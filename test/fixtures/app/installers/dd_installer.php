<?php

class DdInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('dds', '
        id,
        mycc_id,
        name'
        );
    }

    function down_1()
    {
        $this->dropTable('dds');
    }
}

?>