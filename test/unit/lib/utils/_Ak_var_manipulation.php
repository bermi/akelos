<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class Ak_var_manipulation_TestCase extends AkUnitTest
{
    public function test_for_to_array()
    {
        $this->assertEqual(Ak::toArray('es,en,va'),array('es','en','va'));
    }

    public function test_for_string_to_array()
    {
        $this->assertEqual(Ak::stringToArray('es,en,va'), array('es','en','va'));
        $this->assertEqual(Ak::stringToArray('es , en , va'), array('es','en','va'));
    }

}

ak_test_case('Ak_var_manipulation_TestCase');

