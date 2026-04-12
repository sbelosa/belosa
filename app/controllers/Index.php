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

    private function get_logged_in_referral_biolink_url(): ?string {
        $user_id = \Altum\Authentication::check();

        if(!$user_id) {
            return null;
        }

        $main_biolink_id = fc_get_user_main_biolink_id($user_id);

        if($main_biolink_id) {
            $biolink = db()->where('link_id', $main_biolink_id)->where('type', 'biolink')->getOne('links', ['url']);

            if($biolink && !empty($biolink->url)) {
                return $biolink->url;
            }
        }

        $biolink = db()->where('user_id', $user_id)->where('type', 'biolink')->orderBy('link_id', 'ASC')->getOne('links', ['url']);

        return ($biolink && !empty($biolink->url)) ? $biolink->url : null;
    }

    private function append_referral_to_url(string $url, ?string $referral = null): string {
        $referral = trim((string) $referral);

        if($referral === '') {
            return $url;
        }

        return $url . (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . http_build_query(['ref' => $referral]);
    }

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
                'title' => 'Forever Card Club: cjeloviti digitalni poslovni sustav za Forever Living Products partnere',
                'description' => 'Forever Card Club (FCC) je cjeloviti digitalni poslovni sustav za Forever Living Products partnere koji objedinjuje osobnu aplikaciju, pametne preporučne linkove, AI asistente za proizvode, sustav za prikupljanje kontakata, analitiku i NFC karticu.',
                'keywords' => 'Forever Card Club, FCC, digitalni poslovni sustav, Forever Living Products partneri, osobna aplikacija za Forever, pametni preporučni linkovi Forever, AI asistenti za proizvode Forever, prikupljanje kontakata Forever, NFC kartica Forever',
                'hero_heading' => 'Što je Forever Card Club (FCC)?',
                'hero_summary' => 'Forever Card Club (FCC) je cjeloviti digitalni poslovni sustav za Forever Living Products partnere. Forever Card Aplikacija označava osobnu aplikaciju koju dobiva svaki član, a FCC označava cijeli sustav koji povezuje predstavljanje na internetu, preporučne linkove, prikupljanje kontakata, AI podršku i analitiku.',
                'facts' => [
                    'FCC je neovisni sustav za Forever partnere i nije službena stranica kompanije Forever Living Products.',
                    'Forever Card Aplikacija je osobna aplikacija člana unutar FCC sustava.',
                    'Pametni preporučni linkovi vode posjetitelja prema službenom Forever web shopu u njegovoj državi.',
                    'AI alati, sustav za prikupljanje kontakata i analitika pomažu partneru graditi jasan poslovni proces na internetu i uživo.'
                ],
                'solves_heading' => 'Što FCC konkretno rješava?',
                'solves' => [
                    'Online predstavljanje: jedna osobna aplikacija za sadržaj, proizvode, kontakt i preporuke.',
                    'Globalno usmjeravanje kupca: pametni linkovi vode korisnika prema službenom Forever shopu u pravoj državi.',
                    'Prikupljanje kontakata i nastavak razgovora: alati za kontakt pretvaraju interes u stvarne razgovore.',
                    'Lakše otkrivanje proizvoda: AI asistenti pomažu korisniku pronaći relevantne proizvode i idući korak.',
                    'Povezivanje susreta uživo s digitalnim sustavom: NFC kartica i QR kod vode iz fizičkog susreta u FCC aplikaciju i daljnji poslovni put.'
                ],
                'workflow_heading' => 'Kako FCC radi u praksi?',
                'workflow_intro' => 'FCC povezuje prvi interes, aplikaciju, preporuku, kontakt i službeni Forever shop u jedan pregledan poslovni tok.',
                'workflow_steps' => [
                    ['title' => 'Ulaz u sustav', 'text' => 'Osoba dolazi kroz objavu, poruku, QR kod, NFC karticu ili preporuku.'],
                    ['title' => 'Aplikacija se otvara', 'text' => 'Forever Card Aplikacija postaje centralno mjesto za sadržaj, proizvode i kontakt.'],
                    ['title' => 'Istraživanje i usmjeravanje', 'text' => 'Posjetitelj pregledava sadržaj, koristi AI asistente ili otvara važan sljedeći korak.'],
                    ['title' => 'Kontakt ili kupnja', 'text' => 'Sustav vodi prema ostavljanju kontakta ili prema službenom Forever web shopu.'],
                    ['title' => 'Praćenje i nastavak', 'text' => 'Partner prati reakcije i lakše nastavlja razgovor s više konteksta.'],
                ],
                'use_cases_heading' => 'Što FCC rješava za različite partnere?',
                'use_cases_intro' => 'FCC nije isti samo po alatima, nego po tome što daje jasan okvir za različite faze Forever poslovanja.',
                'use_cases' => [
                    ['title' => 'Za novog partnera', 'text' => 'Pomaže krenuti profesionalno i bez potrebe da sve objašnjava ručno od prvog dana.'],
                    ['title' => 'Za aktivnog partnera', 'text' => 'Spaja preporuke, kontakte, proizvode i praćenje interesa u jedan pregledan sustav.'],
                    ['title' => 'Za team leadera', 'text' => 'Olakšava dupliciranje jer tim dobiva jasan alat i ponovljiv tok rada.'],
                    ['title' => 'Za online dijeljenje', 'text' => 'Jedna aplikacija okuplja sadržaj, preporuke i pametne linkove za društvene mreže i poruke.'],
                    ['title' => 'Za rad uživo', 'text' => 'NFC kartica i QR kod pretvaraju fizički susret u digitalni nastavak razgovora.'],
                    ['title' => 'Za AI otkrivanje proizvoda', 'text' => 'AI asistenti olakšavaju prvi korak prema pravoj preporuci i smislenijem razgovoru.'],
                ],
                'term_name' => 'Forever Card Club',
                'term_alternate_names' => ['FCC', 'Forever Card Club (FCC)'],
                'term_description' => 'Forever Card Club (FCC) je cjeloviti digitalni poslovni sustav za Forever Living Products partnere koji uključuje osobnu aplikaciju, pametne preporučne linkove, AI asistente za proizvode, sustav za prikupljanje kontakata, edukaciju, analitiku i fizičku NFC karticu povezanu s partnerovim sadržajem i preporukama.',
                'hub_pages' => [
                    ['name' => 'Što je Forever Card Club?', 'url' => url('page/forever-card-club')],
                    ['name' => 'Kako funkcionira sustav', 'url' => url('page/how-it-works')],
                    ['name' => 'Istaknute FCC aplikacije', 'url' => url('featured-apps')],
                    ['name' => 'Preporučeni FCC sponzori', 'url' => url('recommended-sponsors')],
                    ['name' => 'Česta pitanja', 'url' => url('page/faq')],
                    ['name' => 'O nama', 'url' => url('page/about')],
                ],
            ]
            : [
                'title' => 'Forever Card Club: all-in-one digital business system for Forever Living Products partners',
                'description' => 'Forever Card Club (FCC) is an all-in-one digital business system for Forever Living Products partners that combines a personal app, smart referral links, AI product assistants, lead funnel, analytics, and an NFC card.',
                'keywords' => 'Forever Card Club, FCC, all-in-one digital business system, Forever Living Products partners, personal app for Forever, smart referral links Forever, AI product assistants Forever, lead funnel Forever, NFC card Forever',
                'hero_heading' => 'What Is Forever Card Club (FCC)?',
                'hero_summary' => 'Forever Card Club (FCC) is an all-in-one digital business system for Forever Living Products partners. The Forever Card App is the personal app each member receives, while FCC refers to the wider system that connects online sharing, referral routing, lead capture, AI guidance, and analytics.',
                'facts' => [
                    'FCC is an independent system for Forever partners and not an official Forever Living Products website.',
                    'The Forever Card App is the member\'s personal app inside the FCC system.',
                    'Smart referral links route visitors toward the official Forever webshop in their country.',
                    'AI tools, lead funnel logic, and analytics help partners build a more structured online and offline business process.'
                ],
                'solves_heading' => 'What does FCC solve in practice?',
                'solves' => [
                    'Online sharing: one personal app for content, products, contact actions, and recommendations.',
                    'Global referral routing: smart links guide visitors toward the official Forever shop in the correct market.',
                    'Lead capture and follow-up: lead funnel logic and contact actions turn attention into real conversations.',
                    'AI-guided product discovery: AI product assistants help visitors understand relevant products and choose the next step.',
                    'Offline-to-online handoff: NFC card and QR flows move real-world meetings into the FCC app and follow-up journey.'
                ],
                'workflow_heading' => 'How does FCC work in practice?',
                'workflow_intro' => 'FCC connects first attention, the partner app, referral logic, contact actions, and the official Forever webshop into one business flow.',
                'workflow_steps' => [
                    ['title' => 'Entry point', 'text' => 'A visitor arrives through a post, message, QR code, NFC card, or recommendation.'],
                    ['title' => 'The app opens', 'text' => 'The Forever Card App becomes the central place for content, products, and contact actions.'],
                    ['title' => 'Discovery and guidance', 'text' => 'The visitor explores content, uses AI assistants, or opens the most relevant next step.'],
                    ['title' => 'Contact or purchase path', 'text' => 'The system routes them toward lead capture or toward the official Forever webshop.'],
                    ['title' => 'Tracking and follow-up', 'text' => 'The partner tracks engagement and continues the conversation with more context.'],
                ],
                'use_cases_heading' => 'What does FCC solve for different partners?',
                'use_cases_intro' => 'FCC stands out not only because of its tools, but because it gives a clear operating model for different Forever business stages.',
                'use_cases' => [
                    ['title' => 'For a new partner', 'text' => 'It helps them start professionally without manually explaining everything from day one.'],
                    ['title' => 'For an active seller', 'text' => 'It connects referrals, leads, products, and follow-up inside one structured system.'],
                    ['title' => 'For a team leader', 'text' => 'It makes duplication easier because the team receives a clear tool and repeatable workflow.'],
                    ['title' => 'For online sharing', 'text' => 'One app combines content, recommendations, and smart links for posts and direct messages.'],
                    ['title' => 'For offline networking', 'text' => 'The NFC card and QR flow turn a live meeting into a digital continuation.'],
                    ['title' => 'For AI-guided product discovery', 'text' => 'AI assistants make the first step toward the right recommendation easier and more useful.'],
                ],
                'term_name' => 'Forever Card Club',
                'term_alternate_names' => ['FCC', 'Forever Card Club (FCC)'],
                'term_description' => 'Forever Card Club (FCC) is an all-in-one digital business system for Forever Living Products partners that includes a personal app, smart referral links, AI product assistants, lead funnel, education, analytics, and a physical NFC card connected to the partner\'s content and recommendations.',
                'hub_pages' => [
                    ['name' => 'What Is Forever Card Club?', 'url' => url('page/forever-card-club')],
                    ['name' => 'How the System Works', 'url' => url('page/how-it-works')],
                    ['name' => 'Featured FCC Apps', 'url' => url('featured-apps')],
                    ['name' => 'Recommended FCC Sponsors', 'url' => url('recommended-sponsors')],
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
        $recommended_sponsors = fcc_featured_get_catalog([
            'language' => \Altum\Language::$code,
            'min_signal_30d' => 50,
            'experience_signal_target' => 50,
            'weekly_check_target' => 15,
            'require_experience_signal' => true,
            'require_valid_sales_link' => true,
            'limit' => 6,
        ]);

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
            'share_url' => $this->append_referral_to_url(url(), $this->get_logged_in_referral_biolink_url()),
            /* Custom code: FC-2026-03-24: strengthen homepage SEO and AI semantics */
            'homepage_semantics' => $homepage_semantics,
            'recommended_sponsors' => $recommended_sponsors,
            /* /Custom code: FC-2026-03-24 */
        ]));

    }

}
