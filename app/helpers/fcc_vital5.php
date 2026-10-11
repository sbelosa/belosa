<?php
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-05: Vital5 package identity and explicit package lookup */
function fcc_vital5_package(): array {
    static $package = null;
    if($package !== null) return $package;
    $path = APP_PATH . 'config/fcc_vital5_package.json';
    $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
    if(!is_array($data) || ($data['product_key'] ?? '') !== 'vital5' || ($data['schema_version'] ?? '') !== 'fcc-vital5-package-v1') return $package = [];
    $at = strtotime((string) ($data['reviewed_at'] ?? ''));
    if(!$at || $at > time() + 60) return $package = [];
    return $package = $data;
}

function fcc_vital5_public_identity(array $catalog): array {
    $package = fcc_vital5_package();
    if(!$package || !isset($catalog['vital5'])) return $catalog;
    $row = &$catalog['vital5'];
    $row['aliases'] = array_values(array_unique(array_merge((array) ($row['aliases'] ?? []), (array) ($package['aliases'] ?? []))));
    $row['article_url'] = rtrim(SITE_URL, '/') . '/hr/blog/vital5';
    unset($row);
    return $catalog;
}

function fcc_vital5_copy(string $language): array {
    $copies = (array) (fcc_vital5_package()['copy'] ?? []);
    $locale = function_exists('fcc_locale') ? fcc_locale($language) : strtolower(explode('-', $language)[0]);
    return (array) ($copies[$locale] ?? $copies['hr'] ?? []);
}

function fcc_vital5_components(string $market): array {
    $package = fcc_vital5_package();
    $variant = $package['markets'][strtolower($market)] ?? [];
    return is_array($variant) ? array_values((array) ($variant['components'] ?? [])) : [];
}

function fcc_vital5_contents_text(array $components): string {
    $items = [];
    foreach($components as $component) {
        if(is_string($component) && trim($component) !== '') $items[] = trim($component);
        elseif(is_array($component) && trim((string) ($component['name'] ?? '')) !== '') {
            $quantity = trim((string) ($component['quantity_label'] ?? ''));
            $items[] = ($quantity !== '' ? $quantity . ' ' : '') . trim((string) $component['name']);
        }
    }
    return implode(', ', $items);
}

function fcc_vital5_request_kind(string $text): string {
    $package = fcc_vital5_package();
    if(!$package) return '';
    $identity = [['product_key' => 'vital5', 'canonical_name' => 'Vital5', 'aliases' => (array) ($package['aliases'] ?? ['Vital5', 'Vital 5', 'Vitel5', 'Vitel 5']), 'route' => 'program', 'advisor_status' => 'lookup_only']];
    // Native negations are search equivalents only; the original request and policy stay unchanged.
    $lookupText = preg_replace('/(?<![\p{L}\p{N}])(?:nem|nie|inte)(?![\p{L}\p{N}])/iu', 'not', $text) ?? $text;
    $lookupText = preg_replace('/(?<![\p{L}\p{N}])nu(?=\s+(?:vreau|doresc|caut|am\s+nevoie)\b)/iu', 'not', $lookupText) ?? $lookupText;
    $mentions = fcc_ai_v2_product_mentions($lookupText, $identity);
    foreach($mentions as $mention) if(($mention['product_key'] ?? '') === 'vital5') return !empty($mention['negated']) ? '' : 'named_package';
    $normalized = fcc_ai_v2_normalize_text($lookupText);
    if(preg_match('/(?<![\p{L}\p{N}])(?:hud|hår|bőr|bőrápol|arcápol|haj|skór|skor|twarz|włos|wlos|piele|păr|parul|piel)\p{L}*(?![\p{L}\p{N}])/iu', $text)) return '';
    if(preg_match('/(?<![\p{L}\p{N}])(?:skincare|skin\s+care|koza|koz|lice|lic|kosu|kosa|hair|haar|haut|cheveu|pelo|visage)\p{L}*(?![\p{L}\p{N}])/u', $normalized)) return '';
    $packageWord = '/(?<![\p{L}\p{N}])(?:paket|package|paquet|pacchet|bundle|pack|coffret|lot|set|kit|csomag|pakiet|pachet|naringspaket|kosttillskottspaket)\p{L}*(?![\p{L}\p{N}])/u';
    $nutritionWord = '/(?<![\p{L}\p{N}])(?:nutrit|nutrition|nutric|prehran|prehram|supplement|suplement|supliment|nahrung|ern[aä]hr|compl[eé]ment|ushq|dodat|taplal|etrend|kiegeszit|odzyw|naring|kosttillskott|dnev|svakodnev|vsakodnev|daily|everyday|t[aä]glich|alltag|quotid|diar|ditor|perdit)\p{L}*(?![\p{L}\p{N}])/u';
    $routineWord = '/(?<![\p{L}\p{N}])(?:rutin|routine|rutyn)\p{L}*(?![\p{L}\p{N}])/u';
    $supplementWord = '/(?<![\p{L}\p{N}])(?:nutrit|nutrition|nutric|prehran|prehram|supplement|suplement|supliment|nahrung|ern[aä]hr|compl[eé]ment|ushq|dodat|taplal|etrend|kiegeszit|odzyw|naring|kosttillskott)\p{L}*(?![\p{L}\p{N}])/u';
    if((fcc_ai_v2_has_affirmed_regex($normalized, $packageWord) && fcc_ai_v2_has_affirmed_regex($normalized, $nutritionWord)) || (fcc_ai_v2_has_affirmed_regex($normalized, $routineWord) && fcc_ai_v2_has_affirmed_regex($normalized, $supplementWord))) return 'nutrition_package';
    return '';
}

function fcc_vital5_package_context(string $text, string $market, bool $medical, array $constraints): ?array {
    $package = fcc_vital5_package();
    $kind = fcc_vital5_request_kind($text);
    if(!$package || $kind === '' || $medical || array_filter($constraints)) return null;
    $market = strtolower($market);
    $variant = (array) ($package['markets'][$market] ?? []);
    if($kind === 'nutrition_package' && (empty($variant['official_url']) || !fcc_vital5_components($market))) return null;
    $composition = fcc_vital5_contents_text(fcc_vital5_components($market));
    $copy = fcc_vital5_copy('hr');
    return [
        'product_key' => 'vital5', 'title' => 'Vital5', 'article_slug' => 'vital5',
        'recommendation_scope' => 'package_information', 'request_kind' => $kind,
        'facts' => array_values(array_filter([$copy['intro'] ?? '', $composition !== '' && !empty($copy['contains']) ? sprintf($copy['contains'], $composition) : ($copy['generic_contents'] ?? ''), $copy['market_note'] ?? ''])),
        'ingredients' => [], 'use_cases' => ['informacije o nutritivnom paketu i organizaciji dnevne rutine'],
        'mandatory_safety_notes' => [], 'label_usage' => [],
        'source_refs' => (array) ($variant['source_refs'] ?? []),
        'official_url' => (string) ($variant['official_url'] ?? ''),
    ];
}

function fcc_vital5_public_reply(array $context, string $language): ?array {
    $package = fcc_vital5_package();
    $candidate = $context['candidates']['vital5'] ?? null;
    if(!$package || !is_array($candidate) || count((array) ($context['candidates'] ?? [])) !== 1) return null;
    if(empty($candidate['explicitly_named']) && ($candidate['recommendation_scope'] ?? '') !== 'package_information') return null;
    if(($candidate['status'] ?? '') === 'blocked' || !empty($candidate['constraint_conflicts']) || !empty($context['constraint_blocked'])) return null;
    if(empty($context['safety']['allow_products']) || !empty($context['catalog']['medical_context']) || in_array($context['safety']['level'] ?? '', ['sensitive', 'medical', 'emergency', 'urgent'], true)) return null;
    if(fcc_advisor_business_intent((string) ($context['text'] ?? ''))) return null;
    $copy = fcc_vital5_copy($language);
    if(empty($copy['intro']) || empty($copy['generic_contents']) || empty($copy['market_note']) || empty($copy['article_cta'])) return null;
    $components = fcc_vital5_components((string) ($context['market'] ?? ''));
    $contents = fcc_vital5_contents_text($components);
    $composition = $contents !== '' && !empty($copy['contains']) ? sprintf($copy['contains'], $contents) : $copy['generic_contents'];
    $answer = implode(' ', [$copy['intro'], $composition, $copy['market_note'], $copy['article_cta']]);
    return ['answer' => $answer, 'product_keys' => ['vital5'], 'business_interest' => false, 'health_context' => 'none'];
}
/* /Custom code: FC-2026-10-05 */
