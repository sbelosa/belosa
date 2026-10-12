# Stanje FCC sustava

Ovo je jedini živi dokument o tome što je stvarno na produkciji i kako se objavljuje. Ažurira se pri svakoj objavi. Datirani izvještaji u `docs/partner` služe kao povijest.

Zadnja provjera: 12. listopada 2026., Claude, grana `claude/live-baseline-2026-10-11`.

## 1. Izvor istine

1. Polazište je stvarna produkcija od 11. listopada 2026. Korisnik je preuzeo `public_html.zip` u 18:30 i cPanel backup baze od 00:03.
2. Commit `74d4645` sadrži live `app/` i `themes/` bez promjena. Svaka sljedeća promjena je zaseban commit na toj osnovi.
3. U odnosu na `origin/main` produkcija je imala 905 datoteka kojih nema u Gitu i 557 izmijenjenih. Nijedna datoteka iz `origin/main` nije nedostajala na produkciji.
4. Codex Partner kopija razlikovala se od produkcije u 39 datoteka, od toga 13 u jezičnom cacheu. Uvezena Codex kopija bila je znatno starija od produkcije. Codexove kopije nisu mijenjane.

## 2. Lokalno okruženje

1. Adresa je `http://localhost:8099`, a Docker projekt `fcc-claude` u mapi `ClaudeWorkspaces/FCC-runtime`.
2. Baza je potpuna kopija produkcije: 174 tablice, 710 računa i 1890 kartica.
3. Aplikacija i baza nemaju izlaz na internet. Emailovi, Stripe, OpenAI, FLP360 i push obavijesti zato ne mogu ništa poslati. AI Coach lokalno ne odgovara.
4. Kod se učitava iz `ClaudeWorkspaces/FCC-Live`, pa je svaka izmjena odmah vidljiva lokalno.
5. Produkcijski `config.php`, `ops-readonly-config.php` i operativni ključevi iz `.htaccess` uklonjeni su iz lokalne kopije. Lokalna konfiguracija je zasebna.
6. Pokretanje i zaustavljanje iz mape `FCC-runtime`: `docker compose up -d` i `docker compose stop`.

## 3. Stvarna uporaba na dan snimka

1. Od 710 računa, 616 je na Beginneru, a 84 na PRO paketu.
2. U zadnjih 30 dana aktivna su bila 134 računa, a u zadnjih 7 dana njih 91.
3. Aktivni ciklusi programa: v8 ima 81 korisnika, v7 njih 30, a v6 dvoje.
4. Barem jedan korak programa završila su 54 korisnika. Najviše završenih koraka po korisniku je 12.

## 4. Testovi prije objave

1. Deploy workflow pokreće PHP lint i 13 testova. Na live kodu prije popravka 6 testova nije prolazilo, zato redovna objava preko `main` nije mogla proći.
2. Nakon commita `90edf44` lint prolazi za 1858 PHP datoteka, a svih 13 testova prolazi lokalno.

## 5. Ispravljene greške

1. Prikaz paketa i stranica Moj paket prosljeđivali su `'+10 years'` kroz prijevod. Na njemačkom, slovenskom i drugim jezicima PHP je tada uspoređivao s današnjim datumom. Zato je obnovljivi PRO mogao biti prikazan kao doživotni, a gumb onemogućen.

## 5a. Pojednostavljenje za suradnike (na produkciji od 12. listopada 2026., commit 300355f)

1. **Više** ima četiri skupine: Učenje, Susreti i događaji, Rezultati i tim te Paket i postavke. Tehnički alati su u zatvorenom odjeljku Napredni alati: poveznice, QR kodovi, funneli, statistika, pikseli, domene, projekti, uvodne stranice i kanali obavijesti. Nijedan alat nije uklonjen, a poveznica `partner/more#tools` otvara odjeljak.
2. **Danas** počinje današnjim korakom Mojeg puta. Iznad njega su samo stvarni zadaci koji čekaju, poslovni upiti i dospjela timska javljanja. Događaj, mjesečni bodovi i sponzor slijede ispod. Vlastita promjena CC bodova više se ne prikazuje dvaput.
3. **Zapelo mi je** je u izborniku pomoći, oznaka `?`. Otvara podršku s naslovom stranice, kategorijom i dva pitanja: što sam htio napraviti i gdje je zapelo. Prijave idu u postojeće `feedback_tickets`.
4. Moj put više ne spominje stari program od 60 koraka.
5. **Akademija proizvoda** na početku nudi 10 proizvoda koje suradnici najčešće dijele. Popis je u `app/config/fcc_product_education_starter.php`, s opisom načina odabira, a treba ga povremeno osvježiti novim podacima o klikovima.
6. Akademija i usporedba paketa unutar radnog prostora koriste zajedničku paletu `--fp-*` iz `fcc-partner-premium.css`.
7. Svi novi tekstovi prevedeni su na 12 jezika u `app/config/locales`.

## 5c. Veliki update prema CC bodovima (na produkciji od 12. listopada 2026., commit 300355f)

1. **Javni popisi prate CC iz FLP360.** Istaknute aplikacije traže PRO i najmanje 1 osobni CC, a Preporučeni sponzori PRO i potvrđenu 4CC aktivnost. Oba uvjeta vrijede za tekući ili prošli kalendarski mjesec po vremenu Europe/Zagreb. Jedini izvor pravila je `app/helpers/fcc_cc_visibility.php`. Iznosi CC bodova nigdje se javno ne prikazuju. Na kopiji podataka popis Istaknutih aplikacija narastao je s 14 na 46, a Preporučenih sponzora s 5 na oko 25.
2. **Klikovi su samo statistika.** FCC Rezultati, uređivač kartice, statistika i Coach više ne spominju pragove 15+ i 50+. Emailovi o prelasku praga klikova su ugašeni.
3. **AI analiza aplikacije i tjedni AI plan su ukinuti.** Rute `ai-plan` i `ai-app-review` preusmjeravaju suradnike na Danas i Moju karticu, a administrator zadržava pristup povijesti. Usporedba paketa na 13 jezika opisuje novu PRO vrijednost.
4. **4CC WhatsApp grupa.** Svaki suradnik s 4CC aktivnošću, s PRO paketom ili bez njega, vidi poziv na stranici Danas, s gumbima Pridruži se grupi i Već sam član. PRO je uvjet samo za javni prikaz. Članstvo vrijedi dok je suradnik bez prekida kvalificiran. Poveznica grupe ugrađena je u kod (`FCC_CCV_GROUP_DEFAULT_URL`), a u administraciji (`partner/admin#cc4-group`) može se zamijeniti ili isključiti praznim poljem. Tamo su i popis za uklanjanje te popis kvalificiranih koji se još nisu pridružili.
5. **Podsjetnici za događaje** stižu samo suradnicima koji su potvrdili dolazak ili imaju prijavljene goste. Na kartici događaja na stranici Danas dodan je gumb Dolazim. Na kopiji podataka od 3.000 podsjetnika na čekanju ostao bi 21.
6. **Povučene timske obavijesti** više se ne prikazuju u popisu obavijesti.
7. **Prvi tjedan Mojeg puta**, koraci 1 do 7, otvoren je i bez upisa od 0,33 CC. Nakon 7. koraka prikazuje se jasna poruka da se program nastavlja nakon prve narudžbe.
8. **Pravila su javna.** Prikazana su na stranicama Istaknute aplikacije i Preporučeni sponzori, a novi članak „Javni popisi, CC bodovi i 4CC grupa” u Uputama za FCC postoji na 13 jezika.
9. **Novi test** `scripts/test_cc_visibility.php` dodan je u deploy workflow.

## 5d. Pozivnica u tim, Moj put i GeoLite (lokalno, čeka objavu)

1. **Pozivnica u tim.** Svaki odobreni suradnik s aktivnom karticom i Forever ID-jem automatski dobiva trajnu kratku poveznicu `forevercard.club/poziv/<kod>` s QR kodom. Poveznica vodi na model Upoznaj poslovanje s ugrađenom preporukom. Logika je u `app/helpers/fcc_business_invite.php`, ruta u `BusinessInvite`, a tablice `fcc_business_invites` i `fcc_business_invite_registrations` nastaju pri prvom upisu.
2. **Javna stranica** (`themes/altum/views/partner/business-public.php`) dobila je poglavlja Što dobivaš u našem timu i Nakon narudžbe, gumb za kopiranje broja sponzora i poveznicu na FCC registraciju s kodom pozivnice. Osobni uvod prikazuje se u svim modelima.
3. **PRO dodaje** osobni YouTube ili Vimeo video i uvod (stupci `personal_video_*` u `fcc_business_presentations`) te detaljnu statistiku. Beginner vidi pozivnicu, QR kod i broj registracija.
4. **Obavijesti sponzoru:** kada posjetitelj otvori službenu narudžbu (jednom po posjetitelju i danu) i kada se netko registrira u FCC preko pozivnice. Upiti su već imali obavijest.
5. **Izbornik Podijeli** ima tri izbora: Pozivnica u tim, Proizvodi te Webinari i događaji s podizbornikom.
6. **Administracija** (`partner/admin#invite-admin`): prekidač za otvaranje pozivnice svim suradnicima (`fcc_business_landing_settings.release_mode`) i rokovi provjerenih podataka o Start paketu. Administratori dobivaju tjedni podsjetnik kada do isteka provjere ostane 7 dana ili manje.
7. **AI savjetnik** na kartici nudi trajnu pozivnicu, a nikad poveznicu koja gostu vraća 404.
8. **Moj put umjesto 120-dnevnog plana.** Naslovnica na 13 jezika, članak pomoći i vodič u kodu govore o programu Moj put s 90 koraka. Sadržaj u bazi (članak `120-dnevni-plan` postaje `moj-put` na 13 jezika, spominjanja u člancima `ai-savjetnik`, `start-paket`, `zasto-sustav-ima-smisla`, stranica `how-it-works` i dvije kartice Stjepanovog vodiča) mijenja se gumbom u `partner/admin#content-migration`. Svaka stara vrijednost sprema se u `fcc_content_migration_log`, a gumb Vrati vraća sve što nakon toga nitko nije uređivao. Stara adresa članka preusmjerava na `moj-put` tek kada novi članak postoji.
9. **Oznake telefona u prijevodima.** Srpski, švedski i rumunjski članci koriste `[telefon]`, a francuski `[téléphone]` (ukupno 392 članka). `app/core/Shortcodes.php` ih sada prepoznaje, pa se broj suradnika prikazuje.
10. **GeoLite.** Workflow `.github/workflows/refresh-geolite.yml` svake srijede preuzima službene MaxMind baze, provjerava kontrolni zbroj i sadržaj (`scripts/check_geolite.py`) i FTP-om zamjenjuje samo dvije `.mmdb` datoteke. Dok nema tajni `MAXMIND_ACCOUNT_ID` i `MAXMIND_LICENSE_KEY`, preskače rad bez greške.
11. **Novi test** `scripts/test_invite_content_migration.php` dodan je u deploy workflow.
12. **Nakon objave administrator:** u `partner/admin` otvara Pozivnicu u tim svima i primjenjuje Ažuriranje sadržaja: Moj put. Video na Vimeu `1188447137` u Stjepanovom vodiču možda i dalje spominje 120 dana, to može zamijeniti samo autor.

## 5e. Moj put: tri staze, napredak suradnika i jasniji koraci (lokalno, čeka objavu)

Korisnik je 12. listopada 2026. odobrio sve prijedloge iz `docs/ANALIZA_MOJ_PUT_2026-10-12.md`. Logika je u `app/helpers/fcc_journey_tracks.php`. Svi zapisi koriste postojeće tablice `fcc_partner_pilot_events` i `forever_business_daily_outcomes`, pa nova tablica ni migracija nisu potrebne.

1. **Tri staze.** Prvih 15 koraka isti su za sve. Nakon njih suradnik bira Koristim i preporučujem, Gradim dodatni prihod ili Gradim tim. Svih 90 koraka ostaje, mijenja se redoslijed. Napredak se računa po dovršenim danima kataloga, pa promjena staze ništa ne briše niti ponavlja. Otvoreni korak ostaje na svom zadatku.
2. **Stvarni trenutak.** Koraci s kupcem, novim članom tima, gostom na webinaru ili vlastitim susretom otvaraju se kada to suradnik stvarno ima. Do tada dobiva korak koji može odmah raditi. Pregled 90 koraka prikazuje stazu, sljedećih osam koraka i što čeka.
3. **Ovo već znam i radim.** Iskusni suradnik može potvrditi zajednički korak među prvih 15. Takav korak ne troši dnevni korak i ne računa se kao vježba ni kao primjena.
4. **Zapeo/la sam, javi mojem sponzoru** uz korak i pitanje nakon spremljenog koraka (lako, teško, trebam pomoć). Pomoć i prekretnice javljaju se sponzoru.
5. **Svi sponzori vide napredak izravnih suradnika** na `partner?view=team-progress`, bez obzira na paket. Suradnici su složeni u skupine Trebaju pomoć, Stoje tri dana ili više, Još nisu krenuli, Još nisu otvorili FCC i Napreduju, s gumbom za WhatsApp i gotovom porukom. Sadržaj poruka i bilješki se ne prikazuje. Na stranici Danas sponzor vidi koliko suradnika danas treba njegovu poruku. Ponedjeljkom od 8 sati stiže tjedni sažetak.
6. **Prekretnice** 7, 15, 30, 60 i 90 koraka te prvi osobni CC i prva 4CC aktivnost (samo tekući ili prošli mjesec). Suradnik dobiva čestitku i bira smije li se uspjeh spomenuti. Bez potvrde se ne spominje.
7. **Moj ritam** nakon 90 koraka: četiri tjedne navike.
8. **Administracija** (`partner/admin`) ima pregled Moj put: kako ga tim koristi, s tokom suradnika, stazama, povratnim informacijama, koracima na kojima ljudi zastaju i prekretnicama koje se smiju spomenuti.
9. **Neutralan jezik.** Upute, završne rečenice i primjeri poruka u svih 90 koraka više ne koriste muški rod. Promjena je u sloju revizije (`neutral` u `fcc90.review-20260930.json`), pa dovršeni koraci zadržavaju svoj tekst. Slovenski i srpski prijevodi su također neutralni, a ostali jezici zadržavaju postojeći prijevod. Za crnogorski i poljski, koje trenutačno nitko ne koristi, dio rečenica ostaje u muškom rodu.
10. **Obavijesti sponzoru** spremaju se na hrvatskom i prikazuju na jeziku osobe koja ih čita.
11. **Članak pomoći** Danas i Moj put ima novi odjeljak Tvoja staza i pomoć na 13 jezika. Svi novi tekstovi prevedeni su na 12 jezika.
12. **Novi test** `scripts/test_journey_tracks.php` dodan je u deploy workflow.
13. **Nakon objave** nije potrebna ručna radnja. Suradnici koji su već prošli zajednički dio vidjet će odabir staze pri sljedećem koraku.
14. **Nitko se ne vraća na prvi korak.** Na kopiji baze od 11. listopada program ima 112 stvarnih suradnika, s najviše 15 dovršenih koraka. Svi zadržavaju napredak. Njih 79 nastavlja istim korakom kao prije, 26 zadržava već otvoreni korak, a sedmero dobiva drugi nedovršeni zajednički korak, jer javni video dolazi nakon prve preporuke, a korak s gostom čeka prijavu gosta.
15. **Jasna poruka o pristupu.** Umjesto općenite poruke Provjerimo pristup tvom koraku, suradnik vidi stvarni razlog i jednu radnju: pogledaj uvod, upiši Forever ID na Mojoj kartici, riješi Forever ID koji koriste dva računa ili se javi podršci (`themes/altum/views/partner/journey-access.php`). Na kopiji baze to se odnosi na jednog od 707 odobrenih suradnika.

## 5f. Konačna verzija s dizajnom Živi sloj (lokalno, čeka objavu)

1. Korisnik je 12. listopada 2026. odlučio da novi dizajn s `localhost:8100` ide na produkciju zajedno s Pozivnicom u tim i Mojim putem. Grana `claude/design-zivi-sloj-2026-10-12` (commit `5a410d3`) spojena je u `claude/live-baseline-2026-10-11` (commit `2e06fd5`). Preklapali su se samo dodani prijevodi, a zadržane su obje strane.
2. Živi sloj je zadani izgled (`app/helpers/fcc_ui_theme.php`). Ako zatreba povratak, `FCC_UI_THEME` = `classic` u `fcc-partner-runtime.php` na poslužitelju vraća prijašnji izgled bez nove objave koda.
3. Javne kartice suradnika ne učitavaju novi izgled. Njihova nadogradnja ostaje dobrovoljna, prema planu u FCC-Design `docs/DIZAJN_ZIVI_SLOJ_2026-10-12.md`.
4. Provjereno na 8099: PHP lint za 1.878 datoteka, 15 testova, 22 javne, partnerske i administratorske stranice u novom izgledu bez greške, dvije kartice suradnika u starom izgledu, nove stranice Mojeg puta na računalu i mobitelu.
5. Font DM Sans spremljen je na vlastiti poslužitelj (`themes/altum/assets/fonts/dm-sans`, latinica i proširena latinica, 94 KB, licenca SIL OFL u `OFL.txt`). Preglednici posjetitelja više ne kontaktiraju Google Fonts. Provjereno u pregledniku: oba dijela fonta učitavaju se lokalno, s hrvatskim slovima, bez ijednog zahtjeva prema Googleu.
6. **Fotografija kontakt osobe.** Na stranicama proizvoda okvir Tvoja osoba za ovaj proizvod prikazuje fotografiju koja je već javna na kartici preko koje je posjetitelj stigao (blok avatara ili zaglavlja). Ako ta kartica nema fotografiju, uzima se fotografija s glavne kartice iste osobe, a bez fotografije ostaje početno slovo (`fcc_market_contact_avatar` u `app/helpers/fcc_market_policy.php`).
7. **Jedno mjesto za rad.** Korisnik je 12. listopada 2026. odlučio da se sve uređuje i završava samo na http://localhost:8099. Sav rad iz razgovora DesignMotionHQ analiza i primjena (FCC-Design, commitovi do `5a410d3`) je u ovoj grani. Iz baze 8100 prenesen je lokalni testni administrator (korisnik 1016) s testnom karticom `lokalni-admin-test` (kartica 2806 u 8099), pa isti pristupni podaci iz privatne datoteke `LOKALNI_TESTNI_RACUN.txt` u Claude radnoj mapi rade na 8099. U lokalnu postavku 8099 dodan je `FCC_LOCAL_COUNTRY` = `HR`, pa i lokalna kopija prepoznaje zemlju na stranicama proizvoda. Plan dizajna je u `docs/DIZAJN_ZIVI_SLOJ_2026-10-12.md`. Runtime 8100 je zaustavljen bez brisanja podataka, a njegove brave su oslobođene.

## 5g. Nova FCC kartica (lokalno, čeka objavu)

1. **Odluke korisnika od 12. listopada 2026.** Novi suradnici dobivaju novu karticu bez izbora, u stilu Aloe. Obrasci za telefon i e-mail više se ne nude. Postojeći suradnici sami biraju novu ili staru karticu i mogu se vratiti. PRO suradnici biraju između više stilova. Svi blokovi ostaju naknadno uredivi.
2. **Kako radi.** Nova kartica je drugačiji prikaz istih blokova. Oznaka `fcc_card` = `v2` i stil `fcc_card_theme` spremaju se u postavke kartice, uz povijest promjena (`fcc_card_history`). Prebacivanje nikad ne briše ni ne mijenja blokove. Adresa, NFC, QR kod i preporuka ostaju isti. Logika je u `app/helpers/fcc_card.php`, izgled u `themes/altum/assets/css/fcc-card.css`, a učitava se samo za nove kartice (`themes/altum/views/l/biolink_wrapper.php`).
3. **Stilovi.** Aloe (svijetli, za sve), Smaragd, Zlato, Pijesak i Ponoć (PRO). Bez PRO paketa kartica uvijek prikazuje Aloe, a odabrani stil se vraća kada se PRO vrati. Svaki stil sam pazi na čitljivost i prepisuje boje iz starog uređivača.
4. **Nova registracija.** `User::create_links` nakon izrade kartice poziva `fcc_card_prepare_new`: uklanja zajednički timski video, savjet za početni zaslon i obrasce za telefon i e-mail, dodaje nedostajuće preporučene blokove, slaže redoslijed (profil, trgovina, WhatsApp i Spremi kontakt, ostalo, društvene mreže, poslovna suradnja) i uključuje novu karticu u stilu Aloe.
5. **Osnovni blokovi na svakom paketu.** Na novoj kartici Beginner može prikazati i dodati fotografiju, ime, tekst o sebi, društvene mreže, Forever trgovinu, poslovnu suradnju, popust, Spremi kontakt, WhatsApp i razdjelnik (`FCC_CARD_CORE_BLOCKS`). Isto pravilo poštuju javni prikaz, izbornik blokova, `BiolinkBlockAjax` i `User::sync_whatsapp_biolink_blocks_with_plan`. Blokove koje je promjena paketa automatski isključila nova kartica ponovno uključuje.
6. **Ispravci za sve kartice, i stare.** Spremi kontakt uvijek sprema kontakt vlasnika kartice (dvije kartice spremale su tuđi kontakt), a bez vlastitog vCarda gumb se ne prikazuje. Tvornički natpisi Forever gumba prikazuju se na jeziku kartice. Redak o zemlji prikazuje se jednom i čitljiv je. Podnožje je na jeziku kartice umjesto engleskog. Na mobitelu prekidač bloka s dugom adresom više ne izlazi iz okvira uređivača.
7. **Nova kartica dodatno.** Jedan redak „Ponuda za: Hrvatska · Promijeni zemlju“, AI savjetnik kao gumb „Pitaj savjetnika“ unutar kartice umjesto plutajuće sove, WhatsApp uzima broj iz profila kada je u bloku prazan, natpisi pisani velikim slovima prikazuju se normalno, gumbi WhatsApp i Spremi kontakt stoje jedan uz drugi, a podnožje kaže da je riječ o neovisnom suradniku (Neovisni Forever Business Partner).
8. **Uređivač.** U kartici Izgled je ploča Izgled kartice: nova ili stara kartica, stil i gumb Dodaj preporučene blokove (`views/partner/card-design-panel.php`, radnje `card_design` i `card_recommended` u `Partner.php`). Za novu karticu stare postavke teme i boja su skrivene jer se ne primjenjuju.
9. **Vodič kroz blokove.** Dodaj blok najprije prikazuje jednostavan vodič u tri skupine (Forever i preporuka, Kontakt, O meni i sadržaj) za 17 blokova, s rečenicom čemu blok služi i kako ga iskoristiti te oznakom PRO gdje treba (`views/link/settings/fcc_card_block_guide.php`). Ostali blokovi su pod Svi ostali blokovi. Isto objašnjenje stoji na vrhu obrasca svakog bloka. Obrasci za telefon i e-mail ne mogu se više dodati, ali postojeći rade na staroj kartici i prikupljeni podaci ostaju.
10. **Prijevodi.** 80 novih tekstova na 12 jezika, uz popunjene praznine za redak o zemlji i natpis poslovne suradnje na francuskom, albanskom, srpskom i crnogorskom.
11. **Provjereno na 8099.** PHP lint za 25 izmijenjenih datoteka i 16 testova prije objave, uključujući novi `scripts/test_fcc_card.php`. Privremena kartica s jednim blokom svake od 45 vrsta korištenih na karticama prikazana je u staroj i novoj verziji bez ijednog PHP upozorenja, a provjera kontrasta u stilovima Aloe i Ponoć nije našla nečitljiv tekst. Kartica je uklonjena nakon provjere. Sintetički novi član (korisnik 1017) prošao je stvarni postupak izrade kartice i dobio novu karticu Aloe. Forever trgovina i poslovna suradnja vode na foreverliving.com s Forever ID brojem vlasnika (`fboId`). Prebacivanje na staru karticu i natrag provjereno je u uređivaču sa sintetičkim Beginner računom, kao i dodavanje teksta o sebi na Beginner paketu.
12. **Lokalni testni podaci.** Kartice `claude-test-beginner` (2804, Aloe) i `claude-test-pro` (2805, Smaragd) su nove kartice, `lokalni-admin-test` (2806) ostaje stara za usporedbu, a `hvrdzpcnxs` (2815) je kartica sintetičkog novog člana. Sintetički računi 1014, 1015 i 1017 imaju testni vCard i testni broj koji nije stvaran.
13. **Uvodni tekst.** Prema odluci korisnika uvod ispod imena glasi „Neovisni Forever Business Partner“ (oznaka) i „Za više informacija o Forever proizvodima ili poslovnoj suradnji slobodno me kontaktirajte.“ Na novoj kartici prikazuje se u obojenom okviru, a prvi podebljani red uvoda postaje oznaka ispod imena. Prijevodi postoje na 12 jezika.

## 5h. Novi jednostavni uređivač kartice (lokalno, čeka objavu)

1. **Gdje je.** `partner/card/{id}`. Moje kartice → Uredi karticu otvara novi uređivač, a Napredni uređivač (dosadašnji, `link/{id}`) ostaje dostupan iz Mojih kartica, iz zaglavlja novog uređivača i za svaki blok koji novi uređivač ne uređuje. Iz naprednog uređivača poveznica Jednostavni uređivač vraća na novi.
2. **Kako izgleda.** Tri koraka: Profil (fotografija, ime, kratki opis ispod imena, tekst o meni), Sadržaj (svi dijelovi kartice redom, s pomicanjem gore i dolje, prekidačem prikaza, uređivanjem i uklanjanjem te knjižnicom od 15 blokova) i Izgled (nova ili stara kartica, stil i preporučeni blokovi). Živi pregled je stvarna kartica u načinu pregleda, bez brojanja posjeta.
3. **Mobitel.** Kartica zauzima zaslon, koraci se otvaraju kao donji panel, a dodir na dio kartice u pregledu otvara donji panel s dva do četiri polja tog bloka. Na računalu su koraci lijevo, a kartica u okviru telefona desno.
4. **Spremanje.** Svaka promjena je jedan zahtjev s provjerom sesije (`Partner::card_editor`, logika u `app/helpers/fcc_card_editor.php`). Mijenjaju se samo polja koja obrazac prikazuje, pa ostale postavke iz naprednog uređivača ostaju. Uređuju se samo vlastite kartice i blokovi; tuđa kartica vraća 404. Forever gumbi i dalje sami slažu adresu s preporukom, proizvod vodi na Akademiju s preporukom, a obična Forever poveznica na `/forever-shop` s preporukom.
5. **Pravila.** Paket i nova kartica određuju koje blokove je moguće dodati (isto pravilo kao javni prikaz i `BiolinkBlockAjax`). Ograničenje broja blokova po paketu, jedan AI savjetnik po kartici, provjera fotografija (JPG, PNG, WEBP, GIF, AVIF, stvarna slika, veličina prema postavkama) i poveznica (samo http i https, zabranjene domene, Safe Browsing kao u naprednom uređivaču). Uklanjanje bloka traži potvrdu.
6. **Prijevodi.** 108 novih tekstova na 12 jezika.
7. **Provjereno na 8099.** Lint izmijenjenih datoteka i 17 testova prije objave, uključujući novi `scripts/test_fcc_card_editor.php`. Sa sintetičkim Beginner računom u pregledniku: dodir na gumb u pregledu, promjena natpisa, profil s fotografijom, dodavanje, pomicanje, skrivanje i uklanjanje bloka, knjižnica i PRO zaključavanje. Sa sintetičkim PRO računom preko zahtjeva: proizvod, YouTube, Forever poveznica, česta pitanja i iskustvo kupca prikazuju se na kartici, uz ispravne preporuke, a zatim su uklonjeni. Odbijeni su tuđi blok, neispravna poveznica, lažna slika, istekla sesija, PRO blok i PRO stil na Beginner paketu.

## 5i. WhatsApp broj, pregled stila i napredno uređivanje nove kartice (lokalno, čeka objavu)

1. **WhatsApp broj iz registracije.** `fc_get_user_default_whatsapp_phone` sada vraća broj u obliku koji WhatsApp traži (pozivni broj zemlje i broj bez početne nule, samo znamenke). Pretvorba je u `app/helpers/links.php` (`fc_normalize_whatsapp_phone`). Zemlju za broj zapisan bez pozivnog broja određuje redom: zemlja odabrana pri registraciji, zemlja iz adrese, zemlja prijave i na kraju Hrvatska. Broj se prihvaća samo ako izgleda kao mobilni broj te zemlje, pa poruka nikad ne odlazi strancu u drugoj zemlji. Na kopiji baze ispravan broj sada ima 681 od 712 suradnika (prije 294); ostali su nepotpuni ili pogrešno upisani i ostaju prazni.
2. Broj se upisuje sam pri registraciji (WhatsApp blok predloška), u obrascu WhatsApp bloka u uređivaču (prikazan kao +385 91 123 4567, sprema se kao 385911234567) i na kartici kada je u bloku prazan. Upisani broj u bilo kojem obliku pretvara se pri spremanju; neispravan broj vraća jasnu poruku. Na svim karticama, i starim, broj u gumbu prikazuje se ispravno pretvoren.
3. **Pregled stila prije spremanja.** U koraku Izgled svaka promjena vrste kartice, stila, boja ili detalja odmah se vidi u pregledu, a Spremi izgled je potvrđuje za posjetitelje. Pregled koristi parametre `fcc_card`, `fcc_theme` i `fcc_style`, koji djeluju samo uz ključ pregleda vlasnika (`fcc_card_preview_choice`), pa posjetitelji nikad ne vide nespremljen izgled. Isto vrijedi za bočni pregled u klasičnom uređivaču.
4. **Napredno: boje i detalji.** Unutar odabranog stila: glavna boja i pozadina (PRO, kao i dodatni stilovi), oblik gumba, izgled ostalih gumba, font (moderni, klasični, jednostavni), veličina teksta, oblik fotografije i sjaj pozadine. Sprema se u postavke kartice (`fcc_card_style`). Boje teksta, površina i rubova izračunavaju se iz odabrane pozadine, pa tekst ostaje čitljiv; provjera kontrasta na svih 45 vrsta blokova s tamnom vlastitom pozadinom nije našla nečitljiv tekst.
5. **Izgled bloka.** U obrascu bloka, pod Izgled bloka: za gumbe izgled (kao na kartici, istaknuti, puni, obrub, mekani) i ikona; za naslov veličina i poravnanje; za tekst okvir i poravnanje; za sliku i video rubovi; za društvene mreže izgled ikona; za razdjelnik ikona. Sprema se u blok (`fcc_style`, `icon`) i crta preko atributa `data-fcc-*` na novoj kartici.
6. **Klasični uređivač.** Za nove kartice više se ne nudi kao napredni uređivač. U Mojim karticama poveznica Klasični uređivač prikazuje se samo za stare kartice, a klasični uređivač za novu karticu jasno upućuje na novi uređivač. Ostaje za stare kartice, za rijetke blokove koje novi uređivač ne uređuje i za postavke kartice (adresa, tražilice).
7. **Provjereno.** Lint izmijenjenih datoteka, 17 testova prije objave (prošireni testovi za pretvorbu broja, boje, izgled blokova i pregled), 45 vrsta blokova u staroj i novoj kartici bez PHP upozorenja, pregled stila i spremanje u pregledniku sa sintetičkim Beginner računom te vlastite boje i izgled blokova sa sintetičkim PRO računom.
8. **Lokalni testni brojevi.** Sintetički računi 1014, 1015, 1016 i 1017 imaju izmišljene brojeve iz britanskog raspona 07700 900xxx, koji je rezerviran za filmove i primjere i ne pripada stvarnim osobama.
9. **Podnožje.** Na novoj kartici: „Neovisni Forever Business Partner“ i poveznica Forever Card Club (napomenu o službenoj stranici korisnik je 12. listopada 2026. zatražio ukloniti s kartica), na staroj: „Kartica neovisnog Forever Business Partnera · Forever Card Club“. Isti naziv kao u uvodu ispod imena, na 12 jezika.
10. **Ispravci veličine teksta i sjaja.** Stranica kartice postavlja osnovnu veličinu fonta s prioritetom (`biolink_wrapper.php`), pa je Veća sada postavljena upravo tamo (18 px umjesto 16 px). Sjaj pozadine ima tri jasne razine: blagi (zadano, malo izraženiji nego prije), izraženi i bez sjaja.
11. **Stara kartica u novom uređivaču.** Za staru karticu obrazac svakog bloka ima dio Boje i oblik, s opcijama iz klasičnog uređivača: boja teksta, boja pozadine (ili bez pozadine), oblik, debljina i boja ruba te sjena i njezina boja za gumbe; boja teksta, pozadina i poravnanje za tekst; boja ikona i pozadine za društvene mreže. Boja koja nije promijenjena zadržava spremljeni oblik, primjerice prozirnost. Na novoj kartici taj dio se ne prikazuje, nego Izgled bloka. Tako suradnici koji zadrže staru karticu nastavljaju je uređivati u novom uređivaču, a stari uređivač se može postupno ugasiti.

## 5j. Napomena, instalacija, Coach, prijelaz na Aloe i push (lokalno, čeka objavu)

1. **Napomena o službenoj stranici.** Korisnik je 12. listopada 2026. zatražio da se rečenica da FCC ili kartica nije službena stranica Forever Living Products ukloni svugdje. U kodu je uklonjena s kartice, naslovnice, stranica O nama, `llms.txt` iz aplikacije, javne Pozivnice u tim, pravnih stranica (podnožje) i iz `fcc_public_design.php`. Rečenica da je FCC neovisan sustav i podaci o kupnji u službenom web shopu ostaju.
2. **Napomena u sadržaju baze.** Gumb Ažuriranje sadržaja: napomena o službenoj stranici u `partner/admin#content-migration-notice` uklanja je iz 1.196 članaka i 65 stranica na 13 jezika (`app/config/fcc_content_notice_2026_10_12.json`). Pitanje Je li FCC službeni Forever alat? na stranicama osnivača uklanja se cijelo, a dulje rečenice zadržavaju dio o neovisnosti. Na kopiji baze: 1.261 izmjena za 3 s, povrat za 1,5 s vraća identičan sadržaj (kontrolni zbroj), nakon primjene nijedna rečenica napomene ne ostaje.
3. **Nije mijenjano:** pravni tekst `app/config/legal/2026-09-26.1.hr.php` (odjeljak `neovisnost` s izjavom o žigovima) je verzionirani dokument; promjena traži novu verziju uvjeta. Statična `llms.txt` u korijenu poslužitelja nije dio automatske objave; ispravljena inačica je u korijenu repozitorija (`llms.txt`) za ručni prijenos.
4. **Razdjelnik.** Ikone su zamijenjene znakovima povezanima s Foreverom i radom (aloe list, klica, kapljica, wellness, srce, briga, sunce, zvjezdica, dijamant, točka, tri točke), uz izbor crte (puna, isprekidana, točkasta) i razmaka. Spremanje sada jasno javlja zauzetost, sporu vezu i isteklu sesiju (`fcc-card-editor.js`).
5. **Savjetnik na kartici.** Predloženi upiti na vrhu sada se upisuju u polje pitanja (delegirani klik prije ostale inicijalizacije u `fcc_chat_extreme_popup.php`).
6. **Stranica za instalaciju** `partner/install` dobila je izgled Živi sloj: jedna jasna radnja prema uređaju (Android jednim dodirom, koraci za Safari s ikonama, upozorenje unutar WhatsAppa ili Instagrama, QR kod na računalu), četiri koristi, korake nakon instalacije i česta pitanja. Service worker se registrira s ishodišta stranice, a neuspjeh se više ne prikazuje kao greška.
7. **Poticaj za instalaciju.** Na stranici Danas suradnik koji FCC koristi u pregledniku mobitela vidi karticu Dodaj FCC na početni zaslon (`fcc-partner-install-nudge.js`). Na Androidu instalira jednim dodirom, inače otvara upute. Kasnije je skriva 14 dana, a na iPhoneu se ne prikazuje uz postojeći poziv za obavijesti.
8. **Coach.** Popis mogućnosti i upute za sučelje opisuju novi uređivač, stilove, Napredno, Izgled bloka, razdjelnik, WhatsApp broj, staru karticu, instalaciju, staze Mojeg puta i Napredak mojih suradnika (`fcc_coach_mentor.php`, `fcc_coach_continuity.php`, verzija `mentor-2026-10-12.card2`). Članak pomoći Uredi i provjeri svoju karticu prepisan je za novi uređivač na 13 jezika.
9. **Prijelaz netaknutih kartica na Aloe.** Gumb Nova kartica Aloe za netaknute kartice u `partner/admin#content-migration-aloe` prebacuje na novu karticu one kartice aktivnih suradnika koje nitko nije uređivao (kartica i svi blokovi bez datuma izmjene) i koje imaju samo osnovne ili tvorničke blokove. Blokovi, redoslijed, adresa, QR kod i preporuka ostaju; osnovni blokovi koje je paket isključio ponovno se uključuju. Vlasnik dobiva obavijest kako se vratiti na staru karticu. Na kopiji baze to je 496 kartica (445 Beginner, 51 PRO) i 6 blokova. Povrat vraća samo kartice koje vlasnik nakon toga nije mijenjao i povlači nepročitane obavijesti. Provjereno kroz sučelje i iz naredbenog retka: primjena, povrat s identičnim kontrolnim zbrojem i kartica koju je vlasnik u međuvremenu promijenio ostaje. Lokalno je prijelaz vraćen i čeka gumb.
10. **Stanje migracija** u administraciji sprema se na sat vremena (prvo učitavanje nakon objave traje oko 10 s), a primjena uvijek računa svjež plan.
11. **Push obavijesti.** Na zaključanom zaslonu push ne nosi osobne podatke nego vrstu događaja. Za događaje ovog izdanja (suradnik traži pomoć, tjedni pregled tima, prekretnica, početak Mojeg puta, registracija i narudžba preko Pozivnice u tim, nova kartica) poslužitelj sada šalje i oznaku `detail` (`fcc_push_detail` u `fcc_push_delivery.php`). Sadašnji service worker je zanemaruje i radi kao prije. Nadograđeni `fcc-partner-sw.js` (verzija `fcc-partner-shell-v11`, u korijenu repozitorija) prikazuje točan tekst na 13 jezika; produkcijska datoteka bila je identična lokalnoj (SHA-256 provjeren 12. listopada), a datoteka nije dio automatske objave.
12. **Testovi.** `scripts/test_invite_content_migration.php` provjerava uklanjanje napomene na primjerima i pravila prijelaza na Aloe, a `scripts/test_fcc_card_editor.php` stranicu za instalaciju, poticaj, znanje Coacha, push oznake, nadograđeni service worker i prijevode. Svih 17 testova prolazi, lint 34 PHP datoteke bez greške, 39 stranica za posjetitelja, Beginner, PRO i administratora bez PHP greške i upozorenja.
13. Koraci objave i povrata su u `docs/OBJAVA_2026-10-12.md`.

## 5b. Već provedeno na produkciji prije ove dorade

1. Svi aktivni suradnici dobivaju sadržaj programa v8. Ciklusi označeni s v6 ili v7 otvaraju sljedeći korak iz v8 kataloga, pa migracija podataka nije potrebna.
2. Novo sučelje `/partner` zadano je svima od 1. listopada 2026. (`fcc_partner_rollout_control.all_enabled = 1`).
3. Coach za Beginner koristi `gpt-6-luna`, a za PRO `gpt-6-sol`. Meka mjesečna granica je 8 USD za Coach i 10 USD za savjetnika na karticama, a nakon nje sustav prelazi na Lunu. Stalna pravila odvojena su od konteksta radi predmemorije.
4. Coach ima izbor brzih radnji, a glavni AI savjetnik ima ocjene odgovora u `fcc_ai_message_feedback`.

## 6. Otvorene stavke

1. **GeoLite baze su stare.** `app/includes/GeoLite2-City.mmdb` i `GeoLite2-Country.mmdb` su iz travnja 2026. Kod koristi lokalnu bazu samo ako je mlađa od 60 dana. Tjedni workflow je pripremljen (odjeljak 5d) i čeka da korisnik otvori besplatni MaxMind račun i doda dvije GitHub tajne.
2. **Deploy workflow koristi običan FTP bez šifriranja.** Treba prijeći na `protocol: ftps` s imenom poslužitelja koje odgovara TLS certifikatu. Za to treba ažurirati GitHub Secret `FTP_SERVER`.
3. **Ručni workflow `sync-live-to-github.yml` isključuje provjeru certifikata.** Nakon objedinjavanja ga treba ukinuti ili ispraviti.
4. **Smeće na produkciji.** `themes/__MACOSX` ima 2099 datoteka iz raspakirane arhive. Uz to postoje `.ftp-deploy-sync-state*` datoteke izvan uobičajenog stanja workflowa. Treba ih ukloniti pri kontroliranoj objavi.
5. **`vendor/phpmailer/phpmailer/src/AcmHandler.php`** je AltumCodeov mehanizam praćenja licence koji se aktivira samo pri instalaciji ili ažuriranju. Nije zlonamjeran i ostaje.
6. **Produkcijski ključ `FCC_OPS_READONLY_KEY`** pojavio se u zapisniku Claude razgovora 11. listopada. Preporučena je zamjena ključa.
7. **120-dnevni plan** zamijenjen je programom Moj put prema odluci korisnika od 12. listopada 2026. (odjeljak 5d). Ostaje samo provjera videa u Stjepanovom vodiču.
8. **Urednik sadržaja edukacije** nije izrađen. Sadržaj od oko 128 MB i dalje je u PHP konfiguraciji, a svaka izmjena traži objavu koda. To je zaseban veći projekt.
9. **Najveće datoteke** `fcc_ai.php` (30.574 retka), `vip_funnel.php` i `AiPlan.php` nisu dijeljene. Razdvajati ih postupno, pri sljedećim doradama tih područja.

## 6a. Lokalno testiranje

1. Lokalni testni računi: administrator, Beginner i PRO s 4CC (sintetički Forever ID 999000000001 i sintetički CC zapis), u privatnoj datoteci `FCC-runtime/LOCAL_TEST_ACCOUNT.txt`.
1. Okruženje se pokreće iz `ClaudeWorkspaces/FCC-runtime` naredbom `docker compose up -d`, a otvara na `http://localhost:8099`.
2. Može se prijaviti stvarnim produkcijskim podacima, jer je baza kopija. Lokalni testni računi, administratorski i Beginner, nalaze se u privatnoj datoteci `FCC-runtime/LOCAL_TEST_ACCOUNT.txt`.
3. Provjereno 11. listopada: PHP lint za 1.862 datoteke, 14 testova koji se pokreću prije objave i 42 stranice za PRO i Beginner račun, uključujući engleski i njemački. Nije bilo PHP grešaka ni upozorenja.
4. Provjereno 12. listopada: PHP lint za 1.867 datoteka i 14 testova prije objave bez greške. Migracija sadržaja na kopiji baze: 145 izmjena primijenjeno, vraćeno i ponovno primijenjeno, nakon čega nema spominjanja 120 dana. Pozivnica u tim provjerena je sa sintetičkim Beginner i PRO računima: kratka poveznica, QR kod, javna stranica na hrvatskom, engleskom, slovenskom, mađarskom i njemačkom, osobni video, obavijest pri otvaranju narudžbe i bilježenje registracije. Lokalni runtime nema tajni ključ poslovnog modula, pa je klik na narudžbu provjeren iz naredbenog retka.
5. Provjereno 12. listopada za Moj put: PHP lint za sve izmijenjene datoteke i 15 testova prije objave bez greške. Na svih 115 stvarnih ciklusa novi raspored daje 0 grešaka i nijedan ponovljeni korak. Sintetički Beginner prošao je zajednički dio, korake označene kao poznate, odabir staze, Zapeo/la sam i povratnu informaciju. Sponzorski pregled, obavijesti na hrvatskom, slovenskom, srpskom i engleskom, administratorski pregled i članak pomoći provjereni su preko HTTP-a, a stranice staze i sponzora i na širini mobitela.

## 7. Put objave

Za izdanje od 12. listopada 2026. redoslijed koraka, ručne datoteke i povrat opisani su u `docs/OBJAVA_2026-10-12.md`.

1. Jedini put objave je merge u `main`. Workflow tada pokreće lint i testove te objavljuje `app/` i `themes/`.
2. Prije objave treba uzeti deploy lock `forevercard.club` preko SharedAI koordinatora. Zatim treba provjeriti da produkcija nije mijenjana nakon zadnjeg snimka i pripremiti povrat.
3. Ciljane ručne objave mimo `main` više se ne koriste, osim u hitnom slučaju koji se odmah vraća u Git.
