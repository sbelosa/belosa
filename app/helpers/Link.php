<?php
/*
 * Copyright (c) 2025 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

namespace Altum;

defined('ALTUMCODE') || die();

class Link {

    public static function get_users_biolinks_latest_subquery(string $alias = 'users_biolinks'): string {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias) ?: 'users_biolinks';

        return "(SELECT `ub_latest`.`id`, `ub_latest`.`user_id`, `ub_latest`.`biolink_id`
            FROM `users_biolinks` AS `ub_latest`
            INNER JOIN (
                SELECT `user_id`, MIN(`id`) AS `latest_id`
                FROM `users_biolinks`
                GROUP BY `user_id`
            ) AS `ub_map` ON `ub_latest`.`id` = `ub_map`.`latest_id`
        ) AS `{$alias}`";
    }

    public static function get_user_main_biolink_id(int $user_id, bool $repair_mapping = true): int {
        if($user_id <= 0) {
            return 0;
        }

        $mapping_rows = db()
            ->where('user_id', $user_id)
            ->orderBy('id', 'ASC')
            ->get('users_biolinks', null, ['id', 'biolink_id']);

        $primary_mapping_id = (int) ($mapping_rows[0]->id ?? 0);
        $primary_mapped_biolink_id = (int) ($mapping_rows[0]->biolink_id ?? 0);
        $resolved_biolink_id = 0;

        foreach((array) $mapping_rows as $mapping_row) {
            $candidate_biolink_id = (int) ($mapping_row->biolink_id ?? 0);

            if($candidate_biolink_id <= 0) {
                continue;
            }

            $valid_biolink_id = (int) (db()
                ->where('link_id', $candidate_biolink_id)
                ->where('user_id', $user_id)
                ->where('type', 'biolink')
                ->getValue('links', 'link_id') ?? 0);

            if($valid_biolink_id > 0) {
                $resolved_biolink_id = $valid_biolink_id;
                break;
            }
        }

        if(!$resolved_biolink_id) {
            $fallback_biolink = db()
                ->where('user_id', $user_id)
                ->where('type', 'biolink')
                ->orderBy('is_enabled', 'DESC')
                ->orderBy('datetime', 'ASC')
                ->orderBy('link_id', 'ASC')
                ->getOne('links', ['link_id']);

            $resolved_biolink_id = (int) ($fallback_biolink->link_id ?? 0);
        }

        if($repair_mapping && $resolved_biolink_id > 0) {
            if($primary_mapping_id > 0) {
                if($primary_mapped_biolink_id !== $resolved_biolink_id) {
                    db()->where('id', $primary_mapping_id)->update('users_biolinks', [
                        'biolink_id' => $resolved_biolink_id,
                    ]);
                }
            } else {
                db()->insert('users_biolinks', [
                    'user_id' => $user_id,
                    'biolink_id' => $resolved_biolink_id,
                ]);
            }
        }

        return $resolved_biolink_id;
    }

    public static function get_trusted_forever_request_country_code(): ?string {
        foreach(['HTTP_CF_IPCOUNTRY', 'HTTP_CF-IPCOUNTRY', 'GEOIP_COUNTRY_CODE', 'HTTP_GEOIP_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_COUNTRY'] as $country_header_key) {
            if(!empty($_SERVER[$country_header_key])) {
                $header_country_code = mb_strtoupper(trim((string) $_SERVER[$country_header_key]));
                $header_country_code = mb_substr($header_country_code, 0, 2);

                if(mb_strlen($header_country_code) == 2 && $header_country_code !== 'XX') {
                    return self::resolve_forever_market_country_code($header_country_code);
                }
            }
        }

        return null;
    }

    public static function get_external_geo_country_code(?string $ip_address = null): ?string {
        $ip_address = trim((string) $ip_address);

        if(!$ip_address || !filter_var($ip_address, FILTER_VALIDATE_IP)) {
            return null;
        }

        $cache_key = 'fcc_external_geo_country?' . md5($ip_address);
        $cache_instance = cache()->getItem($cache_key);

        if(!is_null($cache_instance->get())) {
            return self::resolve_forever_market_country_code($cache_instance->get());
        }

        $providers = [
            'https://ipwho.is/' . rawurlencode($ip_address),
            'https://ipapi.co/' . rawurlencode($ip_address) . '/json/',
        ];

        $resolved_country_code = null;

        foreach($providers as $provider_url) {
            try {
                $curl_handle = curl_init($provider_url);

                curl_setopt_array($curl_handle, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 3,
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_USERAGENT => 'ForeverCardClub/1.0',
                    CURLOPT_HTTPHEADER => ['Accept: application/json'],
                ]);

                $response_body = curl_exec($curl_handle);
                $http_code = (int) curl_getinfo($curl_handle, CURLINFO_RESPONSE_CODE);
                curl_close($curl_handle);

                if(!$response_body || $http_code < 200 || $http_code >= 300) {
                    continue;
                }

                $payload = json_decode($response_body);

                if(!$payload) {
                    continue;
                }

                $resolved_country_code = self::resolve_forever_market_country_code(
                    $payload->country_code ?? $payload->country ?? null
                );

                if($resolved_country_code) {
                    break;
                }
            } catch(\Throwable $exception) {
                /* Ignore provider failures and continue with the next fallback. */
            }
        }

        cache()->save($cache_instance->set($resolved_country_code)->expiresAfter(86400));

        return $resolved_country_code;
    }

    public static function get_processed_background_style($settings) {
        $style = '';

        switch($settings->background_type) {
            case 'image':

                $style = 'background: url(\'' . \Altum\Uploads::get_full_url('backgrounds') . $settings->background . '\');';

                break;

            case 'gradient':

                $style = 'background-image: linear-gradient(135deg, ' . $settings->background_color_one . ' 10%, ' . $settings->background_color_two . ' 100%);';

                break;

            case 'color':

                $style = 'background: ' . $settings->background . ';';

                break;

            case 'preset':
            case 'preset_abstract':
                $biolink_backgrounds = require APP_PATH . 'includes/biolink_backgrounds.php';
                $style = $biolink_backgrounds[$settings->background_type][$settings->background];

                break;
        }

        /* Background attachment */
        $style .= 'background-attachment: ' . ($settings->background_attachment ?? 'scroll') . ';';

        return $style;
    }

    public static function get_processed_backdrop_style($settings) {
        $style = '';

        /* Background blur */
        if((isset($settings->background_blur) && $settings->background_blur != 0) || isset($settings->background_brightness) && $settings->background_brightness != 100) {
            $style .= 'backdrop-filter: blur(' . $settings->background_blur .'px) brightness(' . $settings->background_brightness . '%);-webkit-backdrop-filter: blur(' . $settings->background_blur .'px) brightness(' . $settings->background_brightness . '%);';
        }

        return $style;
    }

    public static function get_processed_box_shadow_style($settings) {
        $style = '';

        if(!isset($settings)) {
            return $style;
        }

        if(is_array($settings)) {
            $settings = (object) $settings;
        }

        if(empty($settings->border_shadow_style)) {
            $settings->border_shadow_style = 'subtle';
        }

        /* Box shadow */
        if($settings->border_shadow_style !== 'none') {
            $color = $settings->border_shadow_color ?? '#00000010';

            switch($settings->border_shadow_style) {
                case 'none':
                    $shadow = 'none';
                    break;
                case 'subtle':
                    $shadow = '1px 2px 4px rgba(0, 0, 0, 0.04), 1px 2px 5px ' . $color;
                    break;
                case 'strong':
                    $shadow = '1px 10px 15px -3px rgba(0, 0, 0, 0.1), 1px 4px 10px -2px ' . $color;
                    break;
                case 'hard':
                    $shadow = '4px 4px 0 2px ' . $color;
                    break;
            }

            $style = "box-shadow: {$shadow};";
        }

        return $style;
    }

    public static function get_processed_link_style($settings) {
        $class = '';
        $style = '';

        if(!empty($settings->background_color)) {
            $style .= 'background:' . $settings->background_color . ';';
        }

        if(!empty($settings->text_color)) {
            $style .= 'color:' . $settings->text_color . ';';
        }

        $style .= 'border-width:' . ($settings->border_width ?? 0) . 'px;';

        if(!empty($settings->border_color)) {
            $style .= 'border-color:' . $settings->border_color . ';';
        }

        if(!empty($settings->border_style)) {
            $style .= 'border-style:' . $settings->border_style . ';';
        }

        if(!empty($settings->text_alignment)) {
            $style .= 'text-align:' . $settings->text_alignment . ';';
        }

        /* Animation */
        if(isset($settings->animation)) {
            $class .= ' animate__animated animate__' . $settings->animation_runs . ' animate__' . $settings->animation . ' animate__delay-2s';
        }

        return ['class' => $class, 'style' => $style];
    }

    public static function get_scoped_biolink_theme_custom_css(?string $css, string $scope_selector = '.fcc-biolink-theme-scope'): string {
        $css = trim((string) $css);

        if($css === '') {
            return '';
        }

        return trim(self::scope_biolink_theme_css_rules($css, trim($scope_selector) ?: '.fcc-biolink-theme-scope'));
    }

    private static function scope_biolink_theme_css_rules(string $css, string $scope_selector): string {
        $result = '';
        $length = strlen($css);
        $offset = 0;

        while($offset < $length) {
            if(substr($css, $offset, 2) === '/*') {
                $comment_end = strpos($css, '*/', $offset + 2);
                if($comment_end === false) {
                    $result .= substr($css, $offset);
                    break;
                }

                $result .= substr($css, $offset, $comment_end - $offset + 2);
                $offset = $comment_end + 2;
                continue;
            }

            $current_character = $css[$offset];

            if(trim($current_character) === '') {
                $result .= $current_character;
                $offset++;
                continue;
            }

            if($current_character === '@') {
                [$rule_output, $next_offset] = self::consume_biolink_theme_at_rule($css, $offset, $scope_selector);
                $result .= $rule_output;
                $offset = $next_offset;
                continue;
            }

            $selector_start = $offset;
            while($offset < $length && $css[$offset] !== '{') {
                $offset++;
            }

            if($offset >= $length) {
                $result .= substr($css, $selector_start);
                break;
            }

            $selector_list = trim(substr($css, $selector_start, $offset - $selector_start));
            [$block_body, $next_offset] = self::consume_biolink_theme_block($css, $offset);

            if($selector_list === '') {
                $result .= '{' . $block_body . '}';
            } else {
                $result .= self::scope_biolink_theme_selector_list($selector_list, $scope_selector) . '{' . $block_body . '}';
            }

            $offset = $next_offset;
        }

        return $result;
    }

    private static function consume_biolink_theme_at_rule(string $css, int $offset, string $scope_selector): array {
        $length = strlen($css);
        $rule_start = $offset;

        while($offset < $length && $css[$offset] !== '{' && $css[$offset] !== ';') {
            $offset++;
        }

        if($offset >= $length) {
            return [substr($css, $rule_start), $length];
        }

        if($css[$offset] === ';') {
            return [substr($css, $rule_start, $offset - $rule_start + 1), $offset + 1];
        }

        $at_rule_header = trim(substr($css, $rule_start, $offset - $rule_start));
        [$block_body, $next_offset] = self::consume_biolink_theme_block($css, $offset);

        if(preg_match('/^@(media|supports|container|layer|document)\b/i', $at_rule_header)) {
            return [$at_rule_header . '{' . self::scope_biolink_theme_css_rules($block_body, $scope_selector) . '}', $next_offset];
        }

        return [$at_rule_header . '{' . $block_body . '}', $next_offset];
    }

    private static function consume_biolink_theme_block(string $css, int $opening_brace_offset): array {
        $length = strlen($css);
        $depth = 0;
        $offset = $opening_brace_offset;
        $block_start = $opening_brace_offset + 1;
        while($offset < $length) {
            $character = $css[$offset];

            if(substr($css, $offset, 2) === '/*') {
                $comment_end = strpos($css, '*/', $offset + 2);
                if($comment_end === false) {
                    return [substr($css, $block_start), $length];
                }

                $offset = $comment_end + 2;
                continue;
            }

            if($character === '{') {
                $depth++;
            } elseif($character === '}') {
                $depth--;

                if($depth === 0) {
                    return [substr($css, $block_start, $offset - $block_start), $offset + 1];
                }
            } elseif($character === '"' || $character === '\'') {
                $quote = $character;
                $offset++;

                while($offset < $length) {
                    if($css[$offset] === '\\') {
                        $offset += 2;
                        continue;
                    }

                    if($css[$offset] === $quote) {
                        break;
                    }

                    $offset++;
                }
            }

            $offset++;
        }

        return [substr($css, $block_start), $length];
    }

    private static function scope_biolink_theme_selector_list(string $selector_list, string $scope_selector): string {
        $selectors = [];
        $buffer = '';
        $depth_parentheses = 0;
        $depth_brackets = 0;
        $length = strlen($selector_list);

        for($index = 0; $index < $length; $index++) {
            $character = $selector_list[$index];

            if($character === '(') {
                $depth_parentheses++;
            } elseif($character === ')' && $depth_parentheses > 0) {
                $depth_parentheses--;
            } elseif($character === '[') {
                $depth_brackets++;
            } elseif($character === ']' && $depth_brackets > 0) {
                $depth_brackets--;
            }

            if($character === ',' && $depth_parentheses === 0 && $depth_brackets === 0) {
                $selectors[] = self::scope_biolink_theme_single_selector($buffer, $scope_selector);
                $buffer = '';
                continue;
            }

            $buffer .= $character;
        }

        if(trim($buffer) !== '') {
            $selectors[] = self::scope_biolink_theme_single_selector($buffer, $scope_selector);
        }

        $selectors = array_values(array_unique(array_filter($selectors, static fn($selector) => trim((string) $selector) !== '')));

        return implode(', ', $selectors);
    }

    private static function scope_biolink_theme_single_selector(string $selector, string $scope_selector): string {
        $selector = trim($selector);

        if($selector === '') {
            return '';
        }

        $selector = preg_replace('/:root\b/i', $scope_selector, $selector) ?? $selector;
        $selector = preg_replace('/(^|[\s>+~,(])html(?=$|[\s>+~#.:\[])/i', '$1' . $scope_selector, $selector) ?? $selector;
        $selector = preg_replace('/(^|[\s>+~,(])body(?=$|[\s>+~#.:\[])/i', '$1' . $scope_selector, $selector) ?? $selector;
        $selector = trim(preg_replace('/' . preg_quote($scope_selector, '/') . '\s+' . preg_quote($scope_selector, '/') . '/', $scope_selector, $selector) ?? $selector);

        if(str_contains($selector, $scope_selector)) {
            return $selector;
        }

        return $scope_selector . ' ' . $selector;
    }

    public static function get_biolink($tthis, $link, $user = null, $biolink_blocks = null) {

        /* Determine the background of the biolink */
        $link->design = new \StdClass();
        $link->design->background_class = '';
        $link->design->background_style = '';

        if(isset($tthis->biolink_theme) && $tthis->biolink_theme) {
            $link->settings = (object) array_merge((array) $link->settings, (array) $tthis->biolink_theme->settings->biolink);
        }

        $link->design->background_style = self::get_processed_background_style($link->settings);
        $link->design->backdrop_style = self::get_processed_backdrop_style($link->settings);

        /* Determine the color of the header text */
        $link->design->text_style = 'color: ' . $link->settings->text_color;

        /* Determine the notification branding settings */
        if($user && !$user->plan_settings->removable_branding && !$link->settings->display_branding) {
            $link->settings->display_branding = true;
        }

        if($user && $user->plan_settings->removable_branding && !$link->settings->display_branding) {
            $link->settings->display_branding = false;
        }

        /* Check if we can show the custom branding if available */
        if(isset($link->settings->branding, $link->settings->branding->name, $link->settings->branding->url) && !$user->plan_settings->custom_branding) {
            $link->settings->branding = false;
        }

        /* Prepare the view */
        $data = [
            'link'  => $link,
            'user'  => $user,
            'biolink_blocks' => $biolink_blocks
        ];

        $view = new \Altum\View('l/partials/biolink', (array) $tthis);

        return $view->run($data);

    }

    public static function get_biolink_link($link, $user = null, $biolink_theme = null, $biolink = null) {

        $data = [];

        $biolink_blocks = require APP_PATH . 'includes/enabled_biolink_blocks.php';

        if(!array_key_exists($link->type, $biolink_blocks)) {
            return null;
        }

        /* Apply theme if needed */
        if($biolink_theme && $biolink_blocks[$link->type]['themable']) {
            $theme_block_settings = isset($biolink_theme->settings->biolink_block) ? (array) $biolink_theme->settings->biolink_block : [];
            $theme_social_settings = isset($biolink_theme->settings->biolink_block_socials) ? (array) $biolink_theme->settings->biolink_block_socials : [];
            $theme_heading_settings = isset($biolink_theme->settings->biolink_block_heading) ? (array) $biolink_theme->settings->biolink_block_heading : [];
            $theme_paragraph_settings = isset($biolink_theme->settings->biolink_block_paragraph) ? (array) $biolink_theme->settings->biolink_block_paragraph : [];

            switch($link->type) {
                case 'socials':
                    $link->settings = (object) array_merge((array) $link->settings, $theme_social_settings);
                    break;

                case 'heading':
                    $link->settings = (object) array_merge((array) $link->settings, $theme_heading_settings);
                    break;

                case 'paragraph':
                    $link->settings = (object) array_merge((array) $link->settings, $theme_block_settings, $theme_paragraph_settings);
                    break;

                case 'counter':
                case 'loading':
                    $biolink_theme->settings->biolink_block->number_color = $biolink_theme->settings->biolink_block->text_color;

                    $link->settings = (object) array_merge((array) $link->settings, $theme_block_settings);
                    break;

                case 'external_item':
                    $biolink_theme->settings->biolink_block->price_color = $biolink_theme->settings->biolink_block->text_color;
                    $biolink_theme->settings->biolink_block->name_color = $biolink_theme->settings->biolink_block->text_color;

                    $link->settings = (object) array_merge((array) $link->settings, $theme_block_settings);
                    break;

                case 'business_hours':
                    $biolink_theme->settings->biolink_block->icon_color = $biolink_theme->settings->biolink_block->text_color;

                    $link->settings = (object) array_merge((array) $link->settings, $theme_block_settings);
                    break;

                default:
                    $link->settings = (object) array_merge((array) $link->settings, $theme_block_settings);
                    break;
            }
        }

        /* Determine the css and styling of the button */
        $link_style = self::get_processed_link_style($link->settings);

        /* Paragraph do not add subtle shadow on older versions */
        if(empty($link->settings->border_shadow_style)) {
            $link_style['style'] .= '';
        } else {
            $link_style['style'] .= self::get_processed_box_shadow_style($link->settings);
        }

        $link->design = new \StdClass();
        $link->design->link_class = $link_style['class'];
        $link->design->link_style = $link_style['style'];

        /* Require different files for different types of links available */
        switch($link->type) {
            case 'link':
            /* Custom code */
            case 'link_app_switcher':
            case 'link_back':
            case 'link_forever_shop':
            case 'link_forever_product':
            /* Custom code: FC-2026-03-06: keep only canonical Albania/Kosovo block type */
            case 'link_forever_living_bih':
            case 'link_forever_living_alb_kosovo':
            /* /Custom code: FC-2026-03-06 */
            case 'link_discount':
            case 'link_homescreen_ios':
            case 'link_homescreen_android':
            case 'link_save_contact':         
            /* /Custom code */
            case 'big_link':
            case 'appointment_calendar':
            case 'email_collector':
            case 'contact_collector':
            /* Custom code: FC-2026-03-23: lead funnel block phase 1 */
            case 'lead_funnel':
            /* /Custom code: FC-2026-03-23 */
            case 'rss_feed':
            case 'vcard':
            case 'file':
            case 'pdf_document':
            case 'powerpoint_presentation':
            case 'excel_spreadsheet':
            case 'cta':
            case 'share':
            case 'coupon':
            case 'modal_text':
            case 'youtube_feed':
            case 'paypal':
            case 'phone_collector':
            case 'donation':
            case 'product':
            case 'service':
            case 'faq':
            case 'list':
            case 'alert':

                /* UTM Parameters */
                $link->utm_query = null;
                if($user->plan_settings->utm) {
                    $utm_parameters = [];
                    if($link->utm->source ?? null) $utm_parameters['source'] = $link->utm->source;
                    if($link->utm->medium ?? null) $utm_parameters['medium'] = $link->utm->medium;
                    if($link->settings->name ?? null) $utm_parameters['campaign'] = $link->settings->name;

                    if(count($utm_parameters) > 1) {
                        $append_query = http_build_query($utm_parameters);

                        $separator = str_contains((string) ($link->location_url ?? ''), '?') ? '&' : '?';
                        $link->utm_query = $separator . $append_query;
                    }
                }

                /* Custom code: FC-2026-03-23: lead funnel page url */
                if($link->type == 'lead_funnel') {
                    $link->full_url = url('l/link?biolink_block_id=' . $link->biolink_block_id);
                }
                /* /Custom code: FC-2026-03-23 */

                /* Call to action custom link */
                if($link->type == 'cta') {
                    switch($link->settings->type) {
                        case 'email':
                            $link->location_url = 'mailto:' . $link->settings->value;
                            break;
                        case 'call':
                            $link->location_url = 'tel:' . $link->settings->value;
                            break;
                        case 'sms':
                            $link->location_url = 'sms:' . $link->settings->value;
                            break;
                        case 'facetime':
                            $link->location_url = 'facetime:' . $link->settings->value;
                            break;
                    }
                }

                /* Generate paypal payment link */
                if($link->type == 'paypal') {
                    $paypal_type = [
                        'buy_now' => '_xclick',
                        'add_to_cart' => '_cart',
                        'donation' => '_donations'
                    ];

                    if($link->settings->type == 'add_to_cart') {
                        $link->location_url = sprintf('https://www.paypal.com/cgi-bin/webscr?business=%s&cmd=%s&currency_code=%s&amount=%s&item_name=%s&button_subtype=products&add=1&return=%s&cancel_return=%s', $link->settings->email, $paypal_type[$link->settings->type], $link->settings->currency, $link->settings->price, $link->settings->title, $link->settings->thank_you_url, $link->settings->cancel_url);
                    } else {
                        $link->location_url = sprintf('https://www.paypal.com/cgi-bin/webscr?business=%s&cmd=%s&currency_code=%s&amount=%s&item_name=%s&return=%s&cancel_return=%s', $link->settings->email, $paypal_type[$link->settings->type], $link->settings->currency, $link->settings->price, $link->settings->title, $link->settings->thank_you_url, $link->settings->cancel_url);
                    }
                }

                /* Get payment processors */
                if(in_array($link->type, ['donation', 'product', 'service'])) {
                    $data['payment_processors'] = (new \Altum\Models\PaymentProcessor())->get_payment_processors_by_user_id($user->user_id);
                }

                /* Get data about the appointments */
                if($link->type == 'appointment_calendar') {
                    $available_slots = [];

                    $durations = !empty($link->settings->durations) ? $link->settings->durations : [(object) ['value' => 30, 'type' => 'minutes']];
                    $minimum_notice_period = $link->settings->minimum_notice_period_value ?? 0;
                    $minimum_notice_unit = $link->settings->minimum_notice_period_type ?? 'minutes';
                    $allowed_days = $link->settings->allowed_scheduling_days_ahead ?? 7;
                    $timezone_string = $link->settings->timezone ?? 'UTC';
                    $timezone = new \DateTimeZone($timezone_string);

                    $now = new \DateTime('now', $timezone);
                    $min_notice_timestamp = (clone $now)->modify("+{$minimum_notice_period} {$minimum_notice_unit}")->getTimestamp();

                    $raw_available_times = $link->settings->available_times ?? [];

                    /* Decode booked appointments (stored in UTC) */
                    $scheduled_appointments = db()->where('biolink_block_id', $link->biolink_block_id)->get('data');
                    $booked_slots_by_date = [];

                    foreach ($scheduled_appointments as $appointment) {
                        $decoded = json_decode($appointment->data ?? '{}');

                        if(!empty($decoded->date) && !empty($decoded->start_time)) {
                            $utc_datetime = \DateTime::createFromFormat('Y-m-d H:i', "{$decoded->date} {$decoded->start_time}", new \DateTimeZone('UTC'));
                            if(!$utc_datetime) continue;

                            $local_datetime = clone $utc_datetime;
                            $local_datetime->setTimezone($timezone);

                            $local_date = $local_datetime->format('Y-m-d');
                            $local_time = $local_datetime->format('H:i');

                            $booked_slots_by_date[$local_date][] = $local_time;
                        }
                    }

                    /* Generate future slots */
                    for ($i = 0; $i < $allowed_days; $i++) {
                        $current_date = (clone $now)->modify("+$i days");
                        $date = $current_date->format('Y-m-d');
                        $weekday = strtolower($current_date->format('l'));

                        $available_for_day = $raw_available_times->{$weekday} ?? [];
                        if(empty($available_for_day)) continue;

                        /* Determine the latest allowable end time */
                        $day_end_limit = end($available_for_day);
                        $day_end_datetime = \DateTime::createFromFormat('Y-m-d H:i', "{$date} {$day_end_limit}", $timezone);
                        reset($available_for_day);

                        foreach ($available_for_day as $base_start_time) {
                            foreach ($durations as $duration_config) {
                                $duration_value = (int) $duration_config->value;
                                $duration_unit = $duration_config->type;

                                $slot_start = \DateTime::createFromFormat('Y-m-d H:i', "{$date} {$base_start_time}", $timezone);
                                if(!$slot_start) continue;

                                $slot_end = (clone $slot_start)->modify("+{$duration_value} {$duration_unit}");

                                /* Skip if slot end exceeds the last available time */
                                if($slot_end > $day_end_datetime) {
                                    continue;
                                }

                                /* Skip if slot is too soon (based on minimum notice) */
                                if($slot_start->getTimestamp() < $min_notice_timestamp) {
                                    continue;
                                }

                                $formatted_start = $slot_start->format('H:i');
                                $formatted_end = $slot_end->format('H:i');

                                $is_booked = in_array($formatted_start, $booked_slots_by_date[$date] ?? []);

                                $available_slots[] = [
                                    'date' => $date,
                                    'start_time' => $formatted_start,
                                    'end_time' => $formatted_end,
                                    'is_booked' => $is_booked,
                                ];
                            }
                        }
                    }

                    $data['available_slots'] = $available_slots;
                    $data['timezone'] = $timezone_string;
                }

                if($biolink_blocks[$link->type]['type'] == 'default') {
                    $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                } else {
                    $view_path = \Altum\Plugin::get($biolink_blocks[$link->type]['type'] . '-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'heading':
            case 'paragraph':
            case 'business_hours':

                $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';

                break;

            case 'socials':
                $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                break;

            case 'avatar':
            case 'image':
            case 'featured_link':
            case 'image_grid':
            case 'image_comparison':
            case 'map':
            case 'image_slider':

                /* UTM Parameters */
                $link->utm_query = null;
                if($user->plan_settings->utm && $link->utm->medium && $link->utm->source) {
                    $link->utm_query = '?utm_medium=' . $link->utm->medium . '&utm_source=' . $link->utm->source . '&utm_campaign=' . $link->settings->name;
                }

                if($biolink_blocks[$link->type]['type'] == 'default') {
                    $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                } else {
                    $view_path = \Altum\Plugin::get($biolink_blocks[$link->type]['type'] . '-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'weather':

                /* UTM Parameters */
                $link->utm_query = null;
                if($user->plan_settings->utm && $link->utm->medium && $link->utm->source) {
                    $link->utm_query = '?utm_medium=' . $link->utm->medium . '&utm_source=' . $link->utm->source . '&utm_campaign=' . $link->settings->name;
                }

                if($biolink_blocks[$link->type]['type'] == 'default') {
                    $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                } else {
                    $view_path = \Altum\Plugin::get($biolink_blocks[$link->type]['type'] . '-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'header':

                /* UTM Parameters */
                $link->utm_query = null;
                if($user->plan_settings->utm && $link->utm->medium && $link->utm->source) {
                    $link->utm_query = '?utm_medium=' . $link->utm->medium . '&utm_source=' . $link->utm->source . '&utm_campaign=' . $link->settings->name;
                }

                preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|shorts\/|v\/|watch\?v=|watch\?.+&v=))((?:\w|-){11})(?:&list=(\S+))?/', $link->settings->video_url ?? '', $match);

                $data['embed'] = $match[1] ?? null;

                $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';

                break;

            case 'youtube':
                $youtube_candidates = array_values(array_filter([
                    trim((string) ($link->location_url ?? '')),
                    trim((string) ($link->settings->video_url ?? '')),
                ]));

                $youtube_embed = null;

                foreach($youtube_candidates as $youtube_candidate) {
                    preg_match('/(?:https?:\/\/)?(?:www\.|m\.)?(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:embed\/|shorts\/|live\/|v\/|watch\?v=|watch\?.+&v=))((?:\w|-){11})(?:[?&].*)?$/', $youtube_candidate, $match);

                    if(!empty($match[1])) {
                        $youtube_embed = $match[1];
                        break;
                    }

                    $parsed_host = mb_strtolower((string) parse_url($youtube_candidate, PHP_URL_HOST));
                    $parsed_path = trim((string) parse_url($youtube_candidate, PHP_URL_PATH), '/');
                    $parsed_query = [];
                    parse_str((string) parse_url($youtube_candidate, PHP_URL_QUERY), $parsed_query);

                    if(in_array($parsed_host, ['youtu.be'], true) && preg_match('/^[\w-]{11}$/', $parsed_path)) {
                        $youtube_embed = $parsed_path;
                        break;
                    }

                    if(
                        in_array($parsed_host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)
                        && !empty($parsed_query['v'])
                        && preg_match('/^[\w-]{11}$/', (string) $parsed_query['v'])
                    ) {
                        $youtube_embed = (string) $parsed_query['v'];
                        break;
                    }
                }

                $data['embed'] = $youtube_embed;

                if($data['embed']) {
                    $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                } else {
                    return null;
                }

                break;

            case 'google_form':
                if (
                    preg_match('~/forms/d/e/([a-zA-Z0-9_-]+)~', $link->location_url, $match)
                    || preg_match('~/forms/d/([a-zA-Z0-9_-]+)~', $link->location_url, $match)
                ) {
                    $data['form_id'] = $match[1];
                    $data['embed'] = 'https://docs.google.com/forms/d/e/' . $data['form_id'] . '/viewform?embedded=true';

                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'threads':

                if(preg_match('/(threads\.net)/', $link->location_url)) {
                    $data['embed'] = $link->location_url;

                    $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'snapchat':

                if(preg_match('/(snapchat\.com)/', $link->location_url)) {
                    $data['embed'] = $link->location_url;

                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'soundcloud':

                if(preg_match('/(soundcloud\.com)/', $link->location_url)) {
                    $data['embed'] = $link->location_url;

                    $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'vimeo':

                if(preg_match('/https:\/\/(player\.)?vimeo\.com(\/video)?\/(\d+)/', $link->location_url, $match)) {
                    $data['embed'] = $match[3];

                    $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'twitch':

                if(preg_match('/^(?:https?:\/\/)?(?:www\.)?(?:twitch\.tv\/(?:(?P<channel>[a-zA-Z0-9_]+)\/clip\/(?P<clip_slug>[a-zA-Z0-9_-]+)|videos\/(?P<video_id>\d+)|(?P<channel_only>[a-zA-Z0-9_]+))|clips\.twitch\.tv\/(?P<clip_direct>[a-zA-Z0-9_-]+))$/', $link->location_url, $match)) {

                    if(!empty($match['video_id'])) {
                        $data['embed'] = $match['video_id'];
                        $data['embed_type'] = 'video';
                    } elseif(!empty($match['clip_slug'])) {
                        $data['embed'] = $match['clip_slug'];
                        $data['embed_type'] = 'clip';
                    } elseif(!empty($match['clip_direct'])) {
                        $data['embed'] = $match['clip_direct'];
                        $data['embed_type'] = 'clip';
                    } else {
                        $data['embed'] = $match['channel_only'];
                        $data['embed_type'] = 'channel';
                    }

                    $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'telegram':

                if(preg_match('/^(?:https?:\/\/)?(?:www\.)?(?:t\.me\/)(.+)$/', $link->location_url, $match)) {
                    $data['embed'] = $match[1];

                    $view_path = \Altum\Plugin::get('ultimate-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'spotify':

                if(preg_match('/^(?:https?:\/\/)?(?:www\.)?(?:open\.)?(?:spotify\.com\/)(?:intl-.+\/)*(album|track|show|episode|playlist)+\/(.+)$/', $link->location_url, $match)) {
                    $data['embed_type'] = $match[1];
                    $data['embed_value'] = $match[2];

                    $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'tiktok_video':

                if(preg_match('/^(?:https?:\/\/)?(?:www\.)?(?:tiktok\.com\/.+\/)(.+)$/', $link->location_url, $match)) {
                    $data['embed'] = $match[1];

                    $view_path = THEME_PATH . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'tiktok_profile':

                if(preg_match('/^(?:https?:\/\/)?(?:www\.)?(?:tiktok\.com\/@)([^\/\?]+)/', $link->location_url, $match)) {
                    $data['embed'] = $match[1];

                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'vk_video':

                if(preg_match('/^https:\/\/vk\.com\/(?:.*)video-(\d+)_(\d+)/', $link->location_url, $match)) {
                    $data['embed_oid'] = $match[1];
                    $data['embed_id'] = $match[2];

                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'applemusic':

                if(preg_match('/(https:\/\/music\.apple\.com)/', $link->location_url)) {

                    $position = mb_strpos($link->location_url, 'music.apple.com');

                    if($position !== false) {
                        $link->location_url = str_replace('music.apple.com', 'embed.music.apple.com', $link->location_url);

                        $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                    }

                }

                break;

            case 'tidal':

                if(preg_match('/(https:\/\/tidal\.com)/', $link->location_url)) {

                    $position = mb_strpos($link->location_url, 'tidal.com');

                    if($position !== false) {
                        $link->location_url = str_replace('tidal.com', 'embed.tidal.com', $link->location_url) . '?disableAnalytics=true';
                        $link->location_url = str_replace('browse/', '', $link->location_url);
                        $link->location_url = str_replace('track/', 'tracks/', $link->location_url);
                        $link->location_url = str_replace('album/', 'albums/', $link->location_url);

                        $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                    }

                }

                break;

            case 'mixcloud':

                if(preg_match('/(https:\/\/www.mixcloud\.com)/', $link->location_url)) {

                    $data['embed'] = str_replace('https://www.mixcloud.com', '', $link->location_url);

                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';

                }

                break;

            case 'kick':

                if(preg_match('/^(?:https?:\/\/)?(?:www\.)?(?:kick\.com\/)(.+)$/', $link->location_url, $match)) {
                    $data['embed'] = $match[1];

                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'anchor':

                if(preg_match('/(https:\/\/anchor\.fm)/', $link->location_url)) {

                    $position = mb_strpos($link->location_url, '/', 18);

                    if($position !== false) {

                        $link->location_url = substr_replace($link->location_url, '/embed', $position, 0);

                        $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                    }

                }

                break;

            case 'twitter_profile':

                $link->location_url = str_replace('https://x.com/', 'https://twitter.com/', $link->location_url);

                if(preg_match('/(https:\/\/twitter\.com)/', $link->location_url) || preg_match('/(https:\/\/x\.com)/', $link->location_url)) {
                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'twitter_tweet':

                $link->location_url = str_replace('https://x.com/', 'https://twitter.com/', $link->location_url);

                if(preg_match('/(https:\/\/twitter\.com)/', $link->location_url)) {
                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'twitter_video':

                $link->location_url = str_replace('https://x.com/', 'https://twitter.com/', $link->location_url);

                if(preg_match('/(https:\/\/twitter\.com)/', $link->location_url)) {
                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'pinterest_profile':

                if(preg_match('/(pinterest\.com)/', $link->location_url)) {
                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'instagram_media':

                if(preg_match('/(https:\/\/www.instagram\.com)/', $link->location_url)) {
                    $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'typeform':

                if(preg_match('/https:\/\/.+.typeform\.com\/to\/([a-zA-Z0-9]+)/', $link->location_url, $match)) {
                    $data['embed'] = $match[1];

                    $view_path = \Altum\Plugin::get('ultimate-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'calendly':

                if(preg_match('/(https:\/\/calendly\.com)/', $link->location_url)) {
                    $view_path = \Altum\Plugin::get('ultimate-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';
                }

                break;

            case 'custom_html':
            case 'divider':
            case 'tumblr_post':
            case 'bluesky_post':
            case 'canva':
            case 'code':

                $view_path = \Altum\Plugin::get('pro-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';

                break;

            /* Custom code: FC-2026-02-27: stable chatbot block renderer */
            case 'custom_html_chatbot':
            case 'custom_html_chatbot_pets':

                $view_path = THEME_PATH . 'views/l/biolink_blocks/custom_html_chatbot.php';

                break;
            /* /Custom code: FC-2026-02-27 */

            /* Custom code: FC-2026-03-30: restore WhatsApp block renderer */
            case 'custom_html_whatsapp':

                $view_path = THEME_PATH . 'views/l/biolink_blocks/custom_html_whatsapp.php';

                break;
            /* /Custom code: FC-2026-03-30 */

            case 'discord':
            case 'facebook':
            case 'reddit':
            case 'audio':
            case 'video':
            case 'countdown':
            case 'timeline':
            case 'review':
            case 'markdown':
            case 'rumble':
            case 'iframe':

                $view_path = \Altum\Plugin::get('ultimate-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';

                break;

            case 'external_item':
            case 'counter':
            case 'loading':

                /* Determine the css and styling of the button */
                $link->design = new \StdClass();
                $link->design->card_class = '';
                $link->design->card_style = 'background: ' . $link->settings->background_color . ';border-width: ' . $link->settings->border_width . 'px; border-color: ' . $link->settings->border_color . ';border-style: ' . $link->settings->border_style . ';';

                /* Animation */
                if($link->settings->animation) {
                    $link->design->card_class .= ' animate__animated animate__' . $link->settings->animation_runs . ' animate__' . $link->settings->animation . ' animate__delay-2s';
                }

                /* UTM Parameters */
                $link->utm_query = null;
                if($user->plan_settings->utm && $link->utm->medium && $link->utm->source) {
                    $link->utm_query = '?utm_medium=' . $link->utm->medium . '&utm_source=' . $link->utm->source . '&utm_campaign=' . $link->settings->name;
                }

                $view_path = \Altum\Plugin::get('ultimate-blocks')->path . 'views/l/biolink_blocks/' . $link->type . '.php';

                break;

        }

        /* Custom code: FC-2026-03-06: fail-safe for missing block view files to avoid black screen */
        if(!isset($view_path) || !is_file($view_path)) {
            dil('[BiolinkRender] Missing block view for type: ' . ($link->type ?? 'unknown') . ' path: ' . ($view_path ?? 'undefined'));
            return null;
        }
        /* /Custom code: FC-2026-03-06 */

        /* Prepare the view */
        $data = array_merge($data, [
            'link'      => $link,
            'user'      => $user,
            'biolink'   => $biolink,
        ]);

        return include_view($view_path, $data);
    }

    /* Custom code */
    public static function get_forever_referral_parameter($country_code = null) {
        $country_code = mb_strtolower((string) $country_code);

        return in_array($country_code, ['ba', 'al', 'me', 'xk']) ? 'id' : 'fboId';
    }

    public static function get_forever_discount_query_params($destination_url): array {
        if(!$destination_url || !filter_var($destination_url, FILTER_VALIDATE_URL)) {
            return [];
        }

        $parsed_url = parse_url($destination_url);

        if(empty($parsed_url['query'])) {
            return [];
        }

        parse_str($parsed_url['query'], $params);

        $discount_params = [];

        foreach(['discountConfigType', 'uniqueExtRefID', 'referralUuid'] as $key) {
            if(!empty($params[$key])) {
                $discount_params[$key] = $params[$key];
            }
        }

        return $discount_params;
    }

    public static function resolve_forever_market_country_code($country_code = null): ?string {
        $country_code = mb_strtolower(trim((string) $country_code));

        if($country_code === '' || mb_strlen($country_code) !== 2) {
            return null;
        }

        if($country_code === 'xx') {
            return null;
        }

        return in_array($country_code, ['xk'], true) ? 'al' : $country_code;
    }

    /* Custom code: FC-2026-03-31: blog CTA tracking helpers */
    public static function get_blog_cta_business_post_ids(): array {
        return [406, 407];
    }

    public static function get_blog_cta_click_type_by_post_id(int $blog_post_id): string {
        return in_array($blog_post_id, self::get_blog_cta_business_post_ids(), true) ? 'business' : 'product';
    }

    public static function get_blog_cta_tracking_medium(string $click_type = 'product'): string {
        return $click_type === 'business' ? 'blog_cta_business' : 'blog_cta_product';
    }

    public static function get_fcc_results_qualified_block_types(): array {
        return array_values(array_unique(array_merge(
            self::get_monitored_forever_outbound_types(),
            ['link_forever_living_albania_kosovo']
        )));
    }

    public static function get_fcc_results_qualified_blog_mediums(): array {
        return [
            self::get_blog_cta_tracking_medium('product'),
            self::get_blog_cta_tracking_medium('business'),
        ];
    }

    public static function get_fcc_results_qualified_click_condition_sql(string $track_links_alias, string $biolinks_blocks_alias): string {
        $qualified_block_types_sql = "'" . implode("','", self::get_fcc_results_qualified_block_types()) . "'";
        $qualified_blog_mediums_sql = "'" . implode("','", self::get_fcc_results_qualified_blog_mediums()) . "'";

        return "((COALESCE({$biolinks_blocks_alias}.`type`, '') IN ({$qualified_block_types_sql})) OR (COALESCE({$track_links_alias}.`utm_medium`, '') IN ({$qualified_blog_mediums_sql})))";
    }

    public static function get_monitored_forever_outbound_types(): array {
        return [
            'link_forever_shop',
            'link_forever_product',
            'link_forever_living_bih',
            'link_forever_living_alb_kosovo',
            'link_discount',
        ];
    }

    public static function is_monitored_forever_outbound_type(?string $type): bool {
        return in_array((string) $type, self::get_monitored_forever_outbound_types(), true);
    }

    public static function is_monitored_forever_destination_url($url): bool {
        $url = trim((string) $url);

        if($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $host = is_string($host) ? mb_strtolower(preg_replace('/^www\./', '', $host) ?? $host) : '';

        if($host === '') {
            return false;
        }

        if($host === 'thealoeveraco.shop') {
            return true;
        }

        if(strpos($host, 'foreverliving') !== false || strpos($host, 'foreverlivingproducts') !== false) {
            return true;
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if(is_string($query) && $query !== '') {
            parse_str($query, $query_parameters);

            if(isset($query_parameters['fboId']) || isset($query_parameters['id'])) {
                return true;
            }
        }

        return false;
    }

    public static function get_monitored_forever_destination_signature($url): ?string {
        $url = trim((string) $url);

        if(!self::is_monitored_forever_destination_url($url)) {
            return null;
        }

        $parsed_url = parse_url($url);
        $host = mb_strtolower(trim((string) ($parsed_url['host'] ?? '')));
        $path = '/' . ltrim(trim((string) ($parsed_url['path'] ?? '/')), '/');
        $query = [];

        if(!empty($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query);
        }

        $stable_query = [];
        foreach(['fboId', 'id', 'product', 'sku'] as $query_key) {
            if(isset($query[$query_key]) && $query[$query_key] !== '') {
                $stable_query[$query_key] = (string) $query[$query_key];
            }
        }

        ksort($stable_query);

        return hash('sha256', $host . '|' . $path . '|' . http_build_query($stable_query, '', '&', PHP_QUERY_RFC3986));
    }

    public static function get_monitored_forever_destination_label($url): string {
        $url = trim((string) $url);

        if(!filter_var($url, FILTER_VALIDATE_URL)) {
            return '-';
        }

        $parsed_url = parse_url($url);
        $host = mb_strtolower(trim((string) ($parsed_url['host'] ?? '')));
        $path = trim((string) ($parsed_url['path'] ?? '/'));

        return rtrim($host . ($path !== '' ? $path : '/'), '/');
    }

    public static function get_forever_shop_click_condition_sql(string $track_links_alias, string $biolinks_blocks_alias, string $block_types_sql): string {
        $blog_tracking_medium = self::get_blog_cta_tracking_medium('product');

        return "(({$biolinks_blocks_alias}.`type` IN ({$block_types_sql})) OR ({$track_links_alias}.`utm_medium` = '{$blog_tracking_medium}'))";
    }

    public static function get_forever_registration_click_condition_sql(string $track_links_alias, string $biolinks_blocks_alias, string $block_types_sql): string {
        $blog_tracking_medium = self::get_blog_cta_tracking_medium('business');

        return "(({$biolinks_blocks_alias}.`type` IN ({$block_types_sql})) OR ({$track_links_alias}.`utm_medium` = '{$blog_tracking_medium}'))";
    }

    public static function get_forever_outbound_click_condition_sql(string $track_links_alias, string $biolinks_blocks_alias, string $shop_block_types_sql, string $registration_block_types_sql): string {
        $shop_condition = self::get_forever_shop_click_condition_sql($track_links_alias, $biolinks_blocks_alias, $shop_block_types_sql);
        $registration_condition = self::get_forever_registration_click_condition_sql($track_links_alias, $biolinks_blocks_alias, $registration_block_types_sql);

        return "({$shop_condition} OR {$registration_condition})";
    }
    /* /Custom code: FC-2026-03-31 */

    public static function resolve_preferred_forever_market_country_code($country_code = null, array $available_country_codes = [], ?string $accept_language_header = null, bool $country_code_is_trusted = false): ?string {
        $normalized_country_code = self::resolve_forever_market_country_code($country_code);
        $available_country_codes = array_values(array_unique(array_filter(array_map([self::class, 'resolve_forever_market_country_code'], $available_country_codes))));

        if($country_code_is_trusted && $normalized_country_code) {
            if(empty($available_country_codes) || in_array($normalized_country_code, $available_country_codes, true)) {
                return $normalized_country_code;
            }
        }

        if($normalized_country_code) {
            if(empty($available_country_codes) || in_array($normalized_country_code, $available_country_codes, true)) {
                return $normalized_country_code;
            }
        }

        foreach(self::get_accept_language_forever_market_candidates($accept_language_header) as $candidate_country_code) {
            if(empty($available_country_codes) || in_array($candidate_country_code, $available_country_codes, true)) {
                return $candidate_country_code;
            }
        }

        return $normalized_country_code;
    }

    private static function get_accept_language_forever_market_candidates(?string $accept_language_header): array {
        if(!$accept_language_header) {
            return [];
        }

        $language_market_map = [
            'hr' => 'hr',
            'sr' => 'rs',
            'bs' => 'ba',
            'sl' => 'si',
            'sq' => 'al',
            'de' => 'de',
        ];

        $candidates = [];

        if(preg_match_all('/([a-z]{2,3})(?:-([a-z]{2}))?/i', $accept_language_header, $matches, PREG_SET_ORDER)) {
            foreach($matches as $match) {
                $language_code = mb_strtolower($match[1] ?? '');
                $region_code = self::resolve_forever_market_country_code($match[2] ?? null);

                if($region_code) {
                    $candidates[] = $region_code;
                }

                if(isset($language_market_map[$language_code])) {
                    $candidates[] = $language_market_map[$language_code];
                }
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    public static function build_forever_destination_url($base_url, $forever_id = null, $country_code = null, array $extra_query_params = []) {
        if(!$base_url || !filter_var($base_url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed_url = parse_url($base_url);
        $query_params = [];

        if(!empty($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
        }

        if($forever_id !== null && $forever_id !== '') {
            $query_params[self::get_forever_referral_parameter($country_code)] = $forever_id;
        }

        foreach($extra_query_params as $key => $value) {
            if($value !== null && $value !== '') {
                $query_params[$key] = $value;
            }
        }

        $final_url = ($parsed_url['scheme'] ?? 'https') . '://' . ($parsed_url['host'] ?? '');

        if(isset($parsed_url['port'])) {
            $final_url .= ':' . $parsed_url['port'];
        }

        $final_url .= $parsed_url['path'] ?? '';

        if(!empty($query_params)) {
            $final_url .= '?' . http_build_query($query_params, '', '&', PHP_QUERY_RFC3986);
        }

        if(isset($parsed_url['fragment'])) {
            $final_url .= '#' . $parsed_url['fragment'];
        }

        return $final_url;
    }

    public static function append_query_parameters_to_url($url, array $params = [], bool $preserve_existing = true) {
        $url = trim((string) $url);

        if($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $parsed_url = parse_url($url);
        $query_params = [];

        if(!empty($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
        }

        foreach($params as $key => $value) {
            $key = trim((string) $key);

            if($key === '' || $value === null || $value === '') {
                continue;
            }

            if($preserve_existing && array_key_exists($key, $query_params)) {
                continue;
            }

            $query_params[$key] = (string) $value;
        }

        $final_url = ($parsed_url['scheme'] ?? 'https') . '://' . ($parsed_url['host'] ?? '');

        if(isset($parsed_url['port'])) {
            $final_url .= ':' . $parsed_url['port'];
        }

        $final_url .= $parsed_url['path'] ?? '';

        if(!empty($query_params)) {
            $final_url .= '?' . http_build_query($query_params, '', '&', PHP_QUERY_RFC3986);
        }

        if(isset($parsed_url['fragment'])) {
            $final_url .= '#' . $parsed_url['fragment'];
        }

        return $final_url;
    }

    public static function get_share_tracking_url($url, string $source = 'direct_share', string $medium = 'share', string $context = 'fcc_share') {
        $campaign = 'fcc_' . preg_replace('/[^a-z0-9_]+/i', '_', mb_strtolower(trim($context))) ;
        $campaign = trim($campaign, '_') ?: 'fcc_share';

        $query_parameters = [
            'utm_source' => trim($source),
            'utm_medium' => trim($medium),
            'utm_campaign' => $campaign,
        ];

        if($source === 'qr') {
            $query_parameters['referrer'] = 'qr';
        }

        return self::append_query_parameters_to_url($url, $query_parameters, true);
    }

    public static function get_main_biolink_discount_query_params(int $user_id): array {
        if(!$user_id) {
            return [];
        }

        $main_biolink_id = self::get_user_main_biolink_id($user_id);

        if(!$main_biolink_id) {
            return [];
        }

        $discount_block = db()
            ->where('link_id', (int) $main_biolink_id)
            ->where('type', 'link_discount')
            ->where('is_enabled', 1)
            ->orderBy('`order`', 'ASC')
            ->getOne('biolinks_blocks', ['settings', 'location_url']);

        if(!$discount_block) {
            return [];
        }

        $discount_block->settings = json_decode($discount_block->settings ?? '');

        if(empty($discount_block->settings->apply_to_all_products)) {
            return [];
        }

        $decoded_url = $discount_block->settings->decoded_url ?? null;

        if(!$decoded_url && !empty($discount_block->location_url) && strpos($discount_block->location_url, 'https://thealoeveraco.shop/') === 0) {
            $decoded_url = self::decode_discount_link($discount_block->location_url);
        }

        if(!$decoded_url || !filter_var($decoded_url, FILTER_VALIDATE_URL)) {
            return [];
        }

        return self::get_forever_discount_query_params($decoded_url);
    }

   public static function get_product_webshop_link($referral, $product_id, $country_code = null, $browser_language = null) {                        
        if($biolink = db()->where('url', $referral)->where('type', 'biolink')->getOne('links', ['user_id'])) {
            if($user = db()->where('user_id', $biolink->user_id)->getOne('users', ['user_id', 'status', 'plan_id', 'preferences'])) {
                    if($user->status == 1) {
                        $referral_active = true;   
                    }
            }
        }
        
        if(!isset($referral_active)) {
            return;
        }

        $product = db()->where('blog_post_id', $product_id)->getOne('blog_posts');        

        if(!$product) {
            return;
        }

        if(isset($product->webshop_links) && !empty($product->webshop_links)) {                        
            $webshop_links = json_decode($product->webshop_links);    
            $preferences = json_decode($user->preferences ?? '');                                        
            $user_meta = $preferences->meta ?? '';            
            if (isset($user_meta->foreverId)) {
                $forever_id = $user_meta->foreverId;
            } else {
                $forever_id = '';
            }

            /* Market routing uses the caller-resolved preferred market code. */
            $webshop_country_code = self::resolve_forever_market_country_code($country_code);
            $webshop_base_url = $webshop_links->{$webshop_country_code} ?? null;

            if(!$webshop_base_url && !empty($webshop_links->us)) {
                $webshop_country_code = 'us';
                $webshop_base_url = $webshop_links->us;
            }

            if(!$webshop_base_url && !empty($webshop_links->gb)) {
                $webshop_country_code = 'gb';
                $webshop_base_url = $webshop_links->gb;
            }

            if($webshop_base_url) {
                $discount_params = self::get_main_biolink_discount_query_params((int) $user->user_id);
                $url = self::build_forever_destination_url($webshop_base_url, $forever_id, $webshop_country_code, $discount_params);
            }
        }

        return isset($url) ? $url : false;
    }

    public static function decode_discount_link($url) {         
        $curlhandle = curl_init();
        curl_setopt($curlhandle, CURLOPT_URL, $url);
        curl_setopt($curlhandle, CURLOPT_HEADER, 1);
        curl_setopt($curlhandle, CURLOPT_USERAGENT, 'googlebot');
        curl_setopt($curlhandle, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, 1);
        $final = curl_exec($curlhandle);
        if (preg_match('~Location: (.*)~i', $final, $lasturl)) {
            $location = trim($lasturl[1]);
            
            if ($location) {
                $curlhandle = curl_init();
                curl_setopt($curlhandle, CURLOPT_URL, $location);
                curl_setopt($curlhandle, CURLOPT_HEADER, 1);
                curl_setopt($curlhandle, CURLOPT_USERAGENT, 'googlebot');
                curl_setopt($curlhandle, CURLOPT_FOLLOWLOCATION, 0);
                curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, 1);
                $final = curl_exec($curlhandle);

                if (preg_match('~Location: (.*)~i', $final, $lasturl)) {
                    $destination = trim($lasturl[1]);                    
                }
            }
        }

        return isset($destination) ? $destination : '';
    }
    /* /Custom code */    
}
