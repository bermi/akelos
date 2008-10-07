<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class CacheTestSuite extends AkUnitTestSuite {
    var $partial_tests = true;
        var $baseDir = 'AkCach*';
        var $title = 'Caching';
}
?>