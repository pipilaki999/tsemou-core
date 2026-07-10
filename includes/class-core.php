<?php
namespace TSEMOU;
if (!defined('ABSPATH')) exit;
class Core {
    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    private function __construct() {
        $this->load_modules();
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
    }
    private function load_modules() {
        $this->trace_require('includes/class-runtime-storage.php');
        $this->trace_require('modules/configuration-os/class-configuration-os.php');
        $this->trace_require('modules/discovery-orchestrator/class-discovery-orchestrator.php');
        $this->trace_require('modules/source-discovery/class-source-discovery.php');
        $this->trace_require('modules/scraping-engine/class-scraping-engine.php');
        $this->trace_require('modules/automatic-evidence-creation/class-automatic-evidence-creation.php');
        $this->trace_require('modules/automatic-linking/class-automatic-linking.php');
        $this->trace_require('modules/entity-engine/class-entity-engine.php');
        $this->trace_require('modules/relationship-engine/class-relationship-engine.php');
        $this->trace_require('modules/entity-evidence-links/class-entity-evidence-links.php');
        $this->trace_require('modules/knowledge-graph/class-knowledge-graph.php');
        $this->trace_require('modules/knowledge-graph/class-knowledge-graph-update-engine.php');
        $this->trace_require('modules/company-discovery/class-company-discovery.php');
        $this->trace_require('modules/company-sensor/class-company-sensor.php');
        $this->trace_require('modules/company-section-engine/class-company-section-engine.php');
        $this->trace_require('modules/evidence-engine/class-evidence-engine.php');
        $this->trace_require('modules/evidence-processing-engine/class-evidence-processing-engine.php');
        $this->trace_require('modules/source-intelligence/class-source-intelligence-engine.php');
        $this->trace_require('modules/discovery-engine/class-discovery-engine.php');
        $this->trace_require('modules/company-intelligence/class-company-intelligence-engine.php');
        $this->trace_require('modules/source-object/class-source-object-engine.php');
        $this->trace_require('modules/developer-console/class-developer-console.php');
        $this->trace_require('modules/company-intelligence/class-company-intelligence.php');
        $this->trace_require('modules/homepage-beta/class-homepage-beta.php');
        $this->trace_require('modules/policy-engine/class-policy-engine.php');
        $this->trace_require('modules/proof-engine/class-proof-engine.php');
        $this->trace_require('modules/company-engine/class-company-engine.php');
        $this->trace_require('modules/event-identity/class-event-signature.php');
        $this->trace_require('modules/event-identity/class-event-candidate.php');
        $this->trace_require('modules/event-identity/class-event-identity-matcher.php');
        $this->trace_require('modules/event-identity/class-event-identity-engine.php');
        $this->trace_require('modules/event-identity/class-candidate-normalizer.php');
        $this->trace_require('modules/event-identity/class-alias-resolver.php');
        $this->trace_require('modules/event-identity/class-identity-signature.php');
        $this->trace_require('modules/event-identity/class-identity-matcher.php');
        $this->trace_require('modules/event-identity/class-identity-repository.php');
        $this->trace_require('modules/event-resolver/class-event-resolver.php');
        $this->trace_require('modules/event-resolver/class-event-similarity.php');
        $this->trace_require('modules/event-resolver/class-event-merge.php');
        $this->trace_require('modules/event-resolver/class-event-decision.php');
        $this->trace_require('modules/event-timeline/class-event-timeline-node.php');
        $this->trace_require('modules/event-timeline/class-event-sequence.php');
        $this->trace_require('modules/event-timeline/class-event-timeline.php');
        $this->trace_require('modules/event-intelligence/class-event-policy-adapter.php');
        $this->trace_require('modules/event-intelligence/class-event-public-importance-service.php');
        $this->trace_require('modules/event-intelligence/class-event-trust-adapter.php');
        $this->trace_require('modules/event-intelligence/class-event-story-ranking-service.php');
        $this->trace_require('modules/event-intelligence/class-event-graph-adapter.php');
        $this->trace_require('modules/event-intelligence/class-event-intelligence-result.php');
        $this->trace_require('modules/event-intelligence/class-event-intelligence-orchestrator.php');
        $this->trace_require('modules/story/class-story-module.php');
        $this->trace_require('modules/trust-engine/class-trust-engine.php');
        if (class_exists('\TSEMOU\Modules\ConfigurationOS\Configuration_OS') && method_exists('\TSEMOU\Modules\ConfigurationOS\Configuration_OS', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\ConfigurationOS\Configuration_OS'); }
        if (class_exists('\TSEMOU\Modules\DiscoveryOrchestrator\Discovery_Orchestrator') && method_exists('\TSEMOU\Modules\DiscoveryOrchestrator\Discovery_Orchestrator', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\DiscoveryOrchestrator\Discovery_Orchestrator'); }
        if (class_exists('\TSEMOU\Modules\SourceDiscovery\Source_Discovery') && method_exists('\TSEMOU\Modules\SourceDiscovery\Source_Discovery', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\SourceDiscovery\Source_Discovery'); }
        if (class_exists('\TSEMOU\Modules\ScrapingEngine\Scraping_Engine') && method_exists('\TSEMOU\Modules\ScrapingEngine\Scraping_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\ScrapingEngine\Scraping_Engine'); }
        if (class_exists('\TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation') && method_exists('\TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation'); }
        if (class_exists('\TSEMOU\Modules\AutomaticLinking\Automatic_Linking') && method_exists('\TSEMOU\Modules\AutomaticLinking\Automatic_Linking', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\AutomaticLinking\Automatic_Linking'); }
        if (class_exists('\TSEMOU\Modules\EntityEngine\Entity_Engine') && method_exists('\TSEMOU\Modules\EntityEngine\Entity_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\EntityEngine\Entity_Engine'); }
        if (class_exists('\TSEMOU\Modules\RelationshipEngine\Relationship_Engine') && method_exists('\TSEMOU\Modules\RelationshipEngine\Relationship_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\RelationshipEngine\Relationship_Engine'); }
        if (class_exists('\TSEMOU\Modules\EntityEvidenceLinks\Entity_Evidence_Links') && method_exists('\TSEMOU\Modules\EntityEvidenceLinks\Entity_Evidence_Links', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\EntityEvidenceLinks\Entity_Evidence_Links'); }
        if (class_exists('\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph') && method_exists('\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph'); }
        if (class_exists('\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph_Update_Engine') && method_exists('\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph_Update_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph_Update_Engine'); }
        if (class_exists('\TSEMOU\Modules\CompanyDiscovery\Company_Discovery') && method_exists('\TSEMOU\Modules\CompanyDiscovery\Company_Discovery', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\CompanyDiscovery\Company_Discovery'); }
        if (class_exists('\TSEMOU\Modules\CompanySensor\Company_Sensor') && method_exists('\TSEMOU\Modules\CompanySensor\Company_Sensor', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\CompanySensor\Company_Sensor'); }
        if (class_exists('\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine') && method_exists('\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine'); }
        if (class_exists('\TSEMOU\Modules\DeveloperConsole\Developer_Console') && method_exists('\TSEMOU\Modules\DeveloperConsole\Developer_Console', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\DeveloperConsole\Developer_Console'); }
        if (class_exists('\TSEMOU\Modules\EvidenceEngine\Evidence_Engine') && method_exists('\TSEMOU\Modules\EvidenceEngine\Evidence_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\EvidenceEngine\Evidence_Engine'); }
        if (class_exists('\TSEMOU\Modules\EvidenceProcessingEngine\Evidence_Processing_Engine') && method_exists('\TSEMOU\Modules\EvidenceProcessingEngine\Evidence_Processing_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\EvidenceProcessingEngine\Evidence_Processing_Engine'); }
        if (class_exists('\TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine') && method_exists('\TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine'); }
        if (class_exists('\TSEMOU\Modules\DiscoveryEngine\Discovery_Engine') && method_exists('\TSEMOU\Modules\DiscoveryEngine\Discovery_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\DiscoveryEngine\Discovery_Engine'); }
        if (class_exists('\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine') && method_exists('\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine'); }
        if (class_exists('\TSEMOU\Modules\SourceObject\Source_Object_Engine') && method_exists('\TSEMOU\Modules\SourceObject\Source_Object_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\SourceObject\Source_Object_Engine'); }
        if (class_exists('\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence') && method_exists('\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence'); }
        if (class_exists('\TSEMOU\Modules\HomepageBeta\Homepage_Beta') && method_exists('\TSEMOU\Modules\HomepageBeta\Homepage_Beta', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\HomepageBeta\Homepage_Beta'); }
        if (class_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine') && method_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\PolicyEngine\Policy_Engine'); }
        if (class_exists('\TSEMOU\Modules\ProofEngine\Proof_Engine') && method_exists('\TSEMOU\Modules\ProofEngine\Proof_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\ProofEngine\Proof_Engine'); }
        if (class_exists('\TSEMOU\Modules\CompanyEngine\Company_Engine') && method_exists('\TSEMOU\Modules\CompanyEngine\Company_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\CompanyEngine\Company_Engine'); }
        if (class_exists('\TSEMOU\Modules\EventIdentity\Event_Identity_Engine') && method_exists('\TSEMOU\Modules\EventIdentity\Event_Identity_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\EventIdentity\Event_Identity_Engine'); }
        if (class_exists('\TSEMOU\Modules\EventResolver\Event_Resolver') && method_exists('\TSEMOU\Modules\EventResolver\Event_Resolver', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\EventResolver\Event_Resolver'); }
        if (class_exists('\TSEMOU\Modules\EventTimeline\Event_Timeline') && method_exists('\TSEMOU\Modules\EventTimeline\Event_Timeline', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\EventTimeline\Event_Timeline'); }
        if (class_exists('\TSEMOU\Modules\EventIntelligence\Event_Intelligence_Orchestrator') && method_exists('\TSEMOU\Modules\EventIntelligence\Event_Intelligence_Orchestrator', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\EventIntelligence\Event_Intelligence_Orchestrator'); }
        if (class_exists('\TSEMOU\Modules\Story\Story_Module') && method_exists('\TSEMOU\Modules\Story\Story_Module', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\Story\Story_Module'); }
        if (class_exists('\TSEMOU\Modules\TrustEngine\Trust_Engine') && method_exists('\TSEMOU\Modules\TrustEngine\Trust_Engine', 'instance')) { $this->trace_instance('\\TSEMOU\Modules\TrustEngine\Trust_Engine'); }
    }
    private function trace($message) {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[TSEMOU_ACTIVATION_TRACE] ' . $message);
        }
    }

    private function trace_require($relative_path) {
        $this->trace('require:start ' . $relative_path);
        require_once TSEMOU_CORE_PATH . $relative_path;
        $this->trace('require:ok ' . $relative_path);
    }

    private function trace_instance($fqcn) {
        $this->trace('instance:start ' . $fqcn);
        $fqcn::instance();
        $this->trace('instance:ok ' . $fqcn);
    }

    public function admin_assets($hook) {
        wp_enqueue_style('tsemou-core-admin', TSEMOU_CORE_URL . 'assets/css/admin.css', [], TSEMOU_CORE_VERSION);
        wp_enqueue_script('tsemou-core-admin', TSEMOU_CORE_URL . 'assets/js/admin.js', [], TSEMOU_CORE_VERSION, true);
    }
    public function frontend_assets() {
        if (is_singular('company') || is_post_type_archive('company')) {
            wp_enqueue_style('tsemou-core-frontend', TSEMOU_CORE_URL . 'assets/css/admin.css', [], TSEMOU_CORE_VERSION);
        }
    }

}

