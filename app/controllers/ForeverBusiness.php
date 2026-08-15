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
        $vip_program = forever_business_get_vip_program_state((int) $this->user->user_id);

        /* Admin-only visual states make it possible to verify the collaborator
         * launch component without exposing another member's private data. */
        $vip_preview = $is_admin ? trim((string) ($_GET['vip_preview'] ?? '')) : '';
        if(in_array($vip_preview, ['qualified', 'pending', 'active'], true)) {
            $preview_now = $vip_preview === 'active'
                ? new \DateTimeImmutable('2026-09-01 09:00:00', new \DateTimeZone('Europe/Zagreb'))
                : null;
            $vip_program = forever_business_build_vip_program_state($vip_preview === 'pending' ? 0.125 : 0.510, $preview_now);
            $vip_program['has_linked_id'] = true;
            $vip_program['fbo_id'] = '';
            $vip_program['is_admin_preview'] = true;
            $vip_program['preview_mode'] = $vip_preview;
            if($vip_preview === 'active') {
                $preview_member = [
                    'personal_cc' => .510,
                    'previous_personal_cc' => 0,
                    'is_manager' => 0,
                    'focus_previous_active' => 0,
                    'vip_highest_track_rank' => 0,
                    'verified_progress' => [
                        'rank' => ['mode' => 'qualification'],
                        'is_officially_active' => false,
                    ],
                ];
                $vip_program['preview_action'] = forever_business_get_action($preview_member, null, 0, false, $preview_now);
                $vip_program['preview_action']['can_complete'] = false;
                $vip_program['preview_action']['is_preview'] = true;
            }
        }

        if(!empty($_POST['record_outcome'])) {
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            } elseif(!$is_admin && empty($vip_program['can_access_education'])) {
                Alerts::add_error(empty($vip_program['is_launched'])
                    ? 'Vođena edukacija počinje 1. rujna. Tvoj će se sljedeći korak tada otvoriti automatski ako ispunjavaš uvjet za pristup.'
                    : 'Tvoj pristup edukaciji još nije aktivan. Na stranici Moj Forever možeš provjeriti uvjet i trenutačni napredak.');
            } else {
                $dashboard = forever_business_get_dashboard((int) $this->user->user_id, $is_admin, $requested_root, $period);
                $submitted_fbo_id = forever_business_normalize_fbo_id($_POST['fbo_id'] ?? '');
                $target_member = null;
                foreach($dashboard['members'] as $member) {
                    if((string) ($member['fbo_id'] ?? '') === $submitted_fbo_id) {
                        $target_member = $member;
                        break;
                    }
                }
                $expected_action = $target_member['next_action'] ?? null;
                $matches_visible_action = $expected_action
                    && !empty($expected_action['can_complete'])
                    && hash_equals((string) ($expected_action['key'] ?? ''), (string) ($_POST['action_key'] ?? ''))
                    && hash_equals((string) ($expected_action['core'] ?? ''), (string) ($_POST['core_key'] ?? ''));

                if(!$matches_visible_action) {
                    Alerts::add_error('Ovaj se korak u međuvremenu promijenio. Stranica je osvježena i prikazan je tvoj trenutačni zadatak.');
                } elseif(forever_business_record_daily_outcome((int) $this->user->user_id, $submitted_fbo_id, $dashboard['scope_ids'], $_POST)) {
                    Alerts::add_success('Korak je dovršen. Tvoj sljedeći konkretan korak je spreman.');
                } else {
                    Alerts::add_error('Aktivnost nije spremljena jer Forever ID nije povezan s tvojim računom.');
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
            'priority_members' => $priority_members,
            'requested_root' => $requested_root,
            'is_admin' => $is_admin,
            'vip_program' => $vip_program,
        ]));
    }
}

/* /Custom code: FC-2026-08-13 */
