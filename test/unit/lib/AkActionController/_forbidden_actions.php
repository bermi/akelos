<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class _AkActionController_forbidden_actions extends AkWebTestCase
{
    function test_should_ignore_underscored_methods()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/intranet/_forbidden');
        
        $this->assertResponse(200);
        $this->assertTextMatch('Intranet Controller Works');
    }
    
    function test_should_not_allow_calling_action_controller_methods()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/intranet/render');
        
        $this->assertResponse(405);
        $this->assertTextMatch('405 Method Not Allowed');
    }
}

ak_test('_AkActionController_forbidden_actions');

?>