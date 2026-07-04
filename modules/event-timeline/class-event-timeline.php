<?php
namespace TSEMOU\Modules\EventTimeline;

if (!defined('ABSPATH')) exit;

use TSEMOU\Modules\EventResolver\Event_Resolver;

class Event_Timeline {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public function build($resolved_event = []) {
        $resolved_event = is_array($resolved_event) ? $resolved_event : [];
        $action = sanitize_text_field($resolved_event['action'] ?? 'NEW_EVENT');
        $event_id = sanitize_text_field($resolved_event['event_id'] ?? ($resolved_event['story_id'] ?? ''));
        $timestamp = sanitize_text_field($resolved_event['timestamp'] ?? current_time('mysql'));
        $title = sanitize_text_field($resolved_event['title'] ?? '');
        $summary = sanitize_textarea_field($resolved_event['summary'] ?? '');
        $confidence = floatval($resolved_event['confidence'] ?? 0.0);
        $source = sanitize_text_field($resolved_event['source'] ?? 'resolver');

        $timeline_type = $this->classify_action($action);
        $node = new Event_Timeline_Node([
            'event_id' => $event_id,
            'timestamp' => $timestamp,
            'action' => $timeline_type,
            'title' => $title,
            'summary' => $summary,
            'confidence' => $confidence,
            'source' => $source,
            'payload' => $resolved_event,
        ]);

        $nodes = [$node];
        $ordered = Event_Sequence::order($nodes);

        return [
            'event_id' => $event_id,
            'timeline' => $ordered,
            'nodes' => [$node->to_array()],
        ];
    }

    public function build_from_resolver($payload = []) {
        $payload = is_array($payload) ? $payload : [];
        if (class_exists('\TSEMOU\Modules\EventResolver\Event_Resolver')) {
            $resolved = Event_Resolver::instance()->resolve($payload);
            $payload = array_merge($payload, $resolved);
        }
        return $this->build($payload);
    }

    protected function classify_action($action) {
        $map = [
            'NEW_EVENT' => 'first_report',
            'UPDATE' => 'update',
            'DUPLICATE' => 'duplicate',
            'MERGE' => 'merge',
            'CORRECTION' => 'correction',
        ];
        return $map[$action] ?? 'follow_up';
    }
}
