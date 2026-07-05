<?php
require_once __DIR__ . '/bootstrap.php';

use TSEMOU\Modules\EventIntelligence\Event_Graph_Adapter;
use TSEMOU\Modules\EventIntelligence\Event_Policy_Adapter;
use TSEMOU\Modules\EventIntelligence\Event_Public_Importance_Service;
use TSEMOU\Modules\EventIntelligence\Event_Story_Ranking_Service;

class EventPhaseBServicesTest extends \PHPUnit\Framework\TestCase {
    protected function setUp(): void {
        global $tsemou_test_options, $tsemou_test_meta;
        $tsemou_test_options = [];
        $tsemou_test_meta = [];
    }

    public function test_policy_adapter_marks_actionable_events_deterministically() {
        $result = Event_Policy_Adapter::instance()->evaluate([
            'evidence' => ['id' => 99],
            'event_identity' => ['confidence' => 0.82],
            'resolver_result' => ['action' => 'NEW_EVENT', 'confidence' => 0.88],
        ]);

        $this->assertSame('actionable', $result['decision']);
        $this->assertTrue($result['gates'][0]['passed']);
        $this->assertTrue($result['gates'][1]['passed']);
        $this->assertTrue($result['gates'][2]['passed']);
    }

    public function test_public_importance_service_scores_from_pipeline_state() {
        $result = Event_Public_Importance_Service::instance()->evaluate([
            'evidence' => ['id' => 99, 'intelligence' => ['confidence' => 0.9, 'impact' => 0.7]],
            'resolver_result' => ['action' => 'NEW_EVENT', 'confidence' => 0.8],
            'timeline' => ['nodes' => [['action' => 'first_report']]],
        ]);

        $this->assertSame(86.5, $result['score']);
        $this->assertSame('high', $result['band']);
        $this->assertSame(0.87, $result['confidence']);
    }

    public function test_story_ranking_uses_policy_importance_and_trust() {
        $result = Event_Story_Ranking_Service::instance()->evaluate([
            'story' => ['id' => 77],
            'importance' => ['score' => 86.5, 'confidence' => 0.87],
            'trust' => ['score' => 6.8, 'confidence' => 0.5],
            'policy_decisions' => ['decision' => 'actionable', 'confidence' => 0.8],
            'resolver_result' => ['action' => 'NEW_EVENT'],
        ]);

        $this->assertSame(85.5, $result['score']);
        $this->assertSame('high', $result['priority']);
        $this->assertSame('085.5:77', $result['sort_key']);
    }

    public function test_graph_adapter_creates_event_relationships() {
        $result = Event_Graph_Adapter::instance()->apply([
            'story' => ['id' => 77, 'company_ids' => [11, 12]],
            'evidence' => ['id' => 101],
            'resolver_result' => ['confidence' => 0.82],
            'policy_decisions' => ['decision' => 'actionable'],
            'story_ranking' => ['priority' => 'high'],
        ]);

        $this->assertSame(4, $result['count']);
        $this->assertSame('context_only', $result['relationships'][0]['relationship_type']);
        $this->assertContains('trust_relevant', $result['relationships'][2]['flags']);
        $this->assertContains('influence', $result['relationships'][2]['flags']);
    }
}