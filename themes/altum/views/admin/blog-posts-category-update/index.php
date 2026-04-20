<?php defined('ALTUMCODE') || die() ?>

<?php if(settings()->main->breadcrumbs_is_enabled): ?>
<nav aria-label="breadcrumb">
    <ol class="custom-breadcrumbs small">
        <li>
            <a href="<?= url('admin/blog-posts-categories') ?>"><?= l('admin_blog_posts_categories.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
        </li>
        <li class="active" aria-current="page"><?= l('admin_blog_posts_category_update.breadcrumb') ?></li>
    </ol>
</nav>
<?php endif ?>

<div class="d-flex justify-content-between mb-4">
    <h1 class="h3 mb-0 text-truncate"><i class="fas fa-fw fa-xs fa-book text-primary-900 mr-2"></i> <?= l('admin_blog_posts_categories.header') ?></h1>

    <?= include_view(THEME_PATH . 'views/admin/blog-posts-categories/admin_blog_posts_category_dropdown_button.php', ['id' => $data->blog_posts_category->blog_posts_category_id, 'resource_name' => $data->blog_posts_category->title, 'url' => $data->blog_posts_category->url, 'language' => $data->blog_posts_category->language]) ?>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<div class="card <?= \Altum\Alerts::has_field_errors() ? 'border-danger' : null ?>">
    <div class="card-body">
        <form action="" method="post" role="form">
            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

            <!-- Custom code -->
            <div class="form-group">
                <label for="parent_id"><i class="fa fa-fw fa-sm fa-sort text-muted mr-1"></i> <?= l('admin_blog.main.blog_posts_parent_category_id') ?></label>
                <select id="parent_id" name="parent_id" class="custom-select">                
                    <option></option>
                    <?php foreach($data->blog_posts_parent_categories as $key => $value): ?>                        
                        <option value="<?= $value->blog_posts_category_id ?>" <?= $value->blog_posts_category_id == $data->blog_posts_category->blog_posts_parent_id ? 'selected="selected"' : null ?>><?= $value->title ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <!-- /Custom code -->

            <div class="form-group">
                <label for="url"><i class="fas fa-fw fa-sm fa-bolt text-muted mr-1"></i> <?= l('global.url') ?></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><?= remove_url_protocol_from_url(SITE_URL) . 'blog/category/' ?></span>
                    </div>

                    <input id="url" type="text" name="url" class="form-control <?= \Altum\Alerts::has_field_errors('url') ? 'is-invalid' : null ?>" placeholder="<?= l('global.url_slug_placeholder') ?>" value="<?= $data->blog_posts_category->url ?>" onchange="update_this_value(this, get_slug)" onkeyup="update_this_value(this, get_slug)" maxlength="256" required="required" />
                    <?= \Altum\Alerts::output_field_error('url') ?>
                </div>
            </div>

            <div class="form-group">
                <label for="title"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('admin_blog.title') ?></label>
                <input id="title" type="text" name="title" class="form-control <?= \Altum\Alerts::has_field_errors('title') ? 'is-invalid' : null ?>" value="<?= $data->blog_posts_category->title ?>" maxlength="256" required="required" />
                <?= \Altum\Alerts::output_field_error('title') ?>
            </div>

            <!-- Custom code -->
            <div class="form-group">
                <label for="description"><i class="fa fa-fw fa-sm fa-pen text-muted mr-1"></i> <?= l('admin_blog.main.description') ?></label>
                <textarea id="description" name="description" class="form-control" rows="3" maxlength="256"><?= e($data->blog_posts_category->description) ?></textarea>
                <small class="form-text text-muted">Kratki meta opis kategorije. Za duži shop sadržaj koristite dolje “Shop hub sadržaj”.</small>
            </div>
            <!-- /Custom code -->

            <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4" type="button" data-toggle="collapse" data-target="#shop_context_container" aria-expanded="false" aria-controls="shop_context_container">
                <i class="fas fa-fw fa-store fa-sm mr-1"></i> Shop hub sadržaj
            </button>

            <div class="collapse" id="shop_context_container">
                <?php if(!$data->blog_category_shop_context_supported): ?>
                    <div class="alert alert-info">
                        Strukturirana shop hub polja će biti aktivna nakon lokalne SQL migracije za stupac <code>blog_posts_categories.shop_context</code>.
                    </div>
                <?php endif ?>

                <?php if($data->blog_category_shop_context_supported): ?>
                    <div class="alert alert-secondary">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                            <div>
                                <strong>Popunjenost shop hub SEO/CRO sadržaja:</strong>
                                <?= (int) ($data->shop_context_completion->filled ?? 0) ?>/<?= (int) ($data->shop_context_completion->total ?? 0) ?>
                            </div>

                            <div class="small text-muted mt-2 mt-lg-0">
                                <?= !empty($data->shop_context_completion->is_complete) ? 'Glavna polja su popunjena.' : 'Dovršite još prazna polja kako bi kategorija bila jača za SEO i korisničko iskustvo.' ?>
                            </div>
                        </div>

                        <?php if(!empty($data->shop_context_completion->checks)): ?>
                            <div class="mt-3 d-flex flex-wrap">
                                <?php foreach($data->shop_context_completion->checks as $completion_check): ?>
                                    <span class="badge badge-<?= !empty($completion_check['filled']) ? 'success' : 'light' ?> mr-2 mb-2 px-3 py-2">
                                        <?= !empty($completion_check['filled']) ? 'Popunjeno' : 'Nedostaje' ?>: <?= e($completion_check['label']) ?>
                                    </span>
                                <?php endforeach ?>
                            </div>
                        <?php endif ?>
                    </div>
                <?php endif ?>

                <div class="form-group">
                    <label for="shop_context_page_role"><i class="fas fa-fw fa-sm fa-sitemap text-muted mr-1"></i> Tip kategorije</label>
                    <select id="shop_context_page_role" name="shop_context_page_role" class="custom-select">
                        <option value="" <?= ($data->shop_context_form->page_role ?? '') === '' ? 'selected="selected"' : null ?>>Standardno</option>
                        <option value="shop_hub" <?= ($data->shop_context_form->page_role ?? '') === 'shop_hub' ? 'selected="selected"' : null ?>>Shop hub / category landing</option>
                    </select>
                    <small class="form-text text-muted">Aktivira jači “shop” prikaz kategorije s dodatnim hero, vodičem, FAQ-om i filtrima.</small>
                </div>

                <div class="row">
                    <div class="col-12 col-lg-4">
                        <div class="form-group">
                            <label for="shop_context_hero_badge"><i class="fas fa-fw fa-sm fa-tag text-muted mr-1"></i> Hero badge</label>
                            <input id="shop_context_hero_badge" type="text" name="shop_context_hero_badge" class="form-control" value="<?= e($data->shop_context_form->hero_badge ?? '') ?>" maxlength="80" />
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="form-group">
                            <label for="shop_context_product_count_label"><i class="fas fa-fw fa-sm fa-list text-muted mr-1"></i> Label statistike 1</label>
                            <input id="shop_context_product_count_label" type="text" name="shop_context_product_count_label" class="form-control" value="<?= e($data->shop_context_form->product_count_label ?? '') ?>" maxlength="120" placeholder="Npr. Vodiča za proizvode" />
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="form-group">
                            <label for="shop_context_shop_ready_count_label"><i class="fas fa-fw fa-sm fa-shopping-cart text-muted mr-1"></i> Label statistike 2</label>
                            <input id="shop_context_shop_ready_count_label" type="text" name="shop_context_shop_ready_count_label" class="form-control" value="<?= e($data->shop_context_form->shop_ready_count_label ?? '') ?>" maxlength="120" placeholder="Npr. Dostupno za narudžbu" />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="shop_context_market_count_label"><i class="fas fa-fw fa-sm fa-globe text-muted mr-1"></i> Label statistike 3</label>
                    <input id="shop_context_market_count_label" type="text" name="shop_context_market_count_label" class="form-control" value="<?= e($data->shop_context_form->market_count_label ?? '') ?>" maxlength="120" placeholder="Npr. Podržana tržišta" />
                </div>

                <div class="form-group">
                    <label for="shop_context_hero_subtitle"><i class="fas fa-fw fa-sm fa-align-left text-muted mr-1"></i> Hero podnaslov</label>
                    <textarea id="shop_context_hero_subtitle" name="shop_context_hero_subtitle" class="form-control" rows="3"><?= e($data->shop_context_form->hero_subtitle ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="shop_context_hero_note"><i class="fas fa-fw fa-sm fa-info-circle text-muted mr-1"></i> Napomena ispod statistika</label>
                    <textarea id="shop_context_hero_note" name="shop_context_hero_note" class="form-control" rows="3"><?= e($data->shop_context_form->hero_note ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_meta_title"><i class="fas fa-fw fa-sm fa-heading text-muted mr-1"></i> SEO meta naslov</label>
                            <input id="shop_context_meta_title" type="text" name="shop_context_meta_title" class="form-control" value="<?= e($data->shop_context_form->meta_title ?? '') ?>" maxlength="180" placeholder="Ako ostane prazno, koristi se pametan fallback." />
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_subcategories_title"><i class="fas fa-fw fa-sm fa-project-diagram text-muted mr-1"></i> Naslov podkategorija</label>
                            <input id="shop_context_subcategories_title" type="text" name="shop_context_subcategories_title" class="form-control" value="<?= e($data->shop_context_form->subcategories_title ?? '') ?>" maxlength="120" placeholder="Npr. Povezane podkategorije" />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="shop_context_meta_description"><i class="fas fa-fw fa-sm fa-align-left text-muted mr-1"></i> SEO meta opis</label>
                    <textarea id="shop_context_meta_description" name="shop_context_meta_description" class="form-control" rows="3" placeholder="Ako ostane prazno, koristi se opis kategorije ili generirani sažetak."><?= e($data->shop_context_form->meta_description ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="shop_context_meta_keywords"><i class="fas fa-fw fa-sm fa-key text-muted mr-1"></i> Meta ključne riječi</label>
                    <input id="shop_context_meta_keywords" type="text" name="shop_context_meta_keywords" class="form-control" value="<?= e($data->shop_context_form->meta_keywords ?? '') ?>" maxlength="255" placeholder="npr. forever proizvodi, kolagen, njega kože" />
                    <small class="form-text text-muted">Ako ostane prazno, sustav će složiti fallback iz naziva kategorije, filtera i istaknutih vodiča.</small>
                </div>

                <div class="form-group">
                    <label for="shop_context_guide_title"><i class="fas fa-fw fa-sm fa-compass text-muted mr-1"></i> Naslov vodiča za odabir</label>
                    <input id="shop_context_guide_title" type="text" name="shop_context_guide_title" class="form-control" value="<?= e($data->shop_context_form->guide_title ?? '') ?>" maxlength="160" />
                </div>

                <div class="form-group">
                    <label for="shop_context_guide_items"><i class="fas fa-fw fa-sm fa-columns text-muted mr-1"></i> Kartice vodiča</label>
                    <textarea id="shop_context_guide_items" name="shop_context_guide_items" class="form-control" rows="6" placeholder="Krenite od potrebe | Odaberite ono što je korisniku trenutno najvažnije.&#10;Otvorite vodič | Svaki vodič daje brz pregled i sljedeći korak."><?= e($data->shop_context_form->guide_items ?? '') ?></textarea>
                    <small class="form-text text-muted">Jedan red = jedna kartica. Format: <code>Naslov | Tekst</code>.</small>
                </div>

                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_featured_title"><i class="fas fa-fw fa-sm fa-star text-muted mr-1"></i> Naslov istaknutih proizvoda</label>
                            <input id="shop_context_featured_title" type="text" name="shop_context_featured_title" class="form-control" value="<?= e($data->shop_context_form->featured_title ?? '') ?>" maxlength="160" />
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_featured_post_urls"><i class="fas fa-fw fa-sm fa-link text-muted mr-1"></i> Istaknuti product slugovi</label>
                            <textarea id="shop_context_featured_post_urls" name="shop_context_featured_post_urls" class="form-control" rows="4" placeholder="marine-collagen&#10;forever-vitamin-c&#10;aloe-msm-gel"><?= e($data->shop_context_form->featured_post_urls ?? '') ?></textarea>
                            <small class="form-text text-muted">Jedan red = jedan slug blog proizvoda bez pune domene.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-lg-4">
                        <div class="form-group">
                            <label for="shop_context_discovery_eyebrow"><i class="fas fa-fw fa-sm fa-filter text-muted mr-1"></i> Eyebrow discovery sekcije</label>
                            <input id="shop_context_discovery_eyebrow" type="text" name="shop_context_discovery_eyebrow" class="form-control" value="<?= e($data->shop_context_form->discovery_eyebrow ?? '') ?>" maxlength="80" />
                        </div>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div class="form-group">
                            <label for="shop_context_discovery_title"><i class="fas fa-fw fa-sm fa-search text-muted mr-1"></i> Naslov discovery sekcije</label>
                            <input id="shop_context_discovery_title" type="text" name="shop_context_discovery_title" class="form-control" value="<?= e($data->shop_context_form->discovery_title ?? '') ?>" maxlength="180" />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="shop_context_discovery_subtitle"><i class="fas fa-fw fa-sm fa-align-left text-muted mr-1"></i> Podnaslov discovery sekcije</label>
                    <textarea id="shop_context_discovery_subtitle" name="shop_context_discovery_subtitle" class="form-control" rows="3"><?= e($data->shop_context_form->discovery_subtitle ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="shop_context_filter_chips"><i class="fas fa-fw fa-sm fa-tags text-muted mr-1"></i> Dodatni filter chipovi</label>
                    <textarea id="shop_context_filter_chips" name="shop_context_filter_chips" class="form-control" rows="5" placeholder="Kolagen | marine collagen, collagen, kolagen&#10;Njega kože | lotion, oil, vitamin c, bakuchiol"><?= e($data->shop_context_form->filter_chips ?? '') ?></textarea>
                    <small class="form-text text-muted">Jedan red = jedan filter. Format: <code>Label | pojam 1, pojam 2, pojam 3</code>.</small>
                </div>

                <div class="form-group">
                    <label for="shop_context_seo_title"><i class="fas fa-fw fa-sm fa-file-alt text-muted mr-1"></i> SEO sadržaj naslov</label>
                    <input id="shop_context_seo_title" type="text" name="shop_context_seo_title" class="form-control" value="<?= e($data->shop_context_form->seo_title ?? '') ?>" maxlength="180" />
                </div>

                <div class="form-group">
                    <label for="shop_context_seo_paragraphs"><i class="fas fa-fw fa-sm fa-paragraph text-muted mr-1"></i> SEO odlomci</label>
                    <textarea id="shop_context_seo_paragraphs" name="shop_context_seo_paragraphs" class="form-control" rows="6" placeholder="Svaki red je jedan odlomak za donji SEO blok."><?= e($data->shop_context_form->seo_paragraphs ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="shop_context_faq_title"><i class="fas fa-fw fa-sm fa-question text-muted mr-1"></i> FAQ naslov</label>
                    <input id="shop_context_faq_title" type="text" name="shop_context_faq_title" class="form-control" value="<?= e($data->shop_context_form->faq_title ?? '') ?>" maxlength="160" />
                </div>

                <div class="form-group">
                    <label for="shop_context_faq_items"><i class="fas fa-fw fa-sm fa-question-circle text-muted mr-1"></i> FAQ pitanja i odgovori</label>
                    <textarea id="shop_context_faq_items" name="shop_context_faq_items" class="form-control" rows="6" placeholder="Kako najbrže suziti izbor? | Krenite od glavne potrebe korisnika i otvorite nekoliko vodiča iste teme."><?= e($data->shop_context_form->faq_items ?? '') ?></textarea>
                    <small class="form-text text-muted">Jedan red = jedno pitanje i odgovor. Format: <code>Pitanje | Odgovor</code>.</small>
                </div>
            </div>

            <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4" type="button" data-toggle="collapse" data-target="#advanced_container" aria-expanded="false" aria-controls="advanced_container">
                <i class="fas fa-fw fa-user-tie fa-sm mr-1"></i> <?= l('admin_blog.advanced') ?>
            </button>

            <div class="collapse" id="advanced_container">
                <div class="form-group">
                    <label for="language"><i class="fas fa-fw fa-sm fa-language text-muted mr-1"></i> <?= l('global.language') ?></label>
                    <select id="language" name="language" class="custom-select">
                        <option value="" <?= !$data->blog_posts_category->language ? 'selected="selected"' : null ?>><?= l('global.all') ?></option>
                        <?php foreach(\Altum\Language::$languages as $language): ?>
                            <option value="<?= $language['name'] ?>" <?= $data->blog_posts_category->language == $language['name'] ? 'selected="selected"' : null ?>><?= $language['name'] ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <!-- Custom code -->
                <div class="form-group">                
                    <label for="visibility"><i class="fa fa-fw fa-sm fa-lock text-muted mr-1"></i> <?= l('admin_blog.main.visibility') ?></label>
                    <select id="visibility" name="visibility" class="custom-select">                    
                        <?php foreach(\Altum\Access::$levels as $level): ?>
                            <option value="<?= $level['value'] ?>" <?= $data->blog_posts_category->visibility == $level['value'] ? 'selected="selected"' : null ?>><?= $level['name'] ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="form-group">                
                    <div class="form-group custom-control custom-switch mt-4">
                        <input id="show_share_links" name="show_share_links" type="checkbox" class="custom-control-input" <?= $data->blog_posts_category->show_share_links ? 'checked="checked"' : null ?>>
                        <label class="custom-control-label" for="show_share_links"><i class="fa fa-fw fa-sm fa-share-alt text-muted mr-1"></i> <?= l('admin_blog.main.show_share_links') ?></label>
                    </div>
                </div>
                <!-- /Custom code -->

                <div class="form-group">
                    <label for="order"><i class="fas fa-fw fa-sm fa-sort text-muted mr-1"></i> <?= l('global.order') ?></label>
                    <input id="order" type="number" name="order" class="form-control" value="<?= $data->blog_posts_category->order ?>" />
                    <small class="form-text text-muted"><?= l('global.order_int_help') ?></small>
                </div>
            </div>

            <button type="submit" name="submit" class="btn btn-lg btn-block btn-primary mt-4"><?= l('global.update') ?></button>
        </form>
    </div>
</div>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/universal_delete_modal_url.php', [
    'name' => 'blog_posts_category',
    'resource_id' => 'blog_posts_category_id',
    'has_dynamic_resource_name' => true,
    'path' => 'admin/blog-posts-categories/delete/'
]), 'modals'); ?>
