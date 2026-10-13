<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-13: the team group step now lives in Postavi FCC behind the bell (setup-steps.php).
 * This include only makes sure a new member receives the welcome notice that leads there. */
$groupUid=(int)($this->user->user_id??0);
if($groupUid&&!session_has('admin_user_id')&&!\Altum\Teams::is_delegated())fcc_journey_team_welcome($groupUid);
