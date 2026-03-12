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

class RssAutomation extends Model {

    public function delete($rss_automation_id) {

        $rss_automation = db()->where('rss_automation_id', $rss_automation_id)->getOne('rss_automations', ['user_id', 'rss_automation_id', 'image']);

        if(!$rss_automation) return;

        /* Delete uploaded files */
        \Altum\Uploads::delete_uploaded_file($rss_automation->image, 'websites_rss_automations_images');

        /* Delete the rss_automation */
        db()->where('rss_automation_id', $rss_automation_id)->delete('rss_automations');

    }
}
