<?php
/* Custom code: FC-2026-10-07: Durable owner notifications for verified FCC registrations. */

defined('ALTUMCODE') || die();

function fcc_registration_notifications_ensure_tables(): void {
    static $ready = false;
    if($ready) return;
    foreach([
        "CREATE TABLE IF NOT EXISTS `fcc_registration_admin_notifications` (
            `notification_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `event_key` VARCHAR(96) NOT NULL,
            `audit_id` BIGINT UNSIGNED NULL,
            `user_id` BIGINT UNSIGNED NULL,
            `title` VARCHAR(64) NOT NULL,
            `description` VARCHAR(1024) NOT NULL,
            `url` VARCHAR(512) NOT NULL,
            `internal_notification_id` BIGINT UNSIGNED NULL,
            `status` VARCHAR(24) NOT NULL DEFAULT 'queued',
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`notification_id`),
            UNIQUE KEY `fcc_registration_notification_event_uq` (`event_key`),
            KEY `fcc_registration_notification_status_idx` (`status`, `notification_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `fcc_registration_admin_notification_deliveries` (
            `delivery_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `notification_id` BIGINT UNSIGNED NOT NULL,
            `push_subscriber_id` BIGINT UNSIGNED NOT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'queued',
            `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
            `next_attempt_at` DATETIME NULL,
            `sent_at` DATETIME NULL,
            `last_error` VARCHAR(64) NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`delivery_id`),
            UNIQUE KEY `fcc_registration_notification_recipient_uq` (`notification_id`, `push_subscriber_id`),
            KEY `fcc_registration_notification_retry_idx` (`status`, `next_attempt_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `fcc_registration_admin_push_configuration` (
            `user_id` INT UNSIGNED NOT NULL,
            `public_key` VARCHAR(128) NOT NULL,
            `private_key` VARCHAR(128) NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `fcc_registration_admin_push_subscriptions` (
            `push_subscriber_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `endpoint_sha256` CHAR(64) NOT NULL,
            `endpoint` TEXT NOT NULL,
            `keys` TEXT NOT NULL,
            `is_enabled` TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`push_subscriber_id`),
            UNIQUE KEY `fcc_registration_owner_endpoint_uq` (`endpoint_sha256`),
            KEY `fcc_registration_owner_enabled_idx` (`user_id`, `is_enabled`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ] as $query) {
        if(!database()->query($query)) throw new \RuntimeException('FCC registration notification storage is unavailable.');
    }
    $ready = true;
}

function fcc_registration_admin_push_available(): bool {
    if(!fcc_registration_admin_push_runtime_ready()) return false;
    $configuration = fcc_registration_admin_push_configuration();
    return !empty($configuration->public_key) && !empty($configuration->private_key);
}

function fcc_registration_admin_push_runtime_ready(): bool {
    return class_exists('Minishlink\\WebPush\\WebPush') && class_exists('Minishlink\\WebPush\\Subscription')
        && class_exists('Minishlink\\WebPush\\VAPID');
}

function fcc_registration_admin_push_owner(object $user): bool {
    $user_id = (int) ($user->user_id ?? 0);
    if($user_id < 1 || (int) ($user->type ?? 0) !== 1
        || (int) ($user->status ?? 0) !== 1) return false;
    if(!function_exists('session_has') || !session_has('admin_user_id')) return true;
    /* A returned admin session may retain its own marker. Never allow a delegated identity. */
    return function_exists('session_get') && (int) session_get('admin_user_id') === $user_id
        && (int) session_get('user_id') === $user_id;
}

function fcc_registration_admin_push_configuration(bool $create = false): ?object {
    fcc_registration_notifications_ensure_tables();
    /* User 1 is the application signing key slot, never a push recipient filter. */
    $configuration = db()->where('user_id', 1)->getOne('fcc_registration_admin_push_configuration');
    if($configuration || !$create) return $configuration;
    if(!fcc_registration_admin_push_runtime_ready()) throw new \RuntimeException('FCC Web Push runtime is unavailable.');
    $lock = database()->query("SELECT GET_LOCK('fcc_registration_admin_push_keys', 5) AS acquired");
    if((int) ($lock ? ($lock->fetch_assoc()['acquired'] ?? 0) : 0) !== 1) throw new \RuntimeException('FCC push keys are being prepared.');
    try {
        $configuration = db()->where('user_id', 1)->getOne('fcc_registration_admin_push_configuration');
        if($configuration) return $configuration;
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
        if(!db()->insert('fcc_registration_admin_push_configuration', ['user_id' => 1, 'public_key' => $keys['publicKey'],
            'private_key' => $keys['privateKey'], 'created_at' => get_date()])) throw new \RuntimeException('FCC push keys could not be persisted.');
        return db()->where('user_id', 1)->getOne('fcc_registration_admin_push_configuration');
    } finally {
        database()->query("SELECT RELEASE_LOCK('fcc_registration_admin_push_keys')");
    }
}

function fcc_registration_admin_push_subscribers(): array {
    /* Only active administrators who explicitly subscribed to FCC notifications are recipients. */
    $admins = db()->where('type', 1)->where('status', 1)->get('users', null, ['user_id']) ?: [];
    if(!$admins) return [];
    $user_ids = array_map(static fn(object $admin): int => (int) $admin->user_id, $admins);
    return db()->where('user_id', $user_ids, 'IN')->where('is_enabled', 1)->get('fcc_registration_admin_push_subscriptions') ?: [];
}

function fcc_registration_admin_push_subscription_count(object $user): int {
    if(!fcc_registration_admin_push_owner($user)) return 0;
    $user_id = (int) $user->user_id;
    return count(array_filter(fcc_registration_admin_push_subscribers(),
        static fn(object $subscriber): bool => (int) $subscriber->user_id === $user_id));
}

function fcc_registration_admin_push_endpoint(string $endpoint): string {
    $parts = parse_url($endpoint);
    $host = strtolower((string) ($parts['host'] ?? ''));
    $providers = ['fcm.googleapis.com', 'android.googleapis.com', 'updates.push.services.mozilla.com',
        'notify.windows.com', 'push.apple.com', 'in-vcm-api.vivoglobal.com'];
    $allowed = false;
    foreach($providers as $provider) if($host === $provider || str_ends_with($host, '.' . $provider)) $allowed = true;
    if(!$allowed || strlen($endpoint) > 4096 || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user'])
        || isset($parts['pass']) || isset($parts['fragment']) || (isset($parts['port']) && (int) $parts['port'] !== 443)) {
        throw new \InvalidArgumentException('Invalid FCC push subscription endpoint.');
    }
    return $endpoint;
}

function fcc_registration_admin_push_subscribe(object $user, array $subscription): array {
    if(!fcc_registration_admin_push_owner($user)) throw new \InvalidArgumentException('FCC administrator authentication is required.');
    $user_id = (int) $user->user_id;
    $endpoint = fcc_registration_admin_push_endpoint((string) ($subscription['endpoint'] ?? ''));
    $keys = (array) ($subscription['keys'] ?? []);
    foreach(['p256dh' => 65, 'auth' => 16] as $key => $bytes) {
        $value = (string) ($keys[$key] ?? '');
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if(!preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $value) || strlen((string) $decoded) !== $bytes) throw new \InvalidArgumentException('Invalid FCC push subscription keys.');
    }
    fcc_registration_admin_push_configuration(true);
    $hash = hash('sha256', $endpoint);
    $existing = db()->where('endpoint_sha256', $hash)->getOne('fcc_registration_admin_push_subscriptions');
    if($existing && (int) $existing->user_id !== $user_id) throw new \InvalidArgumentException('A different FCC administrator owns this subscription.');
    $values = ['user_id' => $user_id, 'endpoint_sha256' => $hash, 'endpoint' => $endpoint,
        'keys' => json_encode(['p256dh' => $keys['p256dh'], 'auth' => $keys['auth']]), 'is_enabled' => 1, 'updated_at' => get_date()];
    $saved = $existing ? db()->where('push_subscriber_id', $existing->push_subscriber_id)->where('user_id', $user_id)->update('fcc_registration_admin_push_subscriptions', $values)
        : db()->insert('fcc_registration_admin_push_subscriptions', $values + ['created_at' => get_date()]);
    if(!$saved) throw new \RuntimeException('FCC owner push subscription could not be saved.');
    return ['status' => 'subscribed', 'active_admin_subscribers' => fcc_registration_admin_push_subscription_count($user)];
}

function fcc_registration_admin_push_unsubscribe(object $user, string $endpoint): array {
    if(!fcc_registration_admin_push_owner($user)) throw new \InvalidArgumentException('FCC administrator authentication is required.');
    $hash = hash('sha256', fcc_registration_admin_push_endpoint($endpoint));
    if(!db()->where('user_id', (int) $user->user_id)->where('endpoint_sha256', $hash)->update('fcc_registration_admin_push_subscriptions', ['is_enabled' => 0, 'updated_at' => get_date()])) {
        throw new \RuntimeException('FCC owner push subscription could not be disabled.');
    }
    return ['status' => 'unsubscribed', 'active_admin_subscribers' => fcc_registration_admin_push_subscription_count($user)];
}

function fcc_registration_admin_push_subscription_status(object $user, string $endpoint): array {
    if(!fcc_registration_admin_push_owner($user)) throw new \InvalidArgumentException('FCC administrator authentication is required.');
    $hash = hash('sha256', fcc_registration_admin_push_endpoint($endpoint));
    $subscription = db()->where('user_id', (int) $user->user_id)->where('endpoint_sha256', $hash)->where('is_enabled', 1)
        ->getOne('fcc_registration_admin_push_subscriptions', ['push_subscriber_id']);
    return ['status' => $subscription ? 'subscribed' : 'unsubscribed',
        'active_admin_subscribers' => fcc_registration_admin_push_subscription_count($user)];
}

function fcc_registration_admin_web_push_send(array $content, object $subscriber): bool {
    $user_id = (int) ($subscriber->user_id ?? 0);
    if($user_id < 1 || empty($subscriber->is_enabled) || !fcc_registration_admin_push_available()) return false;
    $admin = db()->where('user_id', $user_id)->where('type', 1)->where('status', 1)->getOne('users', ['user_id']);
    if(!$admin) return false;
    $subscriber = db()->where('push_subscriber_id', (int) ($subscriber->push_subscriber_id ?? 0))->where('user_id', $user_id)
        ->where('is_enabled', 1)->getOne('fcc_registration_admin_push_subscriptions');
    if(!$subscriber) return false;
    $configuration = fcc_registration_admin_push_configuration();
    $web_push = new \Minishlink\WebPush\WebPush(['VAPID' => ['subject' => url(''),
        'publicKey' => $configuration->public_key, 'privateKey' => $configuration->private_key]], [], 20,
        ['timeout' => 8, 'connect_timeout' => 5]);
    $web_push->setAutomaticPadding(0);
    $report = $web_push->sendOneNotification(\Minishlink\WebPush\Subscription::create([
        'endpoint' => $subscriber->endpoint, 'expirationTime' => null, 'keys' => json_decode($subscriber->keys, true),
    ]), json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ['TTL' => 5000, 'urgency' => 'normal']);
    if($report->isSubscriptionExpired()) {
        db()->where('push_subscriber_id', $subscriber->push_subscriber_id)->where('user_id', $user_id)->update('fcc_registration_admin_push_subscriptions', ['is_enabled' => 0, 'updated_at' => get_date()]);
    }
    return $report->isSuccess();
}

function fcc_registration_notification_text(string $value, int $length): string {
    $value = preg_replace('/[\x00-\x1f\x7f]+/u', ' ', strip_tags($value)) ?? '';
    return trim(mb_substr($value, 0, $length));
}

function fcc_registration_notification_enqueue(string $event_key, string $title, string $description, string $url, ?int $audit_id = null, ?int $user_id = null): array {
    fcc_registration_notifications_ensure_tables();
    if(function_exists('fcc_ai_ensure_internal_notifications_enabled')) fcc_ai_ensure_internal_notifications_enabled(false, true);
    $lock = database()->query("SELECT GET_LOCK('fcc_registration_admin_notifications', 0) AS acquired");
    if((int) ($lock ? ($lock->fetch_assoc()['acquired'] ?? 0) : 0) !== 1) {
        throw new \RuntimeException('FCC registration notifications are already being processed.');
    }
    try {
        $existing = db()->where('event_key', $event_key)->getOne('fcc_registration_admin_notifications');
        if($existing) return ['status' => $existing->status, 'notification_id' => (int) $existing->notification_id, 'duplicate' => true];

        $title = fcc_registration_notification_text($title, 64);
        $description = fcc_registration_notification_text($description, 1024);
        $status = !fcc_registration_admin_push_available() ? 'push_unavailable'
            : (fcc_registration_admin_push_subscribers() ? 'queued' : 'no_subscribers');
        db()->startTransaction();
        try {
            $notification_id = db()->insert('fcc_registration_admin_notifications', [
                'event_key' => $event_key, 'audit_id' => $audit_id, 'user_id' => $user_id,
                'title' => $title, 'description' => $description, 'url' => $url,
                'status' => $status, 'created_at' => get_date(), 'updated_at' => get_date(),
            ]);
            if(!$notification_id) throw new \RuntimeException('FCC registration notification could not be queued.');
            $internal_id = db()->insert('internal_notifications', [
                'for_who' => 'admin', 'from_who' => 'system', 'icon' => 'fas fa-user-check',
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
                'url' => $url, 'datetime' => get_date(),
            ]);
            if(!$internal_id || !db()->where('notification_id', $notification_id)->update('fcc_registration_admin_notifications', ['internal_notification_id' => $internal_id])) {
                throw new \RuntimeException('FCC registration internal notification could not be persisted.');
            }
            db()->commit();
        } catch(\Throwable $exception) {
            db()->rollback();
            throw $exception;
        }
        return ['status' => $status, 'notification_id' => (int) $notification_id, 'duplicate' => false];
    } finally {
        database()->query("SELECT RELEASE_LOCK('fcc_registration_admin_notifications')");
    }
}

function fcc_registration_notify_admin_approval(object $user, array|object $sponsor, int $audit_id): array {
    if($audit_id < 1 || (int) ($user->user_id ?? 0) < 1) throw new \InvalidArgumentException('Verified FCC approval audit is required.');
    $sponsor = (array) $sponsor;
    $preferences = is_string($user->preferences ?? null) ? json_decode($user->preferences, true) : (array) ($user->preferences ?? []);
    $meta = (array) ($preferences['meta'] ?? []);
    $fbo_id = preg_replace('/\D/', '', (string) ($meta['foreverId'] ?? $meta['forever_id'] ?? $meta['foreverID'] ?? ''));
    $name = fcc_registration_notification_text((string) ($user->name ?? 'FCC suradnik'), 80);
    $sponsor_name = fcc_registration_notification_text((string) ($sponsor['name'] ?? 'potvrđeni sponzor'), 80);
    return fcc_registration_notification_enqueue('approval:' . $audit_id, 'Novi FCC suradnik automatski je odobren',
        $name . ($fbo_id ? ', Forever ID ' . $fbo_id : '') . '. Sponzor: ' . $sponsor_name . '.',
        'admin/user-view/' . (int) $user->user_id, $audit_id, (int) $user->user_id);
}

function fcc_registration_automation_notify_approval(object $user, array $context): array {
    return fcc_registration_notify_admin_approval($user, $context['sponsor'] ?? [], (int) ($context['audit_id'] ?? 0));
}

function fcc_registration_notify_admin_test(string $request_id): array {
    if(!preg_match('/^[a-zA-Z0-9_]{8,64}$/D', $request_id)) throw new \InvalidArgumentException('A stable notification test identifier is required.');
    return fcc_registration_notification_enqueue('test:' . $request_id, 'Testna FCC obavijest',
        'Ovo je test push obavijesti za automatsku provjeru novih FCC registracija.', 'admin/users');
}

function fcc_registration_admin_notification_status(int $notification_id): array {
    $event = db()->where('notification_id', $notification_id)->getOne('fcc_registration_admin_notifications', ['notification_id', 'status']);
    return ['notification_id' => $notification_id, 'status' => $event->status ?? 'unknown'];
}

function fcc_registration_notification_attempt(object $delivery, array $content, object $subscriber, callable $send, callable $persist): bool {
    if(($delivery->status ?? '') === 'delivered') return true;
    $attempts = (int) ($delivery->attempts ?? 0) + 1;
    try {
        $delivered = $send($content, $subscriber) === true;
        $error = $delivered ? null : 'push_rejected';
    } catch(\Throwable $exception) {
        /* Do not persist exceptions containing private subscription URLs or keys. */
        $delivered = false;
        $error = 'push_transport_failed';
    }
    $next_attempt = (new \DateTimeImmutable(get_date()))->modify('+' . min(360, 5 * (2 ** min($attempts - 1, 6))) . ' minutes')->format('Y-m-d H:i:s');
    $persist([
        'status' => $delivered ? 'delivered' : 'failed', 'attempts' => $attempts,
        'sent_at' => $delivered ? get_date() : null,
        'next_attempt_at' => $delivered ? null : $next_attempt,
        'last_error' => $error, 'updated_at' => get_date(),
    ]);
    return $delivered;
}

function fcc_registration_process_admin_notifications(int $limit = 25): array {
    fcc_registration_notifications_ensure_tables();
    $summary = ['delivered' => 0, 'queued' => 0, 'failed' => 0, 'active_admin_subscribers' => 0];
    if(!fcc_registration_admin_push_available()) return $summary + ['status' => 'push_unavailable'];
    $subscribers = fcc_registration_admin_push_subscribers();
    $summary['active_admin_subscribers'] = count($subscribers);
    if(!$subscribers) return $summary + ['status' => 'no_subscribers'];
    $lock = database()->query("SELECT GET_LOCK('fcc_registration_admin_notifications', 0) AS acquired");
    if((int) ($lock ? ($lock->fetch_assoc()['acquired'] ?? 0) : 0) !== 1) return $summary + ['status' => 'busy'];
    try {
        $events = db()->where('status', ['queued', 'failed', 'no_subscribers', 'push_unavailable'], 'IN')->orderBy('notification_id', 'ASC')->get('fcc_registration_admin_notifications', max(1, min(100, $limit))) ?: [];
        foreach($events as $event) {
            $event_failed = false;
            $event_queued = false;
            foreach($subscribers as $subscriber) {
                $delivery = db()->where('notification_id', $event->notification_id)->where('push_subscriber_id', $subscriber->push_subscriber_id)->getOne('fcc_registration_admin_notification_deliveries');
                if(!$delivery) {
                    $delivery_id = db()->insert('fcc_registration_admin_notification_deliveries', [
                        'notification_id' => $event->notification_id, 'push_subscriber_id' => $subscriber->push_subscriber_id,
                        'status' => 'queued', 'attempts' => 0, 'updated_at' => get_date(),
                    ]);
                    if(!$delivery_id) throw new \RuntimeException('FCC push recipient could not be queued.');
                    $delivery = db()->where('delivery_id', $delivery_id)->getOne('fcc_registration_admin_notification_deliveries');
                }
                if($delivery->status === 'delivered') continue;
                if(!empty($delivery->next_attempt_at) && $delivery->next_attempt_at > get_date()) {
                    $event_queued = true;
                    $summary['queued']++;
                    continue;
                }
                $content = ['title' => $event->title, 'description' => mb_substr($event->description, 0, 128), 'url' => url($event->url), 'tag' => 'fcc-registration-' . $event->notification_id];
                $delivered = fcc_registration_notification_attempt($delivery, $content, $subscriber,
                    static fn(array $payload, object $recipient) => fcc_registration_admin_web_push_send($payload, $recipient),
                    static function(array $changes) use ($delivery): void {
                        if(!db()->where('delivery_id', $delivery->delivery_id)->update('fcc_registration_admin_notification_deliveries', $changes)) {
                            throw new \RuntimeException('FCC push delivery outcome could not be persisted.');
                        }
                    });
                $summary[$delivered ? 'delivered' : 'failed']++;
                if(!$delivered) $event_failed = true;
            }
            $status = $event_failed ? 'failed' : ($event_queued ? 'queued' : 'delivered');
            if(!db()->where('notification_id', $event->notification_id)->update('fcc_registration_admin_notifications', ['status' => $status, 'updated_at' => get_date()])) {
                throw new \RuntimeException('FCC notification summary could not be persisted.');
            }
        }
    } finally {
        database()->query("SELECT RELEASE_LOCK('fcc_registration_admin_notifications')");
    }
    return $summary + ['status' => $summary['failed'] > 0 ? 'retry' : 'processed'];
}

function fcc_registration_admin_notification_diagnostics(): array {
    fcc_registration_notifications_ensure_tables();
    $summary = ['recipient_role' => 'active_admin', 'provider' => 'fcc', 'runtime_ready' => fcc_registration_admin_push_runtime_ready(),
        'internal_notifications_enabled' => !empty(settings()->internal_notifications->admins_is_enabled),
        'setup_route_available' => class_exists('Altum\\Router') && isset(\Altum\Router::$routes['admin']['fcc-push']),
        'setup_controller_available' => defined('APP_PATH') && file_exists(APP_PATH . 'controllers/admin/AdminFccPush.php'),
        'setup_url' => url('admin/fcc-push/'), 'push_available' => fcc_registration_admin_push_available(),
        'active_admin_subscribers' => count(fcc_registration_admin_push_subscribers()), 'delivered' => 0, 'queued' => 0, 'failed' => 0];
    foreach(['delivered', 'queued', 'failed', 'no_subscribers', 'push_unavailable'] as $status) {
        $count = db()->where('status', $status)->getValue('fcc_registration_admin_notifications', 'COUNT(*)');
        $summary[in_array($status, ['no_subscribers', 'push_unavailable'], true) ? 'queued' : $status] += (int) $count;
    }
    return $summary;
}

/* /Custom code: FC-2026-10-07 */
