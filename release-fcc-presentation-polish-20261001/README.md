# Završno vizualno uređenje FCC aplikacije

Objavljeno 1. listopada 2026. u 19:21 po zagrebačkom vremenu.

1. Moji bodovi dobili su objedinjenu karticu mjeseca, jasnije CC pokazatelje i poravnate trake napretka. Kontrole mjeseca imaju jednaku visinu. Naslovi, metapodaci i povijest imaju dosljedne razmake.
2. Povezane kartice slijede vlastitu visinu. Na manjim ekranima sadržaj se slaže u jedan stupac. Trend bez podatka prikazuje prevedenu oznaku, bez umjetne trake za nulu.
3. Zajednički stilovi uređuju naslove, gumbe, postavke obavijesti, naslovnice FCC kartica i napredak prema pragovima klikova.
4. Poslovni izračuni, podaci, ovlasti i shema baze nisu mijenjani.

## Provjera

1. PHP provjera sintakse prošla je za oba predloška.
2. Četrnaest prijavljenih lokalnih HTTP zahtjeva prošlo je bez greške prikaza. Izbor mjeseca provjeren je i kroz obrazac u pregledniku.
3. Trinaest glavnih stranica pregledano je na širinama 360 i 1440 piksela. Bodovi, kartice i rezultati dodatno su provjereni na 320 i 768 piksela. Svih 32 provjere nemaju horizontalno izlijevanje sadržaja.
4. Vizualno su provjereni bodovi, kontakti, tim, radni prostor, postavke, obavijesti, početni ekran edukacije, Top, kartice i rezultati. Konzola nije prijavila JavaScript pogreške.
5. Produkcijski sadržaj svih četiriju datoteka odgovara manifestu. Oba javna CSS resursa vraćaju HTTP 200 i očekivani sadržaj. Privatni radnik vraćen je u prvotno stanje.
6. Produkcijska prijava u pregledniku istekla je prije provjere. Korisnik je zamoljen za ponovnu prijavu. Provjera rasporeda izvedena je u lokalnom okruženju; konačni prikaz sa stvarnim produkcijskim računom ostaje za provjeru nakon prijave.

## Objava i povrat

Objavljene su isključivo četiri datoteke iz manifesta. Svaki prethodni sadržaj spremljen je izvan javnog direktorija. Prije zamjene provjereni su hash vrijednosti i sintaksa. Promjene su zapisane atomskim zamjenama uz mogućnost povrata iz sigurnosne kopije. Privatne pristupne datoteke i snimke korisničkih ekrana nisu dio ovog paketa.
