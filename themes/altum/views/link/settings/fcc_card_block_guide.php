<?php defined('ALTUMCODE') || die() ?>
<?php
/* Custom code: FC-2026-10-12: simple block guide on top of "Dodaj blok".
 * Expects $fcc_guide_link (the card), $fcc_guide_plan_blocks (plan block flags) and $fcc_guide_blocks
 * (block types that have a create form). Locked blocks explain that they come with PRO. */
$fcc_guide_is_v2 = fcc_card_is_v2($fcc_guide_link);
$fcc_guide_catalog = fcc_card_block_catalog();
$fcc_guide_rows = [];
foreach($fcc_guide_catalog as $fcc_guide_key => $fcc_guide_item) {
    if(!isset($fcc_guide_blocks[$fcc_guide_key])) continue;
    $fcc_guide_open = in_array($fcc_guide_key, ['link_forever_webshop_reg', 'link_forever_shop'], true) || fcc_card_block_open($fcc_guide_key, $fcc_guide_plan_blocks, $fcc_guide_is_v2);
    $fcc_guide_rows[$fcc_guide_item['group']][] = [$fcc_guide_key, $fcc_guide_item, $fcc_guide_open];
}
$fcc_guide_h = static fn(string $text): string => htmlspecialchars(fcc_t($text), ENT_QUOTES, 'UTF-8');
?>
<style>
    #biolink_link_create_modal .fcc-card-guide { margin: 0 0 18px; color: #183b37; }
    #biolink_link_create_modal .fcc-card-guide-lead { margin: 0 0 14px; font-size: .95rem; line-height: 1.5; color: #526b65; }
    #biolink_link_create_modal .fcc-card-guide-group { margin: 0 0 16px; padding: 14px; border-radius: 16px; background: #EEF6F1; }
    #biolink_link_create_modal .fcc-card-guide-group h6 { margin: 0 0 2px; font-size: 1rem; font-weight: 700; color: #183b37 !important; }
    #biolink_link_create_modal .fcc-card-guide-group > p { margin: 0 0 10px; font-size: .875rem; color: #526b65; }
    #biolink_link_create_modal .fcc-card-guide-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 8px; }
    #biolink_link_create_modal .fcc-card-guide-item { display: flex; gap: 12px; align-items: flex-start; width: 100%; padding: 12px; text-align: left; border: 1px solid #dce7e1; border-radius: 14px; background: #fff; color: #183b37; cursor: pointer; transition: border-color .15s ease; }
    #biolink_link_create_modal .fcc-card-guide-item:hover, #biolink_link_create_modal .fcc-card-guide-item:focus-visible { border-color: #12775B; outline: none; }
    #biolink_link_create_modal .fcc-card-guide-item[aria-disabled="true"] { cursor: default; opacity: .72; }
    #biolink_link_create_modal .fcc-card-guide-icon { flex: 0 0 38px; width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: #DDEFE5; color: #12775B; }
    #biolink_link_create_modal .fcc-card-guide-item strong { display: flex; gap: 6px; align-items: center; font-size: .95rem; font-weight: 700; color: #183b37; }
    #biolink_link_create_modal .fcc-card-guide-item span.fcc-card-guide-what { display: block; margin-top: 2px; font-size: .85rem; line-height: 1.45; color: #34504a; }
    #biolink_link_create_modal .fcc-card-guide-item span.fcc-card-guide-how { display: block; margin-top: 4px; font-size: .8rem; line-height: 1.45; color: #607a73; }
    #biolink_link_create_modal .fcc-card-guide-badge { display: inline-block; padding: 1px 7px; border-radius: 999px; font-size: .7rem; font-weight: 700; background: #F3E6C4; color: #6B4E0E; }
    #biolink_link_create_modal .fcc-card-guide-all { margin-top: 6px; }
    #biolink_link_create_modal .fcc-card-guide-all > summary { cursor: pointer; padding: 12px 14px; border: 1px dashed #b9cfc4; border-radius: 14px; font-weight: 700; color: #183b37; list-style: none; }
    #biolink_link_create_modal .fcc-card-guide-all > summary::-webkit-details-marker { display: none; }
    #biolink_link_create_modal .fcc-card-guide-all > summary small { display: block; font-weight: 400; color: #607a73; }
    #biolink_link_create_modal .fcc-card-guide-all[open] > summary { margin-bottom: 14px; }
</style>
<div class="fcc-card-guide" data-fcc-card-guide>
    <p class="fcc-card-guide-lead"><?= $fcc_guide_h('Odaberi što želiš dodati. Uz svaki blok piše čemu služi i kako ga iskoristiti. Sve možeš kasnije promijeniti ili ukloniti.') ?></p>
    <?php foreach(fcc_card_block_groups() as $fcc_guide_group => $fcc_guide_group_copy): ?>
        <?php if(empty($fcc_guide_rows[$fcc_guide_group])) continue ?>
        <section class="fcc-card-guide-group" aria-label="<?= $fcc_guide_h($fcc_guide_group_copy['title']) ?>">
            <h6><?= $fcc_guide_h($fcc_guide_group_copy['title']) ?></h6>
            <p><?= $fcc_guide_h($fcc_guide_group_copy['text']) ?></p>
            <div class="fcc-card-guide-list">
                <?php foreach($fcc_guide_rows[$fcc_guide_group] as [$fcc_guide_key, $fcc_guide_item, $fcc_guide_open]): ?>
                    <button type="button" class="fcc-card-guide-item" data-fcc-guide-block="<?= $fcc_guide_key ?>"
                        <?= $fcc_guide_open ? 'data-dismiss="modal" data-toggle="modal" data-target="#create_biolink_' . $fcc_guide_key . '"' : 'aria-disabled="true" onclick="event.preventDefault();return false;"' ?>>
                        <span class="fcc-card-guide-icon" aria-hidden="true"><i class="<?= htmlspecialchars((string) ($fcc_guide_blocks[$fcc_guide_key]['icon'] ?? 'fas fa-square'), ENT_QUOTES, 'UTF-8') ?> fa-fw"></i></span>
                        <span>
                            <strong><?= $fcc_guide_h($fcc_guide_item['title']) ?><?php if(!$fcc_guide_open): ?> <span class="fcc-card-guide-badge">PRO</span><?php endif ?></strong>
                            <span class="fcc-card-guide-what"><?= $fcc_guide_h($fcc_guide_item['what']) ?></span>
                            <span class="fcc-card-guide-how"><?= $fcc_guide_open ? $fcc_guide_h($fcc_guide_item['how']) : $fcc_guide_h('Dostupno uz PRO paket.') ?></span>
                        </span>
                    </button>
                <?php endforeach ?>
            </div>
        </section>
    <?php endforeach ?>
</div>
<?php /* /Custom code: FC-2026-10-12 */ ?>
