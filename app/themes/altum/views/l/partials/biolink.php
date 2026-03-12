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

<body class="<?= l('direction') == 'rtl' ? 'rtl' : null ?> link-body <?= $data->link->design->background_class ?>" style="<?= $data->link->design->background_style ?>">
<?php if(!empty(settings()->custom->body_content_biolink)): ?>
    <?= settings()->custom->body_content_biolink ?>
<?php endif ?>

<?php if((is_string($data->link->settings->background) && string_ends_with('.mp4', $data->link->settings->background)) || isset($_GET['preview'])): ?>
    <video autoplay muted loop playsinline class="link-video-background <?= is_string($data->link->settings->background) && string_ends_with('.mp4', $data->link->settings->background) ? '' : 'd-none' ?>">
        <source src="<?= is_string($data->link->settings->background) && string_ends_with('.mp4', $data->link->settings->background) ? \Altum\Uploads::get_full_url('backgrounds') . $data->link->settings->background : null; ?>" type="video/mp4">
    </video>
<?php endif ?>

<div id="backdrop" class="link-body-backdrop" style="<?= $data->link->design->backdrop_style ?>"></div>

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
                        /* Detect the location */
                        try {
                            $maxmind = (new \MaxMind\Db\Reader(APP_PATH . 'includes/GeoLite2-City.mmdb'))->get(get_ip());
                        } catch(\Exception $exception) {
                            /* :) */
                        }
                        /* Detect extra details about the user */
                        $whichbrowser = get_whichbrowser();
                        $os_name = $whichbrowser->os->name ?? null;
                        $browser_name = $whichbrowser->browser->name ?? null;
                        $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
                        $country_code = isset($maxmind) && isset($maxmind['country']) ? $maxmind['country']['iso_code'] : null;
                        $city_name = isset($maxmind) && isset($maxmind['city']) ? $maxmind['city']['names']['en'] : null;
                        $continent_code = isset($maxmind) && isset($maxmind['continent']) ? $maxmind['continent']['code'] : null;
                        $device_type = get_this_device_type();
                        ?>

                        <?php foreach($data->biolink_blocks as $row): ?>

                            <?php
                            $row->settings = json_decode($row->settings ?? '');

                            /* Custom code */
                            if (($row->type == 'link' && $row->settings->name == 'FOREVER WEB SHOP') || $row->type == 'link_forever_shop' || $row->location_url == 'https://businesscard.club/en/forevershop' || $row->location_url == 'https://foreverliving.com' || $row->location_url == 'https://foreverliving.com/') {
                                if (isset($data->user->preferences->meta->foreverId)) {
                                    $forever_id = $data->user->preferences->meta->foreverId;
                                } else {
                                    $forever_id = '';
                                }
                                
                                if (isset($country_code)) {
                                    switch ($country_code) {
                                        /*case 'HR':
                                            $url = 'https://www.foreverliving.hr/?id=' . $forever_id;
                                            break;*/
                                        
                                        case 'BA':
                                            $url = 'https://www.flpshop.ba/?id=' . $forever_id;
                                            break;

                                        case 'AL':
                                            $url = 'https://www.foreveralbania.com/?id=' . $forever_id;
                                            break;

                                        case 'XK':
                                            $url = 'https://www.foreveralbania.com/?id=' . $forever_id;
                                            break;
                                
                                        /*case 'SI':
                                            $url = 'https://' . $forever_id . '.webshop.forever.si';
                                            break; */
                                        
                                        /*case 'RS':
                                            $url = 'https://www.flpshop.rs/forever-paketi/12560/start-your-journey-pack/'. $forever_id . '/personal.html';
                                            break;  
                                        */

                                        default:
                                            $url = 'https://www.foreverliving.com/?fboId=' . $forever_id;
                                            break;
                                    }
                                } else if (isset($browser_language)) {
                                    switch ($browser_language) {
                                       /* case 'hr':
                                            $url = 'https://www.foreverliving.hr/?id=' . $forever_id;
                                            break;*/
                                        
                                        case 'ba':
                                            $url = 'https://www.flpshop.ba/?id=' . $forever_id;
                                            break;

                                        case 'al':
                                            $url = 'https://www.foreveralbania.com/?id=' . $forever_id;
                                            break;

                                        case 'xk':
                                            $url = 'https://www.foreveralbania.com/?id=' . $forever_id;
                                            break;
                                
                                        /*case 'sl':
                                            $url = 'https://' . $forever_id . '.webshop.forever.si';
                                            break; */

                                        /*case 'rs':
                                            $url = 'https://www.flpshop.rs/forever-paketi/12560/start-your-journey-pack/'. $forever_id . '/personal.html';
                                            break;                                     
                                        */
                                        
                                        default:
                                            $url = 'https://www.foreverliving.com/?fboId=' . $forever_id;
                                            break; 
                                    }
                                } else {
                                    $url = 'https://www.foreverliving.com/?fboId=' . $forever_id;
                                }

                                $row->location_url = $url;
                            }
                            /* /Custom code */

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

                            /* Check if the user has permissions to use the link */
                            /* Custom code: FC-2026-02-27: fallback render for new pets ai block when plan key is missing */
                            $is_biolink_block_enabled_for_plan = $data->user->plan_settings->enabled_biolink_blocks->{$row->type} ?? in_array($row->type, ['custom_html_chatbot_pets', 'link_app_switcher', 'link_back']);
                            if(!$is_biolink_block_enabled_for_plan) {
                                continue;
                            }
                            /* /Custom code: FC-2026-02-27 */

                            /* Custom code */
                            if($row->settings->display_countries && !in_array($country_code, $row->settings->display_countries) && $row->type != 'link_discount') {
                                continue;
                            }
                            /* /Custom code */

                            /* Check if there are any extra display rules */
                            if($continent_code && count($row->settings->display_continents ?? []) && !in_array($continent_code, $row->settings->display_continents ?? [])) {
                                continue;
                            }
                            if($country_code && count($row->settings->display_countries ?? []) && !in_array($country_code, $row->settings->display_countries ?? [])) {
                                continue;
                            }
                            if($city_name && count($row->settings->display_cities ?? []) && !in_array($city_name, $row->settings->display_cities ?? [])) {
                                continue;
                            }
                            if($device_type && count($row->settings->display_devices ?? []) && !in_array($device_type, $row->settings->display_devices ?? [])) {
                                continue;
                            }
                            if($browser_language && count($row->settings->display_languages ?? []) && !in_array($browser_language, $row->settings->display_languages ?? [])) {
                                continue;
                            }
                            if($os_name && count($row->settings->display_operating_systems ?? []) && !in_array($os_name, $row->settings->display_operating_systems ?? [])) {
                                continue;
                            }
                            if($browser_name && count($row->settings->display_browsers ?? []) && !in_array($browser_name, $row->settings->display_browsers ?? [])) {
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
                            if($row->type == 'link_discount' && mb_strtoupper((string) $country_code) !== 'BA' && !filter_var($row->settings->decoded_url, FILTER_VALIDATE_URL)) {
                                continue;
                            }                    
                            if($row->type == 'link_discount') {                                
                                $destination_url = $row->settings->decoded_url;
                        
                                $parsed_url = parse_url($destination_url);
                                $host = $parsed_url['host'];
                                $path = $parsed_url['path'];
                                $path_explode = explode('/', $parsed_url['path']);
                                

                                if ($host == 'foreverliving.com' && isset($path_explode[1]) && $path_explode[1] == 'shop') {
                                    parse_str($parsed_url['query'], $params);

                                    if (isset($params['fboId'])) {
                                        $fbo_id = $params['fboId'];
                                    }
                                    if (isset($params['discountConfigType'])) {
                                        $discount_type = $params['discountConfigType'];
                                    }
                                    if (isset($params['uniqueExtRefID'])) {
                                        $unique_uid = $params['uniqueExtRefID'];
                                    }
                                    if (isset($params['referralUuid'])) {
                                        $refferal_uid = $params['referralUuid'];
                                    }

                                    if (isset($discount_type) && isset($unique_uid) && isset($refferal_uid) && isset($fbo_id)) {
                                        $country_code = strtolower($country_code);                                        
                                        $destination_url = 'https://' . $host . '?fboId=' . $fbo_id . '&discountConfigType=' . $discount_type . '&uniqueExtRefID=' . $unique_uid . '&referralUuid=' . $refferal_uid;                                        

                                        $row->location_url = $destination_url;
                                    }
                                }
                            }

                            if ($row->type == 'custom_html_chatbot') {
                                /* Custom code: FC-2026-02-27: chatbot embed fallback only */
                                $default_chatbot_embed = "<script async type='module' src='https://interfaces.zapier.com/assets/web-components/zapier-interfaces/zapier-interfaces.esm.js'></script>\n<zapier-interfaces-chatbot-embed is-popup='true' chatbot-id='cm8g6mg77000qyrrw89x3vadl'></zapier-interfaces-chatbot-embed>";
                                if(!isset($row->settings) || !is_object($row->settings)) {
                                    $row->settings = new \stdClass();
                                }

                                if(empty(trim((string) ($row->settings->html ?? '')))) {
                                    $row->settings->html = $default_chatbot_embed;
                                }
                                /* /Custom code: FC-2026-02-27 */
                            } 

                            /* Custom code: FC-2026-02-27: pets chatbot embed fallback only */
                            if ($row->type == 'custom_html_chatbot_pets') {
                                $default_pets_chatbot_embed = "<script async type='module' src='https://interfaces.zapier.com/assets/web-components/zapier-interfaces/zapier-interfaces.esm.js'></script>\n<zapier-interfaces-chatbot-embed is-popup='true' chatbot-id='cm8owjjbg000r9mozayq4gksd'></zapier-interfaces-chatbot-embed>";
                                if(!isset($row->settings) || !is_object($row->settings)) {
                                    $row->settings = new \stdClass();
                                }

                                if(empty(trim((string) ($row->settings->html ?? '')))) {
                                    $row->settings->html = $default_pets_chatbot_embed;
                                }
                            }
                            /* /Custom code: FC-2026-02-27 */

                            if ($row->type == 'custom_html_whatsapp') {
                                $button = $row->settings->title ?? ($row->settings->button ?? 'WhatsApp');
                                $phone = preg_replace('/[^\p{L}\p{N}\s]/u', '', $row->settings->phone);
                                $message = rawurlencode($row->settings->message);
                                $button_icon = $row->settings->icon ?? 'fab fa-whatsapp';
                                $button_border_radius = $row->settings->border_radius ?? 'rounded';

                                $whatsapp_link_style = \Altum\Link::get_processed_link_style($row->settings);
                                if(!empty($row->settings->border_shadow_style) && $row->settings->border_shadow_style !== 'none') {
                                    $whatsapp_link_style['style'] .= \Altum\Link::get_processed_box_shadow_style($row->settings);
                                }

                                $button_style = $whatsapp_link_style['style'];
                                $button_extra_class = $whatsapp_link_style['class'];
                                $hover_class = ($data->link->settings->hover_animation ?? 'smooth') != 'false' ? 'link-hover-animation-' . ($data->link->settings->hover_animation ?? 'smooth') : null;

                                require THEME_PATH . 'views/l/partials/whatsapp.php';
                            }
                            /* /Custom code */

                            $row->utm = $data->link->settings->utm;
                            ?>

                            <?= \Altum\Link::get_biolink_link($row, $data->user, $this->biolink_theme ?? null, $data->link) ?? null ?>

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
                    <?php if($data->link->settings->display_branding): ?>
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

    /* Internal tracking for biolink page blocks */
    document.querySelectorAll('a[data-track-biolink-block-id]').forEach(element => {
        element.addEventListener('click', event => {
            let biolink_block_id = event.currentTarget.getAttribute('data-track-biolink-block-id');
            navigator.sendBeacon(`${site_url}l/link?biolink_block_id=${biolink_block_id}&no_redirect`);
        });
    });

    /* Fix CSS when using scroll for background attachment on long content */
    if(document.body.offsetHeight > window.innerHeight) {
        let background_attachment = document.querySelector('body').style.backgroundAttachment;
        if(background_attachment == 'scroll') {
            document.documentElement.style.height = 'auto';
        }
    }
</script>

<!-- Custom code -->
<script>
    var urlinput = 'https://forevercard.club/' + document.getElementById("urlinput").value;
    var myDynamicManifest = {
        "name": "Forever Card Club",
        "short_name": "Forever Card Club",
        "display": "standalone",
        "start_url": urlinput,
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
    }
    const stringManifest = JSON.stringify(myDynamicManifest);
    const blob = new Blob([stringManifest], {type: 'application/json'});
    const manifestURL = URL.createObjectURL(blob);
    document.querySelector('#my-manifest-placeholder').setAttribute('href', manifestURL);
</script>

<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('service-worker.js')
        .then(() => console.log("Service Worker Registered"));
    }
</script>

<script>
    let deferredPrompt;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        document.getElementById("addToHomeScreen").style.display = "block";
    });

    document.getElementById("addToHomeScreen").addEventListener("click", (e) => {  
        e.preventDefault();          
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(choice => {
                if (choice.outcome === "accepted") {
                    //console.log("Aplikacija dodana!");
                }
                deferredPrompt = null;
            });
        }
    });
</script>
<!-- /Custom code -->

<?= $this->views['pixels'] ?? null ?>

<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>