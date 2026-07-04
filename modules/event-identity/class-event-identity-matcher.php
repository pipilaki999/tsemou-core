<?php
namespace TSEMOU\Modules\EventIdentity;

if (!defined('ABSPATH')) exit;

class Event_Identity_Matcher {
    public static function match(Event_Candidate $candidate, $limit = 5) {
        $limit = max(1, absint($limit));
        $matches = [];
        $stories = get_posts([
            'post_type' => 'story',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'numberposts' => 200,
            'orderby' => 'ID',
            'order' => 'ASC',
        ]);

        foreach ($stories as $story) {
            if (intval($story->ID) === intval($candidate->story_id)) {
                continue;
            }

            $existing = Event_Candidate::from_story_post($story->ID);
            if (!$existing) {
                continue;
            }

            $score = self::score($candidate, $existing);
            if ($score <= 0) {
                continue;
            }

            $matches[] = [
                'story_id' => intval($story->ID),
                'title' => $story->post_title,
                'signature' => $existing->signature,
                'score' => round($score, 3),
                'candidate' => $existing->to_array(),
            ];
        }

        usort($matches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($matches, 0, $limit);
    }

    public static function score(Event_Candidate $candidate, Event_Candidate $existing) {
        if ($candidate->signature !== '' && $candidate->signature === $existing->signature) {
            return 1.0;
        }

        $company_overlap = self::overlap_count($candidate->company_names, $existing->company_names);
        $country_overlap = self::overlap_count($candidate->countries, $existing->countries);
        $industry_overlap = self::overlap_count($candidate->industries, $existing->industries);
        $type_match = $candidate->event_type === $existing->event_type ? 1 : 0;

        $score = 0.0;
        $score += $type_match * 0.4;
        $score += min(0.4, $company_overlap * 0.2);
        $score += min(0.1, $country_overlap * 0.05);
        $score += min(0.1, $industry_overlap * 0.05);

        return $score;
    }

    private static function overlap_count($left, $right) {
        $left = array_values(array_unique(array_filter((array) $left)));
        $right = array_values(array_unique(array_filter((array) $right)));
        if (!$left || !$right) {
            return 0;
        }

        $left_set = array_fill_keys($left, true);
        $overlap = 0;
        foreach ($right as $item) {
            if (isset($left_set[$item])) {
                $overlap++;
            }
        }

        return $overlap / max(1, min(count($left), count($right)));
    }
}
