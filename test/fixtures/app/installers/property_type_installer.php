<?php

class PropertyTypeInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('property_types', 
        '
        id integer max=10 auto increment primary,
        description string(255)
        '
        );
    }

    function uninstall()
    {
        $this->dropTable('property_types', array('sequence'=>true));
    }
}

?>