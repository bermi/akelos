<?php

class PostInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('posts', 'id, title, body, posted_on, expires_at');
    }

    function uninstall()
    {
        $this->dropTable('posts', array('sequence'=>true));
    }
}

?>