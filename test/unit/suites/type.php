<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class TypeTestSuite extends AkUnitTestSuite {
    var $partial_tests = true;
        var $baseDir = 'AkType*';
        var $title = 'Types';
}
?>