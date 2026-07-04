<?php
namespace TSEMOU\Modules\EventResolver;

if (!defined('ABSPATH')) exit;

use TSEMOU\Modules\EventIdentity\Event_Identity_Engine;
use TSEMOU\Modules\EventIdentity\Event_Signature;
use TSEMOU\Modules\EventIdentity\IdentityRepository;

class Event_Resolver {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    public function resolve($input = []) {
        $payload = is_array($input) ? $input : [];
        $signature = Event_Signature::build($payload);
        $repository = $signature !== '' ? IdentityRepository::find_by_signature($signature) : null;
        $existing_payload = is_array($repository['payload'] ?? null) ? $repository['payload'] : [];
        $similarity = Event_Similarity::score($payload, $existing_payload);

        $decision = Event_Decision::from_payload($payload, $repository);

        if ($decision->action === 'NEW_EVENT' && $similarity >= 0.85) {
            $decision->action = 'MERGE';
            $decision->confidence = 0.9;
        }

        if ($decision->action === 'UPDATE' && $decision->confidence >= 0.85) {
            $decision->action = 'CORRECTION';
        }

        if ($repository && $decision->action === 'NEW_EVENT') {
            $decision->action = 'DUPLICATE';
            $decision->confidence = 0.95;
        }

        if (class_exists('\TSEMOU\Modules\EventIdentity\Event_Identity_Engine')) {
            Event_Identity_Engine::instance()->analyze_story(intval($payload['story_id'] ?? 0));
        }

        return [
            'action' => $decision->action,
            'confidence' => $decision->confidence,
            'reason' => $decision->reason,
            'signature' => $signature,
        ];
    }
}
