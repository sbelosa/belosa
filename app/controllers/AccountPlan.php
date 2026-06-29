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

    private function create_stripe_portal_url(?string $flow_type = null): string {
        if(!$this->configure_stripe_api()) {
            throw new \Exception(l('account_plan.billing.portal_unavailable'));
        }

        $customer_id = $this->resolve_stripe_customer_id();

        if(!$customer_id) {
            throw new \Exception(l('account_plan.billing.portal_unavailable'));
        }

        $session_payload = [
            'customer' => $customer_id,
            'return_url' => url('account-plan'),
        ];

        if($flow_type) {
            $session_payload['flow_data'] = [
                'type' => $flow_type,
            ];
        }

        $session = \Stripe\BillingPortal\Session::create($session_payload);

        $portal_url = trim((string) ($session->url ?? ''));

        if($portal_url === '') {
            throw new \Exception(l('account_plan.billing.portal_unavailable'));
        }

        return $portal_url;
    }

    private function get_latest_open_stripe_invoice(array $billing_summary = []): ?\Stripe\Invoice {
        if(!$this->configure_stripe_api()) {
            return null;
        }

        $invoice_ids = [];
        $latest_invoice_id = trim((string) ($billing_summary['last_invoice_id'] ?? ''));

        if(str_starts_with($latest_invoice_id, 'in_')) {
            $invoice_ids[$latest_invoice_id] = $latest_invoice_id;
        }

        $subscription_id = trim((string) ($this->user->payment_subscription_id ?? ''));

        if(str_starts_with($subscription_id, 'sub_')) {
            try {
                $invoices = \Stripe\Invoice::all([
                    'subscription' => $subscription_id,
                    'status' => 'open',
                    'limit' => 5,
                ]);

                foreach($invoices->data ?? [] as $invoice) {
                    $invoice_id = (string) ($invoice->id ?? '');

                    if(str_starts_with($invoice_id, 'in_')) {
                        $invoice_ids[$invoice_id] = $invoice_id;
                    }
                }
            } catch(\Throwable $exception) {
            }
        }

        foreach(array_values($invoice_ids) as $invoice_id) {
            try {
                $invoice = \Stripe\Invoice::retrieve($invoice_id);

                if(($invoice->status ?? '') === 'open' && (int) ($invoice->amount_remaining ?? 0) > 0) {
                    return $invoice;
                }
            } catch(\Throwable $exception) {
            }
        }

        return null;
    }

    private function get_stripe_identifier($value): string {
        if(is_string($value)) {
            return trim($value);
        }

        if(is_object($value) && isset($value->id)) {
            return trim((string) $value->id);
        }

        if(is_array($value) && isset($value['id'])) {
            return trim((string) $value['id']);
        }

        return '';
    }

    private function get_retry_invoice_payment_payload(\Stripe\Invoice $invoice): array {
        $customer = null;
        $customer_id = $this->get_stripe_identifier($invoice->customer ?? null);
        $subscription_id = $this->get_stripe_identifier($invoice->subscription ?? null);

        if(!$subscription_id) {
            $subscription_id = trim((string) ($this->user->payment_subscription_id ?? ''));
        }

        if(str_starts_with($customer_id, 'cus_')) {
            try {
                $customer = \Stripe\Customer::retrieve($customer_id);
            } catch(\Throwable $exception) {
            }
        }

        $customer_payment_method_id = $this->get_stripe_identifier($customer->invoice_settings->default_payment_method ?? null);

        if(str_starts_with($customer_payment_method_id, 'pm_')) {
            if(str_starts_with($subscription_id, 'sub_')) {
                try {
                    $subscription = \Stripe\Subscription::retrieve($subscription_id);
                    $subscription_payment_method_id = $this->get_stripe_identifier($subscription->default_payment_method ?? null);

                    if($subscription_payment_method_id !== $customer_payment_method_id) {
                        \Stripe\Subscription::update($subscription_id, [
                            'default_payment_method' => $customer_payment_method_id,
                        ]);
                    }
                } catch(\Throwable $exception) {
                }
            }

            return ['payment_method' => $customer_payment_method_id];
        }

        if(str_starts_with($subscription_id, 'sub_')) {
            try {
                $subscription = \Stripe\Subscription::retrieve($subscription_id);
                $subscription_payment_method_id = $this->get_stripe_identifier($subscription->default_payment_method ?? null);

                if(str_starts_with($subscription_payment_method_id, 'pm_')) {
                    return ['payment_method' => $subscription_payment_method_id];
                }

                $subscription_source_id = $this->get_stripe_identifier($subscription->default_source ?? null);

                if($subscription_source_id) {
                    return ['source' => $subscription_source_id];
                }
            } catch(\Throwable $exception) {
            }
        }

        $invoice_payment_method_id = $this->get_stripe_identifier($invoice->default_payment_method ?? null);

        if(str_starts_with($invoice_payment_method_id, 'pm_')) {
            return ['payment_method' => $invoice_payment_method_id];
        }

        $invoice_source_id = $this->get_stripe_identifier($invoice->default_source ?? null);

        if($invoice_source_id) {
            return ['source' => $invoice_source_id];
        }

        $customer_source_id = $this->get_stripe_identifier($customer->default_source ?? null);

        if($customer_source_id) {
            return ['source' => $customer_source_id];
        }

        return [];
    }

    private function format_stripe_invoice_amount(?\Stripe\Invoice $invoice): string {
        if(!$invoice || !isset($invoice->amount_remaining, $invoice->currency)) {
            return '';
        }

        $currency = mb_strtoupper((string) $invoice->currency);
        $amount = in_array($currency, get_zero_decimal_currencies_array()) ? (float) $invoice->amount_remaining : ((float) $invoice->amount_remaining / 100);

        return nr($amount, 2) . ' ' . $currency;
    }

    private function get_billing_recovery_context(array $billing_summary): array {
        $invoice = $this->get_latest_open_stripe_invoice($billing_summary);

        return [
            'invoice_id' => $invoice->id ?? ($billing_summary['last_invoice_id'] ?? null),
            'invoice_status' => $invoice->status ?? null,
            'invoice_amount' => $this->format_stripe_invoice_amount($invoice),
            'hosted_invoice_url' => trim((string) ($invoice->hosted_invoice_url ?? '')),
            'can_retry_now' => (bool) ($invoice && ($invoice->status ?? '') === 'open'),
        ];
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
        $billing_recovery = in_array((string) ($billing_summary['billing_state'] ?? ''), [Billing::STATE_PAST_DUE, Billing::STATE_PAST_DUE_CRITICAL], true)
            ? $this->get_billing_recovery_context($billing_summary)
            : [];

        /* Prepare the view */
        $data = [
            'suggested_plan' => $suggested_plan ?? null,
            'suggested_plan_code' => $suggested_plan_code ?? null,
            'active_paid_plans' => $active_paid_plans,
            'billing_summary' => $billing_summary,
            'billing_recovery' => $billing_recovery,
            'stripe_portal_available' => $stripe_portal_available,
            'stripe_portal_url' => url('account-plan/stripe_portal'),
            'stripe_payment_method_url' => url('account-plan/stripe_payment_method'),
            'stripe_retry_payment_url' => url('account-plan/retry_stripe_payment' . \Altum\Csrf::get_url_query()),
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

    public function stripe_payment_method() {

        \Altum\Authentication::guard();

        if(vip_funnel_demo_is_sandbox_user($this->user)) {
            Alerts::add_info(vip_funnel_demo_get_locked_action_message('account_plan'));
            redirect('account-plan');
        }

        try {
            header('Location: ' . $this->create_stripe_portal_url('payment_method_update'));
            die();
        } catch(\Throwable $exception) {
            Alerts::add_error(trim((string) $exception->getMessage()) ?: l('account_plan.billing.portal_unavailable'));
            redirect('account-plan');
        }
    }

    public function retry_stripe_payment() {

        \Altum\Authentication::guard();

        if(vip_funnel_demo_is_sandbox_user($this->user)) {
            Alerts::add_info(vip_funnel_demo_get_locked_action_message('account_plan'));
            redirect('account-plan');
        }

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('account-plan');
        }

        $billing_model = new Billing();
        $billing_summary = $billing_model->get_user_billing_summary((int) $this->user->user_id);
        $billing_state = (string) ($billing_summary['billing_state'] ?? '');

        if(!in_array($billing_state, [Billing::STATE_PAST_DUE, Billing::STATE_PAST_DUE_CRITICAL], true)) {
            Alerts::add_info(l('account_plan.billing.retry_not_needed'));
            redirect('account-plan');
        }

        $invoice = $this->get_latest_open_stripe_invoice($billing_summary);

        if(!$invoice) {
            Alerts::add_error(l('account_plan.billing.retry_no_invoice'));
            redirect('account-plan');
        }

        try {
            $payment_payload = $this->get_retry_invoice_payment_payload($invoice);
            $paid_invoice = $invoice->pay($payment_payload);

            if($paid_invoice instanceof \Stripe\Invoice) {
                $invoice = $paid_invoice;
            }

            if(!empty($invoice->paid) || ($invoice->status ?? '') === 'paid') {
                $invoice_currency = !empty($invoice->currency) ? mb_strtoupper((string) $invoice->currency) : null;
                $invoice_amount_paid = isset($invoice->amount_paid)
                    ? (in_array($invoice_currency, get_zero_decimal_currencies_array()) ? (float) $invoice->amount_paid : ((float) $invoice->amount_paid / 100))
                    : null;

                $billing_model->handle_successful_payment([
                    'user_id' => (int) $this->user->user_id,
                    'email' => $this->user->email,
                    'processor' => 'stripe',
                    'stripe_subscription_id' => $this->user->payment_subscription_id,
                    'stripe_invoice_id' => $invoice->id ?? null,
                    'amount' => $invoice_amount_paid,
                    'currency' => $invoice_currency,
                    'stripe_status' => 'active',
                    'occurred_at' => get_date(),
                ]);

                Alerts::add_success(l('account_plan.billing.retry_success'));
                redirect('account-plan');
            }

            Alerts::add_info(l('account_plan.billing.retry_action_required'));
        } catch(\Throwable $exception) {
            $message = trim((string) $exception->getMessage());

            if(!empty($invoice->hosted_invoice_url)) {
                Alerts::add_error(sprintf(l('account_plan.billing.retry_failed_with_invoice'), $message ?: l('global.unknown')));
            } else {
                Alerts::add_error(sprintf(l('account_plan.billing.retry_failed'), $message ?: l('global.unknown')));
            }
        }

        redirect('account-plan');
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
                (new User())->cancel_subscription($this->user->user_id, true);
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
