<?php  echo "<?php\n"?>

require_once(dirname(__FILE__).'/../shared/config/config.php');

/**
 * Unit test for <?php  echo $class_name?>. (Testing docs at http://www.akelos.org/wiki/testing-guide)
 * 
 * Run this test with the command
 *
 *     ./makelos test:units <?php  echo $class_name."\n";?>
 *
 * or
 * 
 *     php test/unit/<?php echo $test_file_name."\n"; ?>
 */

class <?php  echo $class_name?>TestCase extends AkUnitTest
{
    public function test_setup() {
        $this->installAndIncludeModels('<?php  echo $class_name?>');
    }
    
    public function test_should_be_added() {
        // An instance of <?php  echo $class_name?> model is available at 
        // $this-><?php  echo $class_name; ?>

        $this->assertTrue(true);
    }
}

ak_test_case('<?php  echo $class_name?>TestCase');
