<?php

require_once(dirname(__FILE__).'/../router.php');

class RouteWithLangSegment_TestCase extends AkRouteUnitTest
{
    public function setUp() {
        $this->withRoute('/:lang/person/:name');
    }
    
    public function testLangSegmentIsHandledBySegmentFactory() {
        $segments = $this->Route->getSegments();
        $this->assertType('AkLangSegment',$segments['lang']);
    }
    
    public function testLangCanBeOmittedOnParametrize() {
        $this->get('/person/martin')->matches(array('name'=>'martin'));
    }
    
    public function testLangHasAutomaticRequirements() {
        $this->get('/jp/person/martin')->doesntMatch();
        foreach (Ak::langs() as $lang){
            $this->get("/$lang/person")->matches(array('lang'=>$lang));
        }
    }

    public function testLangCanBeOmittedOnUrlize() {
        $this->urlize(array('name'=>'martin'))->returns('/person/martin');
    }
    
    public function testCanUrlizeAvailableLocales() {
        $this->urlize(array('lang'=>'en'))->returns('/en/person');
        $this->urlize(array('lang'=>'es','name'=>'martin'))->returns('/es/person/martin');
    }
    
    public function testBreakUrlizeOnUnknownLocales() {
        $this->urlize(array('lang'=>'jp'))->returnsFalse();
    }
    
    public function testExplicitRequirementsOverwriteTheAutomaticOnes() {
        $this->withRoute('/:lang/person',array(),array('lang'=>'[a-z]{2}'));
        $this->urlize(array('lang'=>'jp'))->returns('/jp/person');
    }
    
    public function testRouterConnectAddsLangSegmentAutomatically() {
        $Router = new AkRouter();
        $Router->person('/person/:name');
        
        $routes = $Router->getRoutes();
        $segments = $routes['person']->getSegments();
        $this->assertArrayHasKey('lang',$segments);
    }    
}

ak_test_case('RouteWithLangSegment_TestCase');