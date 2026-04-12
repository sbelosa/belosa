<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-03-03: hidden sidebar items for all users */
$show_admin_only_sidebar_items = false;

/* Custom code: FC-2026-03-08: user feedback replies counter for sidebar */
$user_feedback_replies_count = 0;
$user_new_contacts_count = 0;
$user_fcc_ai_signal_count = 0;
if(is_logged_in()) {
    try {
        $has_feedback_tickets_table_result = database()->query("SHOW TABLES LIKE 'feedback_tickets'");
        if($has_feedback_tickets_table_result && $has_feedback_tickets_table_result->num_rows) {
            $user_feedback_replies_count = (int) (database()->query("SELECT COUNT(*) AS `total` FROM `feedback_tickets` WHERE `user_id` = {$this->user->user_id} AND `status` = 'answered'")->fetch_object()->total ?? 0);
        }
    } catch(\Throwable $exception) {
        $user_feedback_replies_count = 0;
    }

    try {
        $sidebar_preferences = $this->user->preferences ?? new \stdClass();

        if(is_string($sidebar_preferences)) {
            $sidebar_preferences = json_decode($sidebar_preferences ?? '{}');
        }

        if(is_array($sidebar_preferences)) {
            $sidebar_preferences = (object) $sidebar_preferences;
        }

        if(!$sidebar_preferences instanceof \stdClass) {
            $sidebar_preferences = (object) $sidebar_preferences;
        }

        $last_seen_contact_datum_id = (int) ($sidebar_preferences->data_last_seen_datum_id ?? 0);
        $user_new_contacts_count = (int) (database()->query("SELECT COUNT(*) AS `total`
            FROM `data`
            WHERE `user_id` = {$this->user->user_id}
              AND `datum_id` > {$last_seen_contact_datum_id}
              AND `type` != 'leader_os_fraud_cluster'
              AND `type` != 'billing_event'")->fetch_object()->total ?? 0);
    } catch(\Throwable $exception) {
        $user_new_contacts_count = 0;
    }

    /* Custom code: FC-2026-03-23: sync funnels analytics sidebar access with lead funnel plan availability */
    $enabled_biolink_blocks = (object) ($this->user->plan_settings->enabled_biolink_blocks ?? []);
    $has_lead_funnel_access = (bool) ($enabled_biolink_blocks->lead_funnel ?? false);
    $has_ai_growth_plan_access = \Altum\Authentication::is_admin() || (bool) ($this->user->plan_settings->ai_growth_plan_is_enabled ?? false);
    $has_fcc_ai_access = fcc_ai_user_has_public_ai_access($this->user);
    if($has_fcc_ai_access) {
        try {
            $fcc_ai_sidebar_signal_state = fcc_ai_get_user_sidebar_signal_state($this->user);
            $user_fcc_ai_signal_count = (int) ($fcc_ai_sidebar_signal_state['count'] ?? 0);
        } catch(\Throwable $exception) {
            $user_fcc_ai_signal_count = 0;
        }
    }
    $ai_plan_preferences = $this->user->preferences ?? new \stdClass();

    if(is_string($ai_plan_preferences)) {
        $ai_plan_preferences = json_decode($ai_plan_preferences ?? '{}');
    }

    if(is_array($ai_plan_preferences)) {
        $ai_plan_preferences = (object) $ai_plan_preferences;
    }

    if(!$ai_plan_preferences instanceof \stdClass) {
        $ai_plan_preferences = (object) $ai_plan_preferences;
    }

    $ai_plan_profile = $ai_plan_preferences->leader_ai_profile ?? null;

    if(is_array($ai_plan_profile)) {
        $ai_plan_profile = (object) $ai_plan_profile;
    }

    $ai_plan_profile_required_fields = ['primary_goal', 'priority_offer', 'available_time', 'biggest_blocker', 'communication_style', 'follow_up_readiness', 'weekly_change'];
    $ai_plan_sidebar_profile_complete = true;

    if(!$ai_plan_profile || empty($ai_plan_profile->active_channels) || !is_array($ai_plan_profile->active_channels)) {
        $ai_plan_sidebar_profile_complete = false;
    }

    foreach($ai_plan_profile_required_fields as $ai_plan_profile_field) {
        if(empty($ai_plan_profile->{$ai_plan_profile_field})) {
            $ai_plan_sidebar_profile_complete = false;
            break;
        }
    }

    $ai_plan_sidebar_app_review_accessible = \Altum\Authentication::is_admin() || $ai_plan_sidebar_profile_complete;
    $ai_plan_sidebar_app_review_tooltip = l('ai_plan.app_review_locked_entry_tooltip');
    /* /Custom code: FC-2026-03-23 */
}
/* /Custom code: FC-2026-03-08 */
?>

<div class="app-sidebar" id="fcc_dashboard_tour_sidebar">
    <div class="app-sidebar-title text-truncate">
        <a
                href="<?= url() ?>"
                class="text-truncate"
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
    </div>

    <div class="app-sidebar-links-wrapper flex-grow-1">
        <ul class="app-sidebar-links">
            <?php if(is_logged_in()): ?>
                <li class="<?= \Altum\Router::$controller == 'Dashboard' ? 'active' : null ?> d-flex dropdown" id="internal_notifications">
                    <a href="<?= url('dashboard') ?>" id="fcc_dashboard_tour_sidebar_dashboard"><i class="fas fa-fw fa-sm fa-th mr-2"></i> <?= l('dashboard.menu') ?></a>

                    <?php if(settings()->internal_notifications->users_is_enabled): ?>
                        <a id="internal_notifications_link" href="#" class="default w-auto dropdown-toggle dropdown-toggle-simple ml-1" data-internal-notifications="user" data-tooltip data-tooltip-hide-on-click title="<?= l('internal_notifications.menu') ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-boundary="window">
                            <span id="internal_notifications_icon_wrapper" class="fa-layers fa-fw">
                                <i class="fas fa-fw fa-bell"></i>
                                <?php if($this->user->has_pending_internal_notifications): ?>
                                    <span class="fa-layers-counter text-danger internal-notification-icon">&nbsp;</span>
                                <?php endif ?>
                            </span>
                        </a>

                        <div id="internal_notifications_content" class="dropdown-menu dropdown-menu-right px-4 py-2" style="width: 550px;max-width: 550px;"></div>

                        <?php include_view(THEME_PATH . 'views/partials/internal_notifications_js.php', ['has_pending_internal_notifications' => $this->user->has_pending_internal_notifications]) ?>
                    <?php endif ?>
                </li>

                <?php if(settings()->links->biolinks_is_enabled): ?>
                    <li class="<?= (\Altum\Router::$controller == 'Links' && ($_GET['type'] ?? null) == 'biolink') || (\Altum\Router::$controller == 'Link' && $this->link->type == 'biolink') ? 'active' : null ?>">
                        <a href="<?= url('links?type=biolink') ?>" id="fcc_dashboard_tour_sidebar_apps"><i class="fas fa-fw fa-sm fa-hashtag mr-2"></i> <?= l('links.menu.biolink') ?></a>
                    </li>
                <?php endif ?>

                <?php if(settings()->links->shortener_is_enabled): ?>
                    <li class="<?= (\Altum\Router::$controller == 'Links' && ($_GET['type'] ?? null) == 'link') || (\Altum\Router::$controller == 'Link' && $this->link->type == 'link') || \Altum\Router::$controller == 'LinkCreate' ? 'active' : null ?>">
                        <a href="<?= url('links?type=link') ?>"><i class="fas fa-fw fa-sm fa-link mr-2"></i> <?= l('links.menu.link') ?></a>
                    </li>
                <?php endif ?>

                <?php if(settings()->links->files_is_enabled && $show_admin_only_sidebar_items): ?>
                    <li class="<?= (\Altum\Router::$controller == 'Links' && ($_GET['type'] ?? null) == 'file') || (\Altum\Router::$controller == 'Link' && $this->link->type == 'file') ? 'active' : null ?>">
                        <a href="<?= url('links?type=file') ?>"><i class="fas fa-fw fa-sm fa-file mr-2"></i> <?= l('links.menu.file') ?></a>
                    </li>
                <?php endif ?>

                <?php if(settings()->links->vcards_is_enabled && $show_admin_only_sidebar_items): ?>
                    <li class="<?= (\Altum\Router::$controller == 'Links' && ($_GET['type'] ?? null) == 'vcard') || (\Altum\Router::$controller == 'Link' && $this->link->type == 'vcard') ? 'active' : null ?>">
                        <a href="<?= url('links?type=vcard') ?>"><i class="fas fa-fw fa-sm fa-id-card mr-2"></i> <?= l('links.menu.vcard') ?></a>
                    </li>
                <?php endif ?>

                <?php if(settings()->links->events_is_enabled): ?>
                    <li class="<?= (\Altum\Router::$controller == 'Links' && ($_GET['type'] ?? null) == 'event') || (\Altum\Router::$controller == 'Link' && $this->link->type == 'event') ? 'active' : null ?>">
                        <a href="<?= url('links?type=event') ?>"><i class="fas fa-fw fa-sm fa-calendar mr-2"></i> <?= l('links.menu.event') ?></a>
                    </li>
                <?php endif ?>

                <?php if(settings()->links->static_is_enabled && $show_admin_only_sidebar_items): ?>
                    <li class="<?= (\Altum\Router::$controller == 'Links' && ($_GET['type'] ?? null) == 'static') || (\Altum\Router::$controller == 'Link' && $this->link->type == 'static') ? 'active' : null ?>">
                        <a href="<?= url('links?type=static') ?>"><i class="fas fa-fw fa-sm fa-file-code mr-2"></i> <?= l('links.menu.static') ?></a>
                    </li>
                <?php endif ?>

                <?php if(settings()->codes->qr_codes_is_enabled): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['QrCodes', 'QrCodeUpdate', 'QrCodeCreate']) ? 'active' : null ?>">
                        <a href="<?= url('qr-codes') ?>"><i class="fas fa-fw fa-sm fa-qrcode mr-2"></i> <?= l('qr_codes.menu') ?></a>
                    </li>
                <?php endif ?>

                <?php if(\Altum\Plugin::is_active('aix')): ?>
                    <div class="divider-wrapper">
                        <div class="divider"></div>
                    </div>
                <?php endif ?>

                <?php if(\Altum\Plugin::is_active('aix') && settings()->aix->documents_is_enabled): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['Documents', 'DocumentUpdate', 'DocumentCreate']) ? 'active' : null ?>">
                        <a href="<?= url('documents') ?>"><i class="fas fa-fw fa-sm fa-robot mr-2"></i> <?= l('documents.menu') ?></a>
                    </li>
                <?php endif ?>

                <?php if(\Altum\Plugin::is_active('aix') && settings()->aix->images_is_enabled): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['Images', 'ImageUpdate', 'ImageCreate']) ? 'active' : null ?>">
                        <a href="<?= url('images') ?>"><i class="fas fa-fw fa-sm fa-icons mr-2"></i> <?= l('images.menu') ?></a>
                    </li>
                <?php endif ?>

                <?php if(\Altum\Plugin::is_active('aix') && settings()->aix->transcriptions_is_enabled): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['Transcriptions', 'TranscriptionUpdate', 'TranscriptionCreate']) ? 'active' : null ?>">
                        <a href="<?= url('transcriptions') ?>"><i class="fas fa-fw fa-sm fa-microphone-alt mr-2"></i> <?= l('transcriptions.menu') ?></a>
                    </li>
                <?php endif ?>

                <?php if(\Altum\Plugin::is_active('aix') && settings()->aix->syntheses_is_enabled): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['Syntheses', 'SynthesisUpdate', 'SynthesisCreate']) ? 'active' : null ?>">
                        <a href="<?= url('syntheses') ?>"><i class="fas fa-fw fa-sm fa-voicemail mr-2"></i> <?= l('syntheses.menu') ?></a>
                    </li>
                <?php endif ?>

                <?php if(\Altum\Plugin::is_active('aix') && settings()->aix->chats_is_enabled): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['Chats', 'Chat', 'ChatCreate']) ? 'active' : null ?>">
                        <a href="<?= url('chats') ?>"><i class="fas fa-fw fa-sm fa-comments mr-2"></i> <?= l('chats.menu') ?></a>
                    </li>
                <?php endif ?>

                <?php if(\Altum\Plugin::is_active('email-signatures') && settings()->signatures->is_enabled): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['Signatures', 'SignatureUpdate', 'SignatureCreate']) ? 'active' : null ?>">
                        <a href="<?= url('signatures') ?>"><i class="fas fa-fw fa-sm fa-file-signature mr-2"></i> <?= l('signatures.menu') ?></a>
                    </li>
                <?php endif ?>
            <?php endif ?>

            <?php if(settings()->tools->is_enabled && (settings()->tools->access == 'everyone' || (settings()->tools->access == 'users' && is_logged_in()))): ?>
                <li class="<?= \Altum\Router::$controller == 'Tools' ? 'active' : null ?>">
                    <a href="<?= url('tools') ?>"><i class="fas fa-fw fa-sm fa-tools mr-2"></i> <?= l('tools.menu') ?></a>
                </li>
            <?php endif ?>

            <div class="divider-wrapper">
                <div class="divider"></div>
            </div>

            <?php if(is_logged_in()): ?>

                <?php if(settings()->links->domains_is_enabled && $show_admin_only_sidebar_items): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['Domains', 'DomainUpdate', 'DomainCreate']) ? 'active' : null ?>">
                        <a href="<?= url('domains') ?>"><i class="fas fa-fw fa-sm fa-globe mr-2"></i> <?= l('domains.menu') ?></a>
                    </li>
                <?php endif ?>

                <?php if((settings()->links->biolinks_is_enabled || settings()->links->shortener_is_enabled || settings()->links->files_is_enabled || settings()->links->vcards_is_enabled || settings()->links->events_is_enabled || settings()->links->static_is_enabled) && $show_admin_only_sidebar_items): ?>
                <li class="<?= in_array(\Altum\Router::$controller, ['NotificationHandlers', 'NotificationHandlerUpdate', 'NotificationHandlerCreate']) ? 'active' : null ?>">
                    <a href="<?= url('notification-handlers') ?>"><i class="fas fa-fw fa-sm fa-bell mr-2"></i> <?= l('notification_handlers.menu') ?></a>
                </li>
                <?php endif ?>

                <?php if(settings()->links->pixels_is_enabled): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['Pixels', 'PixelUpdate', 'PixelCreate']) ? 'active' : null ?>">
                        <a href="<?= url('pixels') ?>"><i class="fas fa-fw fa-sm fa-adjust mr-2"></i> <?= l('pixels.menu') ?></a>
                    </li>
                <?php endif ?>

                <?php if(settings()->links->projects_is_enabled && $show_admin_only_sidebar_items): ?>
                <li class="<?= in_array(\Altum\Router::$controller, ['Projects', 'ProjectUpdate', 'ProjectCreate']) ? 'active' : null ?>">
                    <a href="<?= url('projects') ?>"><i class="fas fa-fw fa-sm fa-project-diagram mr-2"></i> <?= l('projects.menu') ?></a>
                </li>
                <?php endif ?>

                <?php if(settings()->links->splash_page_is_enabled && $show_admin_only_sidebar_items): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['SplashPages', 'SplashPageUpdate', 'SplashPageCreate']) ? 'active' : null ?>">
                        <a href="<?= url('splash-pages') ?>"><i class="fas fa-fw fa-sm fa-droplet mr-2"></i> <?= l('splash_pages.menu') ?></a>
                    </li>
                <?php endif ?>

                <?php if(settings()->links->biolinks_is_enabled): ?>
                    <li class="<?= \Altum\Router::$controller == 'Data' ? 'active' : null ?>">
                        <a href="<?= url('data') ?>"><i class="fas fa-fw fa-sm fa-address-book mr-2"></i> <?= l('data.menu') ?>
                            <?php if($user_new_contacts_count): ?>
                                <span class="badge badge-primary ml-2"><?= nr($user_new_contacts_count) ?></span>
                            <?php endif ?>
                        </a>
                    </li>

                    <?php /* Custom code: FC-2026-03-08: user feedback tickets menu */ ?>
                    <li class="<?= \Altum\Router::$controller == 'FeedbackTickets' ? 'active' : null ?>">
                        <a href="<?= url('feedback-tickets') ?>"><i class="fas fa-fw fa-sm fa-comments mr-2"></i> <?= l('feedback_tickets.menu') ?>
                            <?php if($user_feedback_replies_count): ?>
                                <span class="badge badge-danger ml-2"><?= nr($user_feedback_replies_count) ?></span>
                            <?php endif ?>
                        </a>
                    </li>
                    <?php /* /Custom code: FC-2026-03-08 */ ?>

                    <?php if(\Altum\Plugin::is_active('payment-blocks')): ?>
                        <li class="<?= in_array(\Altum\Router::$controller, ['PaymentProcessors', 'PaymentProcessorUpdate', 'PaymentProcessorCreate']) ? 'active' : null ?>">
                            <a href="<?= url('payment-processors') ?>"><i class="fas fa-fw fa-sm fa-credit-card mr-2"></i> <?= l('payment_processors.menu') ?></a>
                        </li>
                        <li class="<?= \Altum\Router::$controller == 'GuestsPayments' ? 'active' : null ?>">
                            <a href="<?= url('guests-payments') ?>"><i class="fas fa-fw fa-sm fa-coins mr-2"></i> <?= l('guests_payments.menu') ?></a>
                        </li>
                    <?php endif ?>
                <?php endif ?>
            <?php endif ?>

            <?php if(settings()->links->biolinks_is_enabled && settings()->links->directory_is_enabled && (settings()->links->directory_access == 'everyone' || (settings()->links->directory_access == 'users' && is_logged_in())) && $show_admin_only_sidebar_items): ?>
                <li class="<?= \Altum\Router::$controller == 'Directory' ? 'active' : null ?>">
                    <a href="<?= url('directory') ?>"><i class="fas fa-fw fa-sm fa-sitemap mr-2"></i> <?= l('directory.menu') ?></a>
                </li>
            <?php endif ?>

            <?php foreach($data->pages as $page): ?>
                <?php /* Custom code: FC-2026-02-27: hide contact page from user sidebar menu */ ?>
                <?php
                $page_url_raw = mb_strtolower(trim((string) $page->url));
                $is_contact_raw = str_contains($page_url_raw, 'page/contact')
                    || str_contains($page_url_raw, '/contact')
                    || str_contains($page_url_raw, '/kontakt');

                $page_url_path = parse_url((string) $page->url, PHP_URL_PATH) ?? '';
                $page_url_path = '/' . trim(mb_strtolower($page_url_path), '/');

                $is_contact_path = preg_match('#/(?:[a-z]{2}/)?(?:page/)?(?:contact|kontakt)/?$#', $page_url_path);
                $is_contact_title = in_array(mb_strtolower(trim((string) $page->title)), ['contact', 'kontakt']);

                if($is_contact_raw || $is_contact_path || $is_contact_title) continue;
                ?>
                <?php /* /Custom code: FC-2026-02-27 */ ?>
                <li>
                    <a href="<?= $page->url ?>" target="<?= $page->target ?>">
                        <?php if($page->icon): ?>
                            <i class="<?= $page->icon ?> fa-fw fa-sm mr-2"></i>
                        <?php endif ?>

                        <?= $page->title ?>
                    </a>
                </li>
            <?php endforeach ?>

                <!-- Custom code: FC-2026-02-25: forever education menu section -->
                <div class="divider-wrapper">
                    <div class="divider"></div>
                </div>
                <li class="app-sidebar-section-label" id="fcc_dashboard_tour_sidebar_section">
                    <span>FCC zona</span>
                </li>
                <?php /* Custom code: FC-2026-03-31: Next step sidebar entry above FCC results */ ?>
                <li class="<?= (\Altum\Router::$controller_key ?? null) === 'ai-plan' ? 'active' : null ?> app-sidebar-fcc-item">
                    <a href="<?= url('ai-plan') ?>" id="fcc_dashboard_tour_sidebar_ai_plan" class="<?= $has_ai_growth_plan_access ? null : 'disabled pointer-events-all' ?>" <?= $has_ai_growth_plan_access ? null : get_plan_feature_disabled_info() ?>><i class="fas fa-fw fa-sm fa-brain mr-2"></i> <?= l('ai_plan.menu') ?></a>
                </li>
                <?php /* /Custom code: FC-2026-03-31 */ ?>
                <li class="<?= \Altum\Router::$controller == 'FccAiHub' ? 'active' : null ?> app-sidebar-fcc-item">
                    <a href="<?= url('fcc-ai') ?>" id="fcc_dashboard_tour_sidebar_fcc_ai" class="<?= $has_fcc_ai_access ? null : 'disabled pointer-events-all' ?>" <?= $has_fcc_ai_access ? null : get_plan_feature_disabled_info() ?>>
                        <i class="fas fa-fw fa-sm fa-robot mr-2"></i> FCC AI
                        <?php if($user_fcc_ai_signal_count): ?>
                            <span class="badge badge-danger ml-2"><?= nr($user_fcc_ai_signal_count) ?></span>
                        <?php endif ?>
                    </a>
                </li>
                <?php $fcc_results_sidebar_is_active = \Altum\Router::$controller == 'FccResults'; ?>
                <li class="<?= $fcc_results_sidebar_is_active ? 'active' : null ?> app-sidebar-fcc-item">
                    <a href="<?= url('fcc-results') ?>" id="fcc_dashboard_tour_sidebar_results">
                        <i class="fas fa-fw fa-sm fa-trophy mr-2"></i> <?= l('fcc_results.menu') ?>
                    </a>
                </li>
                <?php if(settings()->links->biolinks_is_enabled): ?>
                    <li class="<?= in_array(\Altum\Router::$controller, ['FunnelsAnalytics']) ? 'active' : null ?> app-sidebar-fcc-item">
                        <a href="<?= url('funnels-analytics') ?>" class="<?= $has_lead_funnel_access ? null : 'disabled pointer-events-all' ?>" <?= $has_lead_funnel_access ? null : get_plan_feature_disabled_info() ?>>
                            <i class="fas fa-fw fa-sm fa-filter mr-2"></i> <?= l('funnels_analytics.menu') ?>
                        </a>
                    </li>
                <?php endif ?>
                <li class="<?= \Altum\Router::$controller == 'FccEducation' ? 'active' : null ?> app-sidebar-fcc-item">
                    <a href="<?= url('fcc-education?video=last') ?>" id="fcc_dashboard_tour_sidebar_education"><i class="fas fa-fw fa-sm fa-graduation-cap mr-2"></i> FOREVER EDUKACIJA</a>
                </li>
                <li class="<?= \Altum\Router::$controller == 'Blog' ? 'active' : null ?> app-sidebar-fcc-item">
                    <a href="<?= fc_get_forever_products_blog_category_url() ?>" id="fcc_dashboard_tour_sidebar_products"><i class="fas fa-fw fa-sm fa-leaf mr-2"></i> FOREVER PROIZVODI</a>
                </li>
        </ul>
    </div>

    <?php if(is_logged_in()): ?>

        <div class="app-sidebar-footer dropdown">
            <a href="#" class="dropdown-toggle dropdown-toggle-simple" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <div class="d-flex align-items-center app-sidebar-footer-block">
                    <img src="<?= get_user_avatar($this->user->avatar, $this->user->email) ?>" class="app-sidebar-avatar mr-3" loading="lazy" />

                    <div class="app-sidebar-footer-text d-flex flex-column text-truncate">
                        <span class="text-truncate"><?= $this->user->name ?></span>
                        <small class="text-truncate"><?= $this->user->email ?></small>
                    </div>
                </div>
            </a>

            <div class="dropdown-menu dropdown-menu-right">
                <?php if(!\Altum\Teams::is_delegated()): ?>
                    <?php if(\Altum\Authentication::is_admin()): ?>
                        <a class="dropdown-item" href="<?= url('admin') ?>"><i class="fas fa-fw fa-sm fa-fingerprint text-primary mr-2"></i> <?= l('global.menu.admin') ?></a>
                        <div class="dropdown-divider"></div>
                    <?php endif ?>

                    <a class="dropdown-item <?= in_array(\Altum\Router::$controller, ['Account']) ? 'active' : null ?>" href="<?= url('account') ?>"><i class="fas fa-fw fa-sm fa-user-cog mr-2"></i> <?= l('account.menu') ?></a>

                    <a class="dropdown-item <?= in_array(\Altum\Router::$controller, ['AccountPreferences']) ? 'active' : null ?>" href="<?= url('account-preferences') ?>"><i class="fas fa-fw fa-sm fa-sliders-h mr-2"></i> <?= l('account_preferences.menu') ?></a>

                    <a class="dropdown-item <?= in_array(\Altum\Router::$controller, ['AccountPlan']) ? 'active' : null ?>" href="<?= url('account-plan') ?>"><i class="fas fa-fw fa-sm fa-box-open mr-2"></i> <?= l('account_plan.menu') ?></a>

                    <?php if(settings()->payment->is_enabled): ?>
                        <a class="dropdown-item <?= in_array(\Altum\Router::$controller, ['AccountPayments']) ? 'active' : null ?>" href="<?= url('account-payments') ?>"><i class="fas fa-fw fa-sm fa-credit-card mr-2"></i> <?= l('account_payments.menu') ?></a>

                        <?php if(\Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled): ?>
                            <a class="dropdown-item <?= in_array(\Altum\Router::$controller, ['Referrals']) ? 'active' : null ?>" href="<?= url('referrals') ?>"><i class="fas fa-fw fa-sm fa-wallet mr-2"></i> <?= l('referrals.menu') ?></a>
                        <?php endif ?>
                    <?php endif ?>

                    <?php if(settings()->main->api_is_enabled): ?>
                        <a class="dropdown-item <?= in_array(\Altum\Router::$controller, ['AccountApi']) ? 'active' : null ?>" href="<?= url('account-api') ?>"><i class="fas fa-fw fa-sm fa-code mr-2"></i> <?= l('account_api.menu') ?></a>
                    <?php endif ?>

                    <?php if(\Altum\Plugin::is_active('teams')): ?>
                        <a class="dropdown-item <?= in_array(\Altum\Router::$controller, ['TeamsSystem', 'Teams', 'Team', 'TeamCreate', 'TeamUpdate', 'TeamsMember', 'TeamsMembers', 'TeamsMemberCreate', 'TeamsMemberUpdate']) ? 'active' : null ?>" href="<?= url('teams-system') ?>"><i class="fas fa-fw fa-sm fa-user-shield mr-2"></i> <?= l('teams_system.menu') ?></a>
                    <?php endif ?>

                    <?php if(settings()->sso->is_enabled && settings()->sso->display_menu_items && count((array) settings()->sso->websites)): ?>
                        <div class="dropdown-divider"></div>

                        <?php foreach(settings()->sso->websites as $website): ?>
                            <a class="dropdown-item" href="<?= url('sso/switch?to=' . $website->id) ?>"><i class="<?= $website->icon ?> fa-fw fa-sm mr-2"></i> <?= sprintf(l('sso.menu'), $website->name) ?></a>
                        <?php endforeach ?>

                        <div class="dropdown-divider"></div>
                    <?php endif ?>
                <?php endif ?>

                <a class="dropdown-item" href="<?= url('logout') ?>"><i class="fas fa-fw fa-sm fa-sign-out-alt mr-2"></i> <?= l('global.menu.logout') ?></a>
            </div>
        </div>

    <?php else: ?>

        <ul class="app-sidebar-links">
            <li>
                <a class="nav-link" href="<?= url('login') ?>"><i class="fas fa-fw fa-sm fa-sign-in-alt mr-2"></i> <?= l('login.menu') ?></a>
            </li>

            <?php if(settings()->users->register_is_enabled): ?>
                <li><a class="nav-link" href="<?= url('register') ?>"><i class="fas fa-fw fa-sm fa-user-plus mr-2"></i> <?= l('register.menu') ?></a></li>
            <?php endif ?>
        </ul>

    <?php endif ?>
</div>

<?php ob_start() ?>
<style>
    .app-sidebar {
        min-width: 272px;
        max-width: 272px;
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, 0.08), transparent 24%),
            linear-gradient(180deg, rgba(17, 24, 39, 0.98), rgba(9, 13, 24, 0.98));
        border: 1px solid rgba(148, 163, 184, 0.12);
        box-shadow: 0 22px 60px rgba(2, 8, 23, 0.38);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    .app-sidebar::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        border-radius: inherit;
        background: linear-gradient(180deg, rgba(255,255,255,0.03), transparent 24%);
    }

    .app-sidebar-title {
        height: auto;
        margin: 0;
        padding: 1rem 1rem .7rem;
    }

    .app-sidebar-title a {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 82px;
        width: 100%;
        border-radius: 1.15rem;
        border: 1px solid rgba(148, 163, 184, 0.1);
        background:
            radial-gradient(circle at top, rgba(73, 227, 207, 0.1), transparent 40%),
            linear-gradient(180deg, rgba(255,255,255,0.025), rgba(255,255,255,0.01));
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .app-sidebar-title .navbar-logo {
        max-height: 2.85rem;
        height: 2.85rem;
    }

    .app-sidebar-links-wrapper {
        width: 100%;
        padding: 0 .8rem .45rem;
        scrollbar-width: none;
    }

    .app-sidebar-links-wrapper:hover {
        width: 100%;
        scrollbar-width: thin !important;
    }

    .app-sidebar-links {
        gap: .08rem;
    }

    .app-sidebar-links > li {
        width: 100%;
        padding: .16rem 0;
    }

    .app-sidebar-links > li > a {
        min-height: 2.95rem;
        border-radius: 1rem;
        padding: .8rem .95rem;
        color: rgba(226, 232, 240, 0.82);
        border: 1px solid transparent;
        background: transparent;
        font-size: .96rem;
        font-weight: 600;
        letter-spacing: -.01em;
        transition: transform .18s ease, background .18s ease, border-color .18s ease, color .18s ease, box-shadow .18s ease;
    }

    .app-sidebar-links > li > a:hover {
        background: rgba(255,255,255,0.05);
        color: #f8fbff;
        border-color: rgba(148, 163, 184, 0.14);
        box-shadow: 0 12px 24px rgba(2, 8, 23, 0.14);
        transform: translateY(-1px);
        text-decoration: none;
    }

    .app-sidebar-links > li.active > a:not(.default) {
        color: #082826;
        background: linear-gradient(135deg, #d5fff8 0%, #b5f5ed 100%);
        border-color: rgba(191, 246, 239, 0.72);
        box-shadow: 0 14px 28px rgba(45, 212, 191, 0.22);
        font-weight: 700;
    }

    [data-theme-style="dark"] .app-sidebar-links > li > a:hover {
        background: rgba(255,255,255,0.05);
        color: #f8fbff;
        border-color: rgba(148, 163, 184, 0.14);
    }

    [data-theme-style="dark"] .app-sidebar-links > li.active > a:not(.default) {
        color: #082826;
        background: linear-gradient(135deg, #d5fff8 0%, #b5f5ed 100%);
        border-color: rgba(191, 246, 239, 0.72);
    }

    .app-sidebar-links > li > a i {
        opacity: .9;
    }

    .app-sidebar-links > li#internal_notifications {
        gap: .45rem;
        align-items: center;
    }

    .app-sidebar-links > li#internal_notifications > a:first-child {
        flex: 1 1 auto;
    }

    .app-sidebar-links > li#internal_notifications > a.default {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.95rem;
        width: 2.95rem;
        min-height: 2.95rem;
        padding: 0;
        border-radius: 1rem;
        border: 1px solid transparent;
        background: transparent;
        color: rgba(226, 232, 240, 0.78);
    }

    .app-sidebar-links > li#internal_notifications > a.default:hover {
        background: rgba(255,255,255,0.05);
        border-color: rgba(148, 163, 184, 0.12);
        color: #fff;
    }

    .app-sidebar-links > .divider-wrapper {
        width: 100%;
        padding: .7rem .05rem .3rem;
        margin: .2rem 0;
    }

    .app-sidebar-links > .divider-wrapper > .divider {
        border-top: 1px solid rgba(148, 163, 184, 0.1);
    }

    .app-sidebar-footer {
        width: 100%;
        padding: .6rem .8rem .85rem;
    }

    .app-sidebar-footer > a {
        width: 100%;
        padding: .8rem .9rem;
        border-top: 0;
        display: flex;
        align-items: center;
        color: rgba(226, 232, 240, 0.82);
        font-size: .92rem;
        font-weight: 600;
        transition: background .25s ease, border-color .25s ease, transform .25s ease;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.08);
        background: rgba(255,255,255,0.03);
    }

    .app-sidebar-footer > a:hover {
        text-decoration: none;
        background: rgba(255,255,255,0.05);
        color: #fff;
        border-color: rgba(148, 163, 184, 0.12);
        transform: translateY(-1px);
    }

    .app-sidebar-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 1px solid rgba(148, 163, 184, 0.14);
        box-shadow: 0 10px 20px rgba(2, 8, 23, 0.2);
    }

    .app-sidebar-footer-block {
        max-width: 100%;
    }

    .app-sidebar-footer-text {
        color: rgba(226, 232, 240, 0.9);
    }

    .app-sidebar-footer-text small {
        color: rgba(148, 163, 184, 0.88);
        font-size: .82rem;
    }

    .app-sidebar .dropdown-menu {
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(15, 23, 42, 0.98);
        box-shadow: 0 24px 44px rgba(2, 8, 23, 0.32);
    }

    .app-sidebar .dropdown-item {
        color: rgba(226, 232, 240, 0.88);
    }

    .app-sidebar .dropdown-item:hover,
    .app-sidebar .dropdown-item:focus,
    .app-sidebar .dropdown-item.active {
        background: rgba(255,255,255,0.06);
        color: #fff;
    }

    .app-sidebar-links > li.app-sidebar-section-label {
        padding: .95rem .45rem .25rem;
        margin-top: .25rem;
    }

    .app-sidebar-links > li.app-sidebar-section-label span {
        display: inline-block;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #73d8ce;
    }

    .app-sidebar-links > li.app-sidebar-fcc-item {
        padding-top: 0.16rem;
        padding-bottom: 0.16rem;
    }

    .app-sidebar-links > li.app-sidebar-fcc-item.app-sidebar-fcc-subitem {
        padding-left: 1.65rem;
    }

    .app-sidebar-links > li.app-sidebar-fcc-item.app-sidebar-fcc-spotlight {
        padding-top: 0.55rem;
        padding-bottom: 0.4rem;
    }

    .app-sidebar-links > li.app-sidebar-fcc-item > a {
        color: #7fe3d9;
        background: rgba(127, 227, 217, 0.05);
        border: 1px solid rgba(127, 227, 217, 0.08);
        border-radius: 1rem;
        font-weight: 600;
        transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }

    .app-sidebar-links > li.app-sidebar-fcc-item > a:hover {
        color: #cffff8;
        background: rgba(127, 227, 217, 0.1);
        border-color: rgba(127, 227, 217, 0.18);
        box-shadow: 0 10px 24px rgba(15, 40, 38, 0.18);
        transform: translateY(-1px);
    }

    .app-sidebar-links > li.app-sidebar-fcc-item.active > a:not(.default) {
        color: #082826;
        background: linear-gradient(135deg, #bff6ef 0%, #8ee9de 100%);
        border-color: rgba(191, 246, 239, 0.75);
        box-shadow: 0 12px 28px rgba(73, 190, 177, 0.24);
        font-weight: 700;
    }

    [data-theme-style="dark"] .app-sidebar-links > li.app-sidebar-fcc-item > a {
        color: #8ce8df;
        background: rgba(127, 227, 217, 0.05);
        border-color: rgba(127, 227, 217, 0.08);
    }

    [data-theme-style="dark"] .app-sidebar-links > li.app-sidebar-fcc-item > a:hover {
        color: #d8fffb;
        background: rgba(127, 227, 217, 0.12);
        border-color: rgba(127, 227, 217, 0.2);
    }

    [data-theme-style="dark"] .app-sidebar-links > li.app-sidebar-fcc-item.active > a:not(.default) {
        color: #082826;
        background: linear-gradient(135deg, #bff6ef 0%, #8ee9de 100%);
        border-color: rgba(191, 246, 239, 0.72);
    }

    .app-sidebar-links > li.app-sidebar-fcc-item > a.disabled {
        opacity: 0.58;
        background: rgba(127, 227, 217, 0.025);
        border-color: transparent;
        box-shadow: none;
        transform: none;
        filter: saturate(0.75) brightness(0.92);
    }

    .app-sidebar-links > li.app-sidebar-fcc-item.app-sidebar-fcc-subitem > a {
        min-height: 3.35rem;
        font-size: 0.86rem;
        border-radius: 13px;
    }

    .app-sidebar-links > li.app-sidebar-fcc-item.app-sidebar-fcc-spotlight > a {
        min-height: 3.6rem;
        color: #f6fbff;
        background: radial-gradient(140px 90px at 12% 8%, rgba(45, 212, 191, 0.22), transparent 60%), linear-gradient(135deg, rgba(21, 33, 54, 0.96) 0%, rgba(13, 22, 38, 0.98) 100%);
        border-color: rgba(86, 168, 255, 0.18);
        box-shadow: 0 14px 30px rgba(2, 12, 28, 0.24), inset 0 1px 0 rgba(255,255,255,0.04);
        font-weight: 700;
    }

    .app-sidebar-links > li.app-sidebar-fcc-item.app-sidebar-fcc-spotlight > a:hover {
        color: #ffffff;
        background: radial-gradient(160px 110px at 12% 8%, rgba(45, 212, 191, 0.28), transparent 62%), linear-gradient(135deg, rgba(24, 37, 61, 0.98) 0%, rgba(14, 24, 42, 1) 100%);
        border-color: rgba(125, 211, 252, 0.26);
        box-shadow: 0 16px 34px rgba(2, 12, 28, 0.28), inset 0 1px 0 rgba(255,255,255,0.05);
    }

    .app-sidebar-links > li.app-sidebar-fcc-item.app-sidebar-fcc-spotlight.active > a:not(.default) {
        color: #061c22;
        background: linear-gradient(135deg, #d8fff8 0%, #92ecdf 58%, #74c8ff 100%);
        border-color: rgba(216, 255, 248, 0.82);
        box-shadow: 0 18px 36px rgba(73, 190, 177, 0.3);
    }

    @media (max-width: 991.98px) {
        .app-sidebar {
            min-width: 282px;
            max-width: 282px;
        }

        .app-sidebar-title {
            padding: .85rem .85rem .55rem;
        }

        .app-sidebar-links-wrapper,
        .app-sidebar-footer {
            padding-left: .7rem;
            padding-right: .7rem;
        }
    }
</style>
<script>
    'use strict';
    
    document.querySelector('ul[class="app-sidebar-links"] li.active') && document.querySelector('ul[class="app-sidebar-links"] li.active').scrollIntoView({ behavior: 'smooth', block: 'center' });
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
