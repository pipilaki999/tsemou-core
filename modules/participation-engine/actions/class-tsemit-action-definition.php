<?php
namespace TSEMOU\Modules\ParticipationEngine\Actions;

if (!defined('ABSPATH')) exit;

class TSEMIT_Action_Definition {
    public function key() {
        return 'tsemit';
    }

    public function label() {
        return 'TSEMIT';
    }

    public function normalize($raw = []) {
        $raw = is_array($raw) ? $raw : [];

        $actor = is_array($raw['actor'] ?? null) ? $raw['actor'] : [];
        if (empty($actor)) $actor = ['kind' => 'user'];

        $target = is_array($raw['target'] ?? null) ? $raw['target'] : [];
        if (empty($target)) $target = ['type' => 'company', 'id' => 0];

        $payload = is_array($raw['payload'] ?? null) ? $raw['payload'] : [];
        if (!array_key_exists('score', $payload)) $payload['score'] = 0;

        return [
            'actor' => $actor,
            'action_type' => 'tsemit',
            'target' => $target,
            'payload' => $payload,
            'context' => $raw['context'] ?? [],
            'metadata' => $raw['metadata'] ?? [],
            'permissions' => $raw['permissions'] ?? [],
        ];
    }
}
