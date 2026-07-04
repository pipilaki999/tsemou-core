<?php
namespace TSEMOU\Modules\EventIdentity;

if (!defined('ABSPATH')) exit;

class IdentitySignature {
    public static function generate($input) {
        $normalized = CandidateNormalizer::normalize(is_array($input) ? ($input['name'] ?? '') : $input);
        if ($normalized === '') {
            return '';
        }

        $aliases = AliasResolver::resolve($normalized);
        $hash_seed = implode('|', $aliases);
        return 'identity_' . substr(sha1($hash_seed), 0, 24);
    }
}
