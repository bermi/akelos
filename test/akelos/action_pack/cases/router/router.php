<?php

require_once(dirname(__FILE__).'/../router.php');

class Router_TestCase extends RouterUnitTest
{
    /**
     * @var Router
     */
    public $Router;

    public function setUp() {
        $this->Router = new AkRouter();
    }

    public function testInstantiateRouter() {
        $Router = new AkRouter();
    }

    public function testAddRoute() {
        $this->Router->addRoute(null,new AkRoute('person/:name'));
        $this->assertEqual(1, count($this->Router->getRoutes()));
        $this->assertEqual(array(0),array_keys($this->Router->getRoutes()));
    }

    public function testAddNamedRoute() {
        $this->Router->addRoute('person',new AkRoute('person/:name'));
        $this->assertEqual(1, count($this->Router->getRoutes()));
        $this->assertEqual(array('person'),array_keys($this->Router->getRoutes()));
    }

    public function testConnectAddsUnnamedRoute() {
        $this->Router->connect('person/:name');
        $this->assertEqual(1, count($this->Router->getRoutes()));
        $this->assertEqual(array(0),array_keys($this->Router->getRoutes()));
    }

    public function testInterceptCallToUnknownMethodsAndAddNamedRoute() {
        $this->Router->person('person/:name');

        $this->assertEqual(1, count($this->Router->getRoutes()));
        $this->assertEqual(array('person'),array_keys($this->Router->getRoutes()));
    }


    public function testMatchThrowsAnExcpetionIfRequestCannotBeSolved() {
        $Request = $this->createRequest('');
        $PersonRoute = $this->mock('AkRoute', array(
        'parametrize' => new RouteDoesNotMatchRequestException));
        $this->Router->addRoute('person', $PersonRoute);
        $this->expectException('NoMatchingRouteException');
        $this->Router->match($Request);
    }

    public function testMatchTraversesAllRegisteredRoutesIfFalseIsReturned() {
        $Request = $this->createRequest('');

        $PersonRoute = $this->mock('AkRoute', array(
        'parametrize' => new RouteDoesNotMatchRequestException));

        $AuthorRoute = $this->mock('AkRoute', array('parametrize' => true));

        $this->Router->addRoute('person',$PersonRoute);
        $this->Router->addRoute('author',$AuthorRoute);

        $this->Router->match($Request);
        $this->assertEqual($AuthorRoute, $this->Router->currentRoute);
    }


    public function testUrlizeTraversesAllRegisteredRoutesWhileFalseIsReturned() {
        $PersonRoute = $this->mock('AkRoute');
        $PersonRoute->throwOn('urlize', new RouteDoesNotMatchParametersException);
        //$PersonRoute->returnsByValue('urlize', false);

        $AuthorRoute = $this->mock('AkRoute');
        $AuthorRoute->returnsByValue('urlize', '/author/martin');

        $this->Router->addRoute('person',$PersonRoute);
        $this->Router->addRoute('author',$AuthorRoute);

        $this->assertEqual('/author/martin', $this->Router->urlize(array('name'=>'martin')));
    }

    public function testUrlizeThrowsAnExceptionIfItCantFindARoute() {
        $PersonRoute = $this->mock('AkRoute', array(
        'urlize' => new RouteDoesNotMatchParametersException));
        $this->Router->addRoute('person',$PersonRoute);

        $this->expectException('NoMatchingRouteException');
        $this->Router->urlize(array('not'=>'found'));
    }

    public function testUrlizeUsingAnNamedRoute() {
        $AuthorRoute = $this->mock('AkRoute', array('urlize' => '/author/martin'));
        $this->Router->addRoute('author',$AuthorRoute);
        $this->assertEqual('/author/martin',$this->Router->author_url(array('name'=>'martin')));
    }


    public function testUrlizeUsingAnNamedRouteThrowsIfNotApplicable() {
        $AuthorRoute = $this->mock('AkRoute', array('urlize' => new RouteDoesNotMatchParametersException));
        $this->Router->addRoute('author',$AuthorRoute);
        $this->expectException('RouteDoesNotMatchParametersException');
        $this->Router->author_url(array('name'=>'martin'));
    }

    public function testRequirementsShouldntHaveRegexDelimiters() {
        $Router = $this->partialMock('AkRouter', array('addRoute'));
        $Router->expectOnce('addRoute', array(null,new AkRoute('/author/:name',array(),array('name'=>'[a-z]+'))));
        $Router->automatic_lang_segment = false;
        $Router->connect('/author/:name',array(),array('name'=>'/[a-z]+/'));

    }

    public function testDefaultsShouldntBeUsedForRequirements() {
        $Router = $this->partialMock('AkRouter', array('addRoute'));
        $Router->expectOnce('addRoute', array(null,new AkRoute('/author/:name',array(),array('name'=>'[a-z]+'))));
        $Router->automatic_lang_segment = false;
        $Router->connect('/author/:name',array('name'=>'/[a-z]+/'));
    }

    public function testSegmentsShouldntBeDeclaredOptional() {
        $Router = $this->partialMock('AkRouter', array('addRoute'));
        $Router->expectOnce('addRoute', array(null,new AkRoute('/author/:name',array())));
        $Router->automatic_lang_segment = false;
        $Router->connect('/author/:name',array('name'=>OPTIONAL));
    }

    public function testDefaultsShouldntBeUsedForRequirementsAsAnExplicitOption() {
        
        $Router = $this->partialMock('AkRouter', array('addRoute'));
        $Router->expectOnce('addRoute', array(null,new AkRoute('/author/:name',array(),array('name'=>'[a-z]+'))));

        $Router->automatic_lang_segment = false;
        $Router->connect('/author/:name',array('requirements'=>array('name'=>'/[a-z]+/')));
    }

}

ak_test_case('Router_TestCase');
