<?php

require_once(dirname(__FILE__).'/../config.php');

class DatabaseSessions_TestCase extends AkWebTestCase
{
    public $sessionLife = NULL;
    public $webserver_enabled;

    public function __construct() {
        $this->webserver_enabled = AkConfig::getOption('webserver_enabled', false);
        parent::__construct();
    }

    public function skip()
    {
        $this->skipIf(!$this->webserver_enabled, '['.get_class($this).'] Web server not enabled');
    }

    public function setUp() {
        $this->_test_script = AkConfig::getOption('testing_url').
        '/action_pack/public/database_sessions.php';
    }


    public function test_open() {
        $browser = $this->getBrowser();
        $this->get("$this->_test_script?open_check=1");
        $expected_session_id = $browser->getContentAsText();
        $this->get("$this->_test_script?open_check=1");
        //$browser->getContentAsText();
        $this->assertText($expected_session_id,'Sessions are not working correctly');
    }

    public function test_read_write() {
        $expected = 'test_value';
        $this->get("$this->_test_script?key=test_key&value=$expected");
        $this->get("$this->_test_script?key=test_key");
        $this->assertText($expected,'Session is not storing values on database correctly when calling '.
        $this->_test_script.'?key=test_key');
    }

    public function test_destroy() {
        $expected = 'value not found';
        $this->get("$this->_test_script?key=test_key&value=test_value");
        $this->get("$this->_test_script?destroy_check=1");
        $this->get("$this->_test_script?key=test_key");
        $this->assertText($expected,'session_destroy(); is not working as expected');
    }

    public function test_gc() {
        $expected = 'value not found';
        $copy = $this;
        $copy->get("$this->_test_script?key=test_key&value=test_value&expire=1");
        sleep(3);
        $this->restart();
        $this->get("$this->_test_script?dumb_call_for_activating_gc");

        $copy->get("$this->_test_script?key=test_key");
        $this->assertText($expected,'Session garbage collection is not working correctly');
    }
}

ak_test_case('DatabaseSessions_TestCase');
