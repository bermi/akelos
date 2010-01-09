<?php

require_once(dirname(__FILE__).'/../router.php');

class ControllerUrlFor_TestCase extends AkControllerUnitTest
{
    public function __construct() {
        AkConfig::setDir('suite', realpath(dirname(__FILE__).'/../../'));
        $this->rebaseAppPaths();
    }

    public function __destruct() {
        parent::__destruct();
    }
    
    public function setUp() {
        $this->useController('locale_detection');
    }

    public function testUrlFromIndexToList() {
        $this->get('index');
        $controller = $this->Controller;

        $this->assertEqual('/locale_detection/list',$controller->urlFor(array('action'=>'list','only_path'=>true)));
        $this->assertEqual('http://localhost/locale_detection/list',$controller->urlFor(array('action'=>'list')));
    }

    public function testUrlFromSessionWithIdToList() {
        $this->get('session',array('id'=>'1234'));

        $this->assertEqual('/locale_detection/list',$this->Controller->urlFor(array('action'=>'list','only_path'=>true)));
    }

    public function _testUrlFromSessionWithIdToAnotherId() {
        $this->markTestIncomplete('Not implemented.');
        $this->get('session',array('id'=>'123'));

        $this->assertEqual('/locale_detection/session/345',$this->Controller->urlFor(array('id'=>'345','only_path'=>true)));
    }



    /* = = = = = = = = Test API = = = = = = = = */

    public function getMethodsToMockForController() {
        $methods = parent::getMethodsToMockForController();
        $methods[] = 'getUrlWriter';
        return $methods;
    }

    public function setExpectations() {
        $this->Controller->returnsByValue('getUrlWriter', $this->createUrlWriter());
        parent::setExpectations();
    }

    public function createUrlWriter() {
        $UrlWriter = new AkUrlWriter($this->Request,$this->createRouter());
        return $UrlWriter;
    }

    public function createRouter() {
        $Router = new AkRouter();
        $Router->addRoute('default',new AkRoute('/:controller/:action/:id'));
        return $Router;
    }
}

ak_test_case('ControllerUrlFor_TestCase');