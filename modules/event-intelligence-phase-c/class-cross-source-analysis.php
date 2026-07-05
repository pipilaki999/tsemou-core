<?php
namespace TSEMOU\Modules\EventIntelligencePhaseC;

if (!defined('ABSPATH')) exit;

/**
 * Phase C Cross Source Analysis skeleton.
 */
class Cross_Source_Analysis {
    /** @var Cross_Source_Analysis|null */
    private static $instance = null;

    /**
     * Singleton accessor.
     *
     * @return Cross_Source_Analysis
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
     * Build cross-source stage payload.
     *
     * Expected input is the complete Citizen Explanation object.
     * This method compares only already-evaluated information and does not
     * infer truth, rank sources, or create unsupported conclusions.
     *
     * @param array $input
     * @return array
     */
    public function analyze(array $input = []) {
        $evidence_sources = $this->build_evidence_sources($input);
        $agreements = $this->build_agreements($input, $evidence_sources);
        $disagreements = $this->build_disagreements($input, $evidence_sources, $agreements);
        $conflicting_claims = $this->build_conflicting_claims($input, $evidence_sources, $disagreements);
        $verification_gaps = $this->build_verification_gaps($input, $evidence_sources, $agreements, $disagreements, $conflicting_claims);
        $evidence_coverage = $this->build_evidence_coverage($input, $evidence_sources, $verification_gaps);
        $source_traceability = $this->build_source_traceability($input, $evidence_sources);

        return [
            'evidence_sources' => $evidence_sources,
            'agreements' => $agreements,
            'disagreements' => $disagreements,
            'conflicting_claims' => $conflicting_claims,
            'verification_gaps' => $verification_gaps,
            'evidence_coverage' => $evidence_coverage,
            'source_traceability' => $source_traceability,
            'comparison_confidence' => $this->build_confidence(
                $input,
                $evidence_sources,
                $agreements,
                $disagreements,
                $verification_gaps,
                $source_traceability
            ),
        ];
    }

    /**
     * Build normalized evidence source list from explanation payload.
     *
     * @param array $input
     * @return array
     */
    private function build_evidence_sources(array $input) {
        $sources = [];
        $evidence = is_array($input['evidence_overview'] ?? null) ? $input['evidence_overview'] : [];
        $evidence_id = intval($evidence['evidence_id'] ?? 0);
        $is_traceable = !empty($evidence['is_traceable']);

        if ($evidence_id > 0 || $is_traceable) {
            $sources[] = [
                'source_key' => $evidence_id > 0 ? 'evidence_' . $evidence_id : 'evidence_unidentified',
                'evidence_id' => $evidence_id,
                'summary' => $this->string_value($evidence, 'summary', ''),
                'traceable' => $is_traceable,
                'source_trace' => is_array($evidence['source_trace'] ?? null) ? $evidence['source_trace'] : [],
            ];
        }

        return $sources;
    }

    /**
     * Build explicit agreement list from already-provided comparison structures.
     *
     * @param array $input
     * @param array $evidence_sources
     * @return array
     */
    private function build_agreements(array $input, array $evidence_sources) {
        $agreements = $this->array_list($input['agreements'] ?? []);
        $normalized = [];

        foreach ($agreements as $agreement) {
            if (!is_array($agreement)) {
                continue;
            }
            $normalized[] = [
                'topic' => $this->string_value($agreement, 'topic', ''),
                'supports' => $this->string_value($agreement, 'supports', ''),
                'source_refs' => $this->string_list($agreement['source_refs'] ?? []),
                'source_trace' => $this->string_value($agreement, 'source_trace', 'citizen_explanation.agreements'),
            ];
        }

        return $normalized;
    }

    /**
     * Build explicit disagreement list from already-provided comparison structures.
     *
     * @param array $input
     * @param array $evidence_sources
     * @param array $agreements
     * @return array
     */
    private function build_disagreements(array $input, array $evidence_sources, array $agreements) {
        $disagreements = $this->array_list($input['disagreements'] ?? []);
        $normalized = [];

        foreach ($disagreements as $disagreement) {
            if (!is_array($disagreement)) {
                continue;
            }
            $normalized[] = [
                'topic' => $this->string_value($disagreement, 'topic', ''),
                'difference' => $this->string_value($disagreement, 'difference', ''),
                'source_refs' => $this->string_list($disagreement['source_refs'] ?? []),
                'source_trace' => $this->string_value($disagreement, 'source_trace', 'citizen_explanation.disagreements'),
            ];
        }

        return $normalized;
    }

    /**
     * Build explicit conflicting-claims list from already-provided structures.
     *
     * @param array $input
     * @param array $evidence_sources
     * @param array $disagreements
     * @return array
     */
    private function build_conflicting_claims(array $input, array $evidence_sources, array $disagreements) {
        $claims = $this->array_list($input['conflicting_claims'] ?? []);
        $normalized = [];

        foreach ($claims as $claim) {
            if (!is_array($claim)) {
                continue;
            }
            $normalized[] = [
                'claim' => $this->string_value($claim, 'claim', ''),
                'source_refs' => $this->string_list($claim['source_refs'] ?? []),
                'verification_state' => $this->string_value($claim, 'verification_state', ''),
                'source_trace' => $this->string_value($claim, 'source_trace', 'citizen_explanation.conflicting_claims'),
            ];
        }

        return $normalized;
    }

    /**
     * Build explicit verification-gap list while preserving uncertainty.
     *
     * @param array $input
     * @param array $evidence_sources
     * @param array $agreements
     * @param array $disagreements
     * @param array $conflicting_claims
     * @return array
     */
    private function build_verification_gaps(
        array $input,
        array $evidence_sources,
        array $agreements,
        array $disagreements,
        array $conflicting_claims
    ) {
        $gaps = [];
        $provided = $this->array_list($input['verification_gaps'] ?? []);

        foreach ($provided as $gap) {
            if (!is_array($gap)) {
                continue;
            }
            $gaps[] = [
                'field' => $this->string_value($gap, 'field', ''),
                'reason' => $this->string_value($gap, 'reason', ''),
                'source_trace' => $this->string_value($gap, 'source_trace', 'citizen_explanation.verification_gaps'),
            ];
        }

        if (count($evidence_sources) < 2) {
            $gaps[] = [
                'field' => 'evidence_sources',
                'reason' => 'insufficient_distinct_sources_for_cross_source_comparison',
                'source_trace' => 'cross_source.evidence_sources',
            ];
        }
        if (empty($agreements) && empty($disagreements) && empty($conflicting_claims)) {
            $gaps[] = [
                'field' => 'comparison_points',
                'reason' => 'no_explicit_comparison_points_available',
                'source_trace' => 'citizen_explanation',
            ];
        }

        return $gaps;
    }

    /**
     * Build evidence coverage summary from currently available comparison data.
     *
     * @param array $input
     * @param array $evidence_sources
     * @param array $verification_gaps
     * @return array
     */
    private function build_evidence_coverage(array $input, array $evidence_sources, array $verification_gaps) {
        $agreements_count = count($this->array_list($input['agreements'] ?? []));
        $disagreements_count = count($this->array_list($input['disagreements'] ?? []));
        $conflicting_claims_count = count($this->array_list($input['conflicting_claims'] ?? []));

        return [
            'sources_count' => count($evidence_sources),
            'agreements_count' => $agreements_count,
            'disagreements_count' => $disagreements_count,
            'conflicting_claims_count' => $conflicting_claims_count,
            'verification_gaps_count' => count($verification_gaps),
            'has_comparison_capacity' => count($evidence_sources) >= 2,
            'source_trace' => [
                'sources' => 'cross_source.evidence_sources',
                'comparison_points' => 'citizen_explanation',
            ],
        ];
    }

    /**
     * Build source-traceability metadata for downstream consumers.
     *
     * @param array $input
     * @param array $evidence_sources
     * @return array
     */
    private function build_source_traceability(array $input, array $evidence_sources) {
        $traceable_count = 0;
        $trace_map = [];

        foreach ($evidence_sources as $source) {
            if (!is_array($source)) {
                continue;
            }
            $traceable = !empty($source['traceable']);
            if ($traceable) {
                $traceable_count++;
            }
            $trace_map[] = [
                'source_key' => $this->string_value($source, 'source_key', ''),
                'traceable' => $traceable,
                'source_trace' => is_array($source['source_trace'] ?? null) ? $source['source_trace'] : [],
            ];
        }

        return [
            'traceable_sources_count' => $traceable_count,
            'total_sources_count' => count($evidence_sources),
            'traceability_ratio' => count($evidence_sources) > 0 ? round($traceable_count / count($evidence_sources), 3) : 0.0,
            'sources' => $trace_map,
            'source_trace' => [
                'evidence_sources' => 'cross_source.evidence_sources',
            ],
        ];
    }

    /**
     * Build deterministic comparison confidence while preserving upstream confidence.
     *
     * @param array $input
     * @param array $evidence_sources
     * @param array $agreements
     * @param array $disagreements
     * @param array $verification_gaps
     * @param array $source_traceability
     * @return array
     */
    private function build_confidence(
        array $input,
        array $evidence_sources,
        array $agreements,
        array $disagreements,
        array $verification_gaps,
        array $source_traceability
    ) {
        $upstream_confidence = $this->clamp01($input['explanation_confidence']['value'] ?? 0);
        $traceability_confidence = $this->clamp01($source_traceability['traceability_ratio'] ?? 0);
        $capacity_confidence = count($evidence_sources) >= 2 ? 1.0 : 0.0;

        $point_count = count($agreements) + count($disagreements);
        $comparison_points_confidence = $point_count > 0 ? 1.0 : 0.0;
        $gap_penalty = count($verification_gaps) > 0 ? 0.0 : 1.0;

        $value = round((
            $upstream_confidence +
            $traceability_confidence +
            $capacity_confidence +
            $comparison_points_confidence +
            $gap_penalty
        ) / 5, 3);

        return [
            'value' => $value,
            'sources' => [
                'upstream_explanation_confidence' => $upstream_confidence,
                'traceability_confidence' => $traceability_confidence,
                'source_capacity_confidence' => $capacity_confidence,
                'comparison_points_confidence' => $comparison_points_confidence,
                'verification_gap_penalty_component' => $gap_penalty,
            ],
            'source_trace' => [
                'upstream_explanation_confidence' => 'citizen_explanation.explanation_confidence.value',
                'traceability_confidence' => 'cross_source.source_traceability.traceability_ratio',
                'source_capacity_confidence' => 'cross_source.evidence_sources',
                'comparison_points_confidence' => 'cross_source.agreements|cross_source.disagreements',
                'verification_gap_penalty_component' => 'cross_source.verification_gaps',
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
     * Normalize unknown input into an array list.
     *
     * @param mixed $value
     * @return array
     */
    private function array_list($value) {
        return is_array($value) ? array_values($value) : [];
    }

    /**
     * Normalize unknown input into a string list.
     *
     * @param mixed $value
     * @return array
     */
    private function string_list($value) {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $text = sanitize_text_field((string) $item);
            if ($text !== '') {
                $out[] = $text;
            }
        }
        return array_values(array_unique($out));
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