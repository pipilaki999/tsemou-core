<?php
namespace TSEMOU\Modules\EntityEngine;

if (!defined('ABSPATH')) exit;

class Entity_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_entity_post_type'], 9);
        add_action('admin_menu', [$this, 'admin_menu'], 20);
    }

    public function register_entity_post_type() {
        if (post_type_exists('tsemou_entity')) return;

        register_post_type('tsemou_entity', [
            'labels' => [
                'name' => 'TSEMOU Entities',
                'singular_name' => 'TSEMOU Entity',
                'add_new_item' => 'Add New Entity',
                'edit_item' => 'Edit Entity'
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'tsemou-os',
            'supports' => ['title', 'editor', 'thumbnail'],
            'capability_type' => 'post',
            'has_archive' => false,
            'rewrite' => false
        ]);
    }

    public static function entity_types() {
        return [
            'company' => 'Company',
            'ngo' => 'NGO',
            'government' => 'Government',
            'ministry' => 'Ministry',
            'political_party' => 'Political Party',
            'public_figure' => 'Public Figure',
            'ceo' => 'CEO / Executive',
            'billionaire' => 'Billionaire',
            'foundation' => 'Foundation',
            'university' => 'University',
            'media' => 'Media',
            'bank' => 'Bank',
            'product' => 'Product',
            'other' => 'Other'
        ];
    }

    public static function normalize_entity_object($input) {
        $input = is_array($input) ? $input : [];

        return [
            'entity_uuid' => sanitize_text_field($input['entity_uuid'] ?? self::uuid_from_input($input)),
            'entity_type' => sanitize_key($input['entity_type'] ?? 'company'),
            'official_name' => sanitize_text_field($input['official_name'] ?? ($input['name'] ?? '')),
            'display_name' => sanitize_text_field($input['display_name'] ?? ($input['official_name'] ?? ($input['name'] ?? ''))),
            'country' => sanitize_text_field($input['country'] ?? ''),
            'industry' => sanitize_text_field($input['industry'] ?? ''),
            'website' => esc_url_raw($input['website'] ?? ''),
            'source' => sanitize_text_field($input['source'] ?? ''),
            'status' => sanitize_key($input['status'] ?? 'active'),
            'payload' => is_array($input['payload'] ?? null) ? $input['payload'] : []
        ];
    }

    public static function uuid_from_input($input) {
        $seed = strtolower(trim(($input['entity_type'] ?? 'entity') . '|' . ($input['official_name'] ?? ($input['name'] ?? '')) . '|' . ($input['country'] ?? '') . '|' . ($input['website'] ?? '')));
        return 'tsemou_entity_' . substr(sha1($seed), 0, 24);
    }

    public static function create_or_update_entity($input) {
        $entity = self::normalize_entity_object($input);
        if (!$entity['display_name']) {
            return ['success' => false, 'message' => 'Missing entity name.', 'entity_id' => 0];
        }

        $existing = get_posts([
            'post_type' => 'tsemou_entity',
            'post_status' => ['publish','draft','pending','private'],
            'numberposts' => 1,
            'meta_key' => '_tsemou_entity_uuid',
            'meta_value' => $entity['entity_uuid']
        ]);

        $post_data = [
            'post_title' => $entity['display_name'],
            'post_type' => 'tsemou_entity',
            'post_status' => 'draft'
        ];

        if (!empty($existing)) {
            $post_data['ID'] = intval($existing[0]->ID);
            $entity_id = wp_update_post($post_data);
            $action = 'updated';
        } else {
            $entity_id = wp_insert_post($post_data);
            $action = 'created';
        }

        if (is_wp_error($entity_id) || !$entity_id) {
            return ['success' => false, 'message' => 'Could not save entity.', 'entity_id' => 0];
        }

        update_post_meta($entity_id, '_tsemou_entity_uuid', $entity['entity_uuid']);
        update_post_meta($entity_id, '_tsemou_entity_type', $entity['entity_type']);
        update_post_meta($entity_id, '_tsemou_entity_object', wp_json_encode($entity, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return ['success' => true, 'message' => 'Entity ' . $action . '.', 'entity_id' => intval($entity_id), 'action' => $action];
    }

    public static function stats() {
        $counts = wp_count_posts('tsemou_entity');
        return [
            'publish' => intval($counts->publish ?? 0),
            'draft' => intval($counts->draft ?? 0),
            'pending' => intval($counts->pending ?? 0),
            'private' => intval($counts->private ?? 0),
        ];
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Entity Engine',
            'Entity Engine',
            'manage_options',
            'tsemou-entity-engine',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        $stats = self::stats();
        ?>
        <div class="wrap">
            <h1>TSEMOU Entity Engine</h1>
            <p>v2 architecture foundation. Companies remain active, but future objects will be handled as entities.</p>
            <h2>Supported Entity Types</h2>
            <table class="widefat striped" style="max-width:760px;">
                <thead><tr><th>Key</th><th>Label</th></tr></thead>
                <tbody>
                    <?php foreach (self::entity_types() as $key => $label): ?>
                        <tr><td><code><?php echo esc_html($key); ?></code></td><td><?php echo esc_html($label); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Entity Counts</h2>
            <table class="widefat striped" style="max-width:760px;">
                <tbody>
                    <?php foreach ($stats as $key => $value): ?>
                        <tr><th><?php echo esc_html(ucwords($key)); ?></th><td><?php echo esc_html($value); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p><strong>Rule:</strong> Company is now treated as the first entity type, not the whole system.</p>
        </div>
        <?php
    }
}
