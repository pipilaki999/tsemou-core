<?php
namespace TSEMOU\Modules\CompanySectionEngine;

if (!defined('ABSPATH')) exit;

class Company_Section_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_event_post_type'], 15);
        add_action('add_meta_boxes', [$this, 'register_evidence_meta_boxes']);
        add_action('save_post_evidence', [$this, 'save_evidence_meta'], 10, 2);
        add_action('save_post_tsemou_event', [$this, 'save_event_meta'], 10, 2);
        add_action('admin_menu', [$this, 'admin_menu'], 32);
    }

    public function register_event_post_type() {
        if (!post_type_exists('tsemou_event')) {
            register_post_type('tsemou_event', [
                'labels' => [
                    'name' => 'TSEMOU Events',
                    'singular_name' => 'TSEMOU Event',
                    'add_new_item' => 'Add TSEMOU Event',
                    'edit_item' => 'Edit TSEMOU Event'
                ],
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'tsemou-os',
                'supports' => ['title', 'editor'],
                'capability_type' => 'post',
                'menu_icon' => 'dashicons-clock'
            ]);
        }
    }

    public function register_evidence_meta_boxes() {
        add_meta_box('tsemou_evidence_links', 'TSEMOU Evidence Links', [$this, 'render_evidence_meta_box'], 'evidence', 'normal', 'high');
        add_meta_box('tsemou_event_links', 'TSEMOU Event Links', [$this, 'render_event_meta_box'], 'tsemou_event', 'normal', 'high');
    }

    public function render_evidence_meta_box($post) {
        wp_nonce_field('tsemou_evidence_links_save', 'tsemou_evidence_links_nonce');
        $entity_type = get_post_meta($post->ID, '_tsemou_entity_type', true) ?: 'company';
        $entity_id = get_post_meta($post->ID, '_tsemou_entity_id', true);
        $source_url = get_post_meta($post->ID, '_tsemou_source_url', true);
        $evidence_status = get_post_meta($post->ID, '_tsemou_evidence_status', true) ?: 'pending_review';
        $evidence_kind = get_post_meta($post->ID, '_tsemou_evidence_kind', true) ?: 'report';
        $event_date = get_post_meta($post->ID, '_tsemou_event_date', true);
        ?>
        <p><label><strong>Entity Type</strong></label><br>
        <select name="tsemou_entity_type">
            <?php foreach (['company','person','government','ngo','product','event'] as $type): ?>
                <option value="<?php echo esc_attr($type); ?>" <?php selected($entity_type, $type); ?>><?php echo esc_html($type); ?></option>
            <?php endforeach; ?>
        </select></p>

        <p><label><strong>Entity ID</strong> <small>(fallback only — Company relationship is preferred)</small></label><br>
        <input type="number" name="tsemou_entity_id" value="<?php echo esc_attr($entity_id); ?>" style="width:220px;"></p>

        <p><label><strong>Evidence Kind</strong></label><br>
        <select name="tsemou_evidence_kind">
            <?php foreach (['report','court_case','official_document','investigation','fine','positive_action','community_signal','other'] as $kind): ?>
                <option value="<?php echo esc_attr($kind); ?>" <?php selected($evidence_kind, $kind); ?>><?php echo esc_html($kind); ?></option>
            <?php endforeach; ?>
        </select></p>

        <p><label><strong>Status</strong></label><br>
        <select name="tsemou_evidence_status">
            <?php foreach (['pending_review','human_reviewed','verified','rejected','needs_more_context'] as $status): ?>
                <option value="<?php echo esc_attr($status); ?>" <?php selected($evidence_status, $status); ?>><?php echo esc_html($status); ?></option>
            <?php endforeach; ?>
        </select></p>

        <p><label><strong>Source URL</strong></label><br>
        <input type="url" name="tsemou_source_url" value="<?php echo esc_attr($source_url); ?>" style="width:100%;"></p>

        <p><label><strong>Event / Evidence Date</strong></label><br>
        <input type="date" name="tsemou_event_date" value="<?php echo esc_attr($event_date); ?>"></p>
        <?php
    }

    public function render_event_meta_box($post) {
        wp_nonce_field('tsemou_event_links_save', 'tsemou_event_links_nonce');
        $entity_type = get_post_meta($post->ID, '_tsemou_entity_type', true) ?: 'company';
        $entity_id = get_post_meta($post->ID, '_tsemou_entity_id', true);
        $event_type = get_post_meta($post->ID, '_tsemou_event_type', true) ?: 'general';
        $event_date = get_post_meta($post->ID, '_tsemou_event_date', true);
        ?>
        <p><label><strong>Entity Type</strong></label><br>
        <select name="tsemou_entity_type">
            <?php foreach (['company','person','government','ngo','product','event'] as $type): ?>
                <option value="<?php echo esc_attr($type); ?>" <?php selected($entity_type, $type); ?>><?php echo esc_html($type); ?></option>
            <?php endforeach; ?>
        </select></p>

        <p><label><strong>Entity ID</strong> <small>(fallback only — Company relationship is preferred)</small></label><br>
        <input type="number" name="tsemou_entity_id" value="<?php echo esc_attr($entity_id); ?>" style="width:220px;"></p>

        <p><label><strong>Event Type</strong></label><br>
        <select name="tsemou_event_type">
            <?php foreach (['general','evidence_added','company_imported','score_changed','positive_action','case_opened','community_milestone','admin_note'] as $type): ?>
                <option value="<?php echo esc_attr($type); ?>" <?php selected($event_type, $type); ?>><?php echo esc_html($type); ?></option>
            <?php endforeach; ?>
        </select></p>

        <p><label><strong>Event Date</strong></label><br>
        <input type="date" name="tsemou_event_date" value="<?php echo esc_attr($event_date); ?>"></p>
        <?php
    }

    public function save_evidence_meta($post_id, $post) {
        if (!isset($_POST['tsemou_evidence_links_nonce']) || !wp_verify_nonce($_POST['tsemou_evidence_links_nonce'], 'tsemou_evidence_links_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $entity_type = sanitize_key($_POST['tsemou_entity_type'] ?? 'company');
        $entity_id = absint($_POST['tsemou_entity_id'] ?? 0);

        foreach ($_POST as $field => $raw) {
            if (strpos((string)$field, 'company') === false && strpos((string)$field, 'acf') === false) continue;
            $values = [];
            if (is_array($raw)) {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($raw));
                foreach ($iterator as $v) $values[] = $v;
            } else {
                $values[] = $raw;
            }
            foreach ($values as $value) {
                if (is_numeric($value) && get_post_type(absint($value)) === 'company') {
                    $entity_id = absint($value);
                    $entity_type = 'company';
                    break 2;
                }
            }
        }

        update_post_meta($post_id, '_tsemou_entity_type', $entity_type);
        update_post_meta($post_id, '_tsemou_entity_id', $entity_id);
        update_post_meta($post_id, '_tsemou_company_id', $entity_id);
        update_post_meta($post_id, '_tsemou_evidence_kind', sanitize_key($_POST['tsemou_evidence_kind'] ?? 'report'));
        update_post_meta($post_id, '_tsemou_evidence_status', sanitize_key($_POST['tsemou_evidence_status'] ?? 'pending_review'));
        update_post_meta($post_id, '_tsemou_source_url', esc_url_raw($_POST['tsemou_source_url'] ?? ''));
        update_post_meta($post_id, '_tsemou_event_date', sanitize_text_field($_POST['tsemou_event_date'] ?? ''));

        self::add_engine_log('evidence_link_saved', 'Evidence '.$post_id.' linked to '.$entity_type.' '.$entity_id);
    }

    public function save_event_meta($post_id, $post) {
        if (!isset($_POST['tsemou_event_links_nonce']) || !wp_verify_nonce($_POST['tsemou_event_links_nonce'], 'tsemou_event_links_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        update_post_meta($post_id, '_tsemou_entity_type', sanitize_key($_POST['tsemou_entity_type'] ?? 'company'));
        update_post_meta($post_id, '_tsemou_entity_id', absint($_POST['tsemou_entity_id'] ?? 0));
        update_post_meta($post_id, '_tsemou_event_type', sanitize_key($_POST['tsemou_event_type'] ?? 'general'));
        update_post_meta($post_id, '_tsemou_event_date', sanitize_text_field($_POST['tsemou_event_date'] ?? ''));
    }

    public static function get_company_evidence($company_id, $limit = 6) {
        // Evidence Engine v2.4.0 bridge.
        if (class_exists('\\TSEMOU\\Modules\\EvidenceEngine\\Evidence_Engine')) {
            $normalized = \TSEMOU\Modules\EvidenceEngine\Evidence_Engine::get_for_company($company_id, $limit);
            $posts = [];
            foreach ($normalized as $item) {
                $post = get_post($item['id']);
                if ($post) $posts[] = $post;
            }
            if (!empty($posts)) return $posts;
        }

        if (!post_type_exists('evidence')) return [];
        $company_id = intval($company_id);

        $q = new \WP_Query([
            'post_type' => 'evidence',
            'post_status' => ['publish','pending','draft','private'],
            'posts_per_page' => intval($limit),
            'meta_query' => [
                'relation' => 'OR',
                [
                    'relation' => 'AND',
                    ['key' => '_tsemou_entity_type', 'value' => 'company'],
                    ['key' => '_tsemou_entity_id', 'value' => $company_id, 'compare' => '=']
                ],
                ['key' => '_tsemou_company_id', 'value' => $company_id, 'compare' => '='],
                ['key' => 'company', 'value' => $company_id, 'compare' => '='],
                ['key' => 'company', 'value' => '"' . $company_id . '"', 'compare' => 'LIKE'],
                ['key' => 'tsemou_company', 'value' => $company_id, 'compare' => '='],
                ['key' => 'tsemou_company', 'value' => '"' . $company_id . '"', 'compare' => 'LIKE'],
                ['key' => 'evidence_company', 'value' => $company_id, 'compare' => '='],
                ['key' => 'related_company', 'value' => $company_id, 'compare' => '=']
            ],
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);

        $posts = $q->posts;

        if (empty($posts)) {
            $scan = new \WP_Query([
                'post_type' => 'evidence',
                'post_status' => ['publish','pending','draft','private'],
                'posts_per_page' => 100,
                'orderby' => 'modified',
                'order' => 'DESC'
            ]);
            foreach ($scan->posts as $ev) {
                $all_meta = get_post_meta($ev->ID);
                foreach ($all_meta as $key => $values) {
                    foreach ((array)$values as $value) {
                        if (is_array($value)) $value = wp_json_encode($value);
                        $value = (string)$value;
                        if ($value === (string)$company_id || strpos($value, '"' . $company_id . '"') !== false || strpos($value, 'i:' . $company_id . ';') !== false) {
                            $posts[] = $ev;
                            break 2;
                        }
                    }
                }
                if (count($posts) >= intval($limit)) break;
            }
        }

        return array_slice($posts, 0, intval($limit));
    }

    public static function get_company_events($company_id, $limit = 8) {
        $events = [];

        if (post_type_exists('tsemou_event')) {
            $q = new \WP_Query([
                'post_type' => 'tsemou_event',
                'post_status' => ['publish','pending','draft'],
                'posts_per_page' => intval($limit),
                'meta_query' => [
                    ['key' => '_tsemou_entity_type', 'value' => 'company'],
                    ['key' => '_tsemou_entity_id', 'value' => intval($company_id), 'compare' => '=']
                ],
                'orderby' => 'date',
                'order' => 'DESC'
            ]);
            foreach ($q->posts as $p) {
                $events[] = [
                    'title' => get_the_title($p),
                    'description' => wp_strip_all_tags(get_the_excerpt($p) ?: $p->post_content),
                    'date' => get_post_meta($p->ID, '_tsemou_event_date', true) ?: get_the_date('Y-m-d', $p),
                    'type' => get_post_meta($p->ID, '_tsemou_event_type', true) ?: 'general'
                ];
            }
        }

        $evidence = self::get_company_evidence($company_id, $limit);
        foreach ($evidence as $ev) {
            $events[] = [
                'title' => 'TSEMIDENCE added',
                'description' => get_the_title($ev),
                'date' => get_post_meta($ev->ID, '_tsemou_event_date', true) ?: get_the_date('Y-m-d', $ev),
                'type' => 'evidence_added'
            ];
        }

        usort($events, function($a, $b) { return strcmp($b['date'], $a['date']); });
        return array_slice($events, 0, intval($limit));
    }

    public static function get_related_entities($company_id, $limit = 8) {
        $stored = get_post_meta($company_id, '_tsemou_related_entities', true);
        if (is_array($stored) && !empty($stored)) return array_slice($stored, 0, intval($limit));

        $profile = [];
        if (class_exists('\TSEMOU\Modules\CompanyDiscovery\Company_Discovery')) {
            $profile = \TSEMOU\Modules\CompanyDiscovery\Company_Discovery::company_profile($company_id);
        }

        $related = [];
        if (!empty($profile['industry'])) $related[] = ['type'=>'Industry', 'label'=>$profile['industry'], 'relation'=>'category'];
        if (!empty($profile['country'])) $related[] = ['type'=>'Country', 'label'=>$profile['country'], 'relation'=>'jurisdiction'];
        if (!empty($profile['headquarters'])) $related[] = ['type'=>'HQ', 'label'=>$profile['headquarters'], 'relation'=>'location'];
        $related[] = ['type'=>'CEO', 'label'=>'Not connected yet', 'relation'=>'leadership'];
        $related[] = ['type'=>'Peers', 'label'=>'Same industry', 'relation'=>'comparison'];

        return array_slice($related, 0, intval($limit));
    }

    public static function get_community_summary($company_id) {
        $votes = get_post_meta($company_id, '_tsemou_tsemit_votes', true);
        $votes = is_array($votes) ? $votes : ['positive'=>0, 'concern'=>0, 'needs_review'=>0];
        $total = intval($votes['positive'] ?? 0) + intval($votes['concern'] ?? 0) + intval($votes['needs_review'] ?? 0);
        return [
            'total' => $total,
            'positive' => intval($votes['positive'] ?? 0),
            'concern' => intval($votes['concern'] ?? 0),
            'needs_review' => intval($votes['needs_review'] ?? 0),
            'status' => $total > 0 ? 'active' : 'open'
        ];
    }

    public static function section_payload($company_id) {
        $evidence = self::get_company_evidence($company_id, 6);
        $events = self::get_company_events($company_id, 8);
        $related = self::get_related_entities($company_id, 8);
        $community = self::get_community_summary($company_id);

        return [
            'evidence' => $evidence,
            'events' => $events,
            'related' => $related,
            'community' => $community,
            'counts' => [
                'evidence' => count($evidence),
                'events' => count($events),
                'related' => count($related),
                'tsemits' => $community['total']
            ]
        ];
    }


    public static function diagnose_company_page($company_id) {
        $company_id = absint($company_id);
        $result = [
            'company_id' => $company_id,
            'post_type' => get_post_type($company_id),
            'post_status' => get_post_status($company_id),
            'title' => get_the_title($company_id),
            'evidence_count' => 0,
            'events_count' => 0,
            'related_count' => 0,
            'section_engine_class' => class_exists(__CLASS__) ? 'loaded' : 'missing',
            'notes' => []
        ];

        $payload = self::section_payload($company_id);
        $result['evidence_count'] = intval($payload['counts']['evidence'] ?? 0);
        $result['events_count'] = intval($payload['counts']['events'] ?? 0);
        $result['related_count'] = intval($payload['counts']['related'] ?? 0);

        if ($result['post_type'] !== 'company') $result['notes'][] = 'This ID is not a company post.';
        if ($result['evidence_count'] === 0) $result['notes'][] = 'No linked evidence found by Section Engine.';
        if ($result['events_count'] === 0) $result['notes'][] = 'No timeline events found except fallback.';
        return $result;
    }

    public function admin_menu() {
        add_submenu_page('tsemou-os','Section Engine','Section Engine','manage_options','tsemou-section-engine',[$this,'render_admin_page']);
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>TSEMOU Section Engine</h1>
            <p>This engine connects visible TSEMPORT sections to real data layers without creating new frontend shortcodes.</p><p><strong>v2.2.5:</strong> Evidence uses unified relationship detection. Company relationship is preferred; manual Entity ID is fallback.</p>
            <table class="widefat striped">
                <thead><tr><th>Frontend Section</th><th>Engine Source</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td>TSEMIDENCE</td><td>Evidence posts linked by entity type + entity ID</td><td>Foundation active</td></tr>
                    <tr><td>Timeline</td><td>TSEMOU Events + Evidence dates</td><td>Foundation active</td></tr>
                    <tr><td>Related Entities</td><td>Knowledge Graph fallback / related meta</td><td>Foundation active</td></tr>
                    <tr><td>TSEMIT</td><td>Community vote meta placeholder</td><td>Foundation active</td></tr>
                    <tr><td>TSEMScore</td><td>Company score meta / future Entity Trust</td><td>Foundation active</td></tr>
                </tbody>
            </table>

            <h2>Run Company Diagnostics</h2>
            <form method="get">
                <input type="hidden" name="page" value="tsemou-section-engine">
                <input type="number" name="diagnose_company_id" placeholder="Company ID" style="width:160px;">
                <button class="button">Diagnose Company</button>
            </form>
            <?php if (!empty($_GET['diagnose_company_id'])):
                $diag = self::diagnose_company_page(absint($_GET['diagnose_company_id']));
            ?>
                <h3>Diagnostic Result</h3>
                <table class="widefat striped">
                    <tbody>
                    <?php foreach ($diag as $key => $value): ?>
                        <tr>
                            <th><?php echo esc_html($key); ?></th>
                            <td><?php echo esc_html(is_array($value) ? implode(' | ', $value) : $value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h2>How to add evidence now</h2>
            <ol>
                <li>Go to Evidence → Add New.</li>
                <li>Add title, description and source.</li>
                <li>In TSEMOU Evidence Links choose Entity Type: company.</li>
                <li>Set Entity ID to the company post ID.</li>
                <li>Publish or save draft. It will appear inside the company TSEMPORT.</li>
            </ol>

            <h2>How to add timeline events now</h2>
            <ol>
                <li>Go to TSEMOU OS → TSEMOU Events → Add New.</li>
                <li>Set Entity Type: company and Entity ID.</li>
                <li>Set event type and date.</li>
                <li>The event appears in the company TSEMPORT timeline.</li>
            </ol>
            <h2>Section Engine Logs</h2>
            <table class="widefat striped">
                <thead><tr><th>Time</th><th>Action</th><th>Message</th></tr></thead>
                <tbody>
                <?php $logs = self::logs(); if (empty($logs)): ?>
                    <tr><td colspan="3">No logs yet.</td></tr>
                <?php endif; ?>
                <?php foreach (array_slice($logs, 0, 50) as $log): ?>
                    <tr><td><?php echo esc_html($log['time'] ?? ''); ?></td><td><?php echo esc_html($log['action'] ?? ''); ?></td><td><?php echo esc_html($log['message'] ?? ''); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
