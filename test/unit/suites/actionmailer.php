<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class ActionMailerTestSuite extends AkUnitTestSuite {
    var $partial_tests = array('AkActionMailer');
    var $baseDir = '';
    var $title = 'ActionMailer';
}
?>