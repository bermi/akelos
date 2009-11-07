<?php  echo '<?php'?>

class <?php  echo $class_name?>Installer extends AkInstaller
{
    function up_1()
    {
        $this->createTable('<?php  echo AkInflector::tableize($class_name); ?>', "
        <?php  if(empty($table_columns)) : ?>
          id,
          name
         <?php else: ?>
         <?php echo $table_columns; ?>
         <?php endif; ?>
        ");
    }
    
    function down_1()
    {
        $this->dropTable('<?php  echo AkInflector::tableize($class_name); ?>');
    }
}


?>