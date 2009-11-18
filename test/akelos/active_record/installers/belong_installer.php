<?php

class BelongInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('belongs', '
        id,
        many_id,
        name'
        );
    }

    function down_1()
    {
        $this->dropTable('belongs');
    }
}

?>