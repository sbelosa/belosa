<?php defined('ALTUMCODE') || die();
if(fcc_partner_enabled()) \Altum\ThemeStyle::$workspace_light = true;
$fp_tool_context = fcc_partner_enabled() ? fcc_partner_tool_context(\Altum\Router::$controller_key, \Altum\Router::$path === 'admin') : null;
// The card editor already has its own four focused views.
if(\Altum\Router::$controller_key === 'link' && ($this->link->type ?? null) === 'biolink') $fp_tool_context = null;
?>
<!DOCTYPE html>
<html lang="<?= \Altum\Language::$code ?>" dir="<?= l('direction') ?>">
<head>
    <?php if(fcc_partner_enabled()): ?>
    <meta name="theme-color" content="#155e51"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="<?= SITE_URL ?>partner-assets/manifest.php"><link rel="apple-touch-icon" href="<?= SITE_URL ?>partner-assets/fcc-app-192.png">
    <?php header('Cache-Control: no-store, private'); endif ?>
    <title><?= \Altum\Title::get() ?></title>
    <base href="<?= SITE_URL; ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <?php if(!fcc_partner_enabled() && \Altum\Plugin::is_active('pwa') && settings()->pwa->is_enabled): ?>
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
            <?php if(settings()->main->default_language != $language_name): ?>
                <link rel="alternate" href="<?= SITE_URL . $language_code . '/' . \Altum\Router::$original_request ?>" hreflang="<?= $language_code ?>" />
            <?php endif ?>
        <?php endforeach ?>
    <?php endif ?>

    <link href="<?= !empty(settings()->main->favicon) ? settings()->main->favicon_full_url : 'data:,' ?>" rel="icon" />

    <link href="<?= ASSETS_FULL_URL . 'css/' . \Altum\ThemeStyle::get_file() . '?v=' . PRODUCT_CODE ?>" id="css_theme_style" rel="stylesheet" media="screen,print">
    <?php /* Custom code: FC-2026-02-27: cache-bust custom css for biolink editor visual updates */ ?>
    <?php foreach(['custom.' . (DEBUG ? null : 'min.') . 'css', 'libraries/select2.css'] as $file): ?>
        <?php $fcce = strpos($file, 'custom.') === 0 ? '&fcce=20260227b' : null; ?>
        <link href="<?= ASSETS_FULL_URL . 'css/' . $file . '?v=' . PRODUCT_CODE . $fcce ?>" rel="stylesheet" media="screen,print">
    <?php endforeach ?>
    <?php /* /Custom code: FC-2026-02-27 */ ?>

    <!-- Custom code: FC-2026-02-24: help widget assets -->
    <link href="<?= ASSETS_FULL_URL ?>css/help-widget.css?v=<?= PRODUCT_CODE ?>&fcce=20260405v1" rel="stylesheet" media="screen,print">
    <!-- /Custom code: FC-2026-02-24 -->

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
<?php if(fcc_partner_enabled() && \Altum\Router::$controller_key === 'blog'): ?><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/custom.css?v=<?= filemtime(ASSETS_PATH.'css/custom.css') ?>"><?php endif ?>
<?php if(fcc_partner_enabled()): ?><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner.css') ?>"><?php endif ?>
<?php if(fcc_partner_enabled()): ?><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-compat.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner-compat.css') ?>"><?php endif ?>
<?php if(fcc_partner_enabled()): ?><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-premium.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner-premium.css') ?>"><?php endif ?>
<?php if(fcc_partner_enabled() && \Altum\Router::$controller_key === 'blog'): ?><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-library.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner-library.css') ?>"><script defer src="<?= ASSETS_FULL_URL ?>js/fcc-partner-library.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner-library.js') ?>"></script><?php endif ?>
<?php if(fcc_partner_enabled()): ?><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-tools.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner-tools.css') ?>"><?php endif ?>
    <link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-pro.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-pro.css') ?>">
</head>

<body data-fcc-pro-active="<?= fcc_pro_has_access((int)$this->user->user_id) ? '1' : '0' ?>" data-fcc-page="<?= htmlspecialchars(\Altum\Router::$path==='admin'?'admin':\Altum\Router::$controller_key,ENT_QUOTES) ?>" class="<?= $fp_tool_context ? 'fp-tool-page' : '' ?> <?= fcc_partner_enabled() && \Altum\Router::$controller_key === 'blog' ? 'fcc-library-mode' : '' ?> <?= l('direction') == 'rtl' ? 'rtl' : null ?> app <?= fcc_partner_enabled() ? 'fcc-partner-mode' : '' ?> <?= \Altum\ThemeStyle::get() == 'dark' ? 'cc--darkmode' : null ?>" data-theme-style="<?= \Altum\ThemeStyle::get() ?>">
    <?php if(!empty(settings()->custom->body_content)): ?>
        <?= settings()->custom->body_content ?>
    <?php endif ?>

    <?php //ALTUMCODE:DEMO if(DEMO) echo include_view(THEME_PATH . 'views/partials/ac_banner.php', ['demo_url' => 'https://66biolinks.com/demo/', 'product_name' => PRODUCT_NAME, 'product_url' => PRODUCT_URL, 'product_buy_url' => PRODUCT_BUY_URL]) ?>
    <?php if(settings()->main->admin_spotlight_is_enabled || settings()->main->user_spotlight_is_enabled) require THEME_PATH . 'views/partials/spotlight.php' ?>
    <?php if(!fcc_partner_enabled() && \Altum\Plugin::is_active('pwa') && settings()->pwa->is_enabled && settings()->pwa->display_install_bar) require \Altum\Plugin::get('pwa')->path . 'views/partials/pwa.php' ?>

    <div id="app_overlay" class="app-overlay" style="display: none"></div>

    <div class="app-container">
        <?= $this->views['app_sidebar'] ?>

        <section class="app-content">
            <?php require THEME_PATH . 'views/partials/js_welcome.php' ?>
            <?php require THEME_PATH . 'views/partials/admin_impersonate_user.php' ?>
            <?php require THEME_PATH . 'views/partials/team_delegate_access.php' ?>
            <?php require THEME_PATH . 'views/partials/announcements.php' ?>
            <?php require THEME_PATH . 'views/partials/cookie_consent.php' ?>
            <?php require THEME_PATH . 'views/partials/ad_blocker_detector.php' ?>
            <?php if(\Altum\Plugin::is_active('push-notifications') && settings()->push_notifications->is_enabled) require \Altum\Plugin::get('push-notifications')->path . 'views/partials/push_notifications_js.php' ?>

            <div class="container <?= fcc_partner_enabled() ? 'fp-topbar-host' : '' ?>">
                <?= $this->views['app_menu'] ?>
            </div>

            <div class="py-4 p-lg-5">
                <?php require THEME_PATH . 'views/partials/ads_header.php' ?>

                <?php if(is_logged_in() && function_exists('vip_funnel_demo_get_global_banner_payload')): ?>
                    <?php
                    $vip_demo_global_banner = null;
                    try {
                        $vip_demo_global_banner = vip_funnel_demo_get_global_banner_payload($this->user, \Altum\Router::$controller_key ?? '');
                    } catch(\Throwable $exception) {
                        error_log('vip_demo_global_banner_failed: ' . $exception->getMessage());
                        $vip_demo_global_banner = null;
                    }
                    ?>
                    <?php if($vip_demo_global_banner): ?>
                        <div class="container mb-4">
                            <?= include_view(THEME_PATH . 'views/partials/vip_demo_global_banner.php', [
                                'banner' => $vip_demo_global_banner,
                            ]) ?>
                        </div>
                    <?php endif ?>
                <?php endif ?>

                <main class="altum-animate altum-animate-fill-none altum-animate-fade-in">
                    <?php if($fp_tool_context) require THEME_PATH . 'views/partner/tool-context.php'; ?>
                    <?php if(fcc_partner_enabled()&&function_exists('fcc_journey_team_group_joined')&&fcc_jp_schema())require THEME_PATH.'views/partner/journey-team-group.php'; ?>
                    <?php if(fcc_partner_enabled())require THEME_PATH.'views/partner/device-notifications.php'; ?>
                    <?= $this->views['content'] ?>
                </main>

                <?php require THEME_PATH . 'views/partials/ads_footer.php' ?>
            </div>

            <div class="px-lg-5">
                <div class="container d-print-none">
                    <footer class="footer app-footer">
                        <?= $this->views['footer'] ?>
                    </footer>
                </div>
            </div>
        </section>
    </div>

    <?php require THEME_PATH.'views/partner/pro-upgrade.php'; ?>
    <script defer src="<?= ASSETS_FULL_URL ?>js/fcc-pro.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-pro.js') ?>"></script>
    <?= \Altum\Event::get_content('modals') ?>

    <?php require THEME_PATH . 'views/partials/js_global_variables.php' ?>

    <?php foreach(['libraries/jquery.min.js', 'libraries/popper.min.js', 'libraries/bootstrap.min.js', 'custom.' . (DEBUG ? null : 'min.') . 'js', 'libraries/select2.min.js'] as $file): ?>
        <script src="<?= ASSETS_FULL_URL ?>js/<?= $file ?>?v=<?= PRODUCT_CODE ?>"></script>
    <?php endforeach ?>

    <?php foreach(['libraries/fontawesome.min.js', 'libraries/fontawesome-solid.min.js', 'libraries/fontawesome-brands.min.js'] as $file): ?>
        <script src="<?= ASSETS_FULL_URL ?>js/<?= $file ?>?v=<?= PRODUCT_CODE ?>" defer></script>
    <?php endforeach ?>

    <!-- Custom code: FC-2026-02-24: help widget config injection -->
    <?php
    $help_widget_config = [];
    $current_language = \Altum\Language::$name;
    $default_language = \Altum\Language::$default_name;
    if(isset(settings()->fcc_education)) {
        $items_by_language = settings()->fcc_education->help_widget_items_by_language ?? [];
        $items_by_language = is_array($items_by_language) || is_object($items_by_language) ? (array) $items_by_language : [];
        if(isset($items_by_language[$current_language])) {
            $help_widget_config = $items_by_language[$current_language];
        } elseif(isset($items_by_language[$default_language])) {
            $help_widget_config = $items_by_language[$default_language];
        }
    } elseif(!empty(settings()->custom->help_widget_items)) {
        $help_widget_config = settings()->custom->help_widget_items;
    }
    ?>
    <script>
        window.FCC_HELP_CONFIG = <?= json_encode($help_widget_config) ?>;
        window.FCC_HELP_TOOLTIP = <?= json_encode(l('fcc.help_tooltip')) ?>;
        window.FCC_HELP_CLOSE = <?= json_encode(l('fcc.help_close')) ?>;
    </script>
    <!-- /Custom code: FC-2026-02-24 -->

    <!-- Custom code: FC-2026-02-24: help widget assets -->
    <script defer src="<?= ASSETS_FULL_URL ?>js/help-widget.js?v=<?= PRODUCT_CODE ?>&fcce=20260405v1"></script>
    <!-- /Custom code: FC-2026-02-24 -->

    <?php /* Custom code: FC-2026-04-10: internal FCC Coach popup */ ?>
    <?php if(is_logged_in() && fcc_ai_user_has_coach_access($this->user) && !(fcc_partner_enabled() && \Altum\Router::$controller_key === 'blog')): ?>
        <?php $fcc_internal_coach_ui = fcc_ai_get_internal_coach_ui_payload($this->user, \Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr'); ?>
        <?php $fcc_internal_coach_ui_copy = []; ?>
        <?php foreach(['hr', 'en', 'sl', 'bg'] as $fcc_internal_coach_ui_language): ?>
            <?php $fcc_internal_coach_ui_copy[$fcc_internal_coach_ui_language] = fcc_ai_get_internal_coach_ui_payload($this->user, $fcc_internal_coach_ui_language); ?>
        <?php endforeach ?>
        <?= include_view(THEME_PATH . 'views/l/partials/fcc_chat_extreme_popup.php', [
            'config' => [
                'assistant_type' => 'coach',
                'scope' => 'internal_coach',
                'owner_name' => (string) ($this->user->name ?? ''),
                'language_code' => \Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr',
                'source_context' => 'FCC Coach popup',
                'hide_without_context' => false,
                'dom_id' => 'fcc-coach-chat-extreme',
                'assistant_title' => (string) ($fcc_internal_coach_ui['assistant_title'] ?? 'FCC Coach'),
                'intro_label' => (string) ($fcc_internal_coach_ui['intro_label'] ?? 'FCC Coach'),
                'launcher_label' => (string) ($fcc_internal_coach_ui['launcher_label'] ?? 'FCC Coach'),
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

    <script>
    'use strict';

        let toggle_app_sidebar = () => {
            /* Open sidebar menu */
            let body = document.querySelector('body');
            body.classList.toggle('app-sidebar-opened');

            /* Toggle overlay */
            let app_overlay = document.querySelector('#app_overlay');
            app_overlay.style.display == 'none' ? app_overlay.style.display = 'block' : app_overlay.style.display = 'none';

            /* Change toggle button content */
            let button = document.querySelector('#app_menu_toggler');

            if(body.classList.contains('app-sidebar-opened')) {
                button.innerHTML = `<i class="fas fa-fw fa-times"></i>`;
            } else {
                button.innerHTML = `<i class="fas fa-fw fa-bars"></i>`;
            }
        };

        /* Toggler for the sidebar */
        document.querySelector('#app_menu_toggler').addEventListener('click', event => {
            event.preventDefault();

            toggle_app_sidebar();

            let app_sidebar_is_opened = document.querySelector('body').classList.contains('app-sidebar-opened');

            if(app_sidebar_is_opened) {
                document.querySelector('#app_overlay').removeEventListener('click', toggle_app_sidebar);
                document.querySelector('#app_overlay').addEventListener('click', toggle_app_sidebar);
            } else {
                document.querySelector('#app_overlay').removeEventListener('click', toggle_app_sidebar);
            }
        });

        /* Custom select implementation */
        $('select:not([multiple="multiple"]):not([class="input-group-text"]):not([class="custom-select custom-select-sm"]):not([class^="ql"]):not([data-is-not-custom-select])').each(function() {
            let $select = $(this);
            $select.select2({
                placeholder: <?= json_encode(l('global.no_data')) ?>,
                    dir: <?= json_encode(l('direction')) ?>,
                minimumResultsForSearch: 5,
            });

            /* Make sure to trigger the select when the label is clicked as well */
            let selectId = $select.attr('id');
            if(selectId) {
                $('label[for="' + selectId + '"]').on('click', function(event) {
                    event.preventDefault();
                    $select.select2('open');
                });
            }
        });
    </script>
<?php if(fcc_partner_enabled()): ?><script defer src="<?= ASSETS_FULL_URL ?>js/fcc-partner-install.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner-install.js') ?>"></script><?php endif ?>
<?php if(fcc_partner_enabled()): ?><script defer src="<?= ASSETS_FULL_URL ?>js/fcc-partner.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner.js') ?>"></script><?php endif ?>
<?php if(fcc_partner_enabled()): ?><script defer src="<?= ASSETS_FULL_URL ?>js/fcc-partner-editor.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner-editor.js') ?>"></script><?php endif ?>
<?php if(fcc_partner_enabled()): ?><script defer src="<?= ASSETS_FULL_URL ?>js/fcc-partner-tools.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner-tools.js') ?>"></script><?php endif ?>
</body>
</html>
