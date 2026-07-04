<?php
namespace TSEMOU\Modules\EventResolver;

if (!defined('ABSPATH')) exit;

class Event_Merge {
    public static function merge($primary, $secondary) {
        $primary = is_array($primary) ? $primary : [];
        $secondary = is_array($secondary) ? $secondary : [];

        $merged = $primary;
        $merged['title'] = $primary['title'] ?? $secondary['title'] ?? '';
        $merged['summary'] = trim((string) ($primary['summary'] ?? '') . ' ' . (string) ($secondary['summary'] ?? ''));
        $merged['company_name'] = $primary['company_name'] ?? $secondary['company_name'] ?? '';
        $merged['event_type'] = $primary['event_type'] ?? $secondary['event_type'] ?? 'general';
        $merged['signals_count'] = intval($primary['signals_count'] ?? 0) + intval($secondary['signals_count'] ?? 0);
        $merged['updates_count'] = intval($primary['updates_count'] ?? 0) + intval($secondary['updates_count'] ?? 0);

        return $merged;
    }
}
