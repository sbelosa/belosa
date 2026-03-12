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

defined('ALTUMCODE') || die();

function get_next_run_datetime(
    $frequency,
    $time,
    $week_days = [],
    $month_days = [],
    $local_timezone = 'UTC',
    $datetime_modifier = null,
    $datetime_modifier_current = null,
) {
    $local_tz     = new DateTimeZone($local_timezone);
    $current_time = new DateTime('now', $local_tz);
    if($datetime_modifier_current) {
        $current_time = $current_time->modify($datetime_modifier_current);
    }

    [$hour, $minute] = explode(':', $time);
    $run_time = clone $current_time;
    $run_time->setTime((int) $hour, (int) $minute, 0);

    switch ($frequency) {
        case 'daily':
            if($run_time <= $current_time) {
                $run_time->modify('+1 day');
            }
            break;

        case 'weekly':
            while (!in_array((int) $run_time->format('N'), $week_days, true) || $run_time <= $current_time) {
                $run_time->modify('+1 day');
            }
            break;

        case 'monthly':
        default:
            while (!in_array((int) $run_time->format('j'), $month_days, true) || $run_time <= $current_time) {
                $run_time->modify('+1 day');
            }
            break;
    }

    $run_time->setTimezone(new DateTimeZone('UTC'));

    if($datetime_modifier) {
        $run_time->modify($datetime_modifier);
    }

    return $run_time->format('Y-m-d H:i:s');
}

function display_subscriber_log_type($type, $error = null) {
    $error_string = '';
    if($error) {
        $error = json_decode($error ?? '');

        if($error) {
            $error_string = $error->code . ' - ' . $error->message;
        }
    }

    return match ($type) {
        'subscribed' => '<span class="badge badge-success"><i class="fas fa-fw fa-sm fa-user-plus mr-1"></i> ' . l('subscribers.' . $type) . '</span>',
        'unsubscribed' => '<span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-user-minus mr-1"></i> ' . l('subscribers.' . $type) . '</span>',
        'permission_denied' => '<span class="badge badge-dark"><i class="fas fa-fw fa-sm fa-user-plus mr-1"></i> ' . l('subscribers.' . $type) . '</span>',
        'displayed_notification' => '<span class="badge badge-light"><i class="fas fa-fw fa-sm fa-display mr-1"></i> ' . l('subscribers.' . $type) . '</span>',
        'clicked_notification' => '<span class="badge badge-light"><i class="fas fa-fw fa-sm fa-mouse mr-1"></i> ' . l('subscribers.' . $type) . '</span>',
        'closed_notification' => '<span class="badge badge-light"><i class="fas fa-fw fa-sm fa-times mr-1"></i> ' . l('subscribers.' . $type) . '</span>',
        'manually_deleted' => '<span class="badge badge-warning"><i class="fas fa-fw fa-sm fa-trash-alt mr-1"></i> ' . l('subscribers.' . $type) . '</span>',
        'push_notification_sent' => '<span class="badge bg-notification text-notification"><i class="fas fa-fw fa-sm fa-fire mr-1"></i> ' . l('subscribers.' . $type) . '</span>',
        'expired_deleted' => '<span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-calendar-times mr-1"></i> ' . l('subscribers.' . $type) . '</span>',
        'push_notification_failed' => '<span class="badge badge-dark" data-toggle="tooltip" title="' . $error_string . '"><i class="fas fa-fw fa-sm fa-window-close mr-1"></i> ' . l('subscribers.' . $type) . '</span>',
        default => $type,
    };
}

function rss_feed_parse_url($rss_url) {
    $rss_xml = @simplexml_load_file($rss_url);
    if(!$rss_xml) return null;

    $rss_data = [];
    $namespaces = $rss_xml->getNamespaces(true);

    foreach ($rss_xml->channel->item ?? $rss_xml->entry ?? [] as $rss_item) {
        /* Default fields */
        $item_id = (string)($rss_item->guid ?? $rss_item->id ?? $rss_item->link);
        $item_title = (string)($rss_item->title ?? '');
        $item_url = (string)($rss_item->link['href'] ?? $rss_item->link ?? '');

        /* Description handling */
        $item_description = '';
        if(isset($rss_item->description)) {
            $item_description = (string)$rss_item->description;
        } elseif(isset($rss_item->summary)) {
            $item_description = (string)$rss_item->summary;
        } elseif(isset($rss_item->content)) {
            $item_description = (string)$rss_item->content;
        }

        $item_image = null;
        $item_publication_date = null;

        /* Publication date */
        if(isset($rss_item->pubDate)) {
            $item_publication_date = (string)$rss_item->pubDate;
        } elseif(isset($rss_item->published)) {
            $item_publication_date = (string)$rss_item->published;
        } elseif(isset($rss_item->updated)) {
            $item_publication_date = (string)$rss_item->updated;
        }

        /* Image extraction - media namespace */
        if(isset($namespaces['media'])) {
            $media_content = $rss_item->children($namespaces['media']);
            if(isset($media_content->content)) {
                foreach ($media_content->content as $media_item) {
                    if(!empty($media_item->attributes()->url)) {
                        $item_image = (string)$media_item->attributes()->url;
                        break;
                    }
                }
            }
            if(!$item_image && isset($media_content->thumbnail)) {
                foreach ($media_content->thumbnail as $media_thumbnail) {
                    if(!empty($media_thumbnail->attributes()->url)) {
                        $item_image = (string)$media_thumbnail->attributes()->url;
                        break;
                    }
                }
            }
        }

        /* Image extraction - enclosure */
        if(!$item_image && isset($rss_item->enclosure)) {
            $enclosure_attributes = $rss_item->enclosure->attributes();
            if(isset($enclosure_attributes['url'])) {
                $item_image = (string)$enclosure_attributes['url'];
            }
        }

        /* Image extraction - common field <image> */
        if(!$item_image && isset($rss_item->image)) {
            $item_image = (string)$rss_item->image;
        }

        /* Fallback: look for image in description */
        if(!$item_image && preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $item_description, $matches)) {
            $item_image = $matches[1];
        }

        $item_image = get_url($item_image);

        $rss_data[] = [
            'id' => input_clean(md5($item_id)),
            'title' => input_clean($item_title),
            'url' => input_clean($item_url),
            'description' => input_clean($item_description),
            'image' => input_clean($item_image),
            'publication_date' => input_clean($item_publication_date),
        ];
    }

    return $rss_data;
}
function process_utm_parameters($utm, $url) {
    if(!$url) return $url;

    $utm_parameters = [];
    if($utm->source) $utm_parameters['utm_source'] = $utm->source;
    if($utm->medium) $utm_parameters['utm_medium'] = $utm->medium;
    if($utm->campaign) $utm_parameters['utm_campaign'] = $utm->campaign;

    if(count($utm_parameters)) {
        $parsed_url = parse_url($url);
        $already_existing_query_parameters = $parsed_url['query'] ?? '';
        $final_query_string = $already_existing_query_parameters . '&' . http_build_query($utm_parameters);

        parse_str($final_query_string, $final_query_array);
        $final_query_array = array_unique($final_query_array);

        $append_query = '?' . http_build_query($final_query_array);
        $url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'] . $append_query;
    }

    return $url;
}

function initiate_web_push($public_key, $private_key) {
    /* Prepare the web push */
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:hey@example.com',
            'publicKey' => $public_key,
            'privateKey' => $private_key,
        ],
    ];

    $timeout = 20;
    $guzzle_options = [
        \GuzzleHttp\RequestOptions::TIMEOUT          => 8,
        \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT  => 5,
    ];

    $web_push = new \Minishlink\WebPush\WebPush($auth, [], $timeout, $guzzle_options);
    $web_push->setAutomaticPadding(0);

    return $web_push;
}

function process_push_notification($data = [
    'web_push_data',
    'website',
    'subscriber',
    'source_id',
    'source_value',
], $callback = null) {
    extract($data);

    /* Prepare web push */
    $web_push = initiate_web_push($website->keys->public_key, $website->keys->private_key);

    /* Prepare the push data */
    $web_push_data['title'] = html_entity_decode($web_push_data['title'], ENT_QUOTES, 'UTF-8');
    $web_push_data['description'] = html_entity_decode($web_push_data['description'], ENT_QUOTES, 'UTF-8');

    /* Web push content */
    $content = [
        'title' => $web_push_data['title'],
        'description' => $web_push_data['description'],
        'url' => $web_push_data['url'],
        'is_silent' => $web_push_data['is_silent'],
        'is_auto_hide' => $web_push_data['is_auto_hide'],
    ];

    /* Buttons */
    if($web_push_data['button_title_1']) {
        $content['button_title_1'] = $web_push_data['button_title_1'];
        $content['button_url_1'] = $web_push_data['button_url_1'];
    }

    if($web_push_data['button_title_2']) {
        $content['button_title_2'] = $web_push_data['button_title_2'];
        $content['button_url_2'] = $web_push_data['button_url_2'];
    }

    /* Add the icon & badge of the site to the notification */
    if($website->settings->icon) {
        $content['icon'] = \Altum\Uploads::get_full_url('websites_icons') . $website->settings->icon;
        $content['badge'] = \Altum\Uploads::get_full_url('websites_icons') . $website->settings->icon;
    }

    /* Hero image */
    if($web_push_data['image']) {
        $content['image'] = $web_push_data['image'];
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
    $content = array_merge($content, $web_push_data['content']);

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
            'TTL' => $web_push_data['ttl'] ?? array_key_last(require APP_PATH . 'includes/notifications_ttl.php'),
            'urgency' => str_replace('_', '-', $web_push_data['urgency'] ?? 'normal'),
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
            $source_id => $source_value,
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
            $source_id => $source_value,
            'website_id' => $subscriber->website_id,
            'user_id' => $website->user_id,
            'ip' => $subscriber->ip,
            'type' => 'push_notification_failed',
            'error' => $error,
            'datetime' => get_date(),
        ]);
    }

    /* Update the main website */
    db()->where('website_id', $website->website_id)->update('websites', [
        'total_sent_push_notifications' => db()->inc(),
    ]);

    /* Update the user */
    db()->where('user_id', $website->user_id)->update('users', [
        'pusher_sent_push_notifications_current_month' => db()->inc(),
        'pusher_total_sent_push_notifications' => db()->inc(),
    ]);

    if(isset($callback)) $callback();

    /* Clear the cache */
    cache()->deleteItem('total_sent_push_notifications_total?user_id=' . $website->user_id);
}

