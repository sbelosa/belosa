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
$fcc_chat_source_context = trim((string) ($fcc_chat_config['source_context'] ?? ($fcc_chat_scope === 'public_blog' ? fcc_t('FCC blog article') : fcc_t('FCC app popup chat'))));
$fcc_chat_hide_without_context = !empty($fcc_chat_config['hide_without_context']);
$fcc_chat_is_product_advisor = $fcc_chat_assistant_type === 'product_advisor';
$fcc_chat_market_code = mb_strtolower(trim((string) ($fcc_chat_config['market_code'] ?? '')));
if(preg_match('/^[a-z]{2}$/D', $fcc_chat_market_code) !== 1) {
    /* Country and display language are separate; never infer a market from language. */
    $fcc_chat_market_code = strtolower((string) \Altum\Link::get_trusted_forever_request_country_code());
    if(!preg_match('/^[a-z]{2}$/D', $fcc_chat_market_code)) $fcc_chat_market_code = getenv('FCC_LOCAL') === '1' ? 'hr' : '';
}
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
    ? ($fcc_chat_is_english ? fcc_t('Open FCC Preporuka') : fcc_t('Otvori FCC Preporuka'))
    : ($fcc_chat_is_english ? fcc_t('Open ChatExtreme') : fcc_t('Otvori ChatExtreme'));
if($fcc_chat_assistant_type === 'coach' && fcc_partner_enabled()) $fcc_chat_toggle_label = fcc_t('Otvori AI pomoćnika');
$fcc_chat_close_label = $fcc_chat_is_english ? fcc_t('Close chat') : fcc_t('Zatvori chat');
$fcc_chat_assistant_title = trim((string) ($fcc_chat_config['assistant_title'] ?? ($fcc_chat_assistant_type === 'coach' ? fcc_t('FCC Coach') : ($fcc_chat_is_product_advisor ? fcc_t('FCC Preporuka') : fcc_t('Extreme Chat Pets')))));
$fcc_chat_intro_label = trim((string) ($fcc_chat_config['intro_label'] ?? $fcc_chat_assistant_title));
$fcc_chat_message_maxlength = $fcc_chat_assistant_type === 'coach' ? 2000 : 1000;
$fcc_chat_input_placeholder = trim((string) ($fcc_chat_config['input_placeholder'] ?? ($fcc_chat_assistant_type === 'coach'
    ? ($fcc_chat_is_english ? fcc_t('How can I help you inside FCC?') : fcc_t('Kako ti mogu pomoći unutar FCC-a?'))
    : ($fcc_chat_is_english ? fcc_t('What would you like to know?') : fcc_t('Što vas zanima?')))));
$fcc_chat_coach_badge = trim((string) ($fcc_chat_config['coach_badge'] ?? ''));
$fcc_chat_coach_notice = trim((string) ($fcc_chat_config['coach_notice'] ?? ''));
$fcc_chat_coach_mode_key = trim((string) ($fcc_chat_config['coach_mode_key'] ?? ($fcc_chat_assistant_type === 'coach' ? 'default' : '')));
$fcc_chat_ui_copy_override = is_array($fcc_chat_config['ui_copy_override'] ?? null) ? $fcc_chat_config['ui_copy_override'] : [];
$fcc_chat_send_label = $fcc_chat_is_english ? 'Send' : fcc_t('Pošalji');
$fcc_chat_lead_title = $fcc_chat_is_english ? fcc_t('Leave your contact') : fcc_t('Ostavite kontakt');
$fcc_chat_lead_text = $fcc_chat_is_english ? fcc_t('The partner can continue personally.') : fcc_t('Suradnik može osobno nastaviti razgovor.');
$fcc_chat_lead_name_placeholder = $fcc_chat_is_english ? 'Your name' : fcc_t('Vaše ime');
$fcc_chat_lead_email_placeholder = 'Email';
$fcc_chat_lead_phone_placeholder = $fcc_chat_is_english ? fcc_t('WhatsApp or phone') : fcc_t('WhatsApp ili telefon');
$fcc_chat_lead_message_placeholder = $fcc_chat_is_english ? 'Short note or goal' : fcc_t('Kratka poruka ili cilj');
$fcc_chat_lead_submit_label = $fcc_chat_is_english ? 'Send contact' : fcc_t('Pošalji kontakt');
$fcc_chat_lead_toggle_open_text = $fcc_chat_is_english ? 'Open' : 'Otvori';
$fcc_chat_lead_toggle_close_text = $fcc_chat_is_english ? 'Hide' : 'Spusti';
$fcc_chat_lead_toggle_open_label = $fcc_chat_is_english ? fcc_t('Open contact form') : fcc_t('Otvori kontakt formu');
$fcc_chat_lead_toggle_close_label = $fcc_chat_is_english ? fcc_t('Collapse contact form') : fcc_t('Spusti kontakt formu');
$fcc_chat_lead_collapsed_hint = $fcc_chat_is_english
    ? fcc_t('Open the form when you want to leave your contact, or continue chatting below.')
    : fcc_t('Otvori formu kad želiš ostaviti kontakt ili nastavi razgovor ispod.');
$fcc_chat_lead_consent_label = $fcc_chat_is_english
    ? fcc_t('I agree that the owner of this app may contact me about this request.')
    : fcc_t('Pristajem da me vlasnik ove aplikacije kontaktira vezano uz ovaj upit.');
$fcc_chat_lead_privacy_url = fcc_ai_get_public_lead_privacy_url();
$fcc_chat_lead_privacy_label = $fcc_chat_is_english ? fcc_t('Privacy policy') : fcc_t('Politika privatnosti');
$fcc_chat_lead_success_message = $fcc_chat_is_english
    ? fcc_t('Thanks. Your contact was saved and the partner can continue from here.')
    : fcc_t('Hvala. Vaš kontakt je spremljen i suradnik sada može nastaviti razgovor.');
$fcc_chat_phone_label = $fcc_chat_is_english ? 'Phone' : 'Telefon';
$fcc_chat_storage_key = trim((string) ($fcc_chat_config['storage_key'] ?? fcc_ai_get_public_storage_key()));
$fcc_chat_context_storage_key = trim((string) ($fcc_chat_config['context_storage_key'] ?? fcc_ai_get_public_context_storage_key()));
$fcc_chat_conversation_url = trim((string) ($fcc_chat_config['conversation_url'] ?? url('l/fcc-ai/conversation')));
$fcc_chat_message_url = trim((string) ($fcc_chat_config['message_url'] ?? url('l/fcc-ai/message')));
$fcc_chat_feedback_url = trim((string) ($fcc_chat_config['feedback_url'] ?? ($fcc_chat_assistant_type === 'coach' ? url('fcc-ai/coach-feedback') : url('l/fcc-ai/feedback'))));

$fcc_chat_product_card_click_url = trim((string) ($fcc_chat_config['product_card_click_url'] ?? url('l/fcc-ai/product-card-click')));
$fcc_chat_lead_url = trim((string) ($fcc_chat_config['lead_url'] ?? url('l/fcc-ai/lead')));
$fcc_chat_default_logo_url = SITE_URL . ASSETS_URL_PATH . 'images/chat-extreme-logo.png';
$fcc_chat_product_advisor_logo_url = SITE_URL . 'partner-assets/fcc-logo.png';
$fcc_chat_coach_logo_url = SITE_URL . ASSETS_URL_PATH . 'images/fcc-coach-logo-wide.png';
$fcc_chat_logo_url = $fcc_chat_assistant_type === 'coach'
    ? $fcc_chat_coach_logo_url
    : ($fcc_chat_is_product_advisor ? $fcc_chat_product_advisor_logo_url : $fcc_chat_default_logo_url);
$fcc_chat_logo_alt = $fcc_chat_assistant_type === 'coach'
    ? fcc_t('Forever Card Club')
    : ($fcc_chat_is_product_advisor ? fcc_t('FCC Preporuka') : 'ChatExtreme');
$fcc_chat_default_launcher_url = SITE_URL . ASSETS_URL_PATH . 'images/chat-extreme-owl.png';
$fcc_chat_product_advisor_launcher_url = SITE_URL . ASSETS_URL_PATH . 'images/fcc-preporuka-launcher.png';
$fcc_chat_default_launcher_fallback_url = SITE_URL . ASSETS_URL_PATH . 'images/sovica.png';
$fcc_chat_coach_launcher_url = SITE_URL . ASSETS_URL_PATH . 'images/fcc-coach-launcher-v2.png';
$fcc_chat_launcher_url = $fcc_chat_assistant_type === 'coach'
    ? $fcc_chat_coach_launcher_url
    : ($fcc_chat_is_product_advisor ? $fcc_chat_product_advisor_launcher_url : $fcc_chat_default_launcher_url);
$fcc_chat_launcher_fallback_url = $fcc_chat_default_launcher_fallback_url;
$fcc_chat_feedback_positive_label = $fcc_chat_is_english ? 'Good answer' : fcc_t('Dobar odgovor');
$fcc_chat_feedback_negative_label = $fcc_chat_is_english ? 'Bad answer' : fcc_t('Loš odgovor');
$fcc_chat_feedback_reason_title = $fcc_chat_is_english ? fcc_t('What was wrong?') : fcc_t('Što nije bilo dobro?');
$fcc_chat_feedback_reason_options = fcc_ai_get_feedback_reason_options($fcc_chat_language_code);
$fcc_chat_feedback_note_prompt = $fcc_chat_is_english
    ? fcc_t('If you want, briefly add a note about this answer.')
    : fcc_t('Ako želite, kratko dodajte napomenu o ovom odgovoru.');
$fcc_chat_feedback_saved_message = $fcc_chat_is_english
    ? fcc_t('Thanks, the feedback was saved.')
    : fcc_t('Hvala, ocjena je spremljena.');
$fcc_chat_launcher_label = trim((string) ($fcc_chat_config['launcher_label'] ?? ''));
if($fcc_chat_launcher_label === '') {
    if($fcc_chat_assistant_type === 'coach') {
        $fcc_chat_launcher_label = $fcc_chat_assistant_title;
    } elseif($fcc_chat_assistant_type === 'pets_advisor') {
        $fcc_chat_launcher_label = $fcc_chat_is_english ? 'AI Pets' : fcc_t('AI Ljubimci');
    } else {
        $fcc_chat_launcher_label = fcc_t('FCC Preporuka');
    }
}
$fcc_chat_default_welcome = trim((string) ($fcc_chat_config['default_welcome'] ?? ''));
if($fcc_chat_default_welcome === '') {
    $fcc_chat_default_welcome = $fcc_chat_assistant_type === 'coach'
        ? fcc_ai_get_internal_coach_welcome_message($fcc_chat_language_code, $fcc_chat_owner_name)
        : fcc_ai_get_public_welcome_message($fcc_chat_assistant_type, $fcc_chat_language_code);
}

$fcc_chat_ui_copy = [];
foreach(['hr', 'en', 'sl', 'bg', 'de', 'sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'] as $fcc_chat_ui_language) {
    $fcc_chat_translate = static fn(string $text): string => fcc_t($text, [], $fcc_chat_ui_language);
    $fcc_chat_ui_override = is_array($fcc_chat_ui_copy_override[$fcc_chat_ui_language] ?? null)
        ? $fcc_chat_ui_copy_override[$fcc_chat_ui_language]
        : [];
    $fcc_chat_ui_is_english = $fcc_chat_ui_language === 'en';
    $fcc_chat_ui_intro_label = $fcc_chat_assistant_type === 'coach'
        ? (string) ($fcc_chat_ui_override['intro_label'] ?? $fcc_chat_intro_label)
        : ($fcc_chat_assistant_type === 'pets_advisor' ? $fcc_chat_translate('Extreme Chat Pets') : $fcc_chat_translate('FCC Preporuka'));

    if($fcc_chat_assistant_type === 'coach') {
        $fcc_chat_ui_launcher_label = (string) ($fcc_chat_ui_override['launcher_label'] ?? $fcc_chat_launcher_label);
        $fcc_chat_ui_input_placeholder = (string) ($fcc_chat_ui_override['input_placeholder'] ?? match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Kako ti lahko pomagam znotraj FCC-ja?'),
            'bg' => $fcc_chat_translate('Как мога да помогна във FCC?'),
            'en' => $fcc_chat_translate('How can I help you inside FCC?'),
            default => $fcc_chat_translate('Kako ti mogu pomoći unutar FCC-a?'),
        });
    } elseif($fcc_chat_assistant_type === 'pets_advisor') {
        $fcc_chat_ui_launcher_label = match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('AI ljubljenčki'),
            'bg' => $fcc_chat_translate('AI любимци'),
            'en' => 'AI Pets',
            default => $fcc_chat_translate('AI Ljubimci'),
        };
        $fcc_chat_ui_input_placeholder = match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Napišite nekaj o ljubljenčku'),
            'bg' => 'Напишете нещо за любимеца',
            'en' => $fcc_chat_translate('Tell me about your pet'),
            default => $fcc_chat_translate('Napišite nešto o ljubimcu'),
        };
    } else {
        $fcc_chat_ui_launcher_label = $fcc_chat_translate('FCC Preporuka');
        $fcc_chat_ui_input_placeholder = match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Kaj vas zanima?'),
            'bg' => 'Какво ви интересува?',
            'en' => $fcc_chat_translate('What would you like to know?'),
            default => $fcc_chat_translate('Što vas zanima?'),
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
            'sl' => $fcc_chat_translate('Pustite kontakt'),
            'bg' => 'Оставете контакт',
            'en' => $fcc_chat_translate('Leave your contact'),
            default => $fcc_chat_translate('Ostavite kontakt'),
        },
        'leadText' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Partner lahko osebno nadaljuje pogovor.'),
            'bg' => 'Партньорът може лично да продължи разговора.',
            'en' => $fcc_chat_translate('The partner can continue personally.'),
            default => $fcc_chat_translate('Suradnik može osobno nastaviti razgovor.'),
        },
        'leadNamePlaceholder' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Vaše ime'),
            'bg' => 'Вашето име',
            'en' => 'Your name',
            default => $fcc_chat_translate('Vaše ime'),
        },
        'leadPhonePlaceholder' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('WhatsApp ali telefon'),
            'bg' => $fcc_chat_translate('WhatsApp или телефон'),
            'en' => $fcc_chat_translate('WhatsApp or phone'),
            default => $fcc_chat_translate('WhatsApp ili telefon'),
        },
        'leadMessagePlaceholder' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Kratko sporočilo ali cilj'),
            'bg' => 'Кратка бележка или цел',
            'en' => 'Short note or goal',
            default => $fcc_chat_translate('Kratka poruka ili cilj'),
        },
        'leadSubmitLabel' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Pošlji kontakt'),
            'bg' => 'Изпрати контакт',
            'en' => 'Send contact',
            default => $fcc_chat_translate('Pošalji kontakt'),
        },
        'leadToggleOpenText' => match($fcc_chat_ui_language) {
            'sl' => 'Odpri',
            'bg' => 'Отвори',
            'en' => 'Open',
            default => $fcc_chat_translate('Otvori'),
        },
        'leadToggleCloseText' => match($fcc_chat_ui_language) {
            'sl' => 'Skrij',
            'bg' => 'Свий',
            'en' => 'Hide',
            default => $fcc_chat_translate('Spusti'),
        },
        'leadToggleOpenLabel' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Odpri kontaktni obrazec'),
            'bg' => 'Отвори контактната форма',
            'en' => $fcc_chat_translate('Open contact form'),
            default => $fcc_chat_translate('Otvori kontakt formu'),
        },
        'leadToggleCloseLabel' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Zapri kontaktni obrazec'),
            'bg' => 'Свий контактната форма',
            'en' => $fcc_chat_translate('Collapse contact form'),
            default => $fcc_chat_translate('Spusti kontakt formu'),
        },
        'leadCollapsedHint' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Odpri obrazec, ko želiš pustiti kontakt, ali nadaljuj klepet spodaj.'),
            'bg' => 'Отвори формата, когато искаш да оставиш контакт, или продължи чата отдолу.',
            'en' => $fcc_chat_translate('Open the form when you want to leave your contact, or continue chatting below.'),
            default => $fcc_chat_translate('Otvori formu kad želiš ostaviti kontakt ili nastavi razgovor ispod.'),
        },
        'leadSuccessMessage' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Hvala. Vaš kontakt je shranjen in partner lahko nadaljuje pogovor.'),
            'bg' => 'Благодарим. Вашият контакт е запазен и партньорът може да продължи разговора.',
            'en' => $fcc_chat_translate('Thanks. Your contact was saved and the partner can continue from here.'),
            default => $fcc_chat_translate('Hvala. Vaš kontakt je spremljen i suradnik sada može nastaviti razgovor.'),
        },
        'phoneLabel' => match($fcc_chat_ui_language) {
            'sl' => 'Telefon',
            'bg' => 'Телефон',
            'en' => 'Phone',
            default => $fcc_chat_translate('Telefon'),
        },
        'feedbackPositiveLabel' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Dober odgovor'),
            'bg' => 'Добър отговор',
            'en' => 'Good answer',
            default => $fcc_chat_translate('Dobar odgovor'),
        },
        'feedbackNegativeLabel' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Slab odgovor'),
            'bg' => 'Лош отговор',
            'en' => 'Bad answer',
            default => $fcc_chat_translate('Loš odgovor'),
        },
        'feedbackReasonTitle' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Kaj ni bilo v redu?'),
            'bg' => 'Какво не беше наред?',
            'en' => $fcc_chat_translate('What was wrong?'),
            default => $fcc_chat_translate('Što nije bilo dobro?'),
        },
        'feedbackReasonOptions' => fcc_ai_get_feedback_reason_options($fcc_chat_ui_language),
        'feedbackNotePrompt' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Če želite, na kratko dodajte opombo o tem odgovoru.'),
            'bg' => 'Ако желаете, добавете кратка бележка за този отговор.',
            'en' => $fcc_chat_translate('If you want, briefly add a note about this answer.'),
            default => $fcc_chat_translate('Ako želite, kratko dodajte napomenu o ovom odgovoru.'),
        },
        'feedbackSavedMessage' => match($fcc_chat_ui_language) {
            'sl' => $fcc_chat_translate('Hvala, povratna informacija je shranjena.'),
            'bg' => 'Благодарим, обратната връзка е запазена.',
            'en' => $fcc_chat_translate('Thanks, the feedback was saved.'),
            default => $fcc_chat_translate('Hvala, ocjena je spremljena.'),
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
        max-width: calc(100vw - 1rem);
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
        min-width: 0;
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
        max-width: calc(100vw - 1rem);
        height: min(690px, calc(100vh - 1rem));
        min-height: 620px;
        min-width: 0;
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
        max-width: 100%;
        overflow-x: hidden;
    }

    .fcc-chat-extreme__brand {
        max-width: 235px;
        width: 100%;
        min-width: 0;
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
        overflow-x: hidden;
        padding: .1rem 1rem .25rem;
        display: flex;
        flex-direction: column;
        gap: .92rem;
        min-height: 0;
        min-width: 0;
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
        min-width: 0;
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
        overflow-wrap: anywhere;
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

    .fcc-chat-extreme__launcher-stack.is-product-advisor .fcc-chat-extreme__launcher-label {
        background: transparent;
        border: 0;
        box-shadow: none;
        padding: 0;
        max-width: none;
        color: #eefcf9;
        text-shadow: 0 2px 10px rgba(8, 12, 18, .55);
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

    .fcc-chat-extreme__product-card {
        width: min(86%, 360px);
        margin-top: .5rem;
        padding: .88rem;
        border: 1px solid rgba(127, 243, 230, .24);
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(28, 42, 43, .98), rgba(20, 22, 29, .98));
        box-shadow: 0 10px 26px rgba(0, 0, 0, .2);
        color: #ffffff;
    }

    .fcc-chat-extreme__product-card-eyebrow {
        margin-bottom: .3rem;
        color: #88efe4;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .075em;
        text-transform: uppercase;
    }

    .fcc-chat-extreme__product-card-name {
        color: #ffffff;
        font-size: .95rem;
        font-weight: 800;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .fcc-chat-extreme__product-card-meta {
        margin-top: .28rem;
        color: rgba(238, 246, 245, .68);
        font-size: .72rem;
        line-height: 1.35;
    }

    .fcc-chat-extreme__product-card-cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        min-height: 38px;
        margin-top: .7rem;
        padding: .55rem .82rem;
        border-radius: 10px;
        background: #77e8dc;
        color: #101719;
        font-size: .78rem;
        font-weight: 800;
        line-height: 1.2;
        text-decoration: none;
        transition: background-color .15s ease, transform .15s ease;
    }

    .fcc-chat-extreme__product-card-cta:hover,
    .fcc-chat-extreme__product-card-cta:focus {
        background: #a4fff5;
        color: #101719;
        text-decoration: none;
        transform: translateY(-1px);
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
        min-width: 0;
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

    .fcc-chat-extreme__lead-privacy {
        color: #bfc8ff;
        font-size: .78rem;
        text-decoration: underline;
        text-underline-offset: 2px;
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
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }

    .fcc-chat-extreme__composer-label {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
    .fcc-chat-extreme.is-coach .fcc-chat-extreme__composer {
        align-items: end;
        gap: 8px;
        padding-top: 8px;
    }
    .fcc-chat-extreme.is-coach .fcc-chat-extreme__composer textarea.fcc-chat-extreme__input {
        box-sizing: border-box;
        display: block;
        height: 48px;
        min-height: 48px;
        max-height: 144px;
        margin: 0;
        padding: 11px 16px;
        border: 1px solid #c4d8d1;
        border-radius: 24px;
        background: #ffffff;
        color: #173d35;
        font-size: 16px;
        line-height: 24px;
        resize: none;
        overflow-y: hidden;
        box-shadow: none;
        -webkit-appearance: none;
        appearance: none;
    }
    .fcc-chat-extreme.is-coach .fcc-chat-extreme__composer textarea.fcc-chat-extreme__input::placeholder {
        color: #667e76;
        opacity: 1;
    }
    .fcc-chat-extreme.is-coach .fcc-chat-extreme__composer textarea.fcc-chat-extreme__input:focus {
        border-color: #62cabc;
        box-shadow: inset 0 0 0 1px #62cabc;
        outline: none;
    }
    .fcc-chat-extreme__input:focus-visible { outline: 2px solid #62cabc; outline-offset: 2px; }
    .fcc-chat-extreme__feedback-note { display: block; box-sizing: border-box; width: 100%; margin: .7rem 0; padding: .7rem; background: #fff; color: #173d35; border: 1px solid #b5d2c7; border-radius: 10px; font-size: 16px; line-height: 1.4; resize: vertical; }
    .fcc-chat-extreme__feedback-saved { font-size: .75rem; color: #c9e8df; }
    .fcc-chat-extreme__feedback-button { min-width: 44px; min-height: 44px; }
    .fcc-chat-extreme__input {
        width: 100%;
        min-width: 0;
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

    .fcc-chat-extreme.is-coach .fcc-chat-extreme__send {
        box-sizing: border-box;
        width: 48px;
        height: 48px;
        min-height: 48px;
        padding: 0;
        border: 1px solid #4b9583;
        border-radius: 50%;
        background: linear-gradient(135deg, #207c65, #145342);
        color: #ffffff;
    }
    .fcc-chat-extreme.is-coach .fcc-chat-extreme__send:focus-visible {
        outline: 2px solid #b1eee0;
        outline-offset: -4px;
    }

    @media (max-width: 576px) {
        .fcc-chat-extreme,
        .fcc-chat-extreme__panel,
        .fcc-chat-extreme__header,
        .fcc-chat-extreme__kicker,
        .fcc-chat-extreme__thread,
        .fcc-chat-extreme__lead,
        .fcc-chat-extreme__composer {
            max-width: 100%;
            overflow-x: hidden;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        .fcc-chat-extreme {
            left: auto;
            right: auto;
            bottom: auto;
            width: 0;
            height: 0;
            max-width: none;
            overflow: visible;
        }

        .fcc-chat-extreme__launcher-stack {
            width: auto;
            align-items: center;
            padding-right: 0;
        }

        .fcc-chat-extreme__launcher {
            width: 82px;
            height: 82px;
        }

        .fcc-chat-extreme__launcher img {
            width: 82px;
            height: 82px;
        }

        .fcc-chat-extreme__launcher-label {
            display: inline-flex;
            margin-top: .12rem;
            max-width: 112px;
            padding: .08rem .38rem;
            font-size: .7rem;
            min-height: 0;
        }

        .fcc-chat-extreme::before {
            display: none;
            content: none;
        }

        .fcc-chat-extreme.is-open {
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100dvh;
            max-width: 100%;
            overflow: hidden;
        }

        .fcc-chat-extreme:not(.is-open) {
            top: auto;
            left: auto;
            right: calc(.45rem + env(safe-area-inset-right));
            bottom: calc(.45rem + env(safe-area-inset-bottom) + var(--fcc-chat-widget-offset, 0px));
            width: 0;
            height: 0;
            max-width: none;
            overflow: visible;
        }

        .fcc-chat-extreme:not(.is-open) .fcc-chat-extreme__launcher-stack {
            position: absolute;
            right: 0;
            bottom: 0;
            width: auto;
        }

        .fcc-chat-extreme__panel {
            position: fixed;
            inset: 0;
            width: auto;
            height: 100dvh;
            min-height: 100dvh;
            z-index: 1;
            background: #434343;
            border-radius: 0;
            border-width: 0;
            box-shadow: none;
            overflow-x: hidden;
            overscroll-behavior-x: none;
            touch-action: pan-y;
            transform: translateY(100%);
            -webkit-transform: translateY(100%);
            isolation: isolate;
            contain: layout paint style;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        .fcc-chat-extreme__input,
        .fcc-chat-extreme__lead-form input,
        .fcc-chat-extreme__lead-form textarea,
        .fcc-chat-extreme__lead-form select {
            font-size: 16px !important;
            line-height: 1.35;
        }

        .fcc-chat-extreme__send {
            flex: 0 0 52px;
        }

        .fcc-chat-extreme__panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: #434343;
            z-index: 0;
        }

        .fcc-chat-extreme__panel > * {
            position: relative;
            z-index: 1;
        }

        .fcc-chat-extreme.is-open .fcc-chat-extreme__panel {
            transform: none;
            -webkit-transform: none;
        }

        .fcc-chat-extreme__header {
            padding-top: calc(.8rem + env(safe-area-inset-top));
            background: #434343;
        }

        .fcc-chat-extreme__brand {
            max-width: min(188px, calc(100vw - 5.75rem));
        }

        .fcc-chat-extreme__kicker,
        .fcc-chat-extreme__shortcuts,
        .fcc-chat-extreme__thread,
        .fcc-chat-extreme__lead,
        .fcc-chat-extreme__composer {
            background: #434343;
            background-image: none;
        }

        .fcc-chat-extreme__composer {
            background: #434343;
            background-image: none;
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

/* Public card advisor uses FCC surfaces independently from Coach and pets. */
.fcc-chat-extreme[data-assistant-type="product_advisor"]{color:#193d35}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__panel,
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__panel::before{background:#edf5ef;border-color:#cfdfd5;color:#193d35}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__header,
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__kicker,
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__thread,
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__shortcuts,
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead{background:#edf5ef;color:#193d35}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__brand{width:180px;max-height:54px;object-fit:contain;object-position:left center}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__close{color:#235346;background:#e0ede4;border-color:#c1d9cb}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__composer{background:#edf5ef;border-top:1px solid #d1e2d7;padding-top:10px}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__input{color:#193d35;background:#fff;border-color:#afcdbc}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__input::placeholder{color:#5e786c}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__send{color:#fff;background:#176b51;border-color:#176b51}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .is-assistant .fcc-chat-extreme__bubble{background:#fff;color:#193d35;box-shadow:0 1px 3px #163e3512;border:1px solid #d8e6de}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .is-user .fcc-chat-extreme__bubble{background:#176b51;color:#fff}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .is-system .fcc-chat-extreme__bubble{background:#e3eee7;color:#345b4e}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__suggestion{background:#e2f0e7;color:#155b47;border-color:#b6d2c1}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__suggestion:hover{background:#cfe6d7;color:#103d2f}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__inline-link{color:#176b51}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__feedback-saved{color:#345b4e}

.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead{border:1px solid #bcd4c6;max-height:45dvh;overflow-y:auto}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead-title,
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead-text,
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead-consent,
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead-collapsed-hint{color:#294e40}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead-form input:not([type="checkbox"]),
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead-form select{background:#fff;color:#193d35;border-color:#b2cdbc}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead-form input::placeholder{color:#62796c}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead-submit{background:#176b51;color:white;min-height:44px}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead-privacy{color:#176b51}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__lead-toggle,
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__feedback-button{background:#e2ede6;color:#3d6756;border-color:#c3d9cb}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__feedback-button.is-active-up{background:#c3e5d0;color:#0b6041}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-chat-extreme__feedback-button.is-active-down{background:#f9dddd;color:#9b3030}
.fcc-chat-extreme[data-assistant-type="product_advisor"]:has(.is-user) .fcc-advisor-start{display:none}
.fcc-chat-extreme[data-assistant-type="product_advisor"] .fcc-advisor-draft a{min-height:44px;display:flex!important;align-items:center;justify-content:center;background:#176b51;color:white;font-size:14px}
.fcc-advisor-disclosure{margin:8px 14px;font-size:11px;line-height:1.5;color:#48665a}

.fcc-advisor-restart{align-self:flex-end;background:transparent;border:0;color:#176b51;font-size:12px;padding:8px 14px;min-height:36px;cursor:pointer}
.fcc-advisor-start{display:flex;gap:6px;flex-wrap:wrap;padding:0 14px 8px}
.fcc-advisor-start button{border:1px solid #bed6c9;background:#eff7f2;color:#164d3d;border-radius:20px;padding:7px 10px;font-size:12px;cursor:pointer}
.fcc-advisor-draft{width:100%;min-width:0;background:#f4f8f5;color:#193e36;border:1px solid #cdded5;border-radius:14px;padding:12px;box-sizing:border-box}
.fcc-advisor-draft summary{cursor:pointer;font-weight:600;overflow-wrap:anywhere}
.fcc-advisor-draft p{font-size:12px;line-height:1.5;margin:10px 0}
.fcc-advisor-draft textarea{display:block;width:100%;min-width:0;box-sizing:border-box;resize:vertical;border:1px solid #aac9b9;border-radius:8px;background:white;color:#183c32;padding:10px;font:inherit;font-size:16px}
.fcc-advisor-draft button{display:block;background:transparent;border:0;color:#1b6650;padding:12px 0;text-align:left;cursor:pointer;white-space:normal}
.fcc-advisor-draft a{display:block!important;text-align:center;white-space:normal!important}
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
    data-market-code="<?= htmlspecialchars($fcc_chat_market_code, ENT_QUOTES, 'UTF-8') ?>"
    data-owner-name="<?= htmlspecialchars($fcc_chat_owner_name, ENT_QUOTES, 'UTF-8') ?>"
    data-conversation-url="<?= htmlspecialchars($fcc_chat_conversation_url, ENT_QUOTES, 'UTF-8') ?>"
    data-message-url="<?= htmlspecialchars($fcc_chat_message_url, ENT_QUOTES, 'UTF-8') ?>"
    data-coach-token="<?= $fcc_chat_assistant_type === 'coach' ? htmlspecialchars(\Altum\Csrf::get(), ENT_QUOTES, 'UTF-8') : '' ?>"
    data-feedback-url="<?= htmlspecialchars($fcc_chat_feedback_url, ENT_QUOTES, 'UTF-8') ?>"
    data-product-card-click-url="<?= htmlspecialchars($fcc_chat_product_card_click_url, ENT_QUOTES, 'UTF-8') ?>"
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
                    <div class="fcc-chat-extreme__coach-brand-tag"><?= htmlspecialchars(fcc_t('Forever Card Club'), ENT_QUOTES, 'UTF-8') ?></div>
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

        <?php if($fcc_chat_is_product_advisor): require_once APP_PATH.'helpers/fcc_advisor_experience.php'; $advisor_ui=fcc_advisor_ui($fcc_chat_language_code); ?>
            <p class="fcc-advisor-disclosure"><?= htmlspecialchars($advisor_ui['disclosure'], ENT_QUOTES, 'UTF-8') ?></p>
            <button type="button" class="fcc-advisor-restart" data-advisor-restart><?= htmlspecialchars($advisor_ui['restart'], ENT_QUOTES, 'UTF-8') ?></button>
            <div class="fcc-advisor-start">
            <?php foreach(['product','business','contact'] as $key): ?><button type="button" data-advisor-start="<?= htmlspecialchars($advisor_ui[$key], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($advisor_ui[$key], ENT_QUOTES, 'UTF-8') ?></button><?php endforeach ?>
            </div>
        <?php endif ?>
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
                        <input type="hidden" name="lead_offer_id" value="" />
                        <input type="text" name="name" maxlength="128" placeholder="<?= htmlspecialchars($fcc_chat_lead_name_placeholder, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="email" name="email" maxlength="320" placeholder="<?= htmlspecialchars($fcc_chat_lead_email_placeholder, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="text" name="phone" maxlength="32" placeholder="<?= htmlspecialchars($fcc_chat_lead_phone_placeholder, ENT_QUOTES, 'UTF-8') ?>" />

                        <select name="preferred_contact_channel">
                            <option value="whatsapp"><?= htmlspecialchars(fcc_t('WhatsApp'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="phone"><?= htmlspecialchars($fcc_chat_phone_label, ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="email"><?= htmlspecialchars(fcc_t('Email'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option value="viber"><?= htmlspecialchars(fcc_t('Viber'), ENT_QUOTES, 'UTF-8') ?></option>
                        </select>

                        <label class="fcc-chat-extreme__lead-consent">
                            <input type="checkbox" name="consent_contact" value="1" required autocomplete="off" />
                            <span data-chat-extreme-lead-consent><?= htmlspecialchars($fcc_chat_lead_consent_label, ENT_QUOTES, 'UTF-8') ?></span>
                        </label>

                        <a
                            class="fcc-chat-extreme__lead-privacy"
                            data-chat-extreme-lead-privacy
                            href="<?= htmlspecialchars($fcc_chat_lead_privacy_url, ENT_QUOTES, 'UTF-8') ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        ><?= htmlspecialchars($fcc_chat_lead_privacy_label, ENT_QUOTES, 'UTF-8') ?></a>

                        <button type="submit" class="fcc-chat-extreme__lead-submit" data-is-ajax="true"><?= htmlspecialchars($fcc_chat_lead_submit_label, ENT_QUOTES, 'UTF-8') ?></button>
                    </form>
                </div>
            </section>
        <?php endif ?>

        <form id="<?= htmlspecialchars($fcc_chat_form_id, ENT_QUOTES, 'UTF-8') ?>" class="fcc-chat-extreme__composer" data-chat-extreme-form>
            <?php if($fcc_chat_assistant_type === 'coach'): ?>
            <label class="fcc-chat-extreme__composer-label" for="<?= htmlspecialchars($fcc_chat_form_id.'-message', ENT_QUOTES, 'UTF-8') ?>"><?= $fcc_chat_is_english ? fcc_t('Your message to Coach') : fcc_t('Tvoja poruka Coachu') ?></label>
            <textarea id="<?= htmlspecialchars($fcc_chat_form_id.'-message', ENT_QUOTES, 'UTF-8') ?>" name="message" class="fcc-chat-extreme__input" rows="1" maxlength="<?= (int)$fcc_chat_message_maxlength ?>" placeholder="<?= $fcc_chat_is_english ? fcc_t('Write a message…') : fcc_t('Napiši poruku…') ?>" autocomplete="off"></textarea>
            <?php else: ?><input
                type="text"
                name="message"
                class="fcc-chat-extreme__input"
                maxlength="<?= (int) $fcc_chat_message_maxlength ?>"
                placeholder="<?= htmlspecialchars($fcc_chat_input_placeholder, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="off"
            /><?php endif ?>

            <button type="submit" class="fcc-chat-extreme__send" data-is-ajax="true" aria-label="<?= htmlspecialchars($fcc_chat_send_label, ENT_QUOTES, 'UTF-8') ?>">
                <i class="fas fa-paper-plane" aria-hidden="true"></i>
            </button>
        </form>
    </section>

    <div
        class="fcc-chat-extreme__launcher-stack<?= $fcc_chat_assistant_type === 'coach' ? ' is-coach' : '' ?><?= $fcc_chat_is_product_advisor ? ' is-product-advisor' : '' ?>"
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

<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-coach-share.css?v=<?= filemtime(THEME_PATH.'assets/css/fcc-coach-share.css') ?>">
<script src="<?= ASSETS_FULL_URL ?>js/fcc-coach-share.js?v=<?= filemtime(THEME_PATH.'assets/js/fcc-coach-share.js') ?>"></script>
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

        /* Custom code: FC-2026-10-12: a starter suggestion writes its text into the question field.
           It was bound only in the fallback start, so in normal use the suggestions did nothing. */
        if(root.dataset.fccStartersBound !== '1') {
            root.dataset.fccStartersBound = '1';
            root.addEventListener('click', event => {
                const starter = event.target.closest('[data-advisor-start]');
                if(!starter || !root.contains(starter)) return;
                const input = root.querySelector('[data-chat-extreme-form] [name="message"]');
                if(!input) return;
                input.value = starter.dataset.advisorStart || starter.textContent.trim();
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.focus();
                if(typeof input.setSelectionRange === 'function') input.setSelectionRange(input.value.length, input.value.length);
            });
        }
        /* /Custom code: FC-2026-10-12 */

        const resizeComposerInput = input => {
            if(!input || input.tagName !== 'TEXTAREA' || !input.getClientRects().length) {
                return;
            }

            const styles = window.getComputedStyle(input);
            const minHeight = parseFloat(styles.minHeight) || 48;
            const maxHeight = parseFloat(styles.maxHeight) || 144;
            const borderHeight = (parseFloat(styles.borderTopWidth) || 0) + (parseFloat(styles.borderBottomWidth) || 0);
            input.style.height = `${minHeight}px`;
            const contentHeight = input.scrollHeight + borderHeight;
            input.style.height = `${Math.min(maxHeight, Math.max(minHeight, contentHeight))}px`;
            input.style.overflowY = contentHeight > maxHeight ? 'auto' : 'hidden';
        };

        const expandingComposerInput = root.querySelector('[data-chat-extreme-form] textarea[name="message"]');
        if(expandingComposerInput) {
            const resizeComposer = () => resizeComposerInput(expandingComposerInput);
            expandingComposerInput.addEventListener('input', resizeComposer);
            expandingComposerInput.addEventListener('focus', resizeComposer);
            // One binding shared by the normal and recovery composers. Shift+Enter
            // keeps a newline; composing with an IME must never send prematurely.
            if(root.dataset.assistantType === 'coach') {
                expandingComposerInput.addEventListener('keydown', event => {
                    if(event.key !== 'Enter' || event.shiftKey || event.isComposing || event.keyCode === 229) return;
                    event.preventDefault();
                    const form = expandingComposerInput.form;
                    const submit = form?.querySelector('button[type="submit"]');
                    if(event.repeat || !expandingComposerInput.value.trim() || submit?.disabled) return;
                    if(form) form.requestSubmit();
                });
            }
            window.addEventListener('resize', resizeComposer);
            resizeComposer();
        }

        const trustedForeverShopUrl = (rawUrl, marketCode) => {
            try {
                const parsed = new URL(String(rawUrl || ''));
                const hostname = parsed.hostname.toLowerCase().replace(/\.$/, '');
                const trustedHostname = hostname === 'foreverliving.com' || hostname.endsWith('.foreverliving.com');
                const marketProductPaths = {
                    hr: '/shop/hrv/hr-hr/products/',
                };
                const expectedPath = marketProductPaths[String(marketCode || '').trim().toLowerCase()] || '';
                const decodedPath = decodeURIComponent(parsed.pathname).toLowerCase();
                const hasTraversalSegment = /(?:^|\/)\.\.?(?:\/|$)/.test(decodedPath);
                const hasResidualEncodedSeparator = /%(?:2e|2f|5c)/i.test(decodedPath) || decodedPath.includes('\\');

                if(
                    parsed.protocol !== 'https:'
                    || !trustedHostname
                    || parsed.username !== ''
                    || parsed.password !== ''
                    || parsed.port !== ''
                    || !expectedPath
                    || !decodedPath.startsWith(expectedPath)
                    || hasTraversalSegment
                    || hasResidualEncodedSeparator
                ) {
                    return '';
                }

                return parsed.href;
            } catch(error) {
                return '';
            }
        };

        const trustedLeadPrivacyUrl = rawUrl => {
            try {
                const parsed = new URL(String(rawUrl || ''), window.location.origin);
                const sameOrigin = parsed.origin === window.location.origin;
                const safeProtocol = parsed.protocol === 'https:' || (sameOrigin && parsed.protocol === 'http:');

                if(
                    !safeProtocol
                    || parsed.username !== ''
                    || parsed.password !== ''
                    || (!sameOrigin && parsed.port !== '')
                ) {
                    return '';
                }

                return parsed.href;
            } catch(error) {
                return '';
            }
        };

        const normalizeLeadOffer = rawOffer => {
            if(!rawOffer || typeof rawOffer !== 'object' || Array.isArray(rawOffer)) {
                return null;
            }

            const leadType = String(rawOffer.lead_type || '').trim().toLowerCase();
            const expectedPurposes = {
                product_interest: 'forever_product_followup',
                business_interest: 'forever_business_followup',
                mixed_interest: 'forever_product_and_business_followup',
            };
            const offerId = String(rawOffer.offer_id || '').trim().toLowerCase();
            const issuedAt = Number(rawOffer.issued_at || 0);
            const expiresAt = Number(rawOffer.expires_at || 0);
            const privacyUrl = trustedLeadPrivacyUrl(rawOffer.privacy_url);
            const headline = String(rawOffer.headline || '').trim();
            const text = String(rawOffer.text || '').trim();
            const consentText = String(rawOffer.consent_text || '').trim();
            const ownerName = String(rawOffer.owner_name || '').trim();
            const now = Math.floor(Date.now() / 1000);

            if(
                rawOffer.v !== 1
                || rawOffer.server_owned !== true
                || rawOffer.eligible !== true
                || rawOffer.recommended !== true
                || !/^[a-f0-9]{32,64}$/.test(offerId)
                || !Object.prototype.hasOwnProperty.call(expectedPurposes, leadType)
                || String(rawOffer.purpose || '') !== expectedPurposes[leadType]
                || !headline
                || headline.length > 180
                || !text
                || text.length > 800
                || !consentText
                || consentText.length > 1000
                || ownerName.length > 128
                || rawOffer.consent_version !== 'fcc_public_contact_v1'
                || rawOffer.consent_source !== 'fcc_product_advisor_v2_lead_form'
                || !privacyUrl
                || !Number.isSafeInteger(issuedAt)
                || !Number.isSafeInteger(expiresAt)
                || issuedAt <= 0
                || expiresAt <= issuedAt
                || issuedAt > now + 60
                || expiresAt < now
                || Number(rawOffer.retention_days || 0) !== 180
            ) {
                return null;
            }

            return {
                v: 1,
                server_owned: true,
                eligible: true,
                recommended: true,
                offer_id: offerId,
                lead_type: leadType,
                purpose: expectedPurposes[leadType],
                headline,
                text,
                owner_name: ownerName,
                consent_text: consentText,
                consent_version: 'fcc_public_contact_v1',
                consent_source: 'fcc_product_advisor_v2_lead_form',
                privacy_url: privacyUrl,
                issued_at: issuedAt,
                expires_at: expiresAt,
                retention_days: 180,
            };
        };

        const leadOfferFromReply = reply => {
            if(!reply || typeof reply !== 'object') {
                return null;
            }

            const meta = reply.meta && typeof reply.meta === 'object' && !Array.isArray(reply.meta)
                ? reply.meta
                : {};
            const replyToUserMessageId = Number(meta.reply_to_user_message_id || 0);

            return ['fcc_product_advisor_v2', 'fcc_public_advisor'].includes(meta.engine)
                && Number.isSafeInteger(replyToUserMessageId)
                && replyToUserMessageId > 0
                && meta.lead_offer_state === 'active'
                ? normalizeLeadOffer(meta.lead_offer)
                : null;
        };

        const latestChatUserMessageId = messages => {
            if(!Array.isArray(messages)) {
                return 0;
            }

            let currentUserMessageId = 0;
            for(const message of messages) {
                if(
                    message
                    && message.role === 'user'
                    && (message.message_type || 'chat') === 'chat'
                ) {
                    const messageId = Number(message.message_id || 0);
                    if(Number.isSafeInteger(messageId) && messageId > currentUserMessageId) {
                        currentUserMessageId = messageId;
                    }
                }
            }

            return currentUserMessageId;
        };

        const extractLatestLeadOffer = messages => {
            const currentUserMessageId = latestChatUserMessageId(messages);
            if(!Number.isSafeInteger(currentUserMessageId) || currentUserMessageId <= 0) {
                return null;
            }

            for(let index = messages.length - 1; index >= 0; index -= 1) {
                const message = messages[index];
                const meta = message && message.meta && typeof message.meta === 'object' && !Array.isArray(message.meta)
                    ? message.meta
                    : {};
                if(
                    message
                    && message.role === 'assistant'
                    && (message.message_type || 'chat') === 'chat'
                    && ['fcc_product_advisor_v2', 'fcc_public_advisor'].includes(meta.engine)
                    && Number(meta.reply_to_user_message_id || 0) === currentUserMessageId
                ) {
                    /* Only the reply bound to the newest V2 user turn is the
                     * current boundary; late older replies are ignored. */
                    return meta.lead_offer_state === 'active'
                        ? normalizeLeadOffer(meta.lead_offer)
                        : null;
                }
            }

            return null;
        };

        const resolveCurrentLeadOffer = (reply, messages) => {
            const meta = reply && reply.meta && typeof reply.meta === 'object' && !Array.isArray(reply.meta)
                ? reply.meta
                : {};
            const currentUserMessageId = latestChatUserMessageId(messages);
            const replyToUserMessageId = Number(meta.reply_to_user_message_id || 0);

            /* An explicit current V2 reply wins even when it revokes the
             * offer, but only while it is bound to the newest server user
             * turn. A slow older HTTP response must defer to newer messages. */
            if(['fcc_product_advisor_v2', 'fcc_public_advisor'].includes(meta.engine)) {
                if(
                    Number.isSafeInteger(currentUserMessageId)
                    && currentUserMessageId > 0
                    && replyToUserMessageId === currentUserMessageId
                ) {
                    return leadOfferFromReply(reply);
                }

                return extractLatestLeadOffer(messages);
            }

            return extractLatestLeadOffer(messages);
        };

        const normalizeProductCard = rawCard => {
            if(!rawCard || typeof rawCard !== 'object' || Array.isArray(rawCard)) {
                return null;
            }

            if(
                rawCard.v2 !== true
                || rawCard.recommendation_eligible !== true
                || rawCard.advisor_status !== 'recommendable'
                || rawCard.shop_available !== true
            ) {
                return null;
            }

            const productKey = String(rawCard.product_key || '').trim();
            const productName = String(rawCard.product_name || '').trim();
            const marketCode = String(rawCard.market_code || '').trim().toLowerCase();
            const revisionId = Number(rawCard.catalog_revision_id || 0);
            const revisionKey = String(rawCard.catalog_revision_key || '').trim();
            const officialUrl = trustedForeverShopUrl(rawCard.official_url, marketCode);

            if(
                !/^[a-z0-9][a-z0-9-]{0,127}$/.test(productKey)
                || !productName
                || productName.length > 160
                || !/^[a-z]{2}$/.test(marketCode)
                || !Number.isSafeInteger(revisionId)
                || revisionId <= 0
                || !revisionKey
                || revisionKey.length > 128
                || !officialUrl
            ) {
                return null;
            }

            return {
                v2: true,
                recommendation_eligible: true,
                advisor_status: 'recommendable',
                shop_available: true,
                product_key: productKey,
                product_name: productName,
                market_code: marketCode,
                catalog_revision_id: revisionId,
                catalog_revision_key: revisionKey,
                official_url: officialUrl,
            };
        };

        const productCardFromReply = reply => {
            if(!reply || typeof reply !== 'object') {
                return null;
            }

            const payload = reply.recommendation_payload && typeof reply.recommendation_payload === 'object'
                ? reply.recommendation_payload
                : null;
            const meta = reply.meta && typeof reply.meta === 'object' ? reply.meta : {};
            const intent = reply.intent && typeof reply.intent === 'object' ? reply.intent : {};

            if(
                !payload
                || meta.engine !== 'fcc_product_advisor_v2'
                || meta.decision !== 'answer'
                || !['recommendation', 'advice', 'followup_confirmation'].includes(intent.v2_intent)
                || intent.safety_level !== 'routine'
                || payload.v2 !== true
                || payload.recommendation_eligible !== true
                || payload.advisor_status !== 'recommendable'
                || payload.shop_available !== true
            ) {
                return null;
            }

            const approvedCard = normalizeProductCard(meta.product_card);
            if(!approvedCard) {
                return null;
            }

            const payloadMarket = String(payload.market_code || meta.market_code || '').trim().toLowerCase();
            const payloadUrl = trustedForeverShopUrl(payload.official_url, payloadMarket);
            const sameServerSelection = approvedCard.product_key === String(payload.primary_product_key || '').trim()
                && approvedCard.product_name === String(payload.primary_product || '').trim()
                && approvedCard.market_code === payloadMarket
                && approvedCard.catalog_revision_id === Number(meta.catalog_revision_id || 0)
                && approvedCard.catalog_revision_key === String(meta.catalog_revision_key || '').trim()
                && approvedCard.official_url === payloadUrl;

            return sameServerSelection ? approvedCard : null;
        };

        const messagesWithReplyProductCard = (messages, reply = null) => {
            const normalizedMessages = Array.isArray(messages)
                ? messages.map(message => ({
                    ...message,
                    meta: message && message.meta && typeof message.meta === 'object'
                        ? {...message.meta}
                        : {},
                }))
                : [];
            const liveProductCard = productCardFromReply(reply);

            if(!liveProductCard) {
                return normalizedMessages;
            }

            for(let index = normalizedMessages.length - 1; index >= 0; index -= 1) {
                const message = normalizedMessages[index];
                if(message && message.role === 'assistant' && (message.message_type || 'chat') === 'chat') {
                    message.meta.product_card = liveProductCard;
                    break;
                }
            }

            return normalizedMessages;
        };

        const sendNonBlockingProductCardClick = (url, payload) => {
            if(!url || !payload || typeof payload !== 'object') {
                return;
            }

            const body = new URLSearchParams(payload).toString();
            try {
                if(typeof navigator.sendBeacon === 'function') {
                    const beaconBody = new Blob([body], {type: 'application/x-www-form-urlencoded;charset=UTF-8'});
                    if(navigator.sendBeacon(url, beaconBody)) {
                        return;
                    }
                }
            } catch(error) {
            }

            try {
                void fetch(url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                    body,
                    keepalive: true,
                    credentials: 'same-origin',
                }).catch(() => {});
            } catch(error) {
            }
        };

        const appendProductCard = (wrapper, rawCard, messageId = 0, trackClick = null) => {
            const productCard = normalizeProductCard(rawCard);

            if(!wrapper || !productCard) {
                return;
            }

            const language = String(root.dataset.language || root.dataset.defaultLanguage || 'hr').trim().toLowerCase();
            const copy = language === 'en'
                ? {eyebrow: 'Recommended product', meta: 'Official Forever store', cta: 'View product'}
                : language === 'sl'
                    ? {eyebrow: 'Priporočen izdelek', meta: 'Uradna trgovina Forever', cta: 'Oglejte si izdelek'}
                    : language === 'bg'
                        ? {eyebrow: 'Препоръчан продукт', meta: 'Официален магазин Forever', cta: 'Виж продукта'}
                        : language === 'de'
                            ? {eyebrow: 'Empfohlenes Produkt', meta: 'Offizieller Forever Shop', cta: 'Produkt ansehen'}
                            : language === 'sr'
                                ? {eyebrow: 'Preporučeni proizvod', meta: 'Zvanična Forever prodavnica', cta: 'Pogledaj proizvod'}
                                : {eyebrow: 'Preporučeni proizvod', meta: 'Službena Forever trgovina', cta: 'Pogledaj proizvod'};

            if(['sq','cnr','fr','es','sv','hu','pl','ro'].includes(language)) { copy.eyebrow=fccT('Preporučeni proizvod'); copy.meta=fccT('Službena Forever trgovina'); copy.cta=fccT('Pogledaj proizvod'); }
            const card = document.createElement('aside');
            card.className = 'fcc-chat-extreme__product-card';
            card.dataset.productKey = productCard.product_key;

            const eyebrow = document.createElement('div');
            eyebrow.className = 'fcc-chat-extreme__product-card-eyebrow';
            eyebrow.textContent = copy.eyebrow;

            const name = document.createElement('div');
            name.className = 'fcc-chat-extreme__product-card-name';
            name.textContent = productCard.product_name;

            const meta = document.createElement('div');
            meta.className = 'fcc-chat-extreme__product-card-meta';
            meta.textContent = `${copy.meta} · ${productCard.market_code.toUpperCase()}`;

            const link = document.createElement('a');
            link.className = 'fcc-chat-extreme__product-card-cta';
            link.href = productCard.official_url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = `${copy.cta} \u2197`;
            link.setAttribute('aria-label', `${copy.cta}: ${productCard.product_name}`);
            if(Number.isSafeInteger(Number(messageId)) && Number(messageId) > 0 && typeof trackClick === 'function') {
                link.addEventListener('click', () => {
                    trackClick(Number(messageId), productCard);
                });
            }

            card.appendChild(eyebrow);
            card.appendChild(name);
            card.appendChild(meta);
            card.appendChild(link);
            wrapper.appendChild(card);
        };

        const emergencyInit = () => {
            if(root.dataset.fccEmergencyBooted === '1') {
                return;
            }

            root.dataset.fccEmergencyBooted = '1';

            const panel = document.getElementById(<?= json_encode($fcc_chat_shell_id) ?>);
            const launcher = document.getElementById(<?= json_encode($fcc_chat_toggle_id) ?>);
            const thread = document.getElementById(<?= json_encode($fcc_chat_thread_id) ?>);
            const composerForm = document.getElementById(<?= json_encode($fcc_chat_form_id) ?>);
            const composerInput = composerForm ? composerForm.querySelector('[name="message"]') : null;
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
            const productCardClickUrl = root.dataset.productCardClickUrl || '';
            const storageKeyBase = root.dataset.storageKey || 'fcc_ai_public_conversation_id';
            const contextStorageKeyBase = root.dataset.contextStorageKey || 'fcc_ai_public_context';
            const sourceContext = root.dataset.sourceContext || 'FCC app popup chat';
            const defaultWelcome = root.dataset.defaultWelcome || '';
            const language = String(root.dataset.language || root.dataset.defaultLanguage || 'hr').trim().toLowerCase();
            const marketCode = String(root.dataset.marketCode || '').trim().toLowerCase();
            const legacyVisitorStorageKey = `fcc_ai_public_visitor_key:${assistantType}`;
            const visitorStorageKey = `${legacyVisitorStorageKey}:${linkId || 'shared'}`;
            const getConversationStorageKey = () => `${storageKeyBase}:${assistantType}:${linkId || 'shared'}`;
            const getContextStorageKey = () => `${contextStorageKeyBase}:${assistantType}:${linkId || 'shared'}`;

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

                if(scope === 'internal_coach' || text.includes('\n- ') || text.includes('\n• ')) {
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
                html = html.replace(/((?:https?:\/\/|www\.)[^\s<]+)/gi, match => buildInlineLinkHtml(match.replace(/&(amp|lt|gt|quot|#039);/g, entity => ({'&amp;':'&','&lt;':'<','&gt;':'>','&quot;':'"','&#039;':"'"})[entity])));

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

            const trackProductCardClick = (messageId, productCard) => {
                if(!productCardClickUrl || !conversationPublicId || !visitorKey || !Number(messageId)) {
                    return;
                }

                sendNonBlockingProductCardClick(productCardClickUrl, {
                    assistant_type: assistantType,
                    scope,
                    conversation_public_id: conversationPublicId,
                    link_id: linkId,
                    visitor_key: visitorKey,
                    message_id: Number(messageId),
                    product_key: productCard.product_key,
                    catalog_revision_id: productCard.catalog_revision_id,
                    catalog_revision_key: productCard.catalog_revision_key,
                });
            };

            const emergencyShareDrafts = new Map();
            const appendMessage = (role, content, meta = null, messageId = 0) => {
                const wrapper = document.createElement('div');
                wrapper.className = `fcc-chat-extreme__message is-${role === 'user' ? 'user' : role === 'system' ? 'system' : 'assistant'}`;

                const bubble = document.createElement('div');
                bubble.className = 'fcc-chat-extreme__bubble';
                const shareCard = assistantType === 'coach' && window.FccCoachShare
                    ? window.FccCoachShare.createCard({role, meta, message_id:messageId}, language, emergencyShareDrafts) : null;
                bubble.innerHTML = renderMessageHtml(shareCard ? meta.guidance : content);
                if(shareCard && !meta.guidance.trim()) bubble.hidden = true;
                wrapper.appendChild(bubble);
                if(shareCard) wrapper.appendChild(shareCard);

                if(role === 'assistant' && meta && typeof meta === 'object') {
                    appendProductCard(wrapper, meta.product_card, Number(messageId), trackProductCardClick);
                }

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

            /* FC-2026-10-12: starter suggestions are bound once at the start of the chat script. */
        const currentPagePayload = () => {
                const searchParams = new URLSearchParams(window.location.search);
                const hashValue = window.location.hash ? window.location.hash.replace(/^#/, '') : '';

                return {
                    market_code: marketCode,
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
            /* Custom code: FC-2026-10-13: Bind private Coach requests to the active session. */
            if(root.dataset.assistantType === 'coach' && root.dataset.scope === 'internal_coach') {
                const token = root.dataset.coachToken;
                if(typeof token !== 'string' || token === '') throw new Error(window.fccT('Sesija je istekla. Osvježi stranicu i pokušaj ponovno.'));
                payload = {...payload, token};
            }
            /* /Custom code: FC-2026-10-13 */
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
                    throw new Error(Array.isArray(result.message) ? result.message.join(' ') : (result.message || window.fccT('Greška u komunikaciji.')));
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
                    if(!window.matchMedia('(pointer: coarse)').matches) window.setTimeout(() => composerInput.focus(), 60);
                }
            };

            root.__fccChatSetOpen = setOpen;

            try {
                conversationPublicId = window.localStorage.getItem(getConversationStorageKey()) || '';
                visitorKey = window.localStorage.getItem(visitorStorageKey) || '';
                if(!visitorKey && conversationPublicId) {
                    visitorKey = window.localStorage.getItem(legacyVisitorStorageKey) || '';
                    if(visitorKey) {
                        window.localStorage.setItem(visitorStorageKey, visitorKey);
                    }
                }
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
                resizeComposerInput(composerInput);
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
                        const assistantMessage = Array.isArray(details.messages)
                            ? details.messages.slice().reverse().find(item => item && item.role === 'assistant' && (item.message_type || 'chat') === 'chat')
                            : null;
                        appendMessage('assistant', replyContent, {
                            ...(assistantMessage?.meta || {}),
                            product_card: productCardFromReply(details.reply),
                        }, Number(assistantMessage && assistantMessage.message_id ? assistantMessage.message_id : 0));
                    }
                } catch(error) {
                    appendMessage('system', error.message || window.fccT('Chat trenutno nije dostupan.'));
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
        const composerInput = composerForm ? composerForm.querySelector('[name="message"]') : null;
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
        const leadConsentText = leadForm ? leadForm.querySelector('[data-chat-extreme-lead-consent]') : null;
        const leadPrivacyLink = leadForm ? leadForm.querySelector('[data-chat-extreme-lead-privacy]') : null;
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
            const primary = String(value || '').trim().toLowerCase().replace(/_/g, '-').split('-')[0];
            if(['hr', 'en', 'sl', 'bg', 'de', 'sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'].includes(primary)) return primary;
            return primary === 'bs' ? 'hr' : 'en';
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

        // The page language is authoritative, including when resuming an older conversation.
        // Browser locale and shopping country must never override the member's selection.
        const defaultLanguage = normalizePublicLanguageCode(root.dataset.language || root.dataset.defaultLanguage || document.documentElement.lang || 'en');
        const resolvedInitialLanguage = defaultLanguage;

        const config = {
            assistantType: root.dataset.assistantType || 'product_advisor',
            scope: root.dataset.scope || 'public_app',
            linkId: Number(root.dataset.linkId || 0),
            blogPostId: Number(root.dataset.blogPostId || 0),
            defaultLanguage,
            language: resolvedInitialLanguage,
            marketCode: String(root.dataset.marketCode || '').trim().toLowerCase(),
            ownerName: root.dataset.ownerName || '',
            conversationUrl: root.dataset.conversationUrl || '',
            messageUrl: root.dataset.messageUrl || '',
            feedbackUrl: root.dataset.feedbackUrl || '',
            productCardClickUrl: root.dataset.productCardClickUrl || '',
            leadUrl: root.dataset.leadUrl || '',
            storageKeyBase: root.dataset.storageKey || 'fcc_ai_public_conversation_id',
            contextStorageKeyBase: root.dataset.contextStorageKey || 'fcc_ai_public_context',
            sourceContext: root.dataset.sourceContext || 'FCC app popup chat',
            leadSuccessMessage: root.dataset.leadSuccess || window.fccT('Kontakt je spremljen.'),
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
            preTypingPause: 80,
            minTypingDelay: 0,
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
            pendingLeadOffer: null,
            scrollY: 0,
            linkId: Number(config.linkId || 0),
            ownerName: config.ownerName || '',
            feedbackLoadingIds: new Set(),
            renderedMessages: [],
        };

        const trackProductCardClick = (messageId, productCard) => {
            if(
                !config.productCardClickUrl
                || !state.conversationPublicId
                || !state.visitorKey
                || !Number(messageId)
                || !productCard
            ) {
                return;
            }

            sendNonBlockingProductCardClick(config.productCardClickUrl, {
                assistant_type: config.assistantType,
                scope: config.scope,
                conversation_public_id: state.conversationPublicId,
                link_id: state.linkId || 0,
                visitor_key: state.visitorKey,
                message_id: Number(messageId),
                product_key: productCard.product_key,
                catalog_revision_id: productCard.catalog_revision_id,
                catalog_revision_key: productCard.catalog_revision_key,
            });
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
                composerInput.placeholder = config.assistantType === 'coach' ? ({en:'Write a message…',sl:'Napiši sporočilo…',bg:'Напиши съобщение…',de:'Schreib eine Nachricht…',sr:'Napiši poruku…'}[config.language] || window.fccT('Napiši poruku…')) : copy.inputPlaceholder;
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

        const legacyVisitorStorageKey = `fcc_ai_public_visitor_key:${config.assistantType}`;
        const getVisitorStorageKey = () => `${legacyVisitorStorageKey}:${state.linkId || 'shared'}`;
        const getConversationStorageKey = () => `${config.storageKeyBase}:${config.assistantType}:${state.linkId || 'shared'}`;
        const getContextStorageKey = () => `${config.contextStorageKeyBase}:${config.assistantType}:${state.linkId || 'shared'}`;

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

            if(root.dataset.scope === 'internal_coach' || config.streaming || text.includes('\n- ') || text.includes('\n• ')) {
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
            html = html.replace(/((?:https?:\/\/|www\.)[^\s<]+)/gi, match => buildInlineLinkHtml(match.replace(/&(amp|lt|gt|quot|#039);/g, entity => ({'&amp;':'&','&lt;':'<','&gt;':'>','&quot;':'"','&#039;':"'"})[entity])));

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
                market_code: config.marketCode,
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

                state.visitorKey = window.localStorage.getItem(getVisitorStorageKey()) || '';
                if(!state.visitorKey && state.conversationPublicId) {
                    state.visitorKey = window.localStorage.getItem(legacyVisitorStorageKey) || '';
                    if(state.visitorKey) {
                        window.localStorage.setItem(getVisitorStorageKey(), state.visitorKey);
                    }
                }
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
                    window.localStorage.setItem(getVisitorStorageKey(), state.visitorKey);
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
                ? suggestions.filter(item => item && item.url && item.title && /^(https?:\/\/)/i.test(item.url)).slice(0, config.assistantType === 'product_advisor' ? 5 : 3)
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
                return window.fccT('Tjedno');
            }

            if(url.includes('ai-plan?section=plan')) {
                return config.language === 'en' ? 'Plan' : 'Plan';
            }

            if(url.includes('/links')) {
                return window.fccT('Aplikacije');
            }

            if(url.includes('/data')) {
                return window.fccT('Kontakti');
            }

            if(url.includes('/dashboard')) {
                return window.fccT('Pregled');
            }

            if(url.includes('/fcc-ai')) {
                return window.fccT('Asistent');
            }

            return config.language === 'en' ? 'Quick action' : window.fccT('Brzi korak');
        };

        const renderShortcutRail = suggestions => {
            if(!shortcutsRail) {
                return;
            }

            const shortcutSuggestions = <?= $fcc_chat_assistant_type === 'coach' && fcc_journey_enabled() ? '[]' : fcc_t('splitSuggestions(suggestions).shortcuts.slice(0, 4)') ?>;
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

        const appendSuggestions = (wrapper, suggestions, messageId = 0) => {
            const normalizedSuggestions = splitSuggestions(suggestions).inline;

            if(!normalizedSuggestions.length) {
                return;
            }

            const container = document.createElement('div');
            container.className = 'fcc-chat-extreme__suggestions';

            normalizedSuggestions.forEach((suggestion, actionIndex) => {
                const recordClick = () => {
                    if(config.assistantType !== 'product_advisor' || !messageId) return;
                    const body = new URLSearchParams({assistant_type: config.assistantType, scope: config.scope, link_id: state.linkId || 0, visitor_key: state.visitorKey, conversation_public_id: state.conversationPublicId, message_id: messageId, action_index: actionIndex});
                    fetch(<?= json_encode(url('l/fcc-ai/advisor-action')) ?>, {method:'POST', body, keepalive:true}).catch(() => {});
                };
                if(suggestion.kind === 'owner_medical_draft' && suggestion.draft && /^https:\/\/wa\.me\/\d+\?/.test(suggestion.url)) {
                    const details = document.createElement('details');details.className = 'fcc-advisor-draft';
                    const summary = document.createElement('summary');summary.textContent = suggestion.title;
                    const note = document.createElement('p');note.textContent = suggestion.ui.privacy;
                    const input = document.createElement('textarea');input.rows = 7;input.maxLength = 2400;input.value = suggestion.draft;input.setAttribute('aria-label',suggestion.title);
                    const clear = document.createElement('button');clear.type = 'button';clear.textContent = suggestion.ui.generic;
                    const open = document.createElement('a');open.className = 'fcc-chat-extreme__suggestion';open.textContent = suggestion.ui.open;open.target = '_blank';open.rel = 'noopener noreferrer';
                    const update = () => {const u=new URL(suggestion.url);u.searchParams.set('text',input.value);open.href=u.href;};
                    clear.addEventListener('click', () => {input.value=suggestion.generic_draft;update();input.focus();});
                    input.addEventListener('input',update);open.addEventListener('click',recordClick);update();
                    details.append(summary,note,input,clear,open);container.appendChild(details);return;
                }
                const link = document.createElement('a');
                link.className = 'fcc-chat-extreme__suggestion';
                link.href = suggestion.url;
                link.target = isSameOriginNavigationTarget(suggestion.url) ? '_self' : '_blank';
                link.rel = isSameOriginNavigationTarget(suggestion.url) ? 'noopener' : 'noopener noreferrer';
                link.textContent = suggestion.title;
                link.setAttribute('aria-label', suggestion.title);
                link.addEventListener('click',recordClick);
                container.appendChild(link);
            });

            wrapper.appendChild(container);
        };

        const createFeedbackButton = (iconClass, label, countValue, activeClass) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `fcc-chat-extreme__feedback-button ${activeClass || ''}`.trim();
            button.setAttribute('aria-label', label);
            button.setAttribute('aria-pressed', activeClass ? 'true' : 'false');
            button.title = label;
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
                    token: config.assistantType === 'coach' ? root.dataset.coachToken : '',
                    feedback_type: feedbackType,
                    reason,
                    note,
                    ...currentPagePayload(),
                });

                const previousScrollTop = thread.scrollTop;
                applyConversationDetails(details);
                thread.scrollTop = previousScrollTop;
            } catch(error) {
                appendSystemNote(error.message || window.fccT('Ocjena nije spremljena.'));
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
            feedbackMenu.hidden = feedback.viewer_feedback_type !== 'down';

            const feedbackMenuLabel = document.createElement('div');
            feedbackMenuLabel.className = 'fcc-chat-extreme__feedback-menu-label';
            feedbackMenuLabel.textContent = config.feedbackReasonTitle;
            feedbackMenu.appendChild(feedbackMenuLabel);

            const viewerReason = String(feedback.viewer_reason || '').trim();
            const noteInput = document.createElement('textarea');
            noteInput.className = 'fcc-chat-extreme__feedback-note';
            noteInput.rows = 2;
            noteInput.maxLength = 1000;
            noteInput.value = String(feedback.viewer_note || '');
            noteInput.placeholder = config.feedbackNotePrompt;
            noteInput.setAttribute('aria-label', config.feedbackNotePrompt);
            Object.entries(config.feedbackReasonOptions || {}).forEach(([reasonKey, reasonLabel]) => {
                const reasonButton = document.createElement('button');
                reasonButton.type = 'button';
                reasonButton.className = `fcc-chat-extreme__feedback-reason ${viewerReason === reasonKey ? 'is-active' : ''}`.trim();
                reasonButton.textContent = String(reasonLabel || reasonKey);
                reasonButton.addEventListener('click', () => {
                    submitFeedback(Number(message.message_id || 0), 'down', reasonKey, noteInput.value.trim());
                    feedbackMenu.hidden = true;
                });
                feedbackMenu.appendChild(reasonButton);
            });

            feedbackMenu.appendChild(noteInput);
            const noteSave = document.createElement('button');
            noteSave.type = 'button';
            noteSave.className = 'fcc-chat-extreme__feedback-reason';
            noteSave.textContent = config.language === 'en' ? window.fccT('Save note') : window.fccT('Spremi napomenu');
            noteSave.addEventListener('click', () => submitFeedback(Number(message.message_id || 0), 'down', viewerReason || 'other', noteInput.value.trim()));
            feedbackMenu.appendChild(noteSave);

            downButton.addEventListener('click', () => {
                if(feedback.viewer_feedback_type === 'down') feedbackMenu.hidden = !feedbackMenu.hidden;
                else submitFeedback(Number(message.message_id || 0), 'down');
            });

            feedbackActions.appendChild(upButton);
            feedbackActions.appendChild(downButton);
            feedbackWrapper.appendChild(feedbackActions);
            if(feedback.viewer_feedback_type) {
                const saved = document.createElement('span');
                saved.className = 'fcc-chat-extreme__feedback-saved';
                saved.setAttribute('role', 'status');
                saved.textContent = config.feedbackSavedMessage;
                feedbackWrapper.appendChild(saved);
            }
            feedbackWrapper.appendChild(feedbackMenu);

            return feedbackWrapper;
        };

        const coachShareDrafts = new Map();
        const buildMessageNode = message => {
            const wrapper = document.createElement('div');
            const role = ['assistant', 'user', 'system'].includes(message.role) ? message.role : 'assistant';
            wrapper.className = `fcc-chat-extreme__message is-${role}`;

            const bubble = document.createElement('div');
            bubble.className = 'fcc-chat-extreme__bubble';
            const shareCard = config.assistantType === 'coach' && window.FccCoachShare
                ? window.FccCoachShare.createCard(message, config.language, coachShareDrafts) : null;
            renderBubbleContent(bubble, shareCard ? message.meta.guidance : (message.content || ''));
            if(shareCard && !message.meta.guidance.trim()) bubble.hidden = true;
            wrapper.appendChild(bubble);
            if(shareCard) wrapper.appendChild(shareCard);

            if(role === 'assistant' && message.meta && typeof message.meta === 'object') {
                appendProductCard(wrapper, message.meta.product_card, Number(message.message_id || 0), trackProductCardClick);
                appendSuggestions(wrapper, message.meta.knowledge_suggestions || [], Number(message.message_id || 0));
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
            wrapper.setAttribute('role', 'status');
            wrapper.innerHTML = "<div class='fcc-chat-extreme__bubble'><div class='fcc-chat-extreme__typing'><span></span><span></span><span></span></div></div>";
            if(config.assistantType === 'coach') wrapper.querySelector('.fcc-chat-extreme__bubble').textContent = config.language === 'en' ? window.fccT('Coach is writing…') : window.fccT('Coach piše…');
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
            state.renderedMessages.push({...message});
            thread.appendChild(buildMessageNode(message));
            scrollThreadToBottom();
        };

        const applyConversationResponseIncrementally = async (details, previousRenderCount, typingStartedAt) => {
            if(!details || typeof details !== 'object') {
                return;
            }

            const serverMessages = messagesWithReplyProductCard(details.messages, details.reply);
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
                syncLeadCard(resolveCurrentLeadOffer(details.reply, serverMessages), {autoOpen: true});
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

            const responseLeadOffer = resolveCurrentLeadOffer(details.reply, serverMessages);

            if(responseLeadOffer) {
                await delay(1400);
                syncLeadCard(responseLeadOffer, {autoOpen: true});
                scrollThreadToBottom();
            } else {
                syncLeadCard(null);
            }
        };

        const syncLeadCard = (leadOffer, options = {}) => {
            if(!config.leadEnabled || !leadCard || !leadForm) {
                return;
            }

            const nextLeadOffer = normalizeLeadOffer(leadOffer);
            if(nextLeadOffer) {
                state.pendingLeadOffer = {...nextLeadOffer};
            } else {
                state.pendingLeadOffer = null;
            }

            const activeLeadOffer = !state.leadCaptured ? normalizeLeadOffer(state.pendingLeadOffer) : null;
            const shouldShow = !!activeLeadOffer;
            leadCard.hidden = !shouldShow;

            if(!shouldShow) {
                const offerIdInput = leadForm.querySelector('input[name="lead_offer_id"]');
                if(offerIdInput) {
                    offerIdInput.value = '';
                }
                const consentInput = leadForm.querySelector('input[name="consent_contact"]');
                if(consentInput) {
                    consentInput.checked = false;
                }
                setLeadExpanded(false);
                return;
            }

            const offerIdInput = leadForm.querySelector('input[name="lead_offer_id"]');
            const consentInput = leadForm.querySelector('input[name="consent_contact"]');

            if(offerIdInput) {
                const offerChanged = offerIdInput.value !== activeLeadOffer.offer_id;
                offerIdInput.value = activeLeadOffer.offer_id;
                if(offerChanged && consentInput) {
                    consentInput.checked = false;
                }
            }

            if(leadTitle) {
                leadTitle.textContent = activeLeadOffer.headline;
            }

            if(leadText) {
                leadText.textContent = activeLeadOffer.text;
            }

            if(leadConsentText) {
                leadConsentText.textContent = activeLeadOffer.consent_text;
            }

            if(leadPrivacyLink) {
                leadPrivacyLink.href = activeLeadOffer.privacy_url;
            }

            if(options.autoOpen) {
                setLeadExpanded(true);
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

            const serverMessages = messagesWithReplyProductCard(details.messages, details.reply);

            if(shouldRenderMessages) {
                renderMessages(serverMessages);
                renderShortcutRail(
                    details.reply && Array.isArray(details.reply.knowledge_suggestions)
                        ? details.reply.knowledge_suggestions
                        : extractLatestShortcutSuggestions(serverMessages)
                );
            }

            if(shouldRenderMessages) {
                syncLeadCard(resolveCurrentLeadOffer(details.reply, serverMessages), {autoOpen: true});
            }
        };

        const postForm = async (url, payload) => {
            /* Custom code: FC-2026-10-13: Bind private Coach requests to the active session. */
            if(root.dataset.assistantType === 'coach' && root.dataset.scope === 'internal_coach') {
                const token = root.dataset.coachToken;
                if(typeof token !== 'string' || token === '') throw new Error(window.fccT('Sesija je istekla. Osvježi stranicu i pokušaj ponovno.'));
                payload = {...payload, token};
            }
            /* /Custom code: FC-2026-10-13 */
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
                const message = Array.isArray(result.message) ? result.message.join(' ') : (result.message || window.fccT('Greška u komunikaciji.'));
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
                    appendSystemNote(error.message || window.fccT('Chat trenutno nije dostupan.'));
                }

                if(!window.matchMedia('(pointer: coarse)').matches) window.setTimeout(() => composerInput.focus(), 60);
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
            /* A new user turn immediately invalidates the previous contact
             * offer in the UI. Only the fresh server reply may activate one
             * again, including after network failure. */
            syncLeadCard(null);

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
                appendSystemNote(error.message || window.fccT('Dogodila se greška prilikom slanja poruke.'));
            } finally {
                state.isSending = false;

                setTyping(false);

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
                appendSystemNote(<?= json_encode($fcc_chat_is_english ? fcc_t('Please leave at least an email or phone number.') : fcc_t('Ostavite barem email ili telefon.')) ?>);
                return;
            }

            const activeLeadOffer = normalizeLeadOffer(state.pendingLeadOffer);
            const submittedOfferId = String(formData.get('lead_offer_id') || '').trim().toLowerCase();
            const hasExplicitConsent = formData.get('consent_contact') === '1';

            if(!activeLeadOffer || submittedOfferId !== activeLeadOffer.offer_id) {
                appendSystemNote(<?= json_encode($fcc_chat_is_english ? fcc_t('This contact offer is no longer valid. Continue the conversation to request a new one.') : fcc_t('Ova ponuda za kontakt više nije važeća. Nastavite razgovor kako biste dobili novu.')) ?>);
                syncLeadCard(null);
                return;
            }

            if(!hasExplicitConsent) {
                appendSystemNote(<?= json_encode($fcc_chat_is_english ? fcc_t('Please tick the consent checkbox before sending your contact.') : fcc_t('Prije slanja kontakta označite kućicu izričite privole.')) ?>);
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
                    visitor_key: state.visitorKey,
                    lead_offer_id: activeLeadOffer.offer_id,
                    name: (formData.get('name') || '').trim(),
                    email: (formData.get('email') || '').trim(),
                    phone: (formData.get('phone') || '').trim(),
                    preferred_contact_channel: formData.get('preferred_contact_channel') || 'whatsapp',
                    consent_contact: '1',
                });

                state.leadCaptured = true;
                state.pendingLeadOffer = null;
                leadCard.hidden = true;
                leadForm.reset();
                appendSystemNote(config.leadSuccessMessage);
                await ensureConversation(true);
            } catch(error) {
                appendSystemNote(error.message || window.fccT('Kontakt nije spremljen.'));
            } finally {
                state.isLeadSubmitting = false;

                if(leadSubmitButton) {
                    resetSubmitButton(leadSubmitButton, leadSubmitButtonDefaultHtml);
                }
            }
        };

        root.querySelector('[data-advisor-restart]')?.addEventListener('click', async event => {
            if(state.isSending || state.isLeadSubmitting || state.bootPromise) return;
            const button=event.currentTarget; button.disabled=true;
            persistConversationId('');
            state.visitorKey=createToken(); state.leadCaptured=false; state.pendingLeadOffer=null;
            try {window.localStorage.setItem(getVisitorStorageKey(),state.visitorKey);window.localStorage.removeItem(getContextStorageKey());} catch(error) {}
            composerInput.value=''; if(leadForm) leadForm.reset(); syncLeadCard(null);
            renderMessages([{role:'assistant',message_type:'welcome',content:config.defaultWelcome}]);
            try {await ensureConversation(true);} catch(error) {appendSystemNote(error.message);} finally {button.disabled=false;}
        });
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

            if(message === '' || state.isSending) {
                return;
            }

            composerInput.value = '';
            resizeComposerInput(composerInput);
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
