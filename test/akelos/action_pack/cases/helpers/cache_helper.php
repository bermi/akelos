<?php

require_once(dirname(__FILE__).'/../helpers.php');

class CacheHelper_TestCase extends HelperUnitTest
{
    public $fragment_key;

    public function setUp()
    {
        $this->controller = new AkActionController();
        $this->controller->_initCacheHandler();
        $this->controller->Request = new MockAkRequest($this);
        $this->controller->controller_name = 'test';
        $this->cache_helper = $this->controller->cache_helper;
    }

    public function test_helper_instance()
    {
        $this->assertIsA($this->cache_helper,'AkCacheHelper');
    }

    public function _test_init()
    {
        $this->fragment_key = 'key_'.time().microtime(true).'_'.rand(0,1000000);
        $this->fragment_text = "Test Cache Helper With String Key:". $this->fragment_key;
    }

    public function test_all_caches()
    {
        $cacheHandlers = array('cache_lite'=>1,'akadodbcache'=>2);
        $memcacheEnabled = AkConfig::getOption('memcached_enabled', AkMemcache::isServerUp());
        if ($memcacheEnabled) {
            $cacheHandlers['akmemcache'] = 3;
        }
        $unitTests = array('_test_cache_with_string_key','_test_cache_with_string_key_cached');

        if($this->controller->_CacheHandler instanceof AkCacheHandler) {
            foreach ($cacheHandlers as $class=>$type) {
                $this->controller->_CacheHandler->setCacheStore($type);
                $this->_test_init();
                foreach ($unitTests as $test) {
                    $this->$test($class);
                }
            }
        } else {
            $this->fail('CacheHandler is not initialized. Please enable the caching system for the unit-test');
        }
    }


    public function _test_cache_with_string_key($class)
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

    public function _test_cache_with_string_key_cached($class)
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


ak_test_case('CacheHelper_TestCase');
