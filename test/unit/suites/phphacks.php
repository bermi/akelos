<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class PhpHacksSuite extends AkUnitTestSuite {
    public $partial_tests = true;
        public $baseDir = 'PHP_Hacks/*';
        public $title = 'Php Hacks';
}
?>