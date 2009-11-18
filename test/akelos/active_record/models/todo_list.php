<?php

class TodoList extends ActiveRecord
{
    public $acts_as = 'list';
    public $has_many = array(
    'tasks'=>array(
        'dependent' => 'destroy',
        'order'=>'position ASC',
        'class_name' => 'TodoTask',
        'handler_name' => 'task')
    );

}
