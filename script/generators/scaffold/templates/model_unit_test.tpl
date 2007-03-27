<?php  echo '<?php'?>

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
require_once(AK_APP_DIR.DS.'shared_model.php');
require_once(AK_MODELS_DIR.DS.'<?php  echo $singular_name?>.php');

class <?php  echo $model_name?>Test extends  AkUnitTest
{
    function test_setup()
    {
        require_once(AK_APP_DIR.DS.'installers'.DS.'<?php  echo $singular_name?>_installer.php');
        $installer = new <?php  echo $model_name?>Installer();
        $installer->uninstall();
        $installer->install();    
    }
    
    function test_<?php  echo $model_name?>()
    {
        $this->assertTrue(false,'Unit test for <?php  echo $model_name?> not implemented');
    }
}


Ak::test('<?php  echo $model_name?>Test',true);

?>
