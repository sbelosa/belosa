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
use Altum\Models\Billing;
use Altum\Models\Plan;
use Altum\Models\User;

defined('ALTUMCODE') || die();

class AccountPlan extends Controller {

    private function has_stripe_billing_context(): bool {
        $extra = $this->decode_extra($this->user->extra ?? null);
        $payment_processor = trim((string) ($this->user->payment_processor ?? ''));
        $subscription_id = trim((string) ($this->user->payment_subscription_id ?? ''));

        return $payment_processor === 'stripe'
            || str_starts_with($subscription_id, 'sub_')
            || (
                $payment_processor === ''
                && $subscription_id === ''
                && !empty($extra->stripe_customer_id ?? null)
                && !empty($extra->billing_stripe_status ?? null)
            );
    }

    private function decode_extra($extra): object {
        if(is_string($extra)) {
            $extra = json_decode($extra);
        }

        if(is_array($extra)) {
            $extra = (object) $extra;
        }

        if(!is_object($extra)) {
            $extra = (object) [];
        }

        return $extra;
    }

    private function configure_stripe_api(): bool {
        if(!settings()->payment->is_enabled || empty(settings()->stripe->is_enabled) || empty(settings()->stripe->secret_key)) {
            return false;
        }

        \Stripe\Stripe::setApiKey(settings()->stripe->secret_key);
        \Stripe\Stripe::setApiVersion('2023-10-16');

        return true;
    }

    private function persist_stripe_customer_id(int $user_id, ?string $stripe_customer_id): void {
        if(!$stripe_customer_id || !str_starts_with($stripe_customer_id, 'cus_')) {
            return;
        }

        $user = db()->where('user_id', $user_id)->getOne('users', ['extra']);

        if(!$user) {
            return;
        }

        $extra = $this->decode_extra($user->extra ?? null);

        if(($extra->stripe_customer_id ?? null) === $stripe_customer_id) {
            return;
        }

        $extra->stripe_customer_id = $stripe_customer_id;

        db()->where('user_id', $user_id)->update('users', [
            'extra' => json_encode($extra),
        ]);

        cache()->deleteItemsByTag('user_id=' . $user_id);
    }

    private function resolve_stripe_customer_id(): ?string {
        if(!$this->configure_stripe_api()) {
            return null;
        }

        if(!$this->has_stripe_billing_context()) {
            return null;
        }

        $extra = $this->decode_extra($this->user->extra ?? null);
        $stored_customer_id = trim((string) ($extra->stripe_customer_id ?? ''));

        if($stored_customer_id !== '' && str_starts_with($stored_customer_id, 'cus_')) {
            try {
                \Stripe\Customer::retrieve($stored_customer_id);
                return $stored_customer_id;
            } catch(\Throwable $exception) {
            }
        }

        $subscription_id = trim((string) ($this->user->payment_subscription_id ?? ''));
        if($subscription_id !== '' && str_starts_with($subscription_id, 'sub_')) {
            try {
                $subscription = \Stripe\Subscription::retrieve($subscription_id);
                $customer_id = is_string($subscription->customer ?? null) ? $subscription->customer : null;

                if($customer_id) {
                    $this->persist_stripe_customer_id((int) $this->user->user_id, $customer_id);
                    return $customer_id;
                }
            } catch(\Throwable $exception) {
            }
        }

        $email = trim((string) ($this->user->email ?? ''));
        if($email === '') {
            return null;
        }

        try {
            $customers = \Stripe\Customer::all([
                'email' => $email,
                'limit' => 10,
            ]);
        } catch(\Throwable $exception) {
            return null;
        }

        if(count($customers->data) === 1) {
            $customer_id = $customers->data[0]->id ?? null;

            if($customer_id) {
                $this->persist_stripe_customer_id((int) $this->user->user_id, $customer_id);
                return $customer_id;
            }
        }

        $active_customer_ids = [];

        foreach($customers->data as $customer) {
            try {
                $subscriptions = \Stripe\Subscription::all([
                    'customer' => $customer->id,
                    'status' => 'all',
                    'limit' => 10,
                ]);
            } catch(\Throwable $exception) {
                continue;
            }

            foreach($subscriptions->data as $subscription) {
                if(in_array($subscription->status ?? '', ['active', 'trialing', 'past_due', 'unpaid'], true)) {
                    $active_customer_ids[$customer->id] = $customer->id;
                    break;
                }
            }
        }

        if(count($active_customer_ids) === 1) {
            $customer_id = array_values($active_customer_ids)[0];
            $this->persist_stripe_customer_id((int) $this->user->user_id, $customer_id);
            return $customer_id;
        }

        return null;
    }

    private function can_open_stripe_portal(): bool {
        if(!$this->configure_stripe_api()) {
            return false;
        }

        return $this->resolve_stripe_customer_id() !== null;
    }

    private function create_stripe_portal_url(): string {
        if(!$this->configure_stripe_api()) {
            throw new \Exception(l('account_plan.billing.portal_unavailable'));
        }

        $customer_id = $this->resolve_stripe_customer_id();

        if(!$customer_id) {
            throw new \Exception(l('account_plan.billing.portal_unavailable'));
        }

        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $customer_id,
            'return_url' => url('account-plan'),
        ]);

        $portal_url = trim((string) ($session->url ?? ''));

        if($portal_url === '') {
            throw new \Exception(l('account_plan.billing.portal_unavailable'));
        }

        return $portal_url;
    }

    public function index() {

        \Altum\Authentication::guard();

        if(vip_funnel_demo_render_locked_route($this, $this->user, 'account_plan', [
            'back_url' => url('dashboard'),
        ])) {
            return;
        }

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

        $billing_summary = (new Billing())->get_user_billing_summary((int) $this->user->user_id);
        $stripe_portal_available = $this->can_open_stripe_portal();

        /* Prepare the view */
        $data = [
            'suggested_plan' => $suggested_plan ?? null,
            'suggested_plan_code' => $suggested_plan_code ?? null,
            'active_paid_plans' => $active_paid_plans,
            'billing_summary' => $billing_summary,
            'stripe_portal_available' => $stripe_portal_available,
            'stripe_portal_url' => url('account-plan/stripe_portal'),
        ];

        $view = new \Altum\View('account-plan/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function stripe_portal() {

        \Altum\Authentication::guard();

        if(vip_funnel_demo_is_sandbox_user($this->user)) {
            Alerts::add_info(vip_funnel_demo_get_locked_action_message('account_plan'));
            redirect('account-plan');
        }

        try {
            header('Location: ' . $this->create_stripe_portal_url());
            die();
        } catch(\Throwable $exception) {
            Alerts::add_error(trim((string) $exception->getMessage()) ?: l('account_plan.billing.portal_unavailable'));
            redirect('account-plan');
        }
    }

    public function cancel_subscription() {

        \Altum\Authentication::guard();

        if(vip_funnel_demo_is_sandbox_user($this->user)) {
            Alerts::add_info(vip_funnel_demo_get_locked_action_message('account_plan'));
            redirect('account-plan');
        }

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
