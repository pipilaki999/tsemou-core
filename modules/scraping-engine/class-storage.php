<?php
namespace TSEMOU\Modules\ScrapingEngine;

if (!defined('ABSPATH')) exit;

class Storage {
    const COLLECTION = 'raw-evidence';

    public static function all() {
        if (class_exists('\TSEMOU\Runtime_Storage')) {
            return \TSEMOU\Runtime_Storage::all(self::COLLECTION, 300);
        }
        return [];
    }

    public static function get($raw_id) {
        if (class_exists('\TSEMOU\Runtime_Storage')) {
            return \TSEMOU\Runtime_Storage::read(self::COLLECTION, $raw_id);
        }
        return null;
    }

    public static function save(array $record) {
        $raw_id = sanitize_key($record['raw_id'] ?? ('raw_' . md5(wp_json_encode($record))));
        $record['raw_id'] = $raw_id;
        $record['storage_mode'] = 'runtime_file';
        if (class_exists('\TSEMOU\Runtime_Storage')) {
            return \TSEMOU\Runtime_Storage::write(self::COLLECTION, $raw_id, $record);
        }
        return $raw_id;
    }

    public static function clear() {
        if (class_exists('\TSEMOU\Runtime_Storage')) {
            return \TSEMOU\Runtime_Storage::clear(self::COLLECTION);
        }
        return 0;
    }
}
