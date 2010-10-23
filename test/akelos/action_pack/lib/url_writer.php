<?php

abstract class UrlWriterUnitTest extends AkUnitTest 
{
    /**
     * @var AkRequest
     */
    protected $Request;

    private $asked_url_for_parameters;

    /**
     * @return AkRequest
     */
    public function withRequestTo($params) {
        return $this->Request = $this->createRequest($params);
    }
    
    /**
     * @param array $options same as AkActionController->urlFor
     * @return UrlWriterTest
     */
    public function urlFor($options) {
        $this->asked_url_for_parameters = $options;
        return $this;
    }
    
    public function isRewrittenTo($expected_params) {
        $this->Router = $Router = $this->createRouter('urlize', $expected_params);
        $UrlWriter = new AkUrlWriter($this->Request, $Router);
        $UrlWriter->urlFor($this->asked_url_for_parameters);
    }
    
    /**
     * @return AkRequest
     */
    public function createRequest($params) {
        $Request = $this->partialMock('AkRequest',array('getParametersFromRequestedUrl'),array('getParametersFromRequestedUrl'=>$params));
        
        return $this->Request = $Request;
    }
    
    /**
     * @return AkRouter
     */
    public function createRouter($method_name,$expected_params=array()) {
        $Router = $this->partialMock('AkRouter',array($method_name), array($method_name => new AkUrl('')));
        $Router->expectOnce($method_name, array($expected_params, null));
        return $this->Router = $Router;
    }
}
