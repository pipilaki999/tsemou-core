<?php
namespace TSEMOU\Modules\EventIdentity;

if (!defined('ABSPATH')) exit;

class CandidateNormalizer {
    public static function normalize($value, $options = []) {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = self::normalize_unicode($value);
        $value = strtolower($value);
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = trim($value);

        if (!empty($options['greeklish_enabled'])) {
            $value = self::apply_greeklish_placeholder($value);
        }

        return $value;
    }

    public static function normalize_unicode($value) {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        $value = preg_replace('/[\x{0300}-\x{036F}\x{1AB0}-\x{1AFF}\x{1DC0}-\x{1DFF}\x{20D0}-\x{20FF}\x{FE20}-\x{FE2F}]/u', '', $value);
        $value = preg_replace('/[^\p{L}\p{N}\s\-\.]/u', '', $value);

        return $value;
    }

    public static function tokens($value) {
        $normalized = self::normalize($value);
        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $normalized)));
    }

    private static function apply_greeklish_placeholder($value) {
        return $value;
    }
}
