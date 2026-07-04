<?php
namespace TSEMOU\Modules\EventIntelligence;

if (!defined('ABSPATH')) exit;

use TSEMOU\Modules\EvidenceEngine\Evidence_Engine;
use TSEMOU\Modules\EventIdentity\Event_Identity_Engine;
use TSEMOU\Modules\EventResolver\Event_Resolver;
use TSEMOU\Modules\EventTimeline\Event_Timeline;
use TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph;
use TSEMOU\Modules\PolicyEngine\Policy_Engine;
use TSEMOU\Modules\ProofEngine\Proof_Engine;
use TSEMOU\Modules\TrustEngine\Trust_Engine;

class Event_Intelligence_Orchestrator {
    private static $instance = null;
    private $services;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct(array $services = []) {
        $this->services = $services;
    }

    public function run(array $input = []) {
        $input = is_array($input) ? $input : [];
        $state = [
            'story' => null,
            'event_identity' => null,
            'evidence' => null,
            'resolver_result' => null,
            'timeline' => null,
            'policy_decisions' => null,
            'graph_updates' => null,
            'confidence' => 0.0,
            'failed_stage' => '',
            'error' => '',
        ];

        foreach (['story', 'evidence', 'policy', 'importance', 'trust', 'event_identity', 'resolver', 'timeline', 'graph'] as $stage) {
            $result = $this->execute_stage($stage, $input, $state);
            if (!$result['ok']) {
                $state['failed_stage'] = $stage;
                $state['error'] = $result['message'] ?? 'Stage failed.';
                $state['status'] = 'failed';
                return new Event_Intelligence_Result($state);
            }

            if ($stage === 'story') {
                $state['story'] = $result['data'];
            } elseif ($stage === 'evidence') {
                $state['evidence'] = $result['data'];
            } elseif ($stage === 'policy') {
                $state['policy_decisions'] = $result['data'];
            } elseif ($stage === 'importance') {
                $state['importance'] = $result['data'];
            } elseif ($stage === 'trust') {
                $state['trust'] = $result['data'];
            } elseif ($stage === 'event_identity') {
                $state['event_identity'] = $result['data'];
            } elseif ($stage === 'resolver') {
                $state['resolver_result'] = $result['data'];
            } elseif ($stage === 'timeline') {
                $state['timeline'] = $result['data'];
            } elseif ($stage === 'graph') {
                $state['graph_updates'] = $result['data'];
            }

            $state['confidence'] = max($state['confidence'], floatval($result['confidence'] ?? 0.0));
            $state['status'] = 'completed';
        }

        return new Event_Intelligence_Result($state);
    }

    private function execute_stage($stage, array $input, array $state) {
        if (!empty($this->services[$stage])) {
            $callable = $this->services[$stage];
            if (is_callable($callable)) {
                return call_user_func($callable, $input, $state);
            }
        }

        switch ($stage) {
            case 'story':
                return $this->default_story_stage($input);
            case 'evidence':
                return $this->default_evidence_stage($input, $state);
            case 'policy':
                return $this->default_policy_stage($input, $state);
            case 'importance':
                return $this->default_importance_stage($input, $state);
            case 'trust':
                return $this->default_trust_stage($input, $state);
            case 'event_identity':
                return $this->default_event_identity_stage($input, $state);
            case 'resolver':
                return $this->default_resolver_stage($input, $state);
            case 'timeline':
                return $this->default_timeline_stage($input, $state);
            case 'graph':
                return $this->default_graph_stage($input, $state);
            default:
                return ['ok' => true, 'data' => [], 'confidence' => 0.0];
        }
    }

    private function default_story_stage(array $input) {
        $story_id = absint($input['story_id'] ?? 0);
        if (!$story_id) {
            return ['ok' => false, 'message' => 'Missing story id.'];
        }

        $story = get_post($story_id);
        if (!$story || $story->post_type !== 'story') {
            return ['ok' => false, 'message' => 'Story not found.'];
        }

        return ['ok' => true, 'data' => [
            'id' => $story->ID,
            'title' => get_the_title($story->ID),
            'status' => $story->post_status,
            'content' => $story->post_content,
        ], 'confidence' => 0.6];
    }

    private function default_evidence_stage(array $input, array $state) {
        $story = $state['story'] ?? [];
        if (empty($story['id'])) {
            return ['ok' => false, 'message' => 'Story context missing for evidence.'];
        }

        if (class_exists('\TSEMOU\Modules\EvidenceEngine\Evidence_Engine')) {
            $evidence_id = 0;
            $post = get_post($story['id']);
            if ($post) {
                $evidence_id = absint(get_post_meta($story['id'], '_tsemou_evidence_id', true));
            }
            if (!$evidence_id) {
                return ['ok' => true, 'data' => ['id' => 0, 'summary' => 'No evidence linked.'], 'confidence' => 0.2];
            }
            return ['ok' => true, 'data' => Evidence_Engine::get($evidence_id), 'confidence' => 0.5];
        }

        return ['ok' => false, 'message' => 'Evidence engine unavailable.'];
    }

    private function default_policy_stage(array $input, array $state) {
        if (class_exists('TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')) {
            $decision = [
                'decision' => 'review',
                'threshold' => floatval(Policy_Engine::get('event.identity.min_confidence', 0.6)),
            ];
            return ['ok' => true, 'data' => $decision, 'confidence' => 0.6];
        }

        return ['ok' => false, 'message' => 'Policy engine unavailable.'];
    }

    private function default_importance_stage(array $input, array $state) {
        $story = $state['story'] ?? [];
        if (empty($story['id'])) {
            return ['ok' => false, 'message' => 'Story context missing for importance evaluation.'];
        }

        $evidence = $state['evidence'] ?? [];
        $evidence_id = absint($evidence['id'] ?? 0);
        if ($evidence_id && class_exists('TSEMOU\\Modules\\ProofEngine\\Proof_Engine')) {
            $intelligence = Proof_Engine::get_evidence_intelligence($evidence_id);
            return ['ok' => true, 'data' => $intelligence, 'confidence' => floatval($intelligence['confidence'] ?? 0.0)];
        }

        return ['ok' => true, 'data' => ['impact' => 0.0, 'confidence' => 0.0], 'confidence' => 0.0];
    }

    private function default_trust_stage(array $input, array $state) {
        $story = $state['story'] ?? [];
        if (empty($story['id'])) {
            return ['ok' => false, 'message' => 'Story context missing for trust evaluation.'];
        }

        if (class_exists('TSEMOU\\Modules\\TrustEngine\\Trust_Engine')) {
            $company_ids = [];
            if (class_exists('TSEMOU\\Modules\\CompanyEngine\\Company_Engine')) {
                $company_ids = \TSEMOU\Modules\CompanyEngine\Company_Engine::get_connected_company_ids(intval($story['id']));
            }
            $score = 0.0;
            foreach ($company_ids as $company_id) {
                $score = max($score, floatval(Trust_Engine::recalculate_company_trust(intval($company_id))));
            }
            return ['ok' => true, 'data' => ['company_ids' => $company_ids, 'score' => $score], 'confidence' => 0.5];
        }

        return ['ok' => false, 'message' => 'Trust engine unavailable.'];
    }

    private function default_event_identity_stage(array $input, array $state) {
        if (class_exists('\TSEMOU\Modules\EventIdentity\Event_Identity_Engine')) {
            $story_id = intval($state['story']['id'] ?? ($input['story_id'] ?? 0));
            $result = Event_Identity_Engine::instance()->analyze_story($story_id);
            return ['ok' => true, 'data' => $result, 'confidence' => 0.7];
        }

        return ['ok' => false, 'message' => 'Event identity engine unavailable.'];
    }

    private function default_resolver_stage(array $input, array $state) {
        $payload = [
            'story_id' => intval($state['story']['id'] ?? ($input['story_id'] ?? 0)),
            'title' => $state['story']['title'] ?? ($input['title'] ?? ''),
            'summary' => $state['story']['content'] ?? '',
        ];

        if (class_exists('\TSEMOU\Modules\EventResolver\Event_Resolver')) {
            $result = Event_Resolver::instance()->resolve($payload);
            return ['ok' => true, 'data' => $result, 'confidence' => floatval($result['confidence'] ?? 0.0)];
        }

        return ['ok' => false, 'message' => 'Event resolver unavailable.'];
    }

    private function default_timeline_stage(array $input, array $state) {
        $payload = array_merge($state['resolver_result'] ?? [], [
            'event_id' => $state['resolver_result']['signature'] ?? 'evt-default',
            'title' => $state['story']['title'] ?? '',
            'summary' => $state['story']['content'] ?? '',
        ]);

        if (class_exists('\TSEMOU\Modules\EventTimeline\Event_Timeline')) {
            $result = Event_Timeline::instance()->build($payload);
            return ['ok' => true, 'data' => $result, 'confidence' => 0.65];
        }

        return ['ok' => false, 'message' => 'Event timeline unavailable.'];
    }

    private function default_graph_stage(array $input, array $state) {
        if (class_exists('\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph')) {
            $updates = [];
            if (!empty($state['story']['id'])) {
                $updates[] = Knowledge_Graph::add_relationship(
                    intval($state['story']['id']),
                    intval($state['story']['id']),
                    'context_only',
                    50,
                    ['context'],
                    'Event intelligence orchestrator generated a context relationship.'
                );
            }
            return ['ok' => true, 'data' => $updates, 'confidence' => 0.6];
        }

        return ['ok' => false, 'message' => 'Knowledge graph unavailable.'];
    }
}
