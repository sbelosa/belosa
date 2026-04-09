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

/* Custom code: FC-2026-03-30: admin page english draft creation */
class AdminPageCreateEnglish extends Controller {

    public function index() {

        $page_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$page = db()->where('page_id', $page_id)->getOne('pages')) {
            redirect('admin/pages');
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/page-update/' . $page_id);
        }

        $target_language = array_search('en', \Altum\Language::$active_languages, true);

        if(!$target_language) {
            Alerts::add_error(l('admin_page_create_english.error_language_disabled'));
            redirect('admin/page-update/' . $page_id);
        }

        if($page->language === $target_language) {
            Alerts::add_warning(l('admin_page_create_english.warning_already_english'));
            redirect('admin/page-update/' . $page_id);
        }

        if($page->type === 'internal' && db()->where('url', $page->url)->where('language', $target_language)->has('pages')) {
            $existing_page = db()->where('url', $page->url)->where('language', $target_language)->getOne('pages', ['page_id']);
            Alerts::add_warning(l('admin_page_create_english.warning_exists'));
            redirect('admin/page-update/' . $existing_page->page_id);
        }

        $api_key = trim((string) (settings()->main->openai_api_key ?? settings()->aix->openai_api_key ?? ''));
        $model = fc_get_resolved_openai_model(settings()->main->openai_model ?? '');

        if($api_key === '') {
            Alerts::add_error(l('admin_ai.error_missing_api_key'));
            redirect('admin/page-update/' . $page_id);
        }

        set_time_limit(0);
        session_write_close();

        try {
            $translated_fields = $this->translate_fields($page, $target_language, $model, $api_key);

            $source_category = null;
            if($page->pages_category_id) {
                $source_category = db()->where('pages_category_id', $page->pages_category_id)->getOne('pages_categories');
            }

            $target_category = fc_get_or_create_pages_category_translation($source_category, $target_language, $api_key, $model);
            $target_category_id = $target_category->pages_category_id ?? null;

            $insert_data = [
                'pages_category_id' => $target_category_id,
                'plans_ids' => $page->plans_ids,
                'url' => $page->url,
                'title' => input_clean($translated_fields['title'], 256),
                'description' => input_clean($translated_fields['description'], 256),
                'icon' => $page->icon,
                'keywords' => input_clean($translated_fields['keywords'], 256),
                'editor' => in_array($page->editor, ['wysiwyg', 'blocks', 'raw']) ? $page->editor : 'raw',
                'content' => $translated_fields['content'],
                'type' => $page->type,
                'position' => $page->position,
                'language' => $target_language,
                'open_in_new_tab' => (int) ($page->open_in_new_tab ?? 0),
                'order' => (int) ($page->order ?? 0),
                'is_published' => 0,
                'datetime' => get_date(),
                'last_datetime' => get_date(),
            ];

            static $pages_has_image_columns = null;
            if($pages_has_image_columns === null) {
                $pages_has_image_columns = !empty(db()->rawQuery("SHOW COLUMNS FROM `pages` LIKE 'image'")) && !empty(db()->rawQuery("SHOW COLUMNS FROM `pages` LIKE 'image_description'"));
            }

            if($pages_has_image_columns) {
                $insert_data['image'] = $page->image ?? null;
                $insert_data['image_description'] = input_clean($translated_fields['image_description'], 256);
            }

            db()->insert('pages', $insert_data);
            $new_page_id = db()->getInsertId();

            cache()->deleteItem('pages_' . $page->position);
            cache()->deleteItemsByTag('pages');

            $this->resume_session();

            Alerts::add_success(l('admin_page_create_english.success_created'));

            if($page->pages_category_id && !$target_category_id) {
                Alerts::add_warning(l('admin_page_create_english.warning_category_missing'));
            }

            redirect('admin/page-update/' . $new_page_id);
        } catch (\Exception $exception) {
            $this->resume_session();

            Alerts::add_error(sprintf(l('admin_page_create_english.error_failed'), $exception->getMessage()));
            redirect('admin/page-update/' . $page_id);
        }
    }

    private function translate_fields($page, string $target_language, string $model, string $api_key): array {
        $fields = [
            'title' => (string) ($page->title ?? ''),
            'description' => (string) ($page->description ?? ''),
            'keywords' => (string) ($page->keywords ?? ''),
            'image_description' => (string) ($page->image_description ?? ''),
            'content' => $page->type === 'internal' ? (string) ($page->content ?? '') : '',
        ];

        $source_language = !empty($page->language) ? $page->language : 'Croatian';

        $prompt = [
            'Translate the provided page fields from ' . $source_language . ' to ' . $target_language . '.',
            'Return only a valid JSON object with these exact keys: title, description, keywords, image_description, content.',
            'Preserve all HTML structure, attributes, CSS classes, inline styles, placeholders, shortcodes, bracket tokens, line breaks, and entities.',
            'Do not translate or modify URLs, route slugs, image filenames, app links, or technical identifiers.',
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

        if(substr($content, 0, 3) === '```') {
            $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);
        }

        $translated_fields = json_decode($content, true);

        if(!is_array($translated_fields)) {
            throw new \Exception('OpenAI did not return valid JSON.');
        }

        foreach(['title', 'description', 'keywords', 'image_description', 'content'] as $field) {
            if(!array_key_exists($field, $translated_fields)) {
                throw new \Exception('Translated response is missing the ' . $field . ' field.');
            }

            $translated_fields[$field] = is_string($translated_fields[$field]) ? trim($translated_fields[$field]) : '';
        }

        return $translated_fields;
    }

    private function resume_session(): void {
        if(session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

}
/* /Custom code: FC-2026-03-30 */
