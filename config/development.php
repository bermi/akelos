<?php 

// Define constants that are used only on a development environment
// See file boot.php for more info

define('AK_ENABLE_STRICT_XHTML_VALIDATION', false); // At least until the validator is fully tested

// Forces loading database schema on every call
if(isset($_SESSION['__activeRecordColumnsSettingsCache'])){
    unset($_SESSION['__activeRecordColumnsSettingsCache']);
}

?>
