<?php

class AaInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('aas', '
        id,
        name'
        );
    }

    function down_1()
    {
        $this->dropTable('aas');
    }
}

?>