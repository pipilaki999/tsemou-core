<?php
namespace TSEMOU\Modules\EntityFoundation;

if (!defined('ABSPATH')) exit;

class Entity_Registry {
    private static $instance = null;

    private $type_map = [
        'company' => ['entity_type' => 'company', 'entity_category' => 'actor'],
        'post' => ['entity_type' => 'post', 'entity_category' => 'knowledge'],
        'story' => ['entity_type' => 'story', 'entity_category' => 'knowledge'],
        'tsemou_proof' => ['entity_type' => 'proof', 'entity_category' => 'knowledge'],
        'evidence' => ['entity_type' => 'evidence', 'entity_category' => 'knowledge'],
    ];

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'tsemou_entities';
    }

    public function supported_source_types() {
        $ordered = ['company', 'post', 'story', 'tsemou_proof'];
        if (post_type_exists('evidence')) {
            $ordered[] = 'evidence';
        }

        return array_values(array_filter($ordered, function($type) {
            return isset($this->type_map[$type]);
        }));
    }

    public function get_mapping_for_source_type($source_object_type) {
        $source_object_type = sanitize_key((string) $source_object_type);
        return $this->type_map[$source_object_type] ?? null;
    }

    public function find_by_source($source_object_type, $source_object_id) {
        global $wpdb;

        $source_object_type = sanitize_key((string) $source_object_type);
        $source_object_id = absint($source_object_id);
        if (!$source_object_type || $source_object_id <= 0) return null;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::table_name() . " WHERE source_object_type = %s AND source_object_id = %d LIMIT 1",
            $source_object_type,
            $source_object_id
        ), ARRAY_A);

        return is_array($row) ? $row : null;
    }

    public function find_by_entity_id($entity_id) {
        global $wpdb;

        $entity_id = absint($entity_id);
        if ($entity_id <= 0) return null;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::table_name() . " WHERE entity_id = %d LIMIT 1",
            $entity_id
        ), ARRAY_A);

        return is_array($row) ? $row : null;
    }

    public function ensure_entity_for_object($source_object_type, $source_object_id, $args = []) {
        global $wpdb;

        $source_object_type = sanitize_key((string) $source_object_type);
        $source_object_id = absint($source_object_id);
        $args = is_array($args) ? $args : [];

        if ($source_object_id <= 0) return null;

        $mapping = $this->get_mapping_for_source_type($source_object_type);
        if (!$mapping) return null;

        $existing = $this->find_by_source($source_object_type, $source_object_id);
        if ($existing) return $existing;

        $now = current_time('mysql');
        $entity_uuid = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('entity_', true);

        $creator_user_id = absint($args['creator_user_id'] ?? 0);
        if ($creator_user_id <= 0) {
            $post = get_post($source_object_id);
            $creator_user_id = $post ? absint($post->post_author) : 0;
        }

        $status = sanitize_key((string) ($args['status'] ?? 'active'));
        if (!$status) $status = 'active';

        $visibility = sanitize_key((string) ($args['visibility'] ?? 'public'));
        if (!$visibility) $visibility = 'public';

        $origin = sanitize_key((string) ($args['origin'] ?? $this->detect_origin_for_object($source_object_type, $source_object_id)));
        if (!$origin) $origin = 'wordpress';

        $is_action = array_key_exists('is_action', $args)
            ? intval($args['is_action'])
            : $this->resolve_is_action($source_object_type, $origin);

        $inserted = $wpdb->insert(
            self::table_name(),
            [
                'entity_uuid' => $entity_uuid,
                'entity_type' => sanitize_key($mapping['entity_type']),
                'entity_category' => sanitize_key($mapping['entity_category']),
                'source_object_type' => $source_object_type,
                'source_object_id' => $source_object_id,
                'origin' => $origin,
                'creator_user_id' => $creator_user_id,
                'is_action' => $is_action,
                'status' => $status,
                'visibility' => $visibility,
                'cached_tsemit_score' => intval($args['cached_tsemit_score'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                '%s','%s','%s','%s','%d','%s','%d','%d','%s','%s','%d','%s','%s'
            ]
        );

        if ($inserted === false) {
            // Handle race between concurrent registration attempts.
            return $this->find_by_source($source_object_type, $source_object_id);
        }

        $entity_id = absint($wpdb->insert_id);
        return $this->find_by_entity_id($entity_id);
    }

    public function resolve_from_target($target_type, $target_id) {
        $target_type = sanitize_key((string) $target_type);
        $target_id = absint($target_id);

        if ($target_id <= 0) return null;

        if ($target_type === 'entity') {
            return $this->find_by_entity_id($target_id);
        }

        $post = get_post($target_id);
        if (!$post) return null;

        $source_object_type = sanitize_key((string) $post->post_type);
        return $this->ensure_entity_for_object($source_object_type, $target_id);
    }

    public function set_cached_tsemit_score($entity_id, $score) {
        global $wpdb;

        $entity_id = absint($entity_id);
        $score = intval($score);
        if ($entity_id <= 0) return false;

        $updated = $wpdb->update(
            self::table_name(),
            [
                'cached_tsemit_score' => $score,
                'updated_at' => current_time('mysql'),
            ],
            ['entity_id' => $entity_id],
            ['%d','%s'],
            ['%d']
        );

        return $updated !== false;
    }

    public function count_entities() {
        global $wpdb;
        return intval($wpdb->get_var("SELECT COUNT(*) FROM " . self::table_name()));
    }

    private function detect_origin_for_object($source_object_type, $source_object_id) {
        $source_object_type = sanitize_key((string) $source_object_type);
        $source_object_id = absint($source_object_id);
        if ($source_object_id <= 0) return 'wordpress';

        if ($source_object_type === 'post') {
            $rss_markers = ['_tsemou_rss_item_id', '_tsemou_rss_source', '_tsemou_feed_url'];
            foreach ($rss_markers as $key) {
                $value = get_post_meta($source_object_id, $key, true);
                if ($value !== '' && $value !== null) return 'rss';
            }

            $import_markers = ['_tsemou_import_batch_id', '_tsemou_import_source', '_tsemou_ingest_channel'];
            foreach ($import_markers as $key) {
                $value = get_post_meta($source_object_id, $key, true);
                if ($value !== '' && $value !== null) return 'imported';
            }

            $system_markers = ['_tsemou_system_generated', '_tsemou_discovery_origin'];
            foreach ($system_markers as $key) {
                $value = get_post_meta($source_object_id, $key, true);
                if ($value !== '' && $value !== null) return 'system';
            }
        }

        return 'wordpress';
    }

    private function resolve_is_action($source_object_type, $origin) {
        $source_object_type = sanitize_key((string) $source_object_type);
        $origin = sanitize_key((string) $origin);

        if ($source_object_type !== 'post') {
            return 0;
        }

        if (in_array($origin, ['imported', 'rss', 'system'], true)) {
            return 0;
        }

        return 1;
    }
}
