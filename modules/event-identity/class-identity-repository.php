<?php
namespace TSEMOU\Modules\EventIdentity;

if (!defined('ABSPATH')) exit;

class IdentityRepository {
    const OPTION_KEY = 'tsemou_event_identity_repository';

    public static function all() {
        $stored = get_option(self::OPTION_KEY, []);
        return is_array($stored) ? $stored : [];
    }

    public static function save($identity) {
        $identity = is_array($identity) ? $identity : [];
        $identity['signature'] = sanitize_text_field($identity['signature'] ?? '');
        $identity['canonical_name'] = sanitize_text_field($identity['canonical_name'] ?? '');
        $identity['aliases'] = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) ($identity['aliases'] ?? [])))));
        $identity['created_at'] = $identity['created_at'] ?? current_time('mysql');
        $identity['updated_at'] = current_time('mysql');

        $records = self::all();
        $key = $identity['signature'] !== '' ? $identity['signature'] : 'identity_' . substr(sha1($identity['canonical_name'] ?? ''), 0, 24);
        $records[$key] = $identity;
        update_option(self::OPTION_KEY, $records, false);
        return $records[$key];
    }

    public static function find_by_signature($signature) {
        $signature = sanitize_text_field($signature);
        if ($signature === '') {
            return null;
        }

        $records = self::all();
        return $records[$signature] ?? null;
    }

    public static function find_by_alias($value) {
        $normalized = CandidateNormalizer::normalize($value);
        if ($normalized === '') {
            return null;
        }

        foreach (self::all() as $record) {
            $aliases = array_map(function ($alias) {
                return CandidateNormalizer::normalize($alias);
            }, (array) ($record['aliases'] ?? []));
            if (in_array($normalized, $aliases, true)) {
                return $record;
            }
        }

        return null;
    }
}
