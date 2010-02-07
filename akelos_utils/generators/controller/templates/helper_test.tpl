<?php  echo '<?php'?>

require_once(dirname(__FILE__).'/../../shared/config/config.php');

class <?php  echo $class_name?>Helper_TestCase extends AkHelperTest
{
    // Unless you define public $skip_helper_instantation = true;
    // AkHelperTest::_construct will instatiate the <?php  echo $class_name?>Helper
    // which you can reference at $this->Helper.
    // You can get a new instance by calling  $this->getInstance(); 
    // this returns an instace of the helper with
    // the <?php  echo $class_name?>Controller context
    
    function test_should_work() {
        // Replace this with your real tests.
        $this->assertTrue(true);
    }
}

ak_test_case('<?php  echo $class_name?>Helper_TestCase');