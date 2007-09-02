<?php  echo '<?php'?>

class <?php  echo $class_name?>Installer extends AkInstaller
{
    function up_1()
    {
        /** /
        $this->createTable('<?php  echo AkInflector::tableize($class_name); ?>', "
          id,
          created_at,
          updated_at
        ");
        /**/
    }
    
    function down_1()
    {
        /** /
        $this->dropTable('<?php  echo AkInflector::tableize($class_name); ?>');
        /**/
    }
    
}

?>