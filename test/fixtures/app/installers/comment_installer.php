<?php
class CommentInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('comments', "id,name,body,post_id,created_at");
    }

    function up_2()
    {
        $this->addColumn('comments', 'name');
    }

    function down_1()
    {
        $this->dropTable('comments');
    }
    
    function down_2()
    {
        $this->removeColumn('comments', 'name');
    }
}

?>