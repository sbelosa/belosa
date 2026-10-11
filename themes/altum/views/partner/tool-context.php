<?php defined('ALTUMCODE') || die(); ?>
<div class="container fp-tool-context">
    <nav aria-label="<?= htmlspecialchars(fcc_t('Lokacija alata'), ENT_QUOTES, 'UTF-8') ?>"><a href="<?= url('partner/more#tools') ?>"><?= htmlspecialchars(fcc_t('← Svi alati'), ENT_QUOTES, 'UTF-8') ?></a><span><?= fcc_partner_h(fcc_partner_tool_groups()[$fp_tool_context['group']]) ?></span></nav>
    <div class="fp-tool-intro"><p><?= fcc_partner_h($fp_tool_context['description']) ?></p><details><summary><?= htmlspecialchars(fcc_t('Kako koristiti ovaj alat?'), ENT_QUOTES, 'UTF-8') ?></summary><p><?= fcc_partner_h($fp_tool_context['help']) ?></p></details></div>
</div>
