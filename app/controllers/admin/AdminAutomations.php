<?php
/* Custom code: FC-2026-03-18: admin live email automations index */
namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class AdminAutomations extends Controller {

    public function index() {
        fc_ensure_email_automation_tables();
        fc_seed_default_email_automation();

        $automations = db()->orderBy('automation_id', 'ASC')->get('email_automations') ?? [];
        $now = get_date();

        foreach($automations as $automation) {
            $automation->settings = fc_get_email_automation_settings($automation->settings ?? null);
            $automation->segment_count = fc_get_automation_segment_count($automation->segment);
            $automation->steps_total = (int) db()->where('automation_id', $automation->automation_id)->getValue('email_automation_steps', 'COUNT(*)');
            $automation->active_enrollments = (int) db()->where('automation_id', $automation->automation_id)->where('status', 'active')->getValue('email_automation_enrollments', 'COUNT(*)');
            $automation->completed_enrollments = (int) db()->where('automation_id', $automation->automation_id)->where('status', 'completed')->getValue('email_automation_enrollments', 'COUNT(*)');
            $automation->exited_enrollments = (int) db()->where('automation_id', $automation->automation_id)->where('status', 'exited')->getValue('email_automation_enrollments', 'COUNT(*)');
            $automation->due_enrollments = (int) db()->where('automation_id', $automation->automation_id)->where('status', 'active')->where('next_action_datetime', $now, '<=')->getValue('email_automation_enrollments', 'COUNT(*)');
        }

        $view = new \Altum\View('admin/automations/index', (array) $this);
        $this->add_view_content('content', $view->run(['automations' => $automations]));
    }
}
/* /Custom code: FC-2026-03-18 */