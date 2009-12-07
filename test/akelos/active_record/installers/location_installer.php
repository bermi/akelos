<?php

class LocationInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
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

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('locations');
    }
}

