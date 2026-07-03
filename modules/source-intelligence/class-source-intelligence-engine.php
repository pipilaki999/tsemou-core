<?php
namespace TSEMOU\Modules\SourceIntelligence;

if (!defined('ABSPATH')) exit;

/**
 * TSEMOU Source Intelligence Engine v2.5.0
 *
 * Normalizes and scores sources/domains before Discovery/Scraper automation.
 */
class Source_Intelligence_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 25);
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Source Intelligence',
            'Source Intelligence',
            'manage_options',
            'tsemou-source-intelligence',
            [$this, 'render_admin_page']
        );
    }

    public static function normalize_domain($input) {
        $input = trim((string) $input);
        if ($input === '') return '';

        if (strpos($input, '://') === false && strpos($input, '.') !== false) {
            $input = 'https://' . $input;
        }

        $host = parse_url($input, PHP_URL_HOST);
        if (!$host) $host = $input;

        $host = strtolower(trim($host));
        $host = preg_replace('/^www\./', '', $host);
        $host = preg_replace('/:\d+$/', '', $host);

        return sanitize_text_field($host);
    }

    public static function registry() {
        return [
            'reuters.com' => [
                'publisher' => 'Reuters',
                'type' => 'News Agency',
                'country' => 'United Kingdom',
                'credibility' => 9.6,
                'verified' => true,
                'bias' => 'low',
                'class' => 'high_authority',
            ],
            'apnews.com' => [
                'publisher' => 'Associated Press',
                'type' => 'News Agency',
                'country' => 'United States',
                'credibility' => 9.5,
                'verified' => true,
                'bias' => 'low',
                'class' => 'high_authority',
            ],
            'bbc.com' => [
                'publisher' => 'BBC',
                'type' => 'Public Broadcaster',
                'country' => 'United Kingdom',
                'credibility' => 9.1,
                'verified' => true,
                'bias' => 'low',
                'class' => 'high_authority',
            ],
            'bbc.co.uk' => [
                'publisher' => 'BBC',
                'type' => 'Public Broadcaster',
                'country' => 'United Kingdom',
                'credibility' => 9.1,
                'verified' => true,
                'bias' => 'low',
                'class' => 'high_authority',
            ],
            'who.int' => [
                'publisher' => 'World Health Organization',
                'type' => 'International Organization',
                'country' => 'International',
                'credibility' => 9.8,
                'verified' => true,
                'bias' => 'institutional',
                'class' => 'institutional',
            ],
            'un.org' => [
                'publisher' => 'United Nations',
                'type' => 'International Organization',
                'country' => 'International',
                'credibility' => 9.4,
                'verified' => true,
                'bias' => 'institutional',
                'class' => 'institutional',
            ],
            'worldbank.org' => [
                'publisher' => 'World Bank',
                'type' => 'International Organization',
                'country' => 'International',
                'credibility' => 9.2,
                'verified' => true,
                'bias' => 'institutional',
                'class' => 'institutional',
            ],
            'sec.gov' => [
                'publisher' => 'U.S. Securities and Exchange Commission',
                'type' => 'Government Regulator',
                'country' => 'United States',
                'credibility' => 9.7,
                'verified' => true,
                'bias' => 'official',
                'class' => 'government',
            ],
            'fda.gov' => [
                'publisher' => 'U.S. Food and Drug Administration',
                'type' => 'Government Regulator',
                'country' => 'United States',
                'credibility' => 9.6,
                'verified' => true,
                'bias' => 'official',
                'class' => 'government',
            ],
            'europa.eu' => [
                'publisher' => 'European Union',
                'type' => 'Government / Intergovernmental',
                'country' => 'European Union',
                'credibility' => 9.4,
                'verified' => true,
                'bias' => 'official',
                'class' => 'government',
            ],
            'ft.com' => [
                'publisher' => 'Financial Times',
                'type' => 'News Publisher',
                'country' => 'United Kingdom',
                'credibility' => 8.8,
                'verified' => true,
                'bias' => 'business',
                'class' => 'major_media',
            ],
            'nytimes.com' => [
                'publisher' => 'The New York Times',
                'type' => 'News Publisher',
                'country' => 'United States',
                'credibility' => 8.5,
                'verified' => true,
                'bias' => 'editorial',
                'class' => 'major_media',
            ],
            'theguardian.com' => [
                'publisher' => 'The Guardian',
                'type' => 'News Publisher',
                'country' => 'United Kingdom',
                'credibility' => 8.2,
                'verified' => true,
                'bias' => 'editorial',
                'class' => 'major_media',
            ],
            'example.com' => [
                'publisher' => 'Example Domain',
                'type' => 'Test Source',
                'country' => 'Test',
                'credibility' => null,
                'verified' => false,
                'bias' => 'unknown',
                'class' => 'test',
            ],
        ];
    }

    public static function infer_from_tld($domain) {
        $domain = strtolower((string) $domain);

        if (preg_match('/\.gov$/', $domain)) {
            return [
                'type' => 'Government',
                'country' => 'Unknown',
                'credibility' => 8.5,
                'verified' => true,
                'bias' => 'official',
                'class' => 'government',
            ];
        }

        if (preg_match('/\.edu$/', $domain)) {
            return [
                'type' => 'Academic',
                'country' => 'Unknown',
                'credibility' => 8.0,
                'verified' => true,
                'bias' => 'academic',
                'class' => 'academic',
            ];
        }

        if (preg_match('/\.int$/', $domain)) {
            return [
                'type' => 'International Organization',
                'country' => 'International',
                'credibility' => 8.5,
                'verified' => true,
                'bias' => 'institutional',
                'class' => 'institutional',
            ];
        }

        if (preg_match('/(medium|substack|blogspot|wordpress)\./', $domain)) {
            return [
                'type' => 'Independent Publishing',
                'country' => 'Unknown',
                'credibility' => 4.5,
                'verified' => false,
                'bias' => 'unknown',
                'class' => 'independent_blog',
            ];
        }

        return [
            'type' => 'Unknown Source',
            'country' => 'Unknown',
            'credibility' => null,
            'verified' => false,
            'bias' => 'unknown',
            'class' => 'unknown',
        ];
    }

    public static function analyze($input) {
        $domain = self::normalize_domain($input);
        $registry = self::registry();

        $known = $registry[$domain] ?? null;

        // Try parent domain match for paths/subdomains.
        if (!$known) {
            foreach ($registry as $known_domain => $data) {
                if ($domain === $known_domain || substr($domain, -strlen('.' . $known_domain)) === '.' . $known_domain) {
                    $known = $data;
                    $domain = $known_domain;
                    break;
                }
            }
        }

        if ($known) {
            $result = array_merge([
                'domain' => $domain,
                'publisher' => $known['publisher'] ?? ucwords(str_replace('.', ' ', $domain)),
                'known' => true,
            ], $known);
        } else {
            $inferred = self::infer_from_tld($domain);
            $result = array_merge([
                'domain' => $domain,
                'publisher' => ucwords(str_replace(['-', '.'], [' ', ' '], $domain)),
                'known' => false,
            ], $inferred);
        }

        $result['credibility_label'] = is_numeric($result['credibility'] ?? null)
            ? number_format(floatval($result['credibility']), 1) . '/10'
            : 'Not rated';

        $result['risk'] = self::risk_level($result);
        $result['warnings'] = self::warnings($result);

        return $result;
    }

    public static function risk_level($source) {
        if (!empty($source['verified']) && is_numeric($source['credibility']) && $source['credibility'] >= 8) {
            return 'low';
        }

        if (($source['class'] ?? '') === 'unknown') return 'unknown';
        if (($source['class'] ?? '') === 'independent_blog') return 'medium';

        return 'medium';
    }

    public static function warnings($source) {
        $warnings = [];

        if (empty($source['domain'])) $warnings[] = 'missing_domain';
        if (empty($source['known'])) $warnings[] = 'unknown_source';
        if (!is_numeric($source['credibility'] ?? null)) $warnings[] = 'missing_source_credibility';
        if (empty($source['verified'])) $warnings[] = 'unverified_source';

        return $warnings;
    }

    public static function credibility_for_url($url) {
        $source = self::analyze($url);
        return $source['credibility'] ?? null;
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        $source = isset($_GET['source_url']) ? sanitize_text_field($_GET['source_url']) : '';
        $result = $source ? self::analyze($source) : null;
        ?>
        <div class="wrap">
            <h1>TSEMOU Source Intelligence Engine</h1>
            <p>v2.5.0 source/domain intelligence layer for Evidence, Discovery and Scraper pipelines.</p>

            <form method="get" style="background:#fff;border:1px solid #dbe3ef;padding:16px;border-radius:14px;margin-bottom:20px;">
                <input type="hidden" name="page" value="tsemou-source-intelligence">
                <label><strong>Source URL or Domain</strong></label>
                <input type="text" name="source_url" value="<?php echo esc_attr($source); ?>" placeholder="https://www.reuters.com/..." style="width:420px;max-width:100%;">
                <button class="button button-primary">Analyze Source</button>
            </form>

            <h2>Engine Status</h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th>Engine class</th><td>loaded</td></tr>
                    <tr><th>Known sources</th><td><?php echo esc_html(count(self::registry())); ?></td></tr>
                </tbody>
            </table>

            <?php if ($result): ?>
                <h2>Source Intelligence Output</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php endif; ?>
        </div>
        <?php
    }
}
