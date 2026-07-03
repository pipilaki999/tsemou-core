<?php
namespace TSEMOU\Modules\SourceObject;

if (!defined('ABSPATH')) exit;

/**
 * TSEMOU Source Object Engine v2.8.0
 *
 * Converts raw evidence URLs into reusable Source Objects.
 * No scraping, no AI, no external lookup.
 */
class Source_Object_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('admin_menu', [$this, 'admin_menu'], 28);
    }

    public function register_post_type() {
        register_post_type('tsemou_source', [
            'labels' => [
                'name' => 'TSEMOU Sources',
                'singular_name' => 'TSEMOU Source',
                'add_new_item' => 'Add Source',
                'edit_item' => 'Edit Source',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'custom-fields'],
            'capability_type' => 'post',
        ]);
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Source Object Engine',
            'Source Object Engine',
            'manage_options',
            'tsemou-source-object-engine',
            [$this, 'render_admin_page']
        );
    }

    public static function normalize_domain($input) {
        $input = trim((string) $input);
        if ($input === '') return '';

        if (strpos($input, '://') === false && strpos($input, '.') !== false) {
            $input = 'https://' . $input;
        }

        $host = parse_url($input, PHP_URL_HOST);
        if (!$host) $host = $input;

        $host = strtolower(trim($host));
        $host = preg_replace('/^www\./', '', $host);
        $host = preg_replace('/:\d+$/', '', $host);

        return sanitize_text_field($host);
    }

    public static function source_intelligence($url_or_domain) {
        if (class_exists('\TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine')) {
            return \TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine::analyze($url_or_domain);
        }

        $domain = self::normalize_domain($url_or_domain);

        return [
            'domain' => $domain,
            'publisher' => $domain ?: 'Unknown Source',
            'type' => 'Unknown Source',
            'country' => 'Unknown',
            'credibility' => null,
            'credibility_label' => 'Not rated',
            'verified' => false,
            'class' => 'unknown',
            'risk' => 'unknown',
            'warnings' => ['source_intelligence_engine_missing'],
        ];
    }

    public static function find_source_by_domain($domain) {
        $domain = self::normalize_domain($domain);
        if (!$domain) return 0;

        $sources = get_posts([
            'post_type' => 'tsemou_source',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'numberposts' => 1,
            'meta_key' => '_tsemou_source_domain',
            'meta_value' => $domain,
        ]);

        return !empty($sources) ? absint($sources[0]->ID) : 0;
    }

    public static function create_source($url_or_domain, $args = []) {
        $domain = self::normalize_domain($url_or_domain);
        if (!$domain) return new \WP_Error('missing_domain', 'Source domain is required.');

        $existing = self::find_source_by_domain($domain);
        if ($existing) return $existing;

        $intel = self::source_intelligence($url_or_domain);
        $publisher = !empty($args['publisher']) ? sanitize_text_field($args['publisher']) : ($intel['publisher'] ?? $domain);

        $source_id = wp_insert_post([
            'post_type' => 'tsemou_source',
            'post_status' => 'publish',
            'post_title' => $publisher ?: $domain,
            'post_content' => 'TSEMOU Source Object for ' . $domain,
        ], true);

        if (is_wp_error($source_id)) return $source_id;

        update_post_meta($source_id, '_tsemou_source_domain', $domain);
        update_post_meta($source_id, '_tsemou_source_publisher', $publisher);
        update_post_meta($source_id, '_tsemou_source_type', sanitize_text_field($intel['type'] ?? 'Unknown Source'));
        update_post_meta($source_id, '_tsemou_source_country', sanitize_text_field($intel['country'] ?? 'Unknown'));
        update_post_meta($source_id, '_tsemou_source_class', sanitize_text_field($intel['class'] ?? 'unknown'));
        update_post_meta($source_id, '_tsemou_source_risk', sanitize_text_field($intel['risk'] ?? 'unknown'));
        update_post_meta($source_id, '_tsemou_source_verified', !empty($intel['verified']) ? '1' : '0');

        if (is_numeric($intel['credibility'] ?? null)) {
            update_post_meta($source_id, '_tsemou_source_credibility', floatval($intel['credibility']));
        }

        update_post_meta($source_id, '_tsemou_source_intelligence', $intel);
        update_post_meta($source_id, '_tsemou_source_created_by_engine', '2.8.0');
        update_post_meta($source_id, '_tsemou_source_last_seen', current_time('mysql'));

        return absint($source_id);
    }

    public static function find_or_create_from_url($url) {
        $domain = self::normalize_domain($url);
        if (!$domain) return 0;

        $existing = self::find_source_by_domain($domain);
        if ($existing) {
            update_post_meta($existing, '_tsemou_source_last_seen', current_time('mysql'));
            return $existing;
        }

        $created = self::create_source($url);
        return is_wp_error($created) ? 0 : absint($created);
    }

    public static function get_source($source_id) {
        $source_id = absint($source_id);
        $post = get_post($source_id);

        if (!$post || $post->post_type !== 'tsemou_source') return null;

        $cred = get_post_meta($source_id, '_tsemou_source_credibility', true);
        $domain = get_post_meta($source_id, '_tsemou_source_domain', true);

        return [
            'source_id' => $source_id,
            'title' => get_the_title($source_id),
            'domain' => $domain,
            'publisher' => get_post_meta($source_id, '_tsemou_source_publisher', true) ?: get_the_title($source_id),
            'type' => get_post_meta($source_id, '_tsemou_source_type', true),
            'country' => get_post_meta($source_id, '_tsemou_source_country', true),
            'class' => get_post_meta($source_id, '_tsemou_source_class', true),
            'risk' => get_post_meta($source_id, '_tsemou_source_risk', true),
            'verified' => get_post_meta($source_id, '_tsemou_source_verified', true) === '1',
            'credibility' => is_numeric($cred) ? floatval($cred) : null,
            'credibility_label' => is_numeric($cred) ? number_format(floatval($cred), 1) . '/10' : 'Not rated',
            'last_seen' => get_post_meta($source_id, '_tsemou_source_last_seen', true),
            'permalink' => get_edit_post_link($source_id, ''),
            'status' => get_post_status($source_id),
        ];
    }

    public static function source_from_evidence($evidence_id, $create = true) {
        $evidence_id = absint($evidence_id);
        if (!$evidence_id) return null;

        $source_id = absint(get_post_meta($evidence_id, '_tsemou_source_id', true));
        if ($source_id && get_post_type($source_id) === 'tsemou_source') {
            return self::get_source($source_id);
        }

        $url = '';
        foreach (['_tsemou_source_url', 'tsemou_source_url', 'source_url', 'url', 'evidence_url', '_source_url', 'link', 'external_url'] as $key) {
            $value = get_post_meta($evidence_id, $key, true);
            if (!empty($value) && !is_array($value)) {
                $url = esc_url_raw((string) $value);
                break;
            }
        }

        if (!$url) return null;

        if ($create) {
            $source_id = self::find_or_create_from_url($url);
            if ($source_id) {
                update_post_meta($evidence_id, '_tsemou_source_id', $source_id);
                return self::get_source($source_id);
            }
        }

        return null;
    }

    public static function attach_evidence_to_source($evidence_id) {
        $source = self::source_from_evidence($evidence_id, true);
        return $source ? absint($source['source_id']) : 0;
    }

    public static function evidence_count_for_source($source_id) {
        $source_id = absint($source_id);
        if (!$source_id) return 0;

        $query = new \WP_Query([
            'post_type' => ['evidence', 'tsemou_proof'],
            'post_status' => ['publish', 'draft', 'pending'],
            'fields' => 'ids',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_tsemou_source_id',
                    'value' => $source_id,
                    'compare' => '=',
                ]
            ]
        ]);

        return intval($query->found_posts);
    }

    public static function recent_sources($limit = 20) {
        $sources = get_posts([
            'post_type' => 'tsemou_source',
            'post_status' => ['publish', 'draft', 'pending'],
            'numberposts' => intval($limit),
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);

        $out = [];
        foreach ($sources as $source) {
            $item = self::get_source($source->ID);
            if ($item) {
                $item['evidence_count'] = self::evidence_count_for_source($source->ID);
                $out[] = $item;
            }
        }

        return $out;
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        $url = isset($_GET['source_url']) ? sanitize_text_field($_GET['source_url']) : '';
        $evidence_id = isset($_GET['evidence_id']) ? absint($_GET['evidence_id']) : 0;
        $source_id = isset($_GET['source_id']) ? absint($_GET['source_id']) : 0;

        $url_output = null;
        $evidence_output = null;
        $source_output = null;

        if ($url) {
            $created_id = self::find_or_create_from_url($url);
            $url_output = [
                'input' => $url,
                'domain' => self::normalize_domain($url),
                'source_id' => $created_id,
                'source' => $created_id ? self::get_source($created_id) : null,
                'intelligence' => self::source_intelligence($url),
            ];
        }

        if ($evidence_id) {
            $attached = self::attach_evidence_to_source($evidence_id);
            $evidence_output = [
                'evidence_id' => $evidence_id,
                'attached_source_id' => $attached,
                'source' => $attached ? self::get_source($attached) : null,
            ];
        }

        if ($source_id) {
            $source_output = self::get_source($source_id);
        }
        ?>
        <div class="wrap">
            <h1>TSEMOU Source Object Engine</h1>
            <p>v2.8.0 reusable Source Objects for Evidence, Discovery and future Trust scoring.</p>

            <form method="get" style="background:#fff;border:1px solid #dbe3ef;padding:16px;border-radius:14px;margin-bottom:20px;">
                <input type="hidden" name="page" value="tsemou-source-object-engine">

                <label><strong>Source URL / Domain</strong></label>
                <input type="text" name="source_url" value="<?php echo esc_attr($url); ?>" placeholder="https://www.reuters.com/..." style="width:340px;max-width:100%;">

                <label style="margin-left:12px;"><strong>Evidence ID</strong></label>
                <input type="number" name="evidence_id" value="<?php echo esc_attr($evidence_id ?: ''); ?>" style="width:120px;">

                <label style="margin-left:12px;"><strong>Source ID</strong></label>
                <input type="number" name="source_id" value="<?php echo esc_attr($source_id ?: ''); ?>" style="width:120px;">

                <button class="button button-primary">Inspect</button>
            </form>

            <h2>Engine Status</h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th>Engine class</th><td>loaded</td></tr>
                    <tr><th>Post type</th><td>tsemou_source</td></tr>
                    <tr><th>Recent sources</th><td><?php echo esc_html(count(self::recent_sources())); ?></td></tr>
                </tbody>
            </table>

            <?php if ($url_output): ?>
                <h2>URL → Source Object Output</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($url_output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php endif; ?>

            <?php if ($evidence_output): ?>
                <h2>Evidence → Source Object Output</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($evidence_output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php endif; ?>

            <?php if ($source_output): ?>
                <h2>Source Object Output</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($source_output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php endif; ?>

            <h2>Recent Source Objects</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Publisher</th>
                        <th>Domain</th>
                        <th>Type</th>
                        <th>Credibility</th>
                        <th>Evidence</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (self::recent_sources(25) as $source): ?>
                        <tr>
                            <td><?php echo esc_html($source['source_id']); ?></td>
                            <td><?php echo esc_html($source['publisher']); ?></td>
                            <td><?php echo esc_html($source['domain']); ?></td>
                            <td><?php echo esc_html($source['type']); ?></td>
                            <td><?php echo esc_html($source['credibility_label']); ?></td>
                            <td><?php echo esc_html($source['evidence_count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
