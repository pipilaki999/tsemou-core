<?php
namespace TSEMOU\Modules\ConfigurationOS;

if (!defined('ABSPATH')) exit;

class Configuration_OS {
    private static $instance = null;
    private static $cache = [];

    public static function instance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'admin_menu'], 24);
    }

    public static function config_path($name = '') {
        $base = TSEMOU_CORE_PATH . 'config/';
        return $name ? $base . sanitize_file_name($name) . '.json' : $base;
    }

    public static function load($name) {
        $name = sanitize_key($name);

        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $path = self::config_path($name);

        if (!file_exists($path)) {
            self::$cache[$name] = [
                'version' => 'fallback',
                'updated' => current_time('mysql'),
                'data' => self::fallback_data($name)
            ];
            return self::$cache[$name];
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            $data = [
                'version' => 'invalid-fallback',
                'updated' => current_time('mysql'),
                'data' => self::fallback_data($name)
            ];
        }

        self::$cache[$name] = $data;
        return $data;
    }



    public static function fallback_data($name) {
        $name = sanitize_key($name);

        if ($name === 'countries') {
            return [
                'US' => ['name' => 'United States', 'enabled' => true, 'priority' => 100],
                'GB' => ['name' => 'United Kingdom', 'enabled' => true, 'priority' => 95],
                'DE' => ['name' => 'Germany', 'enabled' => true, 'priority' => 90],
                'FR' => ['name' => 'France', 'enabled' => true, 'priority' => 85],
                'GR' => ['name' => 'Greece', 'enabled' => true, 'priority' => 80],
            ];
        }

        if ($name === 'industries') {
            return [
                'technology' => ['label' => 'Technology', 'enabled' => true, 'priority' => 100],
                'food_beverage' => ['label' => 'Food & Beverage', 'enabled' => true, 'priority' => 95],
                'energy' => ['label' => 'Energy', 'enabled' => true, 'priority' => 90],
                'finance' => ['label' => 'Finance', 'enabled' => true, 'priority' => 85],
                'retail' => ['label' => 'Retail', 'enabled' => true, 'priority' => 80],
            ];
        }

        if ($name === 'discovery_waves') {
            return [
                'wave_1' => ['label' => 'Wave 1', 'enabled' => true, 'rank_band' => 'top_1_50', 'priority' => 100],
                'wave_2' => ['label' => 'Wave 2', 'enabled' => true, 'rank_band' => 'top_51_200', 'priority' => 75],
                'wave_3' => ['label' => 'Wave 3', 'enabled' => true, 'rank_band' => 'regional_targets', 'priority' => 50],
            ];
        }

        return [];
    }

    public static function data($name) {
        $config = self::load($name);
        return isset($config['data']) && is_array($config['data']) ? $config['data'] : [];
    }

    public static function get($name, $key, $default = null) {
        $data = self::data($name);
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    public static function enabled_items($name) {
        $data = self::data($name);
        $items = [];

        foreach ($data as $key => $value) {
            if (is_array($value) && array_key_exists('enabled', $value) && !$value['enabled']) {
                continue;
            }
            $items[$key] = $value;
        }

        return $items;
    }

    public static function discovery_wave_tasks() {
        $countries = self::enabled_items('countries');
        $industries = self::enabled_items('industries');
        $waves = self::enabled_items('discovery_waves');

        if (empty($countries)) $countries = self::fallback_data('countries');
        if (empty($industries)) $industries = self::fallback_data('industries');
        if (empty($waves)) $waves = self::fallback_data('discovery_waves');

        $tasks = [];

        foreach ($waves as $wave_key => $wave) {
            foreach ($countries as $country_code => $country) {
                foreach ($industries as $industry_key => $industry) {
                    $tasks[] = [
                        'country' => $country_code,
                        'country_name' => is_array($country) ? ($country['name'] ?? $country_code) : $country_code,
                        'industry' => $industry_key,
                        'industry_label' => is_array($industry) ? ($industry['label'] ?? $industry_key) : $industry_key,
                        'wave' => $wave_key,
                        'rank_band' => is_array($wave) ? ($wave['rank_band'] ?? '') : '',
                        'priority' => intval(is_array($country) ? ($country['priority'] ?? 50) : 50) + intval(is_array($industry) ? ($industry['priority'] ?? 50) : 50) + intval(is_array($wave) ? ($wave['priority'] ?? 50) : 50),
                        'status' => 'pending'
                    ];
                }
            }
        }

        usort($tasks, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        return $tasks;
    }

    public function admin_menu() {
        add_submenu_page(
            'tsemou-os',
            'Configuration OS',
            'Configuration OS',
            'manage_options',
            'tsemou-configuration-os',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) return;

        $files = glob(self::config_path() . '*.json');
        $tasks = self::discovery_wave_tasks();
        ?>
        <div class="wrap">
            <h1>TSEMOU Configuration OS</h1>
            <p><strong>Phase A Runtime:</strong> generates Discovery Tasks for the Orchestrator. No AI, no Trust, no Phase B logic.</p>

            <h2>Configuration Files</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Version</th>
                        <th>Updated</th>
                        <th>Items</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                        <?php
                        $name = basename($file, '.json');
                        $config = self::load($name);
                        $items = isset($config['data']) && is_array($config['data']) ? count($config['data']) : 0;
                        ?>
                        <tr>
                            <td><code><?php echo esc_html(basename($file)); ?></code></td>
                            <td><?php echo esc_html($config['version'] ?? ''); ?></td>
                            <td><?php echo esc_html($config['updated'] ?? ''); ?></td>
                            <td><?php echo esc_html($items); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Discovery Wave Preview</h2>
            <p>Generated tasks: <strong><?php echo esc_html(count($tasks)); ?></strong>. These are the tasks used by Discovery Orchestrator → Seed Company Discovery Queue.</p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>Industry</th>
                        <th>Wave</th>
                        <th>Rank Band</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($tasks, 0, 25) as $task): ?>
                        <tr>
                            <td><?php echo esc_html($task['country_name']); ?></td>
                            <td><?php echo esc_html($task['industry_label']); ?></td>
                            <td><?php echo esc_html($task['wave']); ?></td>
                            <td><?php echo esc_html($task['rank_band']); ?></td>
                            <td><?php echo esc_html($task['priority']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
