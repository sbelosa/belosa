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

use Altum\Alerts;

defined('ALTUMCODE') || die();

class SubscriberUpdate extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.subscribers')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('subscribers');
        }

        /* Check for the plan limit */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `subscribers` WHERE `user_id` = {$this->user->user_id}")->fetch_object()->total ?? 0;
        if($this->user->plan_settings->subscribers_limit != -1 && $total_rows > $this->user->plan_settings->subscribers_limit) {
            redirect('subscribers');
        }

        $subscriber_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$subscriber = db()->where('subscriber_id', $subscriber_id)->where('user_id', $this->user->user_id)->getOne('subscribers')) {
            redirect('subscribers');
        }

        $subscriber->custom_parameters = json_decode($subscriber->custom_parameters ?? '');

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->user->user_id);

        if(!empty($_POST)) {
            /* Filter some of the variables */
            if(!isset($_POST['custom_parameter_key'])) {
                $_POST['custom_parameter_key'] = [];
                $_POST['custom_parameter_value'] = [];
            }

            $custom_parameters = [];
            foreach($_POST['custom_parameter_key'] as $key => $value) {
                if(empty(trim($value))) continue;

                $custom_parameter_key = input_clean($value, 64);
                $custom_parameter_value = input_clean($_POST['custom_parameter_value'][$key], 512);

                $custom_parameters[$custom_parameter_key] = $custom_parameter_value;
            }

            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

            /* Check for any errors */
            $required_fields = [];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    Alerts::add_field_error($field, l('global.error_message.empty_field'));
                }
            }

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }


            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                /* Database query */
                db()->where('subscriber_id', $subscriber->subscriber_id)->update('subscribers', [
                    'custom_parameters' => json_encode($custom_parameters),
                    'last_datetime' => get_date(),
                ]);

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.update1'), '<strong>' . $subscriber->ip . '</strong>'));

                /* Clear the cache */
                cache()->deleteItem('subscriber?subscriber_id=' . $subscriber->subscriber_id);

                /* Refresh the page */
                redirect('subscriber-update/' . $subscriber_id);
            }
        }

        /* Prepare the view */
        $data = [
            'subscriber' => $subscriber,
            'websites' => $websites,
        ];

        $view = new \Altum\View('subscriber-update/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
