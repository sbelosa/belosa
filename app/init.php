<?php
/*
 * Copyright (c) 2025 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

defined('ALTUMCODE') || die();
define('ROOT_PATH', realpath(__DIR__ . '/..') . '/');

/* Custom code: FC-2026-04-01: Resolve folder names safely on case-sensitive live environments */
$resolve_root_directory_name = static function(array $candidates): string {
    foreach($candidates as $candidate) {
        if(is_dir(ROOT_PATH . $candidate)) {
            return $candidate;
        }
    }

    return $candidates[0];
};

$app_directory_name = $resolve_root_directory_name(['app', 'APP']);
$plugins_directory_name = $resolve_root_directory_name(['plugins', 'Plugins']);
$themes_directory_name = $resolve_root_directory_name(['themes', 'Themes']);
$uploads_directory_name = $resolve_root_directory_name(['uploads', 'Uploads']);

define('APP_PATH', ROOT_PATH . $app_directory_name . '/');
define('PLUGINS_PATH', ROOT_PATH . $plugins_directory_name . '/');
define('THEME_PATH', ROOT_PATH . $themes_directory_name . '/altum/');
define('THEME_URL_PATH', $themes_directory_name . '/altum/');
define('ASSETS_PATH', THEME_PATH . 'assets/');
define('ASSETS_URL_PATH', THEME_URL_PATH . 'assets/');
define('UPLOADS_PATH', ROOT_PATH . $uploads_directory_name . '/');
define('UPLOADS_URL_PATH', $uploads_directory_name . '/');
/* /Custom code: FC-2026-04-01 */

const CACHE_DEFAULT_SECONDS = 2592000;

/* Starting to include the required files */
require_once APP_PATH . 'includes/debug.php';
if(!DEBUG) require_once APP_PATH . 'includes/500.php';
require_once APP_PATH . 'includes/product.php';

/* Config file */
require_once ROOT_PATH . 'config.php';

/* Custom code: FC-2026-04-01: bootstrap fallback for LOS privacy hashing on partial deploys */
if(!defined('LOS_PRIVACY_HASH_SALT')) {
    $los_privacy_seed_parts = [
        defined('SITE_URL') ? (string) SITE_URL : '',
        defined('DATABASE_NAME') ? (string) DATABASE_NAME : '',
        defined('DATABASE_PASSWORD') ? (string) DATABASE_PASSWORD : '',
    ];

    $los_privacy_seed = implode('|', array_filter($los_privacy_seed_parts, static function($value) {
        return trim((string) $value) !== '';
    }));

    if($los_privacy_seed === '') {
        $los_privacy_seed = ROOT_PATH;
    }

    define('LOS_PRIVACY_HASH_SALT', getenv('LOS_PRIVACY_HASH_SALT') ?: hash('sha256', $los_privacy_seed));
}
/* /Custom code: FC-2026-04-01 */

/* Custom code: FC-2026-04-08: bootstrap readonly live ops access from environment or local root config */
$fc_bootstrap_value = static function(array $keys, string $default = ''): string {
    foreach($keys as $key) {
        $value = getenv($key);

        if($value !== false && trim((string) $value) !== '') {
            return trim((string) $value);
        }

        if(isset($_SERVER[$key]) && trim((string) $_SERVER[$key]) !== '') {
            return trim((string) $_SERVER[$key]);
        }

        if(isset($_ENV[$key]) && trim((string) $_ENV[$key]) !== '') {
            return trim((string) $_ENV[$key]);
        }
    }

    return $default;
};

$ops_readonly_config_path = ROOT_PATH . 'ops-readonly-config.php';
if(file_exists($ops_readonly_config_path)) {
    require_once $ops_readonly_config_path;
}

if(!defined('FCC_OPS_READONLY_ENABLED')) {
    $fcc_ops_readonly_enabled = $fc_bootstrap_value([
        'FCC_OPS_READONLY_ENABLED',
        'REDIRECT_FCC_OPS_READONLY_ENABLED',
    ]);

    define('FCC_OPS_READONLY_ENABLED', in_array(mb_strtolower($fcc_ops_readonly_enabled), ['1', 'true', 'on', 'yes'], true));
}

if(!defined('FCC_OPS_READONLY_KEY')) {
    $fcc_ops_readonly_key = $fc_bootstrap_value([
        'FCC_OPS_READONLY_KEY',
        'REDIRECT_FCC_OPS_READONLY_KEY',
    ]);

    define('FCC_OPS_READONLY_KEY', trim((string) $fcc_ops_readonly_key));
}
/* /Custom code: FC-2026-04-08 */

/* Establish cookie / session on this path specifically */
define('COOKIE_PATH', preg_replace('|https?://[^/]+|i', '', SITE_URL));

/* Determine if we should set the samesite=strict */
session_set_cookie_params([
    'lifetime' => null,
    'path' => COOKIE_PATH,
    'samesite' => 'Lax',
    'secure' => str_starts_with(SITE_URL, 'https://'),
]);

/* Autoloader */
spl_autoload_register (function ($class) {
    $namespace_prefix = 'Altum';
    $split = explode('\\', $class);

    if($split[0] !== $namespace_prefix) {
        return;
    }

    /* Altum core */
    if(isset($split[1]) && !isset($split[2])) {
        require_once APP_PATH . 'core/' . $split[1] . '.php';
    }

    /* Traits, Models, Helpers */
    if(isset($split[1], $split[2]) && in_array($split[1], ['Traits', 'Models', 'Helpers'])) {
        $folder = mb_strtolower($split[1]);
        require_once APP_PATH . $folder . '/' . $split[2] . '.php';
    }

    /* Payment Gateways helpers */
    if(isset($split[1], $split[2]) && $split[1] == 'PaymentGateways') {
        require_once APP_PATH . 'helpers/payment-gateways/' . $split[2] . '.php';
    }

    /* Qr codes helpers */
    if(isset($split[1], $split[2]) && $split[1] == 'QrCodes') {
        require_once APP_PATH . 'helpers/qr-codes/' . $split[2] . '.php';
    }
});

/* Require files */
require_once APP_PATH . 'core/Controller.php';
require_once APP_PATH . 'core/Model.php';
require_once APP_PATH . 'core/NotFoundException.php';

/* Load some helpers */
require_once APP_PATH . 'helpers/Link.php';
require_once APP_PATH . 'helpers/core.php';
require_once APP_PATH . 'helpers/sessions.php';
require_once APP_PATH . 'helpers/sessions.php';
require_once APP_PATH . 'helpers/others.php';
require_once APP_PATH . 'helpers/links.php';
require_once APP_PATH . 'helpers/strings.php';
require_once APP_PATH . 'helpers/email.php';
/* Custom code: FC-2026-03-18: live email automations helpers */
require_once APP_PATH . 'helpers/automations.php';
/* /Custom code: FC-2026-03-18 */
require_once APP_PATH . 'helpers/fcc_ai.php';
require_once APP_PATH . 'helpers/fcc_featured.php';
require_once APP_PATH . 'helpers/66uptime.php';

/* Autoload for vendor */
require_once ROOT_PATH . 'vendor/autoload.php';
