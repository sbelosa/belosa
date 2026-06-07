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
                        $country_code = null;
                        $country_code_is_trusted = false;
                        /* Custom code: FC-2026-03-05: robust country detection fallback for BIH-only block display */
                        foreach(['HTTP_CF_IPCOUNTRY', 'HTTP_CF-IPCOUNTRY', 'GEOIP_COUNTRY_CODE', 'HTTP_GEOIP_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_COUNTRY'] as $country_header_key) {
                            if(!empty($_SERVER[$country_header_key])) {
                                $header_country_code = mb_strtoupper(trim((string) $_SERVER[$country_header_key]));
                                $header_country_code = mb_substr($header_country_code, 0, 2);

                                if(mb_strlen($header_country_code) == 2 && $header_country_code !== 'XX') {
                                    $country_code = $header_country_code;
                                    $country_code_is_trusted = true;
                                    break;
                                }
                            }
                        }

                        if(!$country_code) {
                            $country_code = \Altum\Link::get_external_geo_country_code($geo_lookup_ip);
                        }

                        if(!$country_code && isset($maxmind['country']['iso_code'])) {
                            $country_code = $maxmind['country']['iso_code'];
                        }

                        if($country_code) {
                            $country_code = mb_strtoupper((string) $country_code);
                        }

                        $forever_id = $data->user->preferences->meta->foreverId ?? '';
                        $forever_webshop_links = settings()->links->forever_webshop_links ?? new \StdClass();
                        $forever_business_links = settings()->links->forever_business_links ?? new \StdClass();
                        /* /Custom code: FC-2026-03-05 */
                        $city_name = isset($maxmind) && isset($maxmind['city']) ? $maxmind['city']['names']['en'] : null;
                        $continent_code = isset($maxmind) && isset($maxmind['continent']) ? $maxmind['continent']['code'] : null;
                        $device_type = get_this_device_type();
                        $fcc_blog_language_names_by_code = array_flip((array) \Altum\Language::$active_languages);
                        $fcc_forever_product_translation_cache = [];
                        $fcc_forever_product_id_cache = [];
                        $fcc_resolve_forever_product = static function(string $translation_key, string $target_language_code, string $fallback_language_code = '') use (&$fcc_forever_product_translation_cache, $fcc_blog_language_names_by_code) {
                            $translation_key = trim($translation_key);
                            $target_language_code = trim($target_language_code);
                            $fallback_language_code = trim($fallback_language_code);

                            if($translation_key === '') {
                                return null;
                            }

                            $cache_key = $translation_key . '|' . $target_language_code . '|' . $fallback_language_code;
                            if(array_key_exists($cache_key, $fcc_forever_product_translation_cache)) {
                                return $fcc_forever_product_translation_cache[$cache_key];
                            }

                            $language_priority = array_values(array_filter(array_unique([
                                $target_language_code,
                                $fallback_language_code,
                            ])));

                            foreach($language_priority as $language_code) {
                                $language_name = $fcc_blog_language_names_by_code[$language_code] ?? null;

                                if(!$language_name) {
                                    continue;
                                }

                                $blog_post = db()
                                    ->where('is_published', 1)
                                    ->where('url', $translation_key)
                                    ->where('language', $language_name)
                                    ->getOne('blog_posts', ['blog_post_id', 'title', 'description', 'url', 'image', 'language']);

                                if(!$blog_post) {
                                    continue;
                                }

                                $language_prefix = !empty($blog_post->language) && isset(\Altum\Language::$active_languages[$blog_post->language])
                                    ? \Altum\Language::$active_languages[$blog_post->language] . '/'
                                    : null;

                                return $fcc_forever_product_translation_cache[$cache_key] = (object) [
                                    'blog_post_id' => (int) ($blog_post->blog_post_id ?? 0),
                                    'title' => (string) ($blog_post->title ?? ''),
                                    'description' => mb_substr(trim(strip_tags((string) ($blog_post->description ?? ''))), 0, 220),
                                    'blog_url' => SITE_URL . $language_prefix . 'blog/' . ($blog_post->url ?? ''),
                                    'image_url' => !empty($blog_post->image) ? \Altum\Uploads::get_full_url('blog') . $blog_post->image : null,
                                    'language_code' => $language_code,
                                ];
                            }

                            $fallback_post = db()
                                ->where('is_published', 1)
                                ->where('url', $translation_key)
                                ->getOne('blog_posts', ['blog_post_id', 'title', 'description', 'url', 'image', 'language']);

                            if(!$fallback_post) {
                                return $fcc_forever_product_translation_cache[$cache_key] = null;
                            }

                            $fallback_language_code = !empty($fallback_post->language) && isset(\Altum\Language::$active_languages[$fallback_post->language])
                                ? \Altum\Language::$active_languages[$fallback_post->language]
                                : \Altum\Language::$default_code;
                            $language_prefix = !empty($fallback_post->language) && isset(\Altum\Language::$active_languages[$fallback_post->language])
                                ? \Altum\Language::$active_languages[$fallback_post->language] . '/'
                                : null;

                            return $fcc_forever_product_translation_cache[$cache_key] = (object) [
                                'blog_post_id' => (int) ($fallback_post->blog_post_id ?? 0),
                                'title' => (string) ($fallback_post->title ?? ''),
                                'description' => mb_substr(trim(strip_tags((string) ($fallback_post->description ?? ''))), 0, 220),
                                'blog_url' => SITE_URL . $language_prefix . 'blog/' . ($fallback_post->url ?? ''),
                                'image_url' => !empty($fallback_post->image) ? \Altum\Uploads::get_full_url('blog') . $fallback_post->image : null,
                                'language_code' => $fallback_language_code,
                            ];
                        };
                        ?>

                        <?php foreach($data->biolink_blocks as $row): ?>

                            <?php
                            $row->settings = json_decode($row->settings ?? '');

                            /* Custom code */
                            if($row->type == 'link_forever_shop') {
                                $business_country_code = \Altum\Link::resolve_preferred_forever_market_country_code(
                                    $country_code,
                                    array_keys(array_filter((array) $forever_business_links, static function($value) {
                                        return !empty($value);
                                    })),
                                    $accept_language_header,
                                    $country_code_is_trusted
                                );
                                $business_base_url = $forever_business_links->{$business_country_code} ?? null;

                                if(!$business_base_url && !empty($forever_business_links->us)) {
                                    $business_country_code = 'us';
                                    $business_base_url = $forever_business_links->us;
                                }

                                if(!$business_base_url && !empty($forever_business_links->gb)) {
                                    $business_country_code = 'gb';
                                    $business_base_url = $forever_business_links->gb;
                                }

                                if($business_base_url) {
                                    $row->location_url = \Altum\Link::build_forever_destination_url($business_base_url, $forever_id, $business_country_code);
                                }
                            }

                            if($row->type == 'link_forever_webshop_reg') {
                                $registration_country_links = \Altum\Link::get_forever_webshop_registration_country_links();
                                $registration_country_code = \Altum\Link::resolve_preferred_forever_market_country_code(
                                    $country_code,
                                    array_keys($registration_country_links),
                                    $accept_language_header,
                                    $country_code_is_trusted
                                ) ?: 'hr';

                                $registration_url = \Altum\Link::build_forever_webshop_registration_url($forever_id, $registration_country_code);

                                if($registration_url) {
                                    $row->location_url = $registration_url;
                                }
                            }

                            if($row->type === 'link_forever_product') {
                                $product_translation_key = trim((string) ($row->settings->product_translation_key ?? ''));

                                if($product_translation_key === '' && !empty($row->settings->product_blog_post_id)) {
                                    $product_blog_post_id = (int) $row->settings->product_blog_post_id;

                                    if(!array_key_exists($product_blog_post_id, $fcc_forever_product_id_cache)) {
                                        $product_blog_post = db()
                                            ->where('blog_post_id', $product_blog_post_id)
                                            ->getOne('blog_posts', ['url']);

                                        $fcc_forever_product_id_cache[$product_blog_post_id] = $product_blog_post->url ?? '';
                                    }

                                    $product_translation_key = (string) $fcc_forever_product_id_cache[$product_blog_post_id];
                                    $row->settings->product_translation_key = $product_translation_key;
                                }

                                $app_language_code = $data->link->settings->language_code ?? \Altum\Language::$default_code;
                                $product_language_mode = in_array($row->settings->product_language_mode ?? 'app', ['app', 'manual'], true) ? $row->settings->product_language_mode : 'app';
                                $target_language_code = $product_language_mode === 'manual'
                                    ? (string) ($row->settings->product_language_code ?? $app_language_code)
                                    : (string) $app_language_code;
                                $fallback_language_code = (string) ($row->settings->product_fallback_language_code ?? 'hr');
                                $resolved_product = $fcc_resolve_forever_product($product_translation_key, $target_language_code, $fallback_language_code);

                                if($resolved_product) {
                                    $row->location_url = $resolved_product->blog_url;
                                    $row->settings->product_blog_post_id = $resolved_product->blog_post_id;
                                    $row->settings->product_image_url = $resolved_product->image_url;

                                    if(!empty($resolved_product->title) && trim((string) ($row->settings->name ?? '')) === '') {
                                        $row->settings->name = $resolved_product->title;
                                    }

                                    if(!empty($resolved_product->description) && trim((string) ($row->settings->description ?? '')) === '') {
                                        $row->settings->description = $resolved_product->description;
                                    }
                                }
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
                            if($row->type == 'link_discount' && !filter_var($row->settings->decoded_url ?? '', FILTER_VALIDATE_URL)) {
                                continue;
                            }                    
                            if($row->type == 'link_discount') {
                                if(function_exists('fc_forever_ordering_copy_payload') && isset($row->settings) && is_object($row->settings)) {
                                    $row->settings = fc_forever_ordering_copy_payload($row->settings);
                                }

                                $webshop_country_code = \Altum\Link::resolve_preferred_forever_market_country_code(
                                    $country_code,
                                    array_keys(array_filter((array) $forever_webshop_links, static function($value) {
                                        return !empty($value);
                                    })),
                                    $accept_language_header,
                                    $country_code_is_trusted
                                );
                                $webshop_base_url = $forever_webshop_links->{$webshop_country_code} ?? null;

                                if(!$webshop_base_url && !empty($forever_webshop_links->us)) {
                                    $webshop_country_code = 'us';
                                    $webshop_base_url = $forever_webshop_links->us;
                                }

                                if(!$webshop_base_url && !empty($forever_webshop_links->gb)) {
                                    $webshop_country_code = 'gb';
                                    $webshop_base_url = $forever_webshop_links->gb;
                                }

                                if($webshop_base_url) {
                                    $discount_query_params = \Altum\Link::get_forever_discount_query_params($row->settings->decoded_url ?? null);
                                    $row->location_url = \Altum\Link::build_forever_destination_url($webshop_base_url, $forever_id, $webshop_country_code, $discount_query_params);
                                }
                            }

                            if(in_array($row->type, ['custom_html_chatbot', 'custom_html_chatbot_pets'], true) && (!isset($row->settings) || !is_object($row->settings))) {
                                $row->settings = new \stdClass();
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
