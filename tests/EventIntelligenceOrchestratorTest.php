<?php
require_once __DIR__ . '/bootstrap.php';

use TSEMOU\Modules\EventIntelligence\Event_Intelligence_Orchestrator;
use TSEMOU\Modules\EventIntelligence\Event_Intelligence_Result;

class EventIntelligenceOrchestratorTest extends \PHPUnit\Framework\TestCase {
    public function test_run_returns_immutable_result_with_injected_services() {
        $services = [
            'story' => function(array $input) {
                return ['ok' => true, 'data' => ['id' => 77, 'title' => 'Acme investigation', 'company_ids' => [11]]];
            },
            'evidence' => function(array $input) {
                return ['ok' => true, 'data' => ['id' => 99, 'summary' => 'Evidence sample']];
            },
            'policy' => function(array $input, array $state) {
                return ['ok' => true, 'data' => ['decision' => 'review', 'threshold' => 0.6]];
            },
            'importance' => function(array $input, array $state) {
                return ['ok' => true, 'data' => ['impact' => 0.8, 'confidence' => 0.9]];
            },
            'trust' => function(array $input, array $state) {
                return ['ok' => true, 'data' => ['company_ids' => [11], 'score' => 7.4]];
            },
            'event_identity' => function(array $input, array $state) {
                return ['ok' => true, 'data' => ['status' => 'analyzed', 'confidence' => 0.8]];
            },
            'resolver' => function(array $input, array $state) {
                return ['ok' => true, 'data' => ['action' => 'UPDATE', 'confidence' => 0.78]];
            },
            'timeline' => function(array $input, array $state) {
                return ['ok' => true, 'data' => ['event_id' => 'evt-77', 'timeline' => ['evt-77' => []]]];
            },
            'graph' => function(array $input, array $state) {
                return ['ok' => true, 'data' => [['relationship_type' => 'associated_with']]];
            },
        ];

        $result = (new Event_Intelligence_Orchestrator($services))->run(['story_id' => 77]);

        $this->assertInstanceOf(Event_Intelligence_Result::class, $result);
        $this->assertSame('completed', $result->status);
        $this->assertSame('Acme investigation', $result->story['title']);
        $this->assertSame('review', $result->policy_decisions['decision']);
        $this->assertSame('UPDATE', $result->resolver_result['action']);
        $this->assertGreaterThan(0.0, $result->confidence);
    }

    public function test_run_stops_safely_when_a_stage_fails() {
        $services = [
            'story' => function(array $input) {
                return ['ok' => true, 'data' => ['id' => 77, 'title' => 'Acme investigation']];
            },
            'evidence' => function(array $input) {
                return ['ok' => false, 'message' => 'evidence failed'];
            },
        ];

        $result = (new Event_Intelligence_Orchestrator($services))->run(['story_id' => 77]);

        $this->assertSame('failed', $result->status);
        $this->assertSame('evidence', $result->failed_stage);
        $this->assertSame('evidence failed', $result->error);
    }
}
