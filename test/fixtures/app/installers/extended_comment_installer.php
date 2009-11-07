<?php
class ExtendedCommentInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('extended_comments', "id,name,body,extended_post_id,created_at");
    }

    function up_2()
    {
        $this->addColumn('extended_comments', 'name');
    }

    function down_1()
    {
        $this->dropTable('extended_comments', array('sequence'=>true));
    }
    
    function down_2()
    {
        $this->removeColumn('extended_comments', 'name');
    }
}

?>