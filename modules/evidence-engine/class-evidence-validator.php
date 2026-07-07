<?php
namespace TSEMOU\Modules\EvidenceEngine;

if (!defined('ABSPATH')) exit;

/**
 * TSEMOU Evidence Validator v2.5.0
 *
 * Validation layer above Evidence_Engine normalization.
 * This decides whether an Evidence item is ready to influence Trust.
 */
class Evidence_Validator {

    public static function validate($evidence_id) {
        $evidence_id = absint($evidence_id);

        if (!class_exists('\TSEMOU\Modules\EvidenceEngine\Evidence_Engine')) {
            return [
                'evidence_id' => $evidence_id,
                'valid' => false,
                'ready_for_trust' => false,
                'score' => 0,
                'errors' => ['evidence_engine_not_loaded'],
                'warnings' => [],
                'checks' => [],
            ];
        }

        $resolution = Evidence_Engine::resolve_post_or_evidence_id($evidence_id, true);
        if (empty($resolution['success'])) {
            $code = sanitize_key($resolution['code'] ?? 'invalid_evidence');
            return [
                'evidence_id' => $evidence_id,
                'valid' => false,
                'ready_for_trust' => false,
                'score' => 0,
                'errors' => [$code],
                'warnings' => [],
                'checks' => [],
                'resolution' => $resolution,
            ];
        }

        $resolved_evidence_id = absint($resolution['evidence_id'] ?? 0);
        $evidence = Evidence_Engine::get($resolved_evidence_id);

        if (!$evidence) {
            return [
                'evidence_id' => $evidence_id,
                'valid' => false,
                'ready_for_trust' => false,
                'score' => 0,
                'errors' => ['invalid_evidence'],
                'warnings' => [],
                'checks' => [],
                'resolution' => $resolution,
            ];
        }

        $errors = [];
        $warnings = [];
        $checks = [];

        $checks['company_relation'] = self::check_company_relation($evidence);
        $checks['source'] = self::check_source($evidence);
        $checks['credibility'] = self::check_credibility($evidence);
        $checks['date'] = self::check_date($evidence);
        $checks['sentiment'] = self::check_sentiment($evidence);
        $checks['kind'] = self::check_kind($evidence);
        $checks['summary'] = self::check_summary($evidence);
        $checks['duplicate'] = self::check_duplicate($evidence);

        foreach ($checks as $check) {
            foreach ($check['errors'] as $error) $errors[] = $error;
            foreach ($check['warnings'] as $warning) $warnings[] = $warning;
        }

        $warnings = array_values(array_unique(array_filter($warnings)));
        $errors = array_values(array_unique(array_filter($errors)));

        $score = self::validation_score($checks, $errors, $warnings);
        $ready_for_trust = empty($errors) && $score >= 70;

        return [
            'evidence_id' => $resolved_evidence_id,
            'title' => $evidence['title'] ?? '',
            'valid' => empty($errors),
            'ready_for_trust' => $ready_for_trust,
            'score' => $score,
            'errors' => $errors,
            'warnings' => $warnings,
            'checks' => $checks,
            'normalized' => $evidence,
            'resolution' => $resolution,
        ];
    }

    public static function validate_for_company($company_id, $limit = 50) {
        $company_id = absint($company_id);

        if (!$company_id || !class_exists('\TSEMOU\Modules\EvidenceEngine\Evidence_Engine')) {
            return [];
        }

        $items = Evidence_Engine::get_for_company($company_id, $limit);
        $out = [];

        foreach ($items as $item) {
            $out[] = self::validate($item['id']);
        }

        return $out;
    }

    private static function pass($message = 'ok', $extra = []) {
        return array_merge([
            'pass' => true,
            'message' => $message,
            'errors' => [],
            'warnings' => [],
        ], $extra);
    }

    private static function fail($error, $message = '', $extra = []) {
        return array_merge([
            'pass' => false,
            'message' => $message ?: $error,
            'errors' => [$error],
            'warnings' => [],
        ], $extra);
    }

    private static function warn($warning, $message = '', $extra = []) {
        return array_merge([
            'pass' => true,
            'message' => $message ?: $warning,
            'errors' => [],
            'warnings' => [$warning],
        ], $extra);
    }

    public static function check_company_relation($evidence) {
        $ids = $evidence['relations']['company_ids'] ?? [];

        if (empty($ids)) {
            return self::fail('missing_company_relation', 'Evidence is not connected to a company.');
        }

        $invalid = [];
        foreach ($ids as $id) {
            if (get_post_type(absint($id)) !== 'company') $invalid[] = $id;
        }

        if (!empty($invalid)) {
            return self::fail('invalid_company_relation', 'Evidence has invalid company relation.', ['invalid_ids' => $invalid]);
        }

        return self::pass('Company relation exists.', ['company_ids' => $ids]);
    }

    public static function check_source($evidence) {
        $url = $evidence['source']['url'] ?? '';

        if (empty($url)) {
            return self::warn('missing_source_url', 'Source URL is missing.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return self::fail('invalid_source_url', 'Source URL is invalid.', ['url' => $url]);
        }

        $domain = parse_url($url, PHP_URL_HOST);
        if (empty($domain)) {
            return self::warn('missing_source_domain', 'Source domain could not be detected.');
        }

        return self::pass('Source URL is valid.', [
            'url' => $url,
            'domain' => $domain,
            'source_class' => self::source_class($domain),
        ]);
    }

    public static function check_credibility($evidence) {
        $cred = $evidence['credibility'] ?? [];
        $score = $cred['score'] ?? null;

        if ($score === null) {
            $domain = $evidence['source']['label'] ?? '';
            $suggested = self::suggest_credibility_from_domain($domain);

            if ($suggested !== null) {
                return self::warn('missing_credibility_but_source_known', 'Credibility missing; known source can suggest baseline.', [
                    'suggested_credibility' => $suggested,
                    'source_domain' => $domain,
                ]);
            }

            return self::warn('missing_credibility', 'Credibility is missing.');
        }

        if ($score < 0 || $score > 10) {
            return self::fail('invalid_credibility_range', 'Credibility must be 0-10.', ['score' => $score]);
        }

        if ($score < 4) {
            return self::warn('low_credibility', 'Low credibility source.', ['score' => $score]);
        }

        return self::pass('Credibility is valid.', ['score' => $score]);
    }

    public static function check_date($evidence) {
        $iso = $evidence['date']['iso'] ?? '';

        if (empty($iso)) {
            return self::warn('missing_evidence_date', 'Evidence date is missing.');
        }

        $ts = strtotime($iso);
        if (!$ts) {
            return self::fail('invalid_evidence_date', 'Evidence date is invalid.', ['date' => $iso]);
        }

        if ($ts > time() + DAY_IN_SECONDS) {
            return self::warn('future_evidence_date', 'Evidence date appears to be in the future.', ['date' => $iso]);
        }

        return self::pass('Evidence date is valid.', ['date' => $iso]);
    }

    public static function check_sentiment($evidence) {
        $value = $evidence['sentiment']['value'] ?? '';

        if (!in_array($value, ['positive', 'negative', 'neutral'], true)) {
            return self::warn('unknown_sentiment', 'Sentiment is unknown.', ['sentiment' => $value]);
        }

        return self::pass('Sentiment is valid.', ['sentiment' => $value]);
    }

    public static function check_kind($evidence) {
        $value = $evidence['kind']['value'] ?? '';

        if (empty($value) || $value === 'uncategorized') {
            return self::warn('uncategorized_evidence', 'Evidence kind is uncategorized.');
        }

        return self::pass('Evidence kind is valid.', ['kind' => $value]);
    }

    public static function check_summary($evidence) {
        $summary = trim((string) ($evidence['summary'] ?? ''));

        if ($summary === '' || $summary === 'No clean evidence summary available yet.') {
            return self::warn('missing_clean_summary', 'Clean evidence summary is missing.');
        }

        if (str_word_count(wp_strip_all_tags($summary)) < 5) {
            return self::warn('short_summary', 'Evidence summary is very short.');
        }

        return self::pass('Evidence summary is usable.');
    }

    public static function check_duplicate($evidence) {
        $id = absint($evidence['id'] ?? 0);
        $url = $evidence['source']['url'] ?? '';
        $title = $evidence['title'] ?? '';

        $duplicates = [];

        if (!empty($url)) {
            $dupes = get_posts([
                'post_type' => Evidence_Engine::evidence_post_types(),
                'post_status' => ['publish', 'draft', 'pending'],
                'numberposts' => 10,
                'post__not_in' => [$id],
                'meta_query' => [
                    'relation' => 'OR',
                    ['key' => '_tsemou_source_url', 'value' => $url, 'compare' => '='],
                    ['key' => 'source_url', 'value' => $url, 'compare' => '='],
                    ['key' => 'url', 'value' => $url, 'compare' => '='],
                    ['key' => 'evidence_url', 'value' => $url, 'compare' => '='],
                ],
            ]);

            foreach ($dupes as $dupe) $duplicates[] = $dupe->ID;
        }

        if (!empty($title)) {
            $title_dupes = get_posts([
                'post_type' => Evidence_Engine::evidence_post_types(),
                'post_status' => ['publish', 'draft', 'pending'],
                'numberposts' => 10,
                'post__not_in' => [$id],
                's' => $title,
            ]);

            foreach ($title_dupes as $dupe) {
                if (strcasecmp(get_the_title($dupe->ID), $title) === 0) $duplicates[] = $dupe->ID;
            }
        }

        $duplicates = array_values(array_unique(array_filter($duplicates)));

        if (!empty($duplicates)) {
            return self::warn('possible_duplicate_evidence', 'Possible duplicate evidence detected.', ['duplicate_ids' => $duplicates]);
        }

        return self::pass('No duplicate detected.');
    }

    public static function validation_score($checks, $errors, $warnings) {
        $score = 100;

        $weights = [
            'missing_company_relation' => 35,
            'invalid_company_relation' => 35,
            'invalid_source_url' => 25,
            'invalid_evidence_date' => 15,
            'invalid_credibility_range' => 20,
            'missing_source_url' => 15,
            'missing_credibility' => 10,
            'missing_credibility_but_source_known' => 6,
            'missing_clean_summary' => 8,
            'uncategorized_evidence' => 5,
            'possible_duplicate_evidence' => 18,
            'low_credibility' => 8,
            'unknown_sentiment' => 6,
        ];

        foreach ($errors as $error) {
            $score -= $weights[$error] ?? 20;
        }

        foreach ($warnings as $warning) {
            $score -= $weights[$warning] ?? 5;
        }

        return max(0, min(100, intval($score)));
    }

    public static function source_class($domain) {
        $domain = strtolower((string) $domain);

        if (class_exists('\\TSEMOU\\Modules\\SourceIntelligence\\Source_Intelligence_Engine')) {
            $source = \TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine::analyze($domain);
            return $source['class'] ?? 'unknown';
        }

        if (preg_match('/(reuters|apnews|bbc|who\.int|europa\.eu|sec\.gov|fda\.gov|un\.org|worldbank\.org)/', $domain)) return 'high_authority';
        if (preg_match('/(gov|edu|int)$/', $domain)) return 'institutional';
        if (preg_match('/(medium|substack|blogspot|wordpress)/', $domain)) return 'independent_blog';

        return 'unknown';
    }

    public static function suggest_credibility_from_domain($domain) {
        if (class_exists('\\TSEMOU\\Modules\\SourceIntelligence\\Source_Intelligence_Engine')) {
            return \TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine::credibility_for_url($domain);
        }

        $class = self::source_class($domain);
        if ($class === 'high_authority') return 9.0;
        if ($class === 'institutional') return 8.0;
        if ($class === 'independent_blog') return 4.5;
        return null;
    }
}
