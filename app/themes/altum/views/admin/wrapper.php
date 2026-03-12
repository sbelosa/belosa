<?php defined('ALTUMCODE') || die() ?>
<!DOCTYPE html>
<html lang="<?= \Altum\Language::$code ?>" dir="<?= l('direction') ?>" class="w-100 h-100">
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

    <link rel="alternate" href="<?= SITE_URL . \Altum\Router::$original_request ?>" hreflang="x-default" />
    <?php if(count(\Altum\Language::$active_languages) > 1): ?>
        <?php foreach(\Altum\Language::$active_languages as $language_name => $language_code): ?>
            <?php if(settings()->main->default_language != $language_name): ?>
                <link rel="alternate" href="<?= SITE_URL . $language_code . '/' . \Altum\Router::$original_request ?>" hreflang="<?= $language_code ?>" />
            <?php endif ?>
        <?php endforeach ?>
    <?php endif ?>

    <link href="<?= !empty(settings()->main->favicon) ? settings()->main->favicon_full_url : 'data:,' ?>" rel="icon" />

    <link href="<?= ASSETS_FULL_URL . 'css/admin-' . \Altum\ThemeStyle::get_file() . '?v=' . PRODUCT_CODE ?>" id="css_theme_style" rel="stylesheet" media="screen,print">
    <?php foreach(['admin-custom.' . (DEBUG ? null : 'min.') . 'css', 'libraries/select2.' . (DEBUG ? null : 'min.') . 'css'] as $file): ?>
        <link href="<?= ASSETS_FULL_URL ?>css/<?= $file ?>?v=<?= PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
    <?php endforeach ?>

    <?= \Altum\Event::get_content('head') ?>

        <?php if(is_logged_in() && !user()->plan_settings->export->pdf): ?>
            <style>@media print { body { display: none; } }</style>
        <?php endif ?>
</head>

<body class="<?= l('direction') == 'rtl' ? 'rtl' : null ?>" data-theme-style="<?= \Altum\ThemeStyle::get() ?>">
<div id="admin_overlay" class="admin-overlay" style="display: none"></div>
<?php if(settings()->main->admin_spotlight_is_enabled || settings()->main->user_spotlight_is_enabled) require THEME_PATH . 'views/partials/spotlight.php' ?>

<div class="admin-container">
    <?= $this->views['admin_sidebar'] ?>

    <section class="admin-content altum-animate altum-animate-fill-none altum-animate-fade-in">
        <?= $this->views['admin_menu'] ?>

        <div class="p-3 p-lg-5 position-relative">
            <?= include_view(THEME_PATH . 'views/admin/partials/admin_version_updates_bar.php') ?>
            <?= include_view(THEME_PATH . 'views/admin/partials/admin_support_bar.php') ?>
            <?= include_view(THEME_PATH . 'views/admin/partials/admin_smtp_setup.php') ?>

            <?= $this->views['content'] ?>

            <div class="card mt-4 d-print-none">
                <div class="card-body">
                    <?= $this->views['footer'] ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?= \Altum\Event::get_content('modals') ?>

<?php require THEME_PATH . 'views/partials/js_global_variables.php' ?>

<?php foreach(['libraries/jquery.slim.min.js', 'libraries/popper.min.js', 'libraries/bootstrap.min.js', 'custom.' . (DEBUG ? null : 'min.') . 'js'] as $file): ?>
    <script src="<?= ASSETS_FULL_URL ?>js/<?= $file ?>?v=<?= PRODUCT_CODE ?>"></script>
<?php endforeach ?>

<?php foreach(['libraries/select2.min.js', 'admin_custom.' . (DEBUG ? null : 'min.') . 'js', 'libraries/fontawesome.min.js', 'libraries/fontawesome-solid.min.js', 'libraries/fontawesome-brands.min.js',] as $file): ?>
    <script src="<?= ASSETS_FULL_URL ?>js/<?= $file ?>?v=<?= PRODUCT_CODE ?>" defer></script>
<?php endforeach ?>

<?php /* Custom code: FC-2026-02-27: logged-in Zapier chatbot embed on admin pages */ ?>
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
            padding: .85rem 1.1rem;
            font-weight: 600;
            color: #fff;
            background: #1f6feb;
            box-shadow: 0 .5rem 1.2rem rgba(0, 0, 0, .28);
            cursor: pointer;
        }

        @media (max-width: 576px) {
            .fcc-zapier-shell {
                right: .75rem;
                left: .75rem;
                width: auto;
                bottom: 5.5rem;
                height: min(75vh, 640px);
            }
        }
    </style>
    <script async type='module' src='https://interfaces.zapier.com/assets/web-components/zapier-interfaces/zapier-interfaces.esm.js'></script>
    <div id='fcc-zapier-shell' class='fcc-zapier-shell' aria-hidden='true'>
        <zapier-interfaces-chatbot-embed is-popup='false' chatbot-id='cm8qedep5004ulsd3hioaty93'></zapier-interfaces-chatbot-embed>
    </div>
    <button type='button' id='fcc-zapier-toggle' class='fcc-zapier-toggle' aria-controls='fcc-zapier-shell' aria-expanded='false'>Chat</button>
    <script>
        (() => {
            const shell = document.getElementById('fcc-zapier-shell');
            const toggle = document.getElementById('fcc-zapier-toggle');

            if(!shell || !toggle) return;

            document.body.classList.add('fcc-chatbot-present');

            const setOpen = isOpen => {
                shell.classList.toggle('is-open', isOpen);
                shell.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                toggle.textContent = isOpen ? 'Zatvori chat' : 'Chat';
            };

            toggle.addEventListener('click', () => setOpen(!shell.classList.contains('is-open')));
            document.addEventListener('keydown', event => {
                if(event.key === 'Escape') setOpen(false);
            });
        })();
    </script>
<?php endif ?>
<?php /* /Custom code: FC-2026-02-27 */ ?>

<?= \Altum\Event::get_content('javascript') ?>

</body>
</html>
