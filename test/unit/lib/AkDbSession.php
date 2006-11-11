<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');


require_once(AK_LIB_DIR.DS.'AkDbSession.php');

/**
* In order to test sessions we have created a help script that we will use for checking and setting session params
*/



class Test_of_AkDbSession_Class extends  WebTestCase
{
    var $sessionLife = NULL;
   
    function setUp()
    {   
        $this->_test_script = str_replace('/fixtures/public','',trim(AK_TESTING_URL,'/')).
        '/mocks/test_script_AkDbSession.php';
    }
    
    function Test_open()
    {
        $browser =& $this->getBrowser();
        $this->get("$this->_test_script?open_check=1");
        $expected_session_id = $browser->getContentAsText();
        $this->get("$this->_test_script?open_check=1");
        //$browser->getContentAsText();

        $this->assertWantedText($expected_session_id,'Sessions are not working correctly');
    }
        
    function Test_read_write()
    {
        $expected = 'test_value';
        $this->get("$this->_test_script?key=test_key&value=$expected");
        $this->get("$this->_test_script?key=test_key");        
        $this->assertWantedText($expected,'Session is not storing values on database correctly when calling '.
        $this->_test_script.'?key=test_key');
    }
        
    function Test_destroy()
    {
        $expected = 'value not found';
        $this->get("$this->_test_script?key=test_key&value=test_value");
        $this->get("$this->_test_script?destroy_check=1");
        $this->get("$this->_test_script?key=test_key");
        $this->assertWantedText($expected,'session_destroy(); is not working as expected');
    }
    
    function Test_gc()
    {
        $expected = 'value not found';
        $copy = $this;
        $copy->get("$this->_test_script?key=test_key&value=test_value&expire=1");
        sleep(3);
        $this->restart();
        $this->get("$this->_test_script?dumb_call_for_activating_gc");

        $copy->get("$this->_test_script?key=test_key");
        $this->assertWantedText($expected,'Session garbage collection is not working correctly');
    }
}

Ak::test('Test_of_AkDbSession_Class', true);

?>
