<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class PluginTestSuite extends AkUnitTestSuite {
    var $partial_tests = true;
        var $baseDir = 'AkPlugin/*';
        var $title = 'Plugins';
}
?>