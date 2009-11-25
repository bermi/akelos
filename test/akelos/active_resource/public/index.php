<?php

define('AK_ENABLE_URL_REWRITE',     false);
define('AK_URL_REWRITE_ENABLED',    false);


require_once(dirname(__FILE__).'/../config.php');

// We need first to rebase the application
$UnitTest = new ActiveResourceUnitTest();

$Dispatcher = new AkDispatcher();
$Dispatcher->dispatch();

?>