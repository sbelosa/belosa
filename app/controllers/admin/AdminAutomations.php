<?php
/* Custom code: FC-2026-03-18: admin live email automations index */
namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class AdminAutomations extends Controller {

    public function index() {
        fc_ensure_email_automation_tables();
        fc_seed_default_email_automation();

        $hub = fc_get_email_hub_analytics();

        /* Custom code: FC-2026-03-19: limit broadcast list size on automations hub */
        $broadcasts_total = (int) db()->getValue('broadcasts', 'COUNT(*)');
        $broadcasts_default_display_limit = 5;
        $show_all_broadcasts = isset($_GET['show_all_broadcasts']) && $_GET['show_all_broadcasts'] === '1';
        $broadcasts_display_limit = $show_all_broadcasts ? $broadcasts_total : $broadcasts_default_display_limit;
        $broadcasts = db()->orderBy('broadcast_id', 'DESC')->get('broadcasts', $broadcasts_display_limit ?: 1) ?? [];
        /* /Custom code: FC-2026-03-19 */

        foreach($broadcasts as $broadcast) {
            $broadcast->settings = json_decode($broadcast->settings ?? '{}');
            $broadcast->analytics = fc_get_email_resource_analytics('broadcast', (int) $broadcast->broadcast_id);
        }

        $automations = db()->orderBy('automation_id', 'ASC')->get('email_automations') ?? [];
        $now = get_date();

        foreach($automations as $automation) {
            $automation->settings = fc_get_email_automation_settings($automation->settings ?? null);
            $automation->segment_count = fc_get_automation_segment_count($automation->segment, $automation->settings);
            $automation->segment_label = fc_get_email_automation_segment_label($automation->segment, $automation->settings);
            $automation->steps_total = (int) db()->where('automation_id', $automation->automation_id)->getValue('email_automation_steps', 'COUNT(*)');
            $automation->active_enrollments = (int) db()->where('automation_id', $automation->automation_id)->where('status', 'active')->getValue('email_automation_enrollments', 'COUNT(*)');
            $automation->completed_enrollments = (int) db()->where('automation_id', $automation->automation_id)->where('status', 'completed')->getValue('email_automation_enrollments', 'COUNT(*)');
            $automation->exited_enrollments = (int) db()->where('automation_id', $automation->automation_id)->where('status', 'exited')->getValue('email_automation_enrollments', 'COUNT(*)');
            $automation->due_enrollments = (int) db()->where('automation_id', $automation->automation_id)->where('status', 'active')->where('next_action_datetime', $now, '<=')->getValue('email_automation_enrollments', 'COUNT(*)');

            /* Custom code: FC-2026-03-19: show Brevo funnel stats on the automation list */
            $automation->analytics = fc_get_email_automation_analytics((int) $automation->automation_id);
            /* /Custom code: FC-2026-03-19 */
        }

        $view = new \Altum\View('admin/automations/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'automations' => $automations,
            'broadcasts' => $broadcasts,
            'broadcasts_total' => $broadcasts_total,
            'broadcasts_display_limit' => $broadcasts_display_limit,
            'broadcasts_default_display_limit' => $broadcasts_default_display_limit,
            'show_all_broadcasts' => $show_all_broadcasts,
            'hub' => $hub,
        ]));
    }
}
/* /Custom code: FC-2026-03-18 */