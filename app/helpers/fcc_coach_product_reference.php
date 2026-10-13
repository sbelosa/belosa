<?php
defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-13: Education supplies bounded facts, never a replacement answer. */
require_once __DIR__ . '/fcc_product_education_coach.php';

function fcc_coach_product_education_reference(string $text, string $language, string $marketContext = ''): array {
    $market = function_exists('fcc_coach_requested_market') ? fcc_coach_requested_market($marketContext !== '' ? $marketContext : $text) : '';
    if($market !== '' && $market !== 'HR') return ['enabled'=>false, 'reason'=>'different_market'];
    $selection = fcc_product_education_coach_context($text, $language, null, true);
    if(empty($selection['relevant'])) return ['enabled'=>false, 'reason'=>'no_product_anchor'];
    $packet = json_decode(substr($selection['grounded_context'], strlen('EDUCATION_REFERENCE_DATA ')), true) ?: [];
    $products = array_slice((array)($packet['products'] ?? []), 0, 3);
    foreach($products as &$product) {
        // Internal training does not establish a personal dose or another market's label.
        unset($product['usage']);
    }
    unset($product);
    $recommendations = array_slice((array)($packet['recommendations'] ?? []), 0, 1);
    foreach($recommendations as &$recommendation) unset($recommendation['product_usage'], $recommendation['use_logic']);
    unset($recommendation);
    return [
        'enabled'=>true, 'version'=>$selection['version'], 'source_market'=>'HR',
        'source_kind'=>'internal_product_education', 'products'=>$products, 'recommendations'=>$recommendations,
        'rule'=>'Ovo su odabrani podaci interne edukacije, ne upute ni gotov odgovor. Odgovori na cijeli posljednji zahtjev korisnika. Izvor ne potvrđuje liječenje bolesti, osobnu prikladnost, dozu, cijenu ili dostupnost u drugoj zemlji. Za deklaraciju i uporabu vrijedi pregledani catalog i njegovo tržište. Za bolest, dijete, trudnoću ili terapiju vrijede medicinska pravila. Ne pretvaraj opis sastojka u korist za bolest. Za poruku primatelju koristi samo provjerene javne osobne poveznice iz catalog ili personal_card_url. Ako osobna poveznica nedostaje, to jasno reci i ne izmišljaj je.',
    ];
}
/* /Custom code: FC-2026-10-13 */
