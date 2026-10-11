<?php defined('ALTUMCODE') || die() ?>
<!DOCTYPE html>
<html lang="<?= \Altum\Language::$code ?>" dir="<?= l('direction') ?>" class="w-100 h-100">
<head>
    <?php fcc_i18n_assets(); ?>
    <title><?= \Altum\Title::get() ?></title>
    <base href="<?= SITE_URL; ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no<?= fcc_partner_enabled() ? ', viewport-fit=cover' : '' ?>" />

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
<?php if(fcc_partner_enabled()): ?><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner.css') ?>"><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-compat.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner-compat.css') ?>"><?php endif ?>
<?php if(fcc_partner_enabled()): ?><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-premium.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner-premium.css') ?>"><?php endif ?>
<?php if(fcc_partner_enabled()): ?><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-admin.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-partner-admin.css') ?>"><?php endif ?>
</head>

<body data-fcc-page="admin" class="<?= fcc_partner_enabled()?'fcc-partner-mode':'' ?> <?= l('direction') == 'rtl' ? 'rtl' : null ?>" data-theme-style="<?= \Altum\ThemeStyle::get() ?>">
<div id="admin_overlay" class="admin-overlay" style="display: none"></div>
<?php if(settings()->main->admin_spotlight_is_enabled || settings()->main->user_spotlight_is_enabled) require THEME_PATH . 'views/partials/spotlight.php' ?>

<div class="admin-container <?= fcc_partner_enabled()?'app-container':'' ?>">
    <?php if(fcc_partner_enabled()) require THEME_PATH.'views/partner/navigation.php'; else echo $this->views['admin_sidebar']; ?>

    <section class="admin-content <?= fcc_partner_enabled()?'app-content':'' ?> altum-animate altum-animate-fill-none altum-animate-fade-in">
        <?php if(fcc_partner_enabled()) require THEME_PATH.'views/partner/header.php'; else echo $this->views['admin_menu']; ?>

        <div class="p-3 p-lg-5 position-relative">
            <?php if(fcc_partner_enabled()): ?><nav class="fp-admin-bar" aria-label="<?= htmlspecialchars(fcc_t('Administracija'), ENT_QUOTES, 'UTF-8') ?>"><a href="<?= url('partner/admin') ?>"><?= htmlspecialchars(fcc_t('← Pregled sustava'), ENT_QUOTES, 'UTF-8') ?></a><a href="<?= url('admin/users') ?>"><?= htmlspecialchars(fcc_t('Korisnici'), ENT_QUOTES, 'UTF-8') ?></a><a href="<?= url('admin/fcc-access') ?>"><?= htmlspecialchars(fcc_t('Pristup novom FCC-u'), ENT_QUOTES, 'UTF-8') ?></a><a href="<?= url('admin/payments') ?>"><?= htmlspecialchars(fcc_t('Naplata'), ENT_QUOTES, 'UTF-8') ?></a><a href="<?= url('admin/leader-operating-system') ?>"><?= htmlspecialchars(fcc_t('LOS i tim'), ENT_QUOTES, 'UTF-8') ?></a><a href="<?= url('admin/blog-posts') ?>"><?= htmlspecialchars(fcc_t('Sadržaj'), ENT_QUOTES, 'UTF-8') ?></a><?php if(fcc_events_enabled()): ?><a href="<?= url('admin/webinars') ?>"><?= htmlspecialchars(fcc_t('Webinari i događaji'), ENT_QUOTES, 'UTF-8') ?></a><?php endif ?><?php if(fcc_business_enabled((int)$this->user->user_id)): ?><a href="<?= url('admin/marketing-plan') ?>"><?= htmlspecialchars(fcc_t('Marketing plan'), ENT_QUOTES, 'UTF-8') ?></a><?php endif ?></nav><details class="fp-admin-toolbox"><summary><?= htmlspecialchars(fcc_t('Svi administratorski alati'), ENT_QUOTES, 'UTF-8') ?></summary><div><?= $this->views['admin_sidebar'] ?></div></details><?php else: ?><?= include_view(THEME_PATH . 'views/admin/partials/admin_support_bar.php') ?><?php endif ?>
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
    <script src="<?= ASSETS_FULL_URL ?>js/<?= $file ?>?v=<?= PRODUCT_CODE ?><?= strpos($file, 'admin_custom.') === 0 ? '&amp;rev=' . filemtime(ASSETS_PATH . 'js/' . $file) : '' ?>" defer></script>
<?php endforeach ?>

<?php /* Custom code: FC-2026-04-10: internal FCC Coach popup on admin pages */ ?>
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
            'source_context' => fcc_t('FCC Coach admin popup'),
            'hide_without_context' => false,
            'dom_id' => 'fcc-coach-chat-extreme-admin',
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

<?php if(fcc_partner_enabled()): ?><script defer src="<?= ASSETS_FULL_URL ?>js/fcc-partner-install.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner-install.js') ?>"></script><?php endif ?>
<?php if(fcc_partner_enabled()): ?><script defer src="<?= ASSETS_FULL_URL ?>js/fcc-partner.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner.js') ?>"></script><?php endif ?>
</body>
</html>
