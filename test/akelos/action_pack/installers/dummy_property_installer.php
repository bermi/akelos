<?php

class DummyPropertyInstaller extends AkInstaller
{
    public function up_1($version = null, $options = array()) {
        $this->createTable('dummy_properties',
        '
        id,
        description string(255),
        details text,
        price int,
        location string(200)',
        array('timestamp'=>false));
    }

    public function down_2($version = null, $options = array()) {
        $this->dropTable('dummy_properties', array('sequence'=>true));
    }
}
