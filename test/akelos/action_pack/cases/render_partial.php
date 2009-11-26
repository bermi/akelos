<?php

require_once(dirname(__FILE__).'/../config.php');

class RenderPartial_TestCase extends AkWebTestCase
{
    public $webserver_enabled;

    public function __construct()
    {
        if(!$this->webserver_enabled = AkConfig::getOption('webserver_enabled', false)){
            echo "Skipping RenderPartial_TestCase: Webserver no accesible at ".AkConfig::getOption('testing_url')."\n";
        }
        parent::__construct();
        $this->_test_script = AkConfig::getOption('testing_url').
        '/action_pack/public/index.php?ak=';
    }

    public function test_check_if_tests_can_be_accesed()
    {
        if(!$this->webserver_enabled) return;

        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'render_tests');

        $this->assertResponse(200);
        $this->assertTextMatch('RenderTestsController is available on tests');
    }

    public function test_render_partial()
    {
        if(!$this->webserver_enabled) return;

        $this->get($this->_test_script.'render_tests/hello_partial');
        $this->assertTextMatch('Hello World From Partial');
    }

    public function test_render_partial_inside_template()
    {
        if(!$this->webserver_enabled) return;

        $this->get($this->_test_script.'advertiser/partial_in_template');
        $this->assertTextMatch('Big CorpBig Corp');
    }

    public function test_render_partial_with_options()
    {
        if(!$this->webserver_enabled) return;

        $this->get($this->_test_script.'render_tests/hello_partial_with_options');
        $this->assertTextMatch('Hello Cruel World From Partial');
    }

    public function test_render_partial_doc_example()
    {
        if(!$this->webserver_enabled) return;

        $this->get($this->_test_script.'advertiser/buy');
        $this->assertTextMatch('Bermi LabsFirst adSeccond ad');
    }

    public function test_render_partial_collection_doc_example()
    {
        if(!$this->webserver_enabled) return;

        $this->get($this->_test_script.'advertiser/all');
        $this->assertTextMatch('1First ad2Seccond ad');
    }

    public function test_render_partial_collection_from_controller()
    {
        if(!$this->webserver_enabled) return;

        $this->get($this->_test_script.'advertiser/show_all');
        $this->assertTextMatch('1First ad2Seccond ad');
    }

    public function test_render_partial_from_different_controller()
    {
        if(!$this->webserver_enabled) return;

        $this->get($this->_test_script.'render_tests/shared_partial');
        $this->assertTextMatch('First ad');
    }

    public function test_render_partial_from_different_template()
    {
        if(!$this->webserver_enabled) return;

        $this->get($this->_test_script.'render_tests/ad');
        $this->assertTextMatch('First ad');
    }
}

ak_test_case('RenderPartial_TestCase');
