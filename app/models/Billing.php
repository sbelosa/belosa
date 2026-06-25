<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * View all other existing AltumCode projects via https://altumcode.com/
 * Get in touch for support or general queries via https://altumcode.com/contact
 * Download the latest version via https://altumcode.com/downloads
 */

namespace Altum\Models;

defined('ALTUMCODE') || die();

class Billing extends Model {

    /* Custom code: FC-2026-03-17: centralized billing risk state, notifications and audit log */
    public const DATA_TYPE = 'billing_event';

    public const STATE_HEALTHY = 'healthy';
    public const STATE_PAST_DUE = 'past_due';
    public const STATE_PAST_DUE_CRITICAL = 'past_due_critical';
    public const STATE_RECOVERED = 'recovered';
    public const STATE_ACCESS_REVOKED = 'access_revoked';

    public const NOTIFICATION_WARNING_FIRST = 'warning_first';
    public const NOTIFICATION_WARNING_SECOND = 'warning_second';
    public const NOTIFICATION_PAUSED = 'paused';
    public const NOTIFICATION_RECOVERED = 'recovered';
    public const NOTIFICATION_REVOKED = 'revoked';

    private const GRACE_PERIOD_DAYS = 7;
    private const ESCALATION_HOURS = 72;
    private const EVENT_PAYLOAD_LIMIT = 15000;

    public function decode_extra($extra): object {
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

    private function encode_json($value): string {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function truncate_text(?string $value, int $limit = self::EVENT_PAYLOAD_LIMIT): ?string {
        if($value === null) {
            return null;
        }

        $value = trim($value);

        if($value === '') {
            return null;
        }

        return mb_substr($value, 0, $limit);
    }

    private function normalize_datetime(?string $datetime): ?string {
        if(!$datetime) {
            return null;
        }

        try {
            return (new \DateTime($datetime))->format('Y-m-d H:i:s');
        } catch(\Exception $exception) {
            return null;
        }
    }

    private function datetime_from_timestamp($timestamp): ?string {
        if(!$timestamp) {
            return null;
        }

        return date('Y-m-d H:i:s', (int) $timestamp);
    }

    private function is_active_access_status(?string $status): bool {
        return in_array((string) $status, ['active', 'trialing'], true);
    }

    private function get_retry_window_until(?string $occurred_at = null, ?string $next_retry_at = null): string {
        $retry_window_until = (new \DateTime($occurred_at ?? get_date()))->modify('+' . self::GRACE_PERIOD_DAYS . ' days');

        if($next_retry_at) {
            try {
                $stripe_retry_until = (new \DateTime($next_retry_at))->modify('+24 hours');

                if($stripe_retry_until > $retry_window_until) {
                    $retry_window_until = $stripe_retry_until;
                }
            } catch(\Exception $exception) {
            }
        }

        return $retry_window_until->format('Y-m-d H:i:s');
    }

    private function get_user_billing_link(object $user, array $context): string {
        $extra = $this->decode_extra($user->extra ?? null);
        $notification_stage = (string) ($context['notification_stage'] ?? '');
        $processor = trim((string) ($context['processor'] ?? ($user->payment_processor ?? '')));
        $stripe_subscription_id = trim((string) ($context['stripe_subscription_id'] ?? ($user->payment_subscription_id ?? '')));
        $has_stripe_context = $processor === 'stripe'
            || str_starts_with($stripe_subscription_id, 'sub_')
            || !empty($extra->stripe_customer_id ?? null);

        if($notification_stage === self::NOTIFICATION_REVOKED) {
            return url('account-plan');
        }

        return $has_stripe_context ? url('account-plan/stripe_portal') : url('account-plan');
    }

    private function safely_cancel_failed_subscription(object $user, string $occurred_at, ?string $reason_text = null): bool {
        if(trim((string) ($user->payment_processor ?? '')) !== 'stripe' || empty($user->payment_subscription_id)) {
            return true;
        }

        try {
            (new User())->cancel_subscription($user->user_id);
        } catch(\Throwable $exception) {
            $this->log_event((int) $user->user_id, [
                'event_type' => 'subscription_cancellation_failed',
                'processor' => 'stripe',
                'billing_state_before' => self::STATE_ACCESS_REVOKED,
                'billing_state_after' => self::STATE_ACCESS_REVOKED,
                'reason_code' => 'stripe_subscription_cancel_failed',
                'reason_text' => trim((string) $exception->getMessage()) ?: ($reason_text ?? l('global.unknown')),
                'stripe_subscription_id' => (string) ($user->payment_subscription_id ?? ''),
                'occurred_at' => $occurred_at,
            ]);

            return false;
        }

        $current_user = db()->where('user_id', $user->user_id)->getOne('users', ['extra']);
        $extra = $this->decode_extra($current_user->extra ?? null);
        $extra->billing_stripe_status = 'canceled';
        $extra->billing_next_retry_at = null;
        $extra->billing_grace_until = null;
        $extra->billing_subscription_cancelled_at = $occurred_at;
        $this->update_user_extra((int) $user->user_id, $extra);

        $this->log_event((int) $user->user_id, [
            'event_type' => 'subscription_canceled_after_failed_retries',
            'processor' => 'stripe',
            'billing_state_before' => self::STATE_ACCESS_REVOKED,
            'billing_state_after' => self::STATE_ACCESS_REVOKED,
            'reason_code' => 'stripe_subscription_retry_window_ended',
            'reason_text' => $reason_text ?? l('global.unknown'),
            'stripe_status' => 'canceled',
            'occurred_at' => $occurred_at,
        ]);

        return true;
    }

    /* Custom code: FC-2026-03-22: avoid false billing-failure emails for transient Stripe authentication flows */
    private function should_defer_first_failure_notification(array $context, int $failed_attempts): bool {
        if($failed_attempts >= 2) {
            return false;
        }

        $reason_code = mb_strtolower(trim((string) ($context['reason_code'] ?? '')));
        $reason_text = mb_strtolower(trim((string) ($context['reason_text'] ?? '')));
        $recoverable_fragments = ['authentication', 'requires_action', 'requires action', 'verification', 'verify', '3d secure', '3d_secure', 'sca'];

        foreach($recoverable_fragments as $fragment) {
            if(($reason_code !== '' && mb_strpos($reason_code, $fragment) !== false) || ($reason_text !== '' && mb_strpos($reason_text, $fragment) !== false)) {
                return true;
            }
        }

        if(!empty($context['next_retry_at']) && !empty($context['occurred_at'])) {
            try {
                $occurred_at = new \DateTime((string) $context['occurred_at']);
                $next_retry_at = new \DateTime((string) $context['next_retry_at']);

                return ($next_retry_at->getTimestamp() - $occurred_at->getTimestamp()) <= 1800;
            } catch(\Exception $exception) {
            }
        }

        return false;
    }
    /* /Custom code: FC-2026-03-22 */

    private function get_user(?int $user_id = null, ?string $subscription_id = null, ?string $email = null): ?object {
        if($user_id) {
            $user = db()->where('user_id', $user_id)->getOne('users');

            if($user) {
                return $user;
            }
        }

        if($subscription_id) {
            $user = db()->where('payment_subscription_id', $subscription_id)->getOne('users');

            if($user) {
                return $user;
            }
        }

        if($email) {
            return db()->where('email', $email)->getOne('users');
        }

        return null;
    }

    public function has_processed_stripe_event(?string $stripe_event_id): bool {
        if(!$stripe_event_id) {
            return false;
        }

        $stripe_event_id = database()->real_escape_string($stripe_event_id);

        $result = database()->query("SELECT COUNT(*) AS total FROM `data` WHERE `type` = '" . self::DATA_TYPE . "' AND JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.stripe_event_id')) = '{$stripe_event_id}'");

        return (int) ($result->fetch_object()->total ?? 0) > 0;
    }

    private function update_user_extra(int $user_id, object $extra): void {
        db()->where('user_id', $user_id)->update('users', [
            'extra' => $this->encode_json($extra),
        ]);

        cache()->deleteItemsByTag('user_id=' . $user_id);
    }

    private function log_event(int $user_id, array $event): void {
        $event['event_type'] = $event['event_type'] ?? 'unknown';
        $event['occurred_at'] = $event['occurred_at'] ?? get_date();

        if(isset($event['payload_snapshot']) && !is_string($event['payload_snapshot'])) {
            $event['payload_snapshot'] = $this->encode_json($event['payload_snapshot']);
        }

        $event['payload_snapshot'] = $this->truncate_text($event['payload_snapshot'] ?? null);

        db()->insert('data', [
            'user_id' => $user_id,
            'type' => self::DATA_TYPE,
            'data' => $this->encode_json($event),
            'datetime' => $event['occurred_at'],
        ]);
    }

    private function create_user_internal_notification(object $user, string $title, string $description, string $url): void {
        if(!settings()->internal_notifications->users_is_enabled) {
            return;
        }

        db()->insert('internal_notifications', [
            'user_id' => $user->user_id,
            'for_who' => 'user',
            'from_who' => 'system',
            'icon' => 'fas fa-credit-card',
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'datetime' => get_date(),
        ]);

        db()->where('user_id', $user->user_id)->update('users', [
            'has_pending_internal_notifications' => 1,
        ]);
    }

    private function create_admin_internal_notification(object $user, string $title, string $description, string $url): void {
        if(!settings()->internal_notifications->admins_is_enabled) {
            return;
        }

        db()->insert('internal_notifications', [
            'for_who' => 'admin',
            'from_who' => 'system',
            'icon' => 'fas fa-triangle-exclamation',
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'datetime' => get_date(),
        ]);
    }

    private function build_notification_context(object $user, array $context, ?string $language = null): array {
        $plan = !empty($context['plan_id']) ? db()->where('plan_id', $context['plan_id'])->getOne('plans', ['plan_id', 'name']) : null;
        $fallback_plan = db()->where('plan_id', 2)->getOne('plans', ['plan_id', 'name']);

        return [
            'NAME' => $user->name,
            /* Custom code: FC-2026-03-22: localize billing placeholders */
            'PLAN_NAME' => $plan->name ?? l('global.none', $language),
            'FAILURE_REASON' => $context['reason_text'] ?? l('global.unknown', $language),
            'FAILURE_CODE' => $context['reason_code'] ?? l('global.none', $language),
            'GRACE_UNTIL' => !empty($context['grace_until']) ? \Altum\Date::get($context['grace_until'], 2) : l('global.none', $language),
            'NEXT_PAYMENT_ATTEMPT' => !empty($context['next_retry_at']) ? \Altum\Date::get($context['next_retry_at'], 2) : l('global.none', $language),
            'USER_PLAN_LINK' => $this->get_user_billing_link($user, $context),
            'USER_PAYMENTS_LINK' => url('account-payments'),
            'RECOVERED_AT' => !empty($context['recovered_at']) ? \Altum\Date::get($context['recovered_at'], 2) : l('global.none', $language),
            'REVOKED_AT' => !empty($context['revoked_at']) ? \Altum\Date::get($context['revoked_at'], 2) : l('global.none', $language),
            'FALLBACK_PLAN_NAME' => $fallback_plan->name ?? l('global.none', $language),
            'STRIPE_SUBSCRIPTION_ID' => $context['stripe_subscription_id'] ?? l('global.none', $language),
            'STRIPE_INVOICE_ID' => $context['stripe_invoice_id'] ?? l('global.none', $language),
            /* /Custom code: FC-2026-03-22 */
        ];
    }

    private function send_notification(object $user, string $stage, array $context): void {
        $stage_map = [
            self::NOTIFICATION_WARNING_FIRST => [
                'email_subject' => 'global.emails.billing_warning_first.subject',
                'email_body' => 'global.emails.billing_warning_first.body',
                'internal_title' => 'global.notifications.billing_warning_first.title',
                'internal_description' => 'global.notifications.billing_warning_first.description',
            ],
            self::NOTIFICATION_WARNING_SECOND => [
                'email_subject' => 'global.emails.billing_warning_second.subject',
                'email_body' => 'global.emails.billing_warning_second.body',
                'internal_title' => 'global.notifications.billing_warning_second.title',
                'internal_description' => 'global.notifications.billing_warning_second.description',
            ],
            self::NOTIFICATION_PAUSED => [
                'email_subject' => 'global.emails.billing_paused.subject',
                'email_body' => 'global.emails.billing_paused.body',
                'internal_title' => 'global.notifications.billing_paused.title',
                'internal_description' => 'global.notifications.billing_paused.description',
            ],
            self::NOTIFICATION_RECOVERED => [
                'email_subject' => 'global.emails.billing_recovered.subject',
                'email_body' => 'global.emails.billing_recovered.body',
                'internal_title' => 'global.notifications.billing_recovered.title',
                'internal_description' => 'global.notifications.billing_recovered.description',
            ],
            self::NOTIFICATION_REVOKED => [
                'email_subject' => 'global.emails.billing_revoked.subject',
                'email_body' => 'global.emails.billing_revoked.body',
                'internal_title' => 'global.notifications.billing_revoked.title',
                'internal_description' => 'global.notifications.billing_revoked.description',
            ],
        ];

        if(!isset($stage_map[$stage])) {
            return;
        }

        /* Custom code: FC-2026-03-22: normalize legacy language aliases */
        $language = fc_resolve_language_name($user->language ?? \Altum\Language::$default_name ?? 'english');
        $placeholders = $this->build_notification_context($user, $context + ['notification_stage' => $stage], $language);
        /* /Custom code: FC-2026-03-22 */
        $email_template = get_email_template(
            [],
            l($stage_map[$stage]['email_subject'], $language),
            array_combine(
                array_map(fn($key) => '{{' . $key . '}}', array_keys($placeholders)),
                array_values($placeholders)
            ),
            l($stage_map[$stage]['email_body'], $language)
        );

        $mail_result = send_mail($user->email, $email_template->subject, $email_template->body, [
            'anti_phishing_code' => $user->anti_phishing_code,
            'language' => $language,
            'return_transport_result' => true,
        ]);
        $mail_success = is_object($mail_result) ? (bool) ($mail_result->success ?? false) : (bool) $mail_result;
        $mail_error = '';

        if(is_object($mail_result)) {
            $mail_error = trim((string) (
                $mail_result->ErrorInfo
                ?? $mail_result->curl_error
                ?? $mail_result->response_body
                ?? ''
            ));
        }

        $internal_title = str_replace(
            array_map(fn($key) => '{{' . $key . '}}', array_keys($placeholders)),
            array_values($placeholders),
            l($stage_map[$stage]['internal_title'], $language)
        );

        $internal_description = str_replace(
            array_map(fn($key) => '{{' . $key . '}}', array_keys($placeholders)),
            array_values($placeholders),
            l($stage_map[$stage]['internal_description'], $language)
        );

        $this->create_user_internal_notification($user, $internal_title, $internal_description, 'account-plan');

        $current_user = db()->where('user_id', $user->user_id)->getOne('users', ['extra']);
        $extra = $this->decode_extra($current_user->extra ?? ($user->extra ?? null));
        $extra->billing_last_notification_stage = $stage;
        $extra->billing_last_notification_at = get_date();
        $extra->billing_last_notification_delivery_success = $mail_success ? 1 : 0;
        $extra->billing_last_notification_delivery_error = $mail_success ? null : ($mail_error ?: l('global.unknown'));
        $this->update_user_extra($user->user_id, $extra);

        $this->log_event($user->user_id, [
            'event_type' => $mail_success ? 'notification_sent' : 'notification_failed',
            'processor' => $context['processor'] ?? 'stripe',
            'notification_stage' => $stage,
            'notification_subject' => $email_template->subject,
            'mail_success' => $mail_success ? 1 : 0,
            'mail_status_code' => is_object($mail_result) ? ($mail_result->status_code ?? null) : null,
            'mail_message_id' => is_object($mail_result) ? ($mail_result->message_id ?? null) : null,
            'mail_error' => $mail_success ? null : ($mail_error ?: l('global.unknown')),
            'reason_code' => $context['reason_code'] ?? null,
            'reason_text' => $context['reason_text'] ?? null,
            'stripe_event_id' => $context['stripe_event_id'] ?? null,
            'stripe_subscription_id' => $context['stripe_subscription_id'] ?? null,
            'stripe_invoice_id' => $context['stripe_invoice_id'] ?? null,
            'stripe_payment_intent_id' => $context['stripe_payment_intent_id'] ?? null,
            'occurred_at' => get_date(),
        ]);

        if(!$mail_success) {
            $this->create_admin_internal_notification(
                $user,
                'Billing email delivery failed',
                sprintf('Could not send %s billing email to %s (%s): %s', $stage, $user->name, $user->email, $mail_error ?: l('global.unknown')),
                'admin/user-view/' . $user->user_id
            );
        }
    }

    private function get_fallback_plan(): ?object {
        return db()->where('plan_id', 2)->getOne('plans');
    }

    private function revoke_user_access(object $user, array $context): void {
        $current_user = db()->where('user_id', $user->user_id)->getOne('users', ['extra', 'plan_id', 'payment_processor', 'payment_subscription_id', 'name', 'email', 'language', 'anti_phishing_code']);
        if($current_user) {
            foreach($current_user as $key => $value) {
                $user->{$key} = $value;
            }
        }

        $fallback_plan = $this->get_fallback_plan();
        $fallback_plan_id = $fallback_plan ? 2 : 'free';
        $fallback_plan_settings = $fallback_plan
            ? json_encode($fallback_plan->settings)
            : json_encode(settings()->plan_free->settings);
        $fallback_plan_expiration_date = $fallback_plan
            ? (new \DateTime())->modify('+10 years')->format('Y-m-d H:i:s')
            : get_date();

        db()->where('user_id', $user->user_id)->update('users', [
            'plan_id' => $fallback_plan_id,
            'plan_settings' => $fallback_plan_settings,
            'plan_expiration_date' => $fallback_plan_expiration_date,
            'plan_expiry_reminder' => 0,
        ]);

        (new User())->sync_links_with_plan($user->user_id);

        $extra = $this->decode_extra($user->extra ?? null);
        $previous_state = $extra->billing_state ?? self::STATE_HEALTHY;
        $notification_stage = $context['notification_stage'] ?? self::NOTIFICATION_REVOKED;
        $extra->billing_state = self::STATE_ACCESS_REVOKED;
        $extra->billing_access_revoked_at = $context['revoked_at'] ?? get_date();
        $extra->billing_grace_until = $context['grace_until'] ?? ($extra->billing_grace_until ?? null);
        $extra->billing_stripe_status = $context['stripe_status'] ?? ($extra->billing_stripe_status ?? null);
        $this->update_user_extra($user->user_id, $extra);

        $this->log_event($user->user_id, [
            'event_type' => 'access_revoked',
            'processor' => $context['processor'] ?? 'stripe',
            'billing_state_before' => $previous_state,
            'billing_state_after' => self::STATE_ACCESS_REVOKED,
            'reason_code' => $context['reason_code'] ?? null,
            'reason_text' => $context['reason_text'] ?? null,
            'stripe_status' => $context['stripe_status'] ?? null,
            'stripe_event_id' => $context['stripe_event_id'] ?? null,
            'stripe_subscription_id' => $context['stripe_subscription_id'] ?? null,
            'stripe_invoice_id' => $context['stripe_invoice_id'] ?? null,
            'stripe_payment_intent_id' => $context['stripe_payment_intent_id'] ?? null,
            'occurred_at' => $context['revoked_at'] ?? get_date(),
        ]);

        if(($extra->billing_last_notification_stage ?? null) !== $notification_stage) {
            $this->send_notification($user, $notification_stage, $context + ['revoked_at' => $extra->billing_access_revoked_at]);
        }

        if($notification_stage === self::NOTIFICATION_REVOKED) {
            $this->create_admin_internal_notification(
                $user,
                l('global.notifications.billing_admin_revoked.title'),
                sprintf(l('global.notifications.billing_admin_revoked.description'), $user->name, $user->email, $context['reason_text'] ?? l('global.unknown')),
                'admin/user-view/' . $user->user_id
            );
        }
    }

    public function handle_payment_failed(array $context): void {
        $user = $this->get_user((int) ($context['user_id'] ?? 0), $context['stripe_subscription_id'] ?? null, $context['email'] ?? null);

        if(!$user) {
            return;
        }

        $extra = $this->decode_extra($user->extra ?? null);
        $previous_state = $extra->billing_state ?? self::STATE_HEALTHY;
        $current_invoice_id = $context['stripe_invoice_id'] ?? null;
        $is_new_invoice = !$current_invoice_id || ($extra->billing_last_invoice_id ?? null) !== $current_invoice_id;

        if($is_new_invoice || empty($extra->billing_grace_started_at ?? null) || in_array($previous_state, [self::STATE_HEALTHY, self::STATE_RECOVERED], true)) {
            $extra->billing_grace_started_at = $context['occurred_at'] ?? get_date();
            $extra->billing_grace_until = $this->get_retry_window_until($extra->billing_grace_started_at, $context['next_retry_at'] ?? null);
            $extra->billing_failed_attempts = 1;
        } else {
            $extra->billing_failed_attempts = (int) ($extra->billing_failed_attempts ?? 0) + 1;
            $extra->billing_grace_until = $this->get_retry_window_until($extra->billing_grace_started_at, $context['next_retry_at'] ?? null);
        }

        $new_state = (int) ($extra->billing_failed_attempts ?? 0) >= 2 ? self::STATE_PAST_DUE_CRITICAL : self::STATE_PAST_DUE;

        $extra->billing_state = $new_state;
        $extra->billing_last_failed_at = $context['occurred_at'] ?? get_date();
        $extra->billing_last_failed_reason_code = $context['reason_code'] ?? null;
        $extra->billing_last_failed_reason_text = $context['reason_text'] ?? null;
        $extra->billing_last_invoice_id = $current_invoice_id;
        $extra->billing_last_payment_intent_id = $context['stripe_payment_intent_id'] ?? null;
        $extra->billing_next_retry_at = $context['next_retry_at'] ?? null;
        $extra->billing_stripe_status = $context['stripe_status'] ?? 'past_due';
        $extra->billing_current_period_end = $context['current_period_end'] ?? null;

        $this->update_user_extra($user->user_id, $extra);

        $this->log_event($user->user_id, [
            'event_type' => 'payment_failed',
            'processor' => $context['processor'] ?? 'stripe',
            'billing_state_before' => $previous_state,
            'billing_state_after' => $new_state,
            'failed_attempts' => (int) ($extra->billing_failed_attempts ?? 0),
            'reason_code' => $context['reason_code'] ?? null,
            'reason_text' => $context['reason_text'] ?? null,
            'amount' => $context['amount'] ?? null,
            'currency' => $context['currency'] ?? null,
            'stripe_status' => $context['stripe_status'] ?? 'past_due',
            'stripe_event_id' => $context['stripe_event_id'] ?? null,
            'stripe_subscription_id' => $context['stripe_subscription_id'] ?? null,
            'stripe_invoice_id' => $context['stripe_invoice_id'] ?? null,
            'stripe_payment_intent_id' => $context['stripe_payment_intent_id'] ?? null,
            'grace_started_at' => $extra->billing_grace_started_at,
            'grace_until' => $extra->billing_grace_until,
            'next_retry_at' => $context['next_retry_at'] ?? null,
            'occurred_at' => $context['occurred_at'] ?? get_date(),
            'payload_snapshot' => $context['payload_snapshot'] ?? null,
        ]);

        $notification_stage = $new_state === self::STATE_PAST_DUE_CRITICAL
            ? self::NOTIFICATION_WARNING_SECOND
            : self::NOTIFICATION_WARNING_FIRST;

        if(!$this->should_defer_first_failure_notification($context, (int) ($extra->billing_failed_attempts ?? 0))
            && ($extra->billing_last_notification_stage ?? null) !== $notification_stage
        ) {
            $this->send_notification($user, $notification_stage, $context + [
                'grace_until' => $extra->billing_grace_until,
                'reason_code' => $context['reason_code'] ?? 'stripe_payment_failed',
                'reason_text' => $context['reason_text'] ?? l('global.unknown'),
            ]);
        }

        if(($extra->billing_failed_attempts ?? 0) >= 2) {
            $this->create_admin_internal_notification(
                $user,
                l('global.notifications.billing_admin_critical.title'),
                sprintf(l('global.notifications.billing_admin_critical.description'), $user->name, $user->email, $context['reason_text'] ?? l('global.unknown')),
                'admin/user-view/' . $user->user_id
            );
        }
    }

    public function handle_successful_payment(array $context): void {
        $user = $this->get_user((int) ($context['user_id'] ?? 0), $context['stripe_subscription_id'] ?? null, $context['email'] ?? null);

        if(!$user) {
            return;
        }

        $extra = $this->decode_extra($user->extra ?? null);
        $previous_state = $extra->billing_state ?? self::STATE_HEALTHY;
        $was_unhealthy = in_array($previous_state, [self::STATE_PAST_DUE, self::STATE_PAST_DUE_CRITICAL, self::STATE_ACCESS_REVOKED], true);

        $extra->billing_state = self::STATE_HEALTHY;
        $extra->billing_failed_attempts = 0;
        $extra->billing_grace_started_at = null;
        $extra->billing_grace_until = null;
        $extra->billing_next_retry_at = null;
        $extra->billing_stripe_status = $context['stripe_status'] ?? 'active';
        $extra->billing_recovered_at = $context['occurred_at'] ?? get_date();
        $extra->billing_last_success_at = $context['occurred_at'] ?? get_date();
        $extra->billing_last_invoice_id = $context['stripe_invoice_id'] ?? ($extra->billing_last_invoice_id ?? null);
        $extra->billing_last_payment_intent_id = $context['stripe_payment_intent_id'] ?? ($extra->billing_last_payment_intent_id ?? null);

        $this->update_user_extra($user->user_id, $extra);

        $this->log_event($user->user_id, [
            'event_type' => $was_unhealthy ? 'payment_recovered' : 'payment_confirmed',
            'processor' => $context['processor'] ?? 'stripe',
            'billing_state_before' => $previous_state,
            'billing_state_after' => self::STATE_HEALTHY,
            'stripe_status' => $context['stripe_status'] ?? 'active',
            'stripe_event_id' => $context['stripe_event_id'] ?? null,
            'stripe_subscription_id' => $context['stripe_subscription_id'] ?? null,
            'stripe_invoice_id' => $context['stripe_invoice_id'] ?? null,
            'stripe_payment_intent_id' => $context['stripe_payment_intent_id'] ?? null,
            'amount' => $context['amount'] ?? null,
            'currency' => $context['currency'] ?? null,
            'occurred_at' => $context['occurred_at'] ?? get_date(),
        ]);

        if($was_unhealthy && ($extra->billing_last_notification_stage ?? null) !== self::NOTIFICATION_RECOVERED) {
            $this->send_notification($user, self::NOTIFICATION_RECOVERED, $context + ['recovered_at' => $extra->billing_recovered_at]);
        }
    }

    public function sync_subscription_status(array $context): void {
        $user = $this->get_user((int) ($context['user_id'] ?? 0), $context['stripe_subscription_id'] ?? null, $context['email'] ?? null);

        if(!$user) {
            return;
        }

        $extra = $this->decode_extra($user->extra ?? null);
        $previous_state = $extra->billing_state ?? self::STATE_HEALTHY;
        $extra->billing_stripe_status = $context['stripe_status'] ?? ($extra->billing_stripe_status ?? null);
        $extra->billing_current_period_end = $context['current_period_end'] ?? ($extra->billing_current_period_end ?? null);
        $extra->billing_next_retry_at = $context['next_retry_at'] ?? ($extra->billing_next_retry_at ?? null);
        $extra->billing_last_invoice_id = $context['stripe_invoice_id'] ?? ($extra->billing_last_invoice_id ?? null);
        $extra->billing_last_payment_intent_id = $context['stripe_payment_intent_id'] ?? ($extra->billing_last_payment_intent_id ?? null);

        $stripe_status = $context['stripe_status'] ?? '';
        $started_past_due = $stripe_status === 'past_due'
            && !in_array($previous_state, [self::STATE_PAST_DUE, self::STATE_PAST_DUE_CRITICAL, self::STATE_ACCESS_REVOKED], true);

        if(in_array($stripe_status, ['unpaid', 'canceled', 'incomplete_expired'], true)) {
            $extra->billing_subscription_cancelled_at = $context['occurred_at'] ?? get_date();
        } elseif(in_array($stripe_status, ['active', 'trialing'], true)) {
            $extra->billing_subscription_cancelled_at = null;
            $extra->billing_subscription_cancelled_during_trial = 0;
        }

        if($started_past_due) {
            $extra->billing_state = self::STATE_PAST_DUE;
            $extra->billing_grace_started_at = $context['occurred_at'] ?? get_date();
            $extra->billing_grace_until = $extra->billing_grace_until ?? $this->get_retry_window_until($extra->billing_grace_started_at, $context['next_retry_at'] ?? null);
            $extra->billing_failed_attempts = max(1, (int) ($extra->billing_failed_attempts ?? 0));
            $extra->billing_last_failed_at = $context['occurred_at'] ?? get_date();
            $extra->billing_last_failed_reason_code = $context['reason_code'] ?? ($extra->billing_last_failed_reason_code ?? null);
            $extra->billing_last_failed_reason_text = $context['reason_text'] ?? ($extra->billing_last_failed_reason_text ?? null);
        }

        $this->update_user_extra($user->user_id, $extra);

        $this->log_event($user->user_id, [
            'event_type' => 'subscription_status_changed',
            'processor' => $context['processor'] ?? 'stripe',
            'billing_state_before' => $previous_state,
            'billing_state_after' => $extra->billing_state ?? self::STATE_HEALTHY,
            'stripe_status' => $context['stripe_status'] ?? null,
            'stripe_event_id' => $context['stripe_event_id'] ?? null,
            'stripe_subscription_id' => $context['stripe_subscription_id'] ?? null,
            'stripe_invoice_id' => $context['stripe_invoice_id'] ?? null,
            'stripe_payment_intent_id' => $context['stripe_payment_intent_id'] ?? null,
            'current_period_end' => $context['current_period_end'] ?? null,
            'next_retry_at' => $context['next_retry_at'] ?? null,
            'reason_code' => $context['reason_code'] ?? null,
            'reason_text' => $context['reason_text'] ?? null,
            'occurred_at' => $context['occurred_at'] ?? get_date(),
            'payload_snapshot' => $context['payload_snapshot'] ?? null,
        ]);

        if($started_past_due) {
            if(($extra->billing_last_notification_stage ?? null) !== self::NOTIFICATION_WARNING_FIRST) {
                $this->send_notification($user, self::NOTIFICATION_WARNING_FIRST, $context + [
                    'grace_until' => $extra->billing_grace_until,
                    'reason_code' => $context['reason_code'] ?? 'stripe_subscription_past_due',
                    'reason_text' => $context['reason_text'] ?? ('Stripe status: ' . ($context['stripe_status'] ?? 'unknown')),
                ]);
            }

            return;
        }

        if(in_array($context['stripe_status'] ?? '', ['unpaid', 'canceled', 'incomplete_expired'], true)) {
            $this->safely_cancel_failed_subscription($user, $context['occurred_at'] ?? get_date(), $context['reason_text'] ?? ('Stripe status: ' . ($context['stripe_status'] ?? 'unknown')));
            $this->revoke_user_access($user, $context + [
                'revoked_at' => $context['occurred_at'] ?? get_date(),
                'grace_until' => null,
                'reason_code' => $context['reason_code'] ?? 'stripe_subscription_terminal',
                'reason_text' => $context['reason_text'] ?? ('Stripe status: ' . ($context['stripe_status'] ?? 'unknown')),
            ]);
        }

        if(($context['stripe_status'] ?? '') === 'active' && in_array($previous_state, [self::STATE_PAST_DUE, self::STATE_PAST_DUE_CRITICAL, self::STATE_ACCESS_REVOKED], true)) {
            $this->handle_successful_payment($context + ['occurred_at' => $context['occurred_at'] ?? get_date()]);
        }
    }

    public function process_grace_periods(int $limit = 25): array {
        $now = get_date();
        $escalated = 0;
        $revoked = 0;

        $result = database()->query("SELECT * FROM `users` WHERE JSON_UNQUOTE(JSON_EXTRACT(`extra`, '$.billing_state')) IN ('" . self::STATE_PAST_DUE . "', '" . self::STATE_PAST_DUE_CRITICAL . "', '" . self::STATE_ACCESS_REVOKED . "') ORDER BY JSON_UNQUOTE(JSON_EXTRACT(`extra`, '$.billing_grace_until')) ASC LIMIT {$limit}");

        while($user = $result->fetch_object()) {
            $extra = $this->decode_extra($user->extra ?? null);
            $state = $extra->billing_state ?? self::STATE_HEALTHY;
            $grace_started_at = $this->normalize_datetime($extra->billing_grace_started_at ?? null);
            $grace_until = $this->normalize_datetime($extra->billing_grace_until ?? null);

            if($this->is_active_access_status($extra->billing_stripe_status ?? null)) {
                $this->handle_successful_payment([
                    'user_id' => (int) $user->user_id,
                    'email' => $user->email,
                    'processor' => $user->payment_processor ?: 'stripe',
                    'stripe_subscription_id' => $user->payment_subscription_id,
                    'stripe_status' => 'active',
                    'occurred_at' => $now,
                ]);

                continue;
            }

            if(in_array($state, [self::STATE_PAST_DUE, self::STATE_PAST_DUE_CRITICAL], true)) {
                if(!$grace_until && $grace_started_at) {
                    $grace_until = $this->get_retry_window_until($grace_started_at, $extra->billing_next_retry_at ?? null);
                    $extra->billing_grace_until = $grace_until;
                    $this->update_user_extra((int) $user->user_id, $extra);
                }

                if($grace_until && $now < $grace_until) {
                    $last_notification_at = $this->normalize_datetime($extra->billing_last_notification_at ?? null);
                    $escalation_base_at = ($extra->billing_last_notification_stage ?? null) === self::NOTIFICATION_WARNING_FIRST && $last_notification_at
                        ? $last_notification_at
                        : $grace_started_at;
                    $escalation_at = $escalation_base_at
                        ? (new \DateTime($escalation_base_at))->modify('+' . self::ESCALATION_HOURS . ' hours')->format('Y-m-d H:i:s')
                        : null;

                    if($state === self::STATE_PAST_DUE && $escalation_at && $now >= $escalation_at) {
                        $extra->billing_state = self::STATE_PAST_DUE_CRITICAL;
                        $this->update_user_extra((int) $user->user_id, $extra);

                        $this->send_notification($user, self::NOTIFICATION_WARNING_SECOND, [
                            'user_id' => (int) $user->user_id,
                            'plan_id' => (int) $user->plan_id,
                            'email' => $user->email,
                            'processor' => $user->payment_processor ?: 'stripe',
                            'stripe_subscription_id' => $user->payment_subscription_id,
                            'stripe_status' => $extra->billing_stripe_status ?? null,
                            'reason_code' => $extra->billing_last_failed_reason_code ?? 'stripe_payment_failed',
                            'reason_text' => $extra->billing_last_failed_reason_text ?? l('global.unknown'),
                            'grace_until' => $grace_until,
                            'next_retry_at' => $extra->billing_next_retry_at ?? null,
                        ]);

                        $escalated++;
                    }

                    continue;
                }

                if(!$grace_until) {
                    continue;
                }

                if(!$this->safely_cancel_failed_subscription($user, $now, $extra->billing_last_failed_reason_text ?? l('global.unknown'))) {
                    continue;
                }

                $this->revoke_user_access($user, [
                    'user_id' => (int) $user->user_id,
                    'plan_id' => (int) $user->plan_id,
                    'email' => $user->email,
                    'processor' => $user->payment_processor ?: 'stripe',
                    'stripe_subscription_id' => $user->payment_subscription_id,
                    'stripe_status' => 'canceled',
                    'reason_code' => 'stripe_retry_window_ended',
                    'reason_text' => $extra->billing_last_failed_reason_text ?? l('global.unknown'),
                    'grace_until' => null,
                    'revoked_at' => $now,
                    'notification_stage' => self::NOTIFICATION_REVOKED,
                ]);

                $revoked++;
            }
        }

        return [
            'escalated' => $escalated,
            'revoked' => $revoked,
        ];
    }

    public function get_user_billing_summary(int $user_id): array {
        $user = db()->where('user_id', $user_id)->getOne('users');
        $extra = $this->decode_extra($user->extra ?? null);

        return [
            'billing_state' => $extra->billing_state ?? self::STATE_HEALTHY,
            'stripe_status' => $extra->billing_stripe_status ?? null,
            'grace_started_at' => $extra->billing_grace_started_at ?? null,
            'grace_until' => $extra->billing_grace_until ?? null,
            'last_failed_at' => $extra->billing_last_failed_at ?? null,
            'last_failed_reason_code' => $extra->billing_last_failed_reason_code ?? null,
            'last_failed_reason_text' => $extra->billing_last_failed_reason_text ?? null,
            'last_invoice_id' => $extra->billing_last_invoice_id ?? null,
            'last_payment_intent_id' => $extra->billing_last_payment_intent_id ?? null,
            'last_notification_stage' => $extra->billing_last_notification_stage ?? null,
            'last_notification_at' => $extra->billing_last_notification_at ?? null,
            'recovered_at' => $extra->billing_recovered_at ?? null,
            'access_revoked_at' => $extra->billing_access_revoked_at ?? null,
            'failed_attempts' => (int) ($extra->billing_failed_attempts ?? 0),
            'next_retry_at' => $extra->billing_next_retry_at ?? null,
            'current_period_end' => $extra->billing_current_period_end ?? null,
        ];
    }

    public function get_user_billing_events(int $user_id, int $limit = 50): array {
        $events = [];
        $result = db()->where('user_id', $user_id)->where('type', self::DATA_TYPE)->orderBy('datum_id', 'DESC')->get('data', $limit);

        foreach($result as $row) {
            $event = json_decode($row->data ?? '{}');
            if(is_array($event)) {
                $event = (object) $event;
            }

            if(!is_object($event)) {
                $event = (object) [];
            }

            $events[] = [
                'event_type' => (string) ($event->event_type ?? 'unknown'),
                'processor' => (string) ($event->processor ?? 'stripe'),
                'billing_state_before' => (string) ($event->billing_state_before ?? ''),
                'billing_state_after' => (string) ($event->billing_state_after ?? ''),
                'reason_code' => (string) ($event->reason_code ?? ''),
                'reason_text' => (string) ($event->reason_text ?? ''),
                'notification_stage' => (string) ($event->notification_stage ?? ''),
                'notification_subject' => (string) ($event->notification_subject ?? ''),
                'mail_success' => isset($event->mail_success) ? (int) $event->mail_success : null,
                'mail_message_id' => (string) ($event->mail_message_id ?? ''),
                'mail_error' => (string) ($event->mail_error ?? ''),
                'stripe_status' => (string) ($event->stripe_status ?? ''),
                'stripe_event_id' => (string) ($event->stripe_event_id ?? ''),
                'stripe_subscription_id' => (string) ($event->stripe_subscription_id ?? ''),
                'stripe_invoice_id' => (string) ($event->stripe_invoice_id ?? ''),
                'stripe_payment_intent_id' => (string) ($event->stripe_payment_intent_id ?? ''),
                'grace_started_at' => (string) ($event->grace_started_at ?? ''),
                'grace_until' => (string) ($event->grace_until ?? ''),
                'next_retry_at' => (string) ($event->next_retry_at ?? ''),
                'occurred_at' => (string) ($event->occurred_at ?? $row->datetime ?? ''),
                'amount' => isset($event->amount) ? (float) $event->amount : null,
                'currency' => (string) ($event->currency ?? ''),
            ];
        }

        return $events;
    }

    private function build_risk_filters_sql(array $filters = []): string {
        $where = ["JSON_UNQUOTE(JSON_EXTRACT(`users`.`extra`, '$.billing_state')) IN ('" . self::STATE_PAST_DUE . "', '" . self::STATE_PAST_DUE_CRITICAL . "', '" . self::STATE_ACCESS_REVOKED . "')"];

        if(!empty($filters['state']) && in_array($filters['state'], [self::STATE_PAST_DUE, self::STATE_PAST_DUE_CRITICAL, self::STATE_ACCESS_REVOKED], true)) {
            $state = database()->real_escape_string($filters['state']);
            $where[] = "JSON_UNQUOTE(JSON_EXTRACT(`users`.`extra`, '$.billing_state')) = '{$state}'";
        }

        if(!empty($filters['processor'])) {
            $processor = database()->real_escape_string($filters['processor']);
            $where[] = "`users`.`payment_processor` = '{$processor}'";
        }

        if(!empty($filters['search'])) {
            $search = database()->real_escape_string($filters['search']);
            $where[] = "(`users`.`name` LIKE '%{$search}%' OR `users`.`email` LIKE '%{$search}%' OR JSON_UNQUOTE(JSON_EXTRACT(`users`.`extra`, '$.billing_last_failed_reason_text')) LIKE '%{$search}%')";
        }

        return implode(' AND ', $where);
    }

    public function count_risk_users(array $filters = []): int {
        $where_sql = $this->build_risk_filters_sql($filters);
        $result = database()->query("SELECT COUNT(*) AS total FROM `users` WHERE {$where_sql}");

        return (int) ($result->fetch_object()->total ?? 0);
    }

    public function get_risk_users(array $filters = [], int $limit = 20, int $offset = 0): array {
        $where_sql = $this->build_risk_filters_sql($filters);
        $users = [];

        $result = database()->query("SELECT `users`.* FROM `users` WHERE {$where_sql} ORDER BY JSON_UNQUOTE(JSON_EXTRACT(`users`.`extra`, '$.billing_grace_until')) ASC, `users`.`user_id` DESC LIMIT {$offset}, {$limit}");

        while($user = $result->fetch_object()) {
            $summary = $this->get_user_billing_summary((int) $user->user_id);

            $users[] = [
                'user' => $user,
                'summary' => $summary,
            ];
        }

        return $users;
    }

    public function get_dashboard_payload(): array {
        $risk_users = [];
        $recovered_last_7d = 0;
        $expiring_24h = 0;

        $states = [self::STATE_PAST_DUE => 0, self::STATE_PAST_DUE_CRITICAL => 0, self::STATE_ACCESS_REVOKED => 0];
        $seven_days_ago = (new \DateTime())->modify('-7 days')->format('Y-m-d H:i:s');
        $tomorrow = (new \DateTime())->modify('+24 hours')->format('Y-m-d H:i:s');

        $result = database()->query("SELECT `user_id`, `name`, `email`, `plan_id`, `payment_processor`, `payment_subscription_id`, `extra` FROM `users` WHERE JSON_UNQUOTE(JSON_EXTRACT(`extra`, '$.billing_state')) IN ('" . self::STATE_PAST_DUE . "', '" . self::STATE_PAST_DUE_CRITICAL . "', '" . self::STATE_ACCESS_REVOKED . "') ORDER BY JSON_UNQUOTE(JSON_EXTRACT(`extra`, '$.billing_grace_until')) ASC, `user_id` DESC LIMIT 50");

        while($user = $result->fetch_object()) {
            $extra = $this->decode_extra($user->extra ?? null);
            $state = $extra->billing_state ?? self::STATE_HEALTHY;

            if(isset($states[$state])) {
                $states[$state]++;
            }

            $grace_until = $this->normalize_datetime($extra->billing_grace_until ?? null);
            if($grace_until && $grace_until <= $tomorrow && in_array($state, [self::STATE_PAST_DUE, self::STATE_PAST_DUE_CRITICAL], true)) {
                $expiring_24h++;
            }

            $risk_users[] = [
                'user_id' => (int) $user->user_id,
                'name' => (string) ($user->name ?? l('global.unknown')),
                'email' => (string) ($user->email ?? ''),
                'plan_id' => (string) ($user->plan_id ?? ''),
                'payment_processor' => (string) ($user->payment_processor ?? ''),
                'payment_subscription_id' => (string) ($user->payment_subscription_id ?? ''),
                'billing_state' => $state,
                'stripe_status' => (string) ($extra->billing_stripe_status ?? ''),
                'last_failed_reason_text' => (string) ($extra->billing_last_failed_reason_text ?? ''),
                'last_failed_reason_code' => (string) ($extra->billing_last_failed_reason_code ?? ''),
                'last_failed_at' => (string) ($extra->billing_last_failed_at ?? ''),
                'grace_until' => (string) ($extra->billing_grace_until ?? ''),
                'next_retry_at' => (string) ($extra->billing_next_retry_at ?? ''),
                'last_notification_stage' => (string) ($extra->billing_last_notification_stage ?? ''),
                'failed_attempts' => (int) ($extra->billing_failed_attempts ?? 0),
            ];
        }

        $recovered_result = database()->query("SELECT COUNT(*) AS total FROM `users` WHERE JSON_UNQUOTE(JSON_EXTRACT(`extra`, '$.billing_recovered_at')) >= '{$seven_days_ago}'");
        $recovered_last_7d = (int) ($recovered_result->fetch_object()->total ?? 0);

        return [
            'counts' => [
                'past_due' => $states[self::STATE_PAST_DUE],
                'past_due_critical' => $states[self::STATE_PAST_DUE_CRITICAL],
                'access_revoked' => $states[self::STATE_ACCESS_REVOKED],
                'expiring_24h' => $expiring_24h,
                'recovered_7d' => $recovered_last_7d,
            ],
            'risk_users' => array_slice($risk_users, 0, 7),
        ];
    }
    /* /Custom code: FC-2026-03-17 */
}
