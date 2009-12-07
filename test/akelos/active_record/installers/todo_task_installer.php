<?php

class TodoTaskInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('todo_tasks',
           "id,
        todo_list_id,
        details,
        position integer default 0,
        is_done,
            created_at"
        );
    }

    public function down_1() {
        $this->dropTable('todo_tasks', array('sequence'=>true));
    }
}

