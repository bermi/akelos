<?php

require_once(dirname(__FILE__).'/../config.php');

class MockTestApplication extends AkTestApplication
{
    public $passes = array();
    public $failures = array();

    public function pass($message = '') {
        $this->passes[] = $message;
    }

    public function fail($message = '') {
        $this->failures[] = $message;
    }
}

class TestApplication_TestCase extends ActionPackUnitTest
{
    public function test_assert_valid_xhtml() {
        $test_app = new MockTestApplication();
        $test_app->_response = file_get_contents(AkConfig::getDir('fixtures').DS.'valid_xhtml.html');

        $test_app->assertValidXhtml();

        $this->assertTrue(empty($test_app->failures));
        $this->assertTrue(count($test_app->passes) == 1);


        $test_app = new MockTestApplication();
        $test_app->_response = file_get_contents(AkConfig::getDir('fixtures').DS.'invalid_xhtml.html');

        $test_app->assertValidXhtml();

        $this->assertTrue(count($test_app->failures) == 1);
        $this->assertTrue(count($test_app->passes) == 0);

    }

    public function test_assert_xpath() {
        $test_app = new MockTestApplication();
        $test_app->_response = file_get_contents(AkConfig::getDir('fixtures').DS.'valid_xhtml.html');

        $test_app->assertValidXhtml();

        $this->assertTrue(empty($test_app->failures));
        $this->assertTrue(count($test_app->passes) == 1);

        $test_app->assertXPath('/html');
        $test_app->assertXPath('/html/body/form');
        $test_app->assertXPath("/html/body/form[@id='test']");
        $this->assertEqual(4,count($test_app->passes));

        $test_app = new MockTestApplication();
        $test_app->_response = file_get_contents(AkConfig::getDir('fixtures').DS.'valid_xhtml.html');

        $test_app->assertValidXhtml();

        $this->assertTrue(empty($test_app->failures));
        $this->assertTrue(count($test_app->passes) == 1);

        $test_app->assertNoXPath("/html/body/form[@id='test']/input[@id='submit']");
        $this->assertEqual(2,count($test_app->passes));
    }

}

ak_test_case('TestApplication_TestCase');

