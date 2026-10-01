# Jedinstveni javni izgled FCC-a, 1. listopada 2026.

Izmjene su objavljene u 16:26 po zagrebačkom vremenu. Novi javni dizajn dostupan je posjetiteljima i prijavljenim suradnicima.

Uzrok starog prikaza bio je odvojeno uključivanje članskog i javnog dijela. Novi katalog na produkciji tražio je prijavu i pristup novom radnom prostoru. Pretraga, stranice rezultata i kategorije vraćale su stari predložak, a poveznica s parametrom design=classic izričito ga je uključivala. Javna naslovnica nije bila dio prethodne migracije. Produkcija ne sprema javni HTML u predmemoriju, a članski service worker propušta blog i javne stranice izravno mreži.

1. Nova naslovnica, zaglavlje, mobilni izbornik i podnožje dijele boje i vizualni stil novog radnog prostora.
2. Javni katalog i prikaz proizvoda dostupni su bez prijave. Stari parametar design=classic više ne uključuje stari izgled. Članski pristup i ovlasti nisu prošireni javnim posjetiteljima.
3. Arhiva članaka, pretraga, kategorije i stranice rezultata koriste novi prikaz. Prelazak na sljedeću stranicu zadržava pretragu, preporuku suradnika i odabrani prikaz.
4. Postojeći članci, informativne stranice, kontakt, istaknute aplikacije i preporučeni sponzori dobili su zajednički javni stil. Kriteriji odabira istaknutih suradnika i sponzora ostali su isti.
5. Javni prikaz naslovnice može se otvoriti kroz ?view=public bez odjave. Uobičajeni ulazak prijavljenog suradnika i dalje vodi u njegov radni prostor. Odgovor za prijavljenog korisnika ne sprema se u zajedničku predmemoriju.
6. Novi tekstovi postoje na hrvatskom, engleskom, slovenskom, njemačkom i španjolskom. Stranice i postojeći sadržaji svih devet aktivnih jezika zadržani su na svojim adresama.
7. Osobne kartice zadržavaju vlastite odabrane teme. Zajedničke postojeće datoteke stilova ostaju dostupne dijelovima aplikacije koji ih koriste. Promjena dizajna nije zahtijevala brisanje sadržaja, korisnika, plaćanja ili napretka.

Provjere:

1. Lokalno je prošlo 1.510 provjera adresa, uključujući 1.472 objavljene adrese sadržaja.
2. Na produkciji je prošlo 1.283 provjera adresa, uključujući svih 1.245 objavljenih adresa članaka, stranica i kategorija iz produkcijskog popisa. Dodatno je provjereno 146 slika, skripti i stilova s produkcijske domene.
3. Provjera 115 proizvoda u hrvatskoj i engleskoj verziji obuhvatila je 230 lokalnih stranica, metapodatke, fotografije, izvorni sadržaj, povezane proizvode, valute i cijene u strukturiranim podacima. Lokalni katalog sadrži više proizvoda od produkcijskog, pa lokalni dodatni sadržaji nisu objavljivani ovim paketom.
4. Prošla su 103 postojeća HTTP testa članskog prostora, 46 testova upravljanog pristupa i 24 provjere očuvanja sadržaja i javnog uključivanja knjižnice. Posebno su provjereni javni pregled za suradnika i administratora, povratak u radni prostor i privatna predmemorija odgovora.
5. Na širinama 320, 390 i 768 piksela provjereno je devet tipova lokalnih javnih stranica. Osam tipova produkcijskih stranica i prikaz proizvoda provjereni su na 390 piksela, bez vodoravnog prelijevanja. U pregledniku nema zabilježenih JavaScript grešaka pri završnoj provjeri.
6. Šest produkcijskih kombinacija proizvoda i zemlje zadržava istog preporučitelja i Forever ID. Hrvatski odredišni URL identičan je prethodnom. Izričit odabir Njemačke sada otvara već spremljenu njemačku ponudu, dok ga je stari javni prikaz zanemarivao i vraćao hrvatsku ponudu. Nijedna kupnja, poruka ni plaćanje nije izvršeno u testu.
7. Sintaksa svih objavljenih PHP datoteka i kontrolni sažeci svih 19 produkcijskih datoteka provjereni su. Usporedba sadržaja prije i nakon objave potvrdila je nepromijenjenih 891 zapisa članaka, 252 stranice, 113 kategorija članaka i devet kategorija stranica. Brojači pregleda izuzeti su iz sažetka jer se normalno mijenjaju posjetom.

Sigurnosne kopije:

Nova kopija cijele baze obuhvaća 150 tablica. Provjeren je završetak SQL izvoza, broj tablica, gzip i SHA256. Datoteka ima 22.769.392 bajta i sažetak 2ecf7b1c1eea0ab7aed989378add083ac8b6604839c6f0e3e7c16e0cfbc620a9. Kopija postoji u privatnom prostoru servera i lokalno uz ovlasti 0600. Ranija provjerena puna kopija aplikacijskih datoteka ostaje dostupna.

Prije zamjene spremljena je svaka postojeća datoteka iz ovog paketa. Točne privatne adrese kopija nalaze se u publish-result.json. Objavljivanje nije sadržavalo upite koji mijenjaju bazu. Privremeni poslužiteljski posao završio je i izvorni radnik za obavijesti vraćen je i provjeren.

Za buduće objavljivanje koristiti samo datoteke iz mape payload i provjeriti kontrolne sažetke iz manifest.json. Ostatak radnog direktorija za izdavanje nije kopija cijele trenutačne produkcije. Za povrat vratiti prethodne datoteke iz evidentirane privatne kopije i ukloniti isključivo nove datoteke označene vrijednošću before=null u manifestu. Bazu nije potrebno vraćati za povrat dizajna.

Napomena o starim testovima: raniji test knjižnice očekuje da proizvodi nikada nemaju strukturirane ponude. Taj uvjet prethodi postojećem prikazu potvrđenih cijena. Umjesto njega korišten je potpuniji test proizvoda koji uspoređuje prikazanu cijenu, zemlju, valutu i odredište s njihovim strukturiranim podacima. Nisu mijenjane cijene radi prolaska testa.

Završna dorada za tražilice objavljena je u 16:37 po zagrebačkom vremenu. Katalog, arhiva, pretraga i kategorije sadrže strukturirane podatke BreadcrumbList, CollectionPage i ItemList. Stavke koriste stalne adrese bez oznake preporučitelja, a broj stavki odgovara stvarno prikazanim rezultatima. Prošlo je 25 ciljanih produkcijskih provjera na pet jezika. Ponovno su uspoređeni kontrolni sažeci svih 19 datoteka s konačnim izvornim kodom. Sadržaj baze ostao je nepromijenjen, a izvorni radnik obavijesti vraćen je i provjeren.

Konačni manifest.json opisuje trenutačno objavljene datoteke. initial-manifest.json čuva stanje prvog objavljivanja, a seo-manifest.json i seo-publish-result.json opisuju tri datoteke završne dorade i njihovu zasebnu sigurnosnu kopiju. Za potpuni povrat ovog paketa koristi se izvorna kopija iz publish-result.json.
