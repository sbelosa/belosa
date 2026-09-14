<?php
/* Custom code: FC-2026-09-14: Exercise the real registration controller, CAPTCHA, CSRF and view without external writes */
namespace {
    if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    define('ALTUMCODE', true);
    define('APP_PATH', dirname(__DIR__) . '/app/');
    define('ASSETS_PATH', dirname(__DIR__) . '/themes/altum/assets/');
    define('COOKIE_PATH', '/');
    final class RegistrationRedirect extends \RuntimeException {}
    function session_get($key, $default = null) { return $_SESSION[$key] ?? $default; }
    function session_set($key, $value) { $_SESSION[$key] = $value; }
    function session_unset_key($key) { unset($_SESSION[$key]); }
    function settings() { return $GLOBALS['fixture_settings']; }
    function l($key) {
        if(!isset($GLOBALS['translations'][$key])) throw new \RuntimeException('Missing translation: ' . $key);
        return $GLOBALS['translations'][$key];
    }
    function url($path = '') { return 'https://example.test/' . $path; }
    function redirect($path) { throw new RegistrationRedirect($path); }
    function throw_404() { throw new \RuntimeException('404'); }
    function process_and_get_redirect_params() { return null; }
    function get_contact_phone_dial_codes_array() { return ['HR' => '385']; }
    function get_contact_phone_country_options_array() { return ['HR' => 'Hrvatska (+385)']; }
    function get_domain_from_email($email) { return explode('@', $email)[1] ?? ''; }
    function get_ip() { return '192.0.2.1'; }
    function get_this_device_type() { return 'desktop'; }
    function get_maxmind_reader_country() { throw new \RuntimeException('No GeoIP in offline test'); }
    function output_alert($type, $message) { return '<p>' . e($message) . '</p>'; }
    class RegistrationDatabase {
        public function where(...$args) { return $this; }
        public function has($table) { return false; }
        public function getValue(...$args) { return $GLOBALS['recent_registrations'] ?? 0; }
    }
    function db() { return new RegistrationDatabase(); }
    require APP_PATH . 'helpers/strings.php';
    require APP_PATH . 'core/Alerts.php';
    require APP_PATH . 'core/Csrf.php';
    require APP_PATH . 'core/Captcha.php';
}
namespace Altum {
    class Authentication { public static function guard($role) {} }
    class CustomHooks { public static function user_initiate_registration() {} }
    class Plugin { public static function is_active($name) { return false; } }
    class Logger { public static function users(...$args) {} }
    class Event { public static function add_content(...$args) {} }
    class View {
        public static array $data = [];
        public function __construct(...$args) {}
        public function run($data) { self::$data = $data; return ''; }
    }
}
namespace Altum\Models {
    class User {
        public static array $created = [];
        public static int $links_created = 0;
        public function create(...$args) { self::$created[] = $args; return ['user_id' => 999]; }
        public function create_links($id) { self::$links_created++; }
    }
    class Plan { public function get_plan_by_id($id) { return (object) ['settings' => (object) []]; } }
}
namespace Altum\Controllers {
    class Controller { public function add_view_content(...$args) {} }
    require APP_PATH . 'controllers/Register.php';
}
namespace {
    set_error_handler(static function($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    $checks = 0;
    function check($condition, string $message): void {
        if(!$condition) throw new \RuntimeException($message);
        $GLOBALS['checks']++;
    }
    function fixture(): void {
        $_SESSION = $_GET = $_POST = $_COOKIE = [];
        $_SERVER['HTTP_USER_AGENT'] = 'FCC registration regression test';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        \Altum\Models\User::$created = [];
        \Altum\Models\User::$links_created = 0;
        \Altum\View::$data = [];
        $GLOBALS['recent_registrations'] = 0;
        $GLOBALS['translations'] = require APP_PATH . 'languages/Hrvatski#hr.php';
        $GLOBALS['fixture_settings'] = (object) [
            'users' => (object) [
                'register_is_enabled' => true, 'register_only_social_logins' => false,
                'register_display_newsletter_checkbox' => false, 'email_aliases_is_enabled' => true,
                'register_lockout_is_enabled' => false, 'register_lockout_time' => 1,
                'register_lockout_max_registrations' => 5, 'email_confirmation' => true,
                'blacklisted_domains' => [], 'blacklisted_countries' => [], 'blacklisted_ips' => [],
            ],
            /* The legacy switch is deliberately OFF: FCC registration must still require CAPTCHA. */
            'captcha' => (object) ['type' => 'basic', 'register_is_enabled' => false],
            'main' => (object) ['default_timezone' => 'Europe/Zagreb', 'terms_and_conditions_url' => '#terms', 'privacy_policy_url' => '#privacy'],
        ];
        foreach(['facebook', 'google', 'twitter', 'discord', 'linkedin', 'microsoft'] as $provider) {
            settings()->$provider = (object) ['is_enabled' => false];
        }
        session_set('captcha', [
            'value' => hash('sha256', 'abc234'), 'expires' => time() + 300,
            'ua' => hash('sha256', $_SERVER['HTTP_USER_AGENT']),
        ]);
        $_POST = [
            'name' => 'Test Member', 'email' => 'registration@example.test', 'password' => 'test-password',
            'meta_foreverId' => '001234567890', 'meta_address' => 'Test 1', 'meta_city' => 'Zagreb',
            'meta_zip' => '10000', 'meta_country' => 'Hrvatska', 'meta_phone' => '0911234567',
            'meta_phone_country_code' => 'HR', 'captcha' => 'AbC234',
            'registration_token' => \Altum\Csrf::get('registration_token'), 'registration_website' => '',
        ];
    }
    function submit(): bool {
        try { (new \Altum\Controllers\Register())->index(); }
        catch(RegistrationRedirect $redirect) {
            check($redirect->getMessage() === 'index', 'Successful registration keeps the existing approval flow');
        }
        return count(\Altum\Models\User::$created) === 1;
    }

    foreach([
        'missing' => null, 'empty' => '', 'letters' => 'abcdefghijkl', 'mixed' => '00123456789a',
        'short' => '12345678901', 'long' => '0012345678901', 'long suffix' => '001234567890spam',
        'inner space' => '001234 567890', 'leading space' => ' 001234567890', 'trailing space' => '001234567890 ',
        'newline' => "001234567890\n", 'tab' => "001234567890\t", 'nul' => "001234567890\0",
        'unicode digits' => '０１２３４５６７８９０１', 'arabic digits' => '٠١٢٣٤٥٦٧٨٩٠١',
        'HTML' => '<b>001234567890</b>', 'array' => ['001234567890'],
        'integer' => 123456789012, 'float' => 123456789012.0, 'scientific' => '1.2345678e11',
    ] as $label => $value) {
        fixture();
        if($value === null) unset($_POST['meta_foreverId']); else $_POST['meta_foreverId'] = $value;
        check(!submit(), 'Invalid ID must not create a user: ' . $label);
        check(\Altum\Alerts::has_field_errors('meta_foreverId'), 'Invalid ID needs a field error: ' . $label);
        check(\Altum\Models\User::$links_created === 0, 'Invalid registration must not create links');
    }
    foreach(['001234567890', '360123456789', '000000000001'] as $valid_id) {
        fixture(); $_POST['meta_foreverId'] = $valid_id;
        check(submit(), 'A valid ID with valid security checks must register');
        check(\Altum\Models\User::$created[0][14]['foreverId'] === $valid_id, 'Store all digits including leading zeroes unchanged');
        check(\Altum\Models\User::$created[0][3] === 0, 'New accounts still require approval');
        check(\Altum\Models\User::$links_created === 1, 'Successful registration creates its links once');
        check(session_get('captcha') === null, 'A successful CAPTCHA cannot be replayed');
    }
    foreach(['missing', 'wrong', 'array', 'expired', 'new session', 'different browser', 'replayed'] as $case) {
        fixture();
        if($case === 'missing') unset($_POST['captcha']);
        if($case === 'wrong') $_POST['captcha'] = 'wrong';
        if($case === 'array') $_POST['captcha'] = ['AbC234'];
        if($case === 'expired') $_SESSION['captcha']['expires'] = time() - 1;
        if($case === 'new session') unset($_SESSION['captcha']);
        if($case === 'different browser') $_SERVER['HTTP_USER_AGENT'] = 'Another browser';
        if($case === 'replayed') check((new \Altum\Captcha())->is_valid(), 'Initial CAPTCHA solution works');
        check(!submit(), 'Invalid CAPTCHA must not create a user: ' . $case);
        check(\Altum\Alerts::has_field_errors('captcha'), 'Invalid CAPTCHA needs a field error: ' . $case);
    }
    foreach(['missing', 'wrong', 'array', 'query only', 'different session'] as $case) {
        fixture();
        if($case === 'query only') $_GET['registration_token'] = $_POST['registration_token'];
        if(in_array($case, ['missing', 'query only'])) unset($_POST['registration_token']);
        if($case === 'wrong') $_POST['registration_token'] = 'wrong';
        if($case === 'array') $_POST['registration_token'] = ['wrong'];
        if($case === 'different session') unset($_SESSION['registration_token']);
        check(!submit(), 'Invalid CSRF token must not create a user: ' . $case);
        check(\Altum\Alerts::has_errors(), 'Invalid CSRF token needs a visible retry message');
    }
    foreach(['https://spam.example', ['malformed']] as $trap) {
        fixture(); $_POST['registration_website'] = $trap;
        check(!submit(), 'A filled or malformed honeypot must not create a user');
    }
    foreach(['name', 'email', 'password', 'meta_phone', 'meta_address', 'cf-turnstile-response'] as $field) {
        fixture(); $_POST[$field] = ['malformed'];
        check(!submit(), 'Malformed fields must fail safely: ' . $field);
    }
    fixture(); settings()->users->register_lockout_is_enabled = true; $GLOBALS['recent_registrations'] = 5;
    check(!submit(), 'Existing IP registration limits remain enforced');

    /* Render the actual view in both languages with the real PNG CAPTCHA generator. */
    foreach(['Hrvatski#hr.php', 'english#en.php'] as $language) {
        fixture(); $GLOBALS['translations'] = require APP_PATH . 'languages/' . $language;
        $_POST = []; $_SERVER['REQUEST_METHOD'] = 'GET';
        (new \Altum\Controllers\Register())->index();
        $data = (object) \Altum\View::$data;
        ob_start(); require dirname(__DIR__) . '/themes/altum/views/register/index.php'; $html = ob_get_clean();
        check(str_contains($html, 'pattern="[0-9]{12}"'), 'Browser validates exactly twelve digits');
        check(str_contains($html, 'inputmode="numeric"'), 'Mobile users get a numeric keyboard');
        check(str_contains($html, 'name="registration_token"'), 'Form provides the session token');
        check(str_contains($html, 'data:image/png;base64,'), 'CAPTCHA renders even with the legacy register switch disabled');
        check(str_contains($html, l('register.forever_id_help')), 'The translated ID help is rendered');
        check(str_contains($html, 'tabindex="-1"'), 'The bot trap is skipped by keyboard navigation');
        preg_match('/data:image\/png;base64,([^\"]+)/', $html, $png);
        check(str_starts_with(base64_decode($png[1]), "\x89PNG\r\n\x1a\n"), 'CAPTCHA is a real PNG');
        $cached = require APP_PATH . 'languages/cache/' . $language;
        check($cached['register.forever_id_help'] === l('register.forever_id_help'), 'Cached translations match');
    }
    fixture(); $_POST['meta_foreverId'] = '\" autofocus onfocus=alert(1)';
    check(!submit(), 'Markup in an ID is rejected');
    check(!str_contains(\Altum\View::$data['values']['meta_foreverId'], '"'), 'The returned ID cannot escape its HTML attribute');
    check(\Altum\View::$data['values']['password'] === '', 'Passwords are not returned to the view');

    echo "Registration security: {$checks} checks passed; no database writes or outbound messages.\n";
}
/* /Custom code: FC-2026-09-14 */
