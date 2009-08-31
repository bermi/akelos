<?php

require_once(AK_LIB_DIR.DS.'AkUnitTest'.DS.'AkTestApplication.php');

class Test_AkActionControllerRendersFormat extends AkWebTestCase
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

?>