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

use Altum\Cache;
use Altum\Meta;
use Altum\Models\Domain;
use Altum\Title;

defined('ALTUMCODE') || die();

class Index extends Controller {

    public function index() {

        /* Custom index redirect if set */
        if(!empty(settings()->main->index_url)) {
            header('Location: ' . settings()->main->index_url); die();
        }

        /* Opengraph image */
        if(settings()->main->opengraph) {
            \Altum\Meta::set_social_image(\Altum\Uploads::get_full_url('opengraph') . settings()->main->opengraph);
        }

        /* Custom code: FC-2026-03-24: strengthen homepage SEO and AI semantics */
        $homepage_semantics = \Altum\Language::$code === 'hr'
            ? [
                'title' => 'Forever Card Club: digitalni sustav za Forever partnere',
                'description' => 'Forever Card Club je neovisni digitalni sustav za Forever partnere koji spaja osobnu aplikaciju, pametne linkove, AI podršku, edukaciju, analitiku i NFC karticu za online i offline poslovanje.',
                'keywords' => 'Forever Card Club, Forever Card, Forever Card aplikacija, digitalni sustav za Forever partnere, pametni linkovi Forever, AI alati za Forever, NFC kartica Forever, online Forever poslovanje',
                'hero_heading' => 'Što je Forever Card Club?',
                'hero_summary' => 'Forever Card Club je neovisni digitalni sustav za Forever partnere. Forever Card označava personaliziranu aplikaciju partnera i povezanu fizičku NFC karticu koja vodi korisnika u isti poslovni sustav.',
                'facts' => [
                    'Osobna Forever Card aplikacija za prezentaciju proizvoda, sadržaja i preporuka.',
                    'Pametni linkovi koji posjetitelja vode na službeni Forever web shop u njegovoj državi.',
                    'AI podrška, edukacija i analitika za jednostavniji razvoj poslovanja.',
                    'NFC kartica povezana s partnerovom aplikacijom za offline i online rad.'
                ],
                'term_name' => 'Forever Card Club',
                'term_alternate_names' => ['FCC', 'Forever Card', 'Forever Card aplikacija'],
                'term_description' => 'Forever Card Club je neovisni digitalni sustav za Forever partnere koji uključuje osobnu aplikaciju, pametne linkove, AI podršku, edukaciju, analitiku i fizičku NFC karticu povezanu s partnerovim sadržajem i preporukama.',
                'hub_pages' => [
                    ['name' => 'Što je Forever Card Club?', 'url' => url('page/forever-card-club')],
                    ['name' => 'Kako funkcionira sustav', 'url' => url('page/how-it-works')],
                    ['name' => 'Česta pitanja', 'url' => url('page/faq')],
                    ['name' => 'O nama', 'url' => url('page/about')],
                ],
            ]
            : [
                'title' => 'Forever Card Club: Digital System for Forever Partners',
                'description' => 'Forever Card Club is an independent digital system for Forever partners that combines a personal app, smart links, AI support, education, analytics, and an NFC card for online and offline business building.',
                'keywords' => 'Forever Card Club, Forever Card, Forever Card app, digital system for Forever partners, smart links Forever, AI tools for Forever, NFC card Forever, online Forever business',
                'hero_heading' => 'What Is Forever Card Club?',
                'hero_summary' => 'Forever Card Club is an independent digital system for Forever partners. Forever Card refers to the partner\'s personalized app and the connected physical NFC card that brings visitors into the same business workflow.',
                'facts' => [
                    'A personal Forever Card app for products, content, and recommendations.',
                    'Smart links that route visitors to the official Forever webshop in their country.',
                    'AI support, education, and analytics for simpler business growth.',
                    'An NFC card connected to the partner\'s app for offline and online work.'
                ],
                'term_name' => 'Forever Card Club',
                'term_alternate_names' => ['FCC', 'Forever Card', 'Forever Card app'],
                'term_description' => 'Forever Card Club is an independent digital system for Forever partners that includes a personal app, smart referral links, AI support, education, analytics, and a physical NFC card connected to the partner\'s content and recommendations.',
                'hub_pages' => [
                    ['name' => 'What Is Forever Card Club?', 'url' => url('page/forever-card-club')],
                    ['name' => 'How the System Works', 'url' => url('page/how-it-works')],
                    ['name' => 'Frequently Asked Questions', 'url' => url('page/faq')],
                    ['name' => 'About Forever Card Club', 'url' => url('page/about')],
                ],
            ];

        Title::set($homepage_semantics['title']);
        Meta::set_description($homepage_semantics['description']);
        Meta::set_keywords($homepage_semantics['keywords']);
        Meta::set_canonical_url(url());
        Meta::set_robots('index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1');
        /* /Custom code: FC-2026-03-24 */

        /* Plans View */
        $view = new \Altum\View('partials/plans', (array) $this);
        $this->add_view_content('plans', $view->run());

        /* Check if the cache exists */
        $cache_instance = cache()->getItem('index_stats');

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            $total_users = database()->query("SELECT MAX(`user_id`) AS `total` FROM `users`")->fetch_object()->total ?? 0;
            $total_links = database()->query("SELECT MAX(`link_id`) AS `total` FROM `links`")->fetch_object()->total ?? 0;
            $total_qr_codes = database()->query("SELECT MAX(`qr_code_id`) AS `total` FROM `qr_codes`")->fetch_object()->total ?? 0;
            $total_track_links = database()->query("SELECT MAX(`id`) AS `total` FROM `track_links`")->fetch_object()->total ?? 0;
            if(\Altum\Plugin::is_active('aix')) {
                if(settings()->aix->documents_is_enabled) {
                    $total_documents = database()->query("SELECT MAX(`document_id`) AS `total` FROM `documents`")->fetch_object()->total ?? 0;
                }

                if(settings()->aix->images_is_enabled && settings()->aix->images_display_latest_on_index) {
                    $total_images = database()->query("SELECT MAX(`image_id`) AS `total` FROM `images`")->fetch_object()->total ?? 0;
                    $images = db()->orderBy('image_id', 'DESC')->get('images', 16);
                }
            }
            $stats = [
                'total_users' => $total_users,
                'total_links' => $total_links,
                'total_qr_codes' => $total_qr_codes,
                'total_track_links' => $total_track_links,
                'total_documents' => $total_documents ?? null,
                'total_images' => $total_images ?? null,
                'images' => $images ?? [],
            ];

            /* Save to cache */
            cache()->save($cache_instance->set($stats)->expiresAfter(3600));

        } else {

            /* Get cache */
            $stats = $cache_instance->get();
            extract($stats);

        }

        if(settings()->main->display_index_latest_blog_posts) {
            $language = \Altum\Language::$name;

            /* Blog posts query */
            $blog_posts_result_query = "
                SELECT * 
                FROM `blog_posts`
                WHERE (`language` = '{$language}' OR `language` IS NULL) AND `is_published` = 1 
                ORDER BY `blog_post_id` DESC
                LIMIT 3
            ";

            $blog_posts = \Altum\Cache::cache_function_result('blog_posts?hash=' . md5($blog_posts_result_query), 'blog_posts', function() use ($blog_posts_result_query) {
                $blog_posts_result = database()->query($blog_posts_result_query);

                /* Iterate over the blog posts */
                $blog_posts = [];

                while($row = $blog_posts_result->fetch_object()) {
                    /* Transform content if needed */
                    $row->content = json_decode($row->content) ? convert_editorjs_json_to_html($row->content) : output_blog_post_content($row->content);

                    $blog_posts[] = $row;
                }

                return $blog_posts;
            });

            /* Custom code: FC-2026-02-25: fallback blog posts to main language */
            if(empty($blog_posts) && \Altum\Language::$main_name != $language) {
                $main_language = \Altum\Language::$main_name;

                $fallback_query = "
                    SELECT *
                    FROM `blog_posts`
                    WHERE (`language` = '{$main_language}' OR `language` IS NULL) AND `is_published` = 1
                    ORDER BY `blog_post_id` DESC
                    LIMIT 3
                ";

                $blog_posts = \Altum\Cache::cache_function_result('blog_posts?hash=' . md5($fallback_query), 'blog_posts', function() use ($fallback_query) {
                    $blog_posts_result = database()->query($fallback_query);

                    $blog_posts = [];

                    while($row = $blog_posts_result->fetch_object()) {
                        $row->content = json_decode($row->content) ? convert_editorjs_json_to_html($row->content) : output_blog_post_content($row->content);

                        $blog_posts[] = $row;
                    }

                    return $blog_posts;
                });
            }
            /* /Custom code: FC-2026-02-25 */
        }

        $tools_categories = require APP_PATH . 'includes/tools/categories.php';
        $enabled_tools = count(array_filter((array) settings()->tools->available_tools));

        /* Get the available domains to use */
        $domains = (new Domain())->get_available_additional_domains();

        /* Main View */
        $view = new \Altum\View('index/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'total_users' => $total_users,
            'total_links' => $total_links,
            'total_qr_codes' => $total_qr_codes,
            'total_track_links' => $total_track_links,
            'total_documents' => $total_documents ?? null,
            'total_images' => $total_images ?? null,
            'images' => $images ?? null,
            'blog_posts' => $blog_posts ?? [],
            'tools_categories' => $tools_categories,
            'enabled_tools' => $enabled_tools,
            'domains' => $domains,
            /* Custom code: FC-2026-03-24: strengthen homepage SEO and AI semantics */
            'homepage_semantics' => $homepage_semantics,
            /* /Custom code: FC-2026-03-24 */
        ]));

    }

}
