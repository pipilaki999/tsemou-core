<?php
namespace TSEMOU\Modules\ParticipationEngine;

use TSEMOU\Modules\ParticipationEngine\Contracts\Action_Subscriber;

if (!defined('ABSPATH')) exit;

class Subscriber_Registry {
    private $subscribers = [];

    public function register(Action_Subscriber $subscriber) {
        $this->subscribers[$subscriber->id()] = $subscriber;
    }

    public function all() {
        return $this->subscribers;
    }

    public function for_action($action_type) {
        $matches = [];
        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->supports($action_type)) {
                $matches[] = $subscriber;
            }
        }
        return $matches;
    }
}
