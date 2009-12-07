<?php

class TodoTask extends ActiveRecord
{
    public $acts_as = array('list'=> array('scope'=> array('todo_list_id','is_done = \'0\'')));
    public $belongs_to = array('todo_list');
}

