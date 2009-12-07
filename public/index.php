<?php

if(!@include(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php')){
    define('AK_ENVIRONMENT', 'setup');
    include(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'DEFAULT-config.php');
}

$Dispatcher = new AkDispatcher();
$Dispatcher->dispatch();
