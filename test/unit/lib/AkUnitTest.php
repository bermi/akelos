<?php
defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkUnitTest.php');

class Test_of_AkUnitTest extends  AkUnitTest
{

    function test_start()
    {
        
    }
    
    function test_should_create_models_on_the_fly()
    {
        $unit_tester = new AkUnitTest();
        $this->assertFalse(class_exists('SomeSillyModel'));
        $unit_tester->installAndIncludeModels(array('SomeSillyModel'=>'id,body'));
        $this->assertTrue(class_exists('SomeSillyModel'));
        $this->assertTrue($someModel = new SomeSillyModel());
        $this->assertEqual($someModel->getTableName(),'some_silly_models');
        $this->assertTrue($someModel->hasColumn('id'));
        $this->assertTrue($someModel->hasColumn('body'));
        $this->assertTrue($someModel->create(array('body'=>'something')));
        $this->assertTrue($someModel->find('first',array('body'=>'something')));
        
        $unit_tester->installAndIncludeModels(array('SomeSillyModel'=>'id,body'));
        $this->assertNoErrors();
        $this->assertFalse($someModel->find('all'));
    }
    
    function test_should_instantiate_Model()
    {
        $unit_tester = new AkUnitTest();
        $this->assertFalse(isset($unit_tester->Account));
        $unit_tester->instantiateModel('Account');
        $this->assertTrue(isset($unit_tester->Account));
        $this->assertTrue(AkActiveRecord::descendsFromActiveRecord($unit_tester->Account));
        
        $this->assertFalse($unit_tester->instantiateModel('AnotherModel'));
        $this->assertError('Could not instantiate AnotherModel');
        $this->assertFalse(isset($unit_tester->AnotherModel));
        
        $unit_tester->instantiateModel('SomeSillyModel');
        $this->assertTrue(isset($unit_tester->SomeSillyModel));
    }
    
    function test_should_produce_some_errors()
    {
        $unit_tester = new AkUnitTest();
        $unit_tester->installAndIncludeModels('Illegal Name');
        $this->assertError('Could not install the table illegal_names for the model Illegal Name');
        $this->assertError('Could not declare the model Illegal Name.');
        $this->assertError('Could not instantiate Illegal Name');
        
        $unit_tester->installAndIncludeModels('AnotherModel',array('instantiate'=>false));
        $this->assertError('Could not install the table another_models for the model AnotherModel');
    }
    
    function test_should_fill_the_table_with_yaml_data()
    {
        $unit_tester = new AkUnitTest();
        $unit_tester->installAndIncludeModels(array('TheModel'=>'id,name'));
        $TheModel =& $unit_tester->TheModel;
        $TheModel->create(array('name'=>'eins'));
        $TheModel->create(array('name'=>'zwei'));
        $TheModel->create(array('name'=>'drei'));
        $TheModel->create(array('name'=>'vier'));
        $this->assertEqual($TheModel->count(),4);
        
        $this->assertTrue($AllRecords = $TheModel->find());
        $yaml = $TheModel->toYaml($AllRecords);
        $this->assertFalse(file_exists(AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'the_models.yaml'));
        Ak::file_put_contents(AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'the_models.yaml',$yaml);
        
        $unit_tester->installAndIncludeModels(array('TheModel'=>'id,name'));
        $this->assertFalse($TheModel->find());
        $this->assertEqual($TheModel->count(),0);
        
        $unit_tester->installAndIncludeModels(array('TheModel'=>'id,name'),array('populate'=>true));
        $this->assertEqual($TheModel->count(),4);
        unlink(AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.'the_models.yaml');
        
    }
}

ak_test('Test_of_AkUnitTest', true);

?>