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

    public static function check() {

        /* Verify if the current route allows use to do the check */
        if(\Altum\Router::$controller_settings['no_authentication_check']) {
            return false;
        }

        /* Already logged in from previous checks */
        if(self::$is_logged_in) {
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
        $url = mb_strtolower(trim((string) $url));

        return strpos($url, 'https://thealoeveraco.shop/') === 0;
    }

    public static function has_valid_forever_sales_link() {
        if(self::$has_valid_forever_sales_link !== null) {
            return self::$has_valid_forever_sales_link;
        }

        if(!self::check() || !isset(self::$user_id)) {
            return self::$has_valid_forever_sales_link = false;
        }

        $discount_blocks = db()
            ->where('user_id', self::$user_id)
            ->where('type', 'link_discount')
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
