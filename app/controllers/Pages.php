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
            $pages_category_query = "
                SELECT *
                FROM `pages_categories`
                WHERE `url` = '{$pages_category_url}'
                ORDER BY
                    CASE
                        WHEN `language` = '{$language}' THEN 0
                        WHEN `language` IS NULL THEN 1
                        ELSE 2
                    END,
                    `pages_category_id` ASC
                LIMIT 1
            ";
            $pages_category = $pages_category_url ? \Altum\Cache::cache_function_result('pages_category?hash=' . md5($pages_category_query), 'pages_categories', function() use ($pages_category_query) {
                return database()->query($pages_category_query)->fetch_object() ?? null;
            }) : null;

            /* Redirect to pages if the category is not found */
            if(!$pages_category) {
                throw_404();
            }

            /* Get the pages for this category */
            /* Custom code: FC-2026-02-24: pages thumbnails query */
            $pages_language = $pages_category->language ?? $language;

            $pages_result_query = "
                SELECT `url`, `title`, `description`, `total_views`, `type`, `language`, `plans_ids`, `image`, `image_description` 
                FROM `pages` 
                WHERE `pages_category_id` = {$pages_category->pages_category_id} AND (`language` = '{$pages_language}' OR `language` IS NULL) AND `is_published` = 1
                ORDER BY `order` ASC, `total_views` DESC
            ";
            /* /Custom code: FC-2026-02-24 */

            $pages = \Altum\Cache::cache_function_result('pages?hash=' . md5($pages_result_query), 'pages', function() use ($pages_result_query, $pages_category, $pages_language) {
                $pages_result = database()->query($pages_result_query);
                /* Custom code: FC-2026-02-24: fallback if thumbnail columns are missing */
                if($pages_result === false) {
                    $fallback_query = "
                        SELECT `url`, `title`, `description`, `total_views`, `type`, `language`, `plans_ids`
                        FROM `pages`
                        WHERE `pages_category_id` = {$pages_category->pages_category_id} AND (`language` = '{$pages_language}' OR `language` IS NULL) AND `is_published` = 1
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

            foreach($pages as $page) {
                if(!empty($page->image_url)) {
                    $social_image = $page->image_url;
                    break;
                }
            }

            if($pages_category->url === 'foreverclub') {
                if(\Altum\Language::$code === 'hr') {
                    $meta_title = 'Forever Card Club vodiči, FAQ i kako sustav radi';
                    $meta_description = 'Saznaj što je Forever Card Club, kako sustav radi i pronađi vodiče, FAQ i objašnjenja za pametne linkove, edukaciju, AI podršku i online razvoj Forever poslovanja.';
                    $meta_keywords = 'Forever Card Club, što je Forever Card Club, kako radi Forever Card Club, FAQ Forever Card Club, Forever poslovanje online, smart linkovi Forever, AI alati za Forever';
                    $foreverclub_semantics = [
                        'heading' => 'Što je Forever Card Club?',
                        'summary' => 'Forever Card Club je neovisni digitalni sustav za Forever partnere koji spaja osobnu aplikaciju, pametne linkove, AI podršku, edukaciju, analitiku i fizičku NFC karticu u jedan poslovni proces.',
                        'facts' => [
                            'Forever Card Club nije službena stranica kompanije Forever Living Products, nego neovisni sustav za partnere.',
                            'Forever Card označava partnerovu personaliziranu aplikaciju i povezanu fizičku NFC karticu koja vodi na isti digitalni sustav.',
                            'Kupnja proizvoda odvija se preko službenog Forever web shopa u državi kupca.',
                            'Sustav je namijenjen za online i offline prezentaciju proizvoda, preporuke i razvoj Forever poslovanja.'
                        ],
                        'term_name' => 'Forever Card Club',
                        'term_alternate_names' => ['FCC', 'Forever Card', 'Forever Card aplikacija'],
                        'term_description' => 'Forever Card Club je neovisni digitalni sustav za Forever partnere koji uključuje osobnu aplikaciju, pametne linkove, AI alate, edukaciju, analitiku i fizičku NFC karticu povezanu s partnerovim sadržajem i preporukama.'
                    ];
                } else {
                    $meta_title = 'Forever Card Club Guides, FAQ and How It Works';
                    $meta_description = 'Learn what Forever Card Club is, how the system works, and explore guides, FAQs, smart links, education, AI support, and online business tools for Forever partners.';
                    $meta_keywords = 'Forever Card Club, what is Forever Card Club, how Forever Card Club works, Forever Card Club FAQ, Forever business tools, smart links Forever, AI tools for Forever partners';
                    $foreverclub_semantics = [
                        'heading' => 'What Is Forever Card Club?',
                        'summary' => 'Forever Card Club is an independent digital system for Forever partners that combines a personal app, smart links, AI support, education, analytics, and a physical NFC card into one business workflow.',
                        'facts' => [
                            'Forever Card Club is not an official Forever Living Products website or store, but an independent system for partners.',
                            'Forever Card refers to the partner\'s personalized app and the connected physical NFC card that lead to the same digital system.',
                            'Product purchases are completed through the official Forever web shop in the customer\'s country.',
                            'The system is designed for online and offline product presentation, recommendations, and Forever business growth.'
                        ],
                        'term_name' => 'Forever Card Club',
                        'term_alternate_names' => ['FCC', 'Forever Card', 'Forever Card app'],
                        'term_description' => 'Forever Card Club is an independent digital system for Forever partners that includes a personal app, smart referral links, AI tools, education, analytics, and a physical NFC card connected to the partner\'s content and recommendations.'
                    ];
                }
            }
            /* /Custom code: FC-2026-03-24 */

            /* Prepare the view */
            $data = [
                'pages_category' => $pages_category,
                'pages' => $pages,
                /* Custom code: FC-2026-03-24: strengthen category SEO metadata for content hubs */
                'foreverclub_semantics' => $foreverclub_semantics,
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
