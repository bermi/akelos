<?php

class TaggingInstaller extends AkInstaller
{
    public function up_1($version = null, $options = array()) {
        $this->createTable('taggings', '
        id integer max=10 auto increment primary,
        file_id integer,
        tag_id integer,
        counter integer default 0,
        updated_at datetime'
        );
    }

    public function down_1($version = null, $options = array()) {
        $this->dropTable('taggings', array('sequence'=>true));
    }
}

