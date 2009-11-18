<?php

class EeInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('ees', '
        id,
        name'
        );
    }

    function down_1()
    {
        $this->dropTable('ees');
    }
}

?>