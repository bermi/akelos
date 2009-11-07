<?php

defined('ALL_TESTS_CALL') ? null : define("ALL_TESTS_CALL",true);
defined('AK_ENABLE_PROFILER') ? null : define('AK_ENABLE_PROFILER',true);

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

if(!defined('ALL_TESTS_RUNNER') && empty($test)){
    $test = new GroupTest('Akelos Framework Action Controller Tests');
    define('ALL_TESTS_RUNNER', false);
}

@session_start();

$partial_tests = array(
'forbidden_actions',
'filters',
'locale_detection',
'partials',
'http_authentication',
'model_instantiation',
'page_caching',
'action_caching',
'sweeper',
'respond_to_format',
'renders_format',
);

foreach ($partial_tests as $partial_test){
    $test->addTestFile(AK_LIB_TESTS_DIRECTORY.DS.'AkActionController'.DS.'_'.$partial_test.'.php');
}

if(!ALL_TESTS_RUNNER){
    if (TextReporter::inCli()) {
        exit ($test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
}

?>
