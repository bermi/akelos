<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class WebserviceTestSuite extends AkUnitTestSuite {
    public $partial_tests = true;
        public $baseDir = 'AkActionWebSer*';
        public $title = 'Webservice';
}
?>