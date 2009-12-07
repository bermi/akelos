<?php

class PictureInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('pictures', '
        id,
        property_id,
        landlord_id,
        title string(200)'
        );
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('pictures', array('sequence'=>true));
    }
}

