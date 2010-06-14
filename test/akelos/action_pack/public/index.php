<?php

// Core tests need to run without installing a new app
$_config_path_candidate = realpath(dirname(__FILE__).'/../../../../app_layout/config');
if($_config_path_candidate){
    define('AK_CONFIG_DIR', $_config_path_candidate);
}
unset($_config_path_candidate);

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


