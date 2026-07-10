<?php
namespace TSEMOU\Modules\CanonicalTSEMIT;

if (!defined('ABSPATH')) exit;

class Canonical_TSEMIT_Schema {
    const SCHEMA_VERSION = '5.5.0';
    const OPTION_KEY = 'tsemou_canonical_schema_version';

    public static function ensure_schema() {
        $installed = get_option(self::OPTION_KEY, '0');
        if ($installed === self::SCHEMA_VERSION) return;

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $entities_table = $wpdb->prefix . 'tsemou_entities';
        $tsemits_table = $wpdb->prefix . 'tsemou_tsemits';

        $entities_sql = "CREATE TABLE {$entities_table} (
            entity_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_uuid CHAR(36) NOT NULL,
            entity_type VARCHAR(64) NOT NULL,
            entity_category VARCHAR(64) NOT NULL,
            source_object_type VARCHAR(64) DEFAULT NULL,
            source_object_id BIGINT(20) UNSIGNED DEFAULT NULL,
            origin VARCHAR(64) NOT NULL DEFAULT 'wordpress',
            creator_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            is_action TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(32) NOT NULL DEFAULT 'active',
            visibility VARCHAR(32) NOT NULL DEFAULT 'public',
            cached_tsemit_score BIGINT(20) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (entity_id),
            UNIQUE KEY uq_entity_uuid (entity_uuid),
            UNIQUE KEY uq_source_object (source_object_type, source_object_id),
            KEY idx_entity_type (entity_type),
            KEY idx_entity_category (entity_category),
            KEY idx_is_action (is_action),
            KEY idx_status_visibility (status, visibility),
            KEY idx_cached_score (cached_tsemit_score)
        ) {$charset_collate};";

        $tsemits_sql = "CREATE TABLE {$tsemits_table} (
            tsemit_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tsemit_uuid CHAR(36) NOT NULL,
            entity_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            state VARCHAR(16) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            activated_at DATETIME DEFAULT NULL,
            deactivated_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (tsemit_id),
            UNIQUE KEY uq_tsemit_uuid (tsemit_uuid),
            UNIQUE KEY uq_entity_user (entity_id, user_id),
            KEY idx_entity_state (entity_id, state),
            KEY idx_user_state (user_id, state),
            KEY idx_updated_at (updated_at)
        ) {$charset_collate};";

        dbDelta($entities_sql);
        dbDelta($tsemits_sql);

        update_option(self::OPTION_KEY, self::SCHEMA_VERSION, false);
    }
}
