<?php

class FileInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('files', '
        id integer max=10 auto increment primary,
        name string 50'
        );
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('files', array('sequence'=>true));
    }
}

