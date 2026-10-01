<?php
defined('ALTUMCODE') || die();

/** Shared presentation only. Recipient selection and delivery stay in the existing mail transport. */
function fc_email_design_copy(string $language): array {
    $locale = \Altum\Language::$active_languages[$language] ?? 'en';
    $copy = [
        'en' => ['Your business, connected.', 'Need a hand?', 'Contact us', 'Privacy', 'You can unsubscribe from emails like this at any time.', 'Unsubscribe'],
        'hr' => ['Tvoje poslovanje, povezano.', 'Trebaš pomoć?', 'Javi nam se', 'Privatnost', 'Ako više ne želiš primati ovakve email obavijesti, možeš se odjaviti.', 'Odjavi me'],
        'sl' => ['Tvoje poslovanje, povezano.', 'Potrebuješ pomoč?', 'Piši nam', 'Zasebnost', 'Od prejemanja takšnih obvestil se lahko kadar koli odjaviš.', 'Odjava'],
        'de' => ['Dein Business, verbunden.', 'Brauchst du Hilfe?', 'Kontaktiere uns', 'Datenschutz', 'Du kannst dich jederzeit von solchen E-Mails abmelden.', 'Abmelden'],
        'es' => ['Tu negocio, conectado.', '¿Necesitas ayuda?', 'Contáctanos', 'Privacidad', 'Puedes dejar de recibir estos correos en cualquier momento.', 'Cancelar suscripción'],
        'fr' => ['Ton activité, connectée.', 'Besoin d’aide ?', 'Contacte-nous', 'Confidentialité', 'Tu peux te désabonner de ces emails à tout moment.', 'Se désabonner'],
        'sr' => ['Tvoje poslovanje, povezano.', 'Treba ti pomoć?', 'Javi nam se', 'Privatnost', 'Ako više ne želiš da primaš ovakva email obaveštenja, možeš da se odjaviš.', 'Odjavi me'],
        'cnr' => ['Tvoje poslovanje, povezano.', 'Treba ti pomoć?', 'Javi nam se', 'Privatnost', 'Ako više ne želiš da primaš ovakva email obavještenja, možeš da se odjaviš.', 'Odjavi me'],
        'sq' => ['Biznesi yt, i lidhur.', 'Të duhet ndihmë?', 'Na kontakto', 'Privatësia', 'Mund të çregjistrohesh nga këto email-e në çdo kohë.', 'Çregjistrohu'],
    ];
    return array_combine(['tagline', 'help', 'contact', 'privacy', 'unsubscribe_text', 'unsubscribe_link'], $copy[$locale] ?? $copy['en']);
}

/** Convert class based legacy/editor bodies to email safe inline styles without changing links or text. */
function fc_email_design_content(string $html): string {
    if(!preg_match('/<[a-z][^>]*>/i', $html)) {
        $html = nl2br(htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }
    if(!class_exists('DOMDocument')) return $html;
    $document = new \DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $body = $document->getElementsByTagName('body')->item(0);
    if(!$body) return $html;
    $defaults = [
        'p' => 'margin:0 0 20px;font-size:16px;line-height:1.7;',
        'h1' => 'margin:0 0 20px;color:#143d35;font-size:28px;line-height:1.25;font-weight:700;',
        'h2' => 'margin:28px 0 14px;color:#143d35;font-size:23px;line-height:1.3;font-weight:700;',
        'h3' => 'margin:24px 0 12px;color:#143d35;font-size:19px;line-height:1.4;font-weight:700;',
        'h4' => 'margin:20px 0 12px;color:#143d35;font-size:17px;line-height:1.4;font-weight:700;',
        'a' => 'color:#106653;text-decoration:underline;overflow-wrap:anywhere;',
        'ul' => 'margin:0 0 24px;padding:0 0 0 24px;',
        'ol' => 'margin:0 0 24px;padding:0 0 0 24px;',
        'li' => 'margin:0 0 10px;padding-left:4px;font-size:16px;line-height:1.65;',
        'img' => 'border:0;max-width:100%;height:auto;',
        'table' => 'max-width:100%;mso-table-lspace:0pt;mso-table-rspace:0pt;',
        'td' => 'font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.65;',
        'blockquote' => 'margin:24px 0;padding:16px 20px;border-left:3px solid #9bbda9;background-color:#edf3ef;',
        'hr' => 'border:0;border-top:1px solid #dbe6df;margin:24px 0;',
        'pre' => 'white-space:pre-wrap;overflow-wrap:anywhere;font-size:14px;',
    ];
    foreach($body->getElementsByTagName('*') as $element) {
        $tag = strtolower($element->tagName);
        $style = $defaults[$tag] ?? '';
        $classes = preg_split('/\s+/', trim($element->getAttribute('class')));
        foreach(['right', 'left', 'center', 'justify'] as $alignment) {
            if(in_array('ql-align-' . $alignment, $classes, true) || in_array('text-' . $alignment, $classes, true)) $style .= 'text-align:' . $alignment . ';';
        }
        foreach(['small' => 14, 'large' => 20, 'huge' => 26] as $size => $pixels) {
            if(in_array('ql-size-' . $size, $classes, true)) $style .= 'font-size:' . $pixels . 'px;';
        }
        if(in_array('note', $classes, true)) $style .= 'font-size:13px;color:#526b64;';
        if(in_array('word-break-all', $classes, true)) $style .= 'overflow-wrap:anywhere;word-break:break-all;';
        $existing = trim($element->getAttribute('style'));
        $style .= $existing;
        if($tag === 'a') {
            $button = in_array('cta', $classes, true) || in_array('fcc-email-button', $classes, true);
            $parent = $element->parentNode;
            for($ancestor = $parent; $ancestor instanceof \DOMElement && $ancestor !== $body; $ancestor = $ancestor->parentNode) {
                if(preg_match('/(?:^|\s)btn(?:\s|$)/', $ancestor->getAttribute('class'))) $button = true;
            }
            // A standalone emphasised action is how existing broadcast drafts express their CTA.
            $emphasized = $element->getElementsByTagName('strong')->length > 0;
            $block = $parent;
            while($block instanceof \DOMElement && in_array($block->tagName, ['strong', 'b', 'span'], true)) {
                if(in_array($block->tagName, ['strong', 'b'], true)) $emphasized = true;
                $block = $block->parentNode;
            }
            if($block instanceof \DOMElement && $block->tagName === 'p' && trim($block->textContent) === trim($element->textContent) && $emphasized) $button = true;
            if(preg_match('/background(?:-color)?\s*:/i', $existing)) $button = true;
            if($button) {
                $style = rtrim($style, ';') . ';display:inline-block;box-sizing:border-box;max-width:100%;padding:14px 22px;border:1px solid #106653;border-radius:12px;background:#106653;color:#ffffff;font-size:16px;font-weight:700;line-height:1.4;text-align:center;text-decoration:none;mso-padding-alt:0;text-underline-color:#106653;';
                $element->setAttribute('class', trim($element->getAttribute('class') . ' fcc-email-button'));
            }
        }
        if($style !== '') $element->setAttribute('style', $style);
    }
    $result = '';
    foreach($body->childNodes as $child) $result .= $document->saveHTML($child);
    return $result;
}

/** Preserve paragraphs and action destinations in the plain text alternative. */
function fc_email_plain_text(string $html): string {
    $html = preg_replace('~<(head|style|script)\b[^>]*>.*?</\1>~is', '', $html);
    $html = preg_replace('~<div\b[^>]*class="fcc-email-preheader"[^>]*>.*?</div>~is', '', $html);
    $html = preg_replace_callback('~<a\b[^>]*href=([\x22\x27])(.*?)\1[^>]*>(.*?)</a>~is', static function($match) {
        $label = trim(html_entity_decode(strip_tags($match[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if($label === '' || $label === $url || !preg_match('~^(https?://|mailto:|tel:)~i', $url)) return $match[3];
        return $match[3] . ' (' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ')';
    }, $html);
    $html = preg_replace('~<br\s*/?>~i', "\n", $html);
    $html = preg_replace('~</(?:p|div|h[1-6]|tr|table|blockquote|ul|ol)>~i', "\n\n", $html);
    $html = preg_replace('~</li>~i', "\n", $html);
    $html = preg_replace('~</t[dh]>~i', "\t", $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\r", "\xc2\xa0"], ['', ' '], $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/ *\n */', "\n", $text);
    return trim(preg_replace('/\n{3,}/', "\n\n", $text));
}
