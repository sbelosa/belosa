<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_chat_config = (array) ($data->config ?? []);
$fcc_chat_assistant_type = trim((string) ($fcc_chat_config['assistant_type'] ?? 'product_advisor'));
$fcc_chat_definition = fcc_ai_get_assistant_type($fcc_chat_assistant_type) ?? [];
$fcc_chat_scope = trim((string) ($fcc_chat_config['scope'] ?? 'public_app'));
$fcc_chat_link_id = (int) ($fcc_chat_config['link_id'] ?? 0);
$fcc_chat_blog_post_id = (int) ($fcc_chat_config['blog_post_id'] ?? 0);
$fcc_chat_owner_name = trim((string) ($fcc_chat_config['owner_name'] ?? ''));
$fcc_chat_language_code = trim((string) ($fcc_chat_config['language_code'] ?? \Altum\Language::$code ?? \Altum\Language::$default_code ?? 'hr'));
$fcc_chat_source_context = trim((string) ($fcc_chat_config['source_context'] ?? ($fcc_chat_scope === 'public_blog' ? 'FCC blog article' : 'FCC app popup chat')));
$fcc_chat_hide_without_context = !empty($fcc_chat_config['hide_without_context']);
$fcc_chat_is_product_advisor = $fcc_chat_assistant_type === 'product_advisor';
$fcc_chat_dom_id = trim((string) ($fcc_chat_config['dom_id'] ?? ('fcc-chat-extreme-' . md5($fcc_chat_assistant_type . '|' . $fcc_chat_scope . '|' . $fcc_chat_link_id . '|' . $fcc_chat_blog_post_id))));
$fcc_chat_shell_id = $fcc_chat_dom_id . '-shell';
$fcc_chat_toggle_id = $fcc_chat_dom_id . '-toggle';
$fcc_chat_thread_id = $fcc_chat_dom_id . '-thread';
$fcc_chat_lead_id = $fcc_chat_dom_id . '-lead';
$fcc_chat_form_id = $fcc_chat_dom_id . '-form';
$fcc_chat_lead_enabled = array_key_exists('lead_enabled', $fcc_chat_config)
    ? !empty($fcc_chat_config['lead_enabled'])
    : !empty($fcc_chat_definition['supports_lead_capture']);
$fcc_chat_is_english = str_starts_with(mb_strtolower($fcc_chat_language_code), 'en');
$fcc_chat_toggle_label = $fcc_chat_is_product_advisor
    ? ($fcc_chat_is_english ? 'Open FCC Preporuka' : 'Otvori FCC Preporuka')
    : ($fcc_chat_is_english ? 'Open ChatExtreme' : 'Otvori ChatExtreme');
$fcc_chat_close_label = $fcc_chat_is_english ? 'Close chat' : 'Zatvori chat';
$fcc_chat_assistant_title = trim((string) ($fcc_chat_config['assistant_title'] ?? ($fcc_chat_assistant_type === 'coach' ? 'FCC Coach' : ($fcc_chat_is_product_advisor ? 'FCC Preporuka' : 'Extreme Chat Pets'))));
$fcc_chat_intro_label = trim((string) ($fcc_chat_config['intro_label'] ?? $fcc_chat_assistant_title));
$fcc_chat_message_maxlength = $fcc_chat_assistant_type === 'coach' ? 2000 : 1000;
$fcc_chat_input_placeholder = trim((string) ($fcc_chat_config['input_placeholder'] ?? ($fcc_chat_assistant_type === 'coach'
    ? ($fcc_chat_is_english ? 'How can I help you inside FCC?' : 'Kako ti mogu pomoći unutar FCC-a?')
    : ($fcc_chat_is_english ? 'What would you like to know?' : 'Što vas zanima?'))));
$fcc_chat_coach_badge = trim((string) ($fcc_chat_config['coach_badge'] ?? ''));
$fcc_chat_coach_notice = trim((string) ($fcc_chat_config['coach_notice'] ?? ''));
$fcc_chat_coach_mode_key = trim((string) ($fcc_chat_config['coach_mode_key'] ?? ($fcc_chat_assistant_type === 'coach' ? 'default' : '')));
$fcc_chat_ui_copy_override = is_array($fcc_chat_config['ui_copy_override'] ?? null) ? $fcc_chat_config['ui_copy_override'] : [];
$fcc_chat_send_label = $fcc_chat_is_english ? 'Send' : 'Pošalji';
$fcc_chat_lead_title = $fcc_chat_is_english ? 'Leave your contact' : 'Ostavite kontakt';
$fcc_chat_lead_text = $fcc_chat_is_english ? 'The partner can continue personally.' : 'Suradnik može osobno nastaviti razgovor.';
$fcc_chat_lead_name_placeholder = $fcc_chat_is_english ? 'Your name' : 'Vaše ime';
$fcc_chat_lead_email_placeholder = 'Email';
$fcc_chat_lead_phone_placeholder = $fcc_chat_is_english ? 'WhatsApp or phone' : 'WhatsApp ili telefon';
$fcc_chat_lead_message_placeholder = $fcc_chat_is_english ? 'Short note or goal' : 'Kratka poruka ili cilj';
$fcc_chat_lead_submit_label = $fcc_chat_is_english ? 'Send contact' : 'Pošalji kontakt';
$fcc_chat_lead_toggle_open_text = $fcc_chat_is_english ? 'Open' : 'Otvori';
$fcc_chat_lead_toggle_close_text = $fcc_chat_is_english ? 'Hide' : 'Spusti';
$fcc_chat_lead_toggle_open_label = $fcc_chat_is_english ? 'Open contact form' : 'Otvori kontakt formu';
$fcc_chat_lead_toggle_close_label = $fcc_chat_is_english ? 'Collapse contact form' : 'Spusti kontakt formu';
$fcc_chat_lead_collapsed_hint = $fcc_chat_is_english
    ? 'Open the form when you want to leave your contact, or continue chatting below.'
    : 'Otvori formu kad želiš ostaviti kontakt ili nastavi razgovor ispod.';
$fcc_chat_lead_consent_label = $fcc_chat_is_english
    ? 'I agree that the partner may contact me.'
    : 'Slažem se da me suradnik može kontaktirati.';
$fcc_chat_lead_success_message = $fcc_chat_is_english
    ? 'Thanks. Your contact was saved and the partner can continue from here.'
    : 'Hvala. Vaš kontakt je spremljen i suradnik sada može nastaviti razgovor.';
$fcc_chat_phone_label = $fcc_chat_is_english ? 'Phone' : 'Telefon';
$fcc_chat_storage_key = trim((string) ($fcc_chat_config['storage_key'] ?? fcc_ai_get_public_storage_key()));
$fcc_chat_context_storage_key = trim((string) ($fcc_chat_config['context_storage_key'] ?? fcc_ai_get_public_context_storage_key()));
$fcc_chat_conversation_url = trim((string) ($fcc_chat_config['conversation_url'] ?? url('l/fcc-ai/conversation')));
$fcc_chat_message_url = trim((string) ($fcc_chat_config['message_url'] ?? url('l/fcc-ai/message')));
$fcc_chat_feedback_url = trim((string) ($fcc_chat_config['feedback_url'] ?? ($fcc_chat_assistant_type === 'coach' ? url('fcc-ai/coach-feedback') : url('l/fcc-ai/feedback'))));
$fcc_chat_lead_url = trim((string) ($fcc_chat_config['lead_url'] ?? url('l/fcc-ai/lead')));
$fcc_chat_default_logo_url = SITE_URL . ASSETS_URL_PATH . 'images/chat-extreme-logo.png';
$fcc_chat_product_advisor_logo_url = SITE_URL . ASSETS_URL_PATH . 'images/fcc-preporuka-logo.png';
$fcc_chat_coach_logo_url = SITE_URL . ASSETS_URL_PATH . 'images/fcc-coach-logo-wide.png';
$fcc_chat_logo_url = $fcc_chat_assistant_type === 'coach'
    ? $fcc_chat_coach_logo_url
    : ($fcc_chat_is_product_advisor ? $fcc_chat_product_advisor_logo_url : $fcc_chat_default_logo_url);
$fcc_chat_logo_alt = $fcc_chat_assistant_type === 'coach'
    ? 'Forever Card Club'
    : ($fcc_chat_is_product_advisor ? 'FCC Preporuka' : 'ChatExtreme');
$fcc_chat_default_launcher_url = SITE_URL . ASSETS_URL_PATH . 'images/chat-extreme-owl.png';
$fcc_chat_product_advisor_launcher_url = SITE_URL . ASSETS_URL_PATH . 'images/fcc-preporuka-launcher.png';
$fcc_chat_default_launcher_fallback_url = SITE_URL . ASSETS_URL_PATH . 'images/sovica.png';
$fcc_chat_coach_launcher_url = SITE_URL . ASSETS_URL_PATH . 'images/fcc-coach-launcher-v2.png';
$fcc_chat_launcher_url = $fcc_chat_assistant_type === 'coach'
    ? $fcc_chat_coach_launcher_url
    : ($fcc_chat_is_product_advisor ? $fcc_chat_product_advisor_launcher_url : $fcc_chat_default_launcher_url);
$fcc_chat_launcher_fallback_url = $fcc_chat_default_launcher_fallback_url;
$fcc_chat_feedback_positive_label = $fcc_chat_is_english ? 'Good answer' : 'Dobar odgovor';
$fcc_chat_feedback_negative_label = $fcc_chat_is_english ? 'Bad answer' : 'Loš odgovor';
$fcc_chat_feedback_reason_title = $fcc_chat_is_english ? 'What was wrong?' : 'Što nije bilo dobro?';
$fcc_chat_feedback_reason_options = fcc_ai_get_feedback_reason_options($fcc_chat_language_code);
$fcc_chat_feedback_note_prompt = $fcc_chat_is_english
    ? 'If you want, briefly add a note about this answer.'
    : 'Ako želite, kratko dodajte napomenu o ovom odgovoru.';
$fcc_chat_feedback_saved_message = $fcc_chat_is_english
    ? 'Thanks, the feedback was saved.'
    : 'Hvala, feedback je spremljen.';
$fcc_chat_launcher_label = trim((string) ($fcc_chat_config['launcher_label'] ?? ''));
if($fcc_chat_launcher_label === '') {
    if($fcc_chat_assistant_type === 'coach') {
        $fcc_chat_launcher_label = $fcc_chat_assistant_title;
    } elseif($fcc_chat_assistant_type === 'pets_advisor') {
        $fcc_chat_launcher_label = $fcc_chat_is_english ? 'AI Pets' : 'AI Ljubimci';
    } else {
        $fcc_chat_launcher_label = 'FCC Preporuka';
    }
}
$fcc_chat_default_welcome = trim((string) ($fcc_chat_config['default_welcome'] ?? ''));
if($fcc_chat_default_welcome === '') {
    $fcc_chat_default_welcome = $fcc_chat_assistant_type === 'coach'
        ? fcc_ai_get_internal_coach_welcome_message($fcc_chat_language_code, $fcc_chat_owner_name)
        : fcc_ai_get_public_welcome_message($fcc_chat_assistant_type, $fcc_chat_language_code);
}

$fcc_chat_ui_copy = [];
foreach(['hr', 'en', 'sl', 'bg'] as $fcc_chat_ui_language) {
    $fcc_chat_ui_override = is_array($fcc_chat_ui_copy_override[$fcc_chat_ui_language] ?? null)
        ? $fcc_chat_ui_copy_override[$fcc_chat_ui_language]
        : [];
    $fcc_chat_ui_is_english = $fcc_chat_ui_language === 'en';
    $fcc_chat_ui_intro_label = $fcc_chat_assistant_type === 'coach'
        ? (string) ($fcc_chat_ui_override['intro_label'] ?? $fcc_chat_intro_label)
        : ($fcc_chat_assistant_type === 'pets_advisor' ? 'Extreme Chat Pets' : 'FCC Preporuka');

    if($fcc_chat_assistant_type === 'coach') {
        $fcc_chat_ui_launcher_label = (string) ($fcc_chat_ui_override['launcher_label'] ?? $fcc_chat_launcher_label);
        $fcc_chat_ui_input_placeholder = (string) ($fcc_chat_ui_override['input_placeholder'] ?? match($fcc_chat_ui_language) {
            'sl' => 'Kako ti lahko pomagam znotraj FCC-ja?',
            'bg' => 'Как мога да помогна във FCC?',
            'en' => 'How can I help you inside FCC?',
            default => 'Kako ti mogu pomoći unutar FCC-a?',
        });
    } elseif($fcc_chat_assistant_type === 'pets_advisor') {
        $fcc_chat_ui_launcher_label = match($fcc_chat_ui_language) {
            'sl' => 'AI ljubljenčki',
            'bg' => 'AI любимци',
            'en' => 'AI Pets',
            default => 'AI Ljubimci',
        };
        $fcc_chat_ui_input_placeholder = match($fcc_chat_ui_language) {
            'sl' => 'Napišite nekaj o ljubljenčku',
            'bg' => 'Напишете нещо за любимеца',
            'en' => 'Tell me about your pet',
            default => 'Napišite nešto o ljubimcu',
        };
    } else {
        $fcc_chat_ui_launcher_label = 'FCC Preporuka';
        $fcc_chat_ui_input_placeholder = match($fcc_chat_ui_language) {
            'sl' => 'Kaj vas zanima?',
            'bg' => 'Какво ви интересува?',
            'en' => 'What would you like to know?',
            default => 'Što vas zanima?',
        };
    }

    $fcc_chat_ui_copy[$fcc_chat_ui_language] = [
        'introLabel' => $fcc_chat_ui_intro_label,
        'launcherLabel' => $fcc_chat_ui_launcher_label,
        'inputPlaceholder' => $fcc_chat_ui_input_placeholder,
        'assistantTitle' => (string) ($fcc_chat_ui_override['assistant_title'] ?? $fcc_chat_assistant_title),
        'coachBadge' => (string) ($fcc_chat_ui_override['coach_badge'] ?? $fcc_chat_coach_badge),
        'coachNotice' => (string) ($fcc_chat_ui_override['coach_notice'] ?? $fcc_chat_coach_notice),
        'leadTitle' => match($fcc_chat_ui_language) {
            'sl' => 'Pustite kontakt',
            'bg' => 'Оставете контакт',
            'en' => 'Leave your contact',
            default => 'Ostavite kontakt',
        },
        'leadText' => match($fcc_chat_ui_language) {
            'sl' => 'Partner lahko osebno nadaljuje pogovor.',
            'bg' => 'Партньорът може лично да продължи разговора.',
            'en' => 'The partner can continue personally.',
            default => 'Suradnik može osobno nastaviti razgovor.',
        },
        'leadNamePlaceholder' => match($fcc_chat_ui_language) {
            'sl' => 'Vaše ime',
            'bg' => 'Вашето име',
            'en' => 'Your name',
            default => 'Vaše ime',
        },
        'leadPhonePlaceholder' => match($fcc_chat_ui_language) {
            'sl' => 'WhatsApp ali telefon',
            'bg' => 'WhatsApp или телефон',
            'en' => 'WhatsApp or phone',
            default => 'WhatsApp ili telefon',
        },
        'leadMessagePlaceholder' => match($fcc_chat_ui_language) {
            'sl' => 'Kratko sporočilo ali cilj',
            'bg' => 'Кратка бележка или цел',
            'en' => 'Short note or goal',
            default => 'Kratka poruka ili cilj',
        },
        'leadSubmitLabel' => match($fcc_chat_ui_language) {
            'sl' => 'Pošlji kontakt',
            'bg' => 'Изпрати контакт',
            'en' => 'Send contact',
            default => 'Pošalji kontakt',
        },
        'leadToggleOpenText' => match($fcc_chat_ui_language) {
            'sl' => 'Odpri',
            'bg' => 'Отвори',
            'en' => 'Open',
            default => 'Otvori',
        },
        'leadToggleCloseText' => match($fcc_chat_ui_language) {
            'sl' => 'Skrij',
            'bg' => 'Свий',
            'en' => 'Hide',
            default => 'Spusti',
        },
        'leadToggleOpenLabel' => match($fcc_chat_ui_language) {
            'sl' => 'Odpri kontaktni obrazec',
            'bg' => 'Отвори контактната форма',
            'en' => 'Open contact form',
            default => 'Otvori kontakt formu',
        },
        'leadToggleCloseLabel' => match($fcc_chat_ui_language) {
            'sl' => 'Zapri kontaktni obrazec',
            'bg' => 'Свий контактната форма',
            'en' => 'Collapse contact form',
            default => 'Spusti kontakt formu',
        },
        'leadCollapsedHint' => match($fcc_chat_ui_language) {
            'sl' => 'Odpri obrazec, ko želiš pustiti kontakt, ali nadaljuj klepet spodaj.',
            'bg' => 'Отвори формата, когато искаш да оставиш контакт, или продължи чата отдолу.',
            'en' => 'Open the form when you want to leave your contact, or continue chatting below.',
            default => 'Otvori formu kad želiš ostaviti kontakt ili nastavi razgovor ispod.',
        },
        'leadSuccessMessage' => match($fcc_chat_ui_language) {
            'sl' => 'Hvala. Vaš kontakt je shranjen in partner lahko nadaljuje pogovor.',
            'bg' => 'Благодарим. Вашият контакт е запазен и партньорът може да продължи разговора.',
            'en' => 'Thanks. Your contact was saved and the partner can continue from here.',
            default => 'Hvala. Vaš kontakt je spremljen i suradnik sada može nastaviti razgovor.',
        },
        'phoneLabel' => match($fcc_chat_ui_language) {
            'sl' => 'Telefon',
            'bg' => 'Телефон',
            'en' => 'Phone',
            default => 'Telefon',
        },
        'feedbackPositiveLabel' => match($fcc_chat_ui_language) {
            'sl' => 'Dober odgovor',
            'bg' => 'Добър отговор',
            'en' => 'Good answer',
            default => 'Dobar odgovor',
        },
        'feedbackNegativeLabel' => match($fcc_chat_ui_language) {
            'sl' => 'Slab odgovor',
            'bg' => 'Лош отговор',
            'en' => 'Bad answer',
            default => 'Loš odgovor',
        },
        'feedbackReasonTitle' => match($fcc_chat_ui_language) {
            'sl' => 'Kaj ni bilo v redu?',
            'bg' => 'Какво не беше наред?',
            'en' => 'What was wrong?',
            default => 'Što nije bilo dobro?',
        },
        'feedbackReasonOptions' => fcc_ai_get_feedback_reason_options($fcc_chat_ui_language),
        'feedbackNotePrompt' => match($fcc_chat_ui_language) {
            'sl' => 'Če želite, na kratko dodajte opombo o tem odgovoru.',
            'bg' => 'Ако желаете, добавете кратка бележка за този отговор.',
            'en' => 'If you want, briefly add a note about this answer.',
            default => 'Ako želite, kratko dodajte napomenu o ovom odgovoru.',
        },
        'feedbackSavedMessage' => match($fcc_chat_ui_language) {
            'sl' => 'Hvala, povratna informacija je shranjena.',
            'bg' => 'Благодарим, обратната връзка е запазена.',
            'en' => 'Thanks, the feedback was saved.',
            default => 'Hvala, feedback je spremljen.',
        },
        'defaultWelcome' => $fcc_chat_assistant_type === 'coach'
            ? (string) ($fcc_chat_ui_override['default_welcome'] ?? fcc_ai_get_internal_coach_welcome_message($fcc_chat_ui_language, $fcc_chat_owner_name))
            : fcc_ai_get_public_welcome_message($fcc_chat_assistant_type, $fcc_chat_ui_language),
    ];
}
?>

<style>
    .fcc-chat-extreme {
        position: fixed;
        right: 1rem;
        bottom: calc(1rem + var(--fcc-chat-widget-offset, 0px));
        z-index: 2147482000;
        font-family: "Avenir Next", "Segoe UI", sans-serif;
        pointer-events: none;
        transition: opacity .18s ease, visibility 0s linear .18s, transform .18s ease;
    }

    .fcc-tour-mode .fcc-chat-extreme,
    .fcc-chat-extreme.is-tutorial-hidden {
        opacity: 0;
        visibility: hidden;
        transform: translateY(12px);
        pointer-events: none;
    }

    .fcc-chat-extreme__launcher-stack {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: .35rem;
        pointer-events: auto;
        transition: transform .22s ease, opacity .22s ease, visibility 0s linear .22s;
    }

    .fcc-chat-extreme__launcher {
        width: 76px;
        height: 76px;
        border: 0;
        padding: 0;
        border-radius: 999px;
        background: transparent;
        box-shadow: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .fcc-chat-extreme__launcher img {
        width: 76px;
        height: 76px;
        display: block;
        object-fit: contain;
        filter: drop-shadow(0 10px 22px rgba(10, 10, 16, .42));
    }

    .fcc-chat-extreme__launcher-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 1.2rem;
        max-width: 132px;
        padding: .12rem .52rem;
        border-radius: 999px;
        background: rgba(13, 22, 36, .68);
        border: 1px solid rgba(127, 243, 230, .18);
        color: #eefcf9;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .01em;
        line-height: 1.15;
        text-align: center;
        text-shadow: 0 .25rem .8rem rgba(0, 0, 0, .24);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .06);
    }

    .fcc-chat-extreme__panel {
        position: absolute;
        right: 0;
        bottom: 0;
        width: min(390px, calc(100vw - 1rem));
        height: min(690px, calc(100vh - 1rem));
        min-height: 620px;
        background: #434343;
        border: 6px solid #d7d1c5;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(0, 0, 0, .42);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(18px) scale(.98);
        transition: transform .22s ease, opacity .22s ease, visibility 0s linear .22s;
    }

    .fcc-chat-extreme.is-open .fcc-chat-extreme__panel {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0) scale(1);
        transition: transform .22s ease, opacity .22s ease;
    }

    .fcc-chat-extreme.is-open .fcc-chat-extreme__launcher-stack {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(10px) scale(.9);
    }

    .fcc-chat-extreme.is-backgrounded .fcc-chat-extreme__launcher-stack {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(8px) scale(.92);
    }

    .fcc-chat-extreme__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        padding: 1rem 1rem .45rem;
        flex: 0 0 auto;
    }

    .fcc-chat-extreme__brand {
        max-width: 235px;
        width: 100%;
        height: auto;
        display: block;
        object-fit: contain;
    }

    .fcc-chat-extreme__coach-brand {
        display: flex;
        flex-direction: column;
        gap: .15rem;
        min-width: 0;
    }

    .fcc-chat-extreme__coach-brand-tag {
        color: rgba(125, 243, 230, .8);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        line-height: 1.1;
    }

    .fcc-chat-extreme__coach-brand-title {
        color: #ffffff;
        font-size: 1.42rem;
        font-weight: 800;
        letter-spacing: .01em;
        line-height: 1.05;
    }

    .fcc-chat-extreme__coach-badge {
        display: inline-flex;
        align-items: center;
        max-width: fit-content;
        margin-top: .35rem;
        padding: .28rem .6rem;
        border-radius: 999px;
        background: rgba(125, 243, 230, .12);
        border: 1px solid rgba(125, 243, 230, .16);
        color: rgba(220, 255, 249, .94);
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .04em;
        line-height: 1.2;
    }

    .fcc-chat-extreme__coach-notice {
        margin: 0 1rem .75rem;
        padding: .72rem .8rem;
        border-radius: 14px;
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, .08);
        color: rgba(241, 248, 255, .82);
        font-size: .78rem;
        line-height: 1.5;
    }

    .fcc-chat-extreme__close {
        width: 36px;
        height: 36px;
        border: 0;
        padding: 0;
        background: transparent;
        color: #ffffff;
        font-size: 1.7rem;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: .92;
    }

    .fcc-chat-extreme__close:hover,
    .fcc-chat-extreme__close:focus {
        opacity: 1;
    }

    .fcc-chat-extreme__kicker {
        padding: 0 1rem .65rem;
        color: #ffffff;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: .01em;
        flex: 0 0 auto;
    }

    .fcc-chat-extreme__shortcuts {
        padding: 0 1rem .85rem;
        display: flex;
        gap: .65rem;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        flex: 0 0 auto;
    }

    .fcc-chat-extreme__shortcuts[hidden] {
        display: none !important;
    }

    .fcc-chat-extreme__shortcuts::-webkit-scrollbar {
        display: none;
    }

    .fcc-chat-extreme__shortcut {
        min-width: 126px;
        max-width: 152px;
        flex: 0 0 132px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: .42rem;
        padding: .85rem .9rem;
        border-radius: 20px;
        border: 1px solid rgba(104, 118, 248, .34);
        background: linear-gradient(180deg, rgba(18, 18, 28, .94) 0%, rgba(12, 12, 21, .98) 100%);
        color: #f8f4ec;
        text-decoration: none;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.04), 0 12px 24px rgba(8, 8, 16, .18);
        transition: transform .16s ease, border-color .16s ease, background .16s ease;
    }

    .fcc-chat-extreme__shortcut:hover,
    .fcc-chat-extreme__shortcut:focus {
        color: #ffffff;
        text-decoration: none;
        border-color: rgba(255, 158, 44, .5);
        background: linear-gradient(180deg, rgba(28, 28, 42, .96) 0%, rgba(16, 16, 27, 1) 100%);
        transform: translateY(-1px);
    }

    .fcc-chat-extreme__shortcut-eyebrow {
        color: rgba(170, 184, 255, .84);
        font-size: .64rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        line-height: 1.2;
    }

    .fcc-chat-extreme__shortcut-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        color: #ffffff;
        font-size: .98rem;
        font-weight: 700;
        line-height: 1.18;
    }

    .fcc-chat-extreme__shortcut-title::after {
        content: '\2197';
        font-size: .8rem;
        color: rgba(255, 255, 255, .74);
        flex: 0 0 auto;
    }

    .fcc-chat-extreme__shortcut-description {
        color: rgba(233, 228, 220, .76);
        font-size: .73rem;
        line-height: 1.38;
    }

    .fcc-chat-extreme.is-coach .fcc-chat-extreme__header {
        padding: .9rem 1rem .35rem;
        align-items: center;
    }

    .fcc-chat-extreme.is-coach .fcc-chat-extreme__shortcuts {
        padding: 0 1rem .65rem;
        gap: .5rem;
    }

    .fcc-chat-extreme.is-coach .fcc-chat-extreme__shortcut {
        min-width: 106px;
        max-width: 122px;
        flex-basis: 110px;
        gap: .28rem;
        padding: .7rem .75rem;
        border-radius: 17px;
    }

    .fcc-chat-extreme.is-coach .fcc-chat-extreme__shortcut-eyebrow {
        font-size: .58rem;
        letter-spacing: .07em;
    }

    .fcc-chat-extreme.is-coach .fcc-chat-extreme__shortcut-title {
        font-size: .82rem;
        line-height: 1.14;
    }

    .fcc-chat-extreme.is-coach .fcc-chat-extreme__shortcut-title::after {
        font-size: .68rem;
    }

    .fcc-chat-extreme.is-coach .fcc-chat-extreme__shortcut-description {
        display: none;
    }

    .fcc-chat-extreme__thread {
        overflow-y: auto;
        padding: .1rem 1rem .25rem;
        display: flex;
        flex-direction: column;
        gap: .92rem;
        min-height: 0;
        flex: 1 1 auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, .16) transparent;
    }

    .fcc-chat-extreme__thread::-webkit-scrollbar {
        width: 8px;
    }

    .fcc-chat-extreme__thread::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, .16);
        border-radius: 999px;
    }

    .fcc-chat-extreme__message {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .fcc-chat-extreme__message.is-assistant {
        align-items: flex-start;
    }

    .fcc-chat-extreme__message.is-user {
        align-items: flex-end;
    }

    .fcc-chat-extreme__message.is-system {
        align-items: center;
    }

    .fcc-chat-extreme__bubble {
        max-width: 86%;
        padding: 1.05rem 1.12rem;
        border-radius: 14px;
        color: #ffffff;
        font-size: 1.01rem;
        line-height: 1.62;
        word-break: break-word;
        white-space: pre-line;
        text-align: left;
    }

    .fcc-chat-extreme__paragraph {
        margin: 0 0 1rem;
    }

    .fcc-chat-extreme__paragraph:last-child {
        margin-bottom: 0;
    }

    .fcc-chat-extreme__section {
        margin: 0 0 1rem;
    }

    .fcc-chat-extreme__section:last-child {
        margin-bottom: 0;
    }

    .fcc-chat-extreme__section-label {
        display: block;
        margin-bottom: .48rem;
        color: #88efe4;
        font-size: .74rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .fcc-chat-extreme__section-text {
        color: inherit;
        line-height: 1.6;
    }

    .fcc-chat-extreme__list {
        margin: 0;
        padding-left: 1.05rem;
        display: flex;
        flex-direction: column;
        gap: .42rem;
    }

    .fcc-chat-extreme__list li {
        margin: 0;
    }

    .fcc-chat-extreme__inline-link {
        color: #7ff3e6;
        text-decoration: underline;
        text-decoration-color: rgba(127, 243, 230, .45);
        text-underline-offset: 2px;
        transition: color .15s ease, text-decoration-color .15s ease;
    }

    .fcc-chat-extreme__inline-link:hover,
    .fcc-chat-extreme__inline-link:focus {
        color: #b6fff7;
        text-decoration-color: rgba(182, 255, 247, .7);
    }

    .fcc-chat-extreme__message.is-assistant .fcc-chat-extreme__bubble {
        background: #1f1f23;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .04);
    }

    .fcc-chat-extreme__message.is-user .fcc-chat-extreme__bubble {
        background: linear-gradient(135deg, #5b47dc, #2c7fff);
        border-bottom-right-radius: 6px;
    }

    .fcc-chat-extreme__message.is-system .fcc-chat-extreme__bubble {
        max-width: 94%;
        background: rgba(12, 12, 16, .42);
        color: #eee8dd;
        font-size: .88rem;
        padding: .65rem .8rem;
        text-align: center;
    }

    .fcc-chat-extreme__suggestions {
        max-width: 88%;
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .45rem;
    }

    .fcc-chat-extreme__message.is-assistant .fcc-chat-extreme__suggestions {
        justify-content: flex-start;
    }

    .fcc-chat-extreme__suggestion {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        max-width: 100%;
        padding: .55rem .78rem;
        border-radius: 999px;
        background: rgba(17, 17, 24, .92);
        border: 1px solid rgba(121, 148, 248, .3);
        color: #f7f3ea;
        font-size: .78rem;
        line-height: 1.2;
        text-decoration: none;
        transition: border-color .15s ease, background-color .15s ease, transform .15s ease;
    }

    .fcc-chat-extreme__suggestion:hover,
    .fcc-chat-extreme__suggestion:focus {
        color: #ffffff;
        border-color: rgba(255, 158, 44, .5);
        background: rgba(35, 35, 46, .96);
        text-decoration: none;
        transform: translateY(-1px);
    }

    .fcc-chat-extreme__suggestion::after {
        content: '\2197';
        font-size: .76rem;
        opacity: .78;
        flex: 0 0 auto;
    }

    .fcc-chat-extreme__feedback {
        max-width: 88%;
        display: inline-flex;
        align-items: flex-start;
        flex-direction: column;
        gap: .45rem;
        margin-top: .42rem;
        padding-left: .15rem;
    }

    .fcc-chat-extreme__feedback-actions {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
    }

    .fcc-chat-extreme__feedback-button {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, .14);
        background: rgba(15, 18, 26, .82);
        color: rgba(235, 240, 248, .76);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform .14s ease, border-color .14s ease, background-color .14s ease, color .14s ease;
    }

    .fcc-chat-extreme__feedback-button:hover,
    .fcc-chat-extreme__feedback-button:focus {
        transform: translateY(-1px);
        border-color: rgba(127, 243, 230, .26);
        color: #ffffff;
    }

    .fcc-chat-extreme__feedback-button.is-active-up {
        background: rgba(16, 185, 129, .18);
        border-color: rgba(110, 231, 183, .26);
        color: #d1fae5;
    }

    .fcc-chat-extreme__feedback-button.is-active-down {
        background: rgba(239, 68, 68, .16);
        border-color: rgba(252, 165, 165, .24);
        color: #fee2e2;
    }

    .fcc-chat-extreme__feedback-count {
        color: rgba(233, 228, 220, .72);
        font-size: .72rem;
        line-height: 1;
        min-width: 1rem;
    }

    .fcc-chat-extreme__feedback-menu {
        display: flex;
        flex-wrap: wrap;
        gap: .38rem;
        padding: .55rem .65rem;
        border-radius: 14px;
        background: rgba(15, 18, 26, .68);
        border: 1px solid rgba(255, 255, 255, .1);
        max-width: 280px;
    }

    .fcc-chat-extreme__feedback-menu[hidden] {
        display: none;
    }

    .fcc-chat-extreme__feedback-menu-label {
        width: 100%;
        color: rgba(233, 228, 220, .82);
        font-size: .7rem;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .fcc-chat-extreme__feedback-reason {
        border: 1px solid rgba(255, 255, 255, .1);
        background: rgba(255, 255, 255, .06);
        color: rgba(245, 247, 250, .9);
        border-radius: 999px;
        padding: .38rem .62rem;
        font-size: .72rem;
        line-height: 1.1;
        cursor: pointer;
        transition: background-color .14s ease, border-color .14s ease, transform .14s ease;
    }

    .fcc-chat-extreme__feedback-reason:hover,
    .fcc-chat-extreme__feedback-reason:focus {
        transform: translateY(-1px);
        border-color: rgba(127, 243, 230, .24);
        background: rgba(122, 162, 247, .14);
    }

    .fcc-chat-extreme__feedback-reason.is-active {
        border-color: rgba(252, 165, 165, .28);
        background: rgba(239, 68, 68, .18);
        color: #fff1f2;
    }

    .fcc-chat-extreme__typing {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        min-height: 1.1rem;
    }

    .fcc-chat-extreme__typing span {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .72);
        animation: fcc-chat-extreme-bounce 1s infinite ease-in-out;
    }

    .fcc-chat-extreme__typing span:nth-child(2) {
        animation-delay: .12s;
    }

    .fcc-chat-extreme__typing span:nth-child(3) {
        animation-delay: .24s;
    }

    @keyframes fcc-chat-extreme-bounce {
        0%, 80%, 100% {
            transform: translateY(0);
            opacity: .6;
        }

        40% {
            transform: translateY(-4px);
            opacity: 1;
        }
    }

    .fcc-chat-extreme__stream-cursor {
        display: inline-block;
        width: .58ch;
        margin-left: .08rem;
        color: rgba(255, 255, 255, .78);
        animation: fcc-chat-extreme-caret 1s step-end infinite;
    }

    @keyframes fcc-chat-extreme-caret {
        0%, 48% {
            opacity: 1;
        }

        49%, 100% {
            opacity: 0;
        }
    }

    .fcc-chat-extreme__lead {
        margin: 0 1rem .8rem;
        padding: .85rem;
        border-radius: 16px;
        background: rgba(18, 18, 24, .68);
        border: 1px solid rgba(255, 255, 255, .08);
        display: flex;
        flex-direction: column;
        gap: .7rem;
        flex: 0 0 auto;
    }

    .fcc-chat-extreme__lead[hidden] {
        display: none !important;
    }

    .fcc-chat-extreme__lead-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
    }

    .fcc-chat-extreme__lead-title {
        color: #ffffff;
        font-size: .95rem;
        font-weight: 700;
        line-height: 1.25;
    }

    .fcc-chat-extreme__lead-toggle {
        min-width: 74px;
        height: 32px;
        padding: 0 .72rem;
        flex: 0 0 auto;
        border: 1px solid rgba(125, 149, 255, .28);
        border-radius: 999px;
        background: rgba(17, 17, 24, .9);
        color: rgba(255, 255, 255, .86);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .42rem;
        cursor: pointer;
        transition: transform .15s ease, border-color .15s ease, background-color .15s ease;
    }

    .fcc-chat-extreme__lead-toggle:hover,
    .fcc-chat-extreme__lead-toggle:focus {
        border-color: rgba(127, 243, 230, .34);
        background: rgba(28, 28, 40, .96);
        transform: translateY(-1px);
    }

    .fcc-chat-extreme__lead-toggle i {
        transition: transform .18s ease;
        font-size: .72rem;
    }

    .fcc-chat-extreme__lead.is-collapsed .fcc-chat-extreme__lead-toggle i {
        transform: rotate(180deg);
    }

    .fcc-chat-extreme__lead-toggle-text {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .02em;
        line-height: 1;
    }

    .fcc-chat-extreme__lead-body {
        display: flex;
        flex-direction: column;
        gap: .7rem;
    }

    .fcc-chat-extreme__lead-body[hidden] {
        display: none !important;
    }

    .fcc-chat-extreme__lead-text {
        color: #e7e2d8;
        font-size: .86rem;
        line-height: 1.45;
    }

    .fcc-chat-extreme__lead-collapsed-hint {
        display: none;
        color: rgba(231, 226, 216, .82);
        font-size: .8rem;
        line-height: 1.42;
        padding-top: .1rem;
        cursor: pointer;
    }

    .fcc-chat-extreme__lead.is-collapsed .fcc-chat-extreme__lead-collapsed-hint {
        display: block;
    }

    .fcc-chat-extreme__lead.is-collapsed .fcc-chat-extreme__lead-header {
        cursor: pointer;
    }

    .fcc-chat-extreme__lead-form {
        display: grid;
        gap: .55rem;
    }

    .fcc-chat-extreme__lead-form input,
    .fcc-chat-extreme__lead-form textarea,
    .fcc-chat-extreme__lead-form select {
        width: 100%;
        border: 1px solid rgba(125, 149, 255, .32);
        background: #151515;
        color: #ffffff;
        border-radius: 12px;
        padding: .78rem .85rem;
        font-size: .92rem;
        outline: none;
    }

    .fcc-chat-extreme__lead-form textarea {
        min-height: 78px;
        resize: vertical;
    }

    .fcc-chat-extreme__lead-consent {
        display: flex;
        align-items: flex-start;
        gap: .55rem;
        color: #ece4d5;
        font-size: .8rem;
        line-height: 1.45;
    }

    .fcc-chat-extreme__lead-consent input {
        width: auto;
        margin-top: .15rem;
    }

    .fcc-chat-extreme__lead-submit {
        border: 0;
        border-radius: 12px;
        padding: .8rem 1rem;
        background: linear-gradient(135deg, #ff9e2c, #6e52ff);
        color: #ffffff;
        font-weight: 700;
        cursor: pointer;
    }

    .fcc-chat-extreme__composer {
        padding: .15rem .95rem .25rem;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: .65rem;
        align-items: center;
        background: linear-gradient(180deg, rgba(67, 67, 67, 0) 0%, rgba(67, 67, 67, .78) 22%, rgba(67, 67, 67, 1) 100%);
        flex: 0 0 auto;
    }

    .fcc-chat-extreme__input {
        width: 100%;
        border: 1px solid rgba(121, 148, 248, .46);
        background: #171717;
        color: #ffffff;
        border-radius: 12px;
        min-height: 52px;
        padding: 0 1rem;
        font-size: 1rem;
        outline: none;
    }

    .fcc-chat-extreme__input::placeholder,
    .fcc-chat-extreme__lead-form input::placeholder,
    .fcc-chat-extreme__lead-form textarea::placeholder {
        color: rgba(255, 255, 255, .56);
    }

    .fcc-chat-extreme__send {
        width: 52px;
        height: 52px;
        border: 0;
        border-radius: 12px;
        background: #171717;
        color: rgba(255, 255, 255, .78);
        border: 1px solid rgba(121, 148, 248, .46);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.05rem;
    }

    .fcc-chat-extreme__send[disabled],
    .fcc-chat-extreme__lead-submit[disabled] {
        opacity: .64;
        cursor: wait;
    }

    @media (max-width: 576px) {
        .fcc-chat-extreme {
            right: .75rem;
            bottom: calc(.75rem + var(--fcc-chat-widget-offset, 0px));
        }

        .fcc-chat-extreme__panel {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100dvh;
            min-height: 100dvh;
            border-radius: 0;
            border-width: 0;
            box-shadow: none;
            transform: translateY(100%);
        }

        .fcc-chat-extreme.is-open .fcc-chat-extreme__panel {
            transform: translateY(0);
        }

        .fcc-chat-extreme__header {
            padding-top: calc(.8rem + env(safe-area-inset-top));
        }

        .fcc-chat-extreme__shortcuts {
            padding-bottom: .7rem;
        }

        .fcc-chat-extreme__shortcut {
            min-width: 138px;
            flex-basis: 138px;
        }

        .fcc-chat-extreme__composer {
            padding-bottom: calc(.2rem + env(safe-area-inset-bottom));
        }
    }
</style>

<div
    id="<?= htmlspecialchars($fcc_chat_dom_id, ENT_QUOTES, 'UTF-8') ?>"
    class="fcc-chat-extreme<?= $fcc_chat_assistant_type === 'coach' ? ' is-coach' : '' ?>"
    data-fcc-chat-extreme
    data-assistant-type="<?= htmlspecialchars($fcc_chat_assistant_type, ENT_QUOTES, 'UTF-8') ?>"
    data-scope="<?= htmlspecialchars($fcc_chat_scope, ENT_QUOTES, 'UTF-8') ?>"
    data-link-id="<?= (int) $fcc_chat_link_id ?>"
    data-blog-post-id="<?= (int) $fcc_chat_blog_post_id ?>"
    data-language="<?= htmlspecialchars($fcc_chat_language_code, ENT_QUOTES, 'UTF-8') ?>"
    data-default-language="<?= htmlspecialchars($fcc_chat_language_code, ENT_QUOTES, 'UTF-8') ?>"
    data-owner-name="<?= htmlspecialchars($fcc_chat_owner_name, ENT_QUOTES, 'UTF-8') ?>"
    data-conversation-url="<?= htmlspecialchars($fcc_chat_conversation_url, ENT_QUOTES, 'UTF-8') ?>"
    data-message-url="<?= htmlspecialchars($fcc_chat_message_url, ENT_QUOTES, 'UTF-8') ?>"
    data-feedback-url="<?= htmlspecialchars($fcc_chat_feedback_url, ENT_QUOTES, 'UTF-8') ?>"
    data-lead-url="<?= htmlspecialchars($fcc_chat_lead_url, ENT_QUOTES, 'UTF-8') ?>"
    data-storage-key="<?= htmlspecialchars($fcc_chat_storage_key, ENT_QUOTES, 'UTF-8') ?>"
    data-context-storage-key="<?= htmlspecialchars($fcc_chat_context_storage_key, ENT_QUOTES, 'UTF-8') ?>"
    data-source-context="<?= htmlspecialchars($fcc_chat_source_context, ENT_QUOTES, 'UTF-8') ?>"
    data-lead-success="<?= htmlspecialchars($fcc_chat_lead_success_message, ENT_QUOTES, 'UTF-8') ?>"
    data-default-welcome="<?= htmlspecialchars($fcc_chat_default_welcome, ENT_QUOTES, 'UTF-8') ?>"
    data-assistant-title="<?= htmlspecialchars($fcc_chat_assistant_title, ENT_QUOTES, 'UTF-8') ?>"
    data-coach-badge="<?= htmlspecialchars($fcc_chat_coach_badge, ENT_QUOTES, 'UTF-8') ?>"
    data-coach-notice="<?= htmlspecialchars($fcc_chat_coach_notice, ENT_QUOTES, 'UTF-8') ?>"
    data-coach-mode-key="<?= htmlspecialchars($fcc_chat_coach_mode_key, ENT_QUOTES, 'UTF-8') ?>"
    data-ui-copy="<?= htmlspecialchars(json_encode($fcc_chat_ui_copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
    data-hide-without-context="<?= $fcc_chat_hide_without_context ? '1' : '0' ?>"
    data-lead-enabled="<?= $fcc_chat_lead_enabled ? '1' : '0' ?>"
>
    <section
        id="<?= htmlspecialchars($fcc_chat_shell_id, ENT_QUOTES, 'UTF-8') ?>"
        class="fcc-chat-extreme__panel"
        aria-hidden="true"
        aria-labelledby="<?= htmlspecialchars($fcc_chat_dom_id . '-title', ENT_QUOTES, 'UTF-8') ?>"
    >
        <header class="fcc-chat-extreme__header">
            <?php if($fcc_chat_assistant_type === 'coach'): ?>
                <div class="fcc-chat-extreme__coach-brand">
                    <div class="fcc-chat-extreme__coach-brand-tag">Forever Card Club</div>
                    <div id="<?= htmlspecialchars($fcc_chat_dom_id . '-title', ENT_QUOTES, 'UTF-8') ?>" class="fcc-chat-extreme__coach-brand-title" data-chat-extreme-assistant-title><?= htmlspecialchars($fcc_chat_assistant_title, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if($fcc_chat_coach_badge !== ''): ?>
                        <div class="fcc-chat-extreme__coach-badge" data-chat-extreme-coach-badge><?= htmlspecialchars($fcc_chat_coach_badge, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif ?>
                </div>
            <?php else: ?>
                <img
                    src="<?= htmlspecialchars($fcc_chat_logo_url, ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($fcc_chat_logo_alt, ENT_QUOTES, 'UTF-8') ?>"
                    class="fcc-chat-extreme__brand"
                    loading="eager"
                    decoding="async"
                />
            <?php endif ?>

            <button
                type="button"
                class="fcc-chat-extreme__close"
                data-chat-extreme-close
                aria-label="<?= htmlspecialchars($fcc_chat_close_label, ENT_QUOTES, 'UTF-8') ?>"
                onclick="return window.fccChatExtremeToggle ? window.fccChatExtremeToggle(<?= htmlspecialchars(json_encode($fcc_chat_dom_id), ENT_QUOTES, 'UTF-8') ?>, false) : false;"
            >
                <span aria-hidden="true">&times;</span>
            </button>
        </header>

        <?php if($fcc_chat_assistant_type !== 'coach'): ?>
            <div id="<?= htmlspecialchars($fcc_chat_dom_id . '-title', ENT_QUOTES, 'UTF-8') ?>" class="fcc-chat-extreme__kicker">
                <?= htmlspecialchars($fcc_chat_intro_label, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif ?>

        <section class="fcc-chat-extreme__shortcuts" data-chat-extreme-shortcuts hidden></section>

        <div id="<?= htmlspecialchars($fcc_chat_thread_id, ENT_QUOTES, 'UTF-8') ?>" class="fcc-chat-extreme__thread" data-chat-extreme-thread></div>

        <?php if($fcc_chat_lead_enabled): ?>
            <section id="<?= htmlspecialchars($fcc_chat_lead_id, ENT_QUOTES, 'UTF-8') ?>" class="fcc-chat-extreme__lead" data-chat-extreme-lead hidden>
                <div class="fcc-chat-extreme__lead-header">
                    <div class="fcc-chat-extreme__lead-title" data-chat-extreme-lead-title><?= htmlspecialchars($fcc_chat_lead_title, ENT_QUOTES, 'UTF-8') ?></div>
                    <button
                        type="button"
                        class="fcc-chat-extreme__lead-toggle"
                        data-chat-extreme-lead-toggle
                        aria-expanded="true"
                        aria-label="<?= htmlspecialchars($fcc_chat_lead_toggle_close_label, ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <span class="fcc-chat-extreme__lead-toggle-text" data-chat-extreme-lead-toggle-text><?= htmlspecialchars($fcc_chat_lead_toggle_close_text, ENT_QUOTES, 'UTF-8') ?></span>
                        <i class="fas fa-chevron-up" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="fcc-chat-extreme__lead-collapsed-hint" data-chat-extreme-lead-collapsed-hint><?= htmlspecialchars($fcc_chat_lead_collapsed_hint, ENT_QUOTES, 'UTF-8') ?></div>

                <div class="fcc-chat-extreme__lead-body" data-chat-extreme-lead-body>
                    <div class="fcc-chat-extreme__lead-text" data-chat-extreme-lead-text><?= htmlspecialchars($fcc_chat_lead_text, ENT_QUOTES, 'UTF-8') ?></div>

                    <form class="fcc-chat-extreme__lead-form" data-chat-extreme-lead-form>
                        <input type="hidden" name="lead_type" value="product_interest" />
                        <input type="text" name="name" maxlength="128" placeholder="<?= htmlspecialchars($fcc_chat_lead_name_placeholder, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="email" name="email" maxlength="320" placeholder="<?= htmlspecialchars($fcc_chat_lead_email_placeholder, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="text" name="phone" maxlength="32" placeholder="<?= htmlspecialchars($fcc_chat_lead_phone_placeholder, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="consent_contact" value="1" />

                        <select name="preferred_contact_channel">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="phone"><?= htmlspecialchars($fcc_chat_phone_label, ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="email">Email</option>
                            <option value="viber">Viber</option>
                        </select>

                        <textarea name="message" maxlength="1000" placeholder="<?= htmlspecialchars($fcc_chat_lead_message_placeholder, ENT_QUOTES, 'UTF-8') ?>"></textarea>

                        <button type="submit" class="fcc-chat-extreme__lead-submit" data-is-ajax="true"><?= htmlspecialchars($fcc_chat_lead_submit_label, ENT_QUOTES, 'UTF-8') ?></button>
                    </form>
                </div>
            </section>
        <?php endif ?>

        <form id="<?= htmlspecialchars($fcc_chat_form_id, ENT_QUOTES, 'UTF-8') ?>" class="fcc-chat-extreme__composer" data-chat-extreme-form>
            <input
                type="text"
                name="message"
                class="fcc-chat-extreme__input"
                maxlength="<?= (int) $fcc_chat_message_maxlength ?>"
                placeholder="<?= htmlspecialchars($fcc_chat_input_placeholder, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="off"
            />

            <button type="submit" class="fcc-chat-extreme__send" data-is-ajax="true" aria-label="<?= htmlspecialchars($fcc_chat_send_label, ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-paper-plane" aria-hidden="true"></i>
            </button>
        </form>
    </section>

    <div
        class="fcc-chat-extreme__launcher-stack<?= $fcc_chat_assistant_type === 'coach' ? ' is-coach' : '' ?>"
        data-chat-extreme-launcher-stack
    >
        <button
            type="button"
            id="<?= htmlspecialchars($fcc_chat_toggle_id, ENT_QUOTES, 'UTF-8') ?>"
            class="fcc-chat-extreme__launcher"
            aria-controls="<?= htmlspecialchars($fcc_chat_shell_id, ENT_QUOTES, 'UTF-8') ?>"
            aria-expanded="false"
            aria-label="<?= htmlspecialchars($fcc_chat_toggle_label, ENT_QUOTES, 'UTF-8') ?>"
            onclick="return window.fccChatExtremeToggle ? window.fccChatExtremeToggle(<?= htmlspecialchars(json_encode($fcc_chat_dom_id), ENT_QUOTES, 'UTF-8') ?>, true) : false;"
        >
            <img
                src="<?= htmlspecialchars($fcc_chat_launcher_url, ENT_QUOTES, 'UTF-8') ?>"
                alt=""
                aria-hidden="true"
                loading="eager"
                decoding="async"
                onerror="this.onerror=null;this.src='<?= htmlspecialchars($fcc_chat_launcher_fallback_url, ENT_QUOTES, 'UTF-8') ?>';"
            />
        </button>

        <?php if($fcc_chat_launcher_label !== ''): ?>
            <div class="fcc-chat-extreme__launcher-label"><?= htmlspecialchars($fcc_chat_launcher_label, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif ?>
    </div>
</div>

<script>
    window.fccChatExtremeToggle = window.fccChatExtremeToggle || function(rootId, forceOpen) {
        const root = document.getElementById(rootId);

        if(!root) {
            return false;
        }

        if(typeof root.__fccChatSetOpen === 'function') {
            root.__fccChatSetOpen(forceOpen);
            return false;
        }

        const panel = root.querySelector('.fcc-chat-extreme__panel');
        const launcher = root.querySelector('.fcc-chat-extreme__launcher');
        const nextState = typeof forceOpen === 'boolean' ? forceOpen : !root.classList.contains('is-open');

        root.classList.toggle('is-open', nextState);

        if(panel) {
            panel.setAttribute('aria-hidden', nextState ? 'false' : 'true');
        }

        if(launcher) {
            launcher.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        }

        return false;
    };
</script>

<script>
    (() => {
        const root = document.getElementById(<?= json_encode($fcc_chat_dom_id) ?>);

        if(!root) {
            return;
        }

        const emergencyInit = () => {
            if(root.dataset.fccEmergencyBooted === '1') {
                return;
            }

            root.dataset.fccEmergencyBooted = '1';

            const panel = document.getElementById(<?= json_encode($fcc_chat_shell_id) ?>);
            const launcher = document.getElementById(<?= json_encode($fcc_chat_toggle_id) ?>);
            const thread = document.getElementById(<?= json_encode($fcc_chat_thread_id) ?>);
            const composerForm = document.getElementById(<?= json_encode($fcc_chat_form_id) ?>);
            const composerInput = composerForm ? composerForm.querySelector('input[name="message"]') : null;
            const sendButton = composerForm ? composerForm.querySelector('button[type="submit"]') : null;
            const sendButtonDefaultHtml = sendButton ? sendButton.innerHTML : '';
            const closeButton = root.querySelector('[data-chat-extreme-close]');
            const leadCard = document.getElementById(<?= json_encode($fcc_chat_lead_id) ?>);

            if(!panel || !launcher || !thread || !composerForm || !composerInput) {
                return;
            }

            if(leadCard) {
                leadCard.hidden = true;
            }

            const assistantType = root.dataset.assistantType || 'product_advisor';
            const scope = root.dataset.scope || 'public_app';
            const linkId = Number(root.dataset.linkId || 0);
            const blogPostId = Number(root.dataset.blogPostId || 0);
            const ownerName = root.dataset.ownerName || '';
            const conversationUrl = root.dataset.conversationUrl || '';
            const messageUrl = root.dataset.messageUrl || '';
            const storageKeyBase = root.dataset.storageKey || 'fcc_ai_public_conversation_id';
            const contextStorageKeyBase = root.dataset.contextStorageKey || 'fcc_ai_public_context';
            const sourceContext = root.dataset.sourceContext || 'FCC app popup chat';
            const defaultWelcome = root.dataset.defaultWelcome || '';
            const language = String(root.dataset.language || root.dataset.defaultLanguage || 'hr').trim().toLowerCase();
            const visitorStorageKey = `fcc_ai_public_visitor_key:${assistantType}`;
            const getConversationStorageKey = () => `${storageKeyBase}:${assistantType}:${linkId || 'shared'}`;
            const getContextStorageKey = () => `${contextStorageKeyBase}:${assistantType}`;

            let conversationPublicId = '';
            let visitorKey = '';
            let isSending = false;

            const escapeHtml = value => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const isSameOriginNavigationTarget = href => {
                try {
                    const parsed = new URL(String(href || ''), window.location.origin);
                    return parsed.origin === window.location.origin;
                } catch(error) {
                    return false;
                }
            };

            const buildInlineLinkHtml = rawUrl => {
                let url = String(rawUrl || '');
                let trailing = '';

                while(url && /[),.;!?]$/.test(url)) {
                    trailing = `${url.slice(-1)}${trailing}`;
                    url = url.slice(0, -1);
                }

                if(!url) {
                    return escapeHtml(rawUrl || '');
                }

                const normalizedHref = /^https?:\/\//i.test(url) ? url : `https://${url}`;
                const sameOrigin = isSameOriginNavigationTarget(normalizedHref);
                const target = sameOrigin ? '_self' : '_blank';
                const rel = sameOrigin ? 'noopener' : 'noopener noreferrer';

                return `<a class="fcc-chat-extreme__inline-link" href="${escapeHtml(normalizedHref)}" target="${target}" rel="${rel}">${escapeHtml(url)}</a>${escapeHtml(trailing)}`;
            };

            const formatDenseProductText = rawValue => {
                let text = String(rawValue || '').replace(/\r\n/g, '\n');

                if(text.includes('\n- ') || text.includes('\n• ')) {
                    return text;
                }

                const foreverMatches = text.match(/\bForever\s+[A-Z0-9]/g) || [];

                if(foreverMatches.length < 2) {
                    return text;
                }

                if(!/(preporu|smjer|support|rutina|routine|najčeš|najces|glavni|dodatn|daily|korak)/i.test(text)) {
                    return text;
                }

                const replacements = [
                    [/(najčešće se gleda)\s+(Forever\s+)/giu, '$1:\n- $2'],
                    [/(najcesce se gleda)\s+(Forever\s+)/giu, '$1:\n- $2'],
                    [/(glavni(?:\s+Forever)?\s+smjer(?:\s+ovdje)?\s+(?:je|ide))\s+(Forever\s+)/giu, '$1:\n- $2'],
                    [/(kao glavni(?:\s+Forever)?\s+smjer(?:\s+ovdje)?\s+(?:je|ide))\s+(Forever\s+)/giu, '$1:\n- $2'],
                    [/(kao osnovni(?:\s+Forever)?\s+smjer(?:\s+ovdje)?\s+(?:je|ide))\s+(Forever\s+)/giu, '$1:\n- $2'],
                    [/(kao glavna preporuka(?:\s+ovdje)?\s+(?:je|ide))\s+(Forever\s+)/giu, '$1:\n- $2'],
                    [/,\s+uz\s+(Forever\s+)/giu, '\n- $1'],
                    [/\s+uz\s+(Forever\s+)/giu, '\n- $1'],
                    [/,\s+a po potrebi i\s+(Forever\s+)/giu, '\n- $1'],
                    [/\s+a po potrebi i\s+(Forever\s+)/giu, '\n- $1'],
                    [/,\s+te\s+(Forever\s+)/giu, '\n- $1'],
                    [/\s+te\s+(Forever\s+)/giu, '\n- $1'],
                    [/,\s+i\s+(Forever\s+)/giu, '\n- $1'],
                    [/\s+i\s+(Forever\s+)/giu, '\n- $1'],
                ];

                replacements.forEach(([pattern, replacement]) => {
                    text = text.replace(pattern, replacement);
                });

                return text.replace(/\n{3,}/g, '\n\n');
            };

            const renderInlineMessageHtml = value => {
                let html = escapeHtml(value);
                html = html.replace(/\*\*([\s\S]+?)\*\*/g, '<strong>$1</strong>');
                html = html.replace(/((?:https?:\/\/|www\.)[^\s<]+)/gi, match => buildInlineLinkHtml(match));

                return html;
            };

            const renderMessageBodyHtml = body => {
                const lines = String(body || '').split('\n').map(line => line.trim()).filter(Boolean);

                if(lines.length && lines.every(line => /^[-•]\s+/.test(line))) {
                    return `<ul class="fcc-chat-extreme__list">${lines.map(line => `<li>${renderInlineMessageHtml(line.replace(/^[-•]\s+/, ''))}</li>`).join('')}</ul>`;
                }

                return `<div class="fcc-chat-extreme__section-text">${renderInlineMessageHtml(String(body || '')).replace(/\n/g, '<br>')}</div>`;
            };

            const renderStructuredMessageHtml = value => {
                const blocks = String(value || '').split(/\n{2,}/).map(block => block.trim()).filter(Boolean);

                if(!blocks.length) {
                    return '';
                }

                return blocks.map(block => {
                    const sectionMatch = block.match(/^([^\n:]{2,48}):\n([\s\S]+)$/);

                    if(sectionMatch) {
                        return `<div class="fcc-chat-extreme__section"><div class="fcc-chat-extreme__section-label">${renderInlineMessageHtml(sectionMatch[1].trim())}</div>${renderMessageBodyHtml(sectionMatch[2].trim())}</div>`;
                    }

                    return `<p class="fcc-chat-extreme__paragraph">${renderInlineMessageHtml(block).replace(/\n/g, '<br>')}</p>`;
                }).join('');
            };

            const renderMessageHtml = value => renderStructuredMessageHtml(formatDenseProductText(value));

            const appendMessage = (role, content) => {
                const wrapper = document.createElement('div');
                wrapper.className = `fcc-chat-extreme__message is-${role === 'user' ? 'user' : role === 'system' ? 'system' : 'assistant'}`;

                const bubble = document.createElement('div');
                bubble.className = 'fcc-chat-extreme__bubble';
                bubble.innerHTML = renderMessageHtml(content);
                wrapper.appendChild(bubble);
                thread.appendChild(wrapper);
                thread.scrollTop = thread.scrollHeight;
            };

            const resetSubmitButton = (button, defaultHtml = '') => {
                if(!button) {
                    return;
                }

                button.disabled = false;
                button.classList.remove('disabled', 'container-disabled-simple');
                button.removeAttribute('data-inner-text');

                if(defaultHtml !== '') {
                    button.innerHTML = defaultHtml;
                }
            };

            const createToken = () => {
                if(window.crypto && typeof window.crypto.randomUUID === 'function') {
                    return window.crypto.randomUUID().replace(/-/g, '');
                }

                return `fcc${Date.now().toString(16)}${Math.random().toString(16).slice(2, 10)}`;
            };

            const currentPagePayload = () => {
                const searchParams = new URLSearchParams(window.location.search);
                const hashValue = window.location.hash ? window.location.hash.replace(/^#/, '') : '';

                return {
                    source_page_url: window.location.href,
                    source_page_slug: window.location.pathname.replace(/^\/+|\/+$/g, ''),
                    source_page_title: (document.title || '').trim(),
                    source_page_section: (searchParams.get('section') || hashValue || '').trim(),
                };
            };

            const persistConversation = details => {
                if(!details || !details.conversation_public_id) {
                    return;
                }

                conversationPublicId = String(details.conversation_public_id);

                try {
                    window.localStorage.setItem(getConversationStorageKey(), conversationPublicId);
                    window.localStorage.setItem(getContextStorageKey(), JSON.stringify({
                        conversation_public_id: conversationPublicId,
                        link_id: linkId,
                        owner_name: ownerName,
                        assistant_type: assistantType,
                        saved_at: Date.now(),
                    }));
                } catch(error) {
                }
            };

            const postForm = async (url, payload) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams(payload).toString(),
                });

                const result = await response.json();

                if(!response.ok || result.status !== 'success') {
                    throw new Error(Array.isArray(result.message) ? result.message.join(' ') : (result.message || 'Greška u komunikaciji.'));
                }

                return result.details || {};
            };

            const ensureConversation = async () => {
                if(!conversationUrl) {
                    return {};
                }

                if(conversationPublicId) {
                    return {conversation_public_id: conversationPublicId};
                }

                const details = await postForm(conversationUrl, {
                    assistant_type: assistantType,
                    scope,
                    conversation_public_id: conversationPublicId,
                    link_id: linkId,
                    blog_post_id: blogPostId,
                    language,
                    source_context: sourceContext,
                    visitor_key: visitorKey,
                    ...currentPagePayload(),
                });

                persistConversation(details);
                return details;
            };

            const setOpen = isOpen => {
                root.classList.toggle('is-open', !!isOpen);
                panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
                launcher.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

                if(isOpen) {
                    window.setTimeout(() => composerInput.focus(), 60);
                }
            };

            root.__fccChatSetOpen = setOpen;

            try {
                conversationPublicId = window.localStorage.getItem(getConversationStorageKey()) || '';
                visitorKey = window.localStorage.getItem(visitorStorageKey) || '';
            } catch(error) {
                conversationPublicId = '';
                visitorKey = '';
            }

            if(!visitorKey) {
                visitorKey = createToken();

                try {
                    window.localStorage.setItem(visitorStorageKey, visitorKey);
                } catch(error) {
                }
            }

            if(!thread.textContent.trim() && defaultWelcome) {
                appendMessage('assistant', defaultWelcome);
            }

            launcher.addEventListener('click', () => setOpen(true));

            if(closeButton) {
                closeButton.addEventListener('click', () => setOpen(false));
            }

            composerForm.addEventListener('submit', async event => {
                event.preventDefault();

                const message = composerInput.value.trim();

                if(!message || isSending || !messageUrl) {
                    return;
                }

                composerInput.value = '';
                appendMessage('user', message);
                isSending = true;

                if(sendButton) {
                    sendButton.disabled = true;
                }

                try {
                    await ensureConversation();

                    const details = await postForm(messageUrl, {
                        assistant_type: assistantType,
                        scope,
                        conversation_public_id: conversationPublicId,
                        link_id: linkId,
                        blog_post_id: blogPostId,
                        language,
                        source_context: sourceContext,
                        visitor_key: visitorKey,
                        message,
                        ...currentPagePayload(),
                    });

                    persistConversation(details);

                    const replyContent = String(
                        (details.reply && details.reply.content)
                        || (Array.isArray(details.messages) ? ((details.messages.slice().reverse().find(item => item && item.role === 'assistant' && (item.message_type || 'chat') === 'chat') || {}).content || '') : '')
                        || ''
                    ).trim();

                    if(replyContent) {
                        appendMessage('assistant', replyContent);
                    }
                } catch(error) {
                    appendMessage('system', error.message || 'Chat trenutno nije dostupan.');
                } finally {
                    isSending = false;

                    if(sendButton) {
                        resetSubmitButton(sendButton, sendButtonDefaultHtml);
                    }
                }
            });
        };

        try {

        const panel = document.getElementById(<?= json_encode($fcc_chat_shell_id) ?>);
        const launcher = document.getElementById(<?= json_encode($fcc_chat_toggle_id) ?>);
        const thread = document.getElementById(<?= json_encode($fcc_chat_thread_id) ?>);
        const composerForm = document.getElementById(<?= json_encode($fcc_chat_form_id) ?>);
        const composerInput = composerForm ? composerForm.querySelector('input[name="message"]') : null;
        const sendButton = composerForm ? composerForm.querySelector('button[type="submit"]') : null;
        const sendButtonDefaultHtml = sendButton ? sendButton.innerHTML : '';
        const closeButton = root.querySelector('[data-chat-extreme-close]');
        const shortcutsRail = root.querySelector('[data-chat-extreme-shortcuts]');
        const leadCard = document.getElementById(<?= json_encode($fcc_chat_lead_id) ?>);
        const leadTitle = leadCard ? leadCard.querySelector('[data-chat-extreme-lead-title]') : null;
        const leadText = leadCard ? leadCard.querySelector('[data-chat-extreme-lead-text]') : null;
        const leadCollapsedHint = leadCard ? leadCard.querySelector('[data-chat-extreme-lead-collapsed-hint]') : null;
        const leadBody = leadCard ? leadCard.querySelector('[data-chat-extreme-lead-body]') : null;
        const leadToggle = leadCard ? leadCard.querySelector('[data-chat-extreme-lead-toggle]') : null;
        const leadToggleText = leadCard ? leadCard.querySelector('[data-chat-extreme-lead-toggle-text]') : null;
        const leadForm = leadCard ? leadCard.querySelector('[data-chat-extreme-lead-form]') : null;
        const leadSubmitButton = leadForm ? leadForm.querySelector('button[type="submit"]') : null;
        const leadSubmitButtonDefaultHtml = leadSubmitButton ? leadSubmitButton.innerHTML : '';
        const mobileMedia = window.matchMedia('(max-width: 576px)');

        if(!panel || !launcher || !thread || !composerForm || !composerInput) {
            return;
        }

        if(typeof window.fccChatExtremeSyncLaunchers !== 'function') {
            window.fccChatExtremeSyncLaunchers = () => {
                const widgets = Array.from(document.querySelectorAll('[data-fcc-chat-extreme]'))
                    .filter(node => node instanceof HTMLElement && node.style.display !== 'none');
                const hasOpenWidget = widgets.some(node => node.classList.contains('is-open'));
                const getPriority = node => ((node.dataset.assistantType || '') === 'coach' ? 10 : 0);
                const compareDomOrder = (a, b) => {
                    if(a === b) {
                        return 0;
                    }

                    const position = a.compareDocumentPosition(b);

                    if(position & Node.DOCUMENT_POSITION_FOLLOWING) {
                        return -1;
                    }

                    if(position & Node.DOCUMENT_POSITION_PRECEDING) {
                        return 1;
                    }

                    return 0;
                };

                widgets.forEach(node => {
                    const stack = node.querySelector('[data-chat-extreme-launcher-stack]');

                    node.classList.toggle('is-backgrounded', hasOpenWidget && !node.classList.contains('is-open'));

                    if(stack instanceof HTMLElement) {
                        node.style.setProperty('--fcc-chat-widget-offset', '0px');
                    }
                });

                const closedWidgets = widgets
                    .filter(node => !node.classList.contains('is-open'))
                    .sort((a, b) => {
                        const priorityDifference = getPriority(a) - getPriority(b);

                        if(priorityDifference !== 0) {
                            return priorityDifference;
                        }

                        return compareDomOrder(a, b);
                    });

                let currentOffset = 0;

                closedWidgets.forEach(node => {
                    const stack = node.querySelector('[data-chat-extreme-launcher-stack]');

                    if(!(stack instanceof HTMLElement)) {
                        return;
                    }

                    node.style.setProperty('--fcc-chat-widget-offset', `${currentOffset}px`);
                    currentOffset += stack.offsetHeight + 12;
                });
            };

            window.addEventListener('resize', () => {
                window.requestAnimationFrame(() => window.fccChatExtremeSyncLaunchers());
            });
        }

        const syncLauncherStacking = () => {
            if(typeof window.fccChatExtremeSyncLaunchers === 'function') {
                window.requestAnimationFrame(() => window.fccChatExtremeSyncLaunchers());
            }
        };

        const normalizePublicLanguageCode = value => {
            const normalized = String(value || '').trim().toLowerCase().replace(/[^a-z_-]/g, '');

            if(normalized.startsWith('sl')) {
                return 'sl';
            }

            if(normalized.startsWith('bg')) {
                return 'bg';
            }

            if(normalized.startsWith('en')) {
                return 'en';
            }

            if(['hr', 'bs', 'sr'].some(prefix => normalized.startsWith(prefix))) {
                return 'hr';
            }

            return 'hr';
        };

        const resolveLocaleMappedLanguage = locale => {
            const rawLocale = String(locale || '').trim();

            if(!rawLocale) {
                return '';
            }

            const normalizedLocale = rawLocale.replace('_', '-');
            const segments = normalizedLocale.split('-').filter(Boolean);
            const primary = (segments[0] || '').toLowerCase();
            const region = segments.length > 1 ? segments[segments.length - 1].toUpperCase() : '';

            if(['HR', 'BA', 'RS', 'ME'].includes(region)) {
                return 'hr';
            }

            if(region === 'SI') {
                return 'sl';
            }

            if(region === 'BG') {
                return 'bg';
            }

            if(primary === 'sl') {
                return 'sl';
            }

            if(primary === 'bg') {
                return 'bg';
            }

            if(['hr', 'bs', 'sr'].includes(primary)) {
                return 'hr';
            }

            return '';
        };

        const resolveInitialPublicLanguage = fallbackLanguage => {
            const fallback = normalizePublicLanguageCode(fallbackLanguage || 'hr');
            const locales = Array.isArray(window.navigator.languages) && window.navigator.languages.length
                ? window.navigator.languages
                : [window.navigator.language || ''];

            for(const locale of locales) {
                const mappedLanguage = resolveLocaleMappedLanguage(locale);

                if(mappedLanguage) {
                    return mappedLanguage;
                }
            }

            return fallback;
        };

        const resetSubmitButton = (button, defaultHtml = '') => {
            if(!button) {
                return;
            }

            button.disabled = false;
            button.classList.remove('disabled', 'container-disabled-simple');
            button.removeAttribute('data-inner-text');

            if(defaultHtml !== '') {
                button.innerHTML = defaultHtml;
            }
        };

        let uiCopy = {};

        try {
            uiCopy = JSON.parse(root.dataset.uiCopy || '{}') || {};
        } catch(error) {
            uiCopy = {};
        }

        const defaultLanguage = normalizePublicLanguageCode(root.dataset.defaultLanguage || root.dataset.language || 'hr');
        const resolvedInitialLanguage = (root.dataset.assistantType || 'product_advisor') === 'coach'
            ? defaultLanguage
            : resolveInitialPublicLanguage(defaultLanguage);

        const config = {
            assistantType: root.dataset.assistantType || 'product_advisor',
            scope: root.dataset.scope || 'public_app',
            linkId: Number(root.dataset.linkId || 0),
            blogPostId: Number(root.dataset.blogPostId || 0),
            defaultLanguage,
            language: resolvedInitialLanguage,
            ownerName: root.dataset.ownerName || '',
            conversationUrl: root.dataset.conversationUrl || '',
            messageUrl: root.dataset.messageUrl || '',
            feedbackUrl: root.dataset.feedbackUrl || '',
            leadUrl: root.dataset.leadUrl || '',
            storageKeyBase: root.dataset.storageKey || 'fcc_ai_public_conversation_id',
            contextStorageKeyBase: root.dataset.contextStorageKey || 'fcc_ai_public_context',
            sourceContext: root.dataset.sourceContext || 'FCC app popup chat',
            leadSuccessMessage: root.dataset.leadSuccess || 'Kontakt je spremljen.',
            defaultWelcome: root.dataset.defaultWelcome || '',
            assistantTitle: root.dataset.assistantTitle || '',
            coachBadge: root.dataset.coachBadge || '',
            coachNotice: root.dataset.coachNotice || '',
            coachModeKey: root.dataset.coachModeKey || '',
            hideWithoutContext: root.dataset.hideWithoutContext === '1',
            leadEnabled: root.dataset.leadEnabled === '1',
            feedbackSavedMessage: <?= json_encode($fcc_chat_feedback_saved_message) ?>,
            feedbackNotePrompt: <?= json_encode($fcc_chat_feedback_note_prompt) ?>,
            feedbackPositiveLabel: <?= json_encode($fcc_chat_feedback_positive_label) ?>,
            feedbackNegativeLabel: <?= json_encode($fcc_chat_feedback_negative_label) ?>,
            feedbackReasonTitle: <?= json_encode($fcc_chat_feedback_reason_title) ?>,
            feedbackReasonOptions: <?= json_encode($fcc_chat_feedback_reason_options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            leadToggleOpenText: <?= json_encode($fcc_chat_lead_toggle_open_text) ?>,
            leadToggleCloseText: <?= json_encode($fcc_chat_lead_toggle_close_text) ?>,
            leadToggleOpenLabel: <?= json_encode($fcc_chat_lead_toggle_open_label) ?>,
            leadToggleCloseLabel: <?= json_encode($fcc_chat_lead_toggle_close_label) ?>,
            leadCollapsedHint: <?= json_encode($fcc_chat_lead_collapsed_hint) ?>,
            preTypingPause: 950,
            minTypingDelay: 1050,
        };

        const state = {
            bootPromise: null,
            conversationPublicId: '',
            visitorKey: '',
            isOpen: false,
            isSending: false,
            isLeadSubmitting: false,
            leadCaptured: false,
            leadCardExpanded: false,
            pendingLeadCapture: null,
            scrollY: 0,
            linkId: Number(config.linkId || 0),
            ownerName: config.ownerName || '',
            feedbackLoadingIds: new Set(),
            renderedMessages: [],
        };

        const kicker = root.querySelector('.fcc-chat-extreme__kicker');
        const assistantTitle = root.querySelector('[data-chat-extreme-assistant-title]');
        const coachBadge = root.querySelector('[data-chat-extreme-coach-badge]');
        const coachNotice = root.querySelector('[data-chat-extreme-coach-notice]');
        const launcherLabel = root.querySelector('.fcc-chat-extreme__launcher-label');

        const getUiCopy = language => {
            const resolvedLanguage = normalizePublicLanguageCode(language || config.language || config.defaultLanguage || 'hr');
            return uiCopy[resolvedLanguage] || uiCopy[config.defaultLanguage] || uiCopy.hr || {};
        };

        const applyCoachModeDetails = mode => {
            if(!mode || typeof mode !== 'object' || config.assistantType !== 'coach') {
                return;
            }

            if(typeof mode.mode_key === 'string' && mode.mode_key) {
                config.coachModeKey = mode.mode_key;
                root.dataset.coachModeKey = mode.mode_key;
            }

            if(typeof mode.assistant_title === 'string' && mode.assistant_title) {
                config.assistantTitle = mode.assistant_title;
                root.dataset.assistantTitle = mode.assistant_title;
            }

            if(typeof mode.coach_badge === 'string') {
                config.coachBadge = mode.coach_badge;
                root.dataset.coachBadge = mode.coach_badge;
            }

            if(typeof mode.coach_notice === 'string') {
                config.coachNotice = mode.coach_notice;
                root.dataset.coachNotice = mode.coach_notice;
            }

            const activeLanguage = normalizePublicLanguageCode(config.language || config.defaultLanguage || 'hr');
            const activeCopy = {...(uiCopy[activeLanguage] || {})};

            if(config.assistantTitle) {
                activeCopy.assistantTitle = config.assistantTitle;
                activeCopy.launcherLabel = mode.launcher_label || activeCopy.launcherLabel || config.assistantTitle;
                activeCopy.introLabel = mode.intro_label || activeCopy.introLabel || config.assistantTitle;
            }

            if(typeof config.coachBadge === 'string') {
                activeCopy.coachBadge = config.coachBadge;
            }

            if(typeof config.coachNotice === 'string') {
                activeCopy.coachNotice = config.coachNotice;
            }

            if(Object.keys(activeCopy).length) {
                uiCopy[activeLanguage] = activeCopy;
            }
        };

        const applyLocalizedCopy = language => {
            const copy = getUiCopy(language);

            if(Object.keys(copy).length === 0) {
                return;
            }

            config.language = normalizePublicLanguageCode(language || config.language);
            config.defaultWelcome = copy.defaultWelcome || config.defaultWelcome;
            config.leadSuccessMessage = copy.leadSuccessMessage || config.leadSuccessMessage;
            config.feedbackSavedMessage = copy.feedbackSavedMessage || config.feedbackSavedMessage;
            config.feedbackNotePrompt = copy.feedbackNotePrompt || config.feedbackNotePrompt;
            config.feedbackPositiveLabel = copy.feedbackPositiveLabel || config.feedbackPositiveLabel;
            config.feedbackNegativeLabel = copy.feedbackNegativeLabel || config.feedbackNegativeLabel;
            config.feedbackReasonTitle = copy.feedbackReasonTitle || config.feedbackReasonTitle;

            if(copy.feedbackReasonOptions && typeof copy.feedbackReasonOptions === 'object') {
                config.feedbackReasonOptions = copy.feedbackReasonOptions;
            }

            if(kicker && copy.introLabel) {
                kicker.textContent = copy.introLabel;
            }

            if(assistantTitle && copy.assistantTitle) {
                assistantTitle.textContent = copy.assistantTitle;
            }

            if(launcherLabel && copy.launcherLabel) {
                launcherLabel.textContent = copy.launcherLabel;
            }

            if(coachBadge && typeof copy.coachBadge === 'string') {
                coachBadge.textContent = copy.coachBadge;
                coachBadge.hidden = copy.coachBadge === '';
            }

            if(coachNotice && typeof copy.coachNotice === 'string') {
                coachNotice.textContent = copy.coachNotice;
                coachNotice.hidden = copy.coachNotice === '';
            }

            if(composerInput && copy.inputPlaceholder) {
                composerInput.placeholder = copy.inputPlaceholder;
            }

            if(leadTitle && copy.leadTitle) {
                leadTitle.textContent = copy.leadTitle;
            }

            if(leadText && copy.leadText) {
                leadText.textContent = copy.leadText;
            }

            if(leadForm) {
                const nameInput = leadForm.querySelector('input[name="name"]');
                const phoneInput = leadForm.querySelector('input[name="phone"]');
                const messageInput = leadForm.querySelector('textarea[name="message"]');
                const submitButton = leadForm.querySelector('button[type="submit"]');
                const channelSelect = leadForm.querySelector('select[name="preferred_contact_channel"]');

                if(nameInput && copy.leadNamePlaceholder) {
                    nameInput.placeholder = copy.leadNamePlaceholder;
                }

                if(phoneInput && copy.leadPhonePlaceholder) {
                    phoneInput.placeholder = copy.leadPhonePlaceholder;
                }

                if(messageInput && copy.leadMessagePlaceholder) {
                    messageInput.placeholder = copy.leadMessagePlaceholder;
                }

                if(submitButton && copy.leadSubmitLabel) {
                    submitButton.textContent = copy.leadSubmitLabel;
                }

                if(copy.leadToggleOpenText) {
                    config.leadToggleOpenText = copy.leadToggleOpenText;
                }

                if(copy.leadToggleCloseText) {
                    config.leadToggleCloseText = copy.leadToggleCloseText;
                }

                if(copy.leadToggleOpenLabel) {
                    config.leadToggleOpenLabel = copy.leadToggleOpenLabel;
                }

                if(copy.leadToggleCloseLabel) {
                    config.leadToggleCloseLabel = copy.leadToggleCloseLabel;
                }

                if(copy.leadCollapsedHint) {
                    config.leadCollapsedHint = copy.leadCollapsedHint;
                }

                if(channelSelect) {
                    const phoneOption = channelSelect.querySelector('option[value="phone"]');

                    if(phoneOption && copy.phoneLabel) {
                        phoneOption.textContent = copy.phoneLabel;
                    }
                }
            }
        };

        const setLeadExpanded = isExpanded => {
            state.leadCardExpanded = !!isExpanded;

            if(!leadCard || !leadBody || !leadToggle) {
                return;
            }

            leadCard.classList.toggle('is-collapsed', !state.leadCardExpanded);
            leadBody.hidden = !state.leadCardExpanded;
            leadToggle.setAttribute('aria-expanded', state.leadCardExpanded ? 'true' : 'false');
            leadToggle.setAttribute('aria-label', state.leadCardExpanded ? config.leadToggleCloseLabel : config.leadToggleOpenLabel);
            if(leadToggleText) {
                leadToggleText.textContent = state.leadCardExpanded ? config.leadToggleCloseText : config.leadToggleOpenText;
            }
            if(leadCollapsedHint) {
                leadCollapsedHint.hidden = state.leadCardExpanded;
                leadCollapsedHint.textContent = config.leadCollapsedHint;
            }
        };

        applyLocalizedCopy(config.language);

        const visitorStorageKey = `fcc_ai_public_visitor_key:${config.assistantType}`;
        const getConversationStorageKey = () => `${config.storageKeyBase}:${config.assistantType}:${state.linkId || 'shared'}`;
        const getContextStorageKey = () => `${config.contextStorageKeyBase}:${config.assistantType}`;

        const escapeHtml = value => String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const isSameOriginNavigationTarget = href => {
            try {
                const parsed = new URL(String(href || ''), window.location.origin);
                return parsed.origin === window.location.origin;
            } catch(error) {
                return false;
            }
        };

        const buildInlineLinkHtml = rawUrl => {
            let url = String(rawUrl || '');
            let trailing = '';

            while(url && /[),.;!?]$/.test(url)) {
                trailing = `${url.slice(-1)}${trailing}`;
                url = url.slice(0, -1);
            }

            if(!url) {
                return escapeHtml(rawUrl || '');
            }

            const normalizedHref = /^https?:\/\//i.test(url) ? url : `https://${url}`;
            const sameOrigin = isSameOriginNavigationTarget(normalizedHref);
            const target = sameOrigin ? '_self' : '_blank';
            const rel = sameOrigin ? 'noopener' : 'noopener noreferrer';

            return `<a class="fcc-chat-extreme__inline-link" href="${escapeHtml(normalizedHref)}" target="${target}" rel="${rel}">${escapeHtml(url)}</a>${escapeHtml(trailing)}`;
        };

        const formatDenseProductText = (rawValue, options = {}) => {
            const config = options && typeof options === 'object' ? options : {};
            let text = String(rawValue || '').replace(/\r\n/g, '\n');

            if(config.streaming || text.includes('\n- ') || text.includes('\n• ')) {
                return text;
            }

            const foreverMatches = text.match(/\bForever\s+[A-Z0-9]/g) || [];

            if(foreverMatches.length < 2) {
                return text;
            }

            if(!/(preporu|smjer|support|rutina|routine|najčeš|najces|glavni|dodatn|daily|korak)/i.test(text)) {
                return text;
            }

            const replacements = [
                [/(najčešće se gleda)\s+(Forever\s+)/giu, '$1:\n- $2'],
                [/(najcesce se gleda)\s+(Forever\s+)/giu, '$1:\n- $2'],
                [/(glavni(?:\s+Forever)?\s+smjer(?:\s+ovdje)?\s+(?:je|ide))\s+(Forever\s+)/giu, '$1:\n- $2'],
                [/(kao glavni(?:\s+Forever)?\s+smjer(?:\s+ovdje)?\s+(?:je|ide))\s+(Forever\s+)/giu, '$1:\n- $2'],
                [/(kao osnovni(?:\s+Forever)?\s+smjer(?:\s+ovdje)?\s+(?:je|ide))\s+(Forever\s+)/giu, '$1:\n- $2'],
                [/(kao glavna preporuka(?:\s+ovdje)?\s+(?:je|ide))\s+(Forever\s+)/giu, '$1:\n- $2'],
                [/,\s+uz\s+(Forever\s+)/giu, '\n- $1'],
                [/\s+uz\s+(Forever\s+)/giu, '\n- $1'],
                [/,\s+a po potrebi i\s+(Forever\s+)/giu, '\n- $1'],
                [/\s+a po potrebi i\s+(Forever\s+)/giu, '\n- $1'],
                [/,\s+te\s+(Forever\s+)/giu, '\n- $1'],
                [/\s+te\s+(Forever\s+)/giu, '\n- $1'],
                [/,\s+i\s+(Forever\s+)/giu, '\n- $1'],
                [/\s+i\s+(Forever\s+)/giu, '\n- $1'],
            ];

            replacements.forEach(([pattern, replacement]) => {
                text = text.replace(pattern, replacement);
            });

            return text.replace(/\n{3,}/g, '\n\n');
        };

        const renderInlineMessageHtml = value => {
            let html = escapeHtml(value);
            html = html.replace(/\*\*([\s\S]+?)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/((?:https?:\/\/|www\.)[^\s<]+)/gi, match => buildInlineLinkHtml(match));

            return html;
        };

        const renderMessageBodyHtml = body => {
            const lines = String(body || '').split('\n').map(line => line.trim()).filter(Boolean);

            if(lines.length && lines.every(line => /^[-•]\s+/.test(line))) {
                return `<ul class="fcc-chat-extreme__list">${lines.map(line => `<li>${renderInlineMessageHtml(line.replace(/^[-•]\s+/, ''))}</li>`).join('')}</ul>`;
            }

            return `<div class="fcc-chat-extreme__section-text">${renderInlineMessageHtml(String(body || '')).replace(/\n/g, '<br>')}</div>`;
        };

        const renderStructuredMessageHtml = value => {
            const blocks = String(value || '').split(/\n{2,}/).map(block => block.trim()).filter(Boolean);

            if(!blocks.length) {
                return '';
            }

            return blocks.map(block => {
                const sectionMatch = block.match(/^([^\n:]{2,48}):\n([\s\S]+)$/);

                if(sectionMatch) {
                    return `<div class="fcc-chat-extreme__section"><div class="fcc-chat-extreme__section-label">${renderInlineMessageHtml(sectionMatch[1].trim())}</div>${renderMessageBodyHtml(sectionMatch[2].trim())}</div>`;
                }

                return `<p class="fcc-chat-extreme__paragraph">${renderInlineMessageHtml(block).replace(/\n/g, '<br>')}</p>`;
            }).join('');
        };

        const renderMessageHtml = (value, options = {}) => {
            const config = options && typeof options === 'object' ? options : {};
            let text = formatDenseProductText(value, config);

            if(config.streaming) {
                const boldMatches = text.match(/\*\*/g) || [];

                if(boldMatches.length % 2 === 1) {
                    const lastBoldIndex = text.lastIndexOf('**');

                    if(lastBoldIndex !== -1) {
                        text = `${text.slice(0, lastBoldIndex)}${text.slice(lastBoldIndex + 2)}`;
                    }
                }

                return renderInlineMessageHtml(text).replace(/\n/g, '<br>');
            }

            return renderStructuredMessageHtml(text);
        };

        const createToken = () => {
            if(window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID().replace(/-/g, '');
            }

            return `fcc${Date.now().toString(16)}${Math.random().toString(16).slice(2, 10)}`;
        };

        const currentPagePayload = () => {
            const searchParams = new URLSearchParams(window.location.search);
            const hashValue = window.location.hash ? window.location.hash.replace(/^#/, '') : '';

            return {
                source_page_url: window.location.href,
                source_page_slug: window.location.pathname.replace(/^\/+|\/+$/g, ''),
                source_page_title: (document.title || '').trim(),
                source_page_section: (searchParams.get('section') || hashValue || '').trim(),
            };
        };

        const readStoredContext = () => {
            try {
                const raw = window.localStorage.getItem(getContextStorageKey());
                return raw ? JSON.parse(raw) : null;
            } catch(error) {
                return null;
            }
        };

        const persistConversationId = value => {
            state.conversationPublicId = value || '';

            try {
                if(state.conversationPublicId) {
                    window.localStorage.setItem(getConversationStorageKey(), state.conversationPublicId);
                } else {
                    window.localStorage.removeItem(getConversationStorageKey());
                }
            } catch(error) {
            }
        };

        const persistContext = details => {
            const conversationPublicId = details && details.conversation_public_id ? details.conversation_public_id : state.conversationPublicId;
            const linkId = Number(details && details.link_id ? details.link_id : state.linkId || 0);
            const ownerName = (details && details.owner_name ? details.owner_name : state.ownerName || '').trim();

            if(!conversationPublicId) {
                return;
            }

            try {
                window.localStorage.setItem(getContextStorageKey(), JSON.stringify({
                    conversation_public_id: conversationPublicId,
                    link_id: linkId,
                    owner_name: ownerName,
                    assistant_type: config.assistantType,
                    saved_at: Date.now(),
                }));
            } catch(error) {
            }
        };

        const hydrateStoredState = () => {
            const bridgeContext = readStoredContext();

            try {
                if(state.linkId > 0) {
                    state.conversationPublicId = window.localStorage.getItem(getConversationStorageKey()) || '';
                }

                state.visitorKey = window.localStorage.getItem(visitorStorageKey) || '';
            } catch(error) {
                state.conversationPublicId = '';
                state.visitorKey = '';
            }

            if(bridgeContext && typeof bridgeContext === 'object') {
                const bridgeLinkId = Number(bridgeContext.link_id || 0);
                const bridgeMatchesCurrentOwner = !state.linkId || !bridgeLinkId || bridgeLinkId === state.linkId;

                if(!state.linkId && bridgeLinkId > 0) {
                    state.linkId = bridgeLinkId;
                }

                if(bridgeMatchesCurrentOwner && !state.conversationPublicId && bridgeContext.conversation_public_id) {
                    state.conversationPublicId = String(bridgeContext.conversation_public_id);
                }

                if(bridgeMatchesCurrentOwner && !state.ownerName && bridgeContext.owner_name) {
                    state.ownerName = String(bridgeContext.owner_name);
                }

                if(!state.conversationPublicId && state.linkId > 0) {
                    try {
                        state.conversationPublicId = window.localStorage.getItem(getConversationStorageKey()) || '';
                    } catch(error) {
                    }
                }
            }

            if(!state.visitorKey) {
                state.visitorKey = createToken();

                try {
                    window.localStorage.setItem(visitorStorageKey, state.visitorKey);
                } catch(error) {
                }
            }
        };

        const scrollThreadToBottom = () => {
            requestAnimationFrame(() => {
                thread.scrollTop = thread.scrollHeight;
            });
        };

        const delay = ms => new Promise(resolve => window.setTimeout(resolve, ms));

        const getMessageFingerprint = message => JSON.stringify([
            String(message && message.role ? message.role : 'assistant'),
            String(message && message.message_type ? message.message_type : 'chat'),
            String(message && message.content ? message.content : ''),
        ]);

        const renderBubbleContent = (bubble, content, options = {}) => {
            bubble.innerHTML = renderMessageHtml(content || '', options);
        };

        const normalizeSuggestions = suggestions => (
            Array.isArray(suggestions)
                ? suggestions.filter(item => item && item.url && item.title).slice(0, 3)
                : []
        );

        const isShortcutSuggestion = suggestion => (
            !!suggestion
            && config.assistantType === 'coach'
            && suggestion.kind === 'internal_page'
        );

        const splitSuggestions = suggestions => {
            const normalizedSuggestions = normalizeSuggestions(suggestions);

            return {
                shortcuts: normalizedSuggestions.filter(isShortcutSuggestion),
                inline: normalizedSuggestions.filter(item => !isShortcutSuggestion(item)),
            };
        };

        const getShortcutEyebrow = suggestion => {
            const url = String(suggestion && suggestion.url ? suggestion.url : '');

            if(url.includes('ai-plan?section=profile')) {
                return config.language === 'en' ? 'AI Plan' : 'AI Plan';
            }

            if(url.includes('ai-plan?section=app_review')) {
                return config.language === 'en' ? 'Review' : 'Review';
            }

            if(url.includes('ai-plan?section=weekly')) {
                return config.language === 'en' ? 'Weekly' : 'Tjedno';
            }

            if(url.includes('ai-plan?section=plan')) {
                return config.language === 'en' ? 'Plan' : 'Plan';
            }

            if(url.includes('/links')) {
                return config.language === 'en' ? 'Apps' : 'Aplikacije';
            }

            if(url.includes('/data')) {
                return config.language === 'en' ? 'Contacts' : 'Kontakti';
            }

            if(url.includes('/dashboard')) {
                return config.language === 'en' ? 'Overview' : 'Pregled';
            }

            if(url.includes('/fcc-ai')) {
                return config.language === 'en' ? 'Assistant' : 'Asistent';
            }

            return config.language === 'en' ? 'Quick action' : 'Brzi korak';
        };

        const renderShortcutRail = suggestions => {
            if(!shortcutsRail) {
                return;
            }

            const shortcutSuggestions = splitSuggestions(suggestions).shortcuts.slice(0, 4);
            shortcutsRail.innerHTML = '';
            shortcutsRail.hidden = shortcutSuggestions.length === 0;

            if(!shortcutSuggestions.length) {
                return;
            }

            shortcutSuggestions.forEach(suggestion => {
                const link = document.createElement('a');
                link.className = 'fcc-chat-extreme__shortcut';
                link.href = suggestion.url;
                link.target = isSameOriginNavigationTarget(suggestion.url) ? '_self' : '_blank';
                link.rel = isSameOriginNavigationTarget(suggestion.url) ? 'noopener' : 'noopener noreferrer';
                link.setAttribute('aria-label', suggestion.title);

                const eyebrow = document.createElement('div');
                eyebrow.className = 'fcc-chat-extreme__shortcut-eyebrow';
                eyebrow.textContent = getShortcutEyebrow(suggestion);

                const title = document.createElement('div');
                title.className = 'fcc-chat-extreme__shortcut-title';
                title.textContent = suggestion.title;

                link.appendChild(eyebrow);
                link.appendChild(title);

                if(suggestion.description) {
                    const description = document.createElement('div');
                    description.className = 'fcc-chat-extreme__shortcut-description';
                    description.textContent = suggestion.description;
                    link.appendChild(description);
                }

                shortcutsRail.appendChild(link);
            });
        };

        const extractLatestShortcutSuggestions = messages => {
            if(!Array.isArray(messages)) {
                return [];
            }

            for(let index = messages.length - 1; index >= 0; index -= 1) {
                const currentMessage = messages[index];
                const suggestions = currentMessage && currentMessage.meta && typeof currentMessage.meta === 'object'
                    ? currentMessage.meta.knowledge_suggestions
                    : [];

                if(splitSuggestions(suggestions).shortcuts.length) {
                    return suggestions;
                }
            }

            return [];
        };

        const appendSuggestions = (wrapper, suggestions) => {
            const normalizedSuggestions = splitSuggestions(suggestions).inline;

            if(!normalizedSuggestions.length) {
                return;
            }

            const container = document.createElement('div');
            container.className = 'fcc-chat-extreme__suggestions';

            normalizedSuggestions.forEach(suggestion => {
                const link = document.createElement('a');
                link.className = 'fcc-chat-extreme__suggestion';
                link.href = suggestion.url;
                link.target = isSameOriginNavigationTarget(suggestion.url) ? '_self' : '_blank';
                link.rel = isSameOriginNavigationTarget(suggestion.url) ? 'noopener' : 'noopener noreferrer';
                link.textContent = suggestion.title;
                link.setAttribute('aria-label', suggestion.title);
                container.appendChild(link);
            });

            wrapper.appendChild(container);
        };

        const createFeedbackButton = (iconClass, label, countValue, activeClass) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `fcc-chat-extreme__feedback-button ${activeClass || ''}`.trim();
            button.setAttribute('aria-label', label);
            button.innerHTML = `<i class="${iconClass}" aria-hidden="true"></i>`;

            if(typeof countValue === 'number' && countValue > 0) {
                const count = document.createElement('span');
                count.className = 'fcc-chat-extreme__feedback-count';
                count.textContent = String(countValue);
                button.appendChild(count);
            }

            return button;
        };

        const submitFeedback = async (messageId, feedbackType, reason = '', note = '') => {
            if(!messageId || !config.feedbackUrl || state.feedbackLoadingIds.has(messageId)) {
                return;
            }

            state.feedbackLoadingIds.add(messageId);

            try {
                await ensureConversation();

                const details = await postForm(config.feedbackUrl, {
                    assistant_type: config.assistantType,
                    scope: config.scope,
                    conversation_public_id: state.conversationPublicId,
                    link_id: state.linkId || 0,
                    blog_post_id: config.blogPostId || 0,
                    language: config.language,
                    source_context: config.sourceContext,
                    visitor_key: state.visitorKey,
                    message_id: messageId,
                    feedback_type: feedbackType,
                    reason,
                    note,
                    ...currentPagePayload(),
                });

                applyConversationDetails(details);
            } catch(error) {
                appendSystemNote(error.message || 'Feedback nije spremljen.');
            } finally {
                state.feedbackLoadingIds.delete(messageId);
            }
        };

        const buildFeedbackNode = message => {
            if(
                !message
                || message.role !== 'assistant'
                || (message.message_type || 'chat') !== 'chat'
                || !Number(message.message_id || 0)
                || !config.feedbackUrl
            ) {
                return null;
            }

            const feedback = message.feedback && typeof message.feedback === 'object' ? message.feedback : {};
            const feedbackWrapper = document.createElement('div');
            feedbackWrapper.className = 'fcc-chat-extreme__feedback';
            const feedbackActions = document.createElement('div');
            feedbackActions.className = 'fcc-chat-extreme__feedback-actions';

            const upButton = createFeedbackButton(
                'fas fa-thumbs-up',
                config.feedbackPositiveLabel,
                Number(feedback.positive_total || 0),
                feedback.viewer_feedback_type === 'up' ? 'is-active-up' : ''
            );

            upButton.addEventListener('click', () => {
                submitFeedback(Number(message.message_id || 0), 'up');
            });

            const downButton = createFeedbackButton(
                'fas fa-thumbs-down',
                config.feedbackNegativeLabel,
                Number(feedback.negative_total || 0),
                feedback.viewer_feedback_type === 'down' ? 'is-active-down' : ''
            );

            const feedbackMenu = document.createElement('div');
            feedbackMenu.className = 'fcc-chat-extreme__feedback-menu';
            feedbackMenu.hidden = true;

            const feedbackMenuLabel = document.createElement('div');
            feedbackMenuLabel.className = 'fcc-chat-extreme__feedback-menu-label';
            feedbackMenuLabel.textContent = config.feedbackReasonTitle;
            feedbackMenu.appendChild(feedbackMenuLabel);

            const viewerReason = String(feedback.viewer_reason || '').trim();
            Object.entries(config.feedbackReasonOptions || {}).forEach(([reasonKey, reasonLabel]) => {
                const reasonButton = document.createElement('button');
                reasonButton.type = 'button';
                reasonButton.className = `fcc-chat-extreme__feedback-reason ${viewerReason === reasonKey ? 'is-active' : ''}`.trim();
                reasonButton.textContent = String(reasonLabel || reasonKey);
                reasonButton.addEventListener('click', () => {
                    const defaultNote = String(feedback.viewer_note || '').trim();
                    const promptResult = window.prompt(config.feedbackNotePrompt, defaultNote);
                    const note = promptResult === null ? defaultNote : String(promptResult || '').trim();
                    submitFeedback(Number(message.message_id || 0), 'down', reasonKey, note);
                    feedbackMenu.hidden = true;
                });
                feedbackMenu.appendChild(reasonButton);
            });

            downButton.addEventListener('click', () => {
                feedbackMenu.hidden = !feedbackMenu.hidden;
            });

            feedbackActions.appendChild(upButton);
            feedbackActions.appendChild(downButton);
            feedbackWrapper.appendChild(feedbackActions);
            feedbackWrapper.appendChild(feedbackMenu);

            return feedbackWrapper;
        };

        const buildMessageNode = message => {
            const wrapper = document.createElement('div');
            const role = ['assistant', 'user', 'system'].includes(message.role) ? message.role : 'assistant';
            wrapper.className = `fcc-chat-extreme__message is-${role}`;

            const bubble = document.createElement('div');
            bubble.className = 'fcc-chat-extreme__bubble';
            renderBubbleContent(bubble, message.content || '');
            wrapper.appendChild(bubble);

            if(role === 'assistant' && message.meta && typeof message.meta === 'object') {
                appendSuggestions(wrapper, message.meta.knowledge_suggestions || []);
            }

            const feedbackNode = buildFeedbackNode(message);
            if(feedbackNode) {
                wrapper.appendChild(feedbackNode);
            }

            return wrapper;
        };

        const renderMessages = messages => {
            thread.innerHTML = '';
            state.renderedMessages = Array.isArray(messages) ? messages.map(message => ({...message})) : [];
            renderShortcutRail(extractLatestShortcutSuggestions(messages));

            if(!Array.isArray(messages) || !messages.length) {
                if(config.defaultWelcome) {
                    renderMessages([{role: 'assistant', message_type: 'welcome', content: config.defaultWelcome}]);
                }

                return;
            }

            messages.forEach(message => {
                thread.appendChild(buildMessageNode(message));
            });

            scrollThreadToBottom();
        };

        const appendMessage = (role, content, meta = null) => {
            const message = {
                role,
                content,
                meta,
                message_type: 'chat',
            };

            state.renderedMessages.push(message);
            thread.appendChild(buildMessageNode(message));
            scrollThreadToBottom();
        };

        const appendSystemNote = content => appendMessage('system', content);

        const setTyping = isVisible => {
            const existing = thread.querySelector('[data-chat-extreme-typing]');

            if(existing) {
                existing.remove();
            }

            if(!isVisible) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'fcc-chat-extreme__message is-assistant';
            wrapper.setAttribute('data-chat-extreme-typing', 'true');
            wrapper.innerHTML = "<div class='fcc-chat-extreme__bubble'><div class='fcc-chat-extreme__typing'><span></span><span></span><span></span></div></div>";
            thread.appendChild(wrapper);
            scrollThreadToBottom();
        };

        const findMessageDivergenceIndex = serverMessages => {
            const localMessages = Array.isArray(state.renderedMessages) ? state.renderedMessages : [];
            const max = Math.min(localMessages.length, serverMessages.length);

            for(let index = 0; index < max; index += 1) {
                if(getMessageFingerprint(localMessages[index]) !== getMessageFingerprint(serverMessages[index])) {
                    return index;
                }
            }

            return max;
        };

        const animateAssistantMessage = async message => {
            const wrapper = document.createElement('div');
            wrapper.className = 'fcc-chat-extreme__message is-assistant';

            const bubble = document.createElement('div');
            bubble.className = 'fcc-chat-extreme__bubble';
            bubble.innerHTML = "<span class='fcc-chat-extreme__stream-cursor'>|</span>";
            wrapper.appendChild(bubble);
            thread.appendChild(wrapper);
            scrollThreadToBottom();

            const fullContent = String(message && message.content ? message.content : '');
            let renderedContent = '';
            let cursor = 0;

            while(cursor < fullContent.length) {
                const chunkSize = fullContent[cursor] === '\n'
                    ? 1
                    : Math.max(1, Math.min(3, Math.floor(Math.random() * 3) + 1));

                renderedContent += fullContent.slice(cursor, cursor + chunkSize);
                cursor += chunkSize;
                bubble.innerHTML = `${renderMessageHtml(renderedContent, {streaming: true})}<span class="fcc-chat-extreme__stream-cursor">|</span>`;
                scrollThreadToBottom();

                const lastChar = renderedContent.slice(-1);
                let pause = renderedContent.length < 140 ? 54 : 40;

                if(lastChar === '\n') {
                    pause = 220;
                } else if(/[.!?]/.test(lastChar)) {
                    pause = 170;
                } else if(/[,:;]/.test(lastChar)) {
                    pause = 120;
                }

                await delay(pause);
            }

            renderBubbleContent(bubble, fullContent);

            if(message.meta && typeof message.meta === 'object') {
                appendSuggestions(wrapper, message.meta.knowledge_suggestions || []);
            }

            const feedbackNode = buildFeedbackNode(message);
            if(feedbackNode) {
                wrapper.appendChild(feedbackNode);
            }

            state.renderedMessages.push({...message});
            scrollThreadToBottom();
        };

        const applyConversationResponseIncrementally = async (details, previousRenderCount, typingStartedAt) => {
            if(!details || typeof details !== 'object') {
                return;
            }

            const serverMessages = Array.isArray(details.messages) ? details.messages : [];
            const divergenceIndex = findMessageDivergenceIndex(serverMessages);
            const shouldFallbackToFullRender = divergenceIndex < previousRenderCount;

            if(shouldFallbackToFullRender) {
                applyConversationDetails(details);
                return;
            }

            const waitLeft = Math.max(0, Number(config.minTypingDelay || 0) - (Date.now() - typingStartedAt));

            if(waitLeft > 0) {
                await delay(waitLeft);
            }

            setTyping(false);
            applyConversationDetails(details, false);

            const newMessages = serverMessages.slice(divergenceIndex);

            if(!newMessages.length) {
                renderShortcutRail(
                    details.reply && Array.isArray(details.reply.knowledge_suggestions)
                        ? details.reply.knowledge_suggestions
                        : extractLatestShortcutSuggestions(serverMessages)
                );
                return;
            }

            for(const message of newMessages) {
                if(message.role === 'assistant' && (message.message_type || 'chat') === 'chat') {
                    await animateAssistantMessage(message);
                } else {
                    state.renderedMessages.push({...message});
                    thread.appendChild(buildMessageNode(message));
                    scrollThreadToBottom();
                }
            }

            renderShortcutRail(
                details.reply && Array.isArray(details.reply.knowledge_suggestions)
                    ? details.reply.knowledge_suggestions
                    : extractLatestShortcutSuggestions(serverMessages)
            );

            const responseLeadCapture = details.reply && details.reply.lead_capture ? details.reply.lead_capture : null;

            if(responseLeadCapture && responseLeadCapture.recommended) {
                await delay(1400);
                syncLeadCard(responseLeadCapture, {autoReveal: true});
                scrollThreadToBottom();
            } else {
                syncLeadCard(responseLeadCapture);
            }
        };

        const syncLeadCard = (leadCapture, options = {}) => {
            if(!config.leadEnabled || !leadCard || !leadForm) {
                return;
            }

            const nextLeadCapture = leadCapture && leadCapture.recommended ? leadCapture : null;

            if(nextLeadCapture) {
                state.pendingLeadCapture = {...nextLeadCapture};
            } else if(state.leadCaptured) {
                state.pendingLeadCapture = null;
            }

            const activeLeadCapture = !state.leadCaptured ? (nextLeadCapture || state.pendingLeadCapture) : null;
            const shouldShow = !!(activeLeadCapture && activeLeadCapture.recommended);
            leadCard.hidden = !shouldShow;

            if(!shouldShow) {
                return;
            }

            const leadTypeInput = leadForm.querySelector('input[name="lead_type"]');

            if(leadTypeInput) {
                leadTypeInput.value = activeLeadCapture.lead_type || 'product_interest';
            }

            if(leadTitle) {
                leadTitle.textContent = activeLeadCapture.headline || <?= json_encode($fcc_chat_lead_title) ?>;
            }

            if(leadText) {
                leadText.textContent = activeLeadCapture.text || <?= json_encode($fcc_chat_lead_text) ?>;
            }

            if(options.autoOpen) {
                setLeadExpanded(true);
            } else if(options.autoReveal && leadCard.hidden === false) {
                setLeadExpanded(false);
            }
        };

        const applyConversationDetails = (details, shouldRenderMessages = true) => {
            if(!details || typeof details !== 'object') {
                return;
            }

            if(details.coach_mode && typeof details.coach_mode === 'object') {
                applyCoachModeDetails(details.coach_mode);
                applyLocalizedCopy(config.language);
            }

            if(Number(details.link_id || 0) > 0) {
                state.linkId = Number(details.link_id || 0);
            }

            if(details.owner_name) {
                state.ownerName = String(details.owner_name);
            }

            if(details.conversation_public_id) {
                persistConversationId(details.conversation_public_id);
                persistContext(details);
            }

            if(typeof details.lead_status === 'string') {
                state.leadCaptured = details.lead_status === 'captured';
            }

            if(shouldRenderMessages) {
                renderMessages(Array.isArray(details.messages) ? details.messages : []);
                renderShortcutRail(
                    details.reply && Array.isArray(details.reply.knowledge_suggestions)
                        ? details.reply.knowledge_suggestions
                        : extractLatestShortcutSuggestions(Array.isArray(details.messages) ? details.messages : [])
                );
            }

            if(shouldRenderMessages) {
                syncLeadCard(details.reply && details.reply.lead_capture ? details.reply.lead_capture : null);
            }
        };

        const postForm = async (url, payload) => {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams(payload).toString(),
            });

            const result = await response.json();

            if(!response.ok || result.status !== 'success') {
                const message = Array.isArray(result.message) ? result.message.join(' ') : (result.message || 'Greška u komunikaciji.');
                throw new Error(message);
            }

            return result.details || {};
        };

        const ensureConversation = async (force = false) => {
            if(state.bootPromise && !force) {
                return state.bootPromise;
            }

            setTyping(true);

            const payload = {
                assistant_type: config.assistantType,
                scope: config.scope,
                conversation_public_id: state.conversationPublicId,
                link_id: state.linkId || 0,
                blog_post_id: config.blogPostId || 0,
                language: config.language,
                source_context: config.sourceContext,
                visitor_key: state.visitorKey,
                ...currentPagePayload(),
            };

            state.bootPromise = postForm(config.conversationUrl, payload)
                .then(details => {
                    applyConversationDetails(details);
                    return details;
                })
                .finally(() => {
                    setTyping(false);
                    state.bootPromise = null;
                });

            return state.bootPromise;
        };

        const syncMobileScrollLock = isOpen => {
            if(!mobileMedia.matches) {
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.left = '';
                document.body.style.right = '';
                document.body.style.width = '';
                return;
            }

            if(isOpen) {
                state.scrollY = window.scrollY || window.pageYOffset || 0;
                document.body.style.position = 'fixed';
                document.body.style.top = `-${state.scrollY}px`;
                document.body.style.left = '0';
                document.body.style.right = '0';
                document.body.style.width = '100%';
            } else {
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.left = '';
                document.body.style.right = '';
                document.body.style.width = '';
                window.scrollTo(0, state.scrollY || 0);
            }
        };

        const isTutorialActive = () => document.body.classList.contains('fcc-tour-mode');

        const setOpen = async isOpen => {
            if(isOpen && isTutorialActive()) {
                return;
            }

            state.isOpen = !!isOpen;
            root.classList.toggle('is-open', state.isOpen);
            panel.setAttribute('aria-hidden', state.isOpen ? 'false' : 'true');
            launcher.setAttribute('aria-expanded', state.isOpen ? 'true' : 'false');
            syncMobileScrollLock(state.isOpen);
            syncLauncherStacking();

            if(state.isOpen) {
                try {
                    await ensureConversation();
                } catch(error) {
                    appendSystemNote(error.message || 'Chat trenutno nije dostupan.');
                }

                window.setTimeout(() => composerInput.focus(), 60);
            }
        };

        root.__fccChatSetOpen = setOpen;

        const sendMessage = async message => {
            if(!message || state.isSending) {
                return;
            }

            state.isSending = true;
            let responseHandled = false;
            const previousRenderCount = state.renderedMessages.length;
            const typingSequence = (async () => {
                await delay(Math.max(0, Number(config.preTypingPause || 0)));

                if(!state.isSending) {
                    return 0;
                }

                setTyping(true);
                return Date.now();
            })();

            if(sendButton) {
                sendButton.disabled = true;
                sendButton.classList.remove('disabled', 'container-disabled-simple');
                sendButton.removeAttribute('data-inner-text');
                sendButton.innerHTML = sendButtonDefaultHtml;
            }

            appendMessage('user', message);

            try {
                const details = await postForm(config.messageUrl, {
                    assistant_type: config.assistantType,
                    scope: config.scope,
                    conversation_public_id: state.conversationPublicId,
                    link_id: state.linkId || 0,
                    blog_post_id: config.blogPostId || 0,
                    language: config.language,
                    source_context: config.sourceContext,
                    visitor_key: state.visitorKey,
                    message,
                    ...currentPagePayload(),
                });

                const typingStartedAt = await typingSequence;
                await applyConversationResponseIncrementally(details, previousRenderCount, typingStartedAt || Date.now());
                responseHandled = true;
            } catch(error) {
                appendSystemNote(error.message || 'Dogodila se greška prilikom slanja poruke.');
            } finally {
                state.isSending = false;

                if(!responseHandled) {
                    setTyping(false);
                }

                if(sendButton) {
                    resetSubmitButton(sendButton, sendButtonDefaultHtml);
                }
            }
        };

        const submitLead = async formData => {
            if(!config.leadEnabled || state.isLeadSubmitting) {
                return;
            }

            const hasContact = (formData.get('email') || '').trim() !== '' || (formData.get('phone') || '').trim() !== '';

            if(!hasContact) {
                appendSystemNote(<?= json_encode($fcc_chat_is_english ? 'Please leave at least an email or phone number.' : 'Ostavite barem email ili telefon.') ?>);
                return;
            }

            state.isLeadSubmitting = true;

            if(leadSubmitButton) {
                leadSubmitButton.disabled = true;
                leadSubmitButton.classList.remove('disabled', 'container-disabled-simple');
                leadSubmitButton.removeAttribute('data-inner-text');
                leadSubmitButton.innerHTML = leadSubmitButtonDefaultHtml;
            }

            try {
                await ensureConversation();

                await postForm(config.leadUrl, {
                    assistant_type: config.assistantType,
                    scope: config.scope,
                    conversation_public_id: state.conversationPublicId,
                    link_id: state.linkId || 0,
                    blog_post_id: config.blogPostId || 0,
                    language: config.language,
                    source_context: config.sourceContext,
                    visitor_key: state.visitorKey,
                    lead_type: formData.get('lead_type') || 'product_interest',
                    name: (formData.get('name') || '').trim(),
                    email: (formData.get('email') || '').trim(),
                    phone: (formData.get('phone') || '').trim(),
                    preferred_contact_channel: formData.get('preferred_contact_channel') || 'whatsapp',
                    message: (formData.get('message') || '').trim(),
                    consent_contact: formData.get('consent_contact') ? '1' : '',
                    ...currentPagePayload(),
                });

                state.leadCaptured = true;
                state.pendingLeadCapture = null;
                leadCard.hidden = true;
                leadForm.reset();
                appendSystemNote(config.leadSuccessMessage);
                await ensureConversation(true);
            } catch(error) {
                appendSystemNote(error.message || 'Lead nije spremljen.');
            } finally {
                state.isLeadSubmitting = false;

                if(leadSubmitButton) {
                    resetSubmitButton(leadSubmitButton, leadSubmitButtonDefaultHtml);
                }
            }
        };

        hydrateStoredState();

        if(config.hideWithoutContext && !state.linkId && !state.conversationPublicId) {
            root.style.display = 'none';
            syncLauncherStacking();
            return;
        }

        renderMessages([{role: 'assistant', message_type: 'welcome', content: config.defaultWelcome}]);
        syncLauncherStacking();

        launcher.addEventListener('click', () => setOpen(true));

        if(closeButton) {
            closeButton.addEventListener('click', () => setOpen(false));
        }

        composerForm.addEventListener('submit', event => {
            event.preventDefault();

            const message = composerInput.value.trim();

            if(message === '') {
                return;
            }

            composerInput.value = '';
            sendMessage(message);
        });

        if(leadForm) {
            leadForm.addEventListener('submit', event => {
                event.preventDefault();
                submitLead(new FormData(leadForm));
            });
        }

        if(leadToggle) {
            leadToggle.addEventListener('click', () => {
                setLeadExpanded(!state.leadCardExpanded);
            });
        }

        if(leadCollapsedHint) {
            leadCollapsedHint.addEventListener('click', () => {
                setLeadExpanded(true);
            });
        }

        if(leadCard) {
            const leadHeader = leadCard.querySelector('.fcc-chat-extreme__lead-header');

            if(leadHeader) {
                leadHeader.addEventListener('click', event => {
                    if(event.target instanceof HTMLElement && event.target.closest('[data-chat-extreme-lead-toggle]')) {
                        return;
                    }

                    if(!state.leadCardExpanded) {
                        setLeadExpanded(true);
                    }
                });
            }
        }

        setLeadExpanded(false);

        const syncViewportState = () => {
            if(!mobileMedia.matches) {
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.left = '';
                document.body.style.right = '';
                document.body.style.width = '';
            } else if(state.isOpen) {
                syncMobileScrollLock(true);
            }
        };

        if(mobileMedia.addEventListener) {
            mobileMedia.addEventListener('change', syncViewportState);
        } else if(mobileMedia.addListener) {
            mobileMedia.addListener(syncViewportState);
        }

        document.addEventListener('keydown', event => {
            if(event.key === 'Escape' && state.isOpen) {
                setOpen(false);
            }
        });

        const syncTutorialVisibility = isActive => {
            root.classList.toggle('is-tutorial-hidden', !!isActive);

            if(isActive) {
                setOpen(false);
            }

            syncLauncherStacking();
        };

        syncTutorialVisibility(document.body.classList.contains('fcc-tour-mode'));

        window.addEventListener('fcc:tutorial:state', event => {
            syncTutorialVisibility(!!(event && event.detail && event.detail.active));
        });
        } catch(error) {
            console.error('FCC Chat bootstrap failed', error);
            emergencyInit();
        }
    })();
</script>
