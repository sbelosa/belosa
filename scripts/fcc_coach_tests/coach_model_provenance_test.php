<?php
/* Real Coach caller, provider wrapper, request builder and parser; synthetic transport and spend boundaries. */
declare(strict_types=1);
namespace Unirest {
    final class Request {
        public static function timeout(int $seconds): void {}
        public static function getInfo(): array { return ['total_time' => 0]; }
        public static function post(string $url, array $headers, $body): object {
            if($url !== 'https://api.openai.com/v1/chat/completions') throw new \RuntimeException('Unexpected mock endpoint.');
            $payload = is_string($body) ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : $body;
            $GLOBALS['model_http_payloads'][] = $payload;
            return (object) ['code' => 200, 'body' => (object) ['model' => $payload['model'], 'choices' => [(object) ['finish_reason' => 'stop', 'message' => (object) ['content' => '{"guidance":"Synthetic guidance.","recipient_message":""}']]], 'usage' => (object) ['prompt_tokens' => 10, 'completion_tokens' => 10, 'total_tokens' => 20]]];
        }
    }
}
namespace Unirest\Request { final class Body { public static function json(array $payload): string { return json_encode($payload, JSON_THROW_ON_ERROR); } } }
namespace {
    function model_extract(string $source, string $name): string {
        $tokens = token_get_all($source); $count = count($tokens);
        for($i = 0; $i < $count; $i++) {
            if(!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
            $j = $i + 1;
            while($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) ++$j;
            if(!is_array($tokens[$j] ?? null) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $name) continue;
            $code = ''; $depth = 0; $opened = false;
            for($j = $i; $j < $count; $j++) {
                $token = $tokens[$j]; $code .= is_array($token) ? $token[1] : $token;
                if($token === '{' || (is_array($token) && in_array($token[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) { ++$depth; $opened = true; }
                elseif($token === '}' && --$depth === 0 && $opened) return $code;
            }
        }
        throw new \RuntimeException('Missing function: ' . $name);
    }
    final class ModelDatabase {
        public function where(...$args): self { return $this; }
        public function orderBy(...$args): self { return $this; }
        public function get(...$args): array { return [(object) ['content' => 'Synthetic request.']]; }
        public function query(string $sql): bool { if($sql !== 'set session wait_timeout=600;') throw new \RuntimeException('Unexpected SQL mock.'); return true; }
    }
    function db(): ModelDatabase { return new ModelDatabase(); }
    function database(): ModelDatabase { return new ModelDatabase(); }
    function fcc_ai_get_openai_api_key(): string { return $GLOBALS['model_case']['missing_key'] ? '' : 'synthetic-offline-key'; }
    function fcc_ai_resolve_model_route(string $route): string { return $GLOBALS['model_case']['configured']; }
    function fcc_journey_enabled(int $uid): bool { return true; }
    function fcc_coach_history_rows(object $conversation): array { return [['role' => 'user', 'content' => 'Synthetic request.']]; }
    function fcc_coach_history_packet(array $rows, string $message): array { return ['recent' => $rows, 'coverage' => ['is_complete' => true], 'earlier' => []]; }
    function fcc_coach_retrieval_text(array $rows): string { return 'Synthetic retrieval.'; }
    function fcc_coach_mentor_context(int $uid, string $text, array $context): array { return ['language' => 'hr', 'intent' => ['mode' => 'learning'], 'product_education_reference' => ['version' => 'synthetic-reference-version', 'products' => [], 'recommendations' => []]]; }
    function fcc_coach_prompt_from_context(array $context): string { return "Synthetic stable rules.\n\nServer context JSON:\n{}"; }
    function fcc_coach_learning_metadata(array $context, array $rows): array { return []; }
    function fcc_ai_locale_instruction(string $language): string { return 'Synthetic locale ' . $language; }
    function fcc_ai_build_conversation_model_messages(object $conversation, string $prompt, ...$args): array { return [['role' => 'system', 'content' => $prompt], ['role' => 'user', 'content' => 'Synthetic request.']]; }
    function fcc_coach_delivery_instructions(array $context, array $history): string { return 'SYNTHETIC_COMPLETE_DELIVERY_CONTRACT'; }
    function fcc_ai_spend_model(int $uid, string $channel, string $requested): string {
        $GLOBALS['model_early_requested'][] = $requested;
        return $GLOBALS['model_case']['early_down'] ? 'gpt-6-luna' : $requested;
    }
    function fcc_ai_spend_preview(int $uid, string $channel, string $requested): array {
        return ['model' => fcc_ai_spend_model($uid, $channel, $requested), 'downgraded' => $GLOBALS['model_case']['early_down'], 'reason' => $GLOBALS['model_case']['early_down'] ? 'monthly_soft_budget' : 'within_budget'];
    }
    function fcc_ai_spend_begin(int $uid, string $channel, string $requested): array {
        $ticket = ['allowed' => true, 'requested_model' => $requested, 'model' => $GLOBALS['model_case']['late_down'] ? 'gpt-6-luna' : $requested, 'reason' => $GLOBALS['model_case']['late_down'] ? 'monthly_soft_budget' : 'within_budget'];
        $GLOBALS['model_tickets'][] = $ticket; return $ticket;
    }
    function fcc_ai_spend_finish(array $ticket, array $result): array { $GLOBALS['model_ledger_requested'][] = $ticket['requested_model']; return $result + ['spend_recorded' => 1]; }
    function fcc_ai_spend_release(array $ticket): void {}
    function fcc_ai_spend_estimate(array $result): array { return ['estimated_cost_usd' => 0.00001]; }
    function fcc_ai_extract_openai_message_text($content): string { return (string) $content; }
    function fcc_coach_decode_reply(string $content, array $context): ?array { return json_decode($content, true); }
    function fcc_coach_reply_issues(array $reply, array $context): array { return []; }
    $source = $argv[1] ?? '/source'; $hashes = [];
    foreach(['app/helpers/fcc_ai_model_policy.php' => ['fcc_ai_workload_options', 'fcc_ai_usage_metrics', 'fcc_ai_usage_metadata', 'fcc_ai_build_chat_request', 'fcc_ai_cacheable_coach_messages', 'fcc_ai_parse_chat_response'], 'app/helpers/fcc_coach_mentor.php' => ['fcc_coach_select_model', 'fcc_coach_luna_response_format', 'fcc_coach_response_format'], 'app/helpers/fcc_ai.php' => ['fcc_ai_send_openai_chat_messages', 'fcc_ai_try_generate_internal_coach_model_reply']] as $path => $names) {
        $raw = file_get_contents($source . '/' . $path);
        foreach($names as $name) { $code = model_extract($raw, $name); $hashes[$name] = hash('sha256', $code); eval($code); }
    }
    $cases = [
        ['name' => 'beginner_luna', 'route' => 'coach_beginner', 'configured' => 'gpt-6-luna', 'expected_requested' => 'gpt-6-luna', 'expected_actual' => 'gpt-6-luna'],
        ['name' => 'vip_sol', 'route' => 'coach_vip', 'configured' => 'gpt-6-sol', 'expected_requested' => 'gpt-6-sol', 'expected_actual' => 'gpt-6-sol'],
        ['name' => 'vip_configured_luna_existing_sol_rule', 'route' => 'coach_vip', 'configured' => 'gpt-6-luna', 'expected_requested' => 'gpt-6-sol', 'expected_actual' => 'gpt-6-sol'],
        ['name' => 'vip_early_budget_downgrade', 'route' => 'coach_vip', 'configured' => 'gpt-6-sol', 'early_down' => true, 'expected_requested' => 'gpt-6-sol', 'expected_actual' => 'gpt-6-luna'],
        ['name' => 'vip_late_locked_budget_downgrade', 'route' => 'coach_vip', 'configured' => 'gpt-6-sol', 'late_down' => true, 'expected_requested' => 'gpt-6-sol', 'expected_actual' => 'gpt-6-luna'],
        ['name' => 'explicit_custom_model_preserved', 'route' => 'coach_vip', 'configured' => 'gpt-6-astra', 'expected_requested' => 'gpt-6-astra', 'expected_actual' => 'gpt-6-astra'],
        ['name' => 'missing_key_preserves_requested_decision', 'route' => 'coach_vip', 'configured' => 'gpt-6-sol', 'early_down' => true, 'missing_key' => true, 'expected_requested' => 'gpt-6-sol', 'expected_actual' => 'gpt-6-luna'],
    ];
    $checks = []; $summaries = [];
    $assert = static function(bool $pass, string $case, string $name) use (&$checks): void { $checks[] = ['case' => $case, 'name' => $name, 'pass' => $pass]; };
    foreach($cases as $case) {
        $case += ['early_down' => false, 'late_down' => false, 'missing_key' => false];
        $GLOBALS['model_case'] = $case; $GLOBALS['model_http_payloads'] = []; $GLOBALS['model_tickets'] = []; $GLOBALS['model_ledger_requested'] = []; $GLOBALS['model_early_requested'] = [];
        $conversation = (object) ['user_id' => 71, 'fcc_ai_conversation_id' => 9, 'public_id' => 'synthetic-conversation', 'language' => 'hr'];
        $result = fcc_ai_try_generate_internal_coach_model_reply($conversation, ['language' => 'hr', 'last_user_message' => 'Synthetic request.', 'ai_plan' => ['coach_summary' => ['route_key' => $case['route']]]]);
        $usage = fcc_ai_usage_metadata($result); $payload = $GLOBALS['model_http_payloads'][0] ?? [];
        $assert(($result['success'] ?? false) === !$case['missing_key'], $case['name'], 'Synthetic provider success or missing key failure is preserved.');
        $assert(count($GLOBALS['model_http_payloads']) === ($case['missing_key'] ? 0 : 1), $case['name'], 'Only expected mock transport calls occur.');
        $assert(($result['model'] ?? '') === $case['expected_actual'], $case['name'], 'Existing selected model is unchanged.');
        $assert(($result['requested_model'] ?? '') === $case['expected_requested'], $case['name'], 'Requested provenance preserves routing before budget downgrade.');
        $assert(($usage['requested_model'] ?? '') === $case['expected_requested'], $case['name'], 'Requested provenance survives persisted usage metadata allowlist.');
        if(!$case['missing_key']) {
            $assert(($payload['model'] ?? '') === $case['expected_actual'], $case['name'], 'Actual request uses the existing budget choice.');
            $assert(str_contains(json_encode($payload['messages']), 'SYNTHETIC_COMPLETE_DELIVERY_CONTRACT'), $case['name'], 'Every selected mentor model receives the delivery contract.');
            $assert(!isset($payload['requested_model']) && !isset($payload['spend_user_id']), $case['name'], 'Audit and internal spend values never leak to provider request body.');
        }
        $summaries[] = ['case' => $case['name'], 'actual_model' => $result['model'] ?? null, 'requested_model' => $result['requested_model'] ?? null, 'persisted_requested_model' => $usage['requested_model'] ?? null, 'ledger_requested_model' => $GLOBALS['model_ledger_requested'][0] ?? null, 'mock_http_calls' => count($GLOBALS['model_http_payloads'])];
    }
    $failures = count(array_filter($checks, static fn(array $check): bool => !$check['pass']));
    echo json_encode(['function_sha256' => $hashes, 'tests' => count($checks), 'failures' => $failures, 'summaries' => $summaries, 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    exit($failures === 0 ? 0 : 1);
}
