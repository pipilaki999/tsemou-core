<?php
namespace TSEMOU\Modules\KnowledgeGraph;

if (!defined('ABSPATH')) exit;

class Knowledge_Graph_Engine {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_post_types']);
        add_action('admin_menu', [$this, 'admin_menu'], 29);
        add_action('admin_post_tsemou_kg_create_entity', [$this, 'handle_create_entity']);
        add_action('admin_post_tsemou_kg_create_relation', [$this, 'handle_create_relation']);
    }

    public function register_post_types() {
        if (!post_type_exists('tsemou_entity')) {
            register_post_type('tsemou_entity', [
                'labels' => ['name'=>'TSEMOU Entities','singular_name'=>'TSEMOU Entity'],
                'public' => false, 'show_ui' => true, 'show_in_menu' => false,
                'supports' => ['title','editor','custom-fields'],
            ]);
        }
        register_post_type('tsemou_relation', [
            'labels' => ['name'=>'TSEMOU Relations','singular_name'=>'TSEMOU Relation'],
            'public' => false, 'show_ui' => true, 'show_in_menu' => false,
            'supports' => ['title','editor','custom-fields'],
        ]);
    }

    public function admin_menu() {
        add_submenu_page('tsemou-os','Knowledge Graph','Knowledge Graph','manage_options','tsemou-knowledge-graph',[$this,'render_admin_page']);
    }

    public static function entity_types() {
        return ['company','source','evidence','person','organization','country','city','topic','event','article','unknown'];
    }

    public static function relation_types() {
        return ['about','mentions','published_by','published','source_of','related_to','owns','owned_by','operates_in','headquartered_in','subsidiary_of','supplies','criticized_by','supports','evidence_for'];
    }

    public static function normalize_label($label) {
        $label = strtolower(wp_strip_all_tags((string)$label));
        $label = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $label = preg_replace('/[^\p{L}\p{N}\s\.\-&]/u', ' ', $label);
        $label = preg_replace('/\s+/', ' ', $label);
        return sanitize_text_field(trim($label));
    }

    public static function external_ref($object_type, $object_id) {
        return sanitize_key($object_type) . ':' . absint($object_id);
    }

    public static function find_entity_by_external_ref($object_type, $object_id) {
        $items = get_posts([
            'post_type'=>'tsemou_entity', 'post_status'=>['publish','draft','pending','private'],
            'numberposts'=>1, 'meta_key'=>'_tsemou_entity_external_ref',
            'meta_value'=>self::external_ref($object_type, $object_id),
        ]);
        return !empty($items) ? absint($items[0]->ID) : 0;
    }

    public static function find_entity_by_label($label, $type='') {
        $meta_query = [['key'=>'_tsemou_entity_normalized_label','value'=>self::normalize_label($label),'compare'=>'=']];
        if ($type) $meta_query[] = ['key'=>'_tsemou_entity_type','value'=>sanitize_key($type),'compare'=>'='];
        $items = get_posts(['post_type'=>'tsemou_entity','post_status'=>['publish','draft','pending','private'],'numberposts'=>1,'meta_query'=>$meta_query]);
        return !empty($items) ? absint($items[0]->ID) : 0;
    }

    public static function create_entity($label, $type='unknown', $args=[]) {
        $label = sanitize_text_field($label);
        $type = sanitize_key($type);
        if (!$label) return new \WP_Error('missing_label','Entity label is required.');
        if (!in_array($type, self::entity_types(), true)) $type = 'unknown';
        if (!empty($args['object_type']) && !empty($args['object_id'])) {
            $existing = self::find_entity_by_external_ref($args['object_type'], $args['object_id']);
            if ($existing) return $existing;
        } else {
            $existing = self::find_entity_by_label($label, $type);
            if ($existing) return $existing;
        }
        $id = wp_insert_post(['post_type'=>'tsemou_entity','post_status'=>'publish','post_title'=>$label,'post_content'=>sanitize_textarea_field($args['description'] ?? '')], true);
        if (is_wp_error($id)) return $id;
        update_post_meta($id, '_tsemou_entity_type', $type);
        update_post_meta($id, '_tsemou_entity_label', $label);
        update_post_meta($id, '_tsemou_entity_normalized_label', self::normalize_label($label));
        update_post_meta($id, '_tsemou_entity_created_by_engine', '2.9.1');
        if (!empty($args['object_type']) && !empty($args['object_id'])) {
            update_post_meta($id, '_tsemou_entity_external_ref', self::external_ref($args['object_type'], $args['object_id']));
            update_post_meta($id, '_tsemou_entity_object_type', sanitize_key($args['object_type']));
            update_post_meta($id, '_tsemou_entity_object_id', absint($args['object_id']));
        }
        return absint($id);
    }

    public static function entity_from_object($object_type, $object_id, $label='', $type='') {
        $object_type = sanitize_key($object_type); $object_id = absint($object_id);
        if (!$object_type || !$object_id) return 0;
        $existing = self::find_entity_by_external_ref($object_type, $object_id);
        if ($existing) return $existing;
        if (!$label) $label = get_the_title($object_id) ?: ($object_type . ' #' . $object_id);
        if (!$type) {
            if ($object_type === 'company') $type = 'company';
            elseif ($object_type === 'tsemou_source') $type = 'source';
            elseif (in_array($object_type, ['evidence','tsemou_proof'], true)) $type = 'evidence';
            else $type = 'unknown';
        }
        $created = self::create_entity($label, $type, ['object_type'=>$object_type, 'object_id'=>$object_id]);
        return is_wp_error($created) ? 0 : absint($created);
    }

    public static function get_entity($entity_id) {
        $entity_id = absint($entity_id); $post = get_post($entity_id);
        if (!$post || $post->post_type !== 'tsemou_entity') return null;
        return [
            'entity_id'=>$entity_id,
            'label'=>get_post_meta($entity_id,'_tsemou_entity_label',true) ?: get_the_title($entity_id),
            'title'=>get_the_title($entity_id),
            'type'=>get_post_meta($entity_id,'_tsemou_entity_type',true) ?: 'unknown',
            'normalized_label'=>get_post_meta($entity_id,'_tsemou_entity_normalized_label',true),
            'external_ref'=>get_post_meta($entity_id,'_tsemou_entity_external_ref',true),
            'object_type'=>get_post_meta($entity_id,'_tsemou_entity_object_type',true),
            'object_id'=>absint(get_post_meta($entity_id,'_tsemou_entity_object_id',true)),
            'status'=>get_post_status($entity_id),
            'edit_link'=>get_edit_post_link($entity_id,''),
        ];
    }

    public static function find_relation($source_entity_id, $type, $target_entity_id) {
        $items = get_posts(['post_type'=>'tsemou_relation','post_status'=>['publish','draft','pending','private'],'numberposts'=>1,'meta_query'=>[
            'relation'=>'AND',
            ['key'=>'_tsemou_relation_source_entity_id','value'=>absint($source_entity_id),'compare'=>'='],
            ['key'=>'_tsemou_relation_target_entity_id','value'=>absint($target_entity_id),'compare'=>'='],
            ['key'=>'_tsemou_relation_type','value'=>sanitize_key($type),'compare'=>'='],
        ]]);
        return !empty($items) ? absint($items[0]->ID) : 0;
    }

    public static function create_relation($source_entity_id, $type, $target_entity_id, $args=[]) {
        $source_entity_id = absint($source_entity_id); $target_entity_id = absint($target_entity_id); $type = sanitize_key($type);
        if (!$source_entity_id || get_post_type($source_entity_id) !== 'tsemou_entity') return new \WP_Error('invalid_source','Invalid source entity.');
        if (!$target_entity_id || get_post_type($target_entity_id) !== 'tsemou_entity') return new \WP_Error('invalid_target','Invalid target entity.');
        if (!in_array($type, self::relation_types(), true)) $type = 'related_to';
        $existing = self::find_relation($source_entity_id, $type, $target_entity_id);
        if ($existing) return $existing;
        $source = self::get_entity($source_entity_id); $target = self::get_entity($target_entity_id);
        $title = ($source['label'] ?? $source_entity_id) . ' → ' . $type . ' → ' . ($target['label'] ?? $target_entity_id);
        $id = wp_insert_post(['post_type'=>'tsemou_relation','post_status'=>'publish','post_title'=>$title,'post_content'=>sanitize_textarea_field($args['description'] ?? '')], true);
        if (is_wp_error($id)) return $id;
        update_post_meta($id, '_tsemou_relation_source_entity_id', $source_entity_id);
        update_post_meta($id, '_tsemou_relation_target_entity_id', $target_entity_id);
        update_post_meta($id, '_tsemou_relation_type', $type);
        update_post_meta($id, '_tsemou_relation_confidence', isset($args['confidence']) ? floatval($args['confidence']) : 1.0);
        update_post_meta($id, '_tsemou_relation_created_by_engine', '2.9.1');
        if (!empty($args['evidence_id'])) update_post_meta($id, '_tsemou_relation_evidence_id', absint($args['evidence_id']));
        if (!empty($args['source_id'])) update_post_meta($id, '_tsemou_relation_source_id', absint($args['source_id']));
        return absint($id);
    }

    public static function get_relation($relation_id) {
        $relation_id = absint($relation_id); $post = get_post($relation_id);
        if (!$post || $post->post_type !== 'tsemou_relation') return null;
        $source_id = absint(get_post_meta($relation_id,'_tsemou_relation_source_entity_id',true));
        $target_id = absint(get_post_meta($relation_id,'_tsemou_relation_target_entity_id',true));
        return [
            'relation_id'=>$relation_id,
            'title'=>get_the_title($relation_id),
            'type'=>get_post_meta($relation_id,'_tsemou_relation_type',true),
            'source_entity_id'=>$source_id,
            'source_entity'=>self::get_entity($source_id),
            'target_entity_id'=>$target_id,
            'target_entity'=>self::get_entity($target_id),
            'confidence'=>floatval(get_post_meta($relation_id,'_tsemou_relation_confidence',true) ?: 1),
            'evidence_id'=>absint(get_post_meta($relation_id,'_tsemou_relation_evidence_id',true)),
            'source_id'=>absint(get_post_meta($relation_id,'_tsemou_relation_source_id',true)),
            'status'=>get_post_status($relation_id),
            'edit_link'=>get_edit_post_link($relation_id,''),
        ];
    }

    public static function relations_for_entity($entity_id, $direction='both', $limit=50) {
        $entity_id = absint($entity_id); if (!$entity_id) return [];
        $meta_query = ['relation'=>'OR'];
        if ($direction === 'out' || $direction === 'both') $meta_query[] = ['key'=>'_tsemou_relation_source_entity_id','value'=>$entity_id,'compare'=>'='];
        if ($direction === 'in' || $direction === 'both') $meta_query[] = ['key'=>'_tsemou_relation_target_entity_id','value'=>$entity_id,'compare'=>'='];
        $items = get_posts(['post_type'=>'tsemou_relation','post_status'=>['publish','draft','pending'],'numberposts'=>intval($limit),'meta_query'=>$meta_query,'orderby'=>'modified','order'=>'DESC']);
        $out = [];
        foreach ($items as $item) { $rel = self::get_relation($item->ID); if ($rel) $out[] = $rel; }
        return $out;
    }

    public static function graph_for_object($object_type, $object_id) {
        $entity_id = self::entity_from_object($object_type, $object_id);
        if (!$entity_id) return null;
        return ['entity'=>self::get_entity($entity_id), 'relations'=>self::relations_for_entity($entity_id, 'both', 100)];
    }

    public static function link_evidence_company_source($evidence_id) {
        $evidence_id = absint($evidence_id); $post = get_post($evidence_id);
        if (!$post || !in_array($post->post_type, ['evidence','tsemou_proof'], true)) return [];
        $created = [];
        $evidence_entity = self::entity_from_object($post->post_type, $evidence_id, get_the_title($evidence_id), 'evidence');
        $company_ids = [];
        if (class_exists('\TSEMOU\Modules\EvidenceEngine\Evidence_Engine')) {
            $normalized = \TSEMOU\Modules\EvidenceEngine\Evidence_Engine::get($evidence_id);
            $company_ids = $normalized['relations']['company_ids'] ?? [];
        }
        foreach ($company_ids as $company_id) {
            $company_entity = self::entity_from_object('company', $company_id, get_the_title($company_id), 'company');
            if ($evidence_entity && $company_entity) {
                $rel = self::create_relation($evidence_entity, 'about', $company_entity, ['evidence_id'=>$evidence_id]);
                if (!is_wp_error($rel)) $created[] = $rel;
            }
        }
        $source_id = absint(get_post_meta($evidence_id, '_tsemou_source_id', true));
        if (!$source_id && class_exists('\TSEMOU\Modules\SourceObject\Source_Object_Engine')) {
            $source_id = \TSEMOU\Modules\SourceObject\Source_Object_Engine::attach_evidence_to_source($evidence_id);
        }
        if ($source_id) {
            $source_entity = self::entity_from_object('tsemou_source', $source_id, get_the_title($source_id), 'source');
            if ($source_entity && $evidence_entity) {
                $rel = self::create_relation($source_entity, 'published', $evidence_entity, ['source_id'=>$source_id, 'evidence_id'=>$evidence_id]);
                if (!is_wp_error($rel)) $created[] = $rel;
            }
        }
        return $created;
    }

    public static function relation_signature($relation) {
        return implode('|', [
            intval(get_post_meta($relation->ID, '_tsemou_relation_source_entity_id', true)),
            sanitize_key(get_post_meta($relation->ID, '_tsemou_relation_type', true)),
            intval(get_post_meta($relation->ID, '_tsemou_relation_target_entity_id', true)),
        ]);
    }

    public static function count_entities_by_type($type) {
        $q = new \WP_Query(['post_type'=>'tsemou_entity','post_status'=>['publish','draft','pending','private'],'fields'=>'ids','posts_per_page'=>1,'meta_query'=>[['key'=>'_tsemou_entity_type','value'=>sanitize_key($type),'compare'=>'=']]]);
        return intval($q->found_posts);
    }

    public static function count_relations() {
        $q = new \WP_Query(['post_type'=>'tsemou_relation','post_status'=>['publish','draft','pending','private'],'fields'=>'ids','posts_per_page'=>1]);
        return intval($q->found_posts);
    }

    public static function graph_summary() {
        return [
            'companies'=>self::count_entities_by_type('company'),
            'sources'=>self::count_entities_by_type('source'),
            'evidence'=>self::count_entities_by_type('evidence'),
            'people'=>self::count_entities_by_type('person'),
            'organizations'=>self::count_entities_by_type('organization'),
            'events'=>self::count_entities_by_type('event'),
            'locations'=>self::count_entities_by_type('country') + self::count_entities_by_type('city'),
            'topics'=>self::count_entities_by_type('topic'),
            'relations'=>self::count_relations(),
        ];
    }

    public static function graph_health() {
        $entities = get_posts(['post_type'=>'tsemou_entity','post_status'=>['publish','draft','pending','private'],'numberposts'=>-1,'fields'=>'ids']);
        $relations = get_posts(['post_type'=>'tsemou_relation','post_status'=>['publish','draft','pending','private'],'numberposts'=>-1]);
        $broken = 0; $duplicates = 0; $seen = []; $connected = [];
        foreach ($relations as $relation) {
            $source = absint(get_post_meta($relation->ID,'_tsemou_relation_source_entity_id',true));
            $target = absint(get_post_meta($relation->ID,'_tsemou_relation_target_entity_id',true));
            if (!$source || get_post_type($source) !== 'tsemou_entity') $broken++;
            if (!$target || get_post_type($target) !== 'tsemou_entity') $broken++;
            if ($source) $connected[$source] = true;
            if ($target) $connected[$target] = true;
            $sig = self::relation_signature($relation);
            if (isset($seen[$sig])) $duplicates++; else $seen[$sig] = true;
        }
        $orphan = 0;
        foreach ($entities as $entity_id) if (empty($connected[$entity_id])) $orphan++;
        return ['entities'=>count($entities),'relations'=>count($relations),'broken_links'=>$broken,'duplicate_relations'=>$duplicates,'orphan_entities'=>$orphan,'status'=>($broken===0 && $duplicates===0 ? 'healthy' : 'warnings')];
    }

    public static function expanded_relation($relation_id, $focus_evidence_id=0) {
        $rel = self::get_relation($relation_id); if (!$rel) return null;
        $source = $rel['source_entity'] ?: [];
        $target = $rel['target_entity'] ?: [];
        $object = (!empty($target['type']) && $target['type'] !== 'evidence') ? $target : $source;
        return [
            'relation_id'=>$rel['relation_id'],
            'relation'=>$rel['type'],
            'source_entity_id'=>$rel['source_entity_id'],
            'source_entity_label'=>$source['label'] ?? '',
            'source_entity_type'=>$source['type'] ?? '',
            'target_entity_id'=>$rel['target_entity_id'],
            'target_entity_label'=>$target['label'] ?? '',
            'target_entity_type'=>$target['type'] ?? '',
            'object_type'=>$object['type'] ?? '',
            'object_id'=>$object['object_id'] ?? 0,
            'object_title'=>$object['label'] ?? '',
            'evidence_id'=>$focus_evidence_id ?: ($rel['evidence_id'] ?? 0),
            'confidence'=>$rel['confidence'],
        ];
    }

    public static function expanded_relations($relation_ids, $focus_evidence_id=0) {
        $out = [];
        foreach ((array)$relation_ids as $id) { $r = self::expanded_relation($id, $focus_evidence_id); if ($r) $out[] = $r; }
        return $out;
    }

    public static function validate_graph() {
        $errors = []; $warnings = []; $seen = [];
        $relations = get_posts(['post_type'=>'tsemou_relation','post_status'=>['publish','draft','pending','private'],'numberposts'=>-1]);
        foreach ($relations as $relation) {
            $source = absint(get_post_meta($relation->ID,'_tsemou_relation_source_entity_id',true));
            $target = absint(get_post_meta($relation->ID,'_tsemou_relation_target_entity_id',true));
            $type = get_post_meta($relation->ID,'_tsemou_relation_type',true);
            if (!$source || get_post_type($source) !== 'tsemou_entity') $errors[] = 'relation_'.$relation->ID.'_invalid_source';
            if (!$target || get_post_type($target) !== 'tsemou_entity') $errors[] = 'relation_'.$relation->ID.'_invalid_target';
            if (!in_array($type, self::relation_types(), true)) $warnings[] = 'relation_'.$relation->ID.'_unknown_type';
            $sig = self::relation_signature($relation);
            if (isset($seen[$sig])) $warnings[] = 'relation_'.$relation->ID.'_duplicate_relation'; else $seen[$sig] = true;
        }
        return ['valid'=>empty($errors),'errors'=>$errors,'warnings'=>$warnings,'relations_checked'=>count($relations),'validation'=>empty($errors) ? (empty($warnings) ? 'PASS' : 'WARNINGS') : 'FAIL'];
    }

    public function handle_create_entity() {
        if (!current_user_can('manage_options')) wp_die('Not allowed.');
        check_admin_referer('tsemou_kg_create_entity');
        $id = self::create_entity($_POST['entity_label'] ?? '', $_POST['entity_type'] ?? 'unknown');
        $url = admin_url('tools.php?page=tsemou-knowledge-graph');
        $url = is_wp_error($id) ? add_query_arg('tsemou_error', rawurlencode($id->get_error_message()), $url) : add_query_arg(['entity_id'=>$id,'created_entity'=>1], $url);
        wp_safe_redirect($url); exit;
    }

    public function handle_create_relation() {
        if (!current_user_can('manage_options')) wp_die('Not allowed.');
        check_admin_referer('tsemou_kg_create_relation');
        $id = self::create_relation(absint($_POST['source_entity_id'] ?? 0), $_POST['relation_type'] ?? 'related_to', absint($_POST['target_entity_id'] ?? 0));
        $url = admin_url('tools.php?page=tsemou-knowledge-graph');
        $url = is_wp_error($id) ? add_query_arg('tsemou_error', rawurlencode($id->get_error_message()), $url) : add_query_arg(['relation_id'=>$id,'created_relation'=>1], $url);
        wp_safe_redirect($url); exit;
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        $entity_id = absint($_GET['entity_id'] ?? 0);
        $relation_id = absint($_GET['relation_id'] ?? 0);
        $object_type = sanitize_key($_GET['object_type'] ?? '');
        $object_id = absint($_GET['object_id'] ?? 0);
        $evidence_id = absint($_GET['evidence_id'] ?? 0);
        $validation = self::validate_graph();
        ?>
        <div class="wrap">
            <h1>TSEMOU Knowledge Graph</h1>
            <p>v2.9.1 inspector upgrade. No AI, no extraction, no scraper yet.</p>
            <?php if (!empty($_GET['tsemou_error'])): ?><div class="notice notice-error"><p><?php echo esc_html($_GET['tsemou_error']); ?></p></div><?php endif; ?>
            <?php if (!empty($_GET['created_entity'])): ?><div class="notice notice-success"><p>Entity created.</p></div><?php endif; ?>
            <?php if (!empty($_GET['created_relation'])): ?><div class="notice notice-success"><p>Relation created.</p></div><?php endif; ?>

            <h2>Create Entity</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="background:#fff;border:1px solid #dbe3ef;padding:16px;border-radius:14px;margin-bottom:20px;">
                <?php wp_nonce_field('tsemou_kg_create_entity'); ?>
                <input type="hidden" name="action" value="tsemou_kg_create_entity">
                <input type="text" name="entity_label" placeholder="Entity label" style="width:260px;">
                <select name="entity_type"><?php foreach (self::entity_types() as $type): ?><option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option><?php endforeach; ?></select>
                <button class="button button-primary">Create Entity</button>
            </form>

            <h2>Create Relation</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="background:#fff;border:1px solid #dbe3ef;padding:16px;border-radius:14px;margin-bottom:20px;">
                <?php wp_nonce_field('tsemou_kg_create_relation'); ?>
                <input type="hidden" name="action" value="tsemou_kg_create_relation">
                <input type="number" name="source_entity_id" placeholder="Source Entity ID" style="width:160px;">
                <select name="relation_type"><?php foreach (self::relation_types() as $type): ?><option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option><?php endforeach; ?></select>
                <input type="number" name="target_entity_id" placeholder="Target Entity ID" style="width:160px;">
                <button class="button button-primary">Create Relation</button>
            </form>

            <h2>Inspect</h2>
            <form method="get" style="background:#fff;border:1px solid #dbe3ef;padding:16px;border-radius:14px;margin-bottom:20px;">
                <input type="hidden" name="page" value="tsemou-knowledge-graph">
                <label><strong>Entity ID</strong></label><input type="number" name="entity_id" value="<?php echo esc_attr($entity_id ?: ''); ?>" style="width:120px;">
                <label style="margin-left:12px;"><strong>Relation ID</strong></label><input type="number" name="relation_id" value="<?php echo esc_attr($relation_id ?: ''); ?>" style="width:120px;">
                <label style="margin-left:12px;"><strong>Object</strong></label><input type="text" name="object_type" value="<?php echo esc_attr($object_type); ?>" placeholder="company/evidence/tsemou_source" style="width:180px;"><input type="number" name="object_id" value="<?php echo esc_attr($object_id ?: ''); ?>" style="width:120px;">
                <label style="margin-left:12px;"><strong>Link Evidence ID</strong></label><input type="number" name="evidence_id" value="<?php echo esc_attr($evidence_id ?: ''); ?>" style="width:120px;">
                <button class="button button-primary">Inspect</button>
            </form>

            <h2>Engine Status</h2>
            <table class="widefat striped"><tbody>
                <tr><th>Engine class</th><td>loaded</td></tr>
                <tr><th>Entity post type</th><td>tsemou_entity</td></tr>
                <tr><th>Relation post type</th><td>tsemou_relation</td></tr>
                <tr><th>Graph valid</th><td><?php echo !empty($validation['valid']) ? 'yes' : 'no'; ?></td></tr>
                <tr><th>Relations checked</th><td><?php echo esc_html($validation['relations_checked']); ?></td></tr>
            </tbody></table>

            <h2>Knowledge Graph Summary</h2>
            <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(self::graph_summary(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>

            <h2>Graph Health</h2>
            <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(self::graph_health(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>

            <?php if ($evidence_id): ?><h2>Evidence Link Output</h2><pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(self::expanded_relations(self::link_evidence_company_source($evidence_id), $evidence_id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre><?php endif; ?>
            <?php if ($entity_id): ?><h2>Entity Output</h2><pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(self::get_entity($entity_id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre><h2>Entity Relations</h2><pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(self::relations_for_entity($entity_id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre><?php endif; ?>
            <?php if ($relation_id): ?><h2>Relation Output</h2><pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(self::get_relation($relation_id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre><?php endif; ?>
            <?php if ($object_type && $object_id): ?><h2>Object Graph Output</h2><pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode(self::graph_for_object($object_type, $object_id), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre><?php endif; ?>

            <h2>Graph Validation</h2>
            <pre style="background:#071226;color:#e2e8f0;padding:14px;border-radius:12px;overflow:auto;"><?php echo esc_html(wp_json_encode($validation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
        <?php
    }
}
