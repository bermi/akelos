<?php 

// Define constants that are used only on a development environment
// See file available_constants.php for more info

define('AK_ENABLE_STRICT_XHTML_VALIDATION', false); // At least until the validator is fully tested

// Forces loading database schema on every call
unset($_SESSION['__activeRecordColumnsSettingsCache']);

?>
