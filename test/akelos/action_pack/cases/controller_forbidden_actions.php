<?php

require_once(dirname(__FILE__).'/../config.php');

class Controller_forbidden_actions_TestCase extends AkWebTestCase
{
    public $webserver_enabled;

    public function __construct()
    {
        if(!$this->webserver_enabled = AkConfig::getOption('webserver_enabled', false)){
            echo "Skipping Controller_forbidden_actions_TestCase: Web server not accesible at ".AkConfig::getOption('testing_url')."\n";
        }
        parent::__construct();
        $this->_test_script = AkConfig::getOption('testing_url').
        '/action_pack/public/index.php?ak=';
    }

    public function test_should_ignore_underscored_methods()
    {
        if(!$this->webserver_enabled) return;

        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'intranet/_forbidden');

        $this->assertResponse(200);
        $this->assertTextMatch('Intranet Controller Works');
    }

    public function test_should_not_allow_calling_action_controller_methods()
    {
        if(!$this->webserver_enabled) return;

        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'intranet/render');

        $this->assertResponse(405);
        $this->assertTextMatch('405 Method Not Allowed');
    }
}

ak_test_case('Controller_forbidden_actions_TestCase');
