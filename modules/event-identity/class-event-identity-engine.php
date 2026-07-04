<?php
namespace TSEMOU\Modules\EventIdentity;

if (!defined('ABSPATH')) exit;

class Event_Identity_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public function analyze_story($story_id) {
        $story_id = absint($story_id);
        if (!$story_id) {
            return ['success' => false, 'message' => 'Missing story id.'];
        }

        $candidate = Event_Candidate::from_story_post($story_id);
        if (!$candidate) {
            return ['success' => false, 'message' => 'Story not found.'];
        }

        $matches = Event_Identity_Matcher::match($candidate, 5);
        $best_match = !empty($matches[0]) ? $matches[0] : null;
        $match_story_id = $best_match['story_id'] ?? 0;
        $match_score = isset($best_match['score']) ? floatval($best_match['score']) : 0.0;
        $is_duplicate = $match_story_id && $match_score >= 0.85;

        $confidence = 0.65;
        if ($is_duplicate) {
            $confidence = 0.92;
        } elseif ($candidate->company_ids || $candidate->company_names) {
            $confidence = 0.78;
        }

        $policy_threshold = 0.6;
        if (class_exists('\\TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')) {
            $policy_threshold = floatval(\TSEMOU\Modules\PolicyEngine\Policy_Engine::get('event.identity.min_confidence', 0.6));
        }

        $importance_score = min(1.0, 0.2 + ($candidate->company_ids ? 0.08 * min(4, count($candidate->company_ids)) : 0.0) + ($candidate->signals_count > 0 ? 0.05 * min(4, intval(ceil($candidate->signals_count / 2))) : 0.0) + ($candidate->updates_count > 0 ? 0.03 * min(3, $candidate->updates_count) : 0.0));
        $policy_decision = ($confidence >= $policy_threshold) ? 'review' : 'context_only';
        if ($importance_score >= 0.8) {
            $policy_decision = 'priority';
        }

        update_post_meta($story_id, '_tsemou_event_signature', $candidate->signature);
        update_post_meta($story_id, '_tsemou_event_identity_status', $is_duplicate ? 'duplicate' : 'analyzed');
        update_post_meta($story_id, '_tsemou_event_identity_confidence', $confidence);
        update_post_meta($story_id, '_tsemou_event_identity_match_story_id', $match_story_id);
        update_post_meta($story_id, '_tsemou_event_identity_match_score', $match_score);
        update_post_meta($story_id, '_tsemou_event_identity_importance_score', $importance_score);
        update_post_meta($story_id, '_tsemou_event_identity_policy_decision', $policy_decision);
        update_post_meta($story_id, '_tsemou_event_identity_last_analyzed', current_time('mysql'));

        if (class_exists('\\TSEMOU\\Modules\\KnowledgeGraph\\Knowledge_Graph') && !empty($candidate->company_ids)) {
            $flags = [];
            if ($importance_score >= 0.8) {
                $flags[] = 'influence';
            }
            if ($policy_decision === 'review') {
                $flags[] = 'requires_human_review';
            }
            if ($is_duplicate) {
                $flags[] = 'context';
            }

            $confidence_pct = max(35, min(100, intval(round($confidence * 100))));
            foreach ($candidate->company_ids as $company_id) {
                \TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph::add_relationship(
                    $story_id,
                    $company_id,
                    'associated_with',
                    $confidence_pct,
                    $flags,
                    'Story linked to company through deterministic event identity analysis.',
                    'active'
                );
            }
        }

        return [
            'success' => true,
            'story_id' => $story_id,
            'signature' => $candidate->signature,
            'status' => $is_duplicate ? 'duplicate' : 'analyzed',
            'confidence' => $confidence,
            'match_story_id' => $match_story_id,
            'match_score' => $match_score,
            'importance_score' => $importance_score,
            'policy_decision' => $policy_decision,
        ];
    }
}
