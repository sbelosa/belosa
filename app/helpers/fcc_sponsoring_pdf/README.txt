FCC personal sponsoring PDF, 14 October 2026

The generator uses a fixed two page A4 layout and a small local PDF 1.4 writer.
There is no HTML renderer, remote image download, shell command, temporary file or PDF library dependency at request time.
Required existing PHP extensions are mbstring and zlib. The existing BaconQrCode application autoloader supplies the QR encoder. GD is optional for local avatar thumbnails. Missing GD safely uses initials.

The QR is vector artwork with four modules of quiet space and M error correction. It encodes the exact personal presentation URL, including its locale. All visible action buttons also have URI annotations.

Only public fields from the canonical sponsoring model are rendered. The optional avatar_path is a server only value. The generator independently checks realpath containment inside uploads/avatars, a five MiB file limit, allowed raster types and an eight megapixel limit. It never opens an avatar URL. No private account email is accepted or printed.

Fonts are DM Sans, licensed under the included SIL Open Font License 1.1. The original variable TTF was retrieved from the official Google Fonts repository:
https://github.com/google/fonts/blob/main/ofl/dmsans/DMSans%5Bopsz%2Cwght%5D.ttf
Original SHA256: 8cd08d97e89c24d0aa92edd2f0f4c8ee6195eee9b7c9f154865a58b02f0c1c0d

The bundled regular and bold TTF instances were generated with fontTools 4.60.1 instantiateVariableFont at opsz 14, weights 400 and 700. Their JSON sidecars contain glyph identifiers and metrics scaled to 1000 units. The runtime embeds the font programs, a CID to glyph map, widths and a ToUnicode CMap. Croatian, Slovenian, German and Spanish glyphs are preserved as Unicode text, not transliterated.

Production entry point: fcc_sponsoring_pdf(array $sp): string.
Model: locale, url and sponsor name are required. The sponsor object may contain headline, phone, Forever ID as a string, initials, card_url, shop_url and avatar_path. contact.whatsapp_url is optional. Missing public phone leaves the line out. Missing WhatsApp points the action back to the personal presentation contact section.

Offline checks:
php scripts/test_fcc_sponsoring_pdf.php
php scripts/render_fcc_sponsoring_pdf.php /absolute/specimen/directory
For an isolated source checkout use FCC_PDF_VENDOR_AUTOLOAD to point at an existing trusted vendor/autoload.php.
Render specimens with Poppler and inspect both A4 pages. Extract with pypdf to verify text and URI annotations. No application database or production services are used by these scripts.
