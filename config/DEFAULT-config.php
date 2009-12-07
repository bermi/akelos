<?php

 // Current environment. Options are: development, testing and production
defined('AK_ENVIRONMENT') || define('AK_ENVIRONMENT', 'development');

// Other default settings like database can be found in ./config/**.yml
// these yaml files will be cached as php for improving performance.

// Change if Akelos core files are at another location
// defined('AK_FRAMEWORK_DIR') || define('AK_FRAMEWORK_DIR', '/path/to/the/framework');

// Akelos bootstrapping. Don't delete this comment as it will be used by ./makelos app:define_constants
include dirname(__FILE__).DIRECTORY_SEPARATOR.'boot.php';

