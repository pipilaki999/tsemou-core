<?php
namespace TSEMOU\Modules\PolicyEngine;

if (!defined('ABSPATH')) exit;

class Policy_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 25);
    }

    public static function defaults() {
        return [
            'severity.low' => 0.2,
            'severity.medium' => 0.5,
            'severity.high' => 1.0,
            'severity.critical' => 2.0,
            'evidence_type.court_decision' => 4.0,
            'evidence_type.court_case' => 2.5,
            'evidence_type.government_report' => 2.0,
            'evidence_type.scientific_study' => 2.0,
            'evidence_type.audit' => 1.8,
            'evidence_type.ngo_report' => 1.5,
            'evidence_type.journalistic_investigation' => 1.4,
            'evidence_type.former_employee' => 1.3,
            'evidence_type.whistleblower' => 1.3,
            'evidence_type.official_statement' => 0.7,
            'evidence_type.community_report' => 0.6,
            'evidence_type.other' => 1.0,
            'source_type.court' => 2.5,
            'source_type.government' => 2.0,
            'source_type.academic' => 1.8,
            'source_type.ngo' => 1.5,
            'source_type.journalist' => 1.4,
            'source_type.former_employee' => 1.3,
            'source_type.former_partner' => 1.3,
            'source_type.company' => 0.7,
            'source_type.community' => 0.6,
            'source_type.anonymous' => 0.4,
            'source_type.other' => 1.0,
            'verification.unverified' => 0.5,
            'verification.partially_verified' => 0.8,
            'verification.verified_documents' => 1.3,
            'verification.multiple_sources' => 1.6,
            'verification.government_verified' => 2.0,
            'verification.court_verified' => 2.5,
            'legal.not_legal' => 1.0,
            'legal.allegation' => 0.7,
            'legal.under_investigation' => 1.1,
            'legal.charges_filed' => 1.5,
            'legal.court_case' => 1.8,
            'legal.convicted' => 2.5,
            'legal.appeal_pending' => 2.0,
            'legal.cleared' => -0.8,
            'legal.dismissed' => -0.6,
            'time_decay.lt_1_year' => 1.0,
            'time_decay.y1_3' => 0.9,
            'time_decay.y3_5' => 0.75,
            'time_decay.y5_10' => 0.5,
            'time_decay.gt_10' => 0.3,
            'trust.max_single_impact' => 1.0,
            'trust.positive_impact_factor' => 0.6,
            'trust.evidence_weight' => 85,
            'trust.community_weight' => 15,
            'trust.base_score' => 5.0,
            'promotion.top_candidate_min_score' => 85,
            'promotion.second_candidate_min_score' => 70,
            'promotion.third_candidate_min_score' => 55,
            'promotion.top_candidate_interval_hours' => 1,
            'promotion.second_candidate_interval_hours' => 2,
            'promotion.third_candidate_interval_hours' => 4,
            'promotion.min_public_importance_score' => 50,
        ];
    }

    public static function all() {
        $stored = get_option('tsemou_policy_engine', []);
        return array_merge(self::defaults(), is_array($stored) ? $stored : []);
    }

    public static function get($key, $default = null) {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function weight($group, $key, $default_key = 'other', $default = 1.0) {
        $key = sanitize_key(str_replace('-', '_', (string) $key));
        $default_key = sanitize_key(str_replace('-', '_', (string) $default_key));
        $value = self::get($group . '.' . $key, null);
        if ($value === null) $value = self::get($group . '.' . $default_key, $default);
        return floatval($value);
    }

    public function admin_menu() {
        add_submenu_page('tsemou-os','Policy Engine','Policy Engine','manage_options','tsemou-policy-engine',[$this,'render_admin_page']);
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        if (!empty($_POST['tsemou_policy_nonce']) && wp_verify_nonce($_POST['tsemou_policy_nonce'], 'tsemou_save_policy_engine')) {
            $next = self::defaults();
            foreach ($next as $key => $default) {
                $field = 'policy_' . md5($key);
                if (isset($_POST[$field])) $next[$key] = floatval($_POST[$field]);
            }
            update_option('tsemou_policy_engine', $next);
            echo '<div class="notice notice-success"><p>Policy Engine saved.</p></div>';
        }

        $policies = self::all();
        $groups = [];
        foreach ($policies as $key => $value) {
            $parts = explode('.', $key, 2);
            $group = $parts[0] ?? 'general';
            $groups[$group][$key] = $value;
        }
        ?>
        <div class="wrap">
            <h1>TSEMOU Policy Engine</h1>
            <p>Central rules used by Evidence, Trust and Community Engines. Change policies here instead of editing code.</p>
            <form method="post">
                <?php wp_nonce_field('tsemou_save_policy_engine', 'tsemou_policy_nonce'); ?>
                <?php foreach ($groups as $group => $items): ?>
                    <h2><?php echo esc_html(ucwords(str_replace('_', ' ', $group))); ?></h2>
                    <table class="form-table">
                        <?php foreach ($items as $key => $value): ?>
                            <tr>
                                <th><?php echo esc_html(str_replace('.', ' → ', $key)); ?></th>
                                <td><input type="number" step="0.1" name="<?php echo esc_attr('policy_' . md5($key)); ?>" value="<?php echo esc_attr($value); ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endforeach; ?>
                <?php submit_button('Save Policy Engine'); ?>
            </form>
        </div>
        <?php
    }
}
