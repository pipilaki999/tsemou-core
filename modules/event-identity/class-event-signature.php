<?php
namespace TSEMOU\Modules\EventIdentity;

if (!defined('ABSPATH')) exit;

class Event_Signature {
    public static function build($payload = []) {
        $payload = is_array($payload) ? $payload : [];

        $event_type = self::normalize_token($payload['event_type'] ?? ($payload['type'] ?? ''));
        $company_names = self::normalize_terms($payload['company_names'] ?? ($payload['companies'] ?? []));
        $countries = self::normalize_terms($payload['countries'] ?? []);
        $industries = self::normalize_terms($payload['industries'] ?? []);
        $story_status = self::normalize_token($payload['story_status'] ?? ($payload['status'] ?? ''));
        $signals_count = intval($payload['signals_count'] ?? 0);
        $updates_count = intval($payload['updates_count'] ?? 0);
        $year = self::normalize_token($payload['year'] ?? '');

        $features = [
            'event_type' => $event_type !== '' ? $event_type : 'general',
            'company_names' => $company_names,
            'countries' => $countries,
            'industries' => $industries,
            'story_status' => $story_status,
            'company_count' => count($company_names),
            'signals_count' => $signals_count,
            'updates_count' => $updates_count,
            'year' => $year,
        ];

        $codec = function_exists('wp_json_encode') ? wp_json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $codec = is_string($codec) ? $codec : serialize($features);

        return 'evt_' . substr(sha1($codec), 0, 32);
    }

    private static function normalize_token($value) {
        $value = strtolower((string) $value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    private static function normalize_terms($value) {
        $items = is_array($value) ? $value : preg_split('/[\n,;|]+/', (string) $value);
        $normalized = [];

        foreach ($items as $item) {
            $token = self::normalize_token($item);
            if ($token !== '') {
                $normalized[] = $token;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);
        return $normalized;
    }
}
