<?php
/* Custom code: FC-2026-10-11: Public visibility and the 4CC WhatsApp group follow FLP360 CC, not public clicks.
 * One rule source: a collaborator qualifies when the current or the previous calendar month
 * (Europe/Zagreb) meets the level. Featured apps need PRO and at least 1 personal CC,
 * recommended sponsors and the 4CC group need PRO and FLP360-confirmed 4CC activity.
 * CC amounts are eligibility data only and are never published. */
defined('ALTUMCODE') || die();

const FCC_CCV_FEATURED_PERSONAL_CC = 1.0;

/** Current and previous business month starts, newest first. */
function fcc_ccv_months(?DateTimeImmutable $now = null): array {
    $now = ($now ?? new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('Europe/Zagreb'));
    $current = $now->modify('first day of this month')->setTime(0, 0);
    return [$current->format('Y-m-d'), $current->modify('-1 month')->format('Y-m-d')];
}

/** Best CC values of the current and previous month for each Forever ID. */
function fcc_ccv_cc_by_forever_id(array $forever_ids, ?DateTimeImmutable $now = null): array {
    $ids = array_values(array_unique(array_filter(array_map('strval', $forever_ids), static fn(string $id): bool => preg_match('/^\d{12}$/D', $id) === 1)));
    $result = [];
    if(!$ids) return $result;
    foreach(array_chunk($ids, 500) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $rows = fcc_partner_rows("SELECT fbo_id, period_month, personal_cc, is_4cc_active FROM forever_business_metrics WHERE period_month IN (?, ?) AND fbo_id IN ({$placeholders})", array_merge(fcc_ccv_months($now), $chunk));
        foreach($rows as $row) {
            $id = (string) $row['fbo_id'];
            $entry = $result[$id] ?? ['personal_cc' => 0.0, 'is_4cc' => false, 'months' => []];
            $entry['personal_cc'] = max($entry['personal_cc'], (float) $row['personal_cc']);
            $entry['is_4cc'] = $entry['is_4cc'] || (int) $row['is_4cc_active'] === 1;
            $entry['months'][] = (string) $row['period_month'];
            $result[$id] = $entry;
        }
    }
    return $result;
}

/** Level of one CC record: none, featured (1 CC) or 4cc. */
function fcc_ccv_level_from_cc(?array $cc): string {
    if(!$cc) return 'none';
    if(!empty($cc['is_4cc'])) return '4cc';
    return (float) ($cc['personal_cc'] ?? 0) >= FCC_CCV_FEATURED_PERSONAL_CC ? 'featured' : 'none';
}

/** Visibility state of one FCC account. PRO belongs to the login, CC to its Forever ID. */
function fcc_ccv_user_state(int $user_id, ?DateTimeImmutable $now = null): array {
    $state = ['forever_id' => '', 'pro' => false, 'cc_level' => 'none', 'featured' => false, 'sponsor' => false, 'cc_available' => false];
    if($user_id <= 0) return $state;
    $user = fcc_partner_one('SELECT preferences FROM users WHERE user_id = ? AND status = 1', [$user_id]);
    if(!$user) return $state;
    $state['forever_id'] = fcc_forever_id($user['preferences'] ?? null);
    $state['pro'] = fcc_pro_has_access($user_id);
    $cc = $state['forever_id'] !== '' ? (fcc_ccv_cc_by_forever_id([$state['forever_id']], $now)[$state['forever_id']] ?? null) : null;
    $state['cc_available'] = $cc !== null;
    $state['cc_level'] = fcc_ccv_level_from_cc($cc);
    $state['featured'] = $state['pro'] && in_array($state['cc_level'], ['featured', '4cc'], true);
    $state['sponsor'] = $state['pro'] && $state['cc_level'] === '4cc';
    return $state;
}

/* 4CC WhatsApp group. Membership is the member's own statement and stays valid only while
 * the member qualifies without interruption. A gap shows the invitation again. */
function fcc_ccv_group_url(): string {
    $url = trim((string) fcc_partner_notification_config('cc4_group_url', ''));
    return preg_match('#^https://chat\.whatsapp\.com/[A-Za-z0-9]+(?:\?[A-Za-z0-9=&_-]*)?$#D', $url) ? $url : '';
}

function fcc_ccv_group_save_url(string $url): void {
    $url = trim($url);
    if($url !== '' && !preg_match('#^https://chat\.whatsapp\.com/[A-Za-z0-9]+(?:\?[A-Za-z0-9=&_-]*)?$#D', $url)) {
        throw new InvalidArgumentException(fcc_t('Upiši poveznicu za pozivnicu u WhatsApp grupu, na primjer https://chat.whatsapp.com/...'));
    }
    fcc_partner_notification_config_save('cc4_group_url', $url);
}

/** Latest join or removal event of a member. */
function fcc_ccv_group_last_event(int $user_id): ?array {
    return fcc_partner_one("SELECT kind, created_at FROM fcc_partner_pilot_events WHERE user_id = ? AND kind IN ('cc4_group_joined','cc4_group_removed') ORDER BY id DESC LIMIT 1", [$user_id]);
}

/** True when the member qualified in every month since joining (each month checked with its previous month). */
function fcc_ccv_group_continuous(string $forever_id, string $joined_at, ?DateTimeImmutable $now = null): bool {
    if($forever_id === '') return false;
    $zone = new DateTimeZone('Europe/Zagreb');
    $start = (new DateTimeImmutable($joined_at, new DateTimeZone('UTC')))->setTimezone($zone)->modify('first day of this month')->setTime(0, 0);
    $end = ($now ?? new DateTimeImmutable('now'))->setTimezone($zone)->modify('first day of this month')->setTime(0, 0);
    $rows = fcc_partner_rows('SELECT period_month FROM forever_business_metrics WHERE fbo_id = ? AND is_4cc_active = 1 AND period_month >= ? AND period_month <= ?', [$forever_id, $start->modify('-1 month')->format('Y-m-d'), $end->format('Y-m-d')]);
    $active = array_fill_keys(array_map(static fn(array $row): string => (string) $row['period_month'], $rows), true);
    for($month = $start; $month <= $end; $month = $month->modify('+1 month')) {
        if(empty($active[$month->format('Y-m-d')]) && empty($active[$month->modify('-1 month')->format('Y-m-d')])) return false;
    }
    return true;
}

function fcc_ccv_group_state(int $user_id, ?DateTimeImmutable $now = null): array {
    $visibility = fcc_ccv_user_state($user_id, $now);
    $event = fcc_ccv_group_last_event($user_id);
    $joined = $event && $event['kind'] === 'cc4_group_joined';
    $valid_member = $joined && $visibility['sponsor'] && fcc_ccv_group_continuous($visibility['forever_id'], (string) $event['created_at'], $now);
    return [
        'eligible' => $visibility['sponsor'],
        'url' => fcc_ccv_group_url(),
        'member' => $valid_member,
        'should_leave' => $joined && !$valid_member && !$visibility['sponsor'],
        'joined_at' => $joined ? (string) $event['created_at'] : null,
        'show_invitation' => $visibility['sponsor'] && !$valid_member && fcc_ccv_group_url() !== '',
    ];
}

function fcc_ccv_group_confirm(int $user_id, array $in): void {
    $key = (string) ($in['request_key'] ?? '');
    if(!preg_match('/^[a-f0-9]{32}$/D', $key)) throw new InvalidArgumentException(fcc_t('Osvježi obrazac i pokušaj ponovno.'));
    if(!fcc_ccv_group_state($user_id)['eligible']) throw new InvalidArgumentException(fcc_t('4CC grupa otvara se uz aktivni PRO i potvrđenu 4CC aktivnost.'));
    fcc_jp_event($user_id, 0, 'cc4_group_joined', 'Suradnik je potvrdio članstvo u 4CC WhatsApp grupi.', $key, get_date());
}

/** Admin confirms that a member who no longer qualifies was removed from the WhatsApp group. */
function fcc_ccv_group_mark_removed(int $admin_id, int $user_id, array $in): void {
    if(!\Altum\Authentication::is_admin()) throw new InvalidArgumentException(fcc_t('Ova radnja dostupna je administratoru.'));
    $key = (string) ($in['request_key'] ?? '');
    if(!preg_match('/^[a-f0-9]{32}$/D', $key)) throw new InvalidArgumentException(fcc_t('Osvježi obrazac i pokušaj ponovno.'));
    if(!fcc_ccv_group_state($user_id)['should_leave']) throw new InvalidArgumentException(fcc_t('Suradnik je i dalje kvalificiran ili nije označen kao član grupe.'));
    fcc_jp_event($user_id, 0, 'cc4_group_removed', 'Administrator ' . $admin_id . ' je potvrdio uklanjanje iz 4CC grupe.', $key, get_date());
}

/** Admin overview: members to remove and qualified collaborators who have not joined yet. */
function fcc_ccv_group_admin_rows(): array {
    $rows = ['remove' => [], 'invited' => [], 'members' => 0];
    $users = fcc_partner_rows("SELECT u.user_id, u.name, u.preferences FROM users u WHERE u.status = 1 AND u.type = 0");
    $ids = [];
    foreach($users as $user) $ids[(int) $user['user_id']] = fcc_forever_id($user['preferences'] ?? null);
    $cc = fcc_ccv_cc_by_forever_id(array_values($ids));
    $joined = [];
    foreach(fcc_partner_rows("SELECT e.user_id, e.kind FROM fcc_partner_pilot_events e JOIN (SELECT user_id, MAX(id) id FROM fcc_partner_pilot_events WHERE kind IN ('cc4_group_joined','cc4_group_removed') GROUP BY user_id) last ON last.id = e.id") as $event) {
        if($event['kind'] === 'cc4_group_joined') $joined[(int) $event['user_id']] = true;
    }
    foreach($users as $user) {
        $uid = (int) $user['user_id'];
        $level = fcc_ccv_level_from_cc($cc[$ids[$uid]] ?? null);
        $qualified_now = $level === '4cc' && fcc_pro_has_access($uid);
        if(!empty($joined[$uid])) {
            $state = fcc_ccv_group_state($uid);
            if($state['should_leave']) $rows['remove'][] = ['user_id' => $uid, 'name' => (string) $user['name']];
            elseif($state['member']) $rows['members']++;
            else $rows['invited'][] = ['user_id' => $uid, 'name' => (string) $user['name']];
        } elseif($qualified_now) {
            $rows['invited'][] = ['user_id' => $uid, 'name' => (string) $user['name']];
        }
    }
    return $rows;
}
/** Public rules, shown on the public lists and in the FCC guide. */
function fcc_ccv_rules_points(): array {
    return [
        fcc_t('Istaknute aplikacije: aktivan PRO paket, odobren javni prikaz glavne kartice i najmanje 1 osobni CC u ovom ili prošlom kalendarskom mjesecu.'),
        fcc_t('Preporučeni sponzori: aktivan PRO paket, odobren javni prikaz, aktivna Forever prodajna poveznica na kartici i 4CC aktivnost potvrđena u FLP360 u ovom ili prošlom kalendarskom mjesecu.'),
        fcc_t('CC bodovi preuzimaju se iz Forever FLP360 sustava dva puta dnevno. Suradnik ulazi na popis čim se uvjet pojavi u podacima, a izlazi tek kada ga ne ispunjava ni u tekućem ni u prošlom mjesecu.'),
        fcc_t('Iznosi CC bodova se ne objavljuju. Klikovi na kartici i u člancima služe samo kao statistika vlasniku kartice.'),
    ];
}
/* /Custom code: FC-2026-10-11 */
