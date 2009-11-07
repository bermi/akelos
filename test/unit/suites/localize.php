<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class LocalizeTestSuite extends AkUnitTestSuite {
    var $partial_tests = true;
        var $baseDir = 'AkLocalize/*';
        var $title = 'Localize';
}
?>