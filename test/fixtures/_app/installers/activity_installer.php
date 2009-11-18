<?php

class ActivityInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('activities', '
        id,
        kid_id,
        name,
        created_at'
        );
    }

    function down_1()
    {
        $this->dropTable('activities');
    }
}

?>