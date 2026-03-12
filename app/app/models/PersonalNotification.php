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

class PersonalNotification extends Model {

    public function delete($personal_notification_id) {

        $personal_notification = db()->where('personal_notification_id', $personal_notification_id)->getOne('personal_notifications', ['user_id', 'personal_notification_id', 'image']);

        if(!$personal_notification) return;

        /* Delete uploaded files */
        \Altum\Uploads::delete_uploaded_file($personal_notification->image, 'websites_personal_notifications_images');

        /* Delete the resource */
        db()->where('personal_notification_id', $personal_notification_id)->delete('personal_notifications');

    }
}
