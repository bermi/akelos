<?php

defined('AK_ENVIRONMENT') ? null : define('AK_ENVIRONMENT', 'testing');
defined('AK_BASE_DIR') ? null : define('AK_BASE_DIR', str_replace(DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php','',__FILE__));
defined('AK_TESTING_URL') ? null : define('AK_TESTING_URL', '${testing-url}');
defined('AK_LOG_EVENTS') ? null : define('AK_LOG_EVENTS', true);


include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'boot.php');

?>