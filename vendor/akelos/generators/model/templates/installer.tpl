<?php  echo '<?php'?>

// Run this installer by calling
// ./script/migrate <?php  echo $class_name?> up
// ./script/migrate <?php  echo $class_name?> down 1

class <?php  echo $class_name?>Installer extends AkInstaller
{
    public function up_1() {
        $this->createTable('<?php  echo AkInflector::tableize($class_name); ?>', "
        <?php  if(empty($table_columns)) : ?>
          id,
          name
         <?php else: ?>
         <?php echo $table_columns; ?>
         <?php endif; ?>
        ");
    }
    
    public function down_1() {
        $this->dropTable('<?php  echo AkInflector::tableize($class_name); ?>');
    }
}
