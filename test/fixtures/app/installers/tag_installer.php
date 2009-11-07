<?php

class TagInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('tags', '
        id integer max=10 auto increment primary,
        score int default 100,
        name string 50'
        );
    }

    function uninstall()
    {
        $this->dropTable('tags', array('sequence'=>true));
    }
}

?>