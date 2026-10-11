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

## 6. Otvorene stavke

1. **GeoLite baze su stare.** `app/includes/GeoLite2-City.mmdb` i `GeoLite2-Country.mmdb` su iz travnja 2026. Kod koristi lokalnu bazu samo ako je mlađa od 60 dana, pa svaki novi posjetitelj čeka vanjski geo servis. Potrebno je preuzeti svježe MaxMind datoteke i uvesti njihovo redovito osvježavanje.
2. **Deploy workflow koristi običan FTP bez šifriranja.** Treba prijeći na `protocol: ftps` s imenom poslužitelja koje odgovara TLS certifikatu. Za to treba ažurirati GitHub Secret `FTP_SERVER`.
3. **Ručni workflow `sync-live-to-github.yml` isključuje provjeru certifikata.** Nakon objedinjavanja ga treba ukinuti ili ispraviti.
4. **Smeće na produkciji.** `themes/__MACOSX` ima 2099 datoteka iz raspakirane arhive. Uz to postoje `.ftp-deploy-sync-state*` datoteke izvan uobičajenog stanja workflowa. Treba ih ukloniti pri kontroliranoj objavi.
5. **`vendor/phpmailer/phpmailer/src/AcmHandler.php`** je AltumCodeov mehanizam praćenja licence koji se aktivira samo pri instalaciji ili ažuriranju. Nije zlonamjeran i ostaje.
6. **Produkcijski ključ `FCC_OPS_READONLY_KEY`** pojavio se u zapisniku Claude razgovora 11. listopada. Preporučena je zamjena ključa.

## 7. Put objave

1. Jedini put objave je merge u `main`. Workflow tada pokreće lint i testove te objavljuje `app/` i `themes/`.
2. Prije objave treba uzeti deploy lock `forevercard.club` preko SharedAI koordinatora. Zatim treba provjeriti da produkcija nije mijenjana nakon zadnjeg snimka i pripremiti povrat.
3. Ciljane ručne objave mimo `main` više se ne koriste, osim u hitnom slučaju koji se odmah vraća u Git.
