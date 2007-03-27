<?php  echo '<?php'?>

class <?php  echo $class_name?>Installer extends AkInstaller
{
    function up_1()
    {
        $this->createTable('<?php  echo $table_name?>', "
          id integer not null auto increment pk
        ");  
    }
    
    function down_1()
    {
        $this->dropTable('<?php  echo $table_name?>');  
    }
    
}

?>