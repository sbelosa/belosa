<?php
if(PHP_SAPI!=='cli')exit(1);
umask(0077);chdir(__DIR__);
if(is_file('shared-release.done')&&is_file('shared-cleanup.php')){require __DIR__.'/shared-cleanup.php';exit;}
if(is_file('shared-release.php')&&is_file('shared-release.ready')){require __DIR__.'/shared-release.php';exit;}
require __DIR__.'/shared-inventory.php';
