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

use Altum\Captcha;
use Altum\Response;

defined('ALTUMCODE') || die();

class FccAiPublic extends Controller {

    public function index() {
        $this->status();
    }

    public function status() {
        fcc_ai_ensure_tables();

        $assistant_types = [];
        foreach(fcc_ai_get_public_assistant_types() as $assistant_type => $definition) {
            $assistant_types[$assistant_type] = [
                'label' => (string) ($definition['label'] ?? $assistant_type),
                'description' => (string) ($definition['description'] ?? ''),
                'supports_lead_capture' => (bool) ($definition['supports_lead_capture'] ?? false),
                'supports_blog_continuation' => (bool) ($definition['supports_blog_continuation'] ?? false),
            ];
        }

        Response::json([
            'version' => 'phase2',
            'tables_ready' => fcc_ai_tables_ready(),
            'assistant_types' => $assistant_types,
            'continuation_storage_key' => fcc_ai_get_public_storage_key(),
            'context_storage_key' => fcc_ai_get_public_context_storage_key(),
            'lead_capture_type' => 'ai_chat_lead',
            'provider' => [
                'openai_ready' => fcc_ai_get_openai_api_key() !== '',
                'default_model' => fcc_ai_resolve_assistant_model(),
            ],
        ]);
    }

    public function conversation() {
        if(empty($_POST)) {
            die();
        }

        try {
            $state = fcc_ai_create_or_resume_public_conversation([
                'assistant_type' => input_clean((string) ($_POST['assistant_type'] ?? ''), 32),
                'scope' => input_clean((string) ($_POST['scope'] ?? ''), 32),
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'link_id' => (int) ($_POST['link_id'] ?? 0),
                'blog_post_id' => (int) ($_POST['blog_post_id'] ?? 0),
                'language' => input_clean((string) ($_POST['language'] ?? ''), 16),
                'source_context' => input_clean((string) ($_POST['source_context'] ?? ''), 255),
                'source_page_url' => input_clean((string) ($_POST['source_page_url'] ?? ''), 2048),
                'source_page_slug' => input_clean((string) ($_POST['source_page_slug'] ?? ''), 128),
                'visitor_key' => input_clean((string) ($_POST['visitor_key'] ?? ''), 64),
            ]);

            Response::json('FCC AI conversation ready.', 'success', $state);
        } catch(\Throwable $exception) {
            Response::json($exception->getMessage(), 'error');
        }
    }

    public function message() {
        if(empty($_POST)) {
            die();
        }

        try {
            $result = fcc_ai_handle_public_message([
                'assistant_type' => input_clean((string) ($_POST['assistant_type'] ?? ''), 32),
                'scope' => input_clean((string) ($_POST['scope'] ?? ''), 32),
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'link_id' => (int) ($_POST['link_id'] ?? 0),
                'blog_post_id' => (int) ($_POST['blog_post_id'] ?? 0),
                'language' => input_clean((string) ($_POST['language'] ?? ''), 16),
                'source_context' => input_clean((string) ($_POST['source_context'] ?? ''), 255),
                'source_page_url' => input_clean((string) ($_POST['source_page_url'] ?? ''), 2048),
                'source_page_slug' => input_clean((string) ($_POST['source_page_slug'] ?? ''), 128),
                'visitor_key' => input_clean((string) ($_POST['visitor_key'] ?? ''), 64),
                'message' => input_clean((string) ($_POST['message'] ?? ''), 4000),
            ]);

            Response::json('FCC AI odgovor je spreman.', 'success', $result);
        } catch(\Throwable $exception) {
            Response::json($exception->getMessage(), 'error');
        }
    }

    public function feedback() {
        if(empty($_POST)) {
            die();
        }

        try {
            $result = fcc_ai_capture_public_message_feedback([
                'assistant_type' => input_clean((string) ($_POST['assistant_type'] ?? ''), 32),
                'scope' => input_clean((string) ($_POST['scope'] ?? ''), 32),
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'link_id' => (int) ($_POST['link_id'] ?? 0),
                'blog_post_id' => (int) ($_POST['blog_post_id'] ?? 0),
                'language' => input_clean((string) ($_POST['language'] ?? ''), 16),
                'source_context' => input_clean((string) ($_POST['source_context'] ?? ''), 255),
                'source_page_url' => input_clean((string) ($_POST['source_page_url'] ?? ''), 2048),
                'source_page_slug' => input_clean((string) ($_POST['source_page_slug'] ?? ''), 128),
                'visitor_key' => input_clean((string) ($_POST['visitor_key'] ?? ''), 64),
                'message_id' => (int) ($_POST['message_id'] ?? 0),
                'feedback_type' => input_clean((string) ($_POST['feedback_type'] ?? ''), 16),
                'reason' => input_clean((string) ($_POST['reason'] ?? ''), 32),
                'note' => input_clean((string) ($_POST['note'] ?? ''), 1000),
            ]);

            Response::json('Feedback je spremljen.', 'success', $result);
        } catch(\Throwable $exception) {
            Response::json($exception->getMessage(), 'error');
        }
    }

    public function lead() {
        if(empty($_POST)) {
            die();
        }

        if(settings()->captcha->biolink_is_enabled && settings()->captcha->type != 'basic' && !(new Captcha())->is_valid()) {
            Response::json(l('global.error_message.invalid_captcha'), 'error');
        }

        if(empty($_POST['consent_contact']) && empty($_POST['agreement']) && empty($_POST['consent'])) {
            Response::json('Potrebna je potvrda da suradnik smije kontaktirati posjetitelja.', 'error');
        }

        try {
            $result = fcc_ai_capture_public_lead([
                'assistant_type' => input_clean((string) ($_POST['assistant_type'] ?? ''), 32),
                'scope' => input_clean((string) ($_POST['scope'] ?? ''), 32),
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'link_id' => (int) ($_POST['link_id'] ?? 0),
                'blog_post_id' => (int) ($_POST['blog_post_id'] ?? 0),
                'language' => input_clean((string) ($_POST['language'] ?? ''), 16),
                'source_context' => input_clean((string) ($_POST['source_context'] ?? ''), 255),
                'source_page_url' => input_clean((string) ($_POST['source_page_url'] ?? ''), 2048),
                'source_page_slug' => input_clean((string) ($_POST['source_page_slug'] ?? ''), 128),
                'visitor_key' => input_clean((string) ($_POST['visitor_key'] ?? ''), 64),
                'lead_type' => input_clean((string) ($_POST['lead_type'] ?? ''), 32),
                'name' => input_clean((string) ($_POST['name'] ?? ''), 128),
                'email' => input_clean_email((string) ($_POST['email'] ?? '')),
                'phone' => input_clean((string) ($_POST['phone'] ?? ''), 32),
                'phone_country_code' => input_clean((string) ($_POST['phone_country_code'] ?? 'HR'), 8),
                'preferred_contact_channel' => input_clean((string) ($_POST['preferred_contact_channel'] ?? 'whatsapp'), 16),
                'country_code' => input_clean((string) ($_POST['country_code'] ?? ''), 8),
                'message' => input_clean((string) ($_POST['message'] ?? ''), 1000),
            ]);

            Response::json('AI lead je uspjesno spremljen u kontakte.', 'success', $result);
        } catch(\Throwable $exception) {
            Response::json($exception->getMessage(), 'error');
        }
    }
}
