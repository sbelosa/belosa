<?php defined('ALTUMCODE') || die() ?>


<!-- Custom code -->
<?php if(!\Altum\Authentication::check()) {
    /* Custom code: FC-2026-02-26: always capture current biolink as active referral */
    $referral_key = null;

    if(isset($data->link->url) && is_string($data->link->url) && trim($data->link->url) !== '') {
        $referral_key = query_clean(trim($data->link->url));
    }

    if(!$referral_key) {
        $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
        $referral_key = query_clean(trim((string) basename((string) $request_path)));
    }
    /* /Custom code: FC-2026-02-26 */

    if($referral_key) {
    if($biolink = db()->where('url', $referral_key)->where('type', 'biolink')->getOne('links', ['user_id'])) {
        if($user = db()->where('user_id', $biolink->user_id)->getOne('users', ['user_id', 'status', 'referral_key'])) {
            if($user->status == 1) {
                /* Custom code: FC-2026-02-26: keep referral cookie for 365 days */
                setcookie('referral', $referral_key, time()+60*60*24*365, '/');
                /* /Custom code: FC-2026-02-26 */

                /* Custom code: FC-2026-02-26: bridge biolink referral to affiliate tracking */
                if(\Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled && !empty($user->referral_key)) {
                    /* Custom code: FC-2026-02-26: always overwrite affiliate referral cookie on biolink visit */
                    setcookie('referred_by', $user->referral_key, time()+60*60*24*365, COOKIE_PATH);
                    /* /Custom code: FC-2026-02-26 */
                }
                /* /Custom code: FC-2026-02-26 */
            }
        }
    }
    }
} 
?>
<!-- /Custom code -->

<?php /* Custom code: FC-2026-10-12: the new FCC card */
$fcc_card_v2 = function_exists('fcc_card_render_is_v2') && fcc_card_render_is_v2($data->link);
$fcc_card_theme_key = $fcc_card_v2 ? fcc_card_render_theme($data->link, (int) $data->user->user_id) : '';
$fcc_card_style = $fcc_card_v2 ? fcc_card_style_css(fcc_card_render_style($data->link, (int) $data->user->user_id)) : ['css' => '', 'classes' => ''];
$fcc_card_country_shown = false;
/* /Custom code: FC-2026-10-12 */ ?>
<?php if($fcc_card_v2): ?>
<body class="<?= l('direction') == 'rtl' ? 'rtl' : null ?> link-body fcc-card fcc-card--<?= $fcc_card_theme_key ?> <?= htmlspecialchars($fcc_card_style['classes'], ENT_QUOTES, 'UTF-8') ?>"<?= $fcc_card_style['css'] !== '' ? ' style="' . htmlspecialchars($fcc_card_style['css'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<?php else: ?>
<body class="<?= l('direction') == 'rtl' ? 'rtl' : null ?> link-body <?= $data->link->design->background_class ?>" style="<?= $data->link->design->background_style ?>">
<?php endif ?>
<?php if(!empty(settings()->custom->body_content_biolink)): ?>
    <?= settings()->custom->body_content_biolink ?>
<?php endif ?>

<?php if(!$fcc_card_v2 && ((is_string($data->link->settings->background) && string_ends_with('.mp4', $data->link->settings->background)) || isset($_GET['preview']))): ?>
    <video autoplay muted loop playsinline class="link-video-background <?= is_string($data->link->settings->background) && string_ends_with('.mp4', $data->link->settings->background) ? '' : 'd-none' ?>">
        <source src="<?= is_string($data->link->settings->background) && string_ends_with('.mp4', $data->link->settings->background) ? \Altum\Uploads::get_full_url('backgrounds') . $data->link->settings->background : null; ?>" type="video/mp4">
    </video>
<?php endif ?>

<div id="backdrop" class="link-body-backdrop" style="<?= $fcc_card_v2 ? '' : $data->link->design->backdrop_style ?>"></div>

<div class="fcc-biolink-theme-scope">
<div class="container animate__animated animate__fadeIn <?= isset($_GET['preview']) ? 'container-disabled-simple' : null ?>">
    <?php require THEME_PATH . 'views/l/partials/biolink_top_left_buttons.php' ?>
    <?php require THEME_PATH . 'views/l/partials/biolink_share.php' ?>

    <div class="row d-flex justify-content-center text-center">
        <div class="col-md-<?= $data->link->settings->width ?? '8' ?> link-content">

            <?php require THEME_PATH . 'views/l/partials/ads_header_biolink.php' ?>

            <main id="links" class="my-<?= $data->link->settings->block_spacing ?? '2' ?>">
                <div class="row">
                    <?php if($data->link->is_verified): ?>
                        <div id="link-verified-wrapper-top" class="col-12 my-<?= $data->link->settings->block_spacing ?? '2' ?> text-center" style="<?= $data->link->settings->verified_location == 'top' ? null : 'display: none;' ?>">
                            <div>
                                <small class="link-verified" data-toggle="tooltip" title="<?= sprintf(l('link.biolink.verified_help'), settings()->main->title) ?>"><i class="fas fa-fw fa-check-circle fa-1x"></i> <?= l('link.biolink.verified') ?></small>
                            </div>
                        </div>
                    <?php endif ?>

                     <?php if($data->biolink_blocks): ?>
                        <?php
                        /* Custom code: FC-2026-03-05: robust geo lookup IP selection behind proxies/docker */
                        $geo_lookup_ip = get_ip();

                        if(
                            !$geo_lookup_ip
                            || !filter_var($geo_lookup_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                        ) {
                            foreach(['HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED', 'HTTP_CLIENT_IP'] as $ip_header_key) {
                                if(empty($_SERVER[$ip_header_key])) {
                                    continue;
                                }

                                $ip_candidates = [$_SERVER[$ip_header_key]];

                                if(in_array($ip_header_key, ['HTTP_X_FORWARDED_FOR', 'HTTP_FORWARDED_FOR', 'HTTP_X_FORWARDED'])) {
                                    $ip_candidates = array_map('trim', explode(',', (string) $_SERVER[$ip_header_key]));
                                }

                                if($ip_header_key === 'HTTP_FORWARDED') {
                                    $ip_candidates = [];

                                    if(preg_match_all('/for="?\[?([a-fA-F0-9:\.]+)\]?"?/i', (string) $_SERVER[$ip_header_key], $matches)) {
                                        $ip_candidates = array_map('trim', $matches[1]);
                                    }
                                }

                                foreach($ip_candidates as $ip_candidate) {
                                    if(filter_var($ip_candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                                        $geo_lookup_ip = $ip_candidate;
                                        break 2;
                                    }
                                }
                            }
                        }
                        /* /Custom code: FC-2026-03-05 */

                        /* Detect the location */
                        try {
                            $maxmind = (new \MaxMind\Db\Reader(APP_PATH . 'includes/GeoLite2-City.mmdb'))->get($geo_lookup_ip);
                        } catch(\Exception $exception) {
                            /* :) */
                        }
                        /* Detect extra details about the user */
                        $whichbrowser = get_whichbrowser();
                        $os_name = $whichbrowser->os->name ?? null;
                        $browser_name = $whichbrowser->browser->name ?? null;
                        $accept_language_header = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null;
                        $browser_language = $accept_language_header ? mb_substr($accept_language_header, 0, 2) : null;
                        $country_code = strtoupper((string)fcc_forever_country_context()['country']) ?: null;
                        $country_code_is_trusted = true;
                        $forever_id = $data->user->preferences->meta->foreverId ?? '';
                        $forever_webshop_links = settings()->links->forever_webshop_links ?? new \StdClass();
                        $forever_business_links = settings()->links->forever_business_links ?? new \StdClass();
                        /* /Custom code: FC-2026-03-05 */
                        $city_name = isset($maxmind) && isset($maxmind['city']) ? $maxmind['city']['names']['en'] : null;
                        $continent_code = isset($maxmind) && isset($maxmind['continent']) ? $maxmind['continent']['code'] : null;
                        $device_type = get_this_device_type();
                        ?>

                        <?php foreach($data->biolink_blocks as $row): ?>

                            <?php
                            $row->settings = json_decode($row->settings ?? '');
                            /* Custom code: FC-2026-10-12: factory labels in the visitor's language; the new card retires the phone and e-mail forms */
                            if(function_exists('fcc_card_localize_factory_label')) fcc_card_localize_factory_label($row);
                            if($fcc_card_v2 && in_array($row->type, FCC_CARD_RETIRED_BLOCKS, true)) continue;
                            fcc_card_whatsapp_phone($row, $data->user, $fcc_card_v2);
                            if($fcc_card_v2) fcc_card_tidy_label($row);
                            /* /Custom code: FC-2026-10-12 */

                            /* Resolve localized product copy and always retain the card owner. */
                            if($row->type === 'link_forever_product' && !fcc_product_render_block($row, $data->link)) continue;

                            /* Check if its a scheduled link and we should show it or not */
                            if(
                                !empty($row->start_date) &&
                                !empty($row->end_date) &&
                                (
                                    \Altum\Date::get('', null) < \Altum\Date::get($row->start_date, null, \Altum\Date::$default_timezone) ||
                                    \Altum\Date::get('', null) > \Altum\Date::get($row->end_date, null, \Altum\Date::$default_timezone)
                                )
                            ) {
                                continue;
                            }

                            /* Custom code: FC-2026-03-06: strict plan-based final block visibility */
                            $enabled_biolink_blocks_for_plan = $data->user->plan_settings->enabled_biolink_blocks ?? (object) [];

                            if(is_string($enabled_biolink_blocks_for_plan)) {
                                $enabled_biolink_blocks_for_plan = json_decode($enabled_biolink_blocks_for_plan) ?: (object) [];
                            }

                            if(is_array($enabled_biolink_blocks_for_plan)) {
                                $enabled_biolink_blocks_for_plan = (object) $enabled_biolink_blocks_for_plan;
                            }

                            $is_biolink_block_enabled_for_plan = (bool) ($enabled_biolink_blocks_for_plan->{$row->type} ?? false);

                            if(
                                !$is_biolink_block_enabled_for_plan &&
                                $row->type === 'vip_funnel_hub' &&
                                function_exists('vip_funnel_user_can_publish_public_hub') &&
                                vip_funnel_user_can_publish_public_hub($data->user)
                            ) {
                                $is_biolink_block_enabled_for_plan = true;
                            }

                            if(!$is_biolink_block_enabled_for_plan) {
                                if(
                                    $row->type === 'link_forever_webshop_reg'
                                    && (
                                        !empty($enabled_biolink_blocks_for_plan->link_forever_shop)
                                        || !empty($enabled_biolink_blocks_for_plan->link_discount)
                                    )
                                ) {
                                    $is_biolink_block_enabled_for_plan = true;
                                }
                            }

                            // The initial NFC card includes a shopping action for every package.
                            if(!$is_biolink_block_enabled_for_plan && in_array($row->type,['link_forever_webshop_reg','link_forever_shop'],true)
                                && !empty($row->settings->factory_shop)
                                && (int)fc_get_user_main_biolink_id((int)$data->user->user_id, false) === (int)$data->link->link_id) {
                                $is_biolink_block_enabled_for_plan = true;
                            }

                            if(in_array($row->type,['link_forever_shop','link_forever_webshop_reg'],true))$is_biolink_block_enabled_for_plan=true;
                            /* Custom code: FC-2026-10-12: the new card shows its core blocks on every package */
                            if($fcc_card_v2 && fcc_card_core_block($row->type)) $is_biolink_block_enabled_for_plan = true;
                            /* /Custom code: FC-2026-10-12 */
                            if(!$is_biolink_block_enabled_for_plan) {
                                continue;
                            }
                            /* /Custom code: FC-2026-03-06 */

                            /* Custom code */
                            if(
                                !isset($_GET['preview']) &&
                                $row->settings->display_countries
                                && (!$country_code || !in_array($country_code, $row->settings->display_countries))
                                && $row->type != 'link_discount'
                                && $row->type != 'link_forever_webshop_reg'
                                && $row->type != 'link_forever_living_bih'
                                && !in_array($row->type, ['link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'])
                            ) {
                                continue;
                            }
                            /* /Custom code */

                            /* Check if there are any extra display rules */
                            if(!isset($_GET['preview']) && $continent_code && count($row->settings->display_continents ?? []) && !in_array($continent_code, $row->settings->display_continents ?? [])) {
                                continue;
                            }
                            if(!isset($_GET['preview']) && $country_code && count($row->settings->display_countries ?? []) && !in_array($country_code, $row->settings->display_countries ?? [])) {
                                continue;
                            }
                            if(!isset($_GET['preview']) && $city_name && count($row->settings->display_cities ?? []) && !in_array($city_name, $row->settings->display_cities ?? [])) {
                                continue;
                            }
                            if(!isset($_GET['preview']) && $device_type && count($row->settings->display_devices ?? []) && !in_array($device_type, $row->settings->display_devices ?? [])) {
                                continue;
                            }
                            if(!isset($_GET['preview']) && $browser_language && count($row->settings->display_languages ?? []) && !in_array($browser_language, $row->settings->display_languages ?? [])) {
                                continue;
                            }
                            if(!isset($_GET['preview']) && $os_name && count($row->settings->display_operating_systems ?? []) && !in_array($os_name, $row->settings->display_operating_systems ?? [])) {
                                continue;
                            }
                            if(!isset($_GET['preview']) && $browser_name && count($row->settings->display_browsers ?? []) && !in_array($browser_name, $row->settings->display_browsers ?? [])) {
                                continue;
                            }
                            /* Custom code */
                            if(!isset($_GET['preview']) && $row->type == 'link_homescreen_android' && $os_name != 'Android') {
                                continue;
                            } 
                            if(!isset($_GET['preview']) && $row->type == 'link_homescreen_android' && $os_name == 'Android') {
                                if(substr($whichbrowser->browser->toString(), 0, 6) != 'Chrome') {
                                 continue;
                                }                                
                            }                            
                            if(!isset($_GET['preview']) && $row->type == 'link_homescreen_ios' && $os_name != 'iOS') {
                                continue;
                            }
                            if(!isset($_GET['preview']) && $row->type == 'link_homescreen_ios' && $os_name == 'iOS') {
                                if(substr($whichbrowser->browser->toString(), 0, 6) != 'Safari') {
                                    continue;
                                }                                
                            }                              
                            /* Custom code: FC-2026-10-12: "Save contact" always saves the owner's own contact */
                            if($row->type === 'link_save_contact' && function_exists('fcc_card_fix_save_contact') && !fcc_card_fix_save_contact($row, (int) $data->user->user_id)) continue;
                            /* /Custom code: FC-2026-10-12 */
                            if(in_array($row->type,['link_forever_shop','link_forever_webshop_reg','link_discount','link_forever_living_bih','link_forever_living_alb_kosovo'],true)) {
                                $row->location_url=fcc_forever_entry_url((string)$data->link->url,$row->type==='link_forever_shop'?'business':'shop',(int)$row->biolink_block_id);
                            }
                            if(!empty($row->location_url) && !in_array($row->type, ['link_forever_shop', 'link_forever_webshop_reg', 'link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo'], true)) {
                                $row->location_url = fcc_forever_owned_destination((string) $row->location_url, (string) $data->link->url, $data->user->preferences);
                            }
                            if($row->type === 'image_slider') {
                                foreach($row->settings->items ?? [] as $item) {
                                    if(is_object($item) && !empty($item->location_url)) $item->location_url = fcc_forever_owned_destination((string) $item->location_url, (string) $data->link->url, $data->user->preferences);
                                }
                            }

                            if(in_array($row->type, ['custom_html_chatbot', 'custom_html_chatbot_pets'], true) && (!isset($row->settings) || !is_object($row->settings))) {
                                $row->settings = new \stdClass();
                            }

                            /* /Custom code */

                            $row->utm = $data->link->settings->utm;
                            ?>

                            <?php /* Custom code: FC-2026-10-12: new card markup hooks, readable country line shown once */
                            $fcc_block_html = (string) (\Altum\Link::get_biolink_link($row, $data->user, $this->biolink_theme ?? null, $data->link) ?? '');
                            echo $fcc_card_v2 ? fcc_card_mark_block($fcc_block_html, $row->type, (array) ($row->settings->fcc_style ?? [])) : $fcc_block_html;
                            if($fcc_card_v2 && $row->type === 'custom_html_chatbot' && function_exists('fcc_ai_user_has_public_ai_access') && fcc_ai_user_has_public_ai_access((int) $data->user->user_id)): ?>
                                <div class="col-12" data-fcc-type="advisor" data-biolink-block-id="<?= (int) $row->biolink_block_id ?>"><button type="button" class="fcc-card-advisor" onclick="var t=document.getElementById('<?= 'fcc-chatbot-' . (int) $row->biolink_block_id . '-toggle' ?>');if(t)t.click();"><span class="fcc-card-advisor-icon" aria-hidden="true"><i class="fas fa-wand-magic-sparkles"></i></span><span><strong><?= htmlspecialchars(fcc_t('Pitaj savjetnika'), ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars(fcc_t('Opiši što trebaš, a savjetnik predloži proizvod.'), ENT_QUOTES, 'UTF-8') ?></small></span></button></div>
                            <?php endif;
                            if(!$fcc_card_country_shown && in_array($row->type, ['link_forever_shop', 'link_forever_webshop_reg', 'link_discount'], true) && $fcc_block_html !== '') {
                                $fcc_card_country_shown = true;
                                echo fcc_card_country_line($country_code, $row->location_url . '&choose=1', $fcc_card_v2, (string) ($data->link->settings->text_color ?? ''));
                            }
                            /* /Custom code: FC-2026-10-12 */ ?>

                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </main>

            <?php require THEME_PATH . 'views/l/partials/ads_footer_biolink.php' ?>

            <footer id="footer" class="link-footer">
                <?php if($data->link->is_verified): ?>
                    <div id="link-verified-wrapper-bottom" class="my-<?= $data->link->settings->block_spacing ?? '2' ?>" style="<?= $data->link->settings->verified_location == 'bottom' ? null : 'display: none;' ?>">
                        <small class="link-verified" data-toggle="tooltip" title="<?= sprintf(l('link.biolink.verified_help'), settings()->main->title) ?>"><i class="fas fa-fw fa-check-circle fa-1x"></i> <?= l('link.biolink.verified') ?></small>
                    </div>
                <?php endif ?>

                <div id="branding" class="link-footer-branding">
                    <?php /* Custom code: FC-2026-10-12: footer in the visitor's language */ if($fcc_card_v2): ?>
                        <?= fcc_card_footer_html(true) ?>
                    <?php elseif($data->link->settings->display_branding && empty($data->link->settings->branding->name ?? '') && function_exists('fcc_card_footer_html')): ?>
                        <span style="<?= $data->link->design->text_style ?>"><?= fcc_card_footer_html(false) ?></span>
                    <?php elseif($data->link->settings->display_branding): ?><?php /* /Custom code: FC-2026-10-12 */ ?>
                        <?php if(isset($data->link->settings->branding, $data->link->settings->branding->name, $data->link->settings->branding->url) && !empty($data->link->settings->branding->name)): ?>
                            <a href="<?= !empty($data->link->settings->branding->url) ? $data->link->settings->branding->url : '#' ?>" style="<?= $data->link->design->text_style ?>"><?= $data->link->settings->branding->name ?></a>
                        <?php else: ?>

                            <?php
                            $replacers = [
                                '{{URL}}' => url(),
                                '{{DASHBOARD_LINK}}' => url('dashboard'),
                                '{{WEBSITE_TITLE}}' => settings()->main->title,
                                '{{AFFILIATE_URL_TAG}}' => \Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled ? '?ref=' . $data->user->referral_key : null,
                            ];

                            settings()->links->branding = str_replace(
                                array_keys($replacers),
                                array_values($replacers),
                                settings()->links->branding
                            );
                            ?>

                            <?= settings()->links->branding ?>
                        <?php endif ?>
                    <?php endif ?>
                </div>
            </footer>

        </div>
    </div>
</div>
</div>

<?= \Altum\Event::get_content('modals') ?>
</body>

<?php ob_start() ?>
<script>
    'use strict';

    /* Background backdrop fix on modal */
    let backdrop_filter = null;
    $('.modal').on('show.bs.modal', function () {
        backdrop_filter = document.querySelector('body').style.backdropFilter;
        document.querySelector('body').style.backdropFilter = '';
    });

    $('.modal').on('hide.bs.modal', function () {
        document.querySelector('body').style.backdropFilter = backdrop_filter;
    });

    /* Custom code: FC-2026-03-07: preserve first-touch traffic source for biolink block click tracking */
    const biolink_attribution_storage_key = 'fcc_biolink_first_touch_source';

    const normalize_traffic_source = value => {
        if(!value) {
            return '';
        }

        value = String(value).toLowerCase().trim();

        if(value.startsWith('utm:')) {
            value = value.substring(4);
        }

        value = value
            .replace(/^https?:\/\//, '')
            .replace(/:\d+$/, '')
            .replace(/^www\./, '')
            .replace(/^m\./, '')
            .replace(/^l\./, '');

        if(value.includes('/')) {
            value = value.split('/')[0];
        }

        if(value === 'fb' || value.includes('facebook')) {
            return 'facebook';
        }

        if(value === 'ig' || value.includes('instagram')) {
            return 'instagram';
        }

        if(value.includes('whatsapp') || value === 'wa') {
            return 'whatsapp';
        }

        if(value.includes('tiktok')) {
            return 'tiktok';
        }

        if(value.includes('youtube') || value === 'youtu.be') {
            return 'youtube';
        }

        if(value.includes('telegram')) {
            return 'telegram';
        }

        if(value.includes('viber')) {
            return 'viber';
        }

        if(value.includes('google') || value === 'gclid') {
            return 'google';
        }

        if(value.includes('linkedin')) {
            return 'linkedin';
        }

        return value;
    };

    const read_persisted_traffic_source = () => {
        try {
            return normalize_traffic_source(sessionStorage.getItem(biolink_attribution_storage_key) ?? '');
        } catch(error) {
            return '';
        }
    };

    const persist_traffic_source = source => {
        if(!source) {
            return;
        }

        try {
            sessionStorage.setItem(biolink_attribution_storage_key, source);
        } catch(error) {
            // ignored
        }
    };

    const resolve_first_touch_traffic_source = () => {
        const current_url = new URL(window.location.href);
        const utm_source = normalize_traffic_source(current_url.searchParams.get('utm_source'));

        if(utm_source) {
            return utm_source;
        }

        const current_host = normalize_traffic_source(window.location.host);
        const referrer = document.referrer;

        if(referrer) {
            try {
                const referrer_host = normalize_traffic_source((new URL(referrer)).host);

                if(referrer_host && referrer_host !== current_host) {
                    return referrer_host;
                }
            } catch(error) {
                // ignored
            }
        }

        return '';
    };

    const detected_first_touch_source = resolve_first_touch_traffic_source();
    if(detected_first_touch_source) {
        persist_traffic_source(detected_first_touch_source);
    }

    /* Internal tracking for biolink page blocks */
    document.querySelectorAll('a[data-track-biolink-block-id]').forEach(element => {
        element.addEventListener('click', event => {
            if (new URL(event.currentTarget.href).pathname.endsWith('/forever-shop')) return;
            let biolink_block_id = event.currentTarget.getAttribute('data-track-biolink-block-id');
            let beacon_url = `${site_url}l/link?biolink_block_id=${biolink_block_id}&no_redirect`;

            const attributed_source = read_persisted_traffic_source();
            if(attributed_source) {
                beacon_url += `&utm_source=${encodeURIComponent(attributed_source)}`;
            }

            navigator.sendBeacon(beacon_url);
        });
    });
    /* /Custom code: FC-2026-03-07 */

    /* Fix CSS when using scroll for background attachment on long content */
    if(document.body.offsetHeight > window.innerHeight) {
        let background_attachment = document.querySelector('body').style.backgroundAttachment;
        if(background_attachment == 'scroll') {
            document.documentElement.style.height = 'auto';
        }
    }
</script>

<!-- Custom code: FC-2026-08-01: safely initialize optional biolink PWA controls -->
<script>
    const manifest_placeholder = document.querySelector('#my-manifest-placeholder');

    if(manifest_placeholder) {
        const manifest_start_url = new URL(window.location.href);
        manifest_start_url.search = '';
        manifest_start_url.hash = '';

        const dynamic_manifest = {
            "name": "Forever Card Club",
            "short_name": "Forever Card Club",
            "display": "standalone",
            "start_url": manifest_start_url.href,
            "background_color": "#ffffff",
            "theme_color": "#000000",
            "icons": [
                {
                    "src": "icon-192.png",
                    "sizes": "192x192",
                    "type": "image/png"
                },
                {
                    "src": "icon-512.png",
                    "sizes": "512x512",
                    "type": "image/png"
                }
            ]
        };
        const manifest_blob = new Blob([JSON.stringify(dynamic_manifest)], {type: 'application/json'});
        const manifest_url = URL.createObjectURL(manifest_blob);
        manifest_placeholder.setAttribute('href', manifest_url);
    }

    if('serviceWorker' in navigator) {
        navigator.serviceWorker.register('service-worker.js').catch(() => {});
    }

    const add_to_home_screen = document.getElementById('addToHomeScreen');
    let deferred_install_prompt;

    window.addEventListener('beforeinstallprompt', event => {
        if(!add_to_home_screen) {
            return;
        }

        event.preventDefault();
        deferred_install_prompt = event;
        add_to_home_screen.style.display = 'block';
    });

    if(add_to_home_screen) {
        add_to_home_screen.addEventListener('click', event => {
            event.preventDefault();

            if(deferred_install_prompt) {
                deferred_install_prompt.prompt();
                deferred_install_prompt.userChoice.then(() => {
                    deferred_install_prompt = null;
                });
            }
        });
    }
</script>
<!-- /Custom code: FC-2026-08-01 -->

<?= $this->views['pixels'] ?? null ?>

<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
