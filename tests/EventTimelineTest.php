<?php
require_once __DIR__ . '/bootstrap.php';

use TSEMOU\Modules\EventTimeline\Event_Timeline;

class EventTimelineTest extends \PHPUnit\Framework\TestCase {
    public function test_timeline_builds_structured_payload() {
        $timeline = Event_Timeline::instance()->build([
            'event_id' => 'evt-1',
            'title' => 'Acme investigation',
            'summary' => 'Initial report',
            'action' => 'NEW_EVENT',
            'confidence' => 0.8,
        ]);

        $this->assertArrayHasKey('timeline', $timeline);
        $this->assertArrayHasKey('nodes', $timeline);
        $this->assertSame('first_report', $timeline['nodes'][0]['action']);
        $this->assertSame('evt-1', $timeline['event_id']);
    }

    public function test_sequence_groups_nodes_by_event_id() {
        $timeline = Event_Timeline::instance()->build([
            'event_id' => 'evt-1',
            'title' => 'Acme investigation',
            'summary' => 'Follow-up',
            'action' => 'UPDATE',
            'confidence' => 0.7,
        ]);

        $this->assertArrayHasKey('evt-1', $timeline['timeline']);
        $this->assertCount(1, $timeline['timeline']['evt-1']);
    }
}
