<?php
namespace TSEMOU\Modules\ParticipationEngine;

use TSEMOU\Modules\ParticipationEngine\Actions\Action_Envelope;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Result;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Status;

if (!defined('ABSPATH')) exit;

class Action_Validator {
    private $registry;

    public function __construct(Action_Registry $registry) {
        $this->registry = $registry;
    }

    public function validate(Action_Envelope $envelope) {
        $action_type = $envelope->action_type();

        if (!$action_type) {
            return Action_Result::failure(Action_Status::FAILED, 'Missing action type.', ['missing_action_type']);
        }

        if (!$this->registry->has($action_type)) {
            return Action_Result::failure(Action_Status::FAILED, 'Action is not registered.', ['action_not_registered']);
        }

        if ($action_type === 'tsemit') {
            $data = $envelope->to_array();
            $target = is_array($data['target'] ?? null) ? $data['target'] : [];
            $payload = is_array($data['payload'] ?? null) ? $data['payload'] : [];
            $context = is_array($data['context'] ?? null) ? $data['context'] : [];
            $is_http_post = sanitize_key((string) ($context['channel'] ?? '')) === 'http_post';

            if (empty($target['id']) || sanitize_key((string) ($target['type'] ?? '')) !== 'company') {
                return Action_Result::failure(Action_Status::FAILED, 'Invalid TSEMIT company target.', ['invalid_company_target']);
            }

            if ($is_http_post) {
                if (!isset($payload['submit_flag']) || !$payload['submit_flag']) {
                    return Action_Result::failure(Action_Status::FAILED, 'Missing TSEMIT submit flag.', ['missing_submit_flag']);
                }

                if (empty($payload['nonce']) || !wp_verify_nonce((string) $payload['nonce'], 'tsemou_company_vote')) {
                    return Action_Result::failure(Action_Status::FAILED, 'Invalid TSEMIT nonce.', ['invalid_nonce']);
                }
            }
        }

        return Action_Result::success(Action_Status::VALIDATED, 'Action validated.');
    }
}
