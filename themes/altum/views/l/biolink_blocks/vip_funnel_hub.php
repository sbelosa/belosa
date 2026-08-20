<?php defined('ALTUMCODE') || die() ?>

<?php
$settings = vip_funnel_to_array($data->link->settings ?? []);
$render = vip_funnel_get_public_hub_render_data((int) ($data->link->user_id ?? 0), $settings);

if(array_key_exists('path_tags', $settings)) {
    $render['paths'] = [];

    foreach((array) ($settings['path_tags'] ?? []) as $path_tag) {
        if(is_object($path_tag)) {
            $path_tag = (array) $path_tag;
        }

        if(is_array($path_tag)) {
            $path_tag = $path_tag['title'] ?? $path_tag['label'] ?? '';
        }

        $path_tag = input_clean((string) $path_tag, 80);

        if($path_tag === '') {
            continue;
        }

        $render['paths'][] = ['title' => $path_tag];

        if(count($render['paths']) >= 3) {
            break;
        }
    }
}

$border_radius_map = [
    'straight' => '.8rem',
    'rounded' => '1.35rem',
    'round' => '1.8rem',
];

$border_radius = $border_radius_map[$settings['border_radius'] ?? 'rounded'] ?? '1.35rem';
$box_shadow_style = \Altum\Link::get_processed_box_shadow_style($settings);
$cta_url = $render['primary_url'] ?? ($data->link->location_url ?? '');
$secondary_url = $render['secondary_url'] ?? '';
$is_clickable = (bool) $cta_url;
/* Custom code: FC-2026-08-20: allow VIP Funnel navigation only from the CTA when configured */
$card_click_enabled = array_key_exists('card_click_enabled', $settings) ? !empty($settings['card_click_enabled']) : true;
$is_card_clickable = $is_clickable && $card_click_enabled;
/* /Custom code: FC-2026-08-20 */
$text_alignment = $settings['text_alignment'] ?? 'left';
$cta_justify_map = [
    'left' => 'flex-start',
    'center' => 'center',
    'right' => 'flex-end',
    'justify' => 'space-between',
];
$cta_justify = $cta_justify_map[$text_alignment] ?? 'flex-start';
$cta_align_items = $text_alignment === 'justify' ? 'stretch' : 'center';
$wrapper_style = sprintf(
    'border:%dpx %s %s;border-radius:%s;background:%s;color:%s;%stext-align:%s;%s',
    (int) ($settings['border_width'] ?? 0),
    $settings['border_style'] ?? 'solid',
    $settings['border_color'] ?? '#101826',
    $border_radius,
    $settings['background_color'] ?? '#101826',
    $settings['text_color'] ?? '#ffffff',
    $box_shadow_style,
    $text_alignment,
    /* Custom code: FC-2026-08-20: show a pointer only when the whole VIP Funnel card is clickable */
    $is_card_clickable ? 'cursor:pointer;' : ''
    /* /Custom code: FC-2026-08-20 */
);
$block_image = !empty($settings['image']) ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $settings['image'] : null;
$columns = (int) ($settings['columns'] ?? 1) === 2 ? '6' : '12';
?>

<div id="<?= 'biolink_block_id_' . $data->link->biolink_block_id ?>" data-biolink-block-id="<?= $data->link->biolink_block_id ?>" data-biolink-block-type="<?= $data->link->type ?>" class="col-12 col-lg-<?= $columns ?> my-<?= $data->biolink->settings->block_spacing ?? '2' ?>">
    <div
        class="link-hover-animation-<?= ($data->biolink->settings->hover_animation ?? 'smooth') != 'false' ? ($data->biolink->settings->hover_animation ?? 'smooth') : 'smooth' ?> <?= 'link-btn-' . ($settings['border_radius'] ?? 'rounded') ?>"
        style="<?= $wrapper_style ?>"
        data-text-color
        data-border-width
        data-border-radius
        data-border-style
        data-border-color
        data-border-shadow
        data-animation
        data-background-color
        data-text-alignment
        <?php /* Custom code: FC-2026-08-20: attach card navigation semantics only when explicitly enabled */ ?>
        <?= $is_card_clickable ? 'data-vip-hub-primary-url="' . $cta_url . '" role="link" tabindex="0"' : '' ?>
        <?php /* /Custom code: FC-2026-08-20 */ ?>
    >
        <div style="padding:1.2rem; position:relative; overflow:hidden;">
            <div style="position:absolute; inset:auto -12% -32% auto; width:240px; height:240px; border-radius:999px; background:radial-gradient(circle, rgba(103, 216, 201, 0.18), transparent 62%); pointer-events:none;"></div>
            <div style="position:absolute; inset:-18% auto auto -12%; width:210px; height:210px; border-radius:999px; background:radial-gradient(circle, rgba(244, 182, 63, 0.18), transparent 62%); pointer-events:none;"></div>

            <?php if($block_image): ?>
                <div class="mb-3">
                    <img src="<?= $block_image ?>" alt="<?= htmlspecialchars((string) ($settings['name'] ?? 'VIP Funnel Hub'), ENT_QUOTES, 'UTF-8') ?>" loading="lazy" style="width:100%; max-height:11rem; object-fit:cover; border-radius:1rem; border:1px solid rgba(255,255,255,.08);" />
                </div>
            <?php endif ?>

            <?php if(!empty($render['kicker'])): ?>
                <div class="small text-uppercase font-weight-bold mb-2" style="letter-spacing:.12em; opacity:.78;">
                    <?php if(!empty($settings['icon'])): ?>
                        <i class="<?= htmlspecialchars($settings['icon'], ENT_QUOTES, 'UTF-8') ?> mr-1"></i>
                    <?php endif ?>
                    <?= htmlspecialchars($render['kicker'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif ?>

            <div class="h4 mb-3" style="color:inherit; line-height:1.15;"><?= htmlspecialchars($render['title'], ENT_QUOTES, 'UTF-8') ?></div>

            <?php if(!empty($render['subtitle'])): ?>
                <div class="mb-3" style="opacity:.86; line-height:1.6;"><?= htmlspecialchars($render['subtitle'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif ?>

            <?php if(!empty($render['show_paths']) && !empty($render['paths'])): ?>
                <div class="d-flex flex-wrap mb-3" style="gap:.5rem; justify-content:<?= $cta_justify ?>;" data-vip-funnel-hub-paths-alignment>
                    <?php foreach($render['paths'] as $path): ?>
                        <span style="display:inline-flex; align-items:center; padding:.38rem .7rem; border-radius:999px; border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.06); font-size:.78rem; font-weight:700;">
                            <?= htmlspecialchars((string) ($path['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <div class="d-flex flex-wrap" style="gap:.75rem; justify-content:<?= $cta_justify ?>; align-items:<?= $cta_align_items ?>;" data-vip-funnel-hub-cta-alignment>
                <?php if($cta_url): ?>
                    <a href="<?= $cta_url ?>" data-vip-hub-stop="true" class="btn btn-primary" style="font-weight:700; border-radius:.95rem; padding:.8rem 1.1rem;">
                        <?= htmlspecialchars($render['primary_cta_text'] ?: 'Otvori funnel', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php else: ?>
                    <button type="button" class="btn btn-light disabled" style="font-weight:700; border-radius:.95rem; padding:.8rem 1.1rem; opacity:.82; pointer-events:none;">
                        <?= htmlspecialchars($render['primary_cta_text'] ?: 'Otvori funnel', ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endif ?>

                <?php if($secondary_url && !empty($render['secondary_cta_text'])): ?>
                    <a href="<?= $secondary_url ?>" data-vip-hub-stop="true" class="btn btn-outline-light" style="font-weight:700; border-radius:.95rem; padding:.8rem 1.1rem;">
                        <?= htmlspecialchars($render['secondary_cta_text'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?php /* Custom code: FC-2026-08-20: register whole-card navigation only when explicitly enabled */ ?>
<?php if($is_card_clickable && !\Altum\Event::exists_content_type_key('javascript', 'vip_funnel_hub_click')): ?>
<?php /* /Custom code: FC-2026-08-20 */ ?>
    <?php ob_start() ?>
    <script>
        'use strict';

        document.addEventListener('click', event => {
            const stopTarget = event.target.closest('[data-vip-hub-stop="true"]');
            if(stopTarget) {
                return;
            }

            const block = event.target.closest('[data-vip-hub-primary-url]');
            if(!block) {
                return;
            }

            const url = block.getAttribute('data-vip-hub-primary-url');
            if(url) {
                window.location.href = url;
            }
        });

        document.addEventListener('keydown', event => {
            const block = event.target.closest('[data-vip-hub-primary-url]');
            if(!block) {
                return;
            }

            if(event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();

            const url = block.getAttribute('data-vip-hub-primary-url');
            if(url) {
                window.location.href = url;
            }
        });
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript', 'vip_funnel_hub_click') ?>
<?php endif ?>
