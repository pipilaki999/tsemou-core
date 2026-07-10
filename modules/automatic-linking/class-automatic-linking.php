<?php
namespace TSEMOU\Modules\AutomaticLinking;

if (!defined('ABSPATH')) exit;

/**
 * TSEMOU Automatic Linking v3.0.10
 *
 * Phase A bridge: Evidence Draft -> Company/Entity -> Source Object -> Knowledge Graph candidate.
 * Safe rules:
 * - no public/frontend output changes
 * - no Trust/Reputation/AI scoring
 * - no permanent Evidence post creation
 * - stores runtime link records first
 */
class Automatic_Linking {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 29);
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Automatic Linking',
            'Automatic Linking',
            'manage_options',
            'tsemou-automatic-linking',
            [$this, 'render_admin_page']
        );
    }

    public static function storage_dir() {
        return trailingslashit(TSEMOU_CORE_PATH) . 'storage/runtime/links/';
    }

    private static function ensure_storage() {
        $dir = self::storage_dir();
        if (!file_exists($dir)) wp_mkdir_p($dir);
        return is_dir($dir) && is_writable($dir);
    }

    public static function link_records() {
        $dir = self::storage_dir();
        if (!is_dir($dir)) return [];
        $items = [];
        foreach (glob($dir . '*.json') ?: [] as $file) {
            $json = json_decode((string) file_get_contents($file), true);
            if (is_array($json)) $items[] = $json;
        }
        usort($items, function($a, $b) {
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });
        return $items;
    }

    public static function get_link_record($link_id) {
        $link_id = sanitize_file_name((string) $link_id);
        if ($link_id === '') return null;
        $file = self::storage_dir() . $link_id . '.json';
        if (!is_file($file)) return null;
        $json = json_decode((string) file_get_contents($file), true);
        return is_array($json) ? $json : null;
    }

    public static function clear_records() {
        $dir = self::storage_dir();
        if (!is_dir($dir)) return 0;
        $count = 0;
        foreach (glob($dir . '*.json') ?: [] as $file) {
            if (@unlink($file)) $count++;
        }
        return $count;
    }

    public static function find_company($payload = []) {
        if (!is_array($payload)) $payload = [];
        $id = absint($payload['company_id'] ?? $payload['entity_id'] ?? 0);
        if ($id && get_post_type($id) === 'company') return $id;

        $name = '';
        foreach (['company', 'company_name', 'company_label', 'target_company', 'title'] as $key) {
            if (!empty($payload[$key]) && !is_array($payload[$key])) {
                $name = sanitize_text_field((string) $payload[$key]);
                break;
            }
        }
        if (!$name) return 0;

        $exact = get_page_by_title($name, OBJECT, 'company');
        if ($exact && !empty($exact->ID)) return absint($exact->ID);

        $q = new \WP_Query([
            'post_type' => 'company',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            's' => $name,
            'orderby' => 'modified',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);
        return !empty($q->posts) ? absint($q->posts[0]) : 0;
    }

    public static function find_source($payload = []) {
        if (!is_array($payload)) $payload = [];
        $source_id = absint($payload['source_id'] ?? 0);
        if ($source_id && get_post_type($source_id) === 'tsemou_source') return $source_id;

        $url = '';
        foreach (['source_url', 'url', 'source', 'resolved_url', 'candidate_url'] as $key) {
            if (!empty($payload[$key]) && !is_array($payload[$key])) {
                $url = esc_url_raw((string) $payload[$key]);
                if (!$url) $url = sanitize_text_field((string) $payload[$key]);
                break;
            }
        }

        if (!$url) return 0;

        if (class_exists('\TSEMOU\Modules\SourceObject\Source_Object_Engine')) {
            return absint(\TSEMOU\Modules\SourceObject\Source_Object_Engine::find_or_create_from_url($url));
        }
        return 0;
    }

    public static function evidence_id_from_payload($payload = []) {
        $id = absint($payload['evidence_id'] ?? 0);
        if ($id && in_array(get_post_type($id), ['evidence', 'tsemou_proof'], true)) return $id;
        return 0;
    }

    private static function sync_evidence_company_relationship($evidence_id, $company_id) {
        $evidence_id = absint($evidence_id);
        $company_id = absint($company_id);

        if ($evidence_id <= 0 || $company_id <= 0) return;

        $company_ids = [$company_id];

        update_post_meta($evidence_id, '_tsemou_company_id', $company_id);
        update_post_meta($evidence_id, '_tsemou_entity_id', $company_id);
        update_post_meta($evidence_id, '_tsemou_evidence_company_ids', $company_ids);
        update_post_meta($evidence_id, '_tsemou_evidence_company', $company_id);
        update_post_meta($evidence_id, '_tsemou_related_company', $company_id);
        update_post_meta($evidence_id, 'related_company', $company_id);
        update_post_meta($evidence_id, 'company', $company_ids);

        if (class_exists('\TSEMOU\Modules\ProofEngine\Proof_Engine')) {
            \TSEMOU\Modules\ProofEngine\Proof_Engine::instance()->sync_evidence_to_companies($evidence_id);
        }
    }

    public static function process($payload = []) {
        if (!is_array($payload)) $payload = [];

        $draft = null;
        $draft_id = sanitize_text_field($payload['draft_id'] ?? '');
        if ($draft_id && class_exists('\TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation')) {
            $draft = \TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation::get_draft($draft_id);
            if (is_array($draft)) {
                $payload = array_merge($draft, $payload);
            }
        }

        $company_id = self::find_company($payload);
        $source_id = self::find_source($payload);
        $evidence_id = self::evidence_id_from_payload($payload);

        if ($evidence_id && $source_id) {
            update_post_meta($evidence_id, '_tsemou_source_id', $source_id);
        }

        if ($evidence_id && $company_id) {
            self::sync_evidence_company_relationship($evidence_id, $company_id);
        }

        if ($evidence_id && $company_id && class_exists('\TSEMOU\Modules\EntityEvidenceLinks\Entity_Evidence_Links')) {
            \TSEMOU\Modules\EntityEvidenceLinks\Entity_Evidence_Links::add_link(
                $evidence_id,
                $company_id,
                'primary_target',
                80,
                'Created by Automatic Linking v3.0.10.'
            );
        }

        $link_id = 'alink_' . substr(sha1(wp_json_encode([
            'draft_id' => $draft_id,
            'raw_id' => $payload['raw_id'] ?? '',
            'evidence_id' => $evidence_id,
            'company_id' => $company_id,
            'source_id' => $source_id,
        ])), 0, 18);

        $record = [
            'link_id' => $link_id,
            'draft_id' => $draft_id,
            'raw_id' => sanitize_text_field($payload['raw_id'] ?? ''),
            'evidence_id' => $evidence_id,
            'company_id' => $company_id,
            'company_title' => $company_id ? get_the_title($company_id) : '',
            'source_id' => $source_id,
            'source_title' => $source_id ? get_the_title($source_id) : '',
            'source_url' => esc_url_raw((string)($payload['source_url'] ?? $payload['url'] ?? '')),
            'status' => ($company_id || $source_id || $evidence_id) ? 'candidate_link_ready' : 'needs_review',
            'mode' => $evidence_id ? 'evidence_post_link' : 'runtime_draft_link',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'notes' => 'Phase A automatic linking. No Trust, Reputation or AI scoring applied.',
        ];

        if (self::ensure_storage()) {
            file_put_contents(self::storage_dir() . $link_id . '.json', wp_json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        self::add_log('automatic_linking_completed', 'Automatic Linking created candidate link record.', $record);

        return [
            'success' => true,
            'link_id' => $link_id,
            'record' => $record,
            'message' => 'Automatic Linking completed. Candidate links ready.',
        ];
    }

    public static function update_knowledge_graph($payload = []) {
        if (!is_array($payload)) $payload = [];
        $link_id = sanitize_text_field($payload['link_id'] ?? '');
        $record = $link_id ? self::get_link_record($link_id) : null;
        if (is_array($record)) {
            $payload = array_merge($payload, $record);
        }

        $company_id = absint($payload['company_id'] ?? 0);
        $source_id = absint($payload['source_id'] ?? 0);
        if (!$company_id || !$source_id) {
            return [
                'success' => true,
                'updated' => false,
                'message' => 'Knowledge Graph update skipped: company or source missing; link remains for review.',
            ];
        }

        if (!class_exists('\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph')) {
            return [
                'success' => false,
                'updated' => false,
                'message' => 'Knowledge Graph engine is not loaded.',
            ];
        }

        $rel = \TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph::add_relationship(
            $company_id,
            $source_id,
            'reported_by',
            70,
            ['context'],
            'Created by Phase A Automatic Linking v3.0.10. Context only; not a trust verdict.',
            'active'
        );

        self::add_log('knowledge_graph_updated', 'Knowledge Graph context relation updated from automatic link.', [
            'link_id' => $link_id,
            'relationship_id' => $rel['relationship_id'] ?? '',
            'company_id' => $company_id,
            'source_id' => $source_id,
        ]);

        return [
            'success' => true,
            'updated' => true,
            'relationship_id' => $rel['relationship_id'] ?? '',
            'message' => 'Knowledge Graph context relation updated.',
        ];
    }

    public static function add_log($action, $message, $context = []) {
        $logs = get_option('tsemou_automatic_linking_logs', []);
        if (!is_array($logs)) $logs = [];
        array_unshift($logs, [
            'time' => current_time('mysql'),
            'action' => sanitize_key($action),
            'message' => sanitize_text_field($message),
            'context' => is_array($context) ? $context : [],
        ]);
        update_option('tsemou_automatic_linking_logs', array_slice($logs, 0, 200), false);
    }

    public static function logs() {
        $logs = get_option('tsemou_automatic_linking_logs', []);
        return is_array($logs) ? $logs : [];
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        if (!empty($_POST['tsemou_auto_linking_nonce']) && wp_verify_nonce($_POST['tsemou_auto_linking_nonce'], 'tsemou_auto_linking_action')) {
            $action = sanitize_text_field($_POST['auto_linking_action'] ?? '');
            if ($action === 'clear_links') {
                $cleared = self::clear_records();
                echo '<div class="notice notice-warning"><p>Automatic Linking records cleared: ' . esc_html($cleared) . '</p></div>';
            }
        }

        $records = self::link_records();
        $logs = self::logs();
        ?>
        <div class="wrap">
            <h1>TSEMOU Automatic Linking</h1>
            <p><strong>Milestone 3.0.10:</strong> connects runtime Evidence Drafts with Company, Source Object and Knowledge Graph context. No Trust, Reputation or AI scoring.</p>

            <form method="post" style="margin:12px 0 18px;">
                <?php wp_nonce_field('tsemou_auto_linking_action', 'tsemou_auto_linking_nonce'); ?>
                <button class="button" name="auto_linking_action" value="clear_links">Clear Runtime Link Records</button>
            </form>

            <h2>Status</h2>
            <table class="widefat striped" style="max-width:760px;"><tbody>
                <tr><th>Engine class</th><td>loaded</td></tr>
                <tr><th>Runtime link records</th><td><?php echo esc_html(count($records)); ?></td></tr>
                <tr><th>Storage</th><td><code><?php echo esc_html(str_replace(TSEMOU_CORE_PATH, '', self::storage_dir())); ?></code></td></tr>
            </tbody></table>

            <h2>Runtime Link Records</h2>
            <table class="widefat striped">
                <thead><tr><th>Link ID</th><th>Draft / Evidence</th><th>Company</th><th>Source</th><th>Status</th><th>Created</th></tr></thead>
                <tbody>
                <?php if (empty($records)): ?><tr><td colspan="6">No automatic link records yet. Run Phase A pipeline through Automatic Evidence → Automatic Linking.</td></tr><?php endif; ?>
                <?php foreach (array_slice($records, 0, 80) as $record): ?>
                    <tr>
                        <td><code><?php echo esc_html($record['link_id'] ?? ''); ?></code></td>
                        <td><?php echo esc_html($record['draft_id'] ?: ('Evidence #' . ($record['evidence_id'] ?? ''))); ?></td>
                        <td><?php echo esc_html(($record['company_title'] ?? '') ?: '—'); ?><?php if (!empty($record['company_id'])): ?> <code>#<?php echo esc_html($record['company_id']); ?></code><?php endif; ?></td>
                        <td><?php echo esc_html(($record['source_title'] ?? '') ?: ($record['source_url'] ?? '—')); ?><?php if (!empty($record['source_id'])): ?> <code>#<?php echo esc_html($record['source_id']); ?></code><?php endif; ?></td>
                        <td><strong><?php echo esc_html($record['status'] ?? ''); ?></strong><br><small><?php echo esc_html($record['mode'] ?? ''); ?></small></td>
                        <td><?php echo esc_html($record['created_at'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Logs</h2>
            <table class="widefat striped"><thead><tr><th>Time</th><th>Action</th><th>Message</th><th>Context</th></tr></thead><tbody>
                <?php if (empty($logs)): ?><tr><td colspan="4">No logs yet.</td></tr><?php endif; ?>
                <?php foreach (array_slice($logs, 0, 80) as $log): ?>
                    <tr><td><?php echo esc_html($log['time'] ?? ''); ?></td><td><?php echo esc_html($log['action'] ?? ''); ?></td><td><?php echo esc_html($log['message'] ?? ''); ?></td><td><code><?php echo esc_html(wp_json_encode($log['context'] ?? [])); ?></code></td></tr>
                <?php endforeach; ?>
            </tbody></table>
        </div>
        <?php
    }
}
