<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'Ak.php');

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



class test_of_Ak_object_inspection extends  UnitTestCase
{

    public $AkTestingObjectInspectionChildInstance;

    public function setUp()
    {
        $this->AkTestingObjectInspectionChildInstance = new AkTestingObjectInspectionChild();
    }

    public function tearDown()
    {
        unset($this->AkTestingObjectInspectionChildInstance);
    }


    public function Test_db()
    {
        require_once(AK_CONTRIB_DIR.'/adodb/adodb.inc.php');

        $db =& Ak::db();
        $this->assertFalse(!$db,'Connecting to the database. Please check your test_config.php file in order to set up a copy of $dns into $GLOBALS["ak_test_db_dns"]');
        $this->assertReference($db,Ak::db(),'Checking db connection singleton');
    }

    public function Test_t()
    {
        $text_to_translate = 'Hello, %name, today is %weekday';
        $vars_to_replace = array('%name'=>'Bermi','%weekday'=>'monday');

        $this->assertEqual(Ak::t($text_to_translate),'Hello, %name, today is %weekday','String with tokens but no replacement array given.');
        $this->assertEqual(Ak::t($text_to_translate),'Hello, %name, today is %weekday','String with tokens but no replacement array given.');
        $this->assertEqual(Ak::t($text_to_translate,$vars_to_replace),'Hello, Bermi, today is monday');

    }

    public function Test_debug()
    {
        ob_start();
        Ak::debug($this->AkTestingObjectInspectionChildInstance);
        $debug_str = ob_get_contents();
        ob_end_clean();

        $this->assertFalse($debug_str == '','Ak::debug not working properly');

    }

    public function Test_get_object_info()
    {
        $this->assertNotEqual(md5(serialize(Ak::get_object_info($this->AkTestingObjectInspectionChildInstance))),
        md5(serialize(Ak::get_object_info($this->AkTestingObjectInspectionChildInstance,true))),'Object inspection does not exclude parent class methods');

    }

    public function Test_get_this_object_methods()
    {
        $expected_methods = array('AkTestingObjectInspectionChild','child_function','child_method');
        $resulting_methods = Ak::get_this_object_methods($this->AkTestingObjectInspectionChildInstance);
        $this->assertEqual($expected_methods,$resulting_methods);
    }

    public function Test_get_this_object_attributes()
    {
        $expected_attributes = array('child_var'=>null,'child_var_null'=>null,'child_var_string'=>'abc','child_var_int'=>123);
        $resulting_attributes = Ak::get_this_object_attributes($this->AkTestingObjectInspectionChildInstance);
        $this->assertEqual($expected_attributes,$resulting_attributes);
    }


    public function Test_for_getTimestamp()
    {
        $this->assertEqual(Ak::getTimestamp(), Ak::time());
        $this->assertEqual('17:52:03', Ak::getDate(Ak::getTimestamp('17:52:03'),'H:i:s'));
        $this->assertEqual(date('Y-m-d').' 17:52:03', Ak::getDate(Ak::getTimestamp('17:52:03')));
        $this->assertEqual('2005-12-25 00:00:00', Ak::getDate(Ak::getTimestamp('2005-12-25')));
        $this->assertEqual('1592-10-09 00:00:00', Ak::getDate(Ak::getTimestamp('1592-10-09')));
        $this->assertEqual('2192-10-09 00:00:00', Ak::getDate(Ak::getTimestamp('2192-10-09')));
        $this->assertEqual('2192-10-09 01:02:03', Ak::getDate(Ak::getTimestamp('2192-10-9 01:02:03')));
    }

    public function Test_for_getDate()
    {
    }


    public function Test_of_encrypt_decrypt()
    {
        $original = "Este es el texto que quiero encriptar";
        $this->assertEqual(Ak::decrypt(Ak::encrypt($original)), $original);

        $key = Ak::randomString(20);
        $file = Ak::file_get_contents(__FILE__);
        $ecripted = Ak::encrypt($file, $key);
        $this->assertEqual(Ak::decrypt($ecripted,$key), $file);

    }


    /**/
    
    public function Test_of_compress_decompress()
    {
        $original = Ak::file_get_contents(__FILE__);
        $compressed = Ak::compress($original);

        Ak::file_put_contents(AK_TMP_DIR.DS.'gzip_test.gz', $compressed, array('base_path'=>AK_TMP_DIR));
        $this->assertTrue(strlen($compressed) < strlen($original));

        $compressed_file = Ak::file_get_contents(AK_TMP_DIR.DS.'gzip_test.gz', array('base_path'=>AK_TMP_DIR));
        $this->assertEqual($compressed_file, $compressed);
        $uncompressed_from_file = Ak::uncompress($compressed_file);
        $uncompressed_from_string = Ak::uncompress($compressed);
        $this->assertEqual($uncompressed_from_file, $uncompressed_from_string);

    }
    /**/

    public function Test_for_StatusKeys()
    {
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


ak_test('test_of_Ak_object_inspection',true);

?>
