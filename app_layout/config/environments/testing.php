<?php

// Define constants that are used only on a testing environment
// See file boot.php for more info

ini_set('date.timezone', 'UTC');
define('AK_DIE_ON_TRIGGER_ERROR', true);

ini_set('display_errors', 1);
ini_set('memory_limit', -1);
ini_set('log_errors', 0);

error_reporting(-1);

include AK_ACTIVE_SUPPORT_DIR.DS.'error_handlers'.DS.'testing.php';

