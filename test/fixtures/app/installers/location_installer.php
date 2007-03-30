<?php

class LocationInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('locations', '
        id,
        parent_id,
        lft integer(10) index,
        rgt integer(10) index,
        name'
        );
    }

    function uninstall()
    {
        $this->dropTable('locations');
    }
}

?>