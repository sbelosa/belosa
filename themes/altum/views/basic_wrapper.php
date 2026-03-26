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

<?php /* Custom code: FC-2026-02-27: logged-in Zapier chatbot embed on basic pages */ ?>
<?php if(is_logged_in()): ?>
    <style>
        .fcc-zapier-shell {
            position: fixed;
            right: 1rem;
            bottom: 5.75rem;
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
            display: flex;
            flex-direction: column;
        }

        .fcc-zapier-shell zapier-interfaces-chatbot-embed {
            display: block;
            width: 100%;
            height: 100%;
            flex: 1 1 auto;
            min-height: 0;
        }

        .fcc-zapier-close-footer {
            display: none;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: .75rem 1rem calc(.75rem + env(safe-area-inset-bottom, 0px));
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, .15);
            background: #0f1726;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            flex: 0 0 auto;
        }

        .fcc-zapier-toggle {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 2147482001;
            border: 0;
            border-radius: 999px;
            padding: .85rem 1.1rem;
            font-weight: 600;
            color: #fff;
            background: #1f6feb;
            box-shadow: 0 .5rem 1.2rem rgba(0, 0, 0, .28);
            cursor: pointer;
        }

        @media (max-width: 576px) {
            .fcc-zapier-shell {
                right: 0;
                left: 0;
                top: 0;
                bottom: 0;
                width: 100vw;
                max-width: 100vw;
                height: 100dvh;
                max-height: 100dvh;
                border-radius: 0;
                box-shadow: none;
                overscroll-behavior: contain;
                touch-action: pan-y;
            }

            .fcc-zapier-close-footer {
                display: flex;
            }

            .fcc-zapier-toggle {
                display: inline-flex;
            }

            body.fcc-chatbot-mobile-open {
                overflow: hidden;
                height: 100dvh;
            }
        }
    </style>
    <script async type='module' src='https://interfaces.zapier.com/assets/web-components/zapier-interfaces/zapier-interfaces.esm.js'></script>
    <div id='fcc-zapier-shell' class='fcc-zapier-shell' aria-hidden='true'>
        <zapier-interfaces-chatbot-embed is-popup='false' chatbot-id='cm8qedep5004ulsd3hioaty93'></zapier-interfaces-chatbot-embed>
        <button type='button' id='fcc-zapier-close-footer' class='fcc-zapier-close-footer' aria-label='Zatvori chat'>Zatvori chat</button>
    </div>
    <button type='button' id='fcc-zapier-toggle' class='fcc-zapier-toggle' aria-controls='fcc-zapier-shell' aria-expanded='false'>Chat</button>
    <script>
        (() => {
            const shell = document.getElementById('fcc-zapier-shell');
            const toggle = document.getElementById('fcc-zapier-toggle');
            const closeFooter = document.getElementById('fcc-zapier-close-footer');
            const mobileMediaQuery = window.matchMedia('(max-width: 576px)');

            if(!shell || !toggle) return;

            document.body.classList.add('fcc-chatbot-present');

            const setOpen = isOpen => {
                shell.classList.toggle('is-open', isOpen);
                shell.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                toggle.textContent = isOpen ? 'Zatvori chat' : 'Chat';

                if(mobileMediaQuery.matches) {
                    document.body.classList.toggle('fcc-chatbot-mobile-open', isOpen);
                } else {
                    document.body.classList.remove('fcc-chatbot-mobile-open');
                }
            };

            const handleViewportChange = () => {
                if(!mobileMediaQuery.matches) {
                    document.body.classList.remove('fcc-chatbot-mobile-open');
                } else if(shell.classList.contains('is-open')) {
                    document.body.classList.add('fcc-chatbot-mobile-open');
                }
            };

            toggle.addEventListener('click', () => setOpen(!shell.classList.contains('is-open')));
            closeFooter?.addEventListener('click', () => setOpen(false));
            document.addEventListener('keydown', event => {
                if(event.key === 'Escape') setOpen(false);
            });

            if(mobileMediaQuery.addEventListener) {
                mobileMediaQuery.addEventListener('change', handleViewportChange);
            } else if(mobileMediaQuery.addListener) {
                mobileMediaQuery.addListener(handleViewportChange);
            }
        })();
    </script>
<?php endif ?>
<?php /* /Custom code: FC-2026-02-27 */ ?>

<?= \Altum\Event::get_content('javascript') ?>
</body>
</html>
