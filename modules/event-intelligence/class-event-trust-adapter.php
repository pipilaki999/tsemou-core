<?php
namespace TSEMOU\Modules\EventIntelligence;

if (!defined('ABSPATH')) exit;

use TSEMOU\Modules\CompanyEngine\Company_Engine;
use TSEMOU\Modules\TrustEngine\Trust_Engine;

class Event_Trust_Adapter {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function evaluate(array $state = []) {
        $story = is_array($state['story'] ?? null) ? $state['story'] : [];
        $company_ids = array_values(array_unique(array_map('intval', is_array($story['company_ids'] ?? null) ? $story['company_ids'] : [])));

        if (empty($company_ids) && !empty($story['id']) && class_exists('TSEMOU\\Modules\\CompanyEngine\\Company_Engine')) {
            $company_ids = Company_Engine::get_connected_company_ids(intval($story['id']));
        }

        $evaluations = [];
        $score = 0.0;
        if (class_exists('TSEMOU\\Modules\\TrustEngine\\Trust_Engine')) {
            foreach ($company_ids as $company_id) {
                $company_score = floatval(Trust_Engine::recalculate_company_trust($company_id));
                $score = max($score, $company_score);
                $evaluations[] = [
                    'company_id' => $company_id,
                    'score' => $company_score,
                ];
            }
        }

        return [
            'company_ids' => $company_ids,
            'score' => round($score, 1),
            'band' => $this->band_from_score($score),
            'evaluations' => $evaluations,
            'confidence' => empty($company_ids) ? 0.2 : 0.5,
        ];
    }

    private function band_from_score($score) {
        if ($score >= 7.5) {
            return 'stable';
        }
        if ($score >= 5.0) {
            return 'watch';
        }
        return 'at_risk';
    }
}