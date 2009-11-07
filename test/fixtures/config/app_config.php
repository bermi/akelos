<?php

defined('AK_ENVIRONMENT') ? null : define('AK_ENVIRONMENT', 'testing');
defined('AK_BASE_DIR') ? null : define('AK_BASE_DIR', str_replace(DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php','', substr(AK_TEST_DIR,0,-5)));
defined('AK_TESTING_URL') ? null : define('AK_TESTING_URL', 'http://localhost/akelos-ci-tests/test/fixtures/public');
defined('AK_LOG_EVENTS') ? null : define('AK_LOG_EVENTS', true);
//defined('AK_CONFIG_DIR') ? null : define('AK_CONFIG_DIR', AK_FIXTURES_DIR.DIRECTORY_SEPARATOR.'config');

include_once(substr(AK_TEST_DIR,0,-5).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'boot.php');
