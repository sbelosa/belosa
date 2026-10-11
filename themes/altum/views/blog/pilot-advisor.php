<?php defined('ALTUMCODE') || die(); ?>
<?php
$fcc_blog_page_language_code = !empty($data->blog_post->language) && isset(\Altum\Language::$active_languages[$data->blog_post->language])
    ? \Altum\Language::$active_languages[$data->blog_post->language]
    : (\Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr');

$fcc_blog_chat_enabled = !fcc_market_contact_only($data->resolved_market_country_code??null) && !empty($data->ai_chat_owner_user_id)
    && fcc_ai_user_has_public_ai_access((int) $data->ai_chat_owner_user_id);
$fcc_blog_chat_assistant = $fcc_blog_chat_enabled
    ? fcc_ai_get_assistant_for_user((int)$data->ai_chat_owner_user_id, 'product_advisor', 'public_blog')
    : null;
$fcc_blog_chat_enabled = $fcc_blog_chat_enabled && (int)($fcc_blog_chat_assistant->is_enabled??0)===1;

$fcc_blog_chat_language_code = !empty($data->ai_chat_owner_user_id)
    ? fcc_ai_get_public_assistant_default_language((int) $data->ai_chat_owner_user_id, 'product_advisor', 'public_blog', $fcc_blog_page_language_code)
    : fcc_ai_resolve_public_reply_language($fcc_blog_page_language_code);
?>

<?php if($fcc_blog_chat_enabled): ?>
    <?= include_view(THEME_PATH . 'views/l/partials/fcc_chat_extreme_popup.php', [
        'config' => [
            'assistant_type' => 'product_advisor',
            'scope' => 'public_blog',
            'link_id' => (int) ($data->ai_chat_owner_link_id ?? 0),
            'blog_post_id' => (int) ($data->blog_post->blog_post_id ?? 0),
            'owner_name' => (string) ($data->ai_chat_owner_name ?? ''),
            'language_code' => $fcc_blog_chat_language_code,
            'source_context' => fcc_t('FCC blog article'),
            'market_code' => (string)($data->resolved_market_country_code??''),
            'hide_without_context' => true,
            'dom_id' => 'fcc-blog-chat-extreme-' . (int) ($data->blog_post->blog_post_id ?? 0),
            'intro_label' => fcc_t('FCC Preporuka'),
        ],
    ]) ?>
<?php endif ?>
