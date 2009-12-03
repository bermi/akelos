<?php  echo '<?php'?>


// Unit test for <?php  echo $class_name?>. (Testing docs at http://www.akelos.org/wiki/testing-guide)
// Run this test with the command
//  ./script/test model <?php  echo $class_name?>


class <?php  echo $class_name?>TestCase extends  AkUnitTest
{

    function test_setup()
    {
        $this->installAndIncludeModels('<?php  echo $class_name?>');
    }
    
    function test_should_be_added()
    {
        // An instance of <?php  echo $class_name?> model is available at 
        // $this-><?php  echo $class_name; ?>
        
        $this->assertTrue(false);
    }
}


?>
