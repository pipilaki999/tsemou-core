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
    }

    public function trigger_event_identity_analysis($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if ($post->post_type !== 'story') return;

        if (class_exists('\\TSEMOU\\Modules\\EventIdentity\\Event_Identity_Engine')) {
            \TSEMOU\Modules\EventIdentity\Event_Identity_Engine::instance()->analyze_story($post_id);
        }
    }
}
