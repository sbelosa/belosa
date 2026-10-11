# Stanje FCC sustava

Ovo je jedini živi dokument o tome što je stvarno na produkciji i kako se objavljuje. Ažurira se pri svakoj objavi. Datirani izvještaji u `docs/partner` služe kao povijest.

Zadnja provjera: 11. listopada 2026., Claude, grana `claude/live-baseline-2026-10-11`.

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

## 5a. Pojednostavljenje za suradnike (lokalno, čeka objavu)

1. **Više** ima četiri skupine: Učenje, Susreti i događaji, Rezultati i tim te Paket i postavke. Tehnički alati su u zatvorenom odjeljku Napredni alati: poveznice, QR kodovi, funneli, statistika, pikseli, domene, projekti, uvodne stranice i kanali obavijesti. Nijedan alat nije uklonjen, a poveznica `partner/more#tools` otvara odjeljak.
2. **Danas** počinje današnjim korakom Mojeg puta. Iznad njega su samo stvarni zadaci koji čekaju, poslovni upiti i dospjela timska javljanja. Događaj, mjesečni bodovi i sponzor slijede ispod. Vlastita promjena CC bodova više se ne prikazuje dvaput.
3. **Zapelo mi je** je u izborniku pomoći, oznaka `?`. Otvara podršku s naslovom stranice, kategorijom i dva pitanja: što sam htio napraviti i gdje je zapelo. Prijave idu u postojeće `feedback_tickets`.
4. Moj put više ne spominje stari program od 60 koraka.
5. **Akademija proizvoda** na početku nudi 10 proizvoda koje suradnici najčešće dijele. Popis je u `app/config/fcc_product_education_starter.php`, s opisom načina odabira, a treba ga povremeno osvježiti novim podacima o klikovima.
6. Akademija i usporedba paketa unutar radnog prostora koriste zajedničku paletu `--fp-*` iz `fcc-partner-premium.css`.
7. Svi novi tekstovi prevedeni su na 12 jezika u `app/config/locales`.

## 5c. Veliki update prema CC bodovima (lokalno, čeka objavu)

1. **Javni popisi prate CC iz FLP360.** Istaknute aplikacije traže PRO i najmanje 1 osobni CC, a Preporučeni sponzori PRO i potvrđenu 4CC aktivnost. Oba uvjeta vrijede za tekući ili prošli kalendarski mjesec po vremenu Europe/Zagreb. Jedini izvor pravila je `app/helpers/fcc_cc_visibility.php`. Iznosi CC bodova nigdje se javno ne prikazuju. Na kopiji podataka popis Istaknutih aplikacija narastao je s 14 na 46, a Preporučenih sponzora s 5 na oko 25.
2. **Klikovi su samo statistika.** FCC Rezultati, uređivač kartice, statistika i Coach više ne spominju pragove 15+ i 50+. Emailovi o prelasku praga klikova su ugašeni.
3. **AI analiza aplikacije i tjedni AI plan su ukinuti.** Rute `ai-plan` i `ai-app-review` preusmjeravaju suradnike na Danas i Moju karticu, a administrator zadržava pristup povijesti. Usporedba paketa na 13 jezika opisuje novu PRO vrijednost.
4. **4CC WhatsApp grupa.** Svaki suradnik s 4CC aktivnošću, s PRO paketom ili bez njega, vidi poziv na stranici Danas, s gumbima Pridruži se grupi i Već sam član. PRO je uvjet samo za javni prikaz. Članstvo vrijedi dok je suradnik bez prekida kvalificiran. Poveznica grupe ugrađena je u kod (`FCC_CCV_GROUP_DEFAULT_URL`), a u administraciji (`partner/admin#cc4-group`) može se zamijeniti ili isključiti praznim poljem. Tamo su i popis za uklanjanje te popis kvalificiranih koji se još nisu pridružili.
5. **Podsjetnici za događaje** stižu samo suradnicima koji su potvrdili dolazak ili imaju prijavljene goste. Na kartici događaja na stranici Danas dodan je gumb Dolazim. Na kopiji podataka od 3.000 podsjetnika na čekanju ostao bi 21.
6. **Povučene timske obavijesti** više se ne prikazuju u popisu obavijesti.
7. **Prvi tjedan Mojeg puta**, koraci 1 do 7, otvoren je i bez upisa od 0,33 CC. Nakon 7. koraka prikazuje se jasna poruka da se program nastavlja nakon prve narudžbe.
8. **Pravila su javna.** Prikazana su na stranicama Istaknute aplikacije i Preporučeni sponzori, a novi članak „Javni popisi, CC bodovi i 4CC grupa” u Uputama za FCC postoji na 13 jezika.
9. **Novi test** `scripts/test_cc_visibility.php` dodan je u deploy workflow.

## 5b. Već provedeno na produkciji prije ove dorade

1. Svi aktivni suradnici dobivaju sadržaj programa v8. Ciklusi označeni s v6 ili v7 otvaraju sljedeći korak iz v8 kataloga, pa migracija podataka nije potrebna.
2. Novo sučelje `/partner` zadano je svima od 1. listopada 2026. (`fcc_partner_rollout_control.all_enabled = 1`).
3. Coach za Beginner koristi `gpt-6-luna`, a za PRO `gpt-6-sol`. Meka mjesečna granica je 8 USD za Coach i 10 USD za savjetnika na karticama, a nakon nje sustav prelazi na Lunu. Stalna pravila odvojena su od konteksta radi predmemorije.
4. Coach ima izbor brzih radnji, a glavni AI savjetnik ima ocjene odgovora u `fcc_ai_message_feedback`.

## 6. Otvorene stavke

1. **GeoLite baze su stare.** `app/includes/GeoLite2-City.mmdb` i `GeoLite2-Country.mmdb` su iz travnja 2026. Kod koristi lokalnu bazu samo ako je mlađa od 60 dana, pa svaki novi posjetitelj čeka vanjski geo servis. Potrebno je preuzeti svježe MaxMind datoteke i uvesti njihovo redovito osvježavanje.
2. **Deploy workflow koristi običan FTP bez šifriranja.** Treba prijeći na `protocol: ftps` s imenom poslužitelja koje odgovara TLS certifikatu. Za to treba ažurirati GitHub Secret `FTP_SERVER`.
3. **Ručni workflow `sync-live-to-github.yml` isključuje provjeru certifikata.** Nakon objedinjavanja ga treba ukinuti ili ispraviti.
4. **Smeće na produkciji.** `themes/__MACOSX` ima 2099 datoteka iz raspakirane arhive. Uz to postoje `.ftp-deploy-sync-state*` datoteke izvan uobičajenog stanja workflowa. Treba ih ukloniti pri kontroliranoj objavi.
5. **`vendor/phpmailer/phpmailer/src/AcmHandler.php`** je AltumCodeov mehanizam praćenja licence koji se aktivira samo pri instalaciji ili ažuriranju. Nije zlonamjeran i ostaje.
6. **Produkcijski ključ `FCC_OPS_READONLY_KEY`** pojavio se u zapisniku Claude razgovora 11. listopada. Preporučena je zamjena ključa.
7. **Stjepanov osobni vodič** u `app/helpers/vip_funnel_stjepan_guide.php` nudi 120-dnevni početni plan i povezan je s videom `120_dnevni_plan`, dok program ima 90 koraka. Tekst nije mijenjan jer bi se razlikovao od videa. Odluka pripada autoru.
8. **Urednik sadržaja edukacije** nije izrađen. Sadržaj od oko 128 MB i dalje je u PHP konfiguraciji, a svaka izmjena traži objavu koda. To je zaseban veći projekt.
9. **Najveće datoteke** `fcc_ai.php` (30.574 retka), `vip_funnel.php` i `AiPlan.php` nisu dijeljene. Razdvajati ih postupno, pri sljedećim doradama tih područja.

## 6a. Lokalno testiranje

1. Lokalni testni računi: administrator, Beginner i PRO s 4CC (sintetički Forever ID 999000000001 i sintetički CC zapis), u privatnoj datoteci `FCC-runtime/LOCAL_TEST_ACCOUNT.txt`.
1. Okruženje se pokreće iz `ClaudeWorkspaces/FCC-runtime` naredbom `docker compose up -d`, a otvara na `http://localhost:8099`.
2. Može se prijaviti stvarnim produkcijskim podacima, jer je baza kopija. Lokalni testni računi, administratorski i Beginner, nalaze se u privatnoj datoteci `FCC-runtime/LOCAL_TEST_ACCOUNT.txt`.
3. Provjereno 11. listopada: PHP lint za 1.862 datoteke, 14 testova koji se pokreću prije objave i 42 stranice za PRO i Beginner račun, uključujući engleski i njemački. Nije bilo PHP grešaka ni upozorenja.

## 7. Put objave

1. Jedini put objave je merge u `main`. Workflow tada pokreće lint i testove te objavljuje `app/` i `themes/`.
2. Prije objave treba uzeti deploy lock `forevercard.club` preko SharedAI koordinatora. Zatim treba provjeriti da produkcija nije mijenjana nakon zadnjeg snimka i pripremiti povrat.
3. Ciljane ručne objave mimo `main` više se ne koriste, osim u hitnom slučaju koji se odmah vraća u Git.
