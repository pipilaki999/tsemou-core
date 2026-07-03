<?php
namespace TSEMOU\Modules\CompanyIntelligence;

if (!defined('ABSPATH')) exit;

class Company_Intelligence {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 28);
        add_shortcode('tsemou_company_intelligence_status', [$this, 'shortcode_company_intelligence_status']);
    }

    public static function default_report($company_id) {
        return [
            'company_id' => intval($company_id),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => 'draft',
            'summary' => '',
            'risk_overview' => [
                'environment' => 'unknown',
                'workers' => 'unknown',
                'human_rights' => 'unknown',
                'ethics' => 'unknown',
                'transparency' => 'unknown',
                'leadership_influence' => 'unknown',
                'responsibility_omission' => 'unknown',
                'war_conflict' => 'unknown',
            ],
            'research_targets' => self::default_research_targets(),
            'candidate_evidence' => [],
            'notes' => '',
            'human_review_required' => true,
            'trust_score_changed' => false,
        ];
    }

    public static function default_research_targets() {
        return [
            'lawsuits',
            'court_decisions',
            'government_investigations',
            'environmental_violations',
            'labor_violations',
            'human_rights',
            'corruption',
            'sanctions',
            'recalls',
            'pollution',
            'tax_scandals',
            'ngo_reports',
            'un_reports',
            'eu_reports',
            'sec_investigations',
            'leadership_influence',
            'political_financing',
            'war_conflict',
            'responsibility_omission'
        ];
    }

    public static function get_report($company_id) {
        $json = get_post_meta($company_id, '_tsemou_company_intelligence_report', true);
        if ($json) {
            $report = json_decode($json, true);
            if (is_array($report)) {
                return array_merge(self::default_report($company_id), $report);
            }
        }

        return self::default_report($company_id);
    }

    public static function save_report($company_id, $report) {
        $report = is_array($report) ? $report : [];
        $report = array_merge(self::default_report($company_id), $report);
        $report['company_id'] = intval($company_id);
        $report['updated_at'] = current_time('mysql');

        update_post_meta($company_id, '_tsemou_company_intelligence_report', wp_json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        update_post_meta($company_id, '_tsemou_company_intelligence_status', sanitize_key($report['status'] ?? 'draft'));
        update_post_meta($company_id, '_tsemou_company_candidate_evidence_count', count($report['candidate_evidence'] ?? []));

        self::add_log('report_saved', $company_id, 'Company intelligence report saved.');

        return $report;
    }

    public static function parse_candidate_lines($text) {
        $text = trim((string) $text);
        if (!$text) return [];

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $items = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            $parts = array_map('trim', explode('|', $line));

            $items[] = [
                'candidate_id' => 'cand_' . substr(sha1($line . microtime(true)), 0, 16),
                'title' => sanitize_text_field($parts[0] ?? $line),
                'source_url' => esc_url_raw($parts[1] ?? ''),
                'source_name' => sanitize_text_field($parts[2] ?? ''),
                'category' => sanitize_key($parts[3] ?? 'unknown'),
                'severity' => sanitize_key($parts[4] ?? 'unknown'),
                'confidence' => isset($parts[5]) && is_numeric($parts[5]) ? max(0, min(100, intval($parts[5]))) : 0,
                'status' => 'candidate',
                'needs_human_review' => true,
                'created_at' => current_time('mysql'),
            ];
        }

        return $items;
    }

    public static function create_initial_report($company_id, $notes = '', $candidate_text = '') {
        $report = self::get_report($company_id);
        $new_candidates = self::parse_candidate_lines($candidate_text);

        $report['status'] = 'initial_research';
        $report['summary'] = sanitize_textarea_field($notes);
        $report['notes'] = sanitize_textarea_field($notes);
        $report['candidate_evidence'] = array_values(array_merge($report['candidate_evidence'] ?? [], $new_candidates));
        $report['human_review_required'] = true;
        $report['trust_score_changed'] = false;

        return self::save_report($company_id, $report);
    }

    public static function add_log($action, $company_id, $message) {
        $logs = get_option('tsemou_company_intelligence_logs', []);
        if (!is_array($logs)) $logs = [];

        array_unshift($logs, [
            'time' => current_time('mysql'),
            'action' => sanitize_key($action),
            'company_id' => intval($company_id),
            'company' => get_the_title($company_id),
            'message' => sanitize_text_field($message)
        ]);

        update_option('tsemou_company_intelligence_logs', array_slice($logs, 0, 200));
    }

    public static function logs() {
        $logs = get_option('tsemou_company_intelligence_logs', []);
        return is_array($logs) ? $logs : [];
    }

    public static function company_options() {
        return get_posts([
            'post_type' => 'company',
            'post_status' => ['publish','draft','pending','private'],
            'numberposts' => 200,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
    }

    public static function stats() {
        $companies = self::company_options();
        $with_report = 0;
        $candidates = 0;

        foreach ($companies as $company) {
            $json = get_post_meta($company->ID, '_tsemou_company_intelligence_report', true);
            if ($json) $with_report++;

            $count = get_post_meta($company->ID, '_tsemou_company_candidate_evidence_count', true);
            if (is_numeric($count)) $candidates += intval($count);
        }

        return [
            'companies' => count($companies),
            'with_report' => $with_report,
            'candidate_evidence' => $candidates,
            'pending_reports' => max(0, count($companies) - $with_report)
        ];
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Company Intelligence',
            'Company Intelligence',
            'manage_options',
            'tsemou-company-intelligence',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        $notice = null;

        if (!empty($_POST['tsemou_company_intelligence_nonce']) && wp_verify_nonce($_POST['tsemou_company_intelligence_nonce'], 'tsemou_company_intelligence_action')) {
            $company_id = intval($_POST['company_id'] ?? 0);
            $action = sanitize_text_field($_POST['company_intelligence_action'] ?? '');

            if ($company_id && $action === 'create_report') {
                $notes = $_POST['research_notes'] ?? '';
                $candidate_text = $_POST['candidate_evidence'] ?? '';
                self::create_initial_report($company_id, $notes, $candidate_text);
                $notice = ['success'=>true, 'message'=>'Company Intelligence Report saved. Candidate evidence added for human review.'];
            }
        }

        $companies = self::company_options();
        $stats = self::stats();
        $logs = self::logs();
        ?>
        <div class="wrap">
            <h1>TSEMOU Company Intelligence</h1>
            <p>Creates an initial intelligence report and candidate evidence queue for each company. This engine does not publish evidence and does not change Trust Score.</p>

            <?php if ($notice): ?>
                <div class="notice notice-success"><p><?php echo esc_html($notice['message']); ?></p></div>
            <?php endif; ?>

            <h2>Status</h2>
            <table class="widefat striped" style="max-width:760px;">
                <tbody>
                    <tr><th>Companies</th><td><?php echo esc_html($stats['companies']); ?></td></tr>
                    <tr><th>With Intelligence Report</th><td><?php echo esc_html($stats['with_report']); ?></td></tr>
                    <tr><th>Pending Reports</th><td><?php echo esc_html($stats['pending_reports']); ?></td></tr>
                    <tr><th>Candidate Evidence</th><td><?php echo esc_html($stats['candidate_evidence']); ?></td></tr>
                </tbody>
            </table>

            <h2>Create / Update Intelligence Report</h2>
            <form method="post">
                <?php wp_nonce_field('tsemou_company_intelligence_action', 'tsemou_company_intelligence_nonce'); ?>
                <input type="hidden" name="company_intelligence_action" value="create_report">

                <table class="form-table">
                    <tr>
                        <th><label>Company</label></th>
                        <td>
                            <select name="company_id" required>
                                <option value="">Select company</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo esc_attr($company->ID); ?>"><?php echo esc_html(get_the_title($company)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Research Notes</label></th>
                        <td>
                            <textarea name="research_notes" rows="7" style="width:100%;" placeholder="Initial research summary. AI/live research will be connected later."></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Candidate Evidence</label></th>
                        <td>
                            <p>One candidate per line. Format:</p>
                            <code>Title | URL | Source | category | severity | confidence</code>
                            <textarea name="candidate_evidence" rows="8" style="width:100%;font-family:monospace;" placeholder="Example: Coca-Cola pollution case | https://example.com | Reuters | environment | high | 90"></textarea>
                            <p class="description">Candidates are not published. They are stored for human review.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Intelligence Report'); ?>
            </form>

            <h2>Company Intelligence Overview</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Candidate Evidence</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <?php $report = self::get_report($company->ID); ?>
                        <tr>
                            <td><strong><?php echo esc_html(get_the_title($company)); ?></strong></td>
                            <td><?php echo esc_html($report['status'] ?? 'draft'); ?></td>
                            <td><?php echo esc_html(count($report['candidate_evidence'] ?? [])); ?></td>
                            <td><?php echo esc_html($report['updated_at'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Logs</h2>
            <table class="widefat striped">
                <thead><tr><th>Time</th><th>Action</th><th>Company</th><th>Message</th></tr></thead>
                <tbody>
                    <?php if (empty($logs)): ?><tr><td colspan="4">No logs yet.</td></tr><?php endif; ?>
                    <?php foreach (array_slice($logs, 0, 50) as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['time'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['action'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['company'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['message'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function shortcode_company_intelligence_status($atts = []) {
        $stats = self::stats();

        ob_start();
        ?>
        <section class="tsemou-intelligence-status" style="max-width:1180px;margin:24px auto;padding:22px;border:1px solid #e2e8f0;border-radius:22px;background:#fff;">
            <strong style="display:block;font-size:22px;margin-bottom:8px;">Company Intelligence</strong>
            <p style="margin:0 0 16px;color:#64748b;">Initial intelligence reports and candidate evidence waiting for human review.</p>
            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;">
                <div><strong><?php echo esc_html($stats['companies']); ?></strong><span>Companies</span></div>
                <div><strong><?php echo esc_html($stats['with_report']); ?></strong><span>Reports</span></div>
                <div><strong><?php echo esc_html($stats['candidate_evidence']); ?></strong><span>Candidate Evidence</span></div>
                <div><strong><?php echo esc_html($stats['pending_reports']); ?></strong><span>Pending</span></div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
