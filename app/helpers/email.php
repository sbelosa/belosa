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

    if(in_array($transport, ['mail', 'smtp', 'brevo_api'], true)) {
        return $transport;
    }

    return !empty(settings()->smtp->host) ? 'smtp' : 'mail';
}

function get_brevo_api_key(): string {
    return settings()->smtp->brevo_api_key ?? BREVO_API_KEY;
}

function brevo_is_configured(): bool {
    return get_mail_transport() === 'brevo_api' && !empty(get_brevo_api_key());
}

function send_brevo_mail($to, $title, $content, $data = [], $reply_to = null, $debug = false) {

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
        return $debug ? ['success' => false, 'error' => 'Missing recipient email.'] : false;
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

    if($reply_to) {
        $payload['replyTo'] = ['email' => $reply_to];
    } elseif(!empty(settings()->smtp->reply_to)) {
        $payload['replyTo'] = [
            'email' => settings()->smtp->reply_to,
            'name' => settings()->smtp->reply_to_name ?: settings()->smtp->from_name,
        ];
    }

    $curl_handle = curl_init(BREVO_API_BASE_URL . '/smtp/email');

    curl_setopt_array($curl_handle, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . get_brevo_api_key(),
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response_body = curl_exec($curl_handle);
    $response_code = (int) curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl_handle);
    curl_close($curl_handle);

    $is_success = empty($curl_error) && $response_code >= 200 && $response_code < 300;

    if($debug) {
        $result = new \stdClass();
        $result->success = $is_success;
        $result->status_code = $response_code;
        $result->response_body = $response_body;
        $result->curl_error = $curl_error;
        $result->payload = $payload;
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

    /* Use sendmail from server */
    if(get_mail_transport() === 'mail' || empty(settings()->smtp->host)) {
        return send_server_mail($to, settings()->smtp->from, $title, $email_template, $reply_to);
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

        return $debug ? $mail : $send;
    } catch (Exception $e) {
        return $debug ? $mail : false;
    }

}
