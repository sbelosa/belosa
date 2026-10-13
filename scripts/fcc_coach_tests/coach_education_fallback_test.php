<?php
/** Offline contract: actual fallback, retrieval, and dose helpers with local published facts. */
if(PHP_SAPI !== 'cli') exit(1);
$source = realpath($argv[1] ?? dirname(__DIR__,2));
if(!$source || !is_file($source . '/app/helpers/fcc_ai.php')) {
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

$context = ['language' => 'hr', 'mentor_enabled' => true, 'user_id' => 0, 'conversation_id' => 0];
$failed = []; $checks = 0; $results = [];
$check = static function(string $case, bool $condition, array $detail = []) use(&$failed, &$checks): void {
    $checks++;
    if(!$condition) $failed[] = ['case' => $case] + $detail;
};
$attempts = [
    'api_error' => ['success' => false, 'reason' => 'api_error'],
    'missing_api_key' => ['success' => false, 'reason' => 'missing_api_key'],
    'exception' => ['success' => false, 'reason' => 'exception'],
];
$ordinary = [
    ['immublend_composition', 'Što sadrži Forever ImmuBlend?', 'forever-immublend'],
    ['ever_shield_description', 'Objasni Forever Ever Shield.', 'aloe-ever-shield'],
    ['arctic_sea_usage', 'Kako se uzima Forever Arctic Sea?', 'forever-arctic-sea'],
];
foreach($attempts as $reason => $attempt) foreach($ordinary as [$name, $message, $productKey]) {
    $case = $reason . '_' . $name;
    $reply = fcc_ai_internal_coach_education_fallback($message, $context, $attempt);
    $check($case . '_available', is_array($reply));
    if(!is_array($reply)) continue;
    $check($case . '_nonempty_guidance', trim((string)($reply['guidance'] ?? '')) !== '');
    $check($case . '_no_recipient', ($reply['recipient_message'] ?? null) === '');
    $check($case . '_local_lane', !empty($reply['force_local_preview']) && ($reply['reply_mode'] ?? '') === 'product_education');
    $check($case . '_version', trim((string)($reply['product_education']['version'] ?? '')) !== '');
    // The product identity must be retrieved from the request, not a generic recommendation.
    $keys = (array)($reply['product_education']['product_keys'] ?? []);
    $check($case . '_requested_product', in_array($productKey, $keys, true), ['actual_product_keys' => $keys]);
    // Build label authority from the same actual published selection, without inventing labels.
    $selection = fcc_product_education_coach_context($message, 'hr');
    $labels = [];
    foreach((array)($selection['products'] ?? []) as $product) {
        if(!in_array((string)($product['product_key'] ?? ''), $keys, true)) continue;
        $labels[] = ['product_key' => $product['product_key'], 'title' => $product['name'],
            'declared_directions' => (array)($product['declared_directions'] ?? []), 'label_usage' => []];
    }
    $reviewContext = ['language' => 'hr', 'intent' => ['mode' => 'learning', 'recipient_forbidden' => true, 'latest_question' => $message],
        'catalog' => ['medical_context' => false, 'products' => $labels]];
    $issues = fcc_coach_reply_issues($reply, $reviewContext);
    $check($case . '_reviewed_usage', !in_array('ungrounded_dose', $issues, true), ['actual_issues' => $issues]);
    $results[] = ['case' => $case, 'product_keys' => $keys, 'declared_label_products' => count($labels), 'review_issues' => $issues];
}
$guardedAttempts = [
    'quality_failure' => ['success' => false, 'reason' => 'coach_response_review_failed'],
    'successful' => ['success' => true],
    'successful_with_error_reason' => ['success' => true, 'reason' => 'api_error'],
    'successful_with_quality_reason' => ['success' => true, 'reason' => 'coach_response_review_failed'],
];
foreach($guardedAttempts as $case => $attempt) {
    $check($case . '_no_fallback', fcc_ai_internal_coach_education_fallback('Što sadrži Forever ImmuBlend?', $context, $attempt) === null);
}
$blocked = [
    ['customer_copy', 'Napiši kratku poruku kupcu o Forever Aloe Lips.'],
    ['business_questions', 'Koja pitanja postaviti suradniku na početku Forever poslovanja? Spomeni ImmuBlend.'],
    ['business_pricing', 'Kolika je cijena i provizija za Forever ImmuBlend?'],
    ['platform_edit', 'Kako dodati Forever ImmuBlend na svoju web stranicu?'],
    ['kidney_context', 'Prijateljica ima bolest bubrega. Što sadrži Forever Aloe Vera Gel?'],
    ['pregnancy_context', 'Trudna sam. Kako se uzima Forever ImmuBlend?'],
    ['child_context', 'Kako se uzima Forever ImmuBlend za dijete?'],
    ['urgent_context', 'Imam bol u prsima. Objasni Forever ImmuBlend.'],
    ['fish_allergy', 'Alergična sam na ribu. Što sadrži Forever Arctic Sea?'],
    ['other_market', 'Što sadrži Forever ImmuBlend u Njemačkoj?'],
    ['unrelated', 'Kako riješiti račun za struju?'],
];
foreach($blocked as [$case, $message]) {
    $check($case . '_no_fallback', fcc_ai_internal_coach_education_fallback($message, $context, $attempts['api_error']) === null);
}
echo json_encode([
    'status' => $failed ? 'FAIL' : 'PASS', 'checks' => $checks, 'passed' => $checks - count($failed),
    'failed' => $failed, 'ordinary_results' => $results, 'database_calls' => 0, 'api_calls' => 0,
    'scope' => 'Actual pure fallback and bridge with conversation_id=0 and user_id=0. Live history and actual model output are not exercised.',
    'ai_sha256' => hash_file('sha256', APP_PATH . 'helpers/fcc_ai.php'),
    'mentor_sha256' => hash_file('sha256', APP_PATH . 'helpers/fcc_coach_mentor.php'),
    'bridge_sha256' => hash_file('sha256', APP_PATH . 'helpers/fcc_product_education_coach_bridge.php'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit($failed ? 1 : 0);
