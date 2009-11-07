<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');
require_once(AK_LIB_DIR.DS.'Ak.php');

class test_Ak_var_manipulation extends  UnitTestCase
{
    public function test_for_to_array()
    {
        $this->assertEqual(Ak::toArray('es,en,va'),array('es','en','va'));
    }    
    
    public function test_for_string_to_array()
    {
        $this->assertEqual(Ak::stringToArray('es,en,va'),array('es','en','va'));
        $this->assertEqual(Ak::stringToArray('es , en , va'),array('es','en','va'));
    }

}


ak_test('test_Ak_var_manipulation');

?>
