# FCC osobno sponzoriranje, javna prezentacija

Ovo je izdvojeni kontekst nove javne osobne prezentacije. Datum dokumentiranja je 15. listopada 2026. Dokument nije zamjena za kontekst cijele FCC aplikacije. Dokumentirane vrijednosti i ponašanje preuzeti su iz izvedenog koda i završnih lokalnih snimki.

## Namjena i granice

1. Posjetitelj upoznaje poslovnu priliku kroz stvarnu osobnu karticu suradnika, preporuke proizvoda, učenje, podršku, kontakte, događaje i daljnje osobno sponzoriranje. Može otvoriti povezani sadržaj ili zatražiti razgovor.
2. Način površine je `Persuade`. Cilj je razumljiva odluka i osobni kontakt. Izvorna javna ruta je `/suradnja/{code}`, uz odabrani jezik i zasebno odabranu zemlju.
3. FCC je neovisan sustav tima Stjepana i Snježane Beloša. Nije službena stranica ili aplikacija kompanije Forever Living Products.
4. Nova prezentacija ne zamjenjuje postojeće osobne kartice i njihove odabrane teme. Privatni radni prostor za dijeljenje nasljeđuje postojeće tokene FCC aplikacije i dodaje samo vlastiti raspored. Ne preuzima ovu novu javnu kompoziciju kao globalni dizajn.
5. Ponude i cijene pripadaju odabranom tržištu i aktualnom izvoru. Jezik ne mijenja vlasnika, sponzora, Forever ID ili zemlju. Vizualna provjera ne odobrava izmišljena iskustva, učinke, zaradu ili univerzalnu dostupnost pojedine funkcije.

## Izvedeni prikaz

Početak na računalu koristi veliki osobni poziv i osobnu karticu u dva stupca. Mobitel koristi kraći naslov i opis, zatim odmah veliku karticu. Uvodni poziv na radnju, sekundarna poveznica i prateća rečenica dolaze iza kartice. Na 390 × 844 i 360 × 800 px ime vlasnika i cijela prva kartična radnja moraju biti vidljivi bez pomicanja.

Sredina prikazuje konkretnu javnu preporuku proizvoda i stvarnu osobnu pozivnicu. Prvi prikaz vodi na postojeću osobnu preporuku, a drugi prikazuje vlasnika i adresu ove prezentacije. Objašnjenje webinara ostaje odvojeno. Završetak nudi osobni kontakt, odgovarajući kanal, zemlju i jasan zahtjev za javljanjem. Bez fotografije koristi se postojeći prikaz inicijala, a bez proizvoda stvarna osobna kartica.

## Izvori koda i sadržaja

Sve putanje u ovom odjeljku relativne su prema korijenu `source` ove radne mape.

1. `themes/altum/views/partner/sponsoring-public.php` određuje javnu strukturu i stvarne komponente.
2. `themes/altum/assets/css/fcc-sponsoring.css` određuje izdvojeni opseg `.sp-page`, osam boja, DM Sans, raspored, prijelome i stanja.
3. `themes/altum/assets/js/fcc-sponsoring.js` dodaje odabir kontaktnog kanala, ponašanje slanja, fokus pogreške, mobilnu radnju i zatvaranje jezika.
4. `app/helpers/fcc_sponsoring.php` preuzima vlasnika i javno dostupne podatke iz postojeće kartice. Proizvode razrješava kroz postojeći FCC katalog i osobne poveznice. Podaci fixture prikaza nisu novi izvor za produkcijski identitet.
5. `app/helpers/fcc_sponsoring_copy.php` daje tekstove za `hr`, `en`, `sl`, `de` i `es`.
6. `scripts/render_fcc_sponsoring_fixture.php` deterministički priprema lokalna prikazna stanja bez baze i promjena runtimea.

## Podrijetlo vizuala

Završna javna izvedba koristi postojeći FCC logotip, stvarnu fotografiju vlasnika, stvarne fotografije proizvoda i linijske SVG simbole iz izvornog prikaza. Ne koristi generiranu fotografiju mentorstva iz ranijeg koncepta. Nije dodan novi rasterski prikaz sučelja.

1. Fotografija vlasnika potječe s javne kartice `https://forevercard.club/ddglabhlcn`, odnosno `https://forevercard.club/uploads/avatars/0af9c3883e1746a91b2698d72b619667.png`. Kontrolni sažetak SHA256 lokalnog izvornika `sponsor.png` je `5bc3db548231c73e43b052d269f8291c255d96aa4ac276bfc8402f5266ebabea`. Izvorni zapis je `local/fcc-sponsoring-concept-20261014/assets/provenance.json` u FCC projektu.
2. Forever Aloe Vera Gel potječe s `https://forevercard.club/uploads/blog/866ad43b6e9730a2b2d277d57032af42.png`. SHA256 lokalne dokazne fotografije je `b760f9d594c736455c3aa4c8172afcd72d1ae215ac8413dcd39ecc2158de7a60`.
3. Aloe Ever Shield potječe s `https://forevercard.club/uploads/blog/fd54132e699b0681b01135e90ea52d1c.png`. SHA256 lokalne dokazne fotografije je `46f29afb86124ba63806a633015ee930c93f05d7418c3d458a077eb1b9d0104e`.
4. Forever Aloe Peaches potječe s `https://forevercard.club/uploads/blog/8a327b2682fb13b81e355befe0182db5.png`. SHA256 lokalne dokazne fotografije je `1a779d71067f37fd094fd9b8f0a268b2e051fe3e8375be6dff152c3662f70379`.
5. Zapis tih triju fotografija nalazi se u `../visual-output/actual-assets/provenance.json` u odnosu na korijen ove radne mape `source`. To su postojeće javne slike iz provjerenog modela, a ne prepravljena pakiranja.
6. DM Sans dolazi iz postojeće FCC mape `themes/altum/assets/fonts/dm-sans`, uz dvije lokalne WOFF2 datoteke i postojeću OFL licenciju. Logotip se koristi s postojeće javne putanje `partner-assets/fcc-logo.png`.

## Dokazni skup i granice potvrde

Završni vizualni izvor je commit `1b344eeadb88c5675b0eb634026b84454e539b74`, nakon početne vizualne izvedbe `511b4eb136fd892353a4f987e228f7bc02b49378`. Izvorna radna mapa nalazi se pod `local/fcc-sponsoring-live-20261014/source` u FCC projektu. Dokumenter nije mijenjao aplikacijske datoteke, Git stanje, bazu ili produkciju.

1. `../visual-output/manifest-reviewer-fix.json` veže pet izvornih datoteka uz kontrolne sažetke i završni commit.
2. `../visual-output/reviewer-final*.png` čini skup od 28 PNG snimki. Obuhvaća hrvatski, engleski, slovenski, njemački i španjolski, utvrđene širine 1440, 390 i 360 px, dugo njemačko ime te hrvatsko prazno stanje, pogrešku i uspjeh. Dva izreza dodatno pokazuju kontakt.
3. `../visual-output/verification-reviewer-final.json` ima 13 prikaza bez neučitanih slika, vodoravnog prelijevanja ili pogrešaka. Potvrđuje izbor kanala, otvaranje pitanja, pojavu mobilne radnje u sredini i skrivanje kod kontakta te očuvanje jezika i zemlje u tržišnom obrascu.
4. `../visual-output/detector-reviewer-final.json` sadrži prazan nalaz `[]`.
5. Neovisna završna provjera `/private/tmp/fcc-sponsoring-finish-verdict-20261014.md` daje odluku `ship` isključivo za prethodne dorade F1 i F2. Obje su riješene. To nije novi puni pregled cijele površine, PDF dokumenta, privatnog odabira Podijeli, sigurnosti, slanja upita, referral integriteta ili produkcijske objave.

Vizualni sustav nalazi se u `DESIGN.md`, a njegova strojna proširenja i samostalni prikazi komponenti u `.impeccable/design.json`. Dokumenti pripadaju isključivo ovoj javnoj površini i ne stvaraju globalnu zamjenu dizajna FCC aplikacije.
