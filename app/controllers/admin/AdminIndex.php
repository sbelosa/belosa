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
use Altum\Response;

defined('ALTUMCODE') || die();

class AdminIndex extends Controller {

    private function normalize_boolean($value): bool {
        if(is_bool($value)) {
            return $value;
        }

        if(is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if(is_string($value)) {
            $value = mb_strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'y', 'on'], true);
        }

        return !empty($value);
    }

    private function extract_fcc_completed_from_preferences($preferences): bool {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!is_object($preferences)) {
            return false;
        }

        $meta = $preferences->meta ?? null;

        if(is_string($meta)) {
            $decoded_meta = json_decode($meta);
            if(is_array($decoded_meta)) {
                $decoded_meta = (object) $decoded_meta;
            }
            if(is_object($decoded_meta)) {
                $meta = $decoded_meta;
            }
        }

        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        if(is_object($meta)) {
            if(
                $this->normalize_boolean($meta->fcc_core_completed ?? null)
                || $this->normalize_boolean($meta->fccCoreCompleted ?? null)
                || $this->normalize_boolean($meta->fcc_completed ?? null)
            ) {
                return true;
            }

            if(
                !empty($meta->fcc_core_completed_at ?? null)
                || !empty($meta->fccCoreCompletedAt ?? null)
                || !empty($meta->fcc_completed_at ?? null)
            ) {
                return true;
            }
        }

        if(
            $this->normalize_boolean($preferences->fcc_core_completed ?? null)
            || $this->normalize_boolean($preferences->fccCoreCompleted ?? null)
            || $this->normalize_boolean($preferences->fcc_completed ?? null)
        ) {
            return true;
        }

        if(
            !empty($preferences->fcc_core_completed_at ?? null)
            || !empty($preferences->fccCoreCompletedAt ?? null)
            || !empty($preferences->fcc_completed_at ?? null)
        ) {
            return true;
        }

        return false;
    }

    private function extract_forever_id_from_preferences($preferences): string {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!is_object($preferences)) {
            return '-';
        }

        $meta = $preferences->meta ?? null;

        if(is_string($meta)) {
            $decoded_meta = json_decode($meta);
            if(is_array($decoded_meta)) {
                $decoded_meta = (object) $decoded_meta;
            }
            if(is_object($decoded_meta)) {
                $meta = $decoded_meta;
            }
        }

        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        if(!is_object($meta)) {
            return '-';
        }

        $forever_id = $meta->foreverId ?? $meta->forever_id ?? $meta->foreverID ?? null;
        $forever_id = is_scalar($forever_id) ? trim((string) $forever_id) : '';

        return $forever_id !== '' ? $forever_id : '-';
    }

    private function is_valid_forever_sales_link_url($url): bool {
        $url = mb_strtolower(trim((string) $url));

        return strpos($url, 'https://thealoeveraco.shop/') === 0;
    }

    /* Custom code: FC-2026-03-04: admin dashboard phase 1 analytics helpers */
    private function get_period_start_datetime(int $days): string {
        $days = max(1, $days);

        return (new \DateTime())->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');
    }

    private function get_kpi_for_period(int $days): array {
        $period_start_datetime = $this->get_period_start_datetime($days);
        $now_datetime = date('Y-m-d H:i:s');
        $pro_plan_id = '5';

        $payments_count = (int) db()->where('status', 'paid')->where('datetime', $period_start_datetime, '>=')->getValue('payments', 'COUNT(`id`)');
        $net_earnings = (float) (db()->where('status', 'paid')->where('datetime', $period_start_datetime, '>=')->getValue('payments', 'SUM(`total_amount_default_currency`)') ?? 0);
        $new_users = (int) db()->where('datetime', $period_start_datetime, '>=')->getValue('users', 'COUNT(`user_id`)');
        $active_pro_packages = (int) db()
            ->where('status', 1)
            ->where('plan_id', $pro_plan_id)
            ->where('plan_expiration_date', $now_datetime, '>=')
            ->getValue('users', 'COUNT(`user_id`)');
        $cancelled_payments = (int) db()->where('status', 'cancelled')->where('datetime', $period_start_datetime, '>=')->getValue('payments', 'COUNT(`id`)');

        $churn_denominator = max(1, $payments_count + $cancelled_payments);
        $churn_rate = round(($cancelled_payments / $churn_denominator) * 100, 2);

        $arpu = $active_pro_packages > 0
            ? round($net_earnings / $active_pro_packages, 2)
            : 0;

        return [
            'payments_count' => $payments_count,
            'net_earnings' => $net_earnings,
            'new_users' => $new_users,
            'active_pro_packages' => $active_pro_packages,
            'churn_rate' => $churn_rate,
            'arpu' => $arpu,
            'cancelled_payments' => $cancelled_payments,
        ];
    }

    private function get_daily_series_map(string $query, string $value_key): array {
        $map = [];
        $result = database()->query($query);

        while($row = $result->fetch_object()) {
            $map[$row->formatted_date] = (float) ($row->{$value_key} ?? 0);
        }

        return $map;
    }

    private function decode_user_extra($extra): object {
        if(is_string($extra)) {
            $extra = json_decode($extra);
        }

        if(is_array($extra)) {
            $extra = (object) $extra;
        }

        if(!is_object($extra)) {
            $extra = (object) [];
        }

        return $extra;
    }

    private function get_extra_datetime(object $extra, string $key): ?string {
        if(empty($extra->{$key}) || !is_string($extra->{$key})) {
            return null;
        }

        try {
            return (new \DateTime($extra->{$key}))->format('Y-m-d H:i:s');
        } catch(\Exception $exception) {
            return null;
        }
    }

    private function is_datetime_within_last_days(?string $datetime, int $days): bool {
        if(!$datetime) {
            return false;
        }

        $start_datetime = $this->get_period_start_datetime($days);

        return $datetime >= $start_datetime && $datetime <= get_date();
    }

    /* Custom code: FC-2026-03-05: normalize and aggregate traffic sources */
    private function normalize_traffic_source_label(string $source): string {
        $source = mb_strtolower(trim($source));

        if($source === '' || in_array($source, ['(direct)', 'direct', 'none', '(none)'], true)) {
            return '(direct)';
        }

        if(strpos($source, 'utm:') === 0) {
            $source = substr($source, 4);
        }

        if(strpos($source, 'http://') === 0 || strpos($source, 'https://') === 0) {
            $parsed_host = parse_url($source, PHP_URL_HOST);
            if(is_string($parsed_host) && $parsed_host !== '') {
                $source = $parsed_host;
            }
        }

        $source = preg_replace('/:\\d+$/', '', $source);
        $source = preg_replace('/^www\./', '', $source);
        $source = preg_replace('/^m\./', '', $source);
        $source = preg_replace('/^l\./', '', $source);

        if(strpos($source, '/') !== false) {
            $source = explode('/', $source)[0];
        }

        /* Custom code: FC-2026-03-07: treat internal hosts as direct source */
        $site_host = parse_url(SITE_URL, PHP_URL_HOST);
        if(is_string($site_host) && $site_host !== '') {
            $site_host = mb_strtolower(trim($site_host));
            $site_host = preg_replace('/^www\./', '', $site_host);

            if($source === $site_host || str_ends_with($source, '.' . $site_host)) {
                return '(direct)';
            }
        }

        if($source === 'forevercard.club' || str_ends_with($source, '.forevercard.club')) {
            return '(direct)';
        }
        /* /Custom code: FC-2026-03-07 */

        if($source === '' || in_array($source, ['(direct)', 'direct', 'none', '(none)'], true)) {
            return '(direct)';
        }

        if($source === 'fb' || strpos($source, 'facebook') !== false) {
            return 'facebook';
        }

        if($source === 'ig' || strpos($source, 'instagram') !== false) {
            return 'instagram';
        }

        if(strpos($source, 'whatsapp') !== false || $source === 'wa') {
            return 'whatsapp';
        }

        if(strpos($source, 'tiktok') !== false) {
            return 'tiktok';
        }

        if(strpos($source, 'youtube') !== false || $source === 'youtu.be') {
            return 'youtube';
        }

        if(strpos($source, 'telegram') !== false) {
            return 'telegram';
        }

        if(strpos($source, 'viber') !== false) {
            return 'viber';
        }

        if(strpos($source, 'google') !== false || $source === 'gclid') {
            return 'google';
        }

        if(strpos($source, 'linkedin') !== false) {
            return 'linkedin';
        }

        return $source;
    }

    private function normalize_and_rank_traffic_sources($result, int $limit = 7): array {
        $aggregated_sources = [];

        while($source_row = $result->fetch_object()) {
            $raw_source = (string) ($source_row->source ?? '(direct)');
            $source = $this->normalize_traffic_source_label($raw_source);
            $total = (int) ($source_row->total ?? 0);

            if(!isset($aggregated_sources[$source])) {
                $aggregated_sources[$source] = 0;
            }

            $aggregated_sources[$source] += $total;
        }

        arsort($aggregated_sources);

        $normalized_sources = [];
        foreach(array_slice($aggregated_sources, 0, max(1, $limit), true) as $source => $total) {
            $normalized_sources[] = [
                'source' => (string) $source,
                'total' => (int) $total,
            ];
        }

        return $normalized_sources;
    }
    /* /Custom code: FC-2026-03-05 */
    /* /Custom code: FC-2026-03-04 */

    public function index() {

        if(settings()->internal_notifications->admins_is_enabled) {
            $internal_notifications = db()->where('for_who', 'admin')->orderBy('internal_notification_id', 'DESC')->get('internal_notifications', 5);

            $should_set_all_read = false;
            foreach($internal_notifications as $notification) {
                if(!$notification->is_read) $should_set_all_read = true;
            }

            if($should_set_all_read) {
                db()->where('for_who', 'admin')->update('internal_notifications', [
                    'is_read' => 1,
                    'read_datetime' => get_date(),
                ]);
            }
        }

        /* Requested plan details */
        $plans = (new \Altum\Models\Plan())->get_plans();

        /* Custom code: FC-2026-03-04: active trial users and cancelled billing markers */
        $trial_plan_ids = [];
        foreach($plans as $plan_id => $plan) {
            $trial_days = (int) ($plan->trial_days ?? 0);
            if($trial_days > 0) {
                $trial_plan_ids[] = (string) $plan_id;
            }
        }

        $active_trial_users = [];
        $active_trial_total = 0;
        $active_trial_cancelled_total = 0;

        if(!empty($trial_plan_ids)) {
            $active_trial_users_rows = db()
                ->where('type', 0)
                ->where('status', 1)
                ->where('plan_trial_done', 1)
                ->where('plan_id', $trial_plan_ids, 'IN')
                ->where('plan_expiration_date', get_date(), '>=')
                ->orderBy('plan_expiration_date', 'ASC')
                ->get('users', null, ['user_id', 'name', 'email', 'plan_id', 'plan_expiration_date', 'payment_subscription_id', 'extra']);

            foreach($active_trial_users_rows as $user) {
                $has_active_subscription = !empty($user->payment_subscription_id);
                $extra = $this->decode_user_extra($user->extra ?? null);
                $is_cancelled_billing_during_trial = !$has_active_subscription && (int) ($extra->billing_subscription_cancelled_during_trial ?? 0) === 1;
                /* Custom code: FC-2026-03-06: include all users with active trial package */
                $trial_started_at = $this->get_extra_datetime($extra, 'billing_trial_started_at');
                /* /Custom code: FC-2026-03-06 */

                $active_trial_users[] = (object) [
                    'user_id' => (int) $user->user_id,
                    'name' => (string) ($user->name ?? ''),
                    'email' => (string) ($user->email ?? ''),
                    'plan_id' => (string) ($user->plan_id ?? ''),
                    'plan_name' => $plans[$user->plan_id]->name ?? (string) $user->plan_id,
                    'plan_expiration_date' => (string) ($user->plan_expiration_date ?? ''),
                    'trial_started_at' => $trial_started_at,
                    'has_active_subscription' => $has_active_subscription,
                    'is_cancelled_billing_during_trial' => $is_cancelled_billing_during_trial,
                ];

                if($is_cancelled_billing_during_trial) {
                    $active_trial_cancelled_total++;
                }
            }

            $active_trial_total = count($active_trial_users);
        }

        $trial_filter = input_clean($_GET['trial_filter'] ?? 'all', 32);
        $allowed_trial_filters = ['all', 'cancelled', 'active', 'no_subscription'];
        if(!in_array($trial_filter, $allowed_trial_filters, true)) {
            $trial_filter = 'all';
        }

        $active_trial_users_filtered = array_values(array_filter($active_trial_users, function($user) use($trial_filter) {
            if($trial_filter == 'cancelled') {
                return (bool) $user->is_cancelled_billing_during_trial;
            }

            if($trial_filter == 'active') {
                return (bool) $user->has_active_subscription;
            }

            if($trial_filter == 'no_subscription') {
                return !(bool) $user->has_active_subscription && !(bool) $user->is_cancelled_billing_during_trial;
            }

            return true;
        }));

        $active_trial_filtered_total = count($active_trial_users_filtered);

        if(isset($_GET['trial_export']) && $_GET['trial_export'] == 'csv') {
            header('Content-Disposition: attachment; filename="trial-monitoring.csv";');
            header('Content-Type: application/csv; charset=UTF-8');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['user_id', 'name', 'email', 'plan', 'trial_until', 'billing_status', 'has_active_subscription', 'cancelled_during_trial']);

            foreach($active_trial_users_filtered as $trial_user) {
                $billing_status = $trial_user->is_cancelled_billing_during_trial
                    ? 'cancelled_during_trial'
                    : ($trial_user->has_active_subscription ? 'active' : 'no_subscription');

                fputcsv($output, [
                    $trial_user->user_id,
                    $trial_user->name,
                    $trial_user->email,
                    $trial_user->plan_name,
                    $trial_user->plan_expiration_date,
                    $billing_status,
                    $trial_user->has_active_subscription ? 1 : 0,
                    $trial_user->is_cancelled_billing_during_trial ? 1 : 0,
                ]);
            }

            fclose($output);
            die();
        }
        /* /Custom code: FC-2026-03-04 */

        $fcc_pending_education_users = [];
        $missing_sales_link_users = [];

        $users = db()->where('type', 0)->where('status', 1)->orderBy('user_id', 'DESC')->get('users', null, ['user_id', 'name', 'preferences']);

        $valid_forever_sales_link_user_ids = [];
        if(!empty($users)) {
            $user_ids = array_map(fn($user) => (int) $user->user_id, $users);

            if(!empty($user_ids)) {
                $discount_blocks = db()
                    ->where('user_id', $user_ids, 'IN')
                    ->where('type', 'link_discount')
                    ->where('is_enabled', 1)
                    ->get('biolinks_blocks', null, ['user_id', 'location_url']);

                foreach($discount_blocks as $discount_block) {
                    if($this->is_valid_forever_sales_link_url($discount_block->location_url ?? '')) {
                        $valid_forever_sales_link_user_ids[(int) $discount_block->user_id] = true;
                    }
                }
            }
        }

        foreach($users as $user) {
            if($this->extract_fcc_completed_from_preferences($user->preferences ?? null)) {
                $is_fcc_completed = true;
            } else {
                $is_fcc_completed = false;
            }

            $forever_id = $this->extract_forever_id_from_preferences($user->preferences ?? null);

            if(!$is_fcc_completed) {
                $fcc_pending_education_users[] = (object) [
                    'user_id' => (int) $user->user_id,
                    'name' => (string) ($user->name ?? ''),
                    'forever_id' => $forever_id,
                ];
            }

            if(empty($valid_forever_sales_link_user_ids[(int) $user->user_id])) {
                $missing_sales_link_users[] = (object) [
                    'user_id' => (int) $user->user_id,
                    'name' => (string) ($user->name ?? ''),
                    'forever_id' => $forever_id,
                ];
            }
        }

        $fcc_search = input_clean($_GET['fcc_search'] ?? '', 128);
        if($fcc_search !== '') {
            $fcc_search_normalized = mb_strtolower($fcc_search);

            $fcc_pending_education_users = array_values(array_filter($fcc_pending_education_users, function($user) use($fcc_search_normalized) {
                return mb_stripos(mb_strtolower((string) $user->name), $fcc_search_normalized) !== false
                    || mb_stripos(mb_strtolower((string) $user->forever_id), $fcc_search_normalized) !== false;
            }));
        }

        $fcc_pending_education_total = count($fcc_pending_education_users);

        $fcc_query_parameters = [];
        if($fcc_search !== '') {
            $fcc_query_parameters['fcc_search'] = $fcc_search;
        }

        $fcc_pending_education_paginator = new \Altum\Paginator(
            $fcc_pending_education_total,
            20,
            $_GET['fcc_page'] ?? 1,
            url('admin') . (!empty($fcc_query_parameters) ? '?' . http_build_query($fcc_query_parameters) . '&fcc_page=%d' : '?fcc_page=%d')
        );

        $fcc_pending_education_users = array_slice(
            $fcc_pending_education_users,
            $fcc_pending_education_paginator->getSqlOffset(),
            $fcc_pending_education_paginator->getItemsPerPage()
        );

        $sales_search = input_clean($_GET['sales_search'] ?? '', 128);
        if($sales_search !== '') {
            $sales_search_normalized = mb_strtolower($sales_search);

            $missing_sales_link_users = array_values(array_filter($missing_sales_link_users, function($user) use($sales_search_normalized) {
                return mb_stripos(mb_strtolower((string) $user->name), $sales_search_normalized) !== false
                    || mb_stripos(mb_strtolower((string) $user->forever_id), $sales_search_normalized) !== false;
            }));
        }

        $missing_sales_link_total = count($missing_sales_link_users);

        $sales_query_parameters = [];
        if($sales_search !== '') {
            $sales_query_parameters['sales_search'] = $sales_search;
        }

        $missing_sales_link_paginator = new \Altum\Paginator(
            $missing_sales_link_total,
            20,
            $_GET['sales_page'] ?? 1,
            url('admin') . (!empty($sales_query_parameters) ? '?' . http_build_query($sales_query_parameters) . '&sales_page=%d' : '?sales_page=%d')
        );

        $missing_sales_link_users = array_slice(
            $missing_sales_link_users,
            $missing_sales_link_paginator->getSqlOffset(),
            $missing_sales_link_paginator->getItemsPerPage()
        );

        /* Main View */
        $data = [
            'plans' => $plans,
            'internal_notifications' => $internal_notifications ?? [],
            'payment_processors' => require APP_PATH . 'includes/payment_processors.php',
            'fcc_pending_education_users' => $fcc_pending_education_users,
            'fcc_pending_education_total' => $fcc_pending_education_total,
            'fcc_pending_education_paginator' => $fcc_pending_education_paginator,
            'fcc_pending_education_search' => $fcc_search,
            'missing_sales_link_users' => $missing_sales_link_users,
            'missing_sales_link_total' => $missing_sales_link_total,
            'missing_sales_link_paginator' => $missing_sales_link_paginator,
            'missing_sales_link_search' => $sales_search,
            /* Custom code: FC-2026-03-04: trial monitoring data */
            'active_trial_users' => $active_trial_users,
            'active_trial_users_filtered' => $active_trial_users_filtered,
            'active_trial_total' => $active_trial_total,
            'active_trial_filtered_total' => $active_trial_filtered_total,
            'active_trial_cancelled_total' => $active_trial_cancelled_total,
            'trial_filter' => $trial_filter,
            /* /Custom code: FC-2026-03-04 */
        ];

        $view = new \Altum\View('admin/index/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function get_stats_ajax() {

        session_write_close();

        if($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw_404();
        }

        set_time_limit(0);

        /* Get stats */
        $biolink_links = db()->where('type', 'biolink')->getValue('links', 'count(`link_id`)');
        $shortened_links = db()->where('type', 'link')->getValue('links', 'count(`link_id`)');
        $track_links = db()->getValue('track_links', 'MAX(`id`)');
        $qr_codes = db()->getValue('qr_codes', 'count(`qr_code_id`)');
        $domains = db()->getValue('domains', 'count(`domain_id`)');
        $users = db()->getValue('users', 'count(`user_id`)');

        /* Custom code: FC-2026-03-05: unify money analytics to paid payments only */
        $payments = db()->where('status', 'paid')->getValue('payments', 'count(`id`)');
        $payments_total_amount = db()->where('status', 'paid')->getValue('payments', 'sum(`total_amount_default_currency`)');
        /* /Custom code: FC-2026-03-04 */

        /* Widgets stats: current month */
        $domains_current_month = db()->where('datetime', date('Y-m-01'), '>=')->getValue('domains', 'count(*)');
        $biolink_links_current_month = db()->where('type', 'biolink')->where('datetime', date('Y-m-01'), '>=')->getValue('links', 'count(*)');
        $shortened_links_current_month = db()->where('type', 'link')->where('datetime', date('Y-m-01'), '>=')->getValue('links', 'count(*)');
        $track_links_current_month = db()->where('datetime', date('Y-m-01'), '>=')->getValue('track_links', 'count(*)');
        $qr_codes_current_month = db()->where('datetime', date('Y-m-01'), '>=')->getValue('qr_codes', 'count(*)');
        $users_current_month = db()->where('datetime', date('Y-m-01'), '>=')->getValue('users', 'count(*)');
        /* Custom code: FC-2026-03-05: monthly money analytics based on paid payments only */
        $payments_current_month = db()->where('status', 'paid')->where('datetime', date('Y-m-01'), '>=')->getValue('payments', 'count(*)');
        $payments_amount_current_month = db()->where('status', 'paid')->where('datetime', date('Y-m-01'), '>=')->getValue('payments', 'sum(`total_amount_default_currency`)');
        /* /Custom code: FC-2026-03-04 */

        /* Get currently active users */
        $fifteen_minutes_ago_datetime = (new \DateTime())->modify('-15 minutes')->format('Y-m-d H:i:s');
        $active_users = db()->where('last_activity', $fifteen_minutes_ago_datetime, '>=')->getValue('users', 'COUNT(*)');

        /* Custom code: FC-2026-03-04: admin dashboard phase 1 analytics data */
        $kpi = [
            'today' => $this->get_kpi_for_period(1),
            '7d' => $this->get_kpi_for_period(7),
            '30d' => $this->get_kpi_for_period(30),
        ];

        $chart_days = 30;
        $chart_start_datetime = $this->get_period_start_datetime($chart_days);

        $revenue_series_map = $this->get_daily_series_map(
            "SELECT DATE(`datetime`) AS `formatted_date`, SUM(`total_amount_default_currency`) AS `metric` FROM `payments` WHERE `status` = 'paid' AND `datetime` >= '{$chart_start_datetime}' GROUP BY `formatted_date` ORDER BY `formatted_date` ASC",
            'metric'
        );

        $new_users_series_map = $this->get_daily_series_map(
            "SELECT DATE(`datetime`) AS `formatted_date`, COUNT(*) AS `metric` FROM `users` WHERE `datetime` >= '{$chart_start_datetime}' GROUP BY `formatted_date` ORDER BY `formatted_date` ASC",
            'metric'
        );

        $active_users_series_map = $this->get_daily_series_map(
            "SELECT DATE(`datetime`) AS `formatted_date`, COUNT(DISTINCT `user_id`) AS `metric` FROM `users_logs` WHERE `datetime` >= '{$chart_start_datetime}' GROUP BY `formatted_date` ORDER BY `formatted_date` ASC",
            'metric'
        );

        $new_subscriptions_series_map = [];
        $cancelled_subscriptions_series_map = [];

        $billing_markers_users = db()->where('extra', '%billing_trial_started_at%', 'LIKE')->get('users', null, ['user_id', 'extra']);
        foreach($billing_markers_users as $billing_user) {
            $extra = $this->decode_user_extra($billing_user->extra ?? null);

            $trial_started_at = $this->get_extra_datetime($extra, 'billing_trial_started_at');
            if($trial_started_at && $trial_started_at >= $chart_start_datetime) {
                $series_date = (new \DateTime($trial_started_at))->format('Y-m-d');
                $new_subscriptions_series_map[$series_date] = ($new_subscriptions_series_map[$series_date] ?? 0) + 1;
            }

            $cancelled_during_trial = (int) ($extra->billing_subscription_cancelled_during_trial ?? 0) === 1;
            $cancelled_at = $this->get_extra_datetime($extra, 'billing_subscription_cancelled_at');
            if($cancelled_during_trial && $cancelled_at && $cancelled_at >= $chart_start_datetime) {
                $series_date = (new \DateTime($cancelled_at))->format('Y-m-d');
                $cancelled_subscriptions_series_map[$series_date] = ($cancelled_subscriptions_series_map[$series_date] ?? 0) + 1;
            }
        }

        $chart_labels = [];
        $revenue_series = [];
        $new_users_series = [];
        $active_users_series = [];
        $new_subscriptions_series = [];
        $cancelled_subscriptions_series = [];

        for($day = $chart_days - 1; $day >= 0; $day--) {
            $date = (new \DateTime())->modify('-' . $day . ' days');
            $formatted_date = $date->format('Y-m-d');

            $chart_labels[] = $date->format('d.m');
            $revenue_series[] = round((float) ($revenue_series_map[$formatted_date] ?? 0), 2);
            $new_users_series[] = (int) ($new_users_series_map[$formatted_date] ?? 0);
            $active_users_series[] = (int) ($active_users_series_map[$formatted_date] ?? 0);
            $new_subscriptions_series[] = (int) ($new_subscriptions_series_map[$formatted_date] ?? 0);
            $cancelled_subscriptions_series[] = (int) ($cancelled_subscriptions_series_map[$formatted_date] ?? 0);
        }

        $recent_logins = [];
        /* Custom code: FC-2026-03-05: restrict realtime recent logins to last 24h */
        $recent_logins_start_datetime = $this->get_period_start_datetime(1);
        $recent_logins_result = database()->query("SELECT `users_logs`.`user_id`, `users_logs`.`datetime`, `users`.`name`, `users`.`plan_id`, `users`.`total_logins` FROM `users_logs` LEFT JOIN `users` ON `users_logs`.`user_id` = `users`.`user_id` WHERE `users_logs`.`type` = 'login.success' AND `users_logs`.`datetime` >= '{$recent_logins_start_datetime}' ORDER BY `users_logs`.`id` DESC LIMIT 5");
        /* /Custom code: FC-2026-03-05 */
        while($row = $recent_logins_result->fetch_object()) {
            $recent_logins[] = [
                'user_id' => (int) ($row->user_id ?? 0),
                'name' => (string) ($row->name ?? l('global.unknown')),
                'datetime' => (string) ($row->datetime ?? ''),
                'plan_id' => (string) ($row->plan_id ?? ''),
                'total_logins' => (int) ($row->total_logins ?? 0),
            ];
        }

        /* Custom code: FC-2026-03-08: provide online users list for realtime modal */
        $online_users_list = [];
        $online_users_result = db()->where('last_activity', $fifteen_minutes_ago_datetime, '>=')->orderBy('last_activity', 'DESC')->get('users', 100, ['user_id', 'name', 'last_activity']);
        foreach($online_users_result as $online_user) {
            $online_users_list[] = [
                'user_id' => (int) ($online_user->user_id ?? 0),
                'name' => (string) ($online_user->name ?? l('global.unknown')),
                'last_activity' => (string) ($online_user->last_activity ?? ''),
            ];
        }
        /* /Custom code: FC-2026-03-08 */

        $online_collaborators = [];
        $online_collaborators_result = db()->where('last_activity', $fifteen_minutes_ago_datetime, '>=')->where('type', 0)->orderBy('last_activity', 'DESC')->get('users', 5, ['user_id', 'name', 'plan_id', 'last_activity', 'total_logins']);
        foreach($online_collaborators_result as $collaborator) {
            $online_collaborators[] = [
                'user_id' => (int) ($collaborator->user_id ?? 0),
                'name' => (string) ($collaborator->name ?? l('global.unknown')),
                'plan_id' => (string) ($collaborator->plan_id ?? ''),
                'last_activity' => (string) ($collaborator->last_activity ?? ''),
                'total_logins' => (int) ($collaborator->total_logins ?? 0),
            ];
        }

        $failed_payments_7d = (int) db()->where('status', ['cancelled', 'pending'], 'IN')->where('datetime', $this->get_period_start_datetime(7), '>=')->getValue('payments', 'COUNT(`id`)');
        $churn_7d = (float) $kpi['7d']['churn_rate'];
        $churn_30d = (float) $kpi['30d']['churn_rate'];

        $alerts = [
            'failed_payments_7d' => $failed_payments_7d,
            'failed_payments_warning' => $failed_payments_7d >= 5,
            'churn_7d' => $churn_7d,
            'churn_30d' => $churn_30d,
            'churn_spike' => $churn_30d > 0 ? $churn_7d > ($churn_30d * 1.25) : $churn_7d > 5,
        ];

        /* Custom code: FC-2026-03-04: phase 2 sales and subscriptions metrics */
        $thirty_days_start_datetime = $this->get_period_start_datetime(30);
        $first_day_current_month = date('Y-m-01 00:00:00');

        $recurring_revenue_current_month = (float) (db()->where('status', 'paid')->where('type', 'recurring')->where('datetime', $first_day_current_month, '>=')->getValue('payments', 'SUM(`total_amount_default_currency`)') ?? 0);

        $active_paid_subscriptions = (int) db()
            ->where('status', 1)
            ->where('plan_id', '5')
            ->where('plan_expiration_date', get_date(), '>=')
            ->getValue('users', 'COUNT(`user_id`)');

        $new_subscriptions_30d = 0;
        $cancelled_subscriptions_30d = 0;

        foreach($billing_markers_users as $billing_user) {
            $extra = $this->decode_user_extra($billing_user->extra ?? null);

            $trial_started_at = $this->get_extra_datetime($extra, 'billing_trial_started_at');
            if($this->is_datetime_within_last_days($trial_started_at, 30)) {
                $new_subscriptions_30d++;
            }

            $cancelled_during_trial = (int) ($extra->billing_subscription_cancelled_during_trial ?? 0) === 1;
            $cancelled_at = $this->get_extra_datetime($extra, 'billing_subscription_cancelled_at');
            if($cancelled_during_trial && $this->is_datetime_within_last_days($cancelled_at, 30)) {
                $cancelled_subscriptions_30d++;
            }
        }

        $failed_payments_30d = (int) db()
            ->where('status', ['cancelled', 'pending'], 'IN')
            ->where('datetime', $thirty_days_start_datetime, '>=')
            ->getValue('payments', 'COUNT(*)');

        $plan_changes_30d = (int) database()->query("SELECT COUNT(*) AS total FROM (SELECT `user_id` FROM `payments` WHERE `status` = 'paid' AND `datetime` >= '{$thirty_days_start_datetime}' GROUP BY `user_id` HAVING COUNT(DISTINCT `plan_id`) > 1) AS `plan_changes`")->fetch_object()->total;

        $trial_plan_ids = [];
        $plans = (new \Altum\Models\Plan())->get_plans();
        foreach($plans as $plan_id => $plan) {
            if((int) ($plan->trial_days ?? 0) > 0) {
                $trial_plan_ids[] = (string) $plan_id;
            }
        }

        $at_risk_trial_users = [];
        if(!empty($trial_plan_ids)) {
            $risk_end_datetime = (new \DateTime())->modify('+7 days')->format('Y-m-d H:i:s');
            $at_risk_rows = db()
                ->where('type', 0)
                ->where('status', 1)
                ->where('plan_trial_done', 1)
                ->where('plan_id', $trial_plan_ids, 'IN')
                ->where('payment_subscription_id', '')
                ->where('plan_expiration_date', get_date(), '>=')
                ->where('plan_expiration_date', $risk_end_datetime, '<=')
                ->orderBy('plan_expiration_date', 'ASC')
                ->get('users', 20, ['user_id', 'name', 'email', 'plan_expiration_date', 'extra']);

            foreach($at_risk_rows as $user) {
                $extra = $this->decode_user_extra($user->extra ?? null);
                $trial_started_at = $this->get_extra_datetime($extra, 'billing_trial_started_at');

                if(!$trial_started_at) {
                    continue;
                }

                $at_risk_trial_users[] = [
                    'user_id' => (int) $user->user_id,
                    'name' => (string) ($user->name ?? l('global.unknown')),
                    'email' => (string) ($user->email ?? ''),
                    'plan_expiration_date' => (string) ($user->plan_expiration_date ?? ''),
                ];

                if(count($at_risk_trial_users) >= 7) {
                    break;
                }
            }
        }

        $sales_subscriptions = [
            'recurring_revenue_current_month' => round($recurring_revenue_current_month, 2),
            'active_paid_subscriptions' => $active_paid_subscriptions,
            'new_subscriptions_30d' => $new_subscriptions_30d,
            'cancelled_subscriptions_30d' => $cancelled_subscriptions_30d,
            'failed_payments_30d' => $failed_payments_30d,
            'plan_changes_30d' => $plan_changes_30d,
            'at_risk_trial_users' => $at_risk_trial_users,
        ];
        /* /Custom code: FC-2026-03-04 */

        /* Custom code: FC-2026-03-04: phase 5 action center backend metrics */
        $urgent_trial_actions = [];
        foreach(array_slice($at_risk_trial_users, 0, 5) as $risk_user) {
            $days_left = null;
            if(!empty($risk_user['plan_expiration_date'])) {
                $expiration_timestamp = strtotime($risk_user['plan_expiration_date']);
                if($expiration_timestamp) {
                    $days_left = max(0, (int) floor(($expiration_timestamp - time()) / 86400));
                }
            }

            $urgent_trial_actions[] = [
                'user_id' => (int) ($risk_user['user_id'] ?? 0),
                'name' => (string) ($risk_user['name'] ?? l('global.unknown')),
                'email' => (string) ($risk_user['email'] ?? ''),
                'plan_expiration_date' => (string) ($risk_user['plan_expiration_date'] ?? ''),
                'days_left' => $days_left,
            ];
        }

        $collaborator_opportunities = [];
        $collaborator_candidates = [];
        $collaborator_candidates_result = database()->query("SELECT `track_links`.`user_id`, `users`.`name`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `biolinks_blocks`.`type` IN ('link_forever_shop', 'link_discount') GROUP BY `track_links`.`user_id` ORDER BY `total` DESC LIMIT 20");
        while($collaborator_candidate_row = $collaborator_candidates_result->fetch_object()) {
            $collaborator_candidates[] = [
                'user_id' => (int) ($collaborator_candidate_row->user_id ?? 0),
                'name' => (string) ($collaborator_candidate_row->name ?? l('global.unknown')),
                'total' => (int) ($collaborator_candidate_row->total ?? 0),
            ];
        }

        if(!empty($collaborator_candidates)) {
            $candidate_user_ids = array_values(array_unique(array_map(fn($entry) => (int) ($entry['user_id'] ?? 0), $collaborator_candidates)));
            $candidate_user_ids = array_filter($candidate_user_ids);

            $candidate_users_map = [];
            if(!empty($candidate_user_ids)) {
                $candidate_users = db()->where('user_id', $candidate_user_ids, 'IN')->get('users', null, ['user_id', 'email', 'status', 'plan_id', 'payment_subscription_id', 'plan_expiration_date', 'extra']);
                foreach($candidate_users as $candidate_user) {
                    $candidate_users_map[(int) $candidate_user->user_id] = $candidate_user;
                }
            }

            foreach($collaborator_candidates as $candidate) {
                $candidate_user_id = (int) ($candidate['user_id'] ?? 0);
                $candidate_total_clicks = (int) ($candidate['total'] ?? 0);
                if(!$candidate_user_id || $candidate_total_clicks < 20) {
                    continue;
                }

                $candidate_user = $candidate_users_map[$candidate_user_id] ?? null;
                if(!$candidate_user) {
                    continue;
                }

                $candidate_extra = $this->decode_user_extra($candidate_user->extra ?? null);
                $cancelled_during_trial = (int) ($candidate_extra->billing_subscription_cancelled_during_trial ?? 0) === 1;
                $has_active_pro_package = (int) ($candidate_user->status ?? 0) === 1
                    && (string) ($candidate_user->plan_id ?? '') === '5'
                    && !empty($candidate_user->plan_expiration_date)
                    && $candidate_user->plan_expiration_date >= get_date();

                if($has_active_pro_package) {
                    continue;
                }

                $collaborator_opportunities[] = [
                    'user_id' => $candidate_user_id,
                    'name' => (string) ($candidate['name'] ?? l('global.unknown')),
                    'email' => (string) ($candidate_user->email ?? ''),
                    'forever_clicks_30d' => $candidate_total_clicks,
                    'billing_state' => $cancelled_during_trial ? 'cancelled_during_trial' : 'no_active_subscription',
                ];

                if(count($collaborator_opportunities) >= 7) {
                    break;
                }
            }
        }

        $three_days_end_datetime = (new \DateTime())->modify('+3 days')->format('Y-m-d H:i:s');
        $trials_ending_3d_without_subscription = 0;
        if(!empty($trial_plan_ids)) {
            $trials_ending_3d_without_subscription = (int) db()
                ->where('type', 0)
                ->where('status', 1)
                ->where('plan_trial_done', 1)
                ->where('plan_id', $trial_plan_ids, 'IN')
                ->where('payment_subscription_id', '')
                ->where('plan_expiration_date', get_date(), '>=')
                ->where('plan_expiration_date', $three_days_end_datetime, '<=')
                ->getValue('users', 'COUNT(`user_id`)');
        }

        $action_warnings = [
            [
                'key' => 'trials_expiring_without_subscription',
                'is_active' => $trials_ending_3d_without_subscription >= 3,
                'value' => $trials_ending_3d_without_subscription,
                'threshold' => 3,
            ],
            [
                'key' => 'high_collaborator_clicks_no_billing',
                'is_active' => count($collaborator_opportunities) >= 3,
                'value' => count($collaborator_opportunities),
                'threshold' => 3,
            ],
        ];

        $action_center = [
            'urgent_trial_actions' => $urgent_trial_actions,
            'collaborator_opportunities' => $collaborator_opportunities,
            'warnings' => $action_warnings,
        ];
        /* /Custom code: FC-2026-03-04 */

        /* Custom code: FC-2026-03-17: billing risk dashboard payload */
        $billing_risk = (new Billing())->get_dashboard_payload();
        /* /Custom code: FC-2026-03-17 */

        /* Custom code: FC-2026-03-04: phase 4 biolink geo and block analytics */
        $today_start_datetime = date('Y-m-d 00:00:00');
        $week_start_datetime = (new \DateTime())->modify('-6 days')->format('Y-m-d 00:00:00');

        /* Custom code: FC-2026-03-08: correct forever webshop vs registration block mappings */
        $forever_shop_block_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $forever_registration_block_types = ['link_forever_shop'];
        $forever_all_block_types = array_merge($forever_shop_block_types, $forever_registration_block_types);
        $forever_shop_block_types_sql = "'" . implode("', '", $forever_shop_block_types) . "'";
        $forever_registration_block_types_sql = "'" . implode("', '", $forever_registration_block_types) . "'";
        $forever_all_block_types_sql = "'" . implode("', '", $forever_all_block_types) . "'";
        $unique_track_links_condition = " AND `track_links`.`is_unique` = 1";
        /* /Custom code: FC-2026-03-08 */

        $biolink_analytics_clicks_today = (int) db()->where('datetime', $today_start_datetime, '>=')->getValue('track_links', 'COUNT(*)');
        $biolink_analytics_clicks_7d = (int) db()->where('datetime', $week_start_datetime, '>=')->getValue('track_links', 'COUNT(*)');
        $biolink_analytics_clicks_30d = (int) db()->where('datetime', $thirty_days_start_datetime, '>=')->getValue('track_links', 'COUNT(*)');

        $biolink_analytics_forever_shop_clicks_30d = 0;
        $biolink_analytics_forever_registration_clicks_30d = 0;

        $forever_clicks_30d_result = database()->query("SELECT `biolinks_blocks`.`type`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `biolinks_blocks`.`type` IN ({$forever_all_block_types_sql}) GROUP BY `biolinks_blocks`.`type`");
        while($row = $forever_clicks_30d_result->fetch_object()) {
            if(in_array((string) $row->type, $forever_shop_block_types, true)) {
                $biolink_analytics_forever_shop_clicks_30d += (int) $row->total;
            }

            if(in_array((string) $row->type, $forever_registration_block_types, true)) {
                $biolink_analytics_forever_registration_clicks_30d += (int) $row->total;
            }
        }

        $top_countries = [];
        $top_countries_result = database()->query("SELECT `track_links`.`country_code`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `track_links`.`country_code` IS NOT NULL AND `track_links`.`country_code` != '' AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `track_links`.`country_code` ORDER BY `total` DESC LIMIT 7");
        while($row = $top_countries_result->fetch_object()) {
            $top_countries[] = [
                'country_code' => (string) ($row->country_code ?? ''),
                'total' => (int) ($row->total ?? 0),
            ];
        }

        $leaderboard = [];
        $leaderboard_result = database()->query("SELECT `track_links`.`user_id`, `users`.`name`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `track_links`.`user_id` ORDER BY `total` DESC LIMIT 15");
        while($row = $leaderboard_result->fetch_object()) {
            $leaderboard[] = [
                'user_id' => (int) ($row->user_id ?? 0),
                'name' => (string) ($row->name ?? l('global.unknown')),
                'total' => (int) ($row->total ?? 0),
            ];
        }

        $period_start_map = [
            'today' => $today_start_datetime,
            '7d' => $week_start_datetime,
            '30d' => $thirty_days_start_datetime,
        ];

        $periods = [];
        foreach($period_start_map as $period_key => $period_start_datetime) {
            $period_top_countries = [];
            $period_top_countries_result = database()->query("SELECT `track_links`.`country_code`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$period_start_datetime}' {$unique_track_links_condition} AND `track_links`.`country_code` IS NOT NULL AND `track_links`.`country_code` != '' AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `track_links`.`country_code` ORDER BY `total` DESC LIMIT 7");
            while($period_country_row = $period_top_countries_result->fetch_object()) {
                $period_top_countries[] = [
                    'country_code' => (string) ($period_country_row->country_code ?? ''),
                    'total' => (int) ($period_country_row->total ?? 0),
                ];
            }

            $period_forever_shop_clicks = 0;
            $period_forever_registration_clicks = 0;
            $period_forever_clicks_result = database()->query("SELECT `biolinks_blocks`.`type`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$period_start_datetime}' {$unique_track_links_condition} AND `biolinks_blocks`.`type` IN ({$forever_all_block_types_sql}) GROUP BY `biolinks_blocks`.`type`");
            while($period_forever_click_row = $period_forever_clicks_result->fetch_object()) {
                if(in_array((string) $period_forever_click_row->type, $forever_shop_block_types, true)) {
                    $period_forever_shop_clicks += (int) $period_forever_click_row->total;
                }

                if(in_array((string) $period_forever_click_row->type, $forever_registration_block_types, true)) {
                    $period_forever_registration_clicks += (int) $period_forever_click_row->total;
                }
            }

            $period_leaderboard = [];
            $period_leaderboard_result = database()->query("SELECT `track_links`.`user_id`, `users`.`name`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` WHERE `track_links`.`datetime` >= '{$period_start_datetime}' {$unique_track_links_condition} AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `track_links`.`user_id` ORDER BY `total` DESC LIMIT 15");
            while($period_leaderboard_row = $period_leaderboard_result->fetch_object()) {
                $period_leaderboard[] = [
                    'user_id' => (int) ($period_leaderboard_row->user_id ?? 0),
                    'name' => (string) ($period_leaderboard_row->name ?? l('global.unknown')),
                    'total' => (int) ($period_leaderboard_row->total ?? 0),
                ];
            }

            $period_source_label_sql = "CASE WHEN `track_links`.`utm_source` IS NOT NULL AND `track_links`.`utm_source` != '' THEN CONCAT('utm:', `track_links`.`utm_source`) WHEN `track_links`.`referrer_host` IS NOT NULL AND `track_links`.`referrer_host` != '' THEN `track_links`.`referrer_host` ELSE '(direct)' END";

            /* Custom code: FC-2026-03-05: normalize source channels for admin period analytics */
            $period_top_shop_sources_result = database()->query("SELECT {$period_source_label_sql} AS `source`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$period_start_datetime}' {$unique_track_links_condition} AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `source` ORDER BY `total` DESC LIMIT 100");
            $period_top_shop_sources = $this->normalize_and_rank_traffic_sources($period_top_shop_sources_result, 7);

            $period_top_registration_sources_result = database()->query("SELECT {$period_source_label_sql} AS `source`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$period_start_datetime}' {$unique_track_links_condition} AND `biolinks_blocks`.`type` IN ({$forever_registration_block_types_sql}) GROUP BY `source` ORDER BY `total` DESC LIMIT 100");
            $period_top_registration_sources = $this->normalize_and_rank_traffic_sources($period_top_registration_sources_result, 7);
            /* /Custom code: FC-2026-03-05 */

            $period_user_details = [];
            if(!empty($period_leaderboard)) {
                foreach(array_map(fn($entry) => (int) $entry['user_id'], $period_leaderboard) as $leaderboard_user_id) {
                    $period_clicks_total = (int) db()->where('user_id', $leaderboard_user_id)->where('datetime', $period_start_datetime, '>=')->getValue('track_links', 'COUNT(*)');

                    $period_user_forever_shop_clicks = 0;
                    $period_user_forever_registration_clicks = 0;
                    $period_user_forever_clicks_result = database()->query("SELECT `biolinks_blocks`.`type`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$period_start_datetime}' {$unique_track_links_condition} AND `track_links`.`user_id` = {$leaderboard_user_id} AND `biolinks_blocks`.`type` IN ({$forever_all_block_types_sql}) GROUP BY `biolinks_blocks`.`type`");
                    while($period_user_forever_click_row = $period_user_forever_clicks_result->fetch_object()) {
                        if(in_array((string) $period_user_forever_click_row->type, $forever_shop_block_types, true)) {
                            $period_user_forever_shop_clicks += (int) $period_user_forever_click_row->total;
                        }

                        if(in_array((string) $period_user_forever_click_row->type, $forever_registration_block_types, true)) {
                            $period_user_forever_registration_clicks += (int) $period_user_forever_click_row->total;
                        }
                    }

                    $period_user_top_countries = [];
                    $period_user_top_countries_result = database()->query("SELECT `track_links`.`country_code`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$period_start_datetime}' {$unique_track_links_condition} AND `track_links`.`user_id` = {$leaderboard_user_id} AND `track_links`.`country_code` IS NOT NULL AND `track_links`.`country_code` != '' AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `track_links`.`country_code` ORDER BY `total` DESC LIMIT 5");
                    while($period_user_country_row = $period_user_top_countries_result->fetch_object()) {
                        $period_user_top_countries[] = [
                            'country_code' => (string) ($period_user_country_row->country_code ?? ''),
                            'total' => (int) ($period_user_country_row->total ?? 0),
                        ];
                    }

                    $period_user_details[(string) $leaderboard_user_id] = [
                        'clicks_total' => $period_clicks_total,
                        'forever_shop_clicks' => $period_user_forever_shop_clicks,
                        'forever_registration_clicks' => $period_user_forever_registration_clicks,
                        'top_countries' => $period_user_top_countries,
                    ];
                }
            }

            $periods[$period_key] = [
                'forever_shop_clicks' => $period_forever_shop_clicks,
                'forever_registration_clicks' => $period_forever_registration_clicks,
                'top_countries' => $period_top_countries,
                'top_shop_sources' => $period_top_shop_sources,
                'top_registration_sources' => $period_top_registration_sources,
                'leaderboard' => $period_leaderboard,
                'user_details' => $period_user_details,
            ];
        }

        $user_details = [];
        if(!empty($leaderboard)) {
            $leaderboard_user_ids = array_map(fn($entry) => (int) $entry['user_id'], $leaderboard);

            foreach($leaderboard_user_ids as $leaderboard_user_id) {
                $clicks_today = (int) db()->where('user_id', $leaderboard_user_id)->where('datetime', $today_start_datetime, '>=')->getValue('track_links', 'COUNT(*)');
                $clicks_7d = (int) db()->where('user_id', $leaderboard_user_id)->where('datetime', $week_start_datetime, '>=')->getValue('track_links', 'COUNT(*)');
                $clicks_30d = (int) db()->where('user_id', $leaderboard_user_id)->where('datetime', $thirty_days_start_datetime, '>=')->getValue('track_links', 'COUNT(*)');

                $forever_shop_clicks_30d = 0;
                $forever_registration_clicks_30d = 0;
                $user_forever_clicks_result = database()->query("SELECT `biolinks_blocks`.`type`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `track_links`.`user_id` = {$leaderboard_user_id} AND `biolinks_blocks`.`type` IN ({$forever_all_block_types_sql}) GROUP BY `biolinks_blocks`.`type`");
                while($user_forever_click_row = $user_forever_clicks_result->fetch_object()) {
                    if(in_array((string) $user_forever_click_row->type, $forever_shop_block_types, true)) {
                        $forever_shop_clicks_30d += (int) $user_forever_click_row->total;
                    }

                    if(in_array((string) $user_forever_click_row->type, $forever_registration_block_types, true)) {
                        $forever_registration_clicks_30d += (int) $user_forever_click_row->total;
                    }
                }

                $user_top_countries = [];
                $user_top_countries_result = database()->query("SELECT `track_links`.`country_code`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `track_links`.`user_id` = {$leaderboard_user_id} AND `track_links`.`country_code` IS NOT NULL AND `track_links`.`country_code` != '' AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `track_links`.`country_code` ORDER BY `total` DESC LIMIT 5");
                while($user_country_row = $user_top_countries_result->fetch_object()) {
                    $user_top_countries[] = [
                        'country_code' => (string) ($user_country_row->country_code ?? ''),
                        'total' => (int) ($user_country_row->total ?? 0),
                    ];
                }

                $user_details[(string) $leaderboard_user_id] = [
                    'clicks_today' => $clicks_today,
                    'clicks_7d' => $clicks_7d,
                    'clicks_30d' => $clicks_30d,
                    'forever_shop_clicks_30d' => $forever_shop_clicks_30d,
                    'forever_registration_clicks_30d' => $forever_registration_clicks_30d,
                    'top_countries' => $user_top_countries,
                ];
            }
        }

        $biolink_analytics = [
            'clicks_today' => $biolink_analytics_clicks_today,
            'clicks_7d' => $biolink_analytics_clicks_7d,
            'clicks_30d' => $biolink_analytics_clicks_30d,
            'forever_shop_clicks_30d' => $biolink_analytics_forever_shop_clicks_30d,
            'forever_registration_clicks_30d' => $biolink_analytics_forever_registration_clicks_30d,
            'top_countries' => $top_countries,
            'leaderboard' => $leaderboard,
            'user_details' => $user_details,
            'periods' => $periods,
        ];
        /* /Custom code: FC-2026-03-04 */

        $admin_analytics = [
            'kpi' => $kpi,
            'charts' => [
                'labels' => $chart_labels,
                'revenue_series' => $revenue_series,
                'new_users_series' => $new_users_series,
                'active_users_series' => $active_users_series,
                'new_subscriptions_series' => $new_subscriptions_series,
                'cancelled_subscriptions_series' => $cancelled_subscriptions_series,
            ],
            'realtime' => [
                'online_users' => (int) $active_users,
                'active_sessions' => (int) $active_users,
                'recent_logins' => $recent_logins,
                'online_users_list' => $online_users_list,
                'online_collaborators' => $online_collaborators,
            ],
            'alerts' => $alerts,
            'sales_subscriptions' => $sales_subscriptions,
            'biolink_analytics' => $biolink_analytics,
            'action_center' => $action_center,
            /* Custom code: FC-2026-03-17: include billing risk analytics payload */
            'billing_risk' => $billing_risk,
            /* /Custom code: FC-2026-03-17 */
        ];
        /* /Custom code: FC-2026-03-04 */

        /* Prepare the data */
        $data = [
            'biolink_links' => $biolink_links,
            'shortened_links' => $shortened_links,
            'track_links' => $track_links,
            'qr_codes' => $qr_codes,
            'domains' => $domains,
            'payments_total_amount' => $payments_total_amount,
            'users' => $users,
            'payments' => $payments,

            'domains_current_month' => $domains_current_month,
            'biolink_links_current_month' => $biolink_links_current_month,
            'shortened_links_current_month' => $shortened_links_current_month,
            'track_links_current_month' => $track_links_current_month,
            'qr_codes_current_month' => $qr_codes_current_month,
            'users_current_month' => $users_current_month,
            'payments_current_month' => $payments_current_month,
            'payments_amount_current_month' => $payments_amount_current_month,

            'active_users' => $active_users,

            /* Custom code: FC-2026-03-04: include admin analytics phase 1 payload */
            'admin_analytics' => $admin_analytics,
            /* /Custom code: FC-2026-03-04 */
        ];

        /* Set a nice success message */
        Response::json('', 'success', $data);

    }

}
