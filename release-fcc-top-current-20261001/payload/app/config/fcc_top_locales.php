<?php
defined('ALTUMCODE') || die();
return json_decode(<<<'JSON'
{
 "title": {
  "hr": "FCC Top 10",
  "en": "FCC Top 10",
  "sl": "FCC Top 10",
  "de": "FCC Top 10",
  "es": "FCC Top 10"
 },
 "intro": {
  "hr": "Prepoznaj dobar rad. Prati vlastiti napredak.",
  "en": "Recognise good work. Follow your own progress.",
  "sl": "Prepoznaj dobro delo. Spremljaj svoj napredek.",
  "de": "Gute Arbeit anerkennen. Den eigenen Fortschritt verfolgen.",
  "es": "Reconoce el buen trabajo. Sigue tu progreso."
 },
 "menu_description": {
  "hr": "Rang liste, proknjiženi CC i moj napredak.",
  "en": "Rankings, posted CC and my progress.",
  "sl": "Lestvice, knjižene točke CC in moj napredek.",
  "de": "Ranglisten, verbuchte CC und mein Fortschritt.",
  "es": "Clasificaciones, CC contabilizados y mi progreso."
 },
 "personal_cc": {
  "hr": "Osobni CC",
  "en": "Personal CC",
  "sl": "Osebni CC",
  "de": "Persönliche CC",
  "es": "CC personales"
 },
 "total_cc": {
  "hr": "Ukupna CC aktivnost",
  "en": "Total CC activity",
  "sl": "Skupna aktivnost CC",
  "de": "Gesamte CC Aktivität",
  "es": "Actividad CC total"
 },
 "recommendations": {
  "hr": "Poslane preporuke proizvoda",
  "en": "Product recommendations sent",
  "sl": "Poslana priporočila izdelkov",
  "de": "Gesendete Produktempfehlungen",
  "es": "Recomendaciones de productos enviadas"
 },
 "invitations": {
  "hr": "Potvrđeno slanje pozivnica",
  "en": "Confirmed invitation sending",
  "sl": "Potrjeno pošiljanje vabil",
  "de": "Bestätigter Einladungsversand",
  "es": "Envío de invitaciones confirmado"
 },
 "registrations": {
  "hr": "Potvrđene prijave gostiju",
  "en": "Verified guest registrations",
  "sl": "Potrjene prijave gostov",
  "de": "Bestätigte Gastanmeldungen",
  "es": "Inscripciones verificadas de invitados"
 },
 "conversations": {
  "hr": "Pokrenute komunikacije",
  "en": "Conversations started",
  "sl": "Začeti pogovori",
  "de": "Begonnene Gespräche",
  "es": "Conversaciones iniciadas"
 },
 "education": {
  "hr": "Završeni koraci edukacije",
  "en": "Completed learning steps",
  "sl": "Zaključeni izobraževalni koraki",
  "de": "Abgeschlossene Lernschritte",
  "es": "Pasos de formación completados"
 },
 "consistency": {
  "hr": "Dani aktivnog rada",
  "en": "Days of active work",
  "sl": "Dnevi aktivnega dela",
  "de": "Tage mit aktiver Arbeit",
  "es": "Días de trabajo activo"
 },
 "personal_cc_help": {
  "hr": "Proknjiženi osobni CC iz posljednje potvrđene FLP360 sinkronizacije za odabrani mjesec.",
  "en": "Posted personal CC from the latest verified FLP360 sync for the selected month.",
  "sl": "Knjiženi osebni CC iz zadnje potrjene sinhronizacije FLP360 za izbrani mesec.",
  "de": "Verbuchte persönliche CC aus der letzten bestätigten FLP360 Synchronisierung für den gewählten Monat.",
  "es": "CC personales contabilizados de la última sincronización verificada de FLP360 del mes seleccionado."
 },
 "total_cc_help": {
  "hr": "Ukupni CC za odabrani kalendarski mjesec. Izvorno polje Total CC. Osobni CC se ne dodaje ponovno. Ovo nije zaseban izračun sponzorskih CC.",
  "en": "Total CC for the selected calendar month. The original Total CC field. Personal CC is not added again. This is not a separate calculation of sponsorship CC.",
  "sl": "Skupni CC za izbrani koledarski mesec. Izvirno polje Total CC. Osebnih CC ne prištevamo ponovno. To ni ločen izračun sponzorskih CC.",
  "de": "Gesamt CC für den ausgewählten Kalendermonat. Das ursprüngliche Feld Total CC. Persönliche CC werden nicht erneut addiert. Dies ist keine separate Berechnung von Sponsoring CC.",
  "es": "CC totales del mes natural seleccionado. El campo original Total CC. No se vuelven a sumar los CC personales. No es un cálculo separado de CC de patrocinio."
 },
 "recommendations_help": {
  "hr": "Slanje potvrđeno u programu Moj put, uz kontakt i preporuku. Isti kontakt i proizvod broje se jednom u razdoblju. Nepovezane preporuke nisu uključene.",
  "en": "Sending confirmed in My journey, linked to a contact and recommendation. The same contact and product count once per period. Unlinked recommendations are excluded.",
  "sl": "Pošiljanje potrjeno v Moji poti, povezano s kontaktom in priporočilom. Isti kontakt in izdelek štejeta enkrat v obdobju. Nepovezana priporočila so izključena.",
  "de": "In Mein Weg bestätigter Versand mit Kontakt und Empfehlung. Derselbe Kontakt und dasselbe Produkt zählen einmal pro Zeitraum. Nicht zugeordnete Empfehlungen zählen nicht.",
  "es": "Envío confirmado en Mi recorrido con contacto y recomendación. El mismo contacto y producto cuentan una vez por periodo. Se excluyen recomendaciones sin vincular."
 },
 "invitations_help": {
  "hr": "Suradnik je označio da je poslao pozivnicu imenovanom kontaktu. Isti kontakt i događaj broje se jednom. Kopiranje i otvaranje WhatsAppa ne broje se.",
  "en": "The member confirmed sending an invitation to a named contact. Each contact and event count once. Copying and opening WhatsApp do not count.",
  "sl": "Sodelavec je potrdil pošiljanje vabila imenovanemu kontaktu. Isti kontakt in dogodek štejeta enkrat. Kopiranje in odpiranje WhatsAppa ne štejeta.",
  "de": "Das Mitglied hat den Versand an einen benannten Kontakt bestätigt. Kontakt und Veranstaltung zählen einmal. Kopieren und Öffnen von WhatsApp zählen nicht.",
  "es": "El colaborador confirmó el envío a un contacto identificado. Cada contacto y evento cuentan una vez. Copiar y abrir WhatsApp no cuentan."
 },
 "registrations_help": {
  "hr": "Gost je potvrdio adresu e pošte. Probne, vlastite, otkazane i prijave za provjeru nisu uključene. Prijava nije dokaz dolaska.",
  "en": "The guest verified their email address. Demo, self, cancelled and review flagged registrations are excluded. Registration does not prove attendance.",
  "sl": "Gost je potrdil elektronski naslov. Poskusne, lastne, odpovedane in prijave za pregled so izključene. Prijava ni dokaz udeležbe.",
  "de": "Der Gast hat seine E Mail Adresse bestätigt. Testanmeldungen, eigene, stornierte und zu prüfende Anmeldungen sind ausgeschlossen. Eine Anmeldung belegt keine Teilnahme.",
  "es": "El invitado verificó su correo electrónico. Se excluyen pruebas, autorregistros, cancelaciones e inscripciones pendientes de revisión. La inscripción no demuestra asistencia."
 },
 "conversations_help": {
  "hr": "Broj različitih kontakata s potvrđenim javljanjem ili radnjom u programu. Jedan kontakt broji se jednom u razdoblju. Odgovor druge osobe nije potvrđen ovim brojem.",
  "en": "Distinct contacts with a confirmed contact action or journey action. Each contact counts once per period. This number does not confirm a reply.",
  "sl": "Različni kontakti s potrjenim javljanjem ali dejanjem v programu. Vsak šteje enkrat v obdobju. Število ne potrjuje odgovora.",
  "de": "Verschiedene Kontakte mit bestätigter Kontaktaufnahme oder Programmaktion. Jeder Kontakt zählt einmal pro Zeitraum. Die Zahl bestätigt keine Antwort.",
  "es": "Contactos distintos con una acción de contacto o del programa confirmada. Cada contacto cuenta una vez por periodo. La cifra no confirma una respuesta."
 },
 "education_help": {
  "hr": "Jedinstveni završeni koraci, uključujući priznatu vježbu. Otvaranje lekcije ne računa se kao završetak.",
  "en": "Unique completed steps, including accepted practice. Opening a lesson does not count as completion.",
  "sl": "Edinstveni zaključeni koraki, vključno s priznano vajo. Odprtje lekcije ne šteje kot zaključek.",
  "de": "Einmalige abgeschlossene Schritte einschließlich anerkannter Übungen. Das Öffnen einer Lektion zählt nicht als Abschluss.",
  "es": "Pasos completados únicos, incluida la práctica aceptada. Abrir una lección no cuenta como completarla."
 },
 "consistency_help": {
  "hr": "Različiti dani s potvrđenom komunikacijom, prema zagrebačkom vremenu.",
  "en": "Distinct days with confirmed contact activity, using Zagreb time.",
  "sl": "Različni dnevi s potrjeno komunikacijo po zagrebškem času.",
  "de": "Verschiedene Tage mit bestätigter Kontaktaktivität nach Zagreber Zeit.",
  "es": "Días distintos con actividad de contacto confirmada, según la hora de Zagreb."
 },
 "period": {
  "hr": "Razdoblje aktivnosti",
  "en": "Activity period",
  "sl": "Obdobje aktivnosti",
  "de": "Aktivitätszeitraum",
  "es": "Periodo de actividad"
 },
 "days": {
  "hr": "{n} dana",
  "en": "{n} days",
  "sl": "{n} dni",
  "de": "{n} Tage",
  "es": "{n} días"
 },
 "cc_month": {
  "hr": "Mjesec za CC",
  "en": "CC month",
  "sl": "Mesec za CC",
  "de": "Monat für CC",
  "es": "Mes de CC"
 },
 "apply": {
  "hr": "Prikaži",
  "en": "Show",
  "sl": "Prikaži",
  "de": "Anzeigen",
  "es": "Mostrar"
 },
 "own": {
  "hr": "Moj rezultat",
  "en": "My result",
  "sl": "Moj rezultat",
  "de": "Mein Ergebnis",
  "es": "Mi resultado"
 },
 "own_rank": {
  "hr": "Moje mjesto među svim aktivnim rezultatima: {n}.",
  "en": "My place among all positive results: {n}.",
  "sl": "Moje mesto med vsemi pozitivnimi rezultati: {n}.",
  "de": "Mein Platz unter allen positiven Ergebnissen: {n}.",
  "es": "Mi puesto entre todos los resultados positivos: {n}."
 },
 "you": {
  "hr": "Ti",
  "en": "You",
  "sl": "Ti",
  "de": "Du",
  "es": "Tú"
 },
 "private_cc": {
  "hr": "Iznos je privatan",
  "en": "Amount is private",
  "sl": "Znesek je zaseben",
  "de": "Betrag ist privat",
  "es": "El importe es privado"
 },
 "no_score": {
  "hr": "Još nema rezultata za rangiranje.",
  "en": "No result to rank yet.",
  "sl": "Rezultata za uvrstitev še ni.",
  "de": "Noch kein Ergebnis für die Rangliste.",
  "es": "Todavía no hay resultados para clasificar."
 },
 "empty": {
  "hr": "Još nema vidljivih sudionika u ovoj kategoriji. Svoju vidljivost možeš uključiti u postavkama.",
  "en": "No visible participants in this category yet. You can enable your visibility in settings.",
  "sl": "V tej kategoriji še ni vidnih udeležencev. Vidnost lahko vključiš v nastavitvah.",
  "de": "Noch keine sichtbaren Teilnehmer in dieser Kategorie. Du kannst deine Sichtbarkeit in den Einstellungen aktivieren.",
  "es": "Todavía no hay participantes visibles en esta categoría. Puedes activar tu visibilidad en los ajustes."
 },
 "rules": {
  "hr": "Kako se broji",
  "en": "How results are counted",
  "sl": "Kako štejemo",
  "de": "Wie gezählt wird",
  "es": "Cómo se contabiliza"
 },
 "privacy": {
  "hr": "Računaju se svi aktivni računi, neovisno o paketu. Vidljivost imena početno je uključena i može se isključiti u postavkama. Ranije isključivanje prikaza se poštuje. Prikazani poredak odnosi se na vidljive sudionike. Jednaki rezultati dijele mjesto.",
  "en": "All active accounts count, regardless of plan. Name visibility is on by default and can be turned off in settings. Previous choices to hide a name are respected. Displayed rankings are among visible participants. Equal results share a place.",
  "sl": "Štejejo vsi aktivni računi ne glede na paket. Vidnost imena je privzeto vključena in jo lahko izklopiš v nastavitvah. Prejšnje odločitve za skritje imena se upoštevajo. Prikazana lestvica velja za vidne udeležence. Enaki rezultati delijo mesto.",
  "de": "Alle aktiven Konten zählen unabhängig vom Tarif. Namen sind standardmäßig sichtbar und können in den Einstellungen verborgen werden. Frühere Entscheidungen zum Verbergen werden respektiert. Die angezeigte Rangliste gilt für sichtbare Teilnehmer. Gleiche Ergebnisse teilen sich einen Platz.",
  "es": "Cuentan todas las cuentas activas, independientemente del plan. Los nombres son visibles por defecto y se pueden ocultar en los ajustes. Se respetan las decisiones anteriores de ocultarlos. La clasificación mostrada incluye participantes visibles. Los empates comparten puesto."
 },
 "cc_privacy": {
  "hr": "Zajednički Forever broj računa se jednom. Za prikaz zajedničkog imena svi povezani računi moraju imati uključenu vidljivost. Iznosi tuđih CC ostaju skriveni. CC se prikazuju po mjesecu i nisu dokaz kupnje preko FCC poveznice.",
  "en": "A shared Forever number counts once. All linked accounts must have visibility on to display the shared name. Other members’ CC amounts stay hidden. CC are monthly and do not prove a purchase through an FCC link.",
  "sl": "Skupna Forever številka šteje enkrat. Za prikaz skupnega imena morajo imeti vsi povezani računi vključeno vidnost. Zneski CC drugih članov ostanejo skriti. CC so mesečni in niso dokaz nakupa prek povezave FCC.",
  "de": "Eine gemeinsame Forever Nummer zählt einmal. Für die Anzeige des gemeinsamen Namens müssen alle verknüpften Konten sichtbar sein. CC Beträge anderer Mitglieder bleiben verborgen. CC gelten monatlich und belegen keinen Kauf über einen FCC Link.",
  "es": "Un número Forever compartido cuenta una vez. Todas las cuentas vinculadas deben tener la visibilidad activada para mostrar el nombre compartido. Los importes CC de otros miembros siguen ocultos. Los CC son mensuales y no demuestran compras a través de enlaces FCC."
 },
 "sync": {
  "hr": "Potvrđena CC sinkronizacija: {at}.",
  "en": "Verified CC sync: {at}.",
  "sl": "Potrjena sinhronizacija CC: {at}.",
  "de": "Bestätigte CC Synchronisierung: {at}.",
  "es": "Sincronización CC verificada: {at}."
 },
 "awaiting": {
  "hr": "Za odabrani mjesec još nema potvrđene CC sinkronizacije.",
  "en": "There is no verified CC sync for the selected month yet.",
  "sl": "Za izbrani mesec še ni potrjene sinhronizacije CC.",
  "de": "Für den ausgewählten Monat liegt noch keine bestätigte CC Synchronisierung vor.",
  "es": "Todavía no hay una sincronización CC verificada para el mes seleccionado."
 },
 "freshness": {
  "hr": "Aktivnosti su iz evidencije do {at}. Vrijeme: Zagreb.",
  "en": "Activities are recorded through {at}. Time: Zagreb.",
  "sl": "Aktivnosti so zabeležene do {at}. Čas: Zagreb.",
  "de": "Aktivitäten sind bis {at} erfasst. Zeit: Zagreb.",
  "es": "Actividades registradas hasta {at}. Hora: Zagreb."
 },
 "settings": {
  "hr": "Vidljivost i osobni ciljevi",
  "en": "Visibility and personal goals",
  "sl": "Vidnost in osebni cilji",
  "de": "Sichtbarkeit und persönliche Ziele",
  "es": "Visibilidad y objetivos personales"
 },
 "visible": {
  "hr": "Prikaži moje ime na FCC Top listama",
  "en": "Show my name on FCC Top rankings",
  "sl": "Prikaži moje ime na lestvicah FCC Top",
  "de": "Meinen Namen in FCC Top Ranglisten anzeigen",
  "es": "Mostrar mi nombre en las clasificaciones FCC Top"
 },
 "visible_help": {
  "hr": "Vidljivost je početno uključena. Možeš je isključiti u bilo kojem trenutku i sakriti ime na svim FCC Top listama. Vlastiti pregled ostaje dostupan uz PRO.",
  "en": "Visibility is on by default. You can turn it off at any time to hide your name across FCC Top rankings. Your personal overview remains available with PRO.",
  "sl": "Vidnost je privzeto vključena. Kadar koli jo lahko izklopiš in skriješ ime na vseh lestvicah FCC Top. Osebni pregled ostane na voljo s PRO.",
  "de": "Die Sichtbarkeit ist standardmäßig aktiviert. Du kannst sie jederzeit ausschalten und deinen Namen in allen FCC Top Ranglisten verbergen. Deine persönliche Übersicht bleibt mit PRO verfügbar.",
  "es": "La visibilidad está activada por defecto. Puedes desactivarla en cualquier momento para ocultar tu nombre en todas las clasificaciones FCC Top. Tu vista personal sigue disponible con PRO."
 },
 "weekly_notice": {
  "hr": "Tjedni podsjetnik na moj napredak",
  "en": "Weekly reminder to review my progress",
  "sl": "Tedenski opomnik za moj napredek",
  "de": "Wöchentliche Erinnerung an meinen Fortschritt",
  "es": "Recordatorio semanal de mi progreso"
 },
 "milestone_notice": {
  "hr": "Obavijest kada uđem među prvih 10",
  "en": "Notify me when I enter the top 10",
  "sl": "Obvesti me ob uvrstitvi med prvih 10",
  "de": "Mich beim Eintritt in die Top 10 benachrichtigen",
  "es": "Avisarme cuando entre en los primeros 10"
 },
 "notice_help": {
  "hr": "Samo u FCC obavijestima, najviše dvije poruke tjedno. Prvo mjerenje postavlja početno stanje. Nema poruka za svaku promjenu mjesta.",
  "en": "Only in the FCC inbox, at most two notices per week. The first measurement sets a baseline. No notice for every position change.",
  "sl": "Samo v obvestilih FCC, največ dve sporočili na teden. Prva meritev določi izhodišče. Brez sporočil ob vsaki spremembi mesta.",
  "de": "Nur im FCC Posteingang, höchstens zwei Hinweise pro Woche. Die erste Messung setzt den Ausgangswert. Kein Hinweis bei jeder Platzänderung.",
  "es": "Solo en la bandeja de FCC, como máximo dos avisos semanales. La primera medición establece la referencia. Sin avisos por cada cambio de puesto."
 },
 "goals": {
  "hr": "Moj cilj za 7 dana",
  "en": "My goal for 7 days",
  "sl": "Moj cilj za 7 dni",
  "de": "Mein Ziel für 7 Tage",
  "es": "Mi objetivo para 7 días"
 },
 "goals_help": {
  "hr": "Početni brojevi su prijedlog. Prilagodi ih svojem vremenu. Ciljevi ne mijenjaju rang listu.",
  "en": "The initial numbers are suggestions. Adjust them to your available time. Goals do not affect rankings.",
  "sl": "Začetne številke so predlog. Prilagodi jih svojemu času. Cilji ne vplivajo na lestvico.",
  "de": "Die Anfangswerte sind Vorschläge. Passe sie deiner verfügbaren Zeit an. Ziele beeinflussen die Rangliste nicht.",
  "es": "Las cifras iniciales son sugerencias. Ajústalas a tu tiempo disponible. Los objetivos no afectan a la clasificación."
 },
 "save": {
  "hr": "Spremi postavke",
  "en": "Save settings",
  "sl": "Shrani nastavitve",
  "de": "Einstellungen speichern",
  "es": "Guardar ajustes"
 },
 "progress": {
  "hr": "Moj ritam u posljednjih 7 dana",
  "en": "My pace over the last 7 days",
  "sl": "Moj ritem v zadnjih 7 dneh",
  "de": "Mein Tempo der letzten 7 Tage",
  "es": "Mi ritmo de los últimos 7 días"
 },
 "start": {
  "hr": "Napravi sljedeći korak",
  "en": "Take the next step",
  "sl": "Naredi naslednji korak",
  "de": "Den nächsten Schritt machen",
  "es": "Da el siguiente paso"
 },
 "private_label": {
  "hr": "Samo za mene",
  "en": "Only for me",
  "sl": "Samo zame",
  "de": "Nur für mich",
  "es": "Solo para mí"
 },
 "rank_change": {
  "hr": "Promjena mjesta od {date}: {n}.",
  "en": "Position change since {date}: {n}.",
  "sl": "Sprememba mesta od {date}: {n}.",
  "de": "Platzänderung seit {date}: {n}.",
  "es": "Cambio de puesto desde {date}: {n}."
 },
 "unavailable": {
  "hr": "Pregled trenutačno nije dostupan. Pokušaj ponovno kasnije.",
  "en": "The overview is temporarily unavailable. Try again later.",
  "sl": "Pregled trenutno ni na voljo. Poskusi pozneje.",
  "de": "Die Übersicht ist vorübergehend nicht verfügbar. Versuche es später erneut.",
  "es": "La vista no está disponible temporalmente. Inténtalo más tarde."
 },
 "goal_error": {
  "hr": "Cilj mora biti cijeli broj od 1 do 1000.",
  "en": "A goal must be a whole number from 1 to 1000.",
  "sl": "Cilj mora biti celo število od 1 do 1000.",
  "de": "Ein Ziel muss eine ganze Zahl von 1 bis 1000 sein.",
  "es": "El objetivo debe ser un número entero de 1 a 1000."
 },
 "conflict": {
  "hr": "Postavke su promijenjene u drugom prozoru. Osvježi stranicu i pokušaj ponovno.",
  "en": "Settings changed in another window. Refresh and try again.",
  "sl": "Nastavitve so bile spremenjene v drugem oknu. Osveži in poskusi znova.",
  "de": "Einstellungen wurden in einem anderen Fenster geändert. Aktualisiere die Seite und versuche es erneut.",
  "es": "Los ajustes cambiaron en otra ventana. Actualiza e inténtalo de nuevo."
 },
 "weekly_title": {
  "hr": "Tvoj tjedni pregled je spreman",
  "en": "Your weekly overview is ready",
  "sl": "Tvoj tedenski pregled je pripravljen",
  "de": "Deine Wochenübersicht ist bereit",
  "es": "Tu resumen semanal está listo"
 },
 "weekly_body": {
  "hr": "Pogledaj svoj napredak i odaberi sljedeći koristan korak.",
  "en": "Review your progress and choose your next useful step.",
  "sl": "Poglej svoj napredek in izberi naslednji koristen korak.",
  "de": "Prüfe deinen Fortschritt und wähle den nächsten sinnvollen Schritt.",
  "es": "Revisa tu progreso y elige el siguiente paso útil."
 },
 "milestone_title": {
  "hr": "Novi ulazak među prvih 10",
  "en": "A new top 10 achievement",
  "sl": "Nova uvrstitev med prvih 10",
  "de": "Neu in den Top 10",
  "es": "Nuevo logro entre los primeros 10"
 },
 "milestone_body": {
  "hr": "U jednoj kategoriji tvoj rezultat ušao je među prvih 10. Otvori svoj pregled za aktualno stanje.",
  "en": "Your result entered the top 10 in a category. Open your overview for the current position.",
  "sl": "V eni kategoriji se je tvoj rezultat uvrstil med prvih 10. Odpri pregled za trenutno stanje.",
  "de": "Dein Ergebnis ist in einer Kategorie in die Top 10 gekommen. Öffne die Übersicht für den aktuellen Stand.",
  "es": "Tu resultado entró en los primeros 10 de una categoría. Abre la vista para consultar la posición actual."
 },
 "no_activity": {
  "hr": "Za ovo razdoblje još nema potvrđenih rezultata prema pravilima ove kategorije.",
  "en": "No confirmed results meet this category’s rules for this period yet.",
  "sl": "Za to obdobje še ni potrjenih rezultatov po pravilih te kategorije.",
  "de": "Für diesen Zeitraum gibt es noch keine bestätigten Ergebnisse nach den Regeln dieser Kategorie.",
  "es": "Todavía no hay resultados confirmados que cumplan las reglas de esta categoría para este periodo."
 },
 "settings_intro": {
  "hr": "Odaberi što želiš podijeliti i postavi svoj ritam rada.",
  "en": "Choose what to share and set your own pace.",
  "sl": "Izberi, kaj želiš deliti, in določi svoj ritem.",
  "de": "Wähle, was du teilen möchtest, und lege dein Tempo fest.",
  "es": "Elige qué compartir y define tu ritmo."
 },
 "view_rankings": {
  "hr": "Pogledaj rang liste",
  "en": "View rankings",
  "sl": "Poglej lestvice",
  "de": "Ranglisten ansehen",
  "es": "Ver clasificaciones"
 },
 "my_visibility": {
  "hr": "Moja vidljivost",
  "en": "My visibility",
  "sl": "Moja vidnost",
  "de": "Meine Sichtbarkeit",
  "es": "Mi visibilidad"
 },
 "my_notifications": {
  "hr": "Moje obavijesti",
  "en": "My notifications",
  "sl": "Moja obvestila",
  "de": "Meine Benachrichtigungen",
  "es": "Mis notificaciones"
 },
 "visibility_context": {
  "hr": "Ime je početno vidljivo drugim PRO korisnicima. Ako isključiš prikaz, tvoje aktivnosti i dalje ulaze u obračun. Tvoj spremljeni odabir se poštuje.",
  "en": "Your name is visible to other PRO members by default. If you turn visibility off, your activity still counts. Your saved choice is respected.",
  "sl": "Tvoje ime je privzeto vidno drugim članom PRO. Če vidnost izklopiš, tvoje aktivnosti še vedno štejejo. Tvoja shranjena izbira se upošteva.",
  "de": "Dein Name ist standardmäßig für andere PRO Mitglieder sichtbar. Wenn du die Sichtbarkeit ausschaltest, zählt deine Aktivität weiterhin. Deine gespeicherte Auswahl bleibt gültig.",
  "es": "Tu nombre es visible para otros miembros PRO por defecto. Si desactivas la visibilidad, tu actividad sigue contando. Se respeta tu elección guardada."
 },
 "weekly_help": {
  "hr": "Jedan podsjetnik ponedjeljkom na pregled vlastitog napretka.",
  "en": "One Monday reminder to review your own progress.",
  "sl": "En ponedeljkov opomnik za pregled lastnega napredka.",
  "de": "Eine Erinnerung am Montag, deinen Fortschritt anzusehen.",
  "es": "Un recordatorio los lunes para revisar tu progreso."
 },
 "milestone_help": {
  "hr": "Obavijest kada tvoj rezultat prvi put u tom tjednu uđe među deset najboljih.",
  "en": "A notice when your result enters the top ten for the first time that week.",
  "sl": "Obvestilo, ko se tvoj rezultat prvič v tednu uvrsti med najboljših deset.",
  "de": "Ein Hinweis, wenn dein Ergebnis in dieser Woche erstmals in die Top Ten kommt.",
  "es": "Un aviso cuando tu resultado entre por primera vez esa semana entre los diez mejores."
 },
 "saved_everywhere": {
  "hr": "Postavke vrijede na svim tvojim uređajima.",
  "en": "Settings apply across all your devices.",
  "sl": "Nastavitve veljajo na vseh tvojih napravah.",
  "de": "Die Einstellungen gelten auf allen deinen Geräten.",
  "es": "Los ajustes se aplican en todos tus dispositivos."
 },
 "result_accounts": {
  "hr": "Računi s rezultatom",
  "en": "Accounts with results",
  "sl": "Računi z rezultati",
  "de": "Konten mit Ergebnissen",
  "es": "Cuentas con resultados"
 },
 "visible_accounts": {
  "hr": "Računi s vidljivim imenom",
  "en": "Accounts with a visible name",
  "sl": "Računi z vidnim imenom",
  "de": "Konten mit sichtbarem Namen",
  "es": "Cuentas con nombre visible"
 },
 "results_private_title": {
  "hr": "Rezultati postoje. Imena su trenutačno skrivena.",
  "en": "Results exist. Names are currently hidden.",
  "sl": "Rezultati obstajajo. Imena so trenutno skrita.",
  "de": "Ergebnisse sind vorhanden. Die Namen sind derzeit verborgen.",
  "es": "Hay resultados. Los nombres están ocultos por ahora."
 },
 "results_private_body": {
  "hr": "Računi s rezultatom u ovoj kategoriji: {n}. Njihova imena skrivena su prema postavkama privatnosti. Rezultati i dalje ulaze u obračun.",
  "en": "Accounts with results in this category: {n}. Their names are hidden according to privacy settings. Their results still count.",
  "sl": "Računi z rezultatom v tej kategoriji: {n}. Njihova imena so skrita glede na nastavitve zasebnosti. Rezultati še vedno štejejo.",
  "de": "Konten mit Ergebnissen in dieser Kategorie: {n}. Ihre Namen sind gemäß den Datenschutzeinstellungen verborgen. Ihre Ergebnisse zählen weiterhin.",
  "es": "Cuentas con resultados en esta categoría: {n}. Sus nombres están ocultos según los ajustes de privacidad. Sus resultados siguen contando."
 },
 "previous_month": {
  "hr": "Prethodni mjesec",
  "en": "Previous month",
  "sl": "Prejšnji mesec",
  "de": "Vormonat",
  "es": "Mes anterior"
 },
 "current_month": {
  "hr": "Tekući mjesec",
  "en": "Current month",
  "sl": "Tekoči mesec",
  "de": "Aktueller Monat",
  "es": "Mes actual"
 },
 "period_explanation": {
  "hr": "CC se prikazuje za odabrani mjesec. Izbor 7, 30 ili 60 dana odnosi se na FCC aktivnosti.",
  "en": "CC is shown for the selected month. The 7, 30 or 60 day selection applies to FCC activity.",
  "sl": "CC je prikazan za izbrani mesec. Izbira 7, 30 ali 60 dni velja za aktivnosti FCC.",
  "de": "CC werden für den gewählten Monat angezeigt. Die Auswahl von 7, 30 oder 60 Tagen gilt für FCC Aktivitäten.",
  "es": "Los CC corresponden al mes seleccionado. La selección de 7, 30 o 60 días se aplica a la actividad de FCC."
 },
 "cc_overview": {
  "hr": "Ukupna aktivnost u odabranom mjesecu",
  "en": "Total activity in the selected month",
  "sl": "Skupna aktivnost v izbranem mesecu",
  "de": "Gesamtaktivität im ausgewählten Monat",
  "es": "Actividad total del mes seleccionado"
 },
 "cc_count_help": {
  "hr": "Broj Forever računa s pozitivnom ukupnom aktivnošću u odabranom mjesecu, uključujući one sa skrivenim imenom.",
  "en": "Number of Forever accounts with positive total activity in the selected month, including accounts with hidden names.",
  "sl": "Število računov Forever s pozitivno skupno aktivnostjo v izbranem mesecu, vključno z računi s skritim imenom.",
  "de": "Anzahl der Forever Konten mit positiver Gesamtaktivität im ausgewählten Monat, einschließlich Konten mit verborgenem Namen.",
  "es": "Número de cuentas Forever con actividad total positiva en el mes seleccionado, incluidas las cuentas con nombre oculto."
 },
 "visibility_notice": {
  "hr": "Prikaz tvog imena je isključen. Tvoj rezultat i dalje se računa. Vidljivost možeš promijeniti u postavkama.",
  "en": "Your name visibility is turned off. Your result still counts. You can change visibility in settings.",
  "sl": "Prikaz tvojega imena je izključen. Tvoj rezultat še vedno šteje. Vidnost lahko spremeniš v nastavitvah.",
  "de": "Die Anzeige deines Namens ist ausgeschaltet. Dein Ergebnis zählt weiterhin. Du kannst die Sichtbarkeit in den Einstellungen ändern.",
  "es": "La visibilidad de tu nombre está desactivada. Tu resultado sigue contando. Puedes cambiar la visibilidad en los ajustes."
 },
 "no_recorded_result": {
  "hr": "Još nema zabilježenog rezultata prema pravilima ove kategorije.",
  "en": "No recorded result meets this category’s rules yet.",
  "sl": "Po pravilih te kategorije še ni zabeleženega rezultata.",
  "de": "Noch kein erfasstes Ergebnis nach den Regeln dieser Kategorie.",
  "es": "Aún no hay resultados registrados según las reglas de esta categoría."
 },
 "no_recorded_help": {
  "hr": "Brojimo samo zapise opisane u pravilima ispod. Aktivnost izvan te evidencije nije uključena, pa prazan prikaz nije dokaz da suradnici ne rade.",
  "en": "Only records described in the rules below are counted. Activity outside that record is not included, so an empty view does not prove that members are inactive.",
  "sl": "Štejemo le zapise, opisane v pravilih spodaj. Aktivnosti zunaj te evidence niso vključene, zato prazen prikaz ne pomeni, da sodelavci ne delajo.",
  "de": "Gezählt werden nur die unten beschriebenen Nachweise. Andere Aktivitäten sind nicht enthalten. Eine leere Ansicht beweist daher keine Inaktivität.",
  "es": "Solo se cuentan los registros descritos en las reglas. No se incluye la actividad fuera de esos registros, por lo que una vista vacía no demuestra inactividad."
 },
 "current_month_hint": {
  "hr": "Prikazuješ CC samo za tekući kalendarski mjesec. Bodovi prethodnog mjeseca ne pribrajaju se ovom obračunu.",
  "en": "You are viewing CC for the current calendar month only. Points from the previous month are not added to this tally.",
  "sl": "Prikazani so CC samo za tekoči koledarski mesec. Točke prejšnjega meseca se ne prištevajo temu obračunu.",
  "de": "Du siehst ausschließlich CC für den aktuellen Kalendermonat. Punkte des Vormonats werden dieser Auswertung nicht hinzugerechnet.",
  "es": "Estás viendo los CC del mes natural actual únicamente. Los puntos del mes anterior no se suman a este cómputo."
 },
 "archive_month_hint": {
  "hr": "Prikazuješ CC za prethodni mjesec. Ti bodovi ne ulaze u tekući mjesečni obračun.",
  "en": "You are viewing CC for the previous month. These points do not belong to the current monthly tally.",
  "sl": "Prikazani so CC za prejšnji mesec. Te točke ne spadajo v tekoči mesečni obračun.",
  "de": "Du siehst CC für den Vormonat. Diese Punkte gehören nicht zur aktuellen Monatsauswertung.",
  "es": "Estás viendo los CC del mes anterior. Estos puntos no pertenecen al cómputo del mes actual."
 },
 "total_active_cc": {
  "hr": "Ukupna aktivnost",
  "en": "Total activity",
  "sl": "Skupna aktivnost",
  "de": "Gesamtaktivität",
  "es": "Actividad total"
 },
 "total_active_cc_help": {
  "hr": "Ukupna aktivnost za odabrani kalendarski mjesec iz potvrđenog FLP360 polja Total Active CC. Uključuje osobne CC i CC Preferred Customer računa. Rangiranje prati aktivnost u tom mjesecu.",
  "en": "Total activity for the selected calendar month from the verified FLP360 Total Active CC field. Includes personal CC and Preferred Customer CC. Ranking follows activity in that month.",
  "sl": "Skupna aktivnost za izbrani koledarski mesec iz potrjenega polja FLP360 Total Active CC. Vključuje osebne CC in CC računov Preferred Customer. Razvrstitev sledi aktivnosti v tem mesecu.",
  "de": "Gesamtaktivität im ausgewählten Kalendermonat aus dem bestätigten FLP360 Feld Total Active CC. Enthält persönliche CC und Preferred Customer CC. Die Rangliste folgt der Aktivität in diesem Monat.",
  "es": "Actividad total del mes natural seleccionado, según el campo verificado Total Active CC de FLP360. Incluye CC personales y CC de Preferred Customer. La clasificación refleja la actividad de ese mes."
 }
}
JSON, true);
