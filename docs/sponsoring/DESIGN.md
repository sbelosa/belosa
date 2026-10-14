---
name: "FCC osobno sponzoriranje"
description: "Vizualni sustav nove javne osobne poslovne prezentacije, izdvojen iz ostatka FCC aplikacije."
colors:
  sp-ink: "#143a2d"
  sp-muted: "#53675b"
  sp-green: "#186548"
  sp-deep: "#103d2b"
  sp-cream: "#f7f7ee"
  sp-paper: "#fffef9"
  sp-lime: "#d2ec87"
  sp-line: "#d4ddcf"
typography:
  display:
    fontFamily: "DM Sans, sans-serif"
    fontSize: "clamp(60px, 5.7vw, 86px)"
    fontWeight: 600
    lineHeight: 1.045
    letterSpacing: "-0.035em"
  headline:
    fontFamily: "DM Sans, sans-serif"
    fontSize: "clamp(32px, 3.3vw, 49px)"
    fontWeight: 550
    lineHeight: 1.14
    letterSpacing: "-0.03em"
  title:
    fontFamily: "DM Sans, sans-serif"
    fontSize: "24px"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "-0.02em"
  body:
    fontFamily: "DM Sans, sans-serif"
    fontSize: "17px"
    fontWeight: 400
    lineHeight: 1.65
  lead:
    fontFamily: "DM Sans, sans-serif"
    fontSize: "20px"
    fontWeight: 400
    lineHeight: 1.65
  label:
    fontFamily: "DM Sans, sans-serif"
    fontSize: "15px"
    fontWeight: 650
    lineHeight: 1.35
rounded:
  field: "6px"
  action: "9px"
  panel: "12px"
  profile: "17px"
  card: "34px"
  phone: "43px"
spacing:
  compact: "8px"
  control: "12px"
  related: "20px"
  panel: "32px"
  group: "48px"
  hero: "70px"
  section: "100px"
components:
  button-primary:
    backgroundColor: "{colors.sp-green}"
    textColor: "#ffffff"
    typography: "{typography.label}"
    rounded: "{rounded.action}"
    padding: "15px 23px"
    height: "56px"
  button-primary-hover:
    backgroundColor: "#0e4e36"
  button-lime:
    backgroundColor: "{colors.sp-lime}"
    textColor: "{colors.sp-deep}"
    typography: "{typography.label}"
    rounded: "{rounded.action}"
    padding: "15px 23px"
    height: "56px"
  button-lime-hover:
    backgroundColor: "#e0f4ac"
  input:
    backgroundColor: "{colors.sp-paper}"
    textColor: "{colors.sp-ink}"
    rounded: "{rounded.field}"
    padding: "10px 12px"
    height: "46px"
    width: "100%"
  form-panel:
    backgroundColor: "{colors.sp-paper}"
    textColor: "{colors.sp-ink}"
    rounded: "{rounded.panel}"
    padding: "36px 35px"
  owner-profile:
    backgroundColor: "{colors.sp-deep}"
    textColor: "{colors.sp-paper}"
    rounded: "{rounded.profile}"
    padding: "20px 12px 18px"
  personal-card:
    backgroundColor: "#fffef8"
    textColor: "{colors.sp-ink}"
    rounded: "{rounded.card}"
    padding: "47px 23px 21px"
  product-proof:
    backgroundColor: "#e8eddf"
    textColor: "{colors.sp-ink}"
    rounded: "{rounded.panel}"
    padding: "32px 35px"
  invitation-proof:
    backgroundColor: "#e0e9cd"
    textColor: "{colors.sp-ink}"
    rounded: "{rounded.panel}"
    padding: "33px 35px"
---

# Design System: FCC osobno sponzoriranje

## Overview

**Creative North Star: "Osobna kartica u prvom planu"**

Topla svijetla podloga, duboka šumska zelena i velika stvarna osobna kartica čine mirnu, izražajnu prezentaciju poslovne prilike. Urednička tipografija daje početku težinu, dok fotografija vlasnika, preporuka konkretnog proizvoda i osobna pozivnica daju sadržaju vjerodostojan identitet. Ritam izmjenjuje otvorene odjeljke, zelene površine i čitljive kratke korake.

Ovaj dokument opisuje samo novu javnu osobnu prezentaciju iz `themes/altum/views/partner/sponsoring-public.php`. Preuzima postojeću FCC zelenu obitelj, logotip i DM Sans te zapisuje vlastitu kompoziciju i izmjerene vrijednosti te površine. Ne zamjenjuje globalni dizajn FCC aplikacije, privatni Partner prostor, odabrane teme osobnih kartica ili njihov postojeći dizajnerski autoritet. Radni prostor za dijeljenje, PDF i produkcijski postupci imaju zasebne provjere.

**Key Characteristics:**

1. Velika kartica sa stvarnim identitetom i radnjama.
2. Topla svijetla podloga uz izražajnu FCC zelenu.
3. Jedna obitelj slova s jasnom razlikom naslova i pomoćnog teksta.
4. Konkretna preporuka i osobna pozivnica umjesto generičkih zamjenskih plakata.
5. Kratak mobilni uvod koji odmah pokazuje vlasnika kartice.

## Colors

Paleta spaja tople papirnate površine s tamnom zelenom, a svijetli limetni naglasak koristi samo gdje podupire radnju na tamnoj površini. Normativne vrijednosti nalaze se u frontmatteru i odgovaraju varijablama na `.sp-page` u CSS izvoru.

### Primary

1. `sp-green` je glavna akcijska zelena za pozive na razgovor, kontakt u kartici i naglašeni dio velikog naslova.
2. `sp-deep` je duboka šumska zelena za profil vlasnika, odjeljak učenja i mobilni poziv na radnju.
3. `sp-lime` je svijetli naglasak za gumb na tamnom odjeljku i označavanje odabranog teksta.

### Neutral

1. `sp-cream` je ukupna topla podloga stranice.
2. `sp-paper` je čista topla površina obrasca i jezičnog izbornika, uz svijetli tekst na tamnom profilu.
3. `sp-ink` nosi osnovni tekst i naslove.
4. `sp-muted` nosi opise, prateće upute i sekundarni sadržaj.
5. `sp-line` razdvaja navigaciju, pitanja i unutarnje cjeline bez snažnih okvira.

**Pravilo identiteta.** Izvorni FCC logotip i stvarne fotografije proizvoda zadržavaju svoje boje. Zelena pozadina nije razlog za bojanje ili izradu zamjenskih pakiranja.

## Typography

**Display Font:** DM Sans, uz `sans-serif` kao pričuvni niz.

**Body Font:** DM Sans. Isti font nosi kontrole i navigaciju.

Lokalne datoteke `dm-sans-latin.woff2` i `dm-sans-latin-ext.woff2` učitavaju promjenjivu debljinu od 100 do 1000 i koriste `font-display: swap`. Datoteka `OFL.txt` prati postojeću FCC obitelj. Opisi su mirni i razgovorni, a naslovi kompaktni, s blago zgusnutim razmakom slova. Ne postoji zasebna dekorativna ili monospace obitelj.

### Hierarchy

1. `display` je glavni naslov na velikom zaslonu, s izražajnom debljinom i bliskim retcima. Naslovi koriste `text-wrap: balance`.
2. `headline` nosi glavne odjeljke. Unutarnje komponente po potrebi imaju lokalnu izmjerenu veličinu iz CSS izvora.
3. `title` nosi naslove manjih sadržajnih cjelina.
4. `body` je osnovni tekst. Uvodni opis koristi `lead`, s najvećom širinom od 470 px na računalu.
5. `label` opisuje glavni gumb. Navigacija koristi 14 px i debljinu 600, a pomoćni tekst namjerno je sitniji od glavnih objašnjenja.

Na mobitelu osnovni tekst postaje 16 px, glavni naslov 46 px s proredom 1.03 i razmakom slova od `-0.03em`, a pri širini do 365 px naslov je 42 px. Mobilni kratki opis koristi 15 px i prored 1.6, odnosno 14 px pri najužem prijelomu. To su stvarna završna nadjačavanja, a ne proporcionalno smanjivanje cijele stranice.

## Layout

Osnovni sadržaj ima maksimalnu širinu 1240 px i širinu `calc(100% - 112px)`. Zaglavlje ima maksimalno 1328 px. Početak na računalu koristi dva stupca omjera 1.13 prema 1, razmak iz tokena `hero` i okomiti razmak 54 px iznad te 66 px ispod sadržaja. Druge velike cjeline koriste izmjenu dvaju stupaca i odjeljaka pune širine. Tri koraka rada i tri preporuke proizvoda dijele red bez zasebnog okvira oko svakog objašnjenja.

Stvarni prijelomi su 1550, 1100, 850, 620 i 365 px. Pri širini do 1100 px ukupni bočni odmak postaje 72 px, do 850 px 48 px, do 620 px 40 px, a do 365 px 32 px. Navigacija odjeljaka nestaje do 850 px. Mobitel zadržava logotip, jezik i simbol kontakta. Do 620 px dvostupčane cjeline prelaze u čitljiv jedan stupac, dok tri proizvoda ostaju usporedivi u zajedničkom redu.

**Pravilo prvog mobilnog dokaza.** Pri 390 × 844 i 360 × 800 px početak mora pokazati ime vlasnika i cijelu prvu radnju unutar kartice bez pomicanja. Završna mobilna kompozicija slaže osobni uvod, kratak naslov, kratko objašnjenje, karticu, glavni gumb, sekundarnu poveznicu i prateću rečenicu. Kartica se ne smanjuje da bi se sačuvali veliki slogani iz računalnog prikaza.

Okvir telefona širok je 416 px na računalu. Do 1100 px koristi 390 px, a na mobitelu ciljnu širinu 370 px uz `max-width: 100%`. Završne izmjerene širine iz lokalnog dokaznog skupa su 350 px na zaslonu od 390 px i 328 px na zaslonu od 360 px. U najduljem njemačkom mobilnom slučaju cijela prva radnja završava na približno 796 px unutar visine od 800 px.

Pri ispisu se skrivaju zaglavlje, mobilna radnja, obrazac i navigacija podnožja. Pozadine se pojednostavljuju, sjenke i zakretanje se uklanjaju, a cjeline nastoje ostati zajedno. Ovo ponašanje CSS ispisa ne potvrđuje izgled zasebno generiranog PDF dokumenta.

## Elevation & Depth

Dubinu stvaraju tonske razlike i nekoliko namjenskih sjena. Okvir telefona ima strukturnu sjenu `0 30px 48px #18372b24`, odnosno `0 19px 35px #18372b24` na mobitelu. Jezični izbornik koristi `0 12px 30px #103d2b26`. List učenja ima `0 23px 30px #07281e36` i mali zakret. Pritisak i lebdenje glavnog gumba ne podižu cijeli odjeljak, nego pojačavaju njegovu površinu sjenom `0 7px 18px #103d2b20`.

Telefon ulazi samo jednom tijekom 0.8 s uz `cubic-bezier(.16,1,.3,1)`, pomak od 12 px i otkrivanje obrisa. To nije stalna animacija. Gumbi mijenjaju boju i sjenu tijekom 0.2 s. Fotografija proizvoda pri lebdenju pomiče se 4 px prema gore tijekom 0.25 s. Otvoreno pitanje zakreće znak za 45 stupnjeva. Postavka `prefers-reduced-motion: reduce` uklanja animacije, prijelaze i animirano pomicanje.

**Pravilo namjenske dubine.** Sjena razlikuje telefon, plutajuću radnju, izbornik ili list učenja od njihove okoline. Uobičajeni sadržajni odjeljci ostaju otvoreni i bez vlastite sjene.

## Shapes

Kontrole imaju umjerene kutove, paneli su mekši, a telefon ima najizraženije zaobljenje. Polja koriste token `field`, glavni gumbi `action`, a sadržajni paneli `panel`. Unutarnja osobna kartica koristi `card`, dok vanjski telefon koristi `phone`. Na mobitelu se ta dva radijusa mijenjaju u 29 i 37 px. Profil vlasnika koristi 17 px na računalu i 15 px na mobitelu.

Fotografija vlasnika kružno se izrezuje uz `object-fit: cover`. Pakiranja proizvoda ostaju cijela uz `object-fit: contain` i `mix-blend-mode: multiply`. Organska zelena podloga iza telefona ima vlastitu asimetričnu konturu. Taj oblik je potpora velikoj kartici, a ne okvir za svaki drugi odjeljak.

## Components

### Buttons

Glavni gumb je puna akcijska površina, čitljiv naziv i jednostavna linijska strelica. Normativne osnovne vrijednosti nalaze se u komponentama frontmattera. Limetni gumb pripada tamnom odjeljku učenja. Tekstualne poveznice koriste debljinu i malu ikonu, bez dodatne pune kartice.

Sve poveznice, gumbi, sažeci i polja imaju vidljiv fokus: puni obris od 3 px u boji `#2c8861`, odmaknut 5 px od ruba. Završna mobilna glavna radnja ima minimalnu visinu 52 px. Kartične radnje imaju minimalno 45 px i odvojenu kontaktnu te trgovinsku površinu.

### Cards / Containers

Osobna kartica je stvaran HTML prikaz vlasnika i postojećih podataka, a ne jedna rasterska slika sučelja. Sadrži fotografiju ili inicijale, ime, ulogu, opis, kontakt, dostupnu trgovinu, do tri proizvoda, dostupne društvene poveznice i otvaranje pune kartice. Tamni profil pojačava identitet, a svijetli donji dio jasno odvaja radnje i proizvode.

Prikaz preporuke koristi prvi dostupni proizvod, njegovu postojeću fotografiju, ime vlasnika i postojeću osobnu poveznicu. Bez proizvoda prikazuje se osobna kartica. Osobna pozivnica prikazuje stvarnog vlasnika, adresu te javne prezentacije i radnju za razgovor. Ti prikazi nisu dokaz izmišljenog događaja, rezultata ili nove funkcije.

### Inputs / Fields

Polja su pune širine, tople podloge, s rubom `#b9c9b1`, unutarnjim razmakom iz frontmattera i vidljivim nazivom iznad polja. Mobilna slova polja imaju 16 px, a minimalna visina 47 px. Odabir kanala koristi dvije oznake s radio kontrolama. Odabrana ima podlogu `#e9efdf` i rub `#66835a`.

JavaScript pokazuje samo polje odabranog kanala, čini ga obveznim i isključuje drugo polje. Tijekom valjanog slanja gumb postaje nedostupan i dobiva `aria-busy`. Povratak iz predmemorije stranice vraća njegov početni sadržaj i dostupnost. Pogreška ostaje vidljiv tekst s `role="alert"`, toplom podlogom `#fae7dc` i tekstom `#7b3423`; dobiva fokus i dovodi se u vidljivi dio. Uspjeh zamjenjuje obrazac porukom s `role="status"` i dostupnim sljedećim poveznicama.

### Navigation

Zaglavlje razdvaja logotip, odjeljke, jezik i kontakt. Jezični izbornik je izvorni `details` element s trenutačnim jezikom. Klik izvan izbornika zatvara ga, a Escape ga zatvara i vraća fokus na njegov sažetak. Promjena jezika čuva odabranu zemlju.

Mobilni poziv na razgovor pojavljuje se nakon izlaska glavnog naslova iz vidljivog dijela i nestaje kada se pokaže kontakt. Skrivena radnja nema tipkovnički fokus. Poštuje donji sigurni prostor uređaja. Nije trajno prekrivanje kontaktnih polja.

### Questions

Pitanja koriste izvorne `details` i `summary` elemente s jasnim vodoravnim razdjelnicima. Otvaranje zadržava pitanje na mjestu i pokazuje objašnjenje ispod njega. Znak plus zakreće se bez promjene značenja pitanja.

## Do's and Don'ts

### Do:

1. Sačuvaj veliku stvarnu osobnu karticu i ranu mobilnu vidljivost imena vlasnika i prve radnje.
2. Koristi postojeći FCC logotip, DM Sans i provjerene fotografije vlasnika i proizvoda.
3. Poveži prikaz preporuke i pozivnice s postojećim osobnim poveznicama i stvarnim sadržajem.
4. Provjeri svih pet jezika, dugo ime, prazno stanje, pogrešku i uspjeh na utvrđenim širinama.
5. Sačuvaj vidljivi fokus, izvorne kontrole i postavku smanjenog kretanja.
6. Primjenjuj ove tokene samo na novu javnu osobnu prezentaciju.

### Don't:

1. Ne vraćaj odbačenu premalu karticu ili mobilni uvod koji odgađa glavni osobni dokaz.
2. Ne zamjenjuj konkretnu preporuku i osobnu pozivnicu velikim generičkim FCC plakatima.
3. Ne izrađuj zamjenske fotografije pakiranja, vlasnika, događaja ili svjedočanstava.
4. Ne prenosi ovu kompoziciju i vrijednosti na cijelu postojeću FCC aplikaciju ili tuđe osobne teme.
5. Ne tumači lokalne vizualne snimke kao potvrdu produkcijske isporuke, PDF prikaza ili slanja upita.
