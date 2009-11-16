<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class ActionViewTestSuite extends AkUnitTestSuite {
        public $partial_tests = true;
        public $excludes = array('AkActionView/helpers/_HelpersUnitTester.php');
        public $baseDir = 'AkActionVie*';
        public $title = 'ActionView';
}
?>