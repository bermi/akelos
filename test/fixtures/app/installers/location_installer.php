<?php

class LocationInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('locations', '
        id,
        parent_id,
        lft integer index,
        rgt integer index,
        owner_id int default 1,
        name,
        group_id'
        );
    }

    function uninstall()
    {
        $this->dropTable('locations');
    }
}

?>