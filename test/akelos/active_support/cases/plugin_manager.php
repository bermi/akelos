<?php

require_once(dirname(__FILE__).'/../config.php');

class PluginManager_TestCase extends ActiveSupportUnitTest
{
    public function __construct() {
        parent::__construct();
        $this->offline_mode = !(@file_get_contents('http://svn.akelos.org/plugins'));
    }

    public function skip(){
        $this->skipIf($this->offline_mode, '['.get_class($this).'] Internet connection unavailable.');
    }
    
    public function test_remove_repositories_config() {
        AkFileSystem::directory_delete(AkConfig::getDir('plugins').DS.'acts_as_versioned');
        @AkFileSystem::file_delete(AkConfig::getDir('config').DS.'plugin_repositories.txt');
    }

    public function setup() {
        $this->PluginManager = new AkPluginManager();
        @AkFileSystem::file_delete(AkConfig::getDir('tmp').DS.'plugin_repositories.yaml');
    }

    public function test_should_get_available_repositories() {
        $repositories = $this->PluginManager->getAvailableRepositories();
        $this->assertTrue(in_array('http://svn.akelos.org/plugins', $repositories));
    }

    public function test_should_add_new_repository() {
        $this->PluginManager->addRepository('http://svn.editam.com/plugins');
        $this->PluginManager->addRepository('http://svn.example.com/plugins');
        $repositories = $this->PluginManager->getAvailableRepositories();
        $this->assertTrue(in_array('http://svn.akelos.org/plugins', $repositories));
        $this->assertTrue(in_array('http://svn.editam.com/plugins', $repositories));
    }

    public function test_should_remove_repository() {
        $repositories = $this->PluginManager->getAvailableRepositories(true);
        $this->assertEqual(count($repositories), 3);

        $this->PluginManager->removeRepository('http://svn.editam.com/plugins');
        $repositories = $this->PluginManager->getAvailableRepositories(true);
        $this->assertFalse(in_array('http://svn.editam.com/plugins', $repositories));
        $this->assertEqual(count($repositories), 2);

        $this->PluginManager->removeRepository('http://svn.example.com/plugins');
        $repositories = $this->PluginManager->getAvailableRepositories(true);
        $this->assertEqual(count($repositories), 1);
    }

    public function test_should_get_remote_plugin_list() {
        $plugins = $this->PluginManager->getPlugins();
        $this->assertEqual($plugins['acts_as_versioned'], 'http://svn.akelos.org/plugins');
    }

    public function test_should_install_plugin() {
        $this->PluginManager->installPlugin('acts_as_versioned');
        $this->assertTrue(in_array('acts_as_versioned', $this->PluginManager->getInstalledPlugins()));
    }

    public function test_should_update_plugin() {
        AkFileSystem::directory_delete(AkConfig::getDir('plugins').DS.'acts_as_versioned'.DS.'lib');
        $this->assertFalse(file_exists(AkConfig::getDir('plugins').DS.'acts_as_versioned'.DS.'lib'.DS.'ActsAsVersioned.php'));
        $this->PluginManager->updatePlugin('acts_as_versioned');
        $this->assertTrue(file_exists(AkConfig::getDir('plugins').DS.'acts_as_versioned'.DS.'lib'.DS.'ActsAsVersioned.php'));
    }

    public function test_should_uninstall_plugin() {
        clearstatcache();
        $this->PluginManager->uninstallPlugin('acts_as_versioned');
        $this->assertFalse(is_dir(AkConfig::getDir('plugins').DS.'acts_as_versioned'));
    }

    public function test_should_get_remote_repositories_listing() {
        $repositories = $this->PluginManager->getRepositoriesFromRemotePage();
        $this->assertEqual($repositories[0], $this->PluginManager->main_repository);
    }

    public function test_remove_plugin() {
        AkFileSystem::directory_delete(AkConfig::getDir('plugins').DS.'acts_as_versioned');
    }
}

ak_test_case('PluginManager_TestCase');

