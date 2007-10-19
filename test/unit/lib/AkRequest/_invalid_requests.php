<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class AkRequestInvalidRequestsIntegrationTest extends AkWebTestCase
{
    function test_should_show_public_dot_404_dot_php()
    {
        $this->setMaximumRedirects(0);
        $this->get(AK_TESTING_URL.'/invalid');
        $this->assertResponse(404);
        $this->assertPattern("/The page you were looking for/");
    }
}

ak_test('AkRequestInvalidRequestsIntegrationTest');


?>