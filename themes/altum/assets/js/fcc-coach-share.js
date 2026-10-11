(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.FccCoachShare = api;
})(typeof window === 'undefined' ? globalThis : window, function () {
    'use strict';
    const copy = {
        hr: ['Poruka za slanje', 'Ovdje možeš urediti poruku.', 'Otvori WhatsApp', 'Kopiraj poruku', 'Kopirano', 'Označi tekst i kopiraj ga.'],
        sr: ['Poruka za slanje', 'Ovde možeš da izmeniš poruku.', 'Otvori WhatsApp', 'Kopiraj poruku', 'Kopirano', 'Označi tekst i kopiraj ga.'],
        en: ['Message to send', 'You can edit the message here.', 'Open WhatsApp', 'Copy message', 'Copied', 'Select and copy the text.'],
        sl: ['Sporočilo za pošiljanje', 'Tukaj lahko urediš sporočilo.', 'Odpri WhatsApp', 'Kopiraj sporočilo', 'Kopirano', 'Izberi in kopiraj besedilo.'],
        de: ['Nachricht zum Senden', 'Hier kannst du die Nachricht bearbeiten.', 'WhatsApp öffnen', 'Nachricht kopieren', 'Kopiert', 'Text markieren und kopieren.'],
        sq: ['Mesazh për dërgim', 'Këtu mund ta ndryshosh mesazhin.', 'Hap WhatsApp', 'Kopjo mesazhin', 'U kopjua', 'Përzgjidh dhe kopjo tekstin.'],
        cnr: ['Poruka za slanje', 'Ovdje možeš urediti poruku.', 'Otvori WhatsApp', 'Kopiraj poruku', 'Kopirano', 'Označi tekst i kopiraj ga.'],
        fr: ['Message à envoyer', 'Tu peux modifier le message ici.', 'Ouvrir WhatsApp', 'Copier le message', 'Copié', 'Sélectionne et copie le texte.'],
        es: ['Mensaje para enviar', 'Puedes editar el mensaje aquí.', 'Abrir WhatsApp', 'Copiar mensaje', 'Copiado', 'Selecciona y copia el texto.'],
        hu: ['Üzenet küldésre', 'Itt szerkesztheted az üzenetet.', 'WhatsApp megnyitása', 'Üzenet másolása', 'Másolva', 'Jelöld ki és másold ki a szöveget.'],
        ro: ['Mesaj de trimis', 'Poți edita mesajul aici.', 'Deschide WhatsApp', 'Copiază mesajul', 'Copiat', 'Selectează și copiază textul.'],
        pl: ['Wiadomość do wysłania', 'Tutaj możesz edytować wiadomość.', 'Otwórz WhatsApp', 'Kopiuj wiadomość', 'Skopiowano', 'Zaznacz i skopiuj tekst.'],
        sv: ['Meddelande att skicka', 'Du kan redigera meddelandet här.', 'Öppna WhatsApp', 'Kopiera meddelande', 'Kopierat', 'Markera och kopiera texten.']
    };
    const educationLinkNotice = {
        hr: 'U poruci za kupca koristi javne poveznice proizvoda.',
        sr: 'U poruci za kupca koristi javne linkove proizvoda.',
        en: 'Use public product links in the message for the customer.',
        sl: 'V sporočilu za stranko uporabi javne povezave do izdelkov.',
        de: 'Verwende in der Nachricht für den Kunden öffentliche Produktlinks.',
        sq: 'Në mesazhin për klientin përdor lidhje publike të produkteve.',
        cnr: 'U poruci za kupca koristi javne poveznice proizvoda.',
        fr: 'Utilise des liens publics vers les produits dans le message au client.',
        es: 'Usa enlaces públicos de los productos en el mensaje para el cliente.',
        hu: 'A vásárlónak szánt üzenetben nyilvános termékhivatkozásokat használj.',
        ro: 'În mesajul pentru client folosește linkuri publice către produse.',
        pl: 'W wiadomości dla klienta używaj publicznych linków do produktów.',
        sv: 'Använd offentliga produktlänkar i meddelandet till kunden.'
    };
    function messageText(value) { return typeof value === 'string' ? value.trim() : ''; }
    // Keep private education links out of customer messages. Other Coach links
    // retain their existing behaviour, including business and webinar messages.
    function containsPrivateEducationLink(value) {
        let decoded = messageText(value);
        for (let pass = 0; pass < 3; pass++) {
            let next;
            try { next = decodeURIComponent(decoded); }
            catch (_) { next = decoded.replace(/%([\da-f]{2})/gi, (_, hex) => String.fromCharCode(parseInt(hex, 16))); }
            if (next === decoded) break;
            decoded = next;
        }
        const candidates = decoded.match(/(?:https?:\/\/|www\.|\/{1,2})[^\s<>"'`]+|(?:^|\s)fcc-product-education(?:[/?#][^\s<>"'`]*)?(?=$|\s|[),.;])/gi) || [];
        return candidates.some(candidate => {
            try {
                const raw = candidate.trim().replace(/[),.;!?]+$/, '');
                const href = /^www\./i.test(raw) ? 'https://' + raw : raw;
                const parsed = new URL(href, 'https://fcc-coach.invalid/');
                return /(?:^|\/)fcc-product-education(?:\/|$)/i.test(parsed.pathname);
            } catch (_) { return false; }
        });
    }
    function whatsappUrl(value) {
        const text = messageText(value);
        // Avoid the wa.me redirect, which can replace four-byte emoji in the payload.
        return text && [...text].length <= 4000 && !containsPrivateEducationLink(text)
            ? 'https://api.whatsapp.com/send?text=' + encodeURIComponent(text) : '';
    }
    // Only the explicit server field is shareable. Never split arbitrary assistant prose.
    function shareable(message) {
        return message && message.role === 'assistant' && typeof message.meta?.guidance === 'string'
            && messageText(message.meta?.recipient_message) !== '' && whatsappUrl(message.meta.recipient_message) !== '';
    }
    function createCard(message, language, drafts) {
        if (!shareable(message)) return null;
        const locale = String(language || '').trim().toLowerCase().replace(/_/g, '-').split('-')[0];
        const labels = copy[locale] || copy.hr.map(text => typeof window !== 'undefined' && window.fccT ? window.fccT(text) : text);
        const privateLinkNotice = educationLinkNotice[locale] || (typeof window !== 'undefined' && window.fccT ? window.fccT(educationLinkNotice.hr) : educationLinkNotice.hr);
        const key = String(message.message_id || message.meta.recipient_message);
        const card = document.createElement('section');
        card.className = 'fcc-coach-share';
        const label = document.createElement('label');
        label.className = 'fcc-coach-share__label';
        label.append(document.createTextNode(labels[0]));
        const input = document.createElement('textarea');
        input.className = 'fcc-coach-share__text';
        input.rows = 6;
        input.maxLength = 4000;
        input.value = drafts.has(key) ? drafts.get(key) : message.meta.recipient_message;
        input.setAttribute('aria-label', labels[0]);
        label.append(input);
        const hint = document.createElement('p');
        hint.className = 'fcc-coach-share__hint';
        hint.textContent = labels[1];
        const actions = document.createElement('div');
        actions.className = 'fcc-coach-share__actions';
        const link = document.createElement('a');
        link.className = 'fcc-coach-share__whatsapp';
        link.textContent = labels[2];
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'fcc-coach-share__copy';
        button.textContent = labels[3];
        const status = document.createElement('span');
        status.className = 'fcc-coach-share__status';
        status.setAttribute('role', 'status');
        const update = () => {
            drafts.set(key, input.value);
            const url = whatsappUrl(input.value);
            if (url) link.href = url;
            else link.removeAttribute('href');
            link.setAttribute('aria-disabled', url ? 'false' : 'true');
            button.disabled = !url;
            status.textContent = containsPrivateEducationLink(input.value) ? privateLinkNotice : '';
        };
        input.addEventListener('input', update);
        link.addEventListener('click', event => { update(); if (!whatsappUrl(input.value)) event.preventDefault(); });
        button.addEventListener('click', async () => {
            update();
            if (!whatsappUrl(input.value)) { input.focus(); return; }
            try {
                if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(messageText(input.value));
                else { input.select(); if (!document.execCommand('copy')) throw new Error('copy'); }
                status.textContent = labels[4];
            } catch (_) { input.focus(); input.select(); status.textContent = labels[5]; }
        });
        update();
        actions.append(link, button);
        card.append(label, hint, actions, status);
        return card;
    }

    // A private help page can prepare an internal Coach question, never submit it.
    const helpSections = {"start":["start","steps","faq"],"daily":["start","steps","example","faq"],"card":["start","steps","faq"],"shop":["start","steps","country","example","faq"],"products":["start","steps","faq"],"business":["start","steps","faq"],"webinar-invite":["start","steps","attendance","next-date","example","faq"],"webinar-host":["start","steps","visibility","recurrence","example","faq"],"contacts":["start","steps","example","faq"],"team-outreach":["start","steps","sent","example","faq"],"coach":["start","steps","help-links","example","faq"],"notifications":["start","steps","faq"],"results":["start","steps","faq"],"plan":["start","steps","faq"],"presentation":["start","steps","customize","downloads","example","faq"],"advanced":["start","steps","faq"],"support":["start","steps","faq"]};
    function prefillHelp(options) {
        if (typeof window === 'undefined' || typeof document === 'undefined') return false;
        window.fccHelpCoachLastStatus = 'unavailable';
        if (!options || typeof options.articleId !== 'string' || typeof options.prompt !== 'string') return false;
        if (Object.prototype.hasOwnProperty.call(options, 'section') && typeof options.section !== 'string') return false;
        const articleId = options.articleId;
        const section = typeof options.section === 'string' ? options.section : '';
        if (!Object.prototype.hasOwnProperty.call(helpSections, articleId)
            || (section !== '' && !helpSections[articleId].includes(section))) return false;
        const page = document.querySelector('[data-fcc-help-page]');
        if (!page || page.dataset.fccHelpCoachAccess !== '1') return false;
        const popup = document.querySelector('#fcc-coach-chat-extreme[data-assistant-type="coach"][data-scope="internal_coach"]');
        const input = popup?.querySelector('[data-chat-extreme-form] [name="message"]');
        if (!popup || !input || input.disabled || typeof window.fccChatExtremeToggle !== 'function') return false;
        const prompt = options.prompt.trim();
        const limit = Number(input.maxLength) > 0 ? Number(input.maxLength) : 2000;
        if (!prompt || [...prompt].length > Math.min(limit, 2000)) return false;
        window.fccChatExtremeToggle(popup.id, true);
        if (String(input.value || '').trim() !== '') {
            window.fccHelpCoachLastStatus = 'draft';
            input.focus();
            return false;
        }
        input.value = prompt;
        input.dataset.fccHelpArticle = articleId;
        input.dataset.fccHelpSection = section;
        input.dispatchEvent(new Event('input', {bubbles: true}));
        input.focus();
        window.fccHelpCoachLastStatus = 'prefilled';
        return true;
    }
    if (typeof window !== 'undefined' && typeof window.addEventListener === 'function') {
        window.addEventListener('fcc:help-coach', event => {
            const accepted = prefillHelp(event.detail);
            if (event.detail && typeof event.detail === 'object') {
                try { event.detail.accepted = accepted; } catch (_) {}
            }
        });
    }
    return {whatsappUrl, shareable, createCard, prefillHelp};
});
