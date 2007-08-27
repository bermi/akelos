<?php

set_time_limit(0);

define('ALL_TESTS_CALL',true);
define('ALL_TESTS_RUNNER',true);

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');

require_once(AK_LIB_DIR.DS.'AkInstaller.php');
require_once(AK_APP_DIR.DS.'installers'.DS.'framework_installer.php');
$installer = new FrameworkInstaller();
$installer->uninstall();
$installer->install();

session_start();

$test = &new GroupTest('Unit tests for the Akelos Framework');

function load_tests($dir, &$test) 
{
   $d = dir($dir);
   while (false !== ($entry = $d->read())) {
       if($entry != '.' && $entry != '..' && $entry[0] != '.' && $entry[0] != '_') {
           $entry = $dir.DS.$entry;
           if(is_dir($entry)) {
               load_tests($entry, $test);
           } else {
               if(!strstr(file_get_contents($entry), 'ALL_TESTS_RUNNER')){
                   $test->addTestFile($entry);
               }else{
                   require_once($entry);
               }
           }
       }
   }
   $d->close();
}

load_tests(AK_TEST_DIR.DS.'unit'.DS.'lib', $test);

if (TextReporter::inCli()) {
    exit ($test->run(new TextReporter()) ? 0 : 1);
}
$test->run(new HtmlReporter());



?>
