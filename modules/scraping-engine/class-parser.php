<?php
namespace TSEMOU\Modules\ScrapingEngine;

if (!defined('ABSPATH')) exit;

class Parser {
    public static function parse(array $fetch_result, array $job) {
        $body = (string) ($fetch_result['body'] ?? '');
        $title = '';
        $text = '';

        if ($body !== '') {
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $m)) {
                $title = trim(wp_strip_all_tags(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            }
            $text = wp_strip_all_tags($body);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            if (function_exists('mb_substr')) {
                $text = mb_substr($text, 0, 4000);
            } else {
                $text = substr($text, 0, 4000);
            }
        }

        if ($title === '') {
            $title = sanitize_text_field($job['source_label'] ?? 'Raw source acquisition');
        }

        return [
            'title' => $title,
            'text' => $text,
            'content_length' => strlen($body),
            'has_content' => $text !== '',
        ];
    }
}
