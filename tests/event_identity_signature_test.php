<?php
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

require_once dirname(__DIR__) . '/modules/event-identity/class-event-signature.php';

use TSEMOU\Modules\EventIdentity\Event_Signature;

$payload_a = [
    'event_type' => 'legal',
    'company_names' => ['Acme Corp', 'Northwind'],
    'countries' => ['US'],
    'industries' => ['manufacturing'],
    'story_status' => 'active',
    'signals_count' => 3,
    'updates_count' => 1,
    'year' => '2025',
];

$payload_b = [
    'event_type' => 'legal',
    'company_names' => ['Northwind', 'Acme Corp'],
    'countries' => ['US'],
    'industries' => ['manufacturing'],
    'story_status' => 'active',
    'signals_count' => 3,
    'updates_count' => 1,
    'year' => '2025',
];

$payload_c = [
    'event_type' => 'incident',
    'company_names' => ['Acme Corp'],
    'countries' => ['DE'],
    'industries' => ['transport'],
    'story_status' => 'active',
    'signals_count' => 4,
    'updates_count' => 2,
    'year' => '2025',
];

$signature_a = Event_Signature::build($payload_a);
$signature_b = Event_Signature::build($payload_b);
$signature_c = Event_Signature::build($payload_c);

if ($signature_a !== $signature_b) {
    fwrite(STDERR, "Determinism check failed: signatures differ for equivalent payloads.\n");
    exit(1);
}

if ($signature_a === $signature_c) {
    fwrite(STDERR, "Determinism check failed: signatures collided for distinct payloads.\n");
    exit(1);
}

echo "Event signature tests passed.\n";
