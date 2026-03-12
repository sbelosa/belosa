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

use Altum\Title;

defined('ALTUMCODE') || die();

class RssAutomation extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        $rss_automation_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$rss_automation = db()->where('rss_automation_id', $rss_automation_id)->where('user_id', $this->user->user_id)->getOne('rss_automations')) {
            redirect('rss-automations');
        }

        $rss_automation->settings = json_decode($rss_automation->settings ?? '');

        /* Get the subscribers_logs list for the user */
        $subscribers_logs = db()->where('rss_automation_id', $rss_automation->rss_automation_id)->orderBy('subscriber_log_id', 'DESC')->get('subscribers_logs', 5);

        /* Get the website */
        $website = (new \Altum\Models\Website())->get_website_by_website_id($rss_automation->website_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        /* Set a custom title */
        Title::set(sprintf(l('rss_automation.title'), $rss_automation->name));

        /* Prepare the view */
        $data = [
            'rss_automation' => $rss_automation,
            'notifications_ttl' => $notifications_ttl,
            'subscriber_logs' => $subscribers_logs,
            'website' => $website,
        ];

        $view = new \Altum\View('rss-automation/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
