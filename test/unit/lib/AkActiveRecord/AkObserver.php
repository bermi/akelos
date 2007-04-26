<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkObserver.php');


class test_AkActiveRecord_observer extends  UnitTestCase
{
    var $_testing_models_to_delete = array();
    var $_testing_model_databases_to_delete = array();

    function test_AkActiveRecord_observer()
    {
        parent::UnitTestCase();
        $this->_createNewTestingModelDatabase('AkTestObservedPerson');
        $this->_createNewTestingModelDatabase('AkTestObservedAccount');
        $this->_createNewTestingModel('AkTestObservedPerson');
        $this->_createNewTestingModel('AkTestObservedAccount');
        $this->_createNewTestingModel('AkTestObservedPersonObserver');
        $this->_createNewTestingModel('AkTestAuditor');
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

            case 'AkTestObservedPerson':
            $model_source =
            '<?php
    class AkTestObservedPerson extends AkActiveRecord 
    { 
    } 
?>';
            break;

            case 'AkTestObservedAccount':
            $model_source =
            '<?php
    class AkTestObservedAccount extends AkActiveRecord 
    {
    } 
?>';
            break;

            case 'AkTestObservedPersonObserver':
            $model_source =
            '<?php
    class AkTestObservedPersonObserver extends AkObserver 
    {
        function update($state)
        {
            switch ($state)
            {
                case "new person created" :
                echo $state;
                break;
                default:
                break;
            }
        }
        
        function afterCreate(&$record)
        {
            echo $record->get("first_name")." has been email with account details";
            $this->logNotified($record,__FUNCTION__);
        }
        
        function afterSave(&$record){$this->logNotified($record,__FUNCTION__);}
        function afterValidationOnCreate(&$record){$this->logNotified($record,__FUNCTION__);}
        function afterValidationOnUpdate(&$record){$this->logNotified($record,__FUNCTION__);}
        function beforeSave(&$record){$this->logNotified($record,__FUNCTION__);
            if(!empty($record->city) && $record->city == "Carlet")
            {
                $record->state = "Valencia";
            }
        }
        function beforeCreate(&$record){$this->logNotified($record,__FUNCTION__); }
        function beforeValidationOnCreate(&$record){$this->logNotified($record,__FUNCTION__);}
        function beforeValidation(&$record){$this->logNotified($record,__FUNCTION__);}
        function afterValidation(&$record) {$this->logNotified($record,__FUNCTION__);}

        function logNotified(&$record, $function)
        {
            if(!isset($record->notified_observers[$function])){
                $record->notified_observers[$function] = 0;
            }
            $record->notified_observers[$function]++;
        }

    }
?>';
            break;


            case 'AkTestAuditor':
            $model_source =
            '<?php
    class AkTestAuditor extends AkObserver 
    { 
        function update($state)
        {
            switch ($state)
            {
                case "new person created" :
                echo $state;
                break;
                default:
                break;
            }
        }
        
        function afterCreate(&$record)
        {
            $record->audited = true;
        }

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
            case 'ak_test_observed_people':
            $table =
            array(
            'table_name' => 'ak_test_observed_people',
            'fields' => 'id I AUTO KEY,
            user_name C(32), 
            first_name C(200), 
            last_name C(200), 
            phone_number I(18), 
            city C(40), 
            state C(40), 
            email C(150), 
            country C(2), 
            sex C(1), 
            birth T, 
            age I(3), 
            password C(32), 
            tos L(1), 
            score I(3), 
            comments X, 
            created_at T, 
            updated_at T, 
            expires T',
            'index_fileds' => 'id',
            'table_options' => array('mysql' => 'TYPE=InnoDB', 'REPLACE')
            );
            break;

            case 'ak_test_observed_accounts':
            $table =
            array(
            'table_name' => 'ak_test_observed_accounts',
            'fields' => 'id I AUTO KEY,
            balance C(32), 
            ak_test_observed_person_id I,
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



    function Test_of__instatiateDefaultObserver()
    {
        $Observed = new AkTestObservedPerson();
        $ObeserversReference =& $Observed->getObservers();
        $this->assertEqual(strtolower(get_class($ObeserversReference[0])), 'aktestobservedpersonobserver');
    }

    function Test_of_addObserver()
    {
        $Observed = new AkTestObservedPerson();

        $null = null;
        $Observer =& Ak::singleton('AkTestObservedPersonObserver', $null);
        
        $params = 'AkTestObservedAccount';
        $Auditor =& Ak::singleton('AkTestAuditor',$params);
        $Auditor->observe(&$Observed);

        $ObeserversReference =& $Observed->getObservers();

        $ObeserversReference[0]->message = 'Hello. I come from the past';

        $this->assertEqual($ObeserversReference[0]->__singleton_id, $Observer->__singleton_id);
        $this->assertReference($ObeserversReference[1], $Auditor);
    }


    function Test_of_addObserver2()
    {
        $ObservedPerson =& new AkTestObservedPerson();

        $ObeserversReference =& $ObservedPerson->getObservers();
        $this->assertEqual(strtolower(get_class($ObeserversReference[0])), 'aktestobservedpersonobserver');
        $this->assertEqual($ObeserversReference[0]->message, 'Hello. I come from the past');
        $this->assertEqual(strtolower(get_class($ObeserversReference[1])), 'aktestauditor');

        $ObservedAccount =& new AkTestObservedAccount();
        $ObeserversReference =& $ObservedAccount->getObservers();
        $this->assertEqual(strtolower(get_class($ObeserversReference[0])), 'aktestauditor');
    }

    function __Test_of_setObservableState_and_getObservableState()
    {
        $ObservedAccount1 =& new AkTestObservedAccount();
        $ObservedAccount1->setObservableState('creating account 1');

        $ObservedAccount2 =& new AkTestObservedAccount();
        $ObservedAccount2->setObservableState('creating account 2');

        $this->assertEqual($ObservedAccount2->getObservableState(), 'creating account 2');
        $this->assertEqual($ObservedAccount1->getObservableState(), 'creating account 1');
    }

    function Test_of_notifyObservers()
    {
        $ObservedPerson =& new AkTestObservedPerson();
        $ObservedPerson->setObservableState('new person created');

        ob_start();
        $ObservedPerson->notifyObservers();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertEqual($content,'new person creatednew person created');

        $this->assertEqual($ObservedPerson->getObservableState(), '');
    }


    function Test_of_default_Active_record_observer_triggers()
    {
        $ObservedPerson =& new AkTestObservedPerson('first_name->','Bermi');
        
        $this->assertTrue(empty($ObservedPerson->audited));
        ob_start();
        $ObservedPerson->save();
        
        $this->assertTrue($ObservedPerson->audited);
        
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertEqual($content, "Bermi has been email with account details");

        $notified = array();
        foreach ($ObservedPerson->notified_observers as $k=>$v){
            $notified[strtolower($k)] = $v;
        }
        $this->assertEqual($notified, array ( 'beforevalidation' => 1, 'beforevalidationoncreate' => 1, 'aftervalidationoncreate' => 1, 'aftervalidation' => 1, 'beforecreate' => 1, 'beforesave' => 1, 'aftersave' => 1, 'aftercreate' => 1, ));

        $ObservedPerson->set('last_name','Ferrer');
        $ObservedPerson->save();
        
        $notified = array();
        foreach ($ObservedPerson->notified_observers as $k=>$v){
            $notified[strtolower($k)] = $v;
        }
        $this->assertEqual($notified, array ( 'beforevalidation' => 2, 'beforevalidationoncreate' => 1, 'aftervalidationoncreate' => 1, 'aftervalidation' => 2, 'beforecreate' => 1, 'beforesave' => 2, 'aftersave' => 2, 'aftercreate' => 1, 'aftervalidationonupdate' => 1, ));

    }
    

    function Test_of_beforeSave_trigger()
    {
        $ObservedPerson =& new AkTestObservedPerson();
        
        $ObservedPerson->city = "Carlet";
        
        ob_start();
        $ObservedPerson->save();
        ob_end_clean();
        
        $this->assertEqual($ObservedPerson->state, "Valencia");
        
    }
    
}

ak_test('test_AkActiveRecord_observer', true);

?>
