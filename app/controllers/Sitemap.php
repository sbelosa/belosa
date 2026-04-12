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

defined('ALTUMCODE') || die();

class Sitemap extends Controller {

    public function index() {

        /* Set the header as xml so the browser can read it properly */
        header('Content-Type: text/xml');

        $view = new \Altum\View('sitemap/sitemap_index', (array) $this);

        echo $view->run();

    }

    public function main() {
        /* Set the header as xml so the browser can read it properly */
        header('Content-Type: text/xml');

        $sitemap_urls = [
            ['loc' => SITE_URL, 'lastmod' => null],
            ['loc' => SITE_URL . 'login', 'lastmod' => null],
            ['loc' => SITE_URL . 'lost-password', 'lastmod' => null],
            ['loc' => SITE_URL . 'llms.txt', 'lastmod' => null],
        ];

        if(settings()->users->email_confirmation) {
            $sitemap_urls[] = ['loc' => SITE_URL . 'resend-activation', 'lastmod' => null];
        }

        if(settings()->users->register_is_enabled) {
            $sitemap_urls[] = ['loc' => SITE_URL . 'register', 'lastmod' => null];
        }

        if(\Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled) {
            $sitemap_urls[] = ['loc' => SITE_URL . 'affiliate', 'lastmod' => null];
        }

        if(settings()->main->api_is_enabled) {
            $sitemap_urls[] = ['loc' => SITE_URL . 'api-documentation', 'lastmod' => null];
        }

        if(settings()->email_notifications->contact && !empty(settings()->email_notifications->emails)) {
            $sitemap_urls[] = ['loc' => SITE_URL . 'contact', 'lastmod' => null];
        }

        if(settings()->payment->is_enabled) {
            $sitemap_urls[] = ['loc' => SITE_URL . 'plan', 'lastmod' => null];
        }

        if(settings()->content->pages_is_enabled) {
            $sitemap_urls[] = ['loc' => SITE_URL . 'pages', 'lastmod' => null];
        }

        if(settings()->content->blog_is_enabled) {
            $sitemap_urls[] = ['loc' => SITE_URL . 'blog', 'lastmod' => null];
        }

        if(settings()->links->directory_is_enabled && settings()->links->directory_access == 'everyone') {
            $sitemap_urls[] = ['loc' => SITE_URL . 'directory', 'lastmod' => null];
        }

        if(settings()->links->biolinks_is_enabled) {
            $sitemap_urls[] = ['loc' => SITE_URL . 'featured-apps', 'lastmod' => null];
            $sitemap_urls[] = ['loc' => SITE_URL . 'recommended-sponsors', 'lastmod' => null];
        }

        if(settings()->tools->is_enabled && settings()->tools->access == 'everyone') {
            foreach ((require APP_PATH . 'includes/tools/tools.php') as $key => $value) {
                if(settings()->tools->available_tools->{$key}) {
                    $sitemap_urls[] = ['loc' => SITE_URL . 'tools/' . str_replace('_', '-', $key), 'lastmod' => null];
                }
            }
        }

        /* Multilingual */
        $new_sitemap_urls = [];

        foreach(\Altum\Language::$active_languages as $language_name => $language_code) {
            foreach($sitemap_urls as $entry) {
                $relative_url = str_replace(SITE_URL, '', $entry['loc']);
                $new_sitemap_urls[] = [
                    'loc' => settings()->main->default_language == $language_name ? SITE_URL . $relative_url : SITE_URL . $language_code . '/' . $relative_url,
                    'lastmod' => $entry['lastmod'],
                ];
            }
        }

        if(settings()->content->pages_is_enabled) {
            $pages = db()->where('type', 'internal')->where('is_published', 1)->get('pages', null, ['url', 'language', 'last_datetime', 'datetime']);
            $pages_categories = db()->get('pages_categories', null, ['url', 'language']);

            foreach ($pages as $page) {
                $new_sitemap_urls[] = [
                    'loc' => fc_get_internal_page_url($page->url, $page->language),
                    'lastmod' => $page->last_datetime ?? $page->datetime ?? null,
                ];
            }

            foreach ($pages_categories as $pages_category) {
                $new_sitemap_urls[] = [
                    'loc' => SITE_URL . ($pages_category->language ? \Altum\Language::$active_languages[$pages_category->language] . '/' : '') . 'pages/' . $pages_category->url,
                    'lastmod' => null,
                ];
            }
        }

        if(settings()->content->blog_is_enabled) {
            $blog_posts = db()->where('is_published', 1)->get('blog_posts', null, ['url', 'language', 'last_datetime', 'datetime']);
            $blog_posts_categories = db()->get('blog_posts_categories', null, ['url', 'language']);

            foreach ($blog_posts as $blog_post) {
                $new_sitemap_urls[] = [
                    'loc' => SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : '') . 'blog/' . $blog_post->url,
                    'lastmod' => $blog_post->last_datetime ?? $blog_post->datetime ?? null,
                ];
            }

            foreach ($blog_posts_categories as $blog_posts_category) {
                $new_sitemap_urls[] = [
                    'loc' => SITE_URL . ($blog_posts_category->language ? \Altum\Language::$active_languages[$blog_posts_category->language] . '/' : '') . 'blog/category/' . $blog_posts_category->url,
                    'lastmod' => null,
                ];
            }
        }

        if(settings()->links->biolinks_is_enabled) {
            $recommended_sponsors = fcc_featured_get_catalog([
                'language' => \Altum\Language::$code,
                'min_signal_30d' => 50,
                'experience_signal_target' => 50,
                'weekly_check_target' => 15,
                'require_experience_signal' => true,
                'require_valid_sales_link' => true,
            ]);

            foreach($recommended_sponsors as $sponsor) {
                foreach(\Altum\Language::$active_languages as $language_name => $language_code) {
                    $prefix = settings()->main->default_language == $language_name ? '' : $language_code . '/';

                    $new_sitemap_urls[] = [
                        'loc' => SITE_URL . $prefix . 'recommended-sponsors/' . $sponsor['profile_slug'],
                        'lastmod' => $sponsor['link_lastmod'] ?? null,
                    ];
                }
            }
        }


        /* Main View */
        $data = [
            'sitemap_urls' => $new_sitemap_urls,
        ];

        $view = new \Altum\View('sitemap/sitemap_main', (array) $this);

        echo $view->run($data);

    }

    public function links() {
        if(!settings()->links->biolinks_is_enabled) {
            throw_404();
        }

        /* Set the header as xml so the browser can read it properly */
        header('Content-Type: text/xml');

        /* How many per sitemap page */
        $pagination = 10000;

        $page = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Different answers for different parts */
        switch($page) {

            /* Sitemap index */
            case null:

                /* Get the total amount of sitemap-eligible main-domain links */
                $total_links = database()->query("
                    SELECT
                        COUNT(`links`.`link_id`) AS `total` 
                    FROM 
                        `links`
                    LEFT JOIN
                        `users` ON `links`.`user_id` = `users`.`user_id`
                    WHERE
                        `users`.`status` = 1
                        AND `links`.`is_enabled` = 1
                        AND `links`.`type` IN ('biolink','static')
                        AND `links`.`domain_id` = 0
                        AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`links`.`settings`, '$.seo.block')), '0') != '1'
                  ")->fetch_object()->total ?? 0;

                /* Calculate the needed sitemaps */
                $total_sitemaps = ceil((int) $total_links / $pagination);

                /* Prepare the urls */
                $sitemap_urls = [];

                foreach(range(1, $total_sitemaps) as $key) {
                    $sitemap_urls[] = SITE_URL . 'sitemap/links/' . $key;
                }

                /* Main View */
                $data = [
                    'sitemap_urls' => $sitemap_urls,
                ];

                $view = new \Altum\View('sitemap/sitemap_links', (array) $this);

                echo $view->run($data);

                break;

            /* Output only indexed external users */
            default:

                $limit_start = ($page - 1) * $pagination;

                /* Get the sitemap-eligible main-domain links */
                $result = database()->query("
                    SELECT
                        `links`.`url`,
                        `links`.`datetime`
                    FROM 
                        `links`
                    LEFT JOIN
                        `users` ON `links`.`user_id` = `users`.`user_id`
                    WHERE
                        `users`.`status` = 1
                        AND `links`.`is_enabled` = 1
                        AND `links`.`type` IN ('biolink','static')
                        AND `links`.`domain_id` = 0
                        AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`links`.`settings`, '$.seo.block')), '0') != '1'
                    LIMIT 
                        {$limit_start}, {$pagination}
                ");

                /* Main View */
                $data = [
                    'result' => $result,
                ];

                $view = new \Altum\View('sitemap/sitemap_links_list', (array) $this);

                echo $view->run($data);

                break;

        }

    }
}
