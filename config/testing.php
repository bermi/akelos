<?php 

// Define constants that are used only on a testing environment
// See file available_constants.php for more info


$GLOBALS['ak_test_db_dns'] = isset($dsn) ? $dsn : $testing_database;

?>
