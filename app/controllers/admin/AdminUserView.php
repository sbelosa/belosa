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

class AdminUserView extends Controller {

    private function get_user_funnels_analytics(int $user_id): array {
        $thirty_days_start_datetime = (new \DateTime())->modify('-29 days')->format('Y-m-d 00:00:00');
        fc_ensure_funnel_analytics_tables();

        $funnels = [];
        $funnel_ids = [];

        $funnels_result = database()->query("
            SELECT
                `biolinks_blocks`.`biolink_block_id`,
                `biolinks_blocks`.`link_id`,
                `biolinks_blocks`.`settings`,
                `links`.`url` AS `biolink_url`
            FROM
                `biolinks_blocks`
            LEFT JOIN
                `links` ON `links`.`link_id` = `biolinks_blocks`.`link_id`
            WHERE
                `biolinks_blocks`.`user_id` = {$user_id}
                AND `biolinks_blocks`.`type` = 'lead_funnel'
                AND `links`.`type` = 'biolink'
            ORDER BY
                `biolinks_blocks`.`biolink_block_id` DESC
        ");

        while($row = $funnels_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '{}');
            $row->name = $row->settings->name ?? l('link.biolink.blocks.lead_funnel');
            $row->open_mode = in_array(($row->settings->open_mode ?? 'popup'), ['popup', 'page'], true) ? $row->settings->open_mode : 'popup';
            $row->thank_you_type = in_array(($row->settings->thank_you_type ?? 'message'), ['message', 'external_url', 'biolink_redirect', 'file_download'], true) ? $row->settings->thank_you_type : 'message';
            $row->analytics_url = url('funnels-analytics?biolink_block_id=' . $row->biolink_block_id);
            $row->data_url = url('data?type=lead_funnel&biolink_block_id=' . $row->biolink_block_id);

            $funnels[(int) $row->biolink_block_id] = $row;
            $funnel_ids[] = (int) $row->biolink_block_id;
        }

        $payload = [
            'total_funnels' => count($funnel_ids),
            'active_funnels_30d' => 0,
            'unique_clicks_total' => 0,
            'unique_clicks_30d' => 0,
            'leads_total' => 0,
            'leads_30d' => 0,
            'conversion_rate_total' => 0,
            'conversion_rate_30d' => 0,
            'top_funnels' => [],
            'open_mode_breakdown' => [],
            'thank_you_type_breakdown' => [],
            'flow' => [
                'entry_points_30d' => 0,
                'views_30d' => 0,
                'opens_30d' => 0,
                'form_starts_30d' => 0,
                'submit_attempts_30d' => 0,
                'submit_success_30d' => 0,
                'submit_errors_30d' => 0,
                'thank_you_views_30d' => 0,
                'cta_clicks_30d' => 0,
                'entry_to_start_rate_30d' => 0,
                'start_to_success_rate_30d' => 0,
                'success_to_cta_rate_30d' => 0,
            ],
            'flow_opportunities' => [],
            'best_open_mode_30d' => null,
            'best_thank_you_type_30d' => null,
        ];

        if(empty($funnel_ids)) {
            return $payload;
        }

        $funnel_ids_sql = implode(',', $funnel_ids);

        $unique_clicks_total = (int) (database()->query("SELECT COALESCE(SUM(`is_unique`), 0) AS `total` FROM `track_links` WHERE `user_id` = {$user_id} AND `biolink_block_id` IN ({$funnel_ids_sql})")->fetch_object()->total ?? 0);
        $unique_clicks_30d = (int) (database()->query("SELECT COALESCE(SUM(`is_unique`), 0) AS `total` FROM `track_links` WHERE `user_id` = {$user_id} AND `datetime` >= '{$thirty_days_start_datetime}' AND `biolink_block_id` IN ({$funnel_ids_sql})")->fetch_object()->total ?? 0);
        $leads_total = (int) (database()->query("SELECT COUNT(*) AS `total` FROM `data` WHERE `user_id` = {$user_id} AND `type` = 'lead_funnel' AND `biolink_block_id` IN ({$funnel_ids_sql})")->fetch_object()->total ?? 0);
        $leads_30d = (int) (database()->query("SELECT COUNT(*) AS `total` FROM `data` WHERE `user_id` = {$user_id} AND `type` = 'lead_funnel' AND `datetime` >= '{$thirty_days_start_datetime}' AND `biolink_block_id` IN ({$funnel_ids_sql})")->fetch_object()->total ?? 0);

        $clicks_30d_by_funnel = [];
        $clicks_30d_by_funnel_result = database()->query("SELECT `biolink_block_id`, COALESCE(SUM(`is_unique`), 0) AS `unique_clicks` FROM `track_links` WHERE `user_id` = {$user_id} AND `datetime` >= '{$thirty_days_start_datetime}' AND `biolink_block_id` IN ({$funnel_ids_sql}) GROUP BY `biolink_block_id`");
        while($row = $clicks_30d_by_funnel_result->fetch_object()) {
            $clicks_30d_by_funnel[(int) $row->biolink_block_id] = (int) ($row->unique_clicks ?? 0);
        }

        $leads_30d_by_funnel = [];
        $leads_30d_by_funnel_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total_leads` FROM `data` WHERE `user_id` = {$user_id} AND `type` = 'lead_funnel' AND `datetime` >= '{$thirty_days_start_datetime}' AND `biolink_block_id` IN ({$funnel_ids_sql}) GROUP BY `biolink_block_id`");
        while($row = $leads_30d_by_funnel_result->fetch_object()) {
            $leads_30d_by_funnel[(int) $row->biolink_block_id] = (int) ($row->total_leads ?? 0);
        }

        $flow_unique_by_event = [];
        $flow_totals_by_event = [];
        $flow_unique_by_funnel_event = [];
        $flow_events_result = database()->query("
            SELECT
                `biolink_block_id`,
                `event_type`,
                COUNT(*) AS `total_events`,
                COUNT(DISTINCT `visitor_key`) AS `unique_visitors`
            FROM
                `funnel_events`
            WHERE
                `user_id` = {$user_id}
                AND `datetime` >= '{$thirty_days_start_datetime}'
                AND `biolink_block_id` IN ({$funnel_ids_sql})
            GROUP BY
                `biolink_block_id`,
                `event_type`
        ");

        while($row = $flow_events_result->fetch_object()) {
            $event_type = (string) $row->event_type;
            $biolink_block_id = (int) $row->biolink_block_id;
            $unique_visitors = (int) ($row->unique_visitors ?? 0);
            $total_events = (int) ($row->total_events ?? 0);

            $flow_unique_by_event[$event_type] = ($flow_unique_by_event[$event_type] ?? 0) + $unique_visitors;
            $flow_totals_by_event[$event_type] = ($flow_totals_by_event[$event_type] ?? 0) + $total_events;
            $flow_unique_by_funnel_event[$biolink_block_id][$event_type] = $unique_visitors;
        }

        $open_mode_breakdown = [
            'popup' => ['type' => 'popup', 'funnels_count' => 0, 'unique_clicks_30d' => 0, 'leads_30d' => 0, 'conversion_rate_30d' => 0],
            'page' => ['type' => 'page', 'funnels_count' => 0, 'unique_clicks_30d' => 0, 'leads_30d' => 0, 'conversion_rate_30d' => 0],
        ];

        $thank_you_type_breakdown = [];
        foreach(['message', 'external_url', 'biolink_redirect', 'file_download'] as $type) {
            $thank_you_type_breakdown[$type] = ['type' => $type, 'funnels_count' => 0, 'unique_clicks_30d' => 0, 'leads_30d' => 0, 'conversion_rate_30d' => 0];
        }

        $top_funnels = [];
        $flow_opportunities = [];
        foreach($funnels as $funnel_id => $funnel) {
            $funnel_unique_clicks_30d = (int) ($clicks_30d_by_funnel[$funnel_id] ?? 0);
            $funnel_leads_30d = (int) ($leads_30d_by_funnel[$funnel_id] ?? 0);
            $funnel_conversion_rate_30d = $funnel_unique_clicks_30d ? round(($funnel_leads_30d / $funnel_unique_clicks_30d) * 100, 1) : 0;
            $funnel_flow = $flow_unique_by_funnel_event[$funnel_id] ?? [];
            $tracked_entry_points_30d = (int) (($funnel_flow['view'] ?? 0) + ($funnel_flow['open'] ?? 0));
            $entry_points_30d = max($tracked_entry_points_30d, $funnel_unique_clicks_30d);
            $form_starts_30d = max((int) ($funnel_flow['form_start'] ?? 0), (int) ($funnel_flow['submit_attempt'] ?? 0));
            $submit_success_30d = (int) ($funnel_flow['submit_success'] ?? 0);
            $cta_clicks_30d = (int) ($funnel_flow['thank_you_cta_click'] ?? 0);
            $entry_to_start_rate_30d = $entry_points_30d ? round(($form_starts_30d / $entry_points_30d) * 100, 1) : 0;
            $start_to_success_rate_30d = $form_starts_30d ? round(($submit_success_30d / $form_starts_30d) * 100, 1) : 0;
            $success_to_cta_rate_30d = $submit_success_30d ? round(($cta_clicks_30d / $submit_success_30d) * 100, 1) : 0;

            if($funnel_unique_clicks_30d > 0 || $funnel_leads_30d > 0) {
                $payload['active_funnels_30d']++;
            }

            $open_mode_breakdown[$funnel->open_mode]['funnels_count']++;
            $open_mode_breakdown[$funnel->open_mode]['unique_clicks_30d'] += $funnel_unique_clicks_30d;
            $open_mode_breakdown[$funnel->open_mode]['leads_30d'] += $funnel_leads_30d;

            $thank_you_type_breakdown[$funnel->thank_you_type]['funnels_count']++;
            $thank_you_type_breakdown[$funnel->thank_you_type]['unique_clicks_30d'] += $funnel_unique_clicks_30d;
            $thank_you_type_breakdown[$funnel->thank_you_type]['leads_30d'] += $funnel_leads_30d;

            $top_funnels[] = [
                'biolink_block_id' => (int) $funnel->biolink_block_id,
                'name' => (string) $funnel->name,
                'biolink_url' => (string) ($funnel->biolink_url ?? ''),
                'analytics_url' => (string) $funnel->analytics_url,
                'data_url' => (string) $funnel->data_url,
                'open_mode' => (string) $funnel->open_mode,
                'thank_you_type' => (string) $funnel->thank_you_type,
                'unique_clicks_30d' => $funnel_unique_clicks_30d,
                'leads_30d' => $funnel_leads_30d,
                'conversion_rate_30d' => $funnel_conversion_rate_30d,
                'entry_points_30d' => $entry_points_30d,
                'form_starts_30d' => $form_starts_30d,
                'submit_success_30d' => $submit_success_30d,
                'cta_clicks_30d' => $cta_clicks_30d,
            ];

            $weakest_stage = null;
            $weakest_rate = null;

            if($entry_points_30d >= 20) {
                $weakest_stage = 'entry_to_start';
                $weakest_rate = $entry_to_start_rate_30d;
            }

            if($form_starts_30d >= 10 && ($weakest_rate === null || $start_to_success_rate_30d < $weakest_rate)) {
                $weakest_stage = 'start_to_success';
                $weakest_rate = $start_to_success_rate_30d;
            }

            if($submit_success_30d >= 10 && ($weakest_rate === null || $success_to_cta_rate_30d < $weakest_rate)) {
                $weakest_stage = 'success_to_cta';
                $weakest_rate = $success_to_cta_rate_30d;
            }

            if($weakest_stage !== null) {
                $flow_opportunities[] = [
                    'biolink_block_id' => (int) $funnel->biolink_block_id,
                    'name' => (string) $funnel->name,
                    'analytics_url' => (string) $funnel->analytics_url,
                    'data_url' => (string) $funnel->data_url,
                    'weakest_stage' => $weakest_stage,
                    'weakest_rate' => $weakest_rate,
                    'entry_points_30d' => $entry_points_30d,
                    'form_starts_30d' => $form_starts_30d,
                    'submit_success_30d' => $submit_success_30d,
                    'cta_clicks_30d' => $cta_clicks_30d,
                ];
            }
        }

        foreach($open_mode_breakdown as &$row) {
            $row['conversion_rate_30d'] = $row['unique_clicks_30d'] ? round(($row['leads_30d'] / $row['unique_clicks_30d']) * 100, 1) : 0;
        }
        unset($row);

        foreach($thank_you_type_breakdown as &$row) {
            $row['conversion_rate_30d'] = $row['unique_clicks_30d'] ? round(($row['leads_30d'] / $row['unique_clicks_30d']) * 100, 1) : 0;
        }
        unset($row);

        $open_mode_ranked = array_values($open_mode_breakdown);
        usort($open_mode_ranked, fn($a, $b) => [$b['conversion_rate_30d'], $b['leads_30d'], $b['unique_clicks_30d']] <=> [$a['conversion_rate_30d'], $a['leads_30d'], $a['unique_clicks_30d']]);
        $thank_you_ranked = array_values($thank_you_type_breakdown);
        usort($thank_you_ranked, fn($a, $b) => [$b['conversion_rate_30d'], $b['leads_30d'], $b['unique_clicks_30d']] <=> [$a['conversion_rate_30d'], $a['leads_30d'], $a['unique_clicks_30d']]);

        usort($top_funnels, fn($a, $b) => [$b['leads_30d'], $b['unique_clicks_30d'], $b['conversion_rate_30d']] <=> [$a['leads_30d'], $a['unique_clicks_30d'], $a['conversion_rate_30d']]);
        usort($flow_opportunities, fn($a, $b) => [$a['weakest_rate'], $b['entry_points_30d']] <=> [$b['weakest_rate'], $a['entry_points_30d']]);

        $payload['unique_clicks_total'] = $unique_clicks_total;
        $payload['unique_clicks_30d'] = $unique_clicks_30d;
        $payload['leads_total'] = $leads_total;
        $payload['leads_30d'] = $leads_30d;
        $payload['conversion_rate_total'] = $unique_clicks_total ? round(($leads_total / $unique_clicks_total) * 100, 1) : 0;
        $payload['conversion_rate_30d'] = $unique_clicks_30d ? round(($leads_30d / $unique_clicks_30d) * 100, 1) : 0;
        $payload['top_funnels'] = array_slice($top_funnels, 0, 5);
        $payload['open_mode_breakdown'] = array_values($open_mode_breakdown);
        $payload['thank_you_type_breakdown'] = array_values($thank_you_type_breakdown);
        $payload['best_open_mode_30d'] = $open_mode_ranked[0] ?? null;
        $payload['best_thank_you_type_30d'] = $thank_you_ranked[0] ?? null;
        $payload['flow'] = [
            'views_30d' => (int) ($flow_unique_by_event['view'] ?? 0),
            'opens_30d' => (int) ($flow_unique_by_event['open'] ?? 0),
            'form_starts_30d' => max((int) ($flow_unique_by_event['form_start'] ?? 0), (int) ($flow_unique_by_event['submit_attempt'] ?? 0)),
            'submit_attempts_30d' => (int) ($flow_unique_by_event['submit_attempt'] ?? 0),
            'submit_success_30d' => (int) ($flow_unique_by_event['submit_success'] ?? 0),
            'submit_errors_30d' => (int) ($flow_totals_by_event['submit_error'] ?? 0),
            'thank_you_views_30d' => max((int) ($flow_unique_by_event['thank_you_view'] ?? 0), (int) ($flow_unique_by_event['submit_success'] ?? 0)),
            'cta_clicks_30d' => (int) ($flow_unique_by_event['thank_you_cta_click'] ?? 0),
        ];
        $payload['flow']['entry_points_30d'] = max($payload['flow']['views_30d'] + $payload['flow']['opens_30d'], $unique_clicks_30d);
        $payload['flow']['entry_to_start_rate_30d'] = $payload['flow']['entry_points_30d'] ? round(($payload['flow']['form_starts_30d'] / $payload['flow']['entry_points_30d']) * 100, 1) : 0;
        $payload['flow']['start_to_success_rate_30d'] = $payload['flow']['form_starts_30d'] ? round(($payload['flow']['submit_success_30d'] / $payload['flow']['form_starts_30d']) * 100, 1) : 0;
        $payload['flow']['success_to_cta_rate_30d'] = $payload['flow']['submit_success_30d'] ? round(($payload['flow']['cta_clicks_30d'] / $payload['flow']['submit_success_30d']) * 100, 1) : 0;
        $payload['flow_opportunities'] = array_slice($flow_opportunities, 0, 5);

        return $payload;
    }

    public function index() {

        $user_id = (isset($this->params[0])) ? (int) $this->params[0] : null;

        /* Check if resource exists */
        if(!$user = db()->where('user_id', $user_id)->getOne('users')) {
            redirect('admin/users');
        }

        /* Custom code: FC-2026-03-19: ensure email analytics tables exist for admin profile mail activity */
        fc_ensure_email_automation_tables();
        /* /Custom code: FC-2026-03-19 */

        /* Get widget stats */
        $biolink_links = db()->where('user_id', $user_id)->where('type', 'biolink')->getValue('links', 'count(`link_id`)');
        $shortened_links = db()->where('user_id', $user_id)->where('type', 'link')->getValue('links', 'count(`link_id`)');
        $file_links = db()->where('user_id', $user_id)->where('type', 'file')->getValue('links', 'count(`link_id`)');
        $vcard_links = db()->where('user_id', $user_id)->where('type', 'vcard')->getValue('links', 'count(`link_id`)');
        $event_links = db()->where('user_id', $user_id)->where('type', 'event')->getValue('links', 'count(`link_id`)');
        $static_links = db()->where('user_id', $user_id)->where('type', 'static')->getValue('links', 'count(`link_id`)');
        $projects = db()->where('user_id', $user_id)->getValue('projects', 'count(`project_id`)');
        $pixels = db()->where('user_id', $user_id)->getValue('pixels', 'count(`pixel_id`)');
        $splash_pages = db()->where('user_id', $user_id)->getValue('splash_pages', 'count(`splash_page_id`)');
        $qr_codes = db()->where('user_id', $user_id)->getValue('qr_codes', 'count(`qr_code_id`)');
        $domains = db()->where('user_id', $user_id)->getValue('domains', 'count(`domain_id`)');
        $payments = in_array(settings()->license->type, ['Extended License', 'extended']) ? db()->where('user_id', $user_id)->getValue('payments', 'count(`id`)') : 0;

        if(\Altum\Plugin::is_active('email-signatures')) {
            $signatures = db()->where('user_id', $user_id)->getValue('signatures', 'count(`signature_id`)');
        }

        if(\Altum\Plugin::is_active('aix')) {
            $documents = db()->where('user_id', $user_id)->getValue('documents', 'count(`document_id`)');
            $images = db()->where('user_id', $user_id)->getValue('images', 'count(`image_id`)');
            $transcriptions = db()->where('user_id', $user_id)->getValue('transcriptions', 'count(`transcription_id`)');
            $syntheses = db()->where('user_id', $user_id)->getValue('syntheses', 'count(`synthesis_id`)');
            $chats = db()->where('user_id', $user_id)->getValue('chats', 'count(`chat_id`)');
        }

        /* Custom code: FC-2026-03-04: admin user view analytics summary */
        $thirty_days_start_datetime = (new \DateTime())->modify('-29 days')->format('Y-m-d 00:00:00');

        $track_clicks_total = (int) db()->where('user_id', $user_id)->getValue('track_links', 'COUNT(`id`)');
        $track_clicks_unique_total = (int) db()->where('user_id', $user_id)->where('is_unique', 1)->getValue('track_links', 'COUNT(`id`)');
        $track_clicks_30d = (int) db()->where('user_id', $user_id)->where('datetime', $thirty_days_start_datetime, '>=')->getValue('track_links', 'COUNT(`id`)');
        $track_clicks_unique_30d = (int) db()->where('user_id', $user_id)->where('is_unique', 1)->where('datetime', $thirty_days_start_datetime, '>=')->getValue('track_links', 'COUNT(`id`)');

        $biolink_visits_total = (int) database()->query("SELECT COUNT(*) AS total FROM `track_links` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`user_id` = {$user_id} AND `links`.`type` = 'biolink'")->fetch_object()->total;
        $biolink_visits_30d = (int) database()->query("SELECT COUNT(*) AS total FROM `track_links` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`user_id` = {$user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `links`.`type` = 'biolink'")->fetch_object()->total;

        $revenue_total = (float) (db()->where('user_id', $user_id)->where('status', 'paid')->getValue('payments', 'SUM(`total_amount_default_currency`)') ?? 0);
        $revenue_30d = (float) (db()->where('user_id', $user_id)->where('status', 'paid')->where('datetime', $thirty_days_start_datetime, '>=')->getValue('payments', 'SUM(`total_amount_default_currency`)') ?? 0);
        $paid_payments_total = (int) db()->where('user_id', $user_id)->where('status', 'paid')->getValue('payments', 'COUNT(`id`)');
        $paid_payments_30d = (int) db()->where('user_id', $user_id)->where('status', 'paid')->where('datetime', $thirty_days_start_datetime, '>=')->getValue('payments', 'COUNT(`id`)');
        $failed_payments_30d = (int) db()->where('user_id', $user_id)->where('status', ['pending', 'cancelled'], 'IN')->where('datetime', $thirty_days_start_datetime, '>=')->getValue('payments', 'COUNT(`id`)');

        $top_countries = [];
        $top_countries_result = database()->query("SELECT `country_code`, COUNT(*) AS `total` FROM `track_links` WHERE `user_id` = {$user_id} AND `country_code` IS NOT NULL AND `country_code` != '' GROUP BY `country_code` ORDER BY `total` DESC LIMIT 5");
        while($country = $top_countries_result->fetch_object()) {
            $top_countries[] = [
                'country_code' => (string) ($country->country_code ?? ''),
                'total' => (int) ($country->total ?? 0),
            ];
        }

        $top_links = [];
        $top_links_result = database()->query("SELECT `track_links`.`link_id`, `links`.`url`, `links`.`type`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`user_id` = {$user_id} AND `track_links`.`link_id` IS NOT NULL GROUP BY `track_links`.`link_id` ORDER BY `total` DESC LIMIT 5");
        while($link = $top_links_result->fetch_object()) {
            $top_links[] = [
                'link_id' => (int) ($link->link_id ?? 0),
                'url' => (string) ($link->url ?? ''),
                'type' => (string) ($link->type ?? ''),
                'total' => (int) ($link->total ?? 0),
            ];
        }

        $recent_payments = [];
        $recent_payments_result = db()->where('user_id', $user_id)->orderBy('id', 'DESC')->get('payments', 5, ['id', 'datetime', 'status', 'processor', 'type', 'total_amount', 'currency', 'total_amount_default_currency']);
        foreach($recent_payments_result as $payment) {
            $recent_payments[] = [
                'id' => (int) ($payment->id ?? 0),
                'datetime' => (string) ($payment->datetime ?? ''),
                'status' => (string) ($payment->status ?? ''),
                'processor' => (string) ($payment->processor ?? ''),
                'type' => (string) ($payment->type ?? ''),
                'total_amount' => (float) ($payment->total_amount ?? 0),
                'currency' => (string) ($payment->currency ?? settings()->payment->default_currency),
                'total_amount_default_currency' => (float) ($payment->total_amount_default_currency ?? 0),
            ];
        }

        $user_analytics = [
            'track_clicks_total' => $track_clicks_total,
            'track_clicks_unique_total' => $track_clicks_unique_total,
            'track_clicks_30d' => $track_clicks_30d,
            'track_clicks_unique_30d' => $track_clicks_unique_30d,
            'biolink_visits_total' => $biolink_visits_total,
            'biolink_visits_30d' => $biolink_visits_30d,
            'revenue_total' => round($revenue_total, 2),
            'revenue_30d' => round($revenue_30d, 2),
            'paid_payments_total' => $paid_payments_total,
            'paid_payments_30d' => $paid_payments_30d,
            'failed_payments_30d' => $failed_payments_30d,
            'top_countries' => $top_countries,
            'top_links' => $top_links,
            'recent_payments' => $recent_payments,
        ];
        /* /Custom code: FC-2026-03-04 */

        /* Custom code: FC-2026-03-26: per-user funnel performance summary for admin profile */
        $user_funnels_analytics = $this->get_user_funnels_analytics($user_id);
        /* /Custom code: FC-2026-03-26 */

        /* Get the current plan details */
        $user->plan = (new Plan())->get_plan_by_id($user->plan_id);

        /* Check if its a custom plan */
        if($user->plan_id == 'custom') {
            $user->plan->settings = $user->plan_settings;
        }

        $user->billing = json_decode($user->billing ?? '');
        $preferences  = json_decode($user->preferences  ?? ''); /* Custom code */

        /* Custom code: FC-2026-03-17: billing risk summary and audit timeline for support */
        $billing_model = new Billing();
        $billing_summary = $billing_model->get_user_billing_summary($user_id);
        $billing_events = $billing_model->get_user_billing_events($user_id, 50);
        /* /Custom code: FC-2026-03-17 */

        /* Custom code: FC-2026-03-19: per-user mail activity summary for admin user profile */
        $email_activity = fc_get_user_email_activity($user_id, 100);
        /* /Custom code: FC-2026-03-19 */

        /* Main View */
        $data = [
            'user' => $user,
            'biolink_links' => $biolink_links,
            'shortened_links' => $shortened_links,
            'file_links' => $file_links,
            'vcard_links' => $vcard_links,
            'event_links' => $event_links,
            'static_links' => $static_links,
            'projects' => $projects,
            'splash_pages' => $splash_pages,
            'pixels' => $pixels,
            'qr_codes' => $qr_codes,
            'domains' => $domains,
            'payments' => $payments,
            'signatures' => $signatures ?? null,
            'documents' => $documents ?? null,
            'images' => $images ?? null,
            'transcriptions' => $transcriptions ?? null,
            'syntheses' => $syntheses ?? null,
            'chats' => $chats ?? null,
            'user_analytics' => $user_analytics,
            'user_funnels_analytics' => $user_funnels_analytics,
            'user_meta' => $preferences->meta ?? null, /* Custom code */
            'user_email_unsubscribe' => $preferences->email_unsubscribe ?? null,
            /* Custom code: FC-2026-03-17: expose billing risk summary and events to admin user profile */
            'billing_summary' => $billing_summary,
            'billing_events' => $billing_events,
            /* /Custom code: FC-2026-03-17 */
            /* Custom code: FC-2026-03-19: expose user email activity to admin profile */
            'email_activity' => $email_activity,
            /* /Custom code: FC-2026-03-19 */
        ];

        $view = new \Altum\View('admin/user-view/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
