<?php

class DummyPictureInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('dummy_pictures', '
        id,
        property_id,
        landlord_id,
        title string(200)'
        );
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('dummy_pictures', array('sequence'=>true));
    }
}

