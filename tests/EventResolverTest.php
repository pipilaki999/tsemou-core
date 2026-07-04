<?php
require_once __DIR__ . '/bootstrap.php';

use TSEMOU\Modules\EventIdentity\IdentityRepository;
use TSEMOU\Modules\EventResolver\Event_Decision;
use TSEMOU\Modules\EventResolver\Event_Merge;
use TSEMOU\Modules\EventResolver\Event_Resolver;
use TSEMOU\Modules\EventResolver\Event_Similarity;

class EventResolverTest extends \PHPUnit\Framework\TestCase {
    public function test_new_event_is_classified_correctly() {
        $decision = Event_Decision::from_payload(['title' => 'Acme investigation']);
        $this->assertSame('NEW_EVENT', $decision->action);
    }

    public function test_duplicate_is_detected_from_repository_signature() {
        IdentityRepository::save([
            'signature' => 'identity_test_duplicate',
            'canonical_name' => 'Acme investigation',
            'aliases' => ['Acme investigation'],
            'payload' => ['title' => 'Acme investigation', 'company' => 'Acme', 'event_type' => 'legal'],
        ]);

        $decision = Event_Decision::from_payload(['title' => 'Acme investigation'], IdentityRepository::find_by_signature('identity_test_duplicate'));
        $this->assertSame('DUPLICATE', $decision->action);
    }

    public function test_similarity_scores_identical_events_highly() {
        $score = Event_Similarity::score(['title' => 'Acme investigation', 'company' => 'Acme', 'event_type' => 'legal'], ['title' => 'Acme investigation', 'company' => 'Acme', 'event_type' => 'legal']);
        $this->assertGreaterThanOrEqual(0.9, $score);
    }

    public function test_merge_combines_payloads() {
        $merged = Event_Merge::merge(['title' => 'Acme', 'summary' => 'First'], ['title' => 'Acme', 'summary' => 'Second', 'signals_count' => 2]);
        $this->assertSame('First Second', trim($merged['summary']));
        $this->assertSame(2, $merged['signals_count']);
    }

    public function test_resolver_returns_deterministic_action() {
        $resolver = Event_Resolver::instance();
        $result = $resolver->resolve(['title' => 'Acme investigation', 'story_id' => 77]);
        $this->assertContains($result['action'], ['UPDATE', 'NEW_EVENT', 'DUPLICATE', 'MERGE', 'CORRECTION']);
    }
}
