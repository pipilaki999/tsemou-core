<?php
namespace TSEMOU\Modules\ParticipationEngine\Subscribers;

use TSEMOU\Modules\ParticipationEngine\Actions\Action_Envelope;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Result;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Status;
use TSEMOU\Modules\ParticipationEngine\Contracts\Action_Subscriber;

if (!defined('ABSPATH')) exit;

class Notifications_Subscriber implements Action_Subscriber {
    public function id() {
        return 'notifications';
    }

    public function supports($action_type) {
        return false;
    }

    public function consume(Action_Envelope $envelope) {
        return Action_Result::success(Action_Status::DISPATCHED, 'Notifications subscriber noop.');
    }
}
