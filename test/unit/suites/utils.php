<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class UtilTestSuite extends AkUnitTestSuite {
    var $partial_tests = array(
'_Ak_convert',
'_Ak_file_functions',
'_Ak_object_inspection',
//'Ak_file_functions_over_ftp',
'_Ak_support_functions',
);
        var $baseDir = 'utils/';
        var $title = 'Utilities';
}
?>