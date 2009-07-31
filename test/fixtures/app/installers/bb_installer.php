<?php

class BbInstaller extends AkInstaller
{
    function up_2()
    {
        $this->addColumn('bbs','languages string(200)');
        $this->addColumn('bbs','other string(200)');
    }
    function down_2()
    {
        $this->removeColumn('bbs','languages');
        $this->removeColumn('bbs','other');
    }
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