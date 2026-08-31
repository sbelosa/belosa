<?php
/* Custom code: FC-2026-08-13: Simple collaborator and manager 4 Core workspace */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Title;

defined('ALTUMCODE') || die();

class ForeverBusiness extends Controller {

    public function index() {
        \Altum\Authentication::guard();
        $request_now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Zagreb'));
        forever_business_ensure_tables();
        forever_business_provision_fcc_members((int) $this->user->user_id);
        forever_business_record_page_visit((int) $this->user->user_id, $request_now);

        $requested_root = forever_business_normalize_fbo_id($_GET['root'] ?? $_POST['root'] ?? '');
        $period = forever_business_period_from_label($_GET['period'] ?? $_POST['period'] ?? '') ?: '';
        $is_admin = (int) ($this->user->type ?? 0) === 1;
        /* The admin's Moj Forever page is now a personal Leader workspace.
         * Team/root exploration belongs to the read-only LOS analytics route. */
        /* This member page is always personal. Root/team exploration belongs
         * to the read-only LOS route for admins and must never influence a
         * participant's per-account task sequence. */
        $requested_root = '';
        $vip_program = forever_business_get_vip_program_state((int) $this->user->user_id, $request_now);

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
                    'force_vip_leader' => true,
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

        if(!empty($_POST['record_outcome']) || !empty($_POST['request_vip_help'])) {
            $is_help_request = !empty($_POST['request_vip_help']);
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            } elseif(empty($vip_program['can_access_education']) || !empty($vip_program['is_admin_preview'])) {
                Alerts::add_error(empty($vip_program['is_launched'])
                    ? 'Vođena edukacija počinje 1. rujna. Tvoj će se sljedeći korak tada otvoriti automatski ako ispunjavaš uvjet za pristup.'
                    : 'Tvoj pristup edukaciji još nije aktivan. Na stranici Moj Forever možeš provjeriti uvjet i trenutačni napredak.');
            } else {
                $dashboard = forever_business_get_dashboard((int) $this->user->user_id, $is_admin, $requested_root, $period, $request_now);
                $submitted_fbo_id = forever_business_normalize_fbo_id($_POST['fbo_id'] ?? '');
                $authenticated_fbo_id = forever_business_normalize_fbo_id($vip_program['fbo_id'] ?? '');
                $target_member = null;
                if($submitted_fbo_id !== '' && hash_equals($authenticated_fbo_id, $submitted_fbo_id)) {
                    foreach($dashboard['members'] as $member) {
                        if((string) ($member['fbo_id'] ?? '') === $submitted_fbo_id) {
                            $target_member = $member;
                            break;
                        }
                    }
                }
                $expected_action = $target_member['next_action'] ?? null;
                $outcome_count = forever_business_normalize_outcome_count($_POST['outcome_count'] ?? null);
                $result_type = forever_business_normalize_result_type($_POST['result_type'] ?? null);
                $difficulty = forever_business_normalize_difficulty($_POST['difficulty'] ?? null);
                $matches_visible_action = $expected_action
                    && hash_equals((string) ($expected_action['key'] ?? ''), (string) ($_POST['action_key'] ?? ''))
                    && hash_equals((string) ($expected_action['core'] ?? ''), (string) ($_POST['core_key'] ?? ''));
                $matches_completable_action = $matches_visible_action && !empty($expected_action['can_complete']);
                $allowed_result_types = is_array($expected_action['allowed_result_types'] ?? null)
                    ? $expected_action['allowed_result_types']
                    : [];
                $completion_mode = $outcome_count === null || !$expected_action
                    ? null
                    : forever_business_vip_completion_mode_for_count($expected_action, $outcome_count);

                if($is_help_request) {
                    $help_note = trim((string) ($_POST['help_note'] ?? ''));
                    if(!$matches_visible_action || !empty($expected_action['is_daily_complete']) || !empty($expected_action['is_program_complete'])) {
                        Alerts::add_error('Ovaj se korak u međuvremenu promijenio. Osvježi stranicu i zatraži pomoć na trenutačnom zadatku.');
                    } elseif((int) ($expected_action['target'] ?? 0) <= 0) {
                        Alerts::add_error('Za ovaj informativni prikaz nije potreban zahtjev za pomoć. Osvježi stranicu kako bi se učitao trenutačni zadatak.');
                    } elseif(mb_strlen($help_note) < 3) {
                        Alerts::add_error('Ukratko napiši što bi ti pomoglo da napraviš sljedeći korak.');
                    } elseif(forever_business_request_vip_help((int) $this->user->user_id, $submitted_fbo_id, [$authenticated_fbo_id], [
                        'action_key' => (string) ($expected_action['key'] ?? ''),
                        'track_key' => (string) ($expected_action['track_key'] ?? ''),
                        'sequence_position' => (int) ($expected_action['sequence_position'] ?? 0),
                        'difficulty' => 'hard',
                        'note' => $help_note,
                    ], $request_now)) {
                        Alerts::add_success('Tvoj upit je poslan mentoru. Današnji korak ostaje otvoren i možete zajedno pronaći najbolju lakšu verziju.');
                    } else {
                        Alerts::add_error('Zahtjev za pomoć nije spremljen. Osvježi stranicu i pokušaj ponovno.');
                    }
                } elseif($outcome_count === null) {
                    Alerts::add_error('Upiši barem jednu završenu radnju. Ako je danas nemaš, odaberi mentorsku vježbu ili pošalji upit mentoru.');
                } elseif($result_type === null || $difficulty === null) {
                    Alerts::add_error('Odaberi glavnu vrstu evidentirane radnje i koliko ti je današnji korak bio zahtjevan. Ti podaci pomažu da edukaciju poboljšamo bez dodatnih zadataka.');
                } elseif(!in_array($result_type, $allowed_result_types, true)) {
                    Alerts::add_error('Odabrana vrsta radnje ne odgovara današnjem zadatku. Odaberi jednu od prikazanih vrsta.');
                } elseif($completion_mode === null) {
                    Alerts::add_error('Za današnji korak napravi najmanje ' . max(1, (int) ($expected_action['quick_target'] ?? 1)) . ' radnji. Ako ti to danas ne odgovara, odaberi mentorsku vježbu ili se javi mentoru.');
                } elseif(!empty($target_member['vip_action_done_today'])) {
                    Alerts::add_error('Današnji VIP korak već je dovršen. Novi zadatak otvorit će se sutra.');
                } elseif(!$matches_completable_action) {
                    Alerts::add_error('Ovaj se korak u međuvremenu promijenio. Stranica je osvježena i prikazan je tvoj trenutačni zadatak.');
                } else {
                    /* Track, core and action stay server-derived. The member supplies
                     * only the bounded primary action type, difficulty, completed
                     * action-unit count and optional note for aggregate coaching. */
                    $record_input = [
                        'core_key' => (string) ($expected_action['core'] ?? ''),
                        'action_key' => (string) ($expected_action['key'] ?? ''),
                        'outcome_type' => (string) ($expected_action['track_key'] ?? 'vip'),
                        'result_type' => $result_type,
                        'difficulty' => $difficulty,
                        'completion_mode' => $completion_mode,
                        'needs_help' => false,
                        'outcome_count' => $outcome_count,
                        'sequence_position' => (int) ($expected_action['sequence_position'] ?? 0),
                        'note' => (string) ($_POST['note'] ?? ''),
                    ];
                    $is_final_program_step = empty($expected_action['is_weekly_plan'])
                        && (int) ($expected_action['sequence_total'] ?? 0) > 0
                        && (int) ($expected_action['sequence_position'] ?? 0) >= (int) $expected_action['sequence_total'];
                    if(forever_business_record_daily_outcome((int) $this->user->user_id, $submitted_fbo_id, [$authenticated_fbo_id], $record_input, $request_now)) {
                        Alerts::add_success($is_final_program_step
                            ? 'Prvih 30 VIP koraka je dovršeno. Tvoj završni pregled je spreman.'
                            : ($completion_mode === 'quick'
                                ? 'Današnja kraća verzija je dovršena. Bravo što održavaš ritam — novi korak otvara se sutra.'
                                : 'Današnji korak je dovršen. Novi konkretan zadatak otvorit će se sutra.'));
                    } else {
                        Alerts::add_error('Aktivnost nije spremljena. Moguće je da je današnji korak već potvrđen; osvježi stranicu i provjeri status.');
                    }
                }
            }

            $query = array_filter(['period' => $period, 'root' => $requested_root]);
            redirect('forever-business' . (!empty($query) ? '?' . http_build_query($query) : ''));
        }

        $dashboard = forever_business_get_dashboard((int) $this->user->user_id, $is_admin, $requested_root, $period, $request_now);
        $own_fbo_id = forever_business_extract_user_fbo_id($this->user->preferences ?? null);
        $focus_member = null;

        foreach($dashboard['members'] as $member) {
            if((string) $member['fbo_id'] === $own_fbo_id || (!$focus_member && (string) $member['fbo_id'] === $requested_root)) {
                $focus_member = $member;
                if((string) $member['fbo_id'] === $own_fbo_id) break;
            }
        }
        if(!$is_admin && !$focus_member && count($dashboard['members']) === 1) {
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
