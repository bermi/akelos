<?='<?php'?>

/**
* This is the <?=$model_name?> Installer. And installer allows you to perform
* database migrations in the same way your file versions are managed by subversion.
*
* You just need to create up and down methods for each database version.
*
* Once you've added the database structure, you just need to call
*
* ./script/migrate <?=$model_name?> install
*
* And your database will be upgraded to the latest revision
*/
class <?=$model_name?>Installer extends AkInstaller
{
    function up_1()
    {
        $this->createTable('<?=$plural_name?>', "
          id,
          name,
          description,
          created_at,
          updated_at
        ");  
    }
    
    function down_1()
    {
        $this->dropTable('<?=$plural_name?>');  
    }
    
}

?>