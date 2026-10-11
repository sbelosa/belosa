<?php

namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class FccGrowth extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        $sets = [
            [
                'slug' => 'products',
                'eyebrow' => 'Gotovi set 01',
                'title' => 'SET ZA PRODAJU PROIZVODA',
                'subtitle' => 'Ako želiš više upita za proizvode i više razgovora koji vode prema kupnji, kreni s ovim setom.',
                'focus' => 'Ovaj set vodi osobu od interesa do poruke ili ostavljenog kontakta.',
                'who' => [
                    'Imaš kontakte koji pitaju što bi im pomoglo za energiju, probavu, detox ili općenito zdravlje.',
                    'Do sada si slao shop link ili generički biolink i razgovori su se gubili.',
                    'Želiš jednostavan sustav koji brzo pokrećeš i odmah počinješ koristiti.'
                ],
                'steps' => [
                    [
                        'number' => '1',
                        'title' => 'Otvori svoj biolink za proizvode',
                        'why' => 'Jedan biolink mora imati samo jedan cilj. Ovdje je cilj otvoriti razgovor o proizvodu, ne slati osobu na deset različitih mjesta.',
                        'action' => 'Idi u biolinkove i odaberi stranicu koju ćeš koristiti samo za preporuku proizvoda.',
                        'blocks' => [
                            'Avatar ili osobna fotografija',
                            'Naslov s jasnom koristi',
                            'Kratki uvod od 2 do 3 rečenice',
                            '3 gumba po interesu',
                            '1 kratki video',
                            'WhatsApp gumb i collector blok'
                        ]
                    ],
                    [
                        'number' => '2',
                        'title' => 'Postavi tekst koji odmah otvara interes',
                        'why' => 'Ljudi rijetko kupuju kada vide puno proizvoda odjednom. Puno bolje reagiraju kada osjete da će dobiti preporuku za svoj cilj.',
                        'action' => 'Kopiraj gotov naslov, uvod i CTA tekstove iz ovog odjeljka i postavi ih bez dodatnog kompliciranja.',
                        'blocks' => [
                            'Naslov: Koji Forever proizvod najbolje odgovara tvom cilju?',
                            'Uvod: Javi mi što želiš riješiti i poslat ću ti najjednostavniju preporuku.',
                            'CTA 1: Želim preporuku na WhatsApp',
                            'CTA 2: Ostavi kontakt da ti se javim'
                        ]
                    ],
                    [
                        'number' => '3',
                        'title' => 'Dodaj samo tri ulaza po interesu',
                        'why' => 'Kada osobi ponudiš previše opcija, ne odabere ništa. Tri jasna puta su lakša za klik i za razgovor.',
                        'action' => 'Postavi tri gumba: Detox i lagan start, Energija i svakodnevni ritam, Opće zdravlje i wellness.',
                        'blocks' => [
                            'Detox i lagan start',
                            'Energija i svakodnevni ritam',
                            'Opće zdravlje i wellness'
                        ]
                    ],
                    [
                        'number' => '4',
                        'title' => 'Uključi i WhatsApp i collector',
                        'why' => 'Neki će ljudi odmah poslati poruku, a neki će radije ostaviti kontakt. Ako imaš samo WhatsApp, gubiš dio leadova.',
                        'action' => 'Na dnu stranice postavi glavni WhatsApp gumb i odmah ispod collector za ime i broj ili email.',
                        'blocks' => [
                            'Primarni CTA: Pošalji mi poruku',
                            'Sekundarni CTA: Ostavi kontakt i javljam ti se',
                            'Collector polja: ime + broj ili email'
                        ]
                    ],
                    [
                        'number' => '5',
                        'title' => 'Objavi i odmah kreni dijeliti',
                        'why' => 'Najveća greška je čekati savršenstvo. Ovaj set treba početi raditi isti dan da što prije dobiješ prve razgovore.',
                        'action' => 'Objavi biolink i šalji ga samo ljudima kojima treba preporuka proizvoda, ne svima istu poruku.',
                        'blocks' => [
                            'Pošalji ga na WhatsApp kontaktima koji su već nekad pokazali interes',
                            'Stavi ga u story s jednostavnim pozivom na javljanje',
                            'Šalji ga nakon razgovora umjesto generičnog shop linka'
                        ]
                    ],
                ],
                'copy' => [
                    'headline' => 'Koji Forever proizvod najbolje odgovara tvom cilju?',
                    'subheadline' => 'Ne moraš sam birati. Javi mi svoj cilj i poslat ću ti jednostavnu preporuku koja ti može najviše odgovarati.',
                    'buttons' => [
                        'Detox i lagan start',
                        'Energija i svakodnevni ritam',
                        'Opće zdravlje i wellness'
                    ],
                    'whatsapp' => 'Bok! Pošalji mi što želiš postići i rado ću ti preporučiti proizvod s kojim je najbolje krenuti.',
                    'collector_intro' => 'Ako ti je lakše, ostavi kontakt i javim ti se s prijedlogom koji je najjednostavniji za tvoj cilj.',
                    'follow_up' => 'Bok! Hvala ti što si ostavio kontakt. Javljam ti se da ti preporučim najjednostavniji proizvod za ono što želiš postići. Samo mi napiši što ti je trenutno najvažnije.'
                ],
                'daily_actions' => [
                    'Pošalji biolink na 5 do 10 ljudi koji su već nekad pokazali interes za proizvod.',
                    'Objavi jedan story s jednostavnim pitanjem i linkom na ovaj set.',
                    'Provjeri tko je kliknuo, tko se javio i tko je ostavio kontakt.',
                    'Javi se svima koji su ostavili kontakt unutar 24 sata.'
                ],
                'links' => [
                    [
                        'title' => 'Otvori biolinkove',
                        'url' => url('links?type=biolink'),
                        'icon' => 'fas fa-link'
                    ],
                    [
                        'title' => 'Provjeri kontakte',
                        'url' => url('data'),
                        'icon' => 'fas fa-address-book'
                    ]
                ]
            ],
            [
                'slug' => 'recruitment',
                'eyebrow' => 'Gotovi set 02',
                'title' => 'SET ZA SURADNJU I PRIJAVU INTERESA',
                'subtitle' => 'Ako želiš više kvalitetnih razgovora o suradnji, koristi ovaj set umjesto generičke prezentacije svega što FCC ima.',
                'focus' => 'Ovaj set vodi osobu od znatizelje do razgovora o tome kako bi suradnja mogla izgledati za nju.',
                'who' => [
                    'Imaš ljude koji pitaju kako funkcionira Forever i može li se raditi uz posao ili obitelj.',
                    'Želiš prestati slati preduge objašnjavajuće poruke i imati jednostavan put do razgovora.',
                    'Trebaju ti ozbiljniji upiti, a ne samo znatiželjni klikovi bez nastavka.'
                ],
                'steps' => [
                    [
                        'number' => '1',
                        'title' => 'Otvori poseban biolink samo za suradnju',
                        'why' => 'Osoba koja razmišlja o suradnji ne smije završiti na stranici s proizvodima, shopom i drugim izlazima. Treba jasan put do razgovora.',
                        'action' => 'Napraviš jedan biolink koji koristiš isključivo kada nekome predstavljaš suradnju.',
                        'blocks' => [
                            'Naslov s jasnom koristi',
                            'Tri koristi suradnje',
                            'Sekcija Što dobivaš u prvih 7 dana',
                            'Kratki video ili objašnjenje',
                            'CTA za razgovor i collector'
                        ]
                    ],
                    [
                        'number' => '2',
                        'title' => 'Pričaj o koristi, ne o svim detaljima',
                        'why' => 'Većina ljudi ne reagira na opsežna objašnjenja, ali reagira kada odmah vide kako bi im sustav mogao olakšati početak.',
                        'action' => 'Kopiraj gotove tekstove koji pokazuju jednostavnost, podršku i jasan prvi korak.',
                        'blocks' => [
                            'Korist 1: gotov digitalni sustav',
                            'Korist 2: jasan prvi korak i edukacija',
                            'Korist 3: lakše predstavljanje proizvoda i suradnje'
                        ]
                    ],
                    [
                        'number' => '3',
                        'title' => 'Makni strah kroz prvih 7 dana',
                        'why' => 'Ljudi se najčešće ne boje posla, nego nejasnog početka. Kada vide što ih čeka prvi tjedan, lakše se odluče za razgovor.',
                        'action' => 'U sredini stranice dodaj blok Što dobivaš u prvih 7 dana s jasnim kratkim točkama.',
                        'blocks' => [
                            'Dan 1: postavljanje profila i linka',
                            'Dan 2: razumijevanje proizvoda i poruke',
                            'Dan 3 do 7: prve objave, prvi razgovori i podrška'
                        ]
                    ],
                    [
                        'number' => '4',
                        'title' => 'Pozovi na kratak razgovor, ne na veliku odluku',
                        'why' => 'Ljudi lakše pristaju na kratak pregled sustava nego na veliku odluku odmah. To snižava otpor i povećava broj odgovora.',
                        'action' => 'Glavni CTA neka bude razgovor od 10 do 15 minuta, a odmah ispod stavi collector za one koji se još ne žele javiti odmah.',
                        'blocks' => [
                            'Glavni CTA: Želim vidjeti kako sustav radi',
                            'Sekundarni CTA: Ostavi kontakt za kratko predstavljanje',
                            'Collector polja: ime + broj ili email'
                        ]
                    ],
                    [
                        'number' => '5',
                        'title' => 'Šalji ovaj set samo ljudima za koje suradnja ima smisla',
                        'why' => 'Kada isti link šalješ svima, rezultati padaju. Ovaj set radi najbolje kada ga šalješ ciljano ljudima koji traže dodatni prihod, fleksibilnost ili novi početak.',
                        'action' => 'Koristi ga u privatnim razgovorima, nakon pitanja o poslu i u story objavama koje pozivaju na kratak pregled sustava.',
                        'blocks' => [
                            'Pošalji nakon razgovora o dodatnom prihodu',
                            'Objavi story s pitanjem Tko bi htio vidjeti gotov sustav za početak?',
                            'Javi se svakome tko ostavi kontakt isti ili sljedeći dan'
                        ]
                    ],
                ],
                'copy' => [
                    'headline' => 'Želiš vidjeti kako izgleda Forever posao uz gotov digitalni sustav?',
                    'subheadline' => 'Ako tražiš jednostavniji početak, jasne korake i alat koji ti olakšava predstavljanje, pokaži mi interes i rado ću ti pokazati kako to izgleda u praksi.',
                    'buttons' => [
                        'Želim vidjeti kako sustav radi',
                        'Što dobivam u prvih 7 dana',
                        'Ostavi kontakt za predstavljanje'
                    ],
                    'whatsapp' => 'Bok! Ako želiš, mogu ti pokazati kako izgleda Forever posao uz gotov digitalni sustav i koje prve korake prolazimo na početku.',
                    'collector_intro' => 'Ako želiš da ti se javim i ukratko objasnim kako sve funkcionira, ostavi kontakt i javim ti se bez obaveze.',
                    'follow_up' => 'Bok! Hvala ti što si ostavio kontakt za predstavljanje suradnje. Javljam ti se da ti ukratko pokažem kako izgleda početak, što dobivaš i kako ti FCC može olakšati prve korake.'
                ],
                'daily_actions' => [
                    'Pošalji ovaj set na 3 do 5 ljudi koji su pokazali interes za dodatni prihod ili fleksibilan rad.',
                    'Objavi jedan story ili status s pozivom na kratki pregled sustava.',
                    'Provjeri tko se javio, tko je kliknuo i tko je ostavio kontakt.',
                    'Dogovori kratak razgovor sa svima koji su pokazali interes.'
                ],
                'links' => [
                    [
                        'title' => 'Otvori biolinkove',
                        'url' => url('links?type=biolink'),
                        'icon' => 'fas fa-link'
                    ],
                    [
                        'title' => 'Forever edukacija',
                        'url' => url('fcc-education?video=last'),
                        'icon' => 'fas fa-graduation-cap'
                    ]
                ]
            ],
        ];

        $start_here = [
            'title' => 'Kako krenuti već danas',
            'items' => [
                'Ako želiš više upita za proizvode, odaberi SET ZA PRODAJU PROIZVODA.',
                'Ako želiš više razgovora o suradnji, odaberi SET ZA SURADNJU I PRIJAVU INTERESA.',
                'Nemoj raditi oba seta odjednom ako tek krećeš. Izaberi jedan, objavi ga danas i počni ga slati odmah.'
            ]
        ];

        $implementation = [
            [
                'title' => '1. Odaberi jedan set',
                'description' => 'Izaberi samo jedan cilj za danas. Tako ćeš brže objaviti i prije dobiti prve rezultate.'
            ],
            [
                'title' => '2. Kopiraj gotove tekstove',
                'description' => 'Nemoj izmišljati tekst od nule. Tekstovi ispod su napravljeni da odmah otvore razgovor.'
            ],
            [
                'title' => '3. Postavi blokove redoslijedom kako je napisano',
                'description' => 'Redoslijed je važan jer vodi osobu od interesa do poruke ili ostavljenog kontakta.'
            ],
            [
                'title' => '4. Objavi i podijeli isti dan',
                'description' => 'Nemoj čekati savršenstvo. Sustav počinje raditi tek kada ga ljudi vide i kliknu.'
            ],
            [
                'title' => '5. Svaki dan napravi mali broj ponavljanja',
                'description' => 'Dovoljno je nekoliko poruka i jedna objava dnevno da kreneš stvarati ritam i rezultate.'
            ]
        ];

        $quick_links = [
            [
                'title' => 'Biolinkovi',
                'description' => 'Otvoriš i urediš svoj set.',
                'url' => url('links?type=biolink'),
                'icon' => 'fas fa-link'
            ],
            [
                'title' => 'Prikupljeni kontakti',
                'description' => 'Provjeriš tko je ostavio kontakt i kome se trebaš javiti.',
                'url' => url('data'),
                'icon' => 'fas fa-database'
            ],
            [
                'title' => 'Statistika linkova',
                'description' => 'Vidiš koji linkovi i poruke otvaraju najviše interesa.',
                'url' => url('links-statistics'),
                'icon' => 'fas fa-chart-line'
            ]
        ];

        $data = [
            'sets' => $sets,
            'start_here' => $start_here,
            'implementation' => $implementation,
            'quick_links' => $quick_links,
        ];

        $view = new \Altum\View('fcc-growth/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }
}