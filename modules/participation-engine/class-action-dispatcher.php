<?php
namespace TSEMOU\Modules\ParticipationEngine;

use TSEMOU\Modules\ParticipationEngine\Actions\Action_Envelope;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Result;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Status;

if (!defined('ABSPATH')) exit;

class Action_Dispatcher {
    private $subscriber_registry;

    public function __construct(Subscriber_Registry $subscriber_registry) {
        $this->subscriber_registry = $subscriber_registry;
    }

    public function dispatch(Action_Envelope $envelope) {
        $subscribers = $this->subscriber_registry->for_action($envelope->action_type());
        $responses = [];

        foreach ($subscribers as $subscriber) {
            $responses[] = [
                'subscriber' => $subscriber->id(),
                'result' => $subscriber->consume($envelope)->to_array(),
            ];
        }

        return Action_Result::success(Action_Status::DISPATCHED, 'Action dispatched.', [
            'subscriber_count' => count($subscribers),
            'subscriber_results' => $responses,
        ]);
    }
}
