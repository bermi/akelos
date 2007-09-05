<?php  echo '<?php'?>

// To run this test calling ./script/test unit/app/models/<?php echo $underscored_model_name; ?> 
// More about testing at http://wiki.akelos.org/testing-guide

class <?php  echo $class_name?>TestCase extends AkUnitTest
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

?>
