<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');
require_once(AK_LIB_DIR.DS.'AkReflection'.DS.'AkReflectionFile.php');


require_once(AK_LIB_DIR.DS.'AkPluginInstaller.php');

   
      

class AkPluginInstaller_TestCase extends  AkUnitTest 
{
    public function setUp()
    {
        $this->installer = new AkPluginInstaller();
        $tplFilename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'plugin_installer_target_class.php.tpl';
        $targetFilename = AK_APP_DIR.DS.'plugin_installer_target_class.php';
        copy($tplFilename,$targetFilename);
        $this->installer->extension_points=array('PluginInstallerTargetClass'=>'plugin_installer_target_class.php');
    }

    
    public function test_install_methods()
    {
        $this->installer->installExtensions('file:'.AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'plugin_installer_method_extensions.php','TEST');
        $checkFilename = AK_APP_DIR.DS.'plugin_installer_target_class.php';
        $reflection = new AkReflectionFile($checkFilename);
        $classes = $reflection->getClasses();
        $this->assertEqual(1,count($classes));
        $extension1 = $classes[0]->getMethod('extension1');
        $this->assertEqual('extension1',$extension1->getName());
    }
    
    
    public function test_remove_methods()
    {
        $this->installer->installExtensions('file:'.AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'plugin_installer_method_extensions.php','TEST');
        $checkFilename = AK_APP_DIR.DS.'plugin_installer_target_class.php';
        $reflection = new AkReflectionFile($checkFilename);
        $classes = $reflection->getClasses();
        $this->assertEqual(1,count($classes));
        $extension1 = $classes[0]->getMethod('extension1');
        $this->assertEqual('extension1',$extension1->getName());
        
        $orgContents = file_get_contents($tplFilename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'plugin_installer_target_class.php.tpl');
        $newContents = file_get_contents($checkFilename);
        $this->assertNotEqual($orgContents,$newContents);
        
        $this->installer->removeExtensions('TEST');
        $orgContents = file_get_contents($tplFilename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'plugin_installer_target_class.php.tpl');
        $newContents = file_get_contents($checkFilename);
        $this->assertEqual($orgContents,$newContents);
    }

}

ak_test('AkPluginInstaller_TestCase',true);

?>
