<?php
/* Custom code: FC-2026-09-11: regression checks for the authentication Stripe restore guard */

namespace {
    if(!defined('ALTUMCODE')) {
        define('ALTUMCODE', true);
    }

    final class AuthenticationStripeRestoreGuardTestConfig {
        public static object $settings;
    }

    final class AuthenticationStripeRestoreGuardTestCacheItem {
        public string $key;
        public $value;

        public function __construct(string $key) {
            $this->key = $key;
            $this->value = AuthenticationStripeRestoreGuardTestCache::$values[$key] ?? null;
        }

        public function get() {
            return $this->value;
        }

        public function set($value): self {
            $this->value = $value;
            return $this;
        }

        public function expiresAfter(int $seconds): self {
            AuthenticationStripeRestoreGuardTestCache::$expirations[$this->key] = $seconds;
            return $this;
        }
    }

    final class AuthenticationStripeRestoreGuardTestCache {
        public static array $values = [];
        public static array $expirations = [];
        public static array $session_statuses = [];

        public function getItem(string $key): AuthenticationStripeRestoreGuardTestCacheItem {
            self::$session_statuses[] = session_status();
            return new AuthenticationStripeRestoreGuardTestCacheItem($key);
        }

        public function save(AuthenticationStripeRestoreGuardTestCacheItem $item): bool {
            self::$values[$item->key] = $item->value;
            return true;
        }
    }

    function cache(): AuthenticationStripeRestoreGuardTestCache {
        static $cache;
        return $cache ??= new AuthenticationStripeRestoreGuardTestCache();
    }

    function settings(): object {
        return AuthenticationStripeRestoreGuardTestConfig::$settings;
    }
}

namespace Stripe {
    final class StripeRestoreGuardTestState {
        public static int $request_count = 0;
        public static array $session_statuses = [];

        public static function reset(): void {
            self::$request_count = 0;
            self::$session_statuses = [];
        }

        public static function record_request(): void {
            self::$request_count++;
            self::$session_statuses[] = session_status();
        }
    }

    final class Stripe {
        public static function setApiKey($api_key): void {
        }

        public static function setApiVersion($api_version): void {
        }
    }

    final class Customer {
        public static function all(array $parameters): object {
            StripeRestoreGuardTestState::record_request();
            throw new \Error('Synthetic Stripe failure for a deterministic regression test.');
        }
    }

    final class Subscription {
        public static function retrieve(string $subscription_id): object {
            StripeRestoreGuardTestState::record_request();
            throw new \Error('Synthetic Stripe failure for a deterministic regression test.');
        }

        public static function all(array $parameters): object {
            StripeRestoreGuardTestState::record_request();
            throw new \Error('Synthetic Stripe failure for a deterministic regression test.');
        }
    }
}

namespace Stripe\HttpClient {
    final class CurlClient {
        public const DEFAULT_TIMEOUT = 80;
        public const DEFAULT_CONNECT_TIMEOUT = 30;
        private static ?self $instance = null;
        public int $timeout = self::DEFAULT_TIMEOUT;
        public int $connect_timeout = self::DEFAULT_CONNECT_TIMEOUT;
        public array $timeout_history = [];
        public array $connect_timeout_history = [];

        public static function instance(): self {
            return self::$instance ??= new self();
        }

        public function setTimeout(int $seconds): self {
            $this->timeout = $seconds;
            $this->timeout_history[] = $seconds;
            return $this;
        }

        public function setConnectTimeout(int $seconds): self {
            $this->connect_timeout = $seconds;
            $this->connect_timeout_history[] = $seconds;
            return $this;
        }

        public function getTimeout(): int {
            return $this->timeout;
        }

        public function getConnectTimeout(): int {
            return $this->connect_timeout;
        }
    }
}

namespace {
    $workspace_root = dirname(__DIR__);
    $authentication_path = $workspace_root . '/app/core/Authentication.php';
    $authentication_source = file_get_contents($authentication_path);

    if($authentication_source === false) {
        fwrite(STDERR, "Could not load Authentication.php.\n");
        exit(1);
    }

    AuthenticationStripeRestoreGuardTestConfig::$settings = (object) [
        'payment' => (object) [
            'is_enabled' => true,
        ],
        'stripe' => (object) [
            'is_enabled' => true,
            'secret_key' => 'sk_test_redacted',
        ],
    ];

    require_once $authentication_path;

    $authentication_reflection = new \ReflectionClass('Altum\\Authentication');
    $should_attempt_method = $authentication_reflection->getMethod('should_attempt_stripe_plan_restore');
    $should_attempt_method->setAccessible(true);
    $maybe_restore_method = $authentication_reflection->getMethod('maybe_restore_stripe_plan_access');
    $maybe_restore_method->setAccessible(true);
    $attempted_users_property = $authentication_reflection->getProperty('stripe_plan_restore_attempted_user_ids');
    $attempted_users_property->setAccessible(true);

    $make_user = static function(int $user_id, array $extra = [], array $overrides = []): object {
        return (object) array_merge([
            'user_id' => $user_id,
            'type' => 0,
            'status' => 1,
            'plan_id' => 2,
            'email' => '',
            'payment_processor' => '',
            'payment_subscription_id' => '',
            'extra' => json_encode((object) $extra),
        ], $overrides);
    };

    $extract_source = static function(string $source, string $start, string $end): string {
        $start_position = strpos($source, $start);
        $end_position = $start_position === false ? false : strpos($source, $end, $start_position + strlen($start));

        if($start_position === false || $end_position === false) {
            return '';
        }

        return substr($source, $start_position, $end_position - $start_position);
    };

    $assertions = [];
    $synthetic_user_id = 1000;

    foreach(['', 'healthy', 'recovered'] as $billing_state) {
        foreach(['canceled', 'unpaid', 'incomplete_expired'] as $stripe_status) {
            $synthetic_user_id++;
            $terminal_user = $make_user($synthetic_user_id, [
                'stripe_customer_id' => 'cus_test_terminal',
                'billing_state' => $billing_state,
                'billing_stripe_status' => $stripe_status,
            ]);
            $description = sprintf('customer-only %s/%s is terminal', $billing_state === '' ? 'empty' : $billing_state, $stripe_status);
            $assertions[$description] = $should_attempt_method->invoke(null, $terminal_user) === false;
        }
    }

    $valid_subscription_user = $make_user(++$synthetic_user_id, [
        'stripe_customer_id' => 'cus_test_linked',
        'billing_state' => 'healthy',
        'billing_stripe_status' => 'canceled',
    ], [
        'payment_processor' => 'stripe',
        'payment_subscription_id' => 'sub_test_linked',
    ]);
    $assertions['a valid sub_ id keeps the legacy restore path eligible'] = $should_attempt_method->invoke(null, $valid_subscription_user) === true;

    $customer_only_user = $make_user(++$synthetic_user_id, [
        'stripe_customer_id' => 'cus_test_customer_only',
        'billing_state' => 'healthy',
        'billing_stripe_status' => 'active',
    ]);
    $assertions['customer-only active context remains eligible'] = $should_attempt_method->invoke(null, $customer_only_user) === true;

    $legacy_customer_only_user = $make_user(++$synthetic_user_id, [
        'stripe_customer_id' => 'cus_test_legacy',
    ]);
    $assertions['customer-only legacy context without a cached status remains eligible'] = $should_attempt_method->invoke(null, $legacy_customer_only_user) === true;

    $past_due_user = $make_user(++$synthetic_user_id, [
        'stripe_customer_id' => 'cus_test_past_due',
        'billing_state' => 'past_due',
        'billing_stripe_status' => 'past_due',
    ]);
    $assertions['past_due customer-only retry context remains eligible'] = $should_attempt_method->invoke(null, $past_due_user) === true;

    $past_due_terminal_user = $make_user(++$synthetic_user_id, [
        'stripe_customer_id' => 'cus_test_retry_terminal',
        'billing_state' => 'past_due',
        'billing_stripe_status' => 'canceled',
    ]);
    $assertions['past_due retry state is not suppressed by a terminal cached status'] = $should_attempt_method->invoke(null, $past_due_terminal_user) === true;

    $non_beginner_user = $make_user(++$synthetic_user_id, [
        'stripe_customer_id' => 'cus_test_paid_plan',
        'billing_state' => 'healthy',
        'billing_stripe_status' => 'active',
    ], [
        'plan_id' => 5,
    ]);
    $assertions['a non-Beginner plan never enters the restore fallback'] = $should_attempt_method->invoke(null, $non_beginner_user) === false;

    $maybe_restore_source = $extract_source(
        $authentication_source,
        'private static function maybe_restore_stripe_plan_access($user)',
        'public static function check()'
    );
    $stripe_lookup_source = $extract_source(
        $authentication_source,
        'private static function get_active_stripe_subscription_for_user($user)',
        'private static function sync_user_from_stripe_subscription($user, $subscription)'
    );
    $check_source = $extract_source(
        $authentication_source,
        'public static function check()',
        '/* Custom code */'
    );
    $already_logged_in_source = $extract_source(
        $check_source,
        '/* Already logged in from previous checks */',
        '/* Check the cookies first */'
    );

    $attempt_marker_position = strpos($maybe_restore_source, 'self::$stripe_plan_restore_attempted_user_ids[$user_id] = true;');
    $session_close_position = strpos($maybe_restore_source, 'session_write_close();');
    $cooldown_position = strpos($maybe_restore_source, 'self::acquire_stripe_plan_restore_cooldown($user_id)');
    $stripe_lookup_position = strpos($maybe_restore_source, 'self::get_active_stripe_subscription_for_user($user)');

    $assertions['already-authenticated checks do not repeat restore'] = $already_logged_in_source !== ''
        && !str_contains($already_logged_in_source, 'maybe_restore_stripe_plan_access');
    $assertions['initial cookie and session authentication retain exactly two restore call sites'] = substr_count($check_source, 'self::maybe_restore_stripe_plan_access(') === 2;
    $assertions['request attempt is marked before Stripe lookup'] = $attempt_marker_position !== false
        && $stripe_lookup_position !== false
        && $attempt_marker_position < $stripe_lookup_position;
    $assertions['session is closed before Stripe lookup'] = $session_close_position !== false
        && $stripe_lookup_position !== false
        && $session_close_position < $stripe_lookup_position;
    $assertions['session is closed before cross-request cooldown cache I/O'] = $session_close_position !== false
        && $cooldown_position !== false
        && $session_close_position < $cooldown_position;
    $assertions['restore fallback catches every Throwable'] = str_contains($maybe_restore_source, 'catch(\\Throwable $exception)');
    $assertions['restore log does not include the Stripe exception message'] = !str_contains($maybe_restore_source, 'getMessage(');
    $assertions['restore fallback uses a ten-minute cross-request cooldown'] = str_contains($authentication_source, 'expiresAfter(600)');
    $assertions['opportunistic Stripe lookup has a bounded total deadline'] = str_contains($maybe_restore_source, 'microtime(true) + 5.5')
        && str_contains($authentication_source, 'if($remaining_seconds < 1.0)');
    $assertions['every Stripe fallback request checks the shared deadline'] = substr_count($stripe_lookup_source, 'self::prepare_stripe_plan_restore_request()') === 3;
    $assertions['Stripe SDK timeouts are restored after the fallback'] = str_contains($maybe_restore_source, 'getConnectTimeout()')
        && str_contains($maybe_restore_source, 'getTimeout()')
        && str_contains($maybe_restore_source, 'setConnectTimeout($previous_stripe_connect_timeout)')
        && str_contains($maybe_restore_source, 'setTimeout($previous_stripe_timeout)');

    \Stripe\StripeRestoreGuardTestState::reset();
    $failure_user = $make_user(++$synthetic_user_id, [
        'stripe_customer_id' => 'cus_test_failure',
        'billing_state' => 'past_due',
        'billing_stripe_status' => 'past_due',
    ]);

    if(session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    session_name('fcc_auth_restore_guard_test');
    session_id('fcc-auth-restore-guard-' . getmypid());
    $session_started = session_start();

    if($session_started) {
        $_SESSION['guard_marker'] = 'preserved';
    }

    $previous_error_log = ini_get('error_log');
    ini_set('error_log', '/dev/null');
    $stripe_http_client = \Stripe\HttpClient\CurlClient::instance();
    $stripe_http_client->connect_timeout = 7;
    $stripe_http_client->timeout = 11;
    $stripe_http_client->connect_timeout_history = [];
    $stripe_http_client->timeout_history = [];
    $first_result = $maybe_restore_method->invoke(null, $failure_user);
    $second_result = $maybe_restore_method->invoke(null, $failure_user);
    $attempted_users_property->setValue(null, []);
    $third_result = $maybe_restore_method->invoke(null, $failure_user);
    ini_set('error_log', (string) $previous_error_log);

    $assertions['synthetic session starts for the lock regression'] = $session_started === true;
    $assertions['Throwable fails open with the original authenticated user'] = $first_result === $failure_user && $second_result === $failure_user && $third_result === $failure_user;
    $assertions['repeated restore calls make only one Stripe request'] = \Stripe\StripeRestoreGuardTestState::$request_count === 1;
    $assertions['cross-request cooldown remains ten minutes'] = AuthenticationStripeRestoreGuardTestCache::$expirations['authentication_stripe_plan_restore_attempt?user_id=' . $failure_user->user_id] === 600;
    $assertions['cooldown cache I/O observes no active PHP session lock'] = AuthenticationStripeRestoreGuardTestCache::$session_statuses === [PHP_SESSION_NONE, PHP_SESSION_NONE];
    $assertions['the Stripe request observes no active PHP session lock'] = \Stripe\StripeRestoreGuardTestState::$session_statuses === [PHP_SESSION_NONE];
    $assertions['temporary Stripe timeouts are applied and previous values restored'] = $stripe_http_client->connect_timeout_history === [2, 7]
        && $stripe_http_client->timeout_history === [5, 11]
        && $stripe_http_client->connect_timeout === 7
        && $stripe_http_client->timeout === 11;
    $assertions['restore leaves the session lock released'] = session_status() === PHP_SESSION_NONE;

    $session_reopened = session_start();
    $assertions['session data is persisted and readable after lazy reopening'] = $session_reopened === true
        && ($_SESSION['guard_marker'] ?? null) === 'preserved';

    if(session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();
    }

    $failed = [];

    foreach($assertions as $description => $passed) {
        if(!$passed) {
            $failed[] = $description;
        }
    }

    if($failed) {
        fwrite(STDERR, "Authentication Stripe restore guard regression check failed:\n- " . implode("\n- ", $failed) . "\n");
        exit(1);
    }

    echo "Authentication Stripe restore guard checks passed.\n";
}

/* /Custom code: FC-2026-09-11 */
