<?php

/* Current educational profiles keep historical source transcriptions intact. */
$component = static function (string $name, string $explanation, string $evidence, array $sourceIds): array {
    return ['name' => $name, 'explanation' => $explanation, 'evidence' => $evidence, 'source_ids' => $sourceIds];
};
$useCase = static function (string $id, string $title, string $context, string $role, string $evidence, string $boundary, array $questions, string $whenRefer, array $sourceIds): array {
    return ['id' => $id, 'title' => $title, 'context' => $context, 'role' => $role, 'evidence' => $evidence, 'boundary' => $boundary, 'questions' => $questions, 'when_refer' => $whenRefer, 'source_ids' => $sourceIds];
};

return [
    'updated_at' => '2026-10-07',
    'products' => [
        'forever-multi-maca' => [
            'topic' => 'Menopauza, raspoloženje i biljna formula s macom',
            'category' => 'Dodaci prehrani',
            'category_key' => 'nutritional',
            'route' => 'oral',
            'catalog_scope' => 'current_inventory',
            'source_verified_at' => '2026-10-07',
            'learning_intro' => 'Multi Maca povezana je s preporukom za tegobe menopauze, posebno valunge, pojačano znojenje i promjene raspoloženja. Suradnik upoznaje macu i višekomponentnu biljnu formulu te uči razgovarati o svakodnevnom funkcioniranju, snu i osobnom praćenju tegoba.',
            'active_components' => [
                $component('Maca', 'Maca je biljka čiji se korijen koristi u prehrani i dodacima. Kod ove teme proučavamo istraživanja raspoloženja i menopauzalnih tegoba te uspoređujemo što je točno istraženo.', 'U malim pokusima različitih maca pripravaka zabilježena su poboljšanja pojedinih psiholoških i menopauzalnih simptoma. Ti pripravci nisu Forever Multi Maca.', ['maca-brooks-2008', 'maca-go-menopause-2006', 'maca-stojanovska-2015']),
                $component('Biljna kombinacija', 'Formula uz macu navodi Tribulus terrestris, Muira Puamu, Catuabu, Saw Palmetto i Pygeum africanum. To je više biljnih sastojaka u istoj tableti, pa uspoređujemo cijelu formulu s drugim proizvodima.', 'Službeni popis sastojaka potvrđuje prisutnost biljaka, bez količina svakog pojedinog sastojka u dostupnom tekstu.', ['forever-multi-maca-hr', 'forever-multi-maca-mkd']),
                $component('L arginin i koenzim Q10', 'Službeni sastav navodi i L arginin te koenzim Q10. Suradnik uči razlikovati pojedini sastojak od cijele kombinacije i prepoznati podatak koji je važan za usporedbu: količinu po dnevnoj porciji.', 'Prisutnost sastojaka potvrđena je popisom. Njihove pojedinačne količine i učinak gotove formule na menopauzalne tegobe nisu potvrđeni ovim profilom.', ['forever-multi-maca-hr', 'forever-multi-maca-mkd']),
            ],
            'use_cases' => [
                $useCase(
                    'multi-maca-menopauza',
                    'Menopauza, valunzi i pojačano znojenje',
                    'Multi Maca preporučuje se kod menopauzalnih tegoba poput valunga i pojačanog znojenja. U razgovoru utvrdi koliko tegobe ometaju dan i san.',
                    'Upoznaj sastav, zabilježi učestalost tegoba prije uvođenja rutine i pouči osobu jednostavnom praćenju promjena.',
                    'Istraživanje posebnog pripravka Maca GO bilježilo je promjene valunga i noćnog znojenja. Primijenjena je drukčija formula od ovog proizvoda.',
                    'Podaci o istraženoj maci služe razumijevanju sastojka, a osobno praćenje ne dokazuje uzrok promjene.',
                    ['Koliko često se pojavljuju valunzi i noćno znojenje?', 'Ometaju li san, posao ili svakodnevne aktivnosti?', 'Koji su drugi dodaci ili terapije već u uporabi?'],
                    'Jako izražene tegobe, neobjašnjivo znojenje ili krvarenje nakon menopauze traže liječničku procjenu. Uporabu uz postojeće zdravstvene probleme i lijekove prethodno provjeriti sa stručnjakom.',
                    ['maca-go-menopause-2006', 'forever-multi-maca-hr']
                ),
                $useCase(
                    'multi-maca-raspolozenje',
                    'Promjene raspoloženja i svakodnevno funkcioniranje',
                    'Uz preporuku za menopauzalne tegobe razgovaramo o raspoloženju, osjećaju energije i kvaliteti sna.',
                    'Poveži razgovor o proizvodu s redovitim snom, obrocima, kretanjem i konkretnim promjenama koje osoba želi pratiti.',
                    'Dva mala ispitivanja maca pripravaka zabilježila su poboljšanja nekih psiholoških pokazatelja. Nisu utvrdila promjene istraženih spolnih hormona.',
                    'Istraživanja ne potvrđuju uklanjanje simptoma gotovim proizvodom niti hormonalni učinak Multi Mace.',
                    ['Koji dio dana je najteži?', 'Jesu li promjene raspoloženja povezane s lošim snom?', 'Koju bi jednu promjenu bilo korisno redovito bilježiti?'],
                    'Trajno sniženo raspoloženje ili tjeskobu koja ometa funkcioniranje treba procijeniti stručnjak.',
                    ['maca-brooks-2008', 'maca-stojanovska-2015', 'forever-multi-maca-hr']
                ),
                $useCase(
                    'multi-maca-kombinacija',
                    'Odvojiti tegobe menopauze od nutritivne potrebe kostiju',
                    'Multi Maca preporučuje se kod menopauzalnih tegoba, a Calcium i Absorbent D za nadopunu kalcija i vitamina D važnih za kosti.',
                    'Multi Maca pripada razgovoru o opisanim tegobama. Calcium i Absorbent D razmatramo prema stvarnom unosu iz hrane i ostalih dodataka.',
                    'Kalcij i vitamin D imaju utvrđene nutritivne uloge. Ova kombinacija sama po sebi nije klinički istražen protokol menopauze.',
                    'Odabir više proizvoda ne znači da svaka osoba treba cijeli paket. Ukupan unos kalcija i vitamina D promatramo kroz sve izvore.',
                    ['Unosi li osoba kalcij hranom?', 'Sadrže li drugi proizvodi vitamin D?', 'Postoji li stručna preporuka zbog koštanog zdravlja?'],
                    'Kod osteoporoze, bolesti bubrega ili propisane terapije sastav rutine uskladiti sa stručnjakom.',
                    ['nih-calcium', 'nih-vitamin-d', 'forever-multi-maca-hr']
                ),
            ],
            'conversation_example' => 'U našoj edukativnoj preporuci za menopauzu uključujemo Multi Macu, posebno kada su tema valunzi, znojenje i raspoloženje. Prvo utvrdimo što vas najviše ometa i što ćete pratiti. Za kalcij i vitamin D odvojeno pogledamo prehranu i proizvode koje već uzimate.',
            'takeaways' => [
                'Multi Maca je glavni proizvod u preporuci P05 za menopauzalne tegobe.',
                'Maca u istraživanjima i gotova višekomponentna formula imaju različit sastav.',
                'Praćenje valunga, sna i raspoloženja pomaže konkretnom razgovoru.',
                'Potrebu za kalcijem i vitaminom D procjenjujemo zasebno.',
            ],
            'study_questions' => [
                'Kod kojih se menopauzalnih tegoba preporučuje Multi Maca?',
                'Koji se sastojci u formuli nalaze uz macu?',
                'Kako razlikovati istraživanje maca pripravka od dokaza o gotovom proizvodu?',
                'Zašto kalcij i vitamin D nisu automatski potrebni u istoj kombinaciji?',
            ],
            'composition_source_facts' => [
                'Višekomponentna formula s macom, drugim biljnim sastojcima, L argininom i koenzimom Q10.',
                'Službeni HR popis navodi sastojke iz soje.',
            ],
            'composition_source_status' => 'official_source_checked',
            'composition_source_title' => 'Službeni Forever HR popis sastojaka, provjeren 7. listopada 2026.',
            'composition_source_metadata' => [
                'url' => 'https://foreverliving.com/shop/hrv/hr-HR/products/nutritional/215-FOREVER-MULTI-MACA',
                'market' => 'hr',
                'language' => 'hr',
                'checked_at' => '2026-10-07',
                'scope' => 'ingredient_list_and_usage',
            ],
            'source_ids' => ['forever-multi-maca-hr', 'forever-multi-maca-mkd', 'maca-brooks-2008', 'maca-go-menopause-2006', 'maca-stojanovska-2015', 'nih-calcium', 'nih-vitamin-d'],
        ],
        'forever-fiber-fusion' => [
            'product_key' => 'forever-fiber-fusion',
            'name' => 'Forever Fiber Fusion',
            'topic' => 'Vlakna u gotovom žele sticku',
            'category' => 'Dodaci prehrani',
            'category_key' => 'nutritional',
            'route' => 'oral',
            'catalog_scope' => 'current_inventory',
            'family_type' => 'product_family',
            'source_verified_at' => '2026-10-07',
            'lesson_ids' => [],
            'learning_intro' => 'Novi Forever Fiber Fusion omogućuje dopunu dnevnog unosa vlakana u gotovom žele sticku s okusom jabuke. Edukaciju počinjemo dnevnim obrocima, redovitošću stolice i podnošljivošću promjene prehrane. Sadržaj se temeljito žvače, pa se razlikuje od starog praška koji se miješao s hranom ili napitkom.',
            'active_components' => [
                $component('Topiva kukuruzna vlakna', 'Topiva kukuruzna vlakna dio su mješavine koja dopunjuje ukupan dnevni unos. Različite vrste vlakana mogu imati različita svojstva, zato sastav povezujemo s osobnom podnošljivošću.', 'Službeni sastav potvrđuje vrstu vlakna. Doprinos cijele porcije učimo prema nutritivnoj tablici.', ['forever-fiber-fusion-official-se', 'nih-fiber']),
                $component('Psyllium te vlakna jabuke i citrusa', 'Psyllium potječe od ljuskica sjemenki biljke Plantago ovata. Uz njega formula navodi vlakna jabuke i citrusa. Njihova kombinacija pripada ukupnom unosu vlakana i teksturi proizvoda.', 'Količine pojedinačnih vlakana nisu jednake ukupnoj količini mješavine. Nije opravdano prenositi dozu istraženog čistog psylliuma na ovaj proizvod.', ['forever-fiber-fusion-official-se', 'nih-fiber']),
                $component('Cink', 'Cink sudjeluje u radu enzima, normalnom metabolizmu makronutrijenata i normalnoj imunološkoj funkciji. U ovoj edukaciji povezuje vlakna s dodatnim nutritivnim doprinosom.', 'Utvrđene funkcije hranjive tvari. Ukupan unos obuhvaća i druge proizvode.', ['nih-zinc', 'eu-claims-regulation']),
                $component('Koncentrat unutarnjeg gela aloe', 'Formula navodi koncentrat unutarnjeg gela lista aloe. Suradnik prepoznaje taj dio biljke i povezuje ga s odvojenom lekcijom o sastavu aloe.', 'Popis sastojaka potvrđuje prisutnost. Ne određuje količinu acemannana niti potvrđuje učinak koncentrata na određenu bolest.', ['forever-fiber-fusion-official-se', 'nih-aloe']),
            ],
            'use_cases' => [
                $useCase(
                    'fiber-fusion-dnevni-unos',
                    'Nedovoljan unos vlakana u svakodnevnoj prehrani',
                    'Osoba rijetko jede mahunarke, cjelovite žitarice, povrće ili voće i želi praktično dopuniti unos vlakana.',
                    'Usporedi što već dolazi iz obroka i gdje se gotovi stick uklapa u dan. Zadrži raznolikost namirnica kao osnovu prehrane.',
                    'Opće preporuke prehrane podupiru primjeren unos vlakana. Konkretan doprinos ovog proizvoda utvrđuje deklarirana porcija.',
                    'Dodatak vlakana dopunjuje prehranu. Sâm broj sastojaka ne određuje potrebu za proizvodom.',
                    ['Koji obrok najčešće sadrži povrće ili mahunarke?', 'Postoji li praktična prepreka redovitim obrocima?', 'Koje izvore vlakana osoba dobro podnosi?'],
                    'Kod posebne medicinske prehrane ili ograničenja tekućine odabir uskladiti sa stručnjakom. Hrvatska uputa traži liječnički savjet uz lijekove i ne preporučuje primjenu kod dijabetesa, abdominalne boli, ileusa, rektalnog krvarenja ni otežanog gutanja.',
                    ['forever-fiber-fusion-hr', 'forever-fiber-fusion-official-se', 'nih-fiber']
                ),
                $useCase(
                    'fiber-fusion-stolica',
                    'Redovitost stolice uz mali unos vlakana',
                    'Vlakna, odgovarajuća tekućina, kretanje i redovite navike pražnjenja dio su razgovora o probavnoj rutini.',
                    'Poveži prehranu s praćenjem učestalosti i tvrdoće stolice. Procijeni podnošljivost dopune i prati promjenu ukupnog unosa.',
                    'NIDDK opisuje prehrambene navike koje mogu pomoći kod zatvora. To je opći prehrambeni okvir, a ne istraživanje ovog proizvoda.',
                    'Za novu formulu primjenjujemo novu uputu. Poveznice starog Forever Fibera u izvornoj matrici ostaju povijesni zapisi.',
                    ['Je li unos vlakana prethodno bio nizak?', 'Je li promjena pražnjenja nova?', 'Ima li osoba bolove u trbuhu ili neku od napomena na lokalnom pakiranju?'],
                    'Krv u stolici, stalna bol, povraćanje ili nemogućnost ispuštanja plinova zahtijevaju odmah liječnika.',
                    ['nih-fiber', 'nih-constipation-symptoms']
                ),
                $useCase(
                    'fiber-fusion-podnosljivost',
                    'Postupna promjena prehrane i praćenje nadutosti',
                    'Nagli porast ukupnog unosa vlakana može promijeniti stvaranje plinova. Osobna podnošljivost usmjerava razgovor.',
                    'Povećavanje vlakana iz cijele prehrane planiramo postupno, bilježimo simptome i uspoređujemo ih s obrocima.',
                    'Postupnost proizlazi iz općih prehrambenih smjernica. Individualne reakcije mogu se razlikovati.',
                    'Postupnost prehrane nije samostalna promjena deklarirane porcije proizvoda. Nadutost ne tumačimo kao dokaz detoksikacije.',
                    ['Je li u isto vrijeme uvedeno više novih namirnica ili dodataka?', 'Pojavljuje li se nadutost nakon određenog obroka?', 'Koja promjena olakšava praćenje podnošljivosti?'],
                    'Trajna ili jaka nadutost, bol i neobjašnjiv gubitak težine traže zdravstvenu procjenu.',
                    ['nih-fiber', 'nih-gas']
                ),
            ],
            'conversation_example' => 'Novi Fiber Fusion gotov je za žvakanje. Njegovu dopunu vlakana povezujemo s vašim obrocima i navikama pražnjenja. Prvo pogledamo koliko često jedete biljne izvore vlakana, a zatim pratimo što vam odgovara.',
            'takeaways' => [
                'Fiber Fusion je nova formula i novi način uporabe.',
                'Stick se temeljito žvače, ne priprema se kao stari prašak.',
                'Ukupan unos vlakana uključuje hranu i dodatke.',
                'Povijesne preporuke starog Fibera ne prenose se automatski na novu formulu.',
            ],
            'study_questions' => [
                'Kako se novi Fiber Fusion uzima u odnosu na stari Forever Fiber?',
                'Koje vrste vlakana navodi novi sastav?',
                'Kako povezati gram vlakana iz proizvoda s unosom iz obroka?',
                'Zašto se prije primjene povijesne preporuke provjerava nova formula?',
            ],
            'composition_source_facts' => [
                'Pakiranje sadrži 30 stickova po 29 g. Jedna porcija daje 8 g vlakana i 1,5 mg cinka.',
                'Sadrži topiva kukuruzna vlakna, psyllium, vlakna jabuke i citrusa te koncentrat unutarnjeg gela aloe.',
                'Gotovi žele s okusom jabuke uzima se temeljitim žvakanjem sadržaja jednog sticka dnevno.',
            ],
            'composition_source_status' => 'official_source_checked',
            'composition_source_title' => 'Službeni Forever izvor za Fiber Fusion, provjeren 7. listopada 2026.',
            'composition_source_metadata' => [
                'url' => 'https://foreverliving.com/shop/swe/en-se/products/nutritional/702-FOREVER-FIBER-FUSION',
                'market' => 'se',
                'language' => 'en',
                'checked_at' => '2026-10-07',
                'scope' => 'ingredient_list_nutrition_and_usage',
                'hr_usage_source_url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/702-FOREVER-FIBER-FUSION',
                'hr_retrieval_method' => 'web_search_returned_full_indexed_primary_page',
                'hr_crawl_recency_reported' => '3 weeks ago',
            ],
            'identity_note' => 'Aktualni proizvod #702. Edukativna kartica razlikuje njegovu formulu od povijesnog Forever Fibera.',
            'replacement_for_product_key' => 'forever-fiber',
            'aliases' => ['Forever Fiber Fusion', 'Fiber Fusion'],
            'source_ids' => ['forever-fiber-fusion-hr', 'forever-fiber-fusion-official-se', 'nih-fiber', 'nih-gas', 'nih-constipation-symptoms', 'nih-zinc', 'nih-aloe', 'eu-claims-regulation'],
        ],
        'forever-fiber' => [
            'catalog_scope' => 'legacy_only',
            'identity_note' => 'Povijesni Forever Fiber u prahu. U aktualnoj preporuci zamjenjuje ga Forever Fiber Fusion.',
            'replacement_product_key' => 'forever-fiber-fusion',
            'replacement_note' => 'Za aktualnu edukativnu primjenu otvori karticu Forever Fiber Fusion. Novi proizvod ima drukčiju formulu, gotovi žele stick i vlastitu uputu za uporabu. Izvorna matrica ostaje sačuvana u izvornom obliku.',
        ],
    ],
    'sources' => [
        'forever-multi-maca-hr' => [
            'title' => 'Forever Hrvatska: Multi Maca, sastav i uporaba',
            'url' => 'https://foreverliving.com/shop/hrv/hr-HR/products/nutritional/215-FOREVER-MULTI-MACA',
            'kind' => 'Službeni izvor kompanije Forever Living Products za hrvatsko tržište',
            'checked_at' => '2026-10-07',
        ],
        'forever-multi-maca-mkd' => [
            'title' => 'Forever: popis sastojaka Multi Mace na drugom tržištu',
            'url' => 'https://foreverliving.com/shop/mkd/en-gb/products/215-FOREVER-MULTI-MACA',
            'kind' => 'Dodatni službeni izvor sastava, drugo tržište',
            'checked_at' => '2026-10-07',
        ],
        'maca-brooks-2008' => [
            'title' => 'Brooks 2008: maca i psihološki simptomi nakon menopauze',
            'url' => 'https://pubmed.ncbi.nlm.nih.gov/18784609/',
            'kind' => 'Malo randomizirano istraživanje posebnog maca pripravka',
            'checked_at' => '2026-10-07',
        ],
        'maca-go-menopause-2006' => [
            'title' => 'Maca GO: istraživanje valunga i noćnog znojenja',
            'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC3614647/',
            'kind' => 'Izvorno istraživanje drugog maca pripravka',
            'checked_at' => '2026-10-07',
        ],
        'maca-stojanovska-2015' => [
            'title' => 'Stojanovska 2015: pilot istraživanje mace nakon menopauze',
            'url' => 'https://pubmed.ncbi.nlm.nih.gov/24931003/',
            'kind' => 'Malo randomizirano istraživanje posebnog maca pripravka',
            'checked_at' => '2026-10-07',
        ],
        'forever-fiber-fusion-official-se' => [
            'title' => 'Forever Fiber Fusion: formula, količine i žvakanje sticka',
            'url' => 'https://foreverliving.com/shop/swe/en-se/products/nutritional/702-FOREVER-FIBER-FUSION',
            'kind' => 'Službeni izvor kompanije Forever Living Products za drugo tržište',
            'checked_at' => '2026-10-07',
        ],
        'forever-fiber-fusion-hr' => [
            'title' => 'Forever Hrvatska: Fiber Fusion, pakiranje i uputa',
            'url' => 'https://foreverliving.com/shop/hrv/hr-hr/products/702-FOREVER-FIBER-FUSION',
            'kind' => 'Službeni hrvatski izvor, indeksirani tekst',
            'checked_at' => '2026-10-07',
            'retrieval_method' => 'web_search_returned_full_indexed_primary_page',
            'crawl_recency_reported' => '3 weeks ago',
        ],
    ],
];
