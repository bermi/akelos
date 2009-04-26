<?php

class BbInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('bbs', '
        id,
        aa_id,
        name'
        );
    }

    function down_1()
    {
        $this->dropTable('bbs');
    }
}

?>