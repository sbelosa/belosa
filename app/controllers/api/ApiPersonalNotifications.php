<?php
/*
 * Copyright (c) 2025 AltumCode (https://altumcode.com/)
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

use Altum\Date;
use Altum\Models\PersonalNotification;
use Altum\Models\Subscriber;
use Altum\Response;
use Altum\Traits\Apiable;

defined('ALTUMCODE') || die();

class ApiPersonalNotifications extends Controller {
    use Apiable;

    public function index() {

        $this->verify_request();

        /* Decide what to continue with */
        switch($_SERVER['REQUEST_METHOD']) {
            case 'GET':

                /* Detect if we only need an object, or the whole list */
                if(isset($this->params[0])) {
                    $this->get();
                } else {
                    $this->get_all();
                }

                break;

            case 'POST':

                /* Detect what method to use */
                if(isset($this->params[0])) {
                    $this->patch();
                } else {
                    $this->post();
                }

                break;

            case 'DELETE':
                $this->delete();
                break;
        }

        $this->return_404();
    }

    private function get_all() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters([], [], []));
        $filters->set_default_order_by($this->api_user->preferences->personal_notifications_default_order_by, $this->api_user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->api_user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);
        $filters->process();

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `personal_notifications` WHERE `user_id` = {$this->api_user->user_id}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('api/personal-notifications?' . $filters->get_get() . '&page=%d')));

        /* Get the data */
        $data = [];
        $data_result = database()->query("
            SELECT
                *
            FROM
                `personal_notifications`
            WHERE
                `user_id` = {$this->api_user->user_id}
                {$filters->get_sql_where()}
                {$filters->get_sql_order_by()}
                  
            {$paginator->get_sql_limit()}
        ");


        while($row = $data_result->fetch_object()) {

            /* Prepare the data */
            $row = [
                'id' => (int) $row->personal_notification_id,
                'user_id' => (int) $row->user_id,
                'website_id' => (int) $row->website_id,
                'subscriber_id' => (int) $row->subscriber_id,
                'name' => $row->name,
                'title' => $row->title,
                'description' => $row->description,
                'url' => $row->url,
                'image_url' => $row->image ? \Altum\Uploads::get_full_url('websites_personal_notifications_images') . $row->image : null,
                'settings' => json_decode($row->settings),
                'is_sent' => (int) $row->is_sent,
                'is_displayed' => (int) $row->is_displayed,
                'is_clicked' => (int) $row->is_clicked,
                'is_closed' => (int) $row->is_closed,
                'status' => $row->status,
                'scheduled_datetime' => $row->scheduled_datetime,
                'sent_datetime' => $row->sent_datetime,
                'last_datetime' => $row->last_datetime,
                'datetime' => $row->datetime
            ];

            $data[] = $row;
        }

        /* Prepare the data */
        $meta = [
            'page' => $_GET['page'] ?? 1,
            'total_pages' => $paginator->getNumPages(),
            'results_per_page' => $filters->get_results_per_page(),
            'total_results' => (int) $total_rows,
        ];

        /* Prepare the pagination links */
        $others = ['links' => [
            'first' => $paginator->getPageUrl(1),
            'last' => $paginator->getNumPages() ? $paginator->getPageUrl($paginator->getNumPages()) : null,
            'next' => $paginator->getNextUrl(),
            'prev' => $paginator->getPrevUrl(),
            'self' => $paginator->getPageUrl($_GET['page'] ?? 1)
        ]];

        Response::jsonapi_success($data, $meta, 200, $others);
    }

    private function get() {

        $personal_notification_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $personal_notification = db()->where('personal_notification_id', $personal_notification_id)->where('user_id', $this->api_user->user_id)->getOne('personal_notifications');

        /* We haven't found the resource */
        if(!$personal_notification) {
            $this->return_404();
        }

        /* Prepare the data */
        $data = [
            'id' => (int) $personal_notification->personal_notification_id,
            'user_id' => (int) $personal_notification->user_id,
            'website_id' => (int) $personal_notification->website_id,
            'subscriber_id' => (int) $personal_notification->subscriber_id,
            'name' => $personal_notification->name,
            'title' => $personal_notification->title,
            'description' => $personal_notification->description,
            'url' => $personal_notification->url,
            'image_url' => $personal_notification->image ? \Altum\Uploads::get_full_url('websites_personal_notifications_images') . $personal_notification->image : null,
            'settings' => json_decode($personal_notification->settings),
            'is_sent' => (int) $personal_notification->is_sent,
            'is_displayed' => (int) $personal_notification->is_displayed,
            'is_clicked' => (int) $personal_notification->is_clicked,
            'is_closed' => (int) $personal_notification->is_closed,
            'status' => $personal_notification->status,
            'scheduled_datetime' => $personal_notification->scheduled_datetime,
            'sent_datetime' => $personal_notification->sent_datetime,
            'last_datetime' => $personal_notification->last_datetime,
            'datetime' => $personal_notification->datetime,
        ];

        Response::jsonapi_success($data);

    }

    private function post() {

        /* Check for any errors */
        $required_fields = ['website_id', 'subscriber_id', 'name', 'title', 'description'];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $this->response_error(l('global.error_message.empty_fields'), 401);
                break 1;
            }
        }

        /* Check for the plan limit */
        $total_rows = db()->where('user_id', $this->api_user->user_id)->getValue('personal_notifications', 'count(*)');
        if($this->api_user->plan_settings->personal_notifications_limit != -1 && $total_rows >= $this->api_user->plan_settings->personal_notifications_limit) {
            $this->response_error(l('global.info_message.plan_feature_limit'), 401);
        }

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->api_user->user_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        /* Filter some of the variables */
        $_POST['name'] = input_clean($_POST['name'], 256);
        $_POST['title'] = input_clean($_POST['title'], 64);
        $_POST['description'] = input_clean($_POST['description'], 128);
        $_POST['url'] = get_url($_POST['url'] ?? '', 512);
        $_POST['website_id'] = array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : array_key_first($websites);
        $_POST['subscriber_id'] = (int) $_POST['subscriber_id'];

        /* Scheduling */
        $_POST['is_scheduled'] = (int) isset($_POST['is_scheduled']);
        $_POST['scheduled_datetime'] = $_POST['is_scheduled'] && !empty($_POST['scheduled_datetime']) && Date::validate($_POST['scheduled_datetime'], 'Y-m-d H:i:s') ?
            (new \DateTime($_POST['scheduled_datetime'], new \DateTimeZone($this->api_user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s')
            : get_date();

        /* Advanced */
        $_POST['ttl'] = isset($_POST['ttl']) && array_key_exists($_POST['ttl'], $notifications_ttl) ? (int) $_POST['ttl'] : array_key_last($notifications_ttl);
        $_POST['urgency'] = isset($_POST['urgency']) && in_array($_POST['urgency'], ['low', 'normal', 'high']) ? $_POST['urgency'] : 'normal';
        $_POST['is_silent'] = (int) isset($_POST['is_silent']);
        $_POST['is_auto_hide'] = (int) isset($_POST['is_auto_hide']);

        /* Buttons */
        $_POST['button_title_1'] = input_clean($_POST['button_title_1'] ?? null, 16);
        $_POST['button_url_1'] = get_url($_POST['button_url_1'] ?? null, 512);
        $_POST['button_title_2'] = input_clean($_POST['button_title_2'] ?? null, 16);
        $_POST['button_url_2'] = get_url($_POST['button_url_2'] ?? null, 512);

        /* UTM */
        $_POST['utm_medium'] = input_clean($_POST['utm_medium'] ?? '', 128);
        $_POST['utm_source'] = input_clean($_POST['utm_source'] ?? '', 128);
        $_POST['utm_campaign'] = input_clean($_POST['utm_campaign'] ?? '', 128);

        /* Get the subscriber */
        $subscriber = (new Subscriber())->get_subscriber_by_subscriber_id($_POST['subscriber_id']);

        /* Make sure subscriber exists properly */
        if(!$subscriber || $subscriber->website_id != $_POST['website_id']) {
            $this->response_error(l('personal_notifications.error_message.subscriber_id'), 401);
        }

        /* Status of the notification */
        $status = $_POST['is_scheduled'] && $_POST['scheduled_datetime'] ? 'scheduled' : 'processing';
        if(isset($_POST['save'])) {
            $status = 'draft';
        }

        if($status != 'draft') {
            /* Check for the plan limit */
            $sent_push_notifications_current_month = db()->where('user_id', $this->api_user->user_id)->getValue('users', '`pusher_sent_push_notifications_current_month`');
            if($this->api_user->plan_settings->sent_push_notifications_per_month_limit != -1 && $sent_push_notifications_current_month + 1 > $this->api_user->plan_settings->sent_push_notifications_per_month_limit) {
                $this->response_error(l('global.info_message.plan_feature_limit'), 401);
            }
        }

        /* Uploaded image */
        $image = \Altum\Uploads::process_upload(null, 'websites_personal_notifications_images', 'image', 'image_remove', settings()->websites->personal_notification_images_limit, 'json_error');

        $settings = [
            /* Scheduling */
            'is_scheduled' => $_POST['is_scheduled'],

            /* Advanced */
            'ttl' => $_POST['ttl'],
            'urgency' => $_POST['urgency'],
            'is_silent' => $_POST['is_silent'],
            'is_auto_hide' => $_POST['is_auto_hide'],

            /* Buttons */
            'button_title_1' => $_POST['button_title_1'],
            'button_url_1' => $_POST['button_url_1'],
            'button_title_2' => $_POST['button_title_2'],
            'button_url_2' => $_POST['button_url_2'],

            /* UTM */
            'utm' => [
                'source' => $_POST['utm_source'],
                'medium' => $_POST['utm_medium'],
                'campaign' => $_POST['utm_campaign'],
            ]
        ];

        /* Database query */
        $personal_notification_id = db()->insert('personal_notifications', [
            'website_id' => $_POST['website_id'],
            'subscriber_id' => $_POST['subscriber_id'],
            'user_id' => $this->api_user->user_id,
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image' => $image,
            'settings' => json_encode($settings),
            'status' => $status,
            'scheduled_datetime' => $_POST['scheduled_datetime'],
            'datetime' => get_date(),
        ]);

        /* Sent the notification now if requested */
        if($status == 'processing') {
            $website = $websites[$_POST['website_id']];
            $is_sent = 1;
            $status = 'sent';
            $sent_datetime = get_date();

            /* Process UTM parameters */
            $_POST['url'] = process_utm_parameters((object) $settings['utm'], $_POST['url']);
            $_POST['button_url_1'] = process_utm_parameters((object) $settings['utm'], $_POST['button_url_1']);
            $_POST['button_url_2'] = process_utm_parameters((object) $settings['utm'], $_POST['button_url_2']);

            /* Send web push */
            process_push_notification([
                'website' => $website,
                'subscriber' => $subscriber,

                'source_id' => 'personal_notification_id',
                'source_value' => $personal_notification_id,

                'web_push_data' => [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'url' => $_POST['url'],
                    'is_silent' => $_POST['is_silent'],
                    'is_auto_hide' => $_POST['is_auto_hide'],
                    'button_title_1' => $_POST['button_title_1'],
                    'button_url_1' => $_POST['button_url_1'],
                    'button_title_2' => $_POST['button_title_2'],
                    'button_url_2' => $_POST['button_url_2'],
                    'image' => $image ? \Altum\Uploads::get_full_url('websites_personal_notifications_images') . $image : null,
                    'content' => [
                        'source_type' => 'personal_notification_id',
                        'personal_notification_id' => $personal_notification_id,
                    ]
                ]
            ], function() use ($personal_notification_id, $is_sent, $status, $sent_datetime) {

                /* Update the personal notification */
                db()->where('personal_notification_id', $personal_notification_id)->update('personal_notifications', [
                    'is_sent' => $is_sent,
                    'status' => $status,
                    'sent_datetime' => $sent_datetime,
                ]);

            });
        }

        /* Prepare the data */
        $data = [
            'id' => $personal_notification_id,
            'user_id' => (int) $this->api_user->user_id,
            'website_id' => $_POST['website_id'],
            'subscriber_id' => $_POST['subscriber_id'],
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_url' => $image ? \Altum\Uploads::get_full_url('websites_personal_notifications_images') . $image : null,
            'settings' => $settings,
            'is_sent' => $is_sent ?? 0,
            'is_displayed' => 0,
            'is_clicked' => 0,
            'is_closed' => 0,
            'status' => $status,
            'scheduled_datetime' => $_POST['scheduled_datetime'],
            'sent_datetime' => $sent_datetime ?? null,
            'last_datetime' => null,
            'datetime' => get_date(),
        ];

        Response::jsonapi_success($data, null, 201);

    }

    private function patch() {

        /* Check for the plan limit */
        $total_rows = db()->where('user_id', $this->api_user->user_id)->getValue('personal_notifications', 'count(`personal_notification_id`)');

        if($this->api_user->plan_settings->personal_notifications_limit != -1 && $total_rows > $this->api_user->plan_settings->personal_notifications_limit) {
            $this->response_error(sprintf(settings()->payment->is_enabled ? l('global.info_message.plan_feature_limit_removal_with_upgrade') : l('global.info_message.plan_feature_limit_removal'), $total_rows - $this->user->plan_settings->personal_notifications_limit, mb_strtolower(l('personal_notifications.title')), l('global.info_message.plan_upgrade')), 401);
        }

        $personal_notification_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $personal_notification = db()->where('personal_notification_id', $personal_notification_id)->where('user_id', $this->api_user->user_id)->getOne('personal_notifications');

        /* We haven't found the resource */
        if(!$personal_notification) {
            $this->return_404();
        }

        $personal_notification->settings = json_decode($personal_notification->settings ?? '');

        /* Check for any errors */
        $required_fields = [];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $this->response_error(l('global.error_message.empty_fields'), 401);
                break 1;
            }
        }

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->api_user->user_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        /* Filter some of the variables */
        $_POST['name'] = input_clean($_POST['name'] ?? $personal_notification->name, 256);
        $_POST['title'] = input_clean($_POST['title'] ?? $personal_notification->title, 64);
        $_POST['description'] = input_clean($_POST['description'] ?? $personal_notification->description, 128);
        $_POST['url'] = get_url($_POST['url'] ?? $personal_notification->url, 512);
        $_POST['website_id'] = isset($_POST['website_id']) && array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : $personal_notification->website_id;
        $_POST['subscriber_id'] = isset($_POST['subscriber_id']) ? (int) $_POST['subscriber_id'] : $personal_notification->subscriber_id;

        /* Scheduling */
        $_POST['is_scheduled'] = (int) (bool) ($_POST['is_scheduled'] ?? $personal_notification->is_scheduled);
        $_POST['scheduled_datetime'] = $_POST['is_scheduled'] && !empty($_POST['scheduled_datetime']) && Date::validate($_POST['scheduled_datetime'], 'Y-m-d H:i:s') ?
            (new \DateTime($_POST['scheduled_datetime'], new \DateTimeZone($this->api_user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s')
            : $personal_notification->scheduled_datetime;

        /* Advanced */
        $_POST['ttl'] = isset($_POST['ttl']) && array_key_exists($_POST['ttl'], $notifications_ttl) ? (int) $_POST['ttl'] : $personal_notification->settings->ttl;
        $_POST['urgency'] = isset($_POST['urgency']) && in_array($_POST['urgency'], ['low', 'normal', 'high']) ? $_POST['urgency'] : $personal_notification->settings->urgency;
        $_POST['is_silent'] = (int) (bool) ($_POST['is_silent'] ?? $personal_notification->settings->is_silent);
        $_POST['is_auto_hide'] = (int) (bool) ($_POST['is_auto_hide'] ?? $personal_notification->settings->is_auto_hide);

        /* Buttons */
        $_POST['button_title_1'] = input_clean($_POST['button_title_1'] ?? $personal_notification->settings->button_title_1, 16);
        $_POST['button_url_1'] = get_url($_POST['button_url_1'] ?? $personal_notification->settings->button_url_1, 512);
        $_POST['button_title_2'] = input_clean($_POST['button_title_2'] ?? $personal_notification->settings->button_title_2, 16);
        $_POST['button_url_2'] = get_url($_POST['button_url_2'] ?? $personal_notification->settings->button_url_2, 512);

        /* UTM */
        $_POST['utm_medium'] = input_clean($_POST['utm_medium'] ?? $personal_notification->settings->utm->medium, 128);
        $_POST['utm_source'] = input_clean($_POST['utm_source'] ?? $personal_notification->settings->utm->source, 128);
        $_POST['utm_campaign'] = input_clean($_POST['utm_campaign'] ?? $personal_notification->settings->utm->campaign, 128);

        /* Get the subscriber */
        $subscriber = (new Subscriber())->get_subscriber_by_subscriber_id($_POST['subscriber_id']);

        /* Make sure subscriber exists properly */
        if(!$subscriber || $subscriber->website_id != $_POST['website_id']) {
            $this->response_error(l('personal_notifications.error_message.subscriber_id'), 401);
        }

        /* Status of the notification */
        if($personal_notification->status != 'sent') {
            $status = $_POST['is_scheduled'] && $_POST['scheduled_datetime'] ? 'scheduled' : 'processing';
            if(isset($_POST['save'])) {
                $status = 'draft';
            }

            if($status != 'draft') {
                /* Check for the plan limit */
                $sent_push_notifications_current_month = db()->where('user_id', $this->api_user->user_id)->getValue('users', '`pusher_sent_push_notifications_current_month`');
                if($this->api_user->plan_settings->sent_push_notifications_per_month_limit != -1 && $sent_push_notifications_current_month + 1 > $this->api_user->plan_settings->sent_push_notifications_per_month_limit) {
                    $this->response_error(l('global.info_message.plan_feature_limit'), 401);
                }
            }
        }

        /* Uploaded image */
        $image = \Altum\Uploads::process_upload($personal_notification->image, 'websites_personal_notifications_images', 'image', 'image_remove', settings()->websites->personal_notification_images_limit, 'json_error');

        $settings = [
            /* Scheduling */
            'is_scheduled' => $_POST['is_scheduled'],

            /* Advanced */
            'ttl' => $_POST['ttl'],
            'urgency' => $_POST['urgency'],
            'is_silent' => $_POST['is_silent'],
            'is_auto_hide' => $_POST['is_auto_hide'],

            /* Buttons */
            'button_title_1' => $_POST['button_title_1'],
            'button_url_1' => $_POST['button_url_1'],
            'button_title_2' => $_POST['button_title_2'],
            'button_url_2' => $_POST['button_url_2'],

            /* UTM */
            'utm' => [
                'source' => $_POST['utm_source'],
                'medium' => $_POST['utm_medium'],
                'campaign' => $_POST['utm_campaign'],
            ]
        ];

        /* Database query */
        db()->where('personal_notification_id'. $personal_notification->personal_notification_id)->update('personal_notifications', [
            'website_id' => $_POST['website_id'],
            'subscriber_id' => $_POST['subscriber_id'],
            'user_id' => $this->api_user->user_id,
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image' => $image,
            'settings' => json_encode($settings),
            'status' => $status,
            'scheduled_datetime' => $_POST['scheduled_datetime'],
            'datetime' => get_date(),
        ]);

        /* Sent the notification now if requested */
        if($status == 'processing') {
            $website = $websites[$_POST['website_id']];
            $is_sent = 1;
            $status = 'sent';
            $sent_datetime = get_date();

            /* Process UTM parameters */
            $_POST['url'] = process_utm_parameters((object) $settings['utm'], $_POST['url']);
            $_POST['button_url_1'] = process_utm_parameters((object) $settings['utm'], $_POST['button_url_1']);
            $_POST['button_url_2'] = process_utm_parameters((object) $settings['utm'], $_POST['button_url_2']);

            /* Send web push */
            process_push_notification([
                'website' => $website,
                'subscriber' => $subscriber,

                'source_id' => 'personal_notification_id',
                'source_value' => $personal_notification_id,

                'web_push_data' => [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'url' => $_POST['url'],
                    'is_silent' => $_POST['is_silent'],
                    'is_auto_hide' => $_POST['is_auto_hide'],
                    'button_title_1' => $_POST['button_title_1'],
                    'button_url_1' => $_POST['button_url_1'],
                    'button_title_2' => $_POST['button_title_2'],
                    'button_url_2' => $_POST['button_url_2'],
                    'image' => $image ? \Altum\Uploads::get_full_url('websites_personal_notifications_images') . $image : null,
                    'content' => [
                        'source_type' => 'personal_notification_id',
                        'personal_notification_id' => $personal_notification_id,
                    ]
                ]
            ], function() use ($personal_notification_id, $is_sent, $status, $sent_datetime) {
                /* Update the personal notification */
                db()->where('personal_notification_id', $personal_notification_id)->update('personal_notifications', [
                    'is_sent' => $is_sent,
                    'status' => $status,
                    'sent_datetime' => $sent_datetime,
                ]);
            });
        }

        /* Prepare the data */
        $data = [
            'id' => $personal_notification->personal_notification_id,
            'user_id' => (int) $personal_notification->user_id,
            'website_id' => $_POST['website_id'],
            'subscriber_id' => $_POST['subscriber_id'],
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_url' => $image ? \Altum\Uploads::get_full_url('websites_personal_notifications_images') . $image : null,
            'settings' => $settings,
            'is_sent' => $is_sent ?? 0,
            'is_displayed' => (int) $personal_notification->is_displayed,
            'is_clicked' => (int) $personal_notification->is_clicked,
            'is_closed' => (int) $personal_notification->is_closed,
            'status' => $status,
            'scheduled_datetime' => $_POST['scheduled_datetime'],
            'sent_datetime' => $sent_datetime ?? null,
            'last_datetime' => get_date(),
            'datetime' => $personal_notification->datetime,
        ];

        Response::jsonapi_success($data, null, 200);

    }

    private function delete() {

        $personal_notification_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $personal_notification = db()->where('personal_notification_id', $personal_notification_id)->where('user_id', $this->api_user->user_id)->getOne('personal_notifications');

        /* We haven't found the resource */
        if(!$personal_notification) {
            $this->return_404();
        }

        /* Delete the resource */
        (new PersonalNotification())->delete($personal_notification_id);

        http_response_code(200);
        die();

    }
}
