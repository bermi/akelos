<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_AkActiceRecord_datatypes extends  AkUnitTest
{
    /**
     * @var ActiveRecord
     */
    var $Hybrid;
    function test_installer_should_handle_integers()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,title,price integer'));
        $columns = $this->Hybrid->getColumnSettings();
        $this->assertEqual($columns['price']['type'],'integer');
    }

    function test_should_save_and_load_integers()
    {
        $Product =& $this->Hybrid->create(array('title'=>'Dollar','price'=>198));
        $Product =& $this->Hybrid->findFirst(array('title'=>'Dollar'));
        $this->assertEqual($Product->price,198);
    }

    function test_integers_can_be_null()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,title,price integer'));
        $Dollar =& $this->Hybrid->create(array('title'=>'not euro','price'=>null));
        $Dollar->reload();

        $this->assertNull($Dollar->price);
    }

    function test_handle_empty_string_as_null_on_integers()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,title,price integer'));
        $Dollar =& $this->Hybrid->create(array('title'=>'not euro','price'=>''));
        $Dollar->reload();

        $this->assertNull($Dollar->price,'Issue #129');
    }

    function test_integers_can_be_zero()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,title,price integer'));
        $Dollar =& $this->Hybrid->create(array('title'=>'not euro','price'=>0));
        $Dollar->reload();

        $this->assertNotNull($Dollar->price);
        $this->assertEqual(0,$Dollar->price);
    }

    function test_integers_can_be_passed_literally_as_string()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,title,price integer'));
        $Dollar =& $this->Hybrid->create(array('title'=>'not euro','price'=>'0'));
        $Dollar->reload();

        $this->assertNotNull($Dollar->price);
        $this->assertEqual(0,$Dollar->price);
    }

    function test_installer_should_handle_decimals()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,title,price decimal(10.2)'));
        $columns = $this->Hybrid->getColumnSettings();
        $this->assertEqual($columns['price']['type'],'decimal');
    }

    function test_should_save_and_load_decimals()
    {
        $Product =& new Hybrid(array('title'=>'apple','price'=>10.99));
        $Product->save();

        $Product =& $this->Hybrid->find('first',array('title'=>'apple'));
        $this->assertEqual($Product->price, 10.99);
    }

    function test_should_round_decimal()
    {
        $Product =& $this->Hybrid->create(array('title'=>'BigBlueStock','price'=>12.9888));
        $Product =& $this->Hybrid->find('first',array('title'=>'BigBlueStock'));
        $this->assertEqual($Product->price, 12.99);
    }

    function test_decimals_can_be_zero()
    {
        $Product =& new Hybrid(array('title'=>'chocolada','price'=>0));
        $Product->save();

        $Product =& $this->Hybrid->find('first',array('title'=>'chocolada'));
        $this->assertEqual($Product->price,0);
    }

    function test_decimals_can_be_null()
    {
        $Product =& new Hybrid(array('title'=>'easter-egg','price'=>null));
        $Product->save();

        $Product =& $this->Hybrid->find('first',array('title'=>'easter-egg'));
        $this->assertNull($Product->price);
    }

    function test_installer_should_handle_booleans()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,title,celebrity boolean'));
        $columns = $this->Hybrid->getColumnSettings();
        $this->assertEqual($columns['celebrity']['type'],'boolean');
    }

    function test_datatype_boolean_should_handle_true()
    {
        $Celebrity =& new Hybrid(array('title'=>'Kate','celebrity'=>true));
        $Celebrity->save();

        $Celebrity =& $this->Hybrid->find('first',array('title'=>'Kate'));
        $this->assertTrue($Celebrity->celebrity);
    }

    function test_datatype_boolean_should_handle_false()
    {
        $Celebrity =& new Hybrid(array('title'=>'Vinnie','celebrity'=>false));
        $Celebrity->save();

        $Celebrity =& $this->Hybrid->find('first',array('title'=>'Vinnie'));
        $this->assertFalse($Celebrity->celebrity);
    }

    function test_null_should_not_be_casted_as_false_on_booleans()
    {
        $Celebrity =& new Hybrid(array('title'=>'Franko','celebrity'=>null));
        $Celebrity->save();

        $Celebrity =& $this->Hybrid->find('first',array('title'=>'Franko'));
        $this->assertNull($Celebrity->celebrity);
    }

    function test_should_save_NULL_on_boolean_column()
    {
        $Celebrity =& $this->Hybrid->create(array('title'=>'Franko','celebrity'=>true));
        $Celebrity->updateAttribute('celebrity',null);
        $Celebrity->reload();
        $this->assertNull($Celebrity->celebrity);
    }

    function test_findBy_should_cast_booleans()
    {
        $Celebrity =& $this->Hybrid->findBy('celebrity','true');
        $this->assertTrue($Celebrity[0]->celebrity);
        $this->assertEqual($Celebrity[0]->title,'Kate');
    }

    function test_strings_can_be_empty()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,title'));
        $Post = $this->Hybrid->create(array('title'=>''));
        $Post->reload();
        $this->assertEqual('',$Post->title);
        $this->assertNotNull($Post->title);

        $Post->updateAttribute('title','',true);
        $Post->reload();
        $this->assertEqual('',$Post->title);
        $this->assertNotNull($Post->title);
    }

    function test_strings_can_be_null()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,title'));
        $Post = $this->Hybrid->create(array('title'=>null));
        $Post->reload();
        $this->assertNull($Post->title);
    }

    function test_date_is_not_datetime()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,name,born date'));
        $columns = $this->Hybrid->getColumnSettings();
        $this->assertEqual('date',$columns['born']['type']);
    }

    function test_handle_empty_date_string_as_null()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,name,born date'));
        $Hans =& $this->Hybrid->create(array('name'=>'Hans','born'=>''));
        $Hans->reload();

        $this->assertNull($Hans->born);
    }

    function test_handle_null_date_as_null()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,name,born date'));
        $Hans =& $this->Hybrid->create(array('name'=>'Hans','born'=>null));
        $Hans->reload();

        $this->assertNull($Hans->born);
    }

    function test_should_not_insert_null_string_on_empty_binary_fields()
    {
        $this->installAndIncludeModels(array('Hybrid'=>'id,data binary'));
        $EmptyFile =& $this->Hybrid->create(array());
        $EmptyFile->reload();
        $this->assertNull($EmptyFile->get('data'));
    }

}

ak_test('test_AkActiceRecord_datatypes',true);

?>