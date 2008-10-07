<?php
require_once(AK_LIB_DIR.DS.'Ak.php');

class Test_Ak extends AkUnitTest
{
    function test_static_var_set_value_null()
    {
        $null = null;
        $return = Ak::setStaticVar('testVar1',$null);
        $this->assertEqual(null,$return);
    }
    
    function test_static_var_set_value_true()
    {
        $true = true;
        $return = Ak::setStaticVar('testVar1',$true);
        $this->assertEqual(true,$return);
        $this->assertEqual(true,Ak::getStaticVar('testVar1'));
    }
    
    function test_static_var_set_value_false()
    {
        $false = false;
        $return = Ak::setStaticVar('testVar1',$false);
        $this->assertEqual(true,$return);
        $this->assertEqual(false,Ak::getStaticVar('testVar1'));
    }
    
    function test_static_var_set_value_array()
    {
        $value = array(1);
        $return = Ak::setStaticVar('testVar1',$value);
        $this->assertEqual(true,$return);
        $this->assertEqual($value,Ak::getStaticVar('testVar1'));
        
        $obj1 = new stdClass;
        $obj1->id = 1;
        $value = array(&$obj1);
        $return = Ak::setStaticVar('testObjectArray',$value);
        $this->assertEqual(true,$return);
        $this->assertEqual($value,Ak::getStaticVar('testObjectArray'));
        $retrievedObject = &$value[0];
        $this->assertEqual($retrievedObject->id, $obj1->id);
        $obj1->id=2;
        $this->assertEqual($retrievedObject->id, $obj1->id);
        $retrievedObject->id=3;
        $this->assertEqual($retrievedObject->id, $obj1->id);
        
    }
    function test_static_var_set_value_float()
    {
        $value = 13.59;
        $return = Ak::setStaticVar('testVar1',$value);
        $this->assertEqual(true,$return);
        $this->assertEqual($value,Ak::getStaticVar('testVar1'));
    }

    function test_static_var_set_value_object_referenced()
    {
        $value = new stdClass;
        $value->id = 1;
        $return = Ak::setStaticVar('testVar1',$value);
        $this->assertEqual(true,$return);
        $storedValue = &Ak::getStaticVar('testVar1');
        $this->assertEqual($value,$storedValue);
        $value->id = 2;
        $this->assertEqual($value->id, $storedValue->id);
    }
    
    function test_static_var_destruct_single_var()
    {
        $value = new stdClass;
        $value->id = 1;
        $return = Ak::setStaticVar('testVar1',$value);
        $this->assertEqual(true,$return);
        $storedValue = &Ak::getStaticVar('testVar1');
        $this->assertEqual($value,$storedValue);
        $null = null;
        Ak::unsetStaticVar('testVar1');
        $storedValue = &Ak::getStaticVar('testVar1');
        $this->assertEqual($null, $storedValue);
        
    }
    
    function test_static_var_destruct_all_vars()
    {
        $value = new stdClass;
        $value->id = 1;
        $return = Ak::setStaticVar('testVar1',$value);
        $this->assertEqual(true,$return);
        
        $value2 = new stdClass;
        $value2->id = 2;
        $return = Ak::setStaticVar('testVar2',$value2);
        $this->assertEqual(true,$return);
        
        $value3 = new stdClass;
        $value3->id = 3;
        $return = Ak::setStaticVar('testVar3',$value3);
        $this->assertEqual(true,$return);
        
        $null = null;
        Ak::unsetStaticVar('testVar1');
        $storedValue1 = &Ak::getStaticVar('testVar1');
        $this->assertEqual($null, $storedValue1);
        
        $storedValue2 = &Ak::getStaticVar('testVar2');
        $this->assertEqual($value2, $storedValue2);
        
        $storedValue3 = &Ak::getStaticVar('testVar3');
        $this->assertEqual($value3, $storedValue3);
        
        Ak::unsetStaticVar($null);
        $storedValue1 = &Ak::getStaticVar('testVar1');
        $this->assertEqual($null, $storedValue1);
        $storedValue2 = &Ak::getStaticVar('testVar2');
        $this->assertEqual($null, $storedValue2);
        $storedValue3 = &Ak::getStaticVar('testVar3');
        $this->assertEqual($null, $storedValue3);
    }
    
}
ak_test('Test_Ak');