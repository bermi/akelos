<?php

defined('ALL_TESTS_CALL') ? null : define("ALL_TESTS_CALL",true);
defined('AK_ENABLE_PROFILER') ? null : define('AK_ENABLE_PROFILER',true);

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

if(!defined('ALL_TESTS_RUNNER') && empty($test)){
    $test = new GroupTest('Akelos Framework Active Record Tests');
    define('ALL_TESTS_RUNNER', false);
    @session_start();
}

//these partials are not refactored yet. so they must be run in sequence!
$partial_tests = array(
    '_AkActiveRecord_1.php',
    '_AkActiveRecord_2.php',
    '_AkActiveRecord_3.php'
);

foreach ($partial_tests as $partial_test){
    $test->addTestFile(AK_LIB_TESTS_DIRECTORY.DS.'AkActiveRecord'.DS.$partial_test);
}

foreach (Ak::dir(AK_LIB_TESTS_DIRECTORY.DS.'AkActiveRecord') as $active_record_test){
    if(!is_array($active_record_test) && !in_array($active_record_test, $partial_tests)){
        if (!ALL_TESTS_RUNNER || $active_record_test[0] == '_'){
            $test->addTestFile(AK_LIB_TESTS_DIRECTORY.DS.'AkActiveRecord'.DS.$active_record_test);
        }                
    }
}


if(!ALL_TESTS_RUNNER){
    if (TextReporter::inCli()) {
        exit ($test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
}

?>
