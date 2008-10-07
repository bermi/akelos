<?php

set_time_limit(0);

define('ALL_TESTS_CALL',true);
//define('ALL_TESTS_RUNNER',true);

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
if (isset($_SERVER['argv'][3]) && $_SERVER['argv'][3] == 'php4') {
    require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config_php4.php');
} else {
    require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');
}

require_once(AK_LIB_DIR.DS.'AkInstaller.php');
require_once(AK_APP_DIR.DS.'installers'.DS.'framework_installer.php');
$installer = new FrameworkInstaller();
$installer->uninstall();
$installer->install();

session_start();

$test = &new GroupTest('Unit tests for the Akelos Framework');
$testName = $_SERVER['argv'][2];
function load_test($dir, $name, &$test) 
{
    $entry = $dir.DS.$name;

    $test->addTestFile($entry);

}

load_test(AK_TEST_DIR.DS.'unit'.DS.'lib',$testName, $test);

if (TextReporter::inCli()) {
    exit ($test->run(new TextReporter()) ? 0 : 1);
}
$test->run(new HtmlReporter());



?>
