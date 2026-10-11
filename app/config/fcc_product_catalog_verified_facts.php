<?php

defined('ALTUMCODE') || die();

/*
 * Source-grounded Croatian facts extracted from "Proizvodi za upis u bazu.pdf".
 *
 * "Verified" here means that every fact below was manually checked against the
 * supplied PDF pages. It is not an independent verification against a current
 * product label or a regulatory source. Consumers must merge only on the exact
 * array key; match signals and aliases are supporting identity evidence, not a
 * reason to merge two different products.
 *
 * Shape: product_key => language_code => fact fields. The source is Croatian,
 * so this overlay deliberately contains only `hr`. Do not copy these strings
 * into en/sl/bg fallback entries; those languages require separately reviewed
 * translations of the same source facts.
 *
 * The PDF contains 33 product records. Two are intentionally not represented:
 * - Forever Garcinia Plus has no unambiguous legacy slug/product_key.
 * - Forever Vitamin C & Bakuchiol is one composite PDF record, while the legacy
 *   catalog stores Forever Vitamin C and Forever Bakuchiol as separate products.
 *
 * Boundary check: the Vitolize Men note continues onto PDF page 11. Clean 9
 * starts immediately afterwards as a new "Naziv proizvoda" record on page 11.
 */

$source_refs = static function(int ...$pages): array {
    return array_map(static fn(int $page): array => [
        'source_type' => 'local_pdf',
        'document' => 'Proizvodi za upis u bazu.pdf',
        'document_sha256' => '2e79d66e2ef24b6ccf8bdf28d0aad2c357acad31f3bb175014714dae0cebd37b',
        'page' => $page,
    ], $pages);
};

$verified_facts_hr = [
    'forever-aloe-vera-gel' => [
        'canonical_match_signals' => ['Aloe Vera Gel', 'Forever Aloe Vera Gel'],
        'aliases' => ['aloe vera gel', 'forever aloe vera gel', 'aloe gel'],
        'use_cases' => ['svakodnevna aloe rutina', 'podrška probavnoj rutini', 'opća vitalnost'],
        'ingredient_facts' => ['Sadrži 99,7 % stabiliziranog gela iz unutarnjeg lista aloe vere.'],
        'approved_support_facts' => ['Aloe napitak namijenjen je probavnoj rutini i prirodnoj apsorpciji nutrijenata.'],
        'usage_facts' => ['Uzimati 50 ml dva puta dnevno prije obroka.', 'Može se pomiješati s vodom, iako je preporučena samostalna konzumacija.', 'Može se koristiti dugoročno u preporučenim dozama.'],
        'safety_notes' => ['Dodatak prehrani nije zamjena za uravnoteženu prehranu.'],
        'source_refs' => $source_refs(1),
    ],

    'aloe-berry-nectar' => [
        'canonical_match_signals' => ['Aloe Berry Nectar', 'Forever Aloe Berry Nectar'],
        'aliases' => ['aloe berry nectar', 'forever aloe berry nectar', 'berry nectar'],
        'use_cases' => ['svakodnevna aloe rutina', 'podrška mokraćnom sustavu', 'opća nutritivna podrška'],
        'ingredient_facts' => ['Kombinira aloe veru i ekstrakt brusnice.', 'Sadrži prirodne antioksidanse.'],
        'approved_support_facts' => ['Aloe napitak s brusnicom namijenjen je rutini usmjerenoj na mokraćni sustav i opću otpornost.'],
        'usage_facts' => ['Uzimati 50 ml dva puta dnevno.', 'Može se koristiti kontinuirano.'],
        'safety_notes' => ['Ne prekoračivati preporučenu dnevnu dozu.', 'Sadrži prirodne biljne ekstrakte.'],
        'source_refs' => $source_refs(1, 2),
    ],

    'forever-aloe-peaches' => [
        'canonical_match_signals' => ['Aloe Peaches', 'Forever Aloe Peaches'],
        'aliases' => ['aloe peaches', 'forever aloe peaches', 'aloe breskva'],
        'use_cases' => ['svakodnevna aloe rutina', 'podrška probavnoj rutini', 'voćna alternativa aloe napitku'],
        'ingredient_facts' => ['Sadrži aloe veru i prirodni ekstrakt breskve.'],
        'approved_support_facts' => ['Voćni aloe napitak namijenjen je probavnoj rutini uz blaži okus.'],
        'usage_facts' => ['Uzimati 50 ml dva puta dnevno.'],
        'safety_notes' => ['Prije primjene kod djece savjetovati se sa stručnjakom.', 'Nakon otvaranja čuvati na hladnom mjestu.'],
        'source_refs' => $source_refs(2, 3),
    ],

    'forever-active-pro-b' => [
        'canonical_match_signals' => ['Forever Active Pro-B', 'Forever Active Pro B'],
        'aliases' => ['forever active pro-b', 'forever active pro b', 'active pro b', 'pro-b'],
        'use_cases' => ['podrška crijevnoj flori', 'probavna rutina', 'rutina nakon antibiotske terapije'],
        'ingredient_facts' => ['Probiotički dodatak sa šest sojeva bakterija.'],
        'approved_support_facts' => ['Namijenjen je podršci ravnoteži crijevne flore.'],
        'usage_facts' => ['Uzimati jednu kapsulu dnevno prije spavanja ili na prazan želudac.', 'Preporučena rutina traje najmanje 30 dana.'],
        'safety_notes' => ['Čuvati na suhom i hladnom mjestu.'],
        'source_refs' => $source_refs(3),
    ],

    'forever-fiber' => [
        'canonical_match_signals' => ['Forever Fiber'],
        'aliases' => ['forever fiber', 'fiber', 'vlakna'],
        'use_cases' => ['povećanje unosa vlakana', 'redovitija probavna rutina'],
        'ingredient_facts' => ['Sadrži topiva vlakna.'],
        'approved_support_facts' => ['Dodatak topivih vlakana namijenjen je podršci redovitoj probavi.'],
        'usage_facts' => ['Pomiješati jednu vrećicu s vodom i uzeti jednom dnevno.', 'Na početku je moguća nadutost dok se tijelo prilagođava.'],
        'safety_notes' => ['Povećati unos vode tijekom korištenja.'],
        'source_refs' => $source_refs(3, 4),
    ],

    'forever-arctic-sea' => [
        'canonical_match_signals' => ['Forever Arctic Sea'],
        'aliases' => ['forever arctic sea', 'arctic sea', 'omega-3'],
        'use_cases' => ['unos omega-3 masnih kiselina', 'kardiovaskularna nutritivna rutina', 'nutritivna podrška mozgu'],
        'ingredient_facts' => ['Sadrži omega-3 masne kiseline EPA i DHA.'],
        'approved_support_facts' => ['Omega-3 dodatak namijenjen je općoj nutritivnoj podršci srcu, mozgu i krvnim žilama.'],
        'usage_facts' => ['Uzimati dvije kapsule dva puta dnevno uz obrok.'],
        'safety_notes' => ['Osobe na terapiji za razrjeđivanje krvi trebaju se savjetovati s liječnikom.', 'Ne prekoračivati preporučenu dnevnu dozu.'],
        'source_refs' => $source_refs(4, 5),
    ],

    'forever-nutra-q10' => [
        'canonical_match_signals' => ['Forever CardioHealth (Nutra Q10)', 'Forever Nutra Q10'],
        'aliases' => ['forever cardiohealth', 'cardiohealth', 'forever nutra q10', 'nutra q10', 'coq10'],
        'use_cases' => ['energijska nutritivna rutina', 'kardiovaskularna nutritivna rutina'],
        'ingredient_facts' => ['Sadrži koenzim Q10, vitamine B skupine i biljne ekstrakte.'],
        'approved_support_facts' => ['Kombinacija je namijenjena rutini usmjerenoj na energiju i kardiovaskularnu nutritivnu podršku.'],
        'usage_facts' => ['Pomiješati jednu vrećicu s napitkom i uzeti jednom dnevno.', 'Preporučuje se uzimanje ujutro uz obrok.'],
        'safety_notes' => ['Osobe s postojećim zdravstvenim stanjima trebaju se savjetovati sa stručnjakom.'],
        'source_refs' => $source_refs(5),
    ],

    'forever-freedom' => [
        'canonical_match_signals' => ['Forever Freedom'],
        'aliases' => ['forever freedom', 'freedom'],
        'use_cases' => ['rutina za opterećene zglobove', 'podrška pokretljivosti'],
        'ingredient_facts' => ['Tekući dodatak s glukozaminom, hondroitinom i MSM-om.'],
        'approved_support_facts' => ['Kombinacija je namijenjena zglobovima i rutini pokretljivosti.'],
        'usage_facts' => ['Uzimati 50 ml dva puta dnevno.'],
        'safety_notes' => ['Nakon otvaranja čuvati u hladnjaku.'],
        'source_refs' => $source_refs(5, 6),
    ],

    'forever-active-ha' => [
        'canonical_match_signals' => ['Forever Active HA'],
        'aliases' => ['forever active ha', 'active ha'],
        'use_cases' => ['nutritivna podrška zglobovima', 'rutina za kožu i zglobove'],
        'ingredient_facts' => ['Sadrži hijaluronsku kiselinu.'],
        'approved_support_facts' => ['Dodatak s hijaluronskom kiselinom namijenjen je rutini usmjerenoj na zglobove i kožu.'],
        'usage_facts' => ['Uzimati dvije kapsule dnevno uz obrok.', 'Može se kombinirati s proizvodom Forever Freedom.'],
        'safety_notes' => ['Pridržavati se preporučene dnevne doze.'],
        'source_refs' => $source_refs(6, 7),
    ],

    'forever-royal-jelly' => [
        'canonical_match_signals' => ['Forever Royal Jelly'],
        'aliases' => ['forever royal jelly', 'royal jelly', 'matična mliječ'],
        'use_cases' => ['opća vitalnost', 'energijska rutina'],
        'ingredient_facts' => ['Sadrži matičnu mliječ.'],
        'approved_support_facts' => ['Dodatak s matičnom mliječi namijenjen je općoj energiji i vitalnosti.'],
        'usage_facts' => ['Otopiti jednu tabletu ispod jezika dva puta dnevno.', 'Namijenjena je dugotrajnoj primjeni uz preporučene doze.'],
        'safety_notes' => ['Osobe alergične na pčelinje proizvode trebaju izbjegavati korištenje.'],
        'source_refs' => $source_refs(7),
    ],

    'forever-immublend' => [
        'canonical_match_signals' => ['Forever ImmuBlend'],
        'aliases' => ['forever immublend', 'immublend', 'immu blend'],
        'use_cases' => ['opća nutritivna podrška imunitetu', 'sezonska rutina otpornosti'],
        'ingredient_facts' => ['Sadrži vitamine, minerale i biljne ekstrakte.'],
        'approved_support_facts' => ['Kombinacija je namijenjena općoj nutritivnoj podršci imunološkom sustavu.'],
        'usage_facts' => ['Uzimati dvije tablete dnevno uz obrok.', 'Može se koristiti tijekom cijele godine, osobito sezonski.'],
        'safety_notes' => ['Ne zamjenjuje raznoliku prehranu.'],
        'source_refs' => $source_refs(7, 8),
    ],

    'forever-absorbent-c' => [
        'canonical_match_signals' => ['Forever Absorbent-C', 'Forever Absorbent C'],
        'aliases' => ['forever absorbent-c', 'forever absorbent c', 'absorbent c'],
        'use_cases' => ['dodatni unos vitamina C', 'opća nutritivna podrška imunitetu'],
        'ingredient_facts' => ['Dodatak prehrani s vitaminom C.'],
        'approved_support_facts' => ['Vitamin C namijenjen je dodatnom unosu vitamina C te nutritivnoj podršci imunitetu i zaštiti od oksidativnog stresa.'],
        'usage_facts' => ['Uzimati jednu tabletu jedan do tri puta dnevno.', 'Može se uzimati uz druge vitamine.'],
        'safety_notes' => ['Ne prekoračivati preporučenu dozu.'],
        'source_refs' => $source_refs(8, 9),
    ],

    'forever-multi-maca' => [
        'canonical_match_signals' => ['Forever Multi Maca'],
        'aliases' => ['forever multi maca', 'multi maca', 'maca'],
        'use_cases' => ['energijska rutina', 'rutina usmjerena na hormonalnu ravnotežu'],
        'ingredient_facts' => ['Sadrži biljku maca, opisanu kao adaptogen.'],
        'approved_support_facts' => ['Maca dodatak namijenjen je energiji i rutini usmjerenoj na hormonalnu ravnotežu.'],
        'usage_facts' => ['Uzimati dvije kapsule dnevno.', 'Preporučuje se najmanje 30 dana prije procjene učinka.'],
        'safety_notes' => ['Ne preporučuje se trudnicama bez savjeta liječnika.'],
        'source_refs' => $source_refs(9),
    ],

    'vitolize-women' => [
        'canonical_match_signals' => ['Vitolize Women', 'Forever Vitolize Women'],
        'aliases' => ['vitolize women', 'forever vitolize women', 'ženski vitolize'],
        'use_cases' => ['ženska nutritivna rutina', 'rutina usmjerena na hormonalnu ravnotežu'],
        'ingredient_facts' => ['Ženska formulacija s biljnim ekstraktima i mineralima.'],
        'approved_support_facts' => ['Formulacija je namijenjena ženskoj rutini usmjerenoj na hormonalnu ravnotežu.'],
        'usage_facts' => ['Uzimati dvije kapsule dnevno.', 'U menopauzi koristiti kao podršku uz savjet stručnjaka.'],
        'safety_notes' => ['Nije zamjena za medicinsku terapiju.'],
        'source_refs' => $source_refs(9, 10),
    ],

    'vitolize-men' => [
        'canonical_match_signals' => ['Vitolize Men', 'Forever Vitolize Men'],
        'aliases' => ['vitolize men', 'forever vitolize men', 'muški vitolize'],
        'use_cases' => ['muška nutritivna rutina', 'vitalnost i ravnoteža'],
        'ingredient_facts' => ['Muška formulacija s biljnim ekstraktima i mineralima.'],
        'approved_support_facts' => ['Formulacija je namijenjena muškoj rutini vitalnosti i ravnoteže.'],
        'usage_facts' => ['Uzimati dvije kapsule dnevno.', 'Može se kombinirati s proizvodom Forever Multi Maca.'],
        'safety_notes' => ['Ne prekoračivati preporučenu dnevnu dozu.'],
        'source_refs' => $source_refs(10, 11),
    ],

    'c9-forever-living-products' => [
        'canonical_match_signals' => ['Clean 9 (C9)', 'C9 Forever Living Products'],
        'aliases' => ['clean 9', 'clean9', 'c9', 'c9 forever living products'],
        'use_cases' => ['devetodnevni strukturirani prehrambeni program', 'početak promjene prehrambenih navika', 'kontrola tjelesne mase'],
        'ingredient_facts' => [],
        'approved_support_facts' => ['Devetodnevni strukturirani program usmjeren je na prehrambene navike i kontrolu tjelesne mase.'],
        'usage_facts' => ['Slijediti detaljan devetodnevni plan unutar paketa.', 'Program se može ponoviti svakih četiri do šest tjedana.'],
        'safety_notes' => ['Tijekom programa povećati unos vode i pridržavati se uputa.'],
        'source_refs' => $source_refs(11),
    ],

    'forever-dx4' => [
        'canonical_match_signals' => ['DX4 Body Balancing System', 'Forever DX4'],
        'aliases' => ['dx4 body balancing system', 'forever dx4', 'dx4'],
        'use_cases' => ['četverodnevni strukturirani prehrambeni program', 'kratka rutina prehrambenih navika'],
        'ingredient_facts' => [],
        'approved_support_facts' => ['Četverodnevni program usmjeren je na kratku nutritivnu rutinu i prehrambene navike.'],
        'usage_facts' => ['Slijediti detaljan četverodnevni plan unutar pakiranja.', 'Može se koristiti u istoj široj rutini s C9, ali ne istovremeno.'],
        'safety_notes' => ['Održavati adekvatan unos tekućine.'],
        'source_refs' => $source_refs(11, 12),
    ],

    /* Legacy direct-product key; no matching published blog slug exists. */
    'vital5' => [
        'canonical_match_signals' => ['Vital5 Advanced Nutrition System', 'Vital5 paket'],
        'aliases' => ['vital5 advanced nutrition system', 'vital5', 'vital 5'],
        'use_cases' => ['tridesetodnevna temeljna nutritivna rutina', 'opća energija i vitalnost'],
        'ingredient_facts' => ['Sustav sadrži više dodataka prehrani.'],
        'approved_support_facts' => ['Mjesečni sustav namijenjen je temeljnoj nutritivnoj podršci.'],
        'usage_facts' => ['Slijediti tridesetodnevni raspored unutar paketa.', 'Predviđena je kontinuirana primjena uz preporučene doze.'],
        'safety_notes' => ['Ne kombinirati s drugim multivitaminskim kompleksima bez savjeta stručnjaka.'],
        'source_refs' => $source_refs(12),
    ],

    'forever-argi' => [
        'canonical_match_signals' => ['Forever Argi+', 'Forever ARGI+'],
        'aliases' => ['forever argi+', 'forever argi', 'argi+', 'argi plus'],
        'use_cases' => ['energijska rutina', 'rutina usmjerena na cirkulaciju'],
        'ingredient_facts' => ['Sadrži L-arginin i vitamine B skupine.'],
        'approved_support_facts' => ['Kombinacija je namijenjena rutini usmjerenoj na energiju i cirkulaciju.'],
        'usage_facts' => ['Pomiješati jednu vrećicu s vodom i uzeti jednom dnevno.', 'Preporučuje se uzimanje ujutro ili prije fizičke aktivnosti.'],
        'safety_notes' => ['Osobama s niskim krvnim tlakom ne preporučuje se bez savjeta stručnjaka.'],
        'source_refs' => $source_refs(13),
    ],

    'forever-therm' => [
        'canonical_match_signals' => ['Forever Therm'],
        'aliases' => ['forever therm', 'therm'],
        'use_cases' => ['energijska rutina', 'rutina usmjerena na metabolizam'],
        'ingredient_facts' => ['Sadrži biljne ekstrakte.', 'Sadrži kofein iz biljnih izvora.'],
        'approved_support_facts' => ['Biljna formulacija namijenjena je energiji i rutini usmjerenoj na metabolizam.'],
        'usage_facts' => ['Slijediti upute proizvoda; uobičajena količina je dvije do tri tablete dnevno.'],
        'safety_notes' => ['Ne uzimati navečer.'],
        'source_refs' => $source_refs(13, 14),
    ],

    'forever-ultra' => [
        'canonical_match_signals' => ['Forever Lite Ultra', 'Forever Ultra Lite'],
        'aliases' => ['forever lite ultra', 'forever ultra lite', 'forever ultra', 'proteinski napitak'],
        'use_cases' => ['dodatni unos proteina', 'prehrambeni plan za kontrolu tjelesne mase'],
        'ingredient_facts' => ['Proteinski napitak biljnog podrijetla.'],
        'approved_support_facts' => ['Napitak je namijenjen dodatnom unosu proteina i može se uklopiti u plan kontrole tjelesne mase.'],
        'usage_facts' => ['Pomiješati jednu do dvije mjerice dnevno s vodom ili mlijekom.', 'Može se koristiti kao zamjena za obrok samo u skladu s planom prehrane.'],
        'safety_notes' => ['Osigurati raznoliku prehranu.'],
        'source_refs' => $source_refs(14, 15),
    ],

    'aloe-activator' => [
        'canonical_match_signals' => ['Aloe Activator', 'Forever Aloe Activator'],
        'aliases' => ['aloe activator', 'forever aloe activator', 'aloe tonik'],
        'use_cases' => ['hidratacija kože', 'njega osjetljive kože', 'njega dehidrirane kože'],
        'ingredient_facts' => ['Visoko koncentrirani aloe tonik.'],
        'approved_support_facts' => ['Tonik je namijenjen hidrataciji i umirivanju kože.'],
        'usage_facts' => ['Nanijeti na blazinicu i utapkati ujutro i navečer.', 'Može se koristiti kao oblog pet do deset minuta.'],
        'safety_notes' => ['Samo za vanjsku upotrebu.'],
        'source_refs' => $source_refs(15, 16),
    ],

    'forever-hydrating-serum' => [
        'canonical_match_signals' => ['Forever Hydrating Serum'],
        'aliases' => ['forever hydrating serum', 'hydrating serum', 'hidratantni serum'],
        'use_cases' => ['hidratacija kože', 'njega dehidrirane kože', 'njega umorne kože'],
        'ingredient_facts' => ['Sadrži hijaluronsku kiselinu.'],
        'approved_support_facts' => ['Serum je namijenjen dubinskoj hidrataciji kože.'],
        'usage_facts' => ['Nanositi prije hidratantne kreme.', 'Može se kombinirati s drugim serumima.'],
        'safety_notes' => ['Samo za vanjsku uporabu.'],
        'source_refs' => $source_refs(16, 17),
    ],

    'infinite-by-forever-restoring-creme' => [
        'canonical_match_signals' => ['Infinite Restoring Crème', 'Infinite By Forever Restoring Creme'],
        'aliases' => ['infinite restoring crème', 'infinite restoring creme', 'infinite by forever restoring creme'],
        'use_cases' => ['hidratacija zrele kože', 'njega vidljivih znakova starenja'],
        'ingredient_facts' => [],
        'approved_support_facts' => ['Krema je namijenjena obnovi i hidrataciji zrele kože.'],
        'usage_facts' => ['Nanositi ujutro i navečer na čistu kožu.', 'Pogodna je za svakodnevnu uporabu.'],
        'safety_notes' => ['Izbjegavati kontakt s očima.'],
        'source_refs' => $source_refs(17, 18),
    ],

    'forever-aloe-body-lotion' => [
        'canonical_match_signals' => ['Aloe Body Lotion', 'Forever Aloe Body Lotion'],
        'aliases' => ['aloe body lotion', 'forever aloe body lotion', 'losion za tijelo'],
        'use_cases' => ['hidratacija tijela', 'njega suhe kože', 'njega osjetljive kože'],
        'ingredient_facts' => [],
        'approved_support_facts' => ['Hidratantna krema za tijelo namijenjena je suhoj i osjetljivoj koži.'],
        'usage_facts' => ['Nanijeti nakon tuširanja.', 'Može se koristiti svakodnevno.'],
        'safety_notes' => ['Samo za vanjsku uporabu.'],
        'source_refs' => $source_refs(18),
    ],

    'forever-aloe-cooling-lotion' => [
        'canonical_match_signals' => ['Aloe Cooling Lotion', 'Forever Aloe Cooling Lotion'],
        'aliases' => ['aloe cooling lotion', 'forever aloe cooling lotion', 'cooling lotion', 'hladeći losion'],
        'use_cases' => ['njega kože nakon fizičke aktivnosti', 'rutina za umorne mišiće', 'učinak hlađenja'],
        'ingredient_facts' => [],
        'approved_support_facts' => ['Losion pruža osvježavajući učinak hlađenja na željenom području.'],
        'usage_facts' => ['Umasirati na željeno područje.', 'Može se koristiti više puta dnevno prema potrebi.'],
        'safety_notes' => ['Izbjegavati otvorene rane.'],
        'source_refs' => $source_refs(18, 19),
    ],

    /* Legacy direct-product key; no matching published blog slug exists. */
    'heat_lotion' => [
        'canonical_match_signals' => ['Aloe Heat Lotion', 'Forever Aloe Heat Lotion'],
        'aliases' => ['aloe heat lotion', 'forever aloe heat lotion', 'heat lotion', 'zagrijavajući losion'],
        'use_cases' => ['rutina za napete mišiće', 'učinak zagrijavanja'],
        'ingredient_facts' => [],
        'approved_support_facts' => ['Losion pruža lagani zagrijavajući učinak i namijenjen je rutini opuštanja napetih mišića.'],
        'usage_facts' => ['Nanijeti dva do tri puta dnevno.'],
        'safety_notes' => ['Ne koristiti na osjetljivoj koži.'],
        'source_refs' => $source_refs(19, 20),
    ],

    'forever-bright' => [
        'canonical_match_signals' => ['Forever Bright Toothgel', 'Forever Bright'],
        'aliases' => ['forever bright toothgel', 'forever bright', 'aloe toothgel', 'zubni gel'],
        'use_cases' => ['svakodnevna oralna higijena', 'oralna rutina bez fluora'],
        'ingredient_facts' => ['Sadrži aloe veru.', 'Zubni gel bez fluora.'],
        'approved_support_facts' => ['Zubni gel namijenjen je svakodnevnoj oralnoj higijeni bez fluora.'],
        'usage_facts' => ['Koristiti dva do tri puta dnevno.'],
        'safety_notes' => ['Ne gutati.'],
        'source_refs' => $source_refs(20),
    ],

    'aloe-ever-shield' => [
        'canonical_match_signals' => ['Aloe Ever-Shield Deodorant', 'Forever Aloe Ever Shield'],
        'aliases' => ['aloe ever-shield deodorant', 'forever aloe ever shield', 'ever-shield deodorant', 'forever deodorant'],
        'use_cases' => ['svakodnevna higijena', 'dezodorans za osjetljivu kožu'],
        'ingredient_facts' => ['Dezodorans bez aluminija.'],
        'approved_support_facts' => ['Dezodorans bez aluminija namijenjen je osjetljivoj koži.'],
        'usage_facts' => ['Nanijeti na čistu kožu.'],
        'safety_notes' => ['Samo za vanjsku uporabu.'],
        'source_refs' => $source_refs(20, 21),
    ],

    'forever-aloe-lips' => [
        'canonical_match_signals' => ['Aloe Lips', 'Forever Aloe Lips'],
        'aliases' => ['aloe lips', 'forever aloe lips', 'aloe balzam za usne'],
        'use_cases' => ['njega suhih usana', 'njega ispucalih usana', 'preventivna njega usana'],
        'ingredient_facts' => ['Balzam za usne s aloe verom.'],
        'approved_support_facts' => ['Balzam je namijenjen njezi suhih i ispucalih usana.'],
        'usage_facts' => ['Nanijeti više puta dnevno.', 'Može se koristiti preventivno.'],
        'safety_notes' => ['Samo za vanjsku uporabu.'],
        'source_refs' => $source_refs(21, 22),
    ],

    'forever-aloe-first-spray' => [
        'canonical_match_signals' => ['Aloe First', 'Forever Aloe First Spray'],
        'aliases' => ['aloe first', 'forever aloe first', 'forever aloe first spray', 'aloe sprej'],
        'use_cases' => ['njega nadražene kože', 'njega osjetljive kože', 'njega kože nakon sunčanja'],
        'ingredient_facts' => ['Sprej s aloe verom i biljnim ekstraktima.'],
        'approved_support_facts' => ['Sprej je namijenjen vanjskoj njezi nadražene i osjetljive kože.'],
        'usage_facts' => ['Raspršiti na kožu prema potrebi.', 'Može se koristiti nakon sunčanja.'],
        'safety_notes' => ['Izbjegavati kontakt s očima.'],
        'source_refs' => $source_refs(22),
    ],
];

return array_map(
    static fn(array $facts): array => ['hr' => $facts],
    $verified_facts_hr
);
