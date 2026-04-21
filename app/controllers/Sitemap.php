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

    private function build_localized_public_url(string $path = '', ?string $language = null): string {
        $path = ltrim(trim($path), '/');
        $language = fc_resolve_language_name($language);
        $default_language = settings()->main->default_language;

        if($language && $language !== $default_language && isset(\Altum\Language::$active_languages[$language])) {
            return SITE_URL . \Altum\Language::$active_languages[$language] . '/' . $path;
        }

        return SITE_URL . $path;
    }

    private function build_localized_internal_page_url(string $slug, ?string $language = null): string {
        $slug = ltrim(trim((string) $slug), '/');

        if($slug === '') {
            return SITE_URL;
        }

        $route_segment = fc_internal_page_uses_pages_route($slug) ? 'pages' : 'page';

        return $this->build_localized_public_url($route_segment . '/' . $slug, $language);
    }

    private function add_sitemap_url(array &$sitemap_urls, string $loc, ?string $lastmod = null): void {
        $loc = trim($loc);

        if($loc === '') {
            return;
        }

        if(!isset($sitemap_urls[$loc])) {
            $sitemap_urls[$loc] = [
                'loc' => $loc,
                'lastmod' => $lastmod,
            ];

            return;
        }

        if(!$sitemap_urls[$loc]['lastmod'] && $lastmod) {
            $sitemap_urls[$loc]['lastmod'] = $lastmod;

            return;
        }

        if($lastmod && $sitemap_urls[$loc]['lastmod'] && strtotime((string) $lastmod) > strtotime((string) $sitemap_urls[$loc]['lastmod'])) {
            $sitemap_urls[$loc]['lastmod'] = $lastmod;
        }
    }

    private function add_localized_sitemap_route(array &$sitemap_urls, string $path = '', ?string $lastmod = null): void {
        foreach(\Altum\Language::$active_languages as $language_name => $language_code) {
            $this->add_sitemap_url($sitemap_urls, $this->build_localized_public_url($path, $language_name), $lastmod);
        }
    }

    public function index() {

        /* Set the header as xml so the browser can read it properly */
        header('Content-Type: text/xml');

        $view = new \Altum\View('sitemap/sitemap_index', (array) $this);

        echo $view->run();

    }

    public function main() {
        /* Set the header as xml so the browser can read it properly */
        header('Content-Type: text/xml');

        $sitemap_urls = [];

        /* Keep only public, canonical routes in the main sitemap. */
        $this->add_localized_sitemap_route($sitemap_urls);
        $this->add_sitemap_url($sitemap_urls, SITE_URL . 'llms.txt');

        if(\Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled) {
            $this->add_localized_sitemap_route($sitemap_urls, 'affiliate');
        }

        if(settings()->main->api_is_enabled) {
            $this->add_localized_sitemap_route($sitemap_urls, 'api-documentation');
        }

        if(settings()->email_notifications->contact && !empty(settings()->email_notifications->emails)) {
            $this->add_localized_sitemap_route($sitemap_urls, 'contact');
        }

        if(settings()->payment->is_enabled) {
            $this->add_localized_sitemap_route($sitemap_urls, 'plan');
        }

        if(settings()->content->pages_is_enabled) {
            $this->add_localized_sitemap_route($sitemap_urls, 'pages');
        }

        if(settings()->content->blog_is_enabled) {
            $this->add_localized_sitemap_route($sitemap_urls, 'blog');
        }

        if(settings()->links->directory_is_enabled && settings()->links->directory_access == 'everyone') {
            $this->add_localized_sitemap_route($sitemap_urls, 'directory');
        }

        if(settings()->links->biolinks_is_enabled) {
            $this->add_localized_sitemap_route($sitemap_urls, 'featured-apps');
            $this->add_localized_sitemap_route($sitemap_urls, 'recommended-sponsors');
        }

        if(settings()->tools->is_enabled && settings()->tools->access == 'everyone') {
            foreach ((require APP_PATH . 'includes/tools/tools.php') as $key => $value) {
                if(settings()->tools->available_tools->{$key}) {
                    $this->add_localized_sitemap_route($sitemap_urls, 'tools/' . str_replace('_', '-', $key));
                }
            }
        }

        if(settings()->content->pages_is_enabled) {
            $pages = db()->where('type', 'internal')->where('is_published', 1)->get('pages', null, ['url', 'language', 'last_datetime', 'datetime']);
            $pages_categories = db()->get('pages_categories', null, ['url', 'language']);

            foreach ($pages as $page) {
                $this->add_sitemap_url(
                    $sitemap_urls,
                    $this->build_localized_internal_page_url((string) $page->url, $page->language),
                    $page->last_datetime ?? $page->datetime ?? null
                );
            }

            foreach ($pages_categories as $pages_category) {
                $this->add_sitemap_url(
                    $sitemap_urls,
                    $this->build_localized_public_url('pages/' . (string) $pages_category->url, $pages_category->language)
                );
            }
        }

        if(settings()->content->blog_is_enabled) {
            $blog_posts = db()->where('is_published', 1)->get('blog_posts', null, ['url', 'language', 'last_datetime', 'datetime']);
            $blog_posts_categories = db()->get('blog_posts_categories', null, ['blog_posts_category_id', 'title', 'url', 'language']);

            foreach ($blog_posts as $blog_post) {
                $this->add_sitemap_url(
                    $sitemap_urls,
                    $this->build_localized_public_url('blog/' . (string) $blog_post->url, $blog_post->language),
                    $blog_post->last_datetime ?? $blog_post->datetime ?? null
                );
            }

            foreach ($blog_posts_categories as $blog_posts_category) {
                $category_indexing_bundle = fc_build_blog_category_indexing_bundle($blog_posts_category);

                if(!$category_indexing_bundle['should_index']) {
                    continue;
                }

                $this->add_sitemap_url(
                    $sitemap_urls,
                    $this->build_localized_public_url('blog/category/' . (string) $blog_posts_category->url, $blog_posts_category->language)
                );
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

                    $this->add_sitemap_url(
                        $sitemap_urls,
                        SITE_URL . $prefix . 'recommended-sponsors/' . $sponsor['profile_slug'],
                        $sponsor['link_lastmod'] ?? null
                    );
                }
            }
        }


        /* Main View */
        $data = [
            'sitemap_urls' => array_values($sitemap_urls),
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
