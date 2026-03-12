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

class Flow extends Model {

    public function get_flow_by_flow_id($flow_id) {

        /* Try to check if the store posts exists via the cache */
        $cache_instance = cache()->getItem('flow?flow_id=' . $flow_id);

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            /* Get data from the database */
            $data = db()->where('flow_id', $flow_id)->getOne('flows');

            if($data) {
                $data->settings = json_decode($data->settings ?? '');

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

    public function get_flows_by_user_id($user_id) {

        $data = [];

        /* Try to check if the store posts exists via the cache */
        $cache_instance = cache()->getItem('flows?user_id=' . $user_id);

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            $result = database()->query("SELECT * FROM `flows` WHERE `user_id` = '{$user_id}'");

            while($row = $result->fetch_object()) {
                $row->settings = json_decode($row->settings ?? '');

                $data[$row->flow_id] = $row;
            }

            cache()->save($cache_instance->set($data)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('user_id=' . $user_id));

        } else {

            /* Get cache */
            $data = $cache_instance->get();

        }

        return $data;
    }

    public function get_flows_by_website_id($website_id) {

        $data = [];

        /* Try to check if the store posts exists via the cache */
        $cache_instance = cache()->getItem('flows?website_id=' . $website_id);
        $user_id = null;

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            $result = database()->query("SELECT * FROM `flows` WHERE `website_id` = '{$website_id}'");

            while($row = $result->fetch_object()) {
                $row->settings = json_decode($row->settings ?? '');

                $data[$row->flow_id] = $row;

                $user_id = $row->user_id;
            }

            cache()->save($cache_instance->set($data)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('user_id=' . $user_id));

        } else {

            /* Get cache */
            $data = $cache_instance->get();

        }

        return $data;
    }

    public function delete($flow_id) {

        $flow = db()->where('flow_id', $flow_id)->getOne('flows', ['user_id', 'flow_id', 'settings', 'image']);

        if(!$flow) return;

        $flow->settings = json_decode($flow->settings ?? '');

        /* Delete uploaded files */
        \Altum\Uploads::delete_uploaded_file($flow->image, 'websites_flows_images');

        /* Delete the flow */
        db()->where('flow_id', $flow_id)->delete('flows');

        /* Clear the cache */
        cache()->deleteItem('flow?flow_id=' . $flow->flow_id);
        cache()->deleteItem('flows?user_id=' . $flow->user_id);
        cache()->deleteItem('flows?website_id=' . $flow->website_id);

    }
}
