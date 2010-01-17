<?php

define('AK_ENABLE_URL_REWRITE',     false);
define('AK_URL_REWRITE_ENABLED',    false);

if(isset($_GET['custom_routes'])){
    define('AK_ROUTES_MAPPING_FILE', dirname(__FILE__).'/../'.str_replace('.','',$_GET['custom_routes']).'_routes.php');
}

require_once(dirname(__FILE__).'/../config.php');

// We need first to rebase the application
$UnitTest = new ActionPackUnitTest();

$Dispatcher = new AkDispatcher();
$Dispatcher->dispatch();


