<?php
/*
 * @copyright Copyright (c) 2023 AltumCode (https://altumcode.com/)
 *
 * This software is exclusively sold through https://altumcode.com/ by the AltumCode author.
 * Downloading this product from any other sources and running it without a proper license is illegal,
 *  except the official ones linked from https://altumcode.com/.
 */

namespace Altum;

/* Custom code */
class Referral {

    public static function initiate() {
        if(\Altum\Authentication::check()) {
            return;
        }

        $legacy_referral_slug = 'wpebe1grqr';
        $default_referral_slug = 'ddglabhlcn';

        $referral_key = isset($_GET['ref']) ? query_clean($_GET['ref']) : null;

        if(!$referral_key) {
            if (isset($_COOKIE['referral'])) {
                $referral_key = $_COOKIE['referral'];
                if($referral_key === $legacy_referral_slug) {
                    $referral_key = $default_referral_slug;
                }
            } else {
                $referral_key = $default_referral_slug;
            }
        }

        /* Get the owner user of the referral key */
        if(!$biolink = db()->where('url', $referral_key)->getOne('links', ['user_id'])) {
            return;
        }

        if(!$user = db()->where('user_id', $biolink->user_id)->getOne('users', ['user_id', 'status'])) {
            return;
        }

        /* Make sure the user is still active */
        if($user->status != 1) {
            return;
        }

        /* Check active Plan */
        $user = db()->where('user_id', $biolink->user_id)->getOne('users', ['user_id', 'status', 'plan_id']);
        if($user->plan_id != 5) {
            //return;
            $referral_key = $default_referral_slug;
        }

        /* Set the cookie for 365 days */
        setcookie('referral', $referral_key, time()+60*60*24*365, '/');
        
        /*setcookie('referral', $referral_key, [
            'expires' => time()+60*60*24*90,
            'path' => '/',
            'domain' => 'forevercard.club',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None',
        ]);*/
    }

    public function get_referral() {
        if(\Altum\Authentication::check()) {
           // return;
        }

        $legacy_referral_slug = 'wpebe1grqr';
        $default_referral_slug = 'ddglabhlcn';

        $referral_key = isset($_GET['ref']) ? query_clean($_GET['ref']) : null;

        if(!$referral_key) {
            if (isset($_COOKIE['referral'])) {
                $referral_key = $_COOKIE['referral'];
                if($referral_key === $legacy_referral_slug) {
                    $referral_key = $default_referral_slug;
                }
            } else {
                $referral_key = $default_referral_slug;
            }
        }

        /* Get the owner user of the referral key */
        if(!$biolink = db()->where('url', $referral_key)->getOne('links', ['user_id'])) {
            return;
        }

        if(!$user = db()->where('user_id', $biolink->user_id)->getOne('users', ['user_id', 'status'])) {
            return;
        }

        /* Make sure the user is still active */
        if($user->status != 1) {
            return;
        }

        /* Check active Plan */
        $user = db()->where('user_id', $biolink->user_id)->getOne('users');
        if($user->plan_id != 5) {
            return;
        }

        return $user;

    }

}
/* /Custom code */
