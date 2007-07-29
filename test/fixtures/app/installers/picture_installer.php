<?php

class PictureInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('pictures', '
        id integer max=10 auto increment primary,
        property_id,
        title string limit=200'
        );
    }

    function uninstall()
    {
        $this->dropTable('pictures', array('sequence'=>true));
    }
}

?>