<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_is_hr = \Altum\Language::$code === 'hr';
$fcc_picker_copy = $fcc_is_hr ? [
    'subheader' => 'Biraj blokove po tome što želiš postići u aplikaciji, a ne po tehničkom tipu.',
    'search_placeholder' => 'Pretraži po nazivu, namjeni ili cilju aplikacije',
    'group_filter' => 'Grupa bloka',
    'group_filter_all' => 'Sve grupe',
    'goal_filter' => 'Cilj aplikacije',
    'goal_filter_all' => 'Svi ciljevi',
    'empty' => 'Nema blokova za ovaj filter. Promijeni pretragu ili odaberi drugu grupu/cilj.',
    'reset_filters' => 'Resetiraj filtere',
    'section_kicker' => 'Kurirana skupina blokova',
    'block_count' => 'blokova',
    'groups' => [
        'start' => ['title' => 'Početak aplikacije', 'subtitle' => 'Blokovi za prvi dojam, uvod, navigaciju i jasan početak stranice.'],
        'contacts' => ['title' => 'Kontakti i leadovi', 'subtitle' => 'Blokovi za prikupljanje kontakata, upita i direktnu komunikaciju.'],
        'sales' => ['title' => 'Prodaja i preporuke', 'subtitle' => 'Blokovi za preporuku proizvoda, CTA-ove, naplatu i funnel korake.'],
        'business' => ['title' => 'Business', 'subtitle' => 'Blokovi za usluge, termine, lokaciju, povjerenje i poslovni kontekst.'],
        'forever' => ['title' => 'Forever Card Club', 'subtitle' => 'FCC specifični blokovi za proizvode, referral, chatbot i app switch flow.'],
        'content' => ['title' => 'Sadržaj i priča', 'subtitle' => 'Blokovi za tekst, slike, medije, raspored, FAQ i bogatiji storytelling.'],
    ],
    'goals' => [
        'intro' => 'Uvod i pregled',
        'lead_capture' => 'Prikupljanje leadova',
        'product_recommendation' => 'Preporuka proizvoda',
        'booking' => 'Naručivanje i termini',
        'trust' => 'Povjerenje i informacije',
        'navigation' => 'Navigacija i pomoć',
        'content' => 'Sadržaj i edukacija',
    ],
    'purposes' => [
        'default_start' => 'za uvod i prvi dojam',
        'default_contacts' => 'za prikupljanje kontakata',
        'default_sales' => 'za preporuku i konverziju',
        'default_business' => 'za predstavljanje business ponude',
        'default_forever' => 'za Forever Card Club flow',
        'default_content' => 'za sadržaj i objašnjenje',
        'lead_funnel' => 'za prijave i vođeni follow-up',
        'link_forever_product' => 'za preporuku proizvoda',
        'link_forever_shop' => 'za službenu FCC registraciju ili prijavu',
        'link_discount' => 'za webshop preporuku',
        'contact_collector' => 'za upite i kontakt obrasce',
        'email_collector' => 'za prikupljanje email leadova',
        'phone_collector' => 'za prikupljanje telefona',
        'custom_html_whatsapp' => 'za direktni WhatsApp kontakt',
        'service' => 'za predstavljanje usluge',
        'appointment_calendar' => 'za rezervaciju termina',
        'map' => 'za lokaciju i dolazak',
        'review' => 'za socijalni dokaz',
        'faq' => 'za česta pitanja',
        'link_app_switcher' => 'za prebacivanje između aplikacija',
        'link_back' => 'za povratak na prethodni korak',
        'link' => 'za glavni gumb ili usmjeravanje',
        'featured_link' => 'za istaknuti glavni CTA',
        'big_link' => 'za veliki akcijski gumb',
        'external_item' => 'za izdvojenu ponudu ili akciju',
        'heading' => 'za naslov i jasan početak',
        'paragraph' => 'za kratko objašnjenje',
        'image' => 'za vizualni fokus',
        'video' => 'za video objašnjenje i povjerenje',
        'modal_text' => 'za dodatno objašnjenje u popupu',
        'markdown' => 'za formatirani tekst i strukturu',
        'custom_html' => 'za napredni prilagođeni sadržaj',
        'image_grid' => 'za prikaz više slika odjednom',
        'image_slider' => 'za galeriju, priču ili rezultate',
        'image_comparison' => 'za usporedbu prije i poslije',
        'divider' => 'za jasan razmak između cjelina',
        'list' => 'za benefite i kratke točke',
        'alert' => 'za važnu obavijest ili upozorenje',
        'audio' => 'za audio poruku ili objašnjenje',
        'file' => 'za preuzimanje datoteke',
        'pdf_document' => 'za PDF vodič ili dokument',
        'powerpoint_presentation' => 'za prezentaciju ili plan',
        'excel_spreadsheet' => 'za tablicu, cjenik ili kalkulaciju',
        'rss_feed' => 'za automatski sadržaj i novosti',
        'anchor' => 'za skok na točan dio stranice',
        'countdown' => 'za rok, hitnost i akciju',
        'vcard' => 'za digitalnu posjetnicu',
        'share' => 'za dijeljenje aplikacije',
        'donation' => 'za uplatu ili donaciju',
        'weather' => 'za lokalni kontekst i informaciju',
        'counter' => 'za brojke, rezultate i dokaz',
        'code' => 'za embed ili napredni kodni dodatak',
        'iframe' => 'za ugradnju vanjskog sadržaja',
        'youtube_feed' => 'za kolekciju video sadržaja',
        'link_homescreen_android' => 'za spremanje aplikacije na početni zaslon',
        'link_homescreen_ios' => 'za spremanje aplikacije na početni zaslon',
        'loading' => 'za vođeni prijelaz ili uvodnu animaciju',
        'socials' => 'za društvene kanale',
        'business_hours' => 'za radno vrijeme',
        'product' => 'za isticanje ponude',
        'paypal' => 'za naplatu i plaćanje',
        'cta' => 'za jasan poziv na akciju',
        'coupon' => 'za promo ponudu',
        'custom_html_chatbot' => 'za AI preporuku Forever proizvoda',
        'custom_html_chatbot_pets' => 'za AI savjetnik za ljubimce',
        'default_embed' => 'za ugradnju vanjskog sadržaja',
        'default_media' => 'za vizualnu ili video priču',
        'default_download' => 'za dokumente i preuzimanje',
    ],
] : [
    'subheader' => 'Choose blocks by what the app should achieve, not by technical category.',
    'search_placeholder' => 'Search by block name, purpose, or app goal',
    'group_filter' => 'Block group',
    'group_filter_all' => 'All groups',
    'goal_filter' => 'App goal',
    'goal_filter_all' => 'All goals',
    'empty' => 'No blocks match this filter. Adjust the search or pick a different group/goal.',
    'reset_filters' => 'Reset filters',
    'section_kicker' => 'Curated block group',
    'block_count' => 'blocks',
    'groups' => [
        'start' => ['title' => 'App start', 'subtitle' => 'Blocks for first impression, intro, navigation, and a clean opening section.'],
        'contacts' => ['title' => 'Contacts and leads', 'subtitle' => 'Blocks for collecting contact details, inquiries, and direct conversations.'],
        'sales' => ['title' => 'Sales and recommendations', 'subtitle' => 'Blocks for product recommendations, CTAs, payments, and funnel steps.'],
        'business' => ['title' => 'Business', 'subtitle' => 'Blocks for services, appointments, location, trust, and business context.'],
        'forever' => ['title' => 'Forever Card Club', 'subtitle' => 'FCC-specific blocks for products, referrals, chatbot support, and app switching.'],
        'content' => ['title' => 'Content and story', 'subtitle' => 'Blocks for text, images, media, scheduling, FAQ, and richer storytelling.'],
    ],
    'goals' => [
        'intro' => 'Intro and overview',
        'lead_capture' => 'Lead capture',
        'product_recommendation' => 'Product recommendation',
        'booking' => 'Ordering and appointments',
        'trust' => 'Trust and information',
        'navigation' => 'Navigation and support',
        'content' => 'Content and education',
    ],
    'purposes' => [
        'default_start' => 'for intros and first impression',
        'default_contacts' => 'for collecting contacts',
        'default_sales' => 'for recommendation and conversion',
        'default_business' => 'for presenting a business offer',
        'default_forever' => 'for the Forever Card Club flow',
        'default_content' => 'for content and explanation',
        'lead_funnel' => 'for registrations and guided follow-up',
        'link_forever_product' => 'for recommending products',
        'link_forever_shop' => 'for official FCC registration or sign-up',
        'link_discount' => 'for webshop referrals',
        'contact_collector' => 'for inquiries and contact forms',
        'email_collector' => 'for collecting email leads',
        'phone_collector' => 'for collecting phone leads',
        'custom_html_whatsapp' => 'for direct WhatsApp contact',
        'service' => 'for presenting services',
        'appointment_calendar' => 'for booking appointments',
        'map' => 'for location and directions',
        'review' => 'for social proof',
        'faq' => 'for common questions',
        'link_app_switcher' => 'for switching between apps',
        'link_back' => 'for returning to the previous step',
        'link' => 'for a primary button or routing action',
        'featured_link' => 'for the main highlighted CTA',
        'big_link' => 'for a large action button',
        'external_item' => 'for a featured external offer or action',
        'heading' => 'for a strong headline',
        'paragraph' => 'for a short explanation',
        'image' => 'for visual focus',
        'video' => 'for video explanation and trust',
        'modal_text' => 'for extra explanation inside a popup',
        'markdown' => 'for formatted text and structure',
        'custom_html' => 'for advanced custom content',
        'image_grid' => 'for showing multiple images at once',
        'image_slider' => 'for a gallery, story, or results showcase',
        'image_comparison' => 'for before-and-after comparisons',
        'divider' => 'for clean visual separation',
        'list' => 'for benefit points and quick highlights',
        'alert' => 'for important notices or warnings',
        'audio' => 'for audio guidance or explanation',
        'file' => 'for downloadable files',
        'pdf_document' => 'for PDF guides or documents',
        'powerpoint_presentation' => 'for decks and presentations',
        'excel_spreadsheet' => 'for price lists, tables, or calculations',
        'rss_feed' => 'for automated updates and fresh content',
        'anchor' => 'for jumping to an exact section',
        'countdown' => 'for urgency, deadlines, and action',
        'vcard' => 'for a digital business card',
        'share' => 'for sharing the app',
        'donation' => 'for payments or donations',
        'weather' => 'for local context and information',
        'counter' => 'for numbers, proof, and momentum',
        'code' => 'for embeds or advanced code snippets',
        'iframe' => 'for embedded external experiences',
        'youtube_feed' => 'for a collection of videos',
        'link_homescreen_android' => 'for saving the app to the home screen',
        'link_homescreen_ios' => 'for saving the app to the home screen',
        'loading' => 'for guided transitions or intro animation',
        'socials' => 'for social channels',
        'business_hours' => 'for opening hours',
        'product' => 'for featured offers',
        'paypal' => 'for payments and checkout',
        'cta' => 'for a clear call to action',
        'coupon' => 'for promo offers',
        'custom_html_chatbot' => 'for AI-guided Forever recommendations',
        'custom_html_chatbot_pets' => 'for the pets AI assistant',
        'default_embed' => 'for embedded external content',
        'default_media' => 'for a visual or video story',
        'default_download' => 'for documents and downloads',
    ],
];

$fcc_group_meta = [
    'start' => ['icon' => 'fas fa-flag-checkered', 'background' => 'linear-gradient(135deg, rgba(15, 23, 42, .96), rgba(20, 36, 72, .92))', 'soft_background' => 'rgba(59, 130, 246, .16)', 'color' => '#8ec5ff', 'dark_background' => '#10203f'],
    'contacts' => ['icon' => 'fas fa-address-book', 'background' => 'linear-gradient(135deg, rgba(9, 30, 34, .96), rgba(14, 55, 52, .92))', 'soft_background' => 'rgba(20, 184, 166, .16)', 'color' => '#5eead4', 'dark_background' => '#0d2a23'],
    'sales' => ['icon' => 'fas fa-bullseye', 'background' => 'linear-gradient(135deg, rgba(41, 24, 9, .96), rgba(64, 34, 8, .92))', 'soft_background' => 'rgba(245, 158, 11, .18)', 'color' => '#f6c56d', 'dark_background' => '#33170b'],
    'business' => ['icon' => 'fas fa-briefcase', 'background' => 'linear-gradient(135deg, rgba(28, 17, 49, .96), rgba(45, 20, 71, .92))', 'soft_background' => 'rgba(168, 85, 247, .18)', 'color' => '#d5b4ff', 'dark_background' => '#24113d'],
    'forever' => ['icon' => 'fas fa-leaf', 'background' => 'linear-gradient(135deg, rgba(33, 32, 11, .96), rgba(53, 43, 7, .92))', 'soft_background' => 'rgba(234, 179, 8, .18)', 'color' => '#f7df84', 'dark_background' => '#382b00'],
    'content' => ['icon' => 'fas fa-layer-group', 'background' => 'linear-gradient(135deg, rgba(11, 29, 36, .96), rgba(12, 48, 58, .92))', 'soft_background' => 'rgba(56, 189, 248, .16)', 'color' => '#8be3ff', 'dark_background' => '#11292a'],
];

$fcc_group_blocks = [
    'start' => ['heading', 'header', 'avatar', 'paragraph', 'image', 'video', 'countdown', 'link_back', 'anchor', 'loading'],
    'contacts' => ['socials', 'email_collector', 'phone_collector', 'contact_collector', 'custom_html_whatsapp', 'link_save_contact', 'share', 'vcard', 'telegram', 'discord'],
    'sales' => ['link', 'featured_link', 'big_link', 'external_item', 'cta', 'coupon', 'paypal', 'donation', 'product', 'lead_funnel'],
    'business' => ['service', 'appointment_calendar', 'calendly', 'typeform', 'google_form', 'map', 'business_hours', 'review', 'faq', 'timeline', 'weather', 'counter', 'rss_feed'],
    'forever' => ['link_app_switcher', 'link_forever_product', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo', 'link_discount', 'link_homescreen_android', 'link_homescreen_ios', 'custom_html_chatbot', 'custom_html_chatbot_pets'],
    'content' => ['modal_text', 'markdown', 'custom_html', 'image_grid', 'image_slider', 'image_comparison', 'divider', 'list', 'alert', 'audio', 'file', 'pdf_document', 'powerpoint_presentation', 'excel_spreadsheet', 'iframe', 'code', 'youtube_feed', 'facebook', 'instagram_media', 'tiktok_video', 'tiktok_profile', 'youtube', 'vimeo', 'twitch', 'soundcloud', 'spotify', 'applemusic', 'mixcloud', 'tidal', 'reddit', 'rumble', 'tumblr_post', 'twitter_tweet', 'twitter_video', 'twitter_profile', 'pinterest_profile', 'vk_video', 'bluesky_post', 'snapchat', 'canva', 'threads', 'text'],
];

$fcc_block_group_map = [];
foreach($fcc_group_blocks as $mapped_group_key => $mapped_blocks) {
    foreach($mapped_blocks as $mapped_block_key) {
        $fcc_block_group_map[$mapped_block_key] = $mapped_group_key;
    }
}

$fcc_block_multi_group_map = [
    'lead_funnel' => ['sales', 'business', 'forever'],
];

$fcc_group_fallback_by_category = [
    'forever' => 'forever',
    'payments' => 'sales',
    'standard' => 'content',
    'advanced' => 'business',
    'embeds' => 'content',
];

$fcc_goal_blocks = [
    'intro' => ['heading', 'header', 'avatar', 'paragraph', 'image', 'video', 'countdown', 'modal_text', 'image_slider', 'link_app_switcher', 'link', 'loading'],
    'lead_capture' => ['email_collector', 'phone_collector', 'contact_collector', 'custom_html_whatsapp', 'link_save_contact', 'socials', 'share', 'vcard', 'telegram', 'discord', 'lead_funnel', 'cta', 'featured_link', 'big_link', 'link', 'service', 'appointment_calendar', 'calendly', 'typeform', 'google_form'],
    'product_recommendation' => ['lead_funnel', 'link_forever_product', 'link_forever_shop', 'link_discount', 'custom_html_chatbot', 'custom_html_chatbot_pets', 'product', 'cta', 'coupon', 'featured_link', 'big_link', 'external_item', 'link', 'paypal', 'service', 'review', 'faq', 'image', 'image_slider', 'video', 'youtube_feed'],
    'booking' => ['appointment_calendar', 'calendly', 'typeform', 'google_form', 'service', 'map', 'business_hours', 'contact_collector', 'phone_collector', 'custom_html_whatsapp', 'paypal', 'donation', 'link', 'cta', 'link_forever_shop', 'lead_funnel'],
    'trust' => ['review', 'faq', 'timeline', 'business_hours', 'map', 'alert', 'list', 'vcard', 'rss_feed', 'counter', 'weather', 'socials', 'share', 'modal_text', 'paragraph', 'markdown', 'video', 'image', 'image_slider', 'product', 'service', 'custom_html_chatbot', 'custom_html_chatbot_pets', 'youtube', 'vimeo', 'facebook', 'instagram_media', 'youtube_feed'],
    'navigation' => ['link_app_switcher', 'link_back', 'link_homescreen_android', 'link_homescreen_ios', 'link_save_contact', 'anchor', 'share', 'link', 'socials'],
    'content' => ['paragraph', 'markdown', 'custom_html', 'image', 'image_grid', 'image_slider', 'image_comparison', 'divider', 'list', 'alert', 'timeline', 'modal_text', 'audio', 'video', 'file', 'pdf_document', 'powerpoint_presentation', 'excel_spreadsheet', 'code', 'iframe', 'weather', 'counter', 'rss_feed', 'youtube_feed', 'facebook', 'instagram_media', 'tiktok_video', 'tiktok_profile', 'youtube', 'vimeo', 'twitch', 'soundcloud', 'spotify', 'applemusic', 'mixcloud', 'tidal', 'reddit', 'rumble', 'tumblr_post', 'twitter_tweet', 'twitter_video', 'twitter_profile', 'pinterest_profile', 'vk_video', 'bluesky_post', 'snapchat', 'canva', 'threads', 'text'],
];

$fcc_block_goal_map = [];
foreach($fcc_goal_blocks as $goal_key => $mapped_blocks) {
    foreach($mapped_blocks as $mapped_block_key) {
        $fcc_block_goal_map[$mapped_block_key] ??= [];
        $fcc_block_goal_map[$mapped_block_key][] = $goal_key;
    }
}

$fcc_default_goals_by_group = [
    'start' => ['intro', 'navigation'],
    'contacts' => ['lead_capture', 'navigation'],
    'sales' => ['product_recommendation', 'lead_capture'],
    'business' => ['trust', 'booking'],
    'forever' => ['product_recommendation', 'navigation', 'lead_capture'],
    'content' => ['content', 'trust'],
];

$fcc_embed_like_blocks = ['applemusic', 'bluesky_post', 'canva', 'facebook', 'instagram_media', 'kick', 'mixcloud', 'pinterest_profile', 'reddit', 'rumble', 'snapchat', 'soundcloud', 'spotify', 'telegram', 'threads', 'tidal', 'tiktok_profile', 'tiktok_video', 'tumblr_post', 'twitch', 'twitter_profile', 'twitter_tweet', 'twitter_video', 'typeform', 'vimeo', 'vk_video', 'youtube'];
$fcc_media_like_blocks = ['image', 'image_grid', 'image_slider', 'image_comparison', 'video', 'audio', 'youtube_feed'];
$fcc_download_like_blocks = ['file', 'pdf_document', 'powerpoint_presentation', 'excel_spreadsheet'];

$fcc_resolve_primary_group = static function(string $block_key, array $block_definition) use ($fcc_block_group_map, $fcc_group_fallback_by_category) {
    if(isset($fcc_block_group_map[$block_key])) {
        return $fcc_block_group_map[$block_key];
    }

    if(str_contains($block_key, 'collector') || in_array($block_key, ['share', 'vcard', 'telegram', 'discord'], true)) {
        return 'contacts';
    }

    if(str_contains($block_key, 'forever') || str_contains($block_key, 'homescreen') || str_contains($block_key, 'chatbot')) {
        return 'forever';
    }

    if(in_array($block_key, ['service', 'appointment_calendar', 'calendly', 'google_form', 'typeform', 'map', 'review', 'faq', 'timeline', 'weather', 'counter', 'rss_feed'], true)) {
        return 'business';
    }

    if(in_array($block_key, ['link', 'featured_link', 'big_link', 'external_item', 'cta', 'coupon', 'paypal', 'donation', 'product', 'lead_funnel'], true)) {
        return 'sales';
    }

    if(in_array($block_key, ['heading', 'header', 'avatar', 'countdown', 'loading', 'anchor'], true)) {
        return 'start';
    }

    return $fcc_group_fallback_by_category[$block_definition['category'] ?? 'standard'] ?? 'content';
};

$fcc_get_groups = static function(string $block_key, array $block_definition) use ($fcc_block_multi_group_map, $fcc_resolve_primary_group) {
    $primary_group_key = $fcc_resolve_primary_group($block_key, $block_definition);
    $group_keys = [$primary_group_key];

    if(isset($fcc_block_multi_group_map[$block_key])) {
        $group_keys = array_merge($group_keys, $fcc_block_multi_group_map[$block_key]);
    }

    return array_values(array_unique(array_filter($group_keys)));
};

$fcc_get_goals = static function(string $block_key, string $group_key) use ($fcc_block_goal_map, $fcc_default_goals_by_group) {
    if(isset($fcc_block_goal_map[$block_key])) {
        return array_values(array_unique($fcc_block_goal_map[$block_key]));
    }

    return $fcc_default_goals_by_group[$group_key] ?? ['content'];
};

$fcc_get_purpose = static function(string $block_key, string $group_key) use ($fcc_picker_copy, $fcc_embed_like_blocks, $fcc_media_like_blocks, $fcc_download_like_blocks) {
    if(isset($fcc_picker_copy['purposes'][$block_key])) {
        return $fcc_picker_copy['purposes'][$block_key];
    }

    if(in_array($block_key, $fcc_embed_like_blocks, true)) {
        return $fcc_picker_copy['purposes']['default_embed'] ?? ($fcc_picker_copy['purposes']['default_content'] ?? '');
    }

    if(in_array($block_key, $fcc_media_like_blocks, true)) {
        return $fcc_picker_copy['purposes']['default_media'] ?? ($fcc_picker_copy['purposes']['default_content'] ?? '');
    }

    if(in_array($block_key, $fcc_download_like_blocks, true)) {
        return $fcc_picker_copy['purposes']['default_download'] ?? ($fcc_picker_copy['purposes']['default_content'] ?? '');
    }

    return $fcc_picker_copy['purposes']['default_' . $group_key] ?? ($fcc_picker_copy['purposes']['default_content'] ?? '');
};

$fcc_get_visual_accent = static function(?string $block_color, string $group_color): string {
    $block_color = strtolower(trim((string) $block_color));

    if($block_color === '' || in_array($block_color, ['#000000', '#111111', '#1f2937', '#334155', '#494949', '#00457c'], true)) {
        return $group_color;
    }

    return $block_color;
};

$fcc_grouped_blocks = array_fill_keys(array_keys($fcc_group_meta), []);
$fcc_enabled_biolink_blocks = (object) ($this->user->plan_settings->enabled_biolink_blocks ?? []);
$fcc_ai_editor_payload = $data->ai_editor_payload ?? [];
$fcc_ai_missing_block_recommendations = is_array($fcc_ai_editor_payload['missing_block_recommendations'] ?? null) ? $fcc_ai_editor_payload['missing_block_recommendations'] : [];
$fcc_ai_missing_copy = $fcc_is_hr ? [
    'title' => 'AI blokovi koji nedostaju',
    'text' => 'Ovdje vidiš što AI smatra da nedostaje ovoj aplikaciji. Dodaj i pripremi odmah ubacuje blok, smješta ga bliže preporučenoj poziciji i otvara ga za uređivanje.',
    'priority' => 'Prioritet %s',
    'position_after' => 'Preporučena pozicija: nakon %s',
    'position_top' => 'Preporučena pozicija: pri vrhu aplikacije',
    'add' => 'Dodaj i pripremi',
    'open_picker' => 'Otvori u popisu blokova',
    'kicker' => 'AI preporuka',
] : [
    'title' => 'Missing AI-recommended blocks',
    'text' => 'This area shows what AI believes is missing from this app. Add and prepare inserts the block, places it closer to the recommended spot, and opens it for editing.',
    'priority' => 'Priority %s',
    'position_after' => 'Recommended position: after %s',
    'position_top' => 'Recommended position: near the top of the app',
    'add' => 'Add and prepare',
    'open_picker' => 'Open in block picker',
    'kicker' => 'AI recommendation',
];
?>

<?php /* Custom code: FC-2026-02-27: premium add-block modal styling */ ?>
<?php ob_start() ?>
<style>
    #biolink_link_create_modal .modal-dialog {
        max-width: 1180px;
    }

    #biolink_link_create_modal .biolink-create-modal-content {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .09);
        border-radius: 1.4rem;
        box-shadow: 0 2rem 4rem rgba(2, 6, 23, 0.42);
        background:
            radial-gradient(circle at top left, rgba(56, 189, 248, .10), transparent 28%),
            radial-gradient(circle at top right, rgba(234, 179, 8, .10), transparent 24%),
            linear-gradient(180deg, #08111f 0%, #0c1526 48%, #111c31 100%) !important;
        color: #e8f0fb;
    }

    #biolink_link_create_modal .biolink-create-modal-content .modal-body {
        background: transparent !important;
        padding: 1.5rem 1.5rem 1.75rem;
    }

    #biolink_link_create_modal .biolink-create-modal-content .modal-title {
        color: #f8fbff;
        font-size: 1.4rem;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    #biolink_link_create_modal .biolink-create-modal-content .text-muted {
        color: rgba(207, 220, 240, .76) !important;
    }

    #biolink_link_create_modal .biolink-create-modal-content .close {
        color: rgba(248, 251, 255, .88);
        text-shadow: none;
        opacity: .8;
    }

    #biolink_link_create_modal .biolink-create-intro {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 1.1rem;
        padding: 1.05rem 1.15rem;
        background: linear-gradient(135deg, rgba(18, 28, 49, .92), rgba(15, 23, 42, .86));
        box-shadow: inset 0 1px 0 rgba(255,255,255,.06), 0 .8rem 1.8rem rgba(2, 6, 23, .18);
    }

    #biolink_link_create_modal .biolink-create-intro::before {
        content: '';
        position: absolute;
        inset: 0 auto auto 0;
        width: 38%;
        height: 100%;
        background: linear-gradient(135deg, rgba(234, 179, 8, .10), transparent 65%);
        pointer-events: none;
    }

    #biolink_link_create_modal .biolink-create-intro h6 {
        position: relative;
        color: #f8fbff;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    #biolink_link_create_modal .biolink-create-filters {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) repeat(2, minmax(220px, 1fr));
        gap: .85rem;
        margin-bottom: 1.35rem;
    }

    #biolink_link_create_modal .biolink-create-search .form-control,
    #biolink_link_create_modal .biolink-create-filters .custom-select[data-is-not-custom-select] {
        min-height: 3.55rem;
        border-radius: .95rem;
        border: 1px solid rgba(255, 255, 255, .10) !important;
        background: linear-gradient(180deg, rgba(7, 13, 26, .94), rgba(12, 20, 36, .94)) !important;
        color: #eef4ff !important;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.04), 0 .65rem 1.4rem rgba(2, 6, 23, .18);
    }

    #biolink_link_create_modal .biolink-create-search .form-control::placeholder {
        color: rgba(196, 208, 228, .58);
    }

    #biolink_link_create_modal .biolink-create-filters label {
        color: rgba(206, 220, 239, .82) !important;
        font-size: .72rem;
        letter-spacing: .12em;
    }

    #biolink_link_create_modal .biolink-create-filters .custom-select[data-is-not-custom-select] {
        appearance: none;
        -webkit-appearance: none;
        background-image:
            linear-gradient(180deg, rgba(255,255,255,0), rgba(255,255,255,0)),
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Cpath fill='%23dbeafe' d='M3.1 4.9L7 8.8l3.9-3.9 1.1 1.1L7 10.9 2 6z'/%3E%3C/svg%3E");
        background-repeat: no-repeat, no-repeat;
        background-position: center right 1rem, center right 1rem;
        background-size: 100% 100%, .9rem;
        padding-right: 2.8rem;
    }

    #biolink_link_create_modal .biolink-block-category-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 1.15rem;
        background: var(--group-background) !important;
        box-shadow: 0 1rem 2.4rem rgba(2, 6, 23, .22);
    }

    #biolink_link_create_modal .biolink-block-category-card::before {
        content: '';
        position: absolute;
        inset: 0 auto auto 0;
        width: 34%;
        height: 100%;
        background: linear-gradient(135deg, var(--group-soft-background), transparent 70%);
        pointer-events: none;
    }

    #biolink_link_create_modal .biolink-block-category-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .35rem;
        color: rgba(233, 242, 255, .72);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    #biolink_link_create_modal .biolink-block-category-title {
        color: #f8fbff;
        font-size: 1.22rem;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    #biolink_link_create_modal .biolink-block-category-subtitle {
        color: rgba(217, 228, 245, .78);
        max-width: 44rem;
        line-height: 1.55;
    }

    #biolink_link_create_modal .biolink-block-category-count {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .45rem .78rem;
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255, 255, 255, .10);
        color: #f8fbff;
        font-size: .76rem;
        font-weight: 700;
        white-space: nowrap;
    }

    #biolink_link_create_modal .biolink-block-category-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3.25rem;
        height: 3.25rem;
        border-radius: 1rem;
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255,255,255,.1);
        color: var(--group-color);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
    }

    #biolink_link_create_modal .biolink-create-block-btn {
        position: relative;
        overflow: hidden;
        min-height: 168px;
        padding: 1rem 1.05rem !important;
        border: 1px solid rgba(255,255,255,.08) !important;
        border-radius: 1rem;
        background:
            radial-gradient(circle at top right, rgba(255,255,255,.07), transparent 34%),
            linear-gradient(180deg, rgba(9, 15, 28, .98), rgba(15, 24, 42, .96)) !important;
        box-shadow: 0 .95rem 2rem rgba(2, 6, 23, 0.18) !important;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    #biolink_link_create_modal .biolink-create-block-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--block-accent, var(--group-color, #38bdf8)), transparent 72%);
        opacity: .8;
    }

    #biolink_link_create_modal .biolink-create-block-btn:hover {
        transform: translateY(-4px);
        border-color: rgba(255,255,255,.18) !important;
        box-shadow: 0 1.35rem 2.6rem rgba(2, 6, 23, 0.32) !important;
    }

    #biolink_link_create_modal .biolink-create-block-btn.container-disabled .biolink-create-block-title,
    #biolink_link_create_modal .biolink-create-block-btn.container-disabled .biolink-create-block-subtitle {
        text-decoration: line-through;
    }

    #biolink_link_create_modal .biolink-create-block-btn.container-disabled {
        opacity: .58;
        filter: saturate(.7);
    }

    #biolink_link_create_modal .biolink-create-block-btn,
    #biolink_link_create_modal .biolink-create-block-btn.btn-light {
        display: flex !important;
        align-items: flex-start !important;
        gap: .95rem;
        color: #f8fbff !important;
        text-align: left;
    }

    #biolink_link_create_modal .biolink-create-block-icon {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3.2rem;
        height: 3.2rem;
        min-width: 3.2rem;
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(255,255,255,.12), rgba(255,255,255,.04));
        border: 1px solid rgba(255,255,255,.08);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
    }

    #biolink_link_create_modal .biolink-create-block-icon img {
        width: 1.85rem;
        height: 1.85rem;
        object-fit: contain;
    }

    #biolink_link_create_modal .biolink-create-block-icon .fa-stack {
        font-size: 1.18rem;
    }

    #biolink_link_create_modal .biolink-create-block-meta {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: .38rem;
        padding-top: .05rem;
    }

    #biolink_link_create_modal .biolink-create-block-title {
        color: #f8fbff;
        font-size: 1.12rem;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    #biolink_link_create_modal .biolink-create-block-purpose {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        width: fit-content;
        border-radius: 999px;
        padding: .32rem .68rem;
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.08);
        font-size: .72rem;
        font-weight: 700;
        color: var(--block-accent, #93c5fd);
        line-height: 1.2;
    }

    #biolink_link_create_modal .biolink-create-block-purpose::before {
        content: '';
        display: inline-block;
        width: .45rem;
        height: .45rem;
        border-radius: 999px;
        background: currentColor;
        box-shadow: 0 0 .75rem currentColor;
    }

    #biolink_link_create_modal .biolink-create-block-goals {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .05rem;
    }

    #biolink_link_create_modal .biolink-create-block-goal {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .22rem .58rem;
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.08);
        color: rgba(232, 240, 251, .88);
        font-size: .68rem;
        font-weight: 700;
    }

    #biolink_link_create_modal .biolink-create-block-subtitle,
    #biolink_link_create_modal .biolink-create-block-btn .text-muted {
        color: rgba(208, 220, 239, .76) !important;
        line-height: 1.55;
    }

    #biolink_link_create_modal .biolink-ai-missing-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(56, 189, 248, .18);
        border-radius: 1.15rem;
        background:
            radial-gradient(circle at top right, rgba(45, 212, 191, .10), transparent 34%),
            linear-gradient(180deg, rgba(8, 19, 36, .98), rgba(11, 24, 43, .96));
        box-shadow: 0 1rem 2.2rem rgba(2, 6, 23, .24);
    }

    #biolink_link_create_modal .biolink-ai-missing-card::before {
        content: '';
        position: absolute;
        inset: 0 auto auto 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #2dd4bf, rgba(45, 212, 191, .15));
    }

    #biolink_link_create_modal .biolink-ai-missing-card .card-body {
        position: relative;
        padding: 1rem 1.1rem 1.05rem;
    }

    #biolink_link_create_modal .biolink-ai-missing-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        color: #5eead4;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    #biolink_link_create_modal .biolink-ai-missing-title {
        color: #f8fbff;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    #biolink_link_create_modal .biolink-ai-missing-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: .8rem;
    }

    #biolink_link_create_modal .biolink-ai-missing-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .3rem .7rem;
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.08);
        color: rgba(235, 245, 255, .92);
        font-size: .72rem;
        font-weight: 700;
    }

    #biolink_link_create_modal .biolink-create-empty {
        border: 1px dashed rgba(255,255,255,.18);
        border-radius: 1.15rem;
        padding: 1.45rem;
        text-align: center;
        color: rgba(220, 230, 245, .8);
        background: linear-gradient(180deg, rgba(12, 20, 36, .88), rgba(15, 24, 42, .82));
    }

    #biolink_link_create_modal .biolink-create-empty-reset {
        margin-top: .9rem;
    }

    #biolink_link_create_modal .biolink-create-empty-reset .btn {
        border-radius: 999px;
        padding-inline: 1rem;
    }

    body[data-theme-style='dark'] #biolink_link_create_modal .biolink-create-modal-content {
        border-color: rgba(255,255,255,.09) !important;
    }

    @media (max-width: 991px) {
        #biolink_link_create_modal .biolink-create-filters {
            grid-template-columns: 1fr;
        }

        #biolink_link_create_modal .biolink-block-category-card .card-body {
            gap: 1rem;
        }
    }

    @media (max-width: 767px) {
        #biolink_link_create_modal .biolink-create-modal-content .modal-body {
            padding: 1rem;
        }

        #biolink_link_create_modal .biolink-create-block-btn {
            min-height: 0;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
<?php /* /Custom code: FC-2026-02-27 */ ?>

<div class="modal fade" id="biolink_link_create_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content biolink-create-modal-content">
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <h5 class="modal-title mb-1">
                            <i class="fas fa-fw fa-sm fa-circle-plus text-primary mr-2"></i>
                            <?= l('biolink_link_create.header') ?>
                        </h5>
                        <p class="text-muted mb-0"><?= $fcc_picker_copy['subheader'] ?></p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div id="fcc_biolink_ai_missing_notification" class="notification-container mb-3"></div>

                <div id="fcc_biolink_block_picker_intro" class="biolink-create-intro mb-3 fcc-biolink-tour-target">
                    <h6 class="mb-2"><?= $fcc_is_hr ? 'Dodaj blok po stvarnoj primjeni' : 'Add a block by real use case' ?></h6>
                    <p class="small text-muted mb-0"><?= $fcc_picker_copy['subheader'] ?></p>
                </div>

                <?php if(!empty($fcc_ai_missing_block_recommendations)): ?>
                    <div class="mb-4">
                        <div class="biolink-block-category-card card border-0 mb-3" style="--group-background: linear-gradient(135deg, rgba(13, 41, 56, .92), rgba(8, 22, 39, .96)); --group-soft-background: rgba(45, 212, 191, .10); --group-color: #2dd4bf;">
                            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                                <div class="pr-3">
                                    <span class="biolink-block-category-kicker"><?= $fcc_ai_missing_copy['kicker'] ?></span>
                                    <div class="biolink-block-category-title"><?= $fcc_ai_missing_copy['title'] ?></div>
                                    <p class="small mb-0 biolink-block-category-subtitle"><?= $fcc_ai_missing_copy['text'] ?></p>
                                </div>

                                <div class="d-flex align-items-center flex-wrap justify-content-end" style="gap: .75rem;">
                                    <span class="biolink-block-category-count"><?= count($fcc_ai_missing_block_recommendations) . ' ' . $fcc_picker_copy['block_count'] ?></span>
                                    <span class="biolink-block-category-icon">
                                        <i class="fas fa-fw fa-wand-magic-sparkles fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <?php foreach($fcc_ai_missing_block_recommendations as $fcc_ai_missing_block): ?>
                                <?php
                                $fcc_ai_missing_label = (string) (($fcc_ai_missing_block['label'] ?? '') ?: l('link.biolink.blocks.' . ($fcc_ai_missing_block['block_type'] ?? '')));
                                $fcc_ai_missing_position_label = trim((string) ($fcc_ai_missing_block['insert_after_label'] ?? ''));
                                $fcc_is_auto_add_supported = !empty($fcc_ai_missing_block['supports_auto_add']);
                                ?>
                                <div class="col-12 col-lg-6 mb-3">
                                    <div class="biolink-ai-missing-card card border-0 h-100">
                                        <div class="card-body">
                                            <div class="biolink-ai-missing-kicker mb-2">
                                                <i class="fas fa-fw fa-wand-magic-sparkles"></i>
                                                <span><?= $fcc_ai_missing_copy['kicker'] ?></span>
                                            </div>
                                            <div class="biolink-ai-missing-title mb-2"><?= htmlspecialchars($fcc_ai_missing_label, ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="small text-muted mb-2"><?= htmlspecialchars((string) ($fcc_ai_missing_block['why'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="biolink-ai-missing-meta mb-3">
                                                <span class="biolink-ai-missing-badge"><?= sprintf($fcc_ai_missing_copy['priority'], nr((int) ($fcc_ai_missing_block['priority'] ?? 0))) ?></span>
                                                <span class="biolink-ai-missing-badge">
                                                    <?= $fcc_ai_missing_position_label !== ''
                                                        ? sprintf($fcc_ai_missing_copy['position_after'], htmlspecialchars($fcc_ai_missing_position_label, ENT_QUOTES, 'UTF-8'))
                                                        : $fcc_ai_missing_copy['position_top'] ?>
                                                </span>
                                            </div>

                                            <?php if($fcc_is_auto_add_supported): ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-primary btn-block js-add-ai-missing-block-modal"
                                                    data-link-id="<?= (int) ($data->link->link_id ?? 0) ?>"
                                                    data-recommendation-key="<?= htmlspecialchars((string) ($fcc_ai_missing_block['recommendation_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-block-type="<?= htmlspecialchars((string) ($fcc_ai_missing_block['block_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                >
                                                    <i class="fas fa-fw fa-wand-magic-sparkles mr-1"></i> <?= $fcc_ai_missing_copy['add'] ?>
                                                </button>
                                            <?php else: ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-light btn-block js-open-ai-block-picker-from-modal"
                                                    data-block-type="<?= htmlspecialchars((string) ($fcc_ai_missing_block['block_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-picker-search="<?= htmlspecialchars((string) ($fcc_ai_missing_block['picker_search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-block-group="<?= htmlspecialchars((string) ($fcc_ai_missing_block['preferred_group'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-block-goal="<?= htmlspecialchars((string) ($fcc_ai_missing_block['preferred_goal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                >
                                                    <i class="fas fa-fw fa-plus-circle mr-1"></i> <?= $fcc_ai_missing_copy['open_picker'] ?>
                                                </button>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endif ?>

                <div class="biolink-create-filters">
                    <form action="" method="get" role="form" id="fcc_biolink_block_picker_search_form" class="biolink-create-search mb-0 fcc-biolink-tour-target">
                        <div id="fcc_biolink_block_picker_search_wrap" class="form-group mb-0 fcc-biolink-tour-target">
                            <input type="search" id="fcc_biolink_block_picker_search" name="search" class="form-control form-control-lg" value="" placeholder="<?= $fcc_picker_copy['search_placeholder'] ?>" aria-label="<?= $fcc_picker_copy['search_placeholder'] ?>" />
                        </div>
                    </form>

                    <div id="fcc_biolink_block_picker_group_filter_wrap" class="form-group mb-0 fcc-biolink-tour-target">
                        <label for="fcc_biolink_block_group_filter" class="small text-uppercase font-weight-bold text-muted mb-2"><?= $fcc_picker_copy['group_filter'] ?></label>
                        <select id="fcc_biolink_block_group_filter" class="custom-select" data-is-not-custom-select>
                            <option value=""><?= $fcc_picker_copy['group_filter_all'] ?></option>
                            <?php foreach($fcc_picker_copy['groups'] as $group_key => $group_copy): ?>
                                <option value="<?= $group_key ?>"><?= $group_copy['title'] ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>

                    <div id="fcc_biolink_block_picker_goal_filter_wrap" class="form-group mb-0 fcc-biolink-tour-target">
                        <label for="fcc_biolink_block_goal_filter" class="small text-uppercase font-weight-bold text-muted mb-2"><?= $fcc_picker_copy['goal_filter'] ?></label>
                        <select id="fcc_biolink_block_goal_filter" class="custom-select" data-is-not-custom-select>
                            <option value=""><?= $fcc_picker_copy['goal_filter_all'] ?></option>
                            <?php foreach($fcc_picker_copy['goals'] as $goal_key => $goal_label): ?>
                                <option value="<?= $goal_key ?>"><?= $goal_label ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <?php foreach(require APP_PATH . 'includes/enabled_biolink_blocks.php' as $key => $value): ?>
                    <?php if(in_array($key, ['link_forever_living_bih', 'link_forever_living_alb_kosovo'], true)) continue ?>

                    <?php
                    $is_block_enabled_for_plan = (bool) ($fcc_enabled_biolink_blocks->{$key} ?? false);
                    $assigned_group_keys = $fcc_get_groups($key, $value);
                    $primary_group_key = $assigned_group_keys[0] ?? 'content';
                    $primary_group_meta = $fcc_group_meta[$primary_group_key] ?? reset($fcc_group_meta);
                    $goal_keys = $fcc_get_goals($key, $primary_group_key);
                    $goal_labels = array_values(array_filter(array_map(static function($goal_key) use ($fcc_picker_copy) {
                        return $fcc_picker_copy['goals'][$goal_key] ?? null;
                    }, $goal_keys)));
                    $group_search_titles = array_values(array_filter(array_map(static function($group_key) use ($fcc_picker_copy) {
                        return $fcc_picker_copy['groups'][$group_key]['title'] ?? null;
                    }, $assigned_group_keys)));
                    $search_text = mb_strtolower(trim(implode(' ', array_filter([
                        l('link.biolink.blocks.' . $key),
                        $fcc_get_purpose($key, $primary_group_key),
                        implode(' ', $goal_labels),
                        implode(' ', $group_search_titles),
                        l('biolink_' . $key . '.subheader'),
                    ]))));
                    ?>
                    <?php foreach($assigned_group_keys as $group_key): ?>
                        <?php
                        $group_meta = $fcc_group_meta[$group_key] ?? $primary_group_meta;
                        $block_visual_accent = $fcc_get_visual_accent($data->biolink_blocks[$key]['color'] ?? null, $group_meta['color']);
                        $purpose_text = $fcc_get_purpose($key, $group_key);
                        ob_start();
                        ?>
                        <div
                            class="col-12 col-lg-6 p-3"
                            data-block-card
                            data-block-group="<?= $group_key ?>"
                            data-block-goals="<?= htmlspecialchars(implode(',', $goal_keys), ENT_QUOTES) ?>"
                            data-block-search="<?= htmlspecialchars($search_text, ENT_QUOTES) ?>"
                            data-block-id="<?= $key ?>"
                            data-block-name="<?= l('link.biolink.blocks.' . $key) ?>"
                            style="--group-color: <?= $group_meta['color'] ?>; --block-accent: <?= htmlspecialchars($block_visual_accent, ENT_QUOTES) ?>;"
                            <?= $is_block_enabled_for_plan ? null : get_plan_feature_disabled_info() ?>
                        >
                            <button
                                type="button"
                                data-dismiss="modal"
                                data-toggle="modal"
                                data-target="#create_biolink_<?= $key ?>"
                                data-tooltip
                                title="<?= l('biolink_' . $key . '.subheader') ?>"
                                class="btn btn-light btn-block btn-lg text-left d-flex align-items-center biolink-create-block-btn <?= $is_block_enabled_for_plan ? null : 'container-disabled' ?>"
                            >
                                <span class="biolink-create-block-icon">
                                    <?php if($key === 'custom_html_chatbot'): ?>
                                        <img
                                            src="<?= SITE_URL . ASSETS_URL_PATH . 'images/sovica.png' ?>"
                                            alt=""
                                            onerror="this.onerror=null;this.src='<?= SITE_URL . UPLOADS_URL_PATH . 'ai-chat/sovica.png' ?>';"
                                            loading="lazy"
                                            decoding="async"
                                        />
                                    <?php elseif($key === 'custom_html_chatbot_pets'): ?>
                                        <span class="fa-stack">
                                            <i class="fas fa-circle fa-stack-2x" style="color: #5f3dc4"></i>
                                            <i class="fas fa-paw fa-stack-1x fa-inverse"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="fa-stack">
                                            <i class="fas fa-circle fa-stack-2x" style="color: <?= htmlspecialchars($block_visual_accent, ENT_QUOTES) ?>"></i>
                                            <i class="<?= $data->biolink_blocks[$key]['icon'] ?> fa-stack-1x fa-inverse"></i>
                                        </span>
                                    <?php endif ?>
                                </span>

                                <span class="biolink-create-block-meta">
                                    <span class="biolink-create-block-title"><?= l('link.biolink.blocks.' . $key) ?></span>
                                    <span class="biolink-create-block-purpose"><?= $purpose_text ?></span>
                                    <?php if($goal_labels): ?>
                                        <span class="biolink-create-block-goals">
                                            <?php foreach($goal_labels as $goal_label): ?>
                                                <span class="biolink-create-block-goal"><?= $goal_label ?></span>
                                            <?php endforeach ?>
                                        </span>
                                    <?php endif ?>
                                    <small class="text-muted biolink-create-block-subtitle"><?= l('biolink_' . $key . '.subheader') ?></small>
                                </span>
                            </button>
                        </div>
                        <?php
                        $fcc_grouped_blocks[$group_key][] = ob_get_clean();
                        ?>
                    <?php endforeach ?>
                <?php endforeach ?>

                <?php foreach($fcc_picker_copy['groups'] as $group_key => $group_copy): ?>
                    <?php if(empty($fcc_grouped_blocks[$group_key])) continue ?>
                    <?php $group_meta = $fcc_group_meta[$group_key] ?>
                    <div class="mb-4" data-purpose-section data-purpose-group="<?= $group_key ?>" data-count-label="<?= htmlspecialchars($fcc_picker_copy['block_count'], ENT_QUOTES) ?>">
                        <div class="biolink-block-category-card card border-0 mb-3" style="--group-background: <?= $group_meta['background'] ?>; --group-soft-background: <?= $group_meta['soft_background'] ?>; --group-color: <?= $group_meta['color'] ?>;">
                            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                                <div class="pr-3">
                                    <span class="biolink-block-category-kicker"><?= $fcc_picker_copy['section_kicker'] ?></span>
                                    <div class="biolink-block-category-title"><?= $group_copy['title'] ?></div>
                                    <p class="small mb-0 biolink-block-category-subtitle"><?= $group_copy['subtitle'] ?></p>
                                </div>

                                <div class="d-flex align-items-center flex-wrap justify-content-end" style="gap: .75rem;">
                                    <span class="biolink-block-category-count" data-purpose-count><?= count($fcc_grouped_blocks[$group_key]) . ' ' . $fcc_picker_copy['block_count'] ?></span>
                                    <span class="biolink-block-category-icon">
                                        <i class="<?= $group_meta['icon'] ?> fa-fw fa-lg"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <?= implode('', $fcc_grouped_blocks[$group_key]) ?>
                        </div>
                    </div>
                <?php endforeach ?>

                <div id="fcc_biolink_block_picker_empty" class="biolink-create-empty d-none">
                    <div><?= $fcc_picker_copy['empty'] ?></div>
                    <div class="biolink-create-empty-reset">
                        <button type="button" class="btn btn-outline-light btn-sm" data-reset-filters><?= $fcc_picker_copy['reset_filters'] ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start() ?>
<script>
    'use strict';

    (() => {
        const modal = document.getElementById('biolink_link_create_modal');
        if(!modal) {
            return;
        }

        const searchForm = modal.querySelector('#fcc_biolink_block_picker_search_form');
        const searchInput = modal.querySelector('#fcc_biolink_block_picker_search');
        const groupFilter = modal.querySelector('#fcc_biolink_block_group_filter');
        const goalFilter = modal.querySelector('#fcc_biolink_block_goal_filter');
        const cards = Array.from(modal.querySelectorAll('[data-block-card]'));
        const sections = Array.from(modal.querySelectorAll('[data-purpose-section]'));
        const emptyState = modal.querySelector('#fcc_biolink_block_picker_empty');
        const resetFiltersButton = modal.querySelector('[data-reset-filters]');
        const aiNotificationContainer = modal.querySelector('#fcc_biolink_ai_missing_notification');

        if(!searchInput || !groupFilter || !goalFilter) {
            return;
        }

        const normalize = value => (value || '').toString().trim().toLowerCase();
        const setAiNotification = (message, status) => {
            if(!aiNotificationContainer) {
                return;
            }

            aiNotificationContainer.innerHTML = '';

            if(message) {
                display_notifications(message, status || 'info', aiNotificationContainer);
            }
        };
        const resetFilters = () => {
            searchInput.value = '';
            groupFilter.value = '';
            goalFilter.value = '';
        };

        const applyFilters = () => {
            const searchValue = normalize(searchInput.value);
            const groupValue = normalize(groupFilter.value);
            const goalValue = normalize(goalFilter.value);

            cards.forEach(card => {
                const blockSearch = normalize(card.getAttribute('data-block-search'));
                const blockGroup = normalize(card.getAttribute('data-block-group'));
                const blockGoals = normalize(card.getAttribute('data-block-goals')).split(',').filter(Boolean);

                const matchesSearch = !searchValue || blockSearch.includes(searchValue);
                const matchesGroup = !groupValue || blockGroup === groupValue;
                const matchesGoal = !goalValue || blockGoals.includes(goalValue);
                const isVisible = matchesSearch && matchesGroup && matchesGoal;

                card.classList.toggle('d-none', !isVisible);
            });

            let visibleCards = 0;
            sections.forEach(section => {
                const visibleInSection = section.querySelectorAll('[data-block-card]:not(.d-none)').length;
                const countLabel = section.getAttribute('data-count-label') || '';
                const countElement = section.querySelector('[data-purpose-count]');
                visibleCards += visibleInSection;
                section.classList.toggle('d-none', visibleInSection === 0);

                if(countElement) {
                    countElement.textContent = `${visibleInSection} ${countLabel}`.trim();
                }
            });

            if(emptyState) {
                emptyState.classList.toggle('d-none', visibleCards > 0);
            }
        };

        if(searchForm) {
            searchForm.addEventListener('submit', event => event.preventDefault());
        }

        ['keyup', 'change', 'search', 'input'].forEach(eventKey => {
            searchInput.addEventListener(eventKey, applyFilters);
        });

        groupFilter.addEventListener('change', applyFilters);
        goalFilter.addEventListener('change', applyFilters);

        if(resetFiltersButton) {
            resetFiltersButton.addEventListener('click', () => {
                resetFilters();
                applyFilters();
                searchInput.focus();
            });
        }

        modal.querySelectorAll('.js-add-ai-missing-block-modal').forEach(button => {
            button.addEventListener('click', () => {
                button.setAttribute('disabled', 'disabled');
                setAiNotification('', 'success');

                $.ajax({
                    type: 'POST',
                    url: `${url}link-ajax`,
                    data: {
                        token: <?= json_encode(\Altum\Csrf::get()) ?>,
                        request_type: 'add_ai_recommended_block',
                        link_id: button.getAttribute('data-link-id') || '',
                        recommendation_key: button.getAttribute('data-recommendation-key') || '',
                        block_type: button.getAttribute('data-block-type') || ''
                    },
                    dataType: 'json',
                    success: response => {
                        button.removeAttribute('disabled');
                        setAiNotification(response.message, response.status);

                        if(response.status === 'success' && response.details?.url) {
                            window.setTimeout(() => redirect(response.details.url, true), 250);
                        }
                    },
                    error: () => {
                        button.removeAttribute('disabled');
                        setAiNotification(<?= json_encode(l('global.error_message.basic')) ?>, 'error');
                    }
                });
            });
        });

        modal.querySelectorAll('.js-open-ai-block-picker-from-modal').forEach(button => {
            button.addEventListener('click', () => {
                const blockType = button.getAttribute('data-block-type') || '';
                const pickerSearch = (button.getAttribute('data-picker-search') || '').trim();
                const blockGroup = (button.getAttribute('data-block-group') || '').trim();
                const blockGoal = (button.getAttribute('data-block-goal') || '').trim();
                searchInput.value = pickerSearch || blockType.replace(/_/g, ' ');
                groupFilter.value = blockGroup;
                goalFilter.value = blockGoal;
                applyFilters();
                searchInput.focus();
            });
        });

        $('#biolink_link_create_modal').on('shown.bs.modal', () => {
            searchInput.focus();
            applyFilters();
        });

        $('#biolink_link_create_modal').on('hidden.bs.modal', () => {
            resetFilters();
            applyFilters();
            setAiNotification('', 'success');
        });

        window.fccBiolinkBlockPicker = {
            open() {
                $('#biolink_link_create_modal').modal('show');
            },
            reset() {
                resetFilters();
                applyFilters();
            },
            setFilters({search = null, group = null, goal = null} = {}) {
                if(typeof search === 'string') {
                    searchInput.value = search;
                }

                if(typeof group === 'string') {
                    groupFilter.value = group;
                }

                if(typeof goal === 'string') {
                    goalFilter.value = goal;
                }

                applyFilters();
            },
            focusSearch() {
                searchInput.focus();
            }
        };

        applyFilters();
    })();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
