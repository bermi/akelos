<?php

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

class AkConfig_TestCase extends  AkUnitTest
{
    private $_base_config_path;

    public function __construct()
    {
        $this->_base_config_path = AK_TMP_DIR.DS.'ak_config'.DS.'cache';
        parent::__construct();
    }

    public function setUp()
    {
        $this->Config = new AkConfig();
        copy(AK_FIXTURES_DIR.DS.'config'.DS.'testconfig1.yml', AK_CONFIG_DIR.DS.'testconfig1.yml');
    }

    public function tearDown()
    {
        @unlink($this->_base_config_path.DS.'testing'.DS.'testconfig1.php');
        @unlink($this->_base_config_path.DS.'development'.DS.'testconfig1.php');
        @unlink($this->_base_config_path.DS.'production'.DS.'testconfig1.php');
        @rmdir($this->_base_config_path.DS.'testing');
        @rmdir($this->_base_config_path.DS.'development');
        @rmdir($this->_base_config_path.DS.'production');
        @unlink(AK_CONFIG_DIR.DS.'testconfig1.yml');
        @rmdir($this->_base_config_path);
        @rmdir(AK_TMP_DIR.DS.'ak_config');
    }

    public function test_generate_cache_filename()
    {
        $expected = $this->_base_config_path.DS.'testing'.DS.'testconfig1.php';
        $result = $this->Config->generateCacheFileName('testconfig1','testing');
        $this->assertEqual($expected, $result);
    }

    public function test_write_cache()
    {
        $expectedFileName = $this->_base_config_path.DS.'testing'.DS.'testconfig1.php';
        $config = array('test1' => 1, 'test2' => array('test3' => 3));
        $this->Config->writeCache($config, 'testconfig1', 'testing');
        $cachedConfig = include $expectedFileName;
        $this->assertEqual($config, $cachedConfig);
    }


    public function test_read_cache()
    {
        $config = array('test1'=>1,'test2'=>array('test3'=>3));
        $this->Config->writeCache($config,'testconfig1','testing');
        $cachedConfig = $this->Config->readCache('testconfig1','testing',true);
        $this->assertEqual($config, $cachedConfig);
    }


    public function test_read_config()
    {
        $expectedConfig =array('value1'=>1,'value2'=>2,'value3'=>array('subvalue1'=>1,'subvalue2'=>2,'subvalue3'=>5,'subvalue4'=>array('subsubvalue1'=>2)));
        $config = $this->Config->readConfig('testconfig1','testing');

        $this->assertEqual($expectedConfig, $config);

        $expectedFileNameTesting = $this->_base_config_path.DS.'testing'.DS.'testconfig1.php';
        $this->assertTrue(file_exists($expectedFileNameTesting));

        $expectedFileNameDev = $this->_base_config_path.DS.'development'.DS.'testconfig1.php';
        $this->assertTrue(file_exists($expectedFileNameDev));

        $expectedFileNameProd = $this->_base_config_path.DS.'production'.DS.'testconfig1.php';
        $this->assertTrue(file_exists($expectedFileNameProd));

    }

    public function test_parse_setting_constant()
    {
        $string = '${AK_ENVIRONMENT}';
        $expected = AK_ENVIRONMENT;
        $result = $this->Config->parseSettingsConstants($string);
        $this->assertEqual($expected, $result);

        $string = '${AK_UNDEFINED}';
        $expected = '';
        $result = $this->Config->parseSettingsConstants($string);
        $this->assertEqual($expected, $result);
    }

    public function test_get_with_and_without_cache()
    {
        $expectedConfig =array('value1'=>1,'value2'=>2,'value3'=>array('subvalue1'=>1,'subvalue2'=>2,'subvalue3'=>5, 'subvalue4'=>array('subsubvalue1'=>2)));
        $config = $this->Config->get('testconfig1','testing');
        $this->assertEqual($expectedConfig, $config);

        $expectedConfig =array('value1'=>100,'value2'=>2,'value3'=>array('subvalue1'=>1,'subvalue2'=>2,'subvalue3'=>7,'subvalue4'=>array('subsubvalue1'=>13)));
        $reader = new AkConfig();
        $config = $reader->get('testconfig1','production');
        $this->assertEqual($expectedConfig, $config);
    }
}

ak_test_run_case_if_executed('AkConfig_TestCase');
