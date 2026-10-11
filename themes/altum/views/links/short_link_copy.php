<?php
defined('ALTUMCODE') || die();

/* Local display copy, shared by the list and the existing creation modal. */
$fcc_short_link_copy = [
    'title' => ['hr'=>'Skraćene poveznice', 'en'=>'Short links', 'sl'=>'Skrajšane povezave', 'de'=>'Kurzlinks', 'es'=>'Enlaces cortos'],
    'intro' => ['hr'=>'Pretvori dugu web adresu u kratku poveznicu koju je lako podijeliti.', 'en'=>'Turn a long web address into a short link that is easy to share.', 'sl'=>'Dolgi spletni naslov spremeni v kratko povezavo, ki jo je preprosto deliti.', 'de'=>'Verwandle eine lange Webadresse in einen kurzen Link, den du einfach teilen kannst.', 'es'=>'Convierte una dirección web larga en un enlace corto fácil de compartir.'],
    'new' => ['hr'=>'Nova poveznica', 'en'=>'New link', 'sl'=>'Nova povezava', 'de'=>'Neuer Link', 'es'=>'Nuevo enlace'],
    'bulk' => ['hr'=>'Više poveznica', 'en'=>'Multiple links', 'sl'=>'Več povezav', 'de'=>'Mehrere Links', 'es'=>'Varios enlaces'],
    'export' => ['hr'=>'Izvoz', 'en'=>'Export', 'sl'=>'Izvoz', 'de'=>'Exportieren', 'es'=>'Exportar'],
    'filters' => ['hr'=>'Filtri', 'en'=>'Filters', 'sl'=>'Filtri', 'de'=>'Filter', 'es'=>'Filtros'],
    'select' => ['hr'=>'Odaberi', 'en'=>'Select', 'sl'=>'Izberi', 'de'=>'Auswählen', 'es'=>'Seleccionar'],
    'select_all' => ['hr'=>'Odaberi sve na stranici', 'en'=>'Select all on this page', 'sl'=>'Izberi vse na strani', 'de'=>'Alle auf dieser Seite auswählen', 'es'=>'Seleccionar todos en esta página'],
    'guide_title' => ['hr'=>'Tri koraka do nove poveznice', 'en'=>'Create a link in three steps', 'sl'=>'Nova povezava v treh korakih', 'de'=>'In drei Schritten zum neuen Link', 'es'=>'Crea un enlace en tres pasos'],
    'paste' => ['hr'=>'Zalijepi dugu poveznicu', 'en'=>'Paste the long link', 'sl'=>'Prilepi dolgo povezavo', 'de'=>'Langen Link einfügen', 'es'=>'Pega el enlace largo'],
    'name' => ['hr'=>'Odaberi kratak naziv', 'en'=>'Choose a short name', 'sl'=>'Izberi kratko ime', 'de'=>'Kurzen Namen wählen', 'es'=>'Elige un nombre corto'],
    'share' => ['hr'=>'Spremi i podijeli', 'en'=>'Save and share', 'sl'=>'Shrani in deli', 'de'=>'Speichern und teilen', 'es'=>'Guarda y comparte'],
    'listed' => ['hr'=>'Poveznice na stranici', 'en'=>'Links on this page', 'sl'=>'Povezave na tej strani', 'de'=>'Links auf dieser Seite', 'es'=>'Enlaces en esta página'],
    'saved' => ['hr'=>'Tvoje poveznice', 'en'=>'Your links', 'sl'=>'Tvoje povezave', 'de'=>'Deine Links', 'es'=>'Tus enlaces'],
    'clicks' => ['hr'=>'Klikovi', 'en'=>'Clicks', 'sl'=>'Kliki', 'de'=>'Klicks', 'es'=>'Clics'],
    'created' => ['hr'=>'Izrađeno', 'en'=>'Created', 'sl'=>'Ustvarjeno', 'de'=>'Erstellt', 'es'=>'Creado'],
    'actions' => ['hr'=>'Radnje', 'en'=>'Actions', 'sl'=>'Dejanja', 'de'=>'Aktionen', 'es'=>'Acciones'],
    'copy' => ['hr'=>'Kopiraj', 'en'=>'Copy', 'sl'=>'Kopiraj', 'de'=>'Kopieren', 'es'=>'Copiar'],
    'edit' => ['hr'=>'Uredi', 'en'=>'Edit', 'sl'=>'Uredi', 'de'=>'Bearbeiten', 'es'=>'Editar'],
    'more' => ['hr'=>'Više', 'en'=>'More', 'sl'=>'Več', 'de'=>'Mehr', 'es'=>'Más'],
    'enable' => ['hr'=>'Uključi ili isključi poveznicu', 'en'=>'Enable or disable this link', 'sl'=>'Vklopi ali izklopi povezavo', 'de'=>'Link aktivieren oder deaktivieren', 'es'=>'Activar o desactivar el enlace'],
    'empty_title' => ['hr'=>'Pripremi svoju prvu kratku poveznicu', 'en'=>'Create your first short link', 'sl'=>'Pripravi svojo prvo kratko povezavo', 'de'=>'Erstelle deinen ersten Kurzlink', 'es'=>'Crea tu primer enlace corto'],
    'empty_text' => ['hr'=>'Zalijepi adresu stranice koju želiš podijeliti. Nakon spremanja ovdje ćeš moći kopirati poveznicu i pratiti klikove.', 'en'=>'Paste the address of the page you want to share. Once saved, you can copy the link and track clicks here.', 'sl'=>'Prilepi naslov strani, ki jo želiš deliti. Po shranjevanju lahko tukaj kopiraš povezavo in spremljaš klike.', 'de'=>'Füge die Adresse der Seite ein, die du teilen möchtest. Nach dem Speichern kannst du hier den Link kopieren und die Klicks verfolgen.', 'es'=>'Pega la dirección de la página que quieres compartir. Después de guardarla, podrás copiar el enlace y consultar los clics aquí.'],
    'destination' => ['hr'=>'Duga poveznica, odredište', 'en'=>'Long link, destination', 'sl'=>'Dolga povezava, cilj', 'de'=>'Langer Link, Zieladresse', 'es'=>'Enlace largo, destino'],
    'destination_help' => ['hr'=>'Zalijepi cijelu adresu stranice koja se treba otvoriti.', 'en'=>'Paste the full address of the page that should open.', 'sl'=>'Prilepi celoten naslov strani, ki naj se odpre.', 'de'=>'Füge die vollständige Adresse der Seite ein, die geöffnet werden soll.', 'es'=>'Pega la dirección completa de la página que debe abrirse.'],
    'alias' => ['hr'=>'Kratak naziv poveznice', 'en'=>'Short link name', 'sl'=>'Kratko ime povezave', 'de'=>'Kurzer Linkname', 'es'=>'Nombre corto del enlace'],
    'alias_placeholder' => ['hr'=>'moj-proizvod', 'en'=>'my-product', 'sl'=>'moj-izdelek', 'de'=>'mein-produkt', 'es'=>'mi-producto'],
    'alias_help' => ['hr'=>'Naziv je dio web adrese nakon kose crte. Možeš ga ostaviti praznim za automatski naziv.', 'en'=>'The name is the part of the web address after the slash. Leave it empty for an automatic name.', 'sl'=>'Ime je del spletnega naslova za poševnico. Za samodejno ime ga lahko pustiš praznega.', 'de'=>'Der Name ist der Teil der Webadresse nach dem Schrägstrich. Lasse das Feld leer, um einen Namen automatisch zu erstellen.', 'es'=>'El nombre es la parte de la dirección web después de la barra. Déjalo vacío para generar un nombre automáticamente.'],
    'alias_auto' => ['hr'=>'Tvoj paket automatski dodjeljuje naziv poveznice.', 'en'=>'Your plan assigns a link name automatically.', 'sl'=>'Tvoj paket samodejno dodeli ime povezave.', 'de'=>'Dein Tarif vergibt den Linknamen automatisch.', 'es'=>'Tu plan asigna el nombre del enlace automáticamente.'],
    'after' => ['hr'=>'Nakon spremanja možeš kopirati novu poveznicu i urediti njezino odredište.', 'en'=>'After saving, you can copy the new link and edit its destination.', 'sl'=>'Po shranjevanju lahko kopiraš novo povezavo in urediš njen cilj.', 'de'=>'Nach dem Speichern kannst du den neuen Link kopieren und seine Zieladresse bearbeiten.', 'es'=>'Después de guardar, puedes copiar el nuevo enlace y editar su destino.'],
    'save' => ['hr'=>'Spremi poveznicu', 'en'=>'Save link', 'sl'=>'Shrani povezavo', 'de'=>'Link speichern', 'es'=>'Guardar enlace'],
    'close' => ['hr'=>'Zatvori', 'en'=>'Close', 'sl'=>'Zapri', 'de'=>'Schließen', 'es'=>'Cerrar'],
];
$fcc_short_t = static function(string $key) use ($fcc_short_link_copy): string {
    $copy = $fcc_short_link_copy[$key];
    $locale = fcc_locale();
    $source = $copy['hr'];
    $translated = fcc_t($source);
    return $translated !== $source ? $translated : ($copy[$locale] ?? $copy['en']);
};
$fcc_short_h = static fn(string $key): string => htmlspecialchars($fcc_short_t($key), ENT_QUOTES, 'UTF-8');

if(!defined('FCC_SHORT_LINK_VIEW_ASSETS')) {
    define('FCC_SHORT_LINK_VIEW_ASSETS', true);
    \Altum\Event::add_content('<link rel="stylesheet" href="' . ASSETS_FULL_URL . 'css/fcc-short-links.css?v=' . filemtime(ASSETS_PATH . 'css/fcc-short-links.css') . '">', 'head');
}
