<?php
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value) {
        return trim((string) $value);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($value) {
        $value = strtolower((string) $value);
        return preg_replace('/[^a-z0-9_\-]/', '', $value);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($value) {
        return trim((string) $value);
    }
}

if (!function_exists('absint')) {
    function absint($value) {
        return abs(intval($value));
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

if (!defined('YEAR_IN_SECONDS')) {
    define('YEAR_IN_SECONDS', 31536000);
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

if (!function_exists('get_post_meta')) {
    $tsemou_test_meta = [];
    function get_post_meta($post_id, $key, $single = true) {
        global $tsemou_test_meta;
        $value = $tsemou_test_meta[$post_id][$key] ?? ($single ? '' : []);
        if ($single) {
            return $value;
        }
        return is_array($value) ? $value : [$value];
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $key, $value) {
        global $tsemou_test_meta;
        if (!isset($tsemou_test_meta[$post_id])) {
            $tsemou_test_meta[$post_id] = [];
        }
        $tsemou_test_meta[$post_id][$key] = $value;
        return true;
    }
}

if (!function_exists('get_post_type')) {
    function get_post_type($post_id) {
        if (intval($post_id) >= 100 && intval($post_id) < 1000) {
            return 'tsemou_proof';
        }
        if (intval($post_id) >= 10 && intval($post_id) < 100) {
            return 'company';
        }
        return 'story';
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability, $object_id = 0) {
        return true;
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($value) {
        return json_encode($value);
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
require_once dirname(__DIR__) . '/modules/event-intelligence/class-event-policy-adapter.php';
require_once dirname(__DIR__) . '/modules/event-intelligence/class-event-public-importance-service.php';
require_once dirname(__DIR__) . '/modules/event-intelligence/class-event-trust-adapter.php';
require_once dirname(__DIR__) . '/modules/event-intelligence/class-event-story-ranking-service.php';
require_once dirname(__DIR__) . '/modules/event-intelligence/class-event-graph-adapter.php';
require_once dirname(__DIR__) . '/modules/evidence-engine/class-evidence-engine.php';
require_once dirname(__DIR__) . '/modules/policy-engine/class-policy-engine.php';
require_once dirname(__DIR__) . '/modules/proof-engine/class-proof-engine.php';
require_once dirname(__DIR__) . '/modules/trust-engine/class-trust-engine.php';
require_once dirname(__DIR__) . '/modules/source-discovery/class-source-discovery.php';
require_once dirname(__DIR__) . '/modules/knowledge-graph/class-knowledge-graph.php';
require_once dirname(__DIR__) . '/modules/event-intelligence/class-event-intelligence-result.php';
require_once dirname(__DIR__) . '/modules/event-intelligence/class-event-intelligence-orchestrator.php';
