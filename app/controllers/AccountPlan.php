<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
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
use Altum\Models\Plan;
use Altum\Models\User;

defined('ALTUMCODE') || die();

class AccountPlan extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Get the account header menu */
        $menu = new \Altum\View('partials/account_header_menu', (array) $this);
        $this->add_view_content('account_header_menu', $menu->run());

        $plan_model = new Plan();
        $active_paid_plans = [];

        if(settings()->payment->is_enabled) {
            foreach($plan_model->get_plans() as $plan) {
                if((int) ($plan->status ?? 0) !== 1) {
                    continue;
                }

                $active_paid_plans[$plan->plan_id] = $plan;
            }
        }

        /* Suggested plan */
        if(settings()->payment->is_enabled && !empty($this->user->plan->additional_settings->suggested_plan_id ?? null)) {
            $suggested_plan = $plan_model->get_plan_by_id($this->user->plan->additional_settings->suggested_plan_id);

            if($this->user->plan->additional_settings->suggested_plan_code_id) {
                $suggested_plan_code = db()->where('code_id', $this->user->plan->additional_settings->suggested_plan_code_id)->getOne('codes');
            }
        }

        if(settings()->payment->is_enabled && empty($suggested_plan) && !empty($active_paid_plans)) {
            $current_plan_id = (string) ($this->user->plan_id ?? '');
            $current_plan_order = (int) ($this->user->plan->order ?? -1);

            foreach($active_paid_plans as $plan) {
                if((string) $plan->plan_id === $current_plan_id) {
                    continue;
                }

                if(in_array($current_plan_id, ['free', 'guest', 'custom'], true) || (int) ($plan->order ?? 0) > $current_plan_order) {
                    $suggested_plan = $plan;
                    break;
                }
            }

            if(empty($suggested_plan)) {
                foreach($active_paid_plans as $plan) {
                    if((string) $plan->plan_id !== $current_plan_id) {
                        $suggested_plan = $plan;
                        break;
                    }
                }
            }
        }

        /* Prepare the view */
        $data = [
            'suggested_plan' => $suggested_plan ?? null,
            'suggested_plan_code' => $suggested_plan_code ?? null,
            'active_paid_plans' => $active_paid_plans,
        ];

        $view = new \Altum\View('account-plan/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function cancel_subscription() {

        \Altum\Authentication::guard();

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('account-plan');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            try {
                (new User())->cancel_subscription($this->user->user_id);
            } catch (\Exception $exception) {
                Alerts::add_error($exception->getCode() . ':' . $exception->getMessage());
                redirect('account-plan');
            }

            /* Set a nice success message */
            Alerts::add_success(l('account_plan.success_message.subscription_canceled'));

            redirect('account-plan');

        }

    }

}
