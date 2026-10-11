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

    public static function own_slug(): ?string {
        if(!Authentication::check()) return null;
        $uid=(int)Authentication::$user_id;
        $id=fc_get_user_main_biolink_id($uid);
        $link=$id?db()->where('link_id',$id)->where('user_id',$uid)->where('type','biolink')->getOne('links',['url']):null;
        if(!$link)$link=db()->where('user_id',$uid)->where('type','biolink')->orderBy('link_id','ASC')->getOne('links',['url']);
        return $link && !empty($link->url)?(string)$link->url:null;
    }

    public static function initiate() {
        if(\Altum\Authentication::check()) {
            // Authentication, including an impersonated session, determines the public referral.
            // Do not change enrollment ownership or a presentation's stored inviter.
            if($slug=self::own_slug()) {
                $_COOKIE['referral']=$slug;
                if(!session_get('admin_user_id'))setcookie('referral',$slug,['expires'=>time()+31536000,'path'=>'/','secure'=>str_starts_with(SITE_URL,'https://'),'httponly'=>true,'samesite'=>'Lax']);
            }
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
            return db()->where('user_id',(int)Authentication::$user_id)->where('status',1)->getOne('users');
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
