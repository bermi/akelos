<?php

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

if(!defined('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION')){
    define('AK_ACTIVE_RECORD_PROTECT_GET_RECURSION', false);
}

class AkActiveRecord_actsAsListTestCase extends  AkUnitTest
{

    function test_AkActiveRecord_actsAsList()
    {
        $this->installAndIncludeModels(array(
            'TodoItem'=>'id, position int, task text, due_time datetime, created_at, expires datetime, updated_at,new_position int'
        ));
    }

    function Test_of_actsAsList_instatiation()
    {
        $TodoItems =& new TodoItem();
        $this->assertEqual($TodoItems->actsLike(), 'active record,list');
        $this->assertFalse(empty($TodoItems->list->column));
        $this->assertTrue(empty($TodoItems->list->scope));

        $TodoItems =& new TodoItem();
        $this->assertErrorPattern('/not_available/',$TodoItems->actsAs('list', array('column'=>'not_available')));

        $this->assertEqual($TodoItems->actsLike(), 'active record');

    }

    function Test_of_Test_of___construct()
    {
        $TodoItems =& new TodoItem();
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
        $TodoItems =& new TodoItem();
        $Object =& new AkObject();
        $this->assertErrorPattern('/is not an active record/',$TodoItems->list->_ensureIsActiveRecordInstance(&$Object));
    }

    function Test_of_getType()
    {
        $TodoItems =& new TodoItem();
        $this->assertEqual($TodoItems->list->getType(), 'list');
    }

    function Test_of_getScopeCondition_and_setScopeCondition()
    {
        $TodoItems =& new TodoItem();
        $this->assertEqual($TodoItems->list->getScopeCondition(), ($TodoItems->_db->type() == 'postgre') ? 'true' : '1');
        $TodoItems->list->setScopeCondition('true');
        $this->assertEqual($TodoItems->list->getScopeCondition(), 'true');
    }

    function Test_of_getBottomItem_1()
    {
        $TodoItems =& new TodoItem();
        $this->assertFalse($TodoItems->list->getBottomItem());
    }

    function Test_of_getBottomPosition_1()
    {
        $TodoItems =& new TodoItem();
        $this->assertIdentical($TodoItems->list->getBottomPosition(), 0);
    }

    function Test_of__addToBottom_1()
    {
        $TodoItems =& new TodoItem();
        $TodoItems->List->_addToBottom();
        $this->assertIdentical($TodoItems->position, 1);
        $this->assertIdentical($TodoItems->List->_ActiveRecordInstance->position, 1);
    }

    function Test_of_beforeCreate()
    {
        $TodoItems =& new TodoItem();
        $position = $TodoItems->getAttribute('position');
        $TodoItems->List->beforeCreate($TodoItems);
        $this->assertIdentical($TodoItems->getAttribute('position'),$position+1);
    }


    function Test_of_getBottomItem_2()
    {
        $TodoItems =& new TodoItem('task->','Email Hilario with new product specs','due_time->',Ak::getDate(Ak::time()+(60*60*24*7)));
        $this->assertPattern('/list/',$TodoItems->actsLike());
        $this->assertTrue($TodoItems->isNewRecord());

        $this->assertTrue($TodoItems->save());

        $this->assertTrue($getBottomItem = $TodoItems->List->getBottomItem());
        $this->assertEqual($getBottomItem->toString(), $TodoItems->toString());

        $TodoItems =& new TodoItem('task->','Book COMDEX trip','due_time->',Ak::getDate(Ak::time()+(60*60*24*3)));
        $this->assertTrue($TodoItems->isNewRecord());
        $this->assertTrue($TodoItems->save());
        $this->assertTrue($getBottomItem = $TodoItems->List->getBottomItem());

        $this->assertEqual($getBottomItem->toString(), $TodoItems->toString());

        $TodoItems =& new TodoItem(1);
        $this->assertTrue($getBottomItem = $TodoItems->List->getBottomItem(2));
        $this->assertEqual($getBottomItem->toString(), $TodoItems->toString());

    }

    function Test_of_getBottomPosition_2()
    {
        $TodoItems =& new TodoItem();
        $this->assertEqual($TodoItems->list->getBottomPosition(), 2);

        $TodoItem = $TodoItems->find(2);
        $this->assertEqual($TodoItem->list->getBottomPosition(), 2);
    }

    function Test_of__addToBottom_2()
    {
        $TodoItems =& new TodoItem();
        $TodoItems->list->_addToBottom();
        $this->assertIdentical($TodoItems->position, 3);
        $this->assertIdentical($TodoItems->list->_ActiveRecordInstance->position, 3);
    }


    function Test_of_isInList()
    {
        $TodoItems =& new TodoItem();
        $this->assertFalse($TodoItems->list->isInList());

        $TodoItems =& new TodoItem(1);
        $this->assertTrue($TodoItems->list->isInList());
    }


    function Test_of_populate_todo_list()
    {
        for ($i = 0; $i <= 30; $i++){
            $attributes = array('task'=>'Task number '.($i+3),'due_time'=>Ak::getDate(Ak::time()+(60*60*24*$i)));
            $TodoTask =& new TodoItem($attributes);
            $this->assertTrue($TodoTask->save());
            $this->assertTrue(($TodoTask->task == $attributes['task']) && $TodoTask->due_time == $attributes['due_time']);
        }
    }


    function Test_of_decrementPositionsOnLowerItems()
    {
        $TodoItems =& new TodoItem();
        $TodoItems->transactionStart();
        $this->assertFalse($TodoItems->list->decrementPositionsOnLowerItems());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $TodoItems =& new TodoItem(10);

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
        $TodoItems =& new TodoItem(10);

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
        $TodoItems =& new TodoItem(10);

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
        $TodoItems =& new TodoItem();
        $this->assertFalse($TodoItems->list->getLowerItem());
        $TodoItem = $TodoItems->find(10);

        $LowerItem = $TodoItem->list->getLowerItem();
        $this->assertEqual($LowerItem->task, 'Task number 11');

        $TodoItem = $TodoItems->find(33);
        $this->assertFalse($TodoItem->list->getLowerItem());


        $TodoItems =& new TodoItem();
        $TodoItems->transactionStart();
        $this->assertTrue($TodoItems->deleteAll());

        $this->assertFalse($TodoItems->list->getLowerItem());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_decrementPosition()
    {
        $TodoItems =& new TodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->decrementPosition());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_incrementPosition()
    {
        $TodoItems =& new TodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->incrementPosition());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_moveLower()
    {
        $TodoItems =& new TodoItem();
        $this->assertFalse($TodoItems->list->moveLower());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $this->assertEqual($todo_list[11] , 'Task number 11');

        $TodoItems =& new TodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveLower());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 11');
        $this->assertEqual($todo_list[11] , 'Task number 10');

        $TodoItems =& new TodoItem(33);
        $this->assertFalse($TodoItems->list->moveLower());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();

    }

    function Test_of_getHigherItem()
    {
        $TodoItems =& new TodoItem();
        $this->assertFalse($TodoItems->list->getHigherItem());

        $TodoItem = $TodoItems->find(10);
        $HigherItem = $TodoItem->list->getHigherItem();
        $this->assertEqual($HigherItem->task, 'Task number 9');

        $TodoItem = $TodoItems->find(1);
        $this->assertFalse($TodoItem->list->getHigherItem());


        $TodoItems =& new TodoItem();
        $TodoItems->transactionStart();
        $this->assertTrue($TodoItems->deleteAll());

        $this->assertFalse($TodoItems->list->getHigherItem());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }


    function Test_of_moveHigher()
    {
        $TodoItems =& new TodoItem();
        $this->assertFalse($TodoItems->list->moveHigher());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[9] , 'Task number 9');
        $this->assertEqual($todo_list[10] , 'Task number 10');

        $TodoItems =& new TodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveHigher());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[9] , 'Task number 10');
        $this->assertEqual($todo_list[10] , 'Task number 9');

        $TodoItems =& new TodoItem(1);
        $this->assertFalse($TodoItems->list->moveHigher());

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_assumeBottomPosition()
    {
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');

        $TodoItems =& new TodoItem(10);
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

        $TodoItems =& new TodoItem();
        $this->assertFalse($TodoItems->list->moveToBottom());

        $TodoItems =& new TodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveToBottom());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 11');
        $this->assertEqual($todo_list[32] , 'Task number 33');
        $this->assertEqual($todo_list[33] , 'Task number 10');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();

        $TodoItems =& new TodoItem(33);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveToBottom());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[33] , 'Task number 33');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_incrementPositionsOnHigherItems()
    {
        $TodoItems =& new TodoItem();
        $TodoItems->transactionStart();
        $this->assertFalse($TodoItems->list->incrementPositionsOnHigherItems());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $TodoItems =& new TodoItem(10);
        $this->assertTrue($TodoItems->list->incrementPositionsOnHigherItems());
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[9] , 'Task number 8');
        $this->assertEqual($todo_list[10] , 'Task number 10');  // Task 9&10 are on position 10, so this is ambigious; last one returned by find wins
        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }


    function Test_of_assumeTopPosition()
    {
        $TodoItems =& new TodoItem();
        $TodoItems->transactionStart();

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $TodoItems =& new TodoItem(10);
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

        $TodoItems =& new TodoItem();
        $this->assertFalse($TodoItems->list->moveToTop());

        $TodoItems =& new TodoItem(10);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveToTop());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 9');
        $this->assertEqual($todo_list[2] , 'Email Hilario with new product specs');
        $this->assertEqual($todo_list[1] , 'Task number 10');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();

        $TodoItems =& new TodoItem(1);
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->moveToTop());

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[1] , 'Email Hilario with new product specs');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function Test_of_isFirst()
    {
        $TodoItems =& new TodoItem(1);

        $this->assertTrue($TodoItems->list->isFirst());

        $TodoItems =& new TodoItem(2);
        $this->assertFalse($TodoItems->list->isFirst());

        $TodoItems =& new TodoItem();
        $this->assertFalse($TodoItems->list->isFirst());
    }


    function Test_of_isLast()
    {
        $TodoItems =& new TodoItem(33);
        $this->assertTrue($TodoItems->list->isLast());

        $TodoItems =& new TodoItem(1);
        $this->assertFalse($TodoItems->list->isLast());

        $TodoItems =& new TodoItem();
        $this->assertFalse($TodoItems->list->isLast());
    }


    function Test_of_incrementPositionsOnLowerItems()
    {
        $TodoItems =& new TodoItem();
        $TodoItems->transactionStart();

        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');
        $TodoItems =& new TodoItem(10);
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
        $TodoItems =& new TodoItem(10);
        $TodoItems->transactionStart();

        $TodoItems->list->insertAtPosition(1);
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[1] , 'Task number 10');


        $TodoItems =& new TodoItem('task->','ship new InmoEasy version');
        $TodoItems->list->insertAtPosition(1);
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[1] , 'ship new InmoEasy version');

        $TodoItems =& new TodoItem(10);
        $TodoItems->list->insertAtPosition(10);
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[10] , 'Task number 10');

        $TodoItems =& new TodoItem(33);
        $TodoItems->list->insertAtPosition(40);
        $todo_list = $this->_getTodoList();
        $this->assertEqual($todo_list[40] , 'Task number 33');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }
    function Test_of_insertAt(){} //Alias for insertAtPosition but with default value to 1


    function Test_of_incrementPositionsOnAllItems()
    {
        $TodoItems =& new TodoItem();
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
        $TodoItems =& new TodoItem();
        $TodoItems->transactionStart();

        $this->assertTrue($TodoItems->list->decrementPositionsOnHigherItems(10));

        $todo_list = $this->_getTodoList();

        $this->assertEqual($todo_list[0] , 'Email Hilario with new product specs');
        $this->assertEqual($todo_list[9] , 'Task number 10');

        $TodoItems->transactionFail();
        $TodoItems->transactionComplete();
    }

    function _getTodoList($use_id_as_index = false)
    {
        $TodoItems = new TodoItem();
        $TodoItems = $TodoItems->find('all',array('order'=>'id ASC'));
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

    function test_should_move_up_the_item_with_the_same_position_as_the_inserted()
    {
        $this->installAndIncludeModels(array('TodoList', 'TodoTask'));

        $ListA =& new TodoList(array('name' => 'A'));
        $this->assertTrue($ListA->save());
        
        $ListA->task->create(array('details' => 1));

        $ListB =& new TodoList(array('name' => 'B'));
        $this->assertTrue($ListB->save());
        $ListB->task->create(array('details' => 2));
        $TodoTask =& $ListB->task->create(array('details' => 3));

        $Task1 =& $TodoTask->find('first',array('details'=>1));

        $Task1->list->removeFromList();
        $this->assertTrue($Task1->save());
        $Task1->todo_list->assign($ListB);
        $this->assertTrue($Task1->save());
        $Task1->list->insertAt(2);

        $ListB =& $ListB->find('first',array('name'=>'B'), array('include'=>'tasks'));

        foreach (array_keys($ListB->tasks) as $k){
            $this->assertEqual($ListB->tasks[$k]->get('position'), $k+1);
        }
    }
    /**//**//**/

}

ak_test('AkActiveRecord_actsAsListTestCase',true);

?>
