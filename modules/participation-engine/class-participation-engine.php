<?php
namespace TSEMOU\Modules\ParticipationEngine;

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/contracts/interface-action-subscriber.php';

require_once __DIR__ . '/actions/class-action-status.php';
require_once __DIR__ . '/actions/class-action-context.php';
require_once __DIR__ . '/actions/class-action-metadata.php';
require_once __DIR__ . '/actions/class-action-envelope.php';
require_once __DIR__ . '/actions/class-action-result.php';
require_once __DIR__ . '/actions/class-tsemit-action-definition.php';

require_once __DIR__ . '/class-action-journal.php';
require_once __DIR__ . '/class-action-registry.php';
require_once __DIR__ . '/class-action-validator.php';
require_once __DIR__ . '/class-action-authorizer.php';
require_once __DIR__ . '/class-subscriber-registry.php';
require_once __DIR__ . '/class-action-dispatcher.php';
require_once __DIR__ . '/class-action-gateway.php';

require_once __DIR__ . '/subscribers/class-trust-subscriber.php';
require_once __DIR__ . '/subscribers/class-proof-subscriber.php';
require_once __DIR__ . '/subscribers/class-knowledge-graph-subscriber.php';
require_once __DIR__ . '/subscribers/class-timeline-subscriber.php';
require_once __DIR__ . '/subscribers/class-community-subscriber.php';
require_once __DIR__ . '/subscribers/class-notifications-subscriber.php';
require_once __DIR__ . '/subscribers/class-future-ai-subscriber.php';

class Participation_Engine {
    private static $instance = null;

    private $action_registry;
    private $subscriber_registry;
    private $action_gateway;

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        $this->boot_foundation();
    }

    private function boot_foundation() {
        $this->action_registry = new Action_Registry();
        $this->action_registry->register_defaults();

        $this->subscriber_registry = new Subscriber_Registry();
        $this->subscriber_registry->register(new Subscribers\Trust_Subscriber());
        $this->subscriber_registry->register(new Subscribers\Proof_Subscriber());
        $this->subscriber_registry->register(new Subscribers\Knowledge_Graph_Subscriber());
        $this->subscriber_registry->register(new Subscribers\Timeline_Subscriber());
        $this->subscriber_registry->register(new Subscribers\Community_Subscriber());
        $this->subscriber_registry->register(new Subscribers\Notifications_Subscriber());
        $this->subscriber_registry->register(new Subscribers\Future_AI_Subscriber());

        $validator = new Action_Validator($this->action_registry);
        $authorizer = new Action_Authorizer();
        $dispatcher = new Action_Dispatcher($this->subscriber_registry);

        $this->action_gateway = new Action_Gateway($this->action_registry, $validator, $authorizer, $dispatcher);
    }

    public function submitAction($normalized_action = []) {
        $normalized_action = is_array($normalized_action) ? $normalized_action : [];
        $normalized_action['metadata'] = $this->ensure_action_identifiers($normalized_action['metadata'] ?? []);

        Action_Journal::record('submit_action', [
            'action_type' => sanitize_key((string) ($normalized_action['action_type'] ?? '')),
            'metadata' => $normalized_action['metadata'],
        ]);

        return $this->action_gateway->receive($normalized_action);
    }

    private function ensure_action_identifiers($metadata = []) {
        $metadata = is_array($metadata) ? $metadata : [];

        if (empty($metadata['action_uuid'])) {
            $metadata['action_uuid'] = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('action_', true);
        }

        if (empty($metadata['correlation_id'])) {
            $metadata['correlation_id'] = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('corr_', true);
        }

        if (empty($metadata['lifecycle_id'])) {
            $metadata['lifecycle_id'] = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('life_', true);
        }

        return $metadata;
    }

    public function action_registry() {
        return $this->action_registry;
    }

    public function subscriber_registry() {
        return $this->subscriber_registry;
    }

    public function action_gateway() {
        return $this->action_gateway;
    }
}
