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

function get_email_template($email_template_subject_array, $email_template_subject, $email_template_body_array, $email_template_body) {

    $email_template_subject = str_replace(
        array_keys($email_template_subject_array),
        array_values($email_template_subject_array),
        $email_template_subject
    );

    $email_template_body = str_replace(
        array_keys($email_template_body_array),
        array_values($email_template_body_array),
        $email_template_body
    );

    return (object) [
        'subject' => $email_template_subject,
        'body' => $email_template_body
    ];
}

function process_send_mail_template($title, $content, $data = []) {
    /* Templating for the title */
    $replacers = [
        '{{WEBSITE_TITLE}}' => settings()->main->title,
    ];

    $title = str_replace(
        array_keys($replacers),
        array_values($replacers),
        $title
    );

    /* Prepare the content */
    $replacers = [
        '{{WEBSITE_TITLE}}' => settings()->main->title,
    ];

    $content = str_replace(
        array_keys($replacers),
        array_values($replacers),
        $content
    );

    /* Process spintax */
    $title = process_spintax($title);
    $content = process_spintax($content);

    /* Get the email template */
    $email_template = include_view(THEME_PATH . 'views/partials/email_wrapper.php', [
        'is_broadcast' => $data['is_broadcast'] ?? null,
        'is_system_email' => $data['is_system_email'] ?? true,
        /* Custom code: FC-2026-03-19: render one-click unsubscribe footer in shared wrapper */
        'unsubscribe_url' => $data['unsubscribe_url'] ?? null,
        /* /Custom code: FC-2026-03-19 */
        'anti_phishing_code' => $data['anti_phishing_code'] ?? null,
        'language' => $data['language'] ?? settings()->main->default_language,
        'content' => $content,
    ]);

    return [
        'title' => $title,
        'content' => $content,
        'email_template' => $email_template,
    ];
}

function send_server_mail($to, $from, $title, $content, $reply_to = null) {

    $headers = "From: " . settings()->smtp->from_name . " <" . strip_tags($from) . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    /* Reply to */
    if($reply_to) {
        $headers .= "Reply-To: " . $reply_to . "\r\n";
    } else {

        /* Check for custom reply_to */
        if(!empty(settings()->smtp->reply_to) && !empty(settings()->smtp->reply_to_name)) {
            $headers .= "Reply-To: " . settings()->smtp->reply_to_name . " <" . settings()->smtp->reply_to . ">\r\n";

        } else {
            $headers .= "Reply-To: " . settings()->smtp->from_name . " <" . settings()->smtp->from . ">\r\n";
        }
    }

    /* CC */
    if(settings()->smtp->cc) {
        $headers .= "CC: " . settings()->smtp->cc . "\r\n";
    }

    /* BCC */
    if(settings()->smtp->bcc) {
        $headers .= "BCC: " . settings()->smtp->bcc . "\r\n";
    }

    /* Sent to multiple addresses if $to variable is array of emails */
    if(is_array($to)) {
        $to = implode(',', $to);
    }

    return mail($to, $title, $content, $headers);
}

/* Custom code: FC-2026-03-18: Brevo API transport for automation emails */
function get_mail_transport(): string {
    $transport = settings()->smtp->transport ?? null;

    if(in_array($transport, ['smtp', 'brevo_api'], true)) {
        return $transport;
    }

    return 'smtp';
}

function fc_log_mail_transport_error(string $transport, string $message, array $context = []): void {
    $payload = [
        'transport' => $transport,
        'message' => $message,
        'context' => $context,
    ];

    error_log('[Mail Transport] ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function get_brevo_api_key(): string {
    return settings()->smtp->brevo_api_key ?? (defined('BREVO_API_KEY') ? BREVO_API_KEY : '');
}

function get_brevo_api_base_url(): string {
    return defined('BREVO_API_BASE_URL') ? BREVO_API_BASE_URL : 'https://api.brevo.com/v3';
}

function brevo_is_configured(): bool {
    return get_mail_transport() === 'brevo_api' && !empty(get_brevo_api_key());
}

function send_brevo_mail($to, $title, $content, $data = [], $reply_to = null, $debug = false) {

    /* Custom code: FC-2026-03-19: optionally return a structured transport result */
    $should_return_transport_result = $debug || !empty($data['return_transport_result']);
    /* /Custom code: FC-2026-03-19 */

    if(!function_exists('curl_init')) {
        fc_log_mail_transport_error('brevo_api', 'PHP curl extension is not available.', [
            'has_curl_init' => false,
        ]);

        if($should_return_transport_result) {
            $result = new \stdClass();
            $result->success = false;
            $result->status_code = 0;
            $result->response_body = null;
            $result->response_json = null;
            $result->curl_error = 'PHP curl extension is not available.';
            $result->payload = null;
            $result->message_id = null;
            $result->ErrorInfo = 'PHP curl extension is not available.';
            $result->errors = ['PHP curl extension is not available on this server.'];

            return $result;
        }

        return false;
    }

    extract(process_send_mail_template($title, $content, $data));

    $to_addresses = is_array($to) ? $to : [$to];
    $to_payload = [];

    foreach($to_addresses as $address) {
        $address = trim((string) $address);

        if($address === '') {
            continue;
        }

        $to_payload[] = ['email' => $address];
    }

    if(empty($to_payload)) {
        fc_log_mail_transport_error('brevo_api', 'Missing recipient email.', [
            'to' => $to,
            'subject' => $title,
        ]);

        if($should_return_transport_result) {
            $result = new \stdClass();
            $result->success = false;
            $result->status_code = 0;
            $result->response_body = null;
            $result->response_json = null;
            $result->curl_error = 'Missing recipient email.';
            $result->payload = null;
            $result->message_id = null;
            $result->ErrorInfo = 'Missing recipient email.';
            $result->errors = ['Missing recipient email.'];

            return $result;
        }

        return false;
    }

    $payload = [
        'sender' => [
            'name' => settings()->smtp->from_name,
            'email' => settings()->smtp->from,
        ],
        'to' => $to_payload,
        'subject' => $title,
        'htmlContent' => $email_template,
        'textContent' => strip_tags($email_template),
    ];

    /* Custom code: FC-2026-03-19: support Brevo tags and transport response metadata */
    if(!empty($data['brevo_tags']) && is_array($data['brevo_tags'])) {
        $payload['tags'] = array_values(array_filter(array_map('strval', $data['brevo_tags'])));
    }

    if(!empty($data['brevo_headers']) && is_array($data['brevo_headers'])) {
        $payload['headers'] = $data['brevo_headers'];
    }

    /* Custom code: FC-2026-03-19: expose unsubscribe route in provider headers as well */
    if(!empty($data['unsubscribe_url'])) {
        $payload['headers'] = array_merge($payload['headers'] ?? [], [
            'List-Unsubscribe' => '<' . $data['unsubscribe_url'] . '>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }
    /* /Custom code: FC-2026-03-19 */
    /* /Custom code: FC-2026-03-19 */

    if($reply_to) {
        $payload['replyTo'] = ['email' => $reply_to];
    } elseif(!empty(settings()->smtp->reply_to)) {
        $payload['replyTo'] = [
            'email' => settings()->smtp->reply_to,
            'name' => settings()->smtp->reply_to_name ?: settings()->smtp->from_name,
        ];
    }

    $json_payload = json_encode($payload);

    if($json_payload === false) {
        fc_log_mail_transport_error('brevo_api', 'Failed to encode Brevo payload as JSON.', [
            'subject' => $title,
            'json_last_error' => function_exists('json_last_error_msg') ? json_last_error_msg() : 'Unknown JSON error',
        ]);

        if($should_return_transport_result) {
            $result = new \stdClass();
            $result->success = false;
            $result->status_code = 0;
            $result->response_body = null;
            $result->response_json = null;
            $result->curl_error = 'Failed to encode Brevo payload as JSON.';
            $result->payload = $payload;
            $result->message_id = null;
            $result->ErrorInfo = 'Failed to encode Brevo payload as JSON.';
            $result->errors = ['Failed to encode Brevo payload as JSON.'];

            return $result;
        }

        return false;
    }

    $curl_handle = curl_init(get_brevo_api_base_url() . '/smtp/email');

    curl_setopt_array($curl_handle, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . get_brevo_api_key(),
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS => $json_payload,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response_body = curl_exec($curl_handle);
    $response_code = (int) curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl_handle);
    curl_close($curl_handle);

    $response_json = json_decode($response_body ?? '');
    $message_id = is_object($response_json) ? fc_normalize_brevo_message_id($response_json->messageId ?? null) : null;

    $is_success = empty($curl_error) && $response_code >= 200 && $response_code < 300;

    if(!$is_success) {
        fc_log_mail_transport_error('brevo_api', 'Brevo API request failed.', [
            'status_code' => $response_code,
            'curl_error' => $curl_error,
            'response_body' => $response_body,
            'subject' => $title,
            'to' => $to_payload,
        ]);
    }

    if($should_return_transport_result) {
        $result = new \stdClass();
        $result->success = $is_success;
        $result->status_code = $response_code;
        $result->response_body = $response_body;
        $result->response_json = $response_json;
        $result->curl_error = $curl_error;
        $result->payload = $payload;
        $result->message_id = $message_id;
        $result->ErrorInfo = $is_success ? '' : trim('Brevo API request failed. HTTP ' . $response_code . ' ' . $curl_error);
        $result->errors = array_values(array_filter([$curl_error, $response_body]));

        return $result;
    }

    return $is_success;
}

function send_automation_mail($to, $title, $content, $data = [], $reply_to = null, $debug = false) {
    return send_mail($to, $title, $content, $data, $reply_to, $debug);
}
/* /Custom code: FC-2026-03-18 */

function send_mail($to, $title, $content, $data = [], $reply_to = null, $debug = false) {

    if(get_mail_transport() === 'brevo_api' && !empty(get_brevo_api_key())) {
        return send_brevo_mail($to, $title, $content, $data, $reply_to, $debug);
    }

    extract(process_send_mail_template($title, $content, $data));

    if(empty(settings()->smtp->host)) {
        fc_log_mail_transport_error('smtp', 'SMTP host is missing.', [
            'subject' => $title,
            'to' => $to,
        ]);

        return false;
    }

    /* Use phpmailer SMTP */
    try {
        /* Initiate phpMailer */
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->isHTML(true);

        /* Set the debugging for phpMailer */
        $mail->SMTPDebug = $debug ? 2 : 0;

        /* SMTP settings */
        if(settings()->smtp->encryption != '0') {
            $mail->SMTPSecure = settings()->smtp->encryption;
        }
        $mail->SMTPAuth = settings()->smtp->auth;
        $mail->Host = settings()->smtp->host;
        $mail->Port = settings()->smtp->port;
        $mail->Username = settings()->smtp->username;
        $mail->Password = settings()->smtp->password;

        /* Timeout */
        $mail->Timeout = 5;

        /* Email sent from */
        $mail->setFrom(settings()->smtp->from, settings()->smtp->from_name);

        /* Reply to */
        if($reply_to) {
            $mail->addReplyTo($reply_to);
        } else {

            /* Check for custom reply_to */
            if(!empty(settings()->smtp->reply_to) && !empty(settings()->smtp->reply_to_name)) {
                $mail->addReplyTo(settings()->smtp->reply_to, settings()->smtp->reply_to_name);
            } else {
                $mail->addReplyTo(settings()->smtp->from, settings()->smtp->from_name);
            }
        }

        /* Custom code: FC-2026-03-19: add one-click unsubscribe headers for SMTP sends */
        if(!empty($data['unsubscribe_url'])) {
            $mail->addCustomHeader('List-Unsubscribe', '<' . $data['unsubscribe_url'] . '>');
            $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        }
        /* /Custom code: FC-2026-03-19 */

        /* Sent to multiple addresses if $to variable is array of emails */
        if(is_array($to)) {
            foreach($to as $address) {
                $mail->addAddress($address);
            }
        } else {
            $mail->addAddress($to);
        }

        /* CC */
        if(settings()->smtp->cc) {
            $cc_emails = explode(',', settings()->smtp->cc);
            foreach($cc_emails as $email) {
                $mail->addCC($email);
            }
        }

        /* BCC */
        if(settings()->smtp->bcc) {
            $bcc_emails = explode(',', settings()->smtp->bcc);
            foreach($bcc_emails as $email) {
                $mail->addBCC($email);
            }
        }

        /* Email title & content */
        $mail->Subject = $title;
        $mail->Body = $email_template;
        $mail->AltBody = strip_tags($mail->Body);

        /* Save errors in array for debugging */
        $errors = [];

        if($debug) {
            $mail->Debugoutput = function($string, $level) use(&$errors) {
                $errors[] = $string;
            };
        }

        /* Send the mail */
        $send = $mail->send();

        /* Save the errors in the returned object for output purposes */
        if($debug) {
            $mail->errors = $errors;
        }

        if(!$send) {
            fc_log_mail_transport_error('smtp', 'PHPMailer send returned false.', [
                'subject' => $title,
                'to' => $to,
                'error_info' => $mail->ErrorInfo,
                'debug_errors' => $errors,
            ]);
        }

        return $debug ? $mail : $send;
    } catch (Exception $e) {
        fc_log_mail_transport_error('smtp', 'PHPMailer exception thrown.', [
            'subject' => $title,
            'to' => $to,
            'exception_message' => $e->getMessage(),
        ]);

        return $debug ? $mail : false;
    }

}
