<?php

error_reporting(E_ALL);

defined('AK_CACHE_HANDLER') ? null: define('AK_CACHE_HANDLER', 1);

defined('AK_ENVIRONMENT') ? null : define('AK_ENVIRONMENT', 'testing');

defined('AK_TEST_DIR') ? null : define('AK_TEST_DIR', str_replace(DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php','',__FILE__));
defined('AK_TMP_DIR') ? null : define('AK_TMP_DIR', AK_TEST_DIR.DIRECTORY_SEPARATOR.'tmp');

defined('AK_APP_DIR') ? null :
define('AK_APP_DIR', AK_TEST_DIR.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'app');

defined('AK_PUBLIC_DIR') ? null :
define('AK_PUBLIC_DIR', AK_TEST_DIR.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'public');

defined('AK_TEST_HELPERS_DIR') ? null :
define('AK_TEST_HELPERS_DIR', AK_TEST_DIR.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'helpers');

defined('AK_SITE_URL_SUFFIX')?null:define('AK_SITE_URL_SUFFIX', str_replace(array(join(DIRECTORY_SEPARATOR,array_diff((array)@explode(DIRECTORY_SEPARATOR,AK_TEST_DIR),
(array)@explode('/',@$_SERVER['REQUEST_URI']))),DIRECTORY_SEPARATOR),array('','/'),AK_TEST_DIR));

defined('AK_ENABLE_AKELOS_ARGS') ? null : define('AK_ENABLE_AKELOS_ARGS', true);
//define('AK_SKIP_DB_CONNECTION',isset($db) && $db === false);
defined('AK_URL_REWRITE_ENABLED') ? null : define('AK_URL_REWRITE_ENABLED', true);

include_once(substr(AK_TEST_DIR,0,-5).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');

defined('AK_APP_LOCALES') ? null : define('AK_APP_LOCALES', 'en,es');
defined('AK_PUBLIC_LOCALES') ? null : define('AK_PUBLIC_LOCALES', AK_APP_LOCALES);
defined('AK_ACTIVE_RECORD_INTERNATIONALIZE_MODELS_BY_DEFAULT') ? null : define('AK_ACTIVE_RECORD_INTERNATIONALIZE_MODELS_BY_DEFAULT', true);
defined('AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS') ? null : define('AK_ACTIVE_RECORD_ENABLE_AUTOMATIC_SETTERS_AND_GETTERS', true);

//defined('AK_TESTING_URL') ? null : define('AK_TESTING_URL', rtrim(AK_URL,'/').'/test/fixtures/public');

defined('AK_TESTING_URL') ? null : define('AK_TESTING_URL', 'http://localhost:81/test/fixtures/public');
defined('AK_TESTING_REWRITE_BASE') ? null : define('AK_TESTING_REWRITE_BASE', false);

defined('AK_LIB_TESTS_DIRECTORY') ? null : define('AK_LIB_TESTS_DIRECTORY', AK_TEST_DIR.DS.'unit'.DS.'lib');

if(AK_TESTING_REWRITE_BASE){
    Ak::file_put_contents(AK_BASE_DIR.'/test/fixtures/public/.htaccess', str_replace('# RewriteBase /test/fixtures/public','RewriteBase '.AK_TESTING_REWRITE_BASE, Ak::file_get_contents(AK_BASE_DIR.'/test/fixtures/public/.htaccess')));
}

if(defined('AK_TEST_DATABASE_ON')){
    $default_profile = AK_ENVIRONMENT;
    defined('AK_DEFAULT_DATABASE_PROFILE')?null:define('AK_DEFAULT_DATABASE_PROFILE', $default_profile);
    //$GLOBALS['database_settings'] = $database_settings;
    include_once(AK_LIB_DIR.DS.'Ak.php');
    Ak::db();
    unset($default_profile);
}


require_once(AK_LIB_DIR.DS.'AkUnitTest.php');


?>