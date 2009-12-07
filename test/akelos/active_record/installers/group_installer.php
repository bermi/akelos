<?php

class GroupInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('groups', 'id,name,description,created_at');
    }

    public function down_1() {
        $this->dropTable('groups');
    }
}

