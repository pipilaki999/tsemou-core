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
        require_once TSEMOU_CORE_PATH . 'includes/class-runtime-storage.php';
        require_once TSEMOU_CORE_PATH . 'modules/configuration-os/class-configuration-os.php';
        require_once TSEMOU_CORE_PATH . 'modules/discovery-orchestrator/class-discovery-orchestrator.php';
        require_once TSEMOU_CORE_PATH . 'modules/source-discovery/class-source-discovery.php';
        require_once TSEMOU_CORE_PATH . 'modules/scraping-engine/class-scraping-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/automatic-evidence-creation/class-automatic-evidence-creation.php';
        require_once TSEMOU_CORE_PATH . 'modules/automatic-linking/class-automatic-linking.php';
        require_once TSEMOU_CORE_PATH . 'modules/entity-engine/class-entity-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/entity-evidence-links/class-entity-evidence-links.php';
        require_once TSEMOU_CORE_PATH . 'modules/knowledge-graph/class-knowledge-graph.php';
        require_once TSEMOU_CORE_PATH . 'modules/company-discovery/class-company-discovery.php';
        require_once TSEMOU_CORE_PATH . 'modules/company-sensor/class-company-sensor.php';
        require_once TSEMOU_CORE_PATH . 'modules/company-section-engine/class-company-section-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/evidence-engine/class-evidence-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/source-intelligence/class-source-intelligence-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/discovery-engine/class-discovery-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/company-intelligence/class-company-intelligence-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/source-object/class-source-object-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/developer-console/class-developer-console.php';
        require_once TSEMOU_CORE_PATH . 'modules/company-intelligence/class-company-intelligence.php';
        require_once TSEMOU_CORE_PATH . 'modules/policy-engine/class-policy-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/proof-engine/class-proof-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/company-engine/class-company-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/event-identity/class-event-signature.php';
        require_once TSEMOU_CORE_PATH . 'modules/event-identity/class-event-candidate.php';
        require_once TSEMOU_CORE_PATH . 'modules/event-identity/class-event-identity-matcher.php';
        require_once TSEMOU_CORE_PATH . 'modules/event-identity/class-event-identity-engine.php';
        require_once TSEMOU_CORE_PATH . 'modules/event-identity/class-candidate-normalizer.php';
        require_once TSEMOU_CORE_PATH . 'modules/event-identity/class-alias-resolver.php';
        require_once TSEMOU_CORE_PATH . 'modules/event-identity/class-identity-signature.php';
        require_once TSEMOU_CORE_PATH . 'modules/event-identity/class-identity-matcher.php';
        require_once TSEMOU_CORE_PATH . 'modules/event-identity/class-identity-repository.php';
        require_once TSEMOU_CORE_PATH . 'modules/story/class-story-module.php';
        require_once TSEMOU_CORE_PATH . 'modules/trust-engine/class-trust-engine.php';
        if (class_exists('\TSEMOU\Modules\ConfigurationOS\Configuration_OS') && method_exists('\TSEMOU\Modules\ConfigurationOS\Configuration_OS', 'instance')) { \TSEMOU\Modules\ConfigurationOS\Configuration_OS::instance(); }
        if (class_exists('\TSEMOU\Modules\DiscoveryOrchestrator\Discovery_Orchestrator') && method_exists('\TSEMOU\Modules\DiscoveryOrchestrator\Discovery_Orchestrator', 'instance')) { \TSEMOU\Modules\DiscoveryOrchestrator\Discovery_Orchestrator::instance(); }
        if (class_exists('\TSEMOU\Modules\SourceDiscovery\Source_Discovery') && method_exists('\TSEMOU\Modules\SourceDiscovery\Source_Discovery', 'instance')) { \TSEMOU\Modules\SourceDiscovery\Source_Discovery::instance(); }
        if (class_exists('\TSEMOU\Modules\ScrapingEngine\Scraping_Engine') && method_exists('\TSEMOU\Modules\ScrapingEngine\Scraping_Engine', 'instance')) { \TSEMOU\Modules\ScrapingEngine\Scraping_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation') && method_exists('\TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation', 'instance')) { \TSEMOU\Modules\AutomaticEvidenceCreation\Automatic_Evidence_Creation::instance(); }
        if (class_exists('\TSEMOU\Modules\AutomaticLinking\Automatic_Linking') && method_exists('\TSEMOU\Modules\AutomaticLinking\Automatic_Linking', 'instance')) { \TSEMOU\Modules\AutomaticLinking\Automatic_Linking::instance(); }
        if (class_exists('\TSEMOU\Modules\EntityEngine\Entity_Engine') && method_exists('\TSEMOU\Modules\EntityEngine\Entity_Engine', 'instance')) { \TSEMOU\Modules\EntityEngine\Entity_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\EntityEvidenceLinks\Entity_Evidence_Links') && method_exists('\TSEMOU\Modules\EntityEvidenceLinks\Entity_Evidence_Links', 'instance')) { \TSEMOU\Modules\EntityEvidenceLinks\Entity_Evidence_Links::instance(); }
        if (class_exists('\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph') && method_exists('\TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph', 'instance')) { \TSEMOU\Modules\KnowledgeGraph\Knowledge_Graph::instance(); }
        if (class_exists('\TSEMOU\Modules\CompanyDiscovery\Company_Discovery') && method_exists('\TSEMOU\Modules\CompanyDiscovery\Company_Discovery', 'instance')) { \TSEMOU\Modules\CompanyDiscovery\Company_Discovery::instance(); }
        if (class_exists('\TSEMOU\Modules\CompanySensor\Company_Sensor') && method_exists('\TSEMOU\Modules\CompanySensor\Company_Sensor', 'instance')) { \TSEMOU\Modules\CompanySensor\Company_Sensor::instance(); }
        if (class_exists('\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine') && method_exists('\TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine', 'instance')) { \TSEMOU\Modules\CompanySectionEngine\Company_Section_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\DeveloperConsole\Developer_Console') && method_exists('\TSEMOU\Modules\DeveloperConsole\Developer_Console', 'instance')) { \TSEMOU\Modules\DeveloperConsole\Developer_Console::instance(); }
        if (class_exists('\TSEMOU\Modules\EvidenceEngine\Evidence_Engine') && method_exists('\TSEMOU\Modules\EvidenceEngine\Evidence_Engine', 'instance')) { \TSEMOU\Modules\EvidenceEngine\Evidence_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine') && method_exists('\TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine', 'instance')) { \TSEMOU\Modules\SourceIntelligence\Source_Intelligence_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\DiscoveryEngine\Discovery_Engine') && method_exists('\TSEMOU\Modules\DiscoveryEngine\Discovery_Engine', 'instance')) { \TSEMOU\Modules\DiscoveryEngine\Discovery_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine') && method_exists('\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine', 'instance')) { \TSEMOU\Modules\CompanyIntelligence\Company_Intelligence_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\SourceObject\Source_Object_Engine') && method_exists('\TSEMOU\Modules\SourceObject\Source_Object_Engine', 'instance')) { \TSEMOU\Modules\SourceObject\Source_Object_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence') && method_exists('\TSEMOU\Modules\CompanyIntelligence\Company_Intelligence', 'instance')) { \TSEMOU\Modules\CompanyIntelligence\Company_Intelligence::instance(); }
        if (class_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine') && method_exists('\TSEMOU\Modules\PolicyEngine\Policy_Engine', 'instance')) { \TSEMOU\Modules\PolicyEngine\Policy_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\ProofEngine\Proof_Engine') && method_exists('\TSEMOU\Modules\ProofEngine\Proof_Engine', 'instance')) { \TSEMOU\Modules\ProofEngine\Proof_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\CompanyEngine\Company_Engine') && method_exists('\TSEMOU\Modules\CompanyEngine\Company_Engine', 'instance')) { \TSEMOU\Modules\CompanyEngine\Company_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\EventIdentity\Event_Identity_Engine') && method_exists('\TSEMOU\Modules\EventIdentity\Event_Identity_Engine', 'instance')) { \TSEMOU\Modules\EventIdentity\Event_Identity_Engine::instance(); }
        if (class_exists('\TSEMOU\Modules\Story\Story_Module') && method_exists('\TSEMOU\Modules\Story\Story_Module', 'instance')) { \TSEMOU\Modules\Story\Story_Module::instance(); }
        if (class_exists('\TSEMOU\Modules\TrustEngine\Trust_Engine') && method_exists('\TSEMOU\Modules\TrustEngine\Trust_Engine', 'instance')) { \TSEMOU\Modules\TrustEngine\Trust_Engine::instance(); }
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
