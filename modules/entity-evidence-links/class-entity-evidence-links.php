<?php
namespace TSEMOU\Modules\EntityEvidenceLinks;

if (!defined('ABSPATH')) exit;

class Entity_Evidence_Links {
    private static $instance = null;
    private static $last_evidence_diagnostics = [];

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 29);
    }

    public static function relationship_types() {
        return [
            'primary_target' => 'Primary Target',
            'related_target' => 'Related Target',
            'leadership_responsibility' => 'Leadership Responsibility',
            'ownership_responsibility' => 'Ownership Responsibility',
            'supplier_customer' => 'Supplier / Customer Relation',
            'funding_influence' => 'Funding / Influence',
            'supporting_evidence' => 'Supporting Evidence',
            'contradicting_evidence' => 'Contradicting Evidence',
            'updates_previous' => 'Updates Previous Evidence',
            'context_only' => 'Context Only'
        ];
    }

    public static function entity_posts() {
        $entities = get_posts([
            'post_type' => 'tsemou_entity',
            'post_status' => ['publish','draft','pending','private'],
            'numberposts' => 500,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $companies = get_posts([
            'post_type' => 'company',
            'post_status' => ['publish','draft','pending','private'],
            'numberposts' => 500,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        return array_merge($entities, $companies);
    }

    public static function evidence_posts() {
        static $mismatch_logged = false;

        $proofs = [];
        $proof_total = 0;
        if (post_type_exists('tsemou_proof')) {
            $proof_total = self::count_posts_by_statuses('tsemou_proof', ['publish','draft','pending','private','future']);
            $proofs = get_posts([
                'post_type' => 'tsemou_proof',
                'post_status' => ['publish','draft','pending','private','future'],
                'numberposts' => 500,
                'orderby' => 'date',
                'order' => 'DESC',
                'perm' => 'readable',
                'suppress_filters' => true,
                'no_found_rows' => true,
            ]);
        }

        $legacy = [];
        $legacy_total = 0;
        if (post_type_exists('evidence')) {
            $legacy_total = self::count_posts_by_statuses('evidence', ['publish','draft','pending','private']);
            $legacy = get_posts([
                'post_type' => 'evidence',
                'post_status' => ['publish','draft','pending','private'],
                'numberposts' => 500,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);
        }

        $proof_query_count = count($proofs);
        $legacy_query_count = count($legacy);
        $fallback_used = false;
        $source_path = 'proof-first';

        if ($proof_query_count > 0) {
            if ($legacy_query_count > 0) {
                $source_path = 'mixed';
                $posts = array_merge($proofs, $legacy);
            } else {
                $posts = $proofs;
            }
        } elseif ($legacy_query_count > 0) {
            $fallback_used = true;
            $source_path = 'legacy-fallback-only';
            $posts = $legacy;
        } else {
            $posts = [];
        }

        $warning = '';
        if ($proof_total > 0 && $proof_query_count === 0) {
            $warning = 'Proof visibility mismatch: tsemou_proof exists but Entity Evidence Links query returned 0.';
            if (!$mismatch_logged) {
                self::add_log('proof_visibility_mismatch', 0, $warning);
                $mismatch_logged = true;
            }
        }

        self::$last_evidence_diagnostics = [
            'proof_total' => intval($proof_total),
            'legacy_total' => intval($legacy_total),
            'proof_query_count' => intval($proof_query_count),
            'legacy_query_count' => intval($legacy_query_count),
            'fallback_used' => $fallback_used,
            'source_path' => $source_path,
            'warning' => $warning,
        ];

        return $posts;
    }

    public static function evidence_diagnostics() {
        if (empty(self::$last_evidence_diagnostics)) {
            self::evidence_posts();
        }

        return self::$last_evidence_diagnostics;
    }

    private static function count_posts_by_statuses($post_type, $statuses = []) {
        if (!post_type_exists($post_type)) {
            return 0;
        }

        $counts = wp_count_posts($post_type);
        if (!is_object($counts)) {
            return 0;
        }

        $statuses = is_array($statuses) ? $statuses : [];
        if (empty($statuses)) {
            $statuses = ['publish', 'draft', 'pending', 'private'];
        }

        $total = 0;
        foreach ($statuses as $status) {
            $total += isset($counts->$status) ? intval($counts->$status) : 0;
        }

        return $total;
    }

    public static function link_object($evidence_id, $entity_id, $relationship_type, $confidence = 0, $notes = '') {
        return [
            'link_id' => 'link_' . substr(sha1($evidence_id . '|' . $entity_id . '|' . $relationship_type), 0, 16),
            'evidence_id' => intval($evidence_id),
            'entity_id' => intval($entity_id),
            'entity_type' => get_post_type($entity_id),
            'relationship_type' => sanitize_key($relationship_type),
            'confidence' => max(0, min(100, intval($confidence))),
            'notes' => sanitize_textarea_field($notes),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => 'active'
        ];
    }

    public static function get_links_for_evidence($evidence_id) {
        $json = get_post_meta($evidence_id, '_tsemou_entity_evidence_links', true);
        $links = $json ? json_decode($json, true) : [];
        return is_array($links) ? $links : [];
    }

    public static function save_links_for_evidence($evidence_id, $links) {
        $links = is_array($links) ? array_values($links) : [];
        update_post_meta($evidence_id, '_tsemou_entity_evidence_links', wp_json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        update_post_meta($evidence_id, '_tsemou_entity_evidence_link_count', count($links));
        self::add_log('links_saved', $evidence_id, 'Evidence entity links saved.');
        return $links;
    }

    public static function add_link($evidence_id, $entity_id, $relationship_type, $confidence = 0, $notes = '') {
        $links = self::get_links_for_evidence($evidence_id);
        $new = self::link_object($evidence_id, $entity_id, $relationship_type, $confidence, $notes);

        foreach ($links as $i => $link) {
            if (($link['link_id'] ?? '') === $new['link_id']) {
                $new['created_at'] = $link['created_at'] ?? current_time('mysql');
                $links[$i] = $new;
                return self::save_links_for_evidence($evidence_id, $links);
            }
        }

        $links[] = $new;
        return self::save_links_for_evidence($evidence_id, $links);
    }

    public static function remove_link($evidence_id, $link_id) {
        $links = self::get_links_for_evidence($evidence_id);
        $links = array_values(array_filter($links, function($link) use ($link_id) {
            return ($link['link_id'] ?? '') !== $link_id;
        }));
        return self::save_links_for_evidence($evidence_id, $links);
    }

    public static function links_for_entity($entity_id) {
        $evidence = self::evidence_posts();
        $found = [];

        foreach ($evidence as $ev) {
            $links = self::get_links_for_evidence($ev->ID);
            foreach ($links as $link) {
                if (intval($link['entity_id'] ?? 0) === intval($entity_id)) {
                    $link['evidence_title'] = get_the_title($ev->ID);
                    $found[] = $link;
                }
            }
        }

        return $found;
    }

    public static function stats($evidence = null) {
        if (!is_array($evidence)) {
            $evidence = self::evidence_posts();
        }

        $linked_evidence = 0;
        $links_total = 0;

        foreach ($evidence as $ev) {
            $links = self::get_links_for_evidence($ev->ID);
            if (!empty($links)) {
                $linked_evidence++;
                $links_total += count($links);
            }
        }

        return [
            'evidence_total' => count($evidence),
            'linked_evidence' => $linked_evidence,
            'links_total' => $links_total,
            'entities_total' => count(self::entity_posts())
        ];
    }

    public static function add_log($action, $evidence_id, $message) {
        $logs = get_option('tsemou_entity_evidence_link_logs', []);
        if (!is_array($logs)) $logs = [];

        array_unshift($logs, [
            'time' => current_time('mysql'),
            'action' => sanitize_key($action),
            'evidence_id' => intval($evidence_id),
            'evidence' => get_the_title($evidence_id),
            'message' => sanitize_text_field($message)
        ]);

        update_option('tsemou_entity_evidence_link_logs', array_slice($logs, 0, 200));
    }

    public static function logs() {
        $logs = get_option('tsemou_entity_evidence_link_logs', []);
        return is_array($logs) ? $logs : [];
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Entity Evidence Links',
            'Entity Evidence Links',
            'manage_options',
            'tsemou-entity-evidence-links',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        $notice = null;

        if (!empty($_POST['tsemou_entity_evidence_links_nonce']) && wp_verify_nonce($_POST['tsemou_entity_evidence_links_nonce'], 'tsemou_entity_evidence_links_action')) {
            $action = sanitize_text_field($_POST['entity_evidence_links_action'] ?? '');
            $evidence_id = intval($_POST['evidence_id'] ?? 0);

            if ($action === 'add_link' && $evidence_id) {
                $entity_id = intval($_POST['entity_id'] ?? 0);
                $relationship_type = sanitize_key($_POST['relationship_type'] ?? '');
                $confidence = intval($_POST['confidence'] ?? 0);
                $notes = $_POST['notes'] ?? '';

                if ($entity_id && $relationship_type) {
                    self::add_link($evidence_id, $entity_id, $relationship_type, $confidence, $notes);
                    $notice = ['success' => true, 'message' => 'Entity evidence link saved.'];
                }
            }

            if ($action === 'remove_link' && $evidence_id) {
                $link_id = sanitize_text_field($_POST['link_id'] ?? '');
                if ($link_id) {
                    self::remove_link($evidence_id, $link_id);
                    $notice = ['success' => true, 'message' => 'Link removed.'];
                }
            }
        }

        $evidence_posts = self::evidence_posts();
        $stats = self::stats($evidence_posts);
        $diagnostics = self::evidence_diagnostics();
        $entities = self::entity_posts();
        $types = self::relationship_types();
        $logs = self::logs();
        ?>
        <div class="wrap">
            <h1>TSEMOU Entity Evidence Links</h1>
            <p>Connect evidence to one or more entities. This is the bridge from company-only evidence to the v2 Entity Architecture.</p>

            <?php if ($notice): ?>
                <div class="notice notice-success"><p><?php echo esc_html($notice['message']); ?></p></div>
            <?php endif; ?>

            <?php if (!empty($diagnostics['warning'])): ?>
                <div class="notice notice-warning"><p><?php echo esc_html($diagnostics['warning']); ?></p></div>
            <?php endif; ?>

            <h2>Status</h2>
            <table class="widefat striped" style="max-width:760px;">
                <tbody>
                    <tr><th>Evidence Total</th><td><?php echo esc_html($stats['evidence_total']); ?></td></tr>
                    <tr><th>Linked Evidence</th><td><?php echo esc_html($stats['linked_evidence']); ?></td></tr>
                    <tr><th>Total Links</th><td><?php echo esc_html($stats['links_total']); ?></td></tr>
                    <tr><th>Available Entities</th><td><?php echo esc_html($stats['entities_total']); ?></td></tr>
                    <tr><th>tsemou_proof count</th><td><?php echo esc_html($diagnostics['proof_total'] ?? 0); ?></td></tr>
                    <tr><th>evidence count</th><td><?php echo esc_html($diagnostics['legacy_total'] ?? 0); ?></td></tr>
                    <tr><th>Proof query returned</th><td><?php echo esc_html($diagnostics['proof_query_count'] ?? 0); ?></td></tr>
                    <tr><th>Fallback used</th><td><?php echo !empty($diagnostics['fallback_used']) ? 'yes' : 'no'; ?></td></tr>
                    <tr><th>Source path</th><td><?php echo esc_html($diagnostics['source_path'] ?? 'proof-first'); ?></td></tr>
                </tbody>
            </table>

            <h2>Add Evidence → Entity Link</h2>
            <form method="post">
                <?php wp_nonce_field('tsemou_entity_evidence_links_action', 'tsemou_entity_evidence_links_nonce'); ?>
                <input type="hidden" name="entity_evidence_links_action" value="add_link">

                <table class="form-table">
                    <tr>
                        <th>Evidence</th>
                        <td>
                            <select name="evidence_id" required>
                                <option value="">Select evidence</option>
                                <?php foreach ($evidence_posts as $ev): ?>
                                    <option value="<?php echo esc_attr($ev->ID); ?>"><?php echo esc_html(get_the_title($ev)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Entity / Company</th>
                        <td>
                            <select name="entity_id" required>
                                <option value="">Select entity</option>
                                <?php foreach ($entities as $entity): ?>
                                    <option value="<?php echo esc_attr($entity->ID); ?>"><?php echo esc_html(get_the_title($entity)); ?> (<?php echo esc_html(get_post_type($entity)); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Relationship Type</th>
                        <td>
                            <select name="relationship_type" required>
                                <?php foreach ($types as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Confidence</th>
                        <td><input type="number" name="confidence" min="0" max="100" value="80"> /100</td>
                    </tr>
                    <tr>
                        <th>Notes</th>
                        <td><textarea name="notes" rows="4" style="width:100%;"></textarea></td>
                    </tr>
                </table>

                <?php submit_button('Save Link'); ?>
            </form>

            <h2>Existing Links</h2>
            <table class="widefat striped">
                <thead><tr><th>Evidence</th><th>Linked Entities</th></tr></thead>
                <tbody>
                    <?php foreach ($evidence_posts as $ev): ?>
                        <?php $links = self::get_links_for_evidence($ev->ID); ?>
                        <tr>
                            <td><strong><?php echo esc_html(get_the_title($ev)); ?></strong></td>
                            <td>
                                <?php if (empty($links)): ?>
                                    —
                                <?php else: ?>
                                    <?php foreach ($links as $link): ?>
                                        <div style="margin-bottom:8px;">
                                            <strong><?php echo esc_html(get_the_title(intval($link['entity_id']))); ?></strong>
                                            <code><?php echo esc_html($link['relationship_type'] ?? ''); ?></code>
                                            <?php echo esc_html($link['confidence'] ?? 0); ?>/100
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Logs</h2>
            <table class="widefat striped">
                <thead><tr><th>Time</th><th>Action</th><th>Evidence</th><th>Message</th></tr></thead>
                <tbody>
                    <?php if (empty($logs)): ?><tr><td colspan="4">No logs yet.</td></tr><?php endif; ?>
                    <?php foreach (array_slice($logs, 0, 50) as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['time'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['action'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['evidence'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['message'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
