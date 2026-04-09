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

/* Custom code: FC-2026-03-30: admin pages category english creation */
class AdminPagesCategoryCreateEnglish extends Controller {

    public function index() {

        $pages_category_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$pages_category = db()->where('pages_category_id', $pages_category_id)->getOne('pages_categories')) {
            redirect('admin/pages-categories');
        }

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('admin/pages-category-update/' . $pages_category_id);
        }

        $target_language = array_search('en', \Altum\Language::$active_languages, true);

        if(!$target_language) {
            Alerts::add_error(l('admin_pages_category_create_english.error_language_disabled'));
            redirect('admin/pages-category-update/' . $pages_category_id);
        }

        if($pages_category->language === $target_language) {
            Alerts::add_warning(l('admin_pages_category_create_english.warning_already_english'));
            redirect('admin/pages-category-update/' . $pages_category_id);
        }

        $api_key = trim((string) (settings()->main->openai_api_key ?? settings()->aix->openai_api_key ?? ''));
        $model = fc_get_resolved_openai_model(settings()->main->openai_model ?? '');

        if($api_key === '') {
            Alerts::add_error(l('admin_ai.error_missing_api_key'));
            redirect('admin/pages-category-update/' . $pages_category_id);
        }

        set_time_limit(0);
        session_write_close();

        try {
            $target_category = fc_get_or_create_pages_category_translation($pages_category, $target_language, $api_key, $model);

            $this->resume_session();

            if(!$target_category) {
                Alerts::add_error(l('admin_pages_category_create_english.error_create_failed'));
                redirect('admin/pages-category-update/' . $pages_category_id);
            }

            if((int) $target_category->pages_category_id === (int) $pages_category_id) {
                Alerts::add_warning(l('admin_pages_category_create_english.warning_target_same'));
            } else {
                Alerts::add_success(l('admin_pages_category_create_english.success_created'));
            }

            redirect('admin/pages-category-update/' . $target_category->pages_category_id);
        } catch (\Exception $exception) {
            $this->resume_session();

            Alerts::add_error(sprintf(l('admin_pages_category_create_english.error_failed'), $exception->getMessage()));
            redirect('admin/pages-category-update/' . $pages_category_id);
        }
    }

    private function resume_session(): void {
        if(session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

}
/* /Custom code: FC-2026-03-30 */
