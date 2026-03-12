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

use Altum\Alerts;
use Altum\Date;
use Altum\Models\Subscriber;

defined('ALTUMCODE') || die();

class PersonalNotificationUpdate extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.personal_notifications')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('personal-notifications');
        }

        /* Check for the plan limit */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `personal_notifications` WHERE `user_id` = {$this->user->user_id}")->fetch_object()->total ?? 0;
        if($this->user->plan_settings->personal_notifications_limit != -1 && $total_rows > $this->user->plan_settings->personal_notifications_limit) {
            redirect('personal-notifications');
        }

        $personal_notification_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$personal_notification = db()->where('personal_notification_id', $personal_notification_id)->where('user_id', $this->user->user_id)->getOne('personal_notifications')) {
            redirect('personal-notifications');
        }

        $personal_notification->settings = json_decode($personal_notification->settings ?? '');

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->user->user_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        if(!empty($_POST)) {
            /* Filter some of the variables */
            $_POST['name'] = input_clean($_POST['name'], 256);
            $_POST['title'] = input_clean($_POST['title'], 64);
            $_POST['description'] = input_clean($_POST['description'], 128);
            $_POST['url'] = input_clean($_POST['url'], 512);
            $_POST['website_id'] = array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : array_key_first($websites);
            $_POST['subscriber_id'] = (int) $_POST['subscriber_id'];

            /* Scheduling */
            $_POST['is_scheduled'] = (int) isset($_POST['is_scheduled']);
            $_POST['scheduled_datetime'] = $_POST['is_scheduled'] && !empty($_POST['scheduled_datetime']) && Date::validate($_POST['scheduled_datetime'], 'Y-m-d H:i:s') ?
                (new \DateTime($_POST['scheduled_datetime'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s')
                : get_date();

            /* Advanced */
            $_POST['ttl'] = isset($_POST['ttl']) && array_key_exists($_POST['ttl'], $notifications_ttl) ? (int) $_POST['ttl'] : array_key_last($notifications_ttl);
            $_POST['urgency'] = isset($_POST['urgency']) && in_array($_POST['urgency'], ['low', 'normal', 'high']) ? $_POST['urgency'] : 'normal';
            $_POST['is_silent'] = (int) isset($_POST['is_silent']);
            $_POST['is_auto_hide'] = (int) isset($_POST['is_auto_hide']);

            /* Buttons */
            $_POST['button_title_1'] = input_clean($_POST['button_title_1'], 16);
            $_POST['button_url_1'] = get_url($_POST['button_url_1'], 512);
            $_POST['button_title_2'] = input_clean($_POST['button_title_2'], 16);
            $_POST['button_url_2'] = get_url($_POST['button_url_2'], 512);

            /* UTM */
            $_POST['utm_medium'] = input_clean($_POST['utm_medium'], 128);
            $_POST['utm_source'] = input_clean($_POST['utm_source'], 128);
            $_POST['utm_campaign'] = input_clean($_POST['utm_campaign'], 128);

            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

            /* Check for any errors */
            $required_fields = $personal_notification->status == 'sent' ? ['name'] : ['subscriber_id', 'name', 'title', 'description'];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    Alerts::add_field_error($field, l('global.error_message.empty_field'));
                }
            }

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            /* Get the subscriber */
            $subscriber = (new Subscriber())->get_subscriber_by_subscriber_id($_POST['subscriber_id']);

            /* Make sure subscriber exists properly */
            if(!$subscriber || $subscriber->website_id != $_POST['website_id']) {
                Alerts::add_field_error('subscriber_id', l('personal_notifications.error_message.subscriber_id'));
            }

            /* Status of the notification */
            if($personal_notification->status != 'sent') {
                $status = $_POST['is_scheduled'] && $_POST['scheduled_datetime'] ? 'scheduled' : 'processing';
                if(isset($_POST['save'])) {
                    $status = 'draft';
                }

                if($status != 'draft') {
                    /* Check for the plan limit */
                    $sent_push_notifications_current_month = db()->where('user_id', $this->user->user_id)->getValue('users', '`pusher_sent_push_notifications_current_month`');
                    if($this->user->plan_settings->sent_push_notifications_per_month_limit != -1 && $sent_push_notifications_current_month + 1 > $this->user->plan_settings->sent_push_notifications_per_month_limit) {
                        Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
                    }
                }
            }

            /* Uploaded image */
            $image = \Altum\Uploads::process_upload($personal_notification->image, 'websites_personal_notifications_images', 'image', 'image_remove', settings()->websites->personal_notification_image_size_limit);

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

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                if($personal_notification->status == 'sent') {
                    /* Database query */
                    db()->where('personal_notification_id', $personal_notification->personal_notification_id)->update('personal_notifications', [
                        'name' => $_POST['name'],
                        'last_datetime' => get_date(),
                    ]);
                }

                else {
                    /* Database query */
                    db()->where('personal_notification_id', $personal_notification->personal_notification_id)->update('personal_notifications', [
                        'website_id' => $_POST['website_id'],
                        'subscriber_id' => $_POST['subscriber_id'],
                        'name' => $_POST['name'],
                        'title' => $_POST['title'],
                        'description' => $_POST['description'],
                        'url' => $_POST['url'],
                        'image' => $image,
                        'settings' => json_encode($settings),
                        'status' => $status,
                        'scheduled_datetime' => $_POST['scheduled_datetime'],
                        'last_datetime' => get_date(),
                    ]);
                }

                /* Sent the notification now if requested */
                if($status == 'processing') {
                    $website = $websites[$_POST['website_id']];

                    /* Process UTM parameters */
                    $_POST['url'] = process_utm_parameters((object) $settings['utm'], $_POST['url']);
                    $_POST['button_url_1'] = process_utm_parameters((object) $settings['utm'], $_POST['button_url_1']);
                    $_POST['button_url_2'] = process_utm_parameters((object) $settings['utm'], $_POST['button_url_2']);

                    /* Prepare the web push */
                    $web_push = initiate_web_push($website->keys->public_key, $website->keys->private_key);

                    /* Prepare the push data */
                    $push_notification_title = html_entity_decode($_POST['title'], ENT_QUOTES, 'UTF-8');
                    $push_notification_description = html_entity_decode($_POST['description'], ENT_QUOTES, 'UTF-8');

                    /* Web push content */
                    $content = [
                        'title' => $push_notification_title,
                        'description' => $push_notification_description,
                        'url' => $_POST['url'],
                        'is_silent' => $_POST['is_silent'],
                        'is_auto_hide' => $_POST['is_auto_hide'],
                    ];

                    /* Buttons */
                    if($_POST['button_title_1']) {
                        $content['button_title_1'] = $_POST['button_title_1'];
                        $content['button_url_1'] = $_POST['button_url_1'];
                    }

                    if($_POST['button_title_2']) {
                        $content['button_title_2'] = $_POST['button_title_2'];
                        $content['button_url_2'] = $_POST['button_url_2'];
                    }

                    /* Add the icon & badge of the site to the notification */
                    if($website->settings->icon) {
                        $content['icon'] = \Altum\Uploads::get_full_url('websites_icons') . $website->settings->icon;
                        $content['badge'] = \Altum\Uploads::get_full_url('websites_icons') . $website->settings->icon;
                    }

                    /* Hero image */
                    if($image) {
                        $content['image'] = \Altum\Uploads::get_full_url('websites_personal_notifications_images') . $image;
                    }

                    /* Set subscriber data */
                    $subscriber_push_data = [
                        'endpoint' => $subscriber->endpoint,
                        'expirationTime' => null,
                        'keys' => json_decode($subscriber->keys, true)
                    ];

                    /* Add extra data to the push */
                    $content['subscriber_id'] = $subscriber->subscriber_id;
                    $content['pixel_key'] = $website->pixel_key;
                    $content['source_type'] = 'personal_notification_id';
                    $content['personal_notification_id'] = $personal_notification_id;

                    /* Dynamic variables processing */
                    $replacers = [
                        '{{CONTINENT_NAME}}' => get_continent_from_continent_code($subscriber->continent_code),
                        '{{COUNTRY_NAME}}' => get_country_from_country_code($subscriber->country_code),
                        '{{CITY_NAME}}' => $subscriber->city_name,
                        '{{DEVICE_TYPE}}' => l('global.device.' . $subscriber->device_type),
                        '{{OS_NAME}}' => $subscriber->os_name,
                        '{{BROWSER_NAME}}' => $subscriber->browser_name,
                        '{{BROWSER_LANGUAGE}}' => get_language_from_locale($subscriber->browser_language),
                    ];

                    /* Custom parameters */
                    foreach($subscriber->custom_parameters as $key => $value) {
                        $replacers['{{CUSTOM_PARAMETERS:' . $key . '}}'] = $value;
                    }

                    foreach(['title', 'description', 'url', 'button_title_1', 'button_url_1', 'button_title_2', 'button_url_2'] as $key) {
                        if(!empty($content[$key])) {
                            $content[$key] = str_replace(
                                array_keys($replacers),
                                array_values($replacers),
                                $content[$key]
                            );

                            /* Process spintax */
                            $content[$key] = process_spintax($content[$key]);
                        }
                    }

                    /* Send push */
                    $response = $web_push->sendOneNotification(
                        \Minishlink\WebPush\Subscription::create($subscriber_push_data),
                        json_encode($content),
                        [
                            'TTL' => $_POST['ttl'] ?? array_key_last($notifications_ttl),
                            'urgency' => str_replace('_', '-', $_POST['urgency'] ?? 'normal'),
                        ]
                    );

                    $response_status_code = $response->getResponse()->getStatusCode();

                    /* Log successful request */
                    if(in_array($response_status_code, [200, 201, 202])) {
                        /* Database query */
                        db()->where('subscriber_id', $subscriber->subscriber_id)->update('subscribers', [
                            'total_sent_push_notifications' => db()->inc(),
                            'last_sent_datetime' => get_date(),
                        ]);

                        /* Insert subscriber log */
                        db()->insert('subscribers_logs', [
                            'subscriber_id' => $subscriber->subscriber_id,
                            'personal_notification_id' => $personal_notification_id,
                            'website_id' => $subscriber->website_id,
                            'user_id' => $website->user_id,
                            'ip' => $subscriber->ip,
                            'type' => 'push_notification_sent',
                            'datetime' => get_date(),
                        ]);
                    }

                    /* Unsubscribe if push failed */
                    if($response_status_code == 410) {
                        /* Database query */
                        db()->where('subscriber_id', $subscriber->subscriber_id)->delete('subscribers');

                        /* Update website statistics */
                        if(db()->count) {
                            db()->where('website_id', $website->website_id)->update('websites', ['total_subscribers' => db()->dec()]);

                            /* Clear the cache */
                            cache()->deleteItem('subscribers_total?user_id=' . $website->user_id);
                            cache()->deleteItem('subscribers_dashboard?user_id=' . $website->user_id);
                        }

                        /* Insert subscriber log */
                        db()->insert('subscribers_logs', [
                            'website_id' => $subscriber->website_id,
                            'user_id' => $website->user_id,
                            'ip' => preg_replace('/\d/', '*', $subscriber->ip),
                            'type' => 'expired_deleted',
                            'datetime' => get_date(),
                        ]);
                    }

                    /* Other potential errors */
                    if($response_status_code >= 400 && $response_status_code != 410) {
                        $error = json_encode([
                            'code' => $response_status_code,
                            'message' => trim($response->getResponse()->getReasonPhrase()),
                        ]);

                        /* Insert subscriber log */
                        db()->insert('subscribers_logs', [
                            'subscriber_id' => $subscriber->subscriber_id,
                            'personal_notification_id' => $personal_notification_id,
                            'website_id' => $subscriber->website_id,
                            'user_id' => $website->user_id,
                            'ip' => $subscriber->ip,
                            'type' => 'push_notification_failed',
                            'error' => $error,
                            'datetime' => get_date(),
                        ]);
                    }

                    /* Update the personal notification */
                    db()->where('personal_notification_id', $personal_notification_id)->update('personal_notifications', [
                        'is_sent' => 1,
                        'status' => 'sent',
                        'sent_datetime' => get_date(),
                    ]);

                    /* Update the main website */
                    db()->where('website_id', $_POST['website_id'])->update('websites', [
                        'total_sent_push_notifications' => db()->inc(),
                    ]);

                    /* Update the user */
                    db()->where('user_id', $this->user->user_id)->update('users', [
                        'pusher_sent_push_notifications_current_month' => db()->inc(),
                        'pusher_total_sent_push_notifications' => db()->inc(),
                    ]);

                    /* Clear the cache */
                    cache()->deleteItem('total_sent_push_notifications_total?user_id=' . $this->user->user_id);
                }

                if(isset($_POST['save'])) {
                    /* Set a nice success message */
                    Alerts::add_success(sprintf(l('personal_notifications.success_message.save'), '<strong>' . $_POST['name'] . '</strong>'));
                } else {
                    /* Set a nice success message */
                    if($_POST['is_scheduled']) {
                        Alerts::add_success(sprintf(l('personal_notifications.success_message.scheduled'), '<strong>' . $_POST['name'] . '</strong>', '<strong>' . \Altum\Date::get_time_until($_POST['scheduled_datetime']) . '</strong>'));
                    } else {
                        Alerts::add_success(sprintf(l('personal_notifications.success_message.sent'), '<strong>' . $_POST['name'] . '</strong>'));
                    }
                }

                /* Refresh the page */
                redirect('personal-notification-update/' . $personal_notification_id);
            }
        }

        /* Prepare the view */
        $data = [
            'personal_notification' => $personal_notification,
            'websites' => $websites,
            'notifications_ttl' => $notifications_ttl,
        ];

        $view = new \Altum\View('personal-notification-update/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
