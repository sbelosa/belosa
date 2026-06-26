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

namespace Altum;

use Altum\Models\User;

defined('ALTUMCODE') || die();

class Authentication {

    public static $is_logged_in = null;
    public static $user_id = null;
    public static $user = null;
    public static $login_guard_is_set = false;
    private static $has_valid_forever_sales_link = null;
    private static $forever_sales_link_notice_applied = false;

    private static function decode_extra($extra): object {
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

    private static function normalize_plan_settings($plan_settings): object {
        if(is_string($plan_settings)) {
            $plan_settings = json_decode($plan_settings ?? '{}');
        }

        if(is_array($plan_settings)) {
            $plan_settings = (object) $plan_settings;
        }

        if(!is_object($plan_settings)) {
            $plan_settings = (object) [];
        }

        return $plan_settings;
    }

    private static function is_entitled_stripe_status(?string $status): bool {
        return in_array((string) $status, ['active', 'trialing'], true);
    }

    private static function is_downgrade_protected_stripe_status(?string $status): bool {
        return in_array((string) $status, ['active', 'trialing', 'past_due'], true);
    }

    private static function stripe_subscription_matches_user($subscription, $user): bool {
        $metadata_user_id = (int) ($subscription->metadata->user_id ?? 0);

        return $metadata_user_id === 0 || $metadata_user_id === (int) ($user->user_id ?? 0);
    }

    private static function should_attempt_stripe_plan_restore($user): bool {
        if(!is_object($user) || (int) ($user->type ?? 0) !== 0 || (int) ($user->status ?? 0) !== 1) {
            return false;
        }

        if(!settings()->payment->is_enabled || empty(settings()->stripe->is_enabled) || empty(settings()->stripe->secret_key)) {
            return false;
        }

        $subscription_id = trim((string) ($user->payment_subscription_id ?? ''));
        $extra = self::decode_extra($user->extra ?? null);
        $has_stripe_context = ($user->payment_processor ?? '') === 'stripe' || str_starts_with($subscription_id, 'sub_') || !empty($extra->stripe_customer_id);

        if(!$has_stripe_context) {
            return false;
        }

        return (int) ($user->plan_id ?? 0) === 2;
    }

    private static function get_active_stripe_subscription_for_user($user) {
        \Stripe\Stripe::setApiKey(settings()->stripe->secret_key);
        \Stripe\Stripe::setApiVersion('2023-10-16');

        $subscription_id = trim((string) ($user->payment_subscription_id ?? ''));
        $extra = self::decode_extra($user->extra ?? null);
        $stripe_customer_id = trim((string) ($extra->stripe_customer_id ?? ''));
        $checked_subscription_ids = [];
        $customer_ids = [];

        if($subscription_id !== '' && str_starts_with($subscription_id, 'sub_')) {
            try {
                $subscription = \Stripe\Subscription::retrieve($subscription_id);
                $checked_subscription_ids[(string) ($subscription->id ?? $subscription_id)] = true;

                if(self::stripe_subscription_matches_user($subscription, $user) && self::is_downgrade_protected_stripe_status($subscription->status ?? '')) {
                    return $subscription;
                }

                if(!empty($subscription->customer) && is_string($subscription->customer) && str_starts_with($subscription->customer, 'cus_')) {
                    $customer_ids[$subscription->customer] = $subscription->customer;
                }
            } catch(\Exception $exception) {
            }
        }

        if($stripe_customer_id !== '' && str_starts_with($stripe_customer_id, 'cus_')) {
            $customer_ids[$stripe_customer_id] = $stripe_customer_id;
        }

        if(!empty($user->email)) {
            try {
                $customers = \Stripe\Customer::all([
                    'email' => $user->email,
                    'limit' => 10,
                ]);

                foreach($customers->data ?? [] as $customer) {
                    $customer_id = (string) ($customer->id ?? '');

                    if($customer_id !== '' && str_starts_with($customer_id, 'cus_')) {
                        $customer_ids[$customer_id] = $customer_id;
                    }
                }
            } catch(\Exception $exception) {
            }
        }

        foreach(array_values($customer_ids) as $customer_id) {
            try {
                $params = [
                    'customer' => $customer_id,
                    'status' => 'all',
                    'limit' => 25,
                ];

                do {
                    $subscriptions = \Stripe\Subscription::all($params);
                    $subscription_data = $subscriptions->data ?? [];

                    foreach($subscription_data as $subscription) {
                        $candidate_subscription_id = (string) ($subscription->id ?? '');

                        if($candidate_subscription_id !== '' && isset($checked_subscription_ids[$candidate_subscription_id])) {
                            continue;
                        }

                        if(!self::stripe_subscription_matches_user($subscription, $user)) {
                            continue;
                        }

                        if(self::is_downgrade_protected_stripe_status($subscription->status ?? '')) {
                            return $subscription;
                        }
                    }

                    $has_more = !empty($subscriptions->has_more) && !empty($subscription_data);

                    if($has_more) {
                        $last_subscription = end($subscription_data);
                        $params['starting_after'] = $last_subscription->id ?? null;
                    }
                } while($has_more && !empty($params['starting_after']));
            } catch(\Exception $exception) {
            }
        }

        return null;
    }

    private static function sync_user_from_stripe_subscription($user, $subscription) {
        $user_id = (int) ($user->user_id ?? 0);
        if($user_id <= 0 || !is_object($subscription) || !self::is_downgrade_protected_stripe_status($subscription->status ?? '')) {
            return $user;
        }

        $existing_user = db()->where('user_id', $user_id)->getOne('users', [
            'user_id',
            'plan_id',
            'plan_settings',
            'plan_trial_done',
            'plan_expiration_date',
            'payment_subscription_id',
            'payment_processor',
            'extra',
        ]);

        if(!$existing_user) {
            return $user;
        }

        $subscription_id = trim((string) ($subscription->id ?? ''));
        $metadata_plan_id = (int) ($subscription->metadata->plan_id ?? 0);
        $update = [];
        $should_sync_links = false;

        if($subscription_id !== '' && (string) ($existing_user->payment_subscription_id ?? '') !== $subscription_id) {
            $update['payment_subscription_id'] = $subscription_id;
        }

        if((string) ($existing_user->payment_processor ?? '') !== 'stripe') {
            $update['payment_processor'] = 'stripe';
        }

        $extra = self::decode_extra($existing_user->extra ?? null);
        $stripe_customer_id = trim((string) ($subscription->customer ?? ''));
        if($stripe_customer_id !== '' && str_starts_with($stripe_customer_id, 'cus_') && (string) ($extra->stripe_customer_id ?? '') !== $stripe_customer_id) {
            $extra->stripe_customer_id = $stripe_customer_id;
            $update['extra'] = json_encode($extra);
        }

        if($metadata_plan_id > 0) {
            $plan = db()->where('plan_id', $metadata_plan_id)->getOne('plans', ['plan_id', 'settings', 'trial_days']);

            if($plan) {
                $current_plan_settings = self::normalize_plan_settings($existing_user->plan_settings ?? '{}');
                $desired_plan_settings = self::normalize_plan_settings($plan->settings ?? '{}');

                if((int) ($existing_user->plan_id ?? 0) !== $metadata_plan_id) {
                    $update['plan_id'] = $metadata_plan_id;
                    $should_sync_links = true;
                }

                if(json_encode($current_plan_settings) !== json_encode($desired_plan_settings)) {
                    $update['plan_settings'] = json_encode($desired_plan_settings);
                    $should_sync_links = true;
                }

                if((int) ($plan->trial_days ?? 0) > 0 && (int) ($existing_user->plan_trial_done ?? 0) !== 1) {
                    $update['plan_trial_done'] = 1;
                }
            }
        }

        if(!empty($subscription->current_period_end)) {
            $live_plan_expiration_date = date('Y-m-d H:i:s', (int) $subscription->current_period_end);
            if((string) ($existing_user->plan_expiration_date ?? '') !== $live_plan_expiration_date) {
                $update['plan_expiration_date'] = $live_plan_expiration_date;
            }
        }

        if(isset($update['plan_id']) || isset($update['plan_settings']) || isset($update['plan_expiration_date'])) {
            $update['plan_expiry_reminder'] = 0;
        }

        if(empty($update)) {
            return $user;
        }

        db()->where('user_id', $user_id)->update('users', $update);

        if($should_sync_links) {
            (new User())->sync_links_with_plan($user_id);
        }

        cache()->deleteItemsByTag('user_id=' . $user_id);

        return (new User())->get_user_by_user_id($user_id) ?? $user;
    }

    private static function maybe_restore_stripe_plan_access($user) {
        if(!self::should_attempt_stripe_plan_restore($user)) {
            return $user;
        }

        $subscription = self::get_active_stripe_subscription_for_user($user);
        if(!$subscription) {
            return $user;
        }

        return self::sync_user_from_stripe_subscription($user, $subscription);
    }

    public static function check() {

        /* Verify if the current route allows use to do the check */
        if(\Altum\Router::$controller_settings['no_authentication_check']) {
            return false;
        }

        /* Already logged in from previous checks */
        if(self::$is_logged_in) {
            if(self::$user) {
                self::$user = self::maybe_restore_stripe_plan_access(self::$user);
                self::$user_id = self::$user->user_id ?? self::$user_id;
            }

            return self::$user_id;
        }

        /* Check the cookies first */
        if(
            isset($_COOKIE['user_id'])
            && isset($_COOKIE['token_code'])
            && mb_strlen($_COOKIE['token_code']) > 0
            && $user = (new User())->get_user_by_user_id($_COOKIE['user_id'])
        ) {
           if($user->token_code == $_COOKIE['token_code'] && isset($_COOKIE['user_password_hash']) && $_COOKIE['user_password_hash'] == md5($user->password)) {
               $user = self::maybe_restore_stripe_plan_access($user);
               self::$is_logged_in = true;
               self::$user_id = $user->user_id;

               self::$user = $user;

               return true;
           }
        }

        /* Check the Session */
        if(
            session_has('user_id')
            && !empty(session_get('user_id'))
            && $user = (new User())->get_user_by_user_id(session_get('user_id'))
        ) {
            if(session_has('user_password_hash') && session_get('user_password_hash') == md5($user->password ?? '')) {
                $user = self::maybe_restore_stripe_plan_access($user);
                self::$is_logged_in = true;
                self::$user_id = $user->user_id;

                self::$user = $user;

                return true;
            }
        }

        return false;
    }

    /* Custom code */
    public static function is_limited() {
        return isset(self::$user_id) ? self::$user->limited : false;
    }

    public static function is_pro() {
        if (isset(self::$user_id) && self::$user->plan_id == 5 && self::$user->status == 1) {
            return true;
        }
        
        if (isset(self::$user_id) && self::$user->type == 1 && self::$user->status == 1) {
            return true;
        }
        
        return false;
    }
    /* /Custom code */

    /* Custom code: FC-2026-02-24: FCC core biolink gate */
    private static function normalize_boolean($value): bool {
        if(is_bool($value)) {
            return $value;
        }

        if(is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if(is_string($value)) {
            $value = mb_strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'y', 'on'], true);
        }

        return !empty($value);
    }

    private static function extract_fcc_completed_from_preferences($preferences): bool {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!is_object($preferences)) {
            return false;
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

        if(is_object($meta)) {
            if(
                self::normalize_boolean($meta->fcc_core_completed ?? null)
                || self::normalize_boolean($meta->fccCoreCompleted ?? null)
                || self::normalize_boolean($meta->fcc_completed ?? null)
            ) {
                return true;
            }

            if(
                !empty($meta->fcc_core_completed_at ?? null)
                || !empty($meta->fccCoreCompletedAt ?? null)
                || !empty($meta->fcc_completed_at ?? null)
            ) {
                return true;
            }
        }

        if(
            self::normalize_boolean($preferences->fcc_core_completed ?? null)
            || self::normalize_boolean($preferences->fccCoreCompleted ?? null)
            || self::normalize_boolean($preferences->fcc_completed ?? null)
        ) {
            return true;
        }

        if(
            !empty($preferences->fcc_core_completed_at ?? null)
            || !empty($preferences->fccCoreCompletedAt ?? null)
            || !empty($preferences->fcc_completed_at ?? null)
        ) {
            return true;
        }

        return false;
    }

    private static function extract_fcc_gate_exempt_from_preferences($preferences): bool {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!is_object($preferences)) {
            return false;
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

        if(is_object($meta) && (
            self::normalize_boolean($meta->fcc_core_gate_exempt ?? null)
            || self::normalize_boolean($meta->fccCoreGateExempt ?? null)
            || self::normalize_boolean($meta->fcc_gate_exempt ?? null)
        )) {
            return true;
        }

        if(
            self::normalize_boolean($preferences->fcc_core_gate_exempt ?? null)
            || self::normalize_boolean($preferences->fccCoreGateExempt ?? null)
            || self::normalize_boolean($preferences->fcc_gate_exempt ?? null)
        ) {
            return true;
        }

        return false;
    }

    public static function is_fcc_core_completed() {
        if(!self::check()) {
            return false;
        }

        if(self::extract_fcc_completed_from_preferences(self::$user->preferences ?? null)) {
            return true;
        }

        if(isset(self::$user_id)) {
            $preferences_from_db = db()->where('user_id', self::$user_id)->getValue('users', 'preferences');
            if($preferences_from_db && self::extract_fcc_completed_from_preferences($preferences_from_db)) {
                return true;
            }
        }

        return false;
    }

    public static function is_fcc_core_gate_exempt() {
        if(!self::check()) {
            return false;
        }

        if(self::extract_fcc_gate_exempt_from_preferences(self::$user->preferences ?? null)) {
            return true;
        }

        if(isset(self::$user_id)) {
            $preferences_from_db = db()->where('user_id', self::$user_id)->getValue('users', 'preferences');
            if($preferences_from_db && self::extract_fcc_gate_exempt_from_preferences($preferences_from_db)) {
                return true;
            }
        }

        return false;
    }

    public static function can_use_biolinks_without_fcc_completion() {
        return self::is_fcc_core_completed() || self::is_fcc_core_gate_exempt();
    }

    private static function is_valid_forever_sales_link_url($url): bool {
        if(class_exists('\Altum\Link') && method_exists('\Altum\Link', 'is_monitored_forever_destination_url') && \Altum\Link::is_monitored_forever_destination_url($url)) {
            return true;
        }

        $url = mb_strtolower(trim((string) $url));

        return strpos($url, 'https://thealoeveraco.shop/') === 0
            || strpos($url, 'foreverliving.com/') !== false
            || strpos($url, 'foreverlivingproducts.') !== false
            || strpos($url, 'flpshop.ba/') !== false
            || strpos($url, 'foreveralbania.com/') !== false;
    }

    public static function has_valid_forever_sales_link() {
        if(self::$has_valid_forever_sales_link !== null) {
            return self::$has_valid_forever_sales_link;
        }

        if(!self::check() || !isset(self::$user_id)) {
            return self::$has_valid_forever_sales_link = false;
        }

        $sales_link_block_types = function_exists('fc_get_forever_sales_link_block_types')
            ? fc_get_forever_sales_link_block_types()
            : ['link_discount', 'link_forever_webshop_reg', 'link_forever_shop', 'link_forever_product', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];

        $discount_blocks = db()
            ->where('user_id', self::$user_id)
            ->where('type', $sales_link_block_types, 'IN')
            ->where('is_enabled', 1)
            ->get('biolinks_blocks', null, ['location_url']);

        foreach($discount_blocks as $discount_block) {
            if(self::is_valid_forever_sales_link_url($discount_block->location_url ?? '')) {
                return self::$has_valid_forever_sales_link = true;
            }
        }

        return self::$has_valid_forever_sales_link = false;
    }

    private static function apply_forever_sales_link_notice() {
        if(self::$forever_sales_link_notice_applied) {
            return;
        }

        if(!self::check() || !isset(self::$user_id)) {
            return;
        }

        if(self::$user->type != 0) {
            return;
        }

        if(self::has_valid_forever_sales_link()) {
            return;
        }

        if(Alerts::has_infos('forever_sales_link_notice')) {
            self::$forever_sales_link_notice_applied = true;
            return;
        }

        Alerts::add_info(sprintf(
            l('forever_sales_link.notice_message'),
            '<a href="' . url('links?type=biolink') . '" class="alert-link">',
            '</a>',
            '<a href="https://youtu.be/8tBJiDu1EWc?si=sULJ5FOvBZMY-7jJ" target="_blank" rel="noopener noreferrer" class="alert-link">',
            '</a>'
        ), 'forever_sales_link_notice');

        self::$forever_sales_link_notice_applied = true;
    }

    private static function apply_fcc_core_biolink_gate() {
        if(!self::check() || !isset(self::$user_id)) {
            return;
        }

        if(self::$user->type != 0) {
            return;
        }

        if(self::can_use_biolinks_without_fcc_completion()) {
            return;
        }

        if(!db()->where('user_id', self::$user_id)->where('type', 'biolink')->where('is_enabled', 1)->has('links')) {
            return;
        }

        db()->where('user_id', self::$user_id)->where('type', 'biolink')->update('links', ['is_enabled' => 0]);

        $biolink_links = db()->where('user_id', self::$user_id)->where('type', 'biolink')->get('links', null, ['link_id']);
        foreach($biolink_links as $link) {
            cache()->deleteItem('link?link_id=' . $link->link_id);
            cache()->deleteItem('biolink_blocks?link_id=' . $link->link_id);
            cache()->deleteItemsByTag('link_id=' . $link->link_id);
        }

        cache()->deleteItemsByTag('user_id=' . self::$user_id);
    }
    /* /Custom code: FC-2026-02-24 */

    public static function is_admin() {

        if(!self::check()) {
            return false;
        }

        return self::$user->type > 0;
    }


    public static function guard($required_permission = 'user') {

        switch ($required_permission) {
            case 'guest':

                if(self::check()) {
                    redirect(isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard');
                }

                break;

            case 'user':

                if(!self::check()) {
                    redirect('login?redirect=' . \Altum\Router::$original_request . (\Altum\Router::$original_request_query ? '?' . \Altum\Router::$original_request_query : null));
                }

                /* Check if user is banned */
                if(self::$user->status != 1) {
                    self::logout();
                }

                /* Custom code: FC-2026-02-24: FCC core biolink gate */
                self::apply_fcc_core_biolink_gate();
                /* /Custom code: FC-2026-02-24 */

                /* Custom code: FC-2026-03-02: Forever sales link compliance notice */
                self::apply_forever_sales_link_notice();
                /* /Custom code: FC-2026-03-02 */

                self::$login_guard_is_set = true;

                break;

            case 'admin':

                if(!self::check()) {
                    redirect('login?redirect=' . \Altum\Router::$original_request . (\Altum\Router::$original_request_query ? '?' . \Altum\Router::$original_request_query : null));
                }

                /* Check if user is banned */
                if(self::$user->status != 1) {
                    self::logout();
                }

                /* Check if user is admin */
                if(self::$user->type != 1) {
                    redirect('dashboard');
                }

                self::$login_guard_is_set = true;

                break;
        }

    }


    public static function logout($page = '') {

        if(self::check()) {
            db()->where('user_id', self::$user_id)->update('users', ['token_code' => '']);

            /* Clear the cache */
            cache()->deleteItemsByTag('user_id=' . self::$user_id);
        }

        session_destroy();
        setcookie('user_id', '', time()-30, COOKIE_PATH);
        setcookie('token_code', '', time()-30, COOKIE_PATH);
        setcookie('user_password_hash', '', time()-30, COOKIE_PATH);
        setcookie('spotlight_has_results', '', time()-30, COOKIE_PATH);

        if($page !== false) {
            redirect($page);
        }
    }

    public static function get_authorization_bearer() {
        $headers = getallheaders();
        $authorization_header = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if(!$authorization_header) {
            return null;
        }

        if(!preg_match('/Bearer\s(\S+)/', $authorization_header, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
