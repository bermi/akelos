<?php

class PanoramaInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('panoramas', '
        id integer max=10 auto increment primary,
        property_id integer,
        title string limit=200'
        );
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('panoramas', array('sequence'=>true));
    }
}

