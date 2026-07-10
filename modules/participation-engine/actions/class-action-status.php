<?php
namespace TSEMOU\Modules\ParticipationEngine\Actions;

if (!defined('ABSPATH')) exit;

class Action_Status {
    const RECEIVED = 'received';
    const NORMALIZED = 'normalized';
    const VALIDATED = 'validated';
    const AUTHORIZED = 'authorized';
    const DISPATCHED = 'dispatched';
    const RESPONDED = 'responded';
    const FAILED = 'failed';
}
