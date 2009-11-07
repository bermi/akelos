<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class WebserviceTestSuite extends AkUnitTestSuite {
    var $partial_tests = true;
        var $baseDir = 'AkActionWebSer*';
        var $title = 'Webservice';
}
?>