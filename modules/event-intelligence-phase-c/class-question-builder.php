<?php
namespace TSEMOU\Modules\EventIntelligencePhaseC;

if (!defined('ABSPATH')) exit;

/**
 * Phase C Question Builder skeleton.
 */
class Question_Builder {
    /** @var Question_Builder|null */
    private static $instance = null;

    /**
     * Singleton accessor.
     *
     * @return Question_Builder
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
     * Build question stage payload.
     *
     * Expected input is the complete Cross Source Analysis object.
     * This method generates only structured, traceable questions that map to
     * already-identified information gaps and comparison states.
     *
     * @param array $input
     * @return array
     */
    public function build(array $input = []) {
        $key_questions = $this->build_key_questions($input);
        $unanswered_questions = $this->build_unanswered_questions($input);
        $evidence_questions = $this->build_evidence_questions($input);
        $timeline_questions = $this->build_timeline_questions($input);
        $investigation_questions = $this->build_investigation_questions($input);
        $citizen_questions = $this->build_citizen_questions(
            $input,
            $key_questions,
            $unanswered_questions,
            $evidence_questions,
            $timeline_questions,
            $investigation_questions
        );
        $question_traceability = $this->build_traceability(
            $input,
            $key_questions,
            $unanswered_questions,
            $evidence_questions,
            $timeline_questions,
            $investigation_questions,
            $citizen_questions
        );

        return [
            'key_questions' => $key_questions,
            'unanswered_questions' => $unanswered_questions,
            'evidence_questions' => $evidence_questions,
            'timeline_questions' => $timeline_questions,
            'investigation_questions' => $investigation_questions,
            'citizen_questions' => $citizen_questions,
            'question_traceability' => $question_traceability,
            'question_confidence' => $this->build_confidence(
                $input,
                $key_questions,
                $unanswered_questions,
                $question_traceability
            ),
        ];
    }

    /**
     * Build key questions from explicit verification gaps and conflicts.
     *
     * @param array $input
     * @return array
     */
    private function build_key_questions(array $input) {
        $questions = [];
        $verification_gaps = $this->array_list($input['verification_gaps'] ?? []);
        $conflicting_claims = $this->array_list($input['conflicting_claims'] ?? []);

        foreach ($verification_gaps as $gap) {
            if (!is_array($gap)) {
                continue;
            }
            $field = $this->string_value($gap, 'field', 'unknown_field');
            $reason = $this->string_value($gap, 'reason', 'unknown_reason');
            $questions[] = [
                'question_key' => 'key_gap_' . sanitize_key($field),
                'question' => 'What verified information is still required for ' . $field . '?',
                'gap_field' => $field,
                'gap_reason' => $reason,
                'source_trace' => $this->string_value($gap, 'source_trace', 'cross_source.verification_gaps'),
            ];
        }

        foreach ($conflicting_claims as $claim) {
            if (!is_array($claim)) {
                continue;
            }
            $claim_text = $this->string_value($claim, 'claim', 'unidentified_claim');
            if ($claim_text === '') {
                continue;
            }
            $questions[] = [
                'question_key' => 'key_conflict_' . sanitize_key($claim_text),
                'question' => 'Which evaluated evidence points currently support or contradict this claim?',
                'claim' => $claim_text,
                'verification_state' => $this->string_value($claim, 'verification_state', ''),
                'source_trace' => $this->string_value($claim, 'source_trace', 'cross_source.conflicting_claims'),
            ];
        }

        return $this->unique_questions($questions);
    }

    /**
     * Build unanswered questions directly from unresolved verification gaps.
     *
     * @param array $input
     * @return array
     */
    private function build_unanswered_questions(array $input) {
        $questions = [];
        $verification_gaps = $this->array_list($input['verification_gaps'] ?? []);

        foreach ($verification_gaps as $gap) {
            if (!is_array($gap)) {
                continue;
            }
            $field = $this->string_value($gap, 'field', 'unknown_field');
            $reason = $this->string_value($gap, 'reason', 'unknown_reason');
            $questions[] = [
                'question_key' => 'unanswered_' . sanitize_key($field),
                'question' => 'What remains unresolved about ' . $field . '?',
                'unresolved_field' => $field,
                'reason' => $reason,
                'source_trace' => $this->string_value($gap, 'source_trace', 'cross_source.verification_gaps'),
            ];
        }

        return $this->unique_questions($questions);
    }

    /**
     * Build evidence-focused questions from source and conflict structures.
     *
     * @param array $input
     * @return array
     */
    private function build_evidence_questions(array $input) {
        $questions = [];
        $evidence_sources = $this->array_list($input['evidence_sources'] ?? []);
        $conflicting_claims = $this->array_list($input['conflicting_claims'] ?? []);

        foreach ($evidence_sources as $source) {
            if (!is_array($source)) {
                continue;
            }
            $source_key = $this->string_value($source, 'source_key', 'unknown_source');
            $traceable = !empty($source['traceable']);
            $questions[] = [
                'question_key' => 'evidence_' . sanitize_key($source_key),
                'question' => $traceable
                    ? 'Which existing evaluated claims currently reference this evidence source?'
                    : 'What is missing to make this evidence source traceable?',
                'source_key' => $source_key,
                'traceable' => $traceable,
                'source_trace' => 'cross_source.evidence_sources',
            ];
        }

        foreach ($conflicting_claims as $claim) {
            if (!is_array($claim)) {
                continue;
            }
            $claim_text = $this->string_value($claim, 'claim', 'unidentified_claim');
            $questions[] = [
                'question_key' => 'evidence_conflict_' . sanitize_key($claim_text),
                'question' => 'Which source references are currently attached to this conflicting claim?',
                'claim' => $claim_text,
                'source_refs' => $this->string_list($claim['source_refs'] ?? []),
                'source_trace' => $this->string_value($claim, 'source_trace', 'cross_source.conflicting_claims'),
            ];
        }

        return $this->unique_questions($questions);
    }

    /**
     * Build timeline-focused questions from unresolved timeline-related gaps.
     *
     * @param array $input
     * @return array
     */
    private function build_timeline_questions(array $input) {
        $questions = [];
        $verification_gaps = $this->array_list($input['verification_gaps'] ?? []);

        foreach ($verification_gaps as $gap) {
            if (!is_array($gap)) {
                continue;
            }
            $field = $this->string_value($gap, 'field', '');
            if (stripos($field, 'timeline') === false && stripos($field, 'timestamp') === false) {
                continue;
            }

            $questions[] = [
                'question_key' => 'timeline_' . sanitize_key($field),
                'question' => 'What verified timeline data is still missing for ' . $field . '?',
                'timeline_field' => $field,
                'reason' => $this->string_value($gap, 'reason', ''),
                'source_trace' => $this->string_value($gap, 'source_trace', 'cross_source.verification_gaps'),
            ];
        }

        return $this->unique_questions($questions);
    }

    /**
     * Build investigation questions from disagreements and unresolved gaps.
     *
     * @param array $input
     * @return array
     */
    private function build_investigation_questions(array $input) {
        $questions = [];
        $disagreements = $this->array_list($input['disagreements'] ?? []);
        $verification_gaps = $this->array_list($input['verification_gaps'] ?? []);

        foreach ($disagreements as $disagreement) {
            if (!is_array($disagreement)) {
                continue;
            }
            $topic = $this->string_value($disagreement, 'topic', 'unknown_topic');
            $questions[] = [
                'question_key' => 'investigation_disagreement_' . sanitize_key($topic),
                'question' => 'What additional verified comparison inputs are required to clarify this disagreement?',
                'topic' => $topic,
                'difference' => $this->string_value($disagreement, 'difference', ''),
                'source_refs' => $this->string_list($disagreement['source_refs'] ?? []),
                'source_trace' => $this->string_value($disagreement, 'source_trace', 'cross_source.disagreements'),
            ];
        }

        foreach ($verification_gaps as $gap) {
            if (!is_array($gap)) {
                continue;
            }
            $field = $this->string_value($gap, 'field', 'unknown_field');
            $questions[] = [
                'question_key' => 'investigation_gap_' . sanitize_key($field),
                'question' => 'Which investigation step is needed to resolve this verification gap?',
                'gap_field' => $field,
                'reason' => $this->string_value($gap, 'reason', ''),
                'source_trace' => $this->string_value($gap, 'source_trace', 'cross_source.verification_gaps'),
            ];
        }

        return $this->unique_questions($questions);
    }

    /**
     * Build citizen-facing question set from the already-built question groups.
     *
     * @param array $input
     * @param array $key_questions
     * @param array $unanswered_questions
     * @param array $evidence_questions
     * @param array $timeline_questions
     * @param array $investigation_questions
     * @return array
     */
    private function build_citizen_questions(
        array $input,
        array $key_questions,
        array $unanswered_questions,
        array $evidence_questions,
        array $timeline_questions,
        array $investigation_questions
    ) {
        $citizen_questions = [];

        foreach ([$key_questions, $unanswered_questions, $evidence_questions, $timeline_questions, $investigation_questions] as $group) {
            foreach ($group as $question) {
                if (!is_array($question)) {
                    continue;
                }
                $citizen_questions[] = [
                    'question_key' => $this->string_value($question, 'question_key', ''),
                    'question' => $this->string_value($question, 'question', ''),
                    'category' => $this->derive_category($question),
                    'source_trace' => $this->string_value($question, 'source_trace', ''),
                ];
            }
        }

        if (empty($citizen_questions)) {
            $citizen_questions[] = [
                'question_key' => 'uncertainty_insufficient_information',
                'question' => 'Which additional verified inputs are required before new public questions can be formed?',
                'category' => 'uncertainty',
                'source_trace' => 'cross_source.verification_gaps',
            ];
        }

        return $this->unique_questions($citizen_questions);
    }

    /**
     * Build question traceability package for downstream integrations.
     *
     * @param array $input
     * @param array $key_questions
     * @param array $unanswered_questions
     * @param array $evidence_questions
     * @param array $timeline_questions
     * @param array $investigation_questions
     * @param array $citizen_questions
     * @return array
     */
    private function build_traceability(
        array $input,
        array $key_questions,
        array $unanswered_questions,
        array $evidence_questions,
        array $timeline_questions,
        array $investigation_questions,
        array $citizen_questions
    ) {
        return [
            'counts' => [
                'key_questions' => count($key_questions),
                'unanswered_questions' => count($unanswered_questions),
                'evidence_questions' => count($evidence_questions),
                'timeline_questions' => count($timeline_questions),
                'investigation_questions' => count($investigation_questions),
                'citizen_questions' => count($citizen_questions),
            ],
            'source_trace' => [
                'evidence_sources' => 'cross_source.evidence_sources',
                'agreements' => 'cross_source.agreements',
                'disagreements' => 'cross_source.disagreements',
                'conflicting_claims' => 'cross_source.conflicting_claims',
                'verification_gaps' => 'cross_source.verification_gaps',
                'comparison_confidence' => 'cross_source.comparison_confidence',
            ],
            'question_map' => $this->build_question_map($citizen_questions),
        ];
    }

    /**
     * Build deterministic confidence for the question payload.
     *
     * @param array $input
     * @param array $key_questions
     * @param array $unanswered_questions
     * @param array $traceability
     * @return array
     */
    private function build_confidence(array $input, array $key_questions, array $unanswered_questions, array $traceability) {
        $upstream = $this->clamp01($input['comparison_confidence']['value'] ?? 0);
        $traceability_component = count($traceability['question_map'] ?? []) > 0 ? 1.0 : 0.0;
        $gap_component = count($unanswered_questions) > 0 ? 1.0 : 0.0;
        $coverage_component = count($key_questions) > 0 ? 1.0 : 0.0;

        $value = round(($upstream + $traceability_component + $gap_component + $coverage_component) / 4, 3);

        return [
            'value' => $value,
            'sources' => [
                'cross_source_comparison_confidence' => $upstream,
                'question_traceability_component' => $traceability_component,
                'unanswered_question_component' => $gap_component,
                'key_question_component' => $coverage_component,
            ],
            'source_trace' => [
                'cross_source_comparison_confidence' => 'cross_source.comparison_confidence.value',
                'question_traceability_component' => 'question_builder.question_traceability.question_map',
                'unanswered_question_component' => 'question_builder.unanswered_questions',
                'key_question_component' => 'question_builder.key_questions',
            ],
        ];
    }

    /**
     * Derive a deterministic category label from question structure.
     *
     * @param array $question
     * @return string
     */
    private function derive_category(array $question) {
        if (!empty($question['gap_field']) || !empty($question['unresolved_field'])) {
            return 'uncertainty';
        }
        if (!empty($question['timeline_field'])) {
            return 'timeline';
        }
        if (!empty($question['source_key']) || !empty($question['source_refs'])) {
            return 'evidence';
        }
        if (!empty($question['topic']) || !empty($question['difference'])) {
            return 'investigation';
        }
        return 'general';
    }

    /**
     * Build question map for downstream API/UI/workspace integrations.
     *
     * @param array $citizen_questions
     * @return array
     */
    private function build_question_map(array $citizen_questions) {
        $map = [];
        foreach ($citizen_questions as $question) {
            if (!is_array($question)) {
                continue;
            }
            $key = $this->string_value($question, 'question_key', '');
            if ($key === '') {
                continue;
            }
            $map[$key] = [
                'category' => $this->string_value($question, 'category', ''),
                'source_trace' => $this->string_value($question, 'source_trace', ''),
            ];
        }
        return $map;
    }

    /**
     * Ensure unique question entries by question_key.
     *
     * @param array $questions
     * @return array
     */
    private function unique_questions(array $questions) {
        $out = [];
        $seen = [];

        foreach ($questions as $question) {
            if (!is_array($question)) {
                continue;
            }
            $key = $this->string_value($question, 'question_key', '');
            if ($key === '') {
                continue;
            }
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $question;
        }

        return $out;
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