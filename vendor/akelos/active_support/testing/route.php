<?php

abstract class AkRouteUnitTest extends AkUnitTest
{
    /**
     * @var AkRequest
     */
    protected $Request;
    
    /**
     * @var AkRoute
     */
    protected $Route;

    /**
     * @return AkRequest
     */
    public function createRequest($url,$method='get') {
        $Request = $this->mock('AkRequest', array('getRequestedUrl'=>$url,'getMethod'=>$method));
        return $this->Request = $Request;
    }
    
    /**
     * takes the same arguments as the constructor of a Route
     *
     * @return Route_TestCase
     */
    public function withRoute($url_pattern, $defaults = array(), $requirements = array(), $conditions = array()) {
        $this->Route = new AkRoute($url_pattern,$defaults,$requirements,$conditions);
        return $this;
    }

    /**
     * @return Route_TestCase
     */
    public function get($url,$method='get') {
        $this->Request = $this->createRequest($url,$method);
        return $this;
    }
    
    public function doesntMatch() {
        try{
            $actual = $this->Route->parametrize($this->Request);
            $this->fail("Expected 'no match', but actually matched: \n\r".var_export($actual,true));
        } catch (RouteDoesNotMatchRequestException $e) {
            $this->pass();
        }
    }
    
    public function matches($params=array()) {
        $actual = $this->Route->parametrize($this->Request);
        $this->assertEqual($params,$actual);
    }
    
    /**
     * @return Route_TestCase
     */
    public function urlize($params = array()) {
        $this->params = $params;
        return $this;
    }
    
    public function returns($url) {
        $this->assertEqual($url,(string)$this->Route->urlize($this->params));
    }
    
    public function returnsFalse() {
        try {
            $actual = $this->Route->urlize($this->params);
            $this->fail('Expected \'no match\', but actually got: '.$actual);
        } catch (RouteDoesNotMatchParametersException $e) { $this->pass(); }
    }
}
