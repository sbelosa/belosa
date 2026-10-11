<?php

/* Ingredient education is separate from the published product declaration. */
$component = static function (string $name, string $explanation, string $evidence, array $sourceIds): array {
    return ['name' => $name, 'explanation' => $explanation, 'evidence' => $evidence, 'source_ids' => $sourceIds];
};
$useCase = static function (string $id, string $title, string $context, string $role, string $evidence, string $boundary, array $questions, string $whenRefer, array $sourceIds): array {
    return ['id' => $id, 'title' => $title, 'context' => $context, 'role' => $role, 'evidence' => $evidence, 'boundary' => $boundary, 'questions' => $questions, 'when_refer' => $whenRefer, 'source_ids' => $sourceIds];
};

return [
    'products' => [
        'forever-aloe-vera-gel' => [
            'learning_intro' => 'Unutarnji gel lista aloe sadrži vodu i složene ugljikohidrate, među njima acemannan. Ti polisaharidi zanimljivi su zbog svoje građe i interakcija s imunološkim stanicama. Vitamin C obrađujemo kroz njegove uloge u kolagenu, zaštiti stanica i normalnoj imunološkoj funkciji.',
            'active_components' => [
                $component('Acemannan i polisaharidi aloe', 'Acemannan je polisaharid unutarnjeg gela aloe, građen pretežito od povezanih manoznih jedinica. Njegova građa i interakcije s imunološkim stanicama predmet su istraživanja.', 'Preklinički mehanizam, bez potvrde kliničke koristi aktualnog napitka.', ['aloe-acemannan-structure', 'aloe-acemannan-study', 'nih-aloe']),
                $component('Vitamin C', 'Vitamin C sudjeluje u stvaranju kolagena, zaštiti stanica od oksidativnog stresa i normalnom radu imunosnog sustava. Kolagen daje strukturu vezivnom tkivu.', 'Utvrđena nutritivna funkcija; prisutnost i količina u napitku te uvjeti EU provjeravaju se prema aktualnoj deklaraciji.', ['nih-vitamin-c', 'eu-claims-regulation']),
            ],
            'use_cases' => [
                $useCase('aloe-gastritis', 'Gastritis i osjetljiv želudac', 'Želučana sluznica štiti stijenku želuca. Gastritis je njezina upala, s različitim uzrocima, uključujući H. pylori.', 'Razgovor povezuje sastav napitka, osobnu podnošljivost i prehrambene navike uz postojeći plan skrbi.', 'Nema potvrđenog terapijskog dokaza za ovaj proizvod kod gastritisa.', 'Za potvrdu učinka na bolest potrebno je kliničko istraživanje konkretnog proizvoda. Kod gastritisa medicinska procjena razjašnjava uzrok upale.', ['Je li uzrok već utvrđen?', 'Koju terapiju osoba već prima?'], 'Trajne ili jake tegobe zahtijevaju liječnika; krvarenje zahtijeva hitnu procjenu.', ['nih-gastritis', 'nih-aloe']),
                $useCase('aloe-refluks', 'Refluks i žgaravica', 'Kod refluksa sadržaj želuca vraća se u jednjak. Vrijeme obroka, ležanje nakon jela i osobni prehrambeni okidači mogu utjecati na simptome.', 'Povezujemo vrijeme pojave žgaravice s navikama i upoznajemo istraživanja oralnih aloe pripravaka.', 'Mali četverotjedni pokus s 79 osoba ispitao je poseban sirup s 5 mg polisaharida/ml.', 'Ograničen istraživački signal nije dokaz učinka Forever napitka niti istraživanje gastritisa.', ['Kada se simptomi pojavljuju?', 'Jesu li povezani s kasnim obrokom ili ležanjem?'], 'Česti simptomi ili otežano gutanje traže liječnika; bol u prsima hitnu procjenu.', ['aloe-reflux-trial', 'nih-reflux', 'nih-reflux-symptoms']),
                $useCase('aloe-imunitet', 'Aloe i normalna imunološka funkcija', 'Imunosni sustav uključuje zaštitne barijere, stanice i signalne tvari. Odgovarajući unos hranjivih tvari podupire njegov normalan rad.', 'Objasni ulogu vitamina C i poveži njegov ukupni dnevni unos s prehranom i sastavom napitka.', 'Uloga vitamina C je utvrđena; imunomodulacija acemannana u laboratoriju nije dokaz koristi pijenja.', 'Ne obećavamo sprječavanje infekcija ni prenosimo tvrdnju vitamina na aloe polisaharide.', ['Kako izgleda svakodnevna prehrana?', 'Sadrže li drugi proizvodi vitamin C?'], 'Učestale ili teške infekcije i imunosupresija traže liječničku procjenu.', ['nih-immune', 'nih-vitamin-c', 'aloe-acemannan-study', 'eu-claims-regulation']),
            ],
            'conversation_example' => 'Acemannan je jedan od polisaharida unutarnjeg gela aloe. Istraživače zanima kako njegova građa sudjeluje u interakciji s imunološkim stanicama. Kod vašeg pitanja o žgaravici korisno je pogledati i kada se javlja, primjerice nakon kasnog obroka.',
            'takeaways' => ['Acemannan je polisaharid unutarnjeg gela aloe.', 'Vitamin C sudjeluje u kolagenu, zaštiti stanica i normalnoj imunološkoj funkciji.'],
            'source_ids' => ['nih-aloe', 'aloe-acemannan-structure', 'aloe-acemannan-study', 'nih-vitamin-c', 'nih-gastritis', 'nih-reflux', 'aloe-reflux-trial', 'nih-immune', 'eu-claims-regulation'],
        ],
        'aloe-berry-nectar' => [
            'learning_intro' => 'Aloe i brusnica donose dvije zanimljive skupine biljnih sastavnica: polisaharide i polifenole. Proantocijanidini, skraćeno PAC, polifenoli su brusnice koji se istražuju u odnosu na prianjanje bakterija. Voćni dio napitka učimo promatrati i kroz okus, šećere i veličinu porcije.',
            'active_components' => [
                $component('Polisaharidi unutarnjeg gela aloe', 'Polisaharidi su lanci povezanih šećernih jedinica. Acemannan je jedna od karakterističnih sastavnica unutarnjeg gela aloe i sadrži pretežito manozne jedinice.', 'Dokazi se odnose na određene pripravke i ishode.', ['aloe-acemannan-structure', 'nih-aloe']),
                $component('Proantocijanidini brusnice, PAC', 'PAC su biljni polifenoli. Istražuju se zbog njihove uloge u otežavanju prianjanja određenih bakterija uz stanice mokraćnog sustava.', 'Mehanizam je ispitan analizom urina izvan tijela nakon određenog PAC pripravka; klinički nalazi pripadaju drugim određenim pripravcima. Prisutnost i količina PAC u napitku provjeravaju se zasebno.', ['cranberry-pac-study', 'nih-cranberry']),
            ],
            'use_cases' => [
                $useCase('berry-mokracni-upit', 'Pitanje o brusnici i mokraćnom sustavu', 'Brusnica je predmet istraživanja kod ponavljajućih urinarnih infekcija u nekih žena. Jedan od proučavanih procesa je prianjanje bakterija.', 'Objasni što su PAC i upoznaj razliku voćnog dodatka i pripravka s određenom količinom PAC.', 'Rezultati ovise o pripravku; za ovaj napitak nije potvrđen odgovarajući unos PAC.', 'Napitak ne predstavljamo kao liječenje postojeće infekcije.', ['Postoje li sada simptomi?', 'Je li istraženi pripravak usporediv?'], 'Peckanje pri mokrenju traži zdravstvenu procjenu, osobito uz vrućicu ili bol.', ['nih-cranberry', 'cranberry-pac-study']),
                $useCase('berry-prehrana', 'Voćni okus i nutritivni doprinos', 'Osoba želi usporediti aloe napitke i razumjeti voćni dodatak.', 'Uspoređujemo okus, porciju, šećere i stvarno deklarirane hranjive tvari.', 'Sastav može potvrditi nutritivni doprinos; naziv voća sam ga ne dokazuje.', 'Napitak nije automatski zamjena za voće ili izvor vlakana.', ['Što je cilj odabira napitka?', 'Koju količinu šećera porcija navodi?'], 'Kod posebne medicinske prehrane izbor uskladiti sa stručnjakom.', ['nih-supplements', 'nih-digestion']),
            ],
            'conversation_example' => 'Brusnica sadrži polifenole koje zovemo PAC. Prianjanje bakterija jedan je od procesa koje istraživači proučavaju. Želite li upoznati te sastojke ili usporediti voćne okuse aloe napitaka?',
            'takeaways' => ['PAC su polifenoli brusnice.', 'Voćni dio napitka promatramo kroz sastav i veličinu porcije.'],
            'source_ids' => ['nih-cranberry', 'cranberry-pac-study', 'aloe-acemannan-structure', 'nih-aloe', 'nih-supplements'],
        ],
        'forever-aloe-peaches' => [
            'learning_intro' => 'Kod aloe i voća upoznajemo polisaharide, biljne pigmente i hranjive tvari. Karotenoidi su pigmenti prisutni u mnogim vrstama voća, a dio njih tijelo pretvara u vitamin A. Za svakodnevni izbor napitka korisno je povezati željeni okus s nutritivnim sastavom porcije.',
            'active_components' => [
                $component('Polisaharidi aloe', 'Polisaharidi su složeni ugljikohidrati sastavljeni od povezanih šećernih jedinica. U unutarnjem gelu aloe među njima se nalazi acemannan.', 'Za procjenu je potreban odgovarajući oralni pripravak i klinički ishod.', ['aloe-acemannan-structure', 'nih-aloe']),
                $component('Karotenoidi u voću', 'Karotenoidi su biljni pigmenti. Beta karoten i neki drugi karotenoidi mogu se pretvoriti u vitamin A, koji sudjeluje u normalnom vidu i imunološkoj funkciji.', 'Utvrđena uloga određenih karotenoida; količina proizvoda nije potvrđena ovim profilom.', ['nih-vitamin-a']),
            ],
            'use_cases' => [
                $useCase('peaches-odabir', 'Odabir napitka prema prehrambenom cilju', 'Voćni okus i sastav porcije mogu pomoći odabrati napitak koji odgovara svakodnevnim navikama.', 'Usporedi okus, veličinu porcije, šećere i hranjive tvari u nutritivnoj tablici.', 'To je procjena deklariranog sastava.', 'Voćni naziv ne potvrđuje vitamine ili vlakna u značajnoj količini.', ['Što želite postići izborom?', 'Što se razlikuje u nutritivnoj tablici?'], 'Posebnu prehranu zbog bolesti uskladiti sa stručnjakom.', ['nih-supplements', 'nih-vitamin-a']),
                $useCase('peaches-refluks', 'Voćni napitak uz refluksne simptome', 'Određena hrana i napitci kod nekih ljudi potiču žgaravicu.', 'Razgovor se usmjerava na osobne okidače i vrijeme obroka.', 'NIDDK opisuje individualne prehrambene okidače i izbjegavanje kasnih obroka.', 'Ne obećavamo da je voćna aloe blaža ili korisna za refluks.', ['Primjećujete li povezanost s napitkom?', 'Pijete li ga neposredno prije ležanja?'], 'Trajan refluks i otežano gutanje procjenjuje liječnik.', ['nih-reflux', 'nih-reflux-symptoms', 'nih-aloe']),
            ],
            'conversation_example' => 'Neki pigmenti u voću mogu biti izvor vitamina A, važnog za vid. Kod napitka usporedimo sastav porcije i okus koji vam odgovara. Koji podatak u nutritivnoj tablici želite bolje razumjeti?',
            'takeaways' => ['Karotenoidi su biljni pigmenti, a dio njih je izvor vitamina A.', 'Okus i nutritivni sastav porcije pomažu svakodnevnom izboru.'],
            'source_ids' => ['nih-aloe', 'aloe-acemannan-structure', 'nih-vitamin-a', 'nih-reflux', 'nih-supplements'],
        ],
        'forever-active-pro-b' => [
            'learning_intro' => 'Probiotici su živi mikroorganizmi koji se proučavaju kroz njihovu ulogu u crijevnoj zajednici. Laktobacili i bifidobakterije sudjeluju u razgradnji ugljikohidrata i stvaranju metaboličkih proizvoda. Soj označava konkretnu bakterijsku varijantu, a CFU broj održivih mikroorganizama koji mogu stvoriti koloniju.',
            'active_components' => [
                $component('Laktobacili', 'Laktobacili fermentiraju ugljikohidrate i stvaraju mliječnu kiselinu. Njihove se uloge u crijevnoj zajednici proučavaju prema pojedinom soju.', 'Dokaz mora odgovarati točnom soju i pripravku; sastav gotovog proizvoda provjerava se prema aktualnoj deklaraciji.', ['nih-probiotics-ods']),
                $component('Bifidobakterije', 'Bifidobakterije koriste različite ugljikohidrate i stvaraju metabolite poput acetata i laktata. Druge crijevne bakterije mogu koristiti te proizvode u daljnjoj razgradnji.', 'Naziv skupine nije dokaz koristi kod određenog simptoma; opis razmjene metabolita je mehanistički. Sojevi proizvoda provjeravaju se prema aktualnoj deklaraciji.', ['nih-probiotics-ods', 'microbiota-metabolites-study']),
            ],
            'use_cases' => [
                $useCase('prob-nadutost', 'Nadutost i pitanje o IBS', 'Plin nastaje i kada mikrobi razgrađuju sastavnice hrane. Na osjećaj nadutosti utječu prehrana, kretanje plina i način rada probavnog sustava.', 'Poveži vrijeme pojave nadutosti s obrocima i upoznaj koji su sojevi i ishodi opisani u istraživanjima.', 'Neki pripravci pokazuju korist u IBS, uz različitu kvalitetu i rezultate studija.', 'To nije potvrda Active Pro B za IBS niti svih probiotika za IBD.', ['Kada se nadutost javlja?', 'Postoji li potvrđena dijagnoza?'], 'Nagla promjena, bol, gubitak težine ili tegobe koje ometaju život traže liječnika.', ['nih-probiotics-ods', 'nih-gas']),
                $useCase('prob-antibiotik', 'Pitanje uz antibiotsku terapiju', 'Neki specifični probiotici istraženi su radi proljeva povezanog s antibioticima.', 'Ljekarnik može usporediti istraženi soj s proizvodom i terapijom.', 'Korist ovisi o soju, populaciji i uvjetima primjene.', 'Ne određujemo raspored niti pretpostavljamo učinak ove formule.', ['Koji antibiotik osoba prima?', 'Odgovara li soj istraženom pripravku?'], 'Teški proljev zahtijeva liječnika; imunosupresija ili teška bolest procjenu prije probiotika.', ['nih-probiotics-ods', 'nih-probiotics']),
            ],
            'conversation_example' => 'Crijevni mikroorganizmi razgrađuju dio hrane i pritom stvaraju različite metabolite. Kod probiotika zato gledamo točan soj. Primjećujete li da se nadutost javlja nakon određenog obroka?',
            'takeaways' => ['Soj označava konkretnu varijantu mikroorganizma.', 'CFU opisuje održive mikroorganizme koji mogu stvoriti koloniju.'],
            'source_ids' => ['nih-probiotics-ods', 'nih-probiotics', 'microbiota-metabolites-study', 'nih-gas'],
        ],
        'forever-fiber' => [
            'learning_intro' => 'Vlakna su sastavnice biljne hrane koje probavni enzimi ne razgrađuju u potpunosti. Ovisno o vrsti, utječu na svojstva crijevnog sadržaja i služe kao supstrat crijevnim mikrobima. Svakodnevni unos povezuje izvore iz hrane, dodatke vlakana i odgovarajuću tekućinu.',
            'active_components' => [
                $component('Topiva vlakna', 'Topiva vlakna obuhvaćaju različite biljne ugljikohidrate. Odgovarajući unos vlakana i tekućine može pomoći mekšoj stolici i lakšem pražnjenju.', 'Opća prehrambena osnova; stvarna vrsta i količina određuju doprinos. Nisu sva topiva vlakna jednako viskozna ili laksativna.', ['nih-fiber', 'nih-ibs-food']),
                $component('Fermentabilna vlakna i fruktooligosaharidi, FOS', 'Fermentacija je mikrobna razgradnja sastavnica hrane u debelom crijevu. FOS su kratki lanci šećernih jedinica koji mogu biti supstrat tim mikroorganizmima; pritom nastaju metaboliti i plinovi.', 'Utvrđen probavni proces, bez obećanja učinka određene mješavine. Prebiotičku ulogu treba procijeniti prema točnom sastojku i količini.', ['nih-gas', 'nih-probiotics', 'fos-fermentation-study']),
            ],
            'use_cases' => [
                $useCase('fiber-zatvor', 'Zatvor uz nedovoljan unos vlakana', 'Malo biljnih namirnica može biti dio problema s pražnjenjem.', 'Uspoređujemo dnevnu prehranu i grame vlakana koje porcija može dopuniti.', 'NIDDK preporučuje postupno povećavanje vlakana uz odgovarajuću tekućinu.', 'To nije dijagnoza uzroka niti jamstvo učinka proizvoda.', ['Koliko biljnih namirnica dan sadrži?', 'Postoji li ograničenje tekućine?'], 'Krv, stalna bol, povraćanje ili nemogućnost ispuštanja plinova traže odmah liječnika.', ['nih-fiber', 'nih-constipation-symptoms']),
                $useCase('fiber-nadutost', 'Nadutost nakon povećanja vlakana', 'Brza promjena unosa može povećati fermentaciju i plinove.', 'Poučavamo postupnost i praćenje podnošljivosti stvarne vrste vlakna.', 'Fermentacija je normalan proces; reakcije se razlikuju.', 'Nadutost nije dokaz detoksikacije ili da proizvod djeluje.', ['Je li unos naglo povećan?', 'Koja vrsta vlakna je uvedena?'], 'Trajne tegobe, bol ili neobjašnjiv gubitak težine traže procjenu.', ['nih-gas', 'nih-fiber']),
            ],
            'conversation_example' => 'Vlakna dolaze iz žitarica, mahunarki, povrća i voća. Dio njih koriste i crijevni mikroorganizmi. Koji vam obrok najčešće sadrži te namirnice i koliko vlakana može dodati jedna porcija?',
            'takeaways' => ['Vlakna povezujemo s pražnjenjem i radom crijevne mikrobiote.', 'Ukupan unos promatramo kroz cijeli dan i povećavamo postupno.'],
            'source_ids' => ['nih-fiber', 'nih-gas', 'nih-ibs-food', 'fos-fermentation-study', 'nih-constipation-symptoms'],
        ],
        'forever-fields-of-greens' => [
            'learning_intro' => 'Biljna hrana donosi vlakna, vitamine, minerale i različite biljne pigmente. Vlakna imaju ulogu u pražnjenju i mikrobnoj razgradnji, a neki pigmenti izvor su vitamina A. Na biljnoj mješavini povezujemo pojedini sastojak s njegovim mjestom u svakodnevnoj prehrani.',
            'active_components' => [
                $component('Biljna vlakna', 'Vlakna su sastavnice biljne hrane koje sudjeluju u oblikovanju crijevnog sadržaja. Prehrana s odgovarajućim unosom vlakana i tekućine podupire uredno pražnjenje.', 'Opća nutritivna uloga, bez pretpostavke količine tablete.', ['nih-fiber']),
                $component('Biljni karotenoidi', 'Karotenoidi su pigmenti biljnih namirnica. Dio njih, poput beta karotena, tijelo može pretvoriti u vitamin A za normalan vid i imunološku funkciju.', 'Edukacija obitelji sastojaka, bez pripisivanja aktualnoj formuli.', ['nih-vitamin-a']),
            ],
            'use_cases' => [
                $useCase('greens-povrce', 'Malo povrća u svakodnevnoj prehrani', 'Povrće doprinosi raznolikosti prehrane i unosu vlakana te mikronutrijenata.', 'Poveži biljne sastojke mješavine s namirnicama koje osoba može uključiti u obroke.', 'Namirnice nude više hranjivih sastavnica; tabletu procjenjujemo prema porciji.', 'Ne izjednačavamo tabletu s porcijom povrća.', ['Koje povrće osoba rado jede?', 'U koji bi obrok moglo stati?'], 'Kod izrazito ograničene prehrane korisna je procjena nutricionista.', ['nih-supplements', 'nih-fiber']),
                $useCase('greens-zatvor', 'Usporedba zelenog proizvoda i vlakana', 'Osoba pretpostavlja da svaki zeleni proizvod daje dovoljno vlakana.', 'Uspoređujemo grame u porciji s proizvodom Fiber i prehrambenim izvorima.', 'Procjena je kvantitativna, prema dostupnoj deklaraciji.', 'Boja i broj biljaka ne dokazuju učinak na zatvor.', ['Koliko vlakana porcija navodi?', 'Je li podatak uopće dostupan?'], 'Zatvor koji traje ili ima alarmantne simptome procjenjuje liječnik.', ['nih-fiber', 'nih-constipation-symptoms']),
            ],
            'conversation_example' => 'Biljni sastojci otvaraju temu vlakana i pigmenata poput karotenoida. Možemo ih povezati i s namirnicama koje volite. Koje biste povrće najlakše uključili u ručak?',
            'takeaways' => ['Biljne namirnice donose vlakna i različite mikronutrijente.', 'Raznolikost biljnih izvora obogaćuje svakodnevne obroke.'],
            'source_ids' => ['nih-fiber', 'nih-vitamin-a', 'nih-supplements'],
        ],
        'forever-absorbent-c' => [
            'learning_intro' => 'Vitamin C je vitamin topljiv u vodi koji sudjeluje u stvaranju kolagena i normalnom radu imunosnog sustava. Djeluje kao antioksidans i povećava apsorpciju željeza iz biljnih namirnica. Bioflavonoide citrusa upoznajemo kao pridruženu skupinu biljnih spojeva.',
            'active_components' => [
                $component('Vitamin C', 'Vitamin C potreban je za kolagen, normalnu imunološku funkciju i zaštitu stanica od oksidativnog stresa. U obroku povećava apsorpciju nehemskog željeza iz biljnih namirnica.', 'Utvrđene nutritivne funkcije, uz provjeru količine i uvjeta tvrdnje.', ['nih-vitamin-c', 'eu-claims-regulation']),
                $component('Bioflavonoidi citrusa', 'Bioflavonoidi su skupina biljnih spojeva prisutnih u citrusima. U hrani i nekim formulama pojavljuju se uz vitamin C, pa ih je korisno upoznati kao zasebne sastavnice.', 'NIH ne potvrđuje opću nadmoć jednog oblika vitamina C.', ['nih-vitamin-c']),
            ],
            'use_cases' => [
                $useCase('c-imunitet', 'Prehrana i normalna imunološka funkcija', 'Ograničena raznolikost prehrane može smanjiti unos vitamina C.', 'Uspoređujemo hranu i doprinos deklarirane dnevne porcije.', 'Normalna imunološka funkcija je utvrđena uloga vitamina C.', 'Ne obećavamo sprječavanje prehlade ili infekcija.', ['Koliko voća i povrća dan sadrži?', 'Uzima li se drugi vitamin C?'], 'Učestale infekcije ili sumnja na manjak zahtijevaju stručnu procjenu.', ['nih-vitamin-c', 'nih-immune', 'eu-claims-regulation']),
                $useCase('c-biljni-obrok', 'Biljni obrok i apsorpcija željeza', 'Vitamin C povećava apsorpciju nehemskog željeza iz hrane.', 'Poučavamo povezivanje biljnog izvora željeza s izvorom vitamina C.', 'Utvrđena nutritivna funkcija.', 'To nije liječenje anemije niti potvrda potrebe za dodatkom.', ['Koji je izvor željeza u obroku?', 'Postoji li utvrđena anemija?'], 'Anemiju i poremećaje nakupljanja željeza procjenjuje liječnik.', ['nih-vitamin-c']),
            ],
            'conversation_example' => 'Vitamin C sudjeluje u kolagenu i može pomoći apsorpciji željeza iz biljnog obroka. Primjer je kombinacija mahunarki i izvora vitamina C. Koje voće ili povrće obično jedete uz takav obrok?',
            'takeaways' => ['Vitamin C sudjeluje u kolagenu, imunološkoj funkciji i zaštiti stanica.', 'Izvor vitamina C može dopuniti biljni obrok sa željezom.'],
            'source_ids' => ['nih-vitamin-c', 'nih-immune', 'eu-claims-regulation'],
        ],
        'forever-arctic-sea' => [
            'learning_intro' => 'EPA i DHA su dugolančane masne kiseline iz skupine omega 3. Ugrađuju se u stanične membrane i sudjeluju u stvaranju signalnih tvari, a DHA je posebno zastupljena u mozgu i mrežnici. Njihov unos povezujemo s prehranom, osobito s masnom ribom, i količinom u dnevnoj porciji.',
            'active_components' => [
                $component('EPA', 'EPA sudjeluje u građi staničnih membrana i služi kao polazište za nastanak signalnih tvari. Zajedno s DHA doprinosi normalnoj funkciji srca pri odgovarajućem unosu.', 'Utvrđena fiziološka uloga; proizvod procjenjujemo prema stvarnoj količini i uvjetima EU tvrdnje.', ['nih-omega3-professional', 'eu-claims-regulation']),
                $component('DHA', 'DHA je sastavnica membrana, posebno zastupljena u mozgu i mrežnici. Sudjeluje u strukturi tih tkiva, a njezin se prehrambeni unos izražava zasebno u miligramima.', 'Utvrđena fiziološka uloga, uz zasebne uvjete EU tvrdnji.', ['nih-omega3-professional', 'eu-claims-regulation']),
            ],
            'use_cases' => [
                $useCase('omega-riba', 'Rijedak unos ribe', 'Osoba rijetko jede ribu i želi razumjeti unos omega 3.', 'Uspoređujemo prehranu i deklarirane mg EPA te DHA po dnevnoj porciji.', 'To je prehrambena procjena dostupnih izvora.', 'Ukupni mg ulja ne predstavljaju mg EPA i DHA.', ['Koliko često se jede riba?', 'Koje količine EPA i DHA porcija daje?'], 'Alergije i redovitu terapiju treba uključiti u stručnu procjenu izbora.', ['nih-omega3', 'nih-supplements']),
                $useCase('omega-srce', 'Pitanje o normalnoj funkciji srca', 'EPA i DHA imaju prehrambenu ulogu povezanu s normalnom funkcijom srca.', 'Poveži njihovu ulogu s ukupnim dnevnim unosom iz hrane i deklarirane porcije.', 'EU tvrdnja za normalnu funkciju srca uključuje ukupni dnevni unos 250 mg EPA i DHA zajedno te ostale uvjete.', 'Ne prikazujemo dodatak kao terapiju triglicerida ili srčane bolesti.', ['Je li cilj prehrambeni unos ili liječenje nalaza?', 'Uzima li osoba antikoagulans?'], 'Bolest srca, antikoagulansi i razmatranje većih doza traže liječnika.', ['eu-claims-regulation', 'nih-omega3-professional']),
            ],
            'conversation_example' => 'EPA i DHA ugrađuju se u stanične membrane, a DHA je posebno zastupljena u mozgu i mrežnici. Pogledajmo njihove količine po dnevnoj porciji i izvore u vašoj prehrani. Koliko često jedete masnu ribu?',
            'takeaways' => ['EPA i DHA sudjeluju u membranama i signalizaciji.', 'Prehrambeni unos računamo prema miligramima EPA i DHA.'],
            'source_ids' => ['nih-omega3', 'nih-omega3-professional', 'eu-claims-regulation'],
        ],
        'forever-immublend' => [
            'learning_intro' => 'Vitamin C, vitamin D i cink sudjeluju u normalnom radu imunosnog sustava kroz različite fiziološke uloge. C je povezan s antioksidativnom zaštitom i kolagenom, D s imunološkom signalizacijom i metabolizmom kalcija, a cink s radom brojnih enzima. Njihov unos promatramo kroz raznoliku prehranu i dnevne porcije dodataka.',
            'active_components' => [
                $component('Vitamin C', 'Vitamin C sudjeluje u normalnoj imunološkoj funkciji i zaštiti stanica od oksidativnog stresa. Potreban je i za nastanak kolagena.', 'Utvrđena nutritivna funkcija, uz dovoljnu deklariranu količinu.', ['nih-vitamin-c', 'eu-claims-regulation']),
                $component('Vitamin D', 'Vitamin D sudjeluje u regulaciji imunoloških procesa i apsorpciji kalcija. Povezan je s održavanjem normalnih kostiju i mišićne funkcije.', 'Utvrđena nutritivna funkcija; dokazi sprječavanja infekcija nisu jednaki.', ['nih-immune', 'eu-claims-regulation']),
                $component('Cink', 'Cink je mineral potreban brojnim enzimima. Sudjeluje u normalnoj imunološkoj funkciji i procesima stvaranja proteina i DNA.', 'Utvrđena nutritivna funkcija, uz provjeru ukupnog unosa. Pretjeran dugotrajan unos može narušiti ravnotežu bakra.', ['nih-zinc', 'eu-claims-regulation']),
            ],
            'use_cases' => [
                $useCase('immu-ogr-prehrana', 'Ograničena prehrana i nutritivne potrebe', 'Raznolikost obroka utječe na unos mikronutrijenata.', 'Uspoređujemo potvrđene količine C, D i cinka s prehranom i drugim dodacima.', 'Nutrijenti imaju utvrđene funkcije; potreba za dopunom procjenjuje se pojedinačno.', 'Slab imunitet nije dijagnoza nutritivnog manjka.', ['Koje su skupine hrane rijetko zastupljene?', 'Koji se dodaci već koriste?'], 'Sumnja na manjak ili učestale infekcije zahtijeva stručnu procjenu.', ['nih-immune', 'nih-supplements']),
                $useCase('immu-kombiniranje', 'Immublend uz vitamin C ili multivitamin', 'Zajednički sastojci mogu se ponoviti u više formula.', 'Izračunavamo ukupan dnevni unos prije razmatranja kombinacije.', 'Više proizvoda ne dokazuje dodatnu korist; previsok unos može biti štetan.', 'Ne obećavamo jačanje imuniteta ni terapiju autoimune bolesti.', ['Kolike su dnevne porcije?', 'Ponavljaju li se C, D ili cink?'], 'Imunosupresiju, autoimunu bolest i terapiju procjenjuje liječnik.', ['nih-supplements', 'nih-zinc', 'nih-immune']),
            ],
            'conversation_example' => 'C, D i cink sudjeluju u normalnom radu imunosnog sustava kroz različite uloge. Pogledajmo gdje ih dobivate hranom i koliki doprinos daju dnevne porcije koje uzimate. Koje dodatke uključujete u svoju rutinu?',
            'takeaways' => ['C, D i cink imaju različite uloge u normalnoj imunološkoj funkciji.', 'Dnevni unos povezujemo s hranom i količinama u porciji.'],
            'source_ids' => ['nih-immune', 'nih-vitamin-c', 'nih-zinc', 'nih-supplements', 'eu-claims-regulation'],
        ],
        'forever-ultra' => [
            'learning_intro' => 'Proteini su izvor aminokiselina koje tijelo koristi za održavanje i obnovu tkiva. Proteinski napitak može biti praktičan način njihova uključivanja u obrok, osobito kada je unos hrane ili raspored obroka ograničen. Pripremljena porcija povezuje prašak, odabranu tekućinu i ostale namirnice u obroku.',
            'active_components' => [
                $component('Proteini i aminokiseline', 'Probava razgrađuje proteine u aminokiseline. Tijelo ih koristi za vlastite proteine, uključujući mišićne strukture, enzime i druge sastavnice tkiva.', 'Utvrđena nutritivna uloga; količina konkretne formule ovdje nije potvrđena.', ['nih-digestion', 'nih-protein']),
                $component('Vitamini i minerali', 'Mikronutrijenti sudjeluju u brojnim tjelesnim funkcijama. Obogaćene formule mogu doprinijeti njihovu unosu, a sastav pripremljenog napitka uključuje i odabranu tekućinu.', 'Potrebna je potvrda identiteta i količine svakog nutrijenta.', ['nih-supplements']),
            ],
            'use_cases' => [
                $useCase('ultra-obrok', 'Obrok s malo proteina', 'Osoba želi jednostavnije uključiti izvor proteina u obrok.', 'Uspoređujemo prašak s hranom te računamo proteine u pripremljenoj porciji.', 'Protein ima nutritivnu ulogu; dodatak nije automatski potreban uz adekvatnu prehranu.', 'Ne proglašavamo ovu obitelj potpunom zamjenom obroka.', ['Što taj obrok već sadrži?', 'Priprema li se s vodom ili mlijekom?'], 'Bolest bubrega ili propisana proteinska ograničenja zahtijevaju stručni plan.', ['nih-digestion', 'nih-protein', 'nih-kidney-protein']),
                $useCase('ultra-aktivnost', 'Praktičan unos proteina uz vježbanje', 'Aktivna osoba procjenjuje ukupnu prehranu i praktičnost obroka.', 'Proteinski pripravak razmatra se kao jedan mogući prehrambeni izvor.', 'Odgovarajući unos proteina podržava održavanje i obnovu mišića.', 'Ne obećavamo rast mišića ili mršavljenje samo uporabom proizvoda.', ['Koliki je ukupni unos kroz dan?', 'Postoji li stvarna prehrambena praznina?'], 'Posebne prehrambene potrebe i zdravstvena ograničenja procjenjuje stručnjak.', ['nih-protein', 'nih-supplements']),
            ],
            'conversation_example' => 'Proteini daju aminokiseline za održavanje i obnovu tkiva. Pogledajmo vaš uobičajeni doručak i kako bi pripremljeni napitak doprinio tom obroku. Koji izvor proteina već uključujete?',
            'takeaways' => ['Aminokiseline iz proteina služe održavanju i obnovi tkiva.', 'Računamo doprinos pripremljene porcije cijelom obroku.'],
            'source_ids' => ['nih-digestion', 'nih-protein', 'nih-supplements', 'nih-kidney-protein'],
        ],
    ],
    'needs' => [
        [
            'id' => 'zatvor-i-vlakna', 'title' => 'Zatvor i nedovoljan unos vlakana',
            'description' => 'Razumij prehrambene izvore, postupnost i doprinos stvarne porcije.',
            'goal' => 'Procijeniti prehrambeni unos vlakana prije razmatranja dopune.',
            'explanation' => 'Vlakna i tekućina mogu pomoći mekšoj stolici i pražnjenju. Fiber i Fields of Greens uspoređujemo prema gramima, a aloe ne pretpostavljamo kao laksativ.',
            'lesson_ids' => ['probava-osnove', 'vlakna-i-navike'], 'product_keys' => ['forever-fiber', 'forever-fields-of-greens'],
            'contributions' => [['product_key' => 'forever-fiber', 'role' => 'Primjer mjerenja doprinosa vlakana.'], ['product_key' => 'forever-fields-of-greens', 'role' => 'Primjer razlike biljne mješavine i količine vlakana.']],
            'when_refer' => 'Krv, stalna bol, povraćanje ili nemogućnost ispuštanja plinova zahtijevaju odmah liječnika.', 'source_ids' => ['nih-fiber', 'nih-constipation-symptoms'],
        ],
        [
            'id' => 'nadutost-i-mikrobiota', 'title' => 'Nadutost i pitanje o probioticima',
            'description' => 'Poveži fermentaciju, prehrambene navike i dokaze za točne sojeve.',
            'goal' => 'Razlikovati uzroke simptoma od svojstava probiotičkog pripravka.',
            'explanation' => 'Mikrobi stvaraju plin razgradnjom neprobavljenih ugljikohidrata. Vlakna mogu mijenjati taj proces, dok eventualna korist probiotika ovisi o soju i istraženoj formuli.',
            'lesson_ids' => ['mikrobiota-i-probiotici', 'vlakna-i-navike'], 'product_keys' => ['forever-active-pro-b', 'forever-fiber'],
            'contributions' => [['product_key' => 'forever-active-pro-b', 'role' => 'Učenje o soju, CFU i istraženom ishodu.'], ['product_key' => 'forever-fiber', 'role' => 'Učenje o fermentaciji i postupnom povećavanju unosa.']],
            'when_refer' => 'Trajne tegobe, nagla promjena, bol ili gubitak težine zahtijevaju liječnika.', 'source_ids' => ['nih-gas', 'nih-probiotics-ods'],
        ],
        [
            'id' => 'gastritis-i-refluks', 'title' => 'Gastritis, refluks i pitanje o aloe',
            'description' => 'Odvoji upalu želuca, povrat sadržaja u jednjak i ograničena istraživanja aloe.',
            'goal' => 'Dati jasan odgovor o dokazima bez izmišljanja preporuke.',
            'explanation' => 'Gastritis ima različite uzroke i zahtijeva odgovarajuću procjenu. Mali pokus aloe sirupa kod refluksnih simptoma nije dokaz za Forever napitke. Vrijeme obroka i osobni okidači korisna su tema razgovora.',
            'lesson_ids' => ['aloe-sastav-i-dokazi', 'citanje-deklaracije'], 'product_keys' => ['forever-aloe-vera-gel', 'forever-aloe-peaches'],
            'contributions' => [['product_key' => 'forever-aloe-vera-gel', 'role' => 'Usporedba pripravka iz studije i završnog napitka.'], ['product_key' => 'forever-aloe-peaches', 'role' => 'Razgovor o podnošljivosti voćnog napitka.']],
            'when_refer' => 'Trajne tegobe ili otežano gutanje traže liječnika; krvarenje ili bol u prsima hitnu procjenu.', 'source_ids' => ['nih-gastritis', 'nih-reflux', 'nih-reflux-symptoms', 'aloe-reflux-trial'],
        ],
        [
            'id' => 'prehrana-i-imunitet', 'title' => 'Prehrana za normalnu imunološku funkciju',
            'description' => 'Razumij uloge C, D i cinka te preklapanje proizvoda.',
            'goal' => 'Prepoznati nutritivni doprinos i stvarnu potrebu za dopunom.',
            'explanation' => 'Dovoljan unos nutrijenata važan je za normalne imunološke funkcije. To ne znači sprječavanje infekcija. Aloe polisaharidi imaju drugačiju razinu dokaza od vitamina C.',
            'lesson_ids' => ['citanje-deklaracije', 'aloe-sastav-i-dokazi', 'razgovor-i-kombinacije'], 'product_keys' => ['forever-absorbent-c', 'forever-immublend', 'forever-aloe-vera-gel'],
            'contributions' => [['product_key' => 'forever-absorbent-c', 'role' => 'Doprinos deklariranog vitamina C.'], ['product_key' => 'forever-immublend', 'role' => 'Usporedba više mikronutrijenata i njihova zbrajanja.'], ['product_key' => 'forever-aloe-vera-gel', 'role' => 'Razlika vitamina C i dokaza za aloe.']],
            'when_refer' => 'Učestale infekcije, sumnja na manjak ili imunosupresija zahtijevaju liječnika.', 'source_ids' => ['nih-immune', 'nih-vitamin-c', 'nih-zinc', 'eu-claims-regulation'],
        ],
        [
            'id' => 'brusnica-i-mokracni-sustav', 'title' => 'Brusnica i mokraćni sustav',
            'description' => 'Razlikuj PAC, istraživanja ponavljanja infekcija i aktualne simptome.',
            'goal' => 'Objasniti što istraživanja brusnice mogu, a što ne mogu potvrditi.',
            'explanation' => 'Određeni pripravci brusnice imaju istraživanja kod ponavljajućih infekcija u nekih žena. Berry Nectar ne nasljeđuje te rezultate bez odgovarajućeg sastava i dokaza.',
            'lesson_ids' => ['citanje-deklaracije', 'aloe-sastav-i-dokazi'], 'product_keys' => ['aloe-berry-nectar'],
            'contributions' => [['product_key' => 'aloe-berry-nectar', 'role' => 'Učenje razlike voćnog dodatka i istraženog pripravka PAC.']],
            'when_refer' => 'Postojeće peckanje i drugi urinarni simptomi zahtijevaju zdravstvenu procjenu.', 'source_ids' => ['nih-cranberry'],
        ],
        [
            'id' => 'unos-omega3', 'title' => 'Rijedak unos ribe i omega 3',
            'description' => 'Nauči računati EPA i DHA te razumjeti dopuštenu nutritivnu tvrdnju.',
            'goal' => 'Procijeniti prehrambene izvore i količinu stvarne porcije.',
            'explanation' => 'EPA i DHA sudjeluju u membranama i signalizaciji. Količina ulja nije njihova količina, a prehrambeni doprinos nije terapija kardiološkog nalaza.',
            'lesson_ids' => ['citanje-deklaracije'], 'product_keys' => ['forever-arctic-sea'],
            'contributions' => [['product_key' => 'forever-arctic-sea', 'role' => 'Primjer usporedbe ukupnog ulja te mg EPA i DHA.']],
            'when_refer' => 'Antikoagulansi, srčana bolest i razmatranje većih doza traže liječnika.', 'source_ids' => ['nih-omega3-professional', 'eu-claims-regulation'],
        ],
        [
            'id' => 'protein-u-obroku', 'title' => 'Obrok s malo proteina',
            'description' => 'Razumij aminokiseline, pripremu napitka i cijeli obrok.',
            'goal' => 'Procijeniti postoji li prehrambena praznina koju praktičan izvor može dopuniti.',
            'explanation' => 'Proteini su izvor aminokiselina za održavanje tkiva. Prašak procjenjujemo uz dodanu tekućinu i ostatak obroka, nakon potvrde točne formule.',
            'lesson_ids' => ['probava-osnove', 'citanje-deklaracije'], 'product_keys' => ['forever-ultra'],
            'contributions' => [['product_key' => 'forever-ultra', 'role' => 'Edukacija obitelji proteinskih prašaka i pripremljene porcije.']],
            'when_refer' => 'Bolest bubrega i propisana proteinska ograničenja zahtijevaju stručni plan.', 'source_ids' => ['nih-digestion', 'nih-protein', 'nih-kidney-protein'],
        ],
    ],
    'routines' => [
        [
            'id' => 'aloe-probiotik-provjera', 'title' => 'Aloe i probiotik: dvije različite uloge', 'status' => 'editorial_candidate',
            'summary' => 'Urednički primjer za usporedbu biljnog napitka i točnih živih mikroorganizama.',
            'goal' => 'Razumjeti doprinos svakog sastava prije zaključka o kombinaciji.',
            'explanation' => 'Aloe napitak daje deklarirane sastavnice napitka, a probiotički proizvod određene sojeve i CFU. Zajedničko uzimanje ne dokazuje sinergiju niti korist kod gastritisa, refluksa ili IBS. Razlog za svaki proizvod procjenjuje se zasebno.',
            'product_keys' => ['forever-aloe-vera-gel', 'forever-active-pro-b'],
            'contributions' => [['product_key' => 'forever-aloe-vera-gel', 'role' => 'Učenje o obliku aloe, polisaharidima i eventualnom vitaminu C.'], ['product_key' => 'forever-active-pro-b', 'role' => 'Učenje o soju i istraženom probiotičkom ishodu.']],
            'checks' => ['Porcije slijede aktualne upute, bez povećavanja radi simptoma.', 'Dokaz za pojedinačni sastojak odvojiti od dokaza za kombinaciju.', 'Ako napitak sadrži vitamin C, uračunati druge izvore.'],
            'when_refer' => 'Imunosupresija, teška bolest ili terapija zahtijevaju stručnu procjenu.', 'source_ids' => ['nih-aloe', 'nih-probiotics', 'nih-supplements'],
        ],
        [
            'id' => 'vlakna-probiotik-provjera', 'title' => 'Vlakna i probiotik: prehrambeni supstrat i mikroorganizam', 'status' => 'editorial_candidate',
            'summary' => 'Urednički primjer za razumijevanje mikrobiote, fermentacije i podnošljivosti.',
            'goal' => 'Razlikovati dopunu vlakana od dodavanja točnih probiotičkih sojeva.',
            'explanation' => 'Fiber može dopuniti potvrđene grame vlakana, dok Active Pro B daje deklarirane žive mikroorganizme. Neka vlakna fermentiraju, ali nisu sva prebiotici. Dva zasebna proizvoda ne čine automatski klinički dokazanu sinbiotičku kombinaciju.',
            'product_keys' => ['forever-fiber', 'forever-active-pro-b'],
            'contributions' => [['product_key' => 'forever-fiber', 'role' => 'Količina i vrsta vlakana, postupnost i tekućina.'], ['product_key' => 'forever-active-pro-b', 'role' => 'Točan soj, CFU, čuvanje i dokazi za pripravu.']],
            'checks' => ['Slijediti deklarirane porcije, bez univerzalnog rasporeda.', 'Zbrojiti vlakna iz hrane i dodataka.', 'Pratiti podnošljivost jer više fermentacije može povećati plinove.'],
            'when_refer' => 'Trajne tegobe ili imunosupresija zahtijevaju liječnika.', 'source_ids' => ['nih-fiber', 'nih-gas', 'nih-probiotics-ods'],
        ],
        [
            'id' => 'mikronutrijenti-provjera', 'title' => 'Vitamin C i Immublend: izračun ukupnog unosa', 'status' => 'editorial_candidate',
            'summary' => 'Urednički primjer koji može pokazati i nepotrebno preklapanje proizvoda.',
            'goal' => 'Povezati prehrambeni cilj s potvrđenim količinama bez automatskog paketa.',
            'explanation' => 'Absorbent C služi učenju o vitaminu C, a Immublend o više mikronutrijenata. Ako oba sadrže C, ukupni unos zbrajamo uz ostale proizvode. Isti cilj ne zahtijeva nužno oba proizvoda, a više nutrijenata ne znači bolju zaštitu od infekcija.',
            'product_keys' => ['forever-absorbent-c', 'forever-immublend'],
            'contributions' => [['product_key' => 'forever-absorbent-c', 'role' => 'Doprinos vitamina C i njegovih utvrđenih funkcija.'], ['product_key' => 'forever-immublend', 'role' => 'Doprinos potvrđenih C, D i cinka te provjera preklapanja.']],
            'checks' => ['Računati deklarirane dnevne porcije svih proizvoda.', 'Provjeriti ponavljaju li se C, D i cink.', 'Potrebu za dopunom povezati s prehranom ili stručnom procjenom.'],
            'when_refer' => 'Sumnja na manjak, učestale infekcije ili redovita terapija traže stručnu procjenu.', 'source_ids' => ['nih-vitamin-c', 'nih-immune', 'nih-zinc', 'nih-supplements'],
        ],
    ],
    'sources' => [
        'nih-digestion' => ['title' => 'NIH NIDDK: kako radi probavni sustav', 'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/digestive-system-how-it-works', 'kind' => 'Službeni edukativni izvor'],
        'nih-fiber' => ['title' => 'NIH NIDDK: prehrana i vlakna kod zatvora', 'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/constipation/eating-diet-nutrition', 'kind' => 'Službeni edukativni izvor'],
        'nih-constipation-symptoms' => ['title' => 'NIH NIDDK: simptomi zatvora i liječnički pregled', 'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/constipation/symptoms-causes', 'kind' => 'Službeni medicinski izvor'],
        'nih-gas' => ['title' => 'NIH NIDDK: nadutost, plinovi i fermentacija', 'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/gas-digestive-tract/symptoms-causes', 'kind' => 'Službeni medicinski izvor'],
        'nih-ibs-food' => ['title' => 'NIH NIDDK: prehrana i vrste vlakana kod IBS', 'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/irritable-bowel-syndrome/eating-diet-nutrition', 'kind' => 'Službeni medicinski izvor'],
        'nih-probiotics' => ['title' => 'NIH NCCIH: probiotici i sigurnost', 'url' => 'https://www.nccih.nih.gov/health/probiotics-usefulness-and-safety', 'kind' => 'Službeni pregled dokaza'],
        'nih-probiotics-ods' => ['title' => 'NIH ODS: probiotički sojevi i klinički dokazi', 'url' => 'https://ods.od.nih.gov/factsheets/Probiotics-HealthProfessional/', 'kind' => 'Službeni stručni pregled'],
        'nih-aloe' => ['title' => 'NIH NCCIH: aloe vera, dokazi i sigurnost', 'url' => 'https://www.nccih.nih.gov/health/aloe-vera', 'kind' => 'Službeni pregled dokaza'],
        'aloe-acemannan-study' => ['title' => 'Acemannan i mišje dendritičke stanice', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/11460308/', 'kind' => 'Prekliničko laboratorijsko istraživanje'],
        'aloe-acemannan-structure' => ['title' => 'Građa manoznog polisaharida acemannana iz aloe', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/24751035/', 'kind' => 'Primarno kemijsko istraživanje'],
        'microbiota-metabolites-study' => ['title' => 'Bifidobakterije i razmjena metabolita među crijevnim bakterijama', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/16672507/', 'kind' => 'Primarno mehanističko istraživanje'],
        'fos-fermentation-study' => ['title' => 'Fruktooligosaharidi i fermentacijski proizvodi u zdravih odraslih', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/12368393/', 'kind' => 'Randomizirano istraživanje određenih sastojaka'],
        'aloe-reflux-trial' => ['title' => 'Pilot istraživanje posebnog aloe sirupa kod refluksnih simptoma', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/26742306/', 'kind' => 'Malo randomizirano kliničko istraživanje'],
        'nih-gastritis' => ['title' => 'NIH NIDDK: gastritis i gastropatija', 'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/gastritis-gastropathy/definition-facts', 'kind' => 'Službeni medicinski izvor'],
        'nih-reflux' => ['title' => 'NIH NIDDK: refluks, prehrana i vrijeme obroka', 'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/acid-reflux-ger-gerd-adults/eating-diet-nutrition', 'kind' => 'Službeni medicinski izvor'],
        'nih-reflux-symptoms' => ['title' => 'NIH NIDDK: simptomi refluksa i potreba za pregledom', 'url' => 'https://www.niddk.nih.gov/health-information/digestive-diseases/acid-reflux-ger-gerd-adults/symptoms-causes', 'kind' => 'Službeni medicinski izvor'],
        'nih-cranberry' => ['title' => 'NIH NCCIH: brusnica i granice dokaza', 'url' => 'https://www.nccih.nih.gov/health/cranberry', 'kind' => 'Službeni pregled dokaza'],
        'cranberry-pac-study' => ['title' => 'PAC brusnice i bakterijsko prianjanje u uzorcima urina', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/20398248/', 'kind' => 'Mehanističko istraživanje analizom uzoraka izvan tijela'],
        'nih-vitamin-a' => ['title' => 'NIH ODS: vitamin A i karotenoidi', 'url' => 'https://ods.od.nih.gov/factsheets/VitaminA-Consumer/', 'kind' => 'Službeni izvor o hranjivoj tvari'],
        'nih-vitamin-c' => ['title' => 'NIH ODS: vitamin C', 'url' => 'https://ods.od.nih.gov/factsheets/VitaminC-Consumer/', 'kind' => 'Službeni izvor o hranjivoj tvari'],
        'nih-immune' => ['title' => 'NIH ODS: nutrijenti i imunološke funkcije', 'url' => 'https://ods.od.nih.gov/factsheets/ImmuneFunction-HealthProfessional/', 'kind' => 'Službeni stručni pregled'],
        'nih-zinc' => ['title' => 'NIH ODS: cink i sigurnost ukupnog unosa', 'url' => 'https://ods.od.nih.gov/factsheets/Zinc-HealthProfessional/', 'kind' => 'Službeni izvor o hranjivoj tvari'],
        'nih-omega3' => ['title' => 'NIH ODS: masne kiseline omega 3', 'url' => 'https://ods.od.nih.gov/factsheets/Omega3FattyAcids-Consumer/', 'kind' => 'Službeni izvor o hranjivoj tvari'],
        'nih-omega3-professional' => ['title' => 'NIH ODS: EPA, DHA, dokazi i sigurnost', 'url' => 'https://ods.od.nih.gov/factsheets/Omega3FattyAcids-HealthProfessional/', 'kind' => 'Službeni stručni pregled'],
        'nih-protein' => ['title' => 'NIH ODS: proteini, prehrana i vježbanje', 'url' => 'https://ods.od.nih.gov/factsheets/ExerciseAndAthleticPerformance-HealthProfessional/', 'kind' => 'Službeni stručni pregled'],
        'nih-kidney-protein' => ['title' => 'NIH NIDDK: proteini i prehrana kod kronične bubrežne bolesti', 'url' => 'https://www.niddk.nih.gov/health-information/kidney-disease/chronic-kidney-disease-ckd/eating-nutrition', 'kind' => 'Službeni medicinski izvor'],
        'nih-supplements' => ['title' => 'NIH ODS: što treba znati o dodacima prehrani', 'url' => 'https://ods.od.nih.gov/factsheets/WYNTK-Consumer/', 'kind' => 'Službeni izvor o sigurnosti'],
        'eu-claims-regulation' => ['title' => 'EU: odobrene tvrdnje i uvjeti za hranjive tvari', 'url' => 'https://eur-lex.europa.eu/legal-content/HR/TXT/?uri=CELEX:32012R0432', 'kind' => 'Službeni regulatorni izvor'],
    ],
];
