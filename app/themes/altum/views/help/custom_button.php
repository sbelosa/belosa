<?php defined('ALTUMCODE') || die() ?>

<h1 class="h4"><?= l('help.custom_button.header') ?></h1>
<p><?= l('help.custom_button.p1') ?></p>
<p><?= l('help.custom_button.p2') ?></p>

<pre class="pre-custom rounded">
await <?= settings()->websites->pixel_exposed_identifier ?>.get_subscription_status();
await <?= settings()->websites->pixel_exposed_identifier ?>.subscribe();
await <?= settings()->websites->pixel_exposed_identifier ?>.unsubscribe();
</pre>

<p><?= l('help.custom_button.p3') ?></p>

<pre class="pre-custom rounded">
&lt;span id="pusher_loading"&gt;Loading status...&lt;/span&gt;
&lt;a href="#" id="pusher_subscribe" style="display: none;"&gt;Subscribe ✅&lt;/a&gt;
&lt;a href="#" id="pusher_unsubscribe" style="display: none;"&gt;Unsubscribe ❌&lt;/a&gt;

&lt;script defer&gt;
    let initiate_pusher_script = async () =&gt; {
        if(typeof pusher !== 'undefined') {
            clearInterval(pusher_is_loaded_interval);

            /* Get status of subscription */
            let status = await <?= settings()->websites->pixel_exposed_identifier ?>.get_subscription_status();

            /* Remove loading message */
            document.querySelector('#pusher_loading').style.display = 'none';

            /* Display subscribe or unsubscribe button based on the current subscription status */
            if(status) {
                document.querySelector('#pusher_unsubscribe').style.display = 'block';
                document.querySelector('#pusher_subscribe').style.display = 'none';
            } else {
                document.querySelector('#pusher_unsubscribe').style.display = 'none';
                document.querySelector('#pusher_subscribe').style.display = 'block';
            }
        }
    }

    let pusher_is_loaded_interval = setInterval(initiate_pusher_script, 100);

    /* Attach simple subscribe event */
    document.querySelector(`#pusher_subscribe`) &amp;&amp; document.querySelector(`#pusher_subscribe`).addEventListener('click', async event =&gt; {
        event.preventDefault();
        await <?= settings()->websites->pixel_exposed_identifier ?>.subscribe(event);
        initiate_pusher_script();
    });

    /* Attach simple unsubscribe event */
    document.querySelector(`#pusher_unsubscribe`) &amp;&amp; document.querySelector(`#pusher_unsubscribe`).addEventListener('click', async event =&gt; {
        event.preventDefault();
        await <?= settings()->websites->pixel_exposed_identifier ?>.unsubscribe(event);
        initiate_pusher_script();
    });
&lt;/script&gt;
</pre>
