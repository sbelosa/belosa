<?php defined('ALTUMCODE') || die() ?>
<!DOCTYPE html>
<html lang="<?= \Altum\Language::$code ?>" dir="<?= l('direction') ?>">
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

        <?php if(\Altum\Meta::$link_alternate): ?>
            <link rel="alternate" href="<?= SITE_URL . \Altum\Router::$original_request ?>" hreflang="x-default" />
            <?php if(count(\Altum\Language::$active_languages) > 1): ?>
                <?php foreach(\Altum\Language::$active_languages as $language_name => $language_code): ?>
                    <?php if(settings()->main->default_language != $language_name): ?>
                        <link rel="alternate" href="<?= SITE_URL . $language_code . '/' . \Altum\Router::$original_request ?>" hreflang="<?= $language_code ?>" />
                    <?php endif ?>
                <?php endforeach ?>
            <?php endif ?>
        <?php endif ?>

        <link href="<?= !empty(settings()->main->favicon) ? settings()->main->favicon_full_url : 'data:,' ?>" rel="icon" />

        <?php
        /* Custom code: FC-2026-02-26: CSS cache busting by filemtime */
        $fcc_theme_style_file = \Altum\ThemeStyle::get_file();
        $fcc_theme_style_path = THEME_PATH . 'assets/css/' . $fcc_theme_style_file;
        $fcc_theme_style_version = PRODUCT_CODE . (file_exists($fcc_theme_style_path) ? '.' . filemtime($fcc_theme_style_path) : '');
        /* /Custom code: FC-2026-02-26 */
        ?>
        <link href="<?= ASSETS_FULL_URL . 'css/' . $fcc_theme_style_file . '?v=' . $fcc_theme_style_version ?>" id="css_theme_style" rel="stylesheet" media="screen,print">
        <?php foreach(['custom.' . (DEBUG ? null : 'min.') . 'css'] as $file): ?>
            <?php
            /* Custom code: FC-2026-02-26: cache bust custom css asset */
            $fcc_custom_css_path = THEME_PATH . 'assets/css/' . $file;
            $fcc_custom_css_version = PRODUCT_CODE . (file_exists($fcc_custom_css_path) ? '.' . filemtime($fcc_custom_css_path) : '');
            /* /Custom code: FC-2026-02-26 */
            ?>
            <link href="<?= ASSETS_FULL_URL . 'css/' . $file . '?v=' . $fcc_custom_css_version ?>" rel="stylesheet" media="screen,print">
        <?php endforeach ?>
        <!-- Custom code: FC-2026-02-25: load custom.css overrides -->
        <?php if(!DEBUG): ?>
            <?php
            /* Custom code: FC-2026-02-26: cache bust explicit custom.css override */
            $fcc_custom_css_override_path = THEME_PATH . 'assets/css/custom.css';
            $fcc_custom_css_override_version = PRODUCT_CODE . (file_exists($fcc_custom_css_override_path) ? '.' . filemtime($fcc_custom_css_override_path) : '');
            /* /Custom code: FC-2026-02-26 */
            ?>
            <link href="<?= ASSETS_FULL_URL . 'css/custom.css?v=' . $fcc_custom_css_override_version ?>" rel="stylesheet" media="screen,print">
        <?php endif ?>
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

    <?php
    /* Custom code: FC-2026-02-26: body class for blog background continuity (supports localized routes) */
    $fcc_is_blog_route = preg_match('/^(?:[a-z]{2}\/)?blog(?:\/|$)/i', \Altum\Router::$original_request) === 1;
    /* /Custom code: FC-2026-02-26 */
    ?>
    <body class="index <?= l('direction') == 'rtl' ? 'rtl' : null ?> <?= \Altum\ThemeStyle::get() == 'dark' ? 'cc--darkmode' : null ?> <?= $fcc_is_blog_route ? 'fcc-blog-route' : null ?>" data-theme-style="<?= \Altum\ThemeStyle::get() ?>">
        <?php if(!empty(settings()->custom->body_content)): ?>
            <?= settings()->custom->body_content ?>
        <?php endif ?>

        <?php //ALTUMCODE:DEMO if(DEMO) echo include_view(THEME_PATH . 'views/partials/ac_banner.php', ['demo_url' => 'https://66biolinks.com/demo/', 'product_name' => PRODUCT_NAME, 'product_url' => PRODUCT_URL, 'product_buy_url' => PRODUCT_BUY_URL]) ?>
        <?php require THEME_PATH . 'views/partials/js_welcome.php' ?>
        <?php require THEME_PATH . 'views/partials/admin_impersonate_user.php' ?>
        <?php require THEME_PATH . 'views/partials/team_delegate_access.php' ?>
        <?php require THEME_PATH . 'views/partials/announcements.php' ?>
        <?php require THEME_PATH . 'views/partials/cookie_consent.php' ?>
        <?php require THEME_PATH . 'views/partials/ad_blocker_detector.php' ?>
        <?php if(settings()->main->admin_spotlight_is_enabled || settings()->main->user_spotlight_is_enabled) require THEME_PATH . 'views/partials/spotlight.php' ?>
        <?php if(\Altum\Plugin::is_active('pwa') && settings()->pwa->is_enabled && settings()->pwa->display_install_bar) require \Altum\Plugin::get('pwa')->path . 'views/partials/pwa.php' ?>
        <?php if(\Altum\Plugin::is_active('push-notifications') && settings()->push_notifications->is_enabled) require \Altum\Plugin::get('push-notifications')->path . 'views/partials/push_notifications_js.php' ?>

        <div class="container pt-4">
            <?= $this->views['menu'] ?>
        </div>

        <?php require THEME_PATH . 'views/partials/ads_header.php' ?>

        <main class="altum-animate altum-animate-fill-none altum-animate-fade-in">
            <?= $this->views['content'] ?>
        </main>

        <?php require THEME_PATH . 'views/partials/ads_footer.php' ?>

        <div class="container d-print-none">
            <footer class="footer app-footer">
                <?= $this->views['footer'] ?>
            </footer>
        </div>

        <?= \Altum\Event::get_content('modals') ?>

        <?php require THEME_PATH . 'views/partials/js_global_variables.php' ?>

        <?php foreach(['libraries/jquery.min.js', 'libraries/popper.min.js', 'libraries/bootstrap.min.js', 'custom.' . (DEBUG ? null : 'min.') . 'js'] as $file): ?>
            <script src="<?= ASSETS_FULL_URL ?>js/<?= $file ?>?v=<?= PRODUCT_CODE ?>"></script>
        <?php endforeach ?>

        <?php foreach(['libraries/fontawesome.min.js', 'libraries/fontawesome-solid.min.js', 'libraries/fontawesome-brands.modified.js'] as $file): ?>
            <script src="<?= ASSETS_FULL_URL ?>js/<?= $file ?>?v=<?= PRODUCT_CODE ?>" defer></script>
        <?php endforeach ?>

        <?php /* Custom code: FC-2026-04-10: internal FCC Coach popup on site pages */ ?>
        <?php if(is_logged_in() && fcc_ai_user_has_coach_access($this->user)): ?>
            <?= include_view(THEME_PATH . 'views/l/partials/fcc_chat_extreme_popup.php', [
                'config' => [
                    'assistant_type' => 'coach',
                    'scope' => 'internal_coach',
                    'owner_name' => (string) ($this->user->name ?? ''),
                    'language_code' => \Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr',
                    'source_context' => 'FCC Coach site popup',
                    'hide_without_context' => false,
                    'dom_id' => 'fcc-coach-chat-extreme-site',
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
