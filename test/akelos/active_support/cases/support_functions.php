<?php

require_once(dirname(__FILE__).'/../config.php');

class SupportFunctions_TestCase extends ActiveSupportUnitTest
{
    public function test_for_importing_models() {
        $models = 'ImportTestModelA, import_test_model_b';

        $this->assertEqual(Ak::import($models), array('ImportTestModelA','ImportTestModelB'));

        $this->assertTrue(class_exists('ImportTestModelA'));
        $this->assertTrue(class_exists('ImportTestModelB'));

        $models = array('ImportTestModelB','Import Test Model C');
        $this->assertEqual(Ak::import($models), array('ImportTestModelB','ImportTestModelC'));

        $this->assertTrue(class_exists('ImportTestModelC'));
    }

    public function Test_for_element_size() {
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

    public function test_should_convert_between_timestamp_and_date() {
        $iso_date = '2007-10-15 16:30:00';
        $this->assertEqual(Ak::getDate(Ak::getTimestamp($iso_date)), $iso_date);
        $this->assertEqual(Ak::getDate(Ak::getTimestamp('2007-10-15 16:30')), $iso_date);
    }

    public function test_should_pick_parameters() {
        $params = array('id'=>3, 'is_enabled'=>1, 'name'=>'Alicia');
        $this->assertEqual(Ak::pick('id,name',$params), array('id'=>3, 'name'=>'Alicia'));
    }


    public function Test_for_getTimestamp() {
        $this->assertEqual(Ak::getTimestamp(), Ak::time());
        $this->assertEqual('17:52:03', Ak::getDate(Ak::getTimestamp('17:52:03'),'H:i:s'));
        $this->assertEqual(date('Y-m-d').' 17:52:03', Ak::getDate(Ak::getTimestamp('17:52:03')));
        $this->assertEqual('2005-12-25 00:00:00', Ak::getDate(Ak::getTimestamp('2005-12-25')));
        $this->assertEqual('1592-10-09 00:00:00', Ak::getDate(Ak::getTimestamp('1592-10-09')));
        $this->assertEqual('2192-10-09 00:00:00', Ak::getDate(Ak::getTimestamp('2192-10-09')));
        $this->assertEqual('2192-10-09 01:02:03', Ak::getDate(Ak::getTimestamp('2192-10-9 01:02:03')));
    }


    public function Test_of_encrypt_decrypt() {
        $original = "Este es el texto que quiero encriptar";
        $this->assertEqual(Ak::decrypt(Ak::encrypt($original)), $original);

        $key = Ak::randomString(20);
        $file = file_get_contents(__FILE__);
        $ecripted = Ak::encrypt($file, $key);
        $this->assertEqual(Ak::decrypt($ecripted,$key), $file);

    }


    public function Test_of_compress_decompress() {
        $original = file_get_contents(__FILE__);
        $compressed = Ak::compress($original);

        file_put_contents(AK_TMP_DIR.DS.'gzip_test.gz', $compressed);
        $this->assertTrue(strlen($compressed) < strlen($original));

        $compressed_file = file_get_contents(AK_TMP_DIR.DS.'gzip_test.gz');
        $this->assertEqual($compressed_file, $compressed);
        $uncompressed_from_file = Ak::uncompress($compressed_file);
        $uncompressed_from_string = Ak::uncompress($compressed);
        $this->assertEqual($uncompressed_from_file, $uncompressed_from_string);

    }

}

ak_test_case('SupportFunctions_TestCase');

