<?php
namespace TSEMOU\Modules\SourceDiscovery;

if (!defined('ABSPATH')) exit;

class Source_Resolver {
    public static function resolve(array $task) {
        $country = strtoupper(sanitize_text_field($task['country'] ?? ''));
        $industry = sanitize_key($task['industry'] ?? '');
        $query = self::query_from_task($task);
        $sources = Source_Registry::enabled_sources();
        $resolved = [];

        foreach ($sources as $id => $source) {
            if (!is_array($source)) continue;
            if (!self::matches_country($source, $country)) continue;
            if (!self::matches_industry($source, $industry)) continue;

            $resolved[] = [
                'source_id' => sanitize_key($id),
                'source_label' => sanitize_text_field($source['label'] ?? $id),
                'source_type' => sanitize_key($source['type'] ?? 'generic'),
                'source_priority' => intval($source['priority'] ?? 50),
                'source_url' => esc_url_raw(self::render_url($source['url_template'] ?? '', $query, $task)),
                'source_description' => sanitize_text_field($source['description'] ?? ''),
            ];
        }

        return Source_Prioritizer::prioritize($resolved, $task);
    }

    public static function query_from_task(array $task) {
        $country = $task['country_name'] ?? $task['country'] ?? '';
        $industry = $task['industry_label'] ?? $task['industry'] ?? '';
        $rank = $task['rank_band'] ?? '';
        return trim($country . ' ' . $industry . ' companies ' . $rank);
    }

    private static function matches_country(array $source, $country) {
        if (empty($source['countries']) || !is_array($source['countries'])) return true;
        return in_array($country, array_map('strtoupper', $source['countries']), true);
    }

    private static function matches_industry(array $source, $industry) {
        if (empty($source['industries']) || !is_array($source['industries'])) return true;
        return in_array($industry, array_map('sanitize_key', $source['industries']), true);
    }

    private static function render_url($template, $query, array $task) {
        $replacements = [
            '{query}' => rawurlencode($query),
            '{country}' => rawurlencode($task['country'] ?? ''),
            '{country_name}' => rawurlencode($task['country_name'] ?? $task['country'] ?? ''),
            '{industry}' => rawurlencode($task['industry'] ?? ''),
            '{industry_label}' => rawurlencode($task['industry_label'] ?? $task['industry'] ?? ''),
            '{wave}' => rawurlencode($task['wave'] ?? ''),
            '{rank_band}' => rawurlencode($task['rank_band'] ?? ''),
        ];
        return strtr((string)$template, $replacements);
    }
}
