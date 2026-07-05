<?php
namespace TSEMOU\Modules\EventIntelligencePhaseC;

if (!defined('ABSPATH')) exit;

/**
 * Phase C Living Story Engine skeleton.
 */
class Living_Story_Engine {
    /** @var Living_Story_Engine|null */
    private static $instance = null;

    /**
     * Singleton accessor.
     *
     * @return Living_Story_Engine
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
     * Execute Living Story stage payload transformation.
     *
     * Expected input is the Story Builder output. This method only organizes
     * known state into a living-story structure and does not infer new facts.
     *
     * @param array $input
     * @return array
     */
    public function evolve(array $input = []) {
        $story_id = intval($input['story_id'] ?? 0);
        $title = $this->string_value($input, 'title', '');
        $last_updated = $this->string_value($input, 'last_updated', '');

        $current_state = $this->build_current_state($input);
        $timeline_state = $this->build_timeline_state($input);
        $evidence_state = $this->build_evidence_state($input);
        $importance_state = $this->build_importance_state($input);
        $trust_state = $this->build_trust_state($input);

        return [
            'story_id' => $story_id,
            'title' => $title,
            'story_version' => $this->build_story_version($last_updated, $timeline_state),
            'current_state' => $current_state,
            'latest_update' => $this->build_latest_update($input, $timeline_state),
            'change_history' => $this->build_change_history($timeline_state, $input),
            'timeline_state' => $timeline_state,
            'evidence_state' => $evidence_state,
            'importance_state' => $importance_state,
            'trust_state' => $trust_state,
            'participation_readiness' => $this->build_participation_readiness($story_id, $input, $current_state),
            'confidence' => $this->build_confidence($input, $importance_state, $trust_state),
        ];
    }

    /**
     * Build current state from existing story status values.
     *
     * @param array $input
     * @return array
     */
    private function build_current_state(array $input) {
        $status = is_array($input['current_status'] ?? null) ? $input['current_status'] : [];

        return [
            'story_status' => $this->string_value($status, 'story_status', ''),
            'policy_decision' => $this->string_value($status, 'policy_decision', ''),
            'resolver_action' => $this->string_value($status, 'resolver_action', ''),
            'ranking_priority' => $this->string_value($status, 'ranking_priority', ''),
        ];
    }

    /**
     * Build timeline state from Story Builder timeline summary.
     *
     * @param array $input
     * @return array
     */
    private function build_timeline_state(array $input) {
        $timeline = is_array($input['timeline_summary'] ?? null) ? $input['timeline_summary'] : [];

        return [
            'event_id' => $this->string_value($timeline, 'event_id', ''),
            'nodes_count' => intval($timeline['nodes_count'] ?? 0),
            'latest_action' => $this->string_value($timeline, 'latest_action', ''),
            'latest_timestamp' => $this->string_value($timeline, 'latest_timestamp', ''),
            'latest_title' => $this->string_value($timeline, 'latest_title', ''),
        ];
    }

    /**
     * Build evidence state from Story Builder evidence summary.
     *
     * @param array $input
     * @return array
     */
    private function build_evidence_state(array $input) {
        $evidence = is_array($input['evidence_summary'] ?? null) ? $input['evidence_summary'] : [];

        return [
            'id' => intval($evidence['id'] ?? 0),
            'summary' => $this->string_value($evidence, 'summary', ''),
            'impact' => floatval($evidence['impact'] ?? 0),
            'confidence' => $this->clamp01($evidence['confidence'] ?? 0),
        ];
    }

    /**
     * Build importance state from Story Builder public importance.
     *
     * @param array $input
     * @return array
     */
    private function build_importance_state(array $input) {
        $importance = is_array($input['public_importance'] ?? null) ? $input['public_importance'] : [];

        return [
            'score' => floatval($importance['score'] ?? 0),
            'band' => $this->string_value($importance, 'band', ''),
            'factors_count' => is_array($importance['factors'] ?? null) ? count($importance['factors']) : 0,
            'confidence' => $this->clamp01($importance['confidence'] ?? 0),
        ];
    }

    /**
     * Build trust state from Story Builder trust payload.
     *
     * @param array $input
     * @return array
     */
    private function build_trust_state(array $input) {
        $trust = is_array($input['trust'] ?? null) ? $input['trust'] : [];

        return [
            'score' => floatval($trust['score'] ?? 0),
            'band' => $this->string_value($trust, 'band', ''),
            'company_ids' => $this->int_list($trust['company_ids'] ?? []),
            'confidence' => $this->clamp01($trust['confidence'] ?? 0),
        ];
    }

    /**
     * Build deterministic story version identifier from known fields.
     *
     * @param string $last_updated
     * @param array $timeline_state
     * @return string
     */
    private function build_story_version($last_updated, array $timeline_state) {
        $timestamp = $last_updated !== '' ? $last_updated : $this->string_value($timeline_state, 'latest_timestamp', '');
        $nodes_count = intval($timeline_state['nodes_count'] ?? 0);

        if ($timestamp === '') {
            return 'v0';
        }

        return 'v' . $nodes_count . '-' . sanitize_key(str_replace([' ', ':'], '-', $timestamp));
    }

    /**
     * Build latest update object.
     *
     * @param array $input
     * @param array $timeline_state
     * @return array
     */
    private function build_latest_update(array $input, array $timeline_state) {
        return [
            'timestamp' => $this->first_non_empty([
                $this->string_value($timeline_state, 'latest_timestamp', ''),
                $this->string_value($input, 'last_updated', ''),
            ]),
            'action' => $this->string_value($timeline_state, 'latest_action', ''),
            'title' => $this->string_value($timeline_state, 'latest_title', ''),
        ];
    }

    /**
     * Build minimal deterministic change history for future extension.
     *
     * @param array $timeline_state
     * @param array $input
     * @return array
     */
    private function build_change_history(array $timeline_state, array $input) {
        $history = [];
        $latest = $this->build_latest_update($input, $timeline_state);

        if (!empty($latest['timestamp']) || !empty($latest['action']) || !empty($latest['title'])) {
            $history[] = [
                'version' => $this->build_story_version($latest['timestamp'], $timeline_state),
                'timestamp' => $latest['timestamp'],
                'action' => $latest['action'],
                'title' => $latest['title'],
            ];
        }

        return $history;
    }

    /**
     * Build participation-readiness metadata for future citizen modules.
     *
     * No participation mechanisms are implemented here.
     *
     * @param int $story_id
     * @param array $input
     * @param array $current_state
     * @return array
     */
    private function build_participation_readiness($story_id, array $input, array $current_state) {
        $related_entities = $this->int_list($input['related_entities'] ?? []);
        $has_summary = trim($this->string_value($input, 'executive_summary', '')) !== '';
        $has_timeline = intval($input['timeline_summary']['nodes_count'] ?? 0) > 0;
        $policy_decision = $this->string_value($current_state, 'policy_decision', '');
        $ready = $story_id > 0 && $has_summary && $has_timeline && $policy_decision !== '';

        return [
            'is_ready' => $ready,
            'requires' => [
                'story_id' => $story_id > 0,
                'executive_summary' => $has_summary,
                'timeline' => $has_timeline,
                'policy_decision' => $policy_decision !== '',
            ],
            'related_entities_count' => count($related_entities),
            'prepared_for' => ['citizen_explanation', 'question_builder'],
            'actions_enabled' => [],
        ];
    }

    /**
     * Build final confidence for living story state.
     *
     * @param array $input
     * @param array $importance_state
     * @param array $trust_state
     * @return float
     */
    private function build_confidence(array $input, array $importance_state, array $trust_state) {
        $values = [
            $this->clamp01($input['confidence'] ?? 0),
            $this->clamp01($importance_state['confidence'] ?? 0),
            $this->clamp01($trust_state['confidence'] ?? 0),
            $this->clamp01($input['evidence_summary']['confidence'] ?? 0),
        ];

        $sum = 0.0;
        foreach ($values as $value) {
            $sum += $value;
        }

        return round($sum / count($values), 3);
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

    /**
     * Clamp a numeric value into [0, 1].
     *
     * @param mixed $value
     * @return float
     */
    private function clamp01($value) {
        return max(0.0, min(1.0, floatval($value)));
    }

    /**
     * Return first non-empty string candidate.
     *
     * @param array $candidates
     * @return string
     */
    private function first_non_empty(array $candidates) {
        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return sanitize_text_field((string) $candidate);
            }
        }
        return '';
    }
}