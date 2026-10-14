<?php
declare(strict_types=1);
/* Custom code: FC-2026-10-14: sponsor resolution lifecycle.
 * Offline regression checks. Runs actual helper code with explicit boundary doubles only.
 * No application bootstrap, real database, network or production data is used. */
namespace FccSponsorHelpOffline;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use DateTimeImmutable;
use DateTimeZone;

if(PHP_SAPI !== 'cli') { http_response_code(404); exit(1); }
$source = $argv[1] ?? dirname(__DIR__);
$helperPath = rtrim($source, '/') . '/app/helpers/fcc_journey_tracks.php';
if(!is_file($helperPath)) throw new RuntimeException('Actual helper missing.');
$hashBefore = hash_file('sha256', $helperPath);
defined('ALTUMCODE') || define('ALTUMCODE', 66);
defined('APP_PATH') || define('APP_PATH', rtrim($source, '/') . '/app/');
defined('SITE_URL') || define('SITE_URL', 'https://offline.invalid/');
$code = (string) file_get_contents($helperPath);
if(!str_starts_with($code, '<?php')) throw new RuntimeException('Unexpected helper header.');
$formerWindowControl = in_array('--former-window-control', $argv, true);
if($formerWindowControl) {
    $code = str_replace("AND created_at>=UTC_TIMESTAMP()-INTERVAL 9 DAY AND (kind IN ('help_resolved','help_feedback') OR created_at>=UTC_TIMESTAMP()-INTERVAL 7 DAY)",
        "AND created_at>=UTC_TIMESTAMP()-INTERVAL 8 DAY AND (kind='help_resolved' OR created_at>=UTC_TIMESTAMP()-INTERVAL 7 DAY)", $code, $controlChanges);
    if($controlChanges !== 1) throw new RuntimeException('Former-window negative control no longer matches the frozen helper.');
}
eval('namespace FccSponsorHelpOffline; use InvalidArgumentException, RuntimeException, Throwable, Exception, DateTime, DateTimeImmutable, DateTimeZone;' . substr($code, 5));
foreach(['fcc_mp_help_project', 'fcc_mp_help_token'] as $required) {
    if(!function_exists(__NAMESPACE__ . '\\' . $required)) throw new RuntimeException('Frozen helper required: ' . $required);
}

$checks = [];
function check(bool $ok, string $name): void {
    $GLOBALS['checks'][] = ['name' => $name, 'passed' => $ok];
    if(!$ok) throw new RuntimeException('FAIL: ' . $name);
}
function event(int $id, int $uid, string $kind, string|array $detail, int $step = 70): array {
    return ['id' => $id, 'user_id' => $uid, 'step_id' => $step, 'kind' => $kind,
        'detail' => is_array($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE) : $detail,
        'created_at' => '2026-10-14 08:00:00', 'content_json' => json_encode(['day' => 8, 'title' => 'Konkretni korak'])];
}
function outcome(int $id = 101, int $uid = 7, string $difficulty = 'hard', int $needsHelp = 1): array {
    return ['outcome_id' => $id, 'recorded_by_user_id' => $uid, 'uid' => $uid,
        'journey_cycle_id' => 700, 'journey_step_id' => 71, 'step_id' => 71,
        'status' => 'done', 'difficulty' => $difficulty, 'needs_help' => $needsHelp,
        'action_date' => '2026-10-14', 'completion_mode' => 'standard',
        'created_at' => '2026-10-14 07:00:00', 'updated_at' => '2026-10-14 08:00:00',
        'content_json' => json_encode(['day' => 9, 'title' => 'Drugi konkretni korak']),
        'title' => 'Drugi konkretni korak'];
}
function resolution(int $id, int $uid, string $key): array {
    $step = str_starts_with($key, 'outcome:') || $key === 'event:20' ? 71 : 70;
    return event($id, $uid, 'help_resolved', ['source' => $key, 'actor' => 1, 'sponsor' => 1, 'snapshot' => str_repeat('a', 64)], $step)
        + ['request_key' => 'mp-resolve-' . substr(hash('sha256', $uid . ':' . $key), 0, 48)];
}
function requests(array $events, array $outcomes = [], int $uid = 7): array {
    $before = serialize([$events, $outcomes]);
    $project = fcc_mp_help_project($events, $outcomes);
    check(serialize([$events, $outcomes]) === $before, 'projection_keeps_inputs_' . count($GLOBALS['checks']));
    return $project[$uid] ?? [];
}
function keyOfLegacy(array $pending, int $outcomeId): string {
    foreach(array_keys($pending) as $key) if(str_starts_with((string) $key, 'outcome:' . $outcomeId . ':')) return (string) $key;
    throw new RuntimeException('Legacy source absent: ' . $outcomeId);
}

/* Real pure authorization functions. The database/graph reads stay explicit boundaries. */
function actualFunction(string $path, string $name): string {
    $tokens = token_get_all((string) file_get_contents($path));
    for($i = 0, $n = count($tokens); $i < $n; $i++) {
        if(!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
        $j = $i + 1;
        while($j < $n && (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE || $tokens[$j] === '&')) $j++;
        if(!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $name) continue;
        $text = ''; $depth = 0; $opened = false;
        for($k = $i; $k < $n; $k++) {
            $part = $tokens[$k]; $text .= is_array($part) ? $part[1] : $part;
            if($part === '{') { $opened = true; $depth++; }
            elseif($part === '}' && $opened && --$depth === 0) return $text;
        }
    }
    throw new RuntimeException('Actual function missing: ' . $name);
}
foreach(['fcc_team.php' => 'fcc_team_edge', 'fcc_team_accounts.php' => 'fcc_team_primary'] as $file => $name) {
    eval('namespace FccSponsorHelpOffline;' . actualFunction(APP_PATH . 'helpers/' . $file, $name));
}
function fcc_locale(): string { return 'hr'; }
function fcc_t(string $text, array $vars = [], ?string $locale = null): string { return strtr($text, $vars); }
function url(string $path): string { return 'https://offline.invalid/' . $path; }
function fcc_partner_contact_phone(string $phone, bool $required = false): string { return $phone; }
function fcc_team_graph(): array {
    if(isset($GLOBALS['fcc_team_graph_run']) && is_array($GLOBALS['fcc_team_graph_run'])) return $GLOBALS['fcc_team_graph_run'];
    $GLOBALS['fixture']['graph_reads']++; return $GLOBALS['fixture']['graph'];
}
function fcc_team_ready(): bool { return true; }
/* function_exists() in production uses global names even when the real source is evaluated
 * in this isolated namespace. These aliases preserve exactly that availability boundary. */
eval('namespace { function fcc_team_graph(): array { return \\FccSponsorHelpOffline\\fcc_team_graph(); } function fcc_team_edge(int $uid,array $graph): ?int { return \\FccSponsorHelpOffline\\fcc_team_edge($uid,$graph); } function fcc_t(string $text,array $vars=[],?string $locale=null): string { return \\FccSponsorHelpOffline\\fcc_t($text,$vars,$locale); } }');

final class MemoryDatabase {
    private ?array $snapshot = null;
    public int $insert_id = 0;
    public function begin_transaction(): bool {
        if($this->snapshot !== null) throw new RuntimeException('Unexpected nested transaction.');
        if(!$GLOBALS['fixture']['advisory_acquired']) throw new RuntimeException('Transaction starts before graph advisory lock.');
        $this->snapshot = $GLOBALS['fixture'];
        $GLOBALS['fixture']['lock_trace'][] = 'begin';
        $GLOBALS['fixture']['transactions'][] = 'begin'; return true;
    }
    public function commit(): bool {
        if($this->snapshot === null) throw new RuntimeException('Commit without transaction.');
        $this->snapshot = null; $GLOBALS['fixture']['lock_trace'][] = 'commit'; $GLOBALS['fixture']['transactions'][] = 'commit'; return true;
    }
    public function rollback(): bool {
        if($this->snapshot === null) throw new RuntimeException('Rollback without transaction.');
        $GLOBALS['fixture'] = $this->snapshot;
        $this->snapshot = null; $GLOBALS['fixture']['lock_trace'][] = 'rollback'; $GLOBALS['fixture']['transactions'][] = 'rollback'; return true;
    }
    public function active(): bool { return $this->snapshot !== null; }
}
function database(): MemoryDatabase { static $db; return $db ??= new MemoryDatabase(); }
function fcc_partner_one(string $sql, array $params = []): ?array {
    if($sql !== "SELECT GET_LOCK('fcc_team_graph',10) ok" || $params !== []) throw new RuntimeException('Unexpected single row query: ' . $sql);
    if(database()->active() || $GLOBALS['fixture']['advisory_acquired']) throw new RuntimeException('Graph advisory lock not acquired before transaction.');
    $GLOBALS['fixture']['sql'][] = [$sql, $params];
    $GLOBALS['fixture']['advisory_attempts']++;
    if(!$GLOBALS['fixture']['advisory_available']) return ['ok' => 0];
    $GLOBALS['fixture']['advisory_acquired'] = true; $GLOBALS['fixture']['lock_trace'][] = 'acquire';
    return ['ok' => 1];
}
function fcc_partner_query(string $sql, array $params = []): object {
    $f = &$GLOBALS['fixture'];
    $f['sql'][] = [$sql, $params];
    if($sql === "SELECT RELEASE_LOCK('fcc_team_graph')") {
        if(database()->active() || !$f['advisory_acquired'] || $params !== []) throw new RuntimeException('Graph lock released outside its owner or before closing transaction.');
        $f['advisory_acquired'] = false; $f['advisory_releases']++; $f['lock_trace'][] = 'release';
        return (object) ['affected_rows' => 0];
    }
    if(str_starts_with($sql, 'SELECT user_id FROM users WHERE user_id IN (?,?) ORDER BY user_id FOR UPDATE')) {
        if(!database()->active()) throw new RuntimeException('User locks outside transaction.');
        $sorted = $params; sort($sorted, SORT_NUMERIC);
        if($sorted !== $params) throw new RuntimeException('User locks out of order.');
        $f['locks'] = $params; return (object) ['affected_rows' => 0];
    }
    if(str_starts_with($sql, 'INSERT IGNORE INTO fcc_partner_pilot_events')) {
        if(!database()->active()) throw new RuntimeException('Event write outside transaction.');
        if($f['fail_write_after'] !== null && $f['event_writes'] >= $f['fail_write_after']) throw new RuntimeException('SYNTHETIC_EVENT_FAILURE');
        foreach($f['events'] as $row) if($row['user_id'] === $params[0] && ($row['request_key'] ?? '') === $params[4]) return (object) ['affected_rows' => 0];
        $id = max(array_column($f['events'], 'id') ?: [0]) + 1;
        $f['events'][] = event($id, $params[0], $params[2], $params[3], (int) ($params[1] ?? 0)) + ['request_key' => $params[4]];
        database()->insert_id = $id; $f['event_writes']++; return (object) ['affected_rows' => 1];
    }
    if(str_starts_with($sql, 'UPDATE fcc_partner_notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP())')) {
        if(!database()->active()) throw new RuntimeException('Notice write outside transaction.');
        if(!preg_match("~ WHERE user_id=\\? AND category='team' AND event_key IN \\(\\?(?:,\\?)*\\)$~", $sql)) throw new RuntimeException('Notice SQL scope differs from the allowlisted operation.');
        $owner = array_shift($params); $affected = 0;
        foreach($f['notices'] as &$notice) if($notice['user_id'] === $owner && $notice['category'] === 'team' && in_array($notice['event_key'], $params, true)) {
            $notice['read_at'] ??= '2026-10-14 09:00:00'; $notice['body'] = ''; $notice['expires_at'] = '2026-10-14 09:00:00'; $affected++;
        }
        unset($notice); return (object) ['affected_rows' => $affected];
    }
    if(str_starts_with($sql, 'UPDATE fcc_partner_push_deliveries d JOIN fcc_partner_notifications n')) {
        if(!database()->active()) throw new RuntimeException('Delivery write outside transaction.');
        if(!preg_match("~ WHERE n.user_id=\\? AND n.category='team' AND n.event_key IN \\(\\?(?:,\\?)*\\) AND d.status IN \\('pending','sending'\\)$~", $sql)) throw new RuntimeException('Delivery SQL scope differs from the allowlisted operation.');
        $owner = array_shift($params); $affected = 0;
        foreach($f['deliveries'] as &$delivery) {
            $notice = $f['notices'][$delivery['notification_id']];
            if($notice['user_id'] === $owner && $notice['category'] === 'team' && in_array($notice['event_key'], $params, true) && in_array($delivery['status'], ['pending', 'sending'], true)) {
                $delivery['status'] = 'skipped'; $delivery['last_code'] = 'help_resolved'; $delivery['updated_at'] = '2026-10-14 09:00:00'; $affected++;
            }
        }
        unset($delivery); return (object) ['affected_rows' => $affected];
    }
    throw new RuntimeException('Unexpected SQL operation: ' . $sql);
}
function fcc_partner_rows(string $sql, array $params = []): array {
    $f = &$GLOBALS['fixture']; $f['sql'][] = [$sql, $params];
    if(str_starts_with($sql, 'SELECT id,user_id,step_id,kind,detail,request_key,created_at FROM fcc_partner_pilot_events')) {
        if(!preg_match('/created_at>=UTC_TIMESTAMP\(\)-INTERVAL (\d+) DAY/', $sql, $outer)
            || !preg_match('/AND \((.*?) OR created_at>=UTC_TIMESTAMP\(\)-INTERVAL (\d+) DAY\)/', $sql, $inner)) throw new RuntimeException('Event time predicates differ from the allowlisted operation.');
        preg_match_all("/'([a-z_]+)'/", $inner[1], $special);
        $now = new DateTimeImmutable($f['now'], new DateTimeZone('UTC'));
        $outerCutoff = $now->modify('-' . $outer[1] . ' days')->format('Y-m-d H:i:s');
        $innerCutoff = $now->modify('-' . $inner[2] . ' days')->format('Y-m-d H:i:s');
        return array_values(array_filter($f['events'], static fn($e) => in_array($e['user_id'], $params, true)
            && in_array($e['kind'], ['stuck', 'help_feedback', 'help_resolved'], true)
            && $e['created_at'] >= $outerCutoff
            && (in_array($e['kind'], $special[1], true) || $e['created_at'] >= $innerCutoff)));
    }
    if(str_starts_with($sql, 'SELECT o.recorded_by_user_id uid,o.outcome_id,o.journey_step_id,o.difficulty,o.needs_help')) {
        if(!preg_match('/o.action_date>=CURDATE\(\)-INTERVAL (\d+) DAY/', $sql, $days)) throw new RuntimeException('Outcome calendar predicate differs from the allowlisted operation.');
        $date = $f['database_date'] ?? substr($f['now'], 0, 10);
        $cutoff = (new DateTimeImmutable($date, new DateTimeZone('UTC')))->modify('-' . $days[1] . ' days')->format('Y-m-d');
        return array_values(array_filter($f['outcomes'], static fn($o) => in_array($o['recorded_by_user_id'], $params, true)
            && $o['status'] === 'done' && $o['journey_cycle_id'] !== null && $o['action_date'] >= $cutoff
            && ($o['needs_help'] === 1 || $o['difficulty'] === 'hard')));
    }
    if(str_starts_with($sql, 'SELECT u.user_id,u.name,r.first_entered_at')) {
        return array_values(array_map(static fn($u) => ['user_id' => $u['user_id'], 'name' => $u['name'], 'first_entered_at' => '2026-10-01 08:00:00'], array_filter($f['graph'], static fn($u) => in_array($u['user_id'], $params, true) && $u['status'] === 1)));
    }
    if(str_starts_with($sql, 'SELECT c.user_id,c.started_at,COUNT(o.outcome_id) completed')) {
        $out = [];
        foreach($f['cycles'] as $c) if(in_array($c['user_id'], $params, true) && in_array($c['status'], ['active', 'completed'], true)) {
            $rows = array_values(array_filter($f['outcomes'], static fn($o) => $o['journey_cycle_id'] === $c['id'] && $o['status'] === 'done'));
            $out[] = ['user_id' => $c['user_id'], 'started_at' => $c['started_at'], 'completed' => count($rows), 'last_done' => $rows ? max(array_column($rows, 'action_date')) : null];
        }
        return $out;
    }
    if(str_starts_with($sql, 'SELECT o.recorded_by_user_id uid,s.content_json')) {
        $out = [];
        foreach($params as $uid) {
            $rows = array_values(array_filter($f['outcomes'], static fn($o) => $o['recorded_by_user_id'] === $uid && $o['status'] === 'done' && $o['journey_cycle_id'] !== null));
            usort($rows, static fn($a, $b) => $b['outcome_id'] <=> $a['outcome_id']);
            if($rows) $out[] = ['uid' => $uid, 'content_json' => $rows[0]['content_json']];
        }
        return $out;
    }
    if(str_starts_with($sql, "SELECT user_id,kind,detail FROM fcc_partner_pilot_events WHERE user_id IN")) {
        return array_values(array_filter($f['events'], static fn($e) => in_array($e['user_id'], $params, true) && $e['kind'] === 'track_chosen'));
    }
    throw new RuntimeException('Unexpected SQL read: ' . $sql);
}
function resetFixture(): void {
    if(database()->active()) throw new RuntimeException('Previous transaction not closed.');
    if(!empty($GLOBALS['fixture']['advisory_acquired'])) throw new RuntimeException('Previous advisory lock not released.');
    unset($GLOBALS['fcc_team_graph_run']);
    $graph = [];
    foreach([1, 2, 7, 8] as $id) $graph[$id] = ['user_id' => $id, 'name' => 'Offline ' . $id, 'status' => 1, 'current_fbo' => '36000000000' . $id,
        'fbo_id' => '36000000000' . $id, 'sponsor_user_id' => $id >= 7 ? 1 : null, 'sponsor_fbo_id' => $id >= 7 ? '360000000001' : '',
        'primary_user_id' => $id, 'relationship_version' => 1, 'is_privacy_requested' => 0, 'contact_confirmed' => 1, 'phone' => '+385991234567', 'manager' => false];
    $outcomes = [outcome()];
    for($id = 102; $id <= 115; $id++) $outcomes[] = outcome($id, 7, 'easy', 0);
    $GLOBALS['fixture'] = ['now' => '2026-10-14 08:00:00', 'graph' => $graph, 'graph_reads' => 0, 'events' => [event(10, 7, 'stuck', '8|Konkretni korak|Molim pomoć.')],
        'outcomes' => $outcomes, 'cycles' => [['id' => 700, 'user_id' => 7, 'status' => 'active', 'version' => 16, 'started_at' => '2026-10-01 08:00:00']],
        'steps' => [['id' => 70, 'user_id' => 7, 'cycle_id' => 700, 'content_json' => '{"day":8,"title":"Konkretni korak"}', 'draft_json' => '{"note":"Bilješka ostaje."}', 'draft_version' => 3]],
        'notices' => [], 'deliveries' => [], 'transactions' => [], 'sql' => [], 'locks' => [], 'event_writes' => 0, 'fail_write_after' => null,
        'advisory_available' => true, 'advisory_acquired' => false, 'advisory_attempts' => 0, 'advisory_releases' => 0, 'lock_trace' => []];
    $pending = fcc_mp_help_pending([7])[7]; $keys = [];
    foreach($pending as $request) $keys = array_merge($keys, $request['notice_keys']);
    $keys = array_values(array_unique($keys));
    foreach($keys as $i => $key) $GLOBALS['fixture']['notices'][$i + 1] = ['id' => $i + 1, 'user_id' => 1, 'category' => 'team', 'event_key' => $key, 'read_at' => null, 'body' => 'Open request', 'expires_at' => '2026-10-17 08:00:00'];
    foreach(['mp:milestone:7:15', 'team:7:1:step:2026-10-14:0', 'mp:request:8:10'] as $key) {
        $id = count($GLOBALS['fixture']['notices']) + 1;
        $GLOBALS['fixture']['notices'][$id] = ['id' => $id, 'user_id' => 1, 'category' => 'team', 'event_key' => $key, 'read_at' => null, 'body' => 'Unrelated', 'expires_at' => '2026-10-17 08:00:00'];
    }
    $id = count($GLOBALS['fixture']['notices']) + 1;
    $GLOBALS['fixture']['notices'][$id] = ['id' => $id, 'user_id' => 2, 'category' => 'team', 'event_key' => $keys[0], 'read_at' => null, 'body' => 'Foreign owner', 'expires_at' => '2026-10-17 08:00:00'];
    foreach($GLOBALS['fixture']['notices'] as $notice) foreach(['pending', 'sending', 'sent'] as $status) $GLOBALS['fixture']['deliveries'][] = ['notification_id' => $notice['id'], 'status' => $status, 'last_code' => '', 'updated_at' => '2026-10-14 08:00:00'];
    $GLOBALS['fixture']['sql'] = [];
}
function historySnapshot(): string { $f = $GLOBALS['fixture']; return serialize([$f['outcomes'], $f['steps'], $f['cycles']]); }
function mutableSnapshot(): string { $f = $GLOBALS['fixture']; return serialize([$f['events'], $f['outcomes'], $f['steps'], $f['cycles'], $f['notices'], $f['deliveries']]); }
function currentToken(): string { return fcc_mp_help_token(7, fcc_mp_help_pending([7])[7] ?? []); }
function deny(callable $call, string $name): void {
    $before = mutableSnapshot(); $denied = false;
    try { $call(); } catch(InvalidArgumentException $e) { $denied = true; }
    check($denied, $name . '_rejected'); check(mutableSnapshot() === $before, $name . '_no_mutation'); check(!database()->active(), $name . '_transaction_closed');
    check(!$GLOBALS['fixture']['advisory_acquired'], $name . '_advisory_not_held');
}

/* Negative control uses the former production-candidate time predicate. All other
 * function code, rows and projection come from the actual frozen helper. */
$pendingActual = actualFunction($helperPath, 'fcc_mp_help_pending');
$newWindow = "AND created_at>=UTC_TIMESTAMP()-INTERVAL 9 DAY AND (kind IN ('help_resolved','help_feedback') OR created_at>=UTC_TIMESTAMP()-INTERVAL 7 DAY)";
$oldWindow = "AND created_at>=UTC_TIMESTAMP()-INTERVAL 8 DAY AND (kind='help_resolved' OR created_at>=UTC_TIMESTAMP()-INTERVAL 7 DAY)";
$pendingFormer = str_replace($newWindow, $oldWindow, $pendingActual, $changedWindows);
if($changedWindows !== 1) throw new RuntimeException('Frozen time predicate changed. Review the negative control before running.');
eval('namespace FccSponsorHelpFormerWindow; function fcc_partner_rows(string $sql,array $params=[]):array { return \\FccSponsorHelpOffline\\fcc_partner_rows($sql,$params); } function fcc_mp_help_project(array $events,array $outcomes):array { return \\FccSponsorHelpOffline\\fcc_mp_help_project($events,$outcomes); } ' . $pendingFormer);

/* Execute the actual CSRF guard and actual resolution action branch from Partner.php. */
$controllerPath = APP_PATH . 'controllers/Partner.php';
$controllerCode = (string) file_get_contents($controllerPath);
$postStart = strpos($controllerCode, "if(\$_SERVER['REQUEST_METHOD']==='POST' && \$contact_export_error===null)");
if($postStart === false || !preg_match('/if\(!isset\(\$_POST\[\'token\'\]\).*?throw new \\\\InvalidArgumentException\(.*?\);/', substr($controllerCode, $postStart), $guardMatch)) throw new RuntimeException('Actual general POST CSRF guard absent.');
if(!preg_match('/if\(\$action===\'mp_help_resolve\'\)\{[^\n]+\}/', $controllerCode, $branchMatch)) throw new RuntimeException('Actual resolution controller branch absent.');
eval('namespace Altum; class Csrf { public static function get(): string { return "offline-session-token"; } } class Alerts { public static function add_success($message): void {} }');
$dispatch = eval('namespace FccSponsorHelpOffline; return static function(int $uid,array $input): string { $_POST=$input; ' . $guardMatch[0] . ' $action=(string)($input["action"]??""); ' . $branchMatch[0] . ' throw new RuntimeException("Unexpected action."); };');

final class OfflineView {
    public object $user;
    public function __construct() { $this->user = (object) ['user_id' => 1]; }
    public function render(string $path): string {
        $h = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $token = '<input type="hidden" name="token" value="offline-session-token">';
        $template = (string) file_get_contents($path);
        if(!str_starts_with($template, '<?php')) throw new RuntimeException('Unexpected actual view header.');
        ob_start();
        try { eval('namespace FccSponsorHelpOffline;' . substr($template, 5)); return (string) ob_get_clean(); }
        catch(Throwable $e) { ob_end_clean(); throw $e; }
    }
}

try {
    check(fcc_mp_help_project([], []) === [], 'empty_projection');
    $stuck = event(10, 7, 'stuck', '8|Konkretni korak|Ne znam kako početi.');
    $legacy = outcome();
    $pending = requests([$stuck], [$legacy]);
    check(count($pending) === 2 && isset($pending['event:10']), 'independent_stuck_and_legacy_requests');
    foreach($pending as $key => $row) {
        check(is_array($row) && ($row['key'] ?? '') === $key, 'request_identity_' . $key);
        foreach(['step_id', 'title', 'help', 'hard', 'notice_keys'] as $field) check(array_key_exists($field, $row), 'request_field_' . $field . '_' . $key);
        check(is_array($row['notice_keys']), 'notice_keys_array_' . $key);
    }
    check($pending['event:10']['step_id'] === 70 && $pending['event:10']['title'] === 'Konkretni korak', 'stuck_references_actual_step');

    $olderNine = event(9, 7, 'stuck', '8|Starija molba|Raniji događaj.');
    $newerTen = event(10, 7, 'stuck', '9|Novija molba|Kasniji događaj.');
    $chronological = requests([$newerTen, $olderNine]);
    check(array_keys($chronological) === ['event:9','event:10'] && end($chronological)['title'] === 'Novija molba', 'latest_title_across_numeric_id_boundary');
    $olderHighId = event(999, 7, 'stuck', '8|Starija molba|Starije vrijeme.');
    $olderHighId['created_at'] = '2026-10-13 08:00:00';
    $newerLegacy = outcome(); $newerLegacy['updated_at'] = '2026-10-14 08:00:00';
    $mixed = requests([$olderHighId], [$newerLegacy]);
    check(end($mixed)['title'] === 'Drugi konkretni korak', 'newer_legacy_title_uses_source_time');
    $newerTen['created_at'] = '2026-10-14 09:00:00';
    $mixed = requests([$newerTen], [$newerLegacy]);
    check(end($mixed)['title'] === 'Novija molba', 'newer_event_title_uses_source_time_before_legacy_key');
    $coachQuery = actualFunction(APP_PATH . 'helpers/fcc_journey_pilot.php', 'fcc_jp_state');
    check(str_contains($coachQuery, "AND kind NOT IN ('help_resolved','help_handled') ORDER BY id DESC LIMIT 20"), 'coach_window_excludes_internal_resolution_audit');

    $legacyKey = keyOfLegacy($pending, 101);
    check($pending[$legacyKey]['help'] === true && $pending[$legacyKey]['hard'] === true, 'legacy_keeps_help_and_hard');

    $token = fcc_mp_help_token(7, $pending);
    check((bool) preg_match('/^[a-f0-9]{64}$/D', $token), 'token_sha256_shape');
    check(fcc_mp_help_token(7, array_reverse($pending, true)) === $token, 'token_order_independent');
    check(fcc_mp_help_token(8, $pending) !== $token, 'token_binds_member');
    check(fcc_mp_help_token(7, [$legacyKey => $pending[$legacyKey]]) !== $token, 'token_binds_whole_set');

    $resolved = requests([$stuck, resolution(11, 7, 'event:10')], [$legacy]);
    check(count($resolved) === 1 && isset($resolved[$legacyKey]), 'resolution_closes_only_named_source');
    check(isset(requests([$stuck, resolution(11, 8, 'event:10')])['event:10']), 'foreign_member_marker_cannot_resolve');
    check(isset(requests([$stuck, resolution(11, 7, 'event:999')])['event:10']), 'unknown_source_cannot_resolve');
    check(isset(requests([$stuck, event(11, 7, 'help_resolved', '{invalid')])['event:10']), 'malformed_marker_cannot_resolve');
    foreach(['missing_actor', 'zero_actor', 'missing_sponsor', 'negative_sponsor', 'missing_snapshot', 'invalid_snapshot', 'missing_request_key', 'foreign_request_key', 'wrong_step', 'invalid_source'] as $case) {
        $forged = resolution(11, 7, 'event:10');
        $detail = json_decode($forged['detail'], true);
        switch($case) {
            case 'missing_actor': unset($detail['actor']); break;
            case 'zero_actor': $detail['actor'] = 0; break;
            case 'missing_sponsor': unset($detail['sponsor']); break;
            case 'negative_sponsor': $detail['sponsor'] = -1; break;
            case 'missing_snapshot': unset($detail['snapshot']); break;
            case 'invalid_snapshot': $detail['snapshot'] = str_repeat('g', 64); break;
            case 'missing_request_key': unset($forged['request_key']); break;
            case 'foreign_request_key': $forged['request_key'] = resolution(11, 8, 'event:10')['request_key']; break;
            case 'wrong_step': $forged['step_id'] = 71; break;
            case 'invalid_source': $detail['source'] = 'event:0'; break;
        }
        $forged['detail'] = json_encode($detail);
        check(isset(requests([$stuck, $forged])['event:10']), 'invalid_marker_' . $case . '_cannot_resolve');
    }
    $newStuck = event(12, 7, 'stuck', '8|Konkretni korak|Ponovno trebam pomoć.');
    check(array_keys(requests([$stuck, resolution(11, 7, 'event:10'), $newStuck])) === ['event:12'], 'new_same_day_request_survives_old_resolution');

    $feedback = event(20, 7, 'help_feedback', ['outcome_id' => 101, 'answer' => 'help'], 71);
    $fromFeedback = requests([$feedback], [$legacy]);
    check(count($fromFeedback) === 1 && isset($fromFeedback['event:20']), 'feedback_supersedes_matching_legacy');
    check($fromFeedback['event:20']['help'] === true && $fromFeedback['event:20']['hard'] === true, 'help_feedback_keeps_reason');
    $easy = event(21, 7, 'help_feedback', ['outcome_id' => 101, 'answer' => 'easy'], 71);
    check(requests([$feedback, $easy], [outcome(101, 7, 'easy', 0)]) === [], 'later_easy_suppresses_old_feedback_and_legacy');
    $hard = event(22, 7, 'help_feedback', ['outcome_id' => 101, 'answer' => 'hard'], 71);
    $hardPending = requests([$feedback, $easy, $hard], [outcome(101, 7, 'hard', 0)]);
    check(array_keys($hardPending) === ['event:22'] && !$hardPending['event:22']['help'] && $hardPending['event:22']['hard'], 'later_hard_is_distinct_request');
    check(array_keys(requests([$feedback, resolution(21, 7, 'event:20'), event(22, 7, 'help_feedback', ['outcome_id' => 101, 'answer' => 'help'], 71)], [$legacy])) === ['event:22'], 'new_feedback_after_resolution_reappears');
    $foreignFeedback = event(20, 8, 'help_feedback', ['outcome_id' => 101, 'answer' => 'easy'], 71);
    check(isset(requests([$foreignFeedback], [$legacy])[$legacyKey]), 'foreign_feedback_cannot_suppress_legacy');
    $foreignOwnerPending = requests([event(23, 7, 'help_feedback', ['outcome_id' => 101, 'answer' => 'easy'], 71)], [outcome(101, 8)], 8);
    check(count($foreignOwnerPending) === 1 && str_starts_with((string) array_key_first($foreignOwnerPending), 'outcome:101:'), 'feedback_cannot_suppress_other_owners_outcome');

    $revision = $legacy;
    $revision['updated_at'] = '2026-10-14 08:00:01';
    $revision['needs_help'] = 0;
    $revised = requests([resolution(31, 7, $legacyKey)], [$revision]);
    $revisionKey = keyOfLegacy($revised, 101);
    check($revisionKey !== $legacyKey && count($revised) === 1, 'new_legacy_revision_survives_old_resolution');
    check(fcc_mp_help_token(7, $revised) !== fcc_mp_help_token(7, [$legacyKey => $pending[$legacyKey]]), 'token_changes_for_revision');
    check(requests([], [outcome(102, 7, 'easy', 0)]) === [], 'easy_outcome_does_not_request_help');
    $unordered = [$hard, $stuck, $easy, $feedback, $newStuck, resolution(13, 7, 'event:10')];
    $forward = requests($unordered, [$legacy]);
    $backward = requests(array_reverse($unordered), [$legacy]);
    check(fcc_mp_help_token(7, $forward) === fcc_mp_help_token(7, $backward), 'projection_feedback_is_chronological_not_input_order');

    /* Calendar outcomes remain eligible longer than a rolling seven-day event window. */
    resetFixture();
    $agedOutcome = outcome(); $agedOutcome['action_date'] = '2026-10-07'; $agedOutcome['updated_at'] = '2026-10-07 07:00:00';
    $agedFeedback = event(20, 7, 'help_feedback', ['outcome_id' => 101, 'answer' => 'help'], 71);
    $agedFeedback['created_at'] = '2026-10-07 07:00:00';
    $GLOBALS['fixture']['events'] = [$agedFeedback]; $GLOBALS['fixture']['outcomes'] = [$agedOutcome];
    $GLOBALS['fixture']['now'] = '2026-10-13 23:00:00';
    $beforeRollingBoundary = fcc_mp_help_pending([7])[7];
    check(array_keys($beforeRollingBoundary) === ['event:20'], 'feedback_has_immutable_identity_before_rolling_boundary');
    $GLOBALS['fixture']['now'] = '2026-10-14 08:00:00';
    $afterRollingBoundary = fcc_mp_help_pending([7])[7];
    check(array_keys($afterRollingBoundary) === ['event:20'], 'seven_days_one_hour_feedback_keeps_identity');
    check(fcc_mp_help_token(7, $afterRollingBoundary) === fcc_mp_help_token(7, $beforeRollingBoundary), 'clock_advance_does_not_change_feedback_token');
    $GLOBALS['fixture']['events'][] = resolution(21, 7, 'event:20');
    $agedStuck = event(30, 7, 'stuck', '8|Prestara molba|Ostaje izvan prozora.'); $agedStuck['created_at'] = '2026-10-07 07:00:00';
    $GLOBALS['fixture']['events'][] = $agedStuck;
    $negativeControl = \FccSponsorHelpFormerWindow\fcc_mp_help_pending([7]);
    check(count($negativeControl[7] ?? []) === 1 && str_starts_with((string) array_key_first($negativeControl[7]), 'outcome:101:'), 'former_query_reproduces_resolved_feedback_reopening_as_legacy');
    check(fcc_mp_help_pending([7]) === [], 'current_query_keeps_seven_days_one_hour_feedback_resolved_and_stuck_expired');

    /* UTC event date can precede the Zagreb calendar action date at midnight. */
    $GLOBALS['fixture']['now'] = '2026-10-14 23:30:00';
    $agedFeedback['created_at'] = '2026-10-06 22:00:00';
    $GLOBALS['fixture']['events'] = [$agedFeedback, resolution(21, 7, 'event:20')];
    $negativeMidnight = \FccSponsorHelpFormerWindow\fcc_mp_help_pending([7]);
    check(count($negativeMidnight[7] ?? []) === 1, 'former_outer_eight_days_also_reproduces_zagreb_midnight_boundary');
    check(fcc_mp_help_pending([7]) === [], 'nine_day_identity_window_covers_zagreb_midnight_boundary');

    /* A true legacy source hashes stored revision fields, never the clock or date window. */
    $GLOBALS['fixture']['now'] = '2026-10-14 08:00:00'; $GLOBALS['fixture']['events'] = [];
    $legacyAtMorning = fcc_mp_help_pending([7])[7]; $stableLegacyKey = keyOfLegacy($legacyAtMorning, 101);
    $GLOBALS['fixture']['now'] = '2026-10-14 23:30:00';
    check(array_keys(fcc_mp_help_pending([7])[7]) === [$stableLegacyKey], 'legacy_identity_does_not_change_with_clock');
    $GLOBALS['fixture']['events'][] = resolution(31, 7, $stableLegacyKey);
    check(fcc_mp_help_pending([7]) === [], 'resolved_legacy_source_stays_resolved_through_same_calendar_day');
    $GLOBALS['fixture']['now'] = '2026-10-15 00:00:00';
    check(fcc_mp_help_pending([7]) === [], 'legacy_outcome_leaves_window_without_new_source');

    resetFixture();
    $beforeHistory = historySnapshot(); $beforeEvents = $GLOBALS['fixture']['events'];
    $beforePending = fcc_mp_help_pending([7])[7];
    $beforeNotices = $GLOBALS['fixture']['notices']; $beforeDeliveries = $GLOBALS['fixture']['deliveries'];
    $visibleKeys = [];
    foreach($beforePending as $request) $visibleKeys = array_merge($visibleKeys, $request['notice_keys']);
    $visibleKeys = array_values(array_unique($visibleKeys));
    $oldToken = currentToken();
    $beforeOverview = fcc_mp_sponsor_overview(1);
    check(count($beforeOverview['groups']['help']) === 1 && $beforeOverview['total'] === 2, 'one_member_with_two_requests_counts_once');
    $result = fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => $oldToken]);
    check($result === 2, 'current_sponsor_resolves_whole_visible_card');
    check(($GLOBALS['fixture']['locks'] ?? []) === [1, 7], 'actor_and_member_are_locked_in_order');
    check($GLOBALS['fixture']['transactions'] === ['begin', 'commit'], 'resolution_is_one_transaction');
    check($GLOBALS['fixture']['lock_trace'] === ['acquire', 'begin', 'commit', 'release'] && $GLOBALS['fixture']['advisory_releases'] === 1, 'graph_advisory_lock_wraps_successful_transaction');
    check(historySnapshot() === $beforeHistory, 'all_outcomes_steps_drafts_cycles_unchanged');
    check(array_slice($GLOBALS['fixture']['events'], 0, count($beforeEvents)) === $beforeEvents, 'original_pilot_events_unchanged');
    $afterOverview = fcc_mp_sponsor_overview(1);
    check(count($afterOverview['groups']['help']) === 0, 'help_member_count_drops_after_resolution');
    check(count($afterOverview['groups']['progress']) === 1 && $afterOverview['groups']['progress'][0]['completed'] === 15, 'member_keeps_15_of_90_and_returns_to_progress');
    check((fcc_mp_help_pending([7])[7] ?? []) === [], 'historical_hard_and_help_do_not_reopen');
    $markers = array_values(array_filter($GLOBALS['fixture']['events'], static fn($e) => $e['kind'] === 'help_resolved'));
    check(count($markers) === 2, 'one_immutable_marker_per_source');
    $markerSources = [];
    foreach($markers as $marker) {
        $detail = json_decode($marker['detail'], true); $markerSources[] = $detail['source'];
        check($marker['user_id'] === 7 && $detail['actor'] === 1 && $detail['sponsor'] === 1 && $detail['snapshot'] === $oldToken, 'marker_attribution_' . $marker['id']);
    }
    sort($markerSources); $expectedSources = array_keys($beforePending); sort($expectedSources);
    check($markerSources === $expectedSources, 'markers_match_exact_visible_sources');
    $handled = array_values(array_filter($GLOBALS['fixture']['events'], static fn($e) => $e['kind'] === 'help_handled'));
    check(count($handled) === 1 && $handled[0]['user_id'] === 1, 'sponsor_revision_has_separate_handled_event');
    foreach($GLOBALS['fixture']['notices'] as $id => $notice) {
        $related = $beforeNotices[$id]['user_id'] === 1 && in_array($notice['event_key'], $visibleKeys, true);
        check($related ? $notice['read_at'] !== null && $notice['body'] === '' && $notice['expires_at'] === '2026-10-14 09:00:00' : $notice === $beforeNotices[$id], 'only_exact_owner_notice_retired_' . $id);
    }
    foreach($GLOBALS['fixture']['deliveries'] as $id => $delivery) {
        $original = $beforeDeliveries[$id]; $notice = $beforeNotices[$original['notification_id']];
        $related = $notice['user_id'] === 1 && in_array($notice['event_key'], $visibleKeys, true) && in_array($original['status'], ['pending', 'sending'], true);
        check($related ? $delivery['status'] === 'skipped' && $delivery['last_code'] === 'help_resolved' : $delivery === $original, 'only_exact_pending_delivery_retired_' . $id);
    }
    $emptySnapshot = mutableSnapshot();
    check(fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => $oldToken]) === 0 && mutableSnapshot() === $emptySnapshot, 'repeat_on_empty_set_is_noop');
    $GLOBALS['fixture']['events'][] = event(50, 7, 'stuck', '8|Konkretni korak|Nova molba istoga dana.');
    check(count(fcc_mp_sponsor_overview(1)['groups']['help']) === 1, 'new_request_is_visible_again');
    deny(static fn() => fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => $oldToken]), 'old_token_cannot_close_new_request');
    check(fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => currentToken()]) === 1, 'new_visible_token_resolves_new_request');

    resetFixture(); $displayed = currentToken();
    $GLOBALS['fixture']['events'][] = event(20, 7, 'stuck', '9|Treći razlog|Stiglo nakon prikaza.');
    deny(static fn() => fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => $displayed]), 'added_source_invalidates_entire_snapshot');
    check(count(fcc_mp_help_pending([7])[7]) === 3, 'stale_snapshot_keeps_all_three_requests_open');
    resetFixture(); $displayed = currentToken();
    $GLOBALS['fixture']['outcomes'][0]['updated_at'] = '2026-10-14 08:00:01';
    deny(static fn() => fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => $displayed]), 'changed_legacy_revision_invalidates_snapshot');

    resetFixture();
    $GLOBALS['fixture']['advisory_available'] = false;
    deny(static fn() => fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => currentToken()]), 'busy_graph_advisory_lock');
    check($GLOBALS['fixture']['transactions'] === [] && $GLOBALS['fixture']['advisory_attempts'] === 1 && $GLOBALS['fixture']['advisory_releases'] === 0, 'busy_graph_lock_has_no_transaction_or_unowned_release');
    resetFixture();
    deny(static fn() => fcc_mp_help_resolve(2, ['member_id' => 7, 'help_token' => currentToken()]), 'foreign_sponsor');
    check($GLOBALS['fixture']['advisory_releases'] === 1 && end($GLOBALS['fixture']['transactions']) === 'rollback', 'graph_advisory_lock_released_after_scope_exception');
    $GLOBALS['fixture']['graph'][2]['manager'] = true; $GLOBALS['fixture']['graph'][2]['type'] = 1;
    deny(static fn() => fcc_mp_help_resolve(2, ['member_id' => 7, 'help_token' => currentToken()]), 'broader_admin_or_manager_role_is_not_direct_sponsorship');
    resetFixture();
    fcc_mp_graph();
    $GLOBALS['fcc_team_graph_run'] = $GLOBALS['fixture']['graph'];
    $GLOBALS['fixture']['graph'][7]['sponsor_user_id'] = 2;
    $GLOBALS['fixture']['graph'][7]['sponsor_fbo_id'] = $GLOBALS['fixture']['graph'][2]['current_fbo'];
    deny(static fn() => fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => currentToken()]), 'former_sponsor_even_if_render_graph_cached');
    check(fcc_mp_help_resolve(2, ['member_id' => 7, 'help_token' => currentToken()]) === 2, 'new_current_sponsor_is_authorized');
    foreach(['actor_inactive', 'member_inactive', 'member_private', 'sponsor_private', 'member_fbo_changed', 'member_is_unapproved_alias'] as $case) {
        resetFixture();
        match($case) {
            'actor_inactive' => $GLOBALS['fixture']['graph'][1]['status'] = 0,
            'member_inactive' => $GLOBALS['fixture']['graph'][7]['status'] = 0,
            'member_private' => $GLOBALS['fixture']['graph'][7]['is_privacy_requested'] = 1,
            'sponsor_private' => $GLOBALS['fixture']['graph'][1]['is_privacy_requested'] = 1,
            'member_fbo_changed' => $GLOBALS['fixture']['graph'][7]['current_fbo'] = '999999999999',
            'member_is_unapproved_alias' => $GLOBALS['fixture']['graph'][7]['primary_user_id'] = 8,
        };
        deny(static fn() => fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => currentToken()]), $case);
    }
    resetFixture(); $emptyToken = currentToken(); fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => $emptyToken]);
    deny(static fn() => fcc_mp_help_resolve(2, ['member_id' => 7, 'help_token' => $emptyToken]), 'empty_set_still_requires_direct_sponsor');
    resetFixture();
    foreach([null, [], 'invalid', str_repeat('a', 63), str_repeat('a', 64)] as $invalid) deny(static fn() => fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => $invalid]), 'invalid_or_unmatched_token_' . get_debug_type($invalid) . '_' . count($checks));
    deny(static fn() => fcc_mp_help_resolve(1, ['member_id' => 0, 'help_token' => currentToken()]), 'invalid_member');
    $GLOBALS['fixture']['events'][] = event(20, 8, 'stuck', '8|Drugi suradnik|Druga molba.');
    $member7Token = currentToken();
    deny(static fn() => fcc_mp_help_resolve(1, ['member_id' => 8, 'help_token' => $member7Token]), 'swapped_member_with_other_members_token');

    foreach([1 => 'second_marker', 2 => 'late_handled_event'] as $after => $case) {
        resetFixture(); $GLOBALS['fixture']['fail_write_after'] = $after; $rollbackBefore = mutableSnapshot(); $failed = false;
        try { fcc_mp_help_resolve(1, ['member_id' => 7, 'help_token' => currentToken()]); } catch(RuntimeException $e) { $failed = $e->getMessage() === 'SYNTHETIC_EVENT_FAILURE'; }
        check($failed && mutableSnapshot() === $rollbackBefore, $case . '_failure_rolls_back_every_effect');
        check(!database()->active() && end($GLOBALS['fixture']['transactions']) === 'rollback', $case . '_failed_resolution_closes_transaction');
        check(!$GLOBALS['fixture']['advisory_acquired'] && $GLOBALS['fixture']['advisory_releases'] === 1, $case . '_failure_releases_graph_lock');
    }

    resetFixture();
    $validInput = ['action' => 'mp_help_resolve', 'member_id' => 7, 'help_token' => currentToken(), 'token' => 'offline-session-token'];
    foreach(['missing', 'wrong', 'array'] as $case) {
        $input = $validInput;
        if($case === 'missing') unset($input['token']); else $input['token'] = $case === 'array' ? [] : 'wrong-token';
        deny(static fn() => $dispatch(1, $input), 'actual_controller_csrf_' . $case);
    }
    check($dispatch(1, $validInput) === 'partner?view=team-progress' && (fcc_mp_help_pending([7])[7] ?? []) === [], 'actual_controller_guard_and_resolution_action_execute');

    resetFixture();
    defined('ASSETS_PATH') || define('ASSETS_PATH', rtrim($source, '/') . '/themes/altum/assets/');
    defined('ASSETS_FULL_URL') || define('ASSETS_FULL_URL', 'https://offline.invalid/assets/');
    $beforeRender = mutableSnapshot();
    $viewPath = rtrim($source, '/') . '/themes/altum/views/partner/journey-team.php';
    $viewHash = hash_file('sha256', $viewPath);
    $html = (new OfflineView())->render($viewPath);
    $dom = new \DOMDocument();
    $dom->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new \DOMXPath($dom);
    $copyButtons = $xpath->query('//button[@data-copy-value]');
    check($copyButtons->length === 2, 'actual_view_renders_copy_for_both_members');
    foreach($copyButtons as $i => $button) {
        check($button->getAttribute('type') === 'button' && $xpath->query('ancestor::form', $button)->length === 0, 'copy_button_cannot_submit_resolution_' . $i);
        check($button->getAttribute('data-copy-value') !== '', 'copy_button_has_actual_message_' . $i);
    }
    $whatsAppLinks = $xpath->query('//a[starts-with(@href,"https://wa.me/")]');
    check($whatsAppLinks->length === 2, 'actual_view_renders_whatsapp_for_confirmed_phone');
    foreach($whatsAppLinks as $i => $link) check($link->getAttribute('target') === '_blank' && $xpath->query('ancestor::form', $link)->length === 0, 'whatsapp_link_cannot_submit_resolution_' . $i);
    $resolveForms = $xpath->query('//form[input[@name="action" and @value="mp_help_resolve"]]');
    check($resolveForms->length === 1, 'actual_view_resolution_exists_only_on_help_card');
    $resolveForm = $resolveForms->item(0);
    check(strtolower($resolveForm->getAttribute('method')) === 'post', 'resolution_requires_explicit_post');
    check($xpath->query('input[@name="token" and @value="offline-session-token"]', $resolveForm)->length === 1, 'resolution_form_has_session_csrf');
    check($xpath->query('input[@name="member_id" and @value="7"]', $resolveForm)->length === 1, 'resolution_form_binds_member');
    check($xpath->query('input[@name="help_token" and @value="' . currentToken() . '"]', $resolveForm)->length === 1, 'resolution_form_binds_actual_visible_snapshot');
    check($xpath->query('button[@type="submit"]', $resolveForm)->length === 1, 'resolution_has_separate_explicit_submit_button');
    check(mutableSnapshot() === $beforeRender, 'render_and_contact_controls_do_not_mutate_help');
    check(hash_file('sha256', $viewPath) === $viewHash, 'actual_view_unchanged');

    check(hash_file('sha256', $helperPath) === $hashBefore, 'actual_helper_unchanged');
    echo json_encode(['status' => 'PASS', 'phase' => 'projection_token_resolver_controller_view', 'checks' => count($checks), 'helper_sha256' => $hashBefore,
        'time_window_regression' => ['calendar_and_rolling_predicates_executed' => true, 'former_query_reopens_legacy' => true, 'new_query_keeps_resolution' => true, 'midnight_boundary_covered' => true],
        'controller_sha256' => hash_file('sha256', $controllerPath), 'view_sha256' => $viewHash,
        'limitations' => ['SQL uses an allowlisted in-memory transaction double.', 'Controller check executes its actual CSRF guard and resolution action branch, not the full HTTP route.', 'Contact controls are checked in the actual rendered DOM, not a browser JavaScript runtime.'],
        'database' => false, 'bootstrap' => false, 'network' => false, 'checks_detail' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch(Throwable $e) {
    fwrite(STDERR, json_encode(['status' => 'FAIL', 'former_window_control' => $formerWindowControl, 'message' => $e->getMessage(), 'checks_completed' => count($checks), 'last_checks' => array_slice($checks, -5)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
    exit(1);
}

/* /Custom code: FC-2026-10-14 */
