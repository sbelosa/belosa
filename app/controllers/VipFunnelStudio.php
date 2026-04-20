<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Response;
use Altum\Title;

defined('ALTUMCODE') || die();

class VipFunnelStudio extends Controller {

    private function resolve_access_or_exit() {
        \Altum\Authentication::guard();

        if(vip_funnel_demo_is_sandbox_user($this->user)) {
            return (object) [
                'can_access' => false,
                'show_sidebar_entry' => true,
            ];
        }

        $access = vip_funnel_resolve_access_state($this->user);

        if(!$access->can_access && !$access->show_sidebar_entry) {
            Alerts::add_info(l('global.info_message.plan_feature_no_access'));
            redirect('dashboard');
        }

        return $access;
    }

    private function decode_posted_payload($user = null): ?array {
        if(isset($_POST['reset_vip_funnel_studio'])) {
            return vip_funnel_get_studio_seed_payload($user);
        }

        $raw_payload = trim((string) ($_POST['vip_funnel_studio_payload'] ?? ''));

        if($raw_payload !== '') {
            $decoded_payload = json_decode($raw_payload, true);

            if(json_last_error() === JSON_ERROR_NONE && is_array($decoded_payload)) {
                return vip_funnel_normalize_studio_payload($decoded_payload, $user);
            }

            Alerts::add_error(l('vip_funnel.alert.invalid_board'));
            return null;
        }

        $raw_board_payload = trim((string) ($_POST['vip_funnel_board_payload'] ?? ''));

        if($raw_board_payload !== '') {
            $decoded_board_payload = json_decode($raw_board_payload, true);

            if(json_last_error() === JSON_ERROR_NONE && is_array($decoded_board_payload)) {
                $payload = vip_funnel_get_studio_state($user)['payload'] ?? vip_funnel_get_studio_seed_payload($user);
                $payload['board'] = $decoded_board_payload;

                return vip_funnel_normalize_studio_payload($payload, $user);
            }
        }

        Alerts::add_error(l('vip_funnel.alert.invalid_board'));

        return null;
    }

    private function persist_payload(array $payload): bool {
        $payload = vip_funnel_normalize_studio_payload($payload, $this->user);

        if(vip_funnel_studio_schema_is_ready()) {
            $saved = vip_funnel_studio_save_to_database($this->user, $payload);
        } else {
            $preferences = vip_funnel_set_user_studio_board_preferences(vip_funnel_get_user_preferences($this->user), $payload['board'] ?? []);
            $preferences->vip_funnel_studio_full = (object) [
                'version' => 2,
                'updated_at' => get_date(),
                'payload' => $payload,
            ];
            $saved = vip_funnel_save_user_preferences((int) $this->user->user_id, $preferences);
        }

        return $saved;
    }

    private function collect_validation_errors(array $payload): array {
        return vip_funnel_collect_payload_validation_errors($payload);
    }

    public function index() {
        $access = $this->resolve_access_or_exit();

        if(vip_funnel_demo_render_locked_route($this, $this->user, 'vip_funnel', [
            'back_url' => url('dashboard'),
        ])) {
            return;
        }

        Title::set(l('vip_funnel.title'));

        if($access->can_access && !empty($_POST)) {
            if(!\Altum\Csrf::check('token') && !\Altum\Csrf::check('global_token')) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            } else {
                $payload = $this->decode_posted_payload($this->user);

                if($payload !== null && !Alerts::has_field_errors() && !Alerts::has_errors()) {
                    $validation_errors = $this->collect_validation_errors($payload);

                    if(!empty($validation_errors)) {
                        Alerts::add_error($validation_errors[0]['message'] ?? l('vip_funnel.alert.validation_fix_before_save'));
                    }
                }

                if($payload !== null && !Alerts::has_field_errors() && !Alerts::has_errors()) {
                    if($this->persist_payload($payload)) {
                        Alerts::add_success(l('vip_funnel.alert.saved'));
                        redirect('vip-funnel-studio');
                    }

                    Alerts::add_error(l('vip_funnel.alert.save_failed'));
                }
            }
        }

        $studio = vip_funnel_get_studio_state($this->user);
        $payload = $studio['payload'];
        $preferred_product_language_code = (string) (\Altum\Language::$code ?? \Altum\Language::$default_code);
        $product_catalog = vip_funnel_get_product_catalog($preferred_product_language_code);
        $product_language_codes = vip_funnel_get_product_language_codes();
        $product_language_options = [];

        foreach($product_language_codes as $language_code) {
            $product_language_options[$language_code] = mb_strtoupper((string) $language_code);
        }

        $view = new \Altum\View($access->can_access ? 'vip-funnel-studio/index' : 'vip-funnel-studio/locked', (array) $this);
        $this->add_view_content('content', $view->run([
            'access' => $access,
            'studio' => $studio,
            'payload' => $payload,
            'demo_access_url' => url('vip-funnel-demo-access'),
            'image_upload_url' => url('vip-funnel-studio/upload-image'),
            'image_upload_max_size_mb' => vip_funnel_get_image_upload_size_limit_mb(),
            'image_upload_accept' => \Altum\Uploads::get_whitelisted_file_extensions_accept('vip_funnel_images'),
            'image_gallery_entries' => vip_funnel_get_image_gallery_entries($this->user, $payload),
            'dashboard_url' => url('dashboard'),
            'account_plan_url' => url('account-plan'),
            'status_options' => vip_funnel_get_step_status_options(),
            'card_type_options' => vip_funnel_get_card_type_options(),
            'goal_options' => vip_funnel_get_goal_options(),
            'block_mode_options' => vip_funnel_get_block_mode_options(),
            'block_template_presets' => vip_funnel_get_block_template_presets(),
            'page_block_type_options' => vip_funnel_get_page_block_type_options(),
            'page_width_options' => vip_funnel_get_page_theme_width_options(),
            'page_alignment_options' => vip_funnel_get_page_block_alignment_options(),
            'page_block_width_options' => vip_funnel_get_page_block_width_options(),
            'page_action_options' => vip_funnel_get_page_action_type_options(),
            'page_font_family_options' => vip_funnel_get_page_font_family_options(),
            'page_font_family_css_map' => vip_funnel_get_page_font_family_css_map(),
            'page_font_weight_options' => vip_funnel_get_page_font_weight_options(),
            'page_block_template_presets' => vip_funnel_get_page_block_template_presets(),
            'product_catalog' => $product_catalog,
            'product_source_mode_options' => vip_funnel_get_product_source_mode_options(),
            'product_target_mode_options' => vip_funnel_get_product_target_mode_options(),
            'product_language_mode_options' => vip_funnel_get_product_language_mode_options(),
            'product_language_options' => $product_language_options,
            'preferred_product_language_code' => $preferred_product_language_code,
            'visibility_options' => vip_funnel_get_visibility_options(),
            'design_variant_options' => vip_funnel_get_design_variant_options(),
            'owner_options' => $studio['owner_options'] ?? [],
        ]));
    }

    public function save_ajax() {
        $access = $this->resolve_access_or_exit();

        if(vip_funnel_demo_is_sandbox_user($this->user)) {
            Response::json(vip_funnel_demo_get_locked_action_message('vip_funnel'), 'error');
        }

        if(!$access->can_access) {
            Response::json(l('global.info_message.plan_feature_no_access'), 'error');
        }

        if(!\Altum\Csrf::check('token') && !\Altum\Csrf::check('global_token')) {
            Response::json(l('global.error_message.invalid_csrf_token'), 'error');
        }

        $payload = $this->decode_posted_payload($this->user);

        if($payload === null) {
            Response::json(l('vip_funnel.alert.invalid_board'), 'error');
        }

        $validation_errors = $this->collect_validation_errors($payload);

        if(!empty($validation_errors)) {
            Response::json(l('vip_funnel.alert.validation_fix_before_save'), 'error', [
                'validation_errors' => $validation_errors,
            ]);
        }

        if(!$this->persist_payload($payload)) {
            Response::json(l('vip_funnel.alert.save_failed'), 'error');
        }

        Response::json(l('vip_funnel.alert.saved'), 'success');
    }

    public function upload_image() {
        $access = $this->resolve_access_or_exit();

        if(vip_funnel_demo_is_sandbox_user($this->user)) {
            Response::json(vip_funnel_demo_get_locked_action_message('vip_funnel'), 'error');
        }

        if(!$access->can_access) {
            Response::json(l('global.info_message.plan_feature_no_access'), 'error');
        }

        if(!\Altum\Csrf::check('token') && !\Altum\Csrf::check('global_token')) {
            Response::json(l('global.error_message.invalid_csrf_token'), 'error');
        }

        if(empty($_FILES['image']['name'])) {
            Response::json(l('vip_funnel.alert.image_upload_missing'), 'error');
        }

        $image = \Altum\Uploads::process_upload(
            null,
            'vip_funnel_images',
            'image',
            'image_remove',
            vip_funnel_get_image_upload_size_limit_mb(),
            'json_error'
        );

        if(!$image) {
            Response::json(l('vip_funnel.alert.image_upload_failed'), 'error');
        }

        $image_url = \Altum\Uploads::get_full_url('vip_funnel_images') . $image;
        vip_funnel_register_image_in_gallery($this->user, $image, $image_url);

        Response::json(l('vip_funnel.alert.image_uploaded'), 'success', [
            'image' => $image,
            'image_url' => $image_url,
        ]);
    }
}
