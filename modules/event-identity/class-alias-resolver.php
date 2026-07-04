<?php
namespace TSEMOU\Modules\EventIdentity;

if (!defined('ABSPATH')) exit;

class AliasResolver {
    public static function resolve($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $variants = [];
        $raw_variants = self::candidate_variants($value);
        foreach ($raw_variants as $variant) {
            $normalized = CandidateNormalizer::normalize($variant);
            if ($normalized !== '') {
                $variants[] = $normalized;
            }
        }

        $variants = array_values(array_unique($variants));
        sort($variants);
        return $variants;
    }

    public static function candidate_variants($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $variants = [$value];
        $tokens = preg_split('/\s+/', trim($value));
        if (count($tokens) > 1) {
            $variants[] = implode(' ', array_slice($tokens, 1));
            $variants[] = $tokens[0];
            $variants[] = $tokens[0] . ' ' . end($tokens);
        }

        if (preg_match('/^([A-Za-z]+)\s+([A-Za-z]+)\s+([A-Za-z]+)$/u', $value, $matches)) {
            $variants[] = $matches[1] . ' ' . $matches[3];
            $variants[] = $matches[1] . ' ' . $matches[2][0] . '. ' . $matches[3];
            $variants[] = $matches[1] . ' ' . $matches[2] . ' ' . $matches[3];
        }

        if (preg_match('/^([A-Za-z]+)\s+([A-Za-z]+)$/u', $value, $matches)) {
            $variants[] = $matches[1];
            $variants[] = $matches[2];
        }

        return array_values(array_unique(array_filter($variants)));
    }
}
