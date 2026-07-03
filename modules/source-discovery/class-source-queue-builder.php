<?php
namespace TSEMOU\Modules\SourceDiscovery;

if (!defined('ABSPATH')) exit;

class Source_Queue_Builder {
    public static function build_payloads(array $task, array $sources) {
        $payloads = [];

        foreach ($sources as $source) {
            $payloads[] = array_merge($task, [
                'source_id' => $source['source_id'] ?? '',
                'source_label' => $source['source_label'] ?? '',
                'source_type' => $source['source_type'] ?? '',
                'source_url' => $source['source_url'] ?? '',
                'source_description' => $source['source_description'] ?? '',
                'source_discovery_status' => 'candidate_source_resolved',
            ]);
        }

        return $payloads;
    }
}
