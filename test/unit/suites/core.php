<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class CoreTestSuite extends AkUnitTestSuite {
    var $partial_tests = array(
        'Ak',
        'AkConfig',
        'AkUnitTest',
        'AkTestApplication',
        'AkSession',
        'AkRouter',
        'AkLocaleManager',
        'AkInstaller',
        'AkPluginInstaller',
        'AkInflector',
        'AkImage',
        'AkHttpClient',
        'AkDbSession',
        'AkReflection'
        
        );
    var $baseDir = '';
    var $title = 'Core Tests';
}
?>