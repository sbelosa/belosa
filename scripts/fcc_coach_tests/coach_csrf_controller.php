<?php
/* Offline boundary fixture: actual FccAiHub methods and actual Csrf, synthetic owner only. */
namespace Altum {
    class Authentication {
        public static function guard(): void {
            if(empty($GLOBALS['fixture_authenticated'])) {
                http_response_code(401);
                Response::json('Authentication required.', 'error');
            }
        }
    }
    class Response {
        public static function json($message, $status = 'success', $details = []): void {
            echo json_encode(['status' => $status, 'http' => http_response_code() ?: 200,
                'effects' => $GLOBALS['fixture_effects'], 'auth' => $GLOBALS['fixture_authenticated']], JSON_THROW_ON_ERROR);
            exit;
        }
    }
}
namespace Altum\Controllers { class Controller { public $user; } }
namespace {
    const FIXTURE_CSRF = 'fixture-session-token-20261013';
    function session_get($name, $default = null) { return $GLOBALS['fixture_session'][$name] ?? $default; }
    function session_set($name, $value): void { $GLOBALS['fixture_session'][$name] = $value; }
    function l($key): string { return (string) $key; }
    function fcc_t($key): string { return (string) $key; }
    function input_clean($value, $length) { return substr((string) $value, 0, $length); }
    function fcc_ai_user_has_coach_access($user): bool { return $GLOBALS['fixture_access']; }
    function fixture_owner_guard($user, array $payload): void {
        if((int) ($user->user_id ?? 0) !== 71 || $payload['conversation_public_id'] === 'synthetic-foreign-conversation') {
            throw new \RuntimeException('Owner mismatch.');
        }
    }
    function fcc_ai_create_or_resume_internal_coach_conversation($user, array $payload): array {
        ++$GLOBALS['fixture_effects']['conversation_helper_calls'];
        fixture_owner_guard($user, $payload);
        ++$GLOBALS['fixture_effects']['conversation_writes'];
        return [];
    }
    function fcc_ai_handle_internal_coach_message($user, array $payload): array {
        ++$GLOBALS['fixture_effects']['message_helper_calls'];
        fixture_owner_guard($user, $payload);
        ++$GLOBALS['fixture_effects']['message_writes'];
        ++$GLOBALS['fixture_effects']['provider_boundary_calls'];
        return [];
    }
    function proposedSource(string $source): string {
        $helper = <<<'PHP'
    private function ensure_coach_post_request(): void {
        if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            Response::json('Invalid request method.', 'error');
        }
        if(!isset($_POST['token']) || !is_string($_POST['token']) || !hash_equals(\Altum\Csrf::get(), $_POST['token'])) {
            http_response_code(403);
            Response::json('Sesija je istekla. Osvježi stranicu i pokušaj ponovno.', 'error');
        }
    }

PHP;
        $anchor = "    public function coach_conversation() {";
        if(substr_count($source, $anchor) !== 1) throw new \RuntimeException('Missing unique conversation anchor.');
        $source = str_replace($anchor, $helper . $anchor, $source);
        foreach(['coach_conversation', 'coach_message'] as $name) {
            $needle = "    public function {$name}() {\n        \\Altum\\Authentication::guard();\n        \$this->ensure_coach_json_access();";
            if(substr_count($source, $needle) !== 1) throw new \RuntimeException('Missing unique guard anchor: ' . $name);
            $source = str_replace($needle, $needle . "\n        \$this->ensure_coach_post_request();", $source);
        }
        return $source;
    }
    $sourcePath = $argv[1] ?? '/source';
    $mode = $argv[2] ?? 'actual';
    if(!in_array($mode, ['actual', 'proposed'], true)) throw new \RuntimeException('Invalid fixture mode.');
    if(($argv[3] ?? '') === 'case') {
        define('ALTUMCODE', true);
        $endpoint = $argv[4]; $case = $argv[5];
        $GLOBALS['fixture_authenticated'] = true;
        $GLOBALS['fixture_access'] = true;
        $GLOBALS['fixture_session'] = ['token' => FIXTURE_CSRF];
        $GLOBALS['fixture_effects'] = array_fill_keys(['conversation_helper_calls', 'message_helper_calls', 'conversation_writes', 'message_writes', 'provider_boundary_calls'], 0);
        $_SERVER['REQUEST_METHOD'] = 'POST'; $_GET = [];
        $_POST = ['token' => FIXTURE_CSRF, 'conversation_public_id' => 'synthetic-owner-conversation', 'message' => 'Synthetic offline message.', 'language' => 'hr'];
        switch($case) {
            case 'valid_owner': break;
            case 'missing_token': unset($_POST['token']); break;
            case 'empty_token': $_POST['token'] = ''; break;
            case 'wrong_token': $_POST['token'] = 'wrong-fixture-token'; break;
            case 'array_token': $_POST['token'] = ['malformed']; break;
            case 'stale_session_token': $GLOBALS['fixture_session']['token'] = 'new-fixture-session-token'; break;
            case 'refresh_new_session_token': $GLOBALS['fixture_session']['token'] = $_POST['token'] = 'new-fixture-session-token'; break;
            case 'query_only_token': $_GET['token'] = FIXTURE_CSRF; unset($_POST['token']); break;
            case 'correct_query_wrong_post': $_GET['token'] = FIXTURE_CSRF; $_POST['token'] = 'wrong-fixture-token'; break;
            case 'missing_token_source_context': unset($_POST['token']); $_POST['source_context'] = 'Synthetic changed source context'; break;
            case 'plan_denied': $GLOBALS['fixture_access'] = false; break;
            case 'unauthenticated': $GLOBALS['fixture_authenticated'] = false; break;
            case 'empty_post': $_POST = []; break;
            default:
                if(str_starts_with($case, 'method_')) $_SERVER['REQUEST_METHOD'] = substr($case, 7);
                else throw new \RuntimeException('Invalid fixture case.');
        }
        require $sourcePath . '/app/core/Csrf.php';
        $controllerSource = file_get_contents($sourcePath . '/app/controllers/FccAiHub.php');
        if($mode === 'proposed') $controllerSource = proposedSource($controllerSource);
        eval('?>' . $controllerSource);
        $controller = new \Altum\Controllers\FccAiHub();
        $controller->user = (object) ['user_id' => 71, 'status' => 1];
        $controller->$endpoint();
        echo json_encode(['status' => 'no_response', 'http' => http_response_code() ?: 200, 'effects' => $GLOBALS['fixture_effects']], JSON_THROW_ON_ERROR);
        exit;
    }
    $cases = ['valid_owner', 'missing_token', 'empty_token', 'wrong_token', 'array_token', 'stale_session_token', 'refresh_new_session_token', 'query_only_token', 'correct_query_wrong_post', 'missing_token_source_context', 'plan_denied', 'unauthenticated', 'empty_post', 'method_GET', 'method_HEAD', 'method_PUT', 'method_DELETE', 'method_OPTIONS', 'method_PATCH'];
    $results = []; $failed = 0;
    foreach(['coach_conversation', 'coach_message'] as $endpoint) foreach($cases as $case) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, $sourcePath, $mode, 'case', $endpoint, $case], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($process);
        if($exit !== 0 || $stderr !== '') throw new \RuntimeException('Offline process failed: ' . $endpoint . '/' . $case . ': ' . $stderr);
        // Current empty POST path intentionally dies without JSON. It is a contract failure but has no effects.
        $result = $output === '' ? ['status' => 'empty_response', 'http' => 200, 'effects' => array_fill_keys(['conversation_helper_calls', 'message_helper_calls', 'conversation_writes', 'message_writes', 'provider_boundary_calls'], 0)] : json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        $accepted = in_array($case, ['valid_owner', 'refresh_new_session_token'], true);
        $expectedHttp = str_starts_with($case, 'method_') ? 405 : ($case === 'plan_denied' ? 200 : ($case === 'unauthenticated' ? 401 : ($accepted ? 200 : 403)));
        $effects = $result['effects'];
        $noWrites = $effects['conversation_writes'] === 0 && $effects['message_writes'] === 0 && $effects['provider_boundary_calls'] === 0;
        $noHelper = $effects['conversation_helper_calls'] === 0 && $effects['message_helper_calls'] === 0;
        $pass = $accepted ? ($result['status'] === 'success' && !$noWrites) : ($result['status'] === 'error' && $noWrites && $noHelper);
        $pass = $pass && $result['http'] === $expectedHttp;
        if(!$pass) ++$failed;
        $results[] = ['endpoint' => $endpoint, 'case' => $case, 'expected_http' => $expectedHttp, 'actual' => $result, 'pass' => $pass];
    }
    echo json_encode(['mode' => $mode, 'source_controller_sha256' => hash_file('sha256', $sourcePath . '/app/controllers/FccAiHub.php'), 'tests' => count($results), 'failures' => $failed, 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    exit($failed === 0 ? 0 : 1);
}
