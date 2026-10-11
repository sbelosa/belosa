<?php

/**
 * Pure, deterministic core for FCC Product Advisor V2.
 *
 * This file deliberately has no database, HTTP or framework dependencies so the
 * exact production decision path can be replayed from CLI tests.
 */

function fcc_ai_v2_list($value): array {
    if(!is_array($value)) {
        return [];
    }

    return array_values(array_filter($value, static function($item): bool {
        return is_scalar($item) || is_array($item);
    }));
}

function fcc_ai_v2_unique_strings($value, int $max = 100): array {
    $result = [];

    foreach(fcc_ai_v2_list($value) as $item) {
        if(!is_scalar($item)) {
            continue;
        }

        $item = trim((string) $item);
        if($item === '' || isset($result[$item])) {
            continue;
        }

        $result[$item] = true;
        if(count($result) >= $max) {
            break;
        }
    }

    return array_keys($result);
}

function fcc_ai_v2_normalize_text(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        '™' => ' ', '®' => ' ', '©' => ' ', '’' => "'", '‘' => "'", '´' => "'",
        '“' => ' ', '”' => ' ', '–' => '-', '—' => '-', '&' => ' i ',
        'č' => 'c', 'ć' => 'c', 'đ' => 'd', 'š' => 's', 'ž' => 'z',
        'Č' => 'c', 'Ć' => 'c', 'Đ' => 'd', 'Š' => 's', 'Ž' => 'z',
        'ñ'=>'n','å'=>'a','ä'=>'a','ö'=>'o','ő'=>'o','ű'=>'u','ú'=>'u','ü'=>'u','á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ł'=>'l','ą'=>'a','ę'=>'e','ń'=>'n','ś'=>'s','ź'=>'z','ż'=>'z','ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t',
        'љ' => 'lj', 'њ' => 'nj', 'џ' => 'dz',
    ]);
    $text = preg_replace('/[^\p{L}\p{N}\'\-+%\.\/]+/u', ' ', $text) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    return trim($text);
}

/** Preserve sentence/clause separators for scope-sensitive negation checks. */
function fcc_ai_v2_normalize_clause_text(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        '™' => ' ', '®' => ' ', '©' => ' ', '’' => "'", '‘' => "'", '´' => "'",
        '“' => ' ', '”' => ' ', '–' => '-', '—' => '-', '&' => ' i ',
        'č' => 'c', 'ć' => 'c', 'đ' => 'd', 'š' => 's', 'ž' => 'z',
        'љ' => 'lj', 'њ' => 'nj', 'џ' => 'dz',
    ]);
    $text = preg_replace('/[^\p{L}\p{N}\'\-+%\.\/;,!?]+/u', ' ', $text) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    return trim($text);
}

function fcc_ai_v2_phrase_pattern(string $phrase): string {
    $phrase = fcc_ai_v2_normalize_text($phrase);
    if($phrase === '') {
        return '';
    }

    $parts = preg_split('/\s+/u', $phrase, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if(!$parts) {
        return '';
    }

    return '(?<![\p{L}\p{N}])' . implode('\\s+', array_map(static function(string $part): string {
        return preg_quote($part, '/');
    }, $parts)) . '(?![\p{L}\p{N}])';
}

function fcc_ai_v2_contains_phrase(string $text, string $phrase): bool {
    $pattern = fcc_ai_v2_phrase_pattern($phrase);
    if($pattern === '') {
        return false;
    }

    return preg_match('/' . $pattern . '/u', fcc_ai_v2_normalize_text($text)) === 1;
}

function fcc_ai_v2_contains_any(string $text, array $phrases): bool {
    foreach($phrases as $phrase) {
        if(is_scalar($phrase) && fcc_ai_v2_contains_phrase($text, (string) $phrase)) {
            return true;
        }
    }

    return false;
}

function fcc_ai_v2_is_compatibility_request(string $message): bool {
    $text = fcc_ai_v2_normalize_text($message);
    if($text === '') {
        return false;
    }

    if(fcc_ai_v2_contains_any($text, [
        'moze li se kombinirati', 'kombinirati', 'smiju li se',
        'ima li smisla uz', 'sto ide uz', 'koji proizvod ide uz',
        'sluzbeno povezan proizvod', 'sluzbeno povezan',
        'combine these', 'can i combine', 'can these be combined',
        'what goes with', 'officially related product',
    ])) {
        return true;
    }

    return preg_match(
        '/(?<![\p{L}\p{N}])(?:mogu|moze|smiju|smem|lahko|can|could)(?:\s+li)?(?![\p{L}\p{N}]).{0,180}(?<![\p{L}\p{N}])(?:zajedno|skupa|together|kombinir\p{L}*|combine\p{L}*)(?![\p{L}\p{N}])/u',
        $text
    ) === 1
        || preg_match(
            '/(?<![\p{L}\p{N}])(?:use|take|uzeti|uzimam|koristiti|koristim)(?![\p{L}\p{N}]).{0,180}(?<![\p{L}\p{N}])(?:zajedno|together)(?![\p{L}\p{N}])/u',
            $text
        ) === 1;
}

function fcc_ai_v2_relationship_runtime_blocked(
    array $relationship,
    array $sourceRow,
    array $targetRow,
    string $message,
    array $previousConstraints = [],
    array $medicalContextFlags = []
): bool {
    $safetyGate = is_array($relationship['safety_gate'] ?? null) ? $relationship['safety_gate'] : [];
    $blockedIf = $safetyGate['blocked_if'] ?? null;
    if(
        ($safetyGate['requires_goal_match'] ?? null) !== true
        || ($safetyGate['allow_if_safety_unknown'] ?? null) !== false
        || !is_bool($safetyGate['allow_general_combination'] ?? null)
        || !is_array($blockedIf)
        || !empty($safetyGate['blocked'])
    ) {
        return true;
    }

    if(
        in_array('product_unavailable', $blockedIf, true)
        && (
            (string) ($sourceRow['availability_status'] ?? '') !== 'available'
            || (string) ($targetRow['availability_status'] ?? '') !== 'available'
        )
    ) {
        return true;
    }

    if(
        in_array('required_safety_unknown', $blockedIf, true)
        && (
            (string) ($sourceRow['safety_review_status'] ?? 'unknown') !== 'complete'
            || (string) ($targetRow['safety_review_status'] ?? 'unknown') !== 'complete'
        )
    ) {
        return true;
    }

    $constraints = fcc_ai_v2_user_product_constraints($message, $previousConstraints);
    if(in_array('under_18', $blockedIf, true) && in_array('age_under_18', $constraints, true)) {
        return true;
    }
    $medicalFlags = array_fill_keys(fcc_ai_v2_unique_strings($medicalContextFlags, 12), true);
    $text = fcc_ai_v2_normalize_text($message);
    $medicationMentioned = isset($medicalFlags['medication_or_therapy'])
        || fcc_ai_v2_has_affirmed_regex(
            $text,
            '/(?<![\p{L}\p{N}])(?:terapij\p{L}*|lijek\p{L}*|medication\p{L}*|medicine\p{L}*|treatment\p{L}*|zdravil\p{L}*|лекарств\p{L}*|терап\p{L}*)(?![\p{L}\p{N}])/u'
        );
    if(in_array('medication_or_therapy', $blockedIf, true) && $medicationMentioned) {
        return true;
    }
    $pregnancyMentioned = isset($medicalFlags['pregnancy_or_breastfeeding'])
        || fcc_ai_v2_has_affirmed_regex(
            $text,
            '/(?<![\p{L}\p{N}])(?:trudn\p{L}*|dojim|dojenj\p{L}*|pregnan\p{L}*|breastfeed\p{L}*|nosec\p{L}*|бремен\p{L}*|кърм\p{L}*)(?![\p{L}\p{N}])/u'
        );
    if(in_array('pregnancy_or_breastfeeding', $blockedIf, true) && $pregnancyMentioned) {
        return true;
    }
    if(
        in_array('allergen_conflict', $blockedIf, true)
        && (
            fcc_ai_v2_product_constraint_conflicts($sourceRow, $constraints)
            || fcc_ai_v2_product_constraint_conflicts($targetRow, $constraints)
        )
    ) {
        return true;
    }

    if(
        in_array('duplicate_micronutrients', $blockedIf, true)
        && fcc_ai_v2_has_affirmed_regex(
            $text,
            '/(?<![\p{L}\p{N}])(?:vec|dodatno|uz\s+to|already|also).{0,70}(?:multivitamin\p{L}*|mineral\p{L}*|dodat\p{L}*\s+prehran\p{L}*|supplement\p{L}*)(?![\p{L}\p{N}])/u'
        )
    ) {
        return true;
    }

    return false;
}

function fcc_ai_v2_has_reviewed_general_combination(array $rows, array $productKeys, string $message = ''): bool {
    $productKeys = fcc_ai_v2_unique_strings($productKeys, 12);
    if(count($productKeys) < 2) {
        return false;
    }

    $mentioned = array_fill_keys($productKeys, true);
    foreach($productKeys as $sourceProductKey) {
        foreach((array) ($rows[$sourceProductKey]['relationships'] ?? []) as $relationship) {
            $targetProductKey = (string) ($relationship['target_product_key'] ?? '');
            $safetyGate = is_array($relationship['safety_gate'] ?? null) ? $relationship['safety_gate'] : [];
            $targetRow = is_array($rows[$targetProductKey] ?? null) ? $rows[$targetProductKey] : [];
            if(
                (string) ($relationship['relation_type'] ?? '') === 'complements'
                && isset($mentioned[$targetProductKey])
                && ($safetyGate['allow_general_combination'] ?? false) === true
                && $targetRow
                && !fcc_ai_v2_relationship_runtime_blocked(
                    $relationship,
                    (array) ($rows[$sourceProductKey] ?? []),
                    $targetRow,
                    $message
                )
            ) {
                return true;
            }
        }
    }

    return false;
}

function fcc_ai_v2_requires_oral_combination_review(string $message, array $mentions, array $rows): bool {
    if(!fcc_ai_v2_is_compatibility_request($message)) {
        return false;
    }

    $mentionedProductKeys = fcc_ai_v2_unique_strings(array_map(
        static fn(array $mention): string => empty($mention['negated']) ? (string) ($mention['product_key'] ?? '') : '',
        $mentions
    ), 12);
    if(count($mentionedProductKeys) < 2) {
        return false;
    }

    $oralOrUnknownCount = 0;
    foreach($mentionedProductKeys as $productKey) {
        if(in_array((string) ($rows[$productKey]['route'] ?? 'unknown'), ['oral', 'mixed', 'program', 'unknown'], true)) {
            $oralOrUnknownCount++;
        }
    }

    return $oralOrUnknownCount >= 2
        && !fcc_ai_v2_has_reviewed_general_combination($rows, $mentionedProductKeys, $message);
}

function fcc_ai_v2_normalize_relationships($relationships): array {
    $normalized = [];

    foreach(fcc_ai_v2_list($relationships) as $relationship) {
        if(!is_array($relationship)) {
            continue;
        }

        $relationId = (int) ($relationship['relation_id'] ?? 0);
        $relationType = trim((string) ($relationship['relation_type'] ?? ''));
        $targetProductKey = trim((string) ($relationship['target_product_key'] ?? ''));
        $goalKeys = fcc_ai_v2_unique_strings($relationship['goal_keys'] ?? [], 12);
        $evidenceSummary = trim((string) ($relationship['evidence_summary'] ?? ''));
        $reasonText = trim((string) ($relationship['reason_text'] ?? ''));
        $sourceRefs = array_values(array_filter(
            fcc_ai_v2_list($relationship['source_refs'] ?? []),
            static fn($sourceRef): bool => is_array($sourceRef) && !empty($sourceRef)
        ));

        if(
            $relationId <= 0
            || !in_array($relationType, ['complements', 'alternative', 'sequence', 'program_member'], true)
            || $targetProductKey === ''
            || !$goalKeys
            || $evidenceSummary === ''
            || $reasonText === ''
            || !$sourceRefs
        ) {
            continue;
        }

        $normalized[] = [
            'relation_id' => $relationId,
            'relation_type' => $relationType,
            'target_product_key' => $targetProductKey,
            'goal_keys' => $goalKeys,
            'evidence_summary' => mb_substr($evidenceSummary, 0, 1000),
            'reason_text' => mb_substr($reasonText, 0, 500),
            'source_refs' => $sourceRefs,
            'market_code' => mb_strtolower(trim((string) ($relationship['market_code'] ?? ''))),
            'safety_gate' => is_array($relationship['safety_gate'] ?? null) ? $relationship['safety_gate'] : [],
            'sequence_order' => max(0, (int) ($relationship['sequence_order'] ?? 0)),
        ];
    }

    usort($normalized, static function(array $left, array $right): int {
        return ($left['relation_id'] <=> $right['relation_id'])
            ?: strcmp($left['target_product_key'], $right['target_product_key']);
    });

    return $normalized;
}

function fcc_ai_v2_catalog_rows(array $catalog): array {
    $catalog_facts = is_array($catalog['facts'] ?? null) ? $catalog['facts'] : [];
    if(isset($catalog['products']) && is_array($catalog['products'])) {
        $catalog = $catalog['products'];
    }

    $rows = [];
    foreach($catalog as $key => $row) {
        if(!is_array($row)) {
            continue;
        }

        if(empty($row['product_key']) && is_string($key)) {
            $row['product_key'] = $key;
        }

        $product_key = trim((string) ($row['product_key'] ?? $row['id'] ?? ''));
        $canonical_name = trim((string) ($row['canonical_name'] ?? $row['name'] ?? ''));
        if($product_key === '' || $canonical_name === '') {
            continue;
        }

        $row['product_key'] = $product_key;
        $row['canonical_name'] = $canonical_name;
        $canonicalWithoutBrand = preg_replace('/^Forever\s+/iu', '', $canonical_name) ?? $canonical_name;
        $identityAliases = $canonicalWithoutBrand !== $canonical_name
            ? [$canonical_name, $canonicalWithoutBrand]
            : [$canonical_name];
        if($product_key === 'forever-garcinia-plus') {
            /* Customers commonly omit "Plus". Keep this identity shorthand
             * available even for a previously materialized catalog revision. */
            $identityAliases[] = 'Garcinia';
        }
        $row['aliases'] = fcc_ai_v2_unique_strings(array_merge($identityAliases, fcc_ai_v2_list($row['aliases'] ?? [])));
        $synthetic_facts = is_array($catalog_facts[$product_key] ?? null) ? $catalog_facts[$product_key] : [];
        $flattened_synthetic_facts = [];
        foreach($synthetic_facts as $factKey => $value) {
            if(
                is_scalar($value)
                && !is_bool($value)
                && !str_contains((string) $factKey, '_by_market')
                && !str_contains((string) $factKey, 'valid_until')
                && trim((string) $value) !== ''
            ) {
                $flattened_synthetic_facts[] = trim((string) $value);
            }
        }
        $row['market_facts'] = $synthetic_facts;

        $row['use_cases'] = fcc_ai_v2_unique_strings($row['use_cases'] ?? []);
        $row['ingredient_facts'] = fcc_ai_v2_unique_strings($row['ingredient_facts'] ?? []);
        $row['approved_support_facts'] = fcc_ai_v2_unique_strings(array_merge(
            fcc_ai_v2_list($row['approved_support_facts'] ?? []),
            $flattened_synthetic_facts
        ));
        $row['usage_facts'] = fcc_ai_v2_unique_strings($row['usage_facts'] ?? []);
        $row['safety_notes'] = fcc_ai_v2_unique_strings($row['safety_notes'] ?? []);
        $row['allergens_contains'] = array_values(array_filter(
            fcc_ai_v2_unique_strings($row['allergens_contains'] ?? [], 20),
            static fn(string $value): bool => in_array($value, [
                'soy', 'fish', 'milk', 'egg', 'tree_nuts', 'gluten', 'bee_products',
            ], true)
        ));
        $row['safety_flags'] = array_values(array_filter(
            fcc_ai_v2_unique_strings($row['safety_flags'] ?? [], 30),
            static fn(string $value): bool => in_array($value, [
                'caffeine', 'green_tea', 'pregnancy_warning', 'breastfeeding_warning',
                'under_18_warning', 'medication_consult', 'empty_stomach_warning',
                'iron_child_poisoning', 'external_use_only', 'avoid_eye_contact',
                'damaged_seal', 'dose_limit',
            ], true)
        ));
        $row['mandatory_safety_notes'] = fcc_ai_v2_unique_strings($row['mandatory_safety_notes'] ?? [], 12);
        $row['safety_review_status'] = in_array((string) ($row['safety_review_status'] ?? ''), ['complete', 'unknown'], true)
            ? (string) $row['safety_review_status']
            : 'unknown';
        $row['suitability_flags'] = array_values(array_filter(
            fcc_ai_v2_unique_strings($row['suitability_flags'] ?? [], 12),
            static fn(string $value): bool => in_array($value, ['vegan', 'vegetarian', 'gluten_free'], true)
        ));
        $row['suitability_review_status'] = in_array((string) ($row['suitability_review_status'] ?? ''), ['complete', 'unknown'], true)
            ? (string) $row['suitability_review_status']
            : 'unknown';
        $row['relationships'] = fcc_ai_v2_normalize_relationships($row['relationships'] ?? []);
        $row['route'] = trim((string) ($row['route'] ?? ''));
        if($row['route'] === '') {
            $identity = fcc_ai_v2_normalize_text($product_key . ' ' . $canonical_name);
            if(fcc_ai_v2_contains_any($identity, ['gelly', 'spray', 'lotion', 'creme', 'cream', 'cleanser', 'exfoliator', 'sunscreen', 'activator'])) {
                $row['route'] = 'topical';
            } elseif(fcc_ai_v2_contains_any($identity, ['c9', 'f15', 'dx4', 'vital5'])) {
                $row['route'] = 'program';
            } else {
                $row['route'] = 'oral';
            }
        }
        $row['advisor_status'] = trim((string) ($row['advisor_status'] ?? 'recommendable')) ?: 'recommendable';
        $row['availability_status'] = trim((string) ($row['availability_status'] ?? 'unknown')) ?: 'unknown';
        $row['official_url'] = trim((string) ($row['official_url'] ?? ''));
        $row['article_url'] = trim((string) ($row['article_url'] ?? ''));
        $rows[$product_key] = $row;
    }

    ksort($rows, SORT_STRING);

    foreach($rows as $productKey => &$row) {
        $row['relationships'] = array_values(array_filter(
            (array) ($row['relationships'] ?? []),
            static function(array $relationship) use ($rows, $productKey): bool {
                $targetProductKey = (string) ($relationship['target_product_key'] ?? '');
                return $targetProductKey !== ''
                    && $targetProductKey !== $productKey
                    && isset($rows[$targetProductKey]);
            }
        ));
    }
    unset($row);

    return $rows;
}

function fcc_ai_v2_advisor_context(array $catalog): array {
    if(is_array($catalog['__advisor_context'] ?? null)) {
        return $catalog['__advisor_context'];
    }
    if(is_array($catalog['advisor_context'] ?? null)) {
        return $catalog['advisor_context'];
    }
    return [];
}

function fcc_ai_v2_label_plan_rules(array $catalog = []): array {
    $context = fcc_ai_v2_advisor_context($catalog);
    $config = is_array($context['label_plans'] ?? null) ? $context['label_plans'] : [];
    if(
        (string) ($config['schema_version'] ?? '') !== 'fcc-product-advisor-label-plans-v1'
        || mb_strtolower((string) ($config['language_code'] ?? '')) !== 'hr'
        || mb_strtolower((string) ($config['market_code'] ?? '')) !== 'hr'
    ) {
        return [];
    }

    return is_array($config['plans'] ?? null) ? $config['plans'] : [];
}

/**
 * Validate a calendar date without consulting the runtime clock. Lexical
 * comparisons are safe only after this check because the accepted format is
 * fixed-width ISO 8601 (YYYY-MM-DD).
 */
function fcc_ai_v2_business_iso_date_is_valid($value): bool {
    if(!is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts) !== 1) {
        return false;
    }

    return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
}

/** A business-policy source must be an official Forever HTTPS document. */
function fcc_ai_v2_business_source_url_is_valid($value): bool {
    if(!is_string($value) || trim($value) !== $value || filter_var($value, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    $parts = parse_url($value);
    if(!is_array($parts) || mb_strtolower((string) ($parts['scheme'] ?? ''), 'UTF-8') !== 'https') {
        return false;
    }
    if(isset($parts['user']) || isset($parts['pass'])) {
        return false;
    }

    $host = mb_strtolower(rtrim((string) ($parts['host'] ?? ''), '.'), 'UTF-8');
    return $host === 'foreverliving.com' || str_ends_with($host, '.foreverliving.com');
}

/**
 * Validate the complete v1 HR business snapshot. This is intentionally strict:
 * a future policy or data shape must ship with a new reviewed schema instead of
 * silently inheriting old 2 CC, discount, package or FCC-access statements.
 */
function fcc_ai_v2_business_offer_is_valid(array $config, string $marketCode, string $asOfDate): bool {
    if(
        $marketCode !== 'HR'
        || ($config['schema_version'] ?? null) !== 'fcc-business-offer-v1'
        || ($config['market_code'] ?? null) !== 'HR'
        || !is_string($config['record_version'] ?? null)
        || preg_match('/^[a-z0-9][a-z0-9._-]{2,80}$/D', (string) $config['record_version']) !== 1
        || !fcc_ai_v2_business_iso_date_is_valid($asOfDate)
        || !fcc_ai_v2_business_iso_date_is_valid($config['effective_from'] ?? null)
        || !fcc_ai_v2_business_iso_date_is_valid($config['reviewed_at'] ?? null)
        || !fcc_ai_v2_business_iso_date_is_valid($config['review_by'] ?? null)
        || strcmp((string) $config['effective_from'], $asOfDate) > 0
        || strcmp($asOfDate, (string) $config['review_by']) > 0
        || strcmp((string) $config['effective_from'], (string) $config['review_by']) > 0
        || strcmp((string) $config['reviewed_at'], (string) $config['effective_from']) < 0
        || strcmp((string) $config['reviewed_at'], (string) $config['review_by']) > 0
    ) {
        return false;
    }

    $sources = $config['sources'] ?? null;
    if(!is_array($sources) || count($sources) < 1) {
        return false;
    }
    foreach($sources as $source) {
        if(
            !is_array($source)
            || !is_string($source['source_id'] ?? null)
            || preg_match('/^[a-z0-9][a-z0-9._-]{2,100}$/D', (string) $source['source_id']) !== 1
            || !is_string($source['title'] ?? null)
            || trim((string) $source['title']) === ''
            || !fcc_ai_v2_business_source_url_is_valid($source['url'] ?? null)
        ) {
            return false;
        }
        foreach(['revised_at', 'effective_from'] as $optionalDateKey) {
            if(array_key_exists($optionalDateKey, $source) && !fcc_ai_v2_business_iso_date_is_valid($source[$optionalDateKey])) {
                return false;
            }
        }
    }

    $facts = $config['facts'] ?? null;
    if(
        !is_array($facts)
        || !is_int($facts['qualification_window_months'] ?? null)
        || $facts['qualification_window_months'] < 1
        || $facts['qualification_window_months'] > 24
        || !is_int($facts['qualification_cc'] ?? null)
        || $facts['qualification_cc'] < 1
        || $facts['qualification_cc'] > 100
        || !is_int($facts['ongoing_discount_percent'] ?? null)
        || $facts['ongoing_discount_percent'] < 1
        || $facts['ongoing_discount_percent'] > 100
        || ($facts['marketing_plan_entry'] ?? null) !== 'opt_in'
        || !is_string($facts['initial_fbo_level'] ?? null)
        || trim((string) $facts['initial_fbo_level']) === ''
        || ($facts['registration_channel'] ?? null) !== 'official_forever_flow_only'
        || ($facts['income_guaranteed'] ?? null) !== false
        || ($facts['fcc_access_automatic_forever_right'] ?? null) !== false
        || ($facts['fcc_access_rule'] ?? null) !== 'mentor_manual_approval_after_structure_verification'
    ) {
        return false;
    }

    $packages = $config['packages'] ?? null;
    foreach(['skin_body', 'sport_wellness'] as $packageKey) {
        $package = is_array($packages) ? ($packages[$packageKey] ?? null) : null;
        if(
            !is_array($package)
            || !is_string($package['item_no'] ?? null)
            || preg_match('/^\d{2,10}$/D', (string) $package['item_no']) !== 1
            || !is_string($package['name'] ?? null)
            || trim((string) $package['name']) === ''
            || !is_float($package['published_cc'] ?? null)
            || !is_finite($package['published_cc'])
            || $package['published_cc'] <= 0
            || !is_int($package['qualification_cc_credit'] ?? null)
            || $package['qualification_cc_credit'] !== $facts['qualification_cc']
            || ($package['public_price_verified'] ?? null) !== false
            || !is_bool($package['contents_must_be_rechecked'] ?? null)
        ) {
            return false;
        }
        foreach(['price', 'public_price', 'price_amount', 'price_text', 'currency'] as $unverifiedPriceKey) {
            if(array_key_exists($unverifiedPriceKey, $package)) {
                return false;
            }
        }
    }

    $messages = $config['messages_hr'] ?? null;
    if(!is_array($messages) || !is_string($messages['disclaimer'] ?? null) || trim((string) $messages['disclaimer']) === '') {
        return false;
    }
    foreach([
        'start_package_overview',
        'start_package_skin_body',
        'start_package_sport_wellness',
        'personal_discount',
        'selling_or_team',
        'qualification_overview',
        'qualification_correction',
        'start_package_qualification_fcc',
        'fcc_access',
        'package_price',
        'opportunity',
    ] as $messageKey) {
        if(!is_array($messages[$messageKey] ?? null) || !$messages[$messageKey]) {
            return false;
        }
        foreach($messages[$messageKey] as $message) {
            if(!is_string($message) || trim($message) === '') {
                return false;
            }
        }
    }

    $disclaimer = fcc_ai_v2_normalize_text((string) $messages['disclaimer']);
    $qualificationCopy = fcc_ai_v2_normalize_text(implode(' ', $messages['qualification_correction']));
    $fccAccessCopy = fcc_ai_v2_normalize_text(implode(' ', $messages['fcc_access']));
    $priceCopy = fcc_ai_v2_normalize_text(implode(' ', $messages['package_price']));
    $allCopy = fcc_ai_v2_normalize_text(implode(' ', array_merge(
        ...array_map(
            static fn($messageGroup): array => is_array($messageGroup) ? $messageGroup : [(string) $messageGroup],
            array_values($messages)
        )
    )));
    if(
        !str_contains($disclaimer, 'zarada nije zajamcena')
        || !str_contains($disclaimer, 'fcc je zasebna mentorska pogodnost')
        || !str_contains($qualificationCopy, 'nisu potrebna 4 cc')
        || !str_contains($qualificationCopy, '2 cc unutar dva uzastopna mjeseca')
        || !str_contains($fccAccessCopy, 'nije automatsko forever pravo')
        || !str_contains($priceCopy, 'ne mogu potvrditi')
        || !str_contains($priceCopy, 'nemam trenutno provjerenu javnu cijenu')
        || preg_match('/(?<!ne\s)(?:zajamcena|garantirana|sigurna|laka|brza)\s+zarada/u', $allCopy) === 1
        || preg_match('/(?:cijena|iznos|kosta).{0,40}\b\d+(?:[.,]\d+)?\s*(?:eur|eura)(?![\p{L}\p{N}])/u', $allCopy) === 1
    ) {
        return false;
    }

    return true;
}

/**
 * Load exact Croatian business terms only while the market record is current.
 * If the review date expires, runtime falls back to generic non-numeric
 * guidance instead of continuing to quote potentially stale CC/package rules.
 */
function fcc_ai_v2_business_offer_context(array $catalog, string $marketCode = 'HR'): array {
    $marketCode = mb_strtoupper(trim($marketCode), 'UTF-8');
    $context = fcc_ai_v2_advisor_context($catalog);
    $businessOffers = is_array($context['business_offers'] ?? null) ? $context['business_offers'] : [];
    $config = is_array($businessOffers[$marketCode] ?? null) ? $businessOffers[$marketCode] : [];
    $asOfDate = $context['as_of_date'] ?? null;
    if(!is_string($asOfDate) || !fcc_ai_v2_business_offer_is_valid($config, $marketCode, $asOfDate)) {
        return [];
    }

    return $config;
}

/**
 * Return a label-derived 30-day purchase calculation only while both the
 * reviewed usage text and an available reviewed package still match. A catalog
 * refresh therefore disables stale calculations automatically.
 */
function fcc_ai_v2_label_plan_text(array $row, string $language, array $catalog = []): string {
    if($language !== 'hr' || mb_strtolower((string) ($row['market_code'] ?? 'hr')) !== 'hr') {
        return '';
    }

    $productKey = (string) ($row['product_key'] ?? '');
    $rule = fcc_ai_v2_label_plan_rules($catalog)[$productKey] ?? null;
    if(!is_array($rule)) {
        return '';
    }

    $usageText = fcc_ai_v2_normalize_text(implode(' ', fcc_ai_v2_unique_strings($row['usage_facts'] ?? [], 12)));
    if($usageText === '') {
        return '';
    }
    foreach(fcc_ai_v2_unique_strings($rule['usage_contains'] ?? [], 8) as $requiredUsageFragment) {
        if(!str_contains($usageText, fcc_ai_v2_normalize_text($requiredUsageFragment))) {
            return '';
        }
    }

    $offerVariants = $row['offer_variants'] ?? [];
    if(is_string($offerVariants)) {
        $decoded = json_decode($offerVariants, true);
        $offerVariants = is_array($decoded) ? $decoded : [];
    } elseif(is_object($offerVariants)) {
        $offerVariants = (array) $offerVariants;
    }
    if(!is_array($offerVariants)) {
        return '';
    }

    $requiredLabels = array_fill_keys(array_map(
        'fcc_ai_v2_normalize_text',
        fcc_ai_v2_unique_strings($rule['offer_package_labels'] ?? [], 8)
    ), true);
    $matchedOffer = false;
    foreach($offerVariants as $offer) {
        if(!is_array($offer)) {
            continue;
        }
        $packageLabel = fcc_ai_v2_normalize_text((string) ($offer['package_label'] ?? ''));
        if(
            isset($requiredLabels[$packageLabel])
            && (string) ($offer['availability_status'] ?? '') === 'available'
            && (string) ($offer['review_status'] ?? '') === 'approved'
        ) {
            $matchedOffer = true;
            break;
        }
    }
    if(!$matchedOffer) {
        return '';
    }

    return trim((string) ($rule['text_hr'] ?? ''));
}

/**
 * Extract durable user constraints that can deterministically exclude a
 * catalog candidate. A missing product allergen is never interpreted as an
 * "allergen free" claim; it only means that no explicit conflict was found.
 */
function fcc_ai_v2_user_product_constraints(string $message, array $previous = []): array {
    $text = fcc_ai_v2_normalize_text($message);
    $constraints = array_fill_keys(fcc_ai_v2_unique_strings($previous, 30), true);
    $allergenPatterns = [
        'soy' => '(?:soj\p{L}*|soy)',
        'fish' => '(?:rib\p{L}*|fish\p{L}*|lignj\p{L}*|mekusc\p{L}*|mollusc\p{L}*|squid\p{L}*|shellfish\p{L}*)',
        'aloe' => '(?:aloj\p{L}*|aloe)',
        'milk' => '(?:mlijec\p{L}*|mlijek\p{L}*|mlek\p{L}*|milk\p{L}*|laktoz\p{L}*|lactos\p{L}*)',
        'egg' => '(?:jaj\p{L}*|egg\p{L}*)',
        'tree_nuts' => '(?:oras\p{L}*|orah\p{L}*|badem\p{L}*|ljesnjak\p{L}*|nuts?|almond\p{L}*|hazelnut\p{L}*)',
        'gluten' => '(?:gluten\p{L}*|celijak\p{L}*|coeliac\p{L}*|celiac\p{L}*)',
        'bee_products' => '(?:pcel\p{L}*|propolis\p{L}*|royal\s+jelly|bee\s+products?|maticn\p{L}*\s+mlijec\p{L}*|med(?:a|u|om)?)',
    ];
    /* "Bez/without" must sit directly in front of the excluded ingredient.
     * Treating it like the stronger allergy cues across a whole clause turns
     * ordinary flavour preferences such as "aloe napitak bez dodatnih okusa"
     * into a false aloe allergy. */
    $constraintCue = '(?:alerg\p{L}*|anafilaks\p{L}*|intoleran\p{L}*|ne\s+podnos\p{L}*|ne\s+smij\p{L}*|izbjegav\p{L}*|allerg\p{L}*|intoleran\p{L}*|cannot\s+have|can\'t\s+have|avoid\p{L}*)';
    $clearCue = '(?:nisam|nemam|nije|nema|not|no\s+longer)\s+(?:vise\s+)?(?:alerg\p{L}*|intoleran\p{L}*)';

    foreach($allergenPatterns as $allergen => $allergenPattern) {
        $key = 'allergen:' . $allergen;
        $clearPattern = '/(?<![\p{L}\p{N}])(?:' . $clearCue . '.{0,70}' . $allergenPattern
            . '|' . $allergenPattern . '.{0,70}(?:mi\s+ne\s+smeta|je\s+u\s+redu|is\s+fine))(?![\p{L}\p{N}])/u';
        if(preg_match($clearPattern, $text) === 1) {
            unset($constraints[$key]);
            continue;
        }

        $constraintPattern = '/(?<![\p{L}\p{N}])(?:' . $constraintCue . '.{0,70}' . $allergenPattern
            . '|' . $allergenPattern . '.{0,70}' . $constraintCue . ')(?![\p{L}\p{N}])/u';
        $withoutConstraintPattern = '/(?<![\p{L}\p{N}])(?:bez|without)\s+'
            . '(?:(?:trag\p{L}*|sadrz\p{L}*|sastoj\p{L}*|proizvod\p{L}*|iz|od|dodan\p{L}*|added|any)\s+){0,4}'
            . $allergenPattern . '(?![\p{L}\p{N}])/u';
        if(
            fcc_ai_v2_has_affirmed_regex($text, $constraintPattern)
            || fcc_ai_v2_has_affirmed_regex($text, $withoutConstraintPattern)
        ) {
            $constraints[$key] = true;
        }
    }

    if(preg_match('/(?<![\p{L}\p{N}])(?:celijak\p{L}*|coeliac\p{L}*|celiac\p{L}*)(?![\p{L}\p{N}])/u', $text) === 1) {
        $constraints['allergen:gluten'] = true;
    }
    if(preg_match('/(?<![\p{L}\p{N}])(?:nisam|not)\s+vegan\p{L}*(?![\p{L}\p{N}])/u', $text) === 1) {
        unset($constraints['diet:vegan']);
    } elseif(fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:vegan\p{L}*)(?![\p{L}\p{N}])/u')) {
        $constraints['diet:vegan'] = true;
    }
    if(preg_match('/(?<![\p{L}\p{N}])(?:nisam|not)\s+vegetarij\p{L}*(?![\p{L}\p{N}])/u', $text) === 1) {
        unset($constraints['diet:vegetarian']);
    } elseif(fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:vegetarij\p{L}*|vegetarian\p{L}*)(?![\p{L}\p{N}])/u')) {
        $constraints['diet:vegetarian'] = true;
    }
    if(preg_match('/(?<![\p{L}\p{N}])(?:vise\s+ne\s+trebam|do\s+not\s+need)\s+(?:bez\s+glutena|gluten[\s-]+free)(?![\p{L}\p{N}])/u', $text) === 1) {
        unset($constraints['diet:gluten_free']);
    } elseif(fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:bez\s+glutena|gluten[\s-]+free|celijak\p{L}*|coeliac\p{L}*|celiac\p{L}*)(?![\p{L}\p{N}])/u')) {
        $constraints['diet:gluten_free'] = true;
    }

    $flagPatterns = [
        'caffeine' => '(?:kofein\p{L}*|caffein\p{L}*)',
        'green_tea' => '(?:zelen\p{L}*\s+caj\p{L}*|green\s+tea)',
    ];
    foreach($flagPatterns as $flag => $flagPattern) {
        $key = 'flag:' . $flag;
        $clearPattern = '/(?<![\p{L}\p{N}])(?:vise\s+)?(?:mi\s+ne\s+smeta|mogu\s+uzimati|can\s+have).{0,45}' . $flagPattern . '(?![\p{L}\p{N}])/u';
        if(preg_match($clearPattern, $text) === 1) {
            unset($constraints[$key]);
            continue;
        }
        $flagCue = '(?:osjetljiv\p{L}*|smeta\p{L}*|ne\s+podnos\p{L}*|ne\s+smij\p{L}*|izbjegav\p{L}*|bez|sensitive|avoid\p{L}*|without|can\'t\s+have|cannot\s+have)';
        $pattern = '/(?<![\p{L}\p{N}])(?:' . $flagCue . '.{0,60}' . $flagPattern
            . '|' . $flagPattern . '.{0,60}' . $flagCue . ')(?![\p{L}\p{N}])/u';
        if(fcc_ai_v2_has_affirmed_regex($text, $pattern)) {
            $constraints[$key] = true;
        }
    }

    $age = null;
    if(preg_match('/\b(?:imam|ima|star\p{L}*|age(?:d)?)\D{0,12}(\d{1,2})\s*(?:godin\p{L}*|years?\s+old)?\b/u', $text, $ageMatch) === 1) {
        $age = (int) ($ageMatch[1] ?? 0);
    } elseif(preg_match('/\b(?:dijete|dete|child)\D{0,12}(\d{1,2})\s*(?:godin\p{L}*|years?)\b/u', $text, $ageMatch) === 1) {
        $age = (int) ($ageMatch[1] ?? 0);
    }
    if($age !== null) {
        if($age > 0 && $age < 18) {
            $constraints['age_under_18'] = true;
        } elseif($age >= 18) {
            unset($constraints['age_under_18']);
        }
    } elseif(fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:za\s+(?:moje\s+)?dijete|for\s+(?:my\s+)?child|maloljetn\p{L}*)(?![\p{L}\p{N}])/u')) {
        $constraints['age_under_18'] = true;
    }

    $result = array_keys($constraints);
    sort($result, SORT_STRING);
    return array_values($result);
}

function fcc_ai_v2_product_constraint_conflicts(array $row, array $constraints): array {
    $constraints = array_fill_keys(fcc_ai_v2_unique_strings($constraints, 30), true);
    if(!$constraints) {
        return [];
    }

    $conflicts = [];
    $declaredAllergens = array_fill_keys((array) ($row['allergens_contains'] ?? []), true);
    foreach(['soy', 'fish', 'milk', 'egg', 'tree_nuts', 'gluten', 'bee_products'] as $allergen) {
        if(!isset($constraints['allergen:' . $allergen])) {
            continue;
        }
        if(isset($declaredAllergens[$allergen])) {
            $conflicts[] = [
                'conflict_type' => 'allergen',
                'constraint_key' => $allergen,
            ];
            continue;
        }

        $explicitlyConfirmedFree = $allergen === 'gluten'
            && (string) ($row['suitability_review_status'] ?? 'unknown') === 'complete'
            && in_array('gluten_free', (array) ($row['suitability_flags'] ?? []), true);
        if(!$explicitlyConfirmedFree) {
            $conflicts[] = [
                'conflict_type' => 'allergen_absence_unconfirmed',
                'constraint_key' => $allergen,
            ];
        }
    }
    foreach(['caffeine', 'green_tea'] as $flag) {
        if(!isset($constraints['flag:' . $flag])) {
            continue;
        }
        $conflicts[] = [
            'conflict_type' => in_array($flag, (array) ($row['safety_flags'] ?? []), true)
                ? 'safety_flag'
                : 'safety_absence_unconfirmed',
            'constraint_key' => $flag,
        ];
    }
    if(isset($constraints['age_under_18'])) {
        $conflicts[] = [
            'conflict_type' => in_array('under_18_warning', (array) ($row['safety_flags'] ?? []), true)
                ? 'age_restriction'
                : 'age_suitability_unconfirmed',
            'constraint_key' => 'under_18',
        ];
    }

    $suitabilityFlags = array_fill_keys((array) ($row['suitability_flags'] ?? []), true);
    $dietRequirements = [
        'diet:vegan' => ['vegan'],
        'diet:vegetarian' => ['vegetarian', 'vegan'],
        'diet:gluten_free' => ['gluten_free'],
    ];
    foreach($dietRequirements as $constraint => $acceptedFlags) {
        if(!isset($constraints[$constraint])) {
            continue;
        }
        $confirmed = (string) ($row['suitability_review_status'] ?? 'unknown') === 'complete';
        if($confirmed) {
            $confirmed = false;
            foreach($acceptedFlags as $acceptedFlag) {
                if(isset($suitabilityFlags[$acceptedFlag])) {
                    $confirmed = true;
                    break;
                }
            }
        }
        if(!$confirmed) {
            $conflicts[] = [
                'conflict_type' => 'suitability_unconfirmed',
                'constraint_key' => substr($constraint, strlen('diet:')),
            ];
        }
    }

    if(!$conflicts && (string) ($row['safety_review_status'] ?? 'unknown') !== 'complete') {
        $conflicts[] = [
            'conflict_type' => 'safety_review_unknown',
            'constraint_key' => (string) (array_key_first($constraints) ?? 'declared_constraint'),
        ];
    }

    return $conflicts;
}

function fcc_ai_v2_relationship_goal_match(
    string $message,
    string $language,
    array $state,
    array $relationship,
    array $mentionedProductKeys = [],
    bool $explicitCompatibilityRequest = false
): string {
    $goalKeys = fcc_ai_v2_unique_strings($relationship['goal_keys'] ?? [], 12);
    if(!$goalKeys) {
        return '';
    }

    $targetProductKey = (string) ($relationship['target_product_key'] ?? '');
    if($targetProductKey !== '' && in_array($targetProductKey, $mentionedProductKeys, true)) {
        return (string) ($goalKeys[0] ?? 'explicit_related_product');
    }

    if($explicitCompatibilityRequest) {
        if(count($mentionedProductKeys) >= 2) {
            return '';
        }
        return (string) ($goalKeys[0] ?? 'explicit_compatibility_request');
    }

    $stateGoalKeys = array_fill_keys(fcc_ai_v2_unique_strings($state['goal_keys'] ?? [], 20), true);
    $normalizedMessage = fcc_ai_v2_normalize_text($message);
    $messageTokens = fcc_ai_v2_meaningful_tokens($normalizedMessage, $language);
    $messageStems = [];
    foreach($messageTokens as $messageToken) {
        $stem = fcc_ai_v2_token_stem($messageToken);
        if(mb_strlen($stem) >= 4) {
            $messageStems[$stem] = true;
        }
    }

    foreach($goalKeys as $goalKey) {
        if(isset($stateGoalKeys[$goalKey])) {
            return $goalKey;
        }

        $goalPhrase = str_replace(['_', '-'], ' ', $goalKey);
        if(fcc_ai_v2_contains_phrase($normalizedMessage, $goalPhrase)) {
            return $goalKey;
        }

        $goalTokens = fcc_ai_v2_meaningful_tokens($goalPhrase, $language);
        $distinctiveGoalStems = [];
        foreach($goalTokens as $goalToken) {
            $stem = fcc_ai_v2_token_stem($goalToken);
            if(mb_strlen($stem) >= 4) {
                $distinctiveGoalStems[$stem] = true;
            }
        }
        if(!$distinctiveGoalStems) {
            continue;
        }

        $matched = count(array_intersect_key($distinctiveGoalStems, $messageStems));
        $required = count($distinctiveGoalStems) === 1 ? 1 : (int) ceil(count($distinctiveGoalStems) / 2);
        if($matched >= $required) {
            return $goalKey;
        }
    }

    return '';
}

function fcc_ai_v2_support_relationships_for_plan(
    string $message,
    string $language,
    array $state,
    array $primaryCandidates,
    array $mentions,
    array $rows,
    bool $explicitCompatibilityRequest = false,
    bool $explicitSequenceRequest = false
): array {
    $mentionedProductKeys = fcc_ai_v2_unique_strings(array_map(
        static fn(array $mention): string => empty($mention['negated']) ? (string) ($mention['product_key'] ?? '') : '',
        $mentions
    ), 12);
    $relationshipConstraints = fcc_ai_v2_user_product_constraints(
        $message,
        fcc_ai_v2_unique_strings($state['user_constraints'] ?? [], 30)
    );
    $relationships = [];
    $seen = [];

    foreach($primaryCandidates as $candidate) {
        $sourceProductKey = (string) ($candidate['product_key'] ?? '');
        $sourceRow = $rows[$sourceProductKey] ?? null;
        if(!$sourceRow) {
            continue;
        }

        foreach((array) ($sourceRow['relationships'] ?? []) as $relationship) {
            $relationType = (string) ($relationship['relation_type'] ?? '');
            if(
                $relationType !== 'complements'
                && !($relationType === 'sequence' && $explicitSequenceRequest)
            ) {
                continue;
            }

            $targetProductKey = (string) ($relationship['target_product_key'] ?? '');
            $targetRow = $rows[$targetProductKey] ?? null;
            if(
                !$targetRow
                || ($targetRow['advisor_status'] ?? '') !== 'recommendable'
                || ($targetRow['availability_status'] ?? '') === 'blocked'
                || !empty(($relationship['safety_gate'] ?? [])['blocked'])
                || fcc_ai_v2_relationship_runtime_blocked(
                    $relationship,
                    $sourceRow,
                    $targetRow,
                    $message,
                    $relationshipConstraints,
                    fcc_ai_v2_unique_strings($state['medical_context_flags'] ?? [], 12)
                )
            ) {
                continue;
            }

            $matchedGoal = fcc_ai_v2_relationship_goal_match(
                $message,
                $language,
                $state,
                $relationship,
                $mentionedProductKeys,
                $explicitCompatibilityRequest || $explicitSequenceRequest
            );
            if($matchedGoal === '') {
                continue;
            }

            $identity = $sourceProductKey . '>' . $targetProductKey;
            if(isset($seen[$identity])) {
                continue;
            }
            $seen[$identity] = true;
            $relationship['source_product_key'] = $sourceProductKey;
            $relationship['matched_goal_key'] = $matchedGoal;
            $relationships[] = $relationship;

            if(count($relationships) >= 16) {
                break 2;
            }
        }
    }

    return $relationships;
}

/**
 * Program membership is factual package content, never an add-on recommendation.
 * Lookup-only members may be named because the approved relationship itself is
 * the evidence; blocked or missing products remain excluded.
 */
function fcc_ai_v2_program_members_for_plan(array $sourceProductKeys, array $rows): array {
    $relationships = [];
    $seen = [];
    foreach(fcc_ai_v2_unique_strings($sourceProductKeys, 3) as $sourceProductKey) {
        $sourceRow = $rows[$sourceProductKey] ?? null;
        if(!$sourceRow) {
            continue;
        }
        foreach((array) ($sourceRow['relationships'] ?? []) as $relationship) {
            if(!is_array($relationship) || (string) ($relationship['relation_type'] ?? '') !== 'program_member') {
                continue;
            }
            $targetProductKey = trim((string) ($relationship['target_product_key'] ?? ''));
            $targetRow = $rows[$targetProductKey] ?? null;
            $identity = $sourceProductKey . '>' . $targetProductKey;
            if(
                $targetProductKey === ''
                || !$targetRow
                || (string) ($targetRow['advisor_status'] ?? '') === 'blocked'
                || (string) ($targetRow['availability_status'] ?? '') === 'blocked'
                || empty($relationship['source_refs'])
                || trim((string) ($relationship['evidence_summary'] ?? '')) === ''
                || isset($seen[$identity])
            ) {
                continue;
            }
            $seen[$identity] = true;
            $relationship['source_product_key'] = $sourceProductKey;
            $relationship['matched_goal_key'] = (string) (($relationship['goal_keys'][0] ?? '') ?: 'program_contents');
            $relationships[] = $relationship;
        }
    }

    usort($relationships, static function(array $left, array $right): int {
        return ((int) ($left['sequence_order'] ?? 0) <=> (int) ($right['sequence_order'] ?? 0))
            ?: strcmp((string) ($left['target_product_key'] ?? ''), (string) ($right['target_product_key'] ?? ''));
    });
    return array_slice($relationships, 0, 12);
}

function fcc_ai_v2_detect_language(string $message, string $previous = 'hr'): string {
    $message = trim($message);
    $previous = in_array($previous, ['hr', 'en', 'sl', 'bg', 'de', 'sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'], true) ? $previous : 'hr';

    if($message === '') {
        return $previous;
    }

    $normalized = fcc_ai_v2_normalize_text($message);
    $explicitSignals = [
        'hr' => ['od sada hrvatski', 'odsada hrvatski', 'odgovori hrvatski', 'odgovaraj hrvatski', 'na hrvatskom'],
        'en' => ['answer in english', 'respond in english', 'reply in english', 'in english please', 'english please', 'na engleskom'],
        'sl' => ['od zdaj odgovarjaj slovensko', 'odgovarjaj slovensko', 'odgovori slovensko', 'v slovenscini'],
        'de' => ['antworte auf deutsch', 'bitte auf deutsch', 'na nemackom', 'na njemackom'],
        'sr' => ['odgovori na srpskom', 'odgovaraj na srpskom', 'na srpskom', 'srpski latinica'],
        'bg' => ['отговаряй на български', 'отговори на български'],
    ];
    foreach($explicitSignals as $explicitLanguage => $signals) {
        if(fcc_ai_v2_contains_any($normalized, $signals)) {
            return $explicitLanguage;
        }
    }

    // Preserve an explicitly selected supported locale; shared words cannot switch Serbian to Croatian.
    if(in_array($previous, ['sr', 'de', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'], true)) return $previous;

    if(preg_match('/[\x{0400}-\x{04FF}]/u', $message)) {
        return 'bg';
    }

    if(preg_match('/\b(?:tezko\s+diham|ne\s+morem\s+dihati|samomor\p{L}*|nosec\p{L}*|zdravnik\p{L}*|zdravil\p{L}*)\b/u', $normalized) === 1) {
        return 'sl';
    }
    if(preg_match('/\b(?:i\s+(?:want|need|have|am)|cannot\s+breathe|chest\s+pain|kill\s+myself|commit\s+suicide|suicidal|pregnant)\b/u', $normalized) === 1) {
        return 'en';
    }

    $tokens = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if(count($tokens) <= 2 && mb_strlen($normalized) <= 18) {
        return $previous;
    }

    $scores = ['hr' => 0, 'en' => 0, 'sl' => 0, 'bg' => 0];
    $signals = [
        'hr' => ['molim', 'trebam', 'zelim', 'proizvod', 'preporuka', 'imam', 'kako', 'sto', 'sta', 'koji', 'koja', 'dijete', 'lijek', 'hvala', 'suradnja', 'zarada', 'prihod'],
        'en' => [
            'please', 'need', 'want', 'product', 'recommend', 'have', 'how', 'what',
            'which', 'child', 'medicine', 'thanks', 'interested', 'business',
            'opportunity', 'join', 'become', 'distributor', 'earn', 'income', 'selling',
            'network', 'pyramid', 'owner', 'app',
        ],
        'sl' => [
            'prosim', 'potrebujem', 'zelim', 'izdelek', 'priporoc', 'lahko', 'kako',
            'kaj', 'kateri', 'otrok', 'zdravilo', 'hvala', 'tezko', 'diham',
            'zdravnik', 'noseca', 'bolecina', 'samomor', 'sodelovanje', 'foreverjem',
            'postanem', 'zasluzim', 'prodajo', 'izdelkov', 'ali', 'mrezni',
            'piramidna', 'lastnikom', 'govoriti', 'delo', 'doma',
        ],
    ];

    foreach($signals as $language => $words) {
        foreach($words as $word) {
            if(fcc_ai_v2_contains_phrase($normalized, $word)) {
                $scores[$language]++;
            }
        }
    }

    arsort($scores, SORT_NUMERIC);
    $top_language = (string) array_key_first($scores);
    $top_score = (int) reset($scores);
    $next_score = (int) (array_values($scores)[1] ?? 0);

    return $top_score >= 2 && $top_score > $next_score ? $top_language : $previous;
}

function fcc_ai_v2_detect_market(string $message, string $previous = ''): string {
    $previous = mb_strtoupper(trim($previous), 'UTF-8');
    if(preg_match('/^[A-Z]{2}$/', $previous) !== 1) {
        $previous = '';
    }

    $text = fcc_ai_v2_normalize_text($message);
    $marketSignals = [
        'HR' => ['hrvatska', 'hrvatskoj', 'hrvatsku', 'croatia', 'croatian market', 'hr trziste'],
        'SI' => ['slovenija', 'sloveniji', 'sloveniju', 'slovenia', 'slovenski trg', 'si trziste'],
        'DE' => ['njemacka', 'njemackoj', 'njemacku', 'germany', 'deutschland', 'de trziste'],
        'AT' => ['austrija', 'austriji', 'austriju', 'austria', 'at trziste'],
        'IT' => ['italija', 'italiji', 'italiju', 'italy', 'italia'],
        'GB' => ['ujedinjeno kraljevstvo', 'velika britanija', 'united kingdom', 'uk market', 'gb market'],
        'US' => ['sjedinjene drzave', 'usa', 'united states', 'us market'],
        'RS' => ['srbija', 'srbiji', 'srbiju', 'serbia', 'rs trziste'],
        'BA' => ['bosna i hercegovina', 'bosni i hercegovini', 'bih', 'ba trziste'],
    ];
    foreach($marketSignals as $market => $signals) {
        if(fcc_ai_v2_contains_any($text, $signals)) {
            return $market;
        }
    }

    return $previous;
}

function fcc_ai_v2_contains_alias(string $message, string $alias): bool {
    if(fcc_ai_v2_contains_phrase($message, $alias)) {
        return true;
    }

    $normalizedAlias = fcc_ai_v2_normalize_text($alias);
    $normalizedMessage = fcc_ai_v2_normalize_text($message);
    /* Official names are not always punctuated consistently between the
     * immutable catalog and customer wording (for example "Nature Min" vs
     * "Nature-Min"). Treat only a letter-to-letter hyphen as a word
     * separator; numeric forms such as omega-3 remain unchanged. */
    $separatorNormalizedAlias = preg_replace('/(?<=\p{L})-(?=\p{L})/u', ' ', $normalizedAlias) ?? $normalizedAlias;
    $separatorNormalizedMessage = preg_replace('/(?<=\p{L})-(?=\p{L})/u', ' ', $normalizedMessage) ?? $normalizedMessage;
    if(fcc_ai_v2_contains_phrase($separatorNormalizedMessage, $separatorNormalizedAlias)) {
        return true;
    }

    $parts = preg_split('/\s+/u', $normalizedAlias, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if(count($parts) < 2) {
        return false;
    }

    $last = array_pop($parts);
    if(mb_strlen((string) $last, 'UTF-8') < 6 || preg_match('/^\p{L}+$/u', (string) $last) !== 1) {
        return false;
    }

    $prefix = implode('\\s+', array_map(static fn(string $part): string => preg_quote($part, '/'), $parts));
    $pattern = '(?<![\p{L}\p{N}])' . $prefix . '\\s+' . preg_quote((string) $last, '/') . '(?:a|u|om|em|i|e)?(?![\p{L}\p{N}])';

    return preg_match('/' . $pattern . '/u', fcc_ai_v2_normalize_text($message)) === 1;
}

function fcc_ai_v2_has_affirmed_regex(string $text, string $pattern): bool {
    $text = fcc_ai_v2_normalize_clause_text($text);
    if($text === '' || preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
        return false;
    }

    foreach((array) ($matches[0] ?? []) as $match) {
        $matchedText = (string) ($match[0] ?? '');
        $offset = (int) ($match[1] ?? 0);
        $prefix = substr($text, max(0, $offset - 90), min(90, $offset));
        $prefix = preg_replace('/.*(?:[\.!?;,]|\bali\b|\bnego\b|\bbut\b|\binstead\b|\brather\s+than\b|\bampak\b)\s*/u', '', $prefix) ?? $prefix;
        $suffix = substr($text, $offset + strlen($matchedText), 45);
        $suffix = preg_replace('/(?:[\.!?;,]).*/u', '', $suffix) ?? $suffix;

        $negatedBefore = preg_match('/(?:^|\s)(?:ne|nemam|nema|nisam|nije|nikad|brez|nisem|nimam|not|no|without|don\'t|do\s+not|am\s+not|не|нямам|няма|не\s+съм|без)(?:\s+\p{L}+){0,4}\s*$/u', $prefix) === 1;
        $negatedAfter = preg_match('/^\s*(?:nemam|nema|nisam|nije|ne|nisem|nimam|not|no|не|нямам|няма)(?:\s|$)/u', $suffix) === 1;
        $postposedCoreferenceNegation = preg_match(
            '/^\s*[,\-]?\s*(?:ali|but|ampak)\s+(?:ja\s+)?(?:nemam|nisam|nimam|i\s+do\s+not\s+have)(?:\s+(?:je|ga|to|tu\s+(?:bolest|dijagnozu|tegobu)|it))?(?:\s|[\.!?]|$)/u',
            substr($text, $offset + strlen($matchedText), 120)
        ) === 1;
        if(!$negatedBefore && !$negatedAfter && !$postposedCoreferenceNegation) {
            return true;
        }
    }

    return false;
}

/**
 * Returns the policy boundary for a requested outcome that would turn a
 * product recommendation into an unsupported health or body-function claim.
 * Negated mentions are ignored, so a user can explicitly move away from a
 * medical goal and ask a neutral label question instead.
 */
function fcc_ai_v2_claim_boundary_reason(string $message): string {
    $text = fcc_ai_v2_normalize_text($message);
    $patterns = [
        'health_claim_immunity' => '/(?<![\p{L}\p{N}])(?:imunitet|immunity|immune\s+system|jacan\p{L}*\s+(?:imunitet|otpornost)|prehlad\p{L}*|grip\p{L}*)(?![\p{L}\p{N}])/u',
        'health_claim_weight_loss' => '/(?<![\p{L}\p{N}])(?:mrsav\p{L}*|smrs\p{L}*|gubit\p{L}*\s+(?:kilogram\p{L}*|kil\p{L}*)|skin\p{L}*\s+(?:(?:visak|suvisn\p{L}*)\s+)?(?:kilogram\p{L}*|kil\p{L}*|kg)|rijes\p{L}*\s+se\s+(?:visk\p{L}*|suvisn\p{L}*)\s+(?:kilogram\p{L}*|kil\p{L}*)|smanj\p{L}*\s+(?:tjelesn\p{L}*\s+)?tezin\p{L}*|kontrol\p{L}*\s+(?:tjelesn\p{L}*\s+)?(?:mas\p{L}*|tezin\p{L}*)|sagor\p{L}*\s+mast\p{L}*|weight\s+loss|lose\s+weight|fat\s+burn\p{L}*|smanj\p{L}*\s+apetit\p{L}*)(?![\p{L}\p{N}])/u',
        'health_claim_hormonal' => '/(?<![\p{L}\p{N}])(?:hormon\p{L}*|libid\p{L}*|prostat\p{L}*|menopauz\p{L}*|testosteron\p{L}*|erekc\p{L}*)(?![\p{L}\p{N}])/u',
        'health_claim_circulation' => '/(?<![\p{L}\p{N}])(?:cirkulacij\p{L}*|krvn\p{L}*\s+tlak\p{L}*|(?:visok|povisen|nizak|snizen)\p{L}*\s+(?:krvn\p{L}*\s+)?tlak\p{L}*|hipertenzij\p{L}*|hipotenzij\p{L}*|krvn\p{L}*\s+zil\p{L}*|cardiovascular|(?:high|low)\s+blood\s+pressure|hypertension|hypotension|blood\s+pressure|circulation)(?![\p{L}\p{N}])/u',
        'health_claim_digestive' => '/(?<![\p{L}\p{N}])(?:probav\p{L}*|zatvor\p{L}*|nadut\p{L}*|osjetljiv\p{L}*\s+zeludac|constipation|bloating|digestive\s+problem\p{L}*)(?![\p{L}\p{N}])/u',
        'health_claim_sleep_stress' => '/(?<![\p{L}\p{N}])(?:nesanic\p{L}*|problem\p{L}*\s+(?:sa\s+)?spavan\p{L}*|za\s+san|(?:za|protiv)\s+stres\p{L}*|insomnia|sleep\s+problem\p{L}*)(?![\p{L}\p{N}])/u',
        'health_claim_pain_inflammation' => '/(?<![\p{L}\p{N}])(?:bol|boli|bole|boln\p{L}*|bolov\p{L}*|upala\p{L}*|upaljen\p{L}*|artrit\p{L}*|psorij\p{L}*|giht\p{L}*|pain|painful|inflamm\p{L}*)(?![\p{L}\p{N}])/u',
    ];

    foreach($patterns as $reason => $pattern) {
        if(fcc_ai_v2_has_affirmed_regex($text, $pattern)) {
            return $reason;
        }
    }

    return '';
}

function fcc_ai_v2_claim_boundary_clarifier(string $reason, string $language): string {
    $messages = [
        'health_claim_immunity' => [
            'hr' => 'Neću tvrditi da proizvod jača imunitet ili sprječava prehladu. Mogu usporediti proizvode samo prema točno deklariranom vitaminu C, vitaminu D, cinku ili drugom sastojku. Koji sastojak ili oblik želite usporediti?',
            'en' => 'I will not claim that a product boosts immunity or prevents colds. I can compare products only by their exactly declared vitamin C, vitamin D, zinc, or another ingredient. Which ingredient or format do you want to compare?',
            'sl' => 'Ne bom trdil, da izdelek krepi odpornost ali preprečuje prehlad. Izdelke lahko primerjam le po natančno navedenem vitaminu C, vitaminu D, cinku ali drugi sestavini. Katero sestavino ali obliko želite primerjati?',
            'bg' => 'Няма да твърдя, че продукт укрепва имунитета или предотвратява настинка. Мога да сравня продукти само по точно деклариран витамин C, витамин D, цинк или друга съставка. Коя съставка или форма искате да сравним?',
        ],
        'health_claim_weight_loss' => [
            'hr' => 'Za cilj smanjenja viška kilograma neću obećavati određeni rezultat, ali mogu vas odmah usmjeriti na relevantne Forever opcije. C9 je strukturirani devetodnevni program prehrane, hidratacije i laganog kretanja; F15 je petnaestodnevni smjer koji prehranu povezuje s vježbanjem; ako ne želite program, Forever Plant Protein je jednostavnija opcija isključivo za dodatni unos biljnih proteina, a ne proizvod koji sam po sebi uzrokuje mršavljenje. C9 i F15 uključuju više dodataka, pa prije točnog izbora trebam jednu sigurnosnu provjeru: jeste li punoljetni, jeste li trudni ili dojite, uzimate li lijekove ili imate dijagnosticirano odnosno kronično stanje?',
            'en' => 'For a weight-management goal I will not promise a specific result, but I can immediately narrow the relevant Forever paths. C9 is a structured nine-day nutrition, hydration and light-movement programme; F15 is a fifteen-day path combining nutrition and exercise; if you do not want a programme, Forever Plant Protein is the simpler option solely for additional plant-protein intake, not a product that causes weight loss by itself. C9 and F15 contain multiple supplements, so before choosing one I need one safety check: are you an adult, pregnant or breastfeeding, taking medication, or living with a diagnosed or chronic condition?',
            'sl' => 'Pri cilju uravnavanja telesne teže ne bom obljubljal določenega rezultata, lahko pa takoj zožim ustrezne možnosti Forever. C9 je strukturiran devetdnevni program prehrane, hidracije in lahkega gibanja; F15 je petnajstdnevna pot, ki povezuje prehrano in vadbo; če ne želite programa, je Forever Plant Protein preprostejša možnost izključno za dodaten vnos rastlinskih beljakovin, ne izdelek, ki sam povzroči hujšanje. C9 in F15 vključujeta več dodatkov, zato pred izbiro potrebujem eno varnostno preverjanje: ste polnoletni, noseči ali dojite, jemljete zdravila oziroma imate diagnosticirano ali kronično stanje?',
            'bg' => 'При цел за контрол на теглото няма да обещавам конкретен резултат, но мога веднага да стесня подходящите възможности на Forever. C9 е структуриран деветдневен режим за хранене, хидратация и леко движение; F15 е петнадесетдневен режим, който съчетава хранене и упражнения; ако не искате програма, Forever Plant Protein е по-прост вариант само за допълнителен прием на растителен протеин, а не продукт, който сам причинява отслабване. C9 и F15 включват няколко добавки, затова преди точен избор е нужна една проверка: пълнолетни ли сте, бременна ли сте или кърмите, приемате ли лекарства или имате диагностицирано/хронично състояние?',
        ],
        'health_claim_hormonal' => [
            'hr' => 'Neću vezati Forever proizvod uz hormonski, libido ili prostatični učinak. Mogu provjeriti samo točno deklarirani sastav i upozorenja proizvoda. Koji proizvod ili sastojak želite provjeriti?',
            'en' => 'I will not link a Forever product to a hormonal, libido, or prostate outcome. I can check only the product’s exactly declared composition and warnings. Which product or ingredient do you want to check?',
            'sl' => 'Izdelka Forever ne bom povezoval s hormonskim učinkom, libidom ali prostato. Preverim lahko le natančno deklarirano sestavo in opozorila. Kateri izdelek ali sestavino želite preveriti?',
            'bg' => 'Няма да свързвам продукт на Forever с хормонален ефект, либидо или простата. Мога да проверя само точно декларирания състав и предупреждения. Кой продукт или съставка искате да проверим?',
        ],
        'health_claim_circulation' => [
            'hr' => 'Neću tvrditi da proizvod poboljšava cirkulaciju, tlak ili krvne žile. Mogu neutralno usporediti deklarirano riblje ulje, koenzim Q10, L-arginin ili drugi sastojak. Koji sastojak ili proizvod vas zanima?',
            'en' => 'I will not claim that a product improves circulation, blood pressure, or blood vessels. I can neutrally compare declared fish oil, coenzyme Q10, L-arginine, or another ingredient. Which ingredient or product interests you?',
            'sl' => 'Ne bom trdil, da izdelek izboljšuje prekrvavitev, krvni tlak ali žile. Nevtralno lahko primerjam deklarirano ribje olje, koencim Q10, L-arginin ali drugo sestavino. Katera sestavina ali izdelek vas zanima?',
            'bg' => 'Няма да твърдя, че продукт подобрява кръвообращението, кръвното налягане или съдовете. Мога неутрално да сравня декларирано рибено масло, коензим Q10, L-аргинин или друга съставка. Коя съставка или продукт ви интересува?',
        ],
        'health_claim_digestive' => [
            'hr' => 'Neću obećati probavni učinak niti proizvod predstaviti kao liječenje tegoba. Mogu usporediti aloe napitke, deklarirana vlakna ili bakterijske kulture isključivo prema sastavu, načinu uporabe i upozorenjima. Koju od tih vrsta želite usporediti?',
            'en' => 'I will not promise a digestive outcome or present a product as treatment. I can compare aloe drinks, declared fibre, or bacterial cultures only by composition, directions, and warnings. Which of those types do you want to compare?',
            'sl' => 'Ne bom obljubljal prebavnega učinka ali izdelka predstavljal kot zdravljenje. Aloe napitke, deklarirane vlaknine ali bakterijske kulture lahko primerjam le po sestavi, uporabi in opozorilih. Katero vrsto želite primerjati?',
            'bg' => 'Няма да обещавам храносмилателен ефект или да представям продукт като лечение. Мога да сравня напитки с алое, декларирани фибри или бактериални култури само по състав, употреба и предупреждения. Кой вид искате да сравним?',
        ],
        'health_claim_sleep_stress' => [
            'hr' => 'Neću proizvod predstaviti kao liječenje nesanice ili stresa. Mogu provjeriti samo točno deklarirani sastav, kofein i upozorenja proizvoda. Koji proizvod ili sastojak želite provjeriti?',
            'en' => 'I will not present a product as treatment for insomnia or stress. I can check only its exactly declared composition, caffeine, and warnings. Which product or ingredient do you want to check?',
            'sl' => 'Izdelka ne bom predstavljal kot zdravljenje nespečnosti ali stresa. Preverim lahko le natančno deklarirano sestavo, kofein in opozorila. Kateri izdelek ali sestavino želite preveriti?',
            'bg' => 'Няма да представям продукт като лечение на безсъние или стрес. Мога да проверя само точно декларирания състав, кофеин и предупреждения. Кой продукт или съставка искате да проверим?',
        ],
    ];

    return isset($messages[$reason])
        ? fcc_ai_v2_localized_text($language, $messages[$reason])
        : '';
}

/**
 * A bare "da" or "ne" cannot answer the four-part safety screen. Advance the
 * weight-management flow only after adult status, pregnancy/breastfeeding,
 * medication and diagnosed/chronic conditions are all addressed explicitly.
 */
function fcc_ai_v2_weight_management_screen_complete(string $message): bool {
    $text = fcc_ai_v2_normalize_text($message);
    if($text === '') {
        return false;
    }

    $adultConfirmed = fcc_ai_v2_has_affirmed_regex(
        $text,
        '/(?<![\p{L}\p{N}])(?:punoljetn\p{L}*|odrasl\p{L}*|adult)(?![\p{L}\p{N}])/u'
    );
    if(!$adultConfirmed && preg_match_all('/(?<!\d)(\d{2})\s*(?:godin\p{L}*|years?\s+old)(?![\p{L}\p{N}])/u', $text, $ageMatches)) {
        foreach((array) ($ageMatches[1] ?? []) as $age) {
            if((int) $age >= 18) {
                $adultConfirmed = true;
                break;
            }
        }
    }

    $pregnancyCleared = fcc_ai_v2_contains_any($text, [
        'nisam trudna', 'nisam trudan', 'ne dojim', 'ne doji',
        'bez trudnoce', 'bez dojenja', 'trudnoca nije primjenjiva',
        'muskarac sam', 'musko sam', 'not pregnant', 'not breastfeeding',
    ]);
    $medicationCleared = preg_match(
        '/(?<![\p{L}\p{N}])(?:ne\s+(?:uzimam|pijem|koristim)|bez)\s+(?:nikakv\p{L}*\s+)?(?:lijek\p{L}*|terapij\p{L}*|medication)(?![\p{L}\p{N}])/u',
        $text
    ) === 1;
    $conditionCleared = preg_match(
        '/(?<![\p{L}\p{N}])(?:nemam|bez)\s+(?:nikakv\p{L}*\s+)?(?:dijagnoz\p{L}*|kronicn\p{L}*\s+(?:bolest\p{L}*|stanj\p{L}*)|bolest\p{L}*|medical\s+condition\p{L}*|chronic\s+condition\p{L}*)(?![\p{L}\p{N}])/u',
        $text
    ) === 1 || fcc_ai_v2_contains_any($text, [
        'zdrav sam', 'zdrava sam', 'zdravo sam',
        'no diagnosed condition', 'no chronic condition',
    ]);

    return $adultConfirmed && $pregnancyCleared && $medicationCleared && $conditionCleared;
}

/** @return ''|'c9'|'f15'|'plant_protein' */
function fcc_ai_v2_weight_management_preference(string $message): string {
    $text = fcc_ai_v2_normalize_text($message);
    if($text === '') {
        return '';
    }

    if($text === '1' || fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:c9|clean\s*9)(?![\p{L}\p{N}])/u')) {
        return 'c9';
    }
    if($text === '2' || fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])f\s*15(?![\p{L}\p{N}])/u')) {
        return 'f15';
    }
    if(
        $text === '3'
        || fcc_ai_v2_contains_any($text, [
            'bez programa', 'samo protein', 'biljni protein',
            'plant protein', 'proteinska opcija',
        ])
    ) {
        return 'plant_protein';
    }

    $shortProgramme = fcc_ai_v2_has_affirmed_regex(
        $text,
        '/(?<![\p{L}\p{N}])(?:krac\p{L}*|kratk\p{L}*|pocetn\p{L}*|devet\s+dan\p{L}*|9\s+dan\p{L}*)(?![\p{L}\p{N}])/u'
    );
    $trainingProgramme = fcc_ai_v2_has_affirmed_regex(
        $text,
        '/(?<![\p{L}\p{N}])(?:petnaest\s+dan\p{L}*|15\s+dan\p{L}*|trening\p{L}*|vjezb\p{L}*|fitness)(?![\p{L}\p{N}])/u'
    );
    if($shortProgramme xor $trainingProgramme) {
        return $shortProgramme ? 'c9' : 'f15';
    }

    return '';
}

/**
 * Resolve a weight-flow choice through catalog identity instead of assuming a
 * single deployment-specific product key. Production revisions use descriptive
 * keys while the deterministic replay catalog deliberately uses short keys.
 */
function fcc_ai_v2_weight_management_product_key(string $preference, array $rows): string {
    $preferredKeys = match($preference) {
        'c9' => ['c9-forever-living-products', 'c9'],
        'f15' => ['forever-f15', 'f15'],
        'plant_protein' => ['forever-plant-protein', 'plant_protein'],
        default => [],
    };
    foreach($preferredKeys as $productKey) {
        if(isset($rows[$productKey])) {
            return $productKey;
        }
    }

    $identityAliases = match($preference) {
        'c9' => ['c9', 'clean 9', 'c9 forever living products', 'forever c9'],
        'f15' => ['f15', 'forever f15'],
        'plant_protein' => ['plant protein', 'forever plant protein'],
        default => [],
    };
    $normalizedAliases = array_map('fcc_ai_v2_normalize_text', $identityAliases);
    foreach($rows as $productKey => $row) {
        $identities = array_merge(
            [(string) ($row['canonical_name'] ?? '')],
            (array) ($row['aliases'] ?? [])
        );
        foreach($identities as $identity) {
            if(in_array(fcc_ai_v2_normalize_text((string) $identity), $normalizedAliases, true)) {
                return (string) $productKey;
            }
        }
    }

    return '';
}

function fcc_ai_v2_weight_management_screen_question(string $language): string {
    return fcc_ai_v2_localized_text($language, [
        'hr' => 'Da ne nagađam sigurnost, odgovorite jednom rečenicom na sve četiri stavke: jeste li punoljetni; je li trudnoća ili dojenje primjenjivo; uzimate li lijekove; imate li dijagnosticirano ili kronično stanje?',
        'en' => 'To avoid guessing about safety, answer all four items in one sentence: are you an adult; are pregnancy or breastfeeding applicable; do you take medication; do you have a diagnosed or chronic condition?',
        'sl' => 'Da ne ugibam o varnosti, v enem stavku odgovorite na vse štiri točke: ste polnoletni; ali sta nosečnost ali dojenje aktualna; jemljete zdravila; imate diagnosticirano ali kronično stanje?',
        'bg' => 'За да не предполагам относно безопасността, отговорете с едно изречение на четирите точки: пълнолетни ли сте; приложими ли са бременност или кърмене; приемате ли лекарства; имате ли диагностицирано или хронично състояние?',
    ]);
}

function fcc_ai_v2_weight_management_format_question(string $language): string {
    return fcc_ai_v2_localized_text($language, [
        'hr' => 'Hvala, sigurnosni odgovor je jasan. Koji vam pristup više odgovara: 1) C9 kao kraći devetodnevni strukturirani početak, 2) F15 kao petnaestodnevni smjer s vježbanjem ili 3) bez programa — samo Forever Plant Protein za dodatni unos biljnih proteina?',
        'en' => 'Thank you, the safety answer is clear. Which approach fits you better: 1) C9 as a shorter structured nine-day start, 2) F15 as a fifteen-day path with exercise, or 3) no programme—only Forever Plant Protein for additional plant-protein intake?',
        'sl' => 'Hvala, varnostni odgovor je jasen. Kateri pristop vam bolj ustreza: 1) C9 kot krajši strukturirani devetdnevni začetek, 2) F15 kot petnajstdnevna pot z vadbo ali 3) brez programa—samo Forever Plant Protein za dodaten vnos rastlinskih beljakovin?',
        'bg' => 'Благодаря, отговорът за безопасност е ясен. Кой подход ви подхожда повече: 1) C9 като по-кратък структуриран деветдневен старт, 2) F15 като петнадесетдневна програма с упражнения или 3) без програма—само Forever Plant Protein за допълнителен прием на растителен протеин?',
    ]);
}

/**
 * Provider prose is untrusted. Detect policy claims per sentence while allowing
 * a genuine server-style negation such as "Neću tvrditi...". A later "ali/but"
 * starts a new affirmative scope and is checked normally.
 */
function fcc_ai_v2_provider_policy_violation(string $text, array $allowedNumericFacts = []): string {
    $normalizeNumericUnits = static function(string $value): string {
        $value = fcc_ai_v2_normalize_text($value);
        return (string) preg_replace(
            '/(?<![\p{L}\p{N}])(?:kapsul\p{L}*|capsules?)(?![\p{L}\p{N}])/u',
            'kapsul',
            (string) preg_replace(
                '/(?<![\p{L}\p{N}])(?:tablet\p{L}*|tablets?)(?![\p{L}\p{N}])/u',
                'tablet',
                (string) preg_replace(
                    '/(?<![\p{L}\p{N}])(?:mjeric\p{L}*|scoops?)(?![\p{L}\p{N}])/u',
                    'mjeric',
                    (string) preg_replace(
                        '/(?<![\p{L}\p{N}])(?:vrecic\p{L}*|sachets?)(?![\p{L}\p{N}])/u',
                        'vrecic',
                        $value
                    )
                )
            )
        );
    };
    $normalizedNumericFacts = array_values(array_filter(array_map(
        'fcc_ai_v2_normalize_text',
        fcc_ai_v2_unique_strings($allowedNumericFacts, 16)
    )));
    $normalizedNumericFacts = array_map($normalizeNumericUnits, $normalizedNumericFacts);
    $sentences = preg_split('/(?<=[\.!?])\s+|\R+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    foreach($sentences as $sentence) {
        $normalized = fcc_ai_v2_normalize_text((string) $sentence);
        if($normalized === '') {
            continue;
        }

        $negatedPolicyStatement = preg_match(
            '/^(?:necu|ne\s+cu|ne\s+tvrdim|ne\s+bom|i\s+will\s+not|we\s+will\s+not|do\s+not)\s+(?:tvrditi|tvrdim|claim|promise|obecati|obljubljati)\b/u',
            $normalized
        ) === 1;
        $hasContrast = preg_match('/\b(?:ali|but|however|ampak|vendar)\b/u', $normalized) === 1;
        if($negatedPolicyStatement && !$hasContrast) {
            continue;
        }

        $claimReason = fcc_ai_v2_claim_boundary_reason($normalized);
        if($claimReason !== '') {
            /* A topic word in a neutral question ("probava, energija ili
             * koza?") is not itself a product claim. Require an efficacy verb
             * or an explicit product-to-outcome construction. */
            $hasEfficacyVerb = preg_match(
                '/(?<![\p{L}\p{N}])(?:jac\p{L}*|poboljs\p{L}*|podrz\p{L}*|sniz\p{L}*|smanj\p{L}*|regul\p{L}*|potak\p{L}*|pomaz\p{L}*|djeluj\p{L}*|sagor\p{L}*|boost\p{L}*|support\p{L}*|improv\p{L}*|lower\p{L}*|reduc\p{L}*|burn\p{L}*|strengthen\p{L}*)(?![\p{L}\p{N}])/u',
                $normalized
            ) === 1;
            $hasProductOutcomeLink = preg_match(
                '/(?:(?:forever|proizvod\p{L}*|dodatak\p{L}*|napitak\p{L}*|product\p{L}*|supplement\p{L}*).{0,45}(?:je\s+)?(?:za|protiv|namijenjen\p{L}*|sluzi\p{L}*).{0,45}(?:imunitet|probav\p{L}*|mrsav\p{L}*|cirkulacij\p{L}*|krvn\p{L}*\s+tlak|hormon\p{L}*|libid\p{L}*|nesanic\p{L}*|stres\p{L}*|bol\p{L}*|upal\p{L}*))|(?:(?:imunitet|probav\p{L}*|mrsav\p{L}*|cirkulacij\p{L}*|krvn\p{L}*\s+tlak|hormon\p{L}*|libid\p{L}*|nesanic\p{L}*|stres\p{L}*|bol\p{L}*|upal\p{L}*).{0,45}(?:forever|proizvod\p{L}*|dodatak\p{L}*|napitak\p{L}*|product\p{L}*|supplement\p{L}*))/u',
                $normalized
            ) === 1;
            if($hasEfficacyVerb || $hasProductOutcomeLink) {
                return $claimReason;
            }
        }
        if(fcc_ai_v2_has_affirmed_regex(
            $normalized,
            '/(?<![\p{L}\p{N}])(?:lijec(?!ni[ck])(?:i|iti|enje|enjem|enja)\p{L}*|izlijec\p{L}*|rijes(?:iti|ava|avaju|enje)\p{L}*|sprijec\p{L}*|sprjec\p{L}*|jamc\p{L}*|garantir\p{L}*|cures?|treats?|prevents?|guarantees?)(?![\p{L}\p{N}])/u'
        )) {
            return 'unapproved_outcome_claim';
        }
        if(preg_match(
            '/(?<![\p{L}\p{N}])(?:prestan\p{L}*|prekin\p{L}*|obustav\p{L}*|zamijen\p{L}*|stop|discontinu\p{L}*|replace|preneh\p{L}*|zamenj\p{L}*|спр\p{L}*|прекрат\p{L}*|замен\p{L}*).{0,45}(?:propisan\p{L}*\s+)?(?:lijek\p{L}*|terapij\p{L}*|medication|medicine|treatment|zdravil\p{L}*|zdravljen\p{L}*|лекарств\p{L}*|терап\p{L}*)(?![\p{L}\p{N}])/u',
            $normalized
        ) === 1) {
            return 'medical_treatment_override';
        }
        if(preg_match_all(
            '/(?<![\p{L}\p{N}])\d+(?:[\.,]\d+)?\s*(?:mg|g|ml|kapsul\p{L}*|tablet\p{L}*|mjeric\p{L}*|vrecic\p{L}*|capsules?|tablets?|scoops?|sachets?)(?![\p{L}\p{N}])/u',
            $normalized,
            $numericMatches
        ) > 0) {
            foreach((array) ($numericMatches[0] ?? []) as $numericMatch) {
                $numericMatch = $normalizeNumericUnits((string) $numericMatch);
                $grounded = false;
                foreach($normalizedNumericFacts as $numericFact) {
                    if(str_contains($numericFact, $numericMatch)) {
                        $grounded = true;
                        break;
                    }
                }
                if(!$grounded) {
                    return 'unapproved_provider_dosage';
                }
            }
        }
    }

    return '';
}

function fcc_ai_v2_medical_context_flags(string $message, array $previous, array $safety): array {
    $text = fcc_ai_v2_normalize_text($message);
    $flags = array_fill_keys(fcc_ai_v2_unique_strings($previous, 12), true);

    if(preg_match('/(?<![\p{L}\p{N}])(?:nisam\s+trudna|ne\s+dojim|not\s+pregnant|not\s+breastfeeding)(?![\p{L}\p{N}])/u', $text) === 1) {
        unset($flags['pregnancy_or_breastfeeding']);
    }
    if(preg_match('/(?<![\p{L}\p{N}])(?:ne\s+uzimam|nisam\s+na|not\s+taking|no\s+longer\s+taking).{0,35}(?:terapij\p{L}*|lijek\p{L}*|medication\p{L}*)(?![\p{L}\p{N}])/u', $text) === 1) {
        unset($flags['medication_or_therapy']);
    }
    if(preg_match('/(?<![\p{L}\p{N}])(?:nemam\s+rak|nisam\s+na\s+kemoterapij\p{L}*|do\s+not\s+have\s+cancer|not\s+on\s+chemotherapy)(?![\p{L}\p{N}])/u', $text) === 1) {
        unset($flags['oncology']);
    }
    if(fcc_ai_v2_contains_any($text, ['nije za dijete', 'za odraslu osobu', 'not for a child', 'for an adult'])) {
        unset($flags['minor']);
    }

    $reason = (string) ($safety['reason_codes'][0] ?? '');
    if($reason === 'oncology_medication') {
        $flags['oncology'] = true;
        $flags['medication_or_therapy'] = true;
    } elseif($reason === 'pregnancy_medication') {
        $flags['pregnancy_or_breastfeeding'] = true;
        $flags['medication_or_therapy'] = true;
    } elseif(in_array($reason, ['child_medication', 'child_product_review'], true)) {
        $flags['minor'] = true;
        if($reason === 'child_medication') {
            $flags['medication_or_therapy'] = true;
        }
    } elseif(in_array($reason, ['sedative_interaction', 'medication_compatibility'], true)) {
        $flags['medication_or_therapy'] = true;
    } elseif($reason === 'medical_or_special_population') {
        if(fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:trudn|pregnan|dojim|breastfeed)\p{L}*(?![\p{L}\p{N}])/u')) {
            $flags['pregnancy_or_breastfeeding'] = true;
        }
        if(fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:terapij|lijek|medication)\p{L}*(?![\p{L}\p{N}])/u')) {
            $flags['medication_or_therapy'] = true;
        }
        if(!$flags) {
            $flags['special_population'] = true;
        }
    }

    $result = array_keys($flags);
    sort($result, SORT_STRING);
    return array_values($result);
}

function fcc_ai_v2_is_negated_mention(string $message, string $alias): bool {
    $normalized = fcc_ai_v2_normalize_clause_text($message);
    $alias_normalized = fcc_ai_v2_normalize_text($alias);
    /* Keep mention detection and negation scope on the same separator model.
     * Replacing a one-byte hyphen with one space also preserves offsets. */
    $normalized = preg_replace('/(?<=\p{L})-(?=\p{L})/u', ' ', $normalized) ?? $normalized;
    $alias_normalized = preg_replace('/(?<=\p{L})-(?=\p{L})/u', ' ', $alias_normalized) ?? $alias_normalized;
    if($normalized === '' || $alias_normalized === '') {
        return false;
    }

    $position = mb_strpos($normalized, $alias_normalized, 0, 'UTF-8');
    if($position === false) {
        return false;
    }

    $prefix = mb_substr($normalized, max(0, $position - 45), min(45, $position), 'UTF-8');
    $prefix = preg_replace('/.*(?:[\.,;!?]|\bali\b|\bnego\b|\bbut\b|\binstead\b|\brather\s+than\b|\bampak\b)/u', '', $prefix) ?? $prefix;

    $suffixStart = $position + mb_strlen($alias_normalized, 'UTF-8');
    $suffix = mb_substr($normalized, $suffixStart, 45, 'UTF-8');
    $suffix = preg_replace('/(?:[\.,;!?]).*/u', '', $suffix) ?? $suffix;

    /* A leading rejection applies to every item in a comma-separated list
     * until a hard contrast or an explicit positive restart appears. This
     * keeps "Ne želim C9, F15 ni Therm" negative without breaking
     * "Ne želim Daily, želim Nature-Min". */
    $clausePrefix = mb_substr($normalized, 0, $position, 'UTF-8');
    $clausePrefix = preg_replace(
        '/.*(?:[\.;!?]|\bali\b|\bnego\b|\bbut\b|\binstead\b|\brather\s+than\b|\bampak\b)\s*/u',
        '',
        $clausePrefix
    ) ?? $clausePrefix;
    $hasLeadingListRejection = preg_match(
        '/(?:^|\s)(?:ne\s+zelim|necu|nemoj|nikako|bez|do\s+not\s+want|don\'t\s+want|not|without)(?:\s|$)/u',
        $clausePrefix
    ) === 1;
    $hasPositiveRestart = preg_match(
        '/,\s*(?:zelim|hocu|trazim|biram|preferiram|want|need|choose|prefer)(?:\s|$)/u',
        $clausePrefix
    ) === 1;

    if($hasLeadingListRejection && !$hasPositiveRestart) {
        return true;
    }

    if(preg_match('/^(?:\s+\p{L}+){0,3}\s*(?:vise\s+)?(?:necu|ne zelim|nemoj|nikako|not again|dont want|do not want)(?:\s+\p{L}+){0,4}/u', $suffix) === 1) {
        return true;
    }

    return preg_match('/(?:^|\s)(?:ne|necu|nemoj|nikako|bez|nije|nisam|not|no|without|don\'t|do not|nocem|ne zelim)(?:\s+\p{L}+){0,4}\s*$/u', $prefix) === 1;
}

function fcc_ai_v2_is_ambiguous_nutrient_alias(string $normalizedAlias): bool {
    return in_array($normalizedAlias, [
        'vitamin c', 'vitamin d', 'vitamin d3', 'vitamin b12', 'b12',
        'kalcij', 'calcium', 'omega 3', 'omega-3',
    ], true);
}

function fcc_ai_v2_product_mentions(string $message, array $catalog): array {
    $rows = fcc_ai_v2_catalog_rows($catalog);
    $candidates = [];

    foreach($rows as $product_key => $row) {
        foreach($row['aliases'] as $alias) {
            $normalized_alias = fcc_ai_v2_normalize_text((string) $alias);
            if($normalized_alias === '' || !fcc_ai_v2_contains_alias($message, $normalized_alias)) {
                continue;
            }

            /* Nutrient names are intents, not unique product identities. For
             * example, bare "vitamin C" must go through the oral/topical
             * entity and route gates instead of becoming a direct mention of
             * the lookup-only facial product Forever Vitamin C. A Forever
             * prefix still identifies the branded product explicitly. */
            if(fcc_ai_v2_is_ambiguous_nutrient_alias($normalized_alias)) {
                $normalizedMessage = fcc_ai_v2_normalize_text($message);
                if(!fcc_ai_v2_contains_phrase($normalizedMessage, 'forever ' . $normalized_alias)) {
                    continue;
                }
            }

            if(
                $product_key === 'marine-collagen'
                && in_array($normalized_alias, ['kolagen', 'riblji kolagen', 'morski kolagen', 'marine collagen', 'tekuci kolagen'], true)
            ) {
                $normalizedMessage = fcc_ai_v2_normalize_text($message);
                if(
                    !fcc_ai_v2_contains_phrase($normalizedMessage, 'forever marine collagen')
                    && preg_match('/(?<![\p{L}\p{N}])613(?![\p{L}\p{N}])/u', $normalizedMessage) !== 1
                ) {
                    /* Generic collagen wording describes an entity and form,
                     * not the identity of the liquid Forever product. */
                    continue;
                }
            }

            $alias_tokens = preg_split('/\s+/u', $normalized_alias, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $generic_single = count($alias_tokens) === 1
                && (mb_strlen($normalized_alias) < 5 || in_array($normalized_alias, ['aloe', 'daily', 'first', 'gel', 'kids'], true))
                && preg_match('/^\p{L}+$/u', $normalized_alias) === 1;
            if($generic_single) {
                $normalized_message = fcc_ai_v2_normalize_text($message);
                $is_exact = $normalized_message === $normalized_alias || $normalized_message === 'forever ' . $normalized_alias;
                $has_forever_prefix = fcc_ai_v2_contains_phrase($normalized_message, 'forever ' . $normalized_alias);
                if(!$is_exact && !$has_forever_prefix) {
                    continue;
                }
            }

            /* Negation is resolved around each alias. A sentence-level flag
             * incorrectly rejected every later product in contrasts such as
             * "ne Daily, nego Nature-Min". */
            $listNegation = false;
            $explicitMaskRejection = $product_key === 'forever-bio-cellulose-mask'
                && fcc_ai_v2_contains_any($message, ['ne samu masku', 'ne zelim samu masku', 'not the mask itself']);
            $candidates[] = [
                'product_key' => $product_key,
                'alias' => (string) $alias,
                'length' => mb_strlen($normalized_alias),
                'negated' => $listNegation || $explicitMaskRejection || fcc_ai_v2_is_negated_mention($message, (string) $alias),
            ];
        }
    }

    usort($candidates, static function(array $left, array $right): int {
        return ($right['length'] <=> $left['length']) ?: strcmp($left['product_key'], $right['product_key']);
    });

    $result = [];
    foreach($candidates as $candidate) {
        if(isset($result[$candidate['product_key']])) {
            continue;
        }

        $result[$candidate['product_key']] = [
            'product_key' => $candidate['product_key'],
            'alias' => $candidate['alias'],
            'negated' => $candidate['negated'],
        ];
    }

    return array_values($result);
}

/**
 * A diagnosis keyword is not automatically the user's diagnosis.  Keep
 * medically relevant third-person requests on the care path, but ignore a
 * relative's/friend's diagnosis when the user explicitly moves to an
 * unrelated goal of their own.  This also catches Croatian postposed
 * negations such as "psorijazu ... nemam je" which a prefix-only matcher
 * cannot understand.
 */
function fcc_ai_v2_has_user_relevant_medical_regex(string $message, string $pattern): bool {
    $text = fcc_ai_v2_normalize_text($message);
    if($text === '' || preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE) < 1) {
        return false;
    }

    foreach((array) ($matches[0] ?? []) as $match) {
        $matchedText = (string) ($match[0] ?? '');
        $offset = (int) ($match[1] ?? 0);
        $prefix = substr($text, max(0, $offset - 120), min(120, $offset));
        $clausePrefix = preg_replace('/.*(?:[\.!?;]|\bali\b|\bbut\b|\bampak\b)\s*/u', '', $prefix) ?? $prefix;
        $suffix = substr($text, $offset + strlen($matchedText), 120);
        $shortSuffix = preg_replace('/(?:[\.!?;]).*/u', '', $suffix) ?? $suffix;

        $negatedBefore = preg_match(
            '/(?:^|\s)(?:ne|nemam|nema|nisam|nije|nikad|brez|nisem|nimam|not|no|without|don\'t|do\s+not|am\s+not|не|нямам|няма|не\s+съм|без)(?:\s+\p{L}+){0,4}\s*$/u',
            $clausePrefix
        ) === 1;
        $negatedAfter = preg_match(
            '/^\s*(?:nemam|nema|nisam|nije|ne|nisem|nimam|not|no|не|нямам|няма)(?:\s|$)/u',
            $shortSuffix
        ) === 1;
        $postposedCoreferenceNegation = preg_match(
            '/^\s*[,\-]?\s*(?:ali|but|ampak)\s+(?:ja\s+)?(?:nemam|nisam|nimam|i\s+do\s+not\s+have)(?:\s+(?:je|ga|to|tu\s+(?:bolest|dijagnozu|tegobu)|it))?(?:\s|[\.!?]|$)/u',
            $suffix
        ) === 1;
        if($negatedBefore || $negatedAfter || $postposedCoreferenceNegation) {
            continue;
        }

        $thirdPersonClause = preg_match(
            '/(?:^|\s)(?:(?:moj|moja|moje|my)\s+)?(?:majk\p{L}*|mam\p{L}*|otac|oca|tat\p{L}*|sestr\p{L}*|brat\p{L}*|suprug\p{L}*|partner\p{L}*|dijet\p{L}*|det\p{L}*|sin|kcer\p{L}*|bak\p{L}*|djed\p{L}*|prijatelj\p{L}*|kolega|kolegic\p{L}*|mother|father|sister|brother|spouse|partner|child|son|daughter|friend|colleague|on|ona|he|she)\s+(?:ima|boluj\p{L}*|zivi\s+s|dijagnosticiran\p{L}*|dijagnosticirana\p{L}*|has|suffers?\s+from|lives?\s+with|was\s+diagnosed\s+with)(?:\s+\p{L}+){0,4}\s*$/u',
            $clausePrefix
        ) === 1;
        if(!$thirdPersonClause) {
            return true;
        }

        $explicitRelativeRequest = preg_match(
            '/(?:(?:za|for)\s+(?:nju|njega|njih|mamu|majku|oca|tatu|dijete|prijatelja|prijateljicu|her|him|them)|(?:njoj|njemu)\b)/u',
            $suffix
        ) === 1;
        $explicitSelfShift = preg_match(
            '/(?:^|[\.!?;]|\bali\b|\bbut\b)\s*(?:a\s+)?(?:ja|meni|za\s+sebe|i)\s+(?:sada\s+|sad\s+|now\s+)?(?:trazim|trebam|zelim|pitam|me\s+zanima|want|need|am\s+looking\s+for|am\s+asking)(?![\p{L}\p{N}])/u',
            $suffix
        ) === 1;
        $ambiguousProductRequest = preg_match(
            '/(?<![\p{L}\p{N}])(?:koji|kakav|preporuc\p{L}*|proizvod\p{L}*|dodatak\p{L}*|napitak\p{L}*|krema\p{L}*|what|which|recommend\p{L}*|product\p{L}*|supplement\p{L}*)(?![\p{L}\p{N}])/u',
            $suffix
        ) === 1;
        if($explicitRelativeRequest || ($ambiguousProductRequest && !$explicitSelfShift)) {
            return true;
        }
    }

    return false;
}

/**
 * Recognize medical context without turning a diagnosis or active infection
 * into a recommendation goal. These keys are used only for deterministic care
 * routing and must never be passed to candidate ranking as product goals.
 *
 * @return array{key:string,reason_code:string,concern_class:string}
 */
function fcc_ai_v2_medical_need_profile(string $message): array {
    $text = fcc_ai_v2_normalize_text($message);

    if(fcc_ai_v2_has_user_relevant_medical_regex(
        $text,
        '/(?<![\p{L}\p{N}])(?:masn\p{L}*\s+jetr\p{L}*|masnic\p{L}*\s+jetr\p{L}*|masnoc\p{L}*\s+(?:na|u)\s+jetr\p{L}*|odmast\p{L}*\s+jetr\p{L}*|skin\p{L}*\s+masnoc\p{L}*\s+(?:s|sa|iz)\s+jetr\p{L}*|steatoz\p{L}*\s+jetr\p{L}*|hepatic\s+steatosis|fatty\s+liver)(?![\p{L}\p{N}])/u'
    )) {
        return [
            'key' => 'fatty_liver',
            'reason_code' => 'diagnosed_fatty_liver',
            'concern_class' => 'diagnosis',
        ];
    }

    if(fcc_ai_v2_has_user_relevant_medical_regex(
        $text,
        '/(?<![\p{L}\p{N}])(?:(?:gljivic|gljivica|gljivice|mikoza)\p{L}*(?:\s+na)?\s+(?:stopal|nog|prst|nokt)\p{L}*|tinea\s+pedis|athlete(?:\'s)?\s+foot|fungal\s+(?:foot|nail)\s+infection)(?![\p{L}\p{N}])/u'
    )) {
        return [
            'key' => 'foot_fungal_infection',
            'reason_code' => 'suspected_foot_fungal_infection',
            'concern_class' => 'active_infection',
        ];
    }

    if(fcc_ai_v2_has_user_relevant_medical_regex(
        $text,
        '/(?<![\p{L}\p{N}])(?:(?:visok|povisen)\p{L}*\s+(?:krvn\p{L}*\s+)?tlak\p{L}*|hipertenzij\p{L}*|high\s+blood\s+pressure|hypertension)(?![\p{L}\p{N}])/u'
    )) {
        return [
            'key' => 'high_blood_pressure',
            'reason_code' => 'diagnosed_hypertension',
            'concern_class' => 'diagnosis',
        ];
    }

    $diagnosisProfiles = [
        'diagnosed_metabolic_condition' => [
            'key' => 'metabolic_condition',
            'pattern' => '/(?<![\p{L}\p{N}])(?:dijabet\p{L}*|secerna\s+bolest|predijabet\p{L}*|inzulinsk\p{L}*\s+rezistenc\p{L}*|(?:visok|povisen)\p{L}*\s+(?:kolesterol\p{L}*|triglicerid\p{L}*)|masnoc\p{L}*\s+u\s+krvi|hiperkolesterol\p{L}*|diabetes|prediabetes|insulin\s+resistance|high\s+cholesterol)(?![\p{L}\p{N}])/u',
        ],
        'diagnosed_skin_condition' => [
            'key' => 'skin_condition',
            'pattern' => '/(?<![\p{L}\p{N}])(?:ekcem\p{L}*|dermatit\p{L}*|psorijaz\p{L}*|akne|aknama|akni|rozacej\p{L}*|eczema|dermatitis|psoriasis|acne|rosacea)(?![\p{L}\p{N}])/u',
        ],
        'diagnosed_cardiovascular_condition' => [
            'key' => 'cardiovascular_condition',
            'pattern' => '/(?<![\p{L}\p{N}])(?:srcan\p{L}*\s+(?:bolest|slabost|zatajenj\p{L}*)|bolest\s+srca|zatajenj\p{L}*\s+srca|aritmij\p{L}*|fibrilacij\p{L}*\s+atrij\p{L}*|koronarn\p{L}*\s+bolest|prebolj\p{L}*\s+infarkt|heart\s+(?:disease|failure)|arrhythmia|atrial\s+fibrillation|coronary\s+disease)(?![\p{L}\p{N}])/u',
        ],
        'diagnosed_kidney_condition' => [
            'key' => 'kidney_condition',
            'pattern' => '/(?<![\p{L}\p{N}])(?:kronicn\p{L}*\s+bolest\s+bubreg\p{L}*|bolest\s+bubreg\p{L}*|bubrezn\p{L}*\s+(?:bolest|insuficijenc\p{L}*|zatajenj\p{L}*)|bubrez\p{L}*\s+mi\s+(?:slabo|lose)\s+rad\p{L}*|renal\s+(?:disease|failure)|chronic\s+kidney\s+disease)(?![\p{L}\p{N}])/u',
        ],
        'diagnosed_liver_condition' => [
            'key' => 'liver_condition',
            'pattern' => '/(?<![\p{L}\p{N}])(?:ciroz\p{L}*|hepatit\p{L}*|bolest\s+jetr\p{L}*|bolesn\p{L}*\s+jetr\p{L}*|zatajenj\p{L}*\s+jetr\p{L}*|cirrhosis|hepatitis|liver\s+(?:disease|failure))(?![\p{L}\p{N}])/u',
        ],
        'diagnosed_endocrine_condition' => [
            'key' => 'endocrine_condition',
            'pattern' => '/(?<![\p{L}\p{N}])(?:hashimot\p{L}*|hipotireoz\p{L}*|hipertireoz\p{L}*|bolest\s+stitnjac\p{L}*|stitnjac\p{L}*\s+mi\s+ne\s+rad\p{L}*\s+dobro|policisticn\p{L}*\s+jajnic\p{L}*|pcos|hypothyroid\p{L}*|hyperthyroid\p{L}*|thyroid\s+disease)(?![\p{L}\p{N}])/u',
        ],
        'diagnosed_digestive_condition' => [
            'key' => 'digestive_condition',
            'pattern' => '/(?<![\p{L}\p{N}])(?:crohn\p{L}*|ulcerozn\p{L}*\s+kolit\p{L}*|celijakij\p{L}*|gastrit\p{L}*|(?:cir\p{L}*|ulkus)\s+(?:na\s+)?zeluc\p{L}*|refluks\p{L}*|gerb|irritabiln\p{L}*\s+crijev\p{L}*|ibs|crohn|ulcerative\s+colitis|coeliac|celiac|gastritis|reflux)(?![\p{L}\p{N}])/u',
        ],
        'diagnosed_respiratory_condition' => [
            'key' => 'respiratory_condition',
            'pattern' => '/(?<![\p{L}\p{N}])(?:astm\p{L}*|kronicn\p{L}*\s+(?:opstruktivn\p{L}*\s+)?(?:bolest\s+)?pluc\p{L}*|kopb|copd|asthma)(?![\p{L}\p{N}])/u',
        ],
        'musculoskeletal_symptoms' => [
            'key' => 'musculoskeletal_condition',
            'pattern' => '/(?<![\p{L}\p{N}])(?:(?:bole|boli)\s+(?:me\s+)?zglob\p{L}*|bol\p{L}*\s+u\s+zglob\p{L}*|artrit\p{L}*|artroz\p{L}*|giht\p{L}*|joint\s+pain|arthritis|osteoarthritis|gout)(?![\p{L}\p{N}])/u',
        ],
        'active_or_suspected_infection' => [
            'key' => 'active_infection',
            'pattern' => '/(?<![\p{L}\p{N}])(?:imam\s+(?:infekcij\p{L}*|kandid\p{L}*|gljivic\p{L}*)|aktivn\p{L}*\s+infekcij\p{L}*|bakterijsk\p{L}*\s+infekcij\p{L}*|vaginaln\p{L}*\s+gljivic\p{L}*|urinarna\s+infekcij\p{L}*|active\s+infection|bacterial\s+infection|yeast\s+infection)(?![\p{L}\p{N}])/u',
        ],
    ];
    foreach($diagnosisProfiles as $reasonCode => $profile) {
        if(fcc_ai_v2_has_user_relevant_medical_regex($text, (string) $profile['pattern'])) {
            return [
                'key' => (string) $profile['key'],
                'reason_code' => $reasonCode,
                'concern_class' => str_contains($reasonCode, 'infection') ? 'active_infection' : 'diagnosis',
            ];
        }
    }

    return ['key' => '', 'reason_code' => '', 'concern_class' => ''];
}

function fcc_ai_v2_safety_assessment(string $message): array {
    $text = fcc_ai_v2_normalize_text($message);
    $result = static fn(string $level, bool $allowProducts, string $reason): array => [
        'level' => $level,
        'allow_products' => $allowProducts,
        'reason_codes' => [$reason],
    ];

    $breathing = fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:(?:tesko|otezano)\s+dis(?:em|anje)|gusim\s+se|ne\s+mogu\s+disati|tezko\s+diham|ne\s+morem\s+dihati|cannot\s+breathe|shortness\s+of\s+breath|не\s+мога\s+да\s+дишам)(?![\p{L}\p{N}])/u');
    $chestPain = fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:(?:bol|pritisak|stezanje)\s+u\s+prsima|boli(?:\s+me)?\s+u\s+prsima|osjecam\s+(?:bol|pritisak|stezanje)\s+u\s+prsima|bolecin\p{L}*\s+v\s+prsih|chest\s+pain|болка\s+в\s+гърдите)(?![\p{L}\p{N}])/u');
    $swelling = fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:(?:otekle?|natekle?)\s+(?:su\s+mi\s+)?(?:usne|jezik|grlo)|otok\s+(?:usana|jezika|grla)|otekle\s+ustnice|подути\s+(?:устни|език|гърло))(?![\p{L}\p{N}])/u');
    $rash = fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:osip|koprivnjac|hives|izpuscaj|обрив)(?![\p{L}\p{N}])/u');

    if($breathing && ($swelling || $rash || fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:anafilaks|anaphylax|анафилакс)\p{L}*(?![\p{L}\p{N}])/u'))) {
        return $result('emergency', false, 'anaphylaxis');
    }
    if($breathing && $chestPain) {
        return $result('emergency', false, 'cardiorespiratory_emergency');
    }
    if(
        fcc_ai_v2_contains_any($text, ['znakovi mozdanog udara', 'oduzela se', 'spusteno lice', 'bez svijesti', 'unconscious'])
        || fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:suicid|samouboj|samomor|ubiti\s+se|ubijem\s+se|zelim\s+se\s+ubiti|kill\s+myself|commit\s+suicide|suicidal|самоуби)\p{L}*(?![\p{L}\p{N}])/u')
    ) {
        return $result('emergency', false, 'life_threatening_symptoms');
    }

    if(preg_match('/\b(?:tlak|pritisak|pressure)\D{0,12}(\d{2,3})\s*[\/\-]\s*(\d{2,3})\b/u', $text, $matches)) {
        $systolic = (int) ($matches[1] ?? 0);
        $diastolic = (int) ($matches[2] ?? 0);
        if($systolic >= 180 || $diastolic >= 120) {
            $organDamageSymptoms = $chestPain
                || $breathing
                || fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:bol\s+u\s+ledima|utrnul\p{L}*|slabost|promjen\p{L}*\s+vida|zamagljen\p{L}*\s+vid|tesko\s+govor\p{L}*|back\s+pain|numbness|weakness|vision\s+change|difficulty\s+speaking)(?![\p{L}\p{N}])/u');
            return $organDamageSymptoms
                ? $result('emergency', false, 'hypertensive_emergency')
                : $result('urgent', false, 'severe_hypertension');
        }
        if($systolic >= 140 || $diastolic >= 90) {
            return $result('sensitive', false, 'high_blood_pressure_reading');
        }
    }

    if($chestPain) {
        return $result('urgent', false, 'chest_pain_assessment');
    }

    $edema = fcc_ai_v2_has_affirmed_regex($text, '/\b(?:otic|otek|natec|edem)\p{L}*\b/u')
        && fcc_ai_v2_contains_any($text, ['noge', 'noga', 'legs']);
    $urinary = fcc_ai_v2_has_affirmed_regex($text, '/\b(?:(?:tesko|otezano)\s+mokr\p{L}*|ne\s+mogu\s+mokr\p{L}*|vrlo\s+malo\s+mokr\p{L}*|difficulty\s+urinating)\b/u');
    if($edema && $urinary) {
        return $result('urgent', false, 'urinary_obstruction');
    }

    $crpHigh = preg_match('/\bcrp\D{0,8}(\d{2,3})\b/u', $text, $crpMatch) === 1 && (int) ($crpMatch[1] ?? 0) >= 40;
    $infection = fcc_ai_v2_has_affirmed_regex($text, '/\b(?:infekcij|infection)\p{L}*\b/u');
    $antibiotic = fcc_ai_v2_has_affirmed_regex($text, '/\b(?:antibiotik|antibiotic)\p{L}*\b/u');
    if($crpHigh && $infection && $antibiotic) {
        return $result('urgent', false, 'infection_on_antibiotic');
    }

    $vascularCluster = preg_match('/\b(?:vena|zila)\b/u', $text) === 1
        && preg_match('/\b(?:tvrd|crven|boln|flebit|ugrus)\p{L}*\b/u', $text) === 1;
    if($vascularCluster) {
        return $result('urgent', false, 'suspected_thrombosis');
    }

    if(fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:krv\s+u\s+stolici|kri\s+v\s+blatu|povrac\p{L}*\s+krv|black\s+stool|blood\s+in\s+stool|кръв\s+в\s+изпражненията)(?![\p{L}\p{N}])/u')) {
        return $result('urgent', false, 'gi_bleeding');
    }
    if(fcc_ai_v2_has_affirmed_regex($text, '/\bgranulom\p{L}*\b/u')) {
        return $result('urgent', false, 'nail_granuloma');
    }

    if(fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:(?:kemoterap|chemotherap|letrozol|onkolog|cancer|химиотерап|онколог)\p{L}*|rak\p{L}*|рак)(?![\p{L}\p{N}])/u')) {
        return $result('medication_review', false, 'oncology_medication');
    }
    $pregnancy = fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:trudn|pregnan|nosec|dojim|dojenje|breastfeed|бремен)\p{L}*(?![\p{L}\p{N}])/u');
    $therapy = fcc_ai_v2_has_affirmed_regex($text, '/(?<![\p{L}\p{N}])(?:terapij|lijek|lek|medication|zdravil|лекарств|терап)\p{L}*(?![\p{L}\p{N}])/u');
    if($pregnancy && $therapy) {
        return $result('medication_review', false, 'pregnancy_medication');
    }
    $child = fcc_ai_v2_has_affirmed_regex(
        $text,
        '/(?<![\p{L}\p{N}])(?:(?:za|moje|mom|svom)\s+(?:dijete|dete)|(?:dijete|dete|child)\b.{0,24}\b\d{1,2}\s*(?:godina|godine|years?)\b|for\s+(?:my\s+)?child)(?![\p{L}\p{N}])/u'
    );
    if($child && ($therapy || preg_match('/\balerg\p{L}*\b/u', $text) === 1)) {
        return $result('medication_review', false, 'child_medication');
    }
    if($child) {
        return $result('medication_review', false, 'child_product_review');
    }
    if(preg_match('/\bterapij\p{L}*\s+za\s+spavanje\b/u', $text) === 1 && fcc_ai_v2_contains_phrase($text, 'melatonin')) {
        return $result('medication_review', false, 'sedative_interaction');
    }
    if(fcc_ai_v2_contains_any($text, ['aspirin', 'tamsal', 'tamsulosin']) || ($therapy && fcc_ai_v2_contains_any($text, ['smijem li', 'smem li', 'can i take']))) {
        return $result('medication_review', false, 'medication_compatibility');
    }

    if(preg_match('/\bneplodn\p{L}*|infertil\p{L}*|zacec\p{L}*\b/u', $text) === 1) {
        return $result('sensitive', false, 'fertility_evaluation');
    }
    if(preg_match('/\bupaljen\p{L}*\s+tetiv\p{L}*|upala\s+tetiv\p{L}*\b/u', $text) === 1) {
        return $result('sensitive', false, 'acute_tendon_injury');
    }
    if(preg_match('/\bsezonsk\p{L}*\s+alerg\p{L}*\b/u', $text) === 1) {
        return $result('sensitive', false, 'allergy_education');
    }
    if($pregnancy || $therapy || preg_match('/\b(?:dijaliz|transplant|autoimun|seps|zatajenje\s+bubrega)\p{L}*\b/u', $text) === 1) {
        return $result('medication_review', false, 'medical_or_special_population');
    }

    $medicalNeed = fcc_ai_v2_medical_need_profile($text);
    if((string) ($medicalNeed['reason_code'] ?? '') !== '') {
        return $result('sensitive', false, (string) $medicalNeed['reason_code']);
    }

    $claimBoundaryReason = fcc_ai_v2_claim_boundary_reason($text);
    if($claimBoundaryReason !== '') {
        return $result('sensitive', false, $claimBoundaryReason);
    }

    return ['level' => 'routine', 'allow_products' => true, 'reason_codes' => []];
}

/**
 * Detect a genuine business/collaboration request independently of product
 * matching. The result is intentionally descriptive only: compensation,
 * eligibility and contractual terms are never inferred from catalog data.
 *
 * @return array{matched:bool,topic:string,reason_code:string}
 */
function fcc_ai_v2_business_intent_profile(string $message, array $state = []): array {
    $text = fcc_ai_v2_normalize_text($message);
    $empty = ['matched' => false, 'topic' => '', 'reason_code' => ''];
    if($text === '') {
        return $empty;
    }

    $shortText = trim($text, " .");
    $specificBusinessTopic = [];
    $mentionsFourCc = fcc_ai_v2_has_affirmed_regex(
        $shortText,
        '/(?<![\p{L}\p{N}])(?:4\s*cc|cetiri\s+cc)(?![\p{L}\p{N}])/u'
    );
    $mentionsTwoCc = fcc_ai_v2_has_affirmed_regex(
        $shortText,
        '/(?<![\p{L}\p{N}])(?:2\s*cc|dva\s+cc)(?![\p{L}\p{N}])/u'
    );
    $startPackageQuestion = fcc_ai_v2_has_affirmed_regex(
        $shortText,
        '/(?<![\p{L}\p{N}])(?:start(?:ni)?\s+paket\p{L}*|start\s+your\s+journey)(?![\p{L}\p{N}])/u'
    );
    $qualificationOverviewQuestion = $mentionsTwoCc
        && fcc_ai_v2_contains_any($shortText, [
            'pocetak', 'krenuti', 'sto znaci', 'kako ostvariti', 'uvjet',
            'kvalifikacija', 'start paket', 'startni paket',
        ]);
    $fccAccessQuestion = fcc_ai_v2_has_affirmed_regex(
        $shortText,
        '/(?<![\p{L}\p{N}])fcc(?:\s+(?:pristup\p{L}*|clanstv\p{L}*|alat\p{L}*))?(?![\p{L}\p{N}])/u'
    ) && fcc_ai_v2_contains_any($shortText, ['pristup', 'automatski', 'dobijem', 'dobiti', 'imam pravo', 'clanstvo', 'nakon paketa', 'uz paket']);
    $packagePriceQuestion = fcc_ai_v2_has_affirmed_regex(
        $shortText,
        '/(?<![\p{L}\p{N}])(?:start(?:ni)?\s+paket\p{L}*|start\s+your\s+journey|skin\s*(?:i|and)?\s*body|sport\s*(?:i|and)?\s*wellness)(?![\p{L}\p{N}])/u'
    ) && fcc_ai_v2_contains_any($shortText, ['cijena', 'koliko kosta', 'iznos', 'eura', 'eur']);

    if($startPackageQuestion && $mentionsTwoCc && $fccAccessQuestion) {
        $specificBusinessTopic = [
            'matched' => true,
            'topic' => 'start_package_qualification_fcc',
            'reason_code' => 'business_start_qualification_fcc',
        ];
    } elseif($mentionsFourCc) {
        $specificBusinessTopic = ['matched' => true, 'topic' => 'qualification_correction', 'reason_code' => 'business_qualification_correction'];
    } elseif($fccAccessQuestion) {
        $specificBusinessTopic = ['matched' => true, 'topic' => 'fcc_access', 'reason_code' => 'business_fcc_access'];
    } elseif($packagePriceQuestion) {
        $specificBusinessTopic = ['matched' => true, 'topic' => 'package_price', 'reason_code' => 'business_package_price'];
    } elseif(fcc_ai_v2_has_affirmed_regex($shortText, '/(?<![\p{L}\p{N}])skin\s*(?:i|and)?\s*body(?![\p{L}\p{N}])/u')) {
        $specificBusinessTopic = ['matched' => true, 'topic' => 'start_package_skin_body', 'reason_code' => 'business_start_package_choice'];
    } elseif(fcc_ai_v2_has_affirmed_regex($shortText, '/(?<![\p{L}\p{N}])sport\s*(?:i|and)?\s*wellness(?![\p{L}\p{N}])/u')) {
        $specificBusinessTopic = ['matched' => true, 'topic' => 'start_package_sport_wellness', 'reason_code' => 'business_start_package_choice'];
    } elseif($qualificationOverviewQuestion) {
        $specificBusinessTopic = ['matched' => true, 'topic' => 'qualification_overview', 'reason_code' => 'business_qualification_overview'];
    } elseif($startPackageQuestion) {
        $specificBusinessTopic = ['matched' => true, 'topic' => 'start_package_overview', 'reason_code' => 'business_start_package_overview'];
    } elseif(fcc_ai_v2_has_affirmed_regex($shortText, '/(?<![\p{L}\p{N}])(?:osobni\s+popust|samo\s+popust|kupovati\s+za\s+sebe)(?![\p{L}\p{N}])/u')) {
        $specificBusinessTopic = ['matched' => true, 'topic' => 'personal_discount', 'reason_code' => 'business_goal_choice'];
    } elseif(fcc_ai_v2_has_affirmed_regex($shortText, '/(?<![\p{L}\p{N}])(?:prodavat\p{L}*\s+(?:forever\s+)?proizvod\p{L}*|prodaja\s+proizvod\p{L}*|graditi\s+tim|prodaja\s+i\s+tim)(?![\p{L}\p{N}])/u')) {
        $specificBusinessTopic = ['matched' => true, 'topic' => 'selling_or_team', 'reason_code' => 'business_goal_choice'];
    }
    if(
        !$specificBusinessTopic
        && (string) ($state['active_flow'] ?? '') === 'business'
        && fcc_ai_v2_contains_any($shortText, [
            'da', 'moze', 'zanima me', 'zelim vise', 'reci vise',
            'yes', 'sure', 'i am interested', 'tell me more',
            'ja', 'lahko', 'povej vec', 'zanima me',
            'да', 'може', 'интересува ме', 'кажете повече',
            'kako se prijavim', 'koji su uvjeti', 'koji su uvjeti suradnje',
            'how do i sign up', 'what are the terms',
            'kaksni so pogoji', 'kako se prijavim',
            'как да се регистрирам', 'какви са условията',
            'start paket', 'startni paket', 'start your journey', '2 cc', 'dva cc',
            '30 posto', '30% popust', 'assistant supervisor', 'fcc pristup',
        ])
        && count(preg_split('/\s+/u', $shortText) ?: []) <= 6
    ) {
        return ['matched' => true, 'topic' => 'continuation', 'reason_code' => 'business_followup'];
    }

    $contactSignal = fcc_ai_v2_contains_any($text, [
        'razgovarati s osobom iza aplikacije', 'razgovarati s vlasnikom aplikacije',
        'razgovarati s vlasnikom ove aplikacije',
        'osoba iza aplikacije', 'vlasnik aplikacije', 'kontakt vlasnika aplikacije',
        'javiti se osobi iza aplikacije', 'tko stoji iza aplikacije',
        'talk to the person behind this app', 'talk to someone behind this app',
        'person behind this app', 'owner of this app', 'app owner',
        'govoriti z osebo za aplikacijo', 'oseba za aplikacijo', 'lastnik aplikacije',
        'govoriti z lastnikom te aplikacije',
        'човека зад приложението', 'собственика на приложението',
        'контакт със собственика на приложението',
        'искам да говоря със собственика на това приложение',
    ]);

    $foodPyramidOnly = preg_match(
        '/(?<![\p{L}\p{N}])(?:prehramben\p{L}*|prehransk\p{L}*|food|хранителн\p{L}*)\s+piramid\p{L}*(?![\p{L}\p{N}])/u',
        $text
    ) === 1;
    $modelKeywordSignal = fcc_ai_v2_contains_any($text, [
        'mlm', 'mrezni marketing', 'network marketing',
        'pyramid scheme', 'piramidalna shema', 'млм', 'мрежов маркетинг',
    ]);
    $pyramidSignal = !$foodPyramidOnly
        && preg_match('/(?<![\p{L}\p{N}])(?:piramid\p{L}*|pyramid\p{L}*|пирамид\p{L}*)(?![\p{L}\p{N}])/u', $text) === 1
        && (
            fcc_ai_v2_contains_any($text, ['forever', 'mlm', 'mrezni marketing', 'network marketing', 'млм', 'мрежов маркетинг'])
            || count(preg_split('/\s+/u', $text) ?: []) <= 4
        );
    $modelSignal = $modelKeywordSignal || $pyramidSignal;

    $opportunitySignal = fcc_ai_v2_contains_any($text, [
        'zanima me suradnja', 'zanima me poslovna suradnja', 'poslovna suradnja',
        'poslovna prilika', 'zelim suradivati', 'postati suradnik',
        'forever posao', 'forever business', 'poslovni pocetak',
        'zelim krenuti u forever posao', 'zelim pokrenuti posao s foreverom',
        'pokrenuti posao s foreverom', 'krenuti s foreverom',
        'kako mogu postati suradnik', 'dodatni prihod', 'dodatan prihod',
        'dodatna zarada', 'rad od kuce', 'raditi od kuce',
        'zelim prodavati', 'zelim prodavati forever', 'kako prodavati forever',
        'pridruziti se timu', 'pridruziti se vasem timu', 'uclaniti se',
        'postati fbo', 'fbo', 'postati distributer', 'postati distributor',
        'trebam sponzora', 'tko mi je sponzor',
        'business opportunity', 'business collaboration', 'join forever',
        'extra income', 'additional income', 'work from home',
        'sell forever products', 'sell forever', 'join the team', 'join your team',
        'sign up as a distributor', 'become an fbo', 'become a distributor', 'find a sponsor',
        'zanima me sodelovanje', 'poslovno sodelovanje', 'poslovna priloznost',
        'dodatni zasluzek', 'dodaten prihodek', 'delo od doma', 'delati od doma',
        'prodajati forever', 'pridruziti se ekipi', 'vclaniti se',
        'postati distributer', 'potrebujem sponzorja',
        'интересувам се от сътрудничество', 'бизнес възможност',
        'интересувам се от бизнес възможността на forever',
        'допълнителен доход', 'работа от вкъщи',
        'искам да продавам forever', 'присъединя към екипа',
        'стана дистрибутор',
        'start paket', 'startni paket', 'start your journey', '2 cc', 'dva cc',
        '30 posto popusta', '30% popust', 'assistant supervisor', 'fcc pristup',
        'skin and body', 'skin body', 'sport and wellness', 'sport wellness',
    ]);

    if(
        !$opportunitySignal
        && count(preg_split('/\s+/u', $text) ?: []) <= 4
        && fcc_ai_v2_contains_any($text, ['fbo', 'distributer', 'distributor', 'sponzor', 'sponsor', 'дистрибутор', 'спонсор'])
    ) {
        $opportunitySignal = true;
    }

    if(!$opportunitySignal) {
        foreach([
            '/(?<![\p{L}\p{N}])(?:zanim\p{L}*|interesir\p{L}*)\s+me.{0,35}(?:suradnj\p{L}*|sodelovanj\p{L}*)(?![\p{L}\p{N}])/u',
            '/(?<![\p{L}\p{N}])dodatn\p{L}*\s+(?:prihod\p{L}*|zarad\p{L}*|zasluz\p{L}*)(?![\p{L}\p{N}])/u',
            '/(?<![\p{L}\p{N}])(?:koliko|kako|mogu\s+li|moze\s+li\s+se)\s+(?:se\s+)?zarad\p{L}*(?![\p{L}\p{N}])/u',
            '/(?<![\p{L}\p{N}])(?:rad|raditi|posao|delo|delati)\s+od\s+(?:kuce|doma)(?![\p{L}\p{N}])/u',
            '/(?<![\p{L}\p{N}])(?:zelim|hocu|planiram|kako)\s+(?:krenuti|pokrenuti|zapoceti).{0,35}(?:forever\p{L}*\s+posao|posao\s+(?:s|sa)\s+forever\p{L}*|s\s+forever\p{L}*)(?![\p{L}\p{N}])/u',
            '/(?<![\p{L}\p{N}])(?:zelim|hocu|kako|mogu\s+li)\s+prodav\p{L}*(?:.{0,30}(?:forever|proizvod\p{L}*))?(?![\p{L}\p{N}])/u',
            '/(?<![\p{L}\p{N}])(?:pridruz\p{L}*|prikljuc\p{L}*|uclan\p{L}*|vclan\p{L}*|join|sign\s+up).{0,35}(?:tim\p{L}*|ekip\p{L}*|forever)(?![\p{L}\p{N}])/u',
            '/(?<![\p{L}\p{N}])(?:postat\p{L}*|postan\p{L}*|become).{0,25}(?:suradnik\p{L}*|fbo|distribut\p{L}*)(?![\p{L}\p{N}])/u',
            '/(?<![\p{L}\p{N}])(?:earn|make)\s+(?:extra\s+)?money(?![\p{L}\p{N}])/u',
            '/(?<![\p{L}\p{N}])(?:kako\s+lahko\s+)?zasluz\p{L}*(?:.{0,35}prodaj\p{L}*)?(?![\p{L}\p{N}])/u',
            '/(?<![\p{L}\p{N}])(?:talk|speak).{0,30}(?:person|owner).{0,30}(?:app|application)(?![\p{L}\p{N}])/u',
        ] as $pattern) {
            if(fcc_ai_v2_has_affirmed_regex($text, $pattern)) {
                $opportunitySignal = true;
                break;
            }
        }
    }

    if(
        $opportunitySignal
        && preg_match(
            '/(?<![\p{L}\p{N}])(?:ne\s+(?:zanima|interesira)\p{L}*\s+me|nisam\s+zainteresiran\p{L}*|ne\s+zelim|necu|not\s+interested|do\s+not\s+want|don\'t\s+want|nisem\s+zainteresiran\p{L}*|не\s+се\s+интересувам|не\s+искам).{0,55}(?:suradnj\p{L}*|sodelovanj\p{L}*|prilik\p{L}*|priloznost\p{L}*|business|opportunit\p{L}*|prodav\p{L}*|join|tim\p{L}*|ekip\p{L}*|сътруднич\p{L}*|бизнес|възможност\p{L}*|продав\p{L}*)(?![\p{L}\p{N}])/u',
            $text
        ) === 1
    ) {
        $opportunitySignal = false;
    }

    if($modelSignal) {
        return ['matched' => true, 'topic' => 'model_question', 'reason_code' => 'business_model_transparency'];
    }
    if($contactSignal) {
        return ['matched' => true, 'topic' => 'contact_request', 'reason_code' => 'business_contact_request'];
    }
    if($specificBusinessTopic) {
        return $specificBusinessTopic;
    }
    if($opportunitySignal) {
        return ['matched' => true, 'topic' => 'opportunity', 'reason_code' => 'business_scope'];
    }

    return $empty;
}

function fcc_ai_v2_detect_intent(string $message, array $mentions, array $state = []): string {
    $text = fcc_ai_v2_normalize_text($message);

    if(fcc_ai_v2_contains_any($text, ['dosta nemas pojma', 'dosta nema smisla', 'odgovor nije koristan', 'resetiraj', 'krenimo ispocetka', 'start over', 'forget previous'])) {
        return 'reset';
    }

    if(fcc_ai_v2_contains_any($text, ['od sada hrvatski', 'odsada hrvatski', 'odgovori hrvatski', 'od zdaj odgovarjaj slovensko', 'odgovarjaj slovensko', 'answer in english', 'respond in english', 'english please', 'na engleskom'])) {
        return 'language_switch';
    }

    if(fcc_ai_v2_contains_any($text, [
        'drustvenim mrezama', 'edukativni sadrzaj', 'napravi whatsapp',
        'napisi poruku', 'napisi objavu', 'napravi objavu', 'whatsapp poruku',
        'whatsapp objavu', 'objavu za', 'tekst za',
        'write a message', 'write a social post', 'social post',
        'napisi objavo', 'napisi sporocilo', 'напиши публикация', 'напиши съобщение',
    ])) {
        return 'content_creation';
    }

    if(!empty(fcc_ai_v2_business_intent_profile($message, $state)['matched'])) {
        return 'business';
    }

    if(fcc_ai_v2_contains_any($text, ['bok', 'pozdrav', 'hello', 'hi', 'dobar dan', 'hvala', 'thanks', 'tks', 'nema na cemu']) && count(preg_split('/\s+/u', $text) ?: []) <= 5) {
        return 'small_talk';
    }

    if(in_array((string) ($state['active_flow'] ?? ''), ['content_creation'], true) && fcc_ai_v2_contains_any($text, ['da', 'moze', 'lahko', 'yes', 'sure'])) {
        return 'content_creation';
    }

    if(fcc_ai_v2_contains_any($text, ['zapravo sam u', 'ipak sam u', 'trziste mijenjam', 'actually i am in'])) {
        return 'market_change';
    }

    $compatibilityRequest = fcc_ai_v2_is_compatibility_request($text);
    if(
        $compatibilityRequest
        && fcc_ai_v2_contains_any($text, [
            'ima li smisla uz', 'sto ide uz', 'koji proizvod ide uz',
            'sluzbeno povezan proizvod', 'sluzbeno povezan',
            'what goes with', 'officially related product',
        ])
    ) {
        return 'recommendation';
    }

    if(
        fcc_ai_v2_contains_any($text, [
            'usporedi', 'razlika izmedu', 'koji je bolji',
            'bolji od', 'bolja od', 'bolje od',
            'compare', 'difference between', 'better than',
        ])
        || $compatibilityRequest
    ) {
        return 'comparison';
    }

    if($mentions && fcc_ai_v2_contains_any($text, ['kako se koristi', 'kako koristiti', 'sastojci', 'sadrzi', 'doziranje', 'koliko uzimati', 'what is', 'how to use', 'ingredients', 'does it contain'])) {
        return 'product_question';
    }

    if(
        fcc_ai_v2_contains_any($text, ['preporuci', 'preporucujes', 'preporuka', 'sto uzeti', 'sta uzeti', 'koji proizvod', 'trebam proizvod', 'recommend', 'which product', 'what should i take'])
        || preg_match('/(?<![\p{L}\p{N}])koj\p{L}*\s+proizvod\p{L}*(?![\p{L}\p{N}])/u', $text) === 1
    ) {
        return 'recommendation';
    }

    if($mentions) {
        return 'direct_product';
    }

    if(fcc_ai_v2_contains_any($text, ['da', 'moze', 'lahko', 'yes', 'sure']) && !empty($state['pending_question'])) {
        return 'followup_confirmation';
    }

    return 'advice';
}

function fcc_ai_v2_stopwords(string $language): array {
    $common = [
        'forever', 'proizvod', 'proizvoda', 'proizvodu', 'proizvodom', 'proizvode', 'proizvodi',
        'preporuka', 'preporuci', 'preporuciti', 'preporucujes', 'treba', 'trebam', 'trebate', 'zelim', 'imam',
        'koji', 'koja', 'kako', 'meni', 'molim', 'moja', 'moj', 'dodatak', 'dodaci', 'dodatka',
        'dodatke', 'prehrani', 'suplement', 'suplementi', 'vitamin', 'vitamini', 'napitak', 'napitke',
        'svakodnevno', 'svakodnevni', 'nakon', 'poslije', 'prije', 'uzimati', 'koristiti', 'aloe',
        'product', 'products', 'recommend', 'need', 'want', 'which', 'what', 'please', 'with', 'from',
        'this', 'that', 'supplement', 'supplements', 'vitamin', 'vitamins', 'drink', 'daily', 'after',
        'before', 'use', 'using', 'i', 'ili', 'za', 'na', 'od', 'u', 'je', 'su', 'the', 'and', 'or',
        'for', 'to',
    ];
    return array_fill_keys($common, true);
}

function fcc_ai_v2_meaningful_tokens(string $text, string $language): array {
    $stopwords = fcc_ai_v2_stopwords($language);
    preg_match_all('/[\p{L}\p{N}]+(?:[-+][\p{L}\p{N}]+)*/u', fcc_ai_v2_normalize_text($text), $matches);
    $tokens = array_values((array) ($matches[0] ?? []));
    $result = [];

    foreach($tokens as $token) {
        if(mb_strlen($token) < 3 || isset($stopwords[$token])) {
            continue;
        }
        $result[$token] = true;
    }

    return array_keys($result);
}

function fcc_ai_v2_token_stem(string $token): string {
    $token = fcc_ai_v2_normalize_text($token);
    if(mb_strlen($token) < 5) {
        return $token;
    }

    $suffixes = [
        'ovima', 'evima', 'acijama', 'anjima', 'enjima',
        'iranje', 'ivanje', 'ijskog', 'ijskom', 'ijskim',
        'anje', 'enja', 'anje', 'enje', 'ama', 'ima',
        'skog', 'skom', 'skim', 'ijsku', 'ijska', 'ijske', 'ijski',
        'ovima', 'evima', 'ove', 'eva', 'sku', 'ska', 'ske', 'ski', 'sko',
        'nog', 'nom', 'nim', 'noj', 'nu', 'om', 'em', 'oj',
        'a', 'e', 'i', 'o', 'u',
    ];

    foreach($suffixes as $suffix) {
        if(!str_ends_with($token, $suffix)) {
            continue;
        }
        $stem = mb_substr($token, 0, mb_strlen($token) - mb_strlen($suffix));
        if(mb_strlen($stem) >= 4) {
            return $stem;
        }
    }

    return $token;
}

function fcc_ai_v2_route_hint(string $message): string {
    $text = fcc_ai_v2_normalize_text($message);
    if(fcc_ai_v2_contains_any($text, ['koza', 'lice', 'krema', 'serum', 'hidratantni', 'hidratacija', 'sprej', 'rana', 'opeklina', 'osip', 'kosa', 'nokti', 'skin', 'face', 'cream', 'serum', 'hydrating', 'hydration', 'spray', 'hair', 'nails'])) {
        return 'topical';
    }
    if(fcc_ai_v2_contains_any($text, ['zubi', 'pasta za zube', 'desni', 'tooth', 'gums', 'oral care'])) {
        return 'oral_care';
    }
    if(fcc_ai_v2_contains_any($text, ['piti', 'napitak', 'dodatak prehrani', 'kapsule', 'tablete', 'drink', 'supplement', 'capsules'])) {
        return 'oral';
    }

    return 'unknown';
}

/**
 * High-signal entities are deterministic gates, not loose prompt hints. This
 * prevents a generic word such as "dodatak" from outranking the named nutrient
 * or product form the visitor actually requested.
 */
function fcc_ai_v2_skin_area_scope(string $message, array $state = []): string {
    $text = fcc_ai_v2_normalize_text($message);
    /* In a correction such as "nije lice ni tijelo, nego usne", the final
     * contrastive clause is the visitor's affirmed scope. Evaluate it first so
     * an earlier negated area cannot turn the correction into a mixed scope. */
    $correctionParts = preg_split(
        '/(?<![\p{L}\p{N}])(?:nego|vec|rather|instead|ampak)(?![\p{L}\p{N}])/u',
        $text
    ) ?: [];
    if(count($correctionParts) > 1) {
        $correction = (string) end($correctionParts);
        $correctedSignals = [
            'face' => '/(?<![\p{L}\p{N}])(?:koz\p{L}*\s+(?:na\s+)?lic\p{L}*|lic\p{L}*|obraz\p{L}*|facial\s+skin|face)(?![\p{L}\p{N}])/u',
            'body' => '/(?<![\p{L}\p{N}])(?:koz\p{L}*\s+na\s+tijel\p{L}*|tijel\p{L}*|body\s+skin|body)(?![\p{L}\p{N}])/u',
            'lips' => '/(?<![\p{L}\p{N}])(?:usn\p{L}*|lip\p{L}*)(?![\p{L}\p{N}])/u',
        ];
        $correctedAreas = [];
        foreach($correctedSignals as $area => $pattern) {
            if(fcc_ai_v2_has_affirmed_regex($correction, $pattern)) {
                $correctedAreas[] = $area;
            }
        }
        if(count($correctedAreas) === 1) {
            return (string) $correctedAreas[0];
        }
    }

    $signals = [
        'face' => fcc_ai_v2_has_affirmed_regex(
            $text,
            '/(?<![\p{L}\p{N}])(?:koz\p{L}*\s+(?:na\s+)?lic\p{L}*|lic\p{L}*|obraz\p{L}*|facial\s+skin|face)(?![\p{L}\p{N}])/u'
        ),
        'body' => fcc_ai_v2_has_affirmed_regex(
            $text,
            '/(?<![\p{L}\p{N}])(?:koz\p{L}*\s+na\s+tijel\p{L}*|tijel\p{L}*|body\s+skin|body)(?![\p{L}\p{N}])/u'
        ),
        'lips' => fcc_ai_v2_has_affirmed_regex(
            $text,
            '/(?<![\p{L}\p{N}])(?:usn\p{L}*|lip\p{L}*)(?![\p{L}\p{N}])/u'
        ),
    ];
    $affirmedAreas = array_keys(array_filter($signals));
    if(count($affirmedAreas) === 1) {
        return (string) $affirmedAreas[0];
    }
    if(count($affirmedAreas) > 1) {
        return 'mixed';
    }

    $pendingSlot = is_array($state['pending_slot'] ?? null) ? $state['pending_slot'] : [];
    $pendingSlotKey = (string) ($pendingSlot['slot_key'] ?? '');
    $previousArea = (string) ($state['skin_area'] ?? '');
    $isSkinSlot = in_array($pendingSlotKey, ['skin_area', 'skin_goal'], true);
    $isSkinGoalAnswer = fcc_ai_v2_has_affirmed_regex(
        $text,
        '/(?<![\p{L}\p{N}])(?:hidratacij\p{L}*|vlazen\p{L}*|suh\p{L}*|dehidriran\p{L}*|zategnut\p{L}*|zatez\p{L}*|ciscen\p{L}*|ocist\p{L}*|umiv\p{L}*|hydration|cleansing)(?![\p{L}\p{N}])/u'
    );
    if($isSkinSlot && $isSkinGoalAnswer && in_array($previousArea, ['face', 'body', 'lips', 'mixed'], true)) {
        return $previousArea;
    }

    return '';
}

function fcc_ai_v2_query_entity_profiles(string $message, array $state = []): array {
    $text = fcc_ai_v2_normalize_text($message);
    $profiles = [
        'vitamin_c' => [
            'query' => '/(?<![\p{L}\p{N}])(?:vitamin(?:om|a)?\s+c|askorb\p{L}*|acerol\p{L}*)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:vitamin\s+c|askorb\p{L}*|acerol\p{L}*|absorbent[\s-]*c)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-absorbent-c'],
            'route' => 'oral',
        ],
        'vitamin_d' => [
            'query' => '/(?<![\p{L}\p{N}])(?:vitamin(?:om|a)?\s+d3?|kolekalciferol|cholecalciferol|absorbent[\s-]*d)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:vitamin\s+d3?|kolekalciferol|cholecalciferol|absorbent[\s-]*d)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['absorbent-d'],
            'route' => 'oral',
        ],
        'vitamin_b12' => [
            'query' => '/(?<![\p{L}\p{N}])(?:vitamin(?:om|a)?\s+b[\s-]*12|b[\s-]*12|kobalamin\p{L}*)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:vitamin\s+b[\s-]*12|b[\s-]*12|kobalamin\p{L}*)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-b12-plus'],
            'route' => 'oral',
        ],
        'plant_protein' => [
            'query' => '/(?<![\p{L}\p{N}])(?:biljn\p{L}*\s+protein\p{L}*|plant[\s-]+protein|protein\p{L}*\s+(?:u\s+)?prah\p{L}*|proteinsk\p{L}*\s+prah\p{L}*|dodatn\p{L}*\s+unos\s+protein\p{L}*)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:biljn\p{L}*\s+protein\p{L}*|plant[\s-]+protein)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-plant-protein'],
            'route' => 'oral',
        ],
        'multimineral' => [
            'query' => '/(?<![\p{L}\p{N}])(?:multimineral\p{L}*|vise\s+mineral\p{L}*|mineral\p{L}*(?:\s+dodatak|\s+u\s+tabletama)?)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:nature[\s-]*min|multimineral\p{L}*|morsk\p{L}*\s+mineral\p{L}*|vise\s+deklariran\p{L}*\s+mineral\p{L}*)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-nature-min'],
            'route' => 'oral',
        ],
        'facial_hydration' => [
            'query' => '/(?<![\p{L}\p{N}])(?:(?:lagan\p{L}*\s+)?hidratantn\p{L}*\s+serum\p{L}*|hydrating\s+serum|lightweight\s+hydrating\s+serum|(?:(?:koz\p{L}*\s+(?:na\s+)?lic\p{L}*|lic\p{L}*|obraz\p{L}*|facial\s+skin|face).{0,55}(?:suh\p{L}*|dehidriran\p{L}*|zategnut\p{L}*|zatez\p{L}*|dry|dehydrated|tight)|(?:suh\p{L}*|dehidriran\p{L}*|zategnut\p{L}*|zatez\p{L}*|dry|dehydrated|tight).{0,55}(?:koz\p{L}*\s+(?:na\s+)?lic\p{L}*|lic\p{L}*|obraz\p{L}*|facial\s+skin|face)))(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:hydrating\s+serum|hidratantn\p{L}*\s+serum\p{L}*|hijaluron\p{L}*|natrijev\s+hijaluronat)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-hydrating-serum'],
            'route' => 'topical',
        ],
        'face_neck_cleansing_need' => [
            'query' => '/(?<![\p{L}\p{N}])(?:(?:zelim|trebam|trazim|treba\s+mi|want|need)\b.{0,35}(?:(?:proizvod\p{L}*|njeg\p{L}*|product|care)\s+za\s+(?:svakodnevn\p{L}*\s+|daily\s+)?(?:ciscen\p{L}*|umiv\p{L}*|clean\p{L}*)|za\s+(?:svakodnevn\p{L}*\s+|daily\s+)?(?:ciscen\p{L}*|umiv\p{L}*|clean\p{L}*)|(?:ocist\p{L}*|clean)\b).{0,35}(?:koz\p{L}*\s+)?(?:lic\p{L}*|obraz\p{L}*|face)(?:.{0,25}(?:vrat\p{L}*|neck))?)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:infinite[\s-]*hydrating[\s-]*cleanser)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['infinite-hydrating-cleanser'],
            'route' => 'topical',
        ],
        'eye_area_care_need' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:krem\p{L}*|njeg\p{L}*|proizvod\p{L}*|cream|care)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:podrucj\p{L}*\s+oko\s+ocij\p{L}*|oko\s+ocij\p{L}*|krem\p{L}*\s+za\s+oci|eye\s+(?:area|cream))(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*awakening[\s-]*eye[\s-]*cream)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-awakening-eye-cream'],
            'route' => 'topical',
        ],
        'hand_hygiene_need' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:higijen\p{L}*|dezinfek\p{L}*|sanitiz\p{L}*)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:ruk\p{L}*|hands?)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*hand[\s-]*sanitizer)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-hand-sanitizer'],
            'route' => 'topical',
        ],
        'underarm_deodorant_need' => [
            'query' => '/^(?!.*(?<![\p{L}\p{N}])(?:stopal\p{L}*|cipel\p{L}*|obuc\p{L}*|intim\p{L}*|feet|foot|shoes?)(?![\p{L}\p{N}]))(?:(?=.*(?<![\p{L}\p{N}])(?:dezodor\p{L}*|deodorant\p{L}*)(?![\p{L}\p{N}]))|(?=.*(?<![\p{L}\p{N}])(?:pazuh\p{L}*|underarm\p{L}*)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:neugod\p{L}*\s+miris\p{L}*|odou?r)(?![\p{L}\p{N}])))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:aloe[\s-]*ever[\s-]*shield)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['aloe-ever-shield'],
            'route' => 'topical',
        ],
        'overnight_face_rinse_need' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:lic\p{L}*|obraz\p{L}*|face)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:preko\s+noci|tijekom\s+noci|overnight)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:(?:ispir\p{L}*|wash\p{L}*).{0,25}(?:ujutr\p{L}*|morning)|(?:ujutr\p{L}*|morning).{0,25}(?:ispir\p{L}*|wash\p{L}*))(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*refining[\s-]*gel[\s-]*mask)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-refining-gel-mask'],
            'route' => 'topical',
        ],
        'face_balancing_need' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:koz\p{L}*\s+lic\p{L}*|lic\p{L}*|obraz\p{L}*|face)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:uravnotez\p{L}*|balanc\p{L}*)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:osvjez\p{L}*|tonir\p{L}*|refresh\p{L}*|tone)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*logic[\s-]*gel[\s-]*balancing[\s-]*essence)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-logic-gel-balancing-essence'],
            'route' => 'topical',
        ],
        'aloe_mask_prep' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:priprem\p{L}*|prepare\p{L}*)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])aloe(?:\s+bio[\s-]*cellulose)?\s+mask\p{L}*(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:aloe[\s-]*activator)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['aloe-activator'],
            'route' => 'topical',
        ],
        'aloe_berry_flavour' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])aloe(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])napit\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:brusnic\p{L}*|cranberr\p{L}*|bobicast\p{L}*|berry)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:aloe[\s-]*berry[\s-]*nectar)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['aloe-berry-nectar'],
            'route' => 'oral',
        ],
        'aloe_mango_flavour' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])aloe(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])napit\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])mango\p{L}*(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*aloe[\s-]*mango)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-aloe-mango'],
            'route' => 'oral',
        ],
        'aloe_peach_flavour' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])aloe(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])napit\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:breskv\p{L}*|peach\p{L}*)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*aloe[\s-]*peaches)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-aloe-peaches'],
            'route' => 'oral',
        ],
        'aloe_plain_flavour' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])aloe(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:napit\p{L}*|pic\p{L}*|piti|drink\p{L}*|beverage)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:obic\p{L}*|neutraln\p{L}*|bez\s+(?:(?:dodatn\p{L}*|vocn\p{L}*)\s+){0,2}okus\p{L}*|plain|unflavou?red)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*aloe[\s-]*vera[\s-]*gel)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-aloe-vera-gel'],
            'route' => 'oral',
        ],
        'bio_cellulose_face_mask' => [
            'query' => '/(?<![\p{L}\p{N}])bio[\s-]*celulozn\p{L}*\s+mask\p{L}*(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:bio[\s-]*cellulose[\s-]*mask)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-bio-cellulose-mask'],
            'route' => 'topical',
        ],
        'calcium_multimineral' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])multimineral\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])kalcij\p{L}*(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*calcium)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-calcium'],
            'route' => 'oral',
        ],
        'daily_multivitamin_multimineral' => [
            'query' => '/(?:(?=.*(?<![\p{L}\p{N}])multivitamin\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:mineral\p{L}*|multimineral\p{L}*)(?![\p{L}\p{N}]))|(?<![\p{L}\p{N}])multivitaminsko[\s-]*multimineraln\p{L}*\s+dodatak(?![\p{L}\p{N}])|(?=.*(?<![\p{L}\p{N}])(?:svakodnevn\p{L}*|dnevn\p{L}*|daily|opci\s+dnevn\p{L}*)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])vitamin\p{L}*\s+i\s+mineral\p{L}*(?![\p{L}\p{N}])))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*daily)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-daily'],
            'route' => 'oral',
        ],
        'vitamin_mineral_gummy' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:vitamin\p{L}*[\s-]+mineral\p{L}*|vitamin\p{L}*\s+i\s+mineral\p{L}*)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:gumen\p{L}*\s+bombon\p{L}*|gumm\p{L}*)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*immune[\s-]*gummy)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-immune-gummy'],
            'route' => 'oral',
        ],
        'fish_collagen_tablets' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:riblj\p{L}*\s+kolagen\p{L}*|morsk\p{L}*\s+kolagen\p{L}*|marine\s+collagen)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:oraln\p{L}*\s+)?tablet\p{L}*(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*infinite[\s-]*firming[\s-]*complex)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-infinite-firming-complex'],
            'route' => 'oral',
        ],
        'coq10_powder_sachet' => [
            'query' => '/(?<![\p{L}\p{N}])(?:koenzim\p{L}*\s+q[\s-]*10|coq[\s-]*10)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*nutra[\s-]*q[\s-]*10)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-nutra-q10'],
            'route' => 'oral',
        ],
        'marine_collagen_sachet' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:morsk\p{L}*|riblj\p{L}*|marine)\s+kolagen\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:tekuc\p{L}*|napit\p{L}*|vrecic\p{L}*|liquid|drink|sachet)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*marine[\s-]*collagen|morsk\p{L}*\s+kolagen\p{L}*)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['marine-collagen'],
            'route' => 'oral',
        ],
        'face_exfoliator_need' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:lic\p{L}*|obraz\p{L}*|face)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:piling\p{L}*|eksfolij\p{L}*|exfoliat\p{L}*|scrub)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*smoothing[\s-]*exfoliator)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-smoothing-exfoliator'],
            'route' => 'topical',
        ],
        'gel_face_moisturizer' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])gel\s+hidratantn\p{L}*\s+krem\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])jut\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])vecer\p{L}*(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*logic[\s-]*aloe[\s-]*soothing[\s-]*gel[\s-]*moisture)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-logic-aloe-soothing-gel-moisture'],
            'route' => 'topical',
        ],
        'overnight_gel_mask' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])gel\s+mask\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:nocn\p{L}*|noc\p{L}*|preko\s+noci|overnight)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*refining[\s-]*gel[\s-]*mask)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-refining-gel-mask'],
            'route' => 'topical',
        ],
        'face_neck_serum_after_cleansing' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])serum\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])lic\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])vrat\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])nakon\s+cisc\p{L}*(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:infinite[\s-]*by[\s-]*forever[\s-]*firming[\s-]*serum)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['infinite-by-forever-firming-serum'],
            'route' => 'topical',
        ],
        'restoring_face_cream_after_cleansing' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])krem\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])lic\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])nakon\s+cisc\p{L}*(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:infinite[\s-]*by[\s-]*forever[\s-]*restoring[\s-]*creme)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['infinite-by-forever-restoring-creme'],
            'route' => 'topical',
        ],
        'l_arginine' => [
            'query' => '/(?<![\p{L}\p{N}])(?:l[\s-]*arginin\p{L}*|arginin\p{L}*|argi\+)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:l[\s-]*arginin\p{L}*|arginin\p{L}*|argi\+)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-argi'],
            'route' => 'oral',
        ],
        'calcium' => [
            'query' => '/(?<![\p{L}\p{N}])(?:kalcij\p{L}*|calcium)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:kalcij\p{L}*|calcium)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-calcium'],
            'route' => 'oral',
        ],
        'omega_3' => [
            'query' => '/(?<![\p{L}\p{N}])(?:omega[\s-]*3|riblj\p{L}*\s+ulj\p{L}*|fish\s+oil)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:omega[\s-]*3|riblje\s+ulje|fish\s+oil|arctic\s+sea)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-arctic-sea'],
            'route' => 'oral',
        ],
        'fiber' => [
            'query' => '/(?<![\p{L}\p{N}])(?:vlakna|vlakno|fiber|fibre)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:vlakna|vlakno|fiber|fibre)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-fiber'],
            'route' => 'oral',
        ],
        'probiotic_cultures' => [
            'query' => '/(?<![\p{L}\p{N}])(?:probiotik\p{L}*|bakterijsk\p{L}*\s+kultur\p{L}*|probiotic\p{L}*)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:probiotik\p{L}*|bakterijsk\p{L}*\s+kultur\p{L}*|probiotic\p{L}*|active\s+pro[\s-]*b)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-active-pro-b'],
            'route' => 'oral',
        ],
        'topical_aloe_hydrating_spray' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:topikaln\p{L}*|vanjsk\p{L}*|koz\p{L}*|skin)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])sprej\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:hidratantn\p{L}*|hidratacij\p{L}*|njeg\p{L}*|hydrating|care)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*aloe[\s-]*first[\s-]*spray)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-aloe-first-spray'],
            'route' => 'topical',
        ],
        'turmeric_zinc_aloe_lozenge' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:pastil\p{L}*|lozeng\p{L}*)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])kurkum\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])cink\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])aloj\p{L}*(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*aloeturm)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-aloeturm'],
            'route' => 'oral',
        ],
        'adult_womens_fragrance' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:zensk\p{L}*|women\p{L}*|for\s+women)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:parfem\p{L}*|miris\p{L}*|fragrance|perfume)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*alofa)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-alofa'],
            'route' => 'topical',
        ],
        'bee_pollen_supplement' => [
            'query' => '/(?<![\p{L}\p{N}])(?:cvjetn\p{L}*\s+polen\p{L}*|pcelinj\p{L}*\s+polen\p{L}*|flower\s+pollen|bee\s+pollen)(?![\p{L}\p{N}])/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*bee[\s-]*pollen)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-bee-pollen'],
            'route' => 'oral',
        ],
        'garlic_thyme_botanical' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:cesnjak\p{L}*|garlic)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:timijan\p{L}*|thyme)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*garlic[\s-]*thyme)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-garlic-thyme'],
            'route' => 'oral',
        ],
        'zinc_c_d_mushroom_supplement' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])cink\p{L}*(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])vitamin\p{L}*.{0,10}c(?:\s+i\s+d)?(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:vitamin\p{L}*.{0,10}d|c\s+i\s+d)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:maitake|shiitake)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*immublend)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-immublend'],
            'route' => 'oral',
        ],
        'lycium_licorice_botanical' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:lycium\p{L}*|goji)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:sladic\p{L}*|licorice)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*lycium[\s-]*plus)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-lycium-plus'],
            'route' => 'oral',
        ],
        'adult_mens_cologne' => [
            'query' => '/(?=.*(?<![\p{L}\p{N}])(?:musk\p{L}*|men\p{L}*|for\s+men)(?![\p{L}\p{N}]))(?=.*(?<![\p{L}\p{N}])(?:kolonjsk\p{L}*|parfem\p{L}*|cologne|fragrance)(?![\p{L}\p{N}]))/u',
            'catalog' => '/(?<![\p{L}\p{N}])(?:forever[\s-]*malosi)(?![\p{L}\p{N}])/u',
            'preferred_keys' => ['forever-malosi'],
            'route' => 'topical',
        ],
    ];

    if(
        preg_match('/(?<![\p{L}\p{N}])aloe(?![\p{L}\p{N}])/u', $text) === 1
        && preg_match('/(?<![\p{L}\p{N}])(?:napit\p{L}*|pic\p{L}*|piti|drink\p{L}*|beverage)(?![\p{L}\p{N}])/u', $text) === 1
    ) {
        $profiles['aloe_beverage'] = [
            'query' => '/aloe/u',
            'catalog' => '/(?<![\p{L}\p{N}])aloe(?![\p{L}\p{N}])/u',
            'preferred_keys' => [],
            'route' => 'oral',
            'categories' => ['drinks', 'napitci'],
        ];
    }

    $matchedProfiles = array_filter(
        $profiles,
        static function(array $profile) use ($text, $message): bool {
            $query = (string) ($profile['query'] ?? '');
            if($query === '' || preg_match($query, $text) !== 1) {
                return false;
            }

            /* Simple entity patterns are clause-aware: a rejected entity may
             * not survive into goals/candidates, while the positive entity
             * after "nego/but/instead" remains eligible. Composite lookahead
             * profiles have their own multi-signal guards below. */
            return str_contains($query, '(?=')
                || fcc_ai_v2_has_affirmed_regex($message, $query);
        }
    );

    $skinArea = fcc_ai_v2_skin_area_scope($message, $state);
    $pendingSlot = is_array($state['pending_slot'] ?? null) ? $state['pending_slot'] : [];
    $pendingSlotKey = (string) ($pendingSlot['slot_key'] ?? '');
    $pendingSkinGoal = in_array($pendingSlotKey, ['skin_area', 'skin_goal'], true);
    if(
        $skinArea === 'face'
        && $pendingSkinGoal
        && fcc_ai_v2_has_affirmed_regex(
            $text,
            '/(?<![\p{L}\p{N}])(?:hidratacij\p{L}*|vlazen\p{L}*|suh\p{L}*|dehidriran\p{L}*|zategnut\p{L}*|zatez\p{L}*|hydration|dry|dehydrated|tight)(?![\p{L}\p{N}])/u'
        )
    ) {
        $matchedProfiles['facial_hydration'] = $profiles['facial_hydration'];
    }
    if(
        $skinArea === 'face'
        && $pendingSkinGoal
        && fcc_ai_v2_has_affirmed_regex(
            $text,
            '/(?<![\p{L}\p{N}])(?:ciscen\p{L}*|ocist\p{L}*|umiv\p{L}*|cleansing|clean)(?![\p{L}\p{N}])/u'
        )
    ) {
        $matchedProfiles['face_neck_cleansing_need'] = $profiles['face_neck_cleansing_need'];
    }

    if(in_array($skinArea, ['body', 'lips', 'mixed'], true)) {
        foreach([
            'facial_hydration', 'face_neck_cleansing_need', 'eye_area_care_need',
            'overnight_face_rinse_need', 'face_balancing_need',
            'gel_face_moisturizer', 'overnight_gel_mask',
            'face_neck_serum_after_cleansing', 'restoring_face_cream_after_cleansing',
        ] as $facialProfileKey) {
            unset($matchedProfiles[$facialProfileKey]);
        }
    }

    /* A negated cosmetic concern is never a recommendation goal. Explicitly
     * requested product formats remain valid neutral catalog queries. */
    if(
        isset($matchedProfiles['facial_hydration'])
        && preg_match('/(?<![\p{L}\p{N}])(?:(?:lagan\p{L}*\s+)?hidratantn\p{L}*\s+serum\p{L}*|hydrating\s+serum)(?![\p{L}\p{N}])/u', $text) !== 1
        && !fcc_ai_v2_has_affirmed_regex(
            $text,
            '/(?<![\p{L}\p{N}])(?:suh\p{L}*|dehidriran\p{L}*|zategnut\p{L}*|zatez\p{L}*|dry|dehydrated|tight)(?![\p{L}\p{N}])/u'
        )
    ) {
        unset($matchedProfiles['facial_hydration']);
    }
    if(
        isset($matchedProfiles['daily_multivitamin_multimineral'])
        && !fcc_ai_v2_has_affirmed_regex(
            $text,
            '/(?<![\p{L}\p{N}])multivitamin\p{L}*(?![\p{L}\p{N}])/u'
        )
        && !(
            fcc_ai_v2_has_affirmed_regex(
                $text,
                '/(?<![\p{L}\p{N}])(?:svakodnevn\p{L}*|dnevn\p{L}*|opci\s+dnevn\p{L}*|daily)(?![\p{L}\p{N}])/u'
            )
            && fcc_ai_v2_has_affirmed_regex(
                $text,
                '/(?<![\p{L}\p{N}])vitamin\p{L}*\s+i\s+mineral\p{L}*(?![\p{L}\p{N}])/u'
            )
        )
    ) {
        unset($matchedProfiles['daily_multivitamin_multimineral']);
    }

    /* Exact neutral form/flavour descriptions outrank their generic nutrient
     * or product-family profile. Removing only the conflicting generic gate
     * keeps the allowed set deterministic without weakening catalog review. */
    $specificPrecedence = [
        'aloe_berry_flavour' => ['aloe_beverage'],
        'aloe_mango_flavour' => ['aloe_beverage'],
        'aloe_peach_flavour' => ['aloe_beverage'],
        'aloe_plain_flavour' => ['aloe_beverage'],
        'calcium_multimineral' => ['multimineral'],
        'daily_multivitamin_multimineral' => ['multimineral'],
        'vitamin_mineral_gummy' => ['multimineral'],
        'fish_collagen_tablets' => ['vitamin_c'],
        'coq10_powder_sachet' => ['multimineral'],
    ];
    foreach($specificPrecedence as $specificKey => $genericKeys) {
        if(!isset($matchedProfiles[$specificKey])) {
            continue;
        }
        foreach($genericKeys as $genericKey) {
            unset($matchedProfiles[$genericKey]);
        }
    }

    return $matchedProfiles;
}

/**
 * Build server-owned, neutral recommendation goals. Only entities that can be
 * matched to a reviewed catalog record are admitted here. Medical context is
 * intentionally handled by fcc_ai_v2_medical_need_profile() instead.
 *
 * @return array{goal_keys:array<int,string>,concern_class:string,entity_keys:array<int,string>}
 */
function fcc_ai_v2_goal_profile(string $message, array $state = []): array {
    $entityKeys = array_keys(fcc_ai_v2_query_entity_profiles($message, $state));
    $goalMap = [
        'vitamin_c' => 'nutrient_vitamin_c',
        'vitamin_d' => 'nutrient_vitamin_d',
        'vitamin_b12' => 'nutrient_vitamin_b12',
        'l_arginine' => 'nutrient_l_arginine',
        'plant_protein' => 'plant_protein',
        'multimineral' => 'multimineral',
        'facial_hydration' => 'facial_hydration',
        'calcium' => 'nutrient_calcium',
        'omega_3' => 'nutrient_omega_3',
        'fiber' => 'nutrient_fiber',
        'probiotic_cultures' => 'nutrient_probiotic_cultures',
        'aloe_beverage' => 'aloe_beverage_format',
        'aloe_plain_flavour' => 'aloe_beverage_plain',
        'aloe_mask_prep' => 'aloe_mask_preparation',
        'aloe_berry_flavour' => 'aloe_beverage_berry',
        'aloe_mango_flavour' => 'aloe_beverage_mango',
        'aloe_peach_flavour' => 'aloe_beverage_peach',
        'bio_cellulose_face_mask' => 'facial_mask_bio_cellulose',
        'calcium_multimineral' => 'nutrient_calcium',
        'daily_multivitamin_multimineral' => 'daily_multivitamin_multimineral',
        'vitamin_mineral_gummy' => 'vitamin_mineral_gummy',
        'fish_collagen_tablets' => 'fish_collagen_tablets',
        'coq10_powder_sachet' => 'coq10_powder_sachet',
        'marine_collagen_sachet' => 'marine_collagen_sachet',
        'face_exfoliator_need' => 'facial_exfoliation',
        'gel_face_moisturizer' => 'facial_gel_moisturizer',
        'overnight_gel_mask' => 'facial_overnight_gel_mask',
        'face_neck_serum_after_cleansing' => 'facial_serum_after_cleansing',
        'restoring_face_cream_after_cleansing' => 'facial_cream_after_cleansing',
        'face_neck_cleansing_need' => 'facial_cleansing',
        'eye_area_care_need' => 'eye_area_care',
        'hand_hygiene_need' => 'hand_hygiene',
        'underarm_deodorant_need' => 'underarm_deodorant',
        'overnight_face_rinse_need' => 'facial_overnight_gel_mask',
        'face_balancing_need' => 'facial_balancing',
        'topical_aloe_hydrating_spray' => 'topical_skin_hydrating_spray',
        'turmeric_zinc_aloe_lozenge' => 'turmeric_zinc_aloe_lozenge',
        'adult_womens_fragrance' => 'adult_womens_fragrance',
        'bee_pollen_supplement' => 'bee_pollen_supplement',
        'garlic_thyme_botanical' => 'garlic_thyme_botanical',
        'zinc_c_d_mushroom_supplement' => 'zinc_c_d_mushroom_supplement',
        'lycium_licorice_botanical' => 'lycium_licorice_botanical',
        'adult_mens_cologne' => 'adult_mens_cologne',
    ];
    $goalKeys = [];
    foreach($entityKeys as $entityKey) {
        if(isset($goalMap[$entityKey])) {
            $goalKeys[] = $goalMap[$entityKey];
        }
    }

    $text = fcc_ai_v2_normalize_text($message);
    $pendingSlot = is_array($state['pending_slot'] ?? null) ? $state['pending_slot'] : [];
    $pendingSlotKey = (string) ($pendingSlot['slot_key'] ?? '');
    $skinArea = fcc_ai_v2_skin_area_scope($message, $state);
    $pendingSkinSlot = in_array($pendingSlotKey, ['need_area', 'skin_area', 'skin_goal', 'body_area', 'lip_fact'], true);
    $skinContextSignal = preg_match(
        '/(?<![\p{L}\p{N}])(?:koz\p{L}*|lic\p{L}*|obraz\p{L}*|tijel\p{L}*|usn\p{L}*|serum\p{L}*|krem\p{L}*|njeg\p{L}*|hidratacij\p{L}*|suh\p{L}*|zategnut\p{L}*|zatez\p{L}*|ciscen\p{L}*|umiv\p{L}*|face|body|lips?|skin|hydration|cleansing)(?![\p{L}\p{N}])/u',
        $text
    ) === 1;
    if(!$goalKeys) {
        if(($pendingSkinSlot || $skinContextSignal) && $skinArea === 'face') {
            $goalKeys[] = 'facial_skincare';
        } elseif(($pendingSkinSlot || $skinContextSignal) && $skinArea === 'body') {
            $goalKeys[] = 'body_skin_care';
        } elseif(($pendingSkinSlot || $skinContextSignal) && $skinArea === 'lips') {
            $goalKeys[] = 'lip_care';
        } elseif(($pendingSkinSlot || $skinContextSignal) && $skinArea === 'mixed') {
            $goalKeys[] = 'mixed_skin_area';
        } elseif(
            in_array($pendingSlotKey, ['need_area', 'nutrient_type'], true)
            && preg_match('/(?<![\p{L}\p{N}])(?:vitamin\p{L}*|mineral\p{L}*|protein\p{L}*|dodatak\p{L}*)(?![\p{L}\p{N}])/u', $text) === 1
        ) {
            $goalKeys[] = 'nutrient_selection';
        }
    }

    $goalKeys = fcc_ai_v2_unique_strings($goalKeys, 8);
    $concernClass = 'unknown';
    if(array_intersect($goalKeys, [
        'facial_hydration', 'facial_skincare', 'skin_care', 'body_skin_care',
        'lip_care', 'mixed_skin_area',
        'aloe_mask_preparation', 'facial_mask_bio_cellulose',
        'facial_gel_moisturizer', 'facial_overnight_gel_mask',
        'facial_serum_after_cleansing', 'facial_cream_after_cleansing',
        'facial_cleansing', 'eye_area_care', 'facial_balancing',
        'facial_exfoliation',
    ])) {
        $concernClass = 'cosmetic';
    } elseif(array_intersect($goalKeys, [
        'hand_hygiene', 'underarm_deodorant', 'topical_skin_hydrating_spray',
        'adult_womens_fragrance', 'adult_mens_cologne',
    ])) {
        $concernClass = 'topical';
    } elseif($goalKeys) {
        $concernClass = 'nutrient';
    }

    return [
        'goal_keys' => $goalKeys,
        'concern_class' => $concernClass,
        'entity_keys' => array_values($entityKeys),
    ];
}

/** @return array{text:string,slot_key:string,question_id:string,accepted_values:array<int,string>} */
function fcc_ai_v2_discovery_question(string $message, string $language, array $state = []): array {
    $text = fcc_ai_v2_normalize_text($message);
    $skinSignal = preg_match('/(?<![\p{L}\p{N}])(?:koz\p{L}*|lic\p{L}*|serum\p{L}*|krema\p{L}*|njeg\p{L}*)(?![\p{L}\p{N}])/u', $text) === 1;
    $faceSignal = preg_match('/(?<![\p{L}\p{N}])(?:lic\p{L}*|serum\p{L}*)(?![\p{L}\p{N}])/u', $text) === 1;
    $nutrientSignal = preg_match('/(?<![\p{L}\p{N}])(?:vitamin\p{L}*|mineral\p{L}*|protein\p{L}*|dodatak\p{L}*|prehran\p{L}*)(?![\p{L}\p{N}])/u', $text) === 1;

    if($skinSignal && !$faceSignal) {
        return [
            'text' => fcc_ai_v2_localized_text($language, [
                'hr' => 'Je li fokus na koži lica ili tijela?',
                'en' => 'Is your focus facial skin or body skin?',
                'sl' => 'Je poudarek na koži obraza ali telesa?',
                'bg' => 'Фокусът върху кожата на лицето или тялото ли е?',
            ]),
            'slot_key' => 'skin_area',
            'question_id' => 'discover.skin_area.v1',
            'accepted_values' => ['face', 'body'],
        ];
    }

    if($skinSignal || in_array('facial_skincare', (array) ($state['goal_keys'] ?? []), true)) {
        return [
            'text' => fcc_ai_v2_localized_text($language, [
                'hr' => 'Što je glavni cilj njege lica: hidratacija i osjećaj zatezanja, čišćenje ili lagana tekstura?',
                'en' => 'What is the main facial-care goal: hydration and tightness, cleansing, or a lightweight texture?',
                'sl' => 'Kaj je glavni cilj nege obraza: vlaženje in občutek zategovanja, čiščenje ali lahka tekstura?',
                'bg' => 'Каква е основната цел за лицето: хидратация и опъване, почистване или лека текстура?',
            ]),
            'slot_key' => 'skin_goal',
            'question_id' => 'discover.skin_goal.v1',
            'accepted_values' => ['hydration', 'cleansing', 'lightweight_texture'],
        ];
    }

    if($nutrientSignal) {
        return [
            'text' => fcc_ai_v2_localized_text($language, [
                'hr' => 'Tražite li konkretno vitamin, multimineralni dodatak ili biljni protein?',
                'en' => 'Are you looking for a specific vitamin, a multimineral supplement, or plant protein?',
                'sl' => 'Iščete določen vitamin, multimineralni dodatek ali rastlinske beljakovine?',
                'bg' => 'Търсите конкретен витамин, мултиминерална добавка или растителен протеин?',
            ]),
            'slot_key' => 'nutrient_type',
            'question_id' => 'discover.nutrient_type.v1',
            'accepted_values' => ['vitamin_c', 'vitamin_d', 'vitamin_b12', 'multimineral', 'plant_protein'],
        ];
    }

    return [
        'text' => fcc_ai_v2_localized_text($language, [
            'hr' => 'Što želite postići ili koji problem želite riješiti? Naziv proizvoda ne trebate znati.',
            'en' => 'Is your main goal skin care, choosing a vitamin/mineral/protein, or checking a product you already have?',
            'sl' => 'Je vaš glavni cilj nega kože, izbira vitamina/minerala/beljakovin ali preverjanje izdelka, ki ga že imate?',
            'bg' => 'Основната ви цел грижа за кожата, избор на витамин/минерал/протеин или проверка на продукт, който вече имате, ли е?',
        ]),
        'slot_key' => 'need_area',
        'question_id' => 'discover.need_area.v1',
        'accepted_values' => ['free_text_goal'],
    ];
}

function fcc_ai_v2_unique_question_id(string $baseQuestionId, array $askedQuestionIds): string {
    $baseQuestionId = trim($baseQuestionId) !== '' ? trim($baseQuestionId) : 'discover.primary_goal.v1';
    $asked = array_fill_keys(fcc_ai_v2_unique_strings($askedQuestionIds, 100), true);
    if(!isset($asked[$baseQuestionId])) {
        return $baseQuestionId;
    }

    for($attempt = 2; $attempt <= 100; $attempt++) {
        $candidate = $baseQuestionId . '.followup_' . $attempt;
        if(!isset($asked[$candidate])) {
            return $candidate;
        }
    }

    return $baseQuestionId . '.followup_101';
}

/** @return array{slot_key:string,question_id:string,accepted_values:array<int,string>} */
function fcc_ai_v2_clarifier_slot(
    string $reason,
    string $message,
    string $clarifyingQuestion,
    string $language,
    array $state = []
): array {
    $questionText = fcc_ai_v2_normalize_text($clarifyingQuestion);
    $reasonMap = [
        'market_required_for_fact' => ['market', 'fact.market.v1', ['HR', 'SI', 'AT', 'DE', 'IT', 'CH', 'NL', 'BE', 'BG']],
        'market_changed_requires_current_fact' => ['market_fact', 'fact.market_changed.v1', ['official_market_fact']],
        'package_contents_vary_by_market' => ['market_or_label', 'fact.package_market.v1', ['market', 'label_photo']],
        'verified_fact_unavailable' => ['label_evidence', 'fact.label_evidence.v1', ['label_photo', 'official_source']],
        'product_suitability_unconfirmed' => ['label_evidence', 'fact.suitability_label.v1', ['label_photo', 'official_marking']],
        'comparison_criterion_required' => ['comparison_criterion', 'compare.criterion.v1', ['ingredients', 'usage', 'warnings', 'price']],
        'completed_program_requires_new_goal' => ['primary_goal', 'discover.post_program_goal.v1', ['maintenance', 'energy', 'weight_control']],
        'verified_product_relationship_unavailable' => ['exact_product_pair', 'relationship.product_pair.v1', ['two_product_names', 'market', 'label_photos']],
        'explicit_language_switch' => ['next_topic', 'conversation.next_topic.v1', ['free_text']],
        'user_reported_bad_answer' => ['repair_mode', 'conversation.repair_mode.v1', ['correct_last', 'new_topic']],
        'unsupported_body_skin_goal' => ['body_area', 'discover.body_area.v1', ['hands', 'arms', 'legs', 'torso', 'other']],
        'unsupported_lip_care_lookup_only' => ['lip_fact', 'catalog.lip_fact.v1', ['official_description', 'current_label']],
        'ambiguous_skin_area' => ['skin_area', 'discover.skin_area_correction.v1', ['face', 'body', 'lips']],
        'weight_management_safety_screen' => [
            'weight_safety',
            'weight.safety.v1',
            ['adult_status', 'pregnancy_or_breastfeeding', 'medication', 'diagnosed_or_chronic_condition'],
        ],
        'weight_management_format_choice' => [
            'weight_format',
            'weight.format.v1',
            ['c9', 'f15', 'plant_protein'],
        ],
    ];
    if(isset($reasonMap[$reason])) {
        return [
            'slot_key' => $reasonMap[$reason][0],
            'question_id' => $reasonMap[$reason][1],
            'accepted_values' => $reasonMap[$reason][2],
        ];
    }

    if(str_starts_with($reason, 'health_claim_')) {
        return [
            'slot_key' => 'neutral_product_fact',
            'question_id' => 'policy.neutral_fact.v1',
            'accepted_values' => ['ingredient', 'format', 'named_product'],
        ];
    }
    if(str_contains($questionText, 'zatvor') || str_contains($questionText, 'nadutost')) {
        return [
            'slot_key' => 'digestive_concern',
            'question_id' => 'medical.digestive_concern.v1',
            'accepted_values' => ['constipation', 'bloating', 'sensitive_stomach'],
        ];
    }
    if(str_contains($questionText, 'probava') && str_contains($questionText, 'energija')) {
        return [
            'slot_key' => 'current_concern',
            'question_id' => 'discover.current_concern.v1',
            'accepted_values' => ['digestion', 'energy', 'sleep', 'stress', 'skin'],
        ];
    }
    if(str_contains($questionText, 'aloe vera gel') && str_contains($questionText, 'okus')) {
        return [
            'slot_key' => 'aloe_flavour',
            'question_id' => 'product.aloe_flavour.v1',
            'accepted_values' => ['plain', 'berry', 'mango', 'peach'],
        ];
    }

    $discovery = fcc_ai_v2_discovery_question($message, $language, $state);
    return [
        'slot_key' => (string) $discovery['slot_key'],
        'question_id' => (string) $discovery['question_id'],
        'accepted_values' => fcc_ai_v2_unique_strings($discovery['accepted_values'] ?? [], 12),
    ];
}

function fcc_ai_v2_row_entity_score(array $row, string $productKey, array $profiles): array {
    if(!$profiles) {
        /* Generic catalog word overlap is not product relevance. An unnamed
         * recommendation needs a reviewed high-signal entity/goal before any
         * product may enter the candidate set. Direct product mentions bypass
         * this gate in fcc_ai_v2_rank_candidates(). */
        return ['matched' => false, 'score' => 0, 'reason_codes' => []];
    }

    $haystack = fcc_ai_v2_normalize_text(implode(' ', array_merge(
        [$productKey, (string) ($row['canonical_name'] ?? '')],
        (array) ($row['aliases'] ?? []),
        (array) ($row['use_cases'] ?? []),
        (array) ($row['ingredient_facts'] ?? []),
        (array) ($row['approved_support_facts'] ?? [])
    )));
    $score = 0;
    $reasonCodes = [];

    foreach($profiles as $entityKey => $profile) {
        $requiredRoute = (string) ($profile['route'] ?? '');
        $rowRoute = (string) ($row['route'] ?? 'unknown');
        if($requiredRoute !== '' && $rowRoute !== $requiredRoute && $rowRoute !== 'mixed') {
            continue;
        }
        $requiredCategories = fcc_ai_v2_unique_strings($profile['categories'] ?? []);
        if($requiredCategories && !in_array((string) ($row['category_key'] ?? ''), $requiredCategories, true)) {
            continue;
        }

        $preferred = in_array($productKey, (array) ($profile['preferred_keys'] ?? []), true);
        $catalogMatch = preg_match((string) $profile['catalog'], $haystack) === 1;
        if(!$preferred && !$catalogMatch) {
            continue;
        }

        $score += $preferred ? 140 : 70;
        $reasonCodes[] = ($preferred ? 'preferred_entity:' : 'catalog_entity:') . $entityKey;
    }

    return [
        'matched' => $score > 0,
        'score' => $score,
        'reason_codes' => $reasonCodes,
    ];
}

function fcc_ai_v2_rank_candidates(string $message, string $language, array $mentions, array $state, array $catalog): array {
    $rows = fcc_ai_v2_catalog_rows($catalog);
    $tokens = fcc_ai_v2_meaningful_tokens($message, $language);
    $route_hint = fcc_ai_v2_route_hint($message);
    $entityProfiles = fcc_ai_v2_query_entity_profiles($message, $state);
    $userConstraints = fcc_ai_v2_user_product_constraints(
        $message,
        fcc_ai_v2_unique_strings($state['user_constraints'] ?? [], 30)
    );
    $mention_index = [];
    foreach($mentions as $mention) {
        $mention_index[(string) ($mention['product_key'] ?? '')] = $mention;
    }
    $rejected = array_fill_keys(fcc_ai_v2_unique_strings($state['rejected_product_keys'] ?? []), true);
    $scores = [];

    foreach($rows as $product_key => $row) {
        if(($row['advisor_status'] ?? '') === 'blocked' || ($row['availability_status'] ?? '') === 'blocked' || isset($rejected[$product_key])) {
            continue;
        }

        if(fcc_ai_v2_product_constraint_conflicts($row, $userConstraints)) {
            continue;
        }

        $direct = isset($mention_index[$product_key]) && empty($mention_index[$product_key]['negated']);
        if(!$direct && ($row['advisor_status'] ?? '') !== 'recommendable') {
            continue;
        }

        if(
            !$direct
            && $route_hint !== 'unknown'
            && ($row['route'] ?? 'unknown') !== $route_hint
            && ($row['route'] ?? 'unknown') !== 'mixed'
        ) {
            continue;
        }

        $score = $direct ? 1000 : 0;
        $reason_codes = $direct ? ['explicit_product_mention'] : [];
        $matchedUseCases = [];
        if(!$direct) {
            $entityRelevance = fcc_ai_v2_row_entity_score($row, $product_key, $entityProfiles);
            if(empty($entityRelevance['matched'])) {
                continue;
            }
            $score += (int) ($entityRelevance['score'] ?? 0);
            $reason_codes = array_merge($reason_codes, (array) ($entityRelevance['reason_codes'] ?? []));
        }
        $haystacks = array_merge($row['aliases'], $row['use_cases'], $row['ingredient_facts'], $row['approved_support_facts'], $row['usage_facts']);
        $normalized_haystack = fcc_ai_v2_normalize_text(implode(' ', $haystacks));
        $haystack_stems = [];
        foreach(fcc_ai_v2_meaningful_tokens($normalized_haystack, $language) as $haystack_token) {
            $haystack_stems[fcc_ai_v2_token_stem($haystack_token)] = true;
        }

        foreach($tokens as $token) {
            if(fcc_ai_v2_contains_phrase($normalized_haystack, $token)) {
                $score += 12;
                $reason_codes[] = 'catalog_token:' . $token;
            } else {
                $stem = fcc_ai_v2_token_stem($token);
                if(mb_strlen($stem) >= 4 && isset($haystack_stems[$stem])) {
                    $score += 10;
                    $reason_codes[] = 'catalog_stem:' . $stem;
                }
            }
        }

        foreach($row['use_cases'] as $use_case) {
            if(fcc_ai_v2_contains_phrase($message, (string) $use_case)) {
                $score += 80 + min(30, mb_strlen((string) $use_case));
                $reason_codes[] = 'exact_use_case';
                $matchedUseCases[] = (string) $use_case;
            }
        }

        if($route_hint !== 'unknown') {
            if(($row['route'] ?? 'unknown') === $route_hint || ($row['route'] ?? '') === 'mixed') {
                $score += 18;
                $reason_codes[] = 'route_match';
            }
        }

        if($score > 0 && ($row['availability_status'] ?? 'unknown') === 'available') {
            $score += 4;
        }

        if($score <= 0) {
            continue;
        }

        $scores[] = [
            'product_key' => $product_key,
            'score' => $score,
            'reason_codes' => array_values(array_unique($reason_codes)),
            'matched_use_cases' => fcc_ai_v2_unique_strings($matchedUseCases, 3),
            'direct' => $direct,
        ];
    }

    usort($scores, static function(array $left, array $right): int {
        return ($right['score'] <=> $left['score']) ?: strcmp($left['product_key'], $right['product_key']);
    });

    if(!$scores) {
        return [];
    }

    $topScore = (int) ($scores[0]['score'] ?? 0);
    $minimumScore = max(18, $topScore - ($entityProfiles ? 30 : 24));
    $scores = array_values(array_filter($scores, static function(array $candidate) use ($minimumScore): bool {
        return !empty($candidate['direct']) || (int) ($candidate['score'] ?? 0) >= $minimumScore;
    }));

    return array_slice($scores, 0, 5);
}

function fcc_ai_v2_localized_text(string $language, array $texts, array $vars=[]): string {
    $text=(string) ($texts[$language] ?? $texts['hr'] ?? $texts['en'] ?? reset($texts) ?: '');
    return strtr(fcc_new_locale($language) && !isset($texts[$language]) ? fcc_t($text,[],$language) : $text,$vars);
}

/** Presentation-only cleanup for official page text whose rendered section
 * headings were concatenated by the source app. Stored evidence and hashes are
 * never changed by this function. */
function fcc_ai_v2_display_fact_text(string $text): string {
    $text = trim($text);
    $text = (string) preg_replace(
        '/\b(UPUTA ZA UPORABU|NAČIN UPORABE|NACIN UPORABE)(?=[\p{L}\p{N}])/u',
        '$1: ',
        $text
    );
    $text = (string) preg_replace(
        '/\b(Napomena|Upozorenje|Oprez|Warning|Caution)(?=[\p{L}\p{N}])/u',
        '$1: ',
        $text
    );
    $text = (string) preg_replace('/\.(?=\p{Lu}\p{Ll})/u', '. ', $text);
    $text = trim((string) preg_replace('/[ \t]+/u', ' ', $text));
    if(preg_match('/^\p{Ll}/u', $text) === 1) {
        $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }
    return $text;
}

function fcc_ai_v2_query_focus(string $message): string {
    $text = fcc_ai_v2_normalize_text($message);
    if(fcc_ai_v2_contains_any($text, ['cijena', 'koliko kosta', 'price', 'cost'])) {
        return 'price';
    }
    if(fcc_ai_v2_contains_any($text, [
        'alkohol', 'sastojci', 'sastav', 'deklaracija', 'alergeni', 'upozorenja',
        'sadrzi li', 'ingredients', 'composition', 'label', 'allergens', 'warnings',
        'does it contain',
    ])) {
        return 'ingredients';
    }
    if(
        fcc_ai_v2_contains_any($text, [
            'tocan sadrzaj', 'tocan popis', 'sto sadrzi', 'sta sadrzi',
            'sto dolazi u', 'sta dolazi u', 'koji proizvodi dolaze',
            'sto je u paketu', 'sta je u paketu', 'sadrzaj paketa',
            'contents', 'exact list', 'what comes in', 'which products come in',
        ])
        || preg_match('/\b(?:koji|koje)\s+proizvod\p{L}*\s+(?:dolaz\p{L}*|sadrz\p{L}*)\s+u\b/u', $text) === 1
    ) {
        return 'package_contents';
    }
    if(
        fcc_ai_v2_contains_any($text, ['kako se koristi', 'kako koristiti', 'uporaba', 'doziranje', 'how to use', 'usage'])
        || preg_match('/\b(?:uporab|dozir|korist)\p{L}*\b/u', $text) === 1
    ) {
        return 'usage';
    }
    if(
        fcc_ai_v2_contains_any($text, [
            'redoslijed', 'kojim redom', 'koraci njege', 'jutarnja i vecernja',
            'jutro i vecer', 'dnevna i nocna', 'routine', 'in what order', 'day and night',
        ])
        || preg_match('/\b(?:rutin|korak)\p{L}*\b/u', $text) === 1
    ) {
        return 'routine_sequence';
    }
    if(
        fcc_ai_v2_contains_any($text, ['vegan', 'vegetarijan', 'vegetarian', 'bez glutena', 'gluten free', 'gluten-free'])
        || preg_match('/\b(?:celijak|coeliac|celiac)\p{L}*\b/u', $text) === 1
    ) {
        return 'suitability';
    }
    if(fcc_ai_v2_is_compatibility_request($text)) {
        return 'compatibility';
    }
    if(fcc_ai_v2_contains_any($text, [
        'compare', 'usporedi', 'bolji od', 'bolja od', 'bolje od', 'better than',
    ])) {
        return 'comparison';
    }

    return 'purpose';
}

function fcc_ai_v2_is_short_context_followup(string $message): bool {
    return preg_match('/^(?:da|moze|lahko|ok|preciznije|tocan\s+popis|koja?\s+(?:su\s+)?(?:dva|2)\s+glavna?|(?:dva|2)\s+glavna?|a\s+koji)\??\.?$/u', fcc_ai_v2_normalize_text($message)) === 1;
}

function fcc_ai_v2_is_medical_context_followup(string $message, array $state): bool {
    $lastSafety = is_array($state['last_safety'] ?? null) ? $state['last_safety'] : [];
    if(
        (string) ($lastSafety['level'] ?? 'routine') === 'routine'
        || empty($lastSafety['reason_codes'])
    ) {
        return false;
    }

    $text = fcc_ai_v2_normalize_text($message);
    $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if(count($tokens) > 8) {
        return false;
    }

    return preg_match(
        '/(?<![\p{L}\p{N}])(?:proizvod\p{L}*|dodatak\p{L}*|napitak\p{L}*|krema\p{L}*|serum\p{L}*|sto\s+uzeti|sta\s+uzeti|koj\p{L}*\s+proizvod\p{L}*)(?![\p{L}\p{N}])/u',
        $text
    ) === 1;
}

function fcc_ai_v2_medical_reason_from_context_key(string $contextKey): string {
    return match(trim($contextKey)) {
        'fatty_liver' => 'diagnosed_fatty_liver',
        'foot_fungal_infection' => 'suspected_foot_fungal_infection',
        'high_blood_pressure' => 'diagnosed_hypertension',
        'metabolic_condition' => 'diagnosed_metabolic_condition',
        'skin_condition' => 'diagnosed_skin_condition',
        'cardiovascular_condition' => 'diagnosed_cardiovascular_condition',
        'kidney_condition' => 'diagnosed_kidney_condition',
        'liver_condition' => 'diagnosed_liver_condition',
        'endocrine_condition' => 'diagnosed_endocrine_condition',
        'digestive_condition' => 'diagnosed_digestive_condition',
        'respiratory_condition' => 'diagnosed_respiratory_condition',
        'musculoskeletal_condition' => 'musculoskeletal_symptoms',
        'active_infection' => 'active_or_suspected_infection',
        default => '',
    };
}

/**
 * Medical context is sticky by default. Merely naming a supplement is not a
 * context switch and cannot bypass a diagnosis from an earlier turn.
 */
function fcc_ai_v2_explicit_medical_context_exit(string $message, string $intent): bool {
    if(in_array($intent, ['reset', 'complaint_reset', 'business'], true)) {
        return true;
    }

    $text = fcc_ai_v2_normalize_text($message);
    $explicitSwitch = fcc_ai_v2_contains_any($text, [
        'sada pitam za', 'sad pitam za', 'sada me zanima', 'sad me zanima',
        'promijenimo temu', 'promjena teme', 'nova tema', 'drugo pitanje',
        'nije povezano s prethodnim', 'odvojeno od prethodnog',
        'now i am asking about', 'change the subject', 'new topic', 'separate question',
    ]);
    if(!$explicitSwitch) {
        return false;
    }

    return fcc_ai_v2_route_hint($text) === 'topical'
        || preg_match('/(?<![\p{L}\p{N}])(?:suh\p{L}*\s+koz\p{L}*|njeg\p{L}*\s+(?:koz\p{L}*|lic\p{L}*|tijel\p{L}*)|lic\p{L}*|usn\p{L}*|parfem\p{L}*|miris\p{L}*|sampon\p{L}*)(?![\p{L}\p{N}])/u', $text) === 1
        || fcc_ai_v2_contains_any($text, [
            'suha koza', 'njega koze', 'njega lica', 'njega tijela', 'parfem', 'miris',
            'hidratantni serum', 'krema za ruke', 'sampon', 'lice', 'usne',
            'skin care', 'skincare', 'fragrance', 'perfume', 'dry skin',
        ]);
}

function fcc_ai_v2_is_product_context_followup(string $message, array $state): bool {
    $activeProductKeys = fcc_ai_v2_unique_strings(array_merge(
        (array) ($state['active_product_keys'] ?? []),
        (array) ($state['last_selected_product_keys'] ?? [])
    ), 3);
    if(!$activeProductKeys) {
        return false;
    }

    if(fcc_ai_v2_contains_any($message, [
        'cemu sluzi', 'za sto sluzi', 'za sta sluzi', 'kako se koristi', 'kako koristiti',
        'tocno kako', 'vise o njemu', 'vise o njoj', 'reci vise', 'sadrzi li',
        'koji su sastojci', 'koji je sastav', 'doziranje', 'uporaba', 'uputa za uporabu',
        'koliko kosta', 'koja je cijena', 'smijem li ga', 'smijem li je', 'moze li se kombinirati',
        'what is it for', 'how is it used', 'how to use it', 'tell me more about it',
        'what does it contain', 'what are the ingredients', 'what is the price',
    ])) {
        return true;
    }

    $normalized = fcc_ai_v2_normalize_text($message);
    $tokens = fcc_ai_v2_meaningful_tokens($normalized, (string) ($state['language'] ?? 'hr'));
    $hasFactualStem = preg_match('/\b(?:uporab|dozir|sastoj|cijen|korist)\p{L}*\b/u', $normalized) === 1;
    $hasReference = preg_match('/\b(?:tog|taj|ovog|ovaj|njega|njemu|ga|je|it|that)\b/u', $normalized) === 1;

    return $hasFactualStem && ($hasReference || count($tokens) <= 8);
}

function fcc_ai_v2_has_verified_focus_fact(array $row, string $focus, string $market): bool {
    $market = mb_strtoupper(trim($market), 'UTF-8');
    $marketFacts = is_array($row['market_facts'] ?? null) ? $row['market_facts'] : [];
    if($focus === 'price') {
        return $market !== ''
            && isset($marketFacts['price_by_market'])
            && is_array($marketFacts['price_by_market'])
            && is_scalar($marketFacts['price_by_market'][$market] ?? null)
            && trim((string) $marketFacts['price_by_market'][$market]) !== '';
    }
    if($focus === 'ingredients') {
        if($market !== '' && is_array($marketFacts['alcohol_statement_by_market'] ?? null) && trim((string) ($marketFacts['alcohol_statement_by_market'][$market] ?? '')) !== '') {
            return true;
        }
        return !empty($row['ingredient_facts']);
    }
    if($focus === 'package_contents') {
        return !empty($row['package_content_facts']);
    }

    return true;
}

function fcc_ai_v2_plan_turn(array $input, array $state, array $catalog): array {
    $message = trim((string) ($input['message'] ?? ''));
    $state = is_array($state) ? $state : [];
    $weightManagementStage = trim((string) ($state['weight_management_stage'] ?? ''));
    if(
        $weightManagementStage === ''
        && (string) ($state['pending_reason'] ?? '') === 'health_claim_weight_loss'
    ) {
        /* Backward-compatible recovery for conversations started before the
         * explicit multi-turn stage was introduced. */
        $weightManagementStage = 'safety_screen';
    }
    $weightManagementScreenComplete = $weightManagementStage === 'safety_screen'
        && fcc_ai_v2_weight_management_screen_complete($message);
    $weightManagementPreference = $weightManagementStage === 'format_choice'
        ? fcc_ai_v2_weight_management_preference($message)
        : '';
    $previousMedicalContextKey = trim((string) ($state['medical_context_key'] ?? ''));
    $previousMedicalContextReason = trim((string) ($state['medical_context_reason_code'] ?? ''));
    if($previousMedicalContextReason === '' && $previousMedicalContextKey !== '') {
        $previousMedicalContextReason = fcc_ai_v2_medical_reason_from_context_key($previousMedicalContextKey);
    }
    $skinAreaScope = fcc_ai_v2_skin_area_scope($message, $state);
    $previous_language = (string) ($state['language'] ?? $input['language'] ?? 'hr');
    $language = fcc_ai_v2_detect_language($message, $previous_language);
    $previousMarket = mb_strtoupper(trim((string) ($state['market'] ?? $input['market'] ?? '')), 'UTF-8');
    $explicitMarket = fcc_ai_v2_detect_market($message, '');
    $market = $explicitMarket !== '' ? $explicitMarket : $previousMarket;
    $marketSource = $explicitMarket !== ''
        ? 'message'
        : (string) ($state['market_source'] ?? ($previousMarket !== '' ? 'server_request' : 'unknown'));
    $marketChanged = $previousMarket !== '' && $market !== '' && $market !== $previousMarket;
    $rows = fcc_ai_v2_catalog_rows($catalog);
    $mentions = fcc_ai_v2_product_mentions($message, $rows);
    if($weightManagementStage === 'format_choice' && $weightManagementPreference !== '') {
        $preferenceProductKey = fcc_ai_v2_weight_management_product_key($weightManagementPreference, $rows);
        $alreadyMentioned = (bool) array_filter(
            $mentions,
            static fn(array $mention): bool => (string) ($mention['product_key'] ?? '') === $preferenceProductKey
                && empty($mention['negated'])
        );
        if($preferenceProductKey !== '' && isset($rows[$preferenceProductKey]) && !$alreadyMentioned) {
            $mentions[] = [
                'product_key' => $preferenceProductKey,
                'alias' => (string) ($rows[$preferenceProductKey]['canonical_name'] ?? $preferenceProductKey),
                'negated' => false,
            ];
        }
    }
    if(
        !$mentions
        && (string) ($state['pending_reason'] ?? '') === 'low_or_ambiguous_relevance'
        && fcc_ai_v2_contains_phrase((string) ($state['pending_question'] ?? ''), 'Aloe Vera Gel')
    ) {
        $preferenceMap = [
            'forever-aloe-vera-gel' => ['neutralni', 'bez okusa', 'plain', 'original'],
            'aloe-berry-nectar' => ['bobičasto', 'bobicasto', 'bobice', 'berry'],
            'forever-aloe-mango' => ['mango'],
            'forever-aloe-peaches' => ['breskva', 'breskve', 'peach'],
        ];
        foreach($preferenceMap as $productKey => $signals) {
            if(isset($rows[$productKey]) && fcc_ai_v2_contains_any($message, $signals)) {
                $mentions[] = ['product_key' => $productKey, 'alias' => (string) ($signals[0] ?? ''), 'negated' => false];
                break;
            }
        }
    }
    $queryFocus = fcc_ai_v2_query_focus($message);
    if(
        fcc_ai_v2_is_short_context_followup($message)
        && in_array((string) ($state['pending_reason'] ?? ''), ['market_required_for_fact', 'package_contents_vary_by_market'], true)
        && !empty($state['pending_query_focus'])
    ) {
        $queryFocus = (string) $state['pending_query_focus'];
    }

    $rejected = fcc_ai_v2_unique_strings($state['rejected_product_keys'] ?? []);
    $completed = fcc_ai_v2_unique_strings($state['completed_product_keys'] ?? []);
    $post_program_request = fcc_ai_v2_contains_any($message, [
        'sto poslije', 'sta poslije', 'sto nakon', 'sta nakon', 'nakon programa',
        'poslije programa', 'what after', 'what next after', 'after finishing',
    ]);
    foreach($mentions as $mention) {
        $key = (string) ($mention['product_key'] ?? '');
        if($key === '') {
            continue;
        }
        if(!empty($mention['negated'])) {
            $rejected[] = $key;
        }
        if(
            empty($mention['negated'])
            && preg_match('/\b(?:zavrsil|odradil|radil|completed|finished)\p{L}*\b/u', fcc_ai_v2_normalize_text($message)) === 1
        ) {
            $completed[] = $key;
        }
        if(empty($mention['negated']) && $post_program_request && (($rows[$key]['route'] ?? '') === 'program')) {
            $completed[] = $key;
        }
    }
    $rejected = fcc_ai_v2_unique_strings($rejected);
    $completed = fcc_ai_v2_unique_strings($completed);

    $positive_mentions = array_values(array_filter($mentions, static fn(array $mention): bool => empty($mention['negated'])));
    $intent = fcc_ai_v2_detect_intent($message, $mentions, $state);
    $businessProfile = fcc_ai_v2_business_intent_profile($message, $state);
    $hasNegatedProductMention = count($positive_mentions) < count($mentions);
    $explicitProductPreferenceContrast = $positive_mentions
        && $hasNegatedProductMention
        && preg_match(
            '/(?<![\p{L}\p{N}])(?:ne\s+zelim|necu|nemoj|do\s+not\s+want|don\'t\s+want).{0,100}(?:[,;]|\bnego\b|\bali\b|\binstead\b|\brather\b).{0,100}(?:zelim|hocu|biram|nego|instead|rather)(?![\p{L}\p{N}])/u',
            fcc_ai_v2_normalize_clause_text($message)
        ) === 1;
    if($explicitProductPreferenceContrast && $intent === 'direct_product') {
        $intent = 'recommendation';
    }
    if($intent === 'language_switch' && $positive_mentions) {
        $intent = in_array($queryFocus, ['usage', 'ingredients', 'package_contents', 'price'], true)
            ? 'product_question'
            : 'direct_product';
    }
    if($intent === 'followup_confirmation' && (string) ($state['pending_reason'] ?? '') === 'explicit_language_switch') {
        $intent = 'small_talk';
    }

    $contentTopic = (string) ($state['active_flow'] ?? '') === 'content_creation'
        ? (string) ($state['content_topic'] ?? '')
        : '';
    if(fcc_ai_v2_contains_any($message, ['fcc sustav', 'fcc preporuku', 'drustvenim mrezama', 'social media'])) {
        $contentTopic = 'fcc_social';
    } elseif(
        fcc_ai_v2_contains_any($message, ['sezonskim alergijama', 'sezonske alergije', 'seasonal allergies'])
        || preg_match('/\bsezonsk\p{L}*\s+alerg\p{L}*\b/u', fcc_ai_v2_normalize_text($message)) === 1
    ) {
        $contentTopic = 'seasonal_allergy_education';
    }
    $contentFormat = 'guidance';
    if(fcc_ai_v2_contains_any($message, ['whatsapp poruku', 'napravi whatsapp', 'whatsapp message'])) {
        $contentFormat = 'whatsapp';
    } elseif(fcc_ai_v2_contains_any($message, ['story', 'story predlozak'])) {
        $contentFormat = 'story';
    } elseif(
        $intent === 'content_creation'
        && (string) ($state['active_flow'] ?? '') === 'content_creation'
        && fcc_ai_v2_contains_any($message, ['da', 'moze', 'lahko', 'yes', 'sure'])
        && $contentTopic === 'fcc_social'
    ) {
        $contentFormat = 'story';
    }
    if($intent === 'market_change' && !empty($state['last_query_focus'])) {
        $queryFocus = (string) $state['last_query_focus'];
    }
    if(!$positive_mentions && $mentions) {
        $intent = 'recommendation';
    }
    if($post_program_request) {
        $intent = 'recommendation';
    }
    $productContextFollowup = !$positive_mentions && fcc_ai_v2_is_product_context_followup($message, $state);
    if($productContextFollowup && in_array($intent, ['advice', 'recommendation', 'followup_confirmation'], true)) {
        $intent = 'product_question';
    }
    if(
        $productContextFollowup
        && (string) ($state['pending_reason'] ?? '') === 'comparison_criterion_required'
        && in_array($queryFocus, ['ingredients', 'usage', 'price', 'suitability'], true)
    ) {
        $intent = 'comparison';
    }

    /* "Sto sadrzi proizvod?" normally asks for its ingredients. Treat it as
     * package contents only for an actual program/kit (or when the user says
     * package/kit explicitly); otherwise single-product label questions were
     * incorrectly routed to a package clarifier. */
    if($queryFocus === 'package_contents') {
        $focusProductKeys = array_values(array_filter(array_map(
            static fn(array $mention): string => (string) ($mention['product_key'] ?? ''),
            $positive_mentions
        )));
        if(!$focusProductKeys && $productContextFollowup) {
            $focusProductKeys = fcc_ai_v2_unique_strings($state['active_product_keys'] ?? [], 3);
        }
        $explicitPackageRequest = fcc_ai_v2_contains_any($message, [
            'sadrzaj paketa', 'sadrzaj kita', 'sto je u paketu', 'sta je u paketu',
            'paket sadrzi', 'kit sadrzi', 'program sadrzi', 'package contents',
            'inside the kit', 'inside the package', 'koji proizvodi dolaze',
        ]) || preg_match('/\b(?:paket|kit|set|program)\p{L}*\b/u', fcc_ai_v2_normalize_text($message)) === 1;
        if(
            !$explicitPackageRequest
            && isset($focusProductKeys[0], $rows[$focusProductKeys[0]])
            && (string) ($rows[$focusProductKeys[0]]['route'] ?? '') !== 'program'
        ) {
            $queryFocus = 'ingredients';
        }
    }
    $context_operation = 'retain';
    if($intent === 'reset') {
        $context_operation = 'reset';
        $state = [];
        $rejected = [];
        $completed = [];
        $mentions = fcc_ai_v2_product_mentions($message, $rows);
        foreach($mentions as $mention) {
            if(!empty($mention['negated'])) {
                $rejected[] = (string) $mention['product_key'];
            }
        }
        $rejected = fcc_ai_v2_unique_strings($rejected);
        $intent = 'complaint_reset';
    } elseif(
        $marketChanged
        || $intent === 'market_change'
        || (!$positive_mentions && $mentions)
        || fcc_ai_v2_contains_any($message, ['nije probava nego', 'ne nije probava nego', 'a sada me zanima'])
        || (!empty($state['last_intent']) && $intent !== $state['last_intent'] && in_array($intent, ['business', 'recommendation'], true))
        || (
            (string) ($state['last_intent'] ?? '') === 'business'
            && in_array($intent, ['direct_product', 'product_question', 'comparison', 'advice'], true)
        )
    ) {
        $context_operation = 'replace';
    }
    $explicitMedicalContextExit = fcc_ai_v2_explicit_medical_context_exit($message, $intent);
    $explicitWeightFlowExit = in_array($intent, ['reset', 'complaint_reset', 'business', 'content_creation'], true)
        || fcc_ai_v2_contains_any($message, [
            'promijenimo temu', 'promjena teme', 'nova tema', 'drugo pitanje',
            'nije povezano s prethodnim', 'odvojeno od prethodnog',
            'change the subject', 'new topic', 'separate question',
        ]);
    if($explicitWeightFlowExit) {
        $weightManagementStage = '';
        $weightManagementScreenComplete = false;
        $weightManagementPreference = '';
    }

    $userConstraints = fcc_ai_v2_user_product_constraints(
        $message,
        fcc_ai_v2_unique_strings($state['user_constraints'] ?? [], 30)
    );
    $directProductConflicts = [];
    foreach($positive_mentions as $mention) {
        $productKey = (string) ($mention['product_key'] ?? '');
        if($productKey === '' || !isset($rows[$productKey])) {
            continue;
        }
        foreach(fcc_ai_v2_product_constraint_conflicts($rows[$productKey], $userConstraints) as $conflict) {
            $conflict['product_key'] = $productKey;
            $directProductConflicts[] = $conflict;
        }
    }

    $safety = fcc_ai_v2_safety_assessment($message);
    if(
        (string) ($safety['level'] ?? '') === 'routine'
        && fcc_ai_v2_requires_oral_combination_review($message, $positive_mentions, $rows)
    ) {
        $safety = [
            'level' => 'sensitive',
            'allow_products' => false,
            'reason_codes' => ['supplement_combination'],
        ];
    }
    if(
        $safety['level'] === 'routine'
        && !($weightManagementStage === 'safety_screen' && $weightManagementScreenComplete)
        && (
            fcc_ai_v2_is_short_context_followup($message)
            || fcc_ai_v2_is_medical_context_followup($message, $state)
            || (string) ($state['active_flow'] ?? '') === 'content_creation'
        )
        && !empty($state['last_safety']['level'])
        && (string) $state['last_safety']['level'] !== 'routine'
    ) {
        $safety = is_array($state['last_safety']) ? $state['last_safety'] : $safety;
        $safety['allow_products'] = false;
        $safety['reason_codes'] = fcc_ai_v2_unique_strings(array_merge(
            (array) ($safety['reason_codes'] ?? []),
            ['medical_followup_context']
        ));
    }

    if(
        (string) ($safety['level'] ?? '') === 'routine'
        && $weightManagementStage === 'format_choice'
        && $weightManagementPreference !== ''
    ) {
        /* Keep the selected option in a non-commercial, facts-only lane. It
         * may be described from the reviewed label, but it must not inherit a
         * claim that the product itself causes weight loss. */
        $safety = [
            'level' => 'sensitive',
            'allow_products' => true,
            'reason_codes' => ['health_claim_weight_loss'],
        ];
    }

    $medicalNeed = fcc_ai_v2_medical_need_profile($message);
    $queryEntityProfiles = fcc_ai_v2_query_entity_profiles($message, $state);
    $currentMedicalContextKey = trim((string) ($medicalNeed['key'] ?? ''));
    $currentMedicalContextReason = trim((string) ($medicalNeed['reason_code'] ?? ''));
    $effectiveMedicalContextKey = $currentMedicalContextKey !== ''
        ? $currentMedicalContextKey
        : ($explicitMedicalContextExit ? '' : $previousMedicalContextKey);
    $effectiveMedicalContextReason = $currentMedicalContextReason !== ''
        ? $currentMedicalContextReason
        : ($effectiveMedicalContextKey !== '' ? $previousMedicalContextReason : '');
    if($effectiveMedicalContextReason === '' && $effectiveMedicalContextKey !== '') {
        $effectiveMedicalContextReason = fcc_ai_v2_medical_reason_from_context_key($effectiveMedicalContextKey);
    }

    $factOnlyFocus = in_array($queryFocus, ['ingredients', 'package_contents', 'price'], true);
    $selectsOrUsesProduct = !empty($positive_mentions)
        || in_array($intent, ['recommendation', 'direct_product', 'product_question', 'comparison'], true)
        || (bool) $queryEntityProfiles;
    $medicalContinuationRequest = $selectsOrUsesProduct
        || fcc_ai_v2_contains_any(fcc_ai_v2_normalize_text($message), [
            'sto bi mi pomoglo', 'sta bi mi pomoglo', 'sto onda', 'sta onda',
            'sto preporucujete', 'sta preporucujete', 'a moze neki',
            'sto da uzmem', 'sta da uzmem', 'what would help', 'what do you recommend',
        ])
        || (
            $effectiveMedicalContextKey !== ''
            && trim($message) !== ''
            && !in_array($intent, [
                'small_talk', 'business', 'content_creation', 'language_switch',
                'market_change', 'complaint_reset',
            ], true)
        );
    $initialSafetyReason = (string) ($safety['reason_codes'][0] ?? '');
    $neutralProductFactMode = str_starts_with($initialSafetyReason, 'health_claim_')
        && $intent !== 'content_creation'
        && (string) ($medicalNeed['key'] ?? '') === ''
        && $effectiveMedicalContextKey === ''
        && ($positive_mentions || $queryEntityProfiles);
    if($neutralProductFactMode) {
        /* A clear product or ingredient class may still be answered from its
         * label without claiming the requested medical outcome. This keeps
         * useful queries such as "vitamin C za imunitet" grounded while the
         * finalizer explicitly disables every commercial CTA for this lane. */
        $safety['allow_products'] = true;
    }

    $medicalContextFlags = fcc_ai_v2_medical_context_flags(
        $message,
        fcc_ai_v2_unique_strings($state['medical_context_flags'] ?? [], 12),
        $safety
    );
    if(
        (string) ($safety['level'] ?? '') === 'routine'
        && $effectiveMedicalContextKey !== ''
        && $medicalContinuationRequest
    ) {
        $safety = [
            'level' => 'medication_review',
            'allow_products' => false,
            'reason_codes' => fcc_ai_v2_unique_strings(array_values(array_filter([
                $effectiveMedicalContextReason,
                'persisted_medical_context',
            ]))),
        ];
    }
    if(
        (string) ($safety['level'] ?? '') === 'routine'
        && $medicalContextFlags
        && $selectsOrUsesProduct
    ) {
        $safety = [
            'level' => 'medication_review',
            'allow_products' => false,
            'reason_codes' => ['persisted_medical_context'],
        ];
    }

    if($safety['level'] !== 'routine' && $intent !== 'content_creation') {
        $context_operation = 'reset';
    }

    $goalProfile = fcc_ai_v2_goal_profile($message, $state);
    $detectedGoalKeys = fcc_ai_v2_unique_strings($goalProfile['goal_keys'] ?? [], 8);
    $goalKeys = $detectedGoalKeys ?: fcc_ai_v2_unique_strings($state['goal_keys'] ?? [], 8);
    if($intent === 'business') {
        /* Product goals never bleed into the separate business conversation. */
        $goalKeys = [];
    } elseif((string) ($medicalNeed['key'] ?? '') !== '') {
        /* A diagnosis or active infection is context for care routing, never a
         * recommendation goal that can rank a product. */
        $goalKeys = [];
    } elseif($detectedGoalKeys && $context_operation === 'replace') {
        $goalKeys = $detectedGoalKeys;
    }

    /* A safety block is not a user rejection. Persist only explicit negations. */

    $contextMentions = [];
    if(!$positive_mentions && ($productContextFollowup || fcc_ai_v2_is_short_context_followup($message) || $intent === 'market_change')) {
        foreach(fcc_ai_v2_unique_strings($state['active_product_keys'] ?? [], 3) as $productKey) {
            if(isset($rows[$productKey])) {
                $contextMentions[] = ['product_key' => $productKey, 'alias' => '', 'negated' => false];
            }
        }
    }
    $mentionsForRanking = $positive_mentions ?: $contextMentions;

    $candidate_state = $state;
    $candidate_state['goal_keys'] = $goalKeys;
    $candidate_state['user_constraints'] = $userConstraints;
    $candidate_state['rejected_product_keys'] = fcc_ai_v2_unique_strings(array_merge($rejected, $completed));
    $candidate_state['completed_product_keys'] = $completed;
    if($post_program_request) {
        $candidate_state['rejected_product_keys'] = fcc_ai_v2_unique_strings(array_merge($rejected, $completed));
    }
    $candidates = $safety['allow_products'] ? fcc_ai_v2_rank_candidates($message, $language, $mentionsForRanking, $candidate_state, $rows) : [];
    /* A body/lip correction must never inherit or lexically rank a facial
     * product. Named-product lookups remain possible, but an unnamed need
     * stays in a relevant discovery flow until a reviewed profile exists. */
    if(!$positive_mentions && in_array($skinAreaScope, ['body', 'lips', 'mixed'], true)) {
        $candidates = [];
    }
    if(in_array($intent, ['direct_product', 'product_question', 'comparison'], true)) {
        $candidates = array_values(array_filter($candidates, static function(array $candidate): bool {
            return !empty($candidate['direct']);
        }));
    }
    foreach($candidates as &$candidate) {
        $candidate['candidate_role'] = 'primary';
    }
    unset($candidate);
    $primaryCandidates = $candidates;
    $allowed_keys = array_values(array_map(static fn(array $candidate): string => (string) $candidate['product_key'], $candidates));
    $primaryAllowedKeys = $allowed_keys;
    $supportRelationships = [];

    $action = 'answer';
    $confidence = 0.75;
    $decision_reasons = [];
    $clarifying_question = '';

    $safetyBlocksProducts = in_array((string) $safety['level'], ['emergency', 'urgent', 'medication_review'], true)
        || ((string) $safety['level'] === 'sensitive' && empty($safety['allow_products']));
    $primarySafetyReason = (string) ($safety['reason_codes'][0] ?? '');
    $contentOnlySafety = $intent === 'content_creation'
        && (string) $safety['level'] === 'sensitive'
        && strpos($primarySafetyReason, 'health_claim_') !== 0;
    $needsMarket = $market === '' && in_array($queryFocus, ['price', 'ingredients', 'package_contents'], true) && ($positive_mentions || $contextMentions);
    $subjectKeys = fcc_ai_v2_unique_strings(array_merge(
        array_map(static fn(array $mention): string => (string) ($mention['product_key'] ?? ''), $positive_mentions),
        array_map(static fn(array $mention): string => (string) ($mention['product_key'] ?? ''), $contextMentions)
    ), 3);
    $subjectName = isset($subjectKeys[0], $rows[$subjectKeys[0]]) ? (string) $rows[$subjectKeys[0]]['canonical_name'] : '';
    $programMemberRelationships = $queryFocus === 'package_contents'
        ? fcc_ai_v2_program_members_for_plan($subjectKeys, $rows)
        : [];
    $missingVerifiedFocusFact = $market !== ''
        && isset($subjectKeys[0], $rows[$subjectKeys[0]])
        && in_array($queryFocus, ['price', 'ingredients', 'package_contents'], true)
        && !($queryFocus === 'package_contents' && $programMemberRelationships)
        && !fcc_ai_v2_has_verified_focus_fact($rows[$subjectKeys[0]], $queryFocus, $market);

    $neutralPolicyBoundary = in_array($primarySafetyReason, [
        'health_claim_immunity',
        'health_claim_weight_loss',
        'health_claim_hormonal',
        'health_claim_circulation',
        'health_claim_sleep_stress',
    ], true) || (
        $primarySafetyReason === 'health_claim_digestive'
        && !fcc_ai_v2_has_affirmed_regex(
            fcc_ai_v2_normalize_text($message),
            '/(?<![\p{L}\p{N}])(?:zatvor\p{L}*|nadut\p{L}*|proljev\p{L}*|povrac\p{L}*|osjetljiv\p{L}*\s+zeludac|constipation|bloating|diarrh\p{L}*|vomit\p{L}*)(?![\p{L}\p{N}])/u'
        )
    );

    if(in_array((string) $safety['level'], ['emergency', 'urgent'], true)) {
        $action = 'urgent_triage';
        $confidence = 1.0;
        $decision_reasons = $safety['reason_codes'];
    } elseif(
        $weightManagementStage !== ''
        && $safetyBlocksProducts
        && $primarySafetyReason !== 'health_claim_weight_loss'
    ) {
        /* A disclosed diagnosis, medication, pregnancy or other safety issue
         * always closes the programme-selection lane. */
        $action = 'doctor_first';
        $confidence = 1.0;
        $decision_reasons = $safety['reason_codes'];
    } elseif($weightManagementStage === 'safety_screen' && $weightManagementScreenComplete) {
        $action = 'clarify';
        $confidence = 1.0;
        $decision_reasons = ['weight_management_format_choice'];
        $clarifying_question = fcc_ai_v2_weight_management_format_question($language);
    } elseif($weightManagementStage === 'safety_screen') {
        $action = 'clarify';
        $confidence = 1.0;
        $decision_reasons = ['weight_management_safety_screen'];
        $clarifying_question = fcc_ai_v2_weight_management_screen_question($language);
    } elseif($weightManagementStage === 'format_choice' && $weightManagementPreference === '') {
        $action = 'clarify';
        $confidence = 1.0;
        $decision_reasons = ['weight_management_format_choice'];
        $clarifying_question = fcc_ai_v2_weight_management_format_question($language);
    } elseif($neutralProductFactMode && $candidates && !$directProductConflicts) {
        $action = $positive_mentions ? 'factual_lookup' : 'answer';
        $confidence = 0.98;
        $decision_reasons = ['neutral_product_fact', $primarySafetyReason];
    } elseif($neutralPolicyBoundary) {
        $action = 'clarify';
        $confidence = 1.0;
        $decision_reasons = $safety['reason_codes'];
        $clarifying_question = fcc_ai_v2_claim_boundary_clarifier($primarySafetyReason, $language);
    } elseif($safetyBlocksProducts && !$contentOnlySafety) {
        $action = 'doctor_first';
        $confidence = 1.0;
        $decision_reasons = $safety['reason_codes'];
    } elseif($directProductConflicts) {
        $unconfirmedConflictTypes = [
            'allergen_absence_unconfirmed',
            'safety_absence_unconfirmed',
            'age_suitability_unconfirmed',
            'suitability_unconfirmed',
            'safety_review_unknown',
        ];
        $conflictTypes = fcc_ai_v2_unique_strings(array_map(
            static fn(array $conflict): string => (string) ($conflict['conflict_type'] ?? ''),
            array_values(array_filter($directProductConflicts, 'is_array'))
        ));
        $onlyUnconfirmedConstraints = $conflictTypes
            && !array_diff($conflictTypes, $unconfirmedConflictTypes);

        if($queryFocus === 'suitability' && $onlyUnconfirmedConstraints) {
            /* Unknown is not a negative fact and must never be rendered as a
             * positive suitability claim. Ask for the current label instead
             * of silently converting the request into a product lookup. */
            $action = 'clarify';
            $confidence = 1.0;
            $decision_reasons = ['product_suitability_unconfirmed'];
            $clarifying_question = fcc_ai_v2_localized_text($language, [
                'hr' => 'Službeni zapis koji imam ne potvrđuje traženu prehrambenu prikladnost. Možete li poslati fotografiju aktualne deklaracije ili službene oznake s pakiranja?',
                'en' => 'The official record I have does not confirm the requested dietary suitability. Can you share a photo of the current label or the official package marking?',
                'sl' => 'Uradni zapis, ki ga imam, ne potrjuje zahtevane prehranske primernosti. Lahko pošljete fotografijo trenutne deklaracije ali uradne oznake na embalaži?',
                'bg' => 'Официалният запис, с който разполагам, не потвърждава исканата хранителна пригодност. Можете ли да изпратите снимка на актуалния етикет или официалната маркировка върху опаковката?',
            ]);
        } else {
            $action = 'factual_lookup';
            $confidence = 1.0;
            $decision_reasons = ['product_constraint_conflict'];
        }
    } elseif($intent === 'content_creation') {
        $action = 'content_creation';
        $confidence = 0.95;
        $decision_reasons = $safety['level'] === 'routine' ? [] : ['content_only_no_product'];
    } elseif($intent === 'small_talk') {
        $action = 'small_talk';
        $confidence = 1.0;
    } elseif($intent === 'business') {
        $action = 'business_guidance';
        $confidence = 0.95;
        $decision_reasons = [(string) ($businessProfile['reason_code'] ?? '') ?: 'business_scope'];
    } elseif($intent === 'language_switch') {
        $action = 'clarify';
        $confidence = 1.0;
        $decision_reasons = ['explicit_language_switch'];
        $clarifying_question = fcc_ai_v2_localized_text($language, [
            'hr' => 'Od sada odgovaram hrvatski. Što želite provjeriti kao sljedeći korak?',
            'en' => 'I will answer in English from now on. What would you like to check next?',
            'sl' => 'Od zdaj odgovarjam slovensko. Kaj želite preveriti kot naslednji korak?',
            'bg' => 'Отсега ще отговарям на български. Какво искате да проверим следващо?',
        ]);
    } elseif($intent === 'complaint_reset') {
        $action = 'clarify';
        $confidence = 1.0;
        $decision_reasons = ['user_reported_bad_answer'];
        $clarifying_question = fcc_ai_v2_localized_text($language, [
            'hr' => 'Žao mi je što odgovor nije bio koristan. Krenimo ispočetka: želite li ispravak zadnjeg odgovora ili potpuno novu temu?',
            'en' => 'I am sorry the answer was not useful. Shall we correct the last answer or start a completely new topic?',
            'sl' => 'Žal mi je, da odgovor ni bil koristen. Začniva znova: želite popravek zadnjega odgovora ali povsem novo temo?',
            'bg' => 'Съжалявам, че отговорът не беше полезен. Да поправим последния отговор или да започнем нова тема?',
        ]);
    } elseif($intent === 'market_change') {
        $action = 'clarify';
        $confidence = 0.95;
        $decision_reasons = ['market_changed_requires_current_fact'];
        $marketName = $market === 'SI' ? 'Sloveniju' : $market;
        if($queryFocus === 'price') {
            $clarifying_question = $market === 'SI'
                ? 'Razumijem, tržište mijenjam na Sloveniju. Za slovensku aktualnu cijenu trebam službeni SI podatak; hrvatsku cijenu neću primijeniti.'
                : 'Razumijem, tržište mijenjam na ' . $marketName . '. Za aktualnu cijenu koristit ću samo službeni podatak tog tržišta; prethodnu cijenu neću primijeniti.';
        } else {
            $clarifying_question = 'Tržište je promijenjeno na ' . $market . '. Za aktualan podatak koristit ću samo službeni zapis tog tržišta.';
        }
    } elseif($needsMarket) {
        $action = 'clarify';
        $confidence = 0.95;
        $decision_reasons = ['market_required_for_fact'];
        if($queryFocus === 'price') {
            $clarifying_question = 'Cijena ovisi o tržištu. Za koju državu trebate aktualnu službenu cijenu' . ($subjectName !== '' ? ' za ' . $subjectName : '') . '?';
        } elseif($queryFocus === 'package_contents') {
            $turnCount = (int) ($state['turn_count'] ?? 0);
            if($turnCount >= 2) {
                $clarifying_question = 'Točan popis ' . ($subjectName !== '' ? $subjectName . ' ' : '') . 'ne treba nagađati: pošaljite tržište ili fotografiju deklaracije paketa.';
            } elseif($turnCount >= 1) {
                $clarifying_question = 'Za precizan popis ' . ($subjectName !== '' ? $subjectName . ' ' : '') . 'i dalje trebam tržište ili fotografiju sadržaja paketa.';
            } else {
                $clarifying_question = 'Točan sadržaj ' . ($subjectName !== '' ? $subjectName . ' ' : '') . 'paketa ovisi o tržištu i verziji. Koja je država ili možete poslati fotografiju deklaracije?';
            }
        } else {
            $clarifying_question = 'Za ' . ($subjectName !== '' ? $subjectName : 'taj proizvod') . ' neću nagađati bez tržišta ili verzije pakiranja. Koja je država ili možete poslati fotografiju deklaracije?';
        }
    } elseif($missingVerifiedFocusFact) {
        $action = 'clarify';
        $confidence = 0.98;
        $decision_reasons = ['verified_fact_unavailable'];
        if($queryFocus === 'price') {
            $clarifying_question = 'Za ' . ($subjectName !== '' ? $subjectName : 'taj proizvod') . ' nemam objavljen provjeren aktualan iznos za tržište ' . $market . '. Provjerite službenu ponudu tog tržišta; cijenu neću nagađati.';
        } elseif($queryFocus === 'package_contents') {
            $clarifying_question = 'Za točan sadržaj ' . ($subjectName !== '' ? $subjectName . ' ' : '') . 'trebam službeni popis tog tržišta ili fotografiju deklaracije paketa.';
        } else {
            $clarifying_question = 'Za taj sastojak nemam provjerenu tvrdnju za verziju ' . $market . '. Pošaljite fotografiju deklaracije pakiranja; odgovor neću nagađati.';
        }
    } elseif($completed && array_intersect($completed, $subjectKeys)) {
        $action = 'clarify';
        $confidence = 0.95;
        $decision_reasons = ['completed_program_requires_new_goal'];
        $clarifying_question = 'Razumijem, taj program je već završen. Koji je sada glavni cilj: održavanje, energija ili daljnja kontrola težine?';
    } elseif($intent === 'comparison' && count($subjectKeys) >= 2 && $queryFocus === 'comparison') {
        $action = 'clarify';
        $confidence = 1.0;
        $decision_reasons = ['comparison_criterion_required'];
        $firstName = (string) ($rows[$subjectKeys[0]]['canonical_name'] ?? $subjectKeys[0]);
        $secondName = (string) ($rows[$subjectKeys[1]]['canonical_name'] ?? $subjectKeys[1]);
        $clarifying_question = fcc_ai_v2_localized_text($language, [
            'hr' => 'Ne mogu proglasiti ' . $firstName . ' ili ' . $secondName . ' općenito boljim. Po kojem ih kriteriju želite usporediti: sastav, način uporabe, upozorenja ili cijena? Ako podatak nije službeno provjeren, to ću jasno navesti.',
            'en' => 'I cannot call ' . $firstName . ' or ' . $secondName . ' universally better. Which criterion should I compare: composition, directions, warnings, or price? I will clearly flag anything not verified by an official source.',
            'sl' => 'Izdelka ' . $firstName . ' ali ' . $secondName . ' ne morem na splošno razglasiti za boljšega. Po katerem merilu ju želite primerjati: sestava, uporaba, opozorila ali cena? Jasno bom označil vse, kar ni uradno preverjeno.',
            'bg' => 'Не мога да определя ' . $firstName . ' или ' . $secondName . ' като универсално по-добър. По какъв критерий да ги сравня: състав, употреба, предупреждения или цена? Ще отбележа ясно всичко, което не е официално потвърдено.',
        ]);
    } elseif($queryFocus === 'package_contents' && $subjectName !== '' && fcc_ai_v2_contains_phrase($subjectName, 'f15')) {
        $action = 'clarify';
        $confidence = 0.95;
        $decision_reasons = ['package_contents_vary_by_market'];
        $clarifying_question = 'Točan sadržaj ' . $subjectName . ' paketa ovisi o tržištu i verziji. Pošaljite državu ili fotografiju deklaracije paketa.';
    } elseif(in_array($intent, ['direct_product', 'product_question', 'comparison'], true) && ($positive_mentions || $contextMentions)) {
        $action = $intent === 'comparison' ? 'answer' : 'factual_lookup';
        $confidence = 0.98;
        $decision_reasons = ['explicit_product_context'];
    } elseif(!$positive_mentions && $skinAreaScope === 'body') {
        $action = 'clarify';
        $confidence = 0.98;
        $decision_reasons = ['unsupported_body_skin_goal'];
        $bodyHydration = fcc_ai_v2_has_affirmed_regex(
            fcc_ai_v2_normalize_text($message),
            '/(?<![\p{L}\p{N}])(?:hidratacij\p{L}*|vlazen\p{L}*|suh\p{L}*|dehidriran\p{L}*|hydration|dry|dehydrated)(?![\p{L}\p{N}])/u'
        );
        $clarifying_question = $bodyHydration
            ? fcc_ai_v2_localized_text($language, [
                'hr' => 'Razumijem da je fokus hidratacija kože tijela, ne lica. Koje područje tijela želite njegovati?',
                'en' => 'I understand that the focus is body-skin hydration, not facial care. Which body area would you like to care for?',
                'sl' => 'Razumem, da je poudarek na vlaženju kože telesa, ne obraza. Kateri del telesa želite negovati?',
                'bg' => 'Разбирам, че фокусът е хидратация на кожата на тялото, не на лицето. За коя област на тялото търсите грижа?',
            ])
            : fcc_ai_v2_localized_text($language, [
                'hr' => 'Razumijem da je fokus koža tijela, ne lica. Je li glavni cilj hidratacija, čišćenje ili njega određenog područja?',
                'en' => 'I understand that the focus is body skin, not facial care. Is the main goal hydration, cleansing, or care for a specific area?',
                'sl' => 'Razumem, da je poudarek na koži telesa, ne obraza. Je glavni cilj vlaženje, čiščenje ali nega določenega predela?',
                'bg' => 'Разбирам, че фокусът е кожата на тялото, не на лицето. Основната цел хидратация, почистване или грижа за конкретна област ли е?',
            ]);
    } elseif(!$positive_mentions && $skinAreaScope === 'lips') {
        $action = 'clarify';
        $confidence = 0.98;
        $decision_reasons = ['unsupported_lip_care_lookup_only'];
        $hasLookupOnlyLips = isset($rows['forever-aloe-lips'])
            && (string) ($rows['forever-aloe-lips']['advisor_status'] ?? '') !== 'recommendable';
        $clarifying_question = $hasLookupOnlyLips
            ? fcc_ai_v2_localized_text($language, [
                'hr' => 'Razumijem, fokus su usne, ne lice ni tijelo. Forever Aloe Lips postoji u katalogu, ali zasad je dostupan samo za provjeru podataka, ne za automatsku preporuku, pa ga neću prikazati kao odabrani proizvod ni ponuditi kupovni link. Želite li provjeriti njegov službeni opis ili aktualnu deklaraciju?',
                'en' => 'Understood: the focus is lips, not face or body. Forever Aloe Lips is in the catalog, but it is currently available for fact lookup only, not automatic recommendation, so I will not select it or provide a purchase link. Would you like to check its official description or current label?',
                'sl' => 'Razumem: poudarek je na ustnicah, ne obrazu ali telesu. Forever Aloe Lips je v katalogu, vendar je trenutno na voljo le za preverjanje podatkov, ne za samodejno priporočilo, zato ga ne bom izbral ali ponudil povezave za nakup. Želite preveriti uradni opis ali trenutno deklaracijo?',
                'bg' => 'Разбирам: фокусът е върху устните, не лицето или тялото. Forever Aloe Lips е в каталога, но засега е само за проверка на данни, не за автоматична препоръка, затова няма да го избирам или да давам линк за покупка. Искате ли да проверим официалното описание или актуалния етикет?',
            ])
            : fcc_ai_v2_localized_text($language, [
                'hr' => 'Razumijem, fokus su usne, ne lice ni tijelo. Za usne trenutačno nemam proizvod s dovoljno provjerenim zapisom za automatsku preporuku. Želite li provjeriti službeni opis ili aktualnu deklaraciju proizvoda koji imate?',
                'en' => 'Understood: the focus is lips, not face or body. I currently do not have a sufficiently reviewed record for an automatic lip-care recommendation. Would you like to check the official description or current label of a product you have?',
                'sl' => 'Razumem: poudarek je na ustnicah, ne obrazu ali telesu. Trenutno nimam dovolj preverjenega zapisa za samodejno priporočilo nege ustnic. Želite preveriti uradni opis ali trenutno deklaracijo izdelka, ki ga imate?',
                'bg' => 'Разбирам: фокусът е върху устните, не лицето или тялото. В момента нямам достатъчно проверен запис за автоматична препоръка за устни. Искате ли да проверим официалното описание или актуалния етикет на продукт, който имате?',
            ]);
    } elseif(!$positive_mentions && $skinAreaScope === 'mixed') {
        $action = 'clarify';
        $confidence = 0.98;
        $decision_reasons = ['ambiguous_skin_area'];
        $clarifying_question = fcc_ai_v2_localized_text($language, [
            'hr' => 'Spomenuli ste više područja kože. Želite li prvo provjeriti lice, tijelo ili usne?',
            'en' => 'You mentioned more than one skin area. Would you like to check the face, body, or lips first?',
            'sl' => 'Omenili ste več predelov kože. Želite najprej preveriti obraz, telo ali ustnice?',
            'bg' => 'Споменахте повече от една област на кожата. Искате ли първо да проверим лицето, тялото или устните?',
        ]);
    } elseif(
        !$allowed_keys
        || (
            $intent === 'recommendation'
            && !$positive_mentions
            && count(fcc_ai_v2_meaningful_tokens($message, $language)) <= 1
            && !fcc_ai_v2_query_entity_profiles($message, $state)
        )
    ) {
        $action = 'clarify';
        $confidence = 0.35;
        $decision_reasons = ['insufficient_grounded_context'];
        if(
            isset(fcc_ai_v2_query_entity_profiles($message, $state)['vitamin_c'])
            && fcc_ai_v2_route_hint($message) === 'topical'
            && isset($rows['forever-vitamin-c'])
        ) {
            $clarifying_question = fcc_ai_v2_localized_text($language, [
                'hr' => 'Prepoznajem da tražite topikalni serum s vitaminom C za lice. Forever Vitamin C postoji u katalogu, ali njegov aktualni službeni zapis još nije potpuno provjeren za automatsku preporuku, pa ga neću predstaviti kao izbor bez potvrđene deklaracije. Mogu provjeriti njegov točan sastav ili uputu ako pošaljete aktualnu deklaraciju pakiranja.',
                'en' => 'I understand that you want a topical vitamin C facial serum. Forever Vitamin C exists in the catalog, but its current official record is not yet fully verified for automatic recommendation, so I will not present it as a choice without a confirmed label. I can check its exact composition or directions if you share the current package label.',
                'sl' => 'Razumem, da iščete topikalni serum z vitaminom C za obraz. Forever Vitamin C je v katalogu, vendar njegov trenutni uradni zapis še ni v celoti preverjen za samodejno priporočilo, zato ga brez potrjene deklaracije ne bom predstavil kot izbiro. Če pošljete trenutno deklaracijo embalaže, lahko preverim natančno sestavo ali uporabo.',
                'bg' => 'Разбирам, че търсите локален серум с витамин C за лице. Forever Vitamin C присъства в каталога, но актуалният му официален запис още не е напълно проверен за автоматична препоръка, затова няма да го представя като избор без потвърден етикет. Мога да проверя точния състав или употреба, ако изпратите актуалния етикет.',
            ]);
        } elseif(fcc_ai_v2_contains_any($message, ['nije probava nego koza', 'ne nije probava nego koza'])) {
            $clarifying_question = 'Razumijem, mijenjamo smjer na kožu. Radi li se o aknama, suhoći ili osjetljivosti kože?';
        } elseif(fcc_ai_v2_contains_any($message, ['probava', 'probavom', 'digestion'])) {
            $clarifying_question = fcc_ai_v2_localized_text($language, [
                'hr' => 'Je li glavni problem zatvor, nadutost ili osjetljiv želudac?',
                'en' => 'Is the main issue constipation, bloating, or a sensitive stomach?',
                'sl' => 'Je glavna težava zaprtje, napihnjenost ali občutljiv želodec?',
                'bg' => 'Основният проблем запек, подуване или чувствителен стомах ли е?',
            ]);
        } elseif(fcc_ai_v2_contains_any($message, ['imunitet', 'immunity'])) {
            $clarifying_question = 'Mogu usporediti samo točno deklarirane sastojke i upozorenja proizvoda. Koja dva proizvoda ili koja hranjiva tvar vas zanimaju?';
        } elseif($completed || $rejected) {
            $clarifying_question = 'Razumijem i neću vraćati odbijeni ili već završeni program. Koji je sada vaš glavni cilj?';
        } elseif(fcc_ai_v2_contains_any($message, ['ne osjecam se', 'not feeling well'])) {
            $clarifying_question = 'Što vas trenutno najviše muči: probava, energija, san, stres ili koža?';
        } else {
            $discoveryState = $state;
            $discoveryState['goal_keys'] = $goalKeys;
            $discoveryQuestion = fcc_ai_v2_discovery_question($message, $language, $discoveryState);
            $clarifying_question = (string) $discoveryQuestion['text'];
        }
    } else {
        $top_score = (int) ($candidates[0]['score'] ?? 0);
        $second_score = (int) ($candidates[1]['score'] ?? 0);
        $confidence = $top_score >= 1000 ? 0.99 : min(0.92, max(0.45, $top_score / 140));
        if($top_score < 18 || ($second_score > 0 && abs($top_score - $second_score) < 5 && $top_score < 1000)) {
            $action = 'clarify';
            $confidence = min($confidence, 0.45);
            $decision_reasons = ['low_or_ambiguous_relevance'];
            if(fcc_ai_v2_contains_any($message, ['nije probava nego koza', 'ne nije probava nego koza'])) {
                $clarifying_question = 'Razumijem, mijenjamo smjer na kožu. Radi li se o aknama, suhoći ili osjetljivosti kože?';
            } elseif(isset(fcc_ai_v2_query_entity_profiles($message, $state)['aloe_beverage'])) {
                $clarifying_question = fcc_ai_v2_localized_text($language, [
                    'hr' => 'Želite li neutralni Aloe Vera Gel ili okus bobičastog voća, manga ili breskve? Svi su napitci, pa bez preference neću proizvoljno izabrati okus.',
                    'en' => 'Would you prefer plain Aloe Vera Gel, or berry, mango, or peach flavour? They are all drinks, so I will not choose a flavour arbitrarily.',
                    'sl' => 'Želite navadni Aloe Vera Gel ali okus jagodičevja, manga ali breskve? Vsi so napitki, zato okusa ne bom izbral samovoljno.',
                    'bg' => 'Предпочитате чист Aloe Vera Gel или вкус горски плодове, манго или праскова? Всички са напитки, затова няма да избирам вкус произволно.',
                ]);
            } elseif($skinAreaScope === 'face' || in_array('facial_skincare', $goalKeys, true)) {
                $facialDiscoveryState = $state;
                $facialDiscoveryState['goal_keys'] = $goalKeys;
                $facialDiscovery = fcc_ai_v2_discovery_question($message, $language, $facialDiscoveryState);
                $clarifying_question = (string) ($facialDiscovery['text'] ?? '');
            } else {
                $clarifying_question = fcc_ai_v2_localized_text($language, [
                    'hr' => 'Što vam je najvažnije postići kako bih suzio preporuku na jedan glavni proizvod?',
                    'en' => 'What is the most important result for you, so I can narrow this to one main product?',
                    'sl' => 'Kaj vam je najpomembneje doseči, da lahko priporočilo zožim na en glavni izdelek?',
                    'bg' => 'Какъв е най-важният резултат за вас, за да сведа препоръката до един основен продукт?',
                ]);
            }
        }
    }

    $supportRequested = in_array($queryFocus, ['compatibility', 'routine_sequence'], true)
        || preg_match(
            '/\b(?:dva|dvije|2|dodatn|dopunsk|kombinacij|povezan)\p{L}*\b|\bjos\s+jed\p{L}*\b|\buz\s+(?:njeg|nju)\p{L}*\b/u',
            fcc_ai_v2_normalize_text($message)
        ) === 1;
    $supportsMayBeConsidered = !empty($safety['allow_products'])
        && in_array($action, ['answer', 'factual_lookup'], true)
        && $supportRequested
        && ($intent === 'recommendation' || in_array($queryFocus, ['compatibility', 'routine_sequence'], true));
    if($supportsMayBeConsidered) {
        $supportRelationships = fcc_ai_v2_support_relationships_for_plan(
            $message,
            $language,
            $state,
            $primaryCandidates,
            $positive_mentions,
            $rows,
            $queryFocus === 'compatibility',
            $queryFocus === 'routine_sequence'
        );
        $blockedSupportKeys = array_fill_keys(fcc_ai_v2_unique_strings(array_merge($rejected, $completed)), true);
        $candidateIndexes = [];
        foreach($candidates as $index => $candidate) {
            $candidateIndexes[(string) ($candidate['product_key'] ?? '')] = $index;
        }
        foreach($supportRelationships as $relationshipIndex => $relationship) {
            $targetProductKey = (string) ($relationship['target_product_key'] ?? '');
            if(
                $targetProductKey === ''
                || isset($blockedSupportKeys[$targetProductKey])
                || !isset($rows[$targetProductKey])
                || fcc_ai_v2_product_constraint_conflicts($rows[$targetProductKey], $userConstraints)
            ) {
                unset($supportRelationships[$relationshipIndex]);
                continue;
            }

            if(isset($candidateIndexes[$targetProductKey])) {
                $existingIndex = $candidateIndexes[$targetProductKey];
                $candidates[$existingIndex]['candidate_role'] = 'primary_or_support';
                $candidates[$existingIndex]['relationship_ids'] = fcc_ai_v2_unique_strings(array_merge(
                    (array) ($candidates[$existingIndex]['relationship_ids'] ?? []),
                    [(string) ($relationship['relation_id'] ?? '')]
                ));
                continue;
            }

            $candidateIndexes[$targetProductKey] = count($candidates);
            $candidates[] = [
                'product_key' => $targetProductKey,
                'score' => 0,
                'reason_codes' => [
                    'verified_' . (string) ($relationship['relation_type'] ?? 'relationship') . '_of:' . (string) ($relationship['source_product_key'] ?? ''),
                    'relationship_goal:' . (string) ($relationship['matched_goal_key'] ?? ''),
                ],
                'direct' => false,
                'candidate_role' => 'support_only',
                'relationship_ids' => [(string) ($relationship['relation_id'] ?? '')],
            ];
        }
        $supportRelationships = array_values($supportRelationships);
        $allowed_keys = fcc_ai_v2_unique_strings(array_merge(
            $primaryAllowedKeys,
            array_map(static fn(array $relationship): string => (string) ($relationship['target_product_key'] ?? ''), $supportRelationships)
        ), 24);
    }

    if(
        $queryFocus === 'compatibility'
        && !empty($safety['allow_products'])
        && in_array($action, ['answer', 'factual_lookup'], true)
        && !$supportRelationships
    ) {
        $action = 'clarify';
        $confidence = 0.98;
        $decision_reasons = ['verified_product_relationship_unavailable'];
        $clarifying_question = fcc_ai_v2_localized_text($language, [
            'hr' => 'Za tu kombinaciju nemam provjerenu službenu potvrdu kompatibilnosti. Navedite točna dva proizvoda i tržište ili pošaljite fotografije obje deklaracije; kombinaciju neću nagađati.',
            'en' => 'I do not have verified official compatibility evidence for that combination. Provide the exact two products and market, or photos of both labels; I will not guess.',
            'sl' => 'Za to kombinacijo nimam preverjene uradne potrditve združljivosti. Navedite točna izdelka in trg ali pošljite fotografiji obeh deklaracij; ne bom ugibal.',
            'bg' => 'Нямам проверено официално потвърждение за тази комбинация. Посочете точните два продукта и пазара или изпратете снимки на двата етикета; няма да предполагам.',
        ]);
    }

    if(!$safety['allow_products'] || in_array($action, ['clarify', 'urgent_triage', 'doctor_first', 'small_talk', 'redirect', 'business_guidance', 'content_creation'], true)) {
        $candidates = [];
        $allowed_keys = [];
        $primaryAllowedKeys = [];
        $supportRelationships = [];
        $safety['allow_products'] = false;
    }

    $askedQuestionIds = fcc_ai_v2_unique_strings($state['asked_question_ids'] ?? [], 100);
    $pendingSlot = null;
    $questionId = '';
    if($action === 'clarify' && trim($clarifying_question) !== '') {
        $pendingState = $state;
        $pendingState['goal_keys'] = $goalKeys;
        $pendingDescriptor = fcc_ai_v2_clarifier_slot(
            (string) ($decision_reasons[0] ?? 'insufficient_grounded_context'),
            $message,
            $clarifying_question,
            $language,
            $pendingState
        );
        $baseQuestionId = (string) ($pendingDescriptor['question_id'] ?? 'discover.primary_goal.v1');
        $questionId = fcc_ai_v2_unique_question_id($baseQuestionId, $askedQuestionIds);
        if(
            $questionId !== $baseQuestionId
            && in_array((string) ($decision_reasons[0] ?? ''), ['insufficient_grounded_context', 'low_or_ambiguous_relevance'], true)
        ) {
            $clarifying_question = str_ends_with($questionId, '.followup_2')
                ? fcc_ai_v2_localized_text($language, [
                    'hr' => 'Koje područje želite prvo riješiti: njega kože, konkretna hranjiva tvar ili provjera proizvoda koji već imate?',
                    'en' => 'Which area should we solve first: skin care, a specific nutrient, or checking a product you already have?',
                    'sl' => 'Katero področje želite najprej rešiti: nego kože, določeno hranilo ali preverjanje izdelka, ki ga že imate?',
                    'bg' => 'Коя област искате да решим първо: грижа за кожата, конкретно хранително вещество или проверка на продукт, който вече имате?',
                ])
                : fcc_ai_v2_localized_text($language, [
                    'hr' => 'Opišite jednom rečenicom što želite od proizvoda ili koje područje njege vas zanima?',
                    'en' => 'In one sentence, what do you want from the product or which care area interests you?',
                    'sl' => 'V enem stavku opišite, kaj želite od izdelka ali katero področje nege vas zanima?',
                    'bg' => 'Опишете с едно изречение какво очаквате от продукта или коя област на грижа ви интересува?',
                ]);
        }
        $pendingSlot = [
            'slot_key' => (string) ($pendingDescriptor['slot_key'] ?? 'primary_goal'),
            'question_id' => $questionId,
            'accepted_values' => fcc_ai_v2_unique_strings($pendingDescriptor['accepted_values'] ?? [], 12),
            'asked_turn' => min(10000, max(0, (int) ($state['turn_count'] ?? 0)) + 1),
            'reason' => (string) ($decision_reasons[0] ?? 'insufficient_grounded_context'),
        ];
        $askedQuestionIds = fcc_ai_v2_unique_strings(array_merge($askedQuestionIds, [$questionId]), 100);
    }

    $state['language'] = $language;
    $state['market'] = $market;
    $state['market_source'] = $marketSource;
    $state['last_intent'] = $intent;
    $state['last_action'] = $action;
    $state['last_safety'] = $safety;
    $state['medical_context_flags'] = $medicalContextFlags;
    $state['medical_context_key'] = $effectiveMedicalContextKey;
    $state['medical_context_reason_code'] = $effectiveMedicalContextReason;
    $state['concern_class'] = (string) ($medicalNeed['concern_class'] ?? '') !== ''
        ? (string) $medicalNeed['concern_class']
        : (string) ($goalProfile['concern_class'] ?? 'unknown');
    $state['goal_keys'] = $goalKeys;
    if(in_array($skinAreaScope, ['face', 'body', 'lips', 'mixed'], true)) {
        $state['skin_area'] = $skinAreaScope;
    } elseif(
        $action === 'business_guidance'
        || (string) ($safety['level'] ?? 'routine') !== 'routine'
        || (
            $detectedGoalKeys
            && !in_array((string) ($goalProfile['concern_class'] ?? ''), ['cosmetic', 'topical'], true)
        )
    ) {
        $state['skin_area'] = '';
    }
    $state['user_constraints'] = $userConstraints;
    $state['rejected_product_keys'] = $rejected;
    $state['completed_product_keys'] = $completed;
    if($action === 'business_guidance') {
        $state['active_product_keys'] = [];
    } elseif($subjectKeys) {
        $state['active_product_keys'] = $subjectKeys;
    } elseif(in_array($context_operation, ['reset', 'replace'], true)) {
        $state['active_product_keys'] = [];
    }
    $state['active_flow'] = $action === 'content_creation'
        ? 'content_creation'
        : ($action === 'business_guidance' ? 'business' : '');
    if($action === 'content_creation') {
        $state['content_topic'] = $contentTopic;
        $state['content_format'] = $contentFormat;
    } else {
        $state['content_topic'] = '';
        $state['content_format'] = '';
    }
    $state['last_allowed_product_keys'] = $allowed_keys;
    $state['pending_question'] = $clarifying_question;
    $state['pending_reason'] = $action === 'clarify' ? (string) ($decision_reasons[0] ?? '') : '';
    $state['pending_query_focus'] = $action === 'clarify' ? $queryFocus : '';
    $state['pending_slot'] = $pendingSlot;
    $state['asked_question_ids'] = $askedQuestionIds;
    $state['last_query_focus'] = $queryFocus;
    $weightDecisionReasons = fcc_ai_v2_unique_strings($decision_reasons, 8);
    if(
        $explicitWeightFlowExit
        || in_array($action, ['urgent_triage', 'doctor_first', 'business_guidance', 'content_creation'], true)
    ) {
        $state['weight_management_stage'] = '';
    } elseif(
        $action === 'clarify'
        && in_array('health_claim_weight_loss', $weightDecisionReasons, true)
    ) {
        $state['weight_management_stage'] = 'safety_screen';
    } elseif(in_array('weight_management_safety_screen', $weightDecisionReasons, true)) {
        $state['weight_management_stage'] = 'safety_screen';
    } elseif(in_array('weight_management_format_choice', $weightDecisionReasons, true)) {
        $state['weight_management_stage'] = 'format_choice';
    } elseif(
        in_array($action, ['answer', 'factual_lookup'], true)
        && in_array('neutral_product_fact', $weightDecisionReasons, true)
        && in_array('health_claim_weight_loss', $weightDecisionReasons, true)
    ) {
        $state['weight_management_stage'] = 'selected_fact_only';
    } else {
        $state['weight_management_stage'] = $weightManagementStage;
    }
    if(
        (string) ($state['weight_management_stage'] ?? '') !== ''
        && (string) ($state['active_flow'] ?? '') === ''
    ) {
        $state['active_flow'] = 'weight_management';
    }
    $state['turn_count'] = min(10000, max(0, (int) ($state['turn_count'] ?? 0)) + 1);

    return [
        'language' => $language,
        'intent' => $intent,
        'safety' => $safety,
        'context_operation' => $context_operation,
        'product_mentions' => $mentions,
        'rejected_product_keys' => $rejected,
        'completed_product_keys' => $completed,
        'user_constraints' => $userConstraints,
        'product_constraint_conflicts' => $directProductConflicts,
        'candidates' => $candidates,
        'allowed_product_keys' => $allowed_keys,
        'primary_allowed_product_keys' => $primaryAllowedKeys,
        'support_relationships' => $supportRelationships,
        'program_member_relationships' => $programMemberRelationships,
        'decision' => [
            'action' => $action,
            'confidence' => round($confidence, 3),
            'reason_codes' => $decision_reasons,
            'clarifying_question' => $clarifying_question,
            'question_id' => $questionId,
            'pending_slot' => $pendingSlot,
        ],
        'state' => $state,
        'trace' => [
            'engine' => 'fcc_product_advisor_v2',
            'catalog_size' => count($rows),
            'route_hint' => fcc_ai_v2_route_hint($message),
            'query_focus' => $queryFocus,
            'market' => $market,
            'subject_product_keys' => $subjectKeys,
            'support_relationship_count' => count($supportRelationships),
            'program_member_relationship_count' => count($programMemberRelationships),
            'support_requested' => $supportRequested,
            'small_talk_kind' => fcc_ai_v2_contains_any($message, ['hvala', 'thanks', 'tks']) ? 'thanks' : (fcc_ai_v2_is_short_context_followup($message) ? 'acknowledgement' : 'greeting'),
            'content_topic' => $contentTopic,
            'content_format' => $contentFormat,
            'business_topic' => (string) ($businessProfile['topic'] ?? ''),
            'goal_keys' => $goalKeys,
            'concern_class' => (string) ($state['concern_class'] ?? 'unknown'),
            'skin_area_scope' => (string) ($state['skin_area'] ?? ''),
            'medical_context_key' => (string) ($state['medical_context_key'] ?? ''),
            'pending_question_id' => $questionId,
            /* Content templates are fully server-owned; calling the provider
             * would add latency/cost without any field being allowed to affect
             * the rendered answer. */
            'provider_required' => !$directProductConflicts
                && (
                    in_array($action, ['answer', 'factual_lookup'], true)
                    || (
                        $action === 'clarify'
                        && (string) ($safety['level'] ?? 'routine') === 'routine'
                        && in_array((string) ($decision_reasons[0] ?? ''), [
                            'insufficient_grounded_context',
                            'low_or_ambiguous_relevance',
                            'comparison_criterion_required',
                            'completed_program_requires_new_goal',
                            'user_reported_bad_answer',
                        ], true)
                    )
                )
                /* Package membership is already a complete, reviewed graph.
                 * Letting model prose override it can only remove or invent
                 * members, so this answer is deliberately server-owned. */
                && !($queryFocus === 'package_contents' && !empty($programMemberRelationships))
                /* The visitor has already completed the deterministic safety
                 * and format flow; another model call cannot add an approved
                 * fact to this facts-only C9/F15/Plant Protein choice. */
                && !(
                    $weightManagementStage === 'format_choice'
                    && $weightManagementPreference !== ''
                ),
        ],
    ];
}

function fcc_ai_v2_fact_index(array $catalog): array {
    $index = [];
    foreach(fcc_ai_v2_catalog_rows($catalog) as $product_key => $row) {
        $groups = [
            'ingredient' => $row['ingredient_facts'],
            'support' => $row['approved_support_facts'],
            'usage' => $row['usage_facts'],
            'safety' => $row['safety_notes'],
        ];
        foreach($groups as $group => $facts) {
            foreach($facts as $offset => $fact) {
                $fact_id = $product_key . ':' . $group . ':' . $offset;
                $index[$fact_id] = [
                    'fact_id' => $fact_id,
                    'product_key' => $product_key,
                    'type' => $group,
                    'text' => (string) $fact,
                ];
            }
        }
    }

    return $index;
}

function fcc_ai_v2_clean_provider_text(string $text, int $max_length = 900): string {
    $text = strip_tags($text);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return mb_substr(trim($text), 0, $max_length);
}

/**
 * A model acknowledgement may make a routine answer feel conversational, but
 * it is not an evidence channel. Keep it to one short reflection of the
 * visitor's goal; product reasoning and facts remain deterministic below.
 */
function fcc_ai_v2_provider_answer_acknowledgement_violation(string $text, string $language): string {
    $clean = fcc_ai_v2_clean_provider_text($text, 1000);
    if($clean === '') {
        return '';
    }
    if(mb_strlen($clean) > 200) {
        return 'too_long';
    }
    if(str_contains($clean, '?')) {
        return 'contains_question';
    }

    $sentences = preg_split('/(?<=[.!])\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if(count($sentences) > 1) {
        return 'multiple_sentences';
    }

    $normalized = fcc_ai_v2_normalize_text($clean);
    $goalLedPatterns = [
        'hr' => '/^(?:(?:razumijem|shvacam|jasno\s+mi\s+je)(?:\s+da)?\s+(?:trazite|zelite|trebate|vazno\s+vam\s+je|fokus\s+vam\s+je|cilj\s+vam\s+je)|prema\s+onome\s+sto\s+trazite|(?:trazite|zelite|trebate)\b|(?:suhoca|dehidracij\p{L}*|hidratacij\p{L}*|ciscenj\p{L}*|unos\s+(?:protein\p{L}*|vitamin\p{L}*|mineral\p{L}*)).{0,55}(?:vas\s+(?:glavni\s+)?(?:cilj|fokus)|vazn\p{L}*\s+vam\s+je))\b/u',
        'en' => '/^(?:(?:i\s+understand|got\s+it)(?:\s+that)?\s+(?:you\s+(?:want|need)|you(?:\s+are|\'re)\s+looking\s+for|your\s+(?:focus|goal)\s+is)|based\s+on\s+what\s+you(?:\s+are|\'re)\s+looking\s+for)\b/u',
        'sl' => '/^(?:(?:razumem|jasno\s+mi\s+je)(?:\s+da)?\s+(?:iscete|zelite|potrebujete|vam\s+je\s+pomembno|vas\s+cilj\s+je)|glede\s+na\s+to\s+kar\s+iscete)\b/u',
        'bg' => '/^(?:(?:разбирам|ясно\s+ми\s+е)(?:\s+че)?\s+(?:търсите|искате|имате\s+нужда|целта\s+ви\s+е|фокусът\s+ви\s+е)|според\s+това\s+което\s+търсите)\b/u',
    ];
    $goalLedPattern = $goalLedPatterns[$language] ?? $goalLedPatterns['hr'];
    if(preg_match($goalLedPattern, $normalized) !== 1) {
        return 'not_goal_led';
    }

    if(preg_match(
        '/(?<![\p{L}\p{N}])(?:jer|zato\s+sto|buduci\s+da|because|since|ker|zato\s+ker|защото|понеже)(?![\p{L}\p{N}])|[:;]/u',
        $normalized
    ) === 1) {
        return 'contains_explanation';
    }
    if(preg_match(
        '/(?<![\p{L}\p{N}])(?:sadrz\p{L}*|contain\p{L}*|vsebuje\p{L}*|съдърж\p{L}*|pomaz\p{L}*|podrzava\p{L}*|hidrira\p{L}*|djeluj\p{L}*|improv\p{L}*|supports?|hydrates?|works?|helps?|помага\p{L}*|подкреп\p{L}*|хидрат\p{L}*)(?![\p{L}\p{N}])/u',
        $normalized
    ) === 1) {
        return 'contains_new_claim';
    }
    if(preg_match(
        '/(?<![\p{L}\p{N}])(?:kupite|kupi|narucite|odmah|morate|obavezno|najbolj\p{L}*|savrsen\p{L}*|ideal\p{L}*|buy\s+now|order\s+now|must|perfect|best|naro\p{L}*|веднага|трябва|най-добр\p{L}*|перфект\p{L}*)(?![\p{L}\p{N}])/u',
        $normalized
    ) === 1) {
        return 'sales_pressure';
    }

    return '';
}

function fcc_ai_v2_safety_text(string $language, string $level, array $reasonCodes): string {
    $reason = (string) ($reasonCodes[0] ?? 'urgent_symptoms');
    if($language === 'hr') {
        $messages = [
            'anaphylaxis' => 'Otečene usne ili jezik, osip i otežano disanje mogu značiti anafilaksiju. Odmah nazovite 112; ako imate propisani autoinjektor adrenalina, primijenite ga prema svojoj uputi.',
            'cardiorespiratory_emergency' => 'Bol u prsima i otežano disanje mogu biti hitno stanje. Odmah nazovite 112 i nemojte voziti sami.',
            'life_threatening_symptoms' => 'Opisani simptomi mogu biti hitno stanje. Odmah nazovite 112 i, ako ste sami, obavijestite osobu u blizini.',
            'hypertensive_emergency' => 'Tlak 180/120 ili viši uz bol u prsima, otežano disanje ili neurološke simptome može biti hipertenzivna hitnost. Odmah nazovite 112; nemojte čekati da se tlak sam snizi i nemojte pokušavati stanje riješiti proizvodom.',
            'severe_hypertension' => 'Tlak 180/120 ili viši ponovite nakon najmanje jedne minute mirovanja. Ako je i drugo mjerenje tako visoko, odmah kontaktirajte liječnika; uz bol u prsima, otežano disanje, slabost, utrnulost, promjenu vida ili govora nazovite 112.',
            'chest_pain_assessment' => 'Bol ili pritisak u prsima treba medicinski procijeniti. Ako je bol nagla, ne prolazi, širi se u ruku, vrat, čeljust, leđa ili je praćena znojenjem, mučninom, omaglicom ili nedostatkom zraka, odmah nazovite 112.',
            'urinary_obstruction' => 'Oticanje nogu uz otežano mokrenje zahtijeva hitnu liječničku procjenu danas. Nemojte uzimati dodatke ili lijekove za izbacivanje vode na svoju ruku prije pregleda.',
            'infection_on_antibiotic' => 'Povišen CRP uz infekciju tijekom antibiotske terapije treba danas procijeniti liječnik koji vodi terapiju, osobito ako se stanje pogoršava, imate temperaturu, bol u slabinama, povraćanje ili izrazitu slabost.',
            'suspected_thrombosis' => 'Tvrda, crvena i bolna vena zahtijeva hitnu liječničku procjenu zbog mogućeg ugruška ili upale vene. Nemojte masirati područje niti čekati preporuku proizvoda.',
            'gi_bleeding' => 'Jaka bol i krv u stolici zahtijevaju hitnu medicinsku procjenu danas. Obratite se svojem liječniku ili hitnoj službi; nemojte pokušavati stanje riješiti proizvodom.',
            'nail_granuloma' => 'Granulom uz nokat treba danas procijeniti liječnik, posebno ako postoji bol, gnoj ili širenje crvenila. Nemojte nanositi kozmetičke proizvode na upaljeno područje.',
        ];
        if(isset($messages[$reason])) {
            return $messages[$reason];
        }
    }

    $emergency = $level === 'emergency';
    return fcc_ai_v2_localized_text($language, [
        'hr' => $emergency
            ? 'Ovo može biti hitno stanje. Odmah nazovite 112; nemojte čekati preporuku proizvoda.'
            : 'Ovo zahtijeva hitnu medicinsku procjenu danas. Kontaktirajte liječnika ili hitnu službu i nemojte pokušavati stanje riješiti proizvodom.',
        'en' => $emergency
            ? 'This may be an emergency. Call your local emergency number now; do not wait for a product recommendation.'
            : 'This needs urgent medical assessment today. Contact a clinician or urgent care service and do not try to manage it with a product.',
        'sl' => $emergency
            ? 'To je lahko nujno stanje. Takoj pokličite 112; ne čakajte na priporočilo izdelka.'
            : 'To zahteva nujno zdravniško oceno še danes. Obrnite se na zdravnika ali urgenco in stanja ne poskušajte reševati z izdelkom.',
        'bg' => $emergency
            ? 'Това може да е спешно състояние. Незабавно се обадете на 112; не чакайте препоръка за продукт.'
            : 'Това изисква спешна медицинска оценка днес. Свържете се с лекар или спешна помощ и не опитвайте да го решите с продукт.',
    ]);
}

/**
 * Server-owned, evidence-reviewed product references that may remain useful
 * inside a diagnosis response. They are deliberately separate from the
 * commercial recommendation/selection payload: they must never turn a
 * supplement into treatment for the diagnosed condition or create the normal
 * "recommended product" card. New profiles belong here only after claim,
 * label, interaction and regression review.
 */
function fcc_ai_v2_supportive_wellness_profile(array $reasonCodes): array {
    $profiles = [
        /* The legacy FCC matrix used this pair for fatty-liver conversations.
         * Current evidence supports Arctic Sea only as a conditional EPA/DHA
         * nutrition option (for example low oily-fish intake or raised
         * triglycerides), not as MASLD treatment. Aloe remains an optional
         * labelled aloe drink and must not be described as liver treatment. */
        'diagnosed_fatty_liver' => [
            'condition_key' => 'fatty_liver',
            'profile_version' => 'fatty-liver-2026-08-31-v1',
            'disclaimer_hr' => 'Ove su opcije potporne nutritivne informacije, a ne liječenje masne jetre niti zamjena za propisanu terapiju.',
            'options' => [
                [
                    'product_key' => 'forever-aloe-vera-gel',
                    'evidence_tier' => 'C_LABEL_ONLY',
                    'reason_hr' => 'Provjeren aloe napitak iz stare FCC rutine; nema pouzdanog dokaza da smanjuje masnoću jetre.',
                ],
                [
                    'product_key' => 'forever-arctic-sea',
                    'evidence_tier' => 'B_GENERAL_NUTRITION',
                    'reason_hr' => 'EPA/DHA nutritivna opcija koja je najrelevantnija uz nizak unos masne ribe ili povišene trigliceride; nije terapija jetre.',
                ],
            ],
        ],
        'diagnosed_hypertension' => [
            'condition_key' => 'high_blood_pressure',
            'profile_version' => 'high-blood-pressure-2026-08-31-v1',
            'disclaimer_hr' => 'Ovi su proizvodi prikazani samo radi neutralne provjere sastava i deklaracije; nisu liječenje hipertenzije niti zamjena za lijekove.',
            'options' => [
                [
                    'product_key' => 'forever-argi',
                    'evidence_tier' => 'C_LABEL_ONLY',
                    'reason_hr' => 'Deklarirani dodatak s L-argininom; prikladnost ovisi o lijekovima i bolestima srca, bubrega ili jetre.',
                ],
                [
                    'product_key' => 'forever-arctic-sea',
                    'evidence_tier' => 'B_GENERAL_NUTRITION',
                    'reason_hr' => 'Dodatak s ribljim uljem i uljem lignje za opću nutritivnu rutinu; nije proizvod za snižavanje tlaka.',
                ],
                [
                    'product_key' => 'forever-nutra-q10',
                    'evidence_tier' => 'C_LABEL_ONLY',
                    'reason_hr' => 'Deklarirani dodatak s koenzimom Q10; prije uporabe treba provjeriti cijelu terapiju.',
                ],
            ],
        ],
    ];

    foreach($profiles as $reasonCode => $profile) {
        if(in_array($reasonCode, $reasonCodes, true)) {
            return is_array($profile) ? $profile : [];
        }
    }

    return [];
}

/** @return array<int,string> */
function fcc_ai_v2_supportive_reference_product_keys(array $reasonCodes): array {
    $profile = fcc_ai_v2_supportive_wellness_profile($reasonCodes);
    $keys = [];
    foreach((array) ($profile['options'] ?? []) as $option) {
        if(!is_array($option)) {
            continue;
        }
        $productKey = trim((string) ($option['product_key'] ?? ''));
        if($productKey !== '') {
            $keys[] = $productKey;
        }
    }
    return array_values(array_unique($keys));
}

function fcc_ai_v2_supportive_product_blocked_by_constraints(string $productKey, array $constraints): bool {
    $constraintSet = array_fill_keys(fcc_ai_v2_unique_strings($constraints, 30), true);
    if(isset($constraintSet['age_under_18'])) {
        return true;
    }
    if($productKey === 'forever-aloe-vera-gel' && isset($constraintSet['allergen:aloe'])) {
        return true;
    }
    if(
        $productKey === 'forever-arctic-sea'
        && (
            isset($constraintSet['allergen:fish'])
            || isset($constraintSet['diet:vegan'])
            || isset($constraintSet['diet:vegetarian'])
        )
    ) {
        return true;
    }

    return false;
}

function fcc_ai_v2_fatty_liver_support_text(array $supportiveProductKeys, array $constraints = []): string {
    $hasAloe = in_array('forever-aloe-vera-gel', $supportiveProductKeys, true);
    $hasArctic = in_array('forever-arctic-sea', $supportiveProductKeys, true);
    $base = 'Kod masne jetre najviše smisla ima plan koji istodobno rješava tjelesnu masu ako je povišena, prehranu, kretanje, alkohol, šećer i trigliceride. Forever proizvod ne zamjenjuje taj plan ni propisanu terapiju.';

    if(!$hasAloe && !$hasArctic) {
        return $base
            . "\n\nZbog navedene dobi, alergije ili prehrambenog ograničenja neću prikazati stari FCC par Aloe Vera Gel + Arctic Sea kao prikladan izbor. Mogu provjeriti drugi proizvod tek kada postoji aktualna deklaracija i nema sukoba s vašim podacima."
            . "\n\nNapomena: dodatak prehrani nije dijagnoza ni liječenje masne jetre. Nemojte prekidati ili mijenjati propisanu terapiju.";
    }

    $sections = [$base, 'Ako uz taj plan želite konkretan Forever nutritivni okvir, predložio bih provjerene dijelove kombinacije iz stare FCC matrice:'];
    $index = 1;
    if($hasAloe) {
        $sections[] = $index++ . '. Forever Aloe Vera Gel — sadrži 99,7 % stabiliziranog Aloe vera gela i može biti opcionalni aloe napitak u svakodnevnoj rutini. Nema pouzdanog dokaza da čisti, detoksicira ili liječi jetru, pa ga ne predstavljam kao terapiju.';
    }
    if($hasArctic) {
        $sections[] = $index++ . '. Forever Arctic Sea — sadrži EPA i DHA. Izvan liječenja same jetre najrelevantniji je ako rijetko jedete masnu ribu ili imate povišene trigliceride. To je omega-3 nutritivna opcija, a ne proizvod koji liječi ili uklanja masnoću iz jetre.';
    }

    $usage = [];
    $quantity = [];
    if($hasAloe) {
        $usage[] = 'Forever Aloe Vera Gel 40 ml tri puta dnevno, a svaku porciju pomiješati s približno 240 ml vode ili soka';
        $quantity[] = '4 boce Forever Aloe Vera Gela od 1 l';
    }
    if($hasArctic) {
        $usage[] = 'Forever Arctic Sea dvije kapsule tri puta dnevno uz obrok';
        $quantity[] = '2 pakiranja Forever Arctic Sea od 120 kapsula';
    }
    $sections[] = 'Deklarirana primjena, samo ako nema sukoba s vašim stanjem i terapijom: ' . implode('; ', $usage) . '.';
    $sections[] = 'Količina za 30 dana pri punoj deklariranoj primjeni: ' . implode(' te ', $quantity) . '. To je izračun količine prema etiketi. To nije terapijski protokol.';

    if(!$hasArctic && isset(array_fill_keys(fcc_ai_v2_unique_strings($constraints, 30), true)['allergen:fish'])) {
        $sections[] = 'Forever Arctic Sea izostavljen je jer ste naveli alergiju na ribu, lignju ili srodne sastojke.';
    }
    if(!$hasAloe && isset(array_fill_keys(fcc_ai_v2_unique_strings($constraints, 30), true)['allergen:aloe'])) {
        $sections[] = 'Forever Aloe Vera Gel izostavljen je jer ste naveli alergiju na aloju.';
    }

    $sections[] = 'Prije početka treba provjeriti trudnoću ili dojenje, lijekove za razrjeđivanje krvi, šećer ili srčani ritam, alergiju na ribu, lignju ili aloju, skoru operaciju te cirozu, hepatitis ili jako povišene jetrene nalaze. Kod žutice, novog oticanja trbuha ili nogu, zbunjenosti, povraćanja krvi ili crne stolice potrebna je hitna medicinska procjena.';
    $sections[] = 'Napomena: ovo su potporne nutritivne opcije i informacije s deklaracije, ne dijagnoza ni liječenje masne jetre. Nemojte prekidati ili mijenjati propisanu terapiju.';

    return implode("\n\n", $sections);
}

function fcc_ai_v2_doctor_first_text(
    string $language,
    array $reasonCodes = [],
    ?array $supportiveProductKeys = null,
    array $constraints = []
): string {
    $reason = (string) ($reasonCodes[0] ?? 'medical_or_special_population');
    if($language === 'hr') {
        if(in_array('medical_followup_context', $reasonCodes, true)) {
            if(in_array('diagnosed_hypertension', $reasonCodes, true)) {
                return 'Za visoki tlak neću odabrati jedan Forever proizvod kao terapiju. Za korisnu neutralnu usporedbu napišite koje lijekove uzimate te imate li bolest srca, bubrega ili jetre. Nakon toga mogu usporediti deklaracije Forever ARGI+ (L-arginin), Forever Arctic Sea (riblje ulje) i Forever Cardiohealth (koenzim Q10) te izdvojiti samo opciju koja nema očit sukob s navedenim podacima — bez tvrdnje da snižava tlak ili zamjenjuje terapiju.';
            }
            return 'Ne mogu sigurno preporučiti Forever proizvod kao odgovor na ovaj medicinski problem. Ostajem pri stručnoj procjeni; nakon dijagnoze i uputa liječnika mogu provjeriti samo neutralne podatke i prikladnost točno navedenog proizvoda.';
        }
        if(in_array('persisted_medical_context', $reasonCodes, true)) {
            return 'Ranije ste naveli trudnoću, terapiju ili drugu posebnu zdravstvenu okolnost. Dok jasno ne potvrdite da to više nije aktualno, neću procijeniti proizvod kao dobar izbor; mogu provjeriti samo njegov sastav, alergene i deklaraciju.';
        }
        if(in_array('diagnosed_fatty_liver', $reasonCodes, true)) {
            $fattyLiverProductKeys = $supportiveProductKeys
                ?? fcc_ai_v2_supportive_reference_product_keys($reasonCodes);
            return fcc_ai_v2_fatty_liver_support_text(
                array_values($fattyLiverProductKeys),
                $constraints
            );
        }
        $messages = [
            'oncology_medication' => 'Uz kemoterapiju ili onkološku terapiju svaki dodatak mora prethodno odobriti onkolog ili klinički farmaceut koji vidi cijelu terapiju. Nemojte započinjati navedene proizvode bez te provjere.',
            'pregnancy_medication' => 'U trudnoći uz terapiju kompatibilnost dodatka treba potvrditi ginekolog ili ljekarnik koji zna točne lijekove i sastav proizvoda. Nemojte određivati dozu bez te provjere.',
            'child_medication' => 'Za dijete s alergijama ili terapijom proizvod mora prije uporabe provjeriti pedijatar ili ljekarnik prema točnom sastavu i lijeku. Nemojte ga uvoditi na svoju ruku.',
            'child_product_review' => 'Za dijete se dodatak prehrani ili drugi proizvod ne treba birati samo prema prodajnoj preporuci. Dob, prehranu, alergije, moguće lijekove i točnu deklaraciju prvo treba provjeriti s pedijatrom ili ljekarnikom; zato ovdje neću prikazati kupovnu preporuku.',
            'sedative_interaction' => 'Uz terapiju za spavanje melatonin treba provjeriti s liječnikom ili ljekarnikom zbog mogućeg zbrajanja sedativnog učinka. Nemojte ga dodavati bez pregleda točne terapije.',
            'medication_compatibility' => 'Kompatibilnost proizvoda s navedenim lijekovima mora potvrditi liječnik ili ljekarnik koji poznaje vašu terapiju. Nemojte započinjati proizvod prije te provjere.',
            'supplement_combination' => 'Neću potvrditi da se dva dodatka mogu uzimati zajedno bez provjere njihovih aktualnih deklaracija, doza i vaše terapije. To provjerite s liječnikom ili ljekarnikom; proizvode ne treba automatski vezati uz prethodni program.',
            'fertility_evaluation' => 'Kod neplodnosti prvi korak je stručna obrada oba partnera kod ginekologa ili androloga. Forever proizvod nije odgovor na uzrok problema i zato ga ovdje neću preporučiti.',
            'acute_tendon_injury' => 'Upaljenu tetivu prvo treba stručno procijeniti liječnik ili fizioterapeut, osobito nakon ozljede, uz jaču bol, otok ili slabost. Neću nuditi dodatke kao liječenje tetive.',
            'health_claim_immunity' => 'Neću preporučiti Forever proizvod kao način jačanja imuniteta, sprječavanja prehlade ili liječenja gripe. Mogu vam korisno usporediti točno deklarirane vitamine, minerale, alergene i način uporabe proizvoda, bez obećanja zdravstvenog ishoda.',
            'health_claim_weight_loss' => 'Neću obećati mršavljenje, smanjenje apetita ili sagorijevanje masti na temelju Forever proizvoda. Mogu usporediti deklarirani sastav, upozorenja i službeni program uporabe, ali bez jamčenja rezultata.',
            'health_claim_hormonal' => 'Neću preporučiti Forever proizvod za hormone, libido, prostatu, menopauzu ili testosteron. Mogu provjeriti samo deklarirani sastav i upozorenja točno navedenog proizvoda.',
            'health_claim_circulation' => 'Neću preporučiti Forever proizvod za krvni tlak, cirkulaciju ili krvne žile. Mogu usporediti točno deklarirane sastojke i upozorenja, ali proizvod nije odgovor na kardiovaskularni problem.',
            'health_claim_digestive' => 'Neću odabrati Forever proizvod kao liječenje zatvora, nadutosti ili želučanih tegoba. Mogu usporediti deklarirani sastav i način uporabe točno navedenih napitaka ili dodataka, bez obećanja probavnog ishoda.',
            'health_claim_sleep_stress' => 'Neću preporučiti Forever proizvod kao liječenje nesanice ili stresa. Mogu provjeriti deklarirani sastav i upozorenja točno navedenog proizvoda, osobito ako već uzimate terapiju.',
            'health_claim_pain_inflammation' => 'Neću preporučiti Forever proizvod za liječenje boli, upale, artritisa, psorijaze ili gihta. Za to je potrebna odgovarajuća stručna procjena; mogu provjeriti samo neutralne deklarirane činjenice o točno navedenom proizvodu.',
            'suspected_foot_fungal_infection' => 'Gljivice na stopalima ili noktima treba procijeniti ljekarnik ili liječnik kako bi se odabrao dokazani antifungalni postupak. U provjerenom Forever katalogu nemam proizvod koji smijem pošteno predstaviti kao antifungalno liječenje; Aloe sprej, gel ili druga kozmetika nisu zamjena za takav postupak i zato ovdje neću prikazati proizvod ni kupovni link. Nakon stručne procjene mogu odvojeno pomoći samo s uobičajenom kozmetičkom njegom zdrave, neoštećene kože, ako to zatražite kao novi cilj.',
            'diagnosed_hypertension' => 'Visoki krvni tlak treba potvrditi pravilnim ponovljenim mjerenjima i voditi s liječnikom; Forever proizvod nije terapija za hipertenziju i zato neću sastaviti prodajni paket „za tlak” niti nasumično ponuditi nepovezani dodatak. Nastavite propisanu terapiju. Ako je tlak 180/120 ili viši, ponovite mjerenje nakon najmanje jedne minute mirovanja i odmah kontaktirajte liječnika; uz bol u prsima, otežano disanje, slabost, utrnulost, promjenu vida ili govora nazovite 112. Ako želite neutralnu provjeru deklaracije, mogu odvojeno usporediti sastav i upozorenja Forever ARGI+ (L-arginin), Forever Arctic Sea (riblje ulje) i Forever Cardiohealth (koenzim Q10), ali tek uz podatak o lijekovima i nikada kao liječenje ili zamjenu za terapiju tlaka.',
            'diagnosed_metabolic_condition' => 'Dijabetes, inzulinska rezistencija te povišeni kolesterol ili trigliceridi zahtijevaju plan koji se temelji na nalazima, prehrani i mogućoj terapiji. Forever proizvod neću predstaviti kao liječenje ni kao način promjene nalaza; liječnik ili ljekarnik može provjeriti točan sastav dodatka uz vašu terapiju.',
            'diagnosed_skin_condition' => 'Akne, ekcem, dermatitis, psorijazu ili rozaceju treba razlikovati i liječiti prema stanju kože. Kozmetički Forever proizvod neću predstaviti kao terapiju; dermatolog ili ljekarnik može procijeniti aktivnu promjenu, a nakon toga mogu neutralno provjeriti sastav i upozorenja točno navedenog proizvoda.',
            'diagnosed_cardiovascular_condition' => 'Kod srčane bolesti, aritmije ili zatajenja srca dodatak može biti važan zbog mogućih interakcija s terapijom. Forever proizvod neću preporučiti za liječenje ili poboljšanje tog stanja; točan proizvod i cijelu terapiju treba provjeriti s liječnikom ili ljekarnikom.',
            'diagnosed_kidney_condition' => 'Kod bolesti ili smanjene funkcije bubrega dodaci mogu zahtijevati posebna ograničenja minerala, tekućine ili doze. Forever proizvod ovdje neću prodajno preporučiti; nefrolog, liječnik ili klinički farmaceut treba prvo pregledati nalaze, terapiju i točnu deklaraciju.',
            'diagnosed_liver_condition' => 'Kod bolesti jetre dodatak se ne treba uvoditi bez pregleda uzroka, nalaza i terapije. Forever proizvod neću predstaviti kao liječenje ili detoksikaciju; liječnik ili klinički farmaceut treba provjeriti točan sastav prije uporabe.',
            'diagnosed_endocrine_condition' => 'Bolesti štitnjače i druga endokrina stanja vode se prema nalazima i terapiji. Forever proizvod neću preporučiti za regulaciju hormona; točan sastav dodatka i vremenski razmak od lijekova treba provjeriti s liječnikom ili ljekarnikom.',
            'diagnosed_digestive_condition' => 'Crohnova bolest, ulcerozni kolitis, celijakija, gastritis, refluks i slična stanja ne rješavaju se prodajnim odabirom dodatka. Forever proizvod neću predstaviti kao liječenje; stručna procjena i provjera sastava važni su osobito kod aktivnih simptoma ili terapije.',
            'diagnosed_respiratory_condition' => 'Astma i kronične plućne bolesti zahtijevaju odgovarajuću procjenu i propisanu terapiju. Forever proizvod nije zamjena za inhalator ili drugi plan liječenja i zato ga ovdje neću prodajno preporučiti.',
            'musculoskeletal_symptoms' => 'Bolni zglobovi, artritis, artroza ili giht imaju različite uzroke i trebaju odgovarajuću procjenu. Forever proizvod neću predstaviti kao liječenje boli ili upale; liječnik ili ljekarnik može zatim provjeriti neutralne podatke o točno navedenom dodatku.',
            'active_or_suspected_infection' => 'Sumnju na aktivnu bakterijsku, gljivičnu ili drugu infekciju treba procijeniti liječnik ili ljekarnik i po potrebi liječiti dokazanim postupkom. Forever proizvod neću predstaviti kao liječenje infekcije niti ću prikazati kupovni link.',
            'high_blood_pressure_reading' => 'Ovo mjerenje nije prag hipertenzivne krize 180/120, ali je povišeno i nije razlog za uzimanje Forever proizvoda. Mirujte, pravilno ponovite mjerenje i dogovorite pravodobnu procjenu s liječnikom; uz bol u prsima, otežano disanje, slabost, utrnulost, promjenu vida ili govora nazovite 112.',
        ];
        if(isset($messages[$reason])) {
            return $messages[$reason];
        }
    }

    return fcc_ai_v2_localized_text($language, [
        'hr' => 'U ovoj situaciji neću preporučiti Forever proizvod bez procjene liječnika ili ljekarnika koji zna vašu dijagnozu i terapiju. Proizvod ne smije zamijeniti pregled ni propisanu terapiju.',
        'en' => in_array('persisted_medical_context', $reasonCodes, true)
            ? 'You previously mentioned pregnancy, medication or another special health circumstance. Until you clearly confirm that it no longer applies, I will not judge a product to be a good choice; I can only check its ingredients, allergens and label.'
            : 'I will not recommend a Forever product here without review by a doctor or pharmacist who knows your diagnosis and treatment. A product must not replace medical assessment or prescribed therapy.',
        'sl' => in_array('persisted_medical_context', $reasonCodes, true)
            ? 'Prej ste omenili nosečnost, zdravljenje ali drugo posebno zdravstveno okoliščino. Dokler jasno ne potrdite, da to ne velja več, izdelka ne bom ocenil kot dobro izbiro; preverim lahko le sestavo, alergene in deklaracijo.'
            : 'V tej situaciji ne bom priporočil izdelka Forever brez presoje zdravnika ali farmacevta, ki pozna vašo diagnozo in terapijo. Izdelek ne sme nadomestiti pregleda ali predpisanega zdravljenja.',
        'bg' => in_array('persisted_medical_context', $reasonCodes, true)
            ? 'По-рано споменахте бременност, лечение или друго специално здравословно обстоятелство. Докато не потвърдите ясно, че то вече не е актуално, няма да определя продукт като добър избор; мога само да проверя състава, алергените и етикета му.'
            : 'В тази ситуация няма да препоръчам продукт на Forever без преценка от лекар или фармацевт, който познава диагнозата и терапията ви. Продуктът не трябва да заменя преглед или предписано лечение.',
    ]);
}

function fcc_ai_v2_product_constraint_conflict_text(string $language, array $conflicts, array $rows): string {
    $grouped = [];
    foreach($conflicts as $conflict) {
        if(!is_array($conflict)) {
            continue;
        }
        $productKey = (string) ($conflict['product_key'] ?? '');
        if($productKey === '' || !isset($rows[$productKey])) {
            continue;
        }
        $grouped[$productKey][] = $conflict;
    }

    $allergenLabels = [
        'hr' => ['soy' => 'soju', 'fish' => 'ribu', 'milk' => 'mlijeko', 'egg' => 'jaja', 'tree_nuts' => 'orašaste plodove', 'gluten' => 'gluten', 'bee_products' => 'pčelinje sastojke'],
        'en' => ['soy' => 'soy', 'fish' => 'fish', 'milk' => 'milk', 'egg' => 'egg', 'tree_nuts' => 'tree nuts', 'gluten' => 'gluten', 'bee_products' => 'bee-derived ingredients'],
        'sl' => ['soy' => 'sojo', 'fish' => 'ribe', 'milk' => 'mleko', 'egg' => 'jajca', 'tree_nuts' => 'oreščke', 'gluten' => 'gluten', 'bee_products' => 'čebelje sestavine'],
        'bg' => ['soy' => 'соя', 'fish' => 'риба', 'milk' => 'мляко', 'egg' => 'яйца', 'tree_nuts' => 'ядки', 'gluten' => 'глутен', 'bee_products' => 'пчелни съставки'],
    ];
    $labels = $allergenLabels[$language] ?? array_map(static fn($text)=>fcc_t($text,[],$language),$allergenLabels['hr']);
    $absenceLabels = $language === 'hr'
        ? [
            'soy' => 'soje', 'fish' => 'ribe', 'milk' => 'mlijeka', 'egg' => 'jaja',
            'tree_nuts' => 'orašastih plodova', 'gluten' => 'glutena',
            'bee_products' => 'pčelinjih sastojaka',
        ]
        : $labels;
    $blocks = [];

    foreach($grouped as $productKey => $productConflicts) {
        $productName = (string) ($rows[$productKey]['canonical_name'] ?? $productKey);
        $allergens = [];
        $unconfirmedAllergens = [];
        $flags = [];
        $unconfirmedFlags = [];
        $suitability = [];
        $under18 = false;
        $under18Unknown = false;
        $unknown = false;
        foreach($productConflicts as $conflict) {
            $type = (string) ($conflict['conflict_type'] ?? '');
            $key = (string) ($conflict['constraint_key'] ?? '');
            if($type === 'allergen' && isset($labels[$key])) {
                $allergens[] = $labels[$key];
            } elseif($type === 'allergen_absence_unconfirmed' && isset($absenceLabels[$key])) {
                $unconfirmedAllergens[] = $absenceLabels[$key];
            } elseif($type === 'safety_flag') {
                $flags[] = $key;
            } elseif($type === 'safety_absence_unconfirmed') {
                $unconfirmedFlags[] = $key;
            } elseif($type === 'age_restriction') {
                $under18 = true;
            } elseif($type === 'age_suitability_unconfirmed') {
                $under18Unknown = true;
            } elseif($type === 'suitability_unconfirmed') {
                $suitability[] = $key;
            } elseif($type === 'safety_review_unknown') {
                $unknown = true;
            }
        }

        if($allergens) {
            $blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Prema provjerenoj službenoj deklaraciji, {product} sadrži {allergens}. Zbog navedenog ograničenja ovaj proizvod neću preporučiti.',
                'en' => 'According to the verified official label, ' . $productName . ' contains ' . implode(' and ', array_values(array_unique($allergens))) . '. I will not recommend it with the constraint you provided.',
                'sl' => 'Po preverjeni uradni deklaraciji ' . $productName . ' vsebuje ' . implode(' in ', array_values(array_unique($allergens))) . '. Zaradi navedene omejitve tega izdelka ne bom priporočil.',
                'bg' => 'Според проверения официален етикет ' . $productName . ' съдържа ' . implode(' и ', array_values(array_unique($allergens))) . '. Няма да го препоръчам при посоченото ограничение.',
            ],['{product}'=>$productName,'{allergens}'=>implode(fcc_t(' i ',[],$language),array_values(array_unique($allergens)))]);
        }
        if($unconfirmedAllergens) {
            $blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Za {product} nemam izričitu službenu potvrdu da je bez {allergens}. Zbog navedenog ograničenja neću pretpostaviti da je prikladan; provjerite aktualnu deklaraciju pakiranja.',
                'en' => 'I do not have explicit official confirmation that ' . $productName . ' is free from ' . implode(' and ', array_values(array_unique($unconfirmedAllergens))) . '. I will not assume it is suitable for that restriction; check the current package label.',
                'sl' => 'Za ' . $productName . ' nimam izrecne uradne potrditve, da ne vsebuje ' . implode(' in ', array_values(array_unique($unconfirmedAllergens))) . '. Ne bom domneval, da je primeren; preverite trenutno deklaracijo embalaže.',
                'bg' => 'Нямам изрично официално потвърждение, че ' . $productName . ' не съдържа ' . implode(' и ', array_values(array_unique($unconfirmedAllergens))) . '. Няма да предполагам, че е подходящ; проверете актуалния етикет.',
            ],['{product}'=>$productName,'{allergens}'=>implode(fcc_t(' i ',[],$language),array_values(array_unique($unconfirmedAllergens)))]);
        }
        if(in_array('caffeine', $flags, true) || in_array('green_tea', $flags, true)) {
            $flagLabel = in_array('caffeine', $flags, true)
                ? fcc_ai_v2_localized_text($language, ['hr' => 'kofein', 'en' => 'caffeine', 'sl' => 'kofein', 'bg' => 'кофеин'])
                : fcc_ai_v2_localized_text($language, ['hr' => 'zeleni čaj', 'en' => 'green tea', 'sl' => 'zeleni čaj', 'bg' => 'зелен чай']);
            $blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => '{product} ima službeno označen {flag}. Budući da ste naveli da ga izbjegavate ili ne podnosite, ovaj proizvod neću preporučiti.',
                'en' => $productName . ' has an official ' . $flagLabel . ' warning. Because you said you avoid or do not tolerate it, I will not recommend this product.',
                'sl' => $productName . ' ima uradno opozorilo za ' . $flagLabel . '. Ker ste navedli, da se mu izogibate ali ga ne prenašate, tega izdelka ne bom priporočil.',
                'bg' => $productName . ' има официално предупреждение за ' . $flagLabel . '. Тъй като посочихте, че го избягвате или не го понасяте, няма да препоръчам продукта.',
            ],['{product}'=>$productName,'{flag}'=>$flagLabel]);
        }
        if($unconfirmedFlags) {
            $blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Za {product} nemam izričitu službenu potvrdu da odgovara ograničenju koje ste naveli za kofein ili zeleni čaj. Neću pretpostaviti da je prikladan bez aktualne deklaracije.',
                'en' => 'I do not have explicit official confirmation that ' . $productName . ' meets the caffeine or green-tea restriction you provided. I will not assume suitability without the current label.',
                'sl' => 'Za ' . $productName . ' nimam izrecne uradne potrditve glede navedene omejitve kofeina ali zelenega čaja. Brez trenutne deklaracije ne bom domneval primernosti.',
                'bg' => 'Нямам изрично официално потвърждение, че ' . $productName . ' отговаря на посоченото ограничение за кофеин или зелен чай. Няма да предполагам без актуален етикет.',
            ],['{product}'=>$productName]);
        }
        if($under18) {
            $blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Službena uputa za {product} navodi da proizvod nije namijenjen osobama mlađim od 18 godina. Zato ga za navedenu dob neću preporučiti.',
                'en' => 'The official instructions for ' . $productName . ' state that it is not intended for people under 18. I will not recommend it for the age provided.',
                'sl' => 'Uradna navodila za ' . $productName . ' navajajo, da izdelek ni namenjen mlajšim od 18 let. Za navedeno starost ga ne bom priporočil.',
                'bg' => 'Официалните указания за ' . $productName . ' посочват, че продуктът не е предназначен за лица под 18 години. Няма да го препоръчам за посочената възраст.',
            ],['{product}'=>$productName]);
        }
        if($under18Unknown) {
            $blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Za {product} nemam izričitu službenu potvrdu prikladnosti za navedenu maloljetnu dob. Zato ga neću preporučiti za dijete bez provjere aktualne deklaracije i stručne procjene.',
                'en' => 'I do not have explicit official confirmation that ' . $productName . ' is suitable for the minor age provided. I will not recommend it for a child without checking the current label and obtaining professional guidance.',
                'sl' => 'Za ' . $productName . ' nimam izrecne uradne potrditve primernosti za navedeno mladoletno starost. Za otroka ga ne bom priporočil brez preverjanja trenutne deklaracije in strokovne presoje.',
                'bg' => 'Нямам изрично официално потвърждение, че ' . $productName . ' е подходящ за посочената непълнолетна възраст. Няма да го препоръчам за дете без актуален етикет и професионална преценка.',
            ],['{product}'=>$productName]);
        }
        foreach(array_values(array_unique($suitability)) as $suitabilityKey) {
            $suitabilityLabel = [
                'vegan' => ['hr' => 'veganski', 'en' => 'vegan', 'sl' => 'veganski', 'bg' => 'веган'],
                'vegetarian' => ['hr' => 'vegetarijanski', 'en' => 'vegetarian', 'sl' => 'vegetarijanski', 'bg' => 'вегетариански'],
                'gluten_free' => ['hr' => 'bez glutena', 'en' => 'gluten-free', 'sl' => 'brez glutena', 'bg' => 'без глутен'],
            ][$suitabilityKey] ?? ['hr' => $suitabilityKey, 'en' => $suitabilityKey];
            $blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Za {product} nemam završenu točnu službenu potvrdu da je {suitability}. Zato ga za tu prehrambenu preferenciju neću preporučiti niti ću status zaključiti iz popisa sastojaka.',
                'en' => 'I do not have a completed exact official confirmation that ' . $productName . ' is ' . ($suitabilityLabel['en'] ?? $suitabilityKey) . '. I will not recommend it for that dietary preference or infer the status from an ingredient list.',
                'sl' => 'Za ' . $productName . ' nimam zaključene natančne uradne potrditve, da je ' . ($suitabilityLabel['sl'] ?? $suitabilityKey) . '. Za to prehransko izbiro ga ne bom priporočil niti statusa sklepal iz seznama sestavin.',
                'bg' => 'Нямам завършено точно официално потвърждение, че ' . $productName . ' е ' . ($suitabilityLabel['bg'] ?? $suitabilityKey) . '. Няма да го препоръчам за това хранително предпочитание или да извеждам статуса от списъка със съставки.',
            ],['{product}'=>$productName,'{suitability}'=>fcc_ai_v2_localized_text($language,$suitabilityLabel)]);
        }
        if($unknown) {
            $blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Za {product} još nemam završenu službenu sigurnosnu provjeru u odnosu na ograničenje koje ste naveli. Zato ga neću označiti sigurnim niti preporučiti.',
                'en' => 'The official safety review for ' . $productName . ' is not complete for the constraint you provided. I will not label it safe or recommend it.',
                'sl' => 'Uradni varnostni pregled za ' . $productName . ' glede navedene omejitve še ni zaključen. Izdelka ne bom označil kot varnega niti ga priporočil.',
                'bg' => 'Официалната проверка за безопасност на ' . $productName . ' спрямо посоченото ограничение още не е завършена. Няма да го определя като безопасен или да го препоръчам.',
            ],['{product}'=>$productName]);
        }
    }

    return trim(implode("\n\n", array_values(array_unique($blocks))));
}

function fcc_ai_v2_validate_provider_candidate(
    array $plan,
    array $candidate,
    array $catalog,
    bool $serverOwnedText = false
): array {
    $errors = [];
    $rows = fcc_ai_v2_catalog_rows($catalog);
    $allowed = array_fill_keys(fcc_ai_v2_unique_strings($plan['allowed_product_keys'] ?? []), true);
    $primaryAllowedValues = fcc_ai_v2_unique_strings(
        array_key_exists('primary_allowed_product_keys', $plan)
            ? ($plan['primary_allowed_product_keys'] ?? [])
            : ($plan['allowed_product_keys'] ?? [])
    );
    $primaryAllowed = array_fill_keys($primaryAllowedValues, true);
    $rejected = array_fill_keys(fcc_ai_v2_unique_strings($plan['rejected_product_keys'] ?? []), true);
    $action = (string) ($plan['decision']['action'] ?? 'clarify');
    $allow_products = !empty($plan['safety']['allow_products']);
    $primary = trim((string) ($candidate['primary_product_key'] ?? ''));
    $all_support = fcc_ai_v2_unique_strings($candidate['support_product_keys'] ?? []);
    $support = array_slice($all_support, 0, 2);
    $selected_for_validation = array_values(array_filter(array_merge([$primary], $all_support)));
    $selected = array_values(array_filter(array_merge([$primary], $support)));

    if(isset($candidate['language']) && (string) $candidate['language'] !== (string) ($plan['language'] ?? 'hr')) {
        $errors[] = 'language_mismatch';
    }

    /* Matching, safety, market and conversation memory are server-owned even
     * when the pure core is called without the provider client adapter. */
    if(isset($candidate['state_patch'])) {
        if(!is_array($candidate['state_patch'])) {
            $errors[] = 'provider_state_patch_invalid';
        } else {
            $statePatch = $candidate['state_patch'];
            $allowedStatePatchFields = [
                'goal_keys', 'subject', 'mentioned_product_keys',
                'rejected_product_keys', 'pending_question',
                'clear_pending_question',
            ];
            if(array_diff(array_keys($statePatch), $allowedStatePatchFields)) {
                $errors[] = 'provider_state_patch_unknown_field';
            }
            if(
                !empty($statePatch['goal_keys'])
                || ($statePatch['subject'] ?? null) !== null
                || !empty($statePatch['mentioned_product_keys'])
                || !empty($statePatch['rejected_product_keys'])
                || ($statePatch['pending_question'] ?? null) !== null
                || !empty($statePatch['clear_pending_question'])
            ) {
                $errors[] = 'provider_state_patch_not_empty';
            }
        }
    }

    if(!$allow_products || in_array($action, ['clarify', 'urgent_triage', 'doctor_first', 'small_talk', 'redirect', 'content_creation'], true)) {
        if($selected) {
            $errors[] = 'products_not_allowed_for_action';
        }
    }

    if(count($all_support) > 2) {
        $errors[] = 'too_many_support_products';
    }

    if($primary !== '' && !isset($primaryAllowed[$primary])) {
        $errors[] = 'primary_product_not_ranked:' . $primary;
    }

    foreach($selected_for_validation as $key) {
        if(!isset($rows[$key])) {
            $errors[] = 'unknown_product:' . $key;
        } elseif(!isset($allowed[$key])) {
            $errors[] = 'product_outside_allowed_set:' . $key;
        }
        if(isset($rejected[$key])) {
            $errors[] = 'rejected_product_selected:' . $key;
        }
    }

    if($support && $primary === '') {
        $errors[] = 'support_without_primary';
    }

    if($primary !== '' && in_array($primary, $support, true)) {
        $errors[] = 'primary_repeated_as_support';
    }

    $queryFocus = (string) ($plan['trace']['query_focus'] ?? '');
    $availableSupportRelationships = array_values(array_filter(
        (array) ($plan['support_relationships'] ?? []),
        static function($relationship) use ($queryFocus): bool {
            if(!is_array($relationship)) {
                return false;
            }
            $relationType = (string) ($relationship['relation_type'] ?? '');
            return $relationType === 'complements'
                || ($relationType === 'sequence' && $queryFocus === 'routine_sequence');
        }
    ));
    if(
        in_array($queryFocus, ['compatibility', 'routine_sequence'], true)
        && $availableSupportRelationships
        && $primary !== ''
    ) {
        $relationshipsForPrimary = array_values(array_filter(
            $availableSupportRelationships,
            static fn(array $relationship): bool => (string) ($relationship['source_product_key'] ?? '') === $primary
        ));
        if(!$relationshipsForPrimary) {
            $errors[] = 'primary_has_no_verified_requested_relationship:' . $primary;
        } elseif(!$support) {
            $errors[] = 'requested_relationship_not_selected';
        }
    }
    $selectedSupportRelationships = [];
    $supportReasonOwners = [];
    foreach($support as $supportProductKey) {
        $matchedRelationship = null;
        foreach($availableSupportRelationships as $relationship) {
            if(
                (string) ($relationship['source_product_key'] ?? '') === $primary
                && (string) ($relationship['target_product_key'] ?? '') === $supportProductKey
                && !empty($relationship['source_refs'])
                && trim((string) ($relationship['reason_text'] ?? '')) !== ''
                && trim((string) ($relationship['matched_goal_key'] ?? '')) !== ''
            ) {
                $matchedRelationship = $relationship;
                break;
            }
        }

        if(!$matchedRelationship) {
            $errors[] = 'support_without_verified_complement:' . $supportProductKey;
            continue;
        }

        $reasonIdentity = fcc_ai_v2_normalize_text((string) $matchedRelationship['reason_text']);
        if($reasonIdentity === '' || isset($supportReasonOwners[$reasonIdentity])) {
            $errors[] = 'support_reason_not_distinct:' . $supportProductKey;
            continue;
        }
        $supportReasonOwners[$reasonIdentity] = $supportProductKey;
        $selectedSupportRelationships[] = $matchedRelationship;
    }

    if($support && empty($plan['trace']['support_requested'])) {
        $errors[] = 'support_not_requested_or_goal_matched';
    } elseif(
        $support
        && (string) ($plan['intent'] ?? '') !== 'recommendation'
        && !in_array($queryFocus, ['compatibility', 'routine_sequence'], true)
    ) {
        $errors[] = 'support_not_requested_or_goal_matched';
    }

    $fact_index = fcc_ai_v2_fact_index($rows);
    $all_fact_ids = fcc_ai_v2_unique_strings($candidate['fact_ids'] ?? []);
    if(count($all_fact_ids) > 8) {
        $errors[] = 'too_many_fact_ids';
    }
    foreach($all_fact_ids as $fact_id) {
        if(!isset($fact_index[$fact_id])) {
            $errors[] = 'unknown_fact:' . $fact_id;
            continue;
        }
        if(!in_array($fact_index[$fact_id]['product_key'], $selected_for_validation, true)) {
            $errors[] = 'fact_for_unselected_product:' . $fact_id;
        }
    }

    $provider_text = implode(' ', [
        (string) ($candidate['content'] ?? ''),
        (string) ($candidate['acknowledgement'] ?? ''),
        (string) ($candidate['explanation'] ?? ''),
        (string) ($candidate['clarifying_question'] ?? ''),
    ]);
    /* These prose checks are a trust boundary for model output. Server-owned
     * planner copy is deterministic application code and is covered by its
     * own exact-response regressions; running provider heuristics over it can
     * incorrectly reject harmless words such as "postići" or reviewed product
     * comparisons that intentionally have no selected sales card. */
    if($provider_text !== '' && !$serverOwnedText) {
        $subjectTextKeys = fcc_ai_v2_unique_strings($plan['trace']['subject_product_keys'] ?? [], 3);
        $textAllowedKeys = array_fill_keys(array_values(array_unique(array_merge($selected, $subjectTextKeys))), true);
        if(fcc_ai_v2_contains_any($provider_text, ['preporuku zadrzati jasnom', 'ako zelite preporuku zadrzati jasnom'])) {
            $errors[] = 'generic_recommendation_tail';
        }
        if(preg_match('/https?:\/\/|www\./iu', $provider_text) === 1) {
            $errors[] = 'provider_text_contains_url';
        }
        $allowedNumericFacts = [];
        foreach($all_fact_ids as $factId) {
            if(isset($fact_index[$factId])) {
                $allowedNumericFacts[] = (string) ($fact_index[$factId]['text'] ?? '');
            }
        }
        $providerPolicyViolation = fcc_ai_v2_provider_policy_violation($provider_text, $allowedNumericFacts);
        if($providerPolicyViolation !== '') {
            $errors[] = 'provider_policy_violation:' . $providerPolicyViolation;
        }
        $providerAcknowledgement = trim((string) ($candidate['acknowledgement'] ?? ''));
        if(substr_count($providerAcknowledgement, '?') > 0) {
            $errors[] = 'provider_acknowledgement_contains_question';
        }
        if(
            $providerAcknowledgement !== ''
            && in_array($action, ['answer', 'factual_lookup'], true)
        ) {
            $acknowledgementViolation = fcc_ai_v2_provider_answer_acknowledgement_violation(
                $providerAcknowledgement,
                (string) ($plan['language'] ?? 'hr')
            );
            if($acknowledgementViolation !== '') {
                $errors[] = 'provider_answer_acknowledgement_' . $acknowledgementViolation;
            }
        }
        if($action === 'clarify') {
            $providerQuestion = trim((string) ($candidate['clarifying_question'] ?? ''));
            if(substr_count($providerQuestion, '?') > 1) {
                $errors[] = 'provider_clarifying_question_count';
            }
        }
        /* The alias gate protects model prose from smuggling an unselected
         * product into the answer. Deterministic server copy is authored and
         * reviewed separately and may neutrally compare named alternatives
         * while intentionally keeping every product unselected (for example
         * the C9/F15/Plant Protein weight-management safety screen). */
        if(!$serverOwnedText) {
            foreach($rows as $product_key => $row) {
                if(isset($textAllowedKeys[$product_key])) {
                    continue;
                }
                foreach($row['aliases'] as $alias) {
                    $normalizedAlias = fcc_ai_v2_normalize_text((string) $alias);
                    if(
                        fcc_ai_v2_is_ambiguous_nutrient_alias($normalizedAlias)
                        && !fcc_ai_v2_contains_phrase($provider_text, 'forever ' . $normalizedAlias)
                    ) {
                        continue;
                    }
                    if(mb_strlen($normalizedAlias) >= 7 && fcc_ai_v2_contains_phrase($provider_text, (string) $alias)) {
                        $errors[] = 'unselected_product_in_text:' . $product_key;
                        break;
                    }
                }
            }
        }
    }

    return [
        'valid' => !$errors,
        'errors' => array_values(array_unique($errors)),
        'selected_product_keys' => $selected,
        'support_relationships' => $selectedSupportRelationships,
    ];
}

function fcc_ai_v2_default_candidate(array $plan): array {
    $allowed = fcc_ai_v2_unique_strings(
        $plan['primary_allowed_product_keys'] ?? $plan['allowed_product_keys'] ?? [],
        3
    );
    $action = (string) ($plan['decision']['action'] ?? 'clarify');
    $primary = in_array($action, ['answer', 'factual_lookup'], true) ? (string) ($allowed[0] ?? '') : '';
    $supportProductKeys = [];
    if($primary !== '' && !empty($plan['trace']['support_requested'])) {
        foreach((array) ($plan['support_relationships'] ?? []) as $relationship) {
            if(
                !is_array($relationship)
                || (string) ($relationship['source_product_key'] ?? '') !== $primary
            ) {
                continue;
            }
            $targetProductKey = trim((string) ($relationship['target_product_key'] ?? ''));
            if($targetProductKey === '' || in_array($targetProductKey, $supportProductKeys, true)) {
                continue;
            }
            $supportProductKeys[] = $targetProductKey;
            if(count($supportProductKeys) >= 2) {
                break;
            }
        }
    }

    $intentMap = [
        'product_question' => 'product_information',
        'direct_product' => 'product_information',
        'comparison' => 'product_information',
        'recommendation' => 'product_recommendation',
        'content_creation' => 'unsupported',
        'business' => 'business',
        'small_talk' => 'small_talk',
    ];
    $decisionMap = [
        'answer' => 'recommend',
        'factual_lookup' => 'inform',
        'clarify' => 'clarify',
        'doctor_first' => 'refer',
        'urgent_triage' => 'refer',
        'content_creation' => 'inform',
        'business_guidance' => 'refer',
        'small_talk' => 'inform',
    ];
    $serverSafety = (string) ($plan['safety']['level'] ?? 'routine');

    return [
        'language' => (string) ($plan['language'] ?? 'hr'),
        'intent' => (string) ($intentMap[(string) ($plan['intent'] ?? '')] ?? 'unsupported'),
        'safety_level' => in_array($serverSafety, ['urgent', 'emergency'], true) ? 'urgent' : ($serverSafety === 'routine' ? 'normal' : 'caution'),
        'decision' => (string) ($decisionMap[$action] ?? 'no_product'),
        'primary_product_key' => $primary,
        'support_product_keys' => $supportProductKeys,
        'fact_ids' => [],
        'acknowledgement' => '',
        'explanation' => '',
        'clarifying_question' => (string) ($plan['decision']['clarifying_question'] ?? ''),
        'lead_capture' => [
            'recommended' => false,
            'lead_type' => 'product_interest',
            'headline' => '',
            'text' => '',
        ],
        'state_patch' => [
            'goal_keys' => [],
            'subject' => null,
            'mentioned_product_keys' => [],
            'rejected_product_keys' => [],
            'pending_question' => null,
            'clear_pending_question' => false,
        ],
    ];
}

function fcc_ai_v2_finalize_turn(array $plan, array $provider_candidate, array $catalog): array {
    $rows = fcc_ai_v2_catalog_rows($catalog);
    $language = (string) ($plan['language'] ?? 'hr');
    $action = (string) ($plan['decision']['action'] ?? 'clarify');
    $serverOwnedPackageContents = (string) ($plan['trace']['query_focus'] ?? '') === 'package_contents'
        && !empty($plan['program_member_relationships']);
    $serverOwnedCandidate = $serverOwnedPackageContents || !$provider_candidate;
    $candidate = !$serverOwnedCandidate
        ? $provider_candidate
        : fcc_ai_v2_default_candidate($plan);
    $validation = fcc_ai_v2_validate_provider_candidate($plan, $candidate, $rows, $serverOwnedCandidate);
    $replayNarrative = '';

    if(!$validation['valid']) {
        $providerValidation = $validation;
        $candidate = fcc_ai_v2_default_candidate($plan);
        $fallback_validation = fcc_ai_v2_validate_provider_candidate($plan, $candidate, $rows, true);
        $validation = $fallback_validation;
        $validation['fallback_used'] = true;
        $validation['provider_errors'] = array_values((array) ($providerValidation['errors'] ?? []));
        $selected = $fallback_validation['selected_product_keys'];
    } else {
        $validation['fallback_used'] = false;
        $selected = $validation['selected_product_keys'];
        if(defined('FCC_AI_V2_DETERMINISTIC') && isset($candidate['content']) && is_scalar($candidate['content'])) {
            $replayNarrative = fcc_ai_v2_clean_provider_text((string) $candidate['content'], 1600);
        }
    }

    $primary = (string) ($selected[0] ?? '');
    $support = array_values(array_slice($selected, 1, 2));
    $selectedSupportRelationships = array_values((array) ($validation['support_relationships'] ?? []));
    $supportRelationshipByTarget = [];
    foreach($selectedSupportRelationships as $selectedSupportRelationship) {
        if(!is_array($selectedSupportRelationship)) {
            continue;
        }
        $targetProductKey = (string) ($selectedSupportRelationship['target_product_key'] ?? '');
        if($targetProductKey !== '') {
            $supportRelationshipByTarget[$targetProductKey] = $selectedSupportRelationship;
        }
    }
    $content_blocks = [];

    if($action === 'urgent_triage') {
        /* Safety routing is server-owned; provider prose can never replace it. */
        $content_blocks[] = fcc_ai_v2_safety_text(
            $language,
            (string) ($plan['safety']['level'] ?? 'urgent'),
            (array) ($plan['safety']['reason_codes'] ?? [])
        );
        $primary = '';
        $support = [];
        $selected = [];
    } elseif($action === 'doctor_first') {
        /* Claim and clinician-review boundaries are deterministic as well. */
        $doctorFirstReasonCodes = (array) ($plan['safety']['reason_codes'] ?? []);
        $doctorFirstConstraints = (array) ($plan['user_constraints'] ?? []);
        $doctorFirstSupportiveKeys = array_values(array_filter(
            fcc_ai_v2_supportive_reference_product_keys($doctorFirstReasonCodes),
            static fn(string $productKey): bool => !fcc_ai_v2_supportive_product_blocked_by_constraints(
                $productKey,
                $doctorFirstConstraints
            )
        ));
        $content_blocks[] = fcc_ai_v2_doctor_first_text(
            $language,
            $doctorFirstReasonCodes,
            $doctorFirstSupportiveKeys,
            $doctorFirstConstraints
        );
        $primary = '';
        $support = [];
        $selected = [];
    } elseif(!empty($plan['product_constraint_conflicts'])) {
        $content_blocks[] = fcc_ai_v2_product_constraint_conflict_text(
            $language,
            (array) $plan['product_constraint_conflicts'],
            $rows
        );
        $primary = '';
        $support = [];
        $selected = [];
    } elseif($action === 'small_talk') {
        if($replayNarrative !== '') {
            $content_blocks[] = $replayNarrative;
        } elseif(($plan['trace']['small_talk_kind'] ?? '') === 'thanks') {
            $content_blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Nema na čemu, drago mi je ako je pomoglo.',
                'en' => 'You are welcome. I am glad that helped.',
                'sl' => 'Ni za kaj, veseli me, če je pomagalo.',
                'bg' => 'Няма защо. Радвам се, че помогнах.',
            ]);
        } else {
            $content_blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Pozdrav! Opišite što želite postići ili koji problem želite riješiti — ne trebate znati naziv Forever proizvoda.',
                'en' => 'Hello! Tell me your main goal or the name of the Forever product you want to check.',
                'sl' => 'Pozdravljeni! Povejte mi svoj glavni cilj ali ime izdelka Forever, ki vas zanima.',
                'bg' => 'Здравейте! Кажете ми основната си цел или името на продукта Forever, който ви интересува.',
            ]);
        }
    } elseif($action === 'business_guidance') {
        /* Business facts and consent framing are server-owned. Provider prose
         * must not invent earnings, terms, pressure or a nonexistent advisor. */
        $businessModelQuestion = in_array(
            'business_model_transparency',
            (array) ($plan['decision']['reason_codes'] ?? []),
            true
        );
        $businessTopic = (string) ($plan['trace']['business_topic'] ?? '');
        $businessMarket = mb_strtoupper(trim((string) ($plan['trace']['market'] ?? '')), 'UTF-8');
        $businessOffer = fcc_ai_v2_business_offer_context($catalog, $businessMarket !== '' ? $businessMarket : 'HR');
        $businessMessagesHr = is_array($businessOffer['messages_hr'] ?? null)
            ? $businessOffer['messages_hr']
            : [];
        if($businessModelQuestion) {
            $content_blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Razumijem pitanje o MLM-u ili "piramidi". Ovdje se suradnja opisuje kao samostalna aktivnost povezana s preporukom i prodajom proizvoda te razvojem klijenata i, po osobnom izboru, tima. Zarada nije zajamčena i to nije obećanje lakog prihoda. Za procjenu modela treba provjeriti aktualne službene uvjete, naknade, moguće troškove, povrate i pravila, koje može objasniti vlasnik ove aplikacije. Razgovor s vlasnikom i ostavljanje kontakta potpuno su dobrovoljni.',
                'en' => 'I understand the question about MLM or a "pyramid". Here, collaboration is described as independent activity connected to recommending and selling products and developing customers and, by personal choice, a team. Earnings are not guaranteed and this is not a promise of easy income. Assessing the model requires checking the current official terms, compensation, possible costs, returns and rules, which the owner of this app can explain. Speaking with the owner and leaving contact details are entirely voluntary.',
                'sl' => 'Razumem vprašanje o MLM-u ali "piramidi". Sodelovanje je tukaj opisano kot samostojna dejavnost, povezana s priporočanjem in prodajo izdelkov ter razvojem strank in, po osebni izbiri, ekipe. Zaslužek ni zagotovljen in to ni obljuba lahkega prihodka. Za presojo modela je treba preveriti trenutne uradne pogoje, nadomestila, morebitne stroške, vračila in pravila, ki jih lahko pojasni lastnik te aplikacije. Pogovor z lastnikom in posredovanje kontakta sta povsem prostovoljna.',
                'bg' => 'Разбирам въпроса за MLM или „пирамида“. Тук сътрудничеството е описано като самостоятелна дейност, свързана с препоръчване и продажба на продукти и развитие на клиенти и, по личен избор, екип. Печалбата не е гарантирана и това не е обещание за лесен доход. За оценка на модела трябва да се проверят актуалните официални условия, възнаграждения, възможни разходи, връщания и правила, които собственикът на това приложение може да обясни. Разговорът със собственика и оставянето на контакт са напълно доброволни.',
            ]);
        } elseif($language === 'hr' && $businessMessagesHr) {
            $businessMessageKey = isset($businessMessagesHr[$businessTopic])
                ? $businessTopic
                : 'opportunity';
            foreach((array) ($businessMessagesHr[$businessMessageKey] ?? []) as $businessMessage) {
                if(is_scalar($businessMessage) && trim((string) $businessMessage) !== '') {
                    $content_blocks[] = trim((string) $businessMessage);
                }
            }
        } else {
            $content_blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Forever poslovna suradnja ovdje znači samostalnu aktivnost povezanu s preporukom i prodajom proizvoda te razvojem klijenata i, po osobnom izboru, tima. Aktualne uvjete može objasniti vlasnik ove aplikacije.',
                'en' => 'Forever business collaboration here means independent activity connected to recommending and selling products and developing customers and, by personal choice, a team. Earnings are not guaranteed and this is not a promise of easy income. The current official terms, obligations and working model can be explained by the owner of this app. Speaking with the owner and leaving contact details are entirely voluntary.',
                'sl' => 'Poslovno sodelovanje Forever tukaj pomeni samostojno dejavnost, povezano s priporočanjem in prodajo izdelkov ter razvojem strank in, po osebni izbiri, ekipe. Zaslužek ni zagotovljen in to ni obljuba lahkega prihodka. Trenutne uradne pogoje, obveznosti in način dela lahko pojasni lastnik te aplikacije. Pogovor z lastnikom in posredovanje kontakta sta povsem prostovoljna.',
                'bg' => 'Бизнес сътрудничеството с Forever тук означава самостоятелна дейност, свързана с препоръчване и продажба на продукти и развитие на клиенти и, по личен избор, екип. Печалбата не е гарантирана и това не е обещание за лесен доход. Актуалните официални условия, задължения и начин на работа могат да бъдат обяснени от собственика на това приложение. Разговорът със собственика и оставянето на контакт са напълно доброволни.',
            ]);
        }
        $businessDisclaimerHr = trim((string) ($businessMessagesHr['disclaimer'] ?? ''));
        $content_blocks[] = fcc_ai_v2_localized_text($language, [
            'hr' => $businessDisclaimerHr !== ''
                ? $businessDisclaimerHr
                : 'Aktualne uvjete, troškove, kvalifikaciju i dostupnost paketa treba provjeriti u službenom Forever toku. Zarada nije zajamčena, a FCC pristup nije automatsko Forever pravo. Razgovor s mentorom i ostavljanje kontakta potpuno su dobrovoljni.',
            'en' => 'Important: Forever does not guarantee income or success. Results depend on real sales, personal work, skills, and market conditions. Registration and purchase must use the official Forever flow; package price, contents, and availability must be checked before any decision.',
            'sl' => 'Pomembno: Forever ne zagotavlja zaslužka ali uspeha. Rezultati so odvisni od dejanske prodaje, osebnega dela, znanja in tržnih razmer. Registracija in nakup morata potekati po uradni poti Forever.',
            'bg' => 'Важно: Forever не гарантира доход или успех. Резултатите зависят от реални продажби, лична работа, умения и пазарни условия. Регистрацията и покупката трябва да минават през официалния процес на Forever.',
        ]);
    } elseif($action === 'redirect') {
        $content_blocks[] = $replayNarrative !== '' ? $replayNarrative : fcc_ai_v2_localized_text($language, [
            'hr' => 'Ovaj razgovor namijenjen je provjeri Forever proizvoda. Za drugu temu dostupne mogućnosti može objasniti vlasnik ove aplikacije; razgovor i ostavljanje kontakta potpuno su dobrovoljni.',
            'en' => 'This conversation is intended for checking Forever products. For another topic, the owner of this app can explain the available options; speaking with them and leaving contact details are entirely voluntary.',
            'sl' => 'Ta pogovor je namenjen preverjanju izdelkov Forever. Za drugo temo lahko razpoložljive možnosti pojasni lastnik te aplikacije; pogovor in posredovanje kontakta sta povsem prostovoljna.',
            'bg' => 'Този разговор е предназначен за проверка на продуктите на Forever. За друга тема собственикът на приложението може да обясни наличните възможности; разговорът и оставянето на контакт са напълно доброволни.',
        ]);
    } elseif($action === 'clarify') {
        $clarifyReasonCodes = fcc_ai_v2_unique_strings($plan['decision']['reason_codes'] ?? [], 8);
        $serverOwnedPolicyBoundary = (bool) array_filter(
            $clarifyReasonCodes,
            static fn(string $reasonCode): bool => str_starts_with($reasonCode, 'health_claim_')
        );
        $providerConsultativeAllowed = !$serverOwnedPolicyBoundary
            && (string) ($plan['safety']['level'] ?? 'routine') === 'routine'
            && !empty($plan['trace']['provider_required']);
        $providerAcknowledgement = $providerConsultativeAllowed
            ? fcc_ai_v2_clean_provider_text((string) ($candidate['acknowledgement'] ?? ''), 240)
            : '';
        $providerQuestion = $providerConsultativeAllowed
            ? fcc_ai_v2_clean_provider_text((string) ($candidate['clarifying_question'] ?? ''), 360)
            : '';
        /*
         * Deterministic replay fixtures predate the split acknowledgement/question
         * fields and keep their approved clarification in `content`. Never enable
         * this compatibility path for live provider responses.
         */
        if(
            $providerConsultativeAllowed
            && $providerAcknowledgement === ''
            && $providerQuestion === ''
            && $replayNarrative !== ''
            && defined('FCC_AI_V2_DETERMINISTIC')
        ) {
            $providerQuestion = $replayNarrative;
        }
        if($providerAcknowledgement !== '') {
            $content_blocks[] = $providerAcknowledgement;
        }
        $question = $providerQuestion !== ''
            ? $providerQuestion
            : trim((string) ($plan['decision']['clarifying_question'] ?? ''));
        $content_blocks[] = $question !== '' ? $question : fcc_ai_v2_localized_text($language, [
            'hr' => 'Koji je vaš glavni cilj kako bih preporuku suzio na jedan proizvod?',
            'en' => 'What is your main goal, so I can narrow the recommendation to one product?',
            'sl' => 'Kaj je vaš glavni cilj, da lahko priporočilo zožim na en izdelek?',
            'bg' => 'Каква е основната ви цел, за да сведа препоръката до един продукт?',
        ]);
    } elseif($action === 'content_creation') {
        $subjectProductKeys = fcc_ai_v2_unique_strings($plan['trace']['subject_product_keys'] ?? [], 1);
        $subjectRow = isset($subjectProductKeys[0], $rows[$subjectProductKeys[0]])
            ? $rows[$subjectProductKeys[0]]
            : null;
        if($subjectRow && (string) ($subjectRow['advisor_status'] ?? '') === 'recommendable') {
            $safeFacts = fcc_ai_v2_unique_strings(array_merge(
                (array) ($subjectRow['approved_support_facts'] ?? []),
                (array) ($subjectRow['ingredient_facts'] ?? []),
                (array) ($subjectRow['usage_facts'] ?? [])
            ), 2);
            $factText = implode(' ', array_map(
                static fn(string $fact): string => rtrim(fcc_ai_v2_display_fact_text($fact), '. ') . '.',
                $safeFacts
            ));
            $content_blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => "Predložak poruke:\n\n" . (string) $subjectRow['canonical_name'] . '. ' . $factText . ' Prije uporabe provjerite aktualnu deklaraciju i upozorenja. Za više informacija javite mi se porukom.',
                'en' => "Message draft:\n\n" . (string) $subjectRow['canonical_name'] . '. ' . $factText . ' Check the current label and warnings before use. Message me for more information.',
                'sl' => "Predlog sporočila:\n\n" . (string) $subjectRow['canonical_name'] . '. ' . $factText . ' Pred uporabo preverite aktualno deklaracijo in opozorila. Za več informacij mi pišite.',
                'bg' => "Примерен текст:\n\n" . (string) $subjectRow['canonical_name'] . '. ' . $factText . ' Преди употреба проверете актуалния етикет и предупрежденията. Пишете ми за повече информация.',
            ]);
        } elseif((string) ($plan['trace']['content_topic'] ?? '') === 'fcc_social') {
            $content_blocks[] = (string) ($plan['trace']['content_format'] ?? '') === 'story'
                ? fcc_ai_v2_localized_text($language, [
                    'hr' => 'Story predložak: Danas dijelim jednostavan način da lakše pronađete Forever proizvod prema svojem cilju. Otvorite moju FCC preporuku i odaberite temu koja vas zanima.',
                    'en' => 'Story draft: Today I am sharing a simple way to find a Forever product based on your goal. Open my FCC recommendation and choose the topic that interests you.',
                    'sl' => 'Predlog zgodbe: Danes delim preprost način, da lažje najdete izdelek Forever glede na svoj cilj. Odprite moje priporočilo FCC in izberite temo, ki vas zanima.',
                    'bg' => 'Текст за story: Днес споделям лесен начин да намерите продукт на Forever според целта си. Отворете моята FCC препоръка и изберете темата, която ви интересува.',
                ])
                : fcc_ai_v2_localized_text($language, [
                    'hr' => 'Najjednostavnije je objaviti kratak edukativni story, dodati jasan osobni poziv i poveznicu na svoju FCC preporuku. Tekst neka vodi osobu na odabir cilja, bez zdravstvenih obećanja i bez nasumičnog dodavanja proizvoda.',
                    'en' => 'The simplest approach is a short educational story, a clear personal invitation, and a link to your FCC recommendation. Guide people to choose a goal without health promises or random product add-ons.',
                    'sl' => 'Najpreprosteje je objaviti kratko izobraževalno zgodbo, dodati jasno osebno povabilo in povezavo do priporočila FCC. Osebo usmerite k izbiri cilja brez zdravstvenih obljub ali naključnih izdelkov.',
                    'bg' => 'Най-лесният подход е кратко образователно story, ясен личен призив и връзка към FCC препоръката. Насочете човека да избере цел без здравни обещания или случайни продукти.',
                ]);
        } elseif((string) ($plan['trace']['content_topic'] ?? '') === 'seasonal_allergy_education') {
            $content_blocks[] = (string) ($plan['trace']['content_format'] ?? '') === 'whatsapp'
                ? fcc_ai_v2_localized_text($language, [
                    'hr' => 'WhatsApp poruka: Sezona alergija može biti neugodna. Pratite simptome, smanjite izloženost poznatim okidačima i kod jače reakcije ili otežanog disanja javite se liječniku odnosno nazovite 112. Ova poruka nije dijagnoza ni preporuka proizvoda.',
                    'en' => 'WhatsApp message: Seasonal allergies can be uncomfortable. Monitor symptoms, reduce exposure to known triggers, and seek medical help for a severe reaction or breathing difficulty; call emergency services when needed. This is not a diagnosis or product recommendation.',
                    'sl' => 'WhatsApp sporočilo: Sezonske alergije so lahko neprijetne. Spremljajte simptome, zmanjšajte izpostavljenost znanim sprožilcem ter ob močnejši reakciji ali težkem dihanju poiščite zdravniško pomoč oziroma pokličite 112. To ni diagnoza ali priporočilo izdelka.',
                    'bg' => 'WhatsApp съобщение: Сезонните алергии могат да бъдат неприятни. Следете симптомите, ограничете известните дразнители и потърсете лекар при силна реакция или затруднено дишане; при нужда се обадете на 112. Това не е диагноза или продуктова препоръка.',
                ])
                : fcc_ai_v2_localized_text($language, [
                    'hr' => 'Sadržaj neka bude edukativan: opišite uobičajene sezonske okidače i praktično smanjenje izloženosti, bez dijagnoze, liječenja ili preporuke Forever proizvoda. Kod jače reakcije ili otežanog disanja uputite osobu liječniku odnosno hitnoj službi.',
                    'en' => 'Keep the content educational: describe common seasonal triggers and practical exposure reduction without diagnosis, treatment, or a Forever product recommendation. Direct severe reactions or breathing difficulty to medical or emergency care.',
                    'sl' => 'Vsebina naj bo izobraževalna: opišite običajne sezonske sprožilce in praktično zmanjšanje izpostavljenosti brez diagnoze, zdravljenja ali priporočila izdelka Forever. Ob močnejši reakciji ali težkem dihanju osebo usmerite k zdravniku oziroma nujni pomoči.',
                    'bg' => 'Нека съдържанието е образователно: опишете обичайни сезонни дразнители и практично ограничаване на излагането без диагноза, лечение или препоръка за продукт на Forever. При силна реакция или затруднено дишане насочете към лекар или спешна помощ.',
                ]);
        } else {
            $content_blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Za siguran predložak navedite točnu temu i format, primjerice edukativna objava, story ili WhatsApp poruka. Ako poruka govori o proizvodu, navedite njegov točan naziv; koristit ću samo provjerene službene činjenice.',
                'en' => 'For a safe draft, specify the exact topic and format, such as an educational post, story, or WhatsApp message. If it is about a product, give its exact name; I will use only verified official facts.',
                'sl' => 'Za varen predlog navedite natančno temo in obliko, na primer izobraževalno objavo, zgodbo ali WhatsApp sporočilo. Če govori o izdelku, navedite njegovo točno ime; uporabil bom le preverjena uradna dejstva.',
                'bg' => 'За безопасен текст посочете точната тема и формат, например образователна публикация, story или WhatsApp съобщение. Ако е за продукт, посочете точното му име; ще използвам само проверени официални факти.',
            ]);
        }
    } else {
        $acknowledgement = fcc_ai_v2_clean_provider_text((string) ($candidate['acknowledgement'] ?? ''), 240);
        $providerAnswerAcknowledgementAllowed = $selected
            && in_array($action, ['answer', 'factual_lookup'], true)
            && (string) ($plan['safety']['level'] ?? 'routine') === 'routine'
            && !empty($plan['trace']['provider_required']);
        if($acknowledgement !== '' && (!$selected || $providerAnswerAcknowledgementAllowed)) {
            $content_blocks[] = $acknowledgement;
        }

        if($replayNarrative !== '' && $selected && !$support) {
            $content_blocks[] = $replayNarrative;
        } elseif($primary !== '' && isset($rows[$primary])) {
            $primary_row = $rows[$primary];
            $lookupOnly = (string) ($primary_row['advisor_status'] ?? 'lookup_only') !== 'recommendable';
            $line = $action === 'factual_lookup'
                ? fcc_ai_v2_localized_text($language, [
                    'hr' => 'Za provjeru koju tražite, provjereni službeni zapis je ' . $primary_row['canonical_name'] . '.',
                    'en' => 'For the check you requested, the verified official record is ' . $primary_row['canonical_name'] . '.',
                    'sl' => 'Za preverjanje, ki ga želite, je preverjeni uradni zapis ' . $primary_row['canonical_name'] . '.',
                    'bg' => 'За поисканата проверка потвърденият официален запис е ' . $primary_row['canonical_name'] . '.',
                ])
                : fcc_ai_v2_localized_text($language, [
                    'hr' => 'Prema onome što tražite, najbliži provjereni izbor je ' . $primary_row['canonical_name'] . '.',
                    'en' => 'Based on what you are looking for, the closest verified choice is ' . $primary_row['canonical_name'] . '.',
                    'sl' => 'Glede na to, kar iščete, je najbližja preverjena izbira ' . $primary_row['canonical_name'] . '.',
                    'bg' => 'Според това, което търсите, най-близкият потвърден избор е ' . $primary_row['canonical_name'] . '.',
                ]);
            if($lookupOnly) {
                $line .= ' ' . fcc_ai_v2_localized_text($language, [
                    'hr' => 'Ovaj zapis nije uključen u automatske preporuke dok službene činjenice i sigurnosne upute ne budu potpuno provjerene.',
                    'en' => 'This record is not eligible for automatic recommendations until its official facts and safety instructions are fully verified.',
                    'sl' => 'Ta zapis ni vključen v samodejna priporočila, dokler uradna dejstva in varnostna navodila niso v celoti preverjena.',
                    'bg' => 'Този запис не участва в автоматични препоръки, докато официалните факти и указанията за безопасност не бъдат напълно проверени.',
                ]);
            }
            $queryFocus = (string) ($plan['trace']['query_focus'] ?? 'purpose');
            $programMemberRelationships = array_values(array_filter(
                (array) ($plan['program_member_relationships'] ?? []),
                static fn($relationship): bool => is_array($relationship)
                    && (string) ($relationship['source_product_key'] ?? '') === $primary
                    && (string) ($relationship['relation_type'] ?? '') === 'program_member'
            ));
            $factIds = fcc_ai_v2_unique_strings($candidate['fact_ids'] ?? [], 8);
            $factIndex = fcc_ai_v2_fact_index($rows);
            $factBuckets = ['support' => [], 'usage' => [], 'ingredient' => []];
            foreach($factIds as $factId) {
                if(isset($factIndex[$factId]) && ($factIndex[$factId]['product_key'] ?? '') === $primary) {
                    $factType = (string) ($factIndex[$factId]['type'] ?? '');
                    if(isset($factBuckets[$factType])) {
                        $factBuckets[$factType][] = (string) $factIndex[$factId]['text'];
                    }
                }
            }
            $facts = [];
            if($action === 'answer') {
                /* Recommendation rationale is server-owned and may use only
                 * reviewed use cases/support facts, never model explanation. */
                $matchedUseCases = [];
                foreach((array) ($plan['candidates'] ?? []) as $plannedCandidate) {
                    if(
                        is_array($plannedCandidate)
                        && (string) ($plannedCandidate['product_key'] ?? '') === $primary
                    ) {
                        $matchedUseCases = fcc_ai_v2_unique_strings($plannedCandidate['matched_use_cases'] ?? [], 3);
                        break;
                    }
                }
                $facts = array_merge(
                    $matchedUseCases,
                    $primary_row['approved_support_facts'],
                    $primary_row['use_cases']
                );
                if(!$facts) {
                    $facts = $primary_row['ingredient_facts'];
                }
            } elseif($queryFocus === 'usage') {
                $facts = $factBuckets['usage'];
            } elseif($queryFocus === 'ingredients') {
                $facts = $factBuckets['ingredient'];
            } elseif($queryFocus === 'package_contents' && $programMemberRelationships) {
                $facts = [];
            } elseif($queryFocus !== 'suitability') {
                $facts = array_merge($factBuckets['support'], $factBuckets['usage']);
                if(!$facts) {
                    $facts = $factBuckets['ingredient'];
                }
            }
            if(!$facts) {
                if($queryFocus === 'usage') {
                    $facts = $primary_row['usage_facts'];
                } elseif($queryFocus === 'ingredients') {
                    $facts = $primary_row['ingredient_facts'];
                } elseif($queryFocus === 'suitability') {
                    $suitabilityLabels = [
                        'vegan' => ['hr' => 'veganski', 'en' => 'vegan', 'sl' => 'veganski', 'bg' => 'веган'],
                        'vegetarian' => ['hr' => 'vegetarijanski', 'en' => 'vegetarian', 'sl' => 'vegetarijanski', 'bg' => 'вегетариански'],
                        'gluten_free' => ['hr' => 'bez glutena', 'en' => 'gluten-free', 'sl' => 'brez glutena', 'bg' => 'без глутен'],
                    ];
                    $verifiedSuitabilityLabels = [];
                    if((string) ($primary_row['suitability_review_status'] ?? 'unknown') === 'complete') {
                        foreach((array) ($primary_row['suitability_flags'] ?? []) as $suitabilityFlag) {
                            if(isset($suitabilityLabels[$suitabilityFlag])) {
                                $verifiedSuitabilityLabels[] = fcc_ai_v2_localized_text($language, $suitabilityLabels[$suitabilityFlag]);
                            }
                        }
                    }
                    if($verifiedSuitabilityLabels) {
                        $facts = [fcc_ai_v2_localized_text($language, [
                            'hr' => 'Službeni izvor označava proizvod kao: ' . implode(', ', $verifiedSuitabilityLabels),
                            'en' => 'The official source labels the product as: ' . implode(', ', $verifiedSuitabilityLabels),
                            'sl' => 'Uradni vir izdelek označuje kot: ' . implode(', ', $verifiedSuitabilityLabels),
                            'bg' => 'Официалният източник обозначава продукта като: ' . implode(', ', $verifiedSuitabilityLabels),
                        ])];
                    }
                } elseif($queryFocus !== 'package_contents') {
                    $facts = array_merge($primary_row['approved_support_facts'], $primary_row['use_cases']);
                    if(!$facts) {
                        $facts = $primary_row['ingredient_facts'];
                    }
                }
            }
            $facts = fcc_ai_v2_unique_strings($facts, 3);
            if($facts) {
                $factSentences = array_map(
                    static fn(string $fact): string => rtrim(fcc_ai_v2_display_fact_text($fact), '. ') . '.',
                    $facts
                );
                $line .= "\n\n" . ($action === 'answer'
                    ? fcc_ai_v2_localized_text($language, [
                        'hr' => 'Zašto je relevantan:',
                        'en' => 'Why it is relevant:',
                        'sl' => 'Zakaj je ustrezen:',
                        'bg' => 'Защо е подходящ:',
                    ])
                    : fcc_ai_v2_localized_text($language, [
                        'hr' => 'Provjereni podaci:',
                        'en' => 'Verified information:',
                        'sl' => 'Preverjeni podatki:',
                        'bg' => 'Потвърдена информация:',
                    ])) . ' ' . implode(' ', $factSentences);
            } elseif($queryFocus === 'package_contents' && $programMemberRelationships) {
                $memberNames = [];
                foreach($programMemberRelationships as $programMemberRelationship) {
                    $memberKey = (string) ($programMemberRelationship['target_product_key'] ?? '');
                    if(isset($rows[$memberKey])) {
                        $memberNames[] = (string) $rows[$memberKey]['canonical_name'];
                    }
                }
                if($memberNames) {
                    $line .= ' ' . fcc_ai_v2_localized_text($language, [
                        'hr' => 'Prema provjerenom službenom zapisu, paket sadrži: ' . implode(', ', $memberNames),
                        'en' => 'According to the verified official record, the kit contains: ' . implode(', ', $memberNames),
                        'sl' => 'Po preverjenem uradnem zapisu paket vsebuje: ' . implode(', ', $memberNames),
                        'bg' => 'Според проверения официален запис комплектът съдържа: ' . implode(', ', $memberNames),
                    ]) . '.';
                }
            } elseif($action === 'factual_lookup') {
                if(($primary_row['route'] ?? '') === 'topical') {
                    $line .= ' ' . fcc_ai_v2_localized_text($language, [
                        'hr' => 'Topikalni je proizvod za vanjsku primjenu na koži.',
                        'en' => 'It is a topical product for external use on the skin.',
                        'sl' => 'Je topikalni izdelek za zunanjo uporabo na koži.',
                        'bg' => 'Това е продукт за външно приложение върху кожата.',
                    ]);
                } elseif(($primary_row['route'] ?? '') === 'program') {
                    $line .= ' ' . fcc_ai_v2_localized_text($language, [
                        'hr' => 'Riječ je o strukturiranom programu; slijedite službene upute svojeg paketa i tržišta.',
                        'en' => 'It is a structured program; follow the official instructions for your package and market.',
                        'sl' => 'Gre za strukturiran program; upoštevajte uradna navodila svojega paketa in trga.',
                        'bg' => 'Това е структурирана програма; следвайте официалните инструкции за вашия пакет и пазар.',
                    ]);
                } else {
                    $line .= ' ' . fcc_ai_v2_localized_text($language, [
                        'hr' => 'Za detaljniji opis ovaj zapis još nema dovoljno provjerenih činjenica; provjerite službenu deklaraciju pakiranja.',
                        'en' => 'This record does not yet contain enough verified facts for a fuller description; check the official package label.',
                        'sl' => 'Ta zapis še nima dovolj preverjenih dejstev za podrobnejši opis; preverite uradno deklaracijo embalaže.',
                        'bg' => 'Записът все още няма достатъчно проверени факти за по-подробно описание; проверете официалния етикет.',
                    ]);
                }
            }
            $content_blocks[] = $line;

            if($action === 'answer' && !$lookupOnly) {
                $usageFact = (string) (($primary_row['usage_facts'][0] ?? '') ?: '');
                if(trim($usageFact) !== '') {
                    $content_blocks[] = fcc_ai_v2_localized_text($language, [
                        'hr' => 'Kako koristiti prema službenoj deklaraciji: ' . fcc_ai_v2_display_fact_text($usageFact),
                        'en' => 'How to use it according to the official label: ' . fcc_ai_v2_display_fact_text($usageFact),
                        'sl' => 'Uporaba po uradni deklaraciji: ' . fcc_ai_v2_display_fact_text($usageFact),
                        'bg' => 'Употреба според официалния етикет: ' . fcc_ai_v2_display_fact_text($usageFact),
                    ]);
                }

                $labelPlanText = fcc_ai_v2_label_plan_text($primary_row, $language, $catalog);
                if($labelPlanText !== '') {
                    $content_blocks[] = 'Plan količine za 30 dana: ' . $labelPlanText;
                }

                $content_blocks[] = fcc_ai_v2_localized_text($language, [
                    'hr' => 'Ako vam ovaj oblik, sastav ili deklarirana uporaba ne odgovaraju, recite što želite promijeniti i prilagodit ću izbor bez vraćanja odbijenog proizvoda.',
                    'en' => 'If this format, composition, or labelled use does not fit, tell me what you want to change and I will adjust the choice without bringing back a rejected product.',
                    'sl' => 'Če vam oblika, sestava ali deklarirana uporaba ne ustrezajo, povejte, kaj želite spremeniti, in izbiro bom prilagodil brez vračanja zavrnjenega izdelka.',
                    'bg' => 'Ако формата, съставът или употребата според етикета не ви устройват, кажете какво искате да промените и ще коригирам избора без да връщам отхвърления продукт.',
                ]);
            }
        }

        foreach($support as $support_key) {
            if(!isset($rows[$support_key])) {
                continue;
            }
            $support_row = $rows[$support_key];
            $relationship = $supportRelationshipByTarget[$support_key] ?? [];
            $relationshipType = (string) ($relationship['relation_type'] ?? 'complements');
            $sequenceOrder = max(0, (int) ($relationship['sequence_order'] ?? 0));
            if($relationshipType === 'sequence') {
                $heading = $sequenceOrder > 0
                    ? fcc_ai_v2_localized_text($language, [
                        'hr' => 'Korak ' . $sequenceOrder,
                        'en' => 'Step ' . $sequenceOrder,
                        'sl' => 'Korak ' . $sequenceOrder,
                        'bg' => 'Стъпка ' . $sequenceOrder,
                    ])
                    : fcc_ai_v2_localized_text($language, [
                        'hr' => 'Sljedeći korak', 'en' => 'Next step', 'sl' => 'Naslednji korak', 'bg' => 'Следваща стъпка',
                    ]);
            } else {
                $heading = fcc_ai_v2_localized_text($language, [
                    'hr' => 'Dodatna podrška', 'en' => 'Optional support', 'sl' => 'Dodatna podpora', 'bg' => 'Допълнителна подкрепа',
                ]);
            }
            $line = $heading . ': ' . $support_row['canonical_name'] . '.';
            if($relationshipType === 'sequence') {
                $relationshipEvidence = trim((string) ($relationship['evidence_summary'] ?? ''));
                if($relationshipEvidence !== '') {
                    $line .= ' ' . rtrim($relationshipEvidence, '. ') . '.';
                }
            } else {
                /* reason_text is an internal eligibility/safety note. It must
                 * remain available to validation, but should never be shown as
                 * sales copy (for example "allowed only if..."). */
                $line .= ' ' . fcc_ai_v2_localized_text($language, [
                    'hr' => 'Službena hrvatska stranica proizvoda izričito navodi ovu kombinaciju.',
                    'en' => 'The official product page explicitly lists this combination.',
                    'sl' => 'Uradna stran izdelka izrecno navaja to kombinacijo.',
                    'bg' => 'Официалната продуктова страница изрично посочва тази комбинация.',
                ]);
            }
            $supportFacts = [];
            foreach(fcc_ai_v2_unique_strings($candidate['fact_ids'] ?? [], 8) as $factId) {
                if(isset($factIndex[$factId]) && ($factIndex[$factId]['product_key'] ?? '') === $support_key) {
                    $supportFacts[] = (string) $factIndex[$factId]['text'];
                }
            }
            if(!$supportFacts) {
                $supportFacts = array_merge($support_row['approved_support_facts'], $support_row['ingredient_facts']);
            }
            foreach(fcc_ai_v2_unique_strings($supportFacts, 2) as $fact) {
                $line .= ' ' . rtrim(fcc_ai_v2_display_fact_text((string) $fact), '. ') . '.';
            }
            $content_blocks[] = $line;
        }

        if(!$selected) {
            $explanation = fcc_ai_v2_clean_provider_text((string) ($candidate['explanation'] ?? ''), 700);
            if($explanation !== '') {
                $content_blocks[] = $explanation;
            } else {
                $content_blocks[] = fcc_ai_v2_localized_text($language, [
                    'hr' => 'Za ovaj upit još nemam dovoljno provjerenog konteksta za točan izbor. Opišite željeni rezultat ili problem jednom rečenicom; naziv i oblik proizvoda ne trebate znati.',
                    'en' => 'I do not currently have enough verified information for an exact product recommendation. Share your main goal and whether you want a drink, supplement, or topical product.',
                    'sl' => 'Za ta primer trenutno nimam dovolj preverjenih podatkov za natančno priporočilo. Napišite glavni cilj in ali želite napitek, prehransko dopolnilo ali izdelek za zunanjo uporabo.',
                    'bg' => 'В момента нямам достатъчно проверени данни за точна препоръка. Посочете основната цел и дали желаете напитка, добавка или продукт за външна употреба.',
                ]);
            }
        }

        /* A provider-generated follow-up must never be appended to an answer
         * or a selected recommendation. Clarifiers are emitted exclusively in
         * the dedicated clarify branch from the server-owned plan. */
    }

    if($selected) {
        $decisionReasonCodes = fcc_ai_v2_unique_strings($plan['decision']['reason_codes'] ?? [], 8);
        if(in_array('neutral_product_fact', $decisionReasonCodes, true)) {
            $healthBoundaryReason = '';
            foreach($decisionReasonCodes as $decisionReasonCode) {
                if(str_starts_with($decisionReasonCode, 'health_claim_')) {
                    $healthBoundaryReason = $decisionReasonCode;
                    break;
                }
            }
            $healthBoundaryTopicsHr = [
                'health_claim_immunity' => 'imunitet, prehladu ili gripu',
                'health_claim_weight_loss' => 'mršavljenje, apetit ili sagorijevanje masti',
                'health_claim_hormonal' => 'hormone, libido ili prostatu',
                'health_claim_circulation' => 'krvni tlak ili cirkulaciju',
                'health_claim_digestive' => 'probavne tegobe',
                'health_claim_sleep_stress' => 'nesanicu ili stres',
                'health_claim_pain_inflammation' => 'bol ili upalu',
            ];
            $topicHr = (string) ($healthBoundaryTopicsHr[$healthBoundaryReason] ?? 'zdravstveni ishod');
            $content_blocks[] = fcc_ai_v2_localized_text($language, [
                'hr' => 'Napomena: ovaj izbor temeljim samo na provjerenom deklariranom sastojku, obliku i uporabi proizvoda — ne predstavljam ga kao liječenje niti obećanje učinka za ' . $topicHr . '.',
                'en' => 'Note: this choice is based only on the verified labelled ingredient, format, and directions; it is not presented as treatment or a promised health outcome.',
                'sl' => 'Opomba: izbira temelji le na preverjeni deklarirani sestavini, obliki in uporabi; izdelka ne predstavljam kot zdravljenje ali obljubljen zdravstveni učinek.',
                'bg' => 'Бележка: изборът се основава само на проверените съставка, форма и употреба от етикета; продуктът не се представя като лечение или обещан здравен резултат.',
            ]);
        }

        $existingContent = fcc_ai_v2_normalize_text(implode(' ', $content_blocks));
        foreach($selected as $selectedProductKey) {
            $selectedRow = $rows[$selectedProductKey] ?? null;
            if(!$selectedRow) {
                continue;
            }
            foreach(fcc_ai_v2_unique_strings($selectedRow['mandatory_safety_notes'] ?? [], 12) as $mandatorySafetyNote) {
                $normalizedSafetyNote = fcc_ai_v2_normalize_text($mandatorySafetyNote);
                if($normalizedSafetyNote === '' || str_contains($existingContent, $normalizedSafetyNote)) {
                    continue;
                }
                $displaySafetyNote = fcc_ai_v2_display_fact_text($mandatorySafetyNote);
                $content_blocks[] = fcc_ai_v2_localized_text($language, [
                    'hr' => 'Važno za ' . (string) $selectedRow['canonical_name'] . ': ' . $displaySafetyNote,
                    'en' => 'Important for ' . (string) $selectedRow['canonical_name'] . ': ' . $displaySafetyNote,
                    'sl' => 'Pomembno za ' . (string) $selectedRow['canonical_name'] . ': ' . $displaySafetyNote,
                    'bg' => 'Важно за ' . (string) $selectedRow['canonical_name'] . ': ' . $displaySafetyNote,
                ]);
                $existingContent .= ' ' . $normalizedSafetyNote;
            }
        }
    }

    $state = is_array($plan['state'] ?? null) ? $plan['state'] : [];
    /* State that affects matching, safety, product identity or follow-up
     * routing is deterministic/server-owned. Provider state_patch fields are
     * schema placeholders and are deliberately never applied. */
    if($selected) {
        $state['active_product_keys'] = array_values(array_slice($selected, 0, 3));
    }
    $state['last_selected_product_keys'] = $selected;
    $state['last_action'] = $action;

    $knowledge_suggestions = [];
    foreach($selected as $key) {
        $row = $rows[$key] ?? null;
        if(!$row || empty($row['article_url'])) {
            continue;
        }
        $knowledge_suggestions[] = [
            'product_key' => $key,
            'blog_post_id' => (int) ($row['blog_post_id'] ?? 0),
            'title' => (string) $row['canonical_name'],
            'url' => (string) $row['article_url'],
        ];
    }

    $primary_product = $primary !== '' && isset($rows[$primary]) ? $rows[$primary] : null;
    $decisionReasonCodes = fcc_ai_v2_unique_strings($plan['decision']['reason_codes'] ?? [], 8);
    $hasHealthClaimBoundary = in_array('neutral_product_fact', $decisionReasonCodes, true)
        || (bool) array_filter(
            $decisionReasonCodes,
            static fn(string $reasonCode): bool => str_starts_with($reasonCode, 'health_claim_')
        );
    /* A neutral label fact can identify a product so the answer remains
     * useful, but it must never be upgraded into a commercial recommendation.
     * Product purchase eligibility is intentionally stricter than product
     * selection and mirrors the card gate in the framework adapter. */
    $recommendationEligible = $primary_product
        && (string) ($primary_product['advisor_status'] ?? 'lookup_only') === 'recommendable'
        && $action === 'answer'
        && (string) ($plan['safety']['level'] ?? '') === 'routine'
        && !empty($plan['safety']['allow_products'])
        && !empty($validation['valid'])
        && !$hasHealthClaimBoundary;
    $support_products = array_values(array_filter(array_map(static function(string $key) use ($rows): ?array {
        return $rows[$key] ?? null;
    }, $support)));
    $supportiveProfile = $action === 'doctor_first'
        ? fcc_ai_v2_supportive_wellness_profile((array) ($plan['safety']['reason_codes'] ?? []))
        : [];
    $supportiveConstraints = (array) ($plan['user_constraints'] ?? []);
    $supportiveOptions = [];
    foreach(array_slice((array) ($supportiveProfile['options'] ?? []), 0, 3) as $supportiveOption) {
        if(!is_array($supportiveOption)) {
            continue;
        }
        $productKey = trim((string) ($supportiveOption['product_key'] ?? ''));
        $row = $rows[$productKey] ?? null;
        if(
            $productKey === ''
            || !is_array($row)
            || (string) ($row['advisor_status'] ?? 'lookup_only') !== 'recommendable'
            || (string) ($row['availability_status'] ?? '') !== 'available'
            || fcc_ai_v2_supportive_product_blocked_by_constraints($productKey, $supportiveConstraints)
        ) {
            continue;
        }
        $evidenceTier = trim((string) ($supportiveOption['evidence_tier'] ?? ''));
        if(!in_array($evidenceTier, ['A_CONDITION_RELEVANT', 'B_GENERAL_NUTRITION', 'C_LABEL_ONLY'], true)) {
            continue;
        }
        $supportiveOptions[] = [
            'product_key' => $productKey,
            'product_name' => (string) ($row['canonical_name'] ?? ''),
            'condition_key' => (string) ($supportiveProfile['condition_key'] ?? ''),
            'profile_version' => (string) ($supportiveProfile['profile_version'] ?? ''),
            'evidence_tier' => $evidenceTier,
            'reason_hr' => trim((string) ($supportiveOption['reason_hr'] ?? '')),
            'not_treatment' => true,
        ];
    }
    $supportiveReferenceKeys = array_values(array_map(
        static fn(array $option): string => (string) $option['product_key'],
        $supportiveOptions
    ));
    $supportiveReferenceProducts = array_values(array_map(
        static fn(string $productKey): string => (string) ($rows[$productKey]['canonical_name'] ?? ''),
        $supportiveReferenceKeys
    ));

    return [
        'content' => trim(implode("\n\n", array_filter($content_blocks))),
        'language' => $language,
        'decision' => [
            'action' => $action,
            'confidence' => (float) ($plan['decision']['confidence'] ?? 0),
            'reason_codes' => array_values((array) ($plan['decision']['reason_codes'] ?? [])),
            'clarifying_question' => (string) ($plan['decision']['clarifying_question'] ?? ''),
            'question_id' => (string) ($plan['decision']['question_id'] ?? ''),
            'pending_slot' => is_array($plan['decision']['pending_slot'] ?? null) ? $plan['decision']['pending_slot'] : null,
        ],
        'primary_product_key' => $primary,
        'support_product_keys' => $support,
        'selected_product_keys' => $selected,
        'validation' => $validation,
        'recommendation_payload' => [
            'v2' => true,
            'skip_product_tail' => true,
            'recommendation_eligible' => (bool) $recommendationEligible,
            'advisor_status' => $primary_product ? (string) ($primary_product['advisor_status'] ?? 'lookup_only') : '',
            'primary_product_key' => $primary,
            'primary_product' => $primary_product ? (string) $primary_product['canonical_name'] : '',
            'support_product_keys' => $support,
            'support_products' => array_values(array_map(static fn(array $row): string => (string) $row['canonical_name'], $support_products)),
            'supportive_reference_product_keys' => $supportiveReferenceKeys,
            'supportive_reference_products' => $supportiveReferenceProducts,
            'supportive_wellness_options' => $supportiveOptions,
            'supportive_wellness_disclaimer' => trim((string) ($supportiveProfile['disclaimer_hr'] ?? '')),
            /* Filled only by the trusted framework adapter from catalog and
             * collaborator data. The pure decision core never creates URLs. */
            'supportive_product_links' => [],
            'supportive_shop_available' => false,
            'support_relationships' => array_values(array_map(static function(array $relationship): array {
                return [
                    'relation_id' => (int) ($relationship['relation_id'] ?? 0),
                    'relation_type' => (string) ($relationship['relation_type'] ?? ''),
                    'source_product_key' => (string) ($relationship['source_product_key'] ?? ''),
                    'target_product_key' => (string) ($relationship['target_product_key'] ?? ''),
                    'matched_goal_key' => (string) ($relationship['matched_goal_key'] ?? ''),
                    'reason_text' => (string) ($relationship['reason_text'] ?? ''),
                ];
            }, $selectedSupportRelationships)),
            'program_members' => array_values(array_map(static function(array $relationship) use ($rows): array {
                $targetProductKey = (string) ($relationship['target_product_key'] ?? '');
                return [
                    'relation_id' => (int) ($relationship['relation_id'] ?? 0),
                    'relation_type' => 'program_member',
                    'source_product_key' => (string) ($relationship['source_product_key'] ?? ''),
                    'target_product_key' => $targetProductKey,
                    'product_key' => $targetProductKey,
                    'product_name' => (string) ($rows[$targetProductKey]['canonical_name'] ?? ''),
                    'sequence_order' => max(0, (int) ($relationship['sequence_order'] ?? 0)),
                ];
            }, array_values(array_filter(
                (array) ($plan['program_member_relationships'] ?? []),
                static fn($relationship): bool => is_array($relationship)
                    && (string) ($relationship['source_product_key'] ?? '') === $primary
            )))),
            'selected_product_keys' => $selected,
            'market_code' => (string) ($primary_product['market_code'] ?? ''),
            'official_url' => $primary_product ? (string) ($primary_product['official_url'] ?? '') : '',
            'recommendation_lines' => [],
            'question_lines' => $action === 'clarify' ? [end($content_blocks) ?: ''] : [],
        ],
        'knowledge_suggestions' => $knowledge_suggestions,
        'state' => $state,
    ];
}
