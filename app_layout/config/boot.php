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

defined('DS')                   || define('DS',                     DIRECTORY_SEPARATOR);
defined('AK_BASE_DIR')          || define('AK_BASE_DIR',            str_replace(DS.'config'.DS.'boot.php','',__FILE__));
defined('AK_FRAMEWORK_DIR')     || define('AK_FRAMEWORK_DIR',       AK_BASE_DIR.DS.'vendor'.DS.'akelos');
defined('AK_TESTING_NAMESPACE') || define('AK_TESTING_NAMESPACE',   'akelos');

include AK_FRAMEWORK_DIR.DS.'autoload.php';

// Akelos only shows debug messages if accessed from the localhost IP, you can manually tell
// Akelos wich IP's you consider to be local.
// AkConfig::setOption('local_ips', array('127.0.0.1', '192.168.1.69'));
