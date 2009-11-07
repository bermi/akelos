<?php

class TodoApi extends AkActionWebserviceApi
{
    function __construct()
    {
        $this->addApiMethod('complete_item', array(
        'expects'=> array('int' => 'Item Id'),
        'returns'=> array('struct' => 'Todo Item')));

        /**/

        $this->addApiMethod('create_item', array(
        'expects'=> array('int'=>'List Id', 'struct' => 'A struct like: array("name" => "Implement this method", "responsible_id" => 232, "notify"=> true)'),

        'returns'=> array('struct' => 'Todo Item')));

        /**/

        $this->addApiMethod('create_list', array(
        'expects'=> array('struct' => 'A struct like: array("name" => "Akelos integration tasks", "description" => "This list should be completed in less than one week", "private" => true)'),

        'returns'=> array('struct' => 'Todo List')));

        /**/

        $this->addApiMethod('delete_item', array(
        'expects'=> array('int' => 'Item Id'),

        'returns'=> 'bool'));

        /**/

        $this->addApiMethod('delete_list', array(
        'expects'=> array('int' => 'List Id'),

        'returns'=> 'bool'));


        /**/

        $this->addApiMethod('get_list', array(
        'expects'=> array('int' => 'List Id'),

        'returns'=> array('struct' => 'Todo List')));



        /**/

        $this->addApiMethod('get_lists', array(
        'expects'=> array('bool' => 'Include completed lists true/false'),

        'returns'=> array('struct' => 'Todo Lists')));


        /**/

        $this->addApiMethod('move_item', array(
        'expects'=> array('int'=>'Item Id', 'struct' => 'A struct containing the new position and an optional list id: array("position" => 3, "todo_list_id" => 12)'),

        'returns'=> 'bool'));


        /**/

        $this->addApiMethod('move_list', array(
        'expects'=>  array('int' => 'List Id', 'int' => 'The new position'),

        'returns'=> 'bool'));

        /**/

        $this->addApiMethod('uncomplete_item', array(
        'expects'=>  array('int' => 'Item Id'),

        'returns'=> 'bool'));
        /**/

        $this->addApiMethod('update_item', array(
        'expects'=> array('int' => 'Item Id', 'struct' => 'A struct like: array("todo_list_id" => 12, "name" => "Implement this method", "responsible_id" => 232, "notify"=> true)'),

        'returns'=> array('struct' => 'Todo Item')));

        /**/

        $this->addApiMethod('update_list', array(
        'expects'=> array('int' => 'List Id', 'struct' => 'A struct like: array("name" => "Akelos integration tasks", "description" => "This list should be completed in less than one week", "private" => true)'),

        'returns'=> array('struct' => 'Todo List')));

    }
}


?>