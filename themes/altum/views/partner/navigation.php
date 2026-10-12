<?php defined('ALTUMCODE') || die();
$path=\Altum\Router::$controller_key; $section=$path==='partner'?(\Altum\Router::$params[0]??'today'):$path;
if(in_array($path,['link','links'],true)) $section=(($path==='links'&&($_GET['type']??'')==='link')||($path==='link'&&($this->link->type??'')==='link'))?'more':'cards';
if(in_array($path,['partner-webinar','partner-marketing'],true)) $section='share';
if($section==='card') $section='cards'; /* FC-2026-10-12: simple card editor */
if(!in_array($section,['today','contacts','share','cards','team'],true)) $section='more';
$navigation=[['today','Danas','home','partner'],['contacts','Kontakti','people','partner/contacts'],['share','Podijeli','share','partner/share'],['cards',fcc_t('Moja kartica'),'card','partner/cards'],['more',fcc_t('Više'),'more','partner/more']];
$desktopNavigation=$navigation; array_splice($desktopNavigation,4,0,[['team','Moj tim','people','partner/team']]);
?>
<aside class="fp-sidebar" aria-label="<?= htmlspecialchars(fcc_t('Glavna navigacija'), ENT_QUOTES, 'UTF-8') ?>">
 <a class="fp-brand" href="<?= url('partner') ?>"><img class="fp-logo" src="<?= SITE_URL ?>partner-assets/fcc-logo.png" width="160" height="41" style="max-width:100%;height:auto" alt="<?= htmlspecialchars(fcc_t('Forever Card Club'), ENT_QUOTES, 'UTF-8') ?>"><small><?= htmlspecialchars(fcc_t('Tvoje poslovanje, povezano.'), ENT_QUOTES, 'UTF-8') ?></small></a>
 <span class="fp-nav-caption"><?= htmlspecialchars(fcc_t('MOJ RADNI PROSTOR'), ENT_QUOTES, 'UTF-8') ?></span>
 <nav><?php foreach($desktopNavigation as [$key,$label,$icon,$href]): ?><a class="fp-nav-link <?= ($section===$key||($key==='more'&&!in_array($section,['today','contacts','share','cards','link','team'])))?'is-active':'' ?>" <?= $key==='team'?fcc_pro_link((int)$this->user->user_id,'team',$href):'href="'.url($href).'"' ?>><?= fcc_partner_icon($icon) ?><span><?= fcc_partner_h(fcc_t($label)) ?></span><?php if($key==='team'&&!fcc_pro_has_access((int)$this->user->user_id)): ?><span class="fcc-pro-badge"><?= htmlspecialchars(fcc_t('PRO'), ENT_QUOTES, 'UTF-8') ?></span><?php endif ?></a><?php endforeach ?></nav>
 <div class="fp-sidebar-bottom"><?php if(\Altum\Authentication::is_admin()): ?><a href="<?= url('partner/admin') ?>"><?= fcc_partner_icon('settings') ?> <?= htmlspecialchars(fcc_t('Administracija'), ENT_QUOTES, 'UTF-8') ?></a><?php endif ?><a href="<?= url('partner/install') ?>"><?= fcc_partner_icon('plus') ?> <?= htmlspecialchars(fcc_t('Instaliraj aplikaciju'), ENT_QUOTES, 'UTF-8') ?></a><?php if(fcc_help_enabled()): ?><a href="<?= fcc_help_url() ?>"><?= fcc_partner_h(fcc_help_t('title')) ?></a><?php endif ?><a href="<?= url('feedback-tickets') ?>"><?= htmlspecialchars(fcc_t('Pomoć i podrška'), ENT_QUOTES, 'UTF-8') ?> <?= fcc_partner_icon('arrow') ?></a><span><?= htmlspecialchars(fcc_t('Forever Card Club'), ENT_QUOTES, 'UTF-8') ?></span></div>
</aside>
<nav class="fp-bottom-nav" aria-label="<?= htmlspecialchars(fcc_t('Mobilna navigacija'), ENT_QUOTES, 'UTF-8') ?>"><?php foreach($navigation as [$key,$label,$icon,$href]): ?><a class="<?= ($section===$key||($section==='team'&&$key==='more'))?'is-active':'' ?>" <?= ($section===$key||($section==='team'&&$key==='more')) ? 'aria-current="page"' : '' ?> href="<?= url($href) ?>"><?= fcc_partner_icon($icon) ?><span><?= fcc_partner_h(fcc_t($label)) ?></span></a><?php endforeach ?></nav>

<script defer src="<?= ASSETS_FULL_URL ?>js/fcc-partner-navigation.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner-navigation.js') ?>"></script>
