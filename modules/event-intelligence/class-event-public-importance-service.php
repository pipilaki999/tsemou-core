<?php
namespace TSEMOU\Modules\EventIntelligence;

if (!defined('ABSPATH')) exit;

use TSEMOU\Modules\ProofEngine\Proof_Engine;

class Event_Public_Importance_Service {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function evaluate(array $state = []) {
        $evidence = is_array($state['evidence'] ?? null) ? $state['evidence'] : [];
        $intelligence = is_array($evidence['intelligence'] ?? null) ? $evidence['intelligence'] : [];
        $evidence_id = intval($evidence['id'] ?? 0);
        if (empty($intelligence) && $evidence_id > 0 && class_exists('TSEMOU\\Modules\\ProofEngine\\Proof_Engine')) {
            $intelligence = Proof_Engine::get_evidence_intelligence($evidence_id);
        }

        $evidence_confidence = $this->clamp01($intelligence['confidence'] ?? 0.0);
        $impact_strength = max(0.0, min(1.0, abs(floatval($intelligence['impact'] ?? 0.0))));
        $resolver_confidence = $this->clamp01($state['resolver_result']['confidence'] ?? 0.0);
        $resolver_factor = $this->resolver_factor($state['resolver_result']['action'] ?? 'NEW_EVENT');
        $timeline_factor = $this->timeline_factor($state['timeline']['nodes'] ?? []);

        $score = round(
            ($evidence_confidence * 45) +
            ($impact_strength * 30) +
            ($resolver_factor * 15) +
            ($timeline_factor * 10),
            1
        );

        return [
            'score' => max(0.0, min(100.0, $score)),
            'confidence' => round(max(0.0, min(1.0, ($evidence_confidence * 0.7) + ($resolver_confidence * 0.3))), 3),
            'band' => $this->band_from_score($score),
            'factors' => [
                ['factor' => 'evidence_confidence', 'value' => $evidence_confidence, 'weight' => 45],
                ['factor' => 'impact_strength', 'value' => $impact_strength, 'weight' => 30],
                ['factor' => 'resolver_action', 'value' => $resolver_factor, 'weight' => 15],
                ['factor' => 'timeline_state', 'value' => $timeline_factor, 'weight' => 10],
            ],
        ];
    }

    private function resolver_factor($action) {
        $map = [
            'NEW_EVENT' => 1.0,
            'UPDATE' => 0.85,
            'CORRECTION' => 0.55,
            'MERGE' => 0.4,
            'DUPLICATE' => 0.2,
        ];
        $action = sanitize_text_field($action);
        return floatval($map[$action] ?? 0.5);
    }

    private function timeline_factor($nodes) {
        $nodes = is_array($nodes) ? $nodes : [];
        if (empty($nodes)) {
            return 0.3;
        }
        $first = sanitize_text_field($nodes[0]['action'] ?? 'follow_up');
        $map = [
            'first_report' => 1.0,
            'update' => 0.8,
            'correction' => 0.55,
            'merge' => 0.4,
            'duplicate' => 0.2,
        ];
        return floatval($map[$first] ?? 0.5);
    }

    private function band_from_score($score) {
        if ($score >= 75) {
            return 'high';
        }
        if ($score >= 45) {
            return 'medium';
        }
        return 'low';
    }

    private function clamp01($value) {
        return max(0.0, min(1.0, floatval($value)));
    }
}