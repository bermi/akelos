<?php

/**
 * This file and the lib/constants.php file perform most part of Akelos 
 * environment guessing.
 * 
 * You can retrieve a list of current settings by running Ak::get_constants();
 *
 * If you're running a high load site you might want to fine tune this options 
 * according to your environment. If you set the options implicitly you might 
 * gain in performance but loose in flexibility when moving to a different 
 * environment.
 * 
 * If you need to customize the framework default settings or specify 
 * internationalization options, edit the files at config/environments/*
 */

defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
defined('AK_BASE_DIR') ? null : define('AK_BASE_DIR', str_replace(DS.'config'.DS.'boot.php','',__FILE__));
defined('AK_FRAMEWORK_DIR') ? null : define('AK_FRAMEWORK_DIR', AK_BASE_DIR);
defined('AK_LIB_DIR') ? null : define('AK_LIB_DIR',AK_FRAMEWORK_DIR.DS.'lib');

/**
 *  Before rev.1232 MySQL on some setups, connections where opened using PHP's default 
 *  encoding latin1 this caused that UTF8 data written by Akelos could not be edited using other DB tools.
 *  
 *  We can't automatically fix this for older applications as you might need to backup your database and follow the steps at
 *  http://codesnippets.joyent.com/posts/show/84 before defining AK_SET_UTF8_ON_MYSQL_CONNECT on your configuration file
 *  to fix this issue
 */
defined('AK_SET_UTF8_ON_MYSQL_CONNECT') ? null : define('AK_SET_UTF8_ON_MYSQL_CONNECT', true);

require_once(AK_LIB_DIR.DS.'constants.php');

?>
