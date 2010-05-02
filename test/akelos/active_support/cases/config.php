<?php

require_once(dirname(__FILE__).'/../config.php');

class Config_TestCase extends  ActiveSupportUnitTest
{
    private $_base_config_path;

    public function __construct() {
        $this->_base_config_path = AkConfig::getDir('tmp').DS.'ak_config'.DS.'cache';
        parent::__construct();
    }

    public function setUp() {
        $this->Config = new AkConfig();
        copy(AkConfig::getDir('fixtures').DS.'testconfig1.yml', AkConfig::getDir('config').DS.'testconfig1.yml');
    }

    public function tearDown() {
        AkFileSystem::file_delete($this->_base_config_path.DS.'testing'.DS.'testconfig1.php', array('base_path' => $this->_base_config_path));
        AkFileSystem::file_delete($this->_base_config_path.DS.'development'.DS.'testconfig1.php', array('base_path' => $this->_base_config_path));
        AkFileSystem::file_delete($this->_base_config_path.DS.'production'.DS.'testconfig1.php', array('base_path' => $this->_base_config_path));
        AkFileSystem::rmdir_tree($this->_base_config_path.DS.'testing');
        AkFileSystem::rmdir_tree($this->_base_config_path.DS.'development');
        AkFileSystem::rmdir_tree($this->_base_config_path.DS.'production');
        AkFileSystem::file_delete(AkConfig::getDir('config').DS.'testconfig1.yml', array('base_path' => $this->_base_config_path));
        AkFileSystem::rmdir_tree($this->_base_config_path);
        AkFileSystem::rmdir_tree(AkConfig::getDir('tmp').DS.'ak_config');
    }

    public function test_generate_cache_filename() {
        $expected = $this->_base_config_path.DS.'testing'.DS.'testconfig1.php';
        $result = $this->Config->getCacheFileName('testconfig1','testing');
        $this->assertEqual($expected, $result);
    }

    public function test_write_cache() {
        $expectedFileName = $this->_base_config_path.DS.AK_ENVIRONMENT.DS.'testconfig1.php';
        $config = array('test1' => 1, 'test2' => array('test3' => 3));
        $this->Config->writeCache($config, 'testconfig1', AK_ENVIRONMENT, true);
        $cachedConfig = include $expectedFileName;
        $this->assertEqual($config, $cachedConfig);
    }

    public function test_read_cache() {
        $config = array('test1'=>1,'test2'=>array('test3'=>3));
        $this->Config->writeCache($config, 'testconfig1', AK_ENVIRONMENT, true);
        $cachedConfig = $this->Config->readCache('testconfig1', AK_ENVIRONMENT, true);
        $this->assertEqual($config, $cachedConfig);
    }

    public function test_read_config() {
        $expectedConfig =array('value1'=>1,'value2'=>2,'value3'=>array('subvalue1'=>1,'subvalue2'=>2,'subvalue3'=>5,'subvalue4'=>array('subsubvalue1'=>2)));
        $config = $this->Config->readConfig('testconfig1','testing', true);

        $this->assertEqual($expectedConfig, $config);

        foreach (array('testing', 'development', 'production') as $environment){
            $this->Config->writeCache($expectedConfig, 'testconfig1', $environment, true);
            $expected_file = $this->Config->getCacheFileName('testconfig1', $environment);
            $this->assertTrue(file_exists($expected_file), "Could not read configuration cache file for $environment");
        }

    }

    public function test_parse_setting_constant() {
        $string = '${AK_ENVIRONMENT}';
        $expected = AK_ENVIRONMENT;
        $result = $this->Config->parseSettingsConstants($string);
        $this->assertEqual($expected, $result);

        $string = '${AK_UNDEFINED}';
        $expected = '';
        $result = $this->Config->parseSettingsConstants($string);
        $this->assertEqual($expected, $result);
    }

    public function test_get_with_and_without_cache() {
        $expectedConfig =array('value1'=>1,'value2'=>2,'value3'=>array('subvalue1'=>1,'subvalue2'=>2,'subvalue3'=>5, 'subvalue4'=>array('subsubvalue1'=>2)));
        $config = $this->Config->get('testconfig1','testing');
        $this->assertEqual($expectedConfig, $config);

        $expectedConfig =array('value1'=>100,'value2'=>2,'value3'=>array('subvalue1'=>1,'subvalue2'=>2,'subvalue3'=>7,'subvalue4'=>array('subsubvalue1'=>13)));
        $reader = new AkConfig(array('skip_cache' => true));
        $config = $reader->get('testconfig1','production');
        $this->assertEqual($expectedConfig, $config);
    }

    public function test_should_return_null_on_unexisting_options() {
        $this->assertEqual(AkConfig::getOption('invalid'), null);
    }

    public function test_should_get_default_option() {
        $this->assertEqual(AkConfig::getOption('invalid', 'default'), 'default');
    }

    public function test_should_get_not_get_default_option_if_already_set() {
        AkConfig::setOption('valid', 'yes');
        $this->assertEqual(AkConfig::getOption('valid', 'default'), 'yes');
    }
}

ak_test_case('Config_TestCase');
