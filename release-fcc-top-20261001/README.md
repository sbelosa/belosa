# FCC Top 10

Objavljeno 1. listopada 2026. u 15:59 prema zagrebačkom vremenu. Stranica je dostupna na https://forevercard.club/partner/top, preko rubrike Više.

Ovaj paket sadrži samo devet objavljenih datoteka u mapi payload. Okolni Git checkout nije potpuna aktualna produkcijska aplikacija. Ne objavljivati ostatak repozitorija. Polazišne i završne kontrolne vrijednosti datoteka nalaze se u manifest.json. Razlike prema stvarnoj produkciji nalaze se u changes.patch.

1. PRO pristup provjerava se na poslužitelju prije izračuna modela za prikaz. Svi aktivni računi ulaze u obračun bez obzira na paket.
2. Osam kategorija obuhvaća osobni CC, izvorni Total CC, preporuke proizvoda, potvrđeno slanje pozivnica, potvrđene prijave gostiju, komunikacije, završene korake i dane aktivnosti. Aktivnosti imaju prozore 7, 30 i 60 kalendarskih dana po zagrebačkom vremenu. CC koristi odvojeni izbor tekućeg ili prethodnog mjeseca.
3. CC dolazi samo iz potvrđenih FLP360 snimki. Osobni CC nije ponovno dodan u Total CC. Nije uveden izmišljeni izračun sponzorskih CC niti atribucija kupnje FCC poveznici.
4. Tuđi iznosi CC nisu uključeni u model za prikaz. Zamućeni znakovi su zamjenski prikaz. Imena se prikazuju nakon uključivanja vidljivosti. Zajednički Forever broj računa se jednom i zahtijeva uključenu vidljivost svih povezanih aktivnih računa. Postojeći zahtjev za privatnost ima prednost.
5. Vidljive liste prikazuju najviše deset sudionika. Izjednačeni rezultati dijele mjesto, a stabilni interni ključ određuje redoslijed unutar istog mjesta. Vlastito mjesto među svim pozitivnim rezultatima dostupno je samo korisniku. Promjene se uspoređuju s posljednjom ranijom dnevnom snimkom istog razdoblja.
6. Poslana preporuka zahtijeva evidentiranu stvarnu radnju uz kontakt. Slanje webinar pozivnice zahtijeva izričitu potvrdu slanja i imenovani kontakt. Kopiranje poveznice i otvaranje aplikacije za poruke nisu slanje. Stari zapisi bez tih podataka nisu pretvoreni u potvrđene radnje. Potvrđena prijava gosta nije dokaz dolaska na događaj.
7. Osobni ciljevi vrijede za posljednjih sedam dana. Početni brojevi su prijedlozi koje korisnik može promijeniti. Spremanje zahtijeva valjani CSRF token i aktualnu verziju postavki.
8. Dvije zasebne postavke dopuštaju tjedni podsjetnik i obavijest o ulasku među prvih deset. Obavijesti su isključene do korisničkog odabira, ostaju samo u FCC sandučiću i ograničene su na dvije tjedno. Prvo mjerenje postavlja početno stanje. Dostava preko push sustava posebno je blokirana pri stvaranju reda i pri slanju.
9. Funkcionalnost ima hrvatske, engleske, slovenske, njemačke i španjolske tekstove. Drugi jezici koriste engleski kao zadani jezik ove funkcionalnosti.

Provjera obuhvaća 328 tvrdnji za novu funkcionalnost, uključujući tekstove za pet jezika, stvarne upite u lokalnu bazu, kontrolu pristupa, skrivene iznose, duplikate, zajedničke račune, obavijesti i HTTP provjere. Zasebno je prošao postojeći test obavijesti. Mobilne širine 360, 390 i 430 piksela nemaju vodoravno prelijevanje. U pregledniku su provjereni spremanje i isključivanje vidljivosti, promjena cilja, poveznica Više, izbor rujna i listopada te filtri. Probne aktivnosti ostale su u lokalnim transakcijama koje su vraćene.

Produkcijska provjera potvrdila je svih devet objavljenih kontrolnih vrijednosti, pristup prijavljenog PRO korisnika, preusmjeravanje neprijavljenih posjetitelja, oba CC mjeseca i vraćen izvorni radnik za obavijesti. Izračun obaju mjeseci trajao je približno 0,12 sekundi. Na početku objave obračun je obuhvatio 701 aktivni račun. Vidljivost niti obavijesti nisu automatski uključene nijednom korisniku.

Sigurnosna kopija izmijenjenih datoteka zabilježena je u publish-result.json. Za povrat treba vratiti četiri postojeće datoteke iz te privatne kopije i ukloniti pet novih datoteka tek nakon vraćanja njihovih pozivatelja. Tri nove tablice mogu ostati neaktivne kako bi se sačuvale korisničke postavke. Postojeći poslovni podaci, naplata, klikovi 15+ i 50+ te postojeći edukacijski napredak nisu mijenjani.

Testovi zahtijevaju aktualni FCC Partner lokalni runtime i FCC_LOCAL=1. Pokreću se isključivo prema lokalnoj bazi fcc_partner_local na poslužitelju db. Paket nije zamjena za cijelu aplikaciju.
