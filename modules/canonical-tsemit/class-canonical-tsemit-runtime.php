<?php
namespace TSEMOU\Modules\CanonicalTSEMIT;

use TSEMOU\Modules\EntityFoundation\Entity_Migrator;

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/class-canonical-tsemit-schema.php';
require_once __DIR__ . '/class-canonical-tsemit-store.php';
require_once __DIR__ . '/class-canonical-tsemit-service.php';
require_once __DIR__ . '/class-canonical-tsemit-frontend-prep.php';

class Canonical_TSEMIT_Runtime {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public static function is_enabled() {
        return true;
    }

    private function __construct() {
        add_action('init', [$this, 'ensure_schema'], 2);

        Canonical_TSEMIT_Frontend_Prep::instance();
    }

    public function ensure_schema() {
        Canonical_TSEMIT_Schema::ensure_schema();
    }

    public function run_vote_migration_step() {
        Entity_Migrator::run_company_vote_migration_step(30);
    }
}
