#!/usr/bin/env php
<?php

const DEBUG = 0;
const MYSQL_DEBUG = 0;
const LOGGING = 1;
const CACHE = 1;
const ALTUMCODE = 66;

$root_path = realpath(__DIR__ . '/..');

if(!$root_path) {
    fwrite(STDERR, "Unable to resolve project root.\n");
    exit(1);
}

require_once $root_path . '/app/init.php';

\Altum\Cache::initialize();
\Altum\Plugin::initialize();
\Altum\Language::initialize();
\Altum\Language::set_default_by_name(settings()->main->default_language);

$default_timezone = trim((string) (settings()->main->default_timezone ?? ''));
if($default_timezone === '' || !in_array($default_timezone, \DateTimeZone::listIdentifiers(), true)) {
    $default_timezone = 'Europe/Zagreb';
}

\Altum\Date::$default_timezone = $default_timezone;
date_default_timezone_set($default_timezone);
\Altum\Date::$timezone = date_default_timezone_get();
\Altum\Date::$date = \Altum\Date::get();

function stjepan_fcc_guide_action(string $id, string $label, array $options = []): array {
    return array_merge([
        'id' => $id,
        'label' => $label,
        'hint' => '',
        'value' => $id,
        'style' => 'primary',
        'action' => 'goto_step',
        'target_step_id' => '',
        'external_url' => '',
        'require_submit' => false,
        'event_key' => '',
        'sticky' => false,
    ], $options);
}

function stjepan_fcc_guide_block(string $id, string $type, array $payload = []): array {
    return array_merge([
        'id' => $id,
        'type' => $type,
        'label' => $payload['label'] ?? $id,
        'layout_width' => 'full',
        'alignment' => 'left',
    ], $payload);
}

function stjepan_fcc_guide_variant(array $blocks, array $updates_by_id = []): array {
    foreach($blocks as &$block) {
        if(!is_array($block)) {
            continue;
        }

        $block_id = (string) ($block['id'] ?? '');
        if($block_id !== '' && isset($updates_by_id[$block_id]) && is_array($updates_by_id[$block_id])) {
            $block = array_replace_recursive($block, $updates_by_id[$block_id]);
        }
    }
    unset($block);

    return $blocks;
}

function stjepan_fcc_guide_surface(string $name, array $blocks, array $settings = []): array {
    return array_merge([
        'name' => $name,
        'background_color' => '#080E14',
        'background_image_url' => '',
        'background_opacity' => 100,
        'surface_color' => '#121C28',
        'text_color' => '#F5FAFF',
        'accent_color' => '#67D8C9',
        'max_width' => 'wide',
        'show_progress' => true,
        'progress_label' => '',
        'progress_current' => 0,
        'progress_total' => 0,
        'ab_enabled' => false,
        'ab_distribution' => 50,
        'blocks' => $blocks,
        'variant_b_blocks' => [],
        'variant_b_settings' => [],
    ], $settings);
}

function stjepan_fcc_guide_step(string $id, string $phase_key, string $path_key, string $card_type, string $title, string $summary, array $blocks, string $next_step_id = '', array $settings = []): array {
    return array_merge([
        'id' => $id,
        'path_key' => $path_key,
        'row_key' => $path_key,
        'card_type' => $card_type,
        'title' => $title,
        'summary' => $summary,
        'helper_text' => $summary,
        'cta' => 'Nastavi',
        'next' => '',
        'next_step_id' => $next_step_id,
        'status_key' => $card_type === 'cta' ? 'conversion' : ($card_type === 'proof' ? 'proof' : 'core'),
        'media_url' => '',
        'answers' => [],
        'tags' => [$phase_key, $path_key, $card_type],
        'owner_user_id' => (int) ($settings['owner_user_id'] ?? 0),
        'visibility_key' => 'all',
        'analytics_label' => $id,
        'design_variant' => $settings['design_variant'] ?? 'card',
        'preview_badge' => $settings['preview_badge'] ?? ucfirst($phase_key),
        'preview_headline' => $title,
        'preview_body' => $summary,
        'block_mode' => $settings['block_mode'] ?? 'message',
        'background_color' => $settings['background_color'] ?? '#121C28',
        'text_color' => '#F5FAFF',
        'accent_color' => $settings['accent_color'] ?? '#67D8C9',
        'button_options' => [],
        'page' => stjepan_fcc_guide_surface($title, $blocks, $settings['surface'] ?? []),
    ], $settings['step'] ?? []);
}

function stjepan_fcc_guide_whatsapp_url(string $base_url = '', string $message = ''): string {
    $base_url = trim($base_url);
    $message = trim($message);

    if($base_url !== '' && function_exists('vip_funnel_rewrite_whatsapp_url_message')) {
        $rewritten = vip_funnel_rewrite_whatsapp_url_message($base_url, $message);
        if($rewritten !== '') {
            return $rewritten;
        }
    }

    return $base_url;
}

function stjepan_fcc_guide_build_payload($user = null, array $options = []): array {
    $options = vip_funnel_to_array($options);
    $owner_user_id = (int) ($user->user_id ?? 0);
    $owner_name = trim((string) ($user->name ?? 'Stjepan Beloša')) ?: 'Stjepan Beloša';
    $contact_email = filter_var((string) ($options['contact_email'] ?? ($user->email ?? 'info@forevercard.club')), FILTER_VALIDATE_EMAIL)
        ? (string) ($options['contact_email'] ?? ($user->email ?? 'info@forevercard.club'))
        : 'info@forevercard.club';
    $owner_profile = function_exists('vip_funnel_get_owner_contact_profile') ? vip_funnel_get_owner_contact_profile($user) : [];
    $owner_whatsapp_url = trim((string) ($options['whatsapp_url'] ?? ($owner_profile['whatsapp_url'] ?? '')));
    $fallback_mailto = 'mailto:' . rawurlencode($contact_email) . '?subject=' . rawurlencode('FCC vodič - sljedeći korak');
    $general_whatsapp_url = stjepan_fcc_guide_whatsapp_url($owner_whatsapp_url, 'Bok Stjepane, pogledao/la sam tvoj FCC vodič i zanima me najbolji sljedeći korak za mene.') ?: $fallback_mailto;
    $business_owner_whatsapp_url = stjepan_fcc_guide_whatsapp_url($owner_whatsapp_url, 'Bok Stjepane, imam svoj posao/klijente i zanima me kako bih FCC i Forever mogao/la uklopiti u ono što već radim.') ?: $fallback_mailto;
    $start_whatsapp_url = stjepan_fcc_guide_whatsapp_url($owner_whatsapp_url, 'Bok Stjepane, želim krenuti sa Start paketom i uključiti se u tvoj FCC sustav.') ?: $fallback_mailto;
    $after_order_whatsapp_url = stjepan_fcc_guide_whatsapp_url($owner_whatsapp_url, 'Bok Stjepane, naručio/la sam Start paket i želim da mi pomogneš složiti moj prvi FCC korak.') ?: $fallback_mailto;
    $demo_whatsapp_url = stjepan_fcc_guide_whatsapp_url($owner_whatsapp_url, 'Bok Stjepane, želim FCC demo i volio/la bih da mi pokažeš kako bi to izgledalo za mene.') ?: $fallback_mailto;
    $product_whatsapp_url = stjepan_fcc_guide_whatsapp_url($owner_whatsapp_url, 'Bok Stjepane, zasad me zanimaju proizvodi i AI preporuka, ali volio/la bih čuti i kako sustav funkcionira.') ?: $fallback_mailto;
    $question_whatsapp_url = function_exists('vip_funnel_get_start_package_question_whatsapp_url')
        ? (vip_funnel_get_start_package_question_whatsapp_url($user, 'hr') ?: $general_whatsapp_url)
        : $general_whatsapp_url;
    $product_shop_url = trim((string) ($options['product_shop_url'] ?? ($owner_profile['main_biolink_url'] ?? ''))) ?: SITE_URL . 'blog';
    $privacy_url = trim((string) ($options['privacy_url'] ?? '')) ?: SITE_URL . 'page/privacy-policy';
    $facebook_pixel_id = vip_funnel_normalize_meta_pixel_id((string) ($options['facebook_pixel_id'] ?? '238225369103006'));

    $video = static function(string $key) use ($options): string {
        return trim((string) ($options['video_' . $key] ?? ''));
    };

    $product_keys = [
        'energy' => vip_funnel_find_catalog_translation_key(['aloe vera gel', 'forever aloe vera gel', 'argiplus', 'vitamin c'], 'hr'),
        'weight' => vip_funnel_find_catalog_translation_key(['c9', 'dx4', 'f15', 'weight', 'regulacija tezine'], 'hr'),
        'skin' => vip_funnel_find_catalog_translation_key(['marine collagen', 'aloescrub', 'skin', 'koza'], 'hr'),
        'routine' => vip_funnel_find_catalog_translation_key(['aloe msm gel', 'msm', 'forever freedom', 'aloe vera gel'], 'hr'),
        'start' => vip_funnel_find_catalog_translation_key(['start paket', 'c9', 'forever'], 'hr'),
    ];
    $primary_product_key = $product_keys['energy'] ?: ($product_keys['skin'] ?: ($product_keys['start'] ?: ''));

    $consent_text = 'Pristajem da me Stjepan Beloša kontaktira putem WhatsAppa ili telefona vezano uz moj odabrani smjer.';
    $privacy_contact_text = 'Podatke koristimo samo za odgovor na tvoj upit i komunikaciju vezanu uz proizvode, FCC sustav ili suradnju sa Stjepanom. Privacy: ' . $privacy_url;
    $business_compliance = 'Ovo nije zaposlenje ni garantirani prihod. Rezultati ovise o tvojoj aktivnosti, vremenu, dosljednosti i tržištu.';
    $product_compliance = 'AI vodič služi za informiranje o proizvodima i općoj wellness rutini. Proizvodi nisu lijek i nisu zamjena za savjet liječnika.';

    $landing_blocks = [
        stjepan_fcc_guide_block('intro_hero', 'headline', [
            'badge' => 'Osobni FCC vodič',
            'title' => 'Pokreni svoj FCC put uz moje osobno mentorstvo',
            'text' => 'Pogledaj kratku poruku i odaberi smjer koji te trenutno najbolje opisuje. Ne moraš sve razumjeti odmah - vodič će te odvesti na najbolji sljedeći korak.',
            'alignment' => 'center',
            'title_size' => 50,
            'text_size' => 20,
        ]),
        stjepan_fcc_guide_block('intro_video', 'video', [
            'title' => 'Prvo pogledaj ovu kratku poruku',
            'text' => 'Uvodni video pokazuje kako odabrati smjer bez preopterećenja.',
            'media_url' => $video('intro'),
            'layout_width' => 'two_thirds',
            'alignment' => 'center',
        ]),
        stjepan_fcc_guide_block('intro_why', 'proof_card', [
            'badge' => 'Zašto ovaj vodič',
            'title' => 'Ne želim da lutaš. Želim da odmah vidiš pravi sljedeći korak.',
            'text' => 'Neki ljudi žele pokrenuti posao, neki prvo žele razumjeti sustav, neki dolaze zbog proizvoda, a neki su već spremni za Start paket.',
            'layout_width' => 'third',
        ]),
        stjepan_fcc_guide_block('intro_choice', 'survey', [
            'title' => 'Gdje se trenutno nalaziš?',
            'text' => 'Odaberi opciju koja te najbolje opisuje. Na temelju toga pokazat ću ti najkraći i najlogičniji put.',
            'options' => [
                stjepan_fcc_guide_action('select_existing_business', 'Imam svoj posao ili klijente i želim pametniji sustav preporuka', ['target_step_id' => 'business_owner', 'value' => 'existing_business', 'style' => 'primary', 'event_key' => 'select_existing_business']),
                stjepan_fcc_guide_action('select_business_path', 'Želim pokrenuti dodatni prihod uz proizvode i mentorstvo', ['target_step_id' => 'business_intro', 'value' => 'business_path', 'style' => 'secondary', 'event_key' => 'select_business_path']),
                stjepan_fcc_guide_action('select_fcc_system', 'Želim prvo razumjeti FCC sustav', ['target_step_id' => 'fcc_system', 'value' => 'fcc_system', 'style' => 'secondary', 'event_key' => 'select_fcc_system']),
                stjepan_fcc_guide_action('select_products', 'Zanimaju me proizvodi i 15% popusta', ['target_step_id' => 'products_entry', 'value' => 'products', 'style' => 'secondary', 'event_key' => 'select_products']),
                stjepan_fcc_guide_action('select_start_package', 'Spreman/na sam za Start paket', ['target_step_id' => 'start_package', 'value' => 'ready_start', 'style' => 'primary', 'event_key' => 'select_start_package']),
            ],
            'auto_advance' => true,
            'alignment' => 'center',
        ]),
    ];

    $landing_page = stjepan_fcc_guide_surface('Stjepan Beloša | Forever Card Club vodič', $landing_blocks, [
        'show_progress' => false,
        'ab_enabled' => true,
        'variant_b_blocks' => stjepan_fcc_guide_variant($landing_blocks, [
            'intro_hero' => [
                'badge' => 'Počni ovdje',
                'title' => 'Pronađi najbolji sljedeći korak za proizvode, sustav ili suradnju',
                'text' => 'Ako te zanimaju Forever proizvodi, dodatni prihod, moj tim ili FCC sustav, kreni od kratkog videa i izaberi ono što ti sada ima najviše smisla.',
            ],
            'intro_choice' => [
                'title' => 'Odaberi svoj najbliži smjer',
            ],
        ]),
    ]);

    $business_owner_blocks = [
        stjepan_fcc_guide_block('business_owner_hero', 'headline', [
            'badge' => 'Za vlasnike biznisa',
            'title' => 'Ako već imaš klijente ili ugled, ovaj sustav možeš lakše uklopiti u ono što već radiš.',
            'text' => 'Ne moraš mijenjati svoj posao. Možeš dodati pametan sustav preporuka, proizvode i digitalni alat koji ti pomaže da ljudima pokažeš opciju bez neugodnog prodavanja.',
            'title_size' => 46,
        ]),
        stjepan_fcc_guide_block('business_owner_video', 'video', [
            'title' => 'Zašto Forever i FCC mogu imati smisla uz postojeći posao',
            'text' => 'Video za vlasnike malih biznisa, uslužne profesionalce i ljude s postojećim kontaktima.',
            'media_url' => $video('forever_prilika'),
            'layout_width' => 'two_thirds',
        ]),
        stjepan_fcc_guide_block('business_owner_fit', 'proof_card', [
            'badge' => 'Posebno dobro za',
            'title' => 'Fizioterapeute, kozmetičare, trenere, wellness ljude i vlasnike uslužnih biznisa.',
            'text' => 'Ako već razgovaraš s ljudima i imaš povjerenje svoje okoline, proizvodi i digitalni alat mogu postati prirodan dodatni kanal preporuke.',
            'layout_width' => 'third',
        ]),
        stjepan_fcc_guide_block('business_owner_reason', 'proof_card', [
            'badge' => 'Zašto može imati smisla',
            'title' => 'Imaš ljude koji ti već vjeruju, a FCC daje jedan organizirani link za preporuku i kontakt.',
            'text' => 'Ne moraš sve znati prvi dan. Ja ti pomažem složiti tvoj osobni način rada prema tvojoj situaciji.',
            'layout_width' => 'half',
        ]),
        stjepan_fcc_guide_block('business_owner_compliance', 'text', ['text' => $business_compliance, 'text_size' => 14, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('business_owner_actions', 'cta_group', [
            'text' => 'Odaberi najlogičniji sljedeći korak.',
            'buttons' => [
                stjepan_fcc_guide_action('owner_to_fcc', 'Pogledaj kako sustav radi', ['target_step_id' => 'fcc_system', 'event_key' => 'select_owner_to_fcc']),
                stjepan_fcc_guide_action('owner_to_check', 'Želim vidjeti je li ovo za mene', ['target_step_id' => 'check', 'style' => 'secondary', 'event_key' => 'select_owner_to_check']),
                stjepan_fcc_guide_action('owner_to_start', 'Spreman/na sam za Start paket', ['target_step_id' => 'start_package', 'style' => 'secondary', 'event_key' => 'select_owner_to_start']),
                stjepan_fcc_guide_action('owner_whatsapp', 'Pošalji mi poruku', ['action' => 'external_url', 'external_url' => $business_owner_whatsapp_url, 'style' => 'ghost', 'event_key' => 'select_owner_whatsapp']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $business_owner = stjepan_fcc_guide_step('business_owner', 'entry', 'business', 'offer', 'Poseban put za vlasnike biznisa', 'Za ljude koji već imaju kontakte, klijente ili vlastiti posao.', $business_owner_blocks, 'fcc_system', [
        'design_variant' => 'spotlight',
        'block_mode' => 'video',
        'surface' => [
            'progress_label' => 'Korak 1 od 4',
            'progress_current' => 1,
            'progress_total' => 4,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($business_owner_blocks, [
                'business_owner_hero' => [
                    'title' => 'Dodatni prihod za ljude koji već imaju kontakte, klijente ili vlastiti posao.',
                    'text' => 'Ovo je posebno zanimljivo ako već razgovaraš s ljudima, vodiš uslugu, imaš preporuke ili želiš nešto svoje razvijati uz postojeći posao.',
                ],
            ]),
        ],
    ]);

    $business_intro_blocks = [
        stjepan_fcc_guide_block('business_intro_hero', 'headline', [
            'badge' => 'Suradnja i proizvodi',
            'title' => 'Ako želiš krenuti ozbiljno, kreni uz proizvode, sustav i mentora.',
            'text' => 'Ovdje ti pokazujem zašto koristim Forever proizvode kao temelj i kako ih povezujem s digitalnim FCC sustavom, AI savjetnikom i osobnim mentorstvom.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('business_intro_video', 'video', [
            'title' => 'Zašto Forever Living Products i FCC zajedno',
            'text' => 'Kratak pregled proizvoda, globalnog potencijala i razloga zašto koristim FCC sustav.',
            'media_url' => $video('forever_prilika'),
        ]),
        stjepan_fcc_guide_block('business_intro_products', 'proof_card', [
            'badge' => 'Proizvodi',
            'title' => 'Proizvodi su temelj',
            'text' => 'Prvo je važno razumjeti što preporučuješ i zašto proizvodi imaju smisla u svakodnevnoj wellness rutini.',
            'layout_width' => 'third',
        ]),
        stjepan_fcc_guide_block('business_intro_system', 'proof_card', [
            'badge' => 'Sustav',
            'title' => 'Sustav pojednostavljuje preporuke',
            'text' => 'FCC ti daje jedan organizirani digitalni okvir za proizvode, kontakt, AI vodič i objašnjenje.',
            'layout_width' => 'third',
        ]),
        stjepan_fcc_guide_block('business_intro_mentor', 'proof_card', [
            'badge' => 'Mentorstvo',
            'title' => 'Mentorstvo daje smjer',
            'text' => 'Ne krećeš sam/a. Pomažem ti složiti realan način rada prema tvojoj situaciji.',
            'layout_width' => 'third',
        ]),
        stjepan_fcc_guide_block('business_intro_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('business_to_mentor', 'Želim vidjeti što dobivam u timu', ['target_step_id' => 'mentor_system', 'event_key' => 'select_business_to_mentor']),
                stjepan_fcc_guide_action('business_to_start', 'Spreman/na sam za Start paket', ['target_step_id' => 'start_package', 'style' => 'secondary', 'event_key' => 'select_business_to_start']),
                stjepan_fcc_guide_action('business_to_contact', 'Imam pitanje za Stjepana', ['target_step_id' => 'contact_result', 'style' => 'ghost', 'event_key' => 'select_business_to_contact']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $business_intro = stjepan_fcc_guide_step('business_intro', 'entry', 'business', 'offer', 'Poslovni uvod', 'Objašnjava Forever, proizvode i suradnju bez preopterećenja.', $business_intro_blocks, 'mentor_system', [
        'design_variant' => 'spotlight',
        'block_mode' => 'video',
        'surface' => [
            'progress_label' => 'Korak 1 od 4',
            'progress_current' => 1,
            'progress_total' => 4,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($business_intro_blocks, [
                'business_intro_hero' => [
                    'title' => 'Prvo razumij kako suradnja funkcionira, pa odluči mirno.',
                    'text' => 'Nema pritiska. Pogledaj što stoji iza proizvoda, kako izgleda sustav i kada ima smisla uzeti Start paket.',
                ],
                'business_intro_actions' => [
                    'buttons' => [
                        stjepan_fcc_guide_action('business_b_to_check', 'Želim kratku provjeru prije odluke', ['target_step_id' => 'check', 'event_key' => 'select_business_to_check']),
                        stjepan_fcc_guide_action('business_b_to_fcc', 'Pogledaj FCC sustav', ['target_step_id' => 'fcc_system', 'style' => 'secondary', 'event_key' => 'select_business_to_fcc']),
                        stjepan_fcc_guide_action('business_b_to_start', 'Start paket', ['target_step_id' => 'start_package', 'style' => 'ghost', 'event_key' => 'select_business_to_start']),
                    ],
                ],
            ]),
        ],
    ]);

    $mentor_blocks = [
        stjepan_fcc_guide_block('mentor_hero', 'headline', [
            'badge' => 'Moj tim',
            'title' => 'Ne krećeš sam/a. Dobivaš plan, sustav i moje mentorstvo.',
            'text' => 'Kad kreneš kroz moj tim, cilj je da prvih 120 dana ne lutaš, nego da točno znaš što radiš, kojim tempom i kojim redoslijedom.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('mentor_video', 'video', [
            'title' => '120-dnevni početni plan i podrška',
            'text' => 'Ovdje kasnije ubacuješ video o planu, AI asistentu, mentorstvu i prvim zadacima.',
            'media_url' => $video('120_dnevni_plan'),
        ]),
        stjepan_fcc_guide_block('mentor_list_a', 'proof_card', [
            'badge' => 'Dobivaš',
            'title' => 'Start paket, ulazak kao Forever suradnik, FCC aplikaciju, AI savjetnika i NFC karticu.',
            'text' => 'Uz to dobivaš WhatsApp podršku, pristup grupama i 120-dnevni početni plan.',
            'layout_width' => 'half',
        ]),
        stjepan_fcc_guide_block('mentor_list_b', 'proof_card', [
            'badge' => 'Smjer',
            'title' => 'Pomoć oko prvog webinara, uvodnog razgovora i povezivanja s postojećim kontaktima.',
            'text' => 'Plan se prilagođava tvom vremenu, iskustvu i cilju.',
            'layout_width' => 'half',
        ]),
        stjepan_fcc_guide_block('mentor_compliance', 'text', ['text' => $business_compliance, 'text_size' => 14]),
        stjepan_fcc_guide_block('mentor_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('mentor_to_start', 'Želim Start paket i ulazak u tim', ['target_step_id' => 'start_package', 'event_key' => 'select_mentor_to_start']),
                stjepan_fcc_guide_action('mentor_to_fcc', 'Prvo želim vidjeti FCC sustav', ['target_step_id' => 'fcc_system', 'style' => 'secondary', 'event_key' => 'select_mentor_to_fcc']),
                stjepan_fcc_guide_action('mentor_to_check', 'Želim kratku provjeru', ['target_step_id' => 'check', 'style' => 'ghost', 'event_key' => 'select_mentor_to_check']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $mentor_system = stjepan_fcc_guide_step('mentor_system', 'segment', 'business', 'proof', 'Što dobivaš u mom timu', 'Vrijednost ulaska u Stjepanov tim, plan i podrška.', $mentor_blocks, 'start_package', [
        'design_variant' => 'proof_strip',
        'block_mode' => 'video',
        'surface' => [
            'progress_label' => 'Korak 2 od 4',
            'progress_current' => 2,
            'progress_total' => 4,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($mentor_blocks, [
                'mentor_hero' => [
                    'title' => '120 dana jasnog početka i mentorstvo dok si aktivan/na u timu.',
                    'text' => 'Plan se prilagođava tvom vremenu, iskustvu i cilju. Rezultati ovise o tvojoj aktivnosti, ali ne ulaziš naslijepo.',
                ],
                'mentor_actions' => [
                    'buttons' => [
                        stjepan_fcc_guide_action('mentor_b_to_check', 'Želim provjeriti je li ovo za mene', ['target_step_id' => 'check', 'event_key' => 'select_mentor_to_check']),
                        stjepan_fcc_guide_action('mentor_b_to_start', 'Start paket', ['target_step_id' => 'start_package', 'style' => 'secondary', 'event_key' => 'select_mentor_to_start']),
                        stjepan_fcc_guide_action('mentor_b_to_ai', 'Pogledaj AI savjetnika', ['target_step_id' => 'ai_advisor', 'style' => 'ghost', 'event_key' => 'select_mentor_to_ai']),
                    ],
                ],
            ]),
        ],
    ]);

    $fcc_system_blocks = [
        stjepan_fcc_guide_block('fcc_system_hero', 'headline', [
            'badge' => 'FCC sustav',
            'title' => 'Jedan link koji povezuje proizvode, AI vodič, preporuku i kontakt.',
            'text' => 'FCC sustav pomaže da ne šalješ deset različitih linkova i ne objašnjavaš sve od nule. Sve je organizirano u jednom digitalnom okviru.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('fcc_system_video', 'video', [
            'title' => 'Kako izgleda FCC sustav u praksi',
            'text' => 'Ovdje ubacuješ video o globalnom linku, aplikaciji, NFC kartici, WhatsApp kontaktu, edukaciji i preporukama.',
            'media_url' => $video('fcc_aplikacija'),
        ]),
        stjepan_fcc_guide_block('fcc_system_person', 'proof_card', [
            'badge' => 'Što osoba vidi',
            'title' => 'Proizvode, AI vodiča, tvoj kontakt, objašnjenje suradnje i mogućnost kupnje ili upita.',
            'text' => 'Sustav daje strukturu bez potrebe da posjetitelj odmah sve čita ili da ti sve objašnjavaš ručno.',
            'layout_width' => 'half',
        ]),
        stjepan_fcc_guide_block('fcc_system_beginner', 'proof_card', [
            'badge' => 'Zašto je važno',
            'title' => 'Početnik ne mora sve znati prvi dan, a vlasnik biznisa dobiva profesionalniji kanal preporuke.',
            'text' => 'Može se dijeliti online, poslati porukom ili pokazati uživo preko NFC kartice.',
            'layout_width' => 'half',
        ]),
        stjepan_fcc_guide_block('fcc_system_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('fcc_to_start', 'Želim ovakav sustav za sebe', ['target_step_id' => 'start_package', 'event_key' => 'select_fcc_to_start']),
                stjepan_fcc_guide_action('fcc_to_demo_request', 'Prvo želim demo uz Stjepanovo objašnjenje', ['target_step_id' => 'result_demo_request', 'style' => 'secondary', 'event_key' => 'select_fcc_to_demo']),
                stjepan_fcc_guide_action('fcc_to_ai', 'Isprobaj AI vodiča', ['target_step_id' => 'ai_advisor', 'style' => 'ghost', 'event_key' => 'select_fcc_to_ai']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $fcc_system = stjepan_fcc_guide_step('fcc_system', 'entry', 'demo', 'demo', 'FCC sustav', 'Pokazuje FCC kao digitalni alat za proizvode, preporuke, kontakt i tim.', $fcc_system_blocks, 'ai_advisor', [
        'design_variant' => 'card',
        'block_mode' => 'video',
        'surface' => [
            'progress_label' => 'Korak 2 od 4',
            'progress_current' => 2,
            'progress_total' => 4,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($fcc_system_blocks, [
                'fcc_system_hero' => [
                    'title' => 'Tvoja digitalna kartica za online i offline preporuke.',
                    'text' => 'Možeš je dijeliti online, poslati porukom ili pokazati uživo preko NFC kartice. Ideja je da ljudima jednostavno pokažeš sustav, a ne da ih nagovaraš.',
                ],
                'fcc_system_actions' => [
                    'buttons' => [
                        stjepan_fcc_guide_action('fcc_b_to_demo', 'Želim da mi Stjepan pokaže demo', ['target_step_id' => 'result_demo_request', 'event_key' => 'select_fcc_to_demo']),
                        stjepan_fcc_guide_action('fcc_b_to_start', 'Spreman/na sam za Start paket', ['target_step_id' => 'start_package', 'style' => 'secondary', 'event_key' => 'select_fcc_to_start']),
                        stjepan_fcc_guide_action('fcc_b_to_rec', 'Kako preporuka izgleda u praksi', ['target_step_id' => 'recommendation_practice', 'style' => 'ghost', 'event_key' => 'select_fcc_to_recommend']),
                    ],
                ],
            ]),
        ],
    ]);

    $ai_blocks = [
        stjepan_fcc_guide_block('ai_hero', 'headline', [
            'badge' => 'AI vodič',
            'title' => 'Isprobaj alat koji tvojoj preporuci daje profesionalan okvir.',
            'text' => 'Kad tek krećeš, ne moraš znati sve proizvode. AI vodič pomaže korisniku da se informira i pronađe proizvode koji imaju smisla za njegov cilj.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('ai_video', 'video', [
            'title' => 'Zašto je AI vodič važan u preporuci',
            'text' => 'Ovdje ubacuješ video o tome kako AI pomaže kod proizvoda i prvog kontakta.',
            'media_url' => $video('ai_savjetnik'),
            'layout_width' => 'two_thirds',
        ]),
        stjepan_fcc_guide_block('ai_card', 'ai_product_advisor', [
            'badge' => 'Primjer alata',
            'title' => 'Pokreni AI vodiča',
            'text' => 'Ovo je primjer alata koji možeš koristiti u svom FCC sustavu.',
            'ai_button_label' => 'Pokreni AI vodiča',
            'ai_launcher_label' => 'AI vodič',
            'ai_intro_label' => 'Tvoj osobni proizvodni vodič',
            'ai_input_placeholder' => 'Napiši cilj ili pitanje...',
            'event_key' => 'open_ai_widget',
            'layout_width' => 'third',
        ]),
        stjepan_fcc_guide_block('ai_why', 'proof_card', [
            'badge' => 'Zašto pomaže',
            'title' => 'Smanjuje neugodu klasične prodaje i pokazuje kako bi izgledao tvoj sustav.',
            'text' => 'Korisnik može istražiti proizvode, a ti dobivaš prirodniji nastavak razgovora.',
            'layout_width' => 'half',
        ]),
        stjepan_fcc_guide_block('ai_compliance', 'text', ['text' => $product_compliance, 'text_size' => 14, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('ai_actions', 'cta_group', [
            'text' => 'Sad si vidio/la primjer alata koji možeš imati u svom FCC sustavu.',
            'buttons' => [
                stjepan_fcc_guide_action('ai_to_start', 'Da, želim svoj sustav', ['target_step_id' => 'start_package', 'event_key' => 'select_ai_to_start']),
                stjepan_fcc_guide_action('ai_to_check', 'Želim prvo razgovarati sa Stjepanom', ['target_step_id' => 'check', 'style' => 'secondary', 'event_key' => 'select_ai_to_check']),
                stjepan_fcc_guide_action('ai_to_products', 'Prvo želim proizvode i popust', ['target_step_id' => 'products_entry', 'style' => 'ghost', 'event_key' => 'select_ai_to_products']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $ai_advisor = stjepan_fcc_guide_step('ai_advisor', 'experience', 'demo', 'demo', 'AI savjetnik kao primjer sustava', 'AI vodič kao proizvodni alat i dokaz sustava.', $ai_blocks, 'start_package', [
        'design_variant' => 'card',
        'block_mode' => 'video',
        'surface' => [
            'progress_label' => 'Korak 3 od 4',
            'progress_current' => 3,
            'progress_total' => 4,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($ai_blocks, [
                'ai_hero' => [
                    'title' => 'Ne moraš sve objašnjavati sam/a.',
                    'text' => 'U mom timu dobivaš sustav koji korisniku može pomoći da istraži proizvode, a tebi olakšava prvi kontakt i preporuku.',
                ],
            ]),
        ],
    ]);

    $recommendation_blocks = [
        stjepan_fcc_guide_block('recommendation_hero', 'headline', [
            'badge' => 'Preporuka bez pritiska',
            'title' => 'Kako preporučuješ bez pritiska i neugodnog prodavanja.',
            'text' => 'Ideja nije da ljudima nešto namećeš, nego da im pokažeš sustav, proizvode i AI vodiča koji im pomaže da sami istraže što im ima smisla.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('recommendation_video', 'video', [
            'title' => 'Kako preporuka izgleda u stvarnom životu',
            'text' => 'Ovdje ubacuješ video o preporuci bez neugodnog prodavanja.',
            'media_url' => $video('preporuka_bez_pritiska'),
        ]),
        stjepan_fcc_guide_block('recommendation_problem', 'proof_card', [
            'badge' => 'Klasičan problem',
            'title' => 'Mnogi odustanu jer ne žele glumiti stručnjaka ili ne znaju kako započeti razgovor.',
            'text' => 'FCC daje primjer, ne pritisak.',
            'layout_width' => 'third',
        ]),
        stjepan_fcc_guide_block('recommendation_solution', 'proof_card', [
            'badge' => 'FCC pristup',
            'title' => 'Pokazuješ sustav, dijeliš iskustvo i daješ osobi mogućnost da sama istraži.',
            'text' => 'Ako želi više, tada razgovarate.',
            'layout_width' => 'third',
        ]),
        stjepan_fcc_guide_block('recommendation_fit', 'proof_card', [
            'badge' => 'Kome pomaže',
            'title' => 'Početnicima, ljudima bez prodajnog iskustva i vlasnicima biznisa.',
            'text' => 'Posebno je korisno kada želiš spojiti online i offline pristup.',
            'layout_width' => 'third',
        ]),
        stjepan_fcc_guide_block('recommendation_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('rec_to_start', 'Želim Start paket i svoj FCC sustav', ['target_step_id' => 'start_package', 'event_key' => 'select_rec_to_start']),
                stjepan_fcc_guide_action('rec_to_check', 'Želim kratku provjeru prije starta', ['target_step_id' => 'check', 'style' => 'secondary', 'event_key' => 'select_rec_to_check']),
                stjepan_fcc_guide_action('rec_to_products', 'Još me zanimaju proizvodi', ['target_step_id' => 'products_entry', 'style' => 'ghost', 'event_key' => 'select_rec_to_products']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $recommendation_practice = stjepan_fcc_guide_step('recommendation_practice', 'experience', 'business', 'proof', 'Kako preporuka izgleda u praksi', 'Objašnjava preporuku bez pritiska.', $recommendation_blocks, 'start_package', [
        'design_variant' => 'proof_strip',
        'block_mode' => 'video',
        'surface' => [
            'progress_label' => 'Korak 3 od 4',
            'progress_current' => 3,
            'progress_total' => 4,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($recommendation_blocks, [
                'recommendation_hero' => [
                    'title' => 'Sustav koji daje primjer, ne prodajni pritisak.',
                    'text' => 'Umjesto da objašnjavaš sve od nule, možeš pokazati svoju aplikaciju, NFC karticu ili link i pustiti osobu da sama istraži.',
                ],
                'recommendation_actions' => [
                    'buttons' => [
                        stjepan_fcc_guide_action('rec_b_to_contact', 'Želim razgovarati sa Stjepanom', ['target_step_id' => 'contact_result', 'event_key' => 'select_rec_to_contact']),
                        stjepan_fcc_guide_action('rec_b_to_start', 'Start paket', ['target_step_id' => 'start_package', 'style' => 'secondary', 'event_key' => 'select_rec_to_start']),
                        stjepan_fcc_guide_action('rec_b_to_products', 'Pogledaj proizvode', ['target_step_id' => 'products_entry', 'style' => 'ghost', 'event_key' => 'select_rec_to_products']),
                    ],
                ],
            ]),
        ],
    ]);

    $products_entry_blocks = [
        stjepan_fcc_guide_block('products_entry_hero', 'headline', [
            'badge' => 'Proizvodi i 15%',
            'title' => 'Prvo pronađi proizvode za sebe i ostvari 15% popusta.',
            'text' => 'Ne moraš odmah razmišljati o suradnji. Kreni od proizvoda, pitaj AI vodiča što ima smisla za tvoj cilj i naruči preko službenog Forever webshopa.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('products_entry_video', 'video', [
            'title' => 'Kako krenuti s proizvodima',
            'text' => 'Ovdje ubacuješ video o proizvodnom putu, AI vodiču i 15% popusta.',
            'media_url' => $video('proizvodi_popust'),
            'layout_width' => 'two_thirds',
        ]),
        stjepan_fcc_guide_block('products_entry_ai', 'ai_product_advisor', [
            'badge' => 'AI preporuka',
            'title' => 'Pitaj AI vodiča',
            'text' => 'Opiši što tražiš u općem wellness smislu i dobit ćeš smjer za istraživanje proizvoda.',
            'ai_button_label' => 'Pokreni AI vodiča',
            'event_key' => 'open_ai_from_products',
            'layout_width' => 'third',
        ]),
        stjepan_fcc_guide_block('products_entry_steps', 'proof_card', [
            'badge' => 'Kako ide',
            'title' => 'Pokreni AI vodiča, opiši cilj, pogledaj preporuku i naruči preko službenog webshopa.',
            'text' => 'Ako ti se sustav svidi, isti okvir možeš koristiti kao suradnik.',
            'layout_width' => 'half',
        ]),
        stjepan_fcc_guide_block('products_entry_compliance', 'text', ['text' => $product_compliance, 'text_size' => 14, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('products_entry_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('products_to_ai', 'Pokreni AI vodiča', ['target_step_id' => 'ai_advisor', 'event_key' => 'open_ai_from_products']),
                stjepan_fcc_guide_action('products_to_shop', 'Ostvari 15% popusta', ['action' => 'external_url', 'external_url' => $product_shop_url, 'style' => 'secondary', 'event_key' => 'click_webshop_discount']),
                stjepan_fcc_guide_action('products_to_start', 'Želim ovaj sustav za sebe', ['target_step_id' => 'start_package', 'style' => 'ghost', 'event_key' => 'select_products_to_start']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $products_entry = stjepan_fcc_guide_step('products_entry', 'entry', 'products', 'offer', 'Proizvodni put / AI i 15% popust', 'Product-first put s AI vodičem i webshop korakom.', $products_entry_blocks, 'product_trust', [
        'design_variant' => 'card',
        'block_mode' => 'video',
        'surface' => [
            'progress_label' => 'Korak 1 od 3',
            'progress_current' => 1,
            'progress_total' => 3,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($products_entry_blocks, [
                'products_entry_hero' => [
                    'title' => 'Isprobaj proizvode, pa vidi ima li smisla i poslovni dio.',
                    'text' => 'Najbolji početak je osobno iskustvo. Ako ti proizvodi i sustav budu imali smisla, kasnije možeš razgovarati sa Stjepanom o suradnji.',
                ],
                'products_entry_actions' => [
                    'buttons' => [
                        stjepan_fcc_guide_action('products_b_to_shop', 'Ostvari 15% popusta', ['action' => 'external_url', 'external_url' => $product_shop_url, 'event_key' => 'click_webshop_discount']),
                        stjepan_fcc_guide_action('products_b_to_ai', 'Pitaj AI vodiča', ['target_step_id' => 'ai_advisor', 'style' => 'secondary', 'event_key' => 'open_ai_from_products']),
                        stjepan_fcc_guide_action('products_b_to_business', 'Zanima me i suradnja', ['target_step_id' => 'business_intro', 'style' => 'ghost', 'event_key' => 'select_products_to_business']),
                    ],
                ],
            ]),
        ],
    ]);

    $product_trust_blocks = [
        stjepan_fcc_guide_block('product_trust_hero', 'headline', [
            'badge' => 'Proizvodi kao temelj',
            'title' => 'Zašto su proizvodi temelj ove suradnje.',
            'text' => 'Prije sustava, preporuke i tima važno je da razumiješ proizvode i vidiš imaju li smisla za tebe.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('product_trust_video', 'video', [
            'title' => 'Prije posla dolazi iskustvo s proizvodima',
            'text' => 'Ovdje ubacuješ video o proizvodima kao temelju povjerenja.',
            'media_url' => $video('proizvodi_temelj'),
        ]),
        stjepan_fcc_guide_block('product_trust_offer', 'product_offer', [
            'badge' => 'Preporuka proizvoda',
            'title' => 'Istraži proizvodni smjer koji ti najviše odgovara',
            'text' => 'Kreni od opće wellness rutine, osobne njege, aloe proizvoda ili početnog paketa.',
            'product_source_mode' => 'dynamic',
            'product_translation_key' => $primary_product_key,
            'product_language_mode' => 'page',
            'product_fallback_language_code' => 'hr',
            'product_primary_mode' => 'blog_guide',
            'product_primary_cta_text' => 'Pogledaj vodič proizvoda',
            'product_secondary_enabled' => true,
            'product_secondary_mode' => 'direct_shop',
            'product_secondary_cta_text' => 'Idi na službeni shop',
            'product_mappings' => [
                ['id' => 'map_energy', 'match_value' => 'energy', 'product_translation_key' => $product_keys['energy'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_weight', 'match_value' => 'weight', 'product_translation_key' => $product_keys['weight'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_skin', 'match_value' => 'skin', 'product_translation_key' => $product_keys['skin'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_routine', 'match_value' => 'routine', 'product_translation_key' => $product_keys['routine'] ?: $primary_product_key, 'product_blog_post_id' => 0],
            ],
        ]),
        stjepan_fcc_guide_block('product_trust_compliance', 'text', ['text' => $product_compliance, 'text_size' => 14]),
        stjepan_fcc_guide_block('product_trust_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('trust_to_ai', 'Želim AI preporuku proizvoda', ['target_step_id' => 'ai_advisor', 'event_key' => 'click_product_ai']),
                stjepan_fcc_guide_action('trust_to_shop', 'Ostvari 15% popusta', ['action' => 'external_url', 'external_url' => $product_shop_url, 'style' => 'secondary', 'event_key' => 'click_webshop_from_trust']),
                stjepan_fcc_guide_action('trust_to_start', 'Želim Start paket', ['target_step_id' => 'start_package', 'style' => 'ghost', 'event_key' => 'select_trust_to_start']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $product_trust = stjepan_fcc_guide_step('product_trust', 'segment', 'products', 'proof', 'Proizvodi kao temelj povjerenja', 'Dodatna proizvodna stranica prije odluke.', $product_trust_blocks, 'result_products', [
        'design_variant' => 'proof_strip',
        'block_mode' => 'product_offer',
        'surface' => [
            'progress_label' => 'Korak 2 od 3',
            'progress_current' => 2,
            'progress_total' => 3,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($product_trust_blocks, [
                'product_trust_hero' => [
                    'title' => 'Prije posla dolazi iskustvo s proizvodima.',
                    'text' => 'Kada osobno koristiš proizvode i razumiješ sustav, preporuka postaje prirodnija i uvjerljivija.',
                ],
                'product_trust_actions' => [
                    'buttons' => [
                        stjepan_fcc_guide_action('trust_b_to_start', 'Želim Start paket i proizvode za početak', ['target_step_id' => 'start_package', 'event_key' => 'select_trust_to_start']),
                        stjepan_fcc_guide_action('trust_b_to_shop', 'Prvo želim 15% popusta', ['action' => 'external_url', 'external_url' => $product_shop_url, 'style' => 'secondary', 'event_key' => 'click_webshop_from_trust']),
                        stjepan_fcc_guide_action('trust_b_to_contact', 'Razgovor sa Stjepanom', ['target_step_id' => 'contact_result', 'style' => 'ghost', 'event_key' => 'select_trust_to_contact']),
                    ],
                ],
            ]),
        ],
    ]);

    $start_blocks = [
        stjepan_fcc_guide_block('start_hero', 'headline', [
            'badge' => 'Start paket',
            'title' => 'Start paket: najbrži ulazak u moj FCC sustav i tim.',
            'text' => 'Ako si razumio/la proizvode, sustav i mentorstvo, ovo je najkraći put da kreneš konkretno.',
            'title_size' => 50,
        ]),
        stjepan_fcc_guide_block('start_video', 'video', [
            'title' => 'Prije narudžbe pogledaj što dobivaš kad kreneš u moj tim',
            'text' => 'Ovdje ubacuješ video o Start paketu, prvim danima i što se događa nakon narudžbe.',
            'media_url' => $video('120_dnevni_plan'),
        ]),
        stjepan_fcc_guide_block('start_key_message', 'proof_card', [
            'badge' => 'Važno',
            'title' => 'Start paket nije prazna članarina.',
            'text' => 'To je proizvodni paket za početak, uz koji ulaziš kao Forever suradnik i možeš krenuti kroz moj FCC sustav.',
            'layout_width' => 'half',
        ]),
        stjepan_fcc_guide_block('start_products', 'proof_card', [
            'badge' => 'Primjer paketa',
            'title' => 'Start Your Journey Best Sellers C9 Vanilla',
            'text' => 'Paket uključuje izbor popularnih proizvoda, osobnu njegu, aloe rutinu i wellness početak. Dostupnost i detalji ovise o službenom Forever webshopu.',
            'layout_width' => 'half',
        ]),
        stjepan_fcc_guide_block('start_team', 'proof_card', [
            'badge' => 'Od Stjepana',
            'title' => 'Dobivaš mentorstvo, 120-dnevni plan, FCC aplikaciju, AI vodiča, NFC karticu i WhatsApp podršku.',
            'text' => 'Nakon narudžbe javiš mi se, dogovorimo prvi korak i spojim te na potrebne upute.',
            'layout_width' => 'half',
        ]),
        stjepan_fcc_guide_block('start_compliance', 'text', ['text' => $business_compliance, 'text_size' => 14, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('start_countdown', 'countdown', [
            'title' => 'Sljedeći Zoom onboarding za nove suradnike',
            'text' => 'Onboarding se održava svaki četvrtak u 20:00 putem Zooma. Nakon registracije osobno ću te kontaktirati i poslati Zoom link, pripremu i sve što ti treba za prvi webinar i početak.',
            'countdown_mode' => 'weekly',
            'countdown_weekly_day' => 4,
            'countdown_weekly_time' => '20:00',
            'countdown_timezone' => 'Europe/Zagreb',
            'countdown_style' => 'spotlight',
        ]),
        stjepan_fcc_guide_block('start_actions', 'cta_group', [
            'text' => 'Odaberi kako želiš napraviti sljedeći korak. Nakon klika na narudžbu otvorit će se službena Forever Living stranica s preporukom i direktnim upisom.',
            'buttons' => [
                stjepan_fcc_guide_action('start_order', 'Naruči Start paket', ['hint' => 'Službeni Forever Living webshop s mentor preporukom i automatskim odabirom zemlje.', 'action' => 'external_url', 'external_url' => vip_funnel_get_forever_business_referral_action_token(), 'event_key' => 'click_start_order', 'sticky' => true]),
                stjepan_fcc_guide_action('start_question', 'Imam pitanje prije narudžbe', ['action' => 'external_url', 'external_url' => $question_whatsapp_url, 'style' => 'secondary', 'event_key' => 'click_start_question']),
                stjepan_fcc_guide_action('start_unsure', 'Nisam siguran/na - želim kratki razgovor', ['target_step_id' => 'result_contact_hot', 'style' => 'ghost', 'event_key' => 'select_start_to_contact']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $start_package = stjepan_fcc_guide_step('start_package', 'experience', 'business', 'cta', 'Start paket', 'Najvažnija konverzijska stranica.', $start_blocks, 'result_contact_hot', [
        'design_variant' => 'decision',
        'block_mode' => 'video',
        'surface' => [
            'progress_label' => 'Korak 4 od 4',
            'progress_current' => 4,
            'progress_total' => 4,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($start_blocks, [
                'start_hero' => [
                    'title' => 'Spreman/na? Kreni s proizvodima, sustavom i mentorstvom.',
                    'text' => 'Start paket ti daje proizvode za početak, ulazak kao Forever suradnik i mogućnost da se uključiš u moj FCC sustav.',
                ],
                'start_actions' => [
                    'buttons' => [
                        stjepan_fcc_guide_action('start_b_order', 'Želim Start paket i Stjepanovo mentorstvo', ['hint' => 'Službeni Forever Living webshop s mentor preporukom i automatskim odabirom zemlje.', 'action' => 'external_url', 'external_url' => vip_funnel_get_forever_business_referral_action_token(), 'event_key' => 'click_start_order', 'sticky' => true]),
                        stjepan_fcc_guide_action('start_b_check', 'Želim kratku provjeru prije narudžbe', ['target_step_id' => 'check', 'style' => 'secondary', 'event_key' => 'select_start_to_check']),
                        stjepan_fcc_guide_action('start_b_whatsapp', 'Pošalji mi WhatsApp poruku', ['action' => 'external_url', 'external_url' => $question_whatsapp_url, 'style' => 'ghost', 'event_key' => 'click_start_question']),
                    ],
                ],
            ]),
        ],
    ]);

    $check_blocks = [
        stjepan_fcc_guide_block('check_hero', 'headline', [
            'badge' => 'Kratka provjera',
            'title' => 'Odgovori iskreno i pokazat ću ti najbolji sljedeći korak.',
            'text' => 'Ovo nije test. Samo želim vidjeti ima li za tebe više smisla Start paket, kratki razgovor, demo, proizvodi ili mirniji nastavak.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('check_goal', 'radio_survey', [
            'title' => 'Što ti je trenutno najbliže?',
            'text' => 'Odaberi ono što te najbolje opisuje.',
            'required' => true,
            'route_on_submit' => false,
            'options' => [
                stjepan_fcc_guide_action('check_goal_owner', 'Imam svoj posao/klijente i želim dodatni kanal preporuka', ['value' => 'existing_business', 'hint' => 'Želim vidjeti kako bi se sustav uklopio u ono što već radim.']),
                stjepan_fcc_guide_action('check_goal_income', 'Želim ozbiljnije pokrenuti dodatni prihod', ['value' => 'serious_income', 'hint' => 'Spreman/na sam učiti i raditi uz jasne korake.']),
                stjepan_fcc_guide_action('check_goal_products', 'Prvo želim proizvode i popust', ['value' => 'product_first', 'hint' => 'Želim krenuti od proizvoda i kasnije vidjeti poslovni dio.']),
                stjepan_fcc_guide_action('check_goal_demo', 'Želim vidjeti FCC demo', ['value' => 'demo_interest', 'hint' => 'Prvo želim razumjeti kako sustav izgleda.']),
                stjepan_fcc_guide_action('check_goal_research', 'Još samo istražujem', ['value' => 'research', 'hint' => 'Želim mirno vidjeti opcije bez pritiska.']),
            ],
        ]),
        stjepan_fcc_guide_block('check_time', 'radio_survey', [
            'title' => 'Koliko realno vremena možeš odvojiti tjedno?',
            'text' => 'Bolje je odgovoriti realno nego idealno.',
            'required' => true,
            'route_on_submit' => false,
            'options' => [
                stjepan_fcc_guide_action('check_time_1_3', '1-3 sata tjedno', ['value' => 'time_1_3', 'hint' => 'Za miran početak i osnovne zadatke.']),
                stjepan_fcc_guide_action('check_time_4_7', '4-7 sati tjedno', ['value' => 'time_4_7', 'hint' => 'Dovoljno za ozbiljan start i prve kontakte.']),
                stjepan_fcc_guide_action('check_time_8', '8+ sati tjedno', ['value' => 'time_8_plus', 'hint' => 'Za brži ritam i aktivniji početak.']),
                stjepan_fcc_guide_action('check_time_no', 'Trenutno nemam vrijeme, ali želim ostati u kontaktu', ['value' => 'time_no_capacity', 'hint' => 'Bolje je krenuti kasnije nego bez fokusa.']),
            ],
        ]),
        stjepan_fcc_guide_block('check_budget', 'radio_survey', [
            'title' => 'Je li ti Start paket od približno 360 EUR realan ovaj tjedan ako zaključiš da je ovo za tebe?',
            'text' => 'Ovo pitanje ne služi za pritisak, nego da te ne vodim na pogrešan sljedeći korak.',
            'required' => true,
            'route_on_submit' => true,
            'options' => [
                stjepan_fcc_guide_action('check_budget_now', 'Da, mogu odmah', ['value' => 'ready_360_now', 'target_step_id' => 'result_start', 'hint' => 'Ako mi je smjer jasan, mogu napraviti narudžbu i krenuti.']),
                stjepan_fcc_guide_action('check_budget_call', 'Da, ali želim kratak razgovor prije narudžbe', ['value' => 'ready_360_call', 'target_step_id' => 'result_contact_hot', 'hint' => 'Trebam potvrdu prije narudžbe.']),
                stjepan_fcc_guide_action('check_budget_system', 'Prvo želim vidjeti sustav', ['value' => 'see_system_first', 'target_step_id' => 'result_demo_request', 'hint' => 'Želim bolje razumjeti kako FCC radi.']),
                stjepan_fcc_guide_action('check_budget_not', 'Ne sada, želim prvo proizvode ili više informacija', ['value' => 'not_ready', 'target_step_id' => 'result_calm_next_step', 'hint' => 'Trenutno nije pravi trenutak za Start paket.']),
            ],
        ]),
        stjepan_fcc_guide_block('check_contact', 'radio_survey', [
            'title' => 'Kako želiš da te Stjepan kontaktira?',
            'text' => 'Za ovu provjeru najbrži su WhatsApp ili poziv.',
            'required' => true,
            'route_on_submit' => false,
            'options' => [
                stjepan_fcc_guide_action('check_contact_whatsapp', 'WhatsApp poruka', ['value' => 'channel_whatsapp', 'hint' => 'Najbrže za prvi kontakt.']),
                stjepan_fcc_guide_action('check_contact_phone', 'Kratki telefonski poziv', ['value' => 'channel_phone', 'hint' => 'Najbolje za brza pitanja.']),
                stjepan_fcc_guide_action('check_contact_whatsapp_call', 'WhatsApp poziv', ['value' => 'channel_whatsapp_call', 'hint' => 'Prvo poruka, zatim dogovor poziva.']),
                stjepan_fcc_guide_action('check_contact_none', 'Za sada ne želim kontakt, samo sljedeći korak', ['value' => 'channel_no_contact', 'hint' => 'Dobit ćeš preporučeni nastavak bez obveze.']),
            ],
        ]),
        stjepan_fcc_guide_block('check_name', 'full_name_field', ['title' => 'Ime i prezime', 'placeholder' => 'Upiši ime i prezime', 'required' => true, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('check_phone', 'phone_field', ['title' => 'WhatsApp / telefon', 'placeholder' => 'Upiši broj na koji te mogu brzo kontaktirati', 'required' => true, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('check_email', 'email_field', ['title' => 'Email - opcionalno', 'placeholder' => 'Upiši email ako želiš potvrdu', 'required' => false, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('check_contact_time', 'text_field', ['title' => 'Najbolji termin za kontakt', 'placeholder' => 'npr. danas poslije 17h, sutra ujutro, navečer', 'field_key' => 'contact_time', 'required' => false, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('check_country', 'text_field', ['title' => 'Zemlja - opcionalno', 'placeholder' => 'npr. Hrvatska, Slovenija, Njemačka...', 'field_key' => 'country', 'required' => false]),
        stjepan_fcc_guide_block('check_consent', 'checkbox_field', ['title' => $consent_text, 'text' => $privacy_contact_text, 'field_key' => 'contact_consent', 'required' => true]),
        stjepan_fcc_guide_block('check_submit', 'cta_group', [
            'text' => 'Pošalji odgovore i dobit ćeš preporučeni sljedeći korak.',
            'buttons' => [
                stjepan_fcc_guide_action('check_submit_btn', 'Pošalji provjeru i pokaži mi sljedeći korak', ['action' => 'submit_next', 'require_submit' => true, 'event_key' => 'submit_check']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $check = stjepan_fcc_guide_step('check', 'segment', 'business', 'question', 'Kratka provjera', 'Kratka provjera za najbolji sljedeći korak.', $check_blocks, 'result_calm_next_step', [
        'design_variant' => 'stacked',
        'block_mode' => 'contact_form',
        'surface' => [
            'progress_label' => 'Korak 2 od 3',
            'progress_current' => 2,
            'progress_total' => 3,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($check_blocks, [
                'check_hero' => [
                    'title' => 'Kratka provjera prije starta ili razgovora.',
                    'text' => 'Ako nisi siguran/na što odabrati, odgovori na nekoliko pitanja i sustav će te usmjeriti.',
                ],
            ]),
        ],
    ]);

    $result_start_blocks = [
        stjepan_fcc_guide_block('result_start_hero', 'headline', [
            'badge' => 'Preporučeni korak',
            'title' => 'Tvoj najbolji sljedeći korak je Start paket.',
            'text' => 'Prema onome što si odabrao/la, ima smisla da kreneš konkretno: proizvodi, ulazak u moj tim i FCC sustav.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('result_start_steps', 'proof_card', [
            'badge' => 'Što napravi sada',
            'title' => 'Klikni Start paket, dovrši narudžbu i pošalji mi WhatsApp poruku.',
            'text' => 'Dogovorit ćemo prvi poziv/webinar i složiti tvoj osobni FCC početak.',
        ]),
        stjepan_fcc_guide_block('result_start_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('result_start_order', 'Naruči Start paket', ['action' => 'external_url', 'external_url' => vip_funnel_get_forever_business_referral_action_token(), 'event_key' => 'click_result_start_order', 'sticky' => true]),
                stjepan_fcc_guide_action('result_start_whatsapp', 'Javljam se Stjepanu nakon narudžbe', ['action' => 'external_url', 'external_url' => $after_order_whatsapp_url, 'style' => 'secondary', 'event_key' => 'click_result_start_whatsapp']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $result_start = stjepan_fcc_guide_step('result_start', 'trust', 'business', 'cta', 'Rezultat - Start', 'Završni korak za spremnu osobu.', $result_start_blocks, '', [
        'design_variant' => 'decision',
        'block_mode' => 'message',
        'surface' => [
            'progress_label' => 'Korak 3 od 3',
            'progress_current' => 3,
            'progress_total' => 3,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($result_start_blocks, [
                'result_start_hero' => [
                    'title' => 'Spreman/na si za konkretan početak.',
                    'text' => 'Kreni sa Start paketom, a nakon narudžbe mi se javi da dogovorimo prvi korak i webinar.',
                ],
            ]),
        ],
    ]);

    $result_contact_blocks = [
        stjepan_fcc_guide_block('result_contact_hero', 'headline', [
            'badge' => 'Razgovor prije odluke',
            'title' => 'Tvoj najbolji sljedeći korak je kratak razgovor sa Stjepanom.',
            'text' => 'Ne trebaš još sve znati. Dovoljno je da mi pošalješ poruku i prođemo što ti ima najviše smisla.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('result_contact_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('hot_whatsapp', 'Pošalji WhatsApp poruku', ['action' => 'external_url', 'external_url' => stjepan_fcc_guide_whatsapp_url($owner_whatsapp_url, 'Bok Stjepane, prošao/la sam kratku provjeru i želim razgovor prije Start paketa.') ?: $fallback_mailto, 'event_key' => 'click_hot_whatsapp']),
                stjepan_fcc_guide_action('hot_to_start', 'Ipak želim odmah Start paket', ['target_step_id' => 'start_package', 'style' => 'secondary', 'event_key' => 'click_hot_to_start']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $result_contact_hot = stjepan_fcc_guide_step('result_contact_hot', 'trust', 'business', 'cta', 'Rezultat - Kontakt', 'Za osobu koja želi razgovor prije Start paketa.', $result_contact_blocks, '', [
        'design_variant' => 'decision',
        'block_mode' => 'message',
        'surface' => [
            'progress_label' => 'Korak 3 od 3',
            'progress_current' => 3,
            'progress_total' => 3,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($result_contact_blocks, [
                'result_contact_hero' => [
                    'title' => 'Prije narudžbe prođimo najvažnije zajedno.',
                    'text' => 'Ako ti je Start paket realan, ali želiš potvrdu, ovo je pravi sljedeći korak.',
                ],
            ]),
        ],
    ]);

    $result_demo_blocks = [
        stjepan_fcc_guide_block('result_demo_hero', 'headline', [
            'badge' => 'Demo zahtjev',
            'title' => 'Tvoj sljedeći korak je FCC demo uz Stjepanovo objašnjenje.',
            'text' => 'Demo ima najviše smisla kada ga pogledamo kroz tvoju situaciju: posao, kontakte, vrijeme i cilj.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('result_demo_reason', 'radio_survey', [
            'title' => 'Zašto želiš demo pristup?',
            'text' => 'Odaberi razlog koji ti je najbliži.',
            'required' => true,
            'route_on_submit' => false,
            'options' => [
                stjepan_fcc_guide_action('demo_reason_start', 'Želim vidjeti sustav prije odluke o Start paketu', ['value' => 'demo_before_start', 'hint' => 'Zanima me FCC, ali prije odluke želim razumjeti kako radi u praksi.']),
                stjepan_fcc_guide_action('demo_reason_recommend', 'Želim koristiti sustav za vlastite preporuke', ['value' => 'demo_recommendations', 'hint' => 'Želim vidjeti kako bih vodio/la ljude od interesa do proizvoda, razgovora ili Start paketa.']),
                stjepan_fcc_guide_action('demo_reason_team', 'Već imam kontakte, kupce ili tim', ['value' => 'demo_existing_network', 'hint' => 'Želim vidjeti može li mi FCC pomoći u prezentaciji, kontaktima i follow-upu.']),
                stjepan_fcc_guide_action('demo_reason_research', 'Još istražujem i želim osnovni prikaz', ['value' => 'demo_research', 'hint' => 'Nisam još spreman/na za odluku, ali želim mirno vidjeti kako sustav izgleda.']),
            ],
        ]),
        stjepan_fcc_guide_block('result_demo_name', 'full_name_field', ['title' => 'Ime i prezime', 'placeholder' => 'Upiši ime i prezime', 'required' => true, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('result_demo_phone', 'phone_field', ['title' => 'WhatsApp / telefon', 'placeholder' => 'Upiši broj na koji te mogu brzo kontaktirati', 'required' => true, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('result_demo_email', 'email_field', ['title' => 'Email za demo pristup', 'placeholder' => 'Upiši email za demo račun', 'required' => true, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('result_demo_contact_time', 'text_field', ['title' => 'Najbolji termin za kontakt', 'placeholder' => 'npr. danas poslije 17h', 'field_key' => 'contact_time', 'required' => false, 'layout_width' => 'half']),
        stjepan_fcc_guide_block('result_demo_about', 'text_field', ['title' => 'Čime se baviš / kratko o sebi', 'placeholder' => 'npr. imam salon, radim online, imam tim...', 'field_key' => 'about_person', 'required' => false]),
        stjepan_fcc_guide_block('result_demo_focus', 'text_field', ['title' => 'Što želiš vidjeti u demu?', 'placeholder' => 'npr. preporuke, proizvodi, kontakti, follow-up, Start paket', 'field_key' => 'demo_focus', 'required' => false]),
        stjepan_fcc_guide_block('result_demo_consent', 'checkbox_field', ['title' => $consent_text, 'text' => 'Email koristimo samo ako je potreban za slanje ili aktivaciju demo pristupa. Privacy: ' . $privacy_url, 'field_key' => 'contact_consent', 'required' => true]),
        stjepan_fcc_guide_block('result_demo_submit', 'cta_group', [
            'text' => 'Pošalji zahtjev i javit ću ti se s najboljim načinom za demo pregled.',
            'buttons' => [
                stjepan_fcc_guide_action('demo_submit', 'Pošalji zahtjev za demo pristup', ['action' => 'submit_next', 'target_step_id' => 'contact_result', 'require_submit' => true, 'event_key' => 'submit_demo_request']),
                stjepan_fcc_guide_action('demo_to_contact', 'Radije želim razgovor o Start paketu', ['target_step_id' => 'result_contact_hot', 'style' => 'secondary', 'event_key' => 'click_demo_to_contact']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $result_demo_request = stjepan_fcc_guide_step('result_demo_request', 'segment', 'demo', 'demo', 'Rezultat - Demo zahtjev', 'Gated demo zahtjev s kontaktom.', $result_demo_blocks, 'contact_result', [
        'design_variant' => 'card',
        'block_mode' => 'contact_form',
        'surface' => [
            'progress_label' => 'Korak 3 od 3',
            'progress_current' => 3,
            'progress_total' => 3,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($result_demo_blocks, [
                'result_demo_hero' => [
                    'title' => 'Pokazat ću ti demo, ali prvo trebam razumjeti gdje si sada.',
                    'text' => 'Tako ti ne pokazujem generički sustav, nego način kako bi ga mogao/la koristiti za sebe.',
                ],
            ]),
        ],
    ]);

    $result_products_blocks = [
        stjepan_fcc_guide_block('result_products_hero', 'headline', [
            'badge' => 'Proizvodi prvi',
            'title' => 'Tvoj najbolji prvi korak su proizvodi i AI preporuka.',
            'text' => 'Kreni mirno. Isprobaj proizvode, vidi što ti ima smisla i kasnije se uvijek možeš javiti za suradnju.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('result_products_ai', 'ai_product_advisor', [
            'badge' => 'AI preporuka',
            'title' => 'Pokreni AI vodiča',
            'text' => 'Opiši cilj i dobit ćeš opći wellness smjer za istraživanje proizvoda.',
            'ai_button_label' => 'Pokreni AI vodiča',
            'event_key' => 'open_ai_result_products',
        ]),
        stjepan_fcc_guide_block('result_products_compliance', 'text', ['text' => $product_compliance, 'text_size' => 14]),
        stjepan_fcc_guide_block('result_products_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('result_products_shop', 'Ostvari 15% popusta', ['action' => 'external_url', 'external_url' => $product_shop_url, 'event_key' => 'click_webshop_result']),
                stjepan_fcc_guide_action('result_products_business', 'Zanima me kasnije i suradnja', ['target_step_id' => 'business_intro', 'style' => 'secondary', 'event_key' => 'select_products_to_business']),
                stjepan_fcc_guide_action('result_products_contact', 'Želim razgovarati sa Stjepanom', ['action' => 'external_url', 'external_url' => $product_whatsapp_url, 'style' => 'ghost', 'event_key' => 'select_products_to_contact']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $result_products = stjepan_fcc_guide_step('result_products', 'experience', 'products', 'cta', 'Rezultat - Proizvodi', 'Product-first završetak s AI i webshopom.', $result_products_blocks, '', [
        'design_variant' => 'decision',
        'block_mode' => 'product_offer',
        'surface' => [
            'progress_label' => 'Korak 3 od 3',
            'progress_current' => 3,
            'progress_total' => 3,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($result_products_blocks, [
                'result_products_hero' => [
                    'title' => 'Prvo iskustvo s proizvodima, pa odluka o suradnji.',
                    'text' => 'Ovo je dobar put ako još ne želiš Start paket, ali želiš upoznati proizvode i sustav.',
                ],
            ]),
        ],
    ]);

    $result_calm_blocks = [
        stjepan_fcc_guide_block('result_calm_hero', 'headline', [
            'badge' => 'Miran nastavak',
            'title' => 'Nije cilj da svi krenu odmah. Cilj je da dobiješ pravi sljedeći korak.',
            'text' => 'Ako sada nije trenutak za Start paket, možeš mirno upoznati proizvode, AI vodiča ili se javiti kad budeš spreman/na.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('result_calm_contact', 'text_field', ['title' => 'Email ili WhatsApp za uvodni video', 'placeholder' => 'Upiši email ili broj za WhatsApp', 'field_key' => 'calm_contact', 'required' => true]),
        stjepan_fcc_guide_block('result_calm_consent', 'checkbox_field', ['title' => $consent_text, 'text' => $privacy_contact_text, 'field_key' => 'contact_consent', 'required' => true]),
        stjepan_fcc_guide_block('result_calm_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('calm_submit', 'Pošalji mi uvodni video', ['action' => 'submit_stay', 'require_submit' => true, 'event_key' => 'submit_intro_request']),
                stjepan_fcc_guide_action('calm_to_products', 'Želim proizvode', ['target_step_id' => 'products_entry', 'style' => 'secondary', 'event_key' => 'select_calm_to_products']),
                stjepan_fcc_guide_action('calm_to_intro', 'Vrati me na početak', ['target_step_id' => '', 'style' => 'ghost', 'event_key' => 'select_calm_to_intro']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $result_calm_next_step = stjepan_fcc_guide_step('result_calm_next_step', 'conversion', 'demo', 'follow_up', 'Rezultat - Miran nastavak', 'Za osobe koje nisu spremne ili samo istražuju.', $result_calm_blocks, '', [
        'design_variant' => 'card',
        'block_mode' => 'contact_form',
        'surface' => [
            'progress_label' => 'Korak 3 od 3',
            'progress_current' => 3,
            'progress_total' => 3,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($result_calm_blocks, [
                'result_calm_hero' => [
                    'title' => 'Kreni mirnije, bez pritiska.',
                    'text' => 'Pogledaj proizvode, isprobaj AI vodiča i vrati se na poslovni dio kada ti bude realnije.',
                ],
            ]),
        ],
    ]);

    $contact_blocks = [
        stjepan_fcc_guide_block('contact_hero', 'headline', [
            'badge' => 'Kontakt',
            'title' => 'Pošalji mi poruku i pokazat ću ti najlogičniji sljedeći korak.',
            'text' => 'Najbrže je preko WhatsAppa. Napiši mi što te zanima: Start paket, proizvodi, FCC demo ili suradnja.',
            'title_size' => 48,
        ]),
        stjepan_fcc_guide_block('contact_actions', 'cta_group', [
            'buttons' => [
                stjepan_fcc_guide_action('contact_whatsapp', 'Pošalji WhatsApp poruku', ['action' => 'external_url', 'external_url' => $general_whatsapp_url, 'event_key' => 'click_general_whatsapp']),
                stjepan_fcc_guide_action('contact_check', 'Kratka provjera', ['target_step_id' => 'check', 'style' => 'secondary', 'event_key' => 'click_contact_to_check']),
                stjepan_fcc_guide_action('contact_start', 'Start paket', ['target_step_id' => 'start_package', 'style' => 'ghost', 'event_key' => 'click_contact_to_start']),
            ],
            'alignment' => 'center',
        ]),
    ];

    $contact_result = stjepan_fcc_guide_step('contact_result', 'trust', 'business', 'cta', 'Kontakt sa Stjepanom', 'Jednostavna kontakt stranica za sve koji žele odgovor.', $contact_blocks, '', [
        'design_variant' => 'decision',
        'block_mode' => 'message',
        'surface' => [
            'show_progress' => false,
            'ab_enabled' => true,
            'variant_b_blocks' => stjepan_fcc_guide_variant($contact_blocks, [
                'contact_hero' => [
                    'title' => 'Napiši mi gdje si sada i usmjerit ću te na pravi sljedeći korak.',
                ],
            ]),
        ],
    ]);

    return vip_funnel_normalize_studio_payload([
        'funnel' => [
            'name' => 'Stjepan Beloša - Forever Card Club vodič',
            'slug' => (string) ($options['slug'] ?? 'stjepan-forever-card-club-vodic'),
            'status' => 'active',
            'visibility_mode' => 'pro_live',
            'owner_mode' => 'private',
        ],
        'overview' => [
            'eyebrow' => 'Stjepan Beloša | Forever Card Club vodič',
            'headline' => 'Pokreni svoj FCC put uz moje osobno mentorstvo',
            'subheadline' => 'Osobni digitalni vodič za proizvode, preporuke i suradnju.',
            'primary_cta' => 'Pronađi moj sljedeći korak',
            'secondary_cta' => 'Razumij FCC sustav',
        ],
        'positioning' => [
            'for' => 'Za vlasnike malih biznisa, uslužne profesionalce, wellness ljude, početnike i osobe koje dolaze s društvenih mreža.',
            'problem' => 'Pažnja s videa se gubi ako osoba ne zna koji je njezin sljedeći korak.',
            'mechanism' => 'Osobni vodič segmentira posjetitelja prema spremnosti, interesu i najboljem sljedećem koraku.',
            'offer_promise' => 'Proizvodi, digitalni sustav i mentorstvo bez pritiska i bez nerealnih obećanja.',
            'why_now' => 'Publika već postoji; sada joj treba jasan i mjerljiv put.',
        ],
        'landing_page' => $landing_page,
        'paths' => [
            ['path_key' => 'business', 'title' => 'Suradnja i Start paket', 'description' => 'Put za osobe koje žele suradnju, Start paket ili Stjepanovo mentorstvo.', 'sort_order' => 1, 'is_enabled' => true],
            ['path_key' => 'products', 'title' => 'Proizvodi i 15% popusta', 'description' => 'Put za osobe koje žele proizvode, AI preporuku i webshop.', 'sort_order' => 2, 'is_enabled' => true],
            ['path_key' => 'demo', 'title' => 'FCC sustav i demo', 'description' => 'Put za osobe koje žele razumjeti sustav, AI vodič ili kontrolirani demo.', 'sort_order' => 3, 'is_enabled' => true],
        ],
        'board' => [
            ['key' => 'entry', 'steps' => [$business_owner, $business_intro, $fcc_system, $products_entry]],
            ['key' => 'segment', 'steps' => [$mentor_system, $check, $product_trust, $result_demo_request]],
            ['key' => 'experience', 'steps' => [$ai_advisor, $recommendation_practice, $start_package, $result_products]],
            ['key' => 'trust', 'steps' => [$result_start, $result_contact_hot, $contact_result]],
            ['key' => 'conversion', 'steps' => [$result_calm_next_step]],
        ],
        'products' => [
            'intro' => 'Proizvodni put koristi AI vodiča, product_offer blok i službeni shop/referral logiku.',
            'primary_offer_title' => 'AI preporuka i proizvodni početak',
            'primary_offer_text' => 'Osoba može krenuti od proizvoda, a kasnije prijeći prema suradnji.',
            'secondary_offer_title' => 'Most prema suradnji',
            'secondary_offer_text' => 'Kupac koji pokaže interes može prijeći na poslovni put.',
            'cta' => 'Pokreni AI vodiča',
        ],
        'proof' => [
            'mentor_intro' => $owner_name . ' je kreator FCC-a i mentor tima od 7.000+ članova.',
            'proof_1' => 'FCC daje jasan okvir za prezentaciju, preporuke i kontakt.',
            'proof_2' => 'AI vodič pomaže korisniku da se informira o proizvodima.',
            'proof_3' => 'Svaka grana ima jasan sljedeći korak.',
            'faq_intro' => 'Najčešće sumnje rješavaju se kroz sustav, provjeru, demo ili razgovor.',
        ],
        'follow_up' => [
            'cadence' => 'HOT_START: odmah; HOT_CONTACT: 0/1 dan; DEMO_INTEREST: nakon zahtjeva; PRODUCT_FIRST: 0/2/5 dana; CALM_FOLLOWUP: 0/5 dana',
            'message_1' => 'Bok, Stjepan ovdje. Vidio sam tvoj odabir i šaljem ti najbolji sljedeći korak.',
            'message_2' => 'Najveća razlika je krenuti sam ili uz sustav. FCC je napravljen da novi ljudi ne moraju sve izmišljati od nule.',
            'message_3' => 'Ako želiš, mogu ti u par minuta reći je li za tebe bolji Start paket, demo ili proizvodni put.',
        ],
        'demo' => [
            'micro_demo_label' => 'Kontrolirani FCC demo',
            'sandbox_label' => 'Demo uz Stjepanovo objašnjenje',
            'approval_note' => 'Demo nije otvoren javno; osoba prvo ostavlja kontakt i razlog.',
        ],
        'analytics' => [
            'primary_goal' => 'qualified_interaction',
            'ab_goal' => 'start_package_click',
        ],
        'defaults' => [
            'owner_user_id' => $owner_user_id,
            'contact_email' => $contact_email,
            'checkout_url' => vip_funnel_get_forever_business_referral_action_token(),
            'whatsapp_url' => $general_whatsapp_url,
            'calendar_url' => $general_whatsapp_url,
            'product_shop_url' => $product_shop_url,
            'privacy_url' => $privacy_url,
            'facebook_pixel_id' => $facebook_pixel_id,
            'hide_public_navbar' => true,
        ],
    ], $user);
}

function stjepan_fcc_guide_sql_quote(string $value): string {
    return "'" . database()->real_escape_string($value) . "'";
}

function stjepan_fcc_guide_card_settings(array $step): array {
    return [
        'summary' => (string) ($step['summary'] ?? ''),
        'helper_text' => (string) ($step['helper_text'] ?? ''),
        'cta' => (string) ($step['cta'] ?? ''),
        'next' => (string) ($step['next'] ?? ''),
        'next_step_id' => (string) ($step['next_step_id'] ?? ''),
        'status_key' => (string) ($step['status_key'] ?? 'core'),
        'media_url' => (string) ($step['media_url'] ?? ''),
        'answers' => vip_funnel_normalize_list_items($step['answers'] ?? []),
        'tags' => vip_funnel_normalize_list_items($step['tags'] ?? [], 8, 40),
        'owner_user_id' => (int) ($step['owner_user_id'] ?? 0),
        'visibility_key' => (string) ($step['visibility_key'] ?? 'all'),
        'analytics_label' => (string) ($step['analytics_label'] ?? ''),
        'design_variant' => (string) ($step['design_variant'] ?? 'card'),
        'preview_badge' => (string) ($step['preview_badge'] ?? ''),
        'preview_headline' => (string) ($step['preview_headline'] ?? ''),
        'preview_body' => (string) ($step['preview_body'] ?? ''),
        'block_mode' => (string) ($step['block_mode'] ?? 'message'),
        'background_color' => (string) ($step['background_color'] ?? '#121C28'),
        'text_color' => (string) ($step['text_color'] ?? '#F5FAFF'),
        'accent_color' => (string) ($step['accent_color'] ?? '#67D8C9'),
        'button_options' => vip_funnel_normalize_button_options($step['button_options'] ?? [], []),
        'step_id' => (string) ($step['id'] ?? ''),
        'page' => vip_funnel_normalize_page_surface_payload($step['page'] ?? [], (string) ($step['title'] ?? 'Stranica vodiča')),
    ];
}

function stjepan_fcc_guide_emit_sql(array $payload, string $target_email): string {
    $payload = vip_funnel_normalize_studio_payload($payload);
    $funnel = vip_funnel_to_array($payload['funnel'] ?? []);
    $settings = [
        'overview' => $payload['overview'],
        'positioning' => $payload['positioning'],
        'landing_page' => $payload['landing_page'],
        'products' => $payload['products'],
        'proof' => $payload['proof'],
        'follow_up' => $payload['follow_up'],
        'demo' => $payload['demo'],
        'analytics' => $payload['analytics'],
        'defaults' => $payload['defaults'],
    ];

    $lines = [];
    $lines[] = "-- Stjepan Beloša - Forever Card Club vodič";
    $lines[] = "-- Generated " . date('Y-m-d H:i:s');
    $lines[] = "START TRANSACTION;";
    $lines[] = "SET @target_user_id := (SELECT `user_id` FROM `users` WHERE `email` = " . stjepan_fcc_guide_sql_quote($target_email) . " LIMIT 1);";
    $lines[] = "SET @funnel_slug := " . stjepan_fcc_guide_sql_quote((string) ($funnel['slug'] ?? 'stjepan-forever-card-club-vodic')) . ";";
    $lines[] = "SET @existing_funnel_id := (SELECT `vip_funnel_id` FROM `vip_funnels` WHERE `user_id` = @target_user_id AND `slug` = @funnel_slug LIMIT 1);";
    $lines[] = "INSERT INTO `vip_funnels` (`user_id`, `name`, `slug`, `status`, `visibility_mode`, `owner_mode`, `settings`, `datetime`, `last_datetime`) SELECT @target_user_id, " . stjepan_fcc_guide_sql_quote((string) ($funnel['name'] ?? 'Stjepan Beloša - Forever Card Club vodič')) . ", @funnel_slug, " . stjepan_fcc_guide_sql_quote((string) ($funnel['status'] ?? 'active')) . ", " . stjepan_fcc_guide_sql_quote((string) ($funnel['visibility_mode'] ?? 'pro_live')) . ", " . stjepan_fcc_guide_sql_quote((string) ($funnel['owner_mode'] ?? 'private')) . ", " . stjepan_fcc_guide_sql_quote(vip_funnel_json_encode($settings)) . ", NOW(), NOW() WHERE @existing_funnel_id IS NULL;";
    $lines[] = "SET @funnel_id := IFNULL(@existing_funnel_id, LAST_INSERT_ID());";
    $lines[] = "UPDATE `vip_funnels` SET `name` = " . stjepan_fcc_guide_sql_quote((string) ($funnel['name'] ?? 'Stjepan Beloša - Forever Card Club vodič')) . ", `status` = " . stjepan_fcc_guide_sql_quote((string) ($funnel['status'] ?? 'active')) . ", `visibility_mode` = " . stjepan_fcc_guide_sql_quote((string) ($funnel['visibility_mode'] ?? 'pro_live')) . ", `owner_mode` = " . stjepan_fcc_guide_sql_quote((string) ($funnel['owner_mode'] ?? 'private')) . ", `settings` = " . stjepan_fcc_guide_sql_quote(vip_funnel_json_encode($settings)) . ", `last_datetime` = NOW() WHERE `vip_funnel_id` = @funnel_id AND `user_id` = @target_user_id;";
    $lines[] = "DELETE FROM `vip_funnel_edges` WHERE `vip_funnel_id` = @funnel_id;";
    $lines[] = "DELETE FROM `vip_funnel_cards` WHERE `vip_funnel_id` = @funnel_id;";
    $lines[] = "DELETE FROM `vip_funnel_paths` WHERE `vip_funnel_id` = @funnel_id;";

    $path_var_map = [];
    foreach(array_values($payload['paths'] ?? []) as $index => $path) {
        $path = vip_funnel_to_array($path);
        $path_key = (string) ($path['path_key'] ?? '');
        $var = '@path_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $path_key);
        $path_var_map[$path_key] = $var;
        $lines[] = "INSERT INTO `vip_funnel_paths` (`vip_funnel_id`, `path_key`, `title`, `description`, `sort_order`, `is_enabled`) VALUES (@funnel_id, " . stjepan_fcc_guide_sql_quote($path_key) . ", " . stjepan_fcc_guide_sql_quote((string) ($path['title'] ?? '')) . ", " . stjepan_fcc_guide_sql_quote((string) ($path['description'] ?? '')) . ", " . (int) ($index + 1) . ", " . (!empty($path['is_enabled']) ? 1 : 0) . ");";
        $lines[] = "SET {$var} := LAST_INSERT_ID();";
    }

    $card_var_map = [];
    $steps_by_id = [];
    foreach(array_values($payload['board'] ?? []) as $phase) {
        $phase = vip_funnel_to_array($phase);
        $phase_key = (string) ($phase['key'] ?? '');
        foreach(array_values($phase['steps'] ?? []) as $index => $step) {
            $step = vip_funnel_to_array($step);
            $step_id = (string) ($step['id'] ?? '');
            if($step_id === '') {
                continue;
            }
            $steps_by_id[$step_id] = $step;
            $var = '@card_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $step_id);
            $card_var_map[$step_id] = $var;
            $path_key = (string) ($step['path_key'] ?? '');
            $path_var = $path_var_map[$path_key] ?? 'NULL';
            $settings_json = vip_funnel_json_encode(stjepan_fcc_guide_card_settings($step));
            $lines[] = "INSERT INTO `vip_funnel_cards` (`vip_funnel_id`, `vip_funnel_path_id`, `phase_key`, `row_key`, `card_type`, `title`, `settings`, `sort_order`, `is_enabled`) VALUES (@funnel_id, {$path_var}, " . stjepan_fcc_guide_sql_quote($phase_key) . ", " . stjepan_fcc_guide_sql_quote((string) ($step['row_key'] ?? $path_key)) . ", " . stjepan_fcc_guide_sql_quote((string) ($step['card_type'] ?? 'offer')) . ", " . stjepan_fcc_guide_sql_quote((string) ($step['title'] ?? '')) . ", " . stjepan_fcc_guide_sql_quote($settings_json) . ", " . (int) ($index + 1) . ", 1);";
            $lines[] = "SET {$var} := LAST_INSERT_ID();";
        }
    }

    foreach(array_values($payload['board'] ?? []) as $phase_index => $phase) {
        $phase = vip_funnel_to_array($phase);
        foreach(array_values($phase['steps'] ?? []) as $step_index => $step) {
            $step = vip_funnel_to_array($step);
            $step_id = (string) ($step['id'] ?? '');
            $from_var = $card_var_map[$step_id] ?? '';
            if($from_var === '') {
                continue;
            }

            $default_next_step_id = trim((string) ($step['next_step_id'] ?? ''));
            if($default_next_step_id === '') {
                $default_next_step_id = vip_funnel_studio_get_auto_next_step_id($payload['board'] ?? [], (int) $phase_index, (int) $step_index, $step);
            }
            if($default_next_step_id !== '' && isset($card_var_map[$default_next_step_id])) {
                $lines[] = "INSERT INTO `vip_funnel_edges` (`vip_funnel_id`, `from_card_id`, `to_card_id`, `edge_type`, `condition_key`, `condition_value`) VALUES (@funnel_id, {$from_var}, {$card_var_map[$default_next_step_id]}, 'default', '', '');";
            }

            foreach(vip_funnel_extract_surface_actions(vip_funnel_to_array($step['page'] ?? [])) as $surface_action) {
                $target_step_id = (string) ($surface_action['target_step_id'] ?? '');
                if($target_step_id === '' || !isset($card_var_map[$target_step_id])) {
                    continue;
                }

                $lines[] = "INSERT INTO `vip_funnel_edges` (`vip_funnel_id`, `from_card_id`, `to_card_id`, `edge_type`, `condition_key`, `condition_value`) VALUES (@funnel_id, {$from_var}, {$card_var_map[$target_step_id]}, " . stjepan_fcc_guide_sql_quote((string) ($surface_action['edge_type'] ?? 'default')) . ", " . stjepan_fcc_guide_sql_quote((string) ($surface_action['condition_key'] ?? '')) . ", " . stjepan_fcc_guide_sql_quote((string) ($surface_action['condition_value'] ?? '')) . ");";
            }
        }
    }

    $lines[] = "COMMIT;";
    $lines[] = "SELECT @funnel_id AS vip_funnel_id, @funnel_slug AS slug, CONCAT('https://forevercard.club/vip-funnel/', @target_user_id, '/', @funnel_slug) AS public_url;";

    return implode("\n", $lines) . "\n";
}

$arguments = getopt('', [
    'email::',
    'slug::',
    'apply',
    'dry-run',
    'emit-sql',
    'product-shop-url::',
    'whatsapp-url::',
    'privacy-url::',
    'facebook-pixel-id::',
    'video-intro::',
    'video-forever-prilika::',
    'video-120-dnevni-plan::',
    'video-fcc-aplikacija::',
    'video-ai-savjetnik::',
    'video-preporuka-bez-pritiska::',
    'video-proizvodi-popust::',
    'video-proizvodi-temelj::',
]);

$target_email = trim((string) ($arguments['email'] ?? 'info@forevercard.club'));
$slug = trim((string) ($arguments['slug'] ?? 'stjepan-forever-card-club-vodic'));
$should_apply = isset($arguments['apply']) && !isset($arguments['dry-run']);
$emit_sql = isset($arguments['emit-sql']);

$user = db()
    ->where('email', $target_email)
    ->getOne('users', ['user_id', 'name', 'email', 'preferences', 'billing', 'referral_key', 'status']);

if(!$user) {
    fwrite(STDERR, "User not found for email: {$target_email}\n");
    exit(1);
}

vip_funnel_ensure_runtime_schema();

$payload = stjepan_fcc_guide_build_payload($user, [
    'slug' => $slug,
    'contact_email' => $target_email,
    'product_shop_url' => trim((string) ($arguments['product-shop-url'] ?? '')),
    'whatsapp_url' => trim((string) ($arguments['whatsapp-url'] ?? '')),
    'privacy_url' => trim((string) ($arguments['privacy-url'] ?? '')),
    'facebook_pixel_id' => trim((string) ($arguments['facebook-pixel-id'] ?? '238225369103006')),
    'video_intro' => trim((string) ($arguments['video-intro'] ?? '')),
    'video_forever_prilika' => trim((string) ($arguments['video-forever-prilika'] ?? '')),
    'video_120_dnevni_plan' => trim((string) ($arguments['video-120-dnevni-plan'] ?? '')),
    'video_fcc_aplikacija' => trim((string) ($arguments['video-fcc-aplikacija'] ?? '')),
    'video_ai_savjetnik' => trim((string) ($arguments['video-ai-savjetnik'] ?? '')),
    'video_preporuka_bez_pritiska' => trim((string) ($arguments['video-preporuka-bez-pritiska'] ?? '')),
    'video_proizvodi_popust' => trim((string) ($arguments['video-proizvodi-popust'] ?? '')),
    'video_proizvodi_temelj' => trim((string) ($arguments['video-proizvodi-temelj'] ?? '')),
]);

$validation_errors = vip_funnel_collect_payload_validation_errors($payload);
if(!empty($validation_errors)) {
    fwrite(STDERR, "Payload validation failed:\n" . vip_funnel_json_encode($validation_errors) . "\n");
    exit(1);
}

if($emit_sql) {
    echo stjepan_fcc_guide_emit_sql($payload, $target_email);
    exit(0);
}

$timestamp = date('Ymd_His');
$preview_path = ROOT_PATH . 'tmp/stjepan_forever_card_club_guide_payload_' . (int) $user->user_id . '_' . $timestamp . '.json';
if(!is_dir(dirname($preview_path))) {
    mkdir(dirname($preview_path), 0775, true);
}
file_put_contents($preview_path, vip_funnel_json_encode($payload));

$public_url = vip_funnel_get_public_funnel_url((int) $user->user_id, (string) ($payload['funnel']['slug'] ?? $slug));
$studio_url = SITE_URL . 'vip-funnel-studio';

echo "Target user: {$user->email} (#{$user->user_id})\n";
echo "Mode: " . ($should_apply ? 'APPLY' : 'DRY RUN') . "\n";
echo "Preview payload: {$preview_path}\n";
echo "Public URL: {$public_url}\n";
echo "Studio URL: {$studio_url}\n";
echo "Pages: " . (1 + array_sum(array_map(static fn($phase) => count((array) ($phase['steps'] ?? [])), (array) ($payload['board'] ?? [])))) . "\n";

if(!$should_apply) {
    echo "No database changes applied. Re-run with --apply to save locally, or --emit-sql to generate production SQL.\n";
    exit(0);
}

$existing = vip_funnel_studio_get_funnel_row_by_slug((int) $user->user_id, (string) ($payload['funnel']['slug'] ?? $slug));
if($existing) {
    $saved = vip_funnel_studio_save_to_database($user, $payload, (int) $existing->vip_funnel_id);
    $row = $saved ? vip_funnel_studio_get_funnel_row((int) $user->user_id, (int) $existing->vip_funnel_id) : null;
} else {
    $row = vip_funnel_studio_create_funnel_from_payload($user, $payload);
}

if(!$row) {
    fwrite(STDERR, "Failed to save Stjepan Forever Card Club guide funnel.\n");
    exit(1);
}

$public_url = vip_funnel_get_public_funnel_url((int) $user->user_id, (string) ($row->slug ?? $slug));
echo "Saved Stjepan Forever Card Club guide funnel successfully.\n";
echo "Funnel ID: " . (int) ($row->vip_funnel_id ?? 0) . "\n";
echo "Test it here: {$public_url}\n";
