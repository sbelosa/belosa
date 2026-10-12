# Poslovna prezentacija: analiza i prijedlog

Datum: 12. listopada 2026. Izvor: kod na grani `claude/live-baseline-2026-10-11` (commit 300355f, jednak produkciji) i lokalna kopija produkcijske baze od 11. listopada 2026. u 00:03.

## 1. Kako modul radi danas

1. Suradnik u Partner aplikaciji otvara Podijeli, zatim Marketing plan ili Poslovnu prezentaciju (`partner-marketing`). Sustav stvara jedinstvenu poveznicu oblika `forevercard.club/hr/business/<64 znaka>`.
2. Primatelj otvara javnu stranicu (`BusinessPresentation`). Stranica opisuje suradnju, nudi WhatsApp i obrazac za upit, a nakon odabira zemlje prikazuje Start Your Journey paket i gumb prema službenoj Forever prijavi s ugrađenim FBO brojem vlasnika prezentacije.
3. Postoje dvije verzije:
   1. Stara verzija (`business-public-legacy.php`). Pripremu dopušta samo aktivni PRO paket.
   2. Nova verzija s tri modela: Upoznaj poslovanje, Osobni poziv, Od proizvoda do suradnje (`business-public.php`). Ne traži PRO, ali je u načinu `release_mode = admin`.
4. Nakon prijave u Forever osoba dobiva FBO broj. Za FCC registraciju treba taj 12-znamenkasti broj, a automatika potvrđuje sponzora preko FLP360.

## 2. Što pokazuju podaci

1. Odobrenih suradnika je 703. Aktivnu karticu ima 697, a PRO 86. Staru prezentaciju može pripremiti oko 12 posto tima.
2. Od 27. rujna do 10. listopada 21 suradnik pripremio je 63 poveznice. Zabilježeno je 13 otvaranja WhatsAppa kod slanja, 8 kopiranja, 7 potvrđenih slanja, 7 klikova prema Foreveru, 2 WhatsApp poruke posjetitelja i 1 upit. Stara verzija ne mjeri otvaranje stranice.
3. Tablica videa `fcc_business_assets` je prazna. Prezentacija trenutačno nema video.
4. Tablica tržišnih ponuda `fcc_business_market_offers` je prazna. Start paket dolazi iz članka `start-paket` i tablice tržišta.
5. Usporedbe radi, VIP vodiči imaju puno veći doseg. Stjepanov vodič ima 10.586 otvaranja, 465 klikova na radnje i 24 klika na narudžbu Start paketa. Vodiči Amile, Ljiljane, Dragana i Branke imaju između 2.695 i 6.490 otvaranja.

## 3. Uočeni problemi

1. **Nova prezentacija nije dostupna primateljima.** Dok je `release_mode = admin`, posjetitelj koji nije administrator dobiva 404.
2. **Greška u AI savjetniku.** Savjetnik na kartici nudi posljednju prezentaciju vlasnika. Na Stjepanovoj kartici to je nova poveznica #35, koja gostima vraća 404.
3. **Većina tima ne može poslati prezentaciju.** Staru verziju priprema samo PRO.
4. **Poveznica nije trajna ni pamtljiva.** Ne može se staviti u WhatsApp status, opis profila, QR kod ili na NFC karticu.
5. **Ne objašnjava vrijednost ulaska kroz tim.** FCC aplikacija, AI savjetnik, NFC kartica, Moj put, WhatsApp grupe i mentor gotovo se ne spominju. Upravo to Stjepanov vodič snažno prikazuje.
6. **Put završava odlaskom na Forever.** Ne objašnjava FBO broj, FCC registraciju ni prve korake. Sponzor ne dobiva obavijest kada netko klikne Naruči, pa izostaje pravovremen poziv.
7. **Podaci o paketu ističu.** Cijena, 2 CC i uvjet do Assistant Supervisora prikazuju se samo uz provjeru mlađu od 30 dana. Hrvatska ističe 27. listopada, a ostale zemlje 29. listopada.
8. **Crna Gora nema poveznicu za Start paket** iako postoji crnogorski jezik. Treba provjeriti službeni put prijave.
9. **Previše alata za isti cilj.** Marketing plan, tri modela prezentacije, VIP vodiči i webinar pozivnice. Suradnik ne zna što poslati novoj osobi.

## 4. Prijedlog: jedna Pozivnica u tim

Cilj: jedna stranica koju svaki suradnik može poslati. Osoba na njoj razumije posao i tim, naruči Start paket sa sponzorom te zna kako dovršiti FCC registraciju.

### 4.1 Dostupnost

1. Svaki odobren suradnik s aktivnom karticom i FBO brojem automatski dobiva svoju pozivnicu, bez pripreme.
2. Trajna kratka poveznica, primjerice `forevercard.club/hr/poziv/ab12cd`, uz QR kod i gumb Kopiraj. Ista poveznica radi u WhatsAppu, statusu, opisu profila i na kartici.
3. Na javnoj kartici gumb Zanima me suradnja vodi na tu pozivnicu, a AI savjetnik nudi samo poveznicu koja gostu stvarno radi.
4. PRO dodaje osobni video ili uvodnu poruku i detaljnu statistiku. Osnovna pozivnica jednaka je za sve.

### 4.2 Sadržaj stranice, mobilno, 3 do 4 minute

1. **Tko te poziva.** Ime, fotografija, kratka osobna poruka i WhatsApp.
2. **Kako se radi.** Preporuka i prodaja proizvoda te razvoj tima. Bez obećanja zarade, s poveznicom na službenu izjavu o prihodima.
3. **Što dobivaš u našem timu.** FCC aplikacija s osobnom karticom i AI savjetnikom, NFC kartica, Moj put s prvih 7 koraka odmah, edukacije i webinari, WhatsApp grupe i osobni mentor.
4. **Start Your Journey paket.** Slika, sadržaj, cijena za odabranu zemlju, 2 CC i uvjet do Assistant Supervisora. Glavni gumb Naruči Start paket otvara službenu prijavu s brojem sponzora. Uz gumb stoje ime i broj sponzora s gumbom Kopiraj, za slučaj da ga Forever zatraži.
5. **Što se događa nakon narudžbe.** Tri koraka: Forever šalje e-mail s FBO brojem, registracija u FCC s tim brojem i automatska potvrda sponzora, zatim javljanje sponzoru i početak Moj put.
6. **Pitanja prije odluke.** Kratka pitanja i odgovori te obrazac za upit kao zamjena kada osoba još nije spremna.

### 4.3 Praćenje i podrška sponzoru

1. Sponzor dobiva obavijest u Partner aplikaciji kada netko s njegove pozivnice klikne Naruči ili pošalje upit, uz zemlju i vrijeme.
2. Nova FCC registracija s potvrđenim sponzorom bilježi se kao stvarni rezultat pozivnice.
3. Jedan pregled za suradnika: otvaranja, klikovi na paket, upiti i potvrđene registracije.

### 4.4 Održavanje

1. Administrator dobiva podsjetnik 7 dana prije isteka provjere cijene i uvjeta paketa.
2. Jedan službeni video tima, najprije na hrvatskom. Stranica radi i bez videa.

### 4.5 Redoslijed provedbe

1. **Odmah:** ispravak AI savjetnika (bez poveznica koje gostu vraćaju 404).
2. **Prva faza:** nova verzija dopunjena dijelovima Što dobivaš u našem timu i Što se događa nakon narudžbe te prikazom broja sponzora. Zatim otvaranje svim suradnicima, uz zadržavanje starih poveznica.
3. **Druga faza:** trajna kratka poveznica i QR, gumb na javnoj kartici, obavijesti sponzoru i mjerenje potvrđenih registracija.
4. **Treća faza:** objedinjavanje izbornika Podijeli na tri jasna izbora (Pozivnica u tim, Proizvod, Webinar), službeni video i podsjetnik za provjeru paketa.

## 5. Povezana stavka: 120-dnevni plan

1. Članak `120-dnevni-plan` obećava osobni plan od više od 20 stranica u roku od 48 sati nakon upitnika. Modul AI plana zatvoren je za suradnike commitom 300355f, pa to obećanje više ne odgovara stvarnom programu.
2. Pojmovi se nalaze na sljedećim mjestima:
   1. Naslovnica u 13 jezika: `index.partners.card4.text`, `index.faq.three.answer` i `index.faq.four.*`.
   2. Baza: 25 blog članaka, 4 stranice `how-it-works` i dvije kartice Stjepanovog vodiča (funnel 36).
   3. Popis članaka AI savjetnika u `fcc_ai.php`.
3. Prijedlog je zamijeniti 120-dnevni plan stvarnim programom Moj put, koji ima 90 praktičnih koraka i prvih 7 otvorenih odmah. Tako javna obećanja, vodič i aplikacija govore isto.

## 6. Provedba

Korisnik je 12. listopada 2026. prihvatio sve tri faze, uz pravilo da PRO dodaje osobni video, uvod i detaljnu statistiku. Provedeno stanje i koraci nakon objave opisani su u `docs/STANJE_SUSTAVA.md`, odjeljak 5d. Nisu provedeni: službeni timski video (snima ga tim) i provjera Start paketa za Crnu Goru.
