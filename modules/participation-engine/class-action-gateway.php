<?php
namespace TSEMOU\Modules\ParticipationEngine;

use TSEMOU\Modules\ParticipationEngine\Actions\Action_Context;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Envelope;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Metadata;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Result;
use TSEMOU\Modules\ParticipationEngine\Actions\Action_Status;

if (!defined('ABSPATH')) exit;

class Action_Gateway {
    private $registry;
    private $validator;
    private $authorizer;
    private $dispatcher;

    public function __construct(Action_Registry $registry, Action_Validator $validator, Action_Authorizer $authorizer, Action_Dispatcher $dispatcher) {
        $this->registry = $registry;
        $this->validator = $validator;
        $this->authorizer = $authorizer;
        $this->dispatcher = $dispatcher;
    }

    public function receive($raw = []) {
        $envelope = $this->normalize($raw);
        Action_Journal::record('receive', [
            'action_type' => $envelope->action_type(),
            'status' => $envelope->status(),
            'metadata' => $envelope->to_array()['metadata'] ?? [],
        ]);

        $validation = $this->validate($envelope);
        if (!$validation->is_success()) {
            Action_Journal::record('validate_failed', [
                'action_type' => $envelope->action_type(),
                'result' => $validation->to_array(),
            ]);
            return $validation;
        }

        $authorization = $this->authorize($envelope);
        if (!$authorization->is_success()) {
            Action_Journal::record('authorize_failed', [
                'action_type' => $envelope->action_type(),
                'result' => $authorization->to_array(),
            ]);
            return $authorization;
        }

        $dispatch = $this->dispatch($envelope);
        if (!$dispatch->is_success()) {
            Action_Journal::record('dispatch_failed', [
                'action_type' => $envelope->action_type(),
                'result' => $dispatch->to_array(),
            ]);
            return $dispatch;
        }

        return $this->respond($envelope, $dispatch);
    }

    public function normalize($raw = []) {
        $raw = is_array($raw) ? $raw : [];

        $action_type = sanitize_key((string) ($raw['action_type'] ?? ''));
        $definition = $this->registry->get($action_type);
        if (is_array($definition) && !empty($definition['definition_class']) && class_exists($definition['definition_class'])) {
            $definition_obj = new $definition['definition_class']();
            if (method_exists($definition_obj, 'normalize')) {
                $raw = $definition_obj->normalize($raw);
            }
        }

        $metadata = Action_Metadata::from_array($raw['metadata'] ?? []);
        $meta = $metadata->to_array();
        if (empty($meta['action_uuid'])) {
            $meta['action_uuid'] = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('action_', true);
        }
        if (empty($meta['correlation_id'])) {
            $meta['correlation_id'] = 'pe_' . wp_generate_password(12, false, false);
        }
        if (empty($meta['lifecycle_id'])) {
            $meta['lifecycle_id'] = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('life_', true);
        }
        $metadata = Action_Metadata::from_array($meta);

        return Action_Envelope::from_array([
            'actor' => $raw['actor'] ?? [],
            'action_type' => $raw['action_type'] ?? '',
            'target' => $raw['target'] ?? [],
            'payload' => $raw['payload'] ?? [],
            'context' => Action_Context::from_array($raw['context'] ?? [])->to_array(),
            'metadata' => $metadata->to_array(),
            'permissions' => $raw['permissions'] ?? [],
            'timestamp' => current_time('mysql'),
            'status' => Action_Status::NORMALIZED,
        ]);
    }

    public function validate(Action_Envelope $envelope) {
        return $this->validator->validate($envelope->with_status(Action_Status::VALIDATED));
    }

    public function authorize(Action_Envelope $envelope) {
        return $this->authorizer->authorize($envelope->with_status(Action_Status::AUTHORIZED));
    }

    public function dispatch(Action_Envelope $envelope) {
        return $this->dispatcher->dispatch($envelope->with_status(Action_Status::DISPATCHED));
    }

    public function respond(Action_Envelope $envelope, Action_Result $dispatch_result) {
        $data = $dispatch_result->to_array();
        $data['envelope'] = $envelope->with_status(Action_Status::RESPONDED)->to_array();

        Action_Journal::record('respond', [
            'action_type' => $envelope->action_type(),
            'result' => $dispatch_result->to_array(),
        ]);

        return Action_Result::success(Action_Status::RESPONDED, 'Action flow completed (foundation mode).', $data);
    }
}
