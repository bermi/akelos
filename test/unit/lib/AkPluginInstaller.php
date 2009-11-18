<?php

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

class AkPluginInstaller_TestCase extends  AkUnitTest
{
    public function setUp()
    {
        $this->PluginInstaller = new AkPluginInstaller();
        $this->PluginInstaller->app_app_dir = AK_TEST_DIR.DS.'fixtures'.DS.'app';
        $tplFilename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'plugin_installer_target_class.php.tpl';
        $targetFilename = $this->PluginInstaller->app_app_dir.DS.'plugin_installer_target_class.php';
        copy($tplFilename, $targetFilename);
        $this->PluginInstaller->extension_points=array('PluginInstallerTargetClass'=>'plugin_installer_target_class.php');
    }

    public function test_install_methods()
    {
        $this->PluginInstaller->installExtensions('file:'.AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'plugin_installer_method_extensions.php','TEST');

        $checkFilename = AK_TEST_DIR.DS.'fixtures'.DS.'app'.DS.'plugin_installer_target_class.php';
        $reflection = new AkReflectionFile($checkFilename);
        $classes = $reflection->getClasses();
        $this->assertEqual(1,count($classes));
        $extension1 = $classes[0]->getMethod('extension1');
        $this->assertEqual('extension1',$extension1->getName());
    }

    public function test_remove_methods()
    {
        $this->PluginInstaller->installExtensions('file:'.AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'plugin_installer_method_extensions.php','TEST');
        $checkFilename = $this->PluginInstaller->app_app_dir.DS.'plugin_installer_target_class.php';
        $reflection = new AkReflectionFile($checkFilename);
        $classes = $reflection->getClasses();
        $this->assertEqual(1,count($classes));
        $extension1 = $classes[0]->getMethod('extension1');
        $this->assertEqual('extension1',$extension1->getName());

        $orgContents = file_get_contents($tplFilename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'plugin_installer_target_class.php.tpl');
        $newContents = file_get_contents($checkFilename);
        $this->assertNotEqual($orgContents,$newContents);

        $this->PluginInstaller->removeExtensions('TEST');
        $orgContents = file_get_contents($tplFilename = AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'plugin_installer_target_class.php.tpl');
        $newContents = file_get_contents($checkFilename);
        $this->assertEqual($orgContents,$newContents);
    }

}

ak_test_case('AkPluginInstaller_TestCase');
