<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-02-24: FCC Edukacija settings UI -->
<?php
$help_widget_items_by_language = settings()->fcc_education->help_widget_items_by_language ?? null;
if(!$help_widget_items_by_language && !empty(settings()->custom->help_widget_items)) {
    $help_widget_items_by_language = [\Altum\Language::$default_name => settings()->custom->help_widget_items];
}
$help_widget_items_by_language = is_array($help_widget_items_by_language) || is_object($help_widget_items_by_language)
    ? (array) $help_widget_items_by_language
    : [];

$languages = array_keys((array) \Altum\Language::$active_languages);

/* Custom code: FC-2026-02-24: FCC education page settings */
$fcc_settings = settings()->fcc_education ?? null;
$education_by_language = $fcc_settings->education_by_language ?? [];
$education_by_language = is_array($education_by_language) || is_object($education_by_language)
    ? (array) $education_by_language
    : [];
/* /Custom code: FC-2026-02-24 */

/* Custom code: FC-2026-02-24: FCC Core help widget pages */
$help_widget_pages = [
    ['key' => 'links_biolink', 'label_key' => 'admin_settings.fcc_education.page.links_biolink', 'pathname' => '/links', 'search' => 'type=biolink'],
    ['key' => 'links_link', 'label_key' => 'admin_settings.fcc_education.page.links_link', 'pathname' => '/links', 'search' => 'type=link'],
    ['key' => 'links_file', 'label_key' => 'admin_settings.fcc_education.page.links_file', 'pathname' => '/links', 'search' => 'type=file'],
    ['key' => 'links_vcard', 'label_key' => 'admin_settings.fcc_education.page.links_vcard', 'pathname' => '/links', 'search' => 'type=vcard'],
    ['key' => 'links_static', 'label_key' => 'admin_settings.fcc_education.page.links_static', 'pathname' => '/links', 'search' => 'type=static'],
    ['key' => 'link_editor', 'label_key' => 'admin_settings.fcc_education.page.link_editor', 'pathname_prefix' => '/link/', 'search' => ''],
    ['key' => 'links_statistics', 'label_key' => 'admin_settings.fcc_education.page.links_statistics', 'pathname' => '/links-statistics', 'search' => ''],
    ['key' => 'qr_codes', 'label_key' => 'admin_settings.fcc_education.page.qr_codes', 'pathname' => '/qr-codes', 'search' => ''],
    ['key' => 'domains', 'label_key' => 'admin_settings.fcc_education.page.domains', 'pathname' => '/domains', 'search' => ''],
    ['key' => 'notification_handlers', 'label_key' => 'admin_settings.fcc_education.page.notification_handlers', 'pathname' => '/notification-handlers', 'search' => ''],
    ['key' => 'pixels', 'label_key' => 'admin_settings.fcc_education.page.pixels', 'pathname' => '/pixels', 'search' => ''],
    ['key' => 'projects', 'label_key' => 'admin_settings.fcc_education.page.projects', 'pathname' => '/projects', 'search' => ''],
    ['key' => 'splash_pages', 'label_key' => 'admin_settings.fcc_education.page.splash_pages', 'pathname' => '/splash-pages', 'search' => ''],
];
/* /Custom code: FC-2026-02-24 */
?>

<div class="form-group">
    <label><i class="fas fa-fw fa-sm fa-circle-question text-muted mr-1"></i> <?= l('admin_settings.fcc_education.tab') ?></label>
    <small class="form-text text-muted"><?= l('admin_settings.fcc_education.help') ?></small>
</div>

<!-- Custom code: FC-2026-02-24: FCC education page settings -->
<div class="mb-4">
    <div class="h6 mb-2"><?= l('admin_settings.fcc_education.page_section') ?></div>
    <small class="form-text text-muted"><?= l('admin_settings.fcc_education.page_section_help') ?></small>
</div>

<?php foreach($languages as $language_name): ?>
    <?php
    $education = $education_by_language[$language_name] ?? null;
    $education = is_array($education) || is_object($education) ? (object) $education : null;
    $education_enabled = $education->enabled ?? 1;
    $education_title = $education->title ?? '';
    $education_subtitle = $education->subtitle ?? '';
    $education_cta_label = $education->cta_label ?? '';
    $education_videos = $education->videos ?? [];
    $education_videos = is_array($education_videos) || is_object($education_videos) ? (array) $education_videos : [];

    $education_lines = [];
    foreach($education_videos as $video) {
        $video = is_array($video) || is_object($video) ? (object) $video : null;
        if(!$video || empty($video->vimeo_id)) {
            continue;
        }
        $title = $video->title ?? '';
        $education_lines[] = trim($title . ' | ' . $video->vimeo_id, ' |');
    }
    ?>

    <div class="border rounded p-3 mb-4">
        <div class="mb-3 font-weight-bold">
            <?= $language_name ?> (<?= \Altum\Language::$languages[$language_name]['code'] ?? '' ?>)
        </div>

        <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="education_<?= $language_name ?>_enabled" name="education[<?= $language_name ?>][enabled]" <?= $education_enabled ? 'checked="checked"' : '' ?> />
            <label class="custom-control-label" for="education_<?= $language_name ?>_enabled"><?= l('admin_settings.fcc_education.page_enabled') ?></label>
        </div>

        <div class="form-group">
            <label for="education_<?= $language_name ?>_title"><?= l('admin_settings.fcc_education.page_title') ?></label>
            <input
                id="education_<?= $language_name ?>_title"
                name="education[<?= $language_name ?>][title]"
                class="form-control"
                type="text"
                value="<?= $education_title ?>"
                placeholder="<?= l('fcc_education.title') ?>"
            />
        </div>

        <div class="form-group">
            <label for="education_<?= $language_name ?>_subtitle"><?= l('admin_settings.fcc_education.page_subtitle') ?></label>
            <input
                id="education_<?= $language_name ?>_subtitle"
                name="education[<?= $language_name ?>][subtitle]"
                class="form-control"
                type="text"
                value="<?= $education_subtitle ?>"
            />
        </div>

        <div class="form-group">
            <label for="education_<?= $language_name ?>_videos"><?= l('admin_settings.fcc_education.page_videos') ?></label>
            <textarea
                id="education_<?= $language_name ?>_videos"
                name="education[<?= $language_name ?>][videos]"
                class="form-control"
                rows="5"
                placeholder="<?= l('admin_settings.fcc_education.page_videos_placeholder') ?>"
            ><?= implode("\n", $education_lines) ?></textarea>
        </div>

        <div class="form-group mb-0">
            <label for="education_<?= $language_name ?>_cta"><?= l('admin_settings.fcc_education.page_cta') ?></label>
            <input
                id="education_<?= $language_name ?>_cta"
                name="education[<?= $language_name ?>][cta_label]"
                class="form-control"
                type="text"
                value="<?= $education_cta_label ?>"
            />
        </div>
    </div>
<?php endforeach ?>
<!-- /Custom code: FC-2026-02-24 -->

<?php foreach($languages as $language_name): ?>
    <?php
    $language_items = $help_widget_items_by_language[$language_name] ?? [];
    $language_items = is_array($language_items) || is_object($language_items) ? (array) $language_items : [];
    $help_widget_lookup = [];
    foreach($language_items as $item) {
        $key = ($item->match->pathname ?? $item->match->pathnamePrefix ?? '') . '|' . ($item->match->searchIncludes ?? '');
        $help_widget_lookup[$key] = $item;
    }
    ?>
    <div class="border rounded p-3 mb-4">
        <div class="mb-3 font-weight-bold">
            <?= $language_name ?> (<?= \Altum\Language::$languages[$language_name]['code'] ?? '' ?>)
        </div>

        <?php foreach($help_widget_pages as $page): ?>
            <?php
            $lookup_key = ($page['pathname'] ?? $page['pathname_prefix']) . '|' . $page['search'];
            $item = $help_widget_lookup[$lookup_key] ?? null;
            $page_label = l($page['label_key']);
            $title = $item->title ?? $page_label;
            $vimeo_id = $item->vimeoId ?? '';
            $description = $item->description ?? '';
            $extra_html = $item->extraHtml ?? '';
            $enabled = isset($item->enabled) ? (int) $item->enabled : 0;
            ?>
            <div class="border rounded p-3 mb-3">
                <div class="mb-2 font-weight-bold">
                    <?= $page_label ?> <span class="text-muted small">(<?= $page['pathname'] ?? $page['pathname_prefix'] ?><?= $page['search'] ? '?' . $page['search'] : '' ?>)</span>
                </div>
                <div class="custom-control custom-switch mb-3">
                    <input type="checkbox" class="custom-control-input" id="help_widget_<?= $language_name ?>_<?= $page['key'] ?>_enabled" name="help_widget[<?= $language_name ?>][<?= $page['key'] ?>][enabled]" <?= $enabled ? 'checked="checked"' : '' ?> />
                    <label class="custom-control-label" for="help_widget_<?= $language_name ?>_<?= $page['key'] ?>_enabled"><?= l('admin_settings.fcc_education.help_widget_enabled') ?></label>
                </div>
                <div class="form-group">
                    <label for="help_widget_<?= $language_name ?>_<?= $page['key'] ?>_title"><?= l('admin_settings.fcc_education.help_widget_title') ?></label>
                    <input
                        id="help_widget_<?= $language_name ?>_<?= $page['key'] ?>_title"
                        name="help_widget[<?= $language_name ?>][<?= $page['key'] ?>][title]"
                        class="form-control"
                        type="text"
                        value="<?= $title ?>"
                        placeholder="<?= $page_label ?>"
                    />
                </div>
                <div class="form-group">
                    <label for="help_widget_<?= $language_name ?>_<?= $page['key'] ?>_vimeo"><?= l('admin_settings.fcc_education.help_widget_vimeo') ?></label>
                    <input
                        id="help_widget_<?= $language_name ?>_<?= $page['key'] ?>_vimeo"
                        name="help_widget[<?= $language_name ?>][<?= $page['key'] ?>][vimeo_id]"
                        class="form-control"
                        type="text"
                        value="<?= $vimeo_id ?>"
                        placeholder="123456789"
                    />
                </div>
                <div class="form-group">
                    <label for="help_widget_<?= $language_name ?>_<?= $page['key'] ?>_description"><?= l('admin_settings.fcc_education.help_widget_description') ?></label>
                    <textarea
                        id="help_widget_<?= $language_name ?>_<?= $page['key'] ?>_description"
                        name="help_widget[<?= $language_name ?>][<?= $page['key'] ?>][description]"
                        class="form-control"
                        rows="3"
                    ><?= $description ?></textarea>
                </div>
                <div class="form-group mb-0">
                    <label for="help_widget_<?= $language_name ?>_<?= $page['key'] ?>_extra_html"><?= l('admin_settings.fcc_education.help_widget_extra_html') ?></label>
                    <textarea
                        id="help_widget_<?= $language_name ?>_<?= $page['key'] ?>_extra_html"
                        name="help_widget[<?= $language_name ?>][<?= $page['key'] ?>][extra_html]"
                        class="form-control"
                        rows="4"
                    ><?= $extra_html ?></textarea>
                </div>
            </div>
        <?php endforeach ?>
    </div>
<?php endforeach ?>

<button type="submit" name="submit" class="btn btn-lg btn-block btn-primary mt-4"><?= l('global.update') ?></button>
<!-- /Custom code: FC-2026-02-24 -->
