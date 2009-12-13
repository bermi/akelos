<?php  echo '<?php'?>

require_once(dirname(__FILE__).'/../../shared/config/config.php');

class <?php  echo $class_name; ?>Controller_TestCase extends AkActionControllerTest
{
    function test_should_work() {
        // Replace this with your real tests.
        $this->assertTrue(true);
    }
}

ak_test_case('<?php  echo $class_name; ?>Controller_TestCase');