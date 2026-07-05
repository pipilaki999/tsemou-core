<?php
namespace TSEMOU\Modules\EventIntelligencePhaseC;

if (!defined('ABSPATH')) exit;

/**
 * Phase C Story Builder skeleton.
 */
class Story_Builder {
    /** @var Story_Builder|null */
    private static $instance = null;

    /**
     * Singleton accessor.
     *
     * @return Story_Builder
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor for singleton.
     */
    private function __construct() {
    }

    /**
     * Build Story Builder stage payload.
     *
     * Expected input is the complete Phase B snapshot.
     * This method only organizes evaluated knowledge and does not infer new facts.
     *
     * @param array $input
     * @return array
     */
    public function build(array $input = []) {
        $story = $this->array_value($input, 'story');
        $evidence = $this->array_value($input, 'evidence');
        $event_identity = $this->array_value($input, 'event_identity');
        $resolver = $this->array_value($input, 'resolver_result');
        if (empty($resolver)) {
            $resolver = $this->array_value($input, 'resolver');
        }
        $timeline = $this->array_value($input, 'timeline');
        $policy = $this->array_value($input, 'policy_decisions');
        if (empty($policy)) {
            $policy = $this->array_value($input, 'policy');
        }
        $public_importance = $this->array_value($input, 'importance');
        if (empty($public_importance)) {
            $public_importance = $this->array_value($input, 'public_importance');
        }
        $trust = $this->array_value($input, 'trust');
        $story_ranking = $this->array_value($input, 'story_ranking');
        $graph = $this->array_value($input, 'graph_updates');
        if (empty($graph)) {
            $graph = $this->array_value($input, 'graph');
        }

        return [
            'story_id' => $this->int_value($story, 'id', intval($input['story_id'] ?? 0)),
            'title' => $this->string_value($story, 'title', ''),
            'executive_summary' => $this->build_executive_summary($story, $evidence),
            'public_importance' => $this->build_public_importance($public_importance),
            'trust' => $this->build_trust($trust),
            'timeline_summary' => $this->build_timeline_summary($timeline),
            'evidence_summary' => $this->build_evidence_summary($evidence),
            'related_entities' => $this->build_related_entities($story, $graph),
            'current_status' => $this->build_current_status($story, $policy, $resolver, $story_ranking),
            'last_updated' => $this->build_last_updated($timeline, $story, $input),
            'confidence' => $this->build_confidence($event_identity, $resolver, $public_importance, $trust, $story_ranking),
        ];
    }

    /**
     * Build executive summary from already-available summary fields.
     *
     * @param array $story
     * @param array $evidence
     * @return string
     */
    private function build_executive_summary(array $story, array $evidence) {
        $candidates = [
            $story['executive_summary'] ?? null,
            $story['summary'] ?? null,
            $story['content'] ?? null,
            $evidence['summary'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return sanitize_textarea_field($candidate);
            }
        }

        return '';
    }

    /**
     * Normalize public importance payload.
     *
     * @param array $importance
     * @return array
     */
    private function build_public_importance(array $importance) {
        return [
            'score' => floatval($importance['score'] ?? 0),
            'band' => $this->string_value($importance, 'band', ''),
            'confidence' => floatval($importance['confidence'] ?? 0),
            'factors' => is_array($importance['factors'] ?? null) ? $importance['factors'] : [],
        ];
    }

    /**
     * Normalize trust payload.
     *
     * @param array $trust
     * @return array
     */
    private function build_trust(array $trust) {
        return [
            'score' => floatval($trust['score'] ?? 0),
            'band' => $this->string_value($trust, 'band', ''),
            'confidence' => floatval($trust['confidence'] ?? 0),
            'company_ids' => $this->int_list($trust['company_ids'] ?? []),
        ];
    }

    /**
     * Build compact timeline summary from existing timeline nodes.
     *
     * @param array $timeline
     * @return array
     */
    private function build_timeline_summary(array $timeline) {
        $nodes = is_array($timeline['nodes'] ?? null) ? $timeline['nodes'] : [];
        $latest = [];
        if (!empty($nodes)) {
            $latest = $nodes[count($nodes) - 1];
        }

        return [
            'event_id' => $this->string_value($timeline, 'event_id', ''),
            'nodes_count' => count($nodes),
            'latest_action' => is_array($latest) ? $this->string_value($latest, 'action', '') : '',
            'latest_timestamp' => is_array($latest) ? $this->string_value($latest, 'timestamp', '') : '',
            'latest_title' => is_array($latest) ? $this->string_value($latest, 'title', '') : '',
        ];
    }

    /**
     * Build evidence summary from existing evidence payload.
     *
     * @param array $evidence
     * @return array
     */
    private function build_evidence_summary(array $evidence) {
        $intelligence = is_array($evidence['intelligence'] ?? null) ? $evidence['intelligence'] : [];

        return [
            'id' => intval($evidence['id'] ?? 0),
            'summary' => $this->string_value($evidence, 'summary', ''),
            'confidence' => floatval($intelligence['confidence'] ?? 0),
            'impact' => floatval($intelligence['impact'] ?? 0),
        ];
    }

    /**
     * Build related entities from story company links and graph relationships.
     *
     * @param array $story
     * @param array $graph
     * @return array
     */
    private function build_related_entities(array $story, array $graph) {
        $ids = [];
        $ids = array_merge($ids, $this->int_list($story['company_ids'] ?? []));

        $relationships = [];
        if (isset($graph['relationships']) && is_array($graph['relationships'])) {
            $relationships = $graph['relationships'];
        } elseif (isset($graph[0]) && is_array($graph[0])) {
            $relationships = $graph;
        }

        foreach ($relationships as $relationship) {
            if (!is_array($relationship)) {
                continue;
            }
            $to_id = intval($relationship['to_entity_id'] ?? 0);
            if ($to_id > 0) {
                $ids[] = $to_id;
            }
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        sort($ids);

        return $ids;
    }

    /**
     * Build current status object from existing phase outputs.
     *
     * @param array $story
     * @param array $policy
     * @param array $resolver
     * @param array $story_ranking
     * @return array
     */
    private function build_current_status(array $story, array $policy, array $resolver, array $story_ranking) {
        return [
            'story_status' => $this->string_value($story, 'status', ''),
            'policy_decision' => $this->string_value($policy, 'decision', ''),
            'resolver_action' => $this->string_value($resolver, 'action', ''),
            'ranking_priority' => $this->string_value($story_ranking, 'priority', ''),
        ];
    }

    /**
     * Build last-updated value from existing known fields.
     *
     * @param array $timeline
     * @param array $story
     * @param array $input
     * @return string
     */
    private function build_last_updated(array $timeline, array $story, array $input) {
        $nodes = is_array($timeline['nodes'] ?? null) ? $timeline['nodes'] : [];
        if (!empty($nodes)) {
            $latest = $nodes[count($nodes) - 1];
            if (is_array($latest) && !empty($latest['timestamp'])) {
                return sanitize_text_field($latest['timestamp']);
            }
        }

        foreach (['last_updated', 'updated_at', 'modified_at'] as $key) {
            if (!empty($story[$key]) && is_string($story[$key])) {
                return sanitize_text_field($story[$key]);
            }
            if (!empty($input[$key]) && is_string($input[$key])) {
                return sanitize_text_field($input[$key]);
            }
        }

        return '';
    }

    /**
     * Compute deterministic confidence from known stage confidences.
     *
     * @param array $event_identity
     * @param array $resolver
     * @param array $public_importance
     * @param array $trust
     * @param array $story_ranking
     * @return float
     */
    private function build_confidence(array $event_identity, array $resolver, array $public_importance, array $trust, array $story_ranking) {
        $values = [
            floatval($event_identity['confidence'] ?? 0),
            floatval($resolver['confidence'] ?? 0),
            floatval($public_importance['confidence'] ?? 0),
            floatval($trust['confidence'] ?? 0),
            floatval($story_ranking['confidence'] ?? 0),
        ];

        $values = array_values(array_filter($values, function($value) {
            return is_numeric($value) && $value >= 0;
        }));

        if (empty($values)) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($values as $value) {
            $sum += max(0.0, min(1.0, floatval($value)));
        }

        return round($sum / count($values), 3);
    }

    /**
     * Return nested array value as array.
     *
     * @param array $source
     * @param string $key
     * @return array
     */
    private function array_value(array $source, $key) {
        $value = $source[$key] ?? [];
        return is_array($value) ? $value : [];
    }

    /**
     * Return key value as sanitized string.
     *
     * @param array $source
     * @param string $key
     * @param string $default
     * @return string
     */
    private function string_value(array $source, $key, $default = '') {
        $value = $source[$key] ?? $default;
        if (!is_scalar($value)) {
            return $default;
        }
        return sanitize_text_field((string) $value);
    }

    /**
     * Return key value as integer.
     *
     * @param array $source
     * @param string $key
     * @param int $default
     * @return int
     */
    private function int_value(array $source, $key, $default = 0) {
        $value = $source[$key] ?? $default;
        return intval($value);
    }

    /**
     * Normalize list values as unique sorted integers.
     *
     * @param mixed $value
     * @return array
     */
    private function int_list($value) {
        if (!is_array($value)) {
            return [];
        }
        $value = array_values(array_unique(array_filter(array_map('intval', $value))));
        sort($value);
        return $value;
    }
}