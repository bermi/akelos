<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class Test_of_AkActionController_partials extends  AkWebTestCase
{
    public function test_check_if_tests_can_be_accesed()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/render_tests');
        
        $this->assertResponse(200);
        $this->assertTextMatch('RenderTestsController is available on tests');
    }
    
    public function test_render_partial()
    {
        $this->get(AK_TESTING_URL.'/render_tests/hello_partial');
        $this->assertTextMatch('Hello World From Partial');
    }
    
    public function test_render_partial_inside_template()
    {
        $this->get(AK_TESTING_URL.'/advertiser/partial_in_template');
        $this->assertTextMatch('Big CorpBig Corp');
    }

    public function test_render_partial_with_options()
    {
        $this->get(AK_TESTING_URL.'/render_tests/hello_partial_with_options');
        $this->assertTextMatch('Hello Cruel World From Partial');
    }

    public function test_render_partial_doc_example()
    {
        $this->get(AK_TESTING_URL.'/advertiser/buy');
        $this->assertTextMatch('Akelos MediaFirst adSeccond ad');
    }

    public function test_render_partial_collection_doc_example()
    {
        $this->get(AK_TESTING_URL.'/advertiser/all');
        $this->assertTextMatch('1First ad2Seccond ad');
    }
    public function test_render_partial_collection_from_controller()
    {
        $this->get(AK_TESTING_URL.'/advertiser/show_all');
        $this->assertTextMatch('1First ad2Seccond ad');
    }

    public function test_render_partial_from_different_controller()
    {
        $this->get(AK_TESTING_URL.'/render_tests/shared_partial');
        $this->assertTextMatch('First ad');
    }
    
    public function test_render_partial_from_different_template()
    {
        $this->get(AK_TESTING_URL.'/render_tests/ad');
        $this->assertTextMatch('First ad');
    }
}

ak_test('Test_of_AkActionController_partials');


?>
