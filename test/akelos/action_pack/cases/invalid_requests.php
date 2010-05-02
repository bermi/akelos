<?php

require_once(dirname(__FILE__).'/../config.php');

class InvalidRequestsIntegration_TestCase extends AkWebTestCase
{
    public function skip(){
        $this->skipIf(!AkConfig::getOption('webserver_enabled', false), '['.get_class($this).'] Web server not enabled');
    }

    public function test_should_show_public_dot_404_dot_php() {
        $this->setMaximumRedirects(0);
        $this->get(AkConfig::getOption('testing_url').'/action_pack/public/index.php?ak=invalid');
        $this->assertResponse(404);
        $this->assertText("Exception in InvalidController#index");
    }
}

ak_test_case('InvalidRequestsIntegration_TestCase');

