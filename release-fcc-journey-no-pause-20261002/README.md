# Edukacija bez pauziranja

Objavljeno 2. listopada 2026. u 12:41 po zagrebačkom vremenu.

1. Iz postavki edukacije uklonjen je datum pauze. Nedovršeni zadatak i njegov nacrt ostaju dostupni dok korisnik ne potvrdi završetak.
2. Spremljeni datumi pauze iz ranijih postavki više ne blokiraju prikaz, spremanje nacrta, završetak, podsjetnike ni prikaz napretka sponzoru. Stari obrazac ili izravni zahtjev ne mogu ponovno uključiti pauzu.
3. Coach više ne predstavlja pauzu kao dostupnu opciju. Kontekst i pravila imaju novu oznaku verzije.
4. Prava pristupa, kriteriji završavanja, redoslijed, zaštita od ponovljenog zahtjeva i postojeće pravilo jednog završenog zadatka dnevno ostaju na snazi. Korisniku je postavljeno zasebno pitanje želi li i neposredni pristup sljedećem zadatku; to nije uvedeno bez jasnog odabira.
5. Osobni izbor podsjetnika ostaje neovisan. Isključivanje podsjetnika ne zatvara zadatak. Zasebna pauza podsjetnika u poslovnim prezentacijama nije pauza edukacije i nije mijenjana.

## Provjera

Prošla je 31 ciljana provjera za PRO i Free račune, stari i aktualni program, stari obrazac, nacrte nakon četrnaest dana, redoslijed i ponovljene zahtjeve. Dodatno prolaze 99 provjera konteksta i 760 provjera aktualnih 90 zadataka. Sintaksa svih jedanaest PHP datoteka je provjerena. Obrazac je pregledan u pregledniku na računalu i mobitelu; kontrola pauze više ne postoji, a izbor podsjetnika postoji.

Poslužiteljska provjera u zasebnom PHP procesu nakon objave potvrdila je sva tri računa sa spremljenim datumom pauze, tri sačuvana ciklusa i pet sačuvanih koraka. Povijest je sačuvana. Jedan od računa imao je još aktivan datum pauze i otvoren korak. Datoteke na produkciji odgovaraju manifestu, a privatni radnik vraćen je u prvotno stanje.

Širi test webinara nije dovršen jer lokalna baza nema raspoloživ objavljeni termin koji njegov scenarij zahtijeva. Povezana promjena opcionalnog savjeta Coacha zasebno je pokrivena ciljanim testom. Nisu pozivani AI servisi ni slane poruke ili obavijesti u okviru testiranja.

## Objava i povrat

Objavljeno je dvanaest datoteka, uz provjeru prethodnih hash vrijednosti, privatnu sigurnosnu kopiju i atomske zamjene. Provjera nakon objave izvedena je u novom procesu, kako bi se učitao novi kod. Nije bilo migracije ni prepisivanja korisničkih postavki ili poslovnih zapisa. Izvorni sadržaj datoteka dostupan je u privatnoj sigurnosnoj kopiji izvan javnog direktorija. Ovaj paket ne sadrži pristupne podatke, produkcijske identifikatore korisnika ni privatne snimke ekrana.
