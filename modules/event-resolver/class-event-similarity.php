<?php
namespace TSEMOU\Modules\EventResolver;

if (!defined('ABSPATH')) exit;

use TSEMOU\Modules\EventIdentity\CandidateNormalizer;

class Event_Similarity {
    public static function score($left, $right) {
        $left = is_array($left) ? $left : [];
        $right = is_array($right) ? $right : [];

        $left_text = CandidateNormalizer::normalize($left['title'] ?? ($left['name'] ?? ''));
        $right_text = CandidateNormalizer::normalize($right['title'] ?? ($right['name'] ?? ''));

        if ($left_text === '' || $right_text === '') {
            return 0.0;
        }

        if ($left_text === $right_text) {
            return 1.0;
        }

        $left_tokens = CandidateNormalizer::tokens($left_text);
        $right_tokens = CandidateNormalizer::tokens($right_text);
        $shared = array_intersect($left_tokens, $right_tokens);
        $token_ratio = count($shared) / max(1, min(count($left_tokens), count($right_tokens)));

        $left_company = CandidateNormalizer::normalize($left['company_name'] ?? ($left['company'] ?? ''));
        $right_company = CandidateNormalizer::normalize($right['company_name'] ?? ($right['company'] ?? ''));
        $company_bonus = ($left_company !== '' && $left_company === $right_company) ? 0.25 : 0.0;
        $type_bonus = ($left['event_type'] ?? '') === ($right['event_type'] ?? '') ? 0.15 : 0.0;

        return round(min(1.0, $token_ratio + $company_bonus + $type_bonus), 2);
    }
}
