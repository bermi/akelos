<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

abstract class AkRouterUnitTest extends AkUnitTest {

    /**
     * @var AkRouter
     */
    public $Router;

    protected $params;
    private   $reciprocity = false;
    private   $errors = false;

    public function setUp() {
        $this->useDefaultMap();
    }

    public function useDefaultMap() {
        $this->useMap(AK_ROUTES_MAPPING_FILE);
    }

    public function useMap($map_file) {
        $this->instantiateRouter()->loadMap($map_file);
    }

    public function instantiateRouter() {
        return $this->Router = new AkRouter();
    }

    public function connect($url_pattern, $options = array(), $requirements = array()) {
        $this->Router->connect($url_pattern, $options, $requirements);
    }

    public function checkReciprocity($bool = true) {
        return $this->reciprocity = $bool;
    }

    /**
     * @param string $url
     * @return PHPUnit_Routing_TestCase
     */
    public function get($url) {
        $Request = $this->createRequest($url);
        try {
            $this->params = $this->Router->match($Request);
            if ($this->reciprocity){
                $this->assertEqual($url, $this->Router->urlize($this->params)->path());
            }
        }catch (NoMatchingRouteException $e){
            $this->errors = true;
        }
        return $this;
    }

    public function createRequest($url,$method='get') {
        return $this->Request = $this->mock('AkRequest', array(
        'getRequestedUrl'   => $url,
        'getMethod'         => $method,
        ));
    }

    /**
     * ->resolvesTo(array('controller'=>'blog','action'=>'index'))
     * ->resolvesTo('blog','index')
     * @param mixed a hash (arg[0]) or a list of values (args*)
     * /
    public function resolvesTo() {
        $this->ensureMatch();

        $params = func_get_args();
        if ($this->is_hash($params[0])){ // ->resolvesTo(array('controller'=>'blog','action'=>'index'));
            return $this->assertEqual($params[0],$this->params);
        }else{                           // ->resolvesTo('blog','index');
            return $this->assertSameValues($params,$this->params);
        }
    }

    public function doesntResolve() {
        $this->ensureNoMatch();
    }

    public function assert404() {
        $this->ensureNoMatch();
    }

    private function ensureNoMatch() {
        if (!$this->hasErrors()) $this->fail("Expected no match, actually got a match.");
    }

    private function ensureMatch() {
        if ($this->hasErrors()) $this->fail("Expected a match, actually got no match.");
    }

    private function hasErrors() {
        return $this->errors;
    }

    private function ensureParameterIsSet($param_name) {
        $this->assertArrayHasKey($param_name,$this->params,"Router did not set the parameter $param_name.");
    }

    public function assertParameterEquals($expected,$param_name) {
        $this->ensureMatch();
        $this->ensureParameterIsSet($param_name);
        $this->assertEqual($expected,$this->params[$param_name],"Expected $param_name to be <$expected>, actually is <{$this->params[$param_name]}>.");
    }

    public function assertParameterNotSet($param_name) {
        // we can't use assertArrayNotHasKey because sometimes a parameter is set to an empty string
        // $this->assertArrayNotHasKey($param_name,$this->params);
        if (isset($this->params[$param_name]) && !empty($this->params[$param_name])){
            $this->fail("Parameter $param_name was set, but was not expected.");
        }
    }

    // though __call would match these, we define some basic assertions for type-h(i|u)nting IDE's
    public function assertController($controller_name) {
        $this->assertParameterEquals($controller_name,'controller');
    }

    public function assertAction($action_name) {
        $this->assertParameterEquals($action_name,'action');
    }

    public function assertId($id) {
        $this->assertParameterEquals($id,'id');
    }

    public function __call($method_name,$args) {
        if (preg_match('/^assert(.*)$/',$method_name,$matches)){
            $param = strtolower($matches[1]);
            return $this->assertParameterEquals($args[0],$param);
        }
        throw new BadMethodCallException("Call to unknown method <$method_name> in ".__CLASS__.".");
    }

    /**
     * Compares the values of the array and the values of the hash ignoring its keys
     * /
    public function assertSameValues($array,$hash) {
        $k = 0;
        foreach ($hash as $key=>$value){
            if (!isset($array[$k])){
                return $this->fail("Parameter <$key> not expected, but in actual.");
            }
            $this->assertEqual($array[$k++],$value);
        }
        if ($k < count($array)){
            return $this->fail("Expected <{$array[$k]}>, not in actual.");
        }
        return true;
    }

    private function is_hash($hash) {
        return is_array($hash) && !isset($hash[0]);
    }
    */
}


