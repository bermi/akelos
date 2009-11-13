<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkContionController_forbidden_actions_TestCase extends AkWebTestCase
{
    public function test_should_ignore_underscored_methods()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/intranet/_forbidden');

        $this->assertResponse(200);
        $this->assertTextMatch('Intranet Controller Works');
    }

    public function test_should_not_allow_calling_action_controller_methods()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/intranet/render');

        $this->assertResponse(405);
        $this->assertTextMatch('405 Method Not Allowed');
    }
}

ak_test_run_case_if_executed('AkContionController_forbidden_actions_TestCase');
