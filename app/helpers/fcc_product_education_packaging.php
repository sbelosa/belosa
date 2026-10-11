<?php

/** Static package facts, kept separate from daily label instructions. */
function fcc_product_education_packaging_data(): array {
    static $data = null;
    if($data !== null) return $data;
    $path = dirname(__DIR__) . '/config/fcc_product_education_packaging.json';
    if(!is_file($path)) return $data = ['products' => []];
    $decoded = json_decode((string) file_get_contents($path), true);
    if(!is_array($decoded) || ($decoded['schema'] ?? '') !== 'fcc_product_education_packaging_v1'
        || !is_array($decoded['products'] ?? null)) return $data = ['products' => []];
    return $data = $decoded;
}

/** Parse only an explicit, fixed daily count from a reviewed label. */
function fcc_product_education_label_daily_usage(array $source, string $unit): ?array {
    if(($source['review_status'] ?? '') !== 'approved'
        || ($source['review_scope'] ?? '') !== 'identity_and_label_facts'
        || !in_array($source['identity_status'] ?? '', ['verified_exact', 'verified_reviewed'], true)) return null;
    foreach(['market_code', 'language_code'] as $scope) {
        if(isset($source[$scope]) && $source[$scope] !== 'hr') return null;
    }
    if(!in_array($unit, ['tablet', 'capsule', 'gummy', 'lozenge', 'sachet', 'stick'], true)) return null;
    $texts = array_values(array_filter((array) ($source['usage_texts'] ?? []), 'is_string'));
    $text = trim((string) preg_replace('/\s+/u', ' ', implode(' ', $texts)));
    if($text === '') return null;

    $numberWords = ['jednu' => 1, 'jedna' => 1, 'jedan' => 1, 'jedne' => 1, 'jednog' => 1,
        'jednom' => 1, 'dvije' => 2, 'dva' => 2, 'tri' => 3, 'četiri' => 4, 'pet' => 5, 'šest' => 6];
    $numeric = static function(string $token) use ($numberWords): int {
        $token = function_exists('mb_strtolower') ? mb_strtolower($token, 'UTF-8') : strtolower($token);
        return ctype_digit($token) ? (int) $token : ($numberWords[$token] ?? 0);
    };
    $number = '(?:[1-9][0-9]*|jednu|jedna|jedan|jedne|jednog|jednom|dvije|dva|tri|četiri|pet|šest)';
    $unitExpression = match($unit) {
        'tablet' => 'tablet[aeu]',
        'capsule' => '(?:meke\s+)?kapsul[aeu]',
        'gummy' => 'gumeni\s+bombon',
        'lozenge' => 'pastil[aeu]',
        'sachet' => 'vrećic[aeu]',
        'stick' => 'stick(?:a|ova)?',
    };
    /* A range, titration, maximum or several age groups does not set a fixed daily amount. */
    if(preg_match('/\d+\s*[-–]\s*\d+|\bdo\s+' . $number . '\b|¼|\bpostupno\b|\bpovećati\b/iu', $text)
        || preg_match('/\bnajviše\b|\bmaksimalno\b|\b(?:po|prema)\s+potrebi\b|\bviše\s+od\s+' . $number . '\b/iu', $text)
        || preg_match('/(?:\d+[.,]\d+|\d+\s*\/\s*\d+|[½¼¾])\s*' . $unitExpression . '\b/iu', $text)
        || preg_match('/\bdjeca\s+od\s+\d+\s+do\s+\d+/iu', $text)) return null;
    if(isset($source['label_daily_count']) && ($source['label_daily_unit'] ?? '') === $unit) {
        $count = $source['label_daily_count'];
        if(!is_int($count) || $count < 1 || $count > 100 || empty($source['label_daily_count_basis'])) return null;
    } else {
        $count = 0;
        if(preg_match('/\b(' . $number . ')\s*[x×]\s*(' . $number . ')\s+' . $unitExpression . '\s+(?:na\s+dan|dnevno)\b/iu', $text, $match)) {
            $count = $numeric($match[1]) * $numeric($match[2]);
        } elseif(preg_match('/\b(' . $number . ')\s+' . $unitExpression . '\s+(?:(' . $number . ')\s+puta\s+)?(?:na\s+dan|dnevno)\b/iu', $text, $match)) {
            $count = $numeric($match[1]) * (isset($match[2]) && $match[2] !== '' ? $numeric($match[2]) : 1);
        } elseif(preg_match('/\b(' . $number . ')\s+puta\s+(?:na\s+dan|dnevno)\b.{0,60}?\b(' . $number . ')\s+' . $unitExpression . '\b/iu', $text, $match)) {
            $count = $numeric($match[1]) * $numeric($match[2]);
        } elseif(preg_match('/\bjednom\s+dnevno\b.{0,60}?\b(' . $number . ')\s+' . $unitExpression . '\b/iu', $text, $match)) {
            $count = $numeric($match[1]);
        } elseif(preg_match('/\b(' . $number . ')\s+' . $unitExpression . '\b.{0,150}?\bjednom\s+dnevno\b/iu', $text, $match)) {
            $count = $numeric($match[1]);
        } elseif(preg_match('/\bdnevno\b.{0,30}?\b(' . $number . ')\s+' . $unitExpression . '\b/iu', $text, $match)) {
            $count = $numeric($match[1]);
        }
        if($count < 1 || $count > 100) return null;
    }
    $verification = is_array($source['latest_verification'] ?? null) ? $source['latest_verification'] : [];
    return ['count' => $count, 'unit' => $unit, 'text' => $text,
        'source_url' => (string) ($source['source_url'] ?? ''),
        'source_date' => (string) ($source['accessed_at'] ?? ''),
        'source_id' => (string) ($source['source_id'] ?? ''),
        'last_verified_at' => (string) ($verification['reviewed_at'] ?? ''),
        'verification_source_url' => (string) ($verification['source_url'] ?? ''),
        'retrieval_mode' => (string) ($verification['retrieval_mode'] ?? ''),
        'market_code' => (string) ($source['market_code'] ?? ''),
        'language_code' => (string) ($source['language_code'] ?? ''),
        'basis' => 'explicit_reviewed_label'];
}

/** Independent plans for each exact offer. Package arithmetic never creates a dose. */
function fcc_product_education_package_plans(array|string $product): array {
    $key = is_array($product) ? ($product['product_key'] ?? '') : $product;
    $result = ['product_key' => is_string($key) ? $key : '', 'period_days' => 30,
        'status' => 'missing_package', 'plans' => [], 'usage_sources' => [],
        'max_continuous_days' => null, 'replacement_product_key' => null,
        'replacement_note' => '', 'reasons' => []];
    if(!is_string($key) || $key === '') return $result;
    $record = fcc_product_education_packaging_data()['products'][$key] ?? null;
    if(!is_array($record)) return $result;
    $result['usage_sources'] = (array) ($record['usage_sources'] ?? []);
    foreach($result['usage_sources'] as &$usageSource) {
        $usageSource['display_usage_texts'] = array_values(array_filter((array) ($usageSource['usage_texts'] ?? []), static fn($text): bool => is_string($text) && trim($text) !== '' && !preg_match('/¼|postupno|povećavati|povećati/iu', $text)));
    }
    unset($usageSource);
    $result['reasons'] = (array) ($record['reasons'] ?? []);
    $result['max_continuous_days'] = is_int($record['max_continuous_days'] ?? null) ? $record['max_continuous_days'] : null;
    $result['replacement_product_key'] = $record['replacement_product_key'] ?? null;
    $result['replacement_note'] = (string) ($record['replacement_note'] ?? '');
    $isOralSingleProduct = ($record['route'] ?? '') === 'oral'
        && !in_array($record['catalog_scope'] ?? '', ['bundle', 'source_only'], true);
    foreach((array) ($record['offers'] ?? []) as $offer) {
        if(!is_array($offer)) continue;
        $pack = (array) ($offer['pack'] ?? []);
        $unit = is_string($pack['unit'] ?? null) ? $pack['unit'] : '';
        $count = is_int($pack['count'] ?? null) && $pack['count'] > 0 ? $pack['count'] : null;
        $isCountable = $isOralSingleProduct && $count !== null
            && in_array($unit, ['tablet', 'capsule', 'gummy', 'lozenge', 'sachet', 'stick'], true);
        $plan = ['sku' => (string) ($offer['sku'] ?? ''), 'name' => (string) ($offer['name'] ?? $record['name'] ?? ''),
            'pack_label' => (string) ($offer['pack_label'] ?? ''),
            'source_url' => (string) ($offer['source_url'] ?? ''), 'source_date' => (string) ($offer['source_date'] ?? ''),
            'source_status' => (string) ($offer['source_status'] ?? ''),
            'availability' => (string) ($offer['availability'] ?? 'unknown'), 'unit' => $unit, 'count' => $count,
            'portion_amount' => $pack['portion_amount'] ?? null, 'portion_unit' => $pack['portion_unit'] ?? null,
            'fiber_per_portion_g' => $pack['fiber_per_portion_g'] ?? null,
            'package_average_per_day' => $isCountable ? round($count / 30, 6) : null,
            'declared_daily_count' => null, 'declared_usage_text' => null, 'declared_usage_source' => null,
            'days_per_pack' => null, 'packs_needed_for_30' => null, 'consumed_for_30' => null,
            'leftover_after_30' => null, 'discrepancy' => null,
            'full_days_per_pack' => null, 'leftover_after_full_days' => null,
            'package_duration_differs_from_30_days' => null,
            'declaration_status' => $isCountable ? 'unconfirmed' : 'not_applicable',
            'quantity_calculation_status' => $isCountable ? 'unconfirmed' : 'not_applicable',
            'needs_label_review' => $isCountable, 'quantity_information_only' => true,
            'continuous_period_blocked' => false, 'max_continuous_days' => $result['max_continuous_days'],
            'allowed_period_days' => null, 'consumed_in_allowed_period' => null, 'leftover_after_allowed_period' => null,
            'packs_needed_in_allowed_period' => null,
            'ready_for_continuous_30_days' => false, 'status' => $isCountable ? 'package_only' : 'not_applicable',
            'reason' => $isCountable ? 'Pakiranje je poznato. Fiksna dnevna uporaba nije potvrđena.' : 'Ovo pakiranje nema obračun tableta, kapsula ili pojedinačnih porcija za oralnu uporabu.'];
        if($isCountable) {
            $dailyMatches = [];
            foreach($result['usage_sources'] as $usageSource) {
                if(!is_array($usageSource)) continue;
                $match = fcc_product_education_label_daily_usage($usageSource, $unit);
                if($match !== null) $dailyMatches[$match['count']] = $match;
            }
            if(count($dailyMatches) === 1) {
                $daily = reset($dailyMatches);
                $dailyCount = $daily['count'];
                $consumed = 30 * $dailyCount;
                $packsNeeded = (int) ceil($consumed / $count);
                $plan['declared_daily_count'] = $dailyCount;
                $plan['declared_usage_text'] = $daily['text'];
                $plan['declared_usage_source'] = $daily;
                $plan['days_per_pack'] = round($count / $dailyCount, 6);
                $plan['full_days_per_pack'] = intdiv($count, $dailyCount);
                $plan['leftover_after_full_days'] = $count % $dailyCount;
                $plan['packs_needed_for_30'] = $packsNeeded;
                $plan['consumed_for_30'] = $consumed;
                $plan['leftover_after_30'] = $packsNeeded * $count - $consumed;
                $plan['discrepancy'] = abs($count / 30 - $dailyCount) > 0.000001;
                $plan['package_duration_differs_from_30_days'] = $plan['discrepancy'];
                $plan['declaration_status'] = 'confirmed';
                $plan['quantity_calculation_status'] = 'confirmed';
                $plan['needs_label_review'] = false;
                $limit = $result['max_continuous_days'];
                $plan['continuous_period_blocked'] = $limit !== null && $limit < 30;
                $plan['allowed_period_days'] = $plan['continuous_period_blocked'] ? $limit : 30;
                $plan['consumed_in_allowed_period'] = $plan['allowed_period_days'] * $dailyCount;
                $allowedPacks = (int) ceil($plan['consumed_in_allowed_period'] / $count);
                $plan['packs_needed_in_allowed_period'] = $allowedPacks;
                $plan['leftover_after_allowed_period'] = $allowedPacks * $count - $plan['consumed_in_allowed_period'];
                $plan['status'] = $plan['continuous_period_blocked'] ? 'continuous_limit' : 'quantity_ready';
                $plan['reason'] = $plan['continuous_period_blocked']
                    ? 'Potvrđena deklaracija ograničava neprekidnu uporabu na ' . $limit . ' dana. Obračun tog razdoblja slijedi dnevnu uputu. Usporedba količine za 30 dana ne produljuje uporabu.'
                    : 'Potvrđena dnevna uputa. Jedno pakiranje prema toj količini pokriva ' . $plan['full_days_per_pack']
                        . ' punih dana.' . ($plan['leftover_after_full_days'] > 0 ? ' Preostale jedinice nakon tog razdoblja: ' . $plan['leftover_after_full_days'] . '.' : '')
                        . ' Informativni obračun za 30 dana ne mijenja dnevnu uputu.';
                $plan['ready_for_continuous_30_days'] = !$plan['continuous_period_blocked'] && $plan['availability'] === 'available';
            } elseif(count($dailyMatches) > 1) {
                $plan['status'] = 'needs_label';
                $plan['reason'] = 'Pregledani izvori navode različite dnevne količine. Potrebno je razjasniti aktualnu varijantu.';
            }
        }
        if($plan['availability'] !== 'available') {
            $plan['status'] = 'unavailable';
            $plan['quantity_calculation_status'] = 'unavailable';
            $plan['ready_for_continuous_30_days'] = false;
            $plan['reason'] = 'Ponuda nije označena dostupnom. ' . ($result['replacement_note'] ?: 'Provjeri aktualnu ponudu prije sastavljanja plana.');
        }
        $result['plans'][] = $plan;
    }
    if($result['plans']) {
        $statuses = array_column($result['plans'], 'status');
        $result['status'] = in_array('quantity_ready', $statuses, true) ? 'quantity_ready'
            : (in_array('continuous_limit', $statuses, true) ? 'continuous_limit'
                : (count(array_unique($statuses)) === 1 ? $statuses[0] : 'mixed_offers'));
    }
    return $result;
}
