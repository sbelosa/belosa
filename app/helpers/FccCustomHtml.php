<?php
namespace Altum\Helpers;

defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-16: preserve and validate complete custom HTML blocks. */
final class FccCustomHtml {
    public const MAX_CHARACTERS = 50000;
    public const MAX_SETTINGS_BYTES = 1048576;

    public static function validate($value): string {
        if(!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
            throw new \InvalidArgumentException('invalid');
        }
        // Browsers use LF in textareas and CRLF in multipart form submissions.
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        if(mb_strlen($value, 'UTF-8') > self::MAX_CHARACTERS) {
            throw new \InvalidArgumentException('length');
        }
        return $value;
    }

    public static function encode(array $settings): string {
        try {
            $json = json_encode($settings, JSON_THROW_ON_ERROR);
        } catch(\JsonException $exception) {
            throw new \InvalidArgumentException('invalid', 0, $exception);
        }
        if(strlen($json) > self::MAX_SETTINGS_BYTES) {
            throw new \InvalidArgumentException('bytes');
        }
        return $json;
    }

    public static function message(string $key): string {
        $messages = [
            'en' => [
                'length' => 'The HTML block can contain up to 50,000 characters. Shorten the code and try again. Your input has not been cut.',
                'bytes' => 'The HTML block and display settings are too large. Reduce the content and try again.',
                'invalid' => 'The HTML block contains invalid text. Check the code and try again.',
                'save' => 'The HTML block could not be saved. Your previous content is unchanged. Please try again.',
                'hint' => 'Up to 50,000 characters. Complex emoji can count as several characters.',
            ],
            'hr' => [
                'length' => 'HTML blok može sadržavati najviše 50.000 znakova. Skratite kod i pokušajte ponovno. Unos nije odrezan.',
                'bytes' => 'HTML blok i postavke prikaza zauzimaju previše prostora. Smanjite sadržaj i pokušajte ponovno.',
                'invalid' => 'HTML blok sadrži neispravan tekst. Provjerite kod i pokušajte ponovno.',
                'save' => 'HTML blok nije spremljen. Prethodni sadržaj ostao je sačuvan. Pokušajte ponovno.',
                'hint' => 'Najviše 50.000 znakova. Složeni emoji mogu se brojiti kao više znakova.',
            ],
            'sl' => [
                'length' => 'Blok HTML lahko vsebuje največ 50.000 znakov. Skrajšajte kodo in poskusite znova. Vnos ni odrezan.',
                'bytes' => 'Blok HTML in nastavitve prikaza zavzamejo preveč prostora. Zmanjšajte vsebino in poskusite znova.',
                'invalid' => 'Blok HTML vsebuje neveljavno besedilo. Preverite kodo in poskusite znova.',
                'save' => 'Bloka HTML ni bilo mogoče shraniti. Prejšnja vsebina je ohranjena. Poskusite znova.',
                'hint' => 'Največ 50.000 znakov. Sestavljeni emodžiji se lahko štejejo kot več znakov.',
            ],
            'de' => [
                'length' => 'Der HTML Block darf bis zu 50.000 Zeichen enthalten. Kürzen Sie den Code und versuchen Sie es erneut. Die Eingabe wurde nicht abgeschnitten.',
                'bytes' => 'Der HTML Block und die Anzeigeeinstellungen sind zu groß. Reduzieren Sie den Inhalt und versuchen Sie es erneut.',
                'invalid' => 'Der HTML Block enthält ungültigen Text. Prüfen Sie den Code und versuchen Sie es erneut.',
                'save' => 'Der HTML Block konnte nicht gespeichert werden. Der bisherige Inhalt bleibt erhalten. Versuchen Sie es erneut.',
                'hint' => 'Bis zu 50.000 Zeichen. Zusammengesetzte Emojis können als mehrere Zeichen zählen.',
            ],
            'es' => [
                'length' => 'El bloque HTML admite hasta 50.000 caracteres. Reduzca el código e inténtelo de nuevo. El contenido no se ha recortado.',
                'bytes' => 'El bloque HTML y los ajustes de visualización ocupan demasiado espacio. Reduzca el contenido e inténtelo de nuevo.',
                'invalid' => 'El bloque HTML contiene texto no válido. Revise el código e inténtelo de nuevo.',
                'save' => 'No se ha podido guardar el bloque HTML. El contenido anterior se conserva. Inténtelo de nuevo.',
                'hint' => 'Hasta 50.000 caracteres. Los emojis complejos pueden contar como varios caracteres.',
            ],
        ];
        $locale = \Altum\Language::$code ?? 'en';
        return $messages[$locale][$key] ?? $messages['en'][$key] ?? $messages['en']['invalid'];
    }
}
/* /Custom code: FC-2026-10-16 */
