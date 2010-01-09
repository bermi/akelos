<?php

require_once(dirname(__FILE__).'/../router.php');

class Url_TestCase extends AkRouteUnitTest
{
    public function testUrlizeReturnsHttpQuery() {
        $url = $this->createUrl('/author/martin');
        $url->setRewriteEnabled(false);

        $this->assertEqual('/?ak=/author/martin',$url->path());
    }
    
    public function testUrlizeAppendsAdditionalParametersWithAnAmpersand() {
        $url = $this->createUrl('/author/martin','age=23');
        $url->setRewriteEnabled(false);

        $this->assertEqual('/?ak=/author/martin&age=23',$url->path());
    }
    
    public function testTrailingSlash() {
        $url = $this->createUrl('/author/martin');
        $url->setOptions(array('trailing_slash'=>true));
        
        $this->assertEqual('/author/martin/',$url->path());
    }
    
    public function testTrailingSlashWithQueryString() {
        $url = $this->createUrl('/author/martin','age=23');
        $url->setOptions(array('trailing_slash'=>true));
        
        $this->assertEqual('/author/martin/?age=23',$url->path());
    }
    
    public function testAddAnchor() {
        $url = $this->createUrl('/author/martin');
        $url->setOptions(array('anchor'=>'field'));
        
        $this->assertEqual('/author/martin#field',$url->path());
    }
    
    public function testRelativeUrlPart() {
        $Request = $this->partialMock('AkRequest',array('getRelativeUrlRoot'));
        $Request->returnsByValue('getRelativeUrlRoot', '/subfolder');
                
        $url = new AkUrl('/author/martin');
        $url->setOptions(array('skip_relative_url_root'=>false,'relative_url_root'=>'/subfolder'));
            
        $this->assertEqual('/subfolder/author/martin',$url->path());
    }
    
    public function testUrl()
    {
        $url = $this->createUrl('/author');
        
        $this->assertEqual('http://localhost/author',$url->url());
    }
    
    public function testToStringMethodDecidesIfOnlyThePathWillBeReturned()
    {
        $url = $this->createUrl('/author');
        $this->assertEqual('http://localhost/author',"$url");

        $url->setOptions(array('only_path'=>true));
        $this->assertEqual('/author',"$url");
    }
    
    /**
     * @return AkUrl
     */
    public function createUrl($path,$query='')
    {
        $Request = $this->partialMock('AkRequest',array('getRelativeUrlRoot','getProtocol','getHostWithPort'), array(
                'getRelativeUrlRoot'    => '',
                'getProtocol'           => 'http',    
                'getHostWithPort'       => 'localhost'
                ));
        

        $url = new AkUrl($path,$query);
        $url->setOptions(array('relative_url_root'=>'','protocol'=>'http','host'=>'localhost'));
                
        return $this->Url = $url;
    }
}

ak_test_case('Url_TestCase');
