<?php
namespace TSEMOU\Modules\ProofEngine;
if (!defined('ABSPATH')) exit;
class Proof_Engine {
    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    private function __construct() {
        add_action('init', [$this, 'register_proof_cpt']);
        add_action('add_meta_boxes', [$this, 'add_proof_meta_boxes']);
        add_action('save_post_tsemou_proof', [$this, 'save_proof_meta'], 10, 2);
    }
    public function register_proof_cpt() {
        register_post_type('tsemou_proof', [
            'labels' => [
                'name' => 'Proofs',
                'singular_name' => 'Proof',
                'menu_name' => 'Proofs',
                'add_new_item' => 'Add New Proof',
                'edit_item' => 'Edit Proof',
                'all_items' => 'All Proofs'
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'tsemou-os',
            'menu_icon' => 'dashicons-media-document',
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'author', 'revisions'],
            'rewrite' => ['slug' => 'proofs'],
        ]);
    }
    public function add_proof_meta_boxes() {
        add_meta_box('tsemou_proof_details','TSEMOU Proof Details',[$this,'render_proof_details'],'tsemou_proof','normal','high');
    }
    public function render_proof_details($post) {
        wp_nonce_field('tsemou_save_proof_meta', 'tsemou_proof_nonce');
        $source = get_post_meta($post->ID, '_tsemou_proof_source', true);
        $url = get_post_meta($post->ID, '_tsemou_proof_url', true);
        $type = get_post_meta($post->ID, '_tsemou_proof_type', true) ?: 'report';
        $reliability = get_post_meta($post->ID, '_tsemou_proof_reliability', true) ?: 'medium';
        $file_id = get_post_meta($post->ID, '_tsemou_related_file', true);
        $evidence_type = get_post_meta($post->ID, '_tsemou_evidence_type', true) ?: $type;
        $source_type = get_post_meta($post->ID, '_tsemou_source_type', true) ?: 'media';
        $verification_level = get_post_meta($post->ID, '_tsemou_verification_level', true) ?: 'unverified';
        $legal_status = get_post_meta($post->ID, '_tsemou_legal_status', true) ?: 'not_legal';
        $severity = get_post_meta($post->ID, '_tsemou_severity', true) ?: 'medium';
        $trust_include = get_post_meta($post->ID, '_tsemou_trust_include', true);
        if ($trust_include === '') $trust_include = 'yes';
        $manual_adjustment_note = get_post_meta($post->ID, '_tsemou_manual_adjustment_note', true);
        $rel_supports = get_post_meta($post->ID, '_tsemou_rel_supports', true);
        $rel_contradicts = get_post_meta($post->ID, '_tsemou_rel_contradicts', true);
        $rel_duplicates = get_post_meta($post->ID, '_tsemou_rel_duplicates', true);
        $rel_updates = get_post_meta($post->ID, '_tsemou_rel_updates', true);
        $rel_supersedes = get_post_meta($post->ID, '_tsemou_rel_supersedes', true);
        $rel_related = get_post_meta($post->ID, '_tsemou_rel_related', true);
        ?>
        <div class="tsemou-proof-box">
            <label>Source</label>
            <input type="text" name="tsemou_proof_source" value="<?php echo esc_attr($source); ?>" placeholder="Reuters, WWF, UN, Court document">
            <label>Source URL</label>
            <input type="url" name="tsemou_proof_url" value="<?php echo esc_attr($url); ?>" placeholder="https://...">
            <label>Proof Type</label>
            <select name="tsemou_proof_type">
                <option value="report" <?php selected($type, 'report'); ?>>Report</option>
                <option value="news" <?php selected($type, 'news'); ?>>News Source</option>
                <option value="court" <?php selected($type, 'court'); ?>>Court Decision</option>
                <option value="ngo" <?php selected($type, 'ngo'); ?>>NGO Report</option>
                <option value="academic" <?php selected($type, 'academic'); ?>>Academic Paper</option>
                <option value="government" <?php selected($type, 'government'); ?>>Government Data</option>
                <option value="video" <?php selected($type, 'video'); ?>>Video</option>
            </select>
            <label>Reliability</label>
            <select name="tsemou_proof_reliability">
                <option value="high" <?php selected($reliability, 'high'); ?>>High</option>
                <option value="medium" <?php selected($reliability, 'medium'); ?>>Medium</option>
                <option value="low" <?php selected($reliability, 'low'); ?>>Low</option>
            </select>
            <label>Connected TSEMOU File</label>
            <select name="tsemou_related_file">
                <option value="">— Select File —</option>
                <?php
                $items = get_posts(['post_type'=>'story','numberposts'=>100,'post_status'=>['publish','draft','pending']]);
                foreach ($items as $item) {
                    echo '<option value="'.esc_attr($item->ID).'" '.selected($file_id,$item->ID,false).'>'.esc_html($item->post_title).'</option>';
                }
                ?>
            </select>

            <hr>
            <h3>Evidence Engine v2</h3>

            <label>Evidence Type</label>
            <select name="tsemou_evidence_type">
                <option value="court_decision" <?php selected($evidence_type, 'court_decision'); ?>>Court Decision</option>
                <option value="court_case" <?php selected($evidence_type, 'court_case'); ?>>Court Case</option>
                <option value="government_report" <?php selected($evidence_type, 'government_report'); ?>>Government Report</option>
                <option value="scientific_study" <?php selected($evidence_type, 'scientific_study'); ?>>Scientific Study</option>
                <option value="ngo_report" <?php selected($evidence_type, 'ngo_report'); ?>>NGO Report</option>
                <option value="audit" <?php selected($evidence_type, 'audit'); ?>>Audit</option>
                <option value="journalistic_investigation" <?php selected($evidence_type, 'journalistic_investigation'); ?>>Journalistic Investigation</option>
                <option value="official_statement" <?php selected($evidence_type, 'official_statement'); ?>>Official Statement</option>
                <option value="whistleblower" <?php selected($evidence_type, 'whistleblower'); ?>>Whistleblower</option>
                <option value="former_employee" <?php selected($evidence_type, 'former_employee'); ?>>Former Employee / Former Partner</option>
                <option value="community_report" <?php selected($evidence_type, 'community_report'); ?>>Community Report</option>
                <option value="other" <?php selected($evidence_type, 'other'); ?>>Other</option>
            </select>

            <label>Source Type</label>
            <select name="tsemou_source_type">
                <option value="court" <?php selected($source_type, 'court'); ?>>Court</option>
                <option value="government" <?php selected($source_type, 'government'); ?>>Government</option>
                <option value="academic" <?php selected($source_type, 'academic'); ?>>Academic / Scientific</option>
                <option value="ngo" <?php selected($source_type, 'ngo'); ?>>NGO</option>
                <option value="journalist" <?php selected($source_type, 'journalist'); ?>>Journalist / Media</option>
                <option value="former_employee" <?php selected($source_type, 'former_employee'); ?>>Former Employee</option>
                <option value="former_partner" <?php selected($source_type, 'former_partner'); ?>>Former Partner / Supplier</option>
                <option value="company" <?php selected($source_type, 'company'); ?>>Company</option>
                <option value="community" <?php selected($source_type, 'community'); ?>>Community</option>
                <option value="anonymous" <?php selected($source_type, 'anonymous'); ?>>Anonymous</option>
                <option value="other" <?php selected($source_type, 'other'); ?>>Other</option>
            </select>

            <label>Verification Level</label>
            <select name="tsemou_verification_level">
                <option value="unverified" <?php selected($verification_level, 'unverified'); ?>>Unverified</option>
                <option value="partially_verified" <?php selected($verification_level, 'partially_verified'); ?>>Partially Verified</option>
                <option value="verified_documents" <?php selected($verification_level, 'verified_documents'); ?>>Verified by Documents</option>
                <option value="multiple_sources" <?php selected($verification_level, 'multiple_sources'); ?>>Multiple Independent Sources</option>
                <option value="government_verified" <?php selected($verification_level, 'government_verified'); ?>>Government Verified</option>
                <option value="court_verified" <?php selected($verification_level, 'court_verified'); ?>>Court Verified</option>
            </select>

            <label>Legal Status</label>
            <select name="tsemou_legal_status">
                <option value="not_legal" <?php selected($legal_status, 'not_legal'); ?>>Not Legal</option>
                <option value="allegation" <?php selected($legal_status, 'allegation'); ?>>Allegation</option>
                <option value="under_investigation" <?php selected($legal_status, 'under_investigation'); ?>>Under Investigation</option>
                <option value="charges_filed" <?php selected($legal_status, 'charges_filed'); ?>>Charges Filed</option>
                <option value="court_case" <?php selected($legal_status, 'court_case'); ?>>Court Case</option>
                <option value="convicted" <?php selected($legal_status, 'convicted'); ?>>Convicted</option>
                <option value="appeal_pending" <?php selected($legal_status, 'appeal_pending'); ?>>Appeal Pending</option>
                <option value="cleared" <?php selected($legal_status, 'cleared'); ?>>Cleared</option>
                <option value="dismissed" <?php selected($legal_status, 'dismissed'); ?>>Dismissed</option>
            </select>

            <label>Severity</label>
            <select name="tsemou_severity">
                <option value="low" <?php selected($severity, 'low'); ?>>Low</option>
                <option value="medium" <?php selected($severity, 'medium'); ?>>Medium</option>
                <option value="high" <?php selected($severity, 'high'); ?>>High</option>
                <option value="critical" <?php selected($severity, 'critical'); ?>>Critical</option>
            </select>

            <label>Include in Trust Engine?</label>
            <select name="tsemou_trust_include">
                <option value="yes" <?php selected($trust_include, 'yes'); ?>>Yes</option>
                <option value="no" <?php selected($trust_include, 'no'); ?>>No</option>
            </select>

            <label>Manual Adjustment Note</label>
            <textarea name="tsemou_manual_adjustment_note" rows="3" placeholder="Optional admin note explaining unusual weight or context."><?php echo esc_textarea($manual_adjustment_note); ?></textarea>

            <hr>
            <h3>Evidence Relationships v2</h3>
            <p class="tsemou-help-text">Use comma-separated Evidence IDs. Example: 123, 456, 789</p>

            <label>Supports Evidence IDs</label>
            <input type="text" name="tsemou_rel_supports" value="<?php echo esc_attr($rel_supports); ?>" placeholder="Evidence IDs that this supports">

            <label>Contradicts Evidence IDs</label>
            <input type="text" name="tsemou_rel_contradicts" value="<?php echo esc_attr($rel_contradicts); ?>" placeholder="Evidence IDs that this contradicts">

            <label>Duplicates Evidence IDs</label>
            <input type="text" name="tsemou_rel_duplicates" value="<?php echo esc_attr($rel_duplicates); ?>" placeholder="Evidence IDs that this duplicates">

            <label>Updates Evidence IDs</label>
            <input type="text" name="tsemou_rel_updates" value="<?php echo esc_attr($rel_updates); ?>" placeholder="Evidence IDs that this updates">

            <label>Supersedes Evidence IDs</label>
            <input type="text" name="tsemou_rel_supersedes" value="<?php echo esc_attr($rel_supersedes); ?>" placeholder="Evidence IDs that this supersedes">

            <label>Related Evidence IDs</label>
            <input type="text" name="tsemou_rel_related" value="<?php echo esc_attr($rel_related); ?>" placeholder="Other related Evidence IDs">


        </div>
        <?php
    }
    public function save_proof_meta($post_id, $post) {
        if (!isset($_POST['tsemou_proof_nonce']) || !wp_verify_nonce($_POST['tsemou_proof_nonce'], 'tsemou_save_proof_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        $fields = [
            '_tsemou_proof_source' => 'tsemou_proof_source',
            '_tsemou_proof_url' => 'tsemou_proof_url',
            '_tsemou_proof_type' => 'tsemou_proof_type',
            '_tsemou_proof_reliability' => 'tsemou_proof_reliability',
            '_tsemou_related_file' => 'tsemou_related_file'
        ];
        foreach ($fields as $meta_key => $post_key) {
            if (isset($_POST[$post_key])) update_post_meta($post_id, $meta_key, sanitize_textarea_field($_POST[$post_key]));
        }

        $this->build_and_save_evidence_profile($post_id);
        $this->build_and_save_evidence_intelligence($post_id);

        $this->sync_evidence_to_companies($post_id);
    }
    public static function get_proofs_for_file($file_id) {
        return get_posts([
            'post_type' => 'tsemou_proof',
            'numberposts' => 50,
            'post_status' => ['publish','draft','pending'],
            'meta_key' => '_tsemou_related_file',
            'meta_value' => $file_id
        ]);
    }
    /**
     * Evidence Engine v0.9.8
     *
     * Runs when a Proof/Evidence object is saved.
     * It does not change frontend templates.
     * It only updates company-side intelligence cache and timeline events.
     */
    public function sync_evidence_to_companies($proof_id) {
        $company_ids = self::get_company_ids_for_evidence($proof_id);

        if (empty($company_ids)) {
            update_post_meta($proof_id, '_tsemou_evidence_engine_status', 'no_company_found');
            return;
        }

        update_post_meta($proof_id, '_tsemou_evidence_company_ids', $company_ids);
        update_post_meta($proof_id, '_tsemou_evidence_engine_status', 'synced');

        foreach ($company_ids as $company_id) {
            $this->refresh_company_evidence_cache($company_id);

            if (class_exists('\TSEMOU\Modules\\CompanyEngine\\Company_Engine') && method_exists('\TSEMOU\Modules\\CompanyEngine\\Company_Engine', 'add_company_timeline_event')) {
                $event_key = '_tsemou_evidence_timeline_logged_' . intval($company_id);

                if (!get_post_meta($proof_id, $event_key, true)) {
                    \TSEMOU\Modules\CompanyEngine\Company_Engine::add_company_timeline_event(
                        $company_id,
                        'New evidence added',
                        get_the_title($proof_id)
                    );
                    update_post_meta($proof_id, $event_key, current_time('mysql'));
                }
            }
        }
    }

    public static function get_company_ids_for_evidence($proof_id) {
        $company_ids = [];

        $direct_keys = [
            '_tsemou_evidence_company',
            'tsemou_evidence_company',
            '_tsemou_related_company',
            'tsemou_related_company',
            'related_company',
            'company',
            'companies'
        ];

        foreach ($direct_keys as $key) {
            $value = get_post_meta($proof_id, $key, true);
            $company_ids = array_merge($company_ids, self::normalize_company_ids($value));
        }

        $related_file = get_post_meta($proof_id, '_tsemou_related_file', true);
        if ($related_file && class_exists('\TSEMOU\Modules\\CompanyEngine\\Company_Engine') && method_exists('\TSEMOU\Modules\\CompanyEngine\\Company_Engine', 'get_connected_company_ids')) {
            $company_ids = array_merge(
                $company_ids,
                \TSEMOU\Modules\CompanyEngine\Company_Engine::get_connected_company_ids(intval($related_file))
            );
        }

        $company_ids = array_values(array_unique(array_filter(array_map('intval', $company_ids))));

        return $company_ids;
    }

    public static function normalize_company_ids($value) {
        $ids = [];

        if (empty($value)) {
            return [];
        }

        if (is_numeric($value)) {
            return [intval($value)];
        }

        if (is_object($value) && isset($value->ID)) {
            return [intval($value->ID)];
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $ids = array_merge($ids, self::normalize_company_ids($item));
            }
            return $ids;
        }

        if (is_string($value)) {
            $maybe = maybe_unserialize($value);
            if ($maybe !== $value) {
                return self::normalize_company_ids($maybe);
            }

            if (strpos($value, ',') !== false) {
                foreach (explode(',', $value) as $piece) {
                    if (is_numeric(trim($piece))) {
                        $ids[] = intval(trim($piece));
                    }
                }
            }
        }

        return $ids;
    }

    public function refresh_company_evidence_cache($company_id) {
        $all = get_posts([
            'post_type' => 'tsemou_proof',
            'post_status' => ['publish', 'draft', 'pending'],
            'numberposts' => -1,
            'fields' => 'ids'
        ]);

        $total = 0;
        $positive = 0;
        $negative = 0;
        $neutral = 0;
        $high_credibility = 0;
        $cred_sum = 0;
        $cred_count = 0;

        foreach ($all as $proof_id) {
            $ids = self::get_company_ids_for_evidence($proof_id);
            if (!in_array(intval($company_id), $ids, true)) {
                continue;
            }

            $total++;

            $sentiment = strtolower(trim((string) self::get_first_meta($proof_id, [
                '_tsemou_sentiment',
                'tsemou_sentiment',
                'sentiment'
            ], 'neutral')));

            if (strpos($sentiment, 'positive') !== false || strpos($sentiment, 'good') !== false) {
                $positive++;
            } elseif (strpos($sentiment, 'negative') !== false || strpos($sentiment, 'bad') !== false) {
                $negative++;
            } else {
                $neutral++;
            }

            $credibility = self::get_first_meta($proof_id, [
                '_tsemou_credibility_score',
                'tsemou_credibility_score',
                'credibility_score',
                'credibility',
                '_tsemou_proof_reliability'
            ], '');

            if ($credibility !== '') {
                if (is_numeric($credibility)) {
                    $cred = floatval($credibility);
                } else {
                    $map = [
                        'high' => 9,
                        'medium' => 6,
                        'low' => 3
                    ];
                    $cred = $map[strtolower((string) $credibility)] ?? 0;
                }

                if ($cred > 0) {
                    $cred_sum += $cred;
                    $cred_count++;
                    if ($cred >= 8) {
                        $high_credibility++;
                    }
                }
            }
        }

        $avg = $cred_count > 0 ? round($cred_sum / $cred_count, 1) : 0;

        update_post_meta($company_id, '_tsemou_evidence_total', $total);
        update_post_meta($company_id, '_tsemou_evidence_positive', $positive);
        update_post_meta($company_id, '_tsemou_evidence_negative', $negative);
        update_post_meta($company_id, '_tsemou_evidence_neutral', $neutral);
        update_post_meta($company_id, '_tsemou_evidence_high_credibility', $high_credibility);
        update_post_meta($company_id, '_tsemou_evidence_avg_credibility', $avg);
        update_post_meta($company_id, '_tsemou_evidence_last_sync', current_time('mysql'));
    }

    public static function get_first_meta($post_id, $keys, $default = '') {
        foreach ($keys as $key) {
            $value = get_post_meta($post_id, $key, true);
            if ($value !== '' && $value !== null) {
                return $value;
            }
        }
        return $default;
    }

    public function build_and_save_evidence_profile($proof_id) {
        $profile = self::build_evidence_profile($proof_id);
        update_post_meta($proof_id, '_tsemou_evidence_profile', wp_json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        update_post_meta($proof_id, '_tsemou_evidence_profile_version', '1.1.5');
    }

    public static function build_evidence_profile($proof_id) {
        $profile = [
            'version' => '1.1.5',
            'identity' => [
                'evidence_id' => intval($proof_id),
                'title' => get_the_title($proof_id),
                'status' => get_post_status($proof_id),
                'created_at' => get_post_field('post_date', $proof_id),
                'updated_at' => get_post_field('post_modified', $proof_id),
            ],
            'classification' => [
                'evidence_type' => self::get_meta_first($proof_id, ['_tsemou_evidence_type', '_tsemou_proof_type'], 'other'),
                'category' => self::get_meta_first($proof_id, ['_tsemou_category', 'category'], ''),
                'source_type' => self::get_meta_first($proof_id, ['_tsemou_source_type'], 'other'),
                'related_file' => intval(self::get_meta_first($proof_id, ['_tsemou_related_file'], 0)),
            ],
            'impact' => [
                'sentiment' => self::get_meta_first($proof_id, ['_tsemou_sentiment', 'sentiment'], 'neutral'),
                'severity' => self::get_meta_first($proof_id, ['_tsemou_severity'], 'medium'),
                'credibility' => self::get_meta_first($proof_id, ['_tsemou_credibility_score', 'credibility_score', 'credibility'], ''),
                'reliability' => self::get_meta_first($proof_id, ['_tsemou_proof_reliability'], 'medium'),
                'include_in_trust' => self::get_meta_first($proof_id, ['_tsemou_trust_include'], 'yes') === 'yes',
            ],
            'verification' => [
                'verification_level' => self::get_meta_first($proof_id, ['_tsemou_verification_level'], 'unverified'),
                'legal_status' => self::get_meta_first($proof_id, ['_tsemou_legal_status'], 'not_legal'),
                'manual_adjustment_note' => self::get_meta_first($proof_id, ['_tsemou_manual_adjustment_note'], ''),
            ],
            'source' => [
                'name' => self::get_meta_first($proof_id, ['_tsemou_proof_source', '_tsemou_source_name', 'source_name'], ''),
                'url' => self::get_meta_first($proof_id, ['_tsemou_proof_url', '_tsemou_source_url', 'source_url'], ''),
            ],
            'engine' => [
                'profile_created_at' => current_time('mysql'),
                'trust_impact' => null,
                'explainability' => [],
            ],
            'relationships' => [
                'supports' => self::normalize_relationship_ids(self::get_meta_first($proof_id, ['_tsemou_rel_supports'], '')),
                'contradicts' => self::normalize_relationship_ids(self::get_meta_first($proof_id, ['_tsemou_rel_contradicts'], '')),
                'duplicates' => self::normalize_relationship_ids(self::get_meta_first($proof_id, ['_tsemou_rel_duplicates'], '')),
                'updates' => self::normalize_relationship_ids(self::get_meta_first($proof_id, ['_tsemou_rel_updates'], '')),
                'supersedes' => self::normalize_relationship_ids(self::get_meta_first($proof_id, ['_tsemou_rel_supersedes'], '')),
                'related' => self::normalize_relationship_ids(self::get_meta_first($proof_id, ['_tsemou_rel_related'], '')),
            ],
        ];

        return apply_filters('tsemou_evidence_profile', $profile, $proof_id);
    }

    public static function get_evidence_profile($proof_id) {
        $json = get_post_meta($proof_id, '_tsemou_evidence_profile', true);

        if ($json) {
            $profile = json_decode($json, true);
            if (is_array($profile)) return $profile;
        }

        return self::build_evidence_profile($proof_id);
    }

    public static function get_meta_first($post_id, $keys, $default = '') {
        foreach ($keys as $key) {
            $value = get_post_meta($post_id, $key, true);
            if ($value !== '' && $value !== null) return $value;
        }
        return $default;
    }

    public function build_and_save_evidence_intelligence($proof_id) {
        $output = self::build_evidence_intelligence($proof_id);
        update_post_meta($proof_id, '_tsemou_evidence_intelligence', wp_json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        update_post_meta($proof_id, '_tsemou_evidence_intelligence_version', '1.2.0');
    }

    public static function build_evidence_intelligence($proof_id) {
        $profile = self::get_evidence_profile($proof_id);

        $type = self::profile_value($profile, 'classification.evidence_type', 'other');
        $source_type = self::profile_value($profile, 'classification.source_type', 'other');
        $verification = self::profile_value($profile, 'verification.verification_level', 'unverified');
        $legal_status = self::profile_value($profile, 'verification.legal_status', 'not_legal');
        $severity = self::profile_value($profile, 'impact.severity', 'medium');
        $sentiment = strtolower((string) self::profile_value($profile, 'impact.sentiment', 'neutral'));
        $credibility = self::profile_value($profile, 'impact.credibility', '');
        $reliability = self::profile_value($profile, 'impact.reliability', 'medium');
        $include = self::profile_value($profile, 'impact.include_in_trust', true);

        $weights = [
            'severity' => self::severity_weight($severity),
            'evidence_type' => self::evidence_type_weight($type),
            'source_type' => self::source_type_weight($source_type),
            'verification' => self::verification_weight($verification),
            'legal_status' => self::legal_status_weight($legal_status),
            'credibility' => self::credibility_weight($credibility, $reliability),
            'time_decay' => self::time_decay_weight(get_post_field('post_date', $proof_id)),
        ];

        $raw = $weights['severity']
            * $weights['evidence_type']
            * $weights['source_type']
            * $weights['verification']
            * abs($weights['legal_status'])
            * $weights['credibility']
            * $weights['time_decay'];

        $max_single = class_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine') ? \TSEMOU\Modules\PolicyEngine\Policy_Engine::get('trust.max_single_impact', 1.0) : 1.0;
        $impact = min(floatval($max_single), $raw);

        if ($weights['legal_status'] < 0) {
            $impact = $impact * -1;
        }

        if (strpos($sentiment, 'negative') !== false || strpos($sentiment, 'bad') !== false) {
            $impact = -abs($impact);
        } elseif (strpos($sentiment, 'positive') !== false || strpos($sentiment, 'good') !== false) {
            $positive_factor = class_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine') ? \TSEMOU\Modules\PolicyEngine\Policy_Engine::get('trust.positive_impact_factor', 0.6) : 0.6;
            $impact = abs($impact) * floatval($positive_factor);
        } else {
            $impact = 0;
        }

        if (!$include || $include === 'no' || $include === 'false') {
            $impact = 0;
        }

        $confidence = self::calculate_confidence($weights, $include);
        $reasons = self::build_explainability_reasons($type, $source_type, $verification, $legal_status, $severity, $weights, $impact);

        return [
            'version' => '1.2.0',
            'evidence_id' => intval($proof_id),
            'calculated_at' => current_time('mysql'),
            'included_in_trust' => (bool) $include && $include !== 'no' && $include !== 'false',
            'impact' => round($impact, 3),
            'confidence' => round($confidence, 3),
            'weights' => $weights,
            'categories' => self::normalize_categories(self::profile_value($profile, 'classification.category', [])),
            'relationships' => self::profile_value($profile, 'relationships', []),
            'relationship_summary' => self::relationship_summary(self::profile_value($profile, 'relationships', [])),
            'explainability' => [
                'summary' => self::impact_summary($impact),
                'reasons' => $reasons,
            ],
        ];
    }

    public static function get_evidence_intelligence($proof_id) {
        $json = get_post_meta($proof_id, '_tsemou_evidence_intelligence', true);
        if ($json) {
            $output = json_decode($json, true);
            if (is_array($output)) return $output;
        }
        return self::build_evidence_intelligence($proof_id);
    }

    public static function profile_value($profile, $path, $default = '') {
        $current = $profile;
        foreach (explode('.', $path) as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) return $default;
            $current = $current[$part];
        }
        return $current;
    }
    public static function severity_weight($severity) {
        if (class_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine')) {
            return \TSEMOU\Modules\PolicyEngine\Policy_Engine::weight('severity', $severity, 'medium', 0.5);
        }
        $map = ['low'=>0.2, 'medium'=>0.5, 'high'=>1.0, 'critical'=>2.0];
        return $map[sanitize_key($severity)] ?? 0.5;
    }
    public static function evidence_type_weight($type) {
        if (class_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine')) {
            return \TSEMOU\Modules\PolicyEngine\Policy_Engine::weight('evidence_type', $type, 'other', 1.0);
        }
        $map = ['court_decision'=>4.0,'court_case'=>2.5,'government_report'=>2.0,'scientific_study'=>2.0,'audit'=>1.8,'ngo_report'=>1.5,'journalistic_investigation'=>1.4,'former_employee'=>1.3,'whistleblower'=>1.3,'official_statement'=>0.7,'community_report'=>0.6,'other'=>1.0];
        return $map[sanitize_key($type)] ?? 1.0;
    }
    public static function source_type_weight($source_type) {
        if (class_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine')) {
            return \TSEMOU\Modules\PolicyEngine\Policy_Engine::weight('source_type', $source_type, 'other', 1.0);
        }
        $map = ['court'=>2.5,'government'=>2.0,'academic'=>1.8,'ngo'=>1.5,'journalist'=>1.4,'former_employee'=>1.3,'former_partner'=>1.3,'company'=>0.7,'community'=>0.6,'anonymous'=>0.4,'other'=>1.0];
        return $map[sanitize_key($source_type)] ?? 1.0;
    }
    public static function verification_weight($verification) {
        if (class_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine')) {
            return \TSEMOU\Modules\PolicyEngine\Policy_Engine::weight('verification', $verification, 'unverified', 0.5);
        }
        $map = ['unverified'=>0.5,'partially_verified'=>0.8,'verified_documents'=>1.3,'multiple_sources'=>1.6,'government_verified'=>2.0,'court_verified'=>2.5];
        return $map[sanitize_key($verification)] ?? 0.5;
    }
    public static function legal_status_weight($legal_status) {
        if (class_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine')) {
            return \TSEMOU\Modules\PolicyEngine\Policy_Engine::weight('legal', $legal_status, 'not_legal', 1.0);
        }
        $map = ['not_legal'=>1.0,'allegation'=>0.7,'under_investigation'=>1.1,'charges_filed'=>1.5,'court_case'=>1.8,'convicted'=>2.5,'appeal_pending'=>2.0,'cleared'=>-0.8,'dismissed'=>-0.6];
        return $map[sanitize_key($legal_status)] ?? 1.0;
    }

    public static function credibility_weight($credibility, $reliability = 'medium') {
        if (is_numeric($credibility)) {
            return max(0, min(1, floatval($credibility) / 10));
        }
        $map = ['high'=>0.9, 'medium'=>0.6, 'low'=>0.3];
        return $map[sanitize_key($reliability)] ?? 0.6;
    }
    public static function time_decay_weight($date) {
        $ts = strtotime($date);
        if (!$ts) return 1.0;
        $years = (time() - $ts) / YEAR_IN_SECONDS;
        if (class_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine')) {
            if ($years < 1) return \TSEMOU\Modules\PolicyEngine\Policy_Engine::get('time_decay.lt_1_year', 1.0);
            if ($years < 3) return \TSEMOU\Modules\PolicyEngine\Policy_Engine::get('time_decay.y1_3', 0.9);
            if ($years < 5) return \TSEMOU\Modules\PolicyEngine\Policy_Engine::get('time_decay.y3_5', 0.75);
            if ($years < 10) return \TSEMOU\Modules\PolicyEngine\Policy_Engine::get('time_decay.y5_10', 0.5);
            return \TSEMOU\Modules\PolicyEngine\Policy_Engine::get('time_decay.gt_10', 0.3);
        }
        if ($years < 1) return 1.0;
        if ($years < 3) return 0.9;
        if ($years < 5) return 0.75;
        if ($years < 10) return 0.5;
        return 0.3;
    }

    public static function calculate_confidence($weights, $include) {
        if (!$include || $include === 'no' || $include === 'false') return 0;
        $confidence = (
            min(1, $weights['verification'] / 2.5) * 0.35 +
            min(1, $weights['source_type'] / 2.5) * 0.25 +
            min(1, $weights['credibility']) * 0.25 +
            min(1, $weights['time_decay']) * 0.15
        );
        return max(0, min(1, $confidence));
    }

    public static function build_explainability_reasons($type, $source_type, $verification, $legal_status, $severity, $weights, $impact) {
        $reasons = [];

        $reasons[] = [
            'label' => 'Evidence type',
            'value' => $type,
            'weight' => $weights['evidence_type'],
        ];
        $reasons[] = [
            'label' => 'Source type',
            'value' => $source_type,
            'weight' => $weights['source_type'],
        ];
        $reasons[] = [
            'label' => 'Verification',
            'value' => $verification,
            'weight' => $weights['verification'],
        ];
        $reasons[] = [
            'label' => 'Legal status',
            'value' => $legal_status,
            'weight' => $weights['legal_status'],
        ];
        $reasons[] = [
            'label' => 'Severity',
            'value' => $severity,
            'weight' => $weights['severity'],
        ];
        $reasons[] = [
            'label' => 'Time decay',
            'value' => 'recency adjustment',
            'weight' => $weights['time_decay'],
        ];
        $reasons[] = [
            'label' => 'Final evidence impact',
            'value' => self::impact_summary($impact),
            'weight' => $impact,
        ];

        return $reasons;
    }

    public static function impact_summary($impact) {
        if ($impact < 0) return 'Reduces company trust';
        if ($impact > 0) return 'Improves company trust';
        return 'No direct trust impact';
    }

    public static function normalize_categories($category) {
        if (is_array($category)) return array_values(array_filter($category));
        if (!$category) return [];
        return array_values(array_filter(array_map('trim', explode(',', (string) $category))));
    }

}
