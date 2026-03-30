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

/* Custom code: FC-2026-03-30: admin blog category english creation */
class AdminBlogPostsCategoryCreateEnglish extends Controller {

    public function index() {

        $blog_posts_category_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$blog_posts_category = db()->where('blog_posts_category_id', $blog_posts_category_id)->getOne('blog_posts_categories')) {
            redirect('admin/blog-posts-categories');
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/blog-posts-category-update/' . $blog_posts_category_id);
        }

        $target_language = array_search('en', \Altum\Language::$active_languages, true);

        if(!$target_language) {
            Alerts::add_error('English must be enabled in Languages before creating an English category.');
            redirect('admin/blog-posts-category-update/' . $blog_posts_category_id);
        }

        if($blog_posts_category->language === $target_language) {
            Alerts::add_warning('This category is already in English.');
            redirect('admin/blog-posts-category-update/' . $blog_posts_category_id);
        }

        $api_key = trim((string) (settings()->main->openai_api_key ?? settings()->aix->openai_api_key ?? ''));
        $model = trim((string) (settings()->main->openai_model ?? 'gpt-4o'));

        if($api_key === '') {
            Alerts::add_error('OpenAI API key is missing in settings.');
            redirect('admin/blog-posts-category-update/' . $blog_posts_category_id);
        }

        set_time_limit(0);
        session_write_close();

        try {
            $target_category = fc_get_or_create_blog_category_translation($blog_posts_category, $target_language, $api_key, $model);

            $this->resume_session();

            if(!$target_category) {
                Alerts::add_error('The English category could not be created.');
                redirect('admin/blog-posts-category-update/' . $blog_posts_category_id);
            }

            if((int) $target_category->blog_posts_category_id === (int) $blog_posts_category_id) {
                Alerts::add_warning('This category is already using the target English record.');
            } else {
                Alerts::add_success('English category created successfully.');
            }

            redirect('admin/blog-posts-category-update/' . $target_category->blog_posts_category_id);
        } catch (\Exception $exception) {
            $this->resume_session();

            Alerts::add_error('The English category could not be created: ' . $exception->getMessage());
            redirect('admin/blog-posts-category-update/' . $blog_posts_category_id);
        }
    }

    private function resume_session(): void {
        if(session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

}
/* /Custom code: FC-2026-03-30 */