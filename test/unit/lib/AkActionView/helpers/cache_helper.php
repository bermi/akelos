<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'cache_helper.php');
require_once(AK_LIB_DIR.DS.'AkActionView.php');
require_once(AK_LIB_DIR.DS.'AkActionController.php');
require_once(AK_LIB_DIR.DS.'AkRequest.php');

ak_generate_mock('AkRequest');

defined('AK_TEST_MEMCACHED_CHECKFILE')? null: define('AK_TEST_MEMCACHED_CHECKFILE',AK_TEST_DIR.DS.DS.'unit'.DS.'config'.DS.'memcached');
class CacheHelperTests extends HelpersUnitTester 
{
    var $fragment_key;
    
    function setUp()
    {
        $this->controller = &new AkActionController();
        $this->controller->_initCacheHandler();
        $this->controller->Request =& new MockAkRequest($this);
        $this->controller->controller_name = 'test';
        $this->controller->instantiateHelpers();

        $this->cache_helper =& $this->controller->cache_helper;
        
        

        
        
    }
    
    function test_helper_instance()
    {
        $this->assertIsA($this->cache_helper,'CacheHelper');
    }
    
    function _test_init()
    {
        $this->fragment_key = 'key_'.time().microtime(true).'_'.rand(0,1000000);
        $this->fragment_text = "Test Cache Helper With String Key:". $this->fragment_key;
    }
    
    function test_all_caches()
    {
        $cacheHandlers = array('cache_lite'=>1,'akadodbcache'=>2);
        $memcacheEnabled = $this->_checkIfEnabled(AK_TEST_MEMCACHED_CHECKFILE);
        if ($memcacheEnabled) {
            $cacheHandlers['akmemcache'] = 3;
        }
        $unitTests = array('_test_cache_with_string_key','_test_cache_with_string_key_cached');
        
        if(is_a($this->controller->_CacheHandler,'AkCacheHandler')) {
        foreach ($cacheHandlers as $class=>$type) {
            $this->controller->_CacheHandler->_setCacheStore($type);
            $this->_test_init();
            foreach ($unitTests as $test) {
                $this->$test($class);
            }
        }
        } else {
            $this->fail('CacheHandler is not initialized. Please enable the caching system for the unit-test');
        }
    }
    
    
    function _test_cache_with_string_key($class)
    {
        ob_start();
        if (!$this->cache_helper->begin($this->fragment_key)) {
            $this->assertTrue(true);
            echo $this->fragment_text;
            echo $this->cache_helper->end($this->fragment_key);
        } else {
            $this->assertFalse(true,'Should not have been cached: ' . $class);
        }
        $contents = ob_get_clean();
        $fragment = $this->controller->readFragment($this->fragment_key);
        $this->assertEqual($this->fragment_text, $fragment);
        $this->assertEqual($this->fragment_text, $contents);
    }

    function _test_cache_with_string_key_cached($class)
    {
        ob_start();
        if (!$this->cache_helper->begin($this->fragment_key)) {
            $this->assertFalse(true,'Should have been cached: ' . $class);
            echo $this->fragment_text;
            echo $this->cache_helper->end($this->fragment_key);
        } else {
            $this->assertTrue(true);
        }
        $output = ob_get_clean();
        $fragment = $this->controller->readFragment($this->fragment_key);
        $this->assertEqual($this->fragment_text, $fragment);
        $this->assertEqual($this->fragment_text, $output);
    }
}


ak_test('CacheHelperTests');

?>