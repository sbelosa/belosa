<?php defined('ALTUMCODE') || die();
$path=\Altum\Router::$controller_key; $section=$path==='partner'?(\Altum\Router::$params[0]??'today'):$path;
if(in_array($path,['link','links'],true)) $section='cards';
if(in_array($path,['partner-webinar','partner-marketing'],true)) $section='share';
if(!in_array($section,['today','contacts','share','cards','team'],true)) $section='more';
$navigation=[['today','Danas','home','partner'],['contacts','Kontakti','people','partner/contacts'],['share','Podijeli','share','partner/share'],['cards','Moja kartica','card','partner/cards'],['more','Više','more','partner/more']];
$desktopNavigation=$navigation; array_splice($desktopNavigation,4,0,[['team','Moj tim','people','partner/team']]);
?>
<aside class="fp-sidebar" aria-label="Glavna navigacija">
 <a class="fp-brand" href="<?= url('partner') ?>"><img class="fp-logo" src="<?= SITE_URL ?>partner-assets/fcc-logo.png" width="160" height="41" style="max-width:100%;height:auto" alt="Forever Card Club"><small>Tvoje poslovanje, povezano.</small></a>
 <span class="fp-nav-caption">MOJ RADNI PROSTOR</span>
 <nav><?php foreach($desktopNavigation as [$key,$label,$icon,$href]): ?><a class="fp-nav-link <?= ($section===$key||($key==='cards'&&$path==='link')||($key==='more'&&!in_array($section,['today','contacts','share','cards','link','team'])))?'is-active':'' ?>" <?= $key==='team'?fcc_pro_link((int)$this->user->user_id,'team',$href):'href="'.url($href).'"' ?>><?= fcc_partner_icon($icon) ?><span><?= $label ?></span><?php if($key==='team'): ?><span class="fcc-pro-badge">PRO</span><?php endif ?></a><?php endforeach ?></nav>
 <div class="fp-sidebar-bottom"><?php if(\Altum\Authentication::is_admin()): ?><a href="<?= url('partner/admin') ?>"><?= fcc_partner_icon('settings') ?> Administracija</a><?php endif ?><a href="<?= url('partner/install') ?>"><?= fcc_partner_icon('plus') ?> Instaliraj aplikaciju</a><a href="<?= url('feedback-tickets') ?>">Pomoć i podrška <?= fcc_partner_icon('arrow') ?></a><span>Forever Card Club</span></div>
</aside>
<nav class="fp-bottom-nav" aria-label="Mobilna navigacija"><?php foreach($navigation as [$key,$label,$icon,$href]): ?><a class="<?= ($section===$key||($section==='team'&&$key==='more'))?'is-active':'' ?>" href="<?= url($href) ?>"><?= fcc_partner_icon($icon) ?><span><?= $label ?></span></a><?php endforeach ?></nav>
