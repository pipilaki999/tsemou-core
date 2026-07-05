<?php
namespace TSEMOU\Modules\EventIntelligencePhaseC;

if (!defined('ABSPATH')) exit;

/**
 * Phase C Citizen Explanation Engine skeleton.
 */
class Citizen_Explanation_Engine {
    /** @var Citizen_Explanation_Engine|null */
    private static $instance = null;

    /**
     * Singleton accessor.
     *
     * @return Citizen_Explanation_Engine
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
     * Build citizen-facing explanation stage payload.
     *
     * Expected input is the full Living Story object. This method reorganizes
     * evaluated knowledge into explanation-ready structures without generating
     * narrative or introducing new conclusions.
     *
     * @param array $input
     * @return array
     */
    public function explain(array $input = []) {
        $what_happened = $this->build_what_happened($input);
        $why_it_matters = $this->build_why_it_matters($input);
        $who_is_affected = $this->build_who_is_affected($input);
        $evidence_overview = $this->build_evidence_overview($input);
        $timeline_overview = $this->build_timeline_overview($input);
        $trust_overview = $this->build_trust_overview($input);
        $public_importance_overview = $this->build_public_importance_overview($input);
        $what_remains_uncertain = $this->build_uncertainty(
            $input,
            $what_happened,
            $evidence_overview,
            $timeline_overview,
            $trust_overview,
            $public_importance_overview
        );
        $what_to_watch_next = $this->build_watch_next($input, $what_remains_uncertain);

        return [
            'what_happened' => $what_happened,
            'why_it_matters' => $why_it_matters,
            'who_is_affected' => $who_is_affected,
            'evidence_overview' => $evidence_overview,
            'timeline_overview' => $timeline_overview,
            'trust_overview' => $trust_overview,
            'public_importance_overview' => $public_importance_overview,
            'what_remains_uncertain' => $what_remains_uncertain,
            'what_to_watch_next' => $what_to_watch_next,
            'explanation_confidence' => $this->build_confidence(
                $input,
                $what_happened,
                $evidence_overview,
                $timeline_overview,
                $trust_overview,
                $public_importance_overview
            ),
        ];
    }

    /**
     * Build structured "what happened" explanation from current known state.
     *
     * @param array $input
     * @return array
     */
    private function build_what_happened(array $input) {
        $latest = is_array($input['latest_update'] ?? null) ? $input['latest_update'] : [];
        $current_state = is_array($input['current_state'] ?? null) ? $input['current_state'] : [];

        return [
            'story_id' => intval($input['story_id'] ?? 0),
            'title' => $this->string_value($input, 'title', ''),
            'latest_update' => [
                'timestamp' => $this->string_value($latest, 'timestamp', ''),
                'action' => $this->string_value($latest, 'action', ''),
                'title' => $this->string_value($latest, 'title', ''),
            ],
            'current_state' => [
                'story_status' => $this->string_value($current_state, 'story_status', ''),
                'policy_decision' => $this->string_value($current_state, 'policy_decision', ''),
                'resolver_action' => $this->string_value($current_state, 'resolver_action', ''),
            ],
            'source_trace' => [
                'latest_update' => 'living_story.latest_update',
                'current_state' => 'living_story.current_state',
            ],
        ];
    }

    /**
     * Build structured "why it matters" explanation from ranked importance.
     *
     * @param array $input
     * @return array
     */
    private function build_why_it_matters(array $input) {
        $importance = is_array($input['importance_state'] ?? null) ? $input['importance_state'] : [];
        $current_state = is_array($input['current_state'] ?? null) ? $input['current_state'] : [];

        return [
            'importance' => [
                'score' => floatval($importance['score'] ?? 0),
                'band' => $this->string_value($importance, 'band', ''),
                'factors_count' => intval($importance['factors_count'] ?? 0),
            ],
            'policy_context' => [
                'policy_decision' => $this->string_value($current_state, 'policy_decision', ''),
                'ranking_priority' => $this->string_value($current_state, 'ranking_priority', ''),
            ],
            'source_trace' => [
                'importance' => 'living_story.importance_state',
                'policy_context' => 'living_story.current_state',
            ],
        ];
    }

    /**
     * Build structured "who is affected" explanation from linked entities.
     *
     * @param array $input
     * @return array
     */
    private function build_who_is_affected(array $input) {
        $trust = is_array($input['trust_state'] ?? null) ? $input['trust_state'] : [];
        $entities = $this->int_list($trust['company_ids'] ?? []);

        return [
            'related_entity_ids' => $entities,
            'related_entities_count' => count($entities),
            'source_trace' => [
                'related_entity_ids' => 'living_story.trust_state.company_ids',
            ],
        ];
    }

    /**
     * Build structured evidence overview with traceability metadata.
     *
     * @param array $input
     * @return array
     */
    private function build_evidence_overview(array $input) {
        $evidence = is_array($input['evidence_state'] ?? null) ? $input['evidence_state'] : [];

        return [
            'evidence_id' => intval($evidence['id'] ?? 0),
            'summary' => $this->string_value($evidence, 'summary', ''),
            'impact' => floatval($evidence['impact'] ?? 0),
            'confidence' => $this->clamp01($evidence['confidence'] ?? 0),
            'is_traceable' => intval($evidence['id'] ?? 0) > 0,
            'source_trace' => [
                'evidence' => 'living_story.evidence_state',
            ],
        ];
    }

    /**
     * Build structured timeline overview.
     *
     * @param array $input
     * @return array
     */
    private function build_timeline_overview(array $input) {
        $timeline = is_array($input['timeline_state'] ?? null) ? $input['timeline_state'] : [];

        return [
            'event_id' => $this->string_value($timeline, 'event_id', ''),
            'nodes_count' => intval($timeline['nodes_count'] ?? 0),
            'latest_action' => $this->string_value($timeline, 'latest_action', ''),
            'latest_timestamp' => $this->string_value($timeline, 'latest_timestamp', ''),
            'latest_title' => $this->string_value($timeline, 'latest_title', ''),
            'source_trace' => [
                'timeline' => 'living_story.timeline_state',
            ],
        ];
    }

    /**
     * Build structured trust overview.
     *
     * @param array $input
     * @return array
     */
    private function build_trust_overview(array $input) {
        $trust = is_array($input['trust_state'] ?? null) ? $input['trust_state'] : [];

        return [
            'score' => floatval($trust['score'] ?? 0),
            'band' => $this->string_value($trust, 'band', ''),
            'related_entities_count' => count($this->int_list($trust['company_ids'] ?? [])),
            'confidence' => $this->clamp01($trust['confidence'] ?? 0),
            'source_trace' => [
                'trust' => 'living_story.trust_state',
            ],
        ];
    }

    /**
     * Build structured public importance overview.
     *
     * @param array $input
     * @return array
     */
    private function build_public_importance_overview(array $input) {
        $importance = is_array($input['importance_state'] ?? null) ? $input['importance_state'] : [];

        return [
            'score' => floatval($importance['score'] ?? 0),
            'band' => $this->string_value($importance, 'band', ''),
            'factors_count' => intval($importance['factors_count'] ?? 0),
            'confidence' => $this->clamp01($importance['confidence'] ?? 0),
            'source_trace' => [
                'public_importance' => 'living_story.importance_state',
            ],
        ];
    }

    /**
     * Build explicit uncertainty object without masking missing information.
     *
     * @param array $input
     * @param array $what_happened
     * @param array $evidence_overview
     * @param array $timeline_overview
     * @param array $trust_overview
     * @param array $public_importance_overview
     * @return array
     */
    private function build_uncertainty(
        array $input,
        array $what_happened,
        array $evidence_overview,
        array $timeline_overview,
        array $trust_overview,
        array $public_importance_overview
    ) {
        $items = [];

        if (intval($input['story_id'] ?? 0) <= 0) {
            $items[] = ['field' => 'story_id', 'reason' => 'missing_or_invalid'];
        }
        if (trim($this->string_value($input, 'title', '')) === '') {
            $items[] = ['field' => 'title', 'reason' => 'missing'];
        }
        if (trim($this->string_value($what_happened['latest_update'] ?? [], 'timestamp', '')) === '') {
            $items[] = ['field' => 'latest_update.timestamp', 'reason' => 'missing'];
        }
        if (!$evidence_overview['is_traceable']) {
            $items[] = ['field' => 'evidence_overview.evidence_id', 'reason' => 'missing_traceable_evidence'];
        }
        if (intval($timeline_overview['nodes_count'] ?? 0) <= 0) {
            $items[] = ['field' => 'timeline_overview.nodes_count', 'reason' => 'no_timeline_nodes'];
        }
        if ($this->clamp01($trust_overview['confidence'] ?? 0) <= 0.0) {
            $items[] = ['field' => 'trust_overview.confidence', 'reason' => 'low_or_missing_confidence'];
        }
        if ($this->clamp01($public_importance_overview['confidence'] ?? 0) <= 0.0) {
            $items[] = ['field' => 'public_importance_overview.confidence', 'reason' => 'low_or_missing_confidence'];
        }

        return [
            'has_uncertainty' => !empty($items),
            'items' => $items,
        ];
    }

    /**
     * Build deterministic "watch next" checklist from known state and uncertainty.
     *
     * @param array $input
     * @param array $uncertainty
     * @return array
     */
    private function build_watch_next(array $input, array $uncertainty) {
        $watch = [];
        $timeline = is_array($input['timeline_state'] ?? null) ? $input['timeline_state'] : [];
        $latest_timestamp = $this->string_value($timeline, 'latest_timestamp', '');
        $latest_action = $this->string_value($timeline, 'latest_action', '');

        if ($latest_timestamp !== '' || $latest_action !== '') {
            $watch[] = [
                'item' => 'timeline_next_change',
                'current_latest_timestamp' => $latest_timestamp,
                'current_latest_action' => $latest_action,
                'source_trace' => 'living_story.timeline_state',
            ];
        }

        foreach ($uncertainty['items'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $watch[] = [
                'item' => 'resolve_uncertainty',
                'field' => $this->string_value($item, 'field', ''),
                'reason' => $this->string_value($item, 'reason', ''),
                'source_trace' => 'citizen_explanation.what_remains_uncertain',
            ];
        }

        return $watch;
    }

    /**
     * Build deterministic explanation confidence while preserving upstream values.
     *
     * @param array $input
     * @param array $what_happened
     * @param array $evidence_overview
     * @param array $timeline_overview
     * @param array $trust_overview
     * @param array $public_importance_overview
     * @return array
     */
    private function build_confidence(
        array $input,
        array $what_happened,
        array $evidence_overview,
        array $timeline_overview,
        array $trust_overview,
        array $public_importance_overview
    ) {
        $living_story_confidence = $this->clamp01($input['confidence'] ?? 0);
        $evidence_confidence = $this->clamp01($evidence_overview['confidence'] ?? 0);
        $trust_confidence = $this->clamp01($trust_overview['confidence'] ?? 0);
        $importance_confidence = $this->clamp01($public_importance_overview['confidence'] ?? 0);

        $timeline_confidence = intval($timeline_overview['nodes_count'] ?? 0) > 0 ? 1.0 : 0.0;
        $traceability_confidence = !empty($evidence_overview['is_traceable']) ? 1.0 : 0.0;

        $summary = round((
            $living_story_confidence +
            $evidence_confidence +
            $trust_confidence +
            $importance_confidence +
            $timeline_confidence +
            $traceability_confidence
        ) / 6, 3);

        return [
            'value' => $summary,
            'sources' => [
                'living_story_confidence' => $living_story_confidence,
                'evidence_confidence' => $evidence_confidence,
                'trust_confidence' => $trust_confidence,
                'public_importance_confidence' => $importance_confidence,
                'timeline_presence_confidence' => $timeline_confidence,
                'evidence_traceability_confidence' => $traceability_confidence,
            ],
            'source_trace' => [
                'living_story_confidence' => 'living_story.confidence',
                'evidence_confidence' => 'living_story.evidence_state.confidence',
                'trust_confidence' => 'living_story.trust_state.confidence',
                'public_importance_confidence' => 'living_story.importance_state.confidence',
                'timeline_presence_confidence' => 'living_story.timeline_state.nodes_count',
                'evidence_traceability_confidence' => 'living_story.evidence_state.id',
            ],
        ];
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
}