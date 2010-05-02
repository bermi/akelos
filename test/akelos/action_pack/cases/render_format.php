<?php

require_once(dirname(__FILE__).'/../config.php');

class RenderFormat_TestCase extends AkWebTestCase
{
    public $webserver_enabled;

    public function __construct() {
        $this->webserver_enabled = AkConfig::getOption('webserver_enabled', false);
        parent::__construct();
        $this->_test_script = AkConfig::getOption('testing_url').
        '/action_pack/public/index.php?ak=';
    }
    
    public function skip(){
        $this->skipIf(!$this->webserver_enabled, '['.get_class($this).'] Web server not enabled');
    }

    public function test_should_render_requested_format() {

        $this->get($this->_test_script.'formats/index.xml');
        $this->assertTextMatch("index.xml.tpl");
        $this->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $this->get($this->_test_script.'formats/index.js');
        $this->assertTextMatch("index.js.tpl");
        $this->assertHeader('Content-Type','application/x-javascript; charset=UTF-8');

        $this->get($this->_test_script.'formats/index.html');
        $this->assertTextMatch("index.tpl");
        $this->assertHeader('Content-Type','text/html; charset=UTF-8');

    }
    
    public function test_should_not_accept_unregistered_formats(){
        $this->get($this->_test_script.'formats/index.php');
        $this->assertPattern("/Routing Error/");

    }
}

ak_test_case('RenderFormat_TestCase');