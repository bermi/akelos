<?php

class ManyInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('manies', '
        id,
        name'
        );
    }

    function down_1()
    {
        $this->dropTable('manies');
    }
}

?>