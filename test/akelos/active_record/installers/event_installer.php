<?php

class EventInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('events', '
        id integer max=10 auto increment primary,
        type string 50,
        description text'
        );
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('events', array('sequence'=>true));
    }
}

