<?php

require_once(dirname(__FILE__).'/../config.php');

class RenderPartial_TestCase extends AkWebTestCase
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

    public function test_check_if_tests_can_be_accesed() {
        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'render_tests');

        $this->assertResponse(200);
        $this->assertTextMatch('RenderTestsController is available on tests');
    }

    public function test_render_partial() {
        $this->get($this->_test_script.'render_tests/hello_partial');
        $this->assertTextMatch('Hello World From Partial');
    }

    public function test_render_partial_inside_template() {
        $this->get($this->_test_script.'advertiser/partial_in_template');
        $this->assertTextMatch('Big CorpBig Corp');
    }

    public function test_render_partial_with_options() {
        $this->get($this->_test_script.'render_tests/hello_partial_with_options');
        $this->assertTextMatch('Hello Cruel World From Partial');
    }

    public function test_render_partial_doc_example() {
        $this->get($this->_test_script.'advertiser/buy');
        $this->assertTextMatch('Bermi LabsFirst adSeccond ad');
    }

    public function test_render_partial_collection_doc_example() {
        $this->get($this->_test_script.'advertiser/all');
        $this->assertTextMatch('1First ad2Seccond ad');
    }

    public function test_render_partial_collection_from_controller() {
        $this->get($this->_test_script.'advertiser/show_all');
        $this->assertTextMatch('1First ad2Seccond ad');
    }

    public function test_render_partial_from_different_controller() {
        $this->get($this->_test_script.'render_tests/shared_partial');
        $this->assertTextMatch('First ad');
    }

    public function test_render_partial_from_different_template() {
        $this->get($this->_test_script.'render_tests/ad');
        $this->assertTextMatch('First ad');
    }
    
    public function test_render_partial_empty_collection() {
        $this->get($this->_test_script.'advertiser/empty_collection');
        $this->assertTextMatch(' ');
    }
    
    public function test_should_use_object_and_not_controllers_item() {
        $this->get($this->_test_script.'advertiser/use_object_and_not_controllers_item');
        $this->assertTextMatch('Render');
    }

}

ak_test_case('RenderPartial_TestCase');
