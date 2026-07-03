<?php
namespace TSEMOU\Modules\AutomaticEvidenceCreation;

if (!defined('ABSPATH')) exit;

class Automatic_Evidence_Creation {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 29);
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Automatic Evidence',
            'Automatic Evidence',
            'manage_options',
            'tsemou-automatic-evidence',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        $drafts = self::drafts();
        echo '<div class="wrap"><h1>TSEMOU Automatic Evidence Creation</h1>';
        echo '<p><strong>Milestone 3.0.8:</strong> safe runtime evidence draft storage. No permanent database write.</p>';
        echo '<table class="widefat striped"><thead><tr><th>Draft ID</th><th>Title</th><th>Source</th><th>Status</th><th>Created</th></tr></thead><tbody>';
        if (empty($drafts)) {
            echo '<tr><td colspan="5">No evidence drafts yet.</td></tr>';
        }
        foreach ($drafts as $draft) {
            echo '<tr>';
            echo '<td><code>' . esc_html($draft['id'] ?? '') . '</code></td>';
            echo '<td>' . esc_html($draft['title'] ?? '') . '</td>';
            echo '<td>' . esc_html($draft['source'] ?? '') . '</td>';
            echo '<td>' . esc_html($draft['status'] ?? '') . '</td>';
            echo '<td>' . esc_html($draft['retrieved_at'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }



    public static function process($payload = []) {
        try {
            if (!is_array($payload)) $payload = [];

            $raw = $payload;
            $raw_id = self::safe_text($payload['raw_id'] ?? '');
            if ($raw_id && class_exists('\TSEMOU\Modules\ScrapingEngine\Scraping_Engine')) {
                $records = \TSEMOU\Modules\ScrapingEngine\Scraping_Engine::raw_records();
                if (is_array($records)) {
                    foreach ($records as $record) {
                        if (is_array($record) && (($record['raw_id'] ?? '') === $raw_id)) {
                            $raw = array_merge($payload, $record);
                            break;
                        }
                    }
                }
            }

            $draft = self::create_draft($raw);
            return [
                'success' => !empty($draft['id']),
                'draft_id' => $draft['id'] ?? '',
                'draft' => $draft,
                'message' => !empty($draft['id']) ? 'Automatic Evidence Creation completed. Evidence Draft stored safely.' : 'Automatic Evidence Creation failed to create draft.',
            ];
        } catch (\Throwable $e) {
            self::add_log('automatic_evidence_error', 'Automatic Evidence Creation recovered from fatal-risk error.', [
                'error' => $e->getMessage(),
                'raw_id' => is_array($payload) ? self::safe_text($payload['raw_id'] ?? '') : '',
            ]);
            return [
                'success' => false,
                'message' => 'Automatic Evidence Creation error safely caught: ' . $e->getMessage(),
            ];
        }
    }

    public static function safe_text($value) {
        if (is_array($value) || is_object($value)) {
            $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return sanitize_text_field((string) $value);
    }

    public static function first_text($raw, $keys, $fallback = '') {
        if (!is_array($raw)) return self::safe_text($fallback);
        foreach ((array) $keys as $key) {
            if (isset($raw[$key]) && $raw[$key] !== '') {
                return self::safe_text($raw[$key]);
            }
        }
        return self::safe_text($fallback);
    }

    public static function first_url($raw, $keys) {
        if (!is_array($raw)) return '';
        foreach ((array) $keys as $key) {
            if (!empty($raw[$key]) && !is_array($raw[$key]) && !is_object($raw[$key])) {
                $url = esc_url_raw((string) $raw[$key]);
                if ($url) return $url;
            }
        }
        return '';
    }

    public static function add_log($action, $message, $context = []) {
        $logs = get_option('tsemou_automatic_evidence_logs', []);
        if (!is_array($logs)) $logs = [];
        array_unshift($logs, [
            'time' => current_time('mysql'),
            'action' => sanitize_key((string) $action),
            'message' => sanitize_text_field((string) $message),
            'context' => is_array($context) ? $context : [],
        ]);
        update_option('tsemou_automatic_evidence_logs', array_slice($logs, 0, 200), false);
    }

    public static function get_draft($draft_id) {
        $draft_id = sanitize_file_name((string) $draft_id);
        if ($draft_id === '') return null;
        $file = self::storage_dir() . $draft_id . '.json';
        if (!is_file($file)) return null;
        $json = json_decode((string) file_get_contents($file), true);
        return is_array($json) ? $json : null;
    }

    public static function create_draft($raw = []) {
        if (!is_array($raw)) $raw = [];
        $id = 'ev_' . gmdate('YmdHis') . '_' . wp_generate_password(6, false, false);

        $source_url = self::first_url($raw, ['source_url', 'url', 'resolved_url', 'candidate_url']);
        $source_label = self::first_text($raw, ['source_label', 'source', 'source_id', 'source_type'], 'unknown_source');
        $company = self::first_text($raw, ['company', 'company_name', 'company_label', 'target_company'], '');
        $title = self::first_text($raw, ['title', 'raw_title', 'source_label'], $source_label);

        $draft = [
            'id' => $id,
            'raw_id' => self::first_text($raw, ['raw_id'], ''),
            'company' => $company,
            'country' => self::first_text($raw, ['country_name', 'country'], ''),
            'industry' => self::first_text($raw, ['industry_label', 'industry'], ''),
            'source' => $source_label,
            'source_url' => $source_url,
            'url' => $source_url,
            'title' => $title,
            'retrieved_at' => current_time('mysql'),
            'language' => self::first_text($raw, ['language'], ''),
            'status' => 'draft',
            'hash' => md5((string) wp_json_encode($raw)),
            'metadata' => [
                'http_status' => intval($raw['http_status'] ?? ($raw['metadata']['http_status'] ?? 0)),
                'raw_status' => self::first_text($raw, ['raw_status', 'status'], ''),
                'storage_mode' => 'runtime_file',
                'version' => '3.0.11',
            ],
        ];

        $dir = self::storage_dir();
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        if (is_dir($dir) && is_writable($dir)) {
            $written = @file_put_contents($dir . $id . '.json', wp_json_encode($draft, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if ($written === false) {
                self::add_log('automatic_evidence_write_failed', 'Evidence draft file could not be written.', ['draft_id' => $id, 'dir' => $dir]);
                $draft['write_error'] = true;
            }
        } else {
            self::add_log('automatic_evidence_storage_unwritable', 'Evidence draft storage directory is not writable.', ['dir' => $dir]);
            $draft['write_error'] = true;
        }
        return $draft;
    }

    public static function drafts() {
        $dir = self::storage_dir();
        if (!is_dir($dir)) return [];
        $items = [];
        foreach (glob($dir . '*.json') ?: [] as $file) {
            $json = json_decode((string) file_get_contents($file), true);
            if (is_array($json)) $items[] = $json;
        }
        return array_reverse($items);
    }

    public static function storage_dir() {
        return trailingslashit(TSEMOU_CORE_PATH) . 'storage/runtime/evidence/';
    }
}
