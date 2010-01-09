<?php

abstract class IdealWorldUnitTest extends AkUnitTest
{
    /**
     * :name=>:args pairs defining the routes the router connects to
     * 
     * array(
     *  'author'=>array('/author/:name',array('controller'=>'author','action'=>'show'))
     * )
     * 
     * results in
     * 
     * $Map->author(:args);
     *
     */
    public $Routes = array();
    
    // we mock away the singletons!
    public function tearDown() {
        AkRouter   ::$singleton = null;
        AkRequest  ::$singleton = null;
        AkUrlWriter::$singleton = null;
    }
    
    /**
     * @return AkUrlWriter
     */
    public function withRequestTo($actual_url) {
        $Router = $this->createRouter();
        $Request = $this->createRequest($actual_url);
        $Request->checkForRoutedRequests($Router);

        return $this->createUrlWriter($Request,$Router);
    }

    /**
     * @return AkUrlWriter
     */
    public function createUrlWriter($Request,$Router) {
        $UrlWriter = new AkUrlWriter($Request,$Router);
        
        AkUrlWriter::$singleton = $UrlWriter;
        return $this->UrlWriter = $UrlWriter;
    }
    
    /**
     * @var AkRouter
     */
    private $Router;
    
    /**
     * @var AkRequest
     */
    private $Request;
    
    /**
     * @return AkRouter
     */
    public function createRouter() {
        $Router = new AkRouter();
        $Router->generate_helper_functions = true;
        foreach ($this->Routes as $name=>$args){
            call_user_func_array(array($Router,$name),$args);
        }
        
        AkRouter::$singleton = $Router;
        return $this->Router = $Router;
    }
    
    /**
     * @return AkRequest
     */
    public function createRequest($url,$method='get') {
        $Request = $this->partialMock('AkRequest',array('getRequestedUrl','getMethod','getRelativeUrlRoot'), array(
            'getRequestedUrl'       => $url,
            'getMethod'             => $method,
            'getRelativeUrlRoot'    => ''
            ));

        AkRequest::$singleton = $Request;
        return $this->Request = $Request;
    }
    
}
