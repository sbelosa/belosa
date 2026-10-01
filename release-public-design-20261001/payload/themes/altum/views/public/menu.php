<?php defined('ALTUMCODE') || die();
fcc_public_assets();
$public_links = [
    'products'=>fcc_public_url('blog'),
    'articles'=>fcc_public_url('blog',['view'=>'articles']),
    'about'=>fcc_public_url('pages/foreverclub'),
    'contact'=>fcc_public_url('page/contact'),
];
$public_account = is_logged_in() ? url('partner') : url('login');
$public_logo = settings()->main->logo_light_full_url ?: settings()->main->logo_dark_full_url;
?>
<a class="fcw-skip" href="<?= e('/'.ltrim((string)($_SERVER['REQUEST_URI'] ?? '/'),'/')) ?>#fcc-public-main"><?= e(fcc_public_copy('skip')) ?></a>
<header class="fcw-header">
 <div class="fcw-header-inner">
  <a class="fcw-brand" href="<?= e(fcc_public_url()) ?>" aria-label="Forever Card Club"><img src="<?= e($public_logo) ?>" width="208" height="64" alt="Forever Card Club"></a>
  <nav class="fcw-desktop-nav" aria-label="<?= e(fcc_public_copy('menu')) ?>"><?php foreach($public_links as $key=>$href): ?><a href="<?= e($href) ?>"><?= e(fcc_public_copy($key)) ?></a><?php endforeach ?></nav>
  <div class="fcw-header-actions"><?php fcc_language_selector('public'); ?><a class="fcw-button fcw-button-small fcw-desktop-account" href="<?= e($public_account) ?>"><?= e(fcc_public_copy(is_logged_in()?'workspace':'login')) ?> <span aria-hidden="true">↗</span></a></div>
  <details class="fcw-mobile-menu"><summary><?= e(fcc_public_copy('menu')) ?> <span aria-hidden="true">☰</span></summary><nav aria-label="<?= e(fcc_public_copy('menu')) ?>"><?php foreach($public_links as $key=>$href): ?><a href="<?= e($href) ?>"><?= e(fcc_public_copy($key)) ?></a><?php endforeach ?><a class="fcw-button" href="<?= e($public_account) ?>"><?= e(fcc_public_copy(is_logged_in()?'workspace':'login')) ?> ↗</a></nav></details>
 </div>
</header>
