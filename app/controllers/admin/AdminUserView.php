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

    public function index() {

        $user_id = (isset($this->params[0])) ? (int) $this->params[0] : null;

        /* Check if resource exists */
        if(!$user = db()->where('user_id', $user_id)->getOne('users')) {
            redirect('admin/users');
        }

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
            'user_meta' => $preferences->meta ?? null, /* Custom code */
            /* Custom code: FC-2026-03-17: expose billing risk summary and events to admin user profile */
            'billing_summary' => $billing_summary,
            'billing_events' => $billing_events,
            /* /Custom code: FC-2026-03-17 */
        ];

        $view = new \Altum\View('admin/user-view/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
