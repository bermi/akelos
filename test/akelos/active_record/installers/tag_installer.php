<?php

class TagInstaller extends AkInstaller
{
    public function install($version = null, $options = array())
    {
        $this->createTable('tags', '
        id integer max=10 auto increment primary,
        score int default 100,
        name string 50'
        );
    }

    public function uninstall($version = null, $options = array())
    {
        $this->dropTable('tags', array('sequence'=>true));
    }
}

