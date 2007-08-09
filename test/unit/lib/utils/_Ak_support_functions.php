<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');
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

    function Test_for_element_size()
    {
        $element = 'check_this_size';
        $expected_value = 15;
        $this->assertEqual(Ak::size($element), $expected_value);
        
        $element = '123';
        $expected_value = 3;
        $this->assertEqual(Ak::size($element), $expected_value);
        
        $element = 123;
        $expected_value = 123;
        $this->assertEqual(Ak::size($element), $expected_value);
        
        $element = array(0=>'A', 1=>'B', 2=>'C', 3=>'D', 4=>array('E', 'F'));
        $expected_value = 5;
        $this->assertEqual(Ak::size($element), $expected_value);
    }

}


ak_test('test_Ak_support_functions');

?>
