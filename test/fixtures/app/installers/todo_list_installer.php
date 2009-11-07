<?php

class TodoListInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('todo_lists', "
        id,
        name,
        description,
        position integer default 1,
        created_at");
    }

    function down_1()
    {
        $this->dropTable('todo_lists', array('sequence'=>true));
    }

}

?>