<?php
/* Offline checks for the simple card editor: profile text, safe paragraphs, social profiles, the block
 * library and the wiring of the editor page. No database or network access. */

if(PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

ini_set('memory_limit', '1024M');

defined('ALTUMCODE') || define('ALTUMCODE', 66);
defined('APP_PATH') || define('APP_PATH', dirname(__DIR__) . '/app/');
defined('THEME_PATH') || define('THEME_PATH', dirname(__DIR__) . '/themes/altum/');

eval('namespace Altum; class Language { public static $code = "hr"; }');

require APP_PATH . 'helpers/strings.php';
if(!function_exists('get_contact_phone_dial_codes_array')) {
    function get_contact_phone_dial_codes_array() { static $codes; return $codes ??= require APP_PATH . 'includes/contact_phone_dial_codes.php'; }
}
require APP_PATH . 'helpers/links.php';
require APP_PATH . 'helpers/fcc_i18n.php';
require APP_PATH . 'helpers/fcc_forever_routing.php';
require APP_PATH . 'helpers/fcc_card.php';
require APP_PATH . 'helpers/fcc_card_editor.php';

$assert = static function(bool $condition, string $message): void {
    if(!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
};
$source = static fn(string $path): string => (string) file_get_contents(dirname(__DIR__) . '/' . $path);

/* Profile: the bold first line is the role badge, the rest is plain text the member can edit. */
[$role, $about] = fcc_card_editor_split_intro(fcc_card_intro_text('hr'));
$assert($role === 'Neovisni Forever Business Partner', 'role line read from the intro');
$assert($about === 'Za više informacija o Forever proizvodima ili poslovnoj suradnji slobodno me kontaktirajte.', 'intro text read');
[$role, $about] = fcc_card_editor_split_intro('<p class="ql-align-center">Prvi red<br>drugi red</p><p>Novi odlomak</p>');
$assert($role === '' && $about === "Prvi red\ndrugi red\n\nNovi odlomak", 'paragraphs and line breaks kept as text');
$assert(fcc_card_editor_text_to_html("Bok <b>svima</b> & \"svi\"\ndrugi red\n\nnovi") === '<p>Bok &lt;b&gt;svima&lt;/b&gt; &amp; &quot;svi&quot;<br>drugi red</p><p>novi</p>', 'text becomes safe paragraphs');
$assert(fcc_card_editor_text_to_html("  \n\n ") === '', 'empty text stays empty');
$assert(!fcc_card_editor_has_rich_text(fcc_card_intro_text('hr')), 'intro has no formatting that would be lost');
$assert(fcc_card_editor_has_rich_text('<p>Pogledaj <a href="https://x.hr">ovo</a></p>'), 'links are reported before saving');
$assert(fcc_card_editor_plain('Ana&#39;s &#34;kutak&#34;') === 'Ana\'s "kutak"', 'stored quotes decoded for inputs');
$assert(fcc_card_editor_line("  Ana \n  Horvat <script>x</script> ", 64) === 'Ana Horvat x', 'one clean line, no tags');

/* Social profiles: full links and handles become what the socials block stores. */
$assert(fcc_card_editor_social_value('instagram', 'https://www.instagram.com/test.fcc/?hl=hr') === 'test.fcc', 'instagram link');
$assert(fcc_card_editor_social_value('instagram', '@test.fcc') === 'test.fcc', 'instagram handle');
$assert(fcc_card_editor_social_value('facebook', 'facebook.com/ana.horvat') === 'ana.horvat', 'facebook link without scheme');
$assert(fcc_card_editor_social_value('tiktok', '@ana') === 'ana', 'tiktok handle');
$assert(fcc_card_editor_social_value('youtube', 'mojkanal') === '@mojkanal', 'youtube channel handle');
$assert(fcc_card_editor_social_value('linkedin', 'https://www.linkedin.com/in/ana-horvat/') === 'in/ana-horvat', 'linkedin link');
$assert(fcc_card_editor_social_value('email', 'Ana@Primjer.HR') === 'ana@primjer.hr', 'email');
$assert(fcc_card_editor_social_value('instagram', '') === '', 'empty profile removed');
$threw = false;
try { fcc_card_editor_social_value('email', 'nije-email'); } catch(InvalidArgumentException $e) { $threw = true; }
$assert($threw, 'invalid email rejected');
$threw = false;
try { fcc_card_editor_social_value('instagram', 'ime s razmakom'); } catch(InvalidArgumentException $e) { $threw = true; }
$assert($threw, 'profile with spaces rejected');

/* WhatsApp: the registration number in the form WhatsApp needs, never a guess into another country. */
foreach([
    ['091 123 4567', ['HR'], '385911234567'], ['+385 91 123 4567', [], '385911234567'], ['00385911234567', [], '385911234567'],
    ['385 091 123 4567', [], '385911234567'], ['91 123 4567', ['HR'], '385911234567'], ['031 123 456', ['SI', 'HR'], '38631123456'],
    ['031 123 456', ['HR'], ''], ['064 123 4567', ['RS', 'HR'], '381641234567'], ['0151 1234 5678', ['DE', 'HR'], '4915112345678'],
    ['+49 0151 12345678', [], '4915112345678'], ['06 30 123 4567', ['HU', 'HR'], '36301234567'], ['07700 900123', ['GB', 'HR'], '447700900123'],
    ['01 234 5678', ['HR'], ''], ['12', ['HR'], ''], ['+1 202 555 0123', [], '12025550123'], ['416 555 0123', ['CA', 'HR'], '14165550123'],
    ['385 00 1234567', [], ''], ['', ['HR'], ''],
] as [$typed, $countries, $expected]) {
    $assert(fc_normalize_whatsapp_phone($typed, $countries) === $expected, 'whatsapp number "' . $typed . '"');
}
$assert(fc_format_whatsapp_phone('385911234567') === '+385 91 123 4567' && fc_format_whatsapp_phone('447700900123') === '+44 7700 900123', 'readable number');
$assert(fc_country_from_text('Hrvatska') === 'HR' && fc_country_from_text('Njemačka') === 'DE' && fc_country_from_text('Bosna i Hercegovina') === 'BA' && fc_country_from_text('Österreich') === 'AT' && fc_country_from_text('xyz') === '', 'country from the address');
$member = (object) ['country' => 'SI', 'preferences' => json_encode(['meta' => ['phone' => '041 123 456', 'country' => 'Slovenija']])];
$assert(fc_get_user_default_whatsapp_phone($member) === '38641123456', 'registration number with the member country');
$assert(fcc_card_editor_whatsapp_display('', $member) === '+386 41 123 456', 'the form shows the registration number');

/* Classic card: the colours, shapes and shadows of the classic editor stay available. */
$assert(fcc_card_editor_classic_fields('link') === ['text_color', 'background_color', 'border_radius', 'border_width', 'border_color', 'border_shadow_style', 'border_shadow_color'], 'classic button options');
$assert(fcc_card_editor_color_parts('#00000000') === ['#000000', true] && fcc_card_editor_color_parts('#ffc600') === ['#FFC600', false] && fcc_card_editor_color_parts('#00000010') === ['#000000', false], 'stored colours read for colour inputs');
$classic = fcc_card_editor_fields((object) ['type' => 'link_save_contact', 'location_url' => '', 'settings' => (object) ['name' => 'Spremi kontakt', 'text_color' => '#000000', 'background_color' => '#FFC600', 'border_radius' => 'rounded', 'border_shadow_style' => 'subtle', 'border_shadow_color' => '#00000010', 'border_width' => 0, 'border_color' => '#000000']]);
$assert($classic['classic_background_color'] === '#FFC600' && $classic['classic_border_shadow_style'] === 'subtle' && $classic['classic_border_radius'] === 'rounded' && $classic['classic_border_width'] === '0', 'classic values shown in the form');
$page_source = $source('themes/altum/views/partner/card-editor.php');
$assert(str_contains($page_source, 'data-fe-classic') && str_contains($source('themes/altum/assets/css/fcc-card-editor.css'), '.fe[data-fe-v2="1"] [data-fe-classic] { display: none; }'), 'classic options shown only for classic cards');

/* Divider: meaningful signs from the icon set the card loads, line style and spacing. */
$assert(fcc_card_editor_style_fields('divider') === ['line', 'space'] && isset(FCC_CARD_DIVIDER_ICONS['fas fa-leaf'], FCC_CARD_DIVIDER_ICONS['fas fa-gem'], FCC_CARD_DIVIDER_ICONS['']), 'divider options');
$assert(fcc_card_block_style_clean(['line' => 'dotted', 'space' => 'l']) === ['line' => 'dotted', 'space' => 'l'], 'divider look kept');
$assert(fcc_card_editor_fields((object) ['type' => 'divider', 'location_url' => '', 'settings' => (object) ['icon' => 'fas fa-gem']])['icon'] === 'fas fa-gem', 'divider sign shown in the form');
$icons = (string) file_get_contents(dirname(__DIR__) . '/themes/altum/assets/js/libraries/fontawesome-solid.min.js');
foreach(array_keys(FCC_CARD_DIVIDER_ICONS) as $icon) {
    if($icon === '') continue;
    $name = substr($icon, strlen('fas fa-'));
    $assert((bool) preg_match('/(?<![\w-])' . preg_quote($name, '/') . '(?=["\']?\s*:)/', $icons), 'divider icon exists in the card icon set: ' . $name);
}
$assert(str_contains($source('themes/altum/assets/js/fcc-card-editor.js'), "t('session')") && str_contains($source('themes/altum/assets/js/fcc-card-editor.js'), "t('busy')"), 'saving always answers with a message');

/* Advisor: starter suggestions write into the question in normal use, not only in the fallback start. */
$chat = $source('themes/altum/views/l/partials/fcc_chat_extreme_popup.php');
$assert(strpos($chat, 'fccStartersBound') !== false && strpos($chat, 'fccStartersBound') < strpos($chat, 'const emergencyInit'), 'advisor starters bound at the start');

/* No "not an official Forever Living Products" notice on cards, pages, footers or llms.txt. */
foreach(['app/helpers/fcc_card.php', 'app/controllers/Index.php', 'app/controllers/Page.php', 'app/controllers/Pages.php', 'app/controllers/LlmsTxt.php', 'themes/altum/views/partner/business-public.php', 'themes/altum/views/partner/business-public-legacy.php', 'themes/altum/views/legal/index.php'] as $file) {
    $text = $source($file);
    $assert(!preg_match('/nije službena stranica|ni njezina službena stranica|not an official Forever|or its official website|semantics_fact_1/i', $text), 'no official page notice in ' . $file);
}

/* Block library: small, Forever first, every type has a form, retired lead forms are not offered. */
$assert(count(FCC_CARD_EDITOR_LIBRARY) <= 15 && array_slice(FCC_CARD_EDITOR_LIBRARY, 0, 2) === ['link_forever_webshop_reg', 'link_forever_shop'], 'small library, Forever first');
$assert(!array_intersect(FCC_CARD_EDITOR_LIBRARY, FCC_CARD_RETIRED_BLOCKS), 'no phone or e-mail forms');
$assert(!array_diff(FCC_CARD_EDITOR_LIBRARY, FCC_CARD_EDITOR_TYPES), 'every library block is editable');
$page = $source('themes/altum/views/partner/card-editor.php');
$assert(str_contains($page, 'foreach(FCC_CARD_EDITOR_TYPES as $type)') && str_contains($page, 'data-fe-form="advanced"'), 'a form template per block type');
foreach(['heading', 'paragraph', 'button', 'custom_html_whatsapp', 'link', 'video', 'image', 'avatar', 'review', 'faq', 'socials', 'link_forever_product'] as $form) {
    $assert(str_contains($page, "'" . $form . "' =>"), 'form ' . $form);
}
foreach(['link', 'youtube', 'faq', 'review', 'link_forever_product'] as $type) {
    $assert(in_array($type, FCC_CARD_EDITOR_NEEDS_FIELDS, true), $type . ' is created only after its fields are filled');
}
$assert(fcc_card_editor_fields((object) ['type' => 'faq', 'location_url' => '', 'settings' => (object) ['items' => [(object) ['title' => 'Kako?', 'content' => 'Ovako.']]]])['items'] === [['title' => 'Kako?', 'content' => 'Ovako.']], 'faq fields');
$assert(fcc_card_editor_fields((object) ['type' => 'custom_html_whatsapp', 'location_url' => '', 'settings' => (object) ['title' => 'WhatsApp', 'message' => 'Bok', 'phone' => '']])['message'] === 'Bok', 'whatsapp fields');

/* Wiring: route, same-origin preview and saves, CSRF, entry points and translations. */
$partner = $source('app/controllers/Partner.php');
$assert(str_contains($partner, "'cards','card'") && str_contains($partner, 'private function card_editor(int $uid)'), 'partner/card route');
$assert(str_contains($partner, 'hash_equals(\\Altum\\Csrf::get(),$_POST[\'token\'])') && str_contains($partner, 'fcc_card_editor_mutate($this->user,$link_id,$_POST)'), 'saves check the session token');
$script = $source('themes/altum/assets/js/fcc-card-editor.js');
$assert(str_contains($script, 'new URL(root.dataset.feEndpoint, location.origin)') && str_contains($script, 'new URL(frame.dataset.feSrc, location.origin)'), 'preview and saves stay on the page origin');
$assert(str_contains($source('themes/altum/views/partner/index.php'), "url('partner/card/'.(int)\$card['link_id'])"), 'Moje kartice opens the simple editor');
$assert(str_contains($source('themes/altum/views/partner/editor-header.php'), "url('partner/card/'"), 'advanced editor links back');
$helper = $source('app/helpers/fcc_card_editor.php');
$assert(str_contains($helper, "->where('user_id', \$user_id)->where('type', 'biolink')") && str_contains($helper, "->where('link_id', (int) \$link->link_id)->where('user_id', (int) \$link->user_id)->getOne('biolinks_blocks')"), 'only own cards and blocks');
foreach(['en', 'de', 'es', 'fr', 'hu', 'pl', 'ro', 'sl', 'sq', 'sr', 'sv', 'cnr'] as $locale) {
    $catalogue = require APP_PATH . 'config/locales/' . $locale . '.php';
    if(is_file(APP_PATH . 'config/locales/' . $locale . '-curated.php')) $catalogue = (require APP_PATH . 'config/locales/' . $locale . '-curated.php') + $catalogue;
    foreach(['Dodirni dio kartice koji želiš promijeniti.', 'Kratki opis ispod imena', 'Spremljeno. Promjena je odmah na kartici.', 'Napredni uređivač', 'Jednostavni uređivač'] as $text) $assert(isset($catalogue[$text]), $locale . ' translates: ' . $text);
}

/* FC-2026-10-12: install page, home screen invitation, Coach knowledge and push details. */
$install = $source('themes/altum/views/partner/install.php');
foreach(['data-install-done', 'data-install-inapp', 'data-install-desktop', 'data-install-howto', 'SquareEye::instance()', "fcc_ui_theme_assets('app')"] as $part) $assert(str_contains($install, $part), 'install page has ' . $part);
// data-copy is the shared copy-to-clipboard hook: only real copy buttons may carry it, or every click is captured.
preg_match_all('~<([a-z]+)\b[^>]*\sdata-copy=~i', $install, $copyTags);
$assert($copyTags[1] !== [] && array_unique(array_map('strtolower', $copyTags[1])) === ['button'] && str_contains($install, 'data-install-copy='), 'only copy buttons use data-copy on the install page');
$assert(str_contains($source('themes/altum/assets/js/fcc-partner-install.js'), 'dataset.installCopy') && !str_contains($source('themes/altum/assets/js/fcc-partner-install.js'), 'dataset.copy'), 'install script reads its texts from data-install-copy');
$installJs = $source('themes/altum/assets/js/fcc-partner-install.js');
$assert(str_contains($installJs, 'location.origin + (new URL(') && !str_contains($installJs, 'status.textContent = window.fccT(\'Instalaciju trenutačno'), 'the worker uses the page origin and a failure is not shown as an error');
$nudge = $source('themes/altum/assets/js/fcc-partner-install-nudge.js');
$assert(str_contains($nudge, "'fcc-install-later:'") && str_contains($nudge, '14*86400000') && str_contains($nudge, "querySelector('[data-device-prompt]')"), 'invitation waits 14 days and never doubles the iPhone prompt');
$assert(str_contains($source('themes/altum/views/partner/device-notifications.php'), 'data-install-nudge'), 'invitation is on the home screen');
$coach = $source('app/helpers/fcc_coach_mentor.php') . $source('app/helpers/fcc_coach_continuity.php');
foreach(['Uredi karticu otvara uređivač s tri koraka', "['team_progress', 'Napredak mojih suradnika'", "\$help['install']", 'Napredno: boje i detalji', 'Boje i oblik'] as $part) $assert(str_contains($coach, $part), 'Coach knows: ' . $part);
$help = json_decode($source('app/config/fcc_help_center/hr.json'), true);
$assert(str_contains(json_encode($help['articles']['card'], JSON_UNESCAPED_UNICODE), 'Spremi izgled'), 'help article describes the new editor');
$push = $source('app/helpers/fcc_push_delivery.php');
foreach(["'mp:help:'=>'team_help'", "'invite:order:'=>'invite_order'", "'card-aloe:'=>'card_update'", "\$payload['detail']=\$detail"] as $part) $assert(str_contains($push, $part), 'push detail: ' . $part);
$worker = $source('fcc-partner-sw.js');
$assert(str_contains($worker, "const VERSION='fcc-partner-shell-v11'") && str_contains($worker, 'FCC_PUSH_DETAIL_COPY[data.detail]'), 'prepared worker shows the detail copy');
foreach(['team_help', 'team_weekly', 'team_milestone', 'team_start', 'invite_registration', 'invite_order', 'card_update'] as $detail) {
    $assert(preg_match('~"' . $detail . '":\{("[a-z]+":\["[^"]+","[^"]+"\],?){13}\}~u', $worker) === 1, 'worker copy in 13 languages: ' . $detail);
}
foreach(['en', 'de', 'es', 'fr', 'hu', 'pl', 'ro', 'sl', 'sq', 'sr', 'sv', 'cnr'] as $locale) {
    $catalogue = require APP_PATH . 'config/locales/' . $locale . '.php';
    if(is_file(APP_PATH . 'config/locales/' . $locale . '-curated.php')) $catalogue = (require APP_PATH . 'config/locales/' . $locale . '-curated.php') + $catalogue;
    foreach(['FCC uvijek pri ruci', 'Dodaj FCC na početni zaslon', 'Tvoja kartica ima novi izgled', 'Nova kartica Aloe za netaknute kartice', 'Ažuriranje sadržaja: napomena o službenoj stranici'] as $text) $assert(isset($catalogue[$text]), $locale . ' translates: ' . $text);
}

/* FC-2026-10-12: a new card opens the new editor, the classic editor stays reachable on purpose. */
$card = static fn(string $type, $settings): object => (object) ['link_id' => 7, 'type' => $type, 'settings' => $settings];
$v2 = $card('biolink', (object) ['fcc_card' => 'v2', 'fcc_card_theme' => 'aloe']);
$assert(fcc_card_editor_route($v2, ['7'], [], 'GET', true) === 'partner/card/7', 'link/{id} of a new card opens the new editor');
$assert(fcc_card_editor_route($v2, ['7'], [], 'HEAD', true) === 'partner/card/7', 'HEAD is routed like GET');
$assert(fcc_card_editor_route($card('biolink', '{"fcc_card":"v2"}'), ['7'], ['auto_copy_link' => 'true'], 'GET', true) === 'partner/card/7', 'stored settings and other query keys still route');
$assert(fcc_card_editor_route($v2, ['7'], [], 'POST', true) === null, 'posts are never routed');
$assert(fcc_card_editor_route($v2, ['7'], [], 'GET', false) === null, 'without the partner area the classic editor stays');
foreach([(object) [], (object) ['fcc_card' => 'v1'], null, '{"fcc_card":"v1"}'] as $settings) $assert(fcc_card_editor_route($card('biolink', $settings), ['7'], [], 'GET', true) === null, 'a classic card stays in the classic editor');
foreach(['vcard', 'link', 'file'] as $type) $assert(fcc_card_editor_route($card($type, (object) ['fcc_card' => 'v2']), ['7'], [], 'GET', true) === null, 'only cards are routed: ' . $type);
foreach(['settings', 'statistics', 'download'] as $method) $assert(fcc_card_editor_route($v2, ['7', $method], [], 'GET', true) === null, 'link/{id}/' . $method . ' stays');
foreach([['classic' => '1'], ['tab' => 'blocks'], ['tab' => 'settings', 'editor' => 'design'], ['tab' => 'settings', 'editor' => 'settings'], ['editor' => 'preview'], ['tab' => 'blocks', 'biolink_block_id' => '5'], ['biolink_block_id' => '5']] as $query) {
    $assert(fcc_card_editor_route($v2, ['7'], $query, 'GET', true) === null, 'deliberate classic address stays: ' . http_build_query($query));
}
$assert(fcc_card_design_return_path(7, true, 'card_design') === 'partner/card/7?step=design', 'switching to the new card continues in the new editor');
$assert(fcc_card_design_return_path(7, true, 'card_recommended') === 'partner/card/7?step=content', 'recommended blocks continue in the content step');
$assert(fcc_card_design_return_path(7, false, 'card_design') === 'link/7?tab=settings&editor=design', 'switching to the classic card stays in the classic editor');
// Execute the destination controller's actual method guard, so a HEAD redirect cannot end at 405.
preg_match('~\n  (if\([^\n]+http_response_code\(405\)[^\n]+)\n  try \{ \$link=fcc_card_editor_link~', $partner, $methodGuard);
$assert(isset($methodGuard[1]), 'destination method guard found');
eval('namespace Altum; class Router { public static $controller_settings = []; }');
$destinationAllows = eval('return static function() { ' . $methodGuard[1] . ' return true; };');
foreach(['GET' => true, 'HEAD' => true, 'PUT' => false, 'DELETE' => false, 'OPTIONS' => false] as $method => $allowed) {
    $_SERVER['REQUEST_METHOD'] = $method;
    http_response_code(200);
    $assert(($destinationAllows() === true) === $allowed, 'destination handles ' . $method);
    $assert(http_response_code() === ($allowed ? 200 : 405), 'destination response for ' . $method);
}
unset($_SERVER['REQUEST_METHOD']);
$assert(str_contains($methodGuard[1], "header('Allow: GET, HEAD, POST')"), 'destination advertises supported methods');
$linkController = $source('app/controllers/Link.php');
$routeAt = strpos($linkController, 'fcc_card_editor_route($this->link, $this->params, $_GET');
$assert($routeAt !== false && $routeAt > strpos($linkController, "->where('user_id', \$this->user->user_id)->getOne('links')") && $routeAt > strpos($linkController, "redirect('links?type=' . \$this->link->type)") && $routeAt < strpos($linkController, 'switch($method)'), 'Link::index routes after the owner and plan checks, before the editor is built');
$assert(str_contains($source('app/controllers/Partner.php'), 'return fcc_card_design_return_path($link_id,fcc_card_is_v2(fcc_card_owned_link($uid,$link_id)),$action);'), 'look changes return through the routing helper');
// Deliberate links from the new editor into the classic one carry a tab, so they are never routed back.
foreach(['themes/altum/views/partner/card-editor.php', 'themes/altum/views/partner/card-editor-steps.php'] as $view) {
    $assert(preg_match_all("~url\('link/'~", $source($view)) === preg_match_all('~url\(\'link/\'\s*\.\s*\(int\)\s*\$link->link_id(?:\s*\.\s*\'([^\']*)\')?\s*\)~', $source($view), $classicLinks), 'every classic link is read in ' . $view);
    $assert($classicLinks[1] !== [], 'classic links found in ' . $view);
    foreach($classicLinks[1] as $suffix) $assert(str_starts_with($suffix, '?tab='), 'classic link keeps its tab in ' . $view . ': ' . ($suffix ?: '(none)'));
}
// The saved look message arrives with the page and is shown once, not left waiting for a later page.
$assert(str_contains($source('themes/altum/views/partner/card-editor.php'), 'data-fe-notice="<?= $h(fcc_card_editor_take_notice()) ?>"') && str_contains($source('themes/altum/assets/js/fcc-card-editor.js'), 'say(root.dataset.feNotice);'), 'the new editor shows the message that led to it');
$cardList = $source('themes/altum/views/partner/index.php');
$assert(str_contains($cardList, "url('link/'.(int)\$card['link_id'].'?editor=preview')") && str_contains($cardList, "<?php if(empty(\$card['fcc_v2'])): ?><a class=\"fp-link fp-card-advanced\" href=\"<?= url('link/'.(int)\$card['link_id']) ?>\""), 'card list: preview keeps the classic view, Klasični uređivač only for classic cards');

/* FC-2026-10-12: on phones Spremi izgled stays in reach and the app menu never covers the open panel. */
$steps = $source('themes/altum/views/partner/card-editor-steps.php');
$barAt = strpos($steps, '<div class="fe-save-bar">');
$assert($barAt !== false && $barAt < strpos($steps, 'data-fe-design-note', $barAt) && strpos($steps, 'data-fe-design-note', $barAt) < strpos($steps, "\$t('Spremi izgled')", $barAt) && strpos($steps, "\$t('Spremi izgled')", $barAt) < strpos($steps, 'data-fe-error', $barAt) && strpos($steps, 'data-fe-error', $barAt) < strpos($steps, '</form>', $barAt), 'the save bar holds the preview note, Spremi izgled and the error, inside the design form');
$editorJs = $source('themes/altum/assets/js/fcc-card-editor.js');
$assert(str_contains($editorJs, "document.documentElement.classList.add('fe-sheet-open');") && str_contains($editorJs, "document.documentElement.classList.remove('fe-lock', 'fe-sheet-open');"), 'opening the panel hides the app menu, closing it brings the menu back');
$editorCss = $source('themes/altum/assets/css/fcc-card-editor.css');
$phoneAt = strpos($editorCss, '@media (max-width: 991px) {');
foreach(['html.fe-sheet-open body .fp-bottom-nav { display: none !important; }', '.fe-panel .fe-save-bar { position: sticky; bottom: calc(-24px - env(safe-area-inset-bottom, 0px));'] as $rule) {
    $assert($phoneAt !== false && strpos($editorCss, $rule) > $phoneAt, 'phone only rule: ' . $rule);
}

echo "fcc card editor checks passed\n";
