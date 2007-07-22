<?php

defined('ALL_TESTS_CALL') ? null : define("ALL_TESTS_CALL",true);
defined('AK_ENABLE_PROFILER') ? null : define('AK_ENABLE_PROFILER',true);

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

if(!defined('ALL_TESTS_RUNNER') && empty($test)){
    $test = &new GroupTest('Akelos Framework Active Record Tests');
    define('ALL_TESTS_RUNNER', false);
    @session_start();
}

$partial_tests = array(
'AkActiveRecord_1',
'AkActiveRecord_2',
'AkActiveRecord_3',
'AkActiveRecord_locking',
'AkActiveRecord_table_inheritance',
'AkActiveRecord_i18n',
'AkActiveRecord_multiple_inclussion',
'AkActiveRecord_accessible_attributes',
'AkActiveRecord_calculations',
'AkActiveRecord_associated_inclusion',
'AkActiveRecord_findOrCreateBy',
);

foreach ($partial_tests as $partial_test){
    $test->addTestFile(AK_LIB_TESTS_DIRECTORY.DS.'AkActiveRecord'.DS.'_'.$partial_test.'.php');
}

// Acts as, Validators, Associations and Observer tests
if(!ALL_TESTS_RUNNER){
    foreach (Ak::dir(AK_LIB_TESTS_DIRECTORY.DS.'AkActiveRecord') as $active_record_test){
        if($active_record_test[0] != '_'){
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
