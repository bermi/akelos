<?php

/**
 * Tests for static functions and generic utilities provided by Akelos
 */

defined('ALL_TESTS_CALL') ? null : define("ALL_TESTS_CALL", true);
defined('AK_ENABLE_PROFILER') ? null : define('AK_ENABLE_PROFILER',true);

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

if(!defined('ALL_TESTS_RUNNER') && empty($test)){
    $test = new GroupTest('Akelos Framework Static Method and utilities Tests');
    define('ALL_TESTS_RUNNER', false);
}

@session_start();

$partial_tests = array(
'Ak_convert',
'Ak_file_functions',
'Ak_object_inspection',
//'Ak_file_functions_over_ftp',
'Ak_support_functions',
);

foreach ($partial_tests as $partial_test){
    $test->addTestFile(AK_LIB_TESTS_DIRECTORY.DS.'utils'.DS.'_'.$partial_test.'.php');
}

if(!ALL_TESTS_RUNNER){
    if (TextReporter::inCli()) {
        exit ($test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
}

?>
