<?php defined('ALTUMCODE') || die() ?>
<!DOCTYPE html>
<html lang="<?= \Altum\Language::$code ?>" dir="<?= l('direction') ?>" class="h-100">
<head>
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
        <meta name="description" content="<?= \Altum\Meta::$description ?>" />
    <?php endif ?>
    <?php if(\Altum\Meta::$keywords): ?>
        <meta name="keywords" content="<?= \Altum\Meta::$keywords ?>" />
    <?php endif ?>

    <?php \Altum\Meta::output() ?>

    <?php if(\Altum\Meta::$canonical): ?>
        <link rel="canonical" href="<?= \Altum\Meta::$canonical ?>" />
    <?php endif ?>

    <?php if(\Altum\Meta::$robots): ?>
        <meta name="robots" content="<?= \Altum\Meta::$robots ?>">
    <?php endif ?>

    <link rel="alternate" href="<?= SITE_URL . \Altum\Router::$original_request ?>" hreflang="x-default" />
    <?php if(count(\Altum\Language::$active_languages) > 1): ?>
        <?php foreach(\Altum\Language::$active_languages as $language_name => $language_code): ?>
            <?php if(settings()->main->default_language != $language_name): ?>
                <link rel="alternate" href="<?= SITE_URL . $language_code . '/' . \Altum\Router::$original_request ?>" hreflang="<?= $language_code ?>" />
            <?php endif ?>
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
</head>

<body class="<?= l('direction') == 'rtl' ? 'rtl' : null ?> bg-gray-50 <?= in_array(\Altum\Router::$controller_key, ['login', 'register']) ? \Altum\Router::$controller_key . '-background' : null ?> <?= \Altum\ThemeStyle::get() == 'dark' ? 'cc--darkmode' : null ?>" data-theme-style="<?= \Altum\ThemeStyle::get() ?>">
<?php if(!empty(settings()->custom->body_content)): ?>
    <?= settings()->custom->body_content ?>
<?php endif ?>

<?php //ALTUMCODE:DEMO if(DEMO) echo include_view(THEME_PATH . 'views/partials/ac_banner.php', ['demo_url' => 'https://66biolinks.com/demo/', 'product_name' => PRODUCT_NAME, 'product_url' => PRODUCT_URL, 'product_buy_url' => PRODUCT_BUY_URL]) ?>

<?php require THEME_PATH . 'views/partials/announcements.php' ?>
<?php require THEME_PATH . 'views/partials/cookie_consent.php' ?>
<?php if(settings()->main->admin_spotlight_is_enabled || settings()->main->user_spotlight_is_enabled) require THEME_PATH . 'views/partials/spotlight.php' ?>

<main class="altum-animate altum-animate-fill-none altum-animate-fade-in py-6">
    <div class="container">
        <div class="d-flex flex-column align-items-center">
            <div class="col-xs-12 col-md-10 col-lg-7 col-xl-6">

                <!-- Custom code: FC-2026-02-25: register logo wrapper -->
                <div class="mb-5 text-center register-logo-wrap">
                    <a href="<?= url() ?>" class="text-decoration-none text-dark">
                        <?php if(settings()->main->{'logo_' . \Altum\ThemeStyle::get()} != ''): ?>
                            <img src="<?= settings()->main->{'logo_' . \Altum\ThemeStyle::get() . '_full_url'} ?>" class="img-fluid navbar-logo" alt="<?= l('global.accessibility.logo_alt') ?>" />
                        <?php else: ?>
                            <span class="h3"><?= settings()->main->title ?></span>
                        <?php endif ?>
                    </a>
                </div>
                <!-- /Custom code: FC-2026-02-25 -->

                <!-- Custom code: FC-2026-02-25: register card hook -->
                <div class="card rounded-2x register-card">
                    <div class="card-body p-5">
                        <?= $this->views['content'] ?>
                    </div>
                </div>
                <!-- /Custom code: FC-2026-02-25 -->

            </div>
        </div>
    </div>
</main>

<?= \Altum\Event::get_content('modals') ?>

<?php require THEME_PATH . 'views/partials/js_global_variables.php' ?>

<?php foreach(['libraries/jquery.slim.min.js', 'libraries/popper.min.js', 'libraries/bootstrap.min.js', 'custom.' . (DEBUG ? null : 'min.') . 'js', 'libraries/fontawesome.min.js', 'libraries/fontawesome-solid.min.js', 'libraries/fontawesome-brands.modified.js'] as $file): ?>
    <script src="<?= ASSETS_FULL_URL ?>js/<?= $file ?>?v=<?= PRODUCT_CODE ?>"></script>
<?php endforeach ?>

<?php /* Custom code: FC-2026-04-10: internal FCC Coach popup on basic pages */ ?>
<?php if(is_logged_in() && fcc_ai_user_has_coach_access($this->user)): ?>
    <?= include_view(THEME_PATH . 'views/l/partials/fcc_chat_extreme_popup.php', [
        'config' => [
            'assistant_type' => 'coach',
            'scope' => 'internal_coach',
            'owner_name' => (string) ($this->user->name ?? ''),
            'language_code' => \Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr',
            'source_context' => 'FCC Coach basic page popup',
            'hide_without_context' => false,
            'dom_id' => 'fcc-coach-chat-extreme-basic',
            'intro_label' => 'FCC Coach',
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
