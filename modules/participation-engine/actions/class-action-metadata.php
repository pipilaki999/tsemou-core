<?php
namespace TSEMOU\Modules\ParticipationEngine\Actions;

if (!defined('ABSPATH')) exit;

class Action_Metadata {
    private $action_uuid;
    private $correlation_id;
    private $lifecycle_id;
    private $idempotency_key;
    private $version;

    public function __construct($action_uuid = '', $correlation_id = '', $lifecycle_id = '', $idempotency_key = '', $version = '1.0') {
        $this->action_uuid = sanitize_text_field((string) $action_uuid);
        $this->correlation_id = sanitize_text_field((string) $correlation_id);
        $this->lifecycle_id = sanitize_text_field((string) $lifecycle_id);
        $this->idempotency_key = sanitize_text_field((string) $idempotency_key);
        $this->version = sanitize_text_field((string) $version);
    }

    public static function from_array($data = []) {
        $data = is_array($data) ? $data : [];
        return new self(
            $data['action_uuid'] ?? '',
            $data['correlation_id'] ?? '',
            $data['lifecycle_id'] ?? '',
            $data['idempotency_key'] ?? '',
            $data['version'] ?? '1.0'
        );
    }

    public function to_array() {
        return [
            'action_uuid' => $this->action_uuid,
            'correlation_id' => $this->correlation_id,
            'lifecycle_id' => $this->lifecycle_id,
            'idempotency_key' => $this->idempotency_key,
            'version' => $this->version,
        ];
    }
}
