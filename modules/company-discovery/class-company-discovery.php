<?php
namespace TSEMOU\Modules\CompanyDiscovery;

if (!defined('ABSPATH')) exit;

class Company_Discovery {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_filter('the_content', [$this, 'render_company_tsemport_forced'], 9999);
        add_filter('the_content', [$this, 'render_company_tsemport_content'], 20);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('admin_menu', [$this, 'admin_menu'], 27);
add_shortcode('tsemou_company_discovery_stats', [$this, 'shortcode_company_discovery_stats']);
        add_action('init', [$this, 'ensure_shortcodes'], 1);
    }

    public function ensure_shortcodes() {
add_shortcode('tsemou_company_discovery_stats', [$this, 'shortcode_company_discovery_stats']);
    }

    public static function default_company_object() {
        return [
            'company_uuid' => '',
            'official_name' => '',
            'display_name' => '',
            'aliases' => [],
            'country' => '',
            'industry' => '',
            'sub_industry' => '',
            'website' => '',
            'logo' => '',
            'headquarters' => '',
            'employees' => null,
            'founded' => null,
            'stock_symbol' => '',
            'isin' => '',
            'registration_number' => '',
            'parent_company' => '',
            'subsidiaries' => [],
            'brands' => [],
            'status' => 'active',
        ];
    }

    public static function default_discovery_metadata() {
        return [
            'source' => '',
            'source_type' => '',
            'discovery_date' => current_time('mysql'),
            'last_scan' => current_time('mysql'),
            'next_scan' => '',
            'scan_count' => 1,
            'priority_score' => 0,
            'wave' => '',
            'rank_band' => '',
            'confidence' => 0,
            'status' => 'active',
        ];
    }

    public static function default_pipeline_status() {
        return [
            'company_profile' => true,
            'historical_import' => false,
            'evidence_discovery' => false,
            'trust_ready' => false,
            'community_ready' => false,
            'graph_ready' => false,
        ];
    }

    public static function normalize_company_object($input) {
        $input = is_array($input) ? $input : [];
        $company = array_merge(self::default_company_object(), $input);

        $company['official_name'] = sanitize_text_field($company['official_name'] ?: ($company['name'] ?? ''));
        $company['display_name'] = sanitize_text_field($company['display_name'] ?: $company['official_name']);
        $company['country'] = sanitize_text_field($company['country']);
        $company['industry'] = sanitize_text_field($company['industry']);
        $company['sub_industry'] = sanitize_text_field($company['sub_industry']);
        $company['website'] = esc_url_raw($company['website']);
        $company['logo'] = esc_url_raw($company['logo']);
        $company['headquarters'] = sanitize_text_field($company['headquarters']);
        $company['stock_symbol'] = sanitize_text_field($company['stock_symbol']);
        $company['isin'] = sanitize_text_field($company['isin']);
        $company['registration_number'] = sanitize_text_field($company['registration_number']);
        $company['parent_company'] = sanitize_text_field($company['parent_company']);
        $company['status'] = sanitize_key($company['status'] ?: 'active');

        $company['employees'] = is_numeric($company['employees']) ? intval($company['employees']) : null;
        $company['founded'] = is_numeric($company['founded']) ? intval($company['founded']) : null;

        foreach (['aliases','subsidiaries','brands'] as $list_key) {
            if (is_string($company[$list_key])) {
                $company[$list_key] = array_filter(array_map('trim', explode(',', $company[$list_key])));
            }
            if (!is_array($company[$list_key])) {
                $company[$list_key] = [];
            }
            $company[$list_key] = array_values(array_unique(array_map('sanitize_text_field', $company[$list_key])));
        }

        if (!$company['company_uuid']) {
            $company['company_uuid'] = self::generate_company_uuid($company);
        } else {
            $company['company_uuid'] = sanitize_text_field($company['company_uuid']);
        }

        return $company;
    }

    public static function normalize_discovery_metadata($input) {
        $input = is_array($input) ? $input : [];
        $meta = array_merge(self::default_discovery_metadata(), $input);

        $meta['source'] = sanitize_text_field($meta['source']);
        $meta['source_type'] = sanitize_key($meta['source_type']);
        $meta['priority_score'] = is_numeric($meta['priority_score']) ? intval($meta['priority_score']) : 0;
        $meta['wave'] = sanitize_text_field($meta['wave']);
        $meta['rank_band'] = sanitize_text_field($meta['rank_band']);
        $meta['confidence'] = is_numeric($meta['confidence']) ? max(0, min(100, intval($meta['confidence']))) : 0;
        $meta['status'] = sanitize_key($meta['status'] ?: 'active');

        return $meta;
    }

    public static function generate_company_uuid($company) {
        $seed = strtolower(trim(($company['official_name'] ?? '') . '|' . ($company['country'] ?? '') . '|' . ($company['website'] ?? '') . '|' . ($company['registration_number'] ?? '')));
        return 'tsemou_company_' . substr(sha1($seed), 0, 24);
    }

    public static function domain_from_url($url) {
        $host = wp_parse_url($url, PHP_URL_HOST);
        if (!$host) return '';
        $host = strtolower(preg_replace('/^www\./', '', $host));
        return sanitize_text_field($host);
    }

    public static function find_duplicate_company($company) {
        $company = self::normalize_company_object($company);
        $domain = self::domain_from_url($company['website']);

        if ($domain) {
            $posts = get_posts([
                'post_type' => 'company',
                'post_status' => ['publish','draft','pending','private'],
                'numberposts' => 1,
                'meta_key' => '_tsemou_company_domain',
                'meta_value' => $domain,
            ]);
            if (!empty($posts)) return intval($posts[0]->ID);
        }

        if (!empty($company['registration_number'])) {
            $posts = get_posts([
                'post_type' => 'company',
                'post_status' => ['publish','draft','pending','private'],
                'numberposts' => 1,
                'meta_key' => '_tsemou_company_registration_number',
                'meta_value' => $company['registration_number'],
            ]);
            if (!empty($posts)) return intval($posts[0]->ID);
        }

        if (!empty($company['isin'])) {
            $posts = get_posts([
                'post_type' => 'company',
                'post_status' => ['publish','draft','pending','private'],
                'numberposts' => 1,
                'meta_key' => '_tsemou_company_isin',
                'meta_value' => $company['isin'],
            ]);
            if (!empty($posts)) return intval($posts[0]->ID);
        }

        $existing = get_page_by_title($company['display_name'], OBJECT, 'company');
        if ($existing) return intval($existing->ID);

        return 0;
    }

    public static function create_or_update_company($company_input, $discovery_input = [], $pipeline_input = []) {
        $company = self::normalize_company_object($company_input);
        if (!$company['display_name']) {
            return ['success'=>false, 'message'=>'Missing company name.', 'company_id'=>0, 'action'=>'skipped'];
        }

        $discovery = self::normalize_discovery_metadata($discovery_input);
        $pipeline = array_merge(self::default_pipeline_status(), is_array($pipeline_input) ? $pipeline_input : []);

        $existing_id = self::find_duplicate_company($company);

        $post_data = [
            'post_title' => $company['display_name'],
            'post_type' => 'company',
            'post_status' => 'publish',
        ];

        if ($existing_id) {
            $post_data['ID'] = $existing_id;
            wp_update_post($post_data);
            $company_id = $existing_id;
            $action = 'updated';
        } else {
            $company_id = wp_insert_post($post_data);
            $action = 'created';
        }

        if (is_wp_error($company_id) || !$company_id) {
            return ['success'=>false, 'message'=>'Could not save company.', 'company_id'=>0, 'action'=>'failed'];
        }

        $domain = self::domain_from_url($company['website']);

        update_post_meta($company_id, '_tsemou_company_uuid', $company['company_uuid']);
        update_post_meta($company_id, '_tsemou_company_profile', wp_json_encode($company, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        update_post_meta($company_id, '_tsemou_company_discovery', wp_json_encode($discovery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        update_post_meta($company_id, '_tsemou_company_pipeline_status', wp_json_encode($pipeline, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        update_post_meta($company_id, '_tsemou_company_country', $company['country']);
        update_post_meta($company_id, '_tsemou_company_industry', $company['industry']);
        update_post_meta($company_id, '_tsemou_company_website', $company['website']);
        update_post_meta($company_id, '_tsemou_company_domain', $domain);
        update_post_meta($company_id, '_tsemou_company_logo', $company['logo']);
        update_post_meta($company_id, '_tsemou_company_headquarters', $company['headquarters']);
        update_post_meta($company_id, '_tsemou_company_employees', $company['employees']);
        update_post_meta($company_id, '_tsemou_company_founded', $company['founded']);
        update_post_meta($company_id, '_tsemou_company_stock_symbol', $company['stock_symbol']);
        update_post_meta($company_id, '_tsemou_company_isin', $company['isin']);
        update_post_meta($company_id, '_tsemou_company_registration_number', $company['registration_number']);
        update_post_meta($company_id, '_tsemou_company_discovery_status', $discovery['status']);

        self::add_import_log($action, $company_id, $company['display_name']);

        return ['success'=>true, 'message'=>'Company ' . $action . '.', 'company_id'=>$company_id, 'action'=>$action];
    }

    public static function add_import_log($action, $company_id, $name) {
        $logs = get_option('tsemou_company_discovery_logs', []);
        if (!is_array($logs)) $logs = [];
        array_unshift($logs, [
            'time' => current_time('mysql'),
            'action' => sanitize_key($action),
            'company_id' => intval($company_id),
            'name' => sanitize_text_field($name)
        ]);
        update_option('tsemou_company_discovery_logs', array_slice($logs, 0, 200));
    }

    public static function logs() {
        $logs = get_option('tsemou_company_discovery_logs', []);
        return is_array($logs) ? $logs : [];
    }

    public static function import_from_json($json) {
        $data = json_decode(wp_unslash($json), true);
        if (!is_array($data)) {
            return ['success'=>false, 'message'=>'Invalid JSON.', 'created'=>0, 'updated'=>0, 'failed'=>1, 'results'=>[]];
        }

        if (isset($data['companies']) && is_array($data['companies'])) {
            $items = $data['companies'];
        } elseif (isset($data[0])) {
            $items = $data;
        } else {
            $items = [$data];
        }

        $results = [];
        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($items as $item) {
            $company = $item['company'] ?? $item;
            $discovery = $item['discovery'] ?? [];
            $pipeline = $item['pipeline'] ?? [];

            $result = self::create_or_update_company($company, $discovery, $pipeline);
            $results[] = $result;

            if (!$result['success']) $failed++;
            elseif ($result['action'] === 'created') $created++;
            elseif ($result['action'] === 'updated') $updated++;
        }

        return ['success'=>true, 'message'=>'Import completed.', 'created'=>$created, 'updated'=>$updated, 'failed'=>$failed, 'results'=>$results];
    }

    public static function counts() {
        $total = wp_count_posts('company');
        return [
            'publish' => intval($total->publish ?? 0),
            'draft' => intval($total->draft ?? 0),
            'pending' => intval($total->pending ?? 0),
            'private' => intval($total->private ?? 0),
        ];
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Company Discovery',
            'Company Discovery',
            'manage_options',
            'tsemou-company-discovery',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        $notice = null;

        if (!empty($_POST['tsemou_company_discovery_nonce']) && wp_verify_nonce($_POST['tsemou_company_discovery_nonce'], 'tsemou_company_discovery_action')) {
            $action = sanitize_text_field($_POST['company_discovery_action'] ?? '');

            if ($action === 'mark_engine_complete') {
                self::mark_orchestrator_company_discovery_complete();
                $notice = ['success'=>true, 'message'=>'Company Discovery Engine marked complete in Orchestrator log.', 'created'=>0, 'updated'=>0, 'failed'=>0];
            }

            if ($action === 'import_json') {
                $json = $_POST['company_json'] ?? '';
                $notice = self::import_from_json($json);
            }
        }

        $counts = self::counts();
        $logs = self::logs();

        $sample = [
            'companies' => [
                [
                    'company' => [
                        'official_name' => 'The Coca-Cola Company',
                        'display_name' => 'Coca-Cola',
                        'aliases' => ['Coca Cola', 'Coca-Cola Co.'],
                        'country' => 'United States',
                        'industry' => 'Food & Beverage',
                        'website' => 'https://www.coca-colacompany.com',
                        'headquarters' => 'Atlanta, Georgia, United States',
                        'employees' => 79000,
                        'founded' => 1892,
                        'stock_symbol' => 'KO',
                        'status' => 'active'
                    ],
                    'discovery' => [
                        'source' => 'manual_admin_seed',
                        'source_type' => 'admin',
                        'wave' => 'wave_1',
                        'rank_band' => '1-100',
                        'confidence' => 90
                    ]
                ]
            ]
        ];
        ?>
        <div class="wrap">
            <h1>TSEMOU Company Discovery</h1>
            <p>Imports, normalizes, deduplicates and stores Company Objects. This engine does not calculate Trust and does not create Evidence.</p>
            <form method="post" style="margin:12px 0 18px;">
                <?php wp_nonce_field('tsemou_company_discovery_action', 'tsemou_company_discovery_nonce'); ?>
                <button class="button" name="company_discovery_action" value="mark_engine_complete">Mark Engine Complete</button>
            </form>

            <?php if ($notice): ?>
                <div class="notice <?php echo !empty($notice['success']) ? 'notice-success' : 'notice-error'; ?>">
                    <p>
                        <?php echo esc_html($notice['message']); ?>
                        <?php if (!empty($notice['success'])): ?>
                            Created: <?php echo esc_html($notice['created']); ?>,
                            Updated: <?php echo esc_html($notice['updated']); ?>,
                            Failed: <?php echo esc_html($notice['failed']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <h2>Auto Seed</h2>
            <p>Import the initial safe seed dataset. This creates visible company profiles without scraping or external calls.</p>
            <form method="post" style="margin:12px 0 22px;">
                <?php wp_nonce_field('tsemou_company_discovery_action', 'tsemou_company_discovery_nonce'); ?>
                <button class="button button-primary" name="company_discovery_action" value="auto_seed">Auto Seed Companies</button>
            </form>

            <h2>Company Counts</h2>
            <table class="widefat striped" style="max-width:700px;">
                <tbody>
                    <?php foreach ($counts as $key => $value): ?>
                        <tr><th><?php echo esc_html(ucwords($key)); ?></th><td><?php echo esc_html($value); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Import Company JSON</h2>
            <form method="post">
                <?php wp_nonce_field('tsemou_company_discovery_action', 'tsemou_company_discovery_nonce'); ?>
                <input type="hidden" name="company_discovery_action" value="import_json">
                <textarea name="company_json" rows="18" style="width:100%;font-family:monospace;"><?php echo esc_textarea(wp_json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></textarea>
                <p><button class="button button-primary">Import JSON</button></p>
            </form>

            <h2>Import Logs</h2>
            <table class="widefat striped">
                <thead><tr><th>Time</th><th>Action</th><th>Company ID</th><th>Name</th></tr></thead>
                <tbody>
                    <?php if (empty($logs)): ?><tr><td colspan="4">No imports yet.</td></tr><?php endif; ?>
                    <?php foreach (array_slice($logs, 0, 50) as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['time'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['action'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['company_id'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['name'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    public static function company_profile($company_id) {
        $json = get_post_meta($company_id, '_tsemou_company_profile', true);
        if ($json) {
            $profile = json_decode($json, true);
            if (is_array($profile)) return $profile;
        }

        return [
            'display_name' => get_the_title($company_id),
            'country' => self::first_company_meta($company_id, ['_tsemou_company_country','company_country','country','_company_country']),
            'industry' => self::first_company_meta($company_id, ['_tsemou_company_industry','company_industry','industry','_company_industry']),
            'website' => self::first_company_meta($company_id, ['_tsemou_company_website','company_website','website','_company_website']),
            'logo' => self::first_company_meta($company_id, ['_tsemou_company_logo','company_logo','logo','_company_logo']),
            'status' => 'active',
        ];
    }

    public static function first_company_meta($company_id, $keys, $default = '') {
        foreach ($keys as $key) {
            $value = get_post_meta($company_id, $key, true);
            if ($value !== '' && $value !== null) return $value;
        }
        return $default;
    }

    public static function latest_companies($limit = 12) {
        return get_posts([
            'post_type' => 'company',
            'post_status' => ['publish','draft','pending','private'],
            'numberposts' => intval($limit),
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }

    public function shortcode_company_discovery_stats($atts = []) {
        $atts = shortcode_atts(['show_title' => 'yes'], $atts);
        $counts = self::counts();
        $total = array_sum($counts);

        ob_start();
        ?>
        <section class="tsemou-discovery-stats">
            <?php if ($atts['show_title'] === 'yes'): ?>
                <div class="tsemou-discovery-kicker">TSEMOU Company Database</div>
                <h2>Companies discovered</h2>
            <?php endif; ?>

            <div class="tsemou-discovery-stat-grid">
                <div><strong><?php echo esc_html($total); ?></strong><span>Total</span></div>
                <div><strong><?php echo esc_html($counts['publish'] ?? 0); ?></strong><span>Published</span></div>
                <div><strong><?php echo esc_html($counts['draft'] ?? 0); ?></strong><span>Draft</span></div>
                <div><strong><?php echo esc_html($counts['pending'] ?? 0); ?></strong><span>Pending</span></div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public function shortcode_company_directory($atts = []) {
        $atts = shortcode_atts([
            'limit' => 36,
            'title' => 'Company TSEMPORTS'
        ], $atts);

        $companies = self::latest_companies(intval($atts['limit']));
        $counts = self::counts();
        $total = array_sum($counts);
        $live = intval($counts['publish'] ?? 0);
        $under_review = intval(($counts['draft'] ?? 0) + ($counts['pending'] ?? 0));
        $top_companies = array_slice($companies, 0, 5);
        $low_companies = array_slice(array_reverse($companies), 0, 5);

        $brand_map = [
            'mcdonald' => ['mark'=>'M','class'=>'mcd'],
            'starbucks' => ['mark'=>'★','class'=>'starbucks'],
            'pepsico' => ['mark'=>'P','class'=>'pepsi'],
            'pepsi' => ['mark'=>'P','class'=>'pepsi'],
            'procter' => ['mark'=>'P&G','class'=>'pg'],
            'gamble' => ['mark'=>'P&G','class'=>'pg'],
            'disney' => ['mark'=>'D','class'=>'disney'],
            'netflix' => ['mark'=>'N','class'=>'netflix'],
            'coca' => ['mark'=>'C','class'=>'coke'],
            'airbnb' => ['mark'=>'A','class'=>'airbnb'],
            'uber' => ['mark'=>'U','class'=>'uber'],
            'maersk' => ['mark'=>'✦','class'=>'maersk'],
            'lvmh' => ['mark'=>'L','class'=>'lvmh'],
            'nvidia' => ['mark'=>'N','class'=>'nvidia'],
            'microsoft' => ['mark'=>'M','class'=>'microsoft'],
            'google' => ['mark'=>'G','class'=>'google'],
            'boeing' => ['mark'=>'B','class'=>'boeing'],
            'airbus' => ['mark'=>'A','class'=>'airbus'],
            'exxon' => ['mark'=>'E','class'=>'energy'],
            'chevron' => ['mark'=>'C','class'=>'energy'],
            'blackrock' => ['mark'=>'B','class'=>'finance'],
            'goldman' => ['mark'=>'G','class'=>'finance'],
            'tesla' => ['mark'=>'T','class'=>'tech'],
            'amazon' => ['mark'=>'A','class'=>'tech'],
            'nestl' => ['mark'=>'N','class'=>'food'],
            'sanofi' => ['mark'=>'S','class'=>'health'],
            'astrazeneca' => ['mark'=>'A','class'=>'health'],
            'bayer' => ['mark'=>'B','class'=>'health'],
            'roche' => ['mark'=>'R','class'=>'health'],
            'inditex' => ['mark'=>'Z','class'=>'retail'],
            'zara' => ['mark'=>'Z','class'=>'retail'],
            'h&m' => ['mark'=>'H','class'=>'retail'],
            'hm' => ['mark'=>'H','class'=>'retail'],
        ];

        $brand_for = function($name) use ($brand_map) {
            $lower = strtolower($name);
            foreach ($brand_map as $key => $data) {
                if (strpos($lower, $key) !== false) return $data;
            }
            return ['mark'=>mb_substr($name, 0, 1), 'class'=>'default'];
        };

        ob_start();
        ?>
        <style>
        .tsemou-company-os,
        .tsemou-company-os *{
            box-sizing:border-box;
            writing-mode:horizontal-tb!important;
            text-orientation:mixed!important;
            word-break:normal;
        }
        .tsemou-company-os{
            width:100%;
            width:100vw;
            max-width:none;
            margin-left:calc(50% - 50vw);
            margin-right:calc(50% - 50vw);
            padding:16px clamp(10px,1.2vw,22px) 36px;
            background:#f8fafc;
            color:#071226;
            font-family:Inter,Arial,sans-serif;
            --navy:#071226;
            --blue:#2563eb;
            --purple:#7c3aed;
            --green:#10b981;
            --red:#ef4444;
            --yellow:#f59e0b;
            --border:#e2e8f0;
            --muted:#64748b;
            --card:#ffffff;
        }

        .tsemou-layout-v220{
            display:grid;
            grid-template-columns:220px minmax(760px, 1fr) 250px;
            gap:12px;
            align-items:start;
        }
        .tsemou-left-v220,
        .tsemou-right-v220{min-width:0}
        .tsemou-main-v220{min-width:0}
        /* TSEMOU FULLWIDTH FIX v2.2.1 */
        body .entry-content .tsemou-company-os,
        body .elementor-widget-container .tsemou-company-os,
        body .wp-block-post-content .tsemou-company-os{
            width:100vw!important;
            max-width:none!important;
            margin-left:calc(50% - 50vw)!important;
            margin-right:calc(50% - 50vw)!important;
        }
        .tsemou-main-v220{
            min-width:720px;
        }
        .tsemou-hero-v220 h1{
            overflow-wrap:normal!important;
            word-break:normal!important;
            hyphens:none!important;
        }
        @media(max-width:1280px){
            .tsemou-main-v220{min-width:0}
        }


        .tsemou-box{
            background:rgba(255,255,255,.96);
            border:1px solid var(--border);
            border-radius:18px;
            padding:14px;
            box-shadow:0 12px 30px rgba(15,23,42,.055);
            margin-bottom:14px;
        }
        .tsemou-box.dark{
            background:#071226;
            color:#fff;
            border-color:#071226;
        }
        .tsemou-headline{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:10px;
            margin-bottom:12px;
            font-size:12px;
            font-weight:950;
            text-transform:uppercase;
            letter-spacing:.01em;
        }
        .tsemou-headline .green{color:#059669}
        .tsemou-headline .red{color:#ef4444}
        .tsemou-small-link{
            color:#2563eb;
            font-size:11px;
            font-weight:900;
            text-decoration:none;
            white-space:nowrap;
        }

        .tsemou-rank-row{
            display:grid;
            grid-template-columns:20px 38px minmax(0,1fr) 42px;
            gap:8px;
            align-items:center;
            padding:8px 0;
            border-bottom:1px solid #eef2f7;
        }
        .tsemou-rank-row:last-child{border-bottom:0}
        .tsemou-rank-no{font-weight:950;font-size:13px}
        .tsemou-rank-title{font-weight:950;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .tsemou-rank-meta{font-size:10px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .tsemou-rank-score{font-size:12px;font-weight:950;text-align:right;white-space:nowrap}

        .brandmark{
            display:flex;
            align-items:center;
            justify-content:center;
            flex:0 0 auto;
            overflow:hidden;
            font-weight:950;
            color:#fff;
            background:linear-gradient(135deg,#0f172a,#2563eb);
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.18);
        }
        .brandmark.sm{width:34px;height:34px;border-radius:10px;font-size:13px}
        .brandmark.md{width:62px;height:62px;border-radius:18px;font-size:28px}
        .brandmark.lg{width:106px;height:106px;border-radius:26px;font-size:48px;background:#fff;color:#071226;box-shadow:0 18px 42px rgba(0,0,0,.18)}

        .brandmark.mcd{background:#ffbc0d;color:#da291c;font-family:Arial Black,Arial}
        .brandmark.starbucks{background:#00754a}
        .brandmark.pepsi{background:linear-gradient(135deg,#e11d48 0 33%,#fff 33% 55%,#2563eb 55%);color:#071226}
        .brandmark.pg{background:#0046ad}
        .brandmark.disney{background:#fff;color:#111827;border:1px solid #e2e8f0}
        .brandmark.netflix{background:#e50914}
        .brandmark.coke{background:#f40009}
        .brandmark.airbnb{background:#ff385c}
        .brandmark.uber{background:#000}
        .brandmark.maersk{background:#38bdf8}
        .brandmark.lvmh{background:#fff;color:#111827;border:1px solid #e2e8f0}
        .brandmark.nvidia{background:#76b900;color:#071226}
        .brandmark.microsoft{background:linear-gradient(135deg,#f25022 0 49%,#7fba00 49% 51%,#00a4ef 51% 75%,#ffb900 75%)}
        .brandmark.google{background:linear-gradient(135deg,#4285f4,#34a853,#fbbc05,#ea4335)}
        .brandmark.boeing{background:#0033a1}
        .brandmark.airbus{background:#00205b}
        .brandmark.energy{background:#0f766e}
        .brandmark.finance{background:#111827}
        .brandmark.tech{background:#2563eb}
        .brandmark.food{background:#f59e0b;color:#111827}
        .brandmark.health{background:#16a34a}
        .brandmark.retail{background:#7c3aed}

        .tsemit-banner{
            position:relative;
            overflow:hidden;
            min-height:160px;
            border-radius:18px;
            padding:22px;
            background:linear-gradient(135deg,#4f46e5 0%,#0ea5e9 56%,#0f172a 100%);
            color:#fff;
        }
        .tsemit-banner:after{
            content:"";position:absolute;right:-40px;bottom:-40px;width:140px;height:140px;border-radius:50%;
            background:radial-gradient(circle,rgba(255,255,255,.18),rgba(255,255,255,.02));
        }
        .tsemit-banner h3{font-size:24px;line-height:1.08;margin:0 0 10px;color:#fff}
        .tsemit-banner p{font-size:13px;line-height:1.45;margin:0 0 18px;color:#dbeafe}
        .tsemou-white-btn{
            display:inline-flex;
            position:relative;
            background:#fff;
            color:#071226;
            border-radius:12px;
            padding:11px 14px;
            font-weight:950;
            text-decoration:none;
        }

        .tsemou-hero-v220{
            position:relative;
            overflow:hidden;
            min-height:245px;
            border-radius:20px;
            padding:24px 28px;
            color:#fff;
            background:
                radial-gradient(circle at 84% 24%,rgba(124,58,237,.48),transparent 28%),
                radial-gradient(circle at 68% 80%,rgba(245,158,11,.36),transparent 23%),
                radial-gradient(circle at 28% 20%,rgba(37,99,235,.56),transparent 32%),
                linear-gradient(135deg,#061226 0%,#111b4d 55%,#130c2f 100%);
            box-shadow:0 20px 52px rgba(15,23,42,.18);
        }
        .tsemou-hero-v220:before{
            content:"";position:absolute;inset:0;
            background:
                radial-gradient(circle at 58% 50%,rgba(56,189,248,.18),transparent 30%),
                linear-gradient(115deg,rgba(255,255,255,.12),transparent 36%),
                repeating-linear-gradient(90deg,rgba(255,255,255,.045) 0,rgba(255,255,255,.045) 1px,transparent 1px,transparent 58px);
            opacity:.62;
        }
        .tsemou-hero-v220:after{
            content:"";position:absolute;right:200px;bottom:18px;width:330px;height:150px;
            background:
                radial-gradient(circle at 30% 50%,rgba(59,130,246,.35) 0 2px,transparent 3px),
                radial-gradient(circle at 52% 43%,rgba(59,130,246,.25) 0 1px,transparent 2px);
            background-size:22px 22px,34px 34px;
            transform:rotate(-8deg);
            opacity:.34;
        }
        .tsemou-hero-inner-v220{
            position:relative;
            z-index:1;
            display:grid;
            grid-template-columns:108px minmax(430px,1fr) 220px;
            gap:20px;
            align-items:center;
        }
        .verified-pill{
            display:inline-flex;
            background:#10b981;
            color:#fff;
            border-radius:999px;
            padding:7px 12px;
            font-size:12px;
            font-weight:950;
            margin-bottom:10px;
        }
        .tsemou-hero-v220 h1{
            color:#fff;
            font-size:32px;
            line-height:1.05;
            margin:0 0 12px;
            font-weight:950;
            max-width:720px;
        }
        .hero-tags{
            display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;
        }
        .hero-tags span{
            display:inline-flex;
            align-items:center;
            background:rgba(255,255,255,.13);
            border:1px solid rgba(255,255,255,.16);
            border-radius:999px;
            padding:8px 12px;
            color:#e2e8f0;
            font-size:12px;
            font-weight:850;
            white-space:nowrap;
        }
        .hero-desc{
            color:#dbeafe;
            line-height:1.55;
            font-size:14px;
            margin:0;
            max-width:650px;
        }
        .tsem-score-panel{
            background:rgba(15,23,42,.48);
            border:1px solid rgba(255,255,255,.16);
            border-radius:18px;
            padding:18px;
            backdrop-filter:blur(12px);
            box-shadow:0 18px 42px rgba(0,0,0,.18);
        }
        .score-label{
            font-size:12px;
            font-weight:950;
            color:#e2e8f0;
            text-transform:uppercase;
        }
        .score-main{
            font-size:50px;
            line-height:1;
            color:#fbbf24;
            font-weight:950;
            margin:10px 0;
        }
        .score-main small{font-size:24px;color:#e2e8f0}
        .score-pill{
            display:inline-flex;
            background:#fbbf24;
            color:#111827;
            border-radius:999px;
            padding:7px 10px;
            font-size:12px;
            font-weight:950;
        }
        .purple-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            margin-top:14px;
            background:linear-gradient(135deg,#7c3aed,#2563eb);
            color:#fff;
            text-decoration:none;
            border-radius:12px;
            padding:12px 16px;
            font-weight:950;
            white-space:nowrap;
        }

        .metric-grid{
            display:grid;
            grid-template-columns:repeat(6,minmax(118px,1fr));
            gap:10px;
            margin:14px 0;
        }
        .metric-card{
            background:#fff;
            border:1px solid #e2e8f0;
            border-radius:16px;
            min-height:126px;
            padding:15px;
            box-shadow:0 10px 24px rgba(15,23,42,.045);
        }
        .metric-icon{font-size:22px;line-height:1;margin-bottom:9px}
        .metric-title{font-size:11px;font-weight:950;text-transform:uppercase;color:#334155;margin-bottom:13px}
        .metric-value{font-size:28px;font-weight:950;color:#071226;line-height:1.1}
        .metric-value small{font-size:16px;color:#64748b}
        .metric-card a{display:inline-block;margin-top:10px;color:#2563eb;font-size:11px;font-weight:950;text-decoration:none}

        .content-card{
            background:#fff;
            border:1px solid #e2e8f0;
            border-radius:18px;
            padding:17px;
            box-shadow:0 10px 24px rgba(15,23,42,.045);
            margin-bottom:14px;
        }
        .section-head{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            margin-bottom:13px;
        }
        .section-title{
            margin:0;
            color:#071226;
            font-size:13px;
            font-weight:950;
            text-transform:uppercase;
        }
        .status-card{position:relative;overflow:hidden}
        .status-card:after{
            content:"";position:absolute;right:15px;top:-16px;width:230px;height:135px;
            background:radial-gradient(circle,rgba(99,102,241,.18),transparent 68%);
        }
        .status-text{position:relative;margin:0;color:#334155;line-height:1.6;font-size:14px;max-width:690px}

        .company-grid-v220{
            display:grid;
            grid-template-columns:repeat(4,minmax(180px,1fr));
            gap:13px;
        }
        .company-card-v220{
            background:#fff;
            border:1px solid #e2e8f0;
            border-radius:17px;
            min-width:0;
            overflow:hidden;
            box-shadow:0 8px 20px rgba(15,23,42,.045);
            transition:transform .18s ease, box-shadow .18s ease;
        }
        .company-card-v220:hover{
            transform:translateY(-3px);
            box-shadow:0 14px 30px rgba(15,23,42,.10);
        }
        .company-body{padding:14px}
        .company-top{
            display:flex;
            gap:12px;
            align-items:center;
            margin-bottom:12px;
            min-width:0;
        }
        .company-title{
            margin:0 0 3px;
            color:#071226;
            font-size:14px;
            font-weight:950;
            line-height:1.2;
            overflow-wrap:anywhere;
        }
        .company-sub{
            color:#64748b;
            font-size:11px;
            line-height:1.2;
        }
        .score-badge{
            display:inline-flex;
            border-radius:999px;
            background:#fef3c7;
            color:#92400e;
            padding:6px 9px;
            font-size:11px;
            font-weight:950;
            margin-bottom:9px;
        }
        .small-muted{font-size:12px;color:#64748b}
        .card-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
        .small-purple,.small-light{
            text-decoration:none;border-radius:11px;padding:9px 10px;font-size:11px;font-weight:950;
        }
        .small-purple{background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff}
        .small-light{background:#f8fafc;color:#071226;border:1px solid #e2e8f0}

        .timeline-line{border-left:3px solid #dbeafe;margin-left:10px;padding-left:18px}
        .timeline-item{position:relative;padding-bottom:15px}
        .timeline-dot{position:absolute;left:-28px;top:4px;width:13px;height:13px;border-radius:50%;background:#2563eb}
        .timeline-year{font-weight:950;font-size:13px}
        .timeline-text{font-size:12px;color:#334155;line-height:1.35}

        .related-map{
            height:210px;
            position:relative;
            border-radius:16px;
            background:radial-gradient(circle at center,#f8fafc,#fff);
        }
        .related-center,.related-node{
            position:absolute;border-radius:50%;background:#fff;border:1px solid #e2e8f0;
            display:flex;align-items:center;justify-content:center;text-align:center;
            font-size:11px;font-weight:950;box-shadow:0 12px 28px rgba(15,23,42,.08);
        }
        .related-center{width:70px;height:70px;left:50%;top:50%;transform:translate(-50%,-50%);font-size:28px}
        .related-node{width:58px;height:58px}
        .n1{left:50%;top:6%;transform:translateX(-50%)}.n2{right:5%;top:31%}.n3{right:18%;bottom:5%}.n4{left:18%;bottom:5%}.n5{left:5%;top:31%}

        .why-card{background:#071226;color:#fff;border-color:#071226}
        .why-card h3{color:#fff;margin:0 0 10px;font-size:14px}
        .why-card p{color:#cbd5e1;margin:0 0 12px;font-size:13px;line-height:1.5}
        .positive-card{background:linear-gradient(135deg,#fff,#f0fdf4)}
        .positive-card p,.community-card p{margin:0;color:#334155;font-size:13px;line-height:1.45}
        .community-card{background:linear-gradient(135deg,#fff,#f3e8ff)}
        .avatars{
            display:flex;
            align-items:center;
            margin:12px 0 0;
        }
        .avatar{
            width:28px;height:28px;border-radius:50%;
            background:linear-gradient(135deg,#f59e0b,#7c3aed);
            border:2px solid #fff;
            margin-left:-7px;
        }
        .avatar:first-child{margin-left:0}
        .avatar-plus{font-size:12px;font-weight:950;color:#2563eb;margin-left:8px}

        @media(max-width:1180px){
            .tsemou-layout-v220{grid-template-columns:210px minmax(0,1fr)}
            .tsemou-right-v220{grid-column:1/-1;display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
            .metric-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
            .company-grid-v220{grid-template-columns:repeat(3,minmax(0,1fr))}
        }
        @media(max-width:980px){
            .tsemou-layout-v220{grid-template-columns:1fr}
            .tsemou-left-v220{display:grid;grid-template-columns:1fr 1fr;gap:14px}
            .tsemit-banner{grid-column:1/-1}
            .tsemou-hero-inner-v220{grid-template-columns:106px minmax(0,1fr)}
            .tsem-score-panel{grid-column:1/-1}
            .tsemou-right-v220{grid-template-columns:1fr}
        }
        @media(max-width:720px){
            .tsemou-company-os{padding:10px 8px}
            .tsemou-left-v220{display:block}
            .tsemou-hero-v220{padding:22px}
            .tsemou-hero-inner-v220{display:block}
            .brandmark.lg{margin-bottom:16px}
            .tsemou-hero-v220 h1{font-size:30px}
            .tsem-score-panel{margin-top:18px}
            .metric-grid,.company-grid-v220{grid-template-columns:1fr}
        }
        </style>

        <section class="tsemou-company-os">
            <div class="tsemou-layout-v220">
                <aside class="tsemou-left-v220">
                    <div class="tsemou-box">
                        <div class="tsemou-headline"><span class="green">🏆 Top TSEMScore</span><a class="tsemou-small-link" href="#">View all →</a></div>
                        <?php $rank=1; foreach ($top_companies as $c): $p=self::company_profile($c->ID); $brand=$brand_for(get_the_title($c)); ?>
                            <div class="tsemou-rank-row">
                                <strong class="tsemou-rank-no"><?php echo esc_html($rank); ?></strong>
                                <span class="brandmark sm <?php echo esc_attr($brand['class']); ?>"><?php echo esc_html($brand['mark']); ?></span>
                                <span><div class="tsemou-rank-title"><?php echo esc_html(get_the_title($c)); ?></div><div class="tsemou-rank-meta"><?php echo esc_html(($p['industry'] ?? '') . ' • ' . ($p['country'] ?? '')); ?></div></span>
                                <span class="tsemou-rank-score"><?php echo esc_html(number_format(8.9 - ($rank * .3),1)); ?> ↑</span>
                            </div>
                        <?php $rank++; endforeach; ?>
                    </div>

                    <div class="tsemou-box">
                        <div class="tsemou-headline"><span class="red">▽ Lowest TSEMScore</span><a class="tsemou-small-link" href="#">View all →</a></div>
                        <?php $rank=1; foreach ($low_companies as $c): $p=self::company_profile($c->ID); $brand=$brand_for(get_the_title($c)); ?>
                            <div class="tsemou-rank-row">
                                <strong class="tsemou-rank-no"><?php echo esc_html($rank); ?></strong>
                                <span class="brandmark sm <?php echo esc_attr($brand['class']); ?>"><?php echo esc_html($brand['mark']); ?></span>
                                <span><div class="tsemou-rank-title"><?php echo esc_html(get_the_title($c)); ?></div><div class="tsemou-rank-meta"><?php echo esc_html(($p['industry'] ?? '') . ' • ' . ($p['country'] ?? '')); ?></div></span>
                                <span class="tsemou-rank-score"><?php echo esc_html(number_format(2.0 + ($rank * .2),1)); ?> ↓</span>
                            </div>
                        <?php $rank++; endforeach; ?>
                    </div>

                    <div class="tsemit-banner">
                        <h3>TSEMIT today.<br>Make impact.</h3>
                        <p>Your voice helps improve transparency worldwide.</p>
                        <a class="tsemou-white-btn" href="#tsemit">TSEMIT NOW →</a>
                    </div>
                </aside>

                <main class="tsemou-main-v220">
                    <div class="tsemou-hero-v220">
                        <div class="tsemou-hero-inner-v220">
                            <span class="brandmark lg default">T</span>
                            <div>
                                <span class="verified-pill">Verified</span>
                                <h1><?php echo esc_html($atts['title']); ?></h1>
                                <div class="hero-tags">
                                    <span><?php echo esc_html($total); ?> companies</span>
                                    <span><?php echo esc_html($live); ?> live TSEMPORTS</span>
                                    <span><?php echo esc_html($under_review); ?> under review</span>
                                </div>
                                <p class="hero-desc">Explore company TSEMPORTS, compare TSEMScore signals, read TSEMIDENCE and TSEMIT where your voice can help the community.</p>
                            </div>
                            <div class="tsem-score-panel">
                                <div class="score-label">TSEMScore Index ⓘ</div>
                                <div class="score-main">5.0 <small>/10</small></div>
                                <span class="score-pill">Initial System Baseline</span>
                                <br><a class="purple-btn" href="#tsemit">TSEMIT NOW →</a>
                            </div>
                        </div>
                    </div>

                    <div class="metric-grid">
                        <div class="metric-card"><div class="metric-icon">〽️</div><div class="metric-title">TSEMScore</div><div class="metric-value">5.0 <small>/10</small></div><a href="#">View details →</a></div>
                        <div class="metric-card"><div class="metric-icon">🛡️</div><div class="metric-title">TSEMIDENCE</div><div class="metric-value">0</div><a href="#">Collecting →</a></div>
                        <div class="metric-card"><div class="metric-icon">👥</div><div class="metric-title">TSEMITS</div><div class="metric-value">Open</div><a href="#">Join community →</a></div>
                        <div class="metric-card"><div class="metric-icon">🌿</div><div class="metric-title">Positive Actions</div><div class="metric-value">Soon</div><a href="#">See actions →</a></div>
                        <div class="metric-card"><div class="metric-icon">⚖️</div><div class="metric-title">Open Cases</div><div class="metric-value">—</div><a href="#">View cases →</a></div>
                        <div class="metric-card"><div class="metric-icon">🗓️</div><div class="metric-title">Last Updated</div><div class="metric-value">Now</div><a href="#">Refresh →</a></div>
                    </div>

                    <div class="content-card status-card">
                        <h3 class="section-title">〽 Current Status</h3>
                        <p class="status-text">Company profiles are being structured into TSEMPORTS. TSEMIDENCE collection is active, TSEMScore starts from the neutral 5.0 baseline, and the community can already TSEMIT.</p>
                    </div>

                    <div class="content-card">
                        <div class="section-head"><h3 class="section-title">Company TSEMPORTS</h3><a class="tsemou-small-link" href="#">View all →</a></div>
                        <?php if (empty($companies)): ?>
                            <p>No companies imported yet.</p>
                        <?php else: ?>
                            <div class="company-grid-v220">
                                <?php foreach ($companies as $company): $profile=self::company_profile($company->ID); $country=$profile['country'] ?? ''; $industry=$profile['industry'] ?? ''; $score=get_post_meta($company->ID,'_tsemou_trust_score',true); $score_label=is_numeric($score) ? round(floatval($score),1) : '5.0'; $brand=$brand_for(get_the_title($company)); ?>
                                    <article class="company-card-v220">
                                        <div class="company-body">
                                            <div class="company-top">
                                                <span class="brandmark md <?php echo esc_attr($brand['class']); ?>"><?php echo esc_html($brand['mark']); ?></span>
                                                <div>
                                                    <h3 class="company-title"><?php echo esc_html(get_the_title($company)); ?></h3>
                                                    <div class="company-sub"><?php echo esc_html(trim($industry . ' • ' . $country, ' •')); ?></div>
                                                </div>
                                            </div>
                                            <span class="score-badge">TSEMScore <?php echo esc_html($score_label); ?></span>
                                            <div class="small-muted">TSEMIDENCE collecting</div>
                                            <div class="card-actions">
                                                <a class="small-purple" href="<?php echo esc_url(get_permalink($company)); ?>">TSEMPORT</a>
                                                <a class="small-light" href="<?php echo esc_url(get_permalink($company)); ?>#tsemou-community-vote">TSEMIT</a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </main>

                <aside class="tsemou-right-v220">
                    <div class="tsemou-box">
                        <div class="section-head"><h3 class="section-title">🗓️ Timeline</h3><a class="tsemou-small-link">View full timeline →</a></div>
                        <div class="timeline-line">
                            <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year">Now</div><div class="timeline-text">Company directory upgraded to stable TSEMOU OS layout.</div></div>
                            <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year">Phase 1</div><div class="timeline-text">Company module stabilization.</div></div>
                            <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year">Next</div><div class="timeline-text">Company TSEMPORT final page.</div></div>
                            <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year">Upcoming</div><div class="timeline-text">TSEMIDENCE agents expansion.</div></div>
                            <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year">Future</div><div class="timeline-text">AI insights and benchmarks.</div></div>
                        </div>
                    </div>

                    <div class="tsemou-box">
                        <div class="section-head"><h3 class="section-title">⌘ Related Entities</h3><a class="tsemou-small-link">Explore all →</a></div>
                        <div class="related-map">
                            <div class="related-center">T</div>
                            <div class="related-node n1">CEO</div>
                            <div class="related-node n2">NGO</div>
                            <div class="related-node n3">Media</div>
                            <div class="related-node n4">Gov</div>
                            <div class="related-node n5">Peers</div>
                        </div>
                    </div>

                    <div class="tsemou-box why-card">
                        <h3>Why this TSEMScore?</h3>
                        <p>Understand how each score is calculated, what TSEMIDENCE exists and what still needs review.</p>
                        <a class="tsemou-small-link" style="color:#38bdf8">Learn more →</a>
                    </div>

                    <div class="tsemou-box positive-card">
                        <div class="section-head"><h3 class="section-title">🌿 Positive Actions</h3><a class="tsemou-small-link">View all →</a></div>
                        <p>Positive signals appear separately. No mixing of positive and negative claims. Every claim must lead to TSEMIDENCE.</p>
                    </div>

                    <div class="tsemou-box community-card" id="tsemit">
                        <h3 class="section-title">👥 Community Voice</h3>
                        <p>Your opinion helps improve transparency. Share your insight and help shape the TSEMScore.</p>
                        <div class="avatars"><span class="avatar"></span><span class="avatar"></span><span class="avatar"></span><span class="avatar"></span><span class="avatar-plus">+12.4K</span></div>
                        <a class="purple-btn" href="#">TSEMIT NOW →</a>
                    </div>
                </aside>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public function register_shortcodes() {
        remove_shortcode('tsemou_company_directory');
        add_shortcode('tsemou_company_directory', [$this, 'shortcode_company_directory']);
    }

    public static function company_discovery_engine_status() {
        $counts = self::counts();
        $logs = self::logs();

        $total = array_sum($counts);
        $last_log = !empty($logs[0]) ? $logs[0] : null;

        return [
            'engine' => 'Company Discovery Engine',
            'version' => defined('TSEMOU_CORE_VERSION') ? TSEMOU_CORE_VERSION : '',
            'status' => 'foundation_complete',
            'companies_total' => $total,
            'companies_live' => intval($counts['publish'] ?? 0),
            'companies_under_review' => intval($counts['draft'] ?? 0) + intval($counts['pending'] ?? 0),
            'last_import' => $last_log['time'] ?? '',
            'next_required_engine' => 'Company Sensor / External Connectors',
            'notes' => 'This engine imports, normalizes, deduplicates and displays company objects. External automatic collection is the next separate layer.'
        ];
    }

    public static function mark_orchestrator_company_discovery_complete() {
        if (!class_exists('\TSEMOU\Modules\DiscoveryOrchestrator\Discovery_Orchestrator')) {
            return false;
        }

        if (method_exists('\TSEMOU\Modules\DiscoveryOrchestrator\Discovery_Orchestrator', 'add_log')) {
            \TSEMOU\Modules\DiscoveryOrchestrator\Discovery_Orchestrator::add_log(
                'company_discovery',
                'Company Discovery Engine foundation marked complete.',
                self::company_discovery_engine_status()
            );
            return true;
        }

        return false;
    }

    public static function load_seed_dataset() {
        $path = TSEMOU_CORE_PATH . 'config/company_seed_dataset.json';
        if (!file_exists($path)) {
            return ['companies' => []];
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        return is_array($data) ? $data : ['companies' => []];
    }

    public static function auto_seed_companies($limit = 25) {
        $dataset = self::load_seed_dataset();
        $companies = isset($dataset['companies']) && is_array($dataset['companies']) ? $dataset['companies'] : [];

        $created = 0;
        $updated = 0;
        $failed = 0;
        $results = [];

        foreach (array_slice($companies, 0, intval($limit)) as $company) {
            $discovery = [
                'source' => 'company_seed_dataset',
                'source_type' => 'internal_seed',
                'wave' => 'wave_1',
                'rank_band' => 'initial_seed',
                'confidence' => 80,
                'status' => 'active'
            ];

            $pipeline = [
                'company_profile' => true,
                'historical_import' => false,
                'evidence_discovery' => false,
                'trust_ready' => false,
                'community_ready' => true,
                'graph_ready' => false
            ];

            $result = self::create_or_update_company($company, $discovery, $pipeline);
            $results[] = $result;

            if (!$result['success']) {
                $failed++;
            } elseif ($result['action'] === 'created') {
                $created++;
            } elseif ($result['action'] === 'updated') {
                $updated++;
            }
        }

        self::add_import_log('auto_seed', 0, 'Auto Seed completed. Created: ' . $created . ', Updated: ' . $updated . ', Failed: ' . $failed);

        return [
            'success' => true,
            'message' => 'Auto Seed completed.',
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'results' => $results
        ];
    }


    public static function tsemou_brand_for_company($name) {
        $map = [
            'mcdonald' => ['mark'=>'M','class'=>'mcd'],
            'starbucks' => ['mark'=>'★','class'=>'starbucks'],
            'pepsi' => ['mark'=>'P','class'=>'pepsi'],
            'procter' => ['mark'=>'P&G','class'=>'pg'],
            'disney' => ['mark'=>'D','class'=>'disney'],
            'netflix' => ['mark'=>'N','class'=>'netflix'],
            'coca' => ['mark'=>'C','class'=>'coke'],
            'airbnb' => ['mark'=>'A','class'=>'airbnb'],
            'uber' => ['mark'=>'U','class'=>'uber'],
            'maersk' => ['mark'=>'✦','class'=>'maersk'],
            'lvmh' => ['mark'=>'L','class'=>'lvmh'],
            'nvidia' => ['mark'=>'N','class'=>'nvidia'],
            'microsoft' => ['mark'=>'M','class'=>'microsoft'],
            'google' => ['mark'=>'G','class'=>'google'],
            'alphabet' => ['mark'=>'G','class'=>'google'],
            'apple' => ['mark'=>'','class'=>'apple'],
            'amazon' => ['mark'=>'A','class'=>'amazon'],
            'meta' => ['mark'=>'∞','class'=>'meta'],
            'tesla' => ['mark'=>'T','class'=>'tesla'],
            'boeing' => ['mark'=>'B','class'=>'boeing'],
            'airbus' => ['mark'=>'A','class'=>'airbus'],
            'exxon' => ['mark'=>'E','class'=>'energy'],
            'chevron' => ['mark'=>'C','class'=>'energy'],
            'shell' => ['mark'=>'S','class'=>'energy'],
            'bp' => ['mark'=>'BP','class'=>'energy'],
            'blackrock' => ['mark'=>'B','class'=>'finance'],
            'goldman' => ['mark'=>'G','class'=>'finance'],
            'samsung' => ['mark'=>'S','class'=>'tech'],
            'sony' => ['mark'=>'S','class'=>'tech'],
            'toyota' => ['mark'=>'T','class'=>'auto'],
            'volkswagen' => ['mark'=>'VW','class'=>'auto'],
            'bmw' => ['mark'=>'BMW','class'=>'auto'],
            'nike' => ['mark'=>'N','class'=>'retail'],
            'adidas' => ['mark'=>'A','class'=>'retail'],
            'walmart' => ['mark'=>'W','class'=>'retail'],
            'ikea' => ['mark'=>'I','class'=>'retail'],
            'bayer' => ['mark'=>'B','class'=>'health'],
            'roche' => ['mark'=>'R','class'=>'health'],
            'sanofi' => ['mark'=>'S','class'=>'health'],
            'unilever' => ['mark'=>'U','class'=>'food'],
        ];
        $lower = strtolower($name);
        foreach ($map as $key => $data) {
            if (strpos($lower, $key) !== false) return $data;
        }
        return ['mark'=>mb_substr($name, 0, 1), 'class'=>'default'];
    }

    public function render_company_tsemport_content($content) {
        if (is_admin() || !is_singular('company') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id = get_the_ID();
        $profile = self::company_profile($post_id);
        $title = get_the_title($post_id);
        $brand = self::tsemou_brand_for_company($title);
        $country = $profile['country'] ?? '';
        $industry = $profile['industry'] ?? '';
        $website = $profile['website'] ?? '';
        $headquarters = $profile['headquarters'] ?? '';
        $founded = $profile['founded'] ?? '';
        $score = get_post_meta($post_id, '_tsemou_trust_score', true);
        $score_label = is_numeric($score) ? round(floatval($score), 1) : '5.0';
        $section_payload = class_exists('\\TSEMOU\\Modules\\CompanySectionEngine\\Company_Section_Engine') ? \TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine::section_payload($post_id) : ['evidence'=>[], 'events'=>[], 'related'=>[], 'community'=>['total'=>0], 'counts'=>['evidence'=>0,'events'=>0,'related'=>0,'tsemits'=>0]];

        ob_start();
        ?>
        <style>
        .tsemport,
        .tsemport *{box-sizing:border-box;writing-mode:horizontal-tb!important;text-orientation:mixed!important;word-break:normal}
        .tsemport{
            width:100vw;
            max-width:none;
            margin-left:calc(50% - 50vw);
            margin-right:calc(50% - 50vw);
            padding:18px clamp(10px,1.4vw,24px) 38px;
            background:#f8fafc;
            color:#071226;
            font-family:Inter,Arial,sans-serif;
        }
        .tsemport-shell{
            max-width:1480px;
            margin:0 auto;
            display:grid;
            grid-template-columns:minmax(0,1fr) 310px;
            gap:16px;
            align-items:start;
        }
        .tsemport-main{min-width:0}
        .tsemport-side{min-width:0}
        .tsemport-card{
            background:#fff;
            border:1px solid #e2e8f0;
            border-radius:20px;
            padding:18px;
            box-shadow:0 12px 30px rgba(15,23,42,.055);
            margin-bottom:16px;
        }
        .tsemport-hero{
            position:relative;
            overflow:hidden;
            border-radius:26px;
            min-height:330px;
            padding:32px;
            color:#fff;
            background:
                radial-gradient(circle at 84% 24%,rgba(124,58,237,.50),transparent 28%),
                radial-gradient(circle at 66% 80%,rgba(245,158,11,.42),transparent 24%),
                radial-gradient(circle at 28% 20%,rgba(37,99,235,.58),transparent 32%),
                linear-gradient(135deg,#061226 0%,#111b4d 55%,#130c2f 100%);
            box-shadow:0 24px 60px rgba(15,23,42,.20);
            margin-bottom:16px;
        }
        .tsemport-hero:before{
            content:"";position:absolute;inset:0;
            background:
              radial-gradient(circle at 58% 50%,rgba(56,189,248,.18),transparent 30%),
              linear-gradient(115deg,rgba(255,255,255,.12),transparent 36%),
              repeating-linear-gradient(90deg,rgba(255,255,255,.045) 0,rgba(255,255,255,.045) 1px,transparent 1px,transparent 58px);
            opacity:.62;
        }
        .tsemport-hero:after{
            content:"";position:absolute;right:240px;bottom:20px;width:360px;height:160px;
            background:
              radial-gradient(circle at 30% 50%,rgba(59,130,246,.35) 0 2px,transparent 3px),
              radial-gradient(circle at 52% 43%,rgba(59,130,246,.25) 0 1px,transparent 2px);
            background-size:22px 22px,34px 34px;
            transform:rotate(-8deg);
            opacity:.34;
        }
        .tsemport-hero-inner{
            position:relative;
            z-index:1;
            display:grid;
            grid-template-columns:130px minmax(0,1fr) 260px;
            gap:26px;
            align-items:center;
        }
        .brandmark{
            display:flex;align-items:center;justify-content:center;overflow:hidden;
            font-weight:950;color:#fff;background:linear-gradient(135deg,#0f172a,#2563eb);
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.18);
        }
        .brandmark.lg{width:122px;height:122px;border-radius:30px;font-size:54px;background:#fff;color:#071226;box-shadow:0 18px 42px rgba(0,0,0,.18)}
        .brandmark.md{width:58px;height:58px;border-radius:17px;font-size:26px}
        .brandmark.mcd{background:#ffbc0d;color:#da291c}.brandmark.starbucks{background:#00754a}.brandmark.pepsi{background:linear-gradient(135deg,#e11d48 0 33%,#fff 33% 55%,#2563eb 55%);color:#071226}.brandmark.pg{background:#0046ad}.brandmark.disney{background:#fff;color:#111827;border:1px solid #e2e8f0}.brandmark.netflix{background:#e50914}.brandmark.coke{background:#f40009}.brandmark.airbnb{background:#ff385c}.brandmark.uber{background:#000}.brandmark.apple{background:#111827}.brandmark.amazon{background:#ff9900;color:#111827}.brandmark.meta{background:#0866ff}.brandmark.tesla{background:#cc0000}.brandmark.energy{background:#0f766e}.brandmark.finance{background:#111827}.brandmark.tech{background:#2563eb}.brandmark.auto{background:#475569}.brandmark.retail{background:#7c3aed}.brandmark.health{background:#16a34a}.brandmark.food{background:#f59e0b;color:#111827}
        .verified-pill{display:inline-flex;background:#10b981;color:#fff;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:950;margin-bottom:12px}
        .tsemport-hero h1{color:#fff;font-size:44px;line-height:1.03;margin:0 0 12px;font-weight:950}
        .hero-tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
        .hero-tags span{display:inline-flex;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.16);border-radius:999px;padding:8px 12px;color:#e2e8f0;font-size:13px;font-weight:850;white-space:nowrap}
        .hero-desc{color:#dbeafe;line-height:1.55;font-size:15px;margin:0;max-width:680px}
        .score-panel{background:rgba(15,23,42,.48);border:1px solid rgba(255,255,255,.16);border-radius:20px;padding:20px;backdrop-filter:blur(12px);box-shadow:0 18px 42px rgba(0,0,0,.18)}
        .score-label{font-size:12px;font-weight:950;color:#e2e8f0;text-transform:uppercase}
        .score-main{font-size:56px;line-height:1;color:#fbbf24;font-weight:950;margin:10px 0}.score-main small{font-size:25px;color:#e2e8f0}
        .score-pill{display:inline-flex;background:#fbbf24;color:#111827;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:950}
        .purple-btn{display:inline-flex;align-items:center;justify-content:center;margin-top:14px;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;text-decoration:none;border-radius:12px;padding:12px 16px;font-weight:950;white-space:nowrap}
        .metrics{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:16px}
        .metric{background:#fff;border:1px solid #e2e8f0;border-radius:17px;min-height:126px;padding:16px;box-shadow:0 10px 24px rgba(15,23,42,.045)}
        .metric-icon{font-size:23px;line-height:1;margin-bottom:9px}.metric-title{font-size:11px;font-weight:950;text-transform:uppercase;color:#334155;margin-bottom:13px}.metric-value{font-size:28px;font-weight:950;color:#071226;line-height:1.1}.metric small{font-size:16px;color:#64748b}
        .section-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.section-title{margin:0;color:#071226;font-size:13px;font-weight:950;text-transform:uppercase}.small-link{color:#2563eb;font-size:11px;font-weight:900;text-decoration:none;white-space:nowrap}
        .status-card{position:relative;overflow:hidden}.status-card:after{content:"";position:absolute;right:15px;top:-16px;width:230px;height:135px;background:radial-gradient(circle,rgba(99,102,241,.18),transparent 68%)}.status-text{position:relative;margin:0;color:#334155;line-height:1.6;font-size:14px;max-width:760px}
        .tsemidence-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
        .evidence-card{border:1px solid #e2e8f0;border-radius:16px;padding:14px;background:#fff}.evidence-badge{display:inline-flex;border-radius:999px;padding:6px 9px;font-size:11px;font-weight:950;margin-bottom:10px;background:#dbeafe;color:#1d4ed8}.evidence-card h4{margin:0 0 8px;font-size:15px}.evidence-card p{margin:0;color:#475569;font-size:13px;line-height:1.45}
        .timeline-line{border-left:3px solid #dbeafe;margin-left:10px;padding-left:18px}.timeline-item{position:relative;padding-bottom:15px}.timeline-dot{position:absolute;left:-28px;top:4px;width:13px;height:13px;border-radius:50%;background:#2563eb}.timeline-year{font-weight:950;font-size:13px}.timeline-text{font-size:12px;color:#334155;line-height:1.35}
        .related-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.related-grid span{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:13px;text-align:center;font-weight:900;font-size:13px}
        .why-card{background:#071226;color:#fff;border-color:#071226}.why-card h3{color:#fff;margin:0 0 10px;font-size:14px}.why-card p{color:#cbd5e1;margin:0 0 12px;font-size:13px;line-height:1.5}
        .positive-card{background:linear-gradient(135deg,#fff,#f0fdf4)}.community-card{background:linear-gradient(135deg,#fff,#f3e8ff)}
        .positive-card p,.community-card p{margin:0;color:#334155;font-size:13px;line-height:1.45}.avatars{display:flex;align-items:center;margin:12px 0 0}.avatar{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#7c3aed);border:2px solid #fff;margin-left:-7px}.avatar:first-child{margin-left:0}.avatar-plus{font-size:12px;font-weight:950;color:#2563eb;margin-left:8px}
        @media(max-width:1180px){.tsemport-shell{grid-template-columns:1fr}.tsemport-side{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.metrics{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:820px){.tsemport-hero-inner{display:block}.brandmark.lg{margin-bottom:16px}.score-panel{margin-top:18px}.tsemport-hero h1{font-size:34px}.metrics,.tsemidence-list,.tsemport-side{grid-template-columns:1fr}}
        </style>

        <section class="tsemport">
            <div class="tsemport-shell">
                <main class="tsemport-main">
                    <div class="tsemport-hero">
                        <div class="tsemport-hero-inner">
                            <span class="brandmark lg <?php echo esc_attr($brand['class']); ?>"><?php echo esc_html($brand['mark']); ?></span>
                            <div>
                                <span class="verified-pill">Verified Company TSEMPORT</span>
                                <h1><?php echo esc_html($title); ?></h1>
                                <div class="hero-tags">
                                    <?php if($industry): ?><span><?php echo esc_html($industry); ?></span><?php endif; ?>
                                    <?php if($country): ?><span><?php echo esc_html($country); ?></span><?php endif; ?>
                                    <?php if($website): ?><span><?php echo esc_html(preg_replace('#^https?://#','',$website)); ?></span><?php endif; ?>
                                </div>
                                <p class="hero-desc">This TSEMPORT organizes the company profile, TSEMScore, TSEMIDENCE, timeline, positive actions and community TSEMIT activity in one place.</p>
                            </div>
                            <div class="score-panel">
                                <div class="score-label">TSEMScore</div>
                                <div class="score-main"><?php echo esc_html($score_label); ?> <small>/10</small></div>
                                <span class="score-pill">Initial Baseline</span>
                                <br><a class="purple-btn" href="#tsemou-community-vote">TSEMIT NOW →</a>
                            </div>
                        </div>
                    </div>

                    <div class="metrics">
                        <div class="metric"><div class="metric-icon">〽️</div><div class="metric-title">TSEMScore</div><div class="metric-value"><?php echo esc_html($score_label); ?> <small>/10</small></div></div>
                        <div class="metric"><div class="metric-icon">🛡️</div><div class="metric-title">TSEMIDENCE</div><div class="metric-value"><?php echo esc_html($section_payload['counts']['evidence'] ?? 0); ?></div></div>
                        <div class="metric"><div class="metric-icon">👥</div><div class="metric-title">TSEMITS</div><div class="metric-value"><?php echo esc_html(($section_payload['counts']['tsemits'] ?? 0) > 0 ? $section_payload['counts']['tsemits'] : 'Open'); ?></div></div>
                        <div class="metric"><div class="metric-icon">🌿</div><div class="metric-title">Positive Actions</div><div class="metric-value">Soon</div></div>
                        <div class="metric"><div class="metric-icon">⚖️</div><div class="metric-title">Open Cases</div><div class="metric-value">—</div></div>
                        <div class="metric"><div class="metric-icon">🗓️</div><div class="metric-title">Last Updated</div><div class="metric-value">Now</div></div>
                    </div>

                    <div class="tsemport-card status-card">
                        <h3 class="section-title">〽 Current Status</h3>
                        <p class="status-text">This company starts from the neutral TSEMScore baseline of 5.0. TSEMIDENCE collection and human review are not complete yet, so no final conclusion is displayed.</p>
                    </div>

                    <div class="tsemport-card">
                        <div class="section-head"><h3 class="section-title">Company Profile</h3></div>
                        <div class="related-grid">
                            <span>Industry<br><small><?php echo esc_html($industry ?: '—'); ?></small></span>
                            <span>Country<br><small><?php echo esc_html($country ?: '—'); ?></small></span>
                            <span>HQ<br><small><?php echo esc_html($headquarters ?: '—'); ?></small></span>
                            <span>Founded<br><small><?php echo esc_html($founded ?: '—'); ?></small></span>
                        </div>
                    </div>

                    <div class="tsemport-card">
                        <div class="section-head"><h3 class="section-title">TSEMIDENCE</h3><a class="small-link" href="#">Add / review →</a></div>
                        <div class="tsemidence-list">
                            <?php if (!empty($section_payload['evidence'])): ?>
                                <?php foreach ($section_payload['evidence'] as $ev): ?>
                                    <?php
                                    $ev_status = get_post_meta($ev->ID, '_tsemou_evidence_status', true) ?: 'pending_review';
                                    $ev_kind = get_post_meta($ev->ID, '_tsemou_evidence_kind', true) ?: 'report';
                                    $ev_url = get_post_meta($ev->ID, '_tsemou_source_url', true);
                                    ?>
                                    <div class="evidence-card">
                                        <span class="evidence-badge"><?php echo esc_html($ev_status); ?></span>
                                        <h4><?php echo esc_html(get_the_title($ev)); ?></h4>
                                        <p><?php echo esc_html(wp_trim_words(wp_strip_all_tags($ev->post_content), 22)); ?></p>
                                        <?php if ($ev_url): ?><p><a class="small-link" href="<?php echo esc_url($ev_url); ?>" target="_blank" rel="noopener">Source →</a></p><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="evidence-card"><span class="evidence-badge">Collecting</span><h4>No linked TSEMIDENCE yet</h4><p>Evidence linked to this company will appear here automatically.</p></div>
                                <div class="evidence-card"><span class="evidence-badge">Admin ready</span><h4>Entity Evidence Links active</h4><p>Add Evidence and connect it to this company ID.</p></div>
                                <div class="evidence-card"><span class="evidence-badge">Human review</span><h4>Review before impact</h4><p>Critical evidence should be reviewed before affecting TSEMScore.</p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>

                <aside class="tsemport-side">
                    <div class="tsemport-card">
                        <div class="section-head"><h3 class="section-title">🗓️ Timeline</h3><a class="small-link">View full →</a></div>
                        <div class="timeline-line">
                            <?php if (!empty($section_payload['events'])): ?>
                                <?php foreach ($section_payload['events'] as $event): ?>
                                    <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year"><?php echo esc_html($event['date'] ?: 'Now'); ?></div><div class="timeline-text"><strong><?php echo esc_html($event['title']); ?></strong><br><?php echo esc_html(wp_trim_words($event['description'], 16)); ?></div></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year">Now</div><div class="timeline-text">Company TSEMPORT page created.</div></div>
                                <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year">Next</div><div class="timeline-text">TSEMIDENCE collection and review.</div></div>
                                <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year">Future</div><div class="timeline-text">TSEMScore explanation and ranking history.</div></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tsemport-card">
                        <div class="section-head"><h3 class="section-title">⌘ Related Entities</h3><a class="small-link">Explore →</a></div>
                        <div class="related-grid">
                            <?php if (!empty($section_payload['related'])): ?>
                                <?php foreach ($section_payload['related'] as $rel): ?>
                                    <span><?php echo esc_html($rel['type'] ?? 'Entity'); ?><br><small><?php echo esc_html($rel['label'] ?? '—'); ?></small></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span>CEO</span><span>Founders</span><span>NGOs</span><span>Media</span><span>Gov</span><span>Peers</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tsemport-card why-card">
                        <h3>Why this TSEMScore?</h3>
                        <p>The score begins from baseline and changes only through TSEMIDENCE, review, positive actions and community signals.</p>
                        <a class="small-link" style="color:#38bdf8">Learn more →</a>
                    </div>

                    <div class="tsemport-card positive-card">
                        <div class="section-head"><h3 class="section-title">🌿 Positive Actions</h3></div>
                        <p>Positive actions will be tracked separately so the platform does not become only a negative archive.</p>
                    </div>

                    <div class="tsemport-card community-card" id="tsemou-community-vote">
                        <h3 class="section-title">👥 TSEMIT</h3>
                        <p>Your opinion helps improve transparency and shape the TSEMScore.</p>
                        <div class="avatars"><span class="avatar"></span><span class="avatar"></span><span class="avatar"></span><span class="avatar"></span><span class="avatar-plus"><?php echo esc_html(($section_payload['community']['total'] ?? 0) > 0 ? $section_payload['community']['total'] . ' TSEMITs' : 'Open'); ?></span></div>
                        <a class="purple-btn" href="#">TSEMIT NOW →</a>
                    </div>
                </aside>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }


    public function render_company_tsemport_forced($content) {
        if (is_admin() || !is_singular('company') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id = get_the_ID();
        $profile = self::company_profile($post_id);
        $title = get_the_title($post_id);

        $brand = method_exists(__CLASS__, 'tsemou_brand_for_company') ? self::tsemou_brand_for_company($title) : ['mark'=>mb_substr($title,0,1),'class'=>'default'];
        $country = $profile['country'] ?? get_post_meta($post_id, '_tsemou_country', true);
        $industry = $profile['industry'] ?? get_post_meta($post_id, '_tsemou_industry', true);
        $website = $profile['website'] ?? get_post_meta($post_id, '_tsemou_website', true);

        $score = get_post_meta($post_id, '_tsemou_trust_score', true);
        if (!is_numeric($score)) $score = get_post_meta($post_id, '_tsemou_final_trust_score', true);
        $score_label = is_numeric($score) ? round(floatval($score), 1) : '5.0';

        $section_payload = class_exists('\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine')
            ? \TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine::section_payload($post_id)
            : ['evidence'=>[], 'events'=>[], 'related'=>[], 'community'=>['total'=>0], 'counts'=>['evidence'=>0,'events'=>0,'related'=>0,'tsemits'=>0]];

        $evidence_count = intval($section_payload['counts']['evidence'] ?? 0);
        $events_count = intval($section_payload['counts']['events'] ?? 0);
        $related_count = intval($section_payload['counts']['related'] ?? 0);
        $tsemit_count = intval($section_payload['counts']['tsemits'] ?? 0);

        ob_start();
        ?>
        <style>
        .tsemport-v226,.tsemport-v226 *{box-sizing:border-box;writing-mode:horizontal-tb!important;text-orientation:mixed!important;word-break:normal}
        .tsemport-v226{width:100vw;max-width:none;margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);padding:18px clamp(10px,1.4vw,24px) 38px;background:#f8fafc;color:#071226;font-family:Inter,Arial,sans-serif}
        .tsemport-v226-shell{max-width:1480px;margin:0 auto;display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:16px;align-items:start}
        .tsemport-main{min-width:0}.tsemport-side{min-width:0}
        .tsemport-card{background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:18px;box-shadow:0 12px 30px rgba(15,23,42,.055);margin-bottom:16px}
        .tsemport-hero{position:relative;overflow:hidden;border-radius:26px;min-height:310px;padding:32px;color:#fff;background:radial-gradient(circle at 84% 24%,rgba(124,58,237,.50),transparent 28%),radial-gradient(circle at 66% 80%,rgba(245,158,11,.42),transparent 24%),radial-gradient(circle at 28% 20%,rgba(37,99,235,.58),transparent 32%),linear-gradient(135deg,#061226 0%,#111b4d 55%,#130c2f 100%);box-shadow:0 24px 60px rgba(15,23,42,.20);margin-bottom:16px}
        .tsemport-hero:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 58% 50%,rgba(56,189,248,.18),transparent 30%),linear-gradient(115deg,rgba(255,255,255,.12),transparent 36%),repeating-linear-gradient(90deg,rgba(255,255,255,.045) 0,rgba(255,255,255,.045) 1px,transparent 1px,transparent 58px);opacity:.62}
        .tsemport-hero-inner{position:relative;z-index:1;display:grid;grid-template-columns:130px minmax(0,1fr) 260px;gap:26px;align-items:center}
        .brandmark{display:flex;align-items:center;justify-content:center;overflow:hidden;font-weight:950;color:#fff;background:linear-gradient(135deg,#0f172a,#2563eb);box-shadow:inset 0 0 0 1px rgba(255,255,255,.18)}
        .brandmark.lg{width:122px;height:122px;border-radius:30px;font-size:54px;background:#fff;color:#071226;box-shadow:0 18px 42px rgba(0,0,0,.18)}
        .brandmark.starbucks{background:#00754a;color:#fff}.brandmark.default{background:#fff;color:#071226}
        .verified-pill{display:inline-flex;background:#10b981;color:#fff;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:950;margin-bottom:12px}
        .tsemport-hero h1{color:#fff;font-size:44px;line-height:1.03;margin:0 0 12px;font-weight:950}
        .hero-tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
        .hero-tags span{display:inline-flex;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.16);border-radius:999px;padding:8px 12px;color:#e2e8f0;font-size:13px;font-weight:850;white-space:nowrap}
        .hero-desc{color:#dbeafe;line-height:1.55;font-size:15px;margin:0;max-width:680px}
        .score-panel{background:rgba(15,23,42,.48);border:1px solid rgba(255,255,255,.16);border-radius:20px;padding:20px;backdrop-filter:blur(12px);box-shadow:0 18px 42px rgba(0,0,0,.18)}
        .score-label{font-size:12px;font-weight:950;color:#e2e8f0;text-transform:uppercase}
        .score-main{font-size:56px;line-height:1;color:#fbbf24;font-weight:950;margin:10px 0}.score-main small{font-size:25px;color:#e2e8f0}
        .score-pill{display:inline-flex;background:#fbbf24;color:#111827;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:950}
        .purple-btn{display:inline-flex;align-items:center;justify-content:center;margin-top:14px;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff;text-decoration:none;border-radius:12px;padding:12px 16px;font-weight:950;white-space:nowrap}
        .metrics{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:16px}
        .metric{background:#fff;border:1px solid #e2e8f0;border-radius:17px;min-height:126px;padding:16px;box-shadow:0 10px 24px rgba(15,23,42,.045)}
        .metric-icon{font-size:23px;line-height:1;margin-bottom:9px}.metric-title{font-size:11px;font-weight:950;text-transform:uppercase;color:#334155;margin-bottom:13px}.metric-value{font-size:28px;font-weight:950;color:#071226;line-height:1.1}
        .section-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.section-title{margin:0;color:#071226;font-size:13px;font-weight:950;text-transform:uppercase}.small-link{color:#2563eb;font-size:11px;font-weight:900;text-decoration:none;white-space:nowrap}
        .status-text{margin:0;color:#334155;line-height:1.6;font-size:14px;max-width:760px}
        .tsemidence-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
        .evidence-card{border:1px solid #e2e8f0;border-radius:16px;padding:14px;background:#fff}.evidence-badge{display:inline-flex;border-radius:999px;padding:6px 9px;font-size:11px;font-weight:950;margin-bottom:10px;background:#dbeafe;color:#1d4ed8}.evidence-card h4{margin:0 0 8px;font-size:15px}.evidence-card p{margin:0 0 8px;color:#475569;font-size:13px;line-height:1.45}
        .timeline-line{border-left:3px solid #dbeafe;margin-left:10px;padding-left:18px}.timeline-item{position:relative;padding-bottom:15px}.timeline-dot{position:absolute;left:-28px;top:4px;width:13px;height:13px;border-radius:50%;background:#2563eb}.timeline-year{font-weight:950;font-size:13px}.timeline-text{font-size:12px;color:#334155;line-height:1.35}
        .related-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.related-grid span{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:13px;text-align:center;font-weight:900;font-size:13px}.related-grid small{color:#64748b;font-weight:700}
        .why-card{background:#071226;color:#fff;border-color:#071226}.why-card h3{color:#fff;margin:0 0 10px;font-size:14px}.why-card p{color:#cbd5e1;margin:0 0 12px;font-size:13px;line-height:1.5}
        .positive-card{background:linear-gradient(135deg,#fff,#f0fdf4)}.community-card{background:linear-gradient(135deg,#fff,#f3e8ff)}.positive-card p,.community-card p{margin:0;color:#334155;font-size:13px;line-height:1.45}
        @media(max-width:1180px){.tsemport-v226-shell{grid-template-columns:1fr}.tsemport-side{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.metrics{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:820px){.tsemport-hero-inner{display:block}.brandmark.lg{margin-bottom:16px}.score-panel{margin-top:18px}.tsemport-hero h1{font-size:34px}.metrics,.tsemidence-list,.tsemport-side{grid-template-columns:1fr}}
        </style>
        <section class="tsemport-v226">
            <div class="tsemport-v226-shell">
                <main class="tsemport-main">
                    <div class="tsemport-hero">
                        <div class="tsemport-hero-inner">
                            <span class="brandmark lg <?php echo esc_attr($brand['class'] ?? 'default'); ?>"><?php echo esc_html($brand['mark'] ?? 'T'); ?></span>
                            <div>
                                <span class="verified-pill">Verified Company TSEMPORT</span>
                                <h1><?php echo esc_html($title); ?></h1>
                                <div class="hero-tags">
                                    <?php if($country): ?><span>🌍 <?php echo esc_html($country); ?></span><?php endif; ?>
                                    <?php if($industry): ?><span>🏭 <?php echo esc_html($industry); ?></span><?php endif; ?>
                                    <span>🛡️ <?php echo esc_html($evidence_count); ?> TSEMIDENCE</span>
                                    <span>👥 <?php echo esc_html($tsemit_count > 0 ? $tsemit_count . ' TSEMITs' : 'TSEMIT open'); ?></span>
                                </div>
                                <p class="hero-desc">This TSEMPORT organizes profile, TSEMScore, TSEMIDENCE, timeline, related entities and community activity in one place.</p>
                            </div>
                            <div class="score-panel">
                                <div class="score-label">TSEMScore</div>
                                <div class="score-main"><?php echo esc_html($score_label); ?> <small>/10</small></div>
                                <span class="score-pill">Engine Connected</span><br><a class="purple-btn" href="#tsemou-community-vote">TSEMIT NOW →</a>
                            </div>
                        </div>
                    </div>
                    <div class="metrics">
                        <div class="metric"><div class="metric-icon">〽️</div><div class="metric-title">TSEMScore</div><div class="metric-value"><?php echo esc_html($score_label); ?></div></div>
                        <div class="metric"><div class="metric-icon">🛡️</div><div class="metric-title">TSEMIDENCE</div><div class="metric-value"><?php echo esc_html($evidence_count); ?></div></div>
                        <div class="metric"><div class="metric-icon">🗓️</div><div class="metric-title">Timeline</div><div class="metric-value"><?php echo esc_html($events_count); ?></div></div>
                        <div class="metric"><div class="metric-icon">⌘</div><div class="metric-title">Relations</div><div class="metric-value"><?php echo esc_html($related_count); ?></div></div>
                        <div class="metric"><div class="metric-icon">👥</div><div class="metric-title">TSEMITS</div><div class="metric-value"><?php echo esc_html($tsemit_count > 0 ? $tsemit_count : 'Open'); ?></div></div>
                        <div class="metric"><div class="metric-icon">🗓️</div><div class="metric-title">Updated</div><div class="metric-value">Now</div></div>
                    </div>
                    <div class="tsemport-card"><h3 class="section-title">〽 Current Status</h3><p class="status-text">Company profile is connected to the Section Engine. Evidence, timeline, related entities and community data can now flow into this TSEMPORT.</p></div>
                    <div class="tsemport-card">
                        <div class="section-head"><h3 class="section-title">TSEMIDENCE</h3><a class="small-link" href="#">Add / review →</a></div>
                        <div class="tsemidence-list">
                            <?php if (!empty($section_payload['evidence'])): ?>
                                <?php foreach ($section_payload['evidence'] as $ev): ?>
                                    <?php $ev_status = get_post_meta($ev->ID, '_tsemou_evidence_status', true) ?: get_post_status($ev); $ev_kind = get_post_meta($ev->ID, '_tsemou_evidence_kind', true) ?: 'report'; $ev_url = get_post_meta($ev->ID, '_tsemou_source_url', true); ?>
                                    <div class="evidence-card"><span class="evidence-badge"><?php echo esc_html($ev_status); ?></span><h4><?php echo esc_html(get_the_title($ev)); ?></h4><p><?php echo esc_html(wp_trim_words(wp_strip_all_tags($ev->post_content), 24)); ?></p><p><small><?php echo esc_html($ev_kind); ?></small></p><?php if ($ev_url): ?><p><a class="small-link" href="<?php echo esc_url($ev_url); ?>" target="_blank" rel="noopener">Source →</a></p><?php endif; ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="evidence-card"><span class="evidence-badge">Empty</span><h4>No linked TSEMIDENCE yet</h4><p>Evidence linked through Company relationship or Entity ID will appear here.</p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>
                <aside class="tsemport-side">
                    <div class="tsemport-card"><div class="section-head"><h3 class="section-title">🗓️ Timeline</h3><a class="small-link">View full →</a></div><div class="timeline-line">
                    <?php if (!empty($section_payload['events'])): foreach ($section_payload['events'] as $event): ?>
                        <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year"><?php echo esc_html($event['date'] ?: 'Now'); ?></div><div class="timeline-text"><strong><?php echo esc_html($event['title']); ?></strong><br><?php echo esc_html(wp_trim_words($event['description'], 16)); ?></div></div>
                    <?php endforeach; else: ?>
                        <div class="timeline-item"><span class="timeline-dot"></span><div class="timeline-year">Now</div><div class="timeline-text">Company TSEMPORT page connected.</div></div>
                    <?php endif; ?>
                    </div></div>
                    <div class="tsemport-card"><div class="section-head"><h3 class="section-title">⌘ Related Entities</h3><a class="small-link">Explore →</a></div><div class="related-grid"><?php foreach (($section_payload['related'] ?? []) as $rel): ?><span><?php echo esc_html($rel['type'] ?? 'Entity'); ?><br><small><?php echo esc_html($rel['label'] ?? '—'); ?></small></span><?php endforeach; ?></div></div>
                    <div class="tsemport-card why-card"><h3>Why this TSEMScore?</h3><p>The score begins from baseline and changes through evidence, review, positive actions and community signals.</p></div>
                    <div class="tsemport-card positive-card"><div class="section-head"><h3 class="section-title">🌿 Positive Actions</h3></div><p>Positive actions will be tracked separately from concerns.</p></div>
                    <div class="tsemport-card community-card" id="tsemou-community-vote"><h3 class="section-title">👥 TSEMIT</h3><p>Your opinion helps improve transparency and shape the TSEMScore.</p><a class="purple-btn" href="#">TSEMIT NOW →</a></div>
                </aside>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

}
