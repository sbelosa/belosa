<?php
/** Actual offline regression for Claude R1/R2 and product-specific dose authority. */
if(PHP_SAPI !== 'cli') exit(1);
$source = realpath($argv[1] ?? dirname(__DIR__,2));
if(!$source || !is_file($source . '/app/helpers/fcc_coach_mentor.php')) {
    fwrite(STDERR, "Supply SOURCE_ROOT.\n"); exit(2);
}
define('ALTUMCODE', 66);
define('APP_PATH', $source . '/app/');
define('SITE_URL', 'https://fcc-coach-review.invalid/');
$sourceFiles = ['fcc_coach_mentor.php', 'fcc_i18n.php', 'fcc_ai.php', 'fcc_product_education_coach.php'];
$sourceBefore = [];
foreach($sourceFiles as $file) $sourceBefore[$file] = hash_file('sha256', APP_PATH . 'helpers/' . $file);
if(isset($argv[2]) && !hash_equals($argv[2], $sourceBefore['fcc_coach_mentor.php'])) {
    fwrite(STDERR, "Mentor source hash does not match the required candidate.\n"); exit(3);
}
function db() { throw new RuntimeException('Database access forbidden in offline test.'); }
function database() { throw new RuntimeException('Database access forbidden in offline test.'); }
require APP_PATH . 'helpers/fcc_i18n.php';
require APP_PATH . 'helpers/fcc_ai.php';
require APP_PATH . 'helpers/fcc_coach_mentor.php';
require APP_PATH . 'helpers/fcc_webinar_growth.php';

$one = ['intent' => ['latest_question' => 'Kako se uzima ImmuBlend?', 'recipient_forbidden' => false],
    'catalog' => ['medical_context' => false, 'products' => [
        ['product_key' => 'forever-immublend', 'title' => 'Forever ImmuBlend', 'label_usage' => ['Odrasli: 2 tablete dnevno uz obrok.']],
    ]]];
$two = $one;
$two['catalog']['products'][] = ['product_key' => 'forever-aloe-vera-gel', 'title' => 'Forever Aloe Vera Gel', 'label_usage' => ['Popiti 60 ml dnevno.']];
$ambiguous = $two;
$ambiguous['intent']['latest_question'] = 'Kako se uzimaju ImmuBlend i Aloe Vera Gel?';
$none = $one;
$none['catalog']['products'][0]['label_usage'] = [];
$decimal = $one;
$decimal['catalog']['products'] = [['product_key' => 'forever-aloe-vera-gel', 'title' => 'Forever Aloe Vera Gel', 'label_usage' => ['Popiti 2,5 ml dva puta dnevno.']]];
$decimal['intent']['latest_question'] = 'Kako se uzima Aloe Vera Gel?';
$twelve = $decimal;
$twelve['catalog']['products'][0]['label_usage'] = ['Popiti 12 ml dnevno.'];
$facts = $one;
$facts['catalog']['products'][0]['ingredient_facts'] = ['Jedna tableta sadrži 100 mg vitamina C.'];
$facts['catalog']['products'][0]['package_facts'] = ['Pakiranje sadrži 60 tableta.'];
$facts['catalog']['products'][0]['facts'] = ['Pakiranje sadrži 60 tableta.'];
$shortDoseNone = ['intent' => ['latest_question' => 'Koliko kapsula Arctic Sea se uzima dnevno?', 'recipient_forbidden' => false],
    'catalog' => ['medical_context' => false, 'products' => [
        ['product_key' => 'forever-arctic-sea', 'title' => 'Forever Arctic Sea', 'label_usage' => [], 'declared_directions' => []],
    ]]];
$shortDoseGrounded = $shortDoseNone;
$shortDoseGrounded['catalog']['products'][0]['label_usage'] = ['Uzimaj 2 kapsule dnevno.'];
$prototypeCases = [
    ['short_name_two_products', $two, 'ImmuBlend se uzima 2 tablete dnevno uz obrok.', false],
    ['full_name_two_products', $two, 'Forever ImmuBlend se uzima 2 tablete dnevno uz obrok.', false],
    ['number_word', $one, 'Uzima se dvije tablete dnevno uz obrok.', false],
    ['unit_without_space', $two, 'Forever Aloe Vera Gel: popij 60ml dnevno.', false],
    ['decimal_comma', $decimal, 'Popij 2,5 ml dva puta dnevno.', false],
    ['packaging_in_usage_answer', $one, 'Pakiranje sadrži 60 tableta. Uzmi 2 tablete dnevno uz obrok.', false],
    ['ingredient_in_usage_answer', $one, 'Jedna tableta sadrži 100 mg vitamina C. Odrasli: 2 tablete dnevno uz obrok.', false],
    ['invented_capsules', $one, 'Uzmi 3 kapsule dnevno.', true],
    ['invented_number_word', $one, 'Uzmi četiri tablete dnevno.', true],
    ['missing_label', $none, 'Uzmi 2 tablete dnevno.', true],
    ['regardless_of_meal', $one, 'Bez obzira na obrok uzmi 4 tablete dnevno.', true],
    ['decimal_suffix', $decimal, 'Popij 5 ml dva puta dnevno.', true],
    ['other_product_label', $two, 'Forever ImmuBlend: popij 60 ml dnevno.', true],
    ['quantity_with_meal_timing', $one, 'ImmuBlend: 3 tablete uz obrok.', true],
    ['refusal', $none, 'Ne mogu ti reći koliko tableta dnevno, provjeri deklaraciju.', false],
    ['negated_limit', $one, 'Ne uzimaj više od 2 tablete dnevno.', false],
];
$extraCases = [
    ['single_product_focus_elsewhere', $two, 'Informacija za ImmuBlend. Uzmi dvije tablete dnevno uz obrok.', false],
    ['correct_named_separate_clauses', $two, 'ImmuBlend se uzima 2 tablete dnevno uz obrok. Aloe Vera Gel: popij 60 ml dnevno.', false],
    ['both_names_no_clause_focus', $two, 'ImmuBlend je dodatak. Aloe Vera Gel je napitak. Uzmi 60 ml dnevno.', true],
    ['both_names_together_no_clause_focus', $two, 'Informacija za ImmuBlend i Aloe Vera Gel. Uzmi 60 ml dnevno.', true],
    ['other_name_does_not_authorize_first', $two, 'Aloe Vera Gel je napitak. ImmuBlend: popij 60 ml dnevno.', true],
    ['wrong_frequency_number', $one, 'ImmuBlend se uzima 2 tablete 2 puta dnevno uz obrok.', true],
    ['wrong_frequency_word', $one, 'ImmuBlend se uzima dvije tablete dva puta dnevno uz obrok.', true],
    ['decimal_dot_and_compact_unit', $decimal, 'Popij 2.5ml dva puta dnevno.', false],
    ['twelve_does_not_authorize_two', $twelve, 'Popij 2 ml dnevno.', true],
    ['package_cannot_authorize_usage', $facts, 'Uzmi 60 tableta dnevno.', true],
    ['ingredient_cannot_authorize_usage', $facts, 'Uzmi 100 mg dnevno.', true],
    ['timing_after_meal', $one, 'ImmuBlend: 3 tablete poslije obroka.', true],
    ['timing_before_meal', $one, 'ImmuBlend: 3 tablete prije obroka.', true],
    ['english_regardless', $one, 'Regardless of treatment, take 4 tablets daily.', true],
    ['ambiguous_question_separate_product_clauses', $ambiguous, 'ImmuBlend je dodatak. Aloe Vera Gel je napitak. Uzmi 60 ml dnevno.', true],
    ['ambiguous_question_both_products_in_dose_clause', $ambiguous, 'Za ImmuBlend i Aloe Vera Gel uzmi 60 ml dnevno.', true],
    ['usage_question_short_number_without_label', $shortDoseNone, 'Arctic Sea: 2 kapsule.', true],
    ['usage_question_short_number_grounded', $shortDoseGrounded, 'Arctic Sea: 2 kapsule.', false],
    ['usage_question_package_fact_only', $shortDoseNone, 'Pakiranje sadrži 60 kapsula.', false],
    ['usage_question_ingredient_fact_only', $shortDoseNone, 'Jedna kapsula sadrži 100 mg sintetskog sastojka.', false],
    ['usage_question_caps_without_label', $shortDoseNone, 'Arctic Sea: 2 caps.', true],
    ['usage_question_softgels_without_label', $shortDoseNone, 'Arctic Sea: 2 softgels.', true],
    ['usage_question_pills_without_label', $shortDoseNone, 'Arctic Sea: 2 pills.', true],
    ['usage_question_quantity_is_without_label', $shortDoseNone, 'Količina je 2 kapsule.', true],
    ['usage_question_package_fact_with_semicolon', $shortDoseNone, 'Pakiranje sadrži 120 kapsula; dnevnu količinu provjeri na deklaraciji.', false],
    ['usage_question_bottle_has_package_fact', $shortDoseNone, 'Bočica ima 120 kapsula, a dnevnu količinu provjeri na deklaraciji.', false],
    ['usage_question_in_box_package_fact', $shortDoseNone, 'U kutiji je 120 kapsula. Dnevnu količinu provjeri na deklaraciji.', false],
    ['usage_question_capsule_has_ingredient_fact', $shortDoseNone, 'Jedna kapsula ima 1000 mg ulja. Dnevnu količinu provjeri na deklaraciji.', false],
    ['usage_question_package_and_imperative', $shortDoseNone, 'Bočica ima 120 kapsula, uzmi 3 kapsule dnevno.', true],
    ['usage_question_english_capsule_has_fact', $shortDoseNone, 'One capsule has 1000 mg of oil.', false],
    ['usage_question_per_capsule_fact', $shortDoseNone, '1000 mg po kapsuli.', false],
];
$failed = []; $checks = 0; $passed = 0;
$check = static function(string $case, bool $expected, bool $actual, array $details = []) use(&$failed, &$checks, &$passed): void {
    $checks++;
    if($expected === $actual) $passed++;
    else $failed[] = ['case' => $case, 'expected_flagged' => $expected, 'actual_flagged' => $actual] + $details;
};
foreach(array_merge($prototypeCases, $extraCases) as [$case, $ctx, $text, $expectedFlagged]) {
    foreach(['guidance', 'recipient_message'] as $field) {
        $reply = ['guidance' => 'Neutralna informacija.', 'recipient_message' => ''];
        $reply[$field] = $text;
        $issues = fcc_coach_reply_issues($reply, $ctx);
        $check('dose_' . $field . '_' . $case, $expectedFlagged, in_array('ungrounded_dose', $issues, true), ['issues' => $issues]);
    }
}
// Field-specific focus: a name in guidance cannot silently authorize a recipient dose.
$crossField = ['guidance' => 'Informacija za ImmuBlend.', 'recipient_message' => 'Uzmi 60 ml dnevno.'];
$crossIssues = fcc_coach_reply_issues($crossField, $two);
$check('cross_field_other_product_dose', true, in_array('ungrounded_dose', $crossIssues, true), ['issues' => $crossIssues]);
$medical = $one;
$medical['catalog']['medical_context'] = true;
$medical['catalog']['products'] = [['title' => 'Forever Aloe Vera Gel', 'label_usage' => []]];
$medicalClauses = [
    ['no_doubt', 'Bez sumnje snižava tlak.', true],
    ['regardless_of_therapy', 'Bez obzira na terapiju, ovo snižava tlak.', true],
    ['forbid_claim', 'Nemoj pisati da snižava tlak.', false],
    ['without_claim', 'Bez tvrdnje da snižava tlak, pošalji samo deklaraciju.', false],
];
foreach($medicalClauses as [$case, $text, $expectedFlagged]) {
    $assertions = fcc_coach_medical_assertion_issues($text);
    $check('assertion_' . $case, $expectedFlagged, in_array('unverified_medical_benefit', $assertions, true), ['issues' => $assertions]);
    foreach(['guidance', 'recipient_message'] as $field) {
        $reply = ['guidance' => 'Neutralna informacija.', 'recipient_message' => ''];
        $reply[$field] = $text . ' Prije uzimanja provjeri deklaraciju sa svojim nefrologom.';
        $issues = fcc_coach_reply_issues($reply, $medical);
        $check('medical_' . $field . '_' . $case, $expectedFlagged, in_array('unverified_medical_benefit', $issues, true), ['issues' => $issues]);
    }
}
$sourceAfter = [];
foreach($sourceFiles as $file) $sourceAfter[$file] = hash_file('sha256', APP_PATH . 'helpers/' . $file);
$check('source_snapshot_stable', true, $sourceBefore === $sourceAfter);
echo json_encode([
    'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks, 'passed' => $passed, 'failed' => $failed,
    'claude_prototype_dose_clauses' => count($prototypeCases), 'extra_dose_clauses' => count($extraCases),
    'both_fields' => true, 'database_calls' => 0, 'api_calls' => 0,
    'source_hashes_before' => $sourceBefore, 'source_hashes_after' => $sourceAfter,
    'mentor_sha256' => hash_file('sha256', APP_PATH . 'helpers/fcc_coach_mentor.php'),
    'i18n_sha256' => hash_file('sha256', APP_PATH . 'helpers/fcc_i18n.php'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit($failed ? 1 : 0);
