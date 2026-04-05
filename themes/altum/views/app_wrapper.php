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

<body class="<?= l('direction') == 'rtl' ? 'rtl' : null ?> app <?= \Altum\ThemeStyle::get() == 'dark' ? 'cc--darkmode' : null ?>" data-theme-style="<?= \Altum\ThemeStyle::get() ?>">
    <?php if(!empty(settings()->custom->body_content)): ?>
        <?= settings()->custom->body_content ?>
    <?php endif ?>

    <?php //ALTUMCODE:DEMO if(DEMO) echo include_view(THEME_PATH . 'views/partials/ac_banner.php', ['demo_url' => 'https://66biolinks.com/demo/', 'product_name' => PRODUCT_NAME, 'product_url' => PRODUCT_URL, 'product_buy_url' => PRODUCT_BUY_URL]) ?>
    <?php if(settings()->main->admin_spotlight_is_enabled || settings()->main->user_spotlight_is_enabled) require THEME_PATH . 'views/partials/spotlight.php' ?>
    <?php if(\Altum\Plugin::is_active('pwa') && settings()->pwa->is_enabled && settings()->pwa->display_install_bar) require \Altum\Plugin::get('pwa')->path . 'views/partials/pwa.php' ?>

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

            <div class="container">
                <?= $this->views['app_menu'] ?>
            </div>

            <div class="py-4 p-lg-5">
                <?php require THEME_PATH . 'views/partials/ads_header.php' ?>

                <main class="altum-animate altum-animate-fill-none altum-animate-fade-in">
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

    <?php /* Custom code: FC-2026-02-27: logged-in Zapier chatbot embed in user zone */ ?>
    <?php if(is_logged_in()): ?>
        <style>
            .fcc-zapier-shell {
                position: fixed;
                right: 1rem;
                bottom: 4.75rem;
                width: min(380px, calc(100vw - 1.5rem));
                height: min(680px, calc(100vh - 7.5rem));
                background: #05070c;
                border-radius: 1rem;
                overflow: hidden;
                box-shadow: 0 1rem 2.5rem rgba(0, 0, 0, .35);
                z-index: 2147482000;
                display: none;
            }

            .fcc-zapier-shell.is-open {
                display: block;
            }

            .fcc-zapier-shell zapier-interfaces-chatbot-embed {
                display: block;
                width: 100%;
                height: 100%;
            }

            .fcc-zapier-toggle {
                position: fixed;
                right: 1rem;
                bottom: 1rem;
                z-index: 2147482001;
                border: 0;
                border-radius: 999px;
                padding: .75rem 1rem;
                font-weight: 600;
                color: #e7e9ee;
                background: linear-gradient(180deg, rgba(33, 41, 52, 0.96), rgba(19, 24, 32, 0.96));
                box-shadow: 0 10px 22px rgba(0, 0, 0, .34);
                cursor: pointer;
            }

            .fcc-assist-dock .fcc-zapier-toggle {
                position: static;
                right: auto;
                bottom: auto;
                z-index: auto;
                border-radius: 0;
                padding: 0 .95rem;
                color: var(--fcc-help-text);
                background: transparent;
                box-shadow: none;
            }

            @media (max-width: 576px) {
                .fcc-zapier-shell {
                    right: .75rem;
                    left: .75rem;
                    width: auto;
                    bottom: 4.9rem;
                    height: min(75vh, 640px);
                }

                .fcc-zapier-toggle {
                    right: .75rem;
                    bottom: .75rem;
                }
            }
        </style>
        <script async type='module' src='https://interfaces.zapier.com/assets/web-components/zapier-interfaces/zapier-interfaces.esm.js'></script>
        <div id='fcc-zapier-shell' class='fcc-zapier-shell' aria-hidden='true'>
            <zapier-interfaces-chatbot-embed is-popup='false' chatbot-id='cm8qedep5004ulsd3hioaty93'></zapier-interfaces-chatbot-embed>
        </div>
        <button type='button' id='fcc-zapier-toggle' class='fcc-zapier-toggle' aria-controls='fcc-zapier-shell' aria-expanded='false'>Ai Savjetnik</button>
        <script>
            (() => {
                const shell = document.getElementById('fcc-zapier-shell');
                const toggle = document.getElementById('fcc-zapier-toggle');

                if(!shell || !toggle) return;

                const isTutorialActive = () => document.body.classList.contains('fcc-tour-mode');

                const setOpen = isOpen => {
                    if(isOpen && isTutorialActive()) {
                        return;
                    }

                    shell.classList.toggle('is-open', isOpen);
                    shell.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                    toggle.textContent = isOpen ? 'Zatvori AI' : 'Ai Savjetnik';
                };

                toggle.addEventListener('click', () => setOpen(!shell.classList.contains('is-open')));
                document.addEventListener('keydown', event => {
                    if(event.key === 'Escape') setOpen(false);
                });

                window.addEventListener('fcc:tutorial:state', event => {
                    if(event && event.detail && event.detail.active) {
                        setOpen(false);
                    }
                });
            })();
        </script>
    <?php endif ?>
    <?php /* /Custom code: FC-2026-02-27 */ ?>

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
</body>
</html>
