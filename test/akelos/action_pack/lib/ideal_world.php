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
    

    private $_singletons = array(
    'AkRouterSingleton' => null,
    'AkRequestSingleton' => null,
    'AkUrlWriterSingleton' => null,
    );

    public function setup(){
        foreach (array_keys($this->_singletons) as $singleton){
            $this->_singletons[$singleton] = Ak::getStaticVar($singleton);
            Ak::unsetStaticVar($singleton);
        }
    }

    public function tearDown(){
        foreach (array_keys($this->_singletons) as $singleton){
            Ak::setStaticVar($singleton, $this->_singletons[$singleton]);
        }
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
        Ak::setStaticVar('AkUrlWriterSingleton', $UrlWriter);
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
        Ak::setStaticVar('AkRouterSingleton', $Router);
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
        Ak::setStaticVar('AkAkRequestSingleton', $Request);
        return $this->Request = $Request;
    }
    
}
