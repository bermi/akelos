<?php

class TodoList extends ActiveRecord
{
    var $acts_as = 'list';
    var $has_many = array(
    'tasks'=>array(
        'dependent' => 'destroy',
        'order'=>'position ASC',
        'class_name' => 'TodoTask',
        'handler_name' => 'task')
    );

}  
?>