<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../fixtures/config/config.php');


require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');


class test_AkActiveRecord_locking extends  UnitTestCase
{
    var $_testing_models_to_delete = array();
    var $_testing_model_databases_to_delete = array();

    function test_AkActiveRecord_validators()
    {
        parent::UnitTestCase();
        $this->_createNewTestingModelDatabase('AkTestBankAccount');
        $this->_createNewTestingModel('AkTestBankAccount');
    }

    function setUp()
    {
    }

    function tearDown()
    {
        unset($_SESSION['__activeRecordColumnsSettingsCache']);
    }


    function _createNewTestingModel($test_model_name)
    {

        static $shutdown_called;
        switch ($test_model_name) {

            case 'AkTestBankAccount':
            $model_source =
            '<?php
    class AkTestBankAccount extends AkActiveRecord 
    {     
    } 
?>';
            break;

            default:
            $model_source = '<?php class '.$test_model_name.' extends AkActiveRecord { } ?>';
            break;
        }

        $file_name = AkInflector::toModelFilename($test_model_name);

        if(!Ak::file_put_contents($file_name,$model_source)){
            die('Ooops!, in order to perform this test, you must set your app/model permissions so this can script can create and delete files into/from it');
        }
        if(!in_array($file_name, get_included_files()) && !class_exists($test_model_name)){
            include($file_name);
        }else {
            return false;
        }
        $this->_testing_models_to_delete[] = $file_name;
        if(!isset($shutdown_called)){
            $shutdown_called = true;
            register_shutdown_function(array(&$this,'_deleteTestingModels'));
        }
        return true;
    }

    function _deleteTestingModels()
    {
        foreach ($this->_testing_models_to_delete as $file){
            Ak::file_delete($file);
        }
    }




    function _createNewTestingModelDatabase($test_model_name)
    {
        static $shutdown_called;
        // Create a data dictionary object, using this connection
        $db =& AK::db();
        //$db->debug = true;
        $table_name = AkInflector::tableize($test_model_name);
        if(in_array($table_name, (array)$db->MetaTables())){
            return false;
        }
        switch ($table_name) {
            case 'ak_test_bank_accounts':
            $table =
            array(
            'table_name' => 'ak_test_bank_accounts',
            'fields' => 'id I AUTO KEY,
            balance I(20),
            lock_version I(20),
            created_at T, 
            updated_at T',
            'index_fileds' => 'id',
            'table_options' => array('mysql' => 'TYPE=InnoDB', 'REPLACE')
            );
            break;
            default:
            return false;
            break;
        }

        $dict = NewDataDictionary($db);
        $sqlarray = $dict->CreateTableSQL($table['table_name'], $table['fields'], $table['table_options']);
        $dict->ExecuteSQLArray($sqlarray);
        if(isset($table['index_fileds'])){
            $sqlarray = $dict->CreateIndexSQL('idx_'.$table['table_name'], $table['table_name'], $table['index_fileds']);
            $dict->ExecuteSQLArray($sqlarray);
        }

        $db->CreateSequence('seq_'.$table['table_name']);

        $this->_testing_model_databases_to_delete[] = $table_name;
        if(!isset($shutdown_called)){
            $shutdown_called = true;
            register_shutdown_function(array(&$this,'_deleteTestingModelDatabases'));
        }
        //$db->debug = false;
        return true;
    }

    function _deleteTestingModelDatabases()
    {
        $db =& AK::db();
        foreach ($this->_testing_model_databases_to_delete as $table_name){
            $db->Execute('DROP TABLE '.$table_name);
            $db->DropSequence('seq_'.$table_name);
        }
    }

    function Test_of_isLockingEnabled()
    {
        $Account = new AkTestBankAccount();
        
        $this->assertTrue($Account->isLockingEnabled());
        
        $Account->lock_optimistically = false;
        
        $this->assertFalse($Account->isLockingEnabled());
    }

    function Test_of_OptimisticLock()
    {
        $Account1 = new AkTestBankAccount('balance->',2000); 
        $Account1->save(); // version 1
        
        $Account2 = new AkTestBankAccount($Account1->getId()); // version 1
        
        
        $Account1->balance = 5;
        $Account2->balance = 3000000;
        
        $Account1->save(); // version 2
        
        //$Account2->_db->debug =true;
        
        $this->assertFalse(@$Account2->save()); // version 1
        $this->assertFalse(@$Account2->save()); // version 1
        //$Account2->_db->debug = false;
        $this->assertErrorPattern('/stale|modificado/',$Account2->save());
        
        $Account1->balance = 1000; 
        
        $this->assertTrue($Account1->save()); // version 2
        
        $Account3 = new AkTestBankAccount($Account1->getId());
        
        $this->assertEqual($Account3->balance, 1000);
    }

}

Ak::test('test_AkActiveRecord_locking',true);

?>
