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
 /* Custom code: FC-2026-10-14: immutable requests retain existing help translations. */
 } elseif(preg_match('/^mp:(?:stuck|help|request):/', $key) && preg_match('/^(.*) treba pomoć s korakom$/us', $title, $t) && preg_match('/^Korak: (.*)\. Javi se danas i prođite ga zajedno\.$/us', $body, $b)) {
  $kind = 'help';$vars = ['name'=>$t[1], 'step'=>fcc_t($b[1], [], $locale)];
 /* /Custom code: FC-2026-10-14 */
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
 /* Custom code: FC-2026-10-14: serialize new requests and resolutions on the member. */
 database()->begin_transaction();
 try {
 fcc_partner_query('SELECT user_id FROM users WHERE user_id=? FOR UPDATE', [$uid]);
 $step = fcc_partner_one('SELECT s.id,s.content_json FROM fcc_partner_journey_steps s JOIN fcc_partner_journey_cycles c ON c.id=s.cycle_id WHERE s.id=? AND s.user_id=? AND c.user_id=?', [(int)($in['step_id'] ?? 0), $uid, $uid]);
 if(!$step) throw new InvalidArgumentException(fcc_t('Otvori svoj korak pa javi gdje je zapelo.'));
 $task = json_decode($step['content_json'], true) ?: [];
 $text = fcc_partner_text($in['stuck_text'] ?? '', 300);
 $key=(string)($in['request_key']??'');
 if(!preg_match('/^[a-f0-9]{32}$/D',$key)) throw new InvalidArgumentException(fcc_t('Osvježi obrazac i pokušaj ponovno.'));
 fcc_mp_event($uid, 'stuck', (int)($task['day'] ?? 0).'|'.($task['title'] ?? '').($text !== '' ? '|'.$text : ''), 'mp-stuck-'.$key, (int)$step['id']);
 $event=fcc_partner_one("SELECT id FROM fcc_partner_pilot_events WHERE user_id=? AND request_key=? AND kind='stuck'",[$uid,'mp-stuck-'.$key]);
 $sponsor = fcc_mp_sponsor_of($uid);
 if(!$sponsor) {database()->commit();return false;}
 $name = trim((string)(fcc_partner_one('SELECT name FROM users WHERE user_id=?', [$uid])['name'] ?? ''));
 [$title, $body] = fcc_mp_notice_copy('help', ['name'=>$name, 'step'=>(string)($task['title'] ?? '')]);
 fcc_partner_notify((int)$sponsor['user_id'], 'mp:request:'.$uid.':'.(int)$event['id'], 'team', $title, $body, 'partner?view=team-progress', 3 * 86400);
 database()->commit();
 return true;
 }catch(Throwable $e){database()->rollback();throw $e;}
 /* /Custom code: FC-2026-10-14 */
}

/** One click answer after a saved step: easy, hard or needs help. A step confirmed as already known is not a worked step and is never rated. */
function fcc_mp_feedback(int $uid, array $in): string {
 $answer = fcc_partner_choice($in['answer'] ?? '', ['easy'=>1, 'hard'=>1, 'help'=>1]);
 /* Custom code: FC-2026-10-14: feedback gets an immutable identity, including same-second repeats. */
 database()->begin_transaction();
 try {
 fcc_partner_query('SELECT user_id FROM users WHERE user_id=? FOR UPDATE', [$uid]);
 $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zagreb')))->format('Y-m-d');
 $row = fcc_partner_one("SELECT o.outcome_id,o.journey_step_id,s.content_json FROM forever_business_daily_outcomes o LEFT JOIN fcc_partner_journey_steps s ON s.id=o.journey_step_id WHERE o.recorded_by_user_id=? AND o.journey_cycle_id IS NOT NULL AND o.status='done' AND o.action_date=? AND (o.completion_mode IS NULL OR o.completion_mode<>'known') ORDER BY o.outcome_id DESC LIMIT 1", [$uid, $today]);
 if(!$row) throw new InvalidArgumentException(fcc_t('Danas još nema spremljenog koraka.'));
 $key=(string)($in['request_key']??'');
 if(!preg_match('/^[a-f0-9]{32}$/D',$key)) throw new InvalidArgumentException(fcc_t('Osvježi obrazac i pokušaj ponovno.'));
 $prior=fcc_partner_one("SELECT id,detail FROM fcc_partner_pilot_events WHERE user_id=? AND request_key=? AND kind='help_feedback'",[$uid,'mp-feedback-'.$key]);
 if($prior){$saved=json_decode($prior['detail'],true);database()->commit();return (string)($saved['answer']??$answer);}
 fcc_partner_query('UPDATE forever_business_daily_outcomes SET difficulty=?,needs_help=?,updated_at=UTC_TIMESTAMP() WHERE outcome_id=? AND recorded_by_user_id=?', [$answer === 'easy' ? 'easy' : 'hard', $answer === 'help' ? 1 : 0, (int)$row['outcome_id'], $uid]);
 fcc_mp_event($uid,'help_feedback',json_encode(['outcome_id'=>(int)$row['outcome_id'],'answer'=>$answer]),'mp-feedback-'.$key,(int)$row['journey_step_id']);
 $event=fcc_partner_one("SELECT id FROM fcc_partner_pilot_events WHERE user_id=? AND request_key=? AND kind='help_feedback'",[$uid,'mp-feedback-'.$key]);
 if($answer === 'help') {
  $task = json_decode((string)$row['content_json'], true) ?: [];
  $sponsor = fcc_mp_sponsor_of($uid);
  if($sponsor) {
   $name = trim((string)(fcc_partner_one('SELECT name FROM users WHERE user_id=?', [$uid])['name'] ?? ''));
   [$title, $body] = fcc_mp_notice_copy('help', ['name'=>$name, 'step'=>(string)($task['title'] ?? '')]);
   fcc_partner_notify((int)$sponsor['user_id'], 'mp:request:'.$uid.':'.(int)$event['id'], 'team', $title, $body, 'partner?view=team-progress', 3 * 86400);
  }
 }
 database()->commit();return $answer;
 }catch(Throwable $e){database()->rollback();throw $e;}
 /* /Custom code: FC-2026-10-14 */
}

function fcc_mp_feedback_given(int $uid): bool {
 $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zagreb')))->format('Y-m-d');
 return (bool)fcc_partner_one("SELECT 1 FROM forever_business_daily_outcomes WHERE recorded_by_user_id=? AND journey_cycle_id IS NOT NULL AND status='done' AND action_date=? AND (completion_mode IS NULL OR completion_mode<>'known') AND difficulty IN ('easy','hard') LIMIT 1", [$uid, $today]);
}

/* ---------- Sponsor view ---------- */

/* Custom code: FC-2026-10-14: preserve feedback history while tracking each resolved help signal. */
function fcc_mp_help_text(string $key): string {
 static $copy;
 $copy??=require APP_PATH.'config/fcc_sponsor_help_locales.php';
 return $copy[fcc_locale()][$key]??$copy['en'][$key]??$key;
}

/** Pure projection. A resolution applies only to its member and immutable source identity. */
function fcc_mp_help_project(array $events,array $outcomes): array {
 $resolved=[];$feedback=[];$requests=[];
 foreach($events as $e){
  $uid=(int)$e['user_id'];$detail=json_decode((string)$e['detail'],true);
  if($e['kind']==='help_resolved'&&is_array($detail)&&is_string($detail['source']??null)
   &&preg_match('/^(?:event:[1-9][0-9]*|outcome:[1-9][0-9]*:[a-f0-9]{24})$/D',$detail['source'])
   &&(int)($detail['actor']??0)>0&&(int)($detail['sponsor']??0)>0
   &&is_string($detail['snapshot']??null)&&preg_match('/^[a-f0-9]{64}$/D',$detail['snapshot'])
   &&($e['request_key']??'')==='mp-resolve-'.substr(hash('sha256',$uid.':'.$detail['source']),0,48))$resolved[$uid][$detail['source']]=(int)($e['step_id']??0);
  if($e['kind']==='help_feedback'&&is_array($detail)&&($oid=(int)($detail['outcome_id']??0))>0&&isset(['easy'=>1,'hard'=>1,'help'=>1][$detail['answer']??''])){
   if((int)$e['id']>(int)($feedback[$uid][$oid]['id']??0))$feedback[$uid][$oid]=$e+['answer'=>$detail['answer']];
  }
 }
 foreach($events as $e){
  if($e['kind']!=='stuck')continue;
  $uid=(int)$e['user_id'];$id=(int)$e['id'];$key='event:'.$id;$parts=explode('|',(string)$e['detail']);
  if(isset($resolved[$uid][$key])&&$resolved[$uid][$key]===(int)($e['step_id']??0))continue;
  $requests[$uid][$key]=['key'=>$key,'step_id'=>(int)($e['step_id']??0),'title'=>$parts[1]??'','help'=>true,'hard'=>false,'created_at'=>(string)$e['created_at'],
   'notice_keys'=>['mp:request:'.$uid.':'.$id,'mp:stuck:'.$uid.':'.(int)($e['step_id']??0).':'.(new DateTimeImmutable($e['created_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Ymd')]];
 }
 foreach($outcomes as $o){
  $uid=(int)$o['uid'];$oid=(int)$o['outcome_id'];$help=!empty($o['needs_help']);$hard=($o['difficulty']??'')==='hard';
  if(!$help&&!$hard)continue;
  $f=$feedback[$uid][$oid]??null;$expected=$help?'help':'hard';
  $key=$f&&$f['answer']===$expected?'event:'.(int)$f['id']:'outcome:'.$oid.':'.substr(hash('sha256',json_encode([$o['updated_at']??'',$o['difficulty'],$help,(int)$o['journey_step_id']])),0,24);
  if(isset($resolved[$uid][$key])&&$resolved[$uid][$key]===(int)$o['journey_step_id'])continue;
  $task=json_decode((string)($o['content_json']??'{}'),true)?:[];
  $notices=['mp:help:'.$uid.':'.$oid];if($f&&$f['answer']===$expected)$notices[]='mp:request:'.$uid.':'.(int)$f['id'];
  $requests[$uid][$key]=['key'=>$key,'step_id'=>(int)$o['journey_step_id'],'title'=>(string)($task['title']??''),'help'=>$help,'hard'=>$hard,'notice_keys'=>$notices,'created_at'=>(string)($f&&$f['answer']===$expected?$f['created_at']:($o['updated_at']??''))];
 }
 // Message templates use the last source. Order by its time, then by numeric event identity.
 foreach($requests as &$list)uasort($list,static fn($a,$b)=>strcmp($a['created_at'],$b['created_at'])?:strnatcmp($a['key'],$b['key']));unset($list);
 return $requests;
}

function fcc_mp_help_pending(array $ids): array {
 $ids=array_values(array_unique(array_filter(array_map('intval',$ids),static fn($id)=>$id>0)));if(!$ids)return [];
 $in=implode(',',array_fill(0,count($ids),'?'));
 // Feedback is measured by calendar day. Keep its identity beyond the rolling stuck window,
 // including the UTC versus Zagreb midnight boundary, so a resolved source cannot become legacy again.
 $events=fcc_partner_rows("SELECT id,user_id,step_id,kind,detail,request_key,created_at FROM fcc_partner_pilot_events WHERE user_id IN ($in) AND kind IN ('stuck','help_feedback','help_resolved') AND created_at>=UTC_TIMESTAMP()-INTERVAL 9 DAY AND (kind IN ('help_resolved','help_feedback') OR created_at>=UTC_TIMESTAMP()-INTERVAL 7 DAY) ORDER BY id",$ids);
 $outcomes=fcc_partner_rows("SELECT o.recorded_by_user_id uid,o.outcome_id,o.journey_step_id,o.difficulty,o.needs_help,o.updated_at,s.content_json FROM forever_business_daily_outcomes o LEFT JOIN fcc_partner_journey_steps s ON s.id=o.journey_step_id WHERE o.recorded_by_user_id IN ($in) AND o.journey_cycle_id IS NOT NULL AND o.status='done' AND o.action_date>=CURDATE()-INTERVAL 7 DAY AND (o.needs_help=1 OR o.difficulty='hard')",$ids);
 return fcc_mp_help_project($events,$outcomes);
}

function fcc_mp_help_token(int $uid,array $requests): string {
 $keys=array_keys($requests);sort($keys,SORT_STRING);return hash('sha256',json_encode([$uid,$keys]));
}

/** Explicit sponsor confirmation, never called by a WhatsApp or clipboard action. */
function fcc_mp_help_resolve(int $actor,array $in): int {
 $member=(int)($in['member_id']??0);$token=$in['help_token']??'';
 if($member<=0||!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new InvalidArgumentException(fcc_mp_help_text('changed'));
 if(!(int)(fcc_partner_one("SELECT GET_LOCK('fcc_team_graph',10) ok")['ok']??0))throw new InvalidArgumentException(fcc_t('Povezivanje je u tijeku.'));
 try {
 database()->begin_transaction();
 try{
  $locks=[$actor,$member];sort($locks,SORT_NUMERIC);
  fcc_partner_query('SELECT user_id FROM users WHERE user_id IN (?,?) ORDER BY user_id FOR UPDATE',$locks);
  // Do not reuse a graph captured while this form was being rendered.
  unset($GLOBALS['fcc_team_graph_run']);$graph=fcc_team_graph();$primary=fcc_team_primary($actor,$graph);
  if(empty($graph[$actor])||(int)$graph[$actor]['status']!==1||!$primary||fcc_team_primary($member,$graph)!==$member||fcc_team_edge($member,$graph)!==$primary)throw new InvalidArgumentException(fcc_mp_help_text('denied'));
  $pending=fcc_mp_help_pending([$member])[$member]??[];
  if(!$pending){database()->commit();return 0;}
  if(!hash_equals(fcc_mp_help_token($member,$pending),$token))throw new InvalidArgumentException(fcc_mp_help_text('changed'));
  $keys=[];$count=0;
  foreach($pending as $source){
   $detail=json_encode(['source'=>$source['key'],'actor'=>$actor,'sponsor'=>$primary,'snapshot'=>$token]);
   if(fcc_mp_event($member,'help_resolved',$detail,'mp-resolve-'.substr(hash('sha256',$member.':'.$source['key']),0,48),(int)$source['step_id']))$count++;
   $keys=array_merge($keys,$source['notice_keys']);
  }
  $keys=array_values(array_unique($keys));
  if($keys){
   $inKeys=implode(',',array_fill(0,count($keys),'?'));
   // Only these help notices are retired; the audit rows and other team notices remain.
   fcc_partner_query("UPDATE fcc_partner_notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()),body='',expires_at=UTC_TIMESTAMP() WHERE user_id=? AND category='team' AND event_key IN ($inKeys)",array_merge([$primary],$keys));
   fcc_partner_query("UPDATE fcc_partner_push_deliveries d JOIN fcc_partner_notifications n ON n.id=d.notification_id SET d.status='skipped',d.last_code='help_resolved',d.updated_at=UTC_TIMESTAMP() WHERE n.user_id=? AND n.category='team' AND n.event_key IN ($inKeys) AND d.status IN ('pending','sending')",array_merge([$primary],$keys));
  }
  // The sponsor's existing revision polling now also observes this resolution on another device.
  $handled=json_encode(['member'=>$member,'actor'=>$actor,'snapshot'=>$token,'count'=>$count]);
  foreach(array_unique([$actor,$primary]) as $owner)fcc_mp_event($owner,'help_handled',$handled,'mp-handled-'.substr(hash('sha256',$member.':'.$token),0,48));
  database()->commit();return $count;
 }catch(Throwable $e){database()->rollback();throw $e;}
 }finally{fcc_partner_query("SELECT RELEASE_LOCK('fcc_team_graph')");}
}
/* /Custom code: FC-2026-10-14 */

function fcc_mp_member_progress(array $ids): array {
 $ids = array_values(array_unique(array_map('intval', $ids)));
 if(!$ids) return [];
 $in = implode(',', array_fill(0, count($ids), '?'));
 $out = [];
 foreach(fcc_partner_rows("SELECT u.user_id,u.name,r.first_entered_at FROM users u LEFT JOIN fcc_partner_rollout_access r ON r.user_id=u.user_id WHERE u.user_id IN ($in) AND u.status=1", $ids) as $u) {
  $out[(int)$u['user_id']] = ['user_id'=>(int)$u['user_id'], 'name'=>$u['name'], 'entered'=>!empty($u['first_entered_at']), 'cycle'=>false, 'completed'=>0, 'last_done'=>null, 'started_at'=>null, 'last_title'=>'', 'track'=>null, 'help'=>false, 'help_title'=>'', 'hard'=>false];
 }
 /* Custom code: FC-2026-10-15: when old cycles exist, the latest cycle consistently wins. */
 foreach(fcc_partner_rows("SELECT c.user_id,c.started_at,COUNT(o.outcome_id) completed,MAX(o.action_date) last_done FROM fcc_partner_journey_cycles c LEFT JOIN forever_business_daily_outcomes o ON o.journey_cycle_id=c.id AND o.status='done' WHERE c.user_id IN ($in) AND c.status IN ('active','completed') GROUP BY c.id ORDER BY c.id", $ids) as $c) {
  if(!isset($out[(int)$c['user_id']])) continue;
  $out[(int)$c['user_id']] = array_replace($out[(int)$c['user_id']], ['cycle'=>true, 'completed'=>(int)$c['completed'], 'last_done'=>$c['last_done'], 'started_at'=>$c['started_at']]);
 }
 /* /Custom code: FC-2026-10-15 */
 foreach(fcc_partner_rows("SELECT o.recorded_by_user_id uid,s.content_json FROM forever_business_daily_outcomes o JOIN fcc_partner_journey_steps s ON s.id=o.journey_step_id WHERE o.outcome_id IN (SELECT MAX(outcome_id) FROM forever_business_daily_outcomes WHERE recorded_by_user_id IN ($in) AND journey_cycle_id IS NOT NULL AND status='done' GROUP BY recorded_by_user_id)", $ids) as $r) {
  if(isset($out[(int)$r['uid']])) $out[(int)$r['uid']]['last_title'] = (string)((json_decode($r['content_json'], true) ?: [])['title'] ?? '');
 }
 foreach(fcc_partner_rows("SELECT user_id,kind,detail FROM fcc_partner_pilot_events WHERE user_id IN ($in) AND kind='track_chosen' ORDER BY id", $ids) as $e) {
  if(!isset($out[(int)$e['user_id']])) continue;
  if($e['kind'] === 'track_chosen' && fcc_mp_track_valid($e['detail'])) $out[(int)$e['user_id']]['track'] = $e['detail'];
 }
 /* Custom code: FC-2026-10-14: all sponsor surfaces share the same unresolved projection. */
 foreach(fcc_mp_help_pending($ids) as $uid=>$requests){
  if(!isset($out[$uid]))continue;
  $out[$uid]['help_requests']=$requests;$out[$uid]['help_token']=fcc_mp_help_token($uid,$requests);
  foreach($requests as $source){$out[$uid]['help']=$out[$uid]['help']||$source['help'];$out[$uid]['hard']=$out[$uid]['hard']||$source['hard'];$out[$uid]['help_title']=$source['title'];}
 }
 /* /Custom code: FC-2026-10-14 */
 return $out;
}

/** Grouped overview of a sponsor's direct members for the sponsor view and the Today panel. */
/* Custom code: FC-2026-10-15: contacts suppress reminders, never a new request for help. */
function fcc_mp_sponsor_overview(int $sponsor, ?DateTimeImmutable $now = null): array {
 $members = fcc_mp_direct_members($sponsor);
 $groups = ['help'=>[], 'inactive'=>[], 'not_started'=>[], 'progress'=>[], 'not_entered'=>[]];
 if(!$members) return ['groups'=>$groups, 'contacted'=>[], 'total'=>0, 'in_program'=>0, 'active_week'=>0];
 $progress = fcc_mp_member_progress(array_keys($members));
 $now = fcc_mp_followup_now($now);
 $today = $now->setTimezone(new DateTimeZone('Europe/Zagreb'))->setTime(0, 0);
 $primary = fcc_team_primary($sponsor, fcc_mp_graph());
 $followups = fcc_mp_followup_read($primary, array_keys($progress), $now)['states'] ?? [];
 $contacted = [];
 $inProgram = 0; $activeWeek = 0;
 foreach($progress as $uid => $p) {
  $p['phone'] = !empty($members[$uid]['contact_confirmed']) ? (string)($members[$uid]['phone'] ?? '') : '';
  $p['days_idle'] = $p['last_done'] ? (int)$today->diff(new DateTimeImmutable($p['last_done'], new DateTimeZone('Europe/Zagreb')))->days : null;
  if($p['cycle']) $inProgram++;
  if($p['days_idle'] !== null && $p['days_idle'] <= 7) $activeWeek++;
  $p['followup'] = $followups[$uid];
  $group = fcc_mp_followup_group($p);
  if(in_array($group, ['inactive', 'not_started', 'not_entered'], true) && !$p['followup']['due']) $contacted[] = $p;
  else $groups[$group][] = $p;
 }
 foreach($groups as &$list) usort($list, static fn($a, $b) => [$b['completed'], $a['name']] <=> [$a['completed'], $b['name']]);
 unset($list);
 usort($contacted, static fn($a, $b) => [$a['followup']['attempt'], $a['followup']['due_at'], $a['name']] <=> [$b['followup']['attempt'], $b['followup']['due_at'], $b['name']]);
 return ['groups'=>$groups, 'contacted'=>$contacted, 'total'=>count($progress), 'in_program'=>$inProgram, 'active_week'=>$activeWeek];
}
/* /Custom code: FC-2026-10-15 */

function fcc_mp_member_message(string $group, array $member): string {
 $first = explode(' ', trim((string)$member['name']))[0] ?? '';
 /* Custom code: FC-2026-10-15: a gentle second message after the first waiting period. */
 if(in_array($group, ['inactive','not_started','not_entered'], true) && (int)($member['followup']['attempt'] ?? 0) === 1) return fcc_mp_followup_text('second_message', ['{name}' => $first]);
 /* /Custom code: FC-2026-10-15 */
 return match($group) {
  'help' => sprintf(fcc_t('Bok %s! Vidim da ti je zapelo na koraku „%s”. Kada možemo kratko proći zajedno?'), $first, $member['help_title'] ?: $member['last_title']),
  'inactive' => sprintf(fcc_t('Bok %s! Kako ide Moj put? Ako ti treba pomoć sa sljedećim korakom, tu sam.'), $first),
  'not_started', 'not_entered' => sprintf(fcc_t('Bok %s! U FCC aplikaciji te čeka tvoj prvi korak programa Moj put. Treba ti oko 20 minuta: %s'), $first, SITE_URL.'partner?view=step'),
  default => sprintf(fcc_t('Bravo %s! Vidim da si već na %d. koraku Mojeg puta. Samo tako!'), $first, (int)$member['completed']),
 };
}

/** Weekly summary for sponsors on Monday morning, one notification per sponsor and week. */
function fcc_mp_weekly_summaries(?DateTimeImmutable $now = null): int {
 /* Custom code: FC-2026-10-15: reminders and their persisted contact times share the database clock. */
 $now = fcc_mp_followup_now($now)->setTimezone(new DateTimeZone('Europe/Zagreb'));
 /* /Custom code: FC-2026-10-15 */
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
  /* Custom code: FC-2026-10-15: the weekly reminder uses the same actionable counts as Today. */
  $overview = fcc_mp_sponsor_overview($sid, $now);
  $moved = $overview['active_week'];
  $waiting = count($overview['groups']['inactive']) + count($overview['groups']['not_started']);
  $help = count($overview['groups']['help']);
  /* /Custom code: FC-2026-10-15 */
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
