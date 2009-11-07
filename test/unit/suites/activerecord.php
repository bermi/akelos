<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class ActiveRecordTestSuite extends AkUnitTestSuite {
    var $partial_tests = true;
    var $baseDir = 'AkActiveRecord/*';
    var $title = 'ActiveRecord';
}
?>