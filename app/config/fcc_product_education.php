<?php

/* Educational content. Product links identify study material, not indications. */
return [
    'version' => '2026.10.07.1',
    'title' => 'Edukacija o proizvodima',
    'description' => 'Prouči proizvode, bioaktivne sastojke i preporuke iz edukacijskih materijala. Pronađi problem, upoznaj pripadajuće proizvode i kombinacije te nauči objasniti ulogu svakog sastojka.',
    'lessons' => [
        [
            'id' => 'probava-osnove',
            'title' => 'Kako probava pretvara hranu u hranjive tvari',
            'summary' => 'Razumij ulogu probavnih organa i objasni kada se preporučuju vlakna, probiotik ili proteinska dopuna.',
            'minutes' => 6,
            'sections' => [
                [
                    'title' => 'Razgradnja hrane i apsorpcija hranjivih tvari',
                    'paragraphs' => ['Probava razgrađuje hranu u dijelove koje tijelo može apsorbirati i koristiti. Proteini se razgrađuju u aminokiseline, ugljikohidrati u jednostavnije šećere, a masti u masne kiseline i druge manje sastavnice. U tome sudjeluju organi, enzimi, živci i hormoni. Vitamini, minerali i voda također su dio prehrambenog unosa.', 'Razgradnja priprema hranu, a apsorpcija znači prijelaz hranjivih tvari kroz crijevnu stijenku. Kad kupac pita za probavu, prvo razjasni misli li na nadutost, stolicu, osjetljiv želudac ili unos hranjivih tvari. Tako preporuku povezuješ s konkretnom potrebom.'],
                    'points' => [],
                ],
                [
                    'title' => 'Usta, želudac, tanko i debelo crijevo',
                    'paragraphs' => ['Žvakanje usitnjava hranu i miješa je sa slinom. Jednjak je prenosi u želudac, koji je miješa s probavnim sokovima. Tanko crijevo nastavlja razgradnju i apsorbira većinu hranjivih tvari. Debelo crijevo apsorbira vodu i oblikuje stolicu. Gušterača stvara enzime, jetra žuč, a žučni mjehur je pohranjuje.', 'Primjer obroka s kruhom, povrćem i proteinskom namirnicom pokazuje različite uloge hranjivih tvari. Proteinski proizvod nadopunjuje proteine, a dodatak vlakana daje vlakna. Razumijevanje tih uloga pomaže izabrati proizvod i objasniti zašto odgovara prehrambenoj potrebi kupca.'],
                    'points' => [],
                ],
                [
                    'title' => 'Kako povezati potrebu s proizvodom',
                    'paragraphs' => ['Ako osoba jede malo biljnih namirnica i ima neredovitu stolicu, razgovor se usmjerava na vlakna i tekućinu. Ako pita za probiotičku dopunu, Active Pro B donosi žive mikroorganizme. Ako preskače proteinske namirnice, proteinski napitak može nadopuniti njihov unos.', 'Kod gastritisa, refluksa i nadutosti preporučuju se Aloe Vera Gel i Active Pro B. Povezane kartice objašnjavaju primjenu i ulogu svakog proizvoda. Raznolika prehrana ostaje temelj na koji se preporučeni proizvodi nadovezuju.'],
                    'points' => ['Prepoznaj konkretnu probavnu ili prehrambenu potrebu.', 'Objasni zasebnu ulogu svakog odabranog proizvoda.', 'Pripremi primjer preporuke razumljiv kupcu.'],
                ],
                [
                    'title' => 'Vježba: preporuka prema konkretnom pitanju',
                    'paragraphs' => ['Kupac kaže da rijetko jede povrće i mahunarke. Objasni kako vlakna sudjeluju u probavi i zašto bi Fiber Fusion mogao nadopuniti njihov unos. Drugom kupcu, koji pita za probiotičku dopunu, objasni zašto Active Pro B ima drugu ulogu.', 'Primjer razgovora: Ako vam u prehrani nedostaje vlakana, Fiber Fusion može nadopuniti njihov unos. Active Pro B donosi probiotičke mikroorganizme. Preporuku ćemo povezati s vašim obrocima i onim što želite poboljšati.'],
                    'points' => [],
                ],
            ],
            'takeaways' => ['Većina hranjivih tvari apsorbira se u tankom crijevu.', 'Vlakna, probiotik i proteinski napitak imaju različite prehrambene uloge.', 'Preporuka počinje konkretnom potrebom kupca i sastavom proizvoda.'],
            'quiz' => [
                ['id' => 'p1', 'prompt' => 'Gdje se apsorbira većina hranjivih tvari iz hrane?', 'options' => ['a' => 'U tankom crijevu.', 'b' => 'Isključivo u želucu.', 'c' => 'Isključivo u debelom crijevu.'], 'correct_option' => 'a', 'explanation' => 'Tanko crijevo apsorbira većinu hranjivih tvari. Debelo crijevo ima važnu ulogu u apsorpciji vode i oblikovanju stolice.'],
                ['id' => 'p2', 'prompt' => 'Kako kupcu povezuješ probavne potrebe s karticom konkretnog proizvoda?', 'options' => [
    'a' => 'Zaključiš da proizvod rješava svaku probavnu tegobu.',
    'b' => 'Objasniš njegov stvarni sastav, porciju i zasebnu prehrambenu ulogu.',
    'c' => 'Predložiš ga kao zamjenu za raznoliku prehranu.',
], 'correct_option' => 'b', 'explanation' => 'Kartica proizvoda daje sastav i uporabu za konkretno objašnjenje njegove uloge. Primjena se povezuje s potrebom osobe i odgovarajućom preporukom.'],
                ['id' => 'p3', 'prompt' => 'Kupac pita koliko proteina sadrži jedna porcija. Što prvo provjeravaš?', 'options' => ['a' => 'Boju pakiranja.', 'b' => 'Tvrdnje iz stare knjižice.', 'c' => 'Aktualnu nutritivnu deklaraciju i veličinu porcije.'], 'correct_option' => 'c', 'explanation' => 'Količina hranjive tvari ima smisla tek kad znamo na koju porciju i aktualnu formulu se odnosi.'],
            ],
            'source_ids' => ['nih-digestion', 'nih-supplements'],
            'product_keys' => ['forever-fiber', 'forever-fields-of-greens', 'forever-ultra'],
        ],
        [
            'id' => 'vlakna-i-navike',
            'title' => 'Vlakna, tekućina i svakodnevna prehrana',
            'summary' => 'Nauči kome preporučiti Fiber Fusion, zašto su vlakna i tekućina povezani te kako objasniti njihovu primjenu.',
            'minutes' => 6,
            'sections' => [
                [
                    'title' => 'Što vlakna donose prehrani',
                    'paragraphs' => ['Vlakna su sastavnice biljne hrane koje probavni enzimi ne razgrađuju u potpunosti. Nalaze se u cjelovitim žitaricama, mahunarkama, voću, povrću i orašastim plodovima. Ovisno o vrsti, utječu na svojstva crijevnog sadržaja i mogu služiti kao supstrat crijevnim mikrobima.', 'Preporuka vlakana ima smisla osobi čiji obroci sadrže malo tih namirnica, osobito kada pita za redovitost stolice. Jasno objasni koliko vlakana porcija dodaje prehrani. Vrsta i grami vlakana daju korisniju usporedbu od broja biljaka na pakiranju.'],
                    'points' => [],
                ],
                [
                    'title' => 'Postupno povećavanje vlakana i tekućina',
                    'paragraphs' => ['NIDDK preporučuje postupno povećavanje unosa vlakana radi prilagodbe probave. Odgovarajuća tekućina pomaže njihovoj funkciji. Potrebe za tekućinom ovise o zdravlju, aktivnosti i okolini.', 'Kupcu preporuči postupno uvođenje prema uputi proizvoda uz obroke bogatije vlaknima. Ako već ima liječnički plan s ograničenjem tekućine, preporuka se uklapa u taj plan. Postupnost i praćenje podnošljivosti dio su praktične uporabe.'],
                    'points' => [],
                ],
                [
                    'title' => 'Fiber Fusion i stvaran doprinos porcije',
                    'paragraphs' => ['Aktualni Fiber Fusion je žele u sticku koji daje 8 g vlakana iz kukuruza, jabuke, citrusa i psilijuma. Preporučuje se za nadopunu prehrane siromašne vlaknima. Njegov oblik i način uporabe razlikuju se od povijesnog praška Forever Fiber.', 'Pri usporedbi dva dodatka promatraj vrstu vlakana, grame po porciji i način uporabe. Funkcija pojedinog sastojka ovisi o njegovoj vrsti i količini, pa se konkretna zdravstvena tvrdnja povezuje s točnim sastojkom i njezinim uvjetima.'],
                    'points' => ['Fiber Fusion daje 8 g vlakana po sticku.', 'Vlakna dodaj uz prehranu i odgovarajuću tekućinu.', 'Uspoređuj vrstu i grame vlakana po porciji.'],
                ],
                [
                    'title' => 'Vježba: preporuči vlakna kupcu',
                    'paragraphs' => ['Kupac jede malo povrća, voća i mahunarki te pita za neredovitu stolicu. U dvije rečenice objasni zašto preporučuješ Fiber Fusion i kako ga povezati s hranom i tekućinom. Zatim navedi razliku između vlakana i probiotika.', 'Primjer razgovora: Fiber Fusion preporučujem za nadopunu vlakana koja vam nedostaju u obrocima. Jedan stick daje 8 g vlakana. Uvedite ga prema uputi uz dovoljno tekućine i postupno obogatite obroke biljnim namirnicama.'],
                    'points' => [],
                ],
            ],
            'takeaways' => ['Vlakna se promatraju kroz cijelu prehranu i stvarnu količinu po porciji.', 'Fiber Fusion daje 8 g vlakana po sticku.', 'Unos vlakana povećava se postupno uz odgovarajuću tekućinu.'],
            'quiz' => [
                ['id' => 'v1', 'prompt' => 'Što je najbolji temelj za usporedbu doprinosa dva proizvoda unosu vlakana?', 'options' => ['a' => 'Broj biljaka spomenutih na pakiranju.', 'b' => 'Boja napitka.', 'c' => 'Vrsta i grami vlakana po usporedivoj porciji.'], 'correct_option' => 'c', 'explanation' => 'Količina, vrsta vlakna i porcija daju provjerljivu osnovu za usporedbu.'],
                ['id' => 'v2', 'prompt' => 'Kako NIDDK opisuje povećavanje unosa vlakana?', 'options' => ['a' => 'Postupno, uz odgovarajući unos tekućine.', 'b' => 'Odmah najvećom mogućom dozom.', 'c' => 'Bez obzira na postojeći prehrambeni plan.'], 'correct_option' => 'a', 'explanation' => 'Postupna promjena omogućuje prilagodbu. Potrebe za tekućinom i poseban prehrambeni plan razmatraju se pojedinačno.'],
                ['id' => 'v3', 'prompt' => 'Za jedan sastojak postoji dopuštena zdravstvena tvrdnja. Vrijedi li automatski za svaki proizvod s vlaknima?', 'options' => ['a' => 'Da, svi izvori vlakana imaju iste uvjete.', 'b' => 'Ne, treba provjeriti sastojak, količinu i uvjete tvrdnje.', 'c' => 'Da, ako je proizvod prirodan.'], 'correct_option' => 'b', 'explanation' => 'Uvjeti tvrdnje odnose se na konkretan sastojak i propisanu uporabu.'],
            ],
            'source_ids' => ['nih-fiber', 'eu-claims', 'forever-fiber-fusion-hr'],
            'product_keys' => ['forever-fiber', 'forever-fields-of-greens'],
        ],
        [
            'id' => 'mikrobiota-i-probiotici',
            'title' => 'Mikrobiota, probiotici i prebiotici',
            'summary' => 'Razlikuj probiotičke mikroorganizme od vlakana i objasni primjenu Active Pro B u povezanim preporukama.',
            'minutes' => 7,
            'sections' => [
                [
                    'title' => 'Mikrobiota, probiotik i prebiotik',
                    'paragraphs' => ['Mikrobiota je zajednica mikroorganizama. U probavnom sustavu neki sudjeluju u razgradnji sastavnica hrane. Probiotici su živi mikroorganizmi namijenjeni zdravstvenoj koristi. Prebiotici su određene neprobavljive sastavnice hrane koje selektivno potiču poželjne mikroorganizme.', 'Active Pro B donosi žive probiotičke mikroorganizme, a Fiber Fusion prehrambena vlakna. Aloe Vera Gel je napitak s unutarnjim gelom aloe. Kad se proizvodi povežu, kupac treba razumjeti zasebnu ulogu svakog proizvoda.'],
                    'points' => [],
                ],
                [
                    'title' => 'Što znače vrsta, soj i CFU',
                    'paragraphs' => ['Vrsta označava skupinu mikroorganizama, a soj konkretnu varijantu unutar te vrste. CFU opisuje broj održivih mikroorganizama koji mogu stvoriti koloniju. Ti podaci, porcija i čuvanje važni su za razumijevanje proizvoda.', 'Istraživanja povezuju rezultat s određenim sojem, formulom i uvjetima uporabe. Zato sastav Active Pro B objasni prema aktualnom proizvodu. Pri usporedbi probiotičkih pripravaka promatra se puni identitet, količina i čuvanje, zajedno s istraženom primjenom.'],
                    'points' => ['Identitet mikroorganizma uključuje vrstu i soj.', 'CFU čitaj uz porciju i podatak o roku.', 'Čuvanje je važno za održivost mikroorganizama.'],
                ],
                [
                    'title' => 'Kome preporučiti probiotičku dopunu',
                    'paragraphs' => ['Kod gastritisa, refluksa i nadutosti preporučuje se Active Pro B uz Aloe Vera Gel. Za odraslu osobu koja pita za probiotičku dopunu tijekom ili nakon antibiotika objašnjava se njegova probiotička uloga i prikladan raspored uz konkretnu terapiju.', 'NCCIH opisuje veći sigurnosni rizik kod teško bolesnih osoba i osoba s oslabljenim imunološkim sustavom. U tim okolnostima liječnik procjenjuje prikladnost proizvoda. Razmak uz antibiotik provjerava se s liječnikom ili ljekarnikom prema konkretnoj terapiji.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: objasni kombinaciju kupcu',
                    'paragraphs' => ['Kupac pita za kombinaciju Gela i Pro B uz nadutost. Objasni da Pro B donosi žive mikroorganizme, a Gel unutarnji gel aloe. Zatim objasni što bi u takvoj preporuci značilo dodavanje vlakana.', 'Primjer razgovora: Kod nadutosti preporučujem Aloe Vera Gel i Active Pro B. Pro B donosi probiotičke mikroorganizme, a Gel aloe napitak. Ako u obrocima nedostaje vlakana, preporuku dopunite odgovarajućim izvorom vlakana.'],
                    'points' => [],
                ],
            ],
            'takeaways' => ['Probiotik donosi žive mikroorganizme, a prebiotik određenu hranu za mikroorganizme.', 'Soj, količina i čuvanje pomažu razumjeti probiotički proizvod.', 'Gel, Pro B i vlakna imaju zasebne uloge u povezanim preporukama.'],
            'quiz' => [
                ['id' => 'm1', 'prompt' => 'Što opisuje prebiotik?', 'options' => ['a' => 'Bilo koji živi mikroorganizam.', 'b' => 'Neprobavljivu sastavnicu hrane koja selektivno potiče poželjne mikroorganizme.', 'c' => 'Svaki vitamin u napitku.'], 'correct_option' => 'b', 'explanation' => 'Prebiotik predstavlja određenu sastavnicu hrane. Probiotik je živi mikroorganizam.'],
                ['id' => 'm2', 'prompt' => 'Što je potrebno za usporedbu probiotičkog proizvoda s kliničkim istraživanjem?', 'options' => ['a' => 'Točan mikroorganizam i soj, količina, formula i istraženi uvjeti.', 'b' => 'Samo najveći broj vrsta.', 'c' => 'Samo činjenica da je proizvod u kapsuli.'], 'correct_option' => 'a', 'explanation' => 'Identitet i istraženi uvjeti određuju na što se dokaz odnosi.'],
                ['id' => 'm3', 'prompt' => 'Teško bolesna osoba pita je li probiotički proizvod siguran za nju. Kako odgovaraš?', 'options' => ['a' => 'Svaki probiotik potpuno je siguran jer je prirodan.', 'b' => 'Povećaj preporučenu dozu.', 'c' => 'Potrebna je procjena liječnika uz aktualni sastav i terapiju.'], 'correct_option' => 'c', 'explanation' => 'Kod teško bolesnih osoba i oslabljenog imuniteta sigurnost se ne pretpostavlja iz kategorije proizvoda.'],
            ],
            'source_ids' => ['nih-probiotics', 'nih-supplements-wisely'],
            'product_keys' => ['forever-active-pro-b', 'forever-fiber', 'forever-immublend'],
        ],
        [
            'id' => 'aloe-sastav-i-dokazi',
            'title' => 'Aloe vera: unutarnji gel, polisaharidi i primjena napitaka',
            'summary' => 'Upoznaj što unutarnji gel aloe sadrži i kako preporučiti Gel i Active Pro B uz probavne tegobe.',
            'minutes' => 7,
            'sections' => [
                [
                    'title' => 'Unutarnji gel, lateks i cijeli list',
                    'paragraphs' => ['Unutarnji gel lista aloe najvećim je dijelom voda i sadrži polisaharide, među kojima je acemannan. Lateks se nalazi uz vanjski dio lista i sadrži druge tvari, uključujući antrakinone povezane s laksativnim djelovanjem. Cijeli list obuhvaća različite dijelove, pa su način obrade i vrsta pripravka važni.', 'Forever Aloe Vera Gel je oralni aloe napitak. Kozmetički gel, sprej ili Activator imaju vanjsku namjenu i svoje upute. Kozmetički Activator ne koristi se kao kapi za oči. Razlika dijela lista i načina primjene temelj je profesionalnog objašnjenja proizvoda.'],
                    'points' => [],
                ],
                [
                    'title' => 'Polisaharidi i bioaktivni sastojci',
                    'paragraphs' => ['Polisaharidi su složeni ugljikohidrati građeni od povezanih šećernih jedinica. Acemannan sadrži pretežito povezane manozne jedinice i istražuje se zbog građe i interakcija s imunološkim stanicama. To objašnjava što znači bioaktivni sastojak aloe.', 'Za gotovi napitak važno je znati oblik, obradu i količine koje navodi Forever Living Products. Istraživanja bioloških mehanizama acemannana daju znanje o sastojku, a klinički rezultat određene formule zasebna je informacija. Postotak aloe u napitku nije istovjetan postotku acemannana.'],
                    'points' => ['Unutarnji gel donosi vodu i polisaharide.', 'Acemannan je jedan od karakterističnih polisaharida aloe.', 'Bioaktivni sastojak i klinički rezultat proizvoda različiti su podaci.'],
                ],
                [
                    'title' => 'Gel i Pro B kod gastritisa, refluksa i nadutosti',
                    'paragraphs' => ['Kod gastritisa, refluksa i nadutosti preporučuju se Aloe Vera Gel i Active Pro B. Gel daje napitak s unutarnjim gelom aloe, a Pro B žive probiotičke mikroorganizme. Suradnik povezuje konkretnu tegobu s preporukom i jednostavno objašnjava obje uloge.', 'Nalazi o kožnoj primjeni i istraživanja drugih oralnih aloe pripravaka imaju vlastiti oblik, sastav i ishod. NCCIH navodi nedovoljno pouzdanih podataka za određena crijevna stanja, uključujući IBS i ulcerozni kolitis. Tijekom trudnoće, dojenja ili uz terapiju osobni odabir aloe napitka usklađuje se s liječnikom.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka koju kupac razumije',
                    'paragraphs' => ['Kupac pita što preporučiti kod gastritisa i osjetljivog želuca. Objasni Gel i Active Pro B, zasebnu ulogu aloe napitka i probiotičkog proizvoda te pripadajuću uporabu na njihovim karticama.', 'Primjer razgovora: Kod gastritisa i osjetljivog želuca preporučujem Aloe Vera Gel i Active Pro B. Gel donosi unutarnji gel aloe, a Pro B probiotičke mikroorganizme. Za dodatna pitanja o kombinaciji dostupni su sponzor, timska grupa i službeni liječnik savjetnik u Foreveru.'],
                    'points' => [],
                ],
            ],
            'takeaways' => ['Unutarnji gel aloe donosi vodu i polisaharide, uključujući acemannan.', 'Gel i Pro B preporučuju se uz gastritis, refluks i nadutost.', 'Oralni aloe napitak i kozmetički proizvod imaju različitu primjenu.'],
            'quiz' => [
                ['id' => 'a1', 'prompt' => 'Koji je prvi važan podatak pri usporedbi proizvoda s aloe?', 'options' => ['a' => 'Samo koliko je biljka stara.', 'b' => 'Samo okus napitka.', 'c' => 'Dio lista, oblik sastojka i deklarirana namjena.'], 'correct_option' => 'c', 'explanation' => 'Proizvodi iz unutarnjeg gela, lateksa ili cijelog lista razlikuju se. Namjena se ne zaključuje iz naziva biljke.'],
                ['id' => 'a2', 'prompt' => 'Studija govori o topikalnoj primjeni aloe. Što možemo zaključiti o aloe napitku?', 'options' => ['a' => 'Taj nalaz sam po sebi ne potvrđuje učinak napitka.', 'b' => 'Napitak sigurno ima isti učinak.', 'c' => 'Možemo povećati količinu napitka da postignemo isti rezultat.'], 'correct_option' => 'a', 'explanation' => 'Oblik, sastav, način primjene i ishod moraju odgovarati predmetu dokaza.'],
                ['id' => 'a3', 'prompt' => 'Kako kupcu profesionalno objašnjavaš preporuku aloe napitka?', 'options' => [
    'a' => 'Predstaviš ga kao liječenje svih upalnih bolesti probave.',
    'b' => 'Objasniš aktualni sastav i primjenu te jasno razlikuješ iskustva korisnika od istraženih učinaka.',
    'c' => 'Pretpostaviš da nema interakcija s lijekovima.',
], 'correct_option' => 'b', 'explanation' => 'Profesionalno objašnjenje povezuje stvaran napitak, iskustvenu primjenu i odgovarajuće izvore. Svaka vrsta informacije ima svoju svrhu.'],
            ],
            'source_ids' => ['nih-aloe', 'aloe-acemannan-structure', 'aloe-acemannan-study', 'eu-claims', 'nih-supplements-wisely'],
            'product_keys' => ['forever-aloe-vera-gel', 'aloe-berry-nectar', 'forever-aloe-peaches'],
        ],
        [
            'id' => 'citanje-deklaracije',
            'title' => 'Sastojci, porcija i jasno objašnjenje proizvoda',
            'summary' => 'Pretvori podatke o proteinima, vlaknima, vitaminima i masnim kiselinama u konkretno objašnjenje kupcu.',
            'minutes' => 8,
            'sections' => [
                [
                    'title' => 'Što kupac treba znati o proizvodu',
                    'paragraphs' => ['Prikaži točan proizvod, glavne sastojke, preporučenu porciju i način uporabe. Razlikuj podatak na 100 g ili 100 ml od podatka za tabletu, kapsulu ili dnevnu porciju. To omogućuje smisleno objašnjenje koliko proizvod nadopunjuje prehranu.', 'Primjer: kupcu koji pita za proteine objasni grame proteina u pripremljenom napitku. Kupcu koji pita za vlakna objasni grame vlakana po sticku. Aktualno pakiranje i tržište određuju koji sastav i uputa pripadaju njegovom proizvodu.'],
                    'points' => ['Točan proizvod i aktualno pakiranje.', 'Glavni sastojci i njihova svrha.', 'Količina u preporučenoj porciji.', 'Način uporabe i prikladnost osobi.', 'Provjerena funkcija sastojka.'],
                ],
                [
                    'title' => 'Sirovina i njezini bioaktivni sastojci',
                    'paragraphs' => ['Riblje ulje je sirovina, a EPA i DHA određene masne kiseline u tom ulju. Količina cijelog ulja zato je drugačiji podatak od količine EPA i DHA. Kod aloe se jednako razlikuju unutarnji gel i pojedini polisaharidi.', 'Vitamin C sudjeluje u stvaranju kolagena, zaštiti stanica od oksidativnog stresa i normalnoj imunološkoj funkciji. Kupcu objasni funkciju zbog koje ga preporučuješ. Kod proteinskog napitka zbroji protein iz praška i dodane tekućine kada tablica to već ne uključuje.'],
                    'points' => [],
                ],
                [
                    'title' => 'Iskustva, funkcija sastojka i potvrđene tvrdnje',
                    'paragraphs' => ['Iskustva Forever korisnika opisuju primjenu proizvoda ili kombinacije. Fiziološko objašnjenje pokazuje čemu služi pojedini sastojak. Znanstveno istraživanje ispituje određeni pripravak i ishod. U preporuci je korisno jasno navesti na kojoj vrsti podatka se objašnjenje temelji.', 'Za zdravstvenu tvrdnju provjeravaju se tekst, status i uvjeti iz registra te odgovarajući sastav i unos proizvoda. Primjer potvrđene funkcije glasi da vitamin C doprinosi normalnoj funkciji imunološkog sustava. Ta preciznost kupcu pokazuje razlog preporuke i što sastojak stvarno radi.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: izračunaj i objasni preporuku',
                    'paragraphs' => ['U izmišljenoj vježbi kapsula daje 100 mg sastojka, a uputa dvije kapsule dnevno. Unos je 200 mg. Drugi izmišljeni proizvod daje još 50 mg istog sastojka, pa je zbroj 250 mg. Ti brojevi služe računanju, a stvarna preporuka slijedi uputu točnog proizvoda.', 'Odaberi jednu karticu proizvoda i napiši kupcu tri rečenice: kome ga preporučuješ, koji sastojak objašnjava njegovu primjenu i kako se koristi. U brojčanom dijelu navedi aktualnu porciju. Tako povezuješ razumijevanje sastava s praktičnom preporukom.'],
                    'points' => [],
                ],
            ],
            'takeaways' => ['Količine se uspoređuju po istoj porciji i mjernoj osnovi.', 'Objasni glavne sastojke kroz njihovu konkretnu funkciju.', 'Iskustvo korisnika, istraživanje i potvrđena tvrdnja imaju različitu ulogu u objašnjenju.'],
            'quiz' => [
                ['id' => 'd1', 'prompt' => 'Izmišljeni proizvod ima 100 mg sastojka po kapsuli i preporučuje dvije kapsule dnevno. Koliki je unos iz tog proizvoda?', 'options' => ['a' => '100 mg dnevno.', 'b' => '200 mg dnevno.', 'c' => 'To nije moguće izračunati.'], 'correct_option' => 'b', 'explanation' => 'Količina po kapsuli množi se brojem kapsula iz preporučenog dnevnog unosa. Primjer nije doza stvarnog proizvoda.'],
                ['id' => 'd2', 'prompt' => 'Koji podaci najbolje opisuju unos EPA i DHA?', 'options' => ['a' => 'Samo ukupni grami ribljeg ulja.', 'b' => 'Samo broj kapsula u kutiji.', 'c' => 'Miligrame EPA i DHA po preporučenoj dnevnoj porciji.'], 'correct_option' => 'c', 'explanation' => 'Količina sirovine ne daje sama po sebi odvojene količine EPA i DHA.'],
                ['id' => 'd3', 'prompt' => 'Prije nego kupcu navedeš potvrđenu zdravstvenu tvrdnju za sastojak proizvoda, što povezuješ?', 'options' => [
    'a' => 'Tekst, status i uvjete tvrdnje s odgovarajućim sastavom i unosom proizvoda.',
    'b' => 'Samo dodaješ riječ podržava.',
    'c' => 'Samo pronađeš bilo koju studiju o istoj biljci.',
], 'correct_option' => 'a', 'explanation' => 'Potvrđena funkcija jasno se povezuje sa sastojkom, količinom i uvjetima. To omogućuje precizno objašnjenje kupcu.'],
            ],
            'source_ids' => ['eu-claims', 'nih-vitamin-c', 'nih-omega3', 'nih-supplements'],
            'product_keys' => ['aloe-berry-nectar', 'forever-aloe-peaches', 'forever-fields-of-greens', 'forever-absorbent-c', 'forever-arctic-sea', 'forever-immublend', 'forever-ultra'],
        ],
        [
            'id' => 'razgovor-i-kombinacije',
            'title' => 'Kako preporučiti proizvod i objasniti kombinaciju kupcu',
            'summary' => 'Prepoznaj potrebu kupca, poveži preporuku s ulogama sastojaka i uvježbaj razumljiv razgovor.',
            'minutes' => 7,
            'sections' => [
                [
                    'title' => 'Počni od potrebe kupca',
                    'paragraphs' => ['Kupac obično dolazi s konkretnim pitanjem: osjetljiv želudac, nadutost, malo vlakana u prehrani, suha koža ili umor. Pitaj kada se problem javlja, što želi postići i koje proizvode već koristi. Preporuku zatim poveži s odgovarajućom primjenom u edukaciji.', 'Kod gastritisa, refluksa i nadutosti preporučuju se Aloe Vera Gel i Active Pro B. Kod nedostatnog unosa vlakana razmatra se Fiber Fusion. Za suhu kožu bira se odgovarajuća vanjska njega. Kupac treba odmah razumjeti zašto je baš njegov problem povezan s odabranim proizvodom.'],
                    'points' => [],
                ],
                [
                    'title' => 'Objasni zasebnu ulogu svakog proizvoda',
                    'paragraphs' => ['Gel donosi aloe napitak s unutarnjim gelom i polisaharidima. Active Pro B daje žive probiotičke mikroorganizme. Fiber Fusion nadopunjuje vlakna. To su tri različite uloge koje se mogu povezati prema potrebi osobe.', 'Kad kombinacija sadrži isti vitamin ili mineral u više proizvoda, pregledaj ukupni dnevni unos i upute. Preporuka pokazuje gdje kombinacija ima mjesto i što svaki proizvod donosi. Dokazana sinergija zahtijeva istraživanje same kombinacije. Jasno razdvojene uloge omogućuju kvalitetnu preporuku i bez tvrdnje da je rezultat zajamčen.'],
                    'points' => ['Imenuj potrebu kupca.', 'Navedi konkretnu primjenu.', 'Objasni ulogu svakog proizvoda.', 'Pokaži preporučenu uporabu na kartici.', 'Provjeri postojeće proizvode i zajedničke sastojke.'],
                ],
                [
                    'title' => 'Dodatna pitanja i individualni odabir',
                    'paragraphs' => ['Za dodatna pitanja o kombinaciji obrati se svojem sponzoru, timskoj grupi ili službenom liječniku savjetniku u Foreveru. Kupcu pripremi jasan popis odabranih proizvoda i način uporabe. Tako se pitanje može razjasniti na osnovi konkretne preporuke.', 'Osoba koja uzima lijekove liječniku ili ljekarniku pokazuje popis dodataka i terapije. Trudnoća, dojenje, primjena kod djece, planirana operacija ili ozbiljna bolest zahtijevaju individualnu procjenu. Propisana terapija ostaje odluka zdravstvenog stručnjaka, a prehrambena preporuka s njom se usklađuje.'],
                    'points' => [],
                ],
                [
                    'title' => 'Vježba: preporuka i razgovor s kupcem',
                    'paragraphs' => ['Kupac pita za preporuku kod gastritisa i osjetljivog želuca. Odgovor sastavi ovim redoslijedom: primjena, nazivi proizvoda, uloge Gela i Pro B, uporaba na karticama i dodatna pitanja. U sljedećem primjeru kupac već koristi više vitaminskih proizvoda, pa uključi pregled zajedničkih sastojaka.', 'Primjer razgovora: Kod gastritisa i osjetljivog želuca preporučujem Aloe Vera Gel i Active Pro B. Gel je aloe napitak, a Pro B donosi probiotičke mikroorganizme. Pokazat ću vam način uporabe, a dodatna pitanja razjašnjavaju sponzor, timska grupa ili službeni liječnik savjetnik u Foreveru.'],
                    'points' => [],
                ],
            ],
            'takeaways' => ['Preporuka jasno povezuje potrebu kupca, proizvode i njihove zasebne uloge.', 'Zajednički vitamini i minerali zbrajaju se iz preporučenih porcija.', 'Dodatna pitanja razjašnjavaju sponzor, timska grupa i službeni liječnik savjetnik u Foreveru, a primjenu uz terapiju liječnik ili ljekarnik.'],
            'quiz' => [
                ['id' => 'r1', 'prompt' => 'Kad koristiš kombinaciju iz zabilježenih korisničkih iskustava, što povezuješ prije preporuke?', 'options' => [
    'a' => 'Zabilježenu primjenu s aktualnim proizvodima, sastavom i uputama.',
    'b' => 'Samo činjenicu da je time dokazana sinergija.',
    'c' => 'Sve stare doze bez provjere današnjih formula.',
], 'correct_option' => 'a', 'explanation' => 'Zapis primjene povezuje se s aktualnim sastavom i uporabom proizvoda. Iskustvo korisnika jasno se označuje kao iskustvo.'],
                ['id' => 'r2', 'prompt' => 'Kupac uzima lijekove i pita za kombinaciju uz svoju dijagnozu. Kako vodiš razgovor?', 'options' => [
    'a' => 'Samostalno odrediš primjenu uz lijek iz popisa dijagnoza.',
    'b' => 'Predložiš da najprije prekine propisanu terapiju.',
    'c' => 'Objasniš primjenu i provjerene podatke, a osobni odabir uz terapiju uskladiš s liječnikom ili ljekarnikom.',
], 'correct_option' => 'c', 'explanation' => 'Preporuka uz lijekove usklađuje se s osobnom terapijom i procjenom liječnika ili ljekarnika.'],
                ['id' => 'r3', 'prompt' => 'Dva preporučena proizvoda sadrže isti vitamin. Koji pregled daje smislen izbor kombinacije?', 'options' => [
    'a' => 'Samo cijena obaju pakiranja.',
    'b' => 'Ukupan unos, aktualne upute, prikladnost osobi i svrha svakog proizvoda.',
    'c' => 'Samo pripadaju li oba kompaniji Forever Living Products.',
], 'correct_option' => 'b', 'explanation' => 'Zajedničke količine zbrajaju se, a svaki proizvod treba imati jasnu ulogu u odabranom preporučenom skupu.'],
            ],
            'source_ids' => ['nih-supplements', 'nih-supplements-wisely', 'eu-claims'],
            'product_keys' => ['forever-aloe-vera-gel', 'forever-active-pro-b', 'forever-fiber', 'forever-immublend', 'forever-absorbent-c', 'forever-arctic-sea'],
        ],
    ],
    'products' => [
        ['product_key' => 'forever-aloe-vera-gel', 'name' => 'Forever Aloe Vera Gel', 'topic' => 'Aloe napitci i čitanje sastava', 'lesson_ids' => ['aloe-sastav-i-dokazi', 'razgovor-i-kombinacije'], 'study_questions' => ['Koji dio lista i udio aloe navodi aktualna deklaracija?', 'Koji je preporučeni dnevni unos tog pakiranja?', 'Koji navodi imaju dokaz i odgovarajuće uvjete uporabe?']],
        ['product_key' => 'aloe-berry-nectar', 'name' => 'Forever Aloe Berry Nectar', 'topic' => 'Usporedba aloe napitaka', 'lesson_ids' => ['aloe-sastav-i-dokazi', 'citanje-deklaracije'], 'study_questions' => ['Koji voćni sastojci i količine su potvrđeni?', 'Kako se aktualni sastav razlikuje od drugog aloe napitka?', 'Zašto naziv brusnica ne daje automatski zaključak o učinku na mokraćni sustav?']],
        ['product_key' => 'forever-aloe-peaches', 'name' => 'Forever Aloe Peaches', 'topic' => 'Sastav, okus i dnevna porcija', 'lesson_ids' => ['aloe-sastav-i-dokazi', 'citanje-deklaracije'], 'study_questions' => ['Koji su dodatni sastojci navedeni u aktualnoj formuli?', 'Koliki je njihov unos prema deklaraciji?', 'Koje razlike sastava možemo objasniti bez tvrdnje o liječenju?']],
        ['product_key' => 'forever-active-pro-b', 'name' => 'Forever Active Pro B', 'topic' => 'Mikroorganizmi, sojevi i količine', 'lesson_ids' => ['mikrobiota-i-probiotici', 'razgovor-i-kombinacije'], 'study_questions' => ['Jesu li dostupne potpune oznake sojeva?', 'Koliko CFU sadrži preporučena porcija i na koji rok se podatak odnosi?', 'Odgovara li istraženi proizvod aktualnoj formuli?']],
        ['product_key' => 'forever-fiber', 'name' => 'Forever Fiber', 'topic' => 'Unos i izvori vlakana', 'lesson_ids' => ['probava-osnove', 'vlakna-i-navike', 'mikrobiota-i-probiotici', 'razgovor-i-kombinacije'], 'study_questions' => ['Koju vrstu i koliko grama vlakana navodi deklaracija?', 'Kako se proizvod priprema prema aktualnoj uputi?', 'Koji se uvjeti odnose na eventualnu zdravstvenu tvrdnju?']],
        ['product_key' => 'forever-fields-of-greens', 'name' => 'Forever Fields of Greens', 'topic' => 'Biljni sastojci i količina po porciji', 'lesson_ids' => ['probava-osnove', 'vlakna-i-navike', 'citanje-deklaracije'], 'study_questions' => ['Koji biljni sastojci su potvrđeni za aktualnu formulu?', 'Jesu li količine vlakana i mikronutrijenata stvarno navedene?', 'Zašto popis biljaka ne zamjenjuje nutritivnu tablicu?']],
        ['product_key' => 'forever-absorbent-c', 'name' => 'Forever Absorbent C', 'topic' => 'Vitamin C i uvjeti tvrdnje', 'lesson_ids' => ['citanje-deklaracije', 'razgovor-i-kombinacije'], 'study_questions' => ['Koliki je unos vitamina C iz preporučene dnevne porcije?', 'Koji drugi proizvodi mogu sadržavati isti vitamin?', 'Koja dopuštena tvrdnja i koji uvjeti odgovaraju provjerenom sastavu?']],
        ['product_key' => 'forever-arctic-sea', 'name' => 'Forever Arctic Sea', 'topic' => 'EPA, DHA i razlikovanje sirovine od sastojka', 'lesson_ids' => ['citanje-deklaracije', 'razgovor-i-kombinacije'], 'study_questions' => ['Koliko EPA i DHA zasebno daje dnevna porcija?', 'Koje izvore ulja i alergene navodi deklaracija?', 'Koji su uvjeti tvrdnje i upozorenja prije razgovora o uporabi?']],
        ['product_key' => 'forever-immublend', 'name' => 'Forever Immublend', 'topic' => 'Višekomponentna formula', 'lesson_ids' => ['mikrobiota-i-probiotici', 'citanje-deklaracije', 'razgovor-i-kombinacije'], 'study_questions' => ['Koji su sastojci potvrđeni u aktualnoj formuli?', 'Koje količine vitamina i minerala treba zbrojiti s drugim proizvodima?', 'Je li određena tvrdnja vezana uz sastojak ili dokaz za cijelu formulu?']],
        ['product_key' => 'forever-ultra', 'name' => 'Forever Lite Ultra', 'topic' => 'Proteini, priprema i provjera identiteta formule', 'lesson_ids' => ['probava-osnove', 'citanje-deklaracije'], 'study_questions' => ['Odgovara li aktualni proizvod ovoj obitelji naziva?', 'Koliko proteina daje pripremljena porcija prema aktualnoj uputi?', 'Koje alergene i dodane hranjive tvari navodi deklaracija?']],
    ],
    'needs' => [
        ['id' => 'razumjeti-probavu', 'title' => 'Želim razumjeti probavu', 'description' => 'Upoznaj razgradnju hrane, apsorpciju i ulogu prehrambenih navika.', 'lesson_ids' => ['probava-osnove', 'vlakna-i-navike'], 'product_keys' => ['forever-fiber', 'forever-fields-of-greens', 'forever-ultra']],
        ['id' => 'uciti-o-mikrobioti', 'title' => 'Želim razlikovati probiotike i prebiotike', 'description' => 'Nauči čitati identitet mikroorganizma, količinu i uvjete istraživanja.', 'lesson_ids' => ['mikrobiota-i-probiotici', 'citanje-deklaracije'], 'product_keys' => ['forever-active-pro-b', 'forever-fiber', 'forever-immublend']],
        ['id' => 'uciti-o-aloe', 'title' => 'Želim upoznati aloe napitke', 'description' => 'Usporedi dijelove lista i deklaracije bez pretpostavke istog učinka.', 'lesson_ids' => ['aloe-sastav-i-dokazi', 'citanje-deklaracije'], 'product_keys' => ['forever-aloe-vera-gel', 'aloe-berry-nectar', 'forever-aloe-peaches']],
        ['id' => 'citati-sastojke', 'title' => 'Želim objasniti sastojke i količine', 'description' => 'Odvoji sastav, znanstveni dokaz i dopuštenu komunikacijsku tvrdnju.', 'lesson_ids' => ['citanje-deklaracije'], 'product_keys' => ['forever-absorbent-c', 'forever-arctic-sea', 'forever-immublend', 'forever-ultra']],
        ['id' => 'provjeriti-kombinacije', 'title' => 'Želim razumjeti provjeru kombinacija', 'description' => 'Nauči što treba provjeriti prije razmatranja zajedničke uporabe.', 'lesson_ids' => ['razgovor-i-kombinacije', 'citanje-deklaracije'], 'product_keys' => ['forever-aloe-vera-gel', 'forever-active-pro-b', 'forever-fiber', 'forever-absorbent-c', 'forever-immublend']],
    ],
    'routines' => [
        ['id' => 'aloe-probiotik-provjera', 'title' => 'Aloe napitak i probiotički proizvod', 'status' => 'editorial_candidate', 'summary' => 'Primjer za provjeru različitih sastava. Kombinacija nije odobrena za preporučivanje kod simptoma ili dijagnoze.', 'product_keys' => ['forever-aloe-vera-gel', 'forever-active-pro-b'], 'checks' => ['Potvrditi aktualne formule i dnevne porcije.', 'Provjeriti oznake probiotičkih sojeva i CFU.', 'Tražiti dokaz za samu kombinaciju.', 'Provjeriti ograničenja i uvjete eventualnih tvrdnji.'], 'source_ids' => ['nih-aloe', 'nih-probiotics', 'eu-claims']],
        ['id' => 'vlakna-probiotik-provjera', 'title' => 'Vlakna i probiotički proizvod', 'status' => 'editorial_candidate', 'summary' => 'Materijal za učenje o vlaknima i mikroorganizmima. Njihovo zajedničko navođenje nije dokaz sinergije niti preporuka uporabe.', 'product_keys' => ['forever-fiber', 'forever-active-pro-b'], 'checks' => ['Provjeriti vrstu i količinu vlakana.', 'Provjeriti točan probiotički sastav.', 'Razlikovati dokaz za sastojak od dokaza za kombinaciju.', 'Pregledati deklarirane upute i sigurnosna ograničenja.'], 'source_ids' => ['nih-fiber', 'nih-probiotics', 'nih-supplements']],
        ['id' => 'mikronutrijenti-provjera', 'title' => 'Usporedba formula s mikronutrijentima', 'status' => 'editorial_candidate', 'summary' => 'Primjer za učenje zbrajanja zajedničkih sastojaka. Ne predstavlja preporučeni paket za imunitet ili bolest.', 'product_keys' => ['forever-absorbent-c', 'forever-immublend'], 'checks' => ['Potvrditi sve količine po dnevnoj porciji.', 'Zbrojiti zajedničke vitamine i minerale.', 'Procijeniti postoji li opravdanje za svaki proizvod.', 'Provjeriti aktualne uvjete tvrdnje i ograničenja uporabe.'], 'source_ids' => ['nih-supplements', 'nih-vitamin-c', 'eu-claims']],
    ],
    'sources' => [
        'nih-digestion' => ['title' => 'NIH NIDDK: kako radi probavni sustav', 'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/digestive-system-how-it-works', 'kind' => 'Službeni edukativni izvor'],
        'nih-fiber' => ['title' => 'NIH NIDDK: vlakna, prehrana i tekućina', 'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/constipation/eating-diet-nutrition', 'kind' => 'Službeni edukativni izvor'],
        'nih-probiotics' => ['title' => 'NIH NCCIH: probiotici, dokazi i sigurnost', 'url' => 'https://www.nccih.nih.gov/health/probiotics-usefulness-and-safety', 'kind' => 'Službeni pregled dokaza'],
        'nih-aloe' => ['title' => 'NIH NCCIH: aloe vera, dokazi i sigurnost', 'url' => 'https://www.nccih.nih.gov/health/aloe-vera', 'kind' => 'Službeni pregled dokaza'],
        'nih-supplements' => ['title' => 'NIH ODS: što treba znati o dodacima prehrani', 'url' => 'https://ods.od.nih.gov/factsheets/WYNTK-Consumer/', 'kind' => 'Službeni izvor o sigurnosti'],
        'nih-supplements-wisely' => ['title' => 'NIH NCCIH: odgovorna uporaba dodataka prehrani', 'url' => 'https://www.nccih.nih.gov/health/using-dietary-supplements-wisely', 'kind' => 'Službeni edukativni izvor'],
        'nih-vitamin-c' => ['title' => 'NIH ODS: vitamin C', 'url' => 'https://ods.od.nih.gov/factsheets/VitaminC-Consumer/', 'kind' => 'Službeni izvor o hranjivoj tvari'],
        'nih-omega3' => ['title' => 'NIH ODS: masne kiseline omega 3', 'url' => 'https://ods.od.nih.gov/factsheets/Omega3FattyAcids-Consumer/', 'kind' => 'Službeni izvor o hranjivoj tvari'],
        'eu-claims' => ['title' => 'Europska komisija: registar zdravstvenih tvrdnji i uvjeti uporabe', 'url' => 'https://food.ec.europa.eu/food-safety/labelling-and-nutrition/nutrition-and-health-claims/eu-register-health-claims_en', 'kind' => 'Službeni regulatorni izvor'],
    ],
];
