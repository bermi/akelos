<?php  echo '<?php'?>

defined('AK_TEST_DATABASE_ON') ? null : define('AK_TEST_DATABASE_ON', true);
require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class <?php  echo $class_name?>Test extends AkUnitTest
{
    function test_setup()
    {
        $this->installAndIncludeModels('<?php  echo $class_name?>');
    }
    
    function test_<?php  echo $class_name?>()
    {
        $this->assertTrue(false,'Unit test for <?php  echo $class_name?> not implemented');
    }
}


ak_test('<?php  echo $class_name?>Test',true);

?>
