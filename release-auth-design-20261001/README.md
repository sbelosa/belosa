# Novi izgled prijave i registracije

Objavljeno 1. listopada 2026. u 16:51 po zagrebačkom vremenu.

Prijava i registracija koristile su basic_wrapper.php i vlastite tamne stilove u predlošcima. Ranije objavljivanje javnog dijela zato ih nije obuhvatilo. Zajednički predložak sada prisilno koristi svijetlu paletu samo za taj odgovor, ne mijenjajući spremljenu temu osobnih kartica. Uklonjeni su stari ugrađeni stilovi obrazaca, a izgled je objedinjen u fcc-auth.css uz postojeće javne stilove.

1. Novi izgled dobile su prijava, registracija, obnova i postavljanje lozinke te povezani zasloni aktivacije računa. Zajednički predložak koristi i postojeća stranica održavanja.
2. Sačuvana su sva polja, akcije obrazaca, Forever ID od 12 znamenki, CAPTCHA, skrivena zamka za botove, zaštitni tokeni, dvofaktorska provjera i postojeća pravila pristupa. Poslužiteljska logika prijave i registracije nije mijenjana.
3. Zajedničko zaglavlje, logo, odabir jezika, zelene akcije i svijetli obrasci prate novi javni FCC. Poveznice na naslovnicu, kontakt, obnovu lozinke i registraciju ostaju dostupne.
4. Prošlo je 18 lokalnih HTTP i strukturnih provjera te 25 produkcijskih provjera na hrvatskom, engleskom, slovenskom, njemačkom i španjolskom. Testiran je i zahtjev sa starim kolačićem tamne teme. Lokalno je potvrđeno uspješno prijavljivanje postojećeg suradnika i administratora. Produkcijski prijavljen korisnik i dalje ispravno odlazi u članski prostor.
5. Prošla je 131 izolirana sigurnosna provjera registracije bez upisa u bazu ili slanja poruka. Stari izolirani test ne uključuje postojeći pomoćnik fcc_t, pa je pri pokretanju dodan identitetski zamjenski pomoćnik. Produkcijski kod nije prilagođavan testu.
6. Prijava, registracija i obnova lozinke provjerene su na širinama 320, 390 i 768 piksela. Nema vodoravnog prelijevanja. U pregledniku su provjereni promjena jezika i prikaz lozinke. U lokalnoj konfiguraciji potvrda emaila nije uključena, pa su njezine javne adrese provjerene na produkciji, gdje jesu dostupne.
7. Provjeren je HTTP odgovor nove datoteke stilova i točno podudaranje sadržaja sve četiri objavljene datoteke. Objavljivanje je potvrdilo nepromijenjen sadržaj članaka, stranica i kategorija te nula upisa u bazu. Izvorni radnik obavijesti vraćen je i provjeren.

Prije objave spremljene su prethodne verzije svih triju postojećih datoteka. Nova datoteka stilova označena je before=null u manifestu. Provjerena sigurnosna kopija cijele baze i prethodna puna kopija aplikacije iz današnjeg objavljivanja ostaju dostupne. Privatne lokacije kopija nalaze se u publish-result.json. Povrat ovog paketa zahtijeva vraćanje triju prethodnih datoteka i uklanjanje nove datoteke fcc-auth.css. Bazu nije potrebno vraćati.

Snimke zaslona prikazuju lokalno provjeren konačni izgled. Produkcijske provjere izvedene su zasebnim HTTP klijentom bez prijave, kako postojeća prijava korisnika u pregledniku ne bi bila prekinuta. Na produkciji nisu stvarani testni računi niti slane poruke za obnovu lozinke.
