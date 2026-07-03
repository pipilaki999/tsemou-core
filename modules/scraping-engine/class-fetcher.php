<?php
namespace TSEMOU\Modules\ScrapingEngine;

if (!defined('ABSPATH')) exit;

class Fetcher {
    public static function fetch(array $job) {
        $url = esc_url_raw($job['source_url'] ?? '');
        $started = current_time('mysql');

        if (!$url) {
            return [
                'ok' => false,
                'status_code' => 0,
                'url' => '',
                'body' => '',
                'error' => 'Missing source URL.',
                'retrieved_at' => $started,
            ];
        }

        $response = wp_remote_get($url, [
            'timeout' => 8,
            'redirection' => 3,
            'user-agent' => 'TSEMOU-Core-Dev/' . (defined('TSEMOU_CORE_VERSION') ? TSEMOU_CORE_VERSION : '3.0'),
        ]);

        if (is_wp_error($response)) {
            return [
                'ok' => false,
                'status_code' => 0,
                'url' => $url,
                'body' => '',
                'error' => $response->get_error_message(),
                'retrieved_at' => $started,
            ];
        }

        $status = intval(wp_remote_retrieve_response_code($response));
        $body = (string) wp_remote_retrieve_body($response);

        return [
            'ok' => ($status >= 200 && $status < 400 && $body !== ''),
            'status_code' => $status,
            'url' => $url,
            'body' => $body,
            'error' => ($status >= 400 ? 'HTTP status ' . $status : ''),
            'retrieved_at' => $started,
        ];
    }
}
