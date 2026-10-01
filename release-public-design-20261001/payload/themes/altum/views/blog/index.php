<?php defined('ALTUMCODE') || die();
// Search and pagination keep the complete archive and never switch back to the retired design.
if(empty($_GET['page']) && empty($_GET['search']) && ($_GET['view'] ?? '') !== 'articles') {
    require THEME_PATH.'views/blog/pilot-library.php';
} else {
    require THEME_PATH.'views/blog/public-list.php';
}
