<?php
defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-15: explicit sponsor contacts, shared across confirmed owner accounts.
 * The immutable done outcome is the reset boundary. Opening FCC, starting a cycle,
 * drafts and feedback do not count. Existing events retain the audit without schema changes. */
const FCC_MP_FOLLOWUP_WAIT = 3 * 86400;

/** Contact timestamps and eligibility use the database clock that writes the audit. */
function fcc_mp_followup_now(?DateTimeImmutable $now = null): DateTimeImmutable {
 if($now) return $now->setTimezone(new DateTimeZone('UTC'));
 $value = (string)(fcc_partner_one('SELECT UTC_TIMESTAMP() followup_now')['followup_now'] ?? '');
 $stamp = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));
 if(!$stamp || $stamp->format('Y-m-d H:i:s') !== $value) throw new RuntimeException('Contact clock unavailable.');
 return $stamp;
}

function fcc_mp_followup_text(string $key, array $values = []): string {
 static $copy;
 $copy ??= require APP_PATH.'config/fcc_sponsor_followup_locales.php';
 return strtr($copy[fcc_locale()][$key] ?? $copy['en'][$key] ?? $key, $values);
}

function fcc_mp_followup_key(string $kind, int $member, array $detail): string {
 return ($kind === 'sponsor_contact' ? 'mp-contact-' : 'mp-contact-undo-')
  .substr(hash('sha256', json_encode([$member, (int)$detail['sponsor'], (int)$detail['progress'],
   (int)$detail['previous'], (int)$detail['attempt'], (int)($detail['target'] ?? 0)])), 0, 48);
}

/** Pure replay of a contact chain for one sponsor, member and real progress snapshot. */
function fcc_mp_followup_project(int $member, int $sponsor, int $progress, array $events, DateTimeImmutable $now): array {
 $stack = []; $revision = 0;
 usort($events, static fn($a, $b) => (int)$a['id'] <=> (int)$b['id']);
 foreach($events as $event) {
  $kind = (string)($event['kind'] ?? '');
  if(!in_array($kind, ['sponsor_contact', 'sponsor_contact_undo'], true) || (int)$event['user_id'] !== $member) continue;
  $d = json_decode((string)$event['detail'], true);
  if(!is_array($d) || (int)($d['sponsor'] ?? 0) !== $sponsor || (int)($d['progress'] ?? -1) !== $progress
   || (int)($d['actor'] ?? 0) <= 0 || (int)($d['previous'] ?? -1) !== $revision
   || !isset($d['attempt']) || !in_array($d['attempt'], [1, 2], true)
   || !is_string($d['snapshot'] ?? null) || !hash_equals(hash('sha256', json_encode([$member, $sponsor, $progress, $revision, count($stack)])), $d['snapshot'])
   || ($event['request_key'] ?? '') !== fcc_mp_followup_key($kind, $member, $d)) continue;
  $stamp = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string)$event['created_at'], new DateTimeZone('UTC'));
  if(!$stamp || $stamp->format('Y-m-d H:i:s') !== $event['created_at'] || $stamp > $now) continue;
  if($kind === 'sponsor_contact') {
   if($d['attempt'] !== count($stack) + 1 || count($stack) >= 2) continue;
   if(count($stack) === 1 && $stamp->getTimestamp() < $stack[0]['at'] + FCC_MP_FOLLOWUP_WAIT) continue;
   $stack[] = ['id' => (int)$event['id'], 'at' => $stamp->getTimestamp()];
  } else {
   if(!$stack || $d['attempt'] !== count($stack) || (int)($d['target'] ?? 0) !== end($stack)['id']) continue;
   array_pop($stack);
  }
  $revision = (int)$event['id'];
 }
 $attempt = count($stack);
 $dueAt = $attempt === 1 ? $stack[0]['at'] + FCC_MP_FOLLOWUP_WAIT : null;
 return ['attempt' => $attempt, 'revision' => $revision, 'progress' => $progress,
  'contact_id' => $stack ? end($stack)['id'] : 0, 'sent_at' => $stack ? end($stack)['at'] : null,
  'due_at' => $dueAt, 'due' => $attempt === 0 || ($attempt === 1 && $now->getTimestamp() >= $dueAt),
  'token' => hash('sha256', json_encode([$member, $sponsor, $progress, $revision, $attempt]))];
}

/** Read all contact history, without a rolling window that could revive a second message. */
function fcc_mp_followup_read(int $sponsor, array $ids, ?DateTimeImmutable $now = null): array {
 $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
 if(!$ids) return ['states' => [], 'events' => []];
 $now = fcc_mp_followup_now($now);
 $in = implode(',', array_fill(0, count($ids), '?')); $anchors = []; $byMember = []; $states = [];
 // All cycles are intentional: opening the first cycle is not a completed step.
 foreach(fcc_partner_rows("SELECT recorded_by_user_id uid,MAX(outcome_id) outcome_id FROM forever_business_daily_outcomes WHERE recorded_by_user_id IN ($in) AND journey_cycle_id IS NOT NULL AND journey_step_id IS NOT NULL AND status='done' GROUP BY recorded_by_user_id", $ids) as $row) $anchors[(int)$row['uid']] = (int)$row['outcome_id'];
 foreach(fcc_partner_rows("SELECT id,user_id,kind,detail,request_key,created_at FROM fcc_partner_pilot_events WHERE user_id IN ($in) AND kind IN ('sponsor_contact','sponsor_contact_undo') ORDER BY id", $ids) as $row) $byMember[(int)$row['user_id']][] = $row;
 foreach($ids as $id) $states[$id] = fcc_mp_followup_project($id, $sponsor, $anchors[$id] ?? 0, $byMember[$id] ?? [], $now);
 return ['states' => $states, 'events' => $byMember];
}

function fcc_mp_followup_group(array $member): string {
 if($member['help'] || $member['hard']) return 'help';
 if(!$member['entered'] || !$member['cycle']) return 'not_entered';
 if($member['completed'] === 0) return 'not_started';
 if($member['completed'] < 90 && $member['days_idle'] !== null && $member['days_idle'] >= 3) return 'inactive';
 return 'progress';
}

/** Only explicit confirmation writes an event. Clipboard and WhatsApp never call this. */
function fcc_mp_followup_record(int $actor, array $in, ?DateTimeImmutable $now = null): array {
 if($now && PHP_SAPI !== 'cli') throw new InvalidArgumentException(fcc_t('Vrijeme određuje sustav.'));
 $member = filter_var($in['member_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
 $token = $in['followup_token'] ?? null; $operation = $in['followup_operation'] ?? 'sent';
 $attempt = filter_var($in['followup_attempt'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2]]);
 if(!$member || !is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token) || !$attempt || !in_array($operation, ['sent', 'undo'], true)) throw new InvalidArgumentException(fcc_mp_followup_text('changed'));
 if(!(int)(fcc_partner_one("SELECT GET_LOCK('fcc_team_graph',10) ok")['ok'] ?? 0)) throw new InvalidArgumentException(fcc_t('Povezivanje je u tijeku.'));
 try {
  database()->begin_transaction();
  try {
   $locks = [$actor, $member]; sort($locks, SORT_NUMERIC);
   fcc_partner_query('SELECT user_id FROM users WHERE user_id IN (?,?) ORDER BY user_id FOR UPDATE', $locks);
   $now = fcc_mp_followup_now($now);
   unset($GLOBALS['fcc_team_graph_run']); $graph = fcc_team_graph(); $primary = fcc_team_primary($actor, $graph);
   if(empty($graph[$actor]) || (int)$graph[$actor]['status'] !== 1 || !$primary || fcc_team_primary($member, $graph) !== $member || fcc_team_edge($member, $graph) !== $primary) throw new InvalidArgumentException(fcc_mp_followup_text('denied'));
   $read = fcc_mp_followup_read($primary, [$member], $now); $state = $read['states'][$member];
   $kind = $operation === 'sent' ? 'sponsor_contact' : 'sponsor_contact_undo';
   if(!hash_equals($state['token'], $token)) {
    // A replay may acknowledge its own prior write, but never consume the next attempt.
    foreach($read['events'][$member] ?? [] as $event) {
     $prior = json_decode((string)$event['detail'], true);
     if($event['kind'] === $kind && is_array($prior) && ($prior['snapshot'] ?? '') === $token
      && (int)($prior['sponsor'] ?? 0) === $primary && (int)($prior['progress'] ?? -1) === $state['progress']
      && (int)($prior['attempt'] ?? 0) === $attempt && ($event['request_key'] ?? '') === fcc_mp_followup_key($kind, $member, $prior)) { database()->commit(); return ['changed' => false, 'attempt' => $state['attempt']]; }
    }
    throw new InvalidArgumentException(fcc_mp_followup_text('changed'));
   }
   if($operation === 'sent') {
    $p = fcc_mp_member_progress([$member])[$member] ?? null;
    if(!$p) throw new InvalidArgumentException(fcc_mp_followup_text('denied'));
    $today = $now->setTimezone(new DateTimeZone('Europe/Zagreb'))->setTime(0, 0);
    $p['days_idle'] = $p['last_done'] ? (int)$today->diff(new DateTimeImmutable($p['last_done'], new DateTimeZone('Europe/Zagreb')))->days : null;
    if(!in_array(fcc_mp_followup_group($p), ['inactive', 'not_started', 'not_entered'], true) || !$state['due'] || $attempt !== $state['attempt'] + 1) throw new InvalidArgumentException(fcc_mp_followup_text('not_due'));
   } elseif(!$state['contact_id'] || $attempt !== $state['attempt']) throw new InvalidArgumentException(fcc_mp_followup_text('changed'));
   $detail = ['sponsor' => $primary, 'actor' => $actor, 'progress' => $state['progress'], 'previous' => $state['revision'], 'attempt' => $attempt, 'snapshot' => $token];
   if($operation === 'undo') $detail['target'] = $state['contact_id'];
   $key = fcc_mp_followup_key($kind, $member, $detail);
   if(!fcc_mp_event($member, $kind, json_encode($detail), $key)) throw new RuntimeException('Contact event was not saved.');
   foreach(array_unique([$actor, $primary]) as $owner) {
    if(!fcc_mp_event($owner, 'sponsor_contact_handled', json_encode(['member' => $member, 'actor' => $actor, 'snapshot' => $token]), 'mp-contact-audit-'.substr(hash('sha256', $member.':'.$key), 0, 47))) throw new RuntimeException('Contact audit was not saved.');
   }
   database()->commit(); return ['changed' => true, 'attempt' => $operation === 'undo' ? $attempt - 1 : $attempt];
  } catch(Throwable $e) { database()->rollback(); throw $e; }
 } finally { fcc_partner_query("SELECT RELEASE_LOCK('fcc_team_graph')"); }
}

function fcc_mp_followup_date(int $timestamp): string {
 return (new DateTimeImmutable('@'.$timestamp))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('d. m. Y. H:i');
}
/* /Custom code: FC-2026-10-15 */
