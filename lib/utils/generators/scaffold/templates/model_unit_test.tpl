<?php  echo '<?php'?>

// To run this test calling ./script/test unit/app/models/<?php  echo AkInflector::underscore($model_name); ?>
// More about testing at http://wiki.akelos.org/testing-guide

class <?php  echo $model_name?>TestCase extends AkUnitTest
{
    function test_setup()
    {
        $this->installAndIncludeModels('<?php  echo $model_name?>');
    }
    
    function test_<?php  echo $model_name?>()
    {
        $this->assertTrue(false,'Unit test for <?php  echo $model_name?> not implemented');
    }
}

?>
