<?php
namespace TSEMOU\Modules\DiscoveryOrchestrator;

if (!defined('ABSPATH')) exit;

class Discovery_Orchestrator {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 26);
        add_filter('cron_schedules', [__CLASS__, 'cron_schedules']);
        add_action('tsemou_phase_a_runtime_tick', [__CLASS__, 'runtime_tick']);
    }

    public static function option_key($name) {
        return 'tsemou_discovery_orchestrator_' . sanitize_key($name);
    }

    public static function cron_schedules($schedules) {
        if (!isset($schedules['tsemou_minute'])) {
            $schedules['tsemou_minute'] = [
                'interval' => 60,
                'display' => 'Every Minute (TSEMOU Runtime)'
            ];
        }
        return $schedules;
    }

    public static function runtime_state() {
        $state = get_option(self::option_key('runtime_state'), []);
        if (!is_array($state)) $state = [];

        return array_merge([
            'status' => 'STOPPED',
            'started_at' => '',
            'paused_at' => '',
            'stopped_at' => '',
            'last_tick_at' => '',
            'last_message' => '',
            'current_worker' => '',
            'total_ticks' => 0,
            'total_processed' => 0,
            'batch_size' => 10,
        ], $state);
    }

    public static function save_runtime_state(array $state) {
        update_option(self::option_key('runtime_state'), $state, false);
    }

    public static function start_runtime($batch_size = 10) {
        $state = self::runtime_state();
        $state['status'] = 'RUNNING';
        $state['started_at'] = $state['started_at'] ?: current_time('mysql');
        $state['paused_at'] = '';
        $state['stopped_at'] = '';
        $state['last_message'] = 'Runtime started.';
        $state['batch_size'] = max(1, min(25, intval($batch_size)));
        self::save_runtime_state($state);

        if (!wp_next_scheduled('tsemou_phase_a_runtime_tick')) {
            wp_schedule_event(time() + 60, 'tsemou_minute', 'tsemou_phase_a_runtime_tick');
        }

        self::add_log('runtime_service', 'Runtime service started.', ['batch_size' => $state['batch_size']]);
        return self::runtime_tick(true);
    }

    public static function pause_runtime() {
        $state = self::runtime_state();
        $state['status'] = 'PAUSED';
        $state['paused_at'] = current_time('mysql');
        $state['last_message'] = 'Runtime paused.';
        self::save_runtime_state($state);
        self::add_log('runtime_service', 'Runtime service paused.');
        return $state;
    }

    public static function stop_runtime() {
        $state = self::runtime_state();
        $state['status'] = 'STOPPED';
        $state['stopped_at'] = current_time('mysql');
        $state['current_worker'] = '';
        $state['last_message'] = 'Runtime stopped.';
        self::save_runtime_state($state);

        $timestamp = wp_next_scheduled('tsemou_phase_a_runtime_tick');
        while ($timestamp) {
            wp_unschedule_event($timestamp, 'tsemou_phase_a_runtime_tick');
            $timestamp = wp_next_scheduled('tsemou_phase_a_runtime_tick');
        }

        self::add_log('runtime_service', 'Runtime service stopped.');
        return $state;
    }

    public static function runtime_tick($manual = false) {
        $state = self::runtime_state();

        if (($state['status'] ?? 'STOPPED') !== 'RUNNING') {
            return ['success' => false, 'message' => 'Runtime is not running.'];
        }

        $stats = self::queue_stats();
        if (intval($stats['pending'] ?? 0) <= 0) {
            $state['status'] = 'STOPPED';
            $state['last_tick_at'] = current_time('mysql');
            $state['last_message'] = 'Queue empty. Runtime stopped.';
            $state['current_worker'] = '';
            self::save_runtime_state($state);
            self::add_log('runtime_service', 'Queue empty. Runtime stopped automatically.');
            return ['success' => false, 'message' => 'Queue empty. Runtime stopped.'];
        }

        $limit = max(1, min(25, intval($state['batch_size'] ?? 10)));
        $processed = 0;
        $last_message = '';

        for ($i = 0; $i < $limit; $i++) {
            $next = self::next_pending_item();
            if (!$next) {
                $last_message = 'No pending queue item found.';
                break;
            }

            $state['current_worker'] = $next['current_engine'] ?? $next['type'] ?? '';
            self::save_runtime_state($state);

            $result = self::run_next_item();
            $last_message = $result['message'] ?? '';

            if (empty($result['success'])) {
                break;
            }
            $processed++;
        }

        $state = self::runtime_state();
        $state['last_tick_at'] = current_time('mysql');
        $state['last_message'] = $last_message ?: 'Runtime tick completed.';
        $state['total_ticks'] = intval($state['total_ticks'] ?? 0) + 1;
        $state['total_processed'] = intval($state['total_processed'] ?? 0) + $processed;
        $state['current_worker'] = '';
        self::save_runtime_state($state);

        self::add_log('runtime_service', 'Runtime tick completed.', [
            'manual' => $manual ? 'yes' : 'no',
            'processed' => $processed,
            'message' => $state['last_message']
        ]);

        return ['success' => true, 'message' => 'Runtime processed ' . $processed . ' jobs.', 'processed' => $processed];
    }

    public static function next_pending_item() {
        foreach (self::queue() as $item) {
            if (($item['status'] ?? 'pending') === 'pending') {
                return $item;
            }
        }
        return null;
    }

    public static function default_pipeline() {
        return [
            'company_discovery' => [
                'label' => 'Company Discovery',
                'status' => 'ready',
                'phase' => 'A',
                'depends_on' => ['configuration_os'],
                'next_engine' => 'source_discovery',
                'description' => 'Selects company discovery tasks from Configuration OS.'
            ],
            'source_discovery' => [
                'label' => 'Source Discovery',
                'status' => 'ready',
                'phase' => 'A',
                'depends_on' => ['company_discovery'],
                'next_engine' => 'scraping_engine',
                'description' => 'Resolves candidate sources and queues source fetch jobs. No scraping yet.'
            ],
            'scraping_engine' => [
                'label' => 'Scraping Engine',
                'status' => 'ready',
                'phase' => 'A',
                'depends_on' => ['source_discovery'],
                'next_engine' => 'automatic_evidence_creation',
                'description' => 'Fetches source content and creates Raw Evidence records. No AI analysis.'
            ],
            'automatic_evidence_creation' => [
                'label' => 'Automatic Evidence Creation',
                'status' => 'ready',
                'phase' => 'A',
                'depends_on' => ['scraping_engine'],
                'next_engine' => 'automatic_linking',
                'description' => 'Creates Evidence Draft runtime files from acquired data. No permanent database write.'
            ],
            'automatic_linking' => [
                'label' => 'Automatic Linking',
                'status' => 'ready',
                'phase' => 'A',
                'depends_on' => ['automatic_evidence_creation'],
                'next_engine' => 'knowledge_graph_update',
                'description' => 'Links companies, sources and evidence.'
            ],
            'knowledge_graph_update' => [
                'label' => 'Knowledge Graph Update',
                'status' => 'ready',
                'phase' => 'A',
                'depends_on' => ['automatic_linking'],
                'next_engine' => null,
                'description' => 'Updates graph relations after acquisition and linking.'
            ],
            'promotion_evaluation' => [
                'label' => 'Promotion Evaluation',
                'status' => 'ready',
                'phase' => 'B',
                'depends_on' => ['story_processing'],
                'next_engine' => null,
                'description' => 'Evaluates story promotion cadence and updates Living Case state using Policy and Event Intelligence outputs.'
            ],
        ];
    }

    public static function pipeline() {
        $default = self::default_pipeline();
        $stored = get_option(self::option_key('pipeline'), []);

        if (!is_array($stored)) {
            return $default;
        }

        $clean = [];
        foreach ($default as $key => $step) {
            $clean[$key] = isset($stored[$key]) && is_array($stored[$key])
                ? array_merge($step, $stored[$key])
                : $step;
        }

        return $clean;
    }

    public static function queue() {
        $queue = get_option(self::option_key('queue'), []);
        return is_array($queue) ? $queue : [];
    }

    public static function save_queue($queue) {
        update_option(self::option_key('queue'), is_array($queue) ? array_values($queue) : [], false);
    }

    public static function logs() {
        $logs = get_option(self::option_key('logs'), []);
        return is_array($logs) ? $logs : [];
    }

    public static function add_log($type, $message, $context = []) {
        $logs = self::logs();

        array_unshift($logs, [
            'time' => current_time('mysql'),
            'type' => sanitize_key($type),
            'message' => sanitize_text_field($message),
            'context' => is_array($context) ? $context : []
        ]);

        update_option(self::option_key('logs'), array_slice($logs, 0, 300), false);
    }

    private static function emit_event($event, array $payload = []) {
        $event = sanitize_key($event);
        if (!$event) return;

        do_action('tsemou_' . $event, $payload);
        self::add_log('event', 'Event emitted: ' . $event, [
            'keys' => array_keys($payload),
        ]);
    }

    public static function make_queue_key($engine, $payload) {
        $parts = [
            $engine,
            $payload['story_id'] ?? '',
            $payload['country'] ?? '',
            $payload['industry'] ?? '',
            $payload['wave'] ?? '',
            $payload['rank_band'] ?? '',
            $payload['source_id'] ?? '',
            $payload['evidence_id'] ?? '',
        ];

        return md5(implode('|', array_map('strval', $parts)));
    }

    public static function enqueue($engine, array $payload = [], $priority = 0) {
        $pipeline = self::pipeline();
        if (!isset($pipeline[$engine]) && $engine !== 'story_processing') {
            self::add_log('error', 'Cannot enqueue unknown Phase A engine.', ['engine' => $engine]);
            return false;
        }

        $queue = self::queue();
        $queue_key = self::make_queue_key($engine, $payload);

        foreach ($queue as $item) {
            if (($item['queue_key'] ?? '') === $queue_key && in_array(($item['status'] ?? 'pending'), ['pending', 'running'], true)) {
                return false;
            }
        }

        $queue[] = array_merge($payload, [
            'queue_key' => $queue_key,
            'type' => $engine,
            'current_engine' => $engine,
            'priority' => intval($priority),
            'status' => 'pending',
            'attempts' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        usort($queue, function($a, $b) {
            return intval($b['priority'] ?? 0) <=> intval($a['priority'] ?? 0);
        });

        self::save_queue($queue);
        self::add_log('queue', 'Queued Phase A engine step.', ['engine' => $engine, 'queue_key' => $queue_key]);
        return true;
    }

    public static function enqueue_story_processing($story_id, array $payload = [], $priority = 0) {
        $payload['story_id'] = absint($story_id);
        return self::enqueue('story_processing', $payload, $priority);
    }

    public static function seed_company_discovery_queue($limit = 100) {
        if (!class_exists('\\TSEMOU\\Modules\\ConfigurationOS\\Configuration_OS')) {
            self::add_log('error', 'Configuration OS not available. Cannot seed discovery queue.');
            return 0;
        }

        $tasks = \TSEMOU\Modules\ConfigurationOS\Configuration_OS::discovery_wave_tasks();
        $added = 0;

        foreach ($tasks as $task) {
            if ($added >= $limit) break;

            $payload = [
                'country' => $task['country'] ?? '',
                'country_name' => $task['country_name'] ?? '',
                'industry' => $task['industry'] ?? '',
                'industry_label' => $task['industry_label'] ?? '',
                'wave' => $task['wave'] ?? '',
                'rank_band' => $task['rank_band'] ?? '',
            ];

            if (self::enqueue('company_discovery', $payload, intval($task['priority'] ?? 0))) {
                $added++;
            }
        }

        self::add_log('queue', 'Seeded Phase A company discovery queue.', ['added' => $added]);
        return $added;
    }

    public static function queue_stats() {
        $queue = self::queue();
        $stats = ['total'=>count($queue),'pending'=>0,'running'=>0,'completed'=>0,'failed'=>0];

        foreach ($queue as $item) {
            $status = $item['status'] ?? 'pending';
            if (!isset($stats[$status])) $stats[$status] = 0;
            $stats[$status]++;
        }

        return $stats;
    }

    public static function run($limit = 1) {
        $limit = max(1, min(25, intval($limit)));
        $results = [];

        self::add_log('runtime', 'Phase A runtime started.', ['limit' => $limit]);

        for ($i = 0; $i < $limit; $i++) {
            $result = self::run_next_item();
            $results[] = $result;

            if (empty($result['success'])) {
                break;
            }
        }

        return $results;
    }

    public static function run_next_item() {
        $queue = self::queue();

        if (empty($queue)) {
            self::add_log('runtime', 'Queue is empty.');
            return ['success' => false, 'message' => 'Queue is empty.'];
        }

        foreach ($queue as $index => $item) {
            if (($item['status'] ?? 'pending') !== 'pending') {
                continue;
            }

            $engine = $item['current_engine'] ?? $item['type'] ?? '';
            $queue[$index]['status'] = 'running';
            $queue[$index]['attempts'] = intval($item['attempts'] ?? 0) + 1;
            $queue[$index]['started_at'] = current_time('mysql');
            $queue[$index]['updated_at'] = current_time('mysql');
            self::save_queue($queue);

            self::add_log('runtime', 'Phase A queue item started.', ['engine' => $engine, 'queue_key' => $item['queue_key'] ?? '']);

            $result = self::execute_engine($queue[$index]);
            $queue = self::queue();

            if (!empty($result['success'])) {
                $queue[$index]['status'] = 'completed';
                $queue[$index]['completed_at'] = current_time('mysql');
                $queue[$index]['updated_at'] = current_time('mysql');
                $queue[$index]['result_message'] = $result['message'] ?? '';
                self::save_queue($queue);

                $next = $result['next_engine'] ?? self::next_engine($engine);
                if ($next) {
                    $payload = $result['payload'] ?? self::payload_from_item($queue[$index]);
                    self::enqueue($next, $payload, intval($queue[$index]['priority'] ?? 0));
                }

                self::add_log('runtime', 'Phase A queue item completed.', [
                    'engine' => $engine,
                    'next_engine' => $next ?: 'done',
                    'message' => $result['message'] ?? ''
                ]);

                return [
                    'success' => true,
                    'message' => $result['message'] ?? 'One queue item completed.',
                    'next_engine' => $next,
                    'item' => $queue[$index]
                ];
            }

            $queue[$index]['status'] = 'failed';
            $queue[$index]['error'] = $result['message'] ?? 'Unknown runtime error.';
            $queue[$index]['updated_at'] = current_time('mysql');
            self::save_queue($queue);

            self::add_log('error', 'Phase A queue item failed.', [
                'engine' => $engine,
                'error' => $queue[$index]['error']
            ]);

            return [
                'success' => false,
                'message' => $queue[$index]['error'],
                'item' => $queue[$index]
            ];
        }

        self::add_log('runtime', 'No pending queue item found.');
        return ['success' => false, 'message' => 'No pending queue item found.'];
    }

    public static function next_engine($engine) {
        $pipeline = self::pipeline();
        return $pipeline[$engine]['next_engine'] ?? null;
    }

    public static function payload_from_item($item) {
        $skip = ['queue_key','type','current_engine','status','attempts','created_at','updated_at','started_at','completed_at','result_message','error'];
        $payload = [];
        foreach ($item as $key => $value) {
            if (!in_array($key, $skip, true)) {
                $payload[$key] = $value;
            }
        }
        return $payload;
    }

    public static function execute_engine($item) {
        $engine = $item['current_engine'] ?? $item['type'] ?? '';

        switch ($engine) {
            case 'story_processing':
                return self::execute_story_processing($item);
            case 'promotion_evaluation':
                return self::execute_promotion_evaluation($item);
            case 'company_discovery':
                return self::execute_company_discovery($item);
            case 'source_discovery':
                return self::execute_source_discovery($item);
            case 'scraping_engine':
                return self::execute_scraping_engine($item);
            case 'automatic_evidence_creation':
                return self::execute_automatic_evidence_creation($item);
            case 'automatic_linking':
                return self::execute_automatic_linking($item);
            case 'knowledge_graph_update':
                return self::execute_knowledge_graph_update($item);
        }

        return ['success' => false, 'message' => 'No Phase A executor available for engine: ' . $engine];
    }

    public static function execute_story_processing($item) {
        $payload = self::payload_from_item($item);
        $story_id = absint($payload['story_id'] ?? 0);

        if ($story_id <= 0) {
            return [
                'success' => false,
                'message' => 'Story processing requires a valid story_id.'
            ];
        }

        if (!class_exists('\\TSEMOU\\Modules\\EvidenceProcessingEngine\\Evidence_Processing_Engine')) {
            $file = TSEMOU_CORE_PATH . 'modules/evidence-processing-engine/class-evidence-processing-engine.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (!class_exists('\\TSEMOU\\Modules\\EvidenceProcessingEngine\\Evidence_Processing_Engine')) {
            return [
                'success' => false,
                'message' => 'Evidence Processing Engine is not loaded.'
            ];
        }

        $result = \\TSEMOU\\Modules\\EvidenceProcessingEngine\\Evidence_Processing_Engine::process_story($story_id);

        if (empty($result['status']) || $result['status'] === 'error') {
            self::add_log('story_processing', 'Story processing failed.', [
                'story_id' => $story_id,
                'errors' => $result['errors'] ?? []
            ]);

            return [
                'success' => false,
                'message' => !empty($result['errors']) ? implode('; ', (array) $result['errors']) : 'Story processing failed.'
            ];
        }

        $payload['story_processing_status'] = $result['status'] ?? 'success';
        $payload['proof_id'] = intval($result['proof_id'] ?? 0);
        $payload['evidence'] = $result['evidence'] ?? [];
        $payload['relationships'] = $result['relationships'] ?? [];
        $payload['graph'] = $result['graph'] ?? [];

        if (empty($payload['graph']['success'])) {
            self::add_log('story_processing', 'Story processing blocked: Knowledge Graph update failed.', [
                'story_id' => $story_id,
                'proof_id' => $payload['proof_id'],
                'graph_errors' => $payload['graph']['errors'] ?? [],
            ]);

            return [
                'success' => false,
                'message' => 'Story processing failed: Knowledge Graph update did not complete.',
            ];
        }

        $relationship_count = is_array($payload['relationships']) ? count($payload['relationships']) : 0;
        if ($relationship_count > 0) {
            self::emit_event('entity_linked', [
                'story_id' => $story_id,
                'proof_id' => $payload['proof_id'],
                'relationship_count' => $relationship_count,
            ]);
        }

        $validation_warnings = [];
        $validation_score = 0;
        if ($payload['proof_id'] > 0 && class_exists('\\TSEMOU\\Modules\\EvidenceEngine\\Evidence_Engine')) {
            $normalized = \TSEMOU\Modules\EvidenceEngine\Evidence_Engine::get($payload['proof_id']);
            $validation_warnings = is_array($normalized['warnings'] ?? null) ? $normalized['warnings'] : [];
            $validation_score = max(0, 100 - (count($validation_warnings) * 20));
            $payload['evidence_validation'] = [
                'score' => $validation_score,
                'warnings' => $validation_warnings,
            ];

            self::emit_event('evidence_validated', [
                'story_id' => $story_id,
                'proof_id' => $payload['proof_id'],
                'validation_score' => $validation_score,
                'warning_count' => count($validation_warnings),
            ]);
        }

        $community_scores = [];
        $community_vote_count = 0;
        if (class_exists('\\TSEMOU\\Modules\\CompanyEngine\\Company_Engine')) {
            $connected_companies = \TSEMOU\Modules\CompanyEngine\Company_Engine::get_connected_company_ids($story_id);
            if (class_exists('\\TSEMOU\\Modules\\TrustEngine\\Trust_Engine')) {
                foreach ((array) $connected_companies as $company_id) {
                    $company_id = absint($company_id);
                    if ($company_id <= 0) continue;

                    $community_scores[$company_id] = floatval(\TSEMOU\Modules\TrustEngine\Trust_Engine::calculate_community_score($company_id));
                    $votes = get_post_meta($company_id, '_tsemou_community_votes', true);
                    if (is_array($votes)) {
                        $community_vote_count += count($votes);
                    }
                }
            }
        }

        if (!empty($community_scores)) {
            $community_avg = round(array_sum($community_scores) / max(1, count($community_scores)), 2);
            $payload['community_intelligence'] = [
                'company_count' => count($community_scores),
                'community_score_average' => $community_avg,
                'vote_count' => intval($community_vote_count),
            ];

            self::emit_event('community_intelligence_evaluated', [
                'story_id' => $story_id,
                'company_count' => count($community_scores),
                'community_score_average' => $community_avg,
                'vote_count' => intval($community_vote_count),
            ]);

            do_action('tsemou_lifecycle_stage', [
                'story_id' => $story_id,
                'stage' => 'Community Intelligence',
                'source' => 'discovery_orchestrator',
                'context' => [
                    'company_count' => count($community_scores),
                    'community_score_average' => $community_avg,
                    'vote_count' => intval($community_vote_count),
                ],
            ]);
        }

        $trust_updates = [];
        if ($payload['proof_id'] > 0 && class_exists('\\TSEMOU\\Modules\\TrustEngine\\Trust_Engine')) {
            $company_ids = \TSEMOU\Modules\TrustEngine\Trust_Engine::get_company_ids_for_evidence($payload['proof_id']);
            foreach ($company_ids as $company_id) {
                $company_id = absint($company_id);
                if ($company_id <= 0) continue;
                $trust_updates[$company_id] = \TSEMOU\Modules\TrustEngine\Trust_Engine::recalculate_company_trust($company_id);
            }

            if (!empty($trust_updates)) {
                self::emit_event('trust_updated', [
                    'story_id' => $story_id,
                    'proof_id' => $payload['proof_id'],
                    'company_count' => count($trust_updates),
                    'company_scores' => $trust_updates,
                ]);

                do_action('tsemou_lifecycle_stage', [
                    'story_id' => $story_id,
                    'stage' => 'Trust Evaluation',
                    'source' => 'discovery_orchestrator',
                    'context' => [
                        'company_count' => count($trust_updates),
                        'validation_score' => $validation_score,
                    ],
                ]);
            }
        }

        self::emit_event('evidence_created', [
            'story_id' => $story_id,
            'proof_id' => $payload['proof_id'],
            'relationship_count' => $relationship_count,
        ]);
        do_action('tsemou_lifecycle_stage', [
            'story_id' => $story_id,
            'stage' => 'Evidence Growth',
            'source' => 'discovery_orchestrator',
            'context' => [
                'proof_id' => $payload['proof_id'],
                'relationship_count' => $relationship_count,
            ],
        ]);

        if (!empty($payload['graph']['success'])) {
            self::emit_event('knowledge_graph_updated', [
                'story_id' => $story_id,
                'proof_id' => $payload['proof_id'],
                'relationship_count' => $relationship_count,
                'error_count' => is_array($payload['graph']['errors'] ?? null) ? count($payload['graph']['errors']) : 0,
            ]);

            do_action('tsemou_lifecycle_stage', [
                'story_id' => $story_id,
                'stage' => 'Knowledge Graph Update',
                'source' => 'discovery_orchestrator',
                'context' => [
                    'proof_id' => $payload['proof_id'],
                    'relationship_count' => $relationship_count,
                ],
            ]);
        }

        self::add_log('story_processing', 'Story processed into canonical evidence.', [
            'story_id' => $story_id,
            'proof_id' => $payload['proof_id'],
            'warnings' => $result['warnings'] ?? [],
            'errors' => $result['errors'] ?? []
        ]);

        return [
            'success' => true,
            'message' => 'Story processing completed. Canonical proof created or updated.',
            'next_engine' => 'promotion_evaluation',
            'payload' => $payload,
            'result' => $result
        ];
    }

    public static function execute_promotion_evaluation($item) {
        $payload = self::payload_from_item($item);
        $story_id = absint($payload['story_id'] ?? 0);

        if ($story_id <= 0) {
            return [
                'success' => false,
                'message' => 'Promotion evaluation requires a valid story_id.'
            ];
        }

        if (!class_exists('\\TSEMOU\\Modules\\EventIntelligence\\Event_Intelligence_Orchestrator')) {
            $file = TSEMOU_CORE_PATH . 'modules/event-intelligence/class-event-intelligence-orchestrator.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (!class_exists('\\TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')) {
            $file = TSEMOU_CORE_PATH . 'modules/policy-engine/class-policy-engine.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (!class_exists('\\TSEMOU\\Modules\\EventIntelligence\\Event_Intelligence_Orchestrator')) {
            return [
                'success' => false,
                'message' => 'Event Intelligence Orchestrator is not loaded for promotion evaluation.'
            ];
        }

        $snapshot = \TSEMOU\Modules\EventIntelligence\Event_Intelligence_Orchestrator::instance()->run(['story_id' => $story_id]);
        $state = method_exists($snapshot, 'to_array') ? $snapshot->to_array() : [];
        $ranking = is_array($state['story_ranking'] ?? null) ? $state['story_ranking'] : [];
        $importance = is_array($state['importance'] ?? null) ? $state['importance'] : [];
        $trust = is_array($state['trust'] ?? null) ? $state['trust'] : [];
        $score = floatval($ranking['score'] ?? 0);
        $importance_score = floatval($importance['score'] ?? 0);
        $trust_score = floatval($trust['score'] ?? 0);

        self::emit_event('public_importance_evaluated', [
            'story_id' => $story_id,
            'importance_score' => $importance_score,
            'importance_band' => sanitize_text_field($importance['band'] ?? ''),
        ]);

        self::emit_event('story_ranked', [
            'story_id' => $story_id,
            'score' => $score,
            'priority' => sanitize_text_field($ranking['priority'] ?? 'low'),
            'trust_score' => $trust_score,
            'importance_score' => $importance_score,
        ]);

        do_action('tsemou_lifecycle_stage', [
            'story_id' => $story_id,
            'stage' => 'Public Importance',
            'source' => 'discovery_orchestrator',
            'context' => [
                'importance_score' => $importance_score,
            ],
        ]);

        do_action('tsemou_lifecycle_stage', [
            'story_id' => $story_id,
            'stage' => 'Community Value',
            'source' => 'discovery_orchestrator',
            'context' => [
                'trust_score' => $trust_score,
                'importance_score' => $importance_score,
            ],
        ]);

        do_action('tsemou_lifecycle_stage', [
            'story_id' => $story_id,
            'stage' => 'Ranking',
            'source' => 'discovery_orchestrator',
            'context' => [
                'score' => $score,
                'priority' => sanitize_text_field($ranking['priority'] ?? 'low'),
            ],
        ]);

        $top_min = class_exists('\\TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')
            ? floatval(\TSEMOU\Modules\PolicyEngine\Policy_Engine::get('promotion.top_candidate_min_score', 85))
            : 85.0;
        $second_min = class_exists('\\TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')
            ? floatval(\TSEMOU\Modules\PolicyEngine\Policy_Engine::get('promotion.second_candidate_min_score', 70))
            : 70.0;
        $third_min = class_exists('\\TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')
            ? floatval(\TSEMOU\Modules\PolicyEngine\Policy_Engine::get('promotion.third_candidate_min_score', 55))
            : 55.0;
        $min_importance = class_exists('\\TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')
            ? floatval(\TSEMOU\Modules\PolicyEngine\Policy_Engine::get('promotion.min_public_importance_score', 50))
            : 50.0;

        if ($importance_score < $min_importance) {
            update_post_meta($story_id, '_tsemou_promotion_status', 'not_eligible_importance');
            self::add_log('promotion_evaluation', 'Story blocked by public importance gate.', [
                'story_id' => $story_id,
                'importance_score' => $importance_score,
                'minimum_required' => $min_importance,
            ]);

            return [
                'success' => true,
                'message' => 'Promotion evaluation completed: story below minimum public importance threshold.',
                'next_engine' => null,
                'payload' => $payload,
            ];
        }

        $slot = '';
        $interval_hours = 0;
        if ($score >= $top_min) {
            $slot = 'top';
            $interval_hours = class_exists('\\TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')
                ? intval(\TSEMOU\Modules\PolicyEngine\Policy_Engine::get('promotion.top_candidate_interval_hours', 1))
                : 1;
        } elseif ($score >= $second_min) {
            $slot = 'second';
            $interval_hours = class_exists('\\TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')
                ? intval(\TSEMOU\Modules\PolicyEngine\Policy_Engine::get('promotion.second_candidate_interval_hours', 2))
                : 2;
        } elseif ($score >= $third_min) {
            $slot = 'third';
            $interval_hours = class_exists('\\TSEMOU\\Modules\\PolicyEngine\\Policy_Engine')
                ? intval(\TSEMOU\Modules\PolicyEngine\Policy_Engine::get('promotion.third_candidate_interval_hours', 4))
                : 4;
        }

        update_post_meta($story_id, '_tsemou_story_ranking_score', $score);
        update_post_meta($story_id, '_tsemou_story_ranking_priority', sanitize_text_field($ranking['priority'] ?? 'low'));

        if ($slot === '') {
            update_post_meta($story_id, '_tsemou_promotion_status', 'not_eligible');
            self::add_log('promotion_evaluation', 'Story is below promotion thresholds.', [
                'story_id' => $story_id,
                'score' => $score,
            ]);

            return [
                'success' => true,
                'message' => 'Promotion evaluation completed: story is below MVT promotion thresholds.',
                'next_engine' => null,
                'payload' => $payload,
            ];
        }

        $now_ts = time();
        $last_surface = get_post_meta($story_id, '_tsemou_promotion_last_surface_at', true);
        $last_ts = $last_surface ? strtotime($last_surface) : 0;
        $interval_seconds = max(1, $interval_hours) * HOUR_IN_SECONDS;

        if ($last_ts > 0 && ($now_ts - $last_ts) < $interval_seconds) {
            $remaining = $interval_seconds - ($now_ts - $last_ts);
            update_post_meta($story_id, '_tsemou_promotion_status', 'waiting_cadence');

            self::add_log('promotion_evaluation', 'Story promotion deferred by cadence policy.', [
                'story_id' => $story_id,
                'slot' => $slot,
                'score' => $score,
                'remaining_seconds' => $remaining,
            ]);

            return [
                'success' => true,
                'message' => 'Promotion evaluation completed: cadence window not elapsed yet.',
                'next_engine' => null,
                'payload' => $payload,
            ];
        }

        $now_mysql = current_time('mysql');
        update_post_meta($story_id, '_tsemou_promotion_status', 'promoted');
        update_post_meta($story_id, '_tsemou_promotion_candidate_slot', $slot);
        update_post_meta($story_id, '_tsemou_promotion_last_surface_at', $now_mysql);
        if (!get_post_meta($story_id, '_tsemou_story_promoted_at', true)) {
            update_post_meta($story_id, '_tsemou_story_promoted_at', $now_mysql);
        }
        update_post_meta($story_id, '_tsemou_living_case_state', 'active');

        $event_payload = [
            'story_id' => $story_id,
            'slot' => $slot,
            'score' => $score,
            'interval_hours' => $interval_hours,
            'rank_priority' => sanitize_text_field($ranking['priority'] ?? 'low'),
            'promoted_at' => $now_mysql,
        ];

        self::add_log('event', 'Event emitted: story_promoted', [
            'keys' => array_keys($event_payload),
        ]);
        self::add_log('event', 'Event emitted: living_case_updated', [
            'keys' => array_keys($event_payload),
        ]);

        do_action('tsemou_lifecycle_stage', [
            'story_id' => $story_id,
            'stage' => 'Promotion',
            'source' => 'discovery_orchestrator',
            'context' => [
                'slot' => $slot,
                'interval_hours' => $interval_hours,
                'score' => $score,
            ],
        ]);

        do_action('tsemou_story_promoted', $story_id, $event_payload);
        do_action('tsemou_living_case_updated', $story_id, $event_payload);

        self::add_log('promotion_evaluation', 'Story promoted to Living Case candidate.', $event_payload);

        return [
            'success' => true,
            'message' => 'Promotion evaluation completed: story promoted and Living Case state refreshed.',
            'next_engine' => null,
            'payload' => array_merge($payload, $event_payload),
        ];
    }

    public static function execute_company_discovery($item) {
        self::add_log('company_discovery', 'Company Discovery step executed.', self::payload_from_item($item));
        return [
            'success' => true,
            'message' => 'Company Discovery completed. Source Discovery queued.',
            'next_engine' => 'source_discovery',
            'payload' => self::payload_from_item($item)
        ];
    }

    public static function execute_source_discovery($item) {
        $payload = self::payload_from_item($item);

        if (!class_exists('\\TSEMOU\\Modules\\SourceDiscovery\\Source_Discovery')) {
            $file = TSEMOU_CORE_PATH . 'modules/source-discovery/class-source-discovery.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (!class_exists('\\TSEMOU\\Modules\\SourceDiscovery\\Source_Discovery')) {
            return [
                'success' => false,
                'message' => 'Source Discovery Engine is not loaded.'
            ];
        }

        $discovery = \TSEMOU\Modules\SourceDiscovery\Source_Discovery::discover($payload);
        $payloads = $discovery['payloads'] ?? [];
        $count = 0;

        foreach ($payloads as $source_payload) {
            $priority = intval($source_payload['priority'] ?? $payload['priority'] ?? 0);
            if (self::enqueue('scraping_engine', $source_payload, $priority)) {
                $count++;
            }
        }

        $payload['source_discovery_status'] = 'candidate_sources_resolved';
        $payload['source_count'] = intval($discovery['count'] ?? count($payloads));
        $payload['queued_source_fetch_jobs'] = $count;

        self::emit_event('source_discovered', [
            'country' => $payload['country'] ?? '',
            'industry' => $payload['industry'] ?? '',
            'source_count' => $payload['source_count'],
            'queued_source_fetch_jobs' => $count,
        ]);

        self::add_log('source_discovery', 'Source Discovery resolved candidate sources.', $payload);

        return [
            'success' => true,
            'message' => 'Source Discovery completed. ' . $count . ' source fetch jobs queued.',
            'next_engine' => null,
            'payload' => $payload
        ];
    }

    public static function execute_scraping_engine($item) {
        $payload = self::payload_from_item($item);

        if (!class_exists('\\TSEMOU\\Modules\\ScrapingEngine\\Scraping_Engine')) {
            $file = TSEMOU_CORE_PATH . 'modules/scraping-engine/class-scraping-engine.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (!class_exists('\\TSEMOU\\Modules\\ScrapingEngine\\Scraping_Engine')) {
            return [
                'success' => false,
                'message' => 'Scraping Engine is not loaded.'
            ];
        }

        $result = \TSEMOU\Modules\ScrapingEngine\Scraping_Engine::process($payload);

        if (empty($result['success'])) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Scraping Engine failed.'
            ];
        }

        $payload['scraping_status'] = 'raw_evidence_created';
        $payload['raw_id'] = $result['raw_id'] ?? '';
        $payload['raw_status'] = $result['record']['status'] ?? '';
        $payload['http_status'] = $result['record']['metadata']['http_status'] ?? 0;

        self::emit_event('article_imported', [
            'raw_id' => $payload['raw_id'],
            'source_url' => $payload['source_url'] ?? '',
            'http_status' => $payload['http_status'],
        ]);

        self::add_log('scraping_engine', 'Scraping Engine created Raw Evidence.', $payload);

        return [
            'success' => true,
            'message' => ($result['message'] ?? 'Scraping Engine completed.') . ' Automatic Evidence Creation queued.',
            'next_engine' => 'automatic_evidence_creation',
            'payload' => $payload
        ];
    }

    public static function execute_automatic_evidence_creation($item) {
        $payload = self::payload_from_item($item);

        if (!class_exists('\TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation')) {
            $file = TSEMOU_CORE_PATH . 'modules/automatic-evidence-creation/class-automatic-evidence-creation.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (!class_exists('\TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation')) {
            return [
                'success' => false,
                'message' => 'Automatic Evidence Creation Engine is not loaded.'
            ];
        }

        try {
            $result = \TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation::process($payload);
        } catch (\Throwable $e) {
            self::add_log('automatic_evidence_creation', 'Automatic Evidence Creation fatal safely caught.', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return [
                'success' => false,
                'message' => 'Automatic Evidence Creation fatal safely caught: ' . $e->getMessage(),
            ];
        }

        if (empty($result['success'])) {
            self::add_log('automatic_evidence_creation', 'Automatic Evidence Creation failed safely.', [
                'message' => $result['message'] ?? 'Automatic Evidence Creation failed.',
                'payload' => $payload,
            ]);
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Automatic Evidence Creation failed.'
            ];
        }

        $payload['evidence_creation_status'] = 'draft_evidence_ready';
        $payload['draft_id'] = $result['draft_id'] ?? '';
        $payload['evidence_storage_mode'] = 'runtime_file';
        $payload['database_write'] = 'no';

        self::emit_event('evidence_draft_created', [
            'draft_id' => $payload['draft_id'],
            'raw_id' => $payload['raw_id'] ?? '',
            'storage_mode' => $payload['evidence_storage_mode'],
        ]);

        self::add_log('automatic_evidence_creation', 'Automatic Evidence Creation created Evidence Draft.', $payload);
        return [
            'success' => true,
            'message' => ($result['message'] ?? 'Automatic Evidence Creation completed.') . ' Automatic Linking queued.',
            'next_engine' => 'automatic_linking',
            'payload' => $payload
        ];
    }

    public static function execute_automatic_linking($item) {
        $payload = self::payload_from_item($item);

        if (!class_exists('\\TSEMOU\\Modules\\AutomaticLinking\\Automatic_Linking')) {
            $file = TSEMOU_CORE_PATH . 'modules/automatic-linking/class-automatic-linking.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (!class_exists('\\TSEMOU\\Modules\\AutomaticLinking\\Automatic_Linking')) {
            return [
                'success' => false,
                'message' => 'Automatic Linking Engine is not loaded.'
            ];
        }

        $result = \TSEMOU\Modules\AutomaticLinking\Automatic_Linking::process($payload);

        if (empty($result['success'])) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Automatic Linking failed.'
            ];
        }

        $payload['linking_status'] = 'candidate_links_ready';
        $payload['link_id'] = $result['link_id'] ?? '';
        $payload['company_id'] = $result['record']['company_id'] ?? ($payload['company_id'] ?? 0);
        $payload['source_id'] = $result['record']['source_id'] ?? ($payload['source_id'] ?? 0);

        self::emit_event('company_linked', [
            'link_id' => $payload['link_id'],
            'company_id' => intval($payload['company_id']),
            'source_id' => intval($payload['source_id']),
            'draft_id' => $payload['draft_id'] ?? '',
        ]);

        self::add_log('automatic_linking', 'Automatic Linking created candidate links.', $payload);
        return [
            'success' => true,
            'message' => ($result['message'] ?? 'Automatic Linking completed.') . ' Knowledge Graph Update queued.',
            'next_engine' => 'knowledge_graph_update',
            'payload' => $payload
        ];
    }

    public static function execute_knowledge_graph_update($item) {
        $payload = self::payload_from_item($item);

        if (!class_exists('\\TSEMOU\\Modules\\AutomaticLinking\\Automatic_Linking')) {
            $file = TSEMOU_CORE_PATH . 'modules/automatic-linking/class-automatic-linking.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (class_exists('\\TSEMOU\\Modules\\AutomaticLinking\\Automatic_Linking')) {
            $kg = \TSEMOU\Modules\AutomaticLinking\Automatic_Linking::update_knowledge_graph($payload);
            $payload['knowledge_graph_status'] = !empty($kg['updated']) ? 'graph_context_relation_updated' : 'graph_update_skipped_needs_review';
            $payload['relationship_id'] = $kg['relationship_id'] ?? '';

            if (!empty($kg['updated'])) {
                self::emit_event('knowledge_graph_updated', [
                    'relationship_id' => $payload['relationship_id'],
                    'company_id' => intval($payload['company_id'] ?? 0),
                    'source_id' => intval($payload['source_id'] ?? 0),
                    'draft_id' => $payload['draft_id'] ?? '',
                ]);
            }

            self::add_log('knowledge_graph_update', $kg['message'] ?? 'Knowledge Graph Update completed.', $payload);
            return [
                'success' => !empty($kg['success']),
                'message' => ($kg['message'] ?? 'Knowledge Graph Update completed.') . ' Phase A acquisition chain completed for this task.',
                'next_engine' => null,
                'payload' => $payload
            ];
        }

        $payload['knowledge_graph_status'] = 'graph_update_ready';
        self::add_log('knowledge_graph_update', 'Knowledge Graph Update step executed.', $payload);
        return [
            'success' => true,
            'message' => 'Phase A acquisition chain completed for this task.',
            'next_engine' => null,
            'payload' => $payload
        ];
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Discovery Orchestrator',
            'Discovery Orchestrator',
            'manage_options',
            'tsemou-discovery-orchestrator',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        if (!empty($_POST['tsemou_orchestrator_nonce']) && wp_verify_nonce($_POST['tsemou_orchestrator_nonce'], 'tsemou_orchestrator_action')) {
            $action = sanitize_text_field($_POST['orchestrator_action'] ?? '');

            if ($action === 'seed_queue') {
                $added = self::seed_company_discovery_queue(100);
                echo '<div class="notice notice-success"><p>Phase A queue seeded. Added ' . esc_html($added) . ' tasks.</p></div>';
            }

            if ($action === 'run_once') {
                $result = self::run_next_item();
                echo '<div class="notice notice-info"><p>' . esc_html($result['message'] ?? 'Runtime executed.') . '</p></div>';
            }

            if ($action === 'run_five') {
                $results = self::run(5);
                $last = end($results);
                echo '<div class="notice notice-info"><p>Runtime executed up to 5 steps. Last result: ' . esc_html($last['message'] ?? 'Done.') . '</p></div>';
            }

            if ($action === 'start_runtime') {
                $result = self::start_runtime(10);
                echo '<div class="notice notice-success"><p>' . esc_html($result['message'] ?? 'Runtime started.') . '</p></div>';
            }

            if ($action === 'pause_runtime') {
                self::pause_runtime();
                echo '<div class="notice notice-warning"><p>Runtime paused.</p></div>';
            }

            if ($action === 'stop_runtime') {
                self::stop_runtime();
                echo '<div class="notice notice-warning"><p>Runtime stopped.</p></div>';
            }

            if ($action === 'runtime_tick') {
                $result = self::runtime_tick(true);
                echo '<div class="notice notice-info"><p>' . esc_html($result['message'] ?? 'Runtime tick executed.') . '</p></div>';
            }

            if ($action === 'reset_pipeline') {
                delete_option(self::option_key('pipeline'));
                self::add_log('pipeline', 'Pipeline reset to Phase A only.');
                echo '<div class="notice notice-success"><p>Pipeline reset to Phase A only.</p></div>';
            }

            if ($action === 'clear_queue') {
                delete_option(self::option_key('queue'));
                self::add_log('queue', 'Queue cleared by admin.');
                echo '<div class="notice notice-warning"><p>Queue cleared.</p></div>';
            }

            if ($action === 'clear_logs') {
                delete_option(self::option_key('logs'));
                echo '<div class="notice notice-warning"><p>Logs cleared.</p></div>';
            }
        }

        $runtime = self::runtime_state();
        $pipeline = self::pipeline();
        $queue = self::queue();
        $stats = self::queue_stats();
        $logs = self::logs();
        ?>
        <div class="wrap tsemou-orchestrator">
            <h1>TSEMOU Discovery Orchestrator</h1>
            <p><strong>Phase A acquisition remains intact:</strong> coordinates acquisition flow and also accepts story-processing bridge jobs. No AI, no Trust, no Reputation, no frontend decisions.</p>

            <h2>Actions</h2>
            <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:18px;">
                <?php wp_nonce_field('tsemou_orchestrator_action', 'tsemou_orchestrator_nonce'); ?>
                <button class="button button-primary" name="orchestrator_action" value="seed_queue">Seed Company Discovery Queue</button>
                <button class="button button-secondary" name="orchestrator_action" value="run_once">Run One Step</button>
                <button class="button button-secondary" name="orchestrator_action" value="run_five">Run Five Steps</button>
                <button class="button button-primary" name="orchestrator_action" value="start_runtime">▶ Start Runtime</button>
                <button class="button" name="orchestrator_action" value="pause_runtime">⏸ Pause Runtime</button>
                <button class="button" name="orchestrator_action" value="stop_runtime">⏹ Stop Runtime</button>
                <button class="button" name="orchestrator_action" value="runtime_tick">Run Runtime Tick</button>
                <button class="button" name="orchestrator_action" value="reset_pipeline">Reset Phase A Pipeline</button>
                <button class="button" name="orchestrator_action" value="clear_queue">Clear Queue</button>
                <button class="button" name="orchestrator_action" value="clear_logs">Clear Logs</button>
            </form>

            <h2>Runtime Service</h2>
            <table class="widefat striped" style="max-width:900px;">
                <tbody>
                    <tr><th>Status</th><td><strong><?php echo esc_html($runtime['status'] ?? 'STOPPED'); ?></strong></td></tr>
                    <tr><th>Batch Size</th><td><?php echo esc_html($runtime['batch_size'] ?? 10); ?></td></tr>
                    <tr><th>Current Worker</th><td><?php echo esc_html($runtime['current_worker'] ?: '—'); ?></td></tr>
                    <tr><th>Last Tick</th><td><?php echo esc_html($runtime['last_tick_at'] ?: '—'); ?></td></tr>
                    <tr><th>Last Message</th><td><?php echo esc_html($runtime['last_message'] ?: '—'); ?></td></tr>
                    <tr><th>Total Ticks</th><td><?php echo esc_html($runtime['total_ticks'] ?? 0); ?></td></tr>
                    <tr><th>Total Processed</th><td><?php echo esc_html($runtime['total_processed'] ?? 0); ?></td></tr>
                    <tr><th>Next Scheduled Tick</th><td><?php $next_tick = wp_next_scheduled('tsemou_phase_a_runtime_tick'); echo esc_html($next_tick ? date_i18n('Y-m-d H:i:s', $next_tick) : 'not scheduled'); ?></td></tr>
                    <tr><th>Memory Usage</th><td><?php echo esc_html(size_format(memory_get_usage(true))); ?></td></tr>
                </tbody>
            </table>

            <h2>Phase A Pipeline</h2>
            <table class="widefat striped">
                <thead><tr><th>Engine Step</th><th>Status</th><th>Depends On</th><th>Next</th><th>Description</th></tr></thead>
                <tbody>
                    <?php foreach ($pipeline as $key => $step): ?>
                        <tr>
                            <td><strong><?php echo esc_html($step['label'] ?? $key); ?></strong><br><code><?php echo esc_html($key); ?></code></td>
                            <td><?php echo esc_html($step['status'] ?? ''); ?></td>
                            <td><?php echo esc_html(implode(', ', $step['depends_on'] ?? [])); ?></td>
                            <td><?php echo esc_html($step['next_engine'] ?? '—'); ?></td>
                            <td><?php echo esc_html($step['description'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Queue Stats</h2>
            <table class="widefat striped" style="max-width:700px;">
                <tbody>
                    <?php foreach ($stats as $key => $value): ?>
                        <tr><th><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></th><td><?php echo esc_html($value); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Queue Preview</h2>
            <table class="widefat striped">
                <thead><tr><th>Engine</th><th>Source</th><th>Country</th><th>Industry</th><th>Wave</th><th>Rank Band</th><th>Priority</th><th>Status</th><th>Message</th></tr></thead>
                <tbody>
                    <?php if (empty($queue)): ?>
                        <tr><td colspan="9">Queue is empty. Click “Seed Company Discovery Queue”.</td></tr>
                    <?php endif; ?>
                    <?php foreach (array_slice($queue, 0, 80) as $item): ?>
                        <tr>
                            <td><code><?php echo esc_html($item['current_engine'] ?? $item['type'] ?? ''); ?></code></td>
                            <td><?php echo esc_html($item['source_label'] ?? $item['source_id'] ?? '—'); ?></td>
                            <td><?php echo esc_html($item['country_name'] ?? $item['country'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['industry_label'] ?? $item['industry'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['wave'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['rank_band'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['priority'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['status'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['result_message'] ?? $item['error'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Logs</h2>
            <table class="widefat striped">
                <thead><tr><th>Time</th><th>Type</th><th>Message</th><th>Context</th></tr></thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="4">No logs yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach (array_slice($logs, 0, 80) as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log['time'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['type'] ?? ''); ?></td>
                            <td><?php echo esc_html($log['message'] ?? ''); ?></td>
                            <td><code><?php echo esc_html(wp_json_encode($log['context'] ?? [])); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
