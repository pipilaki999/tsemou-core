<?php
/**
 * Plugin Name: TSEMOU Core Dev
 * Description: Autonomous TSEMOU OS 3.0 development core for tsemoulab.com.
 * Version: 5.0.1
 * Author: TSEMOU
 */
if (!defined('ABSPATH')) exit;

/**
 * Safety guard:
 * Prevent duplicate TSEMOU Core folders from loading the plugin twice.
 */
if (defined('TSEMOU_CORE_DEV_ALREADY_LOADED')) {
    return;
}
define('TSEMOU_CORE_DEV_ALREADY_LOADED', true);
define('TSEMOU_CORE_VERSION', '5.0.1');
define('TSEMOU_CORE_PATH', plugin_dir_path(__FILE__));
define('TSEMOU_CORE_URL', plugin_dir_url(__FILE__));

if (!function_exists('tsemou_activation_trace')) {
    function tsemou_activation_trace($message) {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[TSEMOU_ACTIVATION_TRACE] ' . $message);
        }
    }
}

tsemou_activation_trace('entry:before_core_require includes/class-core.php');
require_once TSEMOU_CORE_PATH . 'includes/class-core.php';
tsemou_activation_trace('entry:after_core_require includes/class-core.php');
add_action('plugins_loaded', function () {
    tsemou_activation_trace('plugins_loaded:start Core::instance');
    \TSEMOU\Core::instance();
    tsemou_activation_trace('plugins_loaded:ok Core::instance');
});

/**
 * TSEMOU 3.0 hard root admin menu.
 * One root only: TSEMOU OS. All module pages must live below this menu.
 */
add_action('admin_menu', function () {
    add_menu_page(
        'TSEMOU OS',
        'TSEMOU OS',
        'manage_options',
        'tsemou-os',
        'tsemou_core_dev_os_dashboard_render',
        'dashicons-networking',
        3
    );
}, 0);

if (!function_exists('tsemou_core_dev_os_dashboard_render')) {
    function tsemou_core_dev_os_dashboard_render() {
        if (!current_user_can('manage_options')) {
            wp_die('Sorry, you are not allowed to access this page.');
        }

        $counts = [
            'Companies' => post_type_exists('company') ? wp_count_posts('company') : null,
            'Evidence' => post_type_exists('evidence') ? wp_count_posts('evidence') : null,
            'Sources' => post_type_exists('tsemou_source') ? wp_count_posts('tsemou_source') : null,
            'Entities' => post_type_exists('tsemou_entity') ? wp_count_posts('tsemou_entity') : null,
        ];

        echo '<div class="wrap tsemou-dashboard">';
        echo '<h1>TSEMOU OS 3.0</h1>';
        echo '<p><strong>Autonomous Dev Core:</strong> active.</p>';
        echo '<p><strong>Version:</strong> ' . esc_html(TSEMOU_CORE_VERSION) . '</p>';
        echo '<h2>Phase A — Data Acquisition</h2>';
        echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>Layer</th><th>Status</th></tr></thead><tbody>';
        echo '<tr><td>Company Engine</td><td>Foundation active</td></tr>';
        echo '<tr><td>Evidence Engine</td><td>Foundation active</td></tr>';
        echo '<tr><td>Source Engine</td><td>Foundation active</td></tr>';
        echo '<tr><td>Knowledge Graph</td><td>Foundation active</td></tr>';
        echo '<tr><td>Discovery Orchestrator</td><td>Active</td></tr>';
        echo '<tr><td>Source Discovery Engine</td><td>Active</td></tr>';
        echo '<tr><td>Scraping Engine</td><td>Phase A target</td></tr>';
        echo '<tr><td>Automatic Linking</td><td>Active</td></tr>';
        echo '</tbody></table>';
        echo '</div>';
    }
}

/**
 * TSEMOU OS Admin Menu Refactor v3.0.11
 *
 * Centralizes the visible TSEMOU OS submenu after all module registrations.
 * This is intentionally a late cleanup layer: modules may keep registering
 * their own pages, but the final admin UI is deduplicated and ordered here.
 *
 * Result:
 * - one visible TSEMOU OS root
 * - no duplicate Knowledge Graph / Company Intelligence / Scraping entries
 * - stable Phase A / Phase B / Developer ordering
 * - no page callbacks are removed, only duplicate menu rows are hidden
 */
add_action('admin_menu', 'tsemou_core_dev_admin_menu_refactor_311', 9999);

if (!function_exists('tsemou_core_dev_admin_menu_refactor_311')) {
    function tsemou_core_dev_admin_menu_refactor_311() {
        global $submenu;

        $parent = 'tsemou-os';
        if (empty($submenu[$parent]) || !is_array($submenu[$parent])) {
            return;
        }

        $desired_order = [
            'tsemou-os' => 'Dashboard',
            'tsemou-developer-console' => 'Developer Console',

            'tsemou-configuration-os' => 'A · Configuration OS',
            'tsemou-company-discovery' => 'A · Company Discovery',
            'tsemou-company-sensor' => 'A · Company Sensor',
            'tsemou-source-discovery' => 'A · Source Discovery',
            'tsemou-discovery-orchestrator' => 'A · Discovery Orchestrator',
            'tsemou-discovery-engine' => 'A · Discovery Engine',
            'tsemou-scraping-engine' => 'A · Scraping Engine',
            'tsemou-automatic-evidence' => 'A · Automatic Evidence',
            'tsemou-automatic-linking' => 'A · Automatic Linking',
            'tsemou-source-object-engine' => 'A · Source Object Engine',
            'tsemou-evidence-engine' => 'A · Evidence Engine',
            'tsemou-entity-evidence-links' => 'A · Entity Evidence Links',
            'tsemou-knowledge-graph' => 'A · Knowledge Graph',
            'tsemou-section-engine' => 'A · Section Engine',

            'edit.php?post_type=tsemou_entity' => 'Entities',
            'edit.php?post_type=tsemou_proof' => 'Proofs',
            'edit.php?post_type=tsemou_event' => 'Events',

            'tsemou-policy-engine' => 'B · Policy Engine',
            'tsemou-trust-engine' => 'B · Trust Engine',
            'tsemou-community-moderation' => 'B · Community Moderation',
            'tsemou-company-intelligence' => 'B · Company Intelligence',
            'tsemou-source-intelligence' => 'B · Source Intelligence',
            'tsemou-entity-engine' => 'B · Entity Engine',
        ];

        // Keep the latest registration for every slug. This preserves the most
        // recently registered callback while removing duplicate visible rows.
        $by_slug = [];
        for ($i = count($submenu[$parent]) - 1; $i >= 0; $i--) {
            $item = $submenu[$parent][$i];
            $slug = isset($item[2]) ? (string) $item[2] : '';
            if ($slug === '' || isset($by_slug[$slug])) {
                continue;
            }
            $by_slug[$slug] = $item;
        }

        $clean = [];
        foreach ($desired_order as $slug => $label) {
            if (!isset($by_slug[$slug])) {
                continue;
            }
            $item = $by_slug[$slug];
            $item[0] = $label;
            if (isset($item[3])) {
                $item[3] = wp_strip_all_tags($label);
            }
            $clean[] = $item;
            unset($by_slug[$slug]);
        }

        // Keep any future/unknown TSEMOU module once, after the known modules.
        foreach (array_reverse($by_slug) as $item) {
            $clean[] = $item;
        }

        $submenu[$parent] = $clean;
    }
}





/* TSEMOU 3.0: hard root Developer Console registration removed. Developer Console is loaded only as a TSEMOU OS submenu. */

if (!function_exists('tsemou_hard_count_posts')) {
    function tsemou_hard_count_posts($post_type) {
        if (!post_type_exists($post_type)) return ['exists'=>false, 'total'=>0];
        $counts = wp_count_posts($post_type);
        $total = 0;
        foreach (['publish','draft','pending','private'] as $status) {
            $total += isset($counts->$status) ? intval($counts->$status) : 0;
        }
        return ['exists'=>true, 'total'=>$total];
    }
}

if (!function_exists('tsemou_hard_find_company')) {
    function tsemou_hard_find_company($name) {
        $name = sanitize_text_field($name);
        if (!$name) return 0;
        $q = new WP_Query([
            'post_type' => 'company',
            'post_status' => ['publish','draft','pending','private'],
            'posts_per_page' => 1,
            's' => $name,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);
        return !empty($q->posts) ? intval($q->posts[0]->ID) : 0;
    }
}

if (!function_exists('tsemou_hard_evidence_candidates')) {
    function tsemou_hard_evidence_candidates($company_id) {
        $out = [];
        if (!post_type_exists('evidence')) return $out;
        $company_id = absint($company_id);

        $q = new WP_Query([
            'post_type' => 'evidence',
            'post_status' => ['publish','draft','pending','private'],
            'posts_per_page' => 80,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);

        foreach ($q->posts as $ev) {
            $matched = [];
            foreach (get_post_meta($ev->ID) as $key => $values) {
                foreach ((array)$values as $value) {
                    if (is_array($value)) $value = wp_json_encode($value);
                    $value = (string)$value;
                    if ($value === (string)$company_id || strpos($value, '"' . $company_id . '"') !== false || strpos($value, 'i:' . $company_id . ';') !== false) {
                        $matched[] = $key;
                    }
                }
            }
            if (!empty($matched)) {
                $out[] = [
                    'id' => $ev->ID,
                    'title' => get_the_title($ev),
                    'status' => get_post_status($ev),
                    'matched_meta' => implode(', ', array_unique($matched)),
                    'modified' => get_the_modified_date('Y-m-d H:i:s', $ev)
                ];
            }
        }
        return $out;
    }
}

if (!function_exists('tsemou_hard_developer_console_render')) {
    function tsemou_hard_developer_console_render() {
        if (!current_user_can('read')) {
            wp_die('Sorry, you are not allowed to access this page.');
        }

        $company_id = 0;
        if (!empty($_GET['company_id'])) $company_id = absint($_GET['company_id']);
        if (!$company_id && !empty($_GET['company_name'])) $company_id = tsemou_hard_find_company($_GET['company_name']);

        $company = tsemou_hard_count_posts('company');
        $evidence = tsemou_hard_count_posts('evidence');
        $events = tsemou_hard_count_posts('tsemou_event');
        $entities = tsemou_hard_count_posts('tsemou_entity');

        $diag = [];
        if (!empty($_GET['run_diagnostics'])) {
            $diag = [
                'timestamp' => current_time('mysql'),
                'core_version' => defined('TSEMOU_CORE_VERSION') ? TSEMOU_CORE_VERSION : 'unknown',
                'company_id' => $company_id,
                'company_title' => $company_id ? get_the_title($company_id) : '',
                'company_status' => $company_id ? get_post_status($company_id) : '',
                'company_type' => $company_id ? get_post_type($company_id) : '',
                'company_permalink' => $company_id ? get_permalink($company_id) : '',
                'company_meta' => $company_id ? [
                    '_tsemou_country' => get_post_meta($company_id, '_tsemou_country', true),
                    '_tsemou_industry' => get_post_meta($company_id, '_tsemou_industry', true),
                    '_tsemou_trust_score' => get_post_meta($company_id, '_tsemou_trust_score', true),
                    '_tsemou_final_trust_score' => get_post_meta($company_id, '_tsemou_final_trust_score', true),
                ] : [],
                'classes' => [
                    'Company_Discovery' => class_exists('\TSEMOU\Modules\CompanyDiscovery\Company_Discovery') ? 'loaded' : 'missing',
                    'Company_Section_Engine' => class_exists('\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine') ? 'loaded' : 'missing',
                    'Developer_Console' => class_exists('\TSEMOU\Modules\DeveloperConsole\Developer_Console') ? 'loaded' : 'missing',
                ],
                'section_payload' => [],
                'evidence_candidates' => $company_id ? tsemou_hard_evidence_candidates($company_id) : [],
            ];

            if ($company_id && class_exists('\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine')) {
                $payload = \TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine::section_payload($company_id);
                $diag['section_payload'] = [
                    'evidence_count' => intval($payload['counts']['evidence'] ?? 0),
                    'events_count' => intval($payload['counts']['events'] ?? 0),
                    'related_count' => intval($payload['counts']['related'] ?? 0),
                    'tsemits_count' => intval($payload['counts']['tsemits'] ?? 0),
                ];
            }
        }
        ?>
        <div class="wrap">
            <style>
                .tsemou-dev-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:18px 0}
                .tsemou-dev-card{background:#fff;border:1px solid #dbe3ef;border-radius:14px;padding:16px;box-shadow:0 8px 18px rgba(15,23,42,.05)}
                .tsemou-dev-big{font-size:30px;font-weight:800;color:#071226}
                .tsemou-muted{color:#64748b}
                .tsemou-ok{color:#059669;font-weight:700}
                .tsemou-warn{color:#b45309;font-weight:700}
                .tsemou-bad{color:#dc2626;font-weight:700}
                .wrap pre{background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;max-height:540px}
                @media(max-width:900px){.tsemou-dev-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
                @media(max-width:600px){.tsemou-dev-grid{grid-template-columns:1fr}}
            </style>

            <h1>TSEMOU Developer Console</h1>
            <p><strong>Hard Admin Fix:</strong> active in v2.3.2.</p>
            <p class="tsemou-muted">Open from <strong>Tools → TSEMOU Developer Console</strong> or <code>tools.php?page=tsemou-developer-console</code>.</p>

            <div class="tsemou-dev-grid">
                <div class="tsemou-dev-card"><div class="tsemou-muted">Core Version</div><div class="tsemou-dev-big"><?php echo esc_html(defined('TSEMOU_CORE_VERSION') ? TSEMOU_CORE_VERSION : 'unknown'); ?></div></div>
                <div class="tsemou-dev-card"><div class="tsemou-muted">Companies</div><div class="tsemou-dev-big"><?php echo esc_html($company['total']); ?></div></div>
                <div class="tsemou-dev-card"><div class="tsemou-muted">Evidence</div><div class="tsemou-dev-big"><?php echo esc_html($evidence['total']); ?></div></div>
                <div class="tsemou-dev-card"><div class="tsemou-muted">Events</div><div class="tsemou-dev-big"><?php echo esc_html($events['total']); ?></div></div>
            </div>

            <h2>Run Full Diagnostics</h2>
            <form method="get" action="<?php echo esc_url(admin_url('tools.php')); ?>" style="background:#fff;border:1px solid #dbe3ef;padding:16px;border-radius:14px;margin-bottom:20px;">
                <input type="hidden" name="page" value="tsemou-developer-console">
                <input type="hidden" name="run_diagnostics" value="1">
                <label><strong>Company ID</strong></label>
                <input type="number" name="company_id" value="<?php echo esc_attr($company_id ?: ''); ?>" placeholder="7723" style="width:160px;">
                <label style="margin-left:12px;"><strong>or Company name</strong></label>
                <input type="text" name="company_name" value="<?php echo esc_attr($_GET['company_name'] ?? ''); ?>" placeholder="Starbucks" style="width:220px;">
                <button class="button button-primary">Run Full Diagnostics</button>
            </form>

            <?php if (function_exists('tsemou_renderer_switch_controls')) tsemou_renderer_switch_controls($company_id); ?>

            
            <?php if (!empty($_GET['run_diagnostics']) && !empty($company_id) && function_exists('tsemou_clean_tsemport_renderer')): ?>
                <h2>Inline Admin TSEMPORT Preview</h2>
                <div style="background:#ecfeff;border:1px solid #67e8f9;border-radius:14px;padding:12px 16px;margin:12px 0 18px;color:#155e75;">
                    Rendered directly inside Developer Console. No frontend route, no draft permalink, no theme template.
                </div>
                <div style="background:#f8fafc;border:1px solid #dbe3ef;border-radius:18px;padding:0;margin-bottom:24px;overflow:hidden;">
                    <?php echo tsemou_clean_tsemport_renderer($company_id); ?>
                </div>
            <?php endif; ?>

            <h2>Engine Status</h2>
            <table class="widefat striped">
                <thead><tr><th>Engine</th><th>Status</th><th>Records</th><th>Source</th></tr></thead>
                <tbody>
                    <tr><td><strong>Company Engine</strong></td><td class="<?php echo $company['exists'] ? 'tsemou-ok' : 'tsemou-bad'; ?>"><?php echo $company['exists'] ? 'loaded' : 'missing'; ?></td><td><?php echo esc_html($company['total']); ?></td><td>post_type: company</td></tr>
                    <tr><td><strong>Evidence / TSEMIDENCE</strong></td><td class="<?php echo $evidence['exists'] ? 'tsemou-ok' : 'tsemou-bad'; ?>"><?php echo $evidence['exists'] ? 'loaded' : 'missing'; ?></td><td><?php echo esc_html($evidence['total']); ?></td><td>post_type: evidence</td></tr>
                    <tr><td><strong>Timeline</strong></td><td class="<?php echo $events['exists'] ? 'tsemou-ok' : 'tsemou-warn'; ?>"><?php echo $events['exists'] ? 'loaded' : 'foundation'; ?></td><td><?php echo esc_html($events['total']); ?></td><td>post_type: tsemou_event</td></tr>
                    <tr><td><strong>Entity Engine</strong></td><td class="<?php echo $entities['exists'] ? 'tsemou-ok' : 'tsemou-warn'; ?>"><?php echo $entities['exists'] ? 'loaded' : 'foundation'; ?></td><td><?php echo esc_html($entities['total']); ?></td><td>post_type: tsemou_entity</td></tr>
                </tbody>
            </table>

            <?php if (!empty($diag)): ?>
                <h2>Diagnostic JSON</h2>
                <pre><?php echo esc_html(wp_json_encode($diag, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>

                <h2>Evidence Candidates</h2>
                <table class="widefat striped">
                    <thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Matched Meta</th><th>Modified</th></tr></thead>
                    <tbody>
                        <?php if (empty($diag['evidence_candidates'])): ?>
                            <tr><td colspan="5">No evidence candidates found for this company.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($diag['evidence_candidates'] as $ev): ?>
                            <tr>
                                <td><?php echo esc_html($ev['id']); ?></td>
                                <td><?php echo esc_html($ev['title']); ?></td>
                                <td><?php echo esc_html($ev['status']); ?></td>
                                <td><?php echo esc_html($ev['matched_meta']); ?></td>
                                <td><?php echo esc_html($ev['modified']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}





/**
 * TSEMOU v2.3.4 Clean TSEMPORT Renderer.
 *
 * Clean renderer rules:
 * - Legacy mode never touches the current public page.
 * - Preview mode gives admins a safe direct preview.
 * - New mode replaces single company output only through template_redirect.
 * - Renderer reads from engines/payload only. No Elementor/ACF dependency.
 */
if (!function_exists('tsemou_renderer_mode')) {
    function tsemou_renderer_mode() {
        $mode = get_option('tsemou_tsemport_renderer_mode', 'legacy');
        return in_array($mode, ['legacy','preview','new'], true) ? $mode : 'legacy';
    }
}

if (!function_exists('tsemou_set_renderer_mode')) {
    function tsemou_set_renderer_mode($mode) {
        $mode = sanitize_key($mode);
        if (!in_array($mode, ['legacy','preview','new'], true)) $mode = 'legacy';
        update_option('tsemou_tsemport_renderer_mode', $mode, false);
        return $mode;
    }
}

add_action('admin_post_tsemou_set_renderer_mode', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Sorry, you are not allowed to change renderer mode.');
    }
    check_admin_referer('tsemou_set_renderer_mode');
    $mode = isset($_POST['renderer_mode']) ? sanitize_key($_POST['renderer_mode']) : 'legacy';
    tsemou_set_renderer_mode($mode);
    $redirect = wp_get_referer() ?: admin_url('admin.php?page=tsemou-developer-console');
    wp_safe_redirect($redirect);
    exit;
});

if (!function_exists('tsemou_company_profile_safe')) {
    function tsemou_company_profile_safe($company_id) {
        $profile = [
            'title' => get_the_title($company_id),
            'country' => get_post_meta($company_id, '_tsemou_country', true),
            'industry' => get_post_meta($company_id, '_tsemou_industry', true),
            'website' => get_post_meta($company_id, '_tsemou_website', true),
            'summary' => get_post_meta($company_id, '_tsemou_summary', true),
        ];

        if (class_exists('\TSEMOU\Modules\CompanyDiscovery\Company_Discovery')) {
            $discovered = \TSEMOU\Modules\CompanyDiscovery\Company_Discovery::company_profile($company_id);
            if (is_array($discovered)) {
                foreach ($discovered as $k => $v) {
                    if (empty($profile[$k]) && !empty($v)) $profile[$k] = $v;
                }
            }
        }

        return $profile;
    }
}

if (!function_exists('tsemou_company_payload_safe')) {
    function tsemou_company_payload_safe($company_id) {
        $payload = [
            'evidence'=>[],
            'events'=>[],
            'related'=>[],
            'community'=>['total'=>0],
            'counts'=>['evidence'=>0,'events'=>0,'related'=>0,'tsemits'=>0]
        ];

        if (class_exists('\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine')) {
            $real = \TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine::section_payload($company_id);
            if (is_array($real)) $payload = array_replace_recursive($payload, $real);
        }

        return $payload;
    }
}

if (!function_exists('tsemou_company_score_safe')) {
    function tsemou_company_score_safe($company_id) {
        $score = get_post_meta($company_id, '_tsemou_trust_score', true);
        if (!is_numeric($score)) $score = get_post_meta($company_id, '_tsemou_final_trust_score', true);
        if (!is_numeric($score)) $score = get_post_meta($company_id, 'tsemou_trust_score', true);
        return is_numeric($score) ? round(floatval($score), 1) : 5.0;
    }
}

if (!function_exists('tsemou_brand_mark_safe')) {
    function tsemou_brand_mark_safe($title) {
        $mark = mb_substr($title, 0, 1);
        $class = 'default';
        $lower = strtolower($title);
        if (strpos($lower, 'starbucks') !== false) { $mark = '★'; $class = 'starbucks'; }
        elseif (strpos($lower, 'mcdonald') !== false) { $mark = 'M'; $class = 'mcd'; }
        elseif (strpos($lower, 'coca') !== false) { $mark = 'C'; $class = 'coke'; }
        elseif (strpos($lower, 'pepsi') !== false) { $mark = 'P'; $class = 'pepsi'; }
        elseif (strpos($lower, 'tesla') !== false) { $mark = 'T'; $class = 'tesla'; }
        return ['mark'=>$mark, 'class'=>$class];
    }
}

if (!function_exists('tsemou_clean_tsemport_renderer')) {
    function tsemou_clean_tsemport_renderer($company_id = 0) {
        $company_id = $company_id ? absint($company_id) : get_the_ID();

        if (!$company_id || get_post_type($company_id) !== 'company') {
            return '<div class="tsemou-renderer-error">Invalid company for TSEMPORT renderer.</div>';
        }

        $profile = tsemou_company_profile_safe($company_id);
        $payload = tsemou_company_payload_safe($company_id);
        $score = tsemou_company_score_safe($company_id);
        $title = $profile['title'] ?: get_the_title($company_id);
        $country = $profile['country'] ?? '';
        $industry = $profile['industry'] ?? '';
        $website = $profile['website'] ?? '';
        $summary = $profile['summary'] ?? '';

        $brand = tsemou_brand_mark_safe($title);
        $evidence_count = intval($payload['counts']['evidence'] ?? 0);
        $events_count = intval($payload['counts']['events'] ?? 0);
        $related_count = intval($payload['counts']['related'] ?? 0);
        $tsemit_count = intval($payload['counts']['tsemits'] ?? 0);

        ob_start();
        ?>
        <style>
        .tsemou-tsemport-clean,.tsemou-tsemport-clean *{box-sizing:border-box;writing-mode:horizontal-tb!important;text-orientation:mixed!important;word-break:normal}
        .tsemou-tsemport-clean{width:100%;max-width:1500px;margin:0 auto;padding:28px 18px 50px;background:#f8fafc;color:#071226;font-family:Inter,Arial,sans-serif}
        .tsemou-clean-shell{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:18px;align-items:start}
        .tsemou-clean-main,.tsemou-clean-side{min-width:0}
        .clean-card{background:#fff;border:1px solid #e2e8f0;border-radius:22px;padding:18px;box-shadow:0 12px 30px rgba(15,23,42,.055);margin-bottom:18px}
        .clean-hero{position:relative;overflow:hidden;border-radius:30px;min-height:320px;padding:34px;color:#fff;background:radial-gradient(circle at 82% 22%,rgba(124,58,237,.55),transparent 27%),radial-gradient(circle at 65% 83%,rgba(245,158,11,.42),transparent 24%),radial-gradient(circle at 25% 20%,rgba(37,99,235,.62),transparent 32%),linear-gradient(135deg,#061226 0%,#111b4d 55%,#130c2f 100%);box-shadow:0 24px 60px rgba(15,23,42,.20);margin-bottom:18px}
        .clean-hero:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 58% 50%,rgba(56,189,248,.18),transparent 30%),linear-gradient(115deg,rgba(255,255,255,.12),transparent 36%),repeating-linear-gradient(90deg,rgba(255,255,255,.045) 0,rgba(255,255,255,.045) 1px,transparent 1px,transparent 58px);opacity:.64}
        .clean-hero-inner{position:relative;z-index:1;display:grid;grid-template-columns:132px minmax(0,1fr) 270px;gap:28px;align-items:center}
        .clean-brand{display:flex;align-items:center;justify-content:center;width:124px;height:124px;border-radius:32px;font-size:56px;font-weight:950;background:#fff;color:#071226;box-shadow:0 18px 42px rgba(0,0,0,.18)}
        .clean-brand.starbucks{background:#00754a;color:#fff}.clean-brand.mcd{background:#ffbc0d;color:#da291c}.clean-brand.coke{background:#f40009;color:#fff}.clean-brand.pepsi{background:#2563eb;color:#fff}.clean-brand.tesla{background:#cc0000;color:#fff}
        .clean-pill{display:inline-flex;border-radius:999px;padding:7px 12px;font-size:12px;font-weight:950;white-space:nowrap}
        .pill-green{background:#10b981;color:#fff}.pill-yellow{background:#fbbf24;color:#111827}.pill-glass{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.16);color:#e2e8f0}
        .clean-hero h1{color:#fff;font-size:46px;line-height:1.02;margin:12px 0;font-weight:950}
        .clean-tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
        .clean-desc{color:#dbeafe;line-height:1.55;font-size:15px;margin:0;max-width:700px}
        .clean-score{background:rgba(15,23,42,.52);border:1px solid rgba(255,255,255,.17);border-radius:22px;padding:20px;box-shadow:0 18px 42px rgba(0,0,0,.18)}
        .score-label{font-size:12px;font-weight:950;color:#e2e8f0;text-transform:uppercase}.score-main{font-size:58px;line-height:1;color:#fbbf24;font-weight:950;margin:10px 0}.score-main small{font-size:25px;color:#e2e8f0}
        .purple-btn{display:inline-flex;align-items:center;justify-content:center;margin-top:14px;background:linear-gradient(135deg,#7c3aed,#2563eb);color:#fff!important;text-decoration:none;border-radius:13px;padding:12px 16px;font-weight:950;white-space:nowrap;border:0}
        .clean-metrics{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:18px}
        .clean-metric{background:#fff;border:1px solid #e2e8f0;border-radius:18px;min-height:126px;padding:16px;box-shadow:0 10px 24px rgba(15,23,42,.045)}
        .clean-metric-icon{font-size:23px;line-height:1;margin-bottom:9px}.clean-metric-title{font-size:11px;font-weight:950;text-transform:uppercase;color:#334155;margin-bottom:13px}.clean-metric-value{font-size:28px;font-weight:950;color:#071226;line-height:1.1}
        .clean-section-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}.clean-section-title{margin:0;color:#071226;font-size:13px;font-weight:950;text-transform:uppercase;letter-spacing:.02em}.clean-link{color:#2563eb;font-size:11px;font-weight:900;text-decoration:none;white-space:nowrap}
        .clean-evidence-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.clean-evidence-card{border:1px solid #e2e8f0;border-radius:17px;padding:14px;background:#fff}.clean-evidence-badge{display:inline-flex;border-radius:999px;padding:6px 9px;font-size:11px;font-weight:950;margin-bottom:10px;background:#dbeafe;color:#1d4ed8}.clean-evidence-card h4{margin:0 0 8px;font-size:15px}.clean-evidence-card p{margin:0 0 8px;color:#475569;font-size:13px;line-height:1.45}
        .clean-timeline{border-left:3px solid #dbeafe;margin-left:10px;padding-left:18px}.clean-timeline-item{position:relative;padding-bottom:15px}.clean-dot{position:absolute;left:-28px;top:4px;width:13px;height:13px;border-radius:50%;background:#2563eb}.clean-date{font-weight:950;font-size:13px}.clean-text{font-size:12px;color:#334155;line-height:1.35}
        .clean-related{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.clean-related span{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:13px;text-align:center;font-weight:900;font-size:13px}.clean-related small{color:#64748b;font-weight:700}
        .clean-why{background:#071226;color:#fff;border-color:#071226}.clean-why h3{color:#fff;margin:0 0 10px;font-size:14px}.clean-why p{color:#cbd5e1;margin:0 0 12px;font-size:13px;line-height:1.5}
        .clean-positive{background:linear-gradient(135deg,#fff,#f0fdf4)}.clean-community{background:linear-gradient(135deg,#fff,#f3e8ff)}
        @media(max-width:1180px){.tsemou-clean-shell{grid-template-columns:1fr}.tsemou-clean-side{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.clean-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}}
        @media(max-width:820px){.clean-hero-inner{display:block}.clean-brand{margin-bottom:16px}.clean-score{margin-top:18px}.clean-hero h1{font-size:34px}.clean-metrics,.clean-evidence-grid,.tsemou-clean-side{grid-template-columns:1fr}}
        </style>

        <section class="tsemou-tsemport-clean">
            <div class="tsemou-clean-shell">
                <main class="tsemou-clean-main">
                    <div class="clean-hero">
                        <div class="clean-hero-inner">
                            <span class="clean-brand <?php echo esc_attr($brand['class']); ?>"><?php echo esc_html($brand['mark']); ?></span>
                            <div>
                                <span class="clean-pill pill-green">Clean TSEMPORT Renderer</span>
                                <h1><?php echo esc_html($title); ?></h1>
                                <div class="clean-tags">
                                    <?php if ($country): ?><span class="clean-pill pill-glass">🌍 <?php echo esc_html($country); ?></span><?php endif; ?>
                                    <?php if ($industry): ?><span class="clean-pill pill-glass">🏭 <?php echo esc_html($industry); ?></span><?php endif; ?>
                                    <span class="clean-pill pill-glass">🛡️ <?php echo esc_html($evidence_count); ?> TSEMIDENCE</span>
                                    <span class="clean-pill pill-glass">👥 <?php echo esc_html($tsemit_count > 0 ? $tsemit_count . ' TSEMITs' : 'TSEMIT open'); ?></span>
                                </div>
                                <p class="clean-desc"><?php echo esc_html($summary ?: 'This TSEMPORT connects the company identity, TSEMScore, TSEMIDENCE, timeline, related entities and community signals through the TSEMOU engines.'); ?></p>
                            </div>
                            <div class="clean-score">
                                <div class="score-label">TSEMScore</div>
                                <div class="score-main"><?php echo esc_html($score); ?> <small>/10</small></div>
                                <span class="clean-pill pill-yellow">Engine connected</span><br>
                                <a class="purple-btn" href="#tsemou-community-vote">TSEMIT NOW →</a>
                            </div>
                        </div>
                    </div>

                    <div class="clean-metrics">
                        <div class="clean-metric"><div class="clean-metric-icon">〽️</div><div class="clean-metric-title">TSEMScore</div><div class="clean-metric-value"><?php echo esc_html($score); ?></div></div>
                        <div class="clean-metric"><div class="clean-metric-icon">🛡️</div><div class="clean-metric-title">TSEMIDENCE</div><div class="clean-metric-value"><?php echo esc_html($evidence_count); ?></div></div>
                        <div class="clean-metric"><div class="clean-metric-icon">🗓️</div><div class="clean-metric-title">Timeline</div><div class="clean-metric-value"><?php echo esc_html($events_count); ?></div></div>
                        <div class="clean-metric"><div class="clean-metric-icon">⌘</div><div class="clean-metric-title">Relations</div><div class="clean-metric-value"><?php echo esc_html($related_count); ?></div></div>
                        <div class="clean-metric"><div class="clean-metric-icon">👥</div><div class="clean-metric-title">TSEMITS</div><div class="clean-metric-value"><?php echo esc_html($tsemit_count > 0 ? $tsemit_count : 'Open'); ?></div></div>
                        <div class="clean-metric"><div class="clean-metric-icon">🧭</div><div class="clean-metric-title">Mode</div><div class="clean-metric-value">Clean</div></div>
                    </div>

                    <div class="clean-card">
                        <h3 class="clean-section-title">〽 Current Status</h3>
                        <p>Clean renderer is reading directly from the TSEMOU engines. This is the replacement foundation for the old Company Report.</p>
                    </div>

                    <div class="clean-card">
                        <div class="clean-section-head"><h3 class="clean-section-title">TSEMIDENCE</h3><a class="clean-link" href="#">Add / review →</a></div>
                        <div class="clean-evidence-grid">
                            <?php if (!empty($payload['evidence'])): ?>
                                <?php foreach ($payload['evidence'] as $ev): ?>
                                    <?php
                                    $ev_status = class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine') ? \TSEMOU\Modules\CompanyEngine\Company_Engine::get_evidence_status_label($ev->ID) : (get_post_meta($ev->ID, '_tsemou_evidence_status', true) ?: get_post_status($ev));
                                    $ev_kind = class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine') ? \TSEMOU\Modules\CompanyEngine\Company_Engine::get_evidence_kind_label($ev->ID) : (get_post_meta($ev->ID, '_tsemou_evidence_kind', true) ?: 'report');
                                    $ev_url = class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine') ? \TSEMOU\Modules\CompanyEngine\Company_Engine::get_evidence_source_url($ev->ID) : get_post_meta($ev->ID, '_tsemou_source_url', true);
                                    ?>
                                    <div class="clean-evidence-card">
                                        <span class="clean-evidence-badge"><?php echo esc_html($ev_status); ?></span>
                                        <h4><?php echo esc_html(get_the_title($ev)); ?></h4>
                                        <p><?php echo esc_html((class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine') ? \TSEMOU\Modules\CompanyEngine\Company_Engine::get_evidence_display_excerpt($ev->ID, 24) : wp_trim_words(wp_strip_all_tags($ev->post_content), 24))); ?></p>
                                        <p><small><?php echo esc_html($ev_kind); ?></small></p>
                                        <?php if ($ev_url): ?><p><a class="clean-link" href="<?php echo esc_url($ev_url); ?>" target="_blank" rel="noopener">Source →</a></p><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="clean-evidence-card"><span class="clean-evidence-badge">Empty</span><h4>No linked TSEMIDENCE yet</h4><p>Evidence linked through Company relationship or Entity ID will appear here.</p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </main>

                <aside class="tsemou-clean-side">
                    <div class="clean-card">
                        <div class="clean-section-head"><h3 class="clean-section-title">🗓️ Timeline</h3><a class="clean-link">View full →</a></div>
                        <div class="clean-timeline">
                            <?php if (!empty($payload['events'])): ?>
                                <?php foreach ($payload['events'] as $event): ?>
                                    <div class="clean-timeline-item"><span class="clean-dot"></span><div class="clean-date"><?php echo esc_html($event['date'] ?: 'Now'); ?></div><div class="clean-text"><strong><?php echo esc_html($event['title']); ?></strong><br><?php echo esc_html(wp_trim_words($event['description'], 16)); ?></div></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="clean-timeline-item"><span class="clean-dot"></span><div class="clean-date">Now</div><div class="clean-text">Clean TSEMPORT renderer connected.</div></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="clean-card">
                        <div class="clean-section-head"><h3 class="clean-section-title">⌘ Related Entities</h3><a class="clean-link">Explore →</a></div>
                        <div class="clean-related">
                            <?php foreach (($payload['related'] ?? []) as $rel): ?>
                                <span><?php echo esc_html($rel['type'] ?? 'Entity'); ?><br><small><?php echo esc_html($rel['label'] ?? '—'); ?></small></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="clean-card clean-why">
                        <h3>Why this TSEMScore?</h3>
                        <p>The score begins from baseline and changes through evidence, review, positive actions and community signals.</p>
                    </div>

                    <div class="clean-card clean-positive">
                        <h3 class="clean-section-title">🌿 Positive Actions</h3>
                        <p>Positive actions are tracked separately from concerns.</p>
                    </div>

                    <div class="clean-card clean-community" id="tsemou-community-vote">
                        <h3 class="clean-section-title">👥 TSEMIT</h3>
                        <p>Your opinion helps improve transparency and shape the TSEMScore.</p>
                        <a class="purple-btn" href="#">TSEMIT NOW →</a>
                    </div>
                </aside>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

add_action('template_redirect', function() {
    if (!is_singular('company')) return;

    $mode = tsemou_renderer_mode();
    $preview = isset($_GET['tsemou_tsemport_preview']) && $_GET['tsemou_tsemport_preview'] === '1';

    if ($mode === 'legacy' && !$preview) return;
    if ($mode === 'preview' && !$preview) return;

    if ($preview && !current_user_can('manage_options')) {
        wp_die('Preview is available only to admins.');
    }

    if ($mode === 'new' || $preview) {
        status_header(200);
        nocache_headers();
        get_header();
        echo tsemou_clean_tsemport_renderer(get_queried_object_id());
        get_footer();
        exit;
    }
}, 5);

if (!function_exists('tsemou_renderer_switch_controls')) {
    function tsemou_renderer_switch_controls($company_id = 0) {
        if (!current_user_can('manage_options')) return;
        $company_id = absint($company_id);
        $mode = tsemou_renderer_mode();

        if (!$company_id && !empty($_GET['company_id'])) $company_id = absint($_GET['company_id']);
        if (!$company_id && !empty($_GET['company_name']) && function_exists('tsemou_hard_find_company')) {
            $company_id = tsemou_hard_find_company($_GET['company_name']);
        }

        $preview_url = $company_id ? add_query_arg(['page'=>'tsemou-developer-console','company_id'=>$company_id,'run_diagnostics'=>'1','render_tsemport_admin_preview'=>'1'], admin_url('tools.php')) : '';
        ?>
        <div class="tsemou-dev-card" style="background:#fff;border:1px solid #dbe3ef;border-radius:14px;padding:16px;margin:18px 0;">
            <h2 style="margin-top:0;">Clean TSEMPORT Renderer Switch</h2>
            <p><strong>Current mode:</strong> <?php echo esc_html($mode); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <?php wp_nonce_field('tsemou_set_renderer_mode'); ?>
                <input type="hidden" name="action" value="tsemou_set_renderer_mode">
                <select name="renderer_mode">
                    <option value="legacy" <?php selected($mode, 'legacy'); ?>>Legacy - old page stays live</option>
                    <option value="preview" <?php selected($mode, 'preview'); ?>>Preview - admin preview only</option>
                    <option value="new" <?php selected($mode, 'new'); ?>>New TSEMPORT - live</option>
                </select>
                <button class="button button-primary">Save Renderer Mode</button>
                <?php if ($preview_url): ?>
                    <a class="button" target="_blank" href="<?php echo esc_url($preview_url); ?>">Open Admin Preview</a>
                <?php else: ?>
                    <span style="color:#b45309;">Run diagnostics with a Company ID to generate preview link.</span>
                <?php endif; ?>
            </form>
            <p style="color:#64748b;margin-bottom:0;">Recommended now: Preview. Press Run Diagnostics; the preview appears automatically below.</p>
        </div>
        <?php
    }
}



/**
 * v2.3.7 Elementor company_evidence bridge.
 * Keeps Elementor widgets using Query ID "company_evidence" aligned with the
 * same evidence relationships used by Company Section Engine.
 */
add_action('elementor/query/company_evidence', function($query) {
    if (!is_singular('company')) return;

    $company_id = get_queried_object_id();
    if (!$company_id) return;

    $query->set('post_type', ['evidence', 'tsemou_proof']);
    $query->set('post_status', ['publish']);
    $query->set('posts_per_page', 6);
    $query->set('meta_query', [
        'relation' => 'OR',
        ['key' => '_tsemou_company_id', 'value' => $company_id, 'compare' => '='],
        ['key' => '_tsemou_entity_id', 'value' => $company_id, 'compare' => '='],
        ['key' => '_tsemou_evidence_company_ids', 'value' => '"' . $company_id . '"', 'compare' => 'LIKE'],
        ['key' => '_tsemou_evidence_company', 'value' => $company_id, 'compare' => '='],
        ['key' => 'tsemou_evidence_company', 'value' => $company_id, 'compare' => '='],
        ['key' => 'related_company', 'value' => $company_id, 'compare' => '='],
        ['key' => 'company', 'value' => $company_id, 'compare' => '='],
        ['key' => 'company', 'value' => '"' . $company_id . '"', 'compare' => 'LIKE'],
        ['key' => 'company', 'value' => 'i:' . $company_id . ';', 'compare' => 'LIKE'],
    ]);
    $query->set('orderby', 'date');
    $query->set('order', 'DESC');
});



/**
 * TSEMOU v2.4.1 Evidence Engine safety loader.
 */
add_action('plugins_loaded', function() {
    $file = TSEMOU_CORE_PATH . 'modules/evidence-engine/class-evidence-engine.php';
    if (file_exists($file) && !class_exists('\\TSEMOU\\Modules\\EvidenceEngine\\Evidence_Engine')) {
        require_once $file;
    }
    if (class_exists('\\TSEMOU\\Modules\\EvidenceEngine\\Evidence_Engine')) {
        \TSEMOU\Modules\EvidenceEngine\Evidence_Engine::instance();
    }
}, 45);



/* TSEMOU 3.0: removed duplicate Tools page for Evidence Engine. Use TSEMOU OS submenu only. */



/**
 * TSEMOU v2.5.0 Source Intelligence Engine safety loader.
 */
add_action('plugins_loaded', function() {
    $file = TSEMOU_CORE_PATH . 'modules/source-intelligence/class-source-intelligence-engine.php';
    if (file_exists($file) && !class_exists('\\TSEMOU\\Modules\\SourceIntelligence\\Source_Intelligence_Engine')) {
        require_once $file;
    }
    if (class_exists('\\TSEMOU\\Modules\\SourceIntelligence\\Source_Intelligence_Engine')) {
        \TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine::instance();
    }
}, 44);



/* TSEMOU 3.0: removed duplicate Tools page for Source Intelligence. Use TSEMOU OS submenu only. */



/**
 * TSEMOU v2.6.0 Discovery Engine safety loader.
 */
add_action('plugins_loaded', function() {
    $file = TSEMOU_CORE_PATH . 'modules/discovery-engine/class-discovery-engine.php';
    if (file_exists($file) && !class_exists('\\TSEMOU\\Modules\\DiscoveryEngine\\Discovery_Engine')) {
        require_once $file;
    }
    if (class_exists('\\TSEMOU\\Modules\\DiscoveryEngine\\Discovery_Engine')) {
        \TSEMOU\Modules\DiscoveryEngine\Discovery_Engine::instance();
    }
}, 43);



/* TSEMOU 3.0: removed duplicate Tools page for Discovery Engine. Use TSEMOU OS submenu only. */



/**
 * TSEMOU v2.7.0 Company Intelligence Engine safety loader.
 */
add_action('plugins_loaded', function() {
    $file = TSEMOU_CORE_PATH . 'modules/company-intelligence/class-company-intelligence-engine.php';
    if (file_exists($file) && !class_exists('\\TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine')) {
        require_once $file;
    }
    if (class_exists('\\TSEMOU\\Modules\\CompanyIntelligence\\Company_Intelligence_Engine')) {
        \TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine::instance();
    }
}, 42);



/* TSEMOU 3.0: removed duplicate Tools page for Company Intelligence. Use TSEMOU OS submenu only. */



/**
 * TSEMOU v2.8.0 Source Object Engine safety loader.
 */
add_action('plugins_loaded', function() {
    $file = TSEMOU_CORE_PATH . 'modules/source-object/class-source-object-engine.php';
    if (file_exists($file) && !class_exists('\\TSEMOU\\Modules\\SourceObject\\Source_Object_Engine')) {
        require_once $file;
    }
    if (class_exists('\\TSEMOU\\Modules\\SourceObject\\Source_Object_Engine')) {
        \TSEMOU\Modules\SourceObject\Source_Object_Engine::instance();
    }
}, 41);



/* TSEMOU 3.0: removed duplicate Tools page for Source Object. Use TSEMOU OS submenu only. */


/**
 * TSEMOU v2.9.1 Knowledge Graph Engine safety loader.
 */
add_action('plugins_loaded', function() {
    $file = TSEMOU_CORE_PATH . 'modules/knowledge-graph/class-knowledge-graph-engine.php';
    if (file_exists($file) && !class_exists('\\TSEMOU\\Modules\\KnowledgeGraph\\Knowledge_Graph_Engine')) {
        require_once $file;
    }
    if (class_exists('\\TSEMOU\\Modules\\KnowledgeGraph\\Knowledge_Graph_Engine')) {
        \TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph_Engine::instance();
    }
}, 40);

/* TSEMOU 3.0: removed duplicate Tools page for Knowledge Graph. Use TSEMOU OS submenu only. */
