<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class ActionViewTestSuite extends AkUnitTestSuite {
        var $partial_tests = true;
        var $excludes = array('AkActionView/helpers/_HelpersUnitTester.php');
        var $baseDir = 'AkActionVie*';
        var $title = 'ActionView';
}
?>