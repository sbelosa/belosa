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

class Campaign extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        $campaign_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$campaign = db()->where('campaign_id', $campaign_id)->where('user_id', $this->user->user_id)->getOne('campaigns')) {
            redirect('campaigns');
        }

        $campaign->settings = json_decode($campaign->settings ?? '');

        /* Get the subscribers_logs list for the user */
        $subscribers_logs = db()->where('campaign_id', $campaign->campaign_id)->orderBy('subscriber_log_id', 'DESC')->get('subscribers_logs', 5);

        /* Get the website */
        $website = (new \Altum\Models\Website())->get_website_by_website_id($campaign->website_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        /* Set a custom title */
        Title::set(sprintf(l('campaign.title'), $campaign->name));

        /* Prepare the view */
        $data = [
            'campaign' => $campaign,
            'notifications_ttl' => $notifications_ttl,
            'subscriber_logs' => $subscribers_logs,
            'website' => $website,
        ];

        $view = new \Altum\View('campaign/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
