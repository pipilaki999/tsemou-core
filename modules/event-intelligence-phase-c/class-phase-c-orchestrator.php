<?php
namespace TSEMOU\Modules\EventIntelligencePhaseC;

if (!defined('ABSPATH')) exit;

/**
 * Phase C orchestrator skeleton.
 */
class Phase_C_Orchestrator {
    /** @var Phase_C_Orchestrator|null */
    private static $instance = null;

    /**
     * Singleton accessor.
     *
     * @return Phase_C_Orchestrator
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
        * Execute the deterministic Phase C pipeline.
     *
     * Story Builder
     * ↓
     * Living Story Engine
     * ↓
     * Citizen Explanation Engine
     * ↓
     * Cross Source Analysis
     * ↓
     * Question Builder
     * ↓
     * AI Summary Adapter
     *
     * @param array $input
     * @return Phase_C_Result
     */
    public function run(array $input = []) {
        $result = [
            'status' => 'failed',
            'story_builder' => [],
            'living_story' => [],
            'citizen_explanation' => [],
            'cross_source_analysis' => [],
            'questions' => [],
            'ai_summary_adapter' => [],
            'confidence' => 0.0,
            'failed_stage' => '',
            'error' => '',
            'pipeline_trace' => [],
        ];

        $stages = [
            [
                'name' => 'story_builder',
                'runner' => function(array $payload) {
                    return Story_Builder::instance()->build($payload);
                },
            ],
            [
                'name' => 'living_story',
                'runner' => function(array $payload) {
                    return Living_Story_Engine::instance()->evolve($payload);
                },
            ],
            [
                'name' => 'citizen_explanation',
                'runner' => function(array $payload) {
                    return Citizen_Explanation_Engine::instance()->explain($payload);
                },
            ],
            [
                'name' => 'cross_source_analysis',
                'runner' => function(array $payload) {
                    return Cross_Source_Analysis::instance()->analyze($payload);
                },
            ],
            [
                'name' => 'questions',
                'runner' => function(array $payload) {
                    return Question_Builder::instance()->build($payload);
                },
            ],
            [
                'name' => 'ai_summary_adapter',
                'runner' => function(array $payload) {
                    return AI_Summary_Adapter::instance()->summarize($payload);
                },
            ],
        ];

        $payload = $input;
        $stage_confidences = [];

        foreach ($stages as $stage) {
            $stage_name = $stage['name'];
            $runner = $stage['runner'];

            try {
                $stage_output = $runner($payload);
            } catch (\Throwable $exception) {
                $result['failed_stage'] = $stage_name;
                $result['error'] = 'Stage exception: ' . sanitize_text_field($exception->getMessage());
                $result['pipeline_trace'][] = [
                    'stage' => $stage_name,
                    'status' => 'failed',
                    'confidence' => 0.0,
                    'error' => $result['error'],
                ];
                return new Phase_C_Result($result);
            }

            if (!is_array($stage_output)) {
                $result['failed_stage'] = $stage_name;
                $result['error'] = 'Stage returned non-array output.';
                $result['pipeline_trace'][] = [
                    'stage' => $stage_name,
                    'status' => 'failed',
                    'confidence' => 0.0,
                    'error' => $result['error'],
                ];
                return new Phase_C_Result($result);
            }

            $result[$stage_name] = $stage_output;
            $stage_confidence = $this->stage_confidence($stage_output, $stage_name);
            $stage_confidences[] = $stage_confidence;
            $result['pipeline_trace'][] = [
                'stage' => $stage_name,
                'status' => 'completed',
                'confidence' => $stage_confidence,
            ];
            $payload = $stage_output;
        }

        $result['status'] = 'completed';
        $result['failed_stage'] = '';
        $result['error'] = '';
        $result['confidence'] = $this->overall_confidence($stage_confidences);

        return new Phase_C_Result($result);
    }

    /**
     * Resolve stage confidence from known confidence keys.
     *
     * @param array $stage_output
     * @param string $stage_name
     * @return float
     */
    private function stage_confidence(array $stage_output, $stage_name) {
        $candidates = [
            $stage_output['confidence'] ?? null,
            $stage_output['explanation_confidence']['value'] ?? null,
            $stage_output['comparison_confidence']['value'] ?? null,
            $stage_output['question_confidence']['value'] ?? null,
            $stage_output['adapter_confidence']['value'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return $this->clamp01($candidate);
            }
        }

        return 0.0;
    }

    /**
     * Build deterministic overall confidence from available stage confidences.
     *
     * @param array $stage_confidences
     * @return float
     */
    private function overall_confidence(array $stage_confidences) {
        $values = [];
        foreach ($stage_confidences as $value) {
            if (is_numeric($value)) {
                $values[] = $this->clamp01($value);
            }
        }

        if (empty($values)) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($values as $value) {
            $sum += $value;
        }

        return round($sum / count($values), 3);
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