<?php
namespace TSEMOU\Modules\DeveloperConsole;

if (!defined('ABSPATH')) exit;

class Developer_Console {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 5);
        add_action('admin_init', [$this, 'admin_router_fallback'], 1);
    }


    public function admin_router_fallback() {
        if (!is_admin()) return;

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $page = $_GET['page'] ?? '';

        $is_console_path = (strpos($uri, '/wp-admin/tsemou-developer-console') !== false);
        $is_console_page = ($page === 'tsemou-developer-console');

        if (!$is_console_path || $is_console_page) return;

        if (!current_user_can('manage_options')) {
            wp_die('Sorry, you are not allowed to access this page.');
        }

        status_header(200);
        nocache_headers();
        require_once ABSPATH . 'wp-admin/admin-header.php';
        $this->render_page();
        require_once ABSPATH . 'wp-admin/admin-footer.php';
        exit;
    }

    public function admin_menu() {
        $parents = [
            'tsemou-os'
        ];

        foreach ($parents as $parent) {
            add_submenu_page(
                $parent,
                'Developer Console',
                'Developer Console',
                'manage_options',
                'tsemou-developer-console',
                [$this, 'render_page']
            );
        }
    }

    public static function count_posts($post_type, $statuses = ['publish','draft','pending','private']) {
        if (!post_type_exists($post_type)) return ['exists'=>false, 'total'=>0, 'by_status'=>[]];

        $counts = wp_count_posts($post_type);
        $by_status = [];
        $total = 0;

        foreach ($statuses as $status) {
            $value = isset($counts->$status) ? intval($counts->$status) : 0;
            $by_status[$status] = $value;
            $total += $value;
        }

        return ['exists'=>true, 'total'=>$total, 'by_status'=>$by_status];
    }

    public static function detect_classes() {
        return [
            'Company Discovery' => class_exists('\TSEMOU\Modules\CompanyDiscovery\Company_Discovery'),
            'Company Sensor' => class_exists('\TSEMOU\Modules\CompanySensor\Company_Sensor'),
            'Company Section Engine' => class_exists('\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine'),
            'Entity Engine' => class_exists('\TSEMOU\Modules\EntityEngine\Entity_Engine'),
            'Knowledge Graph' => class_exists('\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph'),
            'Trust Engine' => class_exists('\TSEMOU\Modules\TrustEngine\Trust_Engine'),
        ];
    }

    public static function engine_rows() {
        $company = self::count_posts('company');
        $evidence = self::count_posts('evidence');
        $events = self::count_posts('tsemou_event');
        $entities = self::count_posts('tsemou_entity');
        $proofs = self::count_posts('tsemou_proof');

        $classes = self::detect_classes();

        return [
            [
                'engine' => 'Company Engine',
                'status' => $company['exists'] ? 'loaded' : 'missing',
                'records' => $company['total'],
                'source' => 'post_type: company',
                'class' => !empty($classes['Company Discovery']) ? 'loaded' : 'not detected',
                'next' => 'Company directory and TSEMPORT pages'
            ],
            [
                'engine' => 'Evidence / TSEMIDENCE Engine',
                'status' => $evidence['exists'] ? 'loaded' : 'missing',
                'records' => $evidence['total'],
                'source' => 'post_type: evidence + relationship meta',
                'class' => !empty($classes['Company Section Engine']) ? 'section engine loaded' : 'section engine missing',
                'next' => 'Connect evidence relationships reliably'
            ],
            [
                'engine' => 'Timeline Engine',
                'status' => $events['exists'] ? 'loaded' : 'foundation',
                'records' => $events['total'],
                'source' => 'post_type: tsemou_event + evidence dates',
                'class' => !empty($classes['Company Section Engine']) ? 'section engine loaded' : 'section engine missing',
                'next' => 'Normalize event creation'
            ],
            [
                'engine' => 'Entity Engine',
                'status' => $entities['exists'] ? 'loaded' : 'foundation',
                'records' => $entities['total'],
                'source' => 'post_type: tsemou_entity',
                'class' => !empty($classes['Entity Engine']) ? 'loaded' : 'not detected',
                'next' => 'Companies, people, governments, NGOs under unified entity model'
            ],
            [
                'engine' => 'Knowledge Graph',
                'status' => !empty($classes['Knowledge Graph']) ? 'loaded' : 'foundation',
                'records' => $entities['total'],
                'source' => 'entity relations / future graph table',
                'class' => !empty($classes['Knowledge Graph']) ? 'loaded' : 'not detected',
                'next' => 'Related Entities section'
            ],
            [
                'engine' => 'Community / TSEMIT Engine',
                'status' => 'foundation',
                'records' => self::estimate_tsemit_records(),
                'source' => 'post meta: _tsemou_tsemit_votes',
                'class' => 'meta foundation',
                'next' => 'Real user vote tables and moderation'
            ],
            [
                'engine' => 'Trust / TSEMScore Engine',
                'status' => !empty($classes['Trust Engine']) ? 'loaded' : 'foundation',
                'records' => self::estimate_score_records(),
                'source' => 'post meta: _tsemou_trust_score / _tsemou_final_trust_score',
                'class' => !empty($classes['Trust Engine']) ? 'loaded' : 'meta foundation',
                'next' => 'Entity Trust API'
            ],
            [
                'engine' => 'Scraper / Discovery Engine',
                'status' => !empty($classes['Company Sensor']) ? 'loaded' : 'foundation',
                'records' => 0,
                'source' => 'Company Sensor / future scraper queue',
                'class' => !empty($classes['Company Sensor']) ? 'loaded' : 'not detected',
                'next' => 'Automated discovery with admin intervention'
            ],
            [
                'engine' => 'AI Analysis Engine',
                'status' => 'not connected',
                'records' => 0,
                'source' => 'future AI summaries and classification',
                'class' => 'not connected',
                'next' => 'AI assists, human review decides'
            ],
        ];
    }

    public static function estimate_tsemit_records() {
        global $wpdb;
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_tsemou_tsemit_votes'
            )
        );
        return intval($count);
    }

    public static function estimate_score_records() {
        global $wpdb;
        $count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN ('_tsemou_trust_score','_tsemou_final_trust_score')"
        );
        return intval($count);
    }

    public static function find_company_id_by_name($name) {
        $name = sanitize_text_field($name);
        if (!$name) return 0;

        $q = new \WP_Query([
            'post_type' => 'company',
            'post_status' => ['publish','draft','pending','private'],
            'posts_per_page' => 1,
            's' => $name,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);

        if (!empty($q->posts)) return intval($q->posts[0]->ID);
        return 0;
    }

    public static function diagnose_company($company_id) {
        $company_id = absint($company_id);
        $result = [
            'company_id' => $company_id,
            'title' => get_the_title($company_id),
            'post_type' => get_post_type($company_id),
            'post_status' => get_post_status($company_id),
            'permalink' => get_permalink($company_id),
            'company_meta' => [],
            'section_payload' => [],
            'evidence_candidates' => [],
            'timeline_candidates' => [],
            'renderer_detection' => [],
            'errors' => [],
            'recommendations' => [],
        ];

        if (!$company_id || get_post_type($company_id) !== 'company') {
            $result['errors'][] = 'Invalid company ID or ID does not belong to post_type company.';
            return $result;
        }

        $result['company_meta'] = [
            '_tsemou_country' => get_post_meta($company_id, '_tsemou_country', true),
            '_tsemou_industry' => get_post_meta($company_id, '_tsemou_industry', true),
            '_tsemou_trust_score' => get_post_meta($company_id, '_tsemou_trust_score', true),
            '_tsemou_final_trust_score' => get_post_meta($company_id, '_tsemou_final_trust_score', true),
        ];

        if (class_exists('\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine')) {
            $payload = \TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine::section_payload($company_id);
            $result['section_payload'] = [
                'evidence_count' => intval($payload['counts']['evidence'] ?? 0),
                'events_count' => intval($payload['counts']['events'] ?? 0),
                'related_count' => intval($payload['counts']['related'] ?? 0),
                'tsemits_count' => intval($payload['counts']['tsemits'] ?? 0),
            ];
        } else {
            $result['errors'][] = 'Company Section Engine class is not loaded.';
        }

        $result['evidence_candidates'] = self::diagnose_evidence_candidates($company_id);
        $result['timeline_candidates'] = self::diagnose_timeline_candidates($company_id);

        $result['renderer_detection'] = [
            'content_filter_possible' => 'unknown',
            'old_company_report_visible' => 'check frontend text',
            'recommended_next_renderer' => 'Do not force template override until engines pass diagnostics.'
        ];

        if (($result['section_payload']['evidence_count'] ?? 0) === 0 && !empty($result['evidence_candidates'])) {
            $result['recommendations'][] = 'Evidence candidates exist but Section Engine did not count them. Relationship meta mapping needs normalization.';
        }

        if (($result['section_payload']['evidence_count'] ?? 0) === 0 && empty($result['evidence_candidates'])) {
            $result['recommendations'][] = 'No evidence found for this company. Update the Evidence post after selecting Company relationship or Entity ID.';
        }

        if ($result['post_status'] === 'draft') {
            $result['recommendations'][] = 'Company is draft. For public tests, publish it or test preview carefully.';
        }

        return $result;
    }

    public static function diagnose_evidence_candidates($company_id) {
        $candidates = [];
        if (!post_type_exists('evidence')) return $candidates;

        $q = new \WP_Query([
            'post_type' => 'evidence',
            'post_status' => ['publish','pending','draft','private'],
            'posts_per_page' => 30,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);

        foreach ($q->posts as $ev) {
            $meta = get_post_meta($ev->ID);
            $matched = [];
            foreach ($meta as $key => $values) {
                foreach ((array) $values as $value) {
                    if (is_array($value)) $value = wp_json_encode($value);
                    $value = (string) $value;
                    if ($value === (string)$company_id || strpos($value, '"' . $company_id . '"') !== false || strpos($value, 'i:' . $company_id . ';') !== false) {
                        $matched[] = $key;
                    }
                }
            }

            $entity_id = get_post_meta($ev->ID, '_tsemou_entity_id', true);
            $company_id_meta = get_post_meta($ev->ID, '_tsemou_company_id', true);
            if ((string)$entity_id === (string)$company_id) $matched[] = '_tsemou_entity_id';
            if ((string)$company_id_meta === (string)$company_id) $matched[] = '_tsemou_company_id';

            if (!empty($matched)) {
                $candidates[] = [
                    'id' => $ev->ID,
                    'title' => get_the_title($ev),
                    'status' => get_post_status($ev),
                    'matched_meta' => implode(', ', array_unique($matched)),
                    'modified' => get_the_modified_date('Y-m-d H:i:s', $ev),
                ];
            }
        }

        return $candidates;
    }

    public static function diagnose_timeline_candidates($company_id) {
        $events = [];
        if (!post_type_exists('tsemou_event')) return $events;

        $q = new \WP_Query([
            'post_type' => 'tsemou_event',
            'post_status' => ['publish','pending','draft','private'],
            'posts_per_page' => 20,
            'meta_query' => [
                ['key' => '_tsemou_entity_id', 'value' => intval($company_id), 'compare' => '=']
            ],
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);

        foreach ($q->posts as $event) {
            $events[] = [
                'id' => $event->ID,
                'title' => get_the_title($event),
                'status' => get_post_status($event),
                'date' => get_post_meta($event->ID, '_tsemou_event_date', true),
            ];
        }

        return $events;
    }

    public static function run_full_diagnostics($company_id = 0) {
        $rows = self::engine_rows();
        $company_diag = $company_id ? self::diagnose_company($company_id) : null;

        return [
            'timestamp' => current_time('mysql'),
            'core_version' => defined('TSEMOU_CORE_VERSION') ? TSEMOU_CORE_VERSION : 'unknown',
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'engines' => $rows,
            'company_diagnostic' => $company_diag,
        ];
    }

    public static function diagnose_post_evidence_bridge($post_id) {
        $post_id = absint($post_id);
        $out = [
            'post_id' => $post_id,
            'resolved_evidence_id' => 0,
            'evidence_status' => 'failed',
            'linked_entities' => 0,
            'companies_linked' => 0,
            'knowledge_graph_updated' => 'no',
            'pipeline_step' => 'skipped',
            'evidence_created' => 'skipped',
            'entity_linking_executed' => 'skipped',
            'entities_created' => 0,
            'bridge_executed' => false,
            'stage_trace' => [],
            'error' => '',
            'resolution' => null,
        ];

        if ($post_id <= 0 || !get_post($post_id)) {
            $out['error'] = 'invalid_post';
            return $out;
        }

        if (!class_exists('\TSEMOU\Modules\EvidenceEngine\Evidence_Engine')) {
            $out['error'] = 'evidence_engine_not_loaded';
            return $out;
        }

        $resolution = \TSEMOU\Modules\EvidenceEngine\Evidence_Engine::resolve_post_or_evidence_id($post_id, true);
        $out['resolution'] = $resolution;

        if (empty($resolution['success'])) {
            $out['error'] = sanitize_key($resolution['code'] ?? 'evidence_creation_failed');
            $out['evidence_status'] = 'failed';
            return $out;
        }

        $evidence_id = absint($resolution['evidence_id'] ?? 0);
        $out['resolved_evidence_id'] = $evidence_id;
        $out['evidence_status'] = sanitize_key($resolution['status'] ?? 'valid');

        $bridge = is_array($resolution['bridge'] ?? null) ? $resolution['bridge'] : [];
        $out['bridge_executed'] = !empty($bridge['executed']);
        $out['stage_trace'] = is_array($bridge['stage_trace'] ?? null) ? $bridge['stage_trace'] : [];

        $pipeline_steps = is_array($bridge['pipeline_steps'] ?? null) ? $bridge['pipeline_steps'] : [];
        $out['pipeline_step'] = sanitize_key($pipeline_steps['pipeline_step']['status'] ?? ($out['bridge_executed'] ? 'success' : 'skipped'));
        $out['evidence_created'] = sanitize_key($pipeline_steps['evidence_created']['status'] ?? ($out['bridge_executed'] ? 'success' : 'skipped'));
        $out['entity_linking_executed'] = sanitize_key($pipeline_steps['entity_linking_executed']['status'] ?? 'skipped');
        $out['entities_created'] = absint($bridge['entities_created'] ?? 0);

        if ($evidence_id > 0) {
            $company_ids = \TSEMOU\Modules\EvidenceEngine\Evidence_Engine::related_company_ids($evidence_id);
            $out['companies_linked'] = count($company_ids);

            $entity_count = 0;
            foreach (['_tsemou_entity_id','entity_id'] as $key) {
                $value = get_post_meta($evidence_id, $key, true);
                if (is_numeric($value) && absint($value) > 0) $entity_count++;
            }
            $out['linked_entities'] = $entity_count;

            $out['knowledge_graph_updated'] = (!empty($bridge['knowledge_graph_updated']) || $entity_count > 0 || !empty($company_ids)) ? 'yes' : 'no';
        }

        return $out;
    }

    public function render_page() {
        if (!current_user_can('manage_options')) return;

        $company_id = 0;
        if (!empty($_GET['company_id'])) $company_id = absint($_GET['company_id']);
        if (!$company_id && !empty($_GET['company_name'])) $company_id = self::find_company_id_by_name($_GET['company_name']);
        $post_id = !empty($_GET['post_id']) ? absint($_GET['post_id']) : 0;

        $diag = null;
        $post_bridge_diag = null;
        if (!empty($_GET['run_diagnostics'])) {
            $diag = self::run_full_diagnostics($company_id);
            if ($post_id) {
                $post_bridge_diag = self::diagnose_post_evidence_bridge($post_id);
            }
        }

        $engine_rows = self::engine_rows();
        ?>
        <div class="wrap tsemou-dev-console">
            <style>
                .tsemou-dev-console .tsemou-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:18px 0}
                .tsemou-dev-console .card{background:#fff;border:1px solid #dbe3ef;border-radius:14px;padding:16px;box-shadow:0 8px 18px rgba(15,23,42,.05)}
                .tsemou-dev-console .big{font-size:30px;font-weight:800;color:#071226}
                .tsemou-dev-console .muted{color:#64748b}
                .tsemou-dev-console .ok{color:#059669;font-weight:700}
                .tsemou-dev-console .bad{color:#dc2626;font-weight:700}
                .tsemou-dev-console .warn{color:#b45309;font-weight:700}
                .tsemou-dev-console pre{background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;max-height:520px}
                @media(max-width:1100px){.tsemou-dev-console .tsemou-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
                @media(max-width:700px){.tsemou-dev-console .tsemou-grid{grid-template-columns:1fr}}
            </style>

            <h1>TSEMOU Developer Console</h1><p><strong>Admin Router:</strong> active in v2.3.6.</p>
            <p class="muted">Central diagnostic layer for engines, records, relationships and page readiness.</p>

            <div class="tsemou-grid">
                <div class="card"><div class="muted">Core Version</div><div class="big"><?php echo esc_html(defined('TSEMOU_CORE_VERSION') ? TSEMOU_CORE_VERSION : 'unknown'); ?></div></div>
                <div class="card"><div class="muted">Companies</div><div class="big"><?php echo esc_html(self::count_posts('company')['total']); ?></div></div>
                <div class="card"><div class="muted">Evidence</div><div class="big"><?php echo esc_html(self::count_posts('evidence')['total']); ?></div></div>
                <div class="card"><div class="muted">Events</div><div class="big"><?php echo esc_html(self::count_posts('tsemou_event')['total']); ?></div></div>
            </div>

            <h2>Run Full Diagnostics</h2>
            <form method="get" style="background:#fff;border:1px solid #dbe3ef;padding:16px;border-radius:14px;margin-bottom:20px;">
                <input type="hidden" name="page" value="tsemou-developer-console">
                <input type="hidden" name="run_diagnostics" value="1">
                <label><strong>Company ID</strong></label>
                <input type="number" name="company_id" value="<?php echo esc_attr($company_id ?: ''); ?>" placeholder="e.g. 7723" style="width:160px;">
                <label style="margin-left:12px;"><strong>or Company name</strong></label>
                <input type="text" name="company_name" value="<?php echo esc_attr($_GET['company_name'] ?? ''); ?>" placeholder="e.g. Starbucks" style="width:220px;">
                <label style="margin-left:12px;"><strong>Article Post ID</strong></label>
                <input type="number" name="post_id" value="<?php echo esc_attr($post_id ?: ''); ?>" placeholder="e.g. 9637" style="width:140px;">
                <button class="button button-primary">Run Full Diagnostics</button>
            </form>

            <?php if (function_exists('tsemou_renderer_switch_controls')) tsemou_renderer_switch_controls($company_id); ?>

            <?php if (!empty($_GET['run_diagnostics']) && !empty($company_id) && function_exists('tsemou_clean_tsemport_renderer')): ?>
                <h2>Inline Admin TSEMPORT Preview</h2>
                <div style="background:#ecfeff;border:1px solid #67e8f9;border-radius:14px;padding:12px 16px;margin:12px 0 18px;color:#155e75;">
                    Rendered directly inside Developer Console. No frontend route, no draft permalink, no theme template.
                </div>
                <div style="background:#f8fafc;border:1px solid #dbe3ef;border-radius:18px;padding:0;margin-bottom:24px;overflow:hidden;">
                    <?php echo tsemou_clean_tsemport_renderer($company_id); ?>
                </div>
            <?php endif; ?>

            <h2>Engine Status</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Engine</th>
                        <th>Status</th>
                        <th>Records</th>
                        <th>Source</th>
                        <th>Class / Layer</th>
                        <th>Next</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($engine_rows as $row): ?>
                        <tr>
                            <td><strong><?php echo esc_html($row['engine']); ?></strong></td>
                            <td class="<?php echo $row['status'] === 'loaded' ? 'ok' : ($row['status'] === 'missing' ? 'bad' : 'warn'); ?>"><?php echo esc_html($row['status']); ?></td>
                            <td><?php echo esc_html($row['records']); ?></td>
                            <td><?php echo esc_html($row['source']); ?></td>
                            <td><?php echo esc_html($row['class']); ?></td>
                            <td><?php echo esc_html($row['next']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($diag): ?>
                <?php if ($post_bridge_diag): ?>
                    <h2>Post to Evidence Integration Debug</h2>
                    <table class="widefat striped">
                        <tbody>
                            <tr><th>Post ID</th><td><?php echo esc_html($post_bridge_diag['post_id']); ?></td></tr>
                            <tr><th>Resolved Evidence ID</th><td><?php echo esc_html($post_bridge_diag['resolved_evidence_id']); ?></td></tr>
                            <tr><th>Evidence Status</th><td><?php echo esc_html($post_bridge_diag['evidence_status']); ?></td></tr>
                            <tr><th>Pipeline Step</th><td><?php echo esc_html($post_bridge_diag['pipeline_step']); ?></td></tr>
                            <tr><th>Evidence Created</th><td><?php echo esc_html($post_bridge_diag['evidence_created']); ?></td></tr>
                            <tr><th>Entity Linking Executed</th><td><?php echo esc_html($post_bridge_diag['entity_linking_executed']); ?></td></tr>
                            <tr><th>Entities Created</th><td><?php echo esc_html($post_bridge_diag['entities_created']); ?></td></tr>
                            <tr><th>Linked Entities</th><td><?php echo esc_html($post_bridge_diag['linked_entities']); ?></td></tr>
                            <tr><th>Companies Linked</th><td><?php echo esc_html($post_bridge_diag['companies_linked']); ?></td></tr>
                            <tr><th>Knowledge Graph Updated</th><td><?php echo esc_html($post_bridge_diag['knowledge_graph_updated']); ?></td></tr>
                            <tr><th>Error</th><td><?php echo esc_html($post_bridge_diag['error']); ?></td></tr>
                        </tbody>
                    </table>
                    <?php if (!empty($post_bridge_diag['stage_trace'])): ?>
                        <h3>Stage Trace</h3>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>Stage Name</th>
                                    <th>Status</th>
                                    <th>Input</th>
                                    <th>Output</th>
                                    <th>Reason</th>
                                    <th>Records Created</th>
                                    <th>Existing Records Reused</th>
                                    <th>Next Stage Triggered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($post_bridge_diag['stage_trace'] as $stage): ?>
                                    <tr>
                                        <td><?php echo esc_html($stage['stage_name'] ?? ''); ?></td>
                                        <td><?php echo esc_html($stage['executed'] ?? ''); ?></td>
                                        <td><pre style="margin:0;white-space:pre-wrap;max-width:420px;"><?php echo esc_html(wp_json_encode($stage['input'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre></td>
                                        <td><pre style="margin:0;white-space:pre-wrap;max-width:420px;"><?php echo esc_html(wp_json_encode($stage['output'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre></td>
                                        <td><?php echo esc_html($stage['reason'] ?? ''); ?></td>
                                        <td><?php echo esc_html($stage['records_created'] ?? 0); ?></td>
                                        <td><?php echo esc_html($stage['existing_records_reused'] ?? 0); ?></td>
                                        <td><?php echo esc_html($stage['next_stage_triggered'] ?? 'no'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    <pre><?php echo esc_html(wp_json_encode($post_bridge_diag['resolution'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                <?php endif; ?>

                <h2>Diagnostic Result</h2>
                <pre><?php echo esc_html(wp_json_encode($diag, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>

                <?php if (!empty($diag['company_diagnostic'])): $cd = $diag['company_diagnostic']; ?>
                    <h2>Company Diagnostic Summary</h2>
                    <table class="widefat striped">
                        <tbody>
                            <tr><th>Company ID</th><td><?php echo esc_html($cd['company_id']); ?></td></tr>
                            <tr><th>Title</th><td><?php echo esc_html($cd['title']); ?></td></tr>
                            <tr><th>Post Type</th><td><?php echo esc_html($cd['post_type']); ?></td></tr>
                            <tr><th>Status</th><td><?php echo esc_html($cd['post_status']); ?></td></tr>
                            <tr><th>Evidence Count</th><td><?php echo esc_html($cd['section_payload']['evidence_count'] ?? 0); ?></td></tr>
                            <tr><th>Events Count</th><td><?php echo esc_html($cd['section_payload']['events_count'] ?? 0); ?></td></tr>
                            <tr><th>Related Count</th><td><?php echo esc_html($cd['section_payload']['related_count'] ?? 0); ?></td></tr>
                        </tbody>
                    </table>

                    <h3>Evidence Candidates</h3>
                    <table class="widefat striped">
                        <thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Matched Meta</th><th>Modified</th></tr></thead>
                        <tbody>
                        <?php if (empty($cd['evidence_candidates'])): ?>
                            <tr><td colspan="5">No evidence candidates found for this company.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($cd['evidence_candidates'] as $ev): ?>
                            <tr>
                                <td><?php echo esc_html($ev['id']); ?></td>
                                <td><?php echo esc_html($ev['title']); ?></td>
                                <td><?php echo esc_html($ev['status']); ?></td>
                                <td><?php echo esc_html($ev['matched_meta']); ?></td>
                                <td><?php echo esc_html($ev['modified']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h3>Recommendations</h3>
                    <?php if (empty($cd['recommendations'])): ?>
                        <p class="ok">No immediate recommendations. Engine result looks stable.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($cd['recommendations'] as $rec): ?>
                                <li><?php echo esc_html($rec); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
