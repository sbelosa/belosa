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
use Altum\Response;
use Altum\Title;

defined('ALTUMCODE') || die();

class FccAiHub extends Controller {

    public function index() {
        \Altum\Authentication::guard();

        fcc_ai_ensure_tables();
        fcc_ai_seed_user_assistants((int) $this->user->user_id, (string) ($this->user->name ?? ''));
        $is_admin = \Altum\Authentication::is_admin();
        $assistant_types = fcc_ai_get_assistant_types();
        $editable_assistant_types = fcc_ai_get_public_assistant_types();

        $language_options = [
            'auto' => l('fcc_ai.form.language_auto'),
            'hr' => l('fcc_ai.form.language_hr'),
            'en' => l('fcc_ai.form.language_en'),
            'sl' => l('fcc_ai.form.language_sl'),
            'bg' => l('fcc_ai.form.language_bg'),
        ];
        $tone_options = [
            'consultative' => l('fcc_ai.form.tone_consultative'),
            'supportive' => l('fcc_ai.form.tone_supportive'),
            'friendly' => l('fcc_ai.form.tone_friendly'),
            'direct' => l('fcc_ai.form.tone_direct'),
        ];

        if(!empty($_POST)) {
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            $fcc_ai_assistant_id = (int) ($_POST['fcc_ai_assistant_id'] ?? 0);
            $assistant = db()
                ->where('fcc_ai_assistant_id', $fcc_ai_assistant_id)
                ->where('user_id', (int) $this->user->user_id)
                ->getOne('fcc_ai_assistants');

            if(!$assistant) {
                Alerts::add_error(l('fcc_ai.alert.assistant_not_found'));
            }

            if($assistant && !$is_admin && (string) ($assistant->assistant_type ?? '') === 'coach') {
                Alerts::add_error(l('fcc_ai.alert.coach_admin_only'));
            }

            $display_name = input_clean((string) ($_POST['display_name'] ?? ''), 128);
            $language = fcc_ai_normalize_language((string) ($_POST['language'] ?? 'auto'));
            $model = $assistant ? (string) ($assistant->model ?? '') : '';
            $tone = trim((string) ($_POST['tone'] ?? 'consultative'));
            $persona_prompt = trim(mb_substr((string) ($_POST['persona_prompt'] ?? ''), 0, 4000));
            $rules_prompt = trim(mb_substr((string) ($_POST['rules_prompt'] ?? ''), 0, 6000));
            $is_enabled = isset($_POST['is_enabled']) ? 1 : 0;

            if(!array_key_exists($language, $language_options)) {
                Alerts::add_error(l('fcc_ai.alert.language_not_supported'));
            }

            if(!array_key_exists($tone, $tone_options)) {
                Alerts::add_error(l('fcc_ai.alert.tone_not_supported'));
            }

            if(!Alerts::has_errors()) {
                $assistant_settings = json_decode($assistant->settings ?? '{}');

                if(is_array($assistant_settings)) {
                    $assistant_settings = (object) $assistant_settings;
                }

                if(!is_object($assistant_settings)) {
                    $assistant_settings = (object) [];
                }

                $assistant_settings->tone = $tone;
                $assistant_settings->language_mode = $language;
                $assistant_settings->owner_name = $assistant_settings->owner_name ?? (string) ($this->user->name ?? '');

                db()->where('fcc_ai_assistant_id', $fcc_ai_assistant_id)->update('fcc_ai_assistants', [
                    'display_name' => $display_name !== '' ? $display_name : null,
                    'language' => $language !== 'auto' ? $language : null,
                    'model' => $model !== '' ? $model : null,
                    'is_enabled' => $is_enabled,
                    'persona_prompt' => $persona_prompt !== '' ? $persona_prompt : null,
                    'rules_prompt' => $rules_prompt !== '' ? $rules_prompt : null,
                    'settings' => json_encode($assistant_settings),
                    'last_deployed_at' => get_date(),
                    'last_datetime' => get_date(),
                ]);

                Alerts::add_success(l('fcc_ai.alert.saved'));
                redirect('fcc-ai');
            }
        }

        $assistants = db()->where('user_id', (int) $this->user->user_id)->get('fcc_ai_assistants');
        foreach($assistants as $assistant) {
            $assistant->settings = json_decode($assistant->settings ?? '{}');

            if(is_array($assistant->settings)) {
                $assistant->settings = (object) $assistant->settings;
            }

            if(!is_object($assistant->settings)) {
                $assistant->settings = (object) [];
            }
        }

        $messages_total = 0;
        $messages_result = database()->query("
            SELECT COUNT(*) AS `total`
            FROM `fcc_ai_messages`
            LEFT JOIN `fcc_ai_conversations` ON `fcc_ai_conversations`.`fcc_ai_conversation_id` = `fcc_ai_messages`.`fcc_ai_conversation_id`
            WHERE `fcc_ai_conversations`.`user_id` = " . (int) $this->user->user_id
        );
        if($messages_result) {
            $messages_total = (int) ($messages_result->fetch_object()->total ?? 0);
        }

        $recent_leads = db()
            ->where('user_id', (int) $this->user->user_id)
            ->orderBy('fcc_ai_lead_id', 'DESC')
            ->get('fcc_ai_leads', 5);

        $stats = [
            'tables_ready' => fcc_ai_tables_ready(),
            'assistant_rows' => (int) count($assistants),
            'conversations' => (int) db()->where('user_id', (int) $this->user->user_id)->getValue('fcc_ai_conversations', 'COUNT(*)'),
            'messages' => $messages_total,
            'ai_leads' => (int) db()->where('user_id', (int) $this->user->user_id)->getValue('fcc_ai_leads', 'COUNT(*)'),
            'data_leads' => (int) db()->where('user_id', (int) $this->user->user_id)->where('type', 'ai_chat_lead')->getValue('data', 'COUNT(*)'),
            'responses_to_review' => (int) database()->query("
                SELECT COUNT(*) AS `total`
                FROM `fcc_ai_message_feedback` AS `f`
                LEFT JOIN `fcc_ai_conversations` AS `c` ON `c`.`fcc_ai_conversation_id` = `f`.`fcc_ai_conversation_id`
                WHERE `f`.`user_id` = " . (int) $this->user->user_id . "
                  AND `f`.`feedback_type` = 'down'
                  AND COALESCE(`f`.`status`, 'new') != 'resolved'
                  AND COALESCE(`c`.`assistant_type`, '') != 'coach'
            ")->fetch_object()->total,
            'openai_ready' => fcc_ai_get_openai_api_key() !== '',
            'default_model' => fcc_ai_resolve_assistant_model(),
        ];

        $assistant_type_filter = fcc_ai_validate_assistant_type((string) ($_GET['assistant_type'] ?? '')) ?: '';
        $scope_filter = input_clean((string) ($_GET['scope'] ?? ''), 32);
        $lead_status_filter = input_clean((string) ($_GET['lead_status'] ?? ''), 16);
        $selected_conversation_public_id = input_clean((string) ($_GET['conversation'] ?? ''), 64);
        $scope_options = [
            '' => l('fcc_ai.filters.all_scopes'),
            'internal_coach' => l('fcc_ai.scope.internal_coach'),
            'public_app' => l('fcc_ai.scope.public_app'),
            'public_blog' => l('fcc_ai.scope.public_blog'),
        ];
        $lead_status_options = [
            '' => l('fcc_ai.filters.all_lead_statuses'),
            'none' => l('fcc_ai.lead_status.none'),
            'captured' => l('fcc_ai.lead_status.captured'),
        ];

        if(!array_key_exists($scope_filter, $scope_options)) {
            $scope_filter = '';
        }

        if(!array_key_exists($lead_status_filter, $lead_status_options)) {
            $lead_status_filter = '';
        }

        $cutoff_datetime = (new \DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s');
        $cutoff_datetime_escaped = database()->real_escape_string($cutoff_datetime);
        $where_conditions = [
            "`c`.`user_id` = " . (int) $this->user->user_id,
            "COALESCE(`c`.`last_message_at`, `c`.`datetime`) >= '{$cutoff_datetime_escaped}'",
        ];

        if($assistant_type_filter !== '') {
            $where_conditions[] = "`c`.`assistant_type` = '" . database()->real_escape_string($assistant_type_filter) . "'";
        }

        if($scope_filter !== '') {
            $where_conditions[] = "`c`.`scope` = '" . database()->real_escape_string($scope_filter) . "'";
        }

        if($lead_status_filter !== '') {
            $where_conditions[] = "`c`.`lead_status` = '" . database()->real_escape_string($lead_status_filter) . "'";
        }

        $conversations = [];
        $conversations_result = database()->query("
            SELECT
                `c`.*,
                `a`.`display_name` AS `assistant_display_name`,
                (
                    SELECT `m`.`content`
                    FROM `fcc_ai_messages` AS `m`
                    WHERE `m`.`fcc_ai_conversation_id` = `c`.`fcc_ai_conversation_id`
                    ORDER BY `m`.`fcc_ai_message_id` DESC
                    LIMIT 1
                ) AS `latest_message_content`,
                (
                    SELECT `m`.`role`
                    FROM `fcc_ai_messages` AS `m`
                    WHERE `m`.`fcc_ai_conversation_id` = `c`.`fcc_ai_conversation_id`
                    ORDER BY `m`.`fcc_ai_message_id` DESC
                    LIMIT 1
                ) AS `latest_message_role`,
                (
                    SELECT `m`.`datetime`
                    FROM `fcc_ai_messages` AS `m`
                    WHERE `m`.`fcc_ai_conversation_id` = `c`.`fcc_ai_conversation_id`
                    ORDER BY `m`.`fcc_ai_message_id` DESC
                    LIMIT 1
                ) AS `latest_message_datetime`,
                (
                    CASE
                        WHEN `c`.`assistant_type` = 'coach' THEN 0
                        ELSE (
                            SELECT COUNT(*)
                            FROM `fcc_ai_message_feedback` AS `f`
                            WHERE `f`.`fcc_ai_conversation_id` = `c`.`fcc_ai_conversation_id`
                              AND `f`.`feedback_type` = 'down'
                              AND COALESCE(`f`.`status`, 'new') != 'resolved'
                        )
                    END
                ) AS `negative_feedback_total`
            FROM `fcc_ai_conversations` AS `c`
            LEFT JOIN `fcc_ai_assistants` AS `a` ON `a`.`fcc_ai_assistant_id` = `c`.`fcc_ai_assistant_id`
            WHERE " . implode(' AND ', $where_conditions) . "
            ORDER BY COALESCE(`c`.`last_message_at`, `c`.`datetime`) DESC
            LIMIT 50
        ");

        while($conversations_result && $row = $conversations_result->fetch_object()) {
            $row->meta = json_decode($row->meta ?? '{}');

            if(is_array($row->meta)) {
                $row->meta = (object) $row->meta;
            }

            if(!is_object($row->meta)) {
                $row->meta = (object) [];
            }

            $assistant_definition = fcc_ai_get_assistant_type((string) ($row->assistant_type ?? '')) ?? [];
            $row->assistant_label = (string) ($row->assistant_display_name ?: ($assistant_definition['label'] ?? $row->assistant_type));
            $row->scope_label = $scope_options[(string) ($row->scope ?? '')] ?? (string) ($row->scope ?? '');
            $row->latest_message_preview = fcc_ai_excerpt((string) ($row->latest_message_content ?? ''), 140);
            $row->source_page_label = trim((string) ($row->meta->source_page_title ?? ''));

            if($row->source_page_label === '') {
                $row->source_page_label = trim((string) ($row->meta->source_page_slug ?? ''));
            }

            if($row->source_page_label === '') {
                $row->source_page_label = trim((string) ($row->meta->source_context ?? ''));
            }

            $conversations[] = $row;
        }

        $inbox_stats = [
            'conversations_30d' => 0,
            'coach_30d' => 0,
            'public_30d' => 0,
            'captured_leads_30d' => 0,
            'negative_feedback_30d' => 0,
        ];

        $inbox_stats_result = database()->query("
            SELECT
                COUNT(*) AS `conversations_30d`,
                SUM(CASE WHEN `assistant_type` = 'coach' THEN 1 ELSE 0 END) AS `coach_30d`,
                SUM(CASE WHEN `assistant_type` IN ('product_advisor', 'pets_advisor') THEN 1 ELSE 0 END) AS `public_30d`,
                SUM(CASE WHEN `lead_status` = 'captured' THEN 1 ELSE 0 END) AS `captured_leads_30d`,
                (
                    SELECT COUNT(*)
                    FROM `fcc_ai_message_feedback` AS `f`
                    LEFT JOIN `fcc_ai_conversations` AS `fc` ON `fc`.`fcc_ai_conversation_id` = `f`.`fcc_ai_conversation_id`
                    WHERE `f`.`user_id` = " . (int) $this->user->user_id . "
                      AND `f`.`feedback_type` = 'down'
                      AND COALESCE(`f`.`status`, 'new') != 'resolved'
                      AND COALESCE(`fc`.`assistant_type`, '') != 'coach'
                      AND COALESCE(`f`.`last_datetime`, `f`.`datetime`) >= '{$cutoff_datetime_escaped}'
                ) AS `negative_feedback_30d`
            FROM `fcc_ai_conversations`
            WHERE `user_id` = " . (int) $this->user->user_id . "
              AND COALESCE(`last_message_at`, `datetime`) >= '{$cutoff_datetime_escaped}'
        ");

        if($inbox_stats_result && $row = $inbox_stats_result->fetch_object()) {
            $inbox_stats = [
                'conversations_30d' => (int) ($row->conversations_30d ?? 0),
                'coach_30d' => (int) ($row->coach_30d ?? 0),
                'public_30d' => (int) ($row->public_30d ?? 0),
                'captured_leads_30d' => (int) ($row->captured_leads_30d ?? 0),
                'negative_feedback_30d' => (int) ($row->negative_feedback_30d ?? 0),
            ];
        }

        $dashboard_snapshot = fcc_ai_get_user_dashboard_payload((int) $this->user->user_id, $cutoff_datetime, 6, \Altum\Language::$code);

        $review_items = [];
        $review_result = database()->query("
            SELECT
                `f`.`fcc_ai_message_feedback_id`,
                `f`.`fcc_ai_message_id`,
                `f`.`fcc_ai_conversation_id`,
                `f`.`reason`,
                `f`.`note`,
                `f`.`datetime`,
                `c`.`public_id`,
                `c`.`assistant_type`,
                `c`.`scope`,
                `c`.`meta` AS `conversation_meta`,
                `m`.`content` AS `message_content`
            FROM `fcc_ai_message_feedback` AS `f`
            LEFT JOIN `fcc_ai_conversations` AS `c` ON `c`.`fcc_ai_conversation_id` = `f`.`fcc_ai_conversation_id`
            LEFT JOIN `fcc_ai_messages` AS `m` ON `m`.`fcc_ai_message_id` = `f`.`fcc_ai_message_id`
            WHERE `f`.`user_id` = " . (int) $this->user->user_id . "
              AND COALESCE(`c`.`assistant_type`, '') != 'coach'
              AND `f`.`feedback_type` = 'down'
              AND COALESCE(`f`.`status`, 'new') != 'resolved'
              AND COALESCE(`f`.`last_datetime`, `f`.`datetime`) >= '{$cutoff_datetime_escaped}'
            ORDER BY COALESCE(`f`.`last_datetime`, `f`.`datetime`) DESC
            LIMIT 12
        ");

        while($review_result && $review_row = $review_result->fetch_object()) {
            $conversation_meta = json_decode($review_row->conversation_meta ?? '{}');
            if(is_array($conversation_meta)) {
                $conversation_meta = (object) $conversation_meta;
            }
            if(!is_object($conversation_meta)) {
                $conversation_meta = (object) [];
            }

            $review_items[] = (object) [
                'conversation_public_id' => (string) ($review_row->public_id ?? ''),
                'assistant_label' => fcc_ai_get_assistant_label((string) ($review_row->assistant_type ?? '')),
                'scope_label' => $scope_options[(string) ($review_row->scope ?? '')] ?? (string) ($review_row->scope ?? ''),
                'reason_label' => fcc_ai_get_feedback_reason_label((string) ($review_row->reason ?? ''), \Altum\Language::$code),
                'note' => trim((string) ($review_row->note ?? '')),
                'message_excerpt' => fcc_ai_excerpt((string) ($review_row->message_content ?? ''), 170),
                'datetime' => (string) ($review_row->datetime ?? ''),
                'detail_url' => $review_row->public_id ? url('fcc-ai?conversation=' . urlencode((string) $review_row->public_id)) : url('fcc-ai'),
                'source_page_label' => trim((string) (($conversation_meta->source_page_title ?? '') ?: ($conversation_meta->source_context ?? ''))),
            ];
        }

        $selected_conversation = null;
        $selected_messages = [];
        $selected_conversation_lead = null;
        $selected_conversation_link = null;
        $selected_conversation_blog_post = null;

        if($selected_conversation_public_id !== '') {
            $selected_conversation = fcc_ai_get_conversation_by_public_id($selected_conversation_public_id);

            if($selected_conversation && (int) ($selected_conversation->user_id ?? 0) === (int) $this->user->user_id) {
                $selected_conversation->assistant = fcc_ai_get_assistant_by_id((int) ($selected_conversation->fcc_ai_assistant_id ?? 0));
                $selected_assistant_definition = fcc_ai_get_assistant_type((string) ($selected_conversation->assistant_type ?? '')) ?? [];
                $selected_assistant_display_name = '';
                if(is_object($selected_conversation->assistant ?? null)) {
                    $selected_assistant_display_name = trim((string) ($selected_conversation->assistant->display_name ?? ''));
                }

                $selected_conversation->assistant_label = (string) ($selected_assistant_display_name !== '' ? $selected_assistant_display_name : ($selected_assistant_definition['label'] ?? $selected_conversation->assistant_type));
                $selected_conversation->scope_label = $scope_options[(string) ($selected_conversation->scope ?? '')] ?? (string) ($selected_conversation->scope ?? '');
                $selected_messages = fcc_ai_get_conversation_messages((int) $selected_conversation->fcc_ai_conversation_id, 120, fcc_ai_build_feedback_actor('owner', (string) $this->user->user_id, (int) $this->user->user_id));
                $selected_conversation_lead = db()
                    ->where('fcc_ai_conversation_id', (int) $selected_conversation->fcc_ai_conversation_id)
                    ->orderBy('fcc_ai_lead_id', 'DESC')
                    ->getOne('fcc_ai_leads');

                if(!empty($selected_conversation->link_id)) {
                    $selected_conversation_link = db()
                        ->where('link_id', (int) $selected_conversation->link_id)
                        ->getOne('links', ['link_id', 'url', 'location_url']);

                    if(is_array($selected_conversation_link)) {
                        $selected_conversation_link = (object) $selected_conversation_link;
                    }
                }

                if(!empty($selected_conversation->blog_post_id)) {
                    $selected_conversation_blog_post = db()
                        ->where('blog_post_id', (int) $selected_conversation->blog_post_id)
                        ->getOne('blog_posts', ['blog_post_id', 'title', 'url', 'language']);

                    if(is_array($selected_conversation_blog_post)) {
                        $selected_conversation_blog_post = (object) $selected_conversation_blog_post;
                    }
                }
            } else {
                $selected_conversation = null;
            }
        }

        $data = [
            'assistants' => $assistants,
            'assistant_types' => $assistant_types,
            'editable_assistant_types' => $editable_assistant_types,
            'is_admin' => $is_admin,
            'stats' => $stats,
            'inbox_stats' => $inbox_stats,
            'dashboard_snapshot' => $dashboard_snapshot,
            'recent_alerts' => $dashboard_snapshot['recent_alerts'] ?? [],
            'useful_items' => $dashboard_snapshot['useful_items'] ?? [],
            'rising_topics' => $dashboard_snapshot['rising_topics'] ?? [],
            'recent_leads' => $recent_leads,
            'review_items' => $review_items,
            'conversations' => $conversations,
            'conversation_filters' => [
                'assistant_type' => $assistant_type_filter,
                'scope' => $scope_filter,
                'lead_status' => $lead_status_filter,
                'conversation' => $selected_conversation_public_id,
            ],
            'scope_options' => $scope_options,
            'lead_status_options' => $lead_status_options,
            'selected_conversation' => $selected_conversation,
            'selected_messages' => $selected_messages,
            'selected_conversation_lead' => $selected_conversation_lead,
            'selected_conversation_link' => $selected_conversation_link,
            'selected_conversation_blog_post' => $selected_conversation_blog_post,
            'conversation_window_days' => 30,
            'language_options' => $language_options,
            'tone_options' => $tone_options,
            'public_endpoints' => [
                'status' => url('l/fcc-ai/status'),
                'conversation' => url('l/fcc-ai/conversation'),
                'message' => url('l/fcc-ai/message'),
                'feedback' => url('l/fcc-ai/feedback'),
                'lead' => url('l/fcc-ai/lead'),
            ],
            'internal_endpoints' => [
                'conversation' => url('fcc-ai/coach-conversation'),
                'message' => url('fcc-ai/coach-message'),
                'feedback' => url('fcc-ai/coach-feedback'),
            ],
            'continuation_storage_key' => fcc_ai_get_public_storage_key(),
            'context_storage_key' => fcc_ai_get_public_context_storage_key(),
            'internal_continuation_storage_key' => fcc_ai_get_internal_storage_key(),
            'internal_context_storage_key' => fcc_ai_get_internal_context_storage_key(),
        ];

        Title::set(l('fcc_ai.page_title'));

        $view = new \Altum\View('fcc-ai/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }

    public function coach_conversation() {
        \Altum\Authentication::guard();

        if(empty($_POST)) {
            die();
        }

        try {
            $state = fcc_ai_create_or_resume_internal_coach_conversation($this->user, [
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'language' => input_clean((string) ($_POST['language'] ?? ''), 16),
                'source_context' => input_clean((string) ($_POST['source_context'] ?? ''), 255),
                'source_page_url' => input_clean((string) ($_POST['source_page_url'] ?? ''), 2048),
                'source_page_slug' => input_clean((string) ($_POST['source_page_slug'] ?? ''), 255),
                'source_page_title' => input_clean((string) ($_POST['source_page_title'] ?? ''), 255),
                'source_page_section' => input_clean((string) ($_POST['source_page_section'] ?? ''), 64),
            ]);

            Response::json('FCC Coach razgovor je spreman.', 'success', $state);
        } catch(\Throwable $exception) {
            Response::json($exception->getMessage(), 'error');
        }
    }

    public function coach_message() {
        \Altum\Authentication::guard();

        if(empty($_POST)) {
            die();
        }

        try {
            $result = fcc_ai_handle_internal_coach_message($this->user, [
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'language' => input_clean((string) ($_POST['language'] ?? ''), 16),
                'source_context' => input_clean((string) ($_POST['source_context'] ?? ''), 255),
                'source_page_url' => input_clean((string) ($_POST['source_page_url'] ?? ''), 2048),
                'source_page_slug' => input_clean((string) ($_POST['source_page_slug'] ?? ''), 255),
                'source_page_title' => input_clean((string) ($_POST['source_page_title'] ?? ''), 255),
                'source_page_section' => input_clean((string) ($_POST['source_page_section'] ?? ''), 64),
                'message' => input_clean((string) ($_POST['message'] ?? ''), 4000),
            ]);

            Response::json('FCC Coach odgovor je spreman.', 'success', $result);
        } catch(\Throwable $exception) {
            Response::json($exception->getMessage(), 'error');
        }
    }

    public function coach_feedback() {
        \Altum\Authentication::guard();

        if(empty($_POST)) {
            die();
        }

        try {
            $result = fcc_ai_capture_internal_coach_feedback($this->user, [
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'message_id' => (int) ($_POST['message_id'] ?? 0),
                'feedback_type' => input_clean((string) ($_POST['feedback_type'] ?? ''), 16),
                'reason' => input_clean((string) ($_POST['reason'] ?? ''), 32),
                'note' => input_clean((string) ($_POST['note'] ?? ''), 1000),
                'source_page_url' => input_clean((string) ($_POST['source_page_url'] ?? ''), 2048),
                'source_page_slug' => input_clean((string) ($_POST['source_page_slug'] ?? ''), 255),
                'source_page_title' => input_clean((string) ($_POST['source_page_title'] ?? ''), 255),
                'source_page_section' => input_clean((string) ($_POST['source_page_section'] ?? ''), 64),
            ]);

            Response::json('Feedback je spremljen.', 'success', $result);
        } catch(\Throwable $exception) {
            Response::json($exception->getMessage(), 'error');
        }
    }
}
