<?php
/* Custom code: FC-2026-10-07: Offline registration outbox and native push delivery checks. */

namespace Altum {
    class Plugin {
        public static bool $active = true;
        public static function is_active(string $name): bool { return self::$active; }
    }
}
namespace Altum\Helpers {
    class PushNotifications {
        public static array $calls = [];
        public static array $outcomes = [];
        public static function send(array $content, object $subscriber): bool {
            self::$calls[] = ['content' => $content, 'subscriber_id' => $subscriber->push_subscriber_id];
            $outcome = array_shift(self::$outcomes) ?? true;
            if($outcome instanceof \Throwable) throw $outcome;
            return $outcome;
        }
    }
}
namespace {
    define('ALTUMCODE', true);
    $clock = '2026-10-07 12:00:00';
    function get_date(): string { return $GLOBALS['clock']; }
    function url(string $path): string { return 'https://fcc.invalid/' . $path; }
    function settings(): object {
        return (object) ['push_notifications' => (object) ['is_enabled' => true, 'public_key' => 'fixture-public', 'private_key' => 'fixture-private']];
    }
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
            $this->filters[] = [$key, $value, $operator];
            return $this;
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
                default => 'internal_notification_id',
            };
            $row[$id_key] = count($this->tables[$table] ?? []) + 1;
            $this->tables[$table][] = $row;
            return $row[$id_key];
        }
        public function update(string $table, array $changes): bool {
            foreach($this->tables[$table] as &$row) if($this->matches($row)) $row = array_replace($row, $changes);
            $this->filters = [];
            return true;
        }
        public function startTransaction(): void { $this->snapshot = $this->tables; }
        public function commit(): void { $this->snapshot = []; }
        public function rollback(): void { $this->tables = $this->snapshot; $this->snapshot = []; }
    }
    $database = new NotificationTestDatabase();
    function db(): NotificationTestDatabase { return $GLOBALS['database']; }
    function database(): NotificationTestDatabase { return db(); }
    require dirname(__DIR__) . '/app/helpers/fcc_registration_notifications.php';
    $assert = static function(bool $condition, string $message): void {
        if(!$condition) throw new \RuntimeException($message);
    };
    $rejected_state = [];
    $rejected = fcc_registration_notification_attempt((object) ['status' => 'queued', 'attempts' => 0], [], (object) [],
        static fn(): bool => false,
        static function(array $changes) use (&$rejected_state): void { $rejected_state = $changes; });
    $assert(!$rejected && $rejected_state['status'] === 'failed' && $rejected_state['last_error'] === 'push_rejected'
        && $rejected_state['next_attempt_at'] === '2026-10-07 12:05:00', 'Explicit native transport rejection must remain queued for a bounded delayed retry.');
    $database->tables['users'] = [
        ['user_id' => 1, 'type' => 1, 'status' => 1],
        ['user_id' => 2, 'type' => 1, 'status' => 1],
        ['user_id' => 99, 'type' => 0, 'status' => 1],
    ];
    $database->tables['push_subscribers'] = [
        ['push_subscriber_id' => 10, 'user_id' => 1, 'endpoint' => 'private-owner-endpoint', 'keys' => 'private-owner-keys'],
        ['push_subscriber_id' => 11, 'user_id' => 1, 'endpoint' => 'private-owner-endpoint2', 'keys' => 'private-owner-keys2'],
        ['push_subscriber_id' => 12, 'user_id' => 2, 'endpoint' => 'private-other-admin', 'keys' => 'private-other-keys'],
        ['push_subscriber_id' => 13, 'user_id' => 99, 'endpoint' => 'private-customer', 'keys' => 'private-customer-keys'],
    ];
    $user = (object) ['user_id' => 99, 'name' => '<b>Nova osoba</b>', 'preferences' => json_encode(['meta' => ['foreverId' => '360000000099']])];
    $sponsor = ['user_id' => 88, 'fbo_id' => '360000000088', 'name' => '<script>alert(1)</script> Sponzor'];
    $queued = fcc_registration_notify_admin_approval($user, $sponsor, 101);
    $duplicate = fcc_registration_notify_admin_approval($user, $sponsor, 101);
    $assert($queued['status'] === 'queued' && $duplicate['duplicate'] === true, 'Approval event must be idempotent by stable audit ID.');
    $assert(count($database->tables['internal_notifications']) === 1 && count($database->tables['fcc_registration_admin_notifications']) === 1, 'Repeated approval must never duplicate the internal alert or outbox.');
    $assert($database->tables['internal_notifications'][0]['url'] === 'admin/user-view/99', 'Admin link must open the actual approved account.');
    $assert(!str_contains($database->tables['internal_notifications'][0]['description'], '<'), 'Subscriber names must not introduce HTML in internal notifications.');
    \Altum\Helpers\PushNotifications::$outcomes = [true, new \RuntimeException('private-owner-endpoint2 private-owner-keys2')];
    $first = fcc_registration_process_admin_notifications();
    $assert($first['delivered'] === 1 && $first['failed'] === 1, 'A successful recipient and a transport failure must be persisted separately.');
    $assert(array_column(\Altum\Helpers\PushNotifications::$calls, 'subscriber_id') === [10, 11], 'Actual native push transport must target only active root admin subscriptions.');
    $assert($database->tables['fcc_registration_admin_notifications'][0]['status'] === 'failed', 'Partly delivered events must remain retryable.');
    $assert($database->tables['fcc_registration_admin_notification_deliveries'][1]['last_error'] === 'push_transport_failed', 'Delivery failures must not persist private endpoints or credentials.');
    fcc_registration_process_admin_notifications();
    $assert(count(\Altum\Helpers\PushNotifications::$calls) === 2, 'Backoff must prevent immediate retries.');
    $clock = '2026-10-07 12:06:00';
    \Altum\Helpers\PushNotifications::$outcomes = [true];
    $retry = fcc_registration_process_admin_notifications();
    $assert($retry['delivered'] === 1 && count(\Altum\Helpers\PushNotifications::$calls) === 3, 'Only the failed recipient should be retried.');
    $assert(\Altum\Helpers\PushNotifications::$calls[2]['subscriber_id'] === 11, 'A delivered recipient must never be sent the same approval again.');
    fcc_registration_process_admin_notifications();
    $assert(count(\Altum\Helpers\PushNotifications::$calls) === 3, 'Fully delivered events must not be sent again.');
    $diagnostics = fcc_registration_admin_notification_diagnostics();
    $assert($diagnostics['active_admin_subscribers'] === 2 && $diagnostics['delivered'] === 1, 'Safe diagnostics must report owner recipients and delivered events.');
    $assert(!str_contains(json_encode($diagnostics), 'private-'), 'Diagnostics must not expose endpoint URLs or keys.');

    $database->fail_internal_insert = true;
    try {
        fcc_registration_notify_admin_approval($user, $sponsor, 102);
        throw new \RuntimeException('Expected internal alert persistence failure.');
    } catch(\RuntimeException $exception) {
        $assert($exception->getMessage() === 'FCC registration internal notification could not be persisted.', 'Persistence error must reach the retry caller.');
    }
    $assert(count($database->tables['fcc_registration_admin_notifications']) === 1, 'An internal notification failure must roll back the event marker so retry remains possible.');
    $database->fail_internal_insert = false;
    \Altum\Plugin::$active = false;
    $unavailable = fcc_registration_notify_admin_approval($user, $sponsor, 103);
    $assert($unavailable['status'] === 'push_unavailable', 'Disabled push infrastructure must never be reported as delivered.');
    fcc_registration_process_admin_notifications();
    $assert(count(\Altum\Helpers\PushNotifications::$calls) === 3, 'Disabled push infrastructure must never invoke the native transport.');
    \Altum\Plugin::$active = true;
    $database->tables['push_subscribers'] = [];
    $without_subscription = fcc_registration_notify_admin_approval($user, $sponsor, 104);
    $assert($without_subscription['status'] === 'no_subscribers', 'Missing owner subscription must remain durably queued with a truthful diagnostic status.');
    $test = fcc_registration_notify_admin_test('fixture_test_20261007');
    $assert($test['status'] === 'no_subscribers', 'Explicit owner test must use the same durable outbox.');
    $test_copy = end($database->tables['fcc_registration_admin_notifications']);
    $assert($test_copy['title'] === 'Testna FCC obavijest' && !str_contains($test_copy['title'], 'odobren'), 'A push test must not pretend that a real registration was approved.');

    $database->tables['push_subscribers'] = [['push_subscriber_id' => 20, 'user_id' => 1]];
    $later = fcc_registration_process_admin_notifications();
    $assert($later['delivered'] === 3, 'Events queued without push or subscriptions must deliver after the owner enables push.');
    $database->tables['users'][0]['status'] = 0;
    $assert(fcc_registration_admin_push_subscribers() === [], 'Inactive root admins must not receive a push.');
    foreach(['Hrvatski#hr.php', 'english#en.php', 'cache/Hrvatski#hr.php', 'cache/english#en.php'] as $file) {
        $language = require dirname(__DIR__) . '/app/languages/' . $file;
        foreach(['global.emails.admin.fcc_access_rejected.body', 'global.emails.admin.fcc_access_rejected_not_team.body'] as $key) {
            $assert(!preg_match('/manual review|ručne provjere|^\s*-/mi', $language[$key]), 'Registration rejection mail must describe verification accurately without dash bullets.');
        }
    }
    echo "FCC registration notification checks passed.\n";
}

/* /Custom code: FC-2026-10-07 */
