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

use Altum\Alerts;

defined('ALTUMCODE') || die();

class FccEducation extends Controller {

    /* Custom code: FC-2026-02-24: FCC core education page */
    public function index() {

        \Altum\Authentication::guard();

        /* Custom code: FC-2026-02-25: allow admin to view education */
        if($this->user->type != 0 && !\Altum\Authentication::is_admin()) {
            redirect('dashboard');
        }
        /* /Custom code: FC-2026-02-25 */

        /* Custom code: FC-2026-02-24: FCC education settings */
        $language_name = $this->user->language ?? \Altum\Language::$default_name;
        $fcc_settings = settings()->fcc_education ?? null;
        $education_settings = $fcc_settings->education_by_language->{$language_name} ?? null;
        if(is_array($education_settings)) {
            $education_settings = (object) $education_settings;
        }

        $education_enabled = isset($education_settings->enabled) ? (bool) $education_settings->enabled : true;
        /* /Custom code: FC-2026-02-24 */

        $is_completed = \Altum\Authentication::is_fcc_core_completed();

        $education_videos = $education_settings->videos ?? [];
        $education_videos = is_array($education_videos) || is_object($education_videos) ? (array) $education_videos : [];
        $video_count = count($education_videos);

        if(!$education_enabled) {
            redirect('dashboard');
        }

        if(!empty($_POST)) {
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if(!$is_completed && !Alerts::has_errors()) {
                $preferences = $this->user->preferences ?? '{}';
                if(is_string($preferences)) {
                    $preferences = json_decode($preferences);
                }
                if(is_array($preferences)) {
                    $preferences = (object) $preferences;
                }

                $meta = $preferences->meta ?? new \stdClass();
                if(is_array($meta)) {
                    $meta = (object) $meta;
                }

                $current_index = (int) ($meta->fcc_core_progress ?? 0);
                if($current_index < 0) {
                    $current_index = 0;
                }

                if($video_count > 0) {
                    $posted_index = (int) ($_POST['video_index'] ?? -1);
                    if($posted_index !== $current_index) {
                        Alerts::add_error(l('fcc_education.error_invalid_step'));
                    }
                }

                if(Alerts::has_errors()) {
                    redirect('fcc-education');
                }

                if($video_count > 0) {
                    $next_index = $current_index + 1;
                    $meta->fcc_core_progress = $next_index;
                }

                $meta->fcc_core_completed = true;
                $meta->fcc_core_completed_at = get_date();
                if($video_count > 0 && ($meta->fcc_core_progress ?? 0) < $video_count) {
                    $meta->fcc_core_completed = false;
                    $meta->fcc_core_completed_at = null;
                }

                $preferences->meta = $meta;
                $preferences->fcc_core_completed = (bool) $meta->fcc_core_completed;
                $preferences->fcc_core_completed_at = $meta->fcc_core_completed_at;

                db()->where('user_id', $this->user->user_id)->update('users', [
                    'preferences' => json_encode($preferences),
                ]);

                $this->user->preferences = $preferences;
                \Altum\Authentication::$user->preferences = $preferences;

                cache()->deleteItemsByTag('user_id=' . $this->user->user_id);
                cache()->deleteItem('user?user_id=' . $this->user->user_id);

                if($video_count > 0 && ($meta->fcc_core_progress ?? 0) < $video_count) {
                    Alerts::add_success(l('fcc_education.progress_saved'));
                    redirect('fcc-education');
                }

                db()->where('user_id', $this->user->user_id)->where('type', 'biolink')->update('links', [
                    'is_enabled' => 1,
                ]);

                $biolinks = db()->where('user_id', $this->user->user_id)->where('type', 'biolink')->get('links', null, ['link_id']);
                foreach($biolinks as $biolink) {
                    cache()->deleteItem('link?link_id=' . $biolink->link_id);
                    cache()->deleteItem('biolink_blocks?link_id=' . $biolink->link_id);
                    cache()->deleteItemsByTag('link_id=' . $biolink->link_id);
                }

                if(settings()->internal_notifications->users_is_enabled) {
                    db()->insert('internal_notifications', [
                        'user_id' => $this->user->user_id,
                        'for_who' => 'user',
                        'from_who' => 'system',
                        'icon' => 'fas fa-check-circle',
                        'title' => l('fcc_education.notification.title'),
                        'description' => l('fcc_education.notification.description'),
                        'url' => url('links?type=biolink'),
                        'datetime' => get_date(),
                    ]);

                    db()->where('user_id', $this->user->user_id)->update('users', [
                        'has_pending_internal_notifications' => 1
                    ]);

                    cache()->deleteItem('user?user_id=' . $this->user->user_id);
                }

                Alerts::add_success(l('fcc_education.completed_success'));

                redirect('dashboard');
            }
        }

        $preferences = $this->user->preferences ?? '{}';
        if(is_string($preferences)) {
            $preferences = json_decode($preferences);
        }
        $meta = $preferences->meta ?? new \stdClass();
        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        $current_index = (int) ($meta->fcc_core_progress ?? 0);
        if($current_index < 0) {
            $current_index = 0;
        }

        /* Custom code: FC-2026-02-25: allow review of any video after completion */
        $requested_index = null;
        if($video_count > 0 && $is_completed && isset($_GET['video'])) {
            if($_GET['video'] === 'last') {
                $requested_index = $video_count - 1;
            } else if(is_numeric($_GET['video'])) {
                $requested_index = (int) $_GET['video'];
            }
        }

        if($video_count > 0 && $current_index >= $video_count) {
            $current_index = $video_count - 1;
        }

        if($video_count > 0 && $requested_index !== null) {
            if($requested_index < 0) {
                $requested_index = 0;
            }
            if($requested_index >= $video_count) {
                $requested_index = $video_count - 1;
            }
            $current_index = $requested_index;
        }
        /* /Custom code: FC-2026-02-25 */

        $current_video = $video_count > 0 ? ($education_videos[$current_index] ?? null) : null;
        if(is_array($current_video)) {
            $current_video = (object) $current_video;
        }

        $data = [
            'is_completed' => $is_completed,
            /* Custom code: FC-2026-02-24: FCC education settings */
            'education_settings' => $education_settings,
            'education_enabled' => $education_enabled,
            'education_videos' => $education_videos,
            'video_count' => $video_count,
            'current_index' => $current_index,
            'current_video' => $current_video,
            /* Custom code: FC-2026-02-25: review mode */
            'review_index' => $requested_index,
            /* /Custom code: FC-2026-02-25 */
            /* /Custom code: FC-2026-02-24 */
        ];

        $view = new \Altum\View('fcc-education/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }
    /* /Custom code: FC-2026-02-24 */

}
