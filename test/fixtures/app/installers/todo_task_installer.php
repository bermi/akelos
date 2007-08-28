<?php

class TodoTaskInstaller extends AkInstaller
{
    function up_1()
    {
        $this->modifyTable('todo_tasks', "
        id,
        todo_list_id,
        details,
        position integer default 0,
        is_done,
        created_at");
    }
    
    function down_1()
    {
        $this->dropTable('todo_tasks', array('sequence'=>true));
    }

}

?>