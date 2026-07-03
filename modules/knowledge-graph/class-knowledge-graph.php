<?php
namespace TSEMOU\Modules\KnowledgeGraph;

if (!defined('ABSPATH')) exit;

class Knowledge_Graph {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 30);
    }

    public static function relationship_types() {
        return [
            'owns'=>'Owns','owned_by'=>'Owned By','founded'=>'Founded','founded_by'=>'Founded By',
            'ceo_of'=>'CEO Of','executive_of'=>'Executive Of','board_member_of'=>'Board Member Of',
            'subsidiary_of'=>'Subsidiary Of','parent_of'=>'Parent Of','funds'=>'Funds','funded_by'=>'Funded By',
            'lobbies'=>'Lobbies','supports'=>'Supports','opposes'=>'Opposes','partner_of'=>'Partner Of',
            'supplier_to'=>'Supplier To','customer_of'=>'Customer Of','investigated_by'=>'Investigated By',
            'regulated_by'=>'Regulated By','sanctioned_by'=>'Sanctioned By','convicted_by'=>'Convicted By',
            'reported_by'=>'Reported By','associated_with'=>'Associated With','context_only'=>'Context Only'
        ];
    }

    public static function relevance_flags() {
        return [
            'context'=>'Context Only','influence'=>'Influence','responsibility'=>'Responsibility',
            'trust_relevant'=>'Trust Relevant','requires_human_review'=>'Requires Human Review'
        ];
    }

    public static function entity_posts() {
        $entities = get_posts(['post_type'=>'tsemou_entity','post_status'=>['publish','draft','pending','private'],'numberposts'=>1000,'orderby'=>'title','order'=>'ASC']);
        $companies = get_posts(['post_type'=>'company','post_status'=>['publish','draft','pending','private'],'numberposts'=>1000,'orderby'=>'title','order'=>'ASC']);
        return array_merge($entities, $companies);
    }

    public static function graph_relationship_object($from_id, $to_id, $type, $confidence = 0, $flags = [], $notes = '', $status = 'active') {
        $from_id = intval($from_id);
        $to_id = intval($to_id);
        $type = sanitize_key($type);
        $flags = is_array($flags) ? array_map('sanitize_key', $flags) : [];
        $flags = array_values(array_intersect($flags, array_keys(self::relevance_flags())));
        return [
            'relationship_id'=>'kg_' . substr(sha1($from_id.'|'.$to_id.'|'.$type),0,18),
            'from_entity_id'=>$from_id,
            'from_entity_type'=>get_post_type($from_id),
            'to_entity_id'=>$to_id,
            'to_entity_type'=>get_post_type($to_id),
            'relationship_type'=>$type,
            'confidence'=>max(0,min(100,intval($confidence))),
            'flags'=>$flags,
            'notes'=>sanitize_textarea_field($notes),
            'status'=>sanitize_key($status ?: 'active'),
            'created_at'=>current_time('mysql'),
            'updated_at'=>current_time('mysql'),
            'admin_override'=>true
        ];
    }

    public static function all_relationships() {
        $rels = get_option('tsemou_knowledge_graph_relationships', []);
        return is_array($rels) ? $rels : [];
    }

    public static function save_relationships($rels) {
        $rels = is_array($rels) ? array_values($rels) : [];
        update_option('tsemou_knowledge_graph_relationships', $rels, false);
        return $rels;
    }

    public static function add_relationship($from_id, $to_id, $type, $confidence = 0, $flags = [], $notes = '', $status = 'active') {
        $rels = self::all_relationships();
        $new = self::graph_relationship_object($from_id,$to_id,$type,$confidence,$flags,$notes,$status);
        foreach ($rels as $i=>$rel) {
            if (($rel['relationship_id'] ?? '') === $new['relationship_id']) {
                $new['created_at'] = $rel['created_at'] ?? current_time('mysql');
                $rels[$i] = $new;
                self::save_relationships($rels);
                self::add_log('relationship_updated','Knowledge Graph relationship updated.');
                return $new;
            }
        }
        $rels[] = $new;
        self::save_relationships($rels);
        self::add_log('relationship_created','Knowledge Graph relationship created.');
        return $new;
    }

    public static function update_relationship_status($relationship_id, $status) {
        $rels = self::all_relationships();
        foreach ($rels as $i=>$rel) {
            if (($rel['relationship_id'] ?? '') === $relationship_id) {
                $rels[$i]['status'] = sanitize_key($status);
                $rels[$i]['updated_at'] = current_time('mysql');
                self::save_relationships($rels);
                self::add_log('relationship_status','Relationship status changed.');
                return true;
            }
        }
        return false;
    }

    public static function stats() {
        $rels = self::all_relationships();
        $stats = ['relationships_total'=>count($rels),'active'=>0,'inactive'=>0,'needs_review'=>0,'trust_relevant'=>0,'responsibility'=>0,'influence'=>0,'entities_total'=>count(self::entity_posts())];
        foreach ($rels as $rel) {
            $status = $rel['status'] ?? 'active';
            if (!isset($stats[$status])) $stats[$status]=0;
            $stats[$status]++;
            $flags = is_array($rel['flags'] ?? null) ? $rel['flags'] : [];
            foreach (['trust_relevant','responsibility','influence'] as $flag) if (in_array($flag,$flags,true)) $stats[$flag]++;
        }
        return $stats;
    }

    public static function add_log($action, $message) {
        $logs = get_option('tsemou_knowledge_graph_logs', []);
        if (!is_array($logs)) $logs = [];
        array_unshift($logs, ['time'=>current_time('mysql'),'action'=>sanitize_key($action),'message'=>sanitize_text_field($message)]);
        update_option('tsemou_knowledge_graph_logs', array_slice($logs,0,200), false);
    }

    public static function logs() {
        $logs = get_option('tsemou_knowledge_graph_logs', []);
        return is_array($logs) ? $logs : [];
    }

    public function admin_menu() {
        add_submenu_page('tsemou-os','Knowledge Graph','Knowledge Graph','manage_options','tsemou-knowledge-graph',[$this,'render_admin_page']);
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        $notice = null;
        if (!empty($_POST['tsemou_kg_nonce']) && wp_verify_nonce($_POST['tsemou_kg_nonce'], 'tsemou_kg_action')) {
            $action = sanitize_text_field($_POST['kg_action'] ?? '');
            if ($action === 'add_relationship') {
                $from_id = intval($_POST['from_entity_id'] ?? 0);
                $to_id = intval($_POST['to_entity_id'] ?? 0);
                $type = sanitize_key($_POST['relationship_type'] ?? '');
                $confidence = intval($_POST['confidence'] ?? 0);
                $flags = isset($_POST['flags']) && is_array($_POST['flags']) ? $_POST['flags'] : [];
                $notes = $_POST['notes'] ?? '';
                $status = sanitize_key($_POST['status'] ?? 'active');
                if ($from_id && $to_id && $type) {
                    self::add_relationship($from_id,$to_id,$type,$confidence,$flags,$notes,$status);
                    $notice = ['message'=>'Knowledge Graph relationship saved.'];
                }
            }
            if ($action === 'set_status') {
                $relationship_id = sanitize_text_field($_POST['relationship_id'] ?? '');
                $status = sanitize_key($_POST['status'] ?? 'inactive');
                if ($relationship_id) {
                    self::update_relationship_status($relationship_id, $status);
                    $notice = ['message'=>'Relationship status updated.'];
                }
            }
        }
        $entities = self::entity_posts();
        $types = self::relationship_types();
        $flags = self::relevance_flags();
        $rels = self::all_relationships();
        $stats = self::stats();
        $logs = self::logs();
        ?>
        <div class="wrap">
            <h1>TSEMOU Knowledge Graph</h1>
            <p>Admin-controlled relationship graph between entities. A relationship is not guilt; it is context unless policies and evidence make it Trust-relevant.</p>
            <?php if ($notice): ?><div class="notice notice-success"><p><?php echo esc_html($notice['message']); ?></p></div><?php endif; ?>

            <h2>Status</h2>
            <table class="widefat striped" style="max-width:850px;"><tbody>
                <?php foreach ($stats as $key=>$value): ?>
                    <tr><th><?php echo esc_html(ucwords(str_replace('_',' ',$key))); ?></th><td><?php echo esc_html($value); ?></td></tr>
                <?php endforeach; ?>
            </tbody></table>

            <h2>Add / Update Relationship</h2>
            <form method="post">
                <?php wp_nonce_field('tsemou_kg_action', 'tsemou_kg_nonce'); ?>
                <input type="hidden" name="kg_action" value="add_relationship">
                <table class="form-table">
                    <tr><th>From Entity</th><td><select name="from_entity_id" required><option value="">Select source entity</option><?php foreach ($entities as $entity): ?><option value="<?php echo esc_attr($entity->ID); ?>"><?php echo esc_html(get_the_title($entity)); ?> (<?php echo esc_html(get_post_type($entity)); ?>)</option><?php endforeach; ?></select></td></tr>
                    <tr><th>Relationship</th><td><select name="relationship_type" required><?php foreach ($types as $key=>$label): ?><option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th>To Entity</th><td><select name="to_entity_id" required><option value="">Select target entity</option><?php foreach ($entities as $entity): ?><option value="<?php echo esc_attr($entity->ID); ?>"><?php echo esc_html(get_the_title($entity)); ?> (<?php echo esc_html(get_post_type($entity)); ?>)</option><?php endforeach; ?></select></td></tr>
                    <tr><th>Confidence</th><td><input type="number" name="confidence" min="0" max="100" value="80"> /100</td></tr>
                    <tr><th>Relevance Flags</th><td><?php foreach ($flags as $key=>$label): ?><label style="display:block;margin:4px 0;"><input type="checkbox" name="flags[]" value="<?php echo esc_attr($key); ?>"> <?php echo esc_html($label); ?></label><?php endforeach; ?></td></tr>
                    <tr><th>Status</th><td><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option><option value="needs_review">Needs Review</option></select></td></tr>
                    <tr><th>Admin Notes</th><td><textarea name="notes" rows="4" style="width:100%;"></textarea></td></tr>
                </table>
                <?php submit_button('Save Relationship'); ?>
            </form>

            <h2>Relationships</h2>
            <table class="widefat striped">
                <thead><tr><th>From</th><th>Relation</th><th>To</th><th>Confidence</th><th>Flags</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($rels)): ?><tr><td colspan="6">No relationships yet.</td></tr><?php endif; ?>
                <?php foreach ($rels as $rel): ?>
                    <tr>
                        <td><?php echo esc_html(get_the_title(intval($rel['from_entity_id'] ?? 0))); ?></td>
                        <td><code><?php echo esc_html($rel['relationship_type'] ?? ''); ?></code></td>
                        <td><?php echo esc_html(get_the_title(intval($rel['to_entity_id'] ?? 0))); ?></td>
                        <td><?php echo esc_html($rel['confidence'] ?? 0); ?>/100</td>
                        <td><?php echo esc_html(implode(', ', is_array($rel['flags'] ?? null) ? $rel['flags'] : [])); ?></td>
                        <td><strong><?php echo esc_html($rel['status'] ?? 'active'); ?></strong>
                            <form method="post" style="margin-top:6px;">
                                <?php wp_nonce_field('tsemou_kg_action', 'tsemou_kg_nonce'); ?>
                                <input type="hidden" name="kg_action" value="set_status">
                                <input type="hidden" name="relationship_id" value="<?php echo esc_attr($rel['relationship_id'] ?? ''); ?>">
                                <select name="status"><option value="active">Active</option><option value="inactive">Inactive</option><option value="needs_review">Needs Review</option></select>
                                <button class="button button-small">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Logs</h2>
            <table class="widefat striped"><thead><tr><th>Time</th><th>Action</th><th>Message</th></tr></thead><tbody>
            <?php if (empty($logs)): ?><tr><td colspan="3">No logs yet.</td></tr><?php endif; ?>
            <?php foreach (array_slice($logs,0,50) as $log): ?><tr><td><?php echo esc_html($log['time'] ?? ''); ?></td><td><?php echo esc_html($log['action'] ?? ''); ?></td><td><?php echo esc_html($log['message'] ?? ''); ?></td></tr><?php endforeach; ?>
            </tbody></table>
        </div>
        <?php
    }
}
