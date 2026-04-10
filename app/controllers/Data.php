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

class Data extends Controller {

    public function index() {

        if(!settings()->links->biolinks_is_enabled) {
            throw_404();
        }

        \Altum\Authentication::guard();

        $_GET['preferred_contact_channel'] = isset($_GET['preferred_contact_channel']) ? input_clean($_GET['preferred_contact_channel'], 32) : null;
        $allowed_contact_channels = ['whatsapp', 'viber', 'sms', 'phone', 'email'];
        if(!in_array($_GET['preferred_contact_channel'], $allowed_contact_channels, true)) {
            $_GET['preferred_contact_channel'] = null;
        }

        $excluded_data_types = ['leader_os_fraud_cluster', 'billing_event'];
        $excluded_data_types_sql = implode(', ', array_map(static function(string $type): string {
            return "'" . database()->escape($type) . "'";
        }, $excluded_data_types));

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['datum_id', 'biolink_block_id', 'link_id', 'project_id', 'user_id', 'type', 'is_enabled'], [], ['datum_id', 'datetime']));
        $filters->set_default_order_by($this->user->preferences->data_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        $preferred_channel_sql = '';
        if($_GET['preferred_contact_channel']) {
            $preferred_contact_channel = database()->escape($_GET['preferred_contact_channel']);
            $preferred_channel_sql = " AND JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.preferred_contact_channel')) = '{$preferred_contact_channel}'";
        }

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `data` WHERE `user_id` = {$this->user->user_id} AND `type` NOT IN ({$excluded_data_types_sql}) {$preferred_channel_sql} {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('data?' . $filters->get_get() . '&page=%d')));

        $normalize_phone = function($phone) {
            return preg_replace('/[^0-9]/', '', (string) $phone);
        };

        $normalize_phone_with_plus = function($phone) {
            return preg_replace('/[^0-9+]/', '', (string) $phone);
        };

        $build_contact_actions = function($phone, $email, $name, $app_name, $preferred_channel) use ($normalize_phone, $normalize_phone_with_plus) {
            $phone_digits = $normalize_phone($phone);
            $phone_plus = $normalize_phone_with_plus($phone);
            $preferred_channel = trim((string) $preferred_channel);

            $message = sprintf(
                'Pozdrav%s, javljam se vezano uz vaš upit%s.',
                $name ? ' ' . $name : '',
                $app_name ? ' preko ' . $app_name : ''
            );

            $actions = [
                'whatsapp_url' => $phone_digits ? 'https://wa.me/' . $phone_digits . '?text=' . rawurlencode($message) : null,
                'viber_url' => $phone_plus ? 'viber://chat?number=' . rawurlencode($phone_plus) : null,
                'sms_url' => $phone_plus ? 'sms:' . $phone_plus . '?body=' . rawurlencode($message) : null,
                'call_url' => $phone_plus ? 'tel:' . $phone_plus : null,
                'email_url' => $email ? 'mailto:' . $email . '?subject=' . rawurlencode('Upit preko FCC aplikacije') . '&body=' . rawurlencode($message) : null,
            ];

            $preferred_order = [
                'whatsapp' => ['whatsapp_url', 'sms_url', 'call_url', 'email_url'],
                'viber' => ['viber_url', 'whatsapp_url', 'sms_url', 'call_url', 'email_url'],
                'sms' => ['sms_url', 'whatsapp_url', 'call_url', 'email_url'],
                'phone' => ['call_url', 'whatsapp_url', 'sms_url', 'email_url'],
                'email' => ['email_url', 'whatsapp_url', 'sms_url', 'call_url'],
            ][$preferred_channel ?: 'whatsapp'] ?? ['whatsapp_url', 'sms_url', 'call_url', 'email_url'];

            $action_meta = [
                'whatsapp_url' => ['label' => 'WhatsApp', 'icon' => 'fab fa-whatsapp', 'class' => 'is-whatsapp'],
                'viber_url' => ['label' => 'Viber', 'icon' => 'fas fa-comment-dots', 'class' => 'is-viber'],
                'sms_url' => ['label' => 'SMS', 'icon' => 'fas fa-sms', 'class' => 'is-sms'],
                'call_url' => ['label' => 'Nazovi', 'icon' => 'fas fa-phone-alt', 'class' => 'is-call'],
                'email_url' => ['label' => 'Email', 'icon' => 'fas fa-envelope', 'class' => 'is-email'],
            ];

            $primary_action = null;
            foreach($preferred_order as $action_key) {
                if(!empty($actions[$action_key])) {
                    $primary_action = array_merge(['key' => $action_key, 'url' => $actions[$action_key]], $action_meta[$action_key]);
                    break;
                }
            }

            $available_actions = [];
            foreach($action_meta as $action_key => $meta) {
                if(empty($actions[$action_key])) {
                    continue;
                }

                $available_actions[] = array_merge(['key' => $action_key, 'url' => $actions[$action_key]], $meta);
            }

            return [
                'primary_action' => $primary_action,
                'available_actions' => $available_actions,
            ] + $actions;
        };

        /* Get the data list for the user */
        $data = [];
        $summary = [
            'total' => 0,
            'with_phone' => 0,
            'with_email' => 0,
            'with_message' => 0,
            'with_whatsapp' => 0,
            'needs_review' => 0,
        ];
        $data_result = database()->query("
            SELECT `data`.*, `biolinks_blocks`.`settings`, `links`.`url` AS `link_url`
            FROM `data` 
            LEFT JOIN `biolinks_blocks` ON `biolinks_blocks`.`biolink_block_id` = `data`.`biolink_block_id`
            LEFT JOIN `links` ON `links`.`link_id` = `data`.`link_id`
            WHERE 
                `data`.`user_id` = {$this->user->user_id} 
                AND `data`.`type` NOT IN ({$excluded_data_types_sql})
                {$preferred_channel_sql}
                {$filters->get_sql_where('data')} 
                    
                {$filters->get_sql_order_by('data')} 
                {$paginator->get_sql_limit()}
            ");
        while($row = $data_result->fetch_object()) {
            $row->data = json_decode($row->data);
            $row->settings = json_decode($row->settings ?? '');
            $row->biolink_block_name = $row->settings->name ?? null;
            $row->app_name = $row->link_url ?? l('global.unknown');

            $row->contact_name = trim((string) ($row->data->name ?? $row->data->full_name ?? (($row->data->first_name ?? '') . ' ' . ($row->data->last_name ?? ''))));
            $row->contact_email = trim((string) ($row->data->email ?? ''));
            $row->contact_phone = trim((string) ($row->data->phone_e164 ?? $row->data->phone ?? $row->data->whatsapp ?? $row->data->mobile ?? ''));
            $row->contact_message = trim((string) ($row->data->message ?? ''));
            $row->preferred_contact_channel = trim((string) ($row->data->preferred_contact_channel ?? ''));
            $row->source_label = trim((string) ($row->data->source_label ?? ''));
            $row->source_context = trim((string) ($row->data->source_context ?? ''));
            $row->contact_identity = $row->contact_name ?: ($row->contact_email ?: ($row->contact_phone ?: l('global.unknown')));
            $row->initials = mb_strtoupper(mb_substr($row->contact_identity, 0, 2));
            $contact_actions = $build_contact_actions($row->contact_phone, $row->contact_email, $row->contact_name, $row->app_name, $row->preferred_contact_channel);
            $row->whatsapp_url = $contact_actions['whatsapp_url'];
            $row->viber_url = $contact_actions['viber_url'];
            $row->sms_url = $contact_actions['sms_url'];
            $row->call_url = $contact_actions['call_url'];
            $row->email_url = $contact_actions['email_url'];
            $row->primary_action = $contact_actions['primary_action'];
            $row->available_actions = $contact_actions['available_actions'];
            $row->contact_status = $row->primary_action ? 'ready' : 'needs_review';
            $row->contact_status_label = $row->primary_action ? 'Spreman za ' . $row->primary_action['label'] : 'Ručno provjeri kontakt';

            $excluded_keys = ['name', 'full_name', 'first_name', 'last_name', 'email', 'phone', 'phone_e164', 'phone_country_code', 'phone_dial_code', 'whatsapp', 'mobile', 'message', 'preferred_contact_channel', 'source_label', 'source_context', 'source_page_slug', 'source_page_url', 'contact_intent'];
            $row->extra_fields = [];
            foreach((array) $row->data as $key => $value) {
                if(in_array($key, $excluded_keys, true) || $value === '' || $value === null) {
                    continue;
                }

                $row->extra_fields[] = [
                    'label' => $key,
                    'value' => is_scalar($value) ? (string) $value : json_encode($value),
                ];
            }

            $summary['total']++;
            $summary['with_phone'] += $row->contact_phone ? 1 : 0;
            $summary['with_email'] += $row->contact_email ? 1 : 0;
            $summary['with_message'] += $row->contact_message ? 1 : 0;
            $summary['with_whatsapp'] += $row->whatsapp_url ? 1 : 0;
            $summary['needs_review'] += $row->primary_action ? 0 : 1;

            $data[] = $row;
        }

        /* Export handler */
        process_export_csv_new($data, ['datum_id', 'link_id', 'biolink_block_id', 'biolink_block_name', 'user_id', 'project_id', 'type', 'data', 'datetime'], ['data'], sprintf(l('data.title')));
        process_export_json($data, ['datum_id', 'link_id', 'biolink_block_id', 'biolink_block_name', 'user_id', 'project_id', 'type', 'data', 'datetime'], sprintf(l('data.title')));

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Prepare the view */
        $data = [
            'data'              => $data,
            'total_data'        => $total_rows,
            'pagination'        => $pagination,
            'filters'           => $filters,
            'biolink_blocks'    => require APP_PATH . 'includes/biolink_blocks.php',
            'summary'           => $summary,
            'contact_channel_options' => [
                'whatsapp' => 'WhatsApp',
                'viber' => 'Viber',
                'sms' => 'SMS',
                'phone' => 'Poziv',
                'email' => 'Email',
            ],
        ];

        $view = new \Altum\View('data/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        \Altum\Authentication::guard();

        /* Check for any errors */
        if (empty($_POST)) {
            throw_404();
        }

        if(empty($_POST['selected'])) {
            redirect('data');
        }

        if(!isset($_POST['type'])) {
            redirect('data');
        }

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            set_time_limit(0);

            session_write_close();

            $_POST['selected'] = is_array($_POST['selected']) ? array_unique(array_map('intval', $_POST['selected'])) : [];

            switch($_POST['type']) {
                case 'delete':

                    /* Team checks */
                    if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.data')) {
                        Alerts::add_error(l('global.info_message.team_no_access'));
                        redirect('data');
                    }

                    foreach($_POST['selected'] as $datum_id) {
                        db()->where('user_id', $this->user->user_id)->where('datum_id', $datum_id)->delete('data');
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('data');
    }

    public function delete() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.data')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('data');
        }

        if (empty($_POST)) {
            throw_404();
        }

        $datum_id = (int) $_POST['datum_id'];

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$datum = db()->where('datum_id', $datum_id)->where('user_id', $this->user->user_id)->getOne('data', ['datum_id'])) {
            throw_404();
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Delete the resource */
            db()->where('datum_id', $datum_id)->delete('data');

            /* Set a nice success message */
            Alerts::add_success(l('global.success_message.delete2'));

            redirect('data');
        }

        redirect('data');
    }
}
