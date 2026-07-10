<?php
namespace TSEMOU\Modules\ParticipationEngine\Subscribers;

use TSEMOU\Modules\ParticipationEngine\Actions\Action_Envelope;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Result;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Status;
use TSEMOU\Modules\ParticipationEngine\Contracts\Action_Subscriber;

if (!defined('ABSPATH')) exit;

class Community_Subscriber implements Action_Subscriber {
    public function id() {
        return 'community';
    }

    public function supports($action_type) {
        return false;
    }

    public function consume(Action_Envelope $envelope) {
        return Action_Result::success(Action_Status::DISPATCHED, 'Community subscriber noop.');
    }
}
