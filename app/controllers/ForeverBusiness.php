<?php
/* Custom code: FC-2026-08-13: Simple collaborator and manager 4 Core workspace */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Title;

defined('ALTUMCODE') || die();

class ForeverBusiness extends Controller {

    public function index() {
        \Altum\Authentication::guard();
        forever_business_ensure_tables();
        forever_business_provision_fcc_members();
        forever_business_record_page_visit((int) $this->user->user_id);

        $requested_root = forever_business_normalize_fbo_id($_GET['root'] ?? $_POST['root'] ?? '');
        $period = forever_business_period_from_label($_GET['period'] ?? $_POST['period'] ?? '') ?: '';
        $is_admin = (int) ($this->user->type ?? 0) === 1;

        if(!empty($_POST['record_outcome'])) {
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            } else {
                $dashboard = forever_business_get_dashboard((int) $this->user->user_id, $is_admin, $requested_root, $period);
                if(forever_business_record_daily_outcome((int) $this->user->user_id, (string) ($_POST['fbo_id'] ?? ''), $dashboard['scope_ids'], $_POST)) {
                    Alerts::add_success('Aktivnost je spremljena. Manager sada vidi napredak.');
                } else {
                    Alerts::add_error('Aktivnost nije spremljena jer suradnik nije u tvojem dopuštenom timu.');
                }
            }

            $query = array_filter(['period' => $period, 'root' => $requested_root]);
            redirect('forever-business' . (!empty($query) ? '?' . http_build_query($query) : ''));
        }

        $dashboard = forever_business_get_dashboard((int) $this->user->user_id, $is_admin, $requested_root, $period);
        $own_fbo_id = forever_business_extract_user_fbo_id($this->user->preferences ?? null);
        $focus_member = null;

        foreach($dashboard['members'] as $member) {
            if((string) $member['fbo_id'] === $own_fbo_id || (!$focus_member && (string) $member['fbo_id'] === $requested_root)) {
                $focus_member = $member;
                if((string) $member['fbo_id'] === $own_fbo_id) break;
            }
        }
        if(!$focus_member && count($dashboard['members']) === 1) {
            $focus_member = $dashboard['members'][0];
        }

        $focus_members = array_values(array_filter($dashboard['members'], static fn($member) => !empty($member['focus_snapshot_date'])));
        $priority_members = !empty($focus_members) ? $focus_members : $dashboard['members'];
        usort($priority_members, static function($left, $right) {
            $left_focus = !empty($left['focus_snapshot_date']) ? 0 : 1;
            $right_focus = !empty($right['focus_snapshot_date']) ? 0 : 1;
            if($left_focus !== $right_focus) return $left_focus <=> $right_focus;
            $score = static function($member): float {
                $gap = (float) ($member['needed_cc_next_level'] ?? 0);
                if(!empty($member['focus_is_active'])) return 1000 + $gap;
                if(!empty($member['focus_previous_active'])) return 0 + $gap;
                if($gap > 0 && $gap <= 2) return 10 + $gap;
                if((float) ($member['personal_cc'] ?? 0) > 0) return 100 + $gap;
                return 500 + $gap;
            };
            $left_score = $score($left) + ((int) ($left['actions_done_7d'] ?? 0) * 10);
            $right_score = $score($right) + ((int) ($right['actions_done_7d'] ?? 0) * 10);
            return $left_score <=> $right_score ?: strcasecmp((string) $left['name'], (string) $right['name']);
        });

        Title::set('Moj Forever napredak');

        $view = new \Altum\View('forever-business/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'dashboard' => $dashboard,
            'focus_member' => $focus_member,
            'priority_members' => array_slice($priority_members, 0, 100),
            'requested_root' => $requested_root,
            'is_admin' => $is_admin,
        ]));
    }
}

/* /Custom code: FC-2026-08-13 */
