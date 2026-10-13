<?php
/** Offline regression contract, actual production helpers and synthetic data only. */
if (PHP_SAPI !== 'cli') exit(1);
$source = realpath($argv[1] ?? dirname(__DIR__,2));
if (!$source || !is_file($source . '/app/helpers/fcc_coach_mentor.php')) {
    fwrite(STDERR, "Supply SOURCE_ROOT.\n"); exit(2);
}
define('ALTUMCODE', 66);
define('APP_PATH', $source . '/app/');
define('SITE_URL', 'https://fcc-coach-review.invalid/');
function db() { throw new RuntimeException('Database access forbidden in offline test.'); }
function database() { throw new RuntimeException('Database access forbidden in offline test.'); }
require APP_PATH . 'helpers/fcc_i18n.php';
require APP_PATH . 'helpers/fcc_ai.php';
require APP_PATH . 'helpers/fcc_coach_mentor.php';
require APP_PATH . 'helpers/fcc_webinar_growth.php';

$productUrl = SITE_URL . 'blog/aloe-vera-gel?ref=synthetic-owner';
$privateEducationUrl = SITE_URL . 'fcc-product-education?section=products&product=forever-aloe-vera-gel';
$context = [
    'language' => 'hr',
    'intent' => ['mode' => 'contextual', 'recipient_forbidden' => false],
    'catalog' => ['medical_context' => true, 'products' => [[
        'title' => 'Forever Aloe Vera Gel', 'referral_url' => $productUrl,
    ]]],
    'education' => ['internal_links' => [['url' => $privateEducationUrl]]],
];
$neutral = 'Forever Aloe Vera Gel je aloe napitak. Prije uzimanja provjeri deklaraciju sa svojim nefrologom.';
$cases = [
    ['guidance_kidney_claim', false, 'Forever Aloe Vera Gel liječi bolest bubrega. Kupi ga sada.', ''],
    ['guidance_explicit_refusal', true, 'Ne mogu preporučiti Forever Aloe Vera Gel za liječenje bolesti bubrega. O tome razgovaraj sa svojim nefrologom.', ''],
    ['guidance_negated_claim', true, 'Forever Aloe Vera Gel ne liječi bolest bubrega. Nemoj ga kupiti kao terapiju.', ''],
    ['guidance_negation_then_positive', false, 'Forever Aloe Vera Gel ne liječi bolest bubrega, ali regulira krvni tlak.', ''],
    ['guidance_negative_sentence_then_claim', false, 'Ne mogu preporučiti liječenje. Forever Aloe Vera Gel liječi bolest bubrega.', ''],
    ['guidance_negative_sentence_then_sales', false, 'To nije lijek za bolest bubrega. Kupi Forever Aloe Vera Gel sada i isprobaj ga.', ''],
    ['guidance_neutral_facts_and_review', true, $neutral, ''],
    ['guidance_neutral_facts_and_public_referral', true, $neutral . "\n" . $productUrl, ''],
    ['recipient_kidney_claim', false, 'Možeš poslati neutralne informacije.', 'Forever Aloe Vera Gel liječi bolest bubrega. Kupi ga sada.'],
    ['recipient_named_supplement_without_review', false, 'Potrebna je provjera s nefrologom prije uzimanja.', 'Forever Aloe Vera Gel je aloe napitak.'],
    ['recipient_optional_review', false, 'Pripremi neutralne informacije.', 'Forever Aloe Vera Gel je aloe napitak. Ako želiš, provjeri deklaraciju s liječnikom prije uzimanja.'],
    ['recipient_neutral_facts_mandatory_review', true, 'Možeš poslati neutralne informacije.', $neutral],
    ['recipient_public_referral_preserved', true, 'Možeš poslati neutralne informacije.', $neutral . "\n" . $productUrl],
    ['recipient_internal_education_url', false, 'Možeš poslati neutralne informacije.', $neutral . "\n" . $privateEducationUrl],
    ['guidance_internal_education_url', true, $neutral . "\n" . $privateEducationUrl, ''],
];
$clauses = [
    ['denial_pressure', 'Aloe vera ne snižava tlak i nije zamjena za lijek.', false],
    ['contrasting_positive', 'Ovaj proizvod nije lijek, ali snižava tlak.', true],
    ['new_sentence_positive', 'Ne brini. Aloe vera snižava tlak.', true],
    ['remember_positive', 'Ne zaboravi da ovaj napitak snižava tlak.', true],
    ['causal_positive', 'Ne trebaš lijekove jer ovo snižava tlak.', true],
    ['no_doubt_positive', 'Bez sumnje snižava tlak.', true],
    ['not_only_positive', 'Ne samo da snižava tlak nego i šećer.', true],
    ['forbid_writing_claim', 'Nemoj pisati da snižava tlak.', false],
    ['forbid_saying_claim', 'Ne smiješ reći da ovaj proizvod snižava tlak.', false],
    ['forbid_product_claim', 'Nemoj tvrditi da Aloe Vera Gel liječi dijabetes.', false],
    ['without_assertion', 'Bez tvrdnje da snižava tlak, pošalji samo deklaraciju.', false],
    ['quoted_question', 'Kupac pita snižava li Aloe tlak; reci mu da to ne možeš tvrditi.', false],
    ['healthy_breakfast', 'Uz zdravi doručak i šetnju dodaj čašu vode.', false],
    ['healthy_habits', 'Za zdravih navika vrijedi pitati liječnika.', false],
    ['direct_diabetes_claim', 'Aloe Vera Gel liječi dijabetes.', true],
    ['future_kidney_cure', 'Ovaj napitak će ti izliječiti bubrege.', true],
    ['never_forget_positive', 'Nikad ne zaboravi da ovo snižava tlak.', true],
    ['unproven_but_positive', 'Ovo nije dokazano, a ipak snižava tlak.', true],
];
$assertionFailed = [];
foreach($clauses as [$label, $text, $asserted]) {
    $actual = in_array('unverified_medical_benefit', fcc_coach_medical_assertion_issues($text), true);
    if($actual !== $asserted) $assertionFailed[] = ['case' => $label, 'expected_asserted' => $asserted, 'actual_asserted' => $actual];
    foreach(['guidance', 'recipient'] as $field) {
        $reviewedText = $text . ' Prije uzimanja provjeri deklaraciju sa svojim nefrologom.';
        $cases[] = ['clause_' . $field . '_' . $label, !$asserted, $field === 'guidance' ? $reviewedText : 'Neutralna pomoć suradniku.', $field === 'recipient' ? $reviewedText : '', $context, $asserted ? 'unverified_medical_benefit' : null];
    }
}
$doseContext = [
    'language' => 'hr', 'last_user_message' => 'Kako se uzima Forever ImmuBlend?',
    'intent' => ['mode' => 'contextual', 'recipient_forbidden' => false],
    'catalog' => ['medical_context' => false, 'products' => [['product_key' => 'forever-immublend', 'title' => 'Forever ImmuBlend', 'label_usage' => [], 'declared_directions' => []]]],
];
$labelDose = $doseContext;
$labelDose['catalog']['products'][0]['label_usage'] = ['Uzimaj 3 kapsule jednom dnevno.'];
$declaredDose = $doseContext;
$declaredDose['catalog']['products'][0]['declared_directions'] = ['Uzimaj 2 kapsule 2 puta dnevno uz obrok.'];
$factDose = $doseContext;
$factDose['catalog']['products'][0]['ingredients'] = ['Jedna kapsula sadrži 400 mg sintetskog sastojka.'];
$factDose['catalog']['products'][0]['facts'] = ['Pakiranje sadrži 100 kapsula.', 'Opis spominje 3 kapsule dnevno.'];
$factDose['catalog']['products'][0]['package_facts'] = ['Pakiranje sadrži 3 kapsule.'];
$ordinaryFacts = $factDose;
$ordinaryFacts['last_user_message'] = 'Što sadrži Forever ImmuBlend i što je u pakiranju?';
$labelTwelveMl = $doseContext;
$labelTwelveMl['catalog']['products'][0]['label_usage'] = ['Uzimaj 12 ml dnevno.'];
$labelDecimalMl = $doseContext;
$labelDecimalMl['catalog']['products'][0]['label_usage'] = ['Uzimaj 2.5 ml dnevno.'];
$otherProductDose = $doseContext;
$otherProductDose['catalog']['products'][] = ['product_key' => 'arctic-sea', 'title' => 'Forever Arctic Sea', 'label_usage' => ['Uzimaj 3 kapsule dnevno.']];
$doseCases = [
    ['ungrounded_three_capsules', false, 'Uzmi 3 kapsule dnevno.', $doseContext],
    ['exact_verified_label_usage', true, 'Uzimaj 3 kapsule jednom dnevno.', $labelDose],
    ['mismatched_daily_frequency', false, 'Uzimaj 3 kapsule 2 puta dnevno.', $labelDose],
    ['mismatched_quantity', false, 'Uzimaj 4 kapsule jednom dnevno.', $labelDose],
    ['exact_verified_declared_directions', true, 'Uzimaj 2 kapsule 2 puta dnevno uz obrok.', $declaredDose],
    ['ingredient_and_package_do_not_authorize_dose', false, 'Uzmi 3 kapsule dnevno.', $factDose],
    ['ingredient_quantity_does_not_authorize_dose', false, 'Uzmi 400 mg dnevno.', $factDose],
    ['package_count_does_not_authorize_dose', false, 'Uzmi 100 kapsula dnevno.', $factDose],
    ['ordinary_verified_ingredient_fact', true, 'Jedna kapsula sadrži 400 mg sintetskog sastojka.', $ordinaryFacts],
    ['ordinary_verified_package_fact', true, 'Pakiranje sadrži 100 kapsula.', $ordinaryFacts],
    ['label_twelve_ml_does_not_authorize_two_ml', false, 'Uzimaj 2 ml dnevno.', $labelTwelveMl],
    ['label_decimal_does_not_authorize_integer_suffix', false, 'Uzimaj 5 ml dnevno.', $labelDecimalMl],
    ['exact_verified_decimal_quantity', true, 'Uzimaj 2.5 ml dnevno.', $labelDecimalMl],
    ['other_product_label_does_not_authorize_named_first_product', false, 'Za Forever ImmuBlend uzimaj 3 kapsule dnevno.', $otherProductDose],
    ['named_second_product_exact_label', true, 'Za Forever Arctic Sea uzimaj 3 kapsule dnevno.', $otherProductDose],
];
foreach($doseCases as [$label, $accepted, $text, $caseContext]) foreach(['guidance', 'recipient'] as $field) {
    $cases[] = ['dose_' . $field . '_' . $label, $accepted, $field === 'guidance' ? $text : 'Neutralna pomoć suradniku.', $field === 'recipient' ? $text : '', $caseContext, $accepted ? null : 'ungrounded_dose'];
}
$failed = []; $passed = 0;
foreach ($cases as $case) {
    [$label, $expectedAccepted, $guidance, $recipient] = $case;
    $caseContext = $case[4] ?? $context;
    $requiredIssue = $case[5] ?? null;
    $reply = fcc_coach_decode_reply(json_encode([
        'guidance' => $guidance, 'recipient_message' => $recipient,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $caseContext);
    $issues = $reply !== null ? fcc_coach_reply_issues($reply, $caseContext) : [];
    $accepted = $reply !== null && $issues === [];
    if ($accepted !== $expectedAccepted || ($requiredIssue !== null && !in_array($requiredIssue, $issues, true))) {
        $failed[] = ['case' => $label, 'expected_accepted' => $expectedAccepted, 'actual_accepted' => $accepted, 'required_issue' => $requiredIssue, 'actual_issues' => $issues];
    } else {
        $passed++;
    }
    if ($expectedAccepted && $accepted && str_contains($recipient, $productUrl)
        && !str_contains($reply['recipient_message'], $productUrl)) {
        $failed[] = ['case' => $label . '_exact_referral_preservation'];
    }
}
echo json_encode([
    'status' => $failed || $assertionFailed ? 'FAIL' : 'PASS', 'cases' => count($cases), 'passed' => $passed,
    'assertion_checks' => count($clauses), 'assertion_failed' => $assertionFailed,
    'failed' => $failed, 'database_calls' => 0, 'api_calls' => 0,
    'helper_sha256' => hash_file('sha256', APP_PATH . 'helpers/fcc_coach_mentor.php'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit($failed || $assertionFailed ? 1 : 0);
