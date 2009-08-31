<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'number_helper.php');


class NumberHelperTests extends HelpersUnitTester 
{    
    public function test_for_NumberHelper()
    {
        $number = new NumberHelper();
        $this->assertEqual($number->number_to_phone(1235551234),'123-555-1234');
        $this->assertEqual($number->number_to_phone(1235551234, array('area_code' => true)),'(123) 555-1234');
        $this->assertEqual($number->number_to_phone(1235551234, array('delimiter' => ' ')),'123 555 1234');
        $this->assertEqual($number->number_to_phone(1235551234, array('area_code' => true, 'extension' => 555)),
        '(123) 555-1234 x 555');


        $this->assertEqual($number->number_to_currency("1234567890.50"),'$1,234,567,890.50');
        
        $this->assertEqual($number->number_to_currency(123456789.123456, array('precision'=>2, 'unit' => ' Skk', 
        'unit_position' => 'right', 'separator'=> ',', 'delimiter' =>  ' ')),'123 456 789,12 Skk');
        
        $this->assertEqual($number->number_to_currency("1234567890.50"),'$1,234,567,890.50');
        $this->assertEqual($number->number_to_currency(1234567890.506), '$1,234,567,890.51');
        $this->assertEqual($number->number_to_currency(1234567890.50, array('unit' => "&pound;", 'separator' => ",", 'delimiter' => "")), '&pound;1234567890,50');
        $this->assertEqual($number->number_to_currency(1234567890.50, array('unit' => " &euro;", 'separator' => ",", 'delimiter' => ".",'unit_position' => 'right')), '1.234.567.890,50 &euro;');

        $this->assertEqual($number->number_to_percentage(100), '100.00%');
        $this->assertEqual($number->number_to_percentage(100, array('precision' => 0)), '100%');
        $this->assertEqual($number->number_to_percentage(302.0576, array('precision' => 3)), '302.058%');

        $this->assertEqual($number->number_with_delimiter(12345678), '12,345,678');
        $this->assertEqual($number->number_with_delimiter(12345678.2), '12,345,678.2');

        $this->assertEqual($number->human_size(123)          , '123 Bytes');
        $this->assertEqual($number->human_size(1234)         , '1.2 KB');
        $this->assertEqual($number->human_size(12345)        , '12.1 KB');
        $this->assertEqual($number->human_size(1234567)      , '1.2 MB');
        $this->assertEqual($number->human_size(1234567890)   , '1.1 GB');
        
        $this->assertEqual($number->human_size_to_bytes('123 Bytes'), 123);
        $this->assertEqual($number->human_size_to_bytes('1.2 KB'), 1229);
        $this->assertEqual($number->human_size_to_bytes('12.1 KB'), 12391);
        $this->assertEqual($number->human_size_to_bytes('1.2 MB'), 1258292);
        $this->assertEqual($number->human_size_to_bytes('1.1 GB'), 1181116007);
        
        $this->assertEqual($number->number_with_precision(111.2345), '111.235');

        $this->assertEqual($number->zeropad(123, 6), '000123');
        $this->assertEqual($number->zeropad('0123', 6), '000123');
        $this->assertEqual($number->zeropad(12345, 2), '12345');
    }    
}


ak_test('NumberHelperTests');

?>