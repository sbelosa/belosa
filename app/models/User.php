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

namespace Altum\Models;

use Altum\Logger;
use Altum\PaymentGateways\Lemonsqueezy;
use Altum\PaymentGateways\Paystack;
use Razorpay\Api\Api;

defined('ALTUMCODE') || die();

class User extends Model {

    /* Custom code: FC-2026-04-01: guard user cache refreshes from re-entering in the same request */
    private static array $user_cache_refresh_in_progress = [];
    /* /Custom code: FC-2026-04-01 */

    /* Custom code: FC-2026-03-04: trial cancellation analytics helpers */
    private function decode_extra($extra): object {
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
    /* /Custom code: FC-2026-03-04 */

    private function get_plan_limit_key_by_link_type($type) {
        return match($type) {
            'biolink' => 'biolinks_limit',
            'link' => 'links_limit',
            'file' => 'files_limit',
            'vcard' => 'vcards_limit',
            'event' => 'events_limit',
            'static' => 'static_limit',
            default => null,
        };
    }

    private function normalize_plan_settings($plan_settings) {
        if(is_string($plan_settings)) {
            $plan_settings = json_decode($plan_settings ?? '{}');
        }

        if(is_array($plan_settings)) {
            $plan_settings = (object) $plan_settings;
        }

        if(!is_object($plan_settings)) {
            $plan_settings = (object) [];
        }

        return $plan_settings;
    }

    private function normalize_link_settings($settings) {
        if(is_string($settings)) {
            $settings = json_decode($settings ?? '{}');
        }

        if(is_array($settings)) {
            $settings = (object) $settings;
        }

        if(!is_object($settings)) {
            $settings = (object) [];
        }

        return $settings;
    }

    /* Custom code: FC-2026-04-01: keep plan-gated WhatsApp block state aligned with current package */
    private function sync_whatsapp_biolink_blocks_with_plan(int $user_id, object $plan_settings, array &$updated_link_ids = []): void {
        $enabled_biolink_blocks = $plan_settings->enabled_biolink_blocks ?? (object) [];

        if(is_array($enabled_biolink_blocks)) {
            $enabled_biolink_blocks = (object) $enabled_biolink_blocks;
        }

        $can_use_whatsapp_block = (bool) ($enabled_biolink_blocks->custom_html_whatsapp ?? false);

        $whatsapp_blocks = db()
            ->where('user_id', $user_id)
            ->where('type', 'custom_html_whatsapp')
            ->get('biolinks_blocks', null, ['biolink_block_id', 'link_id', 'is_enabled', 'settings']);

        if(!$whatsapp_blocks) {
            return;
        }

        foreach($whatsapp_blocks as $biolink_block) {
            $settings = $this->normalize_link_settings($biolink_block->settings ?? '{}');
            $was_auto_disabled = (bool) ($settings->plan_auto_disabled ?? false);
            $is_enabled = (int) $biolink_block->is_enabled === 1;

            $status_changed = false;
            $settings_changed = false;
            $new_is_enabled = $is_enabled;

            if($can_use_whatsapp_block) {
                if($was_auto_disabled) {
                    $settings->plan_auto_disabled = false;
                    $settings_changed = true;
                }

                if(!$is_enabled && $was_auto_disabled) {
                    $new_is_enabled = true;
                    $status_changed = true;
                }
            } else {
                if($is_enabled) {
                    $new_is_enabled = false;
                    $status_changed = true;
                }

                if(!$was_auto_disabled) {
                    $settings->plan_auto_disabled = true;
                    $settings_changed = true;
                }
            }

            if($status_changed || $settings_changed) {
                $update = [];

                if($status_changed) {
                    $update['is_enabled'] = (int) $new_is_enabled;
                }

                if($settings_changed) {
                    $update['settings'] = json_encode($settings);
                }

                db()->where('biolink_block_id', $biolink_block->biolink_block_id)->where('user_id', $user_id)->update('biolinks_blocks', $update);
                $updated_link_ids[] = (int) $biolink_block->link_id;
            }
        }
    }
    /* /Custom code: FC-2026-04-01 */

    /* Custom code: FC-2026-04-01: centralize user payload hydration to avoid cache refresh recursion */
    private function hydrate_user_record($data) {
        if(!$data) {
            return null;
        }

        $current_plan_settings = $this->normalize_plan_settings($data->plan_settings ?? '{}');

        if(isset($data->plan_id) && is_numeric($data->plan_id)) {
            $plan = (new \Altum\Models\Plan())->get_plan_by_id((int) $data->plan_id);

            if($plan && isset($plan->settings)) {
                $synced_plan_settings = $this->normalize_plan_settings($plan->settings ?? '{}');

                if(json_encode($current_plan_settings) !== json_encode($synced_plan_settings)) {
                    db()->where('user_id', $data->user_id)->update('users', ['plan_settings' => json_encode($synced_plan_settings)]);
                    $current_plan_settings = $synced_plan_settings;
                }
            }
        }

        $data->plan_settings = $current_plan_settings;

        /* Parse billing details if existing */
        $data->billing = json_decode($data->billing ?? '');

        /* Parse preferences if existing */
        $data->preferences = json_decode($data->preferences ?? '');

        return $data;
    }
    /* /Custom code: FC-2026-04-01 */

    /* Custom code: FC-2026-04-01: single source for forced user refreshes bypassing stale cache state */
    private function get_fresh_user_by_user_id($user_id, bool $should_refresh_cache = true) {
        if(isset(self::$user_cache_refresh_in_progress[$user_id])) {
            return db()->where('user_id', $user_id)->getOne('users');
        }

        self::$user_cache_refresh_in_progress[$user_id] = true;

        try {
            $data = $this->hydrate_user_record(db()->where('user_id', $user_id)->getOne('users'));

            if($should_refresh_cache) {
                cache()->deleteItem('user?user_id=' . $user_id);
                cache()->deleteItemsByTag('user_id=' . $user_id);

                if($data) {
                    $cache_instance = cache()->getItem('user?user_id=' . $user_id);

                    cache()->save(
                        $cache_instance->set($data)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('user_id=' . $data->user_id)
                    );
                }
            }

            return $data;
        } finally {
            unset(self::$user_cache_refresh_in_progress[$user_id]);
        }
    }
    /* /Custom code: FC-2026-04-01 */

    public function sync_links_with_plan($user_id) {
        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'plan_settings']);

        if(!$user) {
            return;
        }

        $plan_settings = $this->normalize_plan_settings($user->plan_settings ?? '{}');

        $links = db()->where('user_id', $user->user_id)->where('type', ['biolink', 'link', 'file', 'vcard', 'event', 'static'], 'IN')->orderBy('datetime', 'ASC')->get('links', null, ['link_id', 'type', 'is_enabled', 'settings', 'datetime']);

        if(!$links) {
            return;
        }

        $default_biolink_id = (int) (\Altum\Link::get_user_main_biolink_id((int) $user->user_id) ?? 0);
        $default_vcard_id = (int) (db()->where('user_id', $user->user_id)->getValue('users_vcards', 'vcard_id') ?? 0);

        $links_by_type = [];
        foreach($links as $link) {
            $links_by_type[$link->type][] = $link;
        }

        $updated_link_ids = [];

        foreach($links_by_type as $type => $typed_links) {
            $limit_key = $this->get_plan_limit_key_by_link_type($type);
            if(!$limit_key) {
                continue;
            }

            $limit = isset($plan_settings->{$limit_key}) ? (int) $plan_settings->{$limit_key} : -1;

            if($type == 'biolink' && $default_biolink_id) {
                usort($typed_links, function($a, $b) use($default_biolink_id) {
                    if((int) $a->link_id === $default_biolink_id) return -1;
                    if((int) $b->link_id === $default_biolink_id) return 1;
                    return strcmp((string) $a->datetime, (string) $b->datetime);
                });
            }

            if($type == 'vcard' && $default_vcard_id) {
                usort($typed_links, function($a, $b) use($default_vcard_id) {
                    if((int) $a->link_id === $default_vcard_id) return -1;
                    if((int) $b->link_id === $default_vcard_id) return 1;
                    return strcmp((string) $a->datetime, (string) $b->datetime);
                });
            }

            $enabled_slots_used = 0;
            foreach($typed_links as $link) {
                $link_settings = $this->normalize_link_settings($link->settings ?? '{}');
                $was_auto_disabled = (bool) ($link_settings->plan_auto_disabled ?? false);
                $is_enabled = (int) $link->is_enabled === 1;
                $can_be_enabled = $limit == -1 || $enabled_slots_used < max($limit, 0);
                /* Custom code: FC-2026-03-19: keep default biolink/vcard enabled after downgrade */
                $is_default_link = ($type == 'biolink' && $default_biolink_id && (int) $link->link_id === $default_biolink_id)
                    || ($type == 'vcard' && $default_vcard_id && (int) $link->link_id === $default_vcard_id);
                /* /Custom code: FC-2026-03-19 */

                $new_is_enabled = $is_enabled;
                $settings_changed = false;
                $status_changed = false;

                /* Custom code: FC-2026-03-19: revive the protected default link when a slot exists */
                if($is_default_link && !$is_enabled && $can_be_enabled) {
                    $new_is_enabled = true;
                    $status_changed = true;
                    $enabled_slots_used++;

                    if($was_auto_disabled) {
                        $link_settings->plan_auto_disabled = false;
                        $settings_changed = true;
                    }
                } else
                /* /Custom code: FC-2026-03-19 */

                if($is_enabled) {
                    if($can_be_enabled) {
                        $enabled_slots_used++;

                        if($was_auto_disabled) {
                            $link_settings->plan_auto_disabled = false;
                            $settings_changed = true;
                        }
                    } else {
                        $new_is_enabled = false;
                        $status_changed = true;
                        $link_settings->plan_auto_disabled = true;
                        $settings_changed = true;
                    }
                } else {
                    if($was_auto_disabled && $can_be_enabled) {
                        $new_is_enabled = true;
                        $status_changed = true;
                        $enabled_slots_used++;
                        $link_settings->plan_auto_disabled = false;
                        $settings_changed = true;
                    }
                }

                if($status_changed || $settings_changed) {
                    $update = [];

                    if($status_changed) {
                        $update['is_enabled'] = (int) $new_is_enabled;
                    }

                    if($settings_changed) {
                        $update['settings'] = json_encode($link_settings);
                    }

                    db()->where('link_id', $link->link_id)->where('user_id', $user->user_id)->update('links', $update);
                    $updated_link_ids[] = (int) $link->link_id;
                }
            }
        }

        /* Custom code: FC-2026-04-01: disable PRO-only WhatsApp blocks after plan downgrade and restore after upgrade */
        $this->sync_whatsapp_biolink_blocks_with_plan((int) $user->user_id, $plan_settings, $updated_link_ids);
        /* /Custom code: FC-2026-04-01 */

        if(!empty($updated_link_ids)) {
            foreach(array_unique($updated_link_ids) as $link_id) {
                cache()->deleteItem('link?link_id=' . $link_id);
                cache()->deleteItem('biolink_blocks?link_id=' . $link_id);
                cache()->deleteItemsByTag('link_id=' . $link_id);
            }

            cache()->deleteItemsByTag('user_id=' . $user->user_id);
        }
    }

    public function get_user_by_user_id($user_id) {

        /* Try to check if the store exists via the cache */
        $cache_instance = cache()->getItem('user?user_id=' . $user_id);

        /* Set cache if not existing */
        if(is_null($cache_instance->get())) {

            /* Get data from the database */
            $data = db()->where('user_id', $user_id)->getOne('users');

            if($data) {
                $data = $this->hydrate_user_record($data);

                /* Save to cache */
                cache()->save(
                    $cache_instance->set($data)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('user_id=' . $data->user_id)
                );
            }

        } else {

            /* Get cache */
            $data = $cache_instance->get();

            /* Custom code: FC-2026-04-01: recover from malformed cached payloads before touching nested fields */
            if(!is_object($data) || !isset($data->user_id)) {
                return $this->get_fresh_user_by_user_id($user_id);
            }
            /* /Custom code: FC-2026-04-01 */

            /* Custom code: FC-2026-03-06: revalidate cached user plan after plan changes */
            $latest_plan_row = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'plan_id', 'plan_settings']);

            if($latest_plan_row) {
                $has_plan_id_changed = (string) ($latest_plan_row->plan_id ?? '') !== (string) ($data->plan_id ?? '');

                if($has_plan_id_changed) {
                    return $this->get_fresh_user_by_user_id($user_id);
                }

                $latest_plan_settings = $this->normalize_plan_settings($latest_plan_row->plan_settings ?? '{}');

                if(isset($latest_plan_row->plan_id) && is_numeric($latest_plan_row->plan_id)) {
                    $plan = (new \Altum\Models\Plan())->get_plan_by_id((int) $latest_plan_row->plan_id);

                    if($plan && isset($plan->settings)) {
                        $synced_plan_settings = $this->normalize_plan_settings($plan->settings ?? '{}');

                        if(json_encode($latest_plan_settings) !== json_encode($synced_plan_settings)) {
                            db()->where('user_id', $latest_plan_row->user_id)->update('users', ['plan_settings' => json_encode($synced_plan_settings)]);
                            $latest_plan_settings = $synced_plan_settings;
                        }
                    }
                }

                if(json_encode($this->normalize_plan_settings($data->plan_settings ?? '{}')) !== json_encode($latest_plan_settings)) {
                    $data->plan_settings = $latest_plan_settings;

                    cache()->save(
                        $cache_instance->set($data)->expiresAfter(CACHE_DEFAULT_SECONDS)->addTag('user_id=' . $data->user_id)
                    );
                }
            }
            /* /Custom code: FC-2026-03-06 */

        }

        return $data;
    }


    /* Requires full user variable */
    public function process_user_plan_expiration_by_user($user) {

        if((new \DateTime($user->plan_expiration_date)) < (new \DateTime()) && $user->plan_id != 'free') {

            /* Switch the user to the default plan */
            db()->where('user_id', $user->user_id)->update('users', [
                'plan_id' => 2, /* Custom code */
                'plan_settings' => json_encode( (new \Altum\Models\Plan())->get_plan_by_id(2)->settings),  /* Custom code */
                'plan_expiration_date' => date( 'Y-m-d H:i:s', strtotime( ' + 10 years' ) ),  /* Custom code */
                'payment_subscription_id' => '',
                'payment_processor' => '',
                'payment_total_amount' => 0,
                'payment_currency' => '',
            ]);

            $this->sync_links_with_plan($user->user_id);

            /* Clear the cache */
            cache()->deleteItemsByTag('user_id=' . $user->user_id);
        }

    }

    public function delete($user_id) {

        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'email', 'name', 'preferences']);

        if(!$user) return;

        $user->preferences = json_decode($user->preferences ?? '');

        /* Cancel his active subscriptions if active */
        try {
            $this->cancel_subscription($user_id);
        } catch (\Exception $exception) {
            // :)
        }

        /* Send webhook notification if needed */
        if(settings()->webhooks->user_delete) {
            fire_and_forget('post', settings()->webhooks->user_delete, [
                'user_id' => $user->user_id,
                'email' => $user->email,
                'name' => $user->name,
                'datetime' => get_date(),
            ], signature: true);
        }

        /* Run potential hooks */
        \Altum\CustomHooks::user_delete(['user' => $user]);

        /* Delete the record from the database */
        db()->where('user_id', $user_id)->delete('users');

        /* Clear the cache */
        cache()->deleteItemsByTag('user_id=' . $user_id);

    }

    public function update_last_activity($user_id) {
        db()->where('user_id', $user_id)->update('users', ['last_activity' => get_date()]);
        /* Clear the cache */
        cache()->deleteItemsByTag('user_id=' . $user_id);
    }

    public function verify_null_password($user_id, $email, $password) {
        if(empty($password)) {
            $lost_password_code = $lost_password_code ?? md5(uniqid('', true) . random_bytes(16));
            db()->where('user_id', $user_id)->update('users', ['lost_password_code' => $lost_password_code]);
            redirect('reset-password/' . md5($email) . '/' . $lost_password_code);
        }

        return;
    }

    public function create(
        $email = '',
        $raw_password = '',
        $name = '',
        $status = 0,
        $source = null,
        $email_activation_code = null,
        $lost_password_code = null,
        $is_newsletter_subscribed = 0,
        $plan_id = 'free',
        $plan_settings = '',
        $plan_expiration_date = null,
        $timezone = 'UTC',
        $extra = '',
        $is_admin_created = false,
        $meta = null /* Custom code */
    ) {

        /* Define some needed variables */
        $password = is_null($raw_password) ? null : password_hash($raw_password, PASSWORD_DEFAULT);
        $total_logins = $status == '1' && !$is_admin_created && !in_array($source, ['admin_create', 'admin_api_create']) ? 1 : 0;
        $plan_expiration_date = $plan_expiration_date ?? get_date();
        $plan_trial_done = 0;
        $language = \Altum\Language::$name;
        $api_key = bin2hex(random_bytes(16));
        $referral_key = md5(uniqid('', true) . random_bytes(16));
        $ip = $is_admin_created ? null : get_ip();

        /* Detect the location */
        try {
            $maxmind = $is_admin_created ? null : (get_maxmind_reader_city())->get($ip);
        } catch(\Exception $exception) {
            /* :) */
        }
        $continent_code = isset($maxmind) && isset($maxmind['continent']) ? $maxmind['continent']['code'] : null;
        $country_code = isset($maxmind) && isset($maxmind['country']) ? $maxmind['country']['iso_code'] : null;
        $city_name = isset($maxmind) && isset($maxmind['city']) ? $maxmind['city']['names']['en'] : null;

        /* Billing */
        $billing = json_encode(['type' => 'personal', 'name' => '', 'address' => '', 'city' => '', 'county' => '', 'zip' => '', 'country' => $country_code, 'phone' => '', 'tax_id' => '', 'notes' => '']);

        /* Detect extra details about the user */
        $whichbrowser = get_whichbrowser();
        $browser_name = $whichbrowser->browser->name ?? null;
        $os_name = $whichbrowser->os->name ?? null;
        $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
        $device_type = get_this_device_type();

        /* Check for potential referral cookie */
        $referred_by = null;
        if(!$is_admin_created && isset($_COOKIE['referred_by']) && $user = db()->where('referral_key', $_COOKIE['referred_by'])->getOne('users', ['user_id', 'referral_key'])) {
            $referred_by = $user->user_id;
        }

        /* Default preferences */
        $preferences = json_encode([
            'default_results_per_page' => settings()->main->default_results_per_page,
            'default_order_type' => 'DESC',
            'links_default_order_by' => 'link_id',
            'qr_codes_default_order_by' => 'qr_code_id',
            'openai_api_key' => '',
            'meta' => $meta, /* Custom code */
        ]);

        /* Add the user to the database */
        $registered_user_id = db()->insert('users', [
            'password' => $password,
            'email' => $email,
            'name' => $name,
            'billing' => $billing,
            'api_key' => $api_key,
            'email_activation_code' => $email_activation_code,
            'lost_password_code' => $lost_password_code,
            'is_newsletter_subscribed' => (int) $is_newsletter_subscribed,
            'plan_id' => $plan_id,
            'plan_expiration_date' => $plan_expiration_date,
            'plan_settings' => $plan_settings,
            'plan_trial_done' => $plan_trial_done,
            'referral_key' => $referral_key,
            'referred_by' => $referred_by,
            'language' => $language,
            'timezone' => $timezone,
            'status' => $status,
            'source' => $source,
            'datetime' => get_date(),
            'ip' => $ip,
            'continent_code' => $continent_code,
            'country' => $country_code,
            'city_name' => $city_name,
            'device_type' => $device_type,
            'os_name' => $os_name,
            'browser_name' => $browser_name,
            'browser_language' => $browser_language,
            'total_logins' => $total_logins,
            'extra' => json_encode($extra),
            'preferences' => $preferences,
        ]);

        /* Clear out referral cookie if needed */
        if($referred_by) {
            setcookie('referred_by', '', time()-30, COOKIE_PATH);
        }

        \Altum\CustomHooks::user_finished_registration(['user_id' => $registered_user_id, 'email' => $email, 'plan_settings' => $plan_settings]);

        return [
            'user_id' => $registered_user_id,
            'password' => $password,
            'source' => $source,
            'ip' => $ip,
            'country' => $country_code,
            'city_name' => $city_name,
            'device_type' => $device_type,
            'os_name' => $os_name,
            'browser_name' => $browser_name,
        ];
    }

    /*
    * Function to update a user with more details on a login action
    */
    public function login_aftermath_update($user_id, $method = 'classic') {

        $ip = get_ip();

        setcookie('spotlight_has_results', '', time()-30, COOKIE_PATH);

        /* Detect the location */
        try {
            $maxmind = (get_maxmind_reader_city())->get($ip);
        } catch(\Exception $exception) {
            /* :) */
        }
        $continent_code = isset($maxmind) && isset($maxmind['continent']) ? $maxmind['continent']['code'] : null;
        $country_name = isset($maxmind) && isset($maxmind['country']) ? $maxmind['country']['iso_code'] : null;
        $city_name = isset($maxmind) && isset($maxmind['city']) ? $maxmind['city']['names']['en'] : null;

        /* Detect extra details about the user */
        $whichbrowser = get_whichbrowser();
        $browser_name = $whichbrowser->browser->name ?? null;
        $os_name = $whichbrowser->os->name ?? null;
        $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
        $device_type = get_this_device_type();

        /* Database query */
        db()->where('user_id', $user_id)->update('users', [
            'ip' => $ip,
            'continent_code' => $continent_code,
            'country' => $country_name,
            'city_name' => $city_name,
            'device_type' => $device_type,
            'os_name' => $os_name,
            'browser_name' => $browser_name,
            'browser_language' => $browser_language,
            'total_logins' => db()->inc(),
            'user_deletion_reminder' => 0,
        ]);

        Logger::users($user_id, 'login.' . $method . '.success');

        /* Clear the cache */
        cache()->deleteItemsByTag('user_id=' . $user_id);

        /* Custom code */
        if(isset($_COOKIE['referral'])) {
            setcookie('referral', '', -1, '/');            
        }
        
        if(!$user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'status'])) {
                return;
            }

            if($user->status != 1) {
                return;
            }

            $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'status', 'plan_id']);
            if($user->plan_id != 5) {
                return;
            }

            if(!$referral_key = db()->where('user_id', $user_id)->where('type', 'biolink')->getOne('links', ['url'])) {
                return;
            }

            setcookie('referral', $referral_key->url, time()+60*60*24*365, '/');
        /* /Custom code */

    }

    public function cancel_subscription($user_id) {

        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'payment_subscription_id', 'payment_processor', 'plan_id', 'plan_trial_done', 'plan_expiration_date', 'extra']);

        if(empty($user->payment_subscription_id)) {
            return true;
        }

        switch($user->payment_processor) {
            case 'stripe':

                /* Initiate Stripe */
                \Stripe\Stripe::setApiKey(settings()->stripe->secret_key);
                \Stripe\Stripe::setApiVersion('2023-10-16');

                /* Cancel the Stripe Subscription */
                $subscription = \Stripe\Subscription::retrieve($user->payment_subscription_id);
                $subscription->cancel();

                break;

            case 'paypal':

                /* Custom code: FC-2026-03-09: PayPal cancel fallback for mode/credential mismatches */
                $paypal_api_url = \Altum\PaymentGateways\Paypal::get_api_url();
                $paypal_cancel_payload = \Unirest\Request\Body::json([
                    'reason' => sprintf(l('account_plan.cancel.reason'), settings()->main->title)
                ]);

                $cancel_with_api_url = function(string $api_url) use ($user, $paypal_cancel_payload) {
                    \Unirest\Request::auth(settings()->paypal->client_id, settings()->paypal->secret);

                    $token_response = \Unirest\Request::post($api_url . 'v1/oauth2/token', [], \Unirest\Request\Body::form(['grant_type' => 'client_credentials']));

                    if($token_response->code >= 400) {
                        $error = $token_response->body->error ?? 'paypal_oauth_error';
                        $error_description = $token_response->body->error_description ?? 'Unknown PayPal OAuth error.';
                        throw new \Exception($error . ':' . $error_description);
                    }

                    $headers = [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token_response->body->access_token
                    ];

                    $cancel_response = \Unirest\Request::post($api_url . 'v1/billing/subscriptions/' . $user->payment_subscription_id . '/cancel', $headers, $paypal_cancel_payload);

                    if($cancel_response->code >= 400) {
                        $name = $cancel_response->body->name ?? 'paypal_cancel_error';
                        $message = $cancel_response->body->message ?? 'Unknown PayPal cancellation error.';

                        if($name === 'RESOURCE_NOT_FOUND') {
                            return;
                        }

                        throw new \Exception($name . ':' . $message);
                    }
                };

                try {
                    $cancel_with_api_url($paypal_api_url);
                } catch (\Exception $exception) {
                    $is_paypal_auth_error = stripos($exception->getMessage(), 'invalid_client') !== false || stripos($exception->getMessage(), 'Client Authentication failed') !== false;

                    if(!$is_paypal_auth_error) {
                        throw $exception;
                    }

                    $fallback_api_url = $paypal_api_url === \Altum\PaymentGateways\Paypal::$live_api_url
                        ? \Altum\PaymentGateways\Paypal::$sandbox_api_url
                        : \Altum\PaymentGateways\Paypal::$live_api_url;

                    $cancel_with_api_url($fallback_api_url);
                }
                /* /Custom code: FC-2026-03-09 */

                break;

            case 'paystack':

                Paystack::$secret_key = settings()->paystack->secret_key;

                $payment_subscription_id = explode('###', $user->payment_subscription_id);
                $code = $payment_subscription_id[0];
                $token = $payment_subscription_id[1];

                $response = \Unirest\Request::post(Paystack::$api_url . 'subscription/disable', Paystack::get_headers(), \Unirest\Request\Body::json([
                    'code' => $code,
                    'token' => $token,
                ]));

                if(!$response->body->status) {
                    throw new \Exception($response->body->message);
                }

                break;

            case 'razorpay':

                $razorpay = new Api(settings()->razorpay->key_id, settings()->razorpay->key_secret);

                $response = $razorpay->subscription->fetch($user->payment_subscription_id)->cancel();

                break;

            case 'mollie':

                $mollie = new \Mollie\Api\MollieApiClient();
                $mollie->setApiKey(settings()->mollie->api_key);

                $payment_subscription_id = explode('###', $user->payment_subscription_id);
                $customer_id = $payment_subscription_id[0];
                $subscription_id = $payment_subscription_id[1];

                $mollie->subscriptions->cancelForId($customer_id, $subscription_id);

                break;

            case 'flutterwave':

                $response = \Unirest\Request::put(
                    'https://api.flutterwave.com/v3/subscriptions/' . $user->payment_subscription_id . '/cancel',
                    [
                        'Authorization' => 'Bearer ' . settings()->flutterwave->secret_key,
                        'Content-Type' => 'application/json',
                    ],
                );

                /* Check against errors */
                if($response->code >= 400) {
                    throw new \Exception($response->body->message);
                }

                break;

            case 'lemonsqueezy':

                Lemonsqueezy::$api_key = settings()->lemonsqueezy->api_key;

                $response = \Unirest\Request::delete(
                    Lemonsqueezy::$api_url . 'subscriptions/' . $user->payment_subscription_id,
                    Lemonsqueezy::get_headers()
                );

                /* Check against errors */
                if($response->code >= 400) {
                    throw new \Exception($response->body);
                }

                break;

            case 'paddle_billing':

                /* Paddle API setup */
                $paddle_api_url = 'https://' . (settings()->paddle_billing->mode == 'sandbox' ? 'sandbox-api' : 'api') . '.paddle.com/';
                $paddle_headers = [
                    'Authorization' => 'Bearer ' . settings()->paddle_billing->api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ];

                /* Cancel the subscription */
                $response = \Unirest\Request::post(
                    $paddle_api_url . 'subscriptions/' . $user->payment_subscription_id . '/cancel',
                    $paddle_headers,
                    \Unirest\Request\Body::json([
                        'effective_from' => 'next_billing_period',
                    ])
                );

                break;

        }

        /* Custom code: FC-2026-03-04: track cancellation timing versus trial end */
        $extra = $this->decode_extra($user->extra ?? null);
        $cancelled_at = get_date();

        $trial_days = 0;
        if($user->plan_id && $user->plan_id != 'free') {
            $plan = db()->where('plan_id', $user->plan_id)->getOne('plans', ['trial_days']);
            $trial_days = (int) ($plan->trial_days ?? 0);
        }

        $is_trial_window_open = (int) ($user->plan_trial_done ?? 0) === 1
            && $trial_days > 0
            && !empty($user->plan_expiration_date)
            && (new \DateTime($user->plan_expiration_date)) >= (new \DateTime());

        $extra->billing_subscription_cancelled_at = $cancelled_at;
        $extra->billing_subscription_cancelled_during_trial = $is_trial_window_open ? 1 : 0;
        /* /Custom code: FC-2026-03-04 */

        /* Database query */
        db()->where('user_id', $user->user_id)->update('users', [
            'payment_subscription_id' => '',
            'extra' => json_encode($extra),
        ]);

        /* Clear the cache */
        cache()->deleteItemsByTag('user_id=' . $user->user_id);

    }

    /* Custom code */
    /* vendor tecnickcom/tcpdf */
    public function hydrate_biolink_from_user_data($user_id, $biolink_link_id, $vcard_url = null) {
        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'name', 'email', 'preferences']);

        if(!$user) {
            return;
        }

        $preferences = json_decode($user->preferences ?? '{}');
        $meta = $preferences->meta ?? (object) [];
        $synced_avatar = null;

        $default_synced_avatar = '39a2505ec7fb2989e89b24e4122ebd3e.png';
        if(file_exists(UPLOADS_PATH . 'avatars/' . $default_synced_avatar)) {
            $synced_avatar = $default_synced_avatar;
        }

        $biolink_blocks = db()->where('user_id', $user->user_id)->where('link_id', $biolink_link_id)->get('biolinks_blocks');

        foreach($biolink_blocks as $biolink_block) {
            $biolink_block->settings = json_decode($biolink_block->settings ?? '{}');

            if(is_array($biolink_block->settings)) {
                $biolink_block->settings = (object) $biolink_block->settings;
            }

            switch($biolink_block->type) {
                case 'heading':
                    $biolink_block->settings->text = $user->name;

                    db()->where('biolink_block_id', $biolink_block->biolink_block_id)->update('biolinks_blocks', [
                        'settings' => json_encode($biolink_block->settings),
                    ]);
                    break;

                case 'avatar':
                    if($synced_avatar) {
                        $biolink_block->settings->image = $synced_avatar;

                        db()->where('biolink_block_id', $biolink_block->biolink_block_id)->update('biolinks_blocks', [
                            'settings' => json_encode($biolink_block->settings),
                        ]);
                    }

                    break;

                case 'header':
                    if($synced_avatar) {
                        $biolink_block->settings->avatar = $synced_avatar;

                        db()->where('biolink_block_id', $biolink_block->biolink_block_id)->update('biolinks_blocks', [
                            'settings' => json_encode($biolink_block->settings),
                        ]);
                    }
                    break;

                case 'socials':
                    if(!isset($biolink_block->settings->socials) || is_array($biolink_block->settings->socials)) {
                        $biolink_block->settings->socials = (object) ($biolink_block->settings->socials ?? []);
                    }

                    $biolink_block->settings->socials->email = $user->email;

                    if(isset($meta->phone)) {
                        $biolink_block->settings->socials->tel = $meta->phone;
                    }

                    $biolink_block->settings->socials->facebook = '';
                    $biolink_block->settings->socials->instagram = '';
                    $biolink_block->settings->socials->tiktok = '';

                    db()->where('biolink_block_id', $biolink_block->biolink_block_id)->update('biolinks_blocks', [
                        'settings' => json_encode($biolink_block->settings),
                    ]);
                    break;

                case 'link':
                case 'link_forever_shop':
                case 'link_discount':
                case 'custom_html_chatbot':
                case 'custom_html_whatsapp':
                case 'link_homescreen_android':
                case 'link_homescreen_ios':
                case 'link_save_contact':
                    db()->where('biolink_block_id', $biolink_block->biolink_block_id)->update('biolinks_blocks', [
                        'is_enabled' => 1,
                    ]);

                    if(in_array($biolink_block->type, ['link_forever_shop', 'link_discount', 'custom_html_chatbot', 'custom_html_whatsapp', 'link_homescreen_android', 'link_homescreen_ios'])) {
                        $biolink_block->settings->display_countries = [];

                        db()->where('biolink_block_id', $biolink_block->biolink_block_id)->update('biolinks_blocks', [
                            'settings' => json_encode($biolink_block->settings),
                        ]);
                    }

                    if($biolink_block->type == 'link_save_contact') {
                        if(!$vcard_url) {
                            $vcard_link = db()->where('user_id', $user->user_id)->where('type', 'vcard')->getOne('links', ['url']);
                            $vcard_url = $vcard_link->url ?? null;
                        }

                        if($vcard_url) {
                            db()->where('biolink_block_id', $biolink_block->biolink_block_id)->update('biolinks_blocks', [
                                'location_url' => SITE_URL . $vcard_url,
                            ]);
                        }
                    }

                    break;
            }
        }

        $biolink = db()->where('user_id', $user->user_id)->where('link_id', $biolink_link_id)->where('type', 'biolink')->getOne('links', ['settings']);

        if($biolink) {
            $biolink_settings = json_decode($biolink->settings ?? '{}');

            if(is_array($biolink_settings)) {
                $biolink_settings = (object) $biolink_settings;
            }

            if(!isset($biolink_settings->seo) || is_array($biolink_settings->seo)) {
                $biolink_settings->seo = (object) ($biolink_settings->seo ?? []);
            }

            $biolink_settings->seo->title = $user->name;
            $biolink_settings->seo->meta_description = l('global.seo.meta.description.partner');

            db()->where('link_id', $biolink_link_id)->where('user_id', $user->user_id)->where('type', 'biolink')->update('links', [
                'settings' => json_encode($biolink_settings),
            ]);
        }
    }

    /* Custom code: FC-2026-03-06: resolve factory template id */
    private function get_factory_biolink_template_id(): ?int {
        $biolink_template = db()->where('is_enabled', 1)->where('link_id', 83)->getOne('biolinks_templates', ['biolink_template_id']);

        if(!$biolink_template) {
            $biolink_template = db()->where('is_enabled', 1)->where('biolink_template_id', 1)->getOne('biolinks_templates', ['biolink_template_id']);
        }

        if(!$biolink_template) {
            $biolink_template = db()->where('is_enabled', 1)->orderBy('biolink_template_id', 'ASC')->getOne('biolinks_templates', ['biolink_template_id']);
        }

        return $biolink_template ? (int) $biolink_template->biolink_template_id : null;
    }
    /* /Custom code: FC-2026-03-06 */

    public function create_links($user_id) {
        $user = db()->where('user_id', $user_id)->getOne('users', ['user_id', 'name', 'email', 'preferences']);

        $name = explode(" ", $user->name);  

        $preferences = json_decode($user->preferences ?? '{}');
        $meta = $preferences->meta ?? (object) [];

        $url = mb_strtolower(string_generate(10));
        $type = 'vcard';
        $settings = json_encode([
            'password' => null,
            'sensitive_content' => false,
            'clicks_limit' => null,
            'expiration_url' => null,
            'vcard_avatar' => null,
            'vcard_first_name' => isset($name[0]) ? $name[0] : null,
            'vcard_last_name' => isset($name[1]) ? $name[1] : null,
            'vcard_email' => $user->email,
            'vcard_url' => null,
            'vcard_company' => null,
            'vcard_job_title' => null,
            'vcard_birthday' => null,
            'vcard_street' => isset($meta->address) ? $meta->address : null,
            'vcard_city' => isset($meta->city) ? $meta->city : null,
            'vcard_zip' => isset($meta->zip) ? $meta->zip : null,
            'vcard_region' => null,
            'vcard_country' => isset($meta->country) ? $meta->country : null,
            'vcard_note' => null,
            'vcard_socials' => [],
            'vcard_phone_numbers' => isset($meta->phone) ? [$meta->phone] : [],
        ]);

        /* Generate random url if not specified */
        while(db()->where('url', $url)->getValue('links', 'link_id')) {
            $url = mb_strtolower(string_generate(10));
        }

        //$this->check_url($url);
        
        /* Insert to database */
        $link_id = db()->insert('links', [
            'user_id' => $user->user_id,
            'domain_id' => 0,
            'type' => $type,
            'url' => $url,
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        $vcard_url = $url;

        /* Custom code: FC-2026-03-06: prioritize /link/83 template with local fallback */
        $_POST['biolink_template_id'] = $this->get_factory_biolink_template_id();
        /* /Custom code: FC-2026-03-06 */
    
        /* Check if custom domain is set */
        $domain_id = false;                                

        /* Start the creation process */
        $url = mb_strtolower(string_generate(10));
        $type = 'biolink';
        $settings = json_encode([
            'verified_location' => 'top',
            'favicon' => null,
            'background_type' => 'preset',
            'background' => 'one',
            'background_attachment' => 'scroll',
            'text_color' => '#ffffff',
            'display_branding' => true,
            'branding' => [
                'url' => '',
                'name' => ''
            ],
            'seo' => [
                'block' => false,
                'title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'image' => '',
            ],
            'utm' => [
                'medium' => '',
                'source' => '',
            ],
            'font' => null,
            'font_size' => 16,
            'password' => null,
            'sensitive_content' => false,
            'leap_link' => null,
            'custom_css' => null,
            'custom_js' => null,
        ]);

        /* Generate random url if not specified */
        while(db()->where('url', $url)->where('domain_id', $domain_id)->getValue('links', 'link_id')) {
            $url = mb_strtolower(string_generate(10));
        }            

        /* Check for biolink templates */
        if($_POST['biolink_template_id']) {
            $biolinks_templates = (new \Altum\Models\BiolinksTemplates())->get_biolinks_templates();

            if(array_key_exists($_POST['biolink_template_id'], $biolinks_templates)) {
                $biolink_template = $biolinks_templates[$_POST['biolink_template_id']];

                /* Get the details of the biolink page */
                $biolink = db()->where('link_id', $biolink_template->link_id)->getOne('links');

                if($biolink) {
                    /* Get all the biolink blocks as well */
                    $biolink->settings = json_decode($biolink->settings);
                    $biolink->settings->seo_image = \Altum\Uploads::copy_uploaded_file($biolink->settings->seo_image, 'block_images/', 'block_images/', 'json_error');
                    $biolink->settings->favicon = \Altum\Uploads::copy_uploaded_file($biolink->settings->favicon, 'favicons/', 'favicons/', 'json_error');
                    if ($biolink->settings->background_type == 'image') $biolink->settings->background = \Altum\Uploads::copy_uploaded_file($biolink->settings->background, 'backgrounds/', 'backgrounds/', 'json_error');

                    /* Overwrite default settings with the settings of the template */
                    $settings = json_encode($biolink->settings);

                    /* Prepare the statement and execute query */
                    db()->where('biolink_template_id', $biolink_template->biolink_template_id)->update('biolinks_templates', [
                        'total_usage' => db()->inc()
                    ]);

                }
            }
        }

        /* Insert to database */
        $link_id = db()->insert('links', [
            'user_id' => $user->user_id,                
            'type' => $type,
            'url' => $url,
            'settings' => $settings,
            'datetime' => \Altum\Date::$date,
        ]);

        /* Check for a template usage */
        if(isset($biolink_template)) {
            /* Get all biolink blocks if needed */
            $biolink_blocks = db()->where('link_id', $biolink_template->link_id)->get('biolinks_blocks');

            foreach($biolink_blocks as $biolink_block) {
                $biolink_block->settings = json_decode($biolink_block->settings);

                if(is_array($biolink_block->settings)) {
                    $biolink_block->settings = (object) $biolink_block->settings;
                }

                /* Duplication of resources */
                switch($biolink_block->type) {
                    case 'file':
                        $biolink_block->settings->file = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->file, \Altum\Uploads::get_path('files'), \Altum\Uploads::get_path('files'), 'json_error');
                        break;

                    case 'avatar':
                        $biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'avatars/', 'avatars/', 'json_error');
                        break;

                    case 'vcard':
                        $biolink_block->settings->vcard_avatar = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->vcard_avatar, 'avatars/', 'avatars/', 'json_error');
                        break;

                    case 'image':
                    case 'image_grid':
                        $biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_images/', 'block_images/', 'json_error');
                        break;

                    default:
                        $biolink_block->settings->image = \Altum\Uploads::copy_uploaded_file($biolink_block->settings->image, 'block_thumbnail_images/', 'block_thumbnail_images/', 'json_error');
                        break;
                }

                /* Database query */
                db()->insert('biolinks_blocks', [
                    'user_id' => $user->user_id,
                    'link_id' => $link_id,
                    'type' => $biolink_block->type,
                    'location_url' => $biolink_block->location_url,
                    'settings' => json_encode($biolink_block->settings),
                    'order' => $biolink_block->order,
                    'start_date' => $biolink_block->start_date,
                    'end_date' => $biolink_block->end_date,
                    'is_enabled' => $biolink_block->is_enabled,
                    'datetime' => \Altum\Date::$date,
                ]);
            }
        }

        $this->hydrate_biolink_from_user_data($user->user_id, $link_id, $vcard_url);

        $qr_code_settings = require APP_PATH . 'includes/qr_codes.php';
        $available_qr_codes = require APP_PATH . 'includes/enabled_qr_codes.php';

        $settings = [
            'style' => 'square',
            'inner_eye_style' => 'square',
            'outer_eye_style' => 'square',
            'foreground_type' => 'color',
        ];

        $_POST['link_id'] = $link_id;
        $_POST['type'] = 'url';
        $_POST['url'] = SITE_URL . $url;

        if(!empty($_POST)) {
            $required_fields = ['name', 'type'];

            $_POST['link_id'] = trim(query_clean($_POST['link_id']));
            $_POST['project_id'] = null;
            $_POST['type'] = isset($_POST['type']) && array_key_exists($_POST['type'], $available_qr_codes) ? $_POST['type'] : 'text';
            $settings['inner_eye_style'] = $_POST['inner_eye_style'] = isset($_POST['inner_eye_style']) && in_array($_POST['inner_eye_style'], ['square', 'dot', 'rounded', 'diamond', 'flower', 'leaf',]) ? $_POST['inner_eye_style'] : 'square';
            $settings['outer_eye_style'] = $_POST['outer_eye_style'] = isset($_POST['outer_eye_style']) && in_array($_POST['outer_eye_style'], ['square', 'circle', 'rounded', 'flower', 'leaf',]) ? $_POST['outer_eye_style'] : 'leaf';
            $settings['style'] = $_POST['style'] = isset($_POST['style']) && in_array($_POST['style'], ['square', 'dot', 'round', 'diamond', 'heart']) ? $_POST['style'] : 'square';
            $settings['foreground_type'] = $_POST['foreground_type'] = isset($_POST['foreground_type']) && in_array($_POST['foreground_type'], ['color', 'gradient']) ? $_POST['foreground_type'] : 'color';
            switch($_POST['foreground_type']) {
                case 'color':
                    $settings['foreground_color'] = $_POST['foreground_color'] = isset($_POST['foreground_color']) && preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['foreground_color']) ? $_POST['foreground_color'] : '#000000';
                    break;

                case 'gradient':
                    $settings['foreground_gradient_style'] = $_POST['foreground_gradient_style'] = isset($_POST['foreground_gradient_style']) && in_array($_POST['foreground_gradient_style'], ['vertical', 'horizontal', 'diagonal', 'inverse_diagonal', 'radial']) ? $_POST['foreground_gradient_style'] : 'horizontal';
                    $settings['foreground_gradient_one'] = $_POST['foreground_gradient_one'] = isset($_POST['foreground_gradient_one']) && preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['foreground_gradient_one']) ? $_POST['foreground_gradient_one'] : '#000000';
                    $settings['foreground_gradient_two'] = $_POST['foreground_gradient_two'] = isset($_POST['foreground_gradient_two']) && preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['foreground_gradient_two']) ? $_POST['foreground_gradient_two'] : '#000000';
                    break;
            }
            $settings['background_color'] = $_POST['background_color'] = isset($_POST['background_color']) && preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['background_color']) ? $_POST['background_color'] : '#ffffff';
            $settings['background_color_transparency'] = $_POST['background_color_transparency'] = isset($_POST['background_color_transparency']) && in_array($_POST['background_color_transparency'], range(0, 100)) ? (int) $_POST['background_color_transparency'] : 100;
            $settings['custom_eyes_color'] = $_POST['custom_eyes_color'] = (bool) (int) ($_POST['custom_eyes_color'] ?? 0);
            if($_POST['custom_eyes_color']) {
                $settings['eyes_inner_color'] = $_POST['eyes_inner_color'] = isset($_POST['eyes_inner_color']) && preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['eyes_inner_color']) ? $_POST['eyes_inner_color'] : '#000000';
                $settings['eyes_outer_color'] = $_POST['eyes_outer_color'] = isset($_POST['eyes_outer_color']) && preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_POST['eyes_outer_color']) ? $_POST['eyes_outer_color'] : '#000000';
            }

            $_POST['qr_code_logo'] = !empty($_FILES['qr_code_logo']['name']) && !isset($_POST['qr_code_logo_remove']);
            $settings['qr_code_logo_size'] = $_POST['qr_code_logo_size'] = isset($_POST['qr_code_logo_size']) && in_array($_POST['qr_code_logo_size'], range(5, 35)) ? (int) $_POST['qr_code_logo_size'] : 25;

            $settings['size'] = $_POST['size'] = isset($_POST['size']) && in_array($_POST['size'], range(50, 2000)) ? (int) $_POST['size'] : 500;
            $settings['margin'] = $_POST['margin'] = isset($_POST['margin']) && in_array($_POST['margin'], range(0, 25)) ? (int) $_POST['margin'] : 0;
            $settings['ecc'] = $_POST['ecc'] = isset($_POST['ecc']) && in_array($_POST['ecc'], ['L', 'M', 'Q', 'H']) ? $_POST['ecc'] : 'L';

            /* Type dependant vars */
            switch($_POST['type']) {
                case 'text':
                    $required_fields[] = 'text';
                    $settings['text'] = $_POST['text'] = mb_substr(input_clean($_POST['text']), 0, $qr_code_settings['type']['text']['max_length']);
                    break;

                case 'url':
                    $required_fields[] = 'url';
                    $settings['url'] = $_POST['url'] = mb_substr(input_clean($_POST['url']), 0, $qr_code_settings['type']['url']['max_length']);
                    break;

                case 'phone':
                    $required_fields[] = 'phone';
                    $settings['phone'] = $_POST['phone'] = mb_substr(input_clean($_POST['phone']), 0, $qr_code_settings['type']['phone']['max_length']);
                    break;

                case 'sms':
                    $required_fields[] = 'sms';
                    $settings['sms'] = $_POST['sms'] = mb_substr(input_clean($_POST['sms']), 0, $qr_code_settings['type']['sms']['max_length']);
                    $settings['sms_body'] = $_POST['sms_body'] = mb_substr(input_clean($_POST['sms_body']), 0, $qr_code_settings['type']['sms']['body']['max_length']);
                    break;

                case 'email':
                    $required_fields[] = 'email';
                    $settings['email'] = $_POST['email'] = mb_substr(input_clean($_POST['email']), 0, $qr_code_settings['type']['email']['max_length']);
                    $settings['email_subject'] = $_POST['email_subject'] = mb_substr(input_clean($_POST['email_subject']), 0, $qr_code_settings['type']['email']['subject']['max_length']);
                    $settings['email_body'] = $_POST['email_body'] = mb_substr(input_clean($_POST['email_body']), 0, $qr_code_settings['type']['email']['body']['max_length']);
                    break;

                case 'whatsapp':
                    $required_fields[] = 'whatsapp';
                    $settings['whatsapp'] = $_POST['whatsapp'] = (int) input_clean($_POST['whatsapp'], $qr_code_settings['type']['whatsapp']['max_length']);
                    $settings['whatsapp_body'] = $_POST['whatsapp_body'] = input_clean($_POST['whatsapp_body'], $qr_code_settings['type']['whatsapp']['body']['max_length']);
                    break;

                case 'facetime':
                    $required_fields[] = 'facetime';
                    $settings['facetime'] = $_POST['facetime'] = mb_substr(input_clean($_POST['facetime']), 0, $qr_code_settings['type']['facetime']['max_length']);
                    break;

                case 'location':
                    $required_fields[] = 'location_latitude';
                    $required_fields[] = 'location_longitude';
                    $settings['location_latitude'] = $_POST['location_latitude'] = (float) mb_substr(input_clean($_POST['location_latitude']), 0, $qr_code_settings['type']['location']['latitude']['max_length']);
                    $settings['location_longitude'] = $_POST['location_longitude'] = (float) mb_substr(input_clean($_POST['location_longitude']), 0, $qr_code_settings['type']['location']['longitude']['max_length']);
                    break;

                case 'wifi':
                    $required_fields[] = 'wifi_ssid';
                    $settings['wifi_ssid'] = $_POST['wifi_ssid'] = mb_substr(input_clean($_POST['wifi_ssid']), 0, $qr_code_settings['type']['wifi']['ssid']['max_length']);
                    $settings['wifi_encryption'] = $_POST['wifi_encryption'] = isset($_POST['wifi_encryption']) && in_array($_POST['wifi_encryption'], ['nopass', 'WEP', 'WPA/WPA2']) ? $_POST['wifi_encryption'] : 'nopass';
                    $settings['wifi_password'] = $_POST['wifi_password'] = mb_substr(input_clean($_POST['wifi_password']), 0, $qr_code_settings['type']['wifi']['password']['max_length']);
                    $settings['wifi_is_hidden'] = $_POST['wifi_is_hidden'] = (int) $_POST['wifi_is_hidden'];
                    break;

                case 'event':
                    $required_fields[] = 'event';
                    $settings['event'] = $_POST['event'] = mb_substr(input_clean($_POST['event']), 0, $qr_code_settings['type']['event']['max_length']);
                    $settings['event_location'] = $_POST['event_location'] = mb_substr(input_clean($_POST['event_location']), 0, $qr_code_settings['type']['event']['location']['max_length']);
                    $settings['event_url'] = $_POST['event_url'] = mb_substr(input_clean($_POST['event_url']), 0, $qr_code_settings['type']['event']['url']['max_length']);
                    $settings['event_note'] = $_POST['event_note'] = mb_substr(input_clean($_POST['event_note']), 0, $qr_code_settings['type']['event']['note']['max_length']);
                    $settings['event_timezone'] = $_POST['event_timezone'] = in_array($_POST['event_timezone'], \DateTimeZone::listIdentifiers()) ? input_clean($_POST['event_timezone']) : Date::$default_timezone;
                    $settings['event_start_datetime'] = $_POST['event_start_datetime'] = (new \DateTime($_POST['event_start_datetime']))->format('Y-m-d\TH:i:s');
                    $settings['event_end_datetime'] = $_POST['event_end_datetime'] = (new \DateTime($_POST['event_end_datetime']))->format('Y-m-d\TH:i:s');
                    break;

                case 'crypto':
                    $required_fields[] = 'crypto_address';
                    $settings['crypto_coin'] = $_POST['crypto_coin'] = isset($_POST['crypto_coin']) && array_key_exists($_POST['crypto_coin'], $qr_code_settings['type']['crypto']['coins']) ? $_POST['crypto_coin'] : array_key_first($qr_code_settings['type']['crypto']['coins']);
                    $settings['crypto_address'] = $_POST['crypto_address'] = mb_substr(input_clean($_POST['crypto_address']), 0, $qr_code_settings['type']['crypto']['address']['max_length']);
                    $settings['crypto_amount'] = $_POST['crypto_amount'] = isset($_POST['crypto_amount']) ? (float) $_POST['crypto_amount'] : null;
                    break;

                case 'vcard':
                    $settings['vcard_first_name'] = $_POST['vcard_first_name'] = mb_substr(input_clean($_POST['vcard_first_name']), 0, $qr_code_settings['type']['vcard']['first_name']['max_length']);
                    $settings['vcard_last_name'] = $_POST['vcard_last_name'] = mb_substr(input_clean($_POST['vcard_last_name']), 0, $qr_code_settings['type']['vcard']['last_name']['max_length']);
                    $settings['vcard_email'] = $_POST['vcard_email'] = mb_substr(input_clean($_POST['vcard_email']), 0, $qr_code_settings['type']['vcard']['email']['max_length']);
                    $settings['vcard_url'] = $_POST['vcard_url'] = mb_substr(input_clean($_POST['vcard_url']), 0, $qr_code_settings['type']['vcard']['url']['max_length']);
                    $settings['vcard_company'] = $_POST['vcard_company'] = mb_substr(input_clean($_POST['vcard_company']), 0, $qr_code_settings['type']['vcard']['company']['max_length']);
                    $settings['vcard_job_title'] = $_POST['vcard_job_title'] = mb_substr(input_clean($_POST['vcard_job_title']), 0, $qr_code_settings['type']['vcard']['job_title']['max_length']);
                    $settings['vcard_birthday'] = $_POST['vcard_birthday'] = mb_substr(input_clean($_POST['vcard_birthday']), 0, $qr_code_settings['type']['vcard']['birthday']['max_length']);
                    $settings['vcard_street'] = $_POST['vcard_street'] = mb_substr(input_clean($_POST['vcard_street']), 0, $qr_code_settings['type']['vcard']['street']['max_length']);
                    $settings['vcard_city'] = $_POST['vcard_city'] = mb_substr(input_clean($_POST['vcard_city']), 0, $qr_code_settings['type']['vcard']['city']['max_length']);
                    $settings['vcard_zip'] = $_POST['vcard_zip'] = mb_substr(input_clean($_POST['vcard_zip']), 0, $qr_code_settings['type']['vcard']['zip']['max_length']);
                    $settings['vcard_region'] = $_POST['vcard_region'] = mb_substr(input_clean($_POST['vcard_region']), 0, $qr_code_settings['type']['vcard']['region']['max_length']);
                    $settings['vcard_country'] = $_POST['vcard_country'] = mb_substr(input_clean($_POST['vcard_country']), 0, $qr_code_settings['type']['vcard']['country']['max_length']);
                    $settings['vcard_note'] = $_POST['vcard_note'] = mb_substr(input_clean($_POST['vcard_note']), 0, $qr_code_settings['type']['vcard']['note']['max_length']);

                    /* Phone numbers */
                    if(!isset($_POST['vcard_phone_numbers'])) {
                        $_POST['vcard_phone_numbers'] = [];
                    }
                    $vcard_phone_numbers = [];
                    foreach($_POST['vcard_phone_numbers'] as $key => $value) {
                        if(empty(trim($value))) continue;
                        if($key >= 20) continue;

                        $vcard_phone_numbers[] = mb_substr(input_clean($value), 0, $qr_code_settings['type']['vcard']['phone_number']['max_length']);
                    }
                    $settings['vcard_phone_numbers'] = $vcard_phone_numbers;

                    /* Socials */
                    if(!isset($_POST['vcard_social_label'])) {
                        $_POST['vcard_social_label'] = [];
                        $_POST['vcard_social_value'] = [];
                    }

                    $vcard_socials = [];
                    foreach($_POST['vcard_social_label'] as $key => $value) {
                        if(empty(trim($value))) continue;
                        if($key >= 20) continue;

                        $vcard_socials[] = [
                            'label' => mb_substr(input_clean($value), 0, $qr_code_settings['type']['vcard']['social_value']['max_length']),
                            'value' => mb_substr(input_clean($_POST['vcard_social_value'][$key]), 0, $qr_code_settings['type']['vcard']['social_value']['max_length'])
                        ];
                    }
                    $settings['vcard_socials'] = $vcard_socials;
                    break;

                case 'paypal':
                    $required_fields[] = 'paypal_email';
                    $required_fields[] = 'paypal_title';
                    $required_fields[] = 'paypal_currency';
                    $required_fields[] = 'paypal_price';
                    $settings['paypal_type'] = $_POST['paypal_type'] = isset($_POST['paypal_type']) && array_key_exists($_POST['paypal_type'], $qr_code_settings['type']['paypal']['type']) ? $_POST['paypal_type'] : array_key_first($qr_code_settings['type']['paypal']['type']);
                    $settings['paypal_email'] = $_POST['paypal_email'] = mb_substr(input_clean($_POST['paypal_email']), 0, $qr_code_settings['type']['paypal']['email']['max_length']);
                    $settings['paypal_title'] = $_POST['paypal_title'] = mb_substr(input_clean($_POST['paypal_title']), 0, $qr_code_settings['type']['paypal']['title']['max_length']);
                    $settings['paypal_currency'] = $_POST['paypal_currency'] = mb_substr(input_clean($_POST['paypal_currency']), 0, $qr_code_settings['type']['paypal']['currency']['max_length']);
                    $settings['paypal_price'] = $_POST['paypal_price'] = (float) $_POST['paypal_price'];
                    $settings['paypal_thank_you_url'] = $_POST['paypal_thank_you_url'] = mb_substr(input_clean($_POST['paypal_thank_you_url']), 0, $qr_code_settings['type']['paypal']['thank_you_url']['max_length']);
                    $settings['paypal_cancel_url'] = $_POST['paypal_cancel_url'] = mb_substr(input_clean($_POST['paypal_cancel_url']), 0, $qr_code_settings['type']['paypal']['cancel_url']['max_length']);
                    break;
            }


            $qr_code_logo = null;

            $qr_code_settings = require APP_PATH . 'includes/qr_codes.php';

        /* Process variables */
        $_POST['type'] = isset($_POST['type']) && array_key_exists($_POST['type'], $available_qr_codes) ? $_POST['type'] : 'text';
        $_GET['style'] = isset($_GET['style']) && in_array($_GET['style'], ['square', 'dot', 'round', 'diamond', 'heart']) ? $_GET['style'] : 'square';
        $_GET['inner_eye_style'] = isset($_GET['inner_eye_style']) && in_array($_GET['inner_eye_style'], ['square', 'dot', 'rounded', 'diamond', 'flower', 'leaf',]) ? $_GET['inner_eye_style'] : 'square';
        $_GET['outer_eye_style'] = isset($_GET['outer_eye_style']) && in_array($_GET['outer_eye_style'], ['square', 'circle', 'rounded', 'flower', 'leaf',]) ? $_GET['outer_eye_style'] : 'leaf';
        $_GET['foreground_type'] = isset($_GET['foreground_type']) && in_array($_GET['foreground_type'], ['color', 'gradient']) ? $_GET['foreground_type'] : 'color';
        $_GET['background_color'] = '#ffffff';
        $_GET['background_color_transparency'] = isset($_GET['background_color_transparency']) && in_array($_GET['background_color_transparency'], range(0, 100)) ? (int) $_GET['background_color_transparency'] : 100;
        $_GET['custom_eyes_color'] = 0;
        if($_GET['custom_eyes_color']) {
            $_GET['eyes_inner_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_GET['eyes_inner_color']) ? null : $_GET['eyes_inner_color'];
            $_GET['eyes_outer_color'] = !preg_match('/#([A-Fa-f0-9]{3,4}){1,2}\b/i', $_GET['eyes_outer_color']) ? null : $_GET['eyes_outer_color'];
        }
        $qr_code_logo = !empty($_FILES['qr_code_logo']['name']) && !isset($_GET['qr_code_logo_remove']);
        $_GET['qr_code_logo'] = $_GET['qr_code_logo'] ?? null;
        $_GET['qr_code_logo_size'] = isset($_GET['qr_code_logo_size']) && in_array($_GET['qr_code_logo_size'], range(5, 35)) ? (int) $_GET['qr_code_logo_size'] : 25;
        $_GET['size'] = isset($_GET['size']) && in_array($_GET['size'], range(50, 2000)) ? (int) $_GET['size'] : 500;
        $_GET['margin'] = isset($_GET['margin']) && in_array($_GET['margin'], range(0, 25)) ? (int) $_GET['margin'] : 0;
        $_GET['ecc'] = isset($_GET['ecc']) && in_array($_GET['ecc'], ['L', 'M', 'Q', 'H']) ? $_GET['ecc'] : 'L';

        switch($_POST['type']) {
            case 'text':
                //$_GET['text'] = input_clean($_GET['text']);
                $data = $_GET['text'];
                break;

            case 'url':
                $_POST['url'] = filter_var($_POST['url'], FILTER_SANITIZE_URL);
                $data = $_POST['url'];
                break;

            case 'phone':
                //$_GET['phone'] = input_clean($_GET['phone']);
                $data = 'tel:' . $_GET['phone'];
                break;

            case 'sms':
                //$_GET['sms'] = input_clean($_GET['sms']);
                //$_GET['sms_body'] = input_clean($_GET['sms_body']);
                $data = 'SMSTO:' . $_GET['sms'] . ':' . $_GET['sms_body'];
                break;

            case 'email':
                $_GET['email'] = input_clean($_GET['email']);
                //$_GET['email_subject'] = input_clean($_GET['email_subject']);
                //$_GET['email_body'] = input_clean($_GET['email_body']);
                $data = 'MATMSG:TO:' . $_GET['email'] . ';SUB:' . $_GET['email_subject'] . ';BODY:' . $_GET['email_body'] . ';;';
                break;

            case 'whatsapp':
                //$_GET['whatsapp'] = input_clean($_GET['whatsapp']);
                //$_GET['whatsapp_body'] = input_clean($_GET['whatsapp_body']);
                $data = 'https://wa.me/' . $_GET['whatsapp'] . '?text=' . urlencode($_GET['whatsapp_body']);
                break;

            case 'facetime':
                //$_GET['facetime'] = input_clean($_GET['facetime']);
                $data = 'facetime:' . $_GET['facetime'];
                break;

            case 'location':
                $_GET['location_latitude'] = (float) $_GET['location_latitude'];
                $_GET['location_longitude'] = (float) $_GET['location_longitude'];
                $data = 'geo:' . $_GET['location_latitude'] . ',' . $_GET['location_longitude'] . '?q=' . $_GET['location_latitude'] . ',' . $_GET['location_longitude'];
                break;

            case 'wifi':
                //$_GET['wifi_ssid'] = input_clean($_GET['wifi_ssid']);
                $_GET['wifi_encryption'] = isset($_GET['wifi_encryption']) && in_array($_GET['wifi_encryption'], ['nopass', 'WEP', 'WPA/WPA2']) ? $_GET['wifi_encryption'] : 'nopass';
                if($_GET['wifi_encryption'] == 'WPA/WPA2') $_GET['wifi_encryption'] = 'WPA';
                //$_GET['wifi_password'] = input_clean($_GET['wifi_password']);
                $_GET['wifi_is_hidden'] = (int) $_GET['wifi_is_hidden'];

                $data_to_be_rendered = 'WIFI:S:' . $_GET['wifi_ssid'] . ';';
                $data_to_be_rendered .= 'T:' . $_GET['wifi_encryption'] . ';';
                if($_GET['wifi_password']) $data_to_be_rendered .= 'P:' . $_GET['wifi_password'] . ';';
                if($_GET['wifi_is_hidden']) $data_to_be_rendered .= 'H:' . (bool) $_GET['wifi_is_hidden'] . ';';
                $data_to_be_rendered .= ';';

                $data = $data_to_be_rendered;
                break;

            case 'event':
                //$_GET['event'] = input_clean($_GET['event']);
                //$_GET['event_location'] = input_clean($_GET['event_location']);
                $_GET['event_url'] = filter_var($_GET['event_url'], FILTER_SANITIZE_URL);
                //$_GET['event_note'] = input_clean($_GET['event_note']);
                //$_GET['event_timezone'] = input_clean($_GET['event_timezone']);
                $_GET['event_start_datetime'] = (new \DateTime($_GET['event_start_datetime']))->format('Ymd\THis\Z');
                $_GET['event_end_datetime'] = empty($_GET['event_end_datetime']) ? null : (new \DateTime($_GET['event_end_datetime']))->format('Ymd\THis\Z');

                $data_to_be_rendered = 'BEGIN:VEVENT' . "\n";
                $data_to_be_rendered .= 'SUMMARY:' . $_GET['event'] . "\n";
                $data_to_be_rendered .= 'LOCATION:' . $_GET['event_location'] . "\n";
                $data_to_be_rendered .= 'URL:' . $_GET['event_url'] . "\n";
                $data_to_be_rendered .= 'DESCRIPTION:' . $_GET['event_note'] . "\n";
                $data_to_be_rendered .= 'DTSTART;TZID=' . $_GET['event_timezone'] . ':' . $_GET['event_start_datetime'] . "\n";
                if($_GET['event_end_datetime']) $data_to_be_rendered .= 'DTEND;TZID=' . $_GET['event_timezone'] . ':' . $_GET['event_end_datetime'] . "\n";
                $data_to_be_rendered .= 'END:VEVENT';

                $data = $data_to_be_rendered;
                break;

            case 'crypto':
                $_GET['crypto_coin'] = isset($_GET['crypto_coin']) && array_key_exists($_GET['crypto_coin'], $qr_code_settings['type']['crypto']['coins']) ? $_GET['crypto_coin'] : array_key_first($qr_code_settings['type']['crypto']['coins']);;
                //$_GET['crypto_address'] = input_clean($_GET['crypto_address']);
                $_GET['crypto_amount'] = isset($_GET['crypto_amount']) ? (float) $_GET['crypto_amount'] : null;
                $data = $_GET['crypto_coin'] . ':' . $_GET['crypto_address'] . ($_GET['crypto_amount'] ? '?amount=' . $_GET['crypto_amount'] : null);

                break;

            case 'vcard':
                $_GET['vcard_email'] = filter_var($_GET['vcard_email'], FILTER_SANITIZE_EMAIL);
                $_GET['vcard_url'] = filter_var($_GET['vcard_url'], FILTER_SANITIZE_URL);

                if(!isset($_GET['vcard_phone_numbers'])) {
                    $_GET['vcard_phone_numbers'] = [];
                }

                if(!isset($_GET['vcard_social_label'])) {
                    $_GET['vcard_social_label'] = [];
                    $_GET['vcard_social_value'] = [];
                }

                $vcard = new \JeroenDesloovere\VCard\VCard();
                $vcard->addName($_GET['vcard_last_name'], $_GET['vcard_first_name']);
                $vcard->addAddress(null, null, $_GET['vcard_street'], $_GET['vcard_city'], $_GET['vcard_region'], $_GET['vcard_zip'], $_GET['vcard_country']);
                if($_GET['vcard_email']) $vcard->addEmail($_GET['vcard_email']);
                if($_GET['vcard_url']) $vcard->addURL($_GET['vcard_url']);
                if($_GET['vcard_company']) $vcard->addCompany($_GET['vcard_company']);
                if($_GET['vcard_job_title']) $vcard->addJobtitle($_GET['vcard_job_title']);
                if($_GET['vcard_birthday']) $vcard->addBirthday($_GET['vcard_birthday']);
                if($_GET['vcard_note']) $vcard->addNote($_GET['vcard_note']);

                /* Phone numbers */
                foreach($_GET['vcard_phone_numbers'] as $key => $value) {
                    if(empty(trim($value))) continue;
                    if($key >= 20) continue;
                    $phone_number = mb_substr($value, 0, $qr_code_settings['type']['vcard']['phone_number']['max_length']);
                    $vcard->addPhoneNumber($phone_number);
                }

                /* Socials */
                foreach($_GET['vcard_social_label'] as $key => $value) {
                    if(empty(trim($value))) continue;
                    if($key >= 20) continue;

                    $label = mb_substr($value, 0, $qr_code_settings['type']['vcard']['social_value']['max_length']);
                    $value = mb_substr($_GET['vcard_social_value'][$key], 0, $qr_code_settings['type']['vcard']['social_value']['max_length']);

                    $vcard->addURL(
                        $value,
                        'TYPE=' . $label
                    );
                }

                $data = $vcard->buildVCard();
                break;

            case 'paypal':
                $_GET['paypal_type'] = isset($_GET['paypal_type']) && array_key_exists($_GET['paypal_type'], $qr_code_settings['type']['paypal']['type']) ? $_GET['paypal_type'] : array_key_first($qr_code_settings['type']['paypal']['type']);;
                //$_GET['paypal_email'] = filter_var($_GET['paypal_email'], FILTER_SANITIZE_EMAIL);
                //$_GET['paypal_title'] = input_clean($_GET['paypal_title']);
                //$_GET['paypal_currency'] = input_clean($_GET['paypal_currency']);
                $_GET['paypal_price'] = (float) $_GET['paypal_price'];
                $_GET['paypal_thank_you_url'] = filter_var($_GET['paypal_thank_you_url'], FILTER_SANITIZE_URL);
                $_GET['paypal_cancel_url'] = filter_var($_GET['paypal_cancel_url'], FILTER_SANITIZE_URL);

                if($_GET['paypal_type'] == 'add_to_cart') {
                    $data = sprintf('https://www.paypal.com/cgi-bin/webscr?business=%s&cmd=%s&currency_code=%s&amount=%s&item_name=%s&button_subtype=products&add=1&return=%s&cancel_return=%s', $_GET['paypal_email'], $qr_code_settings['type']['paypal']['type'][$_GET['paypal_type']], $_GET['paypal_currency'], $_GET['paypal_price'], $_GET['paypal_title'], $_GET['paypal_thank_you_url'], $_GET['paypal_cancel_url']);
                } else {
                    $data = sprintf('https://www.paypal.com/cgi-bin/webscr?business=%s&cmd=%s&currency_code=%s&amount=%s&item_name=%s&return=%s&cancel_return=%s', $_GET['paypal_email'], $qr_code_settings['type']['paypal']['type'][$_GET['paypal_type']], $_GET['paypal_currency'], $_GET['paypal_price'], $_GET['paypal_title'], $_GET['paypal_thank_you_url'], $_GET['paypal_cancel_url']);
                }

                break;
        }

        /* :) */
        $qr = new \SimpleSoftwareIO\QrCode\Generator;
        $qr->size($_GET['size']);
        $qr->errorCorrection($_GET['ecc']);
        $qr->encoding('UTF-8');
        $qr->margin($_GET['margin']);

        /* Style */
        switch($_GET['style']) {
            case 'heart':
                $qr->style(\Altum\QrCodes\HeartModule::class, 0.8);
                break;

            case 'diamond':
                $qr->style(\Altum\QrCodes\DiamondModule::class, 0.9);
                break;

            default:
                $qr->style($_GET['style'], 0.9);
                break;
        }

        $qr->eye(\Altum\QrCodes\EyeCombiner::instance($_GET['inner_eye_style'], $_GET['outer_eye_style']));

        /* Colors */
        $background_color = hex_to_rgb($_GET['background_color']);
        $qr->backgroundColor($background_color['r'], $background_color['g'], $background_color['b'], 100 - $_GET['background_color_transparency']);

        /* Eyes */
        if($_GET['custom_eyes_color']) {
            $eyes_inner_color = hex_to_rgb($_GET['eyes_inner_color']);
            $eyes_outer_color = hex_to_rgb($_GET['eyes_outer_color']);

            $qr->eyeColor(0, $eyes_outer_color['r'], $eyes_outer_color['g'], $eyes_outer_color['b'], $eyes_inner_color['r'], $eyes_inner_color['g'], $eyes_inner_color['b']);
            $qr->eyeColor(1, $eyes_outer_color['r'], $eyes_outer_color['g'], $eyes_outer_color['b'], $eyes_inner_color['r'], $eyes_inner_color['g'], $eyes_inner_color['b']);
            $qr->eyeColor(2, $eyes_outer_color['r'], $eyes_outer_color['g'], $eyes_outer_color['b'], $eyes_inner_color['r'], $eyes_inner_color['g'], $eyes_inner_color['b']);
        }

        /* Foreground */
        switch($_GET['foreground_type']) {
            case 'color':
                $_GET['foreground_color'] = '#000000';
                $foreground_color = hex_to_rgb($_GET['foreground_color']);
                $qr->color($foreground_color['r'], $foreground_color['g'], $foreground_color['b']);
                break;

            case 'gradient':
                $_GET['foreground_gradient_style'] = isset($_GET['foreground_gradient_style']) && in_array($_GET['foreground_gradient_style'], ['vertical', 'horizontal', 'diagonal', 'inverse_diagonal', 'radial']) ? $_GET['foreground_gradient_style'] : 'horizontal';
                $foreground_gradient_one = hex_to_rgb($_GET['foreground_gradient_one']);
                $foreground_gradient_two = hex_to_rgb($_GET['foreground_gradient_two']);
                $qr->gradient($foreground_gradient_one['r'], $foreground_gradient_one['g'], $foreground_gradient_one['b'], $foreground_gradient_two['r'], $foreground_gradient_two['g'], $foreground_gradient_two['b'], $_GET['foreground_gradient_style']);
                break;
        }

        /* Generate the first SVG */
        try {
            $svg = $qr->generate($data);
        } catch (\Exception $exception) {
            Response::json($exception->getMessage(), 'error');
        }

        if(($_GET['qr_code_logo'] || $qr_code_logo) && !isset($_GET['qr_code_logo_remove'])) {
            $logo_width_percentage = $_GET['qr_code_logo_size'];

            /* Start doing custom changes to the output SVG */
            $custom_svg_object = SVG::fromString($svg);
            $custom_svg_doc = $custom_svg_object->getDocument();

            /* Already existing qr code logo */
            if($_GET['qr_code_logo']) {
                $qr_code_logo_name = $_GET['qr_code_logo'];
                $qr_code_logo_link = $_GET['qr_code_logo'];
            }

            /* Freshly uploaded qr code logo */
            if($qr_code_logo) {
                $qr_code_logo_name = $_FILES['qr_code_logo']['name'];
                $file_extension = explode('.', $qr_code_logo_name);
                $file_extension = mb_strtolower(end($file_extension));
                $qr_code_logo_link = $_FILES['qr_code_logo']['tmp_name'];

                if($_FILES['qr_code_logo']['error'] == UPLOAD_ERR_INI_SIZE) {
                    Alerts::add_error(sprintf(l('global.error_message.file_size_limit'), $qr_code_settings['qr_code_logo_size_limit']));
                }

                if($_FILES['qr_code_logo']['error'] && $_FILES['qr_code_logo']['error'] != UPLOAD_ERR_INI_SIZE) {
                    Alerts::add_error(l('global.error_message.file_upload'));
                }

                if(!in_array($file_extension, Uploads::get_whitelisted_file_extensions('qr_code_logo'))) {
                    Alerts::add_error(l('global.error_message.invalid_file_type'));
                }

                if(!\Altum\Plugin::is_active('offload') || (\Altum\Plugin::is_active('offload') && !settings()->offload->uploads_url)) {
                    if(!is_writable(UPLOADS_PATH . 'qr_code_logo' . '/')) {
                        Response::json(sprintf(l('global.error_message.directory_not_writable'), UPLOADS_PATH . 'qr_code_logo' . '/'), 'error');
                    }
                }

                if($_FILES['qr_code_logo']['size'] > $qr_code_settings['qr_code_logo_size_limit'] * 1000000) {
                    Response::json(sprintf(l('global.error_message.file_size_limit'), $qr_code_settings['qr_code_logo_size_limit']), 'error');
                }
            }

            /* Process uploaded logo image */
            $qr_code_logo_extension = explode('.', $qr_code_logo_name);
            $qr_code_logo_extension = mb_strtolower(end($qr_code_logo_extension));
            $logo = file_get_contents($qr_code_logo_link);
            $logo_base64 = 'data:image/' . $qr_code_logo_extension . ';base64,' . base64_encode($logo);

            /* Size of the logo */
            list($logo_width, $logo_height) = getimagesize($qr_code_logo_link);
            $logo_ratio = $logo_height / $logo_width;
            $logo_new_width = $_GET['size'] * $logo_width_percentage / 100;
            $logo_new_height = $logo_new_width * $logo_ratio;

            /* Calculate center of the qr code */
            $logo_x = $_GET['size'] / 2 - $logo_new_width / 2;
            $logo_y = $_GET['size'] / 2 - $logo_new_height / 2;

            /* Add the logo to the QR code */
            $logo = new SVGImage($logo_base64, $logo_x, $logo_y, $logo_new_width, $logo_new_height);
            $custom_svg_doc->addChild($logo);

            /* Export the qr code with the logo on top */
            $svg = $custom_svg_object->toXMLString();
        }

        $_POST['qr_code'] = 'data:image/svg+xml;base64,' . base64_encode($svg);

        $qr_code = null;

        /* QR Code image */
        if($_POST['qr_code']) {
            $_POST['qr_code'] = base64_decode(mb_substr($_POST['qr_code'], mb_strlen('data:image/svg+xml;base64,')));

            /* Generate new name for image */
            $image_new_name = md5(time() . rand()) . '.svg';

            /* Offload uploading */
            if(\Altum\Plugin::is_active('offload') && settings()->offload->uploads_url) {
                try {
                    $s3 = new \Aws\S3\S3Client(get_aws_s3_config());

                    /* Upload image */
                    $result = $s3->putObject([
                        'Bucket' => settings()->offload->storage_name,
                        'Key' => 'uploads/qr_code/' . $image_new_name,
                        'ContentType' => 'image/svg+xml',
                        'Body' => $_POST['qr_code'],
                        'ACL' => 'public-read'
                    ]);
                } catch (\Exception $exception) {
                    Alerts::add_error($exception->getMessage());
                }
            }

            /* Local uploading */
            else {
                /* Upload the original */
                file_put_contents(UPLOADS_PATH . 'qr_code' . '/' . $image_new_name, $_POST['qr_code']);
            }

            $qr_code = $image_new_name;
        }

        $settings = json_encode($settings);

        /* Database query */
        $qr_code_id = db()->insert('qr_codes', [
            'user_id' => $user->user_id,
            'project_id' => $_POST['project_id'],
            'name' => $user->name,
            'type' => $_POST['type'],
            'settings' => $settings,
            'qr_code' => $qr_code,
            'qr_code_logo' => $qr_code_logo,
            'datetime' => \Altum\Date::$date,
        ]);                
    }

        $name = mb_strtoupper($user->name);        
        $address = '';
        $city = '';
        $country = 'Hrvatska';

        if (isset($meta->address)) {
            $address = $meta->address;
        }
        
        if (isset($meta->zip) && isset($meta->city)) {
            $city = $meta->zip . ' ' . $meta->city;
        }
        
        if (isset($meta->country)) {
            if (strtolower($meta->country) != 'hrvatska') {
                $country = ucfirst($meta->country);
            }
        }            
            
        $pdf = new \TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();        
        $pdf->SetAutoPageBreak(0);
        $pdf->SetFont('DejaVuSansCondensed', 'B', 20);                    
        $pdf->ln(44);
        $pdf->Cell(10);
        $pdf->Cell(75, 8, $name, 0);                     
        $pdf->SetFont('DejaVuSansCondensed', 'B', 13);   
        $pdf->ln(9);
        $pdf->Cell(10);
        $pdf->Cell(75, 8, $address, 0);                     
        $pdf->ln(7);
        $pdf->Cell(10);
        $pdf->Cell(75, 8, $city, 0);                     
        $pdf->ln(7);
        $pdf->Cell(10);
        $pdf->Cell(75, 8, $country, 0);                     
        $pdf->Output(UPLOADS_PATH . 'qr_code/' . $user->user_id . '.pdf', 'F');    
        
        $code = db()->where('user_id', $user_id)->getOne('qr_codes', ['qr_code', 'name']);
        $name = $code->qr_code;
        $user = $code->name;

        /*$svg = file_get_contents(UPLOADS_PATH . 'qr_code/' . $name);

        $im = new \Imagick();            
        $im->setBackgroundColor(new \ImagickPixel('transparent'));
        $im->readImageBlob($svg);
        $im->setImageUnits(\Imagick::RESOLUTION_PIXELSPERINCH);
        $im->setResolution(300, 300);
        $im->setImageFormat('png');
        $im->resizeImage(230, 230, \Imagick::FILTER_LANCZOS, 1);  
        $im->writeImage(UPLOADS_PATH . 'qr_code/' . pathinfo($name, PATHINFO_FILENAME) . '.png');*/
        
        $svg = UPLOADS_PATH . 'qr_code/' . $name;
        $png = UPLOADS_PATH . 'qr_code/' . pathinfo($name, PATHINFO_FILENAME) . '.png';

        $magick_binary = trim((string) shell_exec('command -v magick'));
        $convert_binary = trim((string) shell_exec('command -v convert'));

        if($magick_binary) {
            exec(escapeshellarg($magick_binary) . ' convert -density 300x300 -background none ' . escapeshellarg($svg) . ' ' . escapeshellarg($png));
        } elseif($convert_binary) {
            exec(escapeshellarg($convert_binary) . ' -density 300x300 -background none ' . escapeshellarg($svg) . ' ' . escapeshellarg($png));
        }

        $pdf = new \TCPDF('L', 'mm', ['85.51', '54.02'], true, 'UTF-8');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();        
        $pdf->SetAutoPageBreak(0);
        $pdf->SetFont('DejaVuSansCondensed', 'B', 10);
        $pdf->Image(UPLOADS_PATH . 'qr_code/' . pathinfo($name, PATHINFO_FILENAME) . '.png', 33.5, 17.5, 19, 19);
        $user = mb_strtoupper($user);
        $pdf->ln(29);
        $pdf->Cell(65, 8, $user, 0, 1, 'C');                     
        $pdf->Output(UPLOADS_PATH . 'qr_code/' . pathinfo($name, PATHINFO_FILENAME) . '.pdf', 'F');
        
        /*$myurl = UPLOADS_PATH . 'qr_code/' . pathinfo($name, PATHINFO_FILENAME) . '.pdf[0]';
        $image = new \Imagick();        
        $image->setResolution(300, 300);
        $image->readImage($myurl);                
        $image->setImageFormat( 'png' );
        $image->writeImage(UPLOADS_PATH . 'qr_code/'. pathinfo($name, PATHINFO_FILENAME) . '.png');*/
        
        $pdf = UPLOADS_PATH . 'qr_code/' . pathinfo($name, PATHINFO_FILENAME) . '.pdf';

        if($magick_binary) {
            exec(escapeshellarg($magick_binary) . ' convert -density 300x300 ' . escapeshellarg($pdf) . ' ' . escapeshellarg($png));
        } elseif($convert_binary) {
            exec(escapeshellarg($convert_binary) . ' -density 300x300 ' . escapeshellarg($pdf) . ' ' . escapeshellarg($png));
        }
    }

}
