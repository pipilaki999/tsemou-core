<?php
namespace TSEMOU\Modules\EventIntelligencePhaseC;

if (!defined('ABSPATH')) exit;

/**
 * Phase C result container skeleton.
 */
class Phase_C_Result implements \JsonSerializable {
    /** @var array */
    private $data;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        $this->data = $data;
    }

    /**
     * Read a value from the result payload.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null) {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * Magic property getter proxy.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        return $this->get($key);
    }

    /**
     * Export payload as array.
     *
     * @return array
     */
    public function to_array() {
        return $this->data;
    }

    /**
     * JSON serialization hook.
     *
     * @return array
     */
    public function jsonSerialize() {
        return $this->to_array();
    }
}