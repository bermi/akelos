<?php
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkUnitTest'.DS.'AkTestApplication.php');

class MockTestApplication extends AkTestApplication
{
    var $passes = array();
    var $failures = array();
    
    function pass($message)
    {
        $this->passes[] = $message;
    }
    
    function fail($message)
    {
        $this->failures[] = $message;
    }
}

class Test_of_AkTestApplication extends  AkUnitTest
{
    function test_assert_valid_xhtml()
    {
        $test_app = new MockTestApplication();
        $test_app->_response = file_get_contents(AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'valid_xhtml.html');
        
        $test_app->assertValidXhtml();

        $this->assertTrue(empty($test_app->failures));
        $this->assertTrue(count($test_app->passes) == 1);
        
        
        $test_app = new MockTestApplication();
        $test_app->_response = file_get_contents(AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'invalid_xhtml.html');
        
        $test_app->assertValidXhtml();
        
        $this->assertTrue(count($test_app->failures) == 1);
        $this->assertTrue(count($test_app->passes) == 0);

    }
    
    function x_test_assert_xpath()
    {
        $test_app = new MockTestApplication();
        $test_app->_response = file_get_contents(AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'valid_xhtml.html');
        
        $test_app->assertValidXhtml();
        
        $this->assertTrue(empty($test_app->failures));
        $this->assertTrue(count($test_app->passes) == 1);
        
        $test_app->assertXPath('/html');
        $test_app->assertXPath('/html/body/form');
        $test_app->assertXPath("/html/body/form[@id='test']");
        $this->assertEqual(4,count($test_app->passes));
        
        $test_app = new MockTestApplication();
        $test_app->_response = file_get_contents(AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'valid_xhtml.html');
        
        $test_app->assertValidXhtml();
        
        $this->assertTrue(empty($test_app->failures));
        $this->assertTrue(count($test_app->passes) == 1);
        
        $test_app->assertNoXPath("/html/body/form[@id='test']/input[@id='submit']");
        $this->assertEqual(2,count($test_app->passes));
    }

}

ak_test('Test_of_AkTestApplication', true);

?>