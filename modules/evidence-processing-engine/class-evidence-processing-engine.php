<?php
namespace TSEMOU\Modules\EvidenceProcessingEngine;

use TSEMOU\Modules\CompanyEngine\Company_Engine;
use TSEMOU\Modules\EntityEngine\Entity_Engine;
use TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph_Update_Engine;
use TSEMOU\Modules\ProofEngine\Proof_Engine;
use TSEMOU\Modules\RelationshipEngine\Relationship_Engine;

if (!defined('ABSPATH')) exit;

class Evidence_Processing_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
    }

    /**
     * Process a Story post into structured evidence and graph-ready relationships.
     *
     * @param int $story_id
     * @return array
     */
    public static function process_story($story_id) {
        $story_id = absint($story_id);
        $story = get_post($story_id);
        $warnings = [];
        $errors = [];

        $validation = self::validate_story($story);
        if (!$validation['valid']) {
            return [
                'status' => 'error',
                'story' => [],
                'evidence' => [],
                'relationships' => [],
                'graph' => [],
                'warnings' => [],
                'errors' => $validation['errors']
            ];
        }

        $story_entity = Entity_Engine::resolve_wordpress_post($story);
        if (!Entity_Engine::is_supported_entity_type($story_entity['entity_type'] ?? '') || intval($story_entity['entity_id'] ?? 0) <= 0) {
            return [
                'status' => 'error',
                'story' => [],
                'evidence' => [],
                'relationships' => [],
                'graph' => [],
                'warnings' => [],
                'errors' => ['invalid_story_entity']
            ];
        }

        $context = self::build_processing_context($story, $story_entity);
        if (empty($context['connected_company_ids'])) {
            $warnings[] = 'missing_connected_companies';
        }

        $evidence = self::build_evidence_payload($story, $story_entity, $context);
        $proof = self::materialize_proof($story, $evidence);
        if (empty($proof['success'])) {
            return [
                'status' => 'error',
                'story' => [
                    'id' => intval($story->ID),
                    'post_type' => $story->post_type,
                    'title' => get_the_title($story),
                    'entity' => $story_entity
                ],
                'evidence' => $evidence,
                'relationships' => [],
                'graph' => [],
                'warnings' => $warnings,
                'errors' => [sanitize_text_field($proof['message'] ?? 'proof_materialization_failed')]
            ];
        }

        $evidence['proof_id'] = intval($proof['proof_id'] ?? 0);
        $evidence['proof_action'] = sanitize_key($proof['action'] ?? '');
        $evidence['entity']['entity_id'] = intval($proof['proof_id'] ?? 0);
        $evidence['entity']['post_type'] = 'tsemou_proof';
        $evidence['entity']['permalink'] = get_permalink(intval($proof['proof_id'] ?? 0));
        $relationships = self::build_relationships($story, $story_entity, $evidence, $context);
        $graph = self::update_graph($evidence, $relationships);

        if (empty($evidence['summary'])) {
            $warnings[] = 'missing_story_summary';
        }
        if (!empty($graph['errors'])) {
            $errors = array_merge($errors, $graph['errors']);
        }

        return [
            'status' => empty($errors) ? 'success' : 'warning',
            'story' => [
                'id' => intval($story->ID),
                'post_type' => $story->post_type,
                'title' => get_the_title($story),
                'entity' => $story_entity
            ],
            'evidence' => $evidence,
            'relationships' => $relationships,
            'graph' => $graph,
            'proof_id' => intval($proof['proof_id'] ?? 0),
            'warnings' => array_values(array_unique($warnings)),
            'errors' => array_values(array_unique($errors))
        ];
    }

    /**
     * Validate that the input Story is processable.
     *
     * @param mixed $story
     * @return array
     */
    public static function validate_story($story) {
        $errors = [];

        if (!$story || !($story instanceof \WP_Post)) {
            $errors[] = 'story_not_found';
        } elseif ($story->post_type !== 'story') {
            $errors[] = 'invalid_story_post_type';
        } elseif (intval($story->ID) <= 0) {
            $errors[] = 'invalid_story_id';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'story_id' => ($story instanceof \WP_Post) ? intval($story->ID) : 0
        ];
    }

    /**
     * Build the internal evidence payload for a Story-derived evidence record.
     *
     * @param \WP_Post $story
     * @param array $story_entity
     * @param array $context
     * @return array
     */
    public static function build_evidence_payload($story, $story_entity = [], $context = []) {
        $story_entity = Entity_Engine::normalize_entity_reference($story_entity);
        $context = is_array($context) ? $context : [];

        $evidence_entity = Entity_Engine::normalize_entity_reference([
            'entity_type' => 'evidence',
            'entity_id' => intval($story->ID),
            'source' => 'story_processing',
            'post_type' => 'story',
            'title' => get_the_title($story),
            'status' => sanitize_key($story->post_status),
            'permalink' => get_permalink($story),
            'meta' => [
                'story_id' => intval($story->ID),
                'call_sign' => $context['call_sign'] ?? '',
                'connected_company_ids' => $context['connected_company_ids'] ?? []
            ]
        ]);

        return [
            'entity' => $evidence_entity,
            'proof_id' => 0,
            'source_story_id' => intval($story->ID),
            'source_story_entity' => $story_entity,
            'title' => get_the_title($story),
            'summary' => $context['summary'] ?? '',
            'status' => sanitize_key($story->post_status),
            'source' => 'story_processing',
            'meta' => [
                'story_id' => intval($story->ID),
                'call_sign' => $context['call_sign'] ?? '',
                'connected_company_ids' => $context['connected_company_ids'] ?? [],
                'story_status' => $context['story_status'] ?? '',
                'permalink' => get_permalink($story)
            ]
        ];
    }

    /**
     * Build normalized relationships derived from a Story and its evidence payload.
     *
     * @param \WP_Post $story
     * @param array $story_entity
     * @param array $evidence
     * @param array $context
     * @return array
     */
    public static function build_relationships($story, $story_entity = [], $evidence = [], $context = []) {
        $story_entity = Entity_Engine::normalize_entity_reference($story_entity);
        $evidence_entity = Entity_Engine::normalize_entity_reference($evidence['entity'] ?? []);
        $context = is_array($context) ? $context : [];
        $relationships = [];

        $relationships[] = Relationship_Engine::normalize_relationship(
            ['entity_type' => $story_entity['entity_type'], 'entity_id' => intval($story_entity['entity_id'])],
            ['entity_type' => $evidence_entity['entity_type'], 'entity_id' => intval($evidence_entity['entity_id'])],
            'story_has_evidence',
            [
                'confidence' => 1.0,
                'source' => 'story_processing',
                'meta' => ['story_id' => intval($story->ID)]
            ]
        );

        foreach ($context['connected_company_ids'] ?? [] as $company_id) {
            $company_id = absint($company_id);
            if ($company_id <= 0) {
                continue;
            }

            $relationships[] = Relationship_Engine::normalize_relationship(
                ['entity_type' => 'story', 'entity_id' => intval($story->ID)],
                ['entity_type' => 'company', 'entity_id' => $company_id],
                'story_mentions_company',
                [
                    'confidence' => 1.0,
                    'source' => 'story_processing'
                ]
            );

            $relationships[] = Relationship_Engine::normalize_relationship(
                ['entity_type' => 'company', 'entity_id' => $company_id],
                ['entity_type' => 'evidence', 'entity_id' => intval($evidence['proof_id'] ?? $evidence_entity['entity_id'])],
                'company_has_evidence',
                [
                    'confidence' => 1.0,
                    'source' => 'story_processing'
                ]
            );
        }

        return $relationships;
    }

    /**
     * Send the evidence and relationships into the Knowledge Graph Update Engine.
     *
     * @param array $evidence
     * @param array $relationships
     * @return array
     */
    public static function update_graph($evidence = [], $relationships = []) {
        $payload = Knowledge_Graph_Update_Engine::build_graph_payload($evidence['entity'] ?? [], $relationships);
        $validation = Knowledge_Graph_Update_Engine::validate_graph_payload($payload);

        return [
            'success' => (bool) $validation['valid'],
            'payload' => $validation['payload'],
            'errors' => $validation['errors'],
            'statistics' => Knowledge_Graph_Update_Engine::get_graph_statistics()
        ];
    }

    /**
     * Return lightweight processing statistics for the engine.
     *
     * @return array
     */
    public static function get_processing_statistics() {
        return [
            'entity_types_supported' => count(Entity_Engine::get_supported_entity_types()),
            'relationship_types_supported' => count(Relationship_Engine::get_supported_relationship_types()),
            'processing_mode' => 'story_to_tsemou_proof',
            'graph_update_mode' => 'service_only'
        ];
    }

    private static function materialize_proof($story, $evidence) {
        if (!class_exists('TSEMOU\\Modules\\ProofEngine\\Proof_Engine') || !method_exists('TSEMOU\\Modules\\ProofEngine\\Proof_Engine', 'create_or_update_for_story')) {
            return [
                'success' => false,
                'proof_id' => 0,
                'action' => 'none',
                'message' => 'Proof Engine integration unavailable.'
            ];
        }

        return Proof_Engine::create_or_update_for_story(intval($story->ID), $evidence);
    }

    private static function build_processing_context($story, $story_entity) {
        $story_id = intval($story->ID);
        $summary = trim((string) get_post_meta($story_id, '_tsemou_executive_summary', true));
        if ($summary === '') {
            $summary = trim(wp_strip_all_tags((string) $story->post_excerpt));
        }
        if ($summary === '') {
            $summary = wp_trim_words(wp_strip_all_tags((string) $story->post_content), 40, '');
        }

        return [
            'story_id' => $story_id,
            'story_status' => get_post_meta($story_id, '_tsemou_status', true) ?: sanitize_key($story->post_status),
            'call_sign' => get_post_meta($story_id, '_tsemou_call_sign', true),
            'summary' => sanitize_text_field($summary),
            'connected_company_ids' => self::get_connected_company_ids($story_id),
            'story_entity' => $story_entity
        ];
    }

    private static function get_connected_company_ids($story_id) {
        if (class_exists('TSEMOU\\Modules\\CompanyEngine\\Company_Engine') && method_exists('TSEMOU\\Modules\\CompanyEngine\\Company_Engine', 'get_connected_company_ids')) {
            $ids = Company_Engine::get_connected_company_ids($story_id);
            if (is_array($ids)) {
                return array_values(array_filter(array_map('absint', $ids)));
            }
        }

        $ids = get_post_meta($story_id, '_tsemou_connected_companies', true);
        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_filter(array_map('absint', $ids)));
    }
}