<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkActionController_renders_format_TestCase extends AkWebTestCase
{
    public function test_should_render_requested_format()
    {

        $this->get(AK_TESTING_URL.'/formats/index.xml');
        $this->assertTextMatch("index.xml.tpl");
        $this->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $this->get(AK_TESTING_URL.'/formats/index.js');
        $this->assertTextMatch("index.js.tpl");
        $this->assertHeader('Content-Type','application/x-javascript; charset=UTF-8');

        $this->get(AK_TESTING_URL.'/formats/index.php');
        $this->assertTextMatch("index.php.tpl");
        $this->assertHeader('Content-Type','application/x-httpd-php; charset=UTF-8');


        $this->get(AK_TESTING_URL.'/formats/index.html');
        $this->assertTextMatch("index.tpl");
        $this->assertHeader('Content-Type','text/html; charset=UTF-8');

        $this->get(AK_TESTING_URL.'/formats/index.xhtml');
        $this->assertTextMatch("index.tpl");
        $this->assertHeader('Content-Type','application/xhtml+xml; charset=UTF-8');
    }
}

ak_test_run_case_if_executed('AkActionController_renders_format_TestCase');