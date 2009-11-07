<?php

class PropertyInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('properties', 
        '
        id,
        description string(255),
        details text,
        price int,
        location string(200)',
        array('timestamp'=>false));
    }

    function uninstall()
    {
        $this->dropTable('properties', array('sequence'=>true));
        $this->dropTable('properties_property_types', array('sequence'=>true));
        @Ak::file_delete(AK_MODELS_DIR.DS.'property_property_type.php');
    }
}

?>