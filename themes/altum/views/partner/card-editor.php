<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: the simple card editor page. Live preview on the right (on phones the card
 * fills the screen), the three steps on the left (on phones a bottom panel), one small form per block. */
$link = $data->link;
$payload = $data->payload;
$t = static fn(string $text, array $replace = []): string => htmlspecialchars(fcc_t($text, $replace), ENT_QUOTES, 'UTF-8');
$h = static fn($text): string => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
$catalog = fcc_card_block_catalog();
$public_url = SITE_URL . $link->url;
// Same origin as the page, so the editor can read the preview and save without cross-site requests.
$site_path = rtrim((string) (parse_url(SITE_URL, PHP_URL_PATH) ?: '/'), '/') . '/';
$preview_url = $site_path . 'l/link?link_id=' . (int) $link->link_id . '&preview=' . md5((string) $link->user_id);
$endpoint = $site_path . 'partner/card/' . (int) $link->link_id;
$profile_phone = fc_get_user_default_whatsapp_phone($this->user);
$is_main = fc_get_user_main_biolink_id((int) $link->user_id, false) === (int) $link->link_id;

\Altum\Event::add_content('<link rel="stylesheet" href="' . ASSETS_FULL_URL . 'css/fcc-card-editor.css?v=' . filemtime(ASSETS_PATH . 'css/fcc-card-editor.css') . '">', 'head');

$guide = static function(string $type) use ($catalog, $t): string {
    if(!isset($catalog[$type])) return '';
    return '<div class="fe-guide"><p>' . $t($catalog[$type]['what']) . '</p><p>' . $t($catalog[$type]['how']) . '</p></div>';
};
$forms = [
    'heading' => '<label class="fe-field"><span>' . $t('Naslov') . '</span><input type="text" name="text" maxlength="128" required></label>',
    'paragraph' => '<label class="fe-field"><span>' . $t('Tekst') . '</span><textarea name="text" rows="6" maxlength="1500" required></textarea><small>' . $t('Prazan red započinje novi odlomak.') . '</small></label><p class="fe-note" data-fe-rich hidden>' . $t('Ovaj tekst ima posebno oblikovanje, primjerice poveznice ili boje. Spremanjem ovdje ostaje samo običan tekst.') . '</p>',
    'button' => '<label class="fe-field"><span>' . $t('Natpis gumba') . '</span><input type="text" name="name" maxlength="64" required></label>',
    'custom_html_whatsapp' => '<label class="fe-field"><span>' . $t('Natpis gumba') . '</span><input type="text" name="title" maxlength="64" placeholder="WhatsApp"></label>'
        . '<label class="fe-field"><span>' . $t('Prva poruka') . '</span><textarea name="message" rows="3" maxlength="300"></textarea><small>' . $t('Ova poruka je već upisana kada ti posjetitelj otvori WhatsApp. Može je promijeniti prije slanja.') . '</small></label>'
        . '<label class="fe-field"><span>' . $t('Broj za WhatsApp') . '</span><input type="tel" name="phone" inputmode="tel" maxlength="24" autocomplete="tel" placeholder="+385 91 123 4567"><small>' . ($profile_phone !== '' ? $t('Broj iz registracije upisan je automatski u obliku koji WhatsApp traži. Možeš ga promijeniti.') : $t('Upiši broj s pozivnim brojem zemlje, primjerice +385 91 123 4567.')) . '</small></label>',
    'link' => '<label class="fe-field"><span>' . $t('Natpis gumba') . '</span><input type="text" name="name" maxlength="64" required></label>'
        . '<label class="fe-field"><span>' . $t('Poveznica') . '</span><input type="url" name="url" inputmode="url" maxlength="2048" required placeholder="https://"><small>' . $t('Forever poveznicama sustav sam dodaje tvoju preporuku.') . '</small></label>',
    'video' => '<label class="fe-field"><span>' . $t('Poveznica videa') . '</span><input type="url" name="url" inputmode="url" maxlength="2048" required placeholder="https://"></label>'
        . '<label class="fe-field"><span>' . $t('Naslov iznad videa') . '</span><input type="text" name="title" maxlength="120"><small>' . $t('Nije obavezno.') . '</small></label>',
    'image' => '<div class="fe-photo"><span class="fe-photo-preview" data-fe-photo-preview></span><div class="fe-photo-actions"><label class="fe-button fe-button-soft"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-fe-photo-input hidden> ' . $t('Odaberi sliku') . '</label><small>' . $t('JPG, PNG ili WEBP.') . '</small></div></div>'
        . '<label class="fe-field"><span>' . $t('Kratki opis slike') . '</span><input type="text" name="alt" maxlength="100"><small>' . $t('Pomaže osobama koje koriste čitač zaslona.') . '</small></label>'
        . '<label class="fe-field"><span>' . $t('Poveznica') . '</span><input type="url" name="url" inputmode="url" maxlength="2048" placeholder="https://"><small>' . $t('Nije obavezno. Dodir na sliku otvara ovu poveznicu.') . '</small></label>',
    'avatar' => '<div class="fe-photo"><span class="fe-photo-preview" data-fe-photo-preview></span><div class="fe-photo-actions"><label class="fe-button fe-button-soft"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-fe-photo-input hidden> ' . $t('Odaberi fotografiju') . '</label></div></div>',
    'review' => '<label class="fe-field"><span>' . $t('Ime osobe') . '</span><input type="text" name="author_name" maxlength="64" required></label>'
        . '<label class="fe-field"><span>' . $t('Iskustvo') . '</span><textarea name="description" rows="4" maxlength="600" required></textarea></label>'
        . '<label class="fe-field"><span>' . $t('Ocjena') . '</span><select name="stars"><option value="5">★★★★★</option><option value="4">★★★★</option><option value="3">★★★</option><option value="2">★★</option><option value="1">★</option></select></label>',
    'faq' => '<div class="fe-faq" data-fe-faq>' . implode('', array_map(static fn($i) => '<div class="fe-faq-item" data-fe-faq-item="' . $i . '"' . ($i > 0 ? ' hidden' : '') . '><label class="fe-field"><span>' . $t('Pitanje {number}', ['{number}' => (string) ($i + 1)]) . '</span><input type="text" name="item_title[' . $i . ']" maxlength="128"></label><label class="fe-field"><span>' . $t('Odgovor') . '</span><textarea name="item_content[' . $i . ']" rows="3" maxlength="1000"></textarea></label></div>', range(0, 5))) . '</div><button type="button" class="fe-button fe-button-soft" data-fe-faq-more>' . $t('Dodaj pitanje') . '</button>',
    'socials' => '<div class="fe-socials">' . implode('', array_map(static fn($key, $network) => '<label class="fe-field fe-field-icon"><span><i class="' . $h($network['icon']) . ' fa-fw" aria-hidden="true"></i> ' . $h($network['label']) . '</span><input type="' . ($key === 'email' ? 'email' : 'text') . '" name="socials[' . $h($key) . ']" maxlength="' . ($key === 'email' ? 320 : 128) . '" placeholder="' . $h($network['placeholder']) . '" autocapitalize="off" spellcheck="false"></label>', array_keys(FCC_CARD_EDITOR_SOCIALS), FCC_CARD_EDITOR_SOCIALS)) . '</div><small class="fe-small">' . $t('Možeš zalijepiti cijelu poveznicu profila ili samo ime profila. Prazna polja se ne prikazuju.') . '</small>',
    'link_forever_product' => '<input type="hidden" name="product_id" data-fe-product-id><p class="fe-product-current" data-fe-product-current hidden></p>'
        . '<label class="fe-field"><span>' . $t('Pronađi proizvod') . '</span><input type="search" data-fe-product-search placeholder="' . $t('Upiši naziv, primjerice Aloe Vera Gel') . '" autocomplete="off"></label>'
        . '<div class="fe-products" data-fe-products role="listbox" aria-label="' . $t('Proizvodi') . '"></div>',
    'info' => '',
];
// The block's own look on the new card: two short choices, folded away until needed.
$look_options = [
    'look' => ['Izgled gumba', ['auto' => 'Kao na kartici', 'filled' => 'Istaknuti', 'surface' => 'Puni', 'outline' => 'Obrub', 'soft' => 'Mekani']],
    'size' => ['Veličina', ['m' => 'Srednja', 's' => 'Mala', 'l' => 'Velika']],
    'align' => ['Poravnanje', ['center' => 'Sredina', 'left' => 'Lijevo']],
    'frame' => ['Okvir', ['auto' => 'Kao na kartici', 'plain' => 'Bez okvira', 'panel' => 'U okviru']],
    'corners' => ['Rubovi', ['rounded' => 'Zaobljeni', 'straight' => 'Ravni']],
    'icons' => ['Ikone', ['circle' => 'U krugu', 'outline' => 'S obrubom', 'plain' => 'Samo ikone']],
];
$look_html = static function(string $type) use ($look_options, $t, $h): string {
    $keys = fcc_card_editor_style_fields($type);
    if(!$keys) return '';
    $html = '<details class="fe-advanced" data-fe-look><summary><span><strong>' . $t('Izgled bloka') . '</strong><small>' . $t('Kako ovaj blok izgleda na novoj kartici.') . '</small></span></summary><div class="fe-advanced-body">';
    foreach($keys as $key) {
        if($key === 'icon') {
            $html .= '<label class="fe-field"><span>' . $t('Ikona') . '</span><select name="style_icon">';
            foreach(FCC_CARD_BUTTON_ICONS as $value => $label) $html .= '<option value="' . $h($value) . '">' . $t($label) . '</option>';
            $html .= '</select></label>';
            continue;
        }
        [$label, $options] = $look_options[$key];
        $html .= '<fieldset class="fe-choice"><legend>' . $t($label) . '</legend><div class="fe-chips">';
        foreach($options as $value => $option_label) $html .= '<label class="fe-chip"><input type="radio" name="style_' . $key . '" value="' . $h($value) . '"><span>' . $t($option_label) . '</span></label>';
        $html .= '</div></fieldset>';
    }
    return $html . '</div></details>';
};
$form_for = static fn(string $type): string => match($type) {
    'link_forever_webshop_reg', 'link_forever_shop', 'link_discount', 'link_save_contact' => 'button',
    'youtube', 'vimeo' => 'video',
    'custom_html_chatbot', 'divider' => 'info',
    default => $type,
};
$copy = [
    'saving' => fcc_t('Spremam…'),
    'error' => fcc_t('Spremanje nije uspjelo. Pokušaj ponovno.'),
    'new' => fcc_t('Novi blok'),
    'save' => fcc_t('Spremi'),
    'add' => fcc_t('Dodaj na karticu'),
    'delete' => fcc_t('Ukloni blok'),
    'confirm_delete' => fcc_t('Ukloniti ovaj blok s kartice? Ovo se ne može poništiti.'),
    'confirm_yes' => fcc_t('Da, ukloni'),
    'no_products' => fcc_t('Nema proizvoda za ovu pretragu.'),
    'selected' => fcc_t('Odabrano: {name}'),
    'loading' => fcc_t('Učitavam…'),
    'labels' => array_map(static fn($type) => fcc_card_editor_label($type), array_combine(FCC_CARD_EDITOR_TYPES, FCC_CARD_EDITOR_TYPES)),
];
?>
<div class="fe" data-fe data-fe-v2="<?= fcc_card_is_v2($link) ? '1' : '0' ?>" data-fe-endpoint="<?= $h($endpoint) ?>" data-fe-token="<?= $h(\Altum\Csrf::get()) ?>" data-fe-step-active="<?= $h($data->step) ?>">
    <header class="fe-head">
        <div class="fe-head-title">
            <h1><?= $t('Uredi karticu') ?></h1>
            <p><a href="<?= $h($public_url) ?>" target="_blank" rel="noopener"><?= $h(preg_replace('~^https?://~', '', $public_url)) ?></a><?php if($is_main): ?> · <?= $t('Glavna NFC kartica') ?><?php endif ?></p>
        </div>
        <div class="fe-head-actions">
            <button type="button" class="fe-button fe-button-soft" data-share-url="<?= $h($public_url) ?>" data-share-title="<?= $t('Moja FCC kartica') ?>"><i class="fas fa-share-alt" aria-hidden="true"></i> <span><?= $t('Podijeli') ?></span></button>
        </div>
    </header>

    <nav class="fe-nav" aria-label="<?= $t('Koraci uređivanja') ?>">
        <button type="button" data-fe-nav="profile"><span>1</span> <?= $t('Profil') ?></button>
        <button type="button" data-fe-nav="content"><span>2</span> <?= $t('Sadržaj') ?></button>
        <button type="button" data-fe-nav="design"><span>3</span> <?= $t('Izgled') ?></button>
    </nav>

    <div class="fe-layout">
        <div class="fe-panel" data-fe-panel>
            <div class="fe-panel-bar"><span class="fe-panel-grip" aria-hidden="true"></span><button type="button" class="fe-icon-button" data-fe-panel-close aria-label="<?= $t('Zatvori') ?>"><i class="fas fa-times" aria-hidden="true"></i></button></div>
            <div class="fe-panel-body" data-fe-steps><?= $payload['steps'] ?></div>
        </div>
        <div class="fe-preview">
            <div class="fe-phone">
                <iframe data-fe-src="<?= $h($preview_url) ?>" title="<?= $t('Pregled kartice') ?>" data-fe-preview loading="eager"></iframe>
                <div class="fe-preview-loading" data-fe-preview-loading hidden><span></span></div>
            </div>
            <p class="fe-preview-hint"><i class="fas fa-hand-pointer" aria-hidden="true"></i> <?= $t('Dodirni dio kartice koji želiš promijeniti.') ?></p>
        </div>
    </div>

    <div class="fe-toast" role="status" aria-live="polite" data-fe-toast hidden></div>

    <dialog class="fe-sheet" data-fe-sheet aria-labelledby="fe-sheet-title">
        <form class="fe-form" data-fe-sheet-form enctype="multipart/form-data" novalidate>
            <div class="fe-sheet-head">
                <span class="fe-panel-grip" aria-hidden="true"></span>
                <h2 id="fe-sheet-title" data-fe-sheet-title></h2>
                <button type="button" class="fe-icon-button" data-fe-sheet-close aria-label="<?= $t('Zatvori') ?>"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <div class="fe-sheet-body" data-fe-sheet-body></div>
            <p class="fe-form-error" role="alert" data-fe-error hidden></p>
            <div class="fe-sheet-actions">
                <button type="submit" class="fe-button" data-fe-sheet-save><?= $t('Spremi') ?></button>
                <button type="button" class="fe-button fe-button-danger-soft" data-fe-sheet-delete><?= $t('Ukloni blok') ?></button>
                <span class="fe-confirm" data-fe-confirm hidden><span><?= $t('Ukloniti ovaj blok s kartice? Ovo se ne može poništiti.') ?></span> <button type="button" class="fe-button fe-button-danger" data-fe-confirm-yes><?= $t('Da, ukloni') ?></button> <button type="button" class="fe-button fe-button-soft" data-fe-confirm-no><?= $t('Odustani') ?></button></span>
            </div>
        </form>
    </dialog>

    <?php foreach(FCC_CARD_EDITOR_TYPES as $type): ?>
        <template data-fe-form="<?= $h($type) ?>"><?= $guide($type) ?><?= $forms[$form_for($type)] ?? '' ?><?= $look_html($type) ?></template>
    <?php endforeach ?>
    <template data-fe-form="advanced"><p class="fe-guide"><?= $t('Sadržaj ovog bloka uređuješ u klasičnom uređivaču. Ovdje ga možeš sakriti, premjestiti ili ukloniti.') ?></p><p><a class="fe-button fe-button-soft" href="<?= url('link/' . (int) $link->link_id . '?tab=blocks') ?>" data-fe-advanced-link><?= $t('Otvori klasični uređivač') ?></a></p></template>

    <?php
    $fe_language = fcc_card_editor_language($link, $this->user);
    $fe_defaults = [];
    foreach(FCC_CARD_EDITOR_LIBRARY as $fe_type) $fe_defaults[$fe_type] = fcc_card_editor_fields((object) ['type' => $fe_type, 'location_url' => '', 'settings' => (object) fcc_card_editor_defaults($fe_type, $this->user, $fe_language)], $this->user);
    ?>
    <script type="application/json" data-fe-data><?= json_encode(['blocks' => $payload['blocks'], 'profile_ids' => $payload['profile_ids'], 'copy' => $copy, 'editable' => FCC_CARD_EDITOR_TYPES, 'defaults' => $fe_defaults], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</div>
<script defer src="<?= ASSETS_FULL_URL ?>js/fcc-card-editor.js?v=<?= filemtime(ASSETS_PATH . 'js/fcc-card-editor.js') ?>"></script>
<?php /* /Custom code: FC-2026-10-12 */ ?>
