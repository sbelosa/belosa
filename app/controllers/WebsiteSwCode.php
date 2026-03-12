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

defined('ALTUMCODE') || die();

class WebsiteSwCode extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        $website_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        $website = db()->where('website_id', $website_id)->where('user_id', $this->user->user_id)->getOne('websites');

        /* Get the server monitor */
        if(!$website) {
            redirect('not-found');
        }

        $sw_js_url = SITE_URL . 'pixel_service_worker.js';

        $content = <<<ALTUM
let website_id = $website_id;
let website_pixel_key = '$website->pixel_key';
importScripts("$sw_js_url");
ALTUM;

        /* Prepare headers */
        header('Content-Description: File Transfer');
        header('Content-Type: text/javascript');
        header('Content-Disposition: attachment; filename="' . settings()->websites->service_worker_file_name . '.js"');
        header('Content-Length: ' . mb_strlen($content));

        /* Output data */
        echo $content;
    }

}
