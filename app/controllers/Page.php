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

    private function resolve_hero_image_url_for_link(?int $link_id): ?string {
        if(!$link_id) {
            return null;
        }

        $hero_block = db()->where('link_id', $link_id)
            ->where('is_enabled', 1)
            ->where('type', ['header', 'avatar', 'image'], 'IN')
            ->orderBy('`order`', 'ASC')
            ->getOne('biolinks_blocks', ['type', 'settings']);

        if(!$hero_block) {
            return null;
        }

        $hero_block->settings = json_decode($hero_block->settings ?? '');

        if($hero_block->type === 'header' && !empty($hero_block->settings->avatar)) {
            return \Altum\Uploads::get_full_url('avatars') . $hero_block->settings->avatar;
        }

        if($hero_block->type === 'avatar' && !empty($hero_block->settings->image)) {
            return \Altum\Uploads::get_full_url('avatars') . $hero_block->settings->image;
        }

        if($hero_block->type === 'image' && !empty($hero_block->settings->image)) {
            return \Altum\Uploads::get_full_url('block_images') . $hero_block->settings->image;
        }

        return null;
    }

    private function get_factory_biolink_template_link_id(): ?int {
        $template = null;

        if(settings()->links->default_biolink_template_id) {
            $template = db()->where('is_enabled', 1)->where('biolink_template_id', settings()->links->default_biolink_template_id)->getOne('biolinks_templates', ['link_id']);
        }

        if(!$template) {
            $template = db()->where('is_enabled', 1)->where('link_id', 83)->getOne('biolinks_templates', ['link_id']);
        }

        if(!$template) {
            $template = db()->where('is_enabled', 1)->where('biolink_template_id', 1)->getOne('biolinks_templates', ['link_id']);
        }

        if(!$template) {
            $template = db()->where('is_enabled', 1)->orderBy('biolink_template_id', 'ASC')->getOne('biolinks_templates', ['link_id']);
        }

        return $template ? (int) $template->link_id : null;
    }

    private function get_contact_phone_country_options(): array {
        $country_options = [];

        foreach(get_contact_phone_country_options_array() as $country_code => $country_label) {
            $country_options[$country_code] = [
                'name' => $country_label,
                'dial_code' => get_contact_phone_dial_codes_array()[$country_code] ?? '',
            ];
        }

        return $country_options;
    }

    private function normalize_contact_phone(string $phone, ?string $country_code = null): array {
        $country_code = mb_strtoupper(trim((string) $country_code));
        $country_options = $this->get_contact_phone_country_options();
        $country_code = array_key_exists($country_code, $country_options) ? $country_code : 'HR';
        $dial_code = $country_options[$country_code]['dial_code'] ?? '';

        $raw_digits = preg_replace('/\D+/', '', $phone);
        $trimmed_digits = ltrim($raw_digits, '0');

        if(!$trimmed_digits) {
            return [
                'raw' => trim($phone),
                'country_code' => $country_code,
                'dial_code' => $dial_code,
                'local' => '',
                'e164' => '',
                'is_valid' => false,
            ];
        }

        if($dial_code && str_starts_with($raw_digits, $dial_code)) {
            $trimmed_digits = $raw_digits;
        } elseif($dial_code) {
            $trimmed_digits = $dial_code . $trimmed_digits;
        }

        $e164 = '+' . $trimmed_digits;
        $is_valid = mb_strlen($trimmed_digits) >= 8 && mb_strlen($trimmed_digits) <= 15;

        return [
            'raw' => trim($phone),
            'country_code' => $country_code,
            'dial_code' => $dial_code,
            'local' => $raw_digits,
            'e164' => $e164,
            'is_valid' => $is_valid,
        ];
    }

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
        $foreverclub_pathways = null;
        $foreverclub_workflow = [];
        $foreverclub_use_cases = [];

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

            $foreverclub_pathways_map = [
                'forever-business-tools' => [
                    'core' => ['forever-card-club', 'how-it-works'],
                    'landing' => ['build-forever-business-online', 'fcc-vs-linktree'],
                ],
                'build-forever-business-online' => [
                    'core' => ['forever-card-app', 'forever-card-funnel'],
                    'landing' => ['forever-business-tools', 'lead-funnel-for-forever-partners'],
                ],
                'smart-referral-links-for-forever-partners' => [
                    'core' => ['smart-referral-links', 'forever-card-club'],
                    'landing' => ['fcc-vs-linktree', 'build-forever-business-online'],
                ],
                'ai-product-assistants-for-forever-partners' => [
                    'core' => ['ai-product-assistants', 'forever-card-app'],
                    'landing' => ['lead-funnel-for-forever-partners', 'build-forever-business-online'],
                ],
                'lead-funnel-for-forever-partners' => [
                    'core' => ['forever-card-funnel', 'how-it-works'],
                    'landing' => ['build-forever-business-online', 'ai-product-assistants-for-forever-partners'],
                ],
                'nfc-card-for-forever-follow-up' => [
                    'core' => ['nfc-card-offline', 'forever-card-app'],
                    'landing' => ['lead-funnel-for-forever-partners', 'forever-business-tools'],
                ],
                'fcc-vs-linktree' => [
                    'core' => ['forever-card-app', 'smart-referral-links'],
                    'landing' => ['forever-business-tools', 'fcc-vs-gohighlevel'],
                ],
                'fcc-vs-gohighlevel' => [
                    'core' => ['forever-card-funnel', 'how-it-works'],
                    'landing' => ['forever-business-tools', 'fcc-vs-linktree'],
                ],
            ];

            if(isset($foreverclub_pathways_map[$page->url])) {
                $related_pages_index = [];

                foreach($related_pages as $related_page) {
                    $related_pages_index[$related_page->url] = $related_page;
                }

                $foreverclub_pathways = [
                    'core_pages' => [],
                    'landing_pages' => [],
                ];

                foreach($foreverclub_pathways_map[$page->url]['core'] as $slug) {
                    if(isset($related_pages_index[$slug])) {
                        $foreverclub_pathways['core_pages'][] = $related_pages_index[$slug];
                    }
                }

                foreach($foreverclub_pathways_map[$page->url]['landing'] as $slug) {
                    if(isset($related_pages_index[$slug])) {
                        $foreverclub_pathways['landing_pages'][] = $related_pages_index[$slug];
                    }
                }
            }

            if(\Altum\Language::$code === 'hr') {
                $page_meta_map = [
                    'forever-card-app' => [
                        'title' => 'Forever Card aplikacija: osobna aplikacija unutar FCC sustava za Forever partnere',
                        'description' => 'Saznaj kako Forever Card aplikacija kao dio FCC sustava pomaže u predstavljanju proizvoda, komunikaciji, prikupljanju kontakata i strukturiranom razvoju Forever poslovanja.',
                        'keywords' => 'Forever Card aplikacija, FCC aplikacija, osobna aplikacija za Forever partnere, Forever Card Club aplikacija, digitalni poslovni sustav Forever'
                    ],
                    'smart-referral-links' => [
                        'title' => 'Pametni preporučni linkovi: kako globalno dijeliti Forever proizvode i preporuku',
                        'description' => 'Saznaj kako pametni preporučni linkovi prepoznaju državu posjetitelja, vode na službeni Forever web shop i zadržavaju preporuku partnera.',
                        'keywords' => 'pametni preporučni linkovi, Forever preporučni link, globalni preporučni linkovi, Forever web shop, preporuka partnera, FCC linkovi'
                    ],
                    'forever-card-funnel' => [
                        'title' => 'Forever Card sustav za kontakte: kako pretvoriti interes u konkretan posao',
                        'description' => 'Saznaj kako FCC sustav za kontakte pomaže u prikupljanju kontakata, bržem nastavku razgovora, organizaciji interesa i boljoj pretvorbi interesa u stvaran posao.',
                        'keywords' => 'Forever Card sustav za kontakte, FCC sustav za kontakte, prikupljanje kontakata za Forever partnere, analitika interesa, razvoj Forever poslovanja na internetu'
                    ],
                    'nfc-card-offline' => [
                        'title' => 'NFC kartica: kako pretvoriti susret uživo u aplikaciju, kontakt i posao',
                        'description' => 'Saznaj kako NFC kartica i QR kod vode osobu na Forever Card aplikaciju, olakšavaju preporuku proizvoda, dijeljenje kontakta, prijavu interesa i razvoj poslovanja uživo.',
                        'keywords' => 'NFC kartica Forever, Forever Card NFC kartica, razvoj Forever poslovanja uživo, QR kod Forever, Forever Card aplikacija, digitalna vizitka Forever, NFC za Forever partnere'
                    ],
                    'ai-product-assistants' => [
                        'title' => 'AI asistenti za preporuku proizvoda: kako sustav preporučuje umjesto partnera',
                        'description' => 'Saznaj kako AI asistenti za ljude i životinje preporučuju Forever proizvode, vode korisnika prema pravom linku za kupnju i pomažu partneru prodavati bez klasične prodaje.',
                        'keywords' => 'AI asistenti Forever, AI preporuka proizvoda, Forever AI savjetnik, AI za kućne ljubimce, AI prodajni savjetnik, Forever Card AI, AI za Forever partnere'
                    ],
                    'forever-card-club' => [
                        'title' => 'Što je Forever Card Club (FCC)? Cjeloviti digitalni poslovni sustav za Forever partnere',
                        'description' => 'Saznaj kako Forever Card Club (FCC) kao cjeloviti digitalni poslovni sustav povezuje osobnu aplikaciju, pametne preporučne linkove, AI asistente za proizvode, sustav za kontakte i NFC karticu za Forever partnere.',
                        'keywords' => 'Forever Card Club, FCC, cjeloviti digitalni poslovni sustav, Forever Living Products partneri, pametni preporučni linkovi, AI asistenti za proizvode, sustav za kontakte, NFC kartica'
                    ],
                    'how-it-works' => [
                        'title' => 'Kako funkcionira Forever Card Club (FCC): aplikacija, preporučni linkovi, AI i kontakti',
                        'description' => 'Pogledaj kako FCC povezuje osobnu aplikaciju partnera, pametne preporučne linkove, AI podršku, sustav za kontakte i NFC karticu sa službenim Forever web shopom.',
                        'keywords' => 'kako funkcionira Forever Card Club, FCC sustav, pametni preporučni linkovi Forever, AI podrška Forever, sustav za kontakte Forever, NFC kartica Forever'
                    ],
                    'faq' => [
                        'title' => 'Forever Card Club (FCC) FAQ: česta pitanja o sustavu, aplikaciji i preporukama',
                        'description' => 'Odgovori na najčešća pitanja o FCC sustavu, osobnoj Forever Card aplikaciji, pametnim preporučnim linkovima, AI alatima, pristupu i službenom Forever webshop procesu.',
                        'keywords' => 'Forever Card Club FAQ, FCC FAQ, pametni preporučni linkovi Forever, Forever Card aplikacija, AI alati Forever, pristup FCC sustavu'
                    ],
                    'about' => [
                        'title' => 'O Forever Card Clubu (FCC): cjeloviti digitalni poslovni sustav za Forever partnere',
                        'description' => 'Upoznaj FCC kao neovisni cjeloviti digitalni poslovni sustav za Forever Living Products partnere koji objedinjuje aplikaciju, edukaciju, AI alate, pametne preporučne linkove i analitiku.',
                        'keywords' => 'o Forever Card Clubu, FCC, digitalni poslovni sustav za Forever partnere, Forever aplikacija, edukacija za Forever, AI alati za Forever'
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
                        'summary' => 'Forever Card Aplikacija je osobna aplikacija koju dobiva svaki član unutar Forever Card Club (FCC) sustava. Ona je središnje mjesto kroz koje FCC povezuje sadržaj, preporuke, kontaktne akcije, prikupljanje kontakata i daljnji poslovni nastavak razgovora.',
                        'facts' => [
                            'Na jednom mjestu spaja predstavljanje partnera, proizvoda, kontakta i poziva na akciju.',
                            'Pomaže da zainteresirana osoba lakše ostavi kontakt i brže dobije prave informacije.',
                            'Olakšava dijeljenje aplikacije preko društvenih mreža, poruka, QR koda i NFC kartice.',
                            'Partner jasnije vidi što radi najbolje i lakše gradi svoj sustav preporuka i praćenja.'
                        ],
                        'term_name' => 'Forever Card Aplikacija',
                        'term_alternate_names' => ['FCC aplikacija', 'Forever Card Aplikacija', 'Forever Card App'],
                        'term_description' => 'Forever Card Aplikacija je personalizirana digitalna aplikacija partnera unutar Forever Card Club sustava, namijenjena predstavljanju proizvoda, prikupljanju kontakata, preporukama i razvoju Forever Living poslovanja.'
                    ],
                    'smart-referral-links' => [
                        'heading' => 'Zašto su ovi linkovi važni?',
                        'summary' => 'Pametni preporučni linkovi su ključni dio FCC sustava jer olakšavaju globalno dijeljenje proizvoda i preporuke. Posjetitelja vode prema ispravnom službenom Forever web shopu, a partneru pomažu da preporuka ostane povezana s njim na jednostavniji i pregledniji način.',
                        'facts' => [
                            'Jedan link može služiti ljudima iz više država bez ručnog traženja pravog shopa.',
                            'Sustav prepoznaje državu posjetitelja i usmjerava ga na odgovarajuću službenu stranicu.',
                            'Partnerova preporuka ostaje ugrađena u iskustvo dijeljenja i kupnje.',
                            'Dijeljenje je jednostavnije, profesionalnije i pogodnije za rad na internetu i uživo.'
                        ],
                        'term_name' => 'Pametni preporučni linkovi',
                        'term_alternate_names' => ['Globalni pametni linkovi', 'FCC preporučni linkovi', 'Forever preporučni linkovi'],
                        'term_description' => 'Pametni preporučni linkovi su globalni linkovi unutar Forever Card Club sustava koji prepoznaju državu posjetitelja, vode ga prema službenom Forever web shopu i zadržavaju partnerovu preporuku.'
                    ],
                    'forever-card-funnel' => [
                        'heading' => 'Zašto je sustav za kontakte važan?',
                        'summary' => 'Sustav za kontakte unutar FCC-a pomaže da interes ne ostane samo na pregledu stranice ili poruke. Vodi posjetitelja prema ostavljanju kontakta, partneru daje jasan sljedeći korak i olakšava da se više razgovora pretvori u konkretan posao.',
                        'facts' => [
                            'Pomaže u prikupljanju kvalitetnijih kontakata kroz jednostavan i jasan kontakt proces.',
                            'Olakšava brži nastavak razgovora preko WhatsAppa, Vibera, SMS-a, poziva ili emaila.',
                            'Daje pregled koji put i koja poruka donose najviše rezultata.',
                            'Smanjuje gubitak interesa između prvog klika i stvarnog kontakta.'
                        ],
                        'term_name' => 'FCC sustav za kontakte',
                        'term_alternate_names' => ['Sustav za kontakte', 'Pametni kontakti', 'Prikupljanje kontakata'],
                        'term_description' => 'FCC sustav za kontakte je dio Forever Card Club sustava namijenjen prikupljanju kontakata, praćenju interesa, bržem nastavku razgovora i pretvaranju interesa u konkretne poslovne razgovore.'
                    ],
                    'nfc-card-offline' => [
                        'heading' => 'Zašto je NFC kartica važna?',
                        'summary' => 'NFC kartica povezuje razgovor uživo i FCC digitalni poslovni sustav. Jednim dodirom ili skeniranjem QR koda osoba otvara Forever Card Aplikaciju s videom, proizvodima, kontaktima, AI alatima, obrascem za interes i svim sljedećim koracima.',
                        'facts' => [
                            'Jedan susret uživo može odmah voditi na personaliziranu aplikaciju partnera.',
                            'Osoba bez ručnog tipkanja dobiva video, proizvode, popust, kontakt i idući korak.',
                            'Ista aplikacija može se koristiti i na NFC kartici i na društvenim mrežama kao biolink.',
                            'Partner dobiva profesionalniji nastup uživo i lakše pretvara razgovor u kontakt ili preporuku.'
                        ],
                        'term_name' => 'NFC kartica',
                        'term_alternate_names' => ['Forever Card NFC kartica', 'FCC NFC kartica', 'Forever Card za susrete uživo'],
                        'term_description' => 'NFC kartica je fizička kartica partnera unutar Forever Card Club sustava koja jednim dodirom ili skeniranjem QR koda otvara Forever Card Aplikaciju s proizvodima, kontaktima, obrascima za interes, AI alatima i drugim poslovnim sadržajem.'
                    ],
                    'ai-product-assistants' => [
                        'heading' => 'Zašto su AI asistenti važni?',
                        'summary' => 'AI asistenti za proizvode pomažu partneru da preporuka proizvoda ne ovisi samo o njegovu iskustvu, znanju ili trenutnoj dostupnosti. Oni vode korisnika prema najboljim kombinacijama proizvoda, savjetima i pravom linku za naručivanje unutar FCC poslovnog toka.',
                        'facts' => [
                            'Postoje specijalizirani AI savjetnici za preporuku proizvoda kod ljudi i zasebno za životinje.',
                            'AI asistenti rade unutar Forever Card Aplikacije, na NFC kartici i kroz dijeljenje aplikacije na društvenim mrežama.',
                            'Preporuke su usklađene s poslovnom logikom, EU okvirom i usmjeravanjem prema službenom linku za kupnju u zemlji posjetitelja.',
                            'Novi partner može slati svoju aplikaciju bez potrebe da zna svaki proizvod ili vodi klasičnu prodaju ručno.'
                        ],
                        'term_name' => 'AI asistenti za preporuku proizvoda',
                        'term_alternate_names' => ['Forever AI savjetnici', 'AI prodajni savjetnik', 'AI za kućne ljubimce'],
                        'term_description' => 'AI asistenti za preporuku proizvoda su specijalizirani savjetnici unutar Forever Card Club sustava koji preporučuju kombinacije Forever proizvoda za ljude i životinje te korisnika vode prema odgovarajućem linku partnera za kupnju.'
                    ],
                ];

                $foreverclub_semantics = [
                    'heading' => 'Kratko objašnjenje',
                    'summary' => 'Forever Card Club (FCC) je cjeloviti digitalni poslovni sustav za Forever Living Products partnere. Forever Card Aplikacija označava osobnu aplikaciju koju dobiva svaki član, dok FCC označava cijeli sustav koji povezuje predstavljanje na internetu, preporučne linkove, prikupljanje kontakata, AI podršku i analitiku.',
                    'facts' => [
                        'Namijenjen je neovisnim Forever partnerima koji žele graditi poslovanje na internetu i uživo.',
                        'Uključuje osobnu aplikaciju, pametne preporučne linkove, AI asistente za proizvode, sustav za prikupljanje kontakata, edukaciju i analitiku.',
                        'Kupnja proizvoda odvija se preko službenog Forever web shopa u državi kupca.',
                        'Forever Card Club nije službena stranica kompanije Forever Living Products.'
                    ],
                    'solves_heading' => 'Što FCC rješava?',
                    'solves' => [
                        'Predstavljanje na internetu kroz jednu osobnu aplikaciju.',
                        'Globalno usmjeravanje prema službenom Forever shopu.',
                        'Prikupljanje kontakata i jasan nastavak razgovora nakon interesa.',
                        'AI podršku pri istraživanju proizvoda i odabiru sljedećeg koraka.',
                        'Povezivanje susreta uživo s online sustavom preko NFC kartice i QR koda.'
                    ],
                    'term_name' => 'Forever Card Club',
                    'term_alternate_names' => ['FCC', 'Forever Card Club (FCC)'],
                    'term_description' => 'Forever Card Club (FCC) je cjeloviti digitalni poslovni sustav za Forever Living Products partnere koji spaja osobnu aplikaciju, pametne preporučne linkove, AI asistente za proizvode, sustav za prikupljanje kontakata, edukaciju, analitiku i fizičku NFC karticu u jedan poslovni proces.'
                ];
                $foreverclub_workflow = [
                    'heading' => 'Kako FCC radi u praksi?',
                    'intro' => 'FCC pretvara interes u jasan slijed koraka, od prve pažnje do kontakta ili kupnje.',
                    'steps' => [
                        ['title' => 'Ulaz', 'text' => 'Osoba dolazi kroz objavu, poruku, QR kod, NFC karticu ili preporuku.'],
                        ['title' => 'Aplikacija', 'text' => 'Forever Card Aplikacija otvara sadržaj, proizvode, kontakt i preporuke.'],
                        ['title' => 'Usmjeravanje', 'text' => 'AI asistenti, linkovi i blokovi vode prema pravom sljedećem koraku.'],
                        ['title' => 'Akcija', 'text' => 'Korisnik ostavlja kontakt ili otvara službeni Forever web shop.'],
                        ['title' => 'Nastavak', 'text' => 'Partner dobiva bolji kontekst za daljnji razgovor i praćenje interesa.'],
                    ],
                ];
                $foreverclub_use_cases = [
                    'heading' => 'Za koga je FCC najkorisniji?',
                    'intro' => 'Isti sustav pomaže u različitim ulogama i poslovnim situacijama.',
                    'items' => [
                        ['title' => 'Novi partner', 'text' => 'Za profesionalniji početak bez ručnog objašnjavanja svega od nule.'],
                        ['title' => 'Aktivni partner', 'text' => 'Za povezivanje preporuka, kontakata i proizvoda u jedan sustav.'],
                        ['title' => 'Team leader', 'text' => 'Za lakše dupliciranje procesa unutar tima.'],
                        ['title' => 'Online dijeljenje', 'text' => 'Za objave, poruke i društvene mreže koje vode u jasnu aplikaciju.'],
                        ['title' => 'Offline networking', 'text' => 'Za pretvaranje susreta uživo u digitalni nastavak kroz NFC i QR.'],
                        ['title' => 'AI preporuke', 'text' => 'Za lakše otkrivanje proizvoda i smisleniji prvi razgovor.'],
                    ],
                ];
            } else {
                $page_meta_map = [
                    'forever-card-app' => [
                        'title' => 'Forever Card app: the personal app inside the FCC system for Forever partners',
                        'description' => 'Learn how the Forever Card app, as part of the FCC system, helps with product presentation, communication, contact capture, and structured Forever business growth.',
                        'keywords' => 'Forever Card app, FCC app, personal app for Forever partners, Forever Card Club app, digital business system Forever'
                    ],
                    'smart-referral-links' => [
                        'title' => 'Smart referral links: how to share Forever products globally with the right referral',
                        'description' => 'Learn how smart referral links detect the visitor country, route them to the official Forever webshop, and keep the partner referral connected.',
                        'keywords' => 'smart referral links, Forever referral links, global referral links, Forever webshop routing, partner referral links, FCC smart links'
                    ],
                    'forever-card-funnel' => [
                        'title' => 'Forever Card Funnel: how to turn interest into real business',
                        'description' => 'Learn how the Forever Card Funnel helps collect leads, improve follow-up, organize contacts, and convert more interest into real business outcomes.',
                        'keywords' => 'Forever Card Funnel, FCC funnel, lead funnel for Forever partners, lead collection, funnel analytics, contact conversion, Forever business funnel'
                    ],
                    'nfc-card-offline' => [
                        'title' => 'NFC Card (Offline): how to turn an in-person meeting into an app, a contact, and real business',
                        'description' => 'Learn how the NFC card and QR code open the Forever Card App, simplify product recommendations, contact sharing, funnel signups, and offline business growth.',
                        'keywords' => 'NFC card Forever, Forever Card NFC card, offline Forever business, QR code Forever, Forever Card App, digital business card Forever, NFC for Forever partners'
                    ],
                    'ai-product-assistants' => [
                        'title' => 'AI Product Assistants: how the system recommends instead of the partner doing classic sales',
                        'description' => 'Learn how AI assistants for people and pets recommend Forever products, guide visitors to the right shop link, and help partners sell through the system instead of manual product explaining.',
                        'keywords' => 'AI assistants Forever, AI product recommendations, Forever AI advisor, AI for pets, AI sales assistant, Forever Card AI, AI for Forever partners'
                    ],
                    'forever-card-club' => [
                        'title' => 'What Is Forever Card Club (FCC)? An all-in-one digital business system for Forever partners',
                        'description' => 'Learn how Forever Card Club (FCC), as an all-in-one digital business system, connects a personal app, smart referral links, AI product assistants, lead funnel, and NFC card for Forever partners.',
                        'keywords' => 'Forever Card Club, FCC, all-in-one digital business system, Forever Living Products partners, smart referral links, AI product assistants, lead funnel, NFC card'
                    ],
                    'how-it-works' => [
                        'title' => 'How Forever Card Club (FCC) works: app, referral routing, AI, and lead funnel',
                        'description' => 'See how FCC connects the partner app, smart referral links, AI guidance, lead funnel logic, and NFC card with official Forever webshop routing.',
                        'keywords' => 'how Forever Card Club works, FCC system, smart referral links Forever, AI guidance Forever, lead funnel Forever, NFC card Forever'
                    ],
                    'faq' => [
                        'title' => 'Forever Card Club (FCC) FAQ: common questions about the system, app, and referral logic',
                        'description' => 'Find clear answers about the FCC system, the personal Forever Card app, smart referral links, AI tools, access requirements, and the official Forever webshop flow.',
                        'keywords' => 'Forever Card Club FAQ, FCC FAQ, smart referral links Forever, Forever Card app, AI tools for Forever, access to FCC system'
                    ],
                    'about' => [
                        'title' => 'About Forever Card Club (FCC): an all-in-one digital business system for Forever partners',
                        'description' => 'Discover FCC as an independent all-in-one digital business system for Forever Living Products partners built around apps, education, AI tools, smart referral links, and analytics.',
                        'keywords' => 'about Forever Card Club, FCC, digital business system for Forever partners, Forever app, education for Forever partners, AI tools for Forever'
                    ],
                    'independent-disclaimer' => [
                        'title' => 'Independent Disclaimer: Forever Card Club and Official Forever Shop Routing',
                        'description' => 'Read the independent disclaimer for Forever Card Club, how the system relates to Forever Living Products, and how product ordering is routed through the official Forever webshop.',
                        'keywords' => 'Forever Card Club disclaimer, independent Forever Card Club, official Forever webshop, Forever Living Products disclaimer, Forever Card Club legal notice'
                    ],
                ];

                $foreverclub_semantics_map = [
                    'forever-card-app' => [
                        'heading' => 'Why does this app matter?',
                        'summary' => 'The Forever Card App is the personal app each member receives inside the Forever Card Club (FCC) system. It is the operating layer through which FCC connects content, recommendations, contact actions, lead capture, and business follow-up.',
                        'facts' => [
                            'It brings product presentation, contact actions, and guidance into one simple experience.',
                            'It helps interested visitors get information faster and choose an easy next step.',
                            'It can be shared through social media, direct messages, QR codes, and NFC cards.',
                            'It gives partners a more professional image and a stronger business follow-up process.'
                        ],
                        'term_name' => 'Forever Card App',
                        'term_alternate_names' => ['FCC app', 'Forever Card App', 'Forever Card application'],
                        'term_description' => 'The Forever Card App is a personalized partner application inside the Forever Card Club system used for product presentation, contact collection, recommendations, and structured Forever Living business growth.'
                    ],
                    'smart-referral-links' => [
                        'heading' => 'Why do these links matter?',
                        'summary' => 'Smart referral links are a core part of the FCC system because they make global product sharing easier. They help visitors reach the correct official Forever webshop while keeping the partner recommendation connected in a simpler and more scalable way.',
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
                    'forever-card-funnel' => [
                        'heading' => 'Why does the funnel matter?',
                        'summary' => 'The lead funnel inside the FCC system helps make sure interest does not stop at a page view or a message. It guides visitors toward leaving a contact, gives the partner a clear next step, and helps turn more attention into real business conversations.',
                        'facts' => [
                            'It helps collect better leads through a simple and clear contact path.',
                            'It supports faster follow-up through WhatsApp, Viber, SMS, calls, or email.',
                            'It shows which funnel and message produce the strongest results.',
                            'It reduces drop-off between the first click and the real contact stage.'
                        ],
                        'term_name' => 'Forever Card Funnel',
                        'term_alternate_names' => ['FCC Funnel', 'Smart contact funnel', 'Lead funnel'],
                        'term_description' => 'The Forever Card Funnel is part of the Forever Card Club system designed for lead capture, contact tracking, faster follow-up, and turning interest into real business conversations.'
                    ],
                    'nfc-card-offline' => [
                        'heading' => 'Why does the NFC card matter?',
                        'summary' => 'The NFC card connects the offline conversation with the FCC digital business system. One tap or QR scan opens the Forever Card App with product info, contact actions, AI support, funnels, and the next business steps.',
                        'facts' => [
                            'A live conversation can instantly lead into the partner’s personalized app.',
                            'Visitors get video, products, discounts, contact details, and the next action without manual searching.',
                            'The same app can be used on the NFC card and on social media as a biolink.',
                            'It gives partners a more professional offline presence and improves conversion from real-world contact to follow-up.'
                        ],
                        'term_name' => 'NFC card',
                        'term_alternate_names' => ['Forever Card NFC card', 'FCC NFC card', 'Offline Forever Card'],
                        'term_description' => 'The NFC card is a physical partner card inside the Forever Card Club system that opens the Forever Card App by tap or QR scan, giving visitors access to products, contacts, funnels, AI tools, and other business content.'
                    ],
                    'ai-product-assistants' => [
                        'heading' => 'Why do AI assistants matter?',
                        'summary' => 'AI product assistants make product recommendations less dependent on the partner’s own experience, memory, or sales confidence. They guide visitors toward suitable product combinations, useful advice, and the correct next ordering step inside the FCC business flow.',
                        'facts' => [
                            'There are specialized AI advisors for people and separate AI advisors for pets.',
                            'They work inside the Forever Card App, on the NFC card flow, and through shared app links on social media.',
                            'Recommendations are shaped to stay aligned with EU-facing guidance, business logic, and routing toward the correct official shop link.',
                            'New partners can share their app without manually explaining every product or handling classic sales conversations alone.'
                        ],
                        'term_name' => 'AI product assistants',
                        'term_alternate_names' => ['Forever AI advisors', 'AI sales assistant', 'AI for pets'],
                        'term_description' => 'AI product assistants are specialized advisors inside the Forever Card Club system that recommend Forever product combinations for people and pets and guide visitors toward the correct partner-linked webshop path.'
                    ],
                ];

                $foreverclub_semantics = [
                    'heading' => 'Quick Explanation',
                    'summary' => 'Forever Card Club (FCC) is an all-in-one digital business system for Forever Living Products partners. The Forever Card App is the personal app each member receives, while FCC refers to the wider system that connects online sharing, referral routing, lead capture, AI guidance, and analytics.',
                    'facts' => [
                        'It is designed for independent Forever partners who want to build online and offline business.',
                        'It includes a personal app, smart referral links, AI product assistants, lead funnel logic, education, and analytics.',
                        'Product purchases happen through the official Forever webshop in the customer\'s country.',
                        'Forever Card Club is not an official Forever Living Products website.'
                    ],
                    'solves_heading' => 'What does FCC solve?',
                    'solves' => [
                        'Online sharing through one personal app.',
                        'Global referral routing toward the official Forever shop.',
                        'Lead capture and structured follow-up after interest.',
                        'AI guidance during product discovery and next-step decisions.',
                        'Offline-to-online transition through NFC card and QR flow.'
                    ],
                    'term_name' => 'Forever Card Club',
                    'term_alternate_names' => ['FCC', 'Forever Card Club (FCC)'],
                    'term_description' => 'Forever Card Club (FCC) is an all-in-one digital business system for Forever Living Products partners that combines a personal app, smart referral links, AI product assistants, lead funnel logic, education, analytics, and a physical NFC card in one business workflow.'
                ];
                $foreverclub_workflow = [
                    'heading' => 'How does FCC work in practice?',
                    'intro' => 'FCC turns interest into a clear sequence of steps, from first attention to contact or purchase.',
                    'steps' => [
                        ['title' => 'Entry', 'text' => 'A visitor arrives through a post, message, QR code, NFC card, or recommendation.'],
                        ['title' => 'App', 'text' => 'The Forever Card App opens content, products, contact actions, and recommendations.'],
                        ['title' => 'Guidance', 'text' => 'AI assistants, links, and blocks lead toward the right next step.'],
                        ['title' => 'Action', 'text' => 'The visitor leaves a lead or opens the official Forever webshop.'],
                        ['title' => 'Follow-up', 'text' => 'The partner gets better context for the next conversation and follow-up.'],
                    ],
                ];
                $foreverclub_use_cases = [
                    'heading' => 'Who benefits most from FCC?',
                    'intro' => 'The same system supports different roles and business situations.',
                    'items' => [
                        ['title' => 'New partner', 'text' => 'For a more professional start without explaining everything manually from zero.'],
                        ['title' => 'Active partner', 'text' => 'For connecting referrals, leads, and products inside one system.'],
                        ['title' => 'Team leader', 'text' => 'For easier duplication of the process across a team.'],
                        ['title' => 'Online sharing', 'text' => 'For posts, messages, and social media that lead into one clear app.'],
                        ['title' => 'Offline networking', 'text' => 'For turning live meetings into digital continuation through NFC and QR.'],
                        ['title' => 'AI guidance', 'text' => 'For easier product discovery and a more useful first conversation.'],
                    ],
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
                'hero_image_url' => null,
                'user_id' => null,
                'main_link_id' => null,
                'contact_biolink_block_id' => null,
                'contact_biolink_block_type' => 'contact_collector',
                'project_id' => null,
            ];

            $collaborator_user = null;
            $main_biolink = null;

            if($collaborator_aff_url) {
                $aff_path = trim((string) parse_url($collaborator_aff_url, PHP_URL_PATH), '/');

                if($aff_path) {
                    $main_biolink = db()->where('url', $aff_path)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'project_id', 'url']);
                }
            }

            if(!$main_biolink && $collaborator_contact->email) {
                $collaborator_user = db()->where('email', $collaborator_contact->email)->getOne('users', ['user_id', 'name', 'email']);

                if($collaborator_user) {
                    $main_biolink_map = db()->where('user_id', $collaborator_user->user_id)->getOne('users_biolinks', ['biolink_id']);

                    if($main_biolink_map && !empty($main_biolink_map->biolink_id)) {
                        $main_biolink = db()->where('link_id', $main_biolink_map->biolink_id)->where('type', 'biolink')->getOne('links', ['link_id', 'user_id', 'project_id', 'url']);
                    }

                    if(!$main_biolink) {
                        $main_biolink = db()->where('user_id', $collaborator_user->user_id)->where('type', 'biolink')->orderBy('link_id', 'ASC')->getOne('links', ['link_id', 'user_id', 'project_id', 'url']);
                    }
                }
            }

            if($main_biolink && empty($collaborator_contact->aff_link)) {
                $collaborator_contact->aff_link = SITE_URL . ltrim($main_biolink->url, '/');
            }

            if($main_biolink) {
                $collaborator_contact->user_id = (int) $main_biolink->user_id;
                $collaborator_contact->main_link_id = (int) $main_biolink->link_id;
                $collaborator_contact->project_id = (int) $main_biolink->project_id;
                $collaborator_contact->hero_image_url = $this->resolve_hero_image_url_for_link($main_biolink->link_id);

                $contact_block = db()->where('link_id', $main_biolink->link_id)
                    ->where('is_enabled', 1)
                    ->where('type', ['contact_collector', 'phone_collector', 'email_collector', 'lead_funnel'], 'IN')
                    ->orderBy('`order`', 'ASC')
                    ->getOne('biolinks_blocks', ['biolink_block_id', 'type']);

                if($contact_block) {
                    $collaborator_contact->contact_biolink_block_id = (int) $contact_block->biolink_block_id;
                    $collaborator_contact->contact_biolink_block_type = $contact_block->type;
                }
            }

            if(empty($collaborator_contact->hero_image_url)) {
                $collaborator_contact->hero_image_url = $this->resolve_hero_image_url_for_link($this->get_factory_biolink_template_link_id());
            }

            if(!empty($_POST['fcc_contact_action']) && $_POST['fcc_contact_action'] === 'store_contact') {
                if(empty($collaborator_contact->user_id) || empty($collaborator_contact->main_link_id)) {
                    \Altum\Alerts::add_error(l('global.error_message.basic'));
                } else {
                    $_POST['name'] = input_clean($_POST['name'] ?? '', 64);
                    $_POST['email'] = input_clean_email($_POST['email'] ?? '');
                    $_POST['phone'] = input_clean($_POST['phone'] ?? '', 32);
                    $_POST['phone_country_code'] = input_clean($_POST['phone_country_code'] ?? 'HR', 8);
                    $_POST['preferred_contact_channel'] = input_clean($_POST['preferred_contact_channel'] ?? 'whatsapp', 32);
                    $_POST['message'] = input_clean($_POST['message'] ?? '', 512);

                    if(!$_POST['name'] || !$_POST['email'] || !$_POST['phone']) {
                        \Altum\Alerts::add_error(l('global.error_message.empty_fields'));
                    } else {
                        $normalized_phone = $this->normalize_contact_phone($_POST['phone'], $_POST['phone_country_code']);

                        if(!$normalized_phone['is_valid']) {
                            \Altum\Alerts::add_error(l('global.error_message.empty_fields'));
                        } else {
                            $contact_type = $collaborator_contact->contact_biolink_block_type ?: 'contact_collector';

                            if(!in_array($contact_type, ['contact_collector', 'phone_collector', 'email_collector', 'lead_funnel'], true)) {
                                $contact_type = 'contact_collector';
                            }

                            $contact_data = [
                                'name' => $_POST['name'],
                                'email' => $_POST['email'],
                                'phone' => $_POST['phone'],
                                'phone_country_code' => $_POST['phone_country_code'],
                                'preferred_contact_channel' => $_POST['preferred_contact_channel'],
                                'phone_e164' => $normalized_phone['e164'],
                                'phone_dial_code' => $normalized_phone['dial_code'],
                                'message' => $_POST['message'],
                                'source_label' => Language::$code === 'hr' ? 'Kontakt stranica suradnika' : 'Collaborator contact page',
                                'source_context' => Language::$code === 'hr' ? 'FCC kontakt handoff' : 'FCC contact handoff',
                                'source_page_slug' => $page->url,
                                'source_page_url' => $page_url,
                                'contact_intent' => 'direct_contact',
                            ];

                            if($contact_type === 'email_collector') {
                                unset($contact_data['message'], $contact_data['preferred_contact_channel'], $contact_data['source_context'], $contact_data['contact_intent']);
                            }

                            if($contact_type === 'phone_collector') {
                                unset($contact_data['message'], $contact_data['source_context'], $contact_data['contact_intent']);
                            }

                            $datum_id = db()->insert('data', [
                                'biolink_block_id' => $collaborator_contact->contact_biolink_block_id ?: null,
                                'link_id' => $collaborator_contact->main_link_id,
                                'project_id' => $collaborator_contact->project_id,
                                'user_id' => $collaborator_contact->user_id,
                                'type' => $contact_type,
                                'data' => json_encode($contact_data),
                                'datetime' => get_date(),
                            ]);

                            if($datum_id) {
                                \Altum\Alerts::add_success(Language::$code === 'hr' ? 'Kontakt je spremljen i proslijeđen suradniku.' : 'Your contact has been saved and sent to the collaborator.');

                                $page_route = ($page->language ? ((\Altum\Language::$active_languages[$page->language] ?? null) ? \Altum\Language::$active_languages[$page->language] . '/' : null) : null) . 'page/' . $page->url;
                                redirect($page_route . '?contact_saved=1#fcc-contact-direct');
                            } else {
                                \Altum\Alerts::add_error(Language::$code === 'hr' ? 'Kontakt trenutno nije moguće spremiti. Pokušaj ponovno za nekoliko trenutaka.' : 'The contact could not be saved right now. Please try again in a moment.');
                            }
                        }
                    }
                }
            }
        }
        /* /Custom code: FC-2026-02-26 */

        /* Prepare the view */
        $data = [
            'page'  => $page,
            'pages_category' => $pages_category,
            'collaborator_contact' => $collaborator_contact,
            'contact_country_options' => get_contact_phone_country_options_array(),
            /* Custom code: FC-2026-03-24: strengthen foreverclub page hub SEO and internal linking */
            'is_foreverclub_page' => $is_foreverclub_page,
            'foreverclub_semantics' => $foreverclub_semantics,
            'related_pages' => $related_pages,
            'foreverclub_pathways' => $foreverclub_pathways,
            'foreverclub_workflow' => $foreverclub_workflow,
            'foreverclub_use_cases' => $foreverclub_use_cases,
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
        $meta_description = $page_meta_override['description'] ?? $page->description ?? (!empty($foreverclub_semantics['summary']) ? $foreverclub_semantics['summary'] : null);

        Meta::set_description(string_truncate($meta_description, 160));
        Meta::set_keywords(string_truncate($page_meta_override['keywords'] ?? $page->keywords, 255));
        Meta::set_canonical_url($page_url);
        if($is_foreverclub_page) {
            Meta::set_robots('index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1');
        }
        /* /Custom code: FC-2026-03-24 */

        /* Custom code: FC-2026-02-25: page social image */
        if(!empty($page->image)) {
            Meta::set_social_image(\Altum\Uploads::get_full_url('pages') . $page->image);
        } elseif($is_foreverclub_page) {
            foreach($related_pages as $related_page) {
                if(!empty($related_page->image_url)) {
                    Meta::set_social_image($related_page->image_url);
                    break;
                }
            }
        }
        /* /Custom code: FC-2026-02-25 */

        /* Disable automated link language alternate */
        Meta::set_link_alternate(false);
    }

}
