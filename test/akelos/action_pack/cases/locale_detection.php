<?php

require_once(dirname(__FILE__).'/../config.php');

class LocaleDetection_TestCase extends AkWebTestCase
{
    public function __construct() {
        $this->webserver_enabled = AkConfig::getOption('webserver_enabled', false);
        parent::__construct();
        $this->_test_script = AkConfig::getOption('testing_url').
        '/action_pack/public/index.php?ak=';
    }
    
    public function skip(){
        $this->skipIf(!$this->webserver_enabled, '['.get_class($this).'] Web server not enabled');
    }

    public function test_request_LocaleDetectionController() {
        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'locale_detection');
        $this->assertResponse(200);
        $this->assertTextMatch('Hello from LocaleDetectionController');
    }

    public function test_Language_header_detection() {
        $this->addHeader('Accept-Language: es,en-us,en;q=0.5');
        $this->get($this->_test_script.'locale_detection/check_header');
        $this->assertTextMatch('es,en-us,en;q=0.5');
    }

    public function test_detect_default_language() {
        $this->addHeader('Accept-Language: es,en-us,en;q=0.5');
        $this->get($this->_test_script.'locale_detection/get_language');
        $this->assertTextMatch('es');
    }

    public function test_sessions_should_work() {
        $this->get($this->_test_script.'locale_detection/session/1234');
        $this->assertTextMatch('1234');

        $this->get($this->_test_script.'locale_detection/session/');
        $this->assertTextMatch('1234');
    }

    public function test_session_are_fresh_on_new_request() {
        $this->get($this->_test_script.'locale_detection/session/');
        $this->assertNoText('1234');
    }

    public function test_language_change() {
        $this->assertEqual( array('en','es'), Ak::langs() );

        $this->addHeader('Accept-Language: es,en-us,en;q=0.5');

        $this->get($this->_test_script.'locale_detection/get_language');
        $this->assertTextMatch('es');

        $this->get($this->_test_script.'locale_detection/get_param&param=message&message=Hello');
        $this->assertTextMatch('Hello');

        $this->get($this->_test_script.'locale_detection/get_param&param=lang&lang=en');
        $this->assertTextMatch('en');

        $this->get($this->_test_script.'locale_detection/get_language&lang=en');
        $this->assertTextMatch('en');

        $this->get($this->_test_script.'locale_detection/get_language');
        $this->assertTextMatch('en');

        $this->get($this->_test_script.'locale_detection/get_language&lang=invalid');
        $this->assertTextMatch('en');

    }

    public function test_language_change_on_ak() {
        $this->assertEqual( array('en','es'), Ak::langs() );

        $this->addHeader('Accept-Language: es,en-us,en;q=0.5');

        $this->get($this->_test_script.'locale_detection/get_language');
        $this->assertTextMatch('es');

        $this->get($this->_test_script.'en/locale_detection/get_language/');
        $this->assertTextMatch('en');

        $this->get($this->_test_script.'locale_detection/get_language');
        $this->assertTextMatch('en');
    }
}

ak_test_case('LocaleDetection_TestCase');

