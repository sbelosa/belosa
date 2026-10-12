# FCC kartice: analiza i prijedlog nove kartice

Datum: 12. listopada 2026. Izvor: kod grane `claude/live-baseline-2026-10-11` i lokalna kopija produkcijske baze od 11. listopada 2026. Kartice i uređivač pregledani su na http://localhost:8099 na širini mobitela od 375 px. Pregledna verzija s prikazom na mobitelu: https://claude.ai/artifact/8GT7jrGsqGr3uJedBFaLKp. Ništa nije implementirano.

## 1. Kako kartica nastaje danas

1. Pri registraciji `Altum\Models\User::create_links()` stvara vCard, glavnu karticu i QR kod. Kartica se kopira iz predloška kartice 83 („Forever Recruiter Revolution“, korišten 1.113 puta), bez blokova popusta i tržišta BiH i Albanije.
2. `fc_ensure_factory_shop_block()` dodaje tvorničke gumbe za trgovinu i poslovnu suradnju, a `hydrate_biolink_from_user_data()` upisuje ime, provjerava avatar i resetira preporuku na novog vlasnika.
3. Adresa kartice je trajno zaštićena, pa NFC i QR uvijek rade.
4. Javni prikaz (`themes/altum/views/l/partials/biolink.php`) prikazuje samo vrste blokova koje dopušta paket. Forever Beginner dopušta 6 od 77 vrsta (avatar, naslov, društvene mreže, Forever trgovina, popust, spremanje kontakta), a Forever Pro sve.
5. Predložak se mijenjao, pa postoje tri generacije početne kartice. Najstarija (263 kartice) ima obične poveznice „FOREVER WEB SHOP“ i „SAVE CONTACT“ te obrasce za telefon i e-mail, koji su na Beginneru skriveni.

## 2. Stanje u brojkama

1. 726 glavnih kartica odobrenih suradnika (618 Beginner, 106 PRO). U sustavu je ukupno 997 kartica od 713 korisnika.
2. 475 kartica (65 %) nikad nije uređeno nakon nastanka.
3. Teme koristi 19 kartica. Postoji 6 tema. Nijedna kartica nema vlastiti CSS ni JavaScript.
4. Na karticama se pojavilo 43 vrste blokova, a samo dvadesetak na više od deset kartica. Uređivač nudi 76 vrsta.
5. Obrazac za telefon je na 512 kartica i prikupio je ukupno 47 brojeva, u zadnjih 90 dana nijedan. Obrazac za e-mail je na 285 kartica i prikupio je 25 adresa.
6. Najviše klikova ima trgovina: 13.211 na staroj poveznici „FOREVER WEB SHOP“ (304 kartice) i 7.301 na gumbima s popustom (43 po kartici). Tvornički gumb „Forever web shop“ za registraciju u trgovini ima 0,6 klikova po kartici.
7. Na 594 kartice prikazuje se jedan od dva zajednička timska videa, a na 282 kartice AI savjetnik.

## 3. Nalazi

1. **Tekst o zemlji se ne čita.** „Zemlja se određuje automatski. Možeš je promijeniti.“ stoji ispod svakog Forever gumba tamnim slovima na tamnoj pozadini.
2. **Dvije kartice spremaju tuđi kontakt.** Gumb „Spremi kontakt“ vodi na vCard druge osobe.
3. **Dva gumba za istu trgovinu** na PRO kartici.
4. **Beginner kartica je preprazna**, a najstarija generacija na Beginneru gubi i trgovinu i spremanje kontakta.
5. **Engleski na hrvatskim karticama.** Podnožje „Page created by independent FBO at Forever Card“ uvijek je na engleskom, a 193 kartice imaju gumb „Business partnership and Start Your Journey“.
6. **Prva radnja je „Postani suradnik“**, iako posjetitelji najviše traže trgovinu i kontakt.
7. **Zastarjeli izgled.** Prijelaz crne u zlatnu, žuti gumbi velikim slovima s tvrdom sjenom, prazan avatar, ime bijelim slovima sa 65 % prozirnosti, plutajuća sova koja prekriva gumbe.
8. **Uređivač.** Pregled je zasebna kartica. Stranica uređivača odjednom učitava 87 skrivenih prozora i 1.069 polja (vidljivo 26), ukupno 3,4 MB koda. Ispod blokova vide se tehničke adrese, a Izgled nudi favicon, zamućenje i svjetlinu pozadine. Na mobitelu prekidač pojedinih redaka izlazi izvan okvira.

## 4. Prijedlog: nova kartica

Unaprijed složen redoslijed od osam dijelova, s podacima iz postojećih blokova i profila:

1. Profil: fotografija, ime, „Forever Business Owner“ i grad, oznaka provjerenog FCC suradnika.
2. Glavna radnja „Naruči Forever proizvode“ s preporukom i popustom, uz jedan red „Ponuda za Hrvatsku · Promijeni“.
3. Brze radnje WhatsApp i Spremi kontakt.
4. Osobni uvod i kratki video (PRO).
5. Tri preporučena proizvoda iz Akademije (PRO).
6. Kartica „Želiš i ti surađivati s Foreverom?“ prema Pozivnici u tim.
7. „Pitaj savjetnika“ unutar kartice (PRO), umjesto plutajuće ikone.
8. Društvene mreže i podnožje na jeziku posjetitelja.

Tri stila umjesto šest tema: Aloe (svijetli), Smaragd (tamni) i Zlato (nasljednik sadašnje kartice). Svaki stil sam pazi na čitljivost. Beginner dobiva cijelu osnovnu karticu, a PRO dodaje uvod, proizvode, savjetnika, obrazac za upit, dodatne blokove i statistiku.

## 5. Prijedlog: novi uređivač

1. Živi pregled uvijek vidljiv. Na mobitelu je kartica gore, a dodir na dio otvara donji panel s dva do četiri polja.
2. Tri koraka: Profil, Radnje, Izgled.
3. Mala biblioteka od četrnaestak blokova za Forever posao. Ostale vrste rade na postojećim karticama, ali se više ne nude.
4. Polja se učitavaju kada zatrebaju.

## 6. Prijelaz

1. „Isprobaj novu karticu“ otvara usporedbu s vlastitim sadržajem, bez objave.
2. Suradnik bira: prebaci, zadrži staru ili kasnije.
3. Prebacivanje ne briše blokove. Nova kartica je drugačiji prikaz istih podataka (oznaka verzije u postavkama kartice), uz snimku postavki prije promjene.
4. „Vrati staru karticu“ najmanje 90 dana. Adresa, NFC, QR i preporuka ostaju isti.
5. Administrator može pozvati na isprobavanje, ali ne može prebaciti umjesto suradnika.
6. Pilot s desetak suradnika i mjerenje klikova na narudžbu, WhatsApp i spremanje kontakta prije ponude svima.

## 7. Faze

1. Ispravci bez promjene izgleda: čitljiv tekst o zemlji, prijevod podnožja, dvije kartice s tuđim kontaktom.
2. Nova kartica s tri stila, prekidačem, snimkom i povratkom, lokalno na 8099.
3. Novi uređivač.
4. Pilot i mjerenje.

## 8. Odluke za korisnika

1. Smiju li se ispravci iz prve faze primijeniti na sve kartice?
2. Dobivaju li nove registracije odmah novu karticu, uz povratak na staru?
3. Koji stil je zadani: Aloe, Smaragd ili Zlato?
4. Prestaju li se nuditi obrasci za telefon i e-mail? Postojeći ostaju.

## 9. Lokalni testni podaci

Testne kartice `claude-test-beginner` (2804) i `claude-test-pro` (2805) na 8099 popunjene su iz tvorničkog predloška, kako bi izgledale kao kartica novog suradnika.
