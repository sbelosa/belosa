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

class Website extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        $website_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$website = db()->where('website_id', $website_id)->where('user_id', $this->user->user_id)->getOne('websites')) {
            redirect('websites');
        }

        $website->settings = json_decode($website->settings ?? '');
        $website->keys = json_decode($website->keys ?? '');

        /* Get the campaigns list for the website */
        $campaigns = db()->where('website_id', $website->website_id)->orderBy('campaign_id', 'DESC')->get('campaigns', 5);
        foreach($campaigns as $row) $row->settings = json_decode($row->settings ?? '');

        /* Get the subscribers list for the website */
        $subscribers = db()->where('website_id', $website->website_id)->orderBy('subscriber_id', 'DESC')->get('subscribers', 5);

        /* Get the subscribers_logs list for the website */
        $subscribers_logs = db()->where('website_id', $website->website_id)->orderBy('subscriber_log_id', 'DESC')->get('subscribers_logs', 5);

        /* Get available custom domains */
        $domains = (new \Altum\Models\Domain())->get_available_domains_by_user($this->user);

        /* Set a custom title */
        Title::set(sprintf(l('website.title'), $website->name));

        /* Prepare the view */
        $data = [
            'domains' => $domains,
            'website' => $website,
            'campaigns' => $campaigns,
            'subscribers' => $subscribers,
            'subscriber_logs' => $subscribers_logs,
        ];

        $view = new \Altum\View('website/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
