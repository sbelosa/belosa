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

/* Custom code: FC-2026-03-08: user feedback tickets module */
class FeedbackTickets extends Controller {

    private function ensure_feedback_upload_directory_is_writable(): bool {
        $directory_path = \Altum\Uploads::get_full_path('feedback_tickets');

        if(!is_dir($directory_path)) {
            @mkdir($directory_path, 0755, true);
        }

        if(!is_writable($directory_path)) {
            @chmod($directory_path, 0755);
        }

        return is_dir($directory_path) && is_writable($directory_path);
    }

    private function has_feedback_tables(): bool {
        try {
            $has_feedback_tickets = database()->query("SHOW TABLES LIKE 'feedback_tickets'");
            $has_feedback_ticket_messages = database()->query("SHOW TABLES LIKE 'feedback_ticket_messages'");

            return $has_feedback_tickets && $has_feedback_tickets->num_rows && $has_feedback_ticket_messages && $has_feedback_ticket_messages->num_rows;
        } catch(\Throwable $exception) {
            return false;
        }
    }

    public function index() {

        \Altum\Authentication::guard();

        /* Custom code: FC-2026-03-08: prevent crash when feedback tables are missing */
        if(!$this->has_feedback_tables()) {
            Alerts::add_error(l('feedback_tickets.alert.unavailable'));
            redirect('dashboard');
        }
        /* /Custom code: FC-2026-03-08 */

        if(!empty($_POST)) {
            $_POST['subject'] = input_clean($_POST['subject'] ?? '', 128);
            $_POST['category'] = input_clean($_POST['category'] ?? '', 16);
            $_POST['message'] = input_clean($_POST['message'] ?? '', 10000);

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if($_POST['subject'] === '') {
                Alerts::add_field_error('subject', l('global.error_message.empty_field'));
            }

            if($_POST['message'] === '') {
                Alerts::add_field_error('message', l('global.error_message.empty_field'));
            }

            if(!in_array($_POST['category'], ['change', 'add', 'bug', 'other'])) {
                $_POST['category'] = 'other';
            }

            $screenshot = null;
            if(!empty($_FILES['screenshot']['name'])) {
                if($this->ensure_feedback_upload_directory_is_writable()) {
                    $screenshot = \Altum\Uploads::process_upload(null, 'feedback_tickets', 'screenshot', 'screenshot_remove', 5);
                } else {
                    Alerts::add_warning(l('feedback_tickets.alert.upload_directory_not_writable'));
                }
            }

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                $feedback_ticket_id = db()->insert('feedback_tickets', [
                    'user_id' => $this->user->user_id,
                    'subject' => $_POST['subject'],
                    'category' => $_POST['category'],
                    'status' => 'open',
                    'screenshot' => $screenshot,
                    'datetime' => get_date(),
                    'last_datetime' => get_date(),
                ]);

                db()->insert('feedback_ticket_messages', [
                    'feedback_ticket_id' => $feedback_ticket_id,
                    'user_id' => $this->user->user_id,
                    'admin_user_id' => null,
                    'is_admin_reply' => 0,
                    'message' => $_POST['message'],
                    'attachment' => $screenshot,
                    'datetime' => get_date(),
                ]);

                Alerts::add_success(l('feedback_tickets.alert.created'));
                redirect('feedback-tickets/ticket/' . $feedback_ticket_id);
            }
        }

        $feedback_tickets = db()
            ->where('user_id', $this->user->user_id)
            ->orderBy('last_datetime', 'DESC')
            ->get('feedback_tickets');

        $data = [
            'feedback_tickets' => $feedback_tickets,
        ];

        $view = new \Altum\View('feedback-tickets/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }

    public function ticket() {

        \Altum\Authentication::guard();

        /* Custom code: FC-2026-03-08: prevent crash when feedback tables are missing */
        if(!$this->has_feedback_tables()) {
            Alerts::add_error(l('feedback_tickets.alert.unavailable'));
            redirect('dashboard');
        }
        /* /Custom code: FC-2026-03-08 */

        $feedback_ticket_id = isset($this->params[0]) ? (int) $this->params[0] : 0;

        if(!$feedback_ticket = db()->where('feedback_ticket_id', $feedback_ticket_id)->where('user_id', $this->user->user_id)->getOne('feedback_tickets')) {
            redirect('feedback-tickets');
        }

        if(!empty($_POST)) {
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if(($_POST['action_type'] ?? null) === 'close' && !Alerts::has_errors()) {
                db()->where('feedback_ticket_id', $feedback_ticket->feedback_ticket_id)->update('feedback_tickets', [
                    'status' => 'closed',
                    'last_datetime' => get_date(),
                ]);

                Alerts::add_success(l('feedback_tickets.alert.closed'));
                redirect('feedback-tickets/ticket/' . $feedback_ticket->feedback_ticket_id);
            }

            if(($_POST['action_type'] ?? null) === 'reply') {
                $_POST['message'] = input_clean($_POST['message'] ?? '', 10000);

                if($_POST['message'] === '') {
                    Alerts::add_field_error('message', l('global.error_message.empty_field'));
                }

                $attachment = null;
                if(!empty($_FILES['attachment']['name'])) {
                    if($this->ensure_feedback_upload_directory_is_writable()) {
                        $attachment = \Altum\Uploads::process_upload(null, 'feedback_tickets', 'attachment', 'attachment_remove', 5);
                    } else {
                        Alerts::add_warning(l('feedback_tickets.alert.upload_directory_not_writable'));
                    }
                }

                if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                    db()->insert('feedback_ticket_messages', [
                        'feedback_ticket_id' => $feedback_ticket->feedback_ticket_id,
                        'user_id' => $this->user->user_id,
                        'admin_user_id' => null,
                        'is_admin_reply' => 0,
                        'message' => $_POST['message'],
                        'attachment' => $attachment,
                        'datetime' => get_date(),
                    ]);

                    db()->where('feedback_ticket_id', $feedback_ticket->feedback_ticket_id)->update('feedback_tickets', [
                        'status' => 'open',
                        'last_datetime' => get_date(),
                    ]);

                    Alerts::add_success(l('feedback_tickets.alert.reply_sent'));
                    redirect('feedback-tickets/ticket/' . $feedback_ticket->feedback_ticket_id);
                }
            }
        }

        $messages = db()->where('feedback_ticket_id', $feedback_ticket->feedback_ticket_id)->orderBy('feedback_ticket_message_id', 'ASC')->get('feedback_ticket_messages');

        $data = [
            'feedback_ticket' => $feedback_ticket,
            'messages' => $messages,
        ];

        $view = new \Altum\View('feedback-tickets/ticket', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }
}
/* /Custom code: FC-2026-03-08 */
