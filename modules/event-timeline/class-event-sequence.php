<?php
namespace TSEMOU\Modules\EventTimeline;

if (!defined('ABSPATH')) exit;

class Event_Sequence {
    public static function order($nodes) {
        $nodes = is_array($nodes) ? $nodes : [];
        $timeline = [];

        foreach ($nodes as $node) {
            if ($node instanceof Event_Timeline_Node) {
                $timeline[] = $node;
            } elseif (is_array($node)) {
                $timeline[] = new Event_Timeline_Node($node);
            }
        }

        usort($timeline, function ($left, $right) {
            $left_ts = $left->timestamp;
            $right_ts = $right->timestamp;
            return strcmp($left_ts, $right_ts);
        });

        $grouped = [];
        foreach ($timeline as $node) {
            $event_id = $node->event_id !== '' ? $node->event_id : 'default';
            if (!isset($grouped[$event_id])) {
                $grouped[$event_id] = [];
            }
            $grouped[$event_id][] = $node->to_array();
        }

        return $grouped;
    }
}
