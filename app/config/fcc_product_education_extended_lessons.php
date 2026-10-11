<?php

/* Extended learning lessons. Original course version and lesson IDs remain unchanged. */
return [
    'lessons' => [
        [
            'id' => 'proteini-u-svakodnevnim-obrocima',
            'topic_id' => 'T01',
            'title' => 'Dovoljno proteina u svakodnevnim obrocima',
            'summary' => 'Nauči kome preporučiti Plant Protein ili Lite Ultra i kako objasniti protein u pripremljenom napitku.',
            'minutes' => 7,
            'sections' => [
                [
                    'title' => 'Proteini počinju na tanjuru',
                    'paragraphs' => ['Proteini su građeni od aminokiselina. Tijelo ih koristi u izgradnji i održavanju svojih tkiva. Dio aminokiselina mora dobiti hranom. U svakodnevnom jelovniku proteinski izvori mogu biti jaja, riba, meso, mliječni proizvodi, mahunarke i proizvodi od soje. Raznolika biljna prehrana također može osigurati aminokiseline koje trebamo.', 'Kupcu koji često jede samo pecivo ili preskače proteinske namirnice objasni gdje ih može dodati u obroke. Kada je napitak praktičniji zbog rasporeda, proteinski proizvod nadopunjuje unos. Pregled jelovnika pokazuje što hrana već daje i gdje postoji potreba za dopunom.'],
                    'points' => [],
                ],
                [
                    'title' => 'Potreba i praktičnost različita su pitanja',
                    'paragraphs' => ['Osobna potreba povezana je s dobi, aktivnošću, ukupnom prehranom i zdravstvenim okolnostima. Sportski cilj sam ne određuje istu količinu za sve. NIH ODS opisuje proteinske proizvode kao moguću dopunu prehrani kada je potrebno osigurati unos. Veći broj proizvoda nije zamjena za pregled obroka i stvarnog cilja.', 'Plant Protein je biljni proteinski proizvod, a Lite Ultra ima zaseban nutritivni sastav i način pripreme. Preporuku poveži s time treba li kupcu nadopuna proteina ili proizvod s drukčijim ukupnim sastavom. Proteini doprinose održavanju i rastu mišićne mase.'],
                    'points' => [],
                ],
                [
                    'title' => 'Računaj pripremljenu porciju',
                    'paragraphs' => ['Grami praška i grami proteina nisu ista veličina. Uspoređuj količinu proteina u deklariranoj porciji i provjeri odnosi li se tablica na suhi proizvod ili pripremljeni napitak. Kada se prašak priprema s mlijekom ili drugim napitkom, dodana tekućina može promijeniti količinu proteina i energije. Naziv biljni napitak ne otkriva njegove grame proteina.', 'Izmišljena porcija praška daje 15 g proteina, a dodana tekućina još 4 g, pa pripremljeni napitak daje 19 g. Ti brojevi služe vježbi. Na kartici stvarnog proizvoda koristi se aktualna deklarirana porcija i točan doprinos tekućine, bez dvostrukog pribrajanja.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac zbog posla nema pripremljen proteinski obrok. Usporedi Plant Protein i Lite Ultra prema količini proteina, ostalim hranjivim tvarima i tekućini za pripremu. Objasni kada bi preporučio svaki proizvod i izračunaj doprinos jedne aktualne porcije.', 'Primjer razgovora: Ako vam u obrocima nedostaje proteina, preporučujem Plant Protein. Protein doprinosi održavanju i rastu mišićne mase. Lite Ultra druga je opcija koju uspoređujemo prema cijeloj nutritivnoj tablici i pripremi.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Protein se procjenjuje kroz cijeli jelovnik.',
                'Pripremljeni napitak može uključivati protein iz praška i dodane tekućine.',
                'Praktičnost proizvoda i osobna potreba za proteinom procjenjuju se zasebno.',
            ],
            'quiz' => [
                [
                    'id' => 'pr1',
                    'prompt' => 'U izmišljenoj vježbi prašak daje 15 g proteina, a dodana tekućina 4 g. Tablica praška ne uključuje tekućinu. Koliko daje napitak?',
                    'options' => [
                        'a' => '15 g, jer se tekućina nikad ne računa.',
                        'b' => '19 g, jer se zbrajaju oba potvrđena doprinosa.',
                        'c' => 'Količina praška jednaka je gramima proteina.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Zbrajaju se proteini iz sastavnica u stvarno upotrijebljenim količinama. Ovdje je 15 plus 4 jednako 19 g.',
                ],
                [
                    'id' => 'pr2',
                    'prompt' => 'Kupac želi odabrati proteinski proizvod jer smatra da svi njegovi obroci imaju malo proteina. Što prvo provjeravaš?',
                    'options' => [
                        'a' => 'Njegov uobičajeni jelovnik, izvore i dostupne količine proteina.',
                        'b' => 'Koje je pakiranje najveće.',
                        'c' => 'Koji proizvod spominje najviše biljaka.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Pregled obroka razjašnjava što već doprinosi unosu i gdje treba dodatni podatak. Sam dojam ne određuje osobnu potrebu.',
                ],
                [
                    'id' => 'pr3',
                    'prompt' => 'Dva pripremljena napitka imaju jednaku količinu proteina. Što još treba usporediti prije nego ih opišeš kao jednake obroke?',
                    'options' => [
                        'a' => 'Ništa, svi ostali sastojci automatski su jednaki.',
                        'b' => 'Samo miris praška.',
                        'c' => 'Ostale hranjive tvari, veličinu porcije i način pripreme.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Jednak protein nije jednak ukupan nutritivni sastav. Usporediva porcija i priprema nužne su za smislen opis.',
                ],
            ],
            'source_ids' => [
                'nih-performance',
                'nih-supplements',
            ],
            'product_keys' => [
                'forever-plant-protein',
                'forever-ultra',
            ],
        ],
        [
            'id' => 'biljna-prehrana-i-vitamin-b12',
            'topic_id' => 'T02',
            'title' => 'Biljna prehrana i pouzdan izvor B12',
            'summary' => 'Objasni zašto se B12 Plus preporučuje u veganskoj i pretežno biljnoj prehrani te ulogu B12 u krvi, živcima i energiji.',
            'minutes' => 7,
            'sections' => [
                [
                    'title' => 'Zašto B12 zaslužuje zaseban plan',
                    'paragraphs' => ['Vitamin B12 potreban je za funkciju živčanog sustava, stvaranje crvenih krvnih stanica i sintezu DNK. U veganskoj prehrani važno je planirati pouzdan izvor. NIH ODS navodi da neobogaćena biljna hrana nije pouzdan izvor B12. Zato zeleno povrće, naziv superhrana i velik broj biljnih sastojaka nisu potvrda njegova unosa.', 'Osobi koja se hrani veganski preporuči pouzdan redovit izvor B12 iz obogaćene hrane ili prikladnog dodatka. B12 doprinosi normalnoj funkciji živčanog sustava, stvaranju crvenih krvnih stanica i smanjenju umora i iscrpljenosti.'],
                    'points' => [],
                ],
                [
                    'title' => 'Obogaćena hrana treba imati potvrđen podatak',
                    'paragraphs' => ['Biljni napitak, pahuljice ili prehrambeni kvasac mogu biti obogaćeni, ali to treba provjeriti na konkretnom pakiranju. Pregledaj naveden vitamin, količinu po porciji i stvarnu učestalost konzumacije. Ako izmišljeni jelovnik uključuje obogaćeni napitak samo povremeno, ne bilježi ga kao redovit svakodnevni izvor.', 'Plant Protein preporučuje se za proteinsku potrebu, a Fields of Greens kao biljna mješavina prema svom sastavu. B12 Plus preporučuje se za nadopunu B12. Kupcu jednostavno objasni koja potreba pripada kojem proizvodu.'],
                    'points' => [],
                ],
                [
                    'title' => 'B12 Plus: čitaj cijelu formulu',
                    'paragraphs' => ['Na Forever B12 Plus pronađi vitamin B12, njegov oblik ako je naveden te količinu po deklariranoj porciji. Pogledaj i ostale sastojke, uključujući folat kada je naveden u aktualnoj formuli. Tako možeš objasniti što proizvod stvarno sadrži i uočiti preklapanje s drugim dodacima. Jedinica mikrogram nije isto što i miligram.', 'Kod potvrđenog problema apsorpcije B12 način nadoknade određuje zdravstveni stručnjak. Za redovitu prehrambenu dopunu koristi se uputa stvarnog proizvoda i pregled postojećih izvora B12.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac se hrani veganski i smatra da zelena biljna mješavina pokriva B12. Objasni razliku Fields of Greens, Plant Proteina i B12 Plus. Za svaki navedi konkretnu ulogu, a za B12 pokaži pouzdan izvor i porciju.', 'Primjer razgovora: Budući da se hranite pretežno biljno, važno je imati pouzdan izvor B12. B12 Plus preporučujem kao dopunu vitamina važnog za živce i krvne stanice te za normalan energijski metabolizam.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Neobogaćena biljna hrana nije pouzdan izvor B12.',
                'Obogaćivanje se potvrđuje za konkretan proizvod i porciju.',
                'B12 Plus nadopunjuje B12, a Plant Protein proteine. Biljni sastojci sami ne potvrđuju B12.',
            ],
            'quiz' => [
                [
                    'id' => 'b1',
                    'prompt' => 'Na biljnom napitku nema podatka o B12, ali na prednjoj strani piše biljni. Kako ga upisuješ u pregled?',
                    'options' => [
                        'a' => 'Kao potvrđen izvor B12.',
                        'b' => 'Kao terapiju za umor.',
                        'c' => 'Kao proizvod čiji doprinos B12 još nije potvrđen.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Biljno podrijetlo ne potvrđuje obogaćivanje ni količinu B12. Potreban je konkretan podatak.',
                ],
                [
                    'id' => 'b2',
                    'prompt' => 'Kupac bira Fields of Greens umjesto potvrđenog izvora B12 jer sadrži zelene biljke. Koje pitanje razjašnjava izbor?',
                    'options' => [
                        'a' => 'Koja je nijansa zelene na kutiji?',
                        'b' => 'Gdje aktualna deklaracija navodi B12 i njegovu količinu po porciji?',
                        'c' => 'Koliko dugo proizvod postoji?',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Pouzdan izvor određuje deklarirani vitamin i količina, a ne boja ili opći biljni opis.',
                ],
                [
                    'id' => 'b3',
                    'prompt' => 'Osoba ima medicinski potvrđen problem apsorpcije B12. Što može učiniti suradnik?',
                    'options' => [
                        'a' => 'Pripremiti deklaraciju i usmjeriti pitanje o nadoknadi prema njezinu zdravstvenom stručnjaku.',
                        'b' => 'Iz ukupnog broja tableta izračunati terapijsku dozu.',
                        'c' => 'Pretpostaviti da proteinski napitak rješava problem apsorpcije.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Plan nadoknade kod poznatog problema apsorpcije zahtijeva stručnu odluku. Provjerena deklaracija korisna je podloga razgovoru.',
                ],
            ],
            'source_ids' => [
                'nih-b12',
                'nih-supplements',
            ],
            'product_keys' => [
                'forever-b12-plus',
                'forever-plant-protein',
                'forever-fields-of-greens',
            ],
        ],
        [
            'id' => 'menopauza-kosti-i-misici',
            'topic_id' => 'T03',
            'title' => 'Menopauza: Multi Maca, prehrana kostiju i mišića',
            'summary' => 'Objasni preporuku Multi Mace kod menopauzalnih tegoba i zasebne uloge kalcija, vitamina D i proteina.',
            'minutes' => 8,
            'sections' => [
                [
                    'title' => 'Različite potrebe u menopauzi',
                    'paragraphs' => ['U menopauzi se mogu pojaviti valunzi, pojačano znojenje, promjene raspoloženja i teškoće sa snom. Multi Maca preporučuje se uz takve tegobe. Osobno praćenje pomaže opisati zbog čega je žena zatražila proizvod.', 'Prehrana kostiju i mišića zasebna je važna tema. Kalcij je potreban za održavanje normalnih kostiju, vitamin D pomaže njegovoj apsorpciji, a proteini su građa mišićnog tkiva. Nakon menopauze hormonalne promjene mogu utjecati na zadržavanje kalcija.'],
                    'points' => [],
                ],
                [
                    'title' => 'Zašto se povezuju Calcium i Absorbent D',
                    'paragraphs' => ['Calcium nadopunjuje unos kalcija kada ga u prehrani nedostaje. Absorbent D daje vitamin D, važan za normalnu apsorpciju i iskorištenje kalcija te održavanje normalnih kostiju i mišićne funkcije. Zato se njihove prehrambene uloge dopunjuju.', 'Preporuku poveži s obrocima i ostalim izvorima tih nutrijenata. Ako žena već ima potvrđenu osteoporozu ili prethodni prijelom, prehrambena dopuna usklađuje se s njezinim planom zdravstvene skrbi. Ukupan unos obuhvaća hranu, obogaćene proizvode i postojeće dodatke.'],
                    'points' => [],
                ],
                [
                    'title' => 'Multi Maca i proteinska dopuna imaju zasebne uloge',
                    'paragraphs' => ['Multi Maca s macom kao prepoznatljivim biljnim sastojkom glavni je proizvod u preporuci za menopauzalne tegobe. Istraživanja drugih pripravaka mace imaju svoje formule i uvjete, pa se stvarno korisničko iskustvo jasno opisuje kao iskustvo.', 'Plant Protein ima drugu primjenu: nadopunu proteina kada ih u obrocima nedostaje. Proteini doprinose održavanju i rastu mišićne mase. Primjereno kretanje i prehrana povezani su s održavanjem mišića, a osobni izbor slijedi mogućnosti i zdravstveni plan žene.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka prema tegobi i prehrani',
                    'paragraphs' => ['Kupac pita za valunge, znojenje i promjene raspoloženja u menopauzi. Objasni preporuku Multi Mace i njezine sastojke. Zatim u drugom slučaju žena kaže da ne jede izvore kalcija i da obroci imaju malo proteina. Objasni koji proizvodi nadopunjuju te odvojene potrebe.', 'Primjer razgovora: Kod valunga, znojenja i promjena raspoloženja u menopauzi preporučujem Multi Macu. Calcium i Absorbent D dodaju se kada nedostaje kalcija i vitamina D, a Plant Protein kada u obrocima nema dovoljno proteina.'],
                    'points' => [],
                ],
            ],
            'takeaways' => ['Multi Maca preporučuje se uz menopauzalne tegobe.', 'Calcium i Absorbent D imaju povezane prehrambene uloge za kosti.', 'Proteinska dopuna preporučuje se prema stvarnom unosu proteina u obrocima.'],
            'quiz' => [
                [
                    'id' => 'me1',
                    'prompt' => 'Osoba već koristi obogaćeni napitak i dodatak s vitaminom D. Što provjeravaš prije razgovora o još jednom proizvodu?',
                    'options' => [
                        'a' => 'Ukupne poznate izvore i količine istog nutrijenta.',
                        'b' => 'Samo koji naziv proizvoda najbolje odgovara menopauzi.',
                        'c' => 'Samo koliko tableta ima kutija.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Dodatni proizvod procjenjuje se uz postojeće izvore. Naziv životnog razdoblja ne otkriva ukupan unos.',
                ],
                [
                    'id' => 'me2',
                    'prompt' => 'Zašto su Calcium i Plant Protein u istoj lekciji?',
                    'options' => [
                        'a' => 'Jer su njihove formule međusobno zamjenjive.',
                        'b' => 'Jer oba predstavljaju dokazanu hormonsku terapiju.',
                        'c' => 'Jer se na njima uče različiti doprinosi prehrani kostiju i mišića.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Povezane kartice obrađuju različite hranjive tvari. Zajednička tema ne znači isti sastav ili medicinsku namjenu.',
                ],
                [
                    'id' => 'me3',
                    'prompt' => 'U razgovoru pronađeš istraživanje nekog drugog pripravka mace. Kako ga povezuje s Multi Macom?',
                    'options' => [
                        'a' => 'Automatski prenosi sve rezultate jer naziv sadrži macu.',
                        'b' => 'Provjerava podudara li se formula, količina i istraženi kontekst.',
                        'c' => 'Iz istraživanja odmah određuje terapiju svim ženama.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Višesastojna formula i drugačiji pripravak nisu isti proizvod. Važno je utvrditi na što se istraživanje stvarno odnosi.',
                ],
            ],
            'source_ids' => [
                'nih-calcium',
                'nih-vitamin-d',
                'nih-performance',
                'nih-supplements',
            ],
            'product_keys' => [
                'forever-calcium',
                'absorbent-d',
                'forever-plant-protein',
                'forever-multi-maca',
            ],
        ],
        [
            'id' => 'vlakna-fermentacija-i-postupnost',
            'topic_id' => 'T04',
            'title' => 'Vlakna, fermentacija i postupna promjena prehrane',
            'summary' => 'Objasni vlakna, nastanak plinova i praktičnu preporuku aktualnog Fiber Fusiona uz hranu i tekućinu.',
            'minutes' => 7,
            'sections' => [
                [
                    'title' => 'Što se događa s neprobavljenim sastavnicama',
                    'paragraphs' => ['Dio ugljikohidrata ne razgradi se i ne apsorbira u tankom crijevu. Bakterije u debelom crijevu mogu ga dalje razgrađivati, pri čemu nastaju i plinovi. NIDDK opisuje plinove kao uobičajeni dio probave. Sama prisutnost plinova zato nije dokaz bolesti, a promjena prehrane može promijeniti ono što osoba osjeća.', 'Fermentacija znači mikrobnu razgradnju određenih sastavnica hrane. Vrsta vlakna, količina i podnošljivost pomažu objasniti zašto različite osobe mogu drukčije reagirati. Kupcu poveži prehrambene izvore s konkretnim gramima vlakana koje proizvod nadopunjuje.'],
                    'points' => [],
                ],
                [
                    'title' => 'Raznolikost hrane i postupna promjena',
                    'paragraphs' => ['Vlakna dolaze iz žitarica, mahunarki, voća, povrća i drugih biljnih namirnica. NIDDK preporučuje postupno povećavanje unosa, uz odgovarajuću tekućinu. U izmišljenom danu prvo pronađi postojeće izvore. Promjena jednog obroka lakše se opisuje i prati nego istodobno dodavanje nekoliko novih proizvoda.', 'Fiber Fusion preporuči kada u obrocima nedostaje vlakana. Promjena se uvodi postupno uz odgovarajuću tekućinu. Kod posebnog liječničkog prehrambenog plana slijedi njegovu uputu. Jaka bol, povraćanje ili krv u stolici zahtijevaju procjenu liječnika.'],
                    'points' => [],
                ],
                [
                    'title' => 'Fiber Fusion: vlakna u jednoj porciji',
                    'paragraphs' => ['Fiber Fusion je aktualni žele u sticku s vlaknima iz kukuruza, jabuke, citrusa i psilijuma. Jedan stick daje 8 g vlakana za nadopunu prehrane. Njegova primjena povezuje stvaran doprinos vlakana, tekućinu i postupno uvođenje.', 'Povijesni Forever Fiber bio je prašak drukčijeg sastava i načina pripreme. Stara i aktualna formula imaju vlastite podatke. Za današnju preporuku koristi se Fiber Fusion i njegova uputa.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac nakon naglog povećanja vlakana primjećuje više plinova. Objasni fermentaciju i zašto se promjena uvodi postupno. Zatim drugom kupcu, koji jede malo biljnih namirnica, predstavi Fiber Fusion i njegovih 8 g vlakana po sticku.', 'Primjer razgovora: Ako u prehrani imate malo vlakana, preporučujem Fiber Fusion kao nadopunu. Uvedite ga prema uputi uz dovoljno tekućine. Postupna promjena pomoći će vam pratiti kako vam odgovara.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Fermentacija nekih neprobavljenih ugljikohidrata može stvarati plinove.',
                'Vlakna se uvode postupno i promatraju u cijeloj prehrani.',
                'Povijesni Fiber i aktualni Fiber Fusion zahtijevaju zasebne podatke.',
            ],
            'quiz' => [
                [
                    'id' => 'vf1',
                    'prompt' => 'Osoba istoga dana doda nekoliko novih izvora vlakana i zamijeti više plinova. Koji je koristan odgovor kupcu?',
                    'options' => [
                        'a' => 'Proglasiti svaki plin dokazom infekcije.',
                        'b' => 'Pregledati promjene i količine te objasniti postupno uklapanje uz osobni kontekst.',
                        'c' => 'Automatski dodati još više svih proizvoda.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Promjena unosa može promijeniti podnošljivost. Pregled onoga što je promijenjeno daje bolju osnovu od automatske nove kombinacije.',
                ],
                [
                    'id' => 'vf2',
                    'prompt' => 'U staroj knjižici pronađeš uputu za pripremu Fibera. Možeš li je primijeniti na Fiber Fusion?',
                    'options' => [
                        'a' => 'Da, jer naziv ima riječ Fiber.',
                        'b' => 'Da, ako korisnik preferira prašak.',
                        'c' => 'Ne bez provjere aktualne formule, oblika i upute za Fusion.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Povijesni naziv i aktualni proizvod imaju zasebne podatke. Upute se ne prenose samo prema sličnosti naziva.',
                ],
                [
                    'id' => 'vf3',
                    'prompt' => 'Zašto uspoređujemo Fiber Fusion i Active Pro B?',
                    'options' => [
                        'a' => 'Da razlikujemo podatak o vlaknima od podatka o živim mikroorganizmima.',
                        'b' => 'Da bismo dokazali da su potpuno zamjenjivi.',
                        'c' => 'Da bismo iz njihova naziva izračunali istu dozu.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Vlakna i probiotički mikroorganizmi različite su sastavnice. Usporedba služi učenju njihovih stvarnih deklariranih podataka.',
                ],
            ],
            'source_ids' => [
                'nih-fiber',
                'nih-gas',
                'nih-probiotics-ods',
            ],
            'product_keys' => [
                'forever-fiber-fusion',
                'forever-fiber',
                'forever-active-pro-b',
            ],
        ],
        [
            'id' => 'probioticki-soj-i-antibiotici',
            'topic_id' => 'T05',
            'title' => 'Active Pro B, probiotički sojevi i primjena uz antibiotike',
            'summary' => 'Objasni probiotičku dopunu tijekom ili nakon antibiotika, identitet mikroorganizama i raspored uz konkretnu terapiju.',
            'minutes' => 8,
            'sections' => [
                [
                    'title' => 'Ime mikroorganizma ima više razina',
                    'paragraphs' => ['Rod, vrsta i soj opisuju identitet probiotičkog mikroorganizma. Sama vrsta često nije dovoljan podatak za povezivanje s istraživanjem. NIH ODS naglašava da se različiti sojevi i formule razlikuju. Zato istraženi rezultat traži usporedbu punog identiteta, a ne samo zajedničku riječ probiotik.', 'Active Pro B donosi žive probiotičke mikroorganizme. Kupcu objasni njihov identitet i probiotičku ulogu prema aktualnom sastavu. Pri povezivanju istraživanja s proizvodom promatra se puna oznaka soja, zajedno s formulom i količinom.'],
                    'points' => [],
                ],
                [
                    'title' => 'CFU, rok i uvjeti čuvanja',
                    'paragraphs' => ['CFU označava jedinice koje mogu stvoriti koloniju. Pročitaj količinu u navedenoj porciji i provjeri odnosi li se podatak na proizvodnju ili održivost do isteka roka. Usporedba zahtijeva i stvaran način čuvanja. Veći broj CFU sam po sebi ne dokazuje bolji rezultat kod svakog cilja.', 'Za preporučeni proizvod pokaži porciju, čuvanje i rok. CFU je podatak o održivim mikroorganizmima, dok prikladnost primjene povezuje taj podatak s vrstom, sojem i stvarnom potrebom osobe.'],
                    'points' => [],
                ],
                [
                    'title' => 'Antibiotik mijenja pitanje koje postavljamo',
                    'paragraphs' => ['Neka istraživanja pokazuju korist određenih probiotičkih pripravaka u smanjenju rizika proljeva povezanog s antibioticima. To ne znači da svaki soj, svaki proizvod i svaka osoba imaju isti ishod. Uspoređuju se istražena formula, količina, skupina odraslih osoba i konkretan rezultat. Iz studije o jednom pripravku ne zaključuješ da je drugi nužno jednak.', 'Za odraslu osobu koja želi probiotičku dopunu tijekom ili nakon antibiotika preporuči Active Pro B. Raspored uz konkretan antibiotik potvrđuje liječnik ili ljekarnik. Teška bolest, oslabljen imunitet i onkološka terapija zahtijevaju liječničku procjenu prije uporabe.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac završava antibiotsku terapiju i pita za probiotički proizvod. Objasni što donosi Active Pro B, pokaži uporabu i čuvanje te pripremi konkretno pitanje o rasporedu uz njegov antibiotik. Zatim objasni zašto su puni soj i formula važni pri usporedbi istraživanja.', 'Primjer razgovora: Ako tijekom ili nakon antibiotika želite probiotičku dopunu, preporučujem Active Pro B. Donosi žive probiotičke mikroorganizme, a raspored uz vaš antibiotik provjerit ćemo s ljekarnikom ili liječnikom.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Dokaz se povezuje s punim identitetom soja i istraženom formulom.',
                'CFU se čita uz porciju, rok i čuvanje.',
                'Pitanje uz antibiotik uključuje konkretan proizvod, terapiju i istraženi ishod.',
            ],
            'quiz' => [
                [
                    'id' => 'ps1',
                    'prompt' => 'Istraživanje navodi točan soj, a dostupni opis proizvoda navodi samo istu vrstu. Što još treba potvrditi?',
                    'options' => [
                        'a' => 'Da proizvod ima isti soj i usporedive istražene uvjete.',
                        'b' => 'Da oba imaju jednaku boju kapsule.',
                        'c' => 'Ništa, svi sojevi iste vrste daju isti rezultat.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Vrsta sama ne potvrđuje podudaranje soja. Za prijenos nalaza provjeravaju se formula i istraženi uvjeti.',
                ],
                [
                    'id' => 'ps2',
                    'prompt' => 'Dva probiotička proizvoda imaju različite brojeve CFU. Možeš li samo iz većeg broja zaključiti koji je bolji uz antibiotik?',
                    'options' => [
                        'a' => 'Da, broj sam određuje svaki klinički ishod.',
                        'b' => 'Da, ako pakiranje traje duže.',
                        'c' => 'Ne, potrebno je usporediti identitet, formulu i dokaz za konkretan ishod.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Broj CFU jedan je podatak o proizvodu. Bez odgovarajućeg dokaza ne određuje korist za određenu situaciju.',
                ],
                [
                    'id' => 'ps3',
                    'prompt' => 'Odrasla osoba pita za točan razmak probiotika i antibiotika. Što radiš?',
                    'options' => [
                        'a' => 'Za sve proizvode propisuješ isti raspored.',
                        'b' => 'Provjeravaš konkretne upute i pripremaš pitanje liječniku ili ljekarniku.',
                        'c' => 'Zaključuješ da vrijeme nije važno za nijednu formulu.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Raspored se usklađuje s konkretnom terapijom i proizvodom. Opća lekcija ne postavlja univerzalni razmak.',
                ],
            ],
            'source_ids' => [
                'nih-probiotics-ods',
                'nih-supplements',
            ],
            'product_keys' => [
                'forever-active-pro-b',
            ],
        ],
        [
            'id' => 'epa-dha-i-prehrambeni-izvori',
            'topic_id' => 'T06',
            'title' => 'Unos EPA i DHA uz rijetko jedenje ribe',
            'summary' => 'Preporuči prikladan omega 3 proizvod osobi koja rijetko jede ribu i objasni uloge EPA i DHA.',
            'minutes' => 7,
            'sections' => [
                [
                    'title' => 'Omega 3 nije jedan podatak',
                    'paragraphs' => ['ALA, EPA i DHA različite su omega 3 masne kiseline. ALA se nalazi u nekim biljnim izvorima, a EPA i DHA osobito u ribi i odgovarajućim algalnim izvorima. Tijelo može pretvarati ALA u EPA i DHA, ali pretvorba je ograničena. Zato se gram ALA ne upisuje kao jednak gram EPA ili DHA.', 'Osobi koja rijetko jede ribu može se preporučiti odgovarajuća omega 3 dopuna. Arctic Sea daje morska ulja, a Algal Omega drugu mogućnost podrijetla ulja. Odabir prati prehranu i stvaran sastav proizvoda.'],
                    'points' => [],
                ],
                [
                    'title' => 'Ukupno ulje i EPA plus DHA',
                    'paragraphs' => ['Na Forever Arctic Sea pronađi aktualnu formulu, deklariranu porciju te odvojene količine EPA i DHA. Ukupna masa ulja obuhvaća i druge sastavnice. Podatak 1000 mg ulja zato nije sam po sebi podatak 1000 mg EPA i DHA. Usporedbu napravi prema istom osnovnom mjerilu, primjerice deklariranoj porciji, a ne izgledu kapsule.', 'U izmišljenoj vježbi 1000 mg ulja sadrži 200 mg EPA i 100 mg DHA, ukupno 300 mg tih kiselina. To nisu brojke Arctic Sea. Na stvarnom proizvodu koristi odvojene deklarirane vrijednosti EPA i DHA po preporučenoj porciji.'],
                    'points' => [],
                ],
                [
                    'title' => 'Prehrambena dopuna i medicinski cilj',
                    'paragraphs' => ['Proizvod se može razmatrati kao mogući doprinos prehrambenom unosu. NIH ODS odvojeno opisuje kliničku uporabu određenih omega 3 pripravaka, primjerice kod visokih triglicerida. Takav medicinski kontekst ne određuje primjenu svakog dodatka prehrani. Iz prehrambene teme rijetko jedenje ribe ne izračunavaš terapijski režim.', 'EPA i DHA doprinose normalnoj funkciji srca pri odgovarajućem unosu. DHA ima i ulogu u održavanju normalnog vida i funkcije mozga. Kod propisanog liječenja triglicerida osobni odabir i doza usklađuju se s liječnikom.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac rijetko jede ribu i pita koji omega 3 proizvod odabrati. Usporedi morski i algalni izvor, deklariranu porciju te odvojene količine EPA i DHA. U dvije rečenice objasni prehrambenu preporuku i njezinu vezu s normalnom funkcijom srca.', 'Primjer razgovora: Ako rijetko jedete ribu, preporučujem omega 3 dopunu. Arctic Sea i Algal Omega razlikuju se po izvoru ulja. Odaberimo proizvod koji odgovara vašoj prehrani i objasnimo njegov doprinos EPA i DHA.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'ALA, EPA i DHA razlikuju se po izvorima i nisu međusobno jednak podatak.',
                'Ukupna masa ulja nije jednaka zbroju EPA i DHA.',
                'Prehrambeno uklapanje i kliničko liječenje lipida različita su pitanja.',
            ],
            'quiz' => [
                [
                    'id' => 'om1',
                    'prompt' => 'Izmišljena porcija ima 1000 mg ulja, 200 mg EPA i 100 mg DHA. Koliki je zbroj EPA i DHA?',
                    'options' => [
                        'a' => '1000 mg.',
                        'b' => '300 mg.',
                        'c' => '1300 mg.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Zbrajaju se deklarirane EPA i DHA: 200 plus 100 jednako 300 mg. Ukupno ulje širi je podatak.',
                ],
                [
                    'id' => 'om2',
                    'prompt' => 'Biljna namirnica sadrži ALA. Možeš li njezinu količinu automatski upisati kao jednaku količinu DHA?',
                    'options' => [
                        'a' => 'Da, sve omega 3 kiseline isti su sastojak.',
                        'b' => 'Da, ako je namirnica hladno prešana.',
                        'c' => 'Ne, riječ je o različitim kiselinama i ograničenoj pretvorbi.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'ALA i DHA nisu isti podatak. Izvor i konkretna masna kiselina moraju ostati jasno navedeni.',
                ],
                [
                    'id' => 'om3',
                    'prompt' => 'Osoba s propisanim liječenjem triglicerida pita može li ga zamijeniti Arctic Seom. Kako koristiš lekciju?',
                    'options' => [
                        'a' => 'Prikažeš aktualni sastav i pitanje usmjeriš liječniku koji vodi terapiju.',
                        'b' => 'Iz ukupne mase ulja izračunaš zamjensku terapijsku dozu.',
                        'c' => 'Zaključiš da svaki omega 3 dodatak zamjenjuje lijek.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Dodatak prehrani i određeni medicinski pripravak nisu automatski zamjenjivi. Odluka zahtijeva konkretan sastav i stručni plan.',
                ],
            ],
            'source_ids' => [
                'nih-omega3',
                'nih-supplements',
            ],
            'product_keys' => [
                'forever-arctic-sea',
            ],
        ],
        [
            'id' => 'san-kofein-i-vecernji-ritual',
            'topic_id' => 'T07',
            'title' => 'San, kofein i večernji ritual',
            'summary' => 'Preporuči biljni večernji napitak i objasni kako raspored sna i vrijeme kofeina utječu na odmor.',
            'minutes' => 8,
            'sections' => [
                [
                    'title' => 'Raspored oblikuje okvir za san',
                    'paragraphs' => ['NHLBI preporučuje odlazak na spavanje i ustajanje u slično vrijeme te dovoljno vremena za san. Večernje navike promatraj kao niz navika, od završetka posla do odlaska u krevet. Jedna šalica čaja ne opisuje cijeli taj niz. Korisnije je prvo pronaći gdje raspored postaje neujednačen.', 'Kupcu koji navečer dugo radi pa teško održava redovit raspored predloži raniji završetak zadataka i slično vrijeme spavanja. To je praktična osnova na koju se može nadovezati ugodan večernji napitak.'],
                    'points' => [],
                ],
                [
                    'title' => 'Kofein zbrajaj kroz dan',
                    'paragraphs' => ['Kofein može ometati san, a njegovi učinci mogu trajati satima. NHLBI zato savjetuje izbjegavanje kofeina prije spavanja. U dnevnom zapisu uključi kavu, čaj i druge potvrđene izvore. Naziv proizvoda za energiju ne govori koliko kofeina osoba ukupno unese, kao što naziv biljni čaj sam ne potvrđuje njegov sastav.', 'Za Therm i Focus pokaži potvrđeni sastav i podatak o kofeinu po porciji. Preporuku energetskog proizvoda poveži s ranijim dijelom dana i drugim izvorima kofeina koje osoba već unosi.'],
                    'points' => [],
                ],
                [
                    'title' => 'Složi kratak i provediv večernji ritual',
                    'paragraphs' => ['NHLBI predlaže mirno vrijeme prije spavanja te tihu, tamnu i prikladno svježu sobu. U obrazovnom planu izaberi nekoliko radnji koje se može ponoviti, primjerice završiti rad, prigušiti svjetlo i pripremiti sobu. Cilj vježbe je pretvoriti opću želju za boljim snom u vidljiv raspored radnji.', 'Aloe Blossom Herbal Tea preporučuje se kao ugodan topli biljni napitak za mirniji završetak dana. Osoba može opisati osjećaj ugode i osobno iskustvo uz napitak. Trajne teškoće sa spavanjem razgovaraju se s liječnikom.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac želi ugodniji završetak dana, ali uzima kofeinski proizvod kasno navečer. Predloži jasno vrijeme odmora i pregled izvora kofeina. Objasni kada preporučuješ Herbal Tea i zašto Therm ili Focus imaju drugu primjenu.', 'Primjer razgovora: Ako želite ugodan napitak navečer, preporučujem Aloe Blossom Herbal Tea. Povežite ga s mirnijim završetkom dana i redovitim vremenom spavanja, a kofein iz kave i drugih proizvoda planirajte ranije.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Vrijeme za san planira se kroz cijeli dnevni raspored.',
                'Za kofein su važni svi izvori, količina i vrijeme unosa.',
                'Večernji ritual povezuje jednostavne navike s provjerenim podacima o napitku.',
            ],
            'quiz' => [
                [
                    'id' => 'sr1',
                    'prompt' => 'Osoba pije kavu kasno popodne i uzima proizvod za energiju navečer. Što prvo bilježiš?',
                    'options' => [
                        'a' => 'Samo naziv večernjeg proizvoda.',
                        'b' => 'Sve potvrđene izvore kofeina, porcije i vrijeme unosa.',
                        'c' => 'Samo broj radnih dana u tjednu.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Pregled cijelog dana daje smisao podatku o kofeinu i mogućem utjecaju na san.',
                ],
                [
                    'id' => 'sr2',
                    'prompt' => 'Na stranici biljnog čaja nema podatka o kofeinu. Kako završavaš usporedbu?',
                    'options' => [
                        'a' => 'Upisuješ da je podatak neprovjeren i tražiš aktualnu deklaraciju.',
                        'b' => 'Upisuješ nulu jer riječ biljni to potvrđuje.',
                        'c' => 'Pretpostavljaš isti sastav kao kod svakog drugog čaja.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Naziv kategorije nije potvrda sastava. Nepoznat podatak ostaje otvoren do provjere.',
                ],
                [
                    'id' => 'sr3',
                    'prompt' => 'Kupac želi uredniji san, ali svake večeri radi do drugog vremena. Koji prijedlog odgovara lekciji?',
                    'options' => [
                        'a' => 'Odabrati jači okus čaja kao jedinu promjenu.',
                        'b' => 'Povećati porciju proizvoda bez čitanja upute.',
                        'c' => 'Najprije složiti ponovljiv raspored završetka rada i mirnog vremena prije spavanja.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Urednije večernje navike povezuju konkretan raspored odmora i dovoljno vremena za san.',
                ],
            ],
            'source_ids' => [
                'nih-sleep',
                'nih-supplements',
            ],
            'product_keys' => [
                'aloe-blossom-herbal-tea',
                'forever-therm',
                'forever-focus',
            ],
        ],
        [
            'id' => 'osteoartritis-i-zglobne-formule',
            'topic_id' => 'T08',
            'title' => 'Osteoartritis: hrskavica, zglobna tekućina i primjena proizvoda',
            'summary' => 'Objasni sastojke Freedoma, Active HA i Move te razliku prehrambene potpore zglobovima i vanjske masažne njege.',
            'minutes' => 9,
            'sections' => [
                [
                    'title' => 'Počni od pitanja koje istraživanje postavlja',
                    'paragraphs' => ['Osteoartritis uključuje promjene hrskavice i drugih zglobnih tkiva, a može biti povezan s boli, ukočenošću i otežanim kretanjem. Razlikuje se od drugih vrsta artritisa, poput reumatoidnog ili psorijatičnog artritisa. NIAMS naglašava da vrstu utvrđuje liječnik jer se uzroci i zbrinjavanje razlikuju. Pri čitanju studije provjeri je li ispitivala bol, funkciju ili strukturu zgloba. To su različiti ishodi.', 'Kupcu koji pita za prehrambenu potporu zglobovima razjasni ima li potvrđen osteoartritis i što želi poboljšati u svakodnevnom kretanju. Promjenu boli ili lakše hodanje opiši kao iskustvo funkcije, a tvrdnja o obnovi hrskavice zahtijeva zaseban dokaz.'],
                    'points' => [],
                ],
                [
                    'title' => 'Isti naziv sastojka ne znači jednaku formulu',
                    'paragraphs' => ['NCCIH navodi da istraživanja glukozamina i hondroitina kod osteoartritisa daju neujednačene rezultate. Važni su istraženi oblik, formula i uvjeti. Činjenica da su te tvari povezane sa strukturom hrskavice ne potvrđuje da bilo koji dodatak obnavlja hrskavicu. Učenje zato počinje preciznijim pitanjem od sadrži li proizvod taj sastojak.', 'Freedom donosi glukozamin, hondroitin i MSM uz aloe napitak. Active HA daje hijaluronsku kiselinu, molekulu koja veže vodu i prirodno je prisutna u zglobnoj tekućini. To su konkretne veze sastojaka s temom zglobova.'],
                    'points' => [],
                ],
                [
                    'title' => 'Razlikuj oralni proizvod i kozmetički gel',
                    'paragraphs' => ['Forever Move je oralni proizvod, a Aloe MSM Gel proizvod za primjenu na koži. Za svaki pročitaj njegovu aktualnu namjenu i uputu. Nalaz o sastojku uzetom na usta nije automatski dokaz učinka kozmetičkog gela na zglob. Jednako tako podatak o ugodi pri masaži kože ne dokazuje promjenu zglobne strukture.', 'Move i ESM Complex provjeravaju se prema tržištu i stvarnoj formuli. Prehrambeni proizvod ima oralnu primjenu, a Aloe MSM Gel vanjsku njegu i masažu kože. Kupcu objasni koja je primjena svakog proizvoda i kako se uklapa uz njegov postojeći plan.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac s osteoartritisom pita za Freedom i Active HA, a želi i proizvod za masažu kože. Objasni zglobne sastojke Freedoma, ulogu hijaluronske kiseline i vanjsku primjenu Aloe MSM Gela. Navedena istraživanja odvoji prema tome mjere li bol, funkciju ili strukturu zgloba.', 'Primjer razgovora: Za prehrambenu potporu zglobovima preporučujem Freedom, Active HA i Move. Freedom ima glukozamin, hondroitin i MSM, a Active HA hijaluronsku kiselinu. Za vanjsku masažnu njegu kože postoji Aloe MSM Gel.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Bol, funkcija i struktura zgloba različiti su istraživački ishodi.',
                'Dokaz se uspoređuje s točnim oblikom, formulom i načinom primjene.',
                'Tržište i aktualni naziv provjeravaju se prije povezivanja proizvoda.',
            ],
            'quiz' => [
                [
                    'id' => 'oz1',
                    'prompt' => 'Studija jedne oralne formule mjerila je promjenu boli. Što treba provjeriti prije povezivanja s drugim proizvodom?',
                    'options' => [
                        'a' => 'Samo pripadaju li oba proizvoda temi zglobova.',
                        'b' => 'Samo imaju li sličnu boju pakiranja.',
                        'c' => 'Podudaraju li se formula, oblik, način primjene i istraženi uvjeti.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Dokaz se odnosi na predmet istraživanja. Zajednička tema ne potvrđuje jednakost proizvoda.',
                ],
                [
                    'id' => 'oz2',
                    'prompt' => 'Kupac navodi lakše hodanje kao dokaz da se hrskavica obnovila. Kako pojašnjavaš rezultat?',
                    'options' => [
                        'a' => 'Promjena funkcije ne potvrđuje sama po sebi obnovu strukture zgloba.',
                        'b' => 'Svako lakše hodanje dokazuje novu hrskavicu.',
                        'c' => 'Strukturni ishod i doživljaj aktivnosti isti su podatak.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Funkcija i struktura odvojeni su ishodi. Zaključak mora odgovarati onome što je stvarno izmjereno.',
                ],
                [
                    'id' => 'oz3',
                    'prompt' => 'Na stranom tržištu pronalaziš naziv ESM Complex. Kako ga koristiš uz Forever Move?',
                    'options' => [
                        'a' => 'Odmah ga proglašavaš aktualnim hrvatskim nazivom.',
                        'b' => 'Prvo provjeravaš tržište, proizvodni identitet i formulu.',
                        'c' => 'Iz stranog naziva određuješ dozu za svakog korisnika.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Tržišni naziv ne potvrđuje lokalni identitet ili sastav. Potrebna je provjera prije povezivanja.',
                ],
            ],
            'source_ids' => [
                'nih-glucosamine',
                'nih-supplements',
                'niams-arthritis-types',
            ],
            'product_keys' => [
                'forever-freedom',
                'forever-active-ha',
                'forever-move',
                'aloe-msm-gel',
            ],
        ],
        [
            'id' => 'zasloni-i-ocne-navike',
            'topic_id' => 'T09',
            'title' => 'Rad uz zaslon i očne navike',
            'summary' => 'Preporuči prehrambenu potporu normalnom vidu i poveži iVision i Arctic Sea sa stankama pri radu uz ekran.',
            'minutes' => 8,
            'sections' => [
                [
                    'title' => 'Opiši kada se javlja nelagoda',
                    'paragraphs' => ['NEI među mogućim simptomima suhog oka navodi peckanje, osjećaj suhoće ili grebanja, crvenilo i zamućen vid. Dug rad uz zaslon može biti povezan s tim problemom. Ipak, opis umornih očiju nije samostalna dijagnoza. Koristan početak razgovora je precizirati aktivnost, trajanje i promjenu koju osoba primjećuje.', 'Kupcu koji više sati radi za računalom pitaj kada se javlja nelagoda i preporuči stanke i treptanje. Ako tegobe ometaju svakodnevicu, razgovara se sa stručnjakom za oči. Opis potrebe pomaže odvojiti prehrambenu potporu vidu od procjene trajnih simptoma.'],
                    'points' => [],
                ],
                [
                    'title' => 'Pregledaj radni prostor i stanke',
                    'paragraphs' => ['NEI navodi stanke od gledanja u zaslon i ograničavanje vremena pred zaslonima među mogućim promjenama navika. Spominje i izbjegavanje dima, vjetra te strujanja klima uređaja. Za praktičan pregled označi gdje osoba sjedi, koliko neprekidno radi i ima li izravno strujanje zraka prema očima.', 'Kratke stanke, odmak pogleda i pregled rasvjete konkretne su preporuke za radni dan. Kupcu pokaži jednu promjenu prostora i jednu promjenu rasporeda koje može odmah primijeniti.'],
                    'points' => [],
                ],
                [
                    'title' => 'Formula i klinički nalaz provjeravaju se zasebno',
                    'paragraphs' => ['U istraživanju DREAM dodatak ribljih omega 3 masnih kiselina nije pokazao veću korist od usporedbe placebom kod proučavane skupine s umjerenim do teškim suhim okom. Taj se rezultat odnosi na opisani pokus. Iz njega ne radi univerzalnu tvrdnju o svim masnim kiselinama, svim očnim simptomima ili svakom dodatku.', 'iVision daje lutein, zeaksantine, vitamine i cink. Vitamin A i cink doprinose održavanju normalnog vida. Arctic Sea daje omega 3 s DHA koja doprinosi održavanju normalnog vida pri odgovarajućem unosu. Stari Vision ostaje zasebna povijesna formula.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac radi cijeli dan pred ekranom i pita što preporučuješ za vid. Objasni iVision, Arctic Sea i zasebne uloge njihovih sastojaka. Dodaj dvije konkretne preporuke za radni prostor ili stanke. Zatim pročitaj DREAM nalaz i navedi na koju se skupinu i ishod odnosio.', 'Primjer razgovora: Za prehrambenu potporu normalnom vidu možemo povezati iVision i Arctic Sea. Jedan daje karotenoide, vitamine i cink, a drugi omega 3. Uz rad pred ekranom preporučujem redovite stanke i treptanje.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Precizan opis tegoba i rada uz zaslon pomaže razumjeti sljedeća radnja.',
                'Stanke i radni prostor mogu se pregledati odvojeno od izbora dodatka.',
                'DREAM nalaz i povijesna Vision formula ne prenose se automatski na aktualni iVision.',
            ],
            'quiz' => [
                [
                    'id' => 'zo1',
                    'prompt' => 'Osoba osjeća nelagodu nakon dugog rada uz zaslon. Koji zapis najbolje pomaže razgovoru?',
                    'options' => [
                        'a' => 'Samo naziv proizvoda koji želi kupiti.',
                        'b' => 'Vrijeme pojave, trajanje rada, radni prostor i utjecaj na aktivnost.',
                        'c' => 'Pretpostavljena dijagnoza iz boje zaslona.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Konkretne okolnosti korisnije su od automatskog povezivanja tegobe i proizvoda.',
                ],
                [
                    'id' => 'zo2',
                    'prompt' => 'Stara knjižica opisuje Forever Vision. Smiješ li taj sastav unijeti kao sastav iVisiona?',
                    'options' => [
                        'a' => 'Da, sličan naziv potvrđuje istu formulu.',
                        'b' => 'Da, ako je knjižica detaljna.',
                        'c' => 'Ne, aktualni iVision provjerava se zasebno.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Povijesni naziv ne potvrđuje aktualnu recepturu. Identitet i deklaracija provjeravaju se za svaki proizvod.',
                ],
                [
                    'id' => 'zo3',
                    'prompt' => 'Kupac tvrdi da DREAM dokazuje kako riblje ulje liječi svaki oblik suhog oka. Kako odgovaraš?',
                    'options' => [
                        'a' => 'U proučavanoj skupini nije utvrđena veća korist od placeba, a nalaz treba vezati uz uvjete pokusa.',
                        'b' => 'Svaki dodatak ribljeg ulja ima jednak dokazan učinak.',
                        'c' => 'Rezultat pokusa nije potrebno čitati ako proizvod sadrži masne kiseline.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Zaključak mora pratiti stvarni nalaz i proučavanu skupinu. DREAM ne potvrđuje navedeno obećanje.',
                ],
            ],
            'source_ids' => [
                'nei-dry-eye',
                'nei-dream-trial',
                'nih-supplements',
            ],
            'product_keys' => [
                'forever-arctic-sea',
                'forever-ivision',
                'forever-vision',
            ],
        ],
        [
            'id' => 'kozna-barijera-i-inci',
            'topic_id' => 'T10',
            'title' => 'Kožna barijera, hidratacija i izbor proizvoda za njegu',
            'summary' => 'Objasni kome preporučiti serum, kremu ili losion i kako njihovi sastojci pridonose njezi kože.',
            'minutes' => 8,
            'sections' => [
                [
                    'title' => 'Zašto su čišćenje i hidratacija povezani',
                    'paragraphs' => ['Koža je zaštitni organ koji pomaže zadržati vodu u tijelu. Epidermis je njezin vanjski sloj. Za suhu kožu AAD savjetuje kraće pranje toplom vodom, blago sredstvo za čišćenje i nanošenje hidratantnog proizvoda dok je koža još vlažna.', 'Osobi sa suhom i zategnutom kožom objasni blago čišćenje i odgovarajuću hidratantnu njegu. Lagani serum i bogatija krema daju različite teksture. Losion za tijelo olakšava nanošenje na veću površinu.'],
                    'points' => [],
                ],
                [
                    'title' => 'Humektansi, emolijensi i kožna barijera',
                    'paragraphs' => ['Humektansi pomažu vezati vodu, emolijensi omekšavaju i zaglađuju površinu kože, a druge sastavnice mogu smanjivati gubitak vode. Te funkcije objašnjavaju zašto se biraju različite hidratantne teksture. INCI je popis kozmetičkih sastojaka koji pomaže prepoznati sastav.', 'Izmišljeni INCI primjer Aqua, Glycerin, Parfum služi prepoznavanju vode, glicerina i mirisa. Taj primjer nije formula Forever proizvoda. CosIng pomaže razumjeti naziv sastojka, dok točan postotak i svojstva gotove formule trebaju podatke o stvarnom proizvodu.'],
                    'points' => [],
                ],
                [
                    'title' => 'Kome preporučiti serum, kremu i losion',
                    'paragraphs' => ['Hydrating Serum preporučuje se kada osoba želi laganu hidratantnu njegu lica, a Restoring Crème kada joj odgovara kremasta završna njega. Aloe Body Lotion namijenjen je njezi kože tijela. Aloe Propolis Crème daje drugu kremastu njegu s propolisom prema svojoj namjeni.', 'Proizvod preporuči prema području primjene i teksturi koja kupcu odgovara. Kod poznate alergije na propolis izbjegavaju se proizvodi koji ga sadrže. Ako njega uzrokuje trajno pečenje ili iritaciju, prekida se primjena i prema potrebi traži procjena dermatologa.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuči njegu prema potrebi kože',
                    'paragraphs' => ['Kupac želi laganu hidrataciju lica, drugi bogatiju kremu, a treći njegu suhe kože tijela. Za svakoga odaberi odgovarajuću teksturu i objasni područje nanošenja. Zatim u sastavu jednog proizvoda prepoznaj sastojak povezan s hidratacijom ili omekšavanjem.', 'Primjer razgovora: Za laganu hidratantnu njegu lica preporučujem Hydrating Serum. Ako želite bogatiju završnu teksturu, odaberite kremu. Za njegu tijela preporučujem losion koji se lako nanosi na veću površinu.'],
                    'points' => [],
                ],
            ],
            'takeaways' => ['Za suhu kožu važni su blago čišćenje i odgovarajuća hidratantna njega.', 'Sastojci mogu vezati vodu, omekšavati kožu ili smanjivati gubitak vlage.', 'Proizvod se bira prema području nanošenja, teksturi i osobnoj podnošljivosti.'],
            'quiz' => [
                [
                    'id' => 'ki1',
                    'prompt' => 'Kupac iz INCI naziva želi odrediti točan postotak sastojka. Što mu pojašnjavaš?',
                    'options' => [
                        'a' => 'Svaki INCI naziv sadrži skriveni podatak o postotku.',
                        'b' => 'Sastojak iz baze uvijek čini većinu formule.',
                        'c' => 'Naziv identificira sastojak, a sam ne daje njegov precizan postotak.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Identitet i koncentracija različiti su podaci. Nepoznatu količinu ne treba izmišljati.',
                ],
                [
                    'id' => 'ki2',
                    'prompt' => 'Za konkretnu kremu imaš samo staru promotivnu sliku bez punog INCI popisa. Koju informaciju pripremaš kupcu?',
                    'options' => [
                        'a' => 'Bilježiš potvrđene podatke i tražiš aktualni cijeli popis sastojaka.',
                        'b' => 'Prepisuješ izmišljeni primjer Aqua, Glycerin, Parfum kao njezinu deklaraciju.',
                        'c' => 'Pretpostavljaš sastav svih drugih krema iste marke.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Vježba s izmišljenim nizom ne potvrđuje stvarnu recepturu. Kartica zahtijeva podatke konkretnog proizvoda.',
                ],
                [
                    'id' => 'ki3',
                    'prompt' => 'Osoba nakon novog proizvoda osjeća pečenje i želi dodati još tri proizvoda. Što odgovara svrsi lekcije?',
                    'options' => [
                        'a' => 'Pojačati sve postupke kako bi osjećaj brže nestao.',
                        'b' => 'Zabilježiti promjenu i iritaciju te je razjasniti s dermatologom umjesto istodobnog dodavanja novih proizvoda.',
                        'c' => 'Pretpostaviti da naziv prirodnog sastojka isključuje reakciju.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Osobna reakcija važan je podatak. Jednostavan pregled promjena olakšava stručnu procjenu.',
                ],
            ],
            'source_ids' => [
                'aad-dry-skin',
                'aad-contact-dermatitis',
                'eu-cosing',
                'niams-skin-learning',
                'aad-moisturizer-selection',
            ],
            'product_keys' => [
                'forever-hydrating-serum',
                'infinite-by-forever-restoring-creme',
                'forever-aloe-body-lotion',
                'forever-aloe-propolis-creme',
            ],
        ],
        [
            'id' => 'aloe-varijante-i-podnosljivost',
            'topic_id' => 'T11',
            'title' => 'Aloe napitci: kome preporučiti Gel i kako odabrati okus',
            'summary' => 'Objasni primjene Gela i odabir Berry Nectara, Peaches ili Manga prema okusu i sastavu.',
            'minutes' => 8,
            'sections' => [
                [
                    'title' => 'Točna varijanta vodi do točne usporedbe',
                    'paragraphs' => ['Forever Aloe Vera Gel, Aloe Berry Nectar, Aloe Peaches i Aloe Mango pripadaju povezanim primjerima napitaka u ovoj lekciji. Za svaki prvo provjeri tržište, pakiranje i aktualnu deklaraciju. Okus je samo jedno obilježje. Sastav i uputa za porciju moraju se pročitati zasebno, čak i kad napitci nose isto ime biljke.', 'Gel je osnovni aloe napitak, a Berry Nectar, Peaches i Mango daju različite voćne okuse. Preporuči napitak koji osoba može redovito koristiti i dobro podnosi, prema njegovom stvarnom sastavu i porciji.'],
                    'points' => [],
                ],
                [
                    'title' => 'Razlikuj dio lista i predmet istraživanja',
                    'paragraphs' => ['NCCIH razlikuje unutarnji gel lista aloe, lateks i cijeli list. Takvi materijali ne predstavljaju isti predmet istraživanja. Jednako je važna razlika između oralne primjene i primjene na koži. Ako čitaš rezultat studije, zapiši koji je materijal istražen, kako je primijenjen i što je mjereno.', 'Gel donosi unutarnji gel aloe s polisaharidima. Uz gastritis, refluks i nadutost preporučuje se s Pro B. Nalaz o drugom obliku aloe ostaje vezan uz taj oblik i istraženi ishod.'],
                    'points' => [],
                ],
                [
                    'title' => 'Osobno iskustvo zapiši precizno',
                    'paragraphs' => ['Za iskustvo s napitkom korisno je razlikovati okus, jednostavnost pripreme i ono što osoba primjećuje nakon uporabe. U izmišljenoj bilješci navedi varijantu, porciju i obrok uz koji se koristi. Ako se istodobno mijenjaju prehrana, raspored i više proizvoda, iz takvog zapisa teško je izdvojiti jednu promjenu.', 'Korisničko iskustvo prenesi konkretnim riječima: što je osoba koristila, zbog koje tegobe i što je primijetila. Tako kupac razumije preporuku, a različite primjene i osobni rezultati ostaju jasno opisani.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac pita za preporuku kod gastritisa, a drugi želi ugodan voćni okus aloe napitka. Prvome objasni Gel i Pro B, a drugome izbor Berry Nectara, Peaches i Manga. Navedi zbog čega se dva razgovora razlikuju.', 'Primjer razgovora: Kod gastritisa, refluksa i nadutosti preporučujem Gel i Active Pro B. Ako birate aloe napitak prema okusu, odaberite Gel, Berry Nectar, Peaches ili Mango prema tome što vam odgovara.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Okus, sastav i porcija zasebni su podaci o napitku.',
                'Dokaz se veže uz određeni dio aloe i način primjene.',
                'Iskustvo precizno opisuje proizvod, primjenu i osobno opažanje.',
            ],
            'quiz' => [
                [
                    'id' => 'av1',
                    'prompt' => 'Dvije varijante aloe napitka imaju različite osnove nutritivnih tablica. Kako uspoređuješ šećere?',
                    'options' => [
                        'a' => 'Prema boji napitka.',
                        'b' => 'Najprije na istoj količini ili jasno određenoj usporedivoj porciji.',
                        'c' => 'Prema tome koji okus osoba više voli.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Brojevi postaju usporedivi tek uz istu mjernu osnovu. Osobni okus druga je dimenzija.',
                ],
                [
                    'id' => 'av2',
                    'prompt' => 'Istraživanje opisuje aloe gel na koži. Što treba učiniti prije povezivanja s napitkom?',
                    'options' => [
                        'a' => 'Prepoznati razliku materijala i načina primjene te ne prenijeti nalaz automatski.',
                        'b' => 'Pretpostaviti isti učinak svake aloe formule.',
                        'c' => 'Povećati porciju napitka do učinka iz studije.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Oralna i topikalna primjena različiti su predmeti. Jednako ime biljke nije dovoljna poveznica.',
                ],
                [
                    'id' => 'av3',
                    'prompt' => 'Osoba je isti dan promijenila jelovnik, napitak i raspored obroka te navodi manje nadutosti. Što zapis potvrđuje?',
                    'options' => [
                        'a' => 'Da je upravo napitak izliječio uzrok.',
                        'b' => 'Da nova varijanta uvijek ima bolji učinak kod svih.',
                        'c' => 'Da je opažena promjena uz više istodobnih promjena, bez potvrde jednog uzroka.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Bilješka korisno opisuje okolnosti, ali ne izolira učinak napitka niti postavlja dijagnozu.',
                ],
            ],
            'source_ids' => [
                'nih-aloe',
                'nih-supplements',
            ],
            'product_keys' => [
                'forever-aloe-vera-gel',
                'aloe-berry-nectar',
                'forever-aloe-peaches',
                'forever-aloe-mango',
            ],
        ],
        [
            'id' => 'ukupni-unos-i-preklapanje-nutrijenata',
            'topic_id' => 'T12',
            'title' => 'Ukupni unos iz više dodataka',
            'summary' => 'Objasni kako povezati više proizvoda i izračunati ukupni unos istih vitamina ili minerala.',
            'minutes' => 9,
            'sections' => [
                [
                    'title' => 'Jedan nutrijent može se pojaviti na više mjesta',
                    'paragraphs' => ['NIH ODS upozorava da vitamini i minerali mogu dolaziti iz dodataka i obogaćene hrane. Zato pregled ne počinje brojem kutija, nego popisom nutrijenata. Dva proizvoda s različitim namjenama mogu sadržavati isti vitamin. Više različitih naziva na polici ne potvrđuje da se njihov sastav međusobno ne preklapa.', 'Prije dodavanja novog proizvoda pitaj kupca koje dodatke već koristi. Na primjer, vitamin C može se nalaziti u više vitaminskih formula. Svaku potvrđenu količinu upiši uz njegovu preporučenu dnevnu porciju.'],
                    'points' => [],
                ],
                [
                    'title' => 'Prvo uskladi porciju i jedinicu',
                    'paragraphs' => ['Deklarirana količina ima smisla tek uz porciju na koju se odnosi. Provjeri piše li po jednoj tableti, više tableta ili preporučenoj dnevnoj količini. Zatim usporedi jedinice. Miligram i mikrogram nisu jednaka količina. Ako se prikaz razlikuje, pretvori ga na istu jedinicu prije zbrajanja i jasno zabilježi račun.', 'Kupcu pokaži zbroj na istoj mjernoj osnovi i u istoj jedinici. Ako vrijednost već uključuje tekućinu za pripremu, taj se doprinos ne dodaje još jednom. Za podatak koji nedostaje pripremi konkretno pitanje o sastavu.'],
                    'points' => [],
                ],
                [
                    'title' => 'Zbroj odgovara na jedno, potreba na drugo pitanje',
                    'paragraphs' => ['Zbroj pokazuje koliko je potvrđeno iz odabranih izvora. Sam ne odgovara treba li osobi taj unos ili odgovara li njezinu zdravstvenom stanju. NIH ODS navodi da veći unos nije uvijek bolji i da visoke količine mogu povećati rizik nuspojava. Referentna vrijednost i gornja granica također nisu osobni cilj koji treba dosegnuti.', 'Preporuka proizlazi iz prehrambene potrebe i dostupnih izvora. Kod istog vitamina u više proizvoda prikaži doprinos svakoga i objasni zbog čega je kombinacija odabrana. Gornja granica i referentna vrijednost imaju različitu svrhu od osobnog cilja.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac koristi dva proizvoda s istim vitaminom i pita treba li treći. Izračunaj potvrđeni zbroj prema porcijama i jedinicama. Zatim objasni postojeće izvore, razlog eventualnog novog proizvoda i podatak koji još treba razjasniti.', 'Primjer razgovora: Prije nego dodamo novi vitaminski proizvod, pogledajmo što već uzimate. Zbrojit ćemo zajedničke vitamine iz preporučenih porcija i odabrati proizvode koji imaju jasnu zasebnu ulogu.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Ponavljanje nutrijenta provjerava se kroz dodatke i obogaćenu hranu.',
                'Količine se zbrajaju nakon usklađivanja porcije i jedinice.',
                'Provjeren zbroj i trajanje pakiranja ne određuju osobnu nutritivnu potrebu.',
            ],
            'quiz' => [
                [
                    'id' => 'un1',
                    'prompt' => 'Izmišljeni proizvod A daje 80 mg, a B 120 mg vitamina C po korištenoj dnevnoj porciji. Koji zbroj bilježiš?',
                    'options' => [
                        'a' => '200 mg iz ta dva proizvoda, uz zaseban pregled drugih izvora.',
                        'b' => '120 mg jer se računa samo proizvod s većom količinom.',
                        'c' => '160 mg jer oba imaju jednak broj porcija.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Količine na istoj osnovi zbrajaju se. Primjer ne određuje osobnu dozu i ne uključuje još neprovjerene izvore.',
                ],
                [
                    'id' => 'un2',
                    'prompt' => 'Tablica već prikazuje količinu vitamina u dnevnoj porciji od dvije tablete. Treba li broj ponovno množiti s dva?',
                    'options' => [
                        'a' => 'Da, svaka tablica uvijek govori o jednoj tableti.',
                        'b' => 'Ne, dvostruko množenje bi isti doprinos uračunalo dva puta.',
                        'c' => 'Da, ako pakiranje sadrži paran broj tableta.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Najprije se čita osnova tablice. Prikaz za cijelu dnevnu porciju već uključuje njezine tablete.',
                ],
                [
                    'id' => 'un3',
                    'prompt' => 'Pakiranje ima 90 tableta, a uputa navodi tri dnevno. Što iz toga možeš zaključiti?',
                    'options' => [
                        'a' => 'Da je svakoj osobi potrebno upravo toliko nutrijenta.',
                        'b' => 'Da ne treba usporediti nijedan drugi dodatak.',
                        'c' => 'Da pakiranje prema toj uputi traje 30 dana, dok osobna potreba ostaje zasebno pitanje.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Trajanje pakiranja aritmetički je podatak. Ne potvrđuje potrebu ili prikladnost unosa za određenu osobu.',
                ],
            ],
            'source_ids' => [
                'nih-supplements',
                'nih-calcium',
                'nih-vitamin-d',
            ],
            'product_keys' => [
                'forever-immublend',
                'forever-absorbent-c',
                'absorbent-d',
                'forever-calcium',
                'forever-nature-min',
                'forever-kids',
            ],
        ],
        [
            'id' => 'laktoza-i-prehrambeni-izbor',
            'topic_id' => 'T13',
            'title' => 'Intolerancija laktoze i nutritivno potpun izbor hrane',
            'summary' => 'Preporuči prehrambeno prikladan izbor kod intolerancije laktoze i objasni kalcij, vitamin D i sastav napitka.',
            'minutes' => 8,
            'sections' => [
                [
                    'title' => 'Laktoza, količina i podnošljivost',
                    'paragraphs' => ['Laktoza je šećer prisutan u mlijeku i mliječnim proizvodima. Intolerancija znači da nakon njezina unosa nastaju probavne tegobe povezane s nepotpunom razgradnjom. NIDDK naglašava da ljudi podnose različite količine. Zato zaključak o jednom velikom napitku nije automatski zaključak o svakoj namirnici i svakoj porciji. Intolerancija laktoze također se razlikuje od alergije na mlijeko.', 'Kupcu koji slabije podnosi laktozu pitaj koje namirnice i količine mu odgovaraju. Izbor može uključiti proizvode bez laktoze i druge dobro podnošljive izvore, uz očuvanje raznolikosti prehrane.'],
                    'points' => [],
                ],
                [
                    'title' => 'Sačuvaj raznolikost i važne hranjive tvari',
                    'paragraphs' => ['Potpuno izbacivanje svih mliječnih proizvoda nije potrebno svakoj osobi s intolerancijom. Neki podnose manje porcije uz obrok, jogurt ili tvrde sireve. Hrana s manje laktoze i proizvodi bez laktoze mogu proširiti izbor. Odgovarajući jelovnik traži ravnotežu između osobne podnošljivosti i dovoljnog unosa hranjivih tvari, umjesto što duljeg popisa zabrana.', 'Calcium može nadopuniti kalcij kada se zbog izbjegavanja mliječnih proizvoda unosi premalo. Absorbent D daje vitamin D važan za apsorpciju kalcija. Tako proizvode povezuješ s konkretnom prehrambenom potrebom.'],
                    'points' => [],
                ],
                [
                    'title' => 'Provjeri prašak i tekućinu za pripremu',
                    'paragraphs' => ['Kod Forever Lite Ultra, u katalogu označenog kao Forever Ultra, pregledaj točnu varijantu i aktualnu formulu. Pročitaj sastojke, podatke o mlijeku i uputu za pripremu. Nemoj proizvod unaprijed proglasiti proizvodom bez laktoze. Ako je podatak o laktozi nejasan, označi ga kao neprovjeren i pronađi službeno pojašnjenje. Navod o mliječnom sastojku i izmjerena količina laktoze nisu isti podatak.', 'Prašak i tekućina za pripremu zajedno određuju sastav napitka. Pri preporuci Lite Ultra ili Plant Proteina provjeri aktualne sastojke i navod o laktozi. Voda mijenja doprinos tekućine, a sastojci praška zadržavaju svoju ulogu.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac s intolerancijom laktoze želi proteinski napitak i izbjegava mliječne namirnice. Objasni zašto pregledavaš i prašak i tekućinu. Zatim objasni kada bi preporučio Calcium i Absorbent D za nadopunu prehrambenih izvora.', 'Primjer razgovora: Ako zbog laktoze izbjegavate mliječne proizvode, važno je imati druge izvore kalcija. Calcium i Absorbent D mogu nadopuniti prehrambeni unos prema potrebi. Za napitak ćemo provjeriti i prašak i tekućinu koju koristite.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Podnošljiva količina laktoze razlikuje se među ljudima.',
                'Ograničenje mliječnih proizvoda zahtijeva pregled izvora kalcija i vitamina D.',
                'Za napitak se provjeravaju aktualna formula praška i dodana tekućina.',
            ],
            'quiz' => [
                [
                    'id' => 'lk1',
                    'prompt' => 'Odrasla osoba ima potvrđenu intoleranciju, ali podnosi malu porciju jogurta. Kako razmatraš njezin izbor hrane?',
                    'options' => [
                        'a' => 'Svaki mliječni proizvod mora trajno izbaciti bez obzira na podnošljivost.',
                        'b' => 'Osobnu podnošljivost i nutritivnu potpunost razmatra zajedno sa stručnim planom.',
                        'c' => 'Podnošljiv jogurt potvrđuje da može popiti bilo koju količinu mlijeka.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Podnošljivost ovisi o osobi i količini. Cilj je odgovarajući izbor hrane uz dovoljan unos hranjivih tvari.',
                ],
                [
                    'id' => 'lk2',
                    'prompt' => 'Kupac tvrdi da je Lite Ultra sigurno bez laktoze kad se priprema s vodom. Koja informacija nedostaje?',
                    'options' => [
                        'a' => 'Provjera sastava i podatka o laktozi u samom prašku.',
                        'b' => 'Temperatura vode, jer ona određuje sadrži li prašak laktozu.',
                        'c' => 'Boja napitka nakon miješanja.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Voda ne potvrđuje sastav praška. Zaključak zahtijeva podatke za aktualnu varijantu proizvoda.',
                ],
                [
                    'id' => 'lk3',
                    'prompt' => 'Nakon smanjenja mliječnih proizvoda osoba pita treba li odmah dodati Calcium i Absorbent D. Koji je sljedeći korak?',
                    'options' => [
                        'a' => 'Odrediti dvostruku porciju oba proizvoda iz same intolerancije.',
                        'b' => 'Pretpostaviti da svaki biljni napitak sadrži dovoljno oba nutrijenta.',
                        'c' => 'Pregledati hranu, obogaćene proizvode i postojeće dodatke te potrebu procijeniti individualno.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Naziv dijagnoze ne otkriva ukupan unos. Deklaracije daju podatke za procjenu, a same ne određuju potrebu za dodatkom.',
                ],
            ],
            'source_ids' => [
                'nih-lactose',
                'nih-lactose-definition',
                'nih-calcium',
                'nih-vitamin-d',
                'nih-supplements',
            ],
            'product_keys' => [
                'forever-calcium',
                'absorbent-d',
                'forever-ultra',
            ],
        ],
        [
            'id' => 'celijakija-testiranje-i-deklaracije',
            'topic_id' => 'T14',
            'title' => 'Celijakija: testiranje, prehrana i deklaracije',
            'summary' => 'Razlikuj sumnju i potvrđenu celijakiju te pomozi kupcu izabrati provjereno prikladan i nutritivno potpun proizvod.',
            'minutes' => 9,
            'sections' => [
                [
                    'title' => 'Najprije razjasni sumnju i testiranje',
                    'paragraphs' => ['Probavne tegobe same ne potvrđuju celijakiju. NIDDK opisuje procjenu koja uključuje zdravstvenu i obiteljsku anamnezu te medicinske pretrage, često krvne testove i biopsiju tankog crijeva. Slični simptomi mogu se pojaviti kod drugih stanja. Zato pri učenju odvoji tri različite situacije: sumnju na celijakiju, postupak testiranja i već potvrđenu dijagnozu.', 'Ako osoba tek planira testiranje, izbacivanje glutena prethodno usklađuje s liječnikom jer promjena može utjecati na nalaze. Ako ga je već izbacila, prenosi taj podatak liječniku. Suradnik pomaže pripremiti pitanja i jasne informacije o prehrani.'],
                    'points' => [],
                ],
                [
                    'title' => 'Biljni naziv nije dokaz da nema glutena',
                    'paragraphs' => ['Nakon potvrđene celijakije stručni plan uključuje bezglutensku prehranu. Gluten je povezan s određenim žitaricama, među kojima su pšenica, ječam i raž. Pri kupnji se provjeravaju sastojci i navod o proizvodu bez glutena, a pri pripremi i mogućnost kontakta s hranom koja ga sadrži. Kontaminacija može nastati tijekom prerade, čuvanja ili pripreme.', 'Za preporuku konkretnog dodatka kupcu s potvrđenom celijakijom potrebno je službeno potvrditi prikladnost točnog proizvoda. Plant Protein ima proteinsku, B12 Plus vitaminsku, a Nature Min mineralnu ulogu. Podatak o glutenu i prehrambena potreba pregledavaju se zasebno.'],
                    'points' => [],
                ],
                [
                    'title' => 'Bezglutenski izbor treba ostati nutritivno potpun',
                    'paragraphs' => ['Provjera glutena i procjena hranjivih tvari odgovaraju na različita pitanja. Izostanak glutena ne govori koliko obrok sadrži proteina, vlakana, vitamina ili minerala. Zato nakon potvrđene dijagnoze pregledaj cijeli jelovnik sa stručnjakom. NIDDK naglašava uravnoteženu prehranu, a oznaka bez glutena sama nije mjerilo prehrambene raznolikosti.', 'Kada je prikladnost proizvoda potvrđena i postoji prehrambena potreba, objasni stvaran doprinos proteina, B12 ili minerala. Preporuka se nadovezuje na bezglutensku prehranu i postojeći stručni plan, prema pojedinom nutrijentu.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Prvi kupac sumnja na celijakiju i sutra planira izbaciti gluten. Drugi ima potvrđenu dijagnozu i traži proteinski proizvod. Objasni zašto se prvome preporučuje razgovor o testiranju, a drugome provjera prikladnosti točnog proizvoda i njegovog proteinskog doprinosa.', 'Primjer razgovora: Za vas biramo proizvod sa službeno potvrđenom prikladnošću za celijakiju. Zatim gledamo treba li vam proteinska, vitaminska ili mineralna dopuna. Svaki od tih proizvoda ima zasebnu prehrambenu ulogu.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Gluten se prije dijagnostičkog testiranja ne izbacuje samostalno.',
                'Biljno podrijetlo proizvoda ne potvrđuje da je bez glutena.',
                'Potvrda prikladnosti i procjena hranjivih tvari dvije su odvojene provjere.',
            ],
            'quiz' => [
                [
                    'id' => 'ce1',
                    'prompt' => 'Osoba ima nadutost i prije zakazanog testiranja želi potpuno izbaciti gluten. Koji je najbolji odgovor?',
                    'options' => [
                        'a' => 'Neka prvo uvede prehranu bez glutena, pa će testiranje biti pouzdanije.',
                        'b' => 'Nadutost je dovoljna za potvrdu celijakije pa testiranje nije potrebno.',
                        'c' => 'Neka prije promjene razgovara s liječnikom jer izbacivanje glutena može utjecati na nalaze.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Redoslijed je važan za dijagnostičku procjenu. Simptomi sami ne potvrđuju celijakiju.',
                ],
                [
                    'id' => 'ce2',
                    'prompt' => 'Na naslovnici proteinskog praška piše biljni protein, ali podatak o glutenu nije dostupan. Što možeš zaključiti?',
                    'options' => [
                        'a' => 'Prikladnost za osobu s celijakijom ostaje neprovjerena do pregleda aktualne deklaracije i pojašnjenja.',
                        'b' => 'Svi biljni proteinski prašci sigurno su bez glutena.',
                        'c' => 'Manja mjerica uklanja potrebu za provjerom glutena.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Naziv kategorije ne potvrđuje sastav cijele formule ni mogućnost kontakta s glutenom.',
                ],
                [
                    'id' => 'ce3',
                    'prompt' => 'Proizvod ima potvrđen navod bez glutena. Je li time riješeno i pitanje treba li osobi B12 ili mineralni dodatak?',
                    'options' => [
                        'a' => 'Da, oznaka potvrđuje dovoljan unos svih vitamina i minerala.',
                        'b' => 'Ne, za potrebu za dodatkom posebno se razmatraju prehrana i stručna procjena.',
                        'c' => 'Da, svaka potvrđena celijakija zahtijeva istu kombinaciju dodataka.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Podatak o glutenu odgovara na pitanje prikladnosti. Ne utvrđuje osobne nutritivne potrebe.',
                ],
            ],
            'source_ids' => [
                'nih-celiac-diagnosis',
                'nih-celiac-nutrition',
                'nih-supplements',
            ],
            'product_keys' => [
                'forever-plant-protein',
                'forever-b12-plus',
                'forever-nature-min',
            ],
        ],
        [
            'id' => 'apneja-i-dnevni-umor',
            'topic_id' => 'T15',
            'title' => 'Apneja u snu: hrkanje, stanke disanja i dnevni umor',
            'summary' => 'Prepoznaj kad je uz umor potrebna procjena sna i kako odvojeno preporučiti večernje navike i napitke.',
            'minutes' => 8,
            'sections' => [
                [
                    'title' => 'Umor promatraj zajedno s noćnim znakovima',
                    'paragraphs' => ['NHLBI među znakovima apneje u snu navodi disanje koje se zaustavlja i ponovno počinje, često glasno hrkanje i hvatanje zraka. Osoba ih ponekad ne primijeti sama, nego za njih sazna od nekoga tko je čuje dok spava. Tijekom dana mogu se pojaviti pospanost, umor te poteškoće s pažnjom i reagiranjem.', 'Kad kupac opisuje uočene stanke disanja i dnevnu pospanost, preporuči razgovor s liječnikom o procjeni sna. Istodobno možeš objasniti navike odmora, ali procjena disanja ostaje važan prvi dio pomoći kupcu.'],
                    'points' => [],
                ],
                [
                    'title' => 'Pripremi informacije koje pomažu procjeni',
                    'paragraphs' => ['Korisna bilješka opisuje vrijeme odlaska na spavanje i ustajanja, noćna buđenja te kada se javlja dnevna pospanost. Ako je druga osoba primijetila prekide disanja, zapiši njezin opis vlastitim riječima. Cilj je prenijeti konkretno opažanje zdravstvenom stručnjaku. Suradnik ne treba brojati navodne događaje i iz njih određivati težinu bolesti.', 'Kupcu pomozi odvojiti osobno opažanje od njegovog uzroka. Veća budnost nakon kofeina govori o osjećaju budnosti, dok uočene noćne stanke disanja i dalje prenosi liječniku. Tako se razgovor usmjerava na stvarnu potrebu.'],
                    'points' => [],
                ],
                [
                    'title' => 'Večernje navike i kofein imaju zasebnu ulogu',
                    'paragraphs' => ['NHLBI za zdrave navike sna preporučuje ujednačen raspored, mirno vrijeme prije spavanja i prikladnu sobu za san. Kofein može ometati san, a njegovi učinci mogu trajati satima. Zato u pregledu dana zabilježi sve izvore kofeina i vrijeme unosa. Raspored koji je dobar za jednu osobu nije automatski prikladan za svaku smjenu i svaki dnevni ritam.', 'Herbal Tea može biti ugodan večernji biljni napitak. Therm i Focus imaju drugi sastav i svrhu, pa se njihov kofein i vrijeme uzimanja promatraju uz ostale izvore. Nijedan se ne preporučuje kao liječenje prekida disanja.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac glasno hrče, partner vidi stanke disanja, a tijekom dana teško ostaje budan. Sastavi odgovor koji prvo vodi procjeni sna. Zatim objasni koje večernje navike i vrijeme kofeina može pregledati te gdje ima mjesto ugodan biljni napitak.', 'Primjer razgovora: Budući da su uočene stanke disanja i imate dnevnu pospanost, preporučujem razgovor s liječnikom o snu. Za mirniji završetak dana možemo odvojeno razmotriti Herbal Tea i ranije vrijeme unosa kofeina.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Uočene stanke disanja i dnevna pospanost zahtijevaju razgovor o procjeni sna.',
                'Osjećaj budnosti ne potvrđuje uredno disanje tijekom spavanja.',
                'Sastav, količina kofeina i vrijeme unosa provjeravaju se zasebno za svaki proizvod.',
            ],
            'quiz' => [
                [
                    'id' => 'ap1',
                    'prompt' => 'Partner opisuje stanke disanja tijekom noći, a osoba teško ostaje budna tijekom dana. Koji je sljedeći korak?',
                    'options' => [
                        'a' => 'Razgovor s liječnikom o znakovima i mogućoj procjeni spavanja.',
                        'b' => 'Povećanje porcije proizvoda za koncentraciju kao provjera uzroka.',
                        'c' => 'Čekanje da sam naziv večernjeg čaja objasni tegobe.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Opis uključuje znakove važne za procjenu apneje. Odabir proizvoda ne utvrđuje uzrok.',
                ],
                [
                    'id' => 'ap2',
                    'prompt' => 'Osoba nakon kave osjeća više energije. Što taj podatak govori o ranije opaženim stankama disanja?',
                    'options' => [
                        'a' => 'Potvrđuje da više nema potrebe za procjenom sna.',
                        'b' => 'Ne potvrđuje jesu li stanke disanja nestale ni što ih uzrokuje.',
                        'c' => 'Dokazuje da je glavni uzrok bio nedostatak dodataka prehrani.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Subjektivna budnost i disanje tijekom spavanja različiti su podaci. Opaženi noćni znakovi i dalje se prenose liječniku.',
                ],
                [
                    'id' => 'ap3',
                    'prompt' => 'U izmišljenoj deklaraciji proizvoda za energiju naveden je kofein, a osoba ga uzima neposredno prije spavanja. Što treba pregledati?',
                    'options' => [
                        'a' => 'Samo okus, jer naziv za energiju jamči bolji san.',
                        'b' => 'Samo broj tableta u pakiranju.',
                        'c' => 'Količinu i vrijeme svih izvora kofeina, uz odvojenu procjenu eventualnih stanki disanja.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Kofein može ometati san. Pregled navika ne zamjenjuje procjenu znakova koji upućuju na poremećaj disanja.',
                ],
            ],
            'source_ids' => [
                'nih-sleep-apnea',
                'nih-sleep',
                'nih-supplements',
            ],
            'product_keys' => [
                'aloe-blossom-herbal-tea',
                'forever-therm',
                'forever-focus',
            ],
        ],
        [
            'id' => 'bubrezna-bolest-i-prehrambeni-plan',
            'topic_id' => 'T16',
            'title' => 'Kronična bubrežna bolest: individualne potrebe za proteinima i mineralima',
            'summary' => 'Pomozi kupcu s bubrežnom bolešću prenijeti konkretne podatke o proteinu, mineralima i pripremljenom napitku njegovom zdravstvenom timu.',
            'minutes' => 9,
            'sections' => [
                [
                    'title' => 'Prehrambeni plan ovisi o stanju i liječenju',
                    'paragraphs' => ['Kod kronične bubrežne bolesti ne postoji jedan jelovnik za sve odrasle osobe. NIDDK opisuje potrebe koje se mijenjaju s napredovanjem bolesti i liječenjem. Stručni plan povezuje stanje bubrega, nalaze i nutritivne potrebe. Tema bubrežnog kamenca zato nije zamjena za ovu temu, a opća preporuka za zdravu odraslu osobu ne određuje plan kod kronične bolesti.', 'Kupcu koji već ima prehrambeni plan pripremi jasan sastav proizvoda koji razmatra. Za osobni odabir proizvoda i količine uključuje se liječnik ili dijetetičar koji poznaje njegovo stanje i nalaze.'],
                    'points' => [],
                ],
                [
                    'title' => 'Proteini prije dijalize i tijekom dijalize',
                    'paragraphs' => ['Nekim osobama koje nisu na dijalizi potrebno je prilagoditi unos proteina. Premalo proteina također može narušiti nutritivni status. Tijekom dijalize potrebe se mogu povećati jer se liječenjem gube proteini, a razlike postoje i među vrstama dijalize. Zato jednaka dijagnoza ne opravdava ni univerzalno povećavanje ni univerzalno smanjivanje proteina.', 'Za Plant Protein i Lite Ultra prikaži protein po stvarnoj porciji te doprinos tekućine za pripremu. To su konkretni podaci koje zdravstveni tim povezuje s osobnim ciljem proteina prije ili tijekom dijalize.'],
                    'points' => [],
                ],
                [
                    'title' => 'Minerali i tekućina traže zasebne provjere',
                    'paragraphs' => ['Prehrambeni pregled može uključivati natrij, kalij, fosfor i tekućinu. Ciljevi se određuju individualno, a ograničenje tekućine ne propisuje se svima jednako. Kod osobe na hemodijalizi zdravstveni tim određuje količinu tekućine, a u zbroj se prema planu uključuju i određene namirnice. Zato preporuku pij više vode ne prenosi automatski u ovaj razgovor.', 'Nature Min daje mineralnu formulu. Za bilo koji novi dodatak prikaži dostupne količine natrija, kalija, fosfora i ostalih relevantnih minerala. Neobjavljeni podatak razjašnjava se s kompanijom Forever Living Products ili zdravstvenim timom prije osobnog odabira.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac s bubrežnom bolešću pita može li uključiti proteinski napitak. Prikaži stvarnu porciju, protein, dostupne minerale i tekućinu za pripremu. Zatim navedi tri pitanja za njegov tim: cilj proteina, minerali koje prati i ukupni unos tekućine.', 'Primjer razgovora: Pripremit ćemo sastav i porciju proizvoda koji vas zanima, uključujući protein, minerale i tekućinu za napitak. Vaš zdravstveni tim zatim može potvrditi uklapa li se u vaš postojeći plan.'],
                    'points' => [],
                ],
            ],
            'takeaways' => [
                'Potrebe za proteinima mogu se razlikovati prije dijalize i tijekom dijalize.',
                'Natrij, kalij, fosfor i tekućina razmatraju se prema individualnom planu.',
                'Nenavedena količina u deklaraciji ne znači da sastojak nije prisutan.',
            ],
            'quiz' => [
                [
                    'id' => 'bb1',
                    'prompt' => 'Dvije osobe imaju kroničnu bubrežnu bolest, ali samo jedna je na dijalizi. Možeš li im izabrati istu porciju proteinskog praška?',
                    'options' => [
                        'a' => 'Da, naziv bolesti određuje jednaku potrebu za proteinima.',
                        'b' => 'Da, ako je izvor proteina biljni.',
                        'c' => 'Ne bez stručnog plana, jer stanje i dijaliza mogu promijeniti potrebe.',
                    ],
                    'correct_option' => 'c',
                    'explanation' => 'Količina proteina procjenjuje se individualno. Dijaliza može povećati potrebe, a pakiranje ne određuje osobni cilj.',
                ],
                [
                    'id' => 'bb2',
                    'prompt' => 'Na dostupnoj deklaraciji praška nema podatka o fosforu. Kako ga upisuješ u karticu za stručnu procjenu?',
                    'options' => [
                        'a' => 'Kao podatak koji nije potvrđen i treba ga dodatno provjeriti.',
                        'b' => 'Kao nulu jer bi svaka prisutna količina sigurno bila navedena.',
                        'c' => 'Kao sigurnu količinu jer je proizvod u prahu.',
                    ],
                    'correct_option' => 'a',
                    'explanation' => 'Nedostajući podatak ne potvrđuje odsutnost. Potrebno je službeno pojašnjenje za procjenu uklapanja u plan.',
                ],
                [
                    'id' => 'bb3',
                    'prompt' => 'Osoba na hemodijalizi ima plan tekućine i želi svaki dan dodati novi napitak. Što treba napraviti?',
                    'options' => [
                        'a' => 'Zanemariti napitak jer tekućina s proteinima nije voda.',
                        'b' => 'Prikazati cijelu pripremu i uskladiti njezin doprinos s planom zdravstvenog tima.',
                        'c' => 'Automatski udvostručiti dnevnu količinu tekućine.',
                    ],
                    'correct_option' => 'b',
                    'explanation' => 'Pripremljeni napitak ima doprinos unosu tekućine i hranjivih tvari. Usklađuje se s individualnim planom.',
                ],
            ],
            'source_ids' => [
                'nih-ckd-nutrition',
                'nih-hemodialysis-nutrition',
                'nih-supplements',
            ],
            'product_keys' => [
                'forever-plant-protein',
                'forever-ultra',
                'forever-nature-min',
            ],
        ],
    ],
    'sources' => [
        'aad-contact-dermatitis' => [
            'title' => 'AAD: kontaktni dermatitis',
            'url' => 'https://www.aad.org/public/diseases/eczema/types/contact-dermatitis',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'aad-dry-skin' => [
            'title' => 'AAD: njega suhe kože i izbor hidratantnog proizvoda',
            'url' => 'https://www.aad.org/public/everyday-care/skin-care-basics/dry/dermatologists-tips-relieve-dry-skin',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'aad-moisturizer-selection' => [
            'title' => 'American Academy of Dermatology: vrste hidratantnih proizvoda i uloge sastojaka',
            'url' => 'https://www.aad.org/public/everyday-care/skin-care-basics/dry/pick-moisturizer',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'eu-cosing' => [
            'title' => 'Europska komisija: CosIng i zajednički nazivi kozmetičkih sastojaka',
            'url' => 'https://single-market-economy.ec.europa.eu/sectors/cosmetics/cosmetic-ingredient-database_en',
            'kind' => 'Službeni stručni izvor o kozmetičkim sastojcima',
            'reviewed_at' => '2026-10-07',
        ],
        'nei-dream-trial' => [
            'title' => 'NEI: rezultati primarnog DREAM pokusa omega 3 za suho oko',
            'url' => 'https://www.nei.nih.gov/research-and-training/research-news/omega-3s-fish-oil-supplements-no-better-placebo-dry-eye',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nei-dry-eye' => [
            'title' => 'NEI: suho oko, zasloni i promjene navika',
            'url' => 'https://www.nei.nih.gov/eye-health-information/eye-conditions-and-diseases/dry-eye',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'niams-arthritis-types' => [
            'title' => 'NIH NIAMS: različite vrste artritisa',
            'url' => 'https://www.niams.nih.gov/health-topics/arthritis',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'niams-skin-learning' => [
            'title' => 'NIH NIAMS: građa kože i njezina zaštitna uloga',
            'url' => 'https://www.niams.nih.gov/health-topics/educational-resources/health-lesson-learning-about-skin',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-aloe' => [
            'title' => 'NIH NCCIH: Aloe Vera',
            'url' => 'https://www.nccih.nih.gov/health/aloe-vera',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-b12' => [
            'title' => 'NIH ODS: vitamin B12 i prehrambeni izvori',
            'url' => 'https://ods.od.nih.gov/factsheets/VitaminB12-Consumer/',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-calcium' => [
            'title' => 'NIH ODS: kalcij i koštano zdravlje',
            'url' => 'https://ods.od.nih.gov/factsheets/Calcium-HealthProfessional/',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-celiac-diagnosis' => [
            'title' => 'NIH NIDDK: dijagnoza celijakije',
            'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/diagnosis',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-celiac-nutrition' => [
            'title' => 'NIH NIDDK: prehrana, gluten i priprema hrane kod celijakije',
            'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/celiac-disease/eating-diet-nutrition',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-ckd-nutrition' => [
            'title' => 'NIH NIDDK: prehrana kod kronične bubrežne bolesti',
            'url' => 'https://www.niddk.nih.gov/health-information/kidney-disease/chronic-kidney-disease-ckd/healthy-eating-adults-chronic-kidney-disease',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-fiber' => [
            'title' => 'NIH NIDDK: unos vlakana i tekućine',
            'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/constipation/eating-diet-nutrition',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-gas' => [
            'title' => 'NIH NIDDK: nastanak plinova i nadutosti',
            'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/gas-digestive-tract/symptoms-causes',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-glucosamine' => [
            'title' => 'NIH NCCIH: glukozamin i hondroitin kod osteoartritisa',
            'url' => 'https://www.nccih.nih.gov/health/glucosamine-and-chondroitin-for-osteoarthritis-what-you-need-to-know',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-hemodialysis-nutrition' => [
            'title' => 'NIH NIDDK: prehrana i unos tekućine tijekom hemodijalize',
            'url' => 'https://www.niddk.nih.gov/health-information/kidney-disease/kidney-failure/hemodialysis/eating-nutrition',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-lactose' => [
            'title' => 'NIH NIDDK: prehrana kod intolerancije laktoze',
            'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/lactose-intolerance/eating-diet-nutrition',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-lactose-definition' => [
            'title' => 'NIH NIDDK: što je intolerancija laktoze',
            'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/lactose-intolerance/definition-facts',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-omega3' => [
            'title' => 'NIH ODS: EPA i DHA u prehrani',
            'url' => 'https://ods.od.nih.gov/factsheets/Omega3FattyAcids-Consumer/',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-performance' => [
            'title' => 'NIH ODS: proteini, arginin i tjelesna aktivnost',
            'url' => 'https://ods.od.nih.gov/factsheets/ExerciseAndAthleticPerformance-HealthProfessional/',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-probiotics-ods' => [
            'title' => 'NIH ODS: probiotički sojevi i klinički nalazi',
            'url' => 'https://ods.od.nih.gov/factsheets/Probiotics-HealthProfessional/',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-sleep' => [
            'title' => 'NIH NHLBI: navike za zdrav san',
            'url' => 'https://www.nhlbi.nih.gov/health/sleep-deprivation/healthy-sleep-habits',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-sleep-apnea' => [
            'title' => 'NIH NHLBI: simptomi apneje u snu',
            'url' => 'https://www.nhlbi.nih.gov/health/sleep-apnea/symptoms',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-supplements' => [
            'title' => 'NIH ODS: dodaci prehrani i sigurnost ukupnog unosa',
            'url' => 'https://ods.od.nih.gov/factsheets/WYNTK-Consumer/',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
        'nih-vitamin-d' => [
            'title' => 'NIH ODS: vitamin D, nutritivne funkcije i ukupan unos',
            'url' => 'https://ods.od.nih.gov/factsheets/VitaminD-Consumer/',
            'kind' => 'Službeni medicinski ili stručni izvor',
            'reviewed_at' => '2026-10-07',
        ],
    ],
    'completed_topic_ids' => [
        'T01',
        'T02',
        'T03',
        'T04',
        'T05',
        'T06',
        'T07',
        'T08',
        'T09',
        'T10',
        'T11',
        'T12',
        'T13',
        'T14',
        'T15',
        'T16',
    ],
];
