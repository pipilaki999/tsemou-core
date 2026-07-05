<?php
namespace TSEMOU\Modules\KnowledgeGraph;

use TSEMOU\Modules\EntityEngine\Entity_Engine;
use TSEMOU\Modules\RelationshipEngine\Relationship_Engine;

if (!defined('ABSPATH')) exit;

class Knowledge_Graph_Update_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
    }

    /**
     * Build and validate a graph payload for an entity update.
     *
     * @param array $entity
     * @return array
     */
    public static function update_entity_graph($entity) {
        $payload = self::build_graph_payload($entity, []);
        $validation = self::validate_graph_payload($payload);

        return self::build_update_result('entity', $validation['payload'], $validation['valid'], $validation['errors']);
    }

    /**
     * Build and validate a graph payload for a relationship update.
     *
     * @param array $relationship
     * @return array
     */
    public static function update_relationship_graph($relationship) {
        $relationship_validation = Relationship_Engine::validate_relationship($relationship);
        $normalized_relationship = $relationship_validation['relationship'];

        $payload = self::build_graph_payload(
            [
                'entity_type' => $normalized_relationship['from']['entity_type'] ?? '',
                'entity_id' => $normalized_relationship['from']['entity_id'] ?? 0,
                'source' => 'relationship_engine'
            ],
            [$normalized_relationship]
        );

        $validation = self::validate_graph_payload($payload);
        $errors = array_merge($relationship_validation['errors'], $validation['errors']);

        return self::build_update_result('relationship', $validation['payload'], empty($errors), $errors);
    }

    /**
     * Build a normalized graph update payload.
     *
     * @param array $entity
     * @param array $relationships
     * @return array
     */
    public static function build_graph_payload($entity = [], $relationships = []) {
        $entity = Entity_Engine::normalize_entity_reference(is_array($entity) ? $entity : []);

        if (!is_array($relationships)) {
            $relationships = [];
        }

        if (isset($relationships['from']) || isset($relationships['relationship_type'])) {
            $relationships = [$relationships];
        }

        $normalized_relationships = [];
        foreach ($relationships as $relationship) {
            if (!is_array($relationship)) {
                continue;
            }

            $normalized_relationships[] = Relationship_Engine::normalize_relationship(
                $relationship['from'] ?? [],
                $relationship['to'] ?? [],
                $relationship['relationship_type'] ?? '',
                [
                    'confidence' => $relationship['confidence'] ?? 1.0,
                    'source' => $relationship['source'] ?? 'manual',
                    'created_at' => $relationship['created_at'] ?? '',
                    'meta' => $relationship['meta'] ?? []
                ]
            );
        }

        return [
            'entity' => $entity,
            'relationships' => $normalized_relationships,
            'updated_at' => current_time('mysql'),
            'status' => 'ready'
        ];
    }

    /**
     * Validate a graph payload shape and its embedded entity/relationships.
     *
     * @param array $payload
     * @return array
     */
    public static function validate_graph_payload($payload) {
        $payload = is_array($payload) ? $payload : [];
        $errors = [];

        $entity = Entity_Engine::normalize_entity_reference($payload['entity'] ?? []);
        if (!Entity_Engine::is_supported_entity_type($entity['entity_type'] ?? '') || intval($entity['entity_id'] ?? 0) <= 0) {
            $errors[] = 'invalid_entity';
        }

        $relationships = [];
        $payload_relationships = $payload['relationships'] ?? [];
        if (!is_array($payload_relationships)) {
            $payload_relationships = [];
        }

        foreach ($payload_relationships as $relationship) {
            $relationship_validation = Relationship_Engine::validate_relationship($relationship);
            $relationships[] = $relationship_validation['relationship'];
            if (!$relationship_validation['valid']) {
                foreach ($relationship_validation['errors'] as $error) {
                    $errors[] = 'relationship:' . $error;
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => array_values(array_unique($errors)),
            'payload' => [
                'entity' => $entity,
                'relationships' => $relationships,
                'updated_at' => sanitize_text_field($payload['updated_at'] ?? current_time('mysql')),
                'status' => empty($errors) ? 'ready' : 'invalid'
            ]
        ];
    }

    /**
     * Return current graph statistics without adding a persistence layer.
     *
     * @return array
     */
    public static function get_graph_statistics() {
        $statistics = [
            'entity_types_supported' => count(Entity_Engine::get_supported_entity_types()),
            'relationship_types_supported' => count(Relationship_Engine::get_supported_relationship_types()),
            'payload_status' => 'service_only'
        ];

        if (class_exists('TSEMOU\\Modules\\KnowledgeGraph\\Knowledge_Graph') && method_exists('TSEMOU\\Modules\\KnowledgeGraph\\Knowledge_Graph', 'stats')) {
            $statistics['knowledge_graph'] = Knowledge_Graph::stats();
        }

        return $statistics;
    }

    private static function build_update_result($update_type, $payload, $valid, $errors = []) {
        return [
            'success' => (bool) $valid,
            'update_type' => sanitize_key($update_type),
            'payload' => is_array($payload) ? $payload : [],
            'errors' => array_values(array_unique(is_array($errors) ? $errors : [])),
            'statistics' => self::get_graph_statistics()
        ];
    }
}