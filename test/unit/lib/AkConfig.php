<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');


require_once(AK_LIB_DIR.DS.'AkConfig.php');

class AkTestConfig extends AkConfig
{
    function AkTestConfig()
    {
        $this->cache_read = false;
        $this->cache_write = false;
    }
    function _readCache($namespace, $environment = AK_ENVIRONMENT, $force = false)
    {
        $config = parent::_readCache($namespace, $environment, $force);
        if ($config) {
            $this->cache_read = true;
        }
        return $config;
    }
    
    function _writeCache($config, $namespace, $environment = AK_ENVIRONMENT, $force = false)
    {
        $this->cache_write=true;
        parent::_writeCache($config, $namespace, $environment, $force);
    }
    
    function _generateCacheFileName($namespace, $environment = AK_ENVIRONMENT)
    {
        $namespace = Ak::sanitize_include($namespace, 'high');
        $cacheFile = AK_TEST_DIR.DS.'fixtures'.DS.'config'.DS.'cache'.DS.$environment.DS.$namespace.'.php';
        return $cacheFile;
    }
    
    function _generateConfigFileName($namespace,$environment = AK_ENVIRONMENT)
    {
        $namespace = Ak::sanitize_include($namespace, 'high');
        $yaml_file_name = AK_TEST_DIR.DS.'fixtures'.DS.'config'.DS.$namespace.'.yml';
        return $yaml_file_name;
    }
}

class AkConfig_TestCase extends  AkUnitTest 
{

    function setUp()
    {
        $this->config = new AkTestConfig();
    }
    function test_generate_cache_filename()
    {
        $expected = AK_TEST_DIR.DS.'fixtures'.DS.'config'.DS.'cache'.DS.'testing'.DS.'testconfig1.php';
        $result = $this->config->_generateCacheFileName('testconfig1','testing');
        $this->assertEqual($expected, $result);
    }
    
    function test_write_cache()
    {
        $expectedFileName = AK_TEST_DIR.DS.'fixtures'.DS.'config'.DS.'cache'.DS.'testing'.DS.'testconfig1.php';
        $config = array('test1'=>1,'test2'=>array('test3'=>3));
        $this->config->_writeCache($config,'testconfig1','testing');
        $cachedConfig = include $expectedFileName;
        $this->assertEqual($config, $cachedConfig);
    }
    
    function test_read_cache()
    {
        $config = array('test1'=>1,'test2'=>array('test3'=>3));
        $this->config->_writeCache($config,'testconfig1','testing');
        $cachedConfig = $this->config->_readCache('testconfig1','testing',true);
        $this->assertEqual($config, $cachedConfig);
        
    }
    
    function test_read_config()
    {
        $expectedConfig =array('value1'=>1,'value2'=>2,'value3'=>array('subvalue1'=>1,'subvalue2'=>2,'subvalue3'=>5,
                               'subvalue4'=>array('subsubvalue1'=>2)));
        $config = $this->config->_readConfig('testconfig1','testing');

        $this->assertEqual($expectedConfig, $config);
        
        $expectedFileNameTesting = AK_TEST_DIR.DS.'fixtures'.DS.'config'.DS.'cache'.DS.'testing'.DS.'testconfig1.php';
        $this->assertTrue(file_exists($expectedFileNameTesting));
        
        $expectedFileNameDev = AK_TEST_DIR.DS.'fixtures'.DS.'config'.DS.'cache'.DS.'development'.DS.'testconfig1.php';
        $this->assertTrue(file_exists($expectedFileNameDev));
        
        $expectedFileNameProd = AK_TEST_DIR.DS.'fixtures'.DS.'config'.DS.'cache'.DS.'production'.DS.'testconfig1.php';
        $this->assertTrue(file_exists($expectedFileNameProd));
        
    }
    
    function test_parse_setting_constant()
    {
        $string = '${AK_ENVIRONMENT}';
        $expected = AK_ENVIRONMENT;
        $result = $this->config->_parseSettingsConstants($string);
        $this->assertEqual($expected, $result);
        
        $string = '${AK_UNDEFINED}';
        $expected = '';
        $result = $this->config->_parseSettingsConstants($string);
        $this->assertEqual($expected, $result);
    }
    
    function test_get_with_and_without_cache()
    {
        $expectedFileName = AK_TEST_DIR.DS.'fixtures'.DS.'config'.DS.'cache'.DS.'testing'.DS.'testconfig1.php';
        @unlink($expectedFileName);
        $expectedConfig =array('value1'=>1,'value2'=>2,'value3'=>array('subvalue1'=>1,'subvalue2'=>2,'subvalue3'=>5,
                               'subvalue4'=>array('subsubvalue1'=>2)));
        $config = $this->config->get('testconfig1','testing');
        
        $this->assertEqual($expectedConfig, $config);
        $this->assertTrue($this->config->cache_write);
        $this->assertFalse($this->config->cache_read);
        
        $expectedConfig =array('value1'=>100,'value2'=>2,'value3'=>array('subvalue1'=>1,'subvalue2'=>2,'subvalue3'=>7,
                               'subvalue4'=>array('subsubvalue1'=>13)));
        $reader = new AkTestConfig();
        $config = $reader->get('testconfig1','production');
        $this->assertEqual($expectedConfig, $config);
        $this->assertFalse($reader->cache_write);
        $this->assertTrue($reader->cache_read);
        
    }
}

ak_test('AkConfig_TestCase',true);

?>