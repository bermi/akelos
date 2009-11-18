<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkActionController_responds_to_format_TestCase extends AkTestApplication
{

    public function setUp()
    {
        $this->rebaseAppPaths();
        $this->installAndIncludeModels('Person');
    }

    public function test_html_format()
    {
        $this->get('http://www.example.com/people/listing');
        $this->assertHeader('Content-Type','text/html');
    }

    public function test_xml_format_with_accept_header()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/xml';
        $this->get('http://www.example.com/people/listing');
        $this->assertHeader('Content-Type','application/xml');

    }
    public function test_xml_format()
    {
        $this->get('http://www.example.com/people/listing.xml');
        $this->assertHeader('Content-Type','application/xml');

    }

}

ak_test_case('AkActionController_responds_to_format_TestCase');