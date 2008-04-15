<?php

class LandlordInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('landlords', '
        id,
        name string(200)'
        );
    }

    function uninstall()
    {
        $this->dropTable('landlords', array('sequence'=>true));
    }
}

?>