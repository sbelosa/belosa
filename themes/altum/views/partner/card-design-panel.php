<?php defined('ALTUMCODE') || die(); ?>
<?php
/* Custom code: FC-2026-10-12: "Izgled kartice" in the editor. New or classic card, the style, and the
 * recommended blocks. Switching never deletes blocks. */
$fcd_link = $data->link;
$fcd_owner = (int) $fcd_link->user_id;
$fcd_is_v2 = fcc_card_is_v2($fcd_link);
$fcd_is_pro = fcc_card_user_is_pro($fcd_owner);
$fcd_settings = fcc_card_settings($fcd_link->settings ?? null);
$fcd_theme = (string) ($fcd_settings->fcc_card_theme ?? 'aloe');
if(!isset(FCC_CARD_THEMES[$fcd_theme]) || (FCC_CARD_THEMES[$fcd_theme]['pro'] && !$fcd_is_pro)) $fcd_theme = 'aloe';
$fcd_missing = $fcd_is_v2 ? fcc_card_missing_recommended($fcd_owner, (int) $fcd_link->link_id) : [];
$fcd_catalog = fcc_card_block_catalog();
$fcd_h = static fn(string $text, array $replace = []): string => htmlspecialchars(fcc_t($text, $replace), ENT_QUOTES, 'UTF-8');
$fcd_has_phone = fc_get_user_default_whatsapp_phone(db()->where('user_id', $fcd_owner)->getOne('users', ['preferences'])) !== '';
?>
<section class="fcc-card-design" data-card-version="<?= $fcd_is_v2 ? 'v2' : 'v1' ?>" aria-labelledby="fcc-card-design-title">
    <h2 id="fcc-card-design-title"><?= $fcd_h('Izgled kartice') ?></h2>
    <p class="fcc-card-design-lead"><?= $fcd_h('Odaberi novu ili staru karticu. Promjena ne briše blokove i uvijek se možeš vratiti. Adresa, NFC, QR kod i preporuka ostaju isti.') ?></p>

    <form method="post" action="<?= url('partner/cards') ?>" class="fcc-card-design-form">
        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>">
        <input type="hidden" name="action" value="card_design">
        <input type="hidden" name="link_id" value="<?= (int) $fcd_link->link_id ?>">

        <fieldset class="fcc-card-design-versions">
            <legend class="sr-only"><?= $fcd_h('Vrsta kartice') ?></legend>
            <label class="fcc-card-design-option">
                <input type="radio" name="card_version" value="v2" <?= $fcd_is_v2 ? 'checked' : '' ?>>
                <span><strong><?= $fcd_h('Nova kartica') ?></strong><small><?= $fcd_h('Moderan i pregledan izgled. Trgovina je na vrhu, a WhatsApp i Spremi kontakt odmah ispod. Preporučeno.') ?></small></span>
            </label>
            <label class="fcc-card-design-option">
                <input type="radio" name="card_version" value="v1" <?= $fcd_is_v2 ? '' : 'checked' ?>>
                <span><strong><?= $fcd_h('Stara kartica') ?></strong><small><?= $fcd_h('Dosadašnji izgled s temama, bojama i fontovima koje si već podesio ili podesila.') ?></small></span>
            </label>
        </fieldset>

        <fieldset class="fcc-card-design-themes" data-fcc-themes>
            <legend><?= $fcd_h('Stil nove kartice') ?></legend>
            <div class="fcc-card-design-theme-list">
                <?php foreach(FCC_CARD_THEMES as $fcd_key => $fcd_item): ?>
                    <?php $fcd_locked = $fcd_item['pro'] && !$fcd_is_pro ?>
                    <label class="fcc-card-design-theme <?= $fcd_locked ? 'is-locked' : '' ?>">
                        <input type="radio" name="card_theme" value="<?= $fcd_key ?>" <?= $fcd_theme === $fcd_key ? 'checked' : '' ?> <?= $fcd_locked ? 'disabled' : '' ?>>
                        <span class="fcc-card-design-swatch" style="background:<?= $fcd_item['swatch'][0] ?>" aria-hidden="true"><i style="background:<?= $fcd_item['swatch'][1] ?>"></i></span>
                        <span class="fcc-card-design-theme-name"><?= htmlspecialchars($fcd_item['name'], ENT_QUOTES, 'UTF-8') ?><?php if($fcd_item['pro']): ?> <em>PRO</em><?php endif ?></span>
                    </label>
                <?php endforeach ?>
            </div>
            <?php if(!$fcd_is_pro): ?><p class="fcc-card-design-note"><?= $fcd_h('Aloe je dostupan svima. Ostali stilovi otvaraju se uz PRO paket.') ?></p><?php endif ?>
        </fieldset>

        <button type="submit" class="fp-button"><?= $fcd_h('Spremi izgled') ?></button>
    </form>

    <?php if($fcd_is_v2): ?>
        <div class="fcc-card-design-recommended">
            <h3><?= $fcd_h('Preporučeni blokovi') ?></h3>
            <?php if($fcd_missing): ?>
                <p><?= $fcd_h('Nova kartica najbolje radi s ovim blokovima. Tvojoj kartici nedostaje:') ?></p>
                <ul>
                    <?php foreach($fcd_missing as $fcd_type): ?><li><?= $fcd_h($fcd_catalog[$fcd_type]['title'] ?? $fcd_type) ?></li><?php endforeach ?>
                </ul>
                <form method="post" action="<?= url('partner/cards') ?>">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>">
                    <input type="hidden" name="action" value="card_recommended">
                    <input type="hidden" name="link_id" value="<?= (int) $fcd_link->link_id ?>">
                    <button type="submit" class="fp-button fp-button-outline"><?= $fcd_h('Dodaj preporučene blokove') ?></button>
                </form>
            <?php else: ?>
                <p><?= $fcd_h('Kartica ima sve preporučene blokove. Uredi ih u kartici Sadržaj.') ?></p>
            <?php endif ?>
            <?php if(!$fcd_has_phone): ?><p class="fcc-card-design-note"><?= $fcd_h('Za WhatsApp gumb upiši broj mobitela s pozivnim brojem zemlje u svom profilu.') ?></p><?php endif ?>
        </div>
    <?php endif ?>
</section>
<style>
    .fp-editor:not([data-editor-mode=design]) .fcc-card-design { display: none; }
    .fp-editor[data-editor-mode=design]:has(.fcc-card-design[data-card-version=v2]) :is(.fp-editor-main, [data-editor-description]) { display: none !important; }
    .fcc-card-design { margin: 16px 0; padding: 18px; border: 1px solid #dce7e1; border-radius: 18px; background: #fff; color: #183b37; }
    .fcc-card-design h2 { margin: 0 0 4px; font-size: 1.15rem; font-weight: 700; }
    .fcc-card-design h3 { margin: 0 0 6px; font-size: 1rem; font-weight: 700; }
    .fcc-card-design-lead { margin: 0 0 14px; color: #526b65; font-size: .92rem; line-height: 1.5; }
    .fcc-card-design fieldset { margin: 0 0 14px; padding: 0; border: 0; min-width: 0; }
    .fcc-card-design legend { margin: 0 0 8px; font-size: .9rem; font-weight: 700; }
    .fcc-card-design-versions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .fcc-card-design-option { display: flex; gap: 10px; align-items: flex-start; margin: 0; padding: 12px; border: 1px solid #dce7e1; border-radius: 14px; cursor: pointer; }
    .fcc-card-design-option:has(input:checked) { border-color: #12775B; background: #EEF6F1; }
    .fcc-card-design-option input { margin-top: 4px; accent-color: #12775B; }
    .fcc-card-design-option strong { display: block; font-size: .95rem; }
    .fcc-card-design-option small { display: block; margin-top: 2px; color: #526b65; font-size: .82rem; line-height: 1.45; }
    .fcc-card-design-theme-list { display: flex; flex-wrap: wrap; gap: 10px; }
    .fcc-card-design-theme { display: flex; flex-direction: column; align-items: center; gap: 6px; margin: 0; padding: 8px; border: 1px solid #dce7e1; border-radius: 14px; cursor: pointer; min-width: 84px; }
    .fcc-card-design-theme input { position: absolute; opacity: 0; pointer-events: none; }
    .fcc-card-design-theme:has(input:checked) { border-color: #12775B; box-shadow: 0 0 0 2px #12775B33; }
    .fcc-card-design-theme:has(input:focus-visible) { outline: 3px solid #12775B; outline-offset: 2px; }
    .fcc-card-design-theme.is-locked { cursor: not-allowed; opacity: .55; }
    .fcc-card-design-swatch { position: relative; width: 52px; height: 52px; border-radius: 14px; border: 1px solid #0000001a; }
    .fcc-card-design-swatch i { position: absolute; left: 10px; right: 10px; bottom: 10px; height: 10px; border-radius: 6px; }
    .fcc-card-design-theme-name { font-size: .85rem; font-weight: 600; }
    .fcc-card-design-theme-name em { font-style: normal; font-size: .68rem; font-weight: 700; padding: 1px 6px; border-radius: 999px; background: #F3E6C4; color: #6B4E0E; }
    .fcc-card-design-note { margin: 8px 0 0; color: #607a73; font-size: .82rem; }
    .fcc-card-design:has(input[name=card_version][value=v1]:checked) [data-fcc-themes] { display: none; }
    .fcc-card-design-recommended { margin-top: 16px; padding-top: 14px; border-top: 1px solid #e3ece7; }
    .fcc-card-design-recommended p { margin: 0 0 8px; color: #34504a; font-size: .9rem; }
    .fcc-card-design-recommended ul { margin: 0 0 10px; padding-left: 18px; color: #34504a; font-size: .9rem; }
    @media (max-width: 575px) { .fcc-card-design-versions { grid-template-columns: 1fr; } .fcc-card-design { padding: 14px; } }
</style>
<?php /* /Custom code: FC-2026-10-12 */ ?>
