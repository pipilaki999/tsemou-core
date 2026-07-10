<?php
namespace TSEMOU\Modules\CanonicalTSEMIT;

if (!defined('ABSPATH')) exit;

class Canonical_TSEMIT_Store {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'tsemou_tsemits';
    }

    public function find_by_entity_user($entity_id, $user_id) {
        global $wpdb;

        $entity_id = absint($entity_id);
        $user_id = absint($user_id);
        if ($entity_id <= 0 || $user_id <= 0) return null;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::table_name() . " WHERE entity_id = %d AND user_id = %d LIMIT 1",
            $entity_id,
            $user_id
        ), ARRAY_A);

        return is_array($row) ? $row : null;
    }

    public function count_active_by_entity($entity_id) {
        global $wpdb;

        $entity_id = absint($entity_id);
        if ($entity_id <= 0) return 0;

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::table_name() . " WHERE entity_id = %d AND state = %s",
            $entity_id,
            'active'
        )));
    }

    public function count_all_records() {
        global $wpdb;
        return intval($wpdb->get_var("SELECT COUNT(*) FROM " . self::table_name()));
    }

    public function upsert_state($entity_id, $user_id, $desired_state, $timestamps = [], $context = []) {
        global $wpdb;

        $entity_id = absint($entity_id);
        $user_id = absint($user_id);
        $desired_state = sanitize_key((string) $desired_state);
        $timestamps = is_array($timestamps) ? $timestamps : [];
        $context = is_array($context) ? $context : [];

        if (!in_array($desired_state, ['active', 'inactive'], true)) {
            return ['success' => false, 'code' => 'invalid_state'];
        }

        if ($entity_id <= 0 || $user_id <= 0) {
            return ['success' => false, 'code' => 'invalid_identity'];
        }

        $now = current_time('mysql');
        $existing = $this->find_by_entity_user($entity_id, $user_id);

        if (!$existing) {
            $created_at = sanitize_text_field((string) ($timestamps['created_at'] ?? $now));
            $activated_at = $desired_state === 'active'
                ? sanitize_text_field((string) ($timestamps['activated_at'] ?? $now))
                : null;
            $deactivated_at = $desired_state === 'inactive'
                ? sanitize_text_field((string) ($timestamps['deactivated_at'] ?? $now))
                : null;

            $inserted = $wpdb->insert(
                self::table_name(),
                [
                    'tsemit_uuid' => function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('tsemit_', true),
                    'entity_id' => $entity_id,
                    'user_id' => $user_id,
                    'state' => $desired_state,
                    'created_at' => $created_at,
                    'updated_at' => $now,
                    'activated_at' => $activated_at,
                    'deactivated_at' => $deactivated_at,
                ],
                ['%s','%d','%d','%s','%s','%s','%s','%s']
            );

            if ($inserted === false) {
                $existing = $this->find_by_entity_user($entity_id, $user_id);
                if (!$existing) return ['success' => false, 'code' => 'insert_failed'];
            } else {
                $existing = $this->find_by_entity_user($entity_id, $user_id);
                return [
                    'success' => true,
                    'changed' => true,
                    'state' => $desired_state,
                    'record' => $existing,
                ];
            }
        }

        $current_state = sanitize_key((string) ($existing['state'] ?? 'inactive'));
        if ($current_state === $desired_state) {
            return [
                'success' => true,
                'changed' => false,
                'state' => $current_state,
                'record' => $existing,
            ];
        }

        $update_data = [
            'state' => $desired_state,
            'updated_at' => $now,
        ];

        if ($desired_state === 'active') {
            $update_data['activated_at'] = sanitize_text_field((string) ($timestamps['activated_at'] ?? $now));
        } else {
            $update_data['deactivated_at'] = sanitize_text_field((string) ($timestamps['deactivated_at'] ?? $now));
        }

        $updated = $wpdb->update(
            self::table_name(),
            $update_data,
            ['tsemit_id' => absint($existing['tsemit_id'])],
            null,
            ['%d']
        );

        if ($updated === false) {
            return ['success' => false, 'code' => 'update_failed'];
        }

        return [
            'success' => true,
            'changed' => true,
            'state' => $desired_state,
            'record' => $this->find_by_entity_user($entity_id, $user_id),
        ];
    }
}
