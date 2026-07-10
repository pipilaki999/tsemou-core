<?php
namespace TSEMOU\Modules\CanonicalTSEMIT;

use TSEMOU\Modules\EntityFoundation\Entity_Registry;

if (!defined('ABSPATH')) exit;

class Canonical_TSEMIT_Service {
    private static $instance = null;

    private $registry;
    private $store;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self(Entity_Registry::instance(), Canonical_TSEMIT_Store::instance());
        return self::$instance;
    }

    public function __construct(Entity_Registry $registry, Canonical_TSEMIT_Store $store) {
        $this->registry = $registry;
        $this->store = $store;
    }

    public function process_action($action_type, $target, $actor, $payload = []) {
        $action_type = sanitize_key((string) $action_type);
        $target = is_array($target) ? $target : [];
        $actor = is_array($actor) ? $actor : [];
        $payload = is_array($payload) ? $payload : [];

        $user_id = absint($actor['user_id'] ?? 0);
        if ($user_id <= 0) {
            return ['success' => false, 'code' => 'not_authenticated', 'message' => 'Authenticated user is required.'];
        }

        $desired_state = sanitize_key((string) ($payload['desired_state'] ?? ''));
        if (!$desired_state) {
            $desired_state = ($action_type === 'untsemit') ? 'inactive' : 'active';
        }

        if (!in_array($desired_state, ['active', 'inactive'], true)) {
            return ['success' => false, 'code' => 'invalid_state', 'message' => 'Invalid desired state.'];
        }

        $target_type = sanitize_key((string) ($target['type'] ?? ''));
        $target_id = absint($target['id'] ?? 0);
        $entity = $this->registry->resolve_from_target($target_type, $target_id);

        if (!$entity) {
            return ['success' => false, 'code' => 'invalid_entity', 'message' => 'Entity could not be resolved.'];
        }

        $entity_id = absint($entity['entity_id'] ?? 0);
        if ($entity_id <= 0) {
            return ['success' => false, 'code' => 'invalid_entity_id', 'message' => 'Entity id is invalid.'];
        }

        $state_result = $this->store->upsert_state($entity_id, $user_id, $desired_state);
        if (empty($state_result['success'])) {
            return ['success' => false, 'code' => sanitize_key((string) ($state_result['code'] ?? 'persist_failed')), 'message' => 'TSEMIT persistence failed.'];
        }

        $score = $this->store->count_active_by_entity($entity_id);
        $this->registry->set_cached_tsemit_score($entity_id, $score);

        return [
            'success' => true,
            'code' => !empty($state_result['changed']) ? 'state_changed' : 'idempotent_no_change',
            'message' => !empty($state_result['changed']) ? 'State updated.' : 'State already current.',
            'changed' => !empty($state_result['changed']),
            'entity_id' => $entity_id,
            'entity_uuid' => sanitize_text_field((string) ($entity['entity_uuid'] ?? '')),
            'source_object_type' => sanitize_key((string) ($entity['source_object_type'] ?? '')),
            'source_object_id' => absint($entity['source_object_id'] ?? 0),
            'tsemit_state' => sanitize_key((string) ($state_result['state'] ?? $desired_state)),
            'tsemit_score' => intval($score),
            'user_id' => $user_id,
            'record' => is_array($state_result['record'] ?? null) ? $state_result['record'] : null,
        ];
    }

    public function get_score($entity_id) {
        return $this->store->count_active_by_entity($entity_id);
    }

    public function get_state($entity_id, $user_id) {
        $row = $this->store->find_by_entity_user($entity_id, $user_id);
        return $row ? sanitize_key((string) ($row['state'] ?? 'inactive')) : 'inactive';
    }
}
