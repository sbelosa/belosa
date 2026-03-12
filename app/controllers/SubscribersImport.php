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

defined('ALTUMCODE') || die();

class SubscribersImport extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.subscribers')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('subscribers');
        }

        /* Check for the plan limit */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `subscribers` WHERE `user_id` = {$this->user->user_id}")->fetch_object()->total ?? 0;

        if($this->user->plan_settings->subscribers_limit != -1 && $total_rows >= $this->user->plan_settings->subscribers_limit) {
            Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
            redirect('subscribers');
        }

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->user->user_id);

        if(!empty($_POST)) {
            $_POST['website_id'] = array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : array_key_first($websites);

            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

            /* Check for any errors */
            $required_fields = [];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    Alerts::add_field_error($field, l('global.error_message.empty_field'));
                }
            }

            if(!isset($_FILES['file'])) {
                Alerts::add_error(l('global.error_message.empty_field'));
            }

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            /* Uploaded file */
            \Altum\Uploads::validate_upload('websites_subscribers_csv', 'file', get_max_upload());

            /* Parse csv */
            $csv_array = array_map(function($csv_line) {
                return str_getcsv($csv_line, ',', '"', '\\');
            }, file($_FILES['file']['tmp_name']));

            if(!$csv_array || !is_array($csv_array)) {
                Alerts::add_error(l('global.error_message.invalid_file_type'));
            }

            $headers_array = $csv_array[0];
            unset($csv_array[0]);
            reset($csv_array);

            /* Detect custom_parameters keys in the CSV headers */
            $custom_parameters_keys = [];
            foreach($headers_array as $header_index => $header_value) {
                if(preg_match('/^custom_parameters\[(.*?)\]$/', $header_value, $matches)) {
                    $custom_parameters_keys[$header_index] = input_clean($matches[1], 64);
                }
            }

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                $website = $websites[$_POST['website_id']];
                $imported_subscribers = 0;

                /* Go over each row */
                foreach($csv_array as $key => $csv_row) {
                    if(count($headers_array) != count($csv_row)) {
                        continue;
                    }

                    /* Required fields */
                    $array_key = array_search('endpoint', $headers_array);
                    if($array_key === false) {
                        continue;
                    }
                    $endpoint = input_clean($csv_row[$array_key], 512);

                    $array_key = array_search('p256dh', $headers_array);
                    if($array_key === false) {
                        continue;
                    }
                    $p256dh = input_clean($csv_row[$array_key], 512);

                    $array_key = array_search('auth', $headers_array);
                    if($array_key === false) {
                        continue;
                    }
                    $auth = input_clean($csv_row[$array_key], 512);

                    /* Generate the keys */
                    $endpoint = get_url($endpoint);
                    $unique_endpoint_id = md5($endpoint);
                    $keys = json_encode([
                        'p256dh' => $p256dh,
                        'auth' => $auth,
                    ]);

                    /* Make sure only whitelisted endpoints are accepted */
                    $endpoint_parsed = parse_url($endpoint);
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
                        if(string_ends_with($whitelisted_host, $endpoint_parsed['host'] ?? '')) {
                            $accepted = true;
                        }
                    }

                    if(!$accepted) {
                        continue;
                    }

                    /* Ip */
                    $array_key = array_search('ip', $headers_array);
                    $ip = input_clean($csv_row[$array_key] ?? '**.***.***.*', 64);
                    $ip = $website->settings->ip_storage_is_enabled ? $ip : preg_replace('/\d/', '*', $ip);

                    /* Date for insertion */
                    $datetime = get_date();
                    if($array_key = array_search('datetime', $headers_array)) {
                        try {
                            $datetime = (new \DateTime($csv_row[$array_key]))->format('Y-m-d H:i:s');
                        } catch (\Exception $exception) {
                            // :)
                        }
                    }

                    /* Country code */
                    $array_key = array_search('country_code', $headers_array);
                    $country_code = array_key_exists($csv_row[$array_key], get_countries_array()) ? $csv_row[$array_key] : null;

                    /* Continent code */
                    $array_key = array_search('continent_code', $headers_array);
                    $continent_code = array_key_exists($csv_row[$array_key], get_continents_array()) ? $csv_row[$array_key] : null;

                    /* Device type */
                    $array_key = array_search('device_type', $headers_array);
                    $device_type = in_array($csv_row[$array_key], ['mobile', 'tablet', 'desktop']) ? $csv_row[$array_key] : null;

                    /* Go through other potential keys */
                    foreach(['city_name', 'os_name', 'browser_name', 'browser_language', 'subscribed_on_url',] as $key) {
                        ${$key} = null;
                        if($array_key = array_search($key, $headers_array)) {
                            ${$key} = input_clean($csv_row[$array_key] ?? '', 256);
                        }
                    }

                    if($country_code) {
                        $country_code = mb_strtoupper(mb_substr($country_code, 0, 2));
                    }

                    $custom_parameters_array = [];
                    foreach($custom_parameters_keys as $header_index => $parameter_key) {
                        if(!empty($csv_row[$header_index])) {
                            $custom_parameters_array[$parameter_key] = input_clean($csv_row[$header_index] ?? '', 512);
                        }
                    }

                    /* Insert / update in the database */
                    $subscriber_id = db()->onDuplicate([
                        'endpoint', 'keys',
                    ])->insert('subscribers', [
                        'user_id' => $this->user->user_id,
                        'website_id' => $website->website_id,
                        'unique_endpoint_id' => $unique_endpoint_id,
                        'endpoint' => $endpoint,
                        'keys' => $keys,
                        'ip' => $ip,
                        'custom_parameters' => json_encode($custom_parameters_array),
                        'city_name' => $city_name,
                        'country_code' => $country_code,
                        'continent_code' => $continent_code,
                        'os_name' => $os_name,
                        'browser_name' => $browser_name,
                        'browser_language' => $browser_language,
                        'device_type' => $device_type,
                        'subscribed_on_url' => $subscribed_on_url,
                        'datetime' => $datetime,
                    ]);

                    /* Insert subscriber log */
                    db()->insert('subscribers_logs', [
                        'subscriber_id' => $subscriber_id,
                        'website_id' => $website->website_id,
                        'user_id' => $website->user_id,
                        'ip' => $ip,
                        'type' => 'subscribed',
                        'datetime' => $datetime,
                    ]);

                    $imported_subscribers++;
                }

                /* Update website statistics */
                db()->where('website_id', $website->website_id)->update('websites', ['total_subscribers' => db()->inc($imported_subscribers)]);

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('subscribers_import.success_message'), '<strong>' . $imported_subscribers . '</strong>'));

                /* Clear the cache */
                cache()->deleteItem('subscribers_total?user_id=' . $this->user->user_id);
                cache()->deleteItem('subscribers_dashboard?user_id=' . $this->user->user_id);

                redirect('subscribers');
            }

        }

        $values = [
            'website_id' => $_POST['website_id'] ?? $_GET['website_id'] ?? array_key_first($websites),
        ];

        /* Prepare the view */
        $data = [
            'websites' => $websites,
            'values' => $values
        ];

        $view = new \Altum\View('subscribers-import/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
