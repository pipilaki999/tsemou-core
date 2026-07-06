<?php
namespace TSEMOU\Modules\CompanyEngine;
if (!defined('ABSPATH')) exit;

class Company_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', [$this, 'add_company_meta_boxes']);
        add_action('save_post_company', [$this, 'save_company_meta'], 10, 2);
        add_action('tsemou_public_pages_update_requested', [$this, 'handle_public_pages_update_requested'], 10, 3);
        add_shortcode('tsemou_company_hero', [$this, 'shortcode_company_hero']);
        add_shortcode('tsemou_company_stats', [$this, 'shortcode_company_stats']);
        add_shortcode('tsemou_company_summary', [$this, 'shortcode_company_summary']);
        add_shortcode('tsemou_company_timeline', [$this, 'shortcode_company_timeline']);
        add_shortcode('tsemou_company_related', [$this, 'shortcode_company_related']);
        add_shortcode('tsemou_company_evidence_feed', [$this, 'shortcode_company_evidence_feed']);
        add_shortcode('tsemou_company_overview', [$this, 'shortcode_company_overview']);
        add_shortcode('tsemou_company_page', [$this, 'shortcode_company_page']);
        add_filter('the_content', [$this, 'render_company_timeline_frontend'], 30);
        add_filter('the_content', [$this, 'render_enhanced_related_articles_frontend'], 25);
        add_shortcode('tsemou_related_articles_pro', [$this, 'shortcode_enhanced_related_articles']);
        add_action('wp_footer', [$this, 'render_related_articles_footer_safe'], 20);
        add_action('template_redirect', [$this, 'start_company_template_buffer'], 1);
    }

    public function handle_public_pages_update_requested($story_id, $company_ids = [], $payload = []) {
        $story_id = absint($story_id);
        if ($story_id <= 0) return;

        if (!is_array($company_ids)) $company_ids = [];
        $company_ids = array_values(array_unique(array_filter(array_map('absint', $company_ids))));
        $updated_at = current_time('mysql');

        foreach ($company_ids as $company_id) {
            update_post_meta($company_id, '_tsemou_public_page_last_refresh', $updated_at);
            update_post_meta($company_id, '_tsemou_public_page_last_story', $story_id);
        }

        do_action('tsemou_public_pages_updated', [
            'story_id' => $story_id,
            'company_ids' => $company_ids,
            'updated_at' => $updated_at,
            'context' => is_array($payload) ? $payload : [],
        ]);
    }

    public static function get_existing_companies() {
        return get_posts([
            'post_type' => 'company',
            'numberposts' => 300,
            'post_status' => ['publish','draft','pending'],
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
    }

    public static function get_connected_company_ids($file_id) {
        $ids = get_post_meta($file_id, '_tsemou_connected_companies', true);
        if (!is_array($ids)) return [];
        return array_values(array_filter(array_map('intval', $ids)));
    }

    public static function get_connected_companies($file_id) {
        $ids = self::get_connected_company_ids($file_id);
        if (!$ids) return [];
        return get_posts([
            'post_type' => 'company',
            'post__in' => $ids,
            'numberposts' => -1,
            'orderby' => 'post__in',
            'post_status' => ['publish','draft','pending']
        ]);
    }

    public static function save_connected_companies($file_id, $company_ids) {
        if (!is_array($company_ids)) $company_ids = [];
        $company_ids = array_values(array_unique(array_filter(array_map('intval', $company_ids))));
        update_post_meta($file_id, '_tsemou_connected_companies', $company_ids);
    }

    public static function count_files_for_company($company_id) {
        $files = get_posts([
            'post_type' => 'story',
            'numberposts' => -1,
            'post_status' => ['publish','draft','pending'],
            'meta_query' => [
                [
                    'key' => '_tsemou_connected_companies',
                    'value' => '"' . intval($company_id) . '"',
                    'compare' => 'LIKE'
                ]
            ],
            'fields' => 'ids'
        ]);
        return count($files);
    }
    public static function get_company_relationships($file_id) {
        $rels = get_post_meta($file_id, '_tsemou_company_relationships', true);
        return is_array($rels) ? $rels : [];
    }

    public static function save_company_relationships($file_id, $relationships) {
        $clean = [];
        if (is_array($relationships)) {
            foreach ($relationships as $company_id => $data) {
                $company_id = intval($company_id);
                if (!$company_id) continue;
                $clean[$company_id] = [
                    'role' => isset($data['role']) ? sanitize_text_field($data['role']) : 'related',
                    'reason' => isset($data['reason']) ? sanitize_textarea_field($data['reason']) : '',
                    'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'under_watch',
                ];
            }
        }
        update_post_meta($file_id, '_tsemou_company_relationships', $clean);
    }

    public function add_company_meta_boxes() {
        add_meta_box(
            'tsemou_company_intelligence',
            'TSEMOU Company Intelligence',
            [$this, 'render_company_intelligence'],
            'company',
            'normal',
            'high'
        );
    }

    public function render_company_intelligence($post) {
        wp_nonce_field('tsemou_save_company_meta', 'tsemou_company_nonce');

        $status = get_post_meta($post->ID, '_tsemou_company_status', true) ?: 'under_watch';
        $score = get_post_meta($post->ID, '_tsemou_company_score', true);
        $last_review = get_post_meta($post->ID, '_tsemou_company_last_review', true);
        $notes = get_post_meta($post->ID, '_tsemou_company_notes', true);
        $connected_files = self::get_files_for_company($post->ID);
        $evidence_total = get_post_meta($post->ID, '_tsemou_evidence_total', true);
        $evidence_positive = get_post_meta($post->ID, '_tsemou_evidence_positive', true);
        $evidence_negative = get_post_meta($post->ID, '_tsemou_evidence_negative', true);
        $evidence_neutral = get_post_meta($post->ID, '_tsemou_evidence_neutral', true);
        $evidence_high = get_post_meta($post->ID, '_tsemou_evidence_high_credibility', true);
        $evidence_avg = get_post_meta($post->ID, '_tsemou_evidence_avg_credibility', true);
        $evidence_last_sync = get_post_meta($post->ID, '_tsemou_evidence_last_sync', true);
        $timeline_events = self::get_company_timeline_events($post->ID);
        ?>
        <div class="tsemou-company-intel">
            <div class="tsemou-company-intel-header">
                <div>
                    <span>TSEMOU COMPANY</span>
                    <strong><?php echo esc_html(get_the_title($post)); ?></strong>
                </div>
                <div class="tsemou-company-score">
                    <label>Score</label>
                    <input type="number" name="tsemou_company_score" value="<?php echo esc_attr($score); ?>" min="0" max="100" placeholder="0-100">
                </div>
            </div>

            <div class="tsemou-company-intel-grid">
                <label>Status
                    <select name="tsemou_company_status">
                        <option value="planet_ally" <?php selected($status, 'planet_ally'); ?>>🟢 Planet Ally</option>
                        <option value="better_company" <?php selected($status, 'better_company'); ?>>🟢 Better Company</option>
                        <option value="under_watch" <?php selected($status, 'under_watch'); ?>>🟡 Under Watch</option>
                        <option value="needs_change" <?php selected($status, 'needs_change'); ?>>🟠 Needs Change</option>
                        <option value="red_flag" <?php selected($status, 'red_flag'); ?>>🔴 Red Flag</option>
                        <option value="toxic" <?php selected($status, 'toxic'); ?>>🔴 Toxic</option>
                        <option value="blacklisted" <?php selected($status, 'blacklisted'); ?>>⚫ Blacklisted</option>
                    </select>
                </label>

                <label>Last Review
                    <input type="date" name="tsemou_company_last_review" value="<?php echo esc_attr($last_review); ?>">
                </label>

                <label>Connected Files
                    <input type="text" value="<?php echo esc_attr(count($connected_files)); ?>" readonly>
                </label>
            </div>

            <label>Internal Notes
                <textarea name="tsemou_company_notes" rows="4" placeholder="Why does this company have this status?"><?php echo esc_textarea($notes); ?></textarea>
            </label>


            <h3>Evidence Engine Counters</h3>
            <div class="tsemou-evidence-counter-panel">
                <div>
                    <strong><?php echo esc_html($evidence_total !== '' ? $evidence_total : 0); ?></strong>
                    <span>Total Evidence</span>
                </div>
                <div>
                    <strong><?php echo esc_html($evidence_positive !== '' ? $evidence_positive : 0); ?></strong>
                    <span>Positive</span>
                </div>
                <div>
                    <strong><?php echo esc_html($evidence_negative !== '' ? $evidence_negative : 0); ?></strong>
                    <span>Negative</span>
                </div>
                <div>
                    <strong><?php echo esc_html($evidence_neutral !== '' ? $evidence_neutral : 0); ?></strong>
                    <span>Neutral</span>
                </div>
                <div>
                    <strong><?php echo esc_html($evidence_high !== '' ? $evidence_high : 0); ?></strong>
                    <span>High Credibility</span>
                </div>
                <div>
                    <strong><?php echo esc_html($evidence_avg !== '' ? $evidence_avg : 0); ?></strong>
                    <span>Avg Credibility</span>
                </div>
            </div>
            <p class="tsemou-evidence-sync-note">
                Last Evidence Sync:
                <strong><?php echo esc_html($evidence_last_sync ?: 'Not synced yet'); ?></strong>
            </p>

            <h3>Connected TSEMOU Files</h3>
            <?php if ($connected_files): ?>
                <div class="tsemou-connected-files">
                    <?php foreach ($connected_files as $file):
                        $call_sign = get_post_meta($file->ID, '_tsemou_call_sign', true) ?: 'NO-CALLSIGN';
                        $file_status = get_post_meta($file->ID, '_tsemou_status', true) ?: 'active';
                        $relationships = self::get_company_relationships($file->ID);
                        $rel = $relationships[$post->ID] ?? ['role' => 'related', 'status' => 'under_watch', 'reason' => ''];
                    ?>
                        <div class="tsemou-connected-file-card">
                            <strong><?php echo esc_html($call_sign); ?></strong>
                            <span><?php echo esc_html($file->post_title); ?></span>
                            <em>Role: <?php echo esc_html($rel['role']); ?> • File: <?php echo esc_html($file_status); ?></em>
                            <?php if (!empty($rel['reason'])): ?>
                                <p><?php echo esc_html($rel['reason']); ?></p>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(get_edit_post_link($file->ID)); ?>">Open File</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="tsemou-module-placeholder">No connected TSEMOU Files yet.</div>
            <?php endif; ?>

            <h3>Company Timeline</h3>

            <div class="tsemou-timeline-add-box">
                <label>Add Timeline Event
                    <input type="text" name="tsemou_company_timeline_title" placeholder="Example: New investigation added">
                </label>
                <label>Event Note
                    <textarea name="tsemou_company_timeline_note" rows="2" placeholder="Short note. Optional."></textarea>
                </label>
            </div>

            <?php echo self::render_company_timeline_markup($post->ID, true); ?>

        </div>
        <?php
    }

    public function save_company_meta($post_id, $post) {
        $old_status_for_timeline = get_post_meta($post_id, '_tsemou_company_status', true);
        if (!isset($_POST['tsemou_company_nonce']) || !wp_verify_nonce($_POST['tsemou_company_nonce'], 'tsemou_save_company_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = [
            '_tsemou_company_status' => 'tsemou_company_status',
            '_tsemou_company_score' => 'tsemou_company_score',
            '_tsemou_company_last_review' => 'tsemou_company_last_review',
            '_tsemou_company_notes' => 'tsemou_company_notes',
        ];

        foreach ($fields as $meta_key => $post_key) {
            if (isset($_POST[$post_key])) {
                update_post_meta($post_id, $meta_key, sanitize_textarea_field($_POST[$post_key]));
            }
        }

        $new_status_for_timeline = get_post_meta($post_id, '_tsemou_company_status', true);
        if ($old_status_for_timeline && $new_status_for_timeline && $old_status_for_timeline !== $new_status_for_timeline) {
            self::add_company_timeline_event(
                $post_id,
                'Status changed',
                'Status changed from ' . $old_status_for_timeline . ' to ' . $new_status_for_timeline
            );
        }

        if (!empty($_POST['tsemou_company_timeline_title'])) {
            self::add_company_timeline_event(
                $post_id,
                sanitize_text_field($_POST['tsemou_company_timeline_title']),
                !empty($_POST['tsemou_company_timeline_note']) ? sanitize_textarea_field($_POST['tsemou_company_timeline_note']) : ''
            );
        }
    }

    public static function get_files_for_company($company_id) {
        return get_posts([
            'post_type' => 'story',
            'numberposts' => -1,
            'post_status' => ['publish','draft','pending'],
            'meta_query' => [
                [
                    'key' => '_tsemou_connected_companies',
                    'value' => '"' . intval($company_id) . '"',
                    'compare' => 'LIKE'
                ]
            ]
        ]);
    }

    public static function add_company_timeline_event($company_id, $title, $note = '') {
        $events = get_post_meta($company_id, '_tsemou_company_manual_timeline', true);
        if (!is_array($events)) $events = [];

        $events[] = [
            'date' => current_time('mysql'),
            'title' => sanitize_text_field($title),
            'note' => sanitize_textarea_field($note),
            'type' => 'manual'
        ];

        update_post_meta($company_id, '_tsemou_company_manual_timeline', $events);
    }

    public static function get_company_timeline_events($company_id) {
        $company_id = intval($company_id);
        $events = [];

        $company = get_post($company_id);
        if ($company) {
            $events[] = [
                'date' => $company->post_date,
                'title' => 'Company profile created',
                'note' => get_the_title($company_id),
                'type' => 'company'
            ];

            if ($company->post_modified && $company->post_modified !== $company->post_date) {
                $events[] = [
                    'date' => $company->post_modified,
                    'title' => 'Company profile updated',
                    'note' => get_the_title($company_id),
                    'type' => 'update'
                ];
            }
        }

        $manual = get_post_meta($company_id, '_tsemou_company_manual_timeline', true);
        if (is_array($manual)) {
            foreach ($manual as $event) {
                if (empty($event['title'])) continue;
                $events[] = [
                    'date' => !empty($event['date']) ? $event['date'] : current_time('mysql'),
                    'title' => $event['title'],
                    'note' => !empty($event['note']) ? $event['note'] : '',
                    'type' => !empty($event['type']) ? $event['type'] : 'manual'
                ];
            }
        }

        $files = self::get_files_for_company($company_id);
        foreach ($files as $file) {
            $events[] = [
                'date' => $file->post_date,
                'title' => 'Connected TSEMOU File',
                'note' => $file->post_title,
                'type' => 'file',
                'link' => get_permalink($file->ID)
            ];

            $proofs = get_posts([
                'post_type' => 'tsemou_proof',
                'numberposts' => 50,
                'post_status' => ['publish','draft','pending'],
                'meta_key' => '_tsemou_related_file',
                'meta_value' => $file->ID
            ]);

            foreach ($proofs as $proof) {
                $events[] = [
                    'date' => $proof->post_date,
                    'title' => 'Evidence added',
                    'note' => $proof->post_title,
                    'type' => 'proof',
                    'link' => get_permalink($proof->ID)
                ];
            }
        }

        usort($events, function($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });

        return array_slice($events, 0, 30);
    }

    public static function render_company_timeline_markup($company_id, $admin = false) {
        $events = self::get_company_timeline_events($company_id);

        ob_start();
        ?>
        <div class="tsemou-company-timeline <?php echo $admin ? 'is-admin' : 'is-frontend'; ?>">
            <h2>📅 Timeline</h2>

            <?php if (!$events): ?>
                <div class="tsemou-timeline-empty">No timeline events yet.</div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="tsemou-timeline-event">
                        <div class="tsemou-timeline-date">
                            <?php echo esc_html(date_i18n('d M Y', strtotime($event['date']))); ?>
                        </div>
                        <div class="tsemou-timeline-body">
                            <strong><?php echo esc_html($event['title']); ?></strong>
                            <?php if (!empty($event['note'])): ?>
                                <p><?php echo esc_html($event['note']); ?></p>
                            <?php endif; ?>
                            <?php if (!$admin && !empty($event['link'])): ?>
                                <a href="<?php echo esc_url($event['link']); ?>">Open</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_company_timeline_frontend($content) {
        if (is_admin()) return $content;
        if (!is_singular('company')) return $content;
        if (!in_the_loop() || !is_main_query()) return $content;

        return $content . self::render_company_timeline_markup(get_the_ID(), false);
    }

    /**
     * ARCHITECTURE LOCK v0.9.7
     *
     * Related Articles = normal WordPress Posts only.
     * TSEMOU Files use the internal CPT slug 'story', but they are NOT Articles.
     * Evidence/Proof objects stay separate and are rendered in Latest Evidence.
     */
    public static function get_related_articles_for_company($company_id, $limit = 12) {
        $company_id = intval($company_id);
        $company_title = get_the_title($company_id);
        $company_slug = get_post_field('post_name', $company_id);

        $meta_query = [
            'relation' => 'OR',
            [
                'key' => '_tsemou_related_company',
                'value' => $company_id,
                'compare' => '='
            ],
            [
                'key' => 'related_company',
                'value' => $company_id,
                'compare' => '='
            ],
            [
                'key' => 'tsemou_company',
                'value' => $company_id,
                'compare' => '='
            ]
        ];

        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => intval($limit),
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query
        ]);

        if (empty($posts) && $company_title) {
            $posts = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'numberposts' => intval($limit),
                'orderby' => 'date',
                'order' => 'DESC',
                's' => $company_title
            ]);
        }

        if (empty($posts) && $company_slug) {
            $posts = get_posts([
                'post_type' => 'post',
                'post_status' => 'publish',
                'numberposts' => intval($limit),
                'orderby' => 'date',
                'order' => 'DESC',
                's' => str_replace('-', ' ', $company_slug)
            ]);
        }

        return $posts;
    }

    public static function get_article_impact_label($post_id) {
        $impact = get_post_meta($post_id, '_tsemou_impact', true);
        if (!$impact) $impact = get_post_meta($post_id, 'impact', true);
        if (!$impact) $impact = get_post_meta($post_id, 'tsemou_impact', true);

        $impact = strtolower(trim((string) $impact));

        if (in_array($impact, ['positive', 'good', 'solution', 'action'], true)) {
            return ['Positive', 'positive'];
        }

        if (in_array($impact, ['negative', 'bad', 'risk', 'concern'], true)) {
            return ['Negative', 'negative'];
        }

        return ['Neutral', 'neutral'];
    }

    public static function render_enhanced_related_articles_markup($company_id) {
        $articles = self::get_related_articles_for_company($company_id, 12);

        ob_start();
        ?>
        <section class="tsemou-related-articles-pro">
            <div class="tsemou-section-heading">
                <h2>📰 Related Articles</h2>
                <span><?php echo esc_html(count($articles)); ?> found</span>
            </div>

            <?php if (empty($articles)): ?>
                <div class="tsemou-empty-box">No related articles found yet.</div>
            <?php else: ?>
                <div class="tsemou-articles-grid">
                    <?php foreach ($articles as $article): ?>
                        <?php
                        $impact = self::get_article_impact_label($article->ID);
                        $cats = get_the_category($article->ID);
                        $cat_name = !empty($cats) ? $cats[0]->name : get_post_type($article->ID);
                        ?>
                        <article class="tsemou-article-card">
                            <a class="tsemou-article-image" href="<?php echo esc_url(get_permalink($article->ID)); ?>">
                                <?php if (has_post_thumbnail($article->ID)): ?>
                                    <?php echo get_the_post_thumbnail($article->ID, 'medium_large'); ?>
                                <?php else: ?>
                                    <div class="tsemou-article-placeholder">TSEMOU</div>
                                <?php endif; ?>
                            </a>

                            <div class="tsemou-article-content">
                                <div class="tsemou-article-meta">
                                    <span>📅 <?php echo esc_html(get_the_date('d M Y', $article->ID)); ?></span>
                                    <span>🏷 <?php echo esc_html($cat_name); ?></span>
                                </div>

                                <h3>
                                    <a href="<?php echo esc_url(get_permalink($article->ID)); ?>">
                                        <?php echo esc_html(get_the_title($article->ID)); ?>
                                    </a>
                                </h3>

                                <div class="tsemou-article-author">👤 <?php echo esc_html(get_the_author_meta('display_name', $article->post_author)); ?></div>

                                <div class="tsemou-impact-badge is-<?php echo esc_attr($impact[1]); ?>">
                                    <?php echo esc_html($impact[0]); ?>
                                </div>

                                <a class="tsemou-read-article" href="<?php echo esc_url(get_permalink($article->ID)); ?>">Read Article</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    public function render_enhanced_related_articles_frontend($content) {
        if (is_admin()) return $content;
        if (!is_singular('company')) return $content;
        if (!in_the_loop() || !is_main_query()) return $content;

        if (strpos($content, 'tsemou-related-articles-pro') !== false) return $content;
        if (strpos($content, '[tsemou_related_articles_pro') !== false) return $content;

        return $content . self::render_enhanced_related_articles_markup(get_the_ID());
    }

    public function shortcode_enhanced_related_articles($atts = []) {
        $atts = shortcode_atts([
            'company_id' => 0
        ], $atts);

        $company_id = intval($atts['company_id']);
        if (!$company_id && is_singular('company')) {
            $company_id = get_the_ID();
        }

        if (!$company_id) return '';

        return self::render_enhanced_related_articles_markup($company_id);
    }

    public function render_related_articles_footer_safe() {
        return;
    }

    public function start_company_template_buffer() {
        if (is_admin()) return;
        if (!is_singular('company')) return;
        ob_start([$this, 'inject_related_articles_into_company_template']);
    }

    public function inject_related_articles_into_company_template($html) {
        if (!is_singular('company')) return $html;
        if (strpos($html, 'tsemou-related-articles-pro') !== false) return $html;

        $company_id = get_queried_object_id();
        if (!$company_id) return $html;

        $section = self::render_enhanced_related_articles_markup($company_id);

        // Preferred: replace the existing empty Related Articles area.
        $patterns = [
            '/(<h[1-4][^>]*>\s*📰\s*Related Articles\s*<\/h[1-4]>)/i',
            '/(<h[1-4][^>]*>\s*Related Articles\s*<\/h[1-4]>)/i',
            '/(<h[1-4][^>]*>\s*Latest Evidence\s*<\/h[1-4]>)/i'
        ];

        foreach ($patterns as $index => $pattern) {
            if (preg_match($pattern, $html)) {
                if ($index < 2) {
                    return preg_replace($pattern, $section, $html, 1);
                }

                return preg_replace($pattern, $section . '$1', $html, 1);
            }
        }

        // Fallback: append before footer if no heading is found.
        if (stripos($html, '</footer>') !== false) {
            return preg_replace('/<\/footer>/i', $section . '</footer>', $html, 1);
        }

        return $html . $section;
    }

    public static function get_company_meta_value($company_id, $keys, $default = '') {
        foreach ($keys as $key) {
            $value = get_post_meta($company_id, $key, true);
            if ($value !== '' && $value !== null) return $value;
        }
        return $default;
    }

    public function shortcode_company_hero($atts = []) {
        $atts = shortcode_atts([
            'company_id' => 0
        ], $atts);

        $company_id = intval($atts['company_id']);
        if (!$company_id && is_singular('company')) {
            $company_id = get_the_ID();
        }

        if (!$company_id) return '';

        $title = get_the_title($company_id);

        $country = self::get_company_meta_value($company_id, [
            '_tsemou_company_country',
            'tsemou_company_country',
            'country'
        ], 'Country not set');

        $industry = self::get_company_meta_value($company_id, [
            '_tsemou_company_industry',
            'tsemou_company_industry',
            'industry'
        ], 'Industry not set');

        $score = self::get_company_meta_value($company_id, [
            '_tsemou_company_score',
            'tsemou_company_score',
            '_tsemou_trust_score',
            'tsemou_trust_score',
            'trust_score'
        ], '—');

                $score = self::normalize_display_score($score);

$total = get_post_meta($company_id, '_tsemou_evidence_total', true);
        if ($total === '' || intval($total) === 0) {
            $legacy_evidence_items = self::get_evidence_for_company_feed($company_id, 999);
            $total = count($legacy_evidence_items);
        }

        $updated = get_the_modified_date('M j, Y', $company_id);
        $thumb = get_the_post_thumbnail_url($company_id, 'medium');

        $status = 'Watch';
        $status_class = 'watch';
        if (is_numeric($score)) {
            if (floatval($score) >= 8) {
                $status = 'Excellent';
                $status_class = 'excellent';
            } elseif (floatval($score) < 5) {
                $status = 'Critical';
                $status_class = 'critical';
            }
        }

        ob_start();
        ?>
        <section class="tsemou-company-hero-widget">
            <div class="tsemou-company-hero-logo">
                <?php if ($thumb): ?>
                    <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>">
                <?php else: ?>
                    <div class="tsemou-company-hero-placeholder"><?php echo esc_html(substr($title, 0, 1)); ?></div>
                <?php endif; ?>
            </div>

            <div class="tsemou-company-hero-main">
                <span class="tsemou-company-hero-kicker">TSEMOU COMPANY REPORT</span>
                <h1><?php echo esc_html($title); ?></h1>

                <div class="tsemou-company-hero-meta">
                    <span>🌍 <?php echo esc_html($country); ?></span>
                    <span>🏭 <?php echo esc_html($industry); ?></span>
                    <span>🧾 <?php echo esc_html($total); ?> Evidence</span>
                    <span>🕒 Updated <?php echo esc_html($updated); ?></span>
                </div>
            </div>

            <div class="tsemou-company-hero-score">
                <span>TSEMOU Trust Score</span>
                <strong><?php echo esc_html($score); ?></strong>
                <small>/10</small>
                <em class="is-<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status); ?></em>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public function shortcode_company_stats($atts = []) {
        $atts = shortcode_atts([
            'company_id' => 0
        ], $atts);

        $company_id = intval($atts['company_id']);
        if (!$company_id && is_singular('company')) {
            $company_id = get_the_ID();
        }

        if (!$company_id) return '';

        $total = get_post_meta($company_id, '_tsemou_evidence_total', true);
        $positive = get_post_meta($company_id, '_tsemou_evidence_positive', true);
        $negative = get_post_meta($company_id, '_tsemou_evidence_negative', true);
        $neutral = get_post_meta($company_id, '_tsemou_evidence_neutral', true);
        $high = get_post_meta($company_id, '_tsemou_evidence_high_credibility', true);
        $avg = get_post_meta($company_id, '_tsemou_evidence_avg_credibility', true);

        $bridge_items = self::get_evidence_for_company_feed($company_id, 999);

        if ($total === '' || intval($total) === 0) {
            $total = count($bridge_items);
        } else {
            $total = intval($total);
        }

        $positive = $positive !== '' ? intval($positive) : 0;
        $negative = $negative !== '' ? intval($negative) : 0;
        $neutral = $neutral !== '' ? intval($neutral) : 0;
        $high = $high !== '' ? intval($high) : 0;
        $avg = $avg !== '' ? $avg : 0;

        if (!empty($bridge_items) && ($positive + $negative + $neutral) === 0) {
            foreach ($bridge_items as $bridge_item) {
                $sentiment = strtolower(trim((string) self::get_company_meta_value($bridge_item->ID, [
                    '_tsemou_sentiment',
                    'tsemou_sentiment',
                    'sentiment'
                ], 'neutral')));

                if (strpos($sentiment, 'positive') !== false) {
                    $positive++;
                } elseif (strpos($sentiment, 'negative') !== false) {
                    $negative++;
                } else {
                    $neutral++;
                }
            }
        }

        ob_start();
        ?>
        <section class="tsemou-company-stats-widget">
            <div class="tsemou-stat-card">
                <div class="tsemou-stat-icon">🧾</div>
                <strong><?php echo esc_html($total); ?></strong>
                <span>Total Evidence</span>
            </div>
            <div class="tsemou-stat-card is-negative">
                <div class="tsemou-stat-icon">⚠️</div>
                <strong><?php echo esc_html($negative); ?></strong>
                <span>Negative Signals</span>
            </div>
            <div class="tsemou-stat-card is-positive">
                <div class="tsemou-stat-icon">✅</div>
                <strong><?php echo esc_html($positive); ?></strong>
                <span>Positive Signals</span>
            </div>
            <div class="tsemou-stat-card">
                <div class="tsemou-stat-icon">⚖️</div>
                <strong><?php echo esc_html($neutral); ?></strong>
                <span>Neutral Evidence</span>
            </div>
            <div class="tsemou-stat-card">
                <div class="tsemou-stat-icon">🛡️</div>
                <strong><?php echo esc_html($high); ?></strong>
                <span>High Credibility</span>
            </div>
            <div class="tsemou-stat-card">
                <div class="tsemou-stat-icon">📊</div>
                <strong><?php echo esc_html($avg); ?></strong>
                <span>Avg Credibility</span>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public static function resolve_company_id_from_shortcode($atts = []) {
        $company_id = !empty($atts['company_id']) ? intval($atts['company_id']) : 0;
        if (!$company_id && is_singular('company')) {
            $company_id = get_the_ID();
        }
        return $company_id;
    }

    public function shortcode_company_summary($atts = []) {
        $atts = shortcode_atts(['company_id' => 0], $atts);
        $company_id = self::resolve_company_id_from_shortcode($atts);
        if (!$company_id) return '';

        $summary = self::get_company_meta_value($company_id, [
            '_tsemou_company_summary',
            'tsemou_company_summary',
            'company_summary',
            'summary'
        ], '');

        $concerns = self::get_company_meta_value($company_id, [
            '_tsemou_company_main_concerns',
            'tsemou_company_main_concerns',
            'main_concerns'
        ], '');

        $positive = self::get_company_meta_value($company_id, [
            '_tsemou_company_positive_notes',
            'tsemou_company_positive_notes',
            'positive_notes'
        ], '');

        ob_start();
        ?>
        <section class="tsemou-company-content-widgets">
            <article class="tsemou-content-card is-summary">
                <h2>📄 Company Summary</h2>
                <?php echo $summary ? wp_kses_post(wpautop($summary)) : '<p>No summary added yet.</p>'; ?>
            </article>

            <article class="tsemou-content-card is-concerns">
                <h2>⚠️ Main Concerns</h2>
                <?php echo $concerns ? wp_kses_post(wpautop($concerns)) : '<p>No concerns added yet.</p>'; ?>
            </article>

            <article class="tsemou-content-card is-positive">
                <h2>✅ Positive Notes</h2>
                <?php echo $positive ? wp_kses_post(wpautop($positive)) : '<p>No positive notes added yet.</p>'; ?>
            </article>
        </section>
        <?php
        return ob_get_clean();
    }

    public function shortcode_company_timeline($atts = []) {
        $atts = shortcode_atts(['company_id' => 0], $atts);
        $company_id = self::resolve_company_id_from_shortcode($atts);
        if (!$company_id) return '';

        return '<div class="tsemou-widget-shell">' . self::render_company_timeline_markup($company_id, false) . '</div>';
    }

    public function shortcode_company_related($atts = []) {
        $atts = shortcode_atts(['company_id' => 0], $atts);
        $company_id = self::resolve_company_id_from_shortcode($atts);
        if (!$company_id) return '';

        return '<div class="tsemou-widget-shell">' . self::render_enhanced_related_articles_markup($company_id) . '</div>';
    }

    public function shortcode_company_evidence_feed($atts = []) {
        $atts = shortcode_atts([
            'company_id' => 0,
            'limit' => 12
        ], $atts);

        $company_id = self::resolve_company_id_from_shortcode($atts);
        if (!$company_id) return '';

        $limit = intval($atts['limit']);
        if ($limit <= 0) $limit = 12;

        $evidence_items = self::get_evidence_for_company_feed($company_id, $limit);

        ob_start();
        ?>
        <section class="tsemou-evidence-feed-widget">
            <div class="tsemou-widget-heading">
                <div>
                    <span>TSEMOU EVIDENCE</span>
                    <h2>Latest Evidence</h2>
                </div>
                <strong><?php echo esc_html(count($evidence_items)); ?> shown</strong>
            </div>

            <?php if (empty($evidence_items)): ?>
                <div class="tsemou-empty-state">
                    No evidence connected to this company yet.
                </div>
            <?php else: ?>
                <div class="tsemou-evidence-feed-grid">
                    <?php foreach ($evidence_items as $item): ?>
                        <?php
                        $sentiment = self::get_evidence_sentiment_label($item->ID);

                        $category = self::get_company_meta_value($item->ID, [
                            '_tsemou_category',
                            'tsemou_category',
                            'category'
                        ], 'Uncategorized');

                        if (is_array($category)) {
                            $category = implode(', ', array_map('sanitize_text_field', $category));
                        }

                        $credibility = self::get_evidence_credibility_label($item->ID);

                        $source = self::get_company_meta_value($item->ID, [
                            '_tsemou_source_name',
                            'tsemou_source_name',
                            'source_name',
                            'source'
                        ], 'Source not set');

                        $sentiment_class = strtolower(trim((string) $sentiment));
                        if (strpos($sentiment_class, 'positive') !== false) {
                            $sentiment_class = 'positive';
                        } elseif (strpos($sentiment_class, 'negative') !== false) {
                            $sentiment_class = 'negative';
                        } else {
                            $sentiment_class = 'neutral';
                        }
                        ?>
                        <article class="tsemou-evidence-feed-card">
                            <?php if (has_post_thumbnail($item->ID)): ?>
                                <a class="tsemou-evidence-feed-image" href="<?php echo esc_url(get_permalink($item->ID)); ?>">
                                    <?php echo get_the_post_thumbnail($item->ID, 'medium_large'); ?>
                                </a>
                            <?php else: ?>
                                <a class="tsemou-evidence-feed-image is-placeholder" href="<?php echo esc_url(get_permalink($item->ID)); ?>">
                                    🧾
                                </a>
                            <?php endif; ?>

                            <div class="tsemou-evidence-feed-body">
                                <div class="tsemou-evidence-feed-badges">
                                    <span class="sentiment-<?php echo esc_attr($sentiment_class); ?>">
                                        <?php echo esc_html($sentiment); ?>
                                    </span>
                                    <span><?php echo esc_html($category); ?></span>
                                </div>

                                <h3>
                                    <a href="<?php echo esc_url(get_permalink($item->ID)); ?>">
                                        <?php echo esc_html(get_the_title($item->ID)); ?>
                                    </a>
                                </h3>

                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt($item->ID), 22)); ?></p>

                                <div class="tsemou-evidence-feed-meta">
                                    <span>📅 <?php echo esc_html(get_the_date('M j, Y', $item->ID)); ?></span>
                                    <span>🌐 <?php echo esc_html($source); ?></span>
                                    <span>⭐ Credibility <?php echo esc_html($credibility); ?></span>
                                </div>

                                <a class="tsemou-evidence-feed-button" href="<?php echo esc_url(get_permalink($item->ID)); ?>">
                                    Read Evidence →
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }


    /**
     * v2.3.8 Evidence Metadata Cleanup
     * Centralized evidence display meta so legacy renderer and clean renderer
     * progressively converge on one evidence pipeline.
     */
    public static function get_evidence_source_url($evidence_id) {
        $url = self::get_company_meta_value($evidence_id, [
            '_tsemou_source_url',
            'tsemou_source_url',
            'source_url',
            'Source URL',
            'url',
            'evidence_url',
            '_source_url'
        ], '');

        return esc_url_raw($url);
    }

    public static function get_evidence_credibility_label($evidence_id) {
        $value = self::get_company_meta_value($evidence_id, [
            '_tsemou_credibility_score',
            'tsemou_credibility_score',
            'credibility_score',
            'credibility',
            'source_credibility',
            '_credibility_score'
        ], '');

        if ($value === '' || $value === null) {
            return 'Not rated';
        }

        if (is_numeric($value)) {
            $num = floatval($value);

            if ($num > 0 && $num <= 1) {
                $num = $num * 10;
            } elseif ($num > 10) {
                $num = $num / 10;
            }

            return number_format($num, 1) . '/10';
        }

        return sanitize_text_field((string) $value);
    }

    public static function get_evidence_display_excerpt($evidence_id, $words = 26) {
        $summary = self::get_company_meta_value($evidence_id, [
            '_tsemou_summary',
            'tsemou_summary',
            'summary',
            'evidence_summary',
            'short_summary',
            'description',
            '_description'
        ], '');

        if (!empty($summary)) {
            return wp_trim_words(wp_strip_all_tags($summary), $words);
        }

        $post = get_post($evidence_id);
        if (!$post) return '';

        $content = trim(wp_strip_all_tags($post->post_content));

        $noise_markers = [
            'Verified Company TSEMPORT',
            'Clean TSEMPORT Renderer',
            'TSEMOU COMPANY REPORT',
            'TSEMScore',
            'TSEMIDENCE',
            'TSEMIT open',
            'Related Entities'
        ];

        $has_noise = false;
        foreach ($noise_markers as $marker) {
            if (stripos($content, $marker) !== false) {
                $has_noise = true;
                break;
            }
        }

        if ($has_noise) {
            $sentences = preg_split('/(?<=[.!?])\s+/', $content);
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (strlen($sentence) < 25) continue;

                $bad = false;
                foreach ($noise_markers as $marker) {
                    if (stripos($sentence, $marker) !== false) {
                        $bad = true;
                        break;
                    }
                }

                if (!$bad) {
                    return wp_trim_words($sentence, $words);
                }
            }
        }

        return wp_trim_words($content, $words);
    }

    public static function get_evidence_status_label($evidence_id) {
        return self::get_company_meta_value($evidence_id, [
            '_tsemou_evidence_status',
            'tsemou_evidence_status',
            'evidence_status',
            'status'
        ], get_post_status($evidence_id));
    }

    public static function get_evidence_sentiment_label($evidence_id) {
        return self::get_company_meta_value($evidence_id, [
            '_tsemou_sentiment',
            'tsemou_sentiment',
            'sentiment',
            'evidence_sentiment'
        ], 'Neutral');
    }

    public static function get_evidence_kind_label($evidence_id) {
        return self::get_company_meta_value($evidence_id, [
            '_tsemou_evidence_kind',
            'tsemou_evidence_kind',
            'evidence_kind',
            'evidence_type',
            'type'
        ], 'Uncategorized');
    }


    public static function get_evidence_for_company_feed($company_id, $limit = 12) {
        $company_id = intval($company_id);

        /*
         * v2.3.7 Legacy Evidence Bridge
         * The old Company Report must read the same relationships as the
         * Company Section Engine / Developer Console.
         */
        $meta_query = [
            'relation' => 'OR',

            // New TSEMOU relationship keys.
            [
                'key' => '_tsemou_company_id',
                'value' => $company_id,
                'compare' => '='
            ],
            [
                'key' => '_tsemou_entity_id',
                'value' => $company_id,
                'compare' => '='
            ],

            // Existing/legacy company relationship keys.
            [
                'key' => '_tsemou_evidence_company_ids',
                'value' => '"' . $company_id . '"',
                'compare' => 'LIKE'
            ],
            [
                'key' => '_tsemou_evidence_company_ids',
                'value' => $company_id,
                'compare' => 'LIKE'
            ],
            [
                'key' => '_tsemou_evidence_company',
                'value' => $company_id,
                'compare' => '='
            ],
            [
                'key' => 'tsemou_evidence_company',
                'value' => $company_id,
                'compare' => '='
            ],
            [
                'key' => 'related_company',
                'value' => $company_id,
                'compare' => '='
            ],

            // ACF / relationship field variants.
            [
                'key' => 'company',
                'value' => $company_id,
                'compare' => '='
            ],
            [
                'key' => 'company',
                'value' => '"' . $company_id . '"',
                'compare' => 'LIKE'
            ],
            [
                'key' => 'company',
                'value' => 'i:' . $company_id . ';',
                'compare' => 'LIKE'
            ],
        ];

        $items = get_posts([
            'post_type' => ['tsemou_proof', 'evidence'],
            'post_status' => ['publish', 'draft', 'pending'],
            'numberposts' => intval($limit),
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query
        ]);

        if (empty($items)) {
            $all_evidence = get_posts([
                'post_type' => ['tsemou_proof', 'evidence'],
                'post_status' => ['publish', 'draft', 'pending'],
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);

            foreach ($all_evidence as $candidate) {
                $meta = get_post_meta($candidate->ID);
                $matched = false;

                foreach ($meta as $key => $values) {
                    foreach ((array) $values as $value) {
                        if (is_array($value)) {
                            $value = wp_json_encode($value);
                        }
                        $value = (string) $value;

                        if (
                            $value === (string) $company_id ||
                            strpos($value, '"' . $company_id . '"') !== false ||
                            strpos($value, 'i:' . $company_id . ';') !== false
                        ) {
                            $matched = true;
                            break 2;
                        }
                    }
                }

                if ($matched) {
                    $items[] = $candidate;
                }

                if (count($items) >= intval($limit)) break;
            }
        }

        if (empty($items) && method_exists(__CLASS__, 'get_connected_company_ids')) {
            $all = get_posts([
                'post_type' => ['tsemou_proof', 'evidence'],
                'post_status' => ['publish', 'draft', 'pending'],
                'numberposts' => -1,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);

            foreach ($all as $item) {
                $related_file = get_post_meta($item->ID, '_tsemou_related_file', true);
                if ($related_file) {
                    $ids = self::get_connected_company_ids(intval($related_file));
                    if (in_array($company_id, array_map('intval', $ids), true)) {
                        $items[] = $item;
                    }
                }

                if (count($items) >= intval($limit)) break;
            }
        }

        return $items;
    }

    public function shortcode_company_overview($atts = []) {
        $atts = shortcode_atts(['company_id' => 0], $atts);
        $company_id = self::resolve_company_id_from_shortcode($atts);
        if (!$company_id) return '';

        $title = get_the_title($company_id);

        $country = self::get_company_meta_value($company_id, [
            '_tsemou_company_country',
            'tsemou_company_country',
            'country'
        ], 'Country not set');

        $industry = self::get_company_meta_value($company_id, [
            '_tsemou_company_industry',
            'tsemou_company_industry',
            'industry'
        ], 'Industry not set');

        $engine_score = get_post_meta($company_id, '_tsemou_trust_engine_score', true);
        $manual_score = self::get_company_meta_value($company_id, [
            '_tsemou_company_score',
            'tsemou_company_score',
            '_tsemou_trust_score',
            'tsemou_trust_score',
            'trust_score'
        ], '');

        $score = $engine_score !== '' ? $engine_score : ($manual_score !== '' ? $manual_score : '—');

        $evidence_score = get_post_meta($company_id, '_tsemou_trust_engine_evidence_score', true);
        $community_score = get_post_meta($company_id, '_tsemou_trust_engine_community_score', true);
        $last_calc = get_post_meta($company_id, '_tsemou_trust_engine_last_calc', true);

        $environment = self::get_company_meta_value($company_id, ['_tsemou_environment_score','environment_score'], '');
        $workers = self::get_company_meta_value($company_id, ['_tsemou_workers_score','workers_score'], '');
        $human = self::get_company_meta_value($company_id, ['_tsemou_human_rights_score','human_rights_score'], '');
        $ethics = self::get_company_meta_value($company_id, ['_tsemou_ethics_score','ethics_score'], '');
        $transparency = self::get_company_meta_value($company_id, ['_tsemou_transparency_score','transparency_score'], '');
        $animals = self::get_company_meta_value($company_id, ['_tsemou_animal_welfare_score','animal_welfare_score'], '');

        $score = self::normalize_display_score($score);
        $environment = self::normalize_display_score($environment);
        $workers = self::normalize_display_score($workers);
        $human = self::normalize_display_score($human);
        $ethics = self::normalize_display_score($ethics);
        $transparency = self::normalize_display_score($transparency);
        $animals = self::normalize_display_score($animals);

        $details = [
            '🌱 Environment' => $environment,
            '👷 Workers' => $workers,
            '⚖️ Human Rights' => $human,
            '🤝 Ethics' => $ethics,
            '🔍 Transparency' => $transparency,
            '🐾 Animal Welfare' => $animals,
        ];

        $status = 'Watch';
        $status_class = 'watch';
        if (is_numeric($score)) {
            if (floatval($score) >= 8) {
                $status = 'Excellent';
                $status_class = 'excellent';
            } elseif (floatval($score) < 5) {
                $status = 'Critical';
                $status_class = 'critical';
            }
        }

        ob_start();
        ?>
        <section class="tsemou-company-overview-widget">
            <div class="tsemou-overview-card is-score">
                <span class="tsemou-overline">TSEMOU Trust</span>
                <strong><?php echo esc_html($score); ?></strong>
                <small>/10</small>
                <em class="is-<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status); ?></em>
            </div>

            <div class="tsemou-overview-card">
                <span class="tsemou-overline">Company Identity</span>
                <h3><?php echo esc_html($title); ?></h3>
                <p>🌍 <?php echo esc_html($country); ?></p>
                <p>🏭 <?php echo esc_html($industry); ?></p>
            </div>

            <div class="tsemou-overview-card">
                <span class="tsemou-overline">Trust Breakdown</span>
                <p><b>Evidence:</b> <?php echo esc_html($evidence_score !== '' ? $evidence_score : '—'); ?></p>
                <p><b>Community:</b> <?php echo esc_html($community_score !== '' ? $community_score : '—'); ?></p>
                <p><b>Last calc:</b> <?php echo esc_html($last_calc ?: '—'); ?></p>
            </div>

            <div class="tsemou-overview-card is-wide">
                <span class="tsemou-overline">Detailed Scores</span>
                <div class="tsemou-score-list">
                    <?php foreach ($details as $label => $value): ?>
                        <?php if ($value !== ''): 
                            $num = is_numeric($value) ? min(10, max(0, floatval($value))) : 0;
                            $pct = $num * 10;
                        ?>
                            <div class="tsemou-score-row">
                                <div><span><?php echo esc_html($label); ?></span><b><?php echo esc_html($value); ?>/10</b></div>
                                <i><u style="width:<?php echo esc_attr($pct); ?>%"></u></i>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($environment === '' && $workers === '' && $human === '' && $ethics === '' && $transparency === '' && $animals === ''): ?>
                        <p>No detailed scores added yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public function shortcode_company_page($atts = []) {
        $atts = shortcode_atts([
            'company_id' => 0,
            'show_vote' => 'yes'
        ], $atts);

        $company_id = self::resolve_company_id_from_shortcode($atts);
        if (!$company_id) return '';

        $short_atts = ['company_id' => $company_id];

        ob_start();
        ?>
        <main class="tsemou-company-page-v2" data-company-id="<?php echo esc_attr($company_id); ?>">

            <section class="tsemou-v2-section tsemou-v2-hero">
                <?php echo $this->shortcode_company_hero($short_atts); ?>
            </section>

            <section class="tsemou-v2-section tsemou-v2-stats">
                <?php echo $this->shortcode_company_stats($short_atts); ?>
            </section>

            <section class="tsemou-v2-section tsemou-v2-overview">
                <?php echo $this->shortcode_company_overview($short_atts); ?>
            </section>

            <section class="tsemou-v2-section tsemou-v2-trust">
                <?php
                if (shortcode_exists('tsemou_company_trust')) {
                    echo do_shortcode('[tsemou_company_trust company_id="' . intval($company_id) . '"]');
                }
                ?>
            </section>

            <?php if ($atts['show_vote'] === 'yes'): ?>
                <section class="tsemou-v2-section tsemou-v2-community">
                    <?php
                    if (shortcode_exists('tsemou_company_quick_vote')) {
                        echo do_shortcode('[tsemou_company_quick_vote company_id="' . intval($company_id) . '"]');
                    } elseif (shortcode_exists('tsemou_company_vote')) {
                        echo do_shortcode('[tsemou_company_vote company_id="' . intval($company_id) . '"]');
                    }
                    ?>
                </section>
            <?php endif; ?>

            <section class="tsemou-v2-section tsemou-v2-summary">
                <?php echo $this->shortcode_company_summary($short_atts); ?>
            </section>

            <section class="tsemou-v2-section tsemou-v2-evidence">
                <?php echo $this->shortcode_company_evidence_feed($short_atts); ?>
            </section>

            <section class="tsemou-v2-section tsemou-v2-related">
                <?php echo $this->shortcode_company_related($short_atts); ?>
            </section>

            <section class="tsemou-v2-section tsemou-v2-timeline">
                <?php echo $this->shortcode_company_timeline($short_atts); ?>
            </section>

        </main>
        <?php
        return ob_get_clean();
    }


    public static function normalize_display_score($value) {
        if ($value === '' || $value === null) return '';
        if (!is_numeric($value)) return $value;

        $num = floatval($value);

        if ($num > 10) {
            $num = $num / 10;
        }

        $num = max(0, min(10, $num));

        return rtrim(rtrim(number_format($num, 1, '.', ''), '0'), '.');
    }

}
