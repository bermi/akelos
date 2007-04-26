<?php  echo '<?php'?>

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');
require_once(AK_CONTROLLERS_DIR.DS.'<?php  echo AkInflector::underscore($class_name)?>_controller.php');


class <?php  echo $class_name?>ControllerTest extends  UnitTestCase
{
    function test_<?php  echo $class_name?>()
    {
        $this->assertTrue(false, '<?php  echo $class_name?>Controller has not being tested');
    }
}


ak_test('<?php  echo $class_name?>ControllerTest',true);

?>
