<?php

require_once(dirname(__FILE__).'/../config.php');

class PluginInstaller_TestCase extends ActiveSupportUnitTest
{
    public function __construct() {
        parent::__construct();
        $this->template_path =      AkConfig::getDir('fixtures').DS.'plugin_installer_target_class.php.tpl';
        $this->target_path =        AkConfig::getDir('fixtures').DS.'plugin_installer_target_class.php';
        $this->extenssion_path =    AkConfig::getDir('fixtures').DS.'plugin_installer_method_extensions.php';
    }

    public function setUp() {
        $this->PluginInstaller = new AkPluginInstaller();
        $this->PluginInstaller->app_app_dir = AkConfig::getDir('fixtures');
        copy($this->template_path, $this->target_path);
        $this->PluginInstaller->extension_points = array('PluginInstallerTargetClass' => 'plugin_installer_target_class.php');
    }

    public function tearDown() {
        unlink($this->target_path);
    }

    public function test_install_methods() {
        $this->PluginInstaller->installExtensions('file:'.$this->extenssion_path,'TEST');

        $reflection = new AkReflectionFile($this->target_path);
        $classes = $reflection->getClasses();
        $this->assertEqual(1, count($classes));
        $extension1 = $classes[0]->getMethod('extension1');
        $this->assertEqual('extension1', $extension1->getName());
    }

    public function test_remove_methods() {
        $this->PluginInstaller->installExtensions('file:'.$this->extenssion_path,'TEST');
        $reflection = new AkReflectionFile($this->target_path);
        $classes = $reflection->getClasses();
        $this->assertEqual(1,count($classes));
        $extension1 = $classes[0]->getMethod('extension1');
        $this->assertEqual('extension1', $extension1->getName());

        $orgContents = file_get_contents($this->template_path);
        $newContents = file_get_contents($this->target_path);
        $this->assertNotEqual($orgContents,$newContents);

        $this->PluginInstaller->removeExtensions('TEST');
        $orgContents = file_get_contents($this->template_path);
        $newContents = file_get_contents($this->target_path);
        $this->assertEqual($orgContents, $newContents);
    }

}

ak_test_case('PluginInstaller_TestCase');
