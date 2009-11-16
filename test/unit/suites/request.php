<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class RequestTestSuite extends AkUnitTestSuite {
    public $partial_tests = true;
    public $baseDir = 'AkRequest/*';
    public $title="Request";
}
?>