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

class Page extends Controller {

    public function index() {

        if(!settings()->content->pages_is_enabled) {
            throw_404();
        }

        $url = isset($this->params[0]) ? query_clean($this->params[0]) : null;
        $language = Language::$name;

        /* If the custom page url is set then try to get data from the database */
        $page_query = "
                SELECT *
            FROM `pages`
            WHERE
                `url` = '{$url}'
                AND `is_published` = 1
            ORDER BY
                CASE
                    WHEN `language` = '{$language}' THEN 0
                    WHEN `language` IS NULL THEN 1
                    ELSE 2
                END,
                `page_id` ASC
            LIMIT 1
            ";
        $page = $url ? \Altum\Cache::cache_function_result('page?hash=' . md5($page_query), 'pages', function() use ($page_query) {
            return database()->query($page_query)->fetch_object() ?? null;
        }) : null;

        /* Redirect if the page does not exist */
        if(!$page) {
            throw_404();
        }

        $page->plans_ids = json_decode($page->plans_ids ?? '');

        /* Custom code: FC-2026-02-25: page thumbnail url */
        $page->image_url = $page->image ? \Altum\Uploads::get_full_url('pages') . $page->image : null;
        /* /Custom code: FC-2026-02-25 */

        if(!empty($page->plans_ids)) {
            if(!is_logged_in()) {
                throw_404();
            };

            if(!in_array(user()->plan_id, $page->plans_ids)) {
                throw_404();
            }
        }

        /* Get the page category */
        $pages_category = $page->pages_category_id ? \Altum\Cache::cache_function_result('pages_category?hash=' . md5($page->pages_category_id), 'pages_categories', function() use ($page) {
            return db()->where('pages_category_id', $page->pages_category_id)->getOne('pages_categories');
        }) : null;

        /* Custom code: FC-2026-03-24: strengthen foreverclub page hub SEO and internal linking */
        $page_url = SITE_URL . ($page->language ? ((\Altum\Language::$active_languages[$page->language] ?? null) ? \Altum\Language::$active_languages[$page->language] . '/' : null) : null) . 'page/' . $page->url;
        $is_foreverclub_page = $pages_category && $pages_category->url === 'foreverclub';
        $related_pages = [];
        $foreverclub_semantics = null;
        $page_meta_override = null;

        if($is_foreverclub_page) {
            $related_pages_query = "
                SELECT `url`, `title`, `description`, `type`, `language`, `image`, `image_description`
                FROM `pages`
                WHERE `pages_category_id` = {$page->pages_category_id} AND `page_id` != {$page->page_id} AND (`language` = '{$language}' OR `language` IS NULL) AND `is_published` = 1
                ORDER BY `order` ASC, `total_views` DESC
            ";

            $related_pages = \Altum\Cache::cache_function_result('pages_related?hash=' . md5($related_pages_query), 'pages', function() use ($related_pages_query) {
                $related_pages_result = database()->query($related_pages_query);

                if($related_pages_result === false) {
                    $fallback_query = preg_replace('/, `image`, `image_description`/', '', $related_pages_query);
                    $related_pages_result = database()->query($fallback_query);
                }

                if($related_pages_result === false) {
                    return [];
                }

                $related_pages = [];

                while($row = $related_pages_result->fetch_object()) {
                    $row->image_url = !empty($row->image) ? \Altum\Uploads::get_full_url('pages') . $row->image : null;
                    $related_pages[] = $row;
                }

                return $related_pages;
            });

            if(\Altum\Language::$code === 'hr') {
                $page_meta_map = [
                    'forever-card-app' => [
                        'title' => 'Forever Card Aplikacija: kako pomaže u prodaji, preporukama i izgradnji tima',
                        'description' => 'Saznaj što je Forever Card Aplikacija, kako pomaže u prikupljanju kontakata, preporuci proizvoda, komunikaciji i razvoju Forever Living poslovanja.',
                        'keywords' => 'Forever Card Aplikacija, FCC aplikacija, Forever Card Club aplikacija, digitalna aplikacija za Forever partnere, Forever poslovanje online, alati za Forever Living'
                    ],
                    'smart-referral-links' => [
                        'title' => 'Pametni referral linkovi: kako globalno dijeliti Forever proizvode i preporuku',
                        'description' => 'Saznaj kako pametni referral linkovi prepoznaju državu posjetitelja, vode na službeni Forever web shop i zadržavaju preporuku partnera.',
                        'keywords' => 'pametni referral linkovi, Forever referral link, globalni referral linkovi, Forever web shop, partner referral, FCC smart links'
                    ],
                    'forever-card-club' => [
                        'title' => 'Što je Forever Card Club? Aplikacija, AI alati i NFC kartica',
                        'description' => 'Saznaj što je Forever Card Club i kako osobna aplikacija, pametni linkovi, AI alati i NFC kartica pomažu Forever partnerima u online i offline poslovanju.',
                        'keywords' => 'Forever Card Club, što je Forever Card Club, Forever Card aplikacija, Forever Card, AI alati za Forever, pametni linkovi Forever, NFC kartica Forever, online Forever poslovanje'
                    ],
                    'how-it-works' => [
                        'title' => 'Kako funkcionira Forever Card Club: aplikacija, linkovi i AI',
                        'description' => 'Pogledaj kako funkcionira Forever Card Club: osobna aplikacija partnera, pametni linkovi, AI savjetnik, NFC kartica i povezivanje sa službenim Forever web shopom.',
                        'keywords' => 'kako funkcionira Forever Card Club, Forever Card sustav, pametni linkovi Forever, AI savjetnik Forever, NFC kartica Forever, Forever web shop, referal sustav Forever'
                    ],
                    'faq' => [
                        'title' => 'Forever Card Club FAQ: česta pitanja i odgovori',
                        'description' => 'Odgovori na najčešća pitanja o Forever Card Clubu, osobnoj aplikaciji, pametnim linkovima, AI alatima, pristupu sustavu i službenom Forever web shopu.',
                        'keywords' => 'Forever Card Club FAQ, pitanja i odgovori Forever Card Club, osobna Forever Card aplikacija, AI alati Forever, pametni linkovi Forever, pristup Forever Card Clubu'
                    ],
                    'about' => [
                        'title' => 'O Forever Card Clubu: digitalni sustav za Forever partnere',
                        'description' => 'Upoznaj Forever Card Club kao neovisni digitalni sustav za Forever partnere koji objedinjuje aplikaciju, edukaciju, AI alate, pametne linkove i analitiku.',
                        'keywords' => 'o Forever Card Clubu, Forever digitalni sustav, Forever partneri, Forever aplikacija, edukacija za Forever, AI alati za Forever, pametni linkovi Forever'
                    ],
                    'independent-disclaimer' => [
                        'title' => 'Pravna napomena i neovisnost Forever Card Cluba',
                        'description' => 'Pročitaj pravnu napomenu o Forever Card Clubu, njegovoj neovisnosti od Forever Living Productsa i načinu naručivanja preko službenog Forever web shopa.',
                        'keywords' => 'Forever Card Club pravna napomena, Forever Card Club neovisnost, službeni Forever web shop, Forever Living Products napomena'
                    ],
                ];

                $foreverclub_semantics_map = [
                    'forever-card-app' => [
                        'heading' => 'Zašto je ova aplikacija važna?',
                        'summary' => 'Forever Card Aplikacija je partnerova osobna digitalna baza za predstavljanje proizvoda, preporuka, edukacije i kontakta. Pomaže da svaka preporuka izgleda ozbiljnije, brže vodi do razgovora i lakše se pretvara u konkretan posao.',
                        'facts' => [
                            'Na jednom mjestu spaja predstavljanje partnera, proizvoda, kontakta i poziva na akciju.',
                            'Pomaže da zainteresirana osoba lakše ostavi kontakt i brže dobije prave informacije.',
                            'Olakšava dijeljenje aplikacije preko društvenih mreža, poruka, QR koda i NFC kartice.',
                            'Partner jasnije vidi što radi najbolje i lakše gradi svoj sustav preporuka i praćenja.'
                        ],
                        'term_name' => 'Forever Card Aplikacija',
                        'term_alternate_names' => ['FCC aplikacija', 'Forever Card app', 'Forever Card Club aplikacija'],
                        'term_description' => 'Forever Card Aplikacija je personalizirana digitalna aplikacija partnera unutar Forever Card Club sustava, namijenjena predstavljanju proizvoda, prikupljanju kontakata, preporukama i razvoju Forever Living poslovanja.'
                    ],
                    'smart-referral-links' => [
                        'heading' => 'Zašto su ovi linkovi važni?',
                        'summary' => 'Pametni referral linkovi olakšavaju globalno dijeljenje proizvoda i preporuke. Posjetitelja vode prema ispravnom službenom Forever web shopu, a partneru pomažu da preporuka ostane povezana s njim na jednostavniji i pregledniji način.',
                        'facts' => [
                            'Jedan link može služiti ljudima iz više država bez ručnog traženja pravog shopa.',
                            'Sustav prepoznaje državu posjetitelja i usmjerava ga na odgovarajuću službenu stranicu.',
                            'Partnerova preporuka ostaje ugrađena u iskustvo dijeljenja i kupnje.',
                            'Dijeljenje je jednostavnije, profesionalnije i pogodnije za online i offline rad.'
                        ],
                        'term_name' => 'Pametni referral linkovi',
                        'term_alternate_names' => ['Global smart links', 'FCC referral linkovi', 'Forever referral linkovi'],
                        'term_description' => 'Pametni referral linkovi su globalni linkovi unutar Forever Card Club sustava koji prepoznaju državu posjetitelja, vode ga prema službenom Forever web shopu i zadržavaju partnerovu preporuku.'
                    ],
                ];

                $foreverclub_semantics = [
                    'heading' => 'Kratko objašnjenje',
                    'summary' => 'Forever Card Club je neovisni digitalni sustav za Forever partnere. Forever Card označava personaliziranu aplikaciju partnera i povezanu fizičku NFC karticu koja korisnika vodi na isti poslovni sustav.',
                    'facts' => [
                        'Namijenjen je neovisnim Forever partnerima koji žele graditi poslovanje online i offline.',
                        'Uključuje osobnu aplikaciju, pametne linkove, AI podršku, edukaciju i analitiku.',
                        'Kupnja proizvoda odvija se preko službenog Forever web shopa u državi kupca.',
                        'Forever Card Club nije službena stranica kompanije Forever Living Products.'
                    ],
                    'term_name' => 'Forever Card Club',
                    'term_alternate_names' => ['FCC', 'Forever Card', 'Forever Card aplikacija'],
                    'term_description' => 'Forever Card Club je neovisni digitalni sustav za Forever partnere koji spaja osobnu aplikaciju, pametne linkove, AI podršku, edukaciju, analitiku i fizičku NFC karticu u jedan poslovni proces.'
                ];
            } else {
                $page_meta_map = [
                    'forever-card-app' => [
                        'title' => 'Forever Card App: how it helps with leads, product sharing and team growth',
                        'description' => 'Learn what the Forever Card App is and how it helps with contacts, product recommendations, follow-up, and building a stronger Forever Living business.',
                        'keywords' => 'Forever Card App, FCC app, Forever Card Club app, app for Forever partners, Forever business tools, Forever Living digital app'
                    ],
                    'smart-referral-links' => [
                        'title' => 'Smart referral links: how to share Forever products globally with the right referral',
                        'description' => 'Learn how smart referral links detect the visitor country, route them to the official Forever webshop, and keep the partner referral connected.',
                        'keywords' => 'smart referral links, Forever referral links, global referral links, Forever webshop routing, partner referral links, FCC smart links'
                    ],
                    'forever-card-club' => [
                        'title' => 'What Is Forever Card Club? App, AI Tools and NFC Card',
                        'description' => 'Learn what Forever Card Club is and how the personal app, smart links, AI tools, and NFC card help Forever partners grow online and offline.',
                        'keywords' => 'what is Forever Card Club, Forever Card Club, Forever Card app, Forever Card, AI tools for Forever, smart links Forever, NFC card Forever, Forever business tools'
                    ],
                    'how-it-works' => [
                        'title' => 'How Forever Card Club Works: App, Smart Links and AI',
                        'description' => 'See how Forever Card Club works through the partner app, smart links, AI assistance, NFC card, and official Forever webshop routing.',
                        'keywords' => 'how Forever Card Club works, Forever Card system, smart links Forever, AI assistant Forever, NFC card Forever, Forever webshop routing, referral system Forever'
                    ],
                    'faq' => [
                        'title' => 'Forever Card Club FAQ: Common Questions Answered',
                        'description' => 'Find clear answers about Forever Card Club, the personal app, smart links, AI tools, access requirements, and the official Forever webshop flow.',
                        'keywords' => 'Forever Card Club FAQ, Forever Card Club questions, Forever Card app questions, AI tools for Forever, smart links Forever, access to Forever Card Club'
                    ],
                    'about' => [
                        'title' => 'About Forever Card Club: Digital System for Forever Partners',
                        'description' => 'Discover Forever Card Club as an independent digital system for Forever partners built around apps, education, AI tools, smart links, and analytics.',
                        'keywords' => 'about Forever Card Club, Forever digital system, Forever partner platform, Forever app, education for Forever partners, AI tools for Forever, smart links Forever'
                    ],
                ];

                $foreverclub_semantics_map = [
                    'forever-card-app' => [
                        'heading' => 'Why does this app matter?',
                        'summary' => 'The Forever Card App gives each partner one clear digital place to present products, answer common questions, collect contacts, and move conversations toward real business outcomes.',
                        'facts' => [
                            'It brings product presentation, contact actions, and guidance into one simple experience.',
                            'It helps interested visitors get information faster and choose an easy next step.',
                            'It can be shared through social media, direct messages, QR codes, and NFC cards.',
                            'It gives partners a more professional image and a stronger business follow-up process.'
                        ],
                        'term_name' => 'Forever Card App',
                        'term_alternate_names' => ['FCC app', 'Forever Card application', 'Forever Card Club app'],
                        'term_description' => 'The Forever Card App is a personalized partner application inside the Forever Card Club system used for product presentation, contact collection, recommendations, and structured Forever Living business growth.'
                    ],
                    'smart-referral-links' => [
                        'heading' => 'Why do these links matter?',
                        'summary' => 'Smart referral links make global product sharing easier. They help visitors reach the correct official Forever webshop while keeping the partner recommendation connected in a simpler and more scalable way.',
                        'facts' => [
                            'One link can serve visitors from different countries without manual searching.',
                            'The system detects the visitor location and routes them to the correct official shop.',
                            'The partner recommendation stays connected to the visitor path and order flow.',
                            'Sharing becomes simpler, more professional, and easier to repeat at scale.'
                        ],
                        'term_name' => 'Smart referral links',
                        'term_alternate_names' => ['Global smart links', 'FCC referral links', 'Forever referral links'],
                        'term_description' => 'Smart referral links are global links inside the Forever Card Club system that detect the visitor country, route them toward the official Forever webshop, and preserve the partner referral.'
                    ],
                ];

                $foreverclub_semantics = [
                    'heading' => 'Quick Explanation',
                    'summary' => 'Forever Card Club is an independent digital system for Forever partners. Forever Card refers to the partner\'s personalized app and the connected physical NFC card that bring visitors into the same business system.',
                    'facts' => [
                        'It is designed for independent Forever partners who want to build online and offline business.',
                        'It includes a personal app, smart links, AI support, education, and analytics.',
                        'Product purchases happen through the official Forever webshop in the customer\'s country.',
                        'Forever Card Club is not an official Forever Living Products website.'
                    ],
                    'term_name' => 'Forever Card Club',
                    'term_alternate_names' => ['FCC', 'Forever Card', 'Forever Card app'],
                    'term_description' => 'Forever Card Club is an independent digital system for Forever partners that combines a personal app, smart referral links, AI support, education, analytics, and a physical NFC card in one business workflow.'
                ];
            }

            $page_meta_override = $page_meta_map[$page->url] ?? null;
            $foreverclub_semantics = $foreverclub_semantics_map[$page->url] ?? $foreverclub_semantics;
        }
        /* /Custom code: FC-2026-03-24 */

        /* Add a new view to the page */
        $cookie_name = 'page_view_' . $page->page_id;
        if(!isset($_COOKIE[$cookie_name])) {
            db()->where('page_id', $page->page_id)->update('pages', ['total_views' => db()->inc()]);
            setcookie($cookie_name, (int) true, time()+60*60*24*1);
        }

        /* Transform content if needed */
        $page->content = json_decode($page->content) ? convert_editorjs_json_to_html($page->content) : output_blog_post_content($page->content);

        /* Custom code: FC-2026-02-26: resolve shortcodes and collaborator contact data for contact page */
        $shortcodes = new \Altum\Shortcodes();
        $page->content = $shortcodes->display_shortcodes($page->content, null);

        $collaborator_contact = null;
        if(mb_strtolower((string) $page->url) === 'contact') {
            $collaborator_aff_biolink = $shortcodes->generate_shorcode('aff_biolink', null);
            $collaborator_aff_url = null;

            if(is_string($collaborator_aff_biolink) && preg_match('/href="([^"]+)"/i', $collaborator_aff_biolink, $matches)) {
                $collaborator_aff_url = $matches[1];
            }

            $collaborator_contact = (object) [
                'name' => $shortcodes->generate_shorcode('name', null) ?? '',
                'email' => $shortcodes->generate_shorcode('email', null) ?? '',
                'phone' => $shortcodes->generate_shorcode('phone', null) ?? '',
                'forever_id' => $shortcodes->generate_shorcode('forever_id', null) ?? '',
                'aff_link' => $collaborator_aff_url,
            ];
        }
        /* /Custom code: FC-2026-02-26 */

        /* Prepare the view */
        $data = [
            'page'  => $page,
            'pages_category' => $pages_category,
            'collaborator_contact' => $collaborator_contact,
            /* Custom code: FC-2026-03-24: strengthen foreverclub page hub SEO and internal linking */
            'is_foreverclub_page' => $is_foreverclub_page,
            'foreverclub_semantics' => $foreverclub_semantics,
            'related_pages' => $related_pages,
            'page_url' => $page_url,
            /* /Custom code: FC-2026-03-24 */
        ];

        $view = new \Altum\View('page/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

        /* Set a custom title */
        /* Custom code: FC-2026-03-24: strengthen foreverclub page hub SEO and internal linking */
        Title::set($page_meta_override['title'] ?? $page->title);
        /* /Custom code: FC-2026-03-24 */

        /* Meta */

        /* Custom code: FC-2026-03-24: strengthen foreverclub page hub SEO and internal linking */
        Meta::set_description(string_truncate($page_meta_override['description'] ?? $page->description, 160));
        Meta::set_keywords(string_truncate($page_meta_override['keywords'] ?? $page->keywords, 255));
        Meta::set_canonical_url($page_url);
        if($is_foreverclub_page) {
            Meta::set_robots('index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1');
        }
        /* /Custom code: FC-2026-03-24 */

        /* Custom code: FC-2026-02-25: page social image */
        if(!empty($page->image)) {
            Meta::set_social_image(\Altum\Uploads::get_full_url('pages') . $page->image);
        }
        /* /Custom code: FC-2026-02-25 */

        /* Disable automated link language alternate */
        Meta::set_link_alternate(false);
    }

}
