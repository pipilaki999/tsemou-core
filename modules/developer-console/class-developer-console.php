<?php
namespace TSEMOU\Modules\DeveloperConsole;

if (!defined('ABSPATH')) exit;

class Developer_Console {
    private static $instance = null;
    const ENTITY_REGISTRATION_STATUS_OPTION = 'tsemou_entity_registration_last_status';

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 5);
        add_action('admin_init', [$this, 'admin_router_fallback'], 1);
        add_action('admin_post_tsemou_run_entity_registration', [$this, 'handle_entity_registration_run']);
    }

    public function handle_entity_registration_run() {
        if (!is_admin()) {
            wp_die('Invalid context.');
        }

        if (strtoupper(sanitize_text_field((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))) !== 'POST') {
            wp_die('Invalid request method.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Sorry, you are not allowed to synchronize the entity registry.');
        }

        check_admin_referer('tsemou_run_entity_registration', 'tsemou_entity_registration_nonce');

        if (!class_exists('\TSEMOU\Modules\EntityFoundation\Entity_Migrator')) {
            update_option(self::ENTITY_REGISTRATION_STATUS_OPTION, [
                'ts' => current_time('mysql'),
                'success' => false,
                'code' => 'entity_migrator_missing',
            ], false);

            wp_safe_redirect(add_query_arg([
                'page' => 'tsemou-developer-console',
                'entity_registry_sync' => 'failed',
            ], admin_url('admin.php')));
            exit;
        }

        $allow_cb = function($allow, $channel) {
            return sanitize_key((string) $channel) === 'developer_console' ? true : $allow;
        };

        add_filter('tsemou_allow_migration_run', $allow_cb, 10, 2);
        $result = \TSEMOU\Modules\EntityFoundation\Entity_Migrator::run_entity_registration_cycle_if_guarded(
            200,
            \TSEMOU\Modules\EntityFoundation\Entity_Migrator::MIGRATION_TARGET_VERSION,
            'developer_console',
            100
        );
        remove_filter('tsemou_allow_migration_run', $allow_cb, 10);

        $state = is_array($result['state'] ?? null) ? $result['state'] : [];
        $status = [
            'ts' => current_time('mysql'),
            'success' => !empty($result['success']),
            'blocked' => !empty($result['blocked']),
            'code' => sanitize_key((string) ($result['code'] ?? 'ok')),
            'message' => sanitize_text_field((string) ($result['message'] ?? '')),
            'version' => sanitize_text_field((string) ($result['version'] ?? '')),
            'supported_types' => self::entity_registration_supported_types(),
            'entity_count' => self::canonical_entity_count(),
            'last_execution_time' => current_time('mysql'),
            'completion_state' => !empty($result['done']) ? 'done' : 'running',
            'done' => !empty($result['done']),
            'cap_reached' => !empty($result['cap_reached']),
            'steps_executed' => intval($result['steps_executed'] ?? 0),
            'final_type_index' => intval($result['final_type_index'] ?? 0),
            'final_source_type' => sanitize_key((string) ($result['final_source_type'] ?? '')),
            'final_offset' => intval($result['final_offset'] ?? 0),
            'scanned_objects' => intval($result['action_scanned_objects'] ?? 0),
            'created_entities' => intval($result['action_created_entities'] ?? 0),
            'existing_entities_skipped' => intval($result['action_existing_entities_skipped'] ?? 0),
            'failed_objects' => intval($result['action_failed_objects'] ?? 0),
            'processed' => intval($state['processed'] ?? 0),
            'state' => $state,
        ];

        update_option(self::ENTITY_REGISTRATION_STATUS_OPTION, $status, false);

        $redirect_state = !empty($status['blocked']) ? 'blocked' : (!empty($status['success']) ? 'ok' : 'failed');
        wp_safe_redirect(add_query_arg([
            'page' => 'tsemou-developer-console',
            'entity_registry_sync' => $redirect_state,
        ], admin_url('admin.php')));
        exit;
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

    public static function proof_first_evidence_counts() {
        $proof = self::count_posts('tsemou_proof');
        $legacy = self::count_posts('evidence');

        if (!empty($proof['exists']) && intval($proof['total']) > 0) {
            return [
                'exists' => true,
                'total' => intval($proof['total']),
                'by_status' => $proof['by_status'] ?? [],
                'source' => 'post_type: tsemou_proof',
                'fallback' => false,
            ];
        }

        if (!empty($legacy['exists'])) {
            return [
                'exists' => true,
                'total' => intval($legacy['total']),
                'by_status' => $legacy['by_status'] ?? [],
                'source' => 'post_type: evidence (fallback)',
                'fallback' => true,
            ];
        }

        if (!empty($proof['exists'])) {
            return [
                'exists' => true,
                'total' => 0,
                'by_status' => $proof['by_status'] ?? [],
                'source' => 'post_type: tsemou_proof',
                'fallback' => false,
            ];
        }

        return [
            'exists' => false,
            'total' => 0,
            'by_status' => [],
            'source' => 'post_type: none',
            'fallback' => false,
        ];
    }

    public static function proof_first_evidence_post_types() {
        $types = [];
        if (post_type_exists('tsemou_proof')) $types[] = 'tsemou_proof';
        if (post_type_exists('evidence')) $types[] = 'evidence';
        return $types;
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
        $evidence = self::proof_first_evidence_counts();
        $events = self::count_posts('tsemou_event');
        $entities = self::count_posts('tsemou_entity');
        $proofs = self::count_posts('tsemou_proof');
        $canonical = self::canonical_diagnostics(1);
        $canonical_ready = !empty($canonical['tables']['entities_exists']) && !empty($canonical['tables']['tsemits_exists']);

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
                'source' => ($evidence['source'] ?? 'post_type: tsemou_proof') . ' + relationship meta',
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
                'status' => $canonical_ready ? 'loaded' : 'foundation',
                'records' => self::estimate_tsemit_records(),
                'source' => $canonical_ready ? 'custom table: tsemou_tsemits' : 'post meta: _tsemou_tsemit_votes',
                'class' => $canonical_ready ? 'canonical foundation' : 'meta foundation',
                'next' => $canonical_ready ? 'Expand coverage to more entity surfaces' : 'Real user vote tables and moderation'
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

        if (class_exists('\\TSEMOU\\Modules\\CanonicalTSEMIT\\Canonical_TSEMIT_Store')) {
            $table = $wpdb->prefix . 'tsemou_tsemits';
            $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
            if ($exists === $table) {
                return intval($wpdb->get_var("SELECT COUNT(*) FROM {$table}"));
            }
        }

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

    public static function canonical_diagnostics($comparison_limit = 20) {
        global $wpdb;

        $entities_table = $wpdb->prefix . 'tsemou_entities';
        $tsemits_table = $wpdb->prefix . 'tsemou_tsemits';

        $entities_exists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $entities_table)) === $entities_table);
        $tsemits_exists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tsemits_table)) === $tsemits_table);

        $entities_total = $entities_exists ? intval($wpdb->get_var("SELECT COUNT(*) FROM {$entities_table}")) : 0;
        $tsemits_total = $tsemits_exists ? intval($wpdb->get_var("SELECT COUNT(*) FROM {$tsemits_table}")) : 0;

        $migration_status = class_exists('\\TSEMOU\\Modules\\EntityFoundation\\Entity_Migrator')
            ? \TSEMOU\Modules\EntityFoundation\Entity_Migrator::migration_status()
            : [];

        $comparisons = [];
        if ($entities_exists && $tsemits_exists && post_type_exists('company')) {
            $companies = get_posts([
                'post_type' => 'company',
                'post_status' => ['publish','draft','pending','private'],
                'numberposts' => max(1, intval($comparison_limit)),
                'orderby' => 'ID',
                'order' => 'DESC',
                'fields' => 'ids',
            ]);

            foreach ($companies as $company_id) {
                $company_id = absint($company_id);
                $legacy_votes = get_post_meta($company_id, '_tsemou_community_votes', true);
                $legacy_active = 0;
                if (is_array($legacy_votes)) {
                    foreach ($legacy_votes as $legacy_vote) {
                        if (!is_array($legacy_vote) || !empty($legacy_vote['blocked'])) continue;
                        $legacy_active++;
                    }
                }

                $entity_id = intval($wpdb->get_var($wpdb->prepare(
                    "SELECT entity_id FROM {$entities_table} WHERE source_object_type = %s AND source_object_id = %d LIMIT 1",
                    'company',
                    $company_id
                )));

                $canonical_active = 0;
                if ($entity_id > 0) {
                    $canonical_active = intval($wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tsemits_table} WHERE entity_id = %d AND state = %s",
                        $entity_id,
                        'active'
                    )));
                }

                $comparisons[] = [
                    'company_id' => $company_id,
                    'entity_id' => $entity_id,
                    'legacy_active' => $legacy_active,
                    'canonical_active' => $canonical_active,
                    'matches' => ($legacy_active === $canonical_active),
                ];
            }
        }

        return [
            'tables' => [
                'entities_exists' => $entities_exists,
                'tsemits_exists' => $tsemits_exists,
            ],
            'counts' => [
                'entities_total' => $entities_total,
                'tsemits_total' => $tsemits_total,
            ],
            'migration_status' => $migration_status,
            'legacy_canonical_comparison' => $comparisons,
        ];
    }

    public static function canonical_entity_count() {
        if (!class_exists('\TSEMOU\Modules\EntityFoundation\Entity_Registry')) {
            return 0;
        }

        return intval(\TSEMOU\Modules\EntityFoundation\Entity_Registry::instance()->count_entities());
    }

    public static function entity_registration_supported_types() {
        if (!class_exists('\TSEMOU\Modules\EntityFoundation\Entity_Registry')) {
            return [];
        }

        return \TSEMOU\Modules\EntityFoundation\Entity_Registry::instance()->supported_source_types();
    }

    public static function entity_registration_last_status() {
        $status = get_option(self::ENTITY_REGISTRATION_STATUS_OPTION, []);
        return is_array($status) ? $status : [];
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
        $types = self::proof_first_evidence_post_types();
        if (empty($types)) return $candidates;

        $q = new \WP_Query([
            'post_type' => $types,
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
            'canonical' => self::canonical_diagnostics(),
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
            'handoff_triggered' => 'no',
            'automatic_linking_status' => 'skipped',
            'automatic_linking_reason' => 'resolver_not_executed',
            'knowledge_graph_update_status' => 'skipped',
            'knowledge_graph_update_reason' => 'automatic_linking_not_executed',
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

        if (($resolution['code'] ?? '') !== 'resolved_existing') {
            $out['automatic_linking_reason'] = 'handoff_only_for_resolved_existing';
            $out['knowledge_graph_update_reason'] = 'handoff_only_for_resolved_existing';
        }

        if (($resolution['code'] ?? '') === 'resolved_existing' && $evidence_id > 0) {
            $handoff = is_array($resolution['handoff'] ?? null) ? $resolution['handoff'] : [];
            if (!empty($handoff)) {
                $out['handoff_triggered'] = sanitize_text_field($handoff['handoff_triggered'] ?? 'yes');
                $out['automatic_linking_status'] = sanitize_key($handoff['automatic_linking_status'] ?? 'skipped');
                $out['automatic_linking_reason'] = sanitize_text_field($handoff['automatic_linking_reason'] ?? 'unknown');
                $out['knowledge_graph_update_status'] = sanitize_key($handoff['knowledge_graph_update_status'] ?? 'skipped');
                $out['knowledge_graph_update_reason'] = sanitize_text_field($handoff['knowledge_graph_update_reason'] ?? 'unknown');
                if (($handoff['knowledge_graph_update_status'] ?? '') === 'success') {
                    $out['knowledge_graph_updated'] = 'yes';
                }
            } else {
                $out['handoff_triggered'] = 'no';
                $out['automatic_linking_status'] = 'failed';
                $out['automatic_linking_reason'] = 'resolver_handoff_missing';
                $out['knowledge_graph_update_status'] = 'skipped';
                $out['knowledge_graph_update_reason'] = 'automatic_linking_failed';
            }
        }

        if ($evidence_id > 0) {
            $company_ids = \TSEMOU\Modules\EvidenceEngine\Evidence_Engine::related_company_ids($evidence_id);
            $out['companies_linked'] = count($company_ids);

            $entity_count = 0;
            foreach (['_tsemou_entity_id','entity_id'] as $key) {
                $value = get_post_meta($evidence_id, $key, true);
                if (is_numeric($value) && absint($value) > 0) $entity_count++;
            }
            $out['linked_entities'] = $entity_count;

            $out['knowledge_graph_updated'] = ($entity_count > 0 || !empty($company_ids)) ? 'yes' : 'no';
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
        $entity_registration_last = self::entity_registration_last_status();
        $entity_registration_types = self::entity_registration_supported_types();
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

            <?php $canonical_diag = self::canonical_diagnostics(10); ?>
            <div class="tsemou-grid">
                <div class="card"><div class="muted">Canonical Entities</div><div class="big"><?php echo esc_html($canonical_diag['counts']['entities_total'] ?? 0); ?></div></div>
                <div class="card"><div class="muted">Canonical TSEMITs</div><div class="big"><?php echo esc_html($canonical_diag['counts']['tsemits_total'] ?? 0); ?></div></div>
                <div class="card"><div class="muted">Entity Migration</div><div class="big"><?php echo !empty($canonical_diag['migration_status']['entity_registration']['done']) ? 'done' : 'running'; ?></div></div>
                <div class="card"><div class="muted">Vote Migration</div><div class="big"><?php echo !empty($canonical_diag['migration_status']['vote_migration']['done']) ? 'done' : 'running'; ?></div></div>
            </div>

            <h2>Canonical Entity Registry Synchronization</h2>
            <?php if (!empty($_GET['entity_registry_sync'])): ?>
                <?php $state = sanitize_key((string) $_GET['entity_registry_sync']); ?>
                <div class="notice <?php echo $state === 'ok' ? 'notice-success' : ($state === 'blocked' ? 'notice-warning' : 'notice-error'); ?> is-dismissible">
                    <p>
                        <?php
                        if ($state === 'ok') echo 'Entity registry synchronization executed.';
                        elseif ($state === 'blocked') echo 'Entity registry synchronization was blocked by guard policy.';
                        else echo 'Entity registry synchronization failed.';
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-bottom:20px;">
                <p><strong>Current Canonical Entity Count:</strong> <?php echo esc_html(self::canonical_entity_count()); ?></p>
                <p><strong>Supported Object Types:</strong> <?php echo esc_html(empty($entity_registration_types) ? 'none' : implode(', ', $entity_registration_types)); ?></p>
                <p><strong>Last Synchronization Status:</strong> <?php echo esc_html($entity_registration_last['code'] ?? 'never_run'); ?></p>
                <p><strong>Last Execution Time:</strong> <?php echo esc_html($entity_registration_last['last_execution_time'] ?? 'never'); ?></p>
                <p><strong>Steps Executed (this action):</strong> <?php echo esc_html(intval($entity_registration_last['steps_executed'] ?? 0)); ?></p>
                <p><strong>Scanned Objects:</strong> <?php echo esc_html(intval($entity_registration_last['scanned_objects'] ?? 0)); ?></p>
                <p><strong>New Entities Created:</strong> <?php echo esc_html(intval($entity_registration_last['created_entities'] ?? 0)); ?></p>
                <p><strong>Existing Entities Skipped:</strong> <?php echo esc_html(intval($entity_registration_last['existing_entities_skipped'] ?? 0)); ?></p>
                <p><strong>Failed Registrations:</strong> <?php echo esc_html(intval($entity_registration_last['failed_objects'] ?? 0)); ?></p>
                <p><strong>Final Source Type:</strong> <?php echo esc_html($entity_registration_last['final_source_type'] ?? ''); ?></p>
                <p><strong>Final Type Index:</strong> <?php echo esc_html(intval($entity_registration_last['final_type_index'] ?? 0)); ?></p>
                <p><strong>Final Offset:</strong> <?php echo esc_html(intval($entity_registration_last['final_offset'] ?? 0)); ?></p>
                <p><strong>Completion State:</strong> <?php echo esc_html($entity_registration_last['completion_state'] ?? 'not_started'); ?></p>
                <p><strong>Done:</strong> <?php echo !empty($entity_registration_last['done']) ? 'yes' : 'no'; ?></p>
                <p><strong>Safety Cap Reached:</strong> <?php echo !empty($entity_registration_last['cap_reached']) ? 'yes' : 'no'; ?></p>
                <?php if (!empty($entity_registration_last['message'])): ?>
                    <p><strong>Last Error:</strong> <?php echo esc_html($entity_registration_last['message']); ?></p>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="tsemou_run_entity_registration">
                    <?php wp_nonce_field('tsemou_run_entity_registration', 'tsemou_entity_registration_nonce'); ?>
                    <button type="submit" class="button button-primary">Synchronize Entity Registry</button>
                </form>
            </div>

            <h2>Canonical vs Legacy Vote Comparison</h2>
            <table class="widefat striped">
                <thead><tr><th>Company ID</th><th>Entity ID</th><th>Legacy Active</th><th>Canonical Active</th><th>Match</th></tr></thead>
                <tbody>
                <?php if (empty($canonical_diag['legacy_canonical_comparison'])): ?>
                    <tr><td colspan="5">No comparison data available yet.</td></tr>
                <?php endif; ?>
                <?php foreach (($canonical_diag['legacy_canonical_comparison'] ?? []) as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row['company_id']); ?></td>
                        <td><?php echo esc_html($row['entity_id']); ?></td>
                        <td><?php echo esc_html($row['legacy_active']); ?></td>
                        <td><?php echo esc_html($row['canonical_active']); ?></td>
                        <td class="<?php echo !empty($row['matches']) ? 'ok' : 'warn'; ?>"><?php echo !empty($row['matches']) ? 'yes' : 'no'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

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
                            <tr><th>Linked Entities</th><td><?php echo esc_html($post_bridge_diag['linked_entities']); ?></td></tr>
                            <tr><th>Companies Linked</th><td><?php echo esc_html($post_bridge_diag['companies_linked']); ?></td></tr>
                            <tr><th>Handoff Triggered</th><td><?php echo esc_html($post_bridge_diag['handoff_triggered']); ?></td></tr>
                            <tr><th>Automatic Linking Status</th><td><?php echo esc_html($post_bridge_diag['automatic_linking_status']); ?></td></tr>
                            <tr><th>Automatic Linking Reason</th><td><?php echo esc_html($post_bridge_diag['automatic_linking_reason']); ?></td></tr>
                            <tr><th>Knowledge Graph Update Status</th><td><?php echo esc_html($post_bridge_diag['knowledge_graph_update_status']); ?></td></tr>
                            <tr><th>Knowledge Graph Update Reason</th><td><?php echo esc_html($post_bridge_diag['knowledge_graph_update_reason']); ?></td></tr>
                            <tr><th>Knowledge Graph Updated</th><td><?php echo esc_html($post_bridge_diag['knowledge_graph_updated']); ?></td></tr>
                            <tr><th>Error</th><td><?php echo esc_html($post_bridge_diag['error']); ?></td></tr>
                        </tbody>
                    </table>
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
