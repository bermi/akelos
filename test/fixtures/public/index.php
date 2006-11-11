<?php

include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');

$ActionController = new AkActionController();
$ActionController->handleRequest();

?>