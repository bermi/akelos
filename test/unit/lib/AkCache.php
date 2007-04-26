<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');


require_once(AK_LIB_DIR.DS.'AkCache.php');

//$db =& Ak::db();
//$db->debug = true;

class Test_of_AkCache_Class extends  UnitTestCase
{
    
    var $_driverInstance = NULL;
    var $Cache = NULL;
    var $id = 'test case cache id';
    var $group = 'test case group to cacth';
    var $text_to_catch = 'this is the text to catch on the test case of the AkCache class';
    
    function setUp()
    {
        $this->Cache =& new AkCache();
    }
    
    function tearDown()
    {
        unset($this->Cache);
    }
    
        
    function Testinit()
    {
        //No driver is loaded
        $this->Cache->init(null,0);
        $this->assertNull($this->Cache->_driverInstance,'Checking that no driver is loaded when cache is disabled');
        
        //Pear Cache Lite driver is loaded
        $this->Cache->init(null,1);
        $this->assertIsA($this->Cache->_driverInstance,'cache_lite');
        
        //AdodbCache database cache driver loaded
        $this->Cache->init(null,2);
        $this->assertIsA($this->Cache->_driverInstance,'akadodbcache');
    }
    
    function Test_get_and_save()
    {
        
        //No cache
        $this->Cache->init(null,0);
        $data = $this->Cache->get('id');
        $this->assertFalse($data,'Cache not enabled so this must return false');
        $this->assertFalse(!$this->Cache->save($this->text_to_catch, $this->id, $this->group),'saving on the file cache must not work because cache is disabled');
        
        //Cache Lite cache
        $this->Cache->init(1,1);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertFalse($data,'This id must not be in the cache (File based)');
        $this->assertFalse(!$this->Cache->save($this->text_to_catch, $this->id, $this->group),'saving the  cache (File based)');
        $this->Cache->init(1,1);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertEqual($data, $this->text_to_catch,'Getting cached data (File based)');
        sleep(2);
        $this->Cache->init(1,1);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertFalse($data,'The cache has expired and we recognize it (File based)');
        
        
        
        // Database cache
        $this->Cache->init(1,2);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertFalse($data,'This id must not be in the cache (Database based)');
        $this->assertFalse(!$this->Cache->save($this->text_to_catch, $this->id, $this->group),'saving the cache (Database based)');
        $this->Cache->init(1,2);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertEqual($data, $this->text_to_catch,'Getting cached data (Database based)');
        sleep(2);
        $this->Cache->init(1,2);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertFalse($data,'The cache has expired and we recognize it (Database based)');
        

    }
    
    function Testremove()
    {
        
        $this->Cache->init(1,0);
        $this->assertFalse(!$this->Cache->remove($this->id, $this->group),'Removing cached file (Cache disabled must return success)');
        
        //Cache Lite cache
        $this->Cache->init(1,1);
        $this->assertFalse(!$this->Cache->save($this->text_to_catch, $this->id, $this->group),'saving the cache (File based)');
        $this->Cache->init(1,1);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertEqual($data, $this->text_to_catch,'Checking that cached data has been inserted (File based)');
        $this->assertFalse(!$this->Cache->remove($this->id, $this->group),'Removing cached file (File based)');
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertFalse($data,'The cache must have been removed at this point but stills here (File based)');
        
        
        //Database cache
        $this->Cache->init(1,2);
        $this->assertFalse(!$this->Cache->save($this->text_to_catch, $this->id, $this->group),'saving the cache (Database based)');
        $this->Cache->init(1,2);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertEqual($data, $this->text_to_catch,'Checking that cached data has been inserted (Database based)');
        $this->Cache->remove($this->id, $this->group);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertFalse($data,'The cache must have been removed at this point but stills here (Database based)');

    }
    
    function Testclean()
    {
                
        //AkCache::clean($group = 'false', $mode = 'ingroup');
        $this->Cache->init(1,1);
        $this->assertFalse(!$this->Cache->save($this->text_to_catch, $this->id, $this->group),'saving on the file cache');
        $this->Cache->init(1,1);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertEqual($data, $this->text_to_catch,'Checking that cached data has been inserted (File based)');
        
        $this->Cache->init(1,1);
        $this->assertFalse(!$this->Cache->clean($this->group),'Removing all the items in cache');
        
        $this->Cache->init(1,1);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertFalse($data,'The cache must have been removed at this point but stills here');
        
        
        
        //AkCache::clean($group = 'false', $mode = 'ingroup');
        $this->Cache->init(1,2);
        $this->assertFalse(!$this->Cache->save($this->text_to_catch, $this->id, $this->group),'saving on the file cache');
        $this->Cache->init(1,2);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertEqual($data, $this->text_to_catch,'Checking that cached data has been inserted (File based)');
        
        $this->Cache->init(1,2);
        $this->assertFalse(!$this->Cache->clean($this->group),'Removing all the items in cache');
        
        $this->Cache->init(1,2);
        $data = $this->Cache->get($this->id, $this->group);
        $this->assertFalse($data,'The cache must have been removed at this point but stills here');

    }

}

ak_test('Test_of_AkCache_Class', true);

?>
