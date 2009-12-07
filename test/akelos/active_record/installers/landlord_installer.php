<?php

class LandlordInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('landlords', '
        id,
        name string(200)'
        );
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('landlords', array('sequence'=>true));
    }
}
