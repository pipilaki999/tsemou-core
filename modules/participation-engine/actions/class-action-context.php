<?php
namespace TSEMOU\Modules\ParticipationEngine\Actions;

if (!defined('ABSPATH')) exit;

class Action_Context {
    private $surface;
    private $channel;
    private $request_id;
    private $origin;

    public function __construct($surface = 'unknown', $channel = 'internal', $request_id = '', $origin = '') {
        $this->surface = sanitize_text_field((string) $surface);
        $this->channel = sanitize_text_field((string) $channel);
        $this->request_id = sanitize_text_field((string) $request_id);
        $this->origin = sanitize_text_field((string) $origin);
    }

    public static function from_array($data = []) {
        $data = is_array($data) ? $data : [];
        return new self(
            $data['surface'] ?? 'unknown',
            $data['channel'] ?? 'internal',
            $data['request_id'] ?? '',
            $data['origin'] ?? ''
        );
    }

    public function to_array() {
        return [
            'surface' => $this->surface,
            'channel' => $this->channel,
            'request_id' => $this->request_id,
            'origin' => $this->origin,
        ];
    }
}
