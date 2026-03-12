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

class Campaign extends Model {

    public function get_campaign_by_campaign_id($campaign_id) {

        /* Try to check if the store posts exists via the cache */
        $cache_instance = cache()->getItem('campaign?campaign_id=' . $campaign_id);

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            /* Get data from the database */
            $data = db()->where('campaign_id', $campaign_id)->getOne('campaigns');

            if($data) {
                $data->settings = json_decode($data->settings ?? '');

                /* Save to cache */
                cache()->save(
                    $cache_instance->set($data)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('user_id=' . $data->user_id)->addTag('campaign_id=' . $data->campaign_id)
                );
            }

        } else {

            /* Get cache */
            $data = $cache_instance->get();

        }

        return $data;
    }

    public function delete($campaign_id) {

        $campaign = db()->where('campaign_id', $campaign_id)->getOne('campaigns', ['user_id', 'campaign_id', 'image']);

        if(!$campaign) return;

        /* Delete uploaded files */
        \Altum\Uploads::delete_uploaded_file($campaign->image, 'websites_campaigns_images');

        /* Delete the campaign */
        db()->where('campaign_id', $campaign_id)->delete('campaigns');

        /* Clear the cache */
        cache()->deleteItemsByTag('campaign_id=' . $campaign_id);
        cache()->deleteItem('campaigns_total?user_id=' . $campaign->user_id);
        cache()->deleteItem('campaigns_dashboard?user_id=' . $campaign->user_id);

    }
}
