<?php

class FatherInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('fathers', '
        id,
        name,
        created_at'
        );
    }

    function down_1()
    {
        $this->dropTable('fathers');
    }
}

?>