<?php
ini_set('short_open_tag', '1');

date_default_timezone_set('UTC');
#ini_set('short_open_tag',true);
include 'init_autoloader.php';

define('RINDOW_TEST_CACHE',     __DIR__.'/cache');
@mkdir(RINDOW_TEST_CACHE, $mode = 0777 , $recursive = TRUE);

if(!class_exists('PHPUnit\Framework\TestCase')) {
    include __DIR__.'/travis/patch55.php';
}
