<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

abstract class AkControllerUnitTest extends AkUnitTest
{
    public $controller_name;

    /**
     * @var AkActionController
     */
    public $Controller;

    public $Request;

    /**
     * @var AkResponse
     */
    public $Response;

    public function useController($controller_name) {
        $this->controller_name = $controller_name;
    }
    
    protected function _enableAssertions() {

        parent::_enableAssertions();

        $this->extendClassLazily('AkRoutingAssertions',
        array(
        'methods' => array (
        'assertRecognizes',
        'assertGenerates',
        'assertRouting',
        'nextAssertionUsingRouter',
        'recognizeRouteForPath',
        ),
        'autoload_path' => dirname(__FILE__).DS.'assertions'.DS.'routing_assertions.php'
        ));
    }
    
    
    public function process($request_type,$action_name,$options) {
        $this->action_name = $action_name;
        $this->addExpectationsDependendOnActionName($action_name);

        $Request = $this->createRequest($request_type,$action_name,$options);
        $Response = $this->createResponse();
        $Controller = $this->createController($this->controller_name);

        $this->setExpectations();

        $Controller->process($this->Request,$this->Response);
    }

    public function get($action_name,$options=array()) {
        $this->process('get',$action_name,$options);
    }

    public function post($action_name,$options=array()) {
        $this->process('post',$action_name,$options);
    }

    public function createRequest($method,$action_name,$options) {
        $params = array_merge(array('controller'=>$this->controller_name,'action'=>$action_name),$options);

        $Request = $this->partialMock('AkRequest',array('getMethod','getParametersFromRequestedUrl'), array(
        'getMethod' => $method,
        'getParametersFromRequestedUrl' => $params
        ));

        // HACK  fix ->getParams
        foreach ($params as $k=>$v){
            $Request->$k = $v;
            $Request->_request[$k] = $v;
        }//HACK
        return $this->Request = $Request;
    }

    public function createResponse() {
        return $this->Response = $this->mock('AkResponse');
    }

    public function createController($controller_name) {
        $controller_class_name = AkInflector::camelize($controller_name).'Controller';
        $this->Controller = $this->partialMock($controller_class_name,$this->getMethodsToMockForController());
        #$this->Controller->Template = $this->getMock('AkActionView');
        return $this->Controller;
    }

    public function addExpectationsDependendOnActionName($action_name) {
        if (!empty($this->expectDefaultRender)){
            $this->expectedMethods['renderWithALayout'] = array($action_name);
        }
        if (!empty($this->expectActionNotCalled)){
            $this->unexpectedMethods[] = $action_name;
        }
    }

    public function setExpectations() {
        $this->Controller->returnsByValue('getControllerName', $this->controller_name);

        foreach ((array)@$this->expectedMethods as $method=>$arguments){
            $this->Controller->expectOnce($method, array($arguments));
        }

        foreach ((array)@$this->unexpectedMethods as $method){
            $this->Controller->expectNever($method);
        }
        $this->clearExpectations();
    }

    public function clearExpectations() {
        unset($this->expectDefaultRender,$this->expectedMethods,$this->unexpectedMethods);

    }

    private $expectDefaultRender;
    private $expectActionNotCalled;
    private $expectedMethods   = array();
    private $unexpectedMethods = array();
    private $action_name;

    /**
     * @return AkUnitTest
     */
    public function expectDefaultRender() {
        $this->expectDefaultRender = true;
        return $this;
    }

    /**
     * accepts same arguments as AkActionController->render()
     *
     * @return AkUnitTest
     */
    public function expectRender($options,$status=null)
    {
        $this->expectedMethods['render'] = func_get_args();
        # since we mock the actual render, performed? is still false, so the defaultRender triggers
        #$this->setPerformedToTrue();
        $this->expectDefaultRender();
        return $this;
    }

    public function setPerformedToTrue() {
        $this->Controller->expect('_hasPerformed', array(true));
    }

    public function expectFilterCalled($filter_name) {
        $this->expectedMethods[$filter_name] = array();
        return $this;
    }

    public function expectFilterNotCalled($filter_name) {
        $this->unexpectedMethods[] = $filter_name;
        return $this;
    }

    public function expectActionNotCalled() {
        $this->expectActionNotCalled = true;
        return $this;
    }

    /**
     * accepts same arguments as AkActionController->redirectTo
     *
     * @return AkUnitTest
     */
    public function expectRedirectTo($options) {
        $this->expectedMethods['redirectTo'] = array($options);
        $this->expectDefaultRender();
        return $this;
    }

    public function assertAssign($variable_name,$expected) {
        if (!isset($this->Controller->$variable_name)) $this->fail("Variable <$variable_name> not assigned.");
        $this->assertEqual($expected,$this->Controller->$variable_name);
    }

    public function assertFlash($scope,$message){
        $this->assertArrayContains($this->Controller->flash,array($scope=>$message));
    }

    public function assertFlashNow($scope,$message) {
        $this->assertArrayContains($this->Controller->flash_now,array($scope=>$message));
    }

    public function getMethodsToMockForController() {
        $default_methods = array('_assertExistanceOfTemplateFile','_addInstanceVariablesToAssigns','getControllerName');
        $default_methods = array('render','getControllerName','_handleFlashAttribute');
        $methods = array_merge (array_keys((array)@$this->expectedMethods),(array)@$this->unexpectedMethods,$default_methods);
        return array_unique($methods);
    }
}
