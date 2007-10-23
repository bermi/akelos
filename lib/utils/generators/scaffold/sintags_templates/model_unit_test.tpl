<?php  echo '<?php'?>


// Unit test for <?php  echo $plural_name?>. (Testing docs at http://wiki.akelos.org/testing-guide)
// Run this test with the command
//  ./script/test model <?php  echo $model_name?>


class <?php  echo $model_name?>TestCase extends  AkUnitTest
{
<?php 
echo !empty($module_preffix) ? '    var $module = \''.trim($module_preffix,DS).'\';' : ''
?>

    function test_setup()
    {
        $this->installAndIncludeModels('<?php  echo $model_name?>');
    }
    
    function test_should_be_added()
    {
        // An instance of <?php  echo $model_name?> model is available at 
        // $this-><?php  echo $model_name; ?>
        
        $this->assertTrue(false);
    }
}


?>
