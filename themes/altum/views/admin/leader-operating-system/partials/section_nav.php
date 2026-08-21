<?php defined('ALTUMCODE') || die() ?>
<?php $active = (string) ($data->active ?? 'leader') ?>

<nav class="nav nav-pills flex-column flex-sm-row mb-4" aria-label="<?= l('admin_leader_operating_system.section_nav') ?>">
    <a class="nav-link <?= $active === 'leader' ? 'active' : null ?>" href="<?= url('admin/leader-operating-system') ?>">
        <i class="fas fa-fw fa-users-cog mr-1"></i><?= l('admin_leader_operating_system.section.leader') ?>
    </a>
    <a class="nav-link <?= $active === 'forever' ? 'active' : null ?>" href="<?= url('admin/leader-operating-system-forever') ?>">
        <i class="fas fa-fw fa-seedling mr-1"></i><?= l('admin_leader_operating_system.section.forever') ?>
    </a>
</nav>
