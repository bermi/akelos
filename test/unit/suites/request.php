<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class RequestTestSuite extends AkUnitTestSuite {
    var $partial_tests = true;
    var $baseDir = 'AkRequest/*';
    var $title="Request";
}
?>