<?php
/* Custom code: FC-2026-03-31: Leader Operating System overview controller */

namespace Altum\Controllers;

use Altum\Title;

defined('ALTUMCODE') || die();

class AdminLeaderOperatingSystem extends Controller {

    private function get_preferences_object($preferences): \stdClass {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!$preferences instanceof \stdClass) {
            $preferences = (object) $preferences;
        }

        return $preferences;
    }

    private function get_period_days(string $period_key): int {
        return [
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
        ][$period_key] ?? 30;
    }

    private function get_period_start_datetime(int $days): string {
        $days = max(1, $days);

        return (new \DateTime())->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');
    }

    private function get_growth_metrics(int $current, int $previous): array {
        $difference = $current - $previous;
        $growth_percent = null;

        if($previous > 0) {
            $growth_percent = round(($difference / $previous) * 100, 1);
        } elseif($current === 0) {
            $growth_percent = 0.0;
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => $difference,
            'growth_percent' => $growth_percent,
        ];
    }

    private function get_biolink_sets(): array {
        $forever_shop_block_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $forever_registration_block_types = ['link_forever_shop'];

        return [
            'forever_shop_block_types_sql' => "'" . implode("', '", $forever_shop_block_types) . "'",
            'forever_registration_block_types_sql' => "'" . implode("', '", $forever_registration_block_types) . "'",
        ];
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

    private function normalize_source_value(string $source): string {
        $source = mb_strtolower(trim($source));

        if($source === '') {
            return '';
        }

        if(strpos($source, '://') !== false) {
            $parsed_host = parse_url($source, PHP_URL_HOST);
            if(is_string($parsed_host) && $parsed_host !== '') {
                $source = $parsed_host;
            }
        }

        if(strpos($source, '/') !== false) {
            $source = explode('/', $source)[0];
        }

        return preg_replace('/^www\./', '', $source) ?? $source;
    }

    private function is_internal_source(string $source): bool {
        $source = $this->normalize_source_value($source);

        if($source === '') {
            return false;
        }

        $site_host = parse_url(SITE_URL, PHP_URL_HOST);
        $site_host = is_string($site_host) ? $this->normalize_source_value($site_host) : '';

        if($site_host !== '' && ($source === $site_host || str_ends_with($source, '.' . $site_host))) {
            return true;
        }

        return $source === 'forevercard.club' || str_ends_with($source, '.forevercard.club');
    }

    private function normalize_source_label(string $source): string {
        $source = $this->normalize_source_value($source);

        if($source === '' || in_array($source, ['(direct)', 'direct', 'none', '(none)'], true) || $this->is_internal_source($source)) {
            return l('admin_index.biolink_qualified_watch.source.direct');
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

    private function get_source_label(array $click): string {
        $utm_source = trim((string) ($click['utm_source'] ?? ''));
        $referrer_host = trim((string) ($click['referrer_host'] ?? ''));

        foreach([$utm_source, $referrer_host] as $candidate_source) {
            $normalized_source = $this->normalize_source_value($candidate_source);

            if($normalized_source === '' || $this->is_internal_source($normalized_source)) {
                continue;
            }

            return $this->normalize_source_label($normalized_source);
        }

        return l('admin_index.biolink_qualified_watch.source.direct');
    }

    private function enrich_rows_with_context(array $rows, string $period_start_datetime, array $biolink_sets): array {
        if(empty($rows)) {
            return $rows;
        }

        $user_ids_sql = implode(',', array_map(static fn($row) => (int) $row['user_id'], $rows));
        $top_context = [];
        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);
        $context_result = database()->query("SELECT
            `track_links`.`user_id`,
            `track_links`.`country_code`,
            `track_links`.`utm_source`,
            `track_links`.`utm_medium`,
            `track_links`.`utm_campaign`,
            `track_links`.`referrer_host`
        FROM `track_links`
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
          AND `track_links`.`is_unique` = 1
          AND `track_links`.`user_id` IN ({$user_ids_sql})
                    AND {$outbound_condition}");

        while($context_row = $context_result->fetch_assoc()) {
            $user_id = (int) ($context_row['user_id'] ?? 0);
            if(!isset($top_context[$user_id])) {
                $top_context[$user_id] = [
                    'countries' => [],
                    'sources' => [],
                ];
            }

            $country_code = strtoupper(trim((string) ($context_row['country_code'] ?? '')));
            if($country_code !== '') {
                $top_context[$user_id]['countries'][$country_code] = ($top_context[$user_id]['countries'][$country_code] ?? 0) + 1;
            }

            $source_label = $this->get_source_label($context_row);
            $top_context[$user_id]['sources'][$source_label] = ($top_context[$user_id]['sources'][$source_label] ?? 0) + 1;
        }

        foreach($rows as &$row) {
            $context = $top_context[(int) $row['user_id']] ?? ['countries' => [], 'sources' => []];
            arsort($context['countries']);
            arsort($context['sources']);
            $row['strongest_country'] = !empty($context['countries']) ? (string) array_key_first($context['countries']) : '-';
            $row['strongest_country_count'] = !empty($context['countries']) ? (int) reset($context['countries']) : 0;
            $row['top_source_label'] = !empty($context['sources']) ? (string) array_key_first($context['sources']) : l('admin_index.biolink_qualified_watch.source.direct');
            $row['top_source_count'] = !empty($context['sources']) ? (int) reset($context['sources']) : 0;
        }
        unset($row);

        return $rows;
    }

    private function clamp_score(float $value): int {
        return (int) max(0, min(100, round($value)));
    }

    private function get_scores(array $row): array {
        $total_clicks = (int) ($row['clicks_total_period'] ?? 0);
        $shop_clicks = (int) ($row['forever_shop_clicks_period'] ?? 0);
        $registration_clicks = (int) ($row['forever_registration_clicks_period'] ?? 0);
        $growth = $row['growth_percent'];
        $shop_share = $total_clicks > 0 ? (($shop_clicks / $total_clicks) * 100) : 0;
        $registration_rate = $shop_clicks > 0 ? (($registration_clicks / $shop_clicks) * 100) : 0;

        $performance_score = $this->clamp_score(min(40, $shop_clicks * 2.3) + min(25, $registration_clicks * 6) + min(35, $total_clicks * 0.55));
        $momentum_score = $this->clamp_score($growth === null ? ($shop_clicks > 0 ? 58 : 0) : 50 + ($growth * 1.1));
        $conversion_score = $this->clamp_score(($shop_share * 0.55) + ($registration_rate * 0.9));

        $risk_score = 0;
        if($growth !== null && $growth <= -20) {
            $risk_score += 35;
        }
        if((int) ($row['previous_forever_shop_clicks_period'] ?? 0) > 0 && $shop_clicks === 0) {
            $risk_score += 35;
        }
        if($total_clicks === 0 && (int) ($row['forever_shop_clicks_90d'] ?? 0) > 0) {
            $risk_score += 20;
        }
        $risk_score = $this->clamp_score($risk_score);

        $opportunity_score = 0;
        if($total_clicks >= 20 && $shop_share < 25) {
            $opportunity_score += 35;
        }
        if($shop_clicks >= 10 && $registration_clicks === 0) {
            $opportunity_score += 20;
        }
        if($growth !== null && $growth > 0 && $registration_rate < 10) {
            $opportunity_score += 15;
        }
        $opportunity_score = $this->clamp_score($opportunity_score);

        $leader_os_score = $this->clamp_score(
            ($performance_score * 0.35)
            + ($momentum_score * 0.2)
            + ($conversion_score * 0.2)
            + ((100 - $risk_score) * 0.1)
            + ($opportunity_score * 0.15)
        );

        return [
            'performance_score' => $performance_score,
            'momentum_score' => $momentum_score,
            'conversion_score' => $conversion_score,
            'risk_score' => $risk_score,
            'opportunity_score' => $opportunity_score,
            'leader_os_score' => $leader_os_score,
            'shop_share_percent' => round($shop_share, 1),
            'registration_rate_percent' => round($registration_rate, 1),
        ];
    }

    private function get_status_payload(array $row): array {
        $qualified = (int) ($row['forever_shop_clicks_90d'] ?? 0) >= 15;
        $growth = $row['growth_percent'] ?? null;
        $current_shop_clicks = (int) ($row['forever_shop_clicks_period'] ?? 0);
        $previous_shop_clicks = (int) ($row['previous_forever_shop_clicks_period'] ?? 0);
        $total_clicks = (int) ($row['clicks_total_period'] ?? 0);
        $opportunity_score = (int) ($row['opportunity_score'] ?? 0);
        $risk_score = (int) ($row['risk_score'] ?? 0);

        $status_key = 'stable';
        $status_label = l('admin_leader_operating_system.status.stable');
        $status_class = 'secondary';

        if($total_clicks === 0 && (int) ($row['forever_shop_clicks_90d'] ?? 0) === 0) {
            $status_key = 'inactive';
            $status_label = l('admin_leader_operating_system.status.inactive');
            $status_class = 'dark';
        } elseif($risk_score >= 55 || ($previous_shop_clicks > 0 && $current_shop_clicks === 0) || ($growth !== null && $growth <= -20)) {
            $status_key = 'risk';
            $status_label = l('admin_leader_operating_system.status.risk');
            $status_class = 'warning';
        } elseif($opportunity_score >= 60 && $total_clicks >= 20) {
            $status_key = 'high_potential';
            $status_label = l('admin_leader_operating_system.status.high_potential');
            $status_class = 'info';
        } elseif(($growth !== null && $growth >= 20) || $current_shop_clicks >= 12) {
            $status_key = 'rising';
            $status_label = l('admin_leader_operating_system.status.rising');
            $status_class = 'success';
        }

        return [
            'qualified' => $qualified,
            'status_key' => $status_key,
            'status_label' => $status_label,
            'status_class' => $status_class,
        ];
    }

    private function get_ai_plan_overview_context($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $profile = $preferences->leader_ai_profile ?? null;
        $checkins = $preferences->leader_ai_weekly_checkins ?? [];
        $outcomes = $preferences->leader_ai_weekly_outcomes ?? [];
        $plans = $preferences->leader_ai_weekly_plans ?? [];
        $mentor_actions = $preferences->leader_ai_admin_coaching ?? null;
        $mentor_history = $this->get_ai_plan_mentor_history($preferences);

        if(is_object($profile)) {
            $profile = (array) $profile;
        }

        if(is_object($checkins)) {
            $checkins = (array) $checkins;
        }

        if(is_object($outcomes)) {
            $outcomes = (array) $outcomes;
        }

        if(is_object($plans)) {
            $plans = (array) $plans;
        }

        if(is_object($mentor_actions)) {
            $mentor_actions = (array) $mentor_actions;
        }

        $latest_checkin = [];
        if(is_array($checkins) && !empty($checkins[0])) {
            $latest_checkin = is_object($checkins[0]) ? (array) $checkins[0] : (array) $checkins[0];
        }

        $latest_outcome = [];
        if(is_array($outcomes) && !empty($outcomes[0])) {
            $latest_outcome = is_object($outcomes[0]) ? (array) $outcomes[0] : (array) $outcomes[0];
        }

        $latest_plan = [];
        if(is_array($plans) && !empty($plans[0])) {
            $latest_plan = is_object($plans[0]) ? (array) $plans[0] : (array) $plans[0];
        }

        $allowed_statuses = ['pending_contact', 'in_progress', 'monitoring', 'resolved'];
        $mentor_status = trim((string) ($mentor_actions['status'] ?? 'pending_contact'));

        if(!in_array($mentor_status, $allowed_statuses, true)) {
            $mentor_status = 'pending_contact';
        }

        $days_since_last_checkin = null;
        if(!empty($latest_checkin['submitted_at'])) {
            try {
                $latest_date = new \DateTimeImmutable((string) $latest_checkin['submitted_at']);
                $days_since_last_checkin = (int) $latest_date->diff(new \DateTimeImmutable())->format('%a');
            } catch(\Throwable $exception) {
                $days_since_last_checkin = null;
            }
        }

        $days_since_last_contact = null;
        if(!empty($mentor_actions['last_contacted_at'])) {
            try {
                $last_contact_date = new \DateTimeImmutable((string) $mentor_actions['last_contacted_at']);
                $days_since_last_contact = (int) $last_contact_date->diff(new \DateTimeImmutable())->format('%a');
            } catch(\Throwable $exception) {
                $days_since_last_contact = null;
            }
        }

        $latest_mentor_event = $mentor_history[0] ?? [];

        $overview_context = [
            'has_profile' => !empty($profile['primary_goal']),
            'has_checkin' => !empty($latest_checkin),
            'has_plan' => !empty($latest_plan),
            'latest_checkin_at' => $latest_checkin['submitted_at'] ?? null,
            'days_since_last_checkin' => $days_since_last_checkin,
            'latest_outcome_completion_level' => (string) ($latest_outcome['completion_level'] ?? ''),
            'needs_follow_up' => (bool) ($mentor_actions['needs_follow_up'] ?? false),
            'mentored_this_week' => (bool) ($mentor_actions['mentored_this_week'] ?? false),
            'mentor_status' => $mentor_status,
            'has_ai_guidance' => trim((string) ($mentor_actions['ai_guidance'] ?? '')) !== '',
            'mentor_next_action' => trim((string) ($mentor_actions['next_action'] ?? '')),
            'last_contacted_at' => $mentor_actions['last_contacted_at'] ?? null,
            'days_since_last_contact' => $days_since_last_contact,
            'mentor_history_total' => count($mentor_history),
            'latest_mentor_event_summary' => trim((string) ($latest_mentor_event['summary'] ?? '')),
            'latest_mentor_event_at' => $latest_mentor_event['created_at'] ?? null,
            'latest_mentor_event_admin' => trim((string) ($latest_mentor_event['admin_name'] ?? '')),
        ];

        /* Custom code: FC-2026-03-31: LOS overview AI usage payload */
        return array_merge($overview_context, $this->get_ai_usage_payload($overview_context));
        /* /Custom code: FC-2026-03-31 */
    }

    private function get_ai_plan_mentor_history($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $history = $preferences->leader_ai_admin_history ?? [];

        if(is_object($history)) {
            $history = (array) $history;
        }

        if(!is_array($history)) {
            return [];
        }

        $normalized = [];

        foreach($history as $history_item) {
            if(is_object($history_item)) {
                $history_item = (array) $history_item;
            }

            if(!is_array($history_item)) {
                continue;
            }

            $summary = trim((string) ($history_item['summary'] ?? ''));

            if($summary === '') {
                continue;
            }

            $normalized[] = [
                'summary' => $summary,
                'created_at' => $history_item['created_at'] ?? null,
                'admin_name' => trim((string) ($history_item['admin_name'] ?? '')),
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $normalized;
    }

    /* Custom code: FC-2026-03-31: LOS overview AI usage payload */
    private function get_ai_usage_payload(array $overview_context): array {
        $stage_key = 'inactive';
        $stage_class = 'status-dark';

        if(!empty($overview_context['has_plan'])) {
            $stage_key = 'active';
            $stage_class = 'status-success';
        } elseif(!empty($overview_context['has_checkin'])) {
            $stage_key = 'questionnaire';
            $stage_class = 'status-info';
        } elseif(!empty($overview_context['has_profile'])) {
            $stage_key = 'started';
            $stage_class = 'status-warning';
        }

        $badges = [];

        if(!empty($overview_context['has_profile'])) {
            $badges[] = [
                'label' => l('admin_leader_operating_system.ai_usage.badge_profile'),
                'class' => 'status-warning',
            ];
        }

        if(!empty($overview_context['has_checkin'])) {
            $badges[] = [
                'label' => l('admin_leader_operating_system.ai_usage.badge_checkin'),
                'class' => 'status-info',
            ];
        }

        if(!empty($overview_context['has_plan'])) {
            $badges[] = [
                'label' => l('admin_leader_operating_system.ai_usage.badge_plan'),
                'class' => 'status-success',
            ];
        }

        if(!empty($overview_context['has_ai_guidance'])) {
            $badges[] = [
                'label' => l('admin_leader_operating_system.ai_usage.badge_guidance'),
                'class' => 'status-info',
            ];
        }

        return [
            'ai_usage_stage_key' => $stage_key,
            'ai_usage_stage_label' => l('admin_leader_operating_system.ai_usage.' . $stage_key),
            'ai_usage_stage_class' => $stage_class,
            'ai_usage_badges' => $badges,
        ];
    }
    /* /Custom code: FC-2026-03-31 */

    private function get_queue_priority_payload(array $candidate): array {
        $score = 0;
        $reason = l('admin_leader_operating_system.queue_reason_monitor');
        $anomaly_score = (int) ($candidate['anomaly_score'] ?? 0);

        if(!empty($candidate['needs_follow_up']) && (($candidate['days_since_last_contact'] ?? null) === null || (int) ($candidate['days_since_last_contact'] ?? 0) >= 7)) {
            $score = 110;
            $reason = l('admin_leader_operating_system.queue_reason_follow_up_stale');
        } elseif(!empty($candidate['needs_follow_up'])) {
            $score = 100;
            $reason = l('admin_leader_operating_system.queue_reason_follow_up');
        } elseif($anomaly_score >= 55) {
            $score = 90;
            $reason = l('admin_leader_operating_system.queue_reason_anomaly_high');
        } elseif(!$candidate['has_profile']) {
            $score = 85;
            $reason = l('admin_leader_operating_system.queue_reason_waiting_profile');
        } elseif(!$candidate['has_checkin']) {
            $score = 75;
            $reason = l('admin_leader_operating_system.queue_reason_waiting_checkin');
        } elseif(($candidate['risk_score'] ?? 0) >= 55 || ($candidate['status_key'] ?? '') === 'risk') {
            $score = 70;
            $reason = l('admin_leader_operating_system.queue_reason_risk');
        } elseif($anomaly_score >= 25) {
            $score = 62;
            $reason = l('admin_leader_operating_system.queue_reason_anomaly_watch');
        } elseif(empty($candidate['mentored_this_week']) && ($candidate['leader_os_score'] ?? 0) < 55) {
            $score = 55;
            $reason = l('admin_leader_operating_system.queue_reason_no_mentor_touch');
        } elseif(($candidate['mentor_status'] ?? '') === 'in_progress') {
            $score = 40;
            $reason = l('admin_leader_operating_system.queue_reason_in_progress');
        }

        return [
            'queue_priority_score' => $score,
            'queue_reason' => $reason,
        ];
    }

    private function get_alert_entries(array $candidate): array {
        $alerts = [];

        if(!empty($candidate['needs_follow_up'])) {
            $alerts[] = [
                'type' => 'manual_follow_up',
                'label' => l('admin_leader_operating_system.alert.manual_follow_up'),
            ];
        }

        if(!empty($candidate['needs_follow_up']) && (($candidate['days_since_last_contact'] ?? null) === null || (int) ($candidate['days_since_last_contact'] ?? 0) >= 7)) {
            $alerts[] = [
                'type' => 'stale_follow_up',
                'label' => l('admin_leader_operating_system.alert.stale_follow_up'),
            ];
        }

        if(!empty($candidate['has_profile']) && empty($candidate['has_checkin'])) {
            $alerts[] = [
                'type' => 'missing_weekly',
                'label' => l('admin_leader_operating_system.alert.missing_weekly'),
            ];
        }

        if(($candidate['days_since_last_checkin'] ?? null) !== null && (int) $candidate['days_since_last_checkin'] >= 14) {
            $alerts[] = [
                'type' => 'stale_weekly',
                'label' => l('admin_leader_operating_system.alert.stale_weekly'),
            ];
        }

        if(in_array((string) ($candidate['latest_outcome_completion_level'] ?? ''), ['low_execution', 'not_started'], true)) {
            $alerts[] = [
                'type' => 'weak_execution',
                'label' => l('admin_leader_operating_system.alert.weak_execution'),
            ];
        }

        if(($candidate['status_key'] ?? '') === 'risk') {
            $alerts[] = [
                'type' => 'analytics_risk',
                'label' => l('admin_leader_operating_system.alert.analytics_risk'),
            ];
        }

        return $alerts;
    }

    /* Custom code: FC-2026-03-31: LOS overview anomaly stage payload */
    private function get_overview_anomaly_payload(array $candidate): array {
        $points = 0;

        if((int) ($candidate['forever_shop_clicks_period'] ?? 0) >= 12 && (int) ($candidate['forever_registration_clicks_period'] ?? 0) === 0) {
            $points += 28;
        }

        if((float) ($candidate['growth_percent'] ?? 0) <= -35) {
            $points += 22;
        }

        if((int) ($candidate['risk_score'] ?? 0) >= 55) {
            $points += 18;
        }

        if((int) ($candidate['clicks_total_period'] ?? 0) >= 20 && (int) ($candidate['active_days_total'] ?? 0) <= 2) {
            $points += 12;
        }

        if(((int) ($candidate['forever_shop_clicks_90d'] ?? 0) >= 15) && (($candidate['days_since_last_checkin'] ?? null) !== null) && (int) $candidate['days_since_last_checkin'] >= 14) {
            $points += 14;
        }

        if(!empty($candidate['needs_follow_up'])) {
            $points += 10;
        }

        $score = $this->clamp_score($points);
        $stage_key = 'stable';
        $stage_class = 'status-success';

        if($score >= 45) {
            $stage_key = 'high';
            $stage_class = 'status-warning';
        } elseif($score >= 20) {
            $stage_key = 'watch';
            $stage_class = 'status-info';
        }

        return [
            'anomaly_stage_key' => $stage_key,
            'anomaly_stage_label' => l('admin_leader_operating_system.anomaly_filter.' . $stage_key),
            'anomaly_stage_class' => $stage_class,
            'anomaly_score' => $score,
        ];
    }
    /* /Custom code: FC-2026-03-31 */

    /* Custom code: FC-2026-04-01: LOS overview suspicious Forever click collaborators */
    private function get_suspicious_click_overview_payload(array $rows, string $period_key): array {
        $retention_days = function_exists('fc_get_forever_click_integrity_retention_days') ? fc_get_forever_click_integrity_retention_days() : 30;
        $effective_period_days = min($this->get_period_days($period_key), $retention_days);
        $period_start_datetime = $this->get_period_start_datetime($effective_period_days);

        $payload = [
            'retention_days' => $retention_days,
            'effective_period_days' => $effective_period_days,
            'totals' => [
                'affected_collaborators' => 0,
                'blocked_attempts_total' => 0,
                'groups_total' => 0,
            ],
            'rows' => [],
        ];

        if(empty($rows) || !function_exists('fc_ensure_forever_click_integrity_tables')) {
            return $payload;
        }

        fc_ensure_forever_click_integrity_tables();

        $row_map = [];
        foreach($rows as $row) {
            $row_map[(int) ($row['user_id'] ?? 0)] = $row;
        }

        $user_ids = array_keys($row_map);
        if(empty($user_ids)) {
            return $payload;
        }

        $user_ids_sql = implode(',', array_map(static fn($user_id) => (int) $user_id, $user_ids));
        $suspicious_result = database()->query("SELECT
            `user_id`,
            `reason_key`,
            `reason_title`,
            `reason_text`,
            `target_signature`,
            `target_label`,
            `datetime`
        FROM `forever_click_integrity_suspicious`
        WHERE `datetime` >= '{$period_start_datetime}'
          AND `user_id` IN ({$user_ids_sql})
        ORDER BY `datetime` DESC");

        $grouped_rows = [];

        while($row = $suspicious_result->fetch_assoc()) {
            $user_id = (int) ($row['user_id'] ?? 0);

            if(!$user_id || !isset($row_map[$user_id])) {
                continue;
            }

            if(!isset($grouped_rows[$user_id])) {
                $grouped_rows[$user_id] = [
                    'blocked_attempts_total' => 0,
                    'groups' => [],
                    'targets' => [],
                    'latest_datetime' => (string) ($row['datetime'] ?? ''),
                    'latest_reason_title' => (string) ($row['reason_title'] ?? ''),
                    'latest_reason_text' => (string) ($row['reason_text'] ?? ''),
                ];
            }

            $grouped_rows[$user_id]['blocked_attempts_total']++;
            $grouped_rows[$user_id]['groups'][(string) (($row['reason_key'] ?? 'unknown') . '|' . ($row['target_signature'] ?? 'target'))] = true;
            $grouped_rows[$user_id]['targets'][(string) ($row['target_label'] ?? '-')] = true;

            if((string) ($row['datetime'] ?? '') >= $grouped_rows[$user_id]['latest_datetime']) {
                $grouped_rows[$user_id]['latest_datetime'] = (string) ($row['datetime'] ?? '');
                $grouped_rows[$user_id]['latest_reason_title'] = (string) ($row['reason_title'] ?? '');
                $grouped_rows[$user_id]['latest_reason_text'] = (string) ($row['reason_text'] ?? '');
            }
        }

        foreach($grouped_rows as $user_id => $suspicious_row) {
            $base_row = $row_map[$user_id];

            $payload['rows'][] = [
                'user_id' => $user_id,
                'name' => (string) ($base_row['name'] ?? l('global.unknown')),
                'email' => (string) ($base_row['email'] ?? ''),
                'detail_url' => (string) ($base_row['detail_url'] ?? ''),
                'admin_user_url' => (string) ($base_row['admin_user_url'] ?? ''),
                'status_label' => (string) ($base_row['status_label'] ?? ''),
                'status_class' => (string) ($base_row['status_class'] ?? 'secondary'),
                'ai_usage_stage_label' => (string) ($base_row['ai_usage_stage_label'] ?? ''),
                'ai_usage_stage_class' => (string) ($base_row['ai_usage_stage_class'] ?? 'status-dark'),
                'anomaly_stage_label' => (string) ($base_row['anomaly_stage_label'] ?? ''),
                'anomaly_stage_class' => (string) ($base_row['anomaly_stage_class'] ?? 'status-info'),
                'blocked_attempts_total' => (int) ($suspicious_row['blocked_attempts_total'] ?? 0),
                'suspicious_groups_total' => count($suspicious_row['groups'] ?? []),
                'targets_total' => count($suspicious_row['targets'] ?? []),
                'last_suspicious_at' => (string) ($suspicious_row['latest_datetime'] ?? ''),
                'top_reason_title' => (string) ($suspicious_row['latest_reason_title'] ?? ''),
                'top_reason_text' => (string) ($suspicious_row['latest_reason_text'] ?? ''),
            ];
        }

        usort($payload['rows'], static function($a, $b) {
            return (($b['blocked_attempts_total'] ?? 0) <=> ($a['blocked_attempts_total'] ?? 0))
                ?: (($b['suspicious_groups_total'] ?? 0) <=> ($a['suspicious_groups_total'] ?? 0))
                ?: strcmp((string) ($b['last_suspicious_at'] ?? ''), (string) ($a['last_suspicious_at'] ?? ''));
        });

        $payload['rows'] = array_slice($payload['rows'], 0, 8);
        $payload['totals']['affected_collaborators'] = count($grouped_rows);

        foreach($grouped_rows as $suspicious_row) {
            $payload['totals']['blocked_attempts_total'] += (int) ($suspicious_row['blocked_attempts_total'] ?? 0);
            $payload['totals']['groups_total'] += count($suspicious_row['groups'] ?? []);
        }

        return $payload;
    }
    /* /Custom code: FC-2026-04-01 */

    private function get_overview_payload(string $period_key, string $search_query, string $status_filter, string $ai_status_filter, string $anomaly_status_filter, string $sort_key, int $page): array {
        $period_days = $this->get_period_days($period_key);
        $period_start_datetime = $this->get_period_start_datetime($period_days);
        $previous_period_start_datetime = (new \DateTimeImmutable($period_start_datetime))->sub(new \DateInterval('P' . $period_days . 'D'))->format('Y-m-d H:i:s');
        $ninety_days_start_datetime = $this->get_period_start_datetime(90);
        $query_start_datetime = $period_days === 90 ? $previous_period_start_datetime : $ninety_days_start_datetime;
        $biolink_sets = $this->get_biolink_sets();
        $shop_condition = \Altum\Link::get_forever_shop_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql']);
        $registration_condition = \Altum\Link::get_forever_registration_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_registration_block_types_sql']);
        $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $biolink_sets['forever_shop_block_types_sql'], $biolink_sets['forever_registration_block_types_sql']);

        $rows = [];
        $result = database()->query("SELECT
            `users`.`user_id`,
            `users`.`name`,
            `users`.`email`,
            `users`.`preferences`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' THEN 1 ELSE 0 END) AS `clicks_total_period`,
            COUNT(DISTINCT CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' AND {$outbound_condition} THEN DATE(`track_links`.`datetime`) ELSE NULL END) AS `active_days_total`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$shop_condition} THEN 1 ELSE 0 END) AS `forever_shop_clicks_period`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$registration_condition} THEN 1 ELSE 0 END) AS `forever_registration_clicks_period`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$previous_period_start_datetime}' AND `track_links`.`datetime` < '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$shop_condition} THEN 1 ELSE 0 END) AS `previous_forever_shop_clicks_period`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$ninety_days_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$outbound_condition} THEN 1 ELSE 0 END) AS `forever_shop_clicks_90d`,
            SUM(CASE WHEN `track_links`.`datetime` >= '{$ninety_days_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$registration_condition} THEN 1 ELSE 0 END) AS `forever_registration_clicks_90d`,
            MAX(`track_links`.`datetime`) AS `last_click_at`
        FROM `users`
        LEFT JOIN `track_links` ON `track_links`.`user_id` = `users`.`user_id` AND `track_links`.`datetime` >= '{$query_start_datetime}'
        LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
        WHERE `users`.`type` = 0
        GROUP BY `users`.`user_id`
        ORDER BY `users`.`name` ASC");

        $search_query_normalized = mb_strtolower(trim($search_query));

        while($row = $result->fetch_object()) {
            $forever_id = $this->extract_forever_id_from_preferences($row->preferences ?? null);

            if($search_query_normalized !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    (string) ($row->name ?? ''),
                    (string) ($row->email ?? ''),
                    (string) $forever_id,
                ]));

                if(mb_strpos($haystack, $search_query_normalized) === false) {
                    continue;
                }
            }

            $candidate = [
                'user_id' => (int) ($row->user_id ?? 0),
                'name' => (string) ($row->name ?? l('global.unknown')),
                'email' => (string) ($row->email ?? ''),
                'forever_id' => $forever_id,
                'clicks_total_period' => (int) ($row->clicks_total_period ?? 0),
                'active_days_total' => (int) ($row->active_days_total ?? 0),
                'forever_shop_clicks_period' => (int) ($row->forever_shop_clicks_period ?? 0),
                'forever_registration_clicks_period' => (int) ($row->forever_registration_clicks_period ?? 0),
                'previous_forever_shop_clicks_period' => (int) ($row->previous_forever_shop_clicks_period ?? 0),
                'forever_shop_clicks_90d' => (int) ($row->forever_shop_clicks_90d ?? 0),
                'forever_registration_clicks_90d' => (int) ($row->forever_registration_clicks_90d ?? 0),
                'last_click_at' => (string) ($row->last_click_at ?? ''),
                'detail_url' => url('admin/leader-operating-system-leader?user_id=' . (int) ($row->user_id ?? 0) . '&period=' . $period_key),
                'admin_user_url' => url('admin/user-view/' . (int) ($row->user_id ?? 0)),
            ];

            $candidate['growth'] = $this->get_growth_metrics($candidate['forever_shop_clicks_period'], $candidate['previous_forever_shop_clicks_period']);
            $candidate['growth_percent'] = $candidate['growth']['growth_percent'];
            $candidate['growth_difference'] = $candidate['growth']['difference'];
            $candidate = array_merge($candidate, $this->get_scores($candidate));
            $candidate = array_merge($candidate, $this->get_status_payload($candidate));
            $candidate = array_merge($candidate, $this->get_ai_plan_overview_context($row->preferences ?? null));
            $candidate = array_merge($candidate, $this->get_overview_anomaly_payload($candidate));
            $candidate = array_merge($candidate, $this->get_queue_priority_payload($candidate));

            if($status_filter !== 'all') {
                $matches_filter = match($status_filter) {
                    'qualified' => (bool) $candidate['qualified'],
                    'rising', 'stable', 'risk', 'high_potential', 'inactive' => $candidate['status_key'] === $status_filter,
                    default => true,
                };

                if(!$matches_filter) {
                    continue;
                }
            }

            if($ai_status_filter !== 'all' && ($candidate['ai_usage_stage_key'] ?? 'inactive') !== $ai_status_filter) {
                continue;
            }

            if($anomaly_status_filter !== 'all' && ($candidate['anomaly_stage_key'] ?? 'stable') !== $anomaly_status_filter) {
                continue;
            }

            $rows[] = $candidate;
        }

        $totals = [
            'all_collaborators' => count($rows),
            'qualified' => 0,
            'rising' => 0,
            'risk' => 0,
            'anomaly_high' => 0,
            'anomaly_watch' => 0,
            'high_potential' => 0,
            'total_shop_clicks_period' => 0,
        ];

        foreach($rows as $row) {
            if($row['qualified']) {
                $totals['qualified']++;
            }
            if($row['status_key'] === 'rising') {
                $totals['rising']++;
            }
            if($row['status_key'] === 'risk') {
                $totals['risk']++;
            }
            if(($row['anomaly_stage_key'] ?? 'stable') === 'high') {
                $totals['anomaly_high']++;
            }
            if(($row['anomaly_stage_key'] ?? 'stable') === 'watch') {
                $totals['anomaly_watch']++;
            }
            if($row['status_key'] === 'high_potential') {
                $totals['high_potential']++;
            }
            $totals['total_shop_clicks_period'] += (int) $row['forever_shop_clicks_period'];
        }

        $rows = $this->enrich_rows_with_context($rows, $period_start_datetime, $biolink_sets);

        $queue_rows = array_values(array_filter($rows, static function($row) {
            return (int) ($row['queue_priority_score'] ?? 0) > 0;
        }));

        usort($queue_rows, static function($a, $b) {
            return (($b['queue_priority_score'] ?? 0) <=> ($a['queue_priority_score'] ?? 0))
                ?: (($b['anomaly_score'] ?? 0) <=> ($a['anomaly_score'] ?? 0))
                ?: (($b['risk_score'] ?? 0) <=> ($a['risk_score'] ?? 0))
                ?: (($a['name'] ?? '') <=> ($b['name'] ?? ''));
        });

        $queue_rows = array_slice($queue_rows, 0, 6);

        $recent_coaching_rows = array_values(array_filter($rows, static function($row) {
            return !empty($row['latest_mentor_event_at']);
        }));

        usort($recent_coaching_rows, static function($a, $b) {
            return strcmp((string) ($b['latest_mentor_event_at'] ?? ''), (string) ($a['latest_mentor_event_at'] ?? ''));
        });

        $recent_coaching_rows = array_slice($recent_coaching_rows, 0, 8);

        $alerts_totals = [
            'manual_follow_up' => 0,
            'weekly_signal_gaps' => 0,
            'execution_or_risk' => 0,
        ];
        $alert_rows = [];

        foreach($rows as $row) {
            $row_alerts = $this->get_alert_entries($row);

            foreach($row_alerts as $alert) {
                if(($alert['type'] ?? '') === 'manual_follow_up') {
                    $alerts_totals['manual_follow_up']++;
                }

                if(in_array(($alert['type'] ?? ''), ['missing_weekly', 'stale_weekly'], true)) {
                    $alerts_totals['weekly_signal_gaps']++;
                }

                if(in_array(($alert['type'] ?? ''), ['weak_execution', 'analytics_risk'], true)) {
                    $alerts_totals['execution_or_risk']++;
                }

                $alert_rows[] = [
                    'name' => $row['name'],
                    'label' => $alert['label'],
                    'detail_url' => $row['detail_url'],
                ];
            }
        }

        $alert_rows = array_slice($alert_rows, 0, 8);

        $suspicious_clicks = $this->get_suspicious_click_overview_payload($rows, $period_key);

        usort($rows, function($a, $b) use ($sort_key) {
            return match($sort_key) {
                'shop_clicks' => ($b['forever_shop_clicks_period'] <=> $a['forever_shop_clicks_period']) ?: ($b['leader_os_score'] <=> $a['leader_os_score']),
                'growth' => (($b['growth_percent'] ?? -9999) <=> ($a['growth_percent'] ?? -9999)) ?: ($b['leader_os_score'] <=> $a['leader_os_score']),
                'registrations' => ($b['forever_registration_clicks_period'] <=> $a['forever_registration_clicks_period']) ?: ($b['leader_os_score'] <=> $a['leader_os_score']),
                'risk' => ($b['risk_score'] <=> $a['risk_score']) ?: ($b['leader_os_score'] <=> $a['leader_os_score']),
                'country' => ($b['strongest_country_count'] <=> $a['strongest_country_count']) ?: (($a['strongest_country'] ?? '') <=> ($b['strongest_country'] ?? '')),
                'source' => ($b['top_source_count'] <=> $a['top_source_count']) ?: (($a['top_source_label'] ?? '') <=> ($b['top_source_label'] ?? '')),
                'last_click' => (($b['last_click_at'] ?? '') <=> ($a['last_click_at'] ?? '')) ?: ($b['leader_os_score'] <=> $a['leader_os_score']),
                default => ($b['leader_os_score'] <=> $a['leader_os_score']) ?: ($b['forever_shop_clicks_period'] <=> $a['forever_shop_clicks_period']),
            };
        });

        $total_results = count($rows);
        $per_page = 25;
        $total_pages = max(1, (int) ceil($total_results / $per_page));
        $page = max(1, min($page, $total_pages));
        $offset = ($page - 1) * $per_page;
        $rows = array_slice($rows, $offset, $per_page);

        return [
            'totals' => $totals,
            'queue_rows' => $queue_rows,
            'recent_coaching_rows' => $recent_coaching_rows,
            'alerts' => [
                'totals' => $alerts_totals,
                'rows' => $alert_rows,
            ],
            'suspicious_clicks' => $suspicious_clicks,
            'rows' => $rows,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_results' => $total_results,
                'total_pages' => $total_pages,
                'from' => $total_results > 0 ? ($offset + 1) : 0,
                'to' => min($offset + $per_page, $total_results),
            ],
        ];
    }

    public function index() {
        $allowed_periods = ['7d', '30d', '90d'];
        $selected_period = isset($_GET['period']) && in_array($_GET['period'], $allowed_periods, true) ? $_GET['period'] : '30d';
        $allowed_statuses = ['all', 'qualified', 'rising', 'stable', 'risk', 'high_potential', 'inactive'];
        $selected_status = isset($_GET['status']) && in_array($_GET['status'], $allowed_statuses, true) ? $_GET['status'] : 'all';
        $allowed_ai_statuses = ['all', 'inactive', 'started', 'questionnaire', 'active'];
        $selected_ai_status = isset($_GET['ai_status']) && in_array($_GET['ai_status'], $allowed_ai_statuses, true) ? $_GET['ai_status'] : 'all';
        $allowed_anomaly_statuses = ['all', 'stable', 'watch', 'high'];
        $selected_anomaly_status = isset($_GET['anomaly_status']) && in_array($_GET['anomaly_status'], $allowed_anomaly_statuses, true) ? $_GET['anomaly_status'] : 'all';
        $allowed_sorts = ['leader_os', 'shop_clicks', 'growth', 'registrations', 'risk', 'country', 'source', 'last_click'];
        $selected_sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts, true) ? $_GET['sort'] : 'leader_os';
        $search_query = trim((string) ($_GET['search'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        Title::set(l('admin_leader_operating_system.title'));

        $data = [
            'selected_period' => $selected_period,
            'period_options' => $allowed_periods,
            'selected_status' => $selected_status,
            'status_options' => $allowed_statuses,
            'selected_ai_status' => $selected_ai_status,
            'ai_status_options' => $allowed_ai_statuses,
            'selected_anomaly_status' => $selected_anomaly_status,
            'anomaly_status_options' => $allowed_anomaly_statuses,
            'selected_sort' => $selected_sort,
            'sort_options' => $allowed_sorts,
            'search_query' => $search_query,
            'overview' => $this->get_overview_payload($selected_period, $search_query, $selected_status, $selected_ai_status, $selected_anomaly_status, $selected_sort, $page),
        ];

        $view = new \Altum\View('admin/leader-operating-system/index', (array) $this);
        $this->add_view_content('content', $view->run((object) $data));
    }
}

/* /Custom code: FC-2026-03-31 */
