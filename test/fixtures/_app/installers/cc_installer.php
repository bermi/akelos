<?php

class CcInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('ccs', '
        id,
        name'
        );
    }

    function down_1()
    {
        $this->dropTable('ccs');
    }
}

?>