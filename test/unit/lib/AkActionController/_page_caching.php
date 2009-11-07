<?php
require_once(AK_LIB_DIR.DS.'AkUnitTest'.DS.'AkTestApplication.php');
require_once(AK_LIB_DIR.DS.'AkCache.php');

class Test_AkActionControllerCachingPages extends AkTestApplication
{

    public $lastModified;

    public function testRequest()
    {
        $this->_flushCache('www.example.com');
        $this->setIp('212.121.121.121');
        $this->get('http://www.example.com/');
        $this->assertText('Test::page::index');
        $this->assertResponse(200);
    }

    public function _flushCache($host)
    {
        $fileCache=AkCache::lookupStore(true);
        if ($fileCache!==false) {
            $fileCache->clean($host);
        }
    }

    public function test_should_cache_get_with_ok_status()
    {
        $this->setIp('212.121.121.121');
        $this->get('http://www.example.com/page_caching/ok');

        $this->assertWantedPattern('/^\s*$/');
        $this->assertResponse(200);
        $this->assertTrue($this->_assertPageCached('/page_caching/ok'));


    }
    public function test_should_cache_get_with_ok_status_gzipped_and_unzipped()
    {
        $this->_flushCache('www.example.com');

        $this->assertTrue($this->_assertPageNotCached('/page_caching/simple'));
        $this->setAcceptEncoding('gzip');
        $this->assertTrue($this->_assertPageNotCached('/page_caching/simple'));
        $this->setAcceptEncoding('');

        $this->setIp('212.121.121.121');
        $this->get('http://www.example.com/page_caching/simple');
        $this->assertText('Simple Text');
        $this->assertFalse($this->getHeader('Content-Encoding'));
        $this->assertResponse(200);
        $this->assertTrue($this->_assertPageCached('/page_caching/simple'));
        $this->setAcceptEncoding('gzip');
        $this->assertTrue($this->_assertPageCached('/page_caching/simple'));
    }

    public function test_should_cache_get_with_ok_status_gzipped()
    {
        $this->_flushCache('www.example.com');
        $this->setIp('212.121.121.121');
        $this->setAcceptEncoding('gzip');
        $this->get('http://www.example.com/page_caching/simple');
        $decoded = $this->gzdecode($this->_response);
        $this->assertEqual('Simple Text', $decoded);

        $this->assertHeader('Content-Encoding','gzip');
        $this->assertResponse(200);
        $this->assertTrue($this->_assertPageCached('/page_caching/simple'));
        $this->setAcceptEncoding('gzip');
        $this->assertTrue($this->_assertPageCached('/page_caching/simple'));

    }
    public function gzdecode($data) {
        $len = strlen($data);
        if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
            return null;  // Not GZIP format (See RFC 1952)
        }
        $method = ord(substr($data,2,1));  // Compression method
        $flags  = ord(substr($data,3,1));  // Flags
        if ($flags & 31 != $flags) {
            // Reserved bits are set -- NOT ALLOWED by RFC 1952
            return null;
        }
        // NOTE: $mtime may be negative (PHP integer limitations)
        $mtime = unpack("V", substr($data,4,4));
        $mtime = $mtime[1];
        $xfl   = substr($data,8,1);
        $os    = substr($data,8,1);
        $headerlen = 10;
        $extralen  = 0;
        $extra     = "";
        if ($flags & 4) {
            // 2-byte length prefixed EXTRA data in header
            if ($len - $headerlen - 2 < 8) {
                return false;    // Invalid format
            }
            $extralen = unpack("v",substr($data,8,2));
            $extralen = $extralen[1];
            if ($len - $headerlen - 2 - $extralen < 8) {
                return false;    // Invalid format
            }
            $extra = substr($data,10,$extralen);
            $headerlen += 2 + $extralen;
        }

        $filenamelen = 0;
        $filename = "";
        if ($flags & 8) {
            // C-style string file NAME data in header
            if ($len - $headerlen - 1 < 8) {
                return false;    // Invalid format
            }
            $filenamelen = strpos(substr($data,8+$extralen),chr(0));
            if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
                return false;    // Invalid format
            }
            $filename = substr($data,$headerlen,$filenamelen);
            $headerlen += $filenamelen + 1;
        }

        $commentlen = 0;
        $comment = "";
        if ($flags & 16) {
            // C-style string COMMENT data in header
            if ($len - $headerlen - 1 < 8) {
                return false;    // Invalid format
            }
            $commentlen = strpos(substr($data,8+$extralen+$filenamelen),chr(0));
            if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
                return false;    // Invalid header format
            }
            $comment = substr($data,$headerlen,$commentlen);
            $headerlen += $commentlen + 1;
        }

        $headercrc = "";
        if ($flags & 1) {
            // 2-bytes (lowest order) of CRC32 on header present
            if ($len - $headerlen - 2 < 8) {
                return false;    // Invalid format
            }
            $calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
            $headercrc = unpack("v", substr($data,$headerlen,2));
            $headercrc = $headercrc[1];
            if ($headercrc != $calccrc) {
                return false;    // Bad header CRC
            }
            $headerlen += 2;
        }

        // GZIP FOOTER - These be negative due to PHP's limitations
        $datacrc = unpack("V",substr($data,-8,4));
        $datacrc = $datacrc[1];
        $isize = unpack("V",substr($data,-4));
        $isize = $isize[1];

        // Perform the decompression:
        $bodylen = $len-$headerlen-8;
        if ($bodylen < 1) {
            // This should never happen - IMPLEMENTATION BUG!
            return null;
        }
        $body = substr($data,$headerlen,$bodylen);
        $data = "";
        if ($bodylen > 0) {
            switch ($method) {
                case 8:
                    // Currently the only supported compression method:
                    $data = gzinflate($body);
                    break;
                default:
                    // Unknown compression method
                    return false;
            }
        } else {
            // I'm not sure if zero-byte body content is allowed.
            // Allow it for now...  Do nothing...
        }

        // Verifiy decompressed size and CRC32:
        // NOTE: This may fail with large data sizes depending on how
        //       PHP's integer limitations affect strlen() since $isize
        //       may be negative for large sizes.
        if ($isize != strlen($data) || crc32($data) != $datacrc) {
            // Bad format!  Length or CRC doesn't match!
            return false;
        }
        return $data;
    }
    public function test_should_cache_get_with_ok_status_xgzipped()
    {
        $this->_flushCache('www.example.com');
        $this->setIp('212.121.121.121');
        $this->setAcceptEncoding('x-gzip');
        $this->get('http://www.example.com/page_caching/simple');
        $decoded = $this->gzdecode($this->_response);
        $this->assertEqual('Simple Text', $decoded);
        $this->assertHeader('Content-Encoding','x-gzip');
        $this->assertResponse(200);
        $this->assertTrue($this->_assertPageCached('/page_caching/simple'));
        $this->setAcceptEncoding('gzip');
        $this->assertTrue($this->_assertPageCached('/page_caching/simple'));

    }
    public function _expirePage($path)
    {
        $controller=$this->getController();
        if ($controller) {
            return $controller->expirePage($path);
        } else {
            return false;
        }
    }
    public function _getCachedPage($path)
    {
        $controller=$this->getController();
        if ($controller) {
            $cachedPage = $controller->getCachedPage($path);
        } else {
            $pageCache = &Ak::singleton('AkCacheHandler',$null);
            $null = null;
            $pageCache->init($null, 'file');
            $cachedPage=$pageCache->getCachedPage($path);
        }
        return $cachedPage;
    }

    public function _assertPageCached($path, $message = false)
    {
        $cachedPage = $this->_getCachedPage($path);
        $this->assertTrue($cachedPage!=false,$message==false?"$path should be cached":$message);
        return $cachedPage!=false && file_exists($cachedPage);
    }
    public function _assertPageNotCached($path, $message = '%s')
    {
        $cachedPage = $this->_getCachedPage($path);
        $this->assertTrue($cachedPage==false,sprintf($message,$path));
        return $cachedPage==false;
    }
    public function test_last_modified()
    {
        $this->setIp('212.121.121.121');
        $this->addIfModifiedSince('Sat, 12 Jul 2008 15:59:46 GMT');
        $this->get('http://www.example.com/page_caching/simple');
        $this->assertHeader('X-Cached-By','Akelos');
        $this->assertHeader('Last-Modified',null);
        $this->lastModified = $this->getHeader('Last-Modified');
    }

    public function test_if_modified_since_304()
    {
        $this->setIp('212.121.121.121');
        $this->addIfModifiedSince($this->lastModified);
        $this->get('http://www.example.com/page_caching/simple');
        $this->assertHeader('X-Cached-By','Akelos');
        $this->assertResponse(304);
    }

    public function test_should_cache_with_custom_path()
    {
        $this->setIp('212.121.121.121');
        $this->get('http://www.example.com/page_caching/custom_path');
        $this->assertText('Akelos rulez');
        $this->assertTrue($this->_assertPageCached('/index.html'));
    }

    public function test_should_expire_cache_with_custom_path()
    {
        $this->get('http://www.example.com/page_caching/custom_path');
        $this->assertTrue($this->_assertPageCached('/index.html'));

        $this->get('http://www.example.com/page_caching/expire_custom_path');
        $this->assertTrue($this->_assertPageNotCached('/index.html'));
    }

    public function test_should_cache_without_trailing_slash_on_url()
    {
        $controller=$this->getController();
        $controller->cachePage('cached content', '/page_caching_test/trailing_slash');
        $this->assertTrue($this->_assertPageCached('/page_caching_test/trailing_slash.html'));
    }

    public function test_should_cache_with_trailing_slash_on_url()
    {
        $controller=$this->getController();
        $controller->cachePage('cached content', '/page_caching_test/trailing_slash/');
        $this->assertTrue($this->_assertPageCached('/page_caching_test/trailing_slash.html'));
    }

    public function test_caches_only_get_and_ok()
    {
        $methods = array('get','post','put','delete');
        $actions = array('ok','no_content','found','not_found');
        foreach ($actions as $action) {
            foreach ($methods as $method) {
                $path='/page_caching/'.$action;
                $this->$method($path);
                if ($this->getHeader('Status') == 200 && $method=='get') {
                    $this->assertTrue($this->_assertPageCached($path, 'action ok with GET request should be cached'));
                } else {
                    $this->assertTrue($this->_assertPageNotCached($path,' action '.$action.' with '.strtoupper($method).' should not be cached'));
                }
            }
        }
    }
    public function test_expiry_of_locale_based_normalized_url()
    {
        $this->assertTrue(true, 'Need to test that expirePage(array("action"=>"index","controller"=>"page","lang"=>"es")) on expires cache http://mydomain.com/es/page and http://mydomain.com/es/page/index');
        
        $this->get('http://www.example.com/es/page_caching');
        $this->_assertPageCached('/es/page_caching/index.html');
        $this->_assertPageCached('/es/page_caching/index');
        $this->_assertPageCached('/es/page_caching/');
        $this->_expirePage(array('controller'=>'page_caching','lang'=>'es'));
        $this->_assertPageNotCached('/es/page_caching/');
        $this->_assertPageNotCached('/es/page_caching/index');
        $this->_assertPageNotCached('/es/page_caching/index.html');
        
    }
    
    public function test_cache_skip()
    {
        $this->_flushCache('www.example.com');
        $this->get('http://www.example.com/page_caching/skip',array(),array(),array());
        $this->assertTextMatch('Hello<!--CACHE-SKIP-START-->
        
        You wont see me after the cache is rendered.
        
        <!--CACHE-SKIP-END-->');
        $this->get('http://www.example.com/page_caching/skip',array(),array(),array());
        $this->assertTextMatch('Hello');
    }
    
    public function test_expiry_of_alllocale_based_normalized_urls()
    {
        $this->assertTrue(true, 'Need to test that expirePage(array("action"=>"index","controller"=>"page","lang"=>"*")) on expires cache http://mydomain.com/**/page and http://mydomain.com/**/page/index');
        $this->get('http://www.example.com/es/page_caching');
        $this->get('http://www.example.com/en/page_caching');
        $this->_assertPageCached('/es/page_caching/index.html');
        $this->_assertPageCached('/es/page_caching/index');
        $this->_assertPageCached('/es/page_caching/');
        $this->_assertPageCached('/en/page_caching/index.html');
        $this->_assertPageCached('/en/page_caching/index');
        $this->_assertPageCached('/en/page_caching/');
        $this->_expirePage(array('controller'=>'page_caching','lang'=>'*'));
        $this->_assertPageNotCached('/es/page_caching/');
        $this->_assertPageNotCached('/es/page_caching/index');
        $this->_assertPageNotCached('/es/page_caching/index.html');
        $this->_assertPageNotCached('/en/page_caching/');
        $this->_assertPageNotCached('/en/page_caching/index');
        $this->_assertPageNotCached('/en/page_caching/index.html');
    }
    public function test_page_cache_priority_before_action_cache() 
    {
        $this->assertTrue(true,'Need to test that if actioncache and pagecache are configured, the page cache is getting the priority 1');
        $this->_flushCache('www.example.com');
        $this->get('http://www.example.com/page_caching/priority');
        $this->assertTextMatch('page');
        $this->_assertPageCached('/page_caching/priority');
    }

    public function test_normalization_of_urls_render_cache()
    {
        $this->_flushCache('www.example.com');
        $this->assertTrue(true, 'Need to test that http://mydomain.com/page renders the same cached version as http://mydomain.com/page/index');
        $this->get('http://www.example.com/page_caching');
        $etag1 = $this->getHeader('ETag');
        $this->get('http://www.example.com/page_caching/index');
        $etag2 = $this->getHeader('ETag');
        $this->assertEqual($etag1,$etag2);
    }
    public function test_expiry_of_normalized_urls()
    {
        $this->assertTrue(true, 'Need to test that expirePage(array("action"=>"index","controller"=>"page")) expires caches  http://mydomain.com/page and http://mydomain.com/page/index');
        $this->get('http://www.example.com/page_caching');
        $this->_assertPageCached('/page_caching/index.html');
        $this->_assertPageCached('/page_caching/index');
        $this->_assertPageCached('/page_caching/');
        $this->_expirePage(array('controller'=>'page_caching'));
        $this->_assertPageNotCached('/page_caching/');
        $this->_assertPageNotCached('/page_caching/index');
        $this->_assertPageNotCached('/page_caching/index.html');
    }
    
    public function test_clean_cache()
    {
        $this->_flushCache('www.example.com');

    }
    
    public function test_format_caching()
    {
        $this->_flushCache('www.example.com');
        $this->get('http://www.example.com/page_caching/format');
        $this->assertHeader('Content-Type','text/html');
        $this->assertTextMatch('<h1>hello business</h1>');
        
        $this->_assertPageCached('/page_caching/format');
        $this->_assertPageCached('/page_caching/format.html');
        $this->_assertPageNotCached('/page_caching/format.xml');
        $this->_assertPageNotCached('/page_caching/format.csv');
        
        $this->get('http://www.example.com/page_caching/format');
        $this->assertHeader('Content-Type','text/html');
        $this->assertHeader('X-Cached-By','Akelos');
        $this->assertTextMatch('<h1>hello business</h1>');
        
        $this->get('http://www.example.com/page_caching/format.xml');
        $this->assertHeader('Content-Type','application/xml');
        $this->assertTextMatch('<hello>business</hello>');
        
        $this->_assertPageCached('/page_caching/format');
        $this->_assertPageCached('/page_caching/format.html');
        $this->_assertPageCached('/page_caching/format.xml');
        $this->_assertPageNotCached('/page_caching/format.csv');
        
        $this->get('http://www.example.com/page_caching/format.xml');
        $this->assertHeader('Content-Type','application/xml');
        $this->assertTextMatch('<hello>business</hello>');
        $this->assertHeader('X-Cached-By','Akelos');
        
        $this->get('http://www.example.com/page_caching/format.csv');
        $this->assertHeader('Content-Type','text/csv');
        $this->assertTextMatch('hello,business');
        
        $this->_assertPageCached('/page_caching/format');
        $this->_assertPageCached('/page_caching/format.html');
        $this->_assertPageCached('/page_caching/format.xml');
        $this->_assertPageCached('/page_caching/format.csv');
        
        $this->get('http://www.example.com/page_caching/format.csv');
        $this->assertHeader('Content-Type','text/csv');
        $this->assertTextMatch('hello,business');
        $this->assertHeader('X-Cached-By','Akelos');

    }
    
    public function test_format_specific_caching()
    {
        $this->_flushCache('www.example.com');
        $this->get('http://www.example.com/page_caching/formatspecific');
        $this->assertHeader('Content-Type','text/html');
        $this->assertTextMatch('html format');
        $this->_assertPageNotCached('/page_caching/formatspecific.html');
        
        $this->get('http://www.example.com/page_caching/formatspecific.js');
        $this->assertHeader('Content-Type','application/x-javascript');
        $this->assertTextMatch('javascript format');
        $this->_assertPageCached('/page_caching/formatspecific.js');
        $this->_assertPageNotCached('/page_caching/formatspecific.html');
    }
    
    public function test_caching_with_get_parameters()
    {
        $this->_flushCache('www.example.com');
        
        $this->get('http://www.example.com/page_caching/get_parameters',array('version'=>1));
        $this->assertHeader('Content-Type','text/html');
        $this->assertTextMatch('version:1');
        $_GET=array('version'=>1);
        $this->_assertPageCached('/page_caching/get_parameters.html');
        $_GET=array();
        $this->_assertPageNotCached('/page_caching/get_parameters.html');
        
        $this->get('http://www.example.com/page_caching/get_parameters');
        $this->assertHeader('Content-Type','text/html');
        $this->assertTextMatch('version:');
        $_GET=array();
        $this->_assertPageCached('/page_caching/get_parameters.html');
        $_GET=array('version'=>1);
        $this->_assertPageCached('/page_caching/get_parameters.html');

    }
}