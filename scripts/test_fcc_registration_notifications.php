<?php
/* Custom code: FC-2026-10-07: Offline FCC owner push, durable outbox and privacy checks. */
namespace Altum {
    class Plugin { public static function is_active(string $name): bool { return false; } }
}
namespace Minishlink\WebPush {
    class Subscription {
        public static function create(array $values): object { return (object) $values; }
    }
    class VAPID {
        public static int $generated = 0;
        public static function createVapidKeys(): array {
            self::$generated++;
            return ['publicKey' => 'fixture_public', 'privateKey' => 'private_fixture_key'];
        }
    }
    class TestReport {
        public function __construct(public bool $success, public bool $expired = false) {}
        public function isSuccess(): bool { return $this->success; }
        public function isSubscriptionExpired(): bool { return $this->expired; }
    }
    class WebPush {
        public static array $calls = [];
        public static array $outcomes = [];
        public function __construct(array $auth, array $options, int $timeout, array $http) {
            if($timeout !== 20 || $http['connect_timeout'] !== 5 || $http['timeout'] !== 8) throw new \RuntimeException('Unbounded push timeout.');
        }
        public function setAutomaticPadding(int $value): void {}
        public function sendOneNotification(object $subscriber, string $content, array $options): TestReport {
            self::$calls[] = ['content' => json_decode($content, true), 'endpoint' => $subscriber->endpoint];
            $outcome = array_shift(self::$outcomes) ?? true;
            if($outcome instanceof \Throwable) throw $outcome;
            return $outcome instanceof TestReport ? $outcome : new TestReport($outcome);
        }
    }
}
namespace {
    define('ALTUMCODE', true);
    $clock = '2026-10-07 12:00:00';
    $impersonating = false;
    function get_date(): string { return $GLOBALS['clock']; }
    function settings(): object { return (object) ['internal_notifications' => (object) ['admins_is_enabled' => 1]]; }
    function url(string $path): string { return 'https://fcc.invalid/' . $path; }
    function session_has(string $name): bool { return $name === 'admin_user_id' && $GLOBALS['impersonating']; }
    function session_get(string $name) { return $GLOBALS['owner_session'][$name] ?? null; }
    if(!function_exists('mb_substr')) {
        function mb_substr(string $value, int $start, ?int $length = null): string { return substr($value, $start, $length); }
    }
    class NotificationTestResult {
        public function fetch_assoc(): array { return ['acquired' => 1]; }
    }
    class NotificationTestDatabase {
        public array $tables = [];
        public array $filters = [];
        public array $snapshot = [];
        public bool $fail_internal_insert = false;
        public function query(string $sql): NotificationTestResult|bool { return new NotificationTestResult(); }
        public function where(string $key, mixed $value, string $operator = '='): self {
            $this->filters[] = [$key, $value, $operator]; return $this;
        }
        public function orderBy(string $key, string $direction): self { return $this; }
        private function matches(array $row): bool {
            foreach($this->filters as [$key, $value, $operator]) {
                if($operator === 'IN' ? !in_array($row[$key] ?? null, $value, true) : ($row[$key] ?? null) != $value) return false;
            }
            return true;
        }
        public function get(string $table, ?int $limit = null, mixed $columns = null): array {
            $rows = array_values(array_filter($this->tables[$table] ?? [], fn(array $row): bool => $this->matches($row)));
            $this->filters = [];
            if($limit !== null) $rows = array_slice($rows, 0, $limit);
            return array_map(static fn(array $row): object => (object) $row, $rows);
        }
        public function getOne(string $table, mixed $columns = null): ?object { return $this->get($table, 1)[0] ?? null; }
        public function getValue(string $table, string $column): int { return count($this->get($table)); }
        public function insert(string $table, array $row): int|false {
            $this->filters = [];
            if($table === 'internal_notifications' && $this->fail_internal_insert) return false;
            $id_key = match($table) {
                'fcc_registration_admin_notifications' => 'notification_id',
                'fcc_registration_admin_notification_deliveries' => 'delivery_id',
                'fcc_registration_admin_push_subscriptions' => 'push_subscriber_id',
                'fcc_registration_admin_push_configuration' => 'user_id',
                default => 'internal_notification_id',
            };
            $row[$id_key] ??= max([0, ...array_column($this->tables[$table] ?? [], $id_key)]) + 1;
            $this->tables[$table][] = $row;
            return $row[$id_key];
        }
        public function update(string $table, array $changes): bool {
            foreach($this->tables[$table] as &$row) if($this->matches($row)) $row = array_replace($row, $changes);
            $this->filters = []; return true;
        }
        public function startTransaction(): void { $this->snapshot = $this->tables; }
        public function commit(): void { $this->snapshot = []; }
        public function rollback(): void { $this->tables = $this->snapshot; $this->snapshot = []; }
    }
    $database = new NotificationTestDatabase();
    function db(): NotificationTestDatabase { return $GLOBALS['database']; }
    function database(): NotificationTestDatabase { return db(); }
    require dirname(__DIR__) . '/app/helpers/fcc_registration_notifications.php';
    $assert = static function(bool $condition, string $message): void { if(!$condition) throw new \RuntimeException($message); };
    $invalid = static function(callable $callback) use ($assert): void {
        try { $callback(); } catch(\InvalidArgumentException $exception) { return; }
        $assert(false, 'Expected invalid owner subscription to be rejected.');
    };
    $base64url = static fn(string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    $keys = ['p256dh' => $base64url("\x04" . str_repeat("\x01", 64)), 'auth' => $base64url(str_repeat("\x02", 16))];
    $owner = (object) ['user_id' => 1, 'type' => 1, 'status' => 1];
    $assert(fcc_registration_admin_push_owner($owner), 'The active root administrator must be eligible.');
    $admin2 = (object) ['user_id' => 2, 'type' => 1, 'status' => 1];
    $assert(fcc_registration_admin_push_owner($admin2), 'An active administrator with a different account ID must be eligible.');
    foreach([(object) ['user_id' => 0, 'type' => 1, 'status' => 1], (object) ['user_id' => 1, 'type' => 0, 'status' => 1], (object) ['user_id' => 2, 'type' => 1, 'status' => 0]] as $denied) {
        $invalid(fn() => fcc_registration_admin_push_subscribe($denied, []));
    }
    $impersonating = true;
    $assert(!fcc_registration_admin_push_owner($owner), 'An impersonated owner session must not be eligible.');
    $owner_session = ['admin_user_id' => 1, 'user_id' => 1];
    $assert(fcc_registration_admin_push_owner($owner), 'A returned authenticated owner with its own retained session marker must remain eligible.');
    foreach([['admin_user_id' => 2, 'user_id' => 1], ['admin_user_id' => 1, 'user_id' => 99], ['admin_user_id' => 1]] as $owner_session) {
        $assert(!fcc_registration_admin_push_owner($owner), 'Other identities and missing session identity must remain blocked.');
    }
    $owner_session = ['admin_user_id' => 1, 'user_id' => 1];
    $assert(!fcc_registration_admin_push_owner($admin2), 'A session marker from another administrator must never authorize the current account.');
    $owner_session = ['admin_user_id' => '2', 'user_id' => '2'];
    $assert(fcc_registration_admin_push_owner($admin2), 'An authenticated administrator may retain its own session marker, including numeric string IDs.');
    foreach([['admin_user_id' => 1, 'user_id' => 2], ['admin_user_id' => 2, 'user_id' => 1], ['admin_user_id' => 2]] as $owner_session) {
        $assert(!fcc_registration_admin_push_owner($admin2), 'Delegated and incomplete admin sessions must remain blocked.');
        $invalid(fn() => fcc_registration_admin_push_subscribe($admin2, []));
    }
    $owner_session = [];
    $impersonating = false;
    foreach(['http://fcm.googleapis.com/private', 'https://127.0.0.1/private', 'https://fcm.googleapis.com.attacker.invalid/private', 'https://name@fcm.googleapis.com/private', 'https://fcm.googleapis.com:444/private', 'https://fcm.googleapis.com/private#fragment'] as $endpoint) {
        $invalid(fn() => fcc_registration_admin_push_endpoint($endpoint));
    }
    $database->tables['users'] = [(array) $owner, (array) $admin2, ['user_id' => 99, 'type' => 0, 'status' => 1],
        ['user_id' => 3, 'type' => 1, 'status' => 0]];
    $subscription = ['endpoint' => 'https://fcm.googleapis.com/private/10', 'keys' => $keys, 'user_id' => 99];
    $invalid(fn() => fcc_registration_admin_push_subscribe($owner, ['endpoint' => $subscription['endpoint'], 'keys' => ['p256dh' => 'bad', 'auth' => 'bad']]));
    fcc_registration_admin_push_subscribe($owner, $subscription);
    fcc_registration_admin_push_subscribe($owner, $subscription);
    $assert(count($database->tables['fcc_registration_admin_push_subscriptions']) === 1 && $database->tables['fcc_registration_admin_push_subscriptions'][0]['user_id'] === 1, 'Repeated subscription must update the owner record and ignore a supplied recipient ID.');
    $assert(\Minishlink\WebPush\VAPID::$generated === 1, 'VAPID keys must remain stable after being persisted.');
    $assert(fcc_registration_admin_push_subscription_status($owner, $subscription['endpoint'])['status'] === 'subscribed', 'Server subscription status must reflect durable consent.');
    fcc_registration_admin_push_unsubscribe($owner, $subscription['endpoint']);
    $assert(fcc_registration_admin_push_subscription_status($owner, $subscription['endpoint'])['status'] === 'unsubscribed', 'Revoked consent must be reflected accurately.');
    $admin2_subscription = ['endpoint' => 'https://fcm.googleapis.com/private/22', 'keys' => $keys, 'user_id' => 1];
    $admin2_saved = fcc_registration_admin_push_subscribe($admin2, $admin2_subscription);
    $assert($admin2_saved['active_admin_subscribers'] === 1 && $database->tables['fcc_registration_admin_push_subscriptions'][1]['user_id'] === 2,
        'Explicit subscription must belong to the actual authenticated administrator and ignore another submitted recipient ID.');
    $owner_saved = fcc_registration_admin_push_subscribe($owner, $subscription);
    $assert($owner_saved['active_admin_subscribers'] === 1 && fcc_registration_admin_push_subscription_count($admin2) === 1
        && count(fcc_registration_admin_push_subscribers()) === 2, 'Browser device counts must be private to the signed in administrator.');
    $assert(fcc_registration_admin_push_subscription_status($admin2, $subscription['endpoint'])['status'] === 'unsubscribed',
        'An administrator must not read another account subscription status as its own.');
    fcc_registration_admin_push_unsubscribe($admin2, $subscription['endpoint']);
    $assert(fcc_registration_admin_push_subscription_status($owner, $subscription['endpoint'])['status'] === 'subscribed',
        'An administrator must not disable another account subscription.');
    $invalid(fn() => fcc_registration_admin_push_subscribe($admin2, $subscription));
    $assert($database->tables['fcc_registration_admin_push_subscriptions'][0]['user_id'] === 1,
        'Cross account endpoint submission must not transfer subscription ownership.');
    fcc_registration_admin_push_unsubscribe($owner, $admin2_subscription['endpoint']);
    $assert(fcc_registration_admin_push_subscription_status($admin2, $admin2_subscription['endpoint'])['status'] === 'subscribed',
        'Subscription isolation must apply in both directions.');
    $assert(\Minishlink\WebPush\VAPID::$generated === 1 && $database->tables['fcc_registration_admin_push_configuration'][0]['user_id'] === 1,
        'All administrators share the stable application signing key slot, without using it as a recipient ID.');
    $row = static fn(int $id, int $user_id): array => ['push_subscriber_id' => $id, 'user_id' => $user_id, 'is_enabled' => 1,
        'endpoint' => 'https://fcm.googleapis.com/private/' . $id, 'endpoint_sha256' => hash('sha256', 'https://fcm.googleapis.com/private/' . $id), 'keys' => json_encode($keys)];
    $database->tables['fcc_registration_admin_push_subscriptions'] = [$row(10, 1), $row(11, 1),
        array_replace($row(12, 2), ['is_enabled' => 0]), $row(13, 99), $row(14, 3)];
    $database->tables['push_subscribers'] = [$row(30, 1)];
    $database->tables['subscribers'] = [$row(40, 1)];
    $rejected_state = [];
    $rejected = fcc_registration_notification_attempt((object) ['status' => 'queued', 'attempts' => 0], [], (object) [], static fn(): bool => false,
        static function(array $changes) use (&$rejected_state): void { $rejected_state = $changes; });
    $assert(!$rejected && $rejected_state['last_error'] === 'push_rejected' && $rejected_state['next_attempt_at'] === '2026-10-07 12:05:00', 'Transport rejection must remain queued with bounded retry delay.');
    $user = (object) ['user_id' => 99, 'name' => '<b>Nova osoba</b>', 'preferences' => json_encode(['meta' => ['foreverId' => '360000000099']])];
    $sponsor = ['user_id' => 88, 'fbo_id' => '360000000088', 'name' => '<script>alert(1)</script> Sponzor'];
    $queued = fcc_registration_notify_admin_approval($user, $sponsor, 101);
    $duplicate = fcc_registration_automation_notify_approval($user, ['sponsor' => $sponsor, 'audit_id' => 101]);
    $assert($queued['status'] === 'queued' && $duplicate['duplicate'] === true, 'Approval event must be idempotent by audit ID despite an inactive native plugin.');
    $assert(count($database->tables['internal_notifications']) === 1 && count($database->tables['fcc_registration_admin_notifications']) === 1, 'Repeated approval must never duplicate the internal alert or outbox.');
    $assert($database->tables['internal_notifications'][0]['url'] === 'admin/user-view/99' && !str_contains($database->tables['internal_notifications'][0]['description'], '<'), 'Approved account link and user names must be safe.');
    \Minishlink\WebPush\WebPush::$outcomes = [true, new \RuntimeException('private endpoint and private key')];
    $first = fcc_registration_process_admin_notifications();
    $assert($first['delivered'] === 1 && $first['failed'] === 1, 'Separate recipient outcomes must be persisted.');
    $assert(array_column(\Minishlink\WebPush\WebPush::$calls, 'endpoint') === ['https://fcm.googleapis.com/private/10', 'https://fcm.googleapis.com/private/11'], 'Only enabled FCC subscriptions of active administrators may receive push, never disabled, inactive, customer, native or website visitor records.');
    $assert($database->tables['fcc_registration_admin_notifications'][0]['status'] === 'failed', 'Partial delivery must remain retryable.');
    $assert($database->tables['fcc_registration_admin_notification_deliveries'][1]['last_error'] === 'push_transport_failed', 'Failure diagnostics must not persist private subscription details.');
    fcc_registration_process_admin_notifications();
    $assert(count(\Minishlink\WebPush\WebPush::$calls) === 2, 'Backoff must prevent immediate retries.');
    $clock = '2026-10-07 12:06:00';
    $retry = fcc_registration_process_admin_notifications();
    $assert($retry['delivered'] === 1 && count(\Minishlink\WebPush\WebPush::$calls) === 3, 'Only the failed owner recipient should be retried.');
    $assert(\Minishlink\WebPush\WebPush::$calls[2]['endpoint'] === 'https://fcm.googleapis.com/private/11', 'A delivered recipient must never receive the same approval again.');
    fcc_registration_process_admin_notifications();
    $assert(count(\Minishlink\WebPush\WebPush::$calls) === 3, 'A completed event must not be sent again.');
    $diagnostics = fcc_registration_admin_notification_diagnostics();
    $assert($diagnostics['recipient_role'] === 'active_admin' && !isset($diagnostics['recipient_admin_user_id']) && $diagnostics['provider'] === 'fcc'
        && $diagnostics['runtime_ready'] && $diagnostics['active_admin_subscribers'] === 2 && $diagnostics['delivered'] === 1,
        'Safe diagnostics must report the active administrator role and actual subscriptions instead of a hardcoded recipient ID.');
    $assert(!str_contains(json_encode($diagnostics), 'private') && !str_contains(json_encode($diagnostics), 'fcm.googleapis.com'), 'Diagnostics must never expose endpoint URLs or private VAPID keys.');
    $database->fail_internal_insert = true;
    try { fcc_registration_notify_admin_approval($user, $sponsor, 102); throw new \RuntimeException('Expected internal persistence failure.'); }
    catch(\RuntimeException $exception) { $assert($exception->getMessage() === 'FCC registration internal notification could not be persisted.', 'Internal persistence errors must reach the retry caller.'); }
    $assert(count($database->tables['fcc_registration_admin_notifications']) === 1, 'Internal alert failure must roll back the event marker.');
    $database->fail_internal_insert = false;
    $database->tables['fcc_registration_admin_push_configuration'] = [];
    $unavailable = fcc_registration_notify_admin_approval($user, $sponsor, 103);
    $assert($unavailable['status'] === 'push_unavailable', 'Missing independent push keys must never be reported as delivered.');
    fcc_registration_process_admin_notifications();
    $assert(count(\Minishlink\WebPush\WebPush::$calls) === 3, 'Missing independent configuration must not invoke transport.');
    fcc_registration_admin_push_configuration(true);
    $database->tables['fcc_registration_admin_push_subscriptions'] = [];
    $without_subscription = fcc_registration_notify_admin_approval($user, $sponsor, 104);
    $assert($without_subscription['status'] === 'no_subscribers', 'Missing owner consent must remain durably queued with accurate status.');
    $test = fcc_registration_notify_admin_test('fixture_test_20261007');
    $assert($test['status'] === 'no_subscribers', 'Explicit test must use the durable outbox.');
    $test_copy = end($database->tables['fcc_registration_admin_notifications']);
    $assert($test_copy['title'] === 'Testna FCC obavijest' && !str_contains($test_copy['title'], 'odobren'), 'A test must not pretend a customer was approved.');
    $database->tables['fcc_registration_admin_push_subscriptions'] = [$row(20, 2)];
    $later = fcc_registration_process_admin_notifications();
    $assert($later['delivered'] === 3 && fcc_registration_admin_notification_status($test['notification_id'])['status'] === 'delivered', 'Events queued before consent must deliver later and report exact test status.');
    $assert(array_column(array_slice(\Minishlink\WebPush\WebPush::$calls, -3), 'endpoint') === array_fill(0, 3, 'https://fcm.googleapis.com/private/20'),
        'The actual active administrator 2 must receive its explicitly subscribed notifications.');
    \Minishlink\WebPush\WebPush::$outcomes = [new \Minishlink\WebPush\TestReport(false, true)];
    $assert(!fcc_registration_admin_web_push_send([], (object) $row(20, 2)), 'Expired subscription must not be reported successful.');
    $assert(fcc_registration_admin_push_subscribers() === [], 'Expired subscription must be disabled to prevent repeated transport calls.');
    $calls_before_revocation = count(\Minishlink\WebPush\WebPush::$calls);
    $assert(!fcc_registration_admin_web_push_send([], (object) $row(20, 2)) && count(\Minishlink\WebPush\WebPush::$calls) === $calls_before_revocation,
        'A stale subscriber object must not override durable revoked consent.');
    $database->tables['fcc_registration_admin_push_subscriptions'][0]['is_enabled'] = 1;
    $database->tables['users'][1]['type'] = 0;
    $assert(fcc_registration_admin_push_subscribers() === [] && !fcc_registration_admin_web_push_send([], (object) $row(20, 2))
        && count(\Minishlink\WebPush\WebPush::$calls) === $calls_before_revocation, 'Revoking admin role must stop delivery before transport even if its subscription remains enabled.');
    $database->tables['users'][1]['type'] = 1;
    $database->tables['users'][1]['status'] = 0;
    $assert(fcc_registration_admin_push_subscribers() === [] && !fcc_registration_admin_web_push_send([], (object) $row(20, 2))
        && count(\Minishlink\WebPush\WebPush::$calls) === $calls_before_revocation, 'Inactive administrators must not receive push.');
    $database->tables['users'][1]['status'] = 1;
    foreach(['Hrvatski#hr.php', 'english#en.php', 'cache/Hrvatski#hr.php', 'cache/english#en.php'] as $file) {
        $language = require dirname(__DIR__) . '/app/languages/' . $file;
        foreach(['global.emails.admin.fcc_access_rejected.body', 'global.emails.admin.fcc_access_rejected_not_team.body'] as $key) {
            $assert(!preg_match('/manual review|ručne provjere|^\s*-/mi', $language[$key]), 'Rejection mail must describe verification accurately without dash bullets.');
        }
    }
    echo "FCC registration notification checks passed.\n";
}
/* /Custom code: FC-2026-10-07 */
