<?php
require_once(AK_LIB_DIR.DS.'AkUnitTest'.DS.'AkTestApplication.php');
require_once(AK_LIB_DIR.DS.'AkCache.php');

class Test_AkActionControllerSweeper extends AkTestApplication
{

    public $lastModified;
    
    public function setUp()
    {
        
        $this->instantiateModel('Person');
    }
    public function test_init()
    {
        $this->installAndIncludeModels(array('Person'));
    }
    public function test_request()
    {
        $this->_flushCache('www.example.com');
        $this->setIp('212.121.121.121');
        $this->get('http://www.example.com/cache_sweeper/show');
        $this->assertText('No such user');
        $this->assertResponse(404);
    }
    
    public function _create_user()
    {
        $this->post('http://www.example.com/cache_sweeper/create',
                    array('first_name'=>'Max','last_name'=>'Mustermann'));
        $url = $this->getHeader('Location');
        $parts  = parse_url($url);
        return 'http://www.example.com'.$parts['path'];
        
    }
    
    public function test_create()
    {
        $this->_flushCache('www.example.com');
        $this->setIp('212.121.121.121');
        $this->showUrl=$this->_create_user();
        $params = preg_split('/\//', rtrim($this->showUrl,'/'));
        $this->userId=$params[count($params)-1];
        $this->assertResponse(302);
    }
    
    public function test_show_cached()
    {
        $this->_flushCache('www.example.com');
        $this->setIp('212.121.121.121');
        $this->get($this->showUrl);
        $this->assertResponse(200);
        $this->get($this->showUrl);
        $this->assertResponse(200);
        $this->assertHeader('X-Cached-By','Akelos-Action-Cache');
        $this->_assertCacheExists('/'.Ak::lang().'/cache_sweeper/show/'.$this->userId,array('host'=>'www.example.com'));
        
    }
    public function test_sweeper_update_handled()
    {
        $this->setIp('212.121.121.121');
        $this->showUrl=$this->_create_user();
        $params = preg_split('/\//', rtrim($this->showUrl,'/'));
        $this->userId=$params[count($params)-1];
        $this->get($this->showUrl);
        $this->assertResponse(200);
        $this->_assertCacheExists('/'.Ak::lang().'/cache_sweeper/show/'.$this->userId,array('host'=>'www.example.com'));
        /**
         * calling update, which is handled by the sweeper
         */
        $this->post('http://www.example.com/cache_sweeper/update/'.$this->userId,array('first_name'=>'Max Schmidt'));
        $this->assertResponse(200);
        
        /**
         * cache should have been removed for $this->userId
         */
        $this->_assertCacheNotExists('/'.Ak::lang().'/cache_sweeper/show/'.$this->userId,array('host'=>'www.example.com'));
        
    }
    public function test_sweeper_delete_unhandled()
    {
        $this->setIp('212.121.121.121');
        $this->showUrl=$this->_create_user();
        $params = preg_split('/\//', rtrim($this->showUrl,'/'));
        $this->userId=$params[count($params)-1];
        $this->get($this->showUrl);
        $this->assertResponse(200);
        $this->_assertCacheExists('/'.Ak::lang().'/cache_sweeper/show/'.$this->userId,array('host'=>'www.example.com'));
        /**
         * calling delete, which is not handled by the sweeper
         */
        $this->post('http://www.example.com/cache_sweeper/delete/'.$this->userId,array('first_name'=>'Max Schmidt'));
        $this->assertResponse(200);
        $this->get('http://www.example.com/page_caching/');
        $this->_assertCacheExists('/'.Ak::lang().'/cache_sweeper/show/'.$this->userId,array('host'=>'www.example.com'));
        
    }
    
    public function test_update_sweeper_except()
    {
        $this->post('http://www.example.com/cache_sweeper2/create',
                    array('first_name'=>'Max','last_name'=>'Mustermann'));
        $this->assertResponse(302);
        $url = $this->getHeader('Location');
        $parts  = parse_url($url);
        $this->showUrl = 'http://www.example.com'.$parts['path'];
        $params = preg_split('/\//', rtrim($this->showUrl,'/'));
        $this->userId = $params[count($params)-1];
        
        $this->_assertCacheNotExists('/'.Ak::lang().'/cache_sweeper2/show/'.$this->userId,array('host'=>'www.example.com'));
        $this->get($this->showUrl);
        $this->_assertCacheExists('/'.Ak::lang().'/cache_sweeper2/show/'.$this->userId,array('host'=>'www.example.com'));
        
        /**
         * delete does not call the sweeper, so cache should still exist
         */
        $this->post('http://www.example.com/cache_sweeper2/delete/'.$this->userId);
        $this->get('http://www.example.com/page_caching/');
        $this->_assertCacheExists('/'.Ak::lang().'/cache_sweeper2/show/'.$this->userId,array('host'=>'www.example.com'));
        /**
         * but user does not exist anymore
         */
        $this->post('http://www.example.com/cache_sweeper2/update/'.$this->userId,array());
        $this->assertResponse(404);
    }
    public function _getActionCache($path, $options = array())
    {
        $controller=$this->getController();
        $options['namespace']='actions';
        $fragment = $controller->readFragment($path, $options);
        return $fragment;
    }
    
    public function _assertCacheExists($path, $options = array())
    {
        $fragment = $this->_getActionCache($path, $options);
        $this->assertTrue($fragment!==false);
    }
    
    public function _assertCacheNotExists($path, $options = array())
    {
        $fragment = $this->_getActionCache($path, $options);
        $this->assertTrue($fragment===false);
    }
    public function _flushCache($host)
    {
        $fileCache=AkCache::lookupStore(true);
        if ($fileCache!==false) {
            $fileCache->clean($host);
        }
    }
}