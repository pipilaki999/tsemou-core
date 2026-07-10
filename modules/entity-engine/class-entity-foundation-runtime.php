<?php
namespace TSEMOU\Modules\EntityFoundation;

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/class-entity-registry.php';
require_once __DIR__ . '/class-entity-migrator.php';

class Entity_Foundation_Runtime {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        // Migration execution is explicit-only via guard wrappers.
    }

    public function run_foundation_steps() {
        Entity_Migrator::run_entity_registration_step(50);
    }
}
