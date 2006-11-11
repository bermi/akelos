<?='<?php'?>

class <?=$class_name?>Installer extends AkInstaller
{
    function up_1()
    {
        $this->createTable('<?=$table_name?>', "
          id integer not null auto increment pk
        ");  
    }
    
    function down_1()
    {
        $this->dropTable('<?=$table_name?>');  
    }
    
}

?>