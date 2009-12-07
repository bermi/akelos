<?php

class DummyLandlordInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('dummy_landlords', '
        id,
        name string(200)'
        );
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('dummy_landlords', array('sequence'=>true));
    }
}
