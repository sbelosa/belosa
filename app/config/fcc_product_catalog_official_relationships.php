<?php

defined('ALTUMCODE') || die();

/*
 * Exact, reviewed product-to-product relationships from current official HR
 * pages. These are evidence records, not automatic bundles. Runtime still has
 * to match the user's goal, exact market availability and every safety gate.
 */

$accessedAt = '2026-08-25T20:46:00+00:00';
$blockedTopical = ['allergen_conflict', 'required_safety_unknown', 'product_unavailable'];
$blockedOral = [
    'allergen_conflict', 'medication_or_therapy', 'pregnancy_or_breastfeeding',
    'under_18', 'duplicate_micronutrients', 'required_safety_unknown',
    'product_unavailable',
];

$make = static function(
    string $sourceProductKey,
    string $targetProductKey,
    string $relationshipType,
    array $goalKeys,
    string $evidenceSummary,
    string $reasonText,
    string $sourceUrl,
    string $contentHash,
    int $sequenceOrder = 0,
    bool $allowGeneralCombination = false,
    array $blockedIf = [],
    array $additionalSources = []
) use ($accessedAt): array {
    $sourceRefs = [[
        'source_type' => 'official_product_relationship',
        'source_url' => $sourceUrl,
        'language_code' => 'hr',
        'market_code' => 'hr',
        'content_sha256' => $contentHash,
        'accessed_at' => $accessedAt,
        'named_target_product_keys' => [$targetProductKey],
    ]];
    foreach($additionalSources as $additionalSource) {
        if(!is_array($additionalSource)) {
            continue;
        }
        $sourceRefs[] = [
            'source_type' => 'official_product_relationship',
            'source_url' => (string) ($additionalSource['source_url'] ?? ''),
            'language_code' => 'hr',
            'market_code' => 'hr',
            'content_sha256' => (string) ($additionalSource['content_sha256'] ?? ''),
            'accessed_at' => $accessedAt,
            'named_target_product_keys' => [$targetProductKey],
        ];
    }

    return [
        'source_product_key' => $sourceProductKey,
        'target_product_key' => $targetProductKey,
        'language_code' => 'hr',
        'market_code' => 'hr',
        'relationship_type' => $relationshipType,
        'goal_keys' => array_values($goalKeys),
        'evidence_summary' => $evidenceSummary,
        'reason_text' => $reasonText,
        'source_refs' => $sourceRefs,
        'safety_gate' => [
            'requires_goal_match' => true,
            'allow_if_safety_unknown' => false,
            'allow_general_combination' => $allowGeneralCombination,
            'blocked_if' => array_values($blockedIf),
        ],
        'sequence_order' => $sequenceOrder,
        'source_content_hash' => $contentHash,
        'review_status' => 'approved',
        'accessed_at' => $accessedAt,
    ];
};

$relationships = [
    $make(
        'forever-daily', 'forever-nature-min', 'complements',
        ['nutritional_completeness'],
        'Službena HR stranica Forever Daily izričito navodi da se može uzimati zajedno s Forever Nature-Min.',
        'Dopušteno samo kada korisnik traži tu kombinaciju i nema konflikta terapije ili dupliranja mikronutrijenata.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/nutritional/439-FOREVER-DAILY',
        'f5cc7361c3dd3bc41b72811181a3eb934a6e9085e1a4a91830a3632d1c68de86',
        0, true, $blockedOral
    ),
    $make(
        'forever-nature-min', 'forever-daily', 'complements',
        ['nutritional_completeness'],
        'Službena HR stranica Forever Nature-Min recipročno navodi uzimanje zajedno s Forever Daily.',
        'Dopušteno samo uz podudaran cilj i bez konflikta terapije ili dupliranja mikronutrijenata.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/037-NATURE-MIN',
        'a63641210351452fccf5cb11b2b95c0bdf4bc092be5468dd0176e3f4d61be19f',
        0, true, $blockedOral
    ),
    $make(
        'forever-aloe-jojoba-shampoo', 'forever-aloe-jojoba-conditioner', 'sequence',
        ['hair_cleansing_conditioning'],
        'Službena HR uputa šampona navodi pranje i ispiranje prije uporabe Aloe-Jojoba regeneratora.',
        'Aloe-Jojoba Shampoo je prvi korak, a Aloe Jojoba Conditioner drugi korak nakon ispiranja; ovo nije automatski paket.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/640-ALOE-JOJOBA-SHAMPOO',
        '0384fb096a1b0da194f0e34ddc60adfb704f49d56f8e6fe50eaa7994d64105e1',
        2, false, $blockedTopical,
        [[
            'source_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/personal-care/641-ALOE-JOJOBA-CONDITIONER',
            'content_sha256' => '93d9dae08bf8908710fa29a15b53a4303cdd14a67963f18dd8797d11daade5d6',
        ]]
    ),
    $make(
        'forever-replenishing-skin-oil', 'aloe-activator', 'sequence',
        ['facial_skin_oil_routine'],
        'Službena HR uputa ulja navodi da kožu prije ulja treba lagano navlažiti Aloe Activatorom.',
        'Aloe Activator je pripremni korak prije ulja samo kada korisnik traži ovu rutinu.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/653-REPLENISHING-SKIN-OIL?isPrerender=true',
        'bf45df42942287261b6cf28fbcb1d182c1d74aa4ff787fe774066c88b0948c5e',
        1, false, $blockedTopical
    ),
    $make(
        'forever-vitamin-c', 'forever-bakuchiol', 'sequence',
        ['day_night_skin_routine'],
        'Službena HR stranica Bakuchiola navodi Forever Vitamin C kao upareni dnevni proizvod te Bakuchiol za večernju uporabu.',
        'Forever Vitamin C je dnevni prvi korak, a Forever Bakuchiol noćni drugi korak; ne prikazuju se kao istodobni add-on.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/skin-care/687-FOREVER-BAKUCHIOL',
        '84e805d6806fad8d66cc1c1ac975cd3dcdfb623510d9c087a60c8cd62fbb75bb',
        2, false, $blockedTopical
    ),
    $make(
        'forever-nutra-q10', 'forever-aloe-vera-gel', 'complements',
        ['cardiohealth_preparation'],
        'Službena HR stranica Forever Cardiohealth navodi da je proizvod dizajniran za miješanje s Aloe Vera Gelom.',
        'Gel je službeno potvrđena mogućnost pripreme, ali nije obvezan jer uputa dopušta i drugu tekućinu.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/312-FOREVER-CARDIOHEALTH',
        '16a00cb0124cff44bfdca1ca905cca231c4f392537ea0c6ea1c646894d38a5eb',
        0, true, $blockedOral
    ),
    $make(
        'forever-logic-aloe-gel-cleanser', 'forever-logic-gel-balancing-essence', 'sequence',
        ['logic_three_step_skin_routine'],
        'Službena HR stranica Logic Cleansera izričito navodi nanošenje Logic Essence nakon čišćenja.',
        'Essence je drugi korak samo u traženoj Logic rutini.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/667-LOGIC-ALOE-GEL-CLEANSER',
        'cfad378d56d034eabc57171552800bdc95a7bae1c3040351ee509a6022f34d3b',
        2, false, $blockedTopical
    ),
    $make(
        'forever-logic-gel-balancing-essence', 'forever-logic-aloe-soothing-gel-moisture', 'sequence',
        ['logic_three_step_skin_routine'],
        'Službena HR Logic Kit uputa navodi cleanser, essence i moisturizer tim redoslijedom.',
        'Moisturizer je treći korak službene Logic rutine.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/673-LOGIC-KIT',
        '96960e619a75413e2bf165d232b7051768301e5576472e9f85c6180ed8362b97',
        3, false, $blockedTopical
    ),
    $make(
        'forever-logic', 'forever-logic-aloe-gel-cleanser', 'program_member',
        ['logic_three_step_skin_routine'],
        'Službena HR Logic Kit stranica imenuje Logic Aloe Gel Cleanser kao prvi član rutine.',
        'Članstvo služi objašnjenju sadržaja službenog kita, ne automatskom upsellu.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/673-LOGIC-KIT',
        '96960e619a75413e2bf165d232b7051768301e5576472e9f85c6180ed8362b97',
        1, false, $blockedTopical
    ),
    $make(
        'forever-logic', 'forever-logic-gel-balancing-essence', 'program_member',
        ['logic_three_step_skin_routine'],
        'Službena HR Logic Kit stranica imenuje Logic Gel Balancing Essence kao drugi član rutine.',
        'Članstvo služi objašnjenju sadržaja službenog kita, ne automatskom upsellu.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/673-LOGIC-KIT',
        '96960e619a75413e2bf165d232b7051768301e5576472e9f85c6180ed8362b97',
        2, false, $blockedTopical
    ),
    $make(
        'forever-logic', 'forever-logic-aloe-soothing-gel-moisture', 'program_member',
        ['logic_three_step_skin_routine'],
        'Službena HR Logic Kit stranica imenuje Logic Soothing Gel Moisturizer kao treći član rutine.',
        'Članstvo služi objašnjenju sadržaja službenog kita, ne automatskom upsellu.',
        'https://foreverliving.com/shop/hrv/hr-hr/products/673-LOGIC-KIT',
        '96960e619a75413e2bf165d232b7051768301e5576472e9f85c6180ed8362b97',
        3, false, $blockedTopical
    ),
];

return [
    'schema_version' => 'fcc-official-product-relationships-v1',
    'generated_at' => $accessedAt,
    'relationships' => $relationships,
];
