<?php

if(!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')){
    define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION',false);
}

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');


require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');


class test_AkActiveRecord_actsAsList extends  UnitTestCase
{
    var $_testing_models_to_delete = array();
    var $_testing_model_databases_to_delete = array();

    function test_AkActiveRecord_actsAsList()
    {
        parent::UnitTestCase();
        $this->_createNewTestingModelDatabase('AkTestTodoItem');
        $this->_createNewTestingModel('AkTestTodoItem');
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

            case 'AkTestTodoItem':
            $model_source =
            '<?php
    class AkTestTodoItem extends AkActiveRecord 
    {
        var $act_as = "list";
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
            case 'ak_test_todo_items':
            $table =
            array(
            'table_name' => 'ak_test_todo_items',
            'fields' => 'id I AUTO KEY,
            position I(20),
            task X,
            due_time T, 
            created_at T, 
            expires T, 
            updated_at T,
            new_position I(10)',
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

    function Test_of_actsAsList_instatiation()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertEqual($TodoItems->actsLike(), 'active record,list');
        $this->assertFalse(empty($TodoItems->list->column));
        $this->assertTrue(empty($TodoItems->list->scope));

        $TodoItems =& new AkTestTodoItem();
        $this->assertErrorPattern('/not_available/',$TodoItems->actsAs('list', array('column'=>'not_available')));

        $this->assertEqual($TodoItems->actsLike(), 'active record');

    }

    function Test_of_Test_of___construct()
    {
        $TodoItems =& new AkTestTodoItem();
        $TodoItems->actsAs('list',
        array(
        'column'=>'new_position', // Redefining the default column
        'scope'=>array('todo_list_id = ? AND completed = 0',$TodoItems->getId()),
        'custom_attribute'=>'This is not allowed here'));
        $this->assertEqual($TodoItems->list->column, 'new_position');
        $this->assertEqual($TodoItems->list->scope, array ( 0 => 'todo_list_id = ? AND completed = 0', 1 => null));
        $this->assertTrue(empty($TodoItems->list->custom_attribute));
    }

    function Test_of__ensureIsActiveRecordInstance()
    {
        $TodoItems =& new AkTestTodoItem();
        $Object =& new AkObject();
        $this->assertErrorPattern('/is not an active record/',$TodoItems->list->_ensureIsActiveRecordInstance(&$Object));
    }

    function Test_of_getType()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertEqual($TodoItems->list->getType(), 'list');
    }

    function Test_of_getScopeCondition_and_setScopeCondition()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertEqual($TodoItems->list->getScopeCondition(), (substr($TodoItems->_db->databaseType,0,4) == 'post') ? 'true' : '1');
        $TodoItems->list->setScopeCondition('true');
        $this->assertEqual($TodoItems->list->getScopeCondition(), 'true');
    }

    function Test_of_getBottomItem_1()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertFalse($TodoItems->list->getBottomItem());
    }

    function Test_of_getBottomPosition_1()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertIdentical($TodoItems->list->getBottomPosition(), 0);
    }

    function Test_of__addToBottom_1()
    {
        $TodoItems =& new AkTestTodoItem();
        $TodoItems->List->_addToBottom();
        $this->assertIdentical($TodoItems->position, 1);
        $this->assertIdentical($TodoItems->List->_ActiveRecordInstance->position, 1);
    }

    function Test_of_beforeCreate()
    {
        $TodoItems =& new AkTestTodoItem();
        $position = $TodoItems->getAttribute('position');
        $TodoItems->List->beforeCreate($TodoItems);
        $this->assertIdentical($TodoItems->getAttribute('position'),$position+1);
    }


    function Test_of_getBottomItem_2()
    {
        $TodoItems =& new AkTestTodoItem('task->','Email Hilario with new product specs','due_time->',Ak::getDate(Ak::time()+(60*60*24*7)));
        $this->assertPattern('/list/',$TodoItems->actsLike());
        $this->assertTrue($TodoItems->isNewRecord());

        $this->assertTrue($TodoItems->save());

        $this->assertTrue($getBottomItem = $TodoItems->List->getBottomItem());
        $this->assertEqual($getBottomItem->toString(), $TodoItems->toString());
        
        $TodoItems =& new AkTestTodoItem('task->','Book COMDEX trip','due_time->',Ak::getDate(Ak::time()+(60*60*24*3)));
        $this->assertTrue($TodoItems->isNewRecord());
        $this->assertTrue($TodoItems->save());
        $this->assertTrue($getBottomItem = $TodoItems->List->getBottomItem());

        $this->assertEqual($getBottomItem->toString(), $TodoItems->toString());

        $TodoItems =& new AkTestTodoItem(1);
        $this->assertTrue($getBottomItem = $TodoItems->List->getBottomItem(2));
        $this->assertEqual($getBottomItem->toString(), $TodoItems->toString());

    }

    function Test_of_getBottomPosition_2()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertEqual($TodoItems->list->getBottomPosition(), 2);

        $TodoItem = $TodoItems->find(2);
        $this->assertEqual($TodoItem->list->getBottomPosition(), 2);
    }

    function Test_of__addToBottom_2()
    {
        $TodoItems =& new AkTestTodoItem();
        $TodoItems->list->_addToBottom();
        $this->assertIdentical($TodoItems->position, 3);
        $this->assertIdentical($TodoItems->list->_ActiveRecordInstance->position, 3);
    }


    function Test_of_isInList()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertFalse($TodoItems->list->isInList());

        $TodoItems =& new AkTestTodoItem(1);
        $this->assertTrue($TodoItems->list->isInList());
    }


    function Test_of_populate_todo_list()
    {
        for ($i = 0; $i <= 30; $i++){
            $attributes = array('task'=>'Task number '.($i+3),'due_time'=>Ak::getDate(Ak::time()+(60*60*24*$i)));
            $TodoTask =& new AkTestTodoItem($attributes);
            $this->assertTrue($TodoTask->save());
            $this->assertTrue(($TodoTask->task == $attributes['task']) && $TodoTask->due_time == $attributes['due_time']);
        }
    }


    function Test_of_decrementPositionsOnLowerItems()
    {
        $TodoItems =& new AkTestTodoItem();
        $TodoItems->transactionStart();
        $this->assertFalse($TodoItems->list->decrementPositionsOnLowerItems());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $TodoItems =& new AkTestTodoItem(10);
        
        $this->assertTrue($TodoItems->list->decrementPositionsOnLowerItems());
        $todo_list = $this->_getTodoList();

        $this->assertEqual($todo_list[10] , 'Task number 11');
        $this->assertFalse(in_array('Task number 10',$todo_list));

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10','Test failed because a database transaction was not performed correctly');
    }

    function Test_of_removeFromList()
    {
        $TodoItems =& new AkTestTodoItem(10);
        
        $TodoItems->transactionStart();
        $this->assertTrue($TodoItems->list->removeFromList());
        $this->assertFalse($TodoItems->list->isInList());
        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10','Test failed because a database transaction was not performed correctly');
    }


    function Test_of_afterDestroy_and_beforeDestroy()
    {
        $TodoItems =& new AkTestTodoItem(10);
        
        $TodoItems->transactionStart();

        $TodoItems->destroy();
        $this->assertFalse($TodoItems->list->isInList());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 11');

        $this->assertEqual($todo_list[14] , 'Task number 15');

        $TodoItems->destroy(array(15,16));

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[13] , 'Task number 14');
        $this->assertEqual($todo_list[14] , 'Task number 17');
        $this->assertEqual($todo_list[15] , 'Task number 18');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10','Test failed because a database transaction was not performed correctly');

    }

    function Test_of_getLowerItem()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertFalse($TodoItems->list->getLowerItem());
        $TodoItem = $TodoItems->find(10);
        
        $LowerItem = $TodoItem->list->getLowerItem();
        $this->assertEqual($LowerItem->task, 'Task number 11');

        $TodoItem = $TodoItems->find(33);
        $this->assertFalse($TodoItem->list->getLowerItem());


        $TodoItems =& new AkTestTodoItem();
        $TodoItems->transactionStart();
        $this->assertTrue($TodoItems->deleteAll());

        $this->assertFalse($TodoItems->list->getLowerItem());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_decrementPosition()
    {
        $TodoItems =& new AkTestTodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->decrementPosition());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_incrementPosition()
    {
        $TodoItems =& new AkTestTodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->incrementPosition());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_moveLower()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertFalse($TodoItems->list->moveLower());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $this->assertEqual($todo_list[11] , 'Task number 11');

        $TodoItems =& new AkTestTodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveLower());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 11');
        $this->assertEqual($todo_list[11] , 'Task number 10');

        $TodoItems =& new AkTestTodoItem(33);
        $this->assertFalse($TodoItems->list->moveLower());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();

    }

    function Test_of_getHigherItem()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertFalse($TodoItems->list->getHigherItem());

        $TodoItem = $TodoItems->find(10);
        $HigherItem = $TodoItem->list->getHigherItem();
        $this->assertEqual($HigherItem->task, 'Task number 9');

        $TodoItem = $TodoItems->find(1);
        $this->assertFalse($TodoItem->list->getHigherItem());


        $TodoItems =& new AkTestTodoItem();
        $TodoItems->transactionStart();
        $this->assertTrue($TodoItems->deleteAll());

        $this->assertFalse($TodoItems->list->getHigherItem());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }


    function Test_of_moveHigher()
    {
        $TodoItems =& new AkTestTodoItem();
        $this->assertFalse($TodoItems->list->moveHigher());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[9] , 'Task number 9');
        $this->assertEqual($todo_list[10] , 'Task number 10');

        $TodoItems =& new AkTestTodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveHigher());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[9] , 'Task number 10');
        $this->assertEqual($todo_list[10] , 'Task number 9');

        $TodoItems =& new AkTestTodoItem(1);
        $this->assertFalse($TodoItems->list->moveHigher());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_assumeBottomPosition()
    {
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');

        $TodoItems =& new AkTestTodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->assumeBottomPosition());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[34] , 'Task number 10');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_moveToBottom()
    {
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');

        $TodoItems =& new AkTestTodoItem();
        $this->assertFalse($TodoItems->list->moveToBottom());

        $TodoItems =& new AkTestTodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveToBottom());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 11');
        $this->assertEqual($todo_list[32] , 'Task number 33');
        $this->assertEqual($todo_list[33] , 'Task number 10');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();

        $TodoItems =& new AkTestTodoItem(33);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveToBottom());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[33] , 'Task number 33');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_incrementPositionsOnHigherItems()
    {
        $TodoItems =& new AkTestTodoItem();
        $TodoItems->transactionStart();
        $this->assertFalse($TodoItems->list->incrementPositionsOnHigherItems());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $TodoItems =& new AkTestTodoItem(10);
        $this->assertTrue($TodoItems->list->incrementPositionsOnHigherItems());
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[9] , 'Task number 8');
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }


    function Test_of_assumeTopPosition()
    {
        $TodoItems =& new AkTestTodoItem();
        $TodoItems->transactionStart();

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $TodoItems =& new AkTestTodoItem(10);
        $this->assertTrue($TodoItems->list->assumeTopPosition());
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[1] , 'Task number 10');
        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_moveToTop()
    {
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');

        $TodoItems =& new AkTestTodoItem();
        $this->assertFalse($TodoItems->list->moveToTop());

        $TodoItems =& new AkTestTodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveToTop());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 9');
        $this->assertEqual($todo_list[2] , 'Email Hilario with new product specs');
        $this->assertEqual($todo_list[1] , 'Task number 10');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();

        $TodoItems =& new AkTestTodoItem(1);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveToTop());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[1] , 'Email Hilario with new product specs');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_isFirst()
    {
        $TodoItems =& new AkTestTodoItem(1);

        $this->assertTrue($TodoItems->list->isFirst());

        $TodoItems =& new AkTestTodoItem(2);
        $this->assertFalse($TodoItems->list->isFirst());

        $TodoItems =& new AkTestTodoItem();
        $this->assertFalse($TodoItems->list->isFirst());
    }


    function Test_of_isLast()
    {
        $TodoItems =& new AkTestTodoItem(33);
        $this->assertTrue($TodoItems->list->isLast());

        $TodoItems =& new AkTestTodoItem(1);
        $this->assertFalse($TodoItems->list->isLast());

        $TodoItems =& new AkTestTodoItem();
        $this->assertFalse($TodoItems->list->isLast());
    }


    function Test_of_incrementPositionsOnLowerItems()
    {
        $TodoItems =& new AkTestTodoItem();
        $TodoItems->transactionStart();

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $TodoItems =& new AkTestTodoItem(10);
        $this->assertTrue($TodoItems->list->incrementPositionsOnLowerItems(10));
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[9] , 'Task number 9');
        $this->assertEqual($todo_list[11] , 'Task number 10');
        $this->assertEqual($todo_list[34] , 'Task number 33');
        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();

    }
    function Test_of_insertAtPosition()
    {
        $TodoItems =& new AkTestTodoItem(10);
        $TodoItems->transactionStart();

        $TodoItems->list->insertAtPosition(1);
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[1] , 'Task number 10');


        $TodoItems =& new AkTestTodoItem('task->','ship new InmoEasy version');
        $TodoItems->list->insertAtPosition(1);
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[1] , 'ship new InmoEasy version');

        $TodoItems =& new AkTestTodoItem(10);
        $TodoItems->list->insertAtPosition(10);
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');

        $TodoItems =& new AkTestTodoItem(33);
        $TodoItems->list->insertAtPosition(40);
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[40] , 'Task number 33');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }
    function Test_of_insertAt(){} //Alias for insertAtPosition but with default value to 1


    function Test_of_incrementPositionsOnAllItems()
    {
        $TodoItems =& new AkTestTodoItem();
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->incrementPositionsOnAllItems());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[2] , 'Email Hilario with new product specs');
        $this->assertEqual($todo_list[34] , 'Task number 33');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_addToListTop(){} // same as incrementPositionsOnAllItems()

    function Test_of_decrementPositionsOnHigherItems()
    {
        $TodoItems =& new AkTestTodoItem();
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->decrementPositionsOnHigherItems(10));

        $todo_list = $this->_getTodoList();

        $this->assertEqual($todo_list[0] , 'Email Hilario with new product specs');
        $this->assertEqual($todo_list[9] , 'Task number 10');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }
    /**/

    function _getTodoList($use_id_as_index = false)
    {
        $TodoItems = new AkTestTodoItem();
        $TodoItems = $TodoItems->find();
        $list = array();
        foreach ($TodoItems as $TodoItem){
            if($use_id_as_index){
                $list[$TodoItem->id] = $TodoItem->position.') '.$TodoItem->task;
            }else{
                $list[$TodoItem->position] = $TodoItem->task;
            }
        }
        return $list;
    }

}


ak_test('test_AkActiveRecord_actsAsList',true);

?>
