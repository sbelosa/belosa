<?php

define('ALTUMCODE', true);
define('DEBUG', false);
define('LOGGING', false);
define('MYSQL_DEBUG', false);
if(!defined('CACHE')) define('CACHE', false);

$rootPath = dirname(__DIR__);
$bootstrapPath = file_exists('/var/www/html/app/init.php')
    ? '/var/www/html/app/init.php'
    : $rootPath . '/app/init.php';

require_once $bootstrapPath;

if(class_exists('\Altum\Cache')) {
    \Altum\Cache::initialize();
}

if(class_exists('\Altum\Plugin')) {
    \Altum\Plugin::initialize();
}

if(class_exists('\Altum\Settings') && !\Altum\Settings::$settings) {
    \Altum\Settings::initialize();
}

if(class_exists('\Altum\Router')) {
    \Altum\Router::$path = \Altum\Router::$path ?? '';
}

if(class_exists('\Altum\Language')) {
    if(empty(\Altum\Language::$languages)) {
        \Altum\Language::initialize();
    }

    $defaultLanguageName = (string) (settings()->main->default_language ?? \Altum\Language::$main_name);
    \Altum\Language::set_default_by_name($defaultLanguageName);
    \Altum\Language::set_by_name($defaultLanguageName);
}

$scenarioPath = $argv[1] ?? (__DIR__ . '/fcc_ai_qa_scenarios_v2.php');

if(!file_exists($scenarioPath)) {
    fwrite(STDERR, "Scenario file not found: {$scenarioPath}\n");
    exit(1);
}

$scenarioBundle = require $scenarioPath;
$scenarios = (array) ($scenarioBundle['scenarios'] ?? []);

if(empty($scenarios)) {
    fwrite(STDERR, "No scenarios defined.\n");
    exit(1);
}

function qa_v2_find_link_id(): int {
    $preferred = (int) ($_SERVER['QA_LINK_ID'] ?? getenv('QA_LINK_ID') ?: 0);

    if($preferred > 0) {
        return $preferred;
    }

    $link = db()
        ->where('is_enabled', 1)
        ->orderBy('link_id', 'ASC')
        ->getOne('links', ['link_id']);

    return (int) ($link->link_id ?? 0);
}

function qa_v2_prepare_language(string $language): void {
    if(!class_exists('\Altum\Language')) {
        return;
    }

    $normalized = trim(mb_strtolower($language));

    if($normalized !== '') {
        \Altum\Language::set_by_code($normalized);

        if(!empty(\Altum\Language::$code) && mb_strtolower((string) \Altum\Language::$code) === $normalized) {
            return;
        }

        \Altum\Language::set_by_name($language);
    }

    if(empty(\Altum\Language::$name)) {
        $defaultLanguageName = (string) (settings()->main->default_language ?? \Altum\Language::$main_name);
        \Altum\Language::set_default_by_name($defaultLanguageName);
        \Altum\Language::set_by_name($defaultLanguageName);
    }
}

function qa_v2_contains_all(string $content, array $needles): bool {
    foreach($needles as $needle) {
        $needle = trim((string) $needle);
        if($needle === '') {
            continue;
        }

        if(mb_stripos($content, $needle) === false) {
            return false;
        }
    }

    return true;
}

function qa_v2_contains_any(string $content, array $needles): bool {
    foreach($needles as $needle) {
        $needle = trim((string) $needle);
        if($needle !== '' && mb_stripos($content, $needle) !== false) {
            return true;
        }
    }

    return false;
}

function qa_v2_contains_any_phrase(string $content, array $phrases): bool {
    $content = mb_strtolower(trim($content));

    if($content === '') {
        return false;
    }

    foreach($phrases as $phrase) {
        $phrase = mb_strtolower(trim((string) $phrase));

        if($phrase === '') {
            continue;
        }

        if(preg_match('/(^|[^\p{L}\p{N}_])' . preg_quote($phrase, '/') . '([^\p{L}\p{N}_]|$)/u', $content)) {
            return true;
        }
    }

    return false;
}

function qa_v2_normalize_list(array $items): array {
    return array_values(array_filter(array_map(static function($item) {
        return trim((string) $item);
    }, $items)));
}

function qa_v2_payload_products_blob(array $payload): string {
    $parts = [];

    $primary = trim((string) ($payload['primary_product'] ?? ''));
    if($primary !== '') {
        $parts[] = $primary;
    }

    $parts = array_merge($parts, qa_v2_normalize_list((array) ($payload['support_products'] ?? [])));
    $parts = array_merge($parts, qa_v2_normalize_list((array) ($payload['recommendation_lines'] ?? [])));

    return implode(' | ', array_values(array_unique(array_filter($parts))));
}

function qa_v2_slugify(string $value): string {
    $value = mb_strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/u', '_', $value) ?? '';
    return trim($value, '_');
}

function qa_v2_add_finding(array &$findings, string $area, string $message, bool $passed, int $weight = 1): void {
    $findings[] = [
        'area' => $area,
        'message' => $message,
        'passed' => $passed,
        'weight' => $weight,
    ];
}

function qa_v2_score_findings(array $findings): array {
    $earned = 0;
    $possible = 0;

    foreach($findings as $finding) {
        $weight = max(1, (int) ($finding['weight'] ?? 1));
        $possible += $weight;
        if(!empty($finding['passed'])) {
            $earned += $weight;
        }
    }

    $score = $possible > 0 ? (int) round(($earned / $possible) * 100) : 100;

    return [
        'earned' => $earned,
        'possible' => $possible,
        'score' => $score,
    ];
}

function qa_v2_evaluate_turn(array $turn, array $result): array {
    $expect = (array) ($turn['expect'] ?? []);
    $intent = (array) ($result['reply']['intent'] ?? []);
    $payload = (array) ($result['reply']['recommendation_payload'] ?? []);
    $reply = trim((string) ($result['reply']['content'] ?? ''));
    $lead = (array) ($result['reply']['lead_capture'] ?? []);
    $payloadProductsBlob = qa_v2_payload_products_blob($payload);
    $payloadQuestionBlob = implode(' | ', qa_v2_normalize_list((array) ($payload['question_lines'] ?? [])));
    $findings = [];

    if(!empty($expect['intent']['truthy'])) {
        foreach((array) $expect['intent']['truthy'] as $key) {
            qa_v2_add_finding(
                $findings,
                'intent',
                "Intent `{$key}` should be truthy.",
                !empty($intent[$key]),
                2
            );
        }
    }

    if(!empty($expect['intent']['equals'])) {
        foreach((array) $expect['intent']['equals'] as $key => $value) {
            qa_v2_add_finding(
                $findings,
                'intent',
                "Intent `{$key}` should equal `" . var_export($value, true) . "`.",
                ($intent[$key] ?? null) === $value,
                2
            );
        }
    }

    if(!empty($expect['intent']['falsy'])) {
        foreach((array) $expect['intent']['falsy'] as $key) {
            qa_v2_add_finding(
                $findings,
                'intent',
                "Intent `{$key}` should stay falsy.",
                empty($intent[$key]),
                2
            );
        }
    }

    if(!empty($expect['payload']['primary_product'])) {
        qa_v2_add_finding(
            $findings,
            'payload',
            'Primary product should match expected value.',
            trim((string) ($payload['primary_product'] ?? '')) === trim((string) $expect['payload']['primary_product']),
            3
        );
    }

    if(!empty($expect['payload']['primary_any'])) {
        qa_v2_add_finding(
            $findings,
            'payload',
            'Primary product should match one of the expected options.',
            qa_v2_contains_any(trim((string) ($payload['primary_product'] ?? '')), (array) $expect['payload']['primary_any']),
            3
        );
    }

    if(array_key_exists('primary_empty', (array) ($expect['payload'] ?? []))) {
        qa_v2_add_finding(
            $findings,
            'payload',
            'Primary product empty/non-empty state should match expectation.',
            ((trim((string) ($payload['primary_product'] ?? '')) === '') === (bool) $expect['payload']['primary_empty']),
            2
        );
    }

    if(!empty($expect['payload']['support_any'])) {
        qa_v2_add_finding(
            $findings,
            'payload',
            'Support products should include at least one expected item.',
            qa_v2_contains_any(
                implode(' | ', qa_v2_normalize_list((array) ($payload['support_products'] ?? []))),
                (array) $expect['payload']['support_any']
            ),
            2
        );
    }

    if(!empty($expect['payload']['support_all'])) {
        qa_v2_add_finding(
            $findings,
            'payload',
            'Support products should include all expected items.',
            qa_v2_contains_all(
                implode(' | ', qa_v2_normalize_list((array) ($payload['support_products'] ?? []))),
                (array) $expect['payload']['support_all']
            ),
            2
        );
    }

    if(!empty($expect['payload']['condition_keys_any'])) {
        qa_v2_add_finding(
            $findings,
            'payload',
            'Condition keys should include at least one expected profile.',
            qa_v2_contains_any(
                implode(' | ', qa_v2_normalize_list((array) ($payload['condition_keys'] ?? []))),
                (array) $expect['payload']['condition_keys_any']
            ),
            3
        );
    }

    if(!empty($expect['payload']['forbid_any'])) {
        qa_v2_add_finding(
            $findings,
            'payload',
            'Payload should not contain forbidden product drift.',
            !qa_v2_contains_any($payloadProductsBlob, (array) $expect['payload']['forbid_any']),
            4
        );
    }

    if(isset($expect['payload']['question_lines_min'])) {
        qa_v2_add_finding(
            $findings,
            'payload',
            'Question lines count should meet the minimum.',
            count((array) ($payload['question_lines'] ?? [])) >= (int) $expect['payload']['question_lines_min'],
            2
        );
    }

    if(!empty($expect['payload']['question_contains_any'])) {
        qa_v2_add_finding(
            $findings,
            'payload',
            'Question lines should contain at least one expected clarification signal.',
            qa_v2_contains_any($payloadQuestionBlob, (array) $expect['payload']['question_contains_any']),
            2
        );
    }

    if(!empty($expect['reply']['must_all'])) {
        qa_v2_add_finding(
            $findings,
            'reply',
            'Reply should contain all required signals.',
            qa_v2_contains_all($reply, (array) $expect['reply']['must_all']),
            3
        );
    }

    if(!empty($expect['reply']['must_any'])) {
        qa_v2_add_finding(
            $findings,
            'reply',
            'Reply should contain at least one expected signal.',
            qa_v2_contains_any($reply, (array) $expect['reply']['must_any']),
            2
        );
    }

    if(!empty($expect['reply']['forbid_any'])) {
        qa_v2_add_finding(
            $findings,
            'reply',
            'Reply should not contain forbidden drift signals.',
            !qa_v2_contains_any_phrase($reply, (array) $expect['reply']['forbid_any']),
            4
        );
    }

    if(array_key_exists('recommended', (array) ($expect['lead'] ?? []))) {
        qa_v2_add_finding(
            $findings,
            'lead',
            'Lead capture recommendation should match expectation.',
            (bool) ($lead['recommended'] ?? false) === (bool) $expect['lead']['recommended'],
            2
        );
    }

    if(!empty($expect['lead']['lead_type'])) {
        qa_v2_add_finding(
            $findings,
            'lead',
            'Lead type should match expectation.',
            trim((string) ($lead['lead_type'] ?? '')) === trim((string) $expect['lead']['lead_type']),
            2
        );
    }

    $score = qa_v2_score_findings($findings);
    $failedFindings = array_values(array_filter($findings, static function(array $finding) {
        return empty($finding['passed']);
    }));

    return [
        'score' => $score['score'],
        'earned' => $score['earned'],
        'possible' => $score['possible'],
        'status' => empty($failedFindings) ? 'PASS' : 'FAIL',
        'failed_findings' => $failedFindings,
        'findings' => $findings,
    ];
}

function qa_v2_evaluate_conversation(array $scenario, array $turnResults): array {
    $conversationExpect = (array) ($scenario['conversation'] ?? []);
    if(empty($conversationExpect)) {
        return [
            'status' => 'PASS',
            'score' => 100,
            'findings' => [],
            'failed_findings' => [],
        ];
    }

    $allReplies = implode("\n\n", array_map(static function(array $turnResult) {
        return trim((string) ($turnResult['result']['reply']['content'] ?? ''));
    }, $turnResults));
    $allPayloadProducts = implode("\n\n", array_map(static function(array $turnResult) {
        return qa_v2_payload_products_blob((array) ($turnResult['result']['reply']['recommendation_payload'] ?? []));
    }, $turnResults));
    $findings = [];

    if(!empty($conversationExpect['reply_forbid_any'])) {
        qa_v2_add_finding(
            $findings,
            'conversation',
            'Combined conversation replies should not contain forbidden drift.',
            !qa_v2_contains_any_phrase($allReplies, (array) $conversationExpect['reply_forbid_any']),
            4
        );
    }

    if(!empty($conversationExpect['reply_must_any'])) {
        qa_v2_add_finding(
            $findings,
            'conversation',
            'Combined conversation replies should contain at least one expected signal.',
            qa_v2_contains_any($allReplies, (array) $conversationExpect['reply_must_any']),
            2
        );
    }

    if(!empty($conversationExpect['reply_must_all'])) {
        qa_v2_add_finding(
            $findings,
            'conversation',
            'Combined conversation replies should contain all expected signals.',
            qa_v2_contains_all($allReplies, (array) $conversationExpect['reply_must_all']),
            2
        );
    }

    if(!empty($conversationExpect['payload_forbid_any'])) {
        qa_v2_add_finding(
            $findings,
            'conversation',
            'Combined payload products should not contain forbidden drift.',
            !qa_v2_contains_any($allPayloadProducts, (array) $conversationExpect['payload_forbid_any']),
            4
        );
    }

    if(!empty($conversationExpect['payload_must_any'])) {
        qa_v2_add_finding(
            $findings,
            'conversation',
            'Combined payload products should include at least one expected signal.',
            qa_v2_contains_any($allPayloadProducts, (array) $conversationExpect['payload_must_any']),
            2
        );
    }

    $score = qa_v2_score_findings($findings);
    $failedFindings = array_values(array_filter($findings, static function(array $finding) {
        return empty($finding['passed']);
    }));

    return [
        'status' => empty($failedFindings) ? 'PASS' : 'FAIL',
        'score' => $score['score'],
        'earned' => $score['earned'],
        'possible' => $score['possible'],
        'findings' => $findings,
        'failed_findings' => $failedFindings,
    ];
}

$linkId = qa_v2_find_link_id();

if($linkId <= 0) {
    fwrite(STDERR, "Unable to resolve an active link for QA.\n");
    exit(1);
}

$scenarioResults = [];
$totalTurnCount = 0;
$failedScenarioCount = 0;
$turnScores = [];

foreach($scenarios as $scenario) {
    $assistantType = trim((string) ($scenario['assistant_type'] ?? 'product_advisor'));
    $scope = trim((string) ($scenario['scope'] ?? 'public_app'));
    $language = trim((string) ($scenario['language'] ?? 'hr'));
    $scenarioId = trim((string) ($scenario['id'] ?? ('scenario_' . bin2hex(random_bytes(3)))));
    $conversationPublicId = 'qa_v2_' . qa_v2_slugify($scenarioId) . '_' . bin2hex(random_bytes(3));
    $visitorKey = 'qa_v2_' . bin2hex(random_bytes(4));
    $turnResults = [];

    foreach((array) ($scenario['messages'] ?? []) as $index => $turn) {
        qa_v2_prepare_language($language);

        $result = fcc_ai_handle_public_message([
            'assistant_type' => $assistantType,
            'scope' => $scope,
            'conversation_public_id' => $conversationPublicId,
            'link_id' => $linkId,
            'language' => $language,
            'message' => (string) ($turn['user'] ?? ''),
            'visitor_key' => $visitorKey,
            'source_context' => 'qa_framework_v2',
        ]);

        if(!empty($result['conversation_public_id'])) {
            $conversationPublicId = (string) $result['conversation_public_id'];
        }

        $evaluation = qa_v2_evaluate_turn($turn, $result);
        $turnResults[] = [
            'index' => $index + 1,
            'user' => (string) ($turn['user'] ?? ''),
            'result' => $result,
            'evaluation' => $evaluation,
        ];
        $turnScores[] = (int) $evaluation['score'];
        $totalTurnCount++;
    }

    $conversationEvaluation = qa_v2_evaluate_conversation($scenario, $turnResults);

    $scenarioStatus = $conversationEvaluation['status'];
    foreach($turnResults as $turnResult) {
        if(($turnResult['evaluation']['status'] ?? 'PASS') !== 'PASS') {
            $scenarioStatus = 'FAIL';
            break;
        }
    }

    if($scenarioStatus !== 'PASS') {
        $failedScenarioCount++;
    }

    $scenarioResults[] = [
        'id' => $scenarioId,
        'assistant_type' => $assistantType,
        'scope' => $scope,
        'language' => $language,
        'status' => $scenarioStatus,
        'turns' => array_map(static function(array $turnResult) {
            return [
                'index' => $turnResult['index'],
                'user' => $turnResult['user'],
                'reply' => trim((string) ($turnResult['result']['reply']['content'] ?? '')),
                'intent' => (array) ($turnResult['result']['reply']['intent'] ?? []),
                'payload' => (array) ($turnResult['result']['reply']['recommendation_payload'] ?? []),
                'lead_capture' => (array) ($turnResult['result']['reply']['lead_capture'] ?? []),
                'evaluation' => $turnResult['evaluation'],
            ];
        }, $turnResults),
        'conversation_evaluation' => $conversationEvaluation,
    ];
}

$summary = [
    'scenario_count' => count($scenarioResults),
    'turn_count' => $totalTurnCount,
    'failed_scenarios' => $failedScenarioCount,
    'passed_scenarios' => count($scenarioResults) - $failedScenarioCount,
    'average_turn_score' => !empty($turnScores) ? (int) round(array_sum($turnScores) / count($turnScores)) : 100,
    'failed_scenario_ids' => array_values(array_map(static function(array $scenarioResult) {
        return (string) $scenarioResult['id'];
    }, array_values(array_filter($scenarioResults, static function(array $scenarioResult) {
        return ($scenarioResult['status'] ?? 'PASS') !== 'PASS';
    })))),
];

echo json_encode([
    'meta' => [
        'framework' => (string) ($scenarioBundle['meta']['framework'] ?? 'fcc_ai_qa_framework_v2'),
        'version' => (string) ($scenarioBundle['meta']['version'] ?? '1.0'),
        'link_id' => $linkId,
        'generated_at' => date('c'),
    ],
    'summary' => $summary,
    'scenarios' => $scenarioResults,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
