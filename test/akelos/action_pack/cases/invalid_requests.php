<?php

require_once(dirname(__FILE__).'/../config.php');

class InvalidRequestsIntegration_TestCase extends AkWebTestCase
{
    public function test_should_show_public_dot_404_dot_php()
    {
        if(!AkConfig::getOption('webserver_enabled', false)) return;
        $this->setMaximumRedirects(0);
        $this->get(AkConfig::getOption('testing_url').'/action_pack/public/index.php?ak=invalid');
        $this->assertResponse(404);
        $this->assertPattern("/The page you were looking for/");
    }
}

ak_test_case('InvalidRequestsIntegration_TestCase');

