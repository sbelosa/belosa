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

class Subscriber extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        $subscriber_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$subscriber = db()->where('subscriber_id', $subscriber_id)->where('user_id', $this->user->user_id)->getOne('subscribers')) {
            redirect('subscribers');
        }

        $subscriber->keys = json_decode($subscriber->keys ?? '');
        $subscriber->custom_parameters = json_decode($subscriber->custom_parameters ?? '', true);

        /* Get the subscribers_logs list for the user */
        $subscriber_logs = db()->where('subscriber_id', $subscriber->subscriber_id)->orderBy('subscriber_log_id', 'DESC')->get('subscribers_logs', 5);

        /* Get the website */
        $website = (new \Altum\Models\Website())->get_website_by_website_id($subscriber->website_id);

        /* Set a custom title */
        Title::set(sprintf(l('subscriber.title'), $subscriber->ip));

        /* Prepare the view */
        $data = [
            'subscriber' => $subscriber,
            'subscriber_logs' => $subscriber_logs,
            'website' => $website,
        ];

        $view = new \Altum\View('subscriber/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
