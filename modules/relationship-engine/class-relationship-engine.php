<?php
namespace TSEMOU\Modules\RelationshipEngine;

use TSEMOU\Modules\EntityEngine\Entity_Engine;

if (!defined('ABSPATH')) exit;

class Relationship_Engine {
    private static $instance = null;

    private static $supported_relationship_types = [
        'company_has_evidence' => 'Company Has Evidence',
        'story_has_evidence' => 'Story Has Evidence',
        'evidence_mentions_company' => 'Evidence Mentions Company',
        'evidence_supports_claim' => 'Evidence Supports Claim',
        'story_mentions_company' => 'Story Mentions Company',
        'story_mentions_topic' => 'Story Mentions Topic',
        'source_published_story' => 'Source Published Story'
    ];

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
    }

    /**
     * Return the supported relationship type configuration.
     *
     * @return array
     */
    public static function get_supported_relationship_types() {
        return self::$supported_relationship_types;
    }

    /**
     * Validate whether a relationship type is supported.
     *
     * @param string $type
     * @return bool
     */
    public static function supports_relationship_type($type) {
        $type = sanitize_key($type);
        return isset(self::$supported_relationship_types[$type]);
    }

    /**
     * Normalize relationship data into a stable relationship array.
     *
     * @param array $from
     * @param array $to
     * @param string $relationship_type
     * @param array $args
     * @return array
     */
    public static function normalize_relationship($from, $to, $relationship_type, $args = []) {
        $args = is_array($args) ? $args : [];
        $relationship_type = sanitize_key($relationship_type);

        $normalized_from = self::normalize_entity_endpoint($from);
        $normalized_to = self::normalize_entity_endpoint($to);

        return [
            'from' => $normalized_from,
            'to' => $normalized_to,
            'relationship_type' => self::supports_relationship_type($relationship_type) ? $relationship_type : '',
            'confidence' => self::normalize_confidence($args['confidence'] ?? 1.0),
            'source' => sanitize_key($args['source'] ?? 'manual'),
            'created_at' => sanitize_text_field($args['created_at'] ?? current_time('mysql')),
            'meta' => self::normalize_meta($args['meta'] ?? [])
        ];
    }

    /**
     * Validate a normalized relationship structure.
     *
     * @param array $relationship
     * @return array
     */
    public static function validate_relationship($relationship) {
        $relationship = is_array($relationship) ? $relationship : [];
        $errors = [];

        $from = self::normalize_entity_endpoint($relationship['from'] ?? []);
        $to = self::normalize_entity_endpoint($relationship['to'] ?? []);
        $relationship_type = sanitize_key($relationship['relationship_type'] ?? '');
        $confidence = self::normalize_confidence($relationship['confidence'] ?? 1.0);

        if (!self::is_valid_entity_endpoint($from)) {
            $errors[] = 'invalid_from_entity';
        }

        if (!self::is_valid_entity_endpoint($to)) {
            $errors[] = 'invalid_to_entity';
        }

        if (!self::supports_relationship_type($relationship_type)) {
            $errors[] = 'unsupported_relationship_type';
        }

        if ($confidence < 0 || $confidence > 1) {
            $errors[] = 'invalid_confidence';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'relationship' => self::normalize_relationship(
                $from,
                $to,
                $relationship_type,
                [
                    'confidence' => $confidence,
                    'source' => $relationship['source'] ?? 'manual',
                    'created_at' => $relationship['created_at'] ?? '',
                    'meta' => $relationship['meta'] ?? []
                ]
            )
        ];
    }

    private static function normalize_entity_endpoint($entity) {
        $entity = is_array($entity) ? $entity : [];

        if (class_exists('TSEMOU\\Modules\\EntityEngine\\Entity_Engine') && method_exists('TSEMOU\\Modules\\EntityEngine\\Entity_Engine', 'normalize_entity_reference')) {
            $normalized = Entity_Engine::normalize_entity_reference($entity);
            return [
                'entity_type' => sanitize_key($normalized['entity_type'] ?? ''),
                'entity_id' => absint($normalized['entity_id'] ?? 0)
            ];
        }

        return [
            'entity_type' => sanitize_key($entity['entity_type'] ?? ''),
            'entity_id' => absint($entity['entity_id'] ?? ($entity['post_id'] ?? 0))
        ];
    }

    private static function is_valid_entity_endpoint($entity) {
        $entity_type = sanitize_key($entity['entity_type'] ?? '');
        $entity_id = absint($entity['entity_id'] ?? 0);

        if (!class_exists('TSEMOU\\Modules\\EntityEngine\\Entity_Engine') || !method_exists('TSEMOU\\Modules\\EntityEngine\\Entity_Engine', 'is_supported_entity_type')) {
            return $entity_type !== '' && $entity_id > 0;
        }

        return Entity_Engine::is_supported_entity_type($entity_type) && $entity_id > 0;
    }

    private static function normalize_confidence($confidence) {
        $confidence = is_numeric($confidence) ? floatval($confidence) : 1.0;
        if ($confidence < 0) return 0.0;
        if ($confidence > 1) return 1.0;
        return $confidence;
    }

    private static function normalize_meta($meta) {
        if (!is_array($meta)) {
            return [];
        }

        return $meta;
    }
}