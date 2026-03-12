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

defined('ALTUMCODE') || die();

$enabled_biolink_blocks = [];

foreach(require APP_PATH . 'includes/biolink_blocks.php' as $type => $value) {
    /* Custom code: FC-2026-03-06: do not hide newly added blocks when global key is missing */
    $is_available_biolink_block = settings()->links->available_biolink_blocks->{$type} ?? true;

    if($is_available_biolink_block) {
        $enabled_biolink_blocks[$type] = $value;
    }
    /* /Custom code: FC-2026-03-06 */
}

return $enabled_biolink_blocks;
