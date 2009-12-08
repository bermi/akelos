<?php

defined('AK_ENVIRONMENT')   ||  define('AK_ENVIRONMENT',    'testing');
defined('AK_BASE_DIR')      ||  define('AK_BASE_DIR',       str_replace(DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php','', substr(AK_TEST_DIR,0,-5)));
defined('AK_LOG_EVENTS')    ||  define('AK_LOG_EVENTS',     true);

defined('DS')                   || define('DS',                     DIRECTORY_SEPARATOR);
defined('AK_FRAMEWORK_DIR')     || define('AK_FRAMEWORK_DIR',       AK_BASE_DIR.DS.'akelos');
defined('AK_TESTING_NAMESPACE') || define('AK_TESTING_NAMESPACE',   'akelos');
defined('AK_TESTING_URL')       || define('AK_TESTING_URL',   'http://akelos.tests');

defined('AK_TMP_DIR')               ||  define('AK_TMP_DIR', AK_BASE_DIR.DS.'tmp');

include_once AK_FRAMEWORK_DIR.DS.'active_support'.DS.'base.php';
include_once AK_FRAMEWORK_DIR.DS.'active_support'.DS.'constants.php';

if(!AkConfig::getOption('testing_url', false))
AkConfig::setOption('testing_url', AK_TESTING_URL);
AkUnitTestSuite::checkIfTestingWebserverIsAccesible(array('base_path' => AK_TEST_DIR.DS.AK_TESTING_NAMESPACE));
AkUnitTestSuite::createTestingDatabaseIfNotAvailable();
AkUnitTestSuite::ensureTmpDirPermissions();

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


