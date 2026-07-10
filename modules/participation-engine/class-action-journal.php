<?php
namespace TSEMOU\Modules\ParticipationEngine;

if (!defined('ABSPATH')) exit;

class Action_Journal {
    public static function record($stage, $payload = []) {
        if (!(defined('WP_DEBUG_LOG') && WP_DEBUG_LOG)) return;

        $data = is_array($payload) ? $payload : [];
        $data['stage'] = sanitize_text_field((string) $stage);
        $data['ts'] = current_time('mysql');

        error_log('[TSEMOU_PARTICIPATION_JOURNAL] ' . wp_json_encode($data));
    }
}
