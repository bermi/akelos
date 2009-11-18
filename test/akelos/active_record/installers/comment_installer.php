<?php

class CommentInstaller extends AkInstaller
{
    public function up_1()
    {
        $this->createTable('comments', "id,name,body,post_id,created_at");
    }

    public function up_2()
    {
        $this->addColumn('comments', 'name');
    }

    public function down_1()
    {
        $this->dropTable('comments', array('sequence'=>true));
    }

    public function down_2()
    {
        $this->removeColumn('comments', 'name');
    }
}

