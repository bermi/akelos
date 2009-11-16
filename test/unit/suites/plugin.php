<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class PluginTestSuite extends AkUnitTestSuite {
    public $partial_tests = true;
        public $baseDir = 'AkPlugin/*';
        public $title = 'Plugins';
}
?>