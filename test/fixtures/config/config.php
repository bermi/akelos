<?php

error_reporting(E_ALL);

define('AK_ENVIRONMENT', 'testing');

defined('AK_TEST_DIR') ? null : define('AK_TEST_DIR', str_replace(DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php','',__FILE__));


defined('AK_APP_DIR') ? null : 
define('AK_APP_DIR', AK_TEST_DIR.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'app');

define('AK_SITE_URL_SUFFIX', str_replace(array(join(DIRECTORY_SEPARATOR,array_diff((array)@explode(DIRECTORY_SEPARATOR,AK_TEST_DIR),
(array)@explode('/',@$_SERVER['REQUEST_URI']))),DIRECTORY_SEPARATOR),array('','/'),AK_TEST_DIR));

//define('AK_SKIP_DB_CONNECTION',isset($db) && $db === false);

include_once(substr(AK_TEST_DIR,0,-5).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');

defined('AK_APP_LOCALES') ? null : define('AK_APP_LOCALES', 'en,es');
defined('AK_PUBLIC_LOCALES') ? null : define('AK_PUBLIC_LOCALES', AK_APP_LOCALES);


defined('AK_TESTING_URL') ? null : define('AK_TESTING_URL', rtrim(AK_URL,'/').'/test/fixtures/public');

defined('AK_LIB_TESTS_DIRECTORY') ? null : define('AK_LIB_TESTS_DIRECTORY', AK_TEST_DIR.DS.'unit'.DS.'lib');

if(defined('AK_TEST_DATABASE_ON')){
    include_once(AK_LIB_DIR.DS.'Ak.php');
    Ak::db(&$dsn);
}


require_once(AK_LIB_DIR.DS.'AkUnitTest.php');


?>