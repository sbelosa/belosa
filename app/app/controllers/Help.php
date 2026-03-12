<?php
/*
 * Copyright (c) 2025 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

namespace Altum\Controllers;

use Altum\Title;

defined('ALTUMCODE') || die();

class Help extends Controller {

    public function index() {

        $page = isset($this->params[0]) ? query_clean(get_slug($this->params[0],'_')) : 'introduction';
        $page = preg_replace('/' . '-' . '+/', '_', $page);

        /* Check if page exists */
        if(!file_exists(THEME_PATH . 'views/help/' . $page . '.php')) {
            redirect('help');
        }

        $view = new \Altum\View('help/' . $page, (array) $this);
        $this->add_view_content('page', $view->run());

        /* Set a custom title */
        Title::set(sprintf(l('help.title'), l('help.' . $page . '.title')));

        /* Prepare the view */
        $data = [
            'page' => $page
        ];

        $view = new \Altum\View('help/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
