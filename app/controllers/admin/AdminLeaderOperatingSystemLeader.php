<?php
/* Custom code: FC-2026-03-31: Leader Operating System detail controller */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Models\Billing;
use Altum\Response;
use Altum\Title;

defined('ALTUMCODE') || die();

class AdminLeaderOperatingSystemLeader extends Controller {

    private function handle_fcc_ai_feedback_resolve_action(int $user_id, string $selected_period): void {
        if(!isset($_POST['resolve_ai_feedback'])) {
            return;
        }

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/leader-operating-system-leader?user_id=' . $user_id . '&period=' . $selected_period . '#leader-os-ai-reviews');
        }

        try {
            fcc_ai_mark_feedback_resolved_by_admin(
                (int) ($_POST['feedback_id'] ?? 0),
                (int) ($this->user->user_id ?? 0)
            );

            Alerts::add_success(l('fcc_ai.review.resolve_success'));
        } catch(\Throwable $exception) {
            Alerts::add_error(trim((string) $exception->getMessage()) ?: l('global.error_message.basic'));
        }

        redirect('admin/leader-operating-system-leader?user_id=' . $user_id . '&period=' . $selected_period . '#leader-os-ai-reviews');
    }

    private function save_user_preferences(int $user_id, \stdClass $preferences): void {
        db()->where('user_id', $user_id)->update('users', [
            'preferences' => json_encode($preferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        /* Custom code: FC-2026-03-31: Keep user caches aligned after LOS/admin preference updates */
        cache()->deleteItemsByTag('user_id=' . $user_id);
        cache()->deleteItem('user?user_id=' . $user_id);
        /* /Custom code: FC-2026-03-31 */
    }

    private function get_extra_object($extra): \stdClass {
        if(is_string($extra)) {
            $extra = json_decode($extra ?? '{}');
        }

        if(is_array($extra)) {
            $extra = (object) $extra;
        }

        if(!$extra instanceof \stdClass) {
            $extra = (object) $extra;
        }

        return $extra;
    }

    private function save_user_extra(int $user_id, \stdClass $extra): void {
        db()->where('user_id', $user_id)->update('users', [
            'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        cache()->deleteItemsByTag('user_id=' . $user_id);
        cache()->deleteItem('user?user_id=' . $user_id);
    }

    private function get_featured_profile_main_biolink(int $user_id): ?object {
        fcc_featured_ensure_columns();

        if($user_id <= 0) {
            return null;
        }

        $mapped_biolink_id = (int) (fc_get_user_main_biolink_id($user_id) ?? 0);
        $select_sql = "
            SELECT
                `links`.*,
                `domains`.`scheme`,
                `domains`.`host`,
                `domains`.`link_id` AS `domain_link_id`,
                `users`.`name`,
                `users`.`email`,
                `users`.`preferences`,
                `users`.`billing`,
                `users`.`plan_id`,
                `users`.`plan_settings`,
                `users`.`plan_expiration_date`
            FROM `links`
            INNER JOIN `users` ON `users`.`user_id` = `links`.`user_id`
            LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id`
        ";

        if($mapped_biolink_id > 0) {
            $result = database()->query($select_sql . "
                WHERE `links`.`user_id` = {$user_id}
                  AND `links`.`link_id` = {$mapped_biolink_id}
                  AND `links`.`type` = 'biolink'
                LIMIT 1
            ");
            $biolink = $result ? $result->fetch_object() : null;

            if($biolink) {
                return $biolink;
            }
        }

        $result = database()->query($select_sql . "
            WHERE `links`.`user_id` = {$user_id}
              AND `links`.`type` = 'biolink'
            ORDER BY
                CASE WHEN `links`.`is_enabled` = 1 THEN 0 ELSE 1 END ASC,
                `links`.`datetime` ASC,
                `links`.`link_id` ASC
            LIMIT 1
        ");

        return $result ? ($result->fetch_object() ?: null) : null;
    }

    private function get_featured_profile_admin_payload(int $user_id): array {
        $main_biolink = $this->get_featured_profile_main_biolink($user_id);

        if(!$main_biolink) {
            return [
                'exists' => false,
            ];
        }

        $link_id = (int) ($main_biolink->link_id ?? 0);
        $signal_snapshot = fcc_ai_get_user_public_visibility_signal_snapshot($user_id);
        $sales_link_summary = fcc_ai_get_user_sales_link_summary($main_biolink, \Altum\Language::$code);
        $generated_profile = fcc_featured_decode_json_payload($main_biolink->fcc_featured_profile_generated ?? null);
        $feature_labels = fcc_featured_get_case_study_feature_labels($link_id, \Altum\Language::$code);
        $plan_settings = fcc_ai_get_user_plan_settings($main_biolink);
        $has_growth_pro = !empty($plan_settings->ai_growth_plan_is_enabled ?? false)
            && fcc_ai_is_plan_expiration_active((string) ($main_biolink->plan_expiration_date ?? ''));
        $growth_signal_30d = max(0, (int) ($signal_snapshot['growth_signal_30d'] ?? 0));
        $growth_signal_7d = max(0, (int) ($signal_snapshot['growth_signal_7d'] ?? 0));
        $public_use_case = fcc_featured_get_effective_public_use_case((string) ($main_biolink->fcc_featured_public_use_case ?? ''), $generated_profile, $feature_labels, \Altum\Language::$code);
        $public_summary = fcc_featured_get_effective_public_summary((string) ($main_biolink->fcc_featured_public_summary ?? ''), $generated_profile, $feature_labels, \Altum\Language::$code);
        $profile_intro = trim((string) ($generated_profile['profile_intro'] ?? ''));
        $meta_description = trim((string) ($generated_profile['meta_description'] ?? ''));
        $profile_slug = fcc_featured_build_profile_slug((string) ($main_biolink->name ?? ''), $link_id);
        $is_public_base_ready = $has_growth_pro
            && !empty($main_biolink->is_enabled)
            && !empty($main_biolink->fcc_featured_opt_in)
            && !empty($main_biolink->fcc_featured_is_approved);
        $is_featured_listed = $is_public_base_ready && $growth_signal_30d >= 15;
        $is_recommended_sponsor = $is_featured_listed
            && $growth_signal_30d >= 50
            && !empty($sales_link_summary['has_valid_enabled_link']);

        return [
            'exists' => true,
            'main_biolink_id' => $link_id,
            'main_biolink_url' => (string) ($main_biolink->url ?? ''),
            'public_app_url' => fcc_featured_build_public_app_url($main_biolink, $link_id),
            'public_market' => fcc_featured_get_default_public_market($main_biolink),
            'public_use_case' => $public_use_case,
            'public_summary' => $public_summary,
            'profile_intro' => $profile_intro,
            'meta_description' => $meta_description,
            'generated_at' => trim((string) ($generated_profile['generated_at'] ?? '')),
            'generated_model' => trim((string) ($generated_profile['model'] ?? '')),
            'feature_labels' => $feature_labels,
            'growth_signal_30d' => $growth_signal_30d,
            'growth_signal_7d' => $growth_signal_7d,
            'featured_threshold_reached' => $growth_signal_30d >= 15,
            'recommended_threshold_reached' => $growth_signal_30d >= 50,
            'has_growth_pro' => $has_growth_pro,
            'is_public_base_ready' => $is_public_base_ready,
            'is_featured_listed' => $is_featured_listed,
            'is_recommended_sponsor' => $is_recommended_sponsor,
            'sales_link_ready' => !empty($sales_link_summary['has_valid_enabled_link']),
            'sales_link_status_label' => (string) ($sales_link_summary['status_label'] ?? l('global.none')),
            'sales_link_editor_url' => (string) ($sales_link_summary['editor_url'] ?? url('links')),
            'opt_in_enabled' => !empty($main_biolink->fcc_featured_opt_in),
            'is_approved' => !empty($main_biolink->fcc_featured_is_approved),
            'profile_url' => $is_recommended_sponsor ? url('recommended-sponsors/' . $profile_slug) : '',
            'featured_apps_url' => $is_featured_listed ? url('featured-apps') : '',
            'generated_profile' => $generated_profile,
        ];
    }

    private function save_featured_profile_admin(int $user_id): void {
        $main_biolink = $this->get_featured_profile_main_biolink($user_id);

        if(!$main_biolink) {
            throw new \Exception('Glavna FCC aplikacija nije pronađena za ovog suradnika.');
        }

        $public_use_case = input_clean($_POST['fcc_featured_public_use_case'] ?? '', 128);
        $public_summary = input_clean($_POST['fcc_featured_public_summary'] ?? '', 420);
        $profile_intro = input_clean($_POST['fcc_featured_profile_intro'] ?? '', 880);
        $meta_description = input_clean($_POST['fcc_featured_meta_description'] ?? '', 180);
        $generated_profile = fcc_featured_decode_json_payload($main_biolink->fcc_featured_profile_generated ?? null);

        foreach([
            'public_use_case' => $public_use_case,
            'public_summary' => $public_summary,
            'profile_intro' => $profile_intro,
            'meta_description' => $meta_description,
        ] as $field => $value) {
            if($value === '') {
                unset($generated_profile[$field]);
            } else {
                $generated_profile[$field] = $value;
            }
        }

        if(!empty($generated_profile)) {
            $generated_profile['admin_updated_at'] = get_date();
        }

        db()->where('link_id', (int) $main_biolink->link_id)->where('user_id', $user_id)->update('links', [
            'fcc_featured_public_use_case' => $public_use_case ?: null,
            'fcc_featured_public_summary' => $public_summary ?: null,
            'fcc_featured_profile_generated' => !empty($generated_profile) ? json_encode($generated_profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) : null,
        ]);

        fcc_featured_clear_public_cache($user_id, (int) $main_biolink->link_id);
    }

    private function configure_stripe_api(): bool {
        $secret_key = trim((string) (settings()->stripe->secret_key ?? ''));

        if($secret_key === '') {
            return false;
        }

        \Stripe\Stripe::setApiKey($secret_key);
        \Stripe\Stripe::setApiVersion('2023-10-16');

        return true;
    }

    private function is_test_stripe_mode(): bool {
        $publishable_key = trim((string) (settings()->stripe->publishable_key ?? ''));
        $secret_key = trim((string) (settings()->stripe->secret_key ?? ''));

        return str_starts_with($publishable_key, 'pk_test_') || str_starts_with($secret_key, 'sk_test_');
    }

    private function get_stripe_dashboard_url(string $resource_type, ?string $resource_id): ?string {
        $resource_id = trim((string) $resource_id);

        if($resource_id === '') {
            return null;
        }

        $dashboard_prefix = $this->is_test_stripe_mode() ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';

        return $dashboard_prefix . trim($resource_type, '/') . '/' . rawurlencode($resource_id);
    }

    private function format_stripe_amount($amount_minor, ?string $currency): ?string {
        if(!is_numeric($amount_minor) || !$currency) {
            return null;
        }

        $currency = mb_strtoupper((string) $currency);
        $is_zero_decimal = in_array($currency, get_zero_decimal_currencies_array(), true);
        $amount = $is_zero_decimal ? (float) $amount_minor : ((float) $amount_minor / 100);

        return nr($amount, $is_zero_decimal ? 0 : 2) . ' ' . $currency;
    }

    private function get_stripe_price_interval_label($price): ?string {
        $recurring = is_object($price) ? ($price->recurring ?? null) : null;

        if(!$recurring || !is_object($recurring)) {
            return null;
        }

        $interval = (string) ($recurring->interval ?? '');
        $interval_count = max(1, (int) ($recurring->interval_count ?? 1));

        if($interval === 'day' && $interval_count === 30) {
            return l('plan.custom_plan.monthly');
        }

        if($interval === 'day' && $interval_count === 365) {
            return l('plan.custom_plan.annual');
        }

        if($interval === 'month' && $interval_count === 1) {
            return l('plan.custom_plan.monthly');
        }

        if($interval === 'month' && $interval_count === 3) {
            return l('plan.custom_plan.quarterly');
        }

        if($interval === 'month' && $interval_count === 6) {
            return l('plan.custom_plan.biannual');
        }

        if($interval === 'month' && $interval_count === 12) {
            return l('plan.custom_plan.annual');
        }

        if($interval === '') {
            return null;
        }

        return $interval_count > 1 ? $interval_count . ' ' . $interval : ucfirst($interval);
    }

    private function get_stripe_status_class(?string $status): string {
        return [
            'active' => 'success',
            'trialing' => 'info',
            'past_due' => 'warning',
            'unpaid' => 'danger',
            'canceled' => 'dark',
            'cancelled' => 'dark',
            'incomplete' => 'warning',
            'incomplete_expired' => 'dark',
            'paused' => 'warning',
        ][trim((string) $status)] ?? 'dark';
    }

    private function get_billing_state_class(?string $state): string {
        return [
            'healthy' => 'success',
            'past_due' => 'warning',
            'past_due_critical' => 'danger',
            'access_revoked' => 'dark',
            'recovered' => 'success',
        ][trim((string) $state)] ?? 'dark';
    }

    private function persist_stripe_customer_id(int $user_id, ?string $stripe_customer_id): void {
        if(!$stripe_customer_id || !str_starts_with($stripe_customer_id, 'cus_')) {
            return;
        }

        $user = db()->where('user_id', $user_id)->getOne('users', ['extra']);

        if(!$user) {
            return;
        }

        $extra = $this->get_extra_object($user->extra ?? null);

        if(($extra->stripe_customer_id ?? null) === $stripe_customer_id) {
            return;
        }

        $extra->stripe_customer_id = $stripe_customer_id;
        $this->save_user_extra($user_id, $extra);
    }

    private function resolve_stripe_customer_id(array $detail): ?string {
        $extra = $this->get_extra_object($detail['extra'] ?? null);
        $stored_customer_id = trim((string) ($extra->stripe_customer_id ?? ''));

        if($stored_customer_id !== '' && str_starts_with($stored_customer_id, 'cus_')) {
            try {
                \Stripe\Customer::retrieve($stored_customer_id);
                return $stored_customer_id;
            } catch(\Throwable $exception) {
                /* Fall through to secondary lookup paths. */
            }
        }

        $subscription_id = trim((string) ($detail['payment_subscription_id'] ?? ''));
        if($subscription_id !== '' && str_starts_with($subscription_id, 'sub_')) {
            try {
                $subscription = \Stripe\Subscription::retrieve($subscription_id);
                $customer_id = is_string($subscription->customer ?? null) ? $subscription->customer : null;

                if($customer_id) {
                    $this->persist_stripe_customer_id((int) ($detail['user_id'] ?? 0), $customer_id);
                    return $customer_id;
                }
            } catch(\Throwable $exception) {
                /* Continue with email lookup if the stored subscription is stale. */
            }
        }

        $email = trim((string) ($detail['email'] ?? ''));
        if($email === '') {
            return null;
        }

        $customers = \Stripe\Customer::all([
            'email' => $email,
            'limit' => 10,
        ]);

        if(count($customers->data) === 1) {
            $customer_id = $customers->data[0]->id ?? null;

            if($customer_id) {
                $this->persist_stripe_customer_id((int) ($detail['user_id'] ?? 0), $customer_id);
                return $customer_id;
            }
        }

        $active_customer_ids = [];

        foreach($customers->data as $customer) {
            $subscriptions = \Stripe\Subscription::all([
                'customer' => $customer->id,
                'status' => 'all',
                'limit' => 10,
            ]);

            foreach($subscriptions->data as $subscription) {
                if(in_array($subscription->status ?? '', ['active', 'trialing', 'past_due', 'unpaid'], true)) {
                    $active_customer_ids[$customer->id] = $customer->id;
                    break;
                }
            }
        }

        if(count($active_customer_ids) === 1) {
            $customer_id = array_values($active_customer_ids)[0];
            $this->persist_stripe_customer_id((int) ($detail['user_id'] ?? 0), $customer_id);
            return $customer_id;
        }

        return null;
    }

    private function resolve_stripe_subscription(array $detail, ?string $customer_id = null) {
        $subscription_id = trim((string) ($detail['payment_subscription_id'] ?? ''));

        if($subscription_id !== '' && str_starts_with($subscription_id, 'sub_')) {
            try {
                return \Stripe\Subscription::retrieve($subscription_id);
            } catch(\Throwable $exception) {
                /* Continue with customer-scoped fallback lookup. */
            }
        }

        if(!$customer_id) {
            return null;
        }

        $subscriptions = \Stripe\Subscription::all([
            'customer' => $customer_id,
            'status' => 'all',
            'limit' => 10,
        ]);

        if(empty($subscriptions->data)) {
            return null;
        }

        foreach(['active', 'trialing', 'past_due', 'unpaid', 'canceled', 'incomplete', 'incomplete_expired'] as $priority_status) {
            foreach($subscriptions->data as $subscription) {
                if(($subscription->status ?? null) === $priority_status) {
                    return $subscription;
                }
            }
        }

        return $subscriptions->data[0] ?? null;
    }

    private function extract_stripe_product_name($price): string {
        if(!is_object($price)) {
            return '-';
        }

        $product = $price->product ?? null;

        if(is_object($product) && !empty($product->name)) {
            return (string) $product->name;
        }

        if(is_string($product) && $product !== '') {
            try {
                $product_object = \Stripe\Product::retrieve($product);

                if(!empty($product_object->name)) {
                    return (string) $product_object->name;
                }
            } catch(\Throwable $exception) {
                /* Fallback to other labels below. */
            }
        }

        if(!empty($price->nickname)) {
            return (string) $price->nickname;
        }

        if(!empty($price->id)) {
            return (string) $price->id;
        }

        return '-';
    }

    private function build_stripe_recent_invoices(?string $customer_id): array {
        if(!$customer_id) {
            return [];
        }

        $invoices = \Stripe\Invoice::all([
            'customer' => $customer_id,
            'limit' => 4,
        ]);

        $payload = [];

        foreach($invoices->data as $invoice) {
            $payload[] = [
                'id' => (string) ($invoice->id ?? ''),
                'status' => (string) ($invoice->status ?? ''),
                'created_at' => !empty($invoice->created) ? date('Y-m-d H:i:s', (int) $invoice->created) : null,
                'total' => $this->format_stripe_amount($invoice->total ?? null, $invoice->currency ?? null),
                'amount_paid' => $this->format_stripe_amount($invoice->amount_paid ?? null, $invoice->currency ?? null),
                'hosted_invoice_url' => (string) ($invoice->hosted_invoice_url ?? ''),
                'dashboard_url' => $this->get_stripe_dashboard_url('invoices', $invoice->id ?? null),
            ];
        }

        return $payload;
    }

    private function get_stripe_billing_payload(array $detail, array $billing_summary): array {
        $payload = [
            'processor' => (string) ($detail['payment_processor'] ?? ''),
            'customer_id' => null,
            'subscription_id' => trim((string) ($detail['payment_subscription_id'] ?? '')) ?: null,
            'portal_available' => false,
            'portal_error' => null,
            'customer_dashboard_url' => null,
            'subscription_dashboard_url' => $this->get_stripe_dashboard_url('subscriptions', $detail['payment_subscription_id'] ?? null),
            'status' => (string) ($billing_summary['stripe_status'] ?? ''),
            'status_class' => $this->get_stripe_status_class($billing_summary['stripe_status'] ?? null),
            'billing_state' => (string) ($billing_summary['billing_state'] ?? 'healthy'),
            'billing_state_class' => $this->get_billing_state_class($billing_summary['billing_state'] ?? null),
            'plan_name' => null,
            'plan_price_label' => null,
            'price_id' => null,
            'current_period_end' => $billing_summary['current_period_end'] ?? null,
            'trial_end' => null,
            'cancel_at_period_end' => false,
            'cancel_at' => null,
            'last_failed_reason_text' => $billing_summary['last_failed_reason_text'] ?? null,
            'last_invoice_id' => $billing_summary['last_invoice_id'] ?? null,
            'last_invoice_dashboard_url' => $this->get_stripe_dashboard_url('invoices', $billing_summary['last_invoice_id'] ?? null),
            'last_payment_intent_id' => $billing_summary['last_payment_intent_id'] ?? null,
            'failed_attempts' => (int) ($billing_summary['failed_attempts'] ?? 0),
            'grace_until' => $billing_summary['grace_until'] ?? null,
            'next_retry_at' => $billing_summary['next_retry_at'] ?? null,
            'recent_invoices' => [],
        ];

        if(!$this->configure_stripe_api()) {
            $payload['portal_error'] = l('admin_leader_operating_system.leader.stripe_not_configured');
            return $payload;
        }

        try {
            $customer_id = $this->resolve_stripe_customer_id($detail);

            if($customer_id) {
                $payload['customer_id'] = $customer_id;
                $payload['portal_available'] = true;
                $payload['customer_dashboard_url'] = $this->get_stripe_dashboard_url('customers', $customer_id);
            }

            $subscription = $this->resolve_stripe_subscription($detail, $customer_id);

            if($subscription) {
                $payload['subscription_id'] = (string) ($subscription->id ?? $payload['subscription_id']);
                $payload['subscription_dashboard_url'] = $this->get_stripe_dashboard_url('subscriptions', $payload['subscription_id']);
                $payload['status'] = (string) ($subscription->status ?? $payload['status']);
                $payload['status_class'] = $this->get_stripe_status_class($payload['status']);
                $payload['current_period_end'] = !empty($subscription->current_period_end) ? date('Y-m-d H:i:s', (int) $subscription->current_period_end) : $payload['current_period_end'];
                $payload['trial_end'] = !empty($subscription->trial_end) ? date('Y-m-d H:i:s', (int) $subscription->trial_end) : null;
                $payload['cancel_at_period_end'] = (bool) ($subscription->cancel_at_period_end ?? false);
                $payload['cancel_at'] = !empty($subscription->cancel_at) ? date('Y-m-d H:i:s', (int) $subscription->cancel_at) : null;

                $price = $subscription->items->data[0]->price ?? null;
                $amount_label = is_object($price) ? $this->format_stripe_amount($price->unit_amount ?? null, $price->currency ?? null) : null;
                $interval_label = $this->get_stripe_price_interval_label($price);
                $payload['plan_name'] = $this->extract_stripe_product_name($price);
                $payload['plan_price_label'] = implode(' / ', array_filter([$amount_label, $interval_label]));
                $payload['price_id'] = is_object($price) ? (string) ($price->id ?? '') : null;

                if(!$payload['customer_id'] && is_string($subscription->customer ?? null)) {
                    $payload['customer_id'] = $subscription->customer;
                    $payload['portal_available'] = true;
                    $payload['customer_dashboard_url'] = $this->get_stripe_dashboard_url('customers', $subscription->customer);
                    $this->persist_stripe_customer_id((int) ($detail['user_id'] ?? 0), $subscription->customer);
                }
            }

            if($payload['customer_id']) {
                $payload['recent_invoices'] = $this->build_stripe_recent_invoices($payload['customer_id']);
            }

            if(!$payload['portal_available']) {
                $payload['portal_error'] = l('admin_leader_operating_system.leader.stripe_customer_missing');
            }
        } catch(\Throwable $exception) {
            $payload['portal_error'] = trim((string) $exception->getMessage()) ?: l('global.error_message.basic');
        }

        return $payload;
    }

    private function create_stripe_portal_url(array $detail, string $return_url): string {
        if(!$this->configure_stripe_api()) {
            throw new \Exception(l('admin_leader_operating_system.leader.stripe_not_configured'));
        }

        $customer_id = $this->resolve_stripe_customer_id($detail);

        if(!$customer_id) {
            throw new \Exception(l('admin_leader_operating_system.leader.stripe_customer_missing'));
        }

        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $customer_id,
            'return_url' => $return_url,
        ]);

        $portal_url = trim((string) ($session->url ?? ''));

        if($portal_url === '') {
            throw new \Exception(l('admin_leader_operating_system.leader.stripe_portal_session_failed'));
        }

        return $portal_url;
    }

    private function get_period_days(string $period_key): int {
        return [
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
        ][$period_key] ?? 30;
    }

    private function get_period_start_datetime(int $days): string {
        $days = max(1, $days);

        return (new \DateTime())->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');
    }

    private function get_biolink_sets(): array {
        $forever_shop_block_types = ['link_discount', 'link_forever_webshop_reg', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $forever_registration_block_types = ['link_forever_shop'];
        $forever_all_block_types = array_values(array_unique(array_merge($forever_shop_block_types, $forever_registration_block_types)));

        return [
            'forever_shop_block_types' => $forever_shop_block_types,
            'forever_registration_block_types' => $forever_registration_block_types,
            'forever_all_block_types_sql' => "'" . implode("', '", $forever_all_block_types) . "'",
            'forever_shop_block_types_sql' => "'" . implode("', '", $forever_shop_block_types) . "'",
            'forever_registration_block_types_sql' => "'" . implode("', '", $forever_registration_block_types) . "'",
        ];
    }

    private function get_app_webshop_block_types(): array {
        $app_webshop_block_types = \Altum\Link::get_monitored_forever_outbound_types();
        $app_webshop_block_types[] = 'link_forever_living_albania_kosovo';

        return array_values(array_unique($app_webshop_block_types));
    }

    private function get_app_webshop_block_types_sql(): string {
        return "'" . implode("', '", $this->get_app_webshop_block_types()) . "'";
    }

    private function get_country_table_key(?string $country_code): string {
        $country_code = strtoupper(trim((string) $country_code));

        return $country_code !== '' ? $country_code : '__unknown__';
    }

    private function get_country_table_name(string $country_key): string {
        if($country_key === '__unknown__') {
            return l('admin_leader_operating_system.leader.country_table.unknown');
        }

        return get_country_from_country_code($country_key);
    }

    private function get_blog_referral_mediums(): array {
        return [
            \Altum\Link::get_blog_cta_tracking_medium('product'),
            \Altum\Link::get_blog_cta_tracking_medium('business'),
        ];
    }

    private function get_blog_referral_mediums_sql(): string {
        return "'" . implode("', '", $this->get_blog_referral_mediums()) . "'";
    }

    private function get_blog_referral_click_condition_sql(string $track_links_alias): string {
        $blog_referral_mediums_sql = $this->get_blog_referral_mediums_sql();

        return "({$track_links_alias}.`utm_medium` IN ({$blog_referral_mediums_sql}) AND {$track_links_alias}.`utm_campaign` LIKE 'blog_post:%')";
    }

    private function has_funnel_events_table(): bool {
        static $has_funnel_events_table = null;

        if($has_funnel_events_table !== null) {
            return $has_funnel_events_table;
        }

        $result = database()->query("SHOW TABLES LIKE 'funnel_events'");
        $has_funnel_events_table = (bool) ($result && $result->num_rows);

        return $has_funnel_events_table;
    }

    private function extract_forever_id_from_preferences($preferences): string {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!is_object($preferences)) {
            return '-';
        }

        $meta = $preferences->meta ?? null;

        if(is_string($meta)) {
            $decoded_meta = json_decode($meta);
            if(is_array($decoded_meta)) {
                $decoded_meta = (object) $decoded_meta;
            }
            if(is_object($decoded_meta)) {
                $meta = $decoded_meta;
            }
        }

        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        if(!is_object($meta)) {
            return '-';
        }

        $forever_id = $meta->foreverId ?? $meta->forever_id ?? $meta->foreverID ?? null;
        $forever_id = is_scalar($forever_id) ? trim((string) $forever_id) : '';

        return $forever_id !== '' ? $forever_id : '-';
    }

    private function normalize_source_value(string $source): string {
        $source = mb_strtolower(trim($source));

        if($source === '') {
            return '';
        }

        if(strpos($source, '://') !== false) {
            $parsed_host = parse_url($source, PHP_URL_HOST);
            if(is_string($parsed_host) && $parsed_host !== '') {
                $source = $parsed_host;
            }
        }

        if(strpos($source, '/') !== false) {
            $source = explode('/', $source)[0];
        }

        return preg_replace('/^www\./', '', $source) ?? $source;
    }

    private function is_internal_source(string $source): bool {
        $source = $this->normalize_source_value($source);

        if($source === '') {
            return false;
        }

        $site_host = parse_url(SITE_URL, PHP_URL_HOST);
        $site_host = is_string($site_host) ? $this->normalize_source_value($site_host) : '';

        if($site_host !== '' && ($source === $site_host || str_ends_with($source, '.' . $site_host))) {
            return true;
        }

        return $source === 'forevercard.club' || str_ends_with($source, '.forevercard.club');
    }

    private function normalize_source_label(string $source): string {
        $source = $this->normalize_source_value($source);

        if($source === '' || in_array($source, ['(direct)', 'direct', 'none', '(none)'], true) || $this->is_internal_source($source)) {
            return l('admin_index.biolink_qualified_watch.source.direct_share');
        }

        if($source === 'direct_share') return l('admin_index.biolink_qualified_watch.source.direct_share');
        if(strpos($source, 'messenger') !== false) return 'messenger';
        if($source === 'fb' || strpos($source, 'facebook') !== false) return 'facebook';
        if($source === 'ig' || strpos($source, 'instagram') !== false) return 'instagram';
        if(strpos($source, 'whatsapp') !== false || $source === 'wa') return 'whatsapp';
        if($source === 'nfc_card') return l('admin_index.biolink_qualified_watch.source.nfc_card');
        if($source === 'qr') return l('admin_index.biolink_qualified_watch.source.qr');
        if(strpos($source, 'tiktok') !== false) return 'tiktok';
        if(strpos($source, 'youtube') !== false || $source === 'youtu.be') return 'youtube';
        if(strpos($source, 'telegram') !== false) return 'telegram';
        if(strpos($source, 'viber') !== false) return 'viber';
        if(strpos($source, 'email') !== false || strpos($source, 'mail') !== false) return 'email';
        if(strpos($source, 'google') !== false || $source === 'gclid') return 'google';
        if($source === 'x' || strpos($source, 'twitter') !== false) return 'x';
        if(strpos($source, 'threads') !== false) return 'threads';
        if(strpos($source, 'linkedin') !== false) return 'linkedin';
        if(strpos($source, 'pinterest') !== false) return 'pinterest';
        if(strpos($source, 'reddit') !== false) return 'reddit';
        if(strpos($source, 'snapchat') !== false) return 'snapchat';
        if(strpos($source, 'teams') !== false) return 'teams';

        return $source;
    }

    private function get_source_label(array $click): string {
        foreach([
            trim((string) ($click['utm_source'] ?? '')),
            trim((string) ($click['referrer_host'] ?? '')),
        ] as $candidate_source) {
            $normalized_source = $this->normalize_source_value($candidate_source);

            if($normalized_source === '' || $this->is_internal_source($normalized_source)) {
                continue;
            }

            return $this->normalize_source_label($normalized_source);
        }

        return l('admin_index.biolink_qualified_watch.source.direct_share');
    }

    private function decode_biolink_block_settings($settings): \stdClass {
        if(is_string($settings)) {
            $settings = json_decode($settings ?? '{}');
        }

        if(is_array($settings)) {
            $settings = (object) $settings;
        }

        if(!$settings instanceof \stdClass) {
            $settings = new \stdClass();
        }

        return $settings;
    }

    private function is_whatsapp_socials_block(\stdClass $settings): bool {
        $socials = $settings->socials ?? null;

        if(is_object($socials)) {
            $socials = (array) $socials;
        }

        if(!is_array($socials)) {
            return false;
        }

        $whatsapp_value = trim((string) ($socials['whatsapp'] ?? ''));

        if($whatsapp_value === '') {
            return false;
        }

        foreach($socials as $social_key => $social_value) {
            if($social_key === 'whatsapp') {
                continue;
            }

            if(trim((string) $social_value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function is_whatsapp_block(string $type, \stdClass $settings): bool {
        if($type === 'custom_html_whatsapp') {
            return true;
        }

        if($type === 'socials') {
            return $this->is_whatsapp_socials_block($settings);
        }

        if($type !== 'link') {
            return false;
        }

        $location_url = trim((string) ($settings->location_url ?? ''));
        if($location_url === '') {
            return false;
        }

        $location_url = mb_strtolower($location_url);

        return str_contains($location_url, 'wa.me') || str_contains($location_url, 'api.whatsapp.com');
    }

    private function calculate_app_signal_score(array $row): int {
        return (int) (
            (int) ($row['app_shop_contacts_period'] ?? 0)
            + (int) ($row['app_whatsapp_contacts_period'] ?? 0)
            + (int) ($row['app_product_clicks_period'] ?? 0)
            + ((int) ($row['app_funnel_registrations_period'] ?? 0) * 2)
        );
    }

    private function get_app_quality_payload(int $signal_score): array {
        $quality_score = $this->clamp_score($signal_score * 4);
        $stage_key = $quality_score >= 70 ? 'strong' : ($quality_score >= 40 ? 'growing' : 'foundation');
        $class_map = [
            'strong' => 'status-success',
            'growing' => 'status-info',
            'foundation' => 'status-warning',
        ];

        return [
            'app_quality_score' => $quality_score,
            'app_quality_stage_key' => $stage_key,
            'app_quality_stage_label' => l('admin_leader_operating_system.app_quality_stage.' . $stage_key),
            'app_quality_stage_class' => $class_map[$stage_key] ?? 'status-dark',
        ];
    }

    private function get_user_app_signal_payload(int $user_id, string $period_start_datetime): array {
        $payload = [
            'app_shop_contacts_period' => 0,
            'app_whatsapp_contacts_period' => 0,
            'app_product_clicks_period' => 0,
            'app_funnel_registrations_period' => 0,
            'app_signal_score' => 0,
        ];

        $shop_types = ['link_discount', 'link_forever_webshop_reg', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $relevant_types = array_unique(array_merge($shop_types, ['link_forever_product', 'lead_funnel', 'custom_html_whatsapp', 'socials', 'link']));
        $relevant_types_sql = "'" . implode("','", array_map(static function($type) {
            return str_replace("'", "\\'", (string) $type);
        }, $relevant_types)) . "'";

        $signal_targets = [];
        $blocks_result = database()->query("SELECT `biolink_block_id`, `type`, `settings`
            FROM `biolinks_blocks`
            WHERE `user_id` = {$user_id}
              AND `type` IN ({$relevant_types_sql})");

        while($row = $blocks_result->fetch_object()) {
            $block_id = (int) ($row->biolink_block_id ?? 0);
            $type = (string) ($row->type ?? '');

            if(!$block_id) {
                continue;
            }

            $settings = $this->decode_biolink_block_settings($row->settings ?? null);

            if(in_array($type, $shop_types, true)) {
                $signal_targets[$block_id]['app_shop_contacts_period'] = true;
            }

            if($type === 'link_forever_product') {
                $signal_targets[$block_id]['app_product_clicks_period'] = true;
            }

            if($type === 'lead_funnel') {
                $signal_targets[$block_id]['app_funnel_registrations_period'] = true;
            }

            if($this->is_whatsapp_block($type, $settings)) {
                $signal_targets[$block_id]['app_whatsapp_contacts_period'] = true;
            }
        }

        if(!empty($signal_targets)) {
            $block_ids_sql = implode(',', array_map('intval', array_keys($signal_targets)));
            $track_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                FROM `track_links`
                WHERE `datetime` >= '{$period_start_datetime}'
                  AND `is_unique` = 1
                  AND `biolink_block_id` IN ({$block_ids_sql})
                GROUP BY `biolink_block_id`");

            while($track_row = $track_result->fetch_object()) {
                $block_id = (int) ($track_row->biolink_block_id ?? 0);
                $total = (int) ($track_row->total ?? 0);

                foreach(array_keys((array) ($signal_targets[$block_id] ?? [])) as $signal_key) {
                    $payload[$signal_key] += $total;
                }
            }

            $funnel_block_ids = [];
            foreach($signal_targets as $block_id => $signal_map) {
                if(isset($signal_map['app_funnel_registrations_period'])) {
                    $funnel_block_ids[] = (int) $block_id;
                }
            }

            if(!empty($funnel_block_ids)) {
                $funnel_block_ids_sql = implode(',', array_map('intval', $funnel_block_ids));
                $funnel_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                    FROM `data`
                    WHERE `type` = 'lead_funnel'
                      AND `datetime` >= '{$period_start_datetime}'
                      AND `biolink_block_id` IN ({$funnel_block_ids_sql})
                    GROUP BY `biolink_block_id`");

                while($funnel_row = $funnel_result->fetch_object()) {
                    $payload['app_funnel_registrations_period'] += (int) ($funnel_row->total ?? 0);
                }
            }
        }

        $payload['app_signal_score'] = $this->calculate_app_signal_score($payload);

        return array_merge($payload, $this->get_app_quality_payload((int) $payload['app_signal_score']));
    }

    private function get_blog_cta_mediums(): array {
        return [
            'product' => \Altum\Link::get_blog_cta_tracking_medium('product'),
            'business' => \Altum\Link::get_blog_cta_tracking_medium('business'),
        ];
    }

    private function get_user_blog_forever_payload(int $user_id, string $period_start_datetime, int $total_forever_clicks = 0): array {
        $mediums = $this->get_blog_cta_mediums();
        $product_medium = db()->escape($mediums['product']);
        $business_medium = db()->escape($mediums['business']);

        $payload = [
            'total_clicks' => 0,
            'product_clicks' => 0,
            'business_clicks' => 0,
            'share_percent' => 0.0,
            'top_article_title' => '-',
            'top_article_url' => '',
            'top_article_clicks' => 0,
        ];

        $summary = database()->query("SELECT
            COUNT(*) AS `total_clicks`,
            SUM(CASE WHEN `utm_medium` = '{$product_medium}' THEN 1 ELSE 0 END) AS `product_clicks`,
            SUM(CASE WHEN `utm_medium` = '{$business_medium}' THEN 1 ELSE 0 END) AS `business_clicks`
        FROM `track_links`
        WHERE `user_id` = {$user_id}
          AND `datetime` >= '{$period_start_datetime}'
          AND `utm_medium` IN ('{$product_medium}', '{$business_medium}')")->fetch_object();

        if($summary) {
            $payload['total_clicks'] = (int) ($summary->total_clicks ?? 0);
            $payload['product_clicks'] = (int) ($summary->product_clicks ?? 0);
            $payload['business_clicks'] = (int) ($summary->business_clicks ?? 0);
        }

        $payload['share_percent'] = $total_forever_clicks > 0 ? round(($payload['total_clicks'] / $total_forever_clicks) * 100, 1) : 0.0;

        $top_article = database()->query("SELECT
            `blog_posts`.`title`,
            `blog_posts`.`url`,
            COUNT(*) AS `total`
        FROM `track_links`
        LEFT JOIN `blog_posts` ON `blog_posts`.`blog_post_id` = CAST(SUBSTRING_INDEX(`track_links`.`utm_campaign`, ':', -1) AS UNSIGNED)
        WHERE `track_links`.`user_id` = {$user_id}
          AND `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `track_links`.`utm_medium` IN ('{$product_medium}', '{$business_medium}')
        GROUP BY `blog_posts`.`blog_post_id`, `blog_posts`.`title`, `blog_posts`.`url`
        ORDER BY `total` DESC, `blog_posts`.`blog_post_id` DESC
        LIMIT 1")->fetch_object();

        if($top_article) {
            $payload['top_article_title'] = !empty($top_article->title) ? (string) $top_article->title : '-';
            $payload['top_article_url'] = (string) ($top_article->url ?? '');
            $payload['top_article_clicks'] = (int) ($top_article->total ?? 0);
        }

        return $payload;
    }

    private function get_consistency_payload(array $selected_payload, array $ai_plan_admin): array {
        $score = 0;

        $active_days_total = (int) ($selected_payload['active_days_total'] ?? 0);
        $score += min(35, $active_days_total * 4);

        if(!empty($ai_plan_admin['has_profile'])) $score += 10;
        if(!empty($ai_plan_admin['has_checkin'])) $score += 15;
        if(!empty($ai_plan_admin['has_plan'])) $score += 15;

        $completion_level = (string) ($ai_plan_admin['latest_outcome_completion_level'] ?? '');
        if($completion_level === 'completed') {
            $score += 20;
        } elseif(in_array($completion_level, ['partial', 'medium_execution', 'high_execution'], true)) {
            $score += 12;
        } elseif($completion_level === 'low_execution') {
            $score += 6;
        }

        $days_since_last_checkin = $ai_plan_admin['days_since_last_checkin'] ?? null;
        if($days_since_last_checkin !== null) {
            if((int) $days_since_last_checkin <= 7) {
                $score += 8;
            } elseif((int) $days_since_last_checkin <= 14) {
                $score += 3;
            } else {
                $score -= 8;
            }
        }

        $score = $this->clamp_score($score);
        $state_key = 'low';
        $state_class = 'status-dark';

        if($score >= 75) {
            $state_key = 'strong';
            $state_class = 'status-success';
        } elseif($score >= 50) {
            $state_key = 'steady';
            $state_class = 'status-info';
        } elseif($score >= 30) {
            $state_key = 'watch';
            $state_class = 'status-warning';
        }

        return [
            'score' => $score,
            'state_key' => $state_key,
            'state_label' => ucfirst($state_key),
            'state_class' => $state_class,
        ];
    }

    private function get_coaching_roi_payload(array $score_history, array $ai_plan_admin): array {
        $payload = [
            'available' => false,
            'anchor_at' => null,
            'days_since_touch' => null,
            'score_before' => null,
            'score_current' => null,
            'score_delta' => null,
            'risk_before' => null,
            'risk_current' => null,
            'risk_delta' => null,
            'signal_label' => 'Nema dovoljno podataka',
            'signal_class' => 'status-dark',
        ];

        $anchor_at = (string) ($ai_plan_admin['mentor_actions']['last_contacted_at'] ?? ($ai_plan_admin['latest_mentor_event']['created_at'] ?? ''));
        if($anchor_at === '') {
            return $payload;
        }

        $payload['anchor_at'] = $anchor_at;

        try {
            $payload['days_since_touch'] = (int) (new \DateTimeImmutable($anchor_at))->diff(new \DateTimeImmutable())->format('%a');
        } catch(\Throwable $exception) {
            $payload['days_since_touch'] = null;
        }

        $snapshots = array_values($score_history['history'] ?? []);
        if(empty($snapshots) && !empty($score_history['latest'])) {
            $snapshots[] = $score_history['latest'];
        }

        if(count($snapshots) < 2 || empty($score_history['latest'])) {
            return $payload;
        }

        $before_snapshot = null;
        foreach($snapshots as $snapshot) {
            $created_at = (string) ($snapshot['created_at'] ?? '');
            if($created_at !== '' && $created_at <= $anchor_at) {
                $before_snapshot = $snapshot;
                break;
            }
        }

        if(!$before_snapshot) {
            $before_snapshot = $score_history['previous'] ?? null;
        }

        if(!$before_snapshot) {
            return $payload;
        }

        $current_snapshot = $score_history['latest'];
        $payload['available'] = true;
        $payload['score_before'] = (int) ($before_snapshot['leader_os_score'] ?? 0);
        $payload['score_current'] = (int) ($current_snapshot['leader_os_score'] ?? 0);
        $payload['score_delta'] = (int) ($payload['score_current'] - $payload['score_before']);
        $payload['risk_before'] = (int) ($before_snapshot['risk_score'] ?? 0);
        $payload['risk_current'] = (int) ($current_snapshot['risk_score'] ?? 0);
        $payload['risk_delta'] = (int) ($payload['risk_current'] - $payload['risk_before']);

        if(($payload['score_delta'] ?? 0) >= 8 || (($payload['score_delta'] ?? 0) >= 3 && ($payload['risk_delta'] ?? 0) <= -5)) {
            $payload['signal_label'] = 'Pozitivan pomak nakon coachinga';
            $payload['signal_class'] = 'status-success';
        } elseif(($payload['score_delta'] ?? 0) <= -8 || ($payload['risk_delta'] ?? 0) >= 8) {
            $payload['signal_label'] = 'Potreban novi coaching zahvat';
            $payload['signal_class'] = 'status-warning';
        } else {
            $payload['signal_label'] = 'Rani signal bez jačeg pomaka';
            $payload['signal_class'] = 'status-info';
        }

        return $payload;
    }

    private function summarize_ai_text(?string $text, int $limit = 180): string {
        $text = trim((string) $text);

        if($text === '') {
            return '';
        }

        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string) $text);

        if(mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1, 'UTF-8')) . '…';
    }

    private function get_ai_text_detail_payload(array $ai_plan_admin): array {
        $latest_checkin = $ai_plan_admin['latest_checkin'] ?? [];
        $latest_outcome = $ai_plan_admin['latest_outcome'] ?? [];
        $latest_plan = $ai_plan_admin['latest_plan'] ?? [];

        $focus_summary = '';
        foreach([
            $latest_plan['focus'] ?? '',
            $latest_checkin['weekly_priority_label'] ?? '',
            $latest_plan['power_move'] ?? '',
        ] as $candidate_text) {
            $candidate_text = $this->summarize_ai_text((string) $candidate_text, 140);

            if($candidate_text !== '') {
                $focus_summary = $candidate_text;
                break;
            }
        }

        $payload = [
            'weekly_context' => $this->summarize_ai_text((string) ($latest_checkin['weekly_context'] ?? '')),
            'adaptive_answer' => $this->summarize_ai_text((string) ($latest_checkin['adaptive_answer'] ?? '')),
            'main_blocker_now' => $this->summarize_ai_text((string) ($latest_outcome['main_blocker_now'] ?? '')),
            'biggest_lesson' => $this->summarize_ai_text((string) ($latest_outcome['biggest_lesson'] ?? '')),
            'next_adjustment' => $this->summarize_ai_text((string) ($latest_outcome['next_adjustment'] ?? '')),
            'best_response' => $this->summarize_ai_text((string) ($latest_outcome['best_response'] ?? '')),
            'focus_summary' => $focus_summary,
        ];

        $payload['has_any'] = false;
        foreach(['weekly_context', 'adaptive_answer', 'main_blocker_now', 'biggest_lesson', 'next_adjustment', 'best_response', 'focus_summary'] as $field) {
            if(trim((string) ($payload[$field] ?? '')) !== '') {
                $payload['has_any'] = true;
                break;
            }
        }

        return $payload;
    }

    private function increment_bucket(array &$buckets, string $key, string $label): void {
        $key = trim($key);
        $label = trim($label);

        if($key === '' || $label === '') {
            return;
        }

        if(!isset($buckets[$key])) {
            $buckets[$key] = ['label' => $label, 'total' => 0];
        }

        $buckets[$key]['total']++;
    }

    private function build_breakdown(array $buckets, int $total, int $limit = 5): array {
        uasort($buckets, static function($a, $b) {
            return (($b['total'] ?? 0) <=> ($a['total'] ?? 0)) ?: (($a['label'] ?? '') <=> ($b['label'] ?? ''));
        });

        $result = [];
        foreach(array_slice($buckets, 0, max(1, $limit), true) as $item) {
            $item_total = (int) ($item['total'] ?? 0);
            $result[] = [
                'label' => (string) ($item['label'] ?? '-'),
                'total' => $item_total,
                'share' => $total > 0 ? round(($item_total / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    private function get_chart_series(int $user_id, string $period_start_datetime, int $period_days, array $biolink_sets): array {
        $labels = [];
        $app_visits = [];
        $app_shop_clicks = [];
        $blog_clicks = [];
        $funnel_registrations = [];

        $period_start = new \DateTimeImmutable($period_start_datetime);
        $date_index = [];

        for($day = 0; $day < $period_days; $day++) {
            $date = $period_start->add(new \DateInterval('P' . $day . 'D'));
            $date_key = $date->format('Y-m-d');
            $date_index[$date_key] = $day;
            $labels[] = $date->format('d.m.');
            $app_visits[] = 0;
            $app_shop_clicks[] = 0;
            $blog_clicks[] = 0;
            $funnel_registrations[] = 0;
        }

        $app_webshop_block_types_sql = $this->get_app_webshop_block_types_sql();
        $blog_referral_click_condition = $this->get_blog_referral_click_condition_sql('`track_links`');

        $result = database()->query("SELECT
            DATE(`track_links`.`datetime`) AS `date_key`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND `track_links`.`link_id` IS NOT NULL AND `track_links`.`biolink_block_id` IS NULL AND `links`.`type` = 'biolink' THEN 1 ELSE 0 END) AS `app_visits`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND `biolinks_blocks`.`type` IN ({$app_webshop_block_types_sql}) THEN 1 ELSE 0 END) AS `app_shop_clicks`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND {$blog_referral_click_condition} THEN 1 ELSE 0 END) AS `blog_clicks`
            FROM `track_links`
            LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
              AND `track_links`.`user_id` = {$user_id}
            GROUP BY DATE(`track_links`.`datetime`)");

        while($row = $result->fetch_assoc()) {
            $date_key = (string) ($row['date_key'] ?? '');

            if($date_key === '' || !isset($date_index[$date_key])) {
                continue;
            }

            $index = $date_index[$date_key];
            $app_visits[$index] = (int) ($row['app_visits'] ?? 0);
            $app_shop_clicks[$index] = (int) ($row['app_shop_clicks'] ?? 0);
            $blog_clicks[$index] = (int) ($row['blog_clicks'] ?? 0);
        }

        if($this->has_funnel_events_table()) {
            $funnel_result = database()->query("SELECT
                DATE(`datetime`) AS `date_key`,
                COUNT(*) AS `total`
            FROM `funnel_events`
            WHERE `user_id` = {$user_id}
              AND `event_type` = 'submit_success'
              AND `datetime` >= '{$period_start_datetime}'
            GROUP BY DATE(`datetime`)");

            while($funnel_row = $funnel_result->fetch_assoc()) {
                $date_key = (string) ($funnel_row['date_key'] ?? '');

                if($date_key === '' || !isset($date_index[$date_key])) {
                    continue;
                }

                $index = $date_index[$date_key];
                $funnel_registrations[$index] = (int) ($funnel_row['total'] ?? 0);
            }
        }

        return [
            'labels' => $labels,
            'app_visits' => $app_visits,
            'app_shop_clicks' => $app_shop_clicks,
            'blog_clicks' => $blog_clicks,
            'funnel_registrations' => $funnel_registrations,
        ];
    }

    private function get_country_signal_matrix_payload(int $user_id, int $period_days, string $period_key): array {
        $period_start_datetime = $this->get_period_start_datetime($period_days);
        $app_webshop_block_types_sql = $this->get_app_webshop_block_types_sql();
        $blog_referral_click_condition = $this->get_blog_referral_click_condition_sql('`track_links`');
        $rows_map = [];

        $clicks_result = database()->query("SELECT
            UPPER(TRIM(COALESCE(`track_links`.`country_code`, ''))) AS `country_code`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND `track_links`.`link_id` IS NOT NULL AND `track_links`.`biolink_block_id` IS NULL AND `links`.`type` = 'biolink' THEN 1 ELSE 0 END) AS `app_visits`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND `biolinks_blocks`.`type` IN ({$app_webshop_block_types_sql}) THEN 1 ELSE 0 END) AS `app_shop_clicks`,
            SUM(CASE WHEN `track_links`.`is_unique` = 1 AND {$blog_referral_click_condition} THEN 1 ELSE 0 END) AS `blog_clicks`
        FROM `track_links`
        LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id`
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `track_links`.`user_id` = {$user_id}
          AND `track_links`.`datetime` >= '{$period_start_datetime}'
        GROUP BY `country_code`");

        while($row = $clicks_result->fetch_object()) {
            $country_code = $this->get_country_table_key($row->country_code ?? '');

            $rows_map[$country_code] = [
                'country_code' => $country_code === '__unknown__' ? '' : $country_code,
                'country_name' => $this->get_country_table_name($country_code),
                'app_visits' => (int) ($row->app_visits ?? 0),
                'app_shop_clicks' => (int) ($row->app_shop_clicks ?? 0),
                'blog_clicks' => (int) ($row->blog_clicks ?? 0),
                'funnel_registrations' => 0,
            ];
        }

        if($this->has_funnel_events_table()) {
            $funnel_result = database()->query("SELECT
                UPPER(TRIM(COALESCE(`country_code`, ''))) AS `country_code`,
                COUNT(*) AS `total`
            FROM `funnel_events`
            WHERE `user_id` = {$user_id}
              AND `event_type` = 'submit_success'
              AND `datetime` >= '{$period_start_datetime}'
            GROUP BY `country_code`");

            while($row = $funnel_result->fetch_object()) {
                $country_code = $this->get_country_table_key($row->country_code ?? '');

                if(!isset($rows_map[$country_code])) {
                    $rows_map[$country_code] = [
                        'country_code' => $country_code === '__unknown__' ? '' : $country_code,
                        'country_name' => $this->get_country_table_name($country_code),
                        'app_visits' => 0,
                        'app_shop_clicks' => 0,
                        'blog_clicks' => 0,
                        'funnel_registrations' => 0,
                    ];
                }

                $rows_map[$country_code]['funnel_registrations'] = (int) ($row->total ?? 0);
            }
        }

        $rows = array_values(array_filter($rows_map, static function(array $row) {
            return ((int) ($row['app_visits'] ?? 0)
                + (int) ($row['app_shop_clicks'] ?? 0)
                + (int) ($row['blog_clicks'] ?? 0)
                + (int) ($row['funnel_registrations'] ?? 0)) > 0;
        }));

        usort($rows, static function(array $a, array $b) {
            $total_a = (int) ($a['app_visits'] ?? 0) + (int) ($a['app_shop_clicks'] ?? 0) + (int) ($a['blog_clicks'] ?? 0) + (int) ($a['funnel_registrations'] ?? 0);
            $total_b = (int) ($b['app_visits'] ?? 0) + (int) ($b['app_shop_clicks'] ?? 0) + (int) ($b['blog_clicks'] ?? 0) + (int) ($b['funnel_registrations'] ?? 0);

            return ($total_b <=> $total_a)
                ?: (($b['app_shop_clicks'] ?? 0) <=> ($a['app_shop_clicks'] ?? 0))
                ?: (($b['blog_clicks'] ?? 0) <=> ($a['blog_clicks'] ?? 0))
                ?: (($a['country_name'] ?? '') <=> ($b['country_name'] ?? ''));
        });

        return [
            'period_key' => $period_key,
            'period_days' => $period_days,
            'rows' => $rows,
            'totals' => [
                'app_visits' => array_sum(array_column($rows, 'app_visits')),
                'app_shop_clicks' => array_sum(array_column($rows, 'app_shop_clicks')),
                'blog_clicks' => array_sum(array_column($rows, 'blog_clicks')),
                'funnel_registrations' => array_sum(array_column($rows, 'funnel_registrations')),
            ],
        ];
    }

    private function get_country_signal_matrix_periods_payload(int $user_id): array {
        $payload = [];

        foreach(['1d' => 1, '7d' => 7, '30d' => 30, '90d' => 90] as $period_key => $period_days) {
            $payload[$period_key] = $this->get_country_signal_matrix_payload($user_id, $period_days, $period_key);
        }

        return $payload;
    }

    private function get_growth_metrics(int $current, int $previous): array {
        $difference = $current - $previous;
        $growth_percent = null;

        if($previous > 0) {
            $growth_percent = round(($difference / $previous) * 100, 1);
        } elseif($current === 0) {
            $growth_percent = 0.0;
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => $difference,
            'growth_percent' => $growth_percent,
        ];
    }

    private function clamp_score(float $value): int {
        return (int) max(0, min(100, round($value)));
    }

    private function get_scores(array $row): array {
        $total_clicks = (int) ($row['clicks_total_period'] ?? 0);
        $shop_clicks = (int) ($row['forever_shop_clicks_period'] ?? 0);
        $registration_clicks = (int) ($row['forever_registration_clicks_period'] ?? 0);
        $app_signal_score = (int) ($row['app_signal_score'] ?? 0);
        $whatsapp_contacts = (int) ($row['app_whatsapp_contacts_period'] ?? 0);
        $product_clicks = (int) ($row['app_product_clicks_period'] ?? 0);
        $funnel_registrations = (int) ($row['app_funnel_registrations_period'] ?? 0);
        $growth = $row['growth_percent'];
        $shop_share = $total_clicks > 0 ? (($shop_clicks / $total_clicks) * 100) : 0;
        $registration_rate = $shop_clicks > 0 ? (($registration_clicks / $shop_clicks) * 100) : 0;

        $performance_score = $this->clamp_score(min(34, $shop_clicks * 2.1) + min(22, $registration_clicks * 6) + min(20, $app_signal_score * 1.15) + min(24, $total_clicks * 0.38));
        $momentum_score = $this->clamp_score($growth === null ? ($shop_clicks > 0 ? 58 : 0) : 50 + ($growth * 1.1));
        $conversion_score = $this->clamp_score(($shop_share * 0.55) + ($registration_rate * 0.9));

        $risk_score = 0;
        if($growth !== null && $growth <= -20) $risk_score += 35;
        if((int) ($row['previous_forever_shop_clicks_period'] ?? 0) > 0 && $shop_clicks === 0) $risk_score += 35;
        if($total_clicks === 0 && (int) ($row['forever_shop_clicks_90d'] ?? 0) > 0) $risk_score += 20;
        $risk_score = $this->clamp_score($risk_score);

        $opportunity_score = 0;
        if($total_clicks >= 20 && $shop_share < 25) $opportunity_score += 35;
        if($shop_clicks >= 10 && $registration_clicks === 0) $opportunity_score += 20;
        if($growth !== null && $growth > 0 && $registration_rate < 10) $opportunity_score += 15;
        if(($whatsapp_contacts + $product_clicks + ($funnel_registrations * 2)) >= 10 && $shop_clicks < 5) $opportunity_score += 18;
        $opportunity_score = $this->clamp_score($opportunity_score);

        $leader_os_score = $this->clamp_score(
            ($performance_score * 0.35)
            + ($momentum_score * 0.2)
            + ($conversion_score * 0.2)
            + ((100 - $risk_score) * 0.1)
            + ($opportunity_score * 0.15)
        );

        return [
            'performance_score' => $performance_score,
            'momentum_score' => $momentum_score,
            'conversion_score' => $conversion_score,
            'risk_score' => $risk_score,
            'opportunity_score' => $opportunity_score,
            'leader_os_score' => $leader_os_score,
            'shop_share_percent' => round($shop_share, 1),
            'registration_rate_percent' => round($registration_rate, 1),
        ];
    }

    private function get_status_payload(array $row): array {
        $qualified = (int) ($row['forever_shop_clicks_90d'] ?? 0) >= 15;
        $growth = $row['growth_percent'] ?? null;
        $current_shop_clicks = (int) ($row['forever_shop_clicks_period'] ?? 0);
        $previous_shop_clicks = (int) ($row['previous_forever_shop_clicks_period'] ?? 0);
        $total_clicks = (int) ($row['clicks_total_period'] ?? 0);
        $opportunity_score = (int) ($row['opportunity_score'] ?? 0);
        $risk_score = (int) ($row['risk_score'] ?? 0);

        $status_key = 'stable';
        $status_label = l('admin_leader_operating_system.status.stable');
        $status_class = 'secondary';

        if($total_clicks === 0 && (int) ($row['forever_shop_clicks_90d'] ?? 0) === 0) {
            $status_key = 'inactive';
            $status_label = l('admin_leader_operating_system.status.inactive');
            $status_class = 'dark';
        } elseif($risk_score >= 55 || ($previous_shop_clicks > 0 && $current_shop_clicks === 0) || ($growth !== null && $growth <= -20)) {
            $status_key = 'risk';
            $status_label = l('admin_leader_operating_system.status.risk');
            $status_class = 'warning';
        } elseif($opportunity_score >= 60 && $total_clicks >= 20) {
            $status_key = 'high_potential';
            $status_label = l('admin_leader_operating_system.status.high_potential');
            $status_class = 'info';
        } elseif(($growth !== null && $growth >= 20) || $current_shop_clicks >= 12) {
            $status_key = 'rising';
            $status_label = l('admin_leader_operating_system.status.rising');
            $status_class = 'success';
        }

        return [
            'qualified' => $qualified,
            'status_key' => $status_key,
            'status_label' => $status_label,
            'status_class' => $status_class,
        ];
    }

    /* Custom code: FC-2026-03-31: Phase 6 stronger risk scoring with privacy-safe fraud signals */
    private function blend_period_payload_with_fraud(array $payload, array $fraud_intelligence): array {
        $base_risk_score = (int) ($payload['risk_score'] ?? 0);
        $fraud_score = (int) ($fraud_intelligence['score'] ?? 0);
        $blended_risk_score = $this->clamp_score(($base_risk_score * 0.65) + ($fraud_score * 0.35));

        $payload['risk_score_base'] = $base_risk_score;
        $payload['fraud_score'] = $fraud_score;
        $payload['risk_score'] = $blended_risk_score;
        $payload['leader_os_score'] = $this->clamp_score(
            ((int) ($payload['performance_score'] ?? 0) * 0.35)
            + ((int) ($payload['momentum_score'] ?? 0) * 0.2)
            + ((int) ($payload['conversion_score'] ?? 0) * 0.2)
            + ((100 - $blended_risk_score) * 0.1)
            + ((int) ($payload['opportunity_score'] ?? 0) * 0.15)
        );

        return array_merge($payload, $this->get_status_payload($payload));
    }

    private function build_fraud_signal_item(string $level_key, string $label, string $text, string $action, int $points): array {
        $class_map = [
            'high' => 'status-warning',
            'watch' => 'status-info',
            'stable' => 'status-success',
        ];

        return [
            'level_key' => $level_key,
            'level_label' => l('admin_leader_operating_system.leader.fraud_level.' . $level_key),
            'class' => $class_map[$level_key] ?? 'status-dark',
            'label' => $label,
            'text' => $text,
            'action' => $action,
            'points' => $points,
        ];
    }

    private function persist_fraud_cluster_summary(int $user_id, string $period_key, array $fraud_intelligence): void {
        $type = 'leader_os_fraud_cluster';
        $today_start_datetime = (new \DateTime())->format('Y-m-d 00:00:00');
        $period_key_sql = database()->real_escape_string($period_key);

        database()->query("DELETE FROM `data` WHERE `user_id` = {$user_id} AND `type` = '{$type}' AND `datetime` >= '{$today_start_datetime}' AND JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.period_key')) = '{$period_key_sql}'");

        if((int) ($fraud_intelligence['clusters_total'] ?? 0) === 0) {
            return;
        }

        db()->insert('data', [
            'user_id' => $user_id,
            'type' => $type,
            'data' => json_encode([
                'period_key' => $period_key,
                'score' => (int) ($fraud_intelligence['score'] ?? 0),
                'level_key' => (string) ($fraud_intelligence['level_key'] ?? 'stable'),
                'clusters_total' => (int) ($fraud_intelligence['clusters_total'] ?? 0),
                'top_concern' => (string) ($fraud_intelligence['top_concern'] ?? ''),
                'retention_days' => fc_get_los_fraud_summary_retention_days(),
                'clusters' => array_map(static function($cluster) {
                    return [
                        'score' => (int) ($cluster['score'] ?? 0),
                        'label' => (string) ($cluster['label'] ?? ''),
                        'text' => (string) ($cluster['text'] ?? ''),
                        'signature' => (string) ($cluster['signature'] ?? ''),
                        'first_datetime' => (string) ($cluster['first_datetime'] ?? ''),
                        'last_datetime' => (string) ($cluster['last_datetime'] ?? ''),
                    ];
                }, array_slice($fraud_intelligence['clusters'] ?? [], 0, 3)),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'datetime' => get_date(),
        ]);
    }

    private function get_fraud_intelligence_payload(int $user_id, string $period_key): array {
        if(function_exists('fc_ensure_forever_click_integrity_tables')) {
            fc_ensure_forever_click_integrity_tables();
        }

        $period_days = min($this->get_period_days($period_key), function_exists('fc_get_forever_click_integrity_retention_days') ? fc_get_forever_click_integrity_retention_days() : 30);
        $period_start_datetime = $this->get_period_start_datetime($period_days);
        $clusters = [];
        $recent_attempts = [];
        $blocked_attempts_total = 0;
        $reason_buckets = [];
        $target_buckets = [];

        $accepted_clicks_total = (int) (db()
            ->where('user_id', $user_id)
            ->where('accepted_datetime', $period_start_datetime, '>=')
            ->getValue('forever_click_integrity_accepts', 'COUNT(`integrity_accept_id`)') ?? 0);

        $suspicious_rows = db()
            ->where('user_id', $user_id)
            ->where('datetime', $period_start_datetime, '>=')
            ->orderBy('datetime', 'DESC')
            ->get('forever_click_integrity_suspicious') ?? [];

        foreach(array_slice($suspicious_rows, 0, 15) as $row) {
            $recent_attempts[] = [
                'datetime' => (string) ($row->datetime ?? ''),
                'reason_title' => (string) ($row->reason_title ?? ''),
                'reason_text' => (string) ($row->reason_text ?? ''),
                'target_label' => (string) ($row->target_label ?? ''),
                'source_type' => (string) ($row->source_type ?? ''),
                'ip_address' => (string) ($row->ip_address ?? ''),
            ];
        }

        $grouped = [];
        foreach($suspicious_rows as $row) {
            $blocked_attempts_total++;
            $group_key = ($row->reason_key ?? 'unknown') . '|' . ($row->target_signature ?? 'target');
            $reason_label = trim((string) ($row->reason_title ?? l('admin_leader_operating_system.leader.fraud_none')));
            $target_label = trim((string) ($row->target_label ?? ''));

            if($reason_label !== '') {
                $reason_buckets[$reason_label] = ($reason_buckets[$reason_label] ?? 0) + 1;
            }

            if($target_label !== '') {
                $target_buckets[$target_label] = ($target_buckets[$target_label] ?? 0) + 1;
            }

            if(!isset($grouped[$group_key])) {
                $grouped[$group_key] = [
                    'label' => (string) ($row->reason_title ?? l('admin_leader_operating_system.leader.fraud_none')),
                    'text' => (string) ($row->reason_text ?? ''),
                    'action' => (string) ($row->reason_details ?? l('admin_leader_operating_system.leader.fraud_blocked_default_action')),
                    'events_total' => 0,
                    'visitors_total' => 0,
                    'funnels_total' => 0,
                    'countries' => [],
                    'targets' => [],
                    'identities' => [],
                    'first_datetime' => (string) ($row->datetime ?? ''),
                    'last_datetime' => (string) ($row->datetime ?? ''),
                    'signature' => substr((string) ($row->identity_hash ?? $row->network_hash ?? md5((string) ($row->ip_address ?? 'unknown'))), 0, 16),
                ];
            }

            $grouped[$group_key]['events_total']++;
            $grouped[$group_key]['targets'][(string) ($row->target_signature ?? '')] = true;
            $grouped[$group_key]['identities'][(string) ($row->identity_hash ?? $row->network_hash ?? $row->ip_address ?? uniqid('id_', true))] = true;

            if(!empty($row->country_code)) {
                $grouped[$group_key]['countries'][(string) $row->country_code] = true;
            }

            if((string) ($row->datetime ?? '') < $grouped[$group_key]['first_datetime']) {
                $grouped[$group_key]['first_datetime'] = (string) $row->datetime;
            }

            if((string) ($row->datetime ?? '') > $grouped[$group_key]['last_datetime']) {
                $grouped[$group_key]['last_datetime'] = (string) $row->datetime;
            }
        }

        foreach($grouped as $group) {
            $first_timestamp = strtotime((string) ($group['first_datetime'] ?? '')) ?: 0;
            $last_timestamp = strtotime((string) ($group['last_datetime'] ?? '')) ?: 0;
            $duration_minutes = $first_timestamp && $last_timestamp && $last_timestamp >= $first_timestamp ? max(1, (int) ceil(($last_timestamp - $first_timestamp) / 60)) : 1;
            $attempts_total = (int) ($group['events_total'] ?? 0);
            $targets_total = count($group['targets'] ?? []);
            $identities_total = count($group['identities'] ?? []);
            $countries_total = count($group['countries'] ?? []);

            $cluster_score = min(100, ($attempts_total * 18) + (max(0, $targets_total - 1) * 12) + (($duration_minutes <= 60 && $attempts_total >= 3) ? 14 : 0) + (($countries_total >= 2) ? 8 : 0));

            $clusters[] = [
                'signature' => (string) ($group['signature'] ?? ''),
                'score' => $this->clamp_score($cluster_score),
                'label' => (string) ($group['label'] ?? l('admin_leader_operating_system.leader.fraud_none')),
                'text' => (string) ($group['text'] ?? ''),
                'action' => (string) ($group['action'] ?? ''),
                'signals' => [],
                'events_total' => $attempts_total,
                'visitors_total' => $attempts_total,
                'funnels_total' => max(1, $targets_total),
                'submit_errors_total' => 0,
                'submit_success_total' => 0,
                'duration_minutes' => $duration_minutes,
                'first_datetime' => (string) ($group['first_datetime'] ?? ''),
                'last_datetime' => (string) ($group['last_datetime'] ?? ''),
            ];
        }

        usort($clusters, static function($a, $b) {
            return (($b['score'] ?? 0) <=> ($a['score'] ?? 0)) ?: (($b['events_total'] ?? 0) <=> ($a['events_total'] ?? 0));
        });
        arsort($reason_buckets);
        arsort($target_buckets);

        $fraud_score = $this->clamp_score(min(100, ($blocked_attempts_total * 9) + (count($clusters) * 6)));
        $level_key = 'stable';

        if($fraud_score >= 55) {
            $level_key = 'high';
        } elseif($fraud_score >= 25) {
            $level_key = 'watch';
        }

        return [
            'period_key' => $period_key,
            'score' => $fraud_score,
            'level_key' => $level_key,
            'level_label' => l('admin_leader_operating_system.leader.fraud_level.' . $level_key),
            'level_class' => $this->build_fraud_signal_item($level_key, '', '', '', 0)['class'],
            'clusters_total' => count($clusters),
            'top_concern' => $clusters[0]['label'] ?? l('admin_leader_operating_system.leader.fraud_none'),
            'retention_days' => function_exists('fc_get_forever_click_integrity_retention_days') ? fc_get_forever_click_integrity_retention_days() : 30,
            'clusters' => array_slice($clusters, 0, 8),
            'blocked_attempts_total' => $blocked_attempts_total,
            'accepted_clicks_total' => $accepted_clicks_total,
            'blocked_vs_accepted_ratio' => $accepted_clicks_total > 0 ? round(($blocked_attempts_total / max(1, $accepted_clicks_total)) * 100, 1) : ($blocked_attempts_total > 0 ? 100.0 : 0.0),
            'top_reasons' => array_map(static function($label, $total) {
                return ['label' => (string) $label, 'total' => (int) $total];
            }, array_keys(array_slice($reason_buckets, 0, 5, true)), array_values(array_slice($reason_buckets, 0, 5, true))),
            'top_targets' => array_map(static function($label, $total) {
                return ['label' => (string) $label, 'total' => (int) $total];
            }, array_keys(array_slice($target_buckets, 0, 5, true)), array_values(array_slice($target_buckets, 0, 5, true))),
            'recent_attempts' => $recent_attempts,
        ];
    }
    /* /Custom code: FC-2026-03-31 */

    private function get_fraud_recommended_action_payload(array $fraud_intelligence): array {
        $score = (int) ($fraud_intelligence['score'] ?? 0);
        $blocked_attempts_total = (int) ($fraud_intelligence['blocked_attempts_total'] ?? 0);
        $clusters_total = (int) ($fraud_intelligence['clusters_total'] ?? 0);
        $ratio = (float) ($fraud_intelligence['blocked_vs_accepted_ratio'] ?? 0);

        if($score >= 55 || $blocked_attempts_total >= 5) {
            return [
                'label' => 'Potrebna hitna provjera',
                'class' => 'status-warning',
                'text' => 'Provjeri izvore prometa, usporedi suspicious razloge i privremeno fokusiraj coaching na kvalitetu leadova umjesto na volumen klikova.',
            ];
        }

        if($clusters_total >= 2 || $ratio >= 35) {
            return [
                'label' => 'Pojačan nadzor',
                'class' => 'status-info',
                'text' => 'Prati ponavljaju li se isti targeti ili isti patterni kroz naredne dane i provjeri dolazi li promet iz realnih kanala koje suradnik stvarno koristi.',
            ];
        }

        return [
            'label' => 'Signal je pod kontrolom',
            'class' => 'status-success',
            'text' => 'Za sada nije potreban poseban zahvat. Dovoljno je pratiti dashboard i reagirati ako blocked attempts ili clusteri krenu rasti.',
        ];
    }

    private function get_primary_breakdown_label(array $breakdown, string $fallback = '-'): string {
        return !empty($breakdown[0]['label']) ? (string) $breakdown[0]['label'] : $fallback;
    }

    private function get_funnel_payload(int $user_id, string $period_start_datetime): array {
        $funnel_blocks = [];
        $funnel_blocks_result = database()->query("SELECT `biolink_block_id`, `settings`
            FROM `biolinks_blocks`
            WHERE `user_id` = {$user_id}
              AND `type` = 'lead_funnel'");

        while($row = $funnel_blocks_result->fetch_object()) {
            $settings = json_decode($row->settings ?? '{}');
            $funnel_blocks[(int) $row->biolink_block_id] = [
                'name' => trim((string) ($settings->name ?? l('link.biolink.blocks.lead_funnel'))),
                'objective' => trim((string) ($settings->thank_you_type ?? $settings->open_mode ?? '')),
            ];
        }

        if(empty($funnel_blocks)) {
            return [
                'total_funnels' => 0,
                'active_funnels' => 0,
                'unique_clicks' => 0,
                'total_leads' => 0,
                'conversion_rate' => 0.0,
                'top_funnel_name' => '-',
                'top_funnel_objective' => '-',
            ];
        }

        $funnel_ids_sql = implode(',', array_map('intval', array_keys($funnel_blocks)));
        $clicks_per_funnel = [];
        $clicks_result = database()->query("SELECT `biolink_block_id`, SUM(`is_unique`) AS `unique_clicks`
            FROM `track_links`
            WHERE `user_id` = {$user_id}
              AND `biolink_block_id` IN ({$funnel_ids_sql})
              AND `datetime` >= '{$period_start_datetime}'
            GROUP BY `biolink_block_id`");

        while($row = $clicks_result->fetch_object()) {
            $clicks_per_funnel[(int) $row->biolink_block_id] = (int) ($row->unique_clicks ?? 0);
        }

        $leads_per_funnel = [];
        $leads_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total_leads`
            FROM `data`
            WHERE `user_id` = {$user_id}
              AND `type` = 'lead_funnel'
              AND `biolink_block_id` IN ({$funnel_ids_sql})
              AND `datetime` >= '{$period_start_datetime}'
            GROUP BY `biolink_block_id`");

        while($row = $leads_result->fetch_object()) {
            $leads_per_funnel[(int) $row->biolink_block_id] = (int) ($row->total_leads ?? 0);
        }

        $active_funnels = 0;
        $unique_clicks = 0;
        $total_leads = 0;
        $top_funnel_name = '-';
        $top_funnel_objective = '-';
        $top_funnel_score = -1;

        foreach($funnel_blocks as $biolink_block_id => $funnel_block) {
            $funnel_clicks = $clicks_per_funnel[$biolink_block_id] ?? 0;
            $funnel_leads = $leads_per_funnel[$biolink_block_id] ?? 0;

            if($funnel_clicks > 0 || $funnel_leads > 0) {
                $active_funnels++;
            }

            $unique_clicks += $funnel_clicks;
            $total_leads += $funnel_leads;

            $funnel_score = ($funnel_leads * 1000) + $funnel_clicks;
            if($funnel_score > $top_funnel_score) {
                $top_funnel_score = $funnel_score;
                $top_funnel_name = $funnel_block['name'] !== '' ? $funnel_block['name'] : l('link.biolink.blocks.lead_funnel');
                $top_funnel_objective = $funnel_block['objective'] !== '' ? $funnel_block['objective'] : '-';
            }
        }

        return [
            'total_funnels' => count($funnel_blocks),
            'active_funnels' => $active_funnels,
            'unique_clicks' => $unique_clicks,
            'total_leads' => $total_leads,
            'conversion_rate' => $unique_clicks ? round(($total_leads / $unique_clicks) * 100, 1) : 0.0,
            'top_funnel_name' => $top_funnel_name,
            'top_funnel_objective' => $top_funnel_objective,
        ];
    }

    /* Custom code: FC-2026-03-31: Leader OS app structure payload for AI analysis */
    private function get_app_structure_payload(int $user_id): array {
        $apps_result = database()->query("SELECT `link_id`, `url` FROM `links` WHERE `user_id` = {$user_id} AND `type` = 'biolink'");

        $apps = [];
        $link_ids = [];

        while($row = $apps_result->fetch_object()) {
            $link_id = (int) ($row->link_id ?? 0);

            if(!$link_id) {
                continue;
            }

            $apps[$link_id] = [
                'url' => (string) ($row->url ?? ''),
                'total_blocks' => 0,
                'forever_blocks' => 0,
                'funnel_blocks' => 0,
                'social_blocks' => 0,
                'content_blocks' => 0,
                'visual_blocks' => 0,
                'cta_blocks' => 0,
                'trust_blocks' => 0,
                'contact_blocks' => 0,
                'review_blocks' => 0,
                'ordered_blocks' => [],
            ];
            $link_ids[] = $link_id;
        }

        if(empty($link_ids)) {
            return [
                'total_apps' => 0,
                'top_app_url' => '-',
                'top_app_total_blocks' => 0,
                'block_mix' => [],
                'priority_blocks' => [],
                'composition_score' => 0,
                'composition_key' => 'critical',
                'composition_label' => l('admin_leader_operating_system.leader.composition_health.critical'),
                'composition_class' => 'status-warning',
                'cta_audit' => [
                    'score' => 0,
                    'state_key' => 'missing',
                    'state_label' => l('admin_leader_operating_system.leader.design_state.missing'),
                    'summary' => l('admin_leader_operating_system.leader.cta_audit_empty'),
                    'first_cta_position' => null,
                ],
                'trust_audit' => [
                    'score' => 0,
                    'state_key' => 'weak',
                    'state_label' => l('admin_leader_operating_system.leader.design_state.weak'),
                    'summary' => l('admin_leader_operating_system.leader.trust_audit_empty'),
                ],
                'content_audit' => [
                    'score' => 0,
                    'state_key' => 'thin',
                    'state_label' => l('admin_leader_operating_system.leader.design_state.thin'),
                    'summary' => l('admin_leader_operating_system.leader.content_audit_empty'),
                ],
                'page_review' => [
                    'has_preview' => false,
                    'public_url' => null,
                    'preview_title' => '-',
                    'checklist' => [],
                ],
                'composition_findings' => [],
                'top_apps' => [],
            ];
        }

        $block_counts = [];
        $priority_block_types = [
            'socials',
            'heading',
            'paragraph',
            'image',
            'avatar',
            'lead_funnel',
            'link_forever_shop',
            'link_forever_product',
            'link_discount',
            'link_app_switcher',
            'link_save_contact',
            'contact_collector',
            'email_collector',
            'phone_collector',
            'review',
            'youtube',
            'tiktok_video',
        ];
        $priority_blocks = array_fill_keys($priority_block_types, 0);
        $cta_types = ['link_forever_shop', 'link_forever_product', 'link_discount', 'lead_funnel', 'link_save_contact', 'contact_collector', 'email_collector', 'phone_collector'];
        $visual_types = ['image', 'avatar', 'youtube', 'tiktok_video'];
        $content_types = ['heading', 'paragraph'];
        $contact_types = ['link_save_contact', 'contact_collector', 'email_collector', 'phone_collector'];
        $trust_types = array_values(array_unique(array_merge($visual_types, ['socials', 'review'], $contact_types)));

        $link_ids_sql = implode(',', array_map('intval', $link_ids));
        $blocks_result = database()->query("SELECT `link_id`, `type`, `order` FROM `biolinks_blocks` WHERE `user_id` = {$user_id} AND `link_id` IN ({$link_ids_sql}) AND `is_enabled` = 1 ORDER BY `link_id` ASC, `order` ASC, `biolink_block_id` ASC");

        while($row = $blocks_result->fetch_object()) {
            $link_id = (int) ($row->link_id ?? 0);
            $type = (string) ($row->type ?? '');
            $order = (int) ($row->order ?? 0);

            if($type === '' || !isset($apps[$link_id])) {
                continue;
            }

            $apps[$link_id]['total_blocks']++;
            $apps[$link_id]['ordered_blocks'][] = [
                'type' => $type,
                'order' => $order,
            ];
            $block_counts[$type] = ($block_counts[$type] ?? 0) + 1;

            if(isset($priority_blocks[$type])) {
                $priority_blocks[$type]++;
            }

            if(in_array($type, $content_types, true)) {
                $apps[$link_id]['content_blocks']++;
            }

            if(in_array($type, ['socials'], true)) {
                $apps[$link_id]['social_blocks']++;
            }

            if(in_array($type, $visual_types, true)) {
                $apps[$link_id]['visual_blocks']++;
            }

            if(in_array($type, $cta_types, true)) {
                $apps[$link_id]['cta_blocks']++;
            }

            if(in_array($type, $trust_types, true)) {
                $apps[$link_id]['trust_blocks']++;
            }

            if(in_array($type, $contact_types, true)) {
                $apps[$link_id]['contact_blocks']++;
            }

            if($type === 'review') {
                $apps[$link_id]['review_blocks']++;
            }

            if($type === 'lead_funnel') {
                $apps[$link_id]['funnel_blocks']++;
            }

            if(str_starts_with($type, 'link_forever') || in_array($type, ['link_discount', 'link_app_switcher'], true)) {
                $apps[$link_id]['forever_blocks']++;
            }
        }

        uasort($block_counts, static function($a, $b) {
            return $b <=> $a;
        });

        uasort($apps, static function($a, $b) {
            return ($b['total_blocks'] <=> $a['total_blocks']) ?: ($b['forever_blocks'] <=> $a['forever_blocks']);
        });

        $top_app_id = (int) array_key_first($apps);
        $top_app = reset($apps);
        $top_app_url = $top_app['url'] ?? '-';
        $top_app_total_blocks = (int) ($top_app['total_blocks'] ?? 0);
        $top_app_public_url = $top_app_url !== '' && $top_app_url !== '-' ? url($top_app_url) : null;

        $block_mix = [];
        foreach(array_slice($block_counts, 0, 8, true) as $type => $total) {
            $block_mix[] = [
                'type' => $type,
                'total' => (int) $total,
            ];
        }

        $forever_entry_points = (int) (($priority_blocks['link_forever_shop'] ?? 0) + ($priority_blocks['link_forever_product'] ?? 0) + ($priority_blocks['link_discount'] ?? 0) + ($priority_blocks['link_forever_webshop_reg'] ?? 0));
        $content_stack = (int) (($priority_blocks['heading'] ?? 0) + ($priority_blocks['paragraph'] ?? 0));
        $visual_stack = (int) (($priority_blocks['image'] ?? 0) + ($priority_blocks['avatar'] ?? 0) + ($priority_blocks['youtube'] ?? 0) + ($priority_blocks['tiktok_video'] ?? 0));
        $social_stack = (int) ($priority_blocks['socials'] ?? 0);
        $funnel_stack = (int) ($priority_blocks['lead_funnel'] ?? 0);

        $health_score = 100;
        if(count($apps) === 0) $health_score -= 45;
        if($top_app_total_blocks < 4) $health_score -= 20;
        if($content_stack < 2) $health_score -= 15;
        if($visual_stack < 1) $health_score -= 10;
        if($social_stack < 1) $health_score -= 10;
        if($funnel_stack < 1) $health_score -= 12;
        if($forever_entry_points < 2) $health_score -= 12;
        $health_score = max(0, min(100, $health_score));

        $health_key = 'strong';
        $health_class = 'status-success';

        if($health_score < 30) {
            $health_key = 'critical';
            $health_class = 'status-warning';
        } elseif($health_score < 55) {
            $health_key = 'fragile';
            $health_class = 'status-warning';
        } elseif($health_score < 75) {
            $health_key = 'workable';
            $health_class = 'status-info';
        }

        $diagnostics = [];

        if(count($apps) === 0) {
            $diagnostics[] = [
                'label' => l('admin_leader_operating_system.leader.structure_diag.no_apps.label'),
                'state_label' => l('admin_leader_operating_system.leader.structure_diag.state.critical'),
                'action' => l('admin_leader_operating_system.leader.structure_diag.no_apps.action'),
                'class' => 'status-warning',
            ];
        }

        if($top_app_total_blocks < 4) {
            $diagnostics[] = [
                'label' => l('admin_leader_operating_system.leader.structure_diag.thin_primary.label'),
                'state_label' => l('admin_leader_operating_system.leader.structure_diag.state.needs_work'),
                'action' => l('admin_leader_operating_system.leader.structure_diag.thin_primary.action'),
                'class' => 'status-warning',
            ];
        }

        if($content_stack < 2) {
            $diagnostics[] = [
                'label' => l('admin_leader_operating_system.leader.structure_diag.content_stack.label'),
                'state_label' => l('admin_leader_operating_system.leader.structure_diag.state.needs_work'),
                'action' => l('admin_leader_operating_system.leader.structure_diag.content_stack.action'),
                'class' => 'status-info',
            ];
        }

        if($forever_entry_points < 2) {
            $diagnostics[] = [
                'label' => l('admin_leader_operating_system.leader.structure_diag.forever_cta.label'),
                'state_label' => l('admin_leader_operating_system.leader.structure_diag.state.needs_work'),
                'action' => l('admin_leader_operating_system.leader.structure_diag.forever_cta.action'),
                'class' => 'status-info',
            ];
        }

        $has_direct_sale_structure = $forever_entry_points >= 2 && ($social_stack >= 1 || $visual_stack >= 1);

        if($funnel_stack < 1 && !$has_direct_sale_structure) {
            $diagnostics[] = [
                'label' => l('admin_leader_operating_system.leader.structure_diag.funnel.label'),
                'state_label' => l('admin_leader_operating_system.leader.structure_diag.state.missing'),
                'action' => l('admin_leader_operating_system.leader.structure_diag.funnel.action'),
                'class' => 'status-dark',
            ];
        } elseif($funnel_stack < 1 && $has_direct_sale_structure) {
            $diagnostics[] = [
                'label' => l('admin_leader_operating_system.leader.structure_diag.funnel_optional.label'),
                'state_label' => l('admin_leader_operating_system.leader.structure_diag.state.healthy'),
                'action' => l('admin_leader_operating_system.leader.structure_diag.funnel_optional.action'),
                'class' => 'status-success',
            ];
        }

        if($social_stack < 1) {
            $diagnostics[] = [
                'label' => l('admin_leader_operating_system.leader.structure_diag.social_proof.label'),
                'state_label' => l('admin_leader_operating_system.leader.structure_diag.state.missing'),
                'action' => l('admin_leader_operating_system.leader.structure_diag.social_proof.action'),
                'class' => 'status-dark',
            ];
        }

        if($visual_stack < 1) {
            $diagnostics[] = [
                'label' => l('admin_leader_operating_system.leader.structure_diag.visuals.label'),
                'state_label' => l('admin_leader_operating_system.leader.structure_diag.state.missing'),
                'action' => l('admin_leader_operating_system.leader.structure_diag.visuals.action'),
                'class' => 'status-dark',
            ];
        }

        if(empty($diagnostics)) {
            $diagnostics[] = [
                'label' => l('admin_leader_operating_system.leader.structure_diag.healthy.label'),
                'state_label' => l('admin_leader_operating_system.leader.structure_diag.state.healthy'),
                'action' => l('admin_leader_operating_system.leader.structure_diag.healthy.action'),
                'class' => 'status-success',
            ];
        }

        /* Custom code: FC-2026-03-31: Phase 7 design and behavior intelligence */
        $top_app_blocks = $top_app['ordered_blocks'] ?? [];
        $first_cta_position = null;
        $heading_before_cta = false;
        $paragraph_before_cta = false;
        $visual_before_cta = false;
        $social_before_cta = false;
        $intro_stack_total = 0;

        foreach($top_app_blocks as $index => $block) {
            $block_type = (string) ($block['type'] ?? '');
            $position = $index + 1;

            if($first_cta_position === null && in_array($block_type, $cta_types, true)) {
                $first_cta_position = $position;
                continue;
            }

            if($first_cta_position === null) {
                if($block_type === 'heading') {
                    $heading_before_cta = true;
                }

                if($block_type === 'paragraph') {
                    $paragraph_before_cta = true;
                }

                if(in_array($block_type, $visual_types, true)) {
                    $visual_before_cta = true;
                }

                if($block_type === 'socials') {
                    $social_before_cta = true;
                }

                if(in_array($block_type, ['avatar', 'heading', 'paragraph', 'socials'], true)) {
                    $intro_stack_total++;
                }
            }
        }

        $has_balanced_intro_stack = $intro_stack_total >= 3 && ($heading_before_cta || $paragraph_before_cta) && ($visual_before_cta || $social_before_cta);

        $cta_audit_score = 0;
        $cta_audit_state_key = 'missing';
        $cta_audit_summary = l('admin_leader_operating_system.leader.cta_audit_empty');

        if((int) ($top_app['cta_blocks'] ?? 0) > 0) {
            $cta_audit_score = 100;

            if($first_cta_position === 1) {
                $cta_audit_score -= 10;
            } elseif($has_balanced_intro_stack && $first_cta_position !== null && $first_cta_position <= 5) {
                $cta_audit_score -= 0;
            } elseif($first_cta_position !== null && $first_cta_position > 3) {
                $cta_audit_score -= min(45, ($first_cta_position - 3) * 10);
            }

            if(!$heading_before_cta && !$paragraph_before_cta) {
                $cta_audit_score -= 20;
            }

            if(!$visual_before_cta) {
                $cta_audit_score -= 10;
            }

            if((int) ($top_app['cta_blocks'] ?? 0) >= 4 && $top_app_total_blocks <= 8) {
                $cta_audit_score -= 10;
            }

            $cta_audit_score = $this->clamp_score($cta_audit_score);

            if($cta_audit_score >= 75) {
                $cta_audit_state_key = 'strong';
            } elseif($cta_audit_score >= 45) {
                $cta_audit_state_key = 'workable';
            } else {
                $cta_audit_state_key = 'weak';
            }

            if($has_balanced_intro_stack && $first_cta_position !== null && $first_cta_position <= 5) {
                $cta_audit_summary = sprintf(l('admin_leader_operating_system.leader.cta_audit_balanced_intro'), $first_cta_position);
            } elseif($first_cta_position !== null && $first_cta_position <= 3) {
                $cta_audit_summary = sprintf(l('admin_leader_operating_system.leader.cta_audit_prominent'), $first_cta_position);
            } elseif($first_cta_position !== null) {
                $cta_audit_summary = sprintf(l('admin_leader_operating_system.leader.cta_audit_delayed'), $first_cta_position);
            }
        }

        $trust_audit_score = 0;
        if((int) ($top_app['visual_blocks'] ?? 0) > 0) {
            $trust_audit_score += 35;
        }
        if((int) ($top_app['social_blocks'] ?? 0) > 0 || (int) ($top_app['contact_blocks'] ?? 0) > 0) {
            $trust_audit_score += 35;
        }
        if((int) ($top_app['review_blocks'] ?? 0) > 0) {
            $trust_audit_score += 15;
        }
        if((int) ($top_app['trust_blocks'] ?? 0) >= 3) {
            $trust_audit_score += 15;
        }
        $trust_audit_score = $this->clamp_score($trust_audit_score);
        $trust_audit_state_key = $trust_audit_score >= 70 ? 'strong' : ($trust_audit_score >= 40 ? 'workable' : 'weak');
        $trust_audit_summary = l('admin_leader_operating_system.leader.trust_audit_weak');

        if((int) ($top_app['visual_blocks'] ?? 0) > 0 && ((int) ($top_app['social_blocks'] ?? 0) > 0 || (int) ($top_app['contact_blocks'] ?? 0) > 0)) {
            $trust_audit_summary = l('admin_leader_operating_system.leader.trust_audit_strong');
        } elseif((int) ($top_app['visual_blocks'] ?? 0) > 0 || (int) ($top_app['social_blocks'] ?? 0) > 0 || (int) ($top_app['contact_blocks'] ?? 0) > 0) {
            $trust_audit_summary = l('admin_leader_operating_system.leader.trust_audit_partial');
        }

        $content_audit_score = 0;
        if($heading_before_cta && $paragraph_before_cta) {
            $content_audit_score += 35;
        } elseif($heading_before_cta || $paragraph_before_cta || (int) ($top_app['content_blocks'] ?? 0) > 0) {
            $content_audit_score += 18;
        }
        if($top_app_total_blocks >= 4 && $top_app_total_blocks <= 10) {
            $content_audit_score += 25;
        } elseif($top_app_total_blocks > 10 && $top_app_total_blocks <= 14) {
            $content_audit_score += 15;
        } elseif($top_app_total_blocks > 0) {
            $content_audit_score += 8;
        }
        if($has_balanced_intro_stack && $first_cta_position !== null && $first_cta_position <= 5) {
            $content_audit_score += 22;
        } elseif($first_cta_position !== null && $first_cta_position >= 2 && $first_cta_position <= 4) {
            $content_audit_score += 20;
        } elseif($first_cta_position === 1) {
            $content_audit_score += 10;
        } elseif($first_cta_position !== null) {
            $content_audit_score += 5;
        }
        if((int) ($top_app['funnel_blocks'] ?? 0) > 0 || (int) ($top_app['contact_blocks'] ?? 0) > 0) {
            $content_audit_score += 15;
        }
        $content_audit_score = $this->clamp_score($content_audit_score);
        $content_audit_state_key = $content_audit_score >= 70 ? 'clear' : ($content_audit_score >= 40 ? 'mixed' : 'thin');
        $content_audit_summary = l('admin_leader_operating_system.leader.content_audit_thin');

        if($content_audit_state_key === 'clear') {
            $content_audit_summary = l('admin_leader_operating_system.leader.content_audit_clear');
        } elseif($content_audit_state_key === 'mixed') {
            $content_audit_summary = l('admin_leader_operating_system.leader.content_audit_mixed');
        }

        $composition_score = $this->clamp_score(($health_score * 0.3) + ($cta_audit_score * 0.25) + ($trust_audit_score * 0.25) + ($content_audit_score * 0.2));
        $composition_key = 'strong';
        $composition_class = 'status-success';

        if($composition_score < 30) {
            $composition_key = 'critical';
            $composition_class = 'status-warning';
        } elseif($composition_score < 55) {
            $composition_key = 'fragile';
            $composition_class = 'status-warning';
        } elseif($composition_score < 75) {
            $composition_key = 'workable';
            $composition_class = 'status-info';
        }

        $composition_findings = [];

        if((int) ($top_app['cta_blocks'] ?? 0) === 0) {
            $composition_findings[] = [
                'label' => l('admin_leader_operating_system.leader.composition_finding.cta_missing.label'),
                'state_label' => l('admin_leader_operating_system.leader.design_state.missing'),
                'action' => l('admin_leader_operating_system.leader.composition_finding.cta_missing.action'),
                'class' => 'status-warning',
            ];
        } elseif($first_cta_position !== null && $first_cta_position > 4 && !$has_balanced_intro_stack) {
            $composition_findings[] = [
                'label' => l('admin_leader_operating_system.leader.composition_finding.cta_delayed.label'),
                'state_label' => l('admin_leader_operating_system.leader.design_state.workable'),
                'action' => sprintf(l('admin_leader_operating_system.leader.composition_finding.cta_delayed.action'), $first_cta_position),
                'class' => 'status-info',
            ];
        }

        if((int) ($top_app['visual_blocks'] ?? 0) < 1 || (((int) ($top_app['social_blocks'] ?? 0) + (int) ($top_app['contact_blocks'] ?? 0)) < 1)) {
            $composition_findings[] = [
                'label' => l('admin_leader_operating_system.leader.composition_finding.trust_gap.label'),
                'state_label' => l('admin_leader_operating_system.leader.design_state.weak'),
                'action' => l('admin_leader_operating_system.leader.composition_finding.trust_gap.action'),
                'class' => 'status-warning',
            ];
        }

        if(!$heading_before_cta || !$paragraph_before_cta) {
            $composition_findings[] = [
                'label' => l('admin_leader_operating_system.leader.composition_finding.content_intro.label'),
                'state_label' => l('admin_leader_operating_system.leader.design_state.thin'),
                'action' => l('admin_leader_operating_system.leader.composition_finding.content_intro.action'),
                'class' => 'status-info',
            ];
        }

        if(empty($composition_findings)) {
            $composition_findings[] = [
                'label' => l('admin_leader_operating_system.leader.composition_finding.healthy.label'),
                'state_label' => l('admin_leader_operating_system.leader.design_state.strong'),
                'action' => l('admin_leader_operating_system.leader.composition_finding.healthy.action'),
                'class' => 'status-success',
            ];
        }

        $page_review_checklist = [];
        if($top_app_public_url) {
            $page_review_checklist[] = [
                'label' => l('admin_leader_operating_system.leader.page_review_check.public_page'),
                'value' => l('admin_leader_operating_system.leader.page_review_yes'),
                'class' => 'status-success',
            ];
        }
        $page_review_checklist[] = [
            'label' => l('admin_leader_operating_system.leader.page_review_check.cta_position'),
            'value' => $first_cta_position ? sprintf(l('admin_leader_operating_system.leader.page_review_cta_position_value'), $first_cta_position) : l('admin_leader_operating_system.leader.page_review_cta_position_missing'),
            'class' => (($first_cta_position && $first_cta_position <= 3) || ($has_balanced_intro_stack && $first_cta_position && $first_cta_position <= 5)) ? 'status-success' : ($first_cta_position ? 'status-info' : 'status-warning'),
        ];
        $page_review_checklist[] = [
            'label' => l('admin_leader_operating_system.leader.page_review_check.trust'),
            'value' => $trust_audit_summary,
            'class' => $trust_audit_state_key === 'strong' ? 'status-success' : ($trust_audit_state_key === 'workable' ? 'status-info' : 'status-warning'),
        ];
        $page_review_checklist[] = [
            'label' => l('admin_leader_operating_system.leader.page_review_check.content_flow'),
            'value' => $content_audit_summary,
            'class' => $content_audit_state_key === 'clear' ? 'status-success' : ($content_audit_state_key === 'mixed' ? 'status-info' : 'status-warning'),
        ];

        $top_apps = [];
        foreach(array_slice($apps, 0, 3, true) as $link_id => $app) {
            $top_apps[] = [
                'link_id' => (int) $link_id,
                'public_url' => !empty($app['url']) ? url($app['url']) : null,
                'url' => (string) ($app['url'] ?? '-'),
                'total_blocks' => (int) ($app['total_blocks'] ?? 0),
                'cta_blocks' => (int) ($app['cta_blocks'] ?? 0),
                'trust_blocks' => (int) ($app['trust_blocks'] ?? 0),
            ];
        }
        /* /Custom code: FC-2026-03-31 */

        return [
            'total_apps' => count($apps),
            'top_app_id' => $top_app_id,
            'top_app_url' => $top_app_url !== '' ? $top_app_url : '-',
            'top_app_public_url' => $top_app_public_url,
            'top_app_total_blocks' => $top_app_total_blocks,
            'block_mix' => $block_mix,
            'priority_blocks' => $priority_blocks,
            'health_score' => $health_score,
            'health_key' => $health_key,
            'health_label' => l('admin_leader_operating_system.leader.structure_health.' . $health_key),
            'health_class' => $health_class,
            'content_stack_total' => $content_stack,
            'visual_stack_total' => $visual_stack,
            'social_stack_total' => $social_stack,
            'funnel_stack_total' => $funnel_stack,
            'forever_entry_points_total' => $forever_entry_points,
            'diagnostics' => $diagnostics,
            'composition_score' => $composition_score,
            'composition_key' => $composition_key,
            'composition_label' => l('admin_leader_operating_system.leader.composition_health.' . $composition_key),
            'composition_class' => $composition_class,
            'cta_audit' => [
                'score' => $cta_audit_score,
                'state_key' => $cta_audit_state_key,
                'state_label' => l('admin_leader_operating_system.leader.design_state.' . $cta_audit_state_key),
                'summary' => $cta_audit_summary,
                'first_cta_position' => $first_cta_position,
                'has_balanced_intro_stack' => $has_balanced_intro_stack,
            ],
            'trust_audit' => [
                'score' => $trust_audit_score,
                'state_key' => $trust_audit_state_key,
                'state_label' => l('admin_leader_operating_system.leader.design_state.' . $trust_audit_state_key),
                'summary' => $trust_audit_summary,
            ],
            'content_audit' => [
                'score' => $content_audit_score,
                'state_key' => $content_audit_state_key,
                'state_label' => l('admin_leader_operating_system.leader.design_state.' . $content_audit_state_key),
                'summary' => $content_audit_summary,
            ],
            'page_review' => [
                'has_preview' => (bool) $top_app_public_url,
                'public_url' => $top_app_public_url,
                'preview_title' => $top_app_url !== '' ? $top_app_url : '-',
                'checklist' => $page_review_checklist,
            ],
            'composition_findings' => $composition_findings,
            'top_apps' => $top_apps,
        ];
    }
    /* /Custom code: FC-2026-03-31 */

    private function get_next_step(array $payload): string {
        if(($payload['status_key'] ?? '') === 'risk') return l('admin_leader_operating_system.leader.next_step.risk');
        if(($payload['status_key'] ?? '') === 'high_potential') return l('admin_leader_operating_system.leader.next_step.opportunity');
        if(($payload['status_key'] ?? '') === 'rising') return l('admin_leader_operating_system.leader.next_step.rising');
        if((int) ($payload['forever_shop_clicks_period'] ?? 0) === 0) return l('admin_leader_operating_system.leader.next_step.reactivate');

        return l('admin_leader_operating_system.leader.next_step.stable');
    }

    /* Custom code: FC-2026-04-01: explain opportunity score with concrete improvement actions */
    private function get_opportunity_actions_payload(array $payload): array {
        $total_clicks = (int) ($payload['clicks_total_period'] ?? 0);
        $shop_clicks = (int) ($payload['forever_shop_clicks_period'] ?? 0);
        $registration_clicks = (int) ($payload['forever_registration_clicks_period'] ?? 0);
        $shop_share = (float) ($payload['shop_share_percent'] ?? 0);
        $registration_rate = (float) ($payload['registration_rate_percent'] ?? 0);
        $growth = $payload['growth_percent'] ?? null;
        $whatsapp_contacts = (int) ($payload['app_whatsapp_contacts_period'] ?? 0);
        $product_clicks = (int) ($payload['app_product_clicks_period'] ?? 0);
        $funnel_registrations = (int) ($payload['app_funnel_registrations_period'] ?? 0);
        $top_source = trim((string) ($payload['top_source_label'] ?? ''));
        $top_country = trim((string) ($payload['top_country_label'] ?? ''));

        $items = [];

        if($total_clicks >= 20 && $shop_share < 25) {
            $items[] = [
                'label' => l('admin_leader_operating_system.leader.opportunity_action.more_shop_share.label'),
                'text' => sprintf(
                    l('admin_leader_operating_system.leader.opportunity_action.more_shop_share.text'),
                    nr($total_clicks),
                    nr($shop_share)
                ),
                'actions' => [
                    l('admin_leader_operating_system.leader.opportunity_action.more_shop_share.step_1'),
                    $top_source !== '' && $top_source !== l('admin_index.biolink_qualified_watch.source.direct_share')
                        ? sprintf(l('admin_leader_operating_system.leader.opportunity_action.more_shop_share.step_2_source'), $top_source)
                        : l('admin_leader_operating_system.leader.opportunity_action.more_shop_share.step_2'),
                    l('admin_leader_operating_system.leader.opportunity_action.more_shop_share.step_3'),
                ],
            ];
        }

        if($shop_clicks >= 10 && $registration_clicks === 0) {
            $items[] = [
                'label' => l('admin_leader_operating_system.leader.opportunity_action.unlock_registrations.label'),
                'text' => sprintf(
                    l('admin_leader_operating_system.leader.opportunity_action.unlock_registrations.text'),
                    nr($shop_clicks)
                ),
                'actions' => [
                    l('admin_leader_operating_system.leader.opportunity_action.unlock_registrations.step_1'),
                    l('admin_leader_operating_system.leader.opportunity_action.unlock_registrations.step_2'),
                    $top_country !== '' && $top_country !== '-'
                        ? sprintf(l('admin_leader_operating_system.leader.opportunity_action.unlock_registrations.step_3_country'), $top_country)
                        : l('admin_leader_operating_system.leader.opportunity_action.unlock_registrations.step_3'),
                ],
            ];
        }

        if($growth !== null && $growth > 0 && $registration_rate < 10) {
            $items[] = [
                'label' => l('admin_leader_operating_system.leader.opportunity_action.raise_conversion_rate.label'),
                'text' => sprintf(
                    l('admin_leader_operating_system.leader.opportunity_action.raise_conversion_rate.text'),
                    nr($growth),
                    nr($registration_rate)
                ),
                'actions' => [
                    l('admin_leader_operating_system.leader.opportunity_action.raise_conversion_rate.step_1'),
                    l('admin_leader_operating_system.leader.opportunity_action.raise_conversion_rate.step_2'),
                    l('admin_leader_operating_system.leader.opportunity_action.raise_conversion_rate.step_3'),
                ],
            ];
        }

        if(($whatsapp_contacts + $product_clicks + ($funnel_registrations * 2)) >= 10 && $shop_clicks < 5) {
            $items[] = [
                'label' => l('admin_leader_operating_system.leader.opportunity_action.app_signal_path.label'),
                'text' => sprintf(
                    l('admin_leader_operating_system.leader.opportunity_action.app_signal_path.text'),
                    nr($whatsapp_contacts),
                    nr($product_clicks),
                    nr($funnel_registrations)
                ),
                'actions' => [
                    l('admin_leader_operating_system.leader.opportunity_action.app_signal_path.step_1'),
                    l('admin_leader_operating_system.leader.opportunity_action.app_signal_path.step_2'),
                    l('admin_leader_operating_system.leader.opportunity_action.app_signal_path.step_3'),
                ],
            ];
        }

        if(empty($items)) {
            $items[] = [
                'label' => l('admin_leader_operating_system.leader.opportunity_action.keep_momentum.label'),
                'text' => l('admin_leader_operating_system.leader.opportunity_action.keep_momentum.text'),
                'actions' => [
                    l('admin_leader_operating_system.leader.opportunity_action.keep_momentum.step_1'),
                    l('admin_leader_operating_system.leader.opportunity_action.keep_momentum.step_2'),
                    l('admin_leader_operating_system.leader.opportunity_action.keep_momentum.step_3'),
                ],
            ];
        }

        return [
            'score' => (int) ($payload['opportunity_score'] ?? 0),
            'intro' => sprintf(
                l('admin_leader_operating_system.leader.opportunity_modal_intro'),
                nr((int) ($payload['opportunity_score'] ?? 0))
            ),
            'items' => $items,
        ];
    }
    /* /Custom code: FC-2026-04-01 */

    private function get_period_payload(int $user_id, string $period_key, array $biolink_sets, int $shop_clicks_90d): array {
        $period_days = $this->get_period_days($period_key);
        $period_start_datetime = $this->get_period_start_datetime($period_days);
        $previous_period_start_datetime = (new \DateTimeImmutable($period_start_datetime))->sub(new \DateInterval('P' . $period_days . 'D'))->format('Y-m-d H:i:s');
        $shop_condition = \Altum\Link::get_forever_shop_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql']);
        $registration_condition = \Altum\Link::get_forever_registration_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_registration_block_types_sql']);
        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);

        $clicks_total = (int) db()->where('user_id', $user_id)->where('datetime', $period_start_datetime, '>=')->getValue('track_links', 'COUNT(*)');
        $previous_clicks_total = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` WHERE `user_id` = {$user_id} AND `datetime` >= '{$previous_period_start_datetime}' AND `datetime` < '{$period_start_datetime}'")->fetch_object()->total;

        $current_counts = database()->query("SELECT
            SUM(CASE WHEN {$shop_condition} THEN 1 ELSE 0 END) AS `shop_clicks`,
            SUM(CASE WHEN {$registration_condition} THEN 1 ELSE 0 END) AS `registration_clicks`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
              AND `track_links`.`is_unique` = 1
              AND `track_links`.`user_id` = {$user_id}")->fetch_object();

        $previous_counts = database()->query("SELECT
            SUM(CASE WHEN {$shop_condition} THEN 1 ELSE 0 END) AS `shop_clicks`,
            SUM(CASE WHEN {$registration_condition} THEN 1 ELSE 0 END) AS `registration_clicks`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`datetime` >= '{$previous_period_start_datetime}'
              AND `track_links`.`datetime` < '{$period_start_datetime}'
              AND `track_links`.`is_unique` = 1
              AND `track_links`.`user_id` = {$user_id}")->fetch_object();

        $forever_shop_clicks = (int) ($current_counts->shop_clicks ?? 0);
        $forever_registration_clicks = (int) ($current_counts->registration_clicks ?? 0);
        $previous_forever_shop_clicks = (int) ($previous_counts->shop_clicks ?? 0);
        $previous_forever_registration_clicks = (int) ($previous_counts->registration_clicks ?? 0);

        $clicks_result = database()->query("SELECT `track_links`.`datetime`, `track_links`.`country_code`, `track_links`.`city_name`, `track_links`.`browser_language`, `track_links`.`browser_name`, `track_links`.`device_type`, `track_links`.`referrer_host`, `track_links`.`utm_source`, `track_links`.`os_name`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
              AND `track_links`.`is_unique` = 1
              AND `track_links`.`user_id` = {$user_id}
              AND {$outbound_condition}
            ORDER BY `track_links`.`datetime` DESC");

        $country_buckets = [];
        $city_buckets = [];
        $source_buckets = [];
        $device_buckets = [];
        $browser_buckets = [];
        $language_buckets = [];
        $active_days = [];
        $last_click_at = '';

        while($click_row = $clicks_result->fetch_assoc()) {
            $last_click_at = $last_click_at ?: (string) ($click_row['datetime'] ?? '');
            $country_code = strtoupper(trim((string) ($click_row['country_code'] ?? '')));
            $city_name = trim((string) ($click_row['city_name'] ?? ''));
            $browser_language = trim((string) ($click_row['browser_language'] ?? ''));
            $browser_name = trim((string) ($click_row['browser_name'] ?? ''));
            $device_type = trim((string) ($click_row['device_type'] ?? ''));
            $source_label = $this->get_source_label($click_row);
            $date_label = substr((string) ($click_row['datetime'] ?? ''), 0, 10);

            $this->increment_bucket($country_buckets, $country_code, $country_code);
            $this->increment_bucket($city_buckets, mb_strtolower($city_name), $city_name);
            $this->increment_bucket($language_buckets, mb_strtolower($browser_language), $browser_language);
            $this->increment_bucket($source_buckets, mb_strtolower($source_label), $source_label);
            $this->increment_bucket($browser_buckets, mb_strtolower($browser_name), $browser_name);
            $this->increment_bucket($device_buckets, mb_strtolower($device_type), $device_type);

            if($date_label !== '') {
                $active_days[$date_label] = true;
            }
        }

        $payload = [
            'clicks_total_period' => $clicks_total,
            'previous_clicks_total' => $previous_clicks_total,
            'forever_shop_clicks_period' => $forever_shop_clicks,
            'forever_registration_clicks_period' => $forever_registration_clicks,
            'previous_forever_shop_clicks_period' => $previous_forever_shop_clicks,
            'previous_forever_registration_clicks_period' => $previous_forever_registration_clicks,
            'forever_shop_clicks_90d' => $shop_clicks_90d,
            'growth' => $this->get_growth_metrics($forever_shop_clicks, $previous_forever_shop_clicks),
            'comparison_clicks_total' => $this->get_growth_metrics($clicks_total, $previous_clicks_total),
            'comparison_registrations' => $this->get_growth_metrics($forever_registration_clicks, $previous_forever_registration_clicks),
            'last_click_at' => $last_click_at,
            'avg_daily_shop_clicks' => round($forever_shop_clicks / max(1, $period_days), 1),
            'avg_daily_registration_clicks' => round($forever_registration_clicks / max(1, $period_days), 1),
            'active_days_total' => count($active_days),
            'chart' => $this->get_chart_series($user_id, $period_start_datetime, $period_days, $biolink_sets),
            'top_countries' => $this->build_breakdown($country_buckets, $forever_shop_clicks, 5),
            'top_cities' => $this->build_breakdown($city_buckets, $forever_shop_clicks, 5),
            'top_sources' => $this->build_breakdown($source_buckets, $forever_shop_clicks, 5),
            'top_devices' => $this->build_breakdown($device_buckets, $forever_shop_clicks, 5),
            'top_browsers' => $this->build_breakdown($browser_buckets, $forever_shop_clicks, 5),
            'top_languages' => $this->build_breakdown($language_buckets, $forever_shop_clicks, 5),
            'funnel' => $this->get_funnel_payload($user_id, $period_start_datetime),
        ];

        $payload = array_merge($payload, $this->get_user_app_signal_payload($user_id, $period_start_datetime));
        $payload['blog_forever'] = $this->get_user_blog_forever_payload($user_id, $period_start_datetime, (int) $payload['forever_shop_clicks_period']);
        $payload['blog_forever_clicks_period'] = (int) ($payload['blog_forever']['total_clicks'] ?? 0);
        $payload['blog_forever_share_percent'] = (float) ($payload['blog_forever']['share_percent'] ?? 0.0);
        $payload['growth_percent'] = $payload['growth']['growth_percent'];
        $payload['growth_difference'] = $payload['growth']['difference'];
        $payload = array_merge($payload, $this->get_scores($payload));
        $payload = array_merge($payload, $this->get_status_payload($payload));
        $payload['top_country_label'] = $this->get_primary_breakdown_label($payload['top_countries']);
        $payload['top_source_label'] = $this->get_primary_breakdown_label($payload['top_sources'], l('admin_index.biolink_qualified_watch.source.direct_share'));
        $payload['top_device_label'] = $this->get_primary_breakdown_label($payload['top_devices']);
        $payload['top_language_label'] = $this->get_primary_breakdown_label($payload['top_languages']);
        $payload['next_step'] = $this->get_next_step($payload);

        return $payload;
    }

    /* Custom code: FC-2026-03-31: V2 cohort comparison by market and collaborator tier */
    private function get_cohort_tier_payload(int $shop_clicks_90d): array {
        $tier_key = 'starter';

        if($shop_clicks_90d >= 60) {
            $tier_key = 'scaled';
        } elseif($shop_clicks_90d >= 15) {
            $tier_key = 'building';
        }

        return [
            'tier_key' => $tier_key,
            'tier_label' => l('admin_leader_operating_system.leader.cohort_tier.' . $tier_key),
        ];
    }

    private function build_cohort_metric(string $label, float $selected_value, float $cohort_value, bool $lower_is_better = false): array {
        $delta = round($selected_value - $cohort_value, 1);
        $is_positive = $lower_is_better ? $delta <= 0 : $delta >= 0;

        return [
            'label' => $label,
            'selected_value' => $selected_value,
            'cohort_value' => $cohort_value,
            'delta' => $delta,
            'delta_class' => $delta === 0.0 ? 'is-neutral' : ($is_positive ? 'is-positive' : 'is-negative'),
        ];
    }

    private function get_cohort_comparison_payload(int $selected_user_id, array $selected_payload, string $period_key): array {
        $period_days = $this->get_period_days($period_key);
        $period_start_datetime = $this->get_period_start_datetime($period_days);
        $previous_period_start_datetime = (new \DateTimeImmutable($period_start_datetime))->sub(new \DateInterval('P' . $period_days . 'D'))->format('Y-m-d H:i:s');
        $ninety_days_start_datetime = $this->get_period_start_datetime(90);
        $query_start_datetime = $period_days === 90 ? $previous_period_start_datetime : $ninety_days_start_datetime;
        $biolink_sets = $this->get_biolink_sets();
        $shop_condition = \Altum\Link::get_forever_shop_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql']);
        $registration_condition = \Altum\Link::get_forever_registration_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_registration_block_types_sql']);
        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);

        $country_result = database()->query("SELECT
            `track_links`.`user_id`,
            UPPER(TRIM(`track_links`.`country_code`)) AS `country_code`,
            COUNT(*) AS `total`
        FROM `track_links`
        LEFT JOIN `users` ON `users`.`user_id` = `track_links`.`user_id`
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `users`.`type` = 0
          AND `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `track_links`.`is_unique` = 1
          AND {$outbound_condition}
        GROUP BY `track_links`.`user_id`, `country_code`");

        $top_country_map = [];

        while($row = $country_result->fetch_object()) {
            $user_id = (int) ($row->user_id ?? 0);
            $country_code = trim((string) ($row->country_code ?? ''));
            $total = (int) ($row->total ?? 0);

            if(!$user_id || $country_code === '') {
                continue;
            }

            if(!isset($top_country_map[$user_id]) || $total > $top_country_map[$user_id]['total']) {
                $top_country_map[$user_id] = [
                    'label' => $country_code,
                    'total' => $total,
                ];
            }
        }

        $selected_tier = $this->get_cohort_tier_payload((int) ($selected_payload['forever_shop_clicks_90d'] ?? 0));
        $selected_country = trim((string) ($selected_payload['top_country_label'] ?? '-'));

        $result = database()->query("SELECT
            `users`.`user_id`,
            `users`.`name`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' THEN 1 ELSE 0 END) AS `clicks_total_period`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$shop_condition} THEN 1 ELSE 0 END) AS `forever_shop_clicks_period`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$registration_condition} THEN 1 ELSE 0 END) AS `forever_registration_clicks_period`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$previous_period_start_datetime}' AND `track_links`.`datetime` < '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$shop_condition} THEN 1 ELSE 0 END) AS `previous_forever_shop_clicks_period`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$ninety_days_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$shop_condition} THEN 1 ELSE 0 END) AS `forever_shop_clicks_90d`
        FROM `users`
        LEFT JOIN `track_links` ON `track_links`.`user_id` = `users`.`user_id` AND `track_links`.`datetime` >= '{$query_start_datetime}'
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `users`.`type` = 0
        GROUP BY `users`.`user_id`");

        $tier_candidates = [];
        $market_tier_candidates = [];

        while($row = $result->fetch_object()) {
            $candidate_user_id = (int) ($row->user_id ?? 0);

            if(!$candidate_user_id || $candidate_user_id === $selected_user_id) {
                continue;
            }

            $candidate = [
                'user_id' => $candidate_user_id,
                'name' => (string) ($row->name ?? l('global.unknown')),
                'clicks_total_period' => (int) ($row->clicks_total_period ?? 0),
                'forever_shop_clicks_period' => (int) ($row->forever_shop_clicks_period ?? 0),
                'forever_registration_clicks_period' => (int) ($row->forever_registration_clicks_period ?? 0),
                'previous_forever_shop_clicks_period' => (int) ($row->previous_forever_shop_clicks_period ?? 0),
                'forever_shop_clicks_90d' => (int) ($row->forever_shop_clicks_90d ?? 0),
            ];

            $candidate['growth'] = $this->get_growth_metrics($candidate['forever_shop_clicks_period'], $candidate['previous_forever_shop_clicks_period']);
            $candidate['growth_percent'] = $candidate['growth']['growth_percent'];
            $candidate = array_merge($candidate, $this->get_scores($candidate));
            $candidate['top_country_label'] = $top_country_map[$candidate_user_id]['label'] ?? '-';
            $candidate['tier'] = $this->get_cohort_tier_payload((int) $candidate['forever_shop_clicks_90d']);

            if(($candidate['tier']['tier_key'] ?? '') !== $selected_tier['tier_key']) {
                continue;
            }

            $tier_candidates[] = $candidate;

            if($selected_country !== '-' && $candidate['top_country_label'] === $selected_country) {
                $market_tier_candidates[] = $candidate;
            }
        }

        $chosen_candidates = count($market_tier_candidates) >= 3 ? $market_tier_candidates : $tier_candidates;
        $scope_key = count($market_tier_candidates) >= 3 ? 'market_tier' : 'tier_only';

        if(empty($chosen_candidates)) {
            return [
                'cohort_size' => 0,
                'scope_key' => $scope_key,
                'scope_label' => l('admin_leader_operating_system.leader.cohort_scope.' . $scope_key),
                'selected_country' => $selected_country,
                'selected_tier_label' => $selected_tier['tier_label'],
                'metrics' => [],
                'top_peers' => [],
            ];
        }

        usort($chosen_candidates, static function($a, $b) {
            return (($b['leader_os_score'] ?? 0) <=> ($a['leader_os_score'] ?? 0)) ?: (($a['name'] ?? '') <=> ($b['name'] ?? ''));
        });

        $cohort_count = count($chosen_candidates);
        $average = static function(array $items, string $key) use ($cohort_count) {
            $total = 0.0;

            foreach($items as $item) {
                $total += (float) ($item[$key] ?? 0);
            }

            return $cohort_count > 0 ? round($total / $cohort_count, 1) : 0.0;
        };

        return [
            'cohort_size' => $cohort_count,
            'scope_key' => $scope_key,
            'scope_label' => l('admin_leader_operating_system.leader.cohort_scope.' . $scope_key),
            'selected_country' => $selected_country,
            'selected_tier_label' => $selected_tier['tier_label'],
            'metrics' => [
                $this->build_cohort_metric(l('admin_leader_operating_system.leader.kpi_score'), (float) ($selected_payload['leader_os_score'] ?? 0), $average($chosen_candidates, 'leader_os_score')),
                $this->build_cohort_metric(l('admin_leader_operating_system.leader.kpi_shop_clicks'), (float) ($selected_payload['forever_shop_clicks_period'] ?? 0), $average($chosen_candidates, 'forever_shop_clicks_period')),
                $this->build_cohort_metric(l('admin_leader_operating_system.leader.kpi_registrations'), (float) ($selected_payload['forever_registration_clicks_period'] ?? 0), $average($chosen_candidates, 'forever_registration_clicks_period')),
                $this->build_cohort_metric(l('admin_leader_operating_system.leader.conversion_score'), (float) ($selected_payload['conversion_score'] ?? 0), $average($chosen_candidates, 'conversion_score')),
                $this->build_cohort_metric(l('admin_leader_operating_system.leader.risk_score'), (float) ($selected_payload['risk_score'] ?? 0), $average($chosen_candidates, 'risk_score'), true),
            ],
            'top_peers' => array_slice(array_map(static function($candidate) {
                return [
                    'name' => (string) ($candidate['name'] ?? ''),
                    'leader_os_score' => (int) ($candidate['leader_os_score'] ?? 0),
                    'top_country_label' => (string) ($candidate['top_country_label'] ?? '-'),
                ];
            }, $chosen_candidates), 0, 3),
        ];
    }
    /* /Custom code: FC-2026-03-31 */

    /* Custom code: FC-2026-03-31: V3 behavior anomaly radar */
    private function build_behavior_anomaly_signal(string $level_key, string $label, string $text, string $action, int $points): array {
        $class_map = [
            'high' => 'status-warning',
            'watch' => 'status-info',
            'stable' => 'status-success',
        ];

        return [
            'level_key' => $level_key,
            'level_label' => l('admin_leader_operating_system.leader.anomaly_level.' . $level_key),
            'class' => $class_map[$level_key] ?? 'status-dark',
            'label' => $label,
            'text' => $text,
            'action' => $action,
            'points' => $points,
        ];
    }

    private function get_behavior_anomaly_payload(array $selected_payload, array $all_periods, array $app_structure, string $period_key): array {
        $signals = [];
        $anomaly_score = 0;
        $shop_clicks = (int) ($selected_payload['forever_shop_clicks_period'] ?? 0);
        $registrations = (int) ($selected_payload['forever_registration_clicks_period'] ?? 0);
        $total_clicks = (int) ($selected_payload['clicks_total_period'] ?? 0);
        $active_days_total = (int) ($selected_payload['active_days_total'] ?? 0);
        $top_source = $selected_payload['top_sources'][0] ?? [];
        $top_country = $selected_payload['top_countries'][0] ?? [];
        $top_source_share = (float) ($top_source['share'] ?? 0);
        $top_country_share = (float) ($top_country['share'] ?? 0);
        $funnel_unique_clicks = (int) ($selected_payload['funnel']['unique_clicks'] ?? 0);
        $funnel_leads = (int) ($selected_payload['funnel']['total_leads'] ?? 0);
        $app_whatsapp_contacts = (int) ($selected_payload['app_whatsapp_contacts_period'] ?? 0);
        $app_product_clicks = (int) ($selected_payload['app_product_clicks_period'] ?? 0);
        $app_funnel_registrations = (int) ($selected_payload['app_funnel_registrations_period'] ?? 0);
        $recent_avg_daily = (float) ($all_periods['7d']['avg_daily_shop_clicks'] ?? 0);
        $baseline_avg_daily = (float) ($all_periods['30d']['avg_daily_shop_clicks'] ?? 0);
        $structure_health_score = (int) ($app_structure['health_score'] ?? 0);

        $add_signal = function(string $level_key, string $label, string $text, string $action, int $points) use (&$signals, &$anomaly_score) {
            $signals[] = $this->build_behavior_anomaly_signal($level_key, $label, $text, $action, $points);
            $anomaly_score += $points;
        };

        if($shop_clicks >= 20 && $top_source_share >= 75) {
            $add_signal(
                'watch',
                l('admin_leader_operating_system.leader.anomaly_signal.source_concentration.label'),
                sprintf(l('admin_leader_operating_system.leader.anomaly_signal.source_concentration.text'), (string) ($top_source['label'] ?? '-'), round($top_source_share, 1)),
                l('admin_leader_operating_system.leader.anomaly_signal.source_concentration.action'),
                16
            );
        }

        if($shop_clicks >= 15 && $top_country_share >= 80) {
            $add_signal(
                'watch',
                l('admin_leader_operating_system.leader.anomaly_signal.market_concentration.label'),
                sprintf(l('admin_leader_operating_system.leader.anomaly_signal.market_concentration.text'), (string) ($top_country['label'] ?? '-'), round($top_country_share, 1)),
                l('admin_leader_operating_system.leader.anomaly_signal.market_concentration.action'),
                14
            );
        }

        if($shop_clicks >= 12 && $registrations === 0) {
            $add_signal(
                'high',
                l('admin_leader_operating_system.leader.anomaly_signal.conversion_block.label'),
                sprintf(l('admin_leader_operating_system.leader.anomaly_signal.conversion_block.text'), $shop_clicks),
                l('admin_leader_operating_system.leader.anomaly_signal.conversion_block.action'),
                22
            );
        }

        if(($app_whatsapp_contacts + $app_product_clicks + ($app_funnel_registrations * 2)) >= 10 && $shop_clicks < 5) {
            $add_signal(
                'watch',
                l('admin_leader_operating_system.leader.anomaly_signal.app_signal_gap.label'),
                sprintf(
                    l('admin_leader_operating_system.leader.anomaly_signal.app_signal_gap.text'),
                    nr($app_whatsapp_contacts),
                    nr($app_product_clicks),
                    nr($app_funnel_registrations),
                    nr($shop_clicks)
                ),
                l('admin_leader_operating_system.leader.anomaly_signal.app_signal_gap.action'),
                18
            );
        }

        if($baseline_avg_daily >= 1.5 && $recent_avg_daily <= max(0.4, $baseline_avg_daily * 0.45)) {
            $add_signal(
                'high',
                l('admin_leader_operating_system.leader.anomaly_signal.recent_drop.label'),
                sprintf(l('admin_leader_operating_system.leader.anomaly_signal.recent_drop.text'), round($baseline_avg_daily, 1), round($recent_avg_daily, 1)),
                l('admin_leader_operating_system.leader.anomaly_signal.recent_drop.action'),
                20
            );
        }

        if($total_clicks >= 20 && $active_days_total <= 2) {
            $add_signal(
                'watch',
                l('admin_leader_operating_system.leader.anomaly_signal.activity_concentration.label'),
                sprintf(l('admin_leader_operating_system.leader.anomaly_signal.activity_concentration.text'), $active_days_total),
                l('admin_leader_operating_system.leader.anomaly_signal.activity_concentration.action'),
                12
            );
        }

        if($funnel_unique_clicks >= 10 && $funnel_leads === 0) {
            $add_signal(
                'high',
                l('admin_leader_operating_system.leader.anomaly_signal.funnel_block.label'),
                sprintf(l('admin_leader_operating_system.leader.anomaly_signal.funnel_block.text'), $funnel_unique_clicks),
                l('admin_leader_operating_system.leader.anomaly_signal.funnel_block.action'),
                18
            );
        }

        if((int) ($selected_payload['funnel']['total_funnels'] ?? 0) >= 2 && (int) ($selected_payload['funnel']['active_funnels'] ?? 0) <= 1) {
            $add_signal(
                'watch',
                l('admin_leader_operating_system.leader.anomaly_signal.funnel_fragmented.label'),
                sprintf(
                    l('admin_leader_operating_system.leader.anomaly_signal.funnel_fragmented.text'),
                    (int) ($selected_payload['funnel']['total_funnels'] ?? 0),
                    (int) ($selected_payload['funnel']['active_funnels'] ?? 0)
                ),
                l('admin_leader_operating_system.leader.anomaly_signal.funnel_fragmented.action'),
                14
            );
        }

        if($funnel_unique_clicks >= 15 && (float) ($selected_payload['funnel']['conversion_rate'] ?? 0) > 0 && (float) ($selected_payload['funnel']['conversion_rate'] ?? 0) < 5) {
            $add_signal(
                'watch',
                l('admin_leader_operating_system.leader.anomaly_signal.funnel_low_conversion.label'),
                sprintf(
                    l('admin_leader_operating_system.leader.anomaly_signal.funnel_low_conversion.text'),
                    round((float) ($selected_payload['funnel']['conversion_rate'] ?? 0), 1),
                    $funnel_unique_clicks
                ),
                l('admin_leader_operating_system.leader.anomaly_signal.funnel_low_conversion.action'),
                12
            );
        }

        if($structure_health_score > 0 && $structure_health_score < 45 && $shop_clicks >= 10) {
            $add_signal(
                'watch',
                l('admin_leader_operating_system.leader.anomaly_signal.structure_friction.label'),
                sprintf(l('admin_leader_operating_system.leader.anomaly_signal.structure_friction.text'), $structure_health_score),
                l('admin_leader_operating_system.leader.anomaly_signal.structure_friction.action'),
                10
            );
        }

        if($shop_clicks >= 15 && (int) ($app_structure['forever_entry_points_total'] ?? 0) < 2) {
            $add_signal(
                'high',
                l('admin_leader_operating_system.leader.anomaly_signal.entry_gap.label'),
                sprintf(l('admin_leader_operating_system.leader.anomaly_signal.entry_gap.text'), (int) ($app_structure['forever_entry_points_total'] ?? 0)),
                l('admin_leader_operating_system.leader.anomaly_signal.entry_gap.action'),
                18
            );
        }

        if($total_clicks >= 20 && (((int) ($app_structure['visual_stack_total'] ?? 0) < 1) || ((int) ($app_structure['social_stack_total'] ?? 0) < 1))) {
            $add_signal(
                'watch',
                l('admin_leader_operating_system.leader.anomaly_signal.trust_gap.label'),
                sprintf(
                    l('admin_leader_operating_system.leader.anomaly_signal.trust_gap.text'),
                    (int) ($app_structure['visual_stack_total'] ?? 0),
                    (int) ($app_structure['social_stack_total'] ?? 0)
                ),
                l('admin_leader_operating_system.leader.anomaly_signal.trust_gap.action'),
                12
            );
        }

        if((int) ($app_structure['total_apps'] ?? 0) >= 3 && (int) ($app_structure['top_app_total_blocks'] ?? 0) <= 4) {
            $add_signal(
                'watch',
                l('admin_leader_operating_system.leader.anomaly_signal.multi_app_fragmented.label'),
                sprintf(
                    l('admin_leader_operating_system.leader.anomaly_signal.multi_app_fragmented.text'),
                    (int) ($app_structure['total_apps'] ?? 0),
                    (int) ($app_structure['top_app_total_blocks'] ?? 0)
                ),
                l('admin_leader_operating_system.leader.anomaly_signal.multi_app_fragmented.action'),
                10
            );
        }

        if(!empty($selected_payload['last_click_at']) && (int) ($selected_payload['forever_shop_clicks_90d'] ?? 0) >= 15) {
            try {
                $days_since_last_click = (int) (new \DateTimeImmutable((string) $selected_payload['last_click_at']))->diff(new \DateTimeImmutable())->format('%a');

                if($days_since_last_click >= 14) {
                    $add_signal(
                        'high',
                        l('admin_leader_operating_system.leader.anomaly_signal.freshness_gap.label'),
                        sprintf(l('admin_leader_operating_system.leader.anomaly_signal.freshness_gap.text'), $days_since_last_click),
                        l('admin_leader_operating_system.leader.anomaly_signal.freshness_gap.action'),
                        18
                    );
                } elseif($days_since_last_click >= 7) {
                    $add_signal(
                        'watch',
                        l('admin_leader_operating_system.leader.anomaly_signal.freshness_gap.label'),
                        sprintf(l('admin_leader_operating_system.leader.anomaly_signal.freshness_gap.text'), $days_since_last_click),
                        l('admin_leader_operating_system.leader.anomaly_signal.freshness_gap.action'),
                        10
                    );
                }
            } catch(\Throwable $exception) {
            }
        }

        usort($signals, static function($a, $b) {
            return (($b['points'] ?? 0) <=> ($a['points'] ?? 0)) ?: (($a['label'] ?? '') <=> ($b['label'] ?? ''));
        });

        $anomaly_score = $this->clamp_score($anomaly_score);
        $level_key = 'stable';

        if($anomaly_score >= 55) {
            $level_key = 'high';
        } elseif($anomaly_score >= 25) {
            $level_key = 'watch';
        }

        return [
            'period_key' => $period_key,
            'score' => $anomaly_score,
            'level_key' => $level_key,
            'level_label' => l('admin_leader_operating_system.leader.anomaly_level.' . $level_key),
            'level_class' => $this->build_behavior_anomaly_signal($level_key, '', '', '', 0)['class'],
            'signals_total' => count($signals),
            'top_concern' => $signals[0]['label'] ?? l('admin_leader_operating_system.leader.anomaly_none'),
            'signals' => $signals,
        ];
    }
    /* /Custom code: FC-2026-03-31 */

    private function get_detail_payload(int $user_id): ?array {
        $user_query = db()->where('user_id', $user_id);

        if($user_id !== (int) ($this->user->user_id ?? 0)) {
            $user_query->where('type', 0);
        }

        $user = $user_query->getOne('users', ['user_id', 'name', 'email', 'preferences', 'extra', 'payment_processor', 'payment_subscription_id', 'plan_id', 'plan_expiration_date']);

        if(!$user) {
            return null;
        }

        $biolink_sets = $this->get_biolink_sets();
        $ninety_days_start_datetime = $this->get_period_start_datetime(90);
          $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);
        $shop_clicks_90d = (int) database()->query("SELECT COUNT(*) AS `total`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`datetime` >= '{$ninety_days_start_datetime}'
              AND `track_links`.`is_unique` = 1
              AND `track_links`.`user_id` = {$user_id}
              AND {$outbound_condition}")->fetch_object()->total;

        $periods = [];
        foreach(['7d', '30d', '90d'] as $period_key) {
            $periods[$period_key] = $this->get_period_payload($user_id, $period_key, $biolink_sets, $shop_clicks_90d);
        }

        /* Custom code: FC-2026-03-31: Phase 6 fraud intelligence per period with lightweight persistence */
        $fraud_intelligence = [];
        foreach(['7d', '30d', '90d'] as $period_key) {
            $fraud_intelligence[$period_key] = $this->get_fraud_intelligence_payload($user_id, $period_key);
            $periods[$period_key] = $this->blend_period_payload_with_fraud($periods[$period_key], $fraud_intelligence[$period_key]);
            $this->persist_fraud_cluster_summary($user_id, $period_key, $fraud_intelligence[$period_key]);
        }
        /* /Custom code: FC-2026-03-31 */

        /* Custom code: FC-2026-03-31: Persist lightweight LOS score snapshots */
        $score_history = $this->persist_score_snapshots((int) $user->user_id, $user->preferences ?? null, $periods);
        /* /Custom code: FC-2026-03-31 */

        $country_signal_matrix_periods = $this->get_country_signal_matrix_periods_payload($user_id);

        return [
            'user_id' => (int) $user->user_id,
            'name' => (string) ($user->name ?? l('global.unknown')),
            'email' => (string) ($user->email ?? ''),
            'extra' => $user->extra ?? null,
            'payment_processor' => (string) ($user->payment_processor ?? ''),
            'payment_subscription_id' => (string) ($user->payment_subscription_id ?? ''),
            'plan_id' => (int) ($user->plan_id ?? 0),
            'plan_expiration_date' => $user->plan_expiration_date ?? null,
            'forever_id' => $this->extract_forever_id_from_preferences($user->preferences ?? null),
            'admin_user_url' => url('admin/user-view/' . (int) $user->user_id),
            /* Custom code: FC-2026-03-31: Attach app structure to leader payload */
            'app_structure' => $this->get_app_structure_payload($user_id),
            /* /Custom code: FC-2026-03-31 */
            /* Custom code: FC-2026-03-31: Attach AI Plan phase 4 admin coaching payload */
            'ai_plan_admin' => $this->get_ai_plan_admin_payload($user->preferences ?? null, $periods['30d'] ?? []),
            /* /Custom code: FC-2026-03-31 */
            /* Custom code: FC-2026-03-31: Attach LOS score history payload */
            'score_history' => $score_history,
            /* /Custom code: FC-2026-03-31 */
            /* Custom code: FC-2026-03-31: Attach Phase 6 fraud intelligence payload */
            'fraud_intelligence' => $fraud_intelligence,
            /* /Custom code: FC-2026-03-31 */
            'country_signal_matrix_periods' => $country_signal_matrix_periods,
            'periods' => $periods,
        ];
    }

    /* Custom code: FC-2026-03-31: Lightweight LOS score snapshot history */
    private function get_score_snapshot_store($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $snapshots = $preferences->leader_os_score_snapshots ?? [];

        if(is_object($snapshots)) {
            $snapshots = (array) $snapshots;
        }

        if(!is_array($snapshots)) {
            return [];
        }

        $normalized = [];

        foreach($snapshots as $snapshot) {
            if(is_object($snapshot)) {
                $snapshot = (array) $snapshot;
            }

            if(!is_array($snapshot)) {
                continue;
            }

            $period_key = $this->get_valid_period_key((string) ($snapshot['period_key'] ?? '30d'));

            $normalized[] = [
                'snapshot_id' => trim((string) ($snapshot['snapshot_id'] ?? '')),
                'period_key' => $period_key,
                'created_at' => $snapshot['created_at'] ?? null,
                'leader_os_score' => (int) ($snapshot['leader_os_score'] ?? 0),
                'performance_score' => (int) ($snapshot['performance_score'] ?? 0),
                'momentum_score' => (int) ($snapshot['momentum_score'] ?? 0),
                'conversion_score' => (int) ($snapshot['conversion_score'] ?? 0),
                'risk_score' => (int) ($snapshot['risk_score'] ?? 0),
                'opportunity_score' => (int) ($snapshot['opportunity_score'] ?? 0),
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_score_snapshot_signature(array $snapshot): string {
        return implode('|', [
            (int) ($snapshot['leader_os_score'] ?? 0),
            (int) ($snapshot['performance_score'] ?? 0),
            (int) ($snapshot['momentum_score'] ?? 0),
            (int) ($snapshot['conversion_score'] ?? 0),
            (int) ($snapshot['risk_score'] ?? 0),
            (int) ($snapshot['opportunity_score'] ?? 0),
        ]);
    }

    private function build_score_snapshot_entry(string $period_key, array $payload): array {
        return [
            'snapshot_id' => $this->generate_los_outreach_id('los_score'),
            'period_key' => $period_key,
            'created_at' => get_date(),
            'leader_os_score' => (int) ($payload['leader_os_score'] ?? 0),
            'performance_score' => (int) ($payload['performance_score'] ?? 0),
            'momentum_score' => (int) ($payload['momentum_score'] ?? 0),
            'conversion_score' => (int) ($payload['conversion_score'] ?? 0),
            'risk_score' => (int) ($payload['risk_score'] ?? 0),
            'opportunity_score' => (int) ($payload['opportunity_score'] ?? 0),
        ];
    }

    private function build_score_history_payload(array $store): array {
        $grouped = [
            '7d' => [],
            '30d' => [],
            '90d' => [],
        ];

        foreach($store as $snapshot) {
            $period_key = $this->get_valid_period_key((string) ($snapshot['period_key'] ?? '30d'));
            $grouped[$period_key][] = $snapshot;
        }

        $payload = [];

        foreach($grouped as $period_key => $snapshots) {
            $history = [];

            foreach(array_values($snapshots) as $index => $snapshot) {
                $previous_snapshot = $snapshots[$index + 1] ?? null;
                $delta = $previous_snapshot ? ((int) ($snapshot['leader_os_score'] ?? 0) - (int) ($previous_snapshot['leader_os_score'] ?? 0)) : null;

                $snapshot['delta_leader_os_score'] = $delta;
                $snapshot['delta_class'] = $delta === null ? 'is-neutral' : ($delta >= 0 ? 'is-positive' : 'is-negative');
                $history[] = $snapshot;
            }

            $payload[$period_key] = [
                'latest' => $history[0] ?? null,
                'previous' => $history[1] ?? null,
                'history' => array_slice($history, 0, 6),
                'total' => count($history),
            ];
        }

        return $payload;
    }

    private function persist_score_snapshots(int $user_id, $preferences, array $periods): array {
        $preferences = $this->get_preferences_object($preferences);
        $store = $this->get_score_snapshot_store($preferences);
        $changed = false;

        foreach(['7d', '30d', '90d'] as $period_key) {
            $payload = $periods[$period_key] ?? null;

            if(!$payload || !is_array($payload)) {
                continue;
            }

            $new_snapshot = $this->build_score_snapshot_entry($period_key, $payload);
            $latest_snapshot = null;

            foreach($store as $snapshot) {
                if((string) ($snapshot['period_key'] ?? '') === $period_key) {
                    $latest_snapshot = $snapshot;
                    break;
                }
            }

            if(!$latest_snapshot || $this->get_score_snapshot_signature($latest_snapshot) !== $this->get_score_snapshot_signature($new_snapshot)) {
                array_unshift($store, $new_snapshot);
                $changed = true;
            }
        }

        if($changed) {
            $pruned_store = [];
            $period_counts = ['7d' => 0, '30d' => 0, '90d' => 0];

            foreach($store as $snapshot) {
                $period_key = $this->get_valid_period_key((string) ($snapshot['period_key'] ?? '30d'));

                if($period_counts[$period_key] >= 12) {
                    continue;
                }

                $pruned_store[] = $snapshot;
                $period_counts[$period_key]++;
            }

            $preferences->leader_os_score_snapshots = array_values($pruned_store);
            $this->save_user_preferences($user_id, $preferences);
            $store = $pruned_store;
        }

        return $this->build_score_history_payload($store);
    }
    /* /Custom code: FC-2026-03-31 */

    /* Custom code: FC-2026-03-31: AI Plan phase 4 admin coaching helpers */
    private function get_preferences_object($preferences): \stdClass {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!$preferences instanceof \stdClass) {
            $preferences = (object) $preferences;
        }

        return $preferences;
    }

    private function translate_ai_plan_option(string $group, $value): string {
        if(is_array($value)) {
            $labels = [];

            foreach($value as $item) {
                $label = $this->translate_ai_plan_option($group, $item);

                if($label !== '-') {
                    $labels[] = $label;
                }
            }

            return !empty($labels) ? implode(', ', $labels) : '-';
        }

        if(!is_scalar($value)) {
            return '-';
        }

        $value = trim((string) $value);

        if($value === '') {
            return '-';
        }

        $key = 'ai_plan.option.' . $group . '.' . $value;
        $label = l($key);

        if($label === $key) {
            return ucfirst(str_replace('_', ' ', $value));
        }

        return $label;
    }

    private function get_ai_plan_profile($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $profile = $preferences->leader_ai_profile ?? null;

        if(is_object($profile)) {
            $profile = (array) $profile;
        }

        if(!is_array($profile)) {
            return [];
        }

        $channels = [];
        foreach((array) ($profile['active_channels'] ?? []) as $channel) {
            if(is_scalar($channel)) {
                $channels[] = (string) $channel;
            }
        }

        return [
            'primary_goal' => (string) ($profile['primary_goal'] ?? ''),
            'primary_goal_label' => $this->translate_ai_plan_option('primary_goal', $profile['primary_goal'] ?? ''),
            'priority_offer' => (string) ($profile['priority_offer'] ?? ''),
            'priority_offer_label' => $this->translate_ai_plan_option('priority_offer', $profile['priority_offer'] ?? ''),
            'biggest_blocker' => (string) ($profile['biggest_blocker'] ?? ''),
            'biggest_blocker_label' => $this->translate_ai_plan_option('biggest_blocker', $profile['biggest_blocker'] ?? ''),
            'active_channels' => $channels,
            'active_channels_label' => $this->translate_ai_plan_option('active_channels', $channels),
            'available_time' => (string) ($profile['available_time'] ?? ''),
            'available_time_label' => $this->translate_ai_plan_option('available_time', $profile['available_time'] ?? ''),
            'notes' => trim((string) ($profile['notes'] ?? '')),
            'updated_at' => $profile['updated_at'] ?? null,
        ];
    }

    private function get_ai_plan_checkins($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $checkins = $preferences->leader_ai_weekly_checkins ?? [];

        if(is_object($checkins)) {
            $checkins = (array) $checkins;
        }

        if(!is_array($checkins)) {
            return [];
        }

        $normalized = [];

        foreach($checkins as $checkin) {
            if(is_object($checkin)) {
                $checkin = (array) $checkin;
            }

            if(!is_array($checkin)) {
                continue;
            }

            $normalized[] = [
                'weekly_priority' => (string) ($checkin['weekly_priority'] ?? ''),
                'weekly_priority_label' => $this->translate_ai_plan_option('weekly_priority', $checkin['weekly_priority'] ?? ''),
                'content_commitment' => (string) ($checkin['content_commitment'] ?? ''),
                'content_commitment_label' => $this->translate_ai_plan_option('content_commitment', $checkin['content_commitment'] ?? ''),
                'follow_up_volume' => (string) ($checkin['follow_up_volume'] ?? ''),
                'follow_up_volume_label' => $this->translate_ai_plan_option('follow_up_volume', $checkin['follow_up_volume'] ?? ''),
                'ai_need' => (string) ($checkin['ai_need'] ?? ''),
                'ai_need_label' => $this->translate_ai_plan_option('ai_need', $checkin['ai_need'] ?? ''),
                'weekly_energy' => (string) ($checkin['weekly_energy'] ?? ''),
                'weekly_energy_label' => $this->translate_ai_plan_option('weekly_energy', $checkin['weekly_energy'] ?? ''),
                'weekly_context' => trim((string) ($checkin['weekly_context'] ?? '')),
                'adaptive_answer' => trim((string) ($checkin['adaptive_answer'] ?? '')),
                'submitted_at' => $checkin['submitted_at'] ?? null,
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_ai_plan_plans($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $plans = $preferences->leader_ai_weekly_plans ?? [];

        if(is_object($plans)) {
            $plans = (array) $plans;
        }

        if(!is_array($plans)) {
            return [];
        }

        $normalized = [];

        foreach($plans as $plan) {
            if(is_object($plan)) {
                $plan = (array) $plan;
            }

            if(!is_array($plan)) {
                continue;
            }

            $coach_ideas = [];
            foreach((array) ($plan['coach_ideas'] ?? []) as $idea) {
                if(is_scalar($idea) && trim((string) $idea) !== '') {
                    $coach_ideas[] = trim((string) $idea);
                }
            }

            $normalized[] = [
                'checkin_submitted_at' => $plan['checkin_submitted_at'] ?? null,
                'generated_at' => $plan['generated_at'] ?? null,
                'headline' => trim((string) ($plan['headline'] ?? '')),
                'summary' => trim((string) ($plan['summary'] ?? '')),
                'focus' => trim((string) ($plan['focus'] ?? '')),
                'coach_intro' => trim((string) ($plan['coach_intro'] ?? '')),
                'brutal_truth' => trim((string) ($plan['brutal_truth'] ?? '')),
                'power_move' => trim((string) ($plan['power_move'] ?? '')),
                'why_this_week' => trim((string) ($plan['why_this_week'] ?? '')),
                'encouragement' => trim((string) ($plan['encouragement'] ?? '')),
                'coach_ideas' => array_slice($coach_ideas, 0, 3),
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['checkin_submitted_at'] ?? $b['generated_at'] ?? ''), (string) ($a['checkin_submitted_at'] ?? $a['generated_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_latest_matching_ai_plan(array $plans, ?array $latest_checkin): ?array {
        if(!$latest_checkin) {
            return $plans[0] ?? null;
        }

        $submitted_at = (string) ($latest_checkin['submitted_at'] ?? '');

        foreach($plans as $plan) {
            if((string) ($plan['checkin_submitted_at'] ?? '') === $submitted_at) {
                return $plan;
            }
        }

        return $plans[0] ?? null;
    }

    private function get_ai_plan_change_log(?array $latest_checkin, ?array $previous_checkin): array {
        if(!$latest_checkin || !$previous_checkin) {
            return [];
        }

        $fields = [
            'weekly_priority' => l('ai_plan.weekly_priority'),
            'weekly_energy' => l('ai_plan.weekly_energy'),
            'content_commitment' => l('ai_plan.content_commitment'),
            'follow_up_volume' => l('ai_plan.follow_up_volume'),
            'ai_need' => l('ai_plan.ai_need'),
        ];

        $changes = [];

        foreach($fields as $field => $label) {
            $previous_value = trim((string) ($previous_checkin[$field] ?? ''));
            $latest_value = trim((string) ($latest_checkin[$field] ?? ''));

            if($previous_value === $latest_value) {
                continue;
            }

            $changes[] = [
                'label' => $label,
                'before' => (string) ($previous_checkin[$field . '_label'] ?? '-'),
                'after' => (string) ($latest_checkin[$field . '_label'] ?? '-'),
            ];
        }

        return $changes;
    }

    private function get_ai_plan_priority(array $profile, ?array $latest_checkin, ?array $latest_plan, array $period_payload): array {
        if(empty($profile['primary_goal'])) {
            return [
                'level' => 'waiting',
                'label' => l('admin_leader_operating_system.leader.ai_plan_priority_waiting'),
                'reason' => l('admin_leader_operating_system.leader.ai_plan_priority_reason_waiting_profile'),
            ];
        }

        if(!$latest_checkin) {
            return [
                'level' => 'waiting',
                'label' => l('admin_leader_operating_system.leader.ai_plan_priority_waiting'),
                'reason' => l('admin_leader_operating_system.leader.ai_plan_priority_reason_waiting_checkin'),
            ];
        }

        $score = 0;

        if(($latest_checkin['weekly_energy'] ?? '') === 'low') {
            $score += 2;
        }

        if(in_array((string) ($profile['biggest_blocker'] ?? ''), ['no_sales', 'no_leads', 'follow_up_unclear', 'low_confidence'], true)) {
            $score += 2;
        }

        if((int) ($period_payload['risk_score'] ?? 0) >= 65) {
            $score += 2;
        }

        if((int) ($period_payload['forever_shop_clicks_period'] ?? 0) < 15) {
            $score += 1;
        }

        if(!empty($latest_plan['brutal_truth'])) {
            $score += 1;
        }

        if($score >= 4) {
            return [
                'level' => 'high',
                'label' => l('admin_leader_operating_system.leader.ai_plan_priority_high'),
                'reason' => l('admin_leader_operating_system.leader.ai_plan_priority_reason_high'),
            ];
        }

        if($score >= 2) {
            return [
                'level' => 'medium',
                'label' => l('admin_leader_operating_system.leader.ai_plan_priority_medium'),
                'reason' => l('admin_leader_operating_system.leader.ai_plan_priority_reason_medium'),
            ];
        }

        return [
            'level' => 'low',
            'label' => l('admin_leader_operating_system.leader.ai_plan_priority_low'),
            'reason' => l('admin_leader_operating_system.leader.ai_plan_priority_reason_low'),
        ];
    }

    private function get_ai_plan_admin_payload($preferences, array $period_payload): array {
        $profile = $this->get_ai_plan_profile($preferences);
        $checkins = $this->get_ai_plan_checkins($preferences);
        $plans = $this->get_ai_plan_plans($preferences);
        $preferences = $this->get_preferences_object($preferences);
        $outcomes = $preferences->leader_ai_weekly_outcomes ?? [];
        if(is_object($outcomes)) {
            $outcomes = (array) $outcomes;
        }
        $latest_outcome = [];
        if(is_array($outcomes) && !empty($outcomes[0])) {
            $latest_outcome = is_object($outcomes[0]) ? (array) $outcomes[0] : (array) $outcomes[0];
        }
        $mentor_history = $this->get_ai_plan_mentor_history($preferences);
        $latest_checkin = $checkins[0] ?? null;
        $previous_checkin = $checkins[1] ?? null;
        $latest_plan = $this->get_latest_matching_ai_plan($plans, $latest_checkin);
        $change_log = $this->get_ai_plan_change_log($latest_checkin, $previous_checkin);
        $priority = $this->get_ai_plan_priority($profile, $latest_checkin, $latest_plan, $period_payload);

        $days_since_last_checkin = null;

        if(!empty($latest_checkin['submitted_at'])) {
            try {
                $latest_date = new \DateTimeImmutable((string) $latest_checkin['submitted_at']);
                $days_since_last_checkin = (int) $latest_date->diff(new \DateTimeImmutable())->format('%a');
            } catch(\Throwable $exception) {
                $days_since_last_checkin = null;
            }
        }

        $mentor_note = '';
        foreach([
            $latest_plan['summary'] ?? '',
            $latest_plan['coach_intro'] ?? '',
            $latest_plan['why_this_week'] ?? '',
            $latest_plan['encouragement'] ?? '',
        ] as $candidate_note) {
            $candidate_note = trim((string) $candidate_note);

            if($candidate_note !== '') {
                $mentor_note = $candidate_note;
                break;
            }
        }

        return [
            'has_profile' => !empty($profile['primary_goal']),
            'has_checkin' => (bool) $latest_checkin,
            'has_plan' => (bool) $latest_plan,
            'profile' => $profile,
            'latest_checkin' => $latest_checkin,
            'previous_checkin' => $previous_checkin,
            'latest_outcome' => $latest_outcome,
            'latest_plan' => $latest_plan,
            'change_log' => $change_log,
            'priority' => $priority,
            'mentor_note' => $mentor_note,
            'history_total' => count($checkins),
            'plans_total' => count($plans),
            'latest_outcome_completion_level' => (string) ($latest_outcome['completion_level'] ?? ''),
            'days_since_last_checkin' => $days_since_last_checkin,
            'mentor_actions' => $this->get_ai_plan_mentor_actions($preferences),
            'mentor_history' => array_slice($mentor_history, 0, 8),
            'mentor_history_total' => count($mentor_history),
            'latest_mentor_event' => $mentor_history[0] ?? null,
        ];
    }

    private function get_ai_plan_mentor_actions($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $actions = $preferences->leader_ai_admin_coaching ?? null;

        if(is_object($actions)) {
            $actions = (array) $actions;
        }

        if(!is_array($actions)) {
            $actions = [];
        }

        $status = trim((string) ($actions['status'] ?? 'pending_contact'));
        $allowed_statuses = ['pending_contact', 'in_progress', 'monitoring', 'resolved'];

        if(!in_array($status, $allowed_statuses, true)) {
            $status = 'pending_contact';
        }

        return [
            'status' => $status,
            'needs_follow_up' => (bool) ($actions['needs_follow_up'] ?? false),
            'mentored_this_week' => (bool) ($actions['mentored_this_week'] ?? false),
            'mentor_note' => trim((string) ($actions['mentor_note'] ?? '')),
            'ai_guidance' => trim((string) ($actions['ai_guidance'] ?? '')),
            'next_action' => trim((string) ($actions['next_action'] ?? '')),
            'updated_at' => $actions['updated_at'] ?? null,
            'last_contacted_at' => $actions['last_contacted_at'] ?? null,
        ];
    }

    private function get_ai_plan_mentor_history($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $history = $preferences->leader_ai_admin_history ?? [];

        if(is_object($history)) {
            $history = (array) $history;
        }

        if(!is_array($history)) {
            return [];
        }

        $normalized = [];

        foreach($history as $history_item) {
            if(is_object($history_item)) {
                $history_item = (array) $history_item;
            }

            if(!is_array($history_item)) {
                continue;
            }

            $summary = trim((string) ($history_item['summary'] ?? ''));

            if($summary === '') {
                continue;
            }

            $normalized[] = [
                'history_id' => trim((string) ($history_item['history_id'] ?? '')),
                'event_key' => trim((string) ($history_item['event_key'] ?? 'update')),
                'summary' => $summary,
                'details' => trim((string) ($history_item['details'] ?? '')),
                'created_at' => $history_item['created_at'] ?? null,
                'admin_id' => (int) ($history_item['admin_id'] ?? 0),
                'admin_name' => trim((string) ($history_item['admin_name'] ?? '')),
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_ai_plan_mentor_admin_identity(): array {
        $admin_name = trim((string) ($this->user->name ?? $this->user->email ?? 'Admin'));

        return [
            'admin_id' => (int) ($this->user->user_id ?? 0),
            'admin_name' => $admin_name !== '' ? $admin_name : 'Admin',
        ];
    }

    private function get_ai_plan_mentor_status_label(string $status): string {
        $key = 'admin_leader_operating_system.leader.ai_plan_admin_status.' . $status;
        $label = l($key);

        return $label === $key ? ucfirst(str_replace('_', ' ', $status)) : $label;
    }

    private function build_ai_plan_mentor_history_change_parts(array $existing_actions, array $new_actions): array {
        $parts = [];

        if(($existing_actions['status'] ?? 'pending_contact') !== ($new_actions['status'] ?? 'pending_contact')) {
            $parts[] = sprintf(
                l('admin_leader_operating_system.leader.ai_plan_history_change_status'),
                $this->get_ai_plan_mentor_status_label((string) ($existing_actions['status'] ?? 'pending_contact')),
                $this->get_ai_plan_mentor_status_label((string) ($new_actions['status'] ?? 'pending_contact'))
            );
        }

        if(trim((string) ($existing_actions['next_action'] ?? '')) !== trim((string) ($new_actions['next_action'] ?? ''))) {
            $parts[] = l('admin_leader_operating_system.leader.ai_plan_history_change_next_action');
        }

        if(trim((string) ($existing_actions['mentor_note'] ?? '')) !== trim((string) ($new_actions['mentor_note'] ?? ''))) {
            $parts[] = l('admin_leader_operating_system.leader.ai_plan_history_change_note');
        }

        if(trim((string) ($existing_actions['ai_guidance'] ?? '')) !== trim((string) ($new_actions['ai_guidance'] ?? ''))) {
            $parts[] = l('admin_leader_operating_system.leader.ai_plan_history_change_guidance');
        }

        if((bool) ($existing_actions['needs_follow_up'] ?? false) !== (bool) ($new_actions['needs_follow_up'] ?? false)) {
            $parts[] = l('admin_leader_operating_system.leader.ai_plan_history_change_follow_up');
        }

        if((bool) ($existing_actions['mentored_this_week'] ?? false) !== (bool) ($new_actions['mentored_this_week'] ?? false)) {
            $parts[] = l('admin_leader_operating_system.leader.ai_plan_history_change_mentored');
        }

        return $parts;
    }

    private function build_ai_plan_mentor_history_entry(string $event_key, array $existing_actions, array $new_actions): ?array {
        $identity = $this->get_ai_plan_mentor_admin_identity();
        $change_parts = $this->build_ai_plan_mentor_history_change_parts($existing_actions, $new_actions);
        $summary = '';

        switch($event_key) {
            case 'follow_up_enabled':
                $summary = l('admin_leader_operating_system.leader.ai_plan_history_follow_up_enabled');
                break;

            case 'follow_up_removed':
                $summary = l('admin_leader_operating_system.leader.ai_plan_history_follow_up_removed');
                break;

            case 'mentored_marked':
                $summary = l('admin_leader_operating_system.leader.ai_plan_history_mentored_marked');
                break;

            case 'mentored_reset':
                $summary = l('admin_leader_operating_system.leader.ai_plan_history_mentored_reset');
                break;

            default:
                if(empty($change_parts)) {
                    return null;
                }

                $summary = l('admin_leader_operating_system.leader.ai_plan_history_updated_actions');
                break;
        }

        return [
            'history_id' => $this->generate_los_outreach_id('los_coaching'),
            'event_key' => $event_key,
            'summary' => $summary,
            'details' => implode(' | ', $change_parts),
            'created_at' => get_date(),
            'admin_id' => $identity['admin_id'],
            'admin_name' => $identity['admin_name'],
        ];
    }

    private function append_ai_plan_mentor_history(\stdClass $preferences, array $history_entry): void {
        $history = $this->get_ai_plan_mentor_history($preferences);
        array_unshift($history, $history_entry);
        $preferences->leader_ai_admin_history = array_slice($history, 0, 24);
    }

    private function update_ai_plan_mentor_actions(int $user_id): void {
        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'preferences']);

        if(!$user) {
            throw new \Exception(l('admin_leader_operating_system.leader.missing'));
        }

        $preferences = $this->get_preferences_object($user->preferences ?? null);
        $existing_actions = $this->get_ai_plan_mentor_actions($preferences);

        $allowed_statuses = ['pending_contact', 'in_progress', 'monitoring', 'resolved'];
        $status = input_clean($_POST['mentor_status'] ?? $existing_actions['status'], 64);
        $status = in_array($status, $allowed_statuses, true) ? $status : $existing_actions['status'];

        $mentor_note = trim(input_clean($_POST['mentor_note'] ?? $existing_actions['mentor_note'], 2000));
        $ai_guidance = trim(input_clean($_POST['mentor_ai_guidance'] ?? $existing_actions['ai_guidance'], 2400));
        $next_action = trim(input_clean($_POST['mentor_next_action'] ?? $existing_actions['next_action'], 280));
        $needs_follow_up = $existing_actions['needs_follow_up'];
        $mentored_this_week = $existing_actions['mentored_this_week'];
        $last_contacted_at = $existing_actions['last_contacted_at'];

        if(isset($_POST['toggle_follow_up'])) {
            $needs_follow_up = !$existing_actions['needs_follow_up'];
        }

        if(isset($_POST['mark_mentored_this_week'])) {
            $mentored_this_week = true;
            $last_contacted_at = get_date();
        }

        if(isset($_POST['reset_mentored_this_week'])) {
            $mentored_this_week = false;
        }

        $new_actions = [
            'status' => $status,
            'needs_follow_up' => $needs_follow_up,
            'mentored_this_week' => $mentored_this_week,
            'mentor_note' => $mentor_note,
            'ai_guidance' => $ai_guidance,
            'next_action' => $next_action,
            'updated_at' => get_date(),
            'last_contacted_at' => $last_contacted_at,
        ];

        $event_key = 'actions_updated';

        if(isset($_POST['toggle_follow_up'])) {
            $event_key = $needs_follow_up ? 'follow_up_enabled' : 'follow_up_removed';
        } elseif(isset($_POST['mark_mentored_this_week'])) {
            $event_key = 'mentored_marked';
        } elseif(isset($_POST['reset_mentored_this_week'])) {
            $event_key = 'mentored_reset';
        }

        $history_entry = $this->build_ai_plan_mentor_history_entry($event_key, $existing_actions, $new_actions);

        $preferences->leader_ai_admin_coaching = (object) [
            'status' => $new_actions['status'],
            'needs_follow_up' => $new_actions['needs_follow_up'],
            'mentored_this_week' => $new_actions['mentored_this_week'],
            'mentor_note' => $new_actions['mentor_note'],
            'ai_guidance' => $new_actions['ai_guidance'],
            'next_action' => $new_actions['next_action'],
            'updated_at' => $new_actions['updated_at'],
            'last_contacted_at' => $new_actions['last_contacted_at'],
        ];

        if($history_entry) {
            $this->append_ai_plan_mentor_history($preferences, $history_entry);
        }

        $this->save_user_preferences($user_id, $preferences);
    }
    /* /Custom code: FC-2026-03-31 */

    private function get_ai_credentials(string $purpose = 'los_leader_detail'): array {
        $api_key = fcc_ai_get_openai_api_key();
        $model = fcc_ai_resolve_model_route($purpose);

        return [
            'api_key' => $api_key,
            'model' => $model,
        ];
    }

    private function get_ai_report_cache_key(int $user_id, string $period_key): string {
        /* Custom code: FC-2026-03-31: Version AI cache after prompt/input changes */
        return 'leader_operating_system_ai_report_v2?user_id=' . $user_id . '&period=' . $period_key;
        /* /Custom code: FC-2026-03-31 */
    }

    private function get_cached_ai_report(int $user_id, string $period_key): ?array {
        $cache_instance = cache()->getItem($this->get_ai_report_cache_key($user_id, $period_key));
        $report = $cache_instance->get();

        return is_array($report) ? $report : null;
    }

    /* Custom code: FC-2026-03-31: Lightweight LOS outreach storage and email workflow */
    private function generate_los_outreach_id(string $prefix): string {
        return $prefix . '_' . md5($prefix . '|' . microtime(true) . '|' . mt_rand());
    }

    private function get_los_outreach_store($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $outreach = $preferences->leader_os_outreach ?? null;

        if(is_object($outreach)) {
            $outreach = (array) $outreach;
        }

        if(!is_array($outreach)) {
            $outreach = [];
        }

        $reports = [];
        foreach((array) ($outreach['reports'] ?? []) as $report) {
            if(is_object($report)) {
                $report = (array) $report;
            }

            if(!is_array($report)) {
                continue;
            }

            $email_body_points = [];
            foreach((array) ($report['email_body_points'] ?? []) as $item) {
                if(is_scalar($item) && trim((string) $item) !== '') {
                    $email_body_points[] = trim((string) $item);
                }
            }

            $reports[] = [
                'report_id' => trim((string) ($report['report_id'] ?? '')),
                'period_key' => trim((string) ($report['period_key'] ?? '30d')),
                'generated_at' => $report['generated_at'] ?? null,
                'admin_id' => (int) ($report['admin_id'] ?? 0),
                'model' => trim((string) ($report['model'] ?? '')),
                'headline' => trim((string) ($report['headline'] ?? '')),
                'executive_summary' => trim((string) ($report['executive_summary'] ?? '')),
                'progress_signal' => trim((string) ($report['progress_signal'] ?? '')),
                'period_comparison_summary' => trim((string) ($report['period_comparison_summary'] ?? '')),
                'email_subject' => trim((string) ($report['email_subject'] ?? '')),
                'email_intro' => trim((string) ($report['email_intro'] ?? '')),
                'email_body_points' => array_slice($email_body_points, 0, 5),
                'email_cta' => trim((string) ($report['email_cta'] ?? '')),
                'admin_action_now' => trim((string) ($report['admin_action_now'] ?? '')),
                'collaborator_action_this_week' => trim((string) ($report['collaborator_action_this_week'] ?? '')),
                'what_to_stop_pushing' => trim((string) ($report['what_to_stop_pushing'] ?? '')),
                'admin_note' => trim((string) ($report['admin_note'] ?? '')),
            ];
        }

        usort($reports, static function($a, $b) {
            return strcmp((string) ($b['generated_at'] ?? ''), (string) ($a['generated_at'] ?? ''));
        });

        $sends = [];
        foreach((array) ($outreach['sends'] ?? []) as $send) {
            if(is_object($send)) {
                $send = (array) $send;
            }

            if(!is_array($send)) {
                continue;
            }

            $sends[] = [
                'send_id' => trim((string) ($send['send_id'] ?? '')),
                'report_id' => trim((string) ($send['report_id'] ?? '')),
                'period_key' => trim((string) ($send['period_key'] ?? '30d')),
                'sent_at' => $send['sent_at'] ?? null,
                'admin_id' => (int) ($send['admin_id'] ?? 0),
                'email_address' => trim((string) ($send['email_address'] ?? '')),
                'subject' => trim((string) ($send['subject'] ?? '')),
                'body_snapshot' => trim((string) ($send['body_snapshot'] ?? '')),
                'status' => trim((string) ($send['status'] ?? 'unknown')),
                'message_id' => trim((string) ($send['message_id'] ?? '')),
                'error_message' => trim((string) ($send['error_message'] ?? '')),
            ];
        }

        usort($sends, static function($a, $b) {
            return strcmp((string) ($b['sent_at'] ?? ''), (string) ($a['sent_at'] ?? ''));
        });

        return [
            'reports' => array_slice($reports, 0, 15),
            'sends' => array_slice($sends, 0, 20),
        ];
    }

    private function store_generated_ai_report(int $user_id, array $report): array {
        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'preferences']);

        if(!$user) {
            return $report;
        }

        $preferences = $this->get_preferences_object($user->preferences ?? null);
        $store = $this->get_los_outreach_store($preferences);
        $report_id = $this->generate_los_outreach_id('los_report');

        array_unshift($store['reports'], [
            'report_id' => $report_id,
            'period_key' => (string) ($report['period_key'] ?? '30d'),
            'generated_at' => $report['generated_at'] ?? get_date(),
            'admin_id' => (int) ($this->user->user_id ?? 0),
            'model' => (string) ($report['model'] ?? ''),
            'headline' => (string) ($report['headline'] ?? ''),
            'executive_summary' => (string) ($report['executive_summary'] ?? ''),
            'progress_signal' => (string) ($report['progress_signal'] ?? ''),
            'period_comparison_summary' => (string) ($report['period_comparison_summary'] ?? ''),
            'email_subject' => (string) ($report['email_subject'] ?? ''),
            'email_intro' => (string) ($report['email_intro'] ?? ''),
            'email_body_points' => $report['email_body_points'] ?? [],
            'email_cta' => (string) ($report['email_cta'] ?? ''),
            'admin_action_now' => (string) ($report['admin_action_now'] ?? ''),
            'collaborator_action_this_week' => (string) ($report['collaborator_action_this_week'] ?? ''),
            'what_to_stop_pushing' => (string) ($report['what_to_stop_pushing'] ?? ''),
            'admin_note' => (string) ($report['admin_note'] ?? ''),
        ]);

        $preferences->leader_os_outreach = (object) [
            'reports' => array_slice($store['reports'], 0, 15),
            'sends' => $store['sends'],
        ];

        $this->save_user_preferences($user_id, $preferences);

        $report['report_id'] = $report_id;

        return $report;
    }

    private function store_ai_report_send(int $user_id, array $send_entry): void {
        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'preferences']);

        if(!$user) {
            return;
        }

        $preferences = $this->get_preferences_object($user->preferences ?? null);
        $store = $this->get_los_outreach_store($preferences);

        array_unshift($store['sends'], $send_entry);

        $preferences->leader_os_outreach = (object) [
            'reports' => $store['reports'],
            'sends' => array_slice($store['sends'], 0, 20),
        ];

        $this->save_user_preferences($user_id, $preferences);
    }

    private function build_ai_email_review_body(array $report): string {
        $body_sections = [];

        if(!empty($report['email_intro'])) {
            $body_sections[] = trim((string) $report['email_intro']);
        }

        $body_points = [];
        foreach((array) ($report['email_body_points'] ?? []) as $item) {
            if(is_scalar($item) && trim((string) $item) !== '') {
                $body_points[] = '- ' . trim((string) $item);
            }
        }

        if(!empty($body_points)) {
            $body_sections[] = implode("\n", $body_points);
        }

        if(!empty($report['email_cta'])) {
            $body_sections[] = trim((string) $report['email_cta']);
        }

        return trim(implode("\n\n", $body_sections));
    }

    private function get_valid_period_key(?string $period_key): string {
        $period_key = trim((string) $period_key);

        return in_array($period_key, ['7d', '30d', '90d'], true) ? $period_key : '30d';
    }

    private function get_detail_and_period_payload_or_fail(int $user_id, string $period_key): array {
        $detail = $this->get_detail_payload($user_id);

        if(!$detail) {
            Response::json(l('admin_leader_operating_system.leader.missing'), 'error');
        }

        $selected_payload = $detail['periods'][$period_key] ?? null;

        if(!$selected_payload) {
            Response::json(l('admin_leader_operating_system.leader.missing'), 'error');
        }

        return [$detail, $selected_payload];
    }

    private function get_ajax_detail_response_payload(array $detail, string $period_key): array {
        $ai_report = $this->get_cached_ai_report((int) ($detail['user_id'] ?? 0), $period_key);

        return [
            'period_key' => $period_key,
            'selected_payload' => $detail['periods'][$period_key] ?? null,
            /* Custom code: FC-2026-03-31: Expose anomaly radar in AJAX payload */
            'behavior_anomaly' => $this->get_behavior_anomaly_payload($detail['periods'][$period_key] ?? [], $detail['periods'] ?? [], $detail['app_structure'] ?? [], $period_key),
            /* /Custom code: FC-2026-03-31 */
            /* Custom code: FC-2026-03-31: Expose Phase 6 fraud payload in AJAX detail */
            'fraud_intelligence' => $detail['fraud_intelligence'][$period_key] ?? $this->get_fraud_intelligence_payload((int) ($detail['user_id'] ?? 0), $period_key),
            /* /Custom code: FC-2026-03-31 */
            'ai_report' => $ai_report,
            'los_outreach' => $this->get_los_outreach_payload((int) ($detail['user_id'] ?? 0), $period_key, $ai_report, (string) ($detail['email'] ?? '')),
        ];
    }

    private function get_los_outreach_payload(int $user_id, string $period_key, ?array $active_report, string $default_email): array {
        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'preferences']);
        $store = $this->get_los_outreach_store($user->preferences ?? null);
        $reports = array_values(array_filter($store['reports'], static function($report) use ($period_key) {
            return (string) ($report['period_key'] ?? '') === $period_key;
        }));
        $sends = array_values(array_filter($store['sends'], static function($send) use ($period_key) {
            return (string) ($send['period_key'] ?? '') === $period_key;
        }));

        $report_version_map = [];
        $report_count = count($reports);

        foreach($reports as $index => &$report) {
            $report['version_number'] = max(1, $report_count - $index);
            $report_version_map[(string) ($report['report_id'] ?? '')] = $report['version_number'];
        }
        unset($report);

        foreach($sends as &$send) {
            $report_id = (string) ($send['report_id'] ?? '');
            $send['report_version_number'] = $report_id !== '' && isset($report_version_map[$report_id]) ? $report_version_map[$report_id] : null;
        }
        unset($send);

        $resolved_active_report = $active_report;

        if($resolved_active_report && !empty($resolved_active_report['report_id'])) {
            foreach($reports as $stored_report) {
                if((string) ($stored_report['report_id'] ?? '') === (string) ($resolved_active_report['report_id'] ?? '')) {
                    $resolved_active_report = $stored_report;
                    break;
                }
            }
        }

        if($resolved_active_report && empty($resolved_active_report['report_id'])) {
            foreach($reports as $stored_report) {
                if(
                    (string) ($stored_report['generated_at'] ?? '') === (string) ($resolved_active_report['generated_at'] ?? '')
                    && (string) ($stored_report['email_subject'] ?? '') === (string) ($resolved_active_report['email_subject'] ?? '')
                ) {
                    $resolved_active_report = $stored_report;
                    break;
                }
            }
        }

        $latest_report = $resolved_active_report ?: ($reports[0] ?? null);

        return [
            'latest_report' => $latest_report,
            'latest_send' => $sends[0] ?? null,
            'report_history' => array_slice($reports, 0, 6),
            'send_history' => array_slice($sends, 0, 8),
            'draft_email' => $default_email,
            'draft_subject' => (string) ($latest_report['email_subject'] ?? ''),
            'draft_body' => $latest_report ? $this->build_ai_email_review_body($latest_report) : '',
        ];
    }

    private function send_ai_report_email(int $user_id, array $detail, string $period_key, ?array $active_report): void {
        $outreach_payload = $this->get_los_outreach_payload($user_id, $period_key, $active_report, (string) ($detail['email'] ?? ''));
        $resolved_report = $outreach_payload['latest_report'] ?? null;
        $recipient_email = trim((string) ($_POST['outreach_email'] ?? $outreach_payload['draft_email'] ?? ''));
        $subject = trim((string) ($_POST['outreach_subject'] ?? $outreach_payload['draft_subject'] ?? ''));
        $body = (string) ($_POST['outreach_body'] ?? $outreach_payload['draft_body'] ?? '');
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = mb_substr(trim(strip_tags($body)), 0, 12000);

        if(!$resolved_report) {
            throw new \Exception(l('admin_leader_operating_system.leader.outreach_error_missing_report'));
        }

        if(!$recipient_email || !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception(l('admin_leader_operating_system.leader.outreach_error_recipient'));
        }

        if($subject === '') {
            throw new \Exception(l('admin_leader_operating_system.leader.outreach_error_subject'));
        }

        if($body === '') {
            throw new \Exception(l('admin_leader_operating_system.leader.outreach_error_body'));
        }

        $transport_result = send_mail($recipient_email, $subject, nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')), [
            'return_transport_result' => true,
        ]);

        if(empty($transport_result->success)) {
            throw new \Exception($this->sanitize_ai_string($transport_result->ErrorInfo ?? $transport_result->response_body ?? '', 280) ?: l('admin_leader_operating_system.leader.outreach_send_failed'));
        }

        $this->store_ai_report_send($user_id, [
            'send_id' => $this->generate_los_outreach_id('los_send'),
            'report_id' => (string) ($resolved_report['report_id'] ?? ''),
            'period_key' => $period_key,
            'sent_at' => get_date(),
            'admin_id' => (int) ($this->user->user_id ?? 0),
            'email_address' => $recipient_email,
            'subject' => $subject,
            'body_snapshot' => $body,
            'status' => 'sent',
            'message_id' => (string) ($transport_result->message_id ?? ''),
            'error_message' => '',
        ]);
    }
    /* /Custom code: FC-2026-03-31 */

    private function sanitize_ai_string($value, int $max_length = 320): string {
        if(!is_scalar($value)) {
            return '';
        }

        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return mb_substr($value, 0, $max_length);
    }

    private function normalize_ai_list($value, int $max_items = 5, int $max_length = 220): array {
        if(!is_array($value)) {
            return [];
        }

        $items = [];

        foreach($value as $item) {
            $normalized_item = $this->sanitize_ai_string($item, $max_length);

            if($normalized_item === '') {
                continue;
            }

            $items[] = $normalized_item;

            if(count($items) >= $max_items) {
                break;
            }
        }

        return $items;
    }

    private function build_ai_report_input(array $detail, array $payload, string $period_key): array {
        $behavior_anomaly = $this->get_behavior_anomaly_payload($payload, $detail['periods'] ?? [], $detail['app_structure'] ?? [], $period_key);
        $fraud_intelligence = $detail['fraud_intelligence'][$period_key] ?? $this->get_fraud_intelligence_payload((int) ($detail['user_id'] ?? 0), $period_key);
        $ai_plan_admin = $detail['ai_plan_admin'] ?? [];
        $mentor_actions = $ai_plan_admin['mentor_actions'] ?? [];
        $consistency = $this->get_consistency_payload($payload, $ai_plan_admin);
        $coaching_roi = !empty($detail['score_history'][$period_key]) ? $this->get_coaching_roi_payload($detail['score_history'][$period_key], $ai_plan_admin) : [];
        $billing_model = new Billing();
        $billing_summary = $billing_model->get_user_billing_summary((int) ($detail['user_id'] ?? 0));
        $stripe_billing = $billing_summary ? $this->get_stripe_billing_payload($detail, $billing_summary) : [];
        $period_comparison = [];
        $chat_intelligence = fcc_ai_get_user_chat_intelligence_payload(
            (int) ($detail['user_id'] ?? 0),
            $this->get_period_start_datetime($this->get_period_days($period_key)),
            6,
            \Altum\Language::$code
        );

        foreach(['7d', '30d', '90d'] as $comparison_key) {
            $comparison_payload = $detail['periods'][$comparison_key] ?? [];

            $period_comparison[$comparison_key] = [
                'period_label' => l('admin_leader_operating_system.period_' . $comparison_key),
                'leader_os_score' => (int) ($comparison_payload['leader_os_score'] ?? 0),
                'status_label' => (string) ($comparison_payload['status_label'] ?? ''),
                'total_clicks' => (int) ($comparison_payload['clicks_total_period'] ?? 0),
                'webshop_clicks' => (int) ($comparison_payload['forever_shop_clicks_period'] ?? 0),
                'registrations' => (int) ($comparison_payload['forever_registration_clicks_period'] ?? 0),
                'blog_clicks' => (int) ($comparison_payload['blog_forever']['total_clicks'] ?? 0),
                'app_visits' => (int) ($comparison_payload['app_visits_total'] ?? 0),
                'registration_rate_percent' => (float) ($comparison_payload['registration_rate_percent'] ?? 0),
                'risk_score' => (int) ($comparison_payload['risk_score'] ?? 0),
                'growth_percent' => $comparison_payload['growth_percent'] ?? null,
            ];
        }

        return [
            'period_key' => $period_key,
            'period_label' => l('admin_leader_operating_system.period_' . $period_key),
            'leader' => [
                'user_id' => (int) ($detail['user_id'] ?? 0),
                'name' => (string) ($detail['name'] ?? ''),
                'email' => (string) ($detail['email'] ?? ''),
                'forever_id' => (string) ($detail['forever_id'] ?? '-'),
            ],
            /* Custom code: FC-2026-03-31: Include current app structure in AI input */
            'app_structure' => [
                'total_apps' => (int) ($detail['app_structure']['total_apps'] ?? 0),
                'top_app_url' => (string) ($detail['app_structure']['top_app_url'] ?? '-'),
                'top_app_public_url' => (string) ($detail['app_structure']['top_app_public_url'] ?? ''),
                'top_app_total_blocks' => (int) ($detail['app_structure']['top_app_total_blocks'] ?? 0),
                'block_mix' => $detail['app_structure']['block_mix'] ?? [],
                'priority_blocks' => $detail['app_structure']['priority_blocks'] ?? [],
                'composition_score' => (int) ($detail['app_structure']['composition_score'] ?? 0),
                'cta_audit' => $detail['app_structure']['cta_audit'] ?? [],
                'trust_audit' => $detail['app_structure']['trust_audit'] ?? [],
                'content_audit' => $detail['app_structure']['content_audit'] ?? [],
                'composition_findings' => $detail['app_structure']['composition_findings'] ?? [],
            ],
            /* /Custom code: FC-2026-03-31 */
            'scores' => [
                'leader_os_score' => (int) ($payload['leader_os_score'] ?? 0),
                'performance_score' => (int) ($payload['performance_score'] ?? 0),
                'momentum_score' => (int) ($payload['momentum_score'] ?? 0),
                'conversion_score' => (int) ($payload['conversion_score'] ?? 0),
                'risk_score' => (int) ($payload['risk_score'] ?? 0),
                'opportunity_score' => (int) ($payload['opportunity_score'] ?? 0),
            ],
            'status' => [
                'status_key' => (string) ($payload['status_key'] ?? 'stable'),
                'status_label' => (string) ($payload['status_label'] ?? ''),
                'qualified' => (bool) ($payload['qualified'] ?? false),
            ],
            'period_comparison' => $period_comparison,
            'metrics' => [
                'total_clicks' => (int) ($payload['clicks_total_period'] ?? 0),
                'webshop_clicks' => (int) ($payload['forever_shop_clicks_period'] ?? 0),
                'registrations' => (int) ($payload['forever_registration_clicks_period'] ?? 0),
                'growth_percent' => $payload['growth_percent'],
                'growth_difference' => (int) ($payload['growth_difference'] ?? 0),
                'shop_share_percent' => (float) ($payload['shop_share_percent'] ?? 0),
                'registration_rate_percent' => (float) ($payload['registration_rate_percent'] ?? 0),
                'avg_daily_shop_clicks' => (float) ($payload['avg_daily_shop_clicks'] ?? 0),
                'active_days_total' => (int) ($payload['active_days_total'] ?? 0),
            ],
            'funnel' => [
                'total_funnels' => (int) ($payload['funnel']['total_funnels'] ?? 0),
                'active_funnels' => (int) ($payload['funnel']['active_funnels'] ?? 0),
                'unique_clicks' => (int) ($payload['funnel']['unique_clicks'] ?? 0),
                'total_leads' => (int) ($payload['funnel']['total_leads'] ?? 0),
                'conversion_rate' => (float) ($payload['funnel']['conversion_rate'] ?? 0),
                'top_funnel_name' => (string) ($payload['funnel']['top_funnel_name'] ?? '-'),
                'top_funnel_objective' => (string) ($payload['funnel']['top_funnel_objective'] ?? '-'),
            ],
            'signals' => [
                'top_country' => (string) ($payload['top_country_label'] ?? '-'),
                'top_source' => (string) ($payload['top_source_label'] ?? '-'),
                'top_device' => (string) ($payload['top_device_label'] ?? '-'),
                'top_language' => (string) ($payload['top_language_label'] ?? '-'),
                'next_step' => (string) ($payload['next_step'] ?? ''),
            ],
            'consistency' => [
                'score' => (int) ($consistency['score'] ?? 0),
                'state_label' => (string) ($consistency['state_label'] ?? ''),
                'state_key' => (string) ($consistency['state_key'] ?? ''),
                'completed_checkins' => (int) ($consistency['completed_checkins'] ?? 0),
                'recent_outcomes' => (int) ($consistency['recent_outcomes'] ?? 0),
            ],
            'coaching' => [
                'status_label' => $this->get_ai_plan_mentor_status_label((string) ($mentor_actions['status'] ?? 'pending_contact')),
                'needs_follow_up' => (bool) ($mentor_actions['needs_follow_up'] ?? false),
                'mentored_this_week' => (bool) ($mentor_actions['mentored_this_week'] ?? false),
                'next_action' => (string) ($mentor_actions['next_action'] ?? ''),
                'mentor_note' => (string) ($mentor_actions['mentor_note'] ?? ''),
                'ai_guidance' => (string) ($mentor_actions['ai_guidance'] ?? ''),
                'last_contacted_at' => $mentor_actions['last_contacted_at'] ?? null,
                'signal_label' => (string) ($coaching_roi['signal_label'] ?? ''),
                'score_delta' => $coaching_roi['score_delta'] ?? null,
                'days_since_touch' => $coaching_roi['days_since_touch'] ?? null,
            ],
            'billing' => [
                'plan_name' => (string) ($stripe_billing['plan_name'] ?? ''),
                'status' => (string) ($stripe_billing['status'] ?? ''),
                'billing_state' => (string) ($stripe_billing['billing_state'] ?? ''),
                'failed_attempts' => (int) ($stripe_billing['failed_attempts'] ?? 0),
                'cancel_at_period_end' => (bool) ($stripe_billing['cancel_at_period_end'] ?? false),
                'current_period_end' => $stripe_billing['current_period_end'] ?? null,
                'grace_until' => $stripe_billing['grace_until'] ?? null,
            ],
            'chat_intelligence' => [
                'headline' => (string) ($chat_intelligence['headline'] ?? ''),
                'executive_summary' => (string) ($chat_intelligence['executive_summary'] ?? ''),
                'executive_report' => [
                    'headline' => (string) ($chat_intelligence['executive_report']['headline'] ?? ''),
                    'summary' => (string) ($chat_intelligence['executive_report']['summary'] ?? ''),
                    'alerts' => array_values($chat_intelligence['executive_report']['alerts'] ?? []),
                    'opportunities' => array_values($chat_intelligence['executive_report']['opportunities'] ?? []),
                    'next_moves' => array_values($chat_intelligence['executive_report']['next_moves'] ?? []),
                ],
                'coach_summary' => (string) ($chat_intelligence['coach']['summary'] ?? ''),
                'coach_blocker' => (string) ($chat_intelligence['coach']['blocker'] ?? ''),
                'coach_next_admin_move' => (string) ($chat_intelligence['coach']['next_admin_move'] ?? ''),
                'public_summary' => (string) ($chat_intelligence['public_ai']['summary'] ?? ''),
                'public_blocker' => (string) ($chat_intelligence['public_ai']['blocker'] ?? ''),
                'public_next_admin_move' => (string) ($chat_intelligence['public_ai']['next_admin_move'] ?? ''),
                'strengths' => array_values($chat_intelligence['strengths'] ?? []),
                'weaknesses' => array_values($chat_intelligence['weaknesses'] ?? []),
                'admin_changes' => array_values($chat_intelligence['admin_changes'] ?? []),
                'coach_top_topics' => array_map(static function($row) {
                    return [
                        'label' => (string) ($row['label'] ?? ''),
                        'total' => (int) ($row['total'] ?? 0),
                    ];
                }, array_slice($chat_intelligence['coach']['top_topics'] ?? [], 0, 4)),
                'public_top_topics' => array_map(static function($row) {
                    return [
                        'label' => (string) ($row['label'] ?? ''),
                        'total' => (int) ($row['total'] ?? 0),
                    ];
                }, array_slice($chat_intelligence['public_ai']['top_topics'] ?? [], 0, 4)),
                'risky_threads' => array_map(static function($row) {
                    return [
                        'topic' => (string) ($row['primary_topic_label'] ?? ''),
                        'summary' => (string) ($row['summary'] ?? ''),
                        'core_issue' => (string) ($row['core_issue'] ?? ''),
                        'quality' => (string) ($row['quality_badge']['label'] ?? ''),
                        'outcome' => (string) ($row['outcome_badge']['label'] ?? ''),
                        'suspicion' => (string) ($row['suspicion']['top_label'] ?? ''),
                    ];
                }, array_slice($chat_intelligence['risky_threads'] ?? [], 0, 4)),
            ],
            /* Custom code: FC-2026-03-31: Feed anomaly radar into AI input */
            'anomaly_radar' => [
                'score' => (int) ($behavior_anomaly['score'] ?? 0),
                'level_key' => (string) ($behavior_anomaly['level_key'] ?? 'stable'),
                'level_label' => (string) ($behavior_anomaly['level_label'] ?? ''),
                'top_concern' => (string) ($behavior_anomaly['top_concern'] ?? ''),
                'signals' => array_map(static function($signal) {
                    return [
                        'label' => (string) ($signal['label'] ?? ''),
                        'text' => (string) ($signal['text'] ?? ''),
                        'action' => (string) ($signal['action'] ?? ''),
                    ];
                }, array_slice($behavior_anomaly['signals'] ?? [], 0, 5)),
            ],
            /* /Custom code: FC-2026-03-31 */
            /* Custom code: FC-2026-03-31: Feed Phase 6 fraud intelligence into AI input */
            'fraud_intelligence' => [
                'score' => (int) ($fraud_intelligence['score'] ?? 0),
                'level_key' => (string) ($fraud_intelligence['level_key'] ?? 'stable'),
                'level_label' => (string) ($fraud_intelligence['level_label'] ?? ''),
                'top_concern' => (string) ($fraud_intelligence['top_concern'] ?? ''),
                'clusters_total' => (int) ($fraud_intelligence['clusters_total'] ?? 0),
                'retention_days' => (int) ($fraud_intelligence['retention_days'] ?? fc_get_los_fraud_event_retention_days()),
                'clusters' => array_map(static function($cluster) {
                    return [
                        'score' => (int) ($cluster['score'] ?? 0),
                        'label' => (string) ($cluster['label'] ?? ''),
                        'text' => (string) ($cluster['text'] ?? ''),
                        'action' => (string) ($cluster['action'] ?? ''),
                    ];
                }, array_slice($fraud_intelligence['clusters'] ?? [], 0, 3)),
            ],
            /* /Custom code: FC-2026-03-31 */
            'breakdowns' => [
                'top_countries' => $payload['top_countries'] ?? [],
                'top_sources' => $payload['top_sources'] ?? [],
                'top_devices' => $payload['top_devices'] ?? [],
                'top_browsers' => $payload['top_browsers'] ?? [],
            ],
        ];
    }

    /* Custom code: FC-2026-03-31: LOS Phase 5 AJAX endpoints */
    public function leader_analytics_ajax() {

        session_write_close();

        if($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw_404();
        }

        $user_id = (int) ($_GET['user_id'] ?? 0);
        $period_key = $this->get_valid_period_key($_GET['period'] ?? '30d');

        if(!$user_id) {
            Response::json(l('global.error_message.basic'), 'error');
        }

        [$detail] = $this->get_detail_and_period_payload_or_fail($user_id, $period_key);

        Response::json('', 'success', $this->get_ajax_detail_response_payload($detail, $period_key));

    }

    public function generate_ai_report_ajax() {

        session_write_close();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw_404();
        }

        if(!\Altum\Csrf::check()) {
            Response::json(l('global.error_message.invalid_csrf_token'), 'error');
        }

        $user_id = (int) ($_POST['user_id'] ?? 0);
        $period_key = $this->get_valid_period_key($_POST['period'] ?? '30d');
        $force_refresh = isset($_POST['force_refresh']) && (int) $_POST['force_refresh'] === 1;

        if(!$user_id) {
            Response::json(l('global.error_message.basic'), 'error');
        }

        try {
            [$detail, $selected_payload] = $this->get_detail_and_period_payload_or_fail($user_id, $period_key);
            $report = $this->generate_ai_report($detail, $selected_payload, $period_key, $force_refresh);

            Response::json('', 'success', [
                'report' => $report,
                'payload' => $this->get_ajax_detail_response_payload($detail, $period_key),
            ]);
        } catch(\Throwable $exception) {
            Response::json($this->sanitize_ai_string($exception->getMessage(), 280) ?: l('admin_leader_operating_system.leader.ai_error_request_failed'), 'error');
        }

    }

    public function send_ai_report_ajax() {

        session_write_close();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw_404();
        }

        if(!\Altum\Csrf::check()) {
            Response::json(l('global.error_message.invalid_csrf_token'), 'error');
        }

        $user_id = (int) ($_POST['user_id'] ?? 0);
        $period_key = $this->get_valid_period_key($_POST['period'] ?? '30d');

        if(!$user_id) {
            Response::json(l('global.error_message.basic'), 'error');
        }

        try {
            [$detail] = $this->get_detail_and_period_payload_or_fail($user_id, $period_key);
            $active_report = $this->get_cached_ai_report((int) $detail['user_id'], $period_key);
            $this->send_ai_report_email((int) $detail['user_id'], $detail, $period_key, $active_report);

            Response::json(l('admin_leader_operating_system.leader.outreach_send_success'), 'success', $this->get_ajax_detail_response_payload($detail, $period_key));
        } catch(\Throwable $exception) {
            Response::json($this->sanitize_ai_string($exception->getMessage(), 280) ?: l('admin_leader_operating_system.leader.outreach_send_failed'), 'error');
        }

    }

    public function report_history_ajax() {

        session_write_close();

        if($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw_404();
        }

        $user_id = (int) ($_GET['user_id'] ?? 0);
        $period_key = $this->get_valid_period_key($_GET['period'] ?? '30d');

        if(!$user_id) {
            Response::json(l('global.error_message.basic'), 'error');
        }

        [$detail] = $this->get_detail_and_period_payload_or_fail($user_id, $period_key);
        $ai_report = $this->get_cached_ai_report((int) $detail['user_id'], $period_key);
        $outreach = $this->get_los_outreach_payload((int) $detail['user_id'], $period_key, $ai_report, (string) ($detail['email'] ?? ''));

        Response::json('', 'success', [
            'latest_report' => $outreach['latest_report'] ?? null,
            'latest_send' => $outreach['latest_send'] ?? null,
            'report_history' => $outreach['report_history'] ?? [],
            'send_history' => $outreach['send_history'] ?? [],
        ]);

    }
    /* /Custom code: FC-2026-03-31 */

    private function validate_ai_report_response(array $report): array {
        /* Custom code: FC-2026-03-31: Allow fuller AI report while keeping sectioned UI */
        $headline = $this->sanitize_ai_string($report['headline'] ?? '', 140);
        $executive_summary = $this->sanitize_ai_string($report['executive_summary'] ?? '', 900);
        $progress_signal = $this->sanitize_ai_string($report['progress_signal'] ?? '', 220);
        $period_comparison_summary = $this->sanitize_ai_string($report['period_comparison_summary'] ?? '', 320);
        $primary_risks = $this->normalize_ai_list($report['primary_risks'] ?? [], 4, 220);
        $opportunities = $this->normalize_ai_list($report['opportunities'] ?? [], 4, 220);
        $admin_action_now = $this->sanitize_ai_string($report['admin_action_now'] ?? '', 240);
        $collaborator_action_this_week = $this->sanitize_ai_string($report['collaborator_action_this_week'] ?? '', 240);
        $what_to_stop_pushing = $this->sanitize_ai_string($report['what_to_stop_pushing'] ?? '', 240);
        $next_30_days = $this->normalize_ai_list($report['next_30_days'] ?? [], 5, 220);
        $admin_note = $this->sanitize_ai_string($report['admin_note'] ?? '', 260);
        $email_subject = $this->sanitize_ai_string($report['email_subject'] ?? '', 160);
        $email_intro = $this->sanitize_ai_string($report['email_intro'] ?? '', 320);
        $email_body_points = $this->normalize_ai_list($report['email_body_points'] ?? [], 5, 240);
        $email_cta = $this->sanitize_ai_string($report['email_cta'] ?? '', 200);
        /* /Custom code: FC-2026-03-31 */

        if($headline === '' || $executive_summary === '' || empty($next_30_days) || $email_subject === '') {
            throw new \Exception(l('admin_leader_operating_system.leader.ai_error_invalid_response'));
        }

        if($admin_action_now === '') {
            $admin_action_now = $opportunities[0] ?? ($next_30_days[0] ?? '');
        }

        if($collaborator_action_this_week === '') {
            $collaborator_action_this_week = $next_30_days[0] ?? '';
        }

        return [
            'headline' => $headline,
            'executive_summary' => $executive_summary,
            'progress_signal' => $progress_signal,
            'period_comparison_summary' => $period_comparison_summary,
            'primary_risks' => $primary_risks,
            'opportunities' => $opportunities,
            'admin_action_now' => $admin_action_now,
            'collaborator_action_this_week' => $collaborator_action_this_week,
            'what_to_stop_pushing' => $what_to_stop_pushing,
            'next_30_days' => $next_30_days,
            'admin_note' => $admin_note,
            'email_subject' => $email_subject,
            'email_intro' => $email_intro,
            'email_body_points' => $email_body_points,
            'email_cta' => $email_cta,
        ];
    }

    private function generate_ai_report(array $detail, array $payload, string $period_key, bool $force_refresh = false): array {
        $user_id = (int) ($detail['user_id'] ?? 0);

        if(!$force_refresh) {
            $cached_report = $this->get_cached_ai_report($user_id, $period_key);

            if($cached_report) {
                return $cached_report;
            }
        }

        $credentials = $this->get_ai_credentials();

        if($credentials['api_key'] === '') {
            throw new \Exception(l('admin_leader_operating_system.leader.ai_error_missing_api_key'));
        }

        $ai_input = $this->build_ai_report_input($detail, $payload, $period_key);

        $response = \Unirest\Request::post(
            'https://api.openai.com/v1/chat/completions',
            [
                'Authorization' => 'Bearer ' . get_random_line_from_text($credentials['api_key']),
                'Content-Type' => 'application/json',
            ],
            \Unirest\Request\Body::json([
                'model' => $credentials['model'],
                'messages' => [
                    [
                        'role' => 'system',
                        /* Custom code: FC-2026-03-31: Prompt AI to analyze current setup instead of parroting tactics */
                        'content' => 'Pisi iskljucivo na hrvatskom. Ti si strucnjak za analizu poslovanja Forever Card Club suradnika. Vrati samo valjan JSON bez markdowna i bez dodatnih kljuceva.'
                        /* /Custom code: FC-2026-03-31 */
                    ],
                    [
                        'role' => 'user',
                        /* Custom code: FC-2026-03-31: Require data-grounded recommendations from actual app structure and analytics */
                        'content' => implode("\n\n", [
                            'Analiziraj stvarno poslovanje suradnika na temelju analytics payload-a, strukture postojecih Forever Card aplikacija i blokova koje vec koristi.',
                            'Zakljuci sto treba promijeniti, sto zadrzati i koje parametre treba uzeti u obzir prije preporuke.',
                            'Vrati samo valjan JSON s tocnim kljucevima: headline, executive_summary, progress_signal, period_comparison_summary, primary_risks, opportunities, admin_action_now, collaborator_action_this_week, what_to_stop_pushing, next_30_days, admin_note, email_subject, email_intro, email_body_points, email_cta.',
                            'Pravila:',
                            '- primary_risks, opportunities, next_30_days i email_body_points moraju biti polja kratkih, konkretnih stringova.',
                            '- progress_signal mora biti 1 kratka recenica koja odmah govori ide li suradnik prema rastu, stagnaciji ili padu.',
                            '- period_comparison_summary mora jasno usporediti 7d, 30d i 90d i objasniti smjer, ne samo nabrojati brojke.',
                            '- admin_action_now mora biti 1 vrlo konkretna akcija koju admin treba napraviti odmah.',
                            '- collaborator_action_this_week mora biti 1 vrlo konkretna tjedna obveza za suradnika.',
                            '- what_to_stop_pushing mora reci sto sada ne treba gurati jer razvodnjava rezultat ili fokus.',
                            '- admin_note mora biti kratka interna biljeska za admina, ne poruka za korisnika.',
                            '- Nemoj samo ponavljati unaprijed zadane taktike. Prvo analiziraj postojecu strukturu aplikacija i blokova, promet, izvore, zemlje, uredaje i funnel podatke, pa tek onda predlozi sto mijenjati.',
                            '- Ako suradnik vec koristi odredene blokove ili funnel, procijeni jesu li dobro iskoristeni i sto nedostaje.',
                            '- Ako promet dolazi iz odredenih izvora, predlozi koje drustvene mreze i koji format sadrzaja imaju najvise smisla. Ako nema dovoljno signala, to jasno reci i predlozi sto testirati.',
                            '- U preporukama mozes koristiti Forever Card pristup kada ima smisla: story s ugradenim linkom na aplikaciju, posebna aplikacija za odredeni proizvod, funnel za prodaju ili regrutaciju, te direktne poruke ljudima koji reagiraju na sadrzaj. Ali to koristi samo ako podaci i postojeca struktura to podupiru.',
                            '- Uvazi da vecina suradnika radi kroz izgradnju osobnog brenda i specificne modele rada.',
                            '- Uvazi consistency, AI check-in / plan / outcome disciplinu, coaching status i billing health ako oni objasnjavaju pad ili zastoj.',
                            '- Nemoj davati genericke savjete. Povezi preporuke s onime sto vec postoji i onime sto nedostaje.',
                            '- Nemoj koristiti markdown, code blockove ni dodatne kljuceve.',
                            '- Izlaz ne mora biti kraci, ali treba biti pregledan i dobro podijeljen po sekcijama.',
                            'Input JSON: ' . json_encode($ai_input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ])
                        /* /Custom code: FC-2026-03-31 */
                    ],
                ],
            ])
        );

        if($response->code >= 400) {
            throw new \Exception($response->body->error->message ?? l('admin_leader_operating_system.leader.ai_error_request_failed'));
        }

        $content = trim((string) ($response->body->choices[0]->message->content ?? ''));

        if(substr($content, 0, 3) === '```') {
            $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);
        }

        $decoded_report = json_decode($content, true);

        if(!is_array($decoded_report)) {
            throw new \Exception(l('admin_leader_operating_system.leader.ai_error_invalid_response'));
        }

        $report = $this->validate_ai_report_response($decoded_report);
        $report['generated_at'] = get_date();
        $report['model'] = $credentials['model'];
        $report['period_key'] = $period_key;
        $report = $this->store_generated_ai_report($user_id, $report);

        $cache_item = cache()->getItem($this->get_ai_report_cache_key($user_id, $period_key));
        $cache_item
            ->set($report)
            ->expiresAfter(86400)
            ->addTag('leader_os_ai_report')
            ->addTag('user_id=' . $user_id);

        cache()->save($cache_item);

        return $report;
    }

    public function index() {
        $allowed_periods = ['7d', '30d', '90d'];
        $selected_period = isset($_GET['period']) && in_array($_GET['period'], $allowed_periods, true) ? $_GET['period'] : '30d';
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        $detail = $user_id > 0 ? $this->get_detail_payload($user_id) : null;
        $billing_model = new Billing();
        $billing_summary = $detail ? $billing_model->get_user_billing_summary((int) $detail['user_id']) : null;

        if($detail && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_featured_profile_admin'])) {
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            } else {
                try {
                    $this->save_featured_profile_admin((int) $detail['user_id']);
                    Alerts::add_success('FCC sponsor profil je spremljen i odmah osvježen na javnim stranicama.');
                } catch(\Throwable $exception) {
                    Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 280) ?: l('global.error_message.basic'));
                }
            }

            redirect('admin/leader-operating-system-leader?user_id=' . $user_id . '&period=' . $selected_period . '#leader-os-featured-profile');
        }

        if($detail && $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_mentor_actions']) || isset($_POST['toggle_follow_up']) || isset($_POST['mark_mentored_this_week']) || isset($_POST['reset_mentored_this_week']))) {
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            } else {
                try {
                    $this->update_ai_plan_mentor_actions((int) $detail['user_id']);
                    Alerts::add_success(l('admin_leader_operating_system.leader.ai_plan_mentor_actions_saved'));
                } catch(\Throwable $exception) {
                    Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 280) ?: l('global.error_message.basic'));
                }
            }

            redirect('admin/leader-operating-system-leader?user_id=' . $user_id . '&period=' . $selected_period . '#leader-os-ai-report');
        }

        if($detail && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_ai_feedback'])) {
            $this->handle_fcc_ai_feedback_resolve_action($user_id, $selected_period);
        }

        if($detail && $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['generate_ai_report']) || isset($_POST['regenerate_ai_report']))) {
            if(!\Altum\Csrf::check()) {
                \Altum\Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            } else {
                try {
                    $selected_payload = $detail['periods'][$selected_period] ?? null;

                    if(!$selected_payload) {
                        throw new \Exception(l('admin_leader_operating_system.leader.missing'));
                    }

                    $force_refresh = isset($_POST['regenerate_ai_report']);
                    $this->generate_ai_report($detail, $selected_payload, $selected_period, $force_refresh);

                    \Altum\Alerts::add_success($force_refresh ? l('admin_leader_operating_system.leader.ai_success_regenerated') : l('admin_leader_operating_system.leader.ai_success_generated'));
                } catch(\Throwable $exception) {
                    \Altum\Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 280) ?: l('admin_leader_operating_system.leader.ai_error_request_failed'));
                }
            }

            redirect('admin/leader-operating-system-leader?user_id=' . $user_id . '&period=' . $selected_period . '#leader-os-ai-report');
        }

        /* Custom code: FC-2026-03-31: LOS outreach send flow */
        if($detail && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_ai_report'])) {
            if(!\Altum\Csrf::check()) {
                \Altum\Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            } else {
                try {
                    $active_report = $this->get_cached_ai_report((int) $detail['user_id'], $selected_period);
                    $this->send_ai_report_email((int) $detail['user_id'], $detail, $selected_period, $active_report);

                    \Altum\Alerts::add_success(l('admin_leader_operating_system.leader.outreach_send_success'));
                } catch(\Throwable $exception) {
                    \Altum\Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 280) ?: l('admin_leader_operating_system.leader.outreach_send_failed'));
                }
            }

            redirect('admin/leader-operating-system-leader?user_id=' . $user_id . '&period=' . $selected_period);
        }
        /* /Custom code: FC-2026-03-31 */

        if($detail && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open_stripe_customer_portal'])) {
            if(!\Altum\Csrf::check()) {
                \Altum\Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                redirect('admin/leader-operating-system-leader?user_id=' . $user_id . '&period=' . $selected_period . '#leader-os-stripe-billing');
            }

            try {
                $portal_url = $this->create_stripe_portal_url(
                    $detail,
                    url('admin/leader-operating-system-leader?user_id=' . $user_id . '&period=' . $selected_period . '#leader-os-stripe-billing')
                );

                header('Location: ' . $portal_url);
                die();
            } catch(\Throwable $exception) {
                \Altum\Alerts::add_error(trim((string) $exception->getMessage()) ?: l('admin_leader_operating_system.leader.stripe_portal_open_failed'));
                redirect('admin/leader-operating-system-leader?user_id=' . $user_id . '&period=' . $selected_period . '#leader-os-stripe-billing');
            }
        }

        Title::set(l('admin_leader_operating_system.leader.title'));

        $ai_report = $detail ? $this->get_cached_ai_report((int) $detail['user_id'], $selected_period) : null;
        $stripe_billing = ($detail && $billing_summary) ? $this->get_stripe_billing_payload($detail, $billing_summary) : null;

        $period_start_datetime = $this->get_period_start_datetime($this->get_period_days($selected_period));

        $data = [
            'selected_period' => $selected_period,
            'period_options' => $allowed_periods,
            'overview_url' => url('admin/leader-operating-system?period=' . $selected_period),
            'detail' => $detail,
            'billing_summary' => $billing_summary,
            'stripe_billing' => $stripe_billing,
            'selected_payload' => $detail['periods'][$selected_period] ?? null,
            'opportunity_actions' => ($detail && !empty($detail['periods'][$selected_period])) ? $this->get_opportunity_actions_payload($detail['periods'][$selected_period]) : null,
            /* Custom code: FC-2026-03-31: V2 cohort comparison payload */
            'cohort_comparison' => ($detail && !empty($detail['periods'][$selected_period])) ? $this->get_cohort_comparison_payload((int) $detail['user_id'], $detail['periods'][$selected_period], $selected_period) : null,
            /* /Custom code: FC-2026-03-31 */
            /* Custom code: FC-2026-03-31: V3 behavior anomaly payload */
            'behavior_anomaly' => ($detail && !empty($detail['periods'][$selected_period])) ? $this->get_behavior_anomaly_payload($detail['periods'][$selected_period], $detail['periods'], $detail['app_structure'] ?? [], $selected_period) : null,
            /* /Custom code: FC-2026-03-31 */
            /* Custom code: FC-2026-03-31: Phase 6 fraud intelligence payload */
            'fraud_intelligence' => $detail['fraud_intelligence'][$selected_period] ?? null,
            'fraud_action' => ($detail && !empty($detail['fraud_intelligence'][$selected_period])) ? $this->get_fraud_recommended_action_payload($detail['fraud_intelligence'][$selected_period]) : null,
            /* /Custom code: FC-2026-03-31 */
            'consistency' => ($detail && !empty($detail['periods'][$selected_period])) ? $this->get_consistency_payload($detail['periods'][$selected_period], $detail['ai_plan_admin'] ?? []) : null,
            'coaching_roi' => ($detail && !empty($detail['score_history'][$selected_period])) ? $this->get_coaching_roi_payload($detail['score_history'][$selected_period], $detail['ai_plan_admin'] ?? []) : null,
            'ai_text_detail' => $detail ? $this->get_ai_text_detail_payload($detail['ai_plan_admin'] ?? []) : null,
            'fcc_ai_detail' => $detail ? fcc_ai_get_user_dashboard_payload((int) ($detail['user_id'] ?? 0), $period_start_datetime, 6, \Altum\Language::$code) : null,
            'fcc_ai_dossier' => $detail ? fcc_ai_get_user_chat_intelligence_payload((int) ($detail['user_id'] ?? 0), $period_start_datetime, 6, \Altum\Language::$code) : null,
            'featured_profile_admin' => $detail ? $this->get_featured_profile_admin_payload((int) $detail['user_id']) : null,
            'ai_report' => $ai_report,
            'los_outreach' => $detail ? $this->get_los_outreach_payload((int) $detail['user_id'], $selected_period, $ai_report, (string) ($detail['email'] ?? '')) : null,
        ];

        $view = new \Altum\View('admin/leader-operating-system/leader', (array) $this);
        $this->add_view_content('content', $view->run((object) $data));
    }
}

/* /Custom code: FC-2026-03-31 */
