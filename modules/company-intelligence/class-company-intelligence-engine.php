<?php
namespace TSEMOU\Modules\CompanyIntelligence;

if (!defined('ABSPATH')) exit;

/**
 * TSEMOU Company Intelligence Engine v2.7.0
 *
 * Minimal canonical company identity and matching layer.
 * No AI, no external lookup, no heavy fuzzy matching yet.
 */
class Company_Intelligence_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 27);
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

    public static function normalize_name($value) {
        $value = strtolower(wp_strip_all_tags((string) $value));
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}\s\.\-&]/u', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value);

        $remove = [
            ' inc', ' incorporated', ' corporation', ' corp', ' company', ' co',
            ' ltd', ' limited', ' llc', ' plc', ' s a', ' sa', ' ag', ' nv',
            ' group', ' holdings', ' holding'
        ];

        foreach ($remove as $suffix) {
            if (substr($value, -strlen($suffix)) === $suffix) {
                $value = trim(substr($value, 0, -strlen($suffix)));
            }
        }

        return sanitize_text_field($value);
    }

    public static function normalize_domain($value) {
        $value = trim((string) $value);
        if ($value === '') return '';

        if (strpos($value, '://') === false && strpos($value, '.') !== false) {
            $value = 'https://' . $value;
        }

        $host = parse_url($value, PHP_URL_HOST);
        if (!$host) $host = $value;

        $host = strtolower(trim($host));
        $host = preg_replace('/^www\./', '', $host);
        $host = preg_replace('/:\d+$/', '', $host);

        return sanitize_text_field($host);
    }

    public static function split_aliases($value) {
        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/[\n,;|]+/', (string) $value);
        }

        $out = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item !== '') $out[] = $item;
        }

        return array_values(array_unique($out));
    }

    public static function get_company_identity($company_id) {
        $company_id = absint($company_id);
        $post = get_post($company_id);

        if (!$post || $post->post_type !== 'company') return null;

        $title = get_the_title($company_id);

        $official = self::first_meta($company_id, [
            '_tsemou_official_name',
            'tsemou_official_name',
            'official_name',
            'company_official_name',
        ], '');

        $display = self::first_meta($company_id, [
            '_tsemou_display_name',
            'tsemou_display_name',
            'display_name',
            'company_display_name',
        ], $title);

        $aliases_raw = self::first_meta($company_id, [
            '_tsemou_aliases',
            'tsemou_aliases',
            'aliases',
            'company_aliases',
        ], '');

        $ticker = self::first_meta($company_id, [
            '_tsemou_ticker',
            'tsemou_ticker',
            'ticker',
            'stock_symbol',
        ], '');

        $website = self::first_meta($company_id, [
            '_tsemou_website',
            'tsemou_website',
            'website',
            'company_website',
            'url',
        ], '');

        $country = self::first_meta($company_id, [
            '_tsemou_country',
            'tsemou_country',
            'country',
        ], '');

        $industry = self::first_meta($company_id, [
            '_tsemou_industry',
            'tsemou_industry',
            'industry',
        ], '');

        $aliases = self::split_aliases($aliases_raw);
        $base_aliases = [$title, $display, $official];

        if (!empty($ticker)) {
            $base_aliases[] = $ticker;
            $base_aliases[] = strtoupper($ticker);
        }

        $aliases = array_values(array_unique(array_filter(array_merge($base_aliases, $aliases))));

        $normalized_aliases = [];
        foreach ($aliases as $alias) {
            $norm = self::normalize_name($alias);
            if ($norm !== '') $normalized_aliases[] = $norm;
        }

        $domain = self::normalize_domain($website);

        return [
            'company_id' => $company_id,
            'title' => $title,
            'canonical_name' => $official ?: $display ?: $title,
            'display_name' => $display ?: $title,
            'official_name' => $official ?: '',
            'aliases' => $aliases,
            'normalized_aliases' => array_values(array_unique($normalized_aliases)),
            'ticker' => strtoupper(trim((string) $ticker)),
            'website' => esc_url_raw((string) $website),
            'domain' => $domain,
            'country' => $country,
            'industry' => $industry,
            'status' => get_post_status($company_id),
        ];
    }

    public static function first_meta($post_id, $keys, $default = '') {
        foreach ((array) $keys as $key) {
            $value = get_post_meta($post_id, $key, true);
            if ($value !== '' && $value !== null && $value !== []) {
                return is_string($value) ? trim($value) : $value;
            }
        }
        return $default;
    }

    public static function company_index($limit = 1000) {
        $companies = get_posts([
            'post_type' => 'company',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'numberposts' => intval($limit),
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $index = [];
        foreach ($companies as $company) {
            $identity = self::get_company_identity($company->ID);
            if ($identity) $index[] = $identity;
        }

        return $index;
    }

    public static function match($input, $limit = 10) {
        $input_original = trim((string) $input);
        $input_norm = self::normalize_name($input_original);
        $input_domain = self::normalize_domain($input_original);
        $input_ticker = strtoupper(trim($input_original));

        if ($input_norm === '' && $input_domain === '' && $input_ticker === '') return [];

        $matches = [];

        foreach (self::company_index() as $company) {
            $best = null;

            if ($input_domain && !empty($company['domain']) && $input_domain === $company['domain']) {
                $best = [
                    'company_id' => $company['company_id'],
                    'title' => $company['title'],
                    'canonical_name' => $company['canonical_name'],
                    'match_type' => 'domain_exact',
                    'matched_value' => $company['domain'],
                    'confidence' => 0.98,
                ];
            }

            if (!$best && $input_ticker && !empty($company['ticker']) && $input_ticker === $company['ticker']) {
                $best = [
                    'company_id' => $company['company_id'],
                    'title' => $company['title'],
                    'canonical_name' => $company['canonical_name'],
                    'match_type' => 'ticker_exact',
                    'matched_value' => $company['ticker'],
                    'confidence' => 0.96,
                ];
            }

            if (!$best && $input_norm) {
                foreach ($company['normalized_aliases'] as $alias) {
                    if ($alias === $input_norm) {
                        $best = [
                            'company_id' => $company['company_id'],
                            'title' => $company['title'],
                            'canonical_name' => $company['canonical_name'],
                            'match_type' => 'alias_exact',
                            'matched_value' => $alias,
                            'confidence' => 0.94,
                        ];
                        break;
                    }
                }
            }

            if (!$best && $input_norm) {
                foreach ($company['normalized_aliases'] as $alias) {
                    if (strlen($alias) >= 4 && (strpos($input_norm, $alias) !== false || strpos($alias, $input_norm) !== false)) {
                        $best = [
                            'company_id' => $company['company_id'],
                            'title' => $company['title'],
                            'canonical_name' => $company['canonical_name'],
                            'match_type' => 'alias_contains',
                            'matched_value' => $alias,
                            'confidence' => 0.78,
                        ];
                        break;
                    }
                }
            }

            if ($best) {
                $best['identity'] = $company;
                $matches[] = $best;
            }
        }

        usort($matches, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return array_slice($matches, 0, intval($limit));
    }

    public static function detect_in_text($text, $limit = 10) {
        $text_norm = self::normalize_name($text);
        if ($text_norm === '') return [];

        $matches = [];

        foreach (self::company_index() as $company) {
            $best = null;

            foreach ($company['normalized_aliases'] as $alias) {
                if (strlen($alias) < 3) continue;

                if (preg_match('/\b' . preg_quote($alias, '/') . '\b/u', $text_norm)) {
                    $best = [
                        'company_id' => $company['company_id'],
                        'title' => $company['title'],
                        'canonical_name' => $company['canonical_name'],
                        'match_type' => 'text_alias',
                        'matched_value' => $alias,
                        'confidence' => strlen($alias) <= 3 ? 0.70 : 0.86,
                    ];
                    break;
                }
            }

            if ($best) {
                $best['identity'] = $company;
                $matches[] = $best;
            }
        }

        usort($matches, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return array_slice($matches, 0, intval($limit));
    }

    public static function enrich_company($company_id) {
        $company_id = absint($company_id);
        $identity = self::get_company_identity($company_id);
        if (!$identity) return false;

        update_post_meta($company_id, '_tsemou_ci_canonical_name', $identity['canonical_name']);
        update_post_meta($company_id, '_tsemou_ci_normalized_aliases', $identity['normalized_aliases']);
        update_post_meta($company_id, '_tsemou_ci_domain', $identity['domain']);
        update_post_meta($company_id, '_tsemou_ci_last_indexed', current_time('mysql'));

        return true;
    }

    public static function enrich_all($limit = 500) {
        $count = 0;
        foreach (self::company_index($limit) as $identity) {
            if (self::enrich_company($identity['company_id'])) $count++;
        }
        return $count;
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        $query = isset($_GET['company_query']) ? sanitize_text_field($_GET['company_query']) : '';
        $company_id = isset($_GET['company_id']) ? absint($_GET['company_id']) : 0;
        $matches = $query ? self::match($query, 20) : [];
        $identity = $company_id ? self::get_company_identity($company_id) : null;

        if (!empty($_POST['tsemou_ci_enrich_all']) && check_admin_referer('tsemou_ci_enrich_all')) {
            $enriched = self::enrich_all();
            echo '<div class="notice notice-success"><p>Company Intelligence index refreshed: ' . esc_html($enriched) . ' companies.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>TSEMOU Company Intelligence Engine</h1>
            <p>v2.7.0 canonical company identity and simple matching layer.</p>

            <form method="get" style="background:#fff;border:1px solid #dbe3ef;padding:16px;border-radius:14px;margin-bottom:20px;">
                <input type="hidden" name="page" value="tsemou-company-intelligence">
                <label><strong>Match company name / alias / ticker / domain</strong></label>
                <input type="text" name="company_query" value="<?php echo esc_attr($query); ?>" placeholder="Apple Inc, AAPL, apple.com..." style="width:360px;max-width:100%;">
                <label style="margin-left:12px;"><strong>Company ID</strong></label>
                <input type="number" name="company_id" value="<?php echo esc_attr($company_id ?: ''); ?>" style="width:120px;">
                <button class="button button-primary">Inspect</button>
            </form>

            <form method="post" style="margin-bottom:20px;">
                <?php wp_nonce_field('tsemou_ci_enrich_all'); ?>
                <input type="hidden" name="tsemou_ci_enrich_all" value="1">
                <button class="button">Refresh Company Intelligence Index</button>
            </form>

            <h2>Engine Status</h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th>Engine class</th><td>loaded</td></tr>
                    <tr><th>Companies indexed live</th><td><?php echo esc_html(count(self::company_index())); ?></td></tr>
                    <tr><th>Scope</th><td>canonical name, aliases, ticker, website/domain, simple matcher</td></tr>
                </tbody>
            </table>

            <?php if ($query): ?>
                <h2>Match Output</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($matches, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php endif; ?>

            <?php if ($identity): ?>
                <h2>Company Identity Output</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($identity, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php endif; ?>
        </div>
        <?php
    }
}
