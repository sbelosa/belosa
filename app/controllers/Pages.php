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

use Altum\Language;
use Altum\Meta;
use Altum\Title;

defined('ALTUMCODE') || die();

class Pages extends Controller {

    public function index() {

        if(!settings()->content->pages_is_enabled) {
            throw_404();
        }

        /* Check if the category url is set */
        $pages_category_url = isset($this->params[0]) ? query_clean($this->params[0]) : null;
        $language = Language::$name;

        /* If the category url is set, get it*/
        if($pages_category_url) {

            /* Pages category index */
            $pages_category_cluster = fc_get_pages_category_cluster($pages_category_url, $language);
            $pages_category = $pages_category_cluster['category'] ?? null;

            /* Redirect to pages if the category is not found */
            if(!$pages_category) {
                if(fc_internal_page_uses_pages_route($pages_category_url)) {
                    $page_controller = \Altum\Router::get_controller('Page');
                    $page_controller->add_params([
                        'params' => [$pages_category_url],
                        'user' => $this->user ?? null,
                    ]);

                    $page_controller->index();
                    \Altum\Router::$controller_key = 'page';
                    \Altum\Router::$controller = 'Page';
                    $this->views = $page_controller->views;

                    return;
                }

                throw_404();
            }

            /* Get the pages for this category */
            /* Custom code: FC-2026-02-24: pages thumbnails query */
            $pages_language = fc_resolve_language_name($language) ?? $pages_category->language ?? $language;
            $pages_category_ids = $pages_category_cluster['ids'] ?? [];

            if(!$pages_category_ids && !empty($pages_category->pages_category_id)) {
                $pages_category_ids[] = (int) $pages_category->pages_category_id;
            }

            $pages_category_ids_sql = implode(',', array_map('intval', $pages_category_ids));

            $pages_result_query = "
                SELECT `url`, `title`, `description`, `total_views`, `type`, `language`, `plans_ids`, `image`, `image_description` 
                FROM `pages` 
                WHERE `pages_category_id` IN ({$pages_category_ids_sql}) AND (`language` = '{$pages_language}' OR `language` IS NULL) AND `is_published` = 1
                ORDER BY `order` ASC, `total_views` DESC
            ";
            /* /Custom code: FC-2026-02-24 */

            $pages = \Altum\Cache::cache_function_result('pages?hash=' . md5($pages_result_query), 'pages', function() use ($pages_result_query, $pages_category_ids_sql, $pages_language) {
                $pages_result = database()->query($pages_result_query);
                /* Custom code: FC-2026-02-24: fallback if thumbnail columns are missing */
                if($pages_result === false) {
                    $fallback_query = "
                        SELECT `url`, `title`, `description`, `total_views`, `type`, `language`, `plans_ids`
                        FROM `pages`
                        WHERE `pages_category_id` IN ({$pages_category_ids_sql}) AND (`language` = '{$pages_language}' OR `language` IS NULL) AND `is_published` = 1
                        ORDER BY `order` ASC, `total_views` DESC
                    ";
                    $pages_result = database()->query($fallback_query);
                }

                if($pages_result === false) {
                    return [];
                }
                /* /Custom code: FC-2026-02-24 */

                /* Iterate over the blog posts */
                $pages = [];

                while($row = $pages_result->fetch_object()) {
                    $row->plans_ids = json_decode($row->plans_ids ?? '');
                    /* Custom code: FC-2026-02-24: pages thumbnails */
                    $row->image_url = !empty($row->image) ? \Altum\Uploads::get_full_url('pages') . $row->image : null;
                    /* /Custom code: FC-2026-02-24 */
                    $pages[] = $row;
                }

                return $pages;
            });

            foreach($pages as $key => $page) {
                if(!empty($page->plans_ids)) {
                    if(!is_logged_in()) unset($pages[$key]);

                    if(!in_array(user()->plan_id, $page->plans_ids)) {
                        unset($pages[$key]);
                    }
                }
            }

            /* Custom code: FC-2026-03-24: strengthen category SEO metadata for content hubs */
            $pages = array_values($pages);

            $pages_category_url = SITE_URL . ($pages_category->language ? ((\Altum\Language::$active_languages[$pages_category->language] ?? null) ? \Altum\Language::$active_languages[$pages_category->language] . '/' : null) : null) . 'pages/' . $pages_category->url;
            $meta_title = $pages_category->title;
            $meta_description = $pages_category->description ?: 'Browse all articles and resources from this content hub.';
            $meta_keywords = null;
            $social_image = null;
            $foreverclub_semantics = null;
            $foreverclub_landing_pages = [];
            $foreverclub_workflow = [];
            $foreverclub_use_cases = [];

            foreach($pages as $page) {
                if(!empty($page->image_url)) {
                    $social_image = $page->image_url;
                    break;
                }
            }

            if($pages_category->url === 'foreverclub') {
                $foreverclub_landing_slugs = [
                    'forever-business-tools',
                    'build-forever-business-online',
                    'smart-referral-links-for-forever-partners',
                    'ai-product-assistants-for-forever-partners',
                    'lead-funnel-for-forever-partners',
                    'nfc-card-for-forever-follow-up',
                    'fcc-vs-linktree',
                    'fcc-vs-gohighlevel',
                ];

                foreach($pages as $page) {
                    if(in_array($page->url, $foreverclub_landing_slugs, true)) {
                        $foreverclub_landing_pages[] = $page;
                    }
                }

                if(\Altum\Language::$code === 'hr') {
                    $meta_title = 'Forever Card Club (FCC): vodiči za cjeloviti digitalni poslovni sustav za Forever partnere';
                    $meta_description = 'Saznaj kako Forever Card Club (FCC) kao cjeloviti digitalni poslovni sustav pomaže Forever Living Products partnerima kroz osobnu aplikaciju, pametne preporučne linkove, AI asistente za proizvode, sustav za kontakte i NFC karticu.';
                    $meta_keywords = 'Forever Card Club, FCC, digitalni poslovni sustav, Forever Living Products partneri, pametni preporučni linkovi Forever, AI asistenti za proizvode Forever, sustav za kontakte Forever, NFC kartica Forever';
                } else {
                    $meta_title = 'Forever Card Club (FCC): guides for an all-in-one digital business system for Forever partners';
                    $meta_description = 'Learn how Forever Card Club (FCC) works as an all-in-one digital business system for Forever Living Products partners through a personal app, smart referral links, AI product assistants, lead funnel, and NFC card.';
                    $meta_keywords = 'Forever Card Club, FCC, all-in-one digital business system, Forever Living Products partners, smart referral links Forever, AI product assistants Forever, lead funnel Forever, NFC card Forever';
                }

                $foreverclub_semantics = [
                    'heading' => l('fcc.page.semantics_heading'),
                    'summary' => l('fcc.page.semantics_summary'),
                    'facts' => [
                        l('fcc.page.semantics_fact_1'),
                        l('fcc.page.semantics_fact_2'),
                        l('fcc.page.semantics_fact_3'),
                        l('fcc.page.semantics_fact_4'),
                    ],
                    'solves_heading' => l('fcc.page.semantics_solves_heading'),
                    'solves' => [
                        l('fcc.page.semantics_solve_1'),
                        l('fcc.page.semantics_solve_2'),
                        l('fcc.page.semantics_solve_3'),
                        l('fcc.page.semantics_solve_4'),
                        l('fcc.page.semantics_solve_5'),
                    ],
                    'term_name' => 'Forever Card Club',
                    'term_alternate_names' => ['FCC', 'Forever Card Club (FCC)'],
                    'term_description' => l('fcc.page.term_description'),
                ];
                $foreverclub_workflow = [
                    'heading' => l('fcc.page.workflow_heading'),
                    'intro' => l('fcc.page.workflow_intro'),
                    'steps' => [
                        ['title' => l('fcc.page.workflow_step_1_title'), 'text' => l('fcc.page.workflow_step_1_text')],
                        ['title' => l('fcc.page.workflow_step_2_title'), 'text' => l('fcc.page.workflow_step_2_text')],
                        ['title' => l('fcc.page.workflow_step_3_title'), 'text' => l('fcc.page.workflow_step_3_text')],
                        ['title' => l('fcc.page.workflow_step_4_title'), 'text' => l('fcc.page.workflow_step_4_text')],
                        ['title' => l('fcc.page.workflow_step_5_title'), 'text' => l('fcc.page.workflow_step_5_text')],
                    ],
                ];
                $foreverclub_use_cases = [
                    'heading' => l('fcc.page.use_cases_heading'),
                    'intro' => l('fcc.page.use_cases_intro'),
                    'items' => [
                        ['title' => l('fcc.page.use_case_1_title'), 'text' => l('fcc.page.use_case_1_text')],
                        ['title' => l('fcc.page.use_case_2_title'), 'text' => l('fcc.page.use_case_2_text')],
                        ['title' => l('fcc.page.use_case_3_title'), 'text' => l('fcc.page.use_case_3_text')],
                        ['title' => l('fcc.page.use_case_4_title'), 'text' => l('fcc.page.use_case_4_text')],
                        ['title' => l('fcc.page.use_case_5_title'), 'text' => l('fcc.page.use_case_5_text')],
                        ['title' => l('fcc.page.use_case_6_title'), 'text' => l('fcc.page.use_case_6_text')],
                    ],
                ];
            }
            /* /Custom code: FC-2026-03-24 */

            /* Prepare the view */
            $data = [
                'pages_category' => $pages_category,
                'pages' => $pages,
                /* Custom code: FC-2026-03-24: strengthen category SEO metadata for content hubs */
                'foreverclub_semantics' => $foreverclub_semantics,
                'foreverclub_landing_pages' => $foreverclub_landing_pages,
                'foreverclub_workflow' => $foreverclub_workflow,
                'foreverclub_use_cases' => $foreverclub_use_cases,
                'pages_category_url' => $pages_category_url,
                /* /Custom code: FC-2026-03-24 */
            ];

            $view = new \Altum\View('pages/pages_category', (array) $this);

            /* Set a custom title */
            /* Custom code: FC-2026-03-24: strengthen category SEO metadata for content hubs */
            Title::set($meta_title);
            /* /Custom code: FC-2026-03-24 */

            /* Meta */
            /* Custom code: FC-2026-03-24: strengthen category SEO metadata for content hubs */
            Meta::set_description(string_truncate($meta_description, 160));
            if($meta_keywords) {
                Meta::set_keywords(string_truncate($meta_keywords, 255));
            }
            if($social_image) {
                Meta::set_social_image($social_image);
            }
            Meta::set_canonical_url($pages_category_url);
            Meta::set_robots('index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1');
            /* /Custom code: FC-2026-03-24 */

            /* Disable automated link language alternate */
            Meta::set_link_alternate(false);

        } else {

            /* Pages index */

            /* Get the popular pages */
            $popular_pages_result_query = "SELECT `url`, `title`, `description`, `total_views`, `type`, `language`, `plans_ids`, `image`, `image_description` FROM `pages` WHERE (`language` = '{$language}' OR `language` IS NULL) AND `is_published` = 1 ORDER BY `total_views` DESC LIMIT 6";

            $popular_pages = settings()->content->pages_popular_widget_is_enabled ? \Altum\Cache::cache_function_result('pages?hash=' . md5($popular_pages_result_query), 'pages', function() use ($popular_pages_result_query) {

                $pages_result = database()->query($popular_pages_result_query);

                /* Iterate over the blog posts */
                $popular_pages = [];

                while($row = $pages_result->fetch_object()) {
                    $row->plans_ids = json_decode($row->plans_ids ?? '');
                    $row->image_url = !empty($row->image) ? \Altum\Uploads::get_full_url('pages') . $row->image : null;
                    $popular_pages[] = $row;
                }

                return $popular_pages;
            }) : [];

            foreach($popular_pages as $key => $page) {
                if(!empty($page->plans_ids)) {
                    if(!is_logged_in()) unset($popular_pages[$key]);

                    if(!in_array(user()->plan_id, $page->plans_ids)) {
                        unset($popular_pages[$key]);
                    }
                }
            }

            /* Get all the pages categories */
            $pages_categories_result_query = "
                SELECT 
                    `pages_categories`.`url`,
                    `pages_categories`.`title`,
                    `pages_categories`.`icon`,
                    `pages_categories`.`language`,
                    COUNT(`pages`.`page_id`) AS `total_pages`
                FROM `pages_categories`
                LEFT JOIN `pages` ON `pages`.`pages_category_id` = `pages_categories`.`pages_category_id`
                WHERE (`pages_categories`.`language` = '{$language}' OR `pages_categories`.`language` IS NULL)
                GROUP BY `pages_categories`.`pages_category_id`
                ORDER BY `pages_categories`.`order` ASC
            ";

            $pages_categories = \Altum\Cache::cache_function_result('pages?hash=' . md5($pages_categories_result_query), 'pages_categories', function() use ($pages_categories_result_query) {
                $pages_result = database()->query($pages_categories_result_query);

                /* Iterate over the blog posts */
                $pages_categories = [];

                while($row = $pages_result->fetch_object()) {
                    $pages_categories[] = $row;
                }

                return $pages_categories;
            });

            /* Prepare the view */
            $data = [
                'popular_pages' => $popular_pages,
                'pages_categories' => $pages_categories
            ];

            $view = new \Altum\View('pages/index', (array) $this);
        }

        $this->add_view_content('content', $view->run($data));

    }

}
