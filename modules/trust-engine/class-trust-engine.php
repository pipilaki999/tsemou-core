<?php
namespace TSEMOU\Modules\TrustEngine;
if (!defined('ABSPATH')) exit;

class Trust_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 30);
        add_action('save_post_tsemou_proof', [$this, 'on_evidence_saved'], 30, 2);
        add_shortcode('tsemou_company_trust', [$this, 'shortcode_company_trust']);
        add_shortcode('tsemou_company_vote', [$this, 'shortcode_company_vote']);
        add_shortcode('tsemou_company_quick_vote', [$this, 'shortcode_company_quick_vote']);
        add_action('init', [$this, 'handle_vote_submission']);
    }

    public static function defaults() {
        return [
            'base_score' => 7.0,
            'evidence_weight' => 85,
            'community_weight' => 15,
            'max_single_impact' => 1.0,
            'severity_low' => 0.2,
            'severity_medium' => 0.5,
            'severity_high' => 1.0,
            'severity_critical' => 2.0,
            'type_court_decision' => 4.0,
            'type_court_case' => 2.5,
            'type_government_report' => 2.0,
            'type_scientific_study' => 2.0,
            'type_audit' => 1.8,
            'type_ngo_report' => 1.5,
            'type_investigation' => 1.4,
            'type_media_article' => 1.2,
            'type_company_statement' => 0.6,
            'type_other' => 1.0,
            'source_court' => 2.5,
            'source_government' => 2.0,
            'source_academic' => 1.8,
            'source_ngo' => 1.5,
            'source_journalist' => 1.4,
            'source_former_employee' => 1.3,
            'source_former_partner' => 1.3,
            'source_company' => 0.7,
            'source_community' => 0.6,
            'source_anonymous' => 0.4,
            'source_other' => 1.0,
            'verification_unverified' => 0.5,
            'verification_partially_verified' => 0.8,
            'verification_verified_documents' => 1.3,
            'verification_multiple_sources' => 1.6,
            'verification_government_verified' => 2.0,
            'verification_court_verified' => 2.5,
            'legal_not_legal' => 1.0,
            'legal_allegation' => 0.7,
            'legal_under_investigation' => 1.1,
            'legal_charges_filed' => 1.5,
            'legal_court_case' => 1.8,
            'legal_convicted' => 2.5,
            'legal_appeal_pending' => 2.0,
            'legal_cleared' => -0.8,
            'legal_dismissed' => -0.6,
        ];
    }

    public static function settings() {
        $stored = get_option('tsemou_trust_engine_settings', []);
        return array_merge(self::defaults(), is_array($stored) ? $stored : []);
    }

    public function admin_menu() {
        add_submenu_page('tsemou-os','Trust Engine','Trust Engine','manage_options','tsemou-trust-engine',[$this,'render_admin_page']);
        add_submenu_page('tsemou-os','Community Moderation','Community Moderation','manage_options','tsemou-community-moderation',[$this,'render_community_moderation_page']);
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        if (!empty($_POST['tsemou_trust_engine_nonce']) && wp_verify_nonce($_POST['tsemou_trust_engine_nonce'], 'tsemou_save_trust_engine')) {
            $settings = self::defaults();
            foreach ($settings as $key => $default) {
                if (isset($_POST[$key])) $settings[$key] = floatval($_POST[$key]);
            }
            update_option('tsemou_trust_engine_settings', $settings);
            echo '<div class="notice notice-success"><p>Trust Engine settings saved.</p></div>';
        }
        $s = self::settings();
        ?>
        <div class="wrap">
            <h1>TSEMOU Trust Engine</h1>
            <p>Evidence is the source of truth. Community can influence the final score with smaller weight.</p>
            <form method="post">
                <?php wp_nonce_field('tsemou_save_trust_engine', 'tsemou_trust_engine_nonce'); ?>
                <h2>Core weights</h2>
                <table class="form-table">
                    <tr><th>Base Score</th><td><input type="number" step="0.1" name="base_score" value="<?php echo esc_attr($s['base_score']); ?>"></td></tr>
                    <tr><th>Evidence Weight %</th><td><input type="number" step="1" name="evidence_weight" value="<?php echo esc_attr($s['evidence_weight']); ?>"></td></tr>
                    <tr><th>Community Weight %</th><td><input type="number" step="1" name="community_weight" value="<?php echo esc_attr($s['community_weight']); ?>"></td></tr>
                    <tr><th>Max Single Evidence Impact</th><td><input type="number" step="0.1" name="max_single_impact" value="<?php echo esc_attr($s['max_single_impact']); ?>"></td></tr>
                </table>
                <h2>Severity impact</h2>
                <table class="form-table">
                    <tr><th>Low</th><td><input type="number" step="0.1" name="severity_low" value="<?php echo esc_attr($s['severity_low']); ?>"></td></tr>
                    <tr><th>Medium</th><td><input type="number" step="0.1" name="severity_medium" value="<?php echo esc_attr($s['severity_medium']); ?>"></td></tr>
                    <tr><th>High</th><td><input type="number" step="0.1" name="severity_high" value="<?php echo esc_attr($s['severity_high']); ?>"></td></tr>
                    <tr><th>Critical</th><td><input type="number" step="0.1" name="severity_critical" value="<?php echo esc_attr($s['severity_critical']); ?>"></td></tr>
                </table>
                <h2>Evidence type multipliers</h2>
                <table class="form-table">
                    <?php foreach ($s as $key => $value): if (strpos($key, 'type_') === 0): ?>
                        <tr><th><?php echo esc_html(ucwords(str_replace('_',' ',substr($key,5)))); ?></th><td><input type="number" step="0.1" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>"></td></tr>
                    <?php endif; endforeach; ?>
                </table>
                <h2>Source multipliers</h2>
                <table class="form-table">
                    <?php foreach ($s as $key => $value): ?>
                        <?php if (strpos($key, 'source_') === 0): ?>
                            <tr><th><?php echo esc_html(ucwords(str_replace('_',' ',substr($key,7)))); ?></th><td><input type="number" step="0.1" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>"></td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>

                <h2>Verification multipliers</h2>
                <table class="form-table">
                    <?php foreach ($s as $key => $value): ?>
                        <?php if (strpos($key, 'verification_') === 0): ?>
                            <tr><th><?php echo esc_html(ucwords(str_replace('_',' ',substr($key,13)))); ?></th><td><input type="number" step="0.1" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>"></td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>

                <h2>Legal status multipliers</h2>
                <table class="form-table">
                    <?php foreach ($s as $key => $value): ?>
                        <?php if (strpos($key, 'legal_') === 0): ?>
                            <tr><th><?php echo esc_html(ucwords(str_replace('_',' ',substr($key,6)))); ?></th><td><input type="number" step="0.1" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>"></td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>

                <?php submit_button('Save Trust Engine Settings'); ?>
            </form>
        </div>
        <?php
    }

    public function on_evidence_saved($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ($post->post_type !== 'tsemou_proof') return;
        foreach (self::get_company_ids_for_evidence($post_id) as $company_id) {
            self::recalculate_company_trust($company_id);
        }
    }

    public static function get_company_ids_for_evidence($evidence_id) {
        $ids = get_post_meta($evidence_id, '_tsemou_evidence_company_ids', true);
        if (is_array($ids) && !empty($ids)) return array_values(array_unique(array_map('intval', $ids)));
        $related_file = get_post_meta($evidence_id, '_tsemou_related_file', true);
        if ($related_file && class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine')) {
            return \TSEMOU\Modules\CompanyEngine\Company_Engine::get_connected_company_ids(intval($related_file));
        }
        return [];
    }

    public static function recalculate_company_trust($company_id) {
        $s = self::settings();
        $evidence_score = self::calculate_evidence_score($company_id);
        $community_score = self::calculate_community_score($company_id);
        $final = (($evidence_score * floatval($s['evidence_weight'])) + ($community_score * floatval($s['community_weight']))) / 100;
        $final = max(0, min(10, round($final, 1)));
        update_post_meta($company_id, '_tsemou_trust_engine_score', $final);
        update_post_meta($company_id, '_tsemou_trust_engine_evidence_score', round($evidence_score, 1));
        update_post_meta($company_id, '_tsemou_trust_engine_community_score', round($community_score, 1));
        update_post_meta($company_id, '_tsemou_trust_engine_last_calc', current_time('mysql'));
        return $final;
    }

    public static function calculate_evidence_score($company_id) {
        $s = self::settings();
        $score = floatval($s['base_score']);
        $evidence = get_posts(['post_type'=>'tsemou_proof','post_status'=>['publish','draft','pending'],'numberposts'=>-1]);
        foreach ($evidence as $item) {
            $company_ids = self::get_company_ids_for_evidence($item->ID);
            if (!in_array(intval($company_id), array_map('intval', $company_ids), true)) continue;
            $score += self::calculate_evidence_impact($item->ID);
        }
        return max(0, min(10, $score));
    }

    public static function calculate_evidence_impact($evidence_id) {
        $engine_impact = self::get_evidence_engine_impact($evidence_id);
        if ($engine_impact !== null) return $engine_impact;

        $s = self::settings();

        $include = self::profile_or_meta($evidence_id, 'impact.include_in_trust', ['_tsemou_trust_include'], 'yes');
        if ($include === 'no' || $include === false || $include === 'false') return 0;

        $sentiment = strtolower((string) self::profile_or_meta($evidence_id, 'impact.sentiment', ['_tsemou_sentiment','sentiment','tsemou_sentiment'], 'neutral'));
        $severity = strtolower((string) self::profile_or_meta($evidence_id, 'impact.severity', ['_tsemou_severity','severity','tsemou_severity'], 'medium'));
        $credibility = self::profile_or_meta($evidence_id, 'impact.credibility', ['_tsemou_credibility_score','credibility_score','credibility'], '');
        $type = strtolower((string) self::profile_or_meta($evidence_id, 'classification.evidence_type', ['_tsemou_evidence_type','_tsemou_proof_type','evidence_type'], 'other'));
        $source_type = strtolower((string) self::profile_or_meta($evidence_id, 'classification.source_type', ['_tsemou_source_type','source_type'], 'other'));
        $verification = strtolower((string) self::profile_or_meta($evidence_id, 'verification.verification_level', ['_tsemou_verification_level','verification_level'], 'unverified'));
        $legal_status = strtolower((string) self::profile_or_meta($evidence_id, 'verification.legal_status', ['_tsemou_legal_status','legal_status'], 'not_legal'));

        $base = self::setting_for($s, 'severity_' . $severity, 'severity_medium');
        $type_multiplier = self::setting_for($s, 'type_' . $type, 'type_other');
        $source_multiplier = self::setting_for($s, 'source_' . $source_type, 'source_other');
        $verification_multiplier = self::setting_for($s, 'verification_' . $verification, 'verification_unverified');
        $legal_multiplier = self::setting_for($s, 'legal_' . $legal_status, 'legal_not_legal');

        if (is_numeric($credibility)) {
            $credibility_weight = floatval($credibility) / 10;
        } else {
            $map = ['high'=>0.9,'medium'=>0.6,'low'=>0.3];
            $credibility_weight = $map[strtolower((string) self::first_meta($evidence_id, ['_tsemou_proof_reliability'], 'medium'))] ?? 0.6;
        }

        $raw = $base * $type_multiplier * $source_multiplier * $verification_multiplier * abs($legal_multiplier) * $credibility_weight;
        $impact = min(floatval($s['max_single_impact']), $raw);

        if ($legal_multiplier < 0) {
            $impact = $impact * -1;
        }

        if (strpos($sentiment, 'negative') !== false || strpos($sentiment, 'bad') !== false) {
            return -abs($impact);
        }

        if (strpos($sentiment, 'positive') !== false || strpos($sentiment, 'good') !== false) {
            return abs($impact) * 0.6;
        }

        return 0;
    }

    public static function setting_for($settings, $key, $fallback_key) {
        $key = sanitize_key(str_replace('-', '_', $key));
        $fallback_key = sanitize_key(str_replace('-', '_', $fallback_key));
        return isset($settings[$key]) ? floatval($settings[$key]) : floatval($settings[$fallback_key] ?? 1);
    }


    public static function calculate_community_score($company_id) {
        $votes = get_post_meta($company_id, '_tsemou_community_votes', true);
        if (!is_array($votes) || empty($votes)) return 7.0;
        $sum = 0; $count = 0;
        foreach ($votes as $vote) {
            if (!empty($vote['blocked'])) continue;
            if (isset($vote['score']) && is_numeric($vote['score'])) { $sum += floatval($vote['score']); $count++; }
        }
        return $count ? max(0, min(10, $sum / $count)) : 7.0;
    }

    public function handle_vote_submission() {
        if (empty($_POST['tsemou_company_vote_submit'])) return;
        if (!is_user_logged_in()) return;
        if (empty($_POST['tsemou_company_vote_nonce']) || !wp_verify_nonce($_POST['tsemou_company_vote_nonce'], 'tsemou_company_vote')) return;
        $company_id = isset($_POST['company_id']) ? intval($_POST['company_id']) : 0;
        if (!$company_id || get_post_type($company_id) !== 'company') return;
        $score = max(0, min(10, floatval($_POST['score'] ?? 0)));
        $votes = get_post_meta($company_id, '_tsemou_community_votes', true);
        if (!is_array($votes)) $votes = [];
        $votes[get_current_user_id()] = [
            'score' => $score,
            'time' => current_time('mysql'),
            'ip_hash' => wp_hash($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent_hash' => wp_hash($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'blocked' => false,
            'suspicion_score' => self::calculate_vote_suspicion($company_id, get_current_user_id())
        ];
        update_post_meta($company_id, '_tsemou_community_votes', $votes);
        self::recalculate_company_trust($company_id);
        wp_safe_redirect(add_query_arg('tsemou_vote', 'saved', get_permalink($company_id)));
        exit;
    }

    public function shortcode_company_vote($atts = []) {
        $atts = shortcode_atts(['company_id'=>0], $atts);
        $company_id = intval($atts['company_id']);
        if (!$company_id && is_singular('company')) $company_id = get_the_ID();
        if (!$company_id) return '';
        if (!is_user_logged_in()) return '<div class="tsemou-community-vote-box">Log in to rate this company.</div>';
        ob_start(); ?>
        <form method="post" class="tsemou-community-vote-box">
            <h3>Community Trust Vote</h3>
            <p>Your vote affects only the Community part of the Trust Score.</p>
            <?php wp_nonce_field('tsemou_company_vote', 'tsemou_company_vote_nonce'); ?>
            <input type="hidden" name="company_id" value="<?php echo esc_attr($company_id); ?>">
            <label>Score 0–10</label>
            <input type="number" name="score" min="0" max="10" step="0.1" required>
            <button type="submit" name="tsemou_company_vote_submit" value="1">Submit Vote</button>
        </form>
        <?php return ob_get_clean();
    }

    public function shortcode_company_trust($atts = []) {
        $atts = shortcode_atts(['company_id'=>0], $atts);
        $company_id = intval($atts['company_id']);
        if (!$company_id && is_singular('company')) $company_id = get_the_ID();
        if (!$company_id) return '';
        $final = get_post_meta($company_id, '_tsemou_trust_engine_score', true);
        if ($final === '') $final = self::recalculate_company_trust($company_id);
        $evidence = get_post_meta($company_id, '_tsemou_trust_engine_evidence_score', true);
        $community = get_post_meta($company_id, '_tsemou_trust_engine_community_score', true);
        $last = get_post_meta($company_id, '_tsemou_trust_engine_last_calc', true);
        $s = self::settings();
        ob_start(); ?>
        <section class="tsemou-trust-engine-widget">
            <div class="tsemou-trust-main">
                <span>TSEMOU TRUST ENGINE</span>
                <strong><?php echo esc_html($final); ?></strong>
                <em>/10 Final Trust Score</em>
            </div>
            <div class="tsemou-trust-breakdown">
                <div><b><?php echo esc_html($evidence); ?></b><span>Evidence Score</span><small><?php echo esc_html($s['evidence_weight']); ?>% influence</small></div>
                <div><b><?php echo esc_html($community); ?></b><span>Community Score</span><small><?php echo esc_html($s['community_weight']); ?>% influence</small></div>
                <div><b><?php echo esc_html($last ?: '—'); ?></b><span>Last Calculation</span><small>automatic</small></div>
            </div>
        </section>
        <?php return ob_get_clean();
    }

    public static function first_meta($post_id, $keys, $default = '') {
        foreach ($keys as $key) {
            $value = get_post_meta($post_id, $key, true);
            if ($value !== '' && $value !== null) return $value;
        }
        return $default;
    }
    public function shortcode_company_quick_vote($atts = []) {
        $atts = shortcode_atts(['company_id'=>0], $atts);
        $company_id = intval($atts['company_id']);
        if (!$company_id && is_singular('company')) $company_id = get_the_ID();
        if (!$company_id) return '';

        if (!is_user_logged_in()) {
            return '<div class="tsemou-community-simple-box"><strong>Community rating</strong><p>Log in to rate this company.</p></div>';
        }

        $votes = get_post_meta($company_id, '_tsemou_community_votes', true);
        $current = '';
        if (is_array($votes) && isset($votes[get_current_user_id()]['score'])) {
            $current = floatval($votes[get_current_user_id()]['score']);
        }

        ob_start();
        ?>
        <form method="post" class="tsemou-community-simple-box">
            <h3>Rate this company</h3>
            <p>Your opinion affects only the Community part of the Trust Score.</p>
            <?php wp_nonce_field('tsemou_company_vote', 'tsemou_company_vote_nonce'); ?>
            <input type="hidden" name="company_id" value="<?php echo esc_attr($company_id); ?>">

            <div class="tsemou-rating-options">
                <?php
                $options = [10=>'Excellent',8=>'Good',6=>'Neutral',4=>'Poor',2=>'Very Poor'];
                foreach ($options as $value => $label):
                ?>
                    <label>
                        <input type="radio" name="score" value="<?php echo esc_attr($value); ?>" <?php checked(floatval($current), floatval($value)); ?> required>
                        <span><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="tsemou_company_vote_submit" value="1">Submit rating</button>
        </form>
        <?php
        return ob_get_clean();
    }

    public static function calculate_vote_suspicion($company_id, $user_id) {
        $score = 0;
        $user = get_userdata($user_id);

        if ($user) {
            $created = strtotime($user->user_registered);
            if ($created && (time() - $created) < DAY_IN_SECONDS) $score += 30;
        }

        $votes = get_post_meta($company_id, '_tsemou_community_votes', true);
        if (is_array($votes)) {
            $ip_hash = wp_hash($_SERVER['REMOTE_ADDR'] ?? '');
            $same_ip = 0;
            foreach ($votes as $vote) {
                if (!empty($vote['ip_hash']) && $vote['ip_hash'] === $ip_hash) $same_ip++;
            }
            if ($same_ip >= 3) $score += 40;
            elseif ($same_ip >= 2) $score += 20;
        }

        return min(100, $score);
    }

    public function render_community_moderation_page() {
        if (!current_user_can('manage_options')) return;

        if (!empty($_POST['tsemou_moderation_nonce']) && wp_verify_nonce($_POST['tsemou_moderation_nonce'], 'tsemou_community_moderation')) {
            $company_id = intval($_POST['company_id'] ?? 0);
            $user_id = intval($_POST['user_id'] ?? 0);
            $action = sanitize_text_field($_POST['vote_action'] ?? '');

            if ($company_id && $user_id) {
                $votes = get_post_meta($company_id, '_tsemou_community_votes', true);
                if (is_array($votes) && isset($votes[$user_id])) {
                    if ($action === 'block') $votes[$user_id]['blocked'] = true;
                    if ($action === 'unblock') $votes[$user_id]['blocked'] = false;
                    update_post_meta($company_id, '_tsemou_community_votes', $votes);
                    self::recalculate_company_trust($company_id);
                    echo '<div class="notice notice-success"><p>Vote moderation updated.</p></div>';
                }
            }
        }

        $companies = get_posts([
            'post_type' => 'company',
            'post_status' => ['publish','draft','pending'],
            'numberposts' => 200,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        ?>
        <div class="wrap">
            <h1>Community Moderation</h1>
            <p>Review community votes and block suspicious votes from influencing the Trust Score.</p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Company</th><th>User</th><th>Score</th><th>Suspicion</th><th>Status</th><th>Time</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $has_votes = false;
                    foreach ($companies as $company):
                        $votes = get_post_meta($company->ID, '_tsemou_community_votes', true);
                        if (!is_array($votes) || empty($votes)) continue;
                        foreach ($votes as $user_id => $vote):
                            $has_votes = true;
                            $user = get_userdata(intval($user_id));
                            $blocked = !empty($vote['blocked']);
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($company->post_title); ?></strong></td>
                                <td><?php echo esc_html($user ? $user->user_login : 'User #' . intval($user_id)); ?></td>
                                <td><?php echo esc_html($vote['score'] ?? ''); ?></td>
                                <td><?php echo esc_html($vote['suspicion_score'] ?? 0); ?>/100</td>
                                <td><?php echo $blocked ? '<span style="color:#b91c1c;font-weight:700">Blocked</span>' : '<span style="color:#15803d;font-weight:700">Active</span>'; ?></td>
                                <td><?php echo esc_html($vote['time'] ?? ''); ?></td>
                                <td>
                                    <form method="post">
                                        <?php wp_nonce_field('tsemou_community_moderation', 'tsemou_moderation_nonce'); ?>
                                        <input type="hidden" name="company_id" value="<?php echo esc_attr($company->ID); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                                        <input type="hidden" name="vote_action" value="<?php echo $blocked ? 'unblock' : 'block'; ?>">
                                        <button class="button"><?php echo $blocked ? 'Unblock' : 'Block'; ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach;
                    endforeach;
                    if (!$has_votes): ?>
                        <tr><td colspan="7">No community votes yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function get_evidence_profile_value($evidence_id, $path, $default = '') {
        $json = get_post_meta($evidence_id, '_tsemou_evidence_profile', true);
        $profile = $json ? json_decode($json, true) : null;

        if (!is_array($profile)) {
            return $default;
        }

        $current = $profile;
        foreach (explode('.', $path) as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }

    public static function profile_or_meta($evidence_id, $profile_path, $meta_keys, $default = '') {
        $value = self::get_evidence_profile_value($evidence_id, $profile_path, null);
        if ($value !== null && $value !== '') return $value;
        return self::first_meta($evidence_id, $meta_keys, $default);
    }

    public static function get_evidence_engine_impact($evidence_id) {
        $json = get_post_meta($evidence_id, '_tsemou_evidence_intelligence', true);
        if ($json) {
            $output = json_decode($json, true);
            if (is_array($output) && array_key_exists('impact', $output)) {
                return floatval($output['impact']);
            }
        }
        return null;
    }

}
