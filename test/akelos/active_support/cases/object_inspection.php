<?php

require_once(dirname(__FILE__).'/../config.php');

//This class is used to check class inspection functions
class AkTestingObjectInspectionParent
{
    public $parent_var;
    public $parent_var_null = null;
    public $parent_var_string = 'abc';
    public $parent_var_int = 123;

    public function AkTestingObjectInspectionParent(){
    }
    public function parent_function(){
    }
    public function &parent_method(){
    }
}

class AkTestingObjectInspectionChild extends AkTestingObjectInspectionParent
{
    public $child_var;
    public $child_var_null = null;
    public $child_var_string = 'abc';
    public $child_var_int = 123;

    public function AkTestingObjectInspectionChild(){
        parent::AkTestingObjectInspectionParent();
    }
    public function child_function(){
    }
    public function &child_method(){
    }
}



class ObjectInspection_TestCase extends ActiveSupportUnitTest
{

    public $AkTestingObjectInspectionChildInstance;

    public function setUp() {
        $this->AkTestingObjectInspectionChildInstance = new AkTestingObjectInspectionChild();
    }

    public function tearDown() {
        unset($this->AkTestingObjectInspectionChildInstance);
    }


    public function Test_db() {
        include_once AK_CONTRIB_DIR.'/adodb/adodb.inc.php';

        $db = Ak::db();
        $this->assertFalse(!$db,'Connecting to the database. Please check your test_config.php file in order to set up a copy of $dns into $GLOBALS["ak_test_db_dns"]');
        $this->assertReference($db,Ak::db(),'Checking db connection singleton');
    }

    public function Test_t() {
        $text_to_translate = 'Hello, %name, today is %weekday';
        $vars_to_replace = array('%name'=>'Bermi','%weekday'=>'monday');

        $this->assertEqual(Ak::t($text_to_translate),'Hello, %name, today is %weekday','String with tokens but no replacement array given.');
        $this->assertEqual(Ak::t($text_to_translate),'Hello, %name, today is %weekday','String with tokens but no replacement array given.');
        $this->assertEqual(Ak::t($text_to_translate,$vars_to_replace),'Hello, Bermi, today is monday');

    }

    public function Test_debug() {
        ob_start();
        Ak::debug($this->AkTestingObjectInspectionChildInstance);
        $debug_str = ob_get_contents();
        ob_end_clean();
        $this->assertFalse($debug_str == '','Ak::debug not working properly');
    }

    public function Test_get_object_info() {
        $this->assertNotEqual(md5(serialize(Ak::get_object_info($this->AkTestingObjectInspectionChildInstance))),
        md5(serialize(Ak::get_object_info($this->AkTestingObjectInspectionChildInstance,true))),'Object inspection does not exclude parent class methods');

    }

    public function Test_get_this_object_methods() {
        $expected_methods = array('AkTestingObjectInspectionChild','child_function','child_method');
        $resulting_methods = Ak::get_this_object_methods($this->AkTestingObjectInspectionChildInstance);
        $this->assertEqual($expected_methods,$resulting_methods);
    }

    public function Test_get_this_object_attributes() {
        $expected_attributes = array('child_var'=>null,'child_var_null'=>null,'child_var_string'=>'abc','child_var_int'=>123);
        $resulting_attributes = Ak::get_this_object_attributes($this->AkTestingObjectInspectionChildInstance);
        $this->assertEqual($expected_attributes,$resulting_attributes);
    }


    public function Test_for_StatusKeys() {
        $Object = new Ak();

        $this->assertFalse(Ak::objectHasBeenModified($Object));

        $this->assertEqual(Ak::getStatusKey($Object), Ak::getStatusKey($Object));

        $Object->name = 'Bermi';
        $this->assertTrue(Ak::objectHasBeenModified($Object));
        $this->assertTrue(Ak::objectHasBeenModified($Object));

        Ak::resetObjectModificationsWacther($Object);

        $this->assertFalse(Ak::objectHasBeenModified($Object));
    }

}


ak_test_case('ObjectInspection_TestCase');

