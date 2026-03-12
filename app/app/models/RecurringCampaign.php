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

class RecurringCampaign extends Model {

    public function delete($recurring_campaign_id) {

        $recurring_campaign = db()->where('recurring_campaign_id', $recurring_campaign_id)->getOne('recurring_campaigns', ['user_id', 'recurring_campaign_id', 'image']);

        if(!$recurring_campaign) return;

        /* Delete uploaded files */
        \Altum\Uploads::delete_uploaded_file($recurring_campaign->image, 'websites_recurring_campaigns_images');

        /* Delete the recurring_campaign */
        db()->where('recurring_campaign_id', $recurring_campaign_id)->delete('recurring_campaigns');

    }
}
