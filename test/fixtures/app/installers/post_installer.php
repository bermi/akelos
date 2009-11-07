<?php

class PostInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('posts', 'id, title, body, hip_factor int, comments_count, posted_on, expires_at');
    }

    function uninstall()
    {
        $this->dropTable('posts', array('sequence'=>true));
        $this->dropTable('posts_users');
    }
}

?>