<?php
namespace TSEMOU\Modules\SourceDiscovery;

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/class-source-registry.php';
require_once __DIR__ . '/class-source-resolver.php';
require_once __DIR__ . '/class-source-prioritizer.php';
require_once __DIR__ . '/class-source-queue-builder.php';

class Source_Discovery {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 27);
    }

    public static function discover(array $task) {
        $sources = Source_Resolver::resolve($task);
        return [
            'sources' => $sources,
            'payloads' => Source_Queue_Builder::build_payloads($task, $sources),
            'count' => count($sources),
        ];
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Source Discovery',
            'Source Discovery',
            'manage_options',
            'tsemou-source-discovery',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        $sample = [
            'country' => sanitize_text_field($_GET['country'] ?? 'US'),
            'country_name' => sanitize_text_field($_GET['country_name'] ?? 'United States'),
            'industry' => sanitize_key($_GET['industry'] ?? 'technology'),
            'industry_label' => sanitize_text_field($_GET['industry_label'] ?? 'Technology'),
            'wave' => sanitize_key($_GET['wave'] ?? 'wave_1'),
            'rank_band' => sanitize_text_field($_GET['rank_band'] ?? 'top_1_50'),
            'priority' => intval($_GET['priority'] ?? 300),
        ];

        $result = self::discover($sample);
        ?>
        <div class="wrap tsemou-source-discovery">
            <h1>TSEMOU Source Discovery</h1>
            <p><strong>Milestone 3.0.3:</strong> resolves candidate sources and prepares source fetch jobs. No scraping yet.</p>

            <h2>Sample Task</h2>
            <table class="widefat striped" style="max-width:800px;"><tbody>
                <tr><th>Country</th><td><?php echo esc_html($sample['country_name']); ?></td></tr>
                <tr><th>Industry</th><td><?php echo esc_html($sample['industry_label']); ?></td></tr>
                <tr><th>Wave</th><td><?php echo esc_html($sample['wave']); ?></td></tr>
                <tr><th>Rank Band</th><td><?php echo esc_html($sample['rank_band']); ?></td></tr>
                <tr><th>Resolved Sources</th><td><?php echo esc_html($result['count']); ?></td></tr>
            </tbody></table>

            <h2>Resolved Candidate Sources</h2>
            <table class="widefat striped">
                <thead><tr><th>Source</th><th>Type</th><th>Priority</th><th>URL</th><th>Description</th></tr></thead>
                <tbody>
                <?php foreach ($result['sources'] as $source): ?>
                    <tr>
                        <td><strong><?php echo esc_html($source['source_label']); ?></strong><br><code><?php echo esc_html($source['source_id']); ?></code></td>
                        <td><?php echo esc_html($source['source_type']); ?></td>
                        <td><?php echo esc_html($source['priority']); ?></td>
                        <td><code><?php echo esc_html($source['source_url']); ?></code></td>
                        <td><?php echo esc_html($source['source_description']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
