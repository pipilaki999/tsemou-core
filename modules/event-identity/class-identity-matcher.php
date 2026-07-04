<?php
namespace TSEMOU\Modules\EventIdentity;

if (!defined('ABSPATH')) exit;

class IdentityMatcher {
    public static function compare($left, $right) {
        $left_name = CandidateNormalizer::normalize($left);
        $right_name = CandidateNormalizer::normalize($right);

        if ($left_name === '' || $right_name === '') {
            return 0.0;
        }

        if ($left_name === $right_name) {
            return 1.0;
        }

        $left_aliases = AliasResolver::resolve($left_name);
        $right_aliases = AliasResolver::resolve($right_name);

        $shared = array_values(array_intersect($left_aliases, $right_aliases));
        if (!empty($shared)) {
            return 0.95;
        }

        $left_tokens = CandidateNormalizer::tokens($left_name);
        $right_tokens = CandidateNormalizer::tokens($right_name);
        $common_tokens = array_values(array_intersect($left_tokens, $right_tokens));
        if (!empty($common_tokens)) {
            $token_score = min(0.9, 0.45 + (0.15 * count($common_tokens)));
            return round($token_score, 2);
        }

        $left_initials = self::initials($left_name);
        $right_initials = self::initials($right_name);
        if ($left_initials !== '' && $left_initials === $right_initials) {
            return 0.4;
        }

        return 0.0;
    }

    private static function initials($value) {
        $tokens = CandidateNormalizer::tokens($value);
        if (empty($tokens)) {
            return '';
        }

        $initials = [];
        foreach ($tokens as $token) {
            $initials[] = substr($token, 0, 1);
        }

        return implode('', $initials);
    }
}
