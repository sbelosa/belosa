<?php

function snjezana_morning_guide_whatsapp_url(string $base_url = '', string $message = ''): string {
    $base_url = trim($base_url);
    $message = trim($message);

    if($base_url === '') {
        $base_url = 'https://wa.me/385912008959';
    }

    if($message === '') {
        return $base_url;
    }

    $separator = str_contains($base_url, '?') ? '&' : '?';

    return $base_url . $separator . 'text=' . rawurlencode($message);
}

function vip_funnel_get_snjezana_morning_guide_payload($user = null, array $options = []): array {
    $options = vip_funnel_to_array($options);
    $owner_profile = vip_funnel_get_owner_contact_profile($user);
    $mentor_name = trim((string) ($owner_profile['name'] ?? ($user->name ?? 'Snježana Gottstein')));
    $mentor_name = $mentor_name !== '' ? $mentor_name : 'Snježana Gottstein';
    $mentor_email = trim((string) ($owner_profile['email'] ?? ($user->email ?? 'snjezana.gott@gmail.com')));
    $mentor_email = filter_var($mentor_email, FILTER_VALIDATE_EMAIL) ? $mentor_email : 'snjezana.gott@gmail.com';
    $contact_email = trim((string) ($options['contact_email'] ?? $mentor_email));
    $contact_email = filter_var($contact_email, FILTER_VALIDATE_EMAIL) ? $contact_email : $mentor_email;
    $owner_whatsapp_url = trim((string) ($options['whatsapp_url'] ?? ''));
    if($owner_whatsapp_url === '') {
        $owner_phone_digits = preg_replace('/\D+/', '', (string) ($owner_profile['phone'] ?? ''));
        if(str_starts_with($owner_phone_digits, '00')) {
            $owner_phone_digits = substr($owner_phone_digits, 2);
        }
        if(str_starts_with($owner_phone_digits, '0')) {
            $owner_phone_digits = '385' . ltrim($owner_phone_digits, '0');
        }
        $owner_whatsapp_url = $owner_phone_digits !== '' ? 'https://wa.me/' . $owner_phone_digits : 'https://wa.me/385912008959';
    }

    $morning_app_url = trim((string) ($options['morning_app_url'] ?? '')) ?: 'https://dsh-snjezana-gottstein.vercel.app/';
    $website_url = trim((string) ($options['website_url'] ?? '')) ?: 'https://snjezanagottstein.com/';
    $product_shop_url = trim((string) ($options['product_shop_url'] ?? '')) ?: (trim((string) ($owner_profile['main_biolink_url'] ?? '')) ?: SITE_URL . 'blog');
    $privacy_url = trim((string) ($options['privacy_url'] ?? '')) ?: SITE_URL . 'page/privacy-policy';
    $start_package_url = vip_funnel_get_forever_business_referral_action_token();
    $facebook_pixel_id = vip_funnel_normalize_meta_pixel_id((string) ($options['facebook_pixel_id'] ?? ''));

    $whatsapp_general = snjezana_morning_guide_whatsapp_url($owner_whatsapp_url, 'Bok Snježana, prošla sam tvoj vodič i želim da mi pomogneš odabrati najbolji sljedeći korak.');
    $whatsapp_morning = snjezana_morning_guide_whatsapp_url($owner_whatsapp_url, 'Bok Snježana, zanima me Jutarnje buđenje i voljela bih dobiti više informacija.');
    $whatsapp_products = snjezana_morning_guide_whatsapp_url($owner_whatsapp_url, 'Bok Snježana, zanimaju me Forever proizvodi i preporuka za vitalnost, rutinu ili opću dobrobit.');
    $whatsapp_collaboration = snjezana_morning_guide_whatsapp_url($owner_whatsapp_url, 'Bok Snježana, zanima me kako bih kroz tvoj pristup, Forever i FCC mogla krenuti u suradnju.');
    $whatsapp_after_start = snjezana_morning_guide_whatsapp_url($owner_whatsapp_url, 'Bok Snježana, naručila sam Start paket i želim da mi pomogneš složiti prvi korak.');

    $video = static function(string $key) use ($options): string {
        return trim((string) ($options['video_' . $key] ?? ''));
    };

    $product_keys = [
        'routine' => vip_funnel_find_catalog_translation_key(['aloe vera gel', 'forever aloe vera gel'], 'hr'),
        'vitality' => vip_funnel_find_catalog_translation_key(['forever active pro-b', 'active pro-b', 'arctic sea'], 'hr'),
        'care' => vip_funnel_find_catalog_translation_key(['aloe jojoba', 'aloe liquid soap', 'forever bright'], 'hr'),
        'discount' => vip_funnel_find_catalog_translation_key(['aloe vera gel', 'forever'], 'hr'),
    ];
    $primary_product_key = $product_keys['routine'] ?: ($product_keys['vitality'] ?: ($product_keys['care'] ?: ($product_keys['discount'] ?: '')));

    $a = static function(string $id, string $label, array $payload = []): array {
        return stjepan_fcc_guide_action($id, $label, $payload);
    };

    $b = static function(string $id, string $type, array $payload = []): array {
        return stjepan_fcc_guide_block($id, $type, $payload);
    };

    $surface = static function(string $name, array $blocks, int $current = 1, int $total = 4, array $settings = []) {
        return stjepan_fcc_guide_surface($name, $blocks, array_merge([
            'background_color' => '#070A0E',
            'surface_color' => '#151C24',
            'text_color' => '#FAF7F0',
            'accent_color' => '#D7B56D',
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
            'accent_color' => '#D7B56D',
            'background_color' => '#151C24',
        ]));
    };

    $business_compliance = 'Ovo nije zaposlenje ni garantirani prihod. Dobivaš smjer, proizvode, digitalni alat i mentorstvo, a rezultati ovise o aktivnosti, vremenu, dosljednosti i tržištu.';
    $product_compliance = 'AI vodič i ove informacije služe za opće informiranje o proizvodima i wellness rutini. Proizvodi nisu lijek i nisu zamjena za savjet liječnika.';
    $contact_consent = 'Pristajem da me Snježana Gottstein kontaktira putem WhatsAppa ili telefona vezano uz moj odabrani smjer. Podaci se koriste samo za odgovor na moj upit. Privacy: ' . $privacy_url;

    $landing_blocks = [
        $b('landing_hero', 'headline', [
            'badge' => 'Osobni vodič Snježane Gottstein',
            'title' => 'Ako me pratiš, ovdje odaberi svoj najbolji sljedeći korak.',
            'text' => 'Kreni kroz kratki vodič i odaberi što ti je sada najbliže: Jutarnje buđenje, vitalnost, proizvodi, suradnja ili osobni razgovor.',
            'alignment' => 'center',
            'title_size' => 48,
            'text_size' => 19,
        ]),
        $b('landing_image', 'image', [
            'badge' => 'Fotografija / atmosfera',
            'title' => 'Ovdje ide Snježanina fotografija ili vizual Jutarnjeg buđenja',
            'text' => 'Kasnije može dodati osobnu fotografiju, sliku grupe, vizual aplikacije ili miran premium banner.',
            'layout_width' => 'half',
        ]),
        $b('landing_video', 'video', [
            'title' => 'Prvo pogledaj kratku poruku',
            'text' => 'Tema videa: zašto sam spojila Jutarnje buđenje, osobni rast, vitalnost i Forever u jedan osobni put.',
            'media_url' => $video('uvod'),
            'layout_width' => 'half',
        ]),
        $b('landing_trust', 'proof_card', [
            'badge' => 'Zašto ovaj vodič',
            'title' => '14 godina rada s ljudima, jutarnja grupa koja traje godinama i povjerenje koje se gradi prisutnošću.',
            'text' => 'Neki žele osobnu promjenu, neki proizvode, neki suradnju, a neki samo trebaju kratak razgovor. Zato ovaj vodič ne gura sve u isti smjer.',
            'layout_width' => 'full',
        ]),
        $b('landing_choice', 'survey', [
            'title' => 'Što ti je sada najbliže?',
            'text' => 'Odaberi iskreno. Vodič će te odvesti na najlogičniji sljedeći korak.',
            'alignment' => 'center',
            'options' => [
                $a('select_morning', 'Želim Jutarnje buđenje i rad na sebi', ['target_step_id' => 'morning_awakening', 'value' => 'morning', 'style' => 'primary', 'event_key' => 'select_morning']),
                $a('select_check', 'Želim da me vodič usmjeri', ['target_step_id' => 'check', 'value' => 'guided_check', 'style' => 'secondary', 'event_key' => 'select_check']),
                $a('select_vitality', 'Zanimaju me vitalnost i proizvodi', ['target_step_id' => 'vitality_products', 'value' => 'products', 'style' => 'secondary', 'event_key' => 'select_products']),
                $a('select_collaboration', 'Zanima me suradnja bez pritiska', ['target_step_id' => 'collaboration_intro', 'value' => 'collaboration', 'style' => 'secondary', 'event_key' => 'select_collaboration']),
                $a('select_whatsapp', 'Želim se javiti Snježani', ['action' => 'external_url', 'external_url' => $whatsapp_general, 'value' => 'whatsapp', 'style' => 'ghost', 'event_key' => 'click_whatsapp']),
            ],
        ]),
    ];

    $landing_page = $surface('Snježana Gottstein | Jutarnje buđenje vodič', $landing_blocks, 0, 0, [
        'show_progress' => false,
        'progress_current' => 0,
        'progress_total' => 0,
    ]);

    $morning_blocks = [
        $b('morning_hero', 'headline', [
            'badge' => 'Jutarnje buđenje',
            'title' => 'Kreni dan drugačije: prisutnije, mirnije i jasnije.',
            'text' => 'Jutarnje buđenje je Snježanin osobni program za žene koje žele svaki dan otvoriti prostor za sebe, svoj mir, tijelo, svjesnost i životne promjene.',
            'title_size' => 46,
            'text_size' => 19,
        ]),
        $b('morning_video', 'video', [
            'title' => 'Što dobivaš kroz Jutarnje buđenje',
            'text' => 'Tema videa: kome je program namijenjen, kako izgleda jutarnji rad i zašto cijena od 39 EUR otvara lakši prvi korak.',
            'media_url' => $video('jutarnje_budenje'),
            'layout_width' => 'two_thirds',
        ]),
        $b('morning_image', 'image', [
            'badge' => 'Vizual programa',
            'title' => 'Ovdje ide slika aplikacije, Snježane ili jutarnje grupe',
            'text' => 'Sekcija je spremna za sliku koju Snježana kasnije ubaci u Studio.',
            'layout_width' => 'third',
        ]),
        $b('morning_offer', 'proof_card', [
            'badge' => 'Posebna ponuda',
            'title' => 'Nova aplikacija za Jutarnje buđenje sada je 39 EUR.',
            'text' => 'Ovo je najlakši prvi korak za osobu koja već osjeća povjerenje prema Snježani, ali još ne razmišlja o suradnji ili proizvodima.',
            'layout_width' => 'half',
        ]),
        $b('morning_bridge', 'proof_card', [
            'badge' => 'Zašto je ovo važno',
            'title' => 'Osoba koja kroz program dobije promjenu kasnije prirodno može razumjeti proizvode ili suradnju.',
            'text' => 'Zato ovaj vodič primarno gradi odnos i razgovor, a ne pritisak.',
            'layout_width' => 'half',
        ]),
        $b('morning_actions', 'cta_group', [
            'text' => 'Odaberi najjednostavniji sljedeći korak.',
            'alignment' => 'center',
            'buttons' => [
                $a('morning_open_app', 'Otvori Jutarnje buđenje', ['action' => 'external_url', 'external_url' => $morning_app_url, 'event_key' => 'click_morning_app', 'sticky' => true, 'hint' => 'Vodi na Snježaninu aplikaciju za Jutarnje buđenje.']),
                $a('morning_whatsapp', 'Imam pitanje za Snježanu', ['action' => 'external_url', 'external_url' => $whatsapp_morning, 'style' => 'secondary', 'event_key' => 'click_morning_whatsapp']),
                $a('morning_check', 'Nisam sigurna, vodi me dalje', ['target_step_id' => 'check', 'style' => 'ghost', 'event_key' => 'select_morning_check']),
            ],
        ]),
    ];

    $trust_blocks = [
        $b('trust_hero', 'headline', [
            'badge' => 'Povjerenje prije odluke',
            'title' => 'Nije cilj da odmah sve kupiš ili odlučiš. Cilj je da osjetiš ima li ovo smisla za tebe.',
            'text' => 'Snježanin rad se temelji na prisutnosti, slušanju i konkretnim pomacima u životu. Zato i ovaj vodič ide mirno, jasno i osobno.',
        ]),
        $b('trust_video', 'video', [
            'title' => 'Zašto me ljudi prate godinama',
            'text' => 'Tema videa: iskustvo s grupama, jutarnji Zoom u 7:30, promjene koje ljudi prijavljuju i zašto povjerenje dolazi prije preporuke.',
            'media_url' => $video('povjerenje'),
            'layout_width' => 'two_thirds',
        ]),
        $b('trust_card', 'proof_card', [
            'badge' => 'Snježanin okvir',
            'title' => 'Terapeut, edukator i Life Coach s 14 godina rada uživo i online.',
            'text' => 'Ovdje se ne radi o agresivnoj prodaji. Radi se o putu: osobna promjena, vitalnost, preporuka i razgovor.',
            'layout_width' => 'third',
        ]),
        $b('trust_actions', 'cta_group', [
            'text' => 'Što želiš dalje?',
            'alignment' => 'center',
            'buttons' => [
                $a('trust_to_morning', 'Želim Jutarnje buđenje', ['target_step_id' => 'morning_awakening', 'event_key' => 'select_trust_morning']),
                $a('trust_to_products', 'Zanimaju me proizvodi i vitalnost', ['target_step_id' => 'vitality_products', 'style' => 'secondary', 'event_key' => 'select_trust_products']),
                $a('trust_to_collab', 'Zanima me suradnja', ['target_step_id' => 'collaboration_intro', 'style' => 'ghost', 'event_key' => 'select_trust_collab']),
            ],
        ]),
    ];

    $vitality_blocks = [
        $b('vitality_hero', 'headline', [
            'badge' => 'Vitalnost duha, uma i tijela',
            'title' => 'Ako želiš krenuti od proizvoda, kreni od svoje svakodnevne rutine.',
            'text' => 'Forever proizvodi se ovdje predstavljaju kao dio wellness rutine, osobne njege i opće dobrobiti, bez medicinskih obećanja.',
        ]),
        $b('vitality_ai', 'ai_product_advisor', [
            'badge' => 'AI vodič za proizvode',
            'title' => 'Pitaj AI vodiča što ima smisla za tvoj cilj.',
            'text' => 'Napiši što želiš podržati u svakodnevnoj rutini: energiju, balans, njegu, opću vitalnost ili jednostavan početak.',
            'ai_button_label' => 'Pokreni AI vodiča',
            'ai_launcher_label' => 'Vitalnost vodič',
            'ai_intro_label' => 'Tvoj osobni wellness vodič',
            'ai_input_placeholder' => 'Napiši što želiš podržati u svojoj rutini...',
            'layout_width' => 'half',
            'event_key' => 'open_ai_vitality',
        ]),
        $b('vitality_choice', 'survey', [
            'title' => 'Što želiš prvo istražiti?',
            'text' => 'Odabir pomaže da preporuka proizvoda bude jasnija.',
            'layout_width' => 'half',
            'options' => [
                $a('vitality_routine', 'Jutarnja wellness rutina', ['target_step_id' => 'product_recommendation', 'value' => 'jutarnja_rutina', 'event_key' => 'select_product_routine']),
                $a('vitality_energy', 'Više vitalnosti kroz dan', ['target_step_id' => 'product_recommendation', 'value' => 'vitalnost', 'style' => 'secondary', 'event_key' => 'select_product_vitality']),
                $a('vitality_care', 'Njega i osjećaj brige za sebe', ['target_step_id' => 'product_recommendation', 'value' => 'njega', 'style' => 'secondary', 'event_key' => 'select_product_care']),
                $a('vitality_discount', 'Želim naručiti bez registracije', ['action' => 'external_url', 'external_url' => $product_shop_url, 'value' => 'popust', 'style' => 'ghost', 'event_key' => 'click_product_discount']),
            ],
        ]),
        $b('vitality_note', 'text', [
            'text' => $product_compliance,
            'text_size' => 14,
        ]),
    ];

    $product_blocks = [
        $b('product_hero', 'headline', [
            'badge' => 'Preporuka proizvoda',
            'title' => 'Odaberi proizvodni smjer, a zatim se po potrebi javi Snježani.',
            'text' => 'Ovo je miran proizvodni put za osobe koje žele prvo osobno iskustvo, rutinu ili naručivanje bez registracije, a tek kasnije razgovor o suradnji.',
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
                ['id' => 'map_routine', 'match_value' => 'jutarnja_rutina', 'product_translation_key' => $product_keys['routine'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_vitality', 'match_value' => 'vitalnost', 'product_translation_key' => $product_keys['vitality'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_care', 'match_value' => 'njega', 'product_translation_key' => $product_keys['care'] ?: $primary_product_key, 'product_blog_post_id' => 0],
                ['id' => 'map_discount', 'match_value' => 'popust', 'product_translation_key' => $product_keys['discount'] ?: $primary_product_key, 'product_blog_post_id' => 0],
            ],
            'layout_width' => 'two_thirds',
            'event_key' => 'click_product_offer',
        ]),
        $b('product_next', 'proof_card', [
            'badge' => 'Ako želiš osobnije',
            'title' => 'Snježani se možeš javiti za preporuku prema tvojoj rutini.',
            'text' => 'Najbolji nastavak je kratak WhatsApp razgovor, posebno ako želiš povezati proizvode s Jutarnjim buđenjem ili osobnim rastom.',
            'layout_width' => 'third',
        ]),
        $b('product_actions', 'cta_group', [
            'text' => 'Odaberi nastavak.',
            'alignment' => 'center',
            'buttons' => [
                $a('product_whatsapp', 'Javi se Snježani za preporuku', ['action' => 'external_url', 'external_url' => $whatsapp_products, 'event_key' => 'click_product_whatsapp']),
                $a('product_shop', 'Otvori FCC aplikaciju / shop', ['action' => 'external_url', 'external_url' => $product_shop_url, 'style' => 'secondary', 'event_key' => 'click_product_shop']),
                $a('product_to_collab', 'Zanima me i suradnja', ['target_step_id' => 'collaboration_intro', 'style' => 'ghost', 'event_key' => 'select_product_collab']),
            ],
        ]),
        $b('product_compliance', 'text', ['text' => $product_compliance, 'text_size' => 14]),
    ];

    $collaboration_blocks = [
        $b('collab_hero', 'headline', [
            'badge' => 'Suradnja bez pritiska',
            'title' => 'Ako već vjeruješ Snježani, suradnja može biti prirodan nastavak osobnog rasta.',
            'text' => 'Ovaj put nije za svakoga i nije brz pritisak. Namijenjen je osobama koje žele spojiti osobni razvoj, wellness proizvode, preporuke i digitalni alat.',
        ]),
        $b('collab_video', 'video', [
            'title' => 'Kako izgleda suradnja uz Snježanu',
            'text' => 'Tema videa: kako preporučivati bez neugodnog prodavanja i zašto je razgovor važniji od nagovaranja.',
            'media_url' => $video('suradnja'),
            'layout_width' => 'two_thirds',
        ]),
        $b('collab_fit', 'proof_card', [
            'badge' => 'Za koga je ovo',
            'title' => 'Za žene koje žele smislen dodatni smjer, imaju povjerenje u Snježanin rad i žele preporučivati mirno.',
            'text' => 'Najbolji kandidati su oni koji već vole osobni rast, rad sa sobom, wellness rutinu ili žele dodatni prihod kroz preporuku.',
            'layout_width' => 'third',
        ]),
        $b('collab_compliance', 'text', ['text' => $business_compliance, 'text_size' => 14]),
        $b('collab_actions', 'cta_group', [
            'text' => 'Kreni onim putem koji ti je najprirodniji.',
            'alignment' => 'center',
            'buttons' => [
                $a('collab_check', 'Želim kratku provjeru', ['target_step_id' => 'check', 'event_key' => 'select_collab_check']),
                $a('collab_start', 'Spremna sam za Start paket', ['target_step_id' => 'start_package', 'style' => 'secondary', 'event_key' => 'select_collab_start']),
                $a('collab_whatsapp', 'Želim razgovor sa Snježanom', ['action' => 'external_url', 'external_url' => $whatsapp_collaboration, 'style' => 'ghost', 'event_key' => 'click_collab_whatsapp']),
            ],
        ]),
    ];

    $recommendation_blocks = [
        $b('rec_hero', 'headline', [
            'badge' => 'Preporuka u stvarnom životu',
            'title' => 'Preporuka ne mora biti prodaja. Može biti poziv na iskustvo i razgovor.',
            'text' => 'Snježanin stil je osoban: slušanje, pitanje, prisutnost i ponuda sljedećeg koraka samo ako osoba osjeća da joj ima smisla.',
        ]),
        $b('rec_video', 'video', [
            'title' => 'Kako preporučivati bez pritiska',
            'text' => 'Tema videa: kako povezati Jutarnje buđenje, proizvode, FCC aplikaciju i WhatsApp razgovor bez neugodnog prodajnog tona.',
            'media_url' => $video('preporuka'),
        ]),
        $b('rec_actions', 'cta_group', [
            'text' => 'Ako želiš ovakav način rada, odaberi nastavak.',
            'alignment' => 'center',
            'buttons' => [
                $a('rec_to_start', 'Želim Start paket i početak', ['target_step_id' => 'start_package', 'event_key' => 'select_rec_start']),
                $a('rec_to_whatsapp', 'Prvo želim razgovor', ['action' => 'external_url', 'external_url' => $whatsapp_collaboration, 'style' => 'secondary', 'event_key' => 'click_rec_whatsapp']),
                $a('rec_to_morning', 'Vraćam se na Jutarnje buđenje', ['target_step_id' => 'morning_awakening', 'style' => 'ghost', 'event_key' => 'select_rec_morning']),
            ],
        ]),
        $b('rec_compliance', 'text', ['text' => $business_compliance, 'text_size' => 14]),
    ];

    $check_blocks = [
        $b('check_hero', 'headline', [
            'badge' => 'Kratka provjera',
            'title' => 'Odgovori iskreno i vodič će te odvesti na najlogičniji sljedeći korak.',
            'text' => 'Ovo nije test. Cilj je da ne lutaš između programa, proizvoda i suradnje, nego da dobiješ jasan smjer.',
        ]),
        $b('check_goal', 'radio_survey', [
            'title' => 'Što ti je sada najbliže?',
            'text' => 'Ovaj odgovor određuje tvoj preporučeni sljedeći korak.',
            'required' => true,
            'route_on_submit' => true,
            'options' => [
                ['id' => 'goal_morning', 'label' => 'Želim Jutarnje buđenje', 'hint' => 'Želim krenuti od rada na sebi, jutarnje rutine i Snježanine podrške.', 'value' => 'morning', 'target_step_id' => 'result_morning'],
                ['id' => 'goal_products', 'label' => 'Zanimaju me vitalnost i proizvodi', 'hint' => 'Želim prvo osobno iskustvo, preporuku ili naručivanje bez registracije.', 'value' => 'products', 'target_step_id' => 'result_products'],
                ['id' => 'goal_collab', 'label' => 'Zanima me suradnja', 'hint' => 'Želim razumjeti kako bih spojila osobni rast, proizvode i preporuke.', 'value' => 'collaboration', 'target_step_id' => 'result_collaboration'],
                ['id' => 'goal_talk', 'label' => 'Trebam osobni razgovor', 'hint' => 'Najviše bi mi pomoglo da se javim Snježani.', 'value' => 'talk', 'target_step_id' => 'result_whatsapp'],
                ['id' => 'goal_start', 'label' => 'Spremna sam za Start paket', 'hint' => 'Želim krenuti konkretnije kroz Forever i preporuku.', 'value' => 'start', 'target_step_id' => 'start_package'],
            ],
        ]),
        $b('check_relationship', 'radio_survey', [
            'title' => 'Kako poznaješ Snježanin rad?',
            'text' => 'Ovo pomaže Snježani da zna odakle krenuti u razgovoru.',
            'required' => false,
            'options' => [
                ['id' => 'rel_morning_group', 'label' => 'U Jutarnjem buđenju sam ili ga pratim', 'hint' => 'Već postoji povjerenje i iskustvo.'],
                ['id' => 'rel_social', 'label' => 'Pratim Snježanu na društvenim mrežama ili YouTubeu', 'hint' => 'Želim povezati ono što pratim sa sljedećim korakom.'],
                ['id' => 'rel_new', 'label' => 'Nova sam i tek istražujem', 'hint' => 'Želim mirno razumjeti opcije.'],
            ],
        ]),
        $b('check_time', 'radio_survey', [
            'title' => 'Koliko prostora sada imaš za novi korak?',
            'text' => 'Odgovori realno, bez pritiska.',
            'required' => false,
            'options' => [
                ['id' => 'time_light', 'label' => 'Želim mali, lagani početak', 'hint' => 'Najbolji put je Jutarnje buđenje ili proizvodi.'],
                ['id' => 'time_medium', 'label' => 'Mogu odvojiti vrijeme za razgovor i prve korake', 'hint' => 'Može imati smisla provjera ili suradnja.'],
                ['id' => 'time_ready', 'label' => 'Spremna sam krenuti ozbiljnije', 'hint' => 'Može imati smisla Start paket i osobni razgovor.'],
            ],
        ]),
        $b('check_name', 'full_name_field', ['title' => 'Ime i prezime', 'placeholder' => 'Upiši ime i prezime', 'required' => true, 'layout_width' => 'half']),
        $b('check_phone', 'phone_field', ['title' => 'WhatsApp / telefon', 'placeholder' => 'Upiši broj na koji te Snježana može kontaktirati', 'required' => true, 'layout_width' => 'half']),
        $b('check_email', 'email_field', ['title' => 'Email - opcionalno', 'placeholder' => 'Upiši email ako želiš potvrdu ili dodatne informacije', 'required' => false, 'layout_width' => 'half']),
        $b('check_contact_time', 'text_field', ['title' => 'Najbolje vrijeme za kontakt', 'placeholder' => 'npr. danas poslije 18h, sutra ujutro...', 'field_key' => 'contact_time', 'required' => false, 'layout_width' => 'half']),
        $b('check_consent', 'checkbox_field', ['title' => 'Privola za kontakt', 'text' => $contact_consent, 'field_key' => 'contact_consent', 'required' => true]),
        $b('check_submit', 'cta_group', [
            'text' => 'Pošalji odgovore i prikaži preporučeni sljedeći korak.',
            'alignment' => 'center',
            'buttons' => [
                $a('check_submit_btn', 'Pošalji i pokaži mi sljedeći korak', ['action' => 'submit_next', 'target_step_id' => 'result_whatsapp', 'require_submit' => true, 'event_key' => 'submit_check']),
            ],
        ]),
    ];

    $start_blocks = [
        $b('start_hero', 'headline', [
            'badge' => 'Start paket',
            'title' => 'Ako želiš krenuti u Forever suradnju, Start paket je tvoj konkretan prvi korak.',
            'text' => 'Ovaj korak ima smisla tek kada želiš spojiti proizvode, osobno iskustvo, preporuku i Snježaninu podršku.',
        ]),
        $b('start_video', 'video', [
            'title' => 'Prije narudžbe pogledaj što znači krenuti uz Snježanu',
            'text' => 'Tema videa: što osoba dobiva, što se događa nakon narudžbe i zašto se treba javiti Snježani prije ili nakon starta.',
            'media_url' => $video('start'),
        ]),
        $b('start_get', 'proof_card', [
            'badge' => 'Što dobivaš',
            'title' => 'Proizvode za početak, Forever upis i smjer za prve preporuke.',
            'text' => 'Nakon narudžbe javi se Snježani da dogovorite prvi razgovor i najjednostavniji početni plan.',
            'layout_width' => 'half',
        ]),
        $b('start_support', 'proof_card', [
            'badge' => 'Snježanina podrška',
            'title' => 'Ne krećeš sama, nego kroz osobni razgovor i jasne prve korake.',
            'text' => 'Start paket nije obećanje rezultata, nego proizvodni početak i ulazak u sustav preporuke.',
            'layout_width' => 'half',
        ]),
        $b('start_compliance', 'text', ['text' => $business_compliance, 'text_size' => 14]),
        $b('start_actions', 'cta_group', [
            'text' => 'Odaberi kako želiš napraviti sljedeći korak.',
            'alignment' => 'center',
            'buttons' => [
                $a('start_order', 'Naruči Start paket', ['action' => 'external_url', 'external_url' => $start_package_url, 'event_key' => 'click_start_order', 'hint' => 'Vodi na službenu Forever Living stranicu s preporukom Snježane i automatskim odabirom zemlje.', 'sticky' => true]),
                $a('start_whatsapp', 'Javljam se Snježani prije ili nakon narudžbe', ['action' => 'external_url', 'external_url' => $whatsapp_after_start, 'style' => 'secondary', 'event_key' => 'click_start_whatsapp']),
                $a('start_unsure', 'Nisam sigurna - želim razgovor', ['target_step_id' => 'result_whatsapp', 'style' => 'ghost', 'event_key' => 'select_start_unsure']),
            ],
        ]),
    ];

    $result_morning_blocks = [
        $b('result_morning_hero', 'headline', [
            'badge' => 'Preporučeni sljedeći korak',
            'title' => 'Tvoj najbolji prvi korak je Jutarnje buđenje.',
            'text' => 'Kreni od osobnog iskustva, ritma i Snježanine podrške. Ako kasnije osjetiš interes za proizvode ili suradnju, put ostaje otvoren.',
        ]),
        $b('result_morning_actions', 'cta_group', [
            'text' => 'Nastavi ovdje.',
            'alignment' => 'center',
            'buttons' => [
                $a('result_morning_app', 'Otvori Jutarnje buđenje', ['action' => 'external_url', 'external_url' => $morning_app_url, 'event_key' => 'click_result_morning_app']),
                $a('result_morning_whatsapp', 'Pošalji Snježani poruku', ['action' => 'external_url', 'external_url' => $whatsapp_morning, 'style' => 'secondary', 'event_key' => 'click_result_morning_whatsapp']),
                $a('result_morning_products', 'Zanimaju me i proizvodi', ['target_step_id' => 'vitality_products', 'style' => 'ghost', 'event_key' => 'select_result_morning_products']),
            ],
        ]),
    ];

    $result_products_blocks = [
        $b('result_products_hero', 'headline', [
            'badge' => 'Preporučeni sljedeći korak',
            'title' => 'Tvoj najbolji prvi korak su vitalnost, proizvodi i osobna preporuka.',
            'text' => 'Kreni od opće wellness rutine i pitaj AI vodiča ili Snježanu što bi imalo smisla za tvoj cilj.',
        ]),
        $b('result_products_ai', 'ai_product_advisor', [
            'title' => 'Pitaj AI vodiča za proizvodni smjer',
            'text' => 'AI vodič pomaže istražiti proizvode bez medicinskih tvrdnji i bez pritiska.',
            'ai_button_label' => 'Pokreni AI vodiča',
            'layout_width' => 'half',
            'event_key' => 'open_ai_result_products',
        ]),
        $b('result_products_actions', 'cta_group', [
            'layout_width' => 'half',
            'buttons' => [
                $a('result_products_shop', 'Otvori FCC aplikaciju / shop', ['action' => 'external_url', 'external_url' => $product_shop_url, 'event_key' => 'click_result_products_shop']),
                $a('result_products_whatsapp', 'Javi se Snježani', ['action' => 'external_url', 'external_url' => $whatsapp_products, 'style' => 'secondary', 'event_key' => 'click_result_products_whatsapp']),
                $a('result_products_collab', 'Zanima me i suradnja', ['target_step_id' => 'collaboration_intro', 'style' => 'ghost', 'event_key' => 'select_result_products_collab']),
            ],
        ]),
        $b('result_products_note', 'text', ['text' => $product_compliance, 'text_size' => 14]),
    ];

    $result_collab_blocks = [
        $b('result_collab_hero', 'headline', [
            'badge' => 'Preporučeni sljedeći korak',
            'title' => 'Tvoj najbolji sljedeći korak je razgovor sa Snježanom o suradnji.',
            'text' => 'Ako ti ima smisla spoj osobnog rasta, proizvoda i preporuke, najbolje je da prvo prođeš kratak razgovor, a zatim odlučiš ima li smisla Start paket.',
        ]),
        $b('result_collab_actions', 'cta_group', [
            'text' => 'Odaberi nastavak.',
            'alignment' => 'center',
            'buttons' => [
                $a('result_collab_whatsapp', 'Pošalji WhatsApp poruku', ['action' => 'external_url', 'external_url' => $whatsapp_collaboration, 'event_key' => 'click_result_collab_whatsapp']),
                $a('result_collab_start', 'Pogledaj Start paket', ['target_step_id' => 'start_package', 'style' => 'secondary', 'event_key' => 'select_result_collab_start']),
                $a('result_collab_rec', 'Kako preporuka izgleda bez pritiska', ['target_step_id' => 'recommendation_practice', 'style' => 'ghost', 'event_key' => 'select_result_collab_rec']),
            ],
        ]),
        $b('result_collab_note', 'text', ['text' => $business_compliance, 'text_size' => 14]),
    ];

    $result_whatsapp_blocks = [
        $b('result_whatsapp_hero', 'headline', [
            'badge' => 'Osobni razgovor',
            'title' => 'Najbolji sljedeći korak je kratka poruka Snježani.',
            'text' => 'Napiši joj što te najviše zanima: Jutarnje buđenje, proizvodi, Start paket ili suradnja. Tako će ti moći odgovoriti osobno i jednostavno.',
        ]),
        $b('result_whatsapp_actions', 'cta_group', [
            'text' => 'Kontaktiraj Snježanu direktno.',
            'alignment' => 'center',
            'buttons' => [
                $a('result_whatsapp_general', 'Pošalji WhatsApp poruku', ['action' => 'external_url', 'external_url' => $whatsapp_general, 'event_key' => 'click_result_whatsapp']),
                $a('result_whatsapp_morning', 'Otvori Jutarnje buđenje', ['action' => 'external_url', 'external_url' => $morning_app_url, 'style' => 'secondary', 'event_key' => 'click_result_whatsapp_morning']),
                $a('result_whatsapp_start', 'Pogledaj Start paket', ['target_step_id' => 'start_package', 'style' => 'ghost', 'event_key' => 'select_result_whatsapp_start']),
            ],
        ]),
    ];

    $board = [
        [
            'key' => 'entry',
            'steps' => [
                $step('morning_awakening', 'entry', 'morning', 'offer', 'Jutarnje buđenje', 'Glavni ulaz za Snježaninu publiku i najmekši prvi korak.', $morning_blocks, '', 1, 3),
                $step('trust_story', 'entry', 'morning', 'proof', 'Zašto Snježani ljudi vjeruju', 'Povjerenje, iskustvo i osobni okvir prije odluke.', $trust_blocks, '', 1, 3),
                $step('vitality_products', 'entry', 'products', 'segment', 'Vitalnost i proizvodi', 'Proizvodni put s AI savjetnikom i opreznim wellness tekstom.', $vitality_blocks, '', 1, 3),
                $step('collaboration_intro', 'entry', 'collaboration', 'segment', 'Suradnja bez pritiska', 'Uvod u suradnju kroz osobni rast i preporuku.', $collaboration_blocks, '', 1, 4),
            ],
        ],
        [
            'key' => 'experience',
            'steps' => [
                $step('product_recommendation', 'experience', 'products', 'offer', 'Preporuka proizvoda', 'Dinamička preporuka po odabranom proizvodnom cilju.', $product_blocks, '', 2, 3),
                $step('recommendation_practice', 'experience', 'collaboration', 'proof', 'Preporuka bez pritiska', 'Objašnjava kako preporuka može biti prirodna i osobna.', $recommendation_blocks, '', 2, 4),
                $step('check', 'experience', 'morning', 'survey', 'Kratka provjera', 'Survey i kontakt forma za pametno usmjeravanje.', $check_blocks, '', 2, 3, ['block_mode' => 'contact_form']),
            ],
        ],
        [
            'key' => 'conversion',
            'steps' => [
                $step('start_package', 'conversion', 'collaboration', 'cta', 'Start paket', 'Službeni Forever start s njezinom preporukom i WhatsApp nastavkom.', $start_blocks, '', 3, 4),
                $step('result_morning', 'conversion', 'morning', 'cta', 'Rezultat - Jutarnje buđenje', 'Završni smjer za osobe kojima je program najbolji prvi korak.', $result_morning_blocks, '', 3, 3),
                $step('result_products', 'conversion', 'products', 'cta', 'Rezultat - Proizvodi', 'Završni smjer za proizvode, AI i shop.', $result_products_blocks, '', 3, 3),
                $step('result_collaboration', 'conversion', 'collaboration', 'cta', 'Rezultat - Suradnja', 'Završni smjer prema razgovoru i Start paketu.', $result_collab_blocks, '', 4, 4),
                $step('result_whatsapp', 'conversion', 'morning', 'cta', 'Kontakt sa Snježanom', 'Jednostavan WhatsApp završetak za neodlučne i tople kontakte.', $result_whatsapp_blocks, '', 3, 3),
            ],
        ],
    ];

    return vip_funnel_normalize_studio_payload([
        'funnel' => [
            'name' => 'Snježana Gottstein - Jutarnje buđenje vodič',
            'slug' => 'snjezana-jutarnje-budenje-vodic',
            'status' => 'active',
            'visibility_mode' => 'pro_live',
            'owner_mode' => 'private',
        ],
        'overview' => [
            'eyebrow' => 'Snježana Gottstein | Osobni vodič',
            'headline' => 'Jutarnje buđenje, vitalnost i osobni sljedeći korak',
            'subheadline' => 'Vodič za žene koje prate Snježanu i žele odabrati između Jutarnjeg buđenja, proizvoda, razgovora ili suradnje.',
            'primary_cta' => 'Odaberi svoj put',
            'secondary_cta' => 'Javi se Snježani',
        ],
        'positioning' => [
            'for' => 'Za žene 35-65+ koje prate Snježanu, žele osobni rast, Jutarnje buđenje, vitalnost ili miran razgovor o suradnji.',
            'problem' => 'Publika ima povjerenje, ali treba jednostavan put od interesa do osobnog sljedećeg koraka.',
            'mechanism' => 'Vodič prvo segmentira osobu kroz Jutarnje buđenje, proizvode, provjeru ili suradnju, a zatim vodi prema WhatsAppu, aplikaciji, AI preporuci ili Start paketu.',
            'offer_promise' => 'Osoban, miran i premium put bez pritiska i bez medicinskih ili financijskih obećanja.',
            'why_now' => 'Nova niža cijena Jutarnjeg buđenja otvara snažan prvi korak za već postojeću publiku.',
        ],
        'landing_page' => $landing_page,
        'paths' => [
            ['path_key' => 'morning', 'title' => 'Jutarnje buđenje', 'description' => 'Primarni put za program, povjerenje i WhatsApp kontakt.', 'sort_order' => 1, 'is_enabled' => true],
            ['path_key' => 'products', 'title' => 'Vitalnost i proizvodi', 'description' => 'Put za AI preporuku, proizvode i naručivanje bez registracije.', 'sort_order' => 2, 'is_enabled' => true],
            ['path_key' => 'collaboration', 'title' => 'Suradnja', 'description' => 'Put za osobe koje žele preporučivati bez pritiska i eventualno krenuti sa Start paketom.', 'sort_order' => 3, 'is_enabled' => true],
        ],
        'board' => $board,
        'products' => [
            'primary_product_key' => $primary_product_key,
            'preferred_language_code' => 'hr',
            'product_shop_url' => $product_shop_url,
            'morning_app_url' => $morning_app_url,
        ],
        'proof' => [
            'mentor' => $mentor_name,
            'experience' => '14 godina rada s ljudima, grupne edukacije, individualni tretmani i Life Coaching.',
            'community' => 'Jutarnje buđenje i grupa koja se okuplja uživo preko Zooma svako jutro.',
        ],
        'follow_up' => [
            'primary_channel' => 'whatsapp',
            'hot_action' => 'Javiti se isti dan i pitati što osoba želi: Jutarnje buđenje, proizvode, Start paket ili razgovor.',
            'morning_action' => 'Poslati link Jutarnjeg buđenja i kratko objasniti kako krenuti.',
            'product_action' => 'Poslati AI/product smjer i pitati želi li osobnu preporuku.',
            'collaboration_action' => 'Dogovoriti kratak razgovor prije Start paketa.',
        ],
        'demo' => [
            'enabled' => false,
            'note' => 'Snježanin funnel ne otvara FCC demo pristup. Sve dodatne informacije idu kroz osobni WhatsApp kontakt.',
        ],
        'analytics' => [
            'events' => ['view_landing', 'select_morning', 'click_morning_app', 'submit_check', 'click_whatsapp', 'click_start_order', 'open_ai_vitality', 'click_product_shop'],
            'primary_kpi' => 'Jutarnje buđenje klik + WhatsApp kontakt',
            'secondary_kpi' => 'AI/product interes i Start paket klik',
        ],
        'defaults' => [
            'owner_user_id' => (int) ($user->user_id ?? 898),
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

function vip_funnel_get_snjezana_morning_guide_primary_payload($user = null): ?array {
    $user_id = (int) ($user->user_id ?? 0);
    $email = trim((string) ($user->email ?? ''));

    if($user_id !== 898 && strcasecmp($email, 'snjezana.gott@gmail.com') !== 0) {
        return null;
    }

    $full_user = db()
        ->where('user_id', $user_id)
        ->getOne('users', ['user_id', 'name', 'email', 'preferences', 'billing', 'referral_key', 'status']);

    return vip_funnel_get_snjezana_morning_guide_payload($full_user ?: $user);
}

function vip_funnel_maybe_create_snjezana_morning_guide($user = null, string $requested_slug = '') {
    $requested_slug = vip_funnel_slugify($requested_slug, '');
    if($requested_slug !== 'snjezana-jutarnje-budenje-vodic' || !vip_funnel_studio_schema_is_ready()) {
        return null;
    }

    $payload = vip_funnel_get_snjezana_morning_guide_primary_payload($user);
    if(!$payload) {
        return null;
    }

    $user_id = (int) ($user->user_id ?? 0);
    $existing = vip_funnel_studio_get_funnel_row_by_slug($user_id, 'snjezana-jutarnje-budenje-vodic');
    if($existing) {
        return $existing;
    }

    $validation_errors = vip_funnel_collect_payload_validation_errors($payload);
    if(!empty($validation_errors)) {
        error_log('[VIP Funnel] Snježana Morning guide validation failed: ' . vip_funnel_json_encode($validation_errors));
        return null;
    }

    return vip_funnel_studio_create_funnel_from_payload((object) [
        'user_id' => $user_id,
        'name' => (string) ($user->name ?? 'Snježana Gottstein'),
        'email' => (string) ($user->email ?? 'snjezana.gott@gmail.com'),
        'preferences' => $user->preferences ?? null,
    ], $payload);
}
