<?php

class GroupInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('groups', 'id,name,description,created_at');
    }
    
    function down_1()
    {
        $this->dropTable('groups');
    }
}

?>
