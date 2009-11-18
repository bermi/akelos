<?php

defined('AK_ENVIRONMENT')   ||    define('AK_ENVIRONMENT', 'testing');
defined('AK_BASE_DIR')      ||  define('AK_BASE_DIR', str_replace(DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php','', substr(AK_TEST_DIR,0,-5)));
defined('AK_TESTING_URL')   ||  define('AK_TESTING_URL', 'http://localhost/akelos-ci-tests/test/fixtures/public');
defined('AK_LOG_EVENTS')    ||  define('AK_LOG_EVENTS', true);

include_once(substr(AK_TEST_DIR,0,-5).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'boot.php');
