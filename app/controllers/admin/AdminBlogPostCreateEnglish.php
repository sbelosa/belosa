<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
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

namespace Altum\Controllers;

use Altum\Alerts;

defined('ALTUMCODE') || die();

/* Custom code: FC-2026-03-30: admin blog post english draft creation */
class AdminBlogPostCreateEnglish extends Controller {

    public function index() {

        $blog_post_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$blog_post = db()->where('blog_post_id', $blog_post_id)->getOne('blog_posts')) {
            redirect('admin/blog-posts');
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/blog-post-update/' . $blog_post_id);
        }

        $target_language = array_search('en', \Altum\Language::$active_languages, true);

        if(!$target_language) {
            Alerts::add_error(l('admin_blog_post_create_english.error_language_disabled'));
            redirect('admin/blog-post-update/' . $blog_post_id);
        }

        if($blog_post->language === $target_language) {
            Alerts::add_warning(l('admin_blog_post_create_english.warning_already_english'));
            redirect('admin/blog-post-update/' . $blog_post_id);
        }

        if($existing_blog_post = db()->where('url', $blog_post->url)->where('language', $target_language)->getOne('blog_posts', ['blog_post_id'])) {
            Alerts::add_warning(l('admin_blog_post_create_english.warning_exists'));
            redirect('admin/blog-post-update/' . $existing_blog_post->blog_post_id);
        }

        $api_key = trim((string) (settings()->main->openai_api_key ?? settings()->aix->openai_api_key ?? ''));
        $model = fc_get_resolved_openai_model(settings()->main->openai_model ?? '');

        if($api_key === '') {
            Alerts::add_error(l('admin_ai.error_missing_api_key'));
            redirect('admin/blog-post-update/' . $blog_post_id);
        }

        set_time_limit(0);
        session_write_close();

        try {
            $translated_fields = $this->translate_fields($blog_post, $target_language, $model, $api_key);

            static $blog_posts_has_search_aliases_column = null;
            if($blog_posts_has_search_aliases_column === null) {
                $blog_posts_has_search_aliases_column = (bool) count(db()->rawQuery("SHOW COLUMNS FROM `blog_posts` LIKE 'search_aliases'"));
            }

            $source_category = null;
            if($blog_post->blog_posts_category_id) {
                $source_category = db()->where('blog_posts_category_id', $blog_post->blog_posts_category_id)->getOne('blog_posts_categories');
            }

            $target_category = fc_get_or_create_blog_category_translation($source_category, $target_language, $api_key, $model);
            $target_category_id = $target_category->blog_posts_category_id ?? null;

            $blog_post_data = [
                'blog_posts_category_id' => $target_category_id,
                'url' => $blog_post->url,
                'title' => input_clean($translated_fields['title'], 256),
                'description' => input_clean($translated_fields['description'], 256),
                'keywords' => input_clean($translated_fields['keywords'], 256),
                'image' => $blog_post->image,
                'image_description' => input_clean($translated_fields['image_description'], 256),
                'editor' => in_array($blog_post->editor, ['wysiwyg', 'blocks', 'raw']) ? $blog_post->editor : 'raw',
                'content' => $translated_fields['content'],
                'language' => $target_language,
                'is_published' => 0,
                'datetime' => get_date(),
                'last_datetime' => get_date(),
                'webshop_links' => $blog_post->webshop_links,
                'sku' => $blog_post->sku,
            ];

            if($blog_posts_has_search_aliases_column) {
                $search_aliases_array = preg_split('/[\r\n,]+/', $translated_fields['search_aliases'] ?? '');
                $search_aliases_array = array_filter(array_map(function($value) {
                    return trim(input_clean($value, 128));
                }, $search_aliases_array));
                $search_aliases_array = array_values(array_unique($search_aliases_array));

                $blog_post_data['search_aliases'] = mb_substr(implode(', ', $search_aliases_array), 0, 2000);
            }

            db()->insert('blog_posts', $blog_post_data);
            $new_blog_post_id = db()->getInsertId();

            cache()->deleteItemsByTag('blog_posts');

            $this->resume_session();

            Alerts::add_success(l('admin_blog_post_create_english.success_created'));

            if($blog_post->blog_posts_category_id && !$target_category_id) {
                Alerts::add_warning(l('admin_blog_post_create_english.warning_category_missing'));
            }

            redirect('admin/blog-post-update/' . $new_blog_post_id);
        } catch (\Exception $exception) {
            $this->resume_session();

            Alerts::add_error(sprintf(l('admin_blog_post_create_english.error_failed'), $exception->getMessage()));
            redirect('admin/blog-post-update/' . $blog_post_id);
        }

    }

    private function translate_fields($blog_post, string $target_language, string $model, string $api_key): array {
        $fields = [
            'title' => (string) ($blog_post->title ?? ''),
            'description' => (string) ($blog_post->description ?? ''),
            'keywords' => (string) ($blog_post->keywords ?? ''),
            'search_aliases' => (string) ($blog_post->search_aliases ?? ''),
            'image_description' => (string) ($blog_post->image_description ?? ''),
            'content' => (string) ($blog_post->content ?? ''),
        ];

        $source_language = !empty($blog_post->language) ? $blog_post->language : 'Croatian';

        $prompt = [
            'Translate the provided blog post fields from ' . $source_language . ' to ' . $target_language . '.',
            'Return only a valid JSON object with these exact keys: title, description, keywords, search_aliases, image_description, content.',
            'Preserve all HTML structure, attributes, CSS classes, inline styles, placeholders, shortcodes, bracket tokens, line breaks, and entities.',
            'Do not translate or modify URLs, webshop links, image filenames, SKU codes, product codes, brand names, or Forever product names.',
            'Translate only human-readable text that should appear to readers in English.',
            'If a field is empty, keep it empty.',
            'Input JSON:' . json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $response = \Unirest\Request::post(
            'https://api.openai.com/v1/chat/completions',
            [
                'Authorization' => 'Bearer ' . get_random_line_from_text($api_key),
                'Content-Type' => 'application/json',
            ],
            \Unirest\Request\Body::json([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional website translator. Return JSON only.'
                    ],
                    [
                        'role' => 'user',
                        'content' => implode("\n\n", $prompt)
                    ],
                ],
            ])
        );

        if($response->code >= 400) {
            throw new \Exception($response->body->error->message ?? 'OpenAI request failed.');
        }

        $content = trim((string) ($response->body->choices[0]->message->content ?? ''));
        $content = $this->strip_code_fences($content);
        $translated_fields = json_decode($content, true);

        if(!is_array($translated_fields)) {
            throw new \Exception('OpenAI did not return valid JSON.');
        }

        foreach(['title', 'description', 'keywords', 'search_aliases', 'image_description', 'content'] as $field) {
            if(!array_key_exists($field, $translated_fields)) {
                throw new \Exception('Translated response is missing the ' . $field . ' field.');
            }

            $translated_fields[$field] = is_string($translated_fields[$field]) ? trim($translated_fields[$field]) : '';
        }

        return $translated_fields;
    }

    private function strip_code_fences(string $content): string {
        if(substr($content, 0, 3) !== '```') {
            return $content;
        }

        $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        return trim($content);
    }

    private function resume_session(): void {
        if(session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

}
/* /Custom code: FC-2026-03-30 */
