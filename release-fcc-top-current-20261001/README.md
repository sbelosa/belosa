# FCC Top, aktualni mjesec i ukupna aktivnost

Produkcijske promjene objavljene su 1. listopada 2026. u tri faze. Postavke su na https://forevercard.club/partner/settings#fcc-top-settings, a rang liste na https://forevercard.club/partner/top.

Ovaj paket nastavlja release-fcc-top-polish-20261001. Sadrži isključivo pet aktualnih datoteka u mapi payload. Okolni Git checkout nije aktualna produkcijska aplikacija i ne smije se objaviti kao cjelina. Manifest i zakrpa uspoređuju prethodni paket s konačnim stanjem nakon svih faza objave.

1. Vidljivost imena početno je uključena za postojeće i nove račune bez spremljenog odabira. Izričito spremljeno isključivanje i postojeći zahtjevi privatnosti ostaju na snazi. Promijenjena je zadana vrijednost stupca visible na 1. Spremljeni korisnički odabiri nisu prepisani.
2. Zadani CC mjesec je tekući kalendarski mjesec prema zagrebačkom vremenu. Rujan je dostupan kao izričit arhivski izbor s jasnom oznakom. Prazan ili nulti listopadski rezultat nikad se ne zamjenjuje rujanskim rezultatom.
3. Jedina CC rang lista je ukupna aktivnost, prema polju Total Active CC iz potvrđene FLP360 snimke. Uključuje osobne CC i CC Preferred Customer računa za odabrani mjesec. Osobni CC i Total CC nisu zasebne kategorije. Nova kategorija ima zasebnu povijest poretka i početno stanje obavijesti kako promjena mjerila ne bi izazvala lažnu obavijest o ulasku među prvih deset. Tuđi CC iznosi ostaju skriveni i ne šalju se u model javne rang liste.
4. Uklonjene su kategorije poslanih preporuka proizvoda i potvrđenog slanja pozivnica, uključujući njihovo računanje, tjedni napredak i polja ciljeva. Stari zapisi i spremljene vrijednosti ciljeva nisu obrisani. Ostaje pet kategorija: ukupna aktivnost, potvrđene prijave gostiju, pokrenute komunikacije, završeni koraci edukacije i dani aktivnog rada.
5. Pristup je i dalje samo za PRO, a svi aktivni korisnici ulaze u obračun. Odabir vidljivosti ne uključuje obavijesti. Korisnik zadržava mogućnost skrivanja imena i odvojenog uključivanja obavijesti.
6. Tekstovi su usklađeni na hrvatskom, engleskom, slovenskom, njemačkom i španjolskom jeziku. Zadržan je popravljeni raspored postavki na računalu i mobitelu.

Provjera obuhvaća 498 uspješnih tvrdnji, uključujući prijevode, pristup, stvarne SQL upite, privatnost, dijeljene Forever račune, prijelaz kalendarskog mjeseca, nulti tekući mjesec, odvojeni prethodni mjesec i spremanje pojednostavljenog obrasca. Lokalni podaci testova vraćeni su transakcijama i vraćanjem početnog cilja.

Testovi su namijenjeni aktualnom FCC Partner izvornom kodu u podmapi local/top-active-20261001. Zahtijevaju FCC_LOCAL=1 i isključivo lokalnu bazu fcc_partner_local na poslužitelju db. Testovi koriste sintetičke lokalne račune.

Datoteka publish-defaults-result.json bilježi početnu promjenu mjeseca i migraciju zadane vidljivosti. Datoteka publish-categories-result.json bilježi uklanjanje kategorija i pripadajućih polja. Datoteka publish-result.json bilježi konačni prijelaz na Total Active CC u 17:39. Kontrolni proces pri objavi učita prethodne pomoćne funkcije prije zamjene datoteka, pa njegovi dijagnostički nazivi kategorija pripadaju prethodnoj verziji. Konačni postcheck-result.json nastao je u novom procesu i mjerodavan je za stvarne kategorije i podatke nakon objave.

Nova sinkronizacija dovršena je 1. listopada 2026. u 17:40 prema zagrebačkom vremenu. Potvrdila je svih 668 povezanih Forever brojeva koji pokrivaju 700 aktivnih FCC računa te upisala 27 izmijenjenih CC snimki. Ručno osvježavanje odnosilo se samo na listopad. Završna provjera pokazuje 15 računa s pozitivnom ukupnom aktivnošću za listopad i vidljivih prvih deset. Arhivski rujanski prikaz ima 138 pozitivnih rezultata prema istom mjerilu. Sinkronizacija je dokumentirana u sync-verification.json.

Produkcija računa 701 aktivnog FCC korisnika, od kojih je 698 početno vidljivo. Tri računa ostaju skrivena zbog već postojećih zahtjeva privatnosti. Nema spremljenih isključivanja unutar ove značajke. Pristup Free korisnika je odbijen, tuđi iznosi ostaju skriveni, a radnik za obavijesti je vraćen na izvornu verziju. Pri provjeri nije poslana nijedna obavijest značajke. Desktop i mobilni prikaz potvrđeni su bez vodoravnog prelijevanja na širinama 1440 i 360 piksela.

Privatne sigurnosne kopije svih faza navedene su u njihovim rezultatima objave. Za potpuni povrat treba vratiti četiri datoteke iz prve kopije, top-settings.php iz druge kopije i zadanu vrijednost stupca visible na 0. To ne vraća niti prepisuje pojedinačne korisničke odabire ili sinkronizirane poslovne podatke.
