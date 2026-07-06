<?php
namespace TSEMOU\Modules\Story;
if (!defined('ABSPATH')) exit;
class Story_Module {
    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    private function __construct() {
        add_action('init', [$this, 'register_story_cpt']);
        add_action('add_meta_boxes', [$this, 'add_story_meta_boxes']);
        add_action('save_post_story', [$this, 'save_story_meta'], 10, 2);
        add_action('save_post_story', [$this, 'trigger_event_identity_analysis'], 20, 2);
        add_action('tsemou_story_promoted', [$this, 'handle_story_promoted'], 10, 2);
        add_action('tsemou_living_case_updated', [$this, 'handle_living_case_updated'], 10, 2);
        add_action('tsemou_lifecycle_stage', [$this, 'handle_lifecycle_stage'], 10, 1);
        // TSEMOU 3.0: root TSEMOU OS menu is registered centrally in tsemou-core.php.
    }
    public function add_os_menu() {
        // Deprecated in 3.0. Root menu is registered centrally.
    }
    public function render_os_dashboard() {
        $files = wp_count_posts('story');
        $proofs = wp_count_posts('tsemou_proof');
        $companies = wp_count_posts('company');
        ?>
        <div class="wrap tsemou-dashboard">
            <h1>TSEMOU OS</h1>
            <p>Knowledge operating system for TSEMOU.</p>
            <div class="tsemou-dashboard-grid">
                <div><strong><?php echo intval($files->publish ?? 0); ?></strong><span>Published Files</span></div>
                <div><strong><?php echo intval($proofs->publish ?? 0); ?></strong><span>Published Proofs</span></div>
                <div><strong><?php echo intval($companies->publish ?? 0); ?></strong><span>Existing Companies</span></div>
                <div><strong>v0.8</strong><span>Company Engine</span></div>
            </div>
        </div>
        <?php
    }
    public function register_story_cpt() {
        register_post_type('story', [
            'labels' => [
                'name' => 'TSEMOU Files',
                'singular_name' => 'TSEMOU File',
                'menu_name' => 'TSEMOU Files',
                'add_new_item' => 'Add New TSEMOU File',
                'edit_item' => 'Edit TSEMOU File',
                'all_items' => 'All TSEMOU Files'
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 21,
            'menu_icon' => 'dashicons-networking',
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'files'],
            'supports' => ['title','editor','excerpt','thumbnail','author','revisions'],
            'capability_type' => 'post'
        ]);
    }
    public function add_story_meta_boxes() {
        add_meta_box('tsemou_file_workspace','TSEMOU File Workspace',[$this,'render_file_workspace'],'story','normal','high');
    }
    public function render_file_workspace($post) {
        wp_nonce_field('tsemou_save_story_meta', 'tsemou_story_nonce');
        $data = [
            'call_sign'=>get_post_meta($post->ID,'_tsemou_call_sign',true),
            'status'=>get_post_meta($post->ID,'_tsemou_status',true) ?: 'active',
            'question'=>get_post_meta($post->ID,'_tsemou_question',true),
            'summary'=>get_post_meta($post->ID,'_tsemou_executive_summary',true),
            'what_happened'=>get_post_meta($post->ID,'_tsemou_what_happened',true),
            'why_matters'=>get_post_meta($post->ID,'_tsemou_why_matters',true),
            'update_note'=>get_post_meta($post->ID,'_tsemou_last_update_note',true),
            'signals_count'=>get_post_meta($post->ID,'_tsemou_signals_count',true) ?: '0',
            'updates_count'=>get_post_meta($post->ID,'_tsemou_updates_count',true) ?: '0',
            'your_impact'=>get_post_meta($post->ID,'_tsemou_your_impact',true),
            'better_choices'=>get_post_meta($post->ID,'_tsemou_better_choices',true),
        ];
        include TSEMOU_CORE_PATH . 'modules/story/views/file-workspace.php';
    }
    public function save_story_meta($post_id, $post) {
        if (!isset($_POST['tsemou_story_nonce']) || !wp_verify_nonce($_POST['tsemou_story_nonce'], 'tsemou_save_story_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = [
            '_tsemou_call_sign'=>'tsemou_call_sign',
            '_tsemou_status'=>'tsemou_status',
            '_tsemou_question'=>'tsemou_question',
            '_tsemou_executive_summary'=>'tsemou_executive_summary',
            '_tsemou_what_happened'=>'tsemou_what_happened',
            '_tsemou_why_matters'=>'tsemou_why_matters',
            '_tsemou_last_update_note'=>'tsemou_last_update_note',
            '_tsemou_signals_count'=>'tsemou_signals_count',
            '_tsemou_updates_count'=>'tsemou_updates_count',
            '_tsemou_your_impact'=>'tsemou_your_impact',
            '_tsemou_better_choices'=>'tsemou_better_choices'
        ];
        foreach ($fields as $meta_key=>$post_key) {
            if (isset($_POST[$post_key])) update_post_meta($post_id, $meta_key, sanitize_textarea_field($_POST[$post_key]));
        }

        if (class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine')) {
            $company_ids = isset($_POST['tsemou_connected_companies']) ? (array) $_POST['tsemou_connected_companies'] : [];
            \TSEMOU\Modules\CompanyEngine\Company_Engine::save_connected_companies($post_id, $company_ids);

            $relationships = isset($_POST['tsemou_company_relationships']) ? (array) $_POST['tsemou_company_relationships'] : [];
            \TSEMOU\Modules\CompanyEngine\Company_Engine::save_company_relationships($post_id, $relationships);
        }

        $this->ensure_initial_lifecycle_stages($post_id);

        $story_status = sanitize_key((string) get_post_meta($post_id, '_tsemou_status', true));
        if ($story_status === 'archived') {
            $current_stage = sanitize_text_field((string) get_post_meta($post_id, '_tsemou_lifecycle_current_stage', true));
            if ($current_stage !== 'Historical Archive') {
                $this->append_lifecycle_stage($post_id, 'Historical Archive', [
                    'source' => 'story_module',
                    'context' => ['status' => 'archived'],
                ]);

                do_action('tsemou_story_archived', $post_id, [
                    'story_id' => absint($post_id),
                    'status' => 'archived',
                    'archived_at' => current_time('mysql'),
                ]);
            }
        }
    }

    public function trigger_event_identity_analysis($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if ($post->post_type !== 'story') return;

        if (class_exists('\\TSEMOU\\Modules\\EventIdentity\\Event_Identity_Engine')) {
            \TSEMOU\Modules\EventIdentity\Event_Identity_Engine::instance()->analyze_story($post_id);
        }
    }

    public function handle_story_promoted($story_id, $payload = []) {
        $story_id = absint($story_id);
        if ($story_id <= 0) return;

        $post = get_post($story_id);
        if (!$post || $post->post_type !== 'story') return;

        $now = current_time('mysql');
        update_post_meta($story_id, '_tsemou_living_case_state', 'active');
        update_post_meta($story_id, '_tsemou_living_case_updated_at', $now);

        if (is_array($payload)) {
            if (isset($payload['slot'])) {
                update_post_meta($story_id, '_tsemou_promotion_candidate_slot', sanitize_text_field($payload['slot']));
            }
            if (isset($payload['score'])) {
                update_post_meta($story_id, '_tsemou_story_ranking_score', floatval($payload['score']));
            }
        }

        $this->append_lifecycle_stage($story_id, 'Living Case', [
            'source' => 'story_module',
            'context' => is_array($payload) ? $payload : [],
        ]);
    }

    public function handle_living_case_updated($story_id, $payload = []) {
        $story_id = absint($story_id);
        if ($story_id <= 0) return;

        if (!class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine')) {
            return;
        }

        $this->append_lifecycle_stage($story_id, 'Knowledge Graph Update', [
            'source' => 'story_module',
            'context' => is_array($payload) ? $payload : [],
        ]);

        $this->append_lifecycle_stage($story_id, 'Continuous Evolution', [
            'source' => 'story_module',
            'context' => ['trigger' => 'living_case_updated'],
        ]);

        $company_ids = \TSEMOU\Modules\CompanyEngine\Company_Engine::get_connected_company_ids($story_id);
        do_action('tsemou_public_pages_update_requested', $story_id, $company_ids, is_array($payload) ? $payload : []);
    }

    public function handle_lifecycle_stage($payload = []) {
        if (!is_array($payload)) return;

        $story_id = absint($payload['story_id'] ?? 0);
        $stage = sanitize_text_field($payload['stage'] ?? '');
        if ($story_id <= 0 || $stage === '') return;

        $this->append_lifecycle_stage($story_id, $stage, [
            'source' => sanitize_text_field($payload['source'] ?? 'runtime'),
            'context' => is_array($payload['context'] ?? null) ? $payload['context'] : [],
        ]);
    }

    private function ensure_initial_lifecycle_stages($story_id) {
        $history = get_post_meta($story_id, '_tsemou_lifecycle_history', true);
        if (!is_array($history) || empty($history)) {
            $this->append_lifecycle_stage($story_id, 'Submission', ['source' => 'story_module']);
            $this->append_lifecycle_stage($story_id, 'Seed', ['source' => 'story_module']);
            $this->append_lifecycle_stage($story_id, 'Community Feed', ['source' => 'story_module']);
        }
    }

    private function append_lifecycle_stage($story_id, $stage, $meta = []) {
        $history = get_post_meta($story_id, '_tsemou_lifecycle_history', true);
        if (!is_array($history)) $history = [];

        $entry = [
            'stage' => sanitize_text_field($stage),
            'at' => current_time('mysql'),
            'meta' => is_array($meta) ? $meta : [],
        ];

        $history[] = $entry;
        if (count($history) > 200) {
            $history = array_slice($history, -200);
        }

        update_post_meta($story_id, '_tsemou_lifecycle_history', $history);
        update_post_meta($story_id, '_tsemou_lifecycle_current_stage', sanitize_text_field($stage));
        update_post_meta($story_id, '_tsemou_lifecycle_updated_at', $entry['at']);
    }
}
