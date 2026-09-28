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
 <legend style="width:auto;font-size:1rem;padding:0 .4rem">Sponzor i timska linija</legend>
 <input type="hidden" name="team_form" value="1">
 <input type="hidden" name="team_version" value="<?= (int)($tm['version']??0) ?>">
 <label for="<?= $teamInputId ?>">Odaberi izravnog sponzora</label>
 <select id="<?= $teamInputId ?>" class="form-control" name="team_sponsor_id" data-is-not-custom-select>
  <option value="0">Bez povezanog sponzora</option>
  <?php foreach(fcc_team_options($teamSubjectId) as $option): ?>
   <option value="<?= (int)$option['user_id'] ?>" <?= $selected===(int)$option['user_id']?'selected':'' ?> <?= !$option['fbo_id']?'disabled':'' ?>><?= fcc_partner_h($option['name'].' · '.$option['email'].' · FCC #'.$option['user_id'].' · '.($option['fbo_id']?:'Forever ID nije upisan')) ?></option>
  <?php endforeach ?>
 </select>
 <?php if($ts): ?><p class="small mt-2">FLP prijedlog: <strong><?= fcc_partner_h($ts['sponsor_name']) ?></strong>, <?= fcc_partner_h($ts['sponsor_fbo_id']) ?>. Izvor od <?= fcc_partner_h($ts['snapshot_date']) ?>. <?= !$ts['fresh']?'Stariji podatak, provjeri prije povezivanja.':(count($ts['accounts'])===1?'Odabir potvrđuješ spremanjem.':'Provjeri odgovarajući FCC račun.') ?></p>
 <?php else: ?><p class="small mt-2">FLP prijedlog još nije dostupan. Sponzora možeš potvrditi ručno nakon provjere članstva.</p><?php endif ?>
 <p class="small">Spremanje povezuje timski profil i napredak s odabranom linijom do prvog menadžera. Izravni sponzor dobiva obavijesti o novim uspjesima.</p>
 <label class="d-block"><input type="checkbox" name="team_is_manager" value="1" <?= !empty($teamGraph[$teamSubjectId]['manager'])?'checked':'' ?>> Menadžer, pregled cijele povezane strukture i dubljih menadžerskih grana</label>
 <?php if($teamPrimary&&$teamPrimary!==$teamSubjectId): ?><p class="small mt-2">Zajednički Forever ID. Glavni profil i kontakt: <a href="<?= url('admin/user-update/'.$teamPrimary) ?>"><?= fcc_partner_h($teamGraph[$teamPrimary]['name']) ?></a>. Sponzorsku liniju uredi na glavnom računu.</p><?php endif ?>
 <details><summary>Poslovni kontakt i razlog ručne dodjele</summary>
  <label class="d-block mt-2" for="team-phone-<?= $teamSubjectId ?>">Poslovni telefon za povezane suradnike</label>
  <input class="form-control" id="team-phone-<?= $teamSubjectId ?>" name="team_phone" type="tel" value="<?= fcc_partner_h($teamPhone) ?>" placeholder="+385…" maxlength="30">
  <?php if($registrationContact): ?><p class="small mt-2">Broj je preuzet iz registracije. Spremanjem potvrđuješ njegov prikaz povezanim suradnicima u timskom profilu.</p><?php endif ?>
  <label class="d-block mt-2"><input type="checkbox" name="team_contact_confirmed" value="1" <?= !empty($tm['contact_confirmed'])||$registrationContact?'checked':'' ?>> Potvrđen je prikaz ovog poslovnog telefona</label>
  <label class="d-block" for="team-reason-<?= $teamSubjectId ?>">Napomena o povezivanju</label>
  <input class="form-control" id="team-reason-<?= $teamSubjectId ?>" name="team_reason" maxlength="500" value="<?= fcc_partner_h($tm['reason']??'') ?>" placeholder="Razlog ako se odabir razlikuje od FLP prijedloga">
 </details>
 <?php if($tm): ?><p class="small mt-2 mb-0">Potvrđeno <?= fcc_partner_h($tm['linked_at']) ?> UTC. Izvor: <?= $tm['source']==='flp_confirmed'?'FLP prijedlog potvrđen u FCC-u':'administratorska dodjela' ?>.</p><?php endif ?>
</fieldset>
<?php endif ?>
