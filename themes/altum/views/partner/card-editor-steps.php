<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: the three steps of the simple card editor. Re-rendered after every save. */
$state = $data->state;
$link = $data->link;
$t = static fn(string $text, array $replace = []): string => htmlspecialchars(fcc_t($text, $replace), ENT_QUOTES, 'UTF-8');
$h = static fn($text): string => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
$profile = $state['profile'];
$groups = fcc_card_block_groups();
?>
<section class="fe-step" data-fe-step="profile" aria-labelledby="fe-step-profile-title">
    <div class="fe-step-head">
        <h2 id="fe-step-profile-title"><?= $t('Profil') ?></h2>
        <p><?= $t('Tko si. Fotografija, ime i nekoliko riječi o tebi na vrhu kartice.') ?></p>
    </div>
    <form class="fe-form" data-fe-profile enctype="multipart/form-data" novalidate>
        <div class="fe-photo">
            <span class="fe-photo-preview" data-fe-photo-preview>
                <?php if($profile['photo']): ?><img src="<?= $h($profile['photo']) ?>" alt=""><?php else: ?><i class="fas fa-user" aria-hidden="true"></i><?php endif ?>
            </span>
            <div class="fe-photo-actions">
                <label class="fe-button fe-button-soft"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-fe-photo-input hidden> <?= $profile['photo'] ? $t('Promijeni fotografiju') : $t('Dodaj fotografiju') ?></label>
                <?php if($profile['photo']): ?><label class="fe-check"><input type="checkbox" name="photo_remove" value="1"> <?= $t('Ukloni fotografiju') ?></label><?php endif ?>
                <small><?= $t('Jasna fotografija lica, najbolje kvadratna.') ?></small>
            </div>
        </div>
        <label class="fe-field"><span><?= $t('Ime') ?></span><input type="text" name="name" maxlength="64" required value="<?= $h($profile['name']) ?>" autocomplete="name"></label>
        <label class="fe-field"><span><?= $t('Kratki opis ispod imena') ?></span><input type="text" name="role" maxlength="80" value="<?= $h($profile['role']) ?>" placeholder="<?= $t('Neovisni Forever Business Partner') ?>"><small><?= $t('Prikazuje se kao oznaka ispod imena.') ?></small></label>
        <label class="fe-field"><span><?= $t('O meni') ?></span><textarea name="about" rows="4" maxlength="600" placeholder="<?= $t('Za više informacija o Forever proizvodima ili poslovnoj suradnji slobodno me kontaktirajte.') ?>"><?= $h($profile['about']) ?></textarea><small><?= $t('Dvije do tri rečenice. Prazan red započinje novi odlomak.') ?></small></label>
        <?php if($profile['rich_text']): ?><p class="fe-note"><?= $t('Ovaj tekst ima posebno oblikovanje, primjerice poveznice ili boje. Spremanjem ovdje ostaje samo običan tekst.') ?></p><?php endif ?>
        <div class="fe-form-actions"><button type="submit" class="fe-button"><?= $t('Spremi profil') ?></button></div>
        <p class="fe-form-error" role="alert" data-fe-error hidden></p>
    </form>
</section>

<section class="fe-step" data-fe-step="content" aria-labelledby="fe-step-content-title">
    <div class="fe-step-head">
        <h2 id="fe-step-content-title"><?= $t('Sadržaj') ?></h2>
        <p><?= $t('Gumbi i dijelovi kartice, redom kojim ih posjetitelji vide. Dodirni dio koji želiš urediti.') ?></p>
    </div>
    <?php if($state['missing']): ?>
        <div class="fe-callout">
            <p><?= $t('Nova kartica najbolje radi s ovim blokovima. Tvojoj kartici nedostaje:') ?> <strong><?= $h(implode(', ', array_map(static fn($type) => fcc_card_editor_label($type), $state['missing']))) ?></strong></p>
            <button type="button" class="fe-button fe-button-soft" data-fe-action="recommended"><?= $t('Dodaj preporučene blokove') ?></button>
        </div>
    <?php endif ?>
    <ol class="fe-parts">
        <?php foreach($state['parts'] as $index => $part): ?>
            <li class="fe-part<?= $part['shown'] ? '' : ' is-hidden' ?>" data-fe-part="<?= (int) $part['id'] ?>">
                <span class="fe-part-icon" aria-hidden="true"><i class="<?= $h($part['icon']) ?> fa-fw"></i></span>
                <button type="button" class="fe-part-main" <?= $part['editable'] ? 'data-fe-edit="' . (int) $part['id'] . '"' : 'data-fe-advanced="' . (int) $part['id'] . '"' ?>>
                    <strong><?= $h($part['label']) ?></strong>
                    <?php if($part['summary'] !== ''): ?><span><?= $h($part['summary']) ?></span><?php endif ?>
                    <?php if($part['retired'] && $state['is_v2']): ?><em class="fe-badge"><?= $t('Ne prikazuje se na novoj kartici') ?></em>
                    <?php elseif($part['locked']): ?><em class="fe-badge fe-badge-pro"><?= $t('Vidljivo uz PRO paket') ?></em>
                    <?php elseif(!$part['enabled']): ?><em class="fe-badge"><?= $t('Skriveno') ?></em><?php endif ?>
                    <?php if(!$part['editable']): ?><em class="fe-badge"><?= $t('Klasični uređivač') ?></em><?php endif ?>
                </button>
                <span class="fe-part-tools">
                    <button type="button" class="fe-icon-button" data-fe-move="up" data-fe-id="<?= (int) $part['id'] ?>" aria-label="<?= $t('Pomakni gore') ?>" <?= $index === 0 ? 'disabled' : '' ?>><i class="fas fa-chevron-up" aria-hidden="true"></i></button>
                    <button type="button" class="fe-icon-button" data-fe-move="down" data-fe-id="<?= (int) $part['id'] ?>" aria-label="<?= $t('Pomakni dolje') ?>" <?= $index === count($state['parts']) - 1 ? 'disabled' : '' ?>><i class="fas fa-chevron-down" aria-hidden="true"></i></button>
                    <label class="fe-switch" title="<?= $t('Prikaži na kartici') ?>"><input type="checkbox" data-fe-toggle="<?= (int) $part['id'] ?>" <?= $part['shown'] ? 'checked' : '' ?> <?= $part['locked'] || ($part['retired'] && $state['is_v2']) ? 'disabled' : '' ?>><span aria-hidden="true"></span><span class="sr-only"><?= $t('Prikaži na kartici') ?>: <?= $h($part['label']) ?></span></label>
                </span>
            </li>
        <?php endforeach ?>
    </ol>
    <?php if(!$state['parts']): ?><p class="fe-empty"><?= $t('Kartica još nema gumbe. Dodaj prvi blok.') ?></p><?php endif ?>

    <div class="fe-add">
        <button type="button" class="fe-button" data-fe-library-open aria-expanded="false" aria-controls="fe-library"><i class="fas fa-plus" aria-hidden="true"></i> <?= $t('Dodaj blok') ?></button>
        <?php if($state['blocks_limit'] !== -1): ?><small><?= $t('Blokova: {count} od {limit}', ['{count}' => (string) $state['blocks_total'], '{limit}' => (string) $state['blocks_limit']]) ?></small><?php endif ?>
    </div>
    <div class="fe-library" id="fe-library" data-fe-library hidden>
        <p class="fe-library-lead"><?= $t('Odaberi što želiš dodati. Uz svaki blok piše čemu služi.') ?></p>
        <?php foreach($groups as $group_key => $group): ?>
            <?php $items = array_filter($state['library'], static fn($entry) => $entry['item']['group'] === $group_key); if(!$items) continue; ?>
            <h3><?= $t($group['title']) ?></h3>
            <p class="fe-library-group-text"><?= $t($group['text']) ?></p>
            <div class="fe-library-list">
                <?php foreach($items as $entry): ?>
                    <button type="button" class="fe-library-item" data-fe-add="<?= $h($entry['type']) ?>" data-fe-needs-fields="<?= $entry['needs_fields'] ? '1' : '0' ?>" <?= !$entry['open'] || $entry['exists'] ? 'disabled' : '' ?>>
                        <span class="fe-part-icon" aria-hidden="true"><i class="<?= $h($entry['icon']) ?> fa-fw"></i></span>
                        <span>
                            <strong><?= $t($entry['item']['title']) ?><?php if(!$entry['open']): ?> <em class="fe-badge fe-badge-pro">PRO</em><?php elseif($entry['exists']): ?> <em class="fe-badge"><?= $t('Već na kartici') ?></em><?php endif ?></strong>
                            <?php if($entry['item']['what'] !== ''): ?><span><?= $t($entry['item']['what']) ?></span><?php endif ?>
                        </span>
                    </button>
                <?php endforeach ?>
            </div>
        <?php endforeach ?>
        <p class="fe-library-more"><?= $t('Trebaš nešto drugo, primjerice obrazac, kartu ili glazbu?') ?> <a href="<?= url('link/' . (int) $link->link_id . '?tab=blocks') ?>"><?= $t('Svi blokovi su u klasičnom uređivaču.') ?></a></p>
    </div>
</section>

<section class="fe-step" data-fe-step="design" aria-labelledby="fe-step-design-title">
    <div class="fe-step-head">
        <h2 id="fe-step-design-title"><?= $t('Izgled') ?></h2>
        <p><?= $t('Odaberi novu ili staru karticu. Promjena ne briše blokove i uvijek se možeš vratiti. Adresa, NFC, QR kod i preporuka ostaju isti.') ?></p>
    </div>
    <form class="fe-form" data-fe-design novalidate>
        <fieldset class="fe-versions">
            <legend class="sr-only"><?= $t('Vrsta kartice') ?></legend>
            <label class="fe-option"><input type="radio" name="card_version" value="v2" <?= $state['is_v2'] ? 'checked' : '' ?>><span><strong><?= $t('Nova kartica') ?></strong><small><?= $t('Moderan i pregledan izgled. Trgovina je na vrhu, a WhatsApp i Spremi kontakt odmah ispod. Preporučeno.') ?></small></span></label>
            <label class="fe-option"><input type="radio" name="card_version" value="v1" <?= $state['is_v2'] ? '' : 'checked' ?>><span><strong><?= $t('Stara kartica') ?></strong><small><?= $t('Dosadašnji izgled s temama, bojama i fontovima koje si već podesio ili podesila.') ?></small></span></label>
        </fieldset>
        <fieldset class="fe-themes" data-fe-themes>
            <legend><?= $t('Stil nove kartice') ?></legend>
            <div class="fe-theme-list">
                <?php foreach(FCC_CARD_THEMES as $key => $theme): $locked = $theme['pro'] && !$state['is_pro']; ?>
                    <label class="fe-theme<?= $locked ? ' is-locked' : '' ?>">
                        <input type="radio" name="card_theme" value="<?= $h($key) ?>" <?= $state['theme'] === $key ? 'checked' : '' ?> <?= $locked ? 'disabled' : '' ?>>
                        <span class="fe-swatch" style="background:<?= $h($theme['swatch'][0]) ?>" aria-hidden="true"><i style="background:<?= $h($theme['swatch'][1]) ?>"></i></span>
                        <span class="fe-theme-name"><?= $h($theme['name']) ?><?php if($theme['pro']): ?> <em class="fe-badge fe-badge-pro">PRO</em><?php endif ?></span>
                    </label>
                <?php endforeach ?>
            </div>
            <?php if(!$state['is_pro']): ?><small><?= $t('Aloe je dostupan svima. Ostali stilovi otvaraju se uz PRO paket.') ?></small><?php endif ?>
        </fieldset>
        <?php
        $style = $state['style'];
        $swatch = FCC_CARD_THEMES[$state['theme']]['swatch'] ?? FCC_CARD_THEMES['aloe']['swatch'];
        $choices = [
            'buttons' => ['Oblik gumba', ['rounded' => 'Zaobljeni', 'pill' => 'Okrugli', 'square' => 'Ravni']],
            'button_look' => ['Ostali gumbi', ['surface' => 'Puni', 'outline' => 'Obrub', 'soft' => 'Mekani']],
            'font' => ['Font', ['modern' => 'Moderni', 'classic' => 'Klasični', 'simple' => 'Jednostavni']],
            'text' => ['Veličina teksta', ['normal' => 'Normalna', 'large' => 'Veća']],
            'avatar' => ['Fotografija', ['circle' => 'Krug', 'rounded' => 'Zaobljeni kvadrat']],
            'glow' => ['Sjaj pozadine', ['on' => 'Blagi', 'strong' => 'Izraženi', 'off' => 'Bez sjaja']],
        ];
        ?>
        <details class="fe-advanced" data-fe-style-panel <?= $style ? 'open' : '' ?>>
            <summary><span><strong><?= $t('Napredno: boje i detalji') ?></strong><small><?= $t('Prilagodi odabrani stil. Svaku promjenu odmah vidiš u pregledu.') ?></small></span></summary>
            <div class="fe-advanced-body">
                <input type="hidden" name="style_present" value="1">
                <div class="fe-colors">
                    <?php foreach(['accent' => ['Glavna boja', 'Gumb za trgovinu, ikone i poveznice.', $swatch[1]], 'background' => ['Pozadina', 'Boja iza kartice. Tekst se sam prilagodi da ostane čitljiv.', $swatch[0]]] as $key => [$label, $hint, $fallback]): ?>
                        <div class="fe-color<?= $state['is_pro'] ? '' : ' is-locked' ?>">
                            <label class="fe-check"><input type="checkbox" name="<?= $key ?>_on" value="1" <?= !empty($style[$key]) ? 'checked' : '' ?> <?= $state['is_pro'] ? '' : 'disabled' ?>> <strong><?= $t($label) ?></strong><?php if(!$state['is_pro']): ?> <em class="fe-badge fe-badge-pro">PRO</em><?php endif ?></label>
                            <input type="color" name="<?= $key ?>" value="<?= $h($style[$key] ?? $fallback) ?>" aria-label="<?= $t($label) ?>" <?= $state['is_pro'] ? '' : 'disabled' ?>>
                            <small><?= $t($hint) ?></small>
                        </div>
                    <?php endforeach ?>
                </div>
                <?php foreach($choices as $key => [$label, $options]): ?>
                    <fieldset class="fe-choice">
                        <legend><?= $t($label) ?></legend>
                        <div class="fe-chips">
                            <?php foreach($options as $value => $option_label): ?>
                                <label class="fe-chip"><input type="radio" name="style_<?= $key ?>" value="<?= $h($value) ?>" <?= ($style[$key] ?? array_key_first($options)) === $value ? 'checked' : '' ?>><span><?= $t($option_label) ?></span></label>
                            <?php endforeach ?>
                        </div>
                    </fieldset>
                <?php endforeach ?>
                <button type="button" class="fe-button fe-button-soft" data-fe-style-reset><?= $t('Vrati izgled stila') ?></button>
            </div>
        </details>
        <?php /* On phones this bar stays at the bottom of the panel, so Spremi izgled is always in reach. */ ?>
        <div class="fe-save-bar">
            <p class="fe-preview-note" data-fe-design-note hidden><span><?= $t('Pregled prikazuje odabrani izgled. Spremi ga da ga vide i posjetitelji.') ?></span> <button type="button" class="fe-link-button" data-fe-design-revert><?= $t('Vrati spremljeno') ?></button></p>
            <div class="fe-form-actions"><button type="submit" class="fe-button"><?= $t('Spremi izgled') ?></button></div>
            <p class="fe-form-error" role="alert" data-fe-error hidden></p>
        </div>
    </form>
    <?php if(!$state['is_v2']): ?><p class="fe-note"><?= $t('Teme, boje i fontove stare kartice uređuješ u klasičnom uređivaču.') ?> <a href="<?= url('link/' . (int) $link->link_id . '?tab=settings&editor=design') ?>"><?= $t('Otvori klasični uređivač') ?></a></p><?php endif ?>
    <p class="fe-more"><?= $t('Adresu kartice, naslov za tražilice i ostale postavke nalaziš u klasičnom uređivaču.') ?> <a href="<?= url('link/' . (int) $link->link_id . '?tab=settings&editor=settings') ?>"><?= $t('Postavke kartice') ?></a></p>
</section>
<?php /* /Custom code: FC-2026-10-12 */ ?>
