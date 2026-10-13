<?php
/** Offline contract: actual helpers, synthetic requests, published education only. */
if (PHP_SAPI !== 'cli') exit(1);
$source = realpath($argv[1] ?? dirname(__DIR__,2));
if (!$source || !is_file($source . '/app/helpers/fcc_coach_product_reference.php')) {
    fwrite(STDERR, "Supply current SOURCE_ROOT.\n"); exit(2);
}
define('ALTUMCODE', 66);
define('APP_PATH', $source . '/app/');
define('SITE_URL', 'https://fcc-coach-review.invalid/');
function db() { throw new RuntimeException('Database access forbidden.'); }
function database() { throw new RuntimeException('Database access forbidden.'); }
$sourceHashes = [];
foreach (['fcc_ai.php', 'fcc_coach_mentor.php', 'fcc_coach_learning.php', 'fcc_product_education_coach.php', 'fcc_coach_product_reference.php', 'fcc_product_education_coach_bridge.php'] as $file) $sourceHashes[$file] = hash_file('sha256', APP_PATH . 'helpers/' . $file);
require APP_PATH . 'helpers/fcc_i18n.php';
require APP_PATH . 'helpers/fcc_ai.php';
require APP_PATH . 'helpers/fcc_coach_mentor.php';
require APP_PATH . 'helpers/fcc_product_education_coach_bridge.php';

$checks = []; $failed = [];
function verify(string $case, bool $passed, array $details = []): void {
    global $checks, $failed;
    $checks[] = ['case' => $case, 'pass' => $passed];
    if (!$passed) $failed[] = ['case' => $case] + $details;
}
function intentCase(string $case, string $latest, string $previous, string $mode, bool $inherits): void {
    $intent = fcc_coach_latest_turn_intent($latest, $previous === '' ? $latest : $previous . "\n" . $latest);
    verify($case . '_mode', ($intent['mode'] ?? '') === $mode, ['actual_mode' => $intent['mode'] ?? null]);
    verify($case . '_inheritance', ($intent['inherits_previous'] ?? false) === $inherits, ['actual_inherits_previous' => $intent['inherits_previous'] ?? null]);
    verify($case . '_latest_preserved', ($intent['latest_question'] ?? '') === $latest);
    if ($mode === 'learning') verify($case . '_no_recipient', !empty($intent['recipient_forbidden']) && empty($intent['recipient_required']));
}
$oldDraft = 'Napiši poruku kupcu o Forever Aloe Vera Gelu.';
intentCase('today_prefix_is_new_request', 'Danas želim razumjeti poslovni početak.', $oldDraft, 'learning', false);
intentCase('strong_prefix_is_new_request', 'Jako mi je važno razumjeti sastav Ever Shielda.', $oldDraft, 'learning', false);
intentCase('give_questions_is_new_request', 'Daj deset pitanja za početni razgovor s novim suradnicama.', $oldDraft, 'learning', false);
intentCase('yes_continues_draft', 'Da.', $oldDraft, 'message', true);
intentCase('shorten_continues_draft', 'Skrati poruku.', $oldDraft, 'message', true);
intentCase('add_sentence_continues_draft', 'Dodaj jednu kratku rečenicu na kraj.', $oldDraft, 'message', true);
intentCase('explicit_multiline_questions', "Napiši 10 pitanja za početni razgovor s dvije suradnice.\nŽelim razumjeti njihove ciljeve.\nNemoj pisati poruku kupcu.", $oldDraft, 'learning', false);
intentCase('named_product_draft', 'Napiši kratku poruku o Ever Shieldu za kupca.', '', 'message', false);
intentCase('learning_overrides_draft', 'Objasni mi sastav Ever Shielda.', $oldDraft, 'learning', false);
intentCase('app_overrides_product', 'Gdje da kliknem za instalaciju FCC aplikacije?', $oldDraft, 'learning', false);

foreach ([
    'ordinary_after' => 'Nakon razgovora trebam deset pitanja za suradnice.',
    'ordinary_click' => 'Where do I click to install FCC?',
    'ordinary_team' => 'Kako da pomognem novim suradnicima?',
    'training_questions' => 'Napiši pitanja za trening novih suradnica.',
    'conversation_supplement' => 'Napiši pitanja kao dopuna prvom razgovoru.',
] as $case => $query) {
    $reference = fcc_coach_product_education_reference($query, 'hr');
    verify($case . '_no_reference', empty($reference['enabled']), ['actual_enabled' => !empty($reference['enabled'])]);
}
$draftReference = fcc_coach_product_education_reference('Napiši poruku za kupca o Ever Shield.', 'hr');
verify('named_draft_keeps_factual_reference', !empty($draftReference['enabled']));
verify('named_draft_has_named_product', in_array('aloe-ever-shield', array_column($draftReference['products'] ?? [], 'product_key'), true));
$inflectedDraftReference = fcc_coach_product_education_reference('Napiši poruku za kupca o Ever Shieldu.', 'hr');
verify('named_inflected_draft_keeps_factual_reference', !empty($inflectedDraftReference['enabled']) && in_array('aloe-ever-shield', array_column($inflectedDraftReference['products'] ?? [], 'product_key'), true));
foreach ([
    'mentor_owns_named_education' => ['Objasni Ever Shield.', ['mentor_enabled' => true]],
    'legacy_draft_returns_to_conversation' => ['Napiši poruku kupcu o Ever Shield.', []],
    'legacy_questions_return_to_conversation' => ['Napiši deset pitanja za trening novih suradnica.', []],
    'legacy_platform_returns_to_conversation' => ['Gdje da kliknem da dodam Ever Shield na karticu?', []],
    'legacy_medical_returns_to_conversation' => ['Imam prijateljicu s bolešću bubrega, pitam o Aloe Vera Gelu.', []],
] as $case => [$query, $context]) {
    verify($case, fcc_product_education_coach_bridge_reply($query, $context + ['language' => 'hr']) === null);
}
$legacyEducation = fcc_product_education_coach_bridge_reply('Objasni Ever Shield.', ['language' => 'hr']);
verify('legacy_explicit_education_preserved', is_array($legacyEducation) && ($legacyEducation['reply_mode'] ?? '') === 'product_education' && trim($legacyEducation['guidance'] ?? '') !== '');
verify('legacy_learning_has_no_recipient_draft', is_array($legacyEducation) && ($legacyEducation['recipient_message'] ?? '') === '');

foreach ([
    'market_de_hr' => ['Objasni Aloe Vera Gel za Njemačku.', 'hr'],
    'market_de_native' => ['Explain Aloe Vera Gel for Germany.', 'de'],
    'market_si_hr' => ['Objasni Aloe Vera Gel za Sloveniju.', 'hr'],
    'market_si_native' => ['Razloži Aloe Vera Gel za Slovenijo.', 'sl'],
] as $case => [$query, $language]) {
    $reference = fcc_coach_product_education_reference($query, $language);
    verify($case . '_hr_reference_disabled', empty($reference['enabled']), ['actual_enabled' => !empty($reference['enabled'])]);
}
$hr = fcc_coach_product_education_reference('Objasni Aloe Vera Gel za Hrvatsku.', 'hr');
verify('market_hr_reference_enabled', !empty($hr['enabled']) && ($hr['source_market'] ?? '') === 'HR');
$continuedDe = fcc_coach_product_education_reference('Objasni Aloe Vera Gel.', 'hr', "Kupac je u Njemačkoj.\nObjasni Aloe Vera Gel.");
verify('market_de_retained_for_reference', empty($continuedDe['enabled']));
$changedHr = fcc_coach_product_education_reference('Objasni Aloe Vera Gel za Hrvatsku.', 'hr', "Kupac je u Njemačkoj.\nNova tema, Aloe Vera Gel za Hrvatsku.");
verify('explicit_market_change_to_hr_enabled', !empty($changedHr['enabled']));

// Entry-specific proof: an unrelated title token must not outrank a nonmatching topic.
$synthetic = [
    'products' => [['product_key' => 'synthetic-product', 'name' => 'Synthetic product', 'aliases' => ['synthetic product']]],
    'published_recommendation_entries' => [[
        'id' => 'unrelated_entry', 'title' => 'Napredak nakon rada',
        'practical_application' => 'Razgovor o osobnom rasporedu.',
        'products' => [['product_key' => 'synthetic-product']],
        'publication_status' => 'published',
    ]],
    'educational_entries' => [],
];
$unrelated = fcc_product_education_coach_search('Što trebam znati o imunitetu nakon rada?', $synthetic, 'hr', true);
verify('topic_without_entry_specific_match_is_empty', empty($unrelated['relevant']), ['actual_ids' => array_column($unrelated['recommendations'] ?? [], 'id')]);
$exact = fcc_product_education_coach_search('Napredak nakon rada', $synthetic, 'hr', true);
verify('exact_entry_title_still_matches', !empty($exact['relevant']) && in_array('unrelated_entry', array_column($exact['recommendations'] ?? [], 'id'), true));
$direct = fcc_product_education_coach_search('Napiši poruku o Synthetic product.', $synthetic, 'hr', true);
verify('named_product_reference_survives_message_intent', !empty($direct['relevant']) && in_array('synthetic-product', $direct['direct_product_keys'] ?? [], true));

function nestedKeys(array $value): array {
    $keys = [];
    foreach ($value as $key => $entry) {
        if (is_string($key)) $keys[] = $key;
        if (is_array($entry)) $keys = array_merge($keys, nestedKeys($entry));
    }
    return $keys;
}
foreach ([
    'aloe_usage' => 'Kako se koristi Aloe Vera Gel?',
    'immunity' => 'Objasni imunitet.',
    'many_named' => 'Objasni Aloe Vera Gel, Arctic Sea, Royal Jelly, ARGI i Ever Shield.',
] as $case => $query) {
    $reference = fcc_coach_product_education_reference($query, 'hr');
    verify($case . '_reference_enabled', !empty($reference['enabled']));
    verify($case . '_product_bound', count($reference['products'] ?? []) <= 3);
    verify($case . '_recommendation_bound', count($reference['recommendations'] ?? []) <= 1);
    $forbidden = array_intersect(nestedKeys($reference), ['usage', 'product_usage', 'use_logic', 'declared_directions', 'label_instructions', 'package_plans', 'customer_message']);
    verify($case . '_no_usage_fields', !$forbidden, ['unexpected_keys' => array_values(array_unique($forbidden))]);
    $packet = json_encode($reference, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $dosePattern = '/\b\d+(?:[.,]\d+)?\s*(?:ml|mililitar\p{L}*|tableta\p{L}*|kapsul\p{L}*|mjeric\p{L}*)\b|\b(?:jedn\p{L}*|dv\p{L}*|tri|cetiri|\d+)\s+(?:puta\s+)?(?:dnevno|na dan)\b/iu';
    verify($case . '_no_dose_in_reference_text', !preg_match($dosePattern, fcc_product_education_coach_normalize($packet)));
    verify($case . '_not_an_answer', !array_key_exists('guidance', $reference) && !array_key_exists('recipient_message', $reference));
    verify($case . '_bounded_packet_bytes', strlen($packet) <= 14000, ['actual_bytes' => strlen($packet)]);
}
// Every published, nonwithdrawn entry keeps an exact ID retrieval path.
$published = fcc_product_education_content(); $publishedCount = 0;
foreach (array_merge($published['published_recommendation_entries'] ?? [], $published['educational_entries'] ?? []) as $entry) {
    if (($entry['publication_status'] ?? '') === 'withdrawn_by_author' || empty($entry['practical_application'])) continue;
    $id = (string) ($entry['id'] ?? ''); if ($id === '') continue;
    $selection = fcc_product_education_coach_search($id, $published, 'hr', true);
    verify('published_exact_id_' . $id, !empty($selection['relevant']) && in_array($id, array_column($selection['recommendations'] ?? [], 'id'), true));
    $publishedCount++;
}
$hashes = [];
foreach (['fcc_ai.php', 'fcc_coach_mentor.php', 'fcc_coach_learning.php', 'fcc_product_education_coach.php', 'fcc_coach_product_reference.php', 'fcc_product_education_coach_bridge.php'] as $file) $hashes[$file] = hash_file('sha256', APP_PATH . 'helpers/' . $file);
verify('source_stable_during_fixture', $sourceHashes === $hashes);
echo json_encode(['status' => $failed ? 'FAIL' : 'PASS', 'checks' => count($checks), 'passed' => count($checks) - count($failed), 'failed' => $failed, 'published_exact_id_cases' => $publishedCount, 'database_calls' => 0, 'api_calls' => 0, 'source_hashes' => $hashes], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit($failed ? 1 : 0);
