<?php
namespace TSEMOU\Modules\ParticipationEngine\Adapters;

if (!defined('ABSPATH')) exit;

class HTTP_TSEMIT_Adapter {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'capture_submission'], 1);
    }

    public function capture_submission() {
        if (empty($_POST['tsemou_company_vote_submit'])) return;

        $request = [
            'action_type' => 'tsemit',
            'actor' => [
                'user_id' => get_current_user_id(),
                'kind' => 'user',
            ],
            'target' => [
                'type' => 'company',
                'id' => isset($_POST['company_id']) ? intval($_POST['company_id']) : 0,
            ],
            'payload' => [
                'score' => isset($_POST['score']) ? floatval($_POST['score']) : 0,
                'submit_flag' => !empty($_POST['tsemou_company_vote_submit']),
                'nonce' => sanitize_text_field(wp_unslash($_POST['tsemou_company_vote_nonce'] ?? '')),
            ],
            'context' => [
                'surface' => 'company_page',
                'channel' => 'http_post',
                'transport' => 'http',
                'origin' => sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? '')),
            ],
            'metadata' => [
                'idempotency_key' => 'tsemit_' . get_current_user_id() . '_' . intval($_POST['company_id'] ?? 0),
                'version' => '1.0',
            ],
            'permissions' => [
                'authenticated' => is_user_logged_in(),
            ],
        ];

        if (class_exists('\\TSEMOU\\Modules\\ParticipationEngine\\Participation_Engine')) {
            \TSEMOU\Modules\ParticipationEngine\Participation_Engine::instance()->submitAction($request);
        }
    }
}
