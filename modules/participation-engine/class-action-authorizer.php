<?php
namespace TSEMOU\Modules\ParticipationEngine;

use TSEMOU\Modules\ParticipationEngine\Actions\Action_Envelope;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Result;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Status;

if (!defined('ABSPATH')) exit;

class Action_Authorizer {
    public function authorize(Action_Envelope $envelope) {
        if ($envelope->action_type() === 'tsemit') {
            $data = $envelope->to_array();
            $actor = is_array($data['actor'] ?? null) ? $data['actor'] : [];
            $permissions = is_array($data['permissions'] ?? null) ? $data['permissions'] : [];
            $authenticated = !empty($permissions['authenticated']) || intval($actor['user_id'] ?? 0) > 0;

            if (!$authenticated) {
                return Action_Result::failure(Action_Status::FAILED, 'User is not authorized to TSEMIT.', ['not_authenticated']);
            }
        }

        return Action_Result::success(Action_Status::AUTHORIZED, 'Action authorized (foundation mode).');
    }
}
