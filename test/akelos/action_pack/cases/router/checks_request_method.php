<?php

require_once(dirname(__FILE__).'/../router.php');

class ChecksRequestMethod_TestCase extends AkRouteUnitTest
{
    public function testMatchesPostRequest() {
        $this->withRoute('/author/:name',array(),array(),array('method'=>'post'));
        
        $this->get('/author/martin','post')->matches(array('name'=>'martin'));
        $this->get('/author/martin','get') ->doesntMatch();
    }
    
    public function testMatchesAnyRequestedMethod() {
        $this->withRoute('/author/:name',array(),array(),array('method'=>ANY));
        
        $this->get('/author/martin','get')->matches(array('name'=>'martin'));
        $this->get('/author/martin','post')->matches(array('name'=>'martin'));
        $this->get('/author/martin','delete')->matches(array('name'=>'martin'));
        $this->get('/author/martin','put')->matches(array('name'=>'martin'));
        $this->get('/author/martin','head')->matches(array('name'=>'martin'));
    }
    
    public function testMatchesPostAndPutRequest() {
        $this->withRoute('/author/:name',array(),array(),array('method'=>'post,put'));
        
        $this->get('/author/martin','post')->matches(array('name'=>'martin'));
        $this->get('/author/martin','put') ->matches(array('name'=>'martin'));
        $this->get('/author/martin','get') ->doesntMatch();
    }
}

ak_test_case('ChecksRequestMethod_TestCase');