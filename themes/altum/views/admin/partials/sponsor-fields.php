<?php
defined('ALTUMCODE') || die();
if(fcc_team_ready()):
$teamSubjectId=(int)$teamSubjectId;
$tm=fcc_team_record($teamSubjectId);
$ts=fcc_team_suggestion($teamSubjectId);
$selected=(int)($tm['sponsor_user_id']??(($ts&&$ts['fresh']&&count($ts['accounts'])===1)?$ts['accounts'][0]['user_id']:0));
$teamInputId='team-sponsor-'.$teamSubjectId;
$teamGraph=fcc_team_graph();
$teamPrimary=fcc_team_primary($teamSubjectId,$teamGraph);
if($teamPrimary&&$teamPrimary!==$teamSubjectId)$selected=(int)($teamGraph[$teamPrimary]['sponsor_user_id']??0);
$registrationContact=fcc_team_registration_contact_available($teamSubjectId,$tm)?fcc_team_registration_phone($teamGraph[$teamSubjectId]??[]):'';
$teamPhone=($tm['phone']??'')?:$registrationContact;
?>
<fieldset class="fcc-sponsor-fields" style="min-width:0;margin:1rem 0;padding:1rem;border:1px solid #91aaa0;border-radius:12px">
 <legend style="width:auto;font-size:1rem;padding:0 .4rem"><?= htmlspecialchars(fcc_t('Sponzor i timska linija'), ENT_QUOTES, 'UTF-8') ?></legend>
 <input type="hidden" name="team_form" value="1">
 <input type="hidden" name="team_version" value="<?= (int)($tm['version']??0) ?>">
 <label for="<?= $teamInputId ?>"><?= htmlspecialchars(fcc_t('Odaberi izravnog sponzora'), ENT_QUOTES, 'UTF-8') ?></label>
 <select id="<?= $teamInputId ?>" class="form-control" name="team_sponsor_id" data-is-not-custom-select>
  <option value="0"><?= htmlspecialchars(fcc_t('Bez povezanog sponzora'), ENT_QUOTES, 'UTF-8') ?></option>
  <?php foreach(fcc_team_options($teamSubjectId) as $option): ?>
   <option value="<?= (int)$option['user_id'] ?>" <?= $selected===(int)$option['user_id']?'selected':'' ?> <?= !$option['fbo_id']?'disabled':'' ?>><?= fcc_partner_h($option['name'].' · '.$option['email'].fcc_t(' · FCC #').$option['user_id'].' · '.($option['fbo_id']?:fcc_t('Forever ID nije upisan'))) ?></option>
  <?php endforeach ?>
 </select>
 <?php if($ts): ?><p class="small mt-2"><?= htmlspecialchars(fcc_t('FLP prijedlog:'), ENT_QUOTES, 'UTF-8') ?> <strong><?= fcc_partner_h($ts['sponsor_name']) ?></strong>, <?= fcc_partner_h($ts['sponsor_fbo_id']) ?><?= htmlspecialchars(fcc_t('. Izvor od'), ENT_QUOTES, 'UTF-8') ?> <?= fcc_partner_h($ts['snapshot_date']) ?>. <?= !$ts['fresh']?fcc_t('Stariji podatak, provjeri prije povezivanja.'):(count($ts['accounts'])===1?fcc_t('Odabir potvrđuješ spremanjem.'):fcc_t('Provjeri odgovarajući FCC račun.')) ?></p>
 <?php else: ?><p class="small mt-2"><?= htmlspecialchars(fcc_t('FLP prijedlog još nije dostupan. Sponzora možeš potvrditi ručno nakon provjere članstva.'), ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
 <p class="small"><?= htmlspecialchars(fcc_t('Spremanje povezuje timski profil i napredak s odabranom linijom do prvog menadžera. Izravni sponzor dobiva obavijesti o novim uspjesima.'), ENT_QUOTES, 'UTF-8') ?></p>
 <label class="d-block"><input type="checkbox" name="team_is_manager" value="1" <?= !empty($teamGraph[$teamSubjectId]['manager'])?'checked':'' ?>> <?= htmlspecialchars(fcc_t('Menadžer, pregled cijele povezane strukture i dubljih menadžerskih grana'), ENT_QUOTES, 'UTF-8') ?></label>
 <?php if($teamPrimary&&$teamPrimary!==$teamSubjectId): ?><p class="small mt-2"><?= htmlspecialchars(fcc_t('Zajednički Forever ID. Glavni profil i kontakt:'), ENT_QUOTES, 'UTF-8') ?> <a href="<?= url('admin/user-update/'.$teamPrimary) ?>"><?= fcc_partner_h($teamGraph[$teamPrimary]['name']) ?></a><?= htmlspecialchars(fcc_t('. Sponzorsku liniju uredi na glavnom računu.'), ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
 <details><summary><?= htmlspecialchars(fcc_t('Poslovni kontakt i razlog ručne dodjele'), ENT_QUOTES, 'UTF-8') ?></summary>
  <label class="d-block mt-2" for="team-phone-<?= $teamSubjectId ?>"><?= htmlspecialchars(fcc_t('Poslovni telefon za povezane suradnike'), ENT_QUOTES, 'UTF-8') ?></label>
  <input class="form-control" id="team-phone-<?= $teamSubjectId ?>" name="team_phone" type="tel" value="<?= fcc_partner_h($teamPhone) ?>" placeholder="+385…" maxlength="30">
  <?php if($registrationContact): ?><p class="small mt-2"><?= htmlspecialchars(fcc_t('Broj je preuzet iz registracije. Spremanjem potvrđuješ njegov prikaz povezanim suradnicima u timskom profilu.'), ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
  <label class="d-block mt-2"><input type="checkbox" name="team_contact_confirmed" value="1" <?= !empty($tm['contact_confirmed'])||$registrationContact?'checked':'' ?>> <?= htmlspecialchars(fcc_t('Potvrđen je prikaz ovog poslovnog telefona'), ENT_QUOTES, 'UTF-8') ?></label>
  <label class="d-block" for="team-reason-<?= $teamSubjectId ?>"><?= htmlspecialchars(fcc_t('Napomena o povezivanju'), ENT_QUOTES, 'UTF-8') ?></label>
  <input class="form-control" id="team-reason-<?= $teamSubjectId ?>" name="team_reason" maxlength="500" value="<?= fcc_partner_h($tm['reason']??'') ?>" placeholder="<?= htmlspecialchars(fcc_t('Razlog ako se odabir razlikuje od FLP prijedloga'), ENT_QUOTES, 'UTF-8') ?>">
 </details>
 <?php if($tm): ?><p class="small mt-2 mb-0"><?= htmlspecialchars(fcc_t('Potvrđeno'), ENT_QUOTES, 'UTF-8') ?> <?= fcc_partner_h($tm['linked_at']) ?> <?= htmlspecialchars(fcc_t('UTC. Izvor:'), ENT_QUOTES, 'UTF-8') ?> <?= $tm['source']==='flp_confirmed'?fcc_t('FLP prijedlog potvrđen u FCC-u'):'administratorska dodjela' ?>.</p><?php endif ?>
</fieldset>
<?php endif ?>
