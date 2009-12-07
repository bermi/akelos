<?php

class PostInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('posts', 'id, title, body, hip_factor int, comments_count, posted_on, expires_at');
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('posts', array('sequence'=>true));
        $this->dropTable('posts_users');
    }
}

