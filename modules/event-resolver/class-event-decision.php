<?php
namespace TSEMOU\Modules\EventResolver;

if (!defined('ABSPATH')) exit;

use TSEMOU\Modules\EventIdentity\Event_Signature;
use TSEMOU\Modules\EventIdentity\IdentityRepository;

class Event_Decision {
    public $action;
    public $confidence;
    public $reason;

    public function __construct($action, $confidence, $reason) {
        $this->action = $action;
        $this->confidence = floatval($confidence);
        $this->reason = (string) $reason;
    }

    public static function from_payload($payload = []) {
        $payload = is_array($payload) ? $payload : [];
        $has_signature = !empty($payload['signature']);
        $has_story_id = !empty($payload['story_id']);
        $has_existing = !empty($payload['existing_event']);

        if ($has_signature && $has_existing) {
            return new self('MERGE', 0.94, 'Existing event signature and incoming payload indicate a merge candidate.');
        }

        if ($has_signature) {
            $existing = IdentityRepository::find_by_signature($payload['signature']);
            if ($existing) {
                return new self('DUPLICATE', 0.95, 'Deterministic signature already exists in the repository.');
            }
        }

        if ($has_story_id) {
            return new self('UPDATE', 0.72, 'Story context exists, so the event should be treated as an update candidate.');
        }

        if (!empty($payload['correction'])) {
            return new self('CORRECTION', 0.9, 'Correction flag detected in incoming payload.');
        }

        $title = trim((string) ($payload['title'] ?? ($payload['name'] ?? '')));
        if ($title === '') {
            return new self('NEW_EVENT', 0.66, 'No deterministic match or reference data was found.');
        }

        return new self('NEW_EVENT', 0.7, 'No duplicate or correction signals were found.');
    }
}
