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

    public static function from_payload($payload = [], $existing_record = null) {
        $payload = is_array($payload) ? $payload : [];
        $has_story_id = !empty($payload['story_id']);
        $has_existing = !empty($payload['existing_event']);
        $has_correction = !empty($payload['correction']);

        if ($has_correction) {
            return new self('CORRECTION', 0.9, 'Correction flag detected in incoming payload.');
        }

        if ($has_existing) {
            return new self('MERGE', 0.94, 'Existing event signature and incoming payload indicate a merge candidate.');
        }

        $signature = Event_Signature::build($payload);
        $existing = $existing_record;
        if ($existing === null && $signature !== '') {
            $existing = IdentityRepository::find_by_signature($signature);
        }

        if ($existing) {
            return new self('DUPLICATE', 0.95, 'Deterministic signature already exists in the repository.');
        }

        if ($has_story_id) {
            return new self('UPDATE', 0.72, 'Story context exists, so the event should be treated as an update candidate.');
        }

        $title = trim((string) ($payload['title'] ?? ($payload['name'] ?? '')));
        if ($title === '') {
            return new self('NEW_EVENT', 0.66, 'No deterministic match or reference data was found.');
        }

        return new self('NEW_EVENT', 0.7, 'No duplicate or correction signals were found.');
    }
}
