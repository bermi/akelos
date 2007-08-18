<?php

class PostInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('posts', 'id, title, body, posted_on');
    }

    function uninstall()
    {
        $this->dropTable('posts');
    }
}

?>