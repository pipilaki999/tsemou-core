<?php
namespace TSEMOU\Modules\ScrapingEngine;

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/class-fetcher.php';
require_once __DIR__ . '/class-parser.php';
require_once __DIR__ . '/class-normalizer.php';
require_once __DIR__ . '/class-storage.php';

class Scraping_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 28);
    }

    public static function process(array $job) {
        $fetch = Fetcher::fetch($job);
        $parsed = Parser::parse($fetch, $job);
        $record = Normalizer::normalize($job, $fetch, $parsed);
        $raw_id = Storage::save($record);

        return [
            'success' => true,
            'raw_id' => $raw_id,
            'record' => $record,
            'message' => !empty($fetch['ok'])
                ? 'Scraping Engine completed. Raw Evidence created.'
                : 'Scraping Engine completed with fetch warning. Raw Evidence placeholder created.',
        ];
    }

    public static function raw_records() {
        return Storage::all();
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Scraping Engine',
            'Scraping Engine',
            'manage_options',
            'tsemou-scraping-engine',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        if (!empty($_POST['tsemou_scraping_nonce']) && wp_verify_nonce($_POST['tsemou_scraping_nonce'], 'tsemou_scraping_action')) {
            $action = sanitize_text_field($_POST['scraping_action'] ?? '');
            if ($action === 'clear_raw') {
                Storage::clear();
                echo '<div class="notice notice-warning"><p>Raw Evidence runtime files cleared.</p></div>';
            }
        }

        $records = array_reverse(self::raw_records());
        ?>
        <div class="wrap tsemou-scraping-engine">
            <h1>TSEMOU Scraping Engine</h1>
            <p><strong>Milestone 3.0.5:</strong> controlled acquisition engine. Creates Raw Evidence in file runtime storage only. No database writes, no AI, no Trust, no scoring.</p>

            <form method="post" style="margin:12px 0 18px;">
                <?php wp_nonce_field('tsemou_scraping_action', 'tsemou_scraping_nonce'); ?>
                <button class="button" name="scraping_action" value="clear_raw">Clear Raw Evidence Records</button>
            </form>

            <h2>Raw Evidence Records</h2>
            <table class="widefat striped">
                <thead><tr><th>Raw ID</th><th>Status</th><th>Source</th><th>HTTP</th><th>Title</th><th>Created</th></tr></thead>
                <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="6">No Raw Evidence records yet. Run a scraping_engine queue item from Discovery Orchestrator.</td></tr>
                <?php endif; ?>
                <?php foreach (array_slice($records, 0, 80) as $record): ?>
                    <tr>
                        <td><code><?php echo esc_html($record['raw_id'] ?? ''); ?></code></td>
                        <td><?php echo esc_html($record['status'] ?? ''); ?></td>
                        <td><strong><?php echo esc_html($record['source_label'] ?? ''); ?></strong><br><code><?php echo esc_html($record['source_url'] ?? ''); ?></code></td>
                        <td><?php echo esc_html($record['metadata']['http_status'] ?? 0); ?></td>
                        <td><?php echo esc_html($record['title'] ?? ''); ?></td>
                        <td><?php echo esc_html($record['created_at'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
