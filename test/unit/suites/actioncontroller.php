<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class ActionControllerTestSuite extends AkUnitTestSuite {
    var $partial_tests = true;
    var $baseDir = 'AkActionController/*';
    var $title = 'ActionController';
}
?>