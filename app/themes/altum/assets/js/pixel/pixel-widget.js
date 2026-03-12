/* Helpers */
let get_scroll_percentage = () => {
    let h = document.documentElement;
    let b = document.body;
    let st = 'scrollTop';
    let sh = 'scrollHeight';

    return (h[st]||b[st]) / ((h[sh]||b[sh]) - h.clientHeight) * 100;
};

class AltumCode66pusherWidget {

    /* Create and initiate the class with the proper parameters */
    constructor(options) {

        /* Initiate the main options variable */
        this.options = {};

        /* Process the passed options and the default ones */
        this.options.content = options.content || '';
        this.options.widget = options.widget || '';
        this.options.delay = typeof options.delay === 'undefined' ? 3000 : options.delay;
        this.options.duration = typeof options.duration === 'undefined' ? -1 : options.duration;
        this.options.stop_on_focus = true;
        this.options.position = typeof options.position === 'undefined' ? 'top_center' : options.position;
        this.options.subscribed_success_url = typeof options.subscribed_success_url === 'undefined' ? null : options.subscribed_success_url;

        /* On what pages to show the notification */
        this.options.trigger_all_pages = typeof options.trigger_all_pages === 'undefined' ? true : options.trigger_all_pages;
        this.options.triggers = options.triggers || [];

        /* More checks on if it should be displayed */
        this.options.display_frequency = typeof options.display_frequency === 'undefined' ? 'all_time' : options.display_frequency;
        this.options.display_mobile = typeof options.display_mobile === 'undefined' ? true : options.display_mobile;
        this.options.display_desktop = typeof options.display_desktop === 'undefined' ? true : options.display_desktop;

        /* When to show the notifications */
        this.options.display_trigger = typeof options.display_trigger === 'undefined' ? 'delay' : options.display_trigger;
        this.options.display_trigger_value = typeof options.display_trigger_value === 'undefined' ? 3 : options.display_trigger_value;

        /* When to show the notifications after a manual close */
        this.options.display_delay_type_after_close = typeof options.display_delay_type_after_close === 'undefined' ? 'time_on_site' : options.display_delay_type_after_close;
        this.options.display_delay_value_after_close = typeof options.display_delay_value_after_close === 'undefined' ? 21600 : options.display_delay_value_after_close;

        /* Animations */
        this.options.on_animation = typeof options.on_animation === 'undefined' ? 'fadeIn' : options.on_animation;
        this.options.off_animation = typeof options.off_animation === 'undefined' ? 'fadeOut' : options.off_animation;
        this.options.animation = typeof options.animation === 'undefined' ? false : options.animation;
        this.options.animation_interval = typeof options.animation_interval === 'undefined' ? 5 : options.animation_interval;
    }

    /* Function to build the toast element */
    async build() {

        /* Check if the user is already subscribed */
        let is_subscribed = await get_subscription_status();

        if(is_subscribed) {
            return false;
        }

        /* Process triggers */
        if(!this.options.trigger_all_pages) {
            let triggered = this.is_page_triggered(this.options.triggers);

            if(!triggered) {
                return false;
            }
        }

        /* Display frequency handle */
        switch(this.options.display_frequency) {
            case 'all_time':
                /* no extra conditions */
                break;

            case 'once_per_session':
                if(sessionStorage.getItem(`${pixel_exposed_identifier}_widget_display_frequency`)) {
                    return false;
                }
                break;

            case 'once_per_browser':
                if(localStorage.getItem(`${pixel_exposed_identifier}_widget_display_frequency`)) {
                    return false;
                }
                break;
        }

        /* Check if it should be shown on the current screen */
        if((!this.options.display_mobile && window.innerWidth < 768) || (!this.options.display_desktop && window.innerWidth > 768)) {
            return false;
        }

        /* Display delay after closing the notification */
        if(sessionStorage.getItem(`${pixel_exposed_identifier}_widget_manually_closed`)) {
            switch(this.options.display_delay_type_after_close) {
                case 'time_on_site':

                    let delayed_time_on_site = parseInt(sessionStorage.getItem(`${pixel_exposed_identifier}_widget_display_delay_type_after_close_time_on_site`) ?? 0);

                    if(delayed_time_on_site < this.options.display_delay_value_after_close * 1000) {

                        setInterval(() => {
                            let delayed_time_on_site = parseInt(sessionStorage.getItem(`${pixel_exposed_identifier}_widget_display_delay_type_after_close_time_on_site`) ?? 0);
                            sessionStorage.setItem(`${pixel_exposed_identifier}_widget_display_delay_type_after_close_time_on_site`, delayed_time_on_site + 500);
                        }, 500);

                        return false;
                    }

                    break;

                case 'pageviews':

                    let pageviews = parseInt(sessionStorage.getItem(`${pixel_exposed_identifier}_widget_display_delay_type_after_close_pageviews`) ?? 0) + 1;
                    sessionStorage.setItem(`${pixel_exposed_identifier}_widget_display_delay_type_after_close_pageviews`, pageviews);

                    if(pageviews < this.options.display_delay_value_after_close) {
                        return false;
                    }

                    break;
            }
        }

        /* Create the html element */
        let main_element = document.createElement('div');
        main_element.className = 'altumcode-66pusher-widget';

        /* Positioning of the toast class */
        main_element.className += ` altumcode-66pusher-widget-${this.options.position}`;

        /* Add the positioning key to the data attribute for later usage */
        main_element.setAttribute('data-position', this.options.position);

        /* Add the animation settings to the data attribute for later usage */
        main_element.setAttribute('data-on-animation', this.options.on_animation);
        main_element.setAttribute('data-off-animation', this.options.off_animation);

        /* Add the content to the element */
        main_element.innerHTML = this.options.content;

        /* Add the close event if needed */
        let close_button = main_element.querySelector('[data-close]');

        if(close_button) {
            /* Click to remove handler */
            close_button.addEventListener('click', event => {
                event.stopPropagation();

                /* Remember that the notification was manually closed */
                sessionStorage.setItem(`${pixel_exposed_identifier}_widget_manually_closed`, true);

                /* Reset other delays */
                sessionStorage.removeItem(`${pixel_exposed_identifier}_widget_display_delay_type_after_close_time_on_site`);
                sessionStorage.removeItem(`${pixel_exposed_identifier}_widget_display_delay_type_after_close_pageviews`);

                /* Remove widget */
                this.constructor.remove_notification(main_element);
            });
        }

        /* Add the proper events to the primary button */
        let subscribe_button = main_element.querySelector('[data-subscribe]');

        if(subscribe_button) {
            if(await get_notification_permission() == 'denied') {

                /* Subscribe button now a refresh button */
                subscribe_button.addEventListener('click', () => {
                    location.reload();
                })

            } else {

                subscribe_button.addEventListener('click', async event => {

                    /* Enable loading animation */
                    main_element.querySelector('[data-loading]').style.display = 'flex';

                    let has_subscribed = await request_push_notification_permission_and_subscribe(event);

                    /* Disable loading animation */
                    main_element.querySelector('[data-loading]').style.display = 'none';

                    if(has_subscribed) {
                        /* Display a success message */
                        main_element.querySelector('[data-title]').innerHTML = this.options.widget.subscribed_title;
                        main_element.querySelector('[data-description]').innerHTML = this.options.widget.subscribed_description;
                        if(this.options.widget.subscribed_image_url) {
                            main_element.querySelector('[data-image]').src = this.options.widget.subscribed_image_url;
                            main_element.querySelector('[data-image]').alt = this.options.widget.subscribed_image_alt;
                        }
                        main_element.querySelector('[data-buttons]').style.cssText = 'display: none !important;';
                    } else {
                        /* Remember that the notification was denied / closed */
                        sessionStorage.setItem(`${pixel_exposed_identifier}_widget_manually_closed`, true);

                        /* Reset other delays */
                        sessionStorage.removeItem(`${pixel_exposed_identifier}_widget_display_delay_type_after_close_time_on_site`);
                        sessionStorage.removeItem(`${pixel_exposed_identifier}_widget_display_delay_type_after_close_pageviews`);
                    }

                    setTimeout(() => {
                        /* Remove widget */
                        this.constructor.remove_notification(main_element);

                        /* Redirect if needed */
                        if(has_subscribed && this.options.subscribed_success_url) {
                            window.location.href = this.options.subscribed_success_url;
                        }
                    }, 4000);

                });

            }
        }

        return main_element;

    }

    /* Function to make sure that the content of the site has loaded before building beginning the main process */
    initiate(callbacks = {}) {

        /* Wait for CSS to load before processing */
        const wait_for_css_and_process = () => {
            let css_load_interval = setInterval(() => {
                if(pixel_widget_css_loaded) {
                    clearInterval(css_load_interval);
                    this.process(callbacks);
                }
            }, 100);
        };

        /* DOM ready logic */
        if(document.readyState === 'complete' || (document.readyState !== 'loading' && !document.documentElement.doScroll)) {
            wait_for_css_and_process();
        } else {
            document.addEventListener('DOMContentLoaded', () => {
                wait_for_css_and_process();
            });
        }

        /* SPA */
        let current_page_url = window.location.href;

        /* Hijack pushState to trigger a custom event */
        (history => {
            const original_push_state = history.pushState;
            history.pushState = function(state) {
                const result = original_push_state.apply(history, arguments);
                window.dispatchEvent(new Event('66pusher_url_change'));
                return result;
            };
        })(window.history);

        /* Handler for all URL changes */
        const handle_url_change = () => {
            if(current_page_url !== window.location.href) {
                current_page_url = window.location.href;

                /* Remove existing widget if present */
                let widget = document.querySelector('div[class*="altumcode-66pusher-widget"][class*="on-"]');
                this.constructor.remove_notification(widget);

                /* Start process again */
                wait_for_css_and_process();
            }
        };

        /* Listen to popstate and custom pushState event */
        window.addEventListener('popstate', handle_url_change);
        window.addEventListener('66pusher_url_change', handle_url_change);
    }

    /* Display main function */
    async process(callbacks = {}) {

        let main_element = await this.build();

        /* Make sure we have an element to display */
        if(!main_element) return false;

        /* Insert the element to the body depending on the position it needs to be shown */
        switch(this.options.position) {
            case 'top':
            case 'top_floating':
                document.body.prepend(main_element);
                break;

            case 'bottom':
            case 'bottom_floating':
                document.body.appendChild(main_element);
                break;

            /* Fixed positions */
            default:
                document.body.appendChild(main_element);
                break;
        }

        let display = async () => {

            /* Change the content based if the user denied permissions */
            if(await get_notification_permission() == 'denied') {
                main_element.querySelector('[data-title]').innerHTML = this.options.widget.permission_denied_title;
                main_element.querySelector('[data-description]').innerHTML = this.options.widget.permission_denied_description;
                if(this.options.widget.permission_denied_image_url) {
                    main_element.querySelector('[data-image]').src = this.options.widget.permission_denied_image_url;
                    main_element.querySelector('[data-image]').alt = this.options.widget.permission_denied_image_alt;
                }
                main_element.querySelector('[data-subscribe]').innerHTML = this.options.widget.permission_denied_refresh_button;
                main_element.querySelector('[data-close]').innerHTML = this.options.widget.permission_denied_close_button;
            }

            /* Make sure they are visible */
            main_element.style.display = 'block';

            /* Add the fade in class */
            main_element.classList.add(`on-${this.options.on_animation}`);
            main_element.classList.add(`on-visible`);

            /* Remove the animation */
            setTimeout(() => {
                main_element.classList.remove(`on-${this.options.on_animation}`);
            }, 1500)

            /* Handle the positioning on the screen */
            this.constructor.reposition();

            /* Add animation intervals */
            if(this.options.animation) {
                main_element.animation_interval = window.setInterval(() => {
                    main_element.classList.add(`animation-${this.options.animation}`);

                    /* Remove the animation */
                    setTimeout(() => {
                        main_element.classList.remove(`animation-${this.options.animation}`);
                    }, (this.options.animation_interval-1) * 1000);
                }, this.options.animation_interval * 1000);
            }

            /* Add timeout to remove the toast if needed */
            if(this.options.duration !== -1) {
                main_element.timeout = window.setTimeout(() => {

                    this.constructor.remove_notification(main_element);

                }, this.options.duration);
            }

            /* Clear timeout if the user focused on the notification in certain conditions */
            if(this.options.stop_on_focus && this.options.duration !== -1) {

                /* Stop countdown on mouseover the notification */
                main_element.addEventListener('mouseover', event => {
                    window.clearTimeout(main_element.timeout);
                });

                /* Add the timeout counter again */
                main_element.addEventListener('mouseleave', () => {
                    main_element.timeout = window.setTimeout(() => {

                        this.constructor.remove_notification(main_element);

                    }, this.options.duration);
                });
            }

            /* Display frequency handle */
            switch(this.options.display_frequency) {
                case 'all_time':
                    /* no extra conditions */
                    break;

                case 'once_per_session':
                    /* Add the notification to the session to avoid other displays on the session */
                    sessionStorage.setItem(`${pixel_exposed_identifier}_widget_display_frequency`, true);
                    break;

                case 'once_per_browser':
                    /* Add the notification to the session to avoid other displays on the session */
                    localStorage.setItem(`${pixel_exposed_identifier}_widget_display_frequency`, true);
                    break;
            }

            /* Add handler for window resizing */
            window.removeEventListener('resize', this.constructor.reposition);
            window.addEventListener('resize', this.constructor.reposition);
        };

        /* Displaying it properly */
        switch(this.options.display_trigger) {
            case 'delay':

                let delay_time_passed = 0;
                let delay_interval = null;

                let delay_tick = () => {
                    if(document.hidden) {
                        return;
                    }

                    delay_time_passed += 100;

                    if(delay_time_passed >= this.options.display_trigger_value * 1000) {
                        clearInterval(delay_interval);
                        display();
                    }
                }

                delay_interval = setInterval(delay_tick, 100);

                break;

            case 'time_on_site':

                let time_on_site_checker = () => {
                    if(document.hidden) return;

                    let time_on_site = parseInt(
                        sessionStorage.getItem(`${pixel_exposed_identifier}_widget_display_trigger_time_on_site`) ?? 0
                    );

                    if(time_on_site > this.options.display_trigger_value * 1000) {
                        display();
                        clearInterval(time_on_site_timer);
                        return;
                    }

                    sessionStorage.setItem(`${pixel_exposed_identifier}_widget_display_trigger_time_on_site`, time_on_site + 500);
                }

                let time_on_site_timer = setInterval(time_on_site_checker, 500);

                break;

            case 'inactivity':

                let timer = null;
                let is_displayed = false;

                let timer_function = () => {
                    if(document.hidden) return;
                    if(is_displayed) return;

                    clearTimeout(timer);

                    timer = setTimeout(() => {

                        display();
                        is_displayed = true;

                        ['load','mousemove','mousedown','touchstart','touchmove','click','keydown','scroll','wheel']
                            .forEach(event => window.removeEventListener(event, timer_function, true));

                    }, this.options.display_trigger_value * 1000);
                }

                ['load','mousemove','mousedown','touchstart','touchmove','click','keydown','scroll','wheel']
                    .forEach(event => window.addEventListener(event, timer_function, true));

                break;

            case 'pageviews':

                let pageviews = parseInt(sessionStorage.getItem(`${pixel_exposed_identifier}_widget_display_trigger_pageviews`) ?? 0) + 1;
                sessionStorage.setItem(`${pixel_exposed_identifier}_widget_display_trigger_pageviews`, pageviews);

                if(pageviews >= this.options.display_trigger_value) {
                    display();
                }

                break;

            case 'exit_intent':

                let exit_intent_triggered = false;

                document.addEventListener('mouseout', event => {

                    if(exit_intent_triggered) return;

                    let viewport_width = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);

                    /* Ignore right edge */
                    if(event.clientX >= (viewport_width - 50))
                        return;

                    /* Require movement toward the top edge */
                    if(event.clientY >= 50)
                        return;

                    /* Must actually leave the window */
                    if(event.relatedTarget || event.toElement)
                        return;

                    /* Exit intent happened */
                    exit_intent_triggered = true;
                    display();

                });

                break;

            case 'scroll':

                let scroll_triggered = false;

                document.addEventListener('scroll', event => {

                    if(!scroll_triggered && get_scroll_percentage() > this.options.display_trigger_value) {

                        display();

                        scroll_triggered = true;

                    }

                });

                break;

            case 'click':

                if(document.querySelector(this.options.display_trigger_value)) {
                    document.querySelector(this.options.display_trigger_value).addEventListener('click', event => {
                        display();
                    })
                }

                break;

            case 'hover':

                if(document.querySelector(this.options.display_trigger_value)) {
                    document.querySelector(this.options.display_trigger_value).addEventListener('mouseenter', event => {
                        display();
                    })
                }

                break;
        }

    }

    is_page_triggered(triggers) {
        let triggered = false;

        /* If there is a Not type of condition, make sure to start with the triggered state of true */
        for(let trigger of triggers) {
            if(trigger.type.startsWith('not_')) {
                triggered = true;
                break;
            }
        }


        triggers.forEach(trigger => {

            switch(trigger.type) {
                case 'exact':

                    if(trigger.value == window.location.href) {
                        triggered = true;
                    }

                    break;

                case 'not_exact':

                    if(trigger.value == window.location.href) {
                        triggered = false;
                    }

                    break;

                case 'contains':

                    if(window.location.href.includes(trigger.value)) {
                        triggered = true;
                    }

                    break;

                case 'not_contains':

                    if(window.location.href.includes(trigger.value)) {
                        triggered = false;
                    }

                    break;

                case 'starts_with':

                    if(window.location.href.startsWith(trigger.value)) {
                        triggered = true;
                    }

                    break;

                case 'not_starts_with':

                    if(window.location.href.startsWith(trigger.value)) {
                        triggered = false;
                    }

                    break;

                case 'ends_with':

                    if(window.location.href.endsWith(trigger.value)) {
                        triggered = true;
                    }

                    break;

                case 'not_ends_with':

                    if(window.location.href.endsWith(trigger.value)) {
                        triggered = false;
                    }

                    break;

                case 'page_contains':

                    if(document.body.innerText.includes(trigger.value)) {
                        triggered = true;
                    }

                    break;
            }

        });

        return triggered;
    }

    /* Function to remove the notification with animation */
    static remove_notification(element) {

        try {
            /* Get animation data */
            let on_animation = element.getAttribute('data-on-animation');
            let off_animation = element.getAttribute('data-off-animation');

            /* Hide the element with an animation */
            element.classList.add(`off-${off_animation}`);

            /* Remove the element from the DOM */
            window.setTimeout(() => {
                element.parentNode.removeChild(element);
            }, 400);

        } catch(event) {
            // ^_^
        }

    }

    /* Positioning function on the screen of all the notifications */
    static reposition() {

        let widget = document.querySelector(`div[class*="altumcode-66pusher-widget"][class*="on-"]`);

        /* Get the height for later positioning usage in the middle of the screen */
        let height = window.innerHeight > 0 ? window.innerHeight : screen.height;
        let height_middle = Math.floor(height / 2);

        /* Default spacings that are going to be iterated if multiple widgets are on the same position */
        let widgets_offset = {
            top_left: {
                left: 20,
                top: 20
            },

            top_center: {
                top: 20
            },

            top_right: {
                right: 20,
                top: 20
            },

            middle_left: {
                left: 20,
                top: height_middle
            },

            middle_center: {
                top: height_middle,
            },

            middle_right: {
                right: 20,
                top: height_middle
            },

            bottom_left: {
                left: 20,
                bottom: 20
            },

            bottom_center: {
                bottom: 20
            },

            bottom_right: {
                right: 20,
                bottom: 20
            }
        };

        /* Spacing between stacked widgets */
        let toast_offset = 20;

        /* Get current position */
        let toast_position = widget.getAttribute('data-position');

        /* Get height */
        let toast_height = widget.offsetHeight;

        switch(toast_position) {

            /* When the notifications do not need to be fixed */
            default:

                /* :) */

                break;

            case 'top_left':

                widget.style['top'] = `${widgets_offset[toast_position].top}px`;
                widgets_offset[toast_position].top += toast_height + toast_offset;

                break;

            case 'top_center':

                widget.style['top'] = `${widgets_offset[toast_position].top}px`;
                widgets_offset[toast_position].top += toast_height + toast_offset;

                break;

            case 'top_right':

                widget.style['top'] = `${widgets_offset[toast_position].top}px`;
                widgets_offset[toast_position].top += toast_height + toast_offset;

                break;

            case 'middle_left':

                widget.style['top'] = `${widgets_offset[toast_position].top - (toast_height / 2)}px`;
                widgets_offset[toast_position].top += toast_height + toast_offset;

                break;

            case 'middle_center':

                widget.style['top'] = `${widgets_offset[toast_position].top - (toast_height / 2)}px`;
                widgets_offset[toast_position].top += toast_height + toast_offset;

                break;

            case 'middle_right':

                widget.style['top'] = `${widgets_offset[toast_position].top - (toast_height / 2)}px`;
                widgets_offset[toast_position].top += toast_height + toast_offset;

                break;

            case 'bottom_left':

                widget.style['bottom'] = `${widgets_offset[toast_position].bottom}px`;
                widgets_offset[toast_position].bottom += toast_height + toast_offset;

                break;

            case 'bottom_center':

                widget.style['bottom'] = `${widgets_offset[toast_position].bottom}px`;
                widgets_offset[toast_position].bottom += toast_height + toast_offset;

                break;

            case 'bottom_right':

                widget.style['bottom'] = `${widgets_offset[toast_position].bottom}px`;
                widgets_offset[toast_position].bottom += toast_height + toast_offset;

                break;

        }

    }

}
