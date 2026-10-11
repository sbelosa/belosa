<?php
defined('ALTUMCODE') || die();

const FCC_HELP_COACH_VERSION = 'help-coach-2026-10-05.1';

/** A guide is usable only when its published source matches this exact release. */
function fcc_help_coach_article_valid(array $article, string $locale): bool {
    $version = (string) fcc_help_meta()['version'];
    return ($article['status'] ?? '') === 'published'
        && ($article['version'] ?? '') === $version
        && ($article['source_version'] ?? '') === $version
        && ($article['locale'] ?? '') === $locale
        && ($article['content_locale'] ?? '') === $locale
        && is_string($article['title'] ?? null)
        && is_string($article['summary'] ?? null)
        && !empty($article['sections']);
}

/** Actual questions, never elapsed time or lines from a joined retrieval string. */
function fcc_help_coach_repeated(string $question, array $context): bool {
    if (($context['help_signals']['repeated_question'] ?? false) === true) return true;
    $current = fcc_help_normalize($question);
    if ($current === '') return false;
    $ids = [];
    foreach ($context['help_user_messages'] ?? [] as $row) {
        $row = (array) $row;
        $id = $row['message_id'] ?? $row['fcc_ai_message_id'] ?? null;
        if (($row['role'] ?? '') !== 'user' || !is_numeric($id) || (int) $id <= 0) continue;
        if (fcc_help_normalize((string) ($row['content'] ?? '')) === $current) $ids[(int) $id] = true;
    }
    return count($ids) >= 2;
}

function fcc_help_coach_explicit(string $question, array $context): bool {
    if (($context['help_signals']['explicit_help'] ?? false) === true) return true;
    $text = fcc_help_normalize($question);
    if (fcc_help_locale() === 'sq' && preg_match('/^(?:si|ku)\b/u', $text)) return true;
    return (bool) preg_match('/\b(?:pomoz\p{L}*|pomoc\p{L}*|objasni\p{L}*|pojasni\p{L}*|uput\p{L}*|kako|gdje|gde|zapinj\p{L}*|help|explain\p{L}*|how|where|guide\p{L}*|stuck|razloz\p{L}*|pojasn\p{L}*|kje|navodil\p{L}*|hilfe|erklar\p{L}*|anleit\p{L}*|wie|wo|ayuda|explica\p{L}*|como|donde|guia|aide|expliq\p{L}*|comment|ou|ndihm\p{L}*|shpjeg\p{L}*|udhezim\p{L}*|hjalp\p{L}*|forklara\p{L}*|hur|var|segit\p{L}*|magyaraz\p{L}*|hogyan|hol|pomoz\p{L}*|wyjasni\p{L}*|jak|gdzie|ajut\p{L}*|explica\p{L}*|cum|unde)\b|ne razum|nije mi jasno|ne radi|ne mogu|not working|do not understand|cannot|versteh|nuk kupt|nuk funksion|fungerar inte|nem mukodik|nie dziala|nu functioneaza/u', $text);
}

/** Small semantic hints choose real sections, rather than constructing new URLs. */
function fcc_help_coach_section_hints(string $text): array {
    $hints = [];
    if (preg_match('/\bkart\p{L}*|\bcard\b|\bcarte\b|tarjet/u', $text)
        && preg_match('/uredi|uredim|ureja|edit|bearbeit|modifi|ndrysh|redakt/u', $text)
        && !preg_match('/credit|kredit|banc|bankov|payment card/u', $text)) $hints['card'] = 'steps';
    if (preg_match('/nov\p{L}* termin|sljedec\p{L}* (?:termin|webinar)|sledec\p{L}* (?:termin|vebinar)|ponovn\p{L}* poz|next (?:date|webinar|session)|invite again|naslednj\p{L}* (?:termin|webinar)|nov\p{L}* datum|nachst\p{L}* (?:termin|webinar)|wieder einlad|proxim\p{L}* (?:fecha|webinar)|volver.*invit|prochain\p{L}* (?:date|webinaire)|nouvelle date|termin\p{L}* (?:te )?ardhsh\p{L}*|ftoj perseri|nasta (?:datum|webbinarium)|kovetkezo (?:idopont|webinar)|nastepn\p{L}* (?:termin|webinar)|urmator\p{L}* (?:data|webinar)/u', $text)) $hints['webinar-invite'] = 'next-date';
    elseif (preg_match('/nije (?:bio|dosao)|ni (?:bil|prisostvoval)|prisustv|prisostv|dolazak|no show|attendance|did not attend|nicht teilgen|anwesen|asistencia|no asist|presence|pas venu|nuk (?:erdhi|mori pjese)|narvaro|deltog inte|reszvetel|nem vett reszt|obecnosc|nie uczestnicz|prezenta|nu a participat/u', $text)) $hints['webinar-invite'] = 'attendance';
    if (preg_match('/(?:synlig|lathat|widoczn|vizibil|vidljiv|tko vidi|ko vidi|timsk|sponzor|sponsor|publika|audience|visibility|team webinar|wer.*sieht|sichtbar|kdo vidi|timski|ekipni|visibilidad|quien.*ve|visibilite|qui.*voit|kush.*sheh|dukshmer|ekip\p{L}*).*webinar|webinar.*(?:synlig|lathat|widoczn|vizibil|vidljiv|tko vidi|ko vidi|timsk|sponzor|sponsor|publika|audience|visibility|team|wer.*sieht|sichtbar|kdo vidi|ekipni|visibilidad|quien.*ve|visibilite|qui.*voit|kush.*sheh|dukshmer|ekip)/u', $text)) $hints['webinar-host'] = 'visibility';
    elseif (preg_match('/ponavlj\p{L}*|recurr\p{L}*|repeat\p{L}*|ponavljaj\p{L}*|wiederhol\p{L}*|repeticion|recurrent|perserit|upprep|ismetl|powtarz|recurent/u', $text) && preg_match('/webinar|webinaire|vebinar/u', $text)) $hints['webinar-host'] = 'recurrence';
    if (preg_match('/shop|webshop|trgovin|tienda|boutique|dyqan|butik|aruhaz|sklep|magazin/u', $text) && preg_match('/zemlj|drzav|country|countries|market|land|staat|pays|pais|shtet|orszag|kraj|tara|titan|bih|alban/u', $text)) $hints['shop'] = 'country';
    if (preg_match('/poruk|message|sporocil|nachricht|mensaje|mesazh|meddelande|uzenet|wiadomosc|mesaj/u', $text) && preg_match('/poslan|slanje|potvrd|sent|confirm|gesendet|bestat|envia|envoye|dergu|potrdi|skickat|elkuld|wyslan|trimis/u', $text)) $hints['team-outreach'] = 'sent';
    if (preg_match('/prezentacij|presentation|ppt|powerpoint|presentacion|presentim|prezentacja|prezentare|bemutato/u', $text)) {
        if (preg_match('/preuz|download|herunterlad|descarg|telecharg|shkark|prenos|ladda ner|letolt|pobierz|descarc/u', $text)) $hints['presentation'] = 'downloads';
        elseif (preg_match('/prilagod|predavac|uvod|custom|presenter|intro|anpass|vortrag|personalis|prezantu|anpassa|testreszab|dostosuj|personaliz/u', $text)) $hints['presentation'] = 'customize';
    }
    return $hints;
}

/** Bounded public source text from one verified section, never the user's query. */
function fcc_help_coach_section_content(array $part): array {
    $out = ['paragraphs' => [], 'steps' => [], 'questions' => [], 'truncated' => false];
    $remaining = 3500;
    $take = static function(string $value) use (&$remaining, &$out): string {
        $value = trim($value);
        $length = mb_strlen($value);
        if ($length > $remaining) { $out['truncated'] = true; return ''; }
        $remaining -= $length;
        return $value;
    };
    foreach (['paragraphs', 'steps'] as $key) foreach ($part[$key] ?? [] as $value) {
        $text = $take((string) $value);
        if ($text !== '') $out[$key][] = $text;
    }
    foreach ($part['questions'] ?? [] as $question) {
        $q = (string) ($question['question'] ?? '');
        $a = (string) ($question['answer'] ?? '');
        if (mb_strlen($q) + mb_strlen($a) > $remaining) { $out['truncated'] = true; continue; }
        $out['questions'][] = ['question' => $take($q), 'answer' => $take($a)];
    }
    return $out;
}

/** One public guide packet, without query text, guest identities or CRM notes. */
function fcc_help_coach_context(int $uid, string $text, array $context = []): array {
    $out = ['enabled' => false, 'version' => FCC_HELP_COACH_VERSION, 'mode' => 'none',
        'max_recommendations' => 1, 'recommendation' => null, 'reading_is_task_completion' => false];
    if ($uid <= 0 || !function_exists('fcc_help_enabled') || !fcc_help_enabled()) return $out;
    $out['enabled'] = true;
    $locale = fcc_help_locale();
    $question = is_string($context['last_user_message'] ?? null) ? $context['last_user_message'] : $text;
    $question = mb_substr(trim($question), 0, 4000);
    $explicit = fcc_help_coach_explicit($question, $context);
    $repeated = fcc_help_coach_repeated($question, $context);
    // A contextual offer requires a deliberate server signal with a useful reason.
    $contextual = ($context['help_signals']['offer_guide'] ?? false) === true;
    if (!$explicit && !$repeated && !$contextual) return $out;
    $out['mode'] = $explicit ? 'explicit' : ($repeated ? 'repeated' : 'contextual');
    $normalized = fcc_help_normalize($question);
    $hints = fcc_help_coach_section_hints($normalized);
    $selected = is_string($context['help_article'] ?? null) ? $context['help_article'] : '';
    $section = is_string($context['help_section'] ?? null) ? $context['help_section'] : '';
    $uiAction = (bool) preg_match('/fcc|\bkart\p{L}*|\bcard\b|\bcarte\b|tarjet|webinar|vebinar|webinaire|kontak|contact|\bstik\p{L}*|coach|notifik|notific|obavijest|obavjest|obavesten|obvest|njoft|benachrichtig|notifications|forever id|podijel|delit|share|kopir|copy|preuz|download|herunterlad|descarg|telecharg|shkark|prenos|registr|prijav|login|sign in|anmeld|connexion|autentik|kort|nevjegy|karta|card|meghivo|zaprosz|invitati|hjalp|segit|pomoz|ajut/u', $normalized);
    $candidates = [];
    foreach (fcc_help_manifest() as $id => $meta) {
        $article = fcc_help_article($id, $locale);
        if (!$article || !fcc_help_coach_article_valid($article, $locale)) continue;
        $score = 0;
        $title = fcc_help_normalize($article['title']);
        $titleMatch = $title !== '' && str_contains($normalized, $title);
        if ($titleMatch) $score += 1000;
        foreach ($article['keywords'] ?? [] as $keyword) {
            $word = fcc_help_normalize((string) $keyword);
            if (mb_strlen($word) < 3) continue;
            $stem = mb_strlen($word) >= 4 ? preg_replace('/[aeiou]$/u', '', $word) : $word;
            if (preg_match('/(?<!\p{L})'.preg_quote($stem, '/').'\p{L}*(?!\p{L})/u', $normalized)) $score += 10 + min(20, mb_strlen($word));
        }
        if (isset($hints[$id])) $score += 150;
        if ($selected === $id) $score += 2000;
        // Product use and ingredient questions need product facts, not a sharing guide.
        $topicUi = $uiAction;
        if (in_array($id, ['products', 'business'], true)) $topicUi = (bool) preg_match('/podijel|delit|share|kopir|copy|teilen|compart|partag|shpernd|poveznic|link|referal|referral|dela|megoszt|udostepn|distribui/u', $normalized);
        if ($id === 'card' && !$titleMatch && $selected !== $id && preg_match('/credit|kredit|banc|bankov|payment card/u', $normalized)) continue;
        if (!$titleMatch && $selected !== $id && !isset($hints[$id]) && (!$topicUi || $score < 15)) continue;
        if ($score > 0) $candidates[] = ['score' => $score, 'article' => $article];
    }
    usort($candidates, static fn($a, $b) => $b['score'] <=> $a['score']);
    $state = fcc_help_state($uid);
    foreach ($candidates as $candidate) {
        $article = $candidate['article'];
        $id = $article['id'];
        if (!$explicit && (isset($state['read'][$id]) || isset($state['dismissed'][$id])
            || strtotime((string) ($state['later'][$id] ?? '1970-01-01')) > time())) return $out;
        $anchor = $selected === $id ? $section : '';
        $valid = array_column($article['sections'], 'id');
        if (!in_array($anchor, $valid, true)) $anchor = $hints[$id] ?? '';
        // A localized button prompt contains the exact localized section title.
        foreach ($article['sections'] as $part) {
            $partTitle = fcc_help_normalize((string) $part['title']);
            if ($partTitle !== '' && str_contains($normalized, $partTitle)) { $anchor = $part['id']; break; }
        }
        if (!in_array($anchor, $valid, true)) $anchor = 'steps';
        if (!in_array($anchor, $valid, true)) $anchor = $valid[0] ?? '';
        $part = array_values(array_filter($article['sections'], static fn($row) => $row['id'] === $anchor))[0] ?? null;
        if (!$part) continue;
        $out['recommendation'] = [
            'id' => $id, 'title' => $article['title'], 'summary' => $article['summary'],
            'anchor' => $anchor, 'section_title' => $part['title'], 'url' => fcc_help_url($id, $anchor),
            'section' => fcc_help_coach_section_content($part),
            'reason' => $explicit ? 'explicit_help_request' : ($repeated ? 'separate_repeated_user_questions' : 'deliberate_contextual_offer'),
            'source_version' => $article['source_version'], 'version' => $article['version'],
            'locale' => $article['locale'], 'requested_locale' => $article['requested_locale'],
            'read' => isset($state['read'][$id]),
        ];
        break;
    }
    return $out;
}

function fcc_help_coach_rules(): string {
    return 'Centar Upute za FCC koristi samo help_center.recommendation iz server konteksta. Za pojašnjenje upotrijebi provjereni title, summary i section tekst, bez pretpostavljanja izostavljenih dijelova. Najprije odgovori na pitanje, a zatim po potrebi dodaj najviše jednu korisnu poveznicu i kratko objasni zašto taj odjeljak pomaže. Prepiši točan url iz descriptor polja url, bez generiranja putanje, kotve, parametara ili drugog vodiča. Ako recommendation nema vrijednost ili enabled nije true, nemoj nuditi poveznicu centra. Poveznica i objašnjenje pripadaju SAMO guidance. Nikada ih nemoj dodavati u recipient_message namijenjen drugoj osobi. Pročitana uputa nije dovršen poslovni zadatak. Ne zaključuj da osoba zapinje iz neaktivnosti; ponavljanje postoji samo uz stvarni server signal ili odvojene korisničke upite. Jezik upute opisuje jezik prikaza računa, ne tržište, zemlju, referal ili dodatna prava Free i PRO paketa.';
}

function fcc_help_coach_is_help_url(string $url): bool {
    $path = '/'.ltrim(rawurldecode((string) parse_url($url, PHP_URL_PATH)), '/');
    return (bool) preg_match('~/(?:[a-z]{2,3}/)?partner/help(?:/|$)~i', $path);
}

/** A model can omit a suggestion, but cannot substitute another guide or URL. */
function fcc_help_coach_allowed_url(array $context): string {
    if (!function_exists('fcc_help_enabled') || !fcc_help_enabled()) return '';
    $help = $context['help_center'] ?? [];
    $row = $help['recommendation'] ?? null;
    if (($help['enabled'] ?? false) !== true || !is_array($row)) return '';
    $id = (string) ($row['id'] ?? '');
    $locale = fcc_help_locale();
    $article = fcc_help_article($id, $locale);
    if (!$article || !fcc_help_coach_article_valid($article, $locale)) return '';
    if (($row['version'] ?? '') !== $article['version'] || ($row['source_version'] ?? '') !== $article['source_version']) return '';
    $anchor = (string) ($row['anchor'] ?? '');
    if (!in_array($anchor, array_column($article['sections'], 'id'), true)) return '';
    $expected = fcc_help_url($id, $anchor);
    return ($row['url'] ?? '') === $expected ? $expected : '';
}

/** Remove unsafe help links without discarding an otherwise useful Coach answer. */
function fcc_help_coach_sanitize_reply(string $guidance, string $recipient, array $context): array {
    $allowed = fcc_help_coach_allowed_url($context);
    $kept = 0;
    $pattern = '~\[[^\]\r\n]*\]\([^)\r\n]+\)|https?://[^\s<>"\]\)]+|/[^\s<>"\]\)]*|(?<![\p{L}\p{N}])(?:[a-z]{2,3}/)?partner/help[^\s<>"\]\)]*~u';
    $filter = static function(string $text, bool $isRecipient) use ($pattern, $allowed, &$kept): string {
        $text = preg_replace_callback($pattern, static function($match) use ($allowed, $isRecipient, &$kept) {
            $token = $match[0];
            $url = $token;
            if (preg_match('~^\[[^\]]*\]\(([^)]+)\)$~u', $token, $parts)) $url = $parts[1];
            $url = rtrim($url, '.,;:!?}');
            if (!fcc_help_coach_is_help_url($url)) return $token;
            if (!$isRecipient && $allowed !== '' && $url === $allowed && $kept === 0) { $kept++; return $token; }
            return '';
        }, $text);
        return trim(preg_replace('/[ \t]{2,}/u', ' ', $text));
    };
    return ['guidance' => $filter($guidance, false), 'recipient_message' => $filter($recipient, true)];
}
