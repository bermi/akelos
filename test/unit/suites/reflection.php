<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class ReflectionTestSuite extends AkUnitTestSuite {
    public $partial_tests = true;
    public $baseDir = 'AkReflection/*';
    public $title = 'Reflection';
}
?>