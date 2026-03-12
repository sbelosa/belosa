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

use Altum\Models\Payments;

defined('ALTUMCODE') || die();

class WebhookPaypal extends Controller {

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

        $data = json_decode($payload);

        if($payload && $data) {

            $configured_paypal_api_url = \Altum\PaymentGateways\Paypal::get_api_url();
            $fallback_paypal_api_url = $configured_paypal_api_url === \Altum\PaymentGateways\Paypal::$live_api_url
                ? \Altum\PaymentGateways\Paypal::$sandbox_api_url
                : \Altum\PaymentGateways\Paypal::$live_api_url;

            $paypal_api_urls = [$configured_paypal_api_url, $fallback_paypal_api_url];
            $paypal_headers_cache = [];

            $get_paypal_headers_for_api_url = function(string $api_url) use (&$paypal_headers_cache) {
                if(isset($paypal_headers_cache[$api_url])) {
                    return $paypal_headers_cache[$api_url];
                }

                \Unirest\Request::auth(settings()->paypal->client_id, settings()->paypal->secret);
                $token_response = \Unirest\Request::post($api_url . 'v1/oauth2/token', [], \Unirest\Request\Body::form(['grant_type' => 'client_credentials']));

                if($token_response->code >= 400) {
                    $error = $token_response->body->error ?? 'paypal_oauth_error';
                    $error_description = $token_response->body->error_description ?? 'Unknown PayPal OAuth error.';
                    throw new \Exception($error . ':' . $error_description);
                }

                return $paypal_headers_cache[$api_url] = [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token_response->body->access_token
                ];
            };

            /* Approve one time payment order and process it */
            if($data->event_type == 'CHECKOUT.ORDER.APPROVED') {
                $response = null;
                $capture_errors = [];

                foreach($paypal_api_urls as $paypal_api_url) {
                    try {
                        $headers = $get_paypal_headers_for_api_url($paypal_api_url);
                    } catch(\Exception $exception) {
                        $capture_errors[] = $paypal_api_url . ' auth failed: ' . $exception->getMessage();
                        continue;
                    }

                    $attempt_response = \Unirest\Request::post($paypal_api_url . 'v2/checkout/orders/' . $data->resource->id . '/capture', $headers);

                    if($attempt_response->code < 400) {
                        $response = $attempt_response;
                        break;
                    }

                    $capture_errors[] = $paypal_api_url . ' capture failed: ' . ($attempt_response->body->name ?? 'paypal_capture_error') . ':' . ($attempt_response->body->message ?? 'Unknown capture error');
                }

                /* Check against errors */
                if(!$response || $response->code >= 400) {
                    $error_message = $response && isset($response->body->name) ? $response->body->name . ':' . ($response->body->message ?? '') : 'paypal_capture_failed';
                    if(DEBUG) {
                        error_log($error_message);
                    }

                    debug_log('[' . \Altum\Router::$controller . '] PayPal capture failed on all environments: ' . implode(' | ', $capture_errors));
                    echo $error_message;
                    http_response_code(400); die();
                }

                /* Start getting the payment details */
                $payment_subscription_id = null;
                $external_payment_id = $response->body->id;
                $payment_total = $response->body->purchase_units[0]->payments->captures[0]->amount->value;
                $payment_currency = $response->body->purchase_units[0]->payments->captures[0]->amount->currency_code;
                $payment_type = 'one_time';

                /* Payment payer details */
                $payer_email = $response->body->payer->email_address;
                $payer_name = $response->body->payer->name->given_name . $response->body->payer->name->surname;

                /* Parse metadata */
                $metadata = explode('&', $response->body->purchase_units[0]->payments->captures[0]->custom_id);
                $user_id = (int) $metadata[0];
                $plan_id = (int) $metadata[1];
                $payment_frequency = $metadata[2];
                $base_amount = $metadata[3];
                $code = $metadata[4];
                $discount_amount = $metadata[5] ? $metadata[5] : 0;
                $taxes_ids = $metadata[6] ?: null;

                (new Payments())->webhook_process_payment(
                    'paypal',
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
                    $payer_name
                );

                die('successful');
            }

            /* Custom code: FC-2026-03-09: support newer PayPal recurring webhook payloads */
            $paypal_recurring_events = [
                'PAYMENT.SALE.COMPLETED',
                'BILLING.SUBSCRIPTION.PAYMENT.COMPLETED',
                'PAYMENT.CAPTURE.COMPLETED'
            ];

            /* Handle received payments by subscriptions */
            if(isset($data->event_type) && in_array($data->event_type, $paypal_recurring_events)) {

                $payment_subscription_id = $data->resource->billing_agreement_id
                    ?? $data->resource->billing_subscription_id
                    ?? $data->resource->supplementary_data->related_ids->billing_agreement_id
                    ?? $data->resource->supplementary_data->related_ids->billing_subscription_id
                    ?? $data->resource->supplementary_data->related_ids->subscription_id
                    ?? null;

                if(!$payment_subscription_id) {
                    debug_log('[' . \Altum\Router::$controller . '] Missing PayPal subscription id for recurring event: ' . ($data->event_type ?? 'unknown'));
                    http_response_code(400); die();
                }

                $response = null;
                $subscription_lookup_errors = [];

                foreach($paypal_api_urls as $paypal_api_url) {
                    try {
                        $headers = $get_paypal_headers_for_api_url($paypal_api_url);
                    } catch(\Exception $exception) {
                        $subscription_lookup_errors[] = $paypal_api_url . ' auth failed: ' . $exception->getMessage();
                        continue;
                    }

                    $attempt_response = \Unirest\Request::get($paypal_api_url . 'v1/billing/subscriptions/' . $payment_subscription_id . '?fields=plan', $headers);

                    if($attempt_response->code < 400) {
                        $response = $attempt_response;
                        break;
                    }

                    $subscription_lookup_errors[] = $paypal_api_url . ' lookup failed: ' . ($attempt_response->body->name ?? 'paypal_lookup_error') . ':' . ($attempt_response->body->message ?? 'Unknown lookup error');
                }

                /* Check against errors */
                if(!$response || $response->code >= 400) {
                    $error_message = $response && isset($response->body->name) ? $response->body->name . ':' . ($response->body->message ?? '') : 'paypal_subscription_lookup_failed';
                    if(DEBUG) {
                        error_log($error_message);
                    }

                    debug_log('[' . \Altum\Router::$controller . '] PayPal subscription lookup failed on all environments for subscription ' . $payment_subscription_id . ': ' . implode(' | ', $subscription_lookup_errors));
                    echo $error_message;
                    http_response_code(400); die();
                }

                /* Start getting the payment details */
                $external_payment_id = $data->resource->id ?? null;
                $payment_total = $data->resource->amount->total ?? $data->resource->amount->value ?? 0;
                $payment_currency = $data->resource->amount->currency ?? $data->resource->amount->currency_code ?? settings()->payment->default_currency;
                $payment_type = 'recurring';

                /* Payment payer details */
                $payer_email = $response->body->subscriber->email_address ?? ($data->resource->payer->email_address ?? null);
                $payer_name = trim(($response->body->subscriber->name->given_name ?? '') . ' ' . ($response->body->subscriber->name->surname ?? ''));

                if(!$payer_name) {
                    $payer_name = $payer_email ?? 'PayPal Subscriber';
                }

                $taxes_ids = null;

                if(isset($response->body->custom_id)) {
                    /* Parse metadata */
                    $metadata = explode('&', $response->body->custom_id);
                    $user_id = (int) $metadata[0];
                    $plan_id = (int) $metadata[1];
                    $payment_frequency = $metadata[2];
                    $base_amount = $metadata[3];
                    $code = $metadata[4];
                    $discount_amount = $metadata[5] ? $metadata[5] : 0;
                    $taxes_ids = $metadata[6] ?: null;
                } else {

                    /* Check for old subscriptions meta data */
                    $extra = explode('###', $response->body->plan->name);

                    if(isset($extra[0], $extra[1], $extra[2])) {
                        $user_id = (int) $extra[0];
                        $plan_id = (int) $extra[1];
                        $payment_frequency = $extra[2];
                        $code = $extra[3];
                        $discount_amount = 0;
                        $base_amount = 0;
                    } else {
                        $extra = explode('!!', $response->body->plan->name);

                        $user_id = (int) $extra[0];
                        $plan_id = (int) $extra[1];
                        $base_amount = $extra[2];
                        $payment_frequency = $extra[3];
                        $code = $extra[4];
                        $discount_amount = $extra[5] ? $extra[5] : 0;
                        $taxes_ids = $extra[6];
                    }
                }

                (new Payments())->webhook_process_payment(
                    'paypal',
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
                    $payer_name
                );

                die('successful');
            }
            /* /Custom code: FC-2026-03-09 */

            /* Custom code: FC-2026-03-09: map failed terminal PayPal subscription states to Beginner plan */
            $paypal_subscription_state_events = [
                'BILLING.SUBSCRIPTION.SUSPENDED',
                'BILLING.SUBSCRIPTION.CANCELLED',
                'BILLING.SUBSCRIPTION.EXPIRED'
            ];

            if(isset($data->event_type) && in_array($data->event_type, $paypal_subscription_state_events)) {
                $payment_subscription_id = $data->resource->id
                    ?? $data->resource->billing_agreement_id
                    ?? $data->resource->billing_subscription_id
                    ?? null;

                if(!$payment_subscription_id) {
                    http_response_code(400); die();
                }

                $user = db()->where('payment_processor', 'paypal')->where('payment_subscription_id', $payment_subscription_id)->getOne('users', ['user_id']);

                if(!$user) {
                    die('successful');
                }

                if($data->event_type === 'BILLING.SUBSCRIPTION.CANCELLED') {
                    db()->where('user_id', $user->user_id)->update('users', [
                        'payment_subscription_id' => '',
                        'payment_processor' => '',
                    ]);

                    cache()->deleteItemsByTag('user_id=' . $user->user_id);
                    die('successful');
                }

                $beginner_plan = db()->where('plan_id', 2)->getOne('plans', ['plan_id', 'settings']);
                if(!$beginner_plan) {
                    http_response_code(400); die();
                }

                db()->where('user_id', $user->user_id)->update('users', [
                    'plan_id' => 2,
                    'plan_settings' => $beginner_plan->settings,
                    'plan_expiration_date' => date('Y-m-d H:i:s', strtotime('+10 years')),
                    'payment_subscription_id' => '',
                    'payment_processor' => '',
                    'payment_total_amount' => 0,
                    'payment_currency' => '',
                ]);

                (new \Altum\Models\User())->sync_links_with_plan($user->user_id);
                cache()->deleteItemsByTag('user_id=' . $user->user_id);

                die('successful');
            }
            /* /Custom code: FC-2026-03-09 */

        }

        die('');

    }

}
