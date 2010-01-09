<?php

require_once(dirname(__FILE__).'/../router.php');
require_once(dirname(__FILE__).'/../../lib/ideal_world.php');

class IdealWorld_TestCase extends IdealWorldUnitTest
{
    public $Routes = array(
        'clutter_namespace'=>array('/nothing/:here')
    );
    
    public function testGenerateHelperFunctions() {
        $name = 'namespaced_name';
        $Route = new AkRoute('/author/:name');

        AkRouterHelper::generateHelperFunctionsFor($name,$Route);

        $this->assertTrue(function_exists('namespaced_name_url'));
        $this->assertTrue(function_exists('namespaced_name_path'));
    }
    
    public function testEnsureSingletonsAreNull() {
        $this->assertNull(AkRouter::$singleton);
        $this->assertNull(AkRequest::$singleton);
    }

    public function testEnsureHelperFunctionsAreAvailable() {
        $this->createRouter();
        $this->assertTrue(function_exists('clutter_namespace_url'));
        $this->assertTrue(function_exists('clutter_namespace_path'));
    }
}

ak_test_case('IdealWorld_TestCase');