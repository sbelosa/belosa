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

$pro_blocks = \Altum\Plugin::is_active('pro-blocks') && file_exists(\Altum\Plugin::get('pro-blocks')->path . 'pro_blocks.php') ? include \Altum\Plugin::get('pro-blocks')->path . 'pro_blocks.php' : [];
$ultimate_blocks = \Altum\Plugin::is_active('ultimate-blocks') && file_exists(\Altum\Plugin::get('ultimate-blocks')->path . 'ultimate_blocks.php') ? include \Altum\Plugin::get('ultimate-blocks')->path . 'ultimate_blocks.php' : [];
$payment_blocks = \Altum\Plugin::is_active('payment-blocks') && file_exists(\Altum\Plugin::get('payment-blocks')->path . 'payment_blocks.php') ? include \Altum\Plugin::get('payment-blocks')->path . 'payment_blocks.php' : [];

$default_blocks = [
    'link' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-link',
        'color' => '#004ecc',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'standard',
    ],
    'heading' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-heading',
        'color' => '#000000',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => 'text',
        'category' => 'standard',
    ],
    'paragraph' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-paragraph',
        'color' => '#494949',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => 'text',
        'category' => 'standard',
    ],
    'avatar' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-user',
        'color' => '#8b2abf',
        'has_statistics' => true,
        'themable' => false,
        'display_dynamic_name' => false,
        'whitelisted_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'standard',
    ],
    'image' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-image',
        'color' => '#0682FF',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'image_alt',
        'whitelisted_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'standard',
    ],
    'socials' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-users',
        'color' => '#63d2ff',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => false,
        'category' => 'standard',
    ],

    'business_hours' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-clock',
        'color' => '#d90377',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => false,
        'category' => 'standard',
    ],

    'paypal' => [
        'type' => 'default',
        'icon' => 'fab fa-fw fa-paypal',
        'color' => '#00457C',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'payments',
    ],

    'email_collector' => [
        'type' => 'default',
        'icon' => 'fas fa-envelope',
        'color' => '#c91685',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'advanced',
    ],

    'phone_collector' => [
        'type' => 'default',
        'icon' => 'fas fa-phone-square-alt',
        'color' => '#39c640',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'advanced',
    ],

    'contact_collector' => [
        'type' => 'default',
        'icon' => 'fas fa-address-book',
        'color' => '#7136c0',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'advanced',
    ],

    /* Custom code: FC-2026-03-23: lead funnel block phase 1 */
    'lead_funnel' => [
        'type' => 'default',
        'icon' => 'fas fa-filter',
        'color' => '#d97706',
        'has_statistics' => true,
        'themable' => false,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'whitelisted_file_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'csv', 'epub'],
        'category' => 'forever',
    ],
    /* /Custom code: FC-2026-03-23 */

    /* Custom code: FC-2026-04-19: VIP Funnel Hub public foundation block */
    'vip_funnel_hub' => [
        'type' => 'default',
        'icon' => 'fas fa-diagram-project',
        'color' => '#14b8a6',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'forever',
    ],
    /* /Custom code: FC-2026-04-19 */

    'modal_text' => [
        'type' => 'default',
        'icon' => 'fas fa-book-open',
        'color' => '#79a978',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'standard',
    ],

//    'threads' => [
//        'type' => 'default',
//        'icon' => 'fab fa-threads',
//        'color' => '#f54640',
//        'has_statistics' => false,
//        'themable' => false,
//        'display_dynamic_name' => false,
//        'whitelisted_hosts' => ['threads.com', 'www.threads.com'],
//        'category' => 'embeds',
//    ],
    'soundcloud' => [
        'type' => 'default',
        'icon' => 'fab fa-soundcloud',
        'color' => '#ff8800',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => false,
        'whitelisted_hosts' => ['soundcloud.com'],
        'category' => 'embeds',
    ],
    'spotify' => [
        'type' => 'default',
        'icon' => 'fab fa-spotify',
        'color' => '#1db954',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => false,
        'whitelisted_hosts' => ['open.spotify.com'],
        'category' => 'embeds',
    ],
    'youtube' => [
        'type' => 'default',
        'icon' => 'fab fa-youtube',
        'color' => '#ff0000',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => false,
        'whitelisted_hosts' => ['www.youtube.com', 'youtu.be'],
        'category' => 'embeds',
    ],
    'twitch' => [
        'type' => 'default',
        'icon' => 'fab fa-twitch',
        'color' => '#6441a5',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => false,
        'whitelisted_hosts' => ['www.twitch.tv'],
        'category' => 'embeds',
    ],
    'vimeo' => [
        'type' => 'default',
        'icon' => 'fab fa-vimeo',
        'color' => '#1ab7ea',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => false,
        'whitelisted_hosts' => ['vimeo.com'],
        'category' => 'embeds',
    ],
    'tiktok_video' => [
        'type' => 'default',
        'icon' => 'fab fa-tiktok',
        'color' => '#FD3E3E',
        'has_statistics' => false,
        'themable' => true,
        'display_dynamic_name' => false,
        'whitelisted_hosts' => ['www.tiktok.com'],
        'category' => 'embeds',
    ],
];

if(settings()->links->google_static_maps_is_enabled) {
    $default_blocks['map'] = [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-map',
        'color' => '#31A952',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'address',
        'category' => 'advanced',
    ];
}

/* Custom code */
$forever_blocks = [
    'link_app_switcher' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-table-cells-large',
        'color' => '#0ea5e9',
        'has_statistics' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'],
        'category' => 'forever',
    ],
    'link_back' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-arrow-left',
        'color' => '#334155',
        'has_statistics' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'],
        'category' => 'forever',
    ],
    'link_forever_shop' => [        
        'type' => 'default',
        'icon' => 'fas fa-fw fa-shopping-cart',
        'color' => '#FFC600',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'forever',
    ],
    /* Custom code: FC-2026-03-09: forever product picker block */
    'link_forever_product' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-box-open',
        'color' => '#1fbf9e',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'forever',
    ],
    /* /Custom code: FC-2026-03-09 */
    /* Custom code: FC-2026-03-05: add dedicated BIH-only forever referral block */
    'link_forever_living_bih' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-flag',
        'color' => '#0f766e',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'forever',
    ],
    'link_forever_living_alb_kosovo' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-flag-checkered',
        'color' => '#16a34a',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'category' => 'forever',
    ],
    /* /Custom code: FC-2026-03-05 */
    'link_discount' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-percent',
        'color' => '#30a85a',
        'has_statistics' => true,
        'themable' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'],
        'category' => 'forever',
    ],
    'link_save_contact' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-address-book',
        'color' => '#41aaa5',
        'has_statistics' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'],
        'category' => 'forever',
    ],
    'link_homescreen_android' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-mobile',
        'color' => '#78C257',
        'has_statistics' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'],
        'category' => 'forever',
    ],
    /*'link_homescreen_ios' => [
        'type' => 'default',
        'icon' => 'fa fa-fw fa-mobile',
        'color' => '#007AFF',
        'has_statistics' => true,
        'display_dynamic_name' => 'name',
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'],
        'category' => 'forever',
    ]*/
    'custom_html_chatbot' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-comment-dots',
        'color' => '#000000',        
        'max_length' => 10000,
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'],
        'category' => 'forever',
    ],
    /* Custom code: FC-2026-02-27: pets ai chatbot block */
    'custom_html_chatbot_pets' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-paw',
        'color' => '#5f3dc4',
        'max_length' => 10000,
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'],
        'category' => 'forever',
    ],
    /* /Custom code: FC-2026-02-27 */
     'custom_html_whatsapp' => [
        'type' => 'default',
        'icon' => 'fas fa-fw fa-phone',
        'color' => '#39c640',        
        'whitelisted_thumbnail_image_extensions' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'],
        'category' => 'forever',
    ],
];

return array_merge(
    $default_blocks,
    $forever_blocks,
    $pro_blocks,
    $ultimate_blocks,
    $payment_blocks,
);
/* /Custom code */
