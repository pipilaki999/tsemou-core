<?php
namespace TSEMOU\Modules\EventIdentity;

if (!defined('ABSPATH')) exit;

class Event_Candidate {
    public $story_id;
    public $title;
    public $summary;
    public $story_status;
    public $event_type;
    public $company_ids;
    public $company_names;
    public $countries;
    public $industries;
    public $signals_count;
    public $updates_count;
    public $year;
    public $signature;

    public function __construct($data = []) {
        $data = is_array($data) ? $data : [];
        $this->story_id = intval($data['story_id'] ?? 0);
        $this->title = sanitize_text_field($data['title'] ?? '');
        $this->summary = sanitize_textarea_field($data['summary'] ?? '');
        $this->story_status = sanitize_key($data['story_status'] ?? '');
        $this->event_type = sanitize_key($data['event_type'] ?? 'general');
        $this->company_ids = array_values(array_unique(array_filter(array_map('intval', (array) ($data['company_ids'] ?? [])))));
        $this->company_names = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) ($data['company_names'] ?? [])))));
        $this->countries = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) ($data['countries'] ?? [])))));
        $this->industries = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) ($data['industries'] ?? [])))));
        $this->signals_count = intval($data['signals_count'] ?? 0);
        $this->updates_count = intval($data['updates_count'] ?? 0);
        $this->year = sanitize_text_field($data['year'] ?? '');
        $this->signature = sanitize_text_field($data['signature'] ?? '');
    }

    public static function from_story_post($story_id) {
        $story_id = absint($story_id);
        $post = get_post($story_id);

        if (!$post || $post->post_type !== 'story') {
            return null;
        }

        $summary = get_post_meta($story_id, '_tsemou_executive_summary', true);
        $summary = $summary !== '' ? $summary : $post->post_excerpt;
        $summary = $summary !== '' ? $summary : wp_strip_all_tags($post->post_content);

        $company_ids = [];
        if (class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine')) {
            $company_ids = \TSEMOU\Modules\CompanyEngine\Company_Engine::get_connected_company_ids($story_id);
        }

        $company_names = [];
        $countries = [];
        $industries = [];

        foreach ($company_ids as $company_id) {
            $company_title = get_the_title($company_id);
            if ($company_title !== '') {
                $company_names[] = $company_title;
            }

            if (class_exists('\\TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine')) {
                $identity = \TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine::get_company_identity($company_id);
                if (!empty($identity['canonical_name'])) {
                    $company_names[] = $identity['canonical_name'];
                }
                if (!empty($identity['country'])) {
                    $countries[] = $identity['country'];
                }
                if (!empty($identity['industry'])) {
                    $industries[] = $identity['industry'];
                }
            }
        }

        $company_names = array_values(array_unique(array_filter($company_names)));
        $countries = array_values(array_unique(array_filter($countries)));
        $industries = array_values(array_unique(array_filter($industries)));

        $payload = [
            'story_id' => $story_id,
            'title' => $post->post_title,
            'summary' => $summary,
            'story_status' => get_post_meta($story_id, '_tsemou_status', true) ?: 'active',
            'event_type' => self::infer_event_type($post, $summary, $company_names),
            'company_ids' => $company_ids,
            'company_names' => $company_names,
            'countries' => $countries,
            'industries' => $industries,
            'signals_count' => intval(get_post_meta($story_id, '_tsemou_signals_count', true) ?: 0),
            'updates_count' => intval(get_post_meta($story_id, '_tsemou_updates_count', true) ?: 0),
            'year' => date('Y', strtotime($post->post_date_gmt ?: $post->post_date)),
        ];

        $payload['signature'] = Event_Signature::build($payload);
        return new self($payload);
    }

    private static function infer_event_type($post, $summary, $company_names) {
        $source = strtolower(trim(($post->post_title ?? '') . ' ' . ($summary ?? '') . ' ' . implode(' ', $company_names)));
        $source = preg_replace('/[^a-z0-9]+/', ' ', $source);

        if (preg_match('/court|lawsuit|case|settlement|convict|charges|trial|investigation|probe|regulator|regulatory|sanction|violation|fraud|scandal|misconduct/', $source)) {
            return 'legal';
        }
        if (preg_match('/merger|acquisition|takeover|sale|purchase|deal|partnership|alliance/', $source)) {
            return 'business';
        }
        if (preg_match('/bankrupt|restructur|closure|layoff|strike|union|employment/', $source)) {
            return 'labor';
        }
        if (preg_match('/product|recall|incident|safety|fire|accident|hazard|contamination/', $source)) {
            return 'incident';
        }

        return 'general';
    }

    public function to_array() {
        return [
            'story_id' => $this->story_id,
            'title' => $this->title,
            'summary' => $this->summary,
            'story_status' => $this->story_status,
            'event_type' => $this->event_type,
            'company_ids' => $this->company_ids,
            'company_names' => $this->company_names,
            'countries' => $this->countries,
            'industries' => $this->industries,
            'signals_count' => $this->signals_count,
            'updates_count' => $this->updates_count,
            'year' => $this->year,
            'signature' => $this->signature,
        ];
    }
}
