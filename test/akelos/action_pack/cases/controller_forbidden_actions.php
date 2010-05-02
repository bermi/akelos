<?php

require_once(dirname(__FILE__).'/../config.php');

class Controller_forbidden_actions_TestCase extends AkWebTestCase
{
    public $webserver_enabled;

    public function __construct() {
        $this->webserver_enabled = AkConfig::getOption('webserver_enabled', false);
        parent::__construct();
        $this->_test_script = AkConfig::getOption('testing_url').
        '/action_pack/public/index.php?ak=';
    }

    public function skip(){
        $this->skipIf(!$this->webserver_enabled, '['.get_class($this).'] Web server not enabled');
    }

    public function test_should_ignore_underscored_methods() {
        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'intranet/_forbidden');
        $this->assertText('No action was specified');
    }

    public function test_should_not_allow_calling_action_controller_methods() {
        $this->setMaximumRedirects(0);
        $this->get($this->_test_script.'intranet/render');
        $this->assertResponse(404);
        $this->assertText('Forbidden action render called');
    }
}

ak_test_case('Controller_forbidden_actions_TestCase');
