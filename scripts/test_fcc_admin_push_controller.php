<?php
/* Custom code: FC-2026-10-07: Offline owner access, CSRF, malformed input and browser template checks. */
namespace Altum {
    class Csrf {
        public static function check(): bool { return ($_POST['token'] ?? '') === 'fixture_csrf'; }
        public static function get(): string { return 'fixture_csrf'; }
    }
    class Response {
        public static function json($message, string $status = 'success', array $details = []): void {
            echo json_encode(['status' => $status, 'details' => $details, 'http' => http_response_code() ?: 200]); exit;
        }
    }
    class Title { public static function set(string $title): void {} }
    class Event {
        public static string $javascript = '';
        public static function add_content(string $content, string $type): void { self::$javascript .= $content; }
    }
    class View {
        public function __construct(string $name, array $context) {}
        public function run(array $data): string {
            $data = (object) $data;
            ob_start(); include dirname(__DIR__) . '/themes/altum/views/admin/fcc-push/index.php';
            $html = ob_get_clean();
            return json_encode(['html' => $html, 'javascript' => Event::$javascript]);
        }
    }
}
namespace Altum\Controllers {
    class Controller {
        public object $user;
        public function add_view_content(string $name, string $content): void { echo $content; }
    }
}
namespace {
    function throw_404(): void { echo json_encode(['http' => 404]); exit; }
    if(($argv[1] ?? '') === 'case') {
        ob_start(); require __DIR__ . '/test_fcc_registration_notifications.php'; ob_end_clean();
        define('APP_PATH', dirname(__DIR__) . '/app/');
        require APP_PATH . 'controllers/admin/AdminFccPush.php';
        $database->tables['users'][0]['status'] = 1;
        $controller = new \Altum\Controllers\AdminFccPush();
        $controller->user = (object) ['user_id' => 1, 'type' => 1, 'status' => 1];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['token' => 'fixture_csrf', 'action' => 'test', 'request_id' => 'controller_fixture_20261007'];
        switch($argv[2]) {
            case 'owner_get': $_SERVER['REQUEST_METHOD'] = 'GET'; break;
            case 'other_admin': $controller->user->user_id = 2; break;
            case 'customer': $controller->user->user_id = 99; $controller->user->type = 0; break;
            case 'inactive_owner': $controller->user->status = 0; break;
            case 'impersonated_owner': $impersonating = true; break;
            case 'missing_csrf': unset($_POST['token']); break;
            case 'malformed_subscription': $_POST['action'] = 'subscribe'; $_POST['subscription'] = '{invalid'; break;
            case 'forged_endpoint': $_POST['action'] = 'subscribe'; $_POST['subscription'] = json_encode(['endpoint' => 'https://127.0.0.1/private', 'keys' => $keys]); break;
            case 'subscribe': $_POST['action'] = 'subscribe'; $_POST['subscription'] = json_encode(['endpoint' => 'https://fcm.googleapis.com/private/99', 'keys' => $keys]); break;
            case 'test': break;
            default: throw new \RuntimeException('Unknown fixture case.');
        }
        $controller->index(); exit;
    }
    $assert = static function(bool $condition, string $message): void { if(!$condition) throw new \RuntimeException($message); };
    $run = static function(string $case): array {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, 'case', $case], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        if(proc_close($process) !== 0) throw new \RuntimeException('Controller fixture failed: ' . $errors);
        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    };
    foreach(['other_admin', 'customer', 'inactive_owner', 'impersonated_owner'] as $case) $assert($run($case)['http'] === 404, 'Only the real active root owner may use push preferences.');
    $assert($run('missing_csrf')['http'] === 403, 'Push writes require CSRF even for the owner.');
    foreach(['malformed_subscription', 'forged_endpoint'] as $case) $assert($run($case)['http'] === 422, 'Invalid subscriptions must be rejected before delivery.');
    $assert($run('subscribe')['details']['status'] === 'subscribed', 'Valid explicit owner subscription must be saved.');
    $assert($run('test')['details']['test']['status'] === 'delivered', 'Test response must use the actual delivered event after draining the outbox.');
    $get = $run('owner_get');
    $assert(str_contains($get['html'], 'Uključi na ovom uređaju') && str_contains($get['javascript'], 'Notification.requestPermission()'), 'Owner template must expose a consent button and browser permission flow.');
    $assert(!str_contains(json_encode($get), 'private_fixture_key'), 'Owner page must never expose the private VAPID key.');
    if(($argv[1] ?? '') === 'render-javascript') {
        preg_match('#<script>(.*?)</script>#s', $get['javascript'], $matches);
        echo $matches[1]; exit;
    }
    echo "FCC admin push controller checks passed.\n";
}
/* /Custom code: FC-2026-10-07 */
