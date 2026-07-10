<?php
namespace TSEMOU\Modules\ParticipationEngine\Subscribers;

use TSEMOU\Modules\ParticipationEngine\Actions\Action_Envelope;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Result;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Status;
use TSEMOU\Modules\ParticipationEngine\Contracts\Action_Subscriber;

if (!defined('ABSPATH')) exit;

class Trust_Subscriber implements Action_Subscriber {
    public function id() {
        return 'trust';
    }

    public function supports($action_type) {
        return sanitize_key((string) $action_type) === 'tsemit';
    }

    public function consume(Action_Envelope $envelope) {
        if (!class_exists('\\TSEMOU\\Modules\\TrustEngine\\Trust_Engine')) {
            return Action_Result::failure(Action_Status::FAILED, 'Trust Engine is not available.', ['trust_engine_missing']);
        }

        $engine = \TSEMOU\Modules\TrustEngine\Trust_Engine::instance();
        if (!method_exists($engine, 'handle_vote_submission')) {
            return Action_Result::failure(Action_Status::FAILED, 'Trust Engine vote handler is missing.', ['trust_handler_missing']);
        }

        // Trust Engine remains the authority for TSEMIT behavior.
        $engine->handle_vote_submission();

        return Action_Result::success(Action_Status::DISPATCHED, 'Trust Engine delegated TSEMIT handling.');
    }
}
