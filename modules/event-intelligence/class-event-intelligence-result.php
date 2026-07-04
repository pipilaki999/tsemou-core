<?php
namespace TSEMOU\Modules\EventIntelligence;

if (!defined('ABSPATH')) exit;

class Event_Intelligence_Result implements \JsonSerializable {
    private $data;

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public function get($key, $default = null) {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function __get($key) {
        return $this->get($key);
    }

    public function to_array() {
        return $this->data;
    }

    public function jsonSerialize() {
        return $this->to_array();
    }
}
