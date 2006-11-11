<?php

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');
require_once(AK_LIB_DIR.DS.'Ak.php');

class test_Ak_support_functions extends  AkUnitTest 
{
    function test_for_importing_models()
    {
        $models = 'ImportTestModelA, import_test_model_b';
        
        $this->assertFalse(class_exists('ImportTestModelA'));
        $this->assertFalse(class_exists('ImportTestModelB'));
        
        $this->assertEqual(Ak::import($models), array('ImportTestModelA','ImportTestModelB'));

        $this->assertTrue(class_exists('ImportTestModelA'));
        $this->assertTrue(class_exists('ImportTestModelB'));
        
        $models = array('ImportTestModelB','Import Test Model C');
        $this->assertEqual(Ak::import($models), array('ImportTestModelB','ImportTestModelC'));
        
        $this->assertTrue(class_exists('ImportTestModelC'));
    }

}


Ak::test('test_Ak_support_functions');

?>
