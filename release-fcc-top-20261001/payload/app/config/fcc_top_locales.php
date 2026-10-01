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
  "hr": "Ukupni CC iz FLP360",
  "en": "Total CC from FLP360",
  "sl": "Skupni CC iz FLP360",
  "de": "Gesamte CC aus FLP360",
  "es": "CC totales de FLP360"
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
  "hr": "Izvorno polje Total CC. Osobni CC se ne dodaje ponovno. Ovo nije zaseban izračun sponzorskih CC.",
  "en": "The original Total CC field. Personal CC is not added again. This is not a separate calculation of sponsorship CC.",
  "sl": "Izvirno polje Total CC. Osebnih CC ne prištevamo ponovno. To ni ločen izračun sponzorskih CC.",
  "de": "Das ursprüngliche Feld Total CC. Persönliche CC werden nicht erneut addiert. Dies ist keine separate Berechnung von Sponsoring CC.",
  "es": "El campo original Total CC. No se vuelven a sumar los CC personales. No es un cálculo separado de CC de patrocinio."
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
  "hr": "Računaju se svi aktivni računi, neovisno o paketu. Imena prikazujemo samo uz uključenu vidljivost. Prikazani poredak odnosi se na vidljive sudionike. Jednaki rezultati dijele mjesto.",
  "en": "All active accounts count, regardless of plan. Names appear only when visibility is enabled. Displayed rankings are among visible participants. Equal results share a place.",
  "sl": "Štejejo vsi aktivni računi ne glede na paket. Imena prikazujemo le ob vključeni vidnosti. Prikazana lestvica velja za vidne udeležence. Enaki rezultati delijo mesto.",
  "de": "Alle aktiven Konten zählen unabhängig vom Tarif. Namen erscheinen nur bei aktivierter Sichtbarkeit. Die angezeigte Rangliste gilt für sichtbare Teilnehmer. Gleiche Ergebnisse teilen sich einen Platz.",
  "es": "Cuentan todas las cuentas activas, independientemente del plan. Los nombres aparecen solo con visibilidad activada. La clasificación mostrada incluye participantes visibles. Los empates comparten puesto."
 },
 "cc_privacy": {
  "hr": "Zajednički Forever broj računa se jednom. Za prikaz zajedničkog imena svi povezani računi trebaju uključiti vidljivost. CC se prikazuju po mjesecu i nisu dokaz kupnje preko FCC poveznice.",
  "en": "A shared Forever number counts once. Every linked account must enable visibility to display the shared name. CC are monthly and do not prove a purchase through an FCC link.",
  "sl": "Skupna Forever številka šteje enkrat. Za prikaz skupnega imena morajo vsi povezani računi vključiti vidnost. CC so mesečni in niso dokaz nakupa prek povezave FCC.",
  "de": "Eine gemeinsame Forever Nummer zählt einmal. Alle verknüpften Konten müssen für den gemeinsamen Namen die Sichtbarkeit aktivieren. CC gelten monatlich und belegen keinen Kauf über einen FCC Link.",
  "es": "Un número Forever compartido cuenta una vez. Todas las cuentas vinculadas deben activar la visibilidad para mostrar el nombre compartido. Los CC son mensuales y no demuestran compras a través de enlaces FCC."
 },
 "sync": {
  "hr": "Potvrđena CC sinkronizacija: {at}.",
  "en": "Verified CC sync: {at}.",
  "sl": "Potrjena sinhronizacija CC: {at}.",
  "de": "Bestätigte CC Synchronisierung: {at}.",
  "es": "Sincronización CC verificada: {at}."
 },
 "awaiting": {
  "hr": "Za ovaj mjesec još nema potvrđene CC sinkronizacije. Možeš odabrati prethodni mjesec.",
  "en": "No verified CC sync for this month yet. You can select the previous month.",
  "sl": "Za ta mesec še ni potrjene sinhronizacije CC. Izbereš lahko prejšnji mesec.",
  "de": "Für diesen Monat liegt noch keine bestätigte CC Synchronisierung vor. Du kannst den Vormonat wählen.",
  "es": "Todavía no hay sincronización CC verificada de este mes. Puedes seleccionar el mes anterior."
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
  "hr": "Isključivanje odmah uklanja tvoje ime. Vlastiti rezultati ostaju dostupni u PRO pregledu.",
  "en": "Turning this off immediately removes your name. Your own results remain available in the PRO overview.",
  "sl": "Izklop takoj odstrani tvoje ime. Lastni rezultati ostanejo v pregledu PRO.",
  "de": "Das Ausschalten entfernt deinen Namen sofort. Deine eigenen Ergebnisse bleiben in der PRO Übersicht verfügbar.",
  "es": "Al desactivarlo, tu nombre se elimina inmediatamente. Tus resultados siguen disponibles en la vista PRO."
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
 }
}
JSON, true);
