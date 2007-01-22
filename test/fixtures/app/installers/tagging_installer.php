<?php

class TaggingInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('taggings', '
        id integer max=10 auto increment primary,
        file_id integer,
        tag_id integer,
        counter integer default 0,
        updated_at datetime'
        );
    }

    function uninstall()
    {
        $this->dropTable('taggings', array('sequence'=>true));
    }
}

?>