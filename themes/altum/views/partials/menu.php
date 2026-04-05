<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-02-26: localized fixed menu labels */
$fcc_is_hr_language = \Altum\Language::$code === 'hr';
$fcc_menu_fcc_label = l('menu.forever_club');
$fcc_menu_products_label = l('menu.forever_products');
$fcc_menu_blog_label = 'Blog';
$fcc_menu_contact_label = l('menu.contact');
$fcc_menu_dashboard_label = l('menu.dashboard');
$fcc_products_category_url = fc_get_forever_products_blog_category_url();

$fcc_share_is_visible = false;
$fcc_share_url = null;
$fcc_share_route = \Altum\Router::$controller_key ?? null;

if(is_logged_in() && in_array($fcc_share_route, ['index', 'blog', 'page'], true)) {
    $fcc_user_id = \Altum\Authentication::check();
    $fcc_referral_slug = null;

    if($fcc_user_id) {
        $fcc_main_biolink_id = fc_get_user_main_biolink_id($fcc_user_id);

        if($fcc_main_biolink_id) {
            $fcc_biolink = db()->where('link_id', $fcc_main_biolink_id)->where('type', 'biolink')->getOne('links', ['url']);

            if($fcc_biolink && !empty($fcc_biolink->url)) {
                $fcc_referral_slug = $fcc_biolink->url;
            }
        }

        if(!$fcc_referral_slug) {
            $fcc_biolink = db()->where('user_id', $fcc_user_id)->where('type', 'biolink')->orderBy('link_id', 'ASC')->getOne('links', ['url']);

            if($fcc_biolink && !empty($fcc_biolink->url)) {
                $fcc_referral_slug = $fcc_biolink->url;
            }
        }
    }

    $fcc_current_path = \Altum\Router::$original_request ?? '';
    $fcc_share_url = url($fcc_current_path);

    if($fcc_referral_slug) {
        $fcc_share_url .= (parse_url($fcc_share_url, PHP_URL_QUERY) ? '&' : '?') . http_build_query(['ref' => $fcc_referral_slug]);
        $fcc_share_is_visible = true;
    }
}
/* /Custom code: FC-2026-02-26 */
?>

<div class="fcc-navbar-shell mb-6 <?= $fcc_share_is_visible ? 'fcc-navbar-shell--with-share-row' : null ?>">
<nav id="navbar" class="navbar navbar-main navbar-expand-lg navbar-light index-highly-rounded border border-gray-100 <?= $fcc_share_is_visible ? 'fcc-navbar--with-share-row' : null ?>">
    <div class="container">
        <a
                class="navbar-brand d-flex"
                href="<?= url() ?>"
                data-logo
                data-light-value="<?= settings()->main->logo_light != '' ? settings()->main->logo_light_full_url : settings()->main->title ?>"
                data-light-class="<?= settings()->main->logo_light != '' ? 'img-fluid navbar-logo' : '' ?>"
                data-light-tag="<?= settings()->main->logo_light != '' ? 'img' : 'span' ?>"
                data-dark-value="<?= settings()->main->logo_dark != '' ? settings()->main->logo_dark_full_url : settings()->main->title ?>"
                data-dark-class="<?= settings()->main->logo_dark != '' ? 'img-fluid navbar-logo' : '' ?>"
                data-dark-tag="<?= settings()->main->logo_dark != '' ? 'img' : 'span' ?>"
        >
            <?php if(settings()->main->{'logo_' . \Altum\ThemeStyle::get()} != ''): ?>
                <img src="<?= settings()->main->{'logo_' . \Altum\ThemeStyle::get() . '_full_url'} ?>" class="img-fluid navbar-logo" alt="<?= l('global.accessibility.logo_alt') ?>" />
            <?php else: ?>
                <?= settings()->main->title ?>
            <?php endif ?>
        </a>

        <button class="btn navbar-custom-toggler d-lg-none" type="button" data-toggle="collapse" data-target="#main_navbar" aria-controls="main_navbar" aria-expanded="false" aria-label="<?= l('global.accessibility.toggle_navigation') ?>">
            <i class="fas fa-fw fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="main_navbar">
            <ul class="navbar-nav align-items-lg-center flex-wrap flex-lg-nowrap">

                <!-- Custom code: FC-2026-02-26: fixed primary menu order -->
                <li class="nav-item"><a class="nav-link" href="<?= url('pages/foreverclub') ?>" id="fcc_tour_nav_forever_club"><?= $fcc_menu_fcc_label ?></a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $fcc_products_category_url ?>" id="fcc_tour_nav_forever_products"><?= $fcc_menu_products_label ?></a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('blog') ?>" id="fcc_tour_nav_blog"><?= $fcc_menu_blog_label ?></a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('page/contact') ?>" id="fcc_tour_nav_contact"><?= $fcc_menu_contact_label ?></a></li>
                <!-- /Custom code: FC-2026-02-26 -->

                <?php foreach($data->pages as $data): ?>
                    <?php
                    /* Custom code: FC-2026-02-26: avoid duplicates with fixed primary links */
                    $fcc_skip_urls = [
                        trim((string) url('pages/foreverclub'), '/'),
                        trim((string) $fcc_products_category_url, '/'),
                        trim((string) url('blog'), '/'),
                        trim((string) url('page/contact'), '/'),
                    ];
                    $fcc_page_url = trim((string) $data->url, '/');
                    $fcc_page_url_lower = mb_strtolower($fcc_page_url);

                    if(in_array($fcc_page_url, $fcc_skip_urls)) {
                        continue;
                    }

                    if(
                        str_contains($fcc_page_url_lower, '/page/contact')
                        || str_ends_with($fcc_page_url_lower, '/contact')
                    ) {
                        continue;
                    }
                    /* /Custom code: FC-2026-02-26 */
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $data->url ?>" target="<?= $data->target ?>">
                            <?php if($data->icon): ?>
                                <i class="<?= $data->icon ?> fa-fw fa-sm mr-1"></i>
                            <?php endif ?>

                            <?= $data->title ?>
                        </a>
                    </li>
                <?php endforeach ?>

                <?php if(settings()->tools->is_enabled && (settings()->tools->access == 'everyone' || (settings()->tools->access == 'users' && is_logged_in()))): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('tools') ?>"><?= l('tools.menu') ?></a></li>
                <?php endif ?>

                <?php /* Custom code: FC-2026-02-24: hide directory from main menu */ ?>
                <?php /* /Custom code: FC-2026-02-24 */ ?>

                <?php if(is_logged_in()): ?>

                    <li class="nav-item"><a class="nav-link" href="<?= url('dashboard') ?>" id="fcc_tour_nav_dashboard"><?= $fcc_menu_dashboard_label ?></a></li>

                    <?php if(settings()->internal_notifications->users_is_enabled): ?>
                        <li class="nav-item dropdown" id="internal_notifications">
                            <a id="internal_notifications_link" href="#" class="nav-link dropdown-toggle dropdown-toggle-simple" data-internal-notifications="user" data-tooltip data-tooltip-hide-on-click title="<?= l('internal_notifications.menu') ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-boundary="window">
                                <span class="fa-layers fa-fw">
                                    <i class="fas fa-fw fa-bell"></i>
                                    <?php if($this->user->has_pending_internal_notifications): ?>
                                        <span class="fa-layers-counter text-danger internal-notification-icon">&nbsp;</span>
                                    <?php endif ?>
                                </span>
                                <span class="d-lg-none ml-1"><?= l('internal_notifications.menu') ?></span>
                            </a>

                            <div id="internal_notifications_content" class="dropdown-menu dropdown-menu-right px-4 py-2" style="width: 550px;max-width: 550px;"></div>
                        </li>

                        <?php include_view(THEME_PATH . 'views/partials/internal_notifications_js.php', ['has_pending_internal_notifications' => $this->user->has_pending_internal_notifications]) ?>
                    <?php endif ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" data-toggle="dropdown" href="#" aria-haspopup="true" aria-expanded="false">
                            <img src="<?= get_user_avatar($this->user->avatar, $this->user->email) ?>" class="navbar-avatar mr-2" loading="lazy" />
                            <?= $this->user->name ?>
                            <span class="ml-2 caret"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="d-flex flex-column flex-lg-row">
                                <div class="pr-lg-3">
                                    <div
                                            class="px-3 py-2 font-weight-bold"
                                            data-logo
                                            data-light-value="<?= settings()->main->logo_light != '' ? settings()->main->logo_light_full_url : settings()->main->title ?>"
                                            data-light-class="<?= settings()->main->logo_light != '' ? 'img-fluid navbar-logo-mini' : '' ?>"
                                            data-light-tag="<?= settings()->main->logo_light != '' ? 'img' : 'span' ?>"
                                            data-dark-value="<?= settings()->main->logo_dark != '' ? settings()->main->logo_dark_full_url : settings()->main->title ?>"
                                            data-dark-class="<?= settings()->main->logo_dark != '' ? 'img-fluid navbar-logo-mini' : '' ?>"
                                            data-dark-tag="<?= settings()->main->logo_dark != '' ? 'img' : 'span' ?>"
                                    >
                                        <?php if(settings()->main->{'logo_' . \Altum\ThemeStyle::get()} != ''): ?>
                                            <img src="<?= settings()->main->{'logo_' . \Altum\ThemeStyle::get() . '_full_url'} ?>" class="img-fluid navbar-logo-mini" alt="<?= l('global.accessibility.logo_alt') ?>" data-toggle="tooltip" title="<?= settings()->main->title ?>" />
                                        <?php else: ?>
                                            <?= settings()->main->title ?>
                                        <?php endif ?>
                                    </div>

                                    <div class="dropdown-divider"></div>

                                    <?php if(settings()->links->biolinks_is_enabled): ?>
                                        <a href="<?= url('links?type=biolink') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-hashtag mr-2"></i> <?= l('links.menu.biolink') ?></a>
                                    <?php endif ?>

                                    <?php if(settings()->links->shortener_is_enabled): ?>
                                        <a href="<?= url('links?type=link') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-link mr-2"></i> <?= l('links.menu.link') ?></a>
                                    <?php endif ?>

                                    <?php if(settings()->links->files_is_enabled): ?>
                                        <a href="<?= url('links?type=file') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-file mr-2"></i> <?= l('links.menu.file') ?></a>
                                    <?php endif ?>

                                    <?php if(settings()->links->vcards_is_enabled): ?>
                                        <a href="<?= url('links?type=vcard') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-id-card mr-2"></i> <?= l('links.menu.vcard') ?></a>
                                    <?php endif ?>

                                    <?php if(settings()->links->events_is_enabled): ?>
                                        <a href="<?= url('links?type=event') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-calendar mr-2"></i> <?= l('links.menu.event') ?></a>
                                    <?php endif ?>

                                    <?php if(settings()->links->static_is_enabled): ?>
                                        <a href="<?= url('links?type=static') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-file-code mr-2"></i> <?= l('links.menu.static') ?></a>
                                    <?php endif ?>

                                    <?php if(settings()->codes->qr_codes_is_enabled): ?>
                                        <a href="<?= url('qr-codes') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-qrcode mr-2"></i> <?= l('qr_codes.menu') ?></a>
                                    <?php endif ?>

                                    <?php if(\Altum\Plugin::is_active('aix')): ?>
                                        <div class="dropdown-divider"></div>
                                    <?php endif ?>

                                    <?php if(\Altum\Plugin::is_active('aix') && settings()->aix->documents_is_enabled): ?>
                                        <a href="<?= url('documents') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-robot mr-2"></i> <?= l('documents.menu') ?></a>
                                    <?php endif ?>

                                    <?php if(\Altum\Plugin::is_active('aix') && settings()->aix->images_is_enabled): ?>
                                        <a href="<?= url('images') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-icons mr-2"></i> <?= l('images.menu') ?></a>
                                    <?php endif ?>

                                    <?php if(\Altum\Plugin::is_active('aix') && settings()->aix->transcriptions_is_enabled): ?>
                                        <a href="<?= url('transcriptions') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-microphone-alt mr-2"></i> <?= l('transcriptions.menu') ?></a>
                                    <?php endif ?>

                                    <?php if(\Altum\Plugin::is_active('aix') && settings()->aix->syntheses_is_enabled): ?>
                                        <a href="<?= url('syntheses') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-voicemail mr-2"></i> <?= l('syntheses.menu') ?></a>
                                    <?php endif ?>

                                    <?php if(\Altum\Plugin::is_active('aix') && settings()->aix->chats_is_enabled): ?>
                                        <a href="<?= url('chats') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-comments mr-2"></i> <?= l('chats.menu') ?></a>
                                    <?php endif ?>

                                    <?php if(\Altum\Plugin::is_active('email-signatures') && settings()->signatures->is_enabled): ?>
                                        <a href="<?= url('signatures') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-file-signature mr-2"></i> <?= l('signatures.menu') ?></a>
                                    <?php endif ?>
                                </div>

                                <div>
                                    <?php if(!\Altum\Teams::is_delegated()): ?>
                                        <?php if(\Altum\Authentication::is_admin()): ?>
                                            <a class="dropdown-item" href="<?= url('admin') ?>"><i class="fas fa-fw fa-sm fa-fingerprint text-primary mr-2"></i> <?= l('global.menu.admin') ?></a>
                                            <div class="dropdown-divider"></div>
                                        <?php else: ?>
                                            <div class="px-3 py-2 font-weight-bold  d-flex align-items-center">
                                                <img src="<?= get_user_avatar($this->user->avatar, $this->user->email) ?>" class="navbar-logo-mini rounded mr-2" loading="lazy" />
                                                <div class="text-truncate d-inline-block"><?= $this->user->email ?></div>
                                            </div>

                                            <div class="dropdown-divider"></div>
                                        <?php endif ?>

                                        <a class="dropdown-item" href="<?= url('account') ?>"><i class="fas fa-fw fa-sm fa-user-cog mr-2"></i> <?= l('account.menu') ?></a>

                                        <a class="dropdown-item" href="<?= url('account-preferences') ?>"><i class="fas fa-fw fa-sm fa-sliders-h mr-2"></i> <?= l('account_preferences.menu') ?></a>

                                        <a class="dropdown-item" href="<?= url('account-plan') ?>"><i class="fas fa-fw fa-sm fa-box-open mr-2"></i> <?= l('account_plan.menu') ?></a>

                                        <?php if(settings()->payment->is_enabled): ?>
                                            <a class="dropdown-item" href="<?= url('account-payments') ?>"><i class="fas fa-fw fa-sm fa-credit-card mr-2"></i> <?= l('account_payments.menu') ?></a>

                                            <?php if(\Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled): ?>
                                                <a class="dropdown-item" href="<?= url('referrals') ?>"><i class="fas fa-fw fa-sm fa-wallet mr-2"></i> <?= l('referrals.menu') ?></a>
                                            <?php endif ?>
                                        <?php endif ?>

                                        <?php if(settings()->main->api_is_enabled): ?>
                                            <a class="dropdown-item" href="<?= url('account-api') ?>"><i class="fas fa-fw fa-sm fa-code mr-2"></i> <?= l('account_api.menu') ?></a>
                                        <?php endif ?>

                                        <?php if(\Altum\Plugin::is_active('teams')): ?>
                                            <a class="dropdown-item" href="<?= url('teams-system') ?>"><i class="fas fa-fw fa-sm fa-user-shield mr-2"></i> <?= l('teams_system.menu') ?></a>
                                        <?php endif ?>

                                        <?php if(settings()->sso->is_enabled && settings()->sso->display_menu_items && count((array) settings()->sso->websites)): ?>
                                            <div class="dropdown-divider"></div>

                                            <?php foreach(settings()->sso->websites as $website): ?>
                                                <a class="dropdown-item" href="<?= url('sso/switch?to=' . $website->id) ?>"><i class="<?= $website->icon ?> fa-fw fa-sm mr-2"></i> <?= sprintf(l('sso.menu'), $website->name) ?></a>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    <?php endif ?>

                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?= url('logout') ?>"><i class="fas fa-fw fa-sm fa-sign-out-alt mr-2"></i> <?= l('global.menu.logout') ?></a>
                                </div>
                            </div>
                        </div>
                    </li>

                <?php else: ?>

                    <?php if(settings()->users->register_is_enabled): ?>
                        <li class="nav-item d-flex align-items-center ml-lg-1">
                            <!-- Custom code: FC-2026-02-27: register button label update -->
                            <a class="btn btn-sm btn-primary" href="<?= url('register') ?>"><i class="fas fa-fw fa-sm fa-user-plus"></i> Registracija</a>
                            <!-- /Custom code: FC-2026-02-27 -->
                        </li>
                    <?php endif ?>

                    <li class="nav-item d-flex align-items-center ml-lg-1">
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('login') ?>"><i class="fas fa-fw fa-sm fa-sign-in-alt"></i> <?= l('login.menu') ?></a>
                    </li>

                <?php endif ?>

            </ul>
        </div>
    </div>

    <?php if($fcc_share_is_visible): ?>
</nav>
    <div class="fcc-navbar-share-row" id="fcc_tour_blog_share_row">
        <div class="fcc-navbar-share-row__inner">
            <div class="fcc-navbar-share-row__copy">
                <span class="fcc-navbar-share-row__pill">FCC</span>
                <div class="fcc-navbar-share-row__text-wrap">
                    <div class="fcc-navbar-share-row__title"><?= $fcc_is_hr_language ? 'Podijelite ovu stranicu s vašom preporukom.' : 'Share this page with your recommendation.' ?></div>
                    <button
                        type="button"
                        id="fcc_tour_blog_share_info"
                        class="fcc-navbar-share-row__info"
                        data-fcc-navbar-share-toggle
                        data-target="#fcc-navbar-share-details"
                        aria-expanded="false"
                        aria-controls="fcc-navbar-share-details"
                    >
                        <i class="fas fa-fw fa-info-circle mr-1"></i>
                        <?= $fcc_is_hr_language ? 'Kako ovo radi' : 'How this works' ?>
                    </button>
                </div>
            </div>

            <div class="fcc-navbar-share-row__buttons" id="fcc_tour_blog_share_buttons">
                <?= include_view(THEME_PATH . 'views/partials/share_buttons.php', [
                    'url' => $fcc_share_url,
                    'class' => 'btn btn-gray-100 btn-sm',
                    'copy_to_clipboard' => true,
                    'tracking_context' => 'navbar_share',
                    'include' => ['copy', 'share', 'print', 'facebook', 'linkedin', 'whatsapp'],
                    'exclude' => ['email', 'threads', 'x', 'telegram'],
                ]) ?>
            </div>
        </div>

        <div id="fcc-navbar-share-details" class="fcc-navbar-share-row__details d-none">
            <?= l('blog.share_referral.navbar_details') ?>
        </div>
    </div>
    <?php endif ?>
<?php if(!$fcc_share_is_visible): ?>
</nav>
<?php endif ?>
</div>

<?php ob_start() ?>
<style>
    .fcc-navbar-shell {
        position: relative;
    }

    .fcc-navbar-shell--with-share-row {
        border-radius: 1.35rem;
        overflow: hidden;
        background: linear-gradient(180deg, rgba(5, 7, 12, 0.98), rgba(10, 13, 20, 0.94));
        border: 1px solid rgba(68, 196, 181, 0.12);
        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.16);
    }

    .fcc-navbar-shell--with-share-row #navbar {
        margin-bottom: 0;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
        border: 0 !important;
        background: transparent;
        box-shadow: none;
    }

    .fcc-navbar--with-share-row {
        padding-bottom: 0;
    }

    .fcc-navbar--with-share-row > .container:first-child {
        padding-bottom: 0.05rem;
    }

    .fcc-navbar-share-row {
        width: 100%;
        padding: 0;
        position: relative;
        z-index: 6;
    }

    .fcc-navbar-share-row__inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.82rem 1.35rem 0.92rem;
        border-top: 1px solid rgba(68, 196, 181, 0.1);
        background:
            radial-gradient(120% 180% at 0% 0%, rgba(74, 208, 189, 0.07), transparent 36%),
            linear-gradient(180deg, rgba(10, 14, 20, 0.68), rgba(10, 14, 20, 0.9));
    }

    .fcc-navbar-share-row__copy {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        min-width: 0;
    }

    .fcc-navbar-share-row__pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.34rem 0.64rem;
        border-radius: 999px;
        background: linear-gradient(135deg, rgba(75, 210, 190, 0.16), rgba(71, 120, 255, 0.1));
        color: #abfaf1;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        flex-shrink: 0;
        box-shadow: inset 0 0 0 1px rgba(171, 250, 241, 0.06);
    }

    .fcc-navbar-share-row__text-wrap {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        flex-wrap: wrap;
        min-width: 0;
    }

    .fcc-navbar-share-row__title {
        color: rgba(241, 246, 251, 0.94);
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.45;
    }

    .fcc-navbar-share-row__info {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        padding: 0.28rem 0.55rem;
        border: 1px solid rgba(68, 196, 181, 0.08);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.03);
        color: rgba(148, 230, 221, 0.9);
        font-size: 0.83rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .fcc-navbar-share-row__buttons .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        margin: 0 0.28rem 0 0;
        padding: 0;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.04);
        position: relative;
        z-index: 7;
        pointer-events: auto;
        touch-action: manipulation;
    }

    .fcc-navbar-share-row__details {
        padding: 0 1.35rem 0.95rem;
        border-bottom-left-radius: 1.35rem;
        border-bottom-right-radius: 1.35rem;
        background: linear-gradient(180deg, rgba(10, 14, 20, 0.88), rgba(10, 14, 20, 0.94));
        color: rgba(228, 235, 241, 0.84);
        font-size: 0.88rem;
        line-height: 1.6;
    }

    .fcc-navbar-shell .dropdown {
        position: relative;
    }

    .fcc-navbar-shell .dropdown-menu.dropdown-menu-right {
        margin-top: 0.8rem;
        border-radius: 1rem;
        overflow: hidden;
        z-index: 1085;
    }

    .fcc-navbar-shell--with-share-row .dropdown-menu.dropdown-menu-right {
        margin-top: 1rem;
    }

    @media (max-width: 991px) {
        .fcc-navbar-shell,
        .fcc-navbar-shell--with-share-row,
        .fcc-navbar-share-row,
        .fcc-navbar-share-row__inner,
        .fcc-navbar-share-row__buttons {
            position: relative;
        }

        .fcc-navbar-share-row__buttons {
            z-index: 8;
        }

        .fcc-navbar-share-row__buttons .btn,
        .fcc-navbar-share-row__buttons a,
        .fcc-navbar-share-row__buttons button {
            pointer-events: auto;
        }

        .fcc-navbar-shell--with-share-row {
            border-radius: 1.15rem;
            overflow: hidden;
        }

        .fcc-navbar-shell--with-share-row #navbar {
            border-bottom-left-radius: 1.15rem;
            border-bottom-right-radius: 1.15rem;
        }

        .fcc-navbar-share-row__inner {
            flex-direction: column;
            align-items: stretch;
            gap: 0.72rem;
            padding: 0.8rem 1rem 0.85rem;
        }

        .fcc-navbar-share-row__copy {
            align-items: flex-start;
            gap: 0.7rem;
        }

        .fcc-navbar-share-row__text-wrap {
            align-items: flex-start;
            flex-direction: column;
            gap: 0.45rem;
        }

        .fcc-navbar-share-row__buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-left: 3.15rem;
        }

        .fcc-navbar-share-row__buttons .btn {
            margin: 0;
            width: 2.35rem;
            height: 2.35rem;
        }

        .fcc-navbar-share-row__details {
            padding: 0 1rem 0.82rem;
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .fcc-navbar-shell .dropdown-menu.dropdown-menu-right,
        .fcc-navbar-shell--with-share-row .dropdown-menu.dropdown-menu-right {
            margin-top: 0.6rem;
        }
    }

    @media (max-width: 575px) {
        .fcc-navbar-shell--with-share-row {
            border-radius: 1.05rem;
        }

        .fcc-navbar-share-row__inner {
            gap: 0.65rem;
            padding: 0.72rem 0.82rem 0.78rem;
        }

        .fcc-navbar-share-row__details {
            padding: 0 0.82rem 0.78rem;
        }

        .fcc-navbar-share-row__title {
            font-size: 0.84rem;
            line-height: 1.38;
        }

        .fcc-navbar-share-row__pill {
            padding: 0.28rem 0.54rem;
            font-size: 0.64rem;
        }

        .fcc-navbar-share-row__info {
            padding: 0.22rem 0.46rem;
            font-size: 0.76rem;
        }

        .fcc-navbar-share-row__buttons {
            margin-left: 0;
            padding-left: 2.95rem;
        }

        .fcc-navbar-share-row__buttons .btn {
            width: 2.2rem;
            height: 2.2rem;
        }

    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head', 'fcc_navbar_share_row_css'); ?>

<?php ob_start() ?>
<script>
    'use strict';

    (() => {
        const button = document.querySelector('[data-fcc-navbar-share-toggle]');
        const target = document.querySelector('#fcc-navbar-share-details');

        if(!button || !target || button.dataset.fccNavbarShareBound) {
            return;
        }

        button.dataset.fccNavbarShareBound = 'true';

        button.addEventListener('click', () => {
            const isHidden = target.classList.contains('d-none');
            target.classList.toggle('d-none', !isHidden);
            button.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
    })();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript', 'fcc_navbar_share_row_js'); ?>
