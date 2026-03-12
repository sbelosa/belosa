<div id="<?= 'biolink_block_id_' . $row->biolink_block_id ?>" data-biolink-block-id="<?= $row->biolink_block_id ?>" data-biolink-block-type="<?= $row->type ?>" class="col-12 my-<?= $data->link->settings->block_spacing ?? '2' ?>">
  <a class="btn btn-block btn-primary link-btn <?= $hover_class ?> <?= 'link-btn-' . $button_border_radius ?> <?= $button_extra_class ?>"
     href="https://wa.me/<?= $phone ?>?text=<?= $message ?>"
     target="_blank"
     rel="noopener noreferrer"
     style="<?= $button_style ?>"
     data-text-color data-border-width data-border-radius data-border-style data-border-color data-border-shadow data-animation data-background-color data-text-alignment>

    <span data-icon>
      <?php if($button_icon): ?>
        <i class="<?= $button_icon ?> mr-1"></i>
      <?php endif ?>
    </span>

    <span data-title><?= $button ?></span>
  </a>
</div>
