<?php
require_once(AK_LIB_DIR.DS.'AkUnitTestSuite.php');
    
class CoreTestSuite extends AkUnitTestSuite {
    public $partial_tests = array(
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
    public $baseDir = '';
    public $title = 'Core Tests';
}
?>