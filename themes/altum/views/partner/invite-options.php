<?php defined('ALTUMCODE') || die();
$inv=$data->invite;$a=$inv['attempt'];$s=$webinar['session']??null;
$v=json_decode($a['video_snapshot']??'{}',true)?:[];
$choice=(string)($data->values['video_choice']??$v['choice']??'none');
$videos=(!$eventChannel&&empty($s['owner_user_id']))?array_filter(fcc_webinar_config()['official_videos'],static fn($v)=>fcc_webinar_video_url($v)!==''):[];
?>
<details class="fp-invite-options" data-invite-options <?= !empty($data->error)?'open':'' ?>>
 <summary><span><?= fcc_partner_icon('plus') ?><span><?= $h(fcc_invite_t($a?'edit':'options')) ?></span></span><span class="fp-invite-options-tail"><span class="fv-pro">PRO</span><span aria-hidden="true">⌄</span></span></summary>
 <div class="fp-invite-options-body">
 <?php if(!$inv['pro']): ?>
 <p><?= $h(fcc_invite_t('free')) ?></p><a class="fp-link" href="<?= url(fcc_pro_upgrade_path('webinar')) ?>"><?= $h(fcc_invite_t('upgrade')) ?></a>
 <?php elseif($s&&$inv['available']): ?>
 <p><?= $h(fcc_invite_t('help')) ?></p>
 <form method="post" class="fp-form" data-invite-customize>
 <input type="hidden" name="token" value="<?= $h(\Altum\Csrf::get()) ?>"><input type="hidden" name="action" value="invite_customize">
 <input type="hidden" name="request_key" value="<?= $h($data->values['request_key']??$data->form_key) ?>">
 <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="session_revision" value="<?= (int)$s['revision'] ?>">
 <input type="hidden" name="invite_channel" value="<?= $channel ?>"><input type="hidden" name="source_attempt" value="<?= (int)($a['id']??0) ?>">
 <input type="hidden" name="step_id" value="<?= (int)($data->values['step_id']??$a['journey_step_id']??$_GET['step_id']??0) ?>">
 <input type="hidden" name="message_draft" value="<?= $h($data->values['message_draft']??'') ?>">
 <label><?= $h(fcc_t('Uvod na pozivnici')) ?><select name="video_choice" data-is-not-custom-select>
 <option value="none" <?= $choice==='none'?'selected':'' ?>><?= $h(fcc_t('Bez uvodnog videa')) ?></option>
 <?php foreach($videos as $key=>$video): ?><option value="<?= $h($key) ?>" <?= $key===$choice?'selected':'' ?>><?= $h(fcc_t($video['title'])) ?></option><?php endforeach ?>
 <option value="custom" <?= $choice==='custom'?'selected':'' ?>><?= $h(fcc_t('Moj YouTube ili Vimeo video')) ?></option></select></label>
 <label data-invite-video-url><?= $h(fcc_t('Poveznica na uvodni video')) ?><input type="url" name="video_url" maxlength="1000" placeholder="https://www.youtube.com/watch?v=…" value="<?= $h($data->values['video_url']??fcc_webinar_video_url($v)) ?>"></label>
 <label><?= $h(fcc_t('Kratki uvod na pozivnici')) ?><textarea name="intro" rows="2" maxlength="400"><?= $h($data->values['intro']??$a['intro_snapshot']??'') ?></textarea></label>
 <label><?= $h(fcc_t('Osoba iz mojih kontakata')) ?><select name="contact_id" data-is-not-custom-select><option value="0"><?= $h(fcc_t('Odabrat ću osobu u WhatsAppu')) ?></option><?php foreach($inv['contacts'] as $contact): ?><option value="<?= (int)$contact['id'] ?>" <?= (int)($data->values['contact_id']??$a['recipient_contact_id']??$_GET['contact_id']??0)===(int)$contact['id']?'selected':'' ?>><?= $h($contact['name']) ?></option><?php endforeach ?></select></label>
 <button class="fp-button fp-button-outline"><?= $h(fcc_invite_t('apply')) ?></button>
 </form>
 <?php if($inv['personal_ready']): ?><a class="fp-invite-records" href="<?= url('partner-webinar?create=1') ?>"><?= $h(fcc_t('Kreiraj svoj webinar ili termin')) ?></a><?php endif ?>
 <?php endif ?>
 </div>
</details>
