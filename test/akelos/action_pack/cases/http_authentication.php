<?php

require_once(dirname(__FILE__).'/../config.php');

class HttpAuthentication_TestCase extends AkWebTestCase
{
    public $webserver_enabled;

    public function __construct()
    {
        if(!$this->webserver_enabled = AkConfig::getOption('webserver_enabled', false)){
            echo "Skipping HttpAuthentication_TestCase: Webserver no accesible at ".AkConfig::getOption('testing_url')."\n";
        }
        parent::__construct();
        $this->_test_script = AkConfig::getOption('testing_url').
        '/action_pack/public/index.php?ak=';
    }

    public function test_should_access_public_action()
    {
        if(!$this->webserver_enabled) return;

        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'authentication');
        $this->assertResponse(200);
        $this->assertTextMatch('Everyone can see me!');
    }

    public function test_should_show_login_with_realm()
    {
        if(!$this->webserver_enabled) return;

        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'authentication/edit');
        $this->assertRealm('App name');
        $this->assertNoText("I'm only accessible if you know the password");
    }

    public function test_should_fail_login()
    {
        if(!$this->webserver_enabled) return;

        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'authentication/edit');
        $this->authenticate('bermi', 'badpass');
        $this->assertResponse(401);
        $this->assertNoText("I'm only accessible if you know the password");
    }

    public function test_should_login()
    {
        if(!$this->webserver_enabled) return;

        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'authentication/edit');
        $this->authenticate('bermi', 'secret');
        $this->assertResponse(200);
        $this->assertText("I'm only accessible if you know the password");

        // still logged in?
        $this->get($this->_test_script.'authentication/edit');
        $this->assertResponse(200);
        $this->assertText("I'm only accessible if you know the password");
    }
}

ak_test_case('HttpAuthentication_TestCase');

