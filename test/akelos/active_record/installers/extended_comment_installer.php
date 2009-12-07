<?php

class ExtendedCommentInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('extended_comments', "id,name,body,extended_post_id,created_at");
    }

    public function up_2() {
        $this->addColumn('extended_comments', 'name');
    }

    public function down_1() {
        $this->dropTable('extended_comments', array('sequence'=>true));
    }

    public function down_2() {
        $this->removeColumn('extended_comments', 'name');
    }
}

