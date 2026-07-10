<?php
namespace TSEMOU\Modules\ParticipationEngine;

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/class-participation-engine.php';
require_once __DIR__ . '/adapters/class-http-tsemit-adapter.php';

class Participation_Runtime {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        Participation_Engine::instance();

        if (class_exists('\\TSEMOU\\Modules\\ParticipationEngine\\Adapters\\HTTP_TSEMIT_Adapter')) {
            \TSEMOU\Modules\ParticipationEngine\Adapters\HTTP_TSEMIT_Adapter::instance();
        }
    }
}
