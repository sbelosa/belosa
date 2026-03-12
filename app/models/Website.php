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

namespace Altum\Models;

defined('ALTUMCODE') || die();

class Website extends Model {

    public function get_websites_by_user_id($user_id) {

        $data = [];

        /* Try to check if the store posts exists via the cache */
        $cache_instance = cache()->getItem('websites?user_id=' . $user_id);

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            $result = database()->query("SELECT * FROM `websites` WHERE `user_id` = '{$user_id}'");

            while($row = $result->fetch_object()) {
                $row->settings = json_decode($row->settings ?? '');
                $row->notifications = json_decode($row->notifications ?? '');
                $row->keys = json_decode($row->keys ?? '');

                $data[$row->website_id] = $row;
            }

            cache()->save($cache_instance->set($data)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('user_id=' . $user_id));

        } else {

            /* Get cache */
            $data = $cache_instance->get();

        }

        return $data;
    }

    public function get_website_by_website_id($website_id) {

        /* Try to check if the store posts exists via the cache */
        $cache_instance = cache()->getItem('website?website_id=' . $website_id);

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            /* Get data from the database */
            $data = db()->where('website_id', $website_id)->getOne('websites');

            if($data) {
                $data->settings = json_decode($data->settings ?? '');
                $data->widget = json_decode($data->widget ?? '');
                $data->button = json_decode($data->button ?? '');
                $data->notifications = json_decode($data->notifications ?? '');
                $data->keys = json_decode($data->keys ?? '');

                /* Save to cache */
                cache()->save(
                    $cache_instance->set($data)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('user_id=' . $data->user_id)
                );
            }

        } else {

            /* Get cache */
            $data = $cache_instance->get();

        }

        return $data;
    }

    public function get_website_by_pixel_key($pixel_key) {

        /* Try to check if the store posts exists via the cache */
        $cache_instance = cache()->getItem('website?pixel_key=' . md5($pixel_key));

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            /* Get data from the database */
            $data = db()->where('pixel_key', $pixel_key)->getOne('websites');

            if($data) {
                $data->settings = json_decode($data->settings ?? '');
                $data->widget = json_decode($data->widget ?? '');
                $data->button = json_decode($data->button ?? '');
                $data->notifications = json_decode($data->notifications ?? '');
                $data->keys = json_decode($data->keys ?? '');

                /* Save to cache */
                cache()->save(
                    $cache_instance->set($data)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('user_id=' . $data->user_id)
                );
            }

        } else {

            /* Get cache */
            $data = $cache_instance->get();

        }

        return $data;
    }

    public function delete($website_id) {

        $website = db()->where('website_id', $website_id)->getOne('websites', ['user_id', 'website_id', 'pixel_key', 'settings']);

        if(!$website) return;

        $website->settings = json_decode($website->settings ?? '');

        /* Delete uploaded files */
        \Altum\Uploads::delete_uploaded_file($website->settings->icon, 'websites_icons');

        /* Delete the website */
        db()->where('website_id', $website_id)->delete('websites');

        /* Clear the cache */
        cache()->deleteItem('websites_total?user_id=' . $website->user_id);
        cache()->deleteItem('websites?user_id=' . $website->user_id);
        cache()->deleteItem('website?website_id=' . $website->website_id);
        cache()->deleteItem('website?pixel_key=' . md5($website->pixel_key));
        cache()->deleteItemsByTag('user_id=' . $website->user_id);

    }
}
