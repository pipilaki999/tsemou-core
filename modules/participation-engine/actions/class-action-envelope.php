<?php
namespace TSEMOU\Modules\ParticipationEngine\Actions;

if (!defined('ABSPATH')) exit;

class Action_Envelope {
    private $actor;
    private $action_type;
    private $target;
    private $payload;
    private $context;
    private $metadata;
    private $permissions;
    private $timestamp;
    private $status;

    public function __construct($actor, $action_type, $target, $payload, Action_Context $context, Action_Metadata $metadata, $permissions, $timestamp, $status) {
        $this->actor = is_array($actor) ? $actor : [];
        $this->action_type = sanitize_key((string) $action_type);
        $this->target = is_array($target) ? $target : [];
        $this->payload = is_array($payload) ? $payload : [];
        $this->context = $context;
        $this->metadata = $metadata;
        $this->permissions = is_array($permissions) ? $permissions : [];
        $this->timestamp = sanitize_text_field((string) $timestamp);
        $this->status = sanitize_key((string) $status);
    }

    public static function from_array($data = []) {
        $data = is_array($data) ? $data : [];
        return new self(
            $data['actor'] ?? [],
            $data['action_type'] ?? '',
            $data['target'] ?? [],
            $data['payload'] ?? [],
            Action_Context::from_array($data['context'] ?? []),
            Action_Metadata::from_array($data['metadata'] ?? []),
            $data['permissions'] ?? [],
            $data['timestamp'] ?? current_time('mysql'),
            $data['status'] ?? Action_Status::RECEIVED
        );
    }

    public function with_status($status) {
        return new self(
            $this->actor,
            $this->action_type,
            $this->target,
            $this->payload,
            $this->context,
            $this->metadata,
            $this->permissions,
            $this->timestamp,
            $status
        );
    }

    public function action_type() {
        return $this->action_type;
    }

    public function status() {
        return $this->status;
    }

    public function to_array() {
        return [
            'actor' => $this->actor,
            'action_type' => $this->action_type,
            'target' => $this->target,
            'payload' => $this->payload,
            'context' => $this->context->to_array(),
            'metadata' => $this->metadata->to_array(),
            'permissions' => $this->permissions,
            'timestamp' => $this->timestamp,
            'status' => $this->status,
        ];
    }
}
