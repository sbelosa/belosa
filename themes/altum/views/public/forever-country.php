<?php defined('ALTUMCODE') || die(); $h=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); ?>
<!doctype html><html lang="<?= $h(fcc_library_pilot_locale()) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $h(fcc_forever_copy($kind)) ?> · Forever Card Club</title>
<style>body{margin:0;background:#edf4ef;color:#173f36;font:16px/1.6 system-ui,sans-serif}main{box-sizing:border-box;max-width:620px;margin:7vh auto;padding:36px;background:#fff;border:1px solid #d4e3da;border-radius:24px}h1{line-height:1.2;font-size:30px}label{display:block;font-weight:650;margin-top:24px}select,button{box-sizing:border-box;width:100%;padding:16px;font:inherit;border-radius:12px}select{margin:8px 0 20px;background:white;border:1px solid #9bb9aa;color:#173f36}button{background:#17644f;color:white;border:0;font-weight:650;cursor:pointer}a{color:#17644f}small{display:block;margin-top:20px}.notice{padding:16px;border-radius:12px;background:#edf4ef} @media(max-width:650px){main{margin:20px 12px;padding:24px}h1{font-size:26px}}</style></head><body><main data-fcc-forever-country>
<a href="<?= $h(url($ref)) ?>">Forever Card Club</a><h1><?= $h(fcc_forever_copy($kind)) ?></h1><p><?= $h(fcc_forever_copy('intro')) ?></p>
<p class="notice" role="status"><?= $h(fcc_forever_copy($message)) ?></p>
<?php if($fbo): ?><form method="get" action="<?= $h(url('forever-shop')) ?>">
<?php if($block_id): ?><input type="hidden" name="block" value="<?= $block_id ?>"><?php else: ?><input type="hidden" name="ref" value="<?= $h($ref) ?>"><input type="hidden" name="kind" value="<?= $h($kind) ?>"><?php endif ?>
<label for="market"><?= $h(fcc_forever_copy('country')) ?></label><select id="market" name="market" required><option value=""><?= $h(fcc_forever_copy('select')) ?></option>
<?php foreach(fcc_forever_markets() as $code=>$offer): ?><option value="<?= $h($code) ?>" <?= $country===$code?'selected':'' ?>><?= $h(fcc_library_pilot_country_name($code)) ?></option><?php endforeach ?></select>
<button type="submit"><?= $h(fcc_forever_copy('continue')) ?></button></form><?php endif ?>
<small><?= $h(fcc_forever_copy('owner')) ?>: <?= $h($owner->name) ?></small><p><a href="<?= $h(url($ref)) ?>"><?= $h(fcc_forever_copy('back')) ?></a></p>
</main></body></html>
