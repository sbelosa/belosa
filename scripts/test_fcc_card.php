<?php
/* Offline checks for the new FCC card: styles and PRO gating, the core blocks every package may show,
 * retired lead forms, display fixes, the block guide and its translations, and the hooks that put new
 * members on the new card. No database or network access. */

if(PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// All twelve interface catalogues are loaded in one process.
ini_set('memory_limit', '1024M');

defined('ALTUMCODE') || define('ALTUMCODE', 66);
defined('APP_PATH') || define('APP_PATH', dirname(__DIR__) . '/app/');
defined('THEME_PATH') || define('THEME_PATH', dirname(__DIR__) . '/themes/altum/');

// The card language is the owner's language; the checks run as a Croatian card.
eval('namespace Altum; class Language { public static $code = "hr"; }');

require APP_PATH . 'helpers/fcc_i18n.php';
require APP_PATH . 'helpers/fcc_forever_routing.php';
require APP_PATH . 'helpers/fcc_card.php';

$assert = static function(bool $condition, string $message): void {
    if(!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
};
$source = static fn(string $path): string => (string) file_get_contents(dirname(__DIR__) . '/' . $path);

/* Card version and styles. Without PRO every style falls back to Aloe. */
$assert(fcc_card_is_v2((object) ['settings' => json_encode(['fcc_card' => 'v2'])]), 'v2 from JSON settings');
$assert(fcc_card_is_v2((object) ['settings' => (object) ['fcc_card' => 'v2']]), 'v2 from object settings');
$assert(!fcc_card_is_v2((object) ['settings' => '{}']) && !fcc_card_is_v2((object) []), 'classic card by default');
$assert(array_keys(FCC_CARD_THEMES) === ['aloe', 'smaragd', 'zlato', 'pijesak', 'ponoc'], 'five styles, Aloe first');
$assert(!FCC_CARD_THEMES['aloe']['pro'] && count(array_filter(FCC_CARD_THEMES, static fn($theme) => $theme['pro'])) === 4, 'Aloe is free, four PRO styles');
$assert(fcc_card_theme((object) ['user_id' => 0, 'settings' => json_encode(['fcc_card' => 'v2', 'fcc_card_theme' => 'zlato'])], 0) === 'aloe', 'PRO style falls back to Aloe without PRO');
$assert(fcc_card_theme((object) ['user_id' => 0, 'settings' => json_encode(['fcc_card_theme' => 'unknown'])], 0) === 'aloe', 'unknown style falls back to Aloe');
$css = $source('themes/altum/assets/css/fcc-card.css');
foreach(array_keys(FCC_CARD_THEMES) as $theme) $assert(str_contains($css, 'body.fcc-card--' . $theme), 'CSS for style ' . $theme);
$assert(!str_contains($css, 'fonts.googleapis') && str_contains($css, '../fonts/dm-sans/dm-sans-latin.woff2'), 'self-hosted font');

/* Core blocks every package may show on the new card; phone and e-mail forms are retired. */
foreach(['avatar', 'heading', 'paragraph', 'socials', 'link_forever_webshop_reg', 'link_forever_shop', 'link_save_contact', 'custom_html_whatsapp'] as $type) {
    $assert(fcc_card_core_block($type), 'core block ' . $type);
    $assert(fcc_card_block_open($type, (object) [], true), $type . ' open on the new card without plan flag');
}
$assert(!fcc_card_block_open('paragraph', (object) [], false), 'classic card keeps plan gating');
$assert(fcc_card_block_open('youtube', (object) ['youtube' => true], true) && !fcc_card_block_open('youtube', (object) [], true), 'other blocks follow the plan');
foreach(FCC_CARD_RETIRED_BLOCKS as $type) {
    $assert(in_array($type, ['phone_collector', 'email_collector'], true), 'retired list');
    $assert(!fcc_card_block_open($type, (object) [$type => true], false), $type . ' is no longer offered');
}

/* Display fixes. */
$row = (object) ['type' => 'link_save_contact', 'settings' => (object) ['name' => 'SPREMI KONTAKT', 'icon' => '']];
fcc_card_tidy_label($row);
$assert($row->settings->name === 'Spremi kontakt' && $row->settings->icon === 'fas fa-address-book', 'labels in normal case, contact icon');
$row = (object) ['type' => 'link', 'settings' => (object) ['name' => 'CC', 'icon' => 'far fa-star']];
fcc_card_tidy_label($row);
$assert($row->settings->name === 'CC' && $row->settings->icon === 'fas fa-star', 'short labels stay, regular icons use the loaded set');
$row = (object) ['type' => 'link', 'settings' => (object) ['name' => 'Moj Blog']];
fcc_card_tidy_label($row);
$assert($row->settings->name === 'Moj Blog', 'mixed case labels stay');
$assert(fcc_card_mark_block('<div id="a"><div>x</div></div>', 'socials') === '<div data-fcc-type="socials" id="a"><div>x</div></div>', 'only the outer block is marked');
$assert(fcc_card_mark_block('', 'socials') === '', 'empty block stays empty');
$copy = require APP_PATH . 'config/fcc_forever_routing_copy.php';
$row = (object) ['type' => 'link_forever_shop', 'settings' => (object) ['name' => $copy['en']['business']]];
fcc_card_localize_factory_label($row);
$assert($row->settings->name === fcc_forever_copy('business'), 'factory label shown in the card language');
$row = (object) ['type' => 'link_forever_shop', 'settings' => (object) ['name' => 'Postani dio mog tima']];
fcc_card_localize_factory_label($row);
$assert($row->settings->name === 'Postani dio mog tima', 'own label stays');

/* The intro under the name: a role line shown as a badge and a short invitation. */
$assert(fcc_card_intro_text('hr') === '<p><strong>Neovisni Forever Business Partner</strong></p><p>Za više informacija o Forever proizvodima ili poslovnoj suradnji slobodno me kontaktirajte.</p>', 'intro text');
$assert(str_contains(fcc_card_intro_text('en'), 'Independent Forever Business Partner'), 'intro text translated');
$assert(str_contains($css, 'p:first-child > strong:only-child'), 'role badge style');

/* Colours and details inside a style; each block's own look. */
$assert(fcc_card_style_clean(['accent' => '#e0a526', 'background' => 'red', 'buttons' => 'pill', 'font' => 'modern', 'text' => 'huge']) === ['accent' => '#E0A526', 'buttons' => 'pill'], 'card style keeps only valid, non default choices');
$dark = fcc_card_style_css(['background' => '#1E1B2E', 'accent' => '#E0A526', 'buttons' => 'pill']);
$assert(str_contains($dark['css'], '--fc-ink:#F4F7F5') && str_contains($dark['css'], '--fc-accent-ink:#14201B') && str_contains($dark['classes'], 'fcc-buttons-pill'), 'dark background gets light text, yellow accent gets dark text');
$light = fcc_card_style_css(['background' => '#FFF4E0', 'accent' => '#12775B']);
$assert(str_contains($light['css'], '--fc-ink:#15201C') && str_contains($light['css'], '--fc-accent-ink:#FFFFFF'), 'light background gets dark text, dark accent gets white text');
$assert(fcc_card_block_style_clean(['look' => 'outline', 'align' => 'center', 'size' => 'xl', 'frame' => 'panel']) === ['look' => 'outline', 'frame' => 'panel'], 'block look keeps only valid, non default choices');
$assert(str_contains(fcc_card_mark_block('<div id="b">x</div>', 'link', ['look' => 'soft', 'evil' => '"><script>']), 'data-fcc-look="soft"') && !str_contains(fcc_card_mark_block('<div id="b">x</div>', 'link', ['evil' => 'x']), 'evil'), 'block look attributes are whitelisted');
$_GET = ['preview' => 'wrong', 'fcc_card' => 'v1'];
$assert(fcc_card_preview_choice((object) ['link_id' => 990001, 'user_id' => 7, 'settings' => '{}']) === [], 'unsaved preview needs the preview key');
$_GET = ['preview' => md5('7'), 'fcc_card' => 'v2', 'fcc_theme' => 'zlato', 'fcc_style' => json_encode(['buttons' => 'square', 'font' => 'nope'])];
$assert(fcc_card_preview_choice((object) ['link_id' => 990002, 'user_id' => 7, 'settings' => '{}']) === ['version' => 'v2', 'theme' => 'zlato', 'style' => ['buttons' => 'square']], 'owner preview shows unsaved choices');
$_GET = [];
foreach(['buttons-pill', 'buttons-square', 'button-look-outline', 'button-look-soft', 'font-classic', 'font-simple', 'text-large', 'avatar-rounded', 'glow-off'] as $class) $assert(str_contains($css, 'fcc-' . $class), 'CSS for ' . $class);
foreach(['filled', 'surface', 'outline', 'soft'] as $look) $assert(str_contains($css, '[data-fcc-look="' . $look . '"]'), 'CSS for block look ' . $look);

/* Block guide: three groups, Forever blocks first, every text translated in all locales. */
$catalog = fcc_card_block_catalog();
$assert(array_keys(fcc_card_block_groups()) === ['forever', 'contact', 'about'], 'three guide groups');
$assert(array_slice(array_keys($catalog), 0, 2) === ['link_forever_webshop_reg', 'link_forever_shop'], 'shop and partnership come first');
foreach($catalog as $type => $item) {
    $assert(isset(fcc_card_block_groups()[$item['group']]) && $item['what'] !== '' && $item['how'] !== '', 'guide entry ' . $type);
    $assert(is_dir(THEME_PATH . 'views/link/settings/biolink_blocks/' . $type), 'guide entry has a block form: ' . $type);
}
$texts = [];
foreach($catalog as $item) array_push($texts, $item['title'], $item['what'], $item['how']);
foreach(fcc_card_block_groups() as $group) array_push($texts, $group['title'], $group['text']);
array_push($texts, 'Neovisni Forever Business Owner. Ovo nije službena stranica Forever Living Products.', 'Ponuda za: {country}', 'Pitaj savjetnika', 'Izgled kartice', 'Nova kartica', 'Stara kartica', 'Neovisni Forever Business Partner', 'Za više informacija o Forever proizvodima ili poslovnoj suradnji slobodno me kontaktirajte.');
foreach(['en', 'de', 'es', 'fr', 'hu', 'pl', 'ro', 'sl', 'sq', 'sr', 'sv', 'cnr'] as $locale) {
    $catalogue = require APP_PATH . 'config/locales/' . $locale . '.php';
    if(is_file(APP_PATH . 'config/locales/' . $locale . '-curated.php')) $catalogue = (require APP_PATH . 'config/locales/' . $locale . '-curated.php') + $catalogue;
    foreach($texts as $text) $assert(isset($catalogue[$text]), $locale . ' translates: ' . $text);
}

/* Hooks: new members get the new card, the backend and the picker respect retired and core blocks,
 * Forever blocks keep the referral routing and the classic card keeps its look. */
$assert(str_contains($source('app/models/User.php'), 'fcc_card_prepare_new((int) $user->user_id, (int) $link_id)'), 'registration prepares the new card');
$ajax = $source('app/controllers/BiolinkBlockAjax.php');
$assert(str_contains($ajax, 'FCC_CARD_RETIRED_BLOCKS') && str_contains($ajax, 'fcc_card_is_v2($fcc_card_link)'), 'block backend gate');
$assert(str_contains($source('themes/altum/views/link/settings/biolink_link_create_modal.php'), 'FCC_CARD_RETIRED_BLOCKS'), 'picker hides retired blocks');
$view = $source('themes/altum/views/l/partials/biolink.php');
$assert(str_contains($view, 'fcc_forever_entry_url') && str_contains($view, 'fcc_forever_owned_destination'), 'Forever referral routing kept');
$assert(str_contains($view, 'fcc_card_fix_save_contact') && substr_count($view, 'fcc_card_country_line(') === 1, 'own contact, one country line');
$assert(str_contains($source('themes/altum/views/l/biolink_wrapper.php'), "css/fcc-card.css"), 'new card stylesheet loaded only through the wrapper');
$partner = $source('app/controllers/Partner.php');
$assert(str_contains($partner, "'card_design'") && str_contains($partner, "'card_recommended'"), 'editor actions');
$helper = $source('app/helpers/fcc_card.php');
$assert(!preg_match('/delete\\(\'biolinks_blocks\'\\)/', substr($helper, strpos($helper, 'function fcc_card_set_design'), 1200)), 'switching never deletes blocks');

echo "fcc card checks passed\n";
