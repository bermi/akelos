<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage Generators
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 */

error_reporting(defined('AK_ERROR_REPORTING_ON_SCRIPTS') ? AK_ERROR_REPORTING_ON_SCRIPTS : 0);
require_once(AK_LIB_DIR.DS.'Ak.php');
require_once(AK_LIB_DIR.DS.'AkObject.php');
require_once(AK_LIB_DIR.DS.'AkInflector.php');
defined('AK_SKIP_DB_CONNECTION') && AK_SKIP_DB_CONNECTION ? ($dsn='') : Ak::db(&$dsn);
array_shift($argv);
$command = join(' ',$argv);

require_once(AK_LIB_DIR.DS.'utils'.DS.'generators'.DS.'AkelosGenerator.php');

$Generator = new AkelosGenerator();
$Generator->runCommand($command);

echo "\n";

?>