<?php

require_once(dirname(__FILE__).'/../router.php');

class RoutingAssertions_TestCase extends AkControllerUnitTest
{

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

    public function useRestfulRoute(){
        $Router = new AkRouter();
        $Router->resources('items');
        $this->nextAssertionUsingRouter($Router);

    }

    public function useItemRoute(){
        $Router = new AkRouter();
        $Router->connect('/:controller/:action/:id', array('controller' => 'items', 'action' => 'index'));
        $Router->connect('/:controller/:action/:id.:format', array('controller' => 'items', 'action' => 'index'));
        $Router->connect('/', array('controller' => 'items', 'action' => 'index'));
        $this->nextAssertionUsingRouter($Router);
    }

    // Testing doc examples at active_support/testing/assertions/routing_assertions.php

    // assertRecognizes

    public function test_should_assert_default_route(){
        $this->assertRecognizes(
        array('controller' => 'items', 'action'=>'index'),
        'items'
        );
    }

    public function test_should_recognize_specific_action(){
        $this->assertRecognizes(
        array('controller' => 'items', 'action'=>'list'),
        'items/list'
        );
    }

    public function test_should_recognize_specific_action_when_posting(){
        $this->useRestfulRoute();
        $this->assertRecognizes(
        array('controller'=>'items', 'action'=>'create'),
        array('path'=>'items', 'method'=>'post')
        );
    }

    public function test_should_recognize_with_parameter(){
        $this->useItemRoute();
        $this->assertRecognizes(
        array(
        'controller' => 'items', 'action'=>'destroy',
        'id' => 1),
        'items/destroy/1'
        );

        $this->assertRecognizes(
        array(
        'controller'=>'items', 'action'=>'list',
        'id' => 1, 'view'=>'print'),
        'items/list/1',
        array('view'=>'print')
        );
    }

    // assertGenerates

    public function test_route_without_action_should_generate_default_action(){
        $this->useRestfulRoute();
        $this->assertGenerates(
        '/items',
        array('controller'=>'items', 'action'=>'index')
        );
    }

    public function test_should_route_list_action(){
        $this->useItemRoute();
        $this->assertGenerates(
        '/items/list',
        array('controller'=>'items', 'action'=>'list')
        );
    }

    public function test_should_generate_with_param(){
        $this->assertGenerates(
        '/items/list/1',
        array('controller'=>'items', 'action'=>'list', 'id'=>'1')
        );
    }

    public function test_should_generate_custom_route(){
        $Router = new AkRouter();
        $Router->connect('/changesets/:revision', array('controller' => 'scm', 'action' => 'show_diff' ));
        $this->nextAssertionUsingRouter($Router);
        $this->assertGenerates(
        'changesets/12',
        array(
        'controller'=>'scm', 'action'=>'show_diff',
        'revision' => 12)
        );
    }


    // assertRouting

    public function test_should_assert_default_action_routing(){
        $this->useItemRoute();
        $this->assertRouting(
        '/home',
        array('controller'=>'home', 'action'=>'index')
        );
    }

    public function test_should_assert_specific_controller_and_parameter_routing(){
        $this->useItemRoute();
        $this->assertRouting(
        '/entries/show/23',
        array('controller'=>'entries', 'action'=>'show', 'id'=>23)
        );
    }

    public function test_should_assert_cusstom_message_when_routing(){
        $this->assertRouting(
        '/store',
        array('controller'=>'store', 'action'=>'index'),
        array(),
        array(),
        'Route for store index not generated properly'
        );
    }


    public function test_should_assert_routing_with_defaults(){
        $this->assertRouting(
        'controller/action/9',
        array('id'=>'9', 'item'=>'square'),
        array('controller'=>'controller', 'action'=>'action'),
        array('item'=>'square')
        );
    }

    public function test_should_assert_routing_with_http_method(){
        $this->useRestfulRoute();
        $this->assertRouting(
        array('method'=>'put', 'path'=>'/items/321'),
        array('controller'=>'items', 'action'=>'update', 'id'=>321)
        );
    }
    public function test_should_assert_generates(){
        $Router = new AkRouter();
        $Router->connect('/about', array('controller' => 'pages', 'action' => 'about'));
        $Router->connect('/:controller/:id', array('controller' => 'items', 'action' => 'show'));
        $this->nextAssertionUsingRouter($Router);

        $this->assertGenerates("/photos/1", array('controller' => 'photos', 'action' => 'show', 'id' => 1));
        $this->assertGenerates("/about", array('controller' => 'pages', 'action' => 'about'));
    }

    public function test_should_assert_recognizes(){
        $Router = new AkRouter();
        $Router->connect('/about', array('controller' => 'pages', 'action' => 'about'));
        $Router->resources('photos');
        $this->nextAssertionUsingRouter($Router);

        $this->assertRecognizes(array('controller' => 'photos', 'action' => 'show', 'id' => 1), "/photos/1");
        $this->assertRecognizes(array('controller' => 'pages', 'action' => 'about'), "/about");
    }


    public function test_should_assert_routing(){
        $this->useRestfulRoute();
        $this->assertRouting(array('path' => 'items', 'method' => 'post' ), array('controller' => 'items', 'action' => 'create'));
    }
}

ak_test_case('RoutingAssertions_TestCase');
