<?php
namespace TSEMOU\Modules\ScrapingEngine;

if (!defined('ABSPATH')) exit;

class Normalizer {
    public static function normalize(array $job, array $fetch_result, array $parsed) {
        return [
            'raw_id' => self::raw_id($job),
            'created_at' => current_time('mysql'),
            'phase' => 'A',
            'status' => !empty($fetch_result['ok']) ? 'fetched' : 'fetch_failed',
            'source_id' => sanitize_key($job['source_id'] ?? ''),
            'source_label' => sanitize_text_field($job['source_label'] ?? ''),
            'source_type' => sanitize_key($job['source_type'] ?? ''),
            'source_url' => esc_url_raw($job['source_url'] ?? $fetch_result['url'] ?? ''),
            'country' => sanitize_text_field($job['country'] ?? ''),
            'country_name' => sanitize_text_field($job['country_name'] ?? ''),
            'industry' => sanitize_key($job['industry'] ?? ''),
            'industry_label' => sanitize_text_field($job['industry_label'] ?? ''),
            'wave' => sanitize_key($job['wave'] ?? ''),
            'rank_band' => sanitize_text_field($job['rank_band'] ?? ''),
            'title' => sanitize_text_field($parsed['title'] ?? ''),
            'content_excerpt' => sanitize_textarea_field($parsed['text'] ?? ''),
            'metadata' => [
                'http_status' => intval($fetch_result['status_code'] ?? 0),
                'retrieved_at' => sanitize_text_field($fetch_result['retrieved_at'] ?? current_time('mysql')),
                'content_length' => intval($parsed['content_length'] ?? 0),
                'has_content' => !empty($parsed['has_content']),
                'error' => sanitize_text_field($fetch_result['error'] ?? ''),
            ],
        ];
    }

    public static function raw_id(array $job) {
        $parts = [
            $job['source_id'] ?? '',
            $job['source_url'] ?? '',
            $job['country'] ?? '',
            $job['industry'] ?? '',
            $job['wave'] ?? '',
            $job['rank_band'] ?? '',
        ];
        return 'raw_' . md5(implode('|', array_map('strval', $parts)));
    }
}
