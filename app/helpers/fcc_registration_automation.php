<?php
/* Custom code: FC-2026-10-07: Verified FLP360 registration decisions and durable delivery retries. */

defined('ALTUMCODE') || die();

function fcc_registration_automation_preferences($value): object {
    if(is_string($value)) $value = json_decode($value);
    $preferences = is_object($value) ? clone $value : (object) (is_array($value) ? $value : []);
    $preferences->meta = (object) ($preferences->meta ?? []);
    return $preferences;
}

function fcc_registration_automation_fbo_id($value): string {
    $value = str_replace('-', '', trim((string) $value));
    return preg_match('/\A[0-9]{12}\z/', $value) ? $value : '';
}

function fcc_registration_automation_is_pending(object $user): bool {
    $meta = fcc_registration_automation_preferences($user->preferences ?? null)->meta;
    return (int) ($user->type ?? 0) === 0 && (int) ($user->status ?? -1) === 0
        && ($user->source ?? '') === 'direct'
        && !empty($meta->fcc_access_requested_at)
        && empty($meta->fcc_access_approved_at)
        && empty($meta->fcc_access_approval_email_sent_at)
        && empty($meta->fcc_access_rejected_at)
        && empty($meta->fcc_access_deactivated_at)
        && empty($meta->limited)
        && in_array($meta->fcc_registration_verification_status ?? '', ['pending', 'retry', 'manual_review'], true);
}

function fcc_registration_automation_snapshot(object $user): string {
    return hash('sha256', json_encode([
        (int) ($user->user_id ?? 0), (string) ($user->datetime ?? ''),
        fcc_registration_automation_fbo_id(fcc_registration_automation_preferences($user->preferences ?? null)->meta->foreverId ?? ''),
        strtolower(trim((string) ($user->email ?? ''))), trim((string) ($user->name ?? '')),
    ], JSON_UNESCAPED_UNICODE));
}

/** Pure validation. Missing responses never authorize either rejection reason. */
function fcc_registration_automation_validate(object $user, array $decision, array $sponsors = [], ?int $now = null): array {
    $manual = static fn(string $reason) => ['status' => 'manual_review', 'reason' => $reason];
    $retry = static fn(string $reason) => ['status' => 'retry', 'reason' => $reason];
    if(!fcc_registration_automation_is_pending($user)) return ['status' => 'unchanged', 'reason' => 'not_pending_registration'];
    $fbo_id = fcc_registration_automation_fbo_id(fcc_registration_automation_preferences($user->preferences ?? null)->meta->foreverId ?? '');
    if((int) ($decision['user_id'] ?? 0) !== (int) $user->user_id
        || (string) ($decision['registered_at'] ?? '') !== (string) ($user->datetime ?? '')
        || fcc_registration_automation_fbo_id($decision['fbo_id'] ?? '') !== $fbo_id || $fbo_id === '') {
        return $manual('registration_changed');
    }
    $evidence = is_array($decision['evidence'] ?? null) ? $decision['evidence'] : [];
    $now = $now ?? time();
    try { $checked_at = (new \DateTimeImmutable((string) ($evidence['checked_at'] ?? ''), new \DateTimeZone('UTC')))->getTimestamp(); }
    catch(\Throwable $exception) { return $retry('invalid_evidence_time'); }
    if(empty($evidence['checked_at']) || $checked_at < $now - 1800 || $checked_at > $now + 120) return $retry('stale_evidence');
    if(($evidence['source'] ?? '') !== 'flp360'
        || fcc_registration_automation_fbo_id($evidence['exact_fbo_id'] ?? '') !== $fbo_id
        || fcc_registration_automation_fbo_id($evidence['root_fbo_id'] ?? '') !== '360000760944') return $retry('identity_or_root_unconfirmed');
    $result = $decision['result'] ?? '';
    if($result === 'manual_review') return $manual('source_requires_manual_review');
    if($result === 'unconfirmed') return $retry('source_unconfirmed');
    if($result === 'invalid_id') {
        return ($evidence['id_exists'] ?? null) === false && ($evidence['authoritative_not_found'] ?? false) === true
            ? ['status' => 'rejected', 'reason' => 'invalid_id'] : $retry('id_not_found_is_not_authoritative');
    }
    if($result === 'valid_id_not_team') {
        return ($evidence['id_exists'] ?? null) === true && ($evidence['in_root_structure'] ?? null) === false
            && ($evidence['authoritative_structure'] ?? false) === true
            ? ['status' => 'rejected', 'reason' => 'valid_id_not_team'] : $retry('structure_exclusion_unconfirmed');
    }
    if($result !== 'approve') return $retry('unknown_decision');
    if(($evidence['id_exists'] ?? null) !== true || ($evidence['in_root_structure'] ?? null) !== true
        || ($evidence['authoritative_structure'] ?? false) !== true) return $retry('structure_membership_unconfirmed');
    $flp_email = strtolower(trim((string) ($evidence['flp_email'] ?? '')));
    if(!filter_var($flp_email, FILTER_VALIDATE_EMAIL)
        || !hash_equals(strtolower(trim((string) ($user->email ?? ''))), $flp_email)) return $manual('owner_email_unconfirmed');
    $sponsor_fbo_id = fcc_registration_automation_fbo_id($evidence['sponsor_fbo_id'] ?? '');
    if($sponsor_fbo_id === '' || $sponsor_fbo_id === $fbo_id) return $manual('sponsor_unconfirmed');
    if(count($sponsors) !== 1 || (int) ($sponsors[0]['status'] ?? 0) !== 1 || !in_array((int) ($sponsors[0]['type'] ?? 0), [0, 1], true)
        || (int) ($sponsors[0]['user_id'] ?? 0) === (int) $user->user_id
        || fcc_registration_automation_fbo_id($sponsors[0]['fbo_id'] ?? '') !== $sponsor_fbo_id) return $manual('sponsor_missing_or_ambiguous');
    return ['status' => 'approved', 'reason' => 'verified_owner_and_structure', 'sponsor' => $sponsors[0]];
}

function fcc_registration_automation_ensure_tables(): void {
    static $ready = false;
    if($ready) return;
    $result = database()->query("CREATE TABLE IF NOT EXISTS `fcc_registration_automation_audits` (
        `audit_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `registered_at` DATETIME NOT NULL,
        `fbo_id` CHAR(12) NOT NULL,
        `user_snapshot_hash` CHAR(64) NOT NULL,
        `status` VARCHAR(32) NOT NULL,
        `reason` VARCHAR(96) NOT NULL,
        `decision_result` VARCHAR(32) NOT NULL,
        `evidence_json` LONGTEXT NOT NULL,
        `sponsor_fbo_id` CHAR(12) NULL,
        `sponsor_user_id` INT UNSIGNED NULL,
        `email_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `admin_notification_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
        `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
        `checked_at` DATETIME NOT NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`audit_id`),
        UNIQUE KEY `fcc_registration_decision_uq` (`user_id`, `registered_at`, `fbo_id`),
        KEY `fcc_registration_retry_idx` (`status`, `email_status`, `updated_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if(!$result) throw new \RuntimeException('Registration audit storage is unavailable.');
    $ready = true;
}

function fcc_registration_automation_pending(int $limit = 100): array {
    fcc_registration_automation_ensure_tables();
    $limit = max(1, min(500, $limit));
    $result = database()->query("SELECT u.user_id, u.name, u.email, u.country, u.datetime, u.type, u.status, u.source, u.preferences
        FROM users u LEFT JOIN fcc_registration_automation_audits a ON a.user_id = u.user_id AND a.registered_at = u.datetime
        WHERE u.type = 0 AND u.status = 0 AND u.source = 'direct' AND JSON_VALID(u.preferences) = 1
          AND NULLIF(JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.fcc_access_requested_at')), '') IS NOT NULL
          AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.fcc_registration_verification_status')), '') IN ('pending', 'retry', 'manual_review')
          AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.fcc_access_approved_at')), '') = ''
          AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.fcc_access_deactivated_at')), '') = ''
          AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.fcc_access_rejected_at')), '') = ''
        ORDER BY COALESCE(a.checked_at, '1970-01-01 00:00:00') ASC, u.datetime ASC, u.user_id ASC LIMIT {$limit}");
    if(!$result) throw new \RuntimeException('Pending registrations are unavailable.');
    $accounts = [];
    while($user = $result->fetch_object()) {
        if(!fcc_registration_automation_is_pending($user)) continue;
        $meta = fcc_registration_automation_preferences($user->preferences)->meta;
        $accounts[] = ['user_id' => (int) $user->user_id, 'fbo_id' => fcc_registration_automation_fbo_id($meta->foreverId ?? ''),
            'registered_at' => (string) $user->datetime, 'name' => (string) $user->name, 'email' => (string) $user->email,
            'country_code' => (string) ($user->country ?? '')];
    }
    return ['root_fbo_id' => '360000760944', 'accounts' => $accounts];
}

function fcc_registration_automation_sponsors(string $fbo_id): array {
    $fbo_id = fcc_registration_automation_fbo_id($fbo_id);
    if($fbo_id === '') return [];
    $result = database()->query("SELECT user_id, name, status, type FROM users WHERE status = 1 AND type IN (0, 1)
        AND JSON_VALID(preferences) = 1 AND REPLACE(TRIM(COALESCE(
          JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverId')),
          JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.forever_id')),
          JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverID')), '')), '-', '') = '{$fbo_id}' LIMIT 2");
    if(!$result) throw new \RuntimeException('Sponsor accounts are unavailable.');
    $rows = [];
    while($row = $result->fetch_assoc()) $rows[] = array_merge($row, ['fbo_id' => $fbo_id]);
    return $rows;
}

function fcc_registration_automation_with_lock(int $user_id, callable $callback): array {
    $lock = 'fcc_registration_' . max(0, $user_id);
    $row = database()->query("SELECT GET_LOCK('{$lock}', 5) AS acquired");
    if(!$row || (int) ($row->fetch_assoc()['acquired'] ?? 0) !== 1) return ['user_id' => $user_id, 'status' => 'retry', 'reason' => 'registration_busy'];
    try { return $callback(); }
    finally { database()->query("SELECT RELEASE_LOCK('{$lock}')"); }
}

function fcc_registration_automation_mail(object $user, string $event, array $assets = []): bool {
    if(!filter_var((string) ($user->email ?? ''), FILTER_VALIDATE_EMAIL)) return false;
    $language = fc_resolve_language_name($user->language ?? null);
    $html_link = static function(string $target, ?string $label = null): string {
        return '<a href="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label ?? $target, ENT_QUOTES, 'UTF-8') . '</a>';
    };
    $key = 'global.emails.admin.' . match($event) {
        'approved' => 'fcc_access_approved',
        'valid_id_not_team' => 'fcc_access_rejected_not_team',
        default => 'fcc_access_rejected',
    };
    $template = get_email_template([], l($key . '.subject', $language), [
        '{{NAME}}' => htmlspecialchars(str_replace('.', '. ', (string) ($user->name ?? '')), ENT_QUOTES, 'UTF-8'),
        '{{APP_LINK}}' => $html_link($assets['main_biolink_url'] ?? url('dashboard')),
        '{{DASHBOARD_LINK}}' => $html_link(url('dashboard')),
        '{{ACCOUNT_PLAN_LINK}}' => $html_link(url('account-plan')),
        '{{CONTACT_EMAIL}}' => $html_link('mailto:info@forevercard.club', 'info@forevercard.club'),
        '{{FORCE_CLICK_LINK}}' => $html_link('https://force.click/use-cases/forever-living-products'),
    ], l($key . '.body', $language));
    $result = send_mail($user->email, $template->subject, $template->body, [
        'anti_phishing_code' => $user->anti_phishing_code ?? null, 'language' => $language, 'return_transport_result' => true,
    ]);
    return is_object($result) && property_exists($result, 'success') ? (bool) $result->success : (bool) $result;
}

/** Preserve the existing LOS approval assets without instantiating an admin controller. */
function fcc_registration_automation_assets(int $user_id): array {
    $main_id = (int) fc_get_user_main_biolink_id($user_id);
    $main = $main_id > 0 ? db()->where('link_id', $main_id)->where('user_id', $user_id)->where('type', 'biolink')->getOne('links', ['link_id', 'url']) : null;
    if(!db()->where('user_id', $user_id)->has('users_vcards')) {
        $vcard = db()->where('user_id', $user_id)->where('type', 'vcard')->orderBy('link_id', 'ASC')->getOne('links', ['link_id']);
        if($vcard && !db()->insert('users_vcards', ['user_id' => $user_id, 'vcard_id' => (int) $vcard->link_id])) throw new \RuntimeException('Registration assets could not be prepared.');
    }
    return ['main_biolink_id' => $main_id, 'main_biolink_url' => $main && !empty($main->url) ? SITE_URL . $main->url : url('dashboard')];
}

function fcc_registration_automation_audit_update(int $audit_id, array $values): void {
    $values['updated_at'] = get_date();
    if(!db()->where('audit_id', $audit_id)->update('fcc_registration_automation_audits', $values)) throw new \RuntimeException('Registration audit could not be saved.');
}

function fcc_registration_automation_approve(object $user, array $sponsor, int $audit_id): array {
    $preferences = fcc_registration_automation_preferences($user->preferences);
    $meta = $preferences->meta;
    $assets = fcc_registration_automation_assets((int) $user->user_id);
    if((int) $user->status === 0) {
        $meta->fcc_access_approved_at = get_date();
        $meta->fcc_registration_verification_status = 'approved';
        $meta->fcc_registration_verified_at = get_date();
        $meta->fcc_registration_verification_audit_id = $audit_id;
        $meta->fcc_sponsor_fbo_id = $sponsor['fbo_id'];
        $meta->fcc_sponsor_user_id = (int) $sponsor['user_id'];
        $preferences->meta = $meta;
        $approved = db()->where('user_id', (int) $user->user_id)->where('status', 0)
            ->where('preferences', $user->preferences)->update('users', ['status' => 1, 'preferences' => json_encode($preferences)]);
        if(!$approved || (int) db()->count !== 1) {
            throw new \RuntimeException('Registration approval could not be saved.');
        }
        $user->status = 1;
        $user->preferences = json_encode($preferences);
        if(!empty(settings()->webhooks->user_new)) {
            try {
                \Unirest\Request::post(settings()->webhooks->user_new, [], [
                    'user_id' => (int) $user->user_id, 'name' => $user->name, 'email' => $user->email,
                    'biolink' => $assets['main_biolink_url'], 'phone' => $meta->phone ?? null,
                    'address' => $meta->address ?? null, 'zip' => $meta->zip ?? null,
                    'city' => $meta->city ?? null, 'country' => $meta->country ?? null, 'foreverId' => $meta->foreverId ?? null,
                ]);
            } catch(\Throwable $exception) { error_log('FCC automated approval webhook delivery failed.'); }
        }
    }
    $email_sent = !empty($meta->fcc_access_approval_email_sent_at);
    if(!$email_sent && fcc_registration_automation_mail($user, 'approved', $assets)) {
        $meta->fcc_access_approval_email_sent_at = get_date();
        $preferences->meta = $meta;
        if(!db()->where('user_id', (int) $user->user_id)->update('users', ['preferences' => json_encode($preferences)])) throw new \RuntimeException('Approval email receipt could not be saved.');
        $email_sent = true;
    }
    cache()->deleteItemsByTag('user_id=' . (int) $user->user_id);
    cache()->deleteItem('user?user_id=' . (int) $user->user_id);
    fcc_registration_automation_audit_update($audit_id, ['status' => 'approved', 'email_status' => $email_sent ? 'sent' : 'pending']);
    $notification = ['status' => 'pending'];
    if(function_exists('fcc_registration_automation_notify_approval')) {
        try { $notification = fcc_registration_automation_notify_approval($user, ['sponsor' => $sponsor, 'audit_id' => $audit_id]); }
        catch(\Throwable $exception) { $notification = ['status' => 'failed']; }
    }
    fcc_registration_automation_audit_update($audit_id, ['admin_notification_status' => (string) ($notification['status'] ?? 'failed')]);
    return ['user_id' => (int) $user->user_id, 'audit_id' => $audit_id, 'status' => 'approved',
        'reason' => 'verified_owner_and_structure', 'email_status' => $email_sent ? 'sent' : 'pending', 'notification' => $notification];
}

function fcc_registration_automation_reject(object $user, string $reason, int $audit_id, string $email_status = 'pending'): array {
    if(!fcc_registration_automation_is_pending($user)) return ['user_id' => (int) $user->user_id, 'status' => 'unchanged', 'reason' => 'not_pending_registration'];
    if(!in_array($reason, ['invalid_id', 'valid_id_not_team'], true)) throw new \InvalidArgumentException('Unknown registration rejection reason.');
    if($email_status !== 'sent') {
        if(!fcc_registration_automation_mail($user, $reason)) {
            fcc_registration_automation_audit_update($audit_id, ['status' => 'rejection_pending', 'email_status' => 'pending']);
            return ['user_id' => (int) $user->user_id, 'audit_id' => $audit_id, 'status' => 'retry', 'reason' => 'rejection_email_pending'];
        }
        fcc_registration_automation_audit_update($audit_id, ['status' => 'rejection_pending', 'email_status' => 'sent']);
    }
    /* Hold the account row while deleting so a simultaneous manual approval
       cannot turn a pending rejection into deletion of an active account. */
    db()->startTransaction();
    try {
        $locked_result = database()->query('SELECT * FROM users WHERE user_id = ' . (int) $user->user_id . ' FOR UPDATE');
        $locked_user = $locked_result ? $locked_result->fetch_object() : null;
        if(!$locked_user || !fcc_registration_automation_is_pending($locked_user)
            || !hash_equals(fcc_registration_automation_snapshot($user), fcc_registration_automation_snapshot($locked_user))) {
            db()->rollback();
            return ['user_id' => (int) $user->user_id, 'audit_id' => $audit_id, 'status' => 'unchanged', 'reason' => 'registration_changed_before_delete'];
        }
        (new \Altum\Models\User())->delete((int) $user->user_id);
        db()->commit();
    } catch(\Throwable $exception) {
        db()->rollback();
        throw $exception;
    }
    if(db()->where('user_id', (int) $user->user_id)->has('users')) {
        return ['user_id' => (int) $user->user_id, 'audit_id' => $audit_id, 'status' => 'retry', 'reason' => 'rejection_delete_pending'];
    }
    fcc_registration_automation_audit_update($audit_id, ['status' => 'rejected', 'email_status' => 'sent']);
    return ['user_id' => (int) $user->user_id, 'audit_id' => $audit_id, 'status' => 'rejected', 'reason' => $reason];
}

function fcc_registration_automation_safe_evidence(array $evidence): array {
    $safe = array_intersect_key($evidence, array_flip(['source', 'checked_at', 'exact_fbo_id', 'sponsor_fbo_id',
        'root_fbo_id', 'id_exists', 'in_root_structure', 'authoritative_structure', 'authoritative_not_found']));
    if(!empty($evidence['flp_email'])) $safe['flp_email_sha256'] = hash('sha256', strtolower(trim((string) $evidence['flp_email'])));
    return $safe;
}

function fcc_registration_automation_apply_decisions(array $decisions, bool $dry_run = false): array {
    if(count($decisions) > 100) throw new \InvalidArgumentException('Too many registration decisions.');
    if(!$dry_run) fcc_registration_automation_ensure_tables();
    $results = [];
    $seen = [];
    foreach($decisions as $decision) {
        if(!is_array($decision)) throw new \InvalidArgumentException('Invalid registration decision.');
        $user_id = (int) ($decision['user_id'] ?? 0);
        if($user_id <= 0 || isset($seen[$user_id])) throw new \InvalidArgumentException('Invalid or repeated registration user.');
        $seen[$user_id] = true;
        $process = static function() use ($user_id, $decision, $dry_run): array {
            $user = db()->where('user_id', $user_id)->getOne('users');
            if(!$user) return ['user_id' => $user_id, 'status' => 'unchanged', 'reason' => 'registration_unavailable'];
            $evidence = is_array($decision['evidence'] ?? null) ? $decision['evidence'] : [];
            $sponsors = ($decision['result'] ?? '') === 'approve' ? fcc_registration_automation_sponsors((string) ($evidence['sponsor_fbo_id'] ?? '')) : [];
            $validated = fcc_registration_automation_validate($user, $decision, $sponsors);
            if($dry_run || $validated['status'] === 'unchanged') return array_merge(['user_id' => $user_id, 'dry_run' => $dry_run], array_diff_key($validated, ['sponsor' => true]));
            $now = get_date();
            $key = ['user_id' => $user_id, 'registered_at' => (string) $user->datetime, 'fbo_id' => fcc_registration_automation_fbo_id($decision['fbo_id'] ?? '')];
            $audit = db()->where('user_id', $key['user_id'])->where('registered_at', $key['registered_at'])->where('fbo_id', $key['fbo_id'])->getOne('fcc_registration_automation_audits');
            $values = array_merge($key, ['user_snapshot_hash' => fcc_registration_automation_snapshot($user),
                'status' => in_array($validated['status'], ['approved', 'rejected'], true) ? 'processing' : $validated['status'],
                'reason' => $validated['reason'], 'decision_result' => (string) ($decision['result'] ?? ''),
                'evidence_json' => json_encode(fcc_registration_automation_safe_evidence($evidence)),
                'sponsor_fbo_id' => $validated['sponsor']['fbo_id'] ?? null, 'sponsor_user_id' => $validated['sponsor']['user_id'] ?? null,
                'attempts' => $audit ? (int) $audit->attempts + 1 : 1, 'checked_at' => $now, 'updated_at' => $now]);
            if($audit) {
                $audit_id = (int) $audit->audit_id;
                if(!hash_equals((string) $audit->user_snapshot_hash, $values['user_snapshot_hash'])
                    || (string) $audit->decision_result !== $values['decision_result']) {
                    $values['email_status'] = 'pending';
                    $values['admin_notification_status'] = 'pending';
                    $audit->email_status = 'pending';
                }
                fcc_registration_automation_audit_update($audit_id, $values);
            } else {
                $audit_id = (int) db()->insert('fcc_registration_automation_audits', array_merge($values, ['created_at' => $now]));
                if(!$audit_id) throw new \RuntimeException('Registration audit could not be created.');
            }
            if($validated['status'] === 'approved') return fcc_registration_automation_approve($user, $validated['sponsor'], $audit_id);
            if($validated['status'] === 'rejected') return fcc_registration_automation_reject($user, $validated['reason'], $audit_id, $audit->email_status ?? 'pending');
            $preferences = fcc_registration_automation_preferences($user->preferences);
            $preferences->meta->fcc_registration_verification_status = $validated['status'];
            $preferences->meta->fcc_registration_verification_reason = $validated['reason'];
            $preferences->meta->fcc_registration_last_checked_at = $now;
            if(!db()->where('user_id', $user_id)->where('status', 0)->update('users', ['preferences' => json_encode($preferences)])) throw new \RuntimeException('Pending registration status could not be saved.');
            return array_merge(['user_id' => $user_id, 'audit_id' => $audit_id], $validated);
        };
        try { $results[] = $dry_run ? $process() : fcc_registration_automation_with_lock($user_id, $process); }
        catch(\Throwable $exception) { error_log('FCC registration processing failed for audit user ' . $user_id . '.'); $results[] = ['user_id' => $user_id, 'status' => 'retry', 'reason' => 'processing_failed']; }
    }
    $deliveries = $dry_run ? [] : fcc_registration_automation_retry_notifications();
    return ['dry_run' => $dry_run, 'results' => $results, 'deliveries' => $deliveries];
}

/** Retry proven decisions without rechecking FLP or exposing recipients in machine responses. */
function fcc_registration_automation_retry_notifications(int $limit = 25): array {
    fcc_registration_automation_ensure_tables();
    $limit = max(1, min(100, $limit));
    $result = database()->query("SELECT * FROM fcc_registration_automation_audits
        WHERE status IN ('processing', 'rejection_pending') OR (status = 'approved'
          AND (email_status <> 'sent' OR admin_notification_status IN ('pending', 'failed')))
        ORDER BY updated_at ASC LIMIT {$limit}");
    if(!$result) throw new \RuntimeException('Registration delivery retries are unavailable.');
    $audits = [];
    while($audit = $result->fetch_object()) $audits[] = $audit;
    $counts = ['email_sent' => 0, 'email_pending' => 0, 'decisions_finished' => 0];
    foreach($audits as $audit) {
        if($audit->status === 'approved' && $audit->email_status === 'sent' && !in_array($audit->admin_notification_status, ['pending', 'failed'], true)) continue;
        try {
            $result = fcc_registration_automation_with_lock((int) $audit->user_id, static function() use ($audit): array {
                $user = db()->where('user_id', (int) $audit->user_id)->getOne('users');
                if(!$user) {
                    fcc_registration_automation_audit_update((int) $audit->audit_id, [
                        'status' => $audit->status === 'rejection_pending' && $audit->email_status === 'sent' ? 'rejected' : 'superseded',
                        'reason' => 'recipient_unavailable',
                    ]);
                    return ['status' => 'unchanged'];
                }
                if(!hash_equals((string) $audit->user_snapshot_hash, fcc_registration_automation_snapshot($user))) {
                    fcc_registration_automation_audit_update((int) $audit->audit_id, ['status' => 'superseded', 'reason' => 'registration_changed']);
                    return ['status' => 'unchanged'];
                }
                if(in_array($audit->decision_result, ['invalid_id', 'valid_id_not_team'], true)) return fcc_registration_automation_reject($user, $audit->decision_result, (int) $audit->audit_id, $audit->email_status);
                if($audit->decision_result !== 'approve') return ['status' => 'unchanged'];
                $meta = fcc_registration_automation_preferences($user->preferences)->meta;
                if((int) $user->status !== 1 || (int) ($meta->fcc_registration_verification_audit_id ?? 0) !== (int) $audit->audit_id) {
                    fcc_registration_automation_audit_update((int) $audit->audit_id, [
                        'status' => fcc_registration_automation_is_pending($user) ? 'retry' : 'superseded',
                        'reason' => 'approval_not_committed_or_changed',
                    ]);
                    return ['status' => 'unchanged'];
                }
                $sponsor_user = db()->where('user_id', (int) $audit->sponsor_user_id)->getOne('users', ['user_id', 'name']);
                return fcc_registration_automation_approve($user, ['fbo_id' => $audit->sponsor_fbo_id,
                    'user_id' => (int) $audit->sponsor_user_id, 'name' => (string) ($sponsor_user->name ?? '')], (int) $audit->audit_id);
            });
            if(($result['email_status'] ?? '') === 'sent') $counts['email_sent']++;
            if(($result['email_status'] ?? '') === 'pending' || ($result['status'] ?? '') === 'retry') $counts['email_pending']++;
            if(in_array($result['status'] ?? '', ['approved', 'rejected'], true)) $counts['decisions_finished']++;
        } catch(\Throwable $exception) { $counts['email_pending']++; }
    }
    if(function_exists('fcc_registration_process_admin_notifications')) $counts['admin_push'] = fcc_registration_process_admin_notifications($limit);
    return $counts;
}

function fcc_registration_automation_status(): array {
    fcc_registration_automation_ensure_tables();
    $result = database()->query("SELECT status, email_status, admin_notification_status, COUNT(*) AS total, MAX(updated_at) AS last_updated_at
        FROM fcc_registration_automation_audits GROUP BY status, email_status, admin_notification_status");
    if(!$result) throw new \RuntimeException('Registration diagnostics are unavailable.');
    $groups = [];
    while($row = $result->fetch_assoc()) $groups[] = $row;
    $status = ['root_fbo_id' => '360000760944', 'audit_groups' => $groups, 'generated_at' => get_date()];
    if(function_exists('fcc_registration_admin_notification_diagnostics')) $status['admin_notifications'] = fcc_registration_admin_notification_diagnostics();
    return $status;
}
/* /Custom code: FC-2026-10-07 */
