# Osobno javljanje, ispravci evidencije

Objavljeno 30. rujna 2026. u 14:10 po zagrebačkom vremenu. Provedena je ciljana objava osam datoteka i aditivna dopuna tablice fcc_team_outreach s tri nullable polja. Postojećih 11 zapisa i dvije potvrde slanja sačuvani su pri objavi.

## Ponašanje

1. Povijest razdvaja pripremu, osobno potvrđena slanja i uklonjene zapise. Status nacrta je neutralan, nepotvrđenog slanja narančast, a potvrđenog slanja zelen.
2. Nakon otvaranja ili kopiranja poruke prikazuje se pitanje o stvarnom slanju s tri izbora. Povratak u aplikaciju i osvježavanje ne potvrđuju slanje. Odgoda vrijedi za trenutačnu sesiju i verziju zapisa.
3. Kopiranje sprema točan tekst poruke i omogućuje osobnu potvrdu i bez otvaranja WhatsAppa. Potvrda odbija tekst promijenjen nakon pripreme.
4. Poništavanje potvrde vraća zapis u nacrt i ažurira kontekst Coacha. Sačuvani ishod i završeno praćenje ostaju sačuvani.
5. Uklanjanje traži potvrdu unutar zapisa, skriva zapis iz aktivne povijesti, podsjetnika i konteksta Coacha. Vraćanje je dostupno u uklonjenim zapisima. Ne mijenja poruku u WhatsAppu.
6. Podsjetnici razlikuju provjeru slanja i provjeru ishoda razgovora. Nacrti i uklonjeni zapisi ne proizvode podsjetnik za razgovor.
7. Autorizacija po vlasniku, PRO pristup, aktualna linija, zaštita CSRF i provjera verzije ostaju obavezni. Nov tekst pripremljen nakon otvaranja ili kopiranja ima zaseban zapis. Novi tekstovi su dostupni na hrvatskom, engleskom, slovenskom, njemačkom i španjolskom.

## Provjera

Prošle su 32 postojeće provjere, 42 ciljane provjere novih radnji i privatnosti te 23 HTTP provjere. Preglednik je potvrdio kopiranje, odgodu, osvježavanje, nacrt, potvrdu slanja, poništavanje, uklanjanje i vraćanje. Mobilni prikaz pregledan je na širinama 390 i 360 piksela. Na širini 390 piksela dokument nema vodoravno prelijevanje. Stvarni iPhone, Android i isporuka kroz WhatsApp ostaju predmet korisničkog testiranja uživo.

Sintetički lokalni računi nakon provjere su deaktivirani, a njihove probne poruke uklonjene. Na produkciji nije slana poruka niti mijenjana postojeća evidencija radi testiranja.

## Objava i povratak

Privatni paket i sigurnosne kopije nalaze se u /home/forevercardclub/fcc-release-20260927/outreach-correction-20260930. Prije svake zamjene provjeren je prethodni SHA256, spremljena kopija i provjerena PHP sintaksa. Instalacija je odrađena u zasebnom procesu kroz postojeći worker, koji je odmah vraćen na izvornu datoteku. Raspored poslova nije mijenjan.

Poslužiteljska provjera potvrdila je shemu i prikaz profila. Neovisna provjera potvrdila je svih osam objavljenih datoteka i javno učitavanje CSS-a i JavaScripta. Stvarni prijavljeni produkcijski pregled pokazuje sve tri skupine evidencije bez JavaScript pogrešaka. Health potvrđuje dostupnu bazu i svježe izvršavanje crona.

Novi JavaScript ima zasebnu datoteku fcc-team-outreach-v2.js radi sigurne objave uz već otvorene starije stranice. Za testiranje nove verzije potrebno je osvježiti FCC.

Kod povratka vratiti datoteke iz privatnih before kopija prema manifestu. Dodatna polja ostaviti u bazi da bi se sačuvali eventualni novi zapisi i mogućnost ponovne objave. Prije povratka provjeriti je li u međuvremenu druga objava izmijenila iste datoteke.
