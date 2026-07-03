<?php
namespace TSEMOU\Modules\CompanySensor;
if (!defined('ABSPATH')) exit;

class Company_Sensor {
    private static $instance = null;
    public static function instance(){ if(self::$instance===null) self::$instance=new self(); return self::$instance; }
    private function __construct(){ add_action('admin_menu',[$this,'admin_menu'],31); }

    public static function load_seed_dataset(){
        $path=TSEMOU_CORE_PATH.'config/company_sensor_seed_dataset.json';
        if(!file_exists($path)) return ['companies'=>[]];
        $data=json_decode(file_get_contents($path),true);
        return is_array($data)?$data:['companies'=>[]];
    }

    public static function load_batches(){
        $path=TSEMOU_CORE_PATH.'config/company_import_batches.json';
        if(!file_exists($path)) return ['batches'=>[]];
        $data=json_decode(file_get_contents($path),true);
        return is_array($data)?$data:['batches'=>[]];
    }

    public static function imported_batches(){
        $done=get_option('tsemou_company_imported_batches',[]);
        return is_array($done)?$done:[];
    }

    public static function mark_batch_imported($batch_key){
        $done=self::imported_batches();
        $done[$batch_key]=current_time('mysql');
        update_option('tsemou_company_imported_batches',$done,false);
    }

    public static function reset_batch_status(){
        delete_option('tsemou_company_imported_batches');
    }

    public static function import_companies($companies,$source='company_sensor'){
        if(!class_exists('\TSEMOU\Modules\CompanyDiscovery\Company_Discovery')){
            return ['success'=>false,'message'=>'Company Discovery Engine not available.','created'=>0,'updated'=>0,'failed'=>0];
        }
        $created=0;$updated=0;$failed=0;$results=[];
        foreach($companies as $company){
            if(empty($company['display_name']) && empty($company['official_name'])){ $failed++; continue; }
            $discovery=[
                'source'=>$source,
                'source_type'=>'batch_structured',
                'wave'=>'company_batch_import',
                'rank_band'=>'batch_import',
                'confidence'=>82,
                'status'=>'active'
            ];
            $pipeline=[
                'company_profile'=>true,
                'historical_import'=>false,
                'evidence_discovery'=>false,
                'trust_ready'=>false,
                'community_ready'=>true,
                'graph_ready'=>false
            ];
            $result=\TSEMOU\Modules\CompanyDiscovery\Company_Discovery::create_or_update_company($company,$discovery,$pipeline);
            $results[]=$result;
            if(empty($result['success'])) $failed++;
            elseif(($result['action']??'')==='created') $created++;
            elseif(($result['action']??'')==='updated') $updated++;
        }
        self::add_log('import','Imported companies from '.$source.'. Created: '.$created.', Updated: '.$updated.', Failed: '.$failed);
        return ['success'=>true,'message'=>'Company import completed.','created'=>$created,'updated'=>$updated,'failed'=>$failed,'results'=>$results];
    }

    public static function run_seed_import(){
        $dataset=self::load_seed_dataset();
        $companies=isset($dataset['companies'])&&is_array($dataset['companies'])?$dataset['companies']:[];
        return self::import_companies($companies,'company_sensor_seed_dataset');
    }

    public static function run_batch_import($batch_key,$force=false){
        $batch_key=sanitize_key($batch_key);
        $data=self::load_batches();
        $batches=isset($data['batches'])&&is_array($data['batches'])?$data['batches']:[];
        if(empty($batches[$batch_key])||!is_array($batches[$batch_key])){
            return ['success'=>false,'message'=>'Batch not found.','created'=>0,'updated'=>0,'failed'=>1];
        }
        $done=self::imported_batches();
        if(!$force && isset($done[$batch_key])){
            return ['success'=>true,'message'=>'Batch already imported. Use Force Re-import if needed.','created'=>0,'updated'=>0,'failed'=>0];
        }
        $result=self::import_companies($batches[$batch_key],'company_batch_'.$batch_key);
        if(!empty($result['success'])) self::mark_batch_imported($batch_key);
        return $result;
    }

    public static function import_next_batch(){
        $data=self::load_batches();
        $batches=isset($data['batches'])&&is_array($data['batches'])?$data['batches']:[];
        $done=self::imported_batches();
        foreach($batches as $key=>$companies){
            if(!isset($done[$key])) return self::run_batch_import($key,false);
        }
        return ['success'=>true,'message'=>'All available batches have already been imported.','created'=>0,'updated'=>0,'failed'=>0];
    }

    public static function import_from_json_text($json){
        $data=json_decode(wp_unslash($json),true);
        if(!is_array($data)) return ['success'=>false,'message'=>'Invalid JSON.','created'=>0,'updated'=>0,'failed'=>1];
        $companies=isset($data['companies'])&&is_array($data['companies'])?$data['companies']:(isset($data[0])?$data:[$data]);
        return self::import_companies($companies,'company_sensor_json_text');
    }

    public static function import_from_json_url($url){
        $url=esc_url_raw($url);
        if(!$url) return ['success'=>false,'message'=>'Invalid URL.','created'=>0,'updated'=>0,'failed'=>1];
        $response=wp_remote_get($url,['timeout'=>20,'redirection'=>3]);
        if(is_wp_error($response)) return ['success'=>false,'message'=>$response->get_error_message(),'created'=>0,'updated'=>0,'failed'=>1];
        $code=intval(wp_remote_retrieve_response_code($response));
        if($code<200||$code>=300) return ['success'=>false,'message'=>'HTTP error '.$code,'created'=>0,'updated'=>0,'failed'=>1];
        return self::import_from_json_text(wp_remote_retrieve_body($response));
    }

    public static function add_log($action,$message){
        $logs=get_option('tsemou_company_sensor_logs',[]);
        if(!is_array($logs)) $logs=[];
        array_unshift($logs,['time'=>current_time('mysql'),'action'=>sanitize_key($action),'message'=>sanitize_text_field($message)]);
        update_option('tsemou_company_sensor_logs',array_slice($logs,0,250),false);
    }
    public static function logs(){ $logs=get_option('tsemou_company_sensor_logs',[]); return is_array($logs)?$logs:[]; }

    public function admin_menu(){
        add_submenu_page('tsemou-os','Company Sensor','Company Sensor','manage_options','tsemou-company-sensor',[$this,'render_admin_page']);
    }

    public function render_admin_page(){
        if(!current_user_can('manage_options')) return;
        $notice=null;
        if(!empty($_POST['tsemou_company_sensor_nonce']) && wp_verify_nonce($_POST['tsemou_company_sensor_nonce'],'tsemou_company_sensor_action')){
            $action=sanitize_text_field($_POST['company_sensor_action']??'');
            if($action==='seed') $notice=self::run_seed_import();
            if($action==='next_batch') $notice=self::import_next_batch();
            if($action==='batch') $notice=self::run_batch_import($_POST['batch_key']??'',false);
            if($action==='force_batch') $notice=self::run_batch_import($_POST['batch_key']??'',true);
            if($action==='reset_batches'){ self::reset_batch_status(); $notice=['success'=>true,'message'=>'Batch status reset. Existing companies were not deleted.','created'=>0,'updated'=>0,'failed'=>0]; }
            if($action==='json_text') $notice=self::import_from_json_text($_POST['company_json']??'');
            if($action==='json_url') $notice=self::import_from_json_url($_POST['company_json_url']??'');
        }
        $dataset=self::load_seed_dataset();
        $batch_data=self::load_batches();
        $batches=isset($batch_data['batches'])&&is_array($batch_data['batches'])?$batch_data['batches']:[];
        $done=self::imported_batches();
        $logs=self::logs();
        ?>
        <div class="wrap">
            <h1>TSEMOU Company Sensor</h1>
            <p>Structured company import system with batches.</p>

            <?php if($notice): ?>
                <div class="notice <?php echo !empty($notice['success'])?'notice-success':'notice-error'; ?>">
                    <p><?php echo esc_html($notice['message']); ?>
                    <?php if(!empty($notice['success'])): ?>
                        Created: <?php echo esc_html($notice['created']); ?>,
                        Updated: <?php echo esc_html($notice['updated']); ?>,
                        Failed: <?php echo esc_html($notice['failed']); ?>
                    <?php endif; ?></p>
                </div>
            <?php endif; ?>

            <h2>Quick Action</h2>
            <form method="post" style="margin-bottom:18px;">
                <?php wp_nonce_field('tsemou_company_sensor_action','tsemou_company_sensor_nonce'); ?>
                <button class="button button-primary button-hero" name="company_sensor_action" value="next_batch">Import Next Available Batch</button>
            </form>

            <h2>Company Import Batches</h2>
            <table class="widefat striped">
                <thead><tr><th>Batch</th><th>Companies</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if(empty($batches)): ?><tr><td colspan="4">No batches found.</td></tr><?php endif; ?>
                <?php foreach($batches as $key=>$companies): ?>
                    <tr>
                        <td><strong><?php echo esc_html($key); ?></strong></td>
                        <td><?php echo esc_html(is_array($companies)?count($companies):0); ?></td>
                        <td>
                            <?php if(isset($done[$key])): ?>
                                <span style="color:#059669;font-weight:700;">Imported</span><br><small><?php echo esc_html($done[$key]); ?></small>
                            <?php else: ?>
                                <span style="color:#b45309;font-weight:700;">Not imported</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" style="display:inline-block;margin-right:6px;">
                                <?php wp_nonce_field('tsemou_company_sensor_action','tsemou_company_sensor_nonce'); ?>
                                <input type="hidden" name="batch_key" value="<?php echo esc_attr($key); ?>">
                                <button class="button" name="company_sensor_action" value="batch">Import</button>
                            </form>
                            <form method="post" style="display:inline-block;">
                                <?php wp_nonce_field('tsemou_company_sensor_action','tsemou_company_sensor_nonce'); ?>
                                <input type="hidden" name="batch_key" value="<?php echo esc_attr($key); ?>">
                                <button class="button" name="company_sensor_action" value="force_batch">Force Re-import / Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post" style="margin:14px 0;">
                <?php wp_nonce_field('tsemou_company_sensor_action','tsemou_company_sensor_nonce'); ?>
                <button class="button" name="company_sensor_action" value="reset_batches">Reset Batch Status</button>
                <span class="description">Does not delete companies.</span>
            </form>

            <hr>
            <h2>Legacy Safe Seed Import</h2>
            <p>Imports <?php echo esc_html(count($dataset['companies']??[])); ?> initial seed companies.</p>
            <form method="post">
                <?php wp_nonce_field('tsemou_company_sensor_action','tsemou_company_sensor_nonce'); ?>
                <button class="button" name="company_sensor_action" value="seed">Run Original Seed Import</button>
            </form>

            <h2>Import from JSON URL</h2>
            <form method="post">
                <?php wp_nonce_field('tsemou_company_sensor_action','tsemou_company_sensor_nonce'); ?>
                <input type="url" name="company_json_url" style="width:70%;" placeholder="https://example.com/companies.json">
                <button class="button" name="company_sensor_action" value="json_url">Import URL</button>
            </form>

            <h2>Import JSON Text</h2>
            <form method="post">
                <?php wp_nonce_field('tsemou_company_sensor_action','tsemou_company_sensor_nonce'); ?>
                <textarea name="company_json" rows="8" style="width:100%;font-family:monospace;" placeholder='{"companies":[{"official_name":"Example Inc.","display_name":"Example","country":"United States","industry":"Technology","website":"https://example.com"}]}'></textarea>
                <p><button class="button" name="company_sensor_action" value="json_text">Import JSON Text</button></p>
            </form>

            <h2>Logs</h2>
            <table class="widefat striped">
                <thead><tr><th>Time</th><th>Action</th><th>Message</th></tr></thead>
                <tbody>
                <?php if(empty($logs)): ?><tr><td colspan="3">No logs yet.</td></tr><?php endif; ?>
                <?php foreach(array_slice($logs,0,80) as $log): ?>
                    <tr><td><?php echo esc_html($log['time']??''); ?></td><td><?php echo esc_html($log['action']??''); ?></td><td><?php echo esc_html($log['message']??''); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
