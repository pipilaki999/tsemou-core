<?php
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

require_once dirname(__DIR__) . '/modules/event-identity/class-candidate-normalizer.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-alias-resolver.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-identity-signature.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-identity-matcher.php';
require_once dirname(__DIR__) . '/modules/event-identity/class-identity-repository.php';
