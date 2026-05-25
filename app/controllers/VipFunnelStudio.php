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
            Alerts::add_error('Vraćanje početnog studija je onemogućeno jer može prebrisati funnel koji je već u izradi.');
            return null;
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

    private function get_selected_funnel_id(): int {
        $funnel_id = (int) ($_POST['funnel_id'] ?? ($_GET['funnel_id'] ?? 0));

        if($funnel_id <= 0 || !vip_funnel_studio_schema_is_ready()) {
            return 0;
        }

        $row = vip_funnel_studio_get_funnel_row((int) ($this->user->user_id ?? 0), $funnel_id);

        return $row ? $funnel_id : 0;
    }

    private function persist_payload(array $payload, int $funnel_id = 0): bool {
        $payload = vip_funnel_normalize_studio_payload($payload, $this->user);

        if(vip_funnel_studio_schema_is_ready()) {
            $saved = vip_funnel_studio_save_to_database($this->user, $payload, $funnel_id);
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
        $selected_funnel_id = $this->get_selected_funnel_id();
        $show_editor = $selected_funnel_id > 0 || isset($_GET['editor']);

        if($access->can_access && vip_funnel_studio_schema_is_ready() && function_exists('vip_funnel_get_personalized_primary_payload') && vip_funnel_get_personalized_primary_payload($this->user)) {
            vip_funnel_studio_ensure_primary_funnel($this->user);
        }

        if($access->can_access && $show_editor && vip_funnel_studio_schema_is_ready()) {
            vip_funnel_studio_ensure_primary_funnel($this->user);
        }

        if($access->can_access && !empty($_POST)) {
            if(!\Altum\Csrf::check('token') && !\Altum\Csrf::check('global_token')) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            } else {
                if(isset($_POST['delete_vip_funnel'])) {
                    $delete_funnel_id = (int) ($_POST['delete_funnel_id'] ?? 0);
                    $delete_confirmed = (int) ($_POST['delete_funnel_confirmed'] ?? 0) === 1;

                    if(!$delete_confirmed) {
                        Alerts::add_error('Za brisanje funnel-a potrebno je potvrditi da razumiješ da se radnja ne može poništiti.');
                    } elseif($delete_funnel_id <= 0) {
                        Alerts::add_error('Funnel za brisanje nije pronađen.');
                    } elseif(vip_funnel_studio_delete_funnel($this->user, $delete_funnel_id)) {
                        Alerts::add_success('Funnel je trajno obrisan. Kontakti i demo zapisi ostaju spremljeni u pregledima.');
                        redirect('vip-funnel-studio');
                    } else {
                        Alerts::add_error('Funnel nije obrisan. Provjeri je li funnel još uvijek tvoj i pokušaj ponovno.');
                    }
                }

                if(isset($_POST['create_vip_funnel'])) {
                    $payload = vip_funnel_get_studio_seed_payload($this->user);
                    $payload['funnel']['name'] = trim(input_clean((string) ($_POST['funnel_name'] ?? ''), 120)) ?: 'Novi VIP Funnel 2.0';
                    $payload['funnel']['slug'] = vip_funnel_slugify($payload['funnel']['name']);

                    if($row = vip_funnel_studio_create_funnel_from_payload($this->user, $payload)) {
                        Alerts::add_success('Novi funnel je kreiran.');
                        redirect('vip-funnel-studio?funnel_id=' . (int) $row->vip_funnel_id);
                    }

                    Alerts::add_error(l('vip_funnel.alert.save_failed'));
                }

                if(isset($_POST['import_vip_funnel_template'])) {
                    $template_key = input_clean((string) ($_POST['template_key'] ?? ''), 80);
                    $template_language = input_clean((string) ($_POST['template_language'] ?? 'hr'), 8);
                    $payload = vip_funnel_get_import_template_payload($template_key, $this->user, $template_language);

                    if($payload && ($row = vip_funnel_studio_create_funnel_from_payload($this->user, $payload))) {
                        Alerts::add_success('FCC VIP funnel je importiran i spreman za uređivanje.');
                        redirect('vip-funnel-studio?funnel_id=' . (int) $row->vip_funnel_id);
                    }

                    Alerts::add_error('Import FCC VIP funnel-a trenutno nije uspio.');
                }

                $payload = isset($_POST['create_vip_funnel']) || isset($_POST['import_vip_funnel_template']) || isset($_POST['delete_vip_funnel']) ? null : $this->decode_posted_payload($this->user);

                if($payload !== null && !Alerts::has_field_errors() && !Alerts::has_errors()) {
                    $validation_errors = $this->collect_validation_errors($payload);

                    if(!empty($validation_errors)) {
                        Alerts::add_error($validation_errors[0]['message'] ?? l('vip_funnel.alert.validation_fix_before_save'));
                    }
                }

                if($payload !== null && !Alerts::has_field_errors() && !Alerts::has_errors()) {
                    if($this->persist_payload($payload, $selected_funnel_id)) {
                        Alerts::add_success(l('vip_funnel.alert.saved'));
                        redirect('vip-funnel-studio' . ($selected_funnel_id > 0 ? '?funnel_id=' . $selected_funnel_id : ''));
                    }

                    Alerts::add_error(l('vip_funnel.alert.save_failed'));
                }
            }
        }

        if($access->can_access && !$show_editor) {
            $funnels = vip_funnel_studio_schema_is_ready() ? vip_funnel_studio_get_funnel_rows((int) $this->user->user_id) : [];
            $dashboard_funnels = [];

            foreach($funnels as $row) {
                $payload = vip_funnel_studio_load_from_database($this->user, (int) ($row->vip_funnel_id ?? 0));
                $analytics = vip_funnel_get_analytics_snapshot((int) ($row->vip_funnel_id ?? 0), $payload);
                $dashboard_funnels[] = [
                    'row' => $row,
                    'payload' => $payload,
                    'analytics' => $analytics,
                    'public_url' => vip_funnel_get_public_funnel_url((int) $this->user->user_id, (string) ($row->slug ?? 'vip-funnel-2-0')),
                    'edit_url' => url('vip-funnel-studio?funnel_id=' . (int) ($row->vip_funnel_id ?? 0)),
                    'analytics_url' => url('vip-funnel-studio?funnel_id=' . (int) ($row->vip_funnel_id ?? 0) . '#analytics'),
                ];
            }

            $view = new \Altum\View('vip-funnel-studio/dashboard', (array) $this);
            $this->add_view_content('content', $view->run([
                'access' => $access,
                'funnels' => $dashboard_funnels,
                'import_templates' => vip_funnel_get_import_template_options($this->user),
                'analytics_url' => url('vip-funnel-studio?editor=1#analytics'),
            ]));
            return;
        }

        $studio = vip_funnel_get_studio_state($this->user, $selected_funnel_id);
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
            'selected_funnel_id' => (int) ($studio['funnel_row']->vip_funnel_id ?? $selected_funnel_id),
            'funnels_index_url' => url('vip-funnel-studio'),
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

        if(!$this->persist_payload($payload, $this->get_selected_funnel_id())) {
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

        $upload_purpose = input_clean((string) ($_POST['purpose'] ?? 'general'), 32);

        if($upload_purpose === 'seo' && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK) {
            $file_extension = mb_strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $file_mime_type = !empty($_FILES['image']['tmp_name']) && is_file($_FILES['image']['tmp_name']) ? mime_content_type($_FILES['image']['tmp_name']) : (string) ($_FILES['image']['type'] ?? '');

            if(!in_array($file_extension, ['jpg', 'jpeg', 'png'], true) || !in_array($file_mime_type, ['image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'], true)) {
                Response::json('SEO fotografija za dijeljenje mora biti JPG ili PNG datoteka.', 'error');
            }
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
