<?php

require_once(dirname(__FILE__).'/../config.php');

class Ak_TestCase extends ActiveSupportUnitTest
{
    public function test_should_get_the_right_temp_dir() {
        $tmp_dir = Ak::get_tmp_dir_name();

        $tmp_file = $tmp_dir.DS.'ak_test_'.__CLASS__;
        $tmp_file2 = $tmp_dir.DS.'ak_test_dir'.DS.'level_one'.DS.'file.txt';
        $this->assertTrue(is_dir($tmp_dir), 'Could not find temporary directory at: '.$tmp_dir);
        $this->assertTrue(touch($tmp_dir.DS.'ak_test_'.__CLASS__), 'Can\'t touch files on the temporary directory '.$tmp_dir);
        $this->assertTrue(AkFileSystem::file_put_contents($tmp_file, 'abc'), 'Can\'t write on the temporary file '.$tmp_file);
        $this->assertTrue(AkFileSystem::file_get_contents($tmp_file) == 'abc', 'Can\'t write on the temporary file '.$tmp_file);
        $this->assertTrue(AkFileSystem::file_put_contents($tmp_file2, 'abce'), 'Can\'t write on the temporary file '.$tmp_file2);
        $this->assertTrue(AkFileSystem::file_get_contents($tmp_file2) == 'abce', 'Can\'t write on the temporary file '.$tmp_file2);
        $this->assertEqual($tmp_dir, AK_TMP_DIR);
    }

    public function test_static_var_set_value_null() {
        $null = null;
        $return = Ak::setStaticVar('testVar1',$null);
        $this->assertEqual(null,$return);
    }

    public function test_static_var_set_value_true() {
        $true = true;
        $return = Ak::setStaticVar('testVar1',$true);
        $this->assertEqual(true,$return);
        $this->assertEqual(true,Ak::getStaticVar('testVar1'));
    }

    public function test_static_var_set_value_false() {
        $false = false;
        $return = Ak::setStaticVar('testVar1',$false);
        $this->assertEqual(true,$return);
        $this->assertEqual(false,Ak::getStaticVar('testVar1'));
    }

    public function test_static_var_set_value_array() {
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
    public function test_static_var_set_value_float() {
        $value = 13.59;
        $return = Ak::setStaticVar('testVar1',$value);
        $this->assertEqual(true,$return);
        $this->assertEqual($value,Ak::getStaticVar('testVar1'));
    }

    public function test_static_var_set_value_object_referenced() {
        $value = new stdClass;
        $value->id = 1;
        $return = Ak::setStaticVar('testVar1',$value);
        $this->assertEqual(true,$return);
        $storedValue = &Ak::getStaticVar('testVar1');
        $this->assertEqual($value,$storedValue);
        $value->id = 2;
        $this->assertEqual($value->id, $storedValue->id);
    }

    public function test_static_var_destruct_single_var() {
        $value = new stdClass;

        $value->id = 1;
        $return = Ak::setStaticVar('testVar1',$value);
        $this->assertEqual(true, $return);

        $storedValue = Ak::getStaticVar('testVar1');
        $this->assertEqual($value, $storedValue);

        $null = null;
        Ak::unsetStaticVar('testVar1');

        $storedValue = Ak::getStaticVar('testVar1');
        $this->assertEqual($null, $storedValue);

    }

    public function test_static_var_destruct_all_vars() {
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

ak_test_case('Ak_TestCase');
