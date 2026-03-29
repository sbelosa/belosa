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
                    $foreverclub_semantics = [
                        'heading' => 'Što je Forever Card Club (FCC)?',
                        'summary' => 'Forever Card Club (FCC) je cjeloviti digitalni poslovni sustav za Forever Living Products partnere. FCC objedinjuje osobnu aplikaciju, pametne preporučne linkove, AI asistente za proizvode, sustav za prikupljanje kontakata, edukaciju, analitiku i fizičku NFC karticu u jedan poslovni proces.',
                        'facts' => [
                            'Forever Card Club nije službena stranica kompanije Forever Living Products, nego neovisni sustav za partnere.',
                            'Forever Card Aplikacija označava osobnu aplikaciju koju dobiva svaki član, dok FCC označava cijeli sustav.',
                            'Kupnja proizvoda odvija se preko službenog Forever web shopa u državi kupca.',
                            'Sustav je namijenjen za predstavljanje na internetu, usmjeravanje kupca, prikupljanje kontakata, AI podršku i razvoj Forever poslovanja.'
                        ],
                        'solves_heading' => 'Što FCC rješava?',
                        'solves' => [
                            'Jedno centralno mjesto za osobnu aplikaciju, sadržaj, proizvode i kontakt.',
                            'Pametno usmjeravanje korisnika prema službenom Forever shopu u njegovoj državi.',
                            'Prikupljanje kontakata i brži nastavak razgovora nakon interesa ili preporuke.',
                            'AI podršku pri otkrivanju proizvoda i izboru sljedećeg koraka.',
                            'Povezivanje kontakta uživo kroz NFC karticu i QR kod s daljnjim digitalnim poslovnim tokom.'
                        ],
                        'term_name' => 'Forever Card Club',
                        'term_alternate_names' => ['FCC', 'Forever Card Club (FCC)'],
                        'term_description' => 'Forever Card Club (FCC) je cjeloviti digitalni poslovni sustav za Forever Living Products partnere koji uključuje osobnu aplikaciju, pametne preporučne linkove, AI asistente za proizvode, sustav za prikupljanje kontakata, edukaciju, analitiku i fizičku NFC karticu povezanu s partnerovim sadržajem i preporukama.'
                    ];
                    $foreverclub_workflow = [
                        'heading' => 'Kako FCC radi u praksi?',
                        'intro' => 'Od prvog interesa do kontakta ili kupnje, FCC povezuje sve ključne korake u jedan poslovni tok.',
                        'steps' => [
                            ['title' => 'Ulaz', 'text' => 'Objava, poruka, QR kod ili NFC kartica dovode osobu u sustav.'],
                            ['title' => 'Aplikacija', 'text' => 'Forever Card Aplikacija otvara sadržaj, proizvode, kontakt i preporuke.'],
                            ['title' => 'Usmjeravanje', 'text' => 'AI, linkovi i blokovi vode korisnika prema pravom sljedećem koraku.'],
                            ['title' => 'Akcija', 'text' => 'Posjetitelj ostavlja kontakt ili odlazi prema službenom Forever shopu.'],
                            ['title' => 'Nastavak', 'text' => 'Partner prati interes i lakše nastavlja razgovor.'],
                        ],
                    ];
                    $foreverclub_use_cases = [
                        'heading' => 'Što FCC rješava?',
                        'intro' => 'Isti sustav podržava više različitih načina gradnje Forever poslovanja.',
                        'items' => [
                            ['title' => 'Novi partner', 'text' => 'Dobiva jasan digitalni početak i profesionalniji prvi dojam.'],
                            ['title' => 'Aktivni partner', 'text' => 'Lakše povezuje preporuke, kontakte i proizvode u jedan tijek rada.'],
                            ['title' => 'Team leader', 'text' => 'Može jednostavnije uvesti tim u isti sustav i lakše duplicirati model rada.'],
                            ['title' => 'Online dijeljenje', 'text' => 'Aplikacija i pametni linkovi olakšavaju dijeljenje kroz objave i poruke.'],
                            ['title' => 'Offline kontakt', 'text' => 'NFC kartica i QR kod daju susretu uživo digitalni nastavak.'],
                            ['title' => 'AI preporuke', 'text' => 'AI asistenti pomažu korisniku brže pronaći relevantan proizvod i sljedeći korak.'],
                        ],
                    ];
                } else {
                    $meta_title = 'Forever Card Club (FCC): guides for an all-in-one digital business system for Forever partners';
                    $meta_description = 'Learn how Forever Card Club (FCC) works as an all-in-one digital business system for Forever Living Products partners through a personal app, smart referral links, AI product assistants, lead funnel, and NFC card.';
                    $meta_keywords = 'Forever Card Club, FCC, all-in-one digital business system, Forever Living Products partners, smart referral links Forever, AI product assistants Forever, lead funnel Forever, NFC card Forever';
                    $foreverclub_semantics = [
                        'heading' => 'What Is Forever Card Club (FCC)?',
                        'summary' => 'Forever Card Club (FCC) is an all-in-one digital business system for Forever Living Products partners. FCC combines a personal app, smart referral links, AI product assistants, lead funnel logic, education, analytics, and a physical NFC card into one business workflow.',
                        'facts' => [
                            'Forever Card Club is not an official Forever Living Products website or store, but an independent system for partners.',
                            'The Forever Card App is the personal app each member receives, while FCC refers to the wider system.',
                            'Product purchases are completed through the official Forever web shop in the customer\'s country.',
                            'The system is designed for online sharing, referral routing, lead capture, AI guidance, and Forever business growth.'
                        ],
                        'solves_heading' => 'What does FCC solve?',
                        'solves' => [
                            'One central place for the partner app, content, products, and contact actions.',
                            'Smarter routing toward the official Forever shop in the visitor\'s country.',
                            'Lead capture and faster follow-up after interest or recommendation.',
                            'AI-guided product discovery and next-step guidance inside the system.',
                            'A clear bridge from offline contact through NFC/QR into the online workflow.'
                        ],
                        'term_name' => 'Forever Card Club',
                        'term_alternate_names' => ['FCC', 'Forever Card Club (FCC)'],
                        'term_description' => 'Forever Card Club (FCC) is an all-in-one digital business system for Forever Living Products partners that includes a personal app, smart referral links, AI product assistants, lead funnel logic, education, analytics, and a physical NFC card connected to the partner\'s content and recommendations.'
                    ];
                    $foreverclub_workflow = [
                        'heading' => 'How does FCC work in practice?',
                        'intro' => 'From first attention to contact or purchase, FCC connects the key steps into one business flow.',
                        'steps' => [
                            ['title' => 'Entry', 'text' => 'A post, message, QR code, or NFC card brings the visitor into the system.'],
                            ['title' => 'App', 'text' => 'The Forever Card App opens content, products, contact actions, and recommendations.'],
                            ['title' => 'Guidance', 'text' => 'AI, links, and blocks route the visitor toward the right next step.'],
                            ['title' => 'Action', 'text' => 'The visitor leaves a lead or moves toward the official Forever shop.'],
                            ['title' => 'Follow-up', 'text' => 'The partner tracks interest and continues the conversation more easily.'],
                        ],
                    ];
                    $foreverclub_use_cases = [
                        'heading' => 'What does FCC solve?',
                        'intro' => 'The same system supports several different ways of building a Forever business.',
                        'items' => [
                            ['title' => 'New partner', 'text' => 'Gets a clearer digital start and a more professional first impression.'],
                            ['title' => 'Active partner', 'text' => 'Can connect referrals, leads, and products inside one workflow.'],
                            ['title' => 'Team leader', 'text' => 'Can onboard the team into the same system and duplicate the model more easily.'],
                            ['title' => 'Online sharing', 'text' => 'The app and smart links make posts and direct sharing much easier.'],
                            ['title' => 'Offline contact', 'text' => 'The NFC card and QR flow give in-person meetings a digital continuation.'],
                            ['title' => 'AI guidance', 'text' => 'AI assistants help visitors find a relevant product and the right next step faster.'],
                        ],
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
