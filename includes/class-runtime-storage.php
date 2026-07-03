<?php
namespace TSEMOU;

if (!defined('ABSPATH')) exit;

/**
 * TSEMOU Runtime Storage — file-based temporary storage.
 *
 * Rule: do not burden the WordPress database with intermediate Phase A data.
 * Runtime/temporary objects are stored as JSON files under wp-content/uploads.
 * Permanent records will be created later by the proper storage layer.
 */
class Runtime_Storage {
    public static function base_dir() {
        $upload = wp_upload_dir(null, false);
        $base = trailingslashit($upload['basedir']) . 'tsemou-runtime-storage/';
        if (!file_exists($base)) {
            wp_mkdir_p($base);
        }
        return $base;
    }

    public static function collection_dir($collection) {
        $collection = sanitize_key($collection);
        $dir = self::base_dir() . $collection . '/';
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        return $dir;
    }

    public static function write($collection, $id, array $data) {
        $collection = sanitize_key($collection);
        $id = sanitize_key($id);
        if (!$id) {
            $id = md5(wp_json_encode($data));
        }
        $data['_runtime_collection'] = $collection;
        $data['_runtime_id'] = $id;
        $data['_runtime_saved_at'] = current_time('mysql');

        $file = self::collection_dir($collection) . $id . '.json';
        $json = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($file, $json, LOCK_EX);
        return $id;
    }

    public static function read($collection, $id) {
        $collection = sanitize_key($collection);
        $id = sanitize_key($id);
        $file = self::collection_dir($collection) . $id . '.json';
        if (!file_exists($file)) {
            return null;
        }
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    public static function all($collection, $limit = 200) {
        $collection = sanitize_key($collection);
        $files = glob(self::collection_dir($collection) . '*.json');
        if (!is_array($files)) return [];
        usort($files, function($a, $b) { return filemtime($b) <=> filemtime($a); });
        $records = [];
        foreach (array_slice($files, 0, max(1, intval($limit))) as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) $records[] = $data;
        }
        return $records;
    }

    public static function clear($collection) {
        $collection = sanitize_key($collection);
        $files = glob(self::collection_dir($collection) . '*.json');
        if (!is_array($files)) return 0;
        $count = 0;
        foreach ($files as $file) {
            if (is_file($file) && unlink($file)) $count++;
        }
        return $count;
    }

    public static function stats() {
        $base = self::base_dir();
        $stats = [];
        foreach (['raw-evidence', 'evidence-drafts'] as $collection) {
            $files = glob(self::collection_dir($collection) . '*.json');
            $stats[$collection] = is_array($files) ? count($files) : 0;
        }
        $stats['base_dir'] = $base;
        return $stats;
    }
}
