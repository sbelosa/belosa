<?php
/** Render synthetic mail only. No recipient lookup, queue changes or transport calls. */
if(PHP_SAPI !== 'cli' || getenv('FCC_LOCAL') !== '1') exit(1);
define('ALTUMCODE', 66); define('DEBUG', 0); define('MYSQL_DEBUG', 0); define('LOGGING', 1); define('CACHE', 0);
$_SERVER['HTTP_HOST'] = 'localhost'; $_SERVER['REQUEST_URI'] = '/';
require dirname(__DIR__) . '/app/init.php';
\Altum\Cache::initialize(); \Altum\Plugin::initialize(); \Altum\Language::initialize();
settings()->main->title = 'Forever Card Club';
if(DATABASE_NAME !== 'fcc_partner_local') exit(1);
set_exception_handler(function($e) { fwrite(STDERR, $e->getMessage() . PHP_EOL); exit(1); });
$checks = [];
$check = function($ok, $label) use (&$checks) { if(!$ok) throw new RuntimeException($label); $checks[] = $label; };
$directory = dirname(__DIR__) . '/local/unified-email-20261001/preview';
if(!is_dir($directory)) mkdir($directory, 0700, true);
$secretUrl = 'https://forevercard.club/reset-password?token=synthetic%2Btoken&email=test%40example.test';
$body = '<p>Pozdrav Ana,</p><p>tvoj sljedeći korak sada je jednostavniji.</p><p>Pripremi poruku, podijeli poziv i prati dogovor na jednom mjestu.</p><h2>Sve je spremno za tvoj dan</h2><ol><li>Odaberi osobu kojoj želiš ponuditi podršku.</li><li>Pripremi osobnu poruku i dogovori sljedeći korak.</li></ol><p><a href="https://forevercard.club/partner"><strong>Otvori moj FCC</strong></a></p><p>Vidimo se uskoro!<br>Stjepan Beloša<br>Forever Card Club</p>';
$unsubscribe = 'https://forevercard.club/email-unsubscribe?token=synthetic%2Bsignature&lang=hr';
foreach(['english' => 'en', 'Hrvatski' => 'hr', 'Slovenščina' => 'sl', 'Deutsch' => 'de', 'Español' => 'es'] as $language => $locale) {
    $template = process_send_mail_template('Tvoj sljedeći korak počinje ovdje', $body, ['language' => $language, 'is_system_email' => false, 'is_broadcast' => true, 'unsubscribe_url' => $unsubscribe, 'anti_phishing_code' => 'QA<123>']);
    $html = $template['email_template'];
    $dom = new DOMDocument(); @$dom->loadHTML($html); $xpath = new DOMXPath($dom);
    $check($dom->documentElement->getAttribute('lang') === $locale, 'Wrapper language ' . $locale);
    $check($xpath->query('//a[@href="' . $unsubscribe . '"]')->length === 1, 'Unsubscribe token preserved ' . $locale);
    $check(str_contains($html, 'QA&lt;123&gt;'), 'Anti phishing code escaped ' . $locale);
    $check(str_contains($template['text_template'], "Vidimo se uskoro!\nStjepan Beloša\nForever Card Club"), 'Signature keeps line breaks ' . $locale);
    $check(!str_contains($template['text_template'], '@media') && !str_contains($template['text_template'], 'mso-'), 'Plain text contains no stylesheet ' . $locale);
    $check(str_contains($template['text_template'], 'https://forevercard.club/partner'), 'Plain text preserves destination ' . $locale);
    $check($xpath->query('//a[contains(@class,"fcc-email-button")]')->length === 1, 'Broadcast action becomes styled button ' . $locale);
    $check(str_contains($html, fc_email_design_copy($language)['unsubscribe_link']), 'Footer translated ' . $locale);
    file_put_contents($directory . '/broadcast-' . $locale . '.html', $html);
}
$registration = process_send_mail_template('Potvrdi svoj račun', 'Pozdrav Ana,<br><br>još samo jedan korak i tvoj račun je spreman.<br><br><a href="' . htmlspecialchars($secretUrl) . '" class="cta">Potvrdi račun</a><br><br>Lijep pozdrav,<br>Forever Card Club', ['language' => 'Hrvatski', 'is_system_email' => true, 'unsubscribe_url' => $unsubscribe]);
$check(!str_contains($registration['email_template'], $unsubscribe), 'System messages do not acquire marketing unsubscribe links');
$dom = new DOMDocument(); @$dom->loadHTML($registration['email_template']); $xpath = new DOMXPath($dom);
$check($xpath->query('//a[@href="' . $secretUrl . '"]')->length === 1, 'Signed account URL remains byte identical after HTML parsing');
$check(str_contains($registration['email_template'], 'background:#106653'), 'Legacy CTA has inline brand style');
$check(str_contains($registration['text_template'], $secretUrl), 'Plain text includes signed account URL');
file_put_contents($directory . '/registration-hr.html', $registration['email_template']);
$webinar = ['language' => 'hr', 'text' => "Bok Ana!\n\nTvoja prijava je zaprimljena.\n\nUpoznaj Forever posao\nNedjelja, 4. listopada u 19:00\n\nPristup webinaru: https://forevercard.club/vip-edukacija\n\nDetalji i upravljanje prijavom: https://forevercard.club/webinar-invite/manage/synthetic-token\n\nPozvao/la te: Tvoj suradnik\nEmail suradnika: suradnik@example.test\n\nPrivatnost: https://forevercard.club/legal/privacy"];
$webinarHtml = process_send_mail_template('Tvoja prijava na webinar', fcc_webinar_mail_html($webinar), ['language' => 'Hrvatski', 'is_system_email' => true]);
file_put_contents($directory . '/webinar-hr.html', $webinarHtml['email_template']);
$check(str_contains($webinarHtml['email_template'], 'https://forevercard.club/vip-edukacija'), 'Canonical webinar link preserved');
$check(str_contains($webinarHtml['email_template'], 'background:#106653'), 'Webinar uses the shared button style');
$plain = fc_email_design_content("Prvi red\nDrugi red\n\nTreći red");
$check(substr_count($plain, '<br>') === 3, 'Plain text input keeps every line');
$check(fc_email_plain_text('<p>Jedan</p><p>Dva</p>') === "Jedan\n\nDva", 'Plain text preserves paragraph boundaries');
$long = process_send_mail_template(str_repeat('Dugi naslov ', 16), '<p>' . str_repeat('DugiNePrekinutiTekst', 40) . '</p><p><a class="cta" href="https://example.test/">' . str_repeat('Duga oznaka gumba ', 10) . '</a></p>', ['language' => 'Hrvatski']);
file_put_contents($directory . '/long-content.html', $long['email_template']);
$custom = fc_email_design_content('<p class="ql-align-center"><span style="color:#ff0000">Istaknuto</span></p><table width="100%"><tr><td>Račun</td><td>10 EUR</td></tr></table><img src="https://example.test/pixel" style="display:none" width="1" height="1">');
$check(str_contains($custom, 'text-align:center') && str_contains($custom, 'color:#ff0000'), 'Editor alignment and explicit text styles preserved');
$check(str_contains($custom, 'display:none') && str_contains($custom, 'width="1"'), 'Tracking pixel remains hidden');
$check(str_contains($custom, '<table') && str_contains($custom, '10 EUR'), 'Transactional table content preserved');
$check(str_contains(fc_email_design_content('<p><strong><a href="https://example.test/">Otvori FCC</a></strong></p>'), 'fcc-email-button'), 'Quill strong outside anchor retains action styling');
foreach(['broadcast-create', 'broadcast-update', 'automation-create', 'automation-update'] as $screen) {
    $source = file_get_contents(dirname(__DIR__) . '/themes/altum/views/admin/' . $screen . '/index.php');
    $check(str_contains($source, 'quill.clipboard.dangerouslyPasteHTML('), 'HTML import uses clipboard conversion: ' . $screen);
}
$report = ['status' => 'PASS', 'checks' => count($checks), 'details' => $checks];
file_put_contents(dirname($directory) . '/test-result.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
