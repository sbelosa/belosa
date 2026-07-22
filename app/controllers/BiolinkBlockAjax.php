<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Date;
use Altum\Models\BiolinksThemes;
use Altum\Models\Domain;
use Altum\Response;
use Unirest\Request;

defined('ALTUMCODE') || die();

class BiolinkBlockAjax extends Controller {
    public $biolink_blocks = null;
    public $biolink_block = null;
    public $total_biolink_blocks = 0;
    /* Custom code: FC-2026-03-06: remove legacy albania_kosovo alias block from available block types */
    public $individual_blocks = ['link_app_switcher', 'link_back', 'link_forever_shop', 'link_forever_webshop_reg', 'link_forever_product', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_discount', 'link_homescreen_android', 'link_homescreen_ios', 'link_save_contact', 'custom_html_chatbot', 'custom_html_chatbot_pets', 'custom_html_whatsapp', 'link', 'featured_link', 'heading', 'big_link', 'paragraph', 'business_hours', 'markdown', 'avatar', 'socials', 'email_collector', 'rss_feed', 'custom_html', 'vcard', 'image', 'image_grid', 'divider', 'list', 'alert', 'faq', 'timeline', 'review', 'image_slider', 'discord', 'countdown', 'cta', 'external_item', 'share', 'coupon', 'youtube_feed', 'paypal', 'phone_collector', 'contact_collector', 'lead_funnel', 'vip_funnel_hub', 'donation', 'product', 'service', 'map', 'iframe', 'header', 'appointment_calendar', 'modal_text', 'image_comparison', 'weather', 'code', 'counter', 'loading'];
    /* /Custom code: FC-2026-03-06 */
    /* Custom code: FC-2026-03-09: restore embeddable block types for Vimeo and other embeds */
    public $embeddable_blocks = ['telegram', 'anchor', 'applemusic', 'soundcloud', 'threads', 'snapchat', 'spotify', 'tidal', 'mixcloud', 'kick', 'tiktok_video', 'vk_video', 'typeform', 'calendly', 'tiktok_profile', 'twitch', 'twitter_tweet', 'twitter_video', 'twitter_profile', 'pinterest_profile', 'vimeo', 'youtube', 'instagram_media', 'facebook', 'reddit', 'rumble', 'tumblr_post', 'bluesky_post', 'canva', 'google_form'];
    /* /Custom code: FC-2026-03-09 */
    public $file_blocks = ['audio', 'video', 'file', 'pdf_document', 'powerpoint_presentation', 'excel_spreadsheet'];

    /* Custom code: FC-2026-04-01: enforce plan-gated Forever blocks on the backend as well */
    private function is_block_enabled_for_current_plan(string $block_type): bool {
        $enabled_biolink_blocks = $this->user->plan_settings->enabled_biolink_blocks ?? (object) [];

        if(is_string($enabled_biolink_blocks)) {
            $enabled_biolink_blocks = json_decode($enabled_biolink_blocks) ?: (object) [];
        }

        if(is_array($enabled_biolink_blocks)) {
            $enabled_biolink_blocks = (object) $enabled_biolink_blocks;
        }

        if($block_type === 'link_forever_webshop_reg') {
            return (bool) (
                ($enabled_biolink_blocks->link_forever_webshop_reg ?? false)
                || ($enabled_biolink_blocks->link_forever_shop ?? false)
                || ($enabled_biolink_blocks->link_discount ?? false)
            );
        }

        return (bool) ($enabled_biolink_blocks->{$block_type} ?? false);
    }

    private function validate_block_access_for_current_plan(string $block_type): void {
        if($block_type === 'vip_funnel_hub' && function_exists('vip_funnel_resolve_access_state')) {
            $vip_funnel_access_state = vip_funnel_resolve_access_state($this->user);

            if(!empty($vip_funnel_access_state->can_access)) {
                return;
            }

            if(($vip_funnel_access_state->locked_reason ?? null) === 'testing') {
                Response::json(l('vip_funnel.block_gate.testing_message'), 'error');
            }
        }

        if($this->is_block_enabled_for_current_plan($block_type)) {
            return;
        }

        $message = l('global.info_message.plan_feature_no_access');

        if(settings()->payment->is_enabled) {
            $message .= ' ' . l('global.info_message.plan_upgrade') . ': ' . url('plan');
        }

        Response::json($message, 'error');
    }
    /* /Custom code: FC-2026-04-01 */


    public function index() {
        \Altum\Authentication::guard();

        if(empty($_POST)) {
            Response::json(l('global.error_message.basic'), 'error');
        }

        if(!(\Altum\Csrf::check('token') || \Altum\Csrf::check('global_token'))) {
            Response::json(l('global.error_message.invalid_csrf_token'), 'error');
        }

        $_POST['request_type'] = $_POST['request_type'] ?? null;
        if(!$_POST['request_type'] && isset($_POST['block_type'], $_POST['link_id'])) {
            $_POST['request_type'] = 'create';
        }

        if(!$_POST['request_type']) {
            Response::json(l('global.error_message.basic'), 'error');
        }

        switch($_POST['request_type']) {

            /* Status toggle */
            case 'is_enabled_toggle': $this->is_enabled_toggle(); break;

            /* Duplicate link */
            case 'duplicate': $this->duplicate(); break;

            /* Order links */
            case 'order': $this->order(); break;

            /* Create */
            case 'create': $this->create(); break;

            /* Update */
            case 'update': $this->update(); break;

            /* Delete */
            case 'delete': $this->delete(); break;

            default:
                Response::json(l('global.error_message.basic'), 'error');
        }

        die();
    }

    private function is_enabled_toggle() {
        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.biolinks_blocks')) {
            Response::json(l('global.info_message.team_no_access'), 'error');
        }

        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];

        /* Get the current status */
        $biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks', ['biolink_block_id', 'link_id', 'type', 'is_enabled']);

        if($biolink_block) {
            $new_is_enabled = (int) !$biolink_block->is_enabled;

            /* Custom code: FC-2026-04-01: do not let disabled plan blocks be re-enabled manually */
            if($new_is_enabled === 1) {
                $this->validate_block_access_for_current_plan($biolink_block->type);
            }
            /* /Custom code: FC-2026-04-01 */

            db()->where('biolink_block_id', $biolink_block->biolink_block_id)->update('biolinks_blocks', ['is_enabled' => $new_is_enabled]);

            /* Clear the cache */
            cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

            Response::json('', 'success');
        }
    }

    public function duplicate() {
        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.biolinks_blocks')) {
            Response::json(l('global.info_message.team_no_access'), 'error');
        }

        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('links');
        }

        /* Get the link data */
        $biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks');

        if(!$biolink_block) {
            redirect('links');
        }

        /* Custom code: FC-2026-04-01: prevent duplicating blocks that the current package no longer allows */
        $this->validate_block_access_for_current_plan($biolink_block->type);
        /* /Custom code: FC-2026-04-01 */


        /* Check for the plan limit */
        $this->total_biolink_blocks = database()->query("SELECT COUNT(*) AS `total` FROM `biolinks_blocks` WHERE `user_id` = {$this->user->user_id} AND `link_id` = {$biolink_block->link_id}")->fetch_object()->total;
        if($this->user->plan_settings->biolink_blocks_limit != -1 && $this->total_biolink_blocks >= $this->user->plan_settings->biolink_blocks_limit) {
            Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
            $biolink_block->settings = json_decode($biolink_block->settings ?? '');

            /* Custom code: FC-2026-02-27: enforce single ai chatbot block on duplicate */
            if(in_array($biolink_block->type, ['custom_html_chatbot', 'custom_html_chatbot_pets'])) {
                $this->validate_single_ai_chatbot_block_limit((int) $biolink_block->link_id);
            }
            /* /Custom code: FC-2026-02-27 */

            /* Duplication of resources */
            switch($biolink_block->type) {
                case 'file':
                case 'audio':
                case 'video':
                case 'pdf_document':
                case 'powerpoint_presentation':
                case 'excel_spreadsheet':
                    $biolink_block->settings->file = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->file, \Altum\Uploads::get_path('files'), \Altum\Uploads::get_path('files'), 'json_error');
                    break;

                case 'review':
                    $biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, \Altum\Uploads::get_path('block_images'), \Altum\Uploads::get_path('block_images'), 'json_error');
                    break;

                case 'avatar':
                    $biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'avatars/', 'avatars/', 'json_error');
                    break;

                case 'header':
                    $biolink_block->settings->avatar = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->avatar, 'avatars/', 'avatars/', 'json_error');
                    $biolink_block->settings->background = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->background, 'backgrounds/', 'backgrounds/', 'json_error');
                    $biolink_block->settings->video_file = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->video_file, 'files/', 'files/', 'json_error');
                    break;

                case 'vcard':
                    $biolink_block->settings->vcard_avatar = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->vcard_avatar, 'avatars/', 'avatars/', 'json_error');
                    $biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_thumbnail_images/', 'block_thumbnail_images/', 'json_error');
                    break;

                case 'image':
                case 'image_grid':
                    $biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_images/', 'block_images/', 'json_error');
                    break;

                case 'image_comparison':
                    $biolink_block->settings->before_image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->before_image, 'block_images/', 'block_images/', 'json_error');
                    $biolink_block->settings->after_image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->after_image, 'block_images/', 'block_images/', 'json_error');
                    break;

                case 'heading':
                    $biolink_block->settings->verified_location = '';
                    break;

                case 'image_slider':

                    $biolink_block->settings->items = (array) $biolink_block->settings->items;

                    foreach($biolink_block->settings->items as $key => $item) {
                        $biolink_block->settings->items[$key]->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->items[$key]->image, 'block_images/', 'block_images/', 'json_error');
                    }

                    break;

                /* Custom code: FC-2026-03-23: lead funnel block phase 1 */
                case 'lead_funnel':
                    $biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_thumbnail_images/', 'block_thumbnail_images/', 'json_error');
                    $biolink_block->settings->thank_you_file = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->thank_you_file, 'files/', 'files/', 'json_error');
                    break;
                /* /Custom code: FC-2026-03-23 */

                default:
                    $biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_thumbnail_images/', 'block_thumbnail_images/', 'json_error');
                    break;
            }

            $settings = json_encode($biolink_block->settings ?? '');

            /* Database query */
            db()->insert('biolinks_blocks', [
                'user_id' => $this->user->user_id,
                'link_id' => $biolink_block->link_id,
                'type' => $biolink_block->type,
                'location_url' => $biolink_block->location_url,
                'settings' => $settings,
                'order' => $biolink_block->order + 1,
                'start_date' => $biolink_block->start_date,
                'end_date' => $biolink_block->end_date,
                'is_enabled' => $biolink_block->is_enabled,
                'datetime' => get_date(),
            ]);

            /* Clear the cache */
            cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

            /* Set a nice success message */
            Alerts::add_success(l('global.success_message.create2'));

            /* Redirect */
            redirect('link/' . $biolink_block->link_id . '?tab=blocks');
        }

        redirect('links');
    }

    private function order() {
        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.biolinks_blocks')) {
            Response::json(l('global.info_message.team_no_access'), 'error');
        }

        if(isset($_POST['biolink_blocks']) && is_array($_POST['biolink_blocks'])) {
            foreach($_POST['biolink_blocks'] as $link) {
                if(!isset($link['biolink_block_id']) || !isset($link['order'])) {
                    continue;
                }

                $biolink_block = db()->where('biolink_block_id', $link['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks', ['link_id']);

                if(!$biolink_block) {
                    continue;
                }

                $link['biolink_block_id'] = (int) $link['biolink_block_id'];
                $link['order'] = (int) $link['order'];

                /* Update the link order */
                db()->where('biolink_block_id', $link['biolink_block_id'])->where('user_id', $this->user->user_id)->update('biolinks_blocks', ['order' => $link['order']]);
            }

            if(isset($biolink_block)) {
                /* Clear the cache */
                cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);
            }
        }

        Response::json('', 'success');
    }

    private function create() {
        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.biolinks_blocks')) {
            Response::json(l('global.info_message.team_no_access'), 'error');
        }

        $this->biolink_blocks = require APP_PATH . 'includes/biolink_blocks.php';

        /* Check for available biolink blocks */
        if(isset($_POST['block_type']) && array_key_exists($_POST['block_type'], $this->biolink_blocks)) {
            $_POST['block_type'] = query_clean($_POST['block_type']);
            $_POST['link_id'] = (int) $_POST['link_id'];

            /* Custom code: FC-2026-04-01: backend plan gate for block creation */
            $this->validate_block_access_for_current_plan($_POST['block_type']);
            /* /Custom code: FC-2026-04-01 */

            $is_handled = false;

            /* Check for the plan limit */
            $this->total_biolink_blocks = database()->query("SELECT COUNT(*) AS `total` FROM `biolinks_blocks` WHERE `user_id` = {$this->user->user_id} AND `link_id` = {$_POST['link_id']}")->fetch_object()->total;
            if($this->user->plan_settings->biolink_blocks_limit != -1 && $this->total_biolink_blocks >= $this->user->plan_settings->biolink_blocks_limit) {
                Response::json(l('global.info_message.plan_feature_limit'), 'error');
            }

            if(in_array($_POST['block_type'], $this->individual_blocks)) {
                $method = 'create_biolink_' . $_POST['block_type'];
                if(method_exists($this, $method)) {
                    $is_handled = true;
                    $this->{$method}();
                }
            }

            else if(in_array($_POST['block_type'], $this->file_blocks)) {
                $is_handled = true;
                $this->create_biolink_file($_POST['block_type']);
            }

            else if(in_array($_POST['block_type'], $this->embeddable_blocks)) {
                $is_handled = true;
                $this->create_biolink_embeddable($_POST['block_type']);
            }

            if(!$is_handled) {
                Response::json(l('global.error_message.basic'), 'error');
            }

        }

        Response::json('', 'success');
    }

    private function update() {
        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.biolinks_blocks')) {
            Response::json(l('global.info_message.team_no_access'), 'error');
        }

        $this->biolink_blocks = require APP_PATH . 'includes/biolink_blocks.php';

        if(!empty($_POST)) {
            /* Check for available biolink blocks */
            if(isset($_POST['block_type']) && array_key_exists($_POST['block_type'], $this->biolink_blocks)) {
                $_POST['block_type'] = input_clean($_POST['block_type']);
                $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];

                if(!$this->biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
                    die();
                }
                $this->biolink_block->settings = json_decode($this->biolink_block->settings ?? '');

                /* Custom code: FC-2026-04-01: backend plan gate for block updates based on the actual stored block type */
                $this->validate_block_access_for_current_plan($this->biolink_block->type);
                /* /Custom code: FC-2026-04-01 */

                /* Check for the plan limit */
                $this->total_biolink_blocks = database()->query("SELECT COUNT(*) AS `total` FROM `biolinks_blocks` WHERE `user_id` = {$this->user->user_id} AND `link_id` = {$this->biolink_block->link_id}")->fetch_object()->total;
                if($this->user->plan_settings->biolink_blocks_limit != -1 && $this->total_biolink_blocks > $this->user->plan_settings->biolink_blocks_limit) {
                    Response::json(sprintf(settings()->payment->is_enabled ? l('global.info_message.plan_feature_limit_removal_with_upgrade') : l('global.info_message.plan_feature_limit_removal'), '<strong>' . $this->total_biolink_blocks - $this->user->plan_settings->biolink_blocks_limit, mb_strtolower(l('biolinks_blocks.title')) . '</strong>', '<a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '</a>'), 'error');
                }

                if(in_array($_POST['block_type'], $this->individual_blocks)) {
                    $this->{'update_biolink_' . $_POST['block_type']}();
                }

                else if(in_array($_POST['block_type'], $this->file_blocks)) {
                    $this->update_biolink_file($_POST['block_type']);
                }

                else if(in_array($_POST['block_type'], $this->embeddable_blocks)) {
                    $this->update_biolink_embeddable($_POST['block_type']);
                }

            }
        }

        die();
    }

    private function create_biolink_link() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'link';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',
            'sensitive_content' => false,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
        ]);
    }

    private function update_biolink_link() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Check for any errors */
        $required_fields = ['location_url', 'name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'sensitive_content' => $_POST['sensitive_content'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url], 'location_url' => $_POST['location_url']]);
    }

    private function create_biolink_big_link() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = input_clean($_POST['name'], 128);
        $_POST['description'] = input_clean($_POST['description'], 256);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'big_link';
        $settings = json_encode([
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'open_in_new_tab' => false,
            'text_color' => '#000000',
            'description_color' => '#808080',
            'text_alignment' => 'left',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',
            'sensitive_content' => false,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_big_link() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['description'] = input_clean($_POST['description'], 256);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['description_color'] = !verify_hex_color($_POST['description_color']) ? '#000000' : $_POST['description_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Check for any errors */
        $required_fields = ['location_url', 'name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'description_color' => $_POST['description_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'sensitive_content' => $_POST['sensitive_content'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url], 'location_url' => $_POST['location_url']]);
    }

    private function create_biolink_counter() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['number'] = is_numeric($_POST['number'] ?? null) ? max(1, min(10000000000, (int) $_POST['number'])) : 1;
        $_POST['description'] = input_clean($_POST['description'], 256);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'counter';
        $settings = json_encode([
            'number' => $_POST['number'],
            'starting_number' => 0,
            'description' => $_POST['description'],
            'number_prefix' => '',
            'number_suffix' => '',
            'number_animation_duration' => 3,

            'open_in_new_tab' => false,
            'number_color' => '#000000',
            'description_color' => '#808080',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'sensitive_content' => false,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_counter() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['number'] = is_numeric($_POST['number'] ?? null) ? max(1, min(10000000000, (int) $_POST['number'])) : 1;
        $_POST['starting_number'] = is_numeric($_POST['starting_number'] ?? null) ? max(0, min(10000000000, (int) $_POST['starting_number'])) : 1;
        $_POST['description'] = input_clean($_POST['description'], 256);
        $_POST['animation_duration'] = is_numeric($_POST['animation_duration'] ?? null) ? max(0, min(10, (int) $_POST['animation_duration'])) : 1;
        $_POST['number_prefix'] = input_clean($_POST['number_prefix'], 32);
        $_POST['number_suffix'] = input_clean($_POST['number_suffix'], 32);
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);

        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['number_color'] = !verify_hex_color($_POST['number_color']) ? '#000000' : $_POST['number_color'];
        $_POST['description_color'] = !verify_hex_color($_POST['description_color']) ? '#000000' : $_POST['description_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Check for any errors */
        $required_fields = ['number'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url'], true);

        $settings = json_encode([
            'number' => $_POST['number'],
            'starting_number' => $_POST['starting_number'],
            'description' => $_POST['description'],
            'number_prefix' => $_POST['number_prefix'],
            'number_suffix' => $_POST['number_suffix'],
            'number_animation_duration' => $_POST['number_animation_duration'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'number_color' => $_POST['number_color'],
            'description_color' => $_POST['description_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'sensitive_content' => $_POST['sensitive_content'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['location_url' => $_POST['location_url']]);
    }

    private function create_biolink_loading() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['number'] = is_numeric($_POST['number'] ?? null) ? max(1, min(100, (int) $_POST['number'])) : 1;
        $_POST['description'] = input_clean($_POST['description'], 256);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'loading';
        $settings = json_encode([
            'number' => $_POST['number'],
            'description' => $_POST['description'],
            'number_prefix' => '',
            'number_suffix' => '%',
            'open_in_new_tab' => false,
            'bar_is_striped' => true,
            'bar_is_animated' => true,
            'number_color' => '#ffffff',
            'description_color' => '#808080',
            'bar_color' => '#000000',
            'bar_height' => '10px',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'sensitive_content' => false,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_loading() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['number'] = is_numeric($_POST['number'] ?? null) ? max(1, min(100, (int) $_POST['number'])) : 1;
        $_POST['bar_height'] = is_numeric($_POST['bar_height'] ?? null) ? max(10, min(50, (int) $_POST['bar_height'])) : 1;
        $_POST['description'] = input_clean($_POST['description'], 256);
        $_POST['number_prefix'] = input_clean($_POST['number_prefix'], 32);
        $_POST['number_suffix'] = input_clean($_POST['number_suffix'], 32);
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['bar_is_striped'] = (int) isset($_POST['bar_is_striped']);
        $_POST['bar_is_animated'] = (int) isset($_POST['bar_is_animated']);

        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['number_color'] = !verify_hex_color($_POST['number_color']) ? '#ffffff' : $_POST['number_color'];
        $_POST['description_color'] = !verify_hex_color($_POST['description_color']) ? '#000000' : $_POST['description_color'];
        $_POST['bar_color'] = !verify_hex_color($_POST['bar_color']) ? '#000000' : $_POST['bar_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Check for any errors */
        $required_fields = ['number'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url'], true);

        $settings = json_encode([
            'number' => $_POST['number'],
            'bar_height' => $_POST['bar_height'],
            'description' => $_POST['description'],
            'number_prefix' => $_POST['number_prefix'],
            'number_suffix' => $_POST['number_suffix'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'bar_is_striped' => $_POST['bar_is_striped'],
            'bar_is_animated' => $_POST['bar_is_animated'],
            'number_color' => $_POST['number_color'],
            'description_color' => $_POST['description_color'],
            'bar_color' => $_POST['bar_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'sensitive_content' => $_POST['sensitive_content'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['location_url' => $_POST['location_url']]);
    }

    private function create_biolink_heading() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['text'] = mb_substr(query_clean($_POST['text']), 0, 256);
        $_POST['heading_type'] = in_array($_POST['heading_type'], ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']) ? query_clean($_POST['heading_type']) : 'h1';

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'heading';
        $settings = json_encode([
            'heading_type' => $_POST['heading_type'],
            'text' => $_POST['text'],
            'text_color' => '#ffffff',
            'text_alignment' => 'center',
            'verified_location' => '',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_heading() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['heading_type'] = in_array($_POST['heading_type'], ['h1', 'h2', 'h3', 'h4', 'h5', 'h6']) ? query_clean($_POST['heading_type']) : 'h1';
        $_POST['text'] = mb_substr(query_clean($_POST['text']), 0, 256);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#ffffff' : $_POST['text_color'];
        $_POST['verified_location'] = isset($_POST['verified_location']) && in_array($_POST['verified_location'], ['', 'left', 'right']) ? query_clean($_POST['verified_location']) : '';

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'heading_type' => $_POST['heading_type'],
            'text' => $_POST['text'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'verified_location' => $_POST['verified_location'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_paragraph() {
        $_POST['link_id'] = (int) $_POST['link_id'];

        $_POST['text'] = quilljs_to_bootstrap($_POST['text']);

        /* Purify the text */
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 's,p,b,strong,i,em,u,strike,blockquote,code,pre,ul,ol,li,a[href],span[style|class],div[style|class],br');
        $config->set('CSS.AllowedProperties', [
            'color',
            'background-color',
            'text-align',
            'font-size',
        ]);

        /* Allow class and style on selected tags */
        $config->set('HTML.AllowedAttributes', 'span.class,span.style,div.class,div.style,p.class,p.style,a.href,a.style');
        $config->set('Attr.AllowedClasses', null);
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('AutoFormat.Linkify', true);

        $purifier = new \HTMLPurifier($config);
        $_POST['text'] = @$purifier->purify($_POST['text']);

        /* Limit max length */
        $_POST['text'] = mb_substr($_POST['text'], 0, 10000);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'paragraph';
        $settings = json_encode([
            'text' => $_POST['text'],
            'text_color' => '#ffffff',
            'background_color' => '#00000000',
            'border_radius' => 'rounded',
            'border_shadow_style' => 'none',
            'border_shadow_color' => '#00000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'text_alignment' => 'center',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_paragraph() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];

        $_POST['text'] = quilljs_to_bootstrap($_POST['text']);

        /* Purify the text */
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 's,p,b,strong,i,em,u,strike,blockquote,code,pre,ul,ol,li,a[href],span[style|class],div[style|class],br');
        $config->set('CSS.AllowedProperties', [
            'color',
            'background-color',
            'text-align',
            'font-size',
        ]);

        /* Allow class and style on selected tags */
        $config->set('HTML.AllowedAttributes', 'span.class,span.style,div.class,div.style,p.class,p.style,a.href,a.style');
        $config->set('Attr.AllowedClasses', null);
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('AutoFormat.Linkify', true);

        $purifier = new \HTMLPurifier($config);
        $_POST['text'] = @$purifier->purify($_POST['text']);

        /* Limit max length */
        $_POST['text'] = mb_substr($_POST['text'], 0, 10000);

        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#ffffff' : $_POST['text_color'];
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#00000000' : $_POST['border_shadow_color'];
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'text' => $_POST['text'],
            'text_color' => $_POST['text_color'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_code() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['code'] = mb_substr($_POST['code'], 0, 10000);
        $_POST['code_language'] = isset($_POST['code_language']) && in_array($_POST['code_language'], ['text','plaintext','html','css','scss','sass','less','javascript','typescript','json','jsonc','xml','yaml','markdown','mdx','php','python','ruby','go','java','kotlin','scala','groovy','rust','swift','c','cpp','objective-c','csharp','bash','shellscript','zsh','fish','powershell','perl','lua','sql','graphql','dockerfile','nginx','ini','toml','dotenv','diff','makefile','cmake','regex','git-commit','git-rebase','git-ignore']) ? $_POST['code_language'] : 'text';

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'code';
        $settings = json_encode([
            'code' => $_POST['code'],
            'code_language' => $_POST['code_language'],
            'theme' => 'github-dark',
            'border_radius' => 'rounded',
            'border_shadow_style' => 'none',
            'border_shadow_color' => '#00000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'text_alignment' => 'center',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_code() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['code'] = mb_substr($_POST['code'], 0, 10000);
        $_POST['code_language'] = isset($_POST['code_language']) && in_array($_POST['code_language'], ['text','plaintext','html','css','scss','sass','less','javascript','typescript','json','jsonc','xml','yaml','markdown','mdx','php','python','ruby','go','java','kotlin','scala','groovy','rust','swift','c','cpp','objective-c','csharp','bash','shellscript','zsh','fish','powershell','perl','lua','sql','graphql','dockerfile','nginx','ini','toml','dotenv','diff','makefile','cmake','regex','git-commit','git-rebase','git-ignore']) ? $_POST['code_language'] : 'text';
        $_POST['theme'] = isset($_POST['theme']) && in_array($_POST['theme'], ['github-light','github-dark','github-dark-dimmed','dark-plus','light-plus','dracula','dracula-soft','nord','one-dark-pro','material-theme','material-theme-darker','material-theme-lighter','monokai','slack-dark','slack-ochin','solarized-light','solarized-dark','tokyo-night','tokyo-night-light','rose-pine','rose-pine-dawn','rose-pine-moon']) ? $_POST['theme'] : 'github-dark';

        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#00000000' : $_POST['border_shadow_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'code' => $_POST['code'],
            'code_language' => $_POST['code_language'],
            'theme' => $_POST['theme'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_modal_text() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = input_clean($_POST['name'], 128);

        $_POST['text'] = quilljs_to_bootstrap($_POST['text']);

        /* Purify the text */
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 's,p,b,strong,i,em,u,strike,blockquote,code,pre,ul,ol,li,a[href],span[style|class],div[style|class],br');
        $config->set('CSS.AllowedProperties', [
            'color',
            'background-color',
            'text-align',
            'font-size',
        ]);

        /* Allow class and style on selected tags */
        $config->set('HTML.AllowedAttributes', 'span.class,span.style,div.class,div.style,p.class,p.style,a.href,a.style');
        $config->set('Attr.AllowedClasses', null);
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('AutoFormat.Linkify', true);

        $purifier = new \HTMLPurifier($config);
        $_POST['text'] = @$purifier->purify($_POST['text']);

        /* Limit max length */
        $_POST['text'] = mb_substr($_POST['text'], 0, 10000);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'modal_text';
        $settings = json_encode([
            'name' => $_POST['name'],
            'text' => $_POST['text'],
            'button_text' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',


            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_modal_text() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = input_clean($_POST['name'], 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        $_POST['text'] = quilljs_to_bootstrap($_POST['text']);

        /* Purify the text */
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 's,p,b,strong,i,em,u,strike,blockquote,code,pre,ul,ol,li,a[href],span[style|class],div[style|class],br');
        $config->set('CSS.AllowedProperties', [
            'color',
            'background-color',
            'text-align',
            'font-size',
        ]);

        /* Allow class and style on selected tags */
        $config->set('HTML.AllowedAttributes', 'span.class,span.style,div.class,div.style,p.class,p.style,a.href,a.style');
        $config->set('Attr.AllowedClasses', null);
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('AutoFormat.Linkify', true);

        $purifier = new \HTMLPurifier($config);
        $_POST['text'] = @$purifier->purify($_POST['text']);

        /* Limit max length */
        $_POST['text'] = mb_substr($_POST['text'], 0, 10000);

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'text' => $_POST['text'],
            'button_text' => $_POST['button_text'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url], 'location_url' => $_POST['location_url']]);
    }

    private function create_biolink_business_hours() {
        $_POST['link_id'] = (int) $_POST['link_id'];

        $_POST['twenty_four_seven'] = (int) isset($_POST['twenty_four_seven']);
        $_POST['temporarily_closed'] = (int) isset($_POST['temporarily_closed']);
        $_POST['note'] = input_clean($_POST['note'],1000);
        foreach(range(1, 7) as $day) {
            $_POST['day_' . $day . '_translation'] = input_clean($_POST['day_' . $day  . '_translation'], 32);
            $_POST['day_' . $day] = input_clean($_POST['day_' . $day], 256);
        }
        $_POST['twenty_four_seven_title'] = input_clean($_POST['twenty_four_seven_title'], 256);
        $_POST['twenty_four_seven_description'] = input_clean($_POST['twenty_four_seven_description'], 256);
        $_POST['temporarily_closed_title'] = input_clean($_POST['temporarily_closed_title'], 256);
        $_POST['temporarily_closed_description'] = input_clean($_POST['temporarily_closed_description'], 256);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'business_hours';
        $settings = json_encode([
            'twenty_four_seven' => $_POST['twenty_four_seven'],
            'temporarily_closed' => $_POST['temporarily_closed'],
            'note' => $_POST['note'],
            'day_1' => $_POST['day_1'],
            'day_2' => $_POST['day_2'],
            'day_3' => $_POST['day_3'],
            'day_4' => $_POST['day_4'],
            'day_5' => $_POST['day_5'],
            'day_6' => $_POST['day_6'],
            'day_7' => $_POST['day_7'],
            'day_1_translation' => $_POST['day_1_translation'],
            'day_2_translation' => $_POST['day_2_translation'],
            'day_3_translation' => $_POST['day_3_translation'],
            'day_4_translation' => $_POST['day_4_translation'],
            'day_5_translation' => $_POST['day_5_translation'],
            'day_6_translation' => $_POST['day_6_translation'],
            'day_7_translation' => $_POST['day_7_translation'],

            'twenty_four_seven_title' => $_POST['twenty_four_seven_title'],
            'twenty_four_seven_description' => $_POST['twenty_four_seven_description'],
            'temporarily_closed_title' => $_POST['temporarily_closed_title'],
            'temporarily_closed_description' => $_POST['temporarily_closed_description'],

            'title_color' => '#ffffff',
            'description_color' => '#717d8f',
            'icon_color' => '#00000000',
            'background_color' => '#00000000',
            'border_radius' => 'rounded',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'text_alignment' => 'center',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_business_hours() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];

        $_POST['twenty_four_seven'] = (int) isset($_POST['twenty_four_seven']);
        $_POST['temporarily_closed'] = (int) isset($_POST['temporarily_closed']);
        $_POST['note'] = input_clean($_POST['note'],1000);
        foreach(range(1, 7) as $day) {
            $_POST['day_' . $day . '_translation'] = input_clean($_POST['day_' . $day  . '_translation'], 32);
            $_POST['day_' . $day] = input_clean($_POST['day_' . $day], 256);
        }
        $_POST['twenty_four_seven_title'] = input_clean($_POST['twenty_four_seven_title'], 256);
        $_POST['twenty_four_seven_description'] = input_clean($_POST['twenty_four_seven_description'], 256);
        $_POST['temporarily_closed_title'] = input_clean($_POST['temporarily_closed_title'], 256);
        $_POST['temporarily_closed_description'] = input_clean($_POST['temporarily_closed_description'], 256);

        $_POST['title_color'] = !verify_hex_color($_POST['title_color']) ? '#ffffff' : $_POST['title_color'];
        $_POST['description_color'] = !verify_hex_color($_POST['description_color']) ? '#ffffff' : $_POST['description_color'];
        $_POST['icon_color'] = !verify_hex_color($_POST['icon_color']) ? '#ffffff' : $_POST['icon_color'];

        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#00000000' : $_POST['border_shadow_color'];
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'twenty_four_seven' => $_POST['twenty_four_seven'],
            'temporarily_closed' => $_POST['temporarily_closed'],
            'note' => $_POST['note'],
            'day_1' => $_POST['day_1'],
            'day_2' => $_POST['day_2'],
            'day_3' => $_POST['day_3'],
            'day_4' => $_POST['day_4'],
            'day_5' => $_POST['day_5'],
            'day_6' => $_POST['day_6'],
            'day_7' => $_POST['day_7'],
            'day_1_translation' => $_POST['day_1_translation'],
            'day_2_translation' => $_POST['day_2_translation'],
            'day_3_translation' => $_POST['day_3_translation'],
            'day_4_translation' => $_POST['day_4_translation'],
            'day_5_translation' => $_POST['day_5_translation'],
            'day_6_translation' => $_POST['day_6_translation'],
            'day_7_translation' => $_POST['day_7_translation'],

            'twenty_four_seven_title' => $_POST['twenty_four_seven_title'],
            'twenty_four_seven_description' => $_POST['twenty_four_seven_description'],
            'temporarily_closed_title' => $_POST['temporarily_closed_title'],
            'temporarily_closed_description' => $_POST['temporarily_closed_description'],

            'title_color' => $_POST['title_color'],
            'description_color' => $_POST['description_color'],
            'icon_color' => $_POST['icon_color'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_weather() {
        $_POST['link_id'] = (int) $_POST['link_id'];

        $_POST['source'] = in_array($_POST['source'], ['latitude_longitude', 'address', 'user_location']) ? $_POST['source'] : 'user_location';
        $_POST['latitude']  = isset($_POST['latitude'])  && is_numeric($_POST['latitude'])  && $_POST['latitude']  >= -90  && $_POST['latitude']  <= 90  ? round((float) $_POST['latitude'], 6)  : null;
        $_POST['longitude'] = isset($_POST['longitude']) && is_numeric($_POST['longitude']) && $_POST['longitude'] >= -180 && $_POST['longitude'] <= 180 ? round((float) $_POST['longitude'], 6) : null;
        $_POST['address'] = input_clean($_POST['address'], 256);

        if($_POST['source'] == 'address') {
            if(settings()->links->google_geocoding_is_enabled) {
                $geocoding_results = file_get_contents('https://maps.google.com/maps/api/geocode/json?address=' . urlencode($_POST['address']) . '&sensor=false&key=' . settings()->links->google_geocoding_api_key);
                $geocoding_results = json_decode($geocoding_results);

                if($geocoding_results && $geocoding_results->status == 'OK') {
                    $_POST['latitude'] = $geocoding_results->results[0]->geometry->location->lat;
                    $_POST['longitude'] = $geocoding_results->results[0]->geometry->location->lng;
                }

                else {
                    Response::json(l('global.error_message.basic'), 'error');
                }
            }

            else {
                $_POST['source'] = 'user_location';
            }
        }

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'weather';
        $settings = json_encode([
            'source' => $_POST['source'],
            'latitude' => $_POST['latitude'],
            'longitude' => $_POST['longitude'],
            'address' => $_POST['address'],
            'unit' => 'celsius',
            'display_address' => '',
            'display_forecast' => true,

            'text_color' => '#ffffff',
            'description_color' => '#717d8f',
            'icon_color' => '#00000000',
            'background_color' => '#00000000',
            'border_radius' => 'rounded',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'text_alignment' => 'center',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_weather() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $biolink_block->settings = json_decode($biolink_block->settings ?? '');

        $_POST['source'] = in_array($_POST['source'], ['latitude_longitude', 'address', 'user_location']) ? $_POST['source'] : 'user_location';
        $_POST['latitude'] = isset($_POST['latitude'])  && is_numeric($_POST['latitude'])  && $_POST['latitude']  >= -90  && $_POST['latitude']  <= 90  ? round((float) $_POST['latitude'], 6)  : null;
        $_POST['longitude'] = isset($_POST['longitude']) && is_numeric($_POST['longitude']) && $_POST['longitude'] >= -180 && $_POST['longitude'] <= 180 ? round((float) $_POST['longitude'], 6) : null;
        $_POST['address'] = input_clean($_POST['address'] ?? '', 256);
        $_POST['display_address'] = input_clean($_POST['display_address'], 256);
        $_POST['unit'] = in_array($_POST['unit'], ['celsius', 'fahrenheit']) ? $_POST['unit'] : 'celsius';
        $_POST['display_forecast'] = (int) isset($_POST['display_forecast']);

        if($_POST['source'] == 'address') {
            if(settings()->links->google_geocoding_is_enabled) {
                $geocoding_results = file_get_contents('https://maps.google.com/maps/api/geocode/json?address=' . urlencode($_POST['address']) . '&sensor=false&key=' . settings()->links->google_geocoding_api_key);
                $geocoding_results = json_decode($geocoding_results);

                if($geocoding_results && $geocoding_results->status == 'OK') {
                    $_POST['latitude'] = $geocoding_results->results[0]->geometry->location->lat;
                    $_POST['longitude'] = $geocoding_results->results[0]->geometry->location->lng;
                }

                else {
                    Response::json(l('global.error_message.basic'), 'error');
                }
            }

            else {
                $_POST['source'] = 'user_location';
            }
        }

        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#ffffff' : $_POST['text_color'];
        $_POST['description_color'] = !verify_hex_color($_POST['description_color']) ? '#ffffff' : $_POST['description_color'];

        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#00000000' : $_POST['border_shadow_color'];
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        /* Display settings */
        $this->process_display_settings();

        $settings = json_encode([
            'source' => $_POST['source'],
            'latitude' => $_POST['latitude'],
            'longitude' => $_POST['longitude'],
            'address' => $_POST['address'],
            'unit' => $_POST['unit'],
            'display_address' => $_POST['display_address'],
            'display_forecast' => $_POST['display_forecast'],

            'text_color' => $_POST['text_color'],
            'description_color' => $_POST['description_color'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_markdown() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['text'] = mb_substr(input_clean($_POST['text']), 0, 10000);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#ffffff' : $_POST['text_color'];
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'markdown';
        $settings = json_encode([
            'text' => $_POST['text'],
            'text_color' => '#ffffff',
            'background_color' => '#000000',
            'border_radius' => 'rounded',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_markdown() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['text'] = mb_substr(input_clean($_POST['text']), 0, 10000);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#ffffff' : $_POST['text_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'text' => $_POST['text'],
            'text_alignment' => $_POST['text_alignment'],
            'text_color' => $_POST['text_color'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_avatar() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['size'] = in_array($_POST['size'], ['75', '100', '125', '150']) ? (int) $_POST['size'] : 125;
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        /* Image upload */
        $db_image = $this->handle_image_upload(null, 'avatars/', settings()->links->avatar_size_limit);

        $type = 'avatar';
        $settings = json_encode([
            'image' => $db_image,
            'use_default_avatar' => empty($db_image),
            'image_alt' => null,
            'size' => $_POST['size'],
            'border_radius' => $_POST['border_radius'],
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'object_fit' => 'contain',
            'open_in_new_tab' => false,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_avatar() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['size'] = in_array($_POST['size'], ['75', '100', '125', '150']) ? (int) $_POST['size'] : 125;
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['object_fit'] = in_array($_POST['object_fit'], ['contain', 'cover', 'fill']) ? query_clean($_POST['object_fit']) : 'contain';
        $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image ?? null, 'avatars/', settings()->links->avatar_size_limit);

        if(!is_upload_file_available('avatars', $db_image)) {
            $db_image = null;
        }

        $image_url = get_biolink_avatar_url((object) ['image' => $db_image]);

        $settings = json_encode([
            'image' => $db_image,
            'use_default_avatar' => empty($db_image),
            'image_alt' => $_POST['image_alt'],
            'size' => $_POST['size'],
            'object_fit' => $_POST['object_fit'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_header() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['background_type'] = in_array($_POST['background_type'], ['image', 'video', 'video_file']) ? $_POST['background_type'] : 'image';
        $_POST['video_url'] = get_url($_POST['video_url'] ?? '');

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        /* Image upload */
        $background = $this->handle_file_upload(null, 'background', 'background_remove', ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'], 'backgrounds/', settings()->links->avatar_size_limit);
        $avatar = $this->handle_file_upload(null, 'avatar', 'avatar_remove', ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'], 'avatars/', settings()->links->avatar_size_limit);

        /* Video file */
        $video_file = $this->handle_file_upload(null, 'video_file', 'video_file_remove', $this->biolink_blocks['header']['whitelisted_video_extensions'], 'files/', settings()->links->video_size_limit);

        $type = 'header';
        $settings = json_encode([
            'video_file' => $video_file,
            'background_type' => $_POST['background_type'],
            'video_url' => $_POST['video_url'],
            'avatar' => $avatar,
            'background' => $background,
            'avatar_alt' => null,
            'background_alt' => null,
            'avatar_size' => 100,
            'border_radius' => 'round',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'object_fit' => 'contain',
            'open_in_new_tab' => false,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_header() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['avatar_size'] = in_array($_POST['avatar_size'], ['75', '100', '125']) ? (int) $_POST['avatar_size'] : 100;
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['object_fit'] = in_array($_POST['object_fit'], ['contain', 'cover', 'fill']) ? query_clean($_POST['object_fit']) : 'contain';
        $_POST['background_alt'] = input_clean($_POST['background_alt'] ?? '', 100);
        $_POST['avatar_alt'] = input_clean($_POST['avatar_alt'] ?? '', 100);
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['background_type'] = in_array($_POST['background_type'], ['image', 'video', 'video_file']) ? $_POST['background_type'] : 'image';
        $_POST['video_url'] = get_url($_POST['video_url'] ?? '');

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $background = $this->handle_file_upload($biolink_block->settings->background, 'background', 'background_remove', ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'], 'backgrounds/', settings()->links->avatar_size_limit);
        $avatar = $this->handle_file_upload($biolink_block->settings->avatar, 'avatar', 'avatar_remove', ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'], 'avatars/', settings()->links->avatar_size_limit);

        $avatar_url = $avatar ? \Altum\Uploads::get_full_url('avatars') . $avatar : null;
        $background_url = $background ? \Altum\Uploads::get_full_url('backgrounds') . $background : null;

        /* Video file */
        $video_file = $this->handle_file_upload($biolink_block->settings->video_file, 'video_file', 'video_file_remove', $this->biolink_blocks['header']['whitelisted_video_extensions'], 'files/', settings()->links->video_size_limit);

        $settings = json_encode([
            'video_file' => $video_file,
            'background_type' => $_POST['background_type'],
            'video_url' => $_POST['video_url'],
            'avatar' => $avatar,
            'background' => $background,
            'avatar_alt' => $_POST['avatar_alt'],
            'background_alt' => $_POST['background_alt'],
            'avatar_size' => $_POST['avatar_size'],
            'object_fit' => $_POST['object_fit'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['avatar' => $avatar_url, 'background' => $background_url]]);
    }

    private function create_biolink_socials() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['color'] = !verify_hex_color($_POST['color']) ? '#ffffff' : $_POST['color'];
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#FFFFFF00' : $_POST['background_color'];
        $_POST['size'] = in_array($_POST['size'], ['s', 'm', 'l', 'xl']) ? $_POST['size'] : 'm';

        /* Make sure the socials sent are proper */
        $biolink_socials = require APP_PATH . 'includes/biolink_socials.php';

        foreach($_POST['socials'] as $key => $value) {
            if(!array_key_exists($key, $biolink_socials)) {
                unset($_POST['socials'][$key]);
            } else {
                $_POST['socials'][$key] = mb_substr(query_clean($_POST['socials'][$key]), 0, $biolink_socials[$key]['max_length']);
            }
        }

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'socials';
        $settings = json_encode([
            'color' => $_POST['color'],
            'background_color' => $_POST['background_color'],
            'socials' => $_POST['socials'],
            'size' => $_POST['size'],
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_socials() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['color'] = !verify_hex_color($_POST['color']) ? '#ffffff' : $_POST['color'];
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#FFFFFF00' : $_POST['background_color'];
        $_POST['size'] = in_array($_POST['size'], ['s', 'm', 'l', 'xl']) ? $_POST['size'] : 'm';
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Make sure the socials sent are proper */
        $biolink_socials = require APP_PATH . 'includes/biolink_socials.php';

        foreach($_POST['socials'] as $key => $value) {
            if(!array_key_exists($key, $biolink_socials)) {
                unset($_POST['socials'][$key]);
            } else {
                $_POST['socials'][$key] = mb_substr(query_clean($_POST['socials'][$key]), 0, $biolink_socials[$key]['max_length']);
            }
        }

        $settings = json_encode([
            'color' => $_POST['color'],
            'background_color' => $_POST['background_color'],
            'socials' => $_POST['socials'],
            'size' => $_POST['size'],
            'border_radius' => $_POST['border_radius'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_email_collector() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'email_collector';
        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',

            'email_placeholder' => l('biolink_email_collector.email_placeholder_default'),
            'name_placeholder' => l('biolink_email_collector.name_placeholder_default'),
            'button_text' => l('biolink_email_collector.button_text_default'),
            'success_text' => l('biolink_email_collector.success_text_default'),
            'thank_you_url' => '',
            'show_agreement' => false,
            'agreement_url' => '',
            'agreement_text' => '',
            'notifications' => [],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_email_collector() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['email_placeholder'] = mb_substr(query_clean($_POST['email_placeholder']), 0, 64);
        $_POST['name_placeholder'] = mb_substr(query_clean($_POST['name_placeholder']), 0, 64);
        $_POST['button_text'] = input_clean($_POST['button_text'], 64);
        $_POST['success_text'] = mb_substr(query_clean($_POST['success_text']), 0, 256);
        $_POST['show_agreement'] = (int) isset($_POST['show_agreement']);
        $_POST['agreement_url'] = get_url($_POST['agreement_url']);
        $_POST['agreement_text'] = mb_substr(query_clean($_POST['agreement_text']), 0, 256);
        $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Get available notification handlers */
        $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

        /* Notification handlers */
        $_POST['notifications'] = array_map(
            'intval',
            array_filter($_POST['notifications'] ?? [], function($notification_handler_id) use($notification_handlers) {
                return array_key_exists($notification_handler_id, $notification_handlers);
            })
        );

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'email_placeholder' => $_POST['email_placeholder'],
            'name_placeholder' => $_POST['name_placeholder'],
            'button_text' => $_POST['button_text'],
            'success_text' => $_POST['success_text'],
            'thank_you_url' => $_POST['thank_you_url'],
            'show_agreement' => $_POST['show_agreement'],
            'agreement_url' => $_POST['agreement_url'],
            'agreement_text' => $_POST['agreement_text'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],

            /* Notifications */
            'notifications' => $_POST['notifications'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_rss_feed() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'rss_feed';
        $settings = json_encode([
            'amount' => 5,
            'open_in_new_tab' => false,
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_rss_feed() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['amount'] = (int) $_POST['amount'];
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $settings = json_encode([
            'amount' => $_POST['amount'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);
        cache()->deleteItem('biolink_block?block_id=' . $biolink_block->biolink_block_id . '&type=rss_feed');

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_iframe() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'iframe';
        $settings = json_encode([
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_iframe() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $settings = json_encode([
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);
        cache()->deleteItem('biolink_block?block_id=' . $biolink_block->biolink_block_id . '&type=iframe');

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_custom_html() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['html'] = mb_substr(trim($_POST['html']), 0, $this->biolink_blocks['custom_html']['max_length']);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'custom_html';
        $settings = json_encode([
			'name' => '',
            'html' => $_POST['html'],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_custom_html() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['html'] = mb_substr(trim($_POST['html']), 0, $this->biolink_blocks['custom_html']['max_length']);
		$_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
			'html' => $_POST['html'],
			'name' => $_POST['name'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_vcard() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'vcard';
        $settings = [
            'name' => $_POST['name'],
            'image' => '',
            'first_name' => '',
            'last_name' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'vcard_socials' => [],
            'vcard_phone_numbers' => [],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ];
        $settings = json_encode($settings);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_vcard() {
        $settings = [];

        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        $settings['vcard_first_name'] = $_POST['vcard_first_name'] = mb_substr(query_clean($_POST['vcard_first_name']), 0, $this->biolink_blocks['vcard']['fields']['first_name']['max_length']);
        $settings['vcard_last_name'] = $_POST['vcard_last_name'] = mb_substr(query_clean($_POST['vcard_last_name']), 0, $this->biolink_blocks['vcard']['fields']['last_name']['max_length']);
        $settings['vcard_email'] = $_POST['vcard_email'] = mb_substr(query_clean($_POST['vcard_email']), 0, $this->biolink_blocks['vcard']['fields']['email']['max_length']);
        $settings['vcard_url'] = $_POST['vcard_url'] = mb_substr(query_clean($_POST['vcard_url']), 0, $this->biolink_blocks['vcard']['fields']['url']['max_length']);
        $settings['vcard_company'] = $_POST['vcard_company'] = mb_substr(query_clean($_POST['vcard_company']), 0, $this->biolink_blocks['vcard']['fields']['company']['max_length']);
        $settings['vcard_job_title'] = $_POST['vcard_job_title'] = mb_substr(query_clean($_POST['vcard_job_title']), 0, $this->biolink_blocks['vcard']['fields']['job_title']['max_length']);
        $settings['vcard_birthday'] = $_POST['vcard_birthday'] = mb_substr(query_clean($_POST['vcard_birthday']), 0, $this->biolink_blocks['vcard']['fields']['birthday']['max_length']);
        $settings['vcard_street'] = $_POST['vcard_street'] = mb_substr(query_clean($_POST['vcard_street']), 0, $this->biolink_blocks['vcard']['fields']['street']['max_length']);
        $settings['vcard_city'] = $_POST['vcard_city'] = mb_substr(query_clean($_POST['vcard_city']), 0, $this->biolink_blocks['vcard']['fields']['city']['max_length']);
        $settings['vcard_zip'] = $_POST['vcard_zip'] = mb_substr(query_clean($_POST['vcard_zip']), 0, $this->biolink_blocks['vcard']['fields']['zip']['max_length']);
        $settings['vcard_region'] = $_POST['vcard_region'] = mb_substr(query_clean($_POST['vcard_region']), 0, $this->biolink_blocks['vcard']['fields']['region']['max_length']);
        $settings['vcard_country'] = $_POST['vcard_country'] = mb_substr(query_clean($_POST['vcard_country']), 0, $this->biolink_blocks['vcard']['fields']['country']['max_length']);
        $settings['vcard_note'] = $_POST['vcard_note'] = mb_substr(query_clean($_POST['vcard_note']), 0, $this->biolink_blocks['vcard']['fields']['note']['max_length']);

        /* Phone numbers */
        if(!isset($_POST['vcard_phone_number_label'])) {
            $_POST['vcard_phone_number_label'] = [];
            $_POST['vcard_phone_number_value'] = [];
        }
        $vcard_phone_numbers = [];
        foreach($_POST['vcard_phone_number_label'] as $key => $value) {
            if($key >= 20) continue;

            $vcard_phone_numbers[] = [
                'label' => mb_substr(input_clean($value), 0, $this->biolink_blocks['vcard']['fields']['phone_number_value']['max_length']),
                'value' => mb_substr(input_clean($_POST['vcard_phone_number_value'][$key]), 0, $this->biolink_blocks['vcard']['fields']['phone_number_value']['max_length'])
            ];
        }
        $settings['vcard_phone_numbers'] = $vcard_phone_numbers;

        /* Socials */
        if(!isset($_POST['vcard_social_label'])) {
            $_POST['vcard_social_label'] = [];
            $_POST['vcard_social_value'] = [];
        }
        $vcard_socials = [];
        foreach($_POST['vcard_social_label'] as $key => $value) {
            if(empty(trim($value))) continue;
            if($key >= 20) continue;

            $vcard_socials[] = [
                'label' => mb_substr(query_clean($value), 0, $this->biolink_blocks['vcard']['fields']['social_value']['max_length']),
                'value' => mb_substr(input_clean($_POST['vcard_social_value'][$key]), 0, $this->biolink_blocks['vcard']['fields']['social_value']['max_length'])
            ];
        }
        $settings['vcard_socials'] = $vcard_socials;

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        /* Vcard avatar */
        $settings['vcard_avatar'] = $this->handle_file_upload($biolink_block->settings->vcard_avatar, 'vcard_avatar', 'vcard_avatar_remove', \Altum\Uploads::get_whitelisted_file_extensions('vcards_avatars'), 'avatars/', 0.75);

        /* Check for the removal of the already uploaded file */
        if(isset($_POST['vcard_avatar_remove'])) {
            /* Offload deleting */
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                $s3 = new \Aws\S3\S3Client(get_aws_s3_config());
                $s3->deleteObject([
                    'Bucket' => settings()->offload->storage_name,
                    'Key' => 'uploads/avatars/' . $biolink_block->settings->vcard_avatar,
                ]);
            }

            /* Local deleting */
            else {
                /* Delete current file */
                if(!empty($biolink_block->settings->vcard_avatar) && file_exists(UPLOADS_PATH . 'avatars/' . $biolink_block->settings->vcard_avatar)) {
                    unlink(UPLOADS_PATH . 'avatars/' . $biolink_block->settings->vcard_avatar);
                }
            }
            $settings['vcard_avatar'] = null;
        }

        $vcard_avatar_url = $settings['vcard_avatar'] ? \Altum\Uploads::get_full_url('avatars') . $settings['vcard_avatar'] : null;

        $settings = array_merge($settings, [
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);
        $settings = json_encode($settings);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success',
            [
                'images' => [
                    'image' => $image_url,
                    'vcard_avatar' => $vcard_avatar_url
                ]
            ]
        );
    }

    private function create_biolink_image() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $db_image = $this->handle_image_upload(null, 'block_images/', settings()->links->image_size_limit);

        $type = 'image';
        $settings = json_encode([
            'image' => $db_image,
            'image_alt' => null,
            'open_in_new_tab' => false,

            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_image() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_images/', settings()->links->image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_images') . $db_image : null;

        $settings = json_encode([
            'image' => $db_image,
            'image_alt' => $_POST['image_alt'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],

            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_featured_link() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $db_image = $this->handle_image_upload(null, 'block_images/', settings()->links->image_size_limit);

        $type = 'featured_link';
        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'image_alt' => null,
            'open_in_new_tab' => false,
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'sensitive_content' => false,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_featured_link() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_images/', settings()->links->image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'image_alt' => $_POST['image_alt'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'sensitive_content' => $_POST['sensitive_content'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_image_grid() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [2, 3]) ? (int) $_POST['columns'] : 2;

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url'], true);

        $db_image = $this->handle_image_upload(null, 'block_images/', settings()->links->image_size_limit);

        $type = 'image_grid';
        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'image_alt' => null,
            'open_in_new_tab' => false,
            'columns' => $_POST['columns'],

            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_image_grid() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['image_alt'] = mb_substr(query_clean($_POST['image_alt']), 0, 100);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [2, 3]) ? (int) $_POST['columns'] : 2;
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_images/', settings()->links->image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'image_alt' => $_POST['image_alt'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'columns' => $_POST['columns'],

            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_image_comparison() {
        $_POST['link_id'] = (int) $_POST['link_id'];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $before_image = $this->handle_file_upload(null, 'before_image', 'before_image_remove', $this->biolink_blocks['image_comparison']['whitelisted_image_extensions'], 'block_images/', settings()->links->image_size_limit);
        $after_image = $this->handle_file_upload(null, 'after_image', 'after_image_remove', $this->biolink_blocks['image_comparison']['whitelisted_image_extensions'], 'block_images/', settings()->links->image_size_limit);

        $type = 'image_comparison';
        $settings = json_encode([
            'before_text' => '',
            'before_image' => $before_image,
            'before_image_alt' => null,
            'after_text' => '',
            'after_image' => $after_image,
            'after_image_alt' => null,
            'columns' => 1,

            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_image_comparison() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['before_text'] = mb_substr(query_clean($_POST['before_text']), 0, 128);
        $_POST['before_image_alt'] = mb_substr(query_clean($_POST['before_image_alt']), 0, 100);
        $_POST['after_text'] = mb_substr(query_clean($_POST['after_text']), 0, 128);
        $_POST['after_image_alt'] = mb_substr(query_clean($_POST['after_image_alt']), 0, 100);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Image upload */
        $before_image = $this->handle_file_upload($biolink_block->settings->before_image, 'before_image', 'before_image_remove', $this->biolink_blocks['image_comparison']['whitelisted_image_extensions'], 'block_images/', settings()->links->image_size_limit);
        $after_image = $this->handle_file_upload($biolink_block->settings->after_image, 'after_image', 'after_image_remove', $this->biolink_blocks['image_comparison']['whitelisted_image_extensions'], 'block_images/', settings()->links->image_size_limit);

        $before_image_url = $before_image ? \Altum\Uploads::get_full_url('block_images') . $before_image : null;
        $after_image_url = $after_image ? \Altum\Uploads::get_full_url('block_images') . $after_image : null;

        $settings = json_encode([
            'before_text' => $_POST['before_text'],
            'before_image' => $before_image,
            'after_text' => $_POST['after_text'],
            'after_image' => $after_image,
            'before_image_alt' => $_POST['before_image_alt'],
            'after_image_alt' => $_POST['after_image_alt'],
            'columns' => $_POST['columns'],

            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['before_image' => $before_image_url, 'after_image' => $after_image_url]]);
    }

    private function create_biolink_divider() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['margin_top'] = $_POST['margin_top'] > 7 || $_POST['margin_top'] < 0 ? 3 : (int) $_POST['margin_top'];
        $_POST['margin_bottom'] = $_POST['margin_bottom'] > 7 || $_POST['margin_bottom'] < 0 ? 3 : (int) $_POST['margin_bottom'];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'divider';
        $settings = json_encode([
            'margin_top' => $_POST['margin_top'],
            'margin_bottom' => $_POST['margin_bottom'],
            'background_color' => '#ffffff',
            'icon' => 'fas fa-infinity',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_divider() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['margin_top'] = $_POST['margin_top'] > 7 || $_POST['margin_top'] < 0 ? 3 : (int) $_POST['margin_top'];
        $_POST['margin_bottom'] = $_POST['margin_bottom'] > 7 || $_POST['margin_bottom'] < 0 ? 3 : (int) $_POST['margin_bottom'];
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['icon'] = query_clean($_POST['icon']);

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'margin_top' => $_POST['margin_top'],
            'margin_bottom' => $_POST['margin_bottom'],
            'background_color' => $_POST['background_color'],
            'icon' => $_POST['icon'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_list() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['text'] = mb_substr(input_clean($_POST['text']), 0, 10000);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'list';
        $settings = json_encode([
            'text' => $_POST['text'],
            'icon' => 'fas fa-check-circle',
            'text_color' => '#000000',
            'text_alignment' => 'left',
            'background_color' => '#FFFFFF',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'margin_items_y' => '1',
            'margin_items_x' => '1',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_list() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['text'] = mb_substr(input_clean($_POST['text']), 0, 10000);
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['margin_items_y'] = $_POST['margin_items_y'] > 5 || $_POST['margin_items_y'] < 0 ? 2 : (int) $_POST['margin_items_y'];
        $_POST['margin_items_x'] = $_POST['margin_items_x'] > 3 || $_POST['margin_items_x'] < 0 ? 1 : (int) $_POST['margin_items_x'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'text' => $_POST['text'],
            'icon' => $_POST['icon'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'margin_items_y' => $_POST['margin_items_y'],
            'margin_items_x' => $_POST['margin_items_x'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_alert() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['text'] = mb_substr(input_clean($_POST['text']), 0, 10000);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'alert';
        $settings = json_encode([
            'text' => $_POST['text'],
            'icon' => 'fas fa-check-circle',
            'open_in_new_tab' => false,
            'text_color' => '#ffffff',
            'text_alignment' => 'left',
            'background_color' => '#FFFFFF38',
            'border_width' => 1,
            'border_style' => 'solid',
            'border_color' => '#FFFFFF8C',
            'border_radius' => 'rounded',
            'display_close_button' => true,
            'alert_pause_after_closed' => 60,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_alert() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['text'] = mb_substr(input_clean($_POST['text']), 0, 10000);
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['display_close_button'] = (int) isset($_POST['display_close_button']);
        $_POST['alert_pause_after_closed'] = (int) $_POST['alert_pause_after_closed'];

        $this->check_location_url($_POST['location_url'], true);

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'text' => $_POST['text'],
            'icon' => $_POST['icon'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'display_close_button' => $_POST['display_close_button'],
            'alert_pause_after_closed' => $_POST['alert_pause_after_closed'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'location_url' => $_POST['location_url'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_faq() {
        $_POST['link_id'] = (int) $_POST['link_id'];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        if(!isset($_POST['item_title'])) {
            $_POST['item_title'] = [];
            $_POST['item_content'] = [];
        }

        $items = [];
        foreach($_POST['item_title'] as $key => $value) {
            if(empty(trim($value))) continue;
            if($key >= 100) continue;

            $items[] = [
                'title' => input_clean($value, 128),
                'content' => input_clean($_POST['item_content'][$key], 1000),
            ];
        }

        $type = 'faq';
        $settings = json_encode([
            'items' => $items,
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_faq() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        if(!isset($_POST['item_title'])) {
            $_POST['item_title'] = [];
            $_POST['item_content'] = [];
        }

        $items = [];
        foreach($_POST['item_title'] as $key => $value) {
            if(empty(trim($value))) continue;
            if($key >= 100) continue;

            $items[] = [
                'title' => input_clean($value, 128),
                'content' => input_clean($_POST['item_content'][$key], 1000),
            ];
        }

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'items' => $items,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_timeline() {
        $_POST['link_id'] = (int) $_POST['link_id'];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        if(!isset($_POST['item_title'])) {
            $_POST['item_title'] = [];
            $_POST['item_content'] = [];
        }

        $items = [];
        foreach($_POST['item_title'] as $key => $value) {
            if(empty(trim($value))) continue;
            if($key >= 100) continue;

            $items[] = [
                'title' => input_clean($value, 128),
                'date' => input_clean($_POST['item_date'][$key], 128),
                'description' => input_clean($_POST['item_description'][$key], 1000),
            ];
        }

        $type = 'timeline';
        $settings = json_encode([
            'items' => $items,
            'title_color' => '#ffffff',
            'date_color' => '#ffffff',
            'description_color' => '#ffffff',
            'line_color' => '#FFFFFF',
            'text_alignment' => 'left',
            'background_color' => '#FFFFFF00',
            'border_shadow_style' => 'none',
            'border_shadow_color' => '#00000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_timeline() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['title_color'] = !verify_hex_color($_POST['title_color']) ? '#000000' : $_POST['title_color'];
        $_POST['date_color'] = !verify_hex_color($_POST['date_color']) ? '#000000' : $_POST['date_color'];
        $_POST['line_color'] = !verify_hex_color($_POST['line_color']) ? '#000000' : $_POST['line_color'];
        $_POST['description_color'] = !verify_hex_color($_POST['description_color']) ? '#000000' : $_POST['description_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        if(!isset($_POST['item_title'])) {
            $_POST['item_title'] = [];
            $_POST['item_content'] = [];
        }

        $items = [];
        foreach($_POST['item_title'] as $key => $value) {
            if(empty(trim($value))) continue;
            if($key >= 100) continue;

            $items[] = [
                'title' => input_clean($value, 128),
                'date' => input_clean($_POST['item_date'][$key], 128),
                'description' => input_clean($_POST['item_description'][$key], 1000),
            ];
        }

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'items' => $items,
            'title_color' => $_POST['title_color'],
            'date_color' => $_POST['date_color'],
            'description_color' => $_POST['description_color'],
            'line_color' => $_POST['line_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_review() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['title'] = mb_substr(input_clean($_POST['title']), 0, 128);
        $_POST['description'] = mb_substr(input_clean($_POST['description']), 0, 1024);
        $_POST['author_name'] = mb_substr(input_clean($_POST['author_name']), 0, 128);
        $_POST['author_description'] = mb_substr(input_clean($_POST['author_description']), 0, 128);
        $_POST['stars'] = $_POST['stars'] > 5 || $_POST['stars'] < 0 ? 5 : (int) $_POST['stars'];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        /* Image upload */
        $db_image = $this->handle_image_upload(null, 'block_images/', settings()->links->image_size_limit);

        $type = 'review';
        $settings = json_encode([
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'author_name' => $_POST['author_name'],
            'author_description' => $_POST['author_description'],
            'image' => $db_image,
            'stars' => $_POST['stars'],

            'title_color' => '#000000',
            'description_color' => '#000000',
            'author_name_color' => '#000000',
            'author_description_color' => '#000000',
            'stars_color' => '#FFDF00',
            'text_alignment' => 'left',
            'background_color' => '#FFFFFF',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_review() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['title'] = mb_substr(input_clean($_POST['title']), 0, 128);
        $_POST['description'] = mb_substr(input_clean($_POST['description']), 0, 1024);
        $_POST['author_name'] = mb_substr(input_clean($_POST['author_name']), 0, 128);
        $_POST['author_description'] = mb_substr(input_clean($_POST['author_description']), 0, 128);
        $_POST['stars'] = $_POST['stars'] > 5 || $_POST['stars'] < 0 ? 5 : (int) $_POST['stars'];
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['title_color'] = !verify_hex_color($_POST['title_color']) ? '#000000' : $_POST['title_color'];
        $_POST['description_color'] = !verify_hex_color($_POST['description_color']) ? '#000000' : $_POST['description_color'];
        $_POST['author_name_color'] = !verify_hex_color($_POST['author_name_color']) ? '#000000' : $_POST['author_name_color'];
        $_POST['author_description_color'] = !verify_hex_color($_POST['author_description_color']) ? '#000000' : $_POST['author_description_color'];
        $_POST['stars_color'] = !verify_hex_color($_POST['stars_color']) ? '#000000' : $_POST['stars_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_images/', settings()->links->image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_images') . $db_image : null;

        $settings = json_encode([
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'author_name' => $_POST['author_name'],
            'author_description' => $_POST['author_description'],
            'stars' => $_POST['stars'],
            'image' => $db_image,
            'title_color' => $_POST['title_color'],
            'description_color' => $_POST['description_color'],
            'author_name_color' => $_POST['author_name_color'],
            'author_description_color' => $_POST['author_description_color'],
            'stars_color' => $_POST['stars_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_image_slider() {
        $_POST['link_id'] = (int) $_POST['link_id'];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        if(!isset($_POST['item_image_alt'])) {
            $_POST['item_image_alt'] = [];
            $_POST['item_location_url'] = [];
        }

        $items = [];
        $count = 1;
        foreach($_POST['item_image_alt'] as $key => $value) {
            if($count++ >= 25) continue;

            $_POST['item_location_url'][$key] = get_url($_POST['item_location_url'][$key]);
            $this->check_location_url($_POST['item_location_url'][$key], true);

            $image = $this->handle_file_upload(null, 'item_image_' . $key, 'image_remove', ['jpg', 'jpeg', 'png', 'svg', 'ico', 'gif'], 'block_images/', settings()->links->image_size_limit);

            $items[md5($image)] = [
                'image_alt' => input_clean($value, 100),
                'location_url' => $_POST['item_location_url'][$key],
                'image' => $image,
            ];
        }

        $type = 'image_slider';
        $settings = json_encode([
            'items' => $items,
            'width_height' => '20',
            'gap' => '2',
            'autoplay_interval' => 5,
            'display_multiple' => true,
            'display_pagination' => true,
            'autoplay' => true,
            'display_arrows' => true,
            'open_in_new_tab' => false,

            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_image_slider() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['width_height'] = $_POST['width_height'] > 25 || $_POST['width_height'] < 10 ? 15 : (int) $_POST['width_height'];
        $_POST['gap'] = $_POST['gap'] > 5 || $_POST['gap'] < 0 ? 2 : (int) $_POST['gap'];
        $_POST['autoplay_interval'] = $_POST['autoplay_interval'] >= 1 || $_POST['autoplay_interval'] <= 20 ? (int) $_POST['autoplay_interval'] : 5;
        $_POST['display_arrows'] = (int) isset($_POST['display_arrows']);
        $_POST['autoplay'] = (int) isset($_POST['autoplay']);
        $_POST['display_multiple'] = (int) isset($_POST['display_multiple']);
        $_POST['display_pagination'] = (int) isset($_POST['display_pagination']);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);


        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        if(!isset($_POST['item_image_alt'])) {
            $_POST['item_image_alt'] = [];
            $_POST['item_location_url'] = [];
        }

        $items = [];
        $count = 1;
        foreach($_POST['item_image_alt'] as $key => $value) {
            if($count++ >= 25) continue;

            $_POST['item_location_url'][$key] = get_url($_POST['item_location_url'][$key]);
            $this->check_location_url($_POST['item_location_url'][$key], true);

            $image = $this->handle_file_upload($biolink_block->settings->items->{$key}->image ?? null, 'item_image_' . $key, 'image_remove', ['jpg', 'jpeg', 'png', 'svg', 'ico', 'gif'], 'block_images/', settings()->links->image_size_limit);

            $items[md5($image)] = [
                'image_alt' => input_clean($value, 100),
                'location_url' => $_POST['item_location_url'][$key],
                'image' => $image,
            ];
        }

        /* Make sure to delete old images if needed */
        foreach($biolink_block->settings->items as $key => $item) {
            if((isset($items[$key]) && $items[$key]['image'] != $item->image) || !isset($items[$key])) {
                \Altum\Uploads::delete_uploaded_file($item->image, 'block_images');
            }
        }

        $settings = json_encode([
            'items' => (array) $items,
            'width_height' => $_POST['width_height'],
            'gap' => $_POST['gap'],
            'autoplay_interval' => $_POST['autoplay_interval'],
            'display_multiple' => $_POST['display_multiple'],
            'autoplay' => $_POST['autoplay'],
            'display_arrows' => $_POST['display_arrows'],
            'display_pagination' => $_POST['display_pagination'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],

            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_discord() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['server_id'] = (int) $_POST['server_id'];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'discord';
        $settings = json_encode([
            'server_id' => $_POST['server_id'],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_discord() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['server_id'] = (int) $_POST['server_id'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'server_id' => $_POST['server_id'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_countdown() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['counter_end_date'] = (new \DateTime($_POST['counter_end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'countdown';
        $settings = json_encode([
            'counter_end_date' => $_POST['counter_end_date'],
            'expired_text' => '',
            'text_color' => '#000000',
            'description_color' => '#808080',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_countdown() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['counter_end_date'] = (new \DateTime($_POST['counter_end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['description_color'] = !verify_hex_color($_POST['description_color']) ? '#000000' : $_POST['description_color'];
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        /* Expired text */
        $_POST['expired_text'] = quilljs_to_bootstrap($_POST['expired_text']);

        /* Purify the text */
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 's,p,b,strong,i,em,u,strike,blockquote,code,pre,ul,ol,li,a[href],span[style|class],div[style|class],br');
        $config->set('CSS.AllowedProperties', [
            'color',
            'background-color',
            'text-align',
            'font-size',
        ]);

        /* Allow class and style on selected tags */
        $config->set('HTML.AllowedAttributes', 'span.class,span.style,div.class,div.style,p.class,p.style,a.href,a.style');
        $config->set('Attr.AllowedClasses', null);
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('AutoFormat.Linkify', true);

        $purifier = new \HTMLPurifier($config);
        $_POST['expired_text'] = @$purifier->purify($_POST['expired_text']);

        /* Limit max length */
        $_POST['expired_text'] = mb_substr($_POST['expired_text'], 0, 10000);

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'counter_end_date' => $_POST['counter_end_date'],
            'expired_text' => $_POST['expired_text'],
            'text_color' => $_POST['text_color'],
            'description_color' => $_POST['description_color'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_file($type) {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        /* File upload */
        $size_limit = in_array($type, ['file', 'pdf_document', 'powerpoint_presentation', 'excel_spreadsheet']) ? settings()->links->file_size_limit : settings()->links->{$type . '_size_limit'};
        $db_file = $this->handle_file_upload(null, 'file', 'file_remove', $this->biolink_blocks[$type]['whitelisted_file_extensions'], 'files/', $size_limit);

        $settings = [
            'file' => $db_file,
            'name' => $_POST['name'],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ];

        if($type == 'video') {
            $settings['poster_url'] = get_url($_POST['poster_url'] ?? null);
            $settings['video_autoplay'] = false; //(int) isset($_POST['video_autoplay']);
            $settings['video_controls'] = true; //(int) isset($_POST['video_controls']);
            $settings['video_loop'] = false; //(int) isset($_POST['video_loop']);
            $settings['video_muted'] = false; //(int) isset($_POST['video_muted']);

            $settings = $settings + [
                'border_shadow_style' => 'subtle',
                'border_shadow_color' => '#00000010',
                'border_width' => 0,
                'border_style' => 'solid',
                'border_color' => '#ffffff',
                'border_radius' => 'rounded',
            ];
        }

        if($type == 'audio') {
            $settings['audio_autoplay'] = false; //(int) isset($_POST['audio_autoplay']);
            $settings['audio_controls'] = true; //(int) isset($_POST['audio_controls']);
            $settings['audio_loop'] = false; //(int) isset($_POST['audio_loop']);
            $settings['audio_muted'] = false; //(int) isset($_POST['audio_muted']);
        }

        if(in_array($type, ['file', 'pdf_document', 'powerpoint_presentation', 'excel_spreadsheet'])) {
            $settings = array_merge($settings, [
                'text_color' => '#000000',
                'text_alignment' => 'center',
                'background_color' => '#ffffff',
                'border_shadow_style' => 'subtle',
                'border_shadow_color' => '#00000010',
                'border_width' => 0,
                'border_style' => 'solid',
                'border_color' => '#ffffff',
                'border_radius' => 'rounded',
                'animation' => false,
                'animation_runs' => 'repeat-1',
                'icon' => '',
                'image' => '',
                'open_in_new_tab' => true,
            ]);
        }

        $settings = json_encode($settings);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_file($type) {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* File upload */
        $size_limit = in_array($type, ['file', 'pdf_document', 'powerpoint_presentation', 'excel_spreadsheet']) ? settings()->links->file_size_limit : settings()->links->{$type . '_size_limit'};
        $db_file = $this->handle_file_upload($biolink_block->settings->file, 'file', 'file_remove', $this->biolink_blocks[$type]['whitelisted_file_extensions'], 'files/', $size_limit);

        $settings = [
            'file' => $db_file,
            'name' => $_POST['name'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ];

        if(in_array($type, ['file', 'pdf_document', 'powerpoint_presentation', 'excel_spreadsheet'])) {
            /* Image upload */
            $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

            $settings = $settings + [
                'text_color' => $_POST['text_color'],
                'text_alignment' => $_POST['text_alignment'],
                'background_color' => $_POST['background_color'],
                'border_radius' => $_POST['border_radius'],
                'border_width' => $_POST['border_width'],
                'border_style' => $_POST['border_style'],
                'border_color' => $_POST['border_color'],
                'border_shadow_style' => $_POST['border_shadow_style'],
                'border_shadow_color' => $_POST['border_shadow_color'],
                'animation' => $_POST['animation'],
                'animation_runs' => $_POST['animation_runs'],
                'icon' => $_POST['icon'],
                'image' => $db_image,
                'open_in_new_tab' => $_POST['open_in_new_tab'],
            ];
        }

        if($type == 'video') {
            $settings['poster_url'] = get_url($_POST['poster_url'] ?? null);
            $settings['video_autoplay'] = (int) isset($_POST['video_autoplay']);
            $settings['video_controls'] = (int) isset($_POST['video_controls']);
            $settings['video_loop'] = (int) isset($_POST['video_loop']);
            $settings['video_muted'] = (int) isset($_POST['video_muted']);

            $settings = $settings + [
                'border_radius' => $_POST['border_radius'],
                'border_width' => $_POST['border_width'],
                'border_style' => $_POST['border_style'],
                'border_color' => $_POST['border_color'],
                'border_shadow_style' => $_POST['border_shadow_style'],
                'border_shadow_color' => $_POST['border_shadow_color'],
            ];
        }

        if($type == 'audio') {
            $settings['audio_autoplay'] = (int) isset($_POST['audio_autoplay']);
            $settings['audio_controls'] = (int) isset($_POST['audio_controls']);
            $settings['audio_loop'] = (int) isset($_POST['audio_loop']);
            $settings['audio_muted'] = (int) isset($_POST['audio_muted']);
        }

        $settings = json_encode($settings);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url ?? null]]);

    }

    private function create_biolink_cta() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['type'] = in_array($_POST['type'], ['email', 'call', 'sms', 'facetime']) ? query_clean($_POST['type']) : 'email';
        $_POST['value'] = mb_substr(query_clean($_POST['value']), 0, 320);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'cta';
        $settings = json_encode([
            'type' => $_POST['type'],
            'value' => $_POST['value'],
            'name' => $_POST['name'],
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_cta() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['type'] = in_array($_POST['type'], ['email', 'call', 'sms', 'facetime']) ? query_clean($_POST['type']) : 'email';
        $_POST['value'] = mb_substr(query_clean($_POST['value']), 0, 320);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'type' => $_POST['type'],
            'value' => $_POST['value'],
            'name' => $_POST['name'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_external_item() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['description'] = mb_substr(query_clean($_POST['description']), 0, 256);
        $_POST['price'] = mb_substr(query_clean($_POST['price']), 0, 32);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url'], true);

        $type = 'external_item';
        $settings = json_encode([
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'name_color' => '#000000',
            'description_color' => '#808080',
            'price_color' => '#000000',
            'open_in_new_tab' => false,
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'image' => '',
            'text_alignment' => 'left',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_external_item() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['description'] = mb_substr(query_clean($_POST['description']), 0, 256);
        $_POST['price'] = mb_substr(query_clean($_POST['price']), 0, 32);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['name_color'] = !verify_hex_color($_POST['name_color']) ? '#000000' : $_POST['name_color'];
        $_POST['description_color'] = !verify_hex_color($_POST['description_color']) ? '#000000' : $_POST['description_color'];
        $_POST['price_color'] = !verify_hex_color($_POST['price_color']) ? '#000000' : $_POST['price_color'];
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Check for any errors */
        $required_fields = ['name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'name_color' => $_POST['name_color'],
            'description_color' => $_POST['description_color'],
            'price_color' => $_POST['price_color'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'image' => $db_image,
            'text_alignment' => $_POST['text_alignment'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url], 'location_url' => $_POST['location_url']]);
    }

    private function create_biolink_share() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'share';
        $settings = json_encode([
            'name' => $_POST['name'],
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_share() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Check for any errors */
        $required_fields = ['location_url', 'name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url], 'location_url' => $_POST['location_url']]);
    }

    private function create_biolink_coupon() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = input_clean($_POST['name'], 128);
        $_POST['coupon'] = input_clean($_POST['coupon'], 32);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'coupon';
        $settings = json_encode([
            'name' => $_POST['name'],
            'coupon' => $_POST['coupon'],
            'description' => '',
            'button_text' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_coupon() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = input_clean($_POST['name'], 128);
        $_POST['description'] = input_clean($_POST['description'], 256);
        $_POST['coupon'] = input_clean($_POST['coupon'], 32);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Check for any errors */
        $required_fields = ['name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url'], true);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'coupon' => $_POST['coupon'],
            'button_text' => $_POST['button_text'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url], 'location_url' => $_POST['location_url']]);
    }

    private function create_biolink_youtube_feed() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['channel_id'] = mb_substr(query_clean($_POST['channel_id']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'youtube_feed';
        $settings = json_encode([
            'channel_id' => $_POST['channel_id'],
            'amount' => 5,
            'open_in_new_tab' => false,
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_youtube_feed() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['channel_id'] = mb_substr(query_clean($_POST['channel_id']), 0, 128);
        $_POST['amount'] = (int) $_POST['amount'];
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'channel_id' => $_POST['channel_id'],
            'amount' => $_POST['amount'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);
        cache()->deleteItem('biolink_block?block_id=' . $biolink_block->biolink_block_id . '&type=youtube_feed');

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_paypal() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['type'] = in_array($_POST['type'], ['buy_now', 'add_to_cart', 'donation']) ? $_POST['type'] : 'buy_now';
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['email'] = input_clean_email($_POST['email'] ?? '');
        $_POST['title'] = mb_substr(query_clean($_POST['title']), 0, 320);
        $_POST['currency'] = mb_substr(query_clean($_POST['currency']), 0, 8);
        $_POST['price'] = (float) $_POST['price'];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'paypal';
        $settings = json_encode([
            'type' => $_POST['type'],
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'title' => $_POST['title'],
            'currency' => $_POST['currency'],
            'price' => $_POST['price'],
            'thank_you_url' => '',
            'cancel_url' => '',
            'open_in_new_tab' => false,
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_paypal() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['type'] = in_array($_POST['type'], ['buy_now', 'add_to_cart', 'donation']) ? $_POST['type'] : 'buy_now';
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['email'] = input_clean_email($_POST['email'] ?? '');
        $_POST['title'] = mb_substr(query_clean($_POST['title']), 0, 320);
        $_POST['currency'] = mb_substr(query_clean($_POST['currency']), 0, 8);
        $_POST['price'] = (float) $_POST['price'];
        $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
        $_POST['cancel_url'] = get_url($_POST['cancel_url']);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Check for any errors */
        $required_fields = ['name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'type' => $_POST['type'],
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'title' => $_POST['title'],
            'currency' => $_POST['currency'],
            'price' => $_POST['price'],
            'thank_you_url' => $_POST['thank_you_url'],
            'cancel_url' => $_POST['cancel_url'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_phone_collector() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'phone_collector';
        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'phone_placeholder' => l('biolink_phone_collector.phone_placeholder_default'),
            'name_placeholder' => l('biolink_phone_collector.name_placeholder_default'),
            'button_text' => l('biolink_phone_collector.button_text_default'),
            'success_text' => l('biolink_phone_collector.success_text_default'),
            'thank_you_url' => '',
            'show_agreement' => false,
            'agreement_url' => '',
            'agreement_text' => '',
            'notifications' => [],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_phone_collector() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['phone_placeholder'] = mb_substr(query_clean($_POST['phone_placeholder']), 0, 64);
        $_POST['name_placeholder'] = mb_substr(query_clean($_POST['name_placeholder']), 0, 64);
        $_POST['button_text'] = input_clean($_POST['button_text'], 64);
        $_POST['success_text'] = mb_substr(query_clean($_POST['success_text']), 0, 256);
        $_POST['show_agreement'] = (int) isset($_POST['show_agreement']);
        $_POST['agreement_url'] = get_url($_POST['agreement_url']);
        $_POST['agreement_text'] = mb_substr(query_clean($_POST['agreement_text']), 0, 256);
        $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Get available notification handlers */
        $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

        /* Notification handlers */
        $_POST['notifications'] = array_map(
            'intval',
            array_filter($_POST['notifications'] ?? [], function($notification_handler_id) use($notification_handlers) {
                return array_key_exists($notification_handler_id, $notification_handlers);
            })
        );

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'phone_placeholder' => $_POST['phone_placeholder'],
            'name_placeholder' => $_POST['name_placeholder'],
            'button_text' => $_POST['button_text'],
            'success_text' => $_POST['success_text'],
            'thank_you_url' => $_POST['thank_you_url'],
            'show_agreement' => $_POST['show_agreement'],
            'agreement_url' => $_POST['agreement_url'],
            'agreement_text' => $_POST['agreement_text'],
            'notifications' => $_POST['notifications'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_contact_collector() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'contact_collector';
        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'phone_placeholder' => l('biolink_contact_collector.phone_placeholder_default'),
            'name_placeholder' => l('biolink_contact_collector.name_placeholder_default'),
            'message_placeholder' => l('biolink_contact_collector.message_placeholder_default'),
            'email_placeholder' => l('biolink_contact_collector.email_placeholder_default'),
            'button_text' => l('biolink_contact_collector.button_text_default'),
            'success_text' => l('biolink_contact_collector.success_text_default'),
            'thank_you_url' => '',
            'show_agreement' => false,
            'agreement_url' => '',
            'agreement_text' => '',
            'notifications' => [],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_contact_collector() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['phone_placeholder'] = mb_substr(query_clean($_POST['phone_placeholder']), 0, 64);
        $_POST['name_placeholder'] = mb_substr(query_clean($_POST['name_placeholder']), 0, 64);
        $_POST['email_placeholder'] = mb_substr(query_clean($_POST['email_placeholder']), 0, 64);
        $_POST['message_placeholder'] = mb_substr(query_clean($_POST['message_placeholder']), 0, 512);
        $_POST['button_text'] = input_clean($_POST['button_text'], 64);
        $_POST['success_text'] = mb_substr(query_clean($_POST['success_text']), 0, 256);
        $_POST['show_agreement'] = (int) isset($_POST['show_agreement']);
        $_POST['agreement_url'] = get_url($_POST['agreement_url']);
        $_POST['agreement_text'] = mb_substr(query_clean($_POST['agreement_text']), 0, 256);
        $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        /* Get available notification handlers */
        $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

        /* Notification handlers */
        $_POST['notifications'] = array_map(
            'intval',
            array_filter($_POST['notifications'] ?? [], function($notification_handler_id) use($notification_handlers) {
                return array_key_exists($notification_handler_id, $notification_handlers);
            })
        );

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'phone_placeholder' => $_POST['phone_placeholder'],
            'name_placeholder' => $_POST['name_placeholder'],
            'email_placeholder' => $_POST['email_placeholder'],
            'message_placeholder' => $_POST['message_placeholder'],
            'button_text' => $_POST['button_text'],
            'success_text' => $_POST['success_text'],
            'thank_you_url' => $_POST['thank_you_url'],
            'show_agreement' => $_POST['show_agreement'],
            'agreement_url' => $_POST['agreement_url'],
            'agreement_text' => $_POST['agreement_text'],
            'notifications' => $_POST['notifications'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    /* Custom code: FC-2026-03-23: lead funnel block phase 1 */
    private function create_biolink_lead_funnel() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'lead_funnel';
        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'open_mode' => 'popup',
            'popup_background_color' => '#ffffff',
            'popup_text_color' => '#212529',
            'popup_button_background_color' => '#007bff',
            'popup_button_text_color' => '#ffffff',
            'page_background_color' => '#ffffff',
            'page_text_color' => '#212529',
            'page_button_background_color' => '#007bff',
            'page_button_text_color' => '#ffffff',
            'popup_title' => $_POST['name'],
            'popup_subtitle' => '',
            'description' => '',
            'video_provider' => 'youtube',
            'video_url' => '',
            'show_name' => true,
            'show_email' => true,
            'show_phone' => true,
            'show_message' => true,
            'require_name' => true,
            'require_email' => true,
            'require_phone' => false,
            'require_message' => false,
            'email_placeholder' => l('biolink_lead_funnel.email_placeholder_default'),
            'phone_placeholder' => l('biolink_lead_funnel.phone_placeholder_default'),
            'name_placeholder' => l('biolink_lead_funnel.name_placeholder_default'),
            'message_placeholder' => l('biolink_lead_funnel.message_placeholder_default'),
            'button_text' => l('biolink_lead_funnel.button_text_default'),
            'success_text' => l('biolink_lead_funnel.success_text_default'),
            'show_agreement' => false,
            'agreement_url' => '',
            'agreement_text' => '',
            'thank_you_type' => 'message',
            'thank_you_title' => l('biolink_lead_funnel.thank_you_title_default'),
            'thank_you_text' => l('biolink_lead_funnel.thank_you_text_default'),
            'thank_you_url' => '',
            'thank_you_biolink_id' => null,
            'thank_you_file_source' => 'local_upload',
            'thank_you_file' => '',
            'thank_you_file_url' => '',
            'thank_you_button_text' => l('biolink_lead_funnel.thank_you_button_text_default'),
            'notifications' => [],
            'columns' => 1,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_lead_funnel() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['popup_title'] = mb_substr(input_clean($_POST['popup_title'] ?? ''), 0, 128);
        /* Custom code: FC-2026-03-23: lead funnel rich text sanitizer */
        $lead_funnel_rich_text_sanitizer = function($content, $max_length = 10000) {
            $content = quilljs_to_bootstrap($content ?? '');

            /* Custom code: FC-2026-03-23: preserve Quill rich text styles on live lead funnel render */
            $lead_funnel_class_style_map = [
                'ql-align-center' => 'text-align:center',
                'ql-align-right' => 'text-align:right',
                'ql-align-justify' => 'text-align:justify',
                'ql-size-small' => 'font-size:0.75em',
                'ql-size-large' => 'font-size:1.5em',
                'ql-size-huge' => 'font-size:2.5em',
                'ql-font-segoe-ui' => 'font-family:\'Segoe UI\',sans-serif',
                'ql-font-roboto' => 'font-family:\'Roboto\',sans-serif',
                'ql-font-scriptorama' => 'font-family:\'Scriptorama\',sans-serif',
                'ql-font-helvetica-neue-medium' => 'font-family:\'Helvetica Neue Medium\',sans-serif',
                'ql-font-helvetica-neue-lt' => 'font-family:\'Helvetica Neue LT\',sans-serif',
            ];

            $content = preg_replace_callback('/<([a-z0-9]+)([^>]*)class="([^"]*)"([^>]*)>/i', function($matches) use ($lead_funnel_class_style_map) {
                $tag = $matches[1];
                $before = $matches[2];
                $class_attribute = $matches[3];
                $after = $matches[4];

                $styles = [];

                foreach($lead_funnel_class_style_map as $class_name => $style_rule) {
                    if(strpos(' ' . $class_attribute . ' ', ' ' . $class_name . ' ') !== false) {
                        $styles[] = $style_rule;
                    }
                }

                if(empty($styles)) {
                    return $matches[0];
                }

                $attributes = $before . 'class="' . $class_attribute . '"' . $after;

                if(preg_match('/style="([^"]*)"/i', $attributes, $style_matches)) {
                    $merged_styles = rtrim(trim($style_matches[1]), ';');
                    if($merged_styles !== '') {
                        $merged_styles .= ';';
                    }

                    $merged_styles .= implode(';', $styles);

                    $attributes = preg_replace('/style="[^"]*"/i', 'style="' . $merged_styles . '"', $attributes, 1);
                } else {
                    $attributes .= ' style="' . implode(';', $styles) . '"';
                }

                return '<' . $tag . $attributes . '>';
            }, $content);
            /* /Custom code: FC-2026-03-23 */

            $lead_funnel_allowed_classes = [
                'ql-align-center',
                'ql-align-right',
                'ql-align-justify',
                'ql-size-small',
                'ql-size-large',
                'ql-size-huge',
                'ql-font-segoe-ui',
                'ql-font-roboto',
                'ql-font-scriptorama',
                'ql-font-helvetica-neue-medium',
                'ql-font-helvetica-neue-lt',
                'ql-color-white',
            ];

            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 's[style|class],p[style|class],b[style|class],strong[style|class],i[style|class],em[style|class],u[style|class],strike[style|class],blockquote[style|class],code[style|class],pre[style|class],ul[style|class],ol[style|class],li[style|class],a[href|style|class|target|rel],span[style|class],div[style|class],br');
            $config->set('CSS.AllowedProperties', [
                'color',
                'background-color',
                'text-align',
                'font-size',
                'font-family',
            ]);
            $config->set('HTML.AllowedAttributes', 's.class,s.style,p.class,p.style,b.class,b.style,strong.class,strong.style,i.class,i.style,em.class,em.style,u.class,u.style,strike.class,strike.style,blockquote.class,blockquote.style,code.class,code.style,pre.class,pre.style,span.class,span.style,div.class,div.style,li.class,li.style,ul.class,ul.style,ol.class,ol.style,a.href,a.style,a.class,a.target,a.rel');
            $config->set('Attr.AllowedClasses', $lead_funnel_allowed_classes);
            $config->set('AutoFormat.AutoParagraph', false);
            $config->set('AutoFormat.Linkify', true);

            $purifier = new \HTMLPurifier($config);
            $content = @$purifier->purify($content);

            return mb_substr($content, 0, $max_length);
        };

        $lead_funnel_inline_rich_text_sanitizer = function($content, $max_length = 10000) use ($lead_funnel_rich_text_sanitizer) {
            $content = $lead_funnel_rich_text_sanitizer($content, $max_length);
            $content = preg_replace('/<\/p>\s*<p[^>]*>/i', '<br>', $content);
            $content = preg_replace('/<p[^>]*>/i', '', $content);
            $content = preg_replace('/<\/p>/i', '', $content);

            return mb_substr($content, 0, $max_length);
        };

        $_POST['popup_subtitle'] = $lead_funnel_rich_text_sanitizer($_POST['popup_subtitle'] ?? '');
        $_POST['description'] = $lead_funnel_rich_text_sanitizer($_POST['description'] ?? '');
        /* /Custom code: FC-2026-03-23 */
        $_POST['video_provider'] = in_array($_POST['video_provider'] ?? '', ['youtube', 'vimeo']) ? query_clean($_POST['video_provider']) : 'youtube';
        $_POST['video_url'] = get_url($_POST['video_url'] ?? '');
        $_POST['open_mode'] = in_array($_POST['open_mode'] ?? '', ['popup', 'page']) ? query_clean($_POST['open_mode']) : 'popup';
        $_POST['popup_background_color'] = !verify_hex_color($_POST['popup_background_color'] ?? null) ? '#ffffff' : $_POST['popup_background_color'];
        $_POST['popup_text_color'] = !verify_hex_color($_POST['popup_text_color'] ?? null) ? '#212529' : $_POST['popup_text_color'];
        $_POST['popup_button_background_color'] = !verify_hex_color($_POST['popup_button_background_color'] ?? null) ? '#007bff' : $_POST['popup_button_background_color'];
        $_POST['popup_button_text_color'] = !verify_hex_color($_POST['popup_button_text_color'] ?? null) ? '#ffffff' : $_POST['popup_button_text_color'];
        $_POST['page_background_color'] = !verify_hex_color($_POST['page_background_color'] ?? null) ? '#ffffff' : $_POST['page_background_color'];
        $_POST['page_text_color'] = !verify_hex_color($_POST['page_text_color'] ?? null) ? '#212529' : $_POST['page_text_color'];
        $_POST['page_button_background_color'] = !verify_hex_color($_POST['page_button_background_color'] ?? null) ? '#007bff' : $_POST['page_button_background_color'];
        $_POST['page_button_text_color'] = !verify_hex_color($_POST['page_button_text_color'] ?? null) ? '#ffffff' : $_POST['page_button_text_color'];
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['name_placeholder'] = mb_substr(query_clean($_POST['name_placeholder'] ?? ''), 0, 64);
        $_POST['email_placeholder'] = mb_substr(query_clean($_POST['email_placeholder'] ?? ''), 0, 64);
        $_POST['phone_placeholder'] = mb_substr(query_clean($_POST['phone_placeholder'] ?? ''), 0, 64);
        $_POST['message_placeholder'] = mb_substr(query_clean($_POST['message_placeholder'] ?? ''), 0, 128);
        $_POST['button_text'] = input_clean($_POST['button_text'] ?? '', 64);
        $_POST['success_text'] = mb_substr(query_clean($_POST['success_text'] ?? ''), 0, 256);
        $_POST['show_name'] = (int) isset($_POST['show_name']);
        $_POST['show_email'] = (int) isset($_POST['show_email']);
        $_POST['show_phone'] = (int) isset($_POST['show_phone']);
        $_POST['show_message'] = (int) isset($_POST['show_message']);
        $_POST['require_name'] = (int) isset($_POST['require_name']);
        $_POST['require_email'] = (int) isset($_POST['require_email']);
        $_POST['require_phone'] = (int) isset($_POST['require_phone']);
        $_POST['require_message'] = (int) isset($_POST['require_message']);
        $_POST['show_agreement'] = (int) isset($_POST['show_agreement']);
        $_POST['agreement_url'] = get_url($_POST['agreement_url'] ?? '');
        $_POST['agreement_text'] = $lead_funnel_inline_rich_text_sanitizer($_POST['agreement_text'] ?? '');
        $_POST['thank_you_type'] = in_array($_POST['thank_you_type'] ?? '', ['message', 'external_url', 'biolink_redirect', 'file_download']) ? query_clean($_POST['thank_you_type']) : 'message';
        $_POST['thank_you_title'] = $lead_funnel_inline_rich_text_sanitizer($_POST['thank_you_title'] ?? '');
        $_POST['thank_you_text'] = $lead_funnel_rich_text_sanitizer($_POST['thank_you_text'] ?? '');
        $_POST['thank_you_url'] = get_url($_POST['thank_you_url'] ?? '');
        $_POST['thank_you_biolink_id'] = !empty($_POST['thank_you_biolink_id']) ? (int) $_POST['thank_you_biolink_id'] : null;
        $_POST['thank_you_file_source'] = in_array($_POST['thank_you_file_source'] ?? '', ['local_upload', 'external_url']) ? query_clean($_POST['thank_you_file_source']) : 'local_upload';
        $_POST['thank_you_file_url'] = get_url($_POST['thank_you_file_url'] ?? '');
        $_POST['thank_you_button_text'] = input_clean($_POST['thank_you_button_text'] ?? '', 64);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        if(!($_POST['show_name'] || $_POST['show_email'] || $_POST['show_phone'] || $_POST['show_message'])) {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        if($_POST['thank_you_type'] == 'external_url' && empty($_POST['thank_you_url'])) {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        if($_POST['thank_you_type'] == 'biolink_redirect') {
            $selected_biolink = $_POST['thank_you_biolink_id']
                ? db()->where('link_id', $_POST['thank_you_biolink_id'])->where('user_id', $this->user->user_id)->where('type', 'biolink')->getOne('links', ['link_id'])
                : null;

            if(!$selected_biolink) {
                Response::json(l('global.error_message.empty_fields'), 'error');
            }
        }

        $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);
        $_POST['notifications'] = array_map(
            'intval',
            array_filter($_POST['notifications'] ?? [], function($notification_handler_id) use($notification_handlers) {
                return array_key_exists($notification_handler_id, $notification_handlers);
            })
        );

        $this->process_display_settings();

        $biolink_block = $this->biolink_block;
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);
        $lead_funnel_local_file_size_limit = min((float) settings()->links->file_size_limit, 15);
        $db_file = $this->handle_file_upload($biolink_block->settings->thank_you_file ?? null, 'thank_you_file', 'thank_you_file_remove', $this->biolink_blocks['lead_funnel']['whitelisted_file_extensions'], 'files/', $lead_funnel_local_file_size_limit);

        if($_POST['thank_you_type'] == 'file_download' && $_POST['thank_you_file_source'] == 'local_upload' && empty($db_file)) {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        if($_POST['thank_you_type'] == 'file_download' && $_POST['thank_you_file_source'] == 'external_url' && empty($_POST['thank_you_file_url'])) {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'open_mode' => $_POST['open_mode'],
            'popup_background_color' => $_POST['popup_background_color'],
            'popup_text_color' => $_POST['popup_text_color'],
            'popup_button_background_color' => $_POST['popup_button_background_color'],
            'popup_button_text_color' => $_POST['popup_button_text_color'],
            'page_background_color' => $_POST['page_background_color'],
            'page_text_color' => $_POST['page_text_color'],
            'page_button_background_color' => $_POST['page_button_background_color'],
            'page_button_text_color' => $_POST['page_button_text_color'],
            'popup_title' => $_POST['popup_title'] ?: $_POST['name'],
            'popup_subtitle' => $_POST['popup_subtitle'],
            'description' => $_POST['description'],
            'video_provider' => $_POST['video_provider'],
            'video_url' => $_POST['video_url'],
            'show_name' => $_POST['show_name'],
            'show_email' => $_POST['show_email'],
            'show_phone' => $_POST['show_phone'],
            'show_message' => $_POST['show_message'],
            'require_name' => $_POST['require_name'],
            'require_email' => $_POST['require_email'],
            'require_phone' => $_POST['require_phone'],
            'require_message' => $_POST['require_message'],
            'name_placeholder' => $_POST['name_placeholder'],
            'email_placeholder' => $_POST['email_placeholder'],
            'phone_placeholder' => $_POST['phone_placeholder'],
            'message_placeholder' => $_POST['message_placeholder'],
            'button_text' => $_POST['button_text'],
            'success_text' => $_POST['success_text'],
            'show_agreement' => $_POST['show_agreement'],
            'agreement_url' => $_POST['agreement_url'],
            'agreement_text' => $_POST['agreement_text'],
            'thank_you_type' => $_POST['thank_you_type'],
            'thank_you_title' => $_POST['thank_you_title'],
            'thank_you_text' => $_POST['thank_you_text'],
            'thank_you_url' => $_POST['thank_you_url'],
            'thank_you_biolink_id' => $_POST['thank_you_biolink_id'],
            'thank_you_file_source' => $_POST['thank_you_file_source'],
            'thank_you_file' => $db_file,
            'thank_you_file_url' => $_POST['thank_you_file_url'],
            'thank_you_button_text' => $_POST['thank_you_button_text'],
            'notifications' => $_POST['notifications'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }
    /* /Custom code: FC-2026-03-23 */

    /* Custom code: FC-2026-04-19: VIP Funnel Hub public foundation block */
    private function create_biolink_vip_funnel_hub() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name'] ?? ''), 0, 128);
        $_POST['location_url'] = get_url($_POST['location_url'] ?? '');
        $_POST['vip_funnel_id'] = (int) ($_POST['vip_funnel_id'] ?? 0);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        if($_POST['location_url']) {
            $this->check_location_url($_POST['location_url']);
        }

        $vip_funnel_id = vip_funnel_studio_get_funnel_row((int) $this->user->user_id, $_POST['vip_funnel_id']) ? $_POST['vip_funnel_id'] : 0;

        $type = 'vip_funnel_hub';
        $settings = json_encode([
            'name' => $_POST['name'],
            'vip_funnel_id' => $vip_funnel_id,
            'image' => '',
            'text_color' => '#ffffff',
            'text_alignment' => 'left',
            'background_color' => '#101826',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#101826',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => 'fas fa-diagram-project',
            'columns' => 1,
            'kicker' => 'Funnel 2.0',
            'title' => 'Pokreni svoj sljedeći korak uz vođeni Funnel 2.0 sustav',
            'subtitle' => 'Jasan put za proizvode, online posao i doživljaj sustava bez preopterećenja.',
            'primary_cta_text' => 'Odaberi svoj smjer',
            'secondary_cta_text' => 'Zatraži VIP pregled',
            'secondary_url' => '',
            'show_paths' => true,
            'path_tags' => [
                'Suradnja i Start paket',
                'Proizvodi bez registracije',
                'FCC sustav i demo',
            ],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_vip_funnel_hub() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url'] ?? '');
        $_POST['secondary_url'] = get_url($_POST['secondary_url'] ?? '');
        $_POST['vip_funnel_id'] = (int) ($_POST['vip_funnel_id'] ?? 0);
        $_POST['name'] = mb_substr(query_clean($_POST['name'] ?? ''), 0, 128);
        $_POST['kicker'] = mb_substr(input_clean($_POST['kicker'] ?? ''), 0, 80);
        $_POST['title'] = mb_substr(input_clean($_POST['title'] ?? ''), 0, 160);
        $_POST['subtitle'] = trim(strip_tags(mb_substr((string) ($_POST['subtitle'] ?? ''), 0, 500)));
        $_POST['primary_cta_text'] = mb_substr(input_clean($_POST['primary_cta_text'] ?? ''), 0, 120);
        $_POST['secondary_cta_text'] = mb_substr(input_clean($_POST['secondary_cta_text'] ?? ''), 0, 120);
        $_POST['show_paths'] = (int) isset($_POST['show_paths']);
        $submitted_path_tags = (array) ($_POST['path_tags'] ?? []);
        $_POST['path_tags'] = [];
        foreach($submitted_path_tags as $path_tag) {
            if(is_array($path_tag) || is_object($path_tag)) {
                continue;
            }

            $path_tag = input_clean((string) $path_tag, 80);
            if($path_tag === '') {
                continue;
            }

            $_POST['path_tags'][] = $path_tag;

            if(count($_POST['path_tags']) >= 3) {
                break;
            }
        }
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#101826' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon'] ?? '');
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#ffffff' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'left';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#101826' : $_POST['background_color'];
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        if($_POST['name'] === '' || $_POST['title'] === '') {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        if($_POST['location_url']) {
            $this->check_location_url($_POST['location_url']);
        }

        if($_POST['secondary_url']) {
            $this->check_location_url($_POST['secondary_url']);
        }

        $vip_funnel_id = vip_funnel_studio_get_funnel_row((int) $this->user->user_id, $_POST['vip_funnel_id']) ? $_POST['vip_funnel_id'] : 0;

        $db_image = $this->handle_image_upload($biolink_block->settings->image ?? '', 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);
        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'vip_funnel_id' => $vip_funnel_id,
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'columns' => $_POST['columns'],
            'kicker' => $_POST['kicker'],
            'title' => $_POST['title'],
            'subtitle' => $_POST['subtitle'],
            'primary_cta_text' => $_POST['primary_cta_text'],
            'secondary_cta_text' => $_POST['secondary_cta_text'],
            'secondary_url' => $_POST['secondary_url'],
            'show_paths' => $_POST['show_paths'],
            'path_tags' => $_POST['path_tags'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url], 'location_url' => $_POST['location_url']]);
    }
    /* /Custom code: FC-2026-04-19 */

    private function create_biolink_appointment_calendar() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'appointment_calendar';
        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'allowed_scheduling_days_ahead' => 7,
            'available_times' => [
                'monday' => [],
                'tuesday' => [],
                'wednesday' => [],
                'thursday' => [],
                'friday' => [],
                'saturday' => [],
                'sunday' => [],
            ],
            'minimum_notice_period_value' => 30,
            'minimum_notice_period_type' => 'minutes',
            'durations' => [
                ['value' => 30, 'type' => 'minutes']
            ],
            'timezone' => \Altum\Date::$default_timezone,
            'phone_placeholder' => l('biolink_appointment_calendar.phone_placeholder_default'),
            'name_placeholder' => l('biolink_appointment_calendar.name_placeholder_default'),
            'message_placeholder' => l('biolink_appointment_calendar.message_placeholder_default'),
            'email_placeholder' => l('biolink_appointment_calendar.email_placeholder_default'),
            'button_text' => l('biolink_appointment_calendar.button_text_default'),
            'success_text' => l('biolink_appointment_calendar.success_text_default'),
            'thank_you_url' => '',
            'show_agreement' => false,
            'agreement_url' => '',
            'agreement_text' => '',
            'notifications' => [],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_appointment_calendar() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        $_POST['timezone'] = in_array($_POST['timezone'], \DateTimeZone::listIdentifiers()) ? input_clean($_POST['timezone']) : Date::$default_timezone;
        $_POST['phone_placeholder'] = mb_substr(query_clean($_POST['phone_placeholder']), 0, 64);
        $_POST['name_placeholder'] = mb_substr(query_clean($_POST['name_placeholder']), 0, 64);
        $_POST['email_placeholder'] = mb_substr(query_clean($_POST['email_placeholder']), 0, 64);
        $_POST['message_placeholder'] = mb_substr(query_clean($_POST['message_placeholder']), 0, 512);
        $_POST['button_text'] = input_clean($_POST['button_text'], 64);
        $_POST['success_text'] = mb_substr(query_clean($_POST['success_text']), 0, 256);
        $_POST['show_agreement'] = (int) isset($_POST['show_agreement']);
        $_POST['agreement_url'] = get_url($_POST['agreement_url']);
        $_POST['agreement_text'] = mb_substr(query_clean($_POST['agreement_text']), 0, 256);
        $_POST['thank_you_url'] = get_url($_POST['thank_you_url']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        if(!isset($_POST['duration_value'])) {
            $_POST['duration_value'] = [];
            $_POST['duration_type'] = [];
        }

        $durations = [];
        $max_limits = ['minutes' => 720, 'hours' => 12];

        foreach ($_POST['duration_value'] as $key => $value) {
            if($key >= 10) {
                continue;
            }

            $trimmed_value = (int) trim($value);

            if($trimmed_value <= 0) continue;

            $type = $_POST['duration_type'][$key] ?? '';

            /* apply unit-based cap */
            if(!in_array($type, ['minutes', 'hours', 'days']) || (int) $trimmed_value > $max_limits[$type]) {
                continue;
            }

            $durations[] = [
                'value' => (int) $trimmed_value,
                'type' => $type,
            ];
        }

        if(empty($durations)) {
            $durations[] = [
                'value' => 30,
                'type' => 'minutes',
            ];
        }

        $_POST['minimum_notice_period_value'] = (int) $_POST['minimum_notice_period_value'];
        $_POST['minimum_notice_period_type'] = isset($_POST['minimum_notice_period_type']) && in_array($_POST['minimum_notice_period_type'], ['minutes', 'hours', 'days']) ? $_POST['minimum_notice_period_type'] : 'minutes';

        /* process available time slots per weekday */
        $available_times = [];
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach($weekdays as $weekday) {
            $weekday_slots = $_POST['available_times'][$weekday] ?? [];

            /* clean + validate each time */
            $available_times[$weekday] = array_values(array_filter(array_map(function($time_slot) {
                /* match HH:MM format */
                return preg_match('/^(2[0-3]|[01]?[0-9]):([0-5][0-9])$/', $time_slot) ? $time_slot : null;
            }, $weekday_slots)));
        }

        /* allowed days ahead scheduling */
        $_POST['allowed_scheduling_days_ahead'] = max(1, min($_POST['allowed_scheduling_days_ahead'], 180));

        /* Get available notification handlers */
        $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

        /* Notification handlers */
        $_POST['notifications'] = array_map(
            'intval',
            array_filter($_POST['notifications'] ?? [], function($notification_handler_id) use($notification_handlers) {
                return array_key_exists($notification_handler_id, $notification_handlers);
            })
        );

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],

            'allowed_scheduling_days_ahead' => $_POST['allowed_scheduling_days_ahead'],
            'available_times' => $available_times,
            'minimum_notice_period_value' => $_POST['minimum_notice_period_value'],
            'minimum_notice_period_type' => $_POST['minimum_notice_period_type'],
            'durations' => $durations,
            'timezone' => $_POST['timezone'],
            'phone_placeholder' => $_POST['phone_placeholder'],
            'name_placeholder' => $_POST['name_placeholder'],
            'email_placeholder' => $_POST['email_placeholder'],
            'message_placeholder' => $_POST['message_placeholder'],
            'button_text' => $_POST['button_text'],
            'success_text' => $_POST['success_text'],
            'thank_you_url' => $_POST['thank_you_url'],
            'show_agreement' => $_POST['show_agreement'],
            'agreement_url' => $_POST['agreement_url'],
            'agreement_text' => $_POST['agreement_text'],
            'notifications' => $_POST['notifications'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_donation() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'donation';
        $settings = [
            'name' => $_POST['name'],
            'image' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',

            'title' => null,
            'description' => null,
            'prefilled_amount' => 5,
            'minimum_amount' => 1,
            'currency' => 'USD',
            'allow_custom_amount' => true,
            'allow_message' => true,
            'thank_you_title' => null,
            'thank_you_description' => null,
            'thank_you_url' => null,
            'payment_processors_ids' => [],
            'email_notification' => null,
            'webhook_url' => null,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ];
        $settings = json_encode($settings);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_donation() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        $_POST['title'] = mb_substr(query_clean($_POST['title']), 0, $this->biolink_blocks['donation']['fields']['title']['max_length']);
        $_POST['description'] = mb_substr(query_clean($_POST['description']), 0, $this->biolink_blocks['donation']['fields']['description']['max_length']);
        $_POST['prefilled_amount'] = (float) $_POST['prefilled_amount'];
        $_POST['minimum_amount'] = (float) $_POST['minimum_amount'];
        $_POST['currency'] = mb_substr(query_clean($_POST['currency']), 0, $this->biolink_blocks['donation']['fields']['currency']['max_length']);
        $_POST['allow_custom_amount'] = (int) isset($_POST['allow_custom_amount']);
        $_POST['allow_message'] = (int) isset($_POST['allow_message']);
        $_POST['thank_you_title'] = mb_substr(query_clean($_POST['thank_you_title']), 0, $this->biolink_blocks['donation']['fields']['thank_you_title']['max_length']);
        $_POST['thank_you_description'] = mb_substr(query_clean($_POST['thank_you_description']), 0, $this->biolink_blocks['donation']['fields']['thank_you_description']['max_length']);
        $_POST['thank_you_url'] = mb_substr(query_clean($_POST['thank_you_url']), 0, $this->biolink_blocks['donation']['fields']['thank_you_url']['max_length']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        $payment_processors = (new \Altum\Models\PaymentProcessor())->get_payment_processors_by_user_id($this->user->user_id);
        $_POST['payment_processors_ids'] = array_map(
            'intval',
            array_filter($_POST['payment_processors_ids'] ?? [], function($payment_processor_id) use($payment_processors) {
                return array_key_exists($payment_processor_id, $payment_processors);
            })
        );

        /* Get available notification handlers */
        $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

        /* Notification handlers */
        $_POST['notifications'] = array_map(
            'intval',
            array_filter($_POST['notifications'] ?? [], function($notification_handler_id) use($notification_handlers) {
                return array_key_exists($notification_handler_id, $notification_handlers);
            })
        );

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'columns' => $_POST['columns'],

            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'prefilled_amount' => $_POST['prefilled_amount'],
            'minimum_amount' => $_POST['minimum_amount'],
            'currency' => $_POST['currency'],
            'allow_custom_amount' => $_POST['allow_custom_amount'],
            'allow_message' => $_POST['allow_message'],
            'thank_you_title' => $_POST['thank_you_title'],
            'thank_you_description' => $_POST['thank_you_description'],
            'thank_you_url' => $_POST['thank_you_url'],
            'payment_processors_ids' => $_POST['payment_processors_ids'],
            'notifications' => $_POST['notifications'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_product() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'product';
        $settings = [
            'name' => $_POST['name'],
            'image' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',

            'file' => null,
            'title' => null,
            'description' => null,
            'price' => 5,
            'minimum_price' => 1,
            'currency' => 'USD',
            'allow_custom_price' => true,
            'thank_you_title' => null,
            'thank_you_description' => null,
            'thank_you_url' => null,
            'payment_processors_ids' => [],
            'email_notification' => null,
            'webhook_url' => null,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ];
        $settings = json_encode($settings);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_product() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        $_POST['title'] = mb_substr(query_clean($_POST['title']), 0, $this->biolink_blocks['product']['fields']['title']['max_length']);
        $_POST['description'] = mb_substr(query_clean($_POST['description']), 0, $this->biolink_blocks['product']['fields']['description']['max_length']);
        $_POST['price'] = (float) $_POST['price'];
        $_POST['minimum_price'] = (float) $_POST['minimum_price'];
        $_POST['currency'] = mb_substr(query_clean($_POST['currency']), 0, $this->biolink_blocks['product']['fields']['currency']['max_length']);
        $_POST['allow_custom_price'] = (int) isset($_POST['allow_custom_price']);
        $_POST['thank_you_title'] = mb_substr(query_clean($_POST['thank_you_title']), 0, $this->biolink_blocks['product']['fields']['thank_you_title']['max_length']);
        $_POST['thank_you_description'] = mb_substr(query_clean($_POST['thank_you_description']), 0, $this->biolink_blocks['product']['fields']['thank_you_description']['max_length']);
        $_POST['thank_you_url'] = mb_substr(query_clean($_POST['thank_you_url']), 0, $this->biolink_blocks['donation']['fields']['thank_you_url']['max_length']);

        $payment_processors = (new \Altum\Models\PaymentProcessor())->get_payment_processors_by_user_id($this->user->user_id);
        $_POST['payment_processors_ids'] = array_map(
            'intval',
            array_filter($_POST['payment_processors_ids'] ?? [], function($payment_processor_id) use($payment_processors) {
                return array_key_exists($payment_processor_id, $payment_processors);
            })
        );

        /* Get available notification handlers */
        $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

        /* Notification handlers */
        $_POST['notifications'] = array_map(
            'intval',
            array_filter($_POST['notifications'] ?? [], function($notification_handler_id) use($notification_handlers) {
                return array_key_exists($notification_handler_id, $notification_handlers);
            })
        );

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* File upload */
        $db_file = $this->handle_file_upload($biolink_block->settings->file, 'file', 'file_remove', $this->biolink_blocks['product']['whitelisted_file_extensions'], \Altum\Uploads::get_path('products_files'), settings()->links->product_file_size_limit);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'columns' => $_POST['columns'],

            'file' => $db_file,
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'minimum_price' => $_POST['minimum_price'],
            'currency' => $_POST['currency'],
            'allow_custom_price' => $_POST['allow_custom_price'],
            'thank_you_title' => $_POST['thank_you_title'],
            'thank_you_description' => $_POST['thank_you_description'],
            'thank_you_url' => $_POST['thank_you_url'],
            'payment_processors_ids' => $_POST['payment_processors_ids'],
            'notifications' => $_POST['notifications'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_service() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'service';
        $settings = [
            'name' => $_POST['name'],
            'image' => '',
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',

            'title' => null,
            'description' => null,
            'price' => null,
            'currency' => 'USD',
            'allow_custom_price' => true,
            'minimum_price' => 1,
            'thank_you_title' => null,
            'thank_you_description' => null,
            'thank_you_url' => null,
            'payment_processors_ids' => [],
            'email_notification' => null,
            'webhook_url' => null,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ];
        $settings = json_encode($settings);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_service() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        $_POST['title'] = mb_substr(query_clean($_POST['title']), 0, $this->biolink_blocks['service']['fields']['title']['max_length']);
        $_POST['description'] = mb_substr(query_clean($_POST['description']), 0, $this->biolink_blocks['service']['fields']['description']['max_length']);
        $_POST['price'] = (float) $_POST['price'];
        $_POST['minimum_price'] = (float) $_POST['minimum_price'];
        $_POST['currency'] = mb_substr(query_clean($_POST['currency']), 0, $this->biolink_blocks['service']['fields']['currency']['max_length']);
        $_POST['thank_you_title'] = mb_substr(query_clean($_POST['thank_you_title']), 0, $this->biolink_blocks['service']['fields']['thank_you_title']['max_length']);
        $_POST['thank_you_description'] = mb_substr(query_clean($_POST['thank_you_description']), 0, $this->biolink_blocks['service']['fields']['thank_you_description']['max_length']);
        $_POST['thank_you_url'] = mb_substr(query_clean($_POST['thank_you_url']), 0, $this->biolink_blocks['donation']['fields']['thank_you_url']['max_length']);
        $_POST['allow_custom_price'] = (int) isset($_POST['allow_custom_price']);

        $payment_processors = (new \Altum\Models\PaymentProcessor())->get_payment_processors_by_user_id($this->user->user_id);
        $_POST['payment_processors_ids'] = array_map(
            'intval',
            array_filter($_POST['payment_processors_ids'] ?? [], function($payment_processor_id) use($payment_processors) {
                return array_key_exists($payment_processor_id, $payment_processors);
            })
        );

        /* Get available notification handlers */
        $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

        /* Notification handlers */
        $_POST['notifications'] = array_map(
            'intval',
            array_filter($_POST['notifications'] ?? [], function($notification_handler_id) use($notification_handlers) {
                return array_key_exists($notification_handler_id, $notification_handlers);
            })
        );

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        $image_url = $db_image ? \Altum\Uploads::get_full_url('block_thumbnail_images') . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'image' => $db_image,
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'columns' => $_POST['columns'],

            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'minimum_price' => $_POST['minimum_price'],
            'currency' => $_POST['currency'],
            'thank_you_title' => $_POST['thank_you_title'],
            'thank_you_description' => $_POST['thank_you_description'],
            'thank_you_url' => $_POST['thank_you_url'],
            'payment_processors_ids' => $_POST['payment_processors_ids'],
            'notifications' => $_POST['notifications'],
            'allow_custom_price' => $_POST['allow_custom_price'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success', ['images' => ['image' => $image_url]]);
    }

    private function create_biolink_map() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['address'] = mb_substr(query_clean($_POST['address']), 0, 64);
        $_POST['location_url'] = get_url($_POST['location_url']);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url'], true);

        $type = 'map';
        $settings = json_encode([
            'address' => $_POST['address'],
            'markers' => '',
            'open_in_new_tab' => false,
            'zoom' => 15,
            'type' => 'roadmap',

            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_map() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['address'] = mb_substr(query_clean($_POST['address']), 0, 64);
        $_POST['open_in_new_tab'] = (int) isset($_POST['open_in_new_tab']);
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['markers'] = input_clean($_POST['markers'], 1024);
        $_POST['zoom'] = in_array($_POST['zoom'], range(1, 20)) ? (int) $_POST['zoom'] : 15;
        $_POST['type'] = in_array($_POST['type'], ['roadmap', 'satellite', 'hybrid', 'terrain']) ? $_POST['type'] : 'roadmap';

        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];

        /* Display settings */
        $this->process_display_settings();

        $biolink_block = $this->biolink_block;

        /* Check for any errors */
        $required_fields = ['address'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url'], true);

        $settings = json_encode([
            'address' => $_POST['address'],
            'markers' => $_POST['markers'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'zoom' => $_POST['zoom'],
            'type' => $_POST['type'],

            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function create_biolink_embeddable($type) {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['theme'] = isset($_POST['theme']) && in_array($_POST['theme'], ['light', 'dark']) ? query_clean($_POST['theme']) : null;
        $_POST['title'] = mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 256);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color'] ?? null) ? '' : $_POST['text_color'];
        $_POST['font_size'] = in_array((int) ($_POST['font_size'] ?? 0), range(12, 40), true) ? (int) $_POST['font_size'] : 0;

        $settings = [
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ];

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $link_settings = is_string($link->settings ?? null) ? json_decode($link->settings ?? '{}') : ($link->settings ?? (object) []);
        if(is_array($link_settings)) {
            $link_settings = (object) $link_settings;
        }

        $default_title_color = !empty($link_settings->text_color) && verify_hex_color($link_settings->text_color) ? $link_settings->text_color : '#F8FAFC';
        $default_title_font_size = in_array((int) (($link_settings->font_size ?? 16) + 4), range(12, 40), true) ? (int) (($link_settings->font_size ?? 16) + 4) : 20;

        if($_POST['theme']) {
            $settings['theme'] = $_POST['theme'];
        }

        if(in_array($type, ['youtube', 'vimeo'], true)) {
            $settings['title'] = $_POST['title'];
            $settings['text_color'] = $_POST['text_color'] ?: $default_title_color;
            $settings['font_size'] = $_POST['font_size'] ?: $default_title_font_size;
        }

        /* Check for any errors */
        $required_fields = ['location_url'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        /* Make sure the location url is valid & get needed details */
        $host = parse_url($_POST['location_url'], PHP_URL_HOST);

        if(isset($this->biolink_blocks[$type]['whitelisted_hosts']) && !in_array($host, $this->biolink_blocks[$type]['whitelisted_hosts'])) {
            Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
        }

        switch($type) {
            case 'tumblr_post':
                try {
                    $response = \Unirest\Request::get(
                        sprintf(
                            'https://www.tumblr.com/oembed/1.0?url=%s',
                            $_POST['location_url']
                        )
                    );
                } catch (\Exception $exception) {
                    Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
                }

                if(!isset($response->body->html)) {
                    Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
                }

                $settings['html'] = $response->body->html;

                break;

            case 'bluesky_post':
                try {
                    $response = \Unirest\Request::get(
                        sprintf(
                            'https://embed.bsky.app/oembed?url=%s',
                            $_POST['location_url']
                        )
                    );
                } catch (\Exception $exception) {
                    Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
                }

                if(!isset($response->body->html)) {
                    Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
                }

                $settings['html'] = $response->body->html;

                break;

            case 'reddit':
                $response = Request::get('https://www.reddit.com/oembed?url=' . $_POST['location_url']);

                if($response->code >= 400) {
                    Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
                }

                $settings['content'] = $response->body->html;
                break;

            case 'youtube':

                $settings['video_autoplay'] = false;
                $settings['video_controls'] = true;
                $settings['video_loop'] = false;
                $settings['video_muted'] = false;

                break;

            case 'mixcloud':

                $settings['type'] = 'classic';

                break;
        }

        $settings = json_encode($settings);

        $settings = $this->process_biolink_theme_id_settings($link, $settings, $type);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_embeddable($type) {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['theme'] = isset($_POST['theme']) && in_array($_POST['theme'], ['light', 'dark']) ? query_clean($_POST['theme']) : null;
        $_POST['title'] = mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 256);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color'] ?? null) ? '#F8FAFC' : $_POST['text_color'];
        $_POST['font_size'] = in_array((int) ($_POST['font_size'] ?? 0), range(12, 40), true) ? (int) $_POST['font_size'] : 20;
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'], ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];

        /* Display settings */
        $this->process_display_settings();

        $settings = [
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ];

        if($_POST['theme']) {
            $settings['theme'] = $_POST['theme'];
        }

        if(in_array($type, ['youtube', 'vimeo'], true)) {
            $settings['title'] = $_POST['title'];
            $settings['text_color'] = $_POST['text_color'];
            $settings['font_size'] = $_POST['font_size'];
        }

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        /* Check for any errors */
        $required_fields = ['location_url'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        /* Make sure the location url is valid & get needed details */
        $host = parse_url($_POST['location_url'], PHP_URL_HOST);

        if(isset($this->biolink_blocks[$type]['whitelisted_hosts']) && !in_array($host, $this->biolink_blocks[$type]['whitelisted_hosts'])) {
            Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
        }

        switch($type) {
            case 'tumblr_post':
                try {
                    $response = \Unirest\Request::get(
                        sprintf(
                            'https://www.tumblr.com/oembed/1.0?url=%s',
                            $_POST['location_url']
                        )
                    );
                } catch (\Exception $exception) {
                    Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
                }

                if(!isset($response->body->html)) {
                    Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
                }

                $settings['html'] = $response->body->html;

                break;

            case 'bluesky_post':
                try {
                    $response = \Unirest\Request::get(
                        sprintf(
                            'https://embed.bsky.app/oembed?url=%s',
                            $_POST['location_url']
                        )
                    );
                } catch (\Exception $exception) {
                    Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
                }

                if(!isset($response->body->html)) {
                    Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
                }

                $settings['html'] = $response->body->html;

                break;

            case 'reddit':
                $response = Request::get('https://www.reddit.com/oembed?url=' . $_POST['location_url']);

                if($response->code >= 400) {
                    Response::json(l('link.error_message.invalid_location_url_embed'), 'error');
                }

                $setting['content'] = $response->body->html;
                break;

            case 'youtube':

                $settings['video_autoplay'] = (int) isset($_POST['video_autoplay']);
                $settings['video_controls'] = (int) isset($_POST['video_controls']);
                $settings['video_loop'] = (int) isset($_POST['video_loop']);
                $settings['video_muted'] = (int) isset($_POST['video_muted']);

                break;

            case 'mixcloud':

                $settings['type'] = isset($_POST['type']) && in_array($_POST['type'], ['picture', 'classic', 'mini']) ? query_clean($_POST['type']) : 'classic';

                break;
        }

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => json_encode($settings),
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json(l('global.success_message.update2'), 'success');
    }

    private function delete() {
        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.biolinks_blocks')) {
            Response::json(l('global.info_message.team_no_access'), 'error');
        }

        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];

        /* Check for possible errors */
        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        (new \Altum\Models\BiolinkBlock())->delete($biolink_block->biolink_block_id);

        Response::json(l('global.success_message.delete2'), 'success', ['url' => url('link/' . $biolink_block->link_id . '?tab=blocks')]);
    }

    public function handle_file_upload($already_existing_file, $file_name, $file_name_remove, $allowed_extensions, $upload_folder, $size_limit) {
        /* File upload */
        $file = (bool) !empty($_FILES[$file_name]['name']) && !isset($_POST[$file_name_remove]);
        $db_file = $already_existing_file;

        if($file) {
            $file_extension = mb_strtolower(pathinfo($_FILES[$file_name]['name'], PATHINFO_EXTENSION));
            $file_temp = $_FILES[$file_name]['tmp_name'];

            if($_FILES[$file_name]['error'] == UPLOAD_ERR_INI_SIZE) {
                Response::json(sprintf(l('global.error_message.file_size_limit'), $size_limit), 'error');
            }

            if($_FILES[$file_name]['error'] && $_FILES[$file_name]['error'] != UPLOAD_ERR_INI_SIZE) {
                Response::json(l('global.error_message.file_upload'), 'error');
            }

            if(!is_writable(UPLOADS_PATH . $upload_folder)) {
                Response::json(sprintf(l('global.error_message.directory_not_writable'), UPLOADS_PATH . $upload_folder), 'error');
            }

            if(!in_array($file_extension, $allowed_extensions)) {
                Response::json(l('global.error_message.invalid_file_type'), 'error');
            }

            if($_FILES[$file_name]['size'] > $size_limit * 1000000) {
                Response::json(sprintf(l('global.error_message.file_size_limit'), $size_limit), 'error');
            }

            /* Generate new name for the file */
            $file_new_name = md5(uniqid('', true) . random_bytes(16)) . '.' . $file_extension;

            /* Try to compress the image */
            if(\Altum\Plugin::is_active('image-optimizer') && settings()->image_optimizer->is_enabled) {
                \Altum\Plugin\ImageOptimizer::optimize($file_temp, $file_new_name, $_FILES[$file_name]['name'], $upload_folder);
            }

            /* Sanitize SVG uploads */
            if($file_extension == 'svg') {
                $svg_sanitizer = new \enshrined\svgSanitize\Sanitizer();
                $dirty_svg = file_get_contents($file_temp);
                $clean_svg = $svg_sanitizer->sanitize($dirty_svg);
                file_put_contents($file_temp, $clean_svg);
            }

            /* Offload uploading */
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                try {
                    $s3 = new \Aws\S3\S3Client(get_aws_s3_config());

                    /* Delete current image */
                    if(!empty($already_existing_file)) {
                        $s3->deleteObject([
                            'Bucket' => settings()->offload->storage_name,
                            'Key' => UPLOADS_URL_PATH . $upload_folder . $already_existing_file,
                        ]);
                    }

                    /* Upload image */
                    $result = $s3->putObject([
                        'Bucket' => settings()->offload->storage_name,
                        'Key' => UPLOADS_URL_PATH . $upload_folder . $file_new_name,
                        'ContentType' => mime_content_type($file_temp),
                        'SourceFile' => $file_temp,
                        'ACL' => 'public-read'
                    ]);
                } catch (\Exception $exception) {
                    Response::json($exception->getMessage(), 'error');
                }
            }

            /* Local uploading */
            else {
                /* Delete current file */
                if(!empty($already_existing_file) && file_exists(UPLOADS_PATH . $upload_folder . $already_existing_file)) {
                    unlink(UPLOADS_PATH . $upload_folder . $already_existing_file);
                }

                /* Upload the original */
                move_uploaded_file($file_temp, UPLOADS_PATH . $upload_folder . $file_new_name);
            }

            $db_file = $file_new_name;
        }

        /* Check for the removal of the already uploaded file */
        if(isset($_POST['image_remove'])) {
            /* Offload deleting */
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                $s3 = new \Aws\S3\S3Client(get_aws_s3_config());
                $s3->deleteObject([
                    'Bucket' => settings()->offload->storage_name,
                    'Key' => UPLOADS_URL_PATH . $upload_folder . $already_existing_file,
                ]);
            }

            /* Local deleting */
            else {
                /* Delete current file */
                if(!empty($db_file) && file_exists(UPLOADS_PATH . $upload_folder . $db_file)) {
                    unlink(UPLOADS_PATH . $upload_folder . $db_file);
                }
            }
            $db_file = null;
        }

        return $db_file;
    }

    private function handle_image_upload($uploaded_image, $upload_folder, $size_limit) {
        return $this->handle_file_upload($uploaded_image, 'image', 'image_remove', ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'], $upload_folder, $size_limit);
    }

    /* Function to bundle together all the checks of an url */
    private function check_location_url($url, $can_be_empty = false) {

        if(empty(trim($url ?? '')) && $can_be_empty) {
            return;
        }

        if(empty(trim($url))) {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        $url_details = parse_url($url);

        if(!isset($url_details['scheme'])) {
            Response::json(l('link.error_message.invalid_location_url'), 'error');
        }

        if(!$this->user->plan_settings->deep_links && !in_array($url_details['scheme'], ['http', 'https'])) {
            Response::json(l('link.error_message.invalid_location_url'), 'error');
        }

        /* Make sure the domain is not blacklisted */
        $domain = get_domain_from_url($url);

        if($domain && in_array($domain, settings()->links->blacklisted_domains)) {
            Response::json(l('link.error_message.blacklisted_domain'), 'error');
        }

        /* Check the url with google safe browsing to make sure it is a safe website */
        if(settings()->links->google_safe_browsing_is_enabled && !$this->is_trusted_forever_url($url)) {
            if(google_safe_browsing_check($url, settings()->links->google_safe_browsing_api_key)) {
                Response::json(l('link.error_message.blacklisted_location_url'), 'error');
            }
        }
    }

    private function is_trusted_forever_url($url) {
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        if(!$host) {
            return false;
        }

        $trusted_hosts = [
            'foreverliving.com',
            'www.foreverliving.com',
            'foreverliving.hr',
            'www.foreverliving.hr',
            'thealoeveraco.shop',
            'www.thealoeveraco.shop',
            'flpshop.ba',
            'www.flpshop.ba',
            'foreveralbania.com',
            'www.foreveralbania.com',
            'webshop.forever.si',
            'businesscard.club',
            'www.businesscard.club',
        ];

        return in_array($host, $trusted_hosts, true);
    }

    private function process_display_settings() {
        $_POST['schedule'] = (int) isset($_POST['schedule']);
        if($_POST['schedule'] && !empty($_POST['start_date']) && !empty($_POST['end_date']) && Date::validate($_POST['start_date'], 'Y-m-d H:i:s') && Date::validate($_POST['end_date'], 'Y-m-d H:i:s')) {
            $_POST['start_date'] = (new \DateTime($_POST['start_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
            $_POST['end_date'] = (new \DateTime($_POST['end_date'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s');
        } else {
            $_POST['start_date'] = $_POST['end_date'] = null;
        }

        $_POST['display_continents'] = array_filter($_POST['display_continents'] ?? [], function($country) {
            return array_key_exists($country, get_continents_array());
        });

        $_POST['display_countries'] = array_filter($_POST['display_countries'] ?? [], function($country) {
            return array_key_exists($country, get_countries_array());
        });

        $_POST['display_cities'] = explode(',', $_POST['display_cities']);
        if(count($_POST['display_cities'])) {
            $_POST['display_cities'] = array_map(function($city) {
                return query_clean($city);
            }, $_POST['display_cities']);

            $_POST['display_cities'] = array_filter($_POST['display_cities'], function($city) {
                return $city !== '';
            });

            $_POST['display_cities'] = array_unique($_POST['display_cities']);
        }

        $_POST['display_devices'] = array_filter($_POST['display_devices'] ?? [], function($device_type) {
            return in_array($device_type, ['desktop', 'tablet', 'mobile']);
        });

        $_POST['display_languages'] = array_filter($_POST['display_languages'] ?? [], function($locale) {
            return array_key_exists($locale, get_locale_languages_array());
        });

        $_POST['display_operating_systems'] = array_filter($_POST['display_operating_systems'] ?? [], function($os_name) {
            return in_array($os_name, ['iOS', 'Android', 'Windows', 'OS X', 'Linux', 'Ubuntu', 'Chrome OS']);
        });

        $_POST['display_browsers'] = array_filter($_POST['display_browsers'] ?? [], function($browser_name) {
            return in_array($browser_name, ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Samsung Internet']);
        });
    }

    private function process_biolink_theme_id_settings($link, $settings, $type) {
        $themable_blocks = [];

        foreach(require APP_PATH . 'includes/biolink_blocks.php' as $key => $value) {
            if($value['themable']) {
                $themable_blocks[] = $key;
            }
        }

        if(!in_array($type, $themable_blocks)) {
            return $settings;
        }

        if(!$link->biolink_theme_id) {
            return $settings;
        }

        /* Get available themes */
        $biolinks_themes = (new BiolinksThemes())->get_biolinks_themes();

        /* Check if we need to override defaults for a new theme */
        $biolink_theme = $biolinks_themes[$link->biolink_theme_id] ?? null;

        if(!$biolink_theme) {
            return $settings;
        }

        if(is_string($settings)) {
            $settings = json_decode($settings);
        }

        /* Save new themed settings */
        switch($type) {
            case 'socials':
                $new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block_socials ?? []));
                break;

            case 'heading':
                $new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block_heading ?? []));
                break;

            case 'paragraph':
                $new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block_paragraph ?? []));
                break;

            case 'counter':
            case 'loading':
                $biolink_theme->settings->biolink_block->number_color = $biolink_theme->settings->biolink_block->text_color;

                $new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block ?? []));
                break;

            case 'external_item':
                $biolink_theme->settings->biolink_block->price_color = $biolink_theme->settings->biolink_block->text_color;
                $biolink_theme->settings->biolink_block->name_color = $biolink_theme->settings->biolink_block->text_color;

                $new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block ?? []));
                break;

            case 'business_hours':
                $biolink_theme->settings->biolink_block->icon_color = $biolink_theme->settings->biolink_block->text_color;

                $new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block ?? []));
                break;


            default:
                $new_settings = json_encode(array_merge((array) $settings, (array) $biolink_theme->settings->biolink_block ?? []));
                break;
        }

        return $new_settings;
    }

    /* Custom code */
    private function get_biolink_full_url(object $link): string {
        if(!empty($link->domain_id)) {
            $domain = (new Domain())->get_domain_by_domain_id($link->domain_id);

            if($domain) {
                return $domain->scheme . $domain->host . '/' . ($domain->link_id == $link->link_id ? null : $link->url);
            }
        }

        return SITE_URL . ($link->url ?? '');
    }

    private function get_app_switcher_user_biolinks(): array {
        $biolinks = db()
            ->where('user_id', $this->user->user_id)
            ->where('type', 'biolink')
            ->orderBy('datetime', 'ASC')
            ->get('links', null, ['link_id', 'domain_id', 'url']);

        $items = [];

        foreach($biolinks as $biolink) {
            $full_url = $this->get_biolink_full_url($biolink);

            $items[] = [
                'key' => (string) $biolink->link_id,
                'name' => $full_url,
                'url' => $full_url,
            ];
        }

        return $items;
    }

    private function get_selected_app_switcher_item(array $apps, string $selected_key): ?array {
        foreach($apps as $app) {
            if(($app['key'] ?? '') === $selected_key) {
                return $app;
            }
        }

        return null;
    }

    private function create_biolink_link_app_switcher() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name'] ?? ''), 0, 128);
        $_POST['selected_app'] = mb_substr(query_clean($_POST['selected_app'] ?? ''), 0, 64);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $apps = $this->get_app_switcher_user_biolinks();
        $selected_app = $this->get_selected_app_switcher_item($apps, $_POST['selected_app']);

        if(empty($_POST['name']) || !$selected_app) {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        $this->check_location_url($selected_app['url']);

        $type = 'link_app_switcher';
        $location_url = $selected_app['url'];
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => 'fas fa-table-cells-large',
            'image' => '',
            'sensitive_content' => false,
            'columns' => '1',
            'apps' => $apps,
            'selected_app' => $selected_app['key'],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $location_url,
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_link_app_switcher() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['name'] = mb_substr(query_clean($_POST['name'] ?? ''), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['selected_app'] = mb_substr(query_clean($_POST['selected_app'] ?? ''), 0, 64);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_color']) ? '#ffffff' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_shadow_color'] ?? '') ? '#00000010' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = in_array((string) ($_POST['columns'] ?? '1'), ['1', '2']) ? (string) $_POST['columns'] : '1';
        $_POST['sensitive_content'] = isset($_POST['sensitive_content']);

        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings ?? '');

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        $apps = $this->get_app_switcher_user_biolinks();

        $selected_app = $this->get_selected_app_switcher_item($apps, $_POST['selected_app']);

        if(empty($_POST['name']) || !$selected_app) {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        $this->check_location_url($selected_app['url']);

        $db_image = $this->handle_image_upload($biolink_block->settings->image ?? null, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        if(isset($_POST['image_remove'])) {
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'sensitive_content' => $_POST['sensitive_content'],
            'columns' => $_POST['columns'],
            'apps' => $apps,
            'selected_app' => $selected_app['key'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $selected_app['url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
            'images' => ['image' => $image_url],
        ]);
    }

    private function create_biolink_link_back() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['name'] = mb_substr(query_clean($_POST['name'] ?? ''), 0, 128);
        $_POST['fallback_url'] = trim((string) ($_POST['fallback_url'] ?? ''));

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $fallback_url = $_POST['fallback_url'] !== '' ? get_url($_POST['fallback_url']) : SITE_URL;

        if(empty($_POST['name'])) {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        $this->check_location_url($fallback_url);

        $type = 'link_back';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => 'fas fa-arrow-left',
            'image' => '',
            'sensitive_content' => false,
            'columns' => '1',
            'fallback_url' => $fallback_url,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $fallback_url,
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_link_back() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['name'] = mb_substr(query_clean($_POST['name'] ?? ''), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['fallback_url'] = trim((string) ($_POST['fallback_url'] ?? ''));
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_color']) ? '#ffffff' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_shadow_color'] ?? '') ? '#00000010' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['columns'] = in_array((string) ($_POST['columns'] ?? '1'), ['1', '2']) ? (string) $_POST['columns'] : '1';
        $_POST['sensitive_content'] = isset($_POST['sensitive_content']);

        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings ?? '');

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        $fallback_url = $_POST['fallback_url'] !== '' ? get_url($_POST['fallback_url']) : SITE_URL;

        if(empty($_POST['name'])) {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        $this->check_location_url($fallback_url);

        $db_image = $this->handle_image_upload($biolink_block->settings->image ?? null, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        if(isset($_POST['image_remove'])) {
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'sensitive_content' => $_POST['sensitive_content'],
            'columns' => $_POST['columns'],
            'fallback_url' => $fallback_url,

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $fallback_url,
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
            'images' => ['image' => $image_url],
        ]);
    }

    private function create_biolink_link_forever_shop() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'link_forever_shop';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => 'black',
            'text_alignment' => 'center',
            'background_color' => '#FFC600',
            'border_shadow_style' => 'subtle',
            'border_shadow_offset_x' => 0,
            'border_shadow_offset_y' => 0,
            'border_shadow_blur' => 20,
            'border_shadow_spread' => 1,
            'border_shadow_color' => '#000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#000000',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',

            /* Display settings */
            'display_countries' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => 'https://www.foreveralbania.com/',
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);        
    }

    private function update_biolink_link_forever_shop() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_offset_x'] = in_array($_POST['border_shadow_offset_x'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_x'] : 0;
        $_POST['border_shadow_offset_y'] = in_array($_POST['border_shadow_offset_y'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_y'] : 0;
        $_POST['border_shadow_blur'] = in_array($_POST['border_shadow_blur'], range(0, 20)) ? (int) $_POST['border_shadow_blur'] : 0;
        $_POST['border_shadow_spread'] = in_array($_POST['border_shadow_spread'], range(0, 10)) ? (int) $_POST['border_shadow_spread'] : 0;
        $_POST['border_shadow_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        $forever_id = '';
        if(isset($this->user->preferences)) {
            $preferences = is_string($this->user->preferences) ? json_decode($this->user->preferences ?? '{}') : $this->user->preferences;
            $meta = $preferences->meta ?? null;

            if(is_string($meta)) {
                $decoded_meta = json_decode($meta);
                if(is_object($decoded_meta) || is_array($decoded_meta)) {
                    $meta = $decoded_meta;
                }
            }

            if(is_array($meta)) {
                $meta = (object) $meta;
            }

            if(is_object($meta)) {
                if(isset($meta->foreverId) && trim((string) $meta->foreverId) !== '') {
                    $forever_id = trim((string) $meta->foreverId);
                } elseif(isset($meta->forever_id) && trim((string) $meta->forever_id) !== '') {
                    $forever_id = trim((string) $meta->forever_id);
                } elseif(isset($meta->foreverID) && trim((string) $meta->foreverID) !== '') {
                    $forever_id = trim((string) $meta->foreverID);
                }
            }
        }

        /* Check for any errors */
        $required_fields = ['location_url', 'name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] != '0')) {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        /* Check for the removal of the already uploaded file */
        if(isset($_POST['image_remove'])) {
            /* Offload deleting */
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                $s3 = new \Aws\S3\S3Client(get_aws_s3_config());
                $s3->deleteObject([
                    'Bucket' => settings()->offload->storage_name,
                    'Key' => 'uploads/block_thumbnail_images/' . $biolink_block->settings->image,
                ]);
            }

            /* Local deleting */
            else {
                /* Delete current file */
                if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                    //unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
                }
            }
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_offset_x' => $_POST['border_shadow_offset_x'],
            'border_shadow_offset_y' => $_POST['border_shadow_offset_y'],
            'border_shadow_blur' => $_POST['border_shadow_blur'],
            'border_shadow_spread' => $_POST['border_shadow_spread'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'forever_id' => $forever_id,

            /* Display settings */
            'display_countries' => $_POST['display_countries'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
            'images' => ['image' => $image_url],
        ]);
    }

    private function create_biolink_link_forever_webshop_reg() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url'] ?? \Altum\Link::get_forever_webshop_registration_base_url('hr'));
        $_POST['name'] = mb_substr(query_clean($_POST['name'] ?? ''), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        if($_POST['name'] === '') {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'link_forever_webshop_reg';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => '#111827',
            'text_alignment' => 'center',
            'background_color' => '#FFC600',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000020',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#FFC600',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => 'fas fa-cart-plus',
            'image' => '',
            'sensitive_content' => false,
            'columns' => 1,

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_link_forever_webshop_reg() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['location_url'] = get_url($_POST['location_url'] ?? \Altum\Link::get_forever_webshop_registration_base_url('hr'));
        $_POST['name'] = mb_substr(query_clean($_POST['name'] ?? ''), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'] ?? null, ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'] ?? null, [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'] ?? null, ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color'] ?? '') ? '#FFC600' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color'] ?? '') ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'] ?? null, require APP_PATH . 'includes/biolink_animations.php') || ($_POST['animation'] ?? null) == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon'] ?? '');
        $_POST['text_color'] = !verify_hex_color($_POST['text_color'] ?? '') ? '#111827' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'] ?? null, ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color'] ?? '') ? '#FFC600' : $_POST['background_color'];
        $_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;

        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        if($_POST['name'] === '') {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }

        $this->check_location_url($_POST['location_url']);

        $db_image = $this->handle_image_upload($biolink_block->settings->image ?? null, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        if(isset($_POST['image_remove'])) {
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                $s3 = new \Aws\S3\S3Client(get_aws_s3_config());
                $s3->deleteObject([
                    'Bucket' => settings()->offload->storage_name,
                    'Key' => 'uploads/block_thumbnail_images/' . ($biolink_block->settings->image ?? null),
                ]);
            }
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'sensitive_content' => $_POST['sensitive_content'],
            'columns' => $_POST['columns'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
            'images' => ['image' => $image_url],
        ]);
    }

    /* Custom code: FC-2026-03-09: Forever product picker block handlers */
    private function create_biolink_link_forever_product() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['product_blog_post_id'] = (int) ($_POST['product_blog_post_id'] ?? 0);
        $_POST['product_translation_key'] = mb_substr(query_clean($_POST['product_translation_key'] ?? ''), 0, 128);
        $_POST['product_language_mode'] = in_array($_POST['product_language_mode'] ?? null, ['app', 'manual'], true) ? query_clean($_POST['product_language_mode']) : 'app';
        $_POST['product_image_url'] = mb_substr(query_clean($_POST['product_image_url'] ?? ''), 0, 2048);
        $available_language_codes = array_values((array) \Altum\Language::$active_languages);
        $_POST['description'] = mb_substr(query_clean($_POST['description'] ?? ''), 0, 220);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $link->settings = json_decode($link->settings ?? '{}');
        $default_language_code = $link->settings->language_code ?? \Altum\Language::$default_code;
        $_POST['product_language_code'] = in_array($_POST['product_language_code'] ?? null, $available_language_codes, true) ? query_clean($_POST['product_language_code']) : $default_language_code;
        $_POST['product_fallback_language_code'] = in_array($_POST['product_fallback_language_code'] ?? null, array_merge($available_language_codes, ['']), true) ? query_clean($_POST['product_fallback_language_code']) : 'hr';

        if($_POST['product_blog_post_id'] && $_POST['product_translation_key'] === '') {
            $blog_post = db()->where('blog_post_id', $_POST['product_blog_post_id'])->getOne('blog_posts', ['url']);
            $_POST['product_translation_key'] = $blog_post->url ?? '';
        }

        $required_fields = ['location_url', 'name'];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] != '0')) {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'link_forever_product';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => null,
            'sensitive_content' => false,
            'columns' => 1,

            'product_blog_post_id' => $_POST['product_blog_post_id'],
            'product_translation_key' => $_POST['product_translation_key'],
            'product_language_mode' => $_POST['product_language_mode'],
            'product_language_code' => $_POST['product_language_code'],
            'product_fallback_language_code' => $_POST['product_fallback_language_code'],
            'product_image_url' => $_POST['product_image_url'],
            'description' => $_POST['description'],

            /* Display settings */
            'display_continents' => [],
            'display_countries' => [],
            'display_cities' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
            'display_browsers' => [],
        ]);

        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => settings()->links->biolinks_new_blocks_position == 'top' ? -$this->total_biolink_blocks : $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_link_forever_product() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'none';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !verify_hex_color($_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#ffffff' : $_POST['background_color'];
        $_POST['sensitive_content'] = (int) isset($_POST['sensitive_content']);
        $_POST['columns'] = isset($_POST['columns']) && in_array($_POST['columns'], [1, 2]) ? (int) $_POST['columns'] : 1;
        $_POST['product_blog_post_id'] = (int) ($_POST['product_blog_post_id'] ?? 0);
        $_POST['product_translation_key'] = mb_substr(query_clean($_POST['product_translation_key'] ?? ''), 0, 128);
        $_POST['product_language_mode'] = in_array($_POST['product_language_mode'] ?? null, ['app', 'manual'], true) ? query_clean($_POST['product_language_mode']) : 'app';
        $_POST['product_image_url'] = mb_substr(query_clean($_POST['product_image_url'] ?? ''), 0, 2048);
        $_POST['description'] = mb_substr(query_clean($_POST['description'] ?? ''), 0, 220);
        $available_language_codes = array_values((array) \Altum\Language::$active_languages);

        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        $link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links', ['settings']);
        $link_settings = $link ? json_decode($link->settings ?? '{}') : new \stdClass();
        $default_language_code = $link_settings->language_code ?? \Altum\Language::$default_code;
        $_POST['product_language_code'] = in_array($_POST['product_language_code'] ?? null, $available_language_codes, true) ? query_clean($_POST['product_language_code']) : $default_language_code;
        $_POST['product_fallback_language_code'] = in_array($_POST['product_fallback_language_code'] ?? null, array_merge($available_language_codes, ['']), true) ? query_clean($_POST['product_fallback_language_code']) : 'hr';

        if($_POST['product_blog_post_id'] && $_POST['product_translation_key'] === '') {
            $blog_post = db()->where('blog_post_id', $_POST['product_blog_post_id'])->getOne('blog_posts', ['url']);
            $_POST['product_translation_key'] = $blog_post->url ?? '';
        }

        $required_fields = ['location_url', 'name'];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] != '0')) {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        $db_image = $this->handle_image_upload($biolink_block->settings->image ?? null, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        if(isset($_POST['image_remove'])) {
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                $s3 = new \Aws\S3\S3Client(get_aws_s3_config());
                $s3->deleteObject([
                    'Bucket' => settings()->offload->storage_name,
                    'Key' => 'uploads/block_thumbnail_images/' . ($biolink_block->settings->image ?? null),
                ]);
            }
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'sensitive_content' => $_POST['sensitive_content'],
            'columns' => $_POST['columns'],

            'product_blog_post_id' => $_POST['product_blog_post_id'],
            'product_translation_key' => $_POST['product_translation_key'],
            'product_language_mode' => $_POST['product_language_mode'],
            'product_language_code' => $_POST['product_language_code'],
            'product_fallback_language_code' => $_POST['product_fallback_language_code'],
            'product_image_url' => $_POST['product_image_url'],
            'description' => $_POST['description'],

            /* Display settings */
            'display_continents' => $_POST['display_continents'],
            'display_countries' => $_POST['display_countries'],
            'display_cities' => $_POST['display_cities'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
            'display_browsers' => $_POST['display_browsers'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
            'images' => ['image' => $image_url],
        ]);
    }
    /* /Custom code: FC-2026-03-09 */

    /* Custom code: FC-2026-03-05: BIH-only forever referral block handlers */
    private function create_biolink_link_forever_living_bih() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'link_forever_living_bih';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => 'black',
            'text_alignment' => 'center',
            'background_color' => '#0f766e',
            'border_shadow_style' => 'subtle',
            'border_shadow_offset_x' => 0,
            'border_shadow_offset_y' => 0,
            'border_shadow_blur' => 20,
            'border_shadow_spread' => 1,
            'border_shadow_color' => '#000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#000000',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',

            /* Display settings */
            'display_countries' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
        ]);

        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_link_forever_living_bih() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_offset_x'] = in_array($_POST['border_shadow_offset_x'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_x'] : 0;
        $_POST['border_shadow_offset_y'] = in_array($_POST['border_shadow_offset_y'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_y'] : 0;
        $_POST['border_shadow_blur'] = in_array($_POST['border_shadow_blur'], range(0, 20)) ? (int) $_POST['border_shadow_blur'] : 0;
        $_POST['border_shadow_spread'] = in_array($_POST['border_shadow_spread'], range(0, 10)) ? (int) $_POST['border_shadow_spread'] : 0;
        $_POST['border_shadow_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings ?? '{}');

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        $required_fields = ['location_url', 'name'];

        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] != '0')) {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        $db_image = $this->handle_image_upload($biolink_block->settings->image ?? null, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        if(isset($_POST['image_remove'])) {
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_offset_x' => $_POST['border_shadow_offset_x'],
            'border_shadow_offset_y' => $_POST['border_shadow_offset_y'],
            'border_shadow_blur' => $_POST['border_shadow_blur'],
            'border_shadow_spread' => $_POST['border_shadow_spread'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,

            /* Display settings */
            'display_countries' => $_POST['display_countries'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
            'images' => ['image' => $image_url],
        ]);
    }

    private function create_biolink_link_forever_living_alb_kosovo() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'link_forever_living_alb_kosovo';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => 'black',
            'text_alignment' => 'center',
            'background_color' => '#16a34a',
            'border_shadow_style' => 'subtle',
            'border_shadow_offset_x' => 0,
            'border_shadow_offset_y' => 0,
            'border_shadow_blur' => 20,
            'border_shadow_spread' => 1,
            'border_shadow_color' => '#000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#000000',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',

            /* Display settings */
            'display_countries' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
        ]);

        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function update_biolink_link_forever_living_alb_kosovo() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_offset_x'] = in_array($_POST['border_shadow_offset_x'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_x'] : 0;
        $_POST['border_shadow_offset_y'] = in_array($_POST['border_shadow_offset_y'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_y'] : 0;
        $_POST['border_shadow_blur'] = in_array($_POST['border_shadow_blur'], range(0, 20)) ? (int) $_POST['border_shadow_blur'] : 0;
        $_POST['border_shadow_spread'] = in_array($_POST['border_shadow_spread'], range(0, 10)) ? (int) $_POST['border_shadow_spread'] : 0;
        $_POST['border_shadow_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings ?? '{}');

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        $required_fields = ['location_url', 'name'];

        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] != '0')) {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        $db_image = $this->handle_image_upload($biolink_block->settings->image ?? null, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        if(isset($_POST['image_remove'])) {
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_offset_x' => $_POST['border_shadow_offset_x'],
            'border_shadow_offset_y' => $_POST['border_shadow_offset_y'],
            'border_shadow_blur' => $_POST['border_shadow_blur'],
            'border_shadow_spread' => $_POST['border_shadow_spread'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,

            /* Display settings */
            'display_countries' => $_POST['display_countries'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
            'images' => ['image' => $image_url],
        ]);
    }

    private function create_biolink_link_forever_living_albania_kosovo() {
        $this->create_biolink_link_forever_living_alb_kosovo();
    }

    private function update_biolink_link_forever_living_albania_kosovo() {
        $this->update_biolink_link_forever_living_alb_kosovo();
    }
    /* /Custom code: FC-2026-03-05 */

    private function create_biolink_link_discount() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['apply_to_all_products'] = isset($_POST['apply_to_all_products']) && in_array($_POST['apply_to_all_products'], ['0', '1']) ? (int) $_POST['apply_to_all_products'] : null;

        /* Custom code: FC-2026-02-26: require explicit apply to all products selection */
        if(is_null($_POST['apply_to_all_products'])) {
            Response::json(l('global.error_message.empty_fields'), 'error');
        }
        /* /Custom code: FC-2026-02-26 */

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);
        $this->validate_forever_discount_url($_POST['location_url']);

        $destination_url = \Altum\Link::decode_discount_link($_POST['location_url']);

        $this->check_location_url($destination_url);

        $type = 'link_discount';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => 'black',
            'text_alignment' => 'center',
            'background_color' => '#FFC600',
            'border_shadow_style' => 'subtle',
            'border_shadow_offset_x' => 0,
            'border_shadow_offset_y' => 0,
            'border_shadow_blur' => 20,
            'border_shadow_spread' => 1,
            'border_shadow_color' => '#000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#000000',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => '',
            'image' => '',
            'decoded_url' => $destination_url,
            'apply_to_all_products' => $_POST['apply_to_all_products'],

            /* Display settings */
            'display_countries' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);        
    }

    private function update_biolink_link_discount() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['apply_to_all_products'] = isset($_POST['apply_to_all_products']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_offset_x'] = in_array($_POST['border_shadow_offset_x'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_x'] : 0;
        $_POST['border_shadow_offset_y'] = in_array($_POST['border_shadow_offset_y'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_y'] : 0;
        $_POST['border_shadow_blur'] = in_array($_POST['border_shadow_blur'], range(0, 20)) ? (int) $_POST['border_shadow_blur'] : 0;
        $_POST['border_shadow_spread'] = in_array($_POST['border_shadow_spread'], range(0, 10)) ? (int) $_POST['border_shadow_spread'] : 0;
        $_POST['border_shadow_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        /* Check for any errors */
        $required_fields = ['location_url', 'name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] != '0')) {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);
        $this->validate_forever_discount_url($_POST['location_url']);

        $destination_url = \Altum\Link::decode_discount_link($_POST['location_url']);

        $this->check_location_url($destination_url);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        /* Check for the removal of the already uploaded file */
        if(isset($_POST['image_remove'])) {
            /* Offload deleting */
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                $s3 = new \Aws\S3\S3Client(get_aws_s3_config());
                $s3->deleteObject([
                    'Bucket' => settings()->offload->storage_name,
                    'Key' => 'uploads/block_thumbnail_images/' . $biolink_block->settings->image,
                ]);
            }

            /* Local deleting */
            else {
                /* Delete current file */
                if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                    //unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
                }
            }
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_offset_x' => $_POST['border_shadow_offset_x'],
            'border_shadow_offset_y' => $_POST['border_shadow_offset_y'],
            'border_shadow_blur' => $_POST['border_shadow_blur'],
            'border_shadow_spread' => $_POST['border_shadow_spread'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,
            'decoded_url' => \Altum\Link::decode_discount_link($_POST['location_url']),
            'apply_to_all_products' => $_POST['apply_to_all_products'],

            /* Display settings */
            'display_countries' => $_POST['display_countries'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
        ]);        
    }

    private function create_biolink_link_homescreen_ios() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'link_homescreen_ios';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => 'black',
            'text_alignment' => 'center',
            'background_color' => '#FFC600',
            'border_shadow_style' => 'subtle',
            'border_shadow_offset_x' => 0,
            'border_shadow_offset_y' => 0,
            'border_shadow_blur' => 20,
            'border_shadow_spread' => 1,
            'border_shadow_color' => '#000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#000000',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => 'fas fa-mobile-alt',
            'image' => '',

            /* Display settings */
            'display_countries' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
            'images' => ['image' => $image_url],
        ]);        
    }

    private function update_biolink_link_homescreen_ios() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_offset_x'] = in_array($_POST['border_shadow_offset_x'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_x'] : 0;
        $_POST['border_shadow_offset_y'] = in_array($_POST['border_shadow_offset_y'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_y'] : 0;
        $_POST['border_shadow_blur'] = in_array($_POST['border_shadow_blur'], range(0, 20)) ? (int) $_POST['border_shadow_blur'] : 0;
        $_POST['border_shadow_spread'] = in_array($_POST['border_shadow_spread'], range(0, 10)) ? (int) $_POST['border_shadow_spread'] : 0;
        $_POST['border_shadow_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        /* Check for any errors */
        $required_fields = ['location_url', 'name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] != '0')) {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        /* Check for the removal of the already uploaded file */
        if(isset($_POST['image_remove'])) {
            /* Offload deleting */
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                $s3 = new \Aws\S3\S3Client(get_aws_s3_config());
                $s3->deleteObject([
                    'Bucket' => settings()->offload->storage_name,
                    'Key' => 'uploads/block_thumbnail_images/' . $biolink_block->settings->image,
                ]);
            }

            /* Local deleting */
            else {
                /* Delete current file */
                if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                    //unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
                }
            }
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_offset_x' => $_POST['border_shadow_offset_x'],
            'border_shadow_offset_y' => $_POST['border_shadow_offset_y'],
            'border_shadow_blur' => $_POST['border_shadow_blur'],
            'border_shadow_spread' => $_POST['border_shadow_spread'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,

            /* Display settings */
            'display_countries' => $_POST['display_countries'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', [
            'url' => url('link/' . $_POST['link_id'] . '?tab=blocks'),
            'images' => ['image' => $image_url],
        ]);        
    }

    private function create_biolink_link_homescreen_android() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'link_homescreen_android';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => 'black',
            'text_alignment' => 'center',
            'background_color' => '#FFC600',
            'border_shadow_style' => 'subtle',
            'border_shadow_offset_x' => 0,
            'border_shadow_offset_y' => 0,
            'border_shadow_blur' => 20,
            'border_shadow_spread' => 1,
            'border_shadow_color' => '#000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#000000',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => 'fas fa-mobile-alt',
            'image' => '',

            /* Display settings */
            'display_countries' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);        
    }

    private function update_biolink_link_homescreen_android() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_offset_x'] = in_array($_POST['border_shadow_offset_x'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_x'] : 0;
        $_POST['border_shadow_offset_y'] = in_array($_POST['border_shadow_offset_y'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_y'] : 0;
        $_POST['border_shadow_blur'] = in_array($_POST['border_shadow_blur'], range(0, 20)) ? (int) $_POST['border_shadow_blur'] : 0;
        $_POST['border_shadow_spread'] = in_array($_POST['border_shadow_spread'], range(0, 10)) ? (int) $_POST['border_shadow_spread'] : 0;
        $_POST['border_shadow_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        /* Check for any errors */
        $required_fields = ['location_url', 'name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] != '0')) {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        /* Check for the removal of the already uploaded file */
        if(isset($_POST['image_remove'])) {
            /* Offload deleting */
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                $s3 = new \Aws\S3\S3Client(get_aws_s3_config());
                $s3->deleteObject([
                    'Bucket' => settings()->offload->storage_name,
                    'Key' => 'uploads/block_thumbnail_images/' . $biolink_block->settings->image,
                ]);
            }

            /* Local deleting */
            else {
                /* Delete current file */
                if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                    //unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
                }
            }
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_offset_x' => $_POST['border_shadow_offset_x'],
            'border_shadow_offset_y' => $_POST['border_shadow_offset_y'],
            'border_shadow_blur' => $_POST['border_shadow_blur'],
            'border_shadow_spread' => $_POST['border_shadow_spread'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,

            /* Display settings */
            'display_countries' => $_POST['display_countries'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);        
    }

    
    private function create_biolink_link_save_contact() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->check_location_url($_POST['location_url']);

        $type = 'link_save_contact';
        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => false,
            'text_color' => 'black',
            'text_alignment' => 'center',
            'background_color' => '#FFC600',
            'border_shadow_style' => 'subtle',
            'border_shadow_offset_x' => 0,
            'border_shadow_offset_y' => 0,
            'border_shadow_blur' => 20,
            'border_shadow_spread' => 1,
            'border_shadow_color' => '#000000',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#000000',
            'border_radius' => 'rounded',
            'animation' => false,
            'animation_runs' => 'repeat-1',
            'icon' => 'far fa-address-book',
            'image' => '',

            /* Display settings */
            'display_countries' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);        
    }

    private function update_biolink_link_save_contact() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['link_id'] = isset($_POST['link_id']) ? (int) $_POST['link_id'] : 0;
        $_POST['location_url'] = get_url($_POST['location_url']);
        $_POST['name'] = mb_substr(query_clean($_POST['name']), 0, 128);
        $_POST['open_in_new_tab'] = isset($_POST['open_in_new_tab']);
        $_POST['border_radius'] = in_array($_POST['border_radius'], ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'], [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'], ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_color']) ? '#000000' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_offset_x'] = in_array($_POST['border_shadow_offset_x'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_x'] : 0;
        $_POST['border_shadow_offset_y'] = in_array($_POST['border_shadow_offset_y'], range(-20, 20)) ? (int) $_POST['border_shadow_offset_y'] : 0;
        $_POST['border_shadow_blur'] = in_array($_POST['border_shadow_blur'], range(0, 20)) ? (int) $_POST['border_shadow_blur'] : 0;
        $_POST['border_shadow_spread'] = in_array($_POST['border_shadow_spread'], range(0, 10)) ? (int) $_POST['border_shadow_spread'] : 0;
        $_POST['border_shadow_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['border_shadow_color']) ? '#000000' : $_POST['border_shadow_color'];
        $_POST['animation'] = in_array($_POST['animation'], require APP_PATH . 'includes/biolink_animations.php') || $_POST['animation'] == 'false' ? query_clean($_POST['animation']) : false;
        $_POST['animation_runs'] = isset($_POST['animation_runs']) && in_array($_POST['animation_runs'], ['repeat-1', 'repeat-2', 'repeat-3', 'infinite']) ? query_clean($_POST['animation_runs']) : false;
        $_POST['icon'] = query_clean($_POST['icon']);
        $_POST['text_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['text_color']) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'], ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? '#ffffff' : $_POST['background_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }
        $biolink_block->settings = json_decode($biolink_block->settings);

        if(!$_POST['link_id'] && isset($biolink_block->link_id)) {
            $_POST['link_id'] = (int) $biolink_block->link_id;
        }

        /* Check for any errors */
        $required_fields = ['location_url', 'name'];

        /* Check for any errors */
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] != '0')) {
                Response::json(l('global.error_message.empty_fields'), 'error');
                break 1;
            }
        }

        $this->check_location_url($_POST['location_url']);

        /* Image upload */
        $db_image = $this->handle_image_upload($biolink_block->settings->image, 'block_thumbnail_images/', settings()->links->thumbnail_image_size_limit);

        /* Check for the removal of the already uploaded file */
        if(isset($_POST['image_remove'])) {
            /* Offload deleting */
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                $s3 = new \Aws\S3\S3Client(get_aws_s3_config());
                $s3->deleteObject([
                    'Bucket' => settings()->offload->storage_name,
                    'Key' => 'uploads/block_thumbnail_images/' . $biolink_block->settings->image,
                ]);
            }

            /* Local deleting */
            else {
                /* Delete current file */
                if(!empty($biolink_block->settings->image) && file_exists(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image)) {
                    //unlink(UPLOADS_PATH . 'block_thumbnail_images/' . $biolink_block->settings->image);
                }
            }
            $db_image = null;
        }

        $image_url = $db_image ? UPLOADS_FULL_URL . 'block_thumbnail_images/' . $db_image : null;

        $settings = json_encode([
            'name' => $_POST['name'],
            'open_in_new_tab' => $_POST['open_in_new_tab'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_offset_x' => $_POST['border_shadow_offset_x'],
            'border_shadow_offset_y' => $_POST['border_shadow_offset_y'],
            'border_shadow_blur' => $_POST['border_shadow_blur'],
            'border_shadow_spread' => $_POST['border_shadow_spread'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'animation' => $_POST['animation'],
            'animation_runs' => $_POST['animation_runs'],
            'icon' => $_POST['icon'],
            'image' => $db_image,

            /* Display settings */
            'display_countries' => $_POST['display_countries'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'location_url' => $_POST['location_url'],
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);        
    }

    private function create_biolink_custom_html_chatbot() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['html'] = mb_substr(trim($_POST['html'] ?? ''), 0, $this->biolink_blocks['custom_html_chatbot']['max_length']);

        if(!fcc_ai_user_has_public_ai_access($this->user)) {
            Response::json(l('global.info_message.plan_feature_no_access'), 'error');
        }

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        /* Custom code: FC-2026-02-27: enforce single ai chatbot block */
        $this->validate_single_ai_chatbot_block_limit((int) $_POST['link_id']);
        /* /Custom code: FC-2026-02-27 */

        $type = 'custom_html_chatbot';
        $settings = json_encode([
            'html' => $_POST['html'],

            /* Display settings */
            'display_countries' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);        
    }

    /* Custom code: FC-2026-02-27: pets ai chatbot block */
    private function create_biolink_custom_html_chatbot_pets() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['html'] = mb_substr(trim($_POST['html'] ?? ''), 0, $this->biolink_blocks['custom_html_chatbot_pets']['max_length']);

        if(!fcc_ai_user_has_public_ai_access($this->user)) {
            Response::json(l('global.info_message.plan_feature_no_access'), 'error');
        }

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $this->validate_single_ai_chatbot_block_limit((int) $_POST['link_id']);

        $type = 'custom_html_chatbot_pets';
        $settings = json_encode([
            'html' => $_POST['html'],

            'display_countries' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
        ]);

        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }
    /* /Custom code: FC-2026-02-27 */

    private function update_biolink_custom_html_chatbot() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['html'] = mb_substr(trim($_POST['html'] ?? ''), 0, $this->biolink_blocks['custom_html_chatbot']['max_length']);

        /* Display settings */
        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'html' => $_POST['html'],

            /* Display settings */
            'display_countries' => $_POST['display_countries'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);        
    }

    /* Custom code: FC-2026-02-27: pets ai chatbot block */
    private function update_biolink_custom_html_chatbot_pets() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['html'] = mb_substr(trim($_POST['html'] ?? ''), 0, $this->biolink_blocks['custom_html_chatbot_pets']['max_length']);

        $this->process_display_settings();

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'html' => $_POST['html'],

            'display_countries' => $_POST['display_countries'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
        ]);

        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);
    }

    private function validate_single_ai_chatbot_block_limit($link_id) {
        $total_ai_blocks = (int) db()
            ->where('link_id', $link_id)
            ->where('user_id', $this->user->user_id)
            ->where('type', ['custom_html_chatbot', 'custom_html_chatbot_pets'], 'IN')
            ->getValue('biolinks_blocks', 'count(*)');

        if($total_ai_blocks >= 1) {
            Response::json(l('create_biolink_custom_html_chatbot_modal.single_ai_limit'), 'error');
        }
    }

    private function is_valid_forever_discount_url($url) {
        $url = mb_strtolower(trim((string) $url));

        return strpos($url, 'https://thealoeveraco.shop/') === 0;
    }

    private function validate_forever_discount_url($url) {
        if(!$this->is_valid_forever_discount_url($url)) {
            Response::json(l('create_biolink_link_discount_modal.error.invalid_forever_url'), 'error');
        }
    }
    /* /Custom code: FC-2026-02-27 */

    private function normalize_whatsapp_phone($phone) {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if(mb_substr($phone, 0, 2) === '00') {
            $phone = mb_substr($phone, 2);
        }

        return $phone;
    }

    private function is_valid_whatsapp_phone($phone) {
        if($phone === '' || !preg_match('/^[1-9][0-9]{7,14}$/', $phone)) {
            return false;
        }

        return true;
    }

    private function extract_user_phone_from_preferences($user) {
        if(!$user) {
            return '';
        }

        $preferences = is_string($user->preferences ?? null) ? json_decode($user->preferences ?? '{}') : ($user->preferences ?? (object) []);
        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        $meta = $preferences->meta ?? (object) [];
        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        return $this->normalize_whatsapp_phone($meta->phone ?? '');
    }

    private function get_default_whatsapp_phone_for_current_user() {
        $referrer_phone = '';
        if(!empty($this->user->referred_by)) {
            $referrer_user = db()->where('user_id', (int) $this->user->referred_by)->getOne('users', ['preferences']);
            $referrer_phone = $this->extract_user_phone_from_preferences($referrer_user);
        }

        if($referrer_phone) {
            return $referrer_phone;
        }

        return $this->extract_user_phone_from_preferences($this->user);
    }

    private function create_biolink_custom_html_whatsapp() {
        $_POST['link_id'] = (int) $_POST['link_id'];
        $_POST['message'] = mb_substr(trim($_POST['message']), 0, $this->biolink_blocks['custom_html_whatsapp']['max_length']);
        $_POST['phone'] = $this->normalize_whatsapp_phone(mb_substr(trim($_POST['phone']), 0, 256));
        $_POST['title'] = mb_substr(trim($_POST['title']), 0, 256);

        if(empty($_POST['phone'])) {
            $_POST['phone'] = $this->get_default_whatsapp_phone_for_current_user();
        }

        if(!$this->is_valid_whatsapp_phone($_POST['phone'])) {
            Response::json(l('create_biolink_custom_html_whatsapp_modal.error.invalid_phone'), 'error');
        }

        if(!$link = db()->where('link_id', $_POST['link_id'])->where('user_id', $this->user->user_id)->getOne('links')) {
            die();
        }

        $type = 'custom_html_whatsapp';
        $settings = json_encode([
            'message' => $_POST['message'],
            'phone' => $_POST['phone'],
            'title' => $_POST['title'],
            'button' => $_POST['title'],
            'text_color' => '#000000',
            'text_alignment' => 'center',
            'background_color' => '#ffffff',
            'border_shadow_style' => 'subtle',
            'border_shadow_color' => '#00000010',
            'border_width' => 0,
            'border_style' => 'solid',
            'border_color' => '#ffffff',
            'border_radius' => 'rounded',
            'icon' => 'fab fa-whatsapp',

            /* Display settings */
            'display_countries' => [],
            'display_devices' => [],
            'display_languages' => [],
            'display_operating_systems' => [],
        ]);

        /* Database query */
        db()->insert('biolinks_blocks', [
            'user_id' => $this->user->user_id,
            'link_id' => $_POST['link_id'],
            'type' => $type,
            'location_url' => null,
            'settings' => $settings,
            'order' => $this->total_biolink_blocks,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $_POST['link_id']);

        Response::json('', 'success', ['url' => url('link/' . $_POST['link_id'] . '?tab=blocks')]);        
    }

    private function update_biolink_custom_html_whatsapp() {
        $_POST['biolink_block_id'] = (int) $_POST['biolink_block_id'];
        $_POST['message'] = mb_substr(trim($_POST['message']), 0, $this->biolink_blocks['custom_html_whatsapp']['max_length']);
        $_POST['phone'] = $this->normalize_whatsapp_phone(mb_substr(trim($_POST['phone']), 0, 256));
        $_POST['title'] = mb_substr(trim($_POST['title']), 0, 256);
        $_POST['border_radius'] = in_array($_POST['border_radius'] ?? null, ['straight', 'round', 'rounded']) ? query_clean($_POST['border_radius']) : 'rounded';
        $_POST['border_width'] = in_array($_POST['border_width'] ?? null, [0, 1, 2, 3, 4, 5]) ? (int) $_POST['border_width'] : 0;
        $_POST['border_style'] = in_array($_POST['border_style'] ?? null, ['solid', 'dashed', 'double', 'inset', 'outset']) ? query_clean($_POST['border_style']) : 'solid';
        $_POST['border_color'] = !verify_hex_color($_POST['border_color'] ?? null) ? '#ffffff' : $_POST['border_color'];
        $_POST['border_shadow_style'] = in_array($_POST['border_shadow_style'] ?? null, ['none', 'subtle', 'strong', 'hard']) ? query_clean($_POST['border_shadow_style']) : 'subtle';
        $_POST['border_shadow_color'] = !verify_hex_color($_POST['border_shadow_color'] ?? null) ? '#00000010' : $_POST['border_shadow_color'];
        $_POST['icon'] = query_clean($_POST['icon'] ?? '');
        $_POST['text_color'] = !verify_hex_color($_POST['text_color'] ?? null) ? '#000000' : $_POST['text_color'];
        $_POST['text_alignment'] = in_array($_POST['text_alignment'] ?? null, ['center', 'left', 'right', 'justify']) ? query_clean($_POST['text_alignment']) : 'center';
        $_POST['background_color'] = !verify_hex_color($_POST['background_color'] ?? null) ? '#ffffff' : $_POST['background_color'];

        /* Display settings */
        $this->process_display_settings();

        if(!$this->is_valid_whatsapp_phone($_POST['phone'])) {
            Response::json(l('create_biolink_custom_html_whatsapp_modal.error.invalid_phone'), 'error');
        }

        if(!$biolink_block = db()->where('biolink_block_id', $_POST['biolink_block_id'])->where('user_id', $this->user->user_id)->getOne('biolinks_blocks')) {
            die();
        }

        $settings = json_encode([
            'message' => $_POST['message'],
            'phone' => $_POST['phone'],
            'title' => $_POST['title'],
            'button' => $_POST['title'],
            'text_color' => $_POST['text_color'],
            'text_alignment' => $_POST['text_alignment'],
            'background_color' => $_POST['background_color'],
            'border_radius' => $_POST['border_radius'],
            'border_width' => $_POST['border_width'],
            'border_style' => $_POST['border_style'],
            'border_color' => $_POST['border_color'],
            'border_shadow_style' => $_POST['border_shadow_style'],
            'border_shadow_color' => $_POST['border_shadow_color'],
            'icon' => $_POST['icon'],

            /* Display settings */
            'display_countries' => $_POST['display_countries'],
            'display_devices' => $_POST['display_devices'],
            'display_languages' => $_POST['display_languages'],
            'display_operating_systems' => $_POST['display_operating_systems'],
        ]);

        /* Database query */
        db()->where('biolink_block_id', $_POST['biolink_block_id'])->update('biolinks_blocks', [
            'settings' => $settings,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
        ]);

        /* Clear the cache */
        cache()->deleteItem('biolink_blocks?link_id=' . $biolink_block->link_id);

        Response::json('', 'success', ['url' => url('link/' . $biolink_block->link_id . '?tab=blocks')]);        
    }
    /* /Custom code */

}
