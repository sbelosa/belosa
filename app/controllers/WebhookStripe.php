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

use Altum\Models\Billing;
use Altum\Models\Payments;

defined('ALTUMCODE') || die();

class WebhookStripe extends Controller {

    /* Custom code: FC-2026-03-17: Stripe billing risk helpers */
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

    private function extract_metadata($object): object {
        $metadata = $object->metadata ?? null;

        if(!$metadata && isset($object->lines->data[0]->metadata)) {
            $metadata = $object->lines->data[0]->metadata;
        }

        if(!$metadata && isset($object->parent->subscription_details->metadata)) {
            $metadata = $object->parent->subscription_details->metadata;
        }

        if(is_array($metadata)) {
            $metadata = (object) $metadata;
        }

        return is_object($metadata) ? $metadata : (object) [];
    }

    private function extract_subscription_id($object): ?string {
        return $object->subscription
            ?? ($object->parent->subscription_details->subscription ?? null)
            ?? ($object->lines->data[0]->parent->subscription_item_details->subscription ?? null)
            ?? null;
    }

    private function get_payment_intent_details($payment_intent_id): array {
        if(!$payment_intent_id) {
            return [
                'reason_code' => null,
                'reason_text' => null,
            ];
        }

        try {
            $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);

            return [
                'reason_code' => $payment_intent->last_payment_error->decline_code ?? $payment_intent->last_payment_error->code ?? null,
                'reason_text' => $payment_intent->last_payment_error->message ?? null,
            ];
        } catch(\Exception $exception) {
            return [
                'reason_code' => null,
                'reason_text' => $exception->getMessage(),
            ];
        }
    }

    private function persist_stripe_customer_id(?int $user_id, ?string $stripe_customer_id, ?string $stripe_subscription_id = null, ?string $email = null): void {
        if(!$stripe_customer_id || !str_starts_with($stripe_customer_id, 'cus_')) {
            return;
        }

        $user = null;

        if($user_id) {
            $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'extra']);
        }

        if(!$user && $stripe_subscription_id) {
            $user = db()->where('payment_subscription_id', $stripe_subscription_id)->getOne('users', ['user_id', 'extra']);
        }

        if(!$user && $email) {
            $user = db()->where('email', $email)->getOne('users', ['user_id', 'extra']);
        }

        if(!$user) {
            return;
        }

        $extra = $this->decode_extra($user->extra ?? null);

        if(($extra->stripe_customer_id ?? null) === $stripe_customer_id) {
            return;
        }

        $extra->stripe_customer_id = $stripe_customer_id;

        db()->where('user_id', $user->user_id)->update('users', [
            'extra' => json_encode($extra),
        ]);

        cache()->deleteItemsByTag('user_id=' . $user->user_id);
    }

    private function stripe_amount_to_decimal($amount, ?string $currency): float {
        $currency = mb_strtoupper((string) $currency);
        $amount = (int) $amount;

        return in_array($currency, get_zero_decimal_currencies_array(), true) ? (float) $amount : $amount / 100;
    }

    private function resolve_charge_from_refund_event(string $event_type, $session) {
        if($event_type === 'charge.refunded') {
            return $session;
        }

        $charge_id = is_string($session->charge ?? null) ? $session->charge : null;

        if(!$charge_id) {
            return null;
        }

        try {
            return \Stripe\Charge::retrieve($charge_id);
        } catch(\Exception $exception) {
            return null;
        }
    }

    private function process_refund_event(string $event_type, $session): bool {
        $charge = $this->resolve_charge_from_refund_event($event_type, $session);

        if(!$charge) {
            return false;
        }

        $invoice_id = is_string($charge->invoice ?? null) ? $charge->invoice : null;
        $payment_intent_id = is_string($charge->payment_intent ?? null) ? $charge->payment_intent : null;

        $payment = null;

        if($invoice_id) {
            $payment = db()
                ->where('processor', 'stripe')
                ->where('payment_id', $invoice_id)
                ->getOne('payments');
        }

        if(!$payment && $payment_intent_id) {
            $payment = db()
                ->where('processor', 'stripe')
                ->where('payment_id', $payment_intent_id)
                ->getOne('payments');
        }

        if(!$payment) {
            return false;
        }

        $currency = mb_strtoupper((string) ($charge->currency ?? $payment->currency ?? settings()->payment->default_currency));
        $stripe_refunded_total = $this->stripe_amount_to_decimal($charge->amount_refunded ?? 0, $currency);

        if($stripe_refunded_total <= 0) {
            return false;
        }

        $refunds = (array) json_decode($payment->refunds ?? '[]');
        $known_refund_ids = [];

        foreach($refunds as $refund) {
            $refund = is_array($refund) ? (object) $refund : $refund;
            $stripe_refund_id = (string) ($refund->stripe_refund_id ?? '');

            if($stripe_refund_id !== '') {
                $known_refund_ids[$stripe_refund_id] = true;
            }
        }

        foreach(($charge->refunds->data ?? []) as $stripe_refund) {
            $stripe_refund_id = (string) ($stripe_refund->id ?? '');

            if($stripe_refund_id === '' || isset($known_refund_ids[$stripe_refund_id])) {
                continue;
            }

            $refunds[] = [
                'id' => count($refunds) + 1,
                'amount' => number_format($this->stripe_amount_to_decimal($stripe_refund->amount ?? 0, $stripe_refund->currency ?? $currency), 2, '.', ''),
                'reason' => $stripe_refund->reason ?? 'stripe_refund',
                'origin' => 'stripe',
                'stripe_refund_id' => $stripe_refund_id,
                'stripe_charge_id' => $charge->id ?? null,
                'datetime' => !empty($stripe_refund->created) ? date('Y-m-d H:i:s', (int) $stripe_refund->created) : get_date(),
            ];

            $known_refund_ids[$stripe_refund_id] = true;
        }

        $refunded_total = min((float) $payment->total_amount, $stripe_refunded_total);
        $refunded_status = $refunded_total >= (float) $payment->total_amount ? 'fully_refunded' : 'partially_refunded';

        db()->where('id', (int) $payment->id)->update('payments', [
            'status' => 'refunded',
            'refunded_total' => number_format($refunded_total, 2, '.', ''),
            'refunded_status' => $refunded_status,
            'refunds' => json_encode($refunds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        cache()->deleteItemsByTag('user_id=' . $payment->user_id);

        return true;
    }
    /* /Custom code: FC-2026-03-17 */

    public function index() {

        /* Make sure no cache is being used on the endpoint */
		header('Cache-Control: no-store');

        if(!in_array(settings()->license->type, ['Extended License', 'extended'])) {
            throw_404();
        }

        if((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST')) {
            throw_404();
        }

        /* Get the headers */
        $headers = getallheaders();

        /* Get the payload */
        $payload = trim(@file_get_contents('php://input'));

        /* Log for debugging purposes */
        debug_log('[' . \Altum\Router::$controller . '] ' . print_r(['headers' => $headers, 'payload' => $payload], true));

        /* Initiate Stripe */
        \Stripe\Stripe::setApiKey(settings()->stripe->secret_key);
        \Stripe\Stripe::setApiVersion('2023-10-16');

        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, settings()->stripe->webhook_secret);
        } catch(\Exception $exception) {
            /* Invalid payload */
            echo $exception->getMessage(); http_response_code(400); die();
        }

        /* Custom code: FC-2026-03-17: process Stripe billing risk and lifecycle events */
        $billing = new Billing();

        if($billing->has_processed_stripe_event($event->id ?? null)) {
            die('Event already processed.');
        }

        if(!in_array($event->type, ['invoice.paid', 'invoice.payment_failed', 'checkout.session.completed', 'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted', 'charge.refunded', 'refund.created'])) {
            die('Event type not needed to be handled, returning ok.');
        }
        /* /Custom code: FC-2026-03-17 */

        $session = $event->data->object;
        $external_payment_id = $session->id;
        $event_occurred_at = date('Y-m-d H:i:s', (int) ($event->created ?? time()));
        $stripe_customer_id = is_string($session->customer ?? null) ? $session->customer : null;
        $stripe_current_period_end = !empty($session->lines->data[0]->period->end) ? date('Y-m-d H:i:s', (int) $session->lines->data[0]->period->end) : null;

        switch($event->type) {
            case 'charge.refunded':
            case 'refund.created':

                $this->process_refund_event($event->type, $session);

                echo 'successful';
                return;

            /* Handle trial start */
            case 'customer.subscription.created':

                /* Only run when the subscription starts with a trial */
                if($session->status === 'trialing') {

                    $metadata = $session->metadata;

                    $user_id = (int) $metadata->user_id;
                    $plan_id = (int) $metadata->plan_id;
                    $payment_frequency = $metadata->payment_frequency;
                    $code = isset($metadata->code) ? $metadata->code : '';
                    $discount_amount = isset($metadata->discount_amount) ? $metadata->discount_amount : 0;
                    $base_amount = isset($metadata->base_amount) ? $metadata->base_amount : 0;
                    $taxes_ids = isset($metadata->taxes_ids) ? $metadata->taxes_ids : null;

                    $payment_type = 'recurring';
                    $payment_subscription_id = $session->id;

                    /* Set to 0 since no payment yet */
                    $payer_email = null;
                    $payer_name = null;
                    $payment_currency = settings()->payment->default_currency;
                    $payment_total = 0;

                }

                $billing->sync_subscription_status([
                    'processor' => 'stripe',
                    'user_id' => $user_id ?? null,
                    'email' => null,
                    'plan_id' => $plan_id ?? null,
                    'stripe_event_id' => $event->id ?? null,
                    'stripe_subscription_id' => $session->id ?? null,
                    'stripe_status' => $session->status ?? 'trialing',
                    'current_period_end' => !empty($session->current_period_end) ? date('Y-m-d H:i:s', (int) $session->current_period_end) : null,
                    'occurred_at' => $event_occurred_at,
                    'payload_snapshot' => $payload,
                ]);

                $this->persist_stripe_customer_id($user_id ?? null, $stripe_customer_id, $session->id ?? null, null);

                break;

            /* Handling recurring payments */
            case 'invoice.paid':

                $payer_email = $session->customer_email;
                $payer_name = $session->customer_name;

                $payment_currency = mb_strtoupper($session->currency);
                $payment_total = in_array($payment_currency, get_zero_decimal_currencies_array()) ? $session->amount_paid : $session->amount_paid / 100;

                /* Process meta data */
                $metadata = $session->lines->data[0]->metadata;

                $user_id = (int) $metadata->user_id;
                $plan_id = (int) $metadata->plan_id;
                $payment_frequency = $metadata->payment_frequency;
                $code = isset($metadata->code) ? $metadata->code : '';
                $discount_amount = isset($metadata->discount_amount) ? $metadata->discount_amount : 0;
                $base_amount = isset($metadata->base_amount) ? $metadata->base_amount : 0;
                $taxes_ids = isset($metadata->taxes_ids) ? $metadata->taxes_ids : null;

                /* Vars */
                $payment_subscription_id =
                    $this->extract_subscription_id($session);

                $payment_type = $payment_subscription_id ? 'recurring' : 'one_time';

                break;

            case 'invoice.payment_failed':

                $metadata = $this->extract_metadata($session);

                $user_id = (int) ($metadata->user_id ?? 0);
                $plan_id = (int) ($metadata->plan_id ?? 0);
                $payment_frequency = $metadata->payment_frequency ?? 'monthly';
                $payment_subscription_id = $this->extract_subscription_id($session);
                $payment_intent_id = is_object($session->payment_intent ?? null) ? $session->payment_intent->id : ($session->payment_intent ?? null);
                $payment_failure = $this->get_payment_intent_details($payment_intent_id);

                $billing->handle_payment_failed([
                    'processor' => 'stripe',
                    'user_id' => $user_id,
                    'plan_id' => $plan_id,
                    'email' => $session->customer_email ?? null,
                    'amount' => in_array(mb_strtoupper($session->currency ?? ''), get_zero_decimal_currencies_array()) ? ($session->amount_due ?? 0) : (($session->amount_due ?? 0) / 100),
                    'currency' => mb_strtoupper($session->currency ?? settings()->payment->default_currency),
                    'reason_code' => $payment_failure['reason_code'] ?? null,
                    'reason_text' => $payment_failure['reason_text'] ?? ($session->last_finalization_error->message ?? null),
                    'stripe_event_id' => $event->id ?? null,
                    'stripe_subscription_id' => $payment_subscription_id,
                    'stripe_invoice_id' => $session->id ?? null,
                    'stripe_payment_intent_id' => $payment_intent_id,
                    'stripe_status' => 'past_due',
                    'current_period_end' => !empty($session->lines->data[0]->period->end) ? date('Y-m-d H:i:s', (int) $session->lines->data[0]->period->end) : null,
                    'next_retry_at' => !empty($session->next_payment_attempt) ? date('Y-m-d H:i:s', (int) $session->next_payment_attempt) : null,
                    'occurred_at' => $event_occurred_at,
                    'payload_snapshot' => $payload,
                ]);

                $this->persist_stripe_customer_id($user_id, $stripe_customer_id, $payment_subscription_id, $session->customer_email ?? null);

                echo 'successful';
                return;

            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':

                $metadata = $this->extract_metadata($session);

                $billing->sync_subscription_status([
                    'processor' => 'stripe',
                    'user_id' => (int) ($metadata->user_id ?? 0),
                    'plan_id' => (int) ($metadata->plan_id ?? 0),
                    'email' => null,
                    'stripe_event_id' => $event->id ?? null,
                    'stripe_subscription_id' => $session->id ?? null,
                    'stripe_invoice_id' => is_string($session->latest_invoice ?? null) ? $session->latest_invoice : ($session->latest_invoice->id ?? null),
                    'stripe_status' => $session->status ?? null,
                    'current_period_end' => !empty($session->current_period_end) ? date('Y-m-d H:i:s', (int) $session->current_period_end) : null,
                    'next_retry_at' => !empty($session->next_pending_invoice_item_invoice) ? date('Y-m-d H:i:s', (int) $session->next_pending_invoice_item_invoice) : null,
                    'reason_code' => 'stripe_subscription_' . ($session->status ?? 'updated'),
                    'reason_text' => 'Stripe subscription status changed to ' . ($session->status ?? 'unknown'),
                    'occurred_at' => $event_occurred_at,
                    'payload_snapshot' => $payload,
                ]);

                $this->persist_stripe_customer_id((int) ($metadata->user_id ?? 0), $stripe_customer_id, $session->id ?? null, null);

                echo 'successful';
                return;

            /* Handling one time payments */
            case 'checkout.session.completed':

                /* Exit when the webhook comes for recurring payments as the invoice.paid event will handle it */
                if($session->subscription) {
                    die();
                }

                $payer_email = $session->customer_details->email;
                $payer_name = $session->customer_details->name;

                $payment_currency = mb_strtoupper($session->currency);
                $payment_total = in_array($payment_currency, get_zero_decimal_currencies_array()) ? $session->amount_total : $session->amount_total / 100;

                /* Process meta data */
                $metadata = $session->metadata;

                $user_id = (int) $metadata->user_id;
                $plan_id = (int) $metadata->plan_id;
                $payment_frequency = $metadata->payment_frequency;
                $code = isset($metadata->code) ? $metadata->code : '';
                $discount_amount = isset($metadata->discount_amount) ? $metadata->discount_amount : 0;
                $base_amount = isset($metadata->base_amount) ? $metadata->base_amount : 0;
                $taxes_ids = isset($metadata->taxes_ids) ? $metadata->taxes_ids : null;

                /* Vars */
                $payment_type = $session->subscription ? 'recurring' : 'one_time';
                $payment_subscription_id =  $payment_type == 'recurring' ? $session->subscription : '';

                break;
        }

        (new Payments())->webhook_process_payment(
            'stripe',
            $external_payment_id,
            $payment_total,
            $payment_currency,
            $user_id,
            $plan_id,
            $payment_frequency,
            $code,
            $discount_amount,
            $base_amount,
            $taxes_ids,
            $payment_type,
            $payment_subscription_id,
            $payer_email,
            $payer_name,
            $stripe_current_period_end
        );

        /* Custom code: FC-2026-03-17: persist canonical Stripe customer after successful payment flows */
        $this->persist_stripe_customer_id($user_id ?? null, $stripe_customer_id, $payment_subscription_id ?? null, $payer_email ?? null);
        /* /Custom code: FC-2026-03-17 */

        /* Custom code: FC-2026-03-17: clear billing risk after successful recurring Stripe charge */
        if(!empty($payment_subscription_id)) {
            $payment_intent_id = is_object($session->payment_intent ?? null) ? $session->payment_intent->id : ($session->payment_intent ?? null);

            $billing->handle_successful_payment([
                /* Custom code: FC-2026-08-01: mark invoice.paid as authoritative recovery evidence */
                'payment_confirmed' => true,
                /* /Custom code: FC-2026-08-01 */
                'processor' => 'stripe',
                'user_id' => $user_id,
                'plan_id' => $plan_id,
                'email' => $payer_email,
                'amount' => $payment_total,
                'currency' => $payment_currency,
                'stripe_event_id' => $event->id ?? null,
                'stripe_subscription_id' => $payment_subscription_id,
                'stripe_invoice_id' => $external_payment_id,
                'stripe_payment_intent_id' => $payment_intent_id,
                'stripe_status' => 'active',
                'current_period_end' => $stripe_current_period_end,
                'occurred_at' => $event_occurred_at,
            ]);
        }
        /* /Custom code: FC-2026-03-17 */

        echo 'successful';

    }

}
