<?php
namespace TSEMOU\Modules\SourceDiscovery;

if (!defined('ABSPATH')) exit;

class Source_Prioritizer {
    public static function prioritize(array $sources, array $task = []) {
        $base_priority = intval($task['priority'] ?? 0);
        foreach ($sources as &$source) {
            $source['priority'] = $base_priority + intval($source['source_priority'] ?? 0);
        }
        unset($source);

        usort($sources, function($a, $b) {
            return intval($b['priority'] ?? 0) <=> intval($a['priority'] ?? 0);
        });

        return $sources;
    }
}
