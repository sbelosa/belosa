<?php defined('ALTUMCODE') || die();
$h='fcc_partner_h';$t='fcc_mw_t';$s=$data->session;$series=$data->series;$v=$data->values;$base=url('partner-webinar?view=manage'.($s?'&session_id='.(int)$s['id']:''));
$upcoming=!$s||$s['starts_at_utc']>gmdate('Y-m-d H:i:s');$editable=$upcoming&&$data->pro;
$token='<input type="hidden" name="token" value="'.$h(\Altum\Csrf::get()).'">';
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-partner-share.css?v='.filemtime(ASSETS_PATH.'css/fcc-partner-share.css').'">','head');
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-webinar.css?v='.filemtime(ASSETS_PATH.'css/fcc-webinar.css').'">','head');
?>
<div class="fp-page fp-share-page fp-webinar-invite fv-workspace">
 <header class="fp-invite-heading"><a class="fp-link" href="<?= url('partner-webinar?view=events') ?>"><?= $h($t('heading')) ?></a><h1><?= $h($t($s?'edit':'new')) ?></h1><p><?= $h($t('intro')) ?></p></header>
 <?php if($data->error): ?><div class="fp-message fp-error" role="alert"><?= $h($data->error) ?></div><?php endif ?>
 <?php if(isset($_GET['saved'])): ?><p class="fv-notice" role="status"><?= $h($t('saved')) ?></p><?php endif ?>
 <div class="fp-invite-layout fv-personal-editor">
  <section class="fp-invite-event fv-intro-card"><span class="fp-invite-online">FOREVER CARD CLUB</span><h2><?= $h($s?$s['title']:$t('heading')) ?></h2><p><?= $h($t('scope_help')) ?></p><p><?= $h($t('language_help')) ?></p>
   <?php if($s): ?><div class="fv-event-time"><strong><?= $h(fcc_webinar_label_time($s)) ?></strong><small><?= $h($t('status_'.$s['status'])) ?> · <?= (int)$data->count ?> <?= $h(fcc_t('prijava')) ?></small></div><p><?= $h($t('privacy')) ?></p>
   <?php if($upcoming&&$s['status']==='scheduled'): ?><a class="fp-button fp-button-light" href="<?= url('partner/share?type=webinar&session_id='.(int)$s['id']) ?>"><?= $h(fcc_t('Pripremi pozivnicu')) ?></a><?php endif ?>
   <?php endif ?>
  </section>
  <section class="fp-invite-compose fv-config">
   <?php if(!$upcoming): ?><p class="fv-notice"><?= $h($t('past')) ?></p><?php elseif(!$data->pro): ?><p class="fv-notice"><?= $h(fcc_t('Za uređivanje i ponovno otvaranje potreban je aktivni PRO paket. Webinar i dalje možeš ukloniti.')) ?></p><?php endif ?>
   <form method="post" action="<?= $h($base) ?>" class="fp-form">
    <?= $token ?><input type="hidden" name="session_id" value="<?= (int)($s['id']??0) ?>"><input type="hidden" name="revision" value="<?= (int)($v['revision']??$s['revision']??0) ?>"><input type="hidden" name="series_revision" value="<?= (int)($series['revision']??0) ?>"><input type="hidden" name="request_key" value="<?= $h($v['request_key']??'') ?>">
    <fieldset class="fv-edit-fields fv-personal-fields" <?= !$editable?'disabled':'' ?>>
     <label><?= $h(fcc_t('Naziv webinara')) ?><input name="custom_title" maxlength="180" required value="<?= $h($v['custom_title']??'') ?>"></label>
     <label><?= $h(fcc_t('Što će gosti saznati?')) ?><textarea name="custom_description" rows="3" maxlength="1600"><?= $h($v['custom_description']??'') ?></textarea></label>
     <label><?= $h($t('scope')) ?><select name="audience_scope" data-is-not-custom-select <?= $s?'disabled':'' ?>><?php foreach(['personal','team'] as $scope): ?><option value="<?= $scope ?>" <?= ($series['audience_scope']??$v['audience_scope']??'personal')===$scope?'selected':'' ?>><?= $h($t($scope)) ?></option><?php endforeach ?></select></label>
     <?php if($s): ?><small class="fp-muted"><?= $h($t('locked')) ?></small><?php endif ?>
     <label><?= $h($t('language')) ?><select name="language" data-is-not-custom-select><?php foreach(fcc_mw_languages() as $code=>$label): ?><option value="<?= $code ?>" <?= ($v['language']??'en')===$code?'selected':'' ?>><?= $h($label) ?></option><?php endforeach ?></select></label>
     <div class="fv-field-pair"><label><?= $h(fcc_t('Datum')) ?><input type="date" name="custom_date" required value="<?= $h($v['custom_date']??'') ?>"></label><label><?= $h(fcc_t('Vrijeme početka')) ?><input type="time" name="custom_time" required value="<?= $h($v['custom_time']??'20:00') ?>"></label></div>
     <label><?= $h(fcc_t('Vremenska zona')) ?><select name="custom_timezone" data-is-not-custom-select><?php foreach(DateTimeZone::listIdentifiers() as $zone): ?><option value="<?= $h($zone) ?>" <?= ($v['custom_timezone']??'Europe/Ljubljana')===$zone?'selected':'' ?>><?= $h($zone) ?></option><?php endforeach ?></select></label>
     <label><?= $h(fcc_t('Trajanje u minutama')) ?><input type="number" name="custom_duration" min="5" max="480" required value="<?= (int)($v['custom_duration']??60) ?>"></label>
     <label><?= $h(fcc_t('Poveznica za ulazak na webinar')) ?><input type="url" name="custom_access_url" maxlength="1000" placeholder="https://zoom.us/j/…" required value="<?= $h($v['custom_access_url']??'') ?>"></label>
     <label><?= $h($t('repeat')) ?><select name="repeat" data-is-not-custom-select><?php foreach(['once','weekly'] as $repeat): ?><option value="<?= $repeat ?>" <?= ($v['repeat']??'once')===$repeat?'selected':'' ?>><?= $h($t($repeat)) ?></option><?php endforeach ?></select></label>
     <label><?= $h($t('until')) ?><input type="date" name="until" value="<?= $h($v['until']??'') ?>"></label>
     <?php if(!$s): ?><label class="fv-check"><input type="checkbox" name="announce" value="1" <?= !empty($v['announce'])?'checked':'' ?>><span><?= $h($t('announce')) ?></span></label><?php else: ?><input type="hidden" name="announce" value="<?= (int)($v['announce']??0) ?>"><?php endif ?>
    </fieldset>
    <?php if($s&&$upcoming): ?><label><?= $h($t('change_scope')) ?><select name="scope" data-is-not-custom-select><option value="one"><?= $h($t('one')) ?></option><option value="future" <?= ($v['scope']??'')==='future'?'selected':'' ?>><?= $h($t('future')) ?></option></select></label><label><?= $h($t('reason')) ?><input name="reason" maxlength="400" value="<?= $h($v['reason']??'') ?>"></label><?php endif ?>
    <?php if($editable): ?><button class="fp-button" name="action" value="managed_save"><?= $h($t('save')) ?></button><?php endif ?>
    <?php if($s&&$upcoming&&$s['status']!=='cancelled'): ?><details class="fv-details"><summary><?= $h($t('cancel')) ?></summary><label class="fv-check"><input type="checkbox" name="confirm_cancel" value="1"><span><?= $h($t('confirm')) ?></span></label><button class="fp-button fp-button-outline" name="action" value="managed_cancel" formnovalidate><?= $h($t('cancel')) ?></button></details><?php endif ?>
   </form>
  </section>
 </div>
 <?php if($data->slots): ?><section class="fp-panel"><h2><?= $h($t('slots')) ?></h2><?php foreach($data->slots as $slot): ?><a class="fv-personal-row" href="<?= url('partner-webinar?view=manage&session_id='.(int)$slot['id']) ?>"><span><?= $h(fcc_webinar_label_time($slot)) ?></span><span class="fv-badge"><?= $h($t('status_'.$slot['status'])) ?></span></a><?php endforeach ?></section><?php endif ?>
</div>
