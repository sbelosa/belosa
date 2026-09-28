<?php
if(PHP_SAPI!=='cli')exit(1);
umask(0077);chdir(__DIR__);
if(is_file('shared-release.done')||is_file('shared-release.failed')){if(!is_file('shared-final.done')&&is_file('shared-final.php'))require __DIR__.'/shared-final.php';exit;}
if(is_file('shared-release.php')&&is_file('shared-release.ready')){require __DIR__.'/shared-release.php';exit;}
require __DIR__.'/shared-inventory.php';
