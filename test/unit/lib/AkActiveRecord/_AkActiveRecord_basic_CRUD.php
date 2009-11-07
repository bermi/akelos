<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class test_ActiveRecord_basic_CRUD_Operation extends AkUnitTest
{
    public function _test_should_return_false_when_destroy_fails()
    {
        $this->installAndIncludeModels('Post');

        $Post = new Post(array('title'=>'A Title'));
        $Post->save();
        $this->assertTrue($Post->destroy());
        $this->assertFalse($Post->destroy());
    }

    public function test_set_Integer_Column_To_Zero_When_Column_Defined_As_Not_Null__ticket_113()
    {
        $this->installAndIncludeModels(array('Product'=>'id,products_status int not null'));
        $prod = new Product(array('products_status' => 2));
        $prod->save();

        $prod->reload();
        $this->assertTrue($prod->updateAttribute('products_status', 0));
        $this->assertTrue($prod->products_status == 0,'Setting to 0 failed. products_status is '.$prod->products_status);
        $prod->reload();
        $this->assertTrue($prod->products_status == 0,'Save failed. products_status is '.$prod->products_status);
    }

    public function test_set_Null()
    {
        $this->installAndIncludeModels(array('Product'=>'id,reference int null'));
        $Product = new Product(array('reference'=>1));
        $Product->save();
        $Product->reload();
        $this->assertEqual($Product->reference,1);
        $Product->updateAttribute('reference',null);
        $this->assertNull($Product->reference);
        $Product->reload();
        $this->assertNull($Product->reference);
    }
}

ak_test('test_ActiveRecord_basic_CRUD_Operation',true);

?>