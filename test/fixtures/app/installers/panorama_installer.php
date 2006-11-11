<?php

class PanoramaInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('panoramas', '
        id integer max=10 auto increment primary,
        property_id integer,
        title string limit=200'
        );
    }

    function uninstall()
    {
        $this->dropTable('panoramas', array('sequence'=>true));
    }
}

?>