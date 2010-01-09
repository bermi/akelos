<?php

require_once(dirname(__FILE__).'/../config.php');

class AcceptHeader_TestCase extends ActionPackUnitTest 
{
    private $_save_env;
   
    /**
     * @var AkRequest
     */
    private $Request;
    
    public function setUp() {
        $this->_save_env = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'get';
        $this->Request = new AkRequest();
    }
    
    public function tearDown() {
        $_SERVER = $this->_save_env;
    }
    
    public function testAssumeQOfOneIfNoneIsPresent() {
        $this->Request->env['HTTP_ACCEPT'] = 'text/html';
        $this->assertEqual(array('type'=>'text/html','q'=>'1.0'),Ak::last($this->Request->getAcceptHeader()));
    }
    
    public function testMimetypeParserRecoginzesAdditionalParameters() {
        $this->Request->env['HTTP_ACCEPT'] = 'text/html;q=0.9;key=value;throw_away';
        $this->assertEqual(array('type'=>'text/html','q'=>'0.9','key'=>'value','throw_away'),Ak::last($this->Request->getAcceptHeader()));
    }
    
    public function testMimetypeParserHandleFalseParametersNice() {
        $this->Request->env['HTTP_ACCEPT'] = 'text/xml;throw_away';
        $this->assertEqual('xml',$this->Request->getFormat());
    }
    
    public function testPreserveOriginalOrderIfQIsEqual() {
        $this->Request->env['HTTP_ACCEPT'] = 'text/html, application/html';
        $accepts = $this->Request->getAcceptHeader();
        array_walk($accepts,array('self','only_type'));
        
        $this->assertEqual(array('text/html','application/html'),$accepts);
    }
    
    public function testReorderAcceptHeaders() {
        $this->Request->env['HTTP_ACCEPT'] = 'text/html, application/xml;q=0.9, application/xhtml+xml, */*;q=0.1';
        $accepts = $this->Request->getAcceptHeader();
        array_walk($accepts,array('self','only_type'));
        
        $this->assertEqual(array('text/html','application/xhtml+xml', 'application/xml', '*/*'),$accepts);            
    }
    
    public function testDeliverHtmlToOpera() {
        $this->Request->env['HTTP_ACCEPT'] = 'text/html, application/xml;q=0.9, application/xhtml+xml, image/png, image/jpeg, image/gif, image/x-xbitmap, */*;q=0.1';
        $this->assertEqual('html',$this->Request->getFormat());
    }
    
    public function testDeliverHtmlToInternetExplorerOnFirstRequest() {
        //Internet Explorer doesnt prefer anything over anything.
        $this->Request->env['HTTP_ACCEPT'] = '*/*';
        $this->assertEqual('html',$this->Request->getFormat());
    }

    public function testDeliverHtmlToInternetExplorerOnSubsequentRequests() {
        $this->Request->env['HTTP_ACCEPT'] = 'image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, */*';
        $this->assertEqual('html',$this->Request->getFormat());
    }
    
    public function testDeliverHtmlToFirefox2() {
        //Firefox prefers xml over html
        $this->Request->env['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
        $this->assertEqual('html',$this->Request->getFormat());
    }
    
    public function testExplicitlyRequestedFormatOverrulesAnyAcceptHeader() {
        $this->Request->env['HTTP_ACCEPT'] = 'text/xml';
        $this->Request->_request['format'] = 'html';
        
        $this->assertEqual('html',$this->Request->getFormat());
    }

    public function testContentTypeGetsParsed() {
        $this->Request->env['CONTENT_TYPE'] = 'text/xml;charset=utf-8';
        $this->assertEqual('text/xml',$this->Request->getContentType());
    }
    
    public function testGetFormatReturnsContentTypeOnPostRequests() {
        $this->Request->env['REQUEST_METHOD'] = 'post';
        $this->Request->env['CONTENT_TYPE'] = 'text/xml;charset=utf-8';
        
        $this->assertEqual('xml',$this->Request->getFormat());
    }
    
    public function testGetFormatReturnsContentTypeOnPutRequests() {
        $this->Request->env['REQUEST_METHOD'] = 'put';
        $this->Request->env['CONTENT_TYPE'] = 'text/xml;charset=utf-8';
        
        $this->assertEqual('xml',$this->Request->getFormat());
    }
    
    /* ============= ============== =========== */
    
    private static function only_type(&$a) {
        $a = $a['type'];
    }
}

ak_test_case('AcceptHeader_TestCase');

