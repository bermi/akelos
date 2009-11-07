<?php
/**
 * Database configuration in database.yml
 */
// If you want to write/delete/create files or directories using ftp instead of local file
// access, you can set an ftp connection string like:
// $ftp_settings = 'ftp://username:password@example.com/path/to_your/base/dir';
$ftp_settings = ''; 

 // Current environment. Options are: development, testing and production
defined('AK_ENVIRONMENT') ? null : define('AK_ENVIRONMENT', 'development');

// defined('AK_FRAMEWORK_DIR') ? null : define('AK_FRAMEWORK_DIR', '/path/to/the/framework');

include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'boot.php');


?>