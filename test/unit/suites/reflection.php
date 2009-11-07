<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class ReflectionTestSuite extends AkUnitTestSuite {
    var $partial_tests = true;
    var $baseDir = 'AkReflection/*';
    var $title = 'Reflection';
}
?>