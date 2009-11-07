<?php

class EventInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('events', '
        id integer max=10 auto increment primary,
        type string 50,
        description text'
        );
    }

    function uninstall()
    {
        $this->dropTable('events', array('sequence'=>true));
    }
}

?>