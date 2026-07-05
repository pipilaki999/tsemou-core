<?php
namespace TSEMOU\Modules\EntityEngine;

if (!defined('ABSPATH')) exit;

class Entity_Engine {
    private static $instance = null;

    private static $supported_entity_types = [
        'company' => 'Company',
        'evidence' => 'Evidence',
        'story' => 'Story',
        'topic' => 'Topic',
        'source' => 'Source',
        'claim' => 'Claim',
        'event' => 'Event',
        'person' => 'Person',
        'investigation' => 'Investigation'
    ];

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
    }

    public static function entity_types() {
        return self::get_supported_entity_types();
    }

    /**
     * Return the supported entity type configuration.
     *
     * @return array
     */
    public static function get_supported_entity_types() {
        return self::$supported_entity_types;
    }

    /**
     * Validate whether an entity type is supported.
     *
     * @param string $entity_type
     * @return bool
     */
    public static function is_supported_entity_type($entity_type) {
        $entity_type = sanitize_key($entity_type);
        return isset(self::$supported_entity_types[$entity_type]);
    }

    /**
     * Normalize an entity reference into a stable entity array.
     *
     * @param array $input
     * @return array
     */
    public static function normalize_entity_reference($input = []) {
        $input = is_array($input) ? $input : [];

        $entity_type = sanitize_key($input['entity_type'] ?? '');
        if (!self::is_supported_entity_type($entity_type)) {
            $entity_type = self::infer_entity_type_from_post_type($input['post_type'] ?? '');
        }
        if (!self::is_supported_entity_type($entity_type)) {
            $entity_type = 'company';
        }

        $entity_id = absint($input['entity_id'] ?? ($input['post_id'] ?? 0));
        $post_type = sanitize_key($input['post_type'] ?? '');
        $title = sanitize_text_field($input['title'] ?? '');
        $status = sanitize_key($input['status'] ?? '');
        $permalink = isset($input['permalink']) ? esc_url_raw($input['permalink']) : '';
        $source = sanitize_key($input['source'] ?? 'wordpress_post');
        $meta = is_array($input['meta'] ?? null) ? $input['meta'] : [];

        return [
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'source' => $source,
            'post_type' => $post_type,
            'title' => $title,
            'status' => $status,
            'permalink' => $permalink,
            'meta' => self::normalize_meta($meta)
        ];
    }

    /**
     * Resolve a WordPress post into a normalized TSEMOU entity.
     *
     * @param int|\WP_Post $post
     * @return array
     */
    public static function resolve_wordpress_post($post) {
        $resolved_post = get_post($post);
        if (!$resolved_post || !($resolved_post instanceof \WP_Post)) {
            return self::normalize_entity_reference([
                'entity_type' => 'company',
                'entity_id' => 0,
                'source' => 'wordpress_post',
                'post_type' => '',
                'title' => '',
                'status' => '',
                'permalink' => '',
                'meta' => []
            ]);
        }

        $post_type = sanitize_key($resolved_post->post_type);
        $entity_type = self::infer_entity_type_from_post_type($post_type);
        if (!self::is_supported_entity_type($entity_type)) {
            $entity_type = self::infer_entity_type_from_meta($resolved_post->ID);
        }
        if (!self::is_supported_entity_type($entity_type)) {
            $entity_type = 'company';
        }

        return self::normalize_entity_reference([
            'entity_type' => $entity_type,
            'entity_id' => intval($resolved_post->ID),
            'source' => 'wordpress_post',
            'post_type' => $post_type,
            'title' => get_the_title($resolved_post),
            'status' => sanitize_key($resolved_post->post_status),
            'permalink' => get_permalink($resolved_post),
            'meta' => self::collect_post_entity_meta($resolved_post->ID)
        ]);
    }

    /**
     * Return stable metadata for a normalized entity.
     *
     * @param array $entity
     * @return array
     */
    public static function get_entity_metadata($entity = []) {
        $entity = self::normalize_entity_reference($entity);

        return [
            'entity_type_label' => self::$supported_entity_types[$entity['entity_type']] ?? ucfirst($entity['entity_type']),
            'entity_type' => $entity['entity_type'],
            'entity_id' => intval($entity['entity_id']),
            'source' => $entity['source'],
            'post_type' => $entity['post_type'],
            'title' => $entity['title'],
            'status' => $entity['status'],
            'permalink' => $entity['permalink'],
            'meta' => $entity['meta']
        ];
    }

    public static function normalize_entity_object($input) {
        return self::normalize_entity_reference($input);
    }

    public static function create_or_update_entity($input) {
        $entity = self::normalize_entity_reference($input);

        return [
            'success' => true,
            'message' => 'Entity reference normalized.',
            'entity_id' => intval($entity['entity_id']),
            'entity' => $entity
        ];
    }

    public static function stats() {
        return [
            'supported_entity_types' => count(self::$supported_entity_types),
            'service_mode' => 1
        ];
    }

    private static function infer_entity_type_from_post_type($post_type) {
        $post_type = sanitize_key($post_type);

        $map = [
            'company' => 'company',
            'tsemou_proof' => 'evidence',
            'story' => 'story',
            'post' => 'story'
        ];

        return $map[$post_type] ?? '';
    }

    private static function infer_entity_type_from_meta($post_id) {
        $entity_type = sanitize_key(get_post_meta($post_id, '_tsemou_entity_type', true));
        if (self::is_supported_entity_type($entity_type)) {
            return $entity_type;
        }

        return '';
    }

    private static function collect_post_entity_meta($post_id) {
        if (!$post_id) return [];

        $meta = [
            'entity_type' => sanitize_key(get_post_meta($post_id, '_tsemou_entity_type', true)),
            'entity_id' => absint(get_post_meta($post_id, '_tsemou_entity_id', true)),
            'call_sign' => sanitize_text_field(get_post_meta($post_id, '_tsemou_call_sign', true)),
            'related_file' => absint(get_post_meta($post_id, '_tsemou_related_file', true)),
            'connected_companies' => get_post_meta($post_id, '_tsemou_connected_companies', true)
        ];

        if (!is_array($meta['connected_companies'])) {
            $meta['connected_companies'] = [];
        }

        return self::normalize_meta($meta);
    }

    private static function normalize_meta($meta) {
        if (!is_array($meta)) {
            return [];
        }

        return $meta;
    }
}
