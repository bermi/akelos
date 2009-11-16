<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class LocalizeTestSuite extends AkUnitTestSuite {
    public $partial_tests = true;
        public $baseDir = 'AkLocalize/*';
        public $title = 'Localize';
}
?>