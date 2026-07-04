<?php
use TSEMOU\Modules\EventIdentity\AliasResolver;
use TSEMOU\Modules\EventIdentity\CandidateNormalizer;
use TSEMOU\Modules\EventIdentity\IdentityMatcher;
use TSEMOU\Modules\EventIdentity\IdentityRepository;
use TSEMOU\Modules\EventIdentity\IdentitySignature;

class EventIdentityEngineTest extends \PHPUnit\Framework\TestCase {
    public function test_signature_generation_is_deterministic() {
        $first = IdentitySignature::generate('Donald Trump');
        $second = IdentitySignature::generate('Donald Trump');
        $third = IdentitySignature::generate('Donald J. Trump');

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $third);
        $this->assertStringStartsWith('identity_', $first);
    }

    public function test_alias_resolution_handles_common_variants() {
        $aliases = AliasResolver::resolve('Donald J. Trump');

        $this->assertContains('donald j trump', $aliases);
        $this->assertContains('donald trump', $aliases);
        $this->assertContains('trump', $aliases);
    }

    public function test_identity_matching_returns_high_confidence_for_aliases() {
        $score = IdentityMatcher::compare('Donald Trump', 'Trump');
        $this->assertGreaterThanOrEqual(0.9, $score);
    }

    public function test_repository_can_store_and_retrieve_identity() {
        $record = IdentityRepository::save([
            'signature' => 'identity_test_001',
            'canonical_name' => 'Donald Trump',
            'aliases' => ['Donald J. Trump', 'Trump'],
        ]);

        $this->assertSame('identity_test_001', $record['signature']);
        $this->assertSame('Donald Trump', $record['canonical_name']);
        $this->assertSame('Donald J. Trump', $record['aliases'][0]);
    }
}
