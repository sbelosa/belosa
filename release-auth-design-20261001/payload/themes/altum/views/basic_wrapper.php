<?php defined('ALTUMCODE') || die();
// Authentication uses the same request-local light palette as the public FCC.
\Altum\ThemeStyle::$workspace_light = true;
fcc_public_assets();
?>
<!DOCTYPE html>
<html lang="<?= fcc_language_tag() ?>" dir="<?= l('direction') ?>" class="h-100">
<head>
    <?php fcc_i18n_assets(); ?>
    <title><?= \Altum\Title::get() ?></title>
    <base href="<?= SITE_URL; ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <?php if(\Altum\Plugin::is_active('pwa') && settings()->pwa->is_enabled): ?>
            <meta name="theme-color" content="<?= settings()->pwa->theme_color ?>"/>

            <?php if(settings()->pwa->is_fullscreen ?? true): ?>
                <meta name="apple-mobile-web-app-capable" content="yes">
                <meta name="mobile-web-app-capable" content="yes">
                <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
            <?php endif ?>

			<?= pwa_generate_dynamic_splash_screen_links() ?>

            <link rel="manifest" href="<?= SITE_URL . UPLOADS_URL_PATH . \Altum\Uploads::get_path('pwa') . 'manifest.json' ?>" />
        <?php endif ?>

    <?php if(\Altum\Meta::$description): ?>
        <meta name="description" content="<?= htmlspecialchars((string) \Altum\Meta::$description, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif ?>
    <?php if(\Altum\Meta::$keywords): ?>
        <meta name="keywords" content="<?= htmlspecialchars((string) \Altum\Meta::$keywords, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif ?>

    <?php \Altum\Meta::output() ?>

    <?php if(\Altum\Meta::$canonical): ?>
        <link rel="canonical" href="<?= htmlspecialchars((string) \Altum\Meta::$canonical, ENT_QUOTES, 'UTF-8') ?>" />
    <?php endif ?>

    <?php if(\Altum\Meta::$robots): ?>
        <meta name="robots" content="<?= \Altum\Meta::$robots ?>">
    <?php endif ?>

    <link rel="alternate" href="<?= SITE_URL . \Altum\Router::$original_request ?>" hreflang="x-default" />
    <?php if(count(\Altum\Language::$active_languages) > 1): ?>
        <?php foreach(\Altum\Language::$active_languages as $language_name => $language_code): ?>
                <link rel="alternate" href="<?= htmlspecialchars(SITE_URL . (settings()->main->default_language === $language_name ? '' : $language_code . '/') . \Altum\Router::$original_request, ENT_QUOTES, 'UTF-8') ?>" hreflang="<?= fcc_language_tag($language_code) ?>" />
        <?php endforeach ?>
    <?php endif ?>

    <link href="<?= !empty(settings()->main->favicon) ? settings()->main->favicon_full_url : 'data:,' ?>" rel="icon" />

    <link href="<?= ASSETS_FULL_URL . 'css/' . \Altum\ThemeStyle::get_file() . '?v=' . PRODUCT_CODE ?>" id="css_theme_style" rel="stylesheet" media="screen,print">
    <?php foreach(['custom.' . (DEBUG ? null : 'min.') . 'css'] as $file): ?>
        <link href="<?= ASSETS_FULL_URL . 'css/' . $file . '?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen">
    <?php endforeach ?>
    <!-- Custom code: FC-2026-02-25: ensure custom.css overrides load -->
    <link href="<?= ASSETS_FULL_URL . 'css/custom.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen">
    <!-- /Custom code: FC-2026-02-25 -->

    <?php require THEME_PATH . 'views/partials/google_analytics.php' ?>

    <?= \Altum\Event::get_content('head') ?>

        <?php if(is_logged_in() && !user()->plan_settings->export->pdf): ?>
            <style>@media print { body { display: none; } }</style>
        <?php endif ?>

    <?php if(!empty(settings()->custom->head_js)): ?>
        <?= get_settings_custom_head_js() ?>
    <?php endif ?>

    <?php if(!empty(settings()->custom->head_css)): ?>
        <style><?= settings()->custom->head_css ?></style>
    <?php endif ?>
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-auth.css?v=<?= filemtime(ASSETS_PATH . 'css/fcc-auth.css') ?>">
</head>

<body class="fcc-public-mode fcc-auth-mode <?= l('direction') == 'rtl' ? 'rtl' : '' ?>" data-theme-style="light" data-fcc-auth-design="20261001">
<?php if(!empty(settings()->custom->body_content)): ?>
    <?= settings()->custom->body_content ?>
<?php endif ?>

<?php //ALTUMCODE:DEMO if(DEMO) echo include_view(THEME_PATH . 'views/partials/ac_banner.php', ['demo_url' => 'https://66biolinks.com/demo/', 'product_name' => PRODUCT_NAME, 'product_url' => PRODUCT_URL, 'product_buy_url' => PRODUCT_BUY_URL]) ?>

<?php require THEME_PATH . 'views/partials/announcements.php' ?>
<?php require THEME_PATH . 'views/partials/cookie_consent.php' ?>
<?php if(settings()->main->admin_spotlight_is_enabled || settings()->main->user_spotlight_is_enabled) require THEME_PATH . 'views/partials/spotlight.php' ?>

<a class="fcw-skip" href="<?= e('/' . ltrim((string) ($_SERVER['REQUEST_URI'] ?? '/'), '/')) ?>#fcc-auth-main"><?= e(fcc_public_copy('skip')) ?></a>
<header class="fcc-auth-header">
    <a class="fcw-brand" href="<?= e(fcc_public_url()) ?>" aria-label="Forever Card Club">
        <img src="<?= e(settings()->main->logo_light_full_url ?: settings()->main->logo_dark_full_url) ?>" width="208" height="64" alt="Forever Card Club">
    </a>
    <?php fcc_language_selector('auth'); ?>
</header>
<main id="fcc-auth-main" class="fcc-auth-main" tabindex="-1">
    <div class="fcc-auth-card <?= \Altum\Router::$controller_key === 'register' ? 'fcc-auth-card-wide' : '' ?>">
        <?= $this->views['content'] ?>
    </div>
    <footer class="fcc-auth-footer">
        <a href="<?= e(fcc_public_url()) ?>"><?= e(fcc_public_copy('home')) ?></a>
        <span aria-hidden="true">·</span>
        <a href="<?= e(fcc_public_url('page/contact')) ?>"><?= e(fcc_public_copy('contact')) ?></a>
        <small>© <?= date('Y') ?> Forever Card Club</small>
    </footer>
</main>

<?= \Altum\Event::get_content('modals') ?>

<?php require THEME_PATH . 'views/partials/js_global_variables.php' ?>

<?php foreach(['libraries/jquery.slim.min.js', 'libraries/popper.min.js', 'libraries/bootstrap.min.js', 'custom.' . (DEBUG ? null : 'min.') . 'js', 'libraries/fontawesome.min.js', 'libraries/fontawesome-solid.min.js', 'libraries/fontawesome-brands.modified.js'] as $file): ?>
    <script src="<?= ASSETS_FULL_URL ?>js/<?= $file ?>?v=<?= PRODUCT_CODE ?>"></script>
<?php endforeach ?>

<?php /* Custom code: FC-2026-04-10: internal FCC Coach popup on basic pages */ ?>
<?php if(is_logged_in() && fcc_ai_user_has_coach_access($this->user)): ?>
    <?php $fcc_internal_coach_ui = fcc_ai_get_internal_coach_ui_payload($this->user, \Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr'); ?>
    <?php $fcc_internal_coach_ui_copy = []; ?>
    <?php foreach(['hr', 'en', 'sl', 'bg', 'de', 'sr', 'sq', 'cnr', 'fr', 'es'] as $fcc_internal_coach_ui_language): ?>
        <?php $fcc_internal_coach_ui_copy[$fcc_internal_coach_ui_language] = fcc_ai_get_internal_coach_ui_payload($this->user, $fcc_internal_coach_ui_language); ?>
    <?php endforeach ?>
    <?= include_view(THEME_PATH . 'views/l/partials/fcc_chat_extreme_popup.php', [
        'config' => [
            'assistant_type' => 'coach',
            'scope' => 'internal_coach',
            'owner_name' => (string) ($this->user->name ?? ''),
            'language_code' => \Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr',
            'source_context' => fcc_t('FCC Coach basic page popup'),
            'hide_without_context' => false,
            'dom_id' => 'fcc-coach-chat-extreme-basic',
            'assistant_title' => (string) ($fcc_internal_coach_ui['assistant_title'] ?? fcc_t('FCC Coach')),
            'intro_label' => (string) ($fcc_internal_coach_ui['intro_label'] ?? fcc_t('FCC Coach')),
            'launcher_label' => (string) ($fcc_internal_coach_ui['launcher_label'] ?? fcc_t('FCC Coach')),
            'coach_badge' => (string) ($fcc_internal_coach_ui['coach_badge'] ?? ''),
            'coach_notice' => (string) ($fcc_internal_coach_ui['coach_notice'] ?? ''),
            'coach_mode_key' => (string) ($fcc_internal_coach_ui['coach_mode_key'] ?? ''),
            'input_placeholder' => (string) ($fcc_internal_coach_ui['input_placeholder'] ?? ''),
            'default_welcome' => (string) ($fcc_internal_coach_ui['default_welcome'] ?? ''),
            'ui_copy_override' => $fcc_internal_coach_ui_copy,
            'storage_key' => fcc_ai_get_internal_storage_key(),
            'context_storage_key' => fcc_ai_get_internal_context_storage_key(),
            'conversation_url' => url('fcc-ai/coach-conversation'),
            'message_url' => url('fcc-ai/coach-message'),
            'lead_url' => '',
            'lead_enabled' => false,
        ],
    ]) ?>
<?php endif ?>
<?php /* /Custom code: FC-2026-04-10 */ ?>

<?= \Altum\Event::get_content('javascript') ?>
</body>
</html>
