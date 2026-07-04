<?php
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value) {
        return trim((string) $value);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($value) {
        return trim((string) $value);
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('get_option')) {
    $tsemou_test_options = [];
    function get_option($key, $default = []) {
        global $tsemou_test_options;
        return array_key_exists($key, $tsemou_test_options) ? $tsemou_test_options[$key] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($key, $value, $autoload = false) {
        global $tsemou_test_options;
        $tsemou_test_options[$key] = $value;
        return true;
    }
}

require_once dirname(__DIR__) . '/modules/event-identity/class-event-signature.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-event-candidate.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-event-identity-matcher.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-event-identity-engine.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-candidate-normalizer.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-alias-resolver.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-identity-signature.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-identity-matcher.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-identity-repository.php';
require_once dirname(__DIR__) . '/modules/event-resolver/class-event-decision.php';
require_once dirname(__DIR__) . '/modules/event-resolver/class-event-similarity.php';
require_once dirname(__DIR__) . '/modules/event-resolver/class-event-merge.php';
require_once dirname(__DIR__) . '/modules/event-resolver/class-event-resolver.php';
require_once dirname(__DIR__) . '/modules/event-timeline/class-event-timeline-node.php';
require_once dirname(__DIR__) . '/modules/event-timeline/class-event-sequence.php';
require_once dirname(__DIR__) . '/modules/event-timeline/class-event-timeline.php';
