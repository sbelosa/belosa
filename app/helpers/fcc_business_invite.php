<?php
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-12: Pozivnica u tim.
 * Every member with an active card and Forever ID gets one permanent short link
 * (forevercard.club/poziv/<code>) to the Upoznaj poslovanje presentation, with their
 * referral built in. The link survives presentation edits; a revoked or invalid share
 * is replaced transparently. Registrations that started from the link are recorded
 * so the sponsor sees real results, and the sponsor is told when a visitor opens the
 * official Start pack order. PRO members can add a personal video and introduction. */

const FCC_INVITE_CODE_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

/* Only a positive answer is cached, so tables created later in the same request are seen. */
function fcc_invite_ready(): bool {
 static $ready = false;
 return $ready = $ready || !!fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_business_invites'");
}

function fcc_invite_personal_columns_ready(): bool {
 static $ready = false;
 return $ready = $ready || !!fcc_partner_one("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_business_presentations' AND COLUMN_NAME='personal_video_id'");
}

/** Additive schema, created on the first write. Reads stay guarded by the ready checks. */
function fcc_invite_ensure_tables(): void {
 static $done = false;
 if($done) return;
 database()->query("CREATE TABLE IF NOT EXISTS `fcc_business_invites` (
  `user_id` int unsigned NOT NULL,
  `code` char(8) NOT NULL,
  `share_id` bigint unsigned NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `fcc_invite_code` (`code`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 database()->query("CREATE TABLE IF NOT EXISTS `fcc_business_invite_registrations` (
  `user_id` int unsigned NOT NULL,
  `owner_user_id` int unsigned NOT NULL,
  `code` char(8) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `fcc_invite_owner` (`owner_user_id`,`created_at`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 if(!fcc_invite_personal_columns_ready()) {
  database()->query("ALTER TABLE `fcc_business_presentations` ADD COLUMN IF NOT EXISTS `personal_video_provider` varchar(16) NULL, ADD COLUMN IF NOT EXISTS `personal_video_id` varchar(64) NULL, ADD COLUMN IF NOT EXISTS `personal_video_hash` varchar(64) NULL");
 }
 $done = true;
}

function fcc_invite_code_valid($code): bool {
 return is_string($code) && preg_match('/^['.FCC_INVITE_CODE_ALPHABET.']{8}$/D', $code) === 1;
}

function fcc_invite_new_code(): string {
 $alphabet = FCC_INVITE_CODE_ALPHABET;
 do {
  $code = '';
  for($i = 0; $i < 8; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
 } while(fcc_partner_one('SELECT user_id FROM fcc_business_invites WHERE code=?', [$code]));
 return $code;
}

function fcc_invite_url(string $code): string {
 return SITE_URL.'poziv/'.$code;
}

function fcc_invite_default_country(int $uid): string {
 $country = strtolower((string)(fcc_partner_one('SELECT country FROM users WHERE user_id=?', [$uid])['country'] ?? ''));
 return isset(fcc_business_countries()[$country]) && $country !== 'xx' ? $country : 'hr';
}

/** A share the invitation may point to: new presentation, available and still carrying the owner's current Forever ID. */
function fcc_invite_share_usable(?array $share): bool {
 if(!$share) return false;
 $public = fcc_business_public((string)$share['public_token']);
 return $public && !empty($public['available']) && fcc_business_landing_is_new($public) && ($public['fbo_snapshot'] ?? '') === ($public['owner']['fbo'] ?? '');
}

/** Returns the member's invitation, creating or repairing it. Throws a member facing reason when it is not available. */
function fcc_invite_for_user(int $uid): array {
 if(!fcc_business_landing_access($uid)) throw new InvalidArgumentException(fcc_t('Pozivnica u tim još nije otvorena za tvoj račun.'));
 fcc_invite_ensure_tables();
 $row = fcc_partner_one('SELECT * FROM fcc_business_invites WHERE user_id=?', [$uid]);
 $share = $row && $row['share_id'] ? fcc_partner_one('SELECT * FROM fcc_business_shares WHERE id=? AND user_id=?', [(int)$row['share_id'], $uid]) : null;
 if(!fcc_invite_share_usable($share)) {
  $share = fcc_business_landing_prepare($uid, ['landing_model'=>'overview', 'country'=>fcc_invite_default_country($uid), 'language'=>fcc_business_preferences($uid)['language'], 'request_key'=>bin2hex(random_bytes(16))]);
 }
 if(!$row) {
  fcc_partner_query('INSERT IGNORE INTO fcc_business_invites (user_id,code,share_id,created_at,updated_at) VALUES (?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())', [$uid, fcc_invite_new_code(), (int)$share['id']]);
  $row = fcc_partner_one('SELECT * FROM fcc_business_invites WHERE user_id=?', [$uid]);
 }
 if((int)$row['share_id'] !== (int)$share['id']) {
  fcc_partner_query('UPDATE fcc_business_invites SET share_id=?,updated_at=UTC_TIMESTAMP() WHERE user_id=?', [(int)$share['id'], $uid]);
 }
 // The page opens in the language of the member's presentation, not the language of the request.
 $language = (string)(fcc_partner_one('SELECT language FROM fcc_business_presentations WHERE id=? AND user_id=?', [(int)$share['presentation_id'], $uid])['language'] ?? '');
 return ['code'=>$row['code'], 'url'=>fcc_invite_url($row['code']), 'share'=>$share, 'page'=>fcc_business_share_destination($share, $language !== '' ? $language : null)['url']];
}

/** Read only lookup used while rendering a presentation. */
function fcc_invite_code_for_user(int $uid): string {
 if(!fcc_invite_ready()) return '';
 return (string)(fcc_partner_one('SELECT code FROM fcc_business_invites WHERE user_id=?', [$uid])['code'] ?? '');
}

/** Public resolution of /poziv/<code>. Returns null when the invitation cannot be shown. */
function fcc_invite_public_destination(string $code, string $country = ''): ?string {
 if(!fcc_invite_ready() || !fcc_invite_code_valid($code)) return null;
 $row = fcc_partner_one('SELECT user_id FROM fcc_business_invites WHERE code=?', [$code]);
 if(!$row) return null;
 try {
  $invite = fcc_invite_for_user((int)$row['user_id']);
 } catch(InvalidArgumentException $e) {
  return null;
 }
 $url = $invite['page'];
 if($country !== '' && isset(fcc_business_countries()[$country])) $url .= '?country='.rawurlencode($country);
 return $url;
}

/** FCC registration link for the "after your order" step; it carries the invitation for attribution. */
function fcc_invite_register_url(int $ownerUid): string {
 $code = fcc_invite_code_for_user($ownerUid);
 return SITE_URL.'register'.($code !== '' ? '?inv='.$code : '');
}

function fcc_invite_remember_from_request(): void {
 try {
  $code = is_string($_GET['inv'] ?? null) ? strtolower($_GET['inv']) : '';
  if(!fcc_invite_code_valid($code) || !fcc_invite_ready()) return;
  if(fcc_partner_one('SELECT user_id FROM fcc_business_invites WHERE code=?', [$code])) $_SESSION['fcc_invite_code'] = ['code'=>$code, 'at'=>time()];
 } catch(Throwable $e) {
  error_log('Invite remember: '.get_class($e));
 }
}

/** Never blocks a registration. */
function fcc_invite_record_registration(int $newUid): void {
 try {
  $remembered = $_SESSION['fcc_invite_code'] ?? null;
  unset($_SESSION['fcc_invite_code']);
  if(!is_array($remembered) || ($remembered['at'] ?? 0) < time() - 3 * 86400 || !fcc_invite_code_valid($remembered['code'] ?? null) || !fcc_invite_ready()) return;
  $owner = fcc_partner_one('SELECT user_id FROM fcc_business_invites WHERE code=?', [$remembered['code']]);
  if(!$owner || (int)$owner['user_id'] === $newUid) return;
  fcc_invite_ensure_tables();
  $stmt = fcc_partner_query('INSERT IGNORE INTO fcc_business_invite_registrations (user_id,owner_user_id,code,created_at) VALUES (?,?,?,UTC_TIMESTAMP())', [$newUid, (int)$owner['user_id'], $remembered['code']]);
  if($stmt->affected_rows === 1) fcc_partner_notify((int)$owner['user_id'], 'invite:registration:'.$newUid, 'service', 'Nova registracija s tvoje Pozivnice u tim', 'Netko se registrirao u FCC preko tvoje pozivnice. Nakon potvrde sponzora pojavit će se u tvom timu, pa mu se javi i dogovorite prve korake.', 'partner/team', 7 * 86400);
 } catch(Throwable $e) {
  error_log('Invite registration: '.get_class($e));
 }
}

function fcc_invite_notify_order(array $share, string $country, string $visitorHash): void {
 try {
  // A fixed text, so the notification can be shown in the member's language.
  fcc_partner_notify((int)$share['user_id'], 'invite:order:'.(int)$share['id'].':'.substr($visitorHash, 0, 16).':'.gmdate('Ymd'), 'service', 'Netko je otvorio narudžbu Start paketa', 'Posjetitelj tvoje Pozivnice u tim otišao je na službenu Forever narudžbu s tvojim brojem sponzora. Ako ti se javi, pomozi mu dovršiti prijavu.', 'partner/notifications', 2 * 86400);
 } catch(Throwable $e) {
  error_log('Invite order notice: '.get_class($e));
 }
}

/** Registrations recorded for this owner and how many of those accounts are already approved. */
function fcc_invite_registrations(int $uid): array {
 if(!fcc_invite_ready()) return ['total'=>0, 'approved'=>0];
 $row = fcc_partner_one('SELECT COUNT(*) total, SUM(u.status=1) approved FROM fcc_business_invite_registrations r JOIN users u ON u.user_id=r.user_id WHERE r.owner_user_id=?', [$uid]);
 return ['total'=>(int)($row['total'] ?? 0), 'approved'=>(int)($row['approved'] ?? 0)];
}

function fcc_invite_qr_svg(string $url): string {
 try {
  $renderer = new \BaconQrCode\Renderer\ImageRenderer(new \BaconQrCode\Renderer\RendererStyle\RendererStyle(240, 1), new \BaconQrCode\Renderer\Image\SvgImageBackEnd());
  return (new \BaconQrCode\Writer($renderer))->writeString($url);
 } catch(Throwable $e) {
  error_log('Invite QR: '.get_class($e));
  return '';
 }
}

/** The PRO personal video of a presentation, shaped like an approved library asset. */
function fcc_invite_personal_video(array $share): ?array {
 if(!fcc_invite_personal_columns_ready() || !fcc_business_pro((int)$share['user_id'])) return null;
 $row = fcc_partner_one('SELECT personal_video_provider provider,personal_video_id video_id,personal_video_hash video_hash FROM fcc_business_presentations WHERE id=? AND user_id=?', [(int)$share['presentation_id'], (int)$share['user_id']]);
 if(!$row || !in_array($row['provider'], ['youtube', 'vimeo'], true) || (string)$row['video_id'] === '') return null;
 return ['provider'=>$row['provider'], 'video_id'=>$row['video_id'], 'video_hash'=>(string)$row['video_hash'], 'title'=>fcc_t('Osobni video'), 'approved'=>1, 'status'=>'published', 'is_demo'=>0, 'duration_seconds'=>0, 'summary'=>'', 'description'=>''];
}

function fcc_invite_personal_values(int $uid, array $share): array {
 $p = fcc_partner_one('SELECT intro FROM fcc_business_presentations WHERE id=? AND user_id=?', [(int)$share['presentation_id'], $uid]);
 $video = fcc_invite_personal_columns_ready() ? fcc_partner_one('SELECT personal_video_provider provider,personal_video_id id,personal_video_hash hash FROM fcc_business_presentations WHERE id=? AND user_id=?', [(int)$share['presentation_id'], $uid]) : null;
 $url = $video && $video['id'] ? fcc_webinar_video_url(['provider'=>$video['provider'], 'id'=>$video['id'], 'hash'=>(string)$video['hash']]) : '';
 return ['intro'=>(string)($p['intro'] ?? ''), 'video_url'=>$url];
}

function fcc_invite_personal_save(int $uid, array $in): void {
 if(!fcc_business_pro($uid)) throw new InvalidArgumentException(fcc_t('Osobni video i uvod dostupni su uz PRO paket.'));
 $invite = fcc_invite_for_user($uid);
 fcc_invite_ensure_tables();
 $intro = fcc_partner_text($in['intro'] ?? '', 240);
 $url = trim((string)($in['personal_video_url'] ?? ''));
 $video = ['provider'=>null, 'id'=>null, 'hash'=>null];
 if($url !== '') {
  $parsed = fcc_webinar_video('custom', $url);
  if(!in_array($parsed['provider'] ?? '', ['youtube', 'vimeo'], true) || ($parsed['id'] ?? '') === '') throw new InvalidArgumentException(fcc_t('Upiši YouTube ili Vimeo poveznicu videa.'));
  $video = ['provider'=>$parsed['provider'], 'id'=>$parsed['id'], 'hash'=>($parsed['hash'] ?? '') ?: null];
 }
 fcc_partner_query('UPDATE fcc_business_presentations SET intro=?,personal_video_provider=?,personal_video_id=?,personal_video_hash=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE id=? AND user_id=?', [$intro, $video['provider'], $video['id'], $video['hash'], (int)$invite['share']['presentation_id'], $uid]);
}
/** Start pack facts per country and until when the reviewed price and terms stay visible on the invitation. */
function fcc_invite_starter_review_rows(?int $now = null): array {
 $now ??= time();
 $records = json_decode((string)@file_get_contents(APP_PATH.'config/fcc_business_starter_details.json'), true) ?: [];
 $rows = [];
 foreach($records as $country => $facts) {
  $until = strtotime($facts['review_until'] ?? '') ?: 0;
  $checked = strtotime($facts['price']['checked_at'] ?? ($facts['reviewed_at'] ?? '')) ?: 0;
  $limits = array_filter([$until, $checked ? $checked + 30 * 86400 : 0]);
  $visible = $limits ? min($limits) : 0;
  $rows[] = ['country'=>$country, 'reviewed_at'=>(string)($facts['reviewed_at'] ?? ''), 'visible_until'=>$visible, 'days_left'=>$visible ? (int)floor(($visible - $now) / 86400) : -1];
 }
 usort($rows, static fn($a, $b) => $a['visible_until'] <=> $b['visible_until']);
 return $rows;
}

/** Weekly reminder to administrators while any country's reviewed facts expire within 7 days. */
function fcc_invite_starter_review_reminders(): int {
 $due = array_values(array_filter(fcc_invite_starter_review_rows(), static fn($row) => $row['days_left'] <= 7));
 if(!$due) return 0;
 $labels = implode(', ', array_map(static fn($row) => fcc_business_country_label($row['country'], 'hr'), $due));
 $sent = 0;
 foreach(fcc_partner_rows('SELECT user_id FROM users WHERE type=1 AND status=1') as $admin) {
  fcc_partner_notify((int)$admin['user_id'], 'invite:starter-review:'.gmdate('o-W'), 'admin', 'Provjeri cijene Start paketa', sprintf(fcc_t('Provjerena cijena i uvjeti Start paketa uskoro nestaju s Pozivnice u tim za: %s. Zatraži novu provjeru službenih stranica.'), $labels), 'partner/admin#invite-admin', 7 * 86400);
  $sent++;
 }
 return $sent;
}

function fcc_invite_admin_summary(): array {
 if(!fcc_invite_ready()) return ['invites'=>0, 'registrations'=>0, 'approved'=>0];
 $invites = (int)(fcc_partner_one('SELECT COUNT(*) n FROM fcc_business_invites')['n'] ?? 0);
 $regs = fcc_partner_one('SELECT COUNT(*) total, SUM(u.status=1) approved FROM fcc_business_invite_registrations r JOIN users u ON u.user_id=r.user_id');
 return ['invites'=>$invites, 'registrations'=>(int)($regs['total'] ?? 0), 'approved'=>(int)($regs['approved'] ?? 0)];
}
/* /Custom code: FC-2026-10-12 */
