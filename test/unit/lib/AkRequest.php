<?php

defined('ALL_TESTS_CALL') ? null : define("ALL_TESTS_CALL",true);
defined('AK_ENABLE_PROFILER') ? null : define('AK_ENABLE_PROFILER',true);

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

if(!defined('ALL_TESTS_RUNNER') && empty($test)){
    $test = new GroupTest('Akelos Framework AkRequest Tests');
    define('ALL_TESTS_RUNNER', false);
}

$partial_tests = array(
'base',
'invalid_requests',
);

foreach ($partial_tests as $partial_test){
    $test->addTestFile(AK_LIB_TESTS_DIRECTORY.DS.'AkRequest'.DS.'_'.$partial_test.'.php');
}

if(!ALL_TESTS_RUNNER){
    if (TextReporter::inCli()) {
        exit ($test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
}

?>
