<?php

$database_settings = array(
'testing' => array(
    'type' => 'pgsql',
    'host' => 'localhost',
    'database_file'=>'/tmp/akelos.sqlite',
    'database_name' => 'framework_tests',
    'user' => '',
    'password' => '',
    'options' => ''
));

$database_settings['development'] = $database_settings['production'] = $database_settings['testing'];
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
defined('AK_ENVIRONMENT') ? null : define('AK_ENVIRONMENT', 'testing');
defined('AK_BASE_DIR') ? null : define('AK_BASE_DIR', str_replace(DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php','',__FILE__));
defined('AK_FRAMEWORK_DIR') ? null : define('AK_FRAMEWORK_DIR', str_replace(DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php',DS.'..'.DS,__FILE__));
defined('AK_TESTING_URL') ? null : define('AK_TESTING_URL', 'http://localhost:8181/test/fixtures/public');
define('AK_LOG_EVENTS', true);

include('fix_htaccess.php');

include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'boot.php');

?>