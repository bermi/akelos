<?php

class PropertyTypeInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('property_types',
        '
        id integer max=10 auto increment primary,
        description string(255)
        '
        );
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('property_types', array('sequence'=>true));
    }
}

