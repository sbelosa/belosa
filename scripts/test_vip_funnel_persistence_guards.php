<?php
/* Custom code: FC-2026-08-20: regression checks for VIP Funnel persistence and routing safety */

$root = dirname(__DIR__);
$helper = file_get_contents($root . '/app/helpers/vip_funnel.php');
$controller = file_get_contents($root . '/app/controllers/VipFunnelStudio.php');
$block_controller = file_get_contents($root . '/app/controllers/BiolinkBlockAjax.php');
$studio_view = file_get_contents($root . '/themes/altum/views/vip-funnel-studio/index.php');
$hub_update_view = file_get_contents($root . '/themes/altum/views/link/settings/biolink_blocks/vip_funnel_hub/vip_funnel_hub_update_form.php');
$hub_create_view = file_get_contents($root . '/themes/altum/views/link/settings/biolink_blocks/vip_funnel_hub/vip_funnel_hub_create_modal.php');
$hub_public_view = file_get_contents($root . '/themes/altum/views/l/biolink_blocks/vip_funnel_hub.php');

if(
    $helper === false
    || $controller === false
    || $block_controller === false
    || $studio_view === false
    || $hub_update_view === false
    || $hub_create_view === false
    || $hub_public_view === false
) {
    fwrite(STDERR, "Could not load VIP Funnel sources.\n");
    exit(1);
}

$extract = static function(string $source, string $start, string $end): string {
    $start_position = strpos($source, $start);
    $end_position = $start_position === false ? false : strpos($source, $end, $start_position + strlen($start));

    if($start_position === false || $end_position === false) {
        return '';
    }

    return substr($source, $start_position, $end_position - $start_position);
};

$public_resolver = $extract(
    $helper,
    'function vip_funnel_get_public_payload_for_user(',
    'function vip_funnel_get_public_funnel_url('
);
$hub_renderer = $extract(
    $helper,
    'function vip_funnel_get_public_hub_render_data(',
    'function vip_funnel_get_user_funnel_select_options('
);
$ajax_save = $extract(
    $controller,
    'public function save_ajax()',
    'public function upload_image()'
);
$save_studio = $extract(
    $studio_view,
    'async function saveStudio()',
    'async function uploadImageForBlock('
);
$focusout_handler = $extract(
    $studio_view,
    "workspaceRoot.addEventListener('focusout'",
    "workspaceRoot.addEventListener('change'"
);

$focusout_apply_position = strpos($focusout_handler, 'applyGenericFieldUpdate(field)');
$focusout_render_position = strpos($focusout_handler, 'renderAll()');
$save_flush_position = strpos($save_studio, 'flushFunnelSettingsFields()');
$save_sync_position = strpos($save_studio, 'syncPayloadInput()');

$assertions = [
    'interactive save resolves and validates an explicit funnel ID before persistence' => str_contains($ajax_save, '$selected_funnel_id = $this->get_selected_funnel_id()')
        && str_contains($ajax_save, '$this->persist_payload($normalized_payload, $selected_funnel_id)'),
    'AJAX rejects a missing or foreign funnel ID' => str_contains($ajax_save, "'code' => 'invalid_funnel_id'")
        && str_contains($ajax_save, 'http_response_code(422)'),
    'AJAX re-reads and confirms persisted status and visibility' => str_contains($ajax_save, "'code' => 'persistence_mismatch'")
        && str_contains($ajax_save, "'persisted' => [")
        && str_contains($ajax_save, "'visibility_mode' => (string)"),
    'legacy preference storage still receives a verifiable success response' => str_contains($ajax_save, 'if(!$schema_is_ready)')
        && str_contains($ajax_save, "'funnel_id' => 0"),
    'focusout captures the live field before rebuilding the editor' => $focusout_apply_position !== false
        && $focusout_render_position !== false
        && $focusout_apply_position < $focusout_render_position,
    'focusout rebuild is coalesced in requestAnimationFrame' => str_contains($focusout_handler, 'cancelAnimationFrame')
        && str_contains($focusout_handler, 'requestAnimationFrame')
        && str_contains($focusout_handler, 'workspaceRoot.contains(document.activeElement)'),
    'save flushes visible funnel settings before serializing payload' => $save_flush_position !== false
        && $save_sync_position !== false
        && $save_flush_position < $save_sync_position,
    'frontend verifies the exact server persistence confirmation' => str_contains($save_studio, 'requestedPersistence')
        && str_contains($save_studio, 'result?.details?.persisted')
        && str_contains($save_studio, 'Number(persisted.funnel_id) !== requestedPersistence.funnel_id'),
    'hub resolves an explicit ID once without falling back after a miss' => str_contains($hub_renderer, 'vip_funnel_get_public_payload_for_user($user_id, \'\', $selected_funnel_id)')
        && substr_count($hub_renderer, 'vip_funnel_get_public_payload_for_user(') === 1
        && str_contains($public_resolver, 'if($funnel_id > 0)')
        && str_contains($public_resolver, 'never resolve an explicit VIP Funnel ID through legacy preferences'),
    'legacy ID zero blocks retain their primary-funnel compatibility' => !str_contains($hub_renderer, '$selected_funnel_id > 0 ?'),
    'direct public slugs retain legacy draft/testing preview compatibility' => str_contains($public_resolver, 'vip_funnel_studio_get_primary_funnel_row($user_id)')
        && !str_contains($public_resolver, 'vip_funnel_is_publicly_published_funnel_row'),
    'hub forms require an exact funnel instead of an implicit primary fallback' => !str_contains($hub_update_view, 'Primarni funnel')
        && !str_contains($hub_create_view, 'Primarni funnel')
        && str_contains($hub_update_view, 'Odaberi točan funnel')
        && str_contains($hub_create_view, 'Odaberi točan funnel'),
    'hub create and update reject an invalid selected funnel' => substr_count($block_controller, "Odaberi valjani funnel koji će ovaj blok otvarati.") === 2,
    'card-wide navigation is optional while legacy blocks stay compatible' => str_contains($hub_update_view, 'name="card_click_enabled"')
        && str_contains($hub_public_view, 'array_key_exists(\'card_click_enabled\', $settings)')
        && str_contains($hub_public_view, '$is_card_clickable = $is_clickable && $card_click_enabled')
        && str_contains($block_controller, "'card_click_enabled' => false"),
];

$failed = array_keys(array_filter($assertions, static fn($passed) => !$passed));

if($failed) {
    fwrite(STDERR, "VIP Funnel persistence guard check failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "VIP Funnel persistence and routing guard checks passed.\n";

/* /Custom code: FC-2026-08-20 */
