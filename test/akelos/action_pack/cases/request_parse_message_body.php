<?php

require_once(dirname(__FILE__).'/../config.php');

class ParseMessageBody_TestCase extends ActionPackUnitTest
{
    private $_save_env;
        
    /**
     * @var AkRequest
     */
    private $Request;
    
    public function setUp() {
        $this->_save_env = $_SERVER;
    }
    
    public function tearDown() {
        $_SERVER = $this->_save_env;
    }
 
    public function testUnknownContentTypeThrowsNotAcceptableException() {
        $this->expectException('NotAcceptableException');
        $data = '<person><name>Steve</name></person>';
        $Request = $this->createRequest('put', $data,'unknown/format');
    }
    
    public function testOurMockReturnsCorrectMessageBody() {
        $data = '<person><name>Steve</name></person>';
        $Request = $this->createRequest('put', $data);
        $this->assertEqual($data,$Request->getMessageBody());
    }
    
    public function testGetContentType() {
        $Request = $this->createRequest('put', '');
        $this->assertEqual('text/xml',$Request->getContentType());
    }
       
    public function testEmptyMessageReturnsEmptyArray() {
        $data = '';
        $Request = $this->createRequest('put', $data);
        
        $this->assertEqual(array(),$Request->getPutParams());
    }
    
    public function testXmlIsAutomaticallyMergedIntoParams() {
        $data = '<person><name>Steve</name></person>';
        $Request = $this->createRequest('put', $data,'text/xml');

        $this->assertEqual(array('person'=>array('name'=>'Steve')),$Request->getPutParams());
    }
    
    public function testJsonIsAutomaticallyMergedIntoParams() {
        $data = '{"person":{"name":"Steve"}}';
        $Request = $this->createRequest('put', $data,'text/x-json');
        
        $this->assertEqual(array('person'=>array('name'=>'Steve')),$Request->getPutParams());
    }
    
    public function testWwwFormIsAutomaticallyMergedIntoParams() {
        $data = 'person%5Bname%5D=Steve';
        $Request = $this->createRequest('put', $data,'application/x-www-form-urlencoded');
        
        $this->assertEqual(array('person'=>array('name'=>'Steve')),$Request->getPutParams());
    }
    
    public function testXmlIsAutomaticallyMergedIntoParamsOnPost() {
        $data = '<person><name>Steve</name></person>';
        $Request = $this->createRequest('post', $data,'text/xml');

        $this->assertEqual(array('person'=>array('name'=>'Steve')), $Request->getPostParams());
    }
    
    public function testJsonIsAutomaticallyMergedIntoParamsOnPostRequests() {
        $data = '{"person":{"name":"Steve"}}';
        $Request = $this->createRequest('post', $data,'text/x-json');
        
        $this->assertEqual(array('person'=>array('name'=>'Steve')),$Request->getPostParams());
    }
    
    public function testWwwFormIsAutomaticallyMergedIntoParamsOnPost() {
        $_save_POST = $_POST;
        $_POST = array('something'=>'here');
        
        $Request = $this->createRequest('post', 'ignored, uses standard super-global','application/x-www-form-urlencoded');
        $this->assertEqual($_POST,$Request->getPostParams());
        
        $_POST = $_save_POST;
    }
    


/* = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =  = = =  */
    
    /**
     * @return AkRequest
     */
    public function createRequest($method, $data, $content_type = 'text/xml') {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['CONTENT_TYPE']   = $content_type;
        $Request = $this->partialMock('AkRequest', array('getMessageBody', 'getMethod'), 
            array('getMessageBody' => $data, 'getMethod' => $method));
        $Request->init();
        return $this->Request = $Request;
    }
}

ak_test_case('ParseMessageBody_TestCase');

