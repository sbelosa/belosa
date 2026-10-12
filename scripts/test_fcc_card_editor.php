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
$assert(fcc_card_editor_fields((object) ['type' => 'faq', 'location_url' => '', 'settings' => (object) ['items' => [(object) ['title' => 'Kako?', 'content' => 'Ovako.']]]]) === ['items' => [['title' => 'Kako?', 'content' => 'Ovako.']]], 'faq fields');
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

echo "fcc card editor checks passed\n";
