<?php

defined('AK_CACHE_HANDLER')         || define('AK_CACHE_HANDLER', 1);
defined('AK_ENVIRONMENT')           || define('AK_ENVIRONMENT', 'testing');
defined('AK_TEST_DIR')              || define('AK_TEST_DIR', str_replace(DIRECTORY_SEPARATOR.'shared'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php','',__FILE__));
defined('AK_FIXTURES_DIR')          || define('AK_FIXTURES_DIR', AK_TEST_DIR.DIRECTORY_SEPARATOR.'fixtures');

if(isset($_SERVER['REQUEST_URI'])){
    defined('AK_SITE_URL_SUFFIX')   || define('AK_SITE_URL_SUFFIX', str_replace(array(join(DIRECTORY_SEPARATOR,array_diff((array)@explode(DIRECTORY_SEPARATOR,AK_TEST_DIR), (array)@explode('/',$_SERVER['REQUEST_URI']))),DIRECTORY_SEPARATOR),array('','/'),AK_TEST_DIR));
}else{
    defined('AK_SITE_URL_SUFFIX')   || define('AK_SITE_URL_SUFFIX', '/');
}
defined('AK_ENABLE_AKELOS_ARGS')    ||  define('AK_ENABLE_AKELOS_ARGS', true);
defined('AK_URL_REWRITE_ENABLED')   ||  define('AK_URL_REWRITE_ENABLED', true);

$_app_config_file = substr(AK_TEST_DIR,0,-5).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
include_once(file_exists($_app_config_file) ? $_app_config_file : 'app_config.php');

defined('AK_APP_LOCALES')           ||  define('AK_APP_LOCALES', 'en,es');
defined('AK_PUBLIC_LOCALES')        ||  define('AK_PUBLIC_LOCALES', AK_APP_LOCALES);

defined('AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS')    ||  define('AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS', true);

