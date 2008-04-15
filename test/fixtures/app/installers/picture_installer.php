<?php

class PictureInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('pictures', '
        id,
        property_id,
        landlord_id,
        title string(200)'
        );
    }

    function uninstall()
    {
        $this->dropTable('pictures', array('sequence'=>true));
    }
}

?>