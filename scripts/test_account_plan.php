<?php
/* Offline account-plan rendering and cancellation guards. No database or payment API calls. */
namespace {
    if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    define('ALTUMCODE', true);
    define('APP_PATH', dirname(__DIR__) . '/app/');
    final class AccountPlanRedirect extends \RuntimeException {}
    function redirect($url) { throw new AccountPlanRedirect($url); }
    function l($key) {
        if(!array_key_exists($key, $GLOBALS['translations'])) throw new \RuntimeException('Missing translation: ' . $key);
        return $GLOBALS['translations'][$key];
    }
    function nr($value, $decimals = 0) { return number_format((float) $value, $decimals, ',', '.'); }
    function currency() { return 'EUR'; }
    function url($path) { return 'http://localhost:8091/' . $path; }
    function settings() { return $GLOBALS['test_settings']; }
    function vip_funnel_demo_is_sandbox_user($user) { return !empty($user->demo); }
    function vip_funnel_demo_get_locked_action_message($route) { return 'Demo account'; }
}
namespace Altum {
    class Language { public static $name = 'Hrvatski'; }
    class Date { public static function get($date, $format) { return (new \DateTimeImmutable($date))->format('d.m.Y.'); } }
    class Csrf {
        public static function get() { return 'test-csrf-token'; }
        public static function get_url_query() { return '&token=' . self::get(); }
    }
    class Authentication {
        public static bool $authenticated = true;
        public static function guard() { if(!self::$authenticated) \redirect('login'); }
    }
    class Alerts {
        public static array $messages = [];
        public static function output_alerts() { return ''; }
        public static function add_info($text) { self::$messages['info'] = $text; }
        public static function add_error($text) { self::$messages['error'] = $text; }
        public static function add_success($text) { self::$messages['success'] = $text; }
        public static function has_field_errors() { return false; }
        public static function has_errors() { return isset(self::$messages['error']); }
    }
    /* The unchanged full feature-list partial is outside these focused view tests. */
    class View {
        public function __construct($view) {}
        public function run($data = []) { return '<ul><li>FCC plan features</li></ul>'; }
    }
}
namespace Altum\Models {
    class User {
        public static array $calls = [];
        public static bool $fail = false;
        public function cancel_subscription($id, $at_period_end = false) {
            self::$calls[] = [$id, $at_period_end];
            if(self::$fail) throw new \RuntimeException('Payment service unavailable');
        }
    }
}
namespace Altum\Controllers {
    class Controller { public $user; }
    require APP_PATH . 'controllers/AccountPlan.php';
}
namespace {
    set_error_handler(static function($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    function fixture(string $state): array {
        $GLOBALS['test_settings'] = (object) ['payment' => (object) [
            'is_enabled' => $state !== 'payments-disabled',
            'currencies' => (object) ['EUR' => (object) ['currency_decimals' => 2, 'symbol' => '€', 'currency_placement' => 'right']],
        ]];
        $plan_settings = (object) [
            'biolinks_limit' => 3, 'biolink_blocks_limit' => 25,
            'ai_growth_plan_is_enabled' => true, 'fcc_ai_is_enabled' => true, 'fcc_coach_is_enabled' => true,
            'vip_funnel_core_is_enabled' => true, 'funnels_analytics_is_enabled' => true,
            'enabled_biolink_blocks' => (object) array_fill_keys(['lead_funnel', 'link_forever_shop', 'link_forever_webshop_reg', 'link_discount', 'link_save_contact', 'custom_html_whatsapp'], true),
        ];
        $plan = (object) ['name' => 'PRO', 'description' => 'Vaši FCC i Forever alati za poslovni rast.', 'settings' => $plan_settings];
        $user = (object) ['user_id' => 42, 'plan_id' => '5', 'plan' => $plan, 'plan_settings' => $plan_settings,
            'plan_expiration_date' => date('Y-m-d H:i:s', strtotime('+1 month')), 'payment_subscription_id' => 'sub_fixture',
            'payment_processor' => 'stripe', 'payment_total_amount' => 24.90, 'payment_currency' => 'EUR', 'plan_trial_done' => 1];
        $data = (object) ['billing_summary' => ['billing_state' => 'healthy', 'stripe_status' => 'active'],
            'stripe_portal_available' => true, 'suggested_plan' => null, 'suggested_plan_code' => null];
        if(in_array($state, ['canceled', 'free', 'lifetime', 'expired'], true)) $user->payment_subscription_id = '';
        if(in_array($state, ['free', 'free-recurring', 'suggested'], true)) {
            $user->plan_id = 'free'; $plan->name = 'FREE'; $plan->description = 'Osnovni FCC paket.';
            $user->plan_expiration_date = null;
        }
        if($state === 'trial') $data->billing_summary['stripe_status'] = 'trialing';
        if($state === 'lifetime') $user->plan_expiration_date = '2099-01-01 00:00:00';
        if(in_array($state, ['expired', 'past-due'], true)) $user->plan_expiration_date = '2020-01-01 00:00:00';
        if($state === 'invalid-date') $user->plan_expiration_date = 'invalid-date';
        if($state === 'portal-unavailable') $data->stripe_portal_available = false;
        if($state === 'past-due') $data->billing_summary = ['billing_state' => 'past_due_critical', 'stripe_status' => 'past_due', 'grace_until' => date('Y-m-d H:i:s', strtotime('+3 days'))];
        if($state === 'free-recurring') $data->billing_summary = ['billing_state' => 'access_revoked', 'stripe_status' => 'past_due', 'grace_until' => date('Y-m-d H:i:s', strtotime('+3 days'))];
        if($state === 'suggested') {
            $user->payment_subscription_id = ''; $user->plan_trial_done = 0;
            $user->plan_settings = (object) ['biolinks_limit' => 1, 'biolink_blocks_limit' => 5];
            $data->suggested_plan = (object) ['plan_id' => 5, 'name' => 'PRO', 'description' => 'Napredni alati za razvoj vašeg poslovanja.', 'settings' => $plan_settings, 'trial_days' => 7,
                'prices' => (object) ['monthly' => (object) ['EUR' => 24.90], 'annual' => (object) ['EUR' => 249]]];
        }
        return [$user, $data];
    }
    function render_plan($user, $data): string {
        $context = new class { public $user; public $views = ['account_header_menu' => '']; };
        $context->user = $user;
        return (function() use ($data) {
            ob_start();
            require dirname(__DIR__) . '/themes/altum/views/account-plan/index.php';
            return ob_get_clean();
        })->call($context);
    }
    function check($passed, $message) { if(!$passed) throw new \RuntimeException($message); }
    function document($html): \DOMXPath {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        return new \DOMXPath($doc);
    }
    $states = ['active', 'trial', 'canceled', 'free', 'free-recurring', 'past-due', 'expired', 'lifetime', 'portal-unavailable', 'payments-disabled', 'suggested', 'invalid-date'];
    if(($argv[1] ?? '') === '--preview') {
        $state = $argv[2] ?? 'active';
        check(in_array($state, $states, true), 'Unknown preview state');
        $GLOBALS['translations'] = require APP_PATH . 'languages/Hrvatski#hr.php';
        [$user, $data] = fixture($state);
        $html = render_plan($user, $data);
        $assets = 'http://localhost:8091/themes/altum/assets/';
        echo '<!doctype html><html lang="hr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Account plan – offline preview</title>';
        echo '<link rel="stylesheet" href="' . $assets . 'css/bootstrap.min.css"><link rel="stylesheet" href="' . $assets . 'css/custom.css">';
        echo '<style>body{padding:24px 0;background:#f3f5f8} .preview-note{max-width:1110px;margin:0 auto 20px;padding:0 15px;font-size:12px;color:#64748b}</style></head><body>';
        echo '<p class="preview-note">Lokalni pregled s izmišljenim podacima · ' . $state . '</p>' . $html;
        echo '<script src="' . $assets . 'js/libraries/fontawesome.min.js"></script><script src="' . $assets . 'js/libraries/fontawesome-solid.min.js"></script></body></html>';
        exit;
    }
    foreach(['Hrvatski#hr.php', 'english#en.php', 'cache/Hrvatski#hr.php', 'cache/english#en.php'] as $language) {
        $GLOBALS['translations'] = require APP_PATH . 'languages/' . $language;
        \Altum\Language::$name = str_contains($language, 'english') ? 'english' : 'Hrvatski';
        foreach($states as $state) {
            [$user, $data] = fixture($state);
            $html = render_plan($user, $data);
            $dom = document($html);
            $has_subscription = $user->payment_subscription_id !== '';
            check($dom->query('//form[@data-cancel-subscription-form]')->length === (int) $has_subscription, "$language/$state: cancellation availability");
            if($has_subscription) {
                check($dom->query('//section[contains(@class,"fcc-account-plan-hero")]//details[@id="fcc-cancel-subscription"]')->length === 1, "$state: cancellation is in the overview");
                check($dom->query('//details[@id="fcc-cancel-subscription" and not(@open)]//form[@method="post"]/input[@name="token" and @value="test-csrf-token"]')->length === 1, "$state: explicit confirmation and POST token");
            }
            check($dom->query('//a[contains(@href,"cancel_subscription")]')->length === 0, "$state: no cancellation GET links");
            check($dom->query('//h1')->length === 1, "$state: one page heading");
            if($state === 'canceled') check(str_contains($html, l('account_plan.manage.state_off')), 'Canceled renewal status');
            if($state === 'trial') check(str_contains($html, l('account_plan.manage.trial_until')), 'Trial date label');
            if($state === 'free-recurring') check(str_contains($html, l('account_plan.manage.state_attention')), 'Revoked access must not hide ongoing billing');
            if($state === 'portal-unavailable') check(!str_contains($html, 'href="http://localhost:8091/account-plan/stripe_payment_method"'), 'No unavailable portal link');
            if($state === 'suggested') check($dom->query('//details[@id="fcc-plan-compare" and not(@open)]//table/thead/tr/th[@scope="col"]')->length === 3, 'Comparison collapsed with accessible column labels');
            if($state === 'active') check(!str_contains($html, 'href="http://localhost:8091/plan/renew"'), 'Recurring users do not get a duplicate renewal CTA');
        }
    }
    $GLOBALS['translations'] = require APP_PATH . 'languages/english#en.php';
    $cases = [
        ['GET', [], true, false, true, false, 0, false],
        ['POST', [], true, false, true, false, 0, false],
        ['POST', ['token' => 'wrong'], true, false, true, false, 0, false],
        ['POST', ['token' => ['test-csrf-token']], true, false, true, false, 0, false],
        ['POST', ['token' => 'test-csrf-token'], true, true, true, false, 0, false],
        ['POST', ['token' => 'test-csrf-token'], false, false, true, false, 0, false],
        ['POST', ['token' => 'test-csrf-token'], true, false, false, false, 0, false],
        ['POST', ['token' => 'test-csrf-token'], true, false, true, false, 1, true],
        ['POST', ['token' => 'test-csrf-token'], true, false, true, true, 1, false],
    ];
    foreach($cases as $index => [$method, $post, $subscription, $demo, $authenticated, $fail, $calls, $success]) {
        $_SERVER['REQUEST_METHOD'] = $method; $_POST = $post;
        // A query-string token must never authorize a cancellation form.
        $_GET = ['token' => 'test-csrf-token'];
        \Altum\Alerts::$messages = []; \Altum\Models\User::$calls = []; \Altum\Models\User::$fail = $fail;
        \Altum\Authentication::$authenticated = $authenticated;
        $controller = new \Altum\Controllers\AccountPlan();
        $controller->user = (object) ['user_id' => 42, 'plan_id' => 'free', 'payment_subscription_id' => $subscription ? 'sub_fixture' : '', 'demo' => $demo];
        try { $controller->cancel_subscription(); } catch(AccountPlanRedirect $redirect) {}
        check(count(\Altum\Models\User::$calls) === $calls, "Cancellation case $index: service calls");
        check(isset(\Altum\Alerts::$messages['success']) === $success, "Cancellation case $index: success only after cancellation");
        if($calls) check(\Altum\Models\User::$calls[0] === [42, true], 'Cancel only the signed-in account and preserve access until period end');
    }
    echo "Account plan checks passed: 48 rendered states and 9 cancellation request cases.\n";
}
