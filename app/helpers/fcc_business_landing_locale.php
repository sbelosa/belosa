<?php
/** Application copy for the administrator pilot. Member content is never translated here. */
function fcc_business_landing_locale(?string $language = null): string {
    $language = function_exists('fcc_locale') ? fcc_locale($language) : ($language ?? 'en');
    return in_array($language,fcc_supported_locales(),true)?$language:'en';
}

function fcc_business_landing_languages(): array {
    return fcc_language_labels();
}

function fcc_business_landing_catalog(string $locale='en'): array {
    static $catalog;
    if($catalog === null) {
        $path = APP_PATH . 'config/locales/business-landing.json';
        $decoded = is_file($path) ? json_decode(file_get_contents($path), true) : [];
        $catalog = is_array($decoded) ? $decoded : [];
    }
    return is_array($catalog[$locale]??null)?$catalog[$locale]:[];
}

/** Translate application copy only. Personal introductions and original videos remain untouched. */
function fcc_business_landing_translate(string $language, string $source, string $english = '', array $replace = []): string {
    $locale=fcc_business_landing_locale($language);
    if($locale==='hr') $translated=$source;
    elseif($locale==='en' && $english!=='') $translated=$english;
    else {
        $catalog=fcc_business_landing_catalog($locale);
        $translated=$catalog[$source]??fcc_t($source,[],$locale);
    }
    return $replace ? strtr($translated, $replace) : $translated;
}
