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

use Altum\Models\Subscriber;
use Altum\Models\User;

defined('ALTUMCODE') || die();

class PixelTrack extends Controller {

    public function index() {

        /* Get the Payload of the Post */
        $payload = @file_get_contents('php://input');
        $_POST = json_decode($payload, true);

        /* Check for any errors */
        $required_fields = ['type'];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                redirect();
            }
        }

        $allowed_types = [
            /* Subscriber */
            'create',
            'delete',

            /* Notifications */
            'displayed_notification',
            'clicked_notification',
            'closed_notification',

            /* Permissions */
            'permission_denied',
        ];

        if(!in_array($_POST['type'], $allowed_types)) {
            die(settings()->main->title . ' (' . SITE_URL . '): Provided type not allowed.');
        }

        $pixel_key = isset($this->params[0]) ? input_clean($this->params[0]) : null;

        /* Get the details of the website from the database */
        $website = (new \Altum\Models\Website())->get_website_by_pixel_key($pixel_key);

        /* Make sure the website has access */
        if(!$website) {
            die(settings()->main->title . ' (' . SITE_URL . '): No website found for this pixel.');
        }

        if(!$website->is_enabled) {
            die(settings()->main->title . ' (' . SITE_URL . '): Website disabled.');
        }

        /* Make sure to get the user data and confirm the user is ok */
        $user = (new \Altum\Models\User())->get_user_by_user_id($website->user_id);

        if(!$user) {
            die("console.log('" . settings()->main->title . " (" . SITE_URL. "): Website owner not found.')");
        }

        if($user->status != 1) {
            die("console.log('" . settings()->main->title . " (" . SITE_URL. "): Website owner is disabled.')");
        }

        /* Check for a custom domain */
        if(isset(\Altum\Router::$data['domain']) && $website->domain_id != \Altum\Router::$data['domain']->domain_id) {
            die("console.log('" . settings()->main->title . " (" . SITE_URL. "): Domain id mismatch.')");
        }

        /* Process the plan of the user */
        (new User())->process_user_plan_expiration_by_user($user);

        /* Create and Delete handlers */
        if(in_array($_POST['type'], ['create', 'delete'])) {
            /* Check for any errors */
            $required_fields = ['url', 'endpoint', 'p256dh', 'auth'];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    redirect();
                }
            }

            /* Parse the data */
            $_POST['endpoint'] = get_url($_POST['endpoint']);
            $_POST['url'] = parse_url($_POST['url'], PHP_URL_HOST) == $website->host ? input_clean($_POST['url'], 2048) : null;
            $unique_endpoint_id = md5($_POST['endpoint']);
            $keys = json_encode([
                'p256dh' => $_POST['p256dh'],
                'auth' => $_POST['auth'],
            ]);

            /* Make sure only whitelisted endpoints are accepted */
            $endpoint = parse_url($_POST['endpoint']);
            $whitelisted_hosts = [
                'android.googleapis.com',
                'fcm.googleapis.com',
                'updates.push.services.mozilla.com',
                'updates-autopush.stage.mozaws.net',
                'updates-autopush.dev.mozaws.net',
                'notify.windows.com',
                'push.apple.com',
                'in-vcm-api.vivoglobal.com',
            ];

            $accepted = false;
            foreach($whitelisted_hosts as $whitelisted_host) {
                if(string_ends_with($whitelisted_host, $endpoint['host'])) {
                    $accepted = true;
                }
            }

            if(!$accepted) {
                die("console.log('" . settings()->main->title . " (" . SITE_URL . "): Endpoint not allowed.')");
            }
        }

        $ip = get_ip();
        $original_ip = $ip;

        /* Check if we can save the real IP or not */
        $ip = $website->settings->ip_storage_is_enabled ? $ip : preg_replace('/\d/', '*', $ip);

        switch($_POST['type']) {
            case 'create':

                /* Check for the plan limit */
                $websites = (new \Altum\Models\Website())->get_websites_by_user_id($user->user_id);
                $total_subscribers = 0;
                foreach($websites as $row) { $total_subscribers += $row->total_subscribers; }
                if($user->plan_settings->subscribers_limit != -1 && $total_subscribers >= $user->plan_settings->subscribers_limit) {
                    die("console.log('" . settings()->main->title . " (" . SITE_URL . "): Subscribers limit reached.')");
                }

                /* Detect the location */
                try {
                    $maxmind = (get_maxmind_reader_city())->get($original_ip);
                } catch(\Exception $exception) {
                    /* :) */
                }
                $continent_code = isset($maxmind) && isset($maxmind['continent']) ? $maxmind['continent']['code'] : null;
                $country_code = isset($maxmind) && isset($maxmind['country']) ? $maxmind['country']['iso_code'] : null;
                $city_name = isset($maxmind) && isset($maxmind['city']) ? $maxmind['city']['names']['en'] : null;

                /* Detect extra details about the user */
                $whichbrowser = get_whichbrowser();
                $browser_name = $whichbrowser->browser->name ?? null;
                $os_name = $whichbrowser->os->name ?? null;
                $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
                $device_type = get_this_device_type();

                /* Check for custom parameters */
                $custom_parameters = [];

                if(isset($_POST['custom_parameters'])) {
                    $i = 1;
                    foreach((array) $_POST['custom_parameters'] as $key => $value) {
                        $key = input_clean($key, '64');
                        $value = input_clean($value, '512');

                        if($i++ >= 10) {
                            break;
                        } else {
                            $custom_parameters[$key] = $value;
                        }
                    }
                }

                /* Insert / update */
                $subscriber_id = db()->onDuplicate([
                    'endpoint', 'keys',
                ])->insert('subscribers', [
                    'website_id' => $website->website_id,
                    'user_id' => $website->user_id,
                    'unique_endpoint_id' => $unique_endpoint_id,
                    'endpoint' => $_POST['endpoint'],
                    'keys' => $keys,
                    'ip' => $ip,
                    'custom_parameters' => json_encode($custom_parameters),
                    'city_name' => $city_name,
                    'country_code' => $country_code,
                    'continent_code' => $continent_code,
                    'os_name' => $os_name,
                    'browser_name' => $browser_name,
                    'browser_language' => $browser_language,
                    'device_type' => $device_type,
                    'subscribed_on_url' => $_POST['url'],
                    'datetime' => get_date(),
                ]);

                /* Update website statistics */
                if(db()->count == 1) {
                    db()->where('website_id', $website->website_id)->update('websites', ['total_subscribers' => db()->inc()]);

                    /* Clear the cache */
                    cache()->deleteItem('subscribers_total?user_id=' . $website->user_id);
                    cache()->deleteItem('subscribers_dashboard?user_id=' . $website->user_id);

                    /* Processing the notification handlers */
                    if(count($website->notifications ?? [])) {
                        /* Fetch user-level notification handlers */
                        $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($user->user_id);

                        /* Core data to be sent to the new processor */
                        $notification_data = [
                            'website_id'        => $website->website_id,
                            'url'               => url('subscriber/' . $subscriber_id),
                            'ip'                => $ip,
                            'subscribed_on_url' => $_POST['url'],
                            'city_name'         => $city_name,
                            'country_code'      => $country_code,
                            'continent_code'    => $continent_code,
                            'os_name'           => $os_name,
                            'browser_name'      => $browser_name,
                            'browser_language'  => $browser_language,
                            'device_type'       => $device_type,
                        ];

                        /* Build a plain caught-data string for the generic message */
                        $dynamic_message_data = \Altum\NotificationHandlers::build_dynamic_message_data($notification_data);

                        /* Compose the generic notification text */
                        $notification_message = sprintf(
                            l('websites.simple_notification', $user->language),
                            $website->name,
                            $website->scheme . $website->host . $website->path,
                            $dynamic_message_data,
                            $notification_data['url']
                        );

                        /* Prepare the email template used by the email handler */
                        $email_template = get_email_template(
                            [
                                '{{WEBSITE_NAME}}' => $website->name,
                            ],
                            l('global.emails.user_new_subscriber.subject', $user->language),
                            [
                                '{{SUBSCRIBER_IP}}' => $ip,
                                '{{WEBSITE_NAME}}'  => $website->name,
                                '{{WEBSITE_URL}}'   => $website->scheme . $website->host . $website->path,
                                '{{DATA}}'          => str_replace("\r\n", "<br />", $dynamic_message_data),
                            ],
                            l('global.emails.user_new_subscriber.body', $user->language)
                        );

                        /* Build the context passed to the new NotificationHandlers class */
                        $context = [
                            /* User details */
                            'user'                 => $user,

                            /* Email */
                            'email_template'       => $email_template,

                            /* Basic message for most integrations */
                            'message'              => $notification_message,

                            /* Push notifications */
                            'push_title'           => l('websites.push_notification.title', $user->language),
                            'push_description'     => sprintf(
                                l('websites.push_notification.description', $user->language),
                                $website->name,
                                $website->scheme . $website->host . $website->path
                            ),

                            /* Whatsapp */
                            'whatsapp_template'    => 'new_subscriber',
                            'whatsapp_parameters'  => [
                                $website->name,
                                $website->scheme . $website->host . $website->path,
                                $notification_data['url'],
                            ],

                            /* Twilio call */
                            'twilio_call_url'      => SITE_URL .
                                'twiml/websites.simple_notification?param1=' .
                                urlencode($website->name) .
                                '&param2=' . urlencode($website->scheme . $website->host . $website->path) .
                                '&param3=&param4=' . urlencode($notification_data['url']),

                            /* Internal notification */
                            'internal_icon'        => 'fas fa-user-check',

                            /* Discord */
                            'discord_color'        => '2664261',

                            /* Slack */
                            'slack_emoji'          => ':large_green_circle:',
                        ];

                        /* Send notifications */
                        \Altum\NotificationHandlers::process(
                            $notification_handlers,
                            $website->notifications,
                            $notification_data,
                            $context
                        );
                    }
                }

                /* Update/resub on an already subscribed user */
                else {
                    /* Insert subscriber log */
                    db()->insert('subscribers_logs', [
                        'website_id' => $website->website_id,
                        'user_id' => $website->user_id,
                        'type' => 'unsubscribed',
                        'ip' => $ip,
                        'datetime' => get_date(),
                    ]);
                }

                /* Insert subscriber log */
                db()->insert('subscribers_logs', [
                    'subscriber_id' => $subscriber_id,
                    'website_id' => $website->website_id,
                    'user_id' => $website->user_id,
                    'ip' => $ip,
                    'type' => 'subscribed',
                    'datetime' => get_date(),
                ]);

                break;

            case 'delete':

                /* Delete subscriber */
                db()->where('unique_endpoint_id', $unique_endpoint_id)->delete('subscribers');

                /* Update website statistics */
                if(db()->count) {
                    db()->where('website_id', $website->website_id)->update('websites', ['total_subscribers' => db()->dec()]);

                    /* Clear the cache */
                    cache()->deleteItem('subscribers_total?user_id=' . $website->user_id);
                    cache()->deleteItem('subscribers_dashboard?user_id=' . $website->user_id);
                }

                /* Insert subscriber log */
                db()->insert('subscribers_logs', [
                    'website_id' => $website->website_id,
                    'user_id' => $website->user_id,
                    'type' => 'unsubscribed',
                    'ip' => $ip,
                    'datetime' => get_date(),
                ]);

                break;

            case 'displayed_notification':
            case 'clicked_notification':
            case 'closed_notification':

                /* Only track those stats if the user has the right plan settings */
                if(!$user->plan_settings->analytics_is_enabled) {
                    break;
                }

                /* Check for any errors */
                $required_fields = ['subscriber_id',];
                foreach($required_fields as $field) {
                    if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                        redirect();
                    }
                }

                $subscriber_id = (int) $_POST['subscriber_id'];

                /* Get the subscriber */
                $subscriber = (new Subscriber())->get_subscriber_by_subscriber_id($subscriber_id);

                /* Campaign tracking log */
                if(isset($_POST['campaign_id'])) {
                    $campaign_id = (int) $_POST['campaign_id'];
                    $rss_automation_id = isset($_POST['rss_automation_id']) ? (int) $_POST['rss_automation_id'] : null;
                    $recurring_campaign_id = isset($_POST['recurring_campaign_id']) ? (int) $_POST['recurring_campaign_id'] : null;

                    /* Insert subscriber log */
                    db()->insert('subscribers_logs', [
                        'subscriber_id' => $subscriber->subscriber_id,
                        'website_id' => $website->website_id,
                        'user_id' => $website->user_id,
                        'campaign_id' => $campaign_id,
                        'rss_automation_id' => $rss_automation_id,
                        'recurring_campaign_id' => $recurring_campaign_id,
                        'type' => $_POST['type'],
                        'ip' => $ip,
                        'datetime' => get_date(),
                    ]);

                    /* More stats recording */
                    $stat_table_column = match ($_POST['type']) {
                        'displayed_notification' => 'total_displayed_push_notifications',
                        'clicked_notification' => 'total_clicked_push_notifications',
                        'closed_notification' => 'total_closed_push_notifications',
                    };

                    /* Update campaign statistics */
                    db()->where('campaign_id', $campaign_id)->update('campaigns', [$stat_table_column => db()->inc()]);

                    /* Update the RSS automation statistics */
                    if($rss_automation_id) {
                        db()->where('rss_automation_id', $rss_automation_id)->update('rss_automations', [$stat_table_column => db()->inc()]);
                    }

                    /* Update the RSS automation statistics */
                    if($recurring_campaign_id) {
                        db()->where('recurring_campaign_id', $recurring_campaign_id)->update('recurring_campaigns', [$stat_table_column => db()->inc()]);
                    }
                }

                /* Flow tracking log */
                else if(isset($_POST['flow_id'])) {
                    $flow_id = (int) $_POST['flow_id'];

                    /* Insert subscriber log */
                    db()->insert('subscribers_logs', [
                        'subscriber_id' => $subscriber->subscriber_id,
                        'website_id' => $website->website_id,
                        'user_id' => $website->user_id,
                        'flow_id' => $flow_id,
                        'type' => $_POST['type'],
                        'ip' => $ip,
                        'datetime' => get_date(),
                    ]);

                    /* More stats recording */
                    $stat_table_column = match ($_POST['type']) {
                        'displayed_notification' => 'total_displayed_push_notifications',
                        'clicked_notification' => 'total_clicked_push_notifications',
                        'closed_notification' => 'total_closed_push_notifications',
                    };

                    /* Update campaign statistics */
                    db()->where('flow_id', $flow_id)->update('flows', [$stat_table_column => db()->inc()]);
                }

                /* Personal notification tracking log */
                else if(isset($_POST['personal_notification_id'])) {
                    $personal_notification_id = (int) $_POST['personal_notification_id'];

                    /* Insert subscriber log */
                    db()->insert('subscribers_logs', [
                        'subscriber_id' => $subscriber->subscriber_id,
                        'website_id' => $website->website_id,
                        'user_id' => $website->user_id,
                        'personal_notification_id' => $personal_notification_id,
                        'type' => $_POST['type'],
                        'ip' => $ip,
                        'datetime' => get_date(),
                    ]);

                    $stat_table_column = match ($_POST['type']) {
                        'displayed_notification' => 'is_displayed',
                        'clicked_notification' => 'is_clicked',
                        'closed_notification' => 'is_closed',
                    };

                    /* Update personal notification */
                    db()->where('personal_notification_id', $personal_notification_id)->update('personal_notifications', [$stat_table_column => 1]);

                    /* Stats table for the rest of updates */
                    $stat_table_column = match ($_POST['type']) {
                        'displayed_notification' => 'total_displayed_push_notifications',
                        'clicked_notification' => 'total_clicked_push_notifications',
                        'closed_notification' => 'total_closed_push_notifications',
                    };
                }

                /* Missing parameters */
                else {
                    redirect();
                }

                /* Update subscriber statistics */
                db()->where('subscriber_id', $subscriber->subscriber_id)->update('subscribers', [$stat_table_column => db()->inc()]);

                /* Update website statistics */
                db()->where('website_id', $website->website_id)->update('websites', [$stat_table_column => db()->inc()]);

                break;

            case 'permission_denied':

                /* Only track those stats if the user has the right plan settings */
                if(!$user->plan_settings->analytics_is_enabled) {
                    break;
                }

                /* Insert subscriber log */
                db()->insert('subscribers_logs', [
                    'website_id' => $website->website_id,
                    'user_id' => $website->user_id,
                    'type' => $_POST['type'],
                    'ip' => $ip,
                    'datetime' => get_date(),
                ]);

                break;
        }

    }

}
