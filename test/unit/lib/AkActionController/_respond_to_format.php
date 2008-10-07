<?php
require_once(AK_LIB_DIR.DS.'AkUnitTest'.DS.'AkTestApplication.php');

class Test_AkActionControllerRespondToFormat extends AkTestApplication
{

    
    function setUp()
    {
        $this->installAndIncludeModels('Person');
    }
    
    function test_html_format()
    {
        $this->get('http://www.example.com/people/listing');
        $this->assertHeader('Content-Type','text/html');
        
    }
    function test_xml_format_with_accept_header()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/xml';
        $this->get('http://www.example.com/people/listing');
        $this->assertHeader('Content-Type','application/xml');
        
    }
    function test_xml_format()
    {
        $this->get('http://www.example.com/people/listing.xml');
        $this->assertHeader('Content-Type','application/xml');
        
    }
}