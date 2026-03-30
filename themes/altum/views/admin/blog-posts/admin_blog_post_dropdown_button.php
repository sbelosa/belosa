<?php defined('ALTUMCODE') || die() ?>

<div class="dropdown">
    <button type="button" class="btn btn-link <?= $data->button_text_class ?? 'text-secondary' ?> dropdown-toggle dropdown-toggle-simple" data-toggle="dropdown" data-boundary="viewport">
        <i class="fas fa-fw fa-ellipsis-v"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right">
        <!-- Custom code: FC-2026-03-30: admin blog post english draft creation -->
        <?php $english_language = array_search('en', \Altum\Language::$active_languages, true); ?>
        <?php if($english_language && ($data->language ?? null) !== $english_language): ?>
            <a class="dropdown-item" href="<?= url('admin/blog-post-create-english/' . $data->id . '?global_token=' . \Altum\Csrf::get('global_token')) ?>"><i class="fas fa-fw fa-sm fa-language mr-2"></i> Create English Draft</a>
        <?php endif ?>
        <!-- /Custom code: FC-2026-03-30 -->
        <a class="dropdown-item" href="<?= SITE_URL . ($data->language ? \Altum\Language::$active_languages[$data->language] . '/' : null) . 'blog/' . $data->url ?>" target="_blank" rel="noreferrer"><i class="fas fa-fw fa-sm fa-eye mr-2"></i> <?= l('global.view') ?></a>
        <a class="dropdown-item" href="admin/blog-post-update/<?= $data->id ?>"><i class="fas fa-fw fa-sm fa-pencil-alt mr-2"></i> <?= l('global.edit') ?></a>
        <a href="#" data-toggle="modal" data-target="#blog_post_delete_modal" data-blog-post-id="<?= $data->id ?>" data-resource-name="<?= $data->resource_name ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>
    </div>
</div>
