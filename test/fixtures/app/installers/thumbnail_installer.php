<?php

class ThumbnailInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('thumbnails', '
        id integer(11) auto increment primary key,
        photo_id integer(10) key,
        owner string (40) default=Picture,
        caption string (200)
        '
        );
    }

    function down_1()
    {
        $this->dropTable('thumbnails', array('sequence'=>true));
    }
}

?>