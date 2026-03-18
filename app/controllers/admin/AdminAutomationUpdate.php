<?php
/* Custom code: FC-2026-03-18: admin live email automation update */
namespace Altum\Controllers;

use Altum\Alerts;

defined('ALTUMCODE') || die();

class AdminAutomationUpdate extends Controller {

    public function index() {
        fc_ensure_email_automation_tables();
        fc_seed_default_email_automation();

        $automation_id = isset($this->params[0]) ? (int) $this->params[0] : 0;
        $automation = db()->where('automation_id', $automation_id)->getOne('email_automations');

        if(!$automation) {
            redirect('admin/automations');
        }

        $steps = fc_get_email_automation_steps((int) $automation->automation_id);

        if(!empty($_POST)) {
            $_POST['name'] = input_clean($_POST['name'] ?? '', 128);
            $_POST['status'] = in_array($_POST['status'] ?? '', ['active', 'paused']) ? input_clean($_POST['status']) : 'paused';
            $_POST['batch_size'] = max(1, min(200, (int) ($_POST['batch_size'] ?? 20)));
            $_POST['video_url'] = !empty($_POST['video_url']) ? input_clean($_POST['video_url'], 2048) : url('fcc-education');

            $submitted_steps = [];

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            foreach($steps as $step) {
                $subject_key = 'subject_' . $step->automation_step_id;
                $content_key = 'content_' . $step->automation_step_id;
                $delay_key = 'delay_minutes_' . $step->automation_step_id;

                $_POST[$subject_key] = input_clean($_POST[$subject_key] ?? '', 128);
                $_POST[$content_key] = $_POST[$content_key] ?? '';
                $_POST[$delay_key] = max(0, (int) ($_POST[$delay_key] ?? 0));

                $submitted_steps[(int) $step->automation_step_id] = [
                    'step' => $step,
                    'subject_key' => $subject_key,
                    'content_key' => $content_key,
                    'delay_key' => $delay_key,
                    'subject' => $_POST[$subject_key],
                    'content' => $_POST[$content_key],
                    'delay_minutes' => $_POST[$delay_key],
                ];
            }

            if(isset($_POST['preview']) || isset($_POST['preview_all'])) {
                $_POST['preview_step_id'] = (int) ($_POST['preview_step_id'] ?? 0);
                $_POST['preview_email'] = mb_substr(filter_var($_POST['preview_email'] ?? '', FILTER_SANITIZE_EMAIL), 0, 320);
                $is_preview_all = isset($_POST['preview_all']);

                if(!$is_preview_all && !isset($submitted_steps[$_POST['preview_step_id']])) {
                    Alerts::add_error(l('admin_automation_update.error_message.invalid_preview_step'));
                }

                if(!$_POST['preview_email']) {
                    Alerts::add_field_error('preview_email', l('global.error_message.empty_field'));
                } elseif(filter_var($_POST['preview_email'], FILTER_VALIDATE_EMAIL) == false) {
                    Alerts::add_field_error('preview_email', l('global.error_message.invalid_email'));
                }

                if($is_preview_all) {
                    foreach($submitted_steps as $submitted_step) {
                        if($submitted_step['subject'] === '') {
                            Alerts::add_field_error($submitted_step['subject_key'], l('global.error_message.empty_field'));
                        }

                        if(trim($submitted_step['content']) === '') {
                            Alerts::add_field_error($submitted_step['content_key'], l('global.error_message.empty_field'));
                        }
                    }
                }

                if(!$is_preview_all && isset($submitted_steps[$_POST['preview_step_id']])) {
                    $preview_step = $submitted_steps[$_POST['preview_step_id']];

                    if($preview_step['subject'] === '') {
                        Alerts::add_field_error($preview_step['subject_key'], l('global.error_message.empty_field'));
                    }

                    if(trim($preview_step['content']) === '') {
                        Alerts::add_field_error($preview_step['content_key'], l('global.error_message.empty_field'));
                    }
                }

                if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                    $preview_settings = fc_get_email_automation_settings(array_merge((array) fc_get_email_automation_settings($automation->settings ?? null), [
                        'video_url' => $_POST['video_url'],
                    ]));
                    $vars = fc_get_email_automation_user_variables($this->user, $preview_settings);

                    if($is_preview_all) {
                        foreach($submitted_steps as $submitted_step) {
                            $email_template = get_email_template(
                                $vars,
                                htmlspecialchars_decode($submitted_step['subject']),
                                $vars,
                                fc_append_email_automation_footer($submitted_step['content'])
                            );

                            /* Custom code: FC-2026-03-18: avoid recipient reply-to on automation previews */
                            send_automation_mail($_POST['preview_email'], $email_template->subject, $email_template->body, ['is_system_email' => true, 'anti_phishing_code' => $this->user->anti_phishing_code, 'language' => $this->user->language]);
                            /* /Custom code: FC-2026-03-18 */
                        }

                        Alerts::add_success(sprintf(l('admin_automation_update.success_message.preview_all'), nr(count($submitted_steps)), '<strong>' . $_POST['preview_email'] . '</strong>'));
                    } else {
                        $preview_step = $submitted_steps[$_POST['preview_step_id']];
                        $email_template = get_email_template(
                            $vars,
                            htmlspecialchars_decode($preview_step['subject']),
                            $vars,
                            fc_append_email_automation_footer($preview_step['content'])
                        );

                        /* Custom code: FC-2026-03-18: avoid recipient reply-to on automation previews */
                        send_automation_mail($_POST['preview_email'], $email_template->subject, $email_template->body, ['is_system_email' => true, 'anti_phishing_code' => $this->user->anti_phishing_code, 'language' => $this->user->language]);
                        /* /Custom code: FC-2026-03-18 */

                        Alerts::add_success(sprintf(l('admin_automation_update.success_message.preview'), '<strong>' . $_POST['preview_email'] . '</strong>'));
                    }
                }
            }

            else {
                if($_POST['name'] === '') {
                    Alerts::add_field_error('name', l('global.error_message.empty_field'));
                }

                foreach($submitted_steps as $submitted_step) {
                    if($submitted_step['subject'] === '') {
                        Alerts::add_field_error($submitted_step['subject_key'], l('global.error_message.empty_field'));
                    }

                    if(trim($submitted_step['content']) === '') {
                        Alerts::add_field_error($submitted_step['content_key'], l('global.error_message.empty_field'));
                    }
                }
            }

            if(!isset($_POST['preview']) && !isset($_POST['preview_all']) && !Alerts::has_field_errors() && !Alerts::has_errors()) {
                $settings = fc_get_email_automation_settings($automation->settings ?? null);
                $settings->batch_size = $_POST['batch_size'];
                $settings->video_url = $_POST['video_url'];
                $settings->template_version = max(2, (int) ($settings->template_version ?? 0));

                db()->where('automation_id', $automation->automation_id)->update('email_automations', [
                    'name' => $_POST['name'],
                    'status' => $_POST['status'],
                    'settings' => json_encode($settings),
                    'last_datetime' => get_date(),
                ]);

                foreach($submitted_steps as $automation_step_id => $submitted_step) {
                    db()->where('automation_step_id', $automation_step_id)->update('email_automation_steps', [
                        'subject' => $submitted_step['subject'],
                        'content' => $submitted_step['content'],
                        'delay_minutes' => $submitted_step['delay_minutes'],
                        'last_datetime' => get_date(),
                    ]);
                }

                Alerts::add_success(l('admin_automation_update.success_message'));
                redirect('admin/automation-update/' . $automation->automation_id);
            }
        }

        $automation = db()->where('automation_id', $automation->automation_id)->getOne('email_automations');
        $automation->settings = fc_get_email_automation_settings($automation->settings ?? null);
        $steps = fc_get_email_automation_steps((int) $automation->automation_id);
        $now = get_date();

        $stats = [
            'segment_count' => fc_get_automation_segment_count($automation->segment),
            'steps_total' => count($steps),
            'active_enrollments' => (int) db()->where('automation_id', $automation->automation_id)->where('status', 'active')->getValue('email_automation_enrollments', 'COUNT(*)'),
            'completed_enrollments' => (int) db()->where('automation_id', $automation->automation_id)->where('status', 'completed')->getValue('email_automation_enrollments', 'COUNT(*)'),
            'exited_enrollments' => (int) db()->where('automation_id', $automation->automation_id)->where('status', 'exited')->getValue('email_automation_enrollments', 'COUNT(*)'),
            'due_enrollments' => (int) db()->where('automation_id', $automation->automation_id)->where('status', 'active')->where('next_action_datetime', $now, '<=')->getValue('email_automation_enrollments', 'COUNT(*)'),
        ];

        $logs = db()->where('automation_id', $automation->automation_id)->orderBy('automation_log_id', 'DESC')->get('email_automation_logs', 15) ?? [];
        foreach($logs as $log) {
            $log->details = json_decode($log->details ?? '');
        }

        /* Custom code: FC-2026-03-18: hydrate activity logs with user name and email */
        $log_user_ids = array_values(array_unique(array_filter(array_map(static function($log) {
            return (int) ($log->user_id ?? 0);
        }, $logs))));
        $log_users = [];

        if(!empty($log_user_ids)) {
            $log_users_result = db()->where('user_id', $log_user_ids, 'IN')->get('users', null, ['user_id', 'name', 'email']) ?? [];

            foreach($log_users_result as $log_user) {
                $log_users[(int) $log_user->user_id] = $log_user;
            }
        }

        foreach($logs as $log) {
            $log->user = $log_users[(int) ($log->user_id ?? 0)] ?? null;
        }
        /* /Custom code: FC-2026-03-18 */

        $view = new \Altum\View('admin/automation-update/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'automation' => $automation,
            'steps' => $steps,
            'stats' => $stats,
            'logs' => $logs,
            'preview_email' => $_POST['preview_email'] ?? $this->user->email,
            'preview_step_id' => (int) ($_POST['preview_step_id'] ?? ($steps[0]->automation_step_id ?? 0)),
            'video_url' => $_POST['video_url'] ?? $automation->settings->video_url,
        ]));
    }
}
/* /Custom code: FC-2026-03-18 */