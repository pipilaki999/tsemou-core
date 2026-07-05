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
        // TODO: Keep Phase C result read access stable and immutable-style.
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /**
     * Magic property getter proxy.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        // TODO: Keep property access behavior aligned with Phase B style.
        return $this->get($key);
    }

    /**
     * Export payload as array.
     *
     * @return array
     */
    public function to_array() {
        // TODO: Keep serialization payload contract stable for Phase C consumers.
        return $this->data;
    }

    /**
     * JSON serialization hook.
     *
     * @return array
     */
    public function jsonSerialize() {
        // TODO: Keep JSON output deterministic for future API/UI layers.
        return $this->to_array();
    }
}