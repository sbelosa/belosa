# Jedan FCC, produkcijska objava 1. listopada 2026.

Novi FCC aktiviran je za svih 701 aktivnih računa, odnosno 696 suradnika i 5 administratora. Četiri neaktivna računa ostaju blokirana. Postavka vrijedi i za buduće račune nakon odobrenja administratora.

## Promjene

1. Migracija 026 dodaje trajnu postavku zajedničkog novog radnog prostora. Aktivacija koristi postojeći administratorski postupak s provjerom popisa, revizijom, transakcijom i zapisom radnje. Glavni prekidač i provjere statusa računa ostaju važeći.
2. Prvi ulazak nakon globalne aktivacije povezuje postojeći edukacijski ciklus. Aktivni, pauzirani i dovršeni programi te spremljene poruke i zadaci ostaju sačuvani. Korisnik bez ciklusa dobiva aktualni program samo jednom.
3. Naslovnica prijavljenog korisnika i stara nadzorna ploča vode u novi FCC. Stara poveznica za detaljnu statistiku vodi na dashboard?view=statistics. Analitički podaci, grafikoni, izvori posjeta i statističke funkcije ostaju dostupni u novom radnom prostoru.
4. Stari početni prikaz, uvodni obilazak i duplicirana obavijest o edukaciji više se ne prikazuju u novoj statistici. Postojeći alati koriste zajedničku navigaciju i izgled novog FCC-a.
5. Navigacija prema bodovima i poveznicama koristi izravne nove putanje. Stare javne adrese, NFC kartice, članci, obrasci i urednici sadržaja ostaju dostupni. Poslužiteljski kompatibilni dijelovi potrebni tim funkcijama ostaju sačuvani.
6. U administraciji više nema redovnog gumba za vraćanje suradnika na stari FCC nakon globalne aktivacije. Administratorski pregled koristi istu provjeru pristupa kao aplikacija. Interna mogućnost hitnog povlačenja pojedinačnog pristupa ostaje provjerena.
7. Na stranici za instalaciju uklonjena je zastarjela poruka o dostupnosti samo odabranim suradnicima. Novi administrativni tekstovi dostupni su na hrvatskom, engleskom, slovenskom, njemačkom i španjolskom.

## Podaci i sigurnosna kopija

Prije objave izrađena je potpuna kopija baze sa 147 tablica i arhiva cijelog public_html. Provjereni su završetak SQL izvoza, broj tablica, integritet komprimiranih arhiva, njihov sadržaj i kontrolni sažeci. Arhiva stranice ima 1.434.198.437 bajtova, a komprimirana baza 22.555.723 bajta. Dodatna kopija baze preuzeta je lokalno i provjerena istim SHA256 sažetkom. Kopije s korisničkim podacima nalaze se izvan repozitorija i javnog direktorija. Nije rađena probna potpuna obnova produkcije.

Kontrolni sažeci svih redaka prije i odmah nakon aktivacije jednaki su za 11 provjerenih skupova podataka: korisničke identitete, pakete i preference, poveznice, blokove kartica, prikupljene upite, uplate, kontakte, osobna javljanja, prijave i pozivnice za webinare, edukacijske cikluse i edukacijske korake. Aktivacija je promijenila samo postavke i evidenciju pristupa. Dodatno je spremljen jedan neposlani nacrt emaila.

## Provjere

Prošle su 46 provjera upravljanog pristupa, 14 provjera glavnog prekidača, 103 lokalne HTTP provjere i 10 provjera prikaza na pet jezika. Posebno su provjereni neodobreni i isključeni računi, buduće odobrenje, administratorske ovlasti, ponovno uključivanje, povijest, planovi i pretplate. Provjerena je PHP sintaksa svih 14 objavljenih PHP datoteka.

Mobilna statistika provjerena je na širinama 360, 390 i 430 piksela. Širina sadržaja jednaka je širini prikaza. To je provjera u pregledniku, a ne fizički test iPhonea ili Androida.

Na produkciji provjereni su stvarni pristup za svih 705 računa, administracija, učitavanje statistike, kartice i uređivač glavne NFC kartice, kontakti, Moj tim, poziv na webinar, paketi, edukacija, stara ulazna poveznica, naslovnica nakon prijave, instalacija i spremljeni nacrt emaila. Glavna poveznica vip-edukacija vraća ispravno preusmjeravanje na Zoom. U pregledanim stranicama nisu zabilježene JavaScript pogreške. Konačni sadržaj svih 15 objavljenih datoteka provjeren je kontrolnim sažetcima.

Baza je povezana, redoviti poslovi rade, Stripe i slanje putem Breva ostaju konfigurirani. Postojeća naplatna upozorenja za 2 kritično zakašnjele pretplate i 15 ukinutih pristupa ostala su nepromijenjena. To su postojeća stanja naplate, a ne pogreške nastale prijelazom. Nova verzija ne dodjeljuje PRO prava besplatnim računima.

## Email za pregled

Nacrt broj 19 spremljen je u FCC administraciji. Predviđeno je 701 jedinstvenih ispravnih adresa aktivnih računa. Isključeni računi nisu u odabiru. Nijedna poruka nije poslana niti stavljena u red za slanje. Postojeće postavke newslettera nisu promijenjene.

Predmet je: Stigao je novi FCC! Večeras u 21:00 upoznaj sve mogućnosti.

Poziv navodi četvrtak 1. listopada 2026. u 21:00, po zagrebačkom vremenu, uz glavnu pristupnu poveznicu https://forevercard.club/vip-edukacija. Datoteke email.txt i email.html sadrže tekst za pregled. Slanje slijedi tek nakon korisnikove provjere i potvrde konačnog teksta. Ako se odobrenje dogodi nakon termina, najprije treba promijeniti vremenski dio poziva.

## Opseg paketa

Manifest sadrži točne sažetke prije i nakon izmjene. Direktorij payload sadrži samo 15 datoteka ove objave. Paket ne sadrži pristupne podatke, korisničke popise niti sigurnosnu kopiju baze. Izolirani testovi nalaze se u tests, a rezultati u verification.json. Stari ili lokalno promijenjeni ostatak radnog direktorija nije objavljivan.
