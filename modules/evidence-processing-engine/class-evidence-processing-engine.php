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

        self::pipeline_trace('stage_4_story_created_or_found', [
            'story_id' => $story_id,
            'executed' => ($story instanceof \WP_Post) ? 'yes' : 'no',
            'post_type' => sanitize_key((string) ($story->post_type ?? '')),
            'reason' => ($story instanceof \WP_Post) ? '' : 'story_not_found',
        ]);

        self::pipeline_trace('5_story_analysis_started', [
            'story_id' => $story_id,
            'post_type' => sanitize_key((string) ($story->post_type ?? '')),
            'executed' => 'yes',
        ]);

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

        self::pipeline_trace('8_evidence_created', [
            'story_id' => intval($story->ID),
            'proof_id' => intval($proof['proof_id'] ?? 0),
            'executed' => 'yes',
            'evidence_post_id' => get_post_type(intval($proof['proof_id'] ?? 0)) === 'evidence' ? intval($proof['proof_id'] ?? 0) : 0,
            'created_or_reused' => sanitize_key((string) ($proof['action'] ?? '')),
            'post_type' => sanitize_key((string) get_post_type(intval($proof['proof_id'] ?? 0))),
            'post_status' => sanitize_key((string) get_post_status(intval($proof['proof_id'] ?? 0))),
            'admin_visible_evidence_count' => self::count_admin_visible_posts('evidence'),
            'admin_visible_tsemou_proof_count' => self::count_admin_visible_posts('tsemou_proof'),
        ]);

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
        } elseif (!in_array($story->post_type, ['story', 'post'], true)) {
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
        self::pipeline_trace('stage_7_knowledge_graph_update', [
            'proof_id' => absint($evidence['proof_id'] ?? 0),
            'relationship_count' => is_array($relationships) ? count($relationships) : 0,
            'executed' => 'yes',
        ]);
        self::pipeline_trace('9_knowledge_graph_update', [
            'proof_id' => absint($evidence['proof_id'] ?? 0),
            'relationship_count' => is_array($relationships) ? count($relationships) : 0,
            'executed' => 'yes',
            'source' => 'evidence_processing_engine',
        ]);

        $payload = Knowledge_Graph_Update_Engine::build_graph_payload($evidence['entity'] ?? [], $relationships);
        $result = Knowledge_Graph_Update_Engine::commit_graph_payload($payload, 'story_processing');

        return [
            'success' => (bool) ($result['success'] ?? false),
            'payload' => $result['payload'] ?? [],
            'errors' => $result['errors'] ?? [],
            'statistics' => $result['statistics'] ?? Knowledge_Graph_Update_Engine::get_graph_statistics()
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
        self::pipeline_trace('stage_6_evidence_creation', [
            'story_id' => intval($story->ID),
            'executed' => 'yes',
            'phase' => 'start',
        ]);
        self::pipeline_trace('7_evidence_creation_started', [
            'story_id' => intval($story->ID),
            'executed' => 'yes',
        ]);

        self::pipeline_trace('stage_7_proof_engine_handoff', [
            'incoming_story_id' => intval($story->ID),
            'incoming_post_id' => intval($story->ID),
            'story_object_class' => is_object($story) ? get_class($story) : 'null',
            'repository_used' => 'Proof_Engine::create_or_update_for_story',
        ]);

        if (!class_exists('TSEMOU\\Modules\\ProofEngine\\Proof_Engine') || !method_exists('TSEMOU\\Modules\\ProofEngine\\Proof_Engine', 'create_or_update_for_story')) {
            return [
                'success' => false,
                'proof_id' => 0,
                'action' => 'none',
                'message' => 'Proof Engine integration unavailable.'
            ];
        }

        $result = Proof_Engine::create_or_update_for_story(intval($story->ID), $evidence);

        self::pipeline_trace('stage_6_evidence_creation', [
            'story_id' => intval($story->ID),
            'executed' => !empty($result['success']) ? 'yes' : 'no',
            'phase' => 'result',
            'proof_id' => intval($result['proof_id'] ?? 0),
            'reason' => !empty($result['success']) ? '' : sanitize_text_field((string) ($result['message'] ?? 'proof_materialization_failed')),
        ]);

        return $result;
    }

    private static function build_processing_context($story, $story_entity) {
        $story_id = intval($story->ID);
        self::pipeline_trace('stage_5_company_resolution', [
            'story_id' => $story_id,
            'executed' => 'yes',
        ]);
        self::pipeline_trace('6_company_resolution_started', [
            'story_id' => $story_id,
            'executed' => 'yes',
        ]);

        $summary = trim((string) get_post_meta($story_id, '_tsemou_executive_summary', true));
        if ($summary === '') {
            $summary = trim(wp_strip_all_tags((string) $story->post_excerpt));
        }
        if ($summary === '') {
            $summary = wp_trim_words(wp_strip_all_tags((string) $story->post_content), 40, '');
        }

        $connected_company_ids = self::get_connected_company_ids($story_id);
        $runtime_detection_executed = 'no';
        $runtime_detected_company_ids = [];
        $runtime_detection_persisted = 'no';
        $detection_text_length = 0;
        $company_index_count = 0;
        $text_match_count = 0;
        $domain_fallback_attempted = 'no';
        $domain_fallback_match_count = 0;
        $domain_fallback_reason = 'not_attempted';
        $detection_text_excerpt = '';
        $normalized_detection_text = '';
        $detection_text_truncated_for_log = 'no';
        $company_index_sample = [];
        $alias_checks = [];
        $alias_checks_truncated = 'no';
        $no_match_reason = '';
        $no_match_reason_context = [
            'has_any_substring_alias' => false,
            'has_any_boundary_mismatch' => false,
            'has_any_normalization_mismatch' => false,
        ];

        if (empty($connected_company_ids) && class_exists('TSEMOU\\Modules\\DiscoveryEngine\\Discovery_Engine') && method_exists('TSEMOU\\Modules\\DiscoveryEngine\\Discovery_Engine', 'detect_companies')) {
            $runtime_detection_executed = 'yes';

            $source_url = '';
            foreach (['_tsemou_source_url', '_tsemou_proof_url', 'source_url', 'url', '_source_url'] as $source_meta_key) {
                $candidate_source_url = esc_url_raw((string) get_post_meta($story_id, $source_meta_key, true));
                if ($candidate_source_url !== '') {
                    $source_url = $candidate_source_url;
                    break;
                }
            }

            $source_domain = '';
            foreach (['_tsemou_source_domain', 'source_domain'] as $domain_meta_key) {
                $candidate_source_domain = sanitize_text_field((string) get_post_meta($story_id, $domain_meta_key, true));
                if ($candidate_source_domain !== '') {
                    $source_domain = $candidate_source_domain;
                    break;
                }
            }

            if ($source_url === '') {
                $source_url = esc_url_raw((string) get_permalink($story_id));
            }

            if ($source_domain === '' && class_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine') && method_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine', 'normalize_domain')) {
                $source_domain = \TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine::normalize_domain($source_url);
            }

            if ($source_domain === '') {
                $parsed_source_domain = parse_url($source_url, PHP_URL_HOST);
                if (is_string($parsed_source_domain) && $parsed_source_domain !== '') {
                    $source_domain = sanitize_text_field(strtolower($parsed_source_domain));
                }
            }

            $detection_text = trim(implode(' ', array_filter([
                trim((string) $story->post_title),
                trim((string) $story->post_excerpt),
                trim(wp_strip_all_tags((string) $story->post_content)),
                trim((string) get_post_meta($story_id, '_tsemou_source_name', true)),
                $source_url,
                $source_domain,
            ])));

            $detection_text_length = strlen($detection_text);
            $detection_text_excerpt = mb_substr($detection_text, 0, 500);
            $detection_text_truncated_for_log = mb_strlen($detection_text) > 500 ? 'yes' : 'no';

            if (class_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine') && method_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine', 'normalize_name')) {
                $normalized_detection_text = \TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine::normalize_name($detection_text);
            }

            if (class_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine') && method_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine', 'company_index_count')) {
                $company_index_count = intval(\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine::company_index_count());
            }

            if (class_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine') && method_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine', 'company_index')) {
                $index_for_diagnostics = \TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine::company_index();

                foreach (array_slice((array) $index_for_diagnostics, 0, 20) as $company_diag) {
                    if (!is_array($company_diag)) continue;
                    $company_index_sample[] = [
                        'company_id' => intval($company_diag['company_id'] ?? 0),
                        'display_name' => sanitize_text_field((string) ($company_diag['display_name'] ?? '')),
                        'official_name' => sanitize_text_field((string) ($company_diag['official_name'] ?? '')),
                        'aliases' => array_values(array_filter(array_map('sanitize_text_field', (array) ($company_diag['aliases'] ?? [])))),
                        'domains' => array_values(array_filter(array_map('sanitize_text_field', (array) ($company_diag['domains'] ?? [])))),
                    ];
                }

                $has_any_substring_alias = false;
                $has_any_boundary_mismatch = false;
                $has_any_normalization_mismatch = false;

                foreach ((array) $index_for_diagnostics as $company_diag) {
                    if (!is_array($company_diag)) continue;

                    $company_id_diag = intval($company_diag['company_id'] ?? 0);
                    $raw_aliases_diag = (array) ($company_diag['aliases'] ?? []);
                    $normalized_aliases_diag = (array) ($company_diag['normalized_aliases'] ?? []);

                    foreach ($normalized_aliases_diag as $alias_diag) {
                        $alias_diag = sanitize_text_field((string) $alias_diag);
                        if ($alias_diag === '' || strlen($alias_diag) < 3) {
                            continue;
                        }

                        $alias_substring_present = strpos((string) $normalized_detection_text, $alias_diag) !== false;
                        $alias_found = preg_match('/\\b' . preg_quote($alias_diag, '/') . '\\b/u', (string) $normalized_detection_text) ? true : false;

                        if ($alias_substring_present) {
                            $has_any_substring_alias = true;
                        }

                        if ($alias_substring_present && !$alias_found) {
                            $has_any_boundary_mismatch = true;
                        }

                        if (count($alias_checks) < 1500) {
                            $alias_checks[] = [
                                'company_id' => $company_id_diag,
                                'alias' => $alias_diag,
                                'found' => $alias_found ? 'yes' : 'no',
                            ];
                        } else {
                            $alias_checks_truncated = 'yes';
                        }
                    }

                    foreach ($raw_aliases_diag as $raw_alias_diag) {
                        $raw_alias_diag = trim((string) $raw_alias_diag);
                        if ($raw_alias_diag === '') continue;
                        if (stripos($detection_text, $raw_alias_diag) === false) continue;

                        $normalized_alias_from_raw = class_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine') && method_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine', 'normalize_name')
                            ? \TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine::normalize_name($raw_alias_diag)
                            : '';

                        if ($normalized_alias_from_raw !== '' && strpos((string) $normalized_detection_text, $normalized_alias_from_raw) === false) {
                            $has_any_normalization_mismatch = true;
                        }
                    }
                }

                $no_match_reason_context = [
                    'has_any_substring_alias' => $has_any_substring_alias,
                    'has_any_boundary_mismatch' => $has_any_boundary_mismatch,
                    'has_any_normalization_mismatch' => $has_any_normalization_mismatch,
                ];
            }

            $company_matches = \TSEMOU\Modules\DiscoveryEngine\Discovery_Engine::detect_companies($detection_text, 10);
            if (is_array($company_matches)) {
                foreach ($company_matches as $company_match) {
                    if (!is_array($company_match)) continue;
                    $matched_company_id = intval($company_match['company_id'] ?? 0);
                    if ($matched_company_id > 0) {
                        $runtime_detected_company_ids[] = $matched_company_id;
                    }
                }
            }

            $text_match_count = count($runtime_detected_company_ids);

            if ($text_match_count === 0) {
                if ($normalized_detection_text === '') {
                    $no_match_reason = 'detection_text_empty';
                } elseif ($detection_text_truncated_for_log === 'yes' && mb_strlen($detection_text) > 5000) {
                    $no_match_reason = 'detection_text_truncated';
                } elseif (!empty($no_match_reason_context['has_any_boundary_mismatch'])) {
                    $no_match_reason = 'regex_boundary_mismatch';
                } elseif (!empty($no_match_reason_context['has_any_normalization_mismatch'])) {
                    $no_match_reason = 'normalization_mismatch';
                } elseif (isset($no_match_reason_context['has_any_substring_alias']) && !$no_match_reason_context['has_any_substring_alias']) {
                    $no_match_reason = 'alias_missing';
                } else {
                    $no_match_reason = 'alias_missing';
                }
            }

            if (empty($runtime_detected_company_ids)) {
                $domain_fallback_attempted = 'yes';

                if ($source_domain === '') {
                    $domain_fallback_reason = 'missing_source_domain';
                } elseif (!(class_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine') && method_exists('TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine', 'detect_by_source_domain'))) {
                    $domain_fallback_reason = 'source_domain_detector_unavailable';
                } else {
                    $domain_matches = \TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine::detect_by_source_domain($source_domain, 10);
                    if (is_array($domain_matches)) {
                        foreach ($domain_matches as $domain_match) {
                            if (!is_array($domain_match)) continue;
                            $domain_company_id = intval($domain_match['company_id'] ?? 0);
                            if ($domain_company_id > 0) {
                                $runtime_detected_company_ids[] = $domain_company_id;
                            }
                        }
                    }

                    $domain_fallback_match_count = count($runtime_detected_company_ids);
                    $domain_fallback_reason = $domain_fallback_match_count > 0 ? 'exact_source_domain_match' : 'no_exact_source_domain_match';
                }
            } else {
                $domain_fallback_reason = 'text_match_found';
            }

            $runtime_detected_company_ids = array_values(array_unique(array_filter(array_map('absint', $runtime_detected_company_ids))));
            $domain_fallback_match_count = count($runtime_detected_company_ids) - $text_match_count;
            if ($domain_fallback_match_count < 0) {
                $domain_fallback_match_count = 0;
            }

            if (!empty($runtime_detected_company_ids) && class_exists('TSEMOU\\Modules\\CompanyEngine\\Company_Engine') && method_exists('TSEMOU\\Modules\\CompanyEngine\\Company_Engine', 'save_connected_companies')) {
                Company_Engine::save_connected_companies($story_id, $runtime_detected_company_ids);
                $runtime_detection_persisted = 'yes';
                $connected_company_ids = self::get_connected_company_ids($story_id);
            }
        }

        self::pipeline_trace('stage_5_runtime_company_detection', [
            'story_id' => $story_id,
            'executed' => $runtime_detection_executed,
            'detection_text_excerpt' => $detection_text_excerpt,
            'normalized_detection_text' => $normalized_detection_text,
            'detection_text_length' => intval($detection_text_length),
            'detection_text_truncated_for_log' => $detection_text_truncated_for_log,
            'company_index_count' => intval($company_index_count),
            'company_index_sample' => $company_index_sample,
            'alias_checks' => $alias_checks,
            'alias_checks_truncated' => $alias_checks_truncated,
            'text_match_count' => intval($text_match_count),
            'domain_fallback_attempted' => $domain_fallback_attempted,
            'domain_fallback_match_count' => intval($domain_fallback_match_count),
            'domain_fallback_reason' => $domain_fallback_reason,
            'no_match_reason' => $no_match_reason,
            'detected_company_count' => count($runtime_detected_company_ids),
            'company_ids' => $runtime_detected_company_ids,
            'persisted' => $runtime_detection_persisted,
        ]);

        if (empty($connected_company_ids)) {
            $resolved_company_ids = [];

            $discovery_company_ids = get_post_meta($story_id, '_tsemou_discovery_company_ids', true);
            if (is_array($discovery_company_ids)) {
                $resolved_company_ids = array_merge($resolved_company_ids, $discovery_company_ids);
            }

            $relationship_map = get_post_meta($story_id, '_tsemou_company_relationships', true);
            if (is_array($relationship_map)) {
                $resolved_company_ids = array_merge($resolved_company_ids, array_keys($relationship_map));
            }

            $single_company_id = get_post_meta($story_id, '_tsemou_company_id', true);
            if (is_numeric($single_company_id)) {
                $resolved_company_ids[] = intval($single_company_id);
            }

            $resolved_company_ids = array_values(array_unique(array_filter(array_map('absint', $resolved_company_ids))));

            if (!empty($resolved_company_ids)) {
                if (class_exists('TSEMOU\\Modules\\CompanyEngine\\Company_Engine') && method_exists('TSEMOU\\Modules\\CompanyEngine\\Company_Engine', 'save_connected_companies')) {
                    Company_Engine::save_connected_companies($story_id, $resolved_company_ids);
                } else {
                    update_post_meta($story_id, '_tsemou_connected_companies', $resolved_company_ids);
                }

                $connected_company_ids = $resolved_company_ids;
                self::pipeline_trace('stage_5_company_context_persisted', [
                    'story_id' => $story_id,
                    'executed' => 'yes',
                    'resolved_count' => count($resolved_company_ids),
                    'source' => 'runtime_meta_fallback',
                ]);
            } else {
                self::pipeline_trace('stage_5_company_context_persisted', [
                    'story_id' => $story_id,
                    'executed' => 'no',
                    'reason' => 'no_runtime_company_ids_found',
                    'source' => 'runtime_meta_fallback',
                ]);
            }
        } else {
            self::pipeline_trace('stage_5_company_context_persisted', [
                'story_id' => $story_id,
                'executed' => 'no',
                'reason' => 'already_present_in_connected_meta',
                'resolved_count' => count($connected_company_ids),
                'source' => 'runtime_meta_fallback',
            ]);
        }

        return [
            'story_id' => $story_id,
            'story_status' => get_post_meta($story_id, '_tsemou_status', true) ?: sanitize_key($story->post_status),
            'call_sign' => get_post_meta($story_id, '_tsemou_call_sign', true),
            'summary' => sanitize_text_field($summary),
            'connected_company_ids' => $connected_company_ids,
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

    private static function pipeline_trace($stage, $context = []) {
        if (!(defined('WP_DEBUG_LOG') && WP_DEBUG_LOG)) return;

        $payload = is_array($context) ? $context : [];
        $payload['stage'] = sanitize_text_field((string) $stage);
        $payload['ts'] = current_time('mysql');
        error_log('[TSEMOU_PIPELINE_TRACE] ' . wp_json_encode($payload));
    }

    private static function count_admin_visible_posts($post_type) {
        if (!post_type_exists($post_type)) return 0;

        $counts = wp_count_posts($post_type);
        if (!is_object($counts)) return 0;

        $total = 0;
        foreach (['publish', 'draft', 'pending', 'private'] as $status) {
            $total += isset($counts->$status) ? intval($counts->$status) : 0;
        }
        return $total;
    }
}