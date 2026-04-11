<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

namespace Altum\Controllers;

use Altum\Models\Billing;
use Altum\Models\Plan;

defined('ALTUMCODE') || die();

class OpsReadonly extends Controller {

    private function json_flags(): int {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if(defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        if(defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
            $flags |= JSON_PARTIAL_OUTPUT_ON_ERROR;
        }

        if(($this->get_param_string('pretty') === '1')) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return $flags;
    }

    private function output(array $payload, int $status_code = 200): void {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        echo json_encode($payload, $this->json_flags());
        die();
    }

    private function respond_success(string $scope, array $data, array $meta = []): void {
        $this->output([
            'status' => 'success',
            'scope' => $scope,
            'generated_at' => get_date(),
            'data' => $data,
            'meta' => array_merge([
                'site_url' => SITE_URL,
            ], $meta),
        ]);
    }

    private function respond_error(string $code, string $message, int $status_code = 400, array $details = []): void {
        $this->output([
            'status' => 'error',
            'generated_at' => get_date(),
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status_code);
    }

    private function get_param_string(string $key, string $default = ''): string {
        $value = $_GET[$key] ?? $default;

        if(is_array($value)) {
            return $default;
        }

        return trim((string) $value);
    }

    private function get_param_int(string $key, int $default = 0): int {
        return (int) ($_GET[$key] ?? $default);
    }

    private function get_param_limit(string $key, int $default = 10, int $min = 1, int $max = 50): int {
        return min($max, max($min, $this->get_param_int($key, $default)));
    }

    private function get_authorization_bearer(): string {
        $header = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));

        if($header !== '' && preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim((string) ($matches[1] ?? ''));
        }

        return '';
    }

    private function get_request_key(): string {
        foreach([
            $this->get_param_string('key'),
            trim((string) ($_SERVER['HTTP_X_FCC_OPS_KEY'] ?? '')),
            $this->get_authorization_bearer(),
        ] as $candidate) {
            if($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private function ensure_access(): void {
        if(!defined('FCC_OPS_READONLY_ENABLED') || !FCC_OPS_READONLY_ENABLED || trim((string) FCC_OPS_READONLY_KEY) === '') {
            $this->respond_error('ops_readonly_disabled', 'Readonly ops endpoint is not enabled.', 404);
        }

        $provided_key = $this->get_request_key();
        if($provided_key === '' || !hash_equals((string) FCC_OPS_READONLY_KEY, $provided_key)) {
            $this->respond_error('invalid_key', 'Readonly ops key is invalid.', 403);
        }
    }

    private function get_object($value): \stdClass {
        if(is_string($value)) {
            $decoded = json_decode($value ?? '{}');
            $value = $decoded === null ? (object) [] : $decoded;
        }

        if(is_array($value)) {
            $value = (object) $value;
        }

        if(!$value instanceof \stdClass) {
            $value = (object) [];
        }

        return $value;
    }

    private function get_list($value): array {
        if(is_object($value)) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            return [];
        }

        $list = [];

        foreach($value as $item) {
            if(is_object($item)) {
                $item = (array) $item;
            }

            if(is_array($item)) {
                $list[] = $item;
            }
        }

        return array_values($list);
    }

    private function get_assoc_array($value): array {
        if(is_object($value)) {
            return (array) $value;
        }

        return is_array($value) ? $value : [];
    }

    private function first_available_datetime(array $item, array $fields): string {
        foreach($fields as $field) {
            $value = trim((string) ($item[$field] ?? ''));
            if($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function sort_items_by_datetimes(array $items, array $fields): array {
        usort($items, function(array $a, array $b) use ($fields) {
            return strcmp(
                $this->first_available_datetime($b, $fields),
                $this->first_available_datetime($a, $fields)
            );
        });

        return $items;
    }

    private function excerpt(?string $value, int $limit = 220): string {
        $value = trim((string) $value);

        if($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        if(mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, max(1, $limit - 1))) . '…';
    }

    private function get_datetime_age_minutes(?string $datetime): ?int {
        $datetime = trim((string) $datetime);

        if($datetime === '') {
            return null;
        }

        try {
            $target = new \DateTimeImmutable($datetime);
            $now = new \DateTimeImmutable();
            $diff_seconds = max(0, $now->getTimestamp() - $target->getTimestamp());

            return (int) floor($diff_seconds / 60);
        } catch(\Throwable $exception) {
            return null;
        }
    }

    private function get_setting_object_from_database(string $key): \stdClass {
        $escaped_key = database()->real_escape_string($key);
        $result = database()->query("SELECT `value` FROM `settings` WHERE `key` = '{$escaped_key}' LIMIT 1");

        if(!$result || !$result->num_rows) {
            return (object) [];
        }

        $row = $result->fetch_object();

        return $this->get_object($row->value ?? null);
    }

    private function is_plan_active(?string $plan_expiration_date): bool {
        $plan_expiration_date = trim((string) $plan_expiration_date);

        if($plan_expiration_date === '') {
            return true;
        }

        try {
            return (new \DateTimeImmutable($plan_expiration_date)) >= (new \DateTimeImmutable());
        } catch(\Throwable $exception) {
            return false;
        }
    }

    private function get_plan_summary($plan_id, $plan_settings = null): array {
        $plan_settings = $this->get_object($plan_settings);
        $plan = null;

        try {
            $plan = (new Plan())->get_plan_by_id($plan_id);
        } catch(\Throwable $exception) {
        }

        $plan_object = is_object($plan) ? $plan : (object) [];
        $resolved_settings = $this->get_object($plan_object->settings ?? $plan_settings);
        $prices = [];

        if(isset($plan_object->prices)) {
            $raw_prices = is_object($plan_object->prices) ? (array) $plan_object->prices : (array) $plan_object->prices;
            foreach($raw_prices as $key => $value) {
                if(is_scalar($value) || is_null($value)) {
                    $prices[(string) $key] = $value;
                }
            }
        }

        return [
            'plan_id' => (string) $plan_id,
            'name' => (string) ($plan_object->name ?? ucfirst((string) $plan_id)),
            'description' => (string) ($plan_object->description ?? ''),
            'color' => (string) ($plan_object->color ?? ''),
            'status' => isset($plan_object->status) ? (int) $plan_object->status : 1,
            'trial_days' => isset($plan_object->trial_days) ? (int) $plan_object->trial_days : 0,
            'prices' => $prices,
            'ai_growth_plan_is_enabled' => (bool) ($plan_settings->ai_growth_plan_is_enabled ?? $resolved_settings->ai_growth_plan_is_enabled ?? false),
        ];
    }

    private function get_status_label(int $status): string {
        return match($status) {
            1 => 'active',
            2 => 'disabled',
            default => 'pending',
        };
    }

    private function build_public_link_url(object $link): string {
        $slug = ltrim((string) ($link->url ?? ''), '/');

        if(!empty($link->domain_id) && !empty($link->host)) {
            $base = rtrim((string) ($link->scheme ?? 'https://') . (string) $link->host, '/');
            $suffix = (int) ($link->domain_link_id ?? 0) === (int) ($link->link_id ?? 0) ? '' : '/' . $slug;

            return $base . $suffix;
        }

        return $slug !== '' ? SITE_URL . $slug : '';
    }

    private function get_main_biolink(int $user_id): ?object {
        if($user_id <= 0) {
            return null;
        }

        $main_biolink_id = (int) (fc_get_user_main_biolink_id($user_id, false) ?? 0);

        if(!$main_biolink_id) {
            return null;
        }

        $result = database()->query("SELECT `links`.`link_id`, `links`.`domain_id`, `links`.`url`, `links`.`is_enabled`, `links`.`datetime`, `links`.`last_datetime`, `domains`.`scheme`, `domains`.`host`, `domains`.`link_id` AS `domain_link_id`
            FROM `links`
            LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id`
            WHERE `links`.`user_id` = {$user_id}
              AND `links`.`type` = 'biolink'
              AND `links`.`link_id` = {$main_biolink_id}
            LIMIT 1");

        return $result ? ($result->fetch_object() ?: null) : null;
    }

    private function get_biolink_block_counts(array $link_ids): array {
        $link_ids = array_values(array_filter(array_map('intval', $link_ids)));

        if(empty($link_ids)) {
            return [];
        }

        $counts = [];
        $link_ids_sql = implode(',', $link_ids);
        $result = database()->query("SELECT `link_id`, COUNT(*) AS `total`
            FROM `biolinks_blocks`
            WHERE `link_id` IN ({$link_ids_sql})
            GROUP BY `link_id`");

        while($row = $result->fetch_object()) {
            $counts[(int) ($row->link_id ?? 0)] = (int) ($row->total ?? 0);
        }

        return $counts;
    }

    private function get_apps_payload(int $user_id): array {
        $result = database()->query("SELECT `links`.`link_id`, `links`.`domain_id`, `links`.`url`, `links`.`is_enabled`, `links`.`datetime`, `links`.`last_datetime`, `domains`.`scheme`, `domains`.`host`, `domains`.`link_id` AS `domain_link_id`
            FROM `links`
            LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id`
            WHERE `links`.`user_id` = {$user_id}
              AND `links`.`type` = 'biolink'");

        $rows = [];
        $link_ids = [];

        while($row = $result->fetch_object()) {
            $rows[] = $row;
            $link_ids[] = (int) ($row->link_id ?? 0);
        }

        $block_counts = $this->get_biolink_block_counts($link_ids);
        $main_biolink = $this->get_main_biolink($user_id);
        $main_biolink_id = (int) ($main_biolink->link_id ?? 0);
        $apps = [];
        $enabled_biolinks = 0;
        $total_blocks = 0;

        foreach($rows as $row) {
            $link_id = (int) ($row->link_id ?? 0);
            $public_url = $this->build_public_link_url($row);
            $total_block_count = (int) ($block_counts[$link_id] ?? 0);
            $total_blocks += $total_block_count;

            if((int) ($row->is_enabled ?? 1) === 1) {
                $enabled_biolinks++;
            }

            $apps[] = [
                'link_id' => $link_id,
                'slug' => (string) ($row->url ?? ''),
                'public_url' => $public_url,
                'nfc_url' => $public_url !== '' ? \Altum\Link::get_share_tracking_url($public_url, 'nfc_card', 'nfc_tap', 'nfc_card') : '',
                'is_enabled' => (int) ($row->is_enabled ?? 1) === 1,
                'datetime' => $row->datetime ?? null,
                'last_datetime' => $row->last_datetime ?? null,
                'total_blocks' => $total_block_count,
                'is_main' => $main_biolink_id > 0 && $main_biolink_id === $link_id,
            ];
        }

        $apps = $this->sort_items_by_datetimes($apps, ['last_datetime', 'datetime']);
        $main_app = null;

        foreach($apps as $app) {
            if(!empty($app['is_main'])) {
                $main_app = $app;
                break;
            }
        }

        if(!$main_app) {
            $main_app = $apps[0] ?? null;
        }

        $total_links = (int) (db()->where('user_id', $user_id)->getValue('links', 'COUNT(*)') ?? 0);
        $total_projects = (int) (db()->where('user_id', $user_id)->getValue('projects', 'COUNT(*)') ?? 0);

        return [
            'totals' => [
                'total_biolinks' => count($apps),
                'enabled_biolinks' => $enabled_biolinks,
                'total_blocks' => $total_blocks,
                'total_links' => $total_links,
                'total_projects' => $total_projects,
            ],
            'main_app' => $main_app,
            'latest_updated_app' => $apps[0] ?? null,
            'apps' => array_slice($apps, 0, 8),
        ];
    }

    private function get_biolink_debug_link(int $user_id, int $requested_link_id = 0): ?object {
        if($user_id <= 0) {
            return null;
        }

        $where_link_id = $requested_link_id > 0 ? " AND `links`.`link_id` = {$requested_link_id}" : '';
        $order_by = $requested_link_id > 0 ? '' : " ORDER BY `links`.`last_datetime` DESC, `links`.`datetime` DESC, `links`.`link_id` DESC";
        $limit = ' LIMIT 1';

        $result = database()->query("SELECT `links`.`link_id`, `links`.`user_id`, `links`.`domain_id`, `links`.`url`, `links`.`is_enabled`, `links`.`datetime`, `links`.`last_datetime`, `links`.`biolink_theme_id`, `links`.`settings`, `links`.`additional`, `domains`.`scheme`, `domains`.`host`, `domains`.`link_id` AS `domain_link_id`
            FROM `links`
            LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id`
            WHERE `links`.`user_id` = {$user_id}
              AND `links`.`type` = 'biolink'{$where_link_id}{$order_by}{$limit}");

        return $result ? ($result->fetch_object() ?: null) : null;
    }

    private function get_biolink_debug_blocks(int $link_id): array {
        if($link_id <= 0) {
            return [];
        }

        $result = database()->query("SELECT `biolink_block_id`, `type`, `location_url`, `settings`, `order`, `is_enabled`, `datetime`, `last_datetime`
            FROM `biolinks_blocks`
            WHERE `link_id` = {$link_id}
            ORDER BY `order` ASC, `biolink_block_id` ASC");

        $blocks = [];

        while($row = $result->fetch_object()) {
            $settings = $this->get_assoc_array($this->get_object($row->settings ?? null));

            $blocks[] = [
                'biolink_block_id' => (int) ($row->biolink_block_id ?? 0),
                'type' => (string) ($row->type ?? ''),
                'location_url' => (string) ($row->location_url ?? ''),
                'order' => (int) ($row->order ?? 0),
                'is_enabled' => (int) ($row->is_enabled ?? 0),
                'datetime' => $row->datetime ?? null,
                'last_datetime' => $row->last_datetime ?? null,
                'settings' => $settings,
                'label_preview' => (string) (
                    $settings['title']
                    ?? $settings['heading']
                    ?? $settings['text']
                    ?? $settings['name']
                    ?? $settings['button_text']
                    ?? ''
                ),
            ];
        }

        return $blocks;
    }

    private function get_biolink_restore_debug_payload(object $user): array {
        $user_id = (int) ($user->user_id ?? 0);
        $requested_link_id = $this->get_param_int('link_id');
        $link = $this->get_biolink_debug_link($user_id, $requested_link_id);

        if(!$link) {
            return [
                'link' => null,
                'current' => [
                    'blocks' => [],
                ],
                'backup' => [
                    'available' => false,
                ],
            ];
        }

        $additional = $this->get_assoc_array($this->get_object($link->additional ?? null));
        $settings = $this->get_assoc_array($this->get_object($link->settings ?? null));
        $backup = $this->get_assoc_array($this->get_object($additional['fcc_ai_bundle_backup'] ?? []));
        $bundle_last_restore = $this->get_assoc_array($this->get_object($additional['fcc_ai_bundle_last_restore'] ?? []));
        $layout_backup = $this->get_assoc_array($this->get_object($additional['fcc_ai_layout_backup'] ?? []));
        $layout_last_restore = $this->get_assoc_array($this->get_object($additional['fcc_ai_layout_last_restore'] ?? []));
        $theme_apply_state = $this->get_assoc_array($this->get_object($additional['fcc_ai_theme_apply_state'] ?? []));
        $review_summary = $this->get_assoc_array($this->get_object($additional['fcc_ai_review_summary'] ?? []));
        $current_blocks = $this->get_biolink_debug_blocks((int) ($link->link_id ?? 0));
        $backup_blocks = $this->get_list($backup['blocks'] ?? []);

        return [
            'link' => [
                'link_id' => (int) ($link->link_id ?? 0),
                'slug' => (string) ($link->url ?? ''),
                'public_url' => $this->build_public_link_url($link),
                'is_enabled' => (int) ($link->is_enabled ?? 1) === 1,
                'datetime' => $link->datetime ?? null,
                'last_datetime' => $link->last_datetime ?? null,
                'biolink_theme_id' => (int) ($link->biolink_theme_id ?? 0),
            ],
            'current' => [
                'settings' => $settings,
                'biolink_theme_id' => (int) ($link->biolink_theme_id ?? 0),
                'blocks_total' => count($current_blocks),
                'blocks' => $current_blocks,
            ],
            'backup' => [
                'available' => !empty($backup_blocks) && !empty($backup['captured_at']),
                'captured_at' => !empty($backup['captured_at']) ? (string) $backup['captured_at'] : null,
                'review_key' => trim((string) ($backup['review_key'] ?? '')),
                'biolink_theme_id' => (int) ($backup['biolink_theme_id'] ?? 0),
                'blocks_total' => count($backup_blocks),
                'link_settings' => $this->get_assoc_array($this->get_object($backup['link_settings'] ?? [])),
                'blocks' => $backup_blocks,
            ],
            'bundle_last_restore' => [
                'restored_at' => !empty($bundle_last_restore['restored_at']) ? (string) $bundle_last_restore['restored_at'] : null,
                'restored_blocks' => max(0, (int) ($bundle_last_restore['restored_blocks'] ?? 0)),
                're_enabled_blocks' => max(0, (int) ($bundle_last_restore['re_enabled_blocks'] ?? 0)),
                'hidden_new_blocks' => max(0, (int) ($bundle_last_restore['hidden_new_blocks'] ?? 0)),
            ],
            'layout_backup' => [
                'available' => !empty($layout_backup['blocks']) && !empty($layout_backup['captured_at']),
                'captured_at' => !empty($layout_backup['captured_at']) ? (string) $layout_backup['captured_at'] : null,
                'review_key' => trim((string) ($layout_backup['review_key'] ?? '')),
                'blocks_total' => count((array) ($layout_backup['blocks'] ?? [])),
            ],
            'layout_last_restore' => [
                'restored_at' => !empty($layout_last_restore['restored_at']) ? (string) $layout_last_restore['restored_at'] : null,
                'restored_blocks' => max(0, (int) ($layout_last_restore['restored_blocks'] ?? 0)),
                're_enabled_blocks' => max(0, (int) ($layout_last_restore['re_enabled_blocks'] ?? 0)),
            ],
            'theme_apply_state' => $theme_apply_state,
            'review_summary' => $review_summary,
        ];
    }

    private function get_meta_summary($preferences, bool $is_active_pro = false): array {
        $preferences = $this->get_object($preferences);
        $meta = $this->get_object($preferences->meta ?? null);
        $card_status = (int) ($meta->card_status ?? 0);
        $card_sent_at = (string) ($meta->fcc_nfc_sent_at ?? '');
        $card_required = (
            (int) ($meta->fcc_nfc_required ?? 0) === 1
            || !isset($meta->card_status)
            || $card_status === 0
        );

        $city_line = trim(implode(' ', array_filter([
            trim((string) ($meta->zip ?? '')),
            trim((string) ($meta->city ?? '')),
        ])));

        return [
            'phone' => trim((string) ($meta->phone ?? '')),
            'forever_id' => trim((string) ($meta->foreverId ?? $meta->forever_id ?? $meta->foreverID ?? '')),
            'approval_datetime' => (string) ($meta->fcc_access_approved_at ?? ''),
            'approval_email_sent_at' => (string) ($meta->fcc_access_approval_email_sent_at ?? ''),
            'card_requested_at' => (string) ($meta->fcc_nfc_requested_at ?? ''),
            'card_sent_at' => $card_sent_at,
            'card_status' => $card_status,
            'card_required' => $is_active_pro && $card_sent_at === '' && $card_required,
            'send_card_email_at' => (string) ($meta->send_card_email ?? ''),
            'limited' => (int) ($meta->limited ?? 0) === 1,
            'address_lines' => array_values(array_filter([
                trim((string) ($meta->address ?? '')),
                $city_line,
                trim((string) ($meta->country ?? '')),
            ])),
        ];
    }

    private function get_active_manual_ai_tier(\stdClass $access): string {
        $manual_tier = trim((string) ($access->manual_tier ?? ''));
        $manual_unlocked_at = trim((string) ($access->manual_unlocked_at ?? ''));

        if($manual_tier === '' || $manual_unlocked_at === '') {
            return '';
        }

        try {
            $expires_at = (new \DateTimeImmutable($manual_unlocked_at))->modify('+30 days');
            return $expires_at >= (new \DateTimeImmutable()) ? $manual_tier : '';
        } catch(\Throwable $exception) {
            return '';
        }
    }

    private function get_ai_summary(object $user): array {
        $preferences = $this->get_object($user->preferences ?? null);
        $plan_settings = $this->get_object($user->plan_settings ?? null);
        $access = $this->get_object($preferences->leader_ai_access ?? null);
        $profile = $this->get_object($preferences->leader_ai_profile ?? null);
        $job = $this->get_object($preferences->leader_ai_app_review_job ?? null);
        $mentor = $this->get_object($preferences->leader_ai_admin_coaching ?? null);

        $weekly_checkins = $this->sort_items_by_datetimes(
            $this->get_list($preferences->leader_ai_weekly_checkins ?? []),
            ['submitted_at']
        );

        $weekly_plans = $this->sort_items_by_datetimes(
            $this->get_list($preferences->leader_ai_weekly_plans ?? []),
            ['checkin_submitted_at', 'generated_at']
        );

        $app_reviews = $this->sort_items_by_datetimes(
            $this->get_list($preferences->leader_ai_app_reviews ?? []),
            ['generated_at']
        );

        $manual_tier_active = $this->get_active_manual_ai_tier($access);
        $plan_feature_enabled = (bool) ($plan_settings->ai_growth_plan_is_enabled ?? false);
        $plan_feature_active = $plan_feature_enabled && $this->is_plan_active((string) ($user->plan_expiration_date ?? ''));
        $has_access = $plan_feature_active || $manual_tier_active !== '';
        $latest_weekly_checkin = $this->get_assoc_array($weekly_checkins[0] ?? []);
        $latest_weekly_plan = $this->get_assoc_array($weekly_plans[0] ?? []);
        $latest_app_review = $this->get_assoc_array($app_reviews[0] ?? []);
        $latest_app_review_performance = $this->get_assoc_array($latest_app_review['performance_snapshot'] ?? []);
        $mentor_guidance = trim((string) ($mentor->ai_guidance ?? ''));

        return [
            'has_access' => $has_access,
            'access_source' => $manual_tier_active !== '' ? 'manual_tier' : ($plan_feature_active ? 'plan' : 'none'),
            'plan_feature_enabled' => $plan_feature_enabled,
            'plan_feature_active' => $plan_feature_active,
            'manual_tier' => (string) ($access->manual_tier ?? ''),
            'manual_tier_active' => $manual_tier_active,
            'manual_note' => (string) ($access->manual_note ?? ''),
            'manual_unlocked_at' => $access->manual_unlocked_at ?? null,
            'starter_app_review_used' => min(1, max(0, (int) ($access->starter_app_review_used ?? (!empty($app_reviews) ? 1 : 0)))),
            'starter_weekly_plan_used' => min(1, max(0, (int) ($access->starter_weekly_plan_used ?? (!empty($weekly_plans) ? 1 : 0)))),
            'counts' => [
                'weekly_checkins' => count($weekly_checkins),
                'weekly_plans' => count($weekly_plans),
                'app_reviews' => count($app_reviews),
            ],
            'profile' => [
                'submitted_at' => $profile->submitted_at ?? null,
                'primary_goal' => (string) ($profile->primary_goal ?? ''),
                'priority_offer' => (string) ($profile->priority_offer ?? ''),
                'biggest_blocker' => (string) ($profile->biggest_blocker ?? ''),
                'communication_style' => (string) ($profile->communication_style ?? ''),
                'follow_up_readiness' => (string) ($profile->follow_up_readiness ?? ''),
            ],
            'latest_weekly_checkin' => [
                'submitted_at' => $latest_weekly_checkin['submitted_at'] ?? null,
                'weekly_priority' => (string) ($latest_weekly_checkin['weekly_priority'] ?? ''),
                'weekly_energy' => (string) ($latest_weekly_checkin['weekly_energy'] ?? ''),
                'ai_need' => (string) ($latest_weekly_checkin['ai_need'] ?? ''),
                'weekly_context_preview' => $this->excerpt((string) ($latest_weekly_checkin['weekly_context'] ?? '')),
            ],
            'latest_weekly_plan' => [
                'generated_at' => $latest_weekly_plan['generated_at'] ?? null,
                'checkin_submitted_at' => $latest_weekly_plan['checkin_submitted_at'] ?? null,
                'headline' => (string) ($latest_weekly_plan['headline'] ?? ''),
                'focus' => (string) ($latest_weekly_plan['focus'] ?? ''),
                'power_move' => (string) ($latest_weekly_plan['power_move'] ?? ''),
                'brutal_truth' => $this->excerpt((string) ($latest_weekly_plan['brutal_truth'] ?? ''), 260),
                'summary' => $this->excerpt((string) ($latest_weekly_plan['summary'] ?? ''), 260),
            ],
            'latest_app_review' => [
                'generated_at' => $latest_app_review['generated_at'] ?? null,
                'selected_link_id' => (int) ($latest_app_review['selected_link_id'] ?? 0),
                'selected_app_name' => (string) ($latest_app_review['selected_app_name'] ?? ''),
                'selected_app_url' => (string) ($latest_app_review['selected_app_url'] ?? ''),
                'quality_score' => (int) ($latest_app_review['quality_score'] ?? 0),
                'quality_level' => (string) ($latest_app_review['quality_level'] ?? ''),
                'headline' => (string) ($latest_app_review['headline'] ?? ''),
                'top_recommendation' => $this->excerpt((string) ($latest_app_review['top_recommendation'] ?? ''), 260),
                'first_move' => $this->excerpt((string) ($latest_app_review['first_move'] ?? ''), 220),
                'weighted_signal_score' => (int) ($latest_app_review_performance['weighted_signal_score'] ?? 0),
            ],
            'app_review_job' => [
                'status' => (string) ($job->status ?? 'idle'),
                'job_id' => (string) ($job->job_id ?? ''),
                'started_at' => $job->started_at ?? null,
                'completed_at' => $job->completed_at ?? null,
                'selected_link_id' => (int) ($job->selected_link_id ?? 0),
                'error_message' => (string) ($job->error_message ?? ''),
            ],
            'mentor_guidance' => [
                'has_guidance' => $mentor_guidance !== '',
                'preview' => $this->excerpt($mentor_guidance, 260),
                'length' => mb_strlen($mentor_guidance),
            ],
        ];
    }

    private function get_recent_global_billing_events(int $limit = 10): array {
        $limit = min(20, max(1, $limit));
        $events = [];
        $result = database()->query("SELECT `data`.`datum_id`, `data`.`user_id`, `data`.`data`, `data`.`datetime`, `users`.`name`, `users`.`email`
            FROM `data`
            LEFT JOIN `users` ON `data`.`user_id` = `users`.`user_id`
            WHERE `data`.`type` = '" . Billing::DATA_TYPE . "'
            ORDER BY `data`.`datum_id` DESC
            LIMIT {$limit}");

        while($row = $result->fetch_object()) {
            $event = $this->get_object($row->data ?? null);
            $events[] = [
                'datum_id' => (int) ($row->datum_id ?? 0),
                'user_id' => (int) ($row->user_id ?? 0),
                'user_name' => (string) ($row->name ?? ''),
                'user_email' => (string) ($row->email ?? ''),
                'event_type' => (string) ($event->event_type ?? 'unknown'),
                'processor' => (string) ($event->processor ?? 'stripe'),
                'billing_state_before' => (string) ($event->billing_state_before ?? ''),
                'billing_state_after' => (string) ($event->billing_state_after ?? ''),
                'reason_code' => (string) ($event->reason_code ?? ''),
                'reason_text' => (string) ($event->reason_text ?? ''),
                'stripe_status' => (string) ($event->stripe_status ?? ''),
                'stripe_subscription_id' => (string) ($event->stripe_subscription_id ?? ''),
                'stripe_invoice_id' => (string) ($event->stripe_invoice_id ?? ''),
                'occurred_at' => (string) ($event->occurred_at ?? $row->datetime ?? ''),
            ];
        }

        return $events;
    }

    private function get_cron_diagnostics_payload(): array {
        $cron_settings = $this->get_setting_object_from_database('cron');
        $webhooks_settings = $this->get_setting_object_from_database('webhooks');
        $cron_key = trim((string) ($cron_settings->key ?? ''));
        $last_run_at = is_scalar($cron_settings->cron_datetime ?? null) ? (string) $cron_settings->cron_datetime : null;
        $last_reset_at = is_scalar($cron_settings->reset_date ?? null) ? (string) $cron_settings->reset_date : null;
        $stale_threshold_minutes = 15;
        $last_run_age_minutes = $this->get_datetime_age_minutes($last_run_at);
        $cron_url = $cron_key !== '' ? url('cron?key=' . rawurlencode($cron_key)) : '';
        $cron_curl_command = $cron_url !== '' ? 'curl -fsS "' . $cron_url . "\" >/dev/null 2>&1" : '';
        $cron_wget_command = $cron_url !== '' ? 'wget -q -O - "' . $cron_url . "\" >/dev/null 2>&1" : '';

        return [
            'key_configured' => $cron_key !== '',
            'last_run_at' => $last_run_at,
            'last_run_age_minutes' => $last_run_age_minutes,
            'last_run_processing_seconds' => isset($cron_settings->cron_datetime_processing) ? (float) $cron_settings->cron_datetime_processing : null,
            'last_reset_at' => $last_reset_at,
            'last_reset_age_minutes' => $this->get_datetime_age_minutes($last_reset_at),
            'last_reset_processing_seconds' => isset($cron_settings->reset_date_processing) ? (float) $cron_settings->reset_date_processing : null,
            'stale_threshold_minutes' => $stale_threshold_minutes,
            'is_stale' => $last_run_age_minutes === null ? true : $last_run_age_minutes > $stale_threshold_minutes,
            'recommended_interval_minutes' => 5,
            'trigger_url' => $cron_url !== '' ? $cron_url : null,
            'commands' => [
                'curl' => $cron_curl_command !== '' ? $cron_curl_command : null,
                'wget' => $cron_wget_command !== '' ? $cron_wget_command : null,
            ],
            'webhooks' => [
                'start_configured' => trim((string) ($webhooks_settings->cron_start ?? '')) !== '',
                'end_configured' => trim((string) ($webhooks_settings->cron_end ?? '')) !== '',
            ],
        ];
    }

    private function get_ai_diagnostics_payload(): array {
        $settings_main = $this->get_setting_object_from_database('main');
        $settings_aix = $this->get_setting_object_from_database('aix');
        $shared_aix_api_key = trim((string) ($settings_aix->openai_api_key ?? ''));
        $shared_main_api_key = trim((string) ($settings_main->openai_api_key ?? ''));
        $shared_api_key_source = $shared_aix_api_key !== '' ? 'aix' : ($shared_main_api_key !== '' ? 'main' : 'none');
        $shared_api_key_configured = $shared_api_key_source !== 'none';
        $default_model = fc_get_resolved_openai_model($settings_main->openai_model ?? '');

        return [
            'feature_flag_enabled' => defined('AI_ENABLED') ? AI_ENABLED : false,
            'shared_openai_key_configured' => $shared_api_key_configured,
            'shared_openai_key_source' => $shared_api_key_source,
            'default_model' => $default_model,
            'shared_ai_ready' => $shared_api_key_configured,
            'feature_flag_is_runtime_blocker' => false,
            'note' => $shared_api_key_configured
                ? 'Shared AI key is configured for server-side AI flows.'
                : 'Shared OpenAI key is missing. AI flows that rely on server-side shared credentials can fail.',
            'flag_usage_note' => 'AI_ENABLED is currently only a global indicator in config/health. AI Plan runtime depends on shared or personal OpenAI credentials and user plan access.',
        ];
    }

    private function get_health_payload(): array {
        $billing_dashboard = (new Billing())->get_dashboard_payload();
        $cron_diagnostics = $this->get_cron_diagnostics_payload();
        $ai_diagnostics = $this->get_ai_diagnostics_payload();

        return [
            'server_time' => get_date(),
            'php_version' => PHP_VERSION,
            'database' => [
                'connected' => true,
                'name' => defined('DATABASE_NAME') ? DATABASE_NAME : '',
            ],
            'features' => [
                'payment_enabled' => !empty(settings()->payment->is_enabled),
                'stripe_enabled' => !empty(settings()->stripe->is_enabled),
                'ai_enabled' => $ai_diagnostics['feature_flag_enabled'] ?? false,
                'brevo_enabled' => defined('BREVO_API_KEY') ? trim((string) BREVO_API_KEY) !== '' : false,
                'ops_readonly_enabled' => FCC_OPS_READONLY_ENABLED,
            ],
            'cron' => $cron_diagnostics,
            'ai' => $ai_diagnostics,
            'billing' => [
                'counts' => $billing_dashboard['counts'] ?? [],
                'latest_event' => $this->get_recent_global_billing_events(1)[0] ?? null,
            ],
            'allowed_scopes' => ['health', 'overview', 'ai_feedback', 'plans', 'billing', 'collaborators', 'collaborator'],
        ];
    }

    private function get_overview_payload(): array {
        $now = get_date();
        $seven_days_ago = (new \DateTimeImmutable())->modify('-7 days')->format('Y-m-d H:i:s');
        $billing_dashboard = (new Billing())->get_dashboard_payload();

        $totals = [
            'collaborators_total' => (int) (database()->query("SELECT COUNT(*) AS `total` FROM `users` WHERE `type` = 0")->fetch_object()->total ?? 0),
            'collaborators_active' => (int) (database()->query("SELECT COUNT(*) AS `total` FROM `users` WHERE `type` = 0 AND `status` = 1")->fetch_object()->total ?? 0),
            'collaborators_pending' => (int) (database()->query("SELECT COUNT(*) AS `total` FROM `users` WHERE `type` = 0 AND `status` = 0")->fetch_object()->total ?? 0),
            'collaborators_disabled' => (int) (database()->query("SELECT COUNT(*) AS `total` FROM `users` WHERE `type` = 0 AND `status` = 2")->fetch_object()->total ?? 0),
            'active_pro' => (int) (database()->query("SELECT COUNT(*) AS `total` FROM `users` WHERE `type` = 0 AND `status` = 1 AND CAST(`plan_id` AS CHAR) = '5' AND (`plan_expiration_date` IS NULL OR `plan_expiration_date` = '' OR `plan_expiration_date` >= '{$now}')")->fetch_object()->total ?? 0),
            'biolinks_total' => (int) (database()->query("SELECT COUNT(*) AS `total`
                FROM `links`
                INNER JOIN `users` ON `links`.`user_id` = `users`.`user_id`
                WHERE `links`.`type` = 'biolink' AND `users`.`type` = 0")->fetch_object()->total ?? 0),
            'biolinks_updated_7d' => (int) (database()->query("SELECT COUNT(*) AS `total`
                FROM `links`
                INNER JOIN `users` ON `links`.`user_id` = `users`.`user_id`
                WHERE `links`.`type` = 'biolink'
                  AND `users`.`type` = 0
                  AND COALESCE(`links`.`last_datetime`, `links`.`datetime`) >= '{$seven_days_ago}'")->fetch_object()->total ?? 0),
            'projects_total' => (int) (database()->query("SELECT COUNT(*) AS `total`
                FROM `projects`
                INNER JOIN `users` ON `projects`.`user_id` = `users`.`user_id`
                WHERE `users`.`type` = 0")->fetch_object()->total ?? 0),
        ];

        $plan_mix = [];
        $plan_mix_result = database()->query("SELECT CAST(`plan_id` AS CHAR) AS `plan_id`, COUNT(*) AS `total`
            FROM `users`
            WHERE `type` = 0
            GROUP BY `plan_id`
            ORDER BY `total` DESC
            LIMIT 6");

        while($row = $plan_mix_result->fetch_object()) {
            $plan_summary = $this->get_plan_summary($row->plan_id ?? '');
            $plan_mix[] = [
                'plan_id' => (string) ($row->plan_id ?? ''),
                'plan_name' => (string) ($plan_summary['name'] ?? ''),
                'users' => (int) ($row->total ?? 0),
            ];
        }

        $cron_diagnostics = $this->get_cron_diagnostics_payload();
        $ai_diagnostics = $this->get_ai_diagnostics_payload();

        return [
            'totals' => $totals,
            'plan_mix' => $plan_mix,
            'billing' => $billing_dashboard,
            'recent_billing_events' => $this->get_recent_global_billing_events(5),
            'health' => [
                'cron_last_run_at' => $cron_diagnostics['last_run_at'] ?? null,
                'cron_is_stale' => $cron_diagnostics['is_stale'] ?? true,
                'cron_key_configured' => $cron_diagnostics['key_configured'] ?? false,
                'shared_openai_key_configured' => $ai_diagnostics['shared_openai_key_configured'] ?? false,
                'shared_ai_ready' => $ai_diagnostics['shared_ai_ready'] ?? false,
                'ops_readonly_enabled' => FCC_OPS_READONLY_ENABLED,
            ],
        ];
    }

    private function resolve_ai_period_key(): string {
        $period = $this->get_param_string('period', '30d');

        return in_array($period, ['7d', '30d', '90d'], true) ? $period : '30d';
    }

    private function resolve_ai_period_start_datetime(string $period_key): string {
        return match($period_key) {
            '7d' => (new \DateTimeImmutable('-7 days'))->format('Y-m-d H:i:s'),
            '90d' => (new \DateTimeImmutable('-90 days'))->format('Y-m-d H:i:s'),
            default => (new \DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s'),
        };
    }

    private function get_ai_feedback_payload(): array {
        $period_key = $this->resolve_ai_period_key();
        $period_start_datetime = $this->resolve_ai_period_start_datetime($period_key);
        $limit = $this->get_param_limit('limit', 20, 1, 40);
        $assistant_type_filter = trim((string) fcc_ai_validate_assistant_type($this->get_param_string('assistant_type')));
        $reason_filter = trim((string) fcc_ai_normalize_feedback_reason($this->get_param_string('reason')));
        $language = $this->get_param_string('language', 'hr');

        $team_payload = fcc_ai_get_team_dashboard_payload($period_start_datetime, [], $limit, $language, $period_key);
        $recent_negative_feedback = array_values(array_filter((array) ($team_payload['recent_negative_feedback'] ?? []), function(array $item) use ($assistant_type_filter, $reason_filter) {
            if($assistant_type_filter !== '' && trim((string) ($item['assistant_type'] ?? '')) !== $assistant_type_filter) {
                return false;
            }

            if($reason_filter !== '' && trim((string) ($item['reason'] ?? '')) !== $reason_filter) {
                return false;
            }

            return true;
        }));

        $assistant_totals = [];
        $reason_totals = [];
        $owner_totals = [];

        foreach($recent_negative_feedback as $item) {
            $assistant_key = trim((string) ($item['assistant_type'] ?? 'unknown'));
            $assistant_label = trim((string) ($item['assistant_label'] ?? $assistant_key));
            $reason_key = trim((string) ($item['reason'] ?? 'other'));
            $reason_label = trim((string) ($item['reason_label'] ?? $reason_key));
            $owner_key = (int) ($item['user_id'] ?? 0);
            $owner_label = trim((string) ($item['owner_name'] ?? 'Unknown'));

            if(!isset($assistant_totals[$assistant_key])) {
                $assistant_totals[$assistant_key] = [
                    'assistant_type' => $assistant_key,
                    'assistant_label' => $assistant_label,
                    'total' => 0,
                ];
            }

            if(!isset($reason_totals[$reason_key])) {
                $reason_totals[$reason_key] = [
                    'reason' => $reason_key,
                    'reason_label' => $reason_label,
                    'total' => 0,
                ];
            }

            if(!isset($owner_totals[$owner_key])) {
                $owner_totals[$owner_key] = [
                    'user_id' => $owner_key,
                    'owner_name' => $owner_label,
                    'total' => 0,
                ];
            }

            $assistant_totals[$assistant_key]['total']++;
            $reason_totals[$reason_key]['total']++;
            $owner_totals[$owner_key]['total']++;
        }

        usort($recent_negative_feedback, static function(array $a, array $b) {
            return strcmp((string) ($b['datetime'] ?? ''), (string) ($a['datetime'] ?? ''));
        });

        $assistant_totals = array_values($assistant_totals);
        usort($assistant_totals, static function(array $a, array $b) {
            return (($b['total'] ?? 0) <=> ($a['total'] ?? 0))
                ?: strcmp((string) ($a['assistant_label'] ?? ''), (string) ($b['assistant_label'] ?? ''));
        });

        $reason_totals = array_values($reason_totals);
        usort($reason_totals, static function(array $a, array $b) {
            return (($b['total'] ?? 0) <=> ($a['total'] ?? 0))
                ?: strcmp((string) ($a['reason_label'] ?? ''), (string) ($b['reason_label'] ?? ''));
        });

        $owner_totals = array_values($owner_totals);
        usort($owner_totals, static function(array $a, array $b) {
            return (($b['total'] ?? 0) <=> ($a['total'] ?? 0))
                ?: strcmp((string) ($a['owner_name'] ?? ''), (string) ($b['owner_name'] ?? ''));
        });

        return [
            'period' => [
                'key' => $period_key,
                'start_datetime' => $period_start_datetime,
            ],
            'filters' => [
                'assistant_type' => $assistant_type_filter,
                'reason' => $reason_filter,
            ],
            'summary' => [
                'negative_feedback_total' => count($recent_negative_feedback),
                'assistant_breakdown' => $assistant_totals,
                'reason_breakdown' => $reason_totals,
                'owner_breakdown' => array_slice($owner_totals, 0, 12),
            ],
            'recent_negative_feedback' => $recent_negative_feedback,
            'top_topics' => array_values(array_slice((array) ($team_payload['top_topics'] ?? []), 0, 10)),
            'rising_topics' => array_values(array_slice((array) ($team_payload['rising_topics'] ?? []), 0, 10)),
            'webinar_candidates' => array_values(array_slice((array) ($team_payload['webinar_candidates'] ?? []), 0, 8)),
            'assistant_performance' => array_values(array_slice((array) ($team_payload['assistant_performance'] ?? []), 0, 8)),
            'help_watchlist' => array_values(array_slice((array) ($team_payload['help_watchlist'] ?? []), 0, 8)),
        ];
    }

    private function build_plan_catalog_entry(object $plan, array $user_counts, array $subscription_counts): array {
        $plan_settings = $this->get_object($plan->settings ?? null);
        $plan_prices = [];

        if(isset($plan->prices)) {
            $raw_prices = is_object($plan->prices) ? (array) $plan->prices : (array) $plan->prices;
            foreach($raw_prices as $key => $value) {
                if(is_scalar($value) || is_null($value)) {
                    $plan_prices[(string) $key] = $value;
                }
            }
        }

        $plan_id = (string) ($plan->plan_id ?? '');

        return [
            'plan_id' => $plan_id,
            'name' => (string) ($plan->name ?? ucfirst($plan_id)),
            'description' => (string) ($plan->description ?? ''),
            'color' => (string) ($plan->color ?? ''),
            'status' => isset($plan->status) ? (int) $plan->status : 1,
            'trial_days' => isset($plan->trial_days) ? (int) $plan->trial_days : 0,
            'ai_growth_plan_is_enabled' => (bool) ($plan_settings->ai_growth_plan_is_enabled ?? false),
            'prices' => $plan_prices,
            'users_total' => (int) ($user_counts[$plan_id] ?? 0),
            'active_subscriptions' => (int) ($subscription_counts[$plan_id] ?? 0),
        ];
    }

    private function get_plans_payload(): array {
        $user_counts = [];
        $subscription_counts = [];
        $plans = [];

        $result = database()->query("SELECT CAST(`plan_id` AS CHAR) AS `plan_id`, COUNT(*) AS `total`
            FROM `users`
            WHERE `type` = 0
            GROUP BY `plan_id`");

        while($row = $result->fetch_object()) {
            $user_counts[(string) ($row->plan_id ?? '')] = (int) ($row->total ?? 0);
        }

        $subscription_result = database()->query("SELECT CAST(`plan_id` AS CHAR) AS `plan_id`, COUNT(*) AS `total`
            FROM `users`
            WHERE `type` = 0 AND `payment_subscription_id` <> ''
            GROUP BY `plan_id`");

        while($row = $subscription_result->fetch_object()) {
            $subscription_counts[(string) ($row->plan_id ?? '')] = (int) ($row->total ?? 0);
        }

        foreach(db()->orderBy('`order`', 'ASC')->get('plans') as $plan) {
            $plan->settings = json_decode($plan->settings ?? '{}');
            $plan->prices = json_decode($plan->prices ?? '{}');
            $plans[] = $this->build_plan_catalog_entry($plan, $user_counts, $subscription_counts);
        }

        $builtin_plan_ids = ['free', 'custom'];
        if(isset(settings()->plan_guest)) {
            $builtin_plan_ids[] = 'guest';
        }

        foreach($builtin_plan_ids as $builtin_plan_id) {
            $builtin_plan = (new Plan())->get_plan_by_id($builtin_plan_id);
            if($builtin_plan) {
                $plans[] = $this->build_plan_catalog_entry((object) $builtin_plan, $user_counts, $subscription_counts);
            }
        }

        return [
            'plans' => $plans,
            'totals' => [
                'plans_in_catalog' => count($plans),
                'collaborators_total' => array_sum($user_counts),
            ],
        ];
    }

    private function get_billing_payload(): array {
        $billing = new Billing();
        $limit = $this->get_param_limit('limit', 10, 1, 25);
        $filters = [];

        $state = $this->get_param_string('state');
        if(in_array($state, [Billing::STATE_PAST_DUE, Billing::STATE_PAST_DUE_CRITICAL, Billing::STATE_ACCESS_REVOKED], true)) {
            $filters['state'] = $state;
        }

        $processor = $this->get_param_string('processor');
        if($processor !== '') {
            $filters['processor'] = $processor;
        }

        $search = $this->get_param_string('search');
        if($search !== '') {
            $filters['search'] = $search;
        }

        $risk_users = [];

        foreach($billing->get_risk_users($filters, $limit, 0) as $entry) {
            $user = $entry['user'] ?? null;
            $summary = $entry['summary'] ?? [];

            if(!$user) {
                continue;
            }

            $risk_users[] = [
                'user_id' => (int) ($user->user_id ?? 0),
                'name' => (string) ($user->name ?? ''),
                'email' => (string) ($user->email ?? ''),
                'plan_id' => (string) ($user->plan_id ?? ''),
                'payment_processor' => (string) ($user->payment_processor ?? ''),
                'payment_subscription_id' => (string) ($user->payment_subscription_id ?? ''),
                'billing_summary' => $summary,
                'los_detail_url' => url('admin/leader-operating-system-leader?user_id=' . (int) ($user->user_id ?? 0) . '&period=30d'),
                'admin_user_url' => url('admin/user-view/' . (int) ($user->user_id ?? 0)),
            ];
        }

        return [
            'dashboard' => $billing->get_dashboard_payload(),
            'risk_users' => $risk_users,
            'recent_events' => $this->get_recent_global_billing_events($limit),
            'filters' => $filters,
        ];
    }

    private function get_collaborators_payload(): array {
        $query = $this->get_param_string('query');
        $limit = $this->get_param_limit('limit', 10, 1, 25);
        $where = "`type` = 0";

        if($query !== '') {
            $escaped_query = database()->real_escape_string($query);
            $where .= " AND (`name` LIKE '%{$escaped_query}%' OR `email` LIKE '%{$escaped_query}%')";
        }

        $results = [];
        $result = database()->query("SELECT `user_id`, `name`, `email`, `status`, `plan_id`, `plan_expiration_date`, `last_activity`, `datetime`
            FROM `users`
            WHERE {$where}
            ORDER BY COALESCE(`last_activity`, `datetime`) DESC, `user_id` DESC
            LIMIT {$limit}");

        while($row = $result->fetch_object()) {
            $plan_summary = $this->get_plan_summary($row->plan_id ?? '');

            $results[] = [
                'user_id' => (int) ($row->user_id ?? 0),
                'name' => (string) ($row->name ?? ''),
                'email' => (string) ($row->email ?? ''),
                'status' => (int) ($row->status ?? 0),
                'status_label' => $this->get_status_label((int) ($row->status ?? 0)),
                'plan_id' => (string) ($row->plan_id ?? ''),
                'plan_name' => (string) ($plan_summary['name'] ?? ''),
                'plan_expiration_date' => $row->plan_expiration_date ?? null,
                'last_activity' => $row->last_activity ?? null,
                'datetime' => $row->datetime ?? null,
                'ops_detail_hint' => [
                    'scope' => 'collaborator',
                    'user_id' => (int) ($row->user_id ?? 0),
                ],
            ];
        }

        return [
            'query' => $query,
            'results_total' => count($results),
            'results' => $results,
        ];
    }

    private function get_collaborator_user(): ?object {
        $user_id = $this->get_param_int('user_id');
        $email = $this->get_param_string('email');
        $query = db()->where('type', 0);

        if($user_id > 0) {
            $query->where('user_id', $user_id);
        } elseif($email !== '') {
            $query->where('email', $email);
        } else {
            return null;
        }

        return $query->getOne('users', [
            'user_id',
            'name',
            'email',
            'status',
            'type',
            'source',
            'language',
            'timezone',
            'country',
            'city_name',
            'plan_id',
            'plan_settings',
            'plan_expiration_date',
            'payment_processor',
            'payment_subscription_id',
            'preferences',
            'extra',
            'last_activity',
            'datetime',
        ]);
    }

    private function resolve_collaborator_section(callable $resolver, $fallback, array &$section_errors, string $section) {
        try {
            return $resolver();
        } catch(\Throwable $exception) {
            $section_errors[$section] = $exception->getMessage();

            return $fallback;
        }
    }

    private function get_collaborator_payload(object $user): array {
        $user_id = (int) ($user->user_id ?? 0);
        $billing_events_limit = $this->get_param_limit('billing_events_limit', 8, 1, 20);
        $section_errors = [];

        $plan = $this->resolve_collaborator_section(function() use ($user) {
            $plan_summary = $this->get_plan_summary($user->plan_id ?? '', $user->plan_settings ?? null);
            $is_active_pro = (string) ($user->plan_id ?? '') === '5' && $this->is_plan_active((string) ($user->plan_expiration_date ?? ''));

            return [
                'plan_id' => (string) ($user->plan_id ?? ''),
                'plan_expiration_date' => $user->plan_expiration_date ?? null,
                'is_plan_active' => $this->is_plan_active((string) ($user->plan_expiration_date ?? '')),
                'is_active_pro' => $is_active_pro,
                'payment_processor' => (string) ($user->payment_processor ?? ''),
                'payment_subscription_id' => (string) ($user->payment_subscription_id ?? ''),
                'summary' => $plan_summary,
            ];
        }, [
            'plan_id' => (string) ($user->plan_id ?? ''),
            'plan_expiration_date' => $user->plan_expiration_date ?? null,
            'is_plan_active' => $this->is_plan_active((string) ($user->plan_expiration_date ?? '')),
            'is_active_pro' => false,
            'payment_processor' => (string) ($user->payment_processor ?? ''),
            'payment_subscription_id' => (string) ($user->payment_subscription_id ?? ''),
            'summary' => [],
        ], $section_errors, 'plan');

        $meta = $this->resolve_collaborator_section(function() use ($user, $plan) {
            return $this->get_meta_summary($user->preferences ?? null, (bool) ($plan['is_active_pro'] ?? false));
        }, [], $section_errors, 'meta');

        $apps = $this->resolve_collaborator_section(function() use ($user_id) {
            return $this->get_apps_payload($user_id);
        }, [
            'totals' => [
                'total_biolinks' => 0,
                'enabled_biolinks' => 0,
                'total_blocks' => 0,
                'total_links' => 0,
                'total_projects' => 0,
            ],
            'main_app' => null,
            'latest_updated_app' => null,
            'apps' => [],
        ], $section_errors, 'apps');

        $ai = $this->resolve_collaborator_section(function() use ($user) {
            return $this->get_ai_summary($user);
        }, [
            'has_access' => false,
            'access_source' => 'unknown',
            'plan_feature_enabled' => false,
            'plan_feature_active' => false,
            'manual_tier' => '',
            'manual_tier_active' => '',
            'manual_note' => '',
            'manual_unlocked_at' => null,
            'starter_app_review_used' => 0,
            'starter_weekly_plan_used' => 0,
            'counts' => [
                'weekly_checkins' => 0,
                'weekly_plans' => 0,
                'app_reviews' => 0,
            ],
            'profile' => [],
            'latest_weekly_checkin' => [],
            'latest_weekly_plan' => [],
            'latest_app_review' => [],
            'app_review_job' => [],
            'mentor_guidance' => [
                'has_guidance' => false,
                'preview' => '',
                'length' => 0,
            ],
        ], $section_errors, 'ai');

        $billing = $this->resolve_collaborator_section(function() use ($user_id, $user, $billing_events_limit) {
            $billing_model = new Billing();
            $extra = $this->get_object($user->extra ?? null);

            return [
                'stripe_customer_id' => (string) ($extra->stripe_customer_id ?? ''),
                'summary' => $billing_model->get_user_billing_summary($user_id),
                'events' => $billing_model->get_user_billing_events($user_id, $billing_events_limit),
            ];
        }, [
            'stripe_customer_id' => '',
            'summary' => [],
            'events' => [],
        ], $section_errors, 'billing');

        $payload = [
            'user' => [
                'user_id' => $user_id,
                'name' => (string) ($user->name ?? ''),
                'email' => (string) ($user->email ?? ''),
                'status' => (int) ($user->status ?? 0),
                'status_label' => $this->get_status_label((int) ($user->status ?? 0)),
                'source' => (string) ($user->source ?? ''),
                'language' => (string) ($user->language ?? ''),
                'timezone' => (string) ($user->timezone ?? ''),
                'country' => (string) ($user->country ?? ''),
                'city_name' => (string) ($user->city_name ?? ''),
                'datetime' => $user->datetime ?? null,
                'last_activity' => $user->last_activity ?? null,
            ],
            'plan' => $plan,
            'meta' => $meta,
            'apps' => $apps,
            'ai' => $ai,
            'billing' => $billing,
            'restore_debug' => $this->resolve_collaborator_section(function() use ($user) {
                return $this->get_biolink_restore_debug_payload($user);
            }, [
                'link' => null,
                'current' => [
                    'blocks' => [],
                ],
                'backup' => [
                    'available' => false,
                ],
            ], $section_errors, 'restore_debug'),
            'urls' => [
                'admin_user_view' => url('admin/user-view/' . $user_id),
                'admin_user_update' => url('admin/user-update/' . $user_id),
                'los_detail' => url('admin/leader-operating-system-leader?user_id=' . $user_id . '&period=30d'),
            ],
        ];

        if(!empty($section_errors)) {
            $payload['section_errors'] = $section_errors;
        }

        return $payload;
    }

    public function index() {
        $this->ensure_access();

        $scope = $this->get_param_string('scope', 'overview');

        switch($scope) {
            case 'health':
                $this->respond_success($scope, $this->get_health_payload());
                break;

            case 'overview':
                $this->respond_success($scope, $this->get_overview_payload());
                break;

            case 'ai_feedback':
                $this->respond_success($scope, $this->get_ai_feedback_payload());
                break;

            case 'plans':
                $this->respond_success($scope, $this->get_plans_payload());
                break;

            case 'billing':
                $this->respond_success($scope, $this->get_billing_payload());
                break;

            case 'collaborators':
                $this->respond_success($scope, $this->get_collaborators_payload());
                break;

            case 'collaborator':
                $user = $this->get_collaborator_user();

                if(!$user) {
                    $this->respond_error('collaborator_not_found', 'Collaborator was not found. Provide user_id or email.', 404, [
                        'scope' => 'collaborator',
                    ]);
                }

                $this->respond_success($scope, $this->get_collaborator_payload($user), [
                    'query' => [
                        'user_id' => (int) ($user->user_id ?? 0),
                        'email' => (string) ($user->email ?? ''),
                    ],
                ]);
                break;

            default:
                $this->respond_error('invalid_scope', 'Readonly ops scope is invalid.', 422, [
                    'allowed_scopes' => ['health', 'overview', 'ai_feedback', 'plans', 'billing', 'collaborators', 'collaborator'],
                ]);
        }
    }

}
