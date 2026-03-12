<?php defined('ALTUMCODE') || die() ?>

<div class="d-flex flex-column flex-lg-row justify-content-between mb-4">
    <div class="mb-3 mb-lg-0">
        <a
                class="h5 footer-heading"
                href="<?= url() ?>"
                data-logo
                data-light-value="<?= settings()->main->logo_light != '' ? settings()->main->logo_light_full_url : settings()->main->title ?>"
                data-light-class="<?= settings()->main->logo_light != '' ? 'mb-2 footer-logo' : 'mb-2' ?>"
                data-light-tag="<?= settings()->main->logo_light != '' ? 'img' : 'span' ?>"
                data-dark-value="<?= settings()->main->logo_dark != '' ? settings()->main->logo_dark_full_url : settings()->main->title ?>"
                data-dark-class="<?= settings()->main->logo_dark != '' ? 'mb-2 footer-logo' : 'mb-2' ?>"
                data-dark-tag="<?= settings()->main->logo_dark != '' ? 'img' : 'span' ?>"
        >
            <?php if(settings()->main->{'logo_' . \Altum\ThemeStyle::get()} != ''): ?>
                <img src="<?= settings()->main->{'logo_' . \Altum\ThemeStyle::get() . '_full_url'} ?>" class="mb-2 footer-logo" alt="<?= l('global.accessibility.logo_alt') ?>" />
            <?php else: ?>
                <span class="mb-2"><?= settings()->main->title ?></span>
            <?php endif ?>
        </a>
        <div class="text-muted font-size-little-small mt-1"><?= sprintf(l('global.footer.copyright'), date('Y'), settings()->main->title) ?></div>

        <?php
        /* Custom code: FC-2026-02-26: mandatory support and complaints footer contact */
        $fcc_footer_support_text = \Altum\Language::$code === 'hr'
            ? 'Za sva pitanja, poteškoće ili primjedbe obratite nam se na'
            : 'For any questions, difficulties or complaints, contact us at';
        /* /Custom code: FC-2026-02-26 */
        ?>
        <div class="text-muted font-size-little-small mt-2">
            <?= $fcc_footer_support_text ?>
            <a href="mailto:info@forevercard.club">info@forevercard.club</a>
        </div>

        <div class="d-flex flex-wrap mt-3 gap-3">
            <?php foreach(require APP_PATH . 'includes/admin_socials.php' as $key => $value): ?>
                <?php if(isset(settings()->socials->{$key}) && !empty(settings()->socials->{$key})): ?>
                    <div class="p-2 footer-social-wrapper" style="background-color: <?= $value['background_color'] ?>;">
                        <a href="<?= sprintf($value['format'], settings()->socials->{$key}) ?>" target="_blank" rel="noreferrer" data-toggle="tooltip" title="<?= $value['name'] ?>">
                            <i class="<?= $value['icon'] ?> fa-fw fa-sm" style="color: <?= $value['color'] ?>;"></i>
                        </a>
                    </div>
                <?php endif ?>
            <?php endforeach ?>
        </div>
    </div>

    <div class="d-flex flex-row flex-truncate">
        <?php if(count(\Altum\Language::$active_languages) > 1): ?>
            <?php
            /* Custom code: FC-2026-03-02: blog post language options only when translation exists */
            $fcc_original_request = trim((string) \Altum\Router::$original_request, '/');
            $fcc_original_request_segments = $fcc_original_request === '' ? [] : explode('/', $fcc_original_request);

            $fcc_is_blog_post_route = \Altum\Router::$controller_key === 'blog'
                && isset($fcc_original_request_segments[0], $fcc_original_request_segments[1])
                && $fcc_original_request_segments[0] === 'blog'
                && !in_array($fcc_original_request_segments[1], ['category', 'feed']);

            $fcc_blog_post_slug = $fcc_is_blog_post_route ? query_clean($fcc_original_request_segments[1]) : null;
            $fcc_blog_post_available_language_codes = [];

            if($fcc_blog_post_slug) {
                $fcc_blog_post_languages_query = "SELECT DISTINCT `language` FROM `blog_posts` WHERE `url` = '{$fcc_blog_post_slug}' AND `is_published` = 1";
                $fcc_blog_post_languages_result = database()->query($fcc_blog_post_languages_query);

                if($fcc_blog_post_languages_result) {
                    while($fcc_blog_post_language_row = $fcc_blog_post_languages_result->fetch_object()) {
                        if(empty($fcc_blog_post_language_row->language)) {
                            $fcc_blog_post_available_language_codes[] = '*';
                            continue;
                        }

                        if(isset(\Altum\Language::$active_languages[$fcc_blog_post_language_row->language])) {
                            $fcc_blog_post_available_language_codes[] = \Altum\Language::$active_languages[$fcc_blog_post_language_row->language];
                        }
                    }
                }

                $fcc_blog_post_available_language_codes = array_unique($fcc_blog_post_available_language_codes);
            }
            /* /Custom code: FC-2026-03-02 */
            ?>
            <div class="mr-3 ml-lg-3 mr-lg-0 fcc-footer-lang-switch" aria-label="<?= l('global.choose_language') ?>">
                <?php foreach(\Altum\Language::$languages_ordered as $language): ?>
                    <?php if($language['status']): ?>
                        <?php
                        $fcc_language_has_blog_translation = true;

                        if($fcc_is_blog_post_route) {
                            $fcc_language_has_blog_translation = in_array($language['code'], $fcc_blog_post_available_language_codes, true) || in_array('*', $fcc_blog_post_available_language_codes, true);

                            if($language['name'] == \Altum\Language::$name) {
                                $fcc_language_has_blog_translation = true;
                            }
                        }

                        if(!$fcc_language_has_blog_translation) {
                            continue;
                        }

                        $new_url = match(\Altum\Router::$controller_key) {
                            'blog' => $fcc_is_blog_post_route
                                ? SITE_URL . $language['code'] . '/' . 'blog/' . $fcc_blog_post_slug
                                : SITE_URL . $language['code'] . '/' . 'blog',
                            default => SITE_URL . $language['code'] . '/' . \Altum\Router::$original_request . (\Altum\Router::$original_request_query ? '?' . \Altum\Router::$original_request_query : null)
                        };
                        $fcc_language_code = mb_strtoupper((string) ($language['code'] ?? ''));
                        $fcc_language_short_label = $fcc_language_code === 'EN' ? 'ENG' : $fcc_language_code;
                        ?>
                        <a href="<?= $new_url ?>" class="fcc-footer-lang-option <?= $language['name'] == \Altum\Language::$name ? 'is-active' : null ?>" data-set-language="<?= $language['name'] ?>" title="<?= $language['name'] ?>">
                            <span class="fcc-footer-lang-flag"><?= $language['language_flag'] ?: '🌐' ?></span>
                            <span class="fcc-footer-lang-code"><?= $fcc_language_short_label ?></span>
                        </a>
                    <?php endif ?>
                <?php endforeach ?>
            </div>

        <?php ob_start() ?>
            <script>
                'use strict';

                document.querySelectorAll('[data-set-language]').forEach(element => element.addEventListener('click', event => {
                    let language = event.currentTarget.getAttribute('data-set-language');
                    set_cookie(`set_language`, language, 90, <?= json_encode(COOKIE_PATH) ?>);
                }));
            </script>
            <?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
        <?php endif ?>

        <?php if(\Altum\Router::$controller_settings['currency_switcher'] && count((array) settings()->payment->currencies ?? []) > 1): ?>
            <div class="dropdown mr-3 ml-lg-3 mr-lg-0">
                <button type="button" class="btn btn-link text-decoration-none p-0" id="currency_switch" data-tooltip data-tooltip-hide-on-click title="<?= l('global.choose_currency') ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-fw fa-sm fa-money-check-alt"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="currency_switch">
                    <?php foreach((array) settings()->payment->currencies as $currency => $currency_data): ?>
                        <a href="#" class="dropdown-item" data-set-currency="<?= $currency ?>">
                            <?php if($currency == currency()): ?>
                                <i class="fas fa-fw fa-sm fa-check mr-2 text-success"></i>
                            <?php else: ?>
                                <span class="fas fa-fw text-muted mr-2"><?= $currency_data->symbol ?: '&nbsp;' ?></span>
                            <?php endif ?>

                            <?= $currency ?>
                        </a>
                    <?php endforeach ?>
                </div>
            </div>

        <?php ob_start() ?>
            <script>
                'use strict';

                document.querySelectorAll('[data-set-currency]').forEach(element => element.addEventListener('click', event => {
                    let currency = event.currentTarget.getAttribute('data-set-currency');
                    set_cookie(`set_currency`, currency, 90, <?= json_encode(COOKIE_PATH) ?>);
                    window.location.reload();
                    event.preventDefault();
                }));
            </script>
            <?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
        <?php endif ?>

        <?php if(is_logged_in() && ((user()->type == 1 && settings()->main->admin_spotlight_is_enabled) || (settings()->main->user_spotlight_is_enabled && user()->type == 0))): ?>
            <div class="mr-3 ml-lg-3 mr-lg-0">
                <button type="button" class="btn btn-link text-decoration-none p-0" data-toggle="tooltip" title="<?= l('global.spotlight.tooltip') ?>" aria-label="<?= l('global.spotlight.tooltip') ?>" onclick="spotlight_display()" data-tooltip-hide-on-click>
                    <i class="fas fa-fw fa-sm fa-search"></i>
                </button>
            </div>
        <?php endif ?>

        <?php /* Custom code: FC-2026-02-26: theme switch removed from footer (dark mode fixed) */ ?>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg mb-3 mb-lg-0">
        <ul class="list-style-none d-flex flex-column flex-lg-row flex-wrap m-0">
            <?php if(settings()->content->blog_is_enabled): ?>
                <li class="mb-2 mr-lg-3"><a href="<?= url('blog') ?>"><?= l('blog.menu') ?></a></li>
            <?php endif ?>

            <?php if(settings()->payment->is_enabled): ?>
                <?php if(\Altum\Plugin::is_active('affiliate') && settings()->affiliate->is_enabled): ?>
                    <li class="mb-2 mr-lg-3"><a href="<?= url('affiliate') ?>"><?= l('affiliate.menu') ?></a></li>
                <?php endif ?>
            <?php endif ?>

            <?php if(settings()->email_notifications->contact && !empty(settings()->email_notifications->emails)): ?>
                <li class="mb-2 mr-lg-3"><a href="<?= url('contact') ?>"><?= l('contact.menu') ?></a></li>
            <?php endif ?>

            <?php if(settings()->cookie_consent->is_enabled): ?>
                <li class="mb-2 mr-lg-3"><a href="#" data-cc="show-preferencesModal"><?= l('global.cookie_consent.menu') ?></a></li>
            <?php endif ?>

            <?php if(\Altum\Plugin::is_active('push-notifications') && settings()->push_notifications->is_enabled && (is_logged_in() || (!is_logged_in() && settings()->push_notifications->guests_is_enabled))): ?>
                <li class="mb-2 mr-lg-3"><a href="#" data-toggle="modal" data-target="#push_notifications_modal"><?= l('push_notifications_modal.menu') ?></a></li>
            <?php endif ?>

            <?php if (!empty($data->pages)): ?>
                <?php foreach($data->pages as $row): ?>
                    <li class="mb-2 mr-lg-3">
                        <a href="<?= $row->url ?>" target="<?= $row->target ?>">
                            <?php if($row->icon): ?>
                                <i class="<?= $row->icon ?> fa-fw fa-sm mr-1"></i>
                            <?php endif ?>

                            <?= $row->title ?>
                        </a>
                    </li>
                <?php endforeach ?>
            <?php endif ?>
        </ul>
    </div>
</div>
