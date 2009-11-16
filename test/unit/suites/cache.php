<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class CacheTestSuite extends AkUnitTestSuite {
    public $partial_tests = true;
        public $baseDir = 'AkCach*';
        public $title = 'Caching';
}
?>