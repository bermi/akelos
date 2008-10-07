<?php

require_once(AK_LIB_DIR.DS.'AkCache.php');
require_once(AK_LIB_DIR.DS.'AkCache'.DS.'AkMemcache.php');

defined('AK_TEST_MEMCACHED_CHECKFILE')? null: define('AK_TEST_MEMCACHED_CHECKFILE',AK_TEST_DIR.DS.DS.'unit'.DS.'config'.DS.'memcached');

class Test_AkMemcache extends  UnitTestCase
{
    /**
     * @var AkMemcache
     */
    var $memcache;
    
    var $check_file = AK_TEST_MEMCACHED_CHECKFILE;
    
    function setUp()
    {
        $cache_settings = Ak::getSettings('caching',false);
        $cache_settings['handler']['type']=3;
        $this->memcache=AkCache::lookupStore($cache_settings);
        $this->assertIsA($this->memcache,'AkCache');
        
    }
    
    function test_init_without_server_fallback_to_default()
    {
        
        $this->memcache=new AkMemcache();
        $res = $this->memcache->init(array());
        $this->assertTrue(true);
    }
    function test_init_with_wrong_server()
    {
        $this->memcache=new AkMemcache();
        
        $res = $this->memcache->init(array('servers'=>array('test:121')));
        $this->assertFalse($res);
        $this->assertError('Could not connect to MemCache daemon');
    }
    
    function test_init_with_wrong_server_using_AkCache_init()
    {
        $cache=new AkCache();
        $res = $cache->init(array('servers'=>array('test:121')),3);
        $this->assertFalse($res);
        $this->assertError('Could not connect to MemCache daemon');
        $this->assertFalse($cache->cache_enabled);
    }
    function test_init_with_wrong_server_using_AkCache_lookupStore()
    {
        $options = array('enabled'=>true,'handler'=>array('type'=>3,'options'=>array('servers'=>array('test:121'))));
        $cache=AkCache::lookupStore($options);
        $this->assertError('Could not connect to MemCache daemon');
        $this->assertFalse($cache);
    }
    function test_set_and_get_string()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $original = 'test';
        $res = $this->memcache->save($original,'test_id_1','strings');
        $stored = $this->memcache->get('test_id_1','strings');
        $this->assertEqual($original,$stored);
        $this->assertTrue(is_string($stored));
    }
    
    function test_set_and_get_integer()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $original = 1111;
        $res = $this->memcache->save($original,'test_id_2','integers');
        $stored = $this->memcache->get('test_id_2','integers');
        $this->assertEqual($original,$stored);
        $this->assertTrue(is_int($stored));
    }
    function test_set_and_get_float()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $original = 11.11;
        $res = $this->memcache->save($original,'test_id_3','floats');
        $stored = $this->memcache->get('test_id_3','floats');
        $this->assertEqual($original,$stored);
        $this->assertTrue(is_float($stored));
    }
    function test_set_and_get_array()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $original = array(0,1,2,3,'test');
        $res = $this->memcache->save($original,'test_id_4','arrays');
        $stored = $this->memcache->get('test_id_4','arrays');
        $this->assertEqual($original,$stored);
        $this->assertTrue(is_array($stored));
    }
    
    function test_set_and_get_object()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $original = new stdClass;
        $original->id = 1;
        $res = $this->memcache->save($original,'test_id_5','objects');
        $stored = $this->memcache->get('test_id_5','objects');
        $this->assertEqual($original,$stored);
        $this->assertTrue(is_object($stored));
        $this->assertEqual($original->id, $stored->id);
    }
    
    function test_set_and_get_objects_within_arrays()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $obj1=new stdClass;
        $obj1->id=1;
        $obj2=new stdClass;
        $obj2->id=2;
        $original = array($obj1,$obj2);
        $res = $this->memcache->save($original,'test_id_6','objects');
        $stored = $this->memcache->get('test_id_6','objects');
        $this->assertEqual($original,$stored);
        $this->assertTrue(is_array($stored));
        $this->assertEqual($original[0]->id, $stored[0]->id);
        $this->assertEqual($original[1]->id, $stored[1]->id);
    }
    
    function test_set_and_get_large_strings()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $original = file_get_contents(__FILE__);
        $res = $this->memcache->save($original,'test_id_7','largestrings');
        $stored = $this->memcache->get('test_id_7','largestrings');
        $this->assertEqual($original,$stored);
        $this->assertTrue(is_string($stored));
    }
    
    function test_set_and_get_binary_data()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $original = file_get_contents(AK_BASE_DIR.DS.'public'.DS.'images'.DS.'akelos_framework_logo.png');
        $res = $this->memcache->save($original,'test_id_8','binary');
        $stored = $this->memcache->get('test_id_8','binary');
        $this->assertEqual($original,$stored);
        $this->assertTrue(is_string($stored));
    }
    
    function test_set_and_get_really_large_string()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $original = $this->_generateLargeString(1000000);
        $res = $this->memcache->save($original,'test_id_9','strings');
        $stored = $this->memcache->get('test_id_9','strings');
        $this->assertEqual($original,$stored);
        $this->assertTrue(is_string($stored));
    }
    function test_set_and_get_really_really_large_string()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $original = $this->_generateLargeString(2000000);
        $res = $this->memcache->save($original,'test_id_10','strings');
        $stored = $this->memcache->get('test_id_10','strings');
        $this->assertEqual($original,$stored);
        $this->assertTrue(is_string($stored));
    }
    
    function test_set_and_remove_key()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $original = $this->_generateLargeString(1000);
        $res = $this->memcache->save($original,'test_id_11','strings');
        $stored = $this->memcache->get('test_id_11','strings');
        $this->assertEqual($original,$stored);
        $this->memcache->remove('test_id_11','strings');
        $afterDelete = $this->memcache->get('test_id_11','strings');
        $this->assertNotEqual($original,$afterDelete);
        $this->assertEqual(null,$afterDelete);
    }
    
    function test_flush_group()
    {
        if (!is_a($this->memcache,'AkCache')) {
            $this->fail('Caching is not enabled. Please enable caching for the unit test');
            return;
        }
        $retrieved = $this->memcache->get('test_id_10','strings');
        $this->assertTrue($retrieved!=null);
        
        $this->memcache->clean('strings');
        
        $retrieved = $this->memcache->get('test_id_10','strings');
        $this->assertTrue($retrieved==null);
        $retrieved = $this->memcache->get('test_id_9','strings');
        $this->assertTrue($retrieved==null);
        $retrieved = $this->memcache->get('test_id_8','strings');
        $this->assertTrue($retrieved==null);
        
        $retrieved = $this->memcache->get('test_id_2','integers');
        $this->assertTrue($retrieved!=null);
    }
    
    function _generateLargeString($size)
    {
        $string = '';
        while(strlen($string)<$size) {
            $string .= md5(time());
        }
        return $string;
    }
}


ak_test('Test_AkMemcache');
?>