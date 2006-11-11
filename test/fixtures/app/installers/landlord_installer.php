<?php

class LandlordInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('landlords', '
        id integer max=10 auto increment primary,
        name string limit=200'
        );
    }

    function uninstall()
    {
        $this->dropTable('landlords', array('sequence'=>true));
    }
}

?>