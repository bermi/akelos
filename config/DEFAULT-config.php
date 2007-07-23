<?php

$database_settings = array(
    'production' => array(
        'type' => 'mysql', // mysql, sqlite or pgsql
        'database_file' => '/home/bermi/database.sqlite', // you only need this for SQLite
        'host' => 'localhost',
        'port' => '',
        'database_name' => '',
        'user' => '',
        'password' => '',
        'options' => '' // persistent, debug, fetchmode, new
    ),
    
    'development' => array(
        'type' => 'mysql',
        'database_file' => '',
        'host' => 'localhost',
        'port' => '',
        'database_name' => '',
        'user' => '',
        'password' => '',
        'options' => ''
    ),
    
    // Warning: The database defined as 'testing' will be erased and
    // re-generated from your development database when you run './script/test app'.
    // Do not set this db to the same as development or production.
    'testing' => array(
        'type' => 'mysql',
        'database_file' => '',
        'host' => 'localhost',
        'port' => '',
        'database_name' => '',
        'user' => '',
        'password' => '',
        'options' => ''
    )
);

// If you want to write/delete/create files or directories using ftp instead of local file
// access, you can set an ftp connection string like:
// $ftp_settings = 'ftp://username:password@example.com/path/to_your/base/dir';
$ftp_settings = ''; 

 // Current environment. Options are: development, testing and production
defined('AK_ENVIRONMENT') ? null : define('AK_ENVIRONMENT', 'development');

// defined('AK_FRAMEWORK_DIR') ? null : define('AK_FRAMEWORK_DIR', '/path/to/the/framework');

include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'boot.php');


?>