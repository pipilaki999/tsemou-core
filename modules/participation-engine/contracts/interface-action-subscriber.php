<?php
namespace TSEMOU\Modules\ParticipationEngine\Contracts;

use TSEMOU\Modules\ParticipationEngine\Actions\Action_Envelope;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Result;

if (!defined('ABSPATH')) exit;

interface Action_Subscriber {
    public function id();
    public function supports($action_type);
    public function consume(Action_Envelope $envelope);
}
