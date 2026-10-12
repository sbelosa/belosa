# FCC dizajn Živi sloj: lokalna izvedba i kartice suradnika

Datum: 12. listopada 2026. Status: lokalno izvedeno i provjereno na portu 8100. Ništa nije objavljeno na produkciji.

**Stanje nakon spajanja, 12. listopada 2026.:** cijeli rad iz ovog dokumenta spojen je u FCC-Live, granu `claude/live-baseline-2026-10-11`, i jedina lokalna verzija je http://localhost:8099. Živi sloj je zadani izgled, a DM Sans je na vlastitom poslužitelju. Odjeljak 5 opisuje prvotni plan, a važeći put objave je u `docs/STANJE_SUSTAVA.md`, odjeljak 5f. Port 8100 je ugašen, a radna kopija FCC-Design ostaje samo kao povijesna kopija.

## 1. Što je napravljeno lokalno

1. Novi izgled Živi sloj je jedna CSS datoteka, `themes/altum/assets/css/fcc-zivi-sloj.css`. Učitava se nakon svih postojećih FCC stilova, pa ne mijenja nijednu postojeću datoteku stila.
2. Pomoćna datoteka `app/helpers/fcc_ui_theme.php` odlučuje koji se izgled prikazuje. Zadani izgled je stari (`classic`). Novi se uključuje vrijednošću `FCC_UI_THEME` u datoteci `fcc-partner-runtime.php` na poslužitelju.
3. Sloj obuhvaća tri dijela: radni prostor suradnika (Danas, Kontakti, Podijeli, Moja kartica, Više, Moj tim, paket, račun), prijavu i registraciju te FCC-ove vlastite javne stranice, primjerice naslovnicu.
4. Javne kartice suradnika (biolink stranice) koriste vlastiti okvir `l/biolink_wrapper.php`. Taj okvir nije diran i nikada ne učitava novi sloj. Provjereno je na tri stvarne kartice iz lokalne kopije baze, i uz uključen novi izgled.
5. Izmijenjene su samo ove datoteke: `app/helpers/fcc_ui_theme.php` (nova), `themes/altum/assets/css/fcc-zivi-sloj.css` (nova), `app/init.php`, `app/includes/partner_environment.php`, `themes/altum/views/app_wrapper.php`, `themes/altum/views/basic_wrapper.php` i `themes/altum/views/wrapper.php`. Svaki wrapper dobiva jedan red za stil i jednu klasu na `body`.

## 2. Kako testirati

1. Adresa lokalne kopije je `http://localhost:8100`. Baza je zasebna kopija lokalne baze od 12. listopada, bez izlaza na internet, pa se ne šalju e-pošta, plaćanja ni obavijesti.
2. Lokalni testni računi i lozinke nalaze se u `ClaudeWorkspaces/FCC-runtime/LOCAL_TEST_ACCOUNT.txt`. Isti računi postoje i u ovoj kopiji baze.
3. Lokalno je novi izgled uključen kao zadani. Stari izgled za usporedbu otvara se dodavanjem `?fcc_ui=classic` na bilo koju adresu, primjerice `http://localhost:8100/partner?fcc_ui=classic`. Novi se vraća s `?fcc_ui=zivi`. Izbor se pamti u pregledniku.
4. Preporučeni redoslijed provjere: Danas, Kontakti, Podijeli, Moja kartica, Više, Moj paket, Prijava, javna naslovnica `/hr`. Svaki ekran treba pogledati na mobitelu i na računalu.
5. Provjereno na 375 px: devet ekrana radnog prostora nema vodoravnog prelijevanja. Jedina iznimka su skrivene poveznice izbornika na stranici obavijesti, a ista iznimka postoji i u starom izgledu.

## 3. Backup i povrat

1. Kod: grana `claude/design-zivi-sloj-2026-10-12` u radnoj kopiji `ClaudeWorkspaces/FCC-Design`. Polazno stanje je produkcijski commit `300355f`, označen oznakom `backup/prije-dizajna-2026-10-12`. Izmjene su u lokalnom commitu `9e22abf`, koji nije poslan na GitHub.
2. Baza: `ClaudeWorkspaces/FCC-Design-runtime/backup/baza-prije-dizajna-2026-10-12.sql.gz`, SHA-256 `2a49ff03f15590814ef2fee1246181771c4e10dc9a09eb6cf237e6da373e383d`.
3. Potpuni lokalni povrat: `ClaudeWorkspaces/FCC-Design-runtime/vrati-na-backup.sh`. Skripta najprije sprema trenutačni dizajnerski rad u zasebnu granu, zatim vraća kod na oznaku i bazu na backup. Dira samo FCC-Design i `fcc-design` kontejnere.
4. Za brzu usporedbu nije potreban povrat, dovoljan je `?fcc_ui=classic`.
5. Lokalni runtime 8099 i radna kopija FCC-Live nisu dirani, jer ih istodobno koristi drugi zadatak.

## 4. Kartice suradnika: prijedlog rješenja bez implementacije

### 4.1. Stanje u lokalnoj kopiji baze

1. Postoji 994 kartica (biolink stranica) od 710 korisnika i 9.432 bloka.
2. Čak 951 kartica nema odabranu gotovu temu, nego vlastite boje, pozadinu i font. Svaki blok ima vlastite postavke boje, ruba, sjene i animacije.
3. Njih 86 ima vlastiti CSS ili JavaScript. To su kartice koje je netko ručno prilagodio i najlakše ih je pokvariti.
4. U zadnjih 30 dana uređivano je 19 kartica.

Zaključak: kartice su osobni rad suradnika. Globalna promjena stila slomila bi stotine pojedinačnih odluka, pa se kartice smiju mijenjati samo na zahtjev vlasnika.

### 4.2. Načela

1. Ništa se ne mijenja samo od sebe. Postojeća kartica izgleda točno kao danas dok vlasnik sam ne odabere novi izgled.
2. Svaka promjena ima pregled prije primjene i povrat jednim dodirom.
3. Ručno uređene postavke imaju prednost pred novim zadanim vrijednostima.
4. Administrator može pozvati suradnike da isprobaju novi izgled, ali ne može ga primijeniti umjesto njih.
5. Adresa kartice, NFC i QR poveznice, referral, opisi za tražilice i praćenje posjeta ostaju isti.

### 4.3. Kako bi radilo za suradnika

1. U uređivaču kartice pojavljuje se obavijest „Dostupan je novi izgled kartice”, s gumbom „Pogledaj kako bi izgledala”.
2. Otvara se usporedba: lijevo današnja kartica, desno ista kartica u novom izgledu, sa stvarnim sadržajem. Ništa još nije objavljeno.
3. Suradnik bira jednu od tri mogućnosti:
   1. „Primijeni na cijelu karticu”: novi izgled pozadine, fonta i svih blokova.
   2. „Primijeni samo gdje nisam ništa mijenjao/la”: nove vrijednosti dobivaju samo postavke koje su još jednake izvornim FCC vrijednostima. Vlastite boje, slike i animacije ostaju.
   3. „Zadrži sadašnji izgled”: obavijest se skriva i više se ne prikazuje automatski.
4. Nakon primjene vidljiv je gumb „Vrati prethodni izgled”, dostupan najmanje 90 dana.
5. Kartica s vlastitim CSS ili JavaScript kodom dobiva samo pregled i ručnu primjenu, uz jasnu napomenu da vlastiti kod može izgledati drugačije.

### 4.4. Tehničko rješenje

1. Svaka kartica dobiva oznaku verzije izgleda u postojećim postavkama, primjerice `fcc_design = "classic"` ili `"zivi-1"`. Kada oznake nema, kartica je `classic` i prikazuje se kao danas.
2. Novi izgled kartice je zaseban stil koji `biolink_wrapper.php` učitava isključivo za kartice s oznakom `zivi-1`. Stil radnog prostora i stil kartica ostaju odvojeni.
3. Prije svake primjene sprema se snimka: postavke kartice i postavke svih njezinih blokova, u novu tablicu, primjerice `fcc_card_design_snapshots` (kartica, vlasnik, vrijeme, razlog, JSON snimka). Povrat vraća točno tu snimku.
4. Za mogućnost „samo gdje nisam ništa mijenjao/la” uspoređuju se postavke s izvornim vrijednostima predloška iz kojeg je kartica nastala. Promijenjene vrijednosti se ne diraju.
5. Pregled se iscrtava iz privremene kopije postavki u memoriji, bez zapisa u bazu.
6. Svaka primjena i povrat bilježe se u dnevnik: tko, kada, koja verzija i koja mogućnost.
7. Nove kartice mogu dobiti novi izgled kao zadani tek kada ga odobrite, a stare kartice ostaju na svojoj verziji.

### 4.5. Uvođenje

1. Faza 1: vaše vlastite kartice i nekoliko dobrovoljaca, uz povratne informacije.
2. Faza 2: obavijest u uređivaču za sve, bez ikakve automatske promjene.
3. Faza 3: novi izgled kao zadani za nove kartice, ako je faza 2 dobro prošla.
4. Mjerenje: broj pregleda usporedbe, broj primjena, broj povrata. Velik broj povrata znači da izgled treba doraditi prije šireg uvođenja.

## 5. Put do produkcije

1. Prije objave: lokalno odobrenje svih ekrana, DM Sans font spremljen na vlastiti poslužitelj umjesto Google Fonts poveznice, i usklađivanje s radom koji se u međuvremenu nalazi u FCC-Live.
2. Objava koda ne mijenja izgled, jer je zadani izgled i dalje stari. To je sigurna prva faza.
3. Zatim se novi izgled može uključiti za sve postavljanjem `FCC_UI_THEME` na `zivi` u `fcc-partner-runtime.php` na poslužitelju. Povrat je vraćanje na `classic`, bez nove objave koda.
4. Objava ide uobičajenim putem uz deploy lock `forevercard.club` i vašu izričitu potvrdu.

## 6. Otvorena pitanja

1. Treba li novi izgled najprije uključiti samo administratorima na produkciji, radi pregleda na stvarnim podacima?
2. Treba li javna naslovnica dobiti novi izgled istodobno s radnim prostorom ili kasnije?
3. Koliko dugo čuvati snimke kartica za povrat: 90 dana ili trajno?
