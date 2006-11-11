<?='<?php'?>

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');
require_once(AK_CONTROLLERS_DIR.DS.'<?=AkInflector::underscore($class_name)?>_controller.php');


class <?=$class_name?>ControllerTest extends  UnitTestCase
{
    function test_<?=$class_name?>()
    {
        $this->assertTrue(false, '<?=$class_name?>Controller has not being tested');
    }
}


Ak::test('<?=$class_name?>ControllerTest',true);

?>
