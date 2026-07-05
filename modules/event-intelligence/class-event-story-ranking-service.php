<?php
namespace TSEMOU\Modules\EventIntelligence;

if (!defined('ABSPATH')) exit;

class Event_Story_Ranking_Service {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function evaluate(array $state = []) {
        $importance_score = max(0.0, min(100.0, floatval($state['importance']['score'] ?? 0.0)));
        $trust_score = max(0.0, min(10.0, floatval($state['trust']['score'] ?? 0.0)));
        $policy_decision = sanitize_text_field($state['policy_decisions']['decision'] ?? 'review');
        $resolver_action = sanitize_text_field($state['resolver_result']['action'] ?? 'NEW_EVENT');

        $policy_modifier = $this->policy_modifier($policy_decision);
        $resolver_modifier = $this->resolver_modifier($resolver_action);

        $score = round((($importance_score * 0.6) + (($trust_score * 10) * 0.2) + ($resolver_modifier * 20)) * $policy_modifier, 1);
        $confidence = round(max(0.0, min(1.0, ((floatval($state['importance']['confidence'] ?? 0.0) * 0.6) + (floatval($state['trust']['confidence'] ?? 0.0) * 0.2) + (floatval($state['policy_decisions']['confidence'] ?? 0.0) * 0.2)))), 3);

        return [
            'story_id' => intval($state['story']['id'] ?? 0),
            'score' => max(0.0, min(100.0, $score)),
            'priority' => $this->priority_from_score($score),
            'sort_key' => sprintf('%06.1f:%d', max(0.0, min(100.0, $score)), intval($state['story']['id'] ?? 0)),
            'components' => [
                'importance_score' => $importance_score,
                'trust_score' => $trust_score,
                'policy_modifier' => $policy_modifier,
                'resolver_modifier' => $resolver_modifier,
            ],
            'confidence' => $confidence,
        ];
    }

    private function policy_modifier($decision) {
        $map = [
            'actionable' => 1.0,
            'review' => 0.7,
            'low_priority' => 0.4,
        ];
        return floatval($map[$decision] ?? 0.7);
    }

    private function resolver_modifier($action) {
        $map = [
            'NEW_EVENT' => 1.0,
            'UPDATE' => 0.85,
            'CORRECTION' => 0.55,
            'MERGE' => 0.45,
            'DUPLICATE' => 0.3,
        ];
        return floatval($map[$action] ?? 0.5);
    }

    private function priority_from_score($score) {
        if ($score >= 80) {
            return 'high';
        }
        if ($score >= 50) {
            return 'medium';
        }
        return 'low';
    }
}