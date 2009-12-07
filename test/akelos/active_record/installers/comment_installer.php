<?php

class CommentInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('comments', "id,name,body,post_id,created_at");
    }

    public function down_1() {
        $this->dropTable('comments', array('sequence'=>true));
    }
}

