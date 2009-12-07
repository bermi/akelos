<?php

class ExtendedPostInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('extended_posts', 'id, title, body, hip_factor int, comments_count, posted_on, expires_at, type');
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('extended_posts', array('sequence'=>true));
    }
}

