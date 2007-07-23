<?php 

// Define constants that are used only on a testing environment
// See file boot.php for more info

@ini_set('display_errors', 1);
@ini_set('memory_limit', -1);

$GLOBALS['ak_test_db_dns'] = isset($dsn) ? $dsn : $testing_database;

?>
