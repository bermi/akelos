<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class TypeTestSuite extends AkUnitTestSuite {
    public $partial_tests = true;
        public $baseDir = 'AkType*';
        public $title = 'Types';
}
?>