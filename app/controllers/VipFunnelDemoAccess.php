<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Title;

defined('ALTUMCODE') || die();

class VipFunnelDemoAccess extends Controller {

    public function index() {
        \Altum\Authentication::guard();

        if(vip_funnel_demo_render_locked_route($this, $this->user, 'vip_funnel', [
            'back_url' => url('dashboard'),
        ])) {
            return;
        }

        $access = vip_funnel_resolve_access_state($this->user);

        if(!$access->can_access) {
            Alerts::add_info($access->locked_copy->message ?? l('global.info_message.plan_feature_no_access'));
            redirect('vip-funnel-studio');
        }

        Title::set(l('vip_funnel.demo.title'));

        $dashboard = vip_funnel_demo_get_dashboard($this->user);
        $request_form = $dashboard['default_request_form'];

        if(!empty($_POST)) {
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            } elseif(!$dashboard['schema_ready']) {
                Alerts::add_error(l('vip_funnel.demo.alert.schema_missing'));
            } else {
                $form_input = [
                    'lead_name' => trim((string) ($_POST['lead_name'] ?? '')),
                    'lead_email' => trim((string) ($_POST['lead_email'] ?? '')),
                    'demo_login_email' => trim((string) ($_POST['demo_login_email'] ?? '')),
                    'lead_phone' => trim((string) ($_POST['lead_phone'] ?? '')),
                    'forever_id' => trim((string) ($_POST['forever_id'] ?? '')),
                    'interest_type' => trim((string) ($_POST['interest_type'] ?? 'demo')),
                    'business_readiness' => trim((string) ($_POST['business_readiness'] ?? 'curious')),
                    'product_goal' => trim((string) ($_POST['product_goal'] ?? '')),
                    'owner_user_id' => (int) ($_POST['owner_user_id'] ?? 0),
                    'source' => trim((string) ($_POST['source'] ?? 'manual_pilot')),
                    'notes' => trim((string) ($_POST['notes'] ?? '')),
                ];
                $request_form = array_merge($request_form, $form_input);

                if(isset($_POST['create_vip_demo_request'])) {
                    $result = vip_funnel_demo_create_request($this->user, $form_input);

                    if($result['success']) {
                        Alerts::add_success($result['message']);
                        redirect('vip-funnel-demo-access');
                    }

                    Alerts::add_error($result['message']);
                } else {
                    $account_id = (int) ($_POST['vip_demo_account_id'] ?? 0);
                    $action = trim((string) ($_POST['vip_demo_action'] ?? ''));
                    $result = vip_funnel_demo_apply_action($this->user, $account_id, $action);

                    if($result['success']) {
                        Alerts::add_success($result['message']);
                    } else {
                        Alerts::add_error($result['message']);
                    }

                    redirect('vip-funnel-demo-access');
                }
            }

            $dashboard = vip_funnel_demo_get_dashboard($this->user);
        }

        $view = new \Altum\View('vip-funnel-demo-access/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'access' => $access,
            'studio_url' => url('vip-funnel-studio'),
            'dashboard' => $dashboard,
            'request_form' => $request_form,
        ]));
    }
}
