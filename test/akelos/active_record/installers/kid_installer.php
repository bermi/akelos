<?php

class KidInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('kids', '
        id,
        father_id,
        name,
        created_at'
        );
    }

    function down_1()
    {
        $this->dropTable('kids');
    }
}

?>