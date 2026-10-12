<?php
/* Optional visual layer "Živi sloj" for the FCC workspace, sign-in pages and FCC's own public pages.
   It only adds one stylesheet after the existing ones, so switching it off restores the current look exactly.
   Collaborators' public cards (biolink pages) use their own wrapper and never load this layer. */
defined('ALTUMCODE') || die();

const FCC_UI_THEMES = ['classic', 'zivi'];

/** Host default from FCC_UI_THEME, optionally overridden by the viewer's preview choice. */
function fcc_ui_theme(): string {
    static $theme = null;
    if($theme !== null) return $theme;

    $default = getenv('FCC_UI_THEME');
    $theme = in_array($default, FCC_UI_THEMES, true) ? $default : 'classic';

    if(!fcc_ui_theme_preview_allowed()) return $theme;

    $requested = $_GET['fcc_ui'] ?? null;
    if(is_string($requested) && in_array($requested, FCC_UI_THEMES, true)) {
        if(!headers_sent()) {
            setcookie('fcc_ui', $requested, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax', 'httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
        }
        return $theme = $requested;
    }

    $saved = $_COOKIE['fcc_ui'] ?? null;
    if(is_string($saved) && in_array($saved, FCC_UI_THEMES, true)) $theme = $saved;

    return $theme;
}

/** Preview switching stays local or explicitly enabled until the layer is approved for everyone. */
function fcc_ui_theme_preview_allowed(): bool {
    return getenv('FCC_LOCAL') === '1' || getenv('FCC_UI_THEME_PREVIEW') === '1';
}

function fcc_ui_theme_is_zivi(): bool {
    return fcc_ui_theme() === 'zivi';
}

function fcc_ui_theme_body_class(): string {
    return fcc_ui_theme_is_zivi() ? 'fcc-ui-zivi' : '';
}

/** Stylesheet tags for one shell: app (signed-in workspace), auth (sign-in, registration) or public (FCC's own pages). */
function fcc_ui_theme_assets(string $shell): string {
    if(!fcc_ui_theme_is_zivi() || !in_array($shell, ['app', 'auth', 'public'], true)) return '';

    $file = 'css/fcc-zivi-sloj.css';
    $version = is_file(ASSETS_PATH . $file) ? filemtime(ASSETS_PATH . $file) : PRODUCT_CODE;

    return '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700;9..40,800&display=swap">'
        . '<link rel="stylesheet" href="' . ASSETS_FULL_URL . $file . '?v=' . $version . '" data-fcc-ui="zivi">';
}
