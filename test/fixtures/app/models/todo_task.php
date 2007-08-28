<?php

class TodoTask extends ActiveRecord
{
    var $acts_as = array('list'=> array('scope'=> array('todo_list_id','is_done = 0')));
    var $belongs_to = array('todo_list');
}

?>