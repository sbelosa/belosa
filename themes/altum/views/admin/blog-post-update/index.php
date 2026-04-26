<?php defined('ALTUMCODE') || die() ?>

<?php if(settings()->main->breadcrumbs_is_enabled): ?>
<nav aria-label="breadcrumb">
    <ol class="custom-breadcrumbs small">
        <li>
            <a href="<?= url('admin/blog-posts') ?>"><?= l('admin_blog_posts.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
        </li>
        <li class="active" aria-current="page"><?= l('admin_blog_post_update.breadcrumb') ?></li>
    </ol>
</nav>
<?php endif ?>

<div class="d-flex justify-content-between mb-4">
    <h1 class="h3 mb-0 text-truncate"><i class="fas fa-fw fa-xs fa-paste text-primary-900 mr-2"></i> <?= l('admin_blog_post_update.header') ?></h1>

    <?= include_view(THEME_PATH . 'views/admin/blog-posts/admin_blog_post_dropdown_button.php', ['id' => $data->blog_post->blog_post_id, 'resource_name' => $data->blog_post->title, 'url' => $data->blog_post->url, 'language' => $data->blog_post->language]) ?>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<div class="card <?= \Altum\Alerts::has_field_errors() ? 'border-danger' : null ?>">
    <div class="card-body">

        <form id="blog_post_update_form" action="" method="post" role="form" enctype="multipart/form-data">
            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

            <div class="form-group">
                <label for="url"><i class="fas fa-fw fa-sm fa-bolt text-muted mr-1"></i> <?= l('global.url') ?></label>
                <div class="input-group">
                    <div id="url_prepend" class="input-group-prepend">
                        <span class="input-group-text"><?= remove_url_protocol_from_url(SITE_URL) . 'blog/' ?></span>
                    </div>

                    <input id="url" type="text" name="url" class="form-control <?= \Altum\Alerts::has_field_errors('url') ? 'is-invalid' : null ?>" placeholder="<?= l('global.url_slug_placeholder') ?>" value="<?= $data->blog_post->url ?>" onchange="update_this_value(this, get_slug)" onkeyup="update_this_value(this, get_slug)" maxlength="256" required="required" />
                    <?= \Altum\Alerts::output_field_error('url') ?>
                </div>
            </div>

            <div class="form-group">
                <label for="title"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('admin_blog.title') ?></label>
                <input id="title" type="text" name="title" class="form-control <?= \Altum\Alerts::has_field_errors('title') ? 'is-invalid' : null ?>" value="<?= $data->blog_post->title ?>" maxlength="256" required="required" />
                <?= \Altum\Alerts::output_field_error('title') ?>
            </div>

            <div class="form-group">
                <label for="description"><i class="fas fa-fw fa-sm fa-pen text-muted mr-1"></i> <?= l('global.description') ?></label>
                <input id="description" type="text" name="description" class="form-control" value="<?= $data->blog_post->description ?>" maxlength="256" />
            </div>

            <div class="form-group" data-file-image-input-wrapper data-file-input-wrapper-size-limit="<?= get_max_upload() ?>" data-file-input-wrapper-size-limit-error="<?= sprintf(l('global.error_message.file_size_limit'), get_max_upload()) ?>">
                <label for="image"><i class="fas fa-fw fa-sm fa-image text-muted mr-1"></i> <?= l('admin_blog.image') ?></label>
                <?= include_view(THEME_PATH . 'views/partials/file_image_input.php', ['uploads_file_key' => 'blog', 'file_key' => 'image', 'already_existing_image' => $data->blog_post->image]) ?>
                <?= \Altum\Alerts::output_field_error('image') ?>
                <small class="form-text text-muted"><?= sprintf(l('global.accessibility.whitelisted_file_extensions'), \Altum\Uploads::get_whitelisted_file_extensions_accept('blog')) . ' ' . sprintf(l('global.accessibility.file_size_limit'), get_max_upload()) ?></small>
            </div>

            <div class="form-group d-none"> <!-- Custom code -->
                <label for="editor"><i class="fas fa-fw fa-sm fa-newspaper text-muted mr-1"></i> <?= l('admin_blog.editor') ?></label>
                <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                    <div class="p-2 col-12 col-lg-4">
                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= $data->blog_post->editor == 'wysiwyg' ? 'active"' : null?>">
                            <input type="radio" name="editor" value="wysiwyg" class="custom-control-input" <?= $data->blog_post->editor == 'wysiwyg' ? 'checked="checked"' : null?> required="required" />
                            <i class="fas fa-eye fa-fw fa-sm mr-1"></i> <?= l('admin_blog.editor_wysiwyg') ?>
                        </label>
                    </div>

                    <div class="p-2 col-12 col-lg-4">
                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= $data->blog_post->editor == 'blocks' ? 'active"' : null?>">
                            <input type="radio" name="editor" value="blocks" class="custom-control-input" <?= $data->blog_post->editor == 'blocks' ? 'checked="checked"' : null?> required="required" />
                            <i class="fas fa-th-large fa-fw fa-sm mr-1"></i> <?= l('admin_blog.editor_blocks') ?>
                        </label>
                    </div>

                    <div class="p-2 col-12 col-lg-4">
                        <label class="btn btn-light btn-block font-size-small mb-0 text-truncate <?= $data->blog_post->editor == 'raw' ? 'active"' : null?>">
                            <input type="radio" name="editor" value="raw" class="custom-control-input" <?= $data->blog_post->editor == 'raw' ? 'checked="checked"' : null?> required="required" />
                            <i class="fas fa-code fa-fw fa-sm mr-1"></i> <?= l('admin_blog.editor_raw') ?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="content"><i class="fas fa-fw fa-sm fa-paragraph text-muted mr-1"></i> <?= l('admin_blog.content') ?></label>             
                <!-- Custom code -->                
                <textarea name="content" id="content" class="form-control editor" style="height: 15rem;"><?= e($data->blog_post->content) ?></textarea>
                <!-- /Custom code -->
            </div>

            <!-- Custom code -->
            <div class="form-group">
                <label for="blog_posts_category_id"><i class="fa fa-fw fa-sm fa-map text-muted mr-1"></i> <?= l('admin_blog.main.blog_posts_category_id') ?></label>
                <select id="blog_posts_category_id" name="blog_posts_category_id" class="custom-select" multiple="multiple">
                    <?php foreach($data->blog_posts_main_categories as $row): ?>
                        <option value="<?= $row->blog_posts_category_id ?>" <?= $data->blog_post->blog_posts_category_id == $row->blog_posts_category_id ? 'selected="selected"' : null ?>><?= $row->title ?></option>
                        <?php foreach($data->blog_posts_parents as $parent): ?>
                            <?php if ($row->blog_posts_category_id == $parent->blog_posts_parent_id): ?>
                                <option class="ml-3" value="<?= $parent->blog_posts_category_id ?>" <?= $data->blog_post->blog_posts_category_id == $parent->blog_posts_category_id ? 'selected="selected"' : null ?>><?= $parent->title ?></option>
                                <?php foreach($data->blog_posts_subcategories as $sub): ?>
                                    <?php if ($parent->blog_posts_category_id == $sub->blog_posts_parent_id): ?>
                                        <option class="ml-4" value="<?= $sub->blog_posts_category_id ?>" <?= $data->blog_post->blog_posts_category_id == $sub->blog_posts_category_id ? 'selected="selected"' : null ?>><?= $sub->title ?></option>
                                    <?php endif ?>
                                <?php endforeach ?>
                            <?php endif ?>
                        <?php endforeach ?>                                                
                    <?php endforeach ?>
                    <option value="" <?= !$data->blog_post->blog_posts_category_id ? 'selected="selected"' : null ?>><?= l('admin_blog.main.blog_posts_category_id_null') ?></option>
                </select>
            </div>            

            <div class="form-group">
                <label for="sku"><i class="fa fa-fw fa-sm fa-shopping-cart text-muted mr-1"></i> <?= l('admin_blog.main.webshop_links.sku') ?></label>
                <input id="sku" type="text" name="sku" class="form-control" value="<?= isset($data->blog_post->sku) ? $data->blog_post->sku : null ?>" />
            </div>
            <div class="form-group">
                <?php $countries_array = ['hr', 'ba', 'al', 'si', 'rs', 'at', 'au', 'ca', 'de', 'ie', 'lu', 'nl', 'no', 'pl', 'se', 'gb', 'us', 'qa', 'ch', 'ae']; ?>
                <?php /* Custom code: FC-2026-03-09: custom webshop link labels for selected countries */ ?>
                <?php $country_labels = [
                    'hr' => 'Forever HR - Hrvatska',
                    'ba' => 'Forever BA - Bosna i Hercegovina',
                    'al' => 'Forever AL - Albanija',
                    'si' => 'Forever SI - Slovenija',
                    'rs' => 'Forever RS - Srbija',
                    'at' => 'Forever AT - Forever Austria',
                    'au' => 'Forever AU - Forever Australia',
                    'ca' => 'Forever CA - Kanada',
                    'de' => 'Forever DE - Njemačka',
                    'ie' => 'Forever IE - Forever Irska',
                    'lu' => 'Forever LU - Luksemburg',
                    'nl' => 'Forever NL - Nizozemska',
                    'no' => 'Forever NO - Forever Norveška',
                    'pl' => 'Forever PL - Poljska',
                    'se' => 'Forever SE - Forever Švedska',
                    'gb' => 'Forever GB - Ujedinjeno Kraljevstvo',
                    'us' => 'Forever US - Sjedinjene Američke Države',
                    'qa' => 'Forever QA - Katar',
                    'ch' => 'Forever CH - Švicarska',
                    'ae' => 'Forever AE - Forever Emirati',
                ]; ?>
                <?php /* /Custom code: FC-2026-03-09 */ ?>
                <?php foreach ($countries_array as $country): ?>                     
                    <?php /* Custom code: FC-2026-03-09: render mapped labels for selected countries */ ?>
                    <label for="webshop_links_hr"><i class="fa fa-fw fa-sm fa-shopping-cart text-muted mr-1"></i> <?= $country_labels[$country] ?? ('Forever ' . strtoupper($country)) ?></label>
                    <?php /* /Custom code: FC-2026-03-09 */ ?>
                    <div class="input-group">                          
                        <input id="webshop_links_<?= $country ?>" type="text" name="webshop_links_<?= $country ?>" class="form-control" value="<?= isset($data->webshop_links->$country) ? $data->webshop_links->$country : null ?>" />                    
                        <div class="input-group-append">
                            <?php if(isset($data->webshop_links->$country) && !empty($data->webshop_links->$country)): ?>
                                <button class="btn btn-outline-secondary" type="button"><a href="<?= $data->webshop_links->$country ?>" target="_blank"><i class="fa fa-fw fa-link"></i></a></button>                        
                            <?php endif; ?>
                        </div>
                    </div>                   
                <?php endforeach; ?>                    
            </div>

            <!-- /Custom code -->

            <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4" type="button" data-toggle="collapse" data-target="#shop_context_container" aria-expanded="false" aria-controls="shop_context_container">
                <i class="fas fa-fw fa-store fa-sm mr-1"></i> Shop landing sadržaj
            </button>

            <div class="collapse" id="shop_context_container">
                <?php if(!$data->blog_shop_context_supported): ?>
                    <div class="alert alert-info">
                        Strukturirana shop polja će biti aktivna nakon lokalne SQL migracije za stupac <code>blog_posts.shop_context</code>.
                    </div>
                <?php endif ?>

                <div class="form-group">
                    <label for="shop_context_page_role"><i class="fas fa-fw fa-sm fa-sitemap text-muted mr-1"></i> Tip landing stranice</label>
                    <select id="shop_context_page_role" name="shop_context_page_role" class="custom-select">
                        <option value="" <?= ($data->shop_context_form->page_role ?? '') === '' ? 'selected="selected"' : null ?>>Standardno</option>
                        <option value="product" <?= ($data->shop_context_form->page_role ?? '') === 'product' ? 'selected="selected"' : null ?>>Product landing</option>
                        <option value="business_start" <?= ($data->shop_context_form->page_role ?? '') === 'business_start' ? 'selected="selected"' : null ?>>Start suradnje / Start paket</option>
                    </select>
                    <small class="form-text text-muted">Koristi se za posebne layoute poput Start paketa i jačih product landing stranica.</small>
                </div>

                <div class="form-group">
                    <label for="shop_context_trust_note"><i class="fas fa-fw fa-sm fa-shield-alt text-muted mr-1"></i> Kratka napomena ispod hero sekcije</label>
                    <textarea id="shop_context_trust_note" name="shop_context_trust_note" class="form-control" rows="3" placeholder="Npr. Ova stranica pomaže brzo razumjeti što korisnik dobiva i koji je najbolji sljedeći korak."><?= e($data->shop_context_form->trust_note ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_meta_title"><i class="fas fa-fw fa-sm fa-heading text-muted mr-1"></i> SEO meta naslov</label>
                            <input id="shop_context_meta_title" type="text" name="shop_context_meta_title" class="form-control" value="<?= e($data->shop_context_form->meta_title ?? '') ?>" maxlength="180" placeholder="Ako ostane prazno, koristi se SEO fallback prema naslovu i kategoriji." />
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_meta_keywords"><i class="fas fa-fw fa-sm fa-key text-muted mr-1"></i> SEO meta ključne riječi</label>
                            <input id="shop_context_meta_keywords" type="text" name="shop_context_meta_keywords" class="form-control" value="<?= e($data->shop_context_form->meta_keywords ?? '') ?>" maxlength="255" placeholder="Ako ostane prazno, koristi se kombinacija proizvoda, kategorije i aliasa." />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="shop_context_meta_description"><i class="fas fa-fw fa-sm fa-align-left text-muted mr-1"></i> SEO meta opis</label>
                    <textarea id="shop_context_meta_description" name="shop_context_meta_description" class="form-control" rows="3" placeholder="Ako ostane prazno, koristi se opis proizvoda ili generirani SEO sažetak."><?= e($data->shop_context_form->meta_description ?? '') ?></textarea>
                </div>

                <div class="card border rounded-0 mb-4">
                    <div class="card-body">
                        <h3 class="h6 mb-3"><i class="fas fa-fw fa-sm fa-receipt text-muted mr-1"></i> Merchant schema podaci</h3>
                        <p class="small text-muted mb-3">Popunite samo ako imate točnu aktivnu cijenu i pravila koja su prikazana/primjenjiva za taj proizvod. Ako cijena nije upisana, stranica neće slati <code>Offer</code> schema kako Google ne bi prijavio grešku.</p>

                        <div class="row">
                            <div class="col-12 col-lg-6">
                                <div class="form-group">
                                    <label for="shop_context_schema_offer_price"><i class="fas fa-fw fa-sm fa-tag text-muted mr-1"></i> Aktivna cijena</label>
                                    <input id="shop_context_schema_offer_price" type="text" name="shop_context_schema_offer_price" class="form-control" value="<?= e($data->shop_context_form->schema_offer_price ?? '') ?>" maxlength="32" placeholder="Npr. 29.90" />
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="form-group">
                                    <label for="shop_context_schema_offer_currency"><i class="fas fa-fw fa-sm fa-coins text-muted mr-1"></i> Valuta</label>
                                    <input id="shop_context_schema_offer_currency" type="text" name="shop_context_schema_offer_currency" class="form-control" value="<?= e($data->shop_context_form->schema_offer_currency ?? '') ?>" maxlength="3" placeholder="Npr. EUR" />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 col-lg-4">
                                <div class="form-group">
                                    <label for="shop_context_schema_shipping_country"><i class="fas fa-fw fa-sm fa-globe text-muted mr-1"></i> Dostava zemlja</label>
                                    <input id="shop_context_schema_shipping_country" type="text" name="shop_context_schema_shipping_country" class="form-control" value="<?= e($data->shop_context_form->schema_shipping_country ?? '') ?>" maxlength="2" placeholder="Npr. HR" />
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="form-group">
                                    <label for="shop_context_schema_shipping_price"><i class="fas fa-fw fa-sm fa-truck text-muted mr-1"></i> Cijena dostave</label>
                                    <input id="shop_context_schema_shipping_price" type="text" name="shop_context_schema_shipping_price" class="form-control" value="<?= e($data->shop_context_form->schema_shipping_price ?? '') ?>" maxlength="32" placeholder="Npr. 0 ili 4.99" />
                                </div>
                            </div>

                            <div class="col-12 col-lg-2">
                                <div class="form-group">
                                    <label for="shop_context_schema_shipping_min_days">Min dana</label>
                                    <input id="shop_context_schema_shipping_min_days" type="text" name="shop_context_schema_shipping_min_days" class="form-control" value="<?= e($data->shop_context_form->schema_shipping_min_days ?? '') ?>" maxlength="3" placeholder="1" />
                                </div>
                            </div>

                            <div class="col-12 col-lg-2">
                                <div class="form-group">
                                    <label for="shop_context_schema_shipping_max_days">Max dana</label>
                                    <input id="shop_context_schema_shipping_max_days" type="text" name="shop_context_schema_shipping_max_days" class="form-control" value="<?= e($data->shop_context_form->schema_shipping_max_days ?? '') ?>" maxlength="3" placeholder="7" />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 col-lg-4">
                                <div class="form-group">
                                    <label for="shop_context_schema_return_country"><i class="fas fa-fw fa-sm fa-undo text-muted mr-1"></i> Povrat zemlja</label>
                                    <input id="shop_context_schema_return_country" type="text" name="shop_context_schema_return_country" class="form-control" value="<?= e($data->shop_context_form->schema_return_country ?? '') ?>" maxlength="2" placeholder="Npr. HR" />
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="form-group">
                                    <label for="shop_context_schema_return_days">Rok povrata u danima</label>
                                    <input id="shop_context_schema_return_days" type="text" name="shop_context_schema_return_days" class="form-control" value="<?= e($data->shop_context_form->schema_return_days ?? '') ?>" maxlength="3" placeholder="Npr. 14" />
                                </div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="form-group">
                                    <label for="shop_context_schema_return_fees">Trošak povrata</label>
                                    <input id="shop_context_schema_return_fees" type="text" name="shop_context_schema_return_fees" class="form-control" value="<?= e($data->shop_context_form->schema_return_fees ?? '') ?>" maxlength="64" placeholder="FreeReturn ili ReturnShippingFees" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_decision_title"><i class="fas fa-fw fa-sm fa-user-check text-muted mr-1"></i> Naslov sekcije “Za koga je”</label>
                            <input id="shop_context_decision_title" type="text" name="shop_context_decision_title" class="form-control" value="<?= e($data->shop_context_form->decision_title ?? '') ?>" maxlength="160" />
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_checks_title"><i class="fas fa-fw fa-sm fa-list-check text-muted mr-1"></i> Naslov sekcije “Na brzinu provjerite”</label>
                            <input id="shop_context_checks_title" type="text" name="shop_context_checks_title" class="form-control" value="<?= e($data->shop_context_form->checks_title ?? '') ?>" maxlength="160" />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="shop_context_summary_cards"><i class="fas fa-fw fa-sm fa-th-large text-muted mr-1"></i> Kartice sažetka</label>
                    <textarea id="shop_context_summary_cards" name="shop_context_summary_cards" class="form-control" rows="5" placeholder="Status | Forever poslovni suradnik&#10;Popust | Put prema 30% popusta"><?= e($data->shop_context_form->summary_cards ?? '') ?></textarea>
                    <small class="form-text text-muted">Jedan red = jedna kartica. Format: <code>Label | Vrijednost</code>.</small>
                </div>

                <div class="form-group">
                    <label for="shop_context_ideal_for"><i class="fas fa-fw fa-sm fa-bullseye text-muted mr-1"></i> Za koga je stranica</label>
                    <textarea id="shop_context_ideal_for" name="shop_context_ideal_for" class="form-control" rows="5" placeholder="Ako želite postati Forever poslovni suradnik.&#10;Ako tražite glavni korak za registraciju i početak."><?= e($data->shop_context_form->ideal_for ?? '') ?></textarea>
                    <small class="form-text text-muted">Jedan red = jedna točka koja se prikazuje u decision bloku.</small>
                </div>

                <div class="form-group">
                    <label for="shop_context_quick_checks"><i class="fas fa-fw fa-sm fa-check-double text-muted mr-1"></i> Brze provjere / ključne informacije</label>
                    <textarea id="shop_context_quick_checks" name="shop_context_quick_checks" class="form-control" rows="5" placeholder="Registracija i narudžba vode na tržište korisnika.&#10;Otvara se put prema 30% popusta."><?= e($data->shop_context_form->quick_checks ?? '') ?></textarea>
                    <small class="form-text text-muted">Jedan red = jedna stavka u quick-check bloku.</small>
                </div>

                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_action_title"><i class="fas fa-fw fa-sm fa-bolt text-muted mr-1"></i> Naslov CTA bloka</label>
                            <input id="shop_context_action_title" type="text" name="shop_context_action_title" class="form-control" value="<?= e($data->shop_context_form->action_title ?? '') ?>" maxlength="160" />
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_primary_cta_label"><i class="fas fa-fw fa-sm fa-mouse-pointer text-muted mr-1"></i> Tekst glavnog CTA gumba</label>
                            <input id="shop_context_primary_cta_label" type="text" name="shop_context_primary_cta_label" class="form-control" value="<?= e($data->shop_context_form->primary_cta_label ?? '') ?>" maxlength="120" />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="shop_context_action_subtitle"><i class="fas fa-fw fa-sm fa-align-left text-muted mr-1"></i> Podnaslov CTA bloka</label>
                    <textarea id="shop_context_action_subtitle" name="shop_context_action_subtitle" class="form-control" rows="3" placeholder="Npr. Ovaj korak vodi direktno na registraciju i narudžbu Start paketa u zemlji korisnika."><?= e($data->shop_context_form->action_subtitle ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_secondary_cta_label"><i class="fas fa-fw fa-sm fa-arrow-right text-muted mr-1"></i> Tekst pomoćnog CTA gumba</label>
                            <input id="shop_context_secondary_cta_label" type="text" name="shop_context_secondary_cta_label" class="form-control" value="<?= e($data->shop_context_form->secondary_cta_label ?? '') ?>" maxlength="120" />
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="form-group">
                            <label for="shop_context_related_eyebrow"><i class="fas fa-fw fa-sm fa-tags text-muted mr-1"></i> Eyebrow povezanih sadržaja</label>
                            <input id="shop_context_related_eyebrow" type="text" name="shop_context_related_eyebrow" class="form-control" value="<?= e($data->shop_context_form->related_eyebrow ?? '') ?>" maxlength="80" />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="shop_context_related_title"><i class="fas fa-fw fa-sm fa-link text-muted mr-1"></i> Naslov povezanih sadržaja</label>
                    <input id="shop_context_related_title" type="text" name="shop_context_related_title" class="form-control" value="<?= e($data->shop_context_form->related_title ?? '') ?>" maxlength="180" />
                </div>

                <div class="form-group">
                    <label for="shop_context_faq"><i class="fas fa-fw fa-sm fa-question-circle text-muted mr-1"></i> Strukturirani FAQ</label>
                    <textarea id="shop_context_faq" name="shop_context_faq" class="form-control" rows="6" placeholder="Kako započeti? | Klikom na glavni gumb otvara se registracija i narudžba za tržište korisnika.&#10;Dobiva li se Forever ID? | Da, kroz ovaj proces nova osoba dobiva svoj Forever ID."><?= e($data->shop_context_form->faq ?? '') ?></textarea>
                    <small class="form-text text-muted">Jedan red = jedno pitanje i odgovor. Format: <code>Pitanje | Odgovor</code>.</small>
                </div>
            </div>

            <div class="form-group custom-control custom-switch">
                <input id="is_published" name="is_published" type="checkbox" class="custom-control-input" <?= $data->blog_post->is_published ? 'checked="checked"' : null ?>>
                <label class="custom-control-label" for="is_published"><?= l('admin_blog.is_published') ?></label>
            </div>

            <button class="btn btn-block btn-gray-200 font-size-little-small font-weight-450 my-4" type="button" data-toggle="collapse" data-target="#advanced_container" aria-expanded="false" aria-controls="advanced_container">
                <i class="fas fa-fw fa-user-tie fa-sm mr-1"></i> <?= l('admin_blog.advanced') ?>
            </button>

            <div class="collapse" id="advanced_container">
                <!-- Custom code: FC-2026-03-09: blog search aliases tags field -->
                <div class="form-group">
                    <label for="search_aliases"><i class="fas fa-fw fa-sm fa-search text-muted mr-1"></i> <?= l('admin_blog.search_aliases_label') ?></label>
                    <input id="search_aliases" type="hidden" name="search_aliases" value="<?= e($data->blog_post->search_aliases ?? '') ?>" data-fcc-aliases-hidden />
                    <div class="form-control d-flex flex-wrap align-items-center" style="min-height: 46px; height: auto;" data-fcc-aliases-wrapper>
                        <div class="d-flex flex-wrap align-items-center" data-fcc-aliases-tags></div>
                        <input type="text" class="border-0 flex-grow-1 py-1" style="min-width: 180px; outline: none;" placeholder="<?= l('admin_blog.search_aliases_placeholder') ?>" data-fcc-aliases-input />
                    </div>
                    <small class="form-text text-muted"><?= l('admin_blog.search_aliases_help') ?></small>
                </div>
                <!-- /Custom code: FC-2026-03-09 -->

                <div class="form-group">
                    <label for="keywords"><i class="fas fa-fw fa-sm fa-file-word text-muted mr-1"></i> <?= l('admin_blog.keywords') ?></label>
                    <input id="keywords" type="text" name="keywords" class="form-control" value="<?= $data->blog_post->keywords ?>" maxlength="256" />
                </div>

                <div class="form-group">
                    <label for="image_description"><i class="fas fa-fw fa-sm fa-id-card text-muted mr-1"></i> <?= l('admin_blog.image_description') ?></label>
                    <input id="image_description" type="text" name="image_description" class="form-control" value="<?= $data->blog_post->image_description ?>" maxlength="256" />
                </div>

                <div class="form-group">
                    <label for="language"><i class="fas fa-fw fa-sm fa-language text-muted mr-1"></i> <?= l('global.language') ?></label>
                    <select id="language" name="language" class="custom-select">
                        <option value="" <?= !$data->blog_post->language ? 'selected="selected"' : null ?>><?= l('global.all') ?></option>
                        <?php foreach(\Altum\Language::$languages as $language): ?>
                            <option value="<?= $language['name'] ?>" <?= $data->blog_post->language == $language['name'] ? 'selected="selected"' : null ?>><?= $language['name'] ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <button type="submit" name="submit" class="btn btn-lg btn-block btn-primary mt-4"><?= l('global.update') ?></button>
        </form>
    </div>
</div>

<?php include_view(THEME_PATH . 'views/partials/codemirror_js.php') ?>


<!-- Custom code -->
<?php ob_start() ?>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
<link href="<?= ASSETS_FULL_URL . 'css/quill.css?v=' . PRODUCT_CODE ?>" rel="stylesheet">
<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/tinymce/tinymce.min.js' ?>"></script>

<script>
  /* Custom code: FC-2026-03-09: aliases tags input */
  (function() {
        const hiddenInput = document.querySelector('[data-fcc-aliases-hidden]');
        const tagsContainer = document.querySelector('[data-fcc-aliases-tags]');
        const tagInput = document.querySelector('[data-fcc-aliases-input]');

        if(!hiddenInput || !tagsContainer || !tagInput) return;

        let aliases = (hiddenInput.value || '').split(',').map(value => value.trim()).filter(Boolean);

        const syncHiddenValue = () => {
            hiddenInput.value = aliases.join(', ');
        };

        const renderAliases = () => {
            tagsContainer.innerHTML = aliases.map((alias, index) => `
                <span class="badge badge-light border mr-1 mb-1 d-inline-flex align-items-center px-2 py-1">
                    <span>${alias}</span>
                    <button type="button" class="btn btn-sm p-0 ml-2 text-muted" data-fcc-alias-remove="${index}" style="line-height: 1;">&times;</button>
                </span>
            `).join('');
        };

        const addAlias = (value) => {
            const alias = value.trim();
            if(!alias) return;
            if(aliases.some(existing => existing.toLowerCase() === alias.toLowerCase())) return;

            aliases.push(alias);
            syncHiddenValue();
            renderAliases();
        };

        tagInput.addEventListener('keydown', (event) => {
            if(event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                addAlias(tagInput.value);
                tagInput.value = '';
            }

            if(event.key === 'Backspace' && !tagInput.value && aliases.length) {
                aliases.pop();
                syncHiddenValue();
                renderAliases();
            }
        });

        tagInput.addEventListener('blur', () => {
            if(tagInput.value.trim()) {
                addAlias(tagInput.value);
                tagInput.value = '';
            }
        });

        tagsContainer.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-fcc-alias-remove]');
            if(!removeButton) return;

            const index = parseInt(removeButton.getAttribute('data-fcc-alias-remove'));
            if(Number.isInteger(index)) {
                aliases.splice(index, 1);
                syncHiddenValue();
                renderAliases();
            }
        });

        syncHiddenValue();
        renderAliases();
  })();
  /* /Custom code: FC-2026-03-09 */

  tinymce.init({
        license_key: 'gpl',
        selector: '.editor',
        /*plugins: 'link image table',
        toolbar: 'undo redo | styles | fontfamily fontsize forecolor | bold italic | link image | alignleft aligncenter alignright alignjustify | outdent indent | table',*/
        plugins: 'code advlist table anchor autolink charmap checklist codesample directionality editimage emoticons export formatpainter image link linkchecker lists media powerpaste searchreplace table typography visualchars wordcount accordion',
        toolbar1: 'selectiveDateButton toggleDateButton splitDateButton menuDateButton undo redo spellcheckdialog  | blocks fontfamily fontsize | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | accordion link image media | lineheight checklist bullist numlist | indent outdent | removeformat typography | table code',
        toolbar2: 'shortcodeName shortcodeEmail shortcodePhone shortcodeForeverId shortcodeBiolink',
        content_css: "<?= ASSETS_FULL_URL . 'css/quill.css?v=' . PRODUCT_CODE ?>",
        skin: 'tinymce-5-dark',
        content_style: '@import url("https://fonts.googleapis.com/css2?family=Roboto"); body { font-family: Segoe UI; }',
        font_family_formats: "Segoe UI=Segoe UI; Scriptorama=scriptorama; Roboto=roboto; Helvetica Neue Medium=Helvetica Neue Medium; Helvetica Neue LT=Helvetica Neue LT",
        setup: (editor) => {          
          const shortcodeName = '[name]';
          const shortcodeEmail = '[email]';
          const shortcodePhone = '[phone]';
          const shortcodeForeverId = '[forever_id]';
          const shortcodeBiolink = '[aff_biolink]';
          
          editor.ui.registry.addButton('shortcodeName', {
            text: '[name]',            
            onAction: (_) => editor.insertContent(shortcodeName)
          });

          editor.ui.registry.addButton('shortcodeEmail', {
            text: '[email]',            
            onAction: (_) => editor.insertContent(shortcodeEmail)
          });

          editor.ui.registry.addButton('shortcodePhone', {
            text: '[phone]',            
            onAction: (_) => editor.insertContent(shortcodePhone)
          });

          editor.ui.registry.addButton('shortcodeForeverId', {
            text: '[forever_id]',            
            onAction: (_) => editor.insertContent(shortcodeForeverId)
          });

          editor.ui.registry.addButton('shortcodeBiolink', {
            text: '[aff_biolink]',            
            onAction: (_) => editor.insertContent(shortcodeBiolink)
           });          
        },
    });
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<!-- /Custom code -->

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/universal_delete_modal_url.php', [
    'name' => 'blog_post',
    'resource_id' => 'blog_post_id',
    'has_dynamic_resource_name' => true,
    'path' => 'admin/blog-posts/delete/'
]), 'modals'); ?>
