<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Meta;
use Altum\Title;

defined('ALTUMCODE') || die();

class VipFunnel extends Controller {

    public function index() {
        $user_id = (int) ($this->params[0] ?? 0);
        $requested_slug = query_clean((string) ($this->params[1] ?? ''));
        $requested_step_id = input_clean((string) ($_GET['step'] ?? ''), 128);
        $state = vip_funnel_get_public_step_state($user_id, $requested_step_id, $requested_slug);

        if(!$state) {
            throw_404();
        }

        vip_funnel_apply_owner_referral_cookies($user_id);

        if($requested_slug === '' || $requested_slug !== ($state['slug'] ?? '')) {
            header('Location: ' . $state['canonical_url']);
            die();
        }

        if(!empty($_POST)) {
            if(!empty($_POST['vf_track_event'])) {
                vip_funnel_process_public_tracking($state, $_POST);
                http_response_code(204);
                die();
            }

            $submission = vip_funnel_process_public_submission($state, $_POST);

            if(!empty($submission['success']) && !empty($submission['redirect_url'])) {
                header('Location: ' . $submission['redirect_url']);
                die();
            }

            if(empty($submission['success'])) {
                Alerts::add_error($submission['message'] ?? l('global.error_message.basic'));
            }
        }

        $run_id = vip_funnel_get_or_create_public_run($state);
        vip_funnel_log_public_event($state, 'view', (string) ($state['page_key'] ?? 'landing'), [], 0, $run_id);
        vip_funnel_log_public_block_views($state, $run_id);

        $payload = $state['payload'] ?? [];
        $title = trim((string) ($payload['overview']['headline'] ?? ($payload['funnel']['name'] ?? 'VIP Funnel 2.0')));
        $description = trim((string) ($state['active']['summary'] ?? ($payload['overview']['subheadline'] ?? '')));

        Title::set($title !== '' ? $title : 'VIP Funnel 2.0');
        Meta::set_description(string_truncate($description !== '' ? $description : $title, 160));
        Meta::set_canonical_url($state['canonical_url']);

        $view = new \Altum\View('vip-funnel-public/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'state' => $state,
        ]));
    }
}
