<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-03-09: auto product image fallback from selected blog product */
$forever_product_image = null;
if(!empty($data->link->settings->image)) {
    $forever_product_image = UPLOADS_FULL_URL . 'block_thumbnail_images/' . $data->link->settings->image;
} elseif(!empty($data->link->settings->product_image_url)) {
    $forever_product_image = $data->link->settings->product_image_url;
}
/* /Custom code: FC-2026-03-09 */
?>

<div id="<?= 'biolink_block_id_' . $data->link->biolink_block_id ?>" data-biolink-block-id="<?= $data->link->biolink_block_id ?>" class="col-12 my-2">
    <!-- Custom code: FC-2026-03-09: compact forever product card inline override -->
    <a href="<?= $data->link->location_url . $data->link->utm_query ?>" data-track-biolink-block-id="<?= $data->link->biolink_block_id ?>" target="<?= $data->link->settings->open_in_new_tab ? '_blank' : '_self' ?>" rel="<?= $data->user->plan_settings->dofollow_is_enabled ? 'dofollow' : 'nofollow' ?>" class="btn btn-block btn-primary link-btn link-hover-animation <?= 'link-btn-' . $data->link->settings->border_radius ?> <?= $data->link->design->link_class ?> fcc-forever-product-card" style="<?= $data->link->design->link_style ?> display:flex;align-items:center;justify-content:space-between;gap:.78rem;padding:.78rem .95rem;text-align:left;min-height:88px;overflow:hidden;" data-text-color data-border-width data-border-radius data-border-style data-border-color data-border-shadow data-animation data-background-color>
        <span class="fcc-forever-product-content" style="display:flex;flex-direction:column;align-items:flex-start;justify-content:center;gap:.2rem;min-width:0;flex:1 1 auto;text-align:left;">
            <span class="fcc-forever-product-title" data-name style="font-size:1.02rem;font-weight:700;line-height:1.24;word-break:break-word;"><?= $data->link->settings->name ?></span>

            <?php if(!empty($data->link->settings->description ?? null)): ?>
                <span class="fcc-forever-product-description" style="font-size:.86rem;line-height:1.34;opacity:.66;display:-webkit-box;line-clamp:2;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word;"><?= $data->link->settings->description ?></span>
            <?php endif ?>
        </span>

        <span class="fcc-forever-product-image-wrap <?= 'link-btn-' . $data->link->settings->border_radius ?>" <?= $forever_product_image ? 'style="display:inline-flex;align-items:center;justify-content:center;width:58px;height:58px;flex:0 0 58px;border-radius:12px;overflow:hidden;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);"' : 'style="display:none;"' ?>>
            <img src="<?= $forever_product_image ?>" class="fcc-forever-product-image" loading="lazy" style="display:block;width:100%;height:100%;object-fit:cover;object-position:center;" onerror="this.closest('.fcc-forever-product-image-wrap').style.display='none';" />
        </span>
    </a>
    <!-- /Custom code: FC-2026-03-09 -->
</div>
