<?php
namespace TSEMOU\Modules\EventTimeline;

if (!defined('ABSPATH')) exit;

class Event_Timeline_Node {
    public $event_id;
    public $timestamp;
    public $action;
    public $title;
    public $summary;
    public $confidence;
    public $source;
    public $payload;

    public function __construct($data = []) {
        $data = is_array($data) ? $data : [];
        $this->event_id = sanitize_text_field($data['event_id'] ?? '');
        $this->timestamp = sanitize_text_field($data['timestamp'] ?? current_time('mysql'));
        $this->action = sanitize_text_field($data['action'] ?? 'UPDATE');
        $this->title = sanitize_text_field($data['title'] ?? '');
        $this->summary = sanitize_textarea_field($data['summary'] ?? '');
        $this->confidence = floatval($data['confidence'] ?? 0.0);
        $this->source = sanitize_text_field($data['source'] ?? 'resolver');
        $this->payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
    }

    public function to_array() {
        return [
            'event_id' => $this->event_id,
            'timestamp' => $this->timestamp,
            'action' => $this->action,
            'title' => $this->title,
            'summary' => $this->summary,
            'confidence' => $this->confidence,
            'source' => $this->source,
            'payload' => $this->payload,
        ];
    }
}
