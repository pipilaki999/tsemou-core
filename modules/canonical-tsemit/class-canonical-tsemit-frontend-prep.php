<?php
namespace TSEMOU\Modules\CanonicalTSEMIT;

if (!defined('ABSPATH')) exit;

class Canonical_TSEMIT_Frontend_Prep {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'register_assets'], 20);
    }

    public function register_assets() {
        wp_register_script(
            'tsemou-canonical-tsemit-component',
            TSEMOU_CORE_URL . 'assets/js/canonical-tsemit-component.js',
            [],
            defined('TSEMOU_CORE_VERSION') ? TSEMOU_CORE_VERSION : null,
            true
        );

        wp_register_style(
            'tsemou-canonical-tsemit-component',
            TSEMOU_CORE_URL . 'assets/css/canonical-tsemit-component.css',
            [],
            defined('TSEMOU_CORE_VERSION') ? TSEMOU_CORE_VERSION : null
        );
    }
}
