<?php

class ThumbnailInstaller extends AkInstaller
{
    public function install($version = null, $options = array()) {
        $this->createTable('thumbnails', '
        id integer(11) auto increment primary key,
        photo_id integer,
        owner string (40) default \'Picture\',
        caption string (200)
        '
        );
    }

    public function uninstall($version = null, $options = array()) {
        $this->dropTable('thumbnails', array('sequence'=>true));
    }
}
