<?php

defined('AK_ENVIRONMENT')   ||  define('AK_ENVIRONMENT',    'testing');
defined('AK_BASE_DIR')      ||  define('AK_BASE_DIR',       str_replace(DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php','', substr(AK_TEST_DIR,0,-5)));
defined('AK_TESTING_URL')   ||  define('AK_TESTING_URL',    'http://akelos.tests');
defined('AK_LOG_EVENTS')    ||  define('AK_LOG_EVENTS',     true);

defined('DS')                   || define('DS',                     DIRECTORY_SEPARATOR);
defined('AK_FRAMEWORK_DIR')     || define('AK_FRAMEWORK_DIR',       AK_BASE_DIR.DS.'akelos');
defined('AK_LIB_DIR')           || define('AK_LIB_DIR',             AK_FRAMEWORK_DIR.DS.'lib');
defined('AK_TESTING_NAMESPACE') || define('AK_TESTING_NAMESPACE',   'akelos');

include_once AK_LIB_DIR.DS.'active_support'.DS.'base.php';
include_once AK_LIB_DIR.DS.'active_support'.DS.'constants.php';

if(!file_exists(AK_CONFIG_DIR.DS.'database.yml')){
    file_put_contents(AK_CONFIG_DIR.DS.'database.yml', 'default:
    type: sqlite
    host:
    database_name:
    database_file: '.AK_TMP_DIR.DS.'akelos.sqlite
    user:
    password:
    options: ');
    function ak_remove_testing_db_settings(){
        unlink(AK_CONFIG_DIR.DS.'database.yml');
    }
    register_shutdown_function('ak_remove_testing_db_settings');
}

try{
    ob_start();
    if(!class_exists('BaseActionController')){
        class BaseActionController extends AkActionController{ }
    }
    if(!class_exists('ApplicationController')){
        class ApplicationController extends BaseActionController { public $layout = false; }
    }
    if(!class_exists('BaseActiveRecord')){
        class BaseActiveRecord extends AkActiveRecord { }
    }
    if(!class_exists('ActiveRecord')){
        class ActiveRecord extends BaseActiveRecord { }
    }
    ob_get_clean();
}catch(Exception $e){}


