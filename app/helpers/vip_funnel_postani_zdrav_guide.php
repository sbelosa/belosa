<?php

function postani_zdrav_guide_whatsapp_url(string $base_url = '', string $message = ''): string {
    $base_url = trim($base_url);
    $message = trim($message);

    if($base_url === '') {
        $base_url = 'https://wa.me/38761745720';
    }

    if($message === '') {
        return $base_url;
    }

    $separator = str_contains($base_url, '?') ? '&' : '?';

    return $base_url . $separator . 'text=' . rawurlencode($message);
}

function postani_zdrav_guide_normalize_whatsapp_url($user = null, string $fallback = 'https://wa.me/38761745720'): string {
    $owner_profile = vip_funnel_get_owner_contact_profile($user);
    $phone_digits = preg_replace('/\D+/', '', (string) ($owner_profile['phone'] ?? ''));

    if(str_starts_with($phone_digits, '00')) {
        $phone_digits = substr($phone_digits, 2);
    }

    if(str_starts_with($phone_digits, '0')) {
        $phone_digits = '387' . ltrim($phone_digits, '0');
    }

    return $phone_digits !== '' ? 'https://wa.me/' . $phone_digits : $fallback;
}

function vip_funnel_get_postani_zdrav_guide_payload($user = null, array $options = []): array {
    $options = vip_funnel_to_array($options);
    $owner_profile = vip_funnel_get_owner_contact_profile($user);
    $mentor_name = trim((string) ($owner_profile['name'] ?? ($user->name ?? 'Amila i Bojan Dizdarević')));
    $mentor_name = $mentor_name !== '' ? $mentor_name : 'Amila i Bojan Dizdarević';
    $mentor_email = trim((string) ($owner_profile['email'] ?? ($user->email ?? 'info@postanizdrav.com')));
    $mentor_email = filter_var($mentor_email, FILTER_VALIDATE_EMAIL) ? $mentor_email : 'info@postanizdrav.com';
    $contact_email = trim((string) ($options['contact_email'] ?? $mentor_email));
    $contact_email = filter_var($contact_email, FILTER_VALIDATE_EMAIL) ? $contact_email : $mentor_email;
    $owner_whatsapp_url = trim((string) ($options['whatsapp_url'] ?? '')) ?: postani_zdrav_guide_normalize_whatsapp_url($user);
    $product_shop_url = trim((string) ($options['product_shop_url'] ?? '')) ?: (trim((string) ($owner_profile['main_biolink_url'] ?? '')) ?: SITE_URL . 'blog');
    $privacy_url = trim((string) ($options['privacy_url'] ?? '')) ?: SITE_URL . 'page/privacy-policy';
    $start_package_url = vip_funnel_get_forever_business_referral_action_token();
    $facebook_pixel_id = vip_funnel_normalize_meta_pixel_id((string) ($options['facebook_pixel_id'] ?? ''));

    $whatsapp_general = postani_zdrav_guide_whatsapp_url($owner_whatsapp_url, 'Pozdrav Amila i Bojane, prošao/la sam Postani Zdrav vodič i zanima me koji je najbolji sljedeći korak za mene.');
    $whatsapp_interview = postani_zdrav_guide_whatsapp_url($owner_whatsapp_url, 'Pozdrav Amila i Bojane, zanima me saradnja kroz Postani Zdrav i želim kratak razgovor/intervju.');
    $whatsapp_webinar = postani_zdrav_guide_whatsapp_url($owner_whatsapp_url, 'Pozdrav Amila i Bojane, pogledao/la sam info video i želim razumjeti da li je ovaj Plan B za mene.');
    $whatsapp_products = postani_zdrav_guide_whatsapp_url($owner_whatsapp_url, 'Pozdrav Amila i Bojane, zanima me #aloechallenge i preporuka proizvoda za svakodnevnu wellness rutinu.');
    $whatsapp_after_start = postani_zdrav_guide_whatsapp_url($owner_whatsapp_url, 'Pozdrav Amila i Bojane, naručio/la sam Start paket i želim da me uvedete u prvi Postani Zdrav korak.');

    $video = static function(string $key) use ($options): string {
        return trim((string) ($options['video_' . $key] ?? ''));
    };

    $product_keys = [
        'aloe' => vip_funnel_find_catalog_translation_key(['forever aloe vera gel', 'aloe vera gel'], 'hr'),
        'c9' => vip_funnel_find_catalog_translation_key(['c9 vanilla', 'clean 9', 'c9'], 'hr'),
        'family' => vip_funnel_find_catalog_translation_key(['forever kids', 'aloe vera gel'], 'hr'),
        'women' => vip_funnel_find_catalog_translation_key(['forever active pro-b', 'arctic sea', 'aloe vera gel'], 'hr'),
        'discount' => vip_funnel_find_catalog_translation_key(['forever aloe vera gel', 'aloe vera gel'], 'hr'),
    ];
    $primary_product_key = $product_keys['aloe'] ?: ($product_keys['c9'] ?: ($product_keys['family'] ?: ($product_keys['women'] ?: ($product_keys['discount'] ?: ''))));

    $a = static function(string $id, string $label, array $payload = []): array {
        return stjepan_fcc_guide_action($id, $label, $payload);
    };

    $b = static function(string $id, string $type, array $payload = []): array {
        return stjepan_fcc_guide_block($id, $type, $payload);
    };

    $surface = static function(string $name, array $blocks, int $current = 1, int $total = 4, array $settings = []) {
        return stjepan_fcc_guide_surface($name, $blocks, array_merge([
            'background_color' => '#050806',
            'surface_color' => '#111A14',
            'text_color' => '#F7FFF8',
            'accent_color' => '#6EEB83',
            'max_width' => 'wide',
            'show_progress' => $current > 0,
            'progress_label' => $current > 0 ? ('Korak ' . $current . ' od ' . $total) : '',
            'progress_current' => $current,
            'progress_total' => $total,
            'ab_enabled' => false,
            'variant_b_blocks' => [],
            'variant_b_settings' => [],
        ], $settings));
    };

    $step = static function(string $id, string $phase, string $path, string $type, string $title, string $summary, array $blocks, string $next = '', int $current = 1, int $total = 4, array $settings = []) use ($surface) {
        return stjepan_fcc_guide_step($id, $phase, $path, $type, $title, $summary, $blocks, $next, array_merge($settings, [
            'surface' => $surface($title, $blocks, $current, $total, $settings['surface'] ?? []),
            'accent_color' => '#6EEB83',
            'background_color' => '#111A14',
        ]));
    };

    $business_compliance = 'Ovo nije zaposlenje ni garantirani prihod. Postani Zdrav sistem daje okvir, alate, proizvode i mentorstvo, a rezultati zavise od tvoje aktivnosti, vremena, dosljednosti i tržišta.';
    $product_compliance = 'AI vodič i ove informacije služe za opće informisanje o proizvodima i wellness rutini. Proizvodi nisu lijek i nisu zamjena za savjet ljekara.';
    $contact_consent = 'Pristajem da me Amila i Bojan Dizdarević kontaktiraju putem WhatsAppa ili telefona vezano uz moj odabrani smjer. Podaci se koriste samo za odgovor na moj upit. Privacy: ' . $privacy_url;

    $landing_blocks = [
        $b('landing_hero', 'headline', [
            'badge' => 'Postani Zdrav | Plan B vodič',
            'title' => 'Ako daješ maksimum za tuđe snove, vrijeme je da pogledaš svoj Plan B.',
            'text' => 'Kratki vodič za ljude koji žele više vremena, jasniji sistem i realan put prema dodatnom prihodu bez praznih obećanja.',
            'alignment' => 'center',
            'title_size' => 50,
            'text_size' => 19,
        ]),
        $b('landing_video', 'video', [
            'title' => 'Prvo pogledaj kratku poruku Amile i Bojana',
            'text' => 'Tema videa: zašto su pokrenuli Postani Zdrav, kome se obraćaju i kako ovaj vodič bira najbolji sljedeći korak.',
            'media_url' => $video('uvod'),
            'layout_width' => 'two_thirds',
        ]),
        $b('landing_image', 'image', [
            'badge' => 'Vizual brenda',
            'title' => 'Ovdje ide Postani Zdrav fotografija, lifestyle kadar ili #aloechallenge vizual',
            'text' => 'Sekcija je spremna za sliku koju Amila i Bojan naknadno ubace u Studio.',
            'layout_width' => 'third',
        ]),
        $b('landing_proof', 'proof_card', [
            'badge' => 'Zašto njih slušati',
            'title' => 'Oni ne pričaju teoriju. I sami prolaze put izlaska iz modela 08-17h.',
            'text' => 'Njihova publika već broji više desetina hiljada ljudi na mrežama. Sada cilj nije samo inspiracija, nego jasan put do razgovora, proizvoda ili saradnje.',
            'layout_width' => 'full',
        ]),
        $b('landing_choice', 'survey', [
            'title' => 'Šta ti je trenutno najbliže?',
            'text' => 'Odaberi iskreno. Vodič će te odvesti na najkraći sljedeći korak.',
            'alignment' => 'center',
            'options' => [
                $a('select_plan_b', 'Tražim izlaz iz 08-17h i želim Plan B', ['target_step_id' => 'plan_b_intro', 'value' => 'plan_b', 'style' => 'primary', 'event_key' => 'select_plan_b']),
                $a('select_webinar', 'Želim pogledati info video / webinar', ['target_step_id' => 'info_webinar', 'value' => 'webinar', 'style' => 'secondary', 'event_key' => 'select_webinar']),
                $a('select_challenge', 'Zanimaju me proizvodi i #aloechallenge', ['target_step_id' => 'aloe_challenge', 'value' => 'products', 'style' => 'secondary', 'event_key' => 'select_aloechallenge']),
                $a('select_check', 'Nisam siguran/na - usmjeri me', ['target_step_id' => 'check', 'value' => 'check', 'style' => 'secondary', 'event_key' => 'select_check']),
                $a('select_interview', 'Želim razgovor sa Amilom i Bojanom', ['action' => 'external_url', 'external_url' => $whatsapp_interview, 'value' => 'interview', 'style' => 'ghost', 'event_key' => 'click_interview_whatsapp']),
            ],
        ]),
    ];

    $landing_page = $surface('Postani Zdrav | Plan B vodič', $landing_blocks, 0, 0, [
        'show_progress' => false,
        'progress_current' => 0,
        'progress_total' => 0,
    ]);

    $plan_blocks = [
        $b('plan_hero', 'headline', [
            'badge' => 'Plan B bez iluzije',
            'title' => 'Najbolji radnik često na kraju dana nema ništa svoje.',
            'text' => 'Ako znaš kako izgleda davati maksimum u firmi, a opet ostati bez vremena, prostora i sigurnosti, ovaj korak objašnjava zašto sistem može biti bolji od još jednog obećanja.',
        ]),
        $b('plan_video', 'video', [
            'title' => 'Zašto Plan B mora čuvati vrijeme i porodicu',
            'text' => 'Tema videa: lično iskustvo, izlazna strategija, realna očekivanja i zašto mrežni marketing ima smisla samo kada postoji jasan sistem.',
            'media_url' => $video('plan_b'),
            'layout_width' => 'two_thirds',
        ]),
        $b('plan_card', 'proof_card', [
            'badge' => 'Njihov ugao',
            'title' => 'Ne traže ljude za lak rezultat, nego 5 ključnih saradnika koji razumiju vrijeme, brojeve i rad.',
            'text' => 'Cilj je napraviti malu jezgru ozbiljnih ljudi, ne masovni haos. Zato vodič ide prema info videu i WhatsApp intervjuu.',
            'layout_width' => 'third',
        ]),
        $b('plan_compliance', 'text', ['text' => $business_compliance, 'text_size' => 14]),
        $b('plan_actions', 'cta_group', [
            'text' => 'Ako ti ovo zvuči poznato, nastavi ovdje.',
            'alignment' => 'center',
            'buttons' => [
                $a('plan_to_webinar', 'Pogledaj info video / webinar', ['target_step_id' => 'info_webinar', 'event_key' => 'select_plan_to_webinar']),
                $a('plan_to_check', 'Želim kratku provjeru za saradnju', ['target_step_id' => 'check', 'style' => 'secondary', 'event_key' => 'select_plan_to_check']),
                $a('plan_to_interview', 'Želim WhatsApp razgovor', ['action' => 'external_url', 'external_url' => $whatsapp_interview, 'style' => 'ghost', 'event_key' => 'click_plan_whatsapp']),
            ],
        ]),
    ];

    $webinar_blocks = [
        $b('webinar_hero', 'headline', [
            'badge' => 'Info video / webinar',
            'title' => 'Prvo razumij sistem, pa odluči da li ima smisla razgovarati.',
            'text' => 'Ovo je glavni filter za ozbiljne ljude: proizvodi, FCC aplikacija, AI alati, mentorstvo i način rada bez agresivne prodaje.',
        ]),
        $b('webinar_video', 'video', [
            'title' => 'Kako funkcioniše Postani Zdrav sistem',
            'text' => 'Tema videa: Plan B, Forever proizvodi, FCC aplikacija, AI pomoćnik, preporuka bez pritiska i zašto se nakon videa ide na kratak intervju.',
            'media_url' => $video('webinar'),
        ]),
        $b('webinar_points', 'proof_card', [
            'badge' => 'Šta trebaš zaključiti',
            'title' => 'Da li si osoba koja želi učiti, raditi i graditi nešto svoje bez bježanja od realnosti?',
            'text' => 'Ako je odgovor da, sljedeći korak je razgovor. Ako još nije trenutak, možeš krenuti od proizvoda i #aloechallenge-a.',
            'layout_width' => 'half',
        ]),
        $b('webinar_system', 'proof_card', [
            'badge' => 'Sistem, ne improvizacija',
            'title' => 'FCC aplikacija i AI alati služe da preporuka bude jasnija i lakša.',
            'text' => 'Ne moraš prvi dan znati sve proizvode i sve odgovore. Sistem pomaže da osoba dobije informacije i da razgovor bude konkretniji.',
            'layout_width' => 'half',
        ]),
        $b('webinar_compliance', 'text', ['text' => $business_compliance, 'text_size' => 14]),
        $b('webinar_actions', 'cta_group', [
            'text' => 'Nakon videa izaberi najiskreniji nastavak.',
            'alignment' => 'center',
            'buttons' => [
                $a('webinar_interview', 'Želim intervju za saradnju', ['action' => 'external_url', 'external_url' => $whatsapp_webinar, 'event_key' => 'click_webinar_whatsapp', 'sticky' => true]),
                $a('webinar_system_detail', 'Pokaži mi kako sistem pomaže', ['target_step_id' => 'fcc_system', 'style' => 'secondary', 'event_key' => 'select_webinar_system']),
                $a('webinar_products', 'Prvo me zanimaju proizvodi', ['target_step_id' => 'aloe_challenge', 'style' => 'ghost', 'event_key' => 'select_webinar_products']),
            ],
        ]),
    ];

    $system_blocks = [
        $b('system_hero', 'headline', [
            'badge' => 'FCC + AI alati',
            'title' => 'Sistem čuva vrijeme jer ne objašnjavaš sve iz početka svaki put.',
            'text' => 'FCC aplikacija, AI vodič i jasni linkovi pomažu da osoba sama vidi proizvode, priču, kontakt i sljedeći korak prije razgovora.',
        ]),
        $b('system_video', 'video', [
            'title' => 'Kako alat izgleda u praksi',
            'text' => 'Tema videa: aplikacija, linkovi, AI preporuka, proizvodi, kontakt i kako se koristi u online i offline preporuci.',
            'media_url' => $video('sistem'),
            'layout_width' => 'two_thirds',
        ]),
        $b('system_card', 'proof_card', [
            'badge' => 'Bez agresivne prodaje',
            'title' => 'Cilj nije pritiskati ljude, nego otvoriti razgovor s jasnim informacijama.',
            'text' => 'To je posebno važno za ljude koji imaju porodicu, posao i ne žele ulaziti u haotičan model rada.',
            'layout_width' => 'third',
        ]),
        $b('system_actions', 'cta_group', [
            'text' => 'Ako želiš ovakav sistem, nastavi prema provjeri ili razgovoru.',
            'alignment' => 'center',
            'buttons' => [
                $a('system_check', 'Provjeri da li si kandidat za saradnju', ['target_step_id' => 'check', 'event_key' => 'select_system_check']),
                $a('system_start', 'Spreman/na sam za Start paket', ['target_step_id' => 'start_package', 'style' => 'secondary', 'event_key' => 'select_system_start']),
                $a('system_interview', 'Razgovor sa Amilom i Bojanom', ['action' => 'external_url', 'external_url' => $whatsapp_interview, 'style' => 'ghost', 'event_key' => 'click_system_whatsapp']),
            ],
        ]),
        $b('system_note', 'text', ['text' => $business_compliance, 'text_size' => 14]),
    ];

    $challenge_blocks = [
        $b('challenge_hero', 'headline', [
            'badge' => '#aloechallenge',
            'title' => 'Ako još nisi za saradnju, kreni od proizvoda i ličnog iskustva.',
            'text' => '#aloechallenge je jednostavan proizvodni ulaz za ljude koji žele bolje navike, aloe rutinu i jasniju preporuku bez medicinskih obećanja.',
        ]),
        $b('challenge_video', 'video', [
            'title' => 'Kako izgleda #aloechallenge do kraja juna',
            'text' => 'Tema videa: za koga je izazov, kako se uključiti, kako se koristi aloe rutina i zašto je lično iskustvo najbolji početak.',
            'media_url' => $video('aloechallenge'),
            'layout_width' => 'two_thirds',
        ]),
        $b('challenge_ai', 'ai_product_advisor', [
            'badge' => 'AI vodič za proizvode',
            'title' => 'Pitaj AI vodiča koji proizvodni smjer ima smisla za tebe.',
            'text' => 'Napiši cilj u općem wellness smislu: aloe rutina, C9 početak, porodična rutina, ženska rutina ili naručivanje bez registracije.',
            'ai_button_label' => 'Pokreni AI vodiča',
            'ai_launcher_label' => 'Postani Zdrav AI',
            'ai_intro_label' => 'Tvoj proizvodni vodič',
            'ai_input_placeholder' => 'Napiši šta želiš podržati u svojoj rutini...',
            'layout_width' => 'third',
            'event_key' => 'open_ai_aloechallenge',
        ]),
        $b('challenge_choice', 'survey', [
            'title' => 'Koji proizvodni smjer te zanima?',
            'text' => 'Odabir vodi na preporuku i sljedeći korak.',
            'alignment' => 'center',
            'options' => [
                $a('challenge_aloe', 'Aloe rutina / #aloechallenge', ['target_step_id' => 'product_recommendation', 'value' => 'aloe', 'event_key' => 'select_product_aloe']),
                $a('challenge_c9', 'C9 paket / početni reset rutine', ['target_step_id' => 'product_recommendation', 'value' => 'c9', 'style' => 'secondary', 'event_key' => 'select_product_c9']),
                $a('challenge_family', 'Porodična wellness rutina', ['target_step_id' => 'product_recommendation', 'value' => 'family', 'style' => 'secondary', 'event_key' => 'select_product_family']),
                $a('challenge_women', 'Ženska svakodnevna rutina', ['target_step_id' => 'product_recommendation', 'value' => 'women', 'style' => 'secondary', 'event_key' => 'select_product_women']),
                $a('challenge_discount', 'Želim naručiti bez registracije', ['action' => 'external_url', 'external_url' => $product_shop_url, 'value' => 'discount', 'style' => 'ghost', 'event_key' => 'click_product_discount']),
            ],
        ]),
        $b('challenge_note', 'text', ['text' => $product_compliance, 'text_size' => 14]),
    ];

    $product_blocks = [
        $b('product_hero', 'headline', [
            'badge' => 'Preporuka proizvoda',
            'title' => 'Kreni od proizvoda koji najviše odgovara tvom trenutnom cilju.',
            'text' => 'Ovaj put je za ljude koji žele prvo kupiti, probati i izgraditi lično iskustvo prije razgovora o saradnji.',
        ]),
        $b('product_offer', 'product_offer', [
            'badge' => 'Preporuka prema odabiru',
            'title' => 'Prvi proizvodni korak',
            'text' => 'Preporuka se prilagođava izboru iz prethodnog koraka.',
            'product_source_mode' => 'dynamic',
            'product_translation_key' => $primary_product_key,
            'product_primary_mode' => 'blog_guide',
            'product_primary_cta_text' => 'Pogledaj vodič proizvoda',
            'product_secondary_enabled' => true,
            'product_secondary_mode' => 'direct_shop',
            'product_secondary_cta_text' => 'Otvori službeni shop bez registracije',
            'product_mappings' => [
                ['id' => 'map_aloe', 'match_value' => 'aloe', 'product_translation_key' => $product_keys['aloe'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_c9', 'match_value' => 'c9', 'product_translation_key' => $product_keys['c9'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_family', 'match_value' => 'family', 'product_translation_key' => $product_keys['family'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_women', 'match_value' => 'women', 'product_translation_key' => $product_keys['women'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_discount', 'match_value' => 'discount', 'product_translation_key' => $product_keys['discount'] ?: $primary_product_key, 'product_blog_post_id' => 0],
            ],
            'layout_width' => 'two_thirds',
            'event_key' => 'click_product_offer',
        ]),
        $b('product_next', 'proof_card', [
            'badge' => 'Ako želiš preciznije',
            'title' => 'Najbolje je da pošalješ poruku i kažeš šta želiš postići.',
            'text' => 'Amila i Bojan mogu te usmjeriti prema #aloechallenge-u, naručivanju proizvoda bez registracije ili razgovoru o saradnji.',
            'layout_width' => 'third',
        ]),
        $b('product_actions', 'cta_group', [
            'text' => 'Odaberi nastavak.',
            'alignment' => 'center',
            'buttons' => [
                $a('product_whatsapp', 'Javi se za #aloechallenge', ['action' => 'external_url', 'external_url' => $whatsapp_products, 'event_key' => 'click_product_whatsapp']),
                $a('product_shop', 'Otvori Postani Zdrav aplikaciju / shop', ['action' => 'external_url', 'external_url' => $product_shop_url, 'style' => 'secondary', 'event_key' => 'click_product_shop']),
                $a('product_to_plan', 'Zanima me i Plan B', ['target_step_id' => 'plan_b_intro', 'style' => 'ghost', 'event_key' => 'select_product_plan']),
            ],
        ]),
        $b('product_compliance', 'text', ['text' => $product_compliance, 'text_size' => 14]),
    ];

    $filter_blocks = [
        $b('filter_hero', 'headline', [
            'badge' => 'Realni filter',
            'title' => 'Ovo nije za svakoga. I baš zato može biti dobro za prave ljude.',
            'text' => 'Ako tražiš brzu zaradu bez rada, ovo nije pravi put. Ako želiš učiti, koristiti sistem i graditi oko porodice i vremena, vrijedi proći provjeru.',
        ]),
        $b('filter_cards_a', 'proof_card', [
            'badge' => 'Dobar kandidat',
            'title' => 'Osoba koja razumije vrijeme, brojeve i dugoročan rad.',
            'text' => 'Neko ko ne želi ostati samo broj na platnom spisku, ali ne traži ni magično rješenje preko noći.',
            'layout_width' => 'half',
        ]),
        $b('filter_cards_b', 'proof_card', [
            'badge' => 'Nije dobar fit',
            'title' => 'Osoba koja želi rezultat bez komunikacije, učenja i dosljednosti.',
            'text' => 'Postani Zdrav sistem olakšava preporuku, ali ne zamjenjuje čovjeka, komunikaciju i dosljednost.',
            'layout_width' => 'half',
        ]),
        $b('filter_actions', 'cta_group', [
            'text' => 'Ako se prepoznaješ u prvom opisu, nastavi.',
            'alignment' => 'center',
            'buttons' => [
                $a('filter_to_check', 'Želim kratku provjeru', ['target_step_id' => 'check', 'event_key' => 'select_filter_check']),
                $a('filter_to_interview', 'Želim direktan intervju', ['action' => 'external_url', 'external_url' => $whatsapp_interview, 'style' => 'secondary', 'event_key' => 'click_filter_whatsapp']),
                $a('filter_to_products', 'Prvo proizvodi', ['target_step_id' => 'aloe_challenge', 'style' => 'ghost', 'event_key' => 'select_filter_products']),
            ],
        ]),
        $b('filter_note', 'text', ['text' => $business_compliance, 'text_size' => 14]),
    ];

    $check_blocks = [
        $b('check_hero', 'headline', [
            'badge' => 'Kratka provjera',
            'title' => 'Odgovori iskreno i vodič će te usmjeriti na najbolji sljedeći korak.',
            'text' => 'Cilj nije odbiti ljude, nego razlikovati: saradnja sada, proizvodi prvo, info video, Start paket ili mirniji nastavak.',
        ]),
        $b('check_goal', 'radio_survey', [
            'title' => 'Šta trenutno najviše želiš?',
            'text' => 'Ovaj odgovor određuje preporučeni smjer.',
            'required' => true,
            'route_on_submit' => true,
            'options' => [
                ['id' => 'goal_key_partner', 'label' => 'Želim ozbiljno razmotriti saradnju', 'hint' => 'Tražim Plan B i spreman/na sam za razgovor.', 'value' => 'collaboration', 'target_step_id' => 'result_interview'],
                ['id' => 'goal_webinar', 'label' => 'Prvo želim info video / webinar', 'hint' => 'Trebam razumjeti sistem prije razgovora.', 'value' => 'webinar', 'target_step_id' => 'info_webinar'],
                ['id' => 'goal_products', 'label' => 'Prvo me zanimaju proizvodi', 'hint' => 'Želim #aloechallenge, naručivanje bez registracije ili preporuku.', 'value' => 'products', 'target_step_id' => 'result_products'],
                ['id' => 'goal_start', 'label' => 'Spreman/na sam za Start paket', 'hint' => 'Želim konkretan poslovni početak.', 'value' => 'start', 'target_step_id' => 'start_package'],
                ['id' => 'goal_calm', 'label' => 'Samo istražujem', 'hint' => 'Želim mirniji sljedeći korak bez pritiska.', 'value' => 'calm', 'target_step_id' => 'result_calm'],
            ],
        ]),
        $b('check_situation', 'radio_survey', [
            'title' => 'Koji opis ti je najbliži?',
            'text' => 'Odgovori realno, ne idealno.',
            'required' => false,
            'options' => [
                ['id' => 'sit_8_17', 'label' => 'Radim 08-17h i tražim izlaznu strategiju', 'hint' => 'Vrijeme i sigurnost su mi važni.'],
                ['id' => 'sit_mother', 'label' => 'Želim karijeru, ali i prisutnost u porodici', 'hint' => 'Ne želim posao koji mi uzima cijeli život.'],
                ['id' => 'sit_products', 'label' => 'Prvo želim proizvode i lično iskustvo', 'hint' => 'Prodaja ili saradnja mogu doći kasnije.'],
                ['id' => 'sit_network', 'label' => 'Imam kontakte i želim sistem preporuke', 'hint' => 'Treba mi organizovan način rada.'],
            ],
        ]),
        $b('check_time', 'radio_survey', [
            'title' => 'Koliko realno vremena možeš odvojiti sedmično?',
            'text' => 'Bolje je krenuti realno nego preambiciozno i stati.',
            'required' => false,
            'options' => [
                ['id' => 'time_1_3', 'label' => '1-3 sata', 'hint' => 'Miran početak i proizvodi.'],
                ['id' => 'time_4_7', 'label' => '4-7 sati', 'hint' => 'Dovoljno za ozbiljan start i prve kontakte.'],
                ['id' => 'time_8_plus', 'label' => '8+ sati', 'hint' => 'Brži ritam, razgovori i ozbiljnija aktivnost.'],
                ['id' => 'time_later', 'label' => 'Trenutno nemam prostor', 'hint' => 'Bolje je ostati u kontaktu nego ulaziti pod pritiskom.'],
            ],
        ]),
        $b('check_start', 'radio_survey', [
            'title' => 'Da li ti je Start paket realan ako zaključiš da je ovo za tebe?',
            'text' => 'Ovo nije pritisak, nego da te ne vodimo na pogrešan sljedeći korak.',
            'required' => false,
            'options' => [
                ['id' => 'start_now', 'label' => 'Da, mogu krenuti odmah', 'hint' => 'Ako je smjer jasan, mogu napraviti narudžbu i start.'],
                ['id' => 'start_call', 'label' => 'Da, ali želim razgovor prije toga', 'hint' => 'Trebam potvrdu i par odgovora.'],
                ['id' => 'start_products', 'label' => 'Prvo želim proizvode', 'hint' => 'Želim lično iskustvo prije poslovnog koraka.'],
                ['id' => 'start_not_now', 'label' => 'Ne sada', 'hint' => 'Trenutno nije pravi trenutak za Start paket.'],
            ],
        ]),
        $b('check_name', 'full_name_field', ['title' => 'Ime i prezime', 'placeholder' => 'Upiši ime i prezime', 'required' => true, 'layout_width' => 'half']),
        $b('check_phone', 'phone_field', ['title' => 'WhatsApp / telefon', 'placeholder' => 'Upiši broj na koji te možemo brzo kontaktirati', 'required' => true, 'layout_width' => 'half']),
        $b('check_email', 'email_field', ['title' => 'Email - opcionalno', 'placeholder' => 'Upiši email ako želiš dodatne informacije', 'required' => false, 'layout_width' => 'half']),
        $b('check_contact_time', 'text_field', ['title' => 'Najbolje vrijeme za kontakt', 'placeholder' => 'npr. danas poslije 18h, sutra ujutro...', 'field_key' => 'contact_time', 'required' => false, 'layout_width' => 'half']),
        $b('check_consent', 'checkbox_field', ['title' => 'Privola za kontakt', 'text' => $contact_consent, 'field_key' => 'contact_consent', 'required' => true]),
        $b('check_submit', 'cta_group', [
            'text' => 'Pošalji odgovore i dobićeš preporučeni sljedeći korak.',
            'alignment' => 'center',
            'buttons' => [
                $a('check_submit_btn', 'Pošalji i pokaži mi sljedeći korak', ['action' => 'submit_next', 'target_step_id' => 'result_interview', 'require_submit' => true, 'event_key' => 'submit_postani_zdrav_check']),
            ],
        ]),
    ];

    $start_blocks = [
        $b('start_hero', 'headline', [
            'badge' => 'Start paket',
            'title' => 'Ako želiš krenuti kao saradnik, Start paket je konkretan prvi korak.',
            'text' => 'Ovaj korak ima smisla kada želiš povezati proizvode, FCC sistem, mentorstvo i realan početak preporuke.',
        ]),
        $b('start_video', 'video', [
            'title' => 'Prije narudžbe pogledaj šta se dešava nakon starta',
            'text' => 'Tema videa: šta osoba dobija, kako izgleda prvi razgovor i kako se ulazi u Postani Zdrav sistem rada.',
            'media_url' => $video('start'),
        ]),
        $b('start_get', 'proof_card', [
            'badge' => 'Dobijaš',
            'title' => 'Proizvodni početak, upis, sistem i smjer za prve kontakte.',
            'text' => 'Nakon narudžbe javi se Amili i Bojanu da te uvedu u prvi korak i dogovore najjednostavniji početni plan.',
            'layout_width' => 'half',
        ]),
        $b('start_support', 'proof_card', [
            'badge' => 'Važno',
            'title' => 'Start paket nije obećanje zarade, nego ulazak u praktičan početak.',
            'text' => 'Sistem pomaže, ali rezultat zavisi od aktivnosti, komunikacije, vremena i dosljednosti.',
            'layout_width' => 'half',
        ]),
        $b('start_compliance', 'text', ['text' => $business_compliance, 'text_size' => 14]),
        $b('start_actions', 'cta_group', [
            'text' => 'Odaberi kako želiš napraviti sljedeći korak.',
            'alignment' => 'center',
            'buttons' => [
                $a('start_order', 'Naruči Start paket', ['action' => 'external_url', 'external_url' => $start_package_url, 'event_key' => 'click_start_order', 'hint' => 'Vodi na službenu Forever Living stranicu s preporukom Amile i Bojana i automatskim odabirom zemlje.', 'sticky' => true]),
                $a('start_whatsapp', 'Javljam se nakon narudžbe', ['action' => 'external_url', 'external_url' => $whatsapp_after_start, 'style' => 'secondary', 'event_key' => 'click_start_whatsapp']),
                $a('start_unsure', 'Nisam siguran/na - želim razgovor', ['action' => 'external_url', 'external_url' => $whatsapp_interview, 'style' => 'ghost', 'event_key' => 'click_start_unsure_whatsapp']),
            ],
        ]),
    ];

    $result_interview_blocks = [
        $b('result_interview_hero', 'headline', [
            'badge' => 'Preporučeni sljedeći korak',
            'title' => 'Tvoj najbolji sljedeći korak je kratak razgovor sa Amilom i Bojanom.',
            'text' => 'Ako te privlači Plan B, ali želiš razumjeti gdje se uklapaš, WhatsApp razgovor je najbrži put do jasne odluke.',
        ]),
        $b('result_interview_actions', 'cta_group', [
            'text' => 'Pošalji poruku dok je ideja svježa.',
            'alignment' => 'center',
            'buttons' => [
                $a('result_interview_whatsapp', 'Pošalji WhatsApp poruku', ['action' => 'external_url', 'external_url' => $whatsapp_interview, 'event_key' => 'click_result_interview_whatsapp']),
                $a('result_interview_start', 'Ipak želim Start paket', ['target_step_id' => 'start_package', 'style' => 'secondary', 'event_key' => 'select_result_interview_start']),
                $a('result_interview_webinar', 'Vraćam se na info video', ['target_step_id' => 'info_webinar', 'style' => 'ghost', 'event_key' => 'select_result_interview_webinar']),
            ],
        ]),
        $b('result_interview_note', 'text', ['text' => $business_compliance, 'text_size' => 14]),
    ];

    $result_products_blocks = [
        $b('result_products_hero', 'headline', [
            'badge' => 'Preporučeni sljedeći korak',
            'title' => 'Tvoj najbolji prvi korak su proizvodi i #aloechallenge.',
            'text' => 'Kreni od ličnog iskustva i rutine. Ako ti sistem kasnije bude imao smisla, možeš razgovarati o saradnji.',
        ]),
        $b('result_products_ai', 'ai_product_advisor', [
            'title' => 'Pitaj AI vodiča za proizvodni smjer',
            'text' => 'AI vodič pomaže istražiti proizvode za opću wellness rutinu, bez medicinskih tvrdnji.',
            'ai_button_label' => 'Pokreni AI vodiča',
            'layout_width' => 'half',
            'event_key' => 'open_ai_result_products',
        ]),
        $b('result_products_actions', 'cta_group', [
            'layout_width' => 'half',
            'buttons' => [
                $a('result_products_challenge', 'Javi se za #aloechallenge', ['action' => 'external_url', 'external_url' => $whatsapp_products, 'event_key' => 'click_result_products_whatsapp']),
                $a('result_products_shop', 'Otvori Postani Zdrav aplikaciju / shop', ['action' => 'external_url', 'external_url' => $product_shop_url, 'style' => 'secondary', 'event_key' => 'click_result_products_shop']),
                $a('result_products_plan', 'Zanima me i Plan B', ['target_step_id' => 'plan_b_intro', 'style' => 'ghost', 'event_key' => 'select_result_products_plan']),
            ],
        ]),
        $b('result_products_note', 'text', ['text' => $product_compliance, 'text_size' => 14]),
    ];

    $result_calm_blocks = [
        $b('result_calm_hero', 'headline', [
            'badge' => 'Miran nastavak',
            'title' => 'Ne moraš odlučiti danas. Bitno je da ne ostaneš u magli.',
            'text' => 'Ako sada nije trenutak za Start paket ili intervju, kreni od info videa ili proizvoda. Put prema razgovoru ostaje otvoren.',
        ]),
        $b('result_calm_actions', 'cta_group', [
            'text' => 'Odaberi najlakši nastavak.',
            'alignment' => 'center',
            'buttons' => [
                $a('result_calm_webinar', 'Pogledaj info video', ['target_step_id' => 'info_webinar', 'event_key' => 'select_calm_webinar']),
                $a('result_calm_products', 'Kreni od #aloechallenge-a', ['target_step_id' => 'aloe_challenge', 'style' => 'secondary', 'event_key' => 'select_calm_products']),
                $a('result_calm_whatsapp', 'Pošalji pitanje na WhatsApp', ['action' => 'external_url', 'external_url' => $whatsapp_general, 'style' => 'ghost', 'event_key' => 'click_calm_whatsapp']),
            ],
        ]),
    ];

    $board = [
        [
            'key' => 'entry',
            'steps' => [
                $step('plan_b_intro', 'entry', 'collaboration', 'segment', 'Plan B uvod', 'Direktan uvod za ljude u modelu 08-17h.', $plan_blocks, '', 1, 4),
                $step('info_webinar', 'entry', 'webinar', 'video', 'Info video / webinar', 'Glavni edukativni filter prije WhatsApp intervjua.', $webinar_blocks, '', 1, 4),
                $step('aloe_challenge', 'entry', 'products', 'offer', '#aloechallenge', 'Produktni ulaz za izazov i lično iskustvo.', $challenge_blocks, '', 1, 3),
                $step('fcc_system', 'entry', 'collaboration', 'proof', 'Sistem i alati', 'FCC aplikacija, AI i preporuka bez pritiska.', $system_blocks, '', 2, 4),
            ],
        ],
        [
            'key' => 'experience',
            'steps' => [
                $step('product_recommendation', 'experience', 'products', 'offer', 'Preporuka proizvoda', 'Dinamička preporuka po proizvodnom interesu.', $product_blocks, '', 2, 3),
                $step('reality_filter', 'experience', 'collaboration', 'segment', 'Realni filter', 'Za koga sistem jeste, a za koga nije.', $filter_blocks, '', 2, 4),
                $step('check', 'experience', 'collaboration', 'survey', 'Kratka provjera', 'Survey i kontakt forma za segmentaciju.', $check_blocks, '', 2, 4, ['block_mode' => 'contact_form']),
            ],
        ],
        [
            'key' => 'conversion',
            'steps' => [
                $step('start_package', 'conversion', 'collaboration', 'cta', 'Start paket', 'Službeni Forever start sa preporukom Amile i Bojana.', $start_blocks, '', 3, 4),
                $step('result_interview', 'conversion', 'collaboration', 'cta', 'Rezultat - intervju', 'Završni smjer prema WhatsApp razgovoru.', $result_interview_blocks, '', 4, 4),
                $step('result_products', 'conversion', 'products', 'cta', 'Rezultat - proizvodi', 'Završni smjer za #aloechallenge, AI i shop.', $result_products_blocks, '', 3, 3),
                $step('result_calm', 'conversion', 'webinar', 'cta', 'Rezultat - miran nastavak', 'Zadržava neodlučne bez pritiska.', $result_calm_blocks, '', 3, 3),
            ],
        ],
    ];

    return vip_funnel_normalize_studio_payload([
        'funnel' => [
            'name' => 'Amila i Bojan Dizdarević - Postani Zdrav Plan B vodič',
            'slug' => 'postani-zdrav-plan-b-vodic',
            'status' => 'active',
            'visibility_mode' => 'pro_live',
            'owner_mode' => 'private',
        ],
        'overview' => [
            'eyebrow' => 'Postani Zdrav | Plan B',
            'headline' => 'Plan B, proizvodi i sistem preporuke bez praznih obećanja',
            'subheadline' => 'Vodič za ljude koji daju maksimum u firmi, žele više vremena i traže realan put prema razgovoru, proizvodima ili saradnji.',
            'primary_cta' => 'Odaberi svoj smjer',
            'secondary_cta' => 'Javi se Amili i Bojanu',
        ],
        'positioning' => [
            'for' => 'Za zaposlene ljude, roditelje i žene koje žele karijeru, porodicu i dodatni poslovni smjer bez agresivne prodaje.',
            'problem' => 'Publika ima pažnju i povjerenje, ali treba jasan put od inspiracije prema info videu, proizvodima, WhatsApp intervjuu ili Start paketu.',
            'mechanism' => 'Vodič segmentira posjetitelja kroz Plan B, info video, #aloechallenge, kratku provjeru i jasne završne CTA korake.',
            'offer_promise' => 'Direktan, jednostavan i realan sistem koji čuva vrijeme i izbjegava prazna obećanja.',
            'why_now' => 'Cilj je pronaći 5 ključnih saradnika i istovremeno iskoristiti #aloechallenge do kraja juna.',
        ],
        'landing_page' => $landing_page,
        'paths' => [
            ['path_key' => 'collaboration', 'title' => 'Plan B i saradnja', 'description' => 'Glavni regrutacijski put prema info videu, provjeri i WhatsApp intervjuu.', 'sort_order' => 1, 'is_enabled' => true],
            ['path_key' => 'webinar', 'title' => 'Info video / webinar', 'description' => 'Edukativni filter za ljude koji žele razumjeti sistem prije razgovora.', 'sort_order' => 2, 'is_enabled' => true],
            ['path_key' => 'products', 'title' => '#aloechallenge i proizvodi', 'description' => 'Produktni put prema AI preporuci, shopu i ličnom iskustvu.', 'sort_order' => 3, 'is_enabled' => true],
        ],
        'board' => $board,
        'products' => [
            'primary_product_key' => $primary_product_key,
            'preferred_language_code' => 'hr',
            'product_shop_url' => $product_shop_url,
        ],
        'proof' => [
            'mentor' => $mentor_name,
            'audience' => 'FB 21k, IG 11k, TikTok 3.5k i YouTube kanal u razvoju.',
            'position' => 'Postani Zdrav govori ljudima koji žele izaći iz kaveza 08-17h i graditi nešto svoje uz sistem.',
        ],
        'follow_up' => [
            'primary_channel' => 'whatsapp',
            'hot_action' => 'Odmah ponuditi kratak intervju za saradnju i pitati šta osoba želi promijeniti u modelu rada/vremena.',
            'webinar_action' => 'Nakon info videa poslati pitanje: Šta ti je najviše imalo smisla i želiš li razgovor?',
            'product_action' => 'Za #aloechallenge poslati proizvodni smjer, upute i pitati želi li ličnu preporuku.',
            'start_action' => 'Nakon Start paketa dogovoriti prvi razgovor i osnovni plan rada.',
        ],
        'demo' => [
            'enabled' => false,
            'note' => 'Ovaj personalizirani vodič ne otvara FCC demo pristup. Sistem se objašnjava kroz info video i WhatsApp razgovor.',
        ],
        'analytics' => [
            'events' => ['view_landing', 'select_plan_b', 'select_webinar', 'click_interview_whatsapp', 'submit_postani_zdrav_check', 'click_start_order', 'open_ai_aloechallenge', 'click_product_shop'],
            'primary_kpi' => 'WhatsApp intervju za saradnju',
            'secondary_kpi' => '#aloechallenge i Start paket klik',
        ],
        'defaults' => [
            'owner_user_id' => (int) ($user->user_id ?? 838),
            'contact_email' => $contact_email,
            'checkout_url' => $start_package_url,
            'whatsapp_url' => $owner_whatsapp_url,
            'calendar_url' => $owner_whatsapp_url,
            'product_shop_url' => $product_shop_url,
            'privacy_url' => $privacy_url,
            'facebook_pixel_id' => $facebook_pixel_id,
            'hide_public_navbar' => true,
        ],
    ], $user);
}

function vip_funnel_get_postani_zdrav_guide_primary_payload($user = null): ?array {
    $user_id = (int) ($user->user_id ?? 0);
    $email = trim((string) ($user->email ?? ''));

    if($user_id !== 838 && strcasecmp($email, 'info@postanizdrav.com') !== 0) {
        return null;
    }

    $full_user = db()
        ->where('user_id', $user_id)
        ->getOne('users', ['user_id', 'name', 'email', 'preferences', 'billing', 'referral_key', 'status']);

    return vip_funnel_get_postani_zdrav_guide_payload($full_user ?: $user);
}

function vip_funnel_maybe_create_postani_zdrav_guide($user = null, string $requested_slug = '') {
    $requested_slug = vip_funnel_slugify($requested_slug, '');
    if($requested_slug !== 'postani-zdrav-plan-b-vodic' || !vip_funnel_studio_schema_is_ready()) {
        return null;
    }

    $payload = vip_funnel_get_postani_zdrav_guide_primary_payload($user);
    if(!$payload) {
        return null;
    }

    $user_id = (int) ($user->user_id ?? 0);
    $existing = vip_funnel_studio_get_funnel_row_by_slug($user_id, 'postani-zdrav-plan-b-vodic');
    if($existing) {
        return $existing;
    }

    $validation_errors = vip_funnel_collect_payload_validation_errors($payload);
    if(!empty($validation_errors)) {
        error_log('[VIP Funnel] Postani Zdrav guide validation failed: ' . vip_funnel_json_encode($validation_errors));
        return null;
    }

    return vip_funnel_studio_create_funnel_from_payload((object) [
        'user_id' => $user_id,
        'name' => (string) ($user->name ?? 'Amila i Bojan Dizdarević'),
        'email' => (string) ($user->email ?? 'info@postanizdrav.com'),
        'preferences' => $user->preferences ?? null,
        'billing' => $user->billing ?? null,
        'referral_key' => $user->referral_key ?? '',
    ], $payload);
}
