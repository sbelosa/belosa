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

/* Custom code: FC-2026-03-08: admin feedback tickets module */
class AdminFeedbackTickets extends Controller {

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

        /* Custom code: FC-2026-03-08: prevent crash when feedback tables are missing */
        if(!$this->has_feedback_tables()) {
            Alerts::add_error(sprintf(l('admin_feedback_tickets.alert.migration_missing'), 'baze/feedback_tickets_migration_2026-03-08.sql'));
            redirect('admin/');
        }

        $this->ensure_feedback_workflow_columns();
        $this->auto_close_read_answered_tickets();
        /* /Custom code: FC-2026-03-08 */

        $feedback_tickets = db()
            ->join('users', 'feedback_tickets.user_id = users.user_id', 'LEFT')
            ->orderBy('feedback_tickets.last_datetime', 'DESC')
            ->get('feedback_tickets', null, [
                'feedback_tickets.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.avatar as user_avatar',
            ]);

        $data = [
            'feedback_tickets' => $feedback_tickets,
        ];

        $view = new \Altum\View('admin/feedback-tickets/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }

    public function ticket() {

        /* Custom code: FC-2026-03-08: prevent crash when feedback tables are missing */
        if(!$this->has_feedback_tables()) {
            Alerts::add_error(sprintf(l('admin_feedback_tickets.alert.migration_missing'), 'baze/feedback_tickets_migration_2026-03-08.sql'));
            redirect('admin/');
        }

        $this->ensure_feedback_workflow_columns();
        $this->auto_close_read_answered_tickets();
        /* /Custom code: FC-2026-03-08 */

        $feedback_ticket_id = isset($this->params[0]) ? (int) $this->params[0] : 0;

        if(!$feedback_ticket = db()
            ->join('users', 'feedback_tickets.user_id = users.user_id', 'LEFT')
            ->where('feedback_tickets.feedback_ticket_id', $feedback_ticket_id)
            ->getOne('feedback_tickets', [
                'feedback_tickets.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.avatar as user_avatar',
            ])) {
            redirect('admin/feedback-tickets');
        }

        if(!empty($_POST)) {
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if(($_POST['action_type'] ?? null) === 'close' && !Alerts::has_errors()) {
                db()->insert('feedback_ticket_messages', [
                    'feedback_ticket_id' => $feedback_ticket->feedback_ticket_id,
                    'user_id' => $feedback_ticket->user_id,
                    'admin_user_id' => $this->user->user_id,
                    'is_admin_reply' => 1,
                    'message' => 'Ticket je označen kao riješen. Ako trebaš dodatnu pomoć, slobodno odgovori na ovaj ticket i ponovno ćemo ga otvoriti.',
                    'attachment' => null,
                    'datetime' => get_date(),
                ]);

                db()->where('feedback_ticket_id', $feedback_ticket->feedback_ticket_id)->update('feedback_tickets', [
                    'status' => 'closed',
                    'last_datetime' => get_date(),
                    'admin_last_replied_at' => get_date(),
                    'user_last_read_at' => null,
                ]);

                db()->insert('internal_notifications', [
                    'user_id' => $feedback_ticket->user_id,
                    'for_who' => 'user',
                    'from_who' => 'admin',
                    'title' => 'Tvoj support ticket je riješen',
                    'description' => sprintf('Ticket "%s" je označen kao riješen. Otvori ticket ako želiš provjeriti odgovor ili dodati novo pitanje.', (string) ($feedback_ticket->subject ?? '')),
                    'url' => url('feedback-tickets/ticket/' . $feedback_ticket->feedback_ticket_id),
                    'icon' => 'fas fa-check-circle',
                    'datetime' => get_date(),
                ]);

                db()->where('user_id', $feedback_ticket->user_id)->update('users', [
                    'has_pending_internal_notifications' => 1,
                ]);

                Alerts::add_success(l('admin_feedback_tickets.alert.closed'));
                redirect('admin/feedback-tickets/ticket/' . $feedback_ticket->feedback_ticket_id);
            }

            if(($_POST['action_type'] ?? null) === 'reopen' && !Alerts::has_errors()) {
                db()->where('feedback_ticket_id', $feedback_ticket->feedback_ticket_id)->update('feedback_tickets', [
                    'status' => 'open',
                    'last_datetime' => get_date(),
                ]);

                Alerts::add_success(l('admin_feedback_tickets.alert.reopened'));
                redirect('admin/feedback-tickets/ticket/' . $feedback_ticket->feedback_ticket_id);
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
                        'user_id' => $feedback_ticket->user_id,
                        'admin_user_id' => $this->user->user_id,
                        'is_admin_reply' => 1,
                        'message' => $_POST['message'],
                        'attachment' => $attachment,
                        'datetime' => get_date(),
                    ]);

                    db()->where('feedback_ticket_id', $feedback_ticket->feedback_ticket_id)->update('feedback_tickets', [
                        'status' => 'answered',
                        'last_datetime' => get_date(),
                        'admin_last_replied_at' => get_date(),
                        'user_last_read_at' => null,
                    ]);

                    db()->insert('internal_notifications', [
                        'user_id' => $feedback_ticket->user_id,
                        'for_who' => 'user',
                        'from_who' => 'admin',
                        'title' => l('admin_feedback_tickets.notification.title'),
                        'description' => sprintf(l('admin_feedback_tickets.notification.description'), $feedback_ticket->subject),
                        'url' => url('feedback-tickets/ticket/' . $feedback_ticket->feedback_ticket_id),
                        'icon' => 'fas fa-reply',
                        'datetime' => get_date(),
                    ]);

                    db()->where('user_id', $feedback_ticket->user_id)->update('users', [
                        'has_pending_internal_notifications' => 1,
                    ]);

                    Alerts::add_success(l('admin_feedback_tickets.alert.reply_sent'));
                    redirect('admin/feedback-tickets/ticket/' . $feedback_ticket->feedback_ticket_id);
                }
            }
        }

        $messages = db()->where('feedback_ticket_id', $feedback_ticket->feedback_ticket_id)->orderBy('feedback_ticket_message_id', 'ASC')->get('feedback_ticket_messages');

        $data = [
            'feedback_ticket' => $feedback_ticket,
            'messages' => $messages,
        ];

        $view = new \Altum\View('admin/feedback-tickets/ticket', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }
}
/* /Custom code: FC-2026-03-08 */
