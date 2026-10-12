<?php
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-12: Moj put tracks, real context, sponsor visibility and feedback.
 * The 90 published tasks stay the same. A member walks the shared first 15 tasks, then picks
 * a track that orders the remaining 75. Tasks that need a customer, a team member, a guest or
 * an own event wait until that exists, so a member is not handed a rehearsal when real work is
 * possible. Progress is derived from completed task days, so earlier progress, a track change
 * or a reordered catalogue never loses or repeats a task. Every record lives in the existing
 * fcc_partner_pilot_events and forever_business_daily_outcomes tables. */

const FCC_MP_COMMON = [1, 2, 3, 4, 6, 8, 9, 11, 5, 12, 7, 10, 13, 14, 15];
const FCC_MP_MILESTONES = [7, 15, 30, 60, 90];
const FCC_MP_CONTEXTS = ['team', 'customer', 'guest', 'event'];

function fcc_mp_tracks(): array {
 return [
  'use' => ['name'=>'Koristim i preporučujem', 'description'=>'Za tebe ako želiš koristiti proizvode, preporučivati ih ljudima oko sebe i brinuti o svojim kupcima. Poslovni razgovori dolaze kasnije i bez pritiska.',
   'order'=>[16,18,19,20,21,27,28,25,24,29,32,33,34,35,30, 31,39,41,43,44,55,56,59,61,65,67,68,42,50,45, 54,76,80,81,86,87,85,73,17,26,36,37,22,23,38, 40,46,57,64,69,70,71,72,77,88,47,48,49,51,52, 53,58,60,62,63,66,74,75,78,79,82,83,84,89,90]],
  'sales' => ['name'=>'Gradim dodatni prihod', 'description'=>'Za tebe ako želiš redovite kupce i stalnu zaradu od prodaje. Fokus su preporuke, briga o kupcima i ritam rada, a pozivanje u posao dolazi postupno.',
   'order'=>[16,18,19,20,21,27,28,25,17,22,23,24,29,26,30, 31,32,33,34,35,39,41,43,44,36,37,38,42,40,45, 55,56,59,61,65,67,68,54,50,46,57,64,76,80,73, 81,86,87,85,69,70,71,72,77,88,47,48,49,51,52, 53,58,60,62,63,66,74,75,78,79,82,83,84,89,90]],
  'team' => ['name'=>'Gradim tim', 'description'=>'Za tebe ako želiš graditi vlastiti tim. Uz preporuke i kupce rano učiš pozivati, predstavljati posao i pomagati novim suradnicima.',
   'order'=>range(16, 90)],
 ];
}

function fcc_mp_track_valid($track): bool {
 return is_string($track) && isset(fcc_mp_tracks()[$track]);
}

function fcc_mp_order(?string $track): array {
 return $track && fcc_mp_track_valid($track) ? array_merge(FCC_MP_COMMON, fcc_mp_tracks()[$track]['order']) : FCC_MP_COMMON;
}

function fcc_mp_event(int $uid, string $kind, string $detail, string $requestKey, int $step = 0): bool {
 $stmt = fcc_partner_query('INSERT IGNORE INTO fcc_partner_pilot_events (user_id,step_id,kind,detail,request_key,created_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP())', [$uid, $step ?: null, $kind, mb_substr($detail, 0, 500), substr($requestKey, 0, 64)]);
 return $stmt->affected_rows === 1;
}

function fcc_mp_track(int $uid, bool $refresh = false): ?string {
 static $cache = [];
 if(!$refresh && array_key_exists($uid, $cache)) return $cache[$uid];
 $row = function_exists('fcc_jp_schema') && fcc_jp_schema() ? fcc_partner_one("SELECT detail FROM fcc_partner_pilot_events WHERE user_id=? AND kind='track_chosen' ORDER BY id DESC LIMIT 1", [$uid]) : null;
 return $cache[$uid] = $row && fcc_mp_track_valid($row['detail']) ? $row['detail'] : null;
}

function fcc_mp_set_track(int $uid, array $in): string {
 $track = (string)($in['track'] ?? '');
 if(!fcc_mp_track_valid($track)) throw new InvalidArgumentException(fcc_t('Odaberi jednu od tri staze.'));
 if(!fcc_jp_enabled($uid)) throw new InvalidArgumentException(fcc_t('Moj put trenutačno nije uključen.'));
 fcc_mp_event($uid, 'track_chosen', $track, 'mp-track-'.bin2hex(random_bytes(12)));
 fcc_mp_track($uid, true);
 return $track;
}

/** Suggested track: team for members who already lead people or use PRO, sales for those with recent CC. */
function fcc_mp_suggested_track(int $uid): string {
 if(fcc_mp_has_team($uid) || fcc_pro_has_access($uid)) return 'team';
 $fbo = forever_business_extract_user_fbo_id(fcc_partner_one('SELECT preferences FROM users WHERE user_id=?', [$uid])['preferences'] ?? '{}');
 if($fbo !== '' && fcc_partner_one('SELECT 1 FROM forever_business_metrics WHERE fbo_id=? AND personal_cc>0 AND period_month>=? LIMIT 1', [$fbo, (new DateTimeImmutable('first day of -1 month', new DateTimeZone('Europe/Zagreb')))->format('Y-m-01')])) return 'sales';
 return 'use';
}

/** Task days already completed in this cycle. Older snapshots without a day keep their position. */
function fcc_mp_done_days(array $rows): array {
 $done = [];
 foreach($rows as $row) {
  $content = json_decode((string)($row['content_json'] ?? ''), true) ?: [];
  $day = (int)($content['day'] ?? $row['sequence_position'] ?? 0);
  if($day >= 1 && $day <= 90) $done[$day] = true;
 }
 return $done;
}

/* ---------- Real context ---------- */

function fcc_mp_graph(): array {
 static $graph;
 return $graph ??= function_exists('fcc_team_graph') ? fcc_team_graph() : [];
}

/** Direct members on the confirmed sponsor line, for every sponsor regardless of plan. Privacy requests are respected by the edge. */
function fcc_mp_direct_members(int $sponsor): array {
 static $cache = [];
 if(isset($cache[$sponsor])) return $cache[$sponsor];
 $graph = fcc_mp_graph();
 if(!$graph || !function_exists('fcc_team_edge')) return $cache[$sponsor] = [];
 $primary = fcc_team_primary($sponsor, $graph);
 if(!$primary) return $cache[$sponsor] = [];
 $out = [];
 foreach($graph as $id => $member) {
  if($id === $primary || fcc_team_primary($id, $graph) !== $id) continue;
  if(fcc_team_edge($id, $graph) === $primary) $out[$id] = $member;
 }
 return $cache[$sponsor] = $out;
}

function fcc_mp_has_team(int $uid): bool {
 return count(fcc_mp_direct_members($uid)) > 0;
}

function fcc_mp_sponsor_of(int $uid): ?array {
 $graph = fcc_mp_graph();
 return $graph && function_exists('fcc_team_sponsor') ? fcc_team_sponsor($uid, $graph) : null;
}

function fcc_mp_has_customer(int $uid): bool {
 return (bool)fcc_partner_one("SELECT 1 FROM fcc_partner_contacts WHERE user_id=? AND sales_stage='customer' AND contact_permission<>'do_not_contact' LIMIT 1", [$uid])
  || (bool)fcc_partner_one("SELECT 1 FROM fcc_partner_opportunities WHERE user_id=? AND status='customer_reported' LIMIT 1", [$uid]);
}

function fcc_mp_context_ready(int $uid, string $requirement, DateTimeImmutable $now): bool {
 static $cache = [];
 $key = $uid.':'.$requirement;
 if(isset($cache[$key])) return $cache[$key];
 $utc = $now->setTimezone(new DateTimeZone('UTC'));
 return $cache[$key] = match($requirement) {
  'team' => fcc_mp_has_team($uid),
  'customer' => fcc_mp_has_customer($uid),
  'guest' => (bool)fcc_partner_one("SELECT r.id FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id WHERE r.user_id=? AND r.status='registered' AND r.is_demo=0 AND r.is_self=0 AND r.needs_review=0 AND s.status='scheduled' AND s.ends_at_utc>? LIMIT 1", [$uid, $utc->format('Y-m-d H:i:s')]),
  'event' => (bool)fcc_partner_one("SELECT id FROM fcc_webinar_sessions WHERE owner_user_id=? AND status='scheduled' AND starts_at_utc<=? AND ends_at_utc>=? LIMIT 1", [$uid, $utc->format('Y-m-d H:i:s'), $now->modify('-7 days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')]),
  default => true,
 };
}

function fcc_mp_task_requirement(int $day): string {
 static $requirements;
 if($requirements === null) {
  $requirements = [];
  foreach(fcc_jp_catalog()['tasks'] as $task) $requirements[(int)$task['day']] = (string)($task['context_requirement'] ?? '');
 }
 return $requirements[$day] ?? '';
}

/**
 * The next task day for this member. Null means the shared first part is done and a track
 * must be chosen first. An opened step keeps its pinned task so nothing shifts mid step.
 */
function fcc_mp_next_day(int $uid, array $rows, ?array $step, DateTimeImmutable $now): ?int {
 $done = fcc_mp_done_days($rows);
 if($step) {
  $pinned = (int)((json_decode((string)$step['content_json'], true) ?: [])['day'] ?? 0);
  if($pinned >= 1 && $pinned <= 90 && !isset($done[$pinned])) return $pinned;
 }
 $track = fcc_mp_track($uid);
 $remaining = array_values(array_filter(fcc_mp_order($track), static fn($day) => !isset($done[$day])));
 foreach($remaining as $day) {
  $requirement = fcc_mp_task_requirement($day);
  if(!in_array($requirement, FCC_MP_CONTEXTS, true) || fcc_mp_context_ready($uid, $requirement, $now)) return $day;
 }
 // Without a track the remaining shared tasks only wait for context; ask for the track first.
 if(!$track) return null;
 if($remaining) return $remaining[0];
 // A complete track with leftovers (catalogue changes): finish any other open day.
 foreach(range(1, 90) as $day) if(!isset($done[$day])) return $day;
 return 90;
}

function fcc_mp_needs_track(int $uid, array $rows, DateTimeImmutable $now): bool {
 return count($rows) < 90 && !fcc_mp_track($uid) && fcc_mp_next_day($uid, $rows, null, $now) === null;
}

/** Tasks that currently wait for a customer, team member, guest or own event. */
function fcc_mp_waiting(int $uid, array $rows, DateTimeImmutable $now): array {
 $done = fcc_mp_done_days($rows);$out = [];
 foreach(fcc_mp_order(fcc_mp_track($uid)) as $day) {
  if(isset($done[$day])) continue;
  $requirement = fcc_mp_task_requirement($day);
  if(in_array($requirement, FCC_MP_CONTEXTS, true) && !fcc_mp_context_ready($uid, $requirement, $now)) $out[$requirement] = ($out[$requirement] ?? 0) + 1;
 }
 return $out;
}

function fcc_mp_requirement_labels(): array {
 return [
  'customer' => 'Koraci s kupcima otvaraju se kada u Kontaktima označiš prvog kupca.',
  'team' => 'Timski koraci otvaraju se kada ti se u timu pridruži prvi suradnik.',
  'guest' => 'Korak s gostom otvara se kada ti se netko prijavi na webinar.',
  'event' => 'Korak o vođenju susreta otvara se kada imaš svoj zakazani susret.',
 ];
}

/* ---------- Known steps, milestones and feedback ---------- */

function fcc_mp_known_allowed(int $day, int $position): bool {
 return in_array($day, FCC_MP_COMMON, true) && $position <= count(FCC_MP_COMMON);
}

/** Called inside the per user journey transaction after a step is saved. */
function fcc_mp_after_complete(int $uid, int $completed): void {
 if(!in_array($completed, FCC_MP_MILESTONES, true)) return;
 if(!fcc_mp_event($uid, 'milestone', 'steps:'.$completed, 'mp-milestone-steps-'.$completed)) return;
 fcc_mp_notify_sponsor_milestone($uid, 'steps:'.$completed);
}

function fcc_mp_milestone_label(string $milestone, ?string $locale = null): string {
 if($milestone === 'first_cc') return fcc_t('Prvi osobni CC bodovi', [], $locale);
 if($milestone === 'first_4cc') return fcc_t('Prva 4CC aktivnost', [], $locale);
 $steps = (int)substr($milestone, 6);
 return $steps === 90 ? fcc_t('Svih 90 koraka Mojeg puta', [], $locale) : fcc_t('{number} koraka Mojeg puta', ['{number}'=>(string)$steps], $locale);
}

/* Sponsor notices are stored in Croatian and shown in the reader's language by fcc_mp_notice_display(). */
function fcc_mp_notice_copy(string $kind, array $vars, ?string $locale = 'hr'): array {
 $copy = [
  'milestone' => ['Prekretnica u tvom timu', '{name}: {milestone}. Javi se i čestitaj.'],
  'help' => ['{name} treba pomoć s korakom', 'Korak: {step}. Javi se danas i prođite ga zajedno.'],
  'weekly' => ['Tvoj tim na Mojem putu ovaj tjedan', 'Napredovalo: {moved}. Stoji ili još nije krenulo: {waiting}. Traži pomoć: {help}.'],
  'start' => ['Novi član tima počinje Moj put', '{name} ima svoj prvi korak Mojeg puta. Pošalji dobrodošlicu i dogovorite kratki razgovor.'],
 ][$kind];
 $replace = [];
 foreach($vars as $key => $value) $replace['{'.$key.'}'] = (string)$value;
 return [fcc_t($copy[0], $replace, $locale), fcc_t($copy[1], $replace, $locale)];
}

function fcc_mp_notice_display(array $notice, ?string $locale = null): array {
 $locale = fcc_locale($locale);
 if($locale === 'hr') return $notice;
 $key = (string)($notice['event_key'] ?? '');$title = (string)($notice['title'] ?? '');$body = (string)($notice['body'] ?? '');
 $vars = null;$kind = null;
 if(preg_match('/^mp:milestone:\d+:(.+)$/', $key, $m)) {
  $label = fcc_mp_milestone_label($m[1], 'hr');$suffix = ': '.$label.'. Javi se i čestitaj.';
  if(str_ends_with($body, $suffix)) { $kind = 'milestone';$vars = ['name'=>substr($body, 0, -strlen($suffix)), 'milestone'=>fcc_mp_milestone_label($m[1], $locale)]; }
 } elseif(preg_match('/^mp:(?:stuck|help):/', $key) && preg_match('/^(.*) treba pomoć s korakom$/us', $title, $t) && preg_match('/^Korak: (.*)\. Javi se danas i prođite ga zajedno\.$/us', $body, $b)) {
  $kind = 'help';$vars = ['name'=>$t[1], 'step'=>fcc_t($b[1], [], $locale)];
 } elseif(str_starts_with($key, 'mp:weekly:') && preg_match('/^Napredovalo: (\d+)\. Stoji ili još nije krenulo: (\d+)\. Traži pomoć: (\d+)\.$/u', $body, $b)) {
  $kind = 'weekly';$vars = ['moved'=>$b[1], 'waiting'=>$b[2], 'help'=>$b[3]];
 } elseif(str_starts_with($key, 'mp:start:') && preg_match('/^(.*) ima svoj prvi korak Mojeg puta\./us', $body, $b)) {
  $kind = 'start';$vars = ['name'=>$b[1]];
 }
 if($kind === null) return $notice;
 [$notice['title'], $notice['body']] = fcc_mp_notice_copy($kind, $vars, $locale);
 return $notice;
}

function fcc_mp_notify_sponsor_milestone(int $uid, string $milestone): void {
 $sponsor = fcc_mp_sponsor_of($uid);
 if(!$sponsor) return;
 $name = trim((string)(fcc_partner_one('SELECT name FROM users WHERE user_id=?', [$uid])['name'] ?? ''));
 [$title, $body] = fcc_mp_notice_copy('milestone', ['name'=>$name, 'milestone'=>fcc_mp_milestone_label($milestone, 'hr')]);
 fcc_partner_notify((int)$sponsor['user_id'], 'mp:milestone:'.$uid.':'.$milestone, 'team', $title, $body, 'partner?view=team-progress', 7 * 86400);
}

/** First personal CC and first 4CC are celebrated only when they happen now, never for old history. */
function fcc_mp_cc_milestones(int $uid): void {
 $fbo = forever_business_extract_user_fbo_id(fcc_partner_one('SELECT preferences FROM users WHERE user_id=?', [$uid])['preferences'] ?? '{}');
 if($fbo === '') return;
 $recent = (new DateTimeImmutable('first day of -1 month', new DateTimeZone('Europe/Zagreb')))->format('Y-m-01');
 $first = fcc_partner_one('SELECT MIN(CASE WHEN personal_cc>0 THEN period_month END) cc, MIN(CASE WHEN is_4cc_active=1 THEN period_month END) cc4 FROM forever_business_metrics WHERE fbo_id=?', [$fbo]);
 foreach(['first_cc'=>$first['cc'] ?? null, 'first_4cc'=>$first['cc4'] ?? null] as $milestone => $month) {
  if($month && $month >= $recent && fcc_mp_event($uid, 'milestone', $milestone, 'mp-milestone-'.$milestone)) fcc_mp_notify_sponsor_milestone($uid, $milestone);
 }
}

/** The newest milestone of the last seven days that the member has not answered yet. */
function fcc_mp_celebration(int $uid): ?array {
 if(!function_exists('fcc_jp_schema') || !fcc_jp_schema()) return null;
 try { fcc_mp_cc_milestones($uid); } catch(Throwable $e) { error_log('Moj put CC milestone: '.get_class($e)); }
 $row = fcc_partner_one("SELECT e.id,e.detail,e.created_at FROM fcc_partner_pilot_events e WHERE e.user_id=? AND e.kind='milestone' AND e.created_at>=UTC_TIMESTAMP()-INTERVAL 7 DAY AND NOT EXISTS (SELECT 1 FROM fcc_partner_pilot_events r WHERE r.user_id=e.user_id AND r.kind IN ('milestone_seen','milestone_share_ok') AND CAST(r.detail AS UNSIGNED)=e.id) ORDER BY e.id DESC LIMIT 1", [$uid]);
 return $row ? ['id'=>(int)$row['id'], 'milestone'=>$row['detail'], 'label'=>fcc_mp_milestone_label($row['detail'])] : null;
}

function fcc_mp_celebration_respond(int $uid, array $in): void {
 $id = (int)($in['milestone_id'] ?? 0);
 if(!fcc_partner_one("SELECT id FROM fcc_partner_pilot_events WHERE id=? AND user_id=? AND kind='milestone'", [$id, $uid])) throw new InvalidArgumentException(fcc_t('Prekretnica nije pronađena.'));
 $share = ($in['share'] ?? '') === '1';
 fcc_mp_event($uid, $share ? 'milestone_share_ok' : 'milestone_seen', (string)$id, 'mp-milestone-answer-'.$id);
}

/** Records the request and returns whether a sponsor could be notified. */
function fcc_mp_stuck(int $uid, array $in): bool {
 if(!fcc_jp_enabled($uid)) throw new InvalidArgumentException(fcc_t('Moj put trenutačno nije uključen.'));
 $step = fcc_partner_one('SELECT s.id,s.content_json FROM fcc_partner_journey_steps s JOIN fcc_partner_journey_cycles c ON c.id=s.cycle_id WHERE s.id=? AND s.user_id=? AND c.user_id=?', [(int)($in['step_id'] ?? 0), $uid, $uid]);
 if(!$step) throw new InvalidArgumentException(fcc_t('Otvori svoj korak pa javi gdje je zapelo.'));
 $task = json_decode($step['content_json'], true) ?: [];
 $text = fcc_partner_text($in['stuck_text'] ?? '', 300);
 $day = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zagreb')))->format('Ymd');
 fcc_mp_event($uid, 'stuck', (int)($task['day'] ?? 0).'|'.($task['title'] ?? '').($text !== '' ? '|'.$text : ''), 'mp-stuck-'.(int)$step['id'].'-'.$day, (int)$step['id']);
 $sponsor = fcc_mp_sponsor_of($uid);
 if(!$sponsor) return false;
 $name = trim((string)(fcc_partner_one('SELECT name FROM users WHERE user_id=?', [$uid])['name'] ?? ''));
 [$title, $body] = fcc_mp_notice_copy('help', ['name'=>$name, 'step'=>(string)($task['title'] ?? '')]);
 fcc_partner_notify((int)$sponsor['user_id'], 'mp:stuck:'.$uid.':'.(int)$step['id'].':'.$day, 'team', $title, $body, 'partner?view=team-progress', 3 * 86400);
 return true;
}

/** One click answer after a saved step: easy, hard or needs help. */
function fcc_mp_feedback(int $uid, array $in): string {
 $answer = fcc_partner_choice($in['answer'] ?? '', ['easy'=>1, 'hard'=>1, 'help'=>1]);
 $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zagreb')))->format('Y-m-d');
 $row = fcc_partner_one("SELECT o.outcome_id,o.journey_step_id,s.content_json FROM forever_business_daily_outcomes o LEFT JOIN fcc_partner_journey_steps s ON s.id=o.journey_step_id WHERE o.recorded_by_user_id=? AND o.journey_cycle_id IS NOT NULL AND o.status='done' AND o.action_date=? ORDER BY o.outcome_id DESC LIMIT 1", [$uid, $today]);
 if(!$row) throw new InvalidArgumentException(fcc_t('Danas još nema spremljenog koraka.'));
 fcc_partner_query('UPDATE forever_business_daily_outcomes SET difficulty=?,needs_help=?,updated_at=UTC_TIMESTAMP() WHERE outcome_id=? AND recorded_by_user_id=?', [$answer === 'easy' ? 'easy' : 'hard', $answer === 'help' ? 1 : 0, (int)$row['outcome_id'], $uid]);
 if($answer === 'help') {
  $task = json_decode((string)$row['content_json'], true) ?: [];
  $sponsor = fcc_mp_sponsor_of($uid);
  if($sponsor) {
   $name = trim((string)(fcc_partner_one('SELECT name FROM users WHERE user_id=?', [$uid])['name'] ?? ''));
   [$title, $body] = fcc_mp_notice_copy('help', ['name'=>$name, 'step'=>(string)($task['title'] ?? '')]);
   fcc_partner_notify((int)$sponsor['user_id'], 'mp:help:'.$uid.':'.(int)$row['outcome_id'], 'team', $title, $body, 'partner?view=team-progress', 3 * 86400);
  }
 }
 return $answer;
}

function fcc_mp_feedback_given(int $uid): bool {
 $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zagreb')))->format('Y-m-d');
 return (bool)fcc_partner_one("SELECT 1 FROM forever_business_daily_outcomes WHERE recorded_by_user_id=? AND journey_cycle_id IS NOT NULL AND status='done' AND action_date=? AND difficulty IN ('easy','hard') LIMIT 1", [$uid, $today]);
}

/* ---------- Sponsor view ---------- */

function fcc_mp_member_progress(array $ids): array {
 $ids = array_values(array_unique(array_map('intval', $ids)));
 if(!$ids) return [];
 $in = implode(',', array_fill(0, count($ids), '?'));
 $out = [];
 foreach(fcc_partner_rows("SELECT u.user_id,u.name,r.first_entered_at FROM users u LEFT JOIN fcc_partner_rollout_access r ON r.user_id=u.user_id WHERE u.user_id IN ($in) AND u.status=1", $ids) as $u) {
  $out[(int)$u['user_id']] = ['user_id'=>(int)$u['user_id'], 'name'=>$u['name'], 'entered'=>!empty($u['first_entered_at']), 'cycle'=>false, 'completed'=>0, 'last_done'=>null, 'started_at'=>null, 'last_title'=>'', 'track'=>null, 'help'=>false, 'help_title'=>'', 'hard'=>false];
 }
 foreach(fcc_partner_rows("SELECT c.user_id,c.started_at,COUNT(o.outcome_id) completed,MAX(o.action_date) last_done FROM fcc_partner_journey_cycles c LEFT JOIN forever_business_daily_outcomes o ON o.journey_cycle_id=c.id AND o.status='done' WHERE c.user_id IN ($in) AND c.status IN ('active','completed') GROUP BY c.id", $ids) as $c) {
  if(!isset($out[(int)$c['user_id']])) continue;
  $out[(int)$c['user_id']] = array_replace($out[(int)$c['user_id']], ['cycle'=>true, 'completed'=>(int)$c['completed'], 'last_done'=>$c['last_done'], 'started_at'=>$c['started_at']]);
 }
 foreach(fcc_partner_rows("SELECT o.recorded_by_user_id uid,s.content_json FROM forever_business_daily_outcomes o JOIN fcc_partner_journey_steps s ON s.id=o.journey_step_id WHERE o.outcome_id IN (SELECT MAX(outcome_id) FROM forever_business_daily_outcomes WHERE recorded_by_user_id IN ($in) AND journey_cycle_id IS NOT NULL AND status='done' GROUP BY recorded_by_user_id)", $ids) as $r) {
  if(isset($out[(int)$r['uid']])) $out[(int)$r['uid']]['last_title'] = (string)((json_decode($r['content_json'], true) ?: [])['title'] ?? '');
 }
 foreach(fcc_partner_rows("SELECT user_id,kind,detail FROM fcc_partner_pilot_events WHERE user_id IN ($in) AND kind IN ('track_chosen','stuck') AND (kind='track_chosen' OR created_at>=UTC_TIMESTAMP()-INTERVAL 7 DAY) ORDER BY id", $ids) as $e) {
  if(!isset($out[(int)$e['user_id']])) continue;
  if($e['kind'] === 'track_chosen' && fcc_mp_track_valid($e['detail'])) $out[(int)$e['user_id']]['track'] = $e['detail'];
  if($e['kind'] === 'stuck') { $parts = explode('|', $e['detail']); $out[(int)$e['user_id']]['help'] = true; $out[(int)$e['user_id']]['help_title'] = $parts[1] ?? ''; }
 }
 foreach(fcc_partner_rows("SELECT recorded_by_user_id uid,MAX(needs_help) help,MAX(difficulty='hard') hard FROM forever_business_daily_outcomes WHERE recorded_by_user_id IN ($in) AND journey_cycle_id IS NOT NULL AND status='done' AND action_date>=CURDATE()-INTERVAL 7 DAY GROUP BY recorded_by_user_id", $ids) as $f) {
  if(!isset($out[(int)$f['uid']])) continue;
  if((int)$f['help']) $out[(int)$f['uid']]['help'] = true;
  if((int)$f['hard']) $out[(int)$f['uid']]['hard'] = true;
 }
 return $out;
}

/** Grouped overview of a sponsor's direct members for the sponsor view and the Today panel. */
function fcc_mp_sponsor_overview(int $sponsor): array {
 $members = fcc_mp_direct_members($sponsor);
 $groups = ['help'=>[], 'inactive'=>[], 'not_started'=>[], 'progress'=>[], 'not_entered'=>[]];
 if(!$members) return ['groups'=>$groups, 'total'=>0, 'in_program'=>0, 'active_week'=>0];
 $progress = fcc_mp_member_progress(array_keys($members));
 $today = new DateTimeImmutable('today', new DateTimeZone('Europe/Zagreb'));
 $inProgram = 0; $activeWeek = 0;
 foreach($progress as $uid => $p) {
  $p['phone'] = !empty($members[$uid]['contact_confirmed']) ? (string)($members[$uid]['phone'] ?? '') : '';
  $p['days_idle'] = $p['last_done'] ? (int)$today->diff(new DateTimeImmutable($p['last_done'], new DateTimeZone('Europe/Zagreb')))->days : null;
  if($p['cycle']) $inProgram++;
  if($p['days_idle'] !== null && $p['days_idle'] <= 7) $activeWeek++;
  if(!$p['entered'] || !$p['cycle']) $groups['not_entered'][] = $p;
  elseif($p['help'] || $p['hard']) $groups['help'][] = $p;
  elseif($p['completed'] === 0) $groups['not_started'][] = $p;
  elseif($p['completed'] < 90 && $p['days_idle'] !== null && $p['days_idle'] >= 3) $groups['inactive'][] = $p;
  else $groups['progress'][] = $p;
 }
 foreach($groups as &$list) usort($list, static fn($a, $b) => [$b['completed'], $a['name']] <=> [$a['completed'], $b['name']]);
 unset($list);
 return ['groups'=>$groups, 'total'=>count($progress), 'in_program'=>$inProgram, 'active_week'=>$activeWeek];
}

function fcc_mp_member_message(string $group, array $member): string {
 $first = explode(' ', trim((string)$member['name']))[0] ?? '';
 return match($group) {
  'help' => sprintf(fcc_t('Bok %s! Vidim da ti je zapelo na koraku „%s”. Kada možemo kratko proći zajedno?'), $first, $member['help_title'] ?: $member['last_title']),
  'inactive' => sprintf(fcc_t('Bok %s! Kako ide Moj put? Ako ti treba pomoć sa sljedećim korakom, tu sam.'), $first),
  'not_started', 'not_entered' => sprintf(fcc_t('Bok %s! U FCC aplikaciji te čeka tvoj prvi korak programa Moj put. Treba ti oko 20 minuta: %s'), $first, SITE_URL.'partner?view=step'),
  default => sprintf(fcc_t('Bravo %s! Vidim da si već na %d. koraku Mojeg puta. Samo tako!'), $first, (int)$member['completed']),
 };
}

/** Weekly summary for sponsors on Monday morning, one notification per sponsor and week. */
function fcc_mp_weekly_summaries(?DateTimeImmutable $now = null): int {
 $now = ($now ?? new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('Europe/Zagreb'));
 if($now->format('N') !== '1' || (int)$now->format('G') < 8 || !function_exists('fcc_jp_schema') || !fcc_jp_schema()) return 0;
 $week = $now->format('o-\WW');
 $graph = fcc_mp_graph();
 if(!$graph) return 0;
 $bySponsor = [];
 foreach(fcc_partner_rows("SELECT DISTINCT user_id FROM fcc_partner_journey_cycles WHERE status IN ('active','completed')") as $row) {
  $sid = fcc_team_edge((int)$row['user_id'], $graph);
  if($sid) $bySponsor[$sid][] = (int)$row['user_id'];
 }
 $sent = 0;
 foreach($bySponsor as $sid => $ids) {
  if(fcc_partner_one('SELECT 1 FROM fcc_partner_notifications WHERE user_id=? AND event_key=?', [$sid, 'mp:weekly:'.$week])) continue;
  $progress = fcc_mp_member_progress($ids);$moved = 0;$waiting = 0;$help = 0;
  foreach($progress as $p) {
   if($p['help'] || $p['hard']) $help++;
   if($p['last_done'] && $p['last_done'] >= $now->modify('-7 days')->format('Y-m-d')) $moved++;
   elseif($p['completed'] < 90) $waiting++;
  }
  [$title, $body] = fcc_mp_notice_copy('weekly', ['moved'=>$moved, 'waiting'=>$waiting, 'help'=>$help]);
  fcc_partner_notify($sid, 'mp:weekly:'.$week, 'team', $title, $body, 'partner?view=team-progress', 3 * 86400);
  $sent++;
 }
 return $sent;
}

/** A member who opens FCC for the first time and starts Moj put is announced to the sponsor. */
function fcc_mp_notify_sponsor_start(int $uid): void {
 try {
  $sponsor = fcc_mp_sponsor_of($uid);
  if(!$sponsor) return;
  $name = trim((string)(fcc_partner_one('SELECT name FROM users WHERE user_id=?', [$uid])['name'] ?? ''));
  [$title, $body] = fcc_mp_notice_copy('start', ['name'=>$name]);
  fcc_partner_notify((int)$sponsor['user_id'], 'mp:start:'.$uid, 'team', $title, $body, 'partner?view=team-progress', 7 * 86400);
 } catch(Throwable $e) {
  error_log('Moj put sponsor start: '.get_class($e));
 }
}

/* ---------- After 90 steps ---------- */

function fcc_mp_rhythm_habits(): array {
 return ['talks'=>'Novi razgovori', 'care'=>'Briga o kupcima i timu', 'share'=>'Preporuke i objave', 'learn'=>'Učenje'];
}

function fcc_mp_rhythm_week(int $uid): array {
 $week = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zagreb')))->format('o-\WW');
 $done = [];
 foreach(fcc_partner_rows("SELECT detail FROM fcc_partner_pilot_events WHERE user_id=? AND kind='rhythm' AND detail LIKE ?", [$uid, $week.':%']) as $row) $done[substr($row['detail'], strlen($week) + 1)] = true;
 return ['week'=>$week, 'done'=>$done];
}

function fcc_mp_rhythm_save(int $uid, array $in): void {
 $habit = fcc_partner_choice($in['habit'] ?? '', fcc_mp_rhythm_habits());
 $week = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zagreb')))->format('o-\WW');
 fcc_mp_event($uid, 'rhythm', $week.':'.$habit, 'mp-rhythm-'.$week.'-'.$habit);
}

/* ---------- Administration ---------- */

function fcc_mp_admin_overview(): array {
 $one = static fn(string $sql, array $p = []) => (int)(fcc_partner_one($sql, $p)['n'] ?? 0);
 $buckets = ['0'=>0, '1-7'=>0, '8-15'=>0, '16-30'=>0, '31-60'=>0, '61-89'=>0, '90'=>0];
 foreach(fcc_partner_rows("SELECT COUNT(o.outcome_id) n FROM fcc_partner_journey_cycles c LEFT JOIN forever_business_daily_outcomes o ON o.journey_cycle_id=c.id AND o.status='done' WHERE c.status IN ('active','completed') GROUP BY c.id") as $r) {
  $n = (int)$r['n'];
  $key = $n === 0 ? '0' : ($n <= 7 ? '1-7' : ($n <= 15 ? '8-15' : ($n <= 30 ? '16-30' : ($n <= 60 ? '31-60' : ($n < 90 ? '61-89' : '90')))));
  $buckets[$key]++;
 }
 $tracks = array_fill_keys(array_keys(fcc_mp_tracks()), 0);
 foreach(fcc_partner_rows("SELECT e.detail FROM fcc_partner_pilot_events e JOIN (SELECT user_id,MAX(id) id FROM fcc_partner_pilot_events WHERE kind='track_chosen' GROUP BY user_id) l ON l.id=e.id") as $r) if(isset($tracks[$r['detail']])) $tracks[$r['detail']]++;
 $stalled = [];
 foreach(fcc_partner_rows("SELECT s.content_json,COUNT(*) n FROM (SELECT o.recorded_by_user_id uid,MAX(o.outcome_id) last FROM forever_business_daily_outcomes o JOIN fcc_partner_journey_cycles c ON c.id=o.journey_cycle_id AND c.status='active' WHERE o.status='done' GROUP BY o.recorded_by_user_id HAVING MAX(o.action_date)<=CURDATE()-INTERVAL 3 DAY) x JOIN forever_business_daily_outcomes o2 ON o2.outcome_id=x.last JOIN fcc_partner_journey_steps s ON s.id=o2.journey_step_id GROUP BY s.content_json ORDER BY n DESC LIMIT 5") as $r) {
  $stalled[] = ['title'=>(string)((json_decode($r['content_json'], true) ?: [])['title'] ?? ''), 'count'=>(int)$r['n']];
 }
 return [
  'approved' => $one("SELECT COUNT(*) n FROM users u JOIN fcc_partner_rollout_access r ON r.user_id=u.user_id AND r.allowed=1 WHERE u.status=1 AND u.type=0"),
  'entered' => $one("SELECT COUNT(*) n FROM fcc_partner_rollout_access WHERE first_entered_at IS NOT NULL"),
  'started' => $one("SELECT COUNT(DISTINCT recorded_by_user_id) n FROM forever_business_daily_outcomes WHERE journey_cycle_id IS NOT NULL AND status='done'"),
  'active_week' => $one("SELECT COUNT(DISTINCT recorded_by_user_id) n FROM forever_business_daily_outcomes WHERE journey_cycle_id IS NOT NULL AND status='done' AND action_date>=CURDATE()-INTERVAL 7 DAY"),
  'buckets' => $buckets,
  'tracks' => $tracks,
  'help_week' => $one("SELECT COUNT(DISTINCT user_id) n FROM fcc_partner_pilot_events WHERE kind='stuck' AND created_at>=UTC_TIMESTAMP()-INTERVAL 7 DAY") + $one("SELECT COUNT(*) n FROM forever_business_daily_outcomes WHERE journey_cycle_id IS NOT NULL AND needs_help=1 AND action_date>=CURDATE()-INTERVAL 7 DAY"),
  'hard_week' => $one("SELECT COUNT(*) n FROM forever_business_daily_outcomes WHERE journey_cycle_id IS NOT NULL AND difficulty='hard' AND action_date>=CURDATE()-INTERVAL 7 DAY"),
  'easy_week' => $one("SELECT COUNT(*) n FROM forever_business_daily_outcomes WHERE journey_cycle_id IS NOT NULL AND difficulty='easy' AND action_date>=CURDATE()-INTERVAL 7 DAY"),
  'practice_share' => $one("SELECT ROUND(100*SUM(learning_mode='practice')/GREATEST(COUNT(*),1)) n FROM forever_business_daily_outcomes WHERE journey_cycle_id IS NOT NULL AND status='done'"),
  'known' => $one("SELECT COUNT(*) n FROM forever_business_daily_outcomes WHERE journey_cycle_id IS NOT NULL AND status='done' AND completion_mode='known'"),
  'stalled' => $stalled,
  'shared_milestones' => fcc_partner_rows("SELECT u.name,m.detail milestone,m.created_at FROM fcc_partner_pilot_events s JOIN fcc_partner_pilot_events m ON m.id=CAST(s.detail AS UNSIGNED) AND m.user_id=s.user_id AND m.kind='milestone' JOIN users u ON u.user_id=s.user_id WHERE s.kind='milestone_share_ok' AND s.created_at>=UTC_TIMESTAMP()-INTERVAL 30 DAY ORDER BY s.id DESC LIMIT 30"),
 ];
}
/* /Custom code: FC-2026-10-12 */
