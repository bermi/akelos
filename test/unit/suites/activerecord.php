<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class ActiveRecordTestSuite extends AkUnitTestSuite {
    public $partial_tests = true;
    public $baseDir = 'AkActiveRecord/*';
    public $title = 'ActiveRecord';
}
?>