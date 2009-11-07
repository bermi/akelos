<?php

define('AK_DISPATCH_MODE', 'fast');

define('AK_ENABLE_PROFILER', false);
define('AK_LOG_EVENTS', false);


include('config/config_fast.php');

$url = '/simple_stuff/action/kjhkjh7687t87?paramsa=a&paramb=b';
$path_info = parse_url($url);
$url_path = trim($path_info['path'], '/');

if(isset($config['routes'][$url_path])){
    $_GET = array_merge($_GET, $config['routes'][$url_path]);
}else{
    list($_GET['controller'], $_GET['action'], $_GET['id']) = split('/', $url_path.'//');
}

$controller_class_name = str_replace(' ','',ucwords(preg_replace('/[^A-Z^a-z^0-9^:]+/',' ',$_GET['controller']))).'Controller';
$controller_file_name = strtolower(preg_replace(array('/[^A-Z^a-z^0-9^\/]+/','/([a-z\d])([A-Z])/','/([A-Z]+)([A-Z][a-z])/'), array('_','\1_\2','\1_\2'), $controller_class_name)).'.php';

if(!@include('app/controllers/'.$controller_file_name)){
    if(!@include('505.php')){
        die('Invalid controller. Please set your public/505.php file to customize this error message.');
    }
    die();
}

$Controller = new $controller_file_name();
$Controller->Request = AkRequest();
$Controller->Response = AkResponse();
$Controller->params = $Controller->Request->getParams();
$Controller->_action_name = $Controller->Request->getAction();
$actionExists = $Controller->_ensureActionExists();
if (!$actionExists) {
    $Controller->handleResponse();
    return false;
}
Ak::t('Akelos');

// After filters
$Controller->afterFilter('_handleFlashAttribute');
$Controller->_initExtensions();
$Controller->_loadActionView();

if(isset($Controller->api)){
    require_once(AK_LIB_DIR.DS.'AkActionWebService.php');
    $Controller->aroundFilter(new AkActionWebService($this));
}

$Controller->_identifyRequest();
$Controller->performActionWithFilters($Controller->_action_name);
$Controller->handleResponse();

?>