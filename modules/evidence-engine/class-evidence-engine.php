<?php
namespace TSEMOU\Modules\EvidenceEngine;

if (!defined('ABSPATH')) exit;

if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('[TSEMOU_ACTIVATION_TRACE] require:start modules/evidence-engine/class-evidence-validator.php');
}
require_once __DIR__ . '/class-evidence-validator.php';
if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
    error_log('[TSEMOU_ACTIVATION_TRACE] require:ok modules/evidence-engine/class-evidence-validator.php');
}

class Evidence_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 20);
    }

    public function admin_menu() {
        add_submenu_page('tsemou-os', 'Evidence Engine', 'Evidence Engine', 'manage_options', 'tsemou-evidence-engine', [$this, 'render_admin_page']);
    }

    public static function evidence_post_types() {
        $types = [];
        foreach (['evidence', 'tsemou_proof'] as $type) {
            if (post_type_exists($type)) $types[] = $type;
        }
        return $types;
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

    public static function source_url($post_id) {
        $url = self::first_meta($post_id, [
            '_tsemou_source_url','tsemou_source_url','source_url','Source URL',
            'url','evidence_url','_source_url','link','external_url'
        ], '');
        if (is_array($url)) $url = reset($url);
        return esc_url_raw((string) $url);
    }

    public static function credibility($post_id) {
        $value = self::first_meta($post_id, [
            '_tsemou_credibility_score','tsemou_credibility_score','credibility_score',
            'credibility','source_credibility','_credibility_score','credibility_rating'
        ], '');

        if (is_string($value) && preg_match('/^field_[a-zA-Z0-9]+$/', $value)) $value = '';

        $raw = $value;
        $score = null;
        $label = 'Not rated';

        if ($value !== '' && $value !== null && is_numeric($value)) {
            $num = floatval($value);
            if ($num > 0 && $num <= 1) $num = $num * 10;
            elseif ($num > 10) $num = $num / 10;
            $score = round(max(0, min(10, $num)), 1);
            $label = number_format($score, 1) . '/10';
        } elseif (!empty($value)) {
            $label = sanitize_text_field((string) $value);
        }

        return ['raw'=>$raw, 'score'=>$score, 'label'=>$label, 'is_rated'=>$score !== null];
    }

    public static function summary($post_id, $words = 36) {
        $summary = self::first_meta($post_id, [
            '_tsemou_summary','tsemou_summary','summary','evidence_summary',
            'short_summary','description','_description','ai_summary'
        ], '');

        if (!empty($summary) && !is_array($summary)) {
            return wp_trim_words(wp_strip_all_tags((string) $summary), $words);
        }

        $post = get_post($post_id);
        if (!$post) return '';

        $content = trim(wp_strip_all_tags($post->post_content));
        $noise = ['Verified Company TSEMPORT','Clean TSEMPORT Renderer','Safe TSEMPORT Renderer','TSEMOU COMPANY REPORT','TSEMScore','TSEMIDENCE','TSEMIT open','Related Entities','Company Identity','Trust Breakdown'];

        $has_noise = false;
        foreach ($noise as $marker) {
            if (stripos($content, $marker) !== false) { $has_noise = true; break; }
        }

        if ($has_noise) {
            $sentences = preg_split('/(?<=[.!?])\s+/', $content);
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (strlen($sentence) < 25) continue;
                $bad = false;
                foreach ($noise as $marker) {
                    if (stripos($sentence, $marker) !== false) { $bad = true; break; }
                }
                if (!$bad) return wp_trim_words($sentence, $words);
            }
            return 'No clean evidence summary available yet.';
        }

        return wp_trim_words($content, $words);
    }

    public static function sentiment($post_id) {
        $value = strtolower(trim((string) self::first_meta($post_id, [
            '_tsemou_sentiment','tsemou_sentiment','sentiment','evidence_sentiment','signal','claim_direction'
        ], 'neutral')));

        if (strpos($value, 'positive') !== false || in_array($value, ['pos','good','benefit','support'], true)) {
            return ['value'=>'positive','label'=>'Positive','icon'=>'✅'];
        }
        if (strpos($value, 'negative') !== false || in_array($value, ['neg','bad','concern','harm'], true)) {
            return ['value'=>'negative','label'=>'Negative','icon'=>'⚠️'];
        }
        return ['value'=>'neutral','label'=>'Neutral','icon'=>'⚖️'];
    }

    public static function kind($post_id) {
        $value = self::first_meta($post_id, ['_tsemou_evidence_kind','tsemou_evidence_kind','evidence_kind','evidence_type','type','category'], 'uncategorized');
        $value = sanitize_text_field((string) $value);
        if ($value === '' || preg_match('/^field_[a-zA-Z0-9]+$/', $value)) $value = 'uncategorized';
        return ['value'=>sanitize_key($value), 'label'=>ucwords(str_replace(['_', '-'], ' ', $value))];
    }

    public static function status($post_id) {
        $value = self::first_meta($post_id, ['_tsemou_evidence_status','tsemou_evidence_status','evidence_status','status','verification_status'], get_post_status($post_id));
        $value = sanitize_text_field((string) $value);
        if ($value === '') $value = get_post_status($post_id);
        return ['value'=>sanitize_key($value), 'label'=>ucwords(str_replace(['_', '-'], ' ', $value))];
    }

    public static function evidence_date($post_id) {
        $date = self::first_meta($post_id, ['_tsemou_event_date','tsemou_event_date','evidence_date','date','published_date','source_date'], '');
        if (!empty($date) && !is_array($date)) {
            $ts = strtotime((string) $date);
            if ($ts) return ['raw'=>(string)$date, 'display'=>date_i18n('M j, Y', $ts), 'iso'=>date('Y-m-d', $ts)];
        }
        return ['raw'=>get_the_date('Y-m-d H:i:s', $post_id), 'display'=>get_the_date('M j, Y', $post_id), 'iso'=>get_the_date('Y-m-d', $post_id)];
    }

    public static function related_company_ids($post_id) {
        $ids = [];
        $keys = ['_tsemou_company_id','_tsemou_entity_id','_tsemou_evidence_company','tsemou_evidence_company','related_company','company','companies'];
        foreach ($keys as $key) {
            $values = get_post_meta($post_id, $key, false);
            foreach ($values as $value) {
                if (is_array($value)) {
                    $flat = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($value));
                    foreach ($flat as $v) if (is_numeric($v) && get_post_type(absint($v)) === 'company') $ids[] = absint($v);
                } else {
                    $value = (string) $value;
                    if (is_numeric($value) && get_post_type(absint($value)) === 'company') $ids[] = absint($value);
                    elseif (preg_match_all('/\d+/', $value, $m)) {
                        foreach ($m[0] as $possible) if (get_post_type(absint($possible)) === 'company') $ids[] = absint($possible);
                    }
                }
            }
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public static function validate($post_id, $normalized = null) {
        if (!$normalized) $normalized = self::get($post_id);
        $warnings = [];
        if (empty($normalized['source']['url'])) $warnings[] = 'missing_source_url';
        if (empty($normalized['relations']['company_ids'])) $warnings[] = 'missing_company_relation';
        if (empty($normalized['credibility']['is_rated'])) $warnings[] = 'missing_credibility';
        if (empty($normalized['summary']) || $normalized['summary'] === 'No clean evidence summary available yet.') $warnings[] = 'missing_clean_summary';
        return $warnings;
    }

    public static function find_evidence_by_source_post($post_id) {
        $post_id = absint($post_id);
        if ($post_id <= 0) return 0;

        $linked = absint(get_post_meta($post_id, '_tsemou_evidence_id', true));
        if ($linked > 0 && in_array(get_post_type($linked), self::evidence_post_types(), true)) {
            return $linked;
        }

        $types = self::evidence_post_types();
        if (empty($types)) return 0;

        $q = get_posts([
            'post_type' => $types,
            'post_status' => ['publish','draft','pending','private'],
            'posts_per_page' => 1,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => [
                'relation' => 'OR',
                ['key' => '_tsemou_source_post_id', 'value' => $post_id, 'compare' => '='],
                ['key' => 'source_post_id', 'value' => $post_id, 'compare' => '='],
            ],
        ]);

        if (!empty($q)) {
            $evidence_id = absint($q[0]->ID);
            if ($evidence_id > 0) {
                update_post_meta($post_id, '_tsemou_evidence_id', $evidence_id);
                return $evidence_id;
            }
        }

        return 0;
    }

    public static function resolve_post_or_evidence_id($input_id, $auto_create = true) {
        $input_id = absint($input_id);
        if ($input_id <= 0) {
            return ['success' => false, 'code' => 'invalid_post', 'message' => 'Invalid ID.', 'input_id' => 0, 'input_type' => '', 'evidence_id' => 0, 'status' => 'failed', 'created' => false];
        }

        $post = get_post($input_id);
        if (!$post) {
            return ['success' => false, 'code' => 'invalid_post', 'message' => 'Post does not exist.', 'input_id' => $input_id, 'input_type' => '', 'evidence_id' => 0, 'status' => 'failed', 'created' => false];
        }

        $input_type = sanitize_key($post->post_type);

        if (in_array($input_type, self::evidence_post_types(), true)) {
            return [
                'success' => true,
                'code' => 'valid_evidence',
                'message' => 'Input already refers to an evidence record.',
                'input_id' => $input_id,
                'input_type' => $input_type,
                'evidence_id' => $input_id,
                'status' => 'valid',
                'created' => false,
                'resolution_meta' => ['direct_evidence_id'],
            ];
        }

        if ($input_type !== 'post') {
            return ['success' => false, 'code' => 'invalid_post', 'message' => 'ID is not an Evidence ID and not a WordPress article post.', 'input_id' => $input_id, 'input_type' => $input_type, 'evidence_id' => 0, 'status' => 'failed', 'created' => false];
        }

        $existing = self::find_evidence_by_source_post($input_id);
        if ($existing > 0) {
            return [
                'success' => true,
                'code' => 'resolved_existing',
                'message' => 'Existing evidence linked to article was found.',
                'input_id' => $input_id,
                'input_type' => $input_type,
                'evidence_id' => $existing,
                'status' => 'valid',
                'created' => false,
                'resolution_meta' => ['_tsemou_evidence_id', '_tsemou_source_post_id', 'source_post_id'],
            ];
        }

        if (!$auto_create) {
            return ['success' => false, 'code' => 'no_linked_evidence', 'message' => 'No Evidence linked to this article.', 'input_id' => $input_id, 'input_type' => $input_type, 'evidence_id' => 0, 'status' => 'failed', 'created' => false];
        }

        $types = self::evidence_post_types();
        $target_type = in_array('evidence', $types, true) ? 'evidence' : (in_array('tsemou_proof', $types, true) ? 'tsemou_proof' : '');
        if ($target_type === '') {
            return ['success' => false, 'code' => 'evidence_creation_failed', 'message' => 'No evidence post type is available for creation.', 'input_id' => $input_id, 'input_type' => $input_type, 'evidence_id' => 0, 'status' => 'failed', 'created' => false];
        }

        $new_id = wp_insert_post([
            'post_type' => $target_type,
            'post_status' => 'draft',
            'post_title' => get_the_title($input_id),
            'post_content' => get_post_field('post_content', $input_id),
        ], true);

        if (is_wp_error($new_id) || !$new_id) {
            return ['success' => false, 'code' => 'evidence_creation_failed', 'message' => 'Evidence creation failed from article post.', 'input_id' => $input_id, 'input_type' => $input_type, 'evidence_id' => 0, 'status' => 'failed', 'created' => false];
        }

        $new_id = absint($new_id);
        update_post_meta($new_id, '_tsemou_source_post_id', $input_id);
        update_post_meta($new_id, 'source_post_id', $input_id);
        update_post_meta($new_id, '_tsemou_source_url', get_permalink($input_id));
        update_post_meta($input_id, '_tsemou_evidence_id', $new_id);

        return [
            'success' => true,
            'code' => 'resolved_created',
            'message' => 'Evidence created from article post and linked.',
            'input_id' => $input_id,
            'input_type' => $input_type,
            'evidence_id' => $new_id,
            'status' => 'created',
            'created' => true,
            'resolution_meta' => ['_tsemou_evidence_id', '_tsemou_source_post_id', 'source_post_id'],
        ];
    }



    public static function credibility_with_source($evidence_id, $source_url = '') {
        $cred = self::credibility($evidence_id);

        if (!empty($cred['is_rated'])) {
            return $cred;
        }

        if ($source_url && class_exists('\TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine')) {
            $source = \TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine::analyze($source_url);
            if (is_numeric($source['credibility'] ?? null)) {
                $score = round(floatval($source['credibility']), 1);
                return [
                    'raw' => '',
                    'score' => $score,
                    'label' => number_format($score, 1) . '/10',
                    'is_rated' => true,
                    'source' => 'source_intelligence_baseline',
                ];
            }
        }

        return $cred;
    }


    public static function source_payload($evidence_id, $source_url = '') {
        $source_url = $source_url ?: self::source_url($evidence_id);
        $fallback_label = $source_url ? parse_url($source_url, PHP_URL_HOST) : 'Source not set';

        $payload = [
            'url' => $source_url,
            'label' => $fallback_label,
            'type' => self::first_meta($evidence_id, ['source_type','_tsemou_source_type'], ''),
            'intelligence' => null,
        ];

        if ($source_url && class_exists('\TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine')) {
            $intel = \TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine::analyze($source_url);
            $payload['label'] = $intel['publisher'] ?? $fallback_label;
            $payload['type'] = $payload['type'] ?: ($intel['type'] ?? '');
            $payload['intelligence'] = $intel;
        }

        if ($source_url && class_exists('\TSEMOU\Modules\SourceObject\Source_Object_Engine')) {
            $source = \TSEMOU\Modules\SourceObject\Source_Object_Engine::source_from_evidence($evidence_id, true);
            if ($source) {
                $payload['source_id'] = $source['source_id'];
                $payload['source_object'] = $source;
                $payload['label'] = $source['publisher'] ?: $payload['label'];
                $payload['type'] = $payload['type'] ?: ($source['type'] ?? '');
            }
        }

        return $payload;
    }


    public static function get($evidence_id) {
        $evidence_id = absint($evidence_id);
        $post = get_post($evidence_id);
        if (!$post || !in_array($post->post_type, self::evidence_post_types(), true)) return null;

        $source_url = self::source_url($evidence_id);
        $normalized = [
            'id' => $evidence_id,
            'post_type' => $post->post_type,
            'title' => get_the_title($evidence_id),
            'permalink' => get_permalink($evidence_id),
            'summary' => self::summary($evidence_id),
            'source' => self::source_payload($evidence_id, $source_url),
            'credibility' => self::credibility_with_source($evidence_id, $source_url),
            'sentiment' => self::sentiment($evidence_id),
            'kind' => self::kind($evidence_id),
            'status' => self::status($evidence_id),
            'date' => self::evidence_date($evidence_id),
            'relations' => ['company_ids' => self::related_company_ids($evidence_id)],
            'raw_status' => get_post_status($evidence_id),
        ];
        $normalized['warnings'] = self::validate($evidence_id, $normalized);
        return $normalized;
    }

    public static function get_for_company($company_id, $limit = 6, $args = []) {
        $company_id = absint($company_id);
        if (!$company_id) return [];
        $types = self::evidence_post_types();
        if (empty($types)) return [];

        $query_args = [
            'post_type' => $types,
            'post_status' => $args['post_status'] ?? ['publish','draft','pending'],
            'posts_per_page' => intval($limit),
            'meta_query' => [
                'relation' => 'OR',
                ['key'=>'_tsemou_company_id','value'=>$company_id,'compare'=>'='],
                ['key'=>'_tsemou_entity_id','value'=>$company_id,'compare'=>'='],
                ['key'=>'_tsemou_evidence_company_ids','value'=>'"' . $company_id . '"','compare'=>'LIKE'],
                ['key'=>'_tsemou_evidence_company_ids','value'=>'i:' . $company_id . ';','compare'=>'LIKE'],
                ['key'=>'_tsemou_evidence_company','value'=>$company_id,'compare'=>'='],
                ['key'=>'tsemou_evidence_company','value'=>$company_id,'compare'=>'='],
                ['key'=>'related_company','value'=>$company_id,'compare'=>'='],
                ['key'=>'company','value'=>$company_id,'compare'=>'='],
                ['key'=>'company','value'=>'"' . $company_id . '"','compare'=>'LIKE'],
                ['key'=>'company','value'=>'i:' . $company_id . ';','compare'=>'LIKE'],
            ],
            'orderby' => 'modified',
            'order' => 'DESC',
        ];

        $posts = get_posts($query_args);

        if (empty($posts)) {
            $scan = get_posts(['post_type'=>$types, 'post_status'=>$query_args['post_status'], 'numberposts'=>120, 'orderby'=>'modified', 'order'=>'DESC']);
            foreach ($scan as $candidate) {
                if (in_array($company_id, self::related_company_ids($candidate->ID), true)) $posts[] = $candidate;
                if (count($posts) >= intval($limit)) break;
            }
        }

        $out = [];
        foreach ($posts as $post) {
            $normalized = self::get($post->ID);
            if ($normalized) $out[] = $normalized;
        }
        return array_slice($out, 0, intval($limit));
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        $evidence_id = !empty($_GET['evidence_id']) ? absint($_GET['evidence_id']) : 0;
        $company_id = !empty($_GET['company_id']) ? absint($_GET['company_id']) : 0;
        $resolution = $evidence_id ? self::resolve_post_or_evidence_id($evidence_id, true) : null;
        ?>
        <div class="wrap">
            <h1>TSEMOU Evidence Engine</h1>
            <p>v2.8.0 normalization + validation + source object layer for evidence metadata, relationships and validation.</p>
            <form method="get" style="background:#fff;border:1px solid #dbe3ef;padding:16px;border-radius:14px;margin-bottom:20px;">
                <input type="hidden" name="page" value="tsemou-evidence-engine">
                <label><strong>Evidence ID or Post ID</strong></label>
                <input type="number" name="evidence_id" value="<?php echo esc_attr($evidence_id ?: ''); ?>" style="width:180px;">
                <label style="margin-left:12px;"><strong>Company ID</strong></label>
                <input type="number" name="company_id" value="<?php echo esc_attr($company_id ?: ''); ?>" style="width:140px;">
                <button class="button button-primary">Inspect</button>
            </form>
            <h2>Engine Status</h2>
            <table class="widefat striped"><tbody>
                <tr><th>Evidence post types</th><td><?php echo esc_html(implode(', ', self::evidence_post_types()) ?: 'none'); ?></td></tr>
                <tr><th>Engine class</th><td>loaded</td></tr>
            </tbody></table>
            <?php if ($evidence_id): ?>
                <h2>Post to Evidence Resolution</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($resolution, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                <?php if (!empty($resolution['code']) && $resolution['code'] === 'no_linked_evidence'): ?>
                    <p><strong>No Evidence linked to this article.</strong></p>
                <?php endif; ?>
                <h2>Evidence Validation Output</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(Evidence_Validator::validate($evidence_id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                <h2>Evidence Normalized Output</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(!empty($resolution['evidence_id']) ? self::get($resolution['evidence_id']) : null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php endif; ?>
            <?php if ($company_id): ?>
                <h2>Company Evidence Validation Output</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(Evidence_Validator::validate_for_company($company_id, 20), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                <h2>Company Evidence Output</h2>
                <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(self::get_for_company($company_id, 20), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php endif; ?>
        </div>
        <?php
    }
}
