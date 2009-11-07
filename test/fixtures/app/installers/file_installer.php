<?php

class FileInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('files', '
        id integer max=10 auto increment primary,
        name string 50'
        );
    }

    function uninstall()
    {
        $this->dropTable('files', array('sequence'=>true));
    }
}

?>