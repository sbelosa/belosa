<?php
/* Custom code: FC-2026-03-19: create reusable live email automations */
namespace Altum\Controllers;

use Altum\Alerts;

defined('ALTUMCODE') || die();

class AdminAutomationCreate extends Controller {

    public function index() {
        fc_ensure_email_automation_tables();
        fc_seed_default_email_automation();

        $plans = (new \Altum\Models\Plan())->get_plans();
        $segment_options = fc_get_email_automation_segment_options();

        if(!empty($_POST)) {
            $_POST['name'] = input_clean($_POST['name'] ?? '', 128);
            $_POST['status'] = in_array($_POST['status'] ?? '', ['active', 'paused']) ? input_clean($_POST['status']) : 'paused';
            $_POST['segment'] = array_key_exists($_POST['segment'] ?? '', $segment_options) ? input_clean($_POST['segment']) : 'all_active_users';
            $_POST['segment_label'] = input_clean($_POST['segment_label'] ?? '', 128);
            $_POST['batch_size'] = max(1, min(200, (int) ($_POST['batch_size'] ?? 20)));
            $_POST['video_url'] = !empty($_POST['video_url']) ? input_clean($_POST['video_url'], 2048) : url('fcc-education');
            $_POST['reentry_is_enabled'] = (int) isset($_POST['reentry_is_enabled']);
            $_POST['exit_when_condition_met'] = (int) isset($_POST['exit_when_condition_met']);
            $_POST['filters_plans'] = array_values(array_unique(array_filter(array_map('strval', $_POST['filters_plans'] ?? []))));
            /* Custom code: FC-2026-03-19: dynamic automation step creation based on visible step cards */
            $_POST['active_steps_count'] = max(1, min(10, (int) ($_POST['active_steps_count'] ?? 1)));
            $submitted_steps = [];

            for($step_order = 1; $step_order <= $_POST['active_steps_count']; $step_order++) {
                $subject = input_clean($_POST['step_subject_' . $step_order] ?? '', 128);
                $content = $_POST['step_content_' . $step_order] ?? '';
                $delay_minutes = max(0, (int) ($_POST['step_delay_minutes_' . $step_order] ?? 0));

                $submitted_steps[$step_order] = [
                    'subject' => $subject,
                    'content' => $content,
                    'prepared_content' => quilljs_to_bootstrap($content),
                    'delay_minutes' => $delay_minutes,
                ];
            }
            /* /Custom code: FC-2026-03-19 */

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if($_POST['name'] === '') {
                Alerts::add_field_error('name', l('global.error_message.empty_field'));
            }

            /* Custom code: FC-2026-03-19: validate all visible automation steps */
            foreach($submitted_steps as $step_order => $submitted_step) {
                if($submitted_step['subject'] === '') {
                    Alerts::add_field_error('step_subject_' . $step_order, l('global.error_message.empty_field'));
                }

                if(trim(strip_tags($submitted_step['content'])) === '') {
                    Alerts::add_field_error('step_content_' . $step_order, l('global.error_message.empty_field'));
                }
            }
            /* /Custom code: FC-2026-03-19 */

            if($_POST['segment'] === 'plan_users' && empty($_POST['filters_plans'])) {
                Alerts::add_field_error('segment', l('global.error_message.empty_field'));
            }

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                $settings = [
                    'batch_size' => $_POST['batch_size'],
                    'segment_label' => $_POST['segment_label'] ?: fc_get_email_automation_segment_label($_POST['segment'], (object) ['segment_label' => '', 'filters_plans' => $_POST['filters_plans']]),
                    'exit_when_condition_met' => $_POST['exit_when_condition_met'],
                    'reentry_is_enabled' => $_POST['reentry_is_enabled'],
                    'video_url' => $_POST['video_url'],
                    'template_version' => 4,
                    'filters_plans' => $_POST['filters_plans'],
                ];

                $automation_id = db()->insert('email_automations', [
                    'name' => $_POST['name'],
                    'segment' => $_POST['segment'],
                    'status' => $_POST['status'],
                    'settings' => json_encode($settings),
                    'datetime' => get_date(),
                    'last_datetime' => get_date(),
                ]);

                /* Custom code: FC-2026-03-19: create automation steps from the submitted step cards */
                foreach($submitted_steps as $step_order => $submitted_step) {
                    db()->insert('email_automation_steps', [
                        'automation_id' => $automation_id,
                        'step_order' => $step_order,
                        'subject' => $submitted_step['subject'],
                        'content' => $submitted_step['prepared_content'],
                        'delay_minutes' => $submitted_step['delay_minutes'],
                        'settings' => json_encode([]),
                        'datetime' => get_date(),
                        'last_datetime' => get_date(),
                    ]);
                }
                /* /Custom code: FC-2026-03-19 */

                Alerts::add_success(sprintf(l('global.success_message.create1'), '<strong>' . $_POST['name'] . '</strong>'));
                redirect('admin/automation-update/' . $automation_id);
            }
        }

        $view = new \Altum\View('admin/automation-create/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'plans' => $plans,
            'segment_options' => $segment_options,
            'values' => [
                'name' => $_POST['name'] ?? generate_prefilled_dynamic_names('Nova automatizacija'),
                'status' => $_POST['status'] ?? 'paused',
                'segment' => $_POST['segment'] ?? 'all_active_users',
                'segment_label' => $_POST['segment_label'] ?? '',
                'batch_size' => $_POST['batch_size'] ?? max(1, min(200, (int) (settings()->content->broadcasts_emails_per_cron ?? 20))),
                'video_url' => $_POST['video_url'] ?? url('fcc-education'),
                'reentry_is_enabled' => $_POST['reentry_is_enabled'] ?? 1,
                'exit_when_condition_met' => $_POST['exit_when_condition_met'] ?? 1,
                'filters_plans' => $_POST['filters_plans'] ?? [],
                /* Custom code: FC-2026-03-19: prefill visible automation steps for dynamic UI */
                'active_steps_count' => $_POST['active_steps_count'] ?? 1,
                'steps' => array_map(static function($step_order) {
                    return [
                        'step_order' => $step_order,
                        'subject' => $_POST['step_subject_' . $step_order] ?? '',
                        'content' => $_POST['step_content_' . $step_order] ?? ($step_order === 1 ? '<p>Upiši prvi mail ove automatizacije.</p>' : '<p>Upiši sadržaj za korak ' . $step_order . '.</p>'),
                        'delay_minutes' => $_POST['step_delay_minutes_' . $step_order] ?? ($step_order === 1 ? 0 : (($step_order - 1) * 1440)),
                    ];
                }, range(1, 10)),
                /* /Custom code: FC-2026-03-19 */
            ],
        ]));
    }
}
/* /Custom code: FC-2026-03-19 */