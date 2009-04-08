<?php

set_time_limit(0);

define('ALL_TESTS_CALL',true);
define('ALL_TESTS_RUNNER',true);

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');

require_once(AK_LIB_DIR.DS.'AkInstaller.php');
require_once(AK_VENDOR_DIR.DS.'simpletest-tools'.DS.'xmlreporter.php');
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
                   $test->addTestFile($entry);
           }
       }
   }
   $d->close();
}
function load_test_files($dir, &$test, $test_files) 
{
    foreach($test_files as $test_file) {
        if(!is_file($test_file)) {
            $test_file = $dir.DS.$test_file;
        }

        $test->addTestFile($test_file);
    }
}
 $test_files = array();
if(count($_SERVER['argv'])>1) {
    //$test_files = $_SERVER['argv'];
   
    for($i=count($_SERVER['argv'])-1;$i>0;$i--) {
        if (is_file($_SERVER['argv'][$i]) && preg_match('/^.*\.php$/',$_SERVER['argv'][$i])) {
            $test_files[] = $_SERVER['argv'][$i];
        }
    }
    
} 
if(count($test_files)>0) {
    load_test_files(AK_TEST_DIR.DS.'unit'.DS.'suites', $test, $test_files);
} else {
    load_tests(AK_TEST_DIR.DS.'unit'.DS.'suites', $test);
}
if (TextReporter::inCli()) {
    $writeXml = false;
    if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1]=='--xml') {
        $file = isset($_SERVER['argv'][2])?$_SERVER['argv'][2]:false;
        $phpversion = isset($_SERVER['argv'][3])?$_SERVER['argv'][3]:'php5';
        $backend = isset($_SERVER['argv'][4])?$_SERVER['argv'][4]:'mysql';
        $writeXml=true;
        $reporter = new XmlReporter('UTF-8',$phpversion,$backend);
        $run = $test->run($reporter);
    } else {
        $reporter = new TextReporter();
        $run = $test->run($reporter);
    }
    
    if ($writeXml) {
        $contents = $reporter->getXml();
        file_put_contents($file,$contents);
    }
    exit ($run ? 0 : 1);
    
}
$test->run(new HtmlReporter());



?>
