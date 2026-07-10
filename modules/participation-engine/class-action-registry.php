<?php
namespace TSEMOU\Modules\ParticipationEngine;

if (!defined('ABSPATH')) exit;

class Action_Registry {
    private $actions = [];

    public function register($key, $definition = []) {
        $key = sanitize_key((string) $key);
        $this->actions[$key] = is_array($definition) ? $definition : [];
    }

    public function register_defaults() {
        $defaults = [
            'create_case' => ['label' => 'Create Case'],
            'join_case' => ['label' => 'Join Case'],
            'tsemit' => [
                'label' => 'TSEMIT',
                'definition_class' => '\\TSEMOU\\Modules\\ParticipationEngine\\Actions\\TSEMIT_Action_Definition',
                'subscriber' => 'trust',
            ],
            'untsemit' => ['label' => 'UNTSEMIT'],
            'offer_help' => ['label' => 'Offer Help'],
            'take_help' => ['label' => 'Take Help'],
            'offer_knowledge' => ['label' => 'Offer Knowledge'],
            'take_knowledge' => ['label' => 'Take Knowledge'],
            'attach_evidence' => ['label' => 'Attach Evidence'],
            'add_proof' => ['label' => 'Add Proof'],
            'start_movement' => ['label' => 'Start Movement'],
            'join_movement' => ['label' => 'Join Movement'],
            'participate_in_chat' => ['label' => 'Participate in Chat'],
            'comment' => ['label' => 'Comment'],
            'follow_case' => ['label' => 'Follow Case'],
            'share_case' => ['label' => 'Share Case'],
        ];

        foreach ($defaults as $key => $definition) {
            $this->register($key, $definition);
        }
    }

    public function has($key) {
        return isset($this->actions[sanitize_key((string) $key)]);
    }

    public function get($key) {
        $key = sanitize_key((string) $key);
        return $this->actions[$key] ?? null;
    }

    public function all() {
        return $this->actions;
    }
}
