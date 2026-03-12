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

namespace Altum\Controllers;

use Altum\Models\User;

defined('ALTUMCODE') || die();

class Pixel extends Controller {

    public function index() {
        $seconds_to_cache = settings()->websites->pixel_cache;
        header('Content-Type: application/javascript');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $seconds_to_cache) . ' GMT');
        header('Pragma: cache');
        header('Cache-Control: max-age=' . $seconds_to_cache);

        /* Check against bots */
        $CrawlerDetect = new \Jaybizzle\CrawlerDetect\CrawlerDetect();

        if($CrawlerDetect->isCrawler()) {
            die("console.log('" . settings()->main->title . " (" . SITE_URL. "): Bot usage has been detected, pixel stopped from executing.')");
        }

        $pixel_key = isset($this->params[0]) ? input_clean($this->params[0]) : null;

        /* Get the details of the website from the database */
        $website = (new \Altum\Models\Website())->get_website_by_pixel_key($pixel_key);

        /* Make sure the website has access */
        if(!$website) {
            die("console.log('" . settings()->main->title . " (" . SITE_URL. "): No website found for this pixel.')");
        }

        if(!$website->is_enabled) {
            die("console.log('" . settings()->main->title . " (" . SITE_URL. "): Website disabled.')");
        }

        /* Make sure to get the user data and confirm the user is ok */
        $user = (new \Altum\Models\User())->get_user_by_user_id($website->user_id);

        if(!$user) {
            die("console.log('" . settings()->main->title . " (" . SITE_URL. "): Website owner not found.')");
        }

        if($user->status != 1) {
            die("console.log('" . settings()->main->title . " (" . SITE_URL. "): Website owner is disabled.')");
        }

        /* Check for a custom domain */
        if(isset(\Altum\Router::$data['domain']) && $website->domain_id != \Altum\Router::$data['domain']->domain_id) {
            die("console.log('" . settings()->main->title . " (" . SITE_URL. "): Domain id mismatch.')");
        }

        /* Process the plan of the user */
        (new User())->process_user_plan_expiration_by_user($user);

        /* Set the default language depending on the user */
        \Altum\Language::set_by_name($user->language);

        /* Detect the location */
        try {
            $maxmind = (get_maxmind_reader_city())->get(get_ip());
        } catch(\Exception $exception) {
            /* :) */
        }
        $continent_code = isset($maxmind) && isset($maxmind['continent']) ? $maxmind['continent']['code'] : null;
        $country_code = isset($maxmind) && isset($maxmind['country']) ? $maxmind['country']['iso_code'] : null;

        /* Detect extra details about the user */
        $whichbrowser = get_whichbrowser();
        $browser_name = $whichbrowser->browser->name ?? null;
        $os_name = $whichbrowser->os->name ?? null;
        $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;

        /* Targeting */
        $targeting = true;

        if($continent_code && count($website->widget->display_continents ?? []) && !in_array($continent_code, $website->widget->display_continents ?? [])) {
            $targeting = false;
        }

        if($country_code && count($website->widget->display_countries ?? []) && !in_array($country_code, $website->widget->display_countries ?? [])) {
            $targeting = false;
        }

        if($os_name && count($website->widget->display_operating_systems ?? []) && !in_array($os_name, $website->widget->display_operating_systems ?? [])) {
            $targeting = false;
        }

        if($browser_language && count($website->widget->display_languages ?? []) && !in_array($browser_language, $website->widget->display_languages ?? [])) {
            $targeting = false;
        }

        if($browser_name && count($website->widget->display_browsers ?? []) && !in_array($browser_name, $website->widget->display_browsers ?? [])) {
            $targeting = false;
        }

        /* Determine the notification branding settings */
        if(!$user->plan_settings->removable_branding_is_enabled) {
            $website->widget->display_branding = true;
            $website->button->display_branding = true;
        }

        if(!$user->plan_settings->custom_branding_is_enabled) {
            $website->settings->branding_name = '';
            $website->settings->branding_url = '';

        }

        if($targeting) {

            /* Main View */
            $data = [
                'pixel_key'             => $pixel_key,
                'website'               => $website,
                'user'                  => $user
            ];

            $view = new \Altum\View('pixel/index', (array) $this);

            $view_data = $view->run($data);

            /* Remove <script> tags */
            $view_data = str_replace('<script>', '', $view_data);
            $view_data = str_replace('</script>', '', $view_data);

            $pattern = '/\/\*[\s\S]*?\*\//';
            $view_data = preg_replace($pattern, '', $view_data);
            $view_data = preg_replace('/[ \t]+/', ' ', $view_data);
            $view_data = preg_replace('/\n{2,}/', "\n", $view_data);
            $view_data = trim($view_data);

            echo $view_data;

        } else {
            echo '';
        }

    }

}
