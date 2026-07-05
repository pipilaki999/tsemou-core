<?php
namespace TSEMOU\Modules\EventIntelligence;

if (!defined('ABSPATH')) exit;

use TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph;

class Event_Graph_Adapter {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function apply(array $state = []) {
        $story_id = intval($state['story']['id'] ?? 0);
        $evidence_id = intval($state['evidence']['id'] ?? 0);
        $company_ids = array_values(array_unique(array_map('intval', is_array($state['story']['company_ids'] ?? null) ? $state['story']['company_ids'] : [])));
        $confidence = max(0, min(100, intval(round(floatval($state['resolver_result']['confidence'] ?? 0.5) * 100))));
        $policy_decision = sanitize_text_field($state['policy_decisions']['decision'] ?? 'review');
        $ranking_priority = sanitize_text_field($state['story_ranking']['priority'] ?? 'low');

        $relationships = [];
        if (!$story_id || !class_exists('TSEMOU\\Modules\\KnowledgeGraph\\Knowledge_Graph')) {
            return [
                'relationships' => $relationships,
                'count' => 0,
                'confidence' => 0.2,
            ];
        }

        $relationships[] = Knowledge_Graph::add_relationship(
            $story_id,
            $story_id,
            'context_only',
            max(50, $confidence),
            ['context'],
            'Event intelligence pipeline context anchor.'
        );

        if ($evidence_id > 0) {
            $relationships[] = Knowledge_Graph::add_relationship(
                $story_id,
                $evidence_id,
                'context_only',
                max(50, $confidence),
                ['context'],
                'Event intelligence linked evidence to the event context.'
            );
        }

        foreach ($company_ids as $company_id) {
            $flags = ['context'];
            if ($evidence_id > 0 && $policy_decision !== 'low_priority') {
                $flags[] = 'trust_relevant';
            }
            if ($ranking_priority === 'high') {
                $flags[] = 'influence';
            }
            $relationships[] = Knowledge_Graph::add_relationship(
                $story_id,
                $company_id,
                'associated_with',
                max(50, $confidence),
                $flags,
                'Event intelligence linked the story event to a connected company.'
            );
        }

        return [
            'relationships' => $relationships,
            'count' => count($relationships),
            'confidence' => $confidence / 100,
        ];
    }
}