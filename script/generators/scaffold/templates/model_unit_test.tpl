<?='<?php'?>

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');
require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
require_once(AK_APP_DIR.DS.'shared_model.php');
require_once(AK_MODELS_DIR.DS.'<?=$singular_name?>.php');

class <?=$model_name?>Test extends  AkUnitTest
{
    function test_setup()
    {
        require_once(AK_APP_DIR.DS.'installers'.DS.'<?=$singular_name?>_installer.php');
        $installer = new <?=$model_name?>Installer();
        $installer->uninstall();
        $installer->install();    
    }
    
    function test_<?=$model_name?>()
    {
        $this->assertTrue(false,'Unit test for <?=$model_name?> not implemented');
    }
}


Ak::test('<?=$model_name?>Test',true);

?>
