<?php
namespace TSEMOU\Modules\EventIntelligencePhaseC;

if (!defined('ABSPATH')) exit;

/**
 * Phase C AI Summary Adapter skeleton.
 */
class AI_Summary_Adapter {
    /** @var AI_Summary_Adapter|null */
    private static $instance = null;

    /**
     * Singleton accessor.
     *
     * @return AI_Summary_Adapter
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
     * Build AI summary adapter stage payload.
     *
     * Expected input is the complete Question Builder output.
     * This adapter only packages deterministic intelligence for external AI
     * consumers and never performs AI generation or provider calls.
     *
     * @param array $input
     * @return array
     */
    public function summarize(array $input = []) {
        $deterministic_context = $this->build_deterministic_context($input);
        $evidence_package = $this->build_evidence_package($input);
        $explanation_package = $this->build_explanation_package($input);
        $question_package = $this->build_question_package($input);
        $traceability_package = $this->build_traceability_package($input);
        $allowed_ai_operations = $this->build_allowed_operations();
        $forbidden_ai_operations = $this->build_forbidden_operations();

        return [
            'ai_payload' => $this->build_ai_payload(
                $input,
                $deterministic_context,
                $evidence_package,
                $explanation_package,
                $question_package,
                $traceability_package,
                $allowed_ai_operations,
                $forbidden_ai_operations
            ),
            'deterministic_context' => $deterministic_context,
            'evidence_package' => $evidence_package,
            'explanation_package' => $explanation_package,
            'question_package' => $question_package,
            'traceability_package' => $traceability_package,
            'allowed_ai_operations' => $allowed_ai_operations,
            'forbidden_ai_operations' => $forbidden_ai_operations,
            'adapter_confidence' => $this->build_confidence($input),
        ];
    }

    /**
     * Build top-level provider-agnostic AI payload.
     *
     * @param array $input
     * @param array $deterministic_context
     * @param array $evidence_package
     * @param array $explanation_package
     * @param array $question_package
     * @param array $traceability_package
     * @param array $allowed_ai_operations
     * @param array $forbidden_ai_operations
     * @return array
     */
    private function build_ai_payload(
        array $input,
        array $deterministic_context,
        array $evidence_package,
        array $explanation_package,
        array $question_package,
        array $traceability_package,
        array $allowed_ai_operations,
        array $forbidden_ai_operations
    ) {
        return [
            'schema_version' => 'phase_c_ai_adapter_v1',
            'provider_target' => 'agnostic',
            'deterministic_context' => $deterministic_context,
            'evidence_package' => $evidence_package,
            'explanation_package' => $explanation_package,
            'question_package' => $question_package,
            'traceability_package' => $traceability_package,
            'allowed_ai_operations' => $allowed_ai_operations,
            'forbidden_ai_operations' => $forbidden_ai_operations,
            'uncertainty_preserved' => count($this->array_list($input['unanswered_questions'] ?? [])) > 0,
            'confidence_passthrough' => $this->build_confidence($input),
        ];
    }

    /**
     * Build deterministic execution context for downstream AI consumers.
     *
     * @param array $input
     * @return array
     */
    private function build_deterministic_context(array $input) {
        $traceability = is_array($input['question_traceability'] ?? null) ? $input['question_traceability'] : [];
        $counts = is_array($traceability['counts'] ?? null) ? $traceability['counts'] : [];

        return [
            'engine' => 'AI_Summary_Adapter',
            'stage' => 'phase_c_engine_6',
            'deterministic_only' => true,
            'ai_generation_performed' => false,
            'question_counts' => [
                'key_questions' => intval($counts['key_questions'] ?? 0),
                'unanswered_questions' => intval($counts['unanswered_questions'] ?? 0),
                'evidence_questions' => intval($counts['evidence_questions'] ?? 0),
                'timeline_questions' => intval($counts['timeline_questions'] ?? 0),
                'investigation_questions' => intval($counts['investigation_questions'] ?? 0),
                'citizen_questions' => intval($counts['citizen_questions'] ?? 0),
            ],
        ];
    }

    /**
     * Build evidence package from question groups tied to evidence context.
     *
     * @param array $input
     * @return array
     */
    private function build_evidence_package(array $input) {
        $evidence_questions = $this->array_list($input['evidence_questions'] ?? []);
        $evidence_trace_refs = [];

        foreach ($evidence_questions as $question) {
            if (!is_array($question)) {
                continue;
            }
            $trace = $this->string_value($question, 'source_trace', '');
            if ($trace !== '') {
                $evidence_trace_refs[] = $trace;
            }
        }

        return [
            'evidence_questions' => $evidence_questions,
            'trace_references' => array_values(array_unique($evidence_trace_refs)),
            'immutable' => true,
        ];
    }

    /**
     * Build explanation package from key and unanswered question sets.
     *
     * @param array $input
     * @return array
     */
    private function build_explanation_package(array $input) {
        return [
            'key_questions' => $this->array_list($input['key_questions'] ?? []),
            'unanswered_questions' => $this->array_list($input['unanswered_questions'] ?? []),
            'uncertainty_preserved' => count($this->array_list($input['unanswered_questions'] ?? [])) > 0,
            'immutable' => true,
        ];
    }

    /**
     * Build question package for provider-independent consumption.
     *
     * @param array $input
     * @return array
     */
    private function build_question_package(array $input) {
        return [
            'timeline_questions' => $this->array_list($input['timeline_questions'] ?? []),
            'investigation_questions' => $this->array_list($input['investigation_questions'] ?? []),
            'citizen_questions' => $this->array_list($input['citizen_questions'] ?? []),
            'immutable' => true,
        ];
    }

    /**
     * Build traceability package from question traceability mappings.
     *
     * @param array $input
     * @return array
     */
    private function build_traceability_package(array $input) {
        $traceability = is_array($input['question_traceability'] ?? null) ? $input['question_traceability'] : [];

        return [
            'counts' => is_array($traceability['counts'] ?? null) ? $traceability['counts'] : [],
            'source_trace' => is_array($traceability['source_trace'] ?? null) ? $traceability['source_trace'] : [],
            'question_map' => is_array($traceability['question_map'] ?? null) ? $traceability['question_map'] : [],
            'immutable' => true,
        ];
    }

    /**
     * Build whitelist of AI operations permitted by adapter boundaries.
     *
     * @return array
     */
    private function build_allowed_operations() {
        return [
            'explain_deterministic_outputs',
            'improve_readability_without_semantic_change',
            'simplify_language_without_semantic_change',
            'translate_without_semantic_change',
            'adapt_style_without_semantic_change',
        ];
    }

    /**
     * Build explicit blacklist of forbidden AI operations.
     *
     * @return array
     */
    private function build_forbidden_operations() {
        return [
            'evaluate_evidence',
            'determine_truth',
            'change_confidence',
            'change_public_importance',
            'modify_trust',
            'modify_policy_decisions',
            'modify_rankings',
            'modify_knowledge_graph',
            'invent_facts',
        ];
    }

    /**
     * Build adapter confidence by preserving upstream question confidence values.
     *
     * @param array $input
     * @return array
     */
    private function build_confidence(array $input) {
        $upstream = is_array($input['question_confidence'] ?? null) ? $input['question_confidence'] : [];
        if (!empty($upstream)) {
            return $upstream;
        }

        return [
            'value' => 0.0,
            'sources' => [
                'question_builder_confidence_available' => 0.0,
            ],
            'source_trace' => [
                'question_builder_confidence_available' => 'question_builder.question_confidence',
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
}