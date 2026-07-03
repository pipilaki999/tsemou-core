<?php
namespace TSEMOU\Modules\DiscoveryEngine;

if (!defined('ABSPATH')) exit;

/**
 * TSEMOU Discovery Engine v2.9.0
 *
 * Foundation for automated internet evidence discovery.
 * This version creates a safe admin-controlled discovery queue.
 */
class Discovery_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('admin_menu', [$this, 'admin_menu'], 26);
        add_action('admin_post_tsemou_discovery_add_candidate', [$this, 'handle_add_candidate']);
        add_action('admin_post_tsemou_discovery_create_evidence', [$this, 'handle_create_evidence']);
    }

    public function register_post_type() {
        register_post_type('tsemou_discovery', [
            'labels' => [
                'name' => 'TSEMOU Discoveries',
                'singular_name' => 'TSEMOU Discovery',
                'add_new_item' => 'Add Discovery Candidate',
                'edit_item' => 'Edit Discovery Candidate',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'custom-fields'],
            'capability_type' => 'post',
        ]);
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Discovery Engine',
            'Discovery Engine',
            'manage_options',
            'tsemou-discovery-engine',
            [$this, 'render_admin_page']
        );
    }

    public static function source_intel($url) {
        if (class_exists('\TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine')) {
            return \TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine::analyze($url);
        }

        $domain = parse_url($url, PHP_URL_HOST);
        return [
            'domain' => $domain,
            'publisher' => $domain ?: 'Unknown',
            'credibility' => null,
            'credibility_label' => 'Not rated',
            'class' => 'unknown',
            'warnings' => ['source_intelligence_engine_missing'],
        ];
    }

    public static function detect_companies($text, $limit = 10) {
        if (class_exists('\\TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine')) {
            return \TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine::detect_in_text($text, $limit);
        }

        $text = strtolower(wp_strip_all_tags((string) $text));
        if ($text === '') return [];

        $companies = get_posts([
            'post_type' => 'company',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'numberposts' => 300,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $matches = [];

        foreach ($companies as $company) {
            $title = get_the_title($company->ID);
            $needle = strtolower($title);
            if ($needle && strpos($text, $needle) !== false) {
                $matches[] = [
                    'company_id' => $company->ID,
                    'title' => $title,
                    'match_type' => 'title_exact_text',
                    'confidence' => 0.88,
                ];
            }
        }

        usort($matches, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return array_slice($matches, 0, intval($limit));
    }

    public static function candidate_status($candidate_id) {
        $url = get_post_meta($candidate_id, '_tsemou_discovery_url', true);
        $company_ids = get_post_meta($candidate_id, '_tsemou_discovery_company_ids', true);
        $source = get_post_meta($candidate_id, '_tsemou_discovery_source_intel', true);

        if (!is_array($company_ids)) $company_ids = [];
        if (!is_array($source)) $source = [];

        $warnings = [];
        $ready = true;

        if (empty($url)) {
            $warnings[] = 'missing_url';
            $ready = false;
        }

        if (empty($company_ids)) {
            $warnings[] = 'no_company_match';
            $ready = false;
        }

        if (empty($source['credibility']) && empty($source['known'])) {
            $warnings[] = 'unknown_source';
        }

        return [
            'ready_for_evidence' => $ready,
            'warnings' => $warnings,
            'company_ids' => $company_ids,
            'source' => $source,
        ];
    }

    public static function create_candidate($url, $title = '', $text = '') {
        $url = esc_url_raw($url);
        $title = sanitize_text_field($title);
        $text = wp_kses_post($text);

        if (!$url) {
            return new \WP_Error('missing_url', 'Discovery URL is required.');
        }

        $source = self::source_intel($url);
        $search_text = trim($title . ' ' . wp_strip_all_tags($text));
        $companies = self::detect_companies($search_text);
        $company_ids = array_map(function($match) { return intval($match['company_id']); }, $companies);

        $post_title = $title ?: ('Discovery Candidate – ' . ($source['publisher'] ?? parse_url($url, PHP_URL_HOST)));

        $candidate_id = wp_insert_post([
            'post_type' => 'tsemou_discovery',
            'post_status' => 'draft',
            'post_title' => $post_title,
            'post_content' => $text,
        ], true);

        if (is_wp_error($candidate_id)) return $candidate_id;

        update_post_meta($candidate_id, '_tsemou_discovery_url', $url);
        update_post_meta($candidate_id, '_tsemou_discovery_source_intel', $source);
        update_post_meta($candidate_id, '_tsemou_discovery_company_matches', $companies);
        update_post_meta($candidate_id, '_tsemou_discovery_company_ids', $company_ids);
        update_post_meta($candidate_id, '_tsemou_discovery_status', 'candidate');
        update_post_meta($candidate_id, '_tsemou_discovery_created_at', current_time('mysql'));

        return $candidate_id;
    }

    public static function create_evidence_from_candidate($candidate_id) {
        $candidate_id = absint($candidate_id);
        $candidate = get_post($candidate_id);

        if (!$candidate || $candidate->post_type !== 'tsemou_discovery') {
            return new \WP_Error('invalid_candidate', 'Invalid discovery candidate.');
        }

        $status = self::candidate_status($candidate_id);
        if (empty($status['ready_for_evidence'])) {
            return new \WP_Error('candidate_not_ready', 'Candidate is not ready for evidence creation.');
        }

        $url = get_post_meta($candidate_id, '_tsemou_discovery_url', true);
        $source = get_post_meta($candidate_id, '_tsemou_discovery_source_intel', true);
        $company_ids = get_post_meta($candidate_id, '_tsemou_discovery_company_ids', true);
        if (!is_array($source)) $source = [];
        if (!is_array($company_ids)) $company_ids = [];

        $evidence_id = wp_insert_post([
            'post_type' => post_type_exists('evidence') ? 'evidence' : 'tsemou_proof',
            'post_status' => 'pending',
            'post_title' => $candidate->post_title,
            'post_content' => $candidate->post_content,
        ], true);

        if (is_wp_error($evidence_id)) return $evidence_id;

        update_post_meta($evidence_id, '_tsemou_source_url', esc_url_raw($url));

        if (class_exists('\\TSEMOU\\Modules\\SourceObject\\Source_Object_Engine')) {
            $source_id = \TSEMOU\Modules\SourceObject\Source_Object_Engine::find_or_create_from_url($url);
            if ($source_id) {
                update_post_meta($evidence_id, '_tsemou_source_id', absint($source_id));
            }
        }
        update_post_meta($evidence_id, '_tsemou_summary', wp_trim_words(wp_strip_all_tags($candidate->post_content), 50));
        update_post_meta($evidence_id, '_tsemou_evidence_status', 'pending_review');
        update_post_meta($evidence_id, '_tsemou_evidence_kind', 'discovered_article');
        update_post_meta($evidence_id, '_tsemou_discovery_candidate_id', $candidate_id);

        if (!empty($source['credibility']) && is_numeric($source['credibility'])) {
            update_post_meta($evidence_id, '_tsemou_credibility_score', floatval($source['credibility']));
        }

        if (!empty($company_ids)) {
            update_post_meta($evidence_id, '_tsemou_company_id', intval($company_ids[0]));
            update_post_meta($evidence_id, '_tsemou_entity_id', intval($company_ids[0]));
            update_post_meta($evidence_id, 'company', array_map('intval', $company_ids));
        }

        update_post_meta($candidate_id, '_tsemou_discovery_status', 'evidence_created');
        update_post_meta($candidate_id, '_tsemou_discovery_evidence_id', $evidence_id);

        if (class_exists('\\TSEMOU\\Modules\\KnowledgeGraph\\Knowledge_Graph_Engine')) {
            \TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph_Engine::link_evidence_company_source($evidence_id);
        }

        return $evidence_id;
    }

    public function handle_add_candidate() {
        if (!current_user_can('manage_options')) wp_die('Not allowed.');
        check_admin_referer('tsemou_discovery_add_candidate');

        $url = $_POST['discovery_url'] ?? '';
        $title = $_POST['discovery_title'] ?? '';
        $text = $_POST['discovery_text'] ?? '';

        $candidate_id = self::create_candidate($url, $title, $text);

        $redirect = admin_url('tools.php?page=tsemou-discovery-engine');
        if (is_wp_error($candidate_id)) {
            $redirect = add_query_arg(['tsemou_error' => rawurlencode($candidate_id->get_error_message())], $redirect);
        } else {
            $redirect = add_query_arg(['candidate_id' => $candidate_id, 'created' => 1], $redirect);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public function handle_create_evidence() {
        if (!current_user_can('manage_options')) wp_die('Not allowed.');
        check_admin_referer('tsemou_discovery_create_evidence');

        $candidate_id = absint($_POST['candidate_id'] ?? 0);
        $evidence_id = self::create_evidence_from_candidate($candidate_id);

        $redirect = admin_url('tools.php?page=tsemou-discovery-engine&candidate_id=' . $candidate_id);
        if (is_wp_error($evidence_id)) {
            $redirect = add_query_arg(['tsemou_error' => rawurlencode($evidence_id->get_error_message())], $redirect);
        } else {
            $redirect = add_query_arg(['evidence_id' => $evidence_id, 'evidence_created' => 1], $redirect);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public static function recent_candidates($limit = 20) {
        return get_posts([
            'post_type' => 'tsemou_discovery',
            'post_status' => ['draft', 'pending', 'publish'],
            'numberposts' => intval($limit),
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        $candidate_id = !empty($_GET['candidate_id']) ? absint($_GET['candidate_id']) : 0;
        $candidate = $candidate_id ? get_post($candidate_id) : null;
        ?>
        <div class="wrap">
            <h1>TSEMOU Discovery Engine</h1>
            <p>v2.6.0 safe discovery queue foundation. This does not scrape automatically yet; it prepares the pipeline.</p>

            <?php if (!empty($_GET['tsemou_error'])): ?>
                <div class="notice notice-error"><p><?php echo esc_html($_GET['tsemou_error']); ?></p></div>
            <?php endif; ?>

            <?php if (!empty($_GET['created'])): ?>
                <div class="notice notice-success"><p>Discovery candidate created.</p></div>
            <?php endif; ?>

            <?php if (!empty($_GET['evidence_created'])): ?>
                <div class="notice notice-success"><p>Evidence created from discovery candidate.</p></div>
            <?php endif; ?>

            <h2>Add Discovery Candidate</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="background:#fff;border:1px solid #dbe3ef;padding:16px;border-radius:14px;margin-bottom:20px;max-width:900px;">
                <?php wp_nonce_field('tsemou_discovery_add_candidate'); ?>
                <input type="hidden" name="action" value="tsemou_discovery_add_candidate">

                <p>
                    <label><strong>Source URL</strong></label><br>
                    <input type="url" name="discovery_url" placeholder="https://www.reuters.com/..." style="width:100%;max-width:760px;">
                </p>

                <p>
                    <label><strong>Title</strong></label><br>
                    <input type="text" name="discovery_title" placeholder="Article title" style="width:100%;max-width:760px;">
                </p>

                <p>
                    <label><strong>Text / Summary</strong></label><br>
                    <textarea name="discovery_text" rows="6" style="width:100%;max-width:760px;" placeholder="Paste article summary or excerpt. Company matching will search this text."></textarea>
                </p>

                <button class="button button-primary">Create Candidate</button>
            </form>

            <?php if ($candidate && $candidate->post_type === 'tsemou_discovery'): ?>
                <?php
                $status = self::candidate_status($candidate_id);
                $url = get_post_meta($candidate_id, '_tsemou_discovery_url', true);
                $matches = get_post_meta($candidate_id, '_tsemou_discovery_company_matches', true);
                if (!is_array($matches)) $matches = [];
                ?>
                <h2>Candidate Diagnostic</h2>
                <table class="widefat striped">
                    <tbody>
                        <tr><th>ID</th><td><?php echo esc_html($candidate_id); ?></td></tr>
                        <tr><th>Title</th><td><?php echo esc_html(get_the_title($candidate_id)); ?></td></tr>
                        <tr><th>URL</th><td><?php echo esc_html($url); ?></td></tr>
                        <tr><th>Ready for Evidence</th><td><?php echo !empty($status['ready_for_evidence']) ? 'yes' : 'no'; ?></td></tr>
                        <tr><th>Warnings</th><td><?php echo esc_html(implode(', ', $status['warnings'])); ?></td></tr>
                    </tbody>
                </table>

                <h3>Source Intelligence</h3>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($status['source'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>

                <h3>Company Matches</h3>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($matches, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>

                <?php if (!empty($status['ready_for_evidence'])): ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('tsemou_discovery_create_evidence'); ?>
                        <input type="hidden" name="action" value="tsemou_discovery_create_evidence">
                        <input type="hidden" name="candidate_id" value="<?php echo esc_attr($candidate_id); ?>">
                        <button class="button button-primary">Create Pending Evidence</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <h2>Recent Candidates</h2>
            <table class="widefat striped">
                <thead>
                    <tr><th>ID</th><th>Title</th><th>Status</th><th>URL</th><th>Companies</th><th>Open</th></tr>
                </thead>
                <tbody>
                <?php foreach (self::recent_candidates() as $item): ?>
                    <?php
                    $url = get_post_meta($item->ID, '_tsemou_discovery_url', true);
                    $ids = get_post_meta($item->ID, '_tsemou_discovery_company_ids', true);
                    if (!is_array($ids)) $ids = [];
                    ?>
                    <tr>
                        <td><?php echo esc_html($item->ID); ?></td>
                        <td><?php echo esc_html(get_the_title($item)); ?></td>
                        <td><?php echo esc_html(get_post_meta($item->ID, '_tsemou_discovery_status', true)); ?></td>
                        <td><?php echo esc_html($url); ?></td>
                        <td><?php echo esc_html(implode(', ', $ids)); ?></td>
                        <td><a class="button" href="<?php echo esc_url(admin_url('tools.php?page=tsemou-discovery-engine&candidate_id=' . $item->ID)); ?>">Inspect</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
