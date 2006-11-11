<?php

class PropertyInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('properties', 
        '
        id integer max=10 auto increment primary,
        description string(255),
        details text,
        landlord_id integer,
        price integer,
        location string limit=200'
        );
    }

    function uninstall()
    {
        $this->dropTable('properties', array('sequence'=>true));
        $this->dropTable('properties_property_types', array('sequence'=>true));
        @Ak::file_delete(AK_MODELS_DIR.DS.'property_property_type.php');
    }
}

?>