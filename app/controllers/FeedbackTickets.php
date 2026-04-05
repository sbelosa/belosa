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

    private function ensure_feedback_workflow_columns(): void {
        static $is_checked = false;

        if($is_checked || !$this->has_feedback_tables()) {
            return;
        }

        $columns = [];
        $result = database()->query("SHOW COLUMNS FROM `feedback_tickets`");

        while($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = true;
        }

        if(!isset($columns['admin_last_replied_at'])) {
            database()->query("ALTER TABLE `feedback_tickets` ADD `admin_last_replied_at` DATETIME NULL DEFAULT NULL AFTER `last_datetime`");
        }

        if(!isset($columns['user_last_read_at'])) {
            database()->query("ALTER TABLE `feedback_tickets` ADD `user_last_read_at` DATETIME NULL DEFAULT NULL AFTER `admin_last_replied_at`");
        }

        if(!isset($columns['is_webinar_topic_suggestion'])) {
            database()->query("ALTER TABLE `feedback_tickets` ADD `is_webinar_topic_suggestion` TINYINT(1) NOT NULL DEFAULT 0 AFTER `user_last_read_at`");
        }

        if(!isset($columns['is_webinar_topic_confirmed'])) {
            database()->query("ALTER TABLE `feedback_tickets` ADD `is_webinar_topic_confirmed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_webinar_topic_suggestion`");
        }

        $is_checked = true;
    }

    private function auto_close_read_answered_tickets(): void {
        if(!$this->has_feedback_tables()) {
            return;
        }

        $this->ensure_feedback_workflow_columns();

        database()->query("
            UPDATE `feedback_tickets`
            SET `status` = 'closed'
            WHERE `status` = 'answered'
              AND `admin_last_replied_at` IS NOT NULL
              AND `user_last_read_at` IS NOT NULL
              AND `user_last_read_at` >= `admin_last_replied_at`
              AND `user_last_read_at` <= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
    }

    private function mark_ticket_as_user_read(object $feedback_ticket): void {
        $status = (string) ($feedback_ticket->status ?? 'open');
        $admin_last_replied_at = (string) ($feedback_ticket->admin_last_replied_at ?? '');
        $user_last_read_at = (string) ($feedback_ticket->user_last_read_at ?? '');

        if(!in_array($status, ['answered', 'closed'], true) || $admin_last_replied_at === '') {
            return;
        }

        if($user_last_read_at !== '' && $user_last_read_at >= $admin_last_replied_at) {
            return;
        }

        db()->where('feedback_ticket_id', (int) $feedback_ticket->feedback_ticket_id)->update('feedback_tickets', [
            'user_last_read_at' => get_date(),
        ]);

        $feedback_ticket->user_last_read_at = get_date();
    }

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

    private function get_recent_admin_notifications(): array {
        $notifications = db()
            ->where('for_who', 'user')
            ->where('from_who', 'admin')
            ->where('user_id', $this->user->user_id)
            ->orderBy('internal_notification_id', 'DESC')
            ->get('internal_notifications', 6);

        foreach($notifications as $notification) {
            $notification->datetime_timeago = \Altum\Date::get_timeago($notification->datetime);
        }

        return $notifications;
    }

    public function index() {

        \Altum\Authentication::guard();

        /* Custom code: FC-2026-03-08: prevent crash when feedback tables are missing */
        if(!$this->has_feedback_tables()) {
            Alerts::add_error(l('feedback_tickets.alert.unavailable'));
            redirect('dashboard');
        }

        $this->ensure_feedback_workflow_columns();
        $this->auto_close_read_answered_tickets();
        /* /Custom code: FC-2026-03-08 */

        $selected_feedback_ticket_id = max(0, (int) ($_GET['ticket_id'] ?? 0));

        if(!empty($_POST)) {
            $action_type = input_clean($_POST['action_type'] ?? '', 32);

            if(in_array($action_type, ['reply', 'close'], true)) {
                $selected_feedback_ticket_id = max(0, (int) ($_POST['feedback_ticket_id'] ?? $selected_feedback_ticket_id));

                if(!$selected_feedback_ticket = db()->where('feedback_ticket_id', $selected_feedback_ticket_id)->where('user_id', $this->user->user_id)->getOne('feedback_tickets')) {
                    Alerts::add_error(l('feedback_tickets.alert.unavailable'));
                    redirect('feedback-tickets');
                }

                $this->mark_ticket_as_user_read($selected_feedback_ticket);

                if(!\Altum\Csrf::check()) {
                    Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                }

                if($action_type === 'close' && !Alerts::has_errors()) {
                    db()->where('feedback_ticket_id', $selected_feedback_ticket->feedback_ticket_id)->update('feedback_tickets', [
                        'status' => 'closed',
                        'last_datetime' => get_date(),
                    ]);

                    Alerts::add_success(l('feedback_tickets.alert.closed'));
                    redirect('feedback-tickets?ticket_id=' . $selected_feedback_ticket->feedback_ticket_id);
                }

                if($action_type === 'reply') {
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
                            'feedback_ticket_id' => $selected_feedback_ticket->feedback_ticket_id,
                            'user_id' => $this->user->user_id,
                            'admin_user_id' => null,
                            'is_admin_reply' => 0,
                            'message' => $_POST['message'],
                            'attachment' => $attachment,
                            'datetime' => get_date(),
                        ]);

                        db()->where('feedback_ticket_id', $selected_feedback_ticket->feedback_ticket_id)->update('feedback_tickets', [
                            'status' => 'open',
                            'last_datetime' => get_date(),
                            'user_last_read_at' => null,
                        ]);

                        Alerts::add_success(l('feedback_tickets.alert.reply_sent'));
                        redirect('feedback-tickets?ticket_id=' . $selected_feedback_ticket->feedback_ticket_id);
                    }
                }
            }

            $_POST['subject'] = input_clean($_POST['subject'] ?? '', 128);
            $_POST['category'] = input_clean($_POST['category'] ?? '', 16);
            $_POST['message'] = input_clean($_POST['message'] ?? '', 10000);
            $_POST['is_webinar_topic_suggestion'] = !empty($_POST['is_webinar_topic_suggestion']) ? 1 : 0;

            if($action_type === '' && !\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if($action_type === '' && $_POST['subject'] === '') {
                Alerts::add_field_error('subject', l('global.error_message.empty_field'));
            }

            if($action_type === '' && $_POST['message'] === '') {
                Alerts::add_field_error('message', l('global.error_message.empty_field'));
            }

            if($action_type === '' && !in_array($_POST['category'], ['change', 'add', 'bug', 'other'])) {
                $_POST['category'] = 'other';
            }

            $screenshot = null;
            if($action_type === '' && !empty($_FILES['screenshot']['name'])) {
                if($this->ensure_feedback_upload_directory_is_writable()) {
                    $screenshot = \Altum\Uploads::process_upload(null, 'feedback_tickets', 'screenshot', 'screenshot_remove', 5);
                } else {
                    Alerts::add_warning(l('feedback_tickets.alert.upload_directory_not_writable'));
                }
            }

            if($action_type === '' && !Alerts::has_field_errors() && !Alerts::has_errors()) {
                $feedback_ticket_id = db()->insert('feedback_tickets', [
                    'user_id' => $this->user->user_id,
                    'subject' => $_POST['subject'],
                    'category' => $_POST['category'],
                    'status' => 'open',
                    'screenshot' => $screenshot,
                    'datetime' => get_date(),
                    'last_datetime' => get_date(),
                    'admin_last_replied_at' => null,
                    'user_last_read_at' => null,
                    'is_webinar_topic_suggestion' => (int) $_POST['is_webinar_topic_suggestion'],
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
                redirect('feedback-tickets?ticket_id=' . $feedback_ticket_id);
            }
        }

        $feedback_tickets = db()
            ->where('user_id', $this->user->user_id)
            ->orderBy('last_datetime', 'DESC')
            ->get('feedback_tickets');

        $selected_feedback_ticket = null;
        $selected_messages = [];

        if(!empty($feedback_tickets)) {
            if($selected_feedback_ticket_id <= 0) {
                $selected_feedback_ticket_id = (int) ($feedback_tickets[0]->feedback_ticket_id ?? 0);
            }

            foreach($feedback_tickets as $ticket) {
                if((int) ($ticket->feedback_ticket_id ?? 0) === $selected_feedback_ticket_id) {
                    $selected_feedback_ticket = $ticket;
                    break;
                }
            }

            if($selected_feedback_ticket) {
                $this->mark_ticket_as_user_read($selected_feedback_ticket);
                $selected_messages = db()->where('feedback_ticket_id', $selected_feedback_ticket->feedback_ticket_id)->orderBy('feedback_ticket_message_id', 'ASC')->get('feedback_ticket_messages');
            }
        }

        $data = [
            'feedback_tickets' => $feedback_tickets,
            'recent_admin_notifications' => $this->get_recent_admin_notifications(),
            'selected_feedback_ticket' => $selected_feedback_ticket,
            'selected_messages' => $selected_messages,
        ];

        $view = new \Altum\View('feedback-tickets/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }

    public function ticket() {
        $feedback_ticket_id = isset($this->params[0]) ? (int) $this->params[0] : 0;
        redirect('feedback-tickets' . ($feedback_ticket_id > 0 ? '?ticket_id=' . $feedback_ticket_id : ''));
    }
}
/* /Custom code: FC-2026-03-08 */
