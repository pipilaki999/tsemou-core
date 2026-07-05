<?php
namespace TSEMOU\Modules\EventIntelligence;

if (!defined('ABSPATH')) exit;

use TSEMOU\Modules\PolicyEngine\Policy_Engine;

class Event_Policy_Adapter {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function evaluate(array $state = []) {
        $identity_confidence = $this->clamp01($state['event_identity']['confidence'] ?? 0.0);
        $resolver_confidence = $this->clamp01($state['resolver_result']['confidence'] ?? 0.0);
        $resolver_action = sanitize_text_field($state['resolver_result']['action'] ?? 'NEW_EVENT');
        $evidence_present = intval($state['evidence']['id'] ?? 0) > 0;
        $identity_threshold = class_exists('TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')
            ? floatval(Policy_Engine::get('event.identity.min_confidence', 0.6))
            : 0.6;
        $resolver_threshold = class_exists('TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')
            ? floatval(Policy_Engine::get('event.resolver.min_confidence', 0.55))
            : 0.55;

        $gates = [
            [
                'gate' => 'evidence_required',
                'passed' => $evidence_present,
                'expected' => true,
                'actual' => $evidence_present,
            ],
            [
                'gate' => 'identity_confidence',
                'passed' => $identity_confidence >= $identity_threshold,
                'expected' => $identity_threshold,
                'actual' => $identity_confidence,
            ],
            [
                'gate' => 'resolver_confidence',
                'passed' => $resolver_confidence >= $resolver_threshold,
                'expected' => $resolver_threshold,
                'actual' => $resolver_confidence,
            ],
        ];

        $decision = 'review';
        if (!$evidence_present || in_array($resolver_action, ['DUPLICATE', 'MERGE'], true)) {
            $decision = 'low_priority';
        } elseif ($identity_confidence >= $identity_threshold && $resolver_confidence >= $resolver_threshold) {
            $decision = 'actionable';
        }

        return [
            'decision' => $decision,
            'action' => $resolver_action,
            'thresholds' => [
                'identity_confidence' => $identity_threshold,
                'resolver_confidence' => $resolver_threshold,
            ],
            'gates' => $gates,
            'confidence' => round(max($identity_confidence, $resolver_confidence), 3),
        ];
    }

    private function clamp01($value) {
        return max(0.0, min(1.0, floatval($value)));
    }
}