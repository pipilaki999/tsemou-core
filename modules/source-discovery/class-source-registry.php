<?php
namespace TSEMOU\Modules\SourceDiscovery;

if (!defined('ABSPATH')) exit;

class Source_Registry {
    public static function config_path($name) {
        return TSEMOU_CORE_PATH . 'config/' . sanitize_file_name($name) . '.json';
    }

    public static function load_json($name, $fallback = []) {
        $path = self::config_path($name);
        if (!file_exists($path)) return $fallback;
        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) return $fallback;
        if (isset($data['data']) && is_array($data['data'])) return $data['data'];
        return $data;
    }

    public static function sources() {
        $fallback = [
            'official_website' => [
                'label' => 'Official Website',
                'type' => 'official',
                'enabled' => true,
                'priority' => 100,
                'url_template' => 'https://www.google.com/search?q={query}+official+website',
                'description' => 'Official company website discovery search.'
            ],
            'wikipedia' => [
                'label' => 'Wikipedia',
                'type' => 'encyclopedia',
                'enabled' => true,
                'priority' => 90,
                'url_template' => 'https://www.google.com/search?q={query}+Wikipedia',
                'description' => 'Public encyclopedia discovery search.'
            ],
            'opencorporates' => [
                'label' => 'OpenCorporates',
                'type' => 'registry',
                'enabled' => true,
                'priority' => 88,
                'url_template' => 'https://opencorporates.com/companies?q={query}',
                'description' => 'Corporate registry discovery.'
            ],
            'linkedin' => [
                'label' => 'LinkedIn',
                'type' => 'professional_network',
                'enabled' => true,
                'priority' => 72,
                'url_template' => 'https://www.google.com/search?q={query}+LinkedIn+company',
                'description' => 'Company profile discovery search.'
            ],
            'news_search' => [
                'label' => 'News Search',
                'type' => 'news',
                'enabled' => true,
                'priority' => 68,
                'url_template' => 'https://www.google.com/search?q={query}+news',
                'description' => 'News discovery search.'
            ],
            'government_registry' => [
                'label' => 'Government Registry',
                'type' => 'government',
                'enabled' => true,
                'priority' => 82,
                'url_template' => 'https://www.google.com/search?q={country_name}+company+registry+{query}',
                'description' => 'Country-level registry discovery search.'
            ],
            'sec' => [
                'label' => 'SEC EDGAR',
                'type' => 'financial_regulator',
                'enabled' => true,
                'priority' => 92,
                'countries' => ['US'],
                'industries' => ['technology','finance','energy','retail','food_beverage'],
                'url_template' => 'https://www.sec.gov/edgar/search/#/q={query}',
                'description' => 'US SEC filing search for public companies.'
            ],
            'companies_house' => [
                'label' => 'Companies House',
                'type' => 'government_registry',
                'enabled' => true,
                'priority' => 92,
                'countries' => ['GB'],
                'url_template' => 'https://find-and-update.company-information.service.gov.uk/search?q={query}',
                'description' => 'UK official company registry.'
            ],
            'gemi' => [
                'label' => 'ΓΕΜΗ',
                'type' => 'government_registry',
                'enabled' => true,
                'priority' => 92,
                'countries' => ['GR'],
                'url_template' => 'https://www.google.com/search?q=ΓΕΜΗ+{query}',
                'description' => 'Greek business registry discovery search.'
            ],
            'bundesanzeiger' => [
                'label' => 'Bundesanzeiger',
                'type' => 'government_registry',
                'enabled' => true,
                'priority' => 90,
                'countries' => ['DE'],
                'url_template' => 'https://www.google.com/search?q=Bundesanzeiger+{query}',
                'description' => 'German official disclosure registry discovery.'
            ]
        ];

        $sources = self::load_json('source_registry', $fallback);
        return is_array($sources) ? $sources : $fallback;
    }

    public static function enabled_sources() {
        $out = [];
        foreach (self::sources() as $id => $source) {
            if (is_array($source) && array_key_exists('enabled', $source) && !$source['enabled']) continue;
            $out[$id] = $source;
        }
        return $out;
    }
}
