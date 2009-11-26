<?php

require_once(dirname(__FILE__).'/../config.php');

class RespondsToFormat_TestCase extends AkTestApplication
{
    public function __construct()
    {
        parent::__construct();
        $this->installAndIncludeModels(array('DummyPerson' => 'id,name'));
    }

    public function __destruct()
    {
        parent::__destruct();
        $Unit = new AkUnitTest();
        $Unit->dropTables('all');
    }

    public function test_html_format()
    {
        $this->get('http://www.example.com/dummy_people/listing');
        $this->assertHeader('Content-Type','text/html');
    }

    public function test_xml_format_with_accept_header()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/xml';
        $this->get('http://www.example.com/dummy_people/listing');
        $this->assertHeader('Content-Type','application/xml');

    }
    public function test_xml_format()
    {
        $this->get('http://www.example.com/dummy_people/listing.xml');
        $this->assertHeader('Content-Type','application/xml');
    }
}

ak_test_case('RespondsToFormat_TestCase');