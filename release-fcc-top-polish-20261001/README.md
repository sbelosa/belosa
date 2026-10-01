# FCC Top, popravak prikaza i objašnjenja rezultata

Objavljeno 1. listopada 2026. u 16:50 prema zagrebačkom vremenu. Produkcijske postavke su na https://forevercard.club/partner/settings#fcc-top-settings, a rang liste na https://forevercard.club/partner/top.

Paket sadrži pet objavljenih datoteka u mapi payload. Okolni Git checkout nije aktualna produkcijska aplikacija. Za objavu se smiju koristiti samo navedene datoteke uz provjeru kontrolnih vrijednosti iz manifest.json. Ovo je nastavak paketa release-fcc-top-20261001.

1. Postavke sada učitavaju vlastiti stil s verzijom. Polja vidljivosti i obavijesti imaju izričit raspored kvadratića i tekstnog bloka, otporan na sukob s općim stilovima obrazaca. Ciljevi su u odvojenoj cjelini, a mobilni prikaz koristi jedan stupac.
2. FCC Top početno otvara prethodni kalendarski mjesec za CC. Izričit izbor tekućeg mjeseca ostaje važeći. Aktivnosti ostaju zasebni prozori 7, 30 i 60 dana.
3. Pregled prikazuje broj Forever računa s pozitivnim osobnim i ukupnim CC rezultatom. Svaka kategorija odvojeno pokazuje broj računa s rezultatom i broj računa s vidljivim imenom.
4. Prazna lista imena uz postojeće rezultate sada izričito objašnjava da su imena skrivena. Kategorija bez potvrđenih zapisa objašnjava ograničenje evidencije. Izostanak zapisa nije predstavljen kao dokaz neaktivnosti suradnika.
5. Obavijesti povezuju na mjesec koji je korišten pri njihovu izračunu. Pristup ostaje samo za PRO, a svi aktivni računi i dalje ulaze u obračun. Osobni odabiri vidljivosti i obavijesti nisu mijenjani.
6. Tekstovi su dostupni na hrvatskom, engleskom, slovenskom, njemačkom i španjolskom.

Produkcijska provjera pokazuje 134 Forever računa s pozitivnim osobnim CC i 159 s pozitivnim ukupnim CC za rujan. Najnovija potvrđena rujanska snimka bila je 30. rujna u 06:42 po zagrebačkom vremenu. To nije tvrdnja da je proveden završni obračun cijelog mjeseca. Za listopad je u trenutku provjere bilo 0 računa s pozitivnim osobnim CC i 1 s pozitivnim ukupnim CC. U 30 dana bilo je 45 računa s evidentiranim završenim koracima. Brojevi se mijenjaju nakon novih zapisa i sinkronizacija.

Na početku revizije nitko nije imao uključenu vidljivost. Tijekom objave jedan korisnik uključio ju je samostalno. Završna provjera potvrdila je prikaz njegova imena bez tuđih CC iznosa. Testiranje nije mijenjalo produkcijske korisničke postavke niti slalo obavijesti.

Za poslane preporuke i potvrđene pozivnice trenutačna evidencija nije imala zapise koji zadovoljavaju postojeća pravila. Pronađene starije ručne bilješke nisu imale povezanu komunikaciju ni potvrđen status slanja. Nisu retroaktivno proglašene potvrđenim slanjem. Mjerila brojanja nisu mijenjana u ovom popravku.

Provjera uključuje 457 uspješnih tvrdnji, od čega većinu čine prijevodi i postojeće osnovne provjere. Posebni testovi provjeravaju skrivene rezultate, dijeljene Forever račune, broj vidljivih računa, zadani i izričit mjesec, privatnost CC, PRO ograničenje i poveznice obavijesti. Lokalni podaci za testove vraćeni su transakcijama. U pregledniku su provjereni spremanje i vraćanje lokalnih postavki, širine 390 i 1440 piksela te produkcijske širine 360 i 1440 piksela bez vodoravnog prelijevanja.

Objava nije mijenjala shemu baze ni poslovne podatke. Svih pet datoteka potvrđeno je kontrolnim vrijednostima. Radnik za obavijesti vraćen je na izvornu verziju nakon provjere. Privatna sigurnosna kopija zabilježena je u publish-result.json. Za povrat treba vratiti tih pet datoteka iz navedene kopije, bez promjena baze.

Testove treba kopirati u podmapu local aktualnog FCC Partner izvornog koda, primjerice local/top-polish-20261001. Zahtijevaju FCC_LOCAL=1 i isključivo lokalnu bazu fcc_partner_local na poslužitelju db. Testovi koriste sintetičke lokalne račune i datume ove provjere.
