<?php defined('ALTUMCODE') || die() ?>
<script>
    'use strict';

    (async () => {
        let url = <?= json_encode(SITE_URL) ?>;
        let title = <?= json_encode(settings()->main->title) ?>;
        let website_pixel_key = <?= json_encode($data->website->pixel_key) ?>;
        let website_host = <?= json_encode($data->website->host) ?>;
        let user_analytics_is_enabled = <?= json_encode($data->user->plan_settings->analytics_is_enabled) ?>;
        let pixel_exposed_identifier = <?= json_encode(settings()->websites->pixel_exposed_identifier) ?>;

        if(website_host.startsWith('www.')) {
            website_host = website_host.replace('www.', '');
        }

        /* Make sure the website loads only where expected */
        if(window.location.hostname !== website_host && window.location.hostname !== `www.${website_host}`) {
            console.log(`${title} (${url}): Website does not match the set domain/subdomain.`);
            return;
        }

        /* Make sure service workers are available in the browser */
        if(!('serviceWorker' in navigator)) {
            return;
        }

        /* Make sure push api is available in the browser */
        if(!('PushManager' in window)) {
            return;
        }

        /* Helper to get the current service worker registration */
        let get_current_registration = async () => {
            let all_registrations = await navigator.serviceWorker.getRegistrations();

            /* Try to find by exact script_url first (new behavior) */
            let current_registration = all_registrations.find(
                registration => registration.active?.scriptURL === script_url
            );

            /* If not found, fallback to the first available registration (old behavior) */
            if (!current_registration && all_registrations.length > 0) {
                current_registration = all_registrations[0];
            }

            return current_registration || null;
        };

        /* Register the main service worker */
        let public_key = <?= json_encode($data->website->keys->public_key) ?>;
        const script_url = <?= json_encode($data->website->scheme . $data->website->host . $data->website->path . '/' . settings()->websites->service_worker_file_name . '.js') ?>;
        const scope = <?= json_encode($data->website->scheme . $data->website->host . $data->website->path . '/') ?>;
        const registration = await navigator.serviceWorker.register(script_url, {scope});

        const scoped_registration = await get_current_registration();
        console.log(`${title} (${url}): ${scoped_registration?.active ? 'Service worker is active.' : 'Service worker not active or not registered'}`);

        /* Helper to easily send logs */
        let send_tracking_data = async data => {
            if(!user_analytics_is_enabled) return;

            try {
                navigator.sendBeacon(`${url}pixel-track/${website_pixel_key}`, JSON.stringify(data));
            } catch (error) {
                console.log(`${title} (${url}): ${error}`);
            }
        }

        /* Get the current notification permission status */
        let get_notification_permission = async () => {
            return Notification.permission;
        }

        /* Get the current status of the web push subscription */
        let get_subscription_status = async () => {
            let registration = await get_current_registration();
            if (!registration) { return false; }

            let subscription = await registration.pushManager.getSubscription();
            return subscription ? true : false;
        };

        /* Unsubscribe function */
        let unsubscribe = async () => {
            let registration = await get_current_registration();
            if (!registration) {
                window.dispatchEvent(new Event(`${pixel_exposed_identifier}.unsubscribed`));
                return true;
            }

            let subscription = await registration.pushManager.getSubscription();
            if(!subscription) {
                /* Dispatch custom JS event */
                window.dispatchEvent(new Event(`${pixel_exposed_identifier}.unsubscribed`));

                return true;
            }

            await subscription.unsubscribe();

            /* Prepare form data */
            let subscription_data = subscription.toJSON();

            let data = {
                endpoint: subscription_data.endpoint,
                auth: subscription_data.keys.auth,
                p256dh: subscription_data.keys.p256dh,
                pixel_key: website_pixel_key,
                url: window.location.href,
                type: 'delete'
            };

            /* Send request to server */
            let response = await fetch(`${url}pixel-track/${website_pixel_key}`, {
                method: 'post',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            /* Dispatch custom JS event */
            window.dispatchEvent(new Event(`${pixel_exposed_identifier}.unsubscribed`));

            return true;
        }

        /* Subscribe function */
        let subscribe = async () => {
            let registration = await get_current_registration();
            if (!registration) { return false; }

            let subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: public_key
            });

            /* Prepare form data */
            let subscription_data = subscription.toJSON();

            let data = {
                endpoint: subscription_data.endpoint,
                auth: subscription_data.keys.auth,
                p256dh: subscription_data.keys.p256dh,
                pixel_key: website_pixel_key,
                url: window.location.href,
                type: 'create'
            };

            /* Check for extra parameters */
            let this_script = document.querySelector(`script[src$="pixel/${website_pixel_key}"]`);

            if(this_script.dataset.customParameters) {

                try {
                    let custom_parameters = JSON.parse(this_script.dataset.customParameters);

                    data['custom_parameters'] = custom_parameters;
                } catch(error) {
                    /* :) */
                }

            }

            /* Send request to server */
            let response = await fetch(`${url}pixel-track/${website_pixel_key}`, {
                method: 'post',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            /* Dispatch custom JS event */
            window.dispatchEvent(new Event(`${pixel_exposed_identifier}.subscribed`));

            return true;
        }

        /* Request permission from the browser and subscribe if accepted */
        let request_push_notification_permission_and_subscribe = async event => {
            event.preventDefault();

            /* Request for permission from the browser */
            let permission = await Notification.requestPermission();

            if(permission === 'granted') {
                let registration = await get_current_registration();
                if (!registration) { return false; }

                let subscription = await registration.pushManager.getSubscription();

                /* Dispatch custom JS event */
                window.dispatchEvent(new Event(`${pixel_exposed_identifier}.permission_granted`));

                /* Not subscribed: Try to subscribe */
                if(!subscription) {
                    return await subscribe();
                }

                /* Subscribed: Unsubscribe from potential old subscription */
                else {
                    await unsubscribe();
                    return await subscribe();
                }
            }

            if(permission == 'denied') {
                /* Send log data */
                await send_tracking_data({
                    type: 'permission_denied'
                })

                /* Dispatch custom JS event */
                window.dispatchEvent(new Event(`${pixel_exposed_identifier}.permission_denied`));

                return false;
            }

        }

        /* Expose function to window */
        window[pixel_exposed_identifier] = {
            get_subscription_status: get_subscription_status,
            unsubscribe: unsubscribe,
            subscribe: subscribe,
        };

        /* Attach simple subscribe event */
        document.querySelector(`[data-${pixel_exposed_identifier}-trigger-simple-subscribe]`) && document.querySelector(`[data-${pixel_exposed_identifier}-trigger-simple-subscribe]`).addEventListener('click', async event => {
            await request_push_notification_permission_and_subscribe(event);
        })

        /* Attach simple unsubscribe event */
        document.querySelector(`[data-${pixel_exposed_identifier}-trigger-simple-unsubscribe]`) && document.querySelector(`[data-${pixel_exposed_identifier}-trigger-simple-unsubscribe]`).addEventListener('click', async event => {
            await unsubscribe(event);
        })

        <?php if($data->website->widget->is_enabled): ?>
        /*  CSS for the widget */
        let pixel_widget_css_loaded = false;

        /* Display subscription widget */
        let is_subscribed = await get_subscription_status();

        if(!is_subscribed) {
            let pixel_css_link = document.createElement('link');
            pixel_css_link.href = '<?= ASSETS_FULL_URL . 'css/pixel-widget.min.css' ?>';
            pixel_css_link.type = 'text/css';
            pixel_css_link.rel = 'stylesheet';
            pixel_css_link.media = 'screen,print';
            pixel_css_link.onload = function() { pixel_widget_css_loaded = true; };
            document.getElementsByTagName('head')[0].appendChild(pixel_css_link);

            <?php require_once ASSETS_PATH . 'js/pixel/pixel-widget.min.js' ?>

            <?php
            /* Dynamic variables processing */
            $replacers = [
                '{{TOTAL_SUBSCRIBERS}}' => $data->website->total_subscribers,
            ];

            foreach(['title', 'description', 'subscribed_title', 'subscribed_description', 'permission_denied_title', 'permission_denied_description'] as $key) {
                $data->website->widget->{$key} = str_replace(
                    array_keys($replacers),
                    array_values($replacers),
                    $data->website->widget->{$key}
                );
            }
            ?>

            <?php $content = include_view(THEME_PATH . 'views/partials/pixel/widget.php', ['website' => $data->website]) ?>

            let widget = new AltumCode66pusherWidget({
                should_show: true,
                content: <?= json_encode($content) ?>,
                widget: <?= json_encode($data->website->widget) ?>,
                display_mobile: <?= json_encode($data->website->widget->display_mobile) ?>,
                display_desktop: <?= json_encode($data->website->widget->display_desktop) ?>,
                display_trigger: <?= json_encode($data->website->widget->display_trigger) ?>,
                display_trigger_value: <?= json_encode($data->website->widget->display_trigger_value) ?>,
                display_delay_type_after_close: <?= json_encode($data->website->widget->display_delay_type_after_close) ?>,
                display_delay_value_after_close: <?= json_encode($data->website->widget->display_delay_value_after_close) ?>,
                duration: <?= $data->website->widget->display_duration === -1 ? -1 : $data->website->widget->display_duration * 1000 ?>,
                display_frequency: <?= json_encode($data->website->widget->display_frequency) ?>,
                position: <?= json_encode($data->website->widget->display_position) ?>,
                trigger_all_pages: <?= json_encode($data->website->widget->trigger_all_pages) ?>,
                triggers: <?= json_encode($data->website->widget->triggers) ?>,
                on_animation: <?= json_encode($data->website->widget->on_animation) ?>,
                off_animation: <?= json_encode($data->website->widget->off_animation) ?>,
                subscribed_success_url: <?= json_encode($data->website->widget->subscribed_success_url) ?>,
            });

            widget.initiate();
        }
        <?php endif ?>

        <?php if($data->website->button->is_enabled): ?>
        /*  CSS for the widget */
        let pixel_button_css_loaded = false;

        /* Make sure to include the external css file */
        let link = document.createElement('link');
        link.href = '<?= ASSETS_FULL_URL . 'css/pixel-button.min.css' ?>';
        link.type = 'text/css';
        link.rel = 'stylesheet';
        link.media = 'screen,print';
        link.onload = function () { pixel_button_css_loaded = true };
        document.getElementsByTagName('head')[0].appendChild(link);

        <?php require_once ASSETS_PATH . 'js/pixel/pixel-button.min.js' ?>

        <?php
        /* Dynamic variables processing */
        $replacers = [
            '{{TOTAL_SUBSCRIBERS}}' => $data->website->total_subscribers,
        ];

        foreach(['title', 'description', 'subscribed_title', 'subscribed_description', 'unsubscribe_title', 'unsubscribe_description', 'unsubscribed_title', 'unsubscribed_description', 'permission_denied_title', 'permission_denied_description'] as $key) {
            $data->website->button->{$key} = str_replace(
                array_keys($replacers),
                array_values($replacers),
                $data->website->button->{$key}
            );
        }
        ?>

        <?php $content = include_view(THEME_PATH . 'views/partials/pixel/button.php', ['website' => $data->website]) ?>

        let widget = new AltumCode66pusherButton({
            should_show: true,
            content: <?= json_encode($content) ?>,
            button: <?= json_encode($data->website->button) ?>,
            display_mobile: <?= json_encode($data->website->button->display_mobile) ?>,
            display_desktop: <?= json_encode($data->website->button->display_desktop) ?>,
            trigger_all_pages: <?= json_encode($data->website->button->trigger_all_pages) ?>,
            triggers: <?= json_encode($data->website->button->triggers) ?>,
            subscribed_success_url: <?= json_encode($data->website->button->subscribed_success_url) ?>,
        });

        widget.initiate();
        <?php endif ?>

    })();
</script>
