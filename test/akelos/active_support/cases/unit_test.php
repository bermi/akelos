<?php

require_once(dirname(__FILE__).'/../config.php');

class UnitTest_TestCase extends ActiveSupportUnitTest
{
    public function __destruct() {
        $this->dropTables('all');
    }
    public function test_should_create_models_on_the_fly() {
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

        $this->expectException('RecordNotFoundException');
        $someModel->find('all');

    }


    public function test_should_instantiate_model() {
        $unit_tester = new AkUnitTest();
        $unit_tester->app_dir = AkConfig::getDir('suite');
        $this->assertFalse(isset($unit_tester->DummyAccount));
        $unit_tester->instantiateModel('DummyAccount');

        $this->assertTrue(isset($unit_tester->DummyAccount));
        $this->assertTrue($unit_tester->DummyAccount instanceof AkActiveRecord);

        $this->expectError('Could not instantiate AnotherModel');
        $this->assertFalse($unit_tester->instantiateModel('AnotherModel'));
        $this->assertFalse(isset($unit_tester->AnotherModel));

        $unit_tester->instantiateModel('SomeSillyModel');
        $this->assertTrue(isset($unit_tester->SomeSillyModel));
    }

    public function test_should_produce_some_errors() {
        $unit_tester = new AkUnitTest();

        $this->expectError('Could not install the table illegal_names for the model Illegal Name');
        $this->expectError('Could not declare the model Illegal Name.');
        $this->expectError('Could not instantiate Illegal Name');

        $unit_tester->installAndIncludeModels('Illegal Name');

        $this->expectError('Could not install the table another_models for the model AnotherModel');
        $unit_tester->installAndIncludeModels('AnotherModel',array('instantiate'=>false));
    }

    public function test_should_fill_the_table_with_yaml_data() {
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

        $yaml_path = AkConfig::getDir('fixtures').DS.'the_models.yml';

        $this->assertFalse(file_exists($yaml_path));
        AkFileSystem::file_put_contents($yaml_path, $yaml);

        $unit_tester->installAndIncludeModels(array('TheModel'=>'id,name'));

        try{
            $TheModel->find();
        } catch (RecordNotFoundException $e){
            $this->pass();
        }

        $this->assertEqual($TheModel->count(),0);

        $unit_tester->installAndIncludeModels(array('TheModel'=>'id,name'), array('populate'=>true));
        $this->assertEqual($TheModel->count(), 4);
        unlink($yaml_path);

    }

    public function test_should_instantiate_selected_models() {
        $models = array('DummyPicture', 'DummyLandlord');

        $unit_tester = new AkUnitTest();
        $unit_tester->includeAndInstatiateModels($models);
        foreach ($models as $model){
            $this->assertTrue(isset($unit_tester->$model));
            $this->assertTrue($unit_tester->$model instanceof AkActiveRecord);
        }

        $unit_tester = new AkUnitTest();
        $unit_tester->includeAndInstatiateModels(join(',',$models));
        foreach ($models as $model){
            $this->assertTrue(isset($unit_tester->$model));
            $this->assertTrue($unit_tester->$model instanceof AkActiveRecord);
        }

    }

    public function test_should_run_migration_up_and_down() {
        $unit_tester = new AkUnitTest();
        $unit_tester->includeAndInstatiateModels('DummyPicture');

        $this->assertTrue($unit_tester->DummyPicture->create(array('title'=>__FUNCTION__)));
        $this->assertTrue($unit_tester->DummyPicture->find('first', array('title'=>__FUNCTION__)));

        $unit_tester->uninstallAndInstallMigration('DummyPicture');

        $this->expectException('RecordNotFoundException');
        $unit_tester->DummyPicture->find('all');
    }

}

ak_test_case('UnitTest_TestCase');

