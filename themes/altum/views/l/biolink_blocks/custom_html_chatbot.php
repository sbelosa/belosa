<?php defined('ALTUMCODE') || die() ?>

<?php
$is_pets_chatbot = ($data->link->type ?? null) === 'custom_html_chatbot_pets';
$block_unique_id = (int) ($data->link->biolink_block_id ?? 0);

if(!$block_unique_id) {
    $block_unique_id = (int) ($data->link->link_id ?? 0);
}

$chatbot_assistant_type = $is_pets_chatbot ? 'pets_advisor' : 'product_advisor';
$chatbot_scope = 'public_app';
$chatbot_owner_link_id = (int) ($data->link->link_id ?? $data->biolink->link_id ?? 0);
$chatbot_owner_user_id = (int) ($data->link->user_id ?? $data->user->user_id ?? 0);

if($chatbot_owner_user_id <= 0 || !fcc_ai_user_has_public_ai_access($chatbot_owner_user_id)) {
    return;
}

$chatbot_owner_name = trim((string) ($data->user->name ?? ''));
$chatbot_page_language_code = trim((string) ($data->link->settings->language_code ?? \Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr'));
$chatbot_language_code = $chatbot_owner_user_id > 0
    ? fcc_ai_get_public_assistant_default_language($chatbot_owner_user_id, $chatbot_assistant_type, 'public_app', $chatbot_page_language_code)
    : fcc_ai_resolve_public_reply_language($chatbot_page_language_code);
$chatbot_is_english = str_starts_with(mb_strtolower($chatbot_language_code), 'en');
$chatbot_dom_id = 'fcc-chatbot-' . $block_unique_id;
$chatbot_source_context = $is_pets_chatbot
    ? ($chatbot_is_english ? 'FCC pet app popup chat' : 'FCC pet app popup chat')
    : ($chatbot_is_english ? 'FCC app popup chat' : 'FCC app popup chat');
$chatbot_intro_label = $is_pets_chatbot ? 'Extreme Chat Pets' : 'FCC Preporuka';
$chatbot_input_placeholder = $is_pets_chatbot
    ? ($chatbot_is_english ? 'Tell me about your pet' : 'Napišite nešto o ljubimcu')
    : ($chatbot_is_english ? 'What would you like to know?' : 'Što vas zanima?');
?>

<?php ob_start() ?>
<?= include_view(THEME_PATH . 'views/l/partials/fcc_chat_extreme_popup.php', [
    'config' => [
        'assistant_type' => $chatbot_assistant_type,
        'scope' => $chatbot_scope,
        'link_id' => $chatbot_owner_link_id,
        'blog_post_id' => 0,
        'owner_name' => $chatbot_owner_name,
        'language_code' => $chatbot_language_code,
        'source_context' => $chatbot_source_context,
        'hide_without_context' => false,
        'dom_id' => $chatbot_dom_id,
        'intro_label' => $chatbot_intro_label,
        'input_placeholder' => $chatbot_input_placeholder,
    ],
]) ?>
<?php \Altum\Event::add_content(ob_get_clean(), 'modals', 'fcc_chatbot_' . $chatbot_dom_id) ?>
