<?php

require_once(dirname(__FILE__).'/../router.php');

class ApiOfRouteTestCase extends AkRouteUnitTest
{
    public function testWithRouteInstantiatesARoute() {
        $this->withRoute('/person/:name');
        $this->assertType('AkRoute',$this->Route);
    }

    public function testMockedRequestCanBeAskedAboutRequestedUrl() {
        $Request = $this->createRequest('/person/martin');
        $this->assertEqual('/person/martin',$Request->getRequestedUrl());
    }

    public function testMockedRequestCanBeAskedAboutRequestedMethod() {
        $Request = $this->createRequest('/person/martin','post');
        $this->assertEqual('post',$Request->getMethod());
    }
}

ak_test_case('ApiOfRouteTestCase');

