<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('api-documentation') ?>"><?= l('api_documentation.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= l('websites.title') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <h1 class="h4 mb-4"><?= l('websites.title') ?></h1>

    <div class="accordion">
        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#read_all" aria-expanded="true" aria-controls="read_all">
                        <?= l('api_documentation.read_all') ?>
                    </a>
                </h3>
            </div>

            <div id="read_all" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-success mr-3">GET</span> <span class="text-muted"><?= SITE_URL ?>api/websites/</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                curl --request GET \<br />
                                --url '<?= SITE_URL ?>api/websites/' \<br />
                                --header 'Authorization: Bearer <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive table-custom-container mb-4">
                        <table class="table table-custom">
                            <thead>
                            <tr>
                                <th><?= l('api_documentation.parameters') ?></th>
                                <th><?= l('global.details') ?></th>
                                <th><?= l('global.description') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>page</td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td><?= l('api_documentation.filters.page') ?></td>
                            </tr>
                            <tr>
                                <td>results_per_page</td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.filters.results_per_page'), '<code>' . implode('</code> , <code>', [10, 25, 50, 100, 250, 500, 1000]) . '</code>', 25) ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.response') ?></label>
                        <pre data-shiki="json">
{
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "website_id": 1,
            "domain_id": 0,
            "pixel_key": "abc123abc123",
            "name": "Example",
            "scheme": "https://",
            "host": "example.com",
            "path": "/path",
            "settings": {
                "icon": null,
                "ip_storage_is_enabled": 1,
                "branding_name": "",
                "branding_url": ""
            },
            "widget": {
                "is_enabled": true,
                "title": "Get notified on discount campaigns 🔥",
                "description": "Once a month, no spam, unsubscribe at any time.",
                "subscribe_button": "Subscribe",
                "close_button": "Not now",
                "image_url": "https://example.com/pixel/bell.svg",
                "image_alt": "",
                "subscribed_title": "Welcome ✨",
                "subscribed_description": "Thank you for subscribing!",
                "subscribed_image_url": "https://example.com/check-circle.svg",
                "subscribed_image_alt": "",
                "subscribed_success_url": "",
                "permission_denied_title": "Push notifications denied permission",
                "permission_denied_description": "If you wish to subscribe - please reset your browser's notification permissions.",
                "permission_denied_refresh_button": "Refresh page",
                "permission_denied_close_button": "Close",
                "permission_denied_image_url": "https://example.com/pixel/sad.svg",
                "permission_denied_image_alt": "",
                "display_continents": [],
                "display_countries": [],
                "display_languages": [],
                "display_operating_systems": [],
                "display_browsers": [],
                "display_mobile": true,
                "display_desktop": true,
                "trigger_all_pages": true,
                "triggers": [],
                "display_trigger": "delay",
                "display_trigger_value": 5,
                "display_frequency": "all_time",
                "display_delay_type_after_close": "time_on_site",
                "display_delay_value_after_close": 3600,
                "direction": "ltr",
                "display_duration": -1,
                "display_position": "top_center",
                "display_branding": true,
                "font": "inherit",
                "title_color": "#000000",
                "description_color": "#000000",
                "background_color": "#ffffff",
                "subscribe_button_text_color": "#ffffff",
                "subscribe_button_background_color": "#000000",
                "close_button_text_color": "#4c5461",
                "close_button_background_color": "#f1f2f4",
                "border_color": "#000000",
                "internal_padding": 12,
                "display_shadow": false,
                "border_radius": "rounded",
                "border_width": 0,
                "hover_animation": "",
                "on_animation": "",
                "off_animation": "",
                "animation": "",
                "animation_interval": 5
            },
            "button": {
                "is_enabled": false,
                "title": "Subscribe for discounts 🔥",
                "description": "Unsubscribe at any time, no spam.",
                "image_url": "https://example.com/pixel/bell.svg",
                "image_alt": "",
                "subscribed_title": "Welcome ✨",
                "subscribed_description": "Thank you for subscribing!",
                "subscribed_image_url": "https://example.com/check-circle.svg",
                "subscribed_image_alt": "",
                "subscribed_success_url": "",
                "unsubscribe_title": "Unsubscribe",
                "unsubscribe_description": "Click to unsubscribe.",
                "unsubscribe_image_url": "https://example.com/minus-circle.svg",
                "unsubscribe_image_alt": "",
                "unsubscribe_success_url": "",
                "unsubscribed_title": "Bye bye 👋",
                "unsubscribed_description": "You can subscribe back at any time!",
                "unsubscribed_image_url": "https://example.com/minus-circle.svg",
                "unsubscribed_image_alt": "",
                "unsubscribed_success_url": "",
                "permission_denied_title": "Browser denied permission",
                "permission_denied_description": "Reset your browser's notification permissions to subscribe.",
                "permission_denied_image_url": "https://example.com/pixel/sad.svg",
                "permission_denied_image_alt": "",
                "display_continents": [],
                "display_countries": [],
                "display_languages": [],
                "display_operating_systems": [],
                "display_browsers": [],
                "display_mobile": true,
                "display_desktop": true,
                "trigger_all_pages": true,
                "triggers": [],
                "direction": "ltr",
                "display_branding": true,
                "font": "inherit",
                "title_color": "#000000",
                "description_color": "#000000",
                "background_color": "#f5f6f7",
                "border_color": "#000000",
                "internal_padding": 12,
                "display_shadow": false,
                "border_radius": "rounded",
                "border_width": 0,
                "hover_animation": ""
            },
            "notifications": [],
            "keys": {
                "public_key": "123",
                "private_key": "123"
            },
            "total_sent_campaigns": 1,
            "total_subscribers": 1,
            "total_sent_push_notifications": 1,
            "total_displayed_push_notifications": 1,
            "total_clicked_push_notifications": 0,
            "total_closed_push_notifications": 1,
            "is_enabled": true,
            "last_datetime": null,
            "datetime": "<?= get_date() ?>",
        }
    ],
    "meta": {
        "page": 1,
        "results_per_page": 25,
        "total": 1,
        "total_pages": 1
    },
    "links": {
        "first": "<?= SITE_URL ?>api/websites?page=1",
        "last": "<?= SITE_URL ?>api/websites?page=1",
        "next": null,
        "prev": null,
        "self": "<?= SITE_URL ?>api/websites?page=1"
    }
}
</pre>
                        </div>
                    </div>
                </div>
            </div>


        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#read" aria-expanded="true" aria-controls="read">
                        <?= l('api_documentation.read') ?>
                    </a>
                </h3>
            </div>

            <div id="read" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-success mr-3">GET</span> <span class="text-muted"><?= SITE_URL ?>api/websites/</span><span class="text-primary">{website_id}</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                curl --request GET \<br />
                                --url '<?= SITE_URL ?>api/websites/<span class="text-primary">{website_id}</span>' \<br />
                                --header 'Authorization: Bearer <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.response') ?></label>
                        <pre data-shiki="json">
{
    "data": {
        "id": 1,
        "user_id": 1,
        "website_id": 1,
        "domain_id": 0,
        "pixel_key": "abc123abc123",
        "name": "Example",
        "scheme": "https://",
        "host": "example.com",
        "path": "/path",
        "settings": {
            "icon": null,
            "ip_storage_is_enabled": 1,
            "branding_name": "",
            "branding_url": ""
        },
        "widget": {
            "is_enabled": true,
            "title": "Get notified on discount campaigns 🔥",
            "description": "Once a month, no spam, unsubscribe at any time.",
            "subscribe_button": "Subscribe",
            "close_button": "Not now",
            "image_url": "https://example.com/pixel/bell.svg",
            "image_alt": "",
            "subscribed_title": "Welcome ✨",
            "subscribed_description": "Thank you for subscribing!",
            "subscribed_image_url": "https://example.com/check-circle.svg",
            "subscribed_image_alt": "",
            "subscribed_success_url": "",
            "permission_denied_title": "Push notifications denied permission",
            "permission_denied_description": "If you wish to subscribe - please reset your browser's notification permissions.",
            "permission_denied_refresh_button": "Refresh page",
            "permission_denied_close_button": "Close",
            "permission_denied_image_url": "https://example.com/pixel/sad.svg",
            "permission_denied_image_alt": "",
            "display_continents": [],
            "display_countries": [],
            "display_languages": [],
            "display_operating_systems": [],
            "display_browsers": [],
            "display_mobile": true,
            "display_desktop": true,
            "trigger_all_pages": true,
            "triggers": [],
            "display_trigger": "delay",
            "display_trigger_value": 5,
            "display_frequency": "all_time",
            "display_delay_type_after_close": "time_on_site",
            "display_delay_value_after_close": 3600,
            "direction": "ltr",
            "display_duration": -1,
            "display_position": "top_center",
            "display_branding": true,
            "font": "inherit",
            "title_color": "#000000",
            "description_color": "#000000",
            "background_color": "#ffffff",
            "subscribe_button_text_color": "#ffffff",
            "subscribe_button_background_color": "#000000",
            "close_button_text_color": "#4c5461",
            "close_button_background_color": "#f1f2f4",
            "border_color": "#000000",
            "internal_padding": 12,
            "display_shadow": false,
            "border_radius": "rounded",
            "border_width": 0,
            "hover_animation": "",
            "on_animation": "",
            "off_animation": "",
            "animation": "",
            "animation_interval": 5
        },
        "button": {
            "is_enabled": false,
            "title": "Subscribe for discounts 🔥",
            "description": "Unsubscribe at any time, no spam.",
            "image_url": "https://example.com/pixel/bell.svg",
            "image_alt": "",
            "subscribed_title": "Welcome ✨",
            "subscribed_description": "Thank you for subscribing!",
            "subscribed_image_url": "https://example.com/check-circle.svg",
            "subscribed_image_alt": "",
            "subscribed_success_url": "",
            "unsubscribe_title": "Unsubscribe",
            "unsubscribe_description": "Click to unsubscribe.",
            "unsubscribe_image_url": "https://example.com/minus-circle.svg",
            "unsubscribe_image_alt": "",
            "unsubscribe_success_url": "",
            "unsubscribed_title": "Bye bye 👋",
            "unsubscribed_description": "You can subscribe back at any time!",
            "unsubscribed_image_url": "https://example.com/minus-circle.svg",
            "unsubscribed_image_alt": "",
            "unsubscribed_success_url": "",
            "permission_denied_title": "Browser denied permission",
            "permission_denied_description": "Reset your browser's notification permissions to subscribe.",
            "permission_denied_image_url": "https://example.com/pixel/sad.svg",
            "permission_denied_image_alt": "",
            "display_continents": [],
            "display_countries": [],
            "display_languages": [],
            "display_operating_systems": [],
            "display_browsers": [],
            "display_mobile": true,
            "display_desktop": true,
            "trigger_all_pages": true,
            "triggers": [],
            "direction": "ltr",
            "display_branding": true,
            "font": "inherit",
            "title_color": "#000000",
            "description_color": "#000000",
            "background_color": "#f5f6f7",
            "border_color": "#000000",
            "internal_padding": 12,
            "display_shadow": false,
            "border_radius": "rounded",
            "border_width": 0,
            "hover_animation": ""
        },
        "notifications": [],
        "keys": {
            "public_key": "123",
            "private_key": "123"
        },
        "total_sent_campaigns": 1,
        "total_subscribers": 1,
        "total_sent_push_notifications": 1,
        "total_displayed_push_notifications": 1,
        "total_clicked_push_notifications": 0,
        "total_closed_push_notifications": 1,
        "is_enabled": true,
        "last_datetime": null,
        "datetime": "<?= get_date() ?>",
    }
}
</pre>
                        </div>
                    </div>
                </div>
            </div>


        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#websites_delete" aria-expanded="true" aria-controls="websites_delete">
                        <?= l('api_documentation.delete') ?>
                    </a>
                </h3>
            </div>

            <div id="websites_delete" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-danger mr-3">DELETE</span> <span class="text-muted"><?= SITE_URL ?>api/websites/</span><span class="text-primary">{website_id}</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                curl --request DELETE \<br />
                                --url '<?= SITE_URL ?>api/websites/<span class="text-primary">{website_id}</span>' \<br />
                                --header 'Authorization: Bearer <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                            </div>
                        </div>
                    </div>

                </div>
            </div>

    </div>
</div>

<?php require THEME_PATH . 'views/partials/shiki_highlighter.php' ?>

