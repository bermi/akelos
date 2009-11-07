<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class PhpHacksSuite extends AkUnitTestSuite {
    var $partial_tests = true;
        var $baseDir = 'PHP_Hacks/*';
        var $title = 'Php Hacks';
}
?>