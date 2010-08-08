<?php

require_once(dirname(__FILE__).'/../config.php');

class Adapter_quoting_table_names_and_attributes_TestCase extends ActiveRecordUnitTest
{
    public function test_should_create_and_remove_table() {
        $this->installAndIncludeModels(array('Exist'=>'id,order'));
        $Installer = new AkInstaller();
        $this->assertTrue($Installer->tableExists('exists'));
        $Installer->dropTable('exists');
        $this->assertFalse($Installer->tableExists('exists'));
    }

    public function test_should_escape_attribute_names() {
        $this->installAndIncludeModels(array('Exist'=>'id,order,index'));
        $Installer = new AkInstaller();
        $this->assertTrue($Installer->tableExists('exists'));
        $Exist = new Exist();
        $columns= array_keys($Exist->getColumns());
        sort($columns);
        $this->assertEqual($columns, array('id', 'index', 'order'));
    }

    public function test_should_crud_using_conflictive_column_names(){
        $this->installAndIncludeModels(array('Exist'=>'id,order,index, updated_at'));
        $Installer = new AkInstaller();
        $this->assertTrue($Installer->tableExists('exists'));
        $Exist = new Exist();
        $Same = $Exist->create(array('order' => 'order_value', 'index' => 'index_value'));
        $this->assertTrue(!$Same->isNewRecord());
        $this->assertTrue($Exist->findFirstBy($Same->getId()));

        $last_update = $Same->get('updated_at');

        $Same->save();
        $this->assertNotEqual($last_update, $Same->get('updated_at'));

        $id = $Same->getId();
        $Same->destroy();

        $this->assertFalse($Exist->findFirstBy($id, array('default' => false)));
    }
}

ak_test_case('Adapter_quoting_table_names_and_attributes_TestCase');