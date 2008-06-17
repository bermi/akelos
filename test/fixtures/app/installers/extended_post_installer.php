<?php

class ExtendedPostInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('extended_posts', 'id, title, body, hip_factor int, comments_count, posted_on, expires_at, type');
    }

    function uninstall()
    {
        $this->dropTable('extended_posts', array('sequence'=>true));
    }
}

?>