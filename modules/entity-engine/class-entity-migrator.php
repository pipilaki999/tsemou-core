<?php
namespace TSEMOU\Modules\EntityFoundation;

use TSEMOU\Modules\CanonicalTSEMIT\Canonical_TSEMIT_Store;

if (!defined('ABSPATH')) exit;

class Entity_Migrator {
    const ENTITY_STATE_OPTION = 'tsemou_entity_registration_state';
    const VOTE_STATE_OPTION = 'tsemou_canonical_vote_migration_state';
    const LOG_OPTION = 'tsemou_canonical_migration_logs';
    const MIGRATION_TARGET_VERSION = '5.5.0';
    const VOTE_MIGRATION_VERSION_OPTION = 'tsemou_company_vote_migration_version';

    public static function migration_guard_allows($channel = 'manual') {
        $channel = sanitize_key((string) $channel);
        $constant_allows = defined('TSEMOU_ALLOW_MIGRATION_RUN') && TSEMOU_ALLOW_MIGRATION_RUN;
        $filter_allows = (bool) apply_filters('tsemou_allow_migration_run', false, $channel);
        return $constant_allows || $filter_allows;
    }

    public static function run_entity_registration_if_guarded($batch_size = 50, $required_version = self::MIGRATION_TARGET_VERSION, $channel = 'manual') {
        $required_version = sanitize_text_field((string) $required_version);
        if (!self::migration_guard_allows($channel)) {
            return ['success' => false, 'blocked' => true, 'code' => 'guard_not_authorized'];
        }

        $existing_state = get_option(self::ENTITY_STATE_OPTION, []);
        if (is_array($existing_state) && !empty($existing_state['done'])) {
            delete_option(self::ENTITY_STATE_OPTION);
        }

        $state = self::run_entity_registration_step($batch_size);

        return ['success' => true, 'state' => $state, 'version' => $required_version];
    }

    public static function run_entity_registration_cycle_if_guarded($batch_size = 50, $required_version = self::MIGRATION_TARGET_VERSION, $channel = 'manual', $max_steps = 100) {
        $required_version = sanitize_text_field((string) $required_version);
        $max_steps = max(1, intval($max_steps));

        if (!self::migration_guard_allows($channel)) {
            return [
                'success' => false,
                'blocked' => true,
                'code' => 'guard_not_authorized',
                'version' => $required_version,
            ];
        }

        // Reset a previously completed cycle only once at the start of an explicit sync action.
        $existing_state = get_option(self::ENTITY_STATE_OPTION, []);
        if (is_array($existing_state) && !empty($existing_state['done'])) {
            delete_option(self::ENTITY_STATE_OPTION);
        }

        $baseline = get_option(self::ENTITY_STATE_OPTION, []);
        if (!is_array($baseline)) $baseline = [];

        $steps_executed = 0;
        $cap_reached = false;
        $error_code = '';
        $error_message = '';
        $final_state = $baseline;

        while ($steps_executed < $max_steps) {
            $before = get_option(self::ENTITY_STATE_OPTION, []);
            if (!is_array($before)) $before = [];

            $before_type_index = intval($before['type_index'] ?? 0);
            $before_offset = intval($before['offset'] ?? 0);
            $before_done = !empty($before['done']);

            $state = self::run_entity_registration_step($batch_size);
            $steps_executed++;

            if (!is_array($state)) {
                $error_code = 'invalid_state_response';
                $error_message = 'Entity registration step returned an invalid state payload.';
                break;
            }

            $final_state = $state;

            $after_type_index = intval($state['type_index'] ?? 0);
            $after_offset = intval($state['offset'] ?? 0);
            $after_done = !empty($state['done']);

            if ($after_done) {
                break;
            }

            $no_progress = (
                $before_type_index === $after_type_index &&
                $before_offset === $after_offset &&
                !$before_done &&
                !$after_done
            );

            if ($no_progress) {
                $error_code = 'no_progress';
                $error_message = 'Synchronization stopped because no progress was detected between consecutive steps.';
                break;
            }
        }

        if ($steps_executed >= $max_steps && empty($final_state['done']) && $error_code === '') {
            $cap_reached = true;
        }

        $types = Entity_Registry::instance()->supported_source_types();
        $final_type_index = intval($final_state['type_index'] ?? 0);
        $final_source_type = isset($types[$final_type_index]) ? sanitize_key((string) $types[$final_type_index]) : '';

        $baseline_scanned = intval($baseline['scanned_objects'] ?? 0);
        $baseline_created = intval($baseline['created_entities'] ?? 0);
        $baseline_skipped = intval($baseline['existing_entities_skipped'] ?? 0);
        $baseline_failed = intval($baseline['failed_objects'] ?? 0);

        $final_scanned = intval($final_state['scanned_objects'] ?? 0);
        $final_created = intval($final_state['created_entities'] ?? 0);
        $final_skipped = intval($final_state['existing_entities_skipped'] ?? 0);
        $final_failed = intval($final_state['failed_objects'] ?? 0);

        $action_scanned = max(0, $final_scanned - $baseline_scanned);
        $action_created = max(0, $final_created - $baseline_created);
        $action_skipped = max(0, $final_skipped - $baseline_skipped);
        $action_failed = max(0, $final_failed - $baseline_failed);

        return [
            'success' => $error_code === '',
            'blocked' => false,
            'code' => $error_code !== '' ? sanitize_key($error_code) : ($cap_reached ? 'cap_reached' : 'ok'),
            'message' => $error_message,
            'version' => $required_version,
            'steps_executed' => $steps_executed,
            'cap_reached' => $cap_reached,
            'done' => !empty($final_state['done']),
            'final_type_index' => $final_type_index,
            'final_source_type' => $final_source_type,
            'final_offset' => intval($final_state['offset'] ?? 0),
            'action_scanned_objects' => $action_scanned,
            'action_created_entities' => $action_created,
            'action_existing_entities_skipped' => $action_skipped,
            'action_failed_objects' => $action_failed,
            'state' => $final_state,
        ];
    }

    public static function run_company_vote_migration_if_guarded($batch_size = 30, $required_version = self::MIGRATION_TARGET_VERSION, $channel = 'manual') {
        $required_version = sanitize_text_field((string) $required_version);
        if (!self::migration_guard_allows($channel)) {
            return ['success' => false, 'blocked' => true, 'code' => 'guard_not_authorized'];
        }

        if (get_option(self::VOTE_MIGRATION_VERSION_OPTION, '') === $required_version) {
            return ['success' => true, 'already_completed' => true, 'version' => $required_version];
        }

        $state = self::run_company_vote_migration_step($batch_size);
        if (!empty($state['done'])) {
            update_option(self::VOTE_MIGRATION_VERSION_OPTION, $required_version, false);
        }

        return ['success' => true, 'state' => $state, 'version' => $required_version];
    }

    public static function run_entity_registration_step($batch_size = 50) {
        $batch_size = max(1, intval($batch_size));
        $state = get_option(self::ENTITY_STATE_OPTION, [
            'type_index' => 0,
            'offset' => 0,
            'done' => false,
            'processed' => 0,
            'created_or_resolved' => 0,
            'scanned_objects' => 0,
            'created_entities' => 0,
            'existing_entities_skipped' => 0,
            'failed_objects' => 0,
            'started_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        if (!empty($state['done'])) return $state;

        $registry = Entity_Registry::instance();
        $types = $registry->supported_source_types();

        $type_index = intval($state['type_index'] ?? 0);
        $offset = intval($state['offset'] ?? 0);

        if (!isset($types[$type_index])) {
            $state['done'] = true;
            $state['updated_at'] = current_time('mysql');
            update_option(self::ENTITY_STATE_OPTION, $state, false);
            return $state;
        }

        $current_type = $types[$type_index];
        if (!post_type_exists($current_type)) {
            $state['type_index'] = $type_index + 1;
            $state['offset'] = 0;
            $state['updated_at'] = current_time('mysql');
            update_option(self::ENTITY_STATE_OPTION, $state, false);
            return $state;
        }

        $ids = get_posts([
            'post_type' => $current_type,
            'post_status' => ['publish','draft','pending','private','future'],
            'fields' => 'ids',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'ID',
            'order' => 'ASC',
        ]);

        if (empty($ids)) {
            $state['type_index'] = $type_index + 1;
            $state['offset'] = 0;
            $state['updated_at'] = current_time('mysql');
            update_option(self::ENTITY_STATE_OPTION, $state, false);
            return $state;
        }

        foreach ($ids as $object_id) {
            $object_id = absint($object_id);
            $state['processed'] = intval($state['processed'] ?? 0) + 1;
            $state['scanned_objects'] = intval($state['scanned_objects'] ?? 0) + 1;

            $existing = $registry->find_by_source($current_type, $object_id);
            if ($existing) {
                $state['existing_entities_skipped'] = intval($state['existing_entities_skipped'] ?? 0) + 1;
                $state['created_or_resolved'] = intval($state['created_or_resolved'] ?? 0) + 1;
                continue;
            }

            $entity = $registry->ensure_entity_for_object($current_type, $object_id);
            if ($entity) {
                $state['created_entities'] = intval($state['created_entities'] ?? 0) + 1;
                $state['created_or_resolved'] = intval($state['created_or_resolved'] ?? 0) + 1;
            } else {
                $state['failed_objects'] = intval($state['failed_objects'] ?? 0) + 1;
            }
        }

        $state['offset'] = $offset + count($ids);
        $state['updated_at'] = current_time('mysql');
        update_option(self::ENTITY_STATE_OPTION, $state, false);

        return $state;
    }

    public static function run_company_vote_migration_step($batch_size = 30) {
        $batch_size = max(1, intval($batch_size));
        $state = get_option(self::VOTE_STATE_OPTION, [
            'offset' => 0,
            'done' => false,
            'processed_companies' => 0,
            'migrated_records' => 0,
            'skipped_records' => 0,
            'errors' => 0,
            'started_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'last_company_id' => 0,
        ]);

        if (!empty($state['done'])) return $state;

        $companies = get_posts([
            'post_type' => 'company',
            'post_status' => ['publish','draft','pending','private','future'],
            'fields' => 'ids',
            'posts_per_page' => $batch_size,
            'offset' => intval($state['offset'] ?? 0),
            'orderby' => 'ID',
            'order' => 'ASC',
        ]);

        if (empty($companies)) {
            $state['done'] = true;
            $state['updated_at'] = current_time('mysql');
            update_option(self::VOTE_STATE_OPTION, $state, false);
            self::log('vote_migration_complete', ['state' => $state]);
            return $state;
        }

        $registry = Entity_Registry::instance();
        $store = Canonical_TSEMIT_Store::instance();

        foreach ($companies as $company_id) {
            $company_id = absint($company_id);
            $entity = $registry->ensure_entity_for_object('company', $company_id);
            if (!$entity) {
                $state['errors'] = intval($state['errors']) + 1;
                self::log('vote_migration_entity_missing', ['company_id' => $company_id]);
                continue;
            }

            $entity_id = absint($entity['entity_id']);
            $legacy_votes = get_post_meta($company_id, '_tsemou_community_votes', true);
            if (!is_array($legacy_votes) || empty($legacy_votes)) {
                $registry->set_cached_tsemit_score($entity_id, $store->count_active_by_entity($entity_id));
                $state['processed_companies'] = intval($state['processed_companies']) + 1;
                $state['last_company_id'] = $company_id;
                continue;
            }

            foreach ($legacy_votes as $legacy_user_id => $legacy_vote) {
                $user_id = absint($legacy_user_id);
                if ($user_id <= 0) {
                    $state['skipped_records'] = intval($state['skipped_records']) + 1;
                    continue;
                }

                $legacy_vote = is_array($legacy_vote) ? $legacy_vote : [];
                $is_blocked = !empty($legacy_vote['blocked']);
                $state_value = $is_blocked ? 'inactive' : 'active';
                $legacy_time = sanitize_text_field((string) ($legacy_vote['time'] ?? ''));

                $timestamps = [];
                if ($legacy_time) {
                    $timestamps['created_at'] = $legacy_time;
                    if ($state_value === 'active') {
                        $timestamps['activated_at'] = $legacy_time;
                    } else {
                        $timestamps['deactivated_at'] = $legacy_time;
                    }
                }

                $result = $store->upsert_state($entity_id, $user_id, $state_value, $timestamps, ['source' => 'legacy_company_vote']);
                if (!empty($result['success'])) {
                    if (!empty($result['changed'])) {
                        $state['migrated_records'] = intval($state['migrated_records']) + 1;
                    } else {
                        $state['skipped_records'] = intval($state['skipped_records']) + 1;
                    }
                } else {
                    $state['errors'] = intval($state['errors']) + 1;
                }
            }

            $registry->set_cached_tsemit_score($entity_id, $store->count_active_by_entity($entity_id));
            $state['processed_companies'] = intval($state['processed_companies']) + 1;
            $state['last_company_id'] = $company_id;
        }

        $state['offset'] = intval($state['offset']) + count($companies);
        $state['updated_at'] = current_time('mysql');
        update_option(self::VOTE_STATE_OPTION, $state, false);

        self::log('vote_migration_step', ['state' => $state, 'batch_size' => count($companies)]);
        return $state;
    }

    public static function migration_status() {
        return [
            'entity_registration' => get_option(self::ENTITY_STATE_OPTION, []),
            'vote_migration' => get_option(self::VOTE_STATE_OPTION, []),
        ];
    }

    private static function log($event, $payload = []) {
        $logs = get_option(self::LOG_OPTION, []);
        if (!is_array($logs)) $logs = [];

        $logs[] = [
            'ts' => current_time('mysql'),
            'event' => sanitize_key((string) $event),
            'payload' => is_array($payload) ? $payload : [],
        ];

        if (count($logs) > 200) {
            $logs = array_slice($logs, -200);
        }

        update_option(self::LOG_OPTION, $logs, false);

        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[TSEMOU_CANONICAL_MIGRATION] ' . wp_json_encode(end($logs)));
        }
    }
}
