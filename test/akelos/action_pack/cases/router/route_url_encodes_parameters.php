<?php

require_once(dirname(__FILE__).'/../router.php');

class RouteUrlencodesParameters_TestCase extends AkRouteUnitTest
{
    public function testParametrizeDecodesReturnedParameters() {
        $this->withRoute('/author/:name')->get('/author/Martin+L.+Degree')->matches(array('name'=>'Martin L. Degree'));
    }
    
    public function testUrlizeEncodesGivenParameters() {
        $this->withRoute('/author/:name')->urlize(array('name'=>'Martin L. Degree'))->returns('/author/Martin+L.+Degree');
    }
    public function testParametrizeDecodesReturnedParametersWithFormat() {
        $this->withRoute('/author/:name.:format', array('format'=>COMPULSORY))->get('/author/Martin+L.+Degree.pdf')->matches(array('name'=>'Martin L. Degree', 'format' => 'pdf'));
    }
    
    public function testUrlizeEncodesGivenParametersWithFormat() {
        $this->withRoute('/author/:name.:format', array('format'=>COMPULSORY))->urlize(array('name'=>'Martin L. Degree', 'format' => 'pdf'))->returns('/author/Martin+L.+Degree.pdf');
    }
}

ak_test_case('RouteUrlencodesParameters_TestCase');

